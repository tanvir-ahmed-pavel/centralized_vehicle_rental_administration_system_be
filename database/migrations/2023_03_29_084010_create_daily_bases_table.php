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

            $table->string('fuel_type')->nullable();
            $table->integer('per_km_rate')->default(0);
            $table->integer('body_rent_per_day')->default(0);
            $table->integer('package_rent_per_day')->default(0);
            $table->integer('package_km_limit_per_day')->default(0);
            $table->integer('lunch_per_day')->default(0);
            $table->integer('dinner_per_day')->default(0);
            $table->integer('ot_per_hour')->default(0);
            $table->integer('tour_allowance_per_night')->default(0);
            $table->integer('vendor_per_km_rate')->default(0);
            $table->integer('vendor_body_rent_per_day')->default(0);
            $table->integer('vendor_package_rent_per_day')->default(0);
            $table->integer('vendor_package_km_limit_per_day')->default(0);
            $table->integer('vendor_lunch_per_day')->default(0);
            $table->integer('vendor_dinner_per_day')->default(0);
            $table->integer('vendor_ot_per_hour')->default(0);
            $table->integer('vendor_tour_allowance_per_night')->default(0);
            $table->text('duty_description')->nullable();
            $table->string('remarks')->nullable();
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
