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
	<style>
		
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
					<h3 class="page-header"><i class="fa fa-user-md"></i> Names & IDs</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="icon_documents_alt"></i>Names & IDs</li>
						<li><a href="#" style="color:#0acca2;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a></li>
					</ol>
				</div>
			</div>
			
            <!-- page start-->
            <div class="row">
                <div class="col-lg-9">
                    <section class="panel">
						<header class="panel-heading tab-bg-info">
                            <ul class="nav nav-tabs">
                                <li class="active">
                                    <a data-toggle="tab" href="#recent-activity">
                                        <i class="icon-home"></i>
											Subjects and IDs
                                    </a>
                                </li>
                                <li>
                                    <a data-toggle="tab" href="#profile">
                                        <i class="icon-user"></i>
											Year Groups and IDs
                                    </a>
                                </li>
                                <li class="">
									<a data-toggle="tab" href="#edit-profile">
                                        <i class="icon-envelope"></i>
											Class Names and IDs
                                    </a>
                                </li>
                            </ul>
                        </header>
                        <div class="panel-body">
                            <div class="tab-content">
                                <div id="recent-activity" class="tab-pane active">
                                    <div class="profile-activity">                                          
										<fieldset style="border-radius:6px; border:2px solid #238E68; width:100%; padding-right:6px;"><br/>
											<?php
												$query = "SELECT * FROM subjects";
												$result = $connection->query($query);
																
													echo'<table border="0" cellpadding="4" width="100%" style="margin-left:3px;">';
														echo'<tr style="background-color:#236B8E;">
																<th width="10%" style="color:white;">S/NO</th>
																<th width="60%" style="color:white;"><center>SUBJECT NAME</center></th>
																<th width="30%" style="color:white;"><center>SUBJECT ID</center></th>											
																			
															</tr>';
																$i=0;
																$j=0;
																while ($row = $result->fetch_array(MYSQLI_NUM))
																{
																	$j=$j+1;
	
																	echo'<tr '.($j%2==0 ? 'style="background-color:#E8E8E8;"':".").'>
																			<td width="10%"><center>'.$j.'</center></td>
																			<td width="60%"><center>'.$row[1].'</center></td>
																			<td width="30%"><center>'.$row[0] .'</center></td>
																		</tr>';
																}
													echo'</div></table>';
																	
																
											?>
										<fieldset>
                                      </div>
                                  </div>
                                  <!-- profile -->
                                  <div id="profile" class="tab-pane">
                                    <section class="panel">
                                      <fieldset style="border-radius:6px; border:2px solid #238E68; width:100%; padding-right:6px;"><br/>
											<?php
												$query = "SELECT * FROM yeargroup";
												$result = $connection->query($query);
																
													echo'<table border="0" cellpadding="4" width="100%" style="margin-left:3px;">';
														echo'<tr style="background-color:#236B8E;">
																<th width="10%" style="color:white;">S/NO</th>
																<th width="45%" style="color:white;"><center>YEAR GROUP NAME</center></th>
																<th width="45%" style="color:white;"><center>ID</center></th>											
																			
															</tr>';
																$i=0;
																$j=0;
																while ($row = $result->fetch_array(MYSQLI_NUM))
																{
																	$j=$j+1;
	
																	echo'<tr '.($j%2==0 ? 'style="background-color:#E8E8E8;"':".").'>
																			<td width="10%"><center>'.$j.'</center></td>
																			<td width="45%"><center>'.$row[1].'</center></td>
																			<td width="45%"><center>'.$row[0] .'</center></td>
																		</tr>';
																}
													echo'</div></table>';
																	
																
											?>
										<fieldset>
                                    </section>
                                      <section>
                                          <div class="row">                                              
                                          </div>
                                      </section>
                                  </div>
                                  <!-- edit-profile -->
                                  <div id="edit-profile" class="tab-pane">
                                    <section class="panel">                                          
                                          <div class="panel-body bio-graph-info">
                                              <fieldset style="border-radius:6px; border:2px solid #238E68; width:100%; padding-right:6px;"><br/>
											<?php
												$query = "SELECT * FROM classes";
												$result = $connection->query($query);
																
													echo'<table border="0" cellpadding="4" width="100%" style="margin-left:3px;">';
														echo'<tr style="background-color:#236B8E;">
																<th width="10%" style="color:white;"><center>S/NO</center></th>
																<th width="30%" style="color:white;"><center>CLASS NAME</center></th>
																<th width="20%" style="color:white;"><center>CLASS ID</center></th>											
																<th width="30%" style="color:white;"><center>YEAR GROUP ID</center></th>											
																			
															</tr>';
																$i=0;
																$j=0;
																while ($row = $result->fetch_array(MYSQLI_NUM))
																{
																	$j=$j+1;
	
																	echo'<tr '.($j%2==0 ? 'style="background-color:#E8E8E8;"':".").'>
																			<td width="10%"><center>'.$j.'</center></td>
																			<td width="30%"><center>'.$row[2].'</center></td>
																			<td width="20%"><center>'.$row[0] .'</center></td>
																			<td width="30%"><center>'.$row[1] .'</center></td>
																		</tr>';
																}
													echo'</div></table>';
																	
																
											?>
										<fieldset>
                                          </div>
                                      </section>
                                  </div>
                              </div>
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
