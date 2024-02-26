<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Driver extends Model
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
        'vendor_id',
        'driver_type',
        'name',
        'email',
        'mobile_no',
        'driving_license_no',
        'licence_expiry_date',
        'nid',
        'is_available',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'opening_balance',
        'current_balance',
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
            'vendor_id' => 'nullable|exists:vendors,id',
            'driver_type' => ['required', Rule::in(['Own', 'Vendor'])],
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile_no' => 'nullable|string|max:50',
            'driving_license_no' => 'nullable|string|max:255',
            'license_expiry_date' => 'nullable|date',
            'nid' => 'nullable|string|max:255',
            'is_available' => 'boolean',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:30',
            'state' => 'nullable|string|max:30',
            'zip_code' => 'nullable|string|max:30',
            'country' => 'nullable|string',
            'opening_balance' => 'nullable|numeric|min:0',
            'current_balance' => 'nullable|numeric|min:0',
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

    public function vehicle()
    {
        return $this->hasOne(Vehicle::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function fuelAdvancePayments()
    {
        return $this->hasMany(FuelAdvancePayment::class);
    }

    public function dailyBases()
    {
        return $this->hasMany(DailyBasis::class);
    }

    public function monthlyContracts()
    {
        return $this->hasMany(MonthlyContract::class);
    }


    public function invoices()
    {
        return $this->hasMany(DriverInvoice::class);
    }
    public function payments()
    {
        return $this->hasMany(DriverPayment::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
