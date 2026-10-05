<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        // [AI] Rotate only demonstrably public-value OAuth passwords; preserve customer-selected passwords and all money.
        DB::table('users')->whereNotNull('social_id')->orderBy('id')->chunkById(100, function ($users) {
            foreach ($users as $user) DB::transaction(function () use ($user) {
                $user = DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
                $matches = false;
                foreach (array_filter([(string)$user->social_id, $user->login_medium === 'apple' ? (string)$user->email : null]) as $publicValue) {
                    if (\Illuminate\Support\Facades\Hash::check($publicValue, $user->password)) $matches = true;
                }
                if (!$matches) return;
                $changes = ['password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(64)),
                    'credential_version' => DB::raw('credential_version + 1')];
                foreach (['remember_token','temporary_token'] as $column) if (Schema::hasColumn('users',$column)) $changes[$column] = null;
                DB::table('users')->where('id',$user->id)->update($changes);
                if (Schema::hasTable('oauth_access_tokens')) {
                    $ids = DB::table('oauth_access_tokens')->where('user_id',$user->id)->pluck('id');
                    DB::table('oauth_access_tokens')->whereIn('id',$ids)->update(['revoked'=>1]);
                    if (Schema::hasTable('oauth_refresh_tokens')) DB::table('oauth_refresh_tokens')->whereIn('access_token_id',$ids)->update(['revoked'=>1]);
                }
                if (Schema::hasTable('sessions') && Schema::hasColumn('sessions','user_id')) DB::table('sessions')->where('user_id',$user->id)->delete();
            });
        });
    }
    // [AI] Revoked public-value credentials must never be restored on rollback.
    public function down(): void {}
};
