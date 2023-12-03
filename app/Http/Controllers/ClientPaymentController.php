<?php

namespace App\Http\Controllers;

use App\Models\ClientInvoice;
use App\Models\ClientPayment;
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
        $clientPayments = $company->clientPayments()
            ->with(['dailyBasis:id,client_id,vehicle_id,driver_id', 'clientInvoice:id,invoice_date,due_date,client_id,vehicle_id,driver_id'])
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

            // Update total_paid in ClientInvoice
            $invoice = ClientInvoice::findOrFail($clientPayment->client_invoice_id);
            $invoice->total_paid += $clientPayment->amount;
            $invoice->save();

            // Update client balance
            $client = $invoice->client;
            $client->current_balance -= $clientPayment->amount;
            $client->save();

            $clientPayment->load(['clientInvoice:id,client_id,total_paid,grand_total', 'clientInvoice.client:id,name']);
            $clientPayment->payment_number = $clientPayment->generatePaymentNumber("Daily", $clientPayment->clientInvoice->client->name, $clientPayment->client_invoice_id, $clientPayment->id);
            $clientPayment->save();

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

        $clientPayment->load(['dailyBasis:id,client_id,vehicle_id,driver_id', 'clientInvoice:id,invoice_date,due_date,client_id,vehicle_id,driver_id', 'chartOfAccount:id,name']);

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

        $clientPayment->load(['dailyBasis:id,client_id,vehicle_id,driver_id', 'clientInvoice:id,invoice_date,due_date,client_id,vehicle_id,driver_id', 'chartOfAccount:id,name']);

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

            // Update total_paid in ClientInvoice
            $invoice = ClientInvoice::findOrFail($clientPayment->client_invoice_id);
            $invoice->total_paid -= $clientPayment->amount;
            $invoice->save();

            // Update client balance
            $client = $invoice->client;
            $client->current_balance += $clientPayment->amount;
            $client->save();

            $clientPayment->delete();

            return response()->json(['message' => 'Client payment record deleted successfully'], 200);
        });
    }
}
