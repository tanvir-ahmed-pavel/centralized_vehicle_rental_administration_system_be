<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Get a paginated list of company records.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $companies = Company::orderBy('id', 'desc')->paginate(10);
        return response()->json($companies, 200);
    }

    /**
     * Create a new company record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate(Company::validationRules());

        $company = Company::create($validatedData);

        return response()->json(['message' => 'Company record created successfully', 'data' => $company], 201);
    }

    /**
     * Get the details of a specific company record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $company = Company::findOrFail($id);
        return response()->json(['data' => $company]);
    }

    /**
     * Update a company record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $validatedData = $request->validate(Company::validationRules());

        $company->update($validatedData);

        return response()->json(['message' => 'Company record updated successfully', 'data' => $company], 200);
    }

    /**
     * Delete a company record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->json(['message' => 'Company record deleted successfully'], 200);
    }
}
