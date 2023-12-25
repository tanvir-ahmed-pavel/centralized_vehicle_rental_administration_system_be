<?php

namespace Database\Factories;

use App\Models\ClientPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<ClientPayment>
 */
class ClientPaymentFactory extends Factory
{
    protected $model = ClientPayment::class;

    public function definition()
    {
        return [
            'company_id' => null,
            'daily_basis_id' => null,
            'monthly_contract_id' => null,
            'client_id' => null,
            'client_invoice_id' => null,
            'date' => $this->faker->date,
            'amount' => $this->faker->randomFloat(2, 0, 10000),
            'payment_method' => $this->faker->randomElement(['Cash', 'Cheque', 'Bank Transfer', 'Mobile Banking (Bkash, Nagad, etc.)', 'Card']),
            'payment_ref' => $this->faker->word,
            'payment_number' => $this->faker->randomNumber,
            'remarks' => $this->faker->text,
            'is_active' => $this->faker->boolean,
        ];
    }
}
