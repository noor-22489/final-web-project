<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // 1. REGISTER
    public function register(Request $request)
    {
        // Validate inputs
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,teacher,student',
        ]);

        // Create User
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);
    }

    // 2. LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        // Use 'api' guard to check credentials
        if (! $token = auth()->guard('api')->attempt($credentials)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized: Invalid email or password'
            ], 401);
        }

        // Block inactive users
        $user = auth()->guard('api')->user();
        if ($user && $user->is_active === false) {
            auth()->guard('api')->logout();
            return response()->json([
                'status' => false,
                'message' => 'Your account is inactive. Please contact admin.'
            ], 403);
        }

        return $this->respondWithToken($token);
    }

    // 3. GET PROFILE (ME)
    public function me()
    {
        return response()->json(auth()->guard('api')->user());
    }

    // 4. LOGOUT
    public function logout()
    {
        auth()->guard('api')->logout();
        return response()->json(['status' => true, 'message' => 'Successfully logged out']);
    }

    // HELPER: Format the Token Response
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->guard('api')->factory()->getTTL() * 60,
            'user' => auth()->guard('api')->user()
        ]);
    }
}