<?php

namespace App\Http\Controllers;

use App\Models\DailyBasis;
use App\Http\Controllers\Controller;
use App\Models\DutyDate;
use App\Traits\DataMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class DailyBasisController extends Controller
{
    use DataMapping;
    /**
     * Get a paginated list of daily basis records.
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

        // Fetch daily basis records based on the authenticated user's company and apply sorting
        $dailyBases = $company->dailyBases()
            ->with(['client', 'vehicle', 'driver', 'dutyDates' => function ($query) {
                $query->orderBy('start_date', 'asc');
            }, 'vendor'])
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
        $mappedData = $dailyBases->map(function ($dailyBasis) {
            return [
                'id' => $dailyBasis->id,
                'daily_basis_number' => $dailyBasis->daily_basis_number,
                'client_id' => $dailyBasis->client_id,
                'client' => [
                    'id' => $dailyBasis->client->id,
                    'name' => $dailyBasis->client->name,
                ],
                'vendor_id' => $dailyBasis->vendor_id,
                'vendor' => [
                    'id' => optional($dailyBasis->vendor)->id,
                    'name' => optional($dailyBasis->vendor)->name,
                ],
                'vehicle_id' => $dailyBasis->vehicle_id,
                'vehicle' => [
                    'id' => $dailyBasis->vehicle->id,
                    'name' => $dailyBasis->vehicle->name,
                    'model_year' => $dailyBasis->vehicle->model_year,
                    'reg' => $dailyBasis->vehicle->reg_no,
                ],
                'driver_id' => $dailyBasis->driver_id,
                'driver' => [
                    'id' => $dailyBasis->driver->id,
                    'name' => $dailyBasis->driver->name,
                    'mobile' => $dailyBasis->driver->mobile_no,
                ],
                'duty_dates' => $dailyBasis->dutyDates->map(function ($dutyDate) {
                    return [
                        'id' => $dutyDate->id,
                        'start_date' => $dutyDate->start_date,
                        'end_date' => $dutyDate->end_date,
                        'is_half_day' => $dutyDate->is_half_day,
                    ];
                }),
                'client_invoices_count' => $dailyBasis->client_invoices_count,
                'driver_invoices_count' => $dailyBasis->driver_invoices_count,
                'vendor_invoices_count' => $dailyBasis->vendor_invoices_count,
                'fuel_advance_payments_count' => $dailyBasis->fuel_advance_payments_count,
                'created_at' => $dailyBasis->created_at,
                'status' => $dailyBasis->status,
            ];
        });

        return response()->json([
            'message' => 'Daily basis records retrieved successfully',
            'data' => $this->mapData($dailyBases, $mappedData)
        ], 200);

    }

    /**
     * Create a new daily basis record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            // Validate the request data
            $validatedData = $request->validate(DailyBasis::validationRules());

            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $company = $user->company;

            if (!$company) {
                return response()->json(['error' => 'Company not found for the user'], 404);
            }

            // Create the daily basis record
            $dailyBasis = $company->dailyBases()->create($validatedData);
            $dailyBasis->daily_basis_number = $dailyBasis->generateDailyBasisNumber($dailyBasis->client->name, $dailyBasis->id);
            $dailyBasis->save();


            // Create duty date records if provided in the request
            if ($request->has('duty_dates') && is_array($request->duty_dates)) {

                foreach ($request->duty_dates as $dutyDateData) {
                    $dailyBasis->dutyDates()->create([
                        "start_date" => $dutyDateData,
                        "end_date" => $dutyDateData,
                    ]);
                }
            }

            $dailyBasis->with(['client', 'vehicle', 'driver', 'dutyDates' => function ($query) {
                $query->orderBy('start_date', 'asc');
            }, 'vendor'])->withCount("clientInvoices", "driverInvoices", "vendorInvoices", "fuelAdvancePayments");

            $mappedData = [
                'id' => $dailyBasis->id,
                'daily_basis_number' => $dailyBasis->daily_basis_number,
                'client_id' => $dailyBasis->client_id,
                'client' => [
                    'id' => $dailyBasis->client->id,
                    'name' => $dailyBasis->client->name,
                ],
                'vendor_id' => $dailyBasis->vendor_id,
                'vendor' => [
                    'id' => optional($dailyBasis->vendor)->id,
                    'name' => optional($dailyBasis->vendor)->name,
                ],
                'vehicle_id' => $dailyBasis->vehicle_id,
                'vehicle' => [
                    'id' => $dailyBasis->vehicle->id,
                    'name' => $dailyBasis->vehicle->name,
                    'model_year' => $dailyBasis->vehicle->model_year,
                    'reg' => $dailyBasis->vehicle->reg_no,
                ],
                'driver_id' => $dailyBasis->driver_id,
                'driver' => [
                    'id' => $dailyBasis->driver->id,
                    'name' => $dailyBasis->driver->name,
                    'mobile' => $dailyBasis->driver->mobile_no,
                ],
                'duty_dates' => $dailyBasis->dutyDates->map(function ($dutyDate) {
                    return [
                        'id' => $dutyDate->id,
                        'start_date' => $dutyDate->start_date,
                        'end_date' => $dutyDate->end_date,
                        'is_half_day' => $dutyDate->is_half_day,
                    ];
                }),
                'client_invoices_count' => $dailyBasis->client_invoices_count,
                'driver_invoices_count' => $dailyBasis->driver_invoices_count,
                'vendor_invoices_count' => $dailyBasis->vendor_invoices_count,
                'fuel_advance_payments_count' => $dailyBasis->fuel_advance_payments_count,
                'created_at' => $dailyBasis->created_at,
                'status' => $dailyBasis->status,
            ];

            return response()->json([
                'message' => 'Daily basis record created successfully',
                'data' => $mappedData,
            ], 201);
        });
    }

    /**
     * Get the details of a specific daily basis record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $dailyBasis = DailyBasis::findOrFail($id);
        // Check if the dailyBasis belongs to the logged-in user's company
        $user = Auth::user();
        if (!$user->company || $dailyBasis->company_id !== $user->company->id) {
            return response()->json(['error' => 'Daily basis not found or unauthorized'], 404);
        }

        $dailyBasis->load(['client:id,name,address,city,country,mobile_no,email',
            'vehicle:id,name,model_year as model,reg_no as reg',
            'driver:id,name,mobile_no,email', 'dutyDates' => function ($query) {
                $query->orderBy('start_date', 'asc');
            },
            'vendor:id,name'])
            ->loadCount("clientInvoices", "driverInvoices", "vendorInvoices", "fuelAdvancePayments");

        return response()->json(['message' => 'Daily basis retrieved successfully', 'data' => $dailyBasis], 200);
    }

    /**
     * Update a daily basis record.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        return DB::transaction(function () use ($request,$id) {

            $dailyBasis = DailyBasis::findOrFail($id);

            // Check if the clientPayment belongs to the logged-in user's company
            $user = Auth::user();
            if (!$user->company || $dailyBasis->company_id !== $user->company->id) {
                return response()->json(['error' => 'Daily basis not found or unauthorized'], 404);
            }

            $validatedData = $request->validate(DailyBasis::validationRules());

            $dailyBasis->update($validatedData);

            $dailyBasis->dutyDates()->delete();
            // Assuming 'duty_dates' is an array of date data
            foreach ($request->duty_dates as $dutyDateData) {
                $dailyBasis->dutyDates()->updateOrCreate([
                    "start_date" => $dutyDateData,
                    "end_date" => $dutyDateData,
                ]);
            }


            $dailyBasis->with(['client', 'vehicle', 'driver', 'dutyDates' => function ($query) {
                $query->orderBy('start_date', 'asc');
            }, 'vendor'])->withCount("clientInvoices", "driverInvoices", "vendorInvoices", "fuelAdvancePayments");

            $mappedData = [
                'id' => $dailyBasis->id,
                'daily_basis_number' => $dailyBasis->daily_basis_number,
                'client_id' => $dailyBasis->client_id,
                'client' => [
                    'id' => $dailyBasis->client->id,
                    'name' => $dailyBasis->client->name,
                ],
                'vendor_id' => $dailyBasis->vendor_id,
                'vendor' => [
                    'id' => optional($dailyBasis->vendor)->id,
                    'name' => optional($dailyBasis->vendor)->name,
                ],
                'vehicle_id' => $dailyBasis->vehicle_id,
                'vehicle' => [
                    'id' => $dailyBasis->vehicle->id,
                    'name' => $dailyBasis->vehicle->name,
                    'model_year' => $dailyBasis->vehicle->model_year,
                    'reg' => $dailyBasis->vehicle->reg_no,
                ],
                'driver_id' => $dailyBasis->driver_id,
                'driver' => [
                    'id' => $dailyBasis->driver->id,
                    'name' => $dailyBasis->driver->name,
                    'mobile' => $dailyBasis->driver->mobile_no,
                ],
                'duty_dates' => $dailyBasis->dutyDates->map(function ($dutyDate) {
                    return [
                        'id' => $dutyDate->id,
                        'start_date' => $dutyDate->start_date,
                        'end_date' => $dutyDate->end_date,
                        'is_half_day' => $dutyDate->is_half_day,
                    ];
                }),
                'client_invoices_count' => $dailyBasis->client_invoices_count,
                'driver_invoices_count' => $dailyBasis->driver_invoices_count,
                'vendor_invoices_count' => $dailyBasis->vendor_invoices_count,
                'fuel_advance_payments_count' => $dailyBasis->fuel_advance_payments_count,
                'created_at' => $dailyBasis->created_at,
                'status' => $dailyBasis->status,
            ];

            return response()->json(['message' => 'Daily basis record updated successfully', 'data' => $mappedData], 200);
        });
    }

    /**
     * Delete a daily basis record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        return DB::transaction(function () use ($id) {
            $dailyBasis = DailyBasis::findOrFail($id);

            // Check if there are any associated invoices
            if ($dailyBasis->clientInvoices()->exists() || $dailyBasis->fuelAdvancePayments()->exists() || $dailyBasis->driverInvoices()->exists() || $dailyBasis->vendorInvoices()->exists()) {
                return response()->json(['error' => 'Daily basis record cannot be deleted as it has associated invoices'], 422);
            }

            // No associated invoices, proceed with deletion
            $dailyBasis->delete();

            return response()->json(['message' => 'Daily basis record deleted successfully'], 200);
        });
    }
}
