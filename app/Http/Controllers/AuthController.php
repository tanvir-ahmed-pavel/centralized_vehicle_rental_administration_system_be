<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $token = $user->createToken('AccessToken')->accessToken;

        return response()->json(['token' => $token], 200);
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

        return response()->json(['token' => $token], 200);
    }


    public function logout (Request $request) {
        $token = $request->user()->token();
        $token->revoke();
        return response()->json(['message' => 'You have been successfully logged out!'], 200);
    }
}
