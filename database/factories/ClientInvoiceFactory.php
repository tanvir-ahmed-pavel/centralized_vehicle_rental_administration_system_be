<?php

namespace Database\Factories;

use App\Models\ClientInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<ClientInvoice>
 */
class ClientInvoiceFactory extends Factory
{
    protected $model = ClientInvoice::class;

    public function definition()
    {
        return [
            'company_id' => 1,
            'daily_basis_id' => null,
            'monthly_contract_id' => null,
            'vehicle_id' => null,
            'client_id' => null,
            'driver_id' => null,
            'status' => null,
            'invoice_number' => $this->faker->randomNumber,
            'invoice_date' => $this->faker->date,
            'due_date' => $this->faker->date,
            'sub_total' => $this->faker->randomFloat(2, 0, 10000),
            'advance_amount' => $this->faker->randomFloat(2, 0, 10000),
            'discount_amount' => $this->faker->randomFloat(2, 0, 10000),
            'tax_percent' => $this->faker->randomFloat(2, 0, 100),
            'vat_percent' => $this->faker->randomFloat(2, 0, 100),
            'tax_amount' => $this->faker->randomFloat(2, 0, 10000),
            'vat_amount' => $this->faker->randomFloat(2, 0, 10000),
            'grand_total' => $this->faker->randomFloat(2, 0, 10000),
            'total_paid' => $this->faker->randomFloat(2, 0, 10000),
            'round_adjustment' => $this->faker->randomFloat(2, 0, 10000),
            'round_total' => $this->faker->randomFloat(2, 0, 10000),
            'remarks' => $this->faker->text,
            'is_active' => $this->faker->boolean,
        ];
    }
}
