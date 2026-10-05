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
        foreach(['sellers','vendor_employees','delivery_men'] as $table)if(Schema::hasTable($table))DB::table($table)->update(['auth_token'=>$table==='delivery_men' ? '' : null]);
    }
    public function down(): void {foreach(['users','sellers'] as $table)Schema::table($table,fn(Blueprint $t)=>$t->dropColumn('credential_version'));Schema::table('password_resets',fn(Blueprint $t)=>$t->dropColumn(['account_id','purpose','reset_attempts','reset_blocked_until']));}
};
