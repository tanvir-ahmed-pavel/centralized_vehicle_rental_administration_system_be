<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Driver;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Driver>
 */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition()
    {
        return [
            'company_id' => 1,
            'vehicle_id' => null,
            'vendor_id' => null,
            'driver_type' => $this->faker->randomElement(['Own', 'Vendor']),
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'mobile_no' => $this->faker->phoneNumber,
            'driving_license_no' => $this->faker->randomNumber,
            'license_expiry_date' => $this->faker->date,
            'nid' => $this->faker->randomNumber,
            'is_available' => $this->faker->boolean,
            'address' => $this->faker->address,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'zip_code' => $this->faker->postcode,
            'country' => $this->faker->country,
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => $this->faker->boolean,
        ];
    }
}
