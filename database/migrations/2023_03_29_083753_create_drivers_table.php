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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            //            will have the option to add vehicle once the driver is created
            $table->unsignedBigInteger('vehicle_id')->nullable();
//            if driver type is outside
            $table->unsignedBigInteger('vendor_id')->nullable();

            $table->enum('driver_type', array('Own','Vendor'));
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('mobile_no',50)->nullable();
            $table->string('driving_licence_no')->nullable();
            $table->date('licence_expiry_date')->nullable();
            $table->string('nid')->nullable();
            $table->integer('is_available')->default(1)->comment('0=unavailable, 1= available');
            $table->text('address');
            $table->string('city',30)->nullable();
            $table->string('state',30)->nullable();
            $table->string('zip_code',30)->nullable();
            $table->integer('country')->comment('this is the id of country')->nullable();

//            if own driver then
            $table->double('opening_balance',11,2)->default(0);
            $table->double('current_balance',11,2)->default(0);

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
        Schema::dropIfExists('drivers');
    }
};
