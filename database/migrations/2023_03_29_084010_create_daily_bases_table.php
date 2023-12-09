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

            $table->enum('status', array('Booking Created', 'On Duty', 'On Hold', 'To Make Invoice', "Invoice Created & Awaiting Payment", "Partially Paid", "Payment Overdue", "Paid & Closed"))->default("Booking Created");

            $table->string('fuel_type')->nullable();
            $table->double('per_km_rate',11,2)->default(0);
            $table->double('body_rent_per_day',11,2)->default(0);
            $table->double('package_rent_per_day',11,2)->default(0);
            $table->double('package_km_limit_per_day',11,2)->default(0);
            $table->double('lunch_per_day',11,2)->default(0);
            $table->double('dinner_per_day',11,2)->default(0);
            $table->double('ot_per_hour',11,2)->default(0);
            $table->double('tour_allowance_per_night',11,2)->default(0);
            $table->double('vendor_per_km_rate',11,2)->default(0);
            $table->double('vendor_body_rent_per_day',11,2)->default(0);
            $table->double('vendor_package_rent_per_day',11,2)->default(0);
            $table->double('vendor_package_km_limit_per_day',11,2)->default(0);
            $table->double('vendor_lunch_per_day',11,2)->default(0);
            $table->double('vendor_dinner_per_day',11,2)->default(0);
            $table->double('vendor_ot_per_hour',11,2)->default(0);
            $table->double('vendor_tour_allowance_per_night',11,2)->default(0);
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
