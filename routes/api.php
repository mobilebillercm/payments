<?php

use Illuminate\Http\Request;

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



Route::post('payments', 'ApiController@makePaymentWithMobileBillerAcount')->middleware('token.verification');

Route::post('payments-from-mobile-biller-credit-account-accepted', 'ApiController@confirmPaymentWithMobileBillerAcount')->middleware('rabbitmq.client');

Route::post('payments-from-mobile-biller-credit-account-failed', 'ApiController@failPaymentWithMobileBillerAcount')->middleware('rabbitmq.client');

Route::get('validations/{paymentmethodtype}', 'ApiController@validatePaymentAccountWithPaymentMethodeType')->middleware('token.verification');












Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});