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
    <title>LogUcab | Route Registration</title>

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
                                <li><a href="{{url('/login')}}"><i class="fa fa-user" @if ($permissions > 0) title="Login" @endif></i></a></li>
                                @if ($permissions > 0)
                                <li><a href="{{url('/logout')}}"><i class="fa" title="Logout"></i>X</a></li> 
                                <!-- falta linkear el logout aqui -->
                                @endif
                                <li class="search-box"><i class="fa fa-search"></i></li>
                                <li class="select-language">
                                    <select name="#" id="#">
                                    <option selected value="End">ENG</option>
                                    <option value="ARA">ARA</option>
                                    <option value="CHI">CHI</option>
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
                                @if (isset($permissions) && $permissions > 3)
                                    <li><a href="#">Menu</a>
                                        <ul>
                                            <li><a href="{{url('/clients')}}">Clients</a></li>
                                            <li><a href="{{url('/users')}}">Users Table</a></li>
                                            <li><a href="{{url('/employees')}}">Employees Table</a></li>
                                            <li><a href="{{url('/locations')}}">Locations Table</a></li>
                                            <li><a href="{{url('/franchises')}}">Franchises Table</a></li>
                                            <li><a href="{{url('/routes')}}">Routes</a></li>
                                            <li><a href="{{url('/ship')}}">Ship Package</a></li>
                                            <li><a href="{{url('/franchiseReg')}}">Franchise Reg</a></li>
                                            <li><a href="{{url('/shipments')}}">Shipments Table</a></li>
                                            <li><a href="{{url('/packages')}}">Packages Table</a></li>
                                        </ul>
                                    </li>
                                    <li><a href="#">Reports</a>
                                        <ul>
                                            <li><a href="{{url('/report/omsrp')}}">Office with most sended & received packages</a></li>
                                        </ul>
                                    </li>
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
            <form class="form-horizontal" role="form"  method="POST" action="{{url('/routes')}}">
                @csrf
                <input type="hidden" name="codigo" @if (isset($routes))
                            value="{{$routes[0]->rut_codigo}}"
                        @endif>
                <div class="form-group">
                    <label for="sucursal" class="col-sm-3 control-label">Sucursal de Origen*</label>
                    <div class="col-sm-9">
                        <select name="sucursalO" class="form-control" style="margin-bottom: 10px;">
                            <option value="">Seleccione una Sucursal</option>
                            @foreach ($franchises as $franchise)
                                <option 
                                @if (isset($routes) && isset($routes) && $franchise->cod == $routes[0]->rut_suc_origen)
                                selected 
                                @endif
                                value="{{$franchise->cod}}">{{$franchise->nombre}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="sucursal" class="col-sm-3 control-label">Sucursal de Destino*</label>
                    <div class="col-sm-9">
                        <select name="sucursalD" class="form-control" style="margin-bottom: 10px;">
                            <option value="">Seleccione una sucursal</option>
                            @foreach ($franchises as $franchise)
                                <option 
                                @if (isset($routes) && isset($routes) && $franchise->cod == $routes[0]->rut_suc_destino)
                                selected 
                                @endif
                                value="{{$franchise->cod}}">{{$franchise->nombre}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="duracion" class="col-sm-3 control-label">Duracion (en Horas)*</label>
                    <div class="col-sm-9">
                        <input name = "duracion" type="number" id="duracion" placeholder="Duracion" class="form-control"
                        @if (isset($routes))
                            value="{{$routes[0]->rut_duracion}}"
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
                <button type="submit" class="btn btn-primary btn-block">Registrar/Actualizar</button>
            </form> <!-- /form -->
        </div> <!-- ./container -->

              <!--====== SCRIPTS JS ======-->
    <!-- <script src="js/vendor/jquery-1.12.4.min.js"></script> -->
    <script src="js/vendor/bootstrap.min.js"></script>

    <!--====== PLUGINS JS ======-->
    <script src="js/vendor/jquery.easing.1.3.js"></script>
    <script src="js/vendor/jquery-migrate-1.2.1.min.js"></script>
    <script src="js/vendor/jquery.appear.js"></script>
    <script src="js/owl.carousel.min.js"></script>
    <script src="js/stellar.js"></script>
    <script src="js/wow.min.js"></script>
    <script src="js/stellarnav.min.js"></script>
    <script src="js/contact-form.js"></script>
    <script src="js/jquery.sticky.js"></script>

    <!--===== ACTIVE JS =====-->
    <script src="js/main.js"></script>
    
</body>

</html>

<!--=====  DATA TABLE =====-->
<script>  
   
</script> 