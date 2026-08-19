<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('vendor_roles')) {
            Schema::create('vendor_roles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_id')->index();
                $table->string('name');
                $table->text('module_access')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();

                $table->foreign('seller_id')->references('id')->on('sellers')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('vendor_employees')) {
            Schema::create('vendor_employees', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('seller_id')->index();
                $table->unsignedBigInteger('vendor_role_id')->index();
                $table->string('name');
                $table->string('phone', 30)->nullable();
                $table->string('email')->unique();
                $table->string('password');
                $table->string('image')->nullable();
                $table->boolean('status')->default(true);
                $table->rememberToken();
                $table->timestamps();

                $table->foreign('seller_id')->references('id')->on('sellers')->onDelete('cascade');
                $table->foreign('vendor_role_id')->references('id')->on('vendor_roles')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_employees');
        Schema::dropIfExists('vendor_roles');
    }
};
