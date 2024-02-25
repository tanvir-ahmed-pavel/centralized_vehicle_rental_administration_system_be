<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $company = Auth::user()->company;

        // Set default values for pagination if not provided in the request
        $page = $request->has('page') ? $request->page : 1;
        $perPage = $request->has('per_page') ? $request->per_page : 10;

        // Set default values for sorting if not provided in the request
        $sortBy = $request->has('sort_by') ? $request->sort_by : 'id';
        $sortOrder = $request->has('sort_order') ? $request->sort_order : 'desc';

        // Fetch client invoices based on the authenticated user's company and apply sorting
        $transactions = $company->transactions()
            ->with(['chartOfAccount', 'clientPayment:id,payment_number',
                'driverPayment:id,payment_number', 'vendorPayment:id,payment_number',
                'fuelAdvancePayment:id,payment_number', 'clientInvoice:id,invoice_number',
                'vendorInvoice:id,invoice_number', 'driverInvoice:id,invoice_number',
                'dailyBasis:id,daily_basis_number', 'monthlyContract:id,monthly_contract_number'
            ])
            ->orderBy($sortBy, $sortOrder)
            ->when($request->has('dailyBasisId'), function ($query) use ($company, $request) {
                $dailyBasisId = $request->dailyBasisId;

                // Check if the provided dailyBasisId belongs to the company for ownership verification
                $dailyBasis = $company->dailyBases()->find($dailyBasisId);

                if (!$dailyBasis) {
                    return response()->json(['error' => 'DailyBasis not found or unauthorized'], 404);
                }

                return $query->where('daily_basis_id', $dailyBasisId);
            })
            ->when($request->has('monthlyContractId'), function ($query) use ($company, $request) {
                $monthlyContractId = $request->monthlyContractId;

                // Check if the provided dailyBasisId belongs to the company for ownership verification
                $monthlyContract = $company->dailyBases()->find($monthlyContractId);

                if (!$monthlyContract) {
                    return response()->json(['error' => 'Monthly Contract not found or unauthorized'], 404);
                }

                return $query->where('monthly_contract_id', $monthlyContractId);
            })
            ->when($request->has('start_date') && $request->has('end_date'), function ($query) use ($request) {
                return $query->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
            })
            ->when($request->has('accounts'), function ($query) use ($request) {
                $accountsType = explode(',', $request->accounts);
                return $query->whereHas('chartOfAccount', function ($subQuery) use ($accountsType) {
                    return $subQuery->whereIn('type', $accountsType);
                });
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Transactions retrieved successfully',
            'data' => $transactions,
        ], 200);

    }

    /**
     * Get transaction statistics for the company.
     *
     * @return \Illuminate\Http\JsonResponse An array containing the sum of transactions for each account type.
     */
    public function transactionStatistics()
    {
//        $company = Auth::user()->company;
//
//        $transactions = $company->transactions();
//
//        $total_debit = $transactions->sum("debit");
//        $total_credit = $transactions->sum("credit");
//
//
//        return response()->json([
//            'message' => 'Transaction statistic retrieved successfully',
//            'data' => [
//                "total_debit" => $total_debit,
//                "total_credit" => $total_credit,
//            ],
//        ], 200);

        $company = Auth::user()->company;

        $allTransactions = $company->transactions();

        $transactions = $allTransactions
            ->select(
                'chart_of_accounts.type',
                DB::raw('SUM(
            CASE
                WHEN chart_of_accounts.type IN ("Liability", "Equity", "Income") THEN
                    ((CASE WHEN transactions.credit IS NOT NULL THEN transactions.credit ELSE 0 END) - (CASE WHEN transactions.debit IS NOT NULL THEN transactions.debit ELSE 0 END))
                ELSE
                    ((CASE WHEN transactions.debit IS NOT NULL THEN transactions.debit ELSE 0 END) - (CASE WHEN transactions.credit IS NOT NULL THEN transactions.credit ELSE 0 END))
            END
        ) as total_amount')
            )
            ->leftJoin('chart_of_accounts', 'transactions.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->groupBy('chart_of_accounts.type')
            ->get();

        $statistics = [
            'assets' => 0,
            'liabilities' => 0,
            'equity' => 0,
            'income' => 0,
            'expenses' => 0,
        ];


        foreach ($transactions as $transaction) {
            switch ($transaction->type) {
                case 'Asset':
                    $statistics['assets'] += $transaction->total_amount;
                    break;
                case 'Liability':
                    $statistics['liabilities'] += $transaction->total_amount;
                    break;
                case 'Equity':
                    $statistics['equity'] += $transaction->total_amount;
                    break;
                case 'Income':
                    $statistics['income'] += $transaction->total_amount;
                    break;
                case 'Expense':
                    $statistics['expenses'] += $transaction->total_amount;
                    break;
                default:
                    break;
            }
        }

        return response()->json([
            'message' => 'Transaction statistic retrieved successfully',
            'data' => $statistics,
        ], 200);

    }


    public function accountStatistics()
    {
        $company = Auth::user()->company;

        $transactions = $company->transactions()
            ->select(
                'chart_of_accounts.type',
                DB::raw('SUM(
            CASE
                WHEN chart_of_accounts.type IN ("Liability", "Equity", "Income") THEN
                    ((CASE WHEN transactions.credit IS NOT NULL THEN transactions.credit ELSE 0 END) - (CASE WHEN transactions.debit IS NOT NULL THEN transactions.debit ELSE 0 END))
                ELSE
                    ((CASE WHEN transactions.debit IS NOT NULL THEN transactions.debit ELSE 0 END) - (CASE WHEN transactions.credit IS NOT NULL THEN transactions.credit ELSE 0 END))
            END
        ) as total_amount')
            )
            ->leftJoin('chart_of_accounts', 'transactions.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->groupBy('chart_of_accounts.type')
            ->get();

        $statistics = [
            'Assets' => 0,
            'Liabilities' => 0,
            'Equity' => 0,
            'Income' => 0,
            'Expenses' => 0,
        ];


        foreach ($transactions as $transaction) {
            switch ($transaction->type) {
                case 'Asset':
                    $statistics['Assets'] += $transaction->total_amount;
                    break;
                case 'Liability':
                    $statistics['Liabilities'] += $transaction->total_amount;
                    break;
                case 'Equity':
                    $statistics['Equity'] += $transaction->total_amount;
                    break;
                case 'Income':
                    $statistics['Income'] += $transaction->total_amount;
                    break;
                case 'Expense':
                    $statistics['Expenses'] += $transaction->total_amount;
                    break;
                default:
                    break;
            }
        }

        return response()->json([
            'message' => 'Transaction statistic retrieved successfully',
            'data' => $statistics,
        ], 200);

    }




    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        //
    }
}
