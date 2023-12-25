<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientPayment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($clientPayment) {
            // Determine the chart_of_account_id based on conditions
            $chartOfAccountId = null;

            // Example: Assign account based on payment method
            switch ($clientPayment->payment_method) {
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
            $clientPayment->chart_of_account_id = $chartOfAccountId;
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
        'client_invoice_id',
        'date',
        'amount',
        'payment_method',
        'payment_ref',
        'payment_number',
        'remarks',
        'is_active',
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
            'client_invoice_id' => 'nullable|exists:client_invoices,id',
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'payment_method' => 'required|in:Cash,Cheque,Bank Transfer,Mobile Banking (Bkash, Nagad, etc.),Card',
            'payment_ref' => 'nullable|string',
            'payment_number' => 'nullable|string',
            'remarks' => 'nullable|string',
            'is_active' => 'nullable|boolean|default:1',
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
    public static function generatePaymentNumber($basisType, $clientName, $invoiceId, $paymentId)
    {
        $clientNameInitials = implode('', array_map(function ($word) {
            return strtoupper(substr($word, 0, 1));
        }, explode(' ', $clientName)));

        $invoiceIdPrefix = str_pad($invoiceId, 3, '0', STR_PAD_LEFT);
        $paymentIdPrefix = str_pad($paymentId, 3, '0', STR_PAD_LEFT);

        $currentYearLastTwoDigits = date('y');

        if ($basisType === 'Daily') {
            return "DBIP-C-{$clientNameInitials}-{$currentYearLastTwoDigits}-{$invoiceIdPrefix}P{$paymentIdPrefix}";
        } elseif ($basisType === 'Monthly') {
            $currentMonth = date('m');
            return "MBIP-C-{$clientNameInitials}-{$currentMonth}{$currentYearLastTwoDigits}-I{$invoiceIdPrefix}-{$paymentIdPrefix}";
        } else {
            // Handle other basis types if needed
            return '';
        }
    }

    // Relationships

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

    public function clientInvoice()
    {
        return $this->belongsTo(ClientInvoice::class);
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
