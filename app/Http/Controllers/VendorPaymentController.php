<?php

namespace App\Http\Controllers;

use App\Models\DailyBasis;
use App\Models\MonthlyContract;
use App\Models\Vendor;
use App\Models\VendorInvoice;
use App\Models\VendorPayment;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorPaymentController extends Controller
{
    /**
     * Get a paginated list of vendor payment records.
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

        // Fetch vendor payments based on the authenticated user's company and apply sorting
        $vendorPayments = $company->vendorInvoicePayments()
            ->with(['dailyBasis:id,vendor_id,vehicle_id,client_id,driver_id', 'vendorInvoice:id,invoice_date,due_date,vendor_id,driver_id,vehicle_id,client_id'])
            ->when($request->has('vendor_id'), function ($query) use ($request, $company) {
                // Filter by vendor_id if the 'vendor_id' parameter is present in the request
                $vendor_id = $request->vendor_id;


                // Check if the provided vendor_id belongs to the company for ownership verification
                $vendor = $company->vendors()->find($vendor_id);

                if (!$vendor) {
                    return response()->json(['error' => 'Vendor not found or unauthorized'], 404);
                }

                return $query->where('vendor_id', $vendor_id);
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Vendor payments retrieved successfully',
            'data' => $vendorPayments,
        ], 200);
    }

    /**
     * Get payments for a specific vendor invoice.
     *
     * @param int $invoiceId
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

        $vendorInvoice = VendorInvoice::findOrFail($request->id);

        // Check if the vendorInvoice belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $vendorInvoice->company_id !== $user->company->id) {
            return response()->json(['error' => 'VendorInvoice not found or unauthorized'], 404);
        }

        // Fetch payments under the specified invoice
        $payments = $vendorInvoice->payments()
            ->with(['vendorInvoice:id,vendor_id', 'vendorInvoice.vendor:id,name'])
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Payments for the specified invoice retrieved successfully',
            'data' => $payments,
        ], 200);
    }


    /**
     * Get payments for a specific vendor invoice.
     *
     * @param int $invoiceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentByVendor(Request $request)
    {

        // Set default values for pagination if not provided in the request
        $page = $request->has('page') ? $request->page : 1;
        $perPage = $request->has('per_page') ? $request->per_page : 10;

        // Set default values for sorting if not provided in the request
        $sortBy = $request->has('sort_by') ? $request->sort_by : 'date';
        $sortOrder = $request->has('sort_order') ? $request->sort_order : 'desc';

        $vendor = Vendor::findOrFail($request->vendor_id);

        // Check if the vendorInvoice belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $vendor->company_id !== $user->company->id) {
            return response()->json(['error' => 'Vendor not found or unauthorized'], 404);
        }

        // Fetch payments under the specified invoice


        $vendorInvoicePayments = $vendor->payments()
            ->select('id', 'company_id', 'vendor_invoice_id', 'daily_basis_id', 'monthly_contract_id',
                'vendor_id', 'date', 'amount', 'payment_method',
                'payment_ref', 'payment_number', 'remarks', "created_at")
            ->orderBy($sortBy, $sortOrder);

        $fuelAdvancePayments = $vendor->fuelAdvancePayments()
            ->select('id', 'company_id', DB::raw('null as vendor_invoice_id'), 'daily_basis_id', 'monthly_contract_id',
                'vendor_id', 'posting_date as date', 'amount', 'payment_method',
                'payment_ref', 'payment_number', 'remarks', "created_at")
            ->orderBy($sortBy, $sortOrder);

        // Combine both queries using union
        $payments = $vendorInvoicePayments->unionAll($fuelAdvancePayments)
            ->with(['vendorInvoice:id,vendor_id,invoice_number', 'dailyBasis:id,daily_basis_number'])
            ->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);


        return response()->json([
            'message' => 'Payments for the specified vendor retrieved successfully',
            'data' => $payments,
        ], 200);
    }

    /**
     * Get the details of a specific vendor payment record.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $vendorPayment = VendorPayment::findOrFail($id);

        // Check if the vendorPayment belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $vendorPayment->company_id !== $user->company->id) {
            return response()->json(['error' => 'VendorPayment not found or unauthorized'], 404);
        }

        $vendorPayment->load(['dailyBasis:id,vendor_id,vehicle_id,client_id,driver_id', 'vendorInvoice:id,invoice_date,due_date,vendor_id,driver_id,vehicle_id,client_id']);

        return response()->json([
            'message' => 'Vendor payment retrieved successfully',
            'data' => $vendorPayment,
        ], 200);
    }

    /**
     * Create a new vendor payment record.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $validatedData = $request->validate(VendorPayment::validationRules());

            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $company = $user->company;

            if (!$company) {
                return response()->json(['error' => 'Company not found for the user'], 404);
            }

            // Determine if the payment is associated with a daily basis or monthly contract
            $dailyBasisId = $request->input('daily_basis_id');
            $monthlyContractId = $request->input('monthly_contract_id');
            $invoice = VendorInvoice::findOrFail($request->vendor_invoice_id);


            if(!$dailyBasisId && !$monthlyContractId){
                return response()->json(['error' => 'Daily basis ID or monthly contract ID is required'], 422);
            }

            // Create the vendor payment record
            $vendorPayment = $company->vendorInvoicePayments()->create($validatedData);

            // Update total_paid and status in VendorInvoice and daily basis
            $invoice->total_paid += $vendorPayment->amount;
            $invoice->save();

            // Adjust status based on payment amount and due date
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

            // Update vendor balance
            $vendor = $invoice->vendor;
            $vendor->current_balance -= $vendorPayment->amount;
            $vendor->save();

            // Generate payment number and save the vendor payment record
            $paymentType = $dailyBasisId ? "Daily" : "Monthly";
            $vendorPayment->payment_number = $vendorPayment->generatePaymentNumber($paymentType, $vendor->name, $invoice->id, $vendorPayment->id);
            $vendorPayment->save();
            $vendorPayment->generateTransactions();

            $vendorPayment->load(['vendorInvoice:id,vendor_id,total_paid,grand_total', 'vendorInvoice.vendor:id,name']);

            // Return the response
            return response()->json([
                'message' => 'Vendor payment record created successfully',
                'data' => $vendorPayment,
            ], 201);
        });
    }


    /**
     * Update a vendor payment record.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $company = Auth::user()->company;
        $vendorPayment = $company->vendorPayments()->findOrFail($id);

        $validatedData = $request->validate(VendorPayment::validationRules());

        $vendorPayment->update($validatedData);

        $vendorPayment->load(['dailyBasis:id,vendor_id,vehicle_id,client_id,driver_id', 'vendorInvoice:id,invoice_date,due_date,vendor_id,driver_id,vehicle_id,client_id']);

        return response()->json([
            'message' => 'Vendor payment record updated successfully',
            'data' => $vendorPayment,
        ], 200);
    }

    /**
     * Delete a vendor payment record.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            // Find the vendor payment record
            $vendorPayment = VendorPayment::findOrFail($id);
            $invoice = VendorInvoice::findOrFail($vendorPayment->vendor_invoice_id);


            // Determine if the payment is associated with a daily basis or monthly contract
            if ($vendorPayment->daily_basis_id) {
                // If associated with a daily basis, update total_paid and status in VendorInvoice and DailyBasis
                $dailyBasis = DailyBasis::findOrFail($vendorPayment->daily_basis_id);

                // Update total_paid in VendorInvoice
                $invoice->total_paid -= $vendorPayment->amount;

                // Update invoice status based on total paid
                if ($invoice->total_paid > 0 && $invoice->total_paid < $invoice->grand_total) {
                    $invoice->status = "Partially Paid";
                    $dailyBasis->status = "Partially Paid";
                } elseif ($invoice->total_paid >= $invoice->grand_total) {
                    $invoice->status = "Paid";
                    $dailyBasis->status = "Paid & Closed";
                } else {
                    $invoice->status = "Created & Awaiting Payment";
                    $dailyBasis->status = "Invoice Created & Awaiting Payment";
                }

                // Update vendor balance
                $vendor = $invoice->vendor;
                $vendor->current_balance += $vendorPayment->amount;

                // Save changes
                $invoice->save();
                $dailyBasis->save();
                $vendor->save();
            } elseif ($vendorPayment->monthly_contract_id) {
                // If associated with a monthly contract, update total_paid and status in MonthlyContract
                $monthlyContract = MonthlyContract::findOrFail($vendorPayment->monthly_contract_id);

                // Update total_paid in VendorInvoice
                $invoice->total_paid -= $vendorPayment->amount;


                // Update contract status based on total paid
                if ($invoice->total_paid > 0 && $invoice->total_paid < $invoice->grand_total) {
                    $invoice->status = "Partially Paid";
                } elseif ($invoice->total_paid >= $invoice->grand_total) {
                    $invoice->status = "Paid";
                } else {
                    $invoice->status = "Created & Awaiting Payment";
                }

                // Update vendor balance
                $vendor = $invoice->vendor;
                $vendor->current_balance += $vendorPayment->amount;

                // Save changes
                $monthlyContract->save();
                $invoice->save();
                $vendor->save();
            }

            // Delete the vendor payment record
            $vendorPayment->delete();

            return response()->json(['message' => 'Vendor payment record deleted successfully'], 200);
        });
    }
}
