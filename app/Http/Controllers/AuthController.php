<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ThrottlesAttempts;

class AuthController extends Controller
{
    use ThrottlesAttempts;
    public function register(Request $request)
    {
        $validatedData = $request->validate(User::validationRules());

        // Create the user
        $user = User::create([
            'name' => $validatedData["name"],
            'email' => $validatedData["email"],
            'password' => bcrypt($validatedData["password"]),
        ]);

        // Create the company for the user
        $company = Company::create([
            'user_id' => $user->id,
            'name' => $request->company_name, // Use the company name from the request
        ]);

        $user->company()->associate($company); // Assign the company to the user
        $user->save();

        $token = $user->createToken('AccessToken')->accessToken;

        return response()->json([
            'message' => 'User and Company created successfully',
            "user"  => $user,
            'token' => $token
        ], 201);
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

            return response()->json([
                'status_code' => 401,
                'message' => "The credentials doesn't match our records"
            ], 401);
        }
        //auth attempt good

        $this->clearAttempts($request);

        $user = $request->user();
        $token = $user->createToken('AccessToken')->accessToken;

        return response()->json([
            "user"  => $user,
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
            "user"  => $user,
            "token"  => $request->bearerToken(),
        ], 200);
    }


}
