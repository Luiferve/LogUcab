<!doctype html>

<html class="no-js" lang="en">

<head>
<!--====== USEFULL META ======-->
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Transportation & Agency Template is a simple Smooth transportation and Agency Based Template" />
<meta name="keywords" content="Portfolio, Agency, Onepage, Html, Business, Blog, Parallax" />
@if (isset($redirect) && $redirect == true)
<meta http-equiv="refresh" content="0;url={{url('/')}}">
@endif
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

<!--====== MAIN STYLESHEETS ======-->
<link href="style.css" rel="stylesheet">
<link href="css/responsive.css" rel="stylesheet">

<script src="js/vendor/modernizr-2.8.3.min.js"></script>
</head>

<body class="body-login-area">
	<div class="login-wrap">
		<div class="login-html">
			<input id="tab-1" type="radio" name="tab" class="sign-in" checked><label for="tab-1" class="tab">Sign In</label>
			<input id="tab-2" type="radio" name="tab" class="sign-up"><label for="tab-2" class="tab">Sign Up</label>
			<div class="login-form">

				<form id="sign-in" method="POST" action="{{url('/login')}}">
					<div class="sign-in-htm">
						<div class="group">
							<label for="user" class="label">Username</label>
							<input id="user" type="email" class="input" name="email" required>
						</div>
						<div class="group">
							<label for="pass" class="label">Password</label>
							<input id="pass" type="password" class="input" data-type="password" name="password" required>
						</div>
						<div class="group">
							<input id="check" type="checkbox" class="check" checked>
							<label for="check"><span class="icon"></span> Keep me Signed in</label>
						</div>
						<div class="group">
							<input type="submit" class="button" value="Sign In">
						</div>
						<div class="hr"></div>
						<div class="foot-lnk">
							<a href="#forgot" class="mouse-hover">Forgot Password?</a>
						</div>
					</div>
				</form>

				<form id="sign-up" method="POST" action="{{url('/login')}}">
					<div class="sign-up-htm">
						<div class="group">
							<label for="pass" class="label">Email Address</label>
							<input id="pass" type="text" class="input" name="email" required>
						</div>
						<div class="group">
							<label for="pass" class="label">Password</label>
							<input id="pass" type="password" class="input" data-type="password" name="password" required>
						</div>
						<div class="group">
							<label for="pass" class="label">Repeat Password</label>
							<input id="pass" type="password" class="input" data-type="password" name="password2" required>
						</div>
						<div class="group">
							<input type="submit" class="button" value="Sign Up">
						</div>
						<div class="hr"></div>
						<div class="foot-lnk">
							<label for="tab-1" class="mouse-hover">Already Member?</a>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
	@if (isset($message))
		<div class="container" id="alert">
			<div class="alert alert-success" role="alert">
				{{$message}}
			</div>
		</div>
	@endif

</body>

</html>