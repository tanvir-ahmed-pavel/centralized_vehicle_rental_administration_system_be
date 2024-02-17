<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard data.
     *
     * @return \Illuminate\Http\JsonResponse An array containing the sum of transactions for each account type.
     */
    public function index()
    {

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

        $clientInvoices = $company->clientInvoices()->get();
        $vendorInvoices = $company->vendorInvoices()->get();
        $driverInvoices = $company->driverInvoices()->get();

        $lifetime_received_from_client = $clientInvoices->sum("total_paid");
        $current_receivable_from_client = $clientInvoices->sum("grand_total") - $lifetime_received_from_client;

        $lifetime_paid_to_driver = $driverInvoices->sum("total_paid");
        $lifetime_paid_to_vendor = $vendorInvoices->sum("total_paid");

        $current_payable_to_vendor = ($vendorInvoices->sum("grand_total")) - $lifetime_paid_to_vendor;
        $current_payable_to_driver = ($driverInvoices->sum("grand_total")) - $lifetime_paid_to_driver;

        // Fetching transactions grouped by month
        $monthlyData = $this->getMonthlyData($company);

        // Organizing data in the required format
        $incomeData = $this->formatIncomeData($monthlyData);

        return response()->json([
            'message' => 'Statistic retrieved successfully',
            'data' => [
                'account_statistics' => $statistics,
                'current_receivable_from_client' => $current_receivable_from_client,
                'current_payable_to_driver' => $current_payable_to_driver,
                'current_payable_to_vendor' => $current_payable_to_vendor,
                'yearly_statistics' => $incomeData,
                'topFive' => $this->getTopFiveResource($company),

            ],
        ], 200);

    }

    private function getMonthlyData($company)
    {
        // Create an array with the months of the last 12 months
        $months = collect();
        for ($i = 0; $i < 12; $i++) {
            $date = Carbon::now()->subMonths($i);
            $months->push([
                'year' => $date->year,
                'month' => $date->monthName,
            ]);
        }

        // Fetch data grouped by month from the database
        $monthlyData = $company->transactions()
            ->select(
                DB::raw('YEAR(transaction_date) as year'),
                DB::raw('MONTHNAME(transaction_date) as month'),
                DB::raw('SUM(
                        CASE
                            WHEN chart_of_accounts.type = "Income" THEN IFNULL(transactions.credit, 0)
                            ELSE 0
                        END
                        ) -
                        SUM(
                            CASE
                                WHEN chart_of_accounts.type = "Income" THEN IFNULL(transactions.debit, 0)
                                ELSE 0
                            END
                        ) as income'),
                DB::raw('SUM(
                        CASE
                            WHEN chart_of_accounts.type = "Liability" THEN IFNULL(transactions.credit, 0)
                            ELSE 0
                        END
                        ) -
                        SUM(
                            CASE
                                WHEN chart_of_accounts.type = "Liability" THEN IFNULL(transactions.debit, 0)
                                ELSE 0
                            END
                        ) as liability'),
                DB::raw('(SUM(
                        CASE
                            WHEN chart_of_accounts.type = "Expense" THEN IFNULL(transactions.debit, 0)
                            ELSE 0
                        END
                        ) -
                        SUM(
                            CASE
                                WHEN chart_of_accounts.type = "Expense" THEN IFNULL(transactions.credit, 0)
                                ELSE 0
                            END
                        )) as expense')
            )
            ->leftJoin('chart_of_accounts', 'transactions.chart_of_account_id', '=', 'chart_of_accounts.id')
            ->whereYear('transaction_date', '>=', now()->subMonths(11)->format('Y'))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Merge fetched data with months array to ensure data for all 12 months
        $mergedData = [];
        foreach ($months as $month) {
            $found = false;
            foreach ($monthlyData as $data) {
                if ($data->year == $month['year'] && $data->month == $month['month']) {
                    $mergedData[] = $data;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $mergedData[] = (object)[
                    'year' => $month['year'],
                    'month' => $month['month'],
                    'type' => '',
                    'income' => 0,
                    'expense' => 0,
                    'liability' => 0,
                ];
            }
        }

        return collect($mergedData);
    }

    // Function to format data in the required format
    private function formatIncomeData($monthlyData)
    {
        $incomeData = [];
        foreach ($monthlyData as $data) {
            $monthYear = $data->month . ' ' . $data->year;
            if (!isset($incomeData[$monthYear])) {
                $incomeData[$monthYear] = [
                    'month' => $data->month,
                    'income' => 0,
                    'expense' => 0,
                    'liability' => 0,
                ];
            }
            $incomeData[$monthYear]['income'] += $data->income;
            $incomeData[$monthYear]['expense'] += $data->expense;
            $incomeData[$monthYear]['liability'] += $data->liability;
        }
        return array_values($incomeData);
    }

    private function getTopFiveResource($company){
        $topVendors = $company->vendors()
            ->select('id', 'name', 'current_balance')
            ->orderByDesc('current_balance')
            ->limit(5)
            ->get();

        // Top 5 clients
        $topClients = $company->clients()
            ->select('id', 'name', 'current_balance')
            ->orderByDesc('current_balance')
            ->limit(5)
            ->get();

        // Top 5 drivers
        $topDrivers = $company->drivers()
            ->select('id', 'name', 'current_balance')
            ->orderByDesc('current_balance')
            ->limit(5)
            ->get();

        return collect((object)[
            'top_vendors' => $topVendors,
            'top_clients' => $topClients,
            'top_drivers' => $topDrivers,
        ]);
    }
}
