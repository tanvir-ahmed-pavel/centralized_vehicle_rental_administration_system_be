<?php

namespace App\Http\Controllers;

use App\Models\MonthlyContract;
use App\Http\Controllers\Controller;
use App\Traits\DataMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MonthlyContractController extends Controller
{
    use DataMapping;
    /**
     * Get a paginated list of monthly contract records.
     *
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

        // Fetch monthly contract records based on the authenticated user's company and apply sorting
        $monthlyContracts = $company->monthlyContracts()->with(['client:id,name,address,city,country,mobile_no,email',
            'vehicle:id,name,model_year as model,reg_no as reg',
            'driver:id,name,mobile_no,email', 'contractPeriod',
            'vendor:id,name,address,city,country,mobile_no,email'])
            ->withCount("clientInvoices", "driverInvoices", "vendorInvoices", "fuelAdvancePayments")
            ->when($request->has('status'), function ($query) use ($request) {
                // Filter by status if the 'status' parameter is present in the request
                $statuses = explode(',', $request->status);
                return $query->whereIn('status', $statuses);
            })
            ->when($request->has('client_id'), function ($query) use ($request) {
                // Filter by client_id if the 'client_id' parameter is present in the request
                $client_id = $request->client_id;

                // Check if the provided client_id belongs to the company for ownership verification
                $client = $query->first()->company->clients()->find($client_id);

                if (!$client) {
                    return response()->json(['error' => 'Client not found or unauthorized'], 404);
                }

                return $query->where('client_id', $client_id);
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        // Map the data to the desired structure
//        $mappedData = $monthlyContracts->map(function ($monthlyContract) {
//            return [
//                'id' => $monthlyContract->id,
//                'monthly_contract_number' => $monthlyContract->monthly_contract_number,
//                'client_id' => $monthlyContract->client_id,
//                'client' => [
//                    'id' => $monthlyContract->client->id,
//                    'name' => $monthlyContract->client->name,
//                ],
//                'vendor_id' => $monthlyContract->vendor_id,
//                'vendor' => [
//                    'id' => optional($monthlyContract->vendor)->id,
//                    'name' => optional($monthlyContract->vendor)->name,
//                ],
//                'vehicle_id' => $monthlyContract->vehicle_id,
//                'vehicle' => [
//                    'id' => $monthlyContract->vehicle->id,
//                    'name' => $monthlyContract->vehicle->name,
//                    'model_year' => $monthlyContract->vehicle->model_year,
//                    'reg' => $monthlyContract->vehicle->reg_no,
//                ],
//                'driver_id' => $monthlyContract->driver_id,
//                'driver' => [
//                    'id' => $monthlyContract->driver->id,
//                    'name' => $monthlyContract->driver->name,
//                    'mobile' => $monthlyContract->driver->mobile_no,
//                ],
//                'duty_dates' => $monthlyContract->contractPeriod->map(function ($contractPeriod) {
//                    return [
//                        'id' => $contractPeriod->id,
//                        'start_date' => $contractPeriod->start_date,
//                        'end_date' => $contractPeriod->end_date,
//                        'is_half_day' => $contractPeriod->is_half_day,
//                    ];
//                }),
//                'client_invoices_count' => $monthlyContract->client_invoices_count,
//                'driver_invoices_count' => $monthlyContract->driver_invoices_count,
//                'vendor_invoices_count' => $monthlyContract->vendor_invoices_count,
//                'fuel_advance_payments_count' => $monthlyContract->fuel_advance_payments_count,
//                'created_at' => $monthlyContract->created_at,
//                'status' => $monthlyContract->status,
//            ];
//        });

        return response()->json([
            'message' => 'Monthly contract records retrieved successfully',
//            'data' => $this->mapData($monthlyContracts, $mappedData)
            'data' => $monthlyContracts
        ], 200);

    }

    /**
     * Create a new monthly contract record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            // Validate the request data
            $validatedData = $request->validate(MonthlyContract::validationRules());

            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $company = $user->company;

            if (!$company) {
                return response()->json(['error' => 'Company not found for the user'], 404);
            }

            // Create the monthly contract record
            $monthlyContract = $company->monthlyContracts()->create($validatedData);
            $monthlyContract->monthly_contract_number = $monthlyContract->generateMonthlyContractNumber($monthlyContract->client->name, $monthlyContract->id);
            $monthlyContract->save();




            // Create duty date records if provided in the request
            if ($request->has('duty_dates')) {
                $contractPeriod = $monthlyContract->contractPeriod()->create([
                    "start_date" => $request->duty_dates[0],
                    "end_date" => $request->duty_dates[1],
                ]);
            }

            $monthlyContract->load(['client:id,name,address,city,country,mobile_no,email',
                'vehicle:id,name,model_year as model,reg_no as reg',
                'driver:id,name,mobile_no,email', 'contractPeriod',
                'vendor:id,name,address,city,country,mobile_no,email'])
                ->loadCount("clientInvoices", "driverInvoices", "vendorInvoices", "fuelAdvancePayments");

            return response()->json([
                'message' => 'Monthly contract record created successfully',
                'data' => $monthlyContract,
            ], 201);
        });
    }

    /**
     * Get the details of a specific monthly contract record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $monthlyContract = MonthlyContract::findOrFail($id);
        // Check if the monthlyContract belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $monthlyContract->company_id !== $user->company->id) {
            return response()->json(['error' => 'Monthly contract not found or unauthorized'], 404);
        }

        $monthlyContract->load(['client:id,name,address,city,country,mobile_no,email',
            'vehicle:id,name,model_year as model,reg_no as reg',
            'driver:id,name,mobile_no,email', 'contractPeriod' => function ($query) {
                $query->orderBy('start_date', 'asc');
            },
            'vendor:id,name,address,city,country,mobile_no,email'])
            ->loadCount("clientInvoices", "driverInvoices", "vendorInvoices", "fuelAdvancePayments");

        return response()->json(['message' => 'Monthly contract retrieved successfully', 'data' => $monthlyContract], 200);
    }

    /**
     * Update a monthly contract record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        return DB::transaction(function () use ($request,$id) {

            $monthlyContract = MonthlyContract::findOrFail($id);

            // Check if the clientPayment belongs to the logged-in user's company
            $user = Auth::user();
            if (!$user->company || $monthlyContract->company_id !== $user->company->id) {
                return response()->json(['error' => 'Monthly contract not found or unauthorized'], 404);
            }

            $validatedData = $request->validate(MonthlyContract::validationRules());

            $monthlyContract->update($validatedData);

            $monthlyContract->contractPeriod()->delete();
            // Assuming 'duty_dates' is an array of date data

            if ($request->has('duty_dates')) {
                $contractPeriod = $monthlyContract->contractPeriod()->updateOrCreate([
                    "start_date" => $request->duty_dates[0],
                    "end_date" => $request->duty_dates[1],
                ]);
            }


            $monthlyContract->load(['client:id,name,address,city,country,mobile_no,email',
                'vehicle:id,name,model_year as model,reg_no as reg',
                'driver:id,name,mobile_no,email', 'contractPeriod',
                'vendor:id,name,address,city,country,mobile_no,email'])
                ->loadCount("clientInvoices", "driverInvoices", "vendorInvoices", "fuelAdvancePayments");

            return response()->json(['message' => 'Monthly contract record updated successfully', 'data' => $monthlyContract], 200);
        });
    }

    /**
     * Delete a monthly contract record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $monthlyContract = MonthlyContract::findOrFail($id);

            // Check if there are any associated invoices
            if ($monthlyContract->clientInvoices()->exists() || $monthlyContract->fuelAdvancePayments()->exists() || $monthlyContract->driverInvoices()->exists() || $monthlyContract->vendorInvoices()->exists()) {
                return response()->json(['error' => 'Monthly contract record cannot be deleted as it has associated invoices'], 422);
            }

            // No associated invoices, proceed with deletion
            $monthlyContract->delete();

            return response()->json(['message' => 'Monthly contract record deleted successfully'], 200);
        });
    }
}
