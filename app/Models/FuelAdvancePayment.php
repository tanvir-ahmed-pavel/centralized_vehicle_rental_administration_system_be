<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

class FuelAdvancePayment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'company_id',
        'daily_basis_id',
        'monthly_contract_id',
        'client_id',
        'vendor_id',
        'driver_id',
        'vehicle_id',
        'chart_of_acc_id',
        'for_the_month_of',
        'posting_date',
        'amount',
        'payment_method',
        'advance_from',
        'advance_to',
        'payment_ref',
        'payment_number',
        'remarks',
    ];

    /**
     * Validation rules.
     *
     * @return array
     */
    public static function validationRules()
    {
        return [
            'company_id' => 'nullable|exists:companies,id',
            'daily_basis_id' => 'nullable|exists:daily_bases,id',
            'monthly_contract_id' => 'nullable|exists:monthly_contracts,id',
            'client_id' => 'nullable|exists:clients,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'chart_of_acc_id' => 'nullable|exists:chart_of_accounts,id',
            'for_the_month_of' => 'nullable|date',
            'posting_date' => 'required|date',
            'amount' => 'required|numeric',
            'payment_method' => ['required', Rule::in(['Cash', 'Cheque', 'Bank Transfer', 'Mobile Banking (Bkash, Nadag, etc.)', "Card"])],
            'advance_from' => 'required|in:Client,Vendor,Own',
            'advance_to' => 'required|in:Driver,Vendor',
            'payment_ref' => 'nullable|string',
            'payment_number' => 'nullable|string',
            'remarks' => 'nullable|string',
        ];
    }

    /**
     * Generate payment number based on the specified pattern.
     *
     * @param string $basisType
     * @param string $clientName
     * @param int $invoiceId
     * @param int $paymentId
     * @return string
     */
    public static function generatePaymentNumber($basisType, $paymentId)
    {

        $paymentIdPrefix = str_pad($paymentId, 3, '0', STR_PAD_LEFT);

        $currentYearLastTwoDigits = date('y');
        $currentMonth = date('m');

        if ($basisType === 'Daily') {
            return "DBFP-{$currentMonth}{$currentYearLastTwoDigits}-P{$paymentIdPrefix}";
        } elseif ($basisType === 'Monthly') {

            return "MBFP-{$currentMonth}{$currentYearLastTwoDigits}-P{$paymentIdPrefix}";
        } else {
            // Handle other basis types if needed
            return '';
        }
    }

    // Relationships

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

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function chartOfAccount()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }
}
