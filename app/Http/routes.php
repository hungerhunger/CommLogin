<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/


Route::get('/', function () {
    return "ok";
});


Route::group([ ], function () {

    Route::get('Comm/check', 'CommController@checkLogin');        //检查登录状态

    Route::get('Comm/sendRandom', 'CommController@sendRandom');   //发送手机验证码

    Route::get('Comm/getCaptcha', 'CommController@getCaptcha');   //获取图形验证码

    Route::get('Comm/login', 'CommController@login');             //登录

    Route::get('Comm/auth', 'CommController@auth');               //认证

    Route::get('Comm/bill', 'CommController@bill');               //通话详单

    Route::get('Comm/getCaptcha1', 'CommController@getCaptcha1');   //获取图形验证码

    Route::get('Comm/sendRandomAuth', 'CommController@sendRandomAuth');   //发送认证验证码
});
