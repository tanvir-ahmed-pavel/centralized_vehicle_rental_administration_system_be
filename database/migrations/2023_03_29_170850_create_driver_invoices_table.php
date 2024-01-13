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
        Schema::create('driver_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('daily_basis_id')->nullable();
            $table->unsignedBigInteger('monthly_contract_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();

            $table->enum('status', array('Created & Awaiting Payment', 'Partially Paid', 'Paid', 'Payment Overdue'))->default('Created & Awaiting Payment');

            $table->string('invoice_number')->nullable();
            $table->date('for_the_month')->nullable();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->date('for_the_month')->nullable();
            $table->decimal('sub_total', 10, 2)->default(0);
            $table->decimal('advance_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('vat_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);

            $table->decimal('total_paid', 10, 2)->default(0);
            $table->decimal('round_adjustment', 10, 2)->default(0);
            $table->decimal('round_total', 10, 2)->default(0);

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
        Schema::dropIfExists('driver_invoices');
    }
};
