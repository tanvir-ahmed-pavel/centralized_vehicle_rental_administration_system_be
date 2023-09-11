<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DailyBasis extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'vehicle_id',
        'driver_id',
        'vendor_id',
        'client_id',
        'status_id',
        'fuel_type',
        'per_km_rate',
        'body_rent_per_day',
        'package_rent_per_day',
        'package_km_limit_per_day',
        'lunch_per_day',
        'dinner_per_day',
        'ot_per_hour',
        'tour_allowance_per_night',
        'duty_description',
        'remarks',
        'is_package',
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
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'client_id' => 'nullable|exists:clients,id',
            'status_id' => 'nullable|exists:statuses,id',
            'fuel_type' => ['required', Rule::in(['Octane', 'Diesel', 'Petrol', 'LPG', 'CNG'])],
            'per_km_rate' => 'nullable|numeric|min:0',
            'body_rent_per_day' => 'nullable|numeric|min:0',
            'package_rent_per_day' => 'nullable|numeric|min:0',
            'package_km_limit_per_day' => 'nullable|numeric|min:0',
            'lunch_per_day' => 'nullable|numeric|min:0',
            'dinner_per_day' => 'nullable|numeric|min:0',
            'ot_per_hour' => 'nullable|numeric|min:0',
            'tour_allowance_per_night' => 'nullable|numeric|min:0',
            'duty_description' => 'nullable|string',
            'remarks' => 'nullable|string',
            'is_package' => 'boolean',
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

    public function dutyDates()
    {
        return $this->hasMany(DutyDate::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

}
