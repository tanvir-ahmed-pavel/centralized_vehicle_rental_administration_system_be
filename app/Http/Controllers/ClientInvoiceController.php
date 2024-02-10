<?php

namespace App\Http\Controllers;

use App\Models\ClientInvoice;
use App\Http\Controllers\Controller;
use App\Traits\DataMapping;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientInvoiceController extends Controller
{
    use DataMapping;

    /**
     * Display a listing of the client invoices.
     *
     * @return JsonResponse
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

        // Fetch client invoices based on the authenticated user's company and apply sorting
        $clientInvoices = $company->clientInvoices()
            ->with(['vehicle:id,name,model_year,reg_no', 'client:id,name,address,mobile_no', 'driver:id,name,mobile_no', 'invoiceItems'])
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
            ->when($request->has('start_date') && $request->has('end_date'), function ($query) use ($request) {
                return $query->whereBetween('invoice_date', [$request->start_date, $request->end_date]);
            })
            ->when($request->has('client_id'), function ($query) use ($company, $request) {
                $client_id = $request->client_id;

                // Check if the provided client_id belongs to the company for ownership verification
                $client = $company->clients()->find($client_id);

                if (!$client) {
                    return response()->json(['error' => 'Client not found or unauthorized'], 404);
                }

                return $query->where('client_id', $client_id);
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Client invoices retrieved successfully',
            'data' => $clientInvoices,
        ], 200);

    }

    /**
     * Store a newly created client invoice.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            // Validate the request data
            $validatedData = $request->validate(ClientInvoice::validationRules());

            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $company = $user->company;

            if (!$company) {
                return response()->json(['error' => 'Company not found for the user'], 404);
            }

            // Create the client invoice record
            $clientInvoice = $company->clientInvoices()->create($validatedData);

            // Create invoice item records if provided in the request
            if ($request->has('invoice_items') && is_array($request->invoice_items)) {
                foreach ($request->invoice_items as $itemData) {
                    $clientInvoice->invoiceItems()->create($itemData);
                }
            }

            // Load relationships for the response
            $clientInvoice->load(['vehicle:id,name,model_year,reg_no', 'client:id,name,address,mobile_no,current_balance', 'driver:id,name,mobile_no', 'invoiceItems']);

            // Generate invoice number
            $clientInvoice->invoice_number = $clientInvoice->generateInvoiceNumber("Daily", $clientInvoice->client->name, $clientInvoice->id, $clientInvoice->daily_basis_id);
            $clientInvoice->save();

//            Generate Chart of accounts transaction
            $clientInvoice->generateTransactions();


            // Update client balance by increasing the grand_total amount of the invoice
            $client = $clientInvoice->client;
            $client->current_balance += $clientInvoice->grand_total;
            $client->save();

            // Update daily basis status
            $dueDate = $clientInvoice->due_date ? Carbon::parse($clientInvoice->due_date) : null;
            $currentDate = Carbon::now();

            if ($dueDate && $currentDate->gt($dueDate)) {
                $dailyBasis = $clientInvoice->dailyBasis;
                $dailyBasis->status = "Payment Overdue";
                $clientInvoice->status = "Payment Overdue";
                $clientInvoice->save();
                $dailyBasis->save();
            } else {
                $dailyBasis = $clientInvoice->dailyBasis;
                $dailyBasis->status = "Invoice Created & Awaiting Payment";
                $dailyBasis->save();
            }

            return response()->json([
                'message' => 'Client invoice created successfully',
                'data' => $clientInvoice,
            ], 201);
        });
    }

    /**
     * Get the details of a specific client invoice.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $clientInvoice = ClientInvoice::findOrFail($id);

        // Check if the clientInvoice belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $clientInvoice->company_id !== $user->company->id) {
            return response()->json(['error' => 'ClientInvoice not found or unauthorized'], 404);
        }

        // Load relationships for the response
        $clientInvoice->load(['vehicle:id,name,model_year,reg_no', 'client:id,name,address,city,country,email,mobile_no', 'driver:id,name,mobile_no', 'invoiceItems'])
            ->loadCount("payments");

        return response()->json([
            'message' => 'Client invoice retrieved successfully',
            'data' => $clientInvoice,
        ], 200);
    }

    /**
     * Update a client invoice record.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {

            $clientInvoice = ClientInvoice::findOrFail($id);

            // Check if the clientPayment belongs to the logged-in user's company
            $user = Auth::user();
            if (!$user->company || $clientInvoice->company_id !== $user->company->id) {
                return response()->json(['error' => 'Client invoice not found or unauthorized'], 404);
            }

            // Validate the request data
            $validatedData = $request->validate(ClientInvoice::validationRules());

//            deduct old amount from client balance
            $client = $clientInvoice->client;
            $client->current_balance -= $clientInvoice->grand_total;

            // Update the client invoice record
            $clientInvoice->update($validatedData);

            // Update or create invoice item records if provided in the request
            $clientInvoice->invoiceItems()->delete();
            if ($request->has('invoice_items') && is_array($request->invoice_items)) {
                foreach ($request->invoice_items as $itemData) {
                    $clientInvoice->invoiceItems()->updateOrCreate(['id' => $itemData['id']], $itemData);
                }
            }

            // Load relationships for the response
            $clientInvoice->load(['vehicle:id,name,model_year,reg_no', 'client:id,name,address,mobile_no', 'driver:id,name,mobile_no', 'invoiceItems']);

            // Update client balance by increasing the grand_total amount of the invoice
            $client->current_balance += $clientInvoice->grand_total;
            $client->save();

            // Update daily basis status
            $dueDate = $clientInvoice->due_date ? Carbon::parse($clientInvoice->due_date) : null;
            $currentDate = Carbon::now();

            if ($dueDate && $currentDate->gt($dueDate)) {
                $dailyBasis = $clientInvoice->dailyBasis;
                $dailyBasis->status = "Payment Overdue";
                $dailyBasis->save();
                $clientInvoice->status = "Payment Overdue";
                $clientInvoice->save();
            } elseif ($clientInvoice->total_paid == 0) {
                $dailyBasis = $clientInvoice->dailyBasis;
                $dailyBasis->status = "Invoice Created & Awaiting Payment";
                $dailyBasis->save();
                $clientInvoice->status = "Created & Awaiting Payment";
                $clientInvoice->save();
            }


            return response()->json([
                'message' => 'Client invoice updated successfully',
                'data' => $clientInvoice,
            ], 200);
        });
    }

    /**
     * Delete a client invoice record.
     *
     * @param int $id
     * @return JsonResponse
     */

    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $clientInvoice = ClientInvoice::findOrFail($id);

            // Check if the clientInvoice belongs to the logged-in user's company
            $user = Auth::user();
            if (!$user->company || $clientInvoice->company_id !== $user->company->id) {
                return response()->json(['error' => 'ClientInvoice not found or unauthorized'], 404);
            }

            // Check if the invoice has any associated payments
            $paymentsCount = $clientInvoice->payments()->count();
            if ($paymentsCount > 0) {
                return response()->json(['error' => 'Cannot delete invoice with associated payments'], 400);
            }

            // Update client balance by deducting the total amount of the invoice
            $client = $clientInvoice->client;
            $client->current_balance -= $clientInvoice->grand_total;
            $client->save();

            // Update daily basis status
            $dailyBasis = $clientInvoice->dailyBasis;
            $dailyBasis->status = "To Make Invoice";
            $dailyBasis->save();

            // Perform the deletion
            $clientInvoice->delete();

            return response()->json(['message' => 'Client invoice deleted successfully'], 200);
        });
    }
}
