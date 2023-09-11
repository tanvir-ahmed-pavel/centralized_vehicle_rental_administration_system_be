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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('website',50)->nullable();
            $table->string('mobile_no',50)->nullable();
            $table->string('tel_no',50)->nullable();
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
            $table->string('contact_person_designation',50)->nullable();
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
        Schema::dropIfExists('companies');
    }
};
