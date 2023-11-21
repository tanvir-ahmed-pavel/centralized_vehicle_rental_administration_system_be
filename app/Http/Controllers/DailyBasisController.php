<?php

namespace App\Http\Controllers;

use App\Models\DailyBasis;
use App\Http\Controllers\Controller;
use App\Models\DutyDate;
use App\Traits\DataMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


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
            ->with(['client', 'vehicle', 'driver', 'dutyDates', 'vendor'])
            ->orderBy($sortBy, $sortOrder)
            ->paginate($perPage, ['*'], 'page', $page);

        // Map the data to the desired structure
        $mappedData = $dailyBases->map(function ($dailyBasis) {
            return [
                'id' => $dailyBasis->id,
                'client' => [
                    'id' => $dailyBasis->client->id,
                    'name' => $dailyBasis->client->name,
                ],
                'vendor' => [
                    'id' => optional($dailyBasis->vendor)->id,
                    'name' => optional($dailyBasis->vendor)->name,
                ],
                'vehicle' => [
                    'id' => $dailyBasis->vehicle->id,
                    'name' => $dailyBasis->vehicle->name,
                    'model' => $dailyBasis->vehicle->model,
                    'reg' => $dailyBasis->vehicle->reg_no,
                ],
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

        // Create duty date records if provided in the request
        if ($request->has('duty_dates') && is_array($request->duty_dates)) {

            foreach ($request->duty_dates as $dutyDateData){
                $dailyBasis->dutyDates()->create([
                    "start_date" => $dutyDateData,
                    "end_date" => $dutyDateData,
                ]);
            }
        }

        return response()->json([
            'message' => 'Daily basis record created successfully',
            'data' => $dailyBasis,
        ], 201);
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
        return response()->json(['data' => $dailyBasis]);
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
        $company = Auth::user()->company;
        $dailyBasis = $company->dailyBases()->findOrFail($id);

        $validatedData = $request->validate(DailyBasis::validationRules());

        $dailyBasis->update($validatedData);

        // Assuming 'duty_dates' is an array of date data
        foreach ($request->duty_dates as $dutyDateData) {
            $dailyBasis->dutyDates()->updateOrCreate([
                "start_date" => $dutyDateData,
                "end_date" => $dutyDateData,
            ]);
        }

        return response()->json(['message' => 'Daily basis record updated successfully', 'data' => $dailyBasis], 200);
    }

    /**
     * Delete a daily basis record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $dailyBasis = DailyBasis::findOrFail($id);
        $dailyBasis->delete();

        return response()->json(['message' => 'Daily basis record deleted successfully'], 200);
    }
}
