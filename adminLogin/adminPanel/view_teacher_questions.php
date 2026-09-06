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

// Get statistics
$statsQuery = "SELECT 
    COUNT(DISTINCT t.testId) as totalTests,
    COUNT(DISTINCT t.staffId) as totalTeachers,
    COUNT(DISTINCT t.subject) as totalSubjects
    FROM tests t";
$statsResult = $connection->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Get teachers for filter
$teachersQuery = "SELECT staffId, CONCAT(firstName, ' ', surname) as fullName FROM stafflogin ORDER BY firstName";
$teachersResult = $connection->query($teachersQuery);

// Get subjects for filter
$subjectsQuery = "SELECT * FROM subjects ORDER BY subjectName";
$subjectsResult = $connection->query($subjectsQuery);

// Get year groups for filter
$yearGroupsQuery = "SELECT * FROM yeargroup ORDER BY yearGroupName";
$yearGroupsResult = $connection->query($yearGroupsQuery);
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

    <title>View Teacher Questions | DLHS Admin</title>

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
	
	<style>
		.stats-card {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			padding: 25px;
			border-radius: 0px;
			margin-bottom: 20px;
			text-align: center;
		}
		.stats-card h3 {
			font-size: 2.5rem;
			margin: 0;
			font-weight: bold;
		}
		.stats-card p {
			margin: 10px 0 0 0;
			opacity: 0.95;
			font-size: 14px;
		}
		.filter-bar {
			background: #f8f9fa;
			padding: 20px;
			margin-bottom: 20px;
			border: 1px solid #ddd;
		}
		.question-card {
			border-left: 4px solid #667eea;
			margin-bottom: 15px;
			padding: 15px;
			background: #fff;
		}
		.correct-answer {
			background: #d4edda;
			padding: 5px 10px;
			border-radius: 3px;
			font-weight: bold;
			color: #155724;
		}
		.option-label {
			display: inline-block;
			width: 30px;
			font-weight: bold;
			color: #667eea;
		}
		.test-header {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			padding: 20px;
			margin-bottom: 20px;
		}
		.loading-overlay {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0, 0, 0, 0.7);
			display: none;
			z-index: 9999;
			justify-content: center;
			align-items: center;
		}
		.loading-overlay.active {
			display: flex;
		}
		.spinner {
			border: 5px solid #f3f3f3;
			border-top: 5px solid #667eea;
			border-radius: 50%;
			width: 60px;
			height: 60px;
			animation: spin 1s linear infinite;
		}
		@keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
		.badge-status-0 {
			background: #ffc107;
			color: #333;
		}
		.badge-status-1 {
			background: #28a745;
		}
		.badge-status-2 {
			background: #6c757d;
		}
	</style>
  </head>
  <body>

	<!-- Loading Overlay -->
	<div class="loading-overlay" id="loadingOverlay">
		<div class="spinner"></div>
	</div>

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
					<h3 class="page-header"><i class="fa fa-question-circle"></i> Teacher Questions Bank</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-question-circle"></i>View Questions</li>
						<li><a href="#" style="color:#0acca2;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a></li>
					</ol>
				</div>
			</div>

			<!-- Statistics Dashboard -->
			<div class="row">
				<div class="col-md-4">
					<div class="stats-card">
						<h3><?php echo $stats['totalTests']; ?></h3>
						<p><i class="fa fa-file-text"></i> Total Tests</p>
					</div>
				</div>
				<div class="col-md-4">
					<div class="stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
						<h3><?php echo $stats['totalTeachers']; ?></h3>
						<p><i class="fa fa-users"></i> Teachers</p>
					</div>
				</div>
				<div class="col-md-4">
					<div class="stats-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
						<h3><?php echo $stats['totalSubjects']; ?></h3>
						<p><i class="fa fa-book"></i> Subjects</p>
					</div>
				</div>
			</div>
              
			<!-- Filter Section -->
			<div class="row">
				<div class="col-lg-12">
					<section class="panel">
						<header class="panel-heading">
							<i class="fa fa-filter"></i> Filter Tests
						</header>
						<div class="panel-body">
							<div class="filter-bar">
								<div class="row">
									<div class="col-md-3">
										<label>Teacher</label>
										<select class="form-control" id="filterTeacher" onchange="loadTests()">
											<option value="">All Teachers</option>
											<?php while($row = $teachersResult->fetch_assoc()): ?>
												<option value="<?php echo $row['staffId']; ?>"><?php echo $row['fullName']; ?></option>
											<?php endwhile; ?>
										</select>
									</div>
									<div class="col-md-3">
										<label>Subject</label>
										<select class="form-control" id="filterSubject" onchange="loadTests()">
											<option value="">All Subjects</option>
											<?php while($row = $subjectsResult->fetch_assoc()): ?>
												<option value="<?php echo $row['subjectId']; ?>"><?php echo $row['subjectName']; ?></option>
											<?php endwhile; ?>
										</select>
									</div>
									<div class="col-md-3">
										<label>Year Group</label>
										<select class="form-control" id="filterYearGroup" onchange="loadTests()">
											<option value="">All Year Groups</option>
											<?php while($row = $yearGroupsResult->fetch_assoc()): ?>
												<option value="<?php echo $row['yearGroupId']; ?>"><?php echo $row['yearGroupName']; ?></option>
											<?php endwhile; ?>
										</select>
									</div>
									<div class="col-md-3">
										<label>Status</label>
										<select class="form-control" id="filterStatus" onchange="loadTests()">
											<option value="">All Statuses</option>
											<option value="0">Not Started</option>
											<option value="1">In Progress</option>
											<option value="2">Completed</option>
										</select>
									</div>
								</div>
								<div class="row" style="margin-top: 15px;">
									<div class="col-md-9">
										<label>Search</label>
										<input type="text" class="form-control" id="searchTest" placeholder="Search test name..." onkeyup="searchTests()">
									</div>
									<div class="col-md-3">
										<label>&nbsp;</label><br>
										<button class="btn btn-default btn-block" onclick="resetFilters()">
											<i class="fa fa-refresh"></i> Reset Filters
										</button>
									</div>
								</div>
							</div>
						</div>
					</section>
				</div>
			</div>

			<!-- Tests List -->
			<div class="row">
				<div class="col-lg-12">
					<section class="panel">
						<header class="panel-heading">
							<i class="fa fa-list"></i> Tests List
							<span class="tools pull-right">
								<a href="javascript:;" onclick="loadTests()"><i class="icon_refresh"></i></a>
							</span>
						</header>
						<div class="panel-body">
							<div class="table-responsive">
								<table id="testsTable" class="table table-striped table-bordered" style="width:100%">
											<thead>
												<tr>
													<th><center>S/NO</center></th>
													<th><center>TEST NAME</center></th>
													<th><center>TEACHER</center></th>
													<th><center>SUBJECT</center></th>
													<th><center>YEAR GROUP</center></th>
													<th><center>TEST DATE</center></th>
													<th><center>STATUS</center></th>
													<th><center>QUESTIONS</center></th>
													<th><center>ACTION</center></th>
												</tr>
											</thead>
											<tbody id="testsTableBody">
												<!-- Data loaded via AJAX -->
											</tbody>
										</table>
							</div>
						</div>
					</section>
				</div>
			</div>

			<!-- Questions Display Section -->
			<div class="row" id="questionsSection" style="display: none;">
				<div class="col-lg-12">
					<section class="panel">
						<header class="panel-heading" id="questionsHeader">
							<i class="fa fa-question-circle"></i> Test Questions
							<span class="tools pull-right">
								<a href="javascript:;" onclick="closeQuestions()"><i class="icon_close"></i></a>
							</span>
						</header>
						<div class="panel-body">
							<div id="questionsContent">
								<!-- Questions loaded here -->
							</div>
						</div>
					</section>
				</div>
			</div>

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
		let testsDataTable = null;
		
		// Load tests on page load
		$(document).ready(function() {
			loadTests();
		});
		
		function showLoading() {
			$('#loadingOverlay').addClass('active');
		}
		
		function hideLoading() {
			$('#loadingOverlay').removeClass('active');
		}
		
		function loadTests() {
			showLoading();
			
			const filterData = {
				teacher: $('#filterTeacher').val(),
				subject: $('#filterSubject').val(),
				yearGroup: $('#filterYearGroup').val(),
				status: $('#filterStatus').val()
			};
			
			$.ajax({
				url: 'get_all_teacher_tests.php',
				type: 'POST',
				data: filterData,
				dataType: 'JSON',
				success: function(response) {
					hideLoading();
					
					// Destroy existing DataTable if it exists
					if (testsDataTable) {
						testsDataTable.destroy();
					}
					
					// Clear tbody
					$('#testsTableBody').html('');
					
					if (response.length > 0) {
						// Populate table
						response.forEach((test, index) => {
							const statusBadge = getStatusBadge(test.status);
							const questionCount = test.questionCount || '?';
							
							const row = `<tr>
								<td align='center'>${index + 1}</td>
								<td>${test.testName}</td>
								<td>${test.teacherName}</td>
								<td>${test.subjectName}</td>
								<td>${test.yearGroupName}</td>
								<td align='center'>${test.testDate}</td>
								<td align='center'>${statusBadge}</td>
								<td align='center'><span class="badge" style="background: #667eea;">${questionCount}</span></td>
								<td align='center'>
									<button class="btn btn-primary btn-sm" onclick="viewQuestions(${test.testId}, '${escapeHtml(test.testName)}', '${escapeHtml(test.teacherName)}')">
										<i class="fa fa-eye"></i> View Questions
									</button>
								</td>
							</tr>`;
							
							$('#testsTableBody').append(row);
						});
						
						// Initialize DataTable
						testsDataTable = $('#testsTable').DataTable({
							"paging": true,
							"ordering": true,
							"info": true,
							"responsive": true,
							"pageLength": 25,
							dom: 'lBfrtip',
							buttons: [
								'copy', 'csv', 'excel', 'pdf', 'print'
							]
						});
					} else {
						$('#testsTableBody').html('<tr><td colspan="9" class="text-center">No tests found</td></tr>');
					}
				},
				error: function() {
					hideLoading();
					alert('Error loading tests. Please try again.');
				}
			});
		}
		
		function getStatusBadge(status) {
			if (status == 0) {
				return '<span class="badge badge-status-0">Not Started</span>';
			} else if (status == 1) {
				return '<span class="badge badge-status-1">In Progress</span>';
			} else if (status == 2) {
				return '<span class="badge badge-status-2">Completed</span>';
			}
			return '<span class="badge">Unknown</span>';
		}
		
		function viewQuestions(testId, testName, teacherName) {
			showLoading();
			
			$.ajax({
				url: 'get_test_questions_admin.php',
				type: 'POST',
				data: { testId: testId },
				dataType: 'JSON',
				success: function(response) {
					hideLoading();
					
					if (response.success) {
						// Update header with question count
						let questionCountText = response.questions.length + ' Objective';
						if (response.testInfo.hasEssay) {
							questionCountText += ' + 1 Essay';
						}
						
						$('#questionsHeader').html(`
							<i class="fa fa-question-circle"></i> ${testName} - ${teacherName}
							<span class="badge pull-right" style="background: #667eea; margin-right: 30px;">${questionCountText}</span>
							<span class="tools pull-right">
								<a href="javascript:;" onclick="closeQuestions()"><i class="icon_close"></i></a>
							</span>
						`);
						
						// Build questions HTML
						let questionsHtml = '';
						
						// Objective Questions Section
						if (response.questions.length > 0) {
							questionsHtml += '<h4 style="color: #667eea; margin-bottom: 20px;"><i class="fa fa-check-circle"></i> Objective Questions</h4>';
							
							response.questions.forEach((q, index) => {
								questionsHtml += `
									<div class="question-card panel panel-default">
										<div class="panel-body">
											<h5><strong>Question ${index + 1} (${q.mark} marks)</strong></h5>
											<p style="font-size: 15px; margin: 15px 0;">${q.question}</p>
											
											<div style="margin: 10px 0;">
												<div style="padding: 5px 0;">
													<span class="option-label">A.</span> ${q.optionA}
													${q.correctOption == 'A' ? '<span class="correct-answer pull-right">✓ Correct Answer</span>' : ''}
												</div>
												<div style="padding: 5px 0;">
													<span class="option-label">B.</span> ${q.optionB}
													${q.correctOption == 'B' ? '<span class="correct-answer pull-right">✓ Correct Answer</span>' : ''}
												</div>
												<div style="padding: 5px 0;">
													<span class="option-label">C.</span> ${q.optionC}
													${q.correctOption == 'C' ? '<span class="correct-answer pull-right">✓ Correct Answer</span>' : ''}
												</div>
												<div style="padding: 5px 0;">
													<span class="option-label">D.</span> ${q.optionD}
													${q.correctOption == 'D' ? '<span class="correct-answer pull-right">✓ Correct Answer</span>' : ''}
												</div>
											</div>
											
											${q.solution ? `
												<div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-left: 3px solid #667eea;">
													<strong>Solution:</strong><br>
													${q.solution}
												</div>
											` : ''}
										</div>
									</div>
								`;
							});
						} else {
							questionsHtml += '<div class="alert alert-info"><i class="fa fa-info-circle"></i> No objective questions found for this test.</div>';
						}
						
						// Essay Question Section
						if (response.essayQuestion && response.essayQuestion.question) {
							questionsHtml += `
								<hr style="margin: 40px 0;">
								<h4 style="color: #667eea; margin-bottom: 20px;"><i class="fa fa-pencil"></i> Essay Question</h4>
								<div class="question-card panel panel-default" style="border-left: 4px solid #f6c23e;">
									<div class="panel-body">
										<div style="background: #fff3cd; padding: 10px; margin-bottom: 15px; border-left: 3px solid #f6c23e;">
											<strong><i class="fa fa-clock-o"></i> Essay Time: ${response.essayQuestion.time || 'Not specified'} minutes</strong>
										</div>
										<h5><strong>Essay Question</strong></h5>
										<p style="font-size: 15px; margin: 15px 0; line-height: 1.6;">${response.essayQuestion.question}</p>
										<div style="margin-top: 15px; padding: 10px; background: #f8f9fa; border-left: 3px solid #667eea;">
											<strong><i class="fa fa-info-circle"></i> Note:</strong> This is an open-ended question requiring detailed written response from students.
										</div>
									</div>
								</div>
							`;
						}
						
						// Add test summary
						if (response.questions.length > 0) {
							const totalMarks = response.questions.reduce((sum, q) => sum + parseFloat(q.mark || 0), 0);
							const summaryHeader = `
								<div class="test-header">
									<div class="row">
										<div class="col-md-3">
											<h5>Test Summary</h5>
											<p><strong>Objective Questions:</strong> ${response.questions.length}</p>
											${response.testInfo.hasEssay ? '<p><strong>Essay:</strong> Yes</p>' : ''}
										</div>
										<div class="col-md-3">
											<h5>&nbsp;</h5>
											<p><strong>Total Marks:</strong> ${totalMarks}</p>
											${response.testInfo.hasEssay ? `<p><strong>Essay Time:</strong> ${response.testInfo.essayTime || 'N/A'} min</p>` : ''}
										</div>
										<div class="col-md-3">
											<h5>&nbsp;</h5>
											<p><strong>Average per Question:</strong> ${(totalMarks / response.questions.length).toFixed(2)}</p>
										</div>
										<div class="col-md-3">
											<h5>&nbsp;</h5>
											<p><strong>Objective Duration:</strong> ${response.testInfo.duration || 'N/A'} min</p>
											${response.testInfo.hasEssay ? `<p><strong>Total Time:</strong> ${parseInt(response.testInfo.duration || 0) + parseInt(response.testInfo.essayTime || 0)} min</p>` : ''}
										</div>
									</div>
								</div>
							`;
							questionsHtml = summaryHeader + questionsHtml;
						} else if (response.essayQuestion && response.essayQuestion.question) {
							// Only essay, no objective questions
							const summaryHeader = `
								<div class="test-header">
									<div class="row">
										<div class="col-md-4">
											<h5>Test Summary</h5>
											<p><strong>Type:</strong> Essay Only</p>
										</div>
										<div class="col-md-4">
											<h5>&nbsp;</h5>
											<p><strong>Essay Time:</strong> ${response.testInfo.essayTime || 'N/A'} minutes</p>
										</div>
										<div class="col-md-4">
											<h5>&nbsp;</h5>
											<p><strong>Total Duration:</strong> ${response.testInfo.essayTime || 'N/A'} minutes</p>
										</div>
									</div>
								</div>
							`;
							questionsHtml = summaryHeader + questionsHtml;
						} else {
							questionsHtml = '<div class="alert alert-info"><i class="fa fa-info-circle"></i> No questions found for this test.</div>';
						}
						
						$('#questionsContent').html(questionsHtml);
						$('#questionsSection').slideDown();
						
						// Scroll to questions
						$('html, body').animate({
							scrollTop: $("#questionsSection").offset().top - 100
						}, 500);
					} else {
						alert('Error: ' + response.message);
					}
				},
				error: function() {
					hideLoading();
					alert('Error loading questions. Please try again.');
				}
			});
		}
		
		function closeQuestions() {
			$('#questionsSection').slideUp();
		}
		
		function searchTests() {
			if (testsDataTable) {
				const searchTerm = $('#searchTest').val();
				testsDataTable.search(searchTerm).draw();
			}
		}
		
		function resetFilters() {
			$('#filterTeacher').val('');
			$('#filterSubject').val('');
			$('#filterYearGroup').val('');
			$('#filterStatus').val('');
			$('#searchTest').val('');
			loadTests();
		}
		
		function escapeHtml(text) {
			const map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return text.replace(/[&<>"']/g, m => map[m]);
		}
	</script>

  </body>
</html>

