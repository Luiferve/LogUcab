<!doctype html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang=""> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8" lang=""> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9" lang=""> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js" lang="en">
<!--<![endif]-->

<head>
    <!--====== USEFULL META ======-->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Transportation & Agency Template is a simple Smooth transportation and Agency Based Template" />
    <meta name="keywords" content="Portfolio, Agency, Onepage, Html, Business, Blog, Parallax" />

    <!--====== TITLE TAG ======-->
    <title>LogUcab 
        @if (!empty($users))
            {{$users[0]->usu_email}}
            {{Cookie::queue('_token', $users[0]->usu_email, 60)}}
        @endif
    </title>

    <!--====== FAVICON ICON =======-->
    <link rel="shortcut icon" type="image/ico" href="img/favicon.png" />

    <!--====== STYLESHEETS ======-->
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/animate.css">
    <link rel="stylesheet" href="css/stellarnav.min.css">
    <link rel="stylesheet" href="css/owl.carousel.css">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">

    <!--====== MAIN STYLESHEETS ======-->
    <link href="style.css" rel="stylesheet">
    <link href="css/responsive.css" rel="stylesheet">

    <script src="js/vendor/modernizr-2.8.3.min.js"></script>
    <!--[if lt IE 9]>
        <script src="//oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="//oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
</head>

<body class="home-one">

    <!--[if lt IE 8]>
        <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->

    <!--- PRELOADER -->
    <div class="preeloader">
        <div class="preloader-spinner"></div>
    </div>

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
                                            <li><a href="{{url('/zones')}}">Zones Table</a></li>
                                            <li><a href="{{url('/airports')}}">Airports Table</a></li>
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
                                            <li><a href="{{url('/report/avg-stay')}}">Average Package Stay by Zone</a></li>
                                            <li><a href="{{url('/report/international')}}">International Franchises</a></li>
                                            <li><a href="{{url('/report/pack-period')}}">Package Information by Period</a></li>
                                            <li><a href="{{url('/report/offices-location')}}">Franchises Detailed Location</a></li>
                                            <li><a href="{{url('/report/biggest-office')}}">Biggest Office</a></li>
                                            <li><a href="{{url('/report/employee-detail-date')}}">Employee Detailed by Date</a></li>
                                            <li><a href="{{url('/report/active-employees')}}">Active Employees List</a></li>
                                            <li><a href="{{url('/report/most-used-transport')}}">Most Used Transport</a><li>
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
        </div>
        <!--START LOGIN-->
        @if (empty($users))
            <div class="container" id="logger">
                @crsf
                <form class="form-horizontal" method="POST" action="{{url('/login')}}">
                    <div class="form-group">
                        <label class="control-label col-sm-2" for="email">Email:</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}" id="email" placeholder="Enter email" name = 'email'>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2" for="pwd">Password:</label>
                        <div class="col-sm-10"> 
                            <input type="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}" id="pwd" placeholder="Enter password" name = 'password'>
                        </div>
                    </div>
                    <div class="form-group"> 
                            <div class="col-sm-offset-2 col-sm-10">
                            <div class="checkbox">
                                <label><input type="checkbox"> Remember me</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group"> 
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-default">Login</button>
                        </div>
                    </div>
                </form>
            </div>
        @else
            <div class="container" id="alert">
                <div class="alert alert-success" role="alert">
                    Bienvenido {{$users[0]->usu_nombre}}!!
                </div>
            </div>
        @endif
        <!--END LOGIN-->
    </header>
    <!--END TOP AREA-->

    <!--FOOER AREA-->
    <div class="footer-area dark-bg">
        <div class="footer-copyright-area">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 col-lg-6 col-sm-12 col-xs-12">
                        <div class="footer-copyright wow fadeIn">
                            
                            <p>Copyright 2018 All rights reserved | LogUcab <i class="fa fa-heart-o" aria-hidden="true"></i></p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-6 col-sm-12 col-xs-12">
                        <div class="footer-social-bookmark text-right wow fadeIn">
                            <ul class="social-bookmark">
                                <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                                <li><a href="#"><i class="fa fa-vimeo"></i></a></li>
                                <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                                <li><a href="#"><i class="fa fa-behance"></i></a></li>
                                <li><a href="#"><i class="fa fa-dribbble"></i></a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--FOOER AREA END-->


    <!--====== SCRIPTS JS ======-->
    <script src="js/vendor/jquery-1.12.4.min.js"></script>
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

    <!--===== ACTIVE JS=====-->
    <script src="js/main.js"></script>
</body>

</html>
