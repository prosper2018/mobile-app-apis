<?php

namespace App\Http\Controllers\API\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;

class Logout extends BaseController
{
    public function logout()
    {
        $user = Auth::guard('sanctum')->user();
        
        if ($user) {
            $user->tokens()->delete();
            return response()->json(['message' => 'Successfully logged out']);
        } else {
            return response()->json(['error' => 'User not authenticated'], 401);
        }
    }
}
