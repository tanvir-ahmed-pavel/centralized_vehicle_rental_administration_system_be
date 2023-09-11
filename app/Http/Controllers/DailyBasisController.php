<?php

namespace App\Http\Controllers;

use App\Models\DailyBasis;
use App\Http\Controllers\Controller;
use App\Models\DutyDate;
use Illuminate\Http\Request;

class DailyBasisController extends Controller
{
    /**
     * Get a paginated list of daily basis records.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $dailyBases = DailyBasis::orderBy('id', 'desc')->paginate(10);
        return response()->json($dailyBases, 200);
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

        // Create the daily basis record
        $dailyBasis = DailyBasis::create($validatedData);

        // Create duty date records if provided in the request
        if ($request->has('duty_dates') && is_array($request->duty_dates)) {
            foreach ($request->duty_dates as $dutyDateData) {
                $dutyDate = new DutyDate($dutyDateData);
                $dailyBasis->dutyDates()->save($dutyDate);
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
        $dailyBasis = DailyBasis::findOrFail($id);

        $validatedData = $request->validate(DailyBasis::validationRules());

        $dailyBasis->update($validatedData);

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
