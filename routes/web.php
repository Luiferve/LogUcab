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
        s.suc_nombre nombre, s.suc_codigo codigo, tel_numero as tel, suc_email as em
    from sucursal s, telefono
    where tel_sucursal is not NULL and tel_sucursal = suc_codigo
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
    select U.usu_codigo,usu_email,usu_password, rol_nombre
    from usuario U, usu_rol UR, rol
    where U.usu_codigo = UR.usu_usuario and rol_codigo = UR.usu_rol
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
                $users = Db::select('select usu_codigo cod from usuario where usu_email=\''.$_POST['email'].'\'')[0];
                $usu_rol = DB::insert('insert into usu_rol(usu_usuario,usu_rol) values('.$users->cod.',3)');
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
    $invoice = NULL;
    $completed = true;

    if ($_POST['receiverID'] == ''){
        $message = $message.'Campo receiverID Vacio. ';
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

        if ($message == ''){
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
            $cost = 0;
            $tipoE = DB:: select('select tip_costo from tipo_envio where tip_codigo='.$_POST['tipo-envio'])[0]->tip_costo;
            $tipoP = DB::select('select tip_costo from tipo_paquete where tip_codigo='.$_POST['tipo'])[0]->tip_costo;
            if ($_POST['peso'] >= 10){
                $cost = ($tipoP + $tipoE) * ($_POST['alto'] * $_POST['ancho'] * $_POST['profundidad']);
            } else {
                $cost = ($tipoP + $tipoE) * $_POST['peso'];
            }

            $shipment = DB::insert('insert into envio(env_fecha, env_cliente, env_empleado, env_suc_origen, env_suc_destino, env_ruta, env_pago) values (\''.date('d/m/Y').'\', '.$sender[0]->cedula.', '.$employee[0]->cod.', '.$_POST['origen'].', '.$_POST['destino'].', '.$route[0]->cod.', '.$payment.')');
            $shipment = DB::select('select env_codigo cod from envio order by env_codigo DESC limit 1');
            
            $package = DB::insert('insert into paquete(paq_peso, paq_ancho, paq_alto, paq_profundidad, paq_destinatario, paq_envio, paq_tipo_paquete, paq_tipo_envio) values ('.$_POST['peso'].', '.$_POST['ancho'].', '.$_POST['alto'].', '.$_POST['profundidad'].', '.$receiver[0]->cod.', '.$shipment[0]->cod.', '.$_POST['tipo'].', '.$_POST['tipo-envio'].')');
            $message = 'Envio Realizado por Bs. '.$cost.'.';
            $invoice = array('Ver Factura',$shipment[0]->cod);
            $_POST = NULL;
        }
    }
    
    $permissions = Cookie::get('permissions');
    return view('shipping',['permissions' => $permissions, 'userEmail' => $userEmail,'types' => $types ,'message' => $message, 'countries' => $countries, 'states' => $states,'franchises' => $franchises, 'invoice' => $invoice]);
});

Route::get('/print/{id}',function ($id){
    $shipment = DB::select('select * from envio where env_codigo='.$id)[0];
    $origin = DB::select('select suc_nombre sucursal,a.lug_nombre municipio, b.lug_nombre estado from sucursal,lugar a, lugar b where suc_codigo='.$shipment->env_suc_origen.' and suc_lugar=a.lug_codigo and a.lug_lugar=b.lug_codigo')[0];
    $destination = DB::select('select suc_nombre sucursal,a.lug_nombre municipio, b.lug_nombre estado from sucursal,lugar a, lugar b where suc_codigo='.$shipment->env_suc_destino.' and suc_lugar=a.lug_codigo and a.lug_lugar=b.lug_codigo')[0];
    $employee = DB::select('select * from empleado where emp_cedula='.$shipment->env_empleado)[0];
    $sender = DB::select('select *from cliente where cli_cedula='.$shipment->env_cliente)[0];
    $package = DB::select('select *,tip_tipo tipo from paquete,tipo_paquete where paq_tipo_paquete=tip_codigo and paq_envio='.$shipment->env_codigo)[0];
    $receiver = DB::select('select * from destinatario where des_codigo='.$package->paq_destinatario)[0];
    $payment = NULL;
    if ($shipment->env_pago != '') $payment = DB::select('select * from pago where pag_codigo='.$shipment->env_pago)[0];

    $cost = 0;
    $tipoE = DB:: select('select tip_costo from tipo_envio where tip_codigo='.$package->paq_tipo_envio)[0]->tip_costo;
    $tipoP = DB::select('select tip_costo from tipo_paquete where tip_codigo='.$package->paq_tipo_paquete)[0]->tip_costo;
    if ($package->paq_peso >= 10){
        $cost = ($tipoP + $tipoE) * ($package->paq_alto * $package->paq_ancho * $package->paq_profundidad);
    } else {
        $cost = ($tipoP + $tipoE) * $package->paq_peso;
    }

    $subtotal = $cost;
    if ($sender->cli_vip != '') $subtotal = $cost * 0.9;

    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    return view('invoice',['permissions' => $permissions, 'userEmail' => $userEmail,'shipment' => $shipment, 'employee' => $employee, 'sender' => $sender,'receiver' => $receiver, 'package' => $package, 'payment' => $payment, 'origin' => $origin, 'destination' => $destination, 'cost' => $cost, 'subtotal' => $subtotal]);
})->where('id', '[0-9]+');




Route::get('/locations/{id}',function ($id) {

    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    // return view('invoice',['permissions' => $permissions, 'userEmail' => $userEmail]);
    return 'Got: '.$id;
})->where('id', '[0-9]+');



Route::get('/locations/delete/{id}',function ($id) {
    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    // return view('invoice',['permissions' => $permissions, 'userEmail' => $userEmail]);
    return 'Got: '.$id;
})->where('id', '[0-9]+');


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

Route::get('/clients/{id}', function ($id) {
    $client = DB::select('select * from cliente where cli_cedula='.$id);
    $phone = DB::select('select tel_numero from telefono where tel_cliente='.$client[0]->cli_cedula);
    $location = DB::select('select * from lugar where lug_codigo='.$client[0]->cli_lugar);    
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');

    $permissions = Cookie::get('permissions');
    return view('client_registration',['client' => $client, 'phone' => $phone, 'location' => $location, 'states' => $states], ["permissions" => $permissions]);
})->where('id', '[0-9]+');

Route::post('/clients', function () {
    $location = DB::insert('insert into lugar(lug_nombre,lug_tipo,lug_lugar) values(\''.$_POST['direcc'].'\',\'Otro\','.$_POST['state'].')');
    $location = DB::select('select lug_codigo cod from lugar order by lug_codigo DESC limit 1');
    $client = DB::update('update cliente set cli_cedula='.$_POST['cedula'].', cli_nombre=\''.$_POST['firstName'].'\', cli_apellido=\''.$_POST['lastName'].'\', cli_f_nacimiento=\''.$_POST['fnac'].'\', cli_empresa=\''.$_POST['empresa'].'\', cli_lugar='.$location[0]->cod.', cli_carnet=\''.$_POST['carnet'].'\', cli_estado_civil=\''.$_POST['civil'].'\', cli_vip='.$_POST['vip'].', cli_email=\''.$_POST['email'].'\' where cli_cedula='.$_POST['cedula']);
    $phone = DB::select('select tel_numero numero from telefono where tel_numero=\''.$_POST['phoneNumber'].'\'');
    if (empty($phone)){
        $phone = DB::delete('delete from telefono where tel_cliente='.$_POST['cedula']);
        $phone = DB::insert('insert into telefono(tel_numero,tel_cliente) values(\''.$_POST['phoneNumber'].'\',\''.$_POST['cedula'].'\')');
        $phone = DB::select('select tel_numero numero from telefono where tel_numero=\''.$_POST['phoneNumber'].'\'');
    } else {
        $phone = DB::update('update telefono set tel_cliente='.$_POST['cedula'].' where tel_numero=\''.$_POST['phoneNumber'].'\'');
    }
    $message = 'Empleado actualizado exitosamente.';

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
    return view('clients_table', ["permissions" => $permissions, 'message' => $message,'clients' => $clients]);
});

Route::get('/clients/delete/{id}', function ($id) {
    $del = DB::delete('delete from cliente where cli_cedula='.$id);
    $query = <<<'EOD'
    select cli_cedula, cli_nombre || ' ' || cli_apellido as cli_nom,
        cli_email as cli_em, cli_f_nacimiento as cli_fn, lug_nombre as cli_lu,
        cli_carnet as cli_car 
    from cliente, lugar
    where
        cli_lugar = lug_codigo
EOD;
    $clients = DB::select($query);
    $message = 'Cliente Eliminado.';

    $permissions = Cookie::get('permissions');
    return view('clients_table',['clients' => $clients], ["permissions" => $permissions, 'message' => $message]);
})->where('id', '[0-9]+');

Route::get('/routes', function () {
    $query = <<<'EOD'
    select distinct rut_codigo as rut_c, s1.suc_nombre rut_o, s2.suc_nombre as rut_d, rut_duracion as rut_du,
    tip_costo as rut_cos
    from ruta, envio, paquete, tipo_envio, sucursal s1, sucursal s2
    where rut_codigo = env_ruta and env_codigo = paq_envio and paq_tipo_envio = tip_codigo and s1.suc_codigo = rut_suc_origen
    and s2.suc_codigo = rut_suc_destino order by rut_codigo DESC
        
EOD;
    $routes = DB::select($query);

    $permissions = Cookie::get('permissions');
    return view('routes_table',['routes' => $routes], ["permissions" => $permissions]);
});

Route::post('/routes', function () {
    $route = DB::update('update ruta set rut_duracion='.$_POST['duracion'].', rut_suc_origen='.$_POST['sucursalO'].', rut_suc_destino='.$_POST['sucursalD'].' where rut_codigo='.$_POST['codigo']);
    $message = 'Ruta actualizada exitosamente.';

    $query = <<<'EOD'
    select distinct rut_codigo as rut_c, s1.suc_nombre rut_o, s2.suc_nombre as rut_d, rut_duracion as rut_du,
    tip_costo as rut_cos
    from ruta, envio, paquete, tipo_envio, sucursal s1, sucursal s2
    where rut_codigo = env_ruta and env_codigo = paq_envio and paq_tipo_envio = tip_codigo and s1.suc_codigo = rut_suc_origen
    and s2.suc_codigo = rut_suc_destino order by rut_codigo DESC
        
EOD;
    $routes = DB::select($query);

    $permissions = Cookie::get('permissions');
    return view('routes_table',['routes' => $routes], ["permissions" => $permissions,'message' => $message]);
});

Route::get('/employees/{id}',function ($id) {
    $countries = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Pais\'');
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    
    $employee = DB::select('select * from empleado where emp_cedula='.$id);
    $location = DB::select('select * from lugar where lug_codigo='.$employee[0]->emp_lugar);
    $phone = DB::select('select * from telefono where tel_empleado='.$id);
    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    return view('employee_registration',['permissions' => $permissions, 'userEmail' => $userEmail,'franchises' => $franchises,'countries' => $countries,'states' => $states,'employee' => $employee,'location' => $location,'phone' => $phone]);
})->where('id', '[0-9]+');

Route::get('/employees/add',function () {
    $countries = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Pais\'');
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    return view('employee_registration',['permissions' => $permissions, 'userEmail' => $userEmail,'franchises' => $franchises,'countries' => $countries,'states' => $states,'add' => true]);
});

Route::post('/employees/add',function () {
    $message = NULL;
    if ($_POST['add'] != ''){
        $location = DB::insert('insert into lugar(lug_nombre,lug_tipo,lug_lugar) values(\''.$_POST['direcc'].'\',\'Otro\','.$_POST['state'].')');
        $location = DB::select('select lug_codigo cod from lugar order by lug_codigo DESC limit 1');
        $phone = DB::select('select tel_numero numero from telefono where tel_numero=\''.$_POST['phoneNumber'].'\'');
        $employee = DB::insert('insert into empleado(emp_cedula, emp_nombre, emp_apellido, emp_email_personal, emp_f_nacimiento, emp_f_ingreso, emp_num_hijos, emp_nivel_academico, emp_profesion, emp_lugar, emp_monto_base, emp_sucursal, emp_edo_civil) values ('.$_POST['cedula'].', \''.$_POST['firstName'].'\', \''.$_POST['lastName'].'\', \''.$_POST['email'].'\', \''.$_POST['fnac'].'\', \''.$_POST['fing'].'\', '.$_POST['hijos'].', \''.$_POST['academico'].'\', \''.$_POST['profesion'].'\', '.$location[0]->cod.', '.$_POST['base'].', '.$_POST['sucursal'].', \''.$_POST['civil'].'\')');
        if (empty($phone)){
            $phone = DB::insert('insert into telefono(tel_numero,tel_empleado) values(\''.$_POST['phoneNumber'].'\',\''.$_POST['cedula'].'\')');
            $phone = DB::select('select tel_numero numero from telefono where tel_numero=\''.$_POST['phoneNumber'].'\'');
        }
   
        $message = 'Empleado agregado exitosamente.';
    } else {
        $location = DB::insert('insert into lugar(lug_nombre,lug_tipo,lug_lugar) values(\''.$_POST['direcc'].'\',\'Otro\','.$_POST['state'].')');
        $location = DB::select('select lug_codigo cod from lugar order by lug_codigo DESC limit 1');
        $employee = DB::update('update empleado set emp_cedula='.$_POST['cedula'].', emp_nombre=\''.$_POST['firstName'].'\', emp_apellido=\''.$_POST['lastName'].'\', emp_email_personal=\''.$_POST['email'].'\', emp_f_nacimiento=\''.$_POST['fnac'].'\', emp_f_ingreso=\''.$_POST['fing'].'\', emp_num_hijos='.$_POST['hijos'].', emp_nivel_academico=\''.$_POST['academico'].'\', emp_profesion=\''.$_POST['profesion'].'\', emp_lugar='.$location[0]->cod.', emp_monto_base='.$_POST['base'].', emp_sucursal='.$_POST['sucursal'].', emp_edo_civil=\''.$_POST['civil'].'\' where emp_cedula='.$_POST['cedula']);
        $phone = DB::select('select tel_numero numero from telefono where tel_numero=\''.$_POST['phoneNumber'].'\'');
        if (empty($phone)){
            $phone = DB::insert('insert into telefono(tel_numero,tel_empleado) values(\''.$_POST['phoneNumber'].'\',\''.$_POST['cedula'].'\')');
            $phone = DB::select('select tel_numero numero from telefono where tel_numero=\''.$_POST['phoneNumber'].'\'');
        }
        $message = 'Empleado actualizado exitosamente.';
    }
    
    $countries = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Pais\'');
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    return view('employee_registration',['permissions' => $permissions, 'userEmail' => $userEmail,'franchises' => $franchises,'countries' => $countries,'states' => $states,'message' => $message]);
});

Route::get('/employees/delete/{id}',function ($id) {
    $del = DB::delete('delete from empleado where emp_cedula='.$id);
    $query = <<<'EOD'
    select emp_cedula, emp_nombre || ' ' || emp_apellido as emp_nombre,
        emp_email_personal as emp_ep, emp_email_coorporativo as emp_ec 
    from empleado
EOD;
    $employees = DB::select($query);
    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    $message = 'Empleado eliminado';
    return view('employees_table',['employees' => $employees], ["permissions" => $permissions,"userEmail" => $userEmail,'message' => $message]);
})->where('id', '[0-9]+');



Route::get('/franchiseReg', function () {
    $countries = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Pais\'');
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');
    $permissions = Cookie::get('permissions');

    return view('franchise_registration', ["permissions" => $permissions, 'countries' => $countries, 'states' => $states, 'add' => true] );
});

Route::post('/franchiseReg',function (){
    $message = NULL;
    if ($_POST['add'] != ''){
        
        $phone = DB::select('select tel_numero numero from telefono where tel_numero=\''.$_POST['phoneNumber'].'\'');
        $franchise = DB::insert('insert into sucursal(suc_nombre, suc_email, suc_lugar) values(\''.$_POST['name'].'\',\''.$_POST['email'].'\','.$_POST['state'].')');
        if (empty($phone)){
            $franchise = DB::select('select suc_codigo cod from sucursal order by suc_codigo DESC limit 1');
            $phone = DB::insert('insert into telefono(tel_numero,tel_sucursal) values(\''.$_POST['phoneNumber'].'\','.$franchise[0]->cod.')');
            $phone = DB::select('select tel_numero numero from telefono where tel_numero=\''.$_POST['phoneNumber'].'\'');
        }
   
        $message = 'Empleado agregado exitosamente.';
    }
    else {
        $location = DB::select('select lug_codigo cod from lugar order by lug_codigo DESC limit 1');
        $franchise = DB::update('update sucursal set suc_nombre = \''.$_POST['name'].'\' , suc_email = \''.$_POST['email'].'\', suc_lugar = '.$_POST['state'].' where suc_codigo = \''.$_POST['codigo'].'\' ');
        $phone = DB::select('select tel_numero numero from telefono where tel_numero=\''.$_POST['phoneNumber'].'\'');
        if (empty($phone)){
            $phone = DB::insert('insert into telefono(tel_numero,tel_sucursal) values(\''.$_POST['phoneNumber'].'\',\''.$_POST['codigo'].'\' )');
            $phone = DB::select('select tel_numero numero from telefono where tel_numero=\''.$_POST['phoneNumber'].'\'');
        }
        $message = 'Empleado actualizado exitosamente.';
    }
    $countries = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Pais\'');
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');
    $permissions = Cookie::get('permissions');
    return view('franchise_registration', ["permissions" => $permissions, 'countries' => $countries, 'states' => $states, 'message' => $message] );
});

Route::get('/franchises/delete/{id}',function ($id) {
    $del = DB::delete('delete from sucursal where suc_codigo='.$id);
    $query = <<<'EOD'
    select (select b.lug_nombre from lugar a, lugar b where s.suc_lugar = a.lug_codigo and a.lug_lugar = b.lug_codigo) estado,
        s.suc_nombre nombre, s.suc_codigo codigo, tel_numero as tel, suc_email as em
    from sucursal s, telefono
    where tel_sucursal is not NULL and tel_sucursal = suc_codigo
EOD;
    $franchises = DB::select($query);
    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    $message = 'sucursal eliminada';
    $userEmail = Cookie::get('user-email');
    return view('franchises_table',['franchises' => $franchises, 'permissions' => $permissions, 'userEmail' => $userEmail, 'message' => $message]);
})->where('id', '[0-9]+');


Route::get('/franchises/{id}',function ($id) {
    $countries = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Pais\'');
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');
    $phone = DB::select('select * from telefono where tel_sucursal='.$id);
    $franchise= DB::select('select * from sucursal where suc_codigo='.$id);
    $permissions = Cookie::get('permissions');
    return view('franchise_registration', ['permissions' => $permissions, 'countries' => $countries, 'states' => $states, 'franchise' =>$franchise, 'phone' => $phone] );
})->where('id', '[0-9]+');


Route::get('/routeReg', function () {
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    $permissions = Cookie::get('permissions');

    return view('route_registration', ["permissions" => $permissions, 'franchises' => $franchises, 'add' => true] );
});

Route::post('/routeReg',function (){
    $message = NULL;
    if ($_POST['add'] != ''){
        $routes = DB::insert('insert into ruta(rut_suc_origen, rut_suc_destino, rut_duracion) values(\''.$_POST['sucursalO'].'\',\''.$_POST['sucursalD'].'\','.$_POST['duracion'].')');
        $message = 'Ruta agregada exitosamente.';
    }
    else{
        $routes = DB:: update('update ruta set rut_suc_origen = \''.$_POST['sucursalO'].'\', rut_suc_destino = \''.$_POST['sucursalO'].'\', rut_duracion = \''.$_POST['duracion'].'\' where rut_codigo = \''.$_POST['codigo'].'\'');
        'Ruta actualizada exitosamente.';
    }
   
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    $permissions = Cookie::get('permissions');
    return view('route_registration', ["permissions" => $permissions, 'message' => $message, 'routes' => $routes, 'franchises' => $franchises] );
});

Route::get('/routes/delete/{id}',function ($id) {
    $del = DB::delete('delete from ruta where rut_codigo='.$id);
    $query = <<<'EOD'
    select distinct rut_codigo as rut_c, s1.suc_nombre rut_o, s2.suc_nombre as rut_d, rut_duracion as rut_du,
    tip_costo as rut_cos
    from ruta, envio, paquete, tipo_envio, sucursal s1, sucursal s2
    where rut_codigo = env_ruta and env_codigo = paq_envio and paq_tipo_envio = tip_codigo and s1.suc_codigo = rut_suc_origen
    and s2.suc_codigo = rut_suc_destino order by rut_codigo DESC
EOD;
    $routes = DB::select($query);
    $permissions = Cookie::get('permissions');
    $message = 'sucursal eliminado';
    return view('routes_table',['routes' => $routes, 'permissions' => $permissions, 'message' => $message]);
})->where('id', '[0-9]+');

Route::get('/routes/{id}',function ($id) {
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    $routes= DB::select('select * from ruta where rut_codigo='.$id);
    $permissions = Cookie::get('permissions');
    return view('route_registration', ['routes' => $routes, 'franchises' =>$franchises, 'permissions' => $permissions] );
})->where('id', '[0-9]+');

Route::get('/users/delete/{id}',function ($id) {
    $del = DB::delete('delete from usuario where usu_codigo='.$id);
    $query = <<<'EOD'
    select U.usu_codigo,usu_email,usu_password, rol_nombre
    from usuario U, usu_rol UR, rol
    where U.usu_codigo = UR.usu_usuario and rol_codigo = UR.usu_rol

EOD;
    $users = DB::select($query);
    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    $message = 'sucursal eliminado';
    return view('users_table',['users' => $users, 'permissions' => $permissions, 'userEmail' => $userEmail]);
})->where('id', '[0-9]+');



Route::post('/routeReg',function (){
    $message = NULL;
    if ($_POST['add'] != ''){
        $routes = DB::insert('insert into ruta(rut_suc_origen, rut_suc_destino, rut_duracion) values(\''.$_POST['sucursalO'].'\',\''.$_POST['sucursalD'].'\','.$_POST['duracion'].')');
        $message = 'Ruta agregada exitosamente.';
    }
    else{
        $routes = DB:: update('update ruta set rut_suc_origen = \''.$_POST['sucursalO'].'\', rut_suc_destino = \''.$_POST['sucursalD'].'\', rut_duracion = \''.$_POST['duracion'].'\' where rut_codigo = \''.$_POST['codigo'].'\'');
        'Ruta actualizada exitosamente.';
    }
   
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    $permissions = Cookie::get('permissions');
    return view('route_registration', ["permissions" => $permissions, 'message' => $message, 'franchises' => $franchises] );
});

Route::get('/usuario', function () {
    $rol = DB::select('select rol_codigo cod, rol_nombre nombre from rol');
    $permissions = Cookie::get('permissions');

    return view('user_registration', ["permissions" => $permissions, 'rol' => $rol, 'add' => true] );
});

Route::get('/users/delete/{id}',function ($id) {
    $del = DB::delete('delete from usuario where usu_codigo='.$id);
    $query = <<<'EOD'
    select U.usu_codigo,usu_email,usu_password, rol_nombre
    from usuario U, usu_rol UR, rol
    where U.usu_codigo = UR.usu_usuario and rol_codigo = UR.usu_rol

EOD;
    $users = DB::select($query);
    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    $message = 'sucursal eliminado';
    return view('users_table',['users' => $users, 'permissions' => $permissions, 'userEmail' => $userEmail]);
})->where('id', '[0-9]+');

Route::get('/users/{id}',function ($id) {
    $users= DB::select('select U.*, rol_codigo  from usuario U ,rol, usu_rol UR where U.usu_codigo = UR.usu_usuario and UR.usu_rol = rol_codigo and U.usu_codigo='.$id);
    $rol = DB::select('select rol_codigo cod, rol_nombre nombre from rol');
    $permissions = Cookie::get('permissions');
    $userEmail = Cookie::get('user-email');
    return view('user_registration',['users' => $users, 'rol' => $rol, 'permissions' => $permissions, 'userEmail' => $userEmail]);
})->where('id', '[0-9]+');


Route::post('/usuarioReg',function (){
    $message = NULL;
        $message = 'Usuario agregado exitosamente.';
        $users = DB:: update('update usuario set usu_email= \''.$_POST['email'].'\', usu_password = \''.$_POST['password'].'\'  where usu_codigo = \''.$_POST['codigo'].'\'');
        'Ruta actualizada exitosamente.';
    
    $rol = DB::select('select rol_codigo cod, rol_nombre nombre from rol');
    $permissions = Cookie::get('permissions');
    return view('user_registration', ["permissions" => $permissions, 'message' => $message, 'rol' => $rol] );
});

Route::get('/shipments', function (){
    $shipments = DB::select('select e.*,(select suc_nombre from sucursal where e.env_suc_origen=suc_codigo) origen,(select suc_nombre from sucursal where e.env_suc_destino=suc_codigo) destino, (select paq_peso from paquete where paq_envio=e.env_codigo) peso,(select tip_tipo from tipo_paquete t,paquete p where t.tip_codigo=p.paq_tipo_paquete and p.paq_envio=e.env_codigo) tipo_paquete,(select tip_tipo from tipo_envio t,paquete p where t.tip_codigo=p.paq_tipo_envio and p.paq_envio=e.env_codigo) tipo_envio from envio e');

    $permissions = Cookie::get('permissions');
    return view('shipments_table', ["permissions" => $permissions, 'shipments' => $shipments] );
});

Route::get('/shipments/{id}', function ($id){
    $shipment = DB::select('select * from envio where env_codigo='.$id)[0];
    $package = DB::select('select p.*,(select paq_estatus_paquete status from paq_est where paq_paquete=p.paq_guia) status from paquete p where p.paq_envio='.$id)[0];
    $statuses = DB::select('select * from estatus_paquete');
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');

    $permissions = Cookie::get('permissions');
    return view('shipment_modification',['permissions' => $permissions, 'shipment' => $shipment, 'package' => $package, 'franchises' => $franchises, 'statuses' => $statuses]);
})->where('id', '[0-9]+');

Route::post('/shipments/{id}', function ($id){
    $shipment = DB::update('update envio set env_cliente='.$_POST['cliente'].', env_empleado='.$_POST['empleado'].', env_suc_origen='.$_POST['origen'].', env_suc_destino='.$_POST['destino'].'	where env_codigo='.$id);
    $package = DB::update('update paquete set paq_peso='.$_POST['peso'].', paq_ancho='.$_POST['ancho'].', paq_alto='.$_POST['alto'].', paq_profundidad='.$_POST['profundidad'].' where paq_guia='.$_POST['paqcodigo']);
    $status = DB::update('update paq_est set paq_fecha=\''.date('d/m/Y').'\', paq_estatus_paquete='.$_POST['estatus'].' where paq_paquete='.$_POST['paqcodigo']);

    $shipments = DB::select('select e.*,(select suc_nombre from sucursal where e.env_suc_origen=suc_codigo) origen,(select suc_nombre from sucursal where e.env_suc_destino=suc_codigo) destino, (select paq_peso from paquete where paq_envio=e.env_codigo) peso,(select tip_tipo from tipo_paquete t,paquete p where t.tip_codigo=p.paq_tipo_paquete and p.paq_envio=e.env_codigo) tipo_paquete,(select tip_tipo from tipo_envio t,paquete p where t.tip_codigo=p.paq_tipo_envio and p.paq_envio=e.env_codigo) tipo_envio from envio e');

    $permissions = Cookie::get('permissions');
    $message = 'Envio actualizado exitosamente';
    return view('shipments_table', ["permissions" => $permissions, 'shipments' => $shipments, 'message' => $message] );
})->where('id', '[0-9]+');

Route::get('/shipments/delete/{id}', function ($id){
    $shipment = DB::delete('delete from envio where env_codigo='.$id);
    
    $shipments = DB::select('select e.*,(select suc_nombre from sucursal where e.env_suc_origen=suc_codigo) origen,(select suc_nombre from sucursal where e.env_suc_destino=suc_codigo) destino, (select paq_peso from paquete where paq_envio=e.env_codigo) peso,(select tip_tipo from tipo_paquete t,paquete p where t.tip_codigo=p.paq_tipo_paquete and p.paq_envio=e.env_codigo) tipo_paquete,(select tip_tipo from tipo_envio t,paquete p where t.tip_codigo=p.paq_tipo_envio and p.paq_envio=e.env_codigo) tipo_envio from envio e');

    $permissions = Cookie::get('permissions');
    $message = 'Envio eliminado exitosamente';
    return view('shipments_table', ["permissions" => $permissions, 'shipments' => $shipments, 'message' => $message] );
})->where('id', '[0-9]+');