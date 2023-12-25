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
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($fuelAdvancePayment) {
            // Determine the chart_of_account_id based on conditions
            $chartOfAccountId = null;

            // Example: Assign account based on payment method
            switch ($fuelAdvancePayment->payment_method) {
                case 'Cash':
                    $chartOfAccountId = ChartOfAccount::where('code', '1100')->value('id');
                    break;
                case 'Bank Transfer':
                    $chartOfAccountId = ChartOfAccount::where('code', '1110')->value('id');
                    break;
                case 'Cheque':
                    $chartOfAccountId = ChartOfAccount::where('code', '1111')->value('id');
                    break;
                case 'Mobile Banking (Bkash, Nagad, etc.)':
                    $chartOfAccountId = ChartOfAccount::where('code', '1112')->value('id');
                    break;
                case 'Card':
                    $chartOfAccountId = ChartOfAccount::where('code', '1113')->value('id');
                    break;

            }
            $fuelAdvancePayment->chart_of_account_id = $chartOfAccountId;
        });
    }

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
        'for_the_month_of',
        'posting_date',
        'amount',
        'payment_method',
        'payment_from',
        'payment_type',
        'payment_to',
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
            'for_the_month_of' => 'nullable|date',
            'posting_date' => 'required|date',
            'amount' => 'required|numeric',
            'payment_method' => ['required', Rule::in(['Cash', 'Cheque', 'Bank Transfer', 'Mobile Banking (Bkash, Nagad, etc.)', "Card"])],
            'payment_from' => ['required', Rule::in(['Client', 'Vendor', 'Self'])],
            'payment_type' => ['required', Rule::in(['Fuel Payment', 'Advance Payment'])],
            'payment_to' => ['nullable', Rule::in(['Driver', 'Vendor'])],
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
    public static function generatePaymentNumber($basisType, $paymentId, $paymentType, $paymentFrom)
    {

        $paymentIdPrefix = str_pad($paymentId, 3, '0', STR_PAD_LEFT);

        $currentYearLastTwoDigits = date('y');
        $currentMonth = date('m');
        $paymentTypePrefix = $paymentType=="Fuel Payment"?"FP":"AP";
         function paymentFromPrefix ($paymentFrom){
            if($paymentFrom=="Client"){
                return "C";
            } elseif($paymentFrom=="Vendor"){
                return "V";
            }else{
                return "";
            }
        };

        $paymentFromPrefix = paymentFromPrefix($paymentFrom);


        if ($basisType === 'Daily') {
            return "DB-{$paymentFromPrefix}{$paymentTypePrefix}-{$currentMonth}{$currentYearLastTwoDigits}-P{$paymentIdPrefix}";
        } elseif ($basisType === 'Monthly') {

            return "MB-{$paymentFromPrefix}{$paymentTypePrefix}-{$currentMonth}{$currentYearLastTwoDigits}-P{$paymentIdPrefix}";
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

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
