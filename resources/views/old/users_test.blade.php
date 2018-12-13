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
<title>LogUcab | Login</title>

<!--====== FAVICON ICON =======-->
<link rel="shortcut icon" type="image/ico" href="img/favicon.png" />

<!--====== STYLESHEETS ======-->
<link rel="stylesheet" href="css/normalize.css">
<link rel="stylesheet" href="css/animate.css">
<link rel="stylesheet" href="css/stellarnav.min.css">
<link rel="stylesheet" href="css/owl.carousel.css">
<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/font-awesome.min.css" rel="stylesheet">

<link rel="stylesheet" type="text/css" href="js/DataTables-1.10.18/css/dataTables.bootstrap.min.css"/>
<script type="text/javascript" src="js/DataTables-1.10.18/css/datatables.min.js"></script>
<script src="js/jQuery-3.3.1/jquery-3.3.1.min.js"></script>
<script src="js/DataTables-1.10.18/js/jquery.dataTables.min.js"></script>
<script src="js/DataTables-1.10.18/js/dataTables.bootstrap.min.js"></script>

<!--====== MAIN STYLESHEETS ======-->
<link href="style.css" rel="stylesheet">
<link href="css/responsive.css" rel="stylesheet">

<script src="js/vendor/modernizr-2.8.3.min.js"></script>
</head>

<body>
    <div class= "table-responsive">
        <table id="users" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <td>Codigo</td>
                    <td>Email</td>
                    <td>Password</td>
                </tr>
            </thead>
            @foreach ($users as $user)
                <tr>
                    <td>{{$user->usu_codigo}}</td>
                    <td>{{$user->usu_email}}</td>
                    <td>{{$user->usu_password}}</td>
                </tr>
            @endforeach
        </table>
    </div>

</body>

</html>

<script>  
 $(document).ready(function(){  
      $('#users').DataTable();  
 });  
 </script>  