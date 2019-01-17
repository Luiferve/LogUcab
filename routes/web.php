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
    $permissions = json_decode(Cookie::get('permissions'));
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

    audit(2,'Tabla de sucursales');
    $permissions = json_decode(Cookie::get('permissions'));
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

    audit(2,'Tabla de lugares');
    $permissions = json_decode(Cookie::get('permissions'));
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

    audit(2,'Tabla de usuarios');
    $permissions = json_decode(Cookie::get('permissions'));
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

    audit(2,'Tabla de empleados');
    $permissions = json_decode(Cookie::get('permissions'));
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
                audit(1,'Registro de usuario ('.$_POST['email'].')',$_POST['email']);
                
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
            
            $permissions = DB::select('select p.rol_privilegio pri from usuario u ,usu_rol r ,rol_pri p where u.usu_email=\''.$_POST['email'].'\' and u.usu_codigo=r.usu_usuario and r.usu_rol=p.rol_rol');
            $p = array();

            foreach ($permissions as $i){
                $p[]=$i->pri;
            }

            Cookie::queue('permissions', json_encode($p), 45000);
            Cookie::queue('user-email',$_POST['email'],45000);
            $redirect = true;

            audit(2,'Inicio de sesion ('.$_POST['email'].')',$_POST['email']);
        }
    }
    
    return view('login',['users' => $users, 'message' => $message, 'redirect' => $redirect]);
});

Route::get('/logout', function () {
    audit(2,'Cierre de sesion');
    Cookie::forget('permissions');
    Cookie::forget('user-email');

    return view('index',['permissions' => NULL]);
});

Route::get('/ship', function () {
    $types = DB::select('select tip_codigo cod, tip_tipo nombre from tipo_paquete');
    $countries = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Pais\'');
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    
    $permissions = json_decode(Cookie::get('permissions'));
    $userEmail = Cookie::get('user-email');
    return view('shipping',['permissions' => $permissions, 'userEmail' => $userEmail,'types' => $types, 'countries' => $countries, 'states' => $states,'franchises' => $franchises]);
});

Route::post('/ship', function () {
    updateVIP();
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


        $route = DB::select('select rut_codigo cod,rut_duracion from ruta where rut_suc_origen='.$_POST['origen'].' and rut_suc_destino='.$_POST['destino']);
        if (empty($route)){
            $route = DB::insert('insert into ruta(rut_duracion, rut_suc_origen, rut_suc_destino, rut_med_transporte) values('.rand(20,120).', '.$_POST['origen'].', '.$_POST['destino'].', 2)');
            $route = DB::select('select rut_codigo cod,rut_duracion from ruta where rut_suc_origen='.$_POST['origen'].' and rut_suc_destino='.$_POST['destino']);
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
    
                $payment = DB::insert('insert into pago(pag_tipo, pag_fecha '.$method[0].') values (\''.$_POST['tipo-pago'].'\', current_date '.$method[1].' )');
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
            
            $package = DB::insert('insert into paquete(paq_peso, paq_ancho, paq_alto, paq_profundidad, paq_destinatario, paq_envio, paq_tipo_paquete, paq_tipo_envio,paq_eta) values ('.$_POST['peso'].', '.$_POST['ancho'].', '.$_POST['alto'].', '.$_POST['profundidad'].', '.$receiver[0]->cod.', '.$shipment[0]->cod.', '.$_POST['tipo'].', '.$_POST['tipo-envio'].',current_date + make_interval(hours := '.$route[0]->rut_duracion.'))');
            $package = DB::select('select paq_guia from paquete where paq_envio='.$shipment[0]->cod);
            $status = DB::insert('insert into paq_est (paq_fecha,paq_estatus_paquete,paq_paquete) values(current_date,1,'.$package[0]->paq_guia.')');
            $message = 'Envio Realizado por Bs. '.$cost.'.';
            $invoice = array('Ver Factura',$shipment[0]->cod);
            $_POST = NULL;

            audit(1,'Envio realizado ('.$shipment[0]->cod.')');
        }
    }
    
    updateVIP();
    $permissions = json_decode(Cookie::get('permissions'));
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

    audit(2,'Impresion de factura ('.$id.')');
    $permissions = json_decode(Cookie::get('permissions'));
    $userEmail = Cookie::get('user-email');
    return view('invoice',['permissions' => $permissions, 'userEmail' => $userEmail,'shipment' => $shipment, 'employee' => $employee, 'sender' => $sender,'receiver' => $receiver, 'package' => $package, 'payment' => $payment, 'origin' => $origin, 'destination' => $destination, 'cost' => $cost, 'subtotal' => $subtotal]);
})->where('id', '[0-9]+');




Route::get('/locations/{id}',function ($id) {

    $permissions = json_decode(Cookie::get('permissions'));
    $userEmail = Cookie::get('user-email');
    // return view('invoice',['permissions' => $permissions, 'userEmail' => $userEmail]);
    return 'Got: '.$id;
})->where('id', '[0-9]+');



Route::get('/locations/delete/{id}',function ($id) {
    $permissions = json_decode(Cookie::get('permissions'));
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

    audit(2,'Tabla de clientes');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('clients_table',['clients' => $clients], ["permissions" => $permissions]);
});

Route::get('/clients/{id}', function ($id) {
    $client = DB::select('select * from cliente where cli_cedula='.$id);
    $phone = DB::select('select tel_numero from telefono where tel_cliente='.$client[0]->cli_cedula);
    $location = DB::select('select * from lugar where lug_codigo='.$client[0]->cli_lugar);    
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');

    $permissions = json_decode(Cookie::get('permissions'));
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

    audit(3,'Cliente modificado ('.$_POST['cedula'].')');
    $permissions = json_decode(Cookie::get('permissions'));
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

    audit(4,'Cliente eliminado ('.$id.')');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('clients_table',['clients' => $clients], ["permissions" => $permissions, 'message' => $message]);
})->where('id', '[0-9]+');

Route::get('/routes', function () {
    $query = <<<'EOD'
    select rut_codigo rut_c, s1.suc_nombre rut_o, s2.suc_nombre rut_d, rut_duracion rut_du
    from ruta, sucursal s1, sucursal s2
    where rut_suc_origen=s1.suc_codigo and rut_suc_destino=s2.suc_codigo 
        
EOD;
    $routes = DB::select($query);

    audit(2,'Tabla de Rutas');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('routes_table',['routes' => $routes], ["permissions" => $permissions]);
});

Route::post('/routes', function () {
    $message = NULL;
    if ($_POST['add'] != ''){
        $routes = DB::insert('insert into ruta(rut_suc_origen, rut_suc_destino, rut_duracion,rut_med_transporte) values(\''.$_POST['sucursalO'].'\',\''.$_POST['sucursalD'].'\','.$_POST['duracion'].',2)');
        $message = 'Ruta agregada exitosamente.';
        audit(1,'Ruta nueva');
    }
    else{
        $routes = DB:: update('update ruta set rut_suc_origen = \''.$_POST['sucursalO'].'\', rut_suc_destino = \''.$_POST['sucursalO'].'\', rut_duracion = \''.$_POST['duracion'].'\' where rut_codigo = '.$_POST['codigo']);
        $message = 'Ruta actualizada exitosamente.';
        audit(3,'Modificacion de Ruta ('.$_POST['codigo'].')');
    }

    // $route = DB::update('update ruta set rut_duracion='.$_POST['duracion'].', rut_suc_origen='.$_POST['sucursalO'].', rut_suc_destino='.$_POST['sucursalD'].' where rut_codigo='.$_POST['codigo']);
    // $message = 'Ruta actualizada exitosamente.';

    $query = <<<'EOD'
    select rut_codigo rut_c, s1.suc_nombre rut_o, s2.suc_nombre rut_d, rut_duracion rut_du
    from ruta, sucursal s1, sucursal s2
    where rut_suc_origen=s1.suc_codigo and rut_suc_destino=s2.suc_codigo 
        
EOD;
    $routes = DB::select($query);

    $permissions = json_decode(Cookie::get('permissions'));
    return view('routes_table',['routes' => $routes], ["permissions" => $permissions,'message' => $message]);
});

Route::get('/employees/{id}',function ($id) {
    $countries = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Pais\'');
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    
    $employee = DB::select('select * from empleado where emp_cedula='.$id);
    $location = DB::select('select * from lugar where lug_codigo='.$employee[0]->emp_lugar);
    $phone = DB::select('select * from telefono where tel_empleado='.$id);
    $permissions = json_decode(Cookie::get('permissions'));
    $userEmail = Cookie::get('user-email');
    return view('employee_registration',['permissions' => $permissions, 'userEmail' => $userEmail,'franchises' => $franchises,'countries' => $countries,'states' => $states,'employee' => $employee,'location' => $location,'phone' => $phone]);
})->where('id', '[0-9]+');

Route::get('/employees/add',function () {
    $countries = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Pais\'');
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    $permissions = json_decode(Cookie::get('permissions'));
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
        audit(1,'Empleado nuevo ('.$_POST['cedula'].')');
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
        audit(3,'Empleado modificado ('.$_POST['cedula'].')');
    }
    
    $countries = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Pais\'');
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    $permissions = json_decode(Cookie::get('permissions'));
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

    audit(4,'Empleado eliminado ('.$id.')');
    $permissions = json_decode(Cookie::get('permissions'));
    $userEmail = Cookie::get('user-email');
    $message = 'Empleado eliminado';
    return view('employees_table',['employees' => $employees], ["permissions" => $permissions,"userEmail" => $userEmail,'message' => $message]);
})->where('id', '[0-9]+');



Route::get('/franchiseReg', function () {
    $countries = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Pais\'');
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');
    $permissions = json_decode(Cookie::get('permissions'));

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
   
        $message = 'Sucursal agregado exitosamente.';
        audit(1,'Nueva sucursal ('.$franchise[0]->cod.')');
    }
    else {
        $location = DB::select('select lug_codigo cod from lugar order by lug_codigo DESC limit 1');
        $franchise = DB::update('update sucursal set suc_nombre = \''.$_POST['name'].'\' , suc_email = \''.$_POST['email'].'\', suc_lugar = '.$_POST['state'].' where suc_codigo = \''.$_POST['codigo'].'\' ');
        $phone = DB::select('select tel_numero numero from telefono where tel_numero=\''.$_POST['phoneNumber'].'\'');
        if (empty($phone)){
            $phone = DB::insert('insert into telefono(tel_numero,tel_sucursal) values(\''.$_POST['phoneNumber'].'\',\''.$_POST['codigo'].'\' )');
            $phone = DB::select('select tel_numero numero from telefono where tel_numero=\''.$_POST['phoneNumber'].'\'');
        }
        $message = 'Sucurlal actualizado exitosamente.';
        audit(3,'Sucursal modificado ('.$_POST['codigo'].')');
    }
    $countries = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Pais\'');
    $states = DB::select('select lug_codigo cod, lug_nombre nombre from lugar where lug_tipo=\'Estado\'');
    $permissions = json_decode(Cookie::get('permissions'));
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

    audit(4,'Sucursal eliminada ('.$id.')');
    $permissions = json_decode(Cookie::get('permissions'));
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
    $permissions = json_decode(Cookie::get('permissions'));
    return view('franchise_registration', ['permissions' => $permissions, 'countries' => $countries, 'states' => $states, 'franchise' =>$franchise, 'phone' => $phone] );
})->where('id', '[0-9]+');


Route::get('/routeReg', function () {
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    $permissions = json_decode(Cookie::get('permissions'));

    return view('route_registration', ["permissions" => $permissions, 'franchises' => $franchises, 'add' => true] );
});

Route::get('/routes/delete/{id}',function ($id) {
    $del = DB::delete('delete from ruta where rut_codigo='.$id);
    $query = <<<'EOD'
    select rut_codigo rut_c, s1.suc_nombre rut_o, s2.suc_nombre rut_d, rut_duracion rut_du
    from ruta, sucursal s1, sucursal s2
    where rut_suc_origen=s1.suc_codigo and rut_suc_destino=s2.suc_codigo 
EOD;
    $routes = DB::select($query);

    audit(4,'Ruta eliminada ('.$id.')');
    $permissions = json_decode(Cookie::get('permissions'));
    $message = 'Ruta eliminada exitosamente.';
    return view('routes_table',['routes' => $routes, 'permissions' => $permissions, 'message' => $message]);
})->where('id', '[0-9]+');

Route::get('/routes/{id}',function ($id) {
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    $routes= DB::select('select * from ruta where rut_codigo='.$id);
    $permissions = json_decode(Cookie::get('permissions'));
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

    audit(4,'Usuario eliminado ('.$id.')');
    $permissions = json_decode(Cookie::get('permissions'));
    $userEmail = Cookie::get('user-email');
    $message = 'sucursal eliminado';
    return view('users_table',['users' => $users, 'permissions' => $permissions, 'userEmail' => $userEmail]);
})->where('id', '[0-9]+');



Route::post('/routeReg',function (){
    $message = NULL;
    if ($_POST['add'] != ''){
        $routes = DB::insert('insert into ruta(rut_suc_origen, rut_suc_destino, rut_duracion) values(\''.$_POST['sucursalO'].'\',\''.$_POST['sucursalD'].'\','.$_POST['duracion'].')');
        $message = 'Ruta agregada exitosamente.';
        audit(1,'Nueva ruta');
    }
    else{
        $routes = DB:: update('update ruta set rut_suc_origen = \''.$_POST['sucursalO'].'\', rut_suc_destino = \''.$_POST['sucursalD'].'\', rut_duracion = \''.$_POST['duracion'].'\' where rut_codigo = \''.$_POST['codigo'].'\'');
        $message = 'Ruta actualizada exitosamente.';
        audit(3,'Ruta modificada ('.$_POST['codigo'].')');
    }
   
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('route_registration', ["permissions" => $permissions, 'message' => $message, 'franchises' => $franchises] );
});

Route::get('/usuario', function () {
    $rol = DB::select('select rol_codigo cod, rol_nombre nombre from rol');
    $permissions = json_decode(Cookie::get('permissions'));

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

    audit(4,'Usuario eliminado ('.$id.')');
    $permissions = json_decode(Cookie::get('permissions'));
    $userEmail = Cookie::get('user-email');
    $message = 'sucursal eliminado';
    return view('users_table',['users' => $users, 'permissions' => $permissions, 'userEmail' => $userEmail]);
})->where('id', '[0-9]+');

Route::get('/users/{id}',function ($id) {
    $users= DB::select('select U.*, rol_codigo  from usuario U ,rol, usu_rol UR where U.usu_codigo = UR.usu_usuario and UR.usu_rol = rol_codigo and U.usu_codigo='.$id);
    $rol = DB::select('select rol_codigo cod, rol_nombre nombre from rol');
    $permissions = json_decode(Cookie::get('permissions'));
    $userEmail = Cookie::get('user-email');
    return view('user_registration',['users' => $users, 'rol' => $rol, 'permissions' => $permissions, 'userEmail' => $userEmail]);
})->where('id', '[0-9]+');


Route::post('/usuarioReg',function (){
    $users = DB::update('update usuario set usu_email= \''.$_POST['email'].'\', usu_password = \''.$_POST['password'].'\'  where usu_codigo ='.$_POST['codigo']);
    $rol = DB::update('update usu_rol set usu_rol='.$_POST['rol'].' where usu_usuario='.$_POST['codigo']);
    $message = 'Usuario actualizado exitosamente.';
    
    audit(3,'Usuario modificado ('.$_POST['codigo'].')');
    $rol = DB::select('select rol_codigo cod, rol_nombre nombre from rol');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('user_registration', ["permissions" => $permissions, 'message' => $message, 'rol' => $rol] );
});

Route::get('/shipments', function (){
    $shipments = DB::select('select e.*,(select suc_nombre from sucursal where e.env_suc_origen=suc_codigo) origen,(select suc_nombre from sucursal where e.env_suc_destino=suc_codigo) destino, (select paq_peso from paquete where paq_envio=e.env_codigo) peso,(select tip_tipo from tipo_paquete t,paquete p where t.tip_codigo=p.paq_tipo_paquete and p.paq_envio=e.env_codigo) tipo_paquete,(select tip_tipo from tipo_envio t,paquete p where t.tip_codigo=p.paq_tipo_envio and p.paq_envio=e.env_codigo) tipo_envio from envio e');

    audit(2,'Tabla de envios');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('shipments_table', ["permissions" => $permissions, 'shipments' => $shipments] );
});

Route::get('/shipments/{id}', function ($id){
    $shipment = DB::select('select * from envio where env_codigo='.$id)[0];
    $package = DB::select('select p.*,(select paq_estatus_paquete status from paq_est where paq_paquete=p.paq_guia) status from paquete p where p.paq_envio='.$id)[0];
    $statuses = DB::select('select * from estatus_paquete');
    $franchises = DB::select('select suc_codigo cod, suc_nombre nombre from sucursal');

    $permissions = json_decode(Cookie::get('permissions'));
    return view('shipment_modification',['permissions' => $permissions, 'shipment' => $shipment, 'package' => $package, 'franchises' => $franchises, 'statuses' => $statuses]);
})->where('id', '[0-9]+');

Route::post('/shipments/{id}', function ($id){
    $shipment = DB::update('update envio set env_cliente='.$_POST['cliente'].', env_empleado='.$_POST['empleado'].', env_suc_origen='.$_POST['origen'].', env_suc_destino='.$_POST['destino'].'	where env_codigo='.$id);
    $package = DB::update('update paquete set paq_peso='.$_POST['peso'].', paq_ancho='.$_POST['ancho'].', paq_alto='.$_POST['alto'].', paq_profundidad='.$_POST['profundidad'].' where paq_guia='.$_POST['paqcodigo']);
    $status = DB::update('update paq_est set paq_fecha=\''.date('d/m/Y').'\', paq_estatus_paquete='.$_POST['estatus'].' where paq_paquete='.$_POST['paqcodigo']);

    $shipments = DB::select('select e.*,(select suc_nombre from sucursal where e.env_suc_origen=suc_codigo) origen,(select suc_nombre from sucursal where e.env_suc_destino=suc_codigo) destino, (select paq_peso from paquete where paq_envio=e.env_codigo) peso,(select tip_tipo from tipo_paquete t,paquete p where t.tip_codigo=p.paq_tipo_paquete and p.paq_envio=e.env_codigo) tipo_paquete,(select tip_tipo from tipo_envio t,paquete p where t.tip_codigo=p.paq_tipo_envio and p.paq_envio=e.env_codigo) tipo_envio from envio e');
    $message = 'Envio actualizado exitosamente';

    audit(3,'Envio modificado ('.$id.')');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('shipments_table', ["permissions" => $permissions, 'shipments' => $shipments, 'message' => $message] );
})->where('id', '[0-9]+');

Route::get('/shipments/delete/{id}', function ($id){
    $shipment = DB::delete('delete from envio where env_codigo='.$id);
    
    $shipments = DB::select('select e.*,(select suc_nombre from sucursal where e.env_suc_origen=suc_codigo) origen,(select suc_nombre from sucursal where e.env_suc_destino=suc_codigo) destino, (select paq_peso from paquete where paq_envio=e.env_codigo) peso,(select tip_tipo from tipo_paquete t,paquete p where t.tip_codigo=p.paq_tipo_paquete and p.paq_envio=e.env_codigo) tipo_paquete,(select tip_tipo from tipo_envio t,paquete p where t.tip_codigo=p.paq_tipo_envio and p.paq_envio=e.env_codigo) tipo_envio from envio e');

    audit(4,'Envio eliminado ('.$id.')');
    $permissions = json_decode(Cookie::get('permissions'));
    $message = 'Envio eliminado exitosamente';
    return view('shipments_table', ["permissions" => $permissions, 'shipments' => $shipments, 'message' => $message] );
})->where('id', '[0-9]+');

Route::get('/report/omsrp', function () {
    $sended = DB::select('select count(e.*) count,s.suc_nombre nombre from envio e,sucursal s where e.env_suc_origen=s.suc_codigo group by nombre order by count(*) DESC limit 1')[0];
    $received = DB::select('select count(e.*) count,s.suc_nombre nombre from envio e,sucursal s where e.env_suc_destino=s.suc_codigo group by nombre order by count(*) DESC limit 1')[0];

    audit(2,'Reporte Oficina con mas paquetes enviados y recibidos');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('omsrp', ["permissions" => $permissions, "sended" => $sended, "received" => $received] );
});

Route::get('/report/mlur', function() {
    $most = DB::select('select env_ruta cod,count(*) uses, s1.suc_nombre o, s2.suc_nombre d from envio, ruta r,sucursal s1, sucursal s2 where env_ruta=r.rut_codigo and r.rut_suc_origen=s1.suc_codigo and r.rut_suc_destino=s2.suc_codigo group by env_ruta, s1.suc_nombre,s2.suc_nombre order by count(*) desc limit 1')[0];
    $least = DB::select('select env_ruta cod,count(*) uses, s1.suc_nombre o, s2.suc_nombre d from envio, ruta r,sucursal s1, sucursal s2 where env_ruta=r.rut_codigo and r.rut_suc_origen=s1.suc_codigo and r.rut_suc_destino=s2.suc_codigo group by env_ruta, s1.suc_nombre,s2.suc_nombre order by count(*) asc limit 1')[0];

    audit(2,'Reporte Ruta mas y menos utilizada');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('mlur', ["permissions" => $permissions, "most" => $most, "least" => $least] );
});

Route::get('/packages', function () {
    $packages = DB::select('select p.*,e.est_nombre estatus,paq_fecha fecha from paquete p,paq_est pe, estatus_paquete e where p.paq_guia=pe.paq_paquete and pe.paq_estatus_paquete=e.est_codigo');

    audit(2,'Tabla de paquetes');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('packages_table', ["permissions" => $permissions,'packages' => $packages] );
});

Route::post('/packages', function () {
    $package = DB::update('update paquete set paq_peso='.$_POST['peso'].', paq_ancho='.$_POST['ancho'].', paq_alto='.$_POST['alto'].', paq_profundidad='.$_POST['profundidad'].' where paq_guia='.$_POST['paqcodigo']);
    $status = DB::insert('insert into paq_est (paq_fecha,paq_estatus_paquete,paq_paquete) values(current_date,'.$_POST['estatus'].','.$_POST['paqcodigo'].')');
    
    $packages = DB::select('select p.*,e.est_nombre estatus from paquete p,paq_est pe, estatus_paquete e where p.paq_guia=pe.paq_paquete and pe.paq_estatus_paquete=e.est_codigo');
    $message = 'Paquete modificado exitosamente';

    audit(3,'Paquete modificado ('.$_POST['paqcodigo'].')');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('packages_table', ["permissions" => $permissions,'packages' => $packages, 'message' => $message] );
});

Route::get('/packages/delete/{id}', function ($id) {
    $package = DB::delete('delete from paquete where paq_guia='.$id);
    $packages = DB::select('select p.*,e.est_nombre estatus from paquete p,paq_est pe, estºatus_paquete e where p.paq_guia=pe.paq_paquete and pe.paq_estatus_paquete=e.est_codigo');
    $message = 'Paquete eliminado exitosamente.';

    audit(4,'Paquete eliminado ('.$id.')');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('packages_table', ["permissions" => $permissions,'packages' => $packages, 'message' => $message] );
})->where('id', '[0-9]+');

Route::get('/packages/{id}',function ($id) {
    $package = DB::select('select p.*,(select paq_estatus_paquete from paq_est where paq_paquete=p.paq_guia) status from paquete p where p.paq_guia='.$id)[0];
    $statuses = DB::select('select * from estatus_paquete');

    $permissions = json_decode(Cookie::get('permissions'));
    return view('package_registration', ["permissions" => $permissions,'package' => $package, 'statuses' => $statuses] );
})->where('id', '[0-9]+');

Route::get('/logs', function (){
    $logs = DB::select('select aud_codigo cod, aud_descripcion des, aud_fecha fecha, usu_email use,acc_nombre acc,rol_nombre rol from auditoria,usuario,accion,rol where aud_usuario=usu_codigo and aud_accion=acc_codigo and aud_rol=rol_codigo');

    audit(2,'Auditorias');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('logs', ["permissions" => $permissions, 'logs' => $logs] );
});

Route::get('/attendance', function () {
    $permissions = json_decode(Cookie::get('permissions'));
    return view('attendance', ["permissions" => $permissions] );
});

Route::post('/attendance', function (){
    $employee = DB::select('select * from empleado where emp_cedula='.$_POST['cedula']);
    $message = NULL;

    if (empty($employee)){
        $message = 'El empleado no esta registrado en el sistema';
    } else {
        $emp_zon_hor = DB::select('select * from emp_zon_hor where emp_zona_empleado='.$_POST['cedula'])[0];
        $attendance = DB::insert('insert into asistencia(asi_fecha, asi_emp_codigo, asi_emp_zon_codigo, asi_emp_zona_empleado, asi_emp_zona_zona, asi_emp_zona_sucursal, asi_emp_horario) values (CURRENT_DATE, '.$emp_zon_hor->emp_codigo.', '.$emp_zon_hor->emp_zon_codigo.', '.$emp_zon_hor->emp_zona_empleado.', '.$emp_zon_hor->emp_zona_zona.', '.$emp_zon_hor->emp_zona_sucursal.', '.date('N').');');

        $message = 'Asistencia marcada';
        audit(1,'Asistencia marcada (Empleado: '.$_POST['cedula'].')');
    }

    $permissions = json_decode(Cookie::get('permissions'));
    return view('attendance', ["permissions" => $permissions, 'message' => $message] );
});

Route::get('/roles', function() {
    $roles = DB::select('select rol_codigo cod, rol_nombre nombre from rol');

    audit(2,'Tabla de roles');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('roles', ["permissions" => $permissions, 'roles' => $roles] );
});

Route::get('/roles/{id}', function($id) {
    $permissions = json_decode(Cookie::get('permissions'));
    if ($id == 0) return view('roles_reg', ["permissions" => $permissions]);

    $rol = DB::select('select * from rol where rol_codigo='.$id)[0];
    $p = DB::select('select rol_privilegio pri from rol_pri where rol_rol='.$id);
    $perm = array();
    foreach ($p as $p){
        $perm[] = $p->pri;
    }

    audit(2,'Consulta rol ('.$id.')');
    return view('roles_reg', ["permissions" => $permissions, 'rol' => $rol, 'perm' => $perm] );
})->where('id', '[0-9]+');

Route::post('/roles/{id}', function($id) {
    $message = NULL;
    if ($id == 0){
        $rol = DB::insert('insert into rol (rol_nombre) values (\''.$_POST['nombre'].'\')');
        $rol = DB::select('select * from rol where rol_nombre=\''.$_POST['nombre'].'\'')[0];

        if (array_key_exists('agregar',$_POST)){
            $pri = DB::insert('insert into rol_pri (rol_rol,rol_privilegio) values ('.$rol->rol_codigo.',1)');
        }
        if (array_key_exists('modificar',$_POST)){
            $pri = DB::insert('insert into rol_pri (rol_rol,rol_privilegio) values ('.$rol->rol_codigo.',2)');
        }
        if (array_key_exists('eliminar',$_POST)){
            $pri = DB::insert('insert into rol_pri (rol_rol,rol_privilegio) values ('.$rol->rol_codigo.',3)');
        }
        if (array_key_exists('consultar',$_POST)){
            $pri = DB::insert('insert into rol_pri (rol_rol,rol_privilegio) values ('.$rol->rol_codigo.',4)');
        }

        $message = 'Nuevo rol creado';
        audit(1,'Nuevo rol ('.$rol->rol_codigo.')');
    } else {
        $rol = DB::update('update rol set rol_nombre=\''.$_POST['nombre'].'\' where rol_codigo='.$id);
        $rol = DB::select('select * from rol where rol_nombre=\''.$_POST['nombre'].'\'')[0];

        if (array_key_exists('agregar',$_POST)){
            $pri = DB::select('select * from rol_pri where rol_rol='.$rol->rol_codigo.' and rol_privilegio=1');
            if (empty($pri)){
                $pri = DB::insert('insert into rol_pri (rol_rol,rol_privilegio) values ('.$rol->rol_codigo.',1)');
            }
        } else {
            $pri = DB::delete('delete from rol_pri where rol_rol='.$rol->rol_codigo.' and rol_privilegio=1');
        }
        if (array_key_exists('modificar',$_POST)){
            $pri = DB::select('select * from rol_pri where rol_rol='.$rol->rol_codigo.' and rol_privilegio=2');
            if (empty($pri)){
                $pri = DB::insert('insert into rol_pri (rol_rol,rol_privilegio) values ('.$rol->rol_codigo.',2)');
            }
        } else {
            $pri = DB::delete('delete from rol_pri where rol_rol='.$rol->rol_codigo.' and rol_privilegio=2');
        }
        if (array_key_exists('eliminar',$_POST)){
            $pri = DB::select('select * from rol_pri where rol_rol='.$rol->rol_codigo.' and rol_privilegio=3');
            if (empty($pri)){
                $pri = DB::insert('insert into rol_pri (rol_rol,rol_privilegio) values ('.$rol->rol_codigo.',3)');
            }
        } else {
            $pri = DB::delete('delete from rol_pri where rol_rol='.$rol->rol_codigo.' and rol_privilegio=3');
        }
        if (array_key_exists('consultar',$_POST)){
            $pri = DB::select('select * from rol_pri where rol_rol='.$rol->rol_codigo.' and rol_privilegio=4');
            if (empty($pri)){
                $pri = DB::insert('insert into rol_pri (rol_rol,rol_privilegio) values ('.$rol->rol_codigo.',4)');
            }
        } else {
            $pri = DB::delete('delete from rol_pri where rol_rol='.$rol->rol_codigo.' and rol_privilegio=4');
        }

        $message = 'Rol modificado exitosamente';
        audit(3,'Rol modificado ('.$rol->rol_codigo.')');
    }

    $permissions = json_decode(Cookie::get('permissions'));
    return view('roles_reg', ["permissions" => $permissions, 'message' => $message] );
})->where('id', '[0-9]+');

Route::get('/roles/delete/{id}', function($id) {
    $rol = DB::delete('delete from rol where rol_codigo='.$id);
    $message = 'Rol eliminado satisfactoriamente';

    $roles = DB::select('select rol_codigo cod, rol_nombre nombre from rol');

    audit(4,'Rol eliminado');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('roles', ["permissions" => $permissions, 'roles' => $roles, 'message' => $message] );
})->where('id', '[0-9]+');

Route::get('/report/port-franchises', function() {
    $franchises = DB::select('select aer_codigo cod,suc_nombre from aeropuerto,sucursal where aer_sucursal=suc_codigo union select pue_codigo cod,suc_nombre from puerto,sucursal where pue_sucursal=suc_codigo');

    audit(2,'Reporte Sucursales en Puertos y Aeropuertos');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('port', ["permissions" => $permissions] );
});

Route::get('/report/avg-weight', function() {
    $franchises = DB::select('select suc_nombre nombre, avg(paq_peso) avg from envio,paquete,sucursal where paq_envio=env_codigo and env_suc_origen=suc_codigo group by suc_nombre');

    audit(2,'Reporte Peso promedio de los paquetes enviados por oficina');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('avg_weight', ["permissions" => $permissions,'franchises' => $franchises] );
});

Route::get('/report/airplanes', function(){
    $airplanes = DB::select('select * from medio_transporte where med_tipo=\'Avion\'');

    audit(2,'Reporte Listado de Aviones y sus caracteristicas');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('airplanes', ["permissions" => $permissions,'airplanes' => $airplanes] );
});

Route::get('/report/frequent', function() {
    $clients = DB::select('select cli_cedula cedula,cli_nombre || \' \' || cli_apellido nombre, count(p.*) paquetes from cliente c,envio e,paquete p where env_cliente=cli_cedula and paq_envio=env_codigo and env_fecha between current_date-30 and current_date group by cli_cedula,cli_nombre having count(p.*)>5');

    audit(2,'Reporte Listado de Clientes Frecuentes');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('frequent', ["permissions" => $permissions,'clients' => $clients] );
});

Route::get('/report/most-send', function() {
    $offices = DB::select('select suc_nombre sucursal, cli_cedula cedula, cli_nombre || \' \' ||cli_apellido nombre, count(p.*) paquetes from cliente, envio, sucursal,paquete p where cli_cedula=env_cliente and p.paq_envio=env_codigo and env_suc_origen=suc_codigo group by suc_nombre, cli_cedula, cli_nombre || \' \' ||cli_apellido');

    audit(2,'Reporte Listado de Clientes que Registran Mas Paquetes por Oficina');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('mostsend', ["permissions" => $permissions,'offices' => $offices] );
});

Route::get('/report/daily-average', function(){
    $offices = DB::select('select sucursal,avg(paquetes) promedio from (select env_fecha,suc_nombre sucursal,count(p.*) paquetes from sucursal,envio, paquete p where suc_codigo=env_suc_origen and env_codigo=paq_envio group by env_fecha,suc_nombre order by suc_nombre,env_fecha) as tabla group by sucursal');

    audit(2,'Reporte Promedio de Paquetes Diario por Oficina');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('daily_average', ["permissions" => $permissions,'offices' => $offices] );
});

Route::get('/report/best-month', function(){
    $month = DB::select('select to_char(to_timestamp(to_char(extract(month from env_fecha), \'999\'), \'MM\'), \'Month\') mes,count(*) envios from envio where env_fecha between current_date - 365 and current_date group by extract(month from env_fecha) order by envios desc limit 1')[0];

    audit(2,'Reporte Mes con mas envios en un año');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('best_month', ["permissions" => $permissions,'month' => $month] );
});

Route::get('/report/avg-stay', function() {
    $zones = DB::select('select suc_nombre,paq_zona_codigo,avg(paq_f_salida-paq_f_entrada) estadia from paq_zon,sucursal where paq_zona_sucursal=suc_codigo and paq_f_salida is not NULL group by paq_zona_codigo,suc_nombre order by paq_zona_codigo');

    audit(2,'Reporte Promedio de Estancia de paquetes por Zona de Oficina');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('avg_stay', ["permissions" => $permissions,'zones' => $zones] );
});

Route::get('/vehicles', function() {
    //TODO: configure the options (delete and edit)
    $vehicles = DB::select('select med_codigo,med_tipo,med_placa,suc_nombre from medio_transporte,flota,sucursal where med_flota=flo_codigo and flo_sucursal=suc_codigo');

    audit(2,'Tabla de vehiculos');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('vehicles', ["permissions" => $permissions,'vehicles' => $vehicles] );
});

Route::get('/report/international', function(){
    $offices = DB::select('select suc_codigo,suc_nombre, pais.lug_nombre from sucursal s, lugar mun,lugar est, lugar pais where suc_lugar=mun.lug_codigo and mun.lug_lugar=est.lug_codigo and est.lug_lugar=pais.lug_codigo and pais.Lug_nombre!=\'VENEZUELA\'');

    audit(2,'Reporte Sucursales Internacionales');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('international', ["permissions" => $permissions,'offices' => $offices] );
});

Route::get('/zones', function() {
    //TODO: missing delete and edit function
    $zones = DB::select('select est.lug_nombre estado,suc_nombre,zon_codigo,zon_tamano,zon_tipo from sucursal,zona,lugar mun,lugar est where suc_codigo=zon_sucursal and suc_lugar=mun.lug_codigo and mun.lug_lugar=est.lug_codigo');

    audit(2,'Tabla de zonas');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('zones', ["permissions" => $permissions,'zones' => $zones] );
});

Route::get('/report/pack-period', function(){
    $permissions = json_decode(Cookie::get('permissions'));
    return view('pack_period', ["permissions" => $permissions]);
});

Route::post('/report/pack-period', function(){
    $packages = DB::select('select tip_tipo,suc_nombre,paq_guia from tipo_paquete,sucursal,envio,paquete where paq_envio=env_codigo and env_suc_origen=suc_codigo and paq_tipo_paquete=tip_codigo and env_fecha between \''.$_POST['inicio'].'\' and \''.$_POST['fin'].'\'');

    audit(2,'Reporte Lista de paquetes por clasificacion y oficina por periodo de tiempo');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('pack_period', ["permissions" => $permissions, 'packages' => $packages,'post' => $_POST]);
});

Route::get('/report/offices-location', function(){
    $offices = DB::select('select p.lug_nombre pais,e.lug_nombre estado, m.lug_nombre municipio, suc_nombre from sucursal,lugar p,lugar m, lugar e where suc_lugar=m.lug_codigo and m.lug_lugar=e.lug_codigo and e.lug_lugar=p.lug_codigo');

    audit(2,'Reporte Lista de oficinas indicando region,estado,pais');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('offices_location', ["permissions" => $permissions, 'offices' => $offices]);
});

Route::get('/report/biggest-office', function(){
    $pais = DB::select('select t1.pais,sucursal,t1.tamano from (select pais,max(tamano) tamano from (select p.lug_nombre pais,suc_nombre,sum(zon_tamano) tamano from sucursal, zona,lugar p,lugar m,lugar e where suc_lugar=m.lug_codigo and m.lug_lugar=e.lug_codigo and e.lug_lugar=p.lug_codigo and zon_sucursal=suc_codigo group by p.lug_nombre,suc_nombre) as t group by pais) as t1,(select p.lug_nombre pais, suc_nombre sucursal,sum(zon_tamano) tamano from sucursal, zona,lugar p,lugar m,lugar e where suc_lugar=m.lug_codigo and m.lug_lugar=e.lug_codigo and e.lug_lugar=p.lug_codigo and zon_sucursal=suc_codigo group by p.lug_nombre, suc_nombre) as t2 where t1.tamano=t2.tamano');
    $estados = DB::select('select t1.estado,sucursal,t1.tamano from (select estado,max(tamano) tamano from (select e.lug_nombre estado,sum(zon_tamano) tamano from sucursal, zona,lugar m,lugar e where suc_lugar=m.lug_codigo and m.lug_lugar=e.lug_codigo and zon_sucursal=suc_codigo group by e.lug_nombre, suc_nombre) as t group by estado) as t1,(select e.lug_nombre estado, suc_nombre sucursal,sum(zon_tamano) tamano from sucursal, zona,lugar m,lugar e where suc_lugar=m.lug_codigo and m.lug_lugar=e.lug_codigo and zon_sucursal=suc_codigo group by e.lug_nombre, suc_nombre) as t2 where t1.tamano=t2.tamano');
    $region = DB::select('select t1.region,sucursal,t1.tamano from (select region,max(tamano) tamano from (select m.lug_nombre region,sum(zon_tamano) tamano from sucursal, zona,lugar m where suc_lugar=m.lug_codigo and zon_sucursal=suc_codigo group by m.lug_nombre, suc_nombre) as t group by region) as t1,(select m.lug_nombre region, suc_nombre sucursal,sum(zon_tamano) tamano from sucursal, zona,lugar m where suc_lugar=m.lug_codigo and zon_sucursal=suc_codigo group by m.lug_nombre, suc_nombre) as t2 where t1.tamano=t2.tamano');

    audit(2,'Reporte Oficina mas amplia por estado, municipio y pais');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('biggest_office', ["permissions" => $permissions, 'region' => $region, 'estados' => $estados, 'pais' => $pais]);
});

Route::get('/report/employee-detail-date', function(){
    $permissions = json_decode(Cookie::get('permissions'));
    return view('employee_date', ["permissions" => $permissions]);
});

Route::post('/report/employee-detail-date', function(){
    $employees = DB::select('select emp_cedula, emp_nombre || \' \' || emp_apellido nombre, emp_email_coorporativo mail,emp_cargo,emp_f_ingreso ing,emp_f_egreso egr from empleado where emp_f_ingreso between \''.$_POST['inicio'].'\' and \''.$_POST['fin'].'\'');
    $active = DB::select('select count(*) from empleado where emp_f_egreso is null');
    $inactive = DB::select('select count(*) from empleado where emp_f_egreso is not null');

    audit(2,'Reporte Listado de empleado activos o no por rango de fechas');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('employee_date', ["permissions" => $permissions, 'employees' => $employees, 'active' => $active, 'inactive' => $inactive]);
});

Route::get('/report/active-employees', function(){
    $employees = DB::select('select emp_cedula, emp_nombre || \' \' || emp_apellido nombre, emp_email_coorporativo mail,emp_cargo,emp_f_ingreso ing from empleado where emp_f_egreso is null');

    audit(2,'Reporte Listado de empleado activos');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('active_employees', ["permissions" => $permissions, 'employees' => $employees]);
});

Route::get('/report/most-used-transport', function(){
    $transport = DB::select('select med_tipo ,count(M.*) from medio_transporte as M, envio, ruta where env_ruta = rut_codigo and rut_med_transporte = med_codigo group by (med_tipo) order by count(M.*) desc limit 1;')[0];

    audit(2,'Reporte de medio de transporte mas utilizado');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('most_used_transport', ["permissions" => $permissions, 'transport' => $transport]);
});

Route::get('/airports', function(){
    //TODO: missing delete and edit functions
    $airports = DB::select('select *,suc_nombre from aeropuerto,sucursal where aer_sucursal=suc_codigo');

    audit(2,'Tabla de Aeropuertos');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('airports', ["permissions" => $permissions, 'airports' => $airports]);
});

Route::get('/ports', function(){
    //TODO: missing delete and edit functions
    $ports = DB::select('select *,suc_nombre from puerto,sucursal where pue_sucursal=suc_codigo');

    audit(2,'Tabla de Aeropuertos');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('ports', ["permissions" => $permissions, 'ports' => $ports]);
});

Route::get('/services', function(){
    //TODO: missing delete and edit functions
    $services = DB::select('select * from servicio');
    
    audit(2,'Tabla de servicios');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('services', ["permissions" => $permissions, 'services' => $services]);
});

Route::get('/report/services', function(){
    $services = DB::select('select suc_nombre,ser_codigo,ser_nombre from suc_ser r, servicio ser, sucursal suc where suc_sucursal=suc.suc_codigo and suc_servicio=ser_codigo');

    audit(2,'Reporte Listado de servicios por oficina');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('services_office', ["permissions" => $permissions, 'services' => $services]);
});

Route::get('/report/workshop', function(){
    $workshops = DB::select('select lug_nombre,t.* from lugar,taller t where lug_codigo=tal_lugar');

    audit(2,'Reporte Listado de talleres por zona');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('workshop_zone', ["permissions" => $permissions, 'workshops' => $workshops]);
});

Route::get('/workshops', function(){
    //TODO: missing delete and edit functions
    $workshops = DB::select('select * from taller');

    audit(2,'Tabla de Talleres');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('workshops', ["permissions" => $permissions, 'workshops' => $workshops]);
});

Route::get('/report/most-transit-office', function() {
    $permissions = json_decode(Cookie::get('permissions'));
    return view('most_transit', ["permissions" => $permissions]);
});

Route::post('/report/most-transit-office', function() {
    $office = DB::select('select suc_nombre , sum(paquetes) from (select env_suc_destino sucursal, count(*) paquetes from envio where env_fecha between \''.$_POST['inicio'].'\' and \''.$_POST['fin'].'\' group by env_suc_destino union select env_suc_origen sucursal, count(*) from envio group by env_suc_origen) as tabla,sucursal where suc_codigo=sucursal group by suc_nombre order by sum desc limit 1')[0];

    audit(2,'Reporte Oficina con mas transito de paquetes por periodo de tiempo');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('most_transit', ["permissions" => $permissions, 'office' => $office, 'post' => $_POST]);
});

Route::get('/report/package-alert', function(){
    $packages = DB::select('select P.paq_guia, PE.paq_fecha, E.est_nombre from paquete as P, paq_est as PE, estatus_paquete as E where PE.paq_estatus_paquete = E.est_codigo and PE.paq_paquete = P.paq_guia and E.est_nombre = \'Preparando\' and paq_fecha + 1 < current_date');
    audit(2,'Alerta paquetes con mas de 24 horas');
    $permissions = json_decode(Cookie::get('permissions'));
    return view('package_alert', ["permissions" => $permissions, 'packages' => $packages]);
});



function audit ($aid,$description,$uname = ''){
    if ($uname == ''){
        $uname = Cookie::get('user-email');
    }
    
    $uid = DB::select('select usu_codigo cod from usuario where usu_email=\''.$uname.'\'')[0]->cod;
    $log = DB::insert('insert into auditoria (aud_usuario,aud_accion,aud_descripcion,aud_fecha,aud_rol) values ('.$uid.','.$aid.',\''.$description.'\',CURRENT_TIMESTAMP,(select usu_rol from usu_rol where usu_usuario='.$uid.'))');
};

function updateVIP (){
    $clients = DB::select('update cliente set cli_vip=1 where cli_cedula in (select cedula from (select cli_cedula cedula, count(p.*) paquetes from cliente c,envio e,paquete p where env_cliente=cli_cedula and paq_envio=env_codigo and env_fecha between current_date-30 and current_date group by cli_cedula having count(p.*)>5) as tabla)');
    $clients = DB::select('update cliente set cli_vip=NULL where cli_cedula not in (select cedula from (select cli_cedula cedula, count(p.*) paquetes from cliente c,envio e,paquete p where env_cliente=cli_cedula and paq_envio=env_codigo and env_fecha between current_date-30 and current_date group by cli_cedula having count(p.*)>5) as tabla)');
}