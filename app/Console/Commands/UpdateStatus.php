<?php

namespace App\Console\Commands;

use App\Models\ClientInvoice;
use App\Models\DriverInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateStatus extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update invoice status based on due date comparison';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $clientInvoices = ClientInvoice::all();
        $driverInvoices = DriverInvoice::all();
        $vendorInvoices = DriverInvoice::all();
        $vehicles = DriverInvoice::all();
        $currentDate = Carbon::now();


        foreach ($vehicles as $vehicle) {
            if ($vehicle->monthlyContracts()->contractPeriod()->whereDate('start_date', '<=', $currentDate)->whereDate('end_date', '>=', $currentDate)->exists()
                || $vehicle->dailyBases()->dutyDates()->whereDate('start_date', $currentDate)->exists()) {
                $vehicle->is_available = false;
                $vehicle->save();
            } else {
                $vehicle->is_available = true;
                $vehicle->save();
            }


        }

        foreach ($clientInvoices as $clientInvoice) {
            $dueDate = $clientInvoice->due_date ? Carbon::parse($clientInvoice->due_date) : null;

            if ($dueDate && $currentDate->gt($dueDate) && $clientInvoice->status != "Paid") {
                $dailyBasis = $clientInvoice->dailyBasis;
                $dailyBasis->status = "Payment Overdue";
                $dailyBasis->save();
                $clientInvoice->status = "Payment Overdue";
                $clientInvoice->save();
            }
        }

        foreach ($driverInvoices as $driverInvoice) {
            $dueDate = $driverInvoice->due_date ? Carbon::parse($driverInvoice->due_date) : null;

            if ($dueDate && $currentDate->gt($dueDate) && $driverInvoice->status != "Paid") {
                $driverInvoice->status = "Payment Overdue";
                $driverInvoice->save();
            }
        }

        foreach ($vendorInvoices as $vendorInvoice) {
            $dueDate = $vendorInvoice->due_date ? Carbon::parse($vendorInvoice->due_date) : null;

            if ($dueDate && $currentDate->gt($dueDate) && $vendorInvoice->status != "Paid") {
                $vendorInvoice->status = "Payment Overdue";
                $vendorInvoice->save();
            }
        }

        $this->info('Invoice and vehicle statuses updated successfully!');

    }
}
