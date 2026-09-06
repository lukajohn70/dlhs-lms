<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DLHS :: Home</title>
<link rel="icon" type="image/png" href="images/dlhslogo2.jpg"/>
<link rel="stylesheet" href="bootstrap/bootstrap.min.css">

<link href="adminLogin/fonts/font-awesome-4.7.0/css/font-awesome.css" rel="stylesheet">
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
	img.resize {
		max-width:50%;
		max-height:50%;
	}
	.signup-form hr {
        margin: 0 -30px 20px;
    }
	
	/* Style The Dropdown Button */
	.dropbtn {
	  background-color: #3366CC;
	  color: white;
	  padding: 8px 20px 8px 20px;
	  font-size: 16px;
	  border: none;
	  cursor: pointer;
	}
	
	/* The container - needed to position the dropdown content */
	.dropdown {
	  position: relative;
	  display: inline-block;
	}

	/* Dropdown Content (Hidden by Default) */
	.dropdown-content {
	  display: none;
	  position: absolute;
	  background-color: #f9f9f9;
	  min-width: 160px;
	  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
	  z-index: 1;
	}

	/* Links inside the dropdown */
	.dropdown-content a {
	  color: black;
	  padding: 12px 16px;
	  text-decoration: none;
	  display: block;
	}

	/* Change color of dropdown links on hover */
	.dropdown-content a:hover {background-color: #f1f1f1}

	/* Show the dropdown menu on hover */
	.dropdown:hover .dropdown-content {
	  display: block;
	}

	/* Change the background color of the dropdown button when the dropdown content is shown */
	.dropdown:hover{
	  
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
</style>

<script src="jQuery3.3.1.js"></script>

</head>
<body>
<div class="signup-form">
    <form action="login.php" id="login_form" method="post">
		<center><img src="images/dlhslogo.png" class="resize" alt="DLHS logo"></img></center>		
		<h3><center>School Management System</center></h3>
		<hr style="margin-right:5px; margin-left:5px;">
		<center>	
			<p>
			<div class="dropdown" style="padding-bottom:200px;">
				<button class="dropbtn" type="button">Select user type <i class="fa fa-chevron-down "></i></button>
				<div class="dropdown-content">
					<a href="adminLogin"><i class="fa fa-user"></i> Admin</a>
					<a href="staffLogin"><i class="fa fa-user-circle "></i> Staff</a>
					<a href="studentLogin"><i class="fa fa-group"></i> Student</a>
				</div>
			</div>
			<div class="text-center">Powered by <a href="https://microbits.ng" target="_blank">Microbits Technologies</a></div>
		</center>
    </form>	
</div>
</body>
</html>                            