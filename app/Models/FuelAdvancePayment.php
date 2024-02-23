<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
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

        static::deleting(function ($fuelAdvancePayment) {
            $fuelAdvancePayment->transactions()->delete();
        });
    }

    public function generateTransactions()
    {
        $assetAccount = null;

        // Example: Assign account based on payment method
        switch ($this->payment_method) {
            case 'Cash':
                $assetAccount = ChartOfAccount::where('code', '1100')->first();
                break;
            case 'Bank Transfer':
                $assetAccount = ChartOfAccount::where('code', '1110')->first();
                break;
            case 'Cheque':
                $assetAccount = ChartOfAccount::where('code', '1111')->first();
                break;
            case 'Mobile Banking (Bkash, Nagad, etc.)':
                $assetAccount = ChartOfAccount::where('code', '1112')->first();
                break;
            case 'Card':
                $assetAccount = ChartOfAccount::where('code', '1113')->first();
                break;
        }

        $chartOfAccountReceivable = ChartOfAccount::where('code', '1200')->first(); // Assuming '1200' is the code for Accounts Receivable

        $chartOfAccountFuelExpense = ChartOfAccount::where('name', 'Fuel Expense')->first();
        $chartOfAccountClientAdvance = ChartOfAccount::where('name', 'Client Advance')->first();

        if (!$assetAccount || !$chartOfAccountReceivable || !$chartOfAccountFuelExpense || !$chartOfAccountClientAdvance) {
            // Handle the case where one or both chart of accounts are not found
            $missingAccounts = [];

            if (!$assetAccount) {
                $missingAccounts[] = $this->payment_method;
            }

            if (!$chartOfAccountReceivable) {
                $missingAccounts[] = 'Accounts Receivable';
            }

            $errorMessage = "Chart of accounts not found for: " . implode(', ', $missingAccounts);

            return response()->json(['error' => $errorMessage], 404);
        }

        if($this->payment_type == "Fuel Payment"){
            if($this->payment_from == "Self"){
                $this->createTransaction($assetAccount, $this->amount, 'Credit', "Fuel Payment");
                $this->createTransaction($chartOfAccountFuelExpense, $this->amount, 'Debit', "Fuel Payment");
            } elseif ($this->payment_from == "Client"){
                $this->createTransaction($assetAccount, $this->amount, 'Debit', "Client Advance Payment");
                $this->createTransaction($chartOfAccountClientAdvance, $this->amount, 'Credit', "Client Advance Payment");

                $this->createTransaction($assetAccount, $this->amount, 'Credit', "Fuel Payment");
                $this->createTransaction($chartOfAccountFuelExpense, $this->amount, 'Debit', "Fuel Payment");
            }
        } elseif ($this->payment_type == "Advance Payment"){
            $this->createTransaction($assetAccount, $this->amount, 'Debit', "Client Advance Payment");
            $this->createTransaction($chartOfAccountClientAdvance, $this->amount, 'Credit', "Client Advance Payment");
        }
        return response()->json(['success' => 'Transactions created successfully'], 200);
    }


    private function createTransaction($chartOfAccount, $amount, $type, $description)
    {
        // Determine whether it's a debit or credit
        $debitAmount = ($type === 'Debit') ? $amount : null;
        $creditAmount = ($type === 'Credit') ? $amount : null;

        $transaction = Transaction::create([
            'company_id' => $this->company_id,
            'daily_basis_id' => $this->daily_basis_id,
            'monthly_contract_id' => $this->monthly_contract_id,
            'client_id' => $this->client_id,
            'chart_of_account_id' => $chartOfAccount->id,
            'fuel_advance_payment_id' => $this->id,
            'user_id' => Auth::id(),
            'debit' => $debitAmount,
            'credit' => $creditAmount,
            'transaction_date' => $this->created_at, // Or use a relevant date
            'description' => $description." - ". $type ." (". $chartOfAccount->name.")." ,
        ]);

        return $transaction;
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
