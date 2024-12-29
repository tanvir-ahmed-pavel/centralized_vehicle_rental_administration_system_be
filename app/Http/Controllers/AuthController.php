<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Traits\ThrottlesAttempts;

class AuthController extends Controller
{
    use ThrottlesAttempts;
    public function register(Request $request)
    {
        return response()->json(['error' => "We are not accepting request via this from, please contact admin."], 406);
        return DB::transaction(function () use ($request) {

            $validatedData = $request->validate(User::validationRules());

            // Create the user
            $user = User::create([
                'name' => $validatedData["name"],
                'email' => $validatedData["email"],
                'password' => bcrypt($validatedData["password"]),
            ]);

            // Create the company for the user
            if ($user) {
                $company = Company::create([
                    'user_id' => $user->id,
                    'name' => $request->company_name, // Use the company name from the request
                ]);

                $user->company()->associate($company); // Assign the company to the user
                $user->save();
            }


            $token = $user->createToken('AccessToken')->accessToken;

            return response()->json([
                'message' => 'User and Company created successfully',
                "user" => $user->load("company"),
                'token' => $token
            ], 201);
        });
    }


    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $credentials = request(['email', 'password']);

        if ($this->hasTooManyAttempts($request)) {
            return $this->sendLockoutResponse($request);
        }

        if (!Auth::attempt($credentials)) {
            $this->incrementAttempts($request);

            return response()->json(['error' => "The credentials doesn't match our records."], 401);
        }
        //auth attempt good

        $this->clearAttempts($request);

        $user = $request->user();
        $token = $user->createToken('AccessToken')->accessToken;

        return response()->json([
            "user"  => $user->load(["company"]),
            'token' => $token
        ], 200);
    }


    public function logout (Request $request) {
        $token = $request->user()->token();
        $token->revoke();
        return response()->json(['message' => 'You have been successfully logged out!'], 200);
    }

    public function verify_auth(Request $request)
    {

        $user = Auth::user();

        return response()->json([
            "user"  => $user->load("company"),
            "token"  => $request->bearerToken(),
        ], 200);
    }


}
