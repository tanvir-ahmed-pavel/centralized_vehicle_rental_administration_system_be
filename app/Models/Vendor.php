<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'vendor_group_id',
        'vendor_type',
        'name',
        'email',
        'mobile_no',
        'tel_no',
        'trade_license_no',
        'tin_no',
        'bin_no',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'contact_person_name',
        'contact_person_mobile_no',
        'contact_person_email',
        'contact_person_nid',
        'contact_person_designation',
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
            'vendor_type' => 'required|in:Company,Individual',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile_no' => 'required|string|max:50',
            'tel_no' => 'nullable|string|max:50',
            'trade_license_no' => 'nullable|string|max:255',
            'tin_no' => 'nullable|string|max:255',
            'bin_no' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:30',
            'state' => 'nullable|string|max:30',
            'zip_code' => 'nullable|string|max:30',
            'country' => 'nullable', 'string', // Assuming 'countries' is the table name for your Country model
            'contact_person_name' => 'nullable|string|max:50',
            'contact_person_mobile_no' => 'nullable|string|max:50',
            'contact_person_email' => 'nullable|email|max:255',
            'contact_person_nid' => 'nullable|string|max:255',
            'contact_person_designation' => 'nullable|string|max:50',
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

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function dailyBases()
    {
        return $this->hasMany(DailyBasis::class);
    }

    public function invoices()
    {
        return $this->hasMany(VendorInvoice::class);
    }
    public function payments()
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function fuelAdvancePayments()
    {
        return $this->hasMany(FuelAdvancePayment::class);
    }
}
