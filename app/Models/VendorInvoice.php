<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;

class VendorInvoice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
//        'name',
//        'email',
//        'password',
    ];

    /**
     * Validation helper.
     *
     */
    public function validate()
    {
        return Validator::make($this->attributes, [
//            'name' => 'required|max:255',
//            'description' => 'required|max:255',
//            'price' => 'required|numeric|min:0',
        ]);
    }

//    Events for cascading on delete
    protected static function boot() {
        parent::boot();

    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function dailyBasis()
    {
        return $this->belongsTo(DailyBasis::class);
    }

    public function monthlyContract()
    {
        return $this->belongsTo(MonthlyContract::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    public function vehicle()
    {
        return $this->hasMany(Vehicle::class);
    }

    public function driver()
    {
        return $this->hasMany(Driver::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(VendorPayment::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
