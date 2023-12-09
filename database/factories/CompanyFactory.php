<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition()
    {
        return [
            'user_id' => 1,
            'name' => $this->faker->company,
            'email' => $this->faker->email,
            'website' => $this->faker->url,
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
            'is_active' => $this->faker->boolean,
        ];
    }
}

