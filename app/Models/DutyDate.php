<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;

class DutyDate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    use SoftDeletes;

    protected $fillable = [
        'daily_basis_id',
        'monthly_contract_id',
        'start_date',
        'end_date',
        'is_half_day',
    ];


    /**
     * Validation helper.
     *
     */
    public static function validationRules()
    {
        return [
            'daily_basis_id' => 'nullable|exists:daily_bases,id',
            'monthly_contract_id' => 'nullable|exists:monthly_contracts,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'is_half_day' => 'boolean',
        ];
    }

    public function dailyBasis()
    {
        return $this->belongsTo(DailyBasis::class);
    }
    public function monthlyContract()
    {
        return $this->belongsTo(MonthlyContract::class);
    }
}
