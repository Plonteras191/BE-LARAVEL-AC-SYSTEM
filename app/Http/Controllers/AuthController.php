<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // Fixed admin accounts (instead of using a database)
    private $admins = [
        [
            'email' => 'eer@admin',
            'password' => 'eeradmin',
            'name' => 'Admin User'
        ],
        [
            'email' => 'eer@admin2',
            'password' => 'eeradmin2',
            'name' => 'Admin User 2'
        ]
    ];

    /**
     * Login user and create token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        // Check credentials against our fixed admin accounts
        $admin = null;
        foreach ($this->admins as $adminAccount) {
            if ($adminAccount['email'] === $request->email &&
                $adminAccount['password'] === $request->password) {
                $admin = $adminAccount;
                break;
            }
        }

        if (!$admin) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Generate a random token
        $token = Str::random(60);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'name' => $admin['name'],
                'email' => $admin['email']
            ]
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        // In a real app, we'd validate the token here
        // For this example, we're just checking if the token exists

        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Just return the first admin user for simplicity
        // In a real app, we would decode the token to get the actual user
        return response()->json([
            'name' => $this->admins[0]['name'],
            'email' => $this->admins[0]['email']
        ]);
    }

    /**
     * Logout user (Revoke the token)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // In a real app with real tokens, we would revoke the token here

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}
