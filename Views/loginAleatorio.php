<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión</title>
    

    <link rel="stylesheet" type="text/css" href="/inventarioSucursales/Assets/css/bootstrap.min.css">

    <link rel="stylesheet" type="text/css" href="/inventarioSucursales/Assets/css/mainlogin.css">

    <link rel="stylesheet" type="text/css" href="/inventarioSucursales/Assets/css/utillogin.css">

    <!-- <link rel="stylesheet" type="text/css" href="/inventario/Assets/css/">

    <link rel="stylesheet" type="text/css" href="/inventario/Assets/css/">

    <link rel="stylesheet" type="text/css" href="/inventario/Assets/css/">
    <link rel="stylesheet" type="text/css" href="/inventario/Assets/css/"> -->
</head>
<body>
<div class="limiter">
    <div class="container-login100">
        <div class="wrap-login100">
            <div class="login100-pic js-tilt" data-tilt>
            <img src="/inventarioSucursales/Assets/images/img-01.webp" alt="IMG">
            </div>
            <form class="login100-form validate-form" method="POST"  action="/inventarioSucursales/Middlewares/routes.php">
                <span class="login100-form-title">
                Iniciar Sesión
                </span>
                <div class="wrap-input100 validate-input" data-validate="Valid email is required: ex@abc.xyz">
                    <input class="input100" type="text" name="user" placeholder="Email">
                    <span class="focus-input100"></span>
                    <span class="symbol-input100">
                    <i class="fa fa-envelope" aria-hidden="true"></i>
                    </span>
                </div>
                <div class="wrap-input100 validate-input" data-validate="Password is required">
                    <input class="input100" type="password" name="password" placeholder="Password">
                    <span class="focus-input100"></span>
                    <span class="symbol-input100">
                    <i class="fa fa-lock" aria-hidden="true"></i>
                    </span>
                </div>
                <div class="container-login100-form-btn">
                    <button class="login100-form-btn">
                    Login
                    </button>
                </div>
            <!-- <div class="text-center p-t-12">
            <span class="txt1">
            Forgot
            </span>
            <a class="txt2" href="#">
            Username / Password?
            </a>
            </div> -->
            <!-- <div class="text-center p-t-136">
            <a class="txt2" href="#">
            Create your Account
            <i class="fa fa-long-arrow-right m-l-5" aria-hidden="true"></i>
            </a>
            </div> -->
            </form>
        </div>
    </div>
</div>


<script src="/inventarioSucursales/assets/js/jquery-1.11.2.min.js"></script>
<script src="/inventarioSucursales/assets/js/bootstrap.min.js"></script>
<script src="/inventarioSucursales/assets/js/tilt.jquery.min.js"></script>

<script>
		$('.js-tilt').tilt({
			scale: 1.1
		})
	</script>

<script async src="https://www.googletagmanager.com/gtag/js?id=UA-23581568-13"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-23581568-13');
</script>

<script src="/inventarioSucursales/assets/js/mainlogin.js"></script>
</body>
</html>