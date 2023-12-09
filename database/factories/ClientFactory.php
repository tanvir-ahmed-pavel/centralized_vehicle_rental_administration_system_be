<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition()
    {
        return [
            'company_id' => 1,
            'client_group_id' => null,
            'client_type' => $this->faker->randomElement(['Company', 'Individual']),
            'name' => $this->faker->company,
            'email' => $this->faker->email,
            'mobile_no' => $this->faker->phoneNumber,
            'tel_no' => $this->faker->phoneNumber,
            'trade_license_no' => $this->faker->randomNumber,
            'tin_no' => $this->faker->randomNumber,
            'bin_no' => $this->faker->randomNumber,
            'address' => $this->faker->address,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'zip_code' => $this->faker->postcode,
            'country' => $this->faker->country,
            'contact_person_name' => $this->faker->name,
            'contact_person_mobile_no' => $this->faker->phoneNumber,
            'contact_person_email' => $this->faker->email,
            'contact_person_nid' => $this->faker->randomNumber,
            'contact_person_designation' => $this->faker->jobTitle,
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => $this->faker->boolean,
        ];
    }
}
