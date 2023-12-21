<?php

namespace App\Http\Controllers;

use App\Models\DailyBasis;
use App\Models\FuelAdvancePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FuelAdvancePaymentController extends Controller
{
    /**
     * Get a paginated list of fuel advance payment records.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
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

        // Fetch fuel advance payments based on the authenticated user's company and apply sorting
        $fuelAdvancePayments = $company->fuelAdvancePayments()
            ->with([
                'dailyBasis:id,client_id,vehicle_id,driver_id',
                'monthlyContract:id,client_id,vehicle_id,driver_id',
                'client:id,name',
                'vendor:id,name',
                'driver:id,name',
                'vehicle:id,name,model,reg_no',
                'chartOfAccount:id,name',
            ])
            ->when($request->has('client_id'), function ($query) use ($request, $company) {
                // Filter by client_id if the 'client_id' parameter is present in the request
                $client_id = $request->client_id;

                // Check if the provided client_id belongs to the company for ownership verification
                $client = $company->clients()->find($client_id);

                if (!$client) {
                    return response()->json(['error' => 'Client not found or unauthorized'], 404);
                }

                return $query->where('client_id', $client_id);
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Fuel advance payments retrieved successfully',
            'data' => $fuelAdvancePayments,
        ], 200);
    }

    /**
     * Get fuel advance payments by DailyBasis.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $dailyBasisId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFuelAdvancePaymentsByDailyBasis(Request $request)
    {
        // Set default values for pagination if not provided in the request
        $page = $request->has('page') ? $request->page : 1;
        $perPage = $request->has('per_page') ? $request->per_page : 10;

        // Set default values for sorting if not provided in the request
        $sortBy = $request->has('sort_by') ? $request->sort_by : 'id';
        $sortOrder = $request->has('sort_order') ? $request->sort_order : 'desc';

        $company = Auth::user()->company;

        // Check if the DailyBasis belongs to the company
        $dailyBasis = $company->dailyBases()->find($request->daily_basis_id);

        if (!$dailyBasis) {
            return response()->json(['error' => 'Daily Basis not found or unauthorized'], 404);
        }

        // Fetch fuel advance payments for the specified DailyBasis
        $fuelAdvancePayments = $dailyBasis->fuelAdvancePayments()
            ->with([
                'dailyBasis:id,client_id,vehicle_id,driver_id',
                'monthlyContract:id,client_id,vehicle_id,driver_id',
            ])
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'Fuel advance payments retrieved successfully for the DailyBasis',
            'data' => $fuelAdvancePayments,
        ], 200);
    }

    /**
     * Create a new fuel advance payment record.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            // Validate the request data
            $validatedData = $request->validate(FuelAdvancePayment::validationRules());

            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $company = $user->company;

            if (!$company) {
                return response()->json(['error' => 'Company not found for the user'], 404);
            }

            // Check if the related daily basis has an invoice
            $dailyBasis = DailyBasis::find($validatedData['daily_basis_id']);

            if (!$dailyBasis) {
                return response()->json(['error' => 'Daily Basis not found for the payment'], 404);
            }

            // Check if there are any associated invoices
            if ($dailyBasis->clientInvoices()->exists()) {
                return response()->json(['error' => 'Cannot create fuel advance payment, the daily basis already has invoices'], 422);
            }

            // Create the fuel advance payment record
            $fuelAdvancePayment = $company->fuelAdvancePayments()->create($validatedData);

            $fuelAdvancePayment->payment_number = $fuelAdvancePayment->generatePaymentNumber("Daily", $fuelAdvancePayment->id);
            $fuelAdvancePayment->save();

            // Additional logic or updates can be added here

            return response()->json([
                'message' => 'Fuel advance payment record created successfully',
                'data' => $fuelAdvancePayment,
            ], 201);
        });
    }

    /**
     * Get the details of a specific fuel advance payment record.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $fuelAdvancePayment = FuelAdvancePayment::findOrFail($id);

        // Check if the fuelAdvancePayment belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $fuelAdvancePayment->company_id !== $user->company->id) {
            return response()->json(['error' => 'FuelAdvancePayment not found or unauthorized'], 404);
        }

        $fuelAdvancePayment->load([
            'dailyBasis:id,client_id,vehicle_id,driver_id',
            'monthlyContract:id,client_id,vehicle_id,driver_id',
            'client:id,name',
            'vendor:id,name',
            'driver:id,name',
            'vehicle:id,name,model,reg_no',
            'chartOfAccount:id,name',
        ]);

        return response()->json([
            'message' => 'Fuel advance payment retrieved successfully',
            'data' => $fuelAdvancePayment,
        ], 200);
    }

    /**
     * Update a fuel advance payment record.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $company = Auth::user()->company;
        $fuelAdvancePayment = $company->fuelAdvancePayments()->findOrFail($id);

        $validatedData = $request->validate(FuelAdvancePayment::validationRules());

        $fuelAdvancePayment->update($validatedData);

        $fuelAdvancePayment->load([
            'dailyBasis:id,client_id,vehicle_id,driver_id',
            'monthlyContract:id,client_id,vehicle_id,driver_id',
            'client:id,name',
            'vendor:id,name',
            'driver:id,name',
            'vehicle:id,name,model,reg_no',
            'chartOfAccount:id,name',
        ]);

        return response()->json([
            'message' => 'Fuel advance payment record updated successfully',
            'data' => $fuelAdvancePayment,
        ], 200);
    }

    /**
     * Delete a fuel advance payment record.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $fuelAdvancePayment = FuelAdvancePayment::findOrFail($id);


            // Check if there are any associated invoices
            if ($fuelAdvancePayment->dailyBasis->clientInvoices()->exists()) {
                return response()->json(['error' => 'Cannot delete fuel advance payment, it has associated invoices'], 422);
            }

            $fuelAdvancePayment->delete();

            return response()->json(['message' => 'Fuel advance payment record deleted successfully'], 200);
        });
    }
}
