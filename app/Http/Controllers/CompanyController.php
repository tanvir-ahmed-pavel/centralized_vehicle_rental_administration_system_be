<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Http\Controllers\Controller;
use http\Client\Curl\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use function PHPUnit\Framework\isEmpty;

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
        $company = Auth::user()->company->load(["user"]);


        if (!$company) {
            return response()->json(['error' => 'Company not found or unauthorized'], 404);
        }

        return response()->json([
            'message' => 'Company retrieved successfully',
            'data' => $company
        ],200);
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

        $password = $request->current_password;
        if (Hash::check($password, auth()->user()->password)) {
            $company->update($validatedData);

            if($request->has("new_password") && isEmpty($request->new_password)){
                $validator = Validator::make(['new_password' => $request->new_password], [
                    'new_password' => 'required|string|min:8|confirmed',
                ]);
                if ($validator->passes()){
                    \App\Models\User::findOrFail(Auth::user()->id)->update([
                        'password' => Hash::make($request->password),
                    ]);
                }
            }

            return response()->json(['message' => 'Setting updated successfully', 'data' => $company], 200);

        }

        return response()->json(['error' => "The password doesn't match with our records"], 422);


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
