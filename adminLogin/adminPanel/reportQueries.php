<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['adminLoggedIn']))
	{
		header('location:../index.php');
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
	}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DLHS Dashboard">
    <meta name="author" content="DLHS IT Department">
    <meta name="keyword" content="DLHS, Dashboard, Admin, Education, School">
    <link rel="shortcut icon" href="../images/dlhslogo2.jpg">

    <title>Report Queries | DLHS</title>

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- bootstrap theme -->
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <!--external css-->
    <!-- font icon -->
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
    <!-- Custom styles -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
	</style>
  </head>

  <body>
  <!-- container section start -->
  <section id="container" class="">
      <!--header start-->
	  <!--Including the header-->
		<?php include 'header.php'; ?>
		
		<!--Including the sidebar-->
		<?php include 'sideBar.php'; ?>
      
    <!--main content start-->
      <section id="main-content">
          <section class="wrapper">
		  <div class="row">
				<div class="col-lg-12">
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Query Reports</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Query Reports</li>
						<li><a href="#" style="color:#0acca2;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a></li>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-6">
						<section class="panel">
							<header class="panel-heading">
								... Title of form goes here ...
							</header>
							<div class="panel-body">
								<form>
									<div class="message2" id="message2" style="color:red;" align="center"></div><div class="message1" style="color:green; font-size:17px;" align="center"></div><br>
									<div class="form-group">
										<label>Name of Year Group (e.g. <em>JS 1</em>)</label>
										<input type="text" name="" class="form-control" placeholder="Enter Year Group" required >
									</div>
									<button type="submit" class="btn btn-primary" id="submit"><i class="fa fa-sign-in"></i> Submit</button>
								</form>
							</div>
						</section>
					</div>
					<div class="col-lg-6">
						<section class="panel">
							<header class="panel-heading">
								... Title of form goes here ...
							</header>
							<div class="panel-body">
								<form>
									<div class="message2" id="message2" style="color:red;" align="center"></div><div class="message1" style="color:green; font-size:17px;" align="center"></div><br>
									<div class="form-group">
										<label>Name of Year Group (e.g. <em>JS 1</em>)</label>
										<input type="text" name="" class="form-control" placeholder="Enter Year Group" required >
									</div>
									<button type="submit" class="btn btn-primary" id="submit"><i class="fa fa-sign-in"></i> Submit</button>
								</form>
							</div>
						</section>
					</div>
					
				</div>		
				<div class="row">
					<div class="col-lg-6">
						<section class="panel">
							<header class="panel-heading">
								... Title of form goes here ...
							</header>
							<div class="panel-body">
								<form>
									<div class="message2" id="message2" style="color:red;" align="center"></div><div class="message1" style="color:green; font-size:17px;" align="center"></div><br>
									<div class="form-group">
										<label>Name of Year Group (e.g. <em>JS 1</em>)</label>
										<input type="text" name="" class="form-control" placeholder="Enter Year Group" required >
									</div>
									<button type="submit" class="btn btn-primary" id="submit"><i class="fa fa-sign-in"></i> Submit</button>
								</form>
							</div>
						</section>
					</div>
					<div class="col-lg-6">
						<section class="panel">
							<header class="panel-heading">
								... Title of form goes here ...
							</header>
							<div class="panel-body">
								<form>
									<div class="message2" id="message2" style="color:red;" align="center"></div><div class="message1" style="color:green; font-size:17px;" align="center"></div><br>
									<div class="form-group">
										<label>Name of Year Group (e.g. <em>JS 1</em>)</label>
										<input type="text" name="" class="form-control" placeholder="Enter Year Group" required >
									</div>
									<button type="submit" class="btn btn-primary" id="submit"><i class="fa fa-sign-in"></i> Submit</button>
								</form>
							</div>
						</section>
					</div>
					
				</div>		
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
    <!-- nice scroll -->
    <script src="js/jquery.scrollTo.min.js"></script>
    <script src="js/jquery.nicescroll.js" type="text/javascript"></script>
    <!-- jquery knob -->
    <script src="assets/jquery-knob/js/jquery.knob.js"></script>
    <!--custome script for all page-->
    <script src="js/scripts.js"></script>

  <script>

      //knob
      $(".knob").knob();

  </script>


  </body>
</html>
