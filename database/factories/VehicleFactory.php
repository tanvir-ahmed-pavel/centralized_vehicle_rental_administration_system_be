<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition()
    {
        return [
            'company_id' => 1,
            'driver_id' => null,
            'vendor_id' => null,
            'vehicle_owner' => $this->faker->randomElement(['Own', 'Vendor']),
            'fuel_type' => $this->faker->randomElement(['Octane', 'Diesel', 'Petrol', 'LPG', 'CNG']),
            'name' => $this->faker->randomElement([
                'Toyota X-Noah',
                'Land Cruiser Prado',
                'Nissan Civilian',
                'Hyundai H1',
                'Ford Transit',
                'Mercedes-Benz Sprinter',
                'Chevrolet Express',
                'Volkswagen Transporter',
                'Renault Master',
                'Peugeot Boxer',
                'Fiat Ducato',
                'Isuzu N-Series',
                'Mitsubishi Fuso Canter',
                'Hino 300 Series',
                'Iveco Daily',
                'Dodge Ram ProMaster',
                'GMC Savana',
                'Jeep Wrangler',
                'Subaru Outback',
                'Honda Odyssey',
                'Kia Sedona',
                'Mazda CX-9',
                'Audi Q7',
                'Lexus GX',
                'Land Rover Discovery',
                'Volvo XC90',
                'BMW X5',
                'Jaguar F-PACE',
            ]),
            'brand' => $this->faker->randomElement([
                'Toyota',
                'Ford',
                'Chevrolet',
                'Nissan',
                'Honda',
                'Hyundai',
                'Mercedes-Benz',
                'Volkswagen',
                'BMW',
                'Audi',
                'Jeep',
                'Subaru',
                'Kia',
                'Mazda',
                'Lexus',
                'Land Rover',
                'Volvo',
                'Mitsubishi',
                'Fiat',
                'Isuzu',
                'GMC',
                'Dodge',
                'Jaguar',
                'Peugeot',
                'Renault',
                'Iveco',
                'Hino',
                'Suzuki',
                'Chrysler',
            ]),
            'model_year' => $this->faker->year,
            'reg_no' => $this->faker->randomNumber,
            'engine_cc' => $this->faker->randomNumber,
            'no_of_seat' => $this->faker->numberBetween(5, 40, ),
            'per_km_rate' => $this->faker->numberBetween(10, 50),
            'body_rent_per_day' => $this->faker->numberBetween(1500, 15000),
            'package_rent_per_day' => $this->faker->numberBetween(1500, 1000000),
            'package_km_limit_per_day' => $this->faker->numberBetween(20, 100),
            'lunch_per_day' => $this->faker->numberBetween(100, 500),
            'dinner_per_day' => $this->faker->numberBetween(10, 500),
            'ot_per_hour' => $this->faker->numberBetween(30, 500),
            'tour_allowance_per_night' => $this->faker->numberBetween(500, 3000),

            'vendor_per_km_rate' => $this->faker->numberBetween(10, 50),
            'vendor_body_rent_per_day' => $this->faker->numberBetween(1500, 100000),
            'vendor_package_rent_per_day' => $this->faker->numberBetween(1500, 1000000),
            'vendor_package_km_limit_per_day' => $this->faker->numberBetween(20, 100),
            'vendor_lunch_per_day' => $this->faker->numberBetween(100, 500),
            'vendor_dinner_per_day' => $this->faker->numberBetween(10, 500),
            'vendor_ot_per_hour' => $this->faker->numberBetween(30, 500),
            'vendor_tour_allowance_per_night' => $this->faker->numberBetween(500, 3000),

            'is_available' => $this->faker->boolean,
            'is_active' => $this->faker->boolean,
        ];
    }
}
