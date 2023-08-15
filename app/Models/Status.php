<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;

class Status extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
//        'name',
//        'email',
//        'password',
    ];

    /**
     * Validation helper.
     *
     */
    public function validate()
    {
        return Validator::make($this->attributes, [
//            'name' => 'required|max:255',
//            'description' => 'required|max:255',
//            'price' => 'required|numeric|min:0',
        ]);
    }
}
