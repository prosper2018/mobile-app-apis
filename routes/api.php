<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BlogController;
use App\Http\Controllers\API\Auth\Login;
use App\Http\Controllers\API\Auth\Logout;
use App\Http\Controllers\API\Users\StaffSetup;
use App\Http\Controllers\API\LeaveController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('login', [Login::class, 'signIn'])->name('login');
Route::post('logout', [Logout::class, 'logout']);
// Route::post('login', [AuthController::class, 'signin']);



Route::middleware('auth:sanctum')->group(function () {
    Route::resource('blogs', BlogController::class);
    Route::post('register', [StaffSetup::class, 'storeStaff']);
    Route::get('profile', [StaffSetup::class, 'profile']);
    Route::post('leave_setup', [LeaveController::class, 'leaveSetup']);
});


// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
