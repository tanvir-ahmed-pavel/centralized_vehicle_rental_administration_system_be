<?php

namespace App\Http\Controllers;

use App\Models\ClientInvoice;
use App\Http\Controllers\Controller;
use App\Traits\DataMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientInvoiceController extends Controller
{
    use DataMapping;
    /**
     * Display a listing of the client invoices.
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

        // Fetch client invoices based on the authenticated user's company and apply sorting
        $clientInvoices = $company->clientInvoices()
            ->with(['dailyBasis', 'monthlyContract', 'vehicle', 'client', 'driver', 'invoiceItems'])
            ->orderBy($sortBy, $sortOrder);

        // Filter by dailyBasisId if provided in the query
        if ($request->has('dailyBasisId')) {
            $dailyBasisId = $request->dailyBasisId;

            // Check if the provided dailyBasisId belongs to the company for ownership verification
            $dailyBasis = $company->dailyBases()->find($dailyBasisId);

            if (!$dailyBasis) {
                return response()->json(['error' => 'DailyBasis not found or unauthorized'], 404);
            }

            $clientInvoices->where('daily_basis_id', $dailyBasisId);
        }

        // Filter by status if the 'status' parameter is present in the request
        if ($request->has('status')) {
            $statuses = explode(',', $request->status);
            $clientInvoices->whereIn('status', $statuses);
        }

        // Filter by client_id if the 'client_id' parameter is present in the request
        if ($request->has('client_id')) {
            $client_id = $request->client_id;

            // Check if the provided client_id belongs to the company for ownership verification
            $client = $company->clients()->find($client_id);

            if (!$client) {
                return response()->json(['error' => 'Client not found or unauthorized'], 404);
            }

            $clientInvoices->where('client_id', $client_id);
        }

        $clientInvoices = $clientInvoices->paginate($perPage, ['*'], 'page', $page);

        // Map the data to the desired structure
        $mappedData = $clientInvoices->map(function ($clientInvoice) {
            return [
                'id' => $clientInvoice->id,
                'invoice_date' => $clientInvoice->invoice_date,
                'invoice_number' => $clientInvoice->invoice_number,
                'due_date' => $clientInvoice->due_date,
                'daily_basis_id' => $clientInvoice->daily_basis_id,
                'monthly_contract_id' => $clientInvoice->monthly_contract_id,
                'grand_total' => $clientInvoice->grand_total,
                'total_paid' => $clientInvoice->total_paid,
                'client_id' => $clientInvoice->client_id,
                'client' => [
                    'id' => $clientInvoice->client->id,
                    'name' => $clientInvoice->client->name,
                    'address' => $clientInvoice->client->address,
                    'mobile_no' => $clientInvoice->client->mobile_no,
                ],
                'vehicle_id' => $clientInvoice->vehicle_id,
                'vehicle' => [
                    'id' => $clientInvoice->vehicle->id,
                    'name' => $clientInvoice->vehicle->name,
                    'model' => $clientInvoice->vehicle->model,
                    'reg' => $clientInvoice->vehicle->reg_no,
                ],
                'driver_id' => $clientInvoice->driver_id,
                'driver' => [
                    'id' => $clientInvoice->driver->id,
                    'name' => $clientInvoice->driver->name,
                    'mobile_no' => $clientInvoice->driver->mobile_no,
                ],
                'created_at' => $clientInvoice->created_at,
                'status' => $clientInvoice->status,
            ];
        });

        return response()->json([
            'message' => 'Client invoices retrieved successfully',
            'data' => $this->mapData($clientInvoices, $mappedData),
        ], 200);
    }

    /**
     * Store a newly created client invoice.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
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
            $clientInvoice->load(['dailyBasis', 'monthlyContract', 'vehicle', 'client', 'driver', 'invoiceItems']);

            // Generate invoice number
            $clientInvoice->invoice_number = $clientInvoice->generateInvoiceNumber("Daily", $clientInvoice->client->name, $clientInvoice->id, $clientInvoice->daily_basis_id);
            $clientInvoice->save();

            // Update client balance by increasing the grand_total amount of the invoice
            $client = $clientInvoice->client;
            $client->current_balance += $clientInvoice->grand_total;
            $client->save();

            // Update daily basis status
            $dailyBasis = $clientInvoice->dailyBasis;
            $dailyBasis->status = "Invoice Created & Awaiting Payment";
            $dailyBasis->save();

            // Map the data to the desired structure
            $mappedData = [
                'id' => $clientInvoice->id,
                'invoice_date' => $clientInvoice->invoice_date,
                'due_date' => $clientInvoice->due_date,
                'daily_basis_id' => $clientInvoice->daily_basis_id,
                'monthly_contract_id' => $clientInvoice->monthly_contract_id,
                'client_id' => $clientInvoice->client_id,
                'client' => [
                    'id' => $clientInvoice->client->id,
                    'name' => $clientInvoice->client->name,
                    'address' => $clientInvoice->client->address,
                    'mobile_no' => $clientInvoice->client->mobile_no,
                ],
                'vehicle_id' => $clientInvoice->vehicle_id,
                'vehicle' => [
                    'id' => $clientInvoice->vehicle->id,
                    'name' => $clientInvoice->vehicle->name,
                    'model' => $clientInvoice->vehicle->model,
                    'reg' => $clientInvoice->vehicle->reg_no,
                ],
                'driver_id' => $clientInvoice->driver_id,
                'driver' => [
                    'id' => $clientInvoice->driver->id,
                    'name' => $clientInvoice->driver->name,
                    'mobile_no' => $clientInvoice->driver->mobile_no,
                ],
                'invoice_items' => $clientInvoice->invoiceItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'unit_rate' => $item->unit_rate,
                        'tax_percent' => $item->tax_percent,
                        'vat_percent' => $item->vat_percent,
                        'tax_amount' => $item->tax_amount,
                        'vat_amount' => $item->vat_amount,
                        'total_amount' => $item->total_amount,
                        'remarks' => $item->remarks
                    ];
                }),
                'created_at' => $clientInvoice->created_at,
                'status' => $clientInvoice->status,
            ];

            return response()->json([
                'message' => 'Client invoice created successfully',
                'data' => $mappedData,
            ], 201);
        });
    }

    /**
     * Get the details of a specific client invoice.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
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
        $clientInvoice->load(['dailyBasis', 'monthlyContract', 'vehicle', 'client', 'driver', 'invoiceItems'])
            ->loadCount("payments");

        // Map the data to the desired structure
        $mappedData = [
            'id' => $clientInvoice->id,
            'invoice_date' => $clientInvoice->invoice_date,
            'due_date' => $clientInvoice->due_date,
            'invoice_number' => $clientInvoice->invoice_number,
            'daily_basis_id' => $clientInvoice->daily_basis_id,
            'monthly_contract_id' => $clientInvoice->monthly_contract_id,
            'grand_total' => $clientInvoice->grand_total,
            'total_paid' => $clientInvoice->total_paid,
            'advance_amount' => $clientInvoice->advance_amount,
            'discount_amount' => $clientInvoice->discount_amount,
            'vat_amount' => $clientInvoice->vat_amount,
            'vat_percent' => $clientInvoice->vat_percent,
            'tax_amount' => $clientInvoice->tax_amount,
            'tax_percent' => $clientInvoice->tax_percent,
            'round_adjustment' => $clientInvoice->round_adjustment,
            'round_total' => $clientInvoice->round_total,
            'client_id' => $clientInvoice->client_id,
            'client' => [
                'id' => $clientInvoice->client->id,
                'name' => $clientInvoice->client->name,
                'address' => $clientInvoice->client->address,
                'mobile_no' => $clientInvoice->client->mobile_no,
            ],
            'vehicle_id' => $clientInvoice->vehicle_id,
            'vehicle' => [
                'id' => $clientInvoice->vehicle->id,
                'name' => $clientInvoice->vehicle->name,
                'model' => $clientInvoice->vehicle->model,
                'reg' => $clientInvoice->vehicle->reg_no,
            ],
            'driver_id' => $clientInvoice->driver_id,
            'driver' => [
                'id' => $clientInvoice->driver->id,
                'name' => $clientInvoice->driver->name,
                'mobile_no' => $clientInvoice->driver->mobile_no,
            ],
            'invoice_items' => $clientInvoice->invoiceItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_rate' => $item->unit_rate,
                    'tax_percent' => $item->tax_percent,
                    'vat_percent' => $item->vat_percent,
                    'tax_amount' => $item->tax_amount,
                    'vat_amount' => $item->vat_amount,
                    'total_amount' => $item->total_amount,
                    'remarks' => $item->remarks
                ];
            }),
            'payments_count' => $clientInvoice->payments_count,
            'is_active' => $clientInvoice->is_active,
            'created_at' => $clientInvoice->created_at,
            'status' => $clientInvoice->status,
        ];

        return response()->json([
            'message' => 'Client invoice retrieved successfully',
            'data' => $mappedData,
        ], 200);
    }

    /**
     * Update a client invoice record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        return DB::transaction(function () use ($request,$id) {

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
            $clientInvoice->load(['dailyBasis', 'monthlyContract', 'vehicle', 'client', 'driver', 'invoiceItems']);

            // Update client balance by increasing the grand_total amount of the invoice
            $client->current_balance += $clientInvoice->grand_total;
            $client->save();

            // Update daily basis status
            $dailyBasis = $clientInvoice->dailyBasis;
            $dailyBasis->status = "Invoice Created & Awaiting Payment";
            $dailyBasis->save();

            // Map the data to the desired structure
            $mappedData = [
                'id' => $clientInvoice->id,
                'invoice_date' => $clientInvoice->invoice_date,
                'due_date' => $clientInvoice->due_date,
                'invoice_number' => $clientInvoice->invoice_number,
                'daily_basis_id' => $clientInvoice->daily_basis_id,
                'monthly_contract_id' => $clientInvoice->monthly_contract_id,
                'client_id' => $clientInvoice->client_id,
                'client' => [
                    'id' => $clientInvoice->client->id,
                    'name' => $clientInvoice->client->name,
                    'address' => $clientInvoice->client->address,
                    'mobile_no' => $clientInvoice->client->mobile_no,
                ],
                'vehicle_id' => $clientInvoice->vehicle_id,
                'vehicle' => [
                    'id' => $clientInvoice->vehicle->id,
                    'name' => $clientInvoice->vehicle->name,
                    'model' => $clientInvoice->vehicle->model,
                    'reg' => $clientInvoice->vehicle->reg_no,
                ],
                'driver_id' => $clientInvoice->driver_id,
                'driver' => [
                    'id' => $clientInvoice->driver->id,
                    'name' => $clientInvoice->driver->name,
                    'mobile_no' => $clientInvoice->driver->mobile_no,
                ],
                'invoice_items' => $clientInvoice->invoiceItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'unit_rate' => $item->unit_rate,
                        'tax_percent' => $item->tax_percent,
                        'vat_percent' => $item->vat_percent,
                        'tax_amount' => $item->tax_amount,
                        'vat_amount' => $item->vat_amount,
                        'total_amount' => $item->total_amount,
                        'remarks' => $item->remarks
                    ];
                }),
                'created_at' => $clientInvoice->created_at,
                'status' => $clientInvoice->status,
            ];

            return response()->json([
                'message' => 'Client invoice updated successfully',
                'data' => $mappedData,
            ], 200);
        });
    }

    /**
     * Delete a client invoice record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
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
