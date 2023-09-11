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
        Schema::create('daily_bases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();

            $table->enum('fuel_type', array('Octane', 'Diesel', 'Petrol', 'LPG', 'CNG'));
            $table->double('per_km_rate',11,2)->nullable();
            $table->double('body_rent_per_day',11,2)->nullable();
            $table->double('package_rent_per_day',11,2)->nullable();
            $table->double('package_km_limit_per_day',11,2)->nullable();
            $table->double('lunch_per_day',11,2)->nullable();
            $table->double('dinner_per_day',11,2)->nullable();
            $table->double('ot_per_hour',11,2)->nullable();
            $table->double('tour_allowance_per_night',11,2)->nullable();

            $table->text('duty_description')->nullable();
            $table->text('remarks')->nullable();

            $table->boolean('is_package')->default(false)->comment('0=false, 1= true');
            $table->boolean('is_active')->default(true)->comment('0=inactive, 1= active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_bases');
    }
};
