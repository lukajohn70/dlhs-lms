<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['userLoggedIn']))
	{
		header('location:../index.php');
	}
	include "../../db_connection/zamani_db_connection1.php";
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

    <title>Charts | Zamani College</title>

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

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 -->
    <!--[if lt IE 9]>
      <script src="js/html5shiv.js"></script>
      <script src="js/respond.min.js"></script>
      <script src="js/lte-ie7.js"></script>
    <![endif]-->
  </head>

  <body>
  <!-- container section start -->
  <section id="container" class="">
      <!--header start-->
      
      <!--Including the header-->
		<?php include 'header.php'; ?>

		<!--Including the sidebar-->
		<?php include 'sideBar.php '; ?>

      <!--main content start-->      
      <section id="main-content">
        <section class="wrapper">
		<div class="row">
				<div class="col-lg-12">
					<h3 class="page-header"><i class="icon_piechart"></i> Chart</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.html">Home</a></li>
						<li><i class="icon_piechart"></i>Chart</li>
						
					</ol>
				</div>
			</div>
            <div class="row">
              <!-- chart morris start -->
              <div class="col-lg-12">
                  <section class="panel">
                      <header class="panel-heading">
                          <h3>General Chart</Char>
                      </header>
                      <div class="panel-body">
                        <div class="tab-pane" id="chartjs">
                      <div class="row">
                          <!-- Line -->
                          <div class="col-lg-6">
                              <section class="panel">
                                  <header class="panel-heading">
                                      Line
                                  </header>
                                  <div class="panel-body text-center">
                                      <canvas id="line" height="300" width="450"></canvas>
                                  </div>
                              </section>
                          </div>                      
                          <!-- Bar -->
                          <div class="col-lg-6">
                              <section class="panel">
                                  <header class="panel-heading">
                                      Bar
                                  </header>
                                  <div class="panel-body text-center">
                                      <canvas id="bar" height="300" width="500"></canvas>
                                  </div>
                              </section>
                          </div>
                      </div>
                      <div class="row">
                          <!-- Radar -->
                          <div class="col-lg-6">
                              <section class="panel">
                                  <header class="panel-heading">
                                      Radar
                                  </header>
                                  <div class="panel-body text-center">
                                      <canvas id="radar" height="300" width="400"></canvas>
                                  </div>
                              </section>
                          </div>
                          <!-- Polar Area -->
                          <div class="col-lg-6">
                              <section class="panel">
                                  <header class="panel-heading">
                                      Polar Area
                                  </header>
                                  <div class="panel-body text-center">
                                      <canvas id="polarArea" height="300" width="400"></canvas>
                                  </div>
                              </section>
                          </div>
                      </div>
                      <div class="row">
                          
                          <!-- Pie -->
                          <div class="col-lg-6">
                              <section class="panel">
                                  <header class="panel-heading">
                                      Pie
                                  </header>
                                  <div class="panel-body text-center">
                                      <canvas id="pie" height="300" width="400"></canvas>
                                  </div>
                              </section>
                          </div>
                          <!-- Doughnut -->
                          <div class="col-lg-6">
                              <section class="panel">
                                  <header class="panel-heading">
                                      Doughnut
                                  </header>
                                  <div class="panel-body text-center">
                                      <canvas id="doughnut" height="300" width="400"></canvas>
                                  </div>
                              </section>
                          </div>
                      </div>
                  </div>
                      </div>
                      </div>
                    </section>
              </div>
              <!-- chart morris start -->
            </div>
      </section>
      <!--main content end-->
      <div class="text-right">
            <div class="credits">
                &copy; <?php echo " ". date("Y")." ";?> Copyright: <a href="https://zamanicollege.com/" style="padding-right:10px;">Zamani College</a>
            </div>
        </div>
    </section>
    <!-- container section end -->
    <!-- javascripts -->
    <script src="js/jquery.js"></script>
    <script src="js/jquery-1.8.3.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <!-- nice scroll -->
    <script src="js/jquery.scrollTo.min.js"></script>
    <script src="js/jquery.nicescroll.js" type="text/javascript"></script>
    <!-- chartjs -->
    <script src="assets/chart-master/Chart.js"></script>
    <!-- custom chart script for this page only-->
    <script src="js/chartjs-custom.js"></script>
    <!--custome script for all page-->
    <script src="js/scripts.js"></script>

  </body>
</html>
