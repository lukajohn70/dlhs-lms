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

    <title>Pending Tests | DLHS</title>

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- bootstrap theme -->
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <!--external css-->
    <!-- font icon -->
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
	<link href="fontAwesome/css/fontawesome.css" rel="stylesheet">
	<link href="fontAwesome/css/brands.css" rel="stylesheet">
	<link href="fontAwesome/css/solid.css" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="../../datatables/css/rowReorder.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>
	<script src="jQuery3.3.1.js"></script>
	<script src="addYearGroupAjax.js"></script>
	<style>
		.modal1 {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            padding-top: 100px; /* Location of the box */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            }
			
			.modal-content {
            border-radius:7px;
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%;
            color:black;
            }

            /* The Close Button */
            .close1{
            color: #ffffff;;
            float: right;
			padding-right:20px;
			padding-top:20px;
            font-size: 28px;
            font-weight: bold;
            }

            .close1:hover, .close1:focus {
            color: #000;
            text-decoration: none;
            cursor: pointer;
            }
	</style>
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
				<div class="col-lg-12">
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Tests yet to be started</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Yet to be started</li>
						<li><a href="#" style="color:#0acca2;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a></li>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-12">
						<section class="panel">
							<header class="panel-heading">
								View yet to be started tests
							</header>
							<div class="panel-body">
								<div class="table-responsive">
									<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
												<thead>
													<tr>
														<th><center>S/NO</center></th>
														<th><center>TEST NAME</center></th>
														<th><center>YEAR GROUP</center></th>
														<th><center>TEST DATE</center></th>
														<th><center>TIME TO START</center></th>
														<th><center>DURATION</center></th>
														<th><center>TEST TEACHER</center></th>
														<th><center>TEST STATUS</center></th>
														<th><center>ACTION</center></th>
													</tr>
												</thead>
												<tbody>
													
												</tbody>
											</table>
								</div>
								<!-- End of table-responsive -->
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

    <!-- jquery ui -->
    <script src="js/jquery-ui-1.9.2.custom.min.js"></script>

    <!--custom checkbox & radio-->
    <script type="text/javascript" src="js/ga.js"></script>
    <!--custom switch-->
    <script src="js/bootstrap-switch.js"></script>
    <!--custom tagsinput-->
    <script src="js/jquery.tagsinput.js"></script>
    
    <!-- colorpicker -->
   
    <!-- bootstrap-wysiwyg -->
    <script src="js/jquery.hotkeys.js"></script>
    <script src="js/bootstrap-wysiwyg.js"></script>
    <script src="js/bootstrap-wysiwyg-custom.js"></script>
    <!-- ck editor -->
    <script type="text/javascript" src="assets/ckeditor/ckeditor.js"></script>
    <!-- custom form component script for this page-->
    <script src="js/form-component.js"></script>
    <!-- custome script for all page -->
    <script src="js/scripts.js"></script>
	<script src="../../datatables/js/jquery.dataTables.min.js"></script>
	<script src="../../datatables/js/dataTables.rowReorder.min.js"></script>
	<script src="../../datatables/js/dataTables.responsive.min.js"></script>
	<script src="../../datatables/js/dataTables.buttons.min.js"></script>
	<script src="../../datatables/js/buttons.flash.min.js"></script>
	<script src="../../datatables/js/jszip.min.js"></script>
	<script src="../../datatables/js/pdfmake.min.js"></script>
	<script src="../../datatables/js/vfs_fonts.js"></script>
	<script src="../../datatables/js/buttons.html5.min.js"></script>
	<script src="../../datatables/js/buttons.print.min.js"></script>
	<script>
		//Function to get pending yet to start tests
		function callTable()	//Declaration of the data table function
		{
			var idToSend = 0;
			$.ajax({
					url: 'getYetToBeStartedTests.php',
					type: 'POST',
					data: {idToSend:idToSend},
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var testId = response[i].testId;
							var testName = response[i].testName;
							var subjectName = response[i].subjectName;
							var yearGroupName = response[i].yearGroupName;
							var testDate = response[i].testDate;
							var startTime = response[i].startTime;
							var duration = response[i].duration;
							var teacherName = response[i].teacherName;					
							
							var tr_str = "<tr>" +
								"<td align='center'>" + (i+1) + "</td>" +
								"<td align='center'>" + testName + " ("+subjectName+")"+"</td>" +
								"<td align='center'>" + yearGroupName + "</td>" +
								"<td align='center'>" + testDate + "</td>" +
								"<td align='center'>" + startTime + "</td>" +
								"<td align='center'>" + duration + "</td>" +
								"<td align='center'>" + teacherName + "</td>" +
								"<td align='center'>" + "Yet to be started - <i class='fa fa-rocket' aria-hidden='true' style='color:red; cursor:pointer;' title='START NOW (Override Schedule)' onclick='clickToStartOverride(\""+testId+"\",\""+testName+"\")'></i>" + "</td>" +
								"<td align='center'><i class='fa fa-hourglass-start' aria-hidden='true' style='color:brown; cursor:pointer;' title='Start test immediately (Admin Override)' onclick='clickToStartOverride(\""+testId+"\",\""+testName+"\")'></i></td>" +
								"</tr>";

							$("#example tbody").append(tr_str);
						}
						$('#example').DataTable( {
						"paging":   true,
						"ordering": true,
						"info":     true,
						"responsive": true,
						dom: 'lBfrtip',
						buttons: [
							'copy', 'csv', 'excel', 'pdf', 'print'
						],
						rowReorder: {
								selector: 'td:nth-child(2)'
							},
							"responsive": true
						});
					}
			});
		}
		//calling the data table function
		callTable();
		
		// Function to start test immediately (override schedule - Admin only)
		function clickToStartOverride(testId, testName) {
			var confirmIt = confirm("⚠️ ADMIN OVERRIDE\n\nThis will START '"+testName+"' IMMEDIATELY, bypassing the scheduled date/time.\n\nAre you sure you want to start this test now?");
			if(confirmIt == true)
			{
				var form_data = 'testId='+testId;
				
				$.ajax({
					url: "startTestOverride.php",
					type: "POST",        
					data: form_data,
					success: function (html) {             
						if (html==0)	// Session expired
						{                              
							window.location.replace("logout.php");
						}
						else if (html==1)	// Success
						{                              
							alert("✅ Test '"+testName+"' has been started successfully!\n\nStudents can now access this test.");
							location.reload();
						}
						else if (html==2)	// Already started
						{                              
							alert("⚠️ This test is already in progress.");
							location.reload();
						}
						else if (html==3)	// Already completed
						{                              
							alert("⚠️ This test has already been completed.");
							location.reload();
						}
						else if (html==4)	// Error
						{                              
							alert("❌ Could not start test. Please try again.");
						}
					}
				});
			}
		}
	</script>


  </body>
</html>
