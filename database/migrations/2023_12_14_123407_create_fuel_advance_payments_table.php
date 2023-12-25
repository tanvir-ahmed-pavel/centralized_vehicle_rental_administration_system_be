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
        Schema::create('fuel_advance_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('daily_basis_id')->nullable();
            $table->unsignedBigInteger('monthly_contract_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->date('for_the_month_of')->nullable();
            $table->date('posting_date');
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['Cash', 'Cheque', 'Bank Transfer', 'Mobile Banking (Bkash, Nagad, etc.)', 'Card']);
            $table->enum('payment_from', ['Client', 'Vendor', 'Self'])->default("Self");
            $table->enum('payment_type', ["Fuel Payment", "Advance Payment"])->default("Fuel Payment");
            $table->enum('payment_to', ['Driver', 'Vendor'])->default("Driver");
            $table->string('payment_ref')->nullable();
            $table->string('payment_number')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_advance_payments');
    }
};
