<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['staffLoggedIn']))
	{
		header('location:../index.php');
	}
	include "../../db_connection/dlhs_db_connection.php";
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DLHS Dashboard">
    <meta name="author" content="DLHS IT Department">
    <meta name="keyword" content="DLHS, Dashboard, Admin, Education, School">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">

    <title>Change Password | DLHS</title>

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
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
	    <link href="css/style-responsive.css" rel="stylesheet" />
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
	<script>
		//function to accept only integer minutes.
		function isNumber(evt) {
			var iKeyCode = (evt.which) ? evt.which : evt.keyCode
			if (iKeyCode < 48 || iKeyCode > 57)
				return false;

			return true;
		} 
	</script>
  </head>
  <body>

	<!-- container section start -->
	<section id="container" class="">
		<!--Including the header-->
		<?php include 'header.php'; ?>

		<!--Including the sidebar-->
		<?php include 'sideBar.php'; ?>

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
					<div class="col-lg-8 col-md-10">
						<section class="panel">
								<header class="panel-heading">
									<?php echo isset($_GET['force']) ? 'Default password change required' : 'Change Your Account Password'; ?>
								</header>
								<div class="panel-body">
										<?php if (isset($_GET['force'])) { ?>
											<div class="alert alert-warning">
												Your account is still using the default password. Set a stronger password before continuing.
											</div>
										<?php } ?>
										<form id="changePasswordForm">
									<div class="form-group">
										<label>New Password</label>
										<div class="input-group">
											<input type="password" name="password1" class="form-control" id="password1" placeholder="Enter New password" required autocomplete="new-password">
											<span class="input-group-btn">
												<button class="btn btn-default" type="button" onclick="dlhsTogglePwd('password1', 'togglePwdIcon1')">
													<i class="fa fa-eye" id="togglePwdIcon1"></i>
												</button>
											</span>
										</div>
                                        <div class="password-strength-meter" style="height: 4px; background-color: #eee; margin-top: 5px; border-radius: 2px; overflow: hidden;">
                                            <div id="strengthFill" style="height: 100%; width: 0; transition: width 0.3s ease, background-color 0.3s ease;"></div>
                                        </div>
									</div>
									<div class="form-group">
										<label>Re-enter New Password</label>
										<div class="input-group">
											<input type="password" name="password2" class="form-control" id="password2" placeholder="Re-enter New password" required autocomplete="new-password">
											<span class="input-group-btn">
												<button class="btn btn-default" type="button" onclick="dlhsTogglePwd('password2', 'togglePwdIcon2')">
													<i class="fa fa-eye" id="togglePwdIcon2"></i>
												</button>
											</span>
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
                                        <li class="compliance-item" data-rule="length"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> At least 8 characters long</li>
                                        <li class="compliance-item" data-rule="uppercase"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> At least one uppercase letter</li>
                                        <li class="compliance-item" data-rule="lowercase"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> At least one lowercase letter</li>
                                        <li class="compliance-item" data-rule="number"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> At least one number</li>
                                        <li class="compliance-item" data-rule="no-default"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> Not a default password (1234, 4321, etc.)</li>
                                        <li class="compliance-item" data-rule="no-repeat"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> No simple repeating sequences (e.g. 1111)</li>
                                        <li class="compliance-item" data-rule="no-sequence"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> No ascending/descending sequences (e.g. abcd)</li>
                                        <li class="compliance-item" data-rule="match"><i class="fa fa-circle-o"></i><i class="fa fa-check-circle"></i> Passwords must match</li>
                                    </ul>

                                    <div style="margin-bottom: 20px;">
                                        <button type="button" class="btn btn-default" onclick="dlhsGeneratePassword()">
                                            <i class="fa fa-magic"></i> Generate Secure Password
                                        </button>
                                        <div id="genPwdNotice" style="display:none; margin-top:10px; padding:10px 14px; background:#e8f4fd; border:1px solid #b8daff; border-radius:8px; color:#004085; font-size:13px;"></div>
                                    </div>

									<button type="submit" class="btn btn-primary" id="submit" disabled><i class="fa fa-save"></i> Update Password</button>
									<div id="statusMessage" style="margin-top: 15px;"></div>
									<div class="message2" id="message2" style="color:red; margin-top: 10px;" align="center"></div><div class="message1" style="color:green; font-size:17px; margin-top: 10px;" align="center"></div>
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
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="changePasswordAjax.js"></script>
    <!-- nice scroll -->
    <script src="js/jquery.scrollTo.min.js"></script>
    <script src="js/jquery.nicescroll.js" type="text/javascript"></script>

    <!-- jquery ui -->
    <script src="js/jquery-ui-1.9.2.custom.min.js"></script>
    <!-- custome script for all page -->
    <script src="js/scripts.js"></script>
	<script>
		function dlhsTogglePwd(inputId, iconId) {
			var f = document.getElementById(inputId);
			var ic = document.getElementById(iconId);
			if (!f || !ic) return;
			if (f.type === 'password') {
				f.type = 'text';
				ic.className = 'fa fa-eye-slash';
			} else {
				f.type = 'password';
				ic.className = 'fa fa-eye';
			}
		}

		function dlhsGeneratePassword() {
			var uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
			var lowercase = 'abcdefghjkmnpqrstuvwxyz';
			var numbers = '23456789';
			var symbols = '!@#$%^&*()_+';
			var chars = uppercase + lowercase + numbers + symbols;
			var retVal = '';
			retVal += uppercase.charAt(Math.floor(Math.random() * uppercase.length));
			retVal += lowercase.charAt(Math.floor(Math.random() * lowercase.length));
			retVal += numbers.charAt(Math.floor(Math.random() * numbers.length));
			retVal += symbols.charAt(Math.floor(Math.random() * symbols.length));
			for (var i = 0; i < 8; i++) {
				retVal += chars.charAt(Math.floor(Math.random() * chars.length));
			}
			retVal = retVal.split('').sort(function(){return 0.5-Math.random()}).join('');
			$('#password1').val(retVal).attr('type', 'text').trigger('input');
			$('#password2').val(retVal).attr('type', 'text').trigger('input');
			$('#togglePwdIcon1').attr('class', 'fa fa-eye-slash');
			$('#togglePwdIcon2').attr('class', 'fa fa-eye-slash');
			$('#genPwdNotice').show().html('<i class="fa fa-check-circle text-success"></i> Generated Strong Password: <strong id="genPwdText">' + retVal + '</strong> <button type="button" class="btn btn-xs btn-default" style="margin-left:8px;" onclick="dlhsCopyGenerated()"><i class="fa fa-copy"></i> Copy</button>');
		}

		function dlhsCopyGenerated() {
			var pwd = $('#genPwdText').text();
			if (navigator.clipboard) {
				navigator.clipboard.writeText(pwd).then(function() {
					dlhsAlert('Password copied to clipboard: ' + pwd, 'success');
				});
			} else {
				dlhsAlert('Generated password: ' + pwd, 'info');
			}
		}
	</script>



	<!-- ===== DLHS Universal Confirm Modal ===== -->
	<div id="dlhsConfirmModal" style="display:none; position:fixed; inset:0; z-index:999990; background:rgba(0,0,0,0.48); backdrop-filter:blur(5px); -webkit-backdrop-filter:blur(5px); align-items:center; justify-content:center;">
		<div style="background:#fff; width:100%; max-width:460px; border-radius:20px; box-shadow:0 28px 64px rgba(0,0,0,0.22); overflow:hidden; margin:0 16px;" onclick="event.stopPropagation()">
			<div id="dlhsConfirmIconWrap" style="padding:30px 24px 4px; text-align:center; font-size:42px; color:#f59e0b;">
				<i class="fa fa-question-circle"></i>
			</div>
			<div style="padding:12px 32px 32px;">
				<p id="dlhsConfirmMsg" style="font-size:15px; color:#222; text-align:center; margin:0 0 26px; line-height:1.65;"></p>
				<div style="display:flex; gap:12px; justify-content:center;">
					<button id="dlhsConfirmCancelBtn" type="button" style="flex:1; max-width:150px; padding:12px 0; border-radius:50px; border:1.5px solid #ddd; background:#f5f5f5; color:#555; font-size:14px; font-weight:600; cursor:pointer; transition:background 0.2s;">Cancel</button>
					<button id="dlhsConfirmOkBtn" type="button" style="flex:1; max-width:150px; padding:12px 0; border-radius:50px; border:none; background:linear-gradient(135deg,#003366,#0055aa); color:#ffd700; font-size:14px; font-weight:700; cursor:pointer; box-shadow:0 4px 16px rgba(0,51,102,0.28); transition:all 0.2s;">Confirm</button>
				</div>
			</div>
		</div>
	</div>

	<!-- ===== DLHS Universal Alert Modal ===== -->
	<div id="dlhsAlertModal" style="display:none; position:fixed; inset:0; z-index:999991; background:rgba(0,0,0,0.48); backdrop-filter:blur(5px); -webkit-backdrop-filter:blur(5px); align-items:center; justify-content:center;">
		<div style="background:#fff; width:100%; max-width:420px; border-radius:20px; box-shadow:0 28px 64px rgba(0,0,0,0.22); overflow:hidden; margin:0 16px;" onclick="event.stopPropagation()">
			<div id="dlhsAlertIconWrap" style="padding:30px 24px 4px; text-align:center; font-size:42px; color:#3b82f6;">
				<i class="fa fa-info-circle"></i>
			</div>
			<div style="padding:12px 32px 32px;">
				<p id="dlhsAlertMsg" style="font-size:15px; color:#222; text-align:center; margin:0 0 26px; line-height:1.65;"></p>
				<div style="text-align:center;">
					<button id="dlhsAlertOkBtn" type="button" style="padding:12px 48px; border-radius:50px; border:none; background:linear-gradient(135deg,#003366,#0055aa); color:#ffd700; font-size:14px; font-weight:700; cursor:pointer; box-shadow:0 4px 16px rgba(0,51,102,0.28);">OK</button>
				</div>
			</div>
		</div>
	</div>

	<style>
		@keyframes dlhsModalPop {
			from { opacity:0; transform:scale(0.86) translateY(16px); }
			to   { opacity:1; transform:scale(1) translateY(0); }
		}
		#dlhsConfirmModal.dlhs-open, #dlhsAlertModal.dlhs-open { display:flex !important; }
		#dlhsConfirmModal.dlhs-open > div, #dlhsAlertModal.dlhs-open > div {
			animation: dlhsModalPop 0.28s cubic-bezier(0.34,1.56,0.64,1);
		}
		#dlhsConfirmCancelBtn:hover { background:#e8e8e8; }
		#dlhsConfirmOkBtn:hover { background:linear-gradient(135deg,#00285c,#004499); transform:translateY(-1px); }
		#dlhsAlertOkBtn:hover { background:linear-gradient(135deg,#00285c,#004499); transform:translateY(-1px); }
	</style>

	<script>
	(function(){
		var _dlhsCb = null;

		window.dlhsConfirm = function(message, onConfirm) {
			_dlhsCb = onConfirm || null;
			document.getElementById('dlhsConfirmMsg').textContent = message;
			document.getElementById('dlhsConfirmIconWrap').innerHTML = '<i class="fa fa-question-circle" style="color:#f59e0b;"></i>';
			document.getElementById('dlhsConfirmModal').classList.add('dlhs-open');
		};

		window.dlhsAlert = function(message, type) {
			document.getElementById('dlhsAlertMsg').textContent = message;
			var iconWrap = document.getElementById('dlhsAlertIconWrap');
			if (type === 'error') {
				iconWrap.innerHTML = '<i class="fa fa-times-circle" style="color:#ef4444;"></i>';
			} else if (type === 'success') {
				iconWrap.innerHTML = '<i class="fa fa-check-circle" style="color:#22c55e;"></i>';
			} else {
				iconWrap.innerHTML = '<i class="fa fa-info-circle" style="color:#3b82f6;"></i>';
			}
			document.getElementById('dlhsAlertModal').classList.add('dlhs-open');
		};

		document.addEventListener('DOMContentLoaded', function(){
			document.getElementById('dlhsConfirmOkBtn').addEventListener('click', function(){
				document.getElementById('dlhsConfirmModal').classList.remove('dlhs-open');
				if (typeof _dlhsCb === 'function') { var cb = _dlhsCb; _dlhsCb = null; cb(); }
			});
			document.getElementById('dlhsConfirmCancelBtn').addEventListener('click', function(){
				document.getElementById('dlhsConfirmModal').classList.remove('dlhs-open');
				_dlhsCb = null;
			});
			document.getElementById('dlhsConfirmModal').addEventListener('click', function(e){
				if (e.target === this) { this.classList.remove('dlhs-open'); _dlhsCb = null; }
			});
			document.getElementById('dlhsAlertOkBtn').addEventListener('click', function(){
				document.getElementById('dlhsAlertModal').classList.remove('dlhs-open');
			});
			document.getElementById('dlhsAlertModal').addEventListener('click', function(e){
				if (e.target === this) { this.classList.remove('dlhs-open'); }
			});
		});
	})();
	</script>

  </body>
</html>
