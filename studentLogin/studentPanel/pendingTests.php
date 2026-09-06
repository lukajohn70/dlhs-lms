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
		require_once "../../scripts/test_workflow_helper.php";
		$configuredTestTypes = dlhsGetConfiguredTestTypes($connection);
	}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DLHS Dashboard">
    <meta name="author" content="DLHS IT Department">
    <meta name="keyword" content="DLHS, Dashboard, Admin, Education, School">
    <link rel="icon" type="image/jpg" href="../images/dlhslogo3.jpg">

    <title>Tests Not started | DLHS</title>

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
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
	<!-- DataTables CSS -->
	<link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="../../datatables/css/rowReorder.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>
	<style>
		/* Modern Modal Styles */
		.modal1 {
			display: none;
			position: fixed;
			z-index: 1000;
			left: 0;
			top: 0;
			width: 100%;
			height: 100%;
			background-color: rgba(0,0,0,0.6);
			animation: fadeIn 0.3s ease-out;
			overflow: auto;
		}

		@keyframes fadeIn {
			from { opacity: 0; }
			to { opacity: 1; }
		}

		@keyframes slideDown {
			from {
				transform: translateY(-50px);
				opacity: 0;
			}
			to {
				transform: translateY(0);
				opacity: 1;
			}
		}

		.modal-content {
			position: relative;
			background-color: #fff;
			margin: 5% auto;
			width: 85%;
			max-width: 700px;
			border-radius: 20px;
			box-shadow: 0 10px 40px rgba(0,0,0,0.2);
			overflow: hidden;
			animation: slideDown 0.4s ease-out;
		}

		/* Modern Close Button */
		.close1 {
			position: absolute;
			top: 15px;
			right: 20px;
			color: #FFD700;
			font-size: 32px;
			font-weight: bold;
			cursor: pointer;
			z-index: 1001;
			transition: all 0.3s ease;
			background: transparent;
			border: none;
			padding: 0;
			line-height: 1;
		}

		.close1:hover, .close1:focus {
			color: #fff;
			transform: rotate(90deg);
			text-decoration: none;
		}

		/* Modern Header */
		.modal-header-custom {
			background: linear-gradient(135deg, #003366 0%, #004080 100%);
			color: #FFD700;
			padding: 30px 40px;
			text-align: center;
			border-radius: 20px 20px 0 0;
		}

		.modal-header-custom h3 {
			margin: 0;
			font-size: 26px;
			font-weight: 600;
			letter-spacing: 0.5px;
			text-transform: uppercase;
		}

		.modal-header-custom .fa {
			margin-right: 10px;
			font-size: 28px;
		}

		/* Modern Body */
		.modal-body-custom {
			background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
			padding: 35px 40px;
		}

		.modal-notice {
			background: #fff;
			border-left: 4px solid #dc3545;
			padding: 15px 20px;
			margin-bottom: 25px;
			border-radius: 8px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
		}

		.modal-notice h4 {
			color: #dc3545;
			font-size: 20px;
			margin: 0;
			font-weight: 600;
		}

		/* Modern List Styles */
		.instructions-list {
			background: #fff;
			padding: 25px 30px;
			border-radius: 12px;
			box-shadow: 0 3px 15px rgba(0,0,0,0.08);
			margin-bottom: 25px;
		}

		.instructions-list li {
			font-size: 18px;
			line-height: 1.8;
			padding: 12px 10px;
			margin-bottom: 10px;
			color: #333;
			border-left: 3px solid #003366;
			padding-left: 20px;
			background: linear-gradient(90deg, rgba(0,51,102,0.03) 0%, transparent 100%);
			border-radius: 5px;
			transition: all 0.3s ease;
		}

		.instructions-list li:hover {
			background: linear-gradient(90deg, rgba(0,51,102,0.08) 0%, transparent 100%);
			transform: translateX(5px);
		}

		.instructions-list li strong {
			color: #003366;
		}

		/* Modern Button */
		.modal-btn-start {
			display: inline-block;
			background: linear-gradient(135deg, #003366 0%, #004080 100%);
			color: #FFD700 !important;
			padding: 16px 50px;
			font-size: 20px;
			font-weight: 600;
			text-decoration: none;
			border-radius: 50px;
			box-shadow: 0 4px 15px rgba(0,51,102,0.3);
			transition: all 0.3s ease;
			text-transform: uppercase;
			letter-spacing: 1px;
			border: none;
			cursor: pointer;
		}

		.modal-btn-start:hover {
			transform: translateY(-3px);
			box-shadow: 0 6px 25px rgba(0,51,102,0.4);
			color: #fff !important;
			text-decoration: none;
		}

		.modal-btn-start:active {
			transform: translateY(-1px);
		}

		.modal-btn-start .fa {
			margin-left: 10px;
		}

		.modal-footer-custom {
			text-align: center;
			padding: 25px;
		}

		/* Legacy styles for compatibility */
		ol.instructions li{
			padding-left:1em;
			padding-top:5px;
			margin-left:20px;
		}
		ol.courses li{
			padding-left:1em;
			margin-left:20px;
			padding-bottom:10px;
		}
		ul.course4 li{
			padding-left:1em;
			margin-left:20px;
			padding-bottom:10px;
			list-style-type:circle;
		}
		#myBtn1:hover {
			cursor: pointer;
		}
	</style>
	<script src="jQuery3.3.1.js"></script>
  </head>
  <body>

	<!-- container section start -->
	<section id="container" class="">
		<!--Including the header-->
		<?php include 'header.php'; ?>

		<!--Including the sidebar-->
		<?php include 'sideBar_pendingTests.php'; ?>

		<!--main content start-->
		<section id="main-content">
			<section class="wrapper">
				<div class="row">
					<div class="col-lg-12">
						<h3 class="page-header"><i class="fa fa-file-text-o"></i> Tests Not started</h3>
						<ol class="breadcrumb" style="font-size:12px;">
							<li style="margin-left:-12px;"><i class="fa fa-home"></i><a href="index.php">Home</a></li>
							<li><i class="icon_document_alt"></i>Tests Not started</li>
							<a href="#" style="color:#0acca2; padding-left:4px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
						</ol>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<section class="panel">
							<header class="panel-heading">
								View Tests not started &nbsp; &nbsp; <div class="message4" style="color:green; font-size:17px;" align="center"></div><div class="message5" style="color:red; font-size:17px;" align="center"></div>
							</header>
							<div class="panel-body">
								<div class="row" style="margin-bottom:15px;">
									<div class="col-md-4">
										<label>Search tests</label>
										<div class="input-group">
											<input type="text" class="form-control" id="testSearchInput" placeholder="Search by test name, subject or date">
											<span class="input-group-btn">
												<button type="button" class="btn btn-primary" id="testSearchBtn">Search</button>
											</span>
										</div>
									</div>
									<div class="col-md-3">
										<label>Filter by type</label>
										<select class="form-control" id="testTypeFilter">
											<option value="">... All Test Types ...</option>
											<?php foreach ($configuredTestTypes as $configuredTestType) { ?>
											<option value="<?php echo htmlspecialchars($configuredTestType); ?>"><?php echo htmlspecialchars($configuredTestType); ?></option>
											<?php } ?>
										</select>
									</div>
									<div class="col-md-2" style="padding-top:25px;">
										<button type="button" class="btn btn-default" id="clearSearchBtn">Clear</button>
									</div>
								</div>
								<style>
									/* Finer Tabs Styling */
									.custom-finer-tabs {
										border-bottom: 2px solid #e2e8f0;
										margin-bottom: 25px;
										display: flex;
										gap: 10px;
									}
									.custom-finer-tabs > li {
										margin-bottom: -2px;
									}
									.custom-finer-tabs > li > a {
										border: none !important;
										color: #64748b;
										font-weight: 600;
										font-size: 15px;
										padding: 12px 20px;
										border-bottom: 3px solid transparent !important;
										border-radius: 8px 8px 0 0;
										transition: all 0.2s ease;
										background: transparent;
									}
									.custom-finer-tabs > li > a:hover {
										color: #0f172a;
										background-color: #f8fafc;
										border-bottom-color: #cbd5e1 !important;
									}
									.custom-finer-tabs > li.active > a, 
									.custom-finer-tabs > li.active > a:hover, 
									.custom-finer-tabs > li.active > a:focus {
										color: #003366;
										background-color: transparent !important;
										border-bottom: 3px solid #003366 !important;
									}
									.custom-finer-tabs > li > a i {
										margin-right: 6px;
										opacity: 0.8;
									}
								</style>
								<ul class="nav nav-tabs custom-finer-tabs" id="testTabs">
									<li class="active"><a data-toggle="tab" href="#notStartedTab"><i class="fa fa-clock-o"></i> Not Started</a></li>
									<li><a data-toggle="tab" href="#inProgressTab"><i class="fa fa-play-circle"></i> In Progress</a></li>
									<li><a data-toggle="tab" href="#endedTab"><i class="fa fa-check-circle"></i> Completed</a></li>
								</ul>
								
								<div class="tab-content">
									<div id="notStartedTab" class="tab-pane fade in active">
										<div class="table-responsive">
											<table id="notStartedTable" class="table table-striped table-bordered bulk_action" style="width:100%">
												<thead>
													<tr><th width="5%"><center>S/NO</center></th><th width="26%"><center>TEST NAME</center></th><th width="10%"><center>TYPE</center></th><th width="10%"><center>CLASS</center></th><th width="16%"><center>DATE</center></th><th width="10%"><center>DURATION</center></th><th width="15%"><center>ACTION</center></th></tr>
												</thead>
												<tbody></tbody>
											</table>
										</div>
									</div>
									<div id="inProgressTab" class="tab-pane fade">
										<div class="table-responsive">
											<table id="inProgressTable" class="table table-striped table-bordered bulk_action" style="width:100%">
												<thead>
													<tr><th width="5%"><center>S/NO</center></th><th width="26%"><center>TEST NAME</center></th><th width="10%"><center>TYPE</center></th><th width="10%"><center>CLASS</center></th><th width="16%"><center>DATE</center></th><th width="10%"><center>DURATION</center></th><th width="15%"><center>ACTION</center></th></tr>
												</thead>
												<tbody></tbody>
											</table>
										</div>
									</div>
									<div id="endedTab" class="tab-pane fade">
										<div class="table-responsive">
											<table id="endedTable" class="table table-striped table-bordered bulk_action" style="width:100%">
												<thead>
													<tr><th width="5%"><center>S/NO</center></th><th width="26%"><center>TEST NAME</center></th><th width="10%"><center>TYPE</center></th><th width="10%"><center>CLASS</center></th><th width="16%"><center>DATE</center></th><th width="10%"><center>DURATION</center></th><th width="15%"><center>STATUS</center></th></tr>
												</thead>
												<tbody></tbody>
											</table>
										</div>
									</div>
								</div>
								<!-- End of table-responsive -->
							   </div>
						</section>
					</div>
				</div>			
			</div>
		</div>
			  
				<!--Modal for giving more instructions-->
				<div id="myModal1" class="modal1">
					<div class="modal-content">
						<span class="close1">&times;</span>
						
						<!-- Modern Header -->
						<div class="modal-header-custom">
							<h3><i class="fa fa-file-text-o"></i> Test Instructions & Guidelines</h3>
						</div>
						
						<!-- Modern Body -->
						<div class="modal-body-custom">
							<!-- Important Notice -->
							<div class="modal-notice">
								<h4><i class="fa fa-exclamation-triangle"></i> Please note the following instructions to guide you</h4>
							</div>
							
							<!-- Instructions List -->
							<ol type="a" class="instructions-list">
								<li>Do <strong>not</strong> minimize your browser while taking the test</li>
								<li>Do <strong>not</strong> close your browser unless you have finished and submitted your test</li>
								<li>Do <strong>not</strong> try to open any application in your device while taking the test</li>
								<li>Contravening any of the aforementioned instructions may lead to <strong>cancellation of your test</strong></li>
								<li>Please note that your test starts and your time starts counting down once you click <strong>Start Test</strong></li>
							</ol>
							
							<div id="setValue"></div>
							
							<!-- Modern Button -->
							<div class="modal-footer-custom">
								<a href="#" id="goToTest" class="modal-btn-start">
									Start Test <i class="fa fa-play-circle"></i>
								</a>
							</div>
						</div>
					</div>	<!-- End of modal-->
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
     <!-- bootstrap-wysiwyg -->
    <script src="js/jquery.hotkeys.js"></script>
    <script src="js/form-component.js"></script>
    <!-- custome script for all page -->
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
	<script src="js/scripts.js"></script>

	<script>
	
		var setId=0;
		function openModal(testId)
		{
			var modal1 = document.getElementById("myModal1");
			setId=testId;
			modal1.style.display = "block";		
		}
		
		// Get the <span> element that closes the modal
		var span1 = document.getElementsByClassName("close1")[0];
		
		// When the user clicks on <span> (x), close the modal2
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
		
		//function to open a new window
		var goToTest = document.getElementById("goToTest");
		
		goToTest.onclick = function() {
			
			var form_data = 
						  'testId='+setId+'&section=objective';
						  						 						 
						 var returnedValue=0;

						$.ajax({
							url: "startExam.php",
							type: "POST",
							data: form_data,
							success: function (html) {     
								if(html==0)
								{
									window.location.replace("logout.php");
								}
														else if (html==1 || html==4)
														{
															var myDeviceWidth = window.innerWidth;
															var myDeviceHeight = window.innerHeight;
															window.open("exam.php", "", "width="+myDeviceWidth+",height="+myDeviceHeight);
														}
								else if (html==2)
								{
									alert("This test has not been started by the invigilator yet. Please wait.");
								}
								else if (html==7)
								{
									alert("⏳ You have not been marked present yet.\n\nPlease wait for the invigilator to mark your attendance before you can start the test.");
								}
								else if (html==3)
								{
									alert("You were not added to take this test");
								}
								else if (html==4)
								{
									alert("You have already started this test. Please use the link for 'Tests in progress'");
								}
								else if (html==5)
								{
									alert("You have already submitted this test.");
								}
								else if (html==6)
								{
									alert("Submit the essay first before opening the objective section.");
								}
							}
										
						});						
			
		}
		
		function getStatus(testId, testStatus)
		{
			if(testStatus == 1)
			{
				return "Test enabled - <a onclick='openModal(\""+testId+"\")' style='cursor:pointer;'>Click to start test</a>";
			}
			else if(testStatus == 0)
			{
				return "Test yet to be enabled";
			}
		}
		
		$(document).ready(function() {
			// Handle URL hash for direct tab linking
			var hash = window.location.hash;
			if (hash) {
				$('.nav-tabs a[href="' + hash + '"]').tab('show');
			}
			$('.nav-tabs a').on('click', function (e) {
				window.location.hash = this.hash;
			});
			
			getPendingTests();
		});
		
		function getPendingTests()
		{		
			$.ajax({
					url: 'getPendingTests.php',
					type: 'GET',
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
								
						var notStartedCount = 1;
						var inProgressCount = 1;
						var endedCount = 1;
						
						var todayDate = new Date();
						var todayDateString = todayDate.getFullYear() + "-" + 
											  String(todayDate.getMonth() + 1).padStart(2, '0') + "-" + 
											  String(todayDate.getDate()).padStart(2, '0');
						
						for(var i=0; i<len; i++){
							var testId = response[i].testId;
							var testNameAndSubject = response[i].testNameAndSubject;
							var testType = response[i].testTypeLabel || response[i].testType;
							var theClass = response[i].theClass;
							var testStatus = response[i].testStatus;
							var testDate = response[i].testDate;							
							var duration = response[i].duration;						
							var examineeTestStatus = response[i].examineeTestStatus;						
							var hasEssay = !!response[i].hasEssay;
							var essaySubmitted = !!response[i].essaySubmitted;
							var reviewOption = response[i].reviewOption;
								
							var statusLabel = "";
							if (examineeTestStatus == 2) {
								statusLabel = "<span style='display:inline-block; margin-bottom:4px; color:#28a745; font-weight:600;'><i class='fa fa-check-circle'></i> Test Completed</span>";
							} else if (examineeTestStatus == 1) {
								statusLabel = "<span style='display:inline-block; margin-bottom:4px; color:#f0ad4e; font-weight:600;'><i class='fa fa-play-circle'></i> In Progress</span>";
							} else {
								statusLabel = (testStatus == 1) ? "<span style='display:inline-block; margin-bottom:4px; color:#5bc0de; font-weight:600;'><i class='fa fa-clock-o'></i> Ready to Start</span>" : "<span style='display:inline-block; margin-bottom:4px; color:#dc3545; font-weight:600;'><i class='fa fa-lock'></i> Not Yet Enabled</span>";
							}

							var buttonsHtml = "";
							// If the examinee already started the test (in progress), show Continue button
							if (examineeTestStatus == 1) {
								buttonsHtml = "<button class='btn btn-warning' style='border-radius:20px; padding:8px 20px; background:#ff8800; color:#fff; border:none;' onclick=\"openModal('" + testId + "')\"><i class='fa fa-arrow-right'></i> Continue Test</button>";
							}
							else if (examineeTestStatus == 0 && testStatus == 1) {
								if (hasEssay && !essaySubmitted) {
									buttonsHtml = "<button class='btn btn-primary' style='border-radius:20px; padding:8px 20px; background:#003366; color:#FFD700; border:none; margin-bottom:4px;' onclick=\"startAndOpenEssay('" + testId + "')\"><i class='fa fa-pencil'></i> Start Essay</button>";
									buttonsHtml += "<br><span style='display:inline-block; margin-top:4px; font-size:12px; color:#8a6100; font-weight:600;'>Objective opens after essay</span>";
								} else {
									buttonsHtml = "<button class='btn btn-success' style='border-radius:20px; padding:8px 20px; background:#28a745; color:#fff; border:none;' onclick=\"openModal('" + testId + "')\"><i class='fa fa-play'></i> Start Objective</button>";
								}
							}
							else if (examineeTestStatus == 2) {
								buttonsHtml = "<span style='color:#28a745; font-size:12px;'><i class='fa fa-check'></i> Submitted</span>";
							}
							else {
								buttonsHtml = "<span style='color:#999; font-size:12px;'><i class='fa fa-info-circle'></i> Waiting for invigilator</span>";
							}

							var targetTable = "";
							var indexToUse = 0;
							
							if (examineeTestStatus == 2) {
								targetTable = "#endedTable";
								indexToUse = endedCount++;
							} else if (examineeTestStatus == 1) {
								targetTable = "#inProgressTable";
								indexToUse = inProgressCount++;
							} else {
								// Show all scheduled tests that are not started
								targetTable = "#notStartedTable";
								indexToUse = notStartedCount++;
							}
							
							if (targetTable !== "") {
								var tr_str = "<tr style='color:#000000;'>" +
									"<td width='3%'><center>" + indexToUse + "</center></td>" +
									"<td width='20%'><center>" + testNameAndSubject + "</center></td>" +
									"<td width='10%'><center>" + testType + "</center></td>" +
									"<td width='8%'><center>" + theClass + "</center></td>" +
									"<td width='10%'><center>" + testDate + "</center></td>" +
									"<td width='8%'><center>" + duration + "</center></td>" +
									"<td width='5%'><center>" + statusLabel + "<br>" + buttonsHtml + "</center></td>" +
								"</tr>";
								$(targetTable + " tbody").append(tr_str);
							}
						}
						
						var dtOptions = {
							"paging":   true,
							"ordering": true,
							"info":     true,
							"responsive": true,
							dom: 'lBfrtip',
							buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
						};
						
						var tableNotStarted = $('#notStartedTable').DataTable(dtOptions);
						var tableInProgress = $('#inProgressTable').DataTable(dtOptions);
						var tableEnded = $('#endedTable').DataTable(dtOptions);
						
						function searchAllTables(val) {
							tableNotStarted.search(val).draw();
							tableInProgress.search(val).draw();
							tableEnded.search(val).draw();
						}
						
						function filterAllTables(val) {
							var filterValue = val ? '^' + $.fn.dataTable.util.escapeRegex(val) : '';
							tableNotStarted.column(2).search(filterValue, true, false).draw();
							tableInProgress.column(2).search(filterValue, true, false).draw();
							tableEnded.column(2).search(filterValue, true, false).draw();
						}

						$('#testSearchBtn').off('click').on('click', function(){
							searchAllTables($('#testSearchInput').val());
						});
						$('#testSearchInput').off('keypress').on('keypress', function(e){
							if (e.which === 13) {
								e.preventDefault();
								searchAllTables($(this).val());
							}
						});
						$('#testTypeFilter').off('change').on('change', function(){
							filterAllTables(this.value);
						});
						$('#clearSearchBtn').off('click').on('click', function(){
							$('#testSearchInput').val('');
							$('#testTypeFilter').val('');
							searchAllTables('');
							filterAllTables('');
						});
					}
			});
		}
		// getPendingTests() is now called from document.ready (see above)

		// Attempt to start an essay: call startExam.php first to set session/test state, then open viewEssay
		function startAndOpenEssay(testId) {
			console.log('startAndOpenEssay called for', testId);
			$.ajax({
				url: 'startExam.php',
				type: 'POST',
				data: { testId: testId, section: 'essay' },
				success: function (html) {
					console.log('startExam response for essay', html);
					// treat 1 or 4 as allowed to open (1 = not started now starting; 4 = in progress)
					if (html == 1 || html == 4) {
						var w = window.screen.width;
						var h = window.screen.height;
						window.open('viewEssay.php?testId=' + encodeURIComponent(testId), '_blank', 'width=' + w + ',height=' + h + ',top=0,left=0');
					} else if (html == 2) {
						alert('This test has not been started by the invigilator yet. Please wait.');
					} else if (html == 7) {
						alert('⏳ You have not been marked present yet.\n\nPlease wait for the invigilator to mark your attendance before you can start the test.');
					} else if (html == 3) {
						alert('You were not added to take this test');
					} else if (html == 5) {
						alert('You have already submitted this test.');
					} else if (html == 6) {
						alert('Submit the essay first before opening the objective section.');
					} else if (html == 0) {
						window.location.replace('logout.php');
					} else {
						alert('Unexpected server response: ' + html);
					}
				},
				error: function (xhr, status, err) {
					console.error('startExam AJAX error', status, err, xhr.responseText);
					alert('Unable to start test. Please check your network or contact support.');
				}
			});
		}
	</script>
	
	
  </body>
</html>
