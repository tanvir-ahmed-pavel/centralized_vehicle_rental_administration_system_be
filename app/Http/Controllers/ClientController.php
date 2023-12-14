<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Http\Controllers\Controller;
use App\Models\ClientInvoice;
use App\Traits\DataMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    use DataMapping;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Set default values for pagination if not provided in the request
        $page = $request->has('page') ? $request->page : 1;
        $perPage = $request->has('per_page') ? $request->per_page : 10;

        // Set default values for sorting if not provided in the request
        $sortBy = $request->has('sort_by') ? $request->sort_by : 'id';
        $sortOrder = $request->has('sort_order') ? $request->sort_order : 'desc';

        $company = Auth::user()->company;

        $clients = $company->clients()->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

        // Map the data to the desired structure
        $mappedData = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
                'mobile_no' => $client->mobile_no,
                'current_balance' => $client->current_balance,
                'client_type' => $client->client_type,
                'company_id' => $client->company_id,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at,
            ];
        });

        return response()->json([
            'message' => 'Client records retrieved successfully',
            'data' => $this->mapData($clients, $mappedData),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate(Client::validationRules());

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $company = $user->company;

        if (!$company) {
            return response()->json(['error' => 'Company not found for the user'], 404);
        }

        $client = $company->clients()->create($validatedData);

        $mappedData = [
            'id' => $client->id,
            'name' => $client->name,
            'mobile_no' => $client->mobile_no,
            'current_balance' => $client->current_balance,
            'client_type' => $client->client_type,
            'company_id' => $client->company_id,
            'created_at' => $client->created_at,
            'updated_at' => $client->updated_at,
        ];

        return response()->json(['message' => 'Client created successfully', 'data' => $mappedData], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $company = Auth::user()->company;

        // Check if the client belongs to the company
        $client = $company->clients()->find($id);

        if (!$client) {
            // Client not found
            return response()->json(['error' => 'Client not found or unauthorized'], 404);
        }

        // Sum all the invoice amounts for the client
        $lifetimeBilled = $client->invoices()->sum('grand_total');

        // Sum all the payments made by the client
        $lifetimePaid = $client->payments()->sum('amount');

        // Add the lifetime billed and lifetime paid to the client data
        $client->lifetime_billed = $lifetimeBilled;
        $client->lifetime_paid = $lifetimePaid;

        $client->loadCount(["dailyBases", "invoices", "payments"]);

        return response()->json([
            'message' => 'Client retrieved successfully',
            'data' => $client
        ],200);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);

        $validatedData = $request->validate(Client::validationRules());

        $client->update($validatedData);

        $mappedData = [
            'id' => $client->id,
            'name' => $client->name,
            'mobile_no' => $client->mobile_no,
            'current_balance' => $client->current_balance,
            'client_type' => $client->client_type,
            'company_id' => $client->company_id,
            'created_at' => $client->created_at,
            'updated_at' => $client->updated_at,
        ];

        return response()->json(['message' => 'Client updated successfully', 'data' => $mappedData], 200);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();

        return response()->json(['message' => 'Client deleted successfully'], 200);
    }
}
