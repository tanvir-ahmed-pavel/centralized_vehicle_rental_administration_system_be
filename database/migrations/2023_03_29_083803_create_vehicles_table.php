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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
//            will have the option to add driver once the vehicle is created
            $table->unsignedBigInteger('driver_id')->nullable();
            //            if vehicle owner is vendor
            $table->unsignedBigInteger('vendor_id')->nullable();

            $table->enum('vehicle_owner', array('Own','Vendor'));
            $table->enum('fuel_type', array('Octane', 'Diesel', 'Petrol', 'LPG', 'CNG'));
            $table->string('name');
            $table->string('brand', 50)->nullable();
            $table->string('model_year', 50)->nullable();
            $table->string('reg_no', 50)->nullable();
            $table->string('engine_cc', 50)->nullable();
            $table->string('no_of_seat', 50)->nullable();
            $table->double('per_km_rate',11,2)->nullable();
            $table->double('body_rate_per_day',11,2)->nullable();
            $table->double('package_rate_per_day',11,2)->nullable();
            $table->double('package_km_limit_per_day',11,2)->nullable();
            $table->double('lunch_per_day',11,2)->nullable();
            $table->double('dinner_per_day',11,2)->nullable();
            $table->double('ot_per_hour',11,2)->nullable();
            $table->double('tour_allowance_per_night',11,2)->nullable();
            $table->integer('is_available')->default(1)->comment('0=unavailable, 1= available');
            $table->integer('is_active')->default(1)->comment('0=inactive, 1= active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
