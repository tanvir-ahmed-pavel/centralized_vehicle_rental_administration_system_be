<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientPayment extends Model
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
        'client_invoice_id',
        'chart_of_acc_id',
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
            'chart_of_acc_id' => 'nullable|exists:chart_of_accounts,id',
            'date' => 'required|date',
            'amount' => 'required|numeric',
            'payment_method' => 'required|in:Cash,Cheque,Bank Transfer,Mobile Banking (Bkash, Nadag, etc.),Card',
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
}
