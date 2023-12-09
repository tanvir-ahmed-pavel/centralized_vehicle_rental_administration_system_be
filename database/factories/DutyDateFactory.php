<?php

namespace Database\Factories;

use App\Models\DutyDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<DutyDate>
 */
class DutyDateFactory extends Factory
{
    protected $model = DutyDate::class;

    public function definition()
    {
        return [
            'daily_basis_id' => null,
            'start_date' => $this->faker->date,
            'end_date' => null,
            'is_half_day' => false,
        ];
    }
}
