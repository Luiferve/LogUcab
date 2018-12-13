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
    $permissions = 4;

    return view('index', ["permissions" => $permissions]);
});

Route::get('/locations', function () {
    $locations = DB::select('select (select d.lug_nombre from lugar b,lugar c,lugar d where a.lug_lugar=b.lug_codigo and b.lug_lugar=c.lug_codigo and c.lug_lugar=d.lug_codigo) pais,(select c.lug_nombre from lugar b,lugar c where a.lug_lugar=b.lug_codigo and b.lug_lugar=c.lug_codigo) estado,(select b.lug_nombre from lugar b where a.lug_lugar=b.lug_codigo) municipio,a.lug_nombre parroquia from lugar a where a.lug_tipo = \'Parroquia\'');

    return view('locations_table',['locations' => $locations]);
});

Route::get('/users', function () {
    $users = DB::select('select usu_codigo,usu_email,usu_password from usuario');

    return view('users_table',['users' => $users]);
});

Route::get('/employees', function () {
    $employees = DB::select('select emp_cedula, emp_nombre || \' \' || emp_apellido as emp_nombre,emp_email_personal as emp_ep, emp_email_coorporativo as emp_ec from empleado');

    return view('employees_table',['employees' => $employees]);
});

Route::get('/login', function () {

    return view('login',[]);
});

Route::post('/login', function () {
    $users = [];
    if (array_key_exists('password2', $_POST)){
        if ($_POST['password'] == $_POST['password2'] && $_POST['password'] != '' && $_POST['email'] != ''){
            $users = DB::select('select usu_email from usuario where usu_email = \''.$_POST['email'].'\'');
            
            if (empty($users)){
                $users = DB::insert('insert into usuario (usu_codigo,usu_email,usu_password) values((select max(usu_codigo) from usuario)+1,\''.$_POST['email'].'\',\''.$_POST['password'].'\')');
                $message = 'Registro exitoso.';
            } else {
                $message = 'El email ('.$_POST['email'].') ya esta registrado.';
            }

        } else {
            $message = 'Las contraseñas no coinciden.';
        }
    } else {
        $users = DB::select('select usu_email from usuario where usu_email = \''.$_POST['email'].'\' and usu_password = \''.$_POST['password'].'\'');
        if (empty($users)){
            $message = 'Datos erroneos.';
        } else {
            $message = 'Bienvenido '.$_POST['email'].'.';
        }
    }
    
    // Cookie::queue('_token', 'test-123456789', 60);
    return view('login',['users' => $users, 'message' => $message]);
});
