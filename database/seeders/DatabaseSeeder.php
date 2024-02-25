<?php

namespace Database\Seeders;

use App\Models\MonthlyContract;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Client;
use App\Models\Vendor;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\DailyBasis;
use App\Models\ClientInvoice;
use App\Models\ClientPayment;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        // Create a user with a company
        $user = User::factory()->create([
            "name"=>"admin",
        ]);

        // Create instances for other models
        Client::factory(30)->create();
        Vendor::factory(20)->create();
        Driver::factory(20)->create();
        Vehicle::factory(30)->create();
        DailyBasis::factory(20)->create();
        MonthlyContract::factory(5)->create();

        $this->call([
            ChartOfAccountSeeder::class,
        ]);
    }
}
