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
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('index');
});

Route::get('/login', function () {
	$name = 'get';
    return view('login',['name' => $name]);
});

Route::post('/login', function () {
    $name = 'post';
    $users = DB::select('select usu_email from usuario where usu_email = \''.$_POST['email'].'\' and usu_password = \''.$_POST['password'].'\'');
    $message = 'Bienvenido '.$_POST['email'].'.';
    if (empty($users)){
        $message = 'Usuario ('.$_POST['email'].') no encontrado.';
    }
    
    // Cookie::queue('_token', 'test-123456789', 60);
    return view('login',['name' => $name, 'users' => $users, 'message' => $message]);
});
