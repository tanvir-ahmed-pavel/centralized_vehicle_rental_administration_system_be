<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Http\Controllers\Controller;
use App\Traits\DataMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller
{
    use DataMapping;

    /**
     * Display a listing of the resource.
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

        // Fetch vehicles based on the authenticated user's company and apply sorting
        $vehicles = $company->vehicles()
            ->with(['vendor', 'driver'])
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        // Map the data to the desired structure
        $mappedData = $vehicles->map(function ($vehicle) {
            return [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'reg_no' => $vehicle->reg_no,
                'vehicle_owner' => $vehicle->vehicle_owner,
                'fuel_type' => $vehicle->fuel_type,
                'brand' => $vehicle->brand,
                'model_year' => $vehicle->model_year,
                'engine_cc' => $vehicle->engine_cc,
                'no_of_seat' => $vehicle->no_of_seat,
                'per_km_rate' => $vehicle->per_km_rate,
                'body_rent_per_day' => $vehicle->body_rent_per_day,
                'package_rent_per_day' => $vehicle->package_rent_per_day,
                'package_km_limit_per_day' => $vehicle->package_km_limit_per_day,
                'lunch_per_day' => $vehicle->lunch_per_day,
                'dinner_per_day' => $vehicle->dinner_per_day,
                'ot_per_hour' => $vehicle->ot_per_hour,
                'tour_allowance_per_night' => $vehicle->tour_allowance_per_night,
                'vendor_per_km_rate' => $vehicle->vendor_per_km_rate,
                'vendor_body_rent_per_day' => $vehicle->vendor_body_rent_per_day,
                'vendor_package_rent_per_day' => $vehicle->vendor_package_rent_per_day,
                'vendor_package_km_limit_per_day' => $vehicle->vendor_package_km_limit_per_day,
                'vendor_lunch_per_day' => $vehicle->vendor_lunch_per_day,
                'vendor_dinner_per_day' => $vehicle->vendor_dinner_per_day,
                'vendor_ot_per_hour' => $vehicle->vendor_ot_per_hour,
                'vendor_tour_allowance_per_night' => $vehicle->vendor_tour_allowance_per_night,
                'vendor_id' => $vehicle->vendor_id,
                'vendor' => [
                    'id' => optional($vehicle->vendor)->id,
                    'name' => optional($vehicle->vendor)->name,
                ],
                'driver_id' => $vehicle->driver_id,
                'driver' => [
                    'id' => optional($vehicle->driver)->id,
                    'name' => optional($vehicle->driver)->name,
                    'mobile' => optional($vehicle->driver)->mobile_no,
                ],
                'is_available' => $vehicle->is_available,
                'status' => $vehicle->status,
                'created_at' => $vehicle->created_at,
                'updated_at' => $vehicle->updated_at,
            ];
        });

        return response()->json([
            'message' => 'Vehicle records retrieved successfully',
            'data' => $this->mapData($vehicles, $mappedData),
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

        $vehicle = $company->vehicles()->create($validatedData);

        $vehicle->with(['vendor', 'driver']);

        $mappedData =[
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'reg_no' => $vehicle->reg_no,
                'vehicle_owner' => $vehicle->vehicle_owner,
                'fuel_type' => $vehicle->fuel_type,
                'brand' => $vehicle->brand,
                'model_year' => $vehicle->model_year,
                'engine_cc' => $vehicle->engine_cc,
                'no_of_seat' => $vehicle->no_of_seat,
                'per_km_rate' => $vehicle->per_km_rate,
                'body_rent_per_day' => $vehicle->body_rent_per_day,
                'package_rent_per_day' => $vehicle->package_rent_per_day,
                'package_km_limit_per_day' => $vehicle->package_km_limit_per_day,
                'lunch_per_day' => $vehicle->lunch_per_day,
                'dinner_per_day' => $vehicle->dinner_per_day,
                'ot_per_hour' => $vehicle->ot_per_hour,
                'tour_allowance_per_night' => $vehicle->tour_allowance_per_night,
                'vendor_per_km_rate' => $vehicle->vendor_per_km_rate,
                'vendor_body_rent_per_day' => $vehicle->vendor_body_rent_per_day,
                'vendor_package_rent_per_day' => $vehicle->vendor_package_rent_per_day,
                'vendor_package_km_limit_per_day' => $vehicle->vendor_package_km_limit_per_day,
                'vendor_lunch_per_day' => $vehicle->vendor_lunch_per_day,
                'vendor_dinner_per_day' => $vehicle->vendor_dinner_per_day,
                'vendor_ot_per_hour' => $vehicle->vendor_ot_per_hour,
                'vendor_tour_allowance_per_night' => $vehicle->vendor_tour_allowance_per_night,
                'vendor_id' => $vehicle->vendor_id,
                'vendor' => [
                    'id' => optional($vehicle->vendor)->id,
                    'name' => optional($vehicle->vendor)->name,
                ],
                'driver_id' => $vehicle->driver_id,
                'driver' => [
                    'id' => optional($vehicle->driver)->id,
                    'name' => optional($vehicle->driver)->name,
                    'mobile' => optional($vehicle->driver)->mobile_no,
                ],
                'is_available' => $vehicle->is_available,
                'status' => $vehicle->status,
                'created_at' => $vehicle->created_at,
                'updated_at' => $vehicle->updated_at,
            ];

        return response()->json(['message' => 'Vehicle created successfully', 'data' => $mappedData], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        return response()->json(['data' => $vehicle], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $validatedData = $request->validate(Vehicle::validationRules());

        $vehicle->update($validatedData);

        $vehicle->with(['vendor', 'driver']);

        $mappedData =[
            'id' => $vehicle->id,
            'name' => $vehicle->name,
            'reg_no' => $vehicle->reg_no,
            'vehicle_owner' => $vehicle->vehicle_owner,
            'fuel_type' => $vehicle->fuel_type,
            'brand' => $vehicle->brand,
            'model_year' => $vehicle->model_year,
            'engine_cc' => $vehicle->engine_cc,
            'no_of_seat' => $vehicle->no_of_seat,
            'per_km_rate' => $vehicle->per_km_rate,
            'body_rent_per_day' => $vehicle->body_rent_per_day,
            'package_rent_per_day' => $vehicle->package_rent_per_day,
            'package_km_limit_per_day' => $vehicle->package_km_limit_per_day,
            'lunch_per_day' => $vehicle->lunch_per_day,
            'dinner_per_day' => $vehicle->dinner_per_day,
            'ot_per_hour' => $vehicle->ot_per_hour,
            'tour_allowance_per_night' => $vehicle->tour_allowance_per_night,
            'vendor_per_km_rate' => $vehicle->vendor_per_km_rate,
            'vendor_body_rent_per_day' => $vehicle->vendor_body_rent_per_day,
            'vendor_package_rent_per_day' => $vehicle->vendor_package_rent_per_day,
            'vendor_package_km_limit_per_day' => $vehicle->vendor_package_km_limit_per_day,
            'vendor_lunch_per_day' => $vehicle->vendor_lunch_per_day,
            'vendor_dinner_per_day' => $vehicle->vendor_dinner_per_day,
            'vendor_ot_per_hour' => $vehicle->vendor_ot_per_hour,
            'vendor_tour_allowance_per_night' => $vehicle->vendor_tour_allowance_per_night,
            'vendor_id' => $vehicle->vendor_id,
            'vendor' => [
                'id' => optional($vehicle->vendor)->id,
                'name' => optional($vehicle->vendor)->name,
            ],
            'driver_id' => $vehicle->driver_id,
            'driver' => [
                'id' => optional($vehicle->driver)->id,
                'name' => optional($vehicle->driver)->name,
                'mobile' => optional($vehicle->driver)->mobile_no,
            ],
            'is_available' => $vehicle->is_available,
            'status' => $vehicle->status,
            'created_at' => $vehicle->created_at,
            'updated_at' => $vehicle->updated_at,
        ];

        return response()->json(['message' => 'Vehicle updated successfully', 'data' => $mappedData], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted successfully'], 200);
    }
}
