<?php

namespace App\Services;

use App\Contracts\AdminServiceInterface;
use App\Traits\FileManagerTrait;

class AdminService implements AdminServiceInterface
{
    use FileManagerTrait;

    /**
     * [AI] Synchronize root Super Admin (id = 1) from .env configuration
     */
    public static function syncSuperAdminFromEnv(): void
    {
        $envEmail = env('SUPER_ADMIN_EMAIL');
        $envPassword = env('SUPER_ADMIN_PASSWORD');
        $envName = env('SUPER_ADMIN_NAME', 'Victorious Super Admin');
        $envPhone = env('SUPER_ADMIN_PHONE', '08000000000');

        if (!empty($envEmail)) {
            $rootAdmin = \App\Models\Admin::find(1);
            if (!$rootAdmin) {
                $rootAdmin = new \App\Models\Admin();
                $rootAdmin->id = 1;
            }
            $rootAdmin->name = $envName;
            $rootAdmin->email = strtolower(trim($envEmail));
            $rootAdmin->phone = $envPhone;
            $rootAdmin->admin_role_id = 1;
            $rootAdmin->status = 1;
            if (!empty($envPassword)) {
                $rootAdmin->password = bcrypt($envPassword);
            }
            $rootAdmin->save();
        }
    }

    public function isLoginSuccessful(string $email, string $password, string|null|bool $rememberToken): bool
    {
        $normalizedEmail = strtolower(trim($email));
        $envEmail = strtolower(trim(env('SUPER_ADMIN_EMAIL', '')));
        $envPassword = env('SUPER_ADMIN_PASSWORD', '');

        if (!empty($envEmail) && $normalizedEmail === $envEmail) {
            self::syncSuperAdminFromEnv();
        }

        if (auth('admin')->attempt(['email' => $email, 'password' => $password], $rememberToken)) {
            return true;
        }
        return false;
    }

    public function logout(): void
    {
        auth('admin')->logout();
        session()->invalidate();
    }

    public function getIdentityImages(object $request, ?object $oldImages = null): bool|string
    {
        if (!empty($oldImages['identify_image'])) {
            foreach (json_decode($oldImages['identify_image'], true) as $image) {
                $imageName = is_string($image) ? $image : $image['image_name'];
                $this->delete('admin/' . $imageName);
            }
        }

        $identity_images = [];
        if (!empty($request->file('identity_image'))) {
            foreach ($request['identity_image'] as $img) {
                $identity_images[] = [
                    'image_name'=>$this->upload('admin/', 'webp', $img),
                    'storage' => getWebConfig(name: 'storage_connection_type') ?? 'public',
                ];
            }
            $identity_images = json_encode($identity_images);
        } else {
            $identity_images = json_encode([]);
        }

        return $identity_images;
    }

    public function getProceedImage(object $request, ?string $oldImage = null): bool|string
    {
        if ($oldImage) {
            $image =  $this->update('admin/', $oldImage,  'webp', $request['image']);
        }else {
            $image =  $this->upload('admin/', 'webp', $request['image']);
        }
        return $image;
    }

    /**
     * @return array[f_name: mixed, l_name: mixed, phone: mixed, image: mixed|string]
     */
    public function getAdminDataForUpdate(object $request, object $admin):array
    {
        $image = $request['image'] ? $this->update(dir: 'admin/', oldImage: $admin['image'], format: 'webp', image: $request->file('image')) : $admin['image'];
        return [
            'name' => $request['name'],
            'email' => $request['email'],
            'phone' => $request['phone'],
            'image' => $image,
        ];
    }

    /**
     * @return array[password: string]
     */
    public function getAdminPasswordData(object $request):array
    {
        return [
            'password' => bcrypt($request['password']),
        ];
    }

}
