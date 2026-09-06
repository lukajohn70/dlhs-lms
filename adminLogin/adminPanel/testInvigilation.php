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
		require_once "../../scripts/test_workflow_helper.php";
		$configuredTestTypes = dlhsGetConfiguredTestTypes($connection);
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

    <title>Invigilation | DLHS</title>

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
	
    <!-- date picker -->
    
    <!-- color picker -->
    
    <!-- Custom styles -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="../../datatables/css/rowReorder.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>
	<script src="jQuery3.3.1.js"></script>
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
            color: red;
            text-decoration: none;
            cursor: pointer;
            }
			
			body{
			overflow-x:hidden;
			overflow-y:auto;
			min-height: 100vh;
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
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Set Invigilators</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Invigilators</li>
						<li><a href="#" style="color:#0acca2;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a></li>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-12">
						<section class="panel">
							<header class="panel-heading">
								View | Edit Academic Year
							</header>
							<div class="panel-body">
								<form>
									<div class="form-group">
										<label>Select Year group</label>
										<select class="form-control m-bot15" name="yearGroup" id="yearGroup" style="width:400px;">
										<option value="">... Select year group ...</option>
										<?php
											$getYearGroups="SELECT * FROM yeargroup";
											$result = $connection->query($getYearGroups);
											while($row = $result->fetch_array(MYSQLI_NUM)){
										?>
										<option value="<?php echo $row[0]; ?>"><?php echo $row[1]; ?></option>
										<?php } ?>
									</select>
									</div>
									<div class="form-group">
										<label>Filter by test type</label>
										<select class="form-control m-bot15" name="testTypeFilter" id="testTypeFilter" style="width:400px;">
											<option value="">... All test types ...</option>
											<?php foreach ($configuredTestTypes as $configuredTestType) { ?>
											<option value="<?php echo htmlspecialchars($configuredTestType); ?>"><?php echo htmlspecialchars($configuredTestType); ?></option>
											<?php } ?>
										</select>
									</div>
									<br>
								</form>
							</div>
							<div class="panel-body">
								<div class="message4" style="color:green; font-size:17px;" align="center"></div><div class="message5" style="color:red; font-size:17px;" align="center"></div>
								<div class="table-responsive">
									<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
												<thead>
													<tr>
														<th width="3%">S/NO</th>
														<th width='21'><center>TEST NAME</center></th>
														<th width='12%'><center>DATE</center></th>
														<th width='8%'><center>DURATION</center></th>
														<th width='10%'><center>START TIME</center></th>
														<th width='10%'><center>TEST YEAR</center></th>
														<th width='5%'><center>REVIEW?</center></th>
														<th width='5%'><center>ESSAY?</center></th>
														<th width='13%'><center>INVIGILATOR</center></th>
														<th width='13%'><center>TEST STATUS</center></th>
														<th width='15%'><center>GLOBAL COMMAND</center></th>
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
	  
	  <div id="myModal1" class="modal1"> <!--Start of modal to assign an invigilator to a test-->
		<div class="modal-content">
			<span class="close1">&times;</span>
			<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999; border-radius:5px 5px 0 0;"><center style="font-size:22px;">Assign Invigilators to Class Arms</center></div>
            <div style="background-color:#E9F1EA; padding:15px; margin:0px 10px 0px 10px; border:1px solid #ccc; border-top:none;">
                <form id="editTestTimeForm">
                    <div class="item form-group">
						<div class="col-md-12">
							<label style="color:#000000; font-size:16px;">Test: <b><span id="currentTestName"></span></b></label><br>
							<p class="text-muted">Select an invigilator for each class arm below. You can assign different teachers to different arms.</p>
							<input type="hidden" name="testId1" id="testId1">
                            
                            <div id="classArmsContainer" style="margin-top:20px; max-height:400px; overflow-y:auto; padding:10px; background:white; border:1px solid #ddd; border-radius:5px;">
                                <!-- Dynamic class arm rows go here -->
                                <div class="text-center" id="classLoader"><i class="fa fa-spinner fa-spin fa-2x"></i></div>
                            </div>
						</div>
					</div>
					<div class="item form-group" style="margin-top:20px;">
						<div class="col-md-12 text-right">
							<button class="btn btn-default" type="button" onclick="document.getElementById('myModal1').style.display='none'">Cancel</button>
							<button type="button" class="btn btn-success" onclick="saveClassInvigilators()"><i class="fa fa-save"></i> Save Assignments</button>
							<div class="message6" id="message6" style="color:red; margin-top:10px;" align="center"></div>
						</div>
					</div>
                </form>
			</div>
			<br>
			<br>
			<hr>
		</div>	<!-- End of modal content-->
	</div><!-- End of modal -->
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
		function getStatus(testId, testName, testStatus, yearGroupId)
		{
			if(testStatus == 0)
			{
				return "Yet to start - <i class='fa fa-hourglass-start' aria-hidden='true' style='color:brown; cursor:pointer;' title='Click to start test' onclick='clickToStart(\""+testId+"\",\""+testName+"\",\""+yearGroupId+"\")'></i>";
			}
			else if(testStatus == 1)
			{
				return "<font style='color:blue; cursor:pointer;'>In progress - <i class='fa fa-hourglass-end' aria-hidden='true' title='Click to end test' onclick='clickToEnd(\""+testId+"\",\""+testName+"\",\""+yearGroupId+"\")'></i></font>";
			}
			else if(testStatus == 2)
			{
				return "<font style='color:green;'>Ended - <i class='fa fa-refresh' aria-hidden='true' style='cursor:pointer;' title='Click to reschedule test' onclick='rescheduleTest(\""+testId+"\",\""+testName+"\",\""+yearGroupId+"\")'></i>";
			}
		}

        function globalTestControl(testId, status) {
            var msgs = ["LOCK this test?", "START this test for ALL arms?", "STOP this test for ALL arms?"];
            if(confirm("Are you sure you want to " + msgs[status])) {
                $.post("../../staffLogin/staffPanel/invigilationActions.php", {action: 'global_test_control', testId: testId, status: status}, function(data) {
                    var res = JSON.parse(data);
                    if(res.status == 'success') {
                        alert("Global command sent!");
                        location.reload();
                    } else {
                        alert("Error: " + res.message);
                    }
                });
            }
        }
		
		//Function to set the set the display of invigilator's Set
		function getInvigilatorStatus(testId, invigilatorId, invigilatorName, yearGroupId)
		{
            var btnText = invigilatorId == 0 ? "Assign Invigilators" : "Manage Assignments";
            var btnClass = invigilatorId == 0 ? "btn-info" : "btn-primary";
            return "<button class='btn btn-xs " + btnClass + "' onclick='openModalUpdateClassInvigilators(\""+testId+"\")'><i class='fa fa-users'></i> " + btnText + "</button>";
		}
		
		//function declaration to set academic year
		function setAcademicYear()
		{
			$('.message1').html("");
			$('.message2').html("");
					
			
			var academicYearId = document.getElementById('academicYear').value;
			var academicYearName = $('#academicYear option:selected').text();
			
			if (academicYearId=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select Academic year');
			}
			else
			{
				var form_data = 
				  'academicYearId='+academicYearId+
				  '&academicYearName='+academicYearName;
			
				$.ajax({
					url: "setAcademicYear.php",
					type: "POST",       
					data: form_data,    
					success: function (html) {   
						if (html==0) 
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1) 
						{                              
							$('.message2').html("");
							$('.message1').html('<i class="fa fa-check"></i> Academic year successfully set to ' + academicYearName).fadeIn('slow');
							$('#example').DataTable().clear().destroy();
							callTable();
						}
						else if (html==2)
						{                              
							$('.message1').html("");
							$('.message2').html('<i class="fa fa-times"></i> Could not set Academic year. Please try again.').fadeIn('slow');
						}
					}
				});
			}
		}	

		function callTable(yearGroupId)	//Declaration of the data table function
		{
			$('#example').DataTable().clear().destroy();
			
			$.ajax({
					url: 'getTests.php',
					type: 'POST',
					data: {yearGroupId : yearGroupId, testType : $('#testTypeFilter').val()},
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var testId = response[i].testId;
							var testName = response[i].testName;
							var testDate = response[i].testDate;						
							var duration = response[i].duration;						
							var timeToDisplay = response[i].timeToDisplay;						
							var subjectName = response[i].subjectName;						
							var yearGroupId = response[i].yearGroupId;						
							var yearGroupName = response[i].yearGroupName;						
							var testYear = response[i].testYear;						
							var reviewOption = response[i].reviewOption;						
							var essayOption = response[i].essayOption;						
							var invigilatorId = response[i].invigilatorId;						
							var invigilatorName = response[i].invigilatorName;						
							var status = response[i].status;						
							var tr_str = "<tr>" +
								"<td width='3%'>" + (i+1) + "</td>" +
								"<td width='21%'><center>" + testName + " ("+subjectName+")"+"</center></td>" +								
								"<td width='12%'><center>" + testDate + "</center></td>" +								
								"<td width='8%'><center>" + duration + "</center></td>" +								
								"<td width='10%'><center>" + timeToDisplay +"</center></td>" +								
								"<td width='10%'><center>" + testYear + "</center></td>" +								
								"<td width='5%'><center>" + reviewOption +"</center></td>" +								
								"<td width='5%'><center>" + essayOption +"</center></td>" +								
								"<td width='13%'><center>" + getInvigilatorStatus(testId, invigilatorId, invigilatorName, yearGroupId) +"</center></td>" +								
								"<td width='13%'><center>" + getStatus(testId, testName, status, yearGroupId) +"</center></td>" +								
								"<td width='15%'><center>" +
                                    "<button class='btn btn-xs btn-success' onclick='globalTestControl(\""+testId+"\", 1)' title='Start Global'><i class='fa fa-play'></i></button> " +
                                    "<button class='btn btn-xs btn-danger' onclick='globalTestControl(\""+testId+"\", 2)' title='Stop Global'><i class='fa fa-stop'></i></button> " +
                                    "<button class='btn btn-xs btn-warning' onclick='globalTestControl(\""+testId+"\", 0)' title='Lock Test'><i class='fa fa-lock'></i></button>" +
                                "</center></td>" +
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
		
		//function to start a test
		function clickToStart(testId, testName, yearGroupId)
		{
			var confirmIt = confirm("Are you sure you wish to start this test?");
			if(confirmIt == true)
			{
				var form_data = 
					  'testId='+testId;
					  
				$.ajax({
					url: "startTest.php",
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
							 callTable(yearGroupId);
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
						else if (html==6)
						{
							$('.message4').html("");
							$('.message5').html('<i class="fa fa-times"></i> Add students to this test before starting it.').fadeIn('slow');
						}
						else if (html==7)
						{
							$('.message4').html("");
							$('.message5').html('<i class="fa fa-times"></i> Add questions to this test before starting it.').fadeIn('slow');
						}
					}
									
				});
			}
		}	//End of function to start a test
		
		//function to end a test
		function clickToEnd(testId, testName, yearGroupId)
		{
			var confirmIt = confirm("Are you sure you wish to end this test?");
			if(confirmIt == true)
			{
				var form_data = 
					  'testId='+testId;
					  
				$.ajax({
					url: "endTest.php",
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
							 callTable(yearGroupId);
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
		function rescheduleTest(testId, testName, yearGroupId)
		{
			var confirmIt = confirm("Are you sure you wish to reschedule this test?");
			if(confirmIt == true)
			{
				var form_data = 
					  'testId='+testId;
					  
				$.ajax({
					url: "rescheduleTest.php",
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
							 callTable(yearGroupId);
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
		
		function openModalUpdateClassInvigilators(testId)
		{
            // Set modal basic info
            document.getElementById("testId1").value = testId;
            $("#currentTestName").text("Loading test details...");
            $("#classArmsContainer").html('<div class="text-center" id="classLoader"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
            
			var modal1 = document.getElementById("myModal1");
			modal1.style.display = "block";

            // Fetch class arms and existing assignments
            $.post("getClassInvigilators.php", {testId: testId}, function(data) {
                var res = JSON.parse(data);
                if(res.status == 'success') {
                    var html = '';
                    var staffOptions = '<option value="">-- Select Invigilator --</option>';
                    
                    $.each(res.staff, function(i, s) {
                        staffOptions += '<option value="'+s.id+'">'+s.name+'</option>';
                    });

                    if(res.classes.length == 0) {
                        html = '<p class="text-warning">No classes found for this test.</p>';
                    } else {
                        $.each(res.classes, function(i, c) {
                            var currentAssigned = res.assignments[c.classId] || "";
                            html += '<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding:10px 0;">';
                            html += '<div style="font-weight:bold; width:40%;">'+c.className+'</div>';
                            html += '<div style="width:60%;">';
                            html += '<select class="form-control class-assignment-select" data-class-id="'+c.classId+'">';
                            html += staffOptions;
                            html += '</select>';
                            html += '</div></div>';
                        });
                    }
                    $("#classArmsContainer").html(html);

                    // Pre-select existing assignments
                    $.each(res.classes, function(i, c) {
                        if (res.assignments[c.classId]) {
                            $('.class-assignment-select[data-class-id="'+c.classId+'"]').val(res.assignments[c.classId]);
                        }
                    });
                    $("#currentTestName").text("Test ID: " + testId);
                } else {
                    $("#classArmsContainer").html('<p class="text-danger">Error loading data.</p>');
                }
            });
		}

		var span1 = document.getElementsByClassName("close1")[0];
        span1.onclick = function() { modal1.style.display = "none"; }
        var modal1 = document.getElementById("myModal1");
        window.onclick = function(event) {
            if (event.target == modal1) { modal1.style.display = "none"; }
        }
		
		function saveClassInvigilators()
		{
			$('.message4').html(""); $('.message5').html(""); $('.message6').html("");
			
			var testId = document.getElementById('testId1').value;
            var assignments = {};
            
            $('.class-assignment-select').each(function() {
                var classId = $(this).data('class-id');
                var staffId = $(this).val();
                if(staffId) {
                    assignments[classId] = staffId;
                }
            });

            var btn = $(event.target);
            btn.text("Saving...").prop('disabled', true);

            $.post("saveClassInvigilators.php", {testId: testId, assignments: assignments}, function(data) {
                btn.html("<i class='fa fa-save'></i> Save Assignments").prop('disabled', false);
                var res = JSON.parse(data);
                if (res.status == 'success') {
                    modal1.style.display = "none";
                    $('.message4').html('<i class="fa fa-check"></i> Assignments successfully updated.').fadeIn('slow');
                    var yearGroupId = document.getElementById('yearGroup').value;
                    callTable(yearGroupId);
                } else {
                    $('.message6').html('<i class="fa fa-times"></i> Could not update. Please try again.').fadeIn('slow');
                }
            });
		}
		
		//If the yeargroup dropdown is selected
		$('#yearGroup').on('change', function(){
			var yearGroupId = document.getElementById('yearGroup').value;
			$('.message4').html("");
			callTable(yearGroupId);
		});

		$('#testTypeFilter').on('change', function(){
			var yearGroupId = document.getElementById('yearGroup').value;
			$('.message4').html("");
			if (yearGroupId !== "") {
				callTable(yearGroupId);
			}
		});
	</script>


  </body>
</html>
