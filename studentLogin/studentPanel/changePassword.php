<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['studentLoggedIn']))
	{
		header('location:../index.php');
	}
	else
	{
		require_once "../../db_connection/dlhs_db_connection.php";
	}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Creative - Bootstrap 3 Responsive Admin Template">
    <meta name="keyword" content="">
    <link rel="icon" type="image/jpg" href="../images/dlhslogo3.jpg">

    <title>Change Password | DLHS</title>

    <!-- Bootstrap CSS -->    
    
    <!-- bootstrap theme -->
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <!--external css-->
    <!-- font icon -->
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
    <!-- date picker -->
    
    <!-- color picker -->
    
	    <!-- Custom styles -->
	    <link href="css/style.css" rel="stylesheet">
		<style>
			.password-policy-box {
				margin: 12px 0 18px;
				padding: 15px 18px;
				border: 1px solid #cfe2f3;
				border-radius: 8px;
				background: #f7fbff;
			}
			.password-policy-box strong {
				display: block;
				margin-bottom: 8px;
				color: #0a5a88;
			}
			.password-policy-box ul {
				margin: 0;
				padding-left: 18px;
			}
			.password-policy-box li {
				margin-bottom: 5px;
				color: #34495e;
			}
		</style>
  </head>
  <body>

	<!-- container section start -->
	<section id="container" class="">
		<!--Including the header-->
		<?php include 'header.php'; ?>

		<!--Including the sidebar-->
		<?php include 'sideBar_index.php'; ?>

      <!--main content start-->
      <section id="main-content">
          <section class="wrapper">
		  <div class="row">
				<div class="col-lg-9">
					<h3 class="page-header"><i class="icon_key_alt"></i> Change Password</h3>
					<ol class="breadcrumb">
						<li style="margin-left:-12px;"><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="icon_key_alt"></i>Change Password</li>
						<a href="#" style="color:#0acca2; padding-left:4px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-9">
						<section class="panel">
								<header class="panel-heading">
									<?php echo isset($_GET['force']) ? 'Default password change required' : 'Please fill required field in the form'; ?>
								</header>
								<div class="panel-body">
									<?php if (isset($_GET['force'])) { ?>
										<div class="alert alert-warning">
											Your account is still using the default password. Set a stronger password before continuing.
										</div>
									<?php } ?>
									<form id="changePasswordForm">
									<div class="form-group">
										<label>New password</label>
										<div style="position:relative; display:flex; align-items:center;">
											<input type="password" name="password1" class="form-control" id="password1" placeholder="Enter New password" required autocomplete="new-password" style="padding-right: 90px;">
											<div style="position:absolute; right:10px; display:flex; gap:8px; align-items:center; z-index:10;">
												<button type="button" onclick="dlhsTogglePwd('password1', 'togglePwdIcon1')" title="Show/hide password" style="background:none; border:none; cursor:pointer; color:#aaa; font-size:15px; padding:2px;">
													<i class="fa fa-eye" id="togglePwdIcon1"></i>
												</button>
												<button type="button" onclick="dlhsGeneratePwd()" title="Generate a strong password" style="background:none; border:none; cursor:pointer; color:#0acca2; font-size:15px; padding:2px;">
													<i class="fa fa-key"></i>
												</button>
												<button type="button" id="copyPwdBtn" onclick="dlhsCopyPwd()" title="Copy password" style="background:none; border:none; cursor:pointer; color:#aaa; font-size:14px; padding:2px; display:none;">
													<i class="fa fa-copy" id="copyPwdIcon"></i>
												</button>
											</div>
										</div>
                                        <div id="genPwdChip" style="display:none; margin-top:6px; font-size:12px; background:rgba(12,204,162,0.1); border:1px dashed #0acca2; border-radius:8px; padding:5px 10px; color:#06735a; font-family:monospace; letter-spacing:1px;"></div>
                                        <div class="password-strength-meter" style="height: 4px; background-color: #eee; margin-top: 5px; border-radius: 2px; overflow: hidden;">
                                            <div id="strengthFill" style="height: 100%; width: 0; transition: width 0.3s ease, background-color 0.3s ease;"></div>
                                        </div>
									</div>
									<div class="form-group">
										<label>Re-enter New password</label>
										<div style="position:relative; display:flex; align-items:center;">
											<input type="password" name="password2" class="form-control" id="password2" placeholder="Re-enter New password" required autocomplete="new-password" style="padding-right: 45px;">
											<div style="position:absolute; right:10px; display:flex; align-items:center; z-index:10;">
												<button type="button" onclick="dlhsTogglePwd('password2', 'togglePwdIcon2')" title="Show/hide password" style="background:none; border:none; cursor:pointer; color:#aaa; font-size:15px; padding:2px;">
													<i class="fa fa-eye" id="togglePwdIcon2"></i>
												</button>
											</div>
										</div>
									</div>
                                            <ul class="compliance-list" id="complianceList" style="list-style: none; padding-left: 0; margin-top: 15px; background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #dee2e6;">
                                                <style>
                                                    .compliance-item { margin-bottom: 8px; display: flex; align-items: center; font-size: 13px; color: #6c757d; }
                                                    .compliance-item i { margin-right: 10px; width: 16px; }
                                                    .compliance-item.met { color: #28a745; font-weight: 600; }
                                                    .compliance-item.not-met { color: #dc3545; }
                                                    .compliance-item i.fa-check-circle { display: none; }
                                                    .compliance-item.met i.fa-check-circle { display: inline-block; }
                                                    .compliance-item.met i.fa-circle-o { display: none; }
                                                </style>
                                                <li class="compliance-item" data-rule="length"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> At least 6 characters long</li>
                                                <li class="compliance-item" data-rule="letter"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> At least one letter</li>
                                                <li class="compliance-item" data-rule="number"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> At least one number</li>
                                                <li class="compliance-item" data-rule="no-default"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> Not a default password (1234, 4321, etc.)</li>
                                                <li class="compliance-item" data-rule="no-repeat"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> No simple repeating sequences (e.g. 1111)</li>
                                                <li class="compliance-item" data-rule="no-sequence"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> No ascending/descending sequences (e.g. abcd)</li>
                                                <li class="compliance-item" data-rule="match"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> Passwords must match</li>
                                            </ul>
									<button type="submit" class="btn btn-primary" id="submit" disabled><i class="fa fa-save"></i> Update Password</button>
									<div class="message2" id="message2" style="color:red;" align="center"></div><div class="message1" style="color:green; font-size:17px;" align="center"></div>
								</form>
							</div>
						</section>
					</div>					
				</div>				
              <!-- page end-->
          </section>
      </section>
      <!--main content end-->
      <div class="text-right">
        <div class="credits">
            <?php include "footer.php"; ?>
        </div>
    </div>
  </section>
  <!-- container section end -->
    <!-- javascripts -->
    <script src="jQuery3.3.1.js"></script>
	<script src="changePasswordAjax.js"></script>
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <!-- nice scroll -->
    <script src="js/jquery.nicescroll.js" type="text/javascript"></script>
   
    <script src="js/scripts.js"></script>
	
	<script>
	function dlhsTogglePwd(inputId, iconId) {
		var f = document.getElementById(inputId);
		var ic = document.getElementById(iconId);
		if (f.type === 'password') { f.type = 'text'; ic.className = 'fa fa-eye-slash'; }
		else { f.type = 'password'; ic.className = 'fa fa-eye'; }
	}
	function dlhsGeneratePwd() {
		var uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
		var lowercase = 'abcdefghjkmnpqrstuvwxyz';
		var numbers = '23456789';
		var symbols = '!@#%';
		var chars = uppercase + lowercase + numbers + symbols;
		var pwd = '';
		pwd += uppercase.charAt(Math.floor(Math.random() * uppercase.length));
		pwd += lowercase.charAt(Math.floor(Math.random() * lowercase.length));
		pwd += numbers.charAt(Math.floor(Math.random() * numbers.length));
		pwd += symbols.charAt(Math.floor(Math.random() * symbols.length));
		for (var i = 0; i < 6; i++) {
			pwd += chars.charAt(Math.floor(Math.random() * chars.length));
		}
		pwd = pwd.split('').sort(function(){return 0.5-Math.random()}).join('');

		var p1 = document.getElementById('password1');
		var p2 = document.getElementById('password2');
		
		p1.type = 'text';
		p2.type = 'text';
		
		p1.value = pwd;
		p2.value = pwd;
		
		document.getElementById('togglePwdIcon1').className = 'fa fa-eye-slash';
		document.getElementById('togglePwdIcon2').className = 'fa fa-eye-slash';
		
		document.getElementById('genPwdChip').style.display = 'block';
		document.getElementById('genPwdChip').innerHTML = '<i class="fa fa-check-circle"></i> Generated Strong Password: <strong>' + pwd + '</strong> (Click save below)';
		document.getElementById('copyPwdBtn').style.display = 'inline-block';
		
		p1.dispatchEvent(new Event('input'));
		p1.dispatchEvent(new Event('keyup'));
		p2.dispatchEvent(new Event('input'));
		p2.dispatchEvent(new Event('keyup'));
	}
	function dlhsCopyPwd() {
		var val = document.getElementById('password1').value;
		if (navigator.clipboard) {
			navigator.clipboard.writeText(val).then(function() {
				var ic = document.getElementById('copyPwdIcon');
				ic.className = 'fa fa-check';
				setTimeout(function(){ ic.className = 'fa fa-copy'; }, 2000);
			});
		}
	}
	</script>
  </body>
</html>
