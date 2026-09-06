<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['studentLoggedIn']))
	{
		header('location:../index.php');
	}
	include "../../db_connection/dlhs_db_connection.php";
	require_once "../../scripts/test_workflow_helper.php";
	$configuredTestTypes = dlhsGetConfiguredTestTypes($connection);
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

    <title>Examinees' Status | DLHS</title>

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- bootstrap theme -->
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <!--external css-->
    <!-- font icon -->
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- date picker -->
    
    <!-- color picker -->
    
    <!-- Custom styles -->
    <link href="css/style.css" rel="stylesheet">
	
    <link href="css/style-responsive.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
<!-- rowReorder removed: <link rel="stylesheet" type="text/css" href="../../datatables/css/rowReorder.dataTables.min.css"/> -->
	<link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>
	<script src="../../libs/jquery.min.js"></script>
	<script src="result/code/highcharts.js"></script>
	<script src="result/code/highcharts-3d.js"></script>
	<script src="result/code/modules/exporting.js"></script>
	<script src="result/code/modules/export-data.js"></script>
	<script src="result/code/modules/accessibility.js"></script>
<!-- Duplicate jQuery removed: <script src="jQuery3.3.1.js"></script> -->
	
	<script>
		//function to accept only integer minutes.
		function isNumber(evt) {
			var iKeyCode = (evt.which) ? evt.which : evt.keyCode
			if (iKeyCode < 48 || iKeyCode > 57)
				return false;

			return true;
		} 
	</script>
	<style>
		.modal1, .modal2{
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
            width: 90%;
            max-width: 1200px;
            color:black;
            }

            /* The Close Button */
            .close1{
            color: #FFD700;
            float: right;
			padding-right:20px;
			padding-top:20px;
            font-size: 28px;
            font-weight: bold;
            }

            .close1:hover, .close1:focus {
            color: #fff;
            text-decoration: none;
            cursor: pointer;
            background-color: rgba(255, 215, 0, 0.2);
            border-radius: 50%;
            }
			
			#container {
				height: 400px;
			}

			.highcharts-figure,
			.highcharts-data-table table {
				min-width: 310px;
				max-width: 800px;
				margin: 1em auto;
			}

			.highcharts-data-table table {
				font-family: Verdana, sans-serif;
				border-collapse: collapse;
				border: 1px solid #ebebeb;
				margin: 10px auto;
				text-align: center;
				width: 100%;
				max-width: 500px;
			}
			
			/* Modern Result Modal Styles - Using Objectives Test Colors */
			.result-header-modal {
				background: #003366;
				color: #FFD700;
				padding: 1.5rem 1rem;
				margin: -20px -20px 20px -20px;
				border-radius: 7px 7px 0 0;
				box-shadow: 0 2px 8px rgba(0, 51, 102, 0.2);
			}
			.result-header-modal h2 {
				font-weight: 700;
				letter-spacing: 1px;
				margin: 0;
				font-size: 24px;
				color: #FFD700;
			}
			.result-card-modal {
				padding: 1rem;
				background: #fff;
			}
			.review-table {
				width: 100%;
				border-collapse: collapse;
				margin-top: 1rem;
				box-shadow: 0 2px 8px rgba(0, 51, 102, 0.1);
			}
			.review-table thead {
				background: #003366;
				color: #FFD700;
			}
			.review-table thead th {
				padding: 12px 8px;
				text-align: center;
				font-weight: 600;
				border: 1px solid #002244;
				color: #FFD700;
			}
			.review-table tbody td {
				padding: 10px 8px;
				border: 1px solid #e0e0e0;
				vertical-align: top;
			}
			.review-table tbody tr:nth-child(even) {
				background-color: #f8f9fa;
			}
			.review-table tbody tr:hover {
				background-color: rgba(0, 51, 102, 0.05);
			}
			.review-question {
				text-align: left;
				line-height: 1.6;
				color: #333;
			}
			.review-question p {
				margin: 0.3rem 0;
			}
			.review-question strong, .review-question em {
				font-weight: 600;
			}
			.review-option {
				text-align: center;
				font-weight: 500;
				color: #003366;
			}
			.review-correct {
				color: #26c281;
				font-weight: 700;
				font-size: 1.1rem;
			}
			.review-summary {
				margin-top: 2rem;
				padding: 1.5rem;
				background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
				border-radius: 12px;
				text-align: center;
				border: 2px solid #003366;
			}
			.review-summary h4 {
				color: #003366;
				margin-bottom: 1rem;
				font-weight: 700;
			}
			.review-summary-stats {
				display: flex;
				justify-content: space-around;
				flex-wrap: wrap;
				gap: 1rem;
				margin-top: 1rem;
			}
			.review-summary-stats div {
				font-size: 1.1rem;
				color: #333;
			}
			.review-summary-stats strong {
				color: #003366;
			}
			.review-percentage {
				color: #26c281;
				font-size: 1.5rem;
				font-weight: 700;
			}
			.welcome-msg-modal {
				font-size: 1.2rem;
				margin-bottom: 1.5rem;
				color: #333;
			}
			.result-summary-modal {
				display: flex;
				flex-wrap: wrap;
				gap: 1rem;
				justify-content: space-between;
				margin-bottom: 1.5rem;
			}
			.summary-item-modal {
				flex: 1 1 120px;
				background: #f5f7fa;
				border-radius: 12px;
				padding: 1rem;
				text-align: center;
				box-shadow: 0 1px 4px rgba(26,35,126,0.04);
			}
			.icon-modal {
				font-size: 2rem;
				margin-bottom: 0.5rem;
			}
			.label-modal {
				font-size: 0.9rem;
				color: #6c757d;
				margin-bottom: 0.3rem;
			}
			.value-modal {
				font-size: 1.5rem;
				font-weight: 700;
				color: #1a237e;
			}
			.progress-modal {
				height: 1.5rem;
				border-radius: 12px;
				background: #e3e6f0;
				margin-bottom: 1rem;
				overflow: hidden;
			}
			.progress-bar-modal {
				height: 100%;
				background: linear-gradient(90deg, #43cea2 0%, #185a9d 100%);
				transition: width 0.6s ease;
				display: flex;
				align-items: center;
				justify-content: center;
				color: white;
				font-weight: 600;
				font-size: 1rem;
			}
			.start-essay-btn-modal {
				background: #1a237e;
				color: #fff;
				border: none;
				border-radius: 8px;
				padding: 0.7rem 1.5rem;
				font-size: 1.1rem;
				font-weight: 600;
				cursor: pointer;
				transition: background 0.3s;
			}
			.start-essay-btn-modal:hover {
				background: #3949ab;
			}
			@media (max-width: 600px) {
				.result-summary-modal {
					flex-direction: column;
					gap: 0.7rem;
				}
			}
	</style>
  </head>
  <body>

	<!-- container section start -->
	<section id="container" class="">
		<!--Including the header-->
		<?php include 'header.php'; ?>

		<!--Including the sidebar-->
		<?php include 'sideBar_takenTests.php'; ?>

      <!--main content start-->
      <section id="main-content">
          <section class="wrapper">
		  <div class="row">
				<div class="col-lg-12">
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Completed Tests</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Tests</li>
						<a href="#" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-12">
						<section class="panel">
							<header class="panel-heading">
								View Completed Test &nbsp; &nbsp; <div class="message4" style="color:green; font-size:17px;" align="center"></div><div class="message5" style="color:red; font-size:17px;" align="center"></div>
							</header>
							<div class="panel-body">
								<hr>
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
												<th width="18%"><center>TEST NAME</center></th>
												<th width="9%"><center>TYPE</center></th>
												<th width="14%"><center>DATE(yyyy-mm-dd)</center></th>
												<th width="9%"><center>DURATION</center></th>
												<th width="14%"><center>TIME YOU STARTED</center></th>
												<th width="14%"><center>TIME SUBMITTED</center></th>
												<th width="16%"><center>STATUS</center></th>
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
  
  <!-- Review Test Modal -->
  <div id="myModal1" class="modal1">
	<div class="modal-content">
		<span class="close1">&times;</span>
		<div class="result-header-modal">
			<h2><i class="fa fa-eye"></i> Test Review</h2>
		</div>
		<div class="result-card-modal">
			<div id="reviewContent">
				<center><i class="fa fa-spinner fa-spin" style="font-size: 2rem; color: #1a237e;"></i> Loading review...</center>
			</div>
		</div>
	</div>
  </div>
    <!-- javascripts -->
<!-- Duplicate jQuery removed: <script src="js/jquery.js"></script> -->
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
<!-- rowReorder removed: <script src="../../datatables/js/dataTables.rowReorder.min.js"></script> -->
	<script src="../../datatables/js/dataTables.responsive.min.js"></script>
	<script src="../../datatables/js/dataTables.buttons.min.js"></script>
	<script src="../../datatables/js/buttons.flash.min.js"></script>
	<script src="../../datatables/js/jszip.min.js"></script>
	<script src="../../datatables/js/pdfmake.min.js"></script>
	<script src="../../datatables/js/vfs_fonts.js"></script>
	<script src="../../datatables/js/buttons.html5.min.js"></script>
	<script src="../../datatables/js/buttons.print.min.js"></script>
	<script>
		
		function getStatus(testId, testStatus, examineeTestStatus, reviewOption, examineeUserId)
		{
			var statusText = "";
			var reviewButton = "";
			
			if(testStatus == 2 && examineeTestStatus == 2)
			{
				statusText = "Submitted";
				// Show review button if review is enabled
				if(reviewOption == "Yes")
				{
					reviewButton = " - <a onclick='viewTestReview(\""+testId+"\",\""+examineeUserId+"\")' style='cursor:pointer; color:#28a745; font-weight:600;' title='Review your test answers'><i class='fa fa-eye'></i> Review Test</a>";
				}
			}
			else if(testStatus == 2 && examineeTestStatus != 2)
			{
				statusText = "Not submitted";
			}
			else if(testStatus == 1)
			{
				statusText = "In progress";
			}
			else
			{
				statusText = "Not started";
			}
			
			return statusText + reviewButton;
		}
		

		
		function callExamineesStatus()	//Declaration of the data table function
		{		
			//$('#example').DataTable().clear().destroy();
			$.ajax({
					url: 'getTakenTestsStatus.php',
					type: 'POST',
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var testId = response[i].testId;
							var testStatus = response[i].testStatus;
							var examineeUserId = response[i].examineeUserId;
							var testNameAndSubject = response[i].testNameAndSubject;
							var testType = response[i].testTypeLabel || response[i].testType;
							var testDate = response[i].testDate;						
							var duration = response[i].duration;						
							var timeStarted = response[i].timeStarted;						
							var timeSubmitted = response[i].timeSubmitted;											
							var examineeTestStatus = response[i].examineeTestStatus;
							var reviewOption = response[i].reviewOption || "No";											
							var tr_str = "<tr>" +
								"<td><center>" + (i+1) + "</center></td>" +
								"<td><center>" + testNameAndSubject + "</center></td>" +
								"<td><center>" + testType + "</center></td>" +
								"<td><center>" + testDate + "</center></td>" +
								"<td><center>" + duration + "</center></td>" +
								"<td><center>" + timeStarted + "</center></td>" +
								"<td><center>" + timeSubmitted + "</center></td>" +
								"<td><center>" + getStatus(testId, testStatus, examineeTestStatus, reviewOption, examineeUserId) + "</center></td>" +
							"</tr>";

							$("#example tbody").append(tr_str);
						}
						var table = $('#example').DataTable( {
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
						$('#testSearchBtn').off('click').on('click', function(){
							table.search($('#testSearchInput').val()).draw();
						});
						$('#testSearchInput').off('keypress').on('keypress', function(e){
							if (e.which === 13) {
								e.preventDefault();
								table.search($(this).val()).draw();
							}
						});
						$('#testTypeFilter').off('change').on('change', function(){
							var filterValue = this.value ? '^' + $.fn.dataTable.util.escapeRegex(this.value) : '';
							table.column(2).search(filterValue, true, false).draw();
						});
						$('#clearSearchBtn').off('click').on('click', function(){
							$('#testSearchInput').val('');
							$('#testTypeFilter').val('');
							table.search('').column(2).search('', true, false).draw();
						});
					}
			});
		}
		callExamineesStatus();
		
		
		//Returning a font awesome icon depending on the status of the answer
		function getAnswerStatus(markStatus)
		{
			if(markStatus == 1)
			{
				return "<i class='fa fa-check' style='color:green; font-size:18px;'></i>";
			}
			else if(markStatus == 2)
			{
				return "<i class='fa fa-times' style='color:red; font-size:18px;'></i>";
			}
			else if(markStatus == 3)
			{
				return "<i class='fa fa-minus' style='color:#888888; font-size:18px;'></i>";
			}
		}
		
		//Function to get text if an option is selected or not
		function getSelectionText(selectedOption)
		{
			if(selectedOption == 0 || selectedOption == "")
			{
				return "Not answered";
			}
			else
			{
				return selectedOption;
			}
		}
		
		//Function to view test review
		function viewTestReview(testId, examineeUserId)
		{
			var modal1 = document.getElementById("myModal1");
			modal1.style.display = "block";
			
			$('#reviewContent').html('<center><i class="fa fa-spinner fa-spin" style="font-size: 2rem; color: #1a237e;"></i> Loading review...</center>');
			
			$.ajax({
				url: "getTestAnswers.php",
				type: "POST",
				data: {testId: testId, examineeUserId: examineeUserId},
				dataType: 'JSON',
				success: function(response) {
					if(!response || response.length == 0)
					{
						$('#reviewContent').html('<center><font style="font-size:16px; color: red;">No answers found for this test.</font></center>');
						return;
					}
					
					var len = response.length;
					var totalMarkToBeEarned = 0;
					var totalMarkedObtained = 0;
					
					// Helper function to extract option text from HTML
					function extractOptionText(optionHtml) {
						if(!optionHtml) return '';
						// Create a temporary div to parse HTML
						var div = document.createElement('div');
						div.innerHTML = optionHtml;
						var text = div.textContent || div.innerText || '';
						text = text.trim();
						// If it's just a letter (A, B, C, D, E), return just the letter
						if(text.length <= 2 && /^[A-E]$/i.test(text)) {
							return text;
						}
						// Otherwise return the HTML for proper rendering
						return optionHtml;
					}
					
					var reviewHtml = '<div style="margin-bottom: 1.5rem;">';
					reviewHtml += '<h4 style="color: #003366; margin-bottom: 1rem; font-weight: 700;"><i class="fa fa-list"></i> Question Review</h4>';
					reviewHtml += '<div style="overflow-x: auto;">';
					reviewHtml += '<table class="review-table">';
					reviewHtml += '<thead>';
					reviewHtml += '<tr>';
					reviewHtml += '<th width="4%">S/NO</th>';
					reviewHtml += '<th width="35%">QUESTION</th>';
					reviewHtml += '<th width="8%">OPTION A</th>';
					reviewHtml += '<th width="8%">OPTION B</th>';
					reviewHtml += '<th width="8%">OPTION C</th>';
					reviewHtml += '<th width="8%">OPTION D</th>';
					reviewHtml += '<th width="7%">CORRECT</th>';
					reviewHtml += '<th width="7%">YOUR ANSWER</th>';
					reviewHtml += '<th width="5%">STATUS</th>';
					reviewHtml += '<th width="5%">MARK</th>';
					reviewHtml += '</tr>';
					reviewHtml += '</thead>';
					reviewHtml += '<tbody>';
					
					for(var i=0; i<len; i++){
						var questionId = response[i].questionId;
						var question = response[i].question || ''; // Already decoded from PHP
						var optionA = extractOptionText(response[i].optionA || '');
						var optionB = extractOptionText(response[i].optionB || '');
						var optionC = extractOptionText(response[i].optionC || '');
						var optionD = extractOptionText(response[i].optionD || '');
						var optionE = response[i].optionE ? extractOptionText(response[i].optionE) : '';
						var correctOption = response[i].correctOption || '';
						var selectedOption = response[i].selectedOption || '';
						var markStatus = response[i].markStatus || 3;
						var mark = +response[i].mark || 0;
						var markObtained = +response[i].markObtained || 0;
						
						totalMarkToBeEarned = totalMarkToBeEarned + mark;
						totalMarkedObtained = totalMarkedObtained + markObtained;
						
						// Use theme colors for row backgrounds
						var rowColor = markStatus == 1 ? '#d4edda' : (markStatus == 2 ? '#f8d7da' : '#fff3cd');
						var rowBorder = markStatus == 1 ? '2px solid #26c281' : (markStatus == 2 ? '2px solid #d95043' : '2px solid #fabb3d');
						
						reviewHtml += '<tr style="background-color: ' + rowColor + '; border-left: ' + rowBorder + ';">';
						reviewHtml += '<td style="text-align: center; font-weight: 600; color: #003366;">' + (i+1) + '</td>';
						reviewHtml += '<td class="review-question">' + question + '</td>';
						reviewHtml += '<td class="review-option">' + optionA + '</td>';
						reviewHtml += '<td class="review-option">' + optionB + '</td>';
						reviewHtml += '<td class="review-option">' + optionC + '</td>';
						reviewHtml += '<td class="review-option">' + optionD + '</td>';
						reviewHtml += '<td style="text-align: center;"><span class="review-correct">' + correctOption + '</span></td>';
						reviewHtml += '<td style="text-align: center; font-weight: 600; color: #003366;">' + getSelectionText(selectedOption) + '</td>';
						reviewHtml += '<td style="text-align: center;">' + getAnswerStatus(markStatus) + '</td>';
						reviewHtml += '<td style="text-align: center; font-weight: 600; color: #003366;">' + markObtained + ' / ' + mark + '</td>';
						reviewHtml += '</tr>';
					}
					
					reviewHtml += '</tbody>';
					reviewHtml += '</table>';
					reviewHtml += '</div>';
					
					var percentageScore = Math.round((totalMarkedObtained/totalMarkToBeEarned) * 100);
					reviewHtml += '<div class="review-summary">';
					reviewHtml += '<h4>Summary</h4>';
					reviewHtml += '<div class="review-summary-stats">';
					reviewHtml += '<div><strong>Total Marks:</strong> ' + totalMarkedObtained + ' / ' + totalMarkToBeEarned + '</div>';
					reviewHtml += '<div><strong>Percentage:</strong> <span class="review-percentage">' + percentageScore + '%</span></div>';
					reviewHtml += '</div>';
					reviewHtml += '</div>';
					reviewHtml += '</div>';
					
					$('#reviewContent').html(reviewHtml);
				},
				error: function(xhr, status, error) {
					console.error('Error loading test review:', error);
					$('#reviewContent').html('<center><font style="font-size:16px; color: red;">Error loading review. Please try again.</font></center>');
				}
			});
		}
		
		// Close modal when clicking on X
		var span1 = document.getElementsByClassName("close1")[0];
		span1.onclick = function() {
			var modal1 = document.getElementById("myModal1");
			modal1.style.display = "none";
		}
		
		// Close modal when clicking outside
		window.onclick = function(event) {
			var modal1 = document.getElementById("myModal1");
			if (event.target == modal1) {
				modal1.style.display = "none";
			}
		}

	</script>
  </body>
</html>
