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
    $permissions = Cookie::get('permissions');

    return view('index', ["permissions" => $permissions]);
});

Route::get('/franchises', function () {
    $query = <<<'EOD'
    select (select b.lug_nombre from lugar a, lugar b where s.suc_lugar = a.lug_codigo and a.lug_lugar = b.lug_codigo) estado,
        s.suc_nombre nombre
    from sucursal s
EOD;
    $franchises = DB::select($query);

    $permissions = Cookie::get('permissions');
    return view('franchises_table',['franchises' => $franchises], ["permissions" => $permissions]);
});

Route::get('/locations', function () {
    $query = <<<'EOD'
    select (select d.lug_nombre from lugar b,lugar c,lugar d where a.lug_lugar=b.lug_codigo and b.lug_lugar=c.lug_codigo and c.lug_lugar=d.lug_codigo) pais,
        (select c.lug_nombre from lugar b,lugar c where a.lug_lugar=b.lug_codigo and b.lug_lugar=c.lug_codigo) estado,
        (select b.lug_nombre from lugar b where a.lug_lugar=b.lug_codigo) municipio,
        a.lug_nombre parroquia 
    from lugar a 
    where a.lug_tipo = 'Parroquia'
EOD;
    $locations = DB::select($query);

    $permissions = Cookie::get('permissions');
    return view('locations_table',['locations' => $locations], ["permissions" => $permissions]);
});

Route::get('/users', function () {
    $query = <<<'EOD'
    select usu_codigo,usu_email,usu_password 
    from usuario
EOD;
    $users = DB::select($query);

    $permissions = Cookie::get('permissions');;
    return view('users_table',['users' => $users], ["permissions" => $permissions]);
});

Route::get('/employees', function () {
    $query = <<<'EOD'
    select emp_cedula, emp_nombre || ' ' || emp_apellido as emp_nombre,
        emp_email_personal as emp_ep, emp_email_coorporativo as emp_ec 
    from empleado
EOD;
    $employees = DB::select($query);

    $permissions = Cookie::get('permissions');
    return view('employees_table',['employees' => $employees], ["permissions" => $permissions]);
});

Route::get('/login', function () {

    return view('login',[]);
});

Route::post('/login', function () {
    $users = [];
    $redirect = false;
    if (array_key_exists('password2', $_POST)){
        if ($_POST['password'] == $_POST['password2'] && $_POST['password'] != '' && $_POST['email'] != ''){
            $users = DB::select('select usu_email from usuario where usu_email = \''.$_POST['email'].'\'');
            
            if (empty($users)){
                $code = DB::select('select max(usu_codigo) m from usuario');
                if (empty($code)){$code = 0;}
                else {
                    $code = $code[0]->m;
                }
                $users = DB::insert('insert into usuario (usu_codigo,usu_email,usu_password) values('.$code.'+1,\''.$_POST['email'].'\',\''.$_POST['password'].'\')');
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

            Cookie::queue('permissions', 4, 60);
            $redirect = true;
        }
    }
    
    // Cookie::queue('_token', 'test-123456789', 60);
    return view('login',['users' => $users, 'message' => $message, 'redirect' => $redirect]);
});

Route::get('/logout', function () {
    Cookie::forget('permissions');

    return view('index',['permissions' => 0]);
});