<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add your chart of accounts data
        // Parent groups
        $assetGroup = ChartOfAccount::create(['code' => '1', 'name' => 'Assets', 'short_name' => 'Assets', 'type' => 'Asset']);
        $liabilityGroup = ChartOfAccount::create(['code' => '2', 'name' => 'Liabilities', 'short_name' => 'Liabilities', 'type' => 'Liability']);
        $equityGroup = ChartOfAccount::create(['code' => '3', 'name' => 'Equity', 'short_name' => 'Equity', 'type' => 'Equity']);
        $incomeGroup = ChartOfAccount::create(['code' => '4', 'name' => 'Income', 'short_name' => 'Income', 'type' => 'Income']);
        $expenseGroup = ChartOfAccount::create(['code' => '5', 'name' => 'Expenses', 'short_name' => 'Expenses', 'type' => 'Expense']);

        // Accounts under Assets
        ChartOfAccount::create(['code' => '1100', 'name' => 'Cash', 'short_name' => 'Cash', 'type' => 'Asset', 'parent_id' => $assetGroup->id]);
        ChartOfAccount::create(['code' => '1110', 'name' => 'Bank Transfer', 'short_name' => 'Bank Transfer', 'type' => 'Asset', 'parent_id' => $assetGroup->id]);
        ChartOfAccount::create(['code' => '1111', 'name' => 'Cheque', 'short_name' => 'Cheque', 'type' => 'Asset', 'parent_id' => $assetGroup->id]);
        ChartOfAccount::create(['code' => '1112', 'name' => 'Mobile Banking (Bkash, Nagad, etc.)', 'short_name' => 'Mobile Bank', 'type' => 'Asset', 'parent_id' => $assetGroup->id]);
        ChartOfAccount::create(['code' => '1113', 'name' => 'Card', 'short_name' => 'Card', 'type' => 'Asset', 'parent_id' => $assetGroup->id]);
        ChartOfAccount::create(['code' => '1114', 'name' => 'Others', 'short_name' => 'Others', 'type' => 'Asset', 'parent_id' => $assetGroup->id]);
        ChartOfAccount::create(['code' => '1200', 'name' => 'Accounts Receivable', 'short_name' => 'AR', 'type' => 'Asset', 'parent_id' => $assetGroup->id]);
        ChartOfAccount::create(['code' => '1300', 'name' => 'Inventory', 'short_name' => 'Inventory', 'type' => 'Asset', 'parent_id' => $assetGroup->id]);

        // Accounts under Liabilities
        ChartOfAccount::create(['code' => '2100', 'name' => 'Accounts Payable', 'short_name' => 'AP', 'type' => 'Liability', 'parent_id' => $liabilityGroup->id]);
        ChartOfAccount::create(['code' => '2200', 'name' => 'Loans Payable', 'short_name' => 'Loans', 'type' => 'Liability', 'parent_id' => $liabilityGroup->id]);

        // Accounts under Equity
        ChartOfAccount::create(['code' => '3100', 'name' => "Owner's Equity", 'short_name' => "Owner's Equity", 'type' => 'Equity', 'parent_id' => $equityGroup->id]);

        // Accounts under Income
        ChartOfAccount::create(['code' => '4100', 'name' => 'Sales', 'short_name' => 'Sales', 'type' => 'Income', 'parent_id' => $incomeGroup->id]);
        ChartOfAccount::create(['code' => '4200', 'name' => 'Client Advance', 'short_name' => 'Client Advance', 'type' => 'Income', 'parent_id' => $incomeGroup->id]);
        ChartOfAccount::create(['code' => '4300', 'name' => 'Daily Basis Income', 'short_name' => 'DB Income', 'type' => 'Income', 'parent_id' => $incomeGroup->id]);
        ChartOfAccount::create(['code' => '4400', 'name' => 'Monthly Basis Income', 'short_name' => 'MB Income', 'type' => 'Income', 'parent_id' => $incomeGroup->id]);

        // Accounts under Expenses
        ChartOfAccount::create(['code' => '5100', 'name' => 'Rent', 'short_name' => 'Rent', 'type' => 'Expense', 'parent_id' => $expenseGroup->id]);
        ChartOfAccount::create(['code' => '5200', 'name' => 'Utilities', 'short_name' => 'Utilities', 'type' => 'Expense', 'parent_id' => $expenseGroup->id]);
        ChartOfAccount::create(['code' => '5300', 'name' => 'Salaries', 'short_name' => 'Salaries', 'type' => 'Expense', 'parent_id' => $expenseGroup->id]);
        ChartOfAccount::create(['code' => '5400', 'name' => 'Fuel Cost', 'short_name' => 'Fuel Cost', 'type' => 'Expense', 'parent_id' => $expenseGroup->id]);
        ChartOfAccount::create(['code' => '5500', 'name' => 'Driver Payments', 'short_name' => 'Driver Payments', 'type' => 'Expense', 'parent_id' => $expenseGroup->id]);
        ChartOfAccount::create(['code' => '5600', 'name' => 'Vendor Payments', 'short_name' => 'Vendor Payments', 'type' => 'Expense', 'parent_id' => $expenseGroup->id]);
    }
}
