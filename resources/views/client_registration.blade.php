<!doctype html>

<html class="no-js" lang="en">

<head>
    <!--====== USEFULL META ======-->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Transportation & Agency Template is a simple Smooth transportation and Agency Based Template" />
    <meta name="keywords" content="Portfolio, Agency, Onepage, Html, Business, Blog, Parallax" />

    <!--====== TITLE TAG ======-->
    <title>LogUcab | Client Modification</title>

    <!--====== FAVICON ICON =======-->
    <link rel="shortcut icon" type="image/ico" href="/img/favicon.png" />

    <!--====== STYLESHEETS ======-->
    <link rel="stylesheet" href="/css/normalize.css">
    <link rel="stylesheet" href="/css/animate.css">
    <link rel="stylesheet" href="/css/stellarnav.min.css">
    <link rel="stylesheet" href="/css/owl.carousel.css">
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/font-awesome.min.css" rel="stylesheet">

    <link rel="stylesheet" type="text/css" href="/js/DataTables-1.10.18/css/dataTables.bootstrap.min.css"/>
    <script type="text/javascript" src="/js/DataTables-1.10.18/css/datatables.min.js"></script>
    <script src="/js/jQuery-3.3.1/jquery-3.3.1.min.js"></script>
    <script src="/js/DataTables-1.10.18/js/jquery.dataTables.min.js"></script>
    <script src="/js/DataTables-1.10.18/js/dataTables.bootstrap.min.js"></script>

    <!--====== MAIN STYLESHEETS ======-->
    <link href="/style.css" rel="stylesheet">
    <link href="/regform.css" rel="stylesheet">
    <link href="/css/responsive.css" rel="stylesheet">

    <script src="/js/vendor/modernizr-2.8.3.min.js"></script>
    
</head>

<body class="home-one">
    <!--- PRELOADER -->
    <!-- <div class="preeloader">
        <div class="preloader-spinner"></div>
    </div> -->

    <!--SCROLL TO TOP-->
    <a href="#home" class="scrolltotop"><i class="fa fa-long-arrow-up"></i></a>

    <!--START TOP AREA-->
    <header class="top-area" id="home">
        <div class="top-area-bg" data-stellar-background-ratio="0.6"></div>
        <div class="header-top-area">
            <!--MAINMENU AREA-->
            <div class="mainmenu-area" id="mainmenu-area">
                <div class="mainmenu-area-bg"></div>
                <nav class="navbar">
                    <div class="container">
                        <div class="navbar-header">
                            <a href="{{url('/')}}" class="navbar-brand"><img src="/img/logo.png" alt="logo"></a>
                        </div>
                        <div class="search-and-language-bar pull-right">
                            <ul>
                                @if (empty($permissions))
                                <li><a href="{{url('/login')}}"><i class="fa fa-user" title="Login" ></i></a></li>
                                @endif
                                @if (!empty($permissions))
                                <li><a href="{{url('/logout')}}"><i class="fa" title="Logout"></i>X</a></li>
                                @endif
                                <li class="search-box"><i class="fa fa-search"></i></li>
                                <li class="select-language">
                                    <select name="#" id="#">
                                    <option selected value="End">SPA</option>
                                    <option value="End">ENG</option>
                                </select>
                                </li>
                            </ul>
                            <form action="#" class="search-form">
                                <input type="search" name="search" id="search">
                                <button type="submit"><i class="fa fa-search"></i></button>
                            </form>
                        </div>
                        <div id="main-nav" class="stellarnav">
                            <ul id="nav" class="nav navbar-nav">
                                <li><a href="{{url('/')}}">home</a></li>
                                <li><a href="about.html">about</a></li>
                                <li><a href="service.html">Service</a></li>
                                <li><a href="contact.html">Contact</a></li>
                                @if (isset($permissions) && in_array(4,$permissions))
                                    <li><a href="#">Menu</a>
                                        <ul>
                                            <li><a href="{{url('/shipments')}}">Shipments Table</a></li>
                                            @if (in_array(1,$permissions))
                                            <li><a href="{{url('/ship')}}">Ship Package</a></li>
                                            <li><a href="{{url('/users')}}">Users Table</a></li>
                                            <li><a href="{{url('/clients')}}">Clients Table</a></li>
                                            <li><a href="{{url('/employees')}}">Employees Table</a></li>
                                            <li><a href="{{url('/packages')}}">Packages Table</a></li>
                                            <li><a href="{{url('/locations')}}">Locations Table</a></li>
                                            <li><a href="{{url('/franchises')}}">Franchises Table</a></li>
                                            <li><a href="{{url('/routes')}}">Routes Table</a></li>
                                            <li><a href="{{url('/roles')}}">Roles Table</a></li>
                                            <li><a href="{{url('/vehicles')}}">Vehicles Table</a></li>
                                            @endif
                                        </ul>
                                    </li>
                                @endif
                                @if (isset($permissions) && in_array(1,$permissions))
                                    <li><a href="#">Reports</a>
                                        <ul>
                                            @if (in_array(3,$permissions))
                                            <li><a href="{{url('/logs')}}">Logs</a></li>
                                            @endif
                                            <li><a href="{{url('/report/omsrp')}}">Office with most sended & received packages</a></li>
                                            <li><a href="{{url('/report/mlur')}}">Most & Least used routes</a></li>
                                            <li><a href="{{url('/report/port-franchises')}}">Airports & Harbors Franchises</a></li>
                                            <li><a href="{{url('/report/avg-weight')}}">Average Package Weight by Franchise</a></li>
                                            <li><a href="{{url('/report/airplanes')}}">Detailed Aircraft List</a></li>
                                            <li><a href="{{url('/report/frequent')}}">Frequent Clients List</a></li>
                                            <li><a href="{{url('/report/most-send')}}">Clients Who Send the Most by Office</a></li>
                                            <li><a href="{{url('/report/daily-average')}}">Average of Daily Packages by Office</a></li>
                                            <li><a href="{{url('/report/best-month')}}">Month with Most Shipments</a></li>
                                        </ul>
                                    </li>
                                @endif
                                @if (isset($permissions) && in_array(3,$permissions))
                                    <li><a href="{{url('/attendance')}}">Attendance</a></li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </nav>
            </div>
            <!--END MAINMENU AREA END-->

    <div class="container" id="cont1">
            @if (isset($message))
                <div class="container" id="alert" style="margin-top: 2%;">
                    <div class="alert alert-success" role="alert">
                        {{$message}}
                    </div>
                </div>
            @endif
            <form class="form-horizontal" role="form" method="POST" action="{{url('clients')}}">
                @csrf
                <div class="form-group">
                    <label for="firstName" class="col-sm-3 control-label">Nombre*</label>
                    <div class="col-sm-9">
                        <input type="text" id="firstName" name="firstName" placeholder="Nombre" class="form-control" autofocus required
                        @if (isset($client))
                            value="{{$client[0]->cli_nombre}}"
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group">
                    <label for="lastName" class="col-sm-3 control-label">Apellido*</label>
                    <div class="col-sm-9">
                        <input type="text" id="lastName" name="lastName" placeholder="Apellido" class="form-control" autofocus required
                        @if (isset($client))
                            value="{{$client[0]->cli_apellido}}"
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group">
                    <label for="id" class="col-sm-3 control-label">Cedula*</label>
                    <div class="col-sm-9">
                        <input type="number" id="cedula" name="cedula" placeholder="Cedula" class="form-control" required
                        @if (isset($client))
                            value="{{$client[0]->cli_cedula}}"
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group">
                    <label for="email" class="col-sm-3 control-label">Email* </label>
                    <div class="col-sm-9">
                        <input type="email" id="email" name="email" placeholder="Email" class="form-control" name= "email" required
                        @if (isset($client))
                            value="{{$client[0]->cli_email}}"
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group">
                    <label for="carnet" class="col-sm-3 control-label">Carnet* </label>
                    <div class="col-sm-9">
                        <input type="text" id="carnet" name="carnet" placeholder="Carnet" class="form-control" name= "carnet" required
                        @if (isset($client))
                            value="{{$client[0]->cli_carnet}}"
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group">
                    <label for="vip" class="col-sm-3 control-label">VIP*</label>
                    <div class="col-sm-9">
                        <select name="vip" class="form-control" style="margin-bottom: 10px;" required>
                            <option value="">Seleccione si es cliente VIP</option>
                            <option value="1" 
                            @if (isset($client) && $client[0]->cli_vip == 1)
                                selected
                            @endif
                            >Si</option>
                            <option value="NULL" 
                            @if (isset($client) && $client[0]->cli_vip != 1)
                                selected
                            @endif
                            >No</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="empresa" class="col-sm-3 control-label">Empresa*</label>
                    <div class="col-sm-9">
                        <input type="text" id="empresa" name="empresa" placeholder="Empresa" class="form-control" required
                        @if (isset($client))
                            value="{{$client[0]->cli_empresa}}"
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group">
                    <label for="civil" class="col-sm-3 control-label">Estado civil*</label>
                    <div class="col-sm-9">
                        <select name="civil" class="form-control" style="margin-bottom: 10px;" required>
                            <option value="">Seleccione el estado civil</option>
                            <option value="Soltero/a">Soltero</option>
                            <option value="Casado/a">Casado</option>
                            <option value="Viudo/a">Viudo</option>
                            <option value="Divorciado/a">Divorciado</option>
                            <option value="Conyugue">Conyugue</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="birthDate" class="col-sm-3 control-label">Fecha de Nacimiento*</label>
                    <div class="col-sm-9">
                        <input type="date" id="birthDate" class="form-control" name="fnac"required
                        @if (isset($client))
                            value="{{$client[0]->cli_f_nacimiento}}"
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group">
                    <label for="phoneNumber" class="col-sm-3 control-label">Telefono* </label>
                    <div class="col-sm-9">
                        <input type="text" id="phoneNumber" name="phoneNumber" placeholder="Telefono" class="form-control" required
                        @if (isset($phone))
                            value="{{$phone[0]->tel_numero}}"
                        @endif
                        >
                    </div>
                </div>
                <div class="form-group">
                    <label for="estado" class="col-sm-3 control-label">Estado*</label>
                    <div class="col-sm-9">
                        <select name="state" class="form-control" style="margin-bottom: 10px;" required>
                            <option value="">Seleccione el estado</option>
                            @foreach ($states as $state)
                                <option 
                                @if (isset($client) && $state->cod == $client[0]->cli_lugar)
                                selected 
                                @endif
                                value="{{$state->cod}}">{{$state->nombre}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                        <label for="lugar" class="col-sm-3 control-label">Zona de Residencia* </label>
                    <div class="col-sm-9">
                        <input type="text" id="direcc" name="direcc" placeholder="Zona de Residencia" class="form-control" required
                        @if (isset($location))
                            value="{{$location[0]->lug_nombre}}"
                        @endif
                        > 
                    </div>
                </div>
                 <!-- /.form-group -->
                <div class="form-group">
                    <div class="col-sm-9 col-sm-offset-3">
                        <span class="help-block">*Obligatorio</span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Actualizar</button>
            </form> <!-- /form -->
        </div> <!-- ./container -->

              <!--====== SCRIPTS JS ======-->
    <!-- <script src="/js/vendor/jquery-1.12.4.min.js"></script> -->
    <script src="/js/vendor/bootstrap.min.js"></script>

    <!--====== PLUGINS JS ======-->
    <script src="/js/vendor/jquery.easing.1.3.js"></script>
    <script src="/js/vendor/jquery-migrate-1.2.1.min.js"></script>
    <script src="/js/vendor/jquery.appear.js"></script>
    <script src="/js/owl.carousel.min.js"></script>
    <script src="/js/stellar.js"></script>
    <script src="/js/wow.min.js"></script>
    <script src="/js/stellarnav.min.js"></script>
    <script src="/js/contact-form.js"></script>
    <script src="/js/jquery.sticky.js"></script>

    <!--===== ACTIVE JS =====-->
    <script src="/js/main.js"></script>
    
</body>

</html>

<!--=====  DATA TABLE =====-->
<script>  
   
</script> 