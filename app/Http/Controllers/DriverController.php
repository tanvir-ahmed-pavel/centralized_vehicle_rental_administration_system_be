<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Traits\DataMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
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

        $drivers = $company->drivers()->with(['vendor'])->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

        // Map the data to the desired structure
        $mappedData = $drivers->map(function ($driver) {
            return [
                'id' => $driver->id,
                'name' => $driver->name,
                'mobile_no' => $driver->mobile_no,
                'license_number' => $driver->license_number,
                'is_available' => $driver->is_available,
                'vendor_id' => $driver->vendeor_id,
                'driver_type' => $driver->driver_type,
                'current_balance' => $driver->current_balance,
                'vendor' => [
                    'id' => optional($driver->vendor)->id,
                    'name' => optional($driver->vendor)->name,
                ],
                'company_id' => $driver->company_id,
                'created_at' => $driver->created_at,
                'updated_at' => $driver->updated_at,
            ];
        });

        return response()->json([
            'message' => 'Driver records retrieved successfully',
            'data' => $this->mapData($drivers, $mappedData),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate(Driver::validationRules());

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $company = $user->company;

        if (!$company) {
            return response()->json(['error' => 'Company not found for the user'], 404);
        }

        $driver = $company->drivers()->create($validatedData);
        $driver->with(["vendor"]);

        $mappedData = [
            'id' => $driver->id,
            'name' => $driver->name,
            'mobile_no' => $driver->mobile_no,
            'license_number' => $driver->license_number,
            'is_available' => $driver->is_available,
            'vendor_id' => $driver->vendeor_id,
            'vendor' => [
                'id' => optional($driver->vendor)->id,
                'name' => optional($driver->vendor)->name,
            ],
            'company_id' => $driver->company_id,
            'created_at' => $driver->created_at,
            'updated_at' => $driver->updated_at,
        ];

        return response()->json(['message' => 'Driver created successfully', 'data' => $mappedData], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $company = Auth::user()->company;

        // Check if the client belongs to the company
        $driver = $company->drivers()->find($id);

        if (!$driver) {
            // Driver not found
            return response()->json(['error' => 'Driver not found or unauthorized'], 404);
        }

        // Sum all the invoice amounts for the client
        $lifetimeBilled = $driver->invoices()->sum('grand_total');

        // Sum all the payments made by the client
        $lifetimePaid = $driver->payments()->sum('amount');
        $lifetimePaid += $driver->fuelAdvancePayments()->sum('amount');


        // Add the lifetime billed and lifetime paid to the client data
        $driver->lifetime_billed = $lifetimeBilled;
        $driver->lifetime_paid = $lifetimePaid;

        $driver->loadCount(["dailyBases", "invoices", "payments"]);

        return response()->json([
            'message' => 'Driver retrieved successfully',
            'data' => $driver
        ],200);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);

        $validatedData = $request->validate(Driver::validationRules());

        $driver->update($validatedData);
        $driver->with(["vendor"]);

        $mappedData = [
            'id' => $driver->id,
            'name' => $driver->name,
            'mobile_no' => $driver->mobile_no,
            'license_number' => $driver->license_number,
            'is_available' => $driver->is_available,
            'vendor_id' => $driver->vendeor_id,
            'vendor' => [
                'id' => optional($driver->vendor)->id,
                'name' => optional($driver->vendor)->name,
            ],
            'company_id' => $driver->company_id,
            'created_at' => $driver->created_at,
            'updated_at' => $driver->updated_at,
        ];

        return response()->json(['message' => 'Driver updated successfully', 'data' => $mappedData], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->delete();

        return response()->json(['message' => 'Driver deleted successfully'], 200);
    }
}
