<?php

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::any('pushmsg/{bot_token}', 'api\ApiController@pushmsg'); //接收消息推送 
Route::any('get/config', 'api\ApiController@get_config'); //接收消息推送 
Route::any('get/romaddress', 'api\ApiController@get_romaddress'); //生成靓号 
