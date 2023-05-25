<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\UserController;
use Illuminate\Support\Facades\Http;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('register', [UserController::class,'register']);
Route::post('login', [UserController::class,'login'])->name('login');
Route::post('refresh-token', [UserController::class,'refreshToken'])->name('refresh');

Route::middleware('auth:api')->group(function(){
        Route::get('get-user', [UserController::class,'getUserInfo']);
        Route::get('all-users', [UserController::class,'getAllUsers']);
        Route::post('logout', [UserController::class,'logout'])->name('logout');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
