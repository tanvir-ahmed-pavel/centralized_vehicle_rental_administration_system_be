<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DriverInvoice extends Model
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
        'status',
        'invoice_number',
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
            'driver_id' => 'nullable|exists:drivers,id',
            'client_id' => 'nullable|exists:clients,id',
            'status' => ['nullable', Rule::in(['Created & Awaiting Payment', 'Partially Paid', 'Paid', 'Payment Overdue'])],
            'invoice_date' => 'required|date',
            'invoice_number' => 'nullable|string',
            'due_date' => 'nullable|date',
            'sub_total' => 'required|numeric',
            'advance_amount' => 'required|numeric',
            'tax_percent' => 'nullable|numeric',
            'vat_percent' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'vat_amount' => 'nullable|numeric',
            'grand_total' => 'required|numeric',
            'total_paid' => 'required|numeric',
            'round_adjustment' => 'required|numeric',
            'round_total' => 'required|numeric',
            'remarks' => 'nullable|string',
            'is_active' => 'required|boolean',
            'invoice_items' => ['required', 'array', 'min:1'],
        ];
    }

    protected static function boot()
    {
        parent::boot();

        // When a driver invoice is deleted, also delete related transactions
        static::deleting(function ($driverInvoice) {
            $driverInvoice->transactions()->delete();
        });
    }

    /**
     * Generate invoice number based on the specified pattern.
     *
     * @param string $basisType
     * @param string $driverName
     * @param int $invoiceId
     * @return string
     */
    public static function generateInvoiceNumber($basisType, $driverName, $invoiceId, $bookingId)
    {
        $separators = '/[\s\-._]+/';
        $driverNameInitials = implode('', array_map(function ($word) {
            return strtoupper(substr($word, 0, 1));
        }, preg_split($separators, $driverName)));

        $invoiceIdPrefix = str_pad($invoiceId, 3, '0', STR_PAD_LEFT);
        $bookingIdPrefix = str_pad($bookingId, 3, '0', STR_PAD_LEFT);

        $currentYearLastTwoDigits = date('y');

        if ($basisType === 'Daily') {
            return "DB-DI-{$driverNameInitials}-{$currentYearLastTwoDigits}-B{$bookingIdPrefix}-INV{$invoiceIdPrefix}";
        } elseif ($basisType === 'Monthly') {
            $currentMonth = date('m');
            return "MB-DI-{$driverNameInitials}-{$currentMonth}{$currentYearLastTwoDigits}-B{$bookingIdPrefix}-INV{$invoiceIdPrefix}";
        } else {
            // Handle other basis types if needed
            return '';
        }
    }

    public function generateTransactions()
    {
        $chartOfAccountPayable = ChartOfAccount::where('name', 'Accounts Payable')->first();
        $chartOfAccountDariverPayments = ChartOfAccount::where('name', 'Driver Payments')->first(); // Assuming '4100' is the code for Sales

        if (!$chartOfAccountPayable || !$chartOfAccountDariverPayments) {
            // Handle the case where one or both chart of accounts are not found

            $missingAccounts = [];

            if (!$chartOfAccountPayable) {
                $missingAccounts[] = 'Accounts Payable';
            }

            if (!$chartOfAccountDariverPayments) {
                $missingAccounts[] = 'Driver Payments';
            }

            $errorMessage = "Chart of accounts not found for: " . implode(', ', $missingAccounts);

            return response()->json(['error' => $errorMessage], 404);
        }

        $this->createTransaction($chartOfAccountPayable, ($this->grand_total - $this->advance_amount), 'Credit', 'Driver Invoice');
        $this->createTransaction($chartOfAccountDariverPayments, ($this->grand_total - $this->advance_amount), 'Debit', 'Driver Invoice');
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
            'driver_id' => $this->driver_id,
            'chart_of_account_id' => $chartOfAccount->id,
            'driver_invoice_id' => $this->id,
            'user_id' => Auth::id(),
            'debit' => $debitAmount,
            'credit' => $creditAmount,
            'transaction_date' => $this->created_at, // Or use a relevant date
            'description' => $description." - ". $type ." (". $chartOfAccount->name.")." ,
        ]);

        return $transaction;
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

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
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
