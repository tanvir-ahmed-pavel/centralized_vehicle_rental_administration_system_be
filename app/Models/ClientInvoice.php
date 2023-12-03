<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;

class ClientInvoice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'daily_basis_id',
        'monthly_contract_id',
        'vehicle_id',
        'client_id',
        'driver_id',
        'status_id',
        'invoice_date',
        'due_date',
        'sub_total',
        'advance_amount',
        'discount_amount',
        'tax_percent',
        'vat_percent',
        'tax_amount',
        'vat_amount',
        'grand_total',
        'total_paid',
        'round_adjustment',
        'round_total',
        'remarks',
        'is_active',
    ];

    /**
     * Validation helper.
     *
     */
    protected $dates = ['deleted_at'];

    public static function validationRules()
    {
        return [
            'company_id' => 'nullable|exists:companies,id',
            'daily_basis_id' => 'nullable|exists:daily_bases,id',
            'monthly_contract_id' => 'nullable|exists:monthly_contracts,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'client_id' => 'nullable|exists:clients,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'status_id' => 'nullable|exists:statuses,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'sub_total' => 'required|numeric',
            'advance_amount' => 'required|numeric',
            'tax_percent' => 'required|numeric',
            'vat_percent' => 'required|numeric',
            'tax_amount' => 'required|numeric',
            'vat_amount' => 'required|numeric',
            'grand_total' => 'required|numeric',
            'total_paid' => 'required|numeric',
            'round_adjustment' => 'required|numeric',
            'round_total' => 'required|numeric',
            'remarks' => 'nullable|string',
            'is_active' => 'required|boolean',
            'invoice_items' => ['required', 'array', 'min:1'],
        ];
    }

//    Events for cascading on delete
    protected static function boot() {
        parent::boot();

//        static::deleted(function ($invoice) {
//            $invoice->payments()->delete();
//        });
    }

    /**
     * Generate payment number based on the specified pattern.
     *
     * @param string $basisType
     * @param string $clientName
     * @param int $invoiceId
     * @return string
     */
    public static function generatePaymentNumber($basisType, $clientName, $invoiceId)
    {
        $clientNameInitials = implode('', array_map(function ($word) {
            return strtoupper(substr($word, 0, 1));
        }, explode(' ', $clientName)));

        $invoiceIdPrefix = str_pad($invoiceId, 3, '0', STR_PAD_LEFT);

        $currentYearLastTwoDigits = date('y');

        if ($basisType === 'Daily') {
            return "DBI-{$clientNameInitials}-{$currentYearLastTwoDigits}-{$invoiceIdPrefix}";
        } elseif ($basisType === 'Monthly') {
            $currentMonth = date('m');
            return "MBI-{$clientNameInitials}-{$currentMonth}{$currentYearLastTwoDigits}-{$invoiceIdPrefix}";
        } else {
            // Handle other basis types if needed
            return '';
        }
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

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(ClientPayment::class);
    }


}
