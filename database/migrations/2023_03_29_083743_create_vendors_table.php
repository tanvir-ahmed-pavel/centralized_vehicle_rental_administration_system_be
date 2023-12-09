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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('vendor_group_id')->nullable();

            $table->enum('vendor_type', array('Company','Individual'));
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('website',255)->nullable();
            $table->string('mobile_no',50)->nullable();
            $table->string('tel_no',50)->nullable();
            //            if vendor type company
            $table->string('trade_license_no')->nullable();
            $table->string('tin_no')->nullable();
            $table->string('bin_no')->nullable();
            $table->text('address')->nullable();
            $table->string('city',30)->nullable();
            $table->string('state',30)->nullable();
            $table->string('zip_code',30)->nullable();
            $table->string('country')->comment('this is the id of country')->nullable();
            $table->string('contact_person_name',50)->nullable();
            $table->string('contact_person_mobile_no',50)->nullable();
            $table->string('contact_person_email')->nullable();
            $table->string('contact_person_nid')->nullable();
            $table->string('contact_person_designation',100)->nullable();
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
        Schema::dropIfExists('vendors');
    }
};
