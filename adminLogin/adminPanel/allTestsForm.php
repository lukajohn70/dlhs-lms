<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['adminLoggedIn']))
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

    <title>All Tests | DLHS</title>

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
    
    <!-- color picker -->
    
    <!-- Custom styles -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
<!-- rowReorder removed: <link rel="stylesheet" type="text/css" href="../../datatables/css/rowReorder.dataTables.min.css"/> -->
	<link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>
	<!-- Select2 (searchable selects) -->
	<link rel="stylesheet" type="text/css" href="../vendor/select2/select2.min.css">
	<script src="../../libs/jquery.min.js"></script>
<!-- Duplicate jQuery removed: <!-- Redundant jQuery removed: <script src="jQuery3.3.1.js"></script> --> -->
	<script>
		function getChecked()
		{
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message5').html('');
			$('.message6').html('');
			$('.message7').html('');
			$('.message8').html('');
			$('.message9').html('');
			$('.message10').html('');
			$('.message11').html('');
			$('.message12').html('');
			
			var favorite1 = []
			var checkboxes = document.querySelectorAll("input[name='rowSelectCheckBox[]']:checked");

			for (var i = 0; i < checkboxes.length; i++) {
				favorite1.push(checkboxes[i].value)
			}
			
			favorite=favorite1.join(",")
			var testId = document.getElementById('test').value;
			var testName2 = $("#test option:selected").text();
			var studentClass = document.getElementById('studentClass').value;
			var studentClassName = $("#studentClass option:selected").text();
            		
			$('.message1').html('')
			if (favorite=="")
			{
				$('.message10').html('<i class="fa fa-info-circle"></i> Please check at least one student before submitting')
			}
			else if (testId=="")
			{
				$('.message10').html('<i class="fa fa-info-circle"></i> Please select test')
			}
			else if (studentClass=="")
			{
				$('.message10').html('<i class="fa fa-info-circle"></i> Please select student\'s class')
			}
			else
			{
				$('.message9').html('');
								
				//organize the data properly
						var form_data = 
						  'testId='+testId+
						  '&checkedStudents='+favorite;

						$.ajax({
							url: "addStudentsForTest.php",
							type: "POST",     
							data: form_data,    
							success: function (html) {             
																
								if (html==0)	//If session is expired.
								{                              
									 window.location.replace("logout.php");
								}
								else if (html==1)	//If examinees successfully added to write test
								{                              
									$('.message10').html('');
									$('.message9').html('<i class="fa fa-check-circle"></i> The selected Student(s) from '+studentClassName+' has/have successfully been added to write '+testName2+'.').fadeIn('slow');
									viewAdded1(testId, studentClass)
								}
								else 	//If insertion is unsuccessful	
								{                              
									 $('.message9').html('');
									 $('.message10').html('Could not add selected Student(s). Please try again.').fadeIn('slow');
								}
							}
										
						});
			}
		}
		
		function onlyNumberKey(evt) {
             
            // Only ASCII character in that range allowed
            var ASCIICode = (evt.which) ? evt.which : evt.keyCode
            if (ASCIICode > 31 && (ASCIICode < 48 || ASCIICode > 57))
                return false;
            return true;
        }
	</script>
	<style>
		.modal1, .modal2 {
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
        
        /* Select2 Dropdown Visibility Fix */
        .select2-container {
            z-index: 9999 !important;
        }
        .select2-dropdown {
            z-index: 10000 !important;
        }
        .panel-body, .panel, .col-lg-4, .col-lg-8 {
            overflow: visible !important;
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
            color: #ffffff;
            float: right;
			padding-right:20px;
			padding-top:20px;
            font-size: 28px;
            font-weight: bold;
            }

            .close1:hover, .close1:focus {
            color: red;
            text-decoration: none;
            cursor: pointer;
            }
			
			body{
			overflow-x:hidden;
			overflow-y:auto;
			height:900px;
		}
		
		/* Custom Search Styling */
		#customSearchInput {
			transition: all 0.3s ease;
		}
		
		#customSearchInput:focus {
			border-color: #0acca2 !important;
			box-shadow: 0 0 12px rgba(10, 204, 162, 0.4) !important;
			outline: none;
		}
		
		#customSearchBtn {
			background: linear-gradient(135deg, #0acca2 0%, #089f8b 100%);
			border: none;
			color: white;
			transition: all 0.3s ease;
			font-size: 14px;
		}
		
		#customSearchBtn:hover {
			background: linear-gradient(135deg, #089f8b 0%, #0acca2 100%);
			transform: translateY(-2px);
			box-shadow: 0 6px 12px rgba(10, 204, 162, 0.4);
		}
		
		#customSearchBtn:active {
			transform: translateY(0);
			box-shadow: 0 2px 4px rgba(10, 204, 162, 0.3);
		}
		
		#clearSearchBtn {
			transition: all 0.3s ease;
			background-color: #f8f9fa;
			border: 1px solid #ddd;
			color: #666;
		}
		
		#clearSearchBtn:hover {
			background-color: #fff;
			border-color: #d9534f;
			color: #d9534f;
			transform: translateY(-2px);
			box-shadow: 0 4px 8px rgba(217, 83, 79, 0.2);
		}
		
		@media (max-width: 992px) {
			#customSearchInput {
				max-width: 100% !important;
			}
		}
		
		@media (max-width: 768px) {
			.row > .col-lg-12 > div > div {
				flex-direction: column !important;
				align-items: stretch !important;
			}
			
			.row > .col-lg-12 > div > div > div {
				width: 100% !important;
				max-width: 100% !important;
			}
			
			#customSearchBtn, #clearSearchBtn {
				width: 100%;
				margin-top: 10px;
			}
		}
		
		/* Review Toggle Switch Styling */
		.review-toggle-switch {
			position: relative;
			display: inline-block;
			width: 50px;
			height: 24px;
		}
		
		.review-toggle-switch input {
			opacity: 0;
			width: 0;
			height: 0;
		}
		
		.review-slider {
			position: absolute;
			cursor: pointer;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background-color: #ccc;
			transition: .4s;
			border-radius: 24px;
		}
		
		.review-slider:before {
			position: absolute;
			content: "";
			height: 18px;
			width: 18px;
			left: 3px;
			bottom: 3px;
			background-color: white;
			transition: .4s;
			border-radius: 50%;
		}
		
		.review-toggle-switch input:checked + .review-slider {
			background-color: #28a745;
		}
		
		.review-toggle-switch input:checked + .review-slider:before {
			transform: translateX(26px);
		}
		
		.review-toggle-switch input:focus + .review-slider {
			box-shadow: 0 0 1px #28a745;
		}
		
		.review-toggle-switch:hover .review-slider {
			box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
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
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Manage all Tests</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Tests</li>
						<a href="#" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
					</ol>
				</div>
			</div>
              
              <!-- Search Section -->
				<div class="row" style="margin-bottom: 20px;">
					<div class="col-lg-12">
						<div style="background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #0acca2;">
							<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
								<div style="flex: 0 0 auto;">
									<h4 style="margin: 0; color: #003366; font-weight: 600;">
										<i class="fa fa-search" style="color: #0acca2;"></i> Search Tests
									</h4>
									<p style="margin: 5px 0 0 0; color: #666; font-size: 13px;">Find tests by name, date, subject, or teacher</p>
								</div>
								<div style="flex: 1 1 auto; max-width: 600px; display: flex; gap: 8px;">
									<input type="text" id="customSearchInput" class="form-control" placeholder="🔍 Type to search tests..." style="flex: 1; height: 40px; border: 2px solid #e0e0e0; border-radius: 6px; font-size: 14px;">
									<button type="button" id="customSearchBtn" class="btn btn-primary" onclick="performSearch()" style="height: 40px; padding: 0 25px; border-radius: 6px; white-space: nowrap; font-weight: 600;">
										<i class="fa fa-search"></i> Search
									</button>
									<button type="button" id="clearSearchBtn" class="btn btn-default" onclick="clearSearch()" title="Clear search" style="height: 40px; padding: 0 20px; border-radius: 6px;">
										<i class="fa fa-times"></i> Clear
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Review Control Section -->
				<div class="row" style="margin-bottom: 20px;">
					<div class="col-lg-12">
						<div style="background: linear-gradient(135deg, #f0f8ff 0%, #e6f3ff 100%); padding: 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-left: 4px solid #0066cc;">
							<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
								<div style="flex: 0 0 auto;">
									<h4 style="margin: 0; color: #003366; font-weight: 600;">
										<i class="fa fa-eye" style="color: #0066cc;"></i> Exam Review Control
									</h4>
									<p style="margin: 5px 0 0 0; color: #666; font-size: 13px;">Control whether students can review their tests after completion</p>
								</div>
								<div style="flex: 1 1 auto; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
									<div style="display: flex; align-items: center; gap: 8px;">
										<label style="margin: 0; font-weight: 600; color: #333;">Filter by Review:</label>
										<select class="form-control" id="reviewFilter" onchange="filterByReview()" style="width: 180px; height: 36px; border: 2px solid #ddd; border-radius: 6px;">
											<option value="all">All Tests</option>
											<option value="Yes">Review Enabled</option>
											<option value="No">Review Disabled</option>
										</select>
									</div>
									<button type="button" class="btn btn-success btn-sm" onclick="bulkToggleReview('Yes')" id="bulkEnableReviewBtn" style="height: 36px; padding: 0 15px; border-radius: 6px; white-space: nowrap;">
										<i class="fa fa-check-circle"></i> Enable Review (<span id="selectedForEnable">0</span>)
									</button>
									<button type="button" class="btn btn-warning btn-sm" onclick="bulkToggleReview('No')" id="bulkDisableReviewBtn" style="height: 36px; padding: 0 15px; border-radius: 6px; white-space: nowrap;">
										<i class="fa fa-times-circle"></i> Disable Review (<span id="selectedForDisable">0</span>)
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>
              
				<div class="row">
					<div class="col-lg-12">
						<section class="panel">
							<header class="panel-heading">
								View | Edit | Delete Test &nbsp; &nbsp; 
								<button type='button' onClick='deleteSelectedTests()' class='btn btn-danger btn-sm' id='deleteSelectedTestsBtn' disabled><i class='fa fa-trash'></i> Delete Selected Tests (<span id='selectedTestsCount'>0</span>)</button>
								&nbsp; &nbsp; <div class="message4" style="color:green; font-size:17px;" align="center"></div><div class="message5" style="color:red; font-size:17px;" align="center"></div>
							</header>
							<div class="panel-body">
								<div class="table-responsive">
									<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
										<thead>
											<tr>
												<th width="1%"><center><input type='checkbox' id='checkUncheckAllTests' onClick='CheckUncheckAllTests()' /> &nbsp;Select All</center></th>
												<th width="1%"><center>S/NO</center></th>
												<th width="20%"><center>TEST NAME</center></th>
												<th width="13%"><center>DATE(yyyy-mm-dd)</center></th>
												<th width="6%"><center>START</center></th>
												<th width="6%"><center>DURATION</center></th>
												<th width="6%"><center>YR GRP</center></th>
												<th width="4%"><center>YEAR</center></th>
												<th width="10%"><center>REVIEW</center></th>
												<th width="7%"><center>TEACHER</center></th>
												<th width="10%"><center>STATUS</center></th>
												<th width="7%"><center>ACTION</center></th>
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
					
				</div><!--End of row for viewing all tests-->
				<div class="row"><!--Beginning of row for adding students to a test and viewing students of a test-->
					<div class="col-lg-4">
						<section class="panel">
							<header class="panel-heading">
								View/Add Student(s) to Test
							</header>
							<div class="panel-body">
								<form class="form-horizontal" name="myForm">
									<div class="form-group">
										<label class="control-label col-sm-12"><strong>View All students added to a test or add new students to a test</strong></label>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-5"><strong>Select Test to Assign Student(s) to:</strong></label>
										<div class="col-sm-7">
											<select class="form-control" name="test" id="test" required >
												<option value="">... Select Test ...</option>
												<?php
													$test="select * from tests";
													$result = $connection->query($test);
													while($row = $result->fetch_array(MYSQLI_NUM)){
												?>
												<option value="<?php echo $row[0]; ?>"><?php echo $row[2]; ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-5"><strong>Select Class of Student(s) to add:</strong></label>
										<div class="col-sm-7">
											<select class="form-control" name="studentClass" id="studentClass" required style="color:#000000;" data-placeholder="... Select class ...">
												<option value="">... Select class ...</option>
											</select>
										</div>
										<br>
									</div>
									
									<div id="result"></div><br>
									<div class='message10' id='message10' style='color:red;' align='center'></div><div class='message9' style='color:green; font-size:17px;'></div>
								</form>
                            </div>
						</section>
					</div>
					<div class="col-lg-8">
						<section class="panel">
							<header class="panel-heading">
								View Students added to a test on the table below &nbsp; &nbsp; &nbsp;<b>Note: You cannot add time here to students of a test that has not started</b>						
							</header>
							<div class="panel-body">
								<div class='message3' style='color:green; font-size:17px;' align='center'></div><br>
								<div class="table-responsive">
									<table id="example1" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
										<thead>
											<tr>
												<th width="1%"><center>S/NO</center></th>
												<th width="32%"><center>NAME</center></th>
												<th width="15%"><center>CLASS</center></th>
												<th width="10%"><center>DURATION</center></th>
												<th width="10%"><center>REMAINING TIME</center></th>
												<th width="10%"><center>STATUS</center></th>
												<th width="15%"><center><input type='checkbox' id='checkUncheckAll2' onClick='CheckUncheckAll2()' /> SELECT/UNSELECT</center></th>
												<th width="7%"><center>ACTION</center></th>
											</tr>
										</thead>
										<tbody>
												
										</tbody>
									</table>
								</div><br>
								<!-- End of table-responsive -->
								<div class="form-group" hidden id="theTimeEdit">
									<label class="control-label">Enter time in minutes to be added/subtracted from test time of selected students (e.g., <b>10</b> &nbsp;for 10 minutes)</label><br>
									<input type="hidden" name="testIdToAddStudentsTime" id="testIdToAddStudentsTime" />
									<input type="hidden" name="classIdToModifyTime" id="classIdToModifyTime" />
									<input type="text" name="valueToModify" class="form-control" id="valueToModify" onkeypress="return onlyNumberKey(event)" placeholder="Enter time to add/subtract in minutes" onmouseout="checkEmail()" required ><br>
									<input type="radio" name="addOrSubtract1" value="add" > <font style="color:#000000;">Add time above to selected test</font> &nbsp; &nbsp; <input type="radio" name="addOrSubtract1" value="subtract"> <font style="color:#000000;">Subtract time above from selected test</font>
									<br><br>
									<button type='button' id='viewAlreadyAdded' onClick='addOrSubtractTestTimeDuringTest()' class='btn btn-primary'>Submit to update time</button><br>
									<div class="message12" style="color:red;" align="center"></div><div class="message11" style="color:green; font-size:17px;" align="center"></div>
								</div>
								<br>
                            </div>
						</section>
					</div>
				</div>				
                </div>
              </div>
              <!-- page end-->
          </section>
      </section>
	  
	<div id="myModal1" class="modal1"> <!--Start of Edit modal-->
		<div class="modal-content">
			<span class="close1">&times;</span>
			<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">Edit Test</center></div>
            <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                <form method="post" id="editTestForm">
                    <div class="item form-group">
						<div class="col-md-12">
							<label><span style="color:#000000;">Test name: </span> <span class="required" style="color:red;">*</span></label><br>
							<input type="hidden" name="testId" required="required" id="testId">
							<input type="text" name="testName1" required="required" maxlength = "20" id="testName1" class="form-control">
						</div>
						<div class="col-md-12">
							<label><span style="color:#000000;">Test date: </span> <span class="required" style="color:red;">*</span></label><br>
							<input type="date" name="testDate1" required="required" id="testDate1" class="form-control">
						</div>
						<div class="col-md-12">
							<label><span style="color:#000000;">Duration (In Minutes:<em> e.g., 50</em>)</span> <span class="required" style="color:red;">*</span></label><br>
							<input type="text" name="testDuration1" required="required" onkeypress="javascript:return isNumber(event)" id="testDuration1" class="form-control">
						</div>
						<div class="row form-group">
							<div class="col-md-4" style="padding-right:32px; padding-left:32px;">
								<label><span style="color:#000000;">Start time (<em>Select hour</em>): </span> <span class="required" style="color:red;">*</span></label><br>
								<select class="form-control" name="startHour1" id="startHour1" required >
											<option value="">... Select Hour ...</option>
											<?php
											
											$hour="select * from hour";
											$result1 = $connection->query($hour);
											while($row1 = $result1->fetch_array(MYSQLI_NUM)){
											?>
											<option value="<?php echo $row1[0]; ?>"><?php echo $row1[1]; ?></option>
											<?php } ?>
										</select>
							</div>
							<div class="col-md-4" style="padding-right:32px; padding-left:32px;">
								<label><span style="color:#000000;">Start time (<em>Select minute</em>): </span> <span class="required" style="color:red;">*</span></label><br>
								<select class="form-control" name="startMinute1" id="startMinute1" required >
											<option value="">... Select Minute ...</option>
											<?php
											
											$minutes="select * from minutes";
											$result1 = $connection->query($minutes);
											while($row1 = $result1->fetch_array(MYSQLI_NUM)){
											?>
											<option value="<?php echo $row1[0]; ?>"><?php echo $row1[1]; ?></option>
											<?php } ?>
										</select>
							</div>
							<div class="col-md-4" style="padding-right:32px; padding-left:32px;">
								<label><span style="color:#000000;">Start time (<em>AM or PM?</em>) </span> <span class="required" style="color:red;">*</span></label><br>
								<select class="form-control" name="amOrPm1" id="amOrPm1" required >
											<option value="">... Set as AM or PM ...</option>
											<option value="AM">AM</option>
											<option value="PM">PM</option>
										</select>
							</div>
						</div>
						<div class="row form-group">
							<div class="col-md-4" style="padding-right:32px; padding-left:32px;">
								<label><span style="color:#000000;">Subject: </span> <span class="required" style="color:red;">*</span></label><br>
								<select class="form-control" name="subjectId1" id="subjectId1" required >
											<option value="">... Select Subject ...</option>
											<?php
											
											$subjects="select * from subjects ORDER BY subjectName ASC";
											$result1 = $connection->query($subjects);
											while($row1 = $result1->fetch_array(MYSQLI_NUM)){
											?>
											<option value="<?php echo $row1[0]; ?>"><?php echo $row1[1]; ?></option>
											<?php } ?>
										</select>
							</div>
							<div class="col-md-4" style="padding-right:32px; padding-left:32px;">
								<label><span style="color:#000000;">Year group: </span> <span class="required" style="color:red;">*</span></label><br>
								<select class="form-control" name="yearGroup1" id="yearGroup1" required >
											<option value="">... Select Year group ...</option>
											<?php
											
											$yearGroup="select * from yeargroup";
											$result2 = $connection->query($yearGroup);
											while($row2 = $result2->fetch_array(MYSQLI_NUM)){
											?>
											<option value="<?php echo $row2[0]; ?>"><?php echo $row2[1]; ?></option>
											<?php } ?>
										</select>
							</div>
							<div class="col-md-4" style="padding-right:32px; padding-left:32px;">
								<label><span style="color:#000000;">Allow Review after test? </span> <span class="required" style="color:red;">*</span></label><br>
								<select class="form-control" name="reviewOption1" id="reviewOption1" required >
									<option value="">... Review option after test ...</option>
									<option value="Yes">Yes</option>
									<option value="No">No</option>
								</select>
							</div>
						</div>
						<div class="row form-group">
							<div class="col-md-4" style="padding-right:32px; padding-left:32px;">
								<label><span style="color:#000000;">Does Test have essay? </span> <span class="required" style="color:red;">*</span></label><br>
								<select class="form-control" name="essayOption1" id="essayOption1" required >
									<option value="">... Select Essay option ...</option>
									<option value="Yes">Yes</option>
									<option value="No">No</option>
								</select>
							</div>
							<div class="col-md-4" style="padding-right:32px; padding-left:32px;" id="addEssayTime1"></div>
						</div>
					</div>
					<div class="item form-group">
						<div class="col-md-12">
							<button class="btn btn-primary" type="reset">Reset</button>
							<button type="button" class="btn btn-success" onclick="editTestDetails()">Update</button>
							<div class="message3" id="message3" style="color:red;" align="center"></div>
						</div>
					</div>
                </form>
			</div>
			<br>
			<br>
			<hr>
		</div>	<!-- End of modal content-->
	</div><!-- End of edit modal-->
	
	<div id="myModal2" class="modal2"> <!--Start of modal to add/subtract time for all or selected students-->
		<div class="modal-content">
			<span class="close1">&times;</span>
			<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">Add/Subtract test time</center></div>
            <div style="background-color:#E9F1EA; padding:15px; color:#fff; margin:0px 10px 0px 10px;">
                <form method="post" id="editTestTimeForm">
                    <div class="item form-group">
						<div class="col-md-12">
							<label><span style="color:#000000;">Test details: </span> <span id="testDetails" style="color:#000000;"></span></label><br>
						</div>
						<div class="col-md-12">
							<label><span style="color:#000000;">Time to add/subtract (In Minutes:<em> e.g., 50</em>)</span> <span class="required" style="color:red;">*</span></label><br>
							<input type="hidden" name="testId2" required="required" id="testId2" class="form-control">
							<input type="text" name="testDuration2" required="required" onkeypress="javascript:return isNumber(event)" id="testDuration2" class="form-control">
						</div>
						<div class="col-md-12">
							<br>
							<input type="radio" name="addOrSubtract" value="add" > <font style="color:#000000;">Add time above to selected test</font> &nbsp; &nbsp; <input type="radio" name="addOrSubtract" value="subtract"> <font style="color:#000000;">Subtract time above from selected test</font>
						</div>
					</div>
					<div class="item form-group">
						<div class="col-md-12">
							<button class="btn btn-primary" type="reset">Reset</button>
							<button type="button" class="btn btn-success" onclick="addOrSubtractTestTime()">Change time</button>
							<div class="message6" id="message6" style="color:red;" align="center"></div>
						</div>
					</div>
                </form>
			</div>
			<br>
			<br>
			<hr>
		</div>	<!-- End of modal content-->
	</div><!-- End of modal to add/subtract time for all or selected students-->
	
	<div id="myModal3" class="modal2"> <!--Start of modal for delete test confirmation-->
		<div class="modal-content">
			<span class="close1">&times;</span>
			<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#dc3545;"><center style="font-size:22px;">⚠️ Delete Test Confirmation</center></div>
            <div style="background-color:#fff; padding:20px; color:#000; margin:0px 10px 0px 10px;">
                <div class="item form-group">
					<div class="col-md-12">
						<div id="deleteTestModalContent">
							<!-- Content will be populated dynamically -->
						</div>
					</div>
					<div class="col-md-12" style="margin-top: 20px;">
						<div class="form-group">
							<label style="color:#000; font-weight: bold;">Type the confirmation code to proceed:</label>
							<input type="text" id="deleteConfirmationInput" class="form-control" placeholder="Type the confirmation code here..." style="margin-bottom: 10px; font-family: monospace; text-align: center; font-size: 16px; letter-spacing: 2px;">
							<div id="confirmationError" style="color: red; font-size: 12px; margin-bottom: 10px;"></div>
						</div>
					</div>
					<div class="col-md-12" style="text-align: center;">
						<button type="button" class="btn btn-secondary" onclick="closeDeleteModal()" style="margin-right: 10px;">Cancel</button>
						<button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="handleConfirmDelete()" disabled>Delete</button>
					</div>
					<div class="col-md-12" style="margin-top: 15px;">
						<div id="deleteModalMessage" style="color:red; font-size:14px; text-align: center;"></div>
					</div>
				</div>
			</div>
			<br>
			<br>
			<hr>
		</div>	<!-- End of modal content-->
	</div><!-- End of modal for delete test confirmation-->
	  
      <!--main content end-->
      <div class="text-right">
        <div class="credits">
            <?php include "footer.php"; ?>
        </div>
    </div>
  </section>
  <!-- container section end -->
    <!-- javascripts -->
<!-- Duplicate jQuery removed: <script src="js/jquery.js"></script> -->
    <script src="js/bootstrap.min.js"></script>
    <!-- nice scroll -->
<script src="js/jquery.scrollTo.min.js"></script>
<!-- nicescroll removed to prevent click-blocking: <script src="js/jquery.nicescroll.js" type="text/javascript"></script> -->

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
    <!-- custome script for all page -->
    <script src="js/scripts.js"></script>
<script src="../../datatables/js/jquery.dataTables.min.js"></script>
<!-- rowReorder removed: <script src="../../datatables/js/dataTables.rowReorder.min.js"></script> -->
	<script src="../../datatables/js/dataTables.responsive.min.js"></script>
	<script src="../../datatables/js/dataTables.buttons.min.js"></script>
	<script src="../../datatables/js/buttons.flash.min.js"></script>
	<script src="../../datatables/js/jszip.min.js"></script>
	<script src="../../datatables/js/pdfmake.min.js"></script>
	<script src="../../datatables/js/vfs_fonts.js"></script>
	<script src="../../datatables/js/buttons.html5.min.js"></script>
	<script src="../../datatables/js/buttons.print.min.js"></script>
	<script src="modal_delete_functions.js"></script>
	<!-- Select2 JS (searchable selects) -->
	<script src="../vendor/select2/select2.min.js"></script>
	<script>
		$(document).ready(function(){
			function initSelect2() {
				// Temporarily disabling Select2 to verify if standard dropdowns work
				/*
				if ($.fn.select2){
					$('#test').select2({
						placeholder: '... Select Test ...',
						width: '100%'
					});
					$('#studentClass').select2({
						placeholder: '... Select class ...',
						width: '100%'
					});
				}
				*/
			}
			initSelect2();
		});
	</script>
	<script>
		// Handle confirm delete - determines if it's individual or bulk delete
		function handleConfirmDelete()
		{
			if (window.currentDeleteTestIds && window.currentDeleteTestIds.length > 1) {
				// Bulk delete
				confirmDeleteSelectedTests();
			} else {
				// Individual delete
				confirmDeleteTest();
			}
		}
		
		// Add real-time validation for confirmation input
		document.addEventListener('DOMContentLoaded', function() {
			var confirmationInput = document.getElementById('deleteConfirmationInput');
			var confirmBtn = document.getElementById('confirmDeleteBtn');
			
			if (confirmationInput && confirmBtn) {
				confirmationInput.addEventListener('input', function() {
					var inputValue = this.value.trim();
					var errorDiv = document.getElementById('confirmationError');
					var confirmationCode = window.currentConfirmationCode;
					
					if (confirmationCode && inputValue === confirmationCode) {
						// Correct confirmation code entered
						confirmBtn.disabled = false;
						errorDiv.innerHTML = '';
					} else if (inputValue.length > 0) {
						// Incorrect code entered
						confirmBtn.disabled = true;
						errorDiv.innerHTML = 'Please type the confirmation code exactly as shown above.';
					} else {
						// Empty input
						confirmBtn.disabled = true;
						errorDiv.innerHTML = '';
					}
				});
			}
		});
	</script>
	<script>
		function getStatus(testId, testName, testStatus)
		{
			if(testStatus == 0)
			{
				return "Yet to start - <i class='fa fa-hourglass-start' aria-hidden='true' style='color:brown; cursor:pointer;' title='Click to start test' onclick='clickToStart(\""+testId+"\",\""+testName+"\")'></i>";
			}
			else if(testStatus == 1)
			{
				return "<font style='color:blue; cursor:pointer;'>In progress - <i class='fa fa-hourglass-end' aria-hidden='true' title='Click to end test' onclick='clickToEnd(\""+testId+"\",\""+testName+"\")'></i></font>";
			}
			else if(testStatus == 2)
			{
				return "<font style='color:green;'>Ended - <i class='fa fa-refresh' aria-hidden='true' style='cursor:pointer;' title='Click to reschedule test' onclick='rescheduleTest(\""+testId+"\",\""+testName+"\")'></i>";
			}
		}
		
		//Function declaration to turn review option on or off - Enhanced with visual toggle switch
		function getReviewFlagging(testId, theReviewOption)
		{
			var isEnabled = (theReviewOption == "Yes");
			var newReviewOption = isEnabled ? "No" : "Yes";
			var toggleText = isEnabled ? "ON" : "OFF";
			var toggleColor = isEnabled ? "#28a745" : "#dc3545";
			var sliderBg = isEnabled ? "#28a745" : "#ccc";
			var sliderPos = isEnabled ? "26px" : "3px";
			
			return "<div style='display: inline-flex; align-items: center; gap: 8px;'>" +
				"<span style='font-weight: 600; color: " + toggleColor + "; font-size: 12px; min-width: 30px;'>" + toggleText + "</span>" +
				"<label class='review-toggle-switch'>" +
				"<input type='checkbox' " + (isEnabled ? "checked" : "") + " onchange='flagReviewOption(\""+testId+"\",\""+newReviewOption+"\")'>" +
				"<span class='review-slider'></span>" +
				"</label>" +
				"</div>";
		}
		
		//Function declaration to turn on or turn off the review option
		function flagReviewOption(testId, newReviewOption)
		{	
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message5').html('');
			$('.message6').html('');
			$('.message7').html('');
			$('.message8').html('');
			$('.message9').html('');
			$('.message10').html('');
			$('.message11').html('');
			$('.message12').html('');
			
			var displayText = "";
			if(newReviewOption == "Yes")
			{
				displayText = "on";
			}
			else if(newReviewOption == "No")
			{
				displayText = "off";
			}
			var confirmIt = confirm("Are you sure you wish to turn "+ displayText + " student review for this test?");
			if(confirmIt == true)
			{
				$.ajax({
					url: "flagReviewOption.php",
					type: "POST",        
					data: {testId : testId, newReviewOption : newReviewOption},
					success: function (html) {             
						if (html==0)	//If session is expired.
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1)	
						{                              
							 $('.message5').html("");
							 $('.message4').html('<i class="fa fa-check"></i> Review option of selected test successfully turned ' + displayText).fadeIn('slow');
							 $('#example').DataTable().clear().destroy();
							 callTable();
							 updateBulkReviewButtons();
						}
						else if (html==2)	//If deletion is unsuccessful	
						{                              
							 $('.message5').html("");
							 $('.message4').html('<i class="fa fa-times"></i> Could turn ' + newReviewOption + ' review option. Please try again.').fadeIn('slow');
						}
					}				
				});
			}
		}
		
		//Bulk toggle review for multiple selected tests
		function bulkToggleReview(newReviewOption)
		{
			var selectedTests = [];
			var checkboxes = document.querySelectorAll("input[name='testSelectCheckBox[]']:checked");
			
			if(checkboxes.length == 0)
			{
				$('.message4').html('<i class="fa fa-info-circle"></i> Please select at least one test to toggle review.').fadeIn('slow');
				return;
			}
			
			for(var i = 0; i < checkboxes.length; i++)
			{
				selectedTests.push(checkboxes[i].value);
			}
			
			var displayText = (newReviewOption == "Yes") ? "enable" : "disable";
			var confirmIt = confirm("Are you sure you want to " + displayText + " review for " + selectedTests.length + " selected test(s)?");
			
			if(confirmIt == true)
			{
				$('.message4').html('<i class="fa fa-spinner fa-spin"></i> Updating review settings...').fadeIn('slow');
				
				$.ajax({
					url: "bulkToggleReview.php",
					type: "POST",
					data: {testIds: selectedTests.join(','), newReviewOption: newReviewOption},
					success: function(response) {
						if(response == 0)
						{
							window.location.replace("logout.php");
						}
						else if(response == 1)
						{
							$('.message4').html('<i class="fa fa-check"></i> Successfully updated review settings for ' + selectedTests.length + ' test(s).').fadeIn('slow');
							$('#example').DataTable().clear().destroy();
							callTable();
							updateBulkReviewButtons();
							// Uncheck all checkboxes
							$("input[name='testSelectCheckBox[]']").prop('checked', false);
							$('#checkUncheckAllTests').prop('checked', false);
						}
						else
						{
							$('.message4').html('<i class="fa fa-times"></i> Failed to update review settings. Please try again.').fadeIn('slow');
						}
					},
					error: function() {
						$('.message4').html('<i class="fa fa-times"></i> Error occurred. Please try again.').fadeIn('slow');
					}
				});
			}
		}
		
		//Filter tests by review status
		function filterByReview()
		{
			var filterValue = document.getElementById('reviewFilter').value;
			var table = $('#example').DataTable();
			
			if(filterValue == 'all')
			{
				table.column(8).search('').draw();
			}
			else
			{
				// Filter by review status - search for "ON" or "OFF" in the review column
				var searchValue = (filterValue == "Yes") ? "ON" : "OFF";
				table.column(8).search(searchValue).draw();
			}
			updateBulkReviewButtons();
		}
		
		//Update bulk review button counts
		function updateBulkReviewButtons()
		{
			var selectedTests = document.querySelectorAll("input[name='testSelectCheckBox[]']:checked").length;
			$('#selectedForEnable').text(selectedTests);
			$('#selectedForDisable').text(selectedTests);
			
			if(selectedTests > 0)
			{
				$('#bulkEnableReviewBtn').prop('disabled', false);
				$('#bulkDisableReviewBtn').prop('disabled', false);
			}
			else
			{
				$('#bulkEnableReviewBtn').prop('disabled', true);
				$('#bulkDisableReviewBtn').prop('disabled', true);
			}
		}
		
		function callTable()	//Declaration of the data table function
		{
			$.ajax({
					url: 'getAllTests.php',
					type: 'get',
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var testId = response[i].testId;
							var testName = response[i].testName;
							var testDate = response[i].testDate;
							var duration = response[i].duration;						
							var startHour = response[i].startHour;						
							var startMinute = response[i].startMinute;						
							var isAmOrPm = response[i].isAmOrPm;						
							var subjectId = response[i].subjectId;						
							var subjectName = response[i].subjectName;						
							var yearGroupId = response[i].yearGroupId;						
							var yearGroupName = response[i].yearGroupName;						
							var testYear = response[i].testYear;
							var theReviewOption = response[i].reviewOption;
							var teacherName = response[i].teacherName;
							var essayOption = response[i].essayOption;
							var essayTime = response[i].essayTime;
							var status = response[i].status;
							
							var startMinuteToDisplay = "";
							if(startMinute.length == 1)
							{
								startMinuteToDisplay = "0"+startMinute;
							}
							else
							{
								startMinuteToDisplay = startMinute;
							}
							var startTime = startHour+':'+startMinuteToDisplay+' '+isAmOrPm;
							
							var reviewOptionTODisplay = ""
							if(theReviewOption == "Yes")
							{
								reviewOptionTODisplay = "ON";
							}
							else if(theReviewOption == "No")
							{
								reviewOptionTODisplay = "OFF";
							}
							
							var tr_str = "<tr>" +
								"<td width='1'><center><input type='checkbox' name='testSelectCheckBox[]' id='testSelectCheckBox' value='"+testId+"' /></center></td>" +
								"<td width='1'><center>" + (i+1) + "</center></td>" +
								"<td width='20'><center>" + testName + " (" + subjectName + ")</center></td>" +
								"<td width='13'><center>" + testDate + "</center></td>" +
								"<td width='6'><center>" + startTime + "</center></td>" +
								"<td width='6'><center>" + duration + " minutes</center></td>" +
								"<td width='6'><center>" + yearGroupName + "</center></td>" +
								"<td width='4'><center>" + testYear + "</center></td>" +
								"<td width='10' style='font-size:13px;'><center>" + reviewOptionTODisplay + getReviewFlagging(testId, theReviewOption) + "</center></td>" +
								"<td width='7'><center>" + teacherName + "</center></td>" +
								"<td width='10'><center>" + getStatus(testId, testName, status) + "</center></td>" +
								"<td align='center' width='7'><a onClick='openEditModal(\""+testId+"\",\""+testName+"\",\""+testDate+"\",\""+duration+"\",\""+startHour+"\",\""+startMinute+"\",\""+isAmOrPm+"\",\""+subjectId+"\",\""+yearGroupId+"\",\""+theReviewOption+"\",\""+essayOption+"\",\""+essayTime+"\")' title='Edit this test' style='cursor:pointer'><i class='fa fa-pencil-square-o'></i></a> &nbsp; &nbsp;<a onClick='openEditTimeModal(\""+testId+"\",\""+testName+"\",\""+testDate+"\",\""+duration+"\",\""+startHour+"\",\""+startMinute+"\",\""+isAmOrPm+"\")' title='Edit test time before it starts' style='cursor:pointer'><i class='fa fa-clock-o'></i></a> &nbsp; &nbsp;<a onClick='deleteTest(\""+testId+"\",\""+testName+"\")' title='Delete this test' style='cursor:pointer'><i class='fa fa-trash' style='color:red;'></i></a></td>" +
								
								"</tr>";

							$("#example tbody").append(tr_str);
						}
						dataTable = $('#example').DataTable( {
						"paging":   true,
						"ordering": true,
						"info":     true,
						"responsive": true,
						dom: 'lBfrtip',
						buttons: [
							'copy', 'csv', 'excel', 'pdf', 'print'
						],
							"responsive": true,
							"initComplete": function() {
								// Add event listeners to checkboxes after table initialization
								addTestCheckboxListeners();
								// Reset the bulk delete button state
								updateBulkDeleteButton();
								// Reset the bulk review button state
								updateBulkReviewButtons();
							}
						});
					}
			});
		}
		//calling the data table function
		callTable();
		
		//function call to open modal for editing a category
		function openEditModal(testId, testName, testDate, duration, startHour, startMinute, isAmOrPm, subjectId, yearGroupId, theReviewOption, essayOption, essayTime)
        {
            var modal1 = document.getElementById("myModal1");
			document.getElementById("testId").value = testId;  
			document.getElementById("testName1").value = testName;  
			document.getElementById("testDate1").value = testDate;  
			document.getElementById("testDuration1").value = duration;  
			$("#startHour1 option[value="+startHour+"]").attr('selected', 'selected');
			startMinute1="";
			if(startMinute==0)
			{
				startMinute1=60;
			}
			else
			{
				startMinute1=startMinute;
			}
			
			$("#startMinute1 option[value="+startMinute1+"]").attr('selected', 'selected');
			$("#amOrPm1 option[value="+isAmOrPm+"]").attr('selected', 'selected');
			$("#subjectId1 option[value="+subjectId+"]").attr('selected', 'selected');
			$("#yearGroup1 option[value="+yearGroupId+"]").attr('selected', 'selected');
			$("#reviewOption1 option[value="+theReviewOption+"]").attr('selected', 'selected');
			$("#essayOption1 option[value="+essayOption+"]").attr('selected', 'selected');
			if(essayOption == "Yes")
			{
				$("#addEssayTime1").html('<label><span style="color:#000000;">Essay time in minutes:</span> <span class="required" style="color:red;">*</span></label><br><input type="text" class="form-control" name="essayTime1" id="essayTime1" onkeypress="javascript:return isNumber(event)" placeholder="Enter the essay duration in minutes">');
				document.getElementById("essayTime1").value = essayTime;
			}
			else
			{
				$("#addEssayTime1").html('');
			}
                
            modal1.style.display = "block";
        }
		var span1 = document.getElementsByClassName("close1")[0];
            
        // When the user clicks on <span> (x), close the modal1
        span1.onclick = function() {
			modal1.style.display = "none";
        }
            
        // When the user clicks anywhere outside of the modal, close it
        var modal1 = document.getElementById("myModal1");
        window.onclick = function(event) {
            if (event.target == modal1) {
                modal1.style.display = "none";
            }
        }
		
		//function call to open modal for editing test time of all or selected students
		function openEditTimeModal(testId, testName, testDate, duration, startHour, startMinute, isAmOrPm)
        {
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message5').html('');
			$('.message6').html('');
			$('.message7').html('');
			$('.message8').html('');
			$('.message9').html('');
			$('.message10').html('');
			$('.message11').html('');
			$('.message12').html('');
			$("#editTestTimeForm").trigger("reset");
            var modal2 = document.getElementById("myModal2");
            var form_data = 'testId='+testId;
			
			$.ajax({
				url: "modifyTimeBeforeTestStarts.php",
				type: "POST",        
				data: form_data,
				success: function (html) {             
					if (html==0)	//If session is expired.
					{                              
						window.location.replace("logout.php");
					}
					else if (html==1)	
					{       
						var startMinuteToDisplay = "";
						if(startMinute.length == 1)
						{
							startMinuteToDisplay = "0"+startMinute;
						}
						else
						{
							startMinuteToDisplay = startMinute;
						}
						var startTime = startHour+':'+startMinuteToDisplay+' '+isAmOrPm;
						
						$('#testDetails').html("<br>Test name: "+testName+"<br> "+"Test date: " +testDate+"<br> "+"Test duration: " +duration+" minutes <br>"+"start time: " + startTime);
						document.getElementById("testId2").value = testId;
						modal2.style.display = "block";
				
					}
					else if (html==2)	//If test had already started	
					{                              
						$('.message5').html('<i class="fa fa-times"></i> You cannot add/subtract general time to/from this test as it has already started. Please use the "Students and test" menu item').fadeIn('slow');
					}
					else if (html==3)	//test has been completed
					{                              
						$('.message5').html('<i class="fa fa-times"></i> You cannot add/subtract general time to/from this test as it has already end. You can reschedule test before modifying time.').fadeIn('slow');
					}
				}				
			});
            
        }
		var span2 = document.getElementsByClassName("close1")[1];
            
        // When the user clicks on <span> (x), close the modal1
        span2.onclick = function() {
			modal2.style.display = "none";
        }
            
        // When the user clicks anywhere outside of the modal, close it
        var modal2 = document.getElementById("myModal2");
        var modal3 = document.getElementById("myModal3");
        window.onclick = function(event) {
            if (event.target == modal2) {
                modal2.style.display = "none";
            }
            if (event.target == modal3) {
                modal3.style.display = "none";
            }
        }
        
        // Close modal3 (delete modal) when clicking X
        var span3 = document.getElementsByClassName("close1")[2];
        if (span3) {
            span3.onclick = function() {
                modal3.style.display = "none";
            }
        }
		
		//Delete test - MOVED TO modal_delete_functions.js
		/*function deleteTest(testId, testName)
		{
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message5').html('');
			$('.message6').html('');
			$('.message7').html('');
			$('.message8').html('');
			$('.message9').html('');
			$('.message10').html('');
			$('.message11').html('');
			$('.message12').html('');
			
			// Create a more detailed confirmation dialog
			var confirmationMessage = "⚠️ WARNING: You are about to delete the test '" + testName + "'\n\n";
			confirmationMessage += "This action will PERMANENTLY DELETE:\n";
			confirmationMessage += "• The test record from the database\n";
			confirmationMessage += "• All test questions and options\n";
			confirmationMessage += "• All student answers and submissions\n";
			confirmationMessage += "• All examinee records and progress\n";
			confirmationMessage += "• All test-related data tables\n\n";
			confirmationMessage += "⚠️ This action cannot be undone!\n\n";
			confirmationMessage += "Type 'DELETE TEST' to confirm deletion:";
			
			// Use a more secure confirmation method
			var userInput = prompt(confirmationMessage);
			
			if (userInput === 'DELETE TEST')
			{
				// Show loading message
				$('.message4').html('<i class="fa fa-spinner fa-spin"></i> Deleting test, please wait...');
				
				var form_data = 'testId=' + encodeURIComponent(testId);
					  
				$.ajax({
					url: "deleteTest.php",
					type: "POST",
					data: form_data,
					timeout: 30000, // 30 second timeout
					success: function (html) {             
						if (html == 0)	//If session is expired.
						{                              
							window.location.replace("logout.php");
						}
						else if (html == 1)	
						{                              
							$('.message5').html("");
							$('.message4').html('<i class="fa fa-check-circle"></i> Test "' + testName + '" has been successfully deleted along with all related data.').fadeIn('slow');
							$('#example').DataTable().clear().destroy();
							callTable();
						}
						else if (html == 2)	//If deletion is unsuccessful	
						{                              
							$('.message4').html("");
							$('.message5').html('<i class="fa fa-exclamation-triangle"></i> Could not delete test. Please try again.').fadeIn('slow');
						}
						else if (html == 3)	//Invalid test ID
						{                              
							$('.message4').html("");
							$('.message5').html('<i class="fa fa-exclamation-triangle"></i> Invalid test ID provided.').fadeIn('slow');
						}
						else if (html == 4)	//Test not found
						{                              
							$('.message4').html("");
							$('.message5').html('<i class="fa fa-exclamation-triangle"></i> Test not found in the database.').fadeIn('slow');
						}
					},
					error: function(xhr, status, error) {
						$('.message4').html('');
						if (status === 'timeout') {
							$('.message5').html('<i class="fa fa-exclamation-triangle"></i> The deletion request timed out. Please try again.');
						} else {
							$('.message5').html('<i class="fa fa-exclamation-triangle"></i> An error occurred while deleting the test. Please try again.');
						}
					}
				});
			}
			else if (userInput !== null) // User clicked OK but didn't type the correct phrase
			{
				$('.message5').html('<i class="fa fa-exclamation-triangle"></i> Deletion cancelled. You must type "DELETE TEST" to confirm.');
			}
			else // User clicked Cancel
			{
				$('.message5').html('<i class="fa fa-info-circle"></i> Test deletion cancelled by user.');
			}
		}*/
		
		//Function to select and unselect all tests for bulk deletion
		function CheckUncheckAllTests()
		{
		   var  selectAllCheckbox=document.getElementById("checkUncheckAllTests");
			if(selectAllCheckbox.checked==true)
			{
				var checkboxes =  document.getElementsByName("testSelectCheckBox[]");
				for(var i=0, n=checkboxes.length;i<n;i++) 
				{
					checkboxes[i].checked = true;
				}
			}
			else
			{
				var checkboxes =  document.getElementsByName("testSelectCheckBox[]");
				for(var i=0, n=checkboxes.length;i<n;i++) 
				{
					checkboxes[i].checked = false;
				}
			}
			updateBulkDeleteButton();
			updateBulkReviewButtons();
		}
		
		// Function to update the bulk delete button based on selected tests
		function updateBulkDeleteButton()
		{
			var checkboxes = document.querySelectorAll("input[name='testSelectCheckBox[]']:checked");
			var count = checkboxes.length;
			var deleteBtn = document.getElementById('deleteSelectedTestsBtn');
			var selectedCountSpan = document.getElementById('selectedTestsCount');
			
			if (selectedCountSpan) {
				selectedCountSpan.textContent = count;
			}
			
			if (deleteBtn) {
				if (count > 0) {
					deleteBtn.disabled = false;
					deleteBtn.classList.remove('btn-secondary');
					deleteBtn.classList.add('btn-danger');
				} else {
					deleteBtn.disabled = true;
					deleteBtn.classList.remove('btn-danger');
					deleteBtn.classList.add('btn-secondary');
				}
			}
		}
		
		// Add event listeners to individual checkboxes when the table is populated
		function addTestCheckboxListeners()
		{
			var checkboxes = document.getElementsByName("testSelectCheckBox[]");
			for(var i=0, n=checkboxes.length;i<n;i++) 
			{
				checkboxes[i].addEventListener('change', function() {
					updateBulkDeleteButton();
					updateBulkReviewButtons();
				});
			}
		}
		
		//Delete selected tests (bulk delete) - MOVED TO modal_delete_functions.js
		/*function deleteSelectedTests()
		{
			$('.message4').html('');
			$('.message5').html('');
			
			var selectedTests = [];
			var selectedTestNames = [];
			var checkboxes = document.querySelectorAll("input[name='testSelectCheckBox[]']:checked");

			for (var i = 0; i < checkboxes.length; i++) {
				selectedTests.push(checkboxes[i].value);
				// Get test name from the table row
				var row = checkboxes[i].closest('tr');
				var testName = row.cells[2].textContent.trim();
				selectedTestNames.push(testName);
			}
			
			if (selectedTests.length == 0)
			{
				$('.message5').html('<i class="fa fa-info-circle"></i> Please select at least one test before deleting');
				return;
			}
			
			// Create a more detailed confirmation dialog
			var testCount = selectedTests.length;
			var confirmationMessage = "⚠️ WARNING: You are about to delete " + testCount + " test(s)\n\n";
			confirmationMessage += "Selected tests:\n";
			selectedTestNames.forEach(function(name, index) {
				confirmationMessage += (index + 1) + ". " + name + "\n";
			});
			confirmationMessage += "\n⚠️ This action will PERMANENTLY DELETE:\n";
			confirmationMessage += "• All test records from the database\n";
			confirmationMessage += "• All test questions and options\n";
			confirmationMessage += "• All student answers and submissions\n";
			confirmationMessage += "• All examinee records and progress\n";
			confirmationMessage += "• All test-related data tables\n\n";
			confirmationMessage += "⚠️ This action cannot be undone!\n\n";
			confirmationMessage += "Type 'DELETE TESTS' to confirm deletion:";
			
			// Use a more secure confirmation method
			var userInput = prompt(confirmationMessage);
			
			if (userInput === 'DELETE TESTS')
			{
				// Show loading message
				$('.message4').html('<i class="fa fa-spinner fa-spin"></i> Deleting tests, please wait...');
				
				//organize the data properly
				var form_data = 'selectedTests=' + encodeURIComponent(selectedTests.join(','));

				$.ajax({
					url: "deleteSelectedTests.php",
					type: "POST",     
					data: form_data,
					timeout: 60000, // 60 second timeout for bulk operations
					success: function (html) {             
						if (html == 0)	//If session is expired.
						{                              
							window.location.replace("logout.php");
						}
						else if (html == 1)	//If tests successfully deleted
						{                              
							$('.message5').html("");
							$('.message4').html('<i class="fa fa-check-circle"></i> ' + testCount + ' test(s) have been successfully deleted from the system.').fadeIn('slow');
							$('#example').DataTable().clear().destroy();
							callTable();
						}
						else if (html == 2)	//If some tests deleted, some failed
						{                              
							$('.message5').html("");
							$('.message4').html('<i class="fa fa-exclamation-triangle"></i> Some tests were deleted, but some failed. Please check the logs and try again.').fadeIn('slow');
							$('#example').DataTable().clear().destroy();
							callTable();
						}
						else if (html == 3)	//If no tests were deleted
						{                              
							$('.message4').html("");
							$('.message5').html('<i class="fa fa-exclamation-triangle"></i> No tests were deleted. Please verify the test IDs and try again.').fadeIn('slow');
						}
						else 	//If deletion is unsuccessful	
						{                              
							$('.message4').html("");
							$('.message5').html('<i class="fa fa-exclamation-triangle"></i> Could not delete selected tests. Please try again.').fadeIn('slow');
						}
					},
					error: function(xhr, status, error) {
						$('.message4').html('');
						if (status === 'timeout') {
							$('.message5').html('<i class="fa fa-exclamation-triangle"></i> The deletion request timed out. Please try again with fewer tests.');
						} else {
							$('.message5').html('<i class="fa fa-exclamation-triangle"></i> An error occurred while deleting tests. Please check your connection and try again.');
						}
					}
				});
			}
			else if (userInput !== null) // User clicked OK but didn't type DELETE TESTS
			{
				$('.message5').html('<i class="fa fa-exclamation-triangle"></i> Deletion cancelled. You must type "DELETE TESTS" to confirm.');
			}
			else // User clicked Cancel
			{
				$('.message5').html('<i class="fa fa-info-circle"></i> Test deletion cancelled by user.');
			}
		}*/
		
		//Start of edit test
		function editTestDetails()
		{
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message5').html('');
			$('.message6').html('');
			$('.message7').html('');
			$('.message8').html('');
			$('.message9').html('');
			$('.message10').html('');
			$('.message11').html('');
			$('.message12').html('');
			
			var confirmIt=confirm("Are you sure you wish to update this test?");
			if(confirmIt == true)
			{
				$('.message3').html("");
				$('.message4').html("");
				
				var testId = $('input[name=testId]').val();
				var testName = $('input[name=testName1]').val();
				var testDate = $('input[name=testDate1]').val();
				var testDuration = $('input[name=testDuration1]').val();
				var startHour = document.getElementById('startHour1').value;
				var startMinute = document.getElementById('startMinute1').value;
				var amOrPm = document.getElementById('amOrPm1').value;
				var subjectId = document.getElementById('subjectId1').value;
				var yearGroupId = document.getElementById('yearGroup1').value;
				var reviewOption = document.getElementById('reviewOption1').value;
				var essayOption = document.getElementById('essayOption1').value;
				
				var theEssayTime = "";
				if(essayOption == "Yes")
				{
					var theEssayTime = document.getElementById('essayTime1').value;					 
				}
																						 
				if (testName=="")
				{
					$('.message3').html('<i class="fa fa-info-circle"></i> ' + ' Please enter test name')
				}
				else if (testDate=="")
				{
					$('.message3').html('<i class="fa fa-info-circle"></i> ' + ' Please select test date')
				}
				else if (testDuration=="")
				{
					$('.message3').html('<i class="fa fa-info-circle"></i> ' + ' Please set test duration in minutes')
				}
				else if (startHour=="")
				{
					$('.message3').html('<i class="fa fa-info-circle"></i> ' + ' Please select test start hour')
				}
				else if (startMinute=="")
				{
					$('.message3').html('<i class="fa fa-info-circle"></i> ' + ' Please select test start minute')
				}
				else if(amOrPm=="")
				{
					$('.message3').html('<i class="fa fa-info-circle"></i> ' + " Please set time as AM or PM")
				}
				else if (subjectId=="")
				{
					$('.message3').html('<i class="fa fa-info-circle"></i> ' + ' Please select test subject')
				}
				else if (yearGroupId=="")
				{
					$('.message3').html('<i class="fa fa-info-circle"></i> ' + ' Please select test year group')
				}
				else if (reviewOption=="")
				{
					$('.message3').html('<i class="fa fa-info-circle"></i> ' + ' Please select test review option')
				}
				else if (essayOption=="")
				{
					$('.message3').html('<i class="fa fa-info-circle"></i> Please select essay option')
				}
				else if (essayOption =="Yes" && theEssayTime == "")
				{
					$('.message3').html('<i class="fa fa-info-circle"></i> Please enter essay time in minutes')
				}
				else
				{						
					var form_data = 
					  'testId='+testId+
					  '&testName='+testName+
					  '&testDate='+testDate+
					  '&testDuration='+testDuration+
					  '&startHour='+startHour+
					  '&startMinute='+startMinute+
					  '&amOrPm='+amOrPm+
					  '&subjectId='+subjectId+
					  '&yearGroupId='+yearGroupId+
					  '&reviewOption='+reviewOption+
					  '&essayOption='+essayOption+
					  '&theEssayTime='+theEssayTime;
							  
					$.ajax({
						url: "editTest.php",
						type: "POST",        
						data: form_data,
						success: function (html) {
							if (html==0)	//If session is expired.
							{                              
								 window.location.replace("logout.php");
							}
							else if (html==1)	
							{                              
								 $('.message3').html("");
								 $('.message4').html('<i class="fa fa-check"></i> ' + testName + ' successfully updated.').fadeIn('slow');
								 var modal1 = document.getElementById("myModal1");
								 modal1.style.display = "none";
								 $('#example').DataTable().clear().destroy();
								 callTable();
							}
							else if (html==2)	//If deletion is unsuccessful	
							{                              
								 $('.message4').html("");
								 $('.message3').html('<i class="fa fa-times"></i> Could not update test. Please try again.').fadeIn('slow');
							}
						}				
					});
				}
			}
		}	
		//End of edit test
		
		//Start of add/subtract test time
		function addOrSubtractTestTime()
		{
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message5').html('');
			$('.message6').html('');
			$('.message7').html('');
			$('.message8').html('');
			$('.message9').html('');
			$('.message10').html('');
			$('.message11').html('');
			$('.message12').html('');
			var confirmIt=confirm("Are you sure you wish to update this test's time?");
			if(confirmIt == true)
			{				
				var testId = $('input[name=testId2]').val();
				var timeValue = $('input[name=testDuration2]').val();
				var timeAction = $('input[name="addOrSubtract"]:checked').val();
																						 
				if (timeValue=="")
				{
					$('.message6').html('<i class="fa fa-info-circle"></i> ' + ' Please enter time to be added or subtracted')
				}
				else if ($('input[name="addOrSubtract"]:checked').length < 1)
				{
					$('.message6').html('<i class="fa fa-info-circle"></i> ' + ' Please select action to be carried out')
				}
				else
				{													  
					$.ajax({
						url: "editOverAllTestTime.php",
						type: "POST",        
						data: {testId:testId, timeValue:timeValue, timeAction:timeAction},
						success: function (html) { 
							if (html==0)	//If session is expired.
							{                              
								 window.location.replace("logout.php");
							}
							else if (html==1)	
							{                              
								 $('.message6').html("");
								 $('.message4').html('<i class="fa fa-check"></i> Test time successfully updated.').fadeIn('slow');
								 var modal2 = document.getElementById("myModal2");
								 modal2.style.display = "none";
								 $('#example').DataTable().clear().destroy();
								 callTable();
							}
							else if (html==2)	//If deletion is unsuccessful	
							{                              
								 $('.message4').html("");
								 $('.message6').html('<i class="fa fa-times"></i> Could not update test time. Please try again.').fadeIn('slow');
							}
							else if (html==3)	//If deletion is unsuccessful	
							{                              
								 $('.message4').html("");
								 $('.message6').html('<i class="fa fa-times"></i> Time to be subtracted is less than current test duration.').fadeIn('slow');
							}
						}				
					});
				}
			}
		}	
		//End of add/subtract test time
		
		//function to start a test
		function clickToStart(testId, testName)
		{
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message5').html('');
			$('.message6').html('');
			$('.message7').html('');
			$('.message8').html('');
			$('.message9').html('');
			$('.message10').html('');
			$('.message11').html('');
			$('.message12').html('');
			
			var confirmIt = confirm("Are you sure you wish to start this test?");
			if(confirmIt == true)
			{
				var form_data = 
					  'testId='+testId;
					  
				$.ajax({
					url: "startTestInAllTests.php",
					type: "POST",        
					data: form_data,
					success: function (html) {             
						if (html==0)	//If session is expired.
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1)	
						{                              
							 $('.message5').html("");
							 $('.message4').html('<i class="fa fa-check"></i> '+testName+' successfully started.').fadeIn('slow');
							 $('#example').DataTable().clear().destroy();
							 callTable();
						}
						else if (html==2)	//If update is unsuccessful	
						{                              
							$('.message4').html("");
							$('.message5').html('<i class="fa fa-times"></i> Could not start test. Please try again.').fadeIn('slow');
						}
						else if (html==3)	//If update is unsuccessful	
						{                              
							$('.message4').html("");
							$('.message5').html('<i class="fa fa-times"></i> It is not yet test date. You can edit test date to start earlier.').fadeIn('slow');
						}
						else if (html==4)	//If update is unsuccessful	
						{                              
							$('.message4').html("");
							$('.message5').html('<i class="fa fa-times"></i> Test date has passed. You can edit test date to reschedule this test.').fadeIn('slow');
						}
						else if (html==5)	//If update is unsuccessful	
						{                              
							$('.message4').html("");
							$('.message5').html('<i class="fa fa-times"></i> It is not yet test time.').fadeIn('slow');
						}
					}
									
				});
			}
		}	//End of function to start a test
		
		//function to end a test
		function clickToEnd(testId, testName)
		{
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message5').html('');
			$('.message6').html('');
			$('.message7').html('');
			$('.message8').html('');
			$('.message9').html('');
			$('.message10').html('');
			$('.message11').html('');
			$('.message12').html('');
			
			var confirmIt = confirm("Are you sure you wish to end this test?");
			if(confirmIt == true)
			{
				var form_data = 
					  'testId='+testId;
					  
				$.ajax({
					url: "endTestInAllTests.php",
					type: "POST",        
					data: form_data,
					success: function (html) {             
						if (html==0)	//If session is expired.
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1)	
						{                              
							 $('.message5').html("");
							 $('.message4').html('<i class="fa fa-check"></i> '+testName+' successfully ended.').fadeIn('slow');
							 $('#example').DataTable().clear().destroy();
							 callTable();
						}
						else if (html==2)	//If update is unsuccessful	
						{                              
							$('.message5').html("");
							$('.message4').html('<i class="fa fa-times"></i> Could not end test. Please try again.').fadeIn('slow');
						}
					}
									
				});
			}
		}	//End of function to end a test
		
		//function to reschedule a test
		function rescheduleTest(testId, testName)
		{
			var confirmIt = confirm("Are you sure you wish to reschedule this test?");
			if(confirmIt == true)
			{
				var form_data = 
					  'testId='+testId;
					  
				$.ajax({
					url: "rescheduleTestInAllTests.php",
					type: "POST",        
					data: form_data,
					success: function (html) {             
						if (html==0)	//If session is expired.
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1)	
						{                              
							 $('.message5').html("");
							 $('.message4').html('<i class="fa fa-check"></i> '+testName+' successfully rescheduled.').fadeIn('slow');
							 $('#example').DataTable().clear().destroy();
							 callTable();
						}
						else if (html==2)	//If update is unsuccessful	
						{                              
							$('.message5').html("");
							$('.message4').html('<i class="fa fa-times"></i> Could not reschedule test. Please try again.').fadeIn('slow');
						}
					}
									
				});
			}
		}	//End of function to reschedule a test
		
		//If the add essay option is changed
		$('#essayOption').on('change', function(){
			var essayOption = document.getElementById('essayOption').value;
			if(essayOption == "" || essayOption == "No")
			{
				$("#addEssayTime").html("");
			}
			else
			{
				$("#addEssayTime").html('<label>Essay time in minutes:</label><input type="text" name="essayTime" class="form-control" id="essayTime" onkeypress="javascript:return isNumber(event)" placeholder="Enter duration in minutes" required >');
			}
		});
		
		//If the edit essay option is changed
		$('#essayOption1').on('change', function(){
			var essayOption1 = document.getElementById('essayOption1').value;
			if(essayOption1 == "" || essayOption1 == "No")
			{
				$("#addEssayTime1").html("");
			}
			else
			{
				$("#addEssayTime1").html('<label><span style="color:#000000;">Essay time in minutes:</span> <span class="required" style="color:red;">*</span></label><br><input type="text" class="form-control" name="essayTime1" id="essayTime1" onkeypress="javascript:return isNumber(event)" placeholder="Enter the essay duration in minutes">');
			}
		});
		
		//For getting the classes that belong to the year group of a selected test
		$("#test").on('change', function() {
			//$('#yearGroup').prop('selectedIndex',0);
			$('#studentClass').html('');
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message5').html('');
			$('.message6').html('');
			$('.message7').html('');
			$('.message8').html('');
			$('.message9').html('');
			$('.message10').html('');
			$('.message11').html('');
			$('.message12').html('');
			$('#result').html('');
			var selectedTestId = $("#test option:selected").val();
			if(selectedTestId == "")
			{
				$('#example1').DataTable().clear().destroy();
				$("#theTimeEdit").hide();
			}
			else
			{
				$.ajax({
					url: "yearGroupClass.php",
					type: "POST",
					data: { selectedTestId : selectedTestId},
					dataType : 'JSON',
					success: function(response){
						
						var len = response.length;

						if(len > 0)
						{
							$('#studentClass').html('<option value="">...Select class...</option>');
							for(var i=0; i<len; i++){
								var classId = response[i].classId;
								var className = response[i].className;
								$("#studentClass").append('<option value="'+classId+'">'+className+'</option>');
							}
							// Refresh Select2 to show new options
							if (typeof $.fn.select2 !== 'undefined') {
								$('#studentClass').trigger('change.select2');
							}
						}
						else
						{
							$('#studentClass').html('');
							$('.message10').html('<i class="fa fa-info-circle"></i> No class(es) in selected year group');
							if (typeof $.fn.select2 !== 'undefined') {
								$('#studentClass').trigger('change.select2');
							}
						}
					}
				});
			}
		});
		
		//Fetching students of a class based on class change
		$('#studentClass').on('change', function(){
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message5').html('');
			$('.message6').html('');
			$('.message7').html('');
			$('.message8').html('');
			$('.message9').html('');
			$('.message10').html('');
			$('.message11').html('');
			$('.message12').html('');
			
			var selectedTestId = $("#test option:selected").val();
			var selectedClassId = $("#studentClass option:selected").val();
			var theClassName=$("#studentClass option:selected").text();
			
			if(selectedClassId == "")
			{
				$('#example1').DataTable().clear().destroy();
				$("#theTimeEdit").hide();
			}
			
			$.ajax({
				url: "fetchStudents.php",
				type: "POST",
				data: { selectedTestId : selectedTestId, selectedClassId : selectedClassId},
				dataType : 'json',
				success: function(response){
					
					var len = response.length;
					if(len > 0)
					{
						$('#result').html("<b>Below are all students of "+theClassName+"</b><br>"+"<input type='checkbox' id='checkUncheckAll' onClick='CheckUncheckAll()' /> &nbsp;(Select/Unselect) all to add/remove from test<hr>");
						for(var i=0; i<len; i++){
							var studentId = response[i].studentId;
							var surname = response[i].surname;
							var firstName = response[i].firstName;
							var middleName = response[i].middleName;
							$("#result").append("<input type='checkbox' name='rowSelectCheckBox[]' id='rowSelectCheckBox' value='"+studentId+"' /> &nbsp; "+ (+i+1)+ " &nbsp; &nbsp;"+surname + " &nbsp; " + firstName +" &nbsp; " + middleName + "<br>");
						}
						$("#result").append("<br><button type='button' id='submitSelected' onClick='getChecked()' class='btn btn-primary'><i class='fa fa-sign-in'></i> Add Students to selected test</button>");
						viewAdded();
					}
					else
					{
						$('#result').html('');
						$('.message2').html('<i class="fa fa-info-circle"></i> No students in selected class');
					}
				}
			});
		});
		
		function callTestStatus(testStatus)
		{
			if(testStatus == 1)
			{
				return "enabled";
			}
			else
			{
				return "disabled";
			}
		}
		
		//function to get the text to be displayed to allow a student's test to be flagged for the student to continue the test even after submission
		function getRedoOpText(testId, studentId, selectedClassId, testStatus)
		{
			if(testStatus == 2)
			{
				return "&nbsp; &nbsp; <a onClick='flagStudentTestStatusToContinue(\""+studentId+"\",\""+testId+"\",\""+selectedClassId+"\")' title='Click to reset submission option for this student' style='cursor:pointer;'><i class='fa fa-redo' aria-hidden='true' style='color:blue;'></i></a>";
			}
			else
			{
				return "";
			}
		}
		//View students added to a test
		function viewAdded()
		{
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message5').html('');
			$('.message6').html('');
			$('.message7').html('');
			$('.message8').html('');
			$('.message9').html('');
			$('.message10').html('');
			$('.message11').html('');
			$('.message12').html('');
			$("#theTimeEdit").hide();
			
			$('#example1').DataTable().clear().destroy();
			
			var selectedTestId = $("#test option:selected").val();
			var selectedClassId = $("#studentClass option:selected").val();
			
			if(selectedTestId == "")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select a test first to view added students');
			}
			else if(selectedClassId == "")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select class first to view added students');
			}
			else
			{
				$.ajax({
						url: 'viewStudentsAddedToTest.php',
						type: 'POST',
						data: { selectedTestId : selectedTestId, selectedClassId : selectedClassId},
						dataType: 'JSON',
						success: function(response)
						{
							var len = response.length;
		
							for(var i=0; i<len; i++){
								var testId = response[i].testId;
								var studentId = response[i].studentId;
								var surname = response[i].surname;
								var firstName = response[i].firstName;
								var middleName = response[i].middleName;						
								var studentClass = response[i].studentClass;						
								var testDuration = response[i].testDuration;						
								var remainingTime = response[i].remainingTime;						
								var testStatus = response[i].testStatus;										
								var testStatusText = response[i].testStatusText;										
								var testAcademicYear = response[i].testAcademicYear;										
								
								var tr_str = "<tr>" +
									"<td width='3%'><center>" + (i+1) + "</center></td>" +
									"<td width='32%'>"+surname+' '+firstName+' '+middleName+"</td>" +
									"<td width='15%'><center>" + studentClass + " ("+testAcademicYear+")</center></td>" +
									"<td width='10%'><center>" + testDuration + " minutes</center></td>" +
									"<td width='10%'><center>" + remainingTime + " minutes</center></td>" +
									"<td width='10%'><center>" + testStatusText + "</center></td>" +
									"<td width='15%'><center><input type='checkbox' "+callTestStatus(testStatus)+" name='rowSelectCheckBox2[]' id='rowSelectCheckBox2' value='"+studentId+"' /></center></td>" +
									"<td align='center' width='5%'><a onClick='deleteStudentFromTest(\""+testId+"\",\""+studentId+"\",\""+selectedClassId+"\")' title='Delete this student from test' style='cursor:pointer;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a>" + getRedoOpText(testId, studentId, selectedClassId, testStatus) + "</td>" +
									
									"</tr>";

								$("#example1 tbody").append(tr_str);
							}
							if(len > 0)
							{
								$("#theTimeEdit").show();
							}
							else
							{
								$("#theTimeEdit").hide();
							}
							$('#example1').DataTable( {
							"paging":   true,
							"ordering": true,
							"info":     true,
							"responsive": true,
							dom: 'lBfrtip',
							buttons: [
								'copy', 'csv', 'excel', 'pdf', 'print'
							],
								"responsive": true
							});
							
							//Assigning testId to hidden field to be stored to enable time to be added to students of the test when test has started
							document.getElementById("testIdToAddStudentsTime").value = selectedTestId;
							document.getElementById("classIdToModifyTime").value = selectedClassId;
						}
				});
			}
		}
		//End of function declaration to get and view students added to a test
		
		//Function to select and unselect students whose test time would be added or subtracted
		function CheckUncheckAll()
		{
		   var  selectAllCheckbox=document.getElementById("checkUncheckAll");
			if(selectAllCheckbox.checked==true)
			{
				var checkboxes =  document.getElementsByName("rowSelectCheckBox[]");
				for(var i=0, n=checkboxes.length;i<n;i++) 
				{
					checkboxes[i].checked = true;
				}
			}
			else
			{
				var checkboxes =  document.getElementsByName("rowSelectCheckBox[]");
				for(var i=0, n=checkboxes.length;i<n;i++) 
				{
					checkboxes[i].checked = false;
				}
			}
		}
		
		//Function to select and unselect students to be added to a test
		function CheckUncheckAll2()
		{
		   var  selectAllCheckbox=document.getElementById("checkUncheckAll2");
			if(selectAllCheckbox.checked==true)
			{
				var checkboxes =  document.getElementsByName("rowSelectCheckBox2[]");
				for(var i=0, n=checkboxes.length;i<n;i++) 
				{
					if(checkboxes[i].disabled !=true)
					{
						checkboxes[i].checked = true;
					}
				}
			}
			else
			{
				var checkboxes =  document.getElementsByName("rowSelectCheckBox2[]");
				for(var i=0, n=checkboxes.length;i<n;i++) 
				{
					checkboxes[i].checked = false;
				}
			}
		}
		
		//function to delete a student from a test
		function deleteStudentFromTest(testId, studentId, selectedClassId)
		{	
			$('.message1').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message5').html('');
			$('.message6').html('');
			$('.message7').html('');
			$('.message8').html('');
			$('.message9').html('');
			$('.message10').html('');
			$('.message11').html('');
			$('.message12').html('');

			var confirmIt = confirm("Are you sure you wish to delete this student from the selected test? \n Please note that all previous attempts of this test by this students would also be deleted.");
			if(confirmIt == true)
			{	
				$.ajax({
					url: "deleteStudentsAddedToTest.php",
					type: "POST",        
					data: { testId : testId, studentId : studentId},
					success: function (html) {             
						if (html==0)	//If session is expired.
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1)	
						{                              
							 $('.message3').html('<i class="fa fa-check"></i> Student successfully deleted from selected test.').fadeIn('slow');
							 $('#example1').DataTable().clear().destroy();
							 viewAdded1(testId, selectedClassId);
						}
						else if (html==2)	//If deletion is unsuccessful	
						{                              
							$('.message4').html('<i class="fa fa-times"></i> Could not delete student from selected test. Please try again.').fadeIn('slow');
						}
					}
									
				});
			}
		}
		
		
		//View students added to a test called after a delete of a student from a test
		function viewAdded1(selectedTestId, selectedClassId)
		{
			$('#example1').DataTable().clear().destroy();
			$.ajax({
				url: 'viewStudentsAddedToTest.php',
				type: 'POST',
				data: { selectedTestId : selectedTestId, selectedClassId : selectedClassId},
				dataType: 'JSON',
				success: function(response)
				{
					var len = response.length;
					for(var i=0; i<len; i++){
						var testId = response[i].testId;
						var studentId = response[i].studentId;
						var surname = response[i].surname;
						var firstName = response[i].firstName;
						var middleName = response[i].middleName;						
						var studentClass = response[i].studentClass;						
						var testDuration = response[i].testDuration;						
						var remainingTime = response[i].remainingTime;						
						var testStatus = response[i].testStatus;										
						var testStatusText = response[i].testStatusText;										
						var testAcademicYear = response[i].testAcademicYear;									
						
						var tr_str = "<tr>" +
							"<td width='1%'><center>" + (i+1) + "</center></td>" +
							"<td width='32%'>"+surname+' '+firstName+' '+middleName+"</td>" +
							"<td width='15%'><center>" + studentClass + " ("+testAcademicYear+")</center></td>" +
							"<td width='10%'><center>" + testDuration + " minutes</center></td>" +
							"<td width='10%'><center>" + remainingTime + " minutes</center></td>" +
							"<td width='10%'><center>" + testStatusText + "</center></td>" +
							"<td width='15%'><center><input type='checkbox' name='rowSelectCheckBox2[]' "+callTestStatus(testStatus)+" id='rowSelectCheckBox2' value='"+studentId+"' /></center></td>" +
							"<td align='center' width='7%'><a onClick='deleteStudentFromTest(\""+testId+"\",\""+studentId+"\",\""+selectedClassId+"\")' title='Delete this student from test' style='cursor:pointer;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a>" + getRedoOpText(testId, studentId, selectedClassId, testStatus) + "</td>" +
							
							"</tr>";
							$("#example1 tbody").append(tr_str);
					}
					if(len > 0)
					{
						$("#theTimeEdit").show();
					}
					else
					{
						$("#theTimeEdit").hide();
					}
					$('#example1').DataTable( {
						"paging":   true,
						"ordering": true,
						"info":     true,
						"responsive": true,
						dom: 'lBfrtip',
						buttons: [
							'copy', 'csv', 'excel', 'pdf', 'print'
						],
							"responsive": true
					});
					
					//Assigning testId to hidden field to be stored to enable time to be added to students of the test when test has started
					document.getElementById("testIdToAddStudentsTime").value = selectedTestId;
					document.getElementById("classIdToModifyTime").value = selectedClassId;
				}
			});
		}
		//End of function to view added students of a test after a successful delete
		
		function getAddOrSubtractTimeText(timeAction)
		{
			if(timeAction == "add")
			{
				return "added to";
			}
			else if(timeAction == "subtract")
			{
				return "subtracted from";
			}
		}
		
		//Start of add/subtract test time
		function addOrSubtractTestTimeDuringTest()
		{
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message6').html('');
			$('.message7').html('');
			$('.message8').html('');
			$('.message9').html('');
			$('.message10').html('');
			$('.message11').html('');
			$('.message12').html('');
			
			var favorite2 = [];
			var checkboxes = document.querySelectorAll("input[name='rowSelectCheckBox2[]']:checked");

			for (var i = 0; i < checkboxes.length; i++) {
				favorite2.push(checkboxes[i].value)
			}
			
			favorite2 = favorite2.join(",")
			var testId = $('input[name=testIdToAddStudentsTime]').val();
			var timeValue = $('input[name=valueToModify]').val();
			var studentClassId = $('input[name=classIdToModifyTime]').val();
			var timeAction = $('input[name="addOrSubtract1"]:checked').val();
            var getActionText = getAddOrSubtractTimeText(timeAction);
			
			$('.message1').html('')
			
			if (timeValue=="")
			{
				$('.message12').html('<i class="fa fa-info-circle"></i> ' + ' Please enter time to be added or subtracted')
			}
			else if (favorite2=="")
			{
				$('.message12').html('<i class="fa fa-info-circle"></i> Please check at least one student before submitting')
			}
			else if (testId=="")
			{
				$('.message12').html('<i class="fa fa-info-circle"></i> Please select test')
			}
			else if ($('input[name="addOrSubtract1"]:checked').length < 1)
			{
				$('.message12').html('<i class="fa fa-info-circle"></i> Please select action to be carried out')
			}
			else
			{
				var confirmIt = confirm("Are you sure you wish to modify the test time of selected student(s)");
				if(confirmIt == true)
				{
					var form_data = 
						  'testId='+testId+
						  '&timeValue='+timeValue+
						  '&timeAction='+timeAction+
						  '&checkedStudents='+favorite2;

						$.ajax({
							url: "editTestTimeOfSelected.php",
							type: "POST",     
							data: form_data,    
							success: function (html) {             							
								if (html==0)	//If session is expired.
								{                              
									 window.location.replace("logout.php");
								}
								else if (html==1)	//If examinees successfully added to write test
								{                              
									$('.message12').html('');
									$('.message11').html('<i class="fa fa-check-circle"></i> ' + timeValue + ' minute(s) has/have been successfully '+ getActionText +' selected student(s)').fadeIn('slow');
									viewAdded1(testId, studentClassId);
								}
								else if (html==3)	//If examinees successfully added to write test
								{                              
									$('.message12').html('');
									$('.message11').html('<i class="fa fa-check-circle"></i> ' + timeValue + ' minute(s) has/have been successfully '+ getActionText +' some selected students. The action could not be carried out on some students with remaining time less than time to be subtracted.').fadeIn('slow');
									viewAdded1(testId, studentClassId);
								}
								else if(html==2) 	//If insertion is unsuccessful	
								{                              
									 $('.message11').html('');
									 $('.message12').html('<i class="fa fa-check-circle"></i> Cannot add time to selected students when the test has not started.').fadeIn('slow');
								}
							}
										
						});
				}
			}
		}	
		//End of add/subtract test time
		
		//function to reset submission option of a student in order to allow the student to continue answering the questions
		function flagStudentTestStatusToContinue(examineeUserId, testId, selectedClassId)
		{
			var confirmIt = confirm("Are you sure you wish to allow this student to continue answering questions of the test?");
			if(confirmIt == true)
			{				
				$.ajax({
					url: "flagToAllowTestToBeContinued.php",
					type: "POST",     
					data: {examineeUserId:examineeUserId, testId:testId},    
					success: function (html) {             							
						if (html==0)	//If session is expired.
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1)	//If examinees successfully added to write test
						{                              
							$('.message3').html('<i class="fa fa-check-circle"></i> Test status of selected student has been flagged to allow student continue test').fadeIn('slow');
							$('#example1').DataTable().clear().destroy();
							 viewAdded1(testId, selectedClassId);
						}
						else if (html==3)	//If examinees successfully added to write test
						{                              
							$('.message3').html('');
							$('.message12').html('<i class="fa fa-check-circle"></i> Overall Test status not in progress.').fadeIn('slow');
							
						}
						else if(html==2) 	//If insertion is unsuccessful	
						{                              
							 $('.message3').html('');
							 $('.message12').html('<i class="fa fa-check-circle"></i> Flagging test status not successful. Please try again.').fadeIn('slow');
						}
					}
				});
			}
		}
		
		// Custom search functionality
		var dataTable; // Store the DataTable instance globally
		
		function performSearch() {
			var searchTerm = document.getElementById('customSearchInput').value;
			if (dataTable) {
				dataTable.search(searchTerm).draw();
				
				// Visual feedback
				if (searchTerm.trim() !== '') {
					document.getElementById('customSearchInput').style.borderColor = '#0acca2';
					document.getElementById('customSearchInput').style.boxShadow = '0 0 5px rgba(10, 204, 162, 0.5)';
				}
			}
		}
		
		function clearSearch() {
			document.getElementById('customSearchInput').value = '';
			document.getElementById('customSearchInput').style.borderColor = '';
			document.getElementById('customSearchInput').style.boxShadow = '';
			if (dataTable) {
				dataTable.search('').draw();
			}
		}
		
		// Allow Enter key to trigger search
		document.addEventListener('DOMContentLoaded', function() {
			var searchInput = document.getElementById('customSearchInput');
			if (searchInput) {
				searchInput.addEventListener('keypress', function(e) {
					if (e.key === 'Enter') {
						e.preventDefault();
						performSearch();
					}
				});
			}
		});
	</script>
	


  </body>
</html>
