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

    <title>Tests in progress | DLHS</title>

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
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Tests in progress</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Tests in progress</li>
						<li><a href="#" style="color:#0acca2;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a></li>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-12">
						<section class="panel">
							<header class="panel-heading">
								View tests in progress
								<div style="float: right;">
									<button type="button" class="btn btn-danger" onclick="openEndAllTestsModal()">
										<i class="fa fa-stop"></i> End All Tests
									</button>
								</div>
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
				
				<!-- End All Tests confirmation modal -->
				<div id="modalEndAllTests" class="modal1">
					<div class="modal-content">
						<span class="close1" onclick="closeEndAllTestsModal()">&times;</span>
						<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#dc3545;"><center style="font-size:20px;">Confirm End All Tests</center></div>
						<div style="background-color:#FDF2F2; padding:15px; margin:10px; border:1px solid #f5c6cb;">
							<div id="endAllTestsBody" style="color:#721c24;"></div>
							<input type="text" id="endAllTestsInput" class="form-control" placeholder="Type code to confirm" onkeyup="onEndAllTestsInput()" style="margin:10px 0;" />
							<div id="endAllTestsError" style="color:#a94442; font-size:14px; margin-bottom:10px;"></div>
							<div id="endAllTestsMsg" style="color:#31708f; font-size:14px; margin-bottom:10px;"></div>
							<button type="button" id="btnConfirmEndAllTests" class="btn btn-danger" onclick="confirmEndAllTests()" disabled>Confirm End All Tests</button>
							<button type="button" class="btn btn-default" onclick="closeEndAllTestsModal()" style="margin-left:8px;">Cancel</button>
						</div>
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
			var idToSend = 1;
			$.ajax({
					url: 'getTestsInProgress.php',
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
								"<td align='center'>" + "Test in progress" + "</td>" +
								
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
		
		// End All Tests functionality
		var END_ALL_CODE = null;
		
		function generateCode(){ 
			var c='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', r=''; 
			for(var i=0;i<8;i++){ 
				r+=c.charAt(Math.floor(Math.random()*c.length)); 
			} 
			return r; 
		}
		
		function openEndAllTestsModal(){
			END_ALL_CODE = generateCode();
			var body = '<p>You are about to <b>END ALL TESTS</b> that are currently in progress.</p>' +
				'<div style="background-color:#fff3cd; border:1px solid #ffeaa7; border-radius:5px; padding:10px; margin-bottom:10px;">' +
				'<div style="color:#856404; font-weight:bold;">Security confirmation required</div>' +
				'<div>Type this code: <span style="color:#dc3545; font-family:monospace; padding:6px 10px; border:2px solid #dc3545; border-radius:4px; letter-spacing:2px;">' + END_ALL_CODE + '</span></div>' +
				'</div>' +
				'<p style="color:#721c24; font-weight:bold;">This will immediately end all tests that are currently running. Students will be logged out and their progress will be saved. This action cannot be undone.</p>';
			
			document.getElementById('endAllTestsBody').innerHTML = body;
			document.getElementById('endAllTestsInput').value = '';
			document.getElementById('endAllTestsError').innerHTML = '';
			document.getElementById('endAllTestsMsg').innerHTML = '';
			document.getElementById('btnConfirmEndAllTests').disabled = true;
			document.getElementById('modalEndAllTests').style.display = 'block';
		}
		
		function closeEndAllTestsModal() {
			document.getElementById('modalEndAllTests').style.display = 'none';
		}
		
		function onEndAllTestsInput() {
			var v = document.getElementById('endAllTestsInput').value.trim();
			var ok = (END_ALL_CODE && v === END_ALL_CODE);
			document.getElementById('btnConfirmEndAllTests').disabled = !ok;
			document.getElementById('endAllTestsError').innerHTML = '';
		}
		
		function confirmEndAllTests() {
			var v = document.getElementById('endAllTestsInput').value.trim();
			if (!(END_ALL_CODE && v === END_ALL_CODE)) {
				document.getElementById('endAllTestsError').innerHTML = 'Please type the confirmation code exactly as shown.';
				return;
			}
			
			document.getElementById('endAllTestsMsg').innerHTML = '<i class="fa fa-spinner fa-spin"></i> Ending all tests, please wait...';
			document.getElementById('btnConfirmEndAllTests').disabled = true;
			
			$.ajax({
				url: 'endAllTests.php',
				type: 'POST',
				data: {},
				success: function(response) {
					if (response == 0) {
						window.location.replace('logout.php');
					} else if (response == 1) {
						document.getElementById('endAllTestsMsg').innerHTML = '<i class="fa fa-check"></i> All tests have been ended successfully.';
						setTimeout(function() {
							closeEndAllTestsModal();
							// Refresh the table to show updated status
							$('#example').DataTable().destroy();
							$('#example tbody').empty();
							callTable();
						}, 2000);
					} else if (response == 3) {
						document.getElementById('endAllTestsMsg').innerHTML = '<i class="fa fa-info-circle"></i> No tests are currently in progress to end.';
						setTimeout(function() {
							closeEndAllTestsModal();
						}, 2000);
					} else {
						var errorMsg = 'Could not end all tests. ';
						if (response == 2) {
							errorMsg += 'Database update failed.';
						} else if (response == 4) {
							errorMsg += 'Database error occurred.';
						} else if (response == 5) {
							errorMsg += 'Database connection failed.';
						} else if (response == 6) {
							errorMsg += 'Query execution failed.';
						} else {
							errorMsg += 'Unknown error (Code: ' + response + ').';
						}
						document.getElementById('endAllTestsMsg').innerHTML = '<i class="fa fa-exclamation-triangle"></i> ' + errorMsg;
						document.getElementById('btnConfirmEndAllTests').disabled = false;
					}
				},
				error: function() {
					document.getElementById('endAllTestsMsg').innerHTML = 'Could not end all tests. Please try again.';
					document.getElementById('btnConfirmEndAllTests').disabled = false;
				}
			});
		}
	</script>


  </body>
</html>
