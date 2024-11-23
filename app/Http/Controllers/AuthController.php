<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user registration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function signUp(Request $request)
    {
        // Validate request inputs
        $data = $request->validate([
            'phone_number' => 'required|unique:users|digits:10',
            'password' => 'required|min:8',
            'role' => 'nullable|in:user,admin,seller', // Ensure role is valid or optional
        ]);

        
        // Set default role to 'user' if none provided
        $data['role'] = $data['role'] ?? 'user';
        
        // Hash the password
        $data['password'] = Hash::make($data['password']);
        
        // return response()->json(['userRole'=>  $data]);


        // Create the user
        $user = User::create($data);
        
        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * Handle user login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Validate login inputs
        $credentials = $request->validate([
            'phone_number' => 'required|digits:10',
            'password' => 'required',
        ]);

        // Find the user by phone number
        $user = User::where('phone_number', $credentials['phone_number'])->first();

        // Verify user and password
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'phone_number' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Handle user logout (revoke current token).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke the current access token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get the authenticated user's details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMe(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }
}
