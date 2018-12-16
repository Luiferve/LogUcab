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
    select U.usu_codigo,U.usu_email,U.usu_password, rol_nombre as rol_n
    from usuario U, rol, usu_rol US
    where (U.usu_cliente is not NUll or U.usu_empleado is not NULL) and US.usu_usuario = U.usu_codigo and US.usu_rol = rol_codigo
EOD;
    $users = DB::select($query);

    $permissions = Cookie::get('permissions');;
    return view('users_table',['users' => $users], ["permissions" => $permissions]);
});

Route::get('/employees', function () {
    $query = <<<'EOD'
    select emp_cedula, emp_nombre || ' ' || emp_apellido as emp_nombre,
        emp_email_personal as emp_ep, emp_email_coorporativo as emp_ec,
        emp_f_ingreso as emp_fi, emp_monto_base as emp_b, 
        suc_nombre as suc_n
    from empleado, sucursal
    where
    	emp_sucursal = suc_codigo
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
            Cookie::queue('user-email',$_POST['email'],60);
            $redirect = true;
        }
    }
    
    return view('login',['users' => $users, 'message' => $message, 'redirect' => $redirect]);
});

Route::get('/logout', function () {
    Cookie::forget('permissions');
    Cookie::forget('user-email');

    return view('index',['permissions' => 0]);
});

Route::get('/ship', function () {
    $types = DB::select('select tip_codigo cod, tip_tipo nombre from tipo_paquete');
    $countries = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Pais\'');
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');

    $permissions = Cookie::get('permissions');
    return view('shipping',['permissions' => $permissions,'types' => $types, 'countries' => $countries, 'states' => $states]);
});

Route::post('/ship', function () {
    $types = DB::select('select tip_codigo cod, tip_tipo nombre from tipo_paquete');
    $countries = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Pais\'');
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');
    $message = '';
    if ($_POST['id1'] == ''){
        $message = $message.'Campo Id1 Vacio</br>';
    }
    if ($_POST['peso'] == ''){
        $message = $message.'Campo Peso Vacio</br>';
    }
    if ($_POST['alto'] == ''){
        $message = $message.'Campo Alto Vacio</br>';
    }
    if ($_POST['ancho'] == ''){
        $message = $message.'Campo Ancho Vacio</br>';
    }
    if ($_POST['profundidad'] == ''){
        $message = $message.'Campo Profundidad Vacio</br>';
    }
    if ($_POST['tipo'] == ''){
        $message = $message.'Campo Tipo Vacio</br>';
    }


    $permissions = Cookie::get('permissions');
    return view('shipping',['permissions' => $permissions,'types' => $types ,'message' => $message, 'countries' => $countries, 'states' => $states]);
});

Route::get('/clients', function () {
    $query = <<<'EOD'
    select cli_cedula, cli_nombre || ' ' || cli_apellido as cli_nom,
        cli_email as cli_em, cli_f_nacimiento as cli_fn, lug_nombre as cli_lu,
        cli_carnet as cli_car 
    from cliente, lugar
    where
    	cli_lugar = lug_codigo
EOD;
    $clients = DB::select($query);

    $permissions = Cookie::get('permissions');
    return view('clients_table',['clients' => $clients], ["permissions" => $permissions]);
});

Route::get('/routes', function () {
    $query = <<<'EOD'
    select distinct rut_codigo as rut_c, s1.suc_nombre rut_o, s2.suc_nombre as rut_d, rut_duracion as rut_du,
    tip_costo as rut_cos
    from ruta, envio, paquete, tipo_envio, sucursal s1, sucursal s2
    where rut_codigo = env_ruta and env_codigo = paq_envio and paq_tipo_envio = tip_codigo and s1.suc_codigo = rut_suc_origen
    and s2.suc_codigo = rut_suc_destino
    	
EOD;
    $routes = DB::select($query);

    $permissions = Cookie::get('permissions');
    return view('routes_table',['routes' => $routes], ["permissions" => $permissions]);
});