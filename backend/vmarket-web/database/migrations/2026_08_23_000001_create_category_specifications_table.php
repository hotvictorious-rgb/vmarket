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
        if (!Schema::hasTable('category_specifications')) {
            Schema::create('category_specifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('category_id')->index();
                $table->string('name', 150);
                $table->enum('input_type', ['text', 'number', 'select', 'multi_select'])->default('text');
                $table->json('options')->nullable();
                $table->boolean('is_required')->default(false);
                $table->string('unit', 50)->nullable();
                $table->string('placeholder', 150)->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_specifications');
    }
};
