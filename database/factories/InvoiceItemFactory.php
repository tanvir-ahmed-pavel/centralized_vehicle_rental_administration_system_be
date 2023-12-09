<?php

namespace Database\Factories;

use App\Models\InvoiceItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<InvoiceItem>
 */
class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition()
    {
        return [
            'client_invoice_id' => null,
            'vendor_invoice_id' => null,
            'driver_invoice_id' => null,
            'description' => $this->faker->sentence,
            'quantity' => $this->faker->randomNumber,
            'unit' => $this->faker->word,
            'unit_rate' => $this->faker->randomFloat(2, 0, 100),
            'tax_percent' => $this->faker->randomFloat(2, 0, 100),
            'vat_percent' => $this->faker->randomFloat(2, 0, 100),
            'tax_amount' => $this->faker->randomFloat(2, 0, 10000),
            'vat_amount' => $this->faker->randomFloat(2, 0, 10000),
            'total_amount' => $this->faker->randomFloat(2, 0, 10000),
            'remarks' => $this->faker->text,
        ];
    }
}
