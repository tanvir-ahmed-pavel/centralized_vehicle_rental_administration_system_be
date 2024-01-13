<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'website',
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
        'is_active',
    ];

    /**
     * Validation helper.
     *
     */
    public static function validationRules()
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:50',
            'mobile_no' => 'nullable|string|max:50',
            'tel_no' => 'nullable|string|max:50',
            'trade_license_no' => 'nullable|string|max:255',
            'tin_no' => 'nullable|string|max:255',
            'bin_no' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:30',
            'state' => 'nullable|string|max:30',
            'zip_code' => 'nullable|string|max:30',
            'country' => 'nullable|string',
            'contact_person_name' => 'nullable|string|max:50',
            'contact_person_mobile_no' => 'nullable|string|max:50',
            'contact_person_email' => 'nullable|email|max:255',
            'contact_person_nid' => 'nullable|string|max:255',
            'contact_person_designation' => 'nullable|string|max:50',
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

    public function user()
    {
        return $this->hasMany(User::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function vendors()
    {
        return $this->hasMany(Vendor::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class);
    }

    public function dailyBases()
    {
        return $this->hasMany(DailyBasis::class);
    }

    public function monthlyContracts()
    {
        return $this->hasMany(MonthlyContract::class);
    }

    public function clientInvoices()
    {
        return $this->hasMany(ClientInvoice::class);
    }
    public function clientInvoicePayments()
    {
        return $this->hasMany(ClientPayment::class);
    }

    public function driverInvoices()
    {
        return $this->hasMany(DriverInvoice::class);
    }
    public function driverInvoicePayments()
    {
        return $this->hasMany(DriverPayment::class);
    }

    public function vendorInvoices()
    {
        return $this->hasMany(VendorInvoice::class);
    }
    public function vendorInvoicePayments()
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function fuelAdvancePayments()
    {
        return $this->hasMany(FuelAdvancePayment::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
