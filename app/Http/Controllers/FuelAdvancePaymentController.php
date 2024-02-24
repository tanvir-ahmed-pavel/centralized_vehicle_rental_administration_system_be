<?php

namespace App\Http\Controllers;

use App\Models\DailyBasis;
use App\Models\FuelAdvancePayment;
use App\Models\MonthlyContract;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            ])->when($request->has('start_date') && $request->has('end_date'), function ($query) use ($request) {
                return $query->whereBetween('posting_date', [$request->start_date, $request->end_date]);
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        // Calculate sum values
        $fuelAdvanceTotal = $fuelAdvancePayments->sum('amount');
        $totalPaidForFuel = $fuelAdvancePayments->where('payment_type', 'Fuel Payment')->sum('amount');
        $clientFuelAdvanceTotal = $fuelAdvancePayments->where('payment_from', 'Client')->sum('amount');
        $vendorFuelAdvanceTotal = $fuelAdvancePayments->where('payment_from', 'Vendor')->sum('amount');
        $ownFuelAdvanceTotal = $fuelAdvancePayments->where('payment_from', 'Self')->sum('amount');

        return response()->json([
            'message' => 'Fuel advance payments retrieved successfully for the DailyBasis',
            'data' => $fuelAdvancePayments,
            'fuelAdvanceTotal' => $fuelAdvanceTotal,
            'totalPaidForFuel' => $totalPaidForFuel,
            'clientFuelAdvanceTotal' => $clientFuelAdvanceTotal,
            'vendorFuelAdvanceTotal' => $vendorFuelAdvanceTotal,
            'ownFuelAdvanceTotal' => $ownFuelAdvanceTotal,
        ], 200);
    }

    /**
     * Get fuel advance payments by Monthly Contract.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $dailyBasisId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFuelAdvancePaymentsByMonthlyContract(Request $request)
    {
        // Set default values for pagination if not provided in the request
        $page = $request->has('page') ? $request->page : 1;
        $perPage = $request->has('per_page') ? $request->per_page : 10;

        // Set default values for sorting if not provided in the request
        $sortBy = $request->has('sort_by') ? $request->sort_by : 'id';
        $sortOrder = $request->has('sort_order') ? $request->sort_order : 'desc';

        $company = Auth::user()->company;

        // Check if the DailyBasis belongs to the company
        $monthlyContract = $company->monthlyContracts()->find($request->monthly_contract_id);

        if (!$monthlyContract) {
            return response()->json(['error' => 'Monthly Contract not found or unauthorized'], 404);
        }

        // Fetch fuel advance payments for the specified DailyBasis
        $fuelAdvancePayments = $monthlyContract->fuelAdvancePayments()
            ->with([
                'dailyBasis:id,client_id,vehicle_id,driver_id',
                'monthlyContract:id,client_id,vehicle_id,driver_id',
            ])
            ->when($request->has('for_the_month_of') && $request->filled('for_the_month_of'), function ($query) use ($request) {
                if ($request->for_the_month_of === 'null' || $request->for_the_month_of === '') {
                    return $query;
                }

                // Convert the month string to a Carbon instance
                $carbonMonth = Carbon::parse($request->for_the_month_of);

                // Filter records based on the month
                return $query->whereMonth('for_the_month_of', $carbonMonth->month);
            })
            ->when($request->has('start_date') && $request->has('end_date'), function ($query) use ($request) {
                return $query->whereBetween('for_the_month_of', [$request->start_date, $request->end_date]);
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        // Calculate sum values
        $fuelAdvanceTotal = $fuelAdvancePayments->sum('amount');
        $totalPaidForFuel = $fuelAdvancePayments->where('payment_type', 'Fuel Payment')->sum('amount');
        $clientFuelAdvanceTotal = $fuelAdvancePayments->where('payment_from', 'Client')->sum('amount');
        $vendorFuelAdvanceTotal = $fuelAdvancePayments->where('payment_from', 'Vendor')->sum('amount');
        $ownFuelAdvanceTotal = $fuelAdvancePayments->where('payment_from', 'Self')->sum('amount');

        return response()->json([
            'message' => 'Fuel advance payments retrieved successfully for the DailyBasis',
            'data' => $fuelAdvancePayments,
            'fuelAdvanceTotal' => $fuelAdvanceTotal,
            'totalPaidForFuel' => $totalPaidForFuel,
            'clientFuelAdvanceTotal' => $clientFuelAdvanceTotal,
            'vendorFuelAdvanceTotal' => $vendorFuelAdvanceTotal,
            'ownFuelAdvanceTotal' => $ownFuelAdvanceTotal,
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
            $validatedData = $request->validate(FuelAdvancePayment::validationRules());

            $user = Auth::user();

            if (!$user || !($company = $user->company)) {
                return response()->json(['error' => 'User or company not found'], 404);
            }

            $dailyBasisId = $request->input('daily_basis_id');
            $monthlyContractId = $request->input('monthly_contract_id');

            // Check if it's a daily basis or monthly contract
            if ($dailyBasisId) {
                $dailyBasis = DailyBasis::find($dailyBasisId);
                // Check if the daily basis exists and has client invoices
                if ($dailyBasis && $dailyBasis->clientInvoices()->exists()) {
                    return response()->json(['error' => 'Cannot create fuel advance payment, the daily basis already has invoices'], 422);
                }
                // Since we have a daily basis, create the fuel advance payment
                $paymentType = 'Daily';
                $fuelAdvancePayment = $company->fuelAdvancePayments()->create($validatedData);
            } elseif ($monthlyContractId) {
                $monthlyContract = MonthlyContract::find($monthlyContractId);
                // Check if the monthly contract exists and has any invoices for the same month
                $fuelMonth = Carbon::parse($validatedData['for_the_month_of'])->format('m');
                $hasInvoices = $monthlyContract->clientInvoices()->whereMonth('for_the_month_of', $fuelMonth)->exists()
                    || $monthlyContract->vendorInvoices()->whereMonth('for_the_month_of', $fuelMonth)->exists()
                    || $monthlyContract->driverInvoices()->whereMonth('for_the_month_of', $fuelMonth)->exists();

                if ($hasInvoices) {
                    return response()->json(['error' => 'Cannot create fuel advance payment for selected month, this month has associated invoice'], 422);
                }
                // Since we have a monthly contract, create the fuel advance payment
                $paymentType = 'Monthly';
                $fuelAdvancePayment = $company->fuelAdvancePayments()->create($validatedData);
            } else {
                return response()->json(['error' => 'Invalid request, please provide either daily_basis_id or monthly_contract_id'], 422);
            }

            if (!$fuelAdvancePayment) {
                return response()->json(['error' => 'Fuel advance payment creation failed'], 500);
            }

            // Generate payment number and save the fuel advance payment
            $fuelAdvancePayment->payment_number = $fuelAdvancePayment->generatePaymentNumber($paymentType, $fuelAdvancePayment->id, $fuelAdvancePayment->payment_type, $fuelAdvancePayment->payment_from);
            $fuelAdvancePayment->save();
            $fuelAdvancePayment->generateTransactions();

            // Update client balance if payment is from client
            if ($validatedData['payment_from'] == 'Client') {
                $client = $fuelAdvancePayment->client;
                $client->current_balance -= $fuelAdvancePayment->amount;
                $client->save();
            }

            // Update vendor or driver balance
            if ($request->filled("vendor_id")) {
                $vendor = $fuelAdvancePayment->vendor;
                $vendor->current_balance -= $fuelAdvancePayment->amount;
                $vendor->save();
            } elseif ($request->filled("driver_id")) {
                $driver = $fuelAdvancePayment->driver;
                $driver->current_balance -= $fuelAdvancePayment->amount;
                $driver->save();
            }

            // Build response data
            $fuelAdvancePayments = $fuelAdvancePayment->dailyBasis ? $fuelAdvancePayment->dailyBasis->fuelAdvancePayments()->get() : $fuelAdvancePayment->monthlyContract->fuelAdvancePayments()->get();
            $fuelAdvanceTotal = $fuelAdvancePayments->sum('amount');
            $totalPaidForFuel = $fuelAdvancePayments->where('payment_type', 'Fuel Payment')->sum('amount');
            $clientFuelAdvanceTotal = $fuelAdvancePayments->where('payment_from', 'Client')->sum('amount');
            $vendorFuelAdvanceTotal = $fuelAdvancePayments->where('payment_from', 'Vendor')->sum('amount');
            $ownFuelAdvanceTotal = $fuelAdvancePayments->where('payment_from', 'Self')->sum('amount');

            return response()->json([
                'message' => 'Fuel advance payments created successfully',
                'data' => $fuelAdvancePayment,
                'fuelAdvanceTotal' => $fuelAdvanceTotal,
                'totalPaidForFuel' => $totalPaidForFuel,
                'clientFuelAdvanceTotal' => $clientFuelAdvanceTotal,
                'vendorFuelAdvanceTotal' => $vendorFuelAdvanceTotal,
                'ownFuelAdvanceTotal' => $ownFuelAdvanceTotal,
            ], 200);
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

        $dailyBasis = DailyBasis::find($fuelAdvancePayment->daily_basis_id);

        if (!$dailyBasis) {
            return response()->json(['error' => 'Daily Basis not found for the payment'], 404);
        }

        // Check if the fuelAdvancePayment belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $fuelAdvancePayment->company_id !== $user->company->id) {
            return response()->json(['error' => 'FuelAdvancePayment not found or unauthorized'], 404);
        }

        $fuelAdvancePayment->load([
            'dailyBasis:id,client_id,vehicle_id,driver_id',
            'monthlyContract:id,client_id,vehicle_id,driver_id',
        ]);

        $fuelAdvancePayments = $dailyBasis->fuelAdvancePayments()->get();
        $fuelAdvanceTotal = $fuelAdvancePayments->sum('amount');
        $totalPaidForFuel = $fuelAdvancePayments->where('payment_type', 'Fuel Payment')->sum('amount');
        $clientFuelAdvanceTotal = $fuelAdvancePayments->where('payment_from', 'Client')->sum('amount');
        $vendorFuelAdvanceTotal = $fuelAdvancePayments->where('payment_from', 'Vendor')->sum('amount');
        $ownFuelAdvanceTotal = $fuelAdvancePayments->where('payment_from', 'Self')->sum('amount');

        return response()->json([
            'message' => 'Fuel advance payments retrieved successfully for the DailyBasis',
            'data' => $fuelAdvancePayment,
            'fuelAdvanceTotal' => $fuelAdvanceTotal,
            'totalPaidForFuel' => $totalPaidForFuel,
            'clientFuelAdvanceTotal' => $clientFuelAdvanceTotal,
            'vendorFuelAdvanceTotal' => $vendorFuelAdvanceTotal,
            'ownFuelAdvanceTotal' => $ownFuelAdvanceTotal,
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
            $fuelAdvancePayment = FuelAdvancePayment::with([
                'dailyBasis.clientInvoices',
                'dailyBasis.vendorInvoices',
                'dailyBasis.driverInvoices',
                'monthlyContract.clientInvoices',
                'monthlyContract.vendorInvoices',
                'monthlyContract.driverInvoices',
            ])->findOrFail($id);

            $hasInvoices = false;

            if ($fuelAdvancePayment->dailyBasis()->exists()) {
                $hasInvoices = $fuelAdvancePayment->dailyBasis->clientInvoices()->exists()
                    || $fuelAdvancePayment->dailyBasis->vendorInvoices()->exists()
                    || $fuelAdvancePayment->dailyBasis->driverInvoices()->exists();
            } elseif ($fuelAdvancePayment->monthlyContract()->exists()) {
                $monthlyContract = $fuelAdvancePayment->monthlyContract()->first();

                $fuelMonth = Carbon::parse($fuelAdvancePayment->for_the_month_of)->format('m');

                $hasInvoices = $monthlyContract->clientInvoices()
                        ->whereMonth('for_the_month_of', $fuelMonth)
                        ->exists()
                    || $monthlyContract->vendorInvoices()
                        ->whereMonth('for_the_month_of', $fuelMonth)
                        ->exists()
                    || $monthlyContract->driverInvoices()
                        ->whereMonth('for_the_month_of', $fuelMonth)
                        ->exists();
            }

            if ($hasInvoices) {
                return response()->json(['error' => 'Cannot delete fuel advance payment, it has associated invoices'], 422);
            }

            // Update client balance
            if ($fuelAdvancePayment->payment_from == "Client" && $fuelAdvancePayment->client) {
                $client = $fuelAdvancePayment->client;
                $client->current_balance += $fuelAdvancePayment->amount;
                $client->save();
            }

            // Update driver or vendor balance
            if ($fuelAdvancePayment->vendor_id && $fuelAdvancePayment->vendor) {
                $vendor = $fuelAdvancePayment->vendor;
                $vendor->current_balance += $fuelAdvancePayment->amount;
                $vendor->save();
            } elseif ($fuelAdvancePayment->driver_id && $fuelAdvancePayment->driver) {
                $driver = $fuelAdvancePayment->driver;
                $driver->current_balance += $fuelAdvancePayment->amount;
                $driver->save();
            }

            $fuelAdvancePayment->delete();

            $fuelAdvancePayments = $fuelAdvancePayment->dailyBasis()->exists() ? $fuelAdvancePayment->dailyBasis->fuelAdvancePayments()->get() : $fuelAdvancePayment->monthlyContract->fuelAdvancePayments()->get();
            $fuelAdvanceTotal = $fuelAdvancePayments->sum('amount');
            $totalPaidForFuel = $fuelAdvancePayments->where('payment_type', 'Fuel Payment')->sum('amount');
            $clientFuelAdvanceTotal = $fuelAdvancePayments->where('payment_from', 'Client')->sum('amount');
            $vendorFuelAdvanceTotal = $fuelAdvancePayments->where('payment_from', 'Vendor')->sum('amount');
            $ownFuelAdvanceTotal = $fuelAdvancePayments->where('payment_from', 'Self')->sum('amount');

            return response()->json([
                'message' => 'Fuel advance payment record deleted successfully',
                'fuelAdvanceTotal' => $fuelAdvanceTotal,
                'totalPaidForFuel' => $totalPaidForFuel,
                'clientFuelAdvanceTotal' => $clientFuelAdvanceTotal,
                'vendorFuelAdvanceTotal' => $vendorFuelAdvanceTotal,
                'ownFuelAdvanceTotal' => $ownFuelAdvanceTotal,
            ], 200);
        });
    }

}
