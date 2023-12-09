<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\DailyBasis;
use App\Models\Driver;
use App\Models\DutyDate;
use App\Models\Vehicle;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<DailyBasis>
 */
class DailyBasisFactory extends Factory
{
    protected $model = DailyBasis::class;

    public function definition()
    {
        // Get random records from the vehicles, drivers, vendors, and clients tables
        $randomVehicle = Vehicle::inRandomOrder()->first();
        $randomDriver = Driver::inRandomOrder()->first();
        $randomVendor = Vendor::inRandomOrder()->first();
        $randomClient = Client::inRandomOrder()->first();

        // Determine if the vendor_id should be null
        $vendorId = mt_rand(0, 1) ? $randomVendor->id : null;

        return [
            'company_id' => 1,
            'vehicle_id' => $randomVehicle,
            'driver_id' => $randomDriver,
            'vendor_id' => $vendorId,
            'client_id' => $randomClient,
            'status' => "Booking Created",
            'fuel_type' => $this->faker->randomElement(['Octane', 'Diesel', 'Petrol', 'LPG', 'CNG']),
            'per_km_rate' => $this->faker->randomFloat(2, 0, 100),
            'body_rent_per_day' => $this->faker->randomFloat(2, 0, 100),
            'package_rent_per_day' => $this->faker->randomFloat(2, 0, 100),
            'package_km_limit_per_day' => $this->faker->randomFloat(2, 0, 100),
            'lunch_per_day' => $this->faker->randomFloat(2, 0, 100),
            'dinner_per_day' => $this->faker->randomFloat(2, 0, 100),
            'ot_per_hour' => $this->faker->randomFloat(2, 0, 100),
            'tour_allowance_per_night' => $this->faker->randomFloat(2, 0, 100),
            'vendor_per_km_rate' => $this->faker->randomFloat(2, 0, 100),
            'vendor_body_rent_per_day' => $this->faker->randomFloat(2, 0, 100),
            'vendor_package_rent_per_day' => $this->faker->randomFloat(2, 0, 100),
            'vendor_package_km_limit_per_day' => $this->faker->randomFloat(2, 0, 100),
            'vendor_lunch_per_day' => $this->faker->randomFloat(2, 0, 100),
            'vendor_dinner_per_day' => $this->faker->randomFloat(2, 0, 100),
            'vendor_ot_per_hour' => $this->faker->randomFloat(2, 0, 100),
            'vendor_tour_allowance_per_night' => $this->faker->randomFloat(2, 0, 100),
            'duty_description' => $this->faker->text,
            'remarks' => $this->faker->text,
            'is_package' => $this->faker->boolean,
            'is_active' => $this->faker->boolean,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (DailyBasis $dailyBasis) {
            // Create duty dates associated with the DailyBasis
            $dutyDates = DutyDate::factory(mt_rand(1, 10))->create(['daily_basis_id' => $dailyBasis->id]);

            // Associate the duty dates with the DailyBasis
            $dailyBasis->dutyDates()->saveMany($dutyDates);
        });
    }
}
