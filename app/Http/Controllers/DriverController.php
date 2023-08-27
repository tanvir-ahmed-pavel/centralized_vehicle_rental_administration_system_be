<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $drivers = Driver::orderBy('id', 'desc')->paginate(10);

        return response()->json($drivers, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate(Driver::validationRules());

        $driver = Driver::create($validatedData);

        return response()->json(['message' => 'Driver created successfully', 'data' => $driver], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $driver = Driver::findOrFail($id);
        return response()->json(['data' => $driver],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);

        $validatedData = $request->validate(Driver::validationRules());

        $driver->update($validatedData);

        return response()->json(['message' => 'Driver updated successfully', 'data' => $driver], 200);
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
