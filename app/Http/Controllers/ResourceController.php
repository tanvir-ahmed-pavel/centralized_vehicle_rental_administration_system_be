<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResourceController extends Controller
{
    public function getDriverList()
    {
        $company = Auth::user()->company;

        $drivers = $company->drivers;

        // Map the data to the desired structure
        $mappedData = $drivers->map(function ($driver) {
            return [
                'id' => $driver->id,
                'name' => $driver->name,
                'mobile_no' => $driver->mobile_no,
                'license_number' => $driver->license_number,
                'is_available' => $driver->is_available,
            ];
        });

        return response()->json([
            'message' => 'Driver list retrieved successfully',
            'data' => $mappedData,
        ], 200);
    }

    public function getVehicleList(Request $request)
    {
        $company = Auth::user()->company;

        // Fetch vehicles based on the authenticated user's company and apply sorting
        $vehicles = $company->vehicles()->with(['driver'])->get();

        // Map the data to the desired structure
        if ($request["allAttribute"] == "true"){
            $mappedData = $vehicles->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'name' => $vehicle->name,
                    'reg_no' => $vehicle->reg_no,
                    'vehicle_owner' => $vehicle->vehicle_owner,
                    'fuel_type' => $vehicle->fuel_type,
                    'brand' => $vehicle->brand,
                    'model_year' => $vehicle->model_year,
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
                    'driver_id' => $vehicle->driver_id,
                    'driver' => [
                        'id' => optional($vehicle->driver)->id,
                        'name' => optional($vehicle->driver)->name,
                        'mobile' => optional($vehicle->driver)->mobile_no,
                    ],
                    'is_available' => $vehicle->is_available,
                    'status' => $vehicle->status,
                ];
            });
            return response()->json([
                'message' => 'Vehicle list retrieved successfully',
                'data' => $mappedData,
            ], 200);
        } else{
            $mappedData = $vehicles->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'name' => $vehicle->name,
                    'reg_no' => $vehicle->reg_no,
                    'vehicle_owner' => $vehicle->vehicle_owner,
                    'fuel_type' => $vehicle->fuel_type,
                    'brand' => $vehicle->brand,
                    'model_year' => $vehicle->model_year,
                    'driver_id' => $vehicle->driver_id,
                    'driver' => [
                        'id' => optional($vehicle->driver)->id,
                        'name' => optional($vehicle->driver)->name,
                        'mobile' => optional($vehicle->driver)->mobile_no,
                    ],
                    'is_available' => $vehicle->is_available,
                    'status' => $vehicle->status,
                ];
            });
            return response()->json([
                'message' => 'Vehicle list retrieved successfully',
                'data' => $mappedData,
            ], 200);
        }



    }

    public function getVendorList()
    {
        $company = Auth::user()->company;

        $vendors = $company->vendors;

        // Map the data to the desired structure
        $mappedData = $vendors->map(function ($vendor) {
            return [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'mobile_no' => $vendor->mobile_no,
            ];
        });

        return response()->json([
            'message' => 'Vendor list retrieved successfully',
            'data' => $mappedData,
        ], 200);
    }

    public function getClientList()
    {
        $company = Auth::user()->company;

        $clients = $company->clients;

        // Map the data to the desired structure
        $mappedData = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'name' => $client->name,
                'mobile_no' => $client->mobile_no,
            ];
        });

        return response()->json([
            'message' => 'Client list retrieved successfully',
            'data' => $mappedData,
        ], 200);
    }


}
