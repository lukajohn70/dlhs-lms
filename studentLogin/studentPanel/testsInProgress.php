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
		require_once "../../scripts/essay_timer_helper.php";
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

    <title>Tests in progress | DLHS</title>

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
	<style>
		/* Modern Modal Styles */
		.modal1, .modal2 {
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

		/* Essay Modal Styles */
		.essay-modal-body {
			background: #E9F1EA;
			padding: 25px;
			margin: 0 15px 15px;
			border-radius: 12px;
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
		<?php include 'sideBar_inProgress.php'; ?>

		<!--main content start-->
		<section id="main-content">
			<section class="wrapper">
				<div class="row">
					<div class="col-lg-12">
						<h3 class="page-header"><i class="fa fa-file-text-o"></i> Tests in progress</h3>
						<ol class="breadcrumb" style="font-size:12px;">
							<li style="margin-left:-12px;"><i class="fa fa-home"></i><a href="index.php">Home</a></li>
							<li><i class="icon_document_alt"></i>Tests in progress</li>
							<a href="#" style="color:#0acca2; padding-left:4px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
						</ol>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12">
						<section class="panel">
							<header class="panel-heading">
								View Tests in progress &nbsp; &nbsp; <div class="message4" style="color:green; font-size:17px;" align="center"></div><div class="message5" style="color:red; font-size:17px;" align="center"></div>
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
								<div class="table-responsive">
									<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
										<thead>
											<tr>
												<th width="5%"><center>S/NO</center></th>
												<th width="20%"><center>TEST NAME</center></th>
												<th width="9%"><center>TYPE</center></th>
												<th width="14%"><center>DATE(yyyy-mm-dd)</center></th>
												<th width="10%"><center>DURATION</center></th>
												<th width="16%"><center>TIME YOU STARTED</center></th>
												<th width="10%"><center>YEAR GRP</center></th>
												<th width="20%"><center>STATUS</center></th>
											</tr>
										</thead>
										<tbody>
											<?php
												$studentId = $_SESSION['studentId'];
												$studentYearGroupId = $_SESSION['studentYearGroup'];
												
												$testsForDisplay = array();
												$query="SELECT * FROM tests WHERE yearGroup='$studentYearGroupId'";
												$result = $connection->query($query);
												if($result)
												{
													while($row = $result->fetch_assoc())
													{
														$testId  = (int) $row['testId'];
														$testName  = isset($row['testName']) ? $row['testName'] : '';
														$testDate = isset($row['testDate']) ? $row['testDate'] : '';
														$duration = isset($row['duration']) ? $row['duration'] : '';
														$startHour = isset($row['startHour']) ? $row['startHour'] : '';
														$startMinute = isset($row['startMinute']) ? (string) $row['startMinute'] : '';
														$isAmOrPm = isset($row['amOrPm']) ? $row['amOrPm'] : '';
														$subjectId = isset($row['subject']) ? (int) $row['subject'] : 0;
														$yearGroupId = isset($row['yearGroup']) ? (int) $row['yearGroup'] : 0;
														$examineesTableName = isset($row['examineesTableName']) ? $row['examineesTableName'] : '';
														$essayOption = isset($row['essayOption']) ? $row['essayOption'] : 'No';
														if (!preg_match('/^[a-zA-Z0-9_]+$/', $examineesTableName)) {
															continue;
														}
														$hasEssayInDb = false;
														$checkEssayQuery = "SELECT question FROM essay_questions WHERE testId='$testId' LIMIT 1";
														$checkEssayResult = $connection->query($checkEssayQuery);
														if ($checkEssayResult && $checkEssayResult->num_rows > 0) {
															$hasEssayInDb = true;
														}
														$checkIfAddedToTest="SELECT * FROM `" . $examineesTableName . "` WHERE examineeUserId='$studentId'";
														$result1 = $connection->query($checkIfAddedToTest);
														if (!$result1 || $result1->num_rows < 1) {
															continue;
														}
														$row1 = $result1->fetch_assoc();
														$subjectName = '';
														$getSubjectName="SELECT subjectName FROM subjects WHERE subjectId='$subjectId'";
														$result2 = $connection->query($getSubjectName);
														if ($result2 && $row2 = $result2->fetch_assoc()) {
															$subjectName = $row2['subjectName'];
														}
														$yearGroupName = '';
														$getStudentYearGroupName="SELECT yearGroupName FROM yeargroup WHERE yearGroupId='$yearGroupId'";
														$result3 = $connection->query($getStudentYearGroupName);
														if ($result3 && $row3 = $result3->fetch_assoc()) {
															$yearGroupName = $row3['yearGroupName'];
														}
														$essaySubmitted = $hasEssayInDb ? dlhsHasSubmittedEssay($connection, $testId, $studentId) : false;
														$testsForDisplay[] = array(
															'testId' => $testId,
															'testName' => $testName,
															'testNameAndSubject' => $testName . " - (" . htmlspecialchars($subjectName) . ")",
															'testType' => dlhsGetDisplayTestType(isset($row['testType']) ? $row['testType'] : '', isset($row['customTestType']) ? $row['customTestType'] : '', isset($row['mockPaperLabel']) ? $row['mockPaperLabel'] : ''),
															'testDate' => $testDate,
															'duration' => $duration . ' minutes',
															'timeStarted' => isset($row1['timeStartedTest']) ? htmlspecialchars($row1['timeStartedTest']) : '-',
															'yearGroupName' => htmlspecialchars($yearGroupName),
															'showEssayLink' => ($essayOption == "Yes" || $hasEssayInDb),
															'essaySubmitted' => $essaySubmitted
														);
													}
												}
												dlhsSortTestsByTypeAndDate($testsForDisplay);
												$i = 0;
												foreach($testsForDisplay as $testRow)
												{
													?>
													<tr>
														<td align='center'><?php echo $i + 1; ?></td>
														<td><?php echo $testRow['testNameAndSubject']; ?></td>
														<td align='center'><?php echo $testRow['testType']; ?></td>
														<td align='center'><?php echo $testRow['testDate']; ?></td>
														<td align='center'><?php echo $testRow['duration']; ?></td>
														<td align='center'><?php echo $testRow['timeStarted']; ?></td>
														<td align='center'><?php echo $testRow['yearGroupName']; ?></td>
														<td align='center'>
															<?php
															if ($testRow['showEssayLink'] && !$testRow['essaySubmitted']) {
																echo "<a onclick='viewEssay(" . $testRow['testId'] . ")' style='cursor:pointer;'>Continue essay</a> | <span style=\"color:#8a6100; font-weight:600;\">Objective locked</span>";
															} else {
																if($testRow['showEssayLink']){echo "<a onclick='viewEssay(" . $testRow['testId'] . ")' style='cursor:pointer;'>View essay</a> | ";}
																echo "<a onclick=\"openModal(" . $testRow['testId'] . ")\" style='cursor:pointer;'>Continue test</a>";
															}
															?>
														</td>
													</tr>
													<?php	
													$i++;
												}
												
											?>	
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
								<li>Please note that your test continues and your time continues counting down once you click <strong>Continue Test</strong></li>
							</ol>
							
							<div id="setValue"></div>
							
							<!-- Modern Button -->
							<div class="modal-footer-custom">
								<a href="#" id="goToTest" class="modal-btn-start">
									Continue Test <i class="fa fa-play-circle"></i>
								</a>
							</div>
						</div>
					</div>	<!-- End of modal content-->
				</div><!-- End of modal-->
				
				<div id="myModal2" class="modal2"> <!--Start of modal to display essay question-->
					<div class="modal-content">
						<span class="close1">&times;</span>
						
						<!-- Modern Header -->
						<div class="modal-header-custom">
							<h3><i class="fa fa-file-text"></i> Essay Question</h3>
						</div>
						
						<!-- Modern Body -->
						<div class="essay-modal-body">
							<form method="post" id="editTestTimeForm">
								<div class="item form-group">
									<div class="col-md-12">
										<label><span style="color:#003366; font-weight:600; font-size:16px;" id="testName"></span></label><br>
										<div id="theQuestion" style="color:#333; font-size:18px; line-height:1.8; margin-top:15px;"></div>
									</div>
								</div>
							</form>
						</div>
					</div>	<!-- End of modal content-->
				</div><!-- End of modal to display essay question-->
			  
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
     <!-- bootstrap-wysiwyg -->
    <script src="js/jquery.hotkeys.js"></script>
    <script src="js/form-component.js"></script>
    <!-- custome script for all page -->
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
									alert("This test has been ended by invigilator");
								}
								else if (html==3)
								{
									alert("You were not added to take this test");
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
		
		//function declaration to view essay question of test
		function viewEssay(testId)
		{
			// Call startExam.php first to ensure session/test state is set, then open essay
			console.log('viewEssay called for', testId);
			$.ajax({
				url: 'startExam.php',
				type: 'POST',
				data: { testId: testId, section: 'essay' },
				success: function (html) {
					console.log('startExam response for viewEssay', html);
					if (html == 1 || html == 4) {
						var url = 'viewEssay.php?testId=' + encodeURIComponent(testId);
						var features = 'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes';
						var w = window.screen.width;
						var h = window.screen.height;
						features += ',width=' + w + ',height=' + h + ',top=0,left=0';
						var win = window.open(url, '_blank', features);
						if (win) { try { win.focus(); } catch(e) {} }
					} else if (html == 2) {
						alert('This test has been ended by invigilator');
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
					alert('Unable to start/open essay. Please check your network or contact support.');
				}
			});
		}
		var span2 = document.getElementsByClassName("close1")[1];
        var modal2 = document.getElementById("myModal2"); 
        // When the user clicks on <span> (x), close the modal1
        span2.onclick = function() {
			modal2.style.display = "none";
        }
		
		var table = $('#example').DataTable( {
			"paging":   true,
			"ordering": true,
			"info":     true,
					
			dom: 'lBfrtip',
			buttons: [
				'copy', 'csv', 'excel', 'pdf', 'print'
			],
			rowReorder: {
				selector: 'td:nth-child(2)'
			},
			"responsive": true
		});
		$('#testSearchBtn').on('click', function(){
			table.search($('#testSearchInput').val()).draw();
		});
		$('#testSearchInput').on('keypress', function(e){
			if (e.which === 13) {
				e.preventDefault();
				table.search($(this).val()).draw();
			}
		});
		$('#testTypeFilter').on('change', function(){
			var filterValue = this.value ? '^' + $.fn.dataTable.util.escapeRegex(this.value) : '';
			table.column(2).search(filterValue, true, false).draw();
		});
		$('#clearSearchBtn').on('click', function(){
			$('#testSearchInput').val('');
			$('#testTypeFilter').val('');
			table.search('').column(2).search('', true, false).draw();
		});
	</script>
	
	
  </body>
</html>
