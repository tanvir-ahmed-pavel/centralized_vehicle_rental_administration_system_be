<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\DriverInvoice;
use App\Models\DriverPayment;
use App\Models\DailyBasis;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DriverPaymentController extends Controller
{
    /**
     * Get a paginated list of driver payment records.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
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

        // Fetch driver payments based on the authenticated user's company and apply sorting
        $driverPayments = $company->driverInvoicePayments()
            ->with(['dailyBasis:id,driver_id,vehicle_id,client_id', 'driverInvoice:id,invoice_date,due_date,driver_id,vehicle_id,client_id'])
            ->when($request->has('driver_id'), function ($query) use ($request, $company) {
                // Filter by driver_id if the 'driver_id' parameter is present in the request
                $driver_id = $request->driver_id;


                // Check if the provided driver_id belongs to the company for ownership verification
                $driver = $company->drivers()->find($driver_id);

                if (!$driver) {
                    return response()->json(['error' => 'Driver not found or unauthorized'], 404);
                }

                return $query->where('driver_id', $driver_id);
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Driver payments retrieved successfully',
            'data' => $driverPayments,
        ], 200);
    }

    /**
     * Get payments for a specific driver invoice.
     *
     * @param  int  $invoiceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentByInvoice(Request $request)
    {

        // Set default values for pagination if not provided in the request
        $page = $request->has('page') ? $request->page : 1;
        $perPage = $request->has('per_page') ? $request->per_page : 10;

        // Set default values for sorting if not provided in the request
        $sortBy = $request->has('sort_by') ? $request->sort_by : 'date';
        $sortOrder = $request->has('sort_order') ? $request->sort_order : 'desc';

        $driverInvoice = DriverInvoice::findOrFail($request->id);

        // Check if the driverInvoice belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $driverInvoice->company_id !== $user->company->id) {
            return response()->json(['error' => 'DriverInvoice not found or unauthorized'], 404);
        }

        // Fetch payments under the specified invoice
        $payments = $driverInvoice->payments()
            ->with(['driverInvoice:id,driver_id', 'driverInvoice.driver:id,name'])
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Payments for the specified invoice retrieved successfully',
            'data' => $payments,
        ], 200);
    }


    /**
     * Get payments for a specific driver invoice.
     *
     * @param  int  $invoiceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentByDriver(Request $request)
    {

        // Set default values for pagination if not provided in the request
        $page = $request->has('page') ? $request->page : 1;
        $perPage = $request->has('per_page') ? $request->per_page : 10;

        // Set default values for sorting if not provided in the request
        $sortBy = $request->has('sort_by') ? $request->sort_by : 'date';
        $sortOrder = $request->has('sort_order') ? $request->sort_order : 'desc';

        $driver = Driver::findOrFail($request->driver_id);

        // Check if the driverInvoice belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $driver->company_id !== $user->company->id) {
            return response()->json(['error' => 'Driver not found or unauthorized'], 404);
        }

        // Fetch payments under the specified invoice
        $payments = $driver->payments()
            ->with(['driverInvoice:id,driver_id,invoice_number'])
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Payments for the specified driver retrieved successfully',
            'data' => $payments,
        ], 200);
    }

    /**
     * Create a new driver payment record.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            // Validate the request data
            $validatedData = $request->validate(DriverPayment::validationRules());

            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $company = $user->company;

            if (!$company) {
                return response()->json(['error' => 'Company not found for the user'], 404);
            }

            // Create the driver payment record
            $driverPayment = $company->driverInvoicePayments()->create($validatedData);

            // Update total_paid  and status in DriverInvoice and dailybasis
            $invoice = DriverInvoice::findOrFail($driverPayment->driver_invoice_id);
            $invoice->total_paid += $driverPayment->amount;
            $invoice->save();

            $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date) : null;
            $currentDate = Carbon::now();

            if ($invoice->total_paid >= $invoice->grand_total) {
                $invoice->status = "Paid";
            } elseif ($dueDate && $currentDate->gt($dueDate) && $invoice->status != "Paid") {
                $invoice->status = "Payment Overdue";
            } elseif ($invoice->total_paid > 0 && $invoice->total_paid < $invoice->grand_total) {
                $invoice->status = "Partially Paid";
            }
            $invoice->save();

            // Update driver balance
            $driver = $invoice->driver;
            $driver->current_balance -= $driverPayment->amount;
            $driver->save();



            $driverPayment->load(['driverInvoice:id,driver_id,total_paid,grand_total', 'driverInvoice.driver:id,name']);
            $driverPayment->payment_number = $driverPayment->generatePaymentNumber("Daily", $driverPayment->driverInvoice->driver->name, $driverPayment->driver_invoice_id, $driverPayment->id);
            $driverPayment->save();
            $driverPayment->generateTransactions();

            return response()->json([
                'message' => 'Driver payment record created successfully',
                'data' => $driverPayment,
            ], 201);
        });
    }

    /**
     * Get the details of a specific driver payment record.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $driverPayment = DriverPayment::findOrFail($id);

        // Check if the driverPayment belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $driverPayment->company_id !== $user->company->id) {
            return response()->json(['error' => 'DriverPayment not found or unauthorized'], 404);
        }

        $driverPayment->load(['dailyBasis:id,driver_id,vehicle_id,client_id', 'driverInvoice:id,invoice_date,due_date,driver_id,vehicle_id,client_id']);

        return response()->json([
            'message' => 'Driver payment retrieved successfully',
            'data' => $driverPayment,
        ], 200);
    }

    /**
     * Update a driver payment record.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $company = Auth::user()->company;
        $driverPayment = $company->driverPayments()->findOrFail($id);

        $validatedData = $request->validate(DriverPayment::validationRules());

        $driverPayment->update($validatedData);

        $driverPayment->load(['dailyBasis:id,driver_id,vehicle_id,client_id', 'driverInvoice:id,invoice_date,due_date,driver_id,vehicle_id,client_id']);

        return response()->json([
            'message' => 'Driver payment record updated successfully',
            'data' => $driverPayment,
        ], 200);
    }

    /**
     * Delete a driver payment record.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $driverPayment = DriverPayment::findOrFail($id);

            // Update total_paid  and status in DriverInvoice and dailybasis
            $invoice = DriverInvoice::findOrFail($driverPayment->driver_invoice_id);
            $invoice->total_paid -= $driverPayment->amount;
            $invoice->save();

            // Update driver balance
            $driver = $invoice->driver;
            $driver->current_balance += $driverPayment->amount;
            $driver->save();

            if($invoice->total_paid>0 && $invoice->total_paid<$invoice->grand_total){
                $invoice->status = "Partially Paid";
            } elseif ($invoice->total_paid>=$invoice->grand_total){
                $invoice->status = "Paid";
            } else{
                $invoice->status = "Created & Awaiting Payment";
            }
            $invoice->save();

            $driverPayment->delete();

            return response()->json(['message' => 'Driver payment record deleted successfully'], 200);
        });
    }
}
