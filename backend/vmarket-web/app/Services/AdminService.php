<?php

namespace App\Services;

use App\Contracts\AdminServiceInterface;
use App\Traits\FileManagerTrait;

class AdminService implements AdminServiceInterface
{
    use FileManagerTrait;

    public function isLoginSuccessful(string $email, string $password, string|null|bool $rememberToken): bool
    {
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

    public function syncAdminFromEnvIfConfigured(): void
    {
        $envEmail = config('auth.admin_credentials.email') ?? env('ADMIN_EMAIL');
        $envPassword = config('auth.admin_credentials.password') ?? env('ADMIN_PASSWORD');
        $envName = config('auth.admin_credentials.name') ?? env('ADMIN_NAME', 'Master Admin');
        $envPhone = config('auth.admin_credentials.phone') ?? env('ADMIN_PHONE', '08000000000');

        if (!empty($envEmail) && !empty($envPassword)) {
            // Strictly enforce only 1 admin: purge any additional admin records
            \App\Models\Admin::where('id', '>', 1)->delete();

            $admin = \App\Models\Admin::find(1);
            if ($admin) {
                $needsUpdate = false;
                if ($admin->email !== $envEmail) {
                    $admin->email = $envEmail;
                    $needsUpdate = true;
                }
                if ($admin->name !== $envName) {
                    $admin->name = $envName;
                    $needsUpdate = true;
                }
                if (!\Illuminate\Support\Facades\Hash::check($envPassword, $admin->password)) {
                    $admin->password = \Illuminate\Support\Facades\Hash::make($envPassword);
                    $needsUpdate = true;
                }
                if (!$admin->status) {
                    $admin->status = 1;
                    $needsUpdate = true;
                }
                if ($admin->admin_role_id !== 1) {
                    $admin->admin_role_id = 1;
                    $needsUpdate = true;
                }
                if ($needsUpdate) {
                    $admin->save();
                }
            } else {
                \App\Models\Admin::create([
                    'id' => 1,
                    'name' => $envName,
                    'email' => $envEmail,
                    'password' => \Illuminate\Support\Facades\Hash::make($envPassword),
                    'phone' => $envPhone,
                    'admin_role_id' => 1,
                    'status' => 1,
                ]);
            }
        }
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
