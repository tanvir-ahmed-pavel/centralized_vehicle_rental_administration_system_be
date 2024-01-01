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
        Schema::create('driver_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('daily_basis_id')->nullable();
            $table->unsignedBigInteger('monthly_contract_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('driver_invoice_id')->nullable();
            $table->date('date');
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['Cash', 'Cheque', 'Bank Transfer', 'Mobile Banking (Bkash, Nagad, etc.)', 'Card']);
            $table->string('payment_ref')->nullable();
            $table->string('payment_number')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_payments');
    }
};
