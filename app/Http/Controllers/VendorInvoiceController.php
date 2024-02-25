<?php

namespace App\Http\Controllers;

use App\Models\VendorInvoice;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorInvoiceController extends Controller
{
    /**
     * Display a listing of the vendor invoices.
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

        $vendorInvoices = $company->vendorInvoices()
            ->with(['vehicle:id,name,model_year,reg_no', 'vendor:id,name,address,mobile_no', 'client:id,name,mobile_no', 'invoiceItems'])
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
            ->when($request->has('status'), function ($query) use ($request) {
                $statuses = explode(',', $request->status);

                return $query->whereIn('status', $statuses);
            })
            ->when($request->has('start_date') && $request->has('end_date'), function ($query) use ($request) {
                return $query->whereBetween('invoice_date', [$request->start_date, $request->end_date]);
            })
            ->when($request->has('vendor_id'), function ($query) use ($company, $request) {
                $vendor_id = $request->vendor_id;

                $vendor = $company->vendors()->find($vendor_id);

                return $vendor
                    ? $query->where('vendor_id', $vendor_id)
                    : $query->where('id', 0); // This will ensure no results if vendor is unauthorized or not found
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Vendor invoices retrieved successfully',
            'data' => $vendorInvoices,
        ], 200);

    }

    /**
     * Get the details of a specific vendor invoice.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $vendorInvoice = VendorInvoice::findOrFail($id);

        // Check if the vendorInvoice belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $vendorInvoice->company_id !== $user->company->id) {
            return response()->json(['error' => 'VendorInvoice not found or unauthorized'], 404);
        }

        // Load relationships for the response
        $vendorInvoice->load(['vehicle:id,name,model_year,reg_no', 'driver:id,name,address,mobile_no', 'client:id,name,mobile_no', 'vendor:id,name,address,city,country,email,mobile_no', 'invoiceItems'])
            ->loadCount("payments");

        return response()->json([
            'message' => 'Vendor invoice retrieved successfully',
            'data' => $vendorInvoice,
        ], 200);
    }

    /**
     * Store a newly created vendor invoice.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            // Validate the request data
            $validatedData = $request->validate(VendorInvoice::validationRules());

            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $company = $user->company;

            if (!$company) {
                return response()->json(['error' => 'Company not found for the user'], 404);
            }

            // Determine if the invoice is associated with a daily basis or monthly contract
            $dailyBasisId = $request->input('daily_basis_id');
            $monthlyContractId = $request->input('monthly_contract_id');

            if (!$dailyBasisId && !$monthlyContractId) {
                return response()->json(['error' => 'Daily basis ID or monthly contract ID is required'], 422);
            }

            // Create the vendor invoice record
            $vendorInvoice = $company->vendorInvoices()->create($validatedData);

            // Create invoice item records if provided in the request
            if ($request->has('invoice_items') && is_array($request->invoice_items)) {
                foreach ($request->invoice_items as $itemData) {
                    $vendorInvoice->invoiceItems()->create($itemData);
                }
            }

            // Load relationships for the response
            $vendorInvoice->load(['vehicle:id,name,model_year,reg_no', 'client:id,name,address,mobile_no,current_balance', 'vendor:id,name,mobile_no,current_balance', 'invoiceItems']);

            // Generate invoice number
            $invoiceType = $dailyBasisId ? "Daily" : "Monthly";
            $bookingId = $dailyBasisId ? $vendorInvoice->daily_basis_id : $vendorInvoice->monthly_contract_id;
            $vendorInvoice->invoice_number = $vendorInvoice->generateInvoiceNumber($invoiceType, $vendorInvoice->vendor->name, $vendorInvoice->id, $bookingId);
            $vendorInvoice->save();

            // Generate Chart of accounts transaction
            $vendorInvoice->generateTransactions();

            // Update vendor balance by increasing the grand_total amount of the invoice
            $vendor = $vendorInvoice->vendor;
            $vendor->current_balance += $vendorInvoice->grand_total;
            $vendor->save();

            // Update daily basis status
            $booking = $dailyBasisId ? $vendorInvoice->dailyBasis : $vendorInvoice->monthlyContract;
            if ($booking) {
                $dueDate = $vendorInvoice->due_date ? Carbon::parse($vendorInvoice->due_date) : null;
                $currentDate = Carbon::now();
                $booking->status = $dueDate && $currentDate->gt($dueDate) ? "Payment Overdue" : "Invoice Created & Awaiting Payment";
                $booking->save();
            }

            return response()->json([
                'message' => 'Vendor invoice created successfully',
                'data' => $vendorInvoice,
            ], 201);
        });
    }

    /**
     * Update a vendor invoice record.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {

            $vendorInvoice = VendorInvoice::findOrFail($id);

            // Check if the vendor invoice belongs to the logged-in user's company
            $user = Auth::user();
            if (!$user->company || $vendorInvoice->company_id !== $user->company->id) {
                return response()->json(['error' => 'Vendor invoice not found or unauthorized'], 404);
            }

            // Validate the request data
            $validatedData = $request->validate(VendorInvoice::validationRules());

            // Deduct old amount from vendor balance
            $vendor = $vendorInvoice->vendor;
            $vendor->current_balance -= $vendorInvoice->grand_total;

            // Update the vendor invoice record
            $vendorInvoice->update($validatedData);

            // Update or create invoice item records if provided in the request
            $vendorInvoice->invoiceItems()->delete();
            if ($request->has('invoice_items') && is_array($request->invoice_items)) {
                foreach ($request->invoice_items as $itemData) {
                    $vendorInvoice->invoiceItems()->create($itemData);
                }
            }

            // Load relationships for the response
            $vendorInvoice->load(['vehicle:id,name,model_year,reg_no', 'client:id,name,address,mobile_no', 'vendor:id,name,mobile_no,current_balance', 'invoiceItems']);

            // Update vendor balance by increasing the grand_total amount of the invoice
            $vendor->current_balance += $vendorInvoice->grand_total;
            $vendor->save();

            // Update daily basis status
            $booking = $vendorInvoice->dailyBasis ?? $vendorInvoice->monthlyContract;
            if ($booking) {
                $dueDate = $vendorInvoice->due_date ? Carbon::parse($vendorInvoice->due_date) : null;
                $currentDate = Carbon::now();
                $booking->status = $dueDate && $currentDate->gt($dueDate) ? "Payment Overdue" : "Invoice Created & Awaiting Payment";
                $booking->save();
            }

            return response()->json([
                'message' => 'Vendor invoice updated successfully',
                'data' => $vendorInvoice,
            ], 200);
        });
    }
    /**
     * Delete a vendor invoice record.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $vendorInvoice = VendorInvoice::findOrFail($id);

            // Check if the vendorInvoice belongs to the logged-in user's company
            $user = Auth::user();
            if (!$user->company || $vendorInvoice->company_id !== $user->company->id) {
                return response()->json(['error' => 'Vendor Invoice not found or unauthorized'], 404);
            }

            // Check if the invoice has any associated payments
            $paymentsCount = $vendorInvoice->payments()->count();
            if ($paymentsCount > 0) {
                return response()->json(['error' => 'Cannot delete invoice with associated payments'], 400);
            }

            // Update vendor balance by deducting the total amount of the invoice
            $vendor = $vendorInvoice->vendor;
            $vendor->current_balance -= $vendorInvoice->grand_total;
            $vendor->save();

            // Update daily basis status if applicable
            if ($vendorInvoice->dailyBasis()->exists()) {
                $vendorInvoice->dailyBasis->update(['status' => 'To Make Invoice']);
            }

            // Update monthly contract status if applicable
            if ($vendorInvoice->monthlyContract()->exists()) {
                $vendorInvoice->monthlyContract->update(['status' => 'To Make Invoice']);
            }

            // Perform the deletion
            $vendorInvoice->delete();

            return response()->json(['message' => 'Vendor invoice deleted successfully'], 200);
        });
    }
}
