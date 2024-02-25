<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\MonthlyContract;
use App\Models\Driver;
use App\Models\DutyDate;
use App\Models\Vehicle;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<MonthlyContract>
 */
class MonthlyContractFactory extends Factory
{
    protected $model = MonthlyContract::class;

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
            'status' => "Contract Created",
            'fuel_type' => $this->faker->randomElement(['Octane', 'Diesel', 'Petrol', 'LPG', 'CNG']),
            'per_km_rate' => $this->faker->numberBetween(10,50),
            'body_rent_per_month' => $this->faker->numberBetween(30000,150000),
            'package_rent_per_month' => $this->faker->numberBetween(50000,200000),
            'package_km_limit_per_month' => $this->faker->numberBetween(1000,3000),
            'lunch_per_day' => $this->faker->numberBetween(100,300),
            'dinner_per_day' => $this->faker->numberBetween(100,300),
            'ot_per_hour' => $this->faker->numberBetween(50,200),
            'tour_allowance_per_night' => $this->faker->numberBetween(800,200),
            'vendor_per_km_rate' => $this->faker->numberBetween(10,50),
            'vendor_body_rent_per_month' => $this->faker->numberBetween(30000,150000),
            'vendor_package_rent_per_month' => $this->faker->numberBetween(50000,200000),
            'vendor_package_km_limit_per_month' => $this->faker->numberBetween(1000,3000),
            'vendor_lunch_per_day' => $this->faker->numberBetween(100,300),
            'vendor_dinner_per_day' => $this->faker->numberBetween(100,300),
            'vendor_ot_per_hour' => $this->faker->numberBetween(50,200),
            'vendor_tour_allowance_per_night' => $this->faker->numberBetween(800,2000),
            'duty_description' => $this->faker->text,
            'bill_cycle_date' => $this->faker->date,
            'remarks' => $this->faker->text,
            'is_package' => $this->faker->boolean,
            'is_active' => $this->faker->boolean,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (MonthlyContract $monthlyContract) {
            // Create duty dates associated with the MonthlyContract
            $contractPeriod = DutyDate::factory(1)->create(
                [
                    'monthly_contract_id' => $monthlyContract->id,
                    'start_date' => $this->faker->date,
                    'end_date' => $this->faker->date,
                    'is_half_day' => false,
                    ]
            );
            $monthlyContract->load('client');
            $monthlyContract->monthly_contract_number = $monthlyContract->generateMonthlyContractNumber($monthlyContract->client->name, $monthlyContract->id);
            $monthlyContract->save();

            // Associate the duty dates with the MonthlyContract
//            $monthlyContract->contractPeriod()->save($contractPeriod);
        });
    }
}
