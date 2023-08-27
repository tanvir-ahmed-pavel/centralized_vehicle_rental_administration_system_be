<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vehicles = Vehicle::orderBy('id', 'desc')->paginate(10);

        return response()->json($vehicles, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate(Vehicle::validationRules());

        $vehicle = Vehicle::create($validatedData);

        return response()->json(['message' => 'Vehicle created successfully', 'data' => $vehicle], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        return response()->json(['vehicle' => $vehicle]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);

        $validatedData = $request->validate(Vehicle::validationRules());

        $vehicle->update($validatedData);

        return response()->json(['message' => 'Vehicle updated successfully', 'data' => $vehicle], 200);
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
