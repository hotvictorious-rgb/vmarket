<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * [AI] Creates lgas (Local Government Areas) table for canonical geography V1.
 *
 * Part of: VMarket Geography & Fulfillment Architecture
 * Phase: 2 - Canonical Geography Schema
 *
 * Nigeria has 774 LGAs. FCT uses Area Councils but we expose them as LGAs
 * for API consistency.
 *
 * IMPORTANT: An LGA is NOT the delivery address itself.
 * The actual address is: Country + State + LGA + street address + coordinates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lgas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('states')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('code', 20)->nullable()->comment('LGA code if available');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['state_id', 'name']);
            $table->index(['state_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lgas');
    }
};
