<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'chart_of_account_id',
        'client_payment_id',
        'driver_payment_id',
        'vendor_payment_id',
        'fuel_advance_payment_id',
        'client_invoice_id',
        'driver_invoice_id',
        'vendor_invoice_id',
        'debit',
        'credit',
        'transaction_date',
        'description',
        'user_id',
    ];


    public static function validationRules()
    {
        return [
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'company_id' => 'nullable',
            'client_payment_id' => 'nullable|exists:client_payments,id',
            'driver_payment_id' => 'nullable|exists:driver_payments,id',
            'vendor_payment_id' => 'nullable|exists:vendor_payments,id',
            'fuel_advance_payment_id' => 'nullable|exists:fuel_advance_payments,id',
            'client_invoice_id' => 'nullable|exists:client_invoices,id',
            'driver_invoice_id' => 'nullable|exists:driver_invoices,id',
            'vendor_invoice_id' => 'nullable|exists:vendor_invoices,id',
            'debit' => 'nullable|numeric',
            'credit' => 'nullable|numeric',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ];
    }

    // Relationships
    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function clientPayment()
    {
        return $this->belongsTo(ClientPayment::class);
    }

    public function driverPayment()
    {
        return $this->belongsTo(DriverPayment::class);
    }

    public function vendorPayment()
    {
        return $this->belongsTo(VendorPayment::class);
    }

    public function fuelAdvancePayment()
    {
        return $this->belongsTo(FuelAdvancePayment::class);
    }

    public function clientInvoice()
    {
        return $this->belongsTo(ClientInvoice::class);
    }

    public function driverInvoice()
    {
        return $this->belongsTo(DriverInvoice::class);
    }

    public function vendorInvoice()
    {
        return $this->belongsTo(VendorInvoice::class);
    }
}
