<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientInvoice;
use App\Models\ClientPayment;
use App\Models\DailyBasis;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientPaymentController extends Controller
{
    /**
     * Get a paginated list of client payment records.
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

        // Fetch client payments based on the authenticated user's company and apply sorting
        $clientPayments = $company->clientInvoicePayments()
            ->with(['dailyBasis:id,client_id,vehicle_id,driver_id', 'clientInvoice:id,invoice_date,due_date,client_id,vehicle_id,driver_id'])
            ->when($request->has('client_id'), function ($query) use ($request, $company) {
                // Filter by client_id if the 'client_id' parameter is present in the request
                $client_id = $request->client_id;


                // Check if the provided client_id belongs to the company for ownership verification
                $client = $company->clients()->find($client_id);

                if (!$client) {
                    return response()->json(['error' => 'Client not found or unauthorized'], 404);
                }

                return $query->where('client_id', $client_id);
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Client payments retrieved successfully',
            'data' => $clientPayments,
        ], 200);
    }

    /**
     * Get payments for a specific client invoice.
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

        $clientInvoice = ClientInvoice::findOrFail($request->id);

        // Check if the clientInvoice belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $clientInvoice->company_id !== $user->company->id) {
            return response()->json(['error' => 'ClientInvoice not found or unauthorized'], 404);
        }

        // Fetch payments under the specified invoice
        $payments = $clientInvoice->payments()
            ->with(['clientInvoice:id,client_id', 'clientInvoice.client:id,name'])
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Payments for the specified invoice retrieved successfully',
            'data' => $payments,
        ], 200);
    }


    /**
     * Get payments for a specific client invoice.
     *
     * @param int $invoiceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentByClient(Request $request)
    {

        // Set default values for pagination if not provided in the request
        $page = $request->has('page') ? $request->page : 1;
        $perPage = $request->has('per_page') ? $request->per_page : 10;

        // Set default values for sorting if not provided in the request
        $sortBy = $request->has('sort_by') ? $request->sort_by : 'date';
        $sortOrder = $request->has('sort_order') ? $request->sort_order : 'desc';

        $client = Client::findOrFail($request->client_id);

        // Check if the clientInvoice belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $client->company_id !== $user->company->id) {
            return response()->json(['error' => 'Client not found or unauthorized'], 404);
        }

        // Fetch payments under the specified invoice
//        $payments = $client->payments()
//            ->with(['clientInvoice:id,client_id,invoice_number'])
//            ->orderBy($sortBy, $sortOrder)
//            ->paginate($perPage, ['*'], 'page', $page);

        $clientInvoicePayments = $client->payments()
            ->select('id', 'company_id', 'client_invoice_id', 'daily_basis_id', 'monthly_contract_id',
                'client_id', 'date', 'amount', 'payment_method',
                'payment_ref', 'payment_number', 'remarks', "created_at")
            ->orderBy($sortBy, $sortOrder);

        $fuelAdvancePayments = $client->fuelAdvancePayments()
            ->select('id', 'company_id', DB::raw('null as client_invoice_id'), 'daily_basis_id', 'monthly_contract_id',
                'client_id', 'posting_date as date', 'amount', 'payment_method',
                'payment_ref', 'payment_number', 'remarks', "created_at")
            ->orderBy($sortBy, $sortOrder);

        // Combine both queries using union
        $payments = $clientInvoicePayments->unionAll($fuelAdvancePayments)->with(['clientInvoice:id,client_id,invoice_number', 'dailyBasis:id,daily_basis_number'])->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Payments for the specified client retrieved successfully',
            'data' => $payments,
        ], 200);
    }

    /**
     * Create a new client payment record.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            // Validate the request data
            $validatedData = $request->validate(ClientPayment::validationRules());

            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $company = $user->company;

            if (!$company) {
                return response()->json(['error' => 'Company not found for the user'], 404);
            }

            // Create the client payment record
            $clientPayment = $company->clientInvoicePayments()->create($validatedData);

            // Update total_paid  and status in ClientInvoice and dailybasis
            $invoice = ClientInvoice::findOrFail($clientPayment->client_invoice_id);
            $dailyBasis = DailyBasis::findOrFail($clientPayment->daily_basis_id);
            $invoice->total_paid += $clientPayment->amount;
            $invoice->save();

            $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date) : null;
            $currentDate = Carbon::now();
            if ($invoice->total_paid >= $invoice->grand_total) {
                $invoice->status = "Paid";
                $dailyBasis->status = "Paid & Closed";
            } elseif ($dueDate && $currentDate->gt($dueDate) && $invoice->status != "Paid") {
                $invoice->status = "Payment Overdue";
                $dailyBasis->status = "Payment Overdue";
            }
            elseif ($invoice->total_paid > 0 && $invoice->total_paid < $invoice->grand_total) {
                $invoice->status = "Partially Paid";
                $dailyBasis->status = "Partially Paid";
            }

            $dailyBasis->save();
            $invoice->save();

            // Update client balance
            $client = $invoice->client;
            $client->current_balance -= $clientPayment->amount;
            $client->save();


            $clientPayment->load(['clientInvoice:id,client_id,total_paid,grand_total', 'clientInvoice.client:id,name']);
            $clientPayment->payment_number = $clientPayment->generatePaymentNumber("Daily", $clientPayment->clientInvoice->client->name, $clientPayment->client_invoice_id, $clientPayment->id);
            $clientPayment->save();
            $clientPayment->generateTransactions();

            return response()->json([
                'message' => 'Client payment record created successfully',
                'data' => $clientPayment,
            ], 201);
        });
    }

    /**
     * Get the details of a specific client payment record.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $clientPayment = ClientPayment::findOrFail($id);

        // Check if the clientPayment belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $clientPayment->company_id !== $user->company->id) {
            return response()->json(['error' => 'ClientPayment not found or unauthorized'], 404);
        }

        $clientPayment->load(['dailyBasis:id,client_id,vehicle_id,driver_id', 'clientInvoice:id,invoice_date,due_date,client_id,vehicle_id,driver_id']);

        return response()->json([
            'message' => 'Client payment retrieved successfully',
            'data' => $clientPayment,
        ], 200);
    }

    /**
     * Update a client payment record.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $company = Auth::user()->company;
        $clientPayment = $company->clientPayments()->findOrFail($id);

        $validatedData = $request->validate(ClientPayment::validationRules());

        $clientPayment->update($validatedData);

        $clientPayment->load(['dailyBasis:id,client_id,vehicle_id,driver_id', 'clientInvoice:id,invoice_date,due_date,client_id,vehicle_id,driver_id']);

        return response()->json([
            'message' => 'Client payment record updated successfully',
            'data' => $clientPayment,
        ], 200);
    }

    /**
     * Delete a client payment record.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $clientPayment = ClientPayment::findOrFail($id);

            // Update total_paid  and status in ClientInvoice and dailybasis
            $invoice = ClientInvoice::findOrFail($clientPayment->client_invoice_id);
            $dailyBasis = DailyBasis::findOrFail($clientPayment->daily_basis_id);
            $invoice->total_paid -= $clientPayment->amount;
            $invoice->save();

            // Update client balance
            $client = $invoice->client;
            $client->current_balance += $clientPayment->amount;
            $client->save();

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
            $dailyBasis->save();
            $invoice->save();

            $clientPayment->delete();

            return response()->json(['message' => 'Client payment record deleted successfully'], 200);
        });
    }
}
