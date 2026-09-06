<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn']))
	{
		header('location:../index.php');
	}
	include "../../db_connection/dlhs_db_connection.php";
	require_once "../../scripts/test_workflow_helper.php";

	$setupTestId = isset($_GET['testId']) ? (int) $_GET['testId'] : 0;
	$setupFlowActive = isset($_GET['setup']) && $_GET['setup'] == '1';
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

    <title>Manage Test Access | DLHS</title>

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
	<!-- Select2 (searchable selects) -->
	<link rel="stylesheet" type="text/css" href="../vendor/select2/select2.min.css">
	 <!-- bootstrap-wysiwyg -->
	<script>
		function getChecked()
		{
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message6').html('');
			
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
				$('.message2').html('<i class="fa fa-info-circle"></i> Please check at least one student before submitting')
			}
			else if (testId=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select test')
			}
			else if (studentClass=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select student\'s class')
			}
			else
			{
				$('.message2').html('');
								
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
									$('.message2').html('');
									var successMessage = '<i class="fa fa-check-circle"></i> The selected Student(s) from '+studentClassName+' has/have successfully been added to write '+testName2+'.';
									if (setupFlowActive) {
										successMessage += ' <a href="addQuestionForm.php?testId='+testId+'" class="btn btn-xs btn-primary" style="margin-left:10px;">Proceed to add questions</a>';
									}
									$('.message1').html(successMessage).fadeIn('slow');
									viewAdded1(testId, studentClass)
								}
								else 	//If insertion is unsuccessful	
								{                              
									 $('.message1').html('');
									 $('.message2').html('Could not add selected Student(s). Please try again.').fadeIn('slow');
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
    <!-- ck editor -->
	<script src="jQuery3.3.1.js"></script>
	<style>
		body{
			overflow-x:hidden;
			overflow-y:auto;
			height:900px;
		}
		/* Standard modal styles (match other pages) */
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
					<h3 class="page-header"><i class="fa fa-users"></i> Manage Test Access</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-users"></i>Manage Test Access</li>
						<a href="#" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-4">
						<section class="panel">
							<header class="panel-heading">
								View/Add Student(s) to Test
							</header>
							<div class="panel-body">
								<form class="form-horizontal" name="myForm">
									<?php if ($setupFlowActive) { ?>
									<div class="alert alert-info">
										<i class="fa fa-info-circle"></i> Step 2 of 3: add students to the new test before questions can be entered.
									</div>
									<?php } ?>
									<div class="form-group">
										<label class="control-label col-sm-12"><strong>View All students added to a test or add new students to a test</strong></label>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-12"><strong>Select Test to Assign Student(s) to:</strong></label>
										<div class="col-sm-12">
											<!-- Search box removed per request -->
											<!-- Test Dropdown -->
											<select class="form-control" name="test" id="test" required style="height: 38px;">
												<option value="">... Select Test ...</option>
												<?php
													$staffId=$_SESSION['staffId'];
													$test="select testId, testName, testType from tests WHERE staffId='$staffId' ORDER BY testName ASC";
													$result = $connection->query($test);
													while($row = $result->fetch_assoc()){
														$normalizedType = dlhsNormalizeTestType(isset($row['testType']) ? $row['testType'] : '');
														$label = $row['testName'];
														if ($normalizedType !== '') {
															$label .= " ({$normalizedType})";
														}
												?>
												<option value="<?php echo (int) $row['testId']; ?>" <?php echo $setupTestId === (int) $row['testId'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-sm-12"><strong>Select Class of Student(s) to add:</strong></label>
										<div class="col-sm-12">
											<select class="form-control" name="studentClass" id="studentClass" required style="color:#000000;" data-placeholder="... Select class ...">
												<option value="">... Select class ...</option>
											</select>
										</div>
										<br>
									</div>
									
									<div id="result"></div><br>
									<div class='message2' id='message2' style='color:red;' align='center'></div><div class='message1' style='color:green; font-size:17px;'></div>
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
								<div class='message3' style='color:green; font-size:17px;' align='center'></div>
								<div class="table-responsive">
								<div style="margin-bottom:10px;">
									<button type="button" class="btn btn-danger" onclick="openDeleteSelectedStudentsModal()"><i class="fa fa-trash"></i> Delete selected students</button>
									<button type="button" class="btn btn-danger" onclick="openRemoveAllStudentsModal()" style="margin-left:8px;"><i class="fa fa-user-times"></i> Remove all students from this test</button>
								</div>
									<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
										<thead>
											<tr>
												<th width="3%"><center>S/NO</center></th>
												<th width="32%"><center>NAME</center></th>
												<th width="15%"><center>CLASS</center></th>
												<th width="10%"><center>DURATION</center></th>
												<th width="10%"><center>REMAINING TIME</center></th>
												<th width="10%"><center>STATUS</center></th>
												<th width="15%"><center><input type='checkbox' id='checkUncheckAll2' onClick='CheckUncheckAll2()' /> SELECT/UNSELECT</center></th>
												<th width="5%"><center>ACTION</center></th>
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
									<input type="radio" name="addOrSubtract" value="add" > <font style="color:#000000;">Add time above to selected test</font> &nbsp; &nbsp; <input type="radio" name="addOrSubtract" value="subtract"> <font style="color:#000000;">Subtract time above from selected test</font>
									<br><br>
									<button type='button' id='viewAlreadyAdded' onClick='addOrSubtractTestTime()' class='btn btn-primary'>Submit to update time</button><br>
									<div class="message6" style="color:red;" align="center"></div><div class="message7" style="color:green; font-size:17px;" align="center"></div>
								</div>
								<br>
                            </div>
						</section>
					</div>
		<!-- Remove all students confirmation modal -->
		<div id="modalRemoveAllStudents" class="modal1">
			<div class="modal-content">
				<span class="close1" onclick="closeRemoveAllStudentsModal()">&times;</span>
				<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#c9302c;"><center style="font-size:20px;">Confirm Remove All Students</center></div>
				<div style="background-color:#FDF2F2; padding:15px; margin:10px; border:1px solid #f5c6cb;">
					<div id="removeAllStudentsBody" style="color:#721c24;"></div>
					<input type="text" id="removeAllStudentsInput" class="form-control" placeholder="Type code to confirm" onkeyup="onRemoveAllStudentsInput()" style="margin:10px 0;" />
					<div id="removeAllStudentsError" style="color:#a94442; font-size:14px; margin-bottom:10px;"></div>
					<div id="removeAllStudentsMsg" style="color:#31708f; font-size:14px; margin-bottom:10px;"></div>
					<button type="button" id="btnConfirmRemoveAllStudents" class="btn btn-danger" onclick="confirmRemoveAllStudents()" disabled>Confirm Remove</button>
					<button type="button" class="btn btn-default" onclick="closeRemoveAllStudentsModal()" style="margin-left:8px;">Cancel</button>
				</div>
			</div>
		</div>
		<!-- Delete selected students confirmation modal -->
		<div id="modalDeleteSelectedStudents" class="modal1">
			<div class="modal-content">
				<span class="close1" onclick="closeDeleteSelectedStudentsModal()">&times;</span>
				<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#c9302c;"><center style="font-size:20px;">Confirm Delete Selected Students</center></div>
				<div style="background-color:#FDF2F2; padding:15px; margin:10px; border:1px solid #f5c6cb;">
					<div id="deleteSelectedStudentsBody" style="color:#721c24;"></div>
					<input type="text" id="deleteSelectedStudentsInput" class="form-control" placeholder="Type code to confirm" onkeyup="onDeleteSelectedStudentsInput()" style="margin:10px 0;" />
					<div id="deleteSelectedStudentsError" style="color:#a94442; font-size:14px; margin-bottom:10px;"></div>
					<div id="deleteSelectedStudentsMsg" style="color:#31708f; font-size:14px; margin-bottom:10px;"></div>
					<button type="button" id="btnConfirmDeleteSelectedStudents" class="btn btn-danger" onclick="confirmDeleteSelectedStudents()" disabled>Confirm Delete</button>
					<button type="button" class="btn btn-default" onclick="closeDeleteSelectedStudentsModal()" style="margin-left:8px;">Cancel</button>
				</div>
			</div>
		</div>
		<!-- Retake test confirmation modal -->
		<div id="modalRetakeTest" class="modal1">
			<div class="modal-content">
				<span class="close1" onclick="closeRetakeModal()">&times;</span>
				<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#28a745;"><center style="font-size:20px;">Confirm Allow Student to Retake Test</center></div>
				<div style="background-color:#F8F9FA; padding:15px; margin:10px; border:1px solid #d4edda;">
					<div id="retakeTestBody" style="color:#155724;"></div>
					<input type="text" id="retakeTestInput" class="form-control" placeholder="Type code to confirm" onkeyup="onRetakeTestInput()" style="margin:10px 0;" />
					<div id="retakeTestError" style="color:#a94442; font-size:14px; margin-bottom:10px;"></div>
					<div id="retakeTestMsg" style="color:#31708f; font-size:14px; margin-bottom:10px;"></div>
					<button type="button" id="btnConfirmRetakeTest" class="btn btn-success" onclick="confirmRetakeTest()" disabled>Confirm Retake</button>
					<button type="button" class="btn btn-default" onclick="closeRetakeModal()" style="margin-left:8px;">Cancel</button>
				</div>
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
	<!-- Select2 JS (searchable selects) -->
	<script src="../vendor/select2/select2.min.js"></script>
	<script>
		var setupFlowActive = <?php echo $setupFlowActive ? 'true' : 'false'; ?>;
		var setupTestId = <?php echo (int) $setupTestId; ?>;

		// Initialize Select2 on the #test select to enable in-dropdown searching
		$(document).ready(function(){
			if (typeof $.fn.select2 !== 'undefined'){
				$('#test').select2({
					placeholder: '... Select Test ...',
					width: '100%'
				});
				// Make studentClass searchable and show placeholder
				$('#studentClass').select2({
					placeholder: '... Select class ...',
					width: '100%'
				});
			}

			if (setupTestId > 0) {
				$('#test').val(String(setupTestId)).trigger('change');
			}
		});
	</script>
	<script>
		$("#test").on('change', function() {
			//$('#yearGroup').prop('selectedIndex',0);
			$('#studentClass').html('');
			$('.message1').html('');
			$('.message2').html('');
			$('#result').html('');
			var selectedTestId = $("#test option:selected").val();
			if(selectedTestId == "")
			{
				$('#example').DataTable().clear().destroy();
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
						}
						else
						{
							$('#studentClass').html('');
							$('.message2').html('<i class="fa fa-info-circle"></i> No class(es) in selected year group');
						}
					}
				});
			}
		});
		
		//Fetching students of a class based on class change
		$('#studentClass').on('change', function(){
			$('.message1').html('');
			$('.message2').html('');
			
			var selectedTestId = $("#test option:selected").val();
			var selectedClassId = $("#studentClass option:selected").val();
			var theClassName=$("#studentClass option:selected").text();
			
			$.ajax({
				url: "fetchStudents.php",
				type: "POST",
				data: { selectedTestId : selectedTestId, selectedClassId : selectedClassId},
				dataType : 'json',
				success: function(response){
					
					var len = response.length;
					if(len > 0)
					{
						var html = '<div class="student-select-card" style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:15px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">';
						html += '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">';
						html += '  <strong style="color:#2c3e50;">Students in ' + theClassName + '</strong>';
						html += '  <span id="selectedCountBadge" class="label label-info" style="font-size:12px;">0 / ' + len + ' selected</span>';
						html += '</div>';
						html += '<div style="font-size:11px; color:#7f8c8d; margin-bottom:10px;">Select all for whole-class exams, or pick specific students for elective subjects.</div>';
						html += '<div style="margin-bottom:10px; display:flex; gap:8px;">';
						html += '  <button type="button" class="btn btn-xs btn-primary" onclick="selectAllStudents(true)"><i class="fa fa-check-square-o"></i> Select All (Whole Class)</button>';
						html += '  <button type="button" class="btn btn-xs btn-default" onclick="selectAllStudents(false)" style="margin-left:6px;"><i class="fa fa-square-o"></i> Clear</button>';
						html += '</div>';
						html += '<div style="max-height:280px; overflow-y:auto; border:1px solid #e8ecf1; border-radius:6px; padding:6px; background:#fafbfc;">';
						for(var i=0; i<len; i++){
							var studentId = response[i].studentId;
							var surname = response[i].surname;
							var firstName = response[i].firstName;
							var middleName = response[i].middleName;
							html += '<label style="display:block; padding:5px 8px; margin-bottom:3px; font-weight:normal; cursor:pointer; border-radius:4px; background:#fff; border:1px solid #f0f2f5;" onmouseover="this.style.background=\'#e8f8f5\'" onmouseout="this.style.background=\'#fff\'">';
							html += '<input type="checkbox" name="rowSelectCheckBox[]" value="'+studentId+'" onchange="updateSelectedCount()" style="margin-right:8px; vertical-align:middle;" /> ';
							html += '<span style="color:#95a5a6; font-size:11px; width:22px; display:inline-block;">'+(i+1)+'.</span> ';
							html += '<strong>'+surname+'</strong> ' + firstName + ' ' + middleName;
							html += '</label>';
						}
						html += '</div>';
						html += '<div style="margin-top:12px;">';
						html += '<button type="button" id="submitSelected" onClick="getChecked()" class="btn btn-success btn-block" style="font-weight:600;"><i class="fa fa-user-plus"></i> Enroll Selected Students (0)</button>';
						html += '</div>';
						html += '</div>';
						$('#result').html(html);
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

		// Remove all students dynamic confirmation
		var RAS_CODE = null;
		function generateCode(){ var c='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', r=''; for(var i=0;i<8;i++){ r+=c.charAt(Math.floor(Math.random()*c.length)); } return r; }
		function openRemoveAllStudentsModal(){
			var testId = $("#test").val();
			if(testId == ""){ alert('Please select a test first'); return; }
			RAS_CODE = generateCode();
			var testName = $('#test option:selected').text();
			var body = '<p>You are about to remove <b>ALL students</b> from the test: <span style="color:#dc3545;">'+testName+'</span>.</p>'+
				'<div style="background-color:#fff3cd; border:1px solid #ffeaa7; border-radius:5px; padding:10px; margin-bottom:10px;">'+
				'<div style="color:#856404; font-weight:bold;">Security confirmation required</div>'+
				'<div>Type this code: <span style="color:#dc3545; font-family:monospace; padding:6px 10px; border:2px solid #dc3545; border-radius:4px; letter-spacing:2px;">'+RAS_CODE+'</span></div>'+
				'</div>'+
				'<p style="color:#721c24; font-weight:bold;">This will also delete any previous attempts/answers for those students in this test. This action cannot be undone.</p>';
			document.getElementById('removeAllStudentsBody').innerHTML = body;
			document.getElementById('removeAllStudentsInput').value='';
			document.getElementById('removeAllStudentsError').innerHTML='';
			document.getElementById('removeAllStudentsMsg').innerHTML='';
			document.getElementById('btnConfirmRemoveAllStudents').disabled = true;
			document.getElementById('modalRemoveAllStudents').style.display = 'block';
		}
		function closeRemoveAllStudentsModal(){ document.getElementById('modalRemoveAllStudents').style.display = 'none'; }
		function onRemoveAllStudentsInput(){ var v=document.getElementById('removeAllStudentsInput').value.trim(); var ok=(RAS_CODE && v===RAS_CODE); document.getElementById('btnConfirmRemoveAllStudents').disabled=!ok; document.getElementById('removeAllStudentsError').innerHTML=''; }
		function confirmRemoveAllStudents(){
			var testId = $("#test").val();
			var v=document.getElementById('removeAllStudentsInput').value.trim();
			if(!(RAS_CODE && v===RAS_CODE)){ document.getElementById('removeAllStudentsError').innerHTML='Please type the confirmation code exactly as shown.'; return; }
			document.getElementById('removeAllStudentsMsg').innerHTML = '<i class="fa fa-spinner fa-spin"></i> Removing, please wait...';
			document.getElementById('btnConfirmRemoveAllStudents').disabled = true;
			$.ajax({
				url:'removeAllStudentsFromTest.php',
				type:'POST',
				data:{ testId: testId },
				success:function(html){
					if(html==0){ window.location.replace('logout.php'); }
					else if(html==1){ document.getElementById('removeAllStudentsMsg').innerHTML = '<i class="fa fa-check"></i> All students removed from this test.'; setTimeout(function(){ closeRemoveAllStudentsModal(); $("#studentClass").trigger('change'); }, 1200); }
					else { document.getElementById('removeAllStudentsMsg').innerHTML = 'Could not remove students. Please try again.'; document.getElementById('btnConfirmRemoveAllStudents').disabled = false; }
				}
			});
		}
		
		//View students added to a test
		function viewAdded()
		{
			$('.message2').html('');
			$('.message6').html('');
			$("#theTimeEdit").hide();
			
			$('#example').DataTable().clear().destroy();
			
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
								var questionsAnswered = response[i].questionsAnswered;
								var totalQuestions = response[i].totalQuestions;										
								
								var tr_str = "<tr>" +
									"<td width='3%'><center>" + (i+1) + "</center></td>" +
									"<td width='32%'>"+surname+' '+firstName+' '+middleName+"</td>" +
									"<td width='15%'><center>" + studentClass + " ("+testAcademicYear+")</center></td>" +
									"<td width='10%'><center>" + testDuration + " minutes</center></td>" +
									"<td width='10%'><center>" + remainingTime + " minutes</center></td>" +
									"<td width='10%'><center>" + testStatusText + "</center></td>" +
									"<td width='15%'><center><input type='checkbox' "+callTestStatus(testStatus)+" name='rowSelectCheckBox2[]' id='rowSelectCheckBox2' value='"+studentId+"' /></center></td>" +
									"<td align='center' width='5%'>" +
										"<a onClick='deleteStudentFromTest(\""+testId+"\",\""+studentId+"\",\""+selectedClassId+"\")' title='Delete this student from test' style='cursor:pointer; margin-right:5px;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a>" +
										(((testStatus == 2 || (testStatus == 1 && remainingTime <= 0)) && questionsAnswered < totalQuestions) ? "<a onClick='openRetakeModal(\""+testId+"\",\""+studentId+"\",\""+selectedClassId+"\",\""+surname+" "+firstName+" "+middleName+"\")' title='Allow student to retake test (did not answer all questions)' style='cursor:pointer;'><i class='fa fa-refresh' aria-hidden='true' style='color:green;'></i></a>" : "") +
									"</td>" +
									
									"</tr>";

								$("#example tbody").append(tr_str);
							}
							if(len > 0)
							{
								$("#theTimeEdit").show();
							}
							else
							{
								$("#theTimeEdit").hide();
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
							
							//Assigning testId to hidden field to be stored to enable time to be added to students of the test when test has started
							document.getElementById("testIdToAddStudentsTime").value = selectedTestId;
							document.getElementById("classIdToModifyTime").value = selectedClassId;
						}
				});
			}
		}
		//calling the data table function
		
		//Function to select and unselect students whose test time would be added or subtracted
		function CheckUncheckAll()
		{
		   var  selectAllCheckbox=document.getElementById("checkUncheckAll");
			if(selectAllCheckbox && selectAllCheckbox.checked==true)
			{
				selectAllStudents(true);
			}
			else
			{
				selectAllStudents(false);
			}
		}

		function updateSelectedCount() {
			var checked = document.querySelectorAll("input[name='rowSelectCheckBox[]']:checked").length;
			var total = document.querySelectorAll("input[name='rowSelectCheckBox[]']").length;
			var badge = document.getElementById('selectedCountBadge');
			if (badge) {
				badge.innerText = checked + ' / ' + total + ' selected';
				if (checked === total && total > 0) {
					badge.className = 'label label-success';
				} else if (checked > 0) {
					badge.className = 'label label-primary';
				} else {
					badge.className = 'label label-info';
				}
			}
			var btn = document.getElementById('submitSelected');
			if (btn) {
				btn.innerHTML = "<i class='fa fa-user-plus'></i> Enroll Selected Students (" + checked + ")";
			}
		}

		function selectAllStudents(checked) {
			var checkboxes = document.getElementsByName("rowSelectCheckBox[]");
			for(var i=0; i<checkboxes.length; i++) {
				checkboxes[i].checked = checked;
			}
			var chkAll = document.getElementById("checkUncheckAll");
			if (chkAll) chkAll.checked = checked;
			updateSelectedCount();
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

		// Delete selected students dynamic confirmation and action
		var DSS_CODE = null;
		function openDeleteSelectedStudentsModal(){
			var testId = $("#test").val();
			if(testId == ""){ alert('Please select a test first'); return; }
			var selected = [];
			var checkboxes = document.querySelectorAll("input[name='rowSelectCheckBox2[]']:checked");
			for (var i = 0; i < checkboxes.length; i++) { selected.push(checkboxes[i].value); }
			if(selected.length === 0){ alert('Please select at least one student'); return; }
			DSS_CODE = generateCode();
			var body = '<p>You are about to <b>delete</b> '+selected.length+' selected student(s) from this test.</p>'+
				'<div style="background-color:#fff3cd; border:1px solid #ffeaa7; border-radius:5px; padding:10px; margin-bottom:10px;">'+
				'<div style="color:#856404; font-weight:bold;">Security confirmation required</div>'+
				'<div>Type this code: <span style="color:#dc3545; font-family:monospace; padding:6px 10px; border:2px solid #dc3545; border-radius:4px; letter-spacing:2px;">'+DSS_CODE+'</span></div>'+
				'</div>'+
				'<p style="color:#721c24; font-weight:bold;">This will also delete their previous attempts/answers for this test. This action cannot be undone.</p>';
			document.getElementById('deleteSelectedStudentsBody').innerHTML = body;
			document.getElementById('deleteSelectedStudentsInput').value='';
			document.getElementById('deleteSelectedStudentsError').innerHTML='';
			document.getElementById('deleteSelectedStudentsMsg').innerHTML='';
			document.getElementById('btnConfirmDeleteSelectedStudents').disabled = true;
			document.getElementById('modalDeleteSelectedStudents').style.display = 'block';
		}
		function closeDeleteSelectedStudentsModal(){ document.getElementById('modalDeleteSelectedStudents').style.display = 'none'; }
		function onDeleteSelectedStudentsInput(){ var v=document.getElementById('deleteSelectedStudentsInput').value.trim(); var ok=(DSS_CODE && v===DSS_CODE); document.getElementById('btnConfirmDeleteSelectedStudents').disabled=!ok; document.getElementById('deleteSelectedStudentsError').innerHTML=''; }
		function confirmDeleteSelectedStudents(){
			var testId = $("#test").val();
			var v=document.getElementById('deleteSelectedStudentsInput').value.trim();
			if(!(DSS_CODE && v===DSS_CODE)){ document.getElementById('deleteSelectedStudentsError').innerHTML='Please type the confirmation code exactly as shown.'; return; }
			document.getElementById('deleteSelectedStudentsMsg').innerHTML = '<i class="fa fa-spinner fa-spin"></i> Deleting, please wait...';
			document.getElementById('btnConfirmDeleteSelectedStudents').disabled = true;
			var ids = [];
			var checkboxes = document.querySelectorAll("input[name='rowSelectCheckBox2[]']:checked");
			for (var i = 0; i < checkboxes.length; i++) { ids.push(checkboxes[i].value); }
			$.ajax({
				url:'deleteSelectedStudentsFromTest.php',
				type:'POST',
				data:{ testId: testId, studentIds: ids },
				dataType: 'json',
				success:function(resp){
					if(resp.success==0){ window.location.replace('logout.php'); }
					else if(resp.success==1){
						var msg = '<i class="fa fa-check"></i> Selected students deleted.';
						msg += ' <span style="color:#888; font-size:13px;">(Essay records deleted: ' + resp.deletedEssays + ')</span>';
						document.getElementById('deleteSelectedStudentsMsg').innerHTML = msg;
						setTimeout(function(){ closeDeleteSelectedStudentsModal(); $("#studentClass").trigger('change'); }, 1200);
					}
					else {
						var msg = 'Could not delete selected students. Please try again.';
						msg += ' <span style="color:#888; font-size:13px;">(Essay records deleted: ' + resp.deletedEssays + ')</span>';
						document.getElementById('deleteSelectedStudentsMsg').innerHTML = msg;
						document.getElementById('btnConfirmDeleteSelectedStudents').disabled = false;
					}
				}
			});
		}
		
		//function to delete a student from a test
		function deleteStudentFromTest(testId, studentId, selectedClassId)
		{	
			$('.message1').html('');
			$('.message3').html('');
			$('.message4').html('');

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
							 $('#example').DataTable().clear().destroy();
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
			$('#example').DataTable().clear().destroy();
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
						var questionsAnswered = response[i].questionsAnswered;
						var totalQuestions = response[i].totalQuestions;									
						
						var tr_str = "<tr>" +
							"<td width='3%'><center>" + (i+1) + "</center></td>" +
							"<td width='32%'>"+surname+' '+firstName+' '+middleName+"</td>" +
							"<td width='15%'><center>" + studentClass + " ("+testAcademicYear+")</center></td>" +
							"<td width='10%'><center>" + testDuration + " minutes</center></td>" +
							"<td width='10%'><center>" + remainingTime + " minutes</center></td>" +
							"<td width='10%'><center>" + testStatusText + "</center></td>" +
							"<td width='15%'><center><input type='checkbox' name='rowSelectCheckBox2[]' "+callTestStatus(testStatus)+" id='rowSelectCheckBox2' value='"+studentId+"' /></center></td>" +
							"<td align='center' width='5%'>" +
								"<a onClick='deleteStudentFromTest(\""+testId+"\",\""+studentId+"\",\""+selectedClassId+"\")' title='Delete this student from test' style='cursor:pointer; margin-right:5px;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a>" +
								(((testStatus == 2 || (testStatus == 1 && remainingTime <= 0)) && questionsAnswered < totalQuestions) ? "<a onClick='openRetakeModal(\""+testId+"\",\""+studentId+"\",\""+selectedClassId+"\",\""+surname+" "+firstName+" "+middleName+"\")' title='Allow student to retake test (did not answer all questions)' style='cursor:pointer;'><i class='fa fa-refresh' aria-hidden='true' style='color:green;'></i></a>" : "") +
							"</td>" +
							
							"</tr>";
							$("#example tbody").append(tr_str);
					}
					if(len > 0)
					{
						$("#theTimeEdit").show();
					}
					else
					{
						$("#theTimeEdit").hide();
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
		function addOrSubtractTestTime()
		{
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
			$('.message4').html('');
			$('.message6').html('');
			$('.message7').html('');
			
			var favorite2 = [];
			var checkboxes = document.querySelectorAll("input[name='rowSelectCheckBox2[]']:checked");

			for (var i = 0; i < checkboxes.length; i++) {
				favorite2.push(checkboxes[i].value)
			}
			
			favorite2 = favorite2.join(",")
			var testId = $('input[name=testIdToAddStudentsTime]').val();
			var timeValue = $('input[name=valueToModify]').val();
			var studentClassId = $('input[name=classIdToModifyTime]').val();
			var timeAction = $('input[name="addOrSubtract"]:checked').val();
            var getActionText = getAddOrSubtractTimeText(timeAction);
			
			$('.message1').html('')
			
			if (timeValue=="")
			{
				$('.message6').html('<i class="fa fa-info-circle"></i> ' + ' Please enter time to be added or subtracted')
			}
			else if (favorite2=="")
			{
				$('.message6').html('<i class="fa fa-info-circle"></i> Please check at least one student before submitting')
			}
			else if (testId=="")
			{
				$('.message6').html('<i class="fa fa-info-circle"></i> Please select test')
			}
			else if ($('input[name="addOrSubtract"]:checked').length < 1)
			{
				$('.message6').html('<i class="fa fa-info-circle"></i> Please select action to be carried out')
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
									$('.message6').html('');
									$('.message7').html('<i class="fa fa-check-circle"></i> ' + timeValue + ' minute(s) has/have been successfully '+ getActionText +' selected student(s)').fadeIn('slow');
									viewAdded1(testId, studentClassId);
								}
								else if (html==3)	//If examinees successfully added to write test
								{                              
									$('.message6').html('');
									$('.message7').html('<i class="fa fa-check-circle"></i> ' + timeValue + ' minute(s) has/have been successfully '+ getActionText +' some selected students. The action could not be carried out on some students with remaining time less than time to be subtracted.').fadeIn('slow');
									viewAdded1(testId, studentClassId);
								}
								else if(html==2) 	//If insertion is unsuccessful	
								{                              
									 $('.message7').html('');
									 $('.message6').html('<i class="fa fa-check-circle"></i> Cannot add time to selected students when the test has not started.').fadeIn('slow');
								}
							}
										
						});
				}
			}
		}	
		//End of add/subtract test time
		
		// Retake test functionality
		var RETAKE_CODE = null;
		var retakeTestId = null;
		var retakeStudentId = null;
		var retakeClassId = null;
		
		function openRetakeModal(testId, studentId, classId, studentName) {
			RETAKE_CODE = generateCode();
			retakeTestId = testId;
			retakeStudentId = studentId;
			retakeClassId = classId;
			
			var body = '<p>You are about to allow <b>' + studentName + '</b> to retake this test.</p>' +
				'<div style="background-color:#fff3cd; border:1px solid #ffeaa7; border-radius:5px; padding:10px; margin-bottom:10px;">' +
				'<div style="color:#856404; font-weight:bold;">Security confirmation required</div>' +
				'<div>Type this code: <span style="color:#28a745; font-family:monospace; padding:6px 10px; border:2px solid #28a745; border-radius:4px; letter-spacing:2px;">' + RETAKE_CODE + '</span></div>' +
				'</div>' +
				'<p style="color:#155724; font-weight:bold;">This student did not answer all questions. This will reset the student\'s test status but preserve their previous answers. Additional time (2 minutes per unanswered question) will be added to help them complete the test.</p>';
			
			document.getElementById('retakeTestBody').innerHTML = body;
			document.getElementById('retakeTestInput').value = '';
			document.getElementById('retakeTestError').innerHTML = '';
			document.getElementById('retakeTestMsg').innerHTML = '';
			document.getElementById('btnConfirmRetakeTest').disabled = true;
			document.getElementById('modalRetakeTest').style.display = 'block';
		}
		
		function closeRetakeModal() {
			document.getElementById('modalRetakeTest').style.display = 'none';
		}
		
		function onRetakeTestInput() {
			var v = document.getElementById('retakeTestInput').value.trim();
			var ok = (RETAKE_CODE && v === RETAKE_CODE);
			document.getElementById('btnConfirmRetakeTest').disabled = !ok;
			document.getElementById('retakeTestError').innerHTML = '';
		}
		
		function confirmRetakeTest() {
			var v = document.getElementById('retakeTestInput').value.trim();
			if (!(RETAKE_CODE && v === RETAKE_CODE)) {
				document.getElementById('retakeTestError').innerHTML = 'Please type the confirmation code exactly as shown.';
				return;
			}
			
			document.getElementById('retakeTestMsg').innerHTML = '<i class="fa fa-spinner fa-spin"></i> Resetting test, please wait...';
			document.getElementById('btnConfirmRetakeTest').disabled = true;
			
			$.ajax({
				url: 'allowStudentRetakeTest.php',
				type: 'POST',
				data: { 
					testId: retakeTestId, 
					studentId: retakeStudentId 
				},
				dataType: 'json',
				success: function(response) {
					if (response == 0) {
						window.location.replace('logout.php');
					} else if (typeof response === 'object' && response.success == 1) {
						var message = '<i class="fa fa-check"></i> Student can now retake the test. Previous answers are preserved.<br>' +
									  '<div style="margin-top: 10px; padding: 8px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">' +
									  '<strong>Additional Time Added:</strong><br>' +
									  '• Unanswered questions: ' + response.unansweredQuestions + '<br>' +
									  '• Additional time: ' + response.additionalTimeMinutes + ' minutes<br>' +
									  '• Total test time: ' + response.totalTimeMinutes + ' minutes' +
									  '</div>';
						document.getElementById('retakeTestMsg').innerHTML = message;
						setTimeout(function() {
							closeRetakeModal();
							$("#studentClass").trigger('change');
						}, 2000);
					} else {
						document.getElementById('retakeTestMsg').innerHTML = 'Could not reset test. Please try again.';
						document.getElementById('btnConfirmRetakeTest').disabled = false;
					}
				},
				error: function() {
					document.getElementById('retakeTestMsg').innerHTML = 'Could not reset test. Please try again.';
					document.getElementById('btnConfirmRetakeTest').disabled = false;
				}
			});
		}
		
			// Test search removed — dropdown now shows full test list without client-side filtering
	</script>
  </body>
</html>
