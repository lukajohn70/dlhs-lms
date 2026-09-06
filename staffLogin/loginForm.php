<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css?family=Roboto:400,700" rel="stylesheet">
<title>NWRI-Login</title>
<link rel="icon" type="image/png" href="../img/Water4.png" />
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script> 
<link href="../font_awesome/font-awesome.css" rel="stylesheet">
<style type="text/css">
	body {
		color: #fff;
		background: #000033;
		font-family: 'Roboto', sans-serif;
	}
    .form-control {
        min-height: 41px;
		box-shadow: none;
		border-color: #e1e4e5;
	}
    .form-control:focus {
		border-color: #5fcaba;
	}
    .form-control, .btn {        
        border-radius: 3px;
    }    
	.signup-form {
		width: 400px;
		margin: 0 auto;
		padding: 30px 0;
	}	
    .signup-form form {
		color: #9ba5a8;
		border-radius: 3px;
    	margin-bottom: 15px;
        background: #fff;
        box-shadow: 0px 2px 2px rgba(0, 0, 0, 0.3);
        padding: 30px;
    }
	.signup-form h2 {
		color: #333;
		font-weight: bold;
        margin-top: 0;
    }
    .signup-form hr {
        margin: 0 -30px 20px;
    }
	.signup-form .form-group {
		margin-bottom: 20px;
	}
    .signup-form label {
		font-weight: normal;
		font-size: 13px;
	}
    .signup-form .btn {        
        font-size: 16px;
        font-weight: bold;
		background: #5fcaba;
		border: none;
		min-width: 140px;
    }
	.signup-form .btn:hover, .signup-form .btn:focus {
		background: #3fc0ad;
        outline: none !important;
	}
	.signup-form a {
		color: #fff;
		text-decoration: underline;
	}
    .signup-form a:hover {
		text-decoration: none;
	}
	.signup-form form a {
		color: #5fcaba;
		text-decoration: none;
	}	
	.signup-form form a:hover {
		text-decoration: underline;
	}
	img.resize {
		max-width:50%;
		max-height:50%;
	}
</style>

<script src="jQuery3.3.1.js"></script>
<script src="loginAjax.js"></script>
</head>
<body>
<div class="signup-form">
    <form action="#" id="login_form">
<center><img src="../img/NWRILogo.jpg" class="resize" alt="NWRI logo"></img></center>		
<h2><center>Login</center></h2>
		 <p>Please login here <a href="../index.php" style="float:right;">Home</a></p>
		 <div class="message" style="color:red;" align="center"></div><div class="message1" style="color:green;" align="center"></div>
		<center><hr style="width:100%;"></center>
		<a href="#" style="color:#0acca2;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a><p>
        <div class="form-group">
        	<input type="text" class="form-control" id="username" name="adminUsername" placeholder="Username" required="required">
        </div>
		<div class="form-group">
            <input type="password" class="form-control" id="password" name="adminPassword" placeholder="Password" required="required">
        </div>
		<div class="form-group">
            <button type="submit" id="submit" class="btn btn-primary btn-block btn-lg"><i class="fa fa-sign-in"></i> Login</button>
        </div>
		<div class="text-center">Forgot password? <a href="../forgotPassword/forgotPwordForm.php">Click here</a></div>
		<div class="text-center">Don't have an account? <a href="../signup/signupForm.php">Create Account</a></div>
    </form>	
</div>
</body>
</html>                            