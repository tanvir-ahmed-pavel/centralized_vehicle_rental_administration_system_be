<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vendors = Vendor::orderBy('id', 'desc')->paginate(10);

        return response()->json($vendors, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate(Vendor::validationRules());

        $vendor = Vendor::create($validatedData);

        return response()->json(['message' => 'Vendor created successfully', 'data' => $vendor], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $vendor = Vendor::findOrFail($id);
        return response()->json(['vendor' => $vendor]);
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
