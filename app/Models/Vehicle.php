<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'driver_id',
        'vendor_id',
        'vehicle_owner',
        'fuel_type',
        'name',
        'brand',
        'model_year',
        'reg_no',
        'engine_cc',
        'no_of_seat',
        'per_km_rate',
        'body_rate_per_day',
        'package_rate_per_day',
        'package_km_limit_per_day',
        'lunch_per_day',
        'dinner_per_day',
        'ot_per_hour',
        'tour_allowance_per_night',
        'is_available',
        'is_active',
    ];

    /**
     * Validation helper.
     *
     */
    public static function validationRules()
    {
        return [
            'company_id' => 'nullable|exists:companies,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'vehicle_owner' => ['required', Rule::in(['Own', 'Vendor'])],
            'fuel_type' => ['nullable', Rule::in(['Octane', 'Diesel', 'Petrol', 'LPG', 'CNG'])],
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:50',
            'model_year' => 'nullable|string|max:50',
            'reg_no' => 'nullable|string|max:50',
            'engine_cc' => 'nullable|string|max:50',
            'no_of_seat' => 'nullable|string|max:50',
            'per_km_rate' => 'nullable|numeric',
            'body_rate_per_day' => 'nullable|numeric',
            'package_rate_per_day' => 'nullable|numeric',
            'package_km_limit_per_day' => 'nullable|numeric',
            'lunch_per_day' => 'nullable|numeric',
            'dinner_per_day' => 'nullable|numeric',
            'ot_per_hour' => 'nullable|numeric',
            'tour_allowance_per_night' => 'nullable|numeric',
            'is_available' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

//    Events for cascading on delete
    protected static function boot() {
        parent::boot();

//        static::deleted(function ($invoice) {
//            $invoice->payments()->delete();
//        });
    }

    // Vehicle.php

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function dailyBases()
    {
        return $this->hasMany(DailyBasis::class);
    }

}
