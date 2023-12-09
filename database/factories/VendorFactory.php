<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Vendor;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition()
    {
        return [
            'company_id' => 1,
            'vendor_group_id' => null,
            'vendor_type' => $this->faker->randomElement(['Company', 'Individual']),
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
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => $this->faker->boolean,
        ];
    }
}
