<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('password_resets',function(Blueprint $t){$t->unsignedBigInteger('account_id')->nullable();$t->string('purpose')->nullable();$t->unsignedInteger('reset_attempts')->default(0);$t->timestamp('reset_blocked_until')->nullable();});
        foreach(['users','sellers'] as $table)Schema::table($table,fn(Blueprint $t)=>$t->unsignedBigInteger('credential_version')->default(0));
        // [AI] Previously exposed or unbound credentials cannot be safely upgraded. Require fresh login/reset issuance.
        DB::table('password_resets')->delete();
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
        foreach(['sellers','vendor_employees','delivery_men'] as $table)if(Schema::hasTable($table))DB::table($table)->update(['auth_token'=>$table==='delivery_men' ? '' : null]);
    }
    public function down(): void {foreach(['users','sellers'] as $table)Schema::table($table,fn(Blueprint $t)=>$t->dropColumn('credential_version'));Schema::table('password_resets',fn(Blueprint $t)=>$t->dropColumn(['account_id','purpose','reset_attempts','reset_blocked_until']));}
};
