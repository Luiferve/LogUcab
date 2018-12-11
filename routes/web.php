<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
use Illuminate\Support\Facades\Input;

Route::get('/', function () {
    return view('index');
});

Route::get('/login', function () {
	$name = 'get';
    return view('login',['_POST' => $_POST,'name' => $name]);
});

Route::post('/login', function () {
    $name = 'post';
    return view('login',['_POST' => $_POST,'name' => $name]);
});
