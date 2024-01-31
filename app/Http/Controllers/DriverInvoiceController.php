<?php

namespace App\Http\Controllers;

use App\Models\DriverInvoice;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DriverInvoiceController extends Controller
{
    /**
     * Display a listing of the driver invoices.
     *
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

        // Fetch driver invoices based on the authenticated user's company and apply sorting
        $driverInvoices = $company->driverInvoices()
            ->with(['vehicle:id,name,model_year,reg_no', 'driver:id,name,address,mobile_no', 'client:id,name,mobile_no', 'invoiceItems'])
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

                return $query->where('monthly_contract_id', $monthlyContract);
            })
            ->when($request->has('status'), function ($query) use ($request) {
                $statuses = explode(',', $request->status);

                return $query->whereIn('status', $statuses);
            })
            ->when($request->has('driver_id'), function ($query) use ($company, $request) {
                $driver_id = $request->driver_id;

                $driver = $company->drivers()->find($driver_id);

                return $driver
                    ? $query->where('driver_id', $driver_id)
                    : $query->where('id', 0); // No results if driver is unauthorized or not found
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Driver invoices retrieved successfully',
            'data' => $driverInvoices,
        ], 200);

    }

    /**
     * Store a newly created driver invoice.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            // Validate the request data
            $validatedData = $request->validate(DriverInvoice::validationRules());

            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $company = $user->company;

            if (!$company) {
                return response()->json(['error' => 'Company not found for the user'], 404);
            }

            // Create the driver invoice record
            $driverInvoice = $company->driverInvoices()->create($validatedData);

            // Create invoice item records if provided in the request
            if ($request->has('invoice_items') && is_array($request->invoice_items)) {
                foreach ($request->invoice_items as $itemData) {
                    $driverInvoice->invoiceItems()->create($itemData);
                }
            }

            // Load relationships for the response
            $driverInvoice->load(['vehicle:id,name,model_year,reg_no', 'client:id,name,address,mobile_no', 'driver:id,name,mobile_no', 'invoiceItems']);

            // Generate invoice number
            $driverInvoice->invoice_number = $driverInvoice->generateInvoiceNumber("Daily", $driverInvoice->driver->name, $driverInvoice->id, $driverInvoice->daily_basis_id);
            $driverInvoice->save();

//            Generate Chart of accounts transaction
            $driverInvoice->generateTransactions();


            // Update driver balance by increasing the grand_total amount of the invoice
            $driver = $driverInvoice->driver;
            $driver->current_balance += $driverInvoice->grand_total;
            $driver->save();

            // Update daily basis status
            $dueDate = $driverInvoice->due_date ? Carbon::parse($driverInvoice->due_date) : null;
            $currentDate = Carbon::now();

            if ($dueDate && $currentDate->gt($dueDate)) {
                $driverInvoice->status = "Payment Overdue";
                $driverInvoice->save();
            }

            return response()->json([
                'message' => 'Driver invoice created successfully',
                'data' => $driverInvoice,
            ], 201);
        });
    }

    /**
     * Get the details of a specific driver invoice.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $driverInvoice = DriverInvoice::findOrFail($id);

        // Check if the driverInvoice belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $driverInvoice->company_id !== $user->company->id) {
            return response()->json(['error' => 'DriverInvoice not found or unauthorized'], 404);
        }

        // Load relationships for the response
        $driverInvoice->load(['vehicle:id,name,model_year,reg_no', 'client:id,name,address,mobile_no', 'driver:id,name,mobile_no', 'invoiceItems'])
            ->loadCount("payments");

        return response()->json([
            'message' => 'Driver invoice retrieved successfully',
            'data' => $driverInvoice,
        ], 200);
    }

    /**
     * Update a driver invoice record.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {

            $driverInvoice = DriverInvoice::findOrFail($id);

            // Check if the driverPayment belongs to the logged-in user's company
            $user = Auth::user();
            if (!$user->company || $driverInvoice->company_id !== $user->company->id) {
                return response()->json(['error' => 'Driver invoice not found or unauthorized'], 404);
            }

            // Validate the request data
            $validatedData = $request->validate(DriverInvoice::validationRules());

//            deduct old amount from driver balance
            $driver = $driverInvoice->driver;
            $driver->current_balance -= $driverInvoice->grand_total;

            // Update the driver invoice record
            $driverInvoice->update($validatedData);

            // Update or create invoice item records if provided in the request
            $driverInvoice->invoiceItems()->delete();
            if ($request->has('invoice_items') && is_array($request->invoice_items)) {
                foreach ($request->invoice_items as $itemData) {
                    $driverInvoice->invoiceItems()->updateOrCreate(['id' => $itemData['id']], $itemData);
                }
            }

            // Load relationships for the response
            $driverInvoice->load(['vehicle:id,name,model_year,reg_no', 'client:id,name,address,mobile_no', 'driver:id,name,mobile_no', 'invoiceItems']);

            // Update driver balance by increasing the grand_total amount of the invoice
            $driver->current_balance += $driverInvoice->grand_total;
            $driver->save();

            // Update daily basis status
            $dueDate = $driverInvoice->due_date ? Carbon::parse($driverInvoice->due_date) : null;
            $currentDate = Carbon::now();

            if ($dueDate && $currentDate->gt($dueDate) ) {
                $driverInvoice->status = "Payment Overdue";
                $driverInvoice->save();
            }


            return response()->json([
                'message' => 'Driver invoice updated successfully',
                'data' => $driverInvoice,
            ], 200);
        });
    }

    /**
     * Delete a driver invoice record.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $driverInvoice = DriverInvoice::findOrFail($id);

            // Check if the driverInvoice belongs to the logged-in user's company
            $user = Auth::user();
            if (!$user->company || $driverInvoice->company_id !== $user->company->id) {
                return response()->json(['error' => 'Driver Invoice not found or unauthorized'], 404);
            }

            // Check if the invoice has any associated payments
            $paymentsCount = $driverInvoice->payments()->count();
            if ($paymentsCount > 0) {
                return response()->json(['error' => 'Cannot delete invoice with associated payments'], 400);
            }

            // Update driver balance by deducting the total amount of the invoice
            $driver = $driverInvoice->driver;
            $driver->current_balance -= $driverInvoice->grand_total;
            $driver->save();

            // Update daily basis status
            $dailyBasis = $driverInvoice->dailyBasis;
            $dailyBasis->status = "To Make Invoice";
            $dailyBasis->save();

            // Perform the deletion
            $driverInvoice->delete();

            return response()->json(['message' => 'Driver invoice deleted successfully'], 200);
        });
    }
}
