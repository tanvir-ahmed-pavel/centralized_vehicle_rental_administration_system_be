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
        $clientInvoices = ClientInvoice::with("dailyBases")->all();
        $driverInvoices = DriverInvoice::all();
        $currentDate = Carbon::now();

        foreach ($clientInvoices as $clientInvoice){
            $dueDate = $clientInvoice->due_date ? Carbon::parse($clientInvoice->due_date) : null;

            if ($dueDate && $currentDate->gt($dueDate) && $clientInvoice->status != "Paid") {
                $dailyBasis = $clientInvoice->dailyBasis;
                $dailyBasis->status = "Payment Overdue";
                $dailyBasis->save();
                $clientInvoice->status = "Payment Overdue";
                $clientInvoice->save();
            }
        }

        foreach ($driverInvoices as $driverInvoice){
            $dueDate = $driverInvoice->due_date ? Carbon::parse($driverInvoice->due_date) : null;

            if ($dueDate && $currentDate->gt($dueDate) && $driverInvoice->status != "Paid") {
                $driverInvoice->status = "Payment Overdue";
                $driverInvoice->save();
            }
        }

        $this->info('Invoice statuses updated successfully!');

    }
}
