<?php

use App\Http\Controllers\api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('register',[UserController::class,'register']);
Route::post('login',[UserController::class,'login']);
Route::post('update-password',[UserController::class,'update_password']);
Route::get('subscriptions',[UserController::class,'subscriptions']);
Route::post('update-profile',[UserController::class,'update_profile']);
Route::post('update-fcm',[UserController::class,'update_fcm']);
Route::post('get-profile',[UserController::class,'get_profile']);
Route::post('get-fcm',[UserController::class,'get_fcm']);
Route::post('send-verifiaction-pin',[UserController::class,'send_verification_pin']);
Route::post('set-new-password',[UserController::class,'set_new_password']);
Route::post('disable-account',[UserController::class,'disabled_account']);
Route::post('enable-account',[UserController::class,'enable_account']);
Route::post('user-subscribe',[UserController::class,'user_subscribe']);

