<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SyncAdminFromEnvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:sync-from-env {--force : Force sync without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync the single Super Admin account strictly from .env credentials and purge extra admins';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = config('auth.admin_credentials.email') ?? env('ADMIN_EMAIL');
        $password = config('auth.admin_credentials.password') ?? env('ADMIN_PASSWORD');
        $name = config('auth.admin_credentials.name') ?? env('ADMIN_NAME', 'Master Admin');
        $phone = config('auth.admin_credentials.phone') ?? env('ADMIN_PHONE', '08000000000');

        if (empty($email) || empty($password)) {
            $this->error('ADMIN_EMAIL or ADMIN_PASSWORD is not configured in .env.');
            return self::FAILURE;
        }

        // Strictly enforce only 1 admin: purge any additional admin entries
        $deletedCount = Admin::where('id', '>', 1)->delete();
        if ($deletedCount > 0) {
            $this->warn("Removed {$deletedCount} extra admin account(s). System strictly enforces exactly 1 Super Admin.");
        }

        $admin = Admin::find(1);
        if ($admin) {
            $admin->name = $name;
            $admin->email = $email;
            $admin->password = Hash::make($password);
            $admin->phone = $phone;
            $admin->admin_role_id = 1;
            $admin->status = 1;
            $admin->save();

            $this->info("Super Admin (ID 1) updated from .env:");
        } else {
            $admin = Admin::create([
                'id' => 1,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'phone' => $phone,
                'admin_role_id' => 1,
                'status' => 1,
            ]);

            $this->info("Super Admin (ID 1) created from .env:");
        }

        $this->line("  Name:  {$admin->name}");
        $this->line("  Email: {$admin->email}");
        $this->line("  Role:  Super Admin (Role ID 1)");
        $this->line("  Total Admins in Database: " . Admin::count());

        return self::SUCCESS;
    }
}
