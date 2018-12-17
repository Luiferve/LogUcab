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
    $userEmail = Cookie::get('user-email');

    return view('index', ["permissions" => $permissions,"userEmail" => $userEmail]);
});

Route::get('/franchises', function () {
    $query = <<<'EOD'
    select (select b.lug_nombre from lugar a, lugar b where s.suc_lugar = a.lug_codigo and a.lug_lugar = b.lug_codigo) estado,
        s.suc_nombre nombre, s.suc_codigo codigo
    from sucursal s
EOD;
    $franchises = DB::select($query);

    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    return view('franchises_table',['franchises' => $franchises], ["permissions" => $permissions,"userEmail" => $userEmail]);
});

Route::get('/locations', function () {
    $query = <<<'EOD'
    select (select d.lug_nombre from lugar b,lugar c,lugar d where a.lug_lugar=b.lug_codigo and b.lug_lugar=c.lug_codigo and c.lug_lugar=d.lug_codigo) pais,
        (select c.lug_nombre from lugar b,lugar c where a.lug_lugar=b.lug_codigo and b.lug_lugar=c.lug_codigo) estado,
        (select b.lug_nombre from lugar b where a.lug_lugar=b.lug_codigo) municipio,
        a.lug_nombre parroquia, a.lug_codigo codigo
    from lugar a 
    where a.lug_tipo = 'Parroquia'
EOD;
    $locations = DB::select($query);

    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    return view('locations_table',['locations' => $locations], ["permissions" => $permissions,"userEmail" => $userEmail]);
});

Route::get('/users', function () {
    $query = <<<'EOD'
    select usu_codigo,usu_email,usu_password 
    from usuario
EOD;
    $users = DB::select($query);

    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    return view('users_table',['users' => $users], ["permissions" => $permissions,"userEmail" => $userEmail]);
});

Route::get('/employees', function () {
    $query = <<<'EOD'
    select emp_cedula, emp_nombre || ' ' || emp_apellido as emp_nombre,
        emp_email_personal as emp_ep, emp_email_coorporativo as emp_ec 
    from empleado
EOD;
    $employees = DB::select($query);

    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    return view('employees_table',['employees' => $employees], ["permissions" => $permissions,"userEmail" => $userEmail]);
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

            Cookie::queue('permissions', 4, 45000);
            Cookie::queue('user-email',$_POST['email'],45000);
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
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    
    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    return view('shipping',['permissions' => $permissions, 'userEmail' => $userEmail,'types' => $types, 'countries' => $countries, 'states' => $states,'franchises' => $franchises]);
});

Route::post('/ship', function () {
    $userEmail = Cookie::get('user-email');

    $types = DB::select('select tip_codigo cod, tip_tipo nombre from tipo_paquete');
    $countries = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Pais\'');
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    $employee = DB::select('select usu_empleado cod from usuario where usu_email=\''.$userEmail.'\'');
    $message = '';
    $completed = true;

    if ($_POST['receiverID'] == ''){
        $message = $message.'Campo receiverID Vacio. ';
        $completed = false;
    }
    if ($_POST['receiverName'] == ''){
        $message = $message.'Campo receiverName Vacio. ';
        $completed = false;
    }
    if ($_POST['senderID'] == ''){
        $message = $message.'Campo senderID Vacio. ';
        $completed = false;
    }
    if ($_POST['origen'] == ''){
        $message = $message.'Campo Origen Vacio. ';
        $completed = false;
    }
    if ($_POST['destino'] == ''){
        $message = $message.'Campo Destino Vacio. ';
        $completed = false;
    }
    if ($_POST['peso'] == ''){
        $message = $message.'Campo Peso Vacio. ';
        $completed = false;
    }
    if ($_POST['alto'] == ''){
        $message = $message.'Campo Alto Vacio. ';
        $completed = false;
    }
    if ($_POST['ancho'] == ''){
        $message = $message.'Campo Ancho Vacio. ';
        $completed = false;
    }
    if ($_POST['profundidad'] == ''){
        $message = $message.'Campo Profundidad Vacio. ';
        $completed = false;
    }
    if ($_POST['tipo'] == ''){
        $message = $message.'Campo Tipo Vacio. ';
        $completed = false;
    }
    if ($_POST['country'] == ''){
        $message = $message.'Campo Pais Vacio. ';
        $completed = false;
    }
    if ($_POST['state'] == ''){
        $message = $message.'Campo Estado Vacio. ';
        $completed = false;
    }
    if ($_POST['tipo-pago'] == ''){
        $message = $message.'Campo Tipo de pago Vacio. ';
        $completed = false;
    }
    if ($completed){
        $receiver = DB::select('select des_codigo cod, des_cedula cedula, des_nombre nombre from destinatario where des_cedula='.$_POST['receiverID']);
        if (empty($receiver)){
            if ($_POST['receiverName'] != ''){
                $receiver = DB::insert('insert into destinatario(des_cedula, des_nombre) values ('.$_POST['receiverID'].', \''.$_POST['receiverName'].'\')');
                $receiver = DB::select('select des_codigo cod, des_cedula cedula, des_nombre nombre from destinatario order by des_codigo DESC limit 1');
            } else {
                $message = $message.'Faltan datos del destinatario. ';
            }
        }

        $sender = DB::select('select cli_cedula cedula, cli_nombre nombre from cliente where cli_cedula='.$_POST['senderID']);
        if (empty($sender)){
            if ($_POST['senderName'] != '' && $_POST['surname'] != '' && $_POST['date'] != '' && $_POST['civil'] != '' && $_POST['company'] != '' && $_POST['phone-#'] != '' && $_POST['country'] != '' && $_POST['state'] != '' && $_POST['address'] != '' && $_POST['email'] != '' && $_POST['civil'] != ''){
                $location = DB::insert('insert into lugar(lug_nombre,lug_tipo,lug_lugar) values(\''.$_POST['address'].'\',\'Otro\','.$_POST['state'].')');
                $location = DB::select('select lug_codigo cod from lugar order by lug_codigo DESC limit 1');

                $sender = DB::insert('insert into cliente(cli_cedula, cli_nombre, cli_apellido, cli_f_nacimiento, cli_empresa, cli_lugar, cli_estado_civil, cli_email) values ('.$_POST['senderID'].', \''.$_POST['senderName'].'\', \''.$_POST['surname'].'\',\''.$_POST['date'].'\', \''.$_POST['company'].'\', '.$location[0]->cod.', \''.$_POST['civil'].'\', \''.$_POST['email'].'\')');
                $sender = DB::select('select cli_cedula cedula, cli_nombre nombre from cliente where cli_cedula='.$_POST['senderID']);

                $phone = DB::select('select tel_numero numero from telefono where tel_numero=\''.$_POST['phone-#'].'\'');
                if (empty($phone)){
                    $phone = DB::insert('insert into telefono(tel_numero,tel_cliente) values(\''.$_POST['phone-#'].'\',\''.$_POST['senderID'].'\')');
                    $phone = DB::select('select tel_numero numero from telefono where tel_numero=\''.$_POST['phone-#'].'\'');
                }

            } else {
                $message = $message.'Faltan datos del remitente. ';
            }
        }


        $route = DB::select('select rut_codigo cod from ruta where rut_suc_origen='.$_POST['origen'].' and rut_suc_destino='.$_POST['destino']);
        if (empty($route)){
            $route = DB::insert('insert into ruta(rut_duracion, rut_suc_origen, rut_suc_destino, rut_med_transporte) values('.rand(20,120).', '.$_POST['origen'].', '.$_POST['destino'].', 2)');
            $route = DB::select('select rut_codigo cod from ruta where rut_suc_origen='.$_POST['origen'].' and rut_suc_destino='.$_POST['destino']);
        }

        $payment = 'NULL';
        if ($_POST['tipo-pago'] != 'N'){
            $method = array('','');
            if ($_POST['tipo-pago'] == 'Credito'){
                $method[0] = ',cre_tarjeta';
                $method[1] = ','.$_POST['card-number'];
            } elseif ($_POST['tipo-pago'] == 'Debito'){
                $method[0] = ',deb_tarjeta';
                $method[1] = ','.$_POST['card-number'];
            } elseif ($_POST['tipo-pago'] == 'Cheque'){
                $method[0] = ',che-num-cheque';
                $method[1] = ','.$_POST['card-number'];
            }

            $payment = DB::insert('insert into pago(pag_tipo, pag_fecha '.$method[0].') values (\''.$_POST['tipo-pago'].'\', \''.date('d/m/Y').'\' '.$method[1].' )');
            $payment = DB::select('select pag_codigo cod from pago order by pag_codigo DESC limit 1')[0]->cod;
        }

        // echo(var_dump($employee));
        // echo(var_dump($sender));
        // echo(var_dump($route));
        $shipment = DB::insert('insert into envio(env_fecha, env_cliente, env_empleado, env_suc_origen, env_suc_destino, env_ruta, env_pago) values (\''.date('d/m/Y').'\', '.$sender[0]->cedula.', '.$employee[0]->cod.', '.$_POST['origen'].', '.$_POST['destino'].', '.$route[0]->cod.', '.$payment.')');
        $shipment = DB::select('select env_codigo cod from envio order by env_codigo DESC limit 1');
        
        $package = DB::insert('insert into paquete(paq_peso, paq_ancho, paq_alto, paq_profundidad, paq_destinatario, paq_envio, paq_tipo_paquete, paq_tipo_envio) values ('.$_POST['peso'].', '.$_POST['ancho'].', '.$_POST['alto'].', '.$_POST['profundidad'].', '.$receiver[0]->cod.', '.$shipment[0]->cod.', '.$_POST['tipo'].', '.$_POST['tipo-envio'].')');
        $message = 'Envio Realizado';
        $_POST = NULL;
    }
    
    $permissions = Cookie::get('permissions');
    return view('shipping',['permissions' => $permissions, 'userEmail' => $userEmail,'types' => $types ,'message' => $message, 'countries' => $countries, 'states' => $states,'franchises' => $franchises]);
});

Route::get('/print',function (){

    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    return view('invoice',['permissions' => $permissions, 'userEmail' => $userEmail]);
});

Route::get('/users/{id}',function ($id) {

    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    // return view('invoice',['permissions' => $permissions, 'userEmail' => $userEmail]);
    return 'Got: '.$id;
});

Route::get('/employees/{id}',function ($id) {

    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    // return view('invoice',['permissions' => $permissions, 'userEmail' => $userEmail]);
    return 'Got: '.$id;
});

Route::get('/locations/{id}',function ($id) {

    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    // return view('invoice',['permissions' => $permissions, 'userEmail' => $userEmail]);
    return 'Got: '.$id;
});

Route::get('/franchises/{id}',function ($id) {

    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    // return view('invoice',['permissions' => $permissions, 'userEmail' => $userEmail]);
    return 'Got: '.$id;
});

Route::get('/users/delete/{id}',function ($id) {

    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    // return view('invoice',['permissions' => $permissions, 'userEmail' => $userEmail]);
    return 'Got: '.$id;
});

Route::get('/employees/delete/{id}',function ($id) {

    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    // return view('invoice',['permissions' => $permissions, 'userEmail' => $userEmail]);
    return 'Got: '.$id;
});

Route::get('/locations/delete/{id}',function ($id) {

    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    // return view('invoice',['permissions' => $permissions, 'userEmail' => $userEmail]);
    return 'Got: '.$id;
});

Route::get('/franchises/delete/{id}',function ($id) {

    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    // return view('invoice',['permissions' => $permissions, 'userEmail' => $userEmail]);
    return 'Got: '.$id;
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