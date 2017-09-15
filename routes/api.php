<?php

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
Route::post('/signup' , 'UserApiController@signup');
//login request
Route::post('oauth/token', 'UserApiController@login_access');
//email confirmation
// Route::get('/confirmation/{token}', 'UserApiController@confirmation')->name('user_confirmation');
//resend email
Route::post('/resendEmail', 'UserApiController@resend_email');

Route::group(['middleware' => ['auth:api']], function () {

	// user profile

	Route::post('/change/password' , 'UserApiController@change_password');

	Route::post('/update/location' , 'UserApiController@update_location');

	Route::get('/details' , 'UserApiController@details');

	Route::post('/update/profile' , 'UserApiController@update_profile');

	// services

	Route::get('/services' , 'UserApiController@services');

	// provider

	Route::post('/rate/provider' , 'UserApiController@rate_provider');

	// request

	Route::post('/send/request' , 'UserApiController@send_request');

	Route::post('/cancel/request' , 'UserApiController@cancel_request');

	Route::get('/request/check' , 'UserApiController@request_status_check');

	// history

	Route::get('/trips' , 'UserApiController@trips');

	Route::get('/trip/details' , 'UserApiController@trip_details');

	// payment

	Route::post('/payment' , 'PaymentController@payment');

	Route::post('/add/money' , 'PaymentController@add_money');

	// estimated

	Route::get('/estimated/fare' , 'UserApiController@estimated_fare');

	// promocode

	Route::get('/promocodes' , 'UserApiController@promocodes');

	Route::post('/promocode/add' , 'UserApiController@add_promocode');

	// card payment

    Route::resource('card', 'Resource\CardResource');

});
