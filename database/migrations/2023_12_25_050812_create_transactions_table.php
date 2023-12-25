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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('chart_of_account_id');
            $table->unsignedBigInteger('daily_basis_id')->nullable();
            $table->unsignedBigInteger('monthly_contract_id')->nullable();
            $table->unsignedBigInteger('client_payment_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('driver_payment_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('vendor_payment_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('fuel_advance_payment_id')->nullable();
            $table->unsignedBigInteger('client_invoice_id')->nullable();
            $table->unsignedBigInteger('driver_invoice_id')->nullable();
            $table->unsignedBigInteger('vendor_invoice_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('debit', 10, 2)->nullable();
            $table->decimal('credit', 10, 2)->nullable();
            $table->date('transaction_date');
            $table->string('description');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
