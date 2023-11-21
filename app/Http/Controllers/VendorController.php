<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Traits\DataMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorController extends Controller
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

        $vendors = $company->vendors()->orderBy($sortBy, $sortOrder)->paginate($perPage, ['*'], 'page', $page);

        // Map the data to the desired structure
        $mappedData = $vendors->map(function ($vendor) {
            return [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'mobile_no' => $vendor->mobile_no,
                'current_balance' => $vendor->current_balance,
                'vendor_type' => $vendor->vendor_type,
                'company_id' => $vendor->company_id,
                'created_at' => $vendor->created_at,
                'updated_at' => $vendor->updated_at,
            ];
        });

        return response()->json([
            'message' => 'Vendor records retrieved successfully',
            'data' => $this->mapData($vendors, $mappedData),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate(Vehicle::validationRules());

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $company = $user->company;

        if (!$company) {
            return response()->json(['error' => 'Company not found for the user'], 404);
        }

        $vendor = $company->vendors()->create($validatedData);

        return response()->json(['message' => 'Vendor created successfully', 'data' => $vendor], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $vendor = Vendor::findOrFail($id);
        return response()->json(['data' => $vendor], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);

        $validatedData = $request->validate(Vendor::validationRules());

        $vendor->update($validatedData);

        return response()->json(['message' => 'Vendor updated successfully', 'data' => $vendor], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();

        return response()->json(['message' => 'Vendor deleted successfully'], 200);
    }
}
