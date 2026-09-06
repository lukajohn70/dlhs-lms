<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn']))
	{
		header('location:../index.php');
	}
	include "../../db_connection/dlhs_db_connection.php";
	require_once "../../scripts/test_workflow_helper.php";
	$configuredTestTypes = dlhsGetConfiguredTestTypes($connection);
	$academicSessions = dlhsGetAcademicSessions($connection, false);
	$currentAcademicSession = dlhsGetCurrentAcademicSessionName($connection);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">

    <title>Add Test | DLHS</title>

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
	<script src="../../libs/jquery.min.js"></script>
<!-- Duplicate jQuery removed: <script src="jQuery3.3.1.js"></script> -->
	<script>
		//function to accept only integer minutes.
		function isNumber(evt) {
			var iKeyCode = (evt.which) ? evt.which : evt.keyCode
			if (iKeyCode < 48 || iKeyCode > 57)
				return false;
			return true;
		}


		function dlhsToggleCreateForm() {
			var formCol = document.getElementById('createFormColumn');
			var tableCol = document.getElementById('tableColumn');
			var btn = document.getElementById('toggleFormBtn');
			if (!formCol || !tableCol || !btn) return;
			
			if (formCol.style.display === 'none') {
				formCol.style.display = 'block';
				tableCol.className = 'col-lg-9';
				btn.innerHTML = '<i class="fa fa-minus"></i> Hide Form';
				btn.className = 'btn btn-danger btn-sm';
			} else {
				formCol.style.display = 'none';
				tableCol.className = 'col-lg-12';
				btn.innerHTML = '<i class="fa fa-plus"></i> Create New Test';
				btn.className = 'btn btn-success btn-sm';
			}
			
			if ($.fn.dataTable) {
				$('#example').DataTable().columns.adjust().responsive.recalc();
			}
		}
	</script>
	<style>
		@keyframes dlhs-pulse {
			0%, 100% { opacity: 1; transform: scale(1); }
			50% { opacity: 0.82; transform: scale(1.03); }
		}
		@keyframes dlhs-slide-in {
			from { opacity: 0; transform: translateY(-12px); }
			to   { opacity: 1; transform: translateY(0); }
		}
		.modal1, .modal2 {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1200; /* Sit on top */
            padding-top: 40px; /* Location of the box */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            }
			
			.modal-content {
            border-radius:16px;
            background-color: #fefefe;
            margin: auto;
            padding: 0;
            border: 1px solid rgba(0,0,0,0.08);
            width: 78%;
			max-width: 1080px;
			box-shadow: 0 20px 45px rgba(0,0,0,0.18);
            color:black;
            }

            /* The Close Button */
            .close1{
            color: #ffffff;
            position: absolute;
			right: 22px;
			top: 18px;
            font-size: 28px;
            font-weight: bold;
			z-index: 4;
            }

            .close1:hover, .close1:focus {
            color: red;
            text-decoration: none;
            cursor: pointer;
            }

			.custom-test-type-group {
				display: none;
				padding: 12px;
				border-radius: 8px;
				background: #fff7e6;
				border: 1px solid #f4d28f;
				margin-top: 10px;
			}

			.custom-test-type-group small {
				display: block;
				margin-top: 6px;
				color: #8a5a00;
			}

			.edit-test-shell {
				background: linear-gradient(180deg, #f8fbff 0%, #eef5fb 100%);
				border-radius: 16px;
				overflow: hidden;
			}

			.edit-test-header {
				padding: 24px 28px;
				background: linear-gradient(135deg, #003366 0%, #0a5a8c 100%);
				color: #fff;
			}

			.edit-test-header h3 {
				margin: 0;
				font-size: 24px;
				font-weight: 700;
				letter-spacing: 0.02em;
			}

			.edit-test-header p {
				margin: 8px 0 0;
				color: rgba(255,255,255,0.82);
				font-size: 13px;
			}

			.edit-test-body {
				padding: 26px 28px 30px;
			}

			.edit-section {
				background: #ffffff;
				border: 1px solid rgba(0, 51, 102, 0.08);
				border-radius: 14px;
				padding: 20px 22px 8px;
				margin-bottom: 18px;
				box-shadow: 0 6px 20px rgba(0, 51, 102, 0.05);
			}

			.edit-section-title {
				margin: 0 0 16px;
				font-size: 14px;
				font-weight: 700;
				letter-spacing: 0.08em;
				text-transform: uppercase;
				color: #003366;
			}

			.edit-form-grid {
				display: grid;
				grid-template-columns: repeat(3, minmax(0, 1fr));
				gap: 18px 20px;
			}

			.edit-form-grid .field-span-2 {
				grid-column: span 2;
			}

			.edit-form-grid .field-span-3 {
				grid-column: span 3;
			}

			.edit-field label {
				display: block;
				margin-bottom: 8px;
				color: #193b5a;
				font-weight: 600;
			}

			.edit-field .form-control {
				min-height: 42px;
				border-radius: 10px;
				border: 1px solid #cdd9e5;
				box-shadow: none;
			}

			.edit-field .form-control:focus {
				border-color: #0a5a8c;
				box-shadow: 0 0 0 3px rgba(10, 90, 140, 0.12);
			}

			.edit-highlight {
				background: linear-gradient(180deg, #fffaf0 0%, #fff5df 100%);
				border-color: #f0d08d;
			}

			.edit-actions {
				display: flex;
				align-items: center;
				gap: 12px;
				flex-wrap: wrap;
				padding-top: 6px;
			}

			.edit-actions .btn {
				min-width: 130px;
				border-radius: 999px;
				padding: 10px 18px;
				font-weight: 600;
			}

			@media (max-width: 991px) {
				.modal-content {
					width: 94%;
				}
				.edit-form-grid {
					grid-template-columns: repeat(2, minmax(0, 1fr));
				}
				.edit-form-grid .field-span-3 {
					grid-column: span 2;
				}
			}

			@media (max-width: 767px) {
				.modal1, .modal2 {
					padding-top: 16px;
				}
				.edit-test-body {
					padding: 18px;
				}
				.edit-form-grid {
					grid-template-columns: minmax(0, 1fr);
				}
				.edit-form-grid .field-span-2,
				.edit-form-grid .field-span-3 {
					grid-column: span 1;
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
		<?php include 'sideBar.php'; ?>

      <!--main content start-->
      <section id="main-content">
          <section class="wrapper">
		  <div class="row">
				<div class="col-lg-12">
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Add Test</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Tests</li>
						<a href="#" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-3" id="createFormColumn" style="display: none;">
						<section class="panel">
							<header class="panel-heading">
								Please fill required field in the form
							</header>
							<div class="panel-body">
								<form>
									<div class="form-group">
										<label>Generated test name</label>
										<input type="text" name="testNamePreview" id="testNamePreview" class="form-control" placeholder="Session + Year group + Subject code + Test type" readonly >
										<input type="hidden" name="testName" id="testNameTyping" value="">
									</div>
									<div class="form-group">
										<label>Academic session</label>
										<select class="form-control m-bot15" name="academicSession" id="academicSession" required >
											<option value="">... Select Session ...</option>
											<?php foreach ($academicSessions as $academicSessionRow) { ?>
											<?php $sessionName = isset($academicSessionRow['sessionName']) ? $academicSessionRow['sessionName'] : ''; ?>
											<option value="<?php echo htmlspecialchars($sessionName); ?>" <?php echo ($sessionName === $currentAcademicSession) ? 'selected' : ''; ?>>
												<?php echo htmlspecialchars($sessionName); ?><?php echo !empty($academicSessionRow['isCurrentSession']) ? ' (Current)' : ''; ?>
											</option>
											<?php } ?>
										</select>
									</div>
									<div class="form-group">
										<label>Subject</label>
										<select class="form-control m-bot15" name="subject_composite" id="subject_composite" required onchange="dlhsHandleSubjectChange('create')">
											<option value="">... Select Subject ...</option>
											<?php
											if (isset($_SESSION['staffLoggedIn'])) {
												$staffId = $_SESSION['staffId'];
												$subjectQuery = "SELECT DISTINCT s.subjectId, s.subjectName, yg.yearGroupId, yg.yearGroupName 
																 FROM subjects s 
																 INNER JOIN subject_teacher_assignment sta ON s.subjectId = sta.subjectId 
																 INNER JOIN classes c ON sta.classId = c.classId 
																 INNER JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId 
																 WHERE sta.teacherId = '$staffId' 
																 ORDER BY s.subjectName ASC, yg.yearGroupName ASC";
												$res = $connection->query($subjectQuery);
												while($row = $res->fetch_assoc()){
													$val = $row['subjectId'] . '|' . $row['yearGroupId'];
													$label = $row['subjectName'] . ' ' . $row['yearGroupName'];
												?>
												<option value="<?php echo $val; ?>"><?php echo htmlspecialchars($label); ?></option>
												<?php 
												}
											}
											?>
										</select>
										<input type="hidden" name="subject" id="subject">
										<input type="hidden" name="yearGroup" id="yearGroup">
									</div>
									<div class="form-group">
										<label>Class (Arm)</label>
										<select class="form-control m-bot15" name="classId" id="classId" required onchange="updateGeneratedCreateName()">
											<option value="">... Select Class ...</option>
										</select>
									</div>
									<hr>
									<div class="form-group">
										<label>Type of test</label>
										<select class="form-control m-bot15" name="testType" id="testType" required >
											<option value="">... Select Test Type ...</option>
											<?php foreach ($configuredTestTypes as $configuredTestType) { ?>
											<option value="<?php echo htmlspecialchars($configuredTestType); ?>"><?php echo htmlspecialchars($configuredTestType); ?></option>
											<?php } ?>
										</select>
									</div>
									<div class="form-group" id="customTestTypeGroup" style="display:none;">
										<label>Specify Test Type</label>
										<input type="text" name="customTestType" id="customTestType" class="form-control" placeholder="e.g., MOCK EXAMS">
									</div>
									<div class="form-group" id="mockPaperGroup" style="display:none;">
										<label>Paper (For Mock)</label>
										<input type="text" name="mockPaperLabel" id="mockPaperLabel" class="form-control" placeholder="e.g., PAPER 1">
									</div>
									<hr>
									<div class="form-group">
										<label>Date of test</label>
										<input type="date" name="testDate" id="testDate" class="form-control" required >
									</div>
									<div class="form-group">
										<label>Duration (In Minutes)</label>
										<input type="text" name="testDuration" onkeypress="javascript:return isNumber(event)" class="form-control" placeholder="e.g., 50" required >
									</div>
									<input type="hidden" name="startHour" id="startHour" value="8">
									<input type="hidden" name="startMinute" id="startMinute" value="0">
									<input type="hidden" name="amOrPm" id="amOrPm" value="AM">
									<div class="form-group">
										<label>Allow Review after test?</label>
										<select class="form-control m-bot15" name="reviewOption" id="reviewOption" required >
											<option value="">... Review option after test ...</option>
											<option value="Yes">Yes</option>
											<option value="No">No</option>
										</select>
									</div>
									<div class="form-group">
										<label>Does Test have essay?</label>
										<select class="form-control m-bot15" name="essayOption" id="essayOption" required >
											<option value="">... Select option ...</option>
											<option value="Yes">Yes</option>
											<option value="No">No</option>
										</select>
									</div>
									<div class="form-group" id="addEssayTime"></div>
									<div class="form-group">
										<label>
											Randomize Questions? 
											<i class="fa fa-info-circle" data-toggle="tooltip" title="Questions will appear in different order for each student (Anti-cheating)"></i>
										</label>
										<select class="form-control m-bot15" name="randomizeQuestions" id="randomizeQuestions" required >
											<option value="">... Select option ...</option>
											<option value="Yes">Yes - Randomize</option>
											<option value="No">No - Keep Original Order</option>
										</select>
										<small class="text-muted">
											<i class="fa fa-shield"></i> Randomization helps prevent cheating by showing questions in different order to each student.
										</small>
									</div>
									<div class="form-group">
										<label>
											Randomize Answer Options (A, B, C, D, E)? 
											<i class="fa fa-info-circle" data-toggle="tooltip" title="Answer choices will appear in different order for each student (Enhanced Anti-cheating)"></i>
										</label>
										<select class="form-control m-bot15" name="randomizeOptions" id="randomizeOptions" required >
											<option value="">... Select option ...</option>
											<option value="Yes">Yes - Randomize Options</option>
											<option value="No">No - Keep Original Order</option>
										</select>
										<small class="text-muted">
											<i class="fa fa-shield"></i> <strong>Maximum Security:</strong> Each student sees different option arrangements (e.g., correct answer could be A, B, or C for different students).
										</small>
									</div>
									<button type="button" class="btn btn-primary" id="submit" onclick="addTest()"><i class="fa fa-sign-in"></i> Submit</button>
									<div class="message2" id="message2" style="color:red;" align="center"></div><div class="message1" style="color:green; font-size:17px;" align="center"></div>
								</form>
							</div>
						</section>
					</div>
					<div class="col-lg-12" id="tableColumn">
						<section class="panel">
							<header class="panel-heading" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
								<span>View | Edit | Delete Test &nbsp; &nbsp; <span class="message4" style="color:green; font-size:17px;"></span><span class="message5" style="color:red; font-size:17px;"></span></span>
								<button class="btn btn-success btn-sm" id="toggleFormBtn" onclick="dlhsToggleCreateForm()" style="font-weight: 700; border-radius: 6px; padding: 6px 12px;"><i class="fa fa-plus"></i> Create New Test</button>
							</header>
							<div class="panel-body">
								<div class="form-group" style="max-width:320px;">
									<label>Filter by test type</label>
									<select class="form-control" id="testTypeFilter">
										<option value="">... All Test Types ...</option>
										<?php foreach ($configuredTestTypes as $configuredTestType) { ?>
										<option value="<?php echo htmlspecialchars($configuredTestType); ?>"><?php echo htmlspecialchars($configuredTestType); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="table-responsive">
									<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
										<thead>
											<tr style="font-size:13px;">
												<th width="1%"><center>S/NO</center></th>
												<th width="21%"><center>TEST NAME</center></th>
												<th width="8%"><center>TYPE</center></th>
												<th width="12%"><center>DATE(yyyy-mm-dd)</center></th>
												<th width="7%"><center>DURATION</center></th>
												<th width="7%"><center>YR GRP</center></th>
												<th width="9%"><center>SESSION</center></th>
												<th width="5%"><center>YEAR</center></th>
												<th width="11%"><center>REVIEW</center></th>
												<th width="11%"><center>SETUP</center></th>
												<th width="16%"><center>STATUS</center></th>
												<th width="12%"><center>ACTION</center></th>
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
	  
	<div id="myModal1" class="modal1"> <!--Start of Edit modal-->
		<div class="modal-content">
			<span class="close1">&times;</span>
			<div class="edit-test-shell">
				<div class="edit-test-header">
					<h3>Edit Test</h3>
					<p>Update the selected test details below. Conditional fields such as mock paper and custom test type will appear automatically when needed.</p>
				</div>
				<div class="edit-test-body">
					<form method="post" id="editTestForm">
						<input type="hidden" name="testId" required="required" id="testId">
						<input type="hidden" name="startHour1" id="startHour1" value="8">
						<input type="hidden" name="startMinute1" id="startMinute1" value="0">
						<input type="hidden" name="amOrPm1" id="amOrPm1" value="AM">
						<input type="hidden" name="reviewOption1" id="reviewOption1">

						<div class="edit-section">
							<div class="edit-section-title">Test Identity</div>
							<div class="edit-form-grid">
								<div class="edit-field field-span-3">
									<label><span>Generated test name: </span> <span class="required" style="color:red;">*</span></label>
									<input type="text" name="testName1" required="required" maxlength="60" id="testName1" class="form-control" readonly>
								</div>
								<div class="edit-field">
									<label><span>Test date: </span> <span class="required" style="color:red;">*</span></label>
									<input type="date" name="testDate1" required="required" id="testDate1" class="form-control">
								</div>
								<div class="edit-field">
									<label><span>Duration in minutes: </span> <span class="required" style="color:red;">*</span></label>
									<input type="text" name="testDuration1" required="required" onkeypress="javascript:return isNumber(event)" id="testDuration1" class="form-control" placeholder="e.g. 50">
								</div>
								<div class="edit-field">
									<label><span>Academic session: </span> <span class="required" style="color:red;">*</span></label>
									<select class="form-control" name="academicSession1" id="academicSession1" required>
										<option value="">... Select Session ...</option>
										<?php foreach ($academicSessions as $academicSessionRow) { ?>
										<?php $sessionName = isset($academicSessionRow['sessionName']) ? $academicSessionRow['sessionName'] : ''; ?>
										<option value="<?php echo htmlspecialchars($sessionName); ?>">
											<?php echo htmlspecialchars($sessionName); ?><?php echo !empty($academicSessionRow['isCurrentSession']) ? ' (Current)' : ''; ?>
										</option>
										<?php } ?>
									</select>
								</div>
							</div>
						</div>

						<div class="edit-section">
							<div class="edit-section-title">Academic Setup</div>
							<div class="edit-form-grid">
								<div class="edit-field">
									<label><span>Subject: </span> <span class="required" style="color:red;">*</span></label>
									<select class="form-control" name="subject_composite1" id="subject_composite1" required onchange="dlhsHandleSubjectChange('edit')">
										<option value="">... Select Subject ...</option>
										<?php
										if (isset($_SESSION['staffLoggedIn'])) {
											$staffId = $_SESSION['staffId'];
											$subjectQuery = "SELECT DISTINCT s.subjectId, s.subjectName, yg.yearGroupId, yg.yearGroupName 
															 FROM subjects s 
															 INNER JOIN subject_teacher_assignment sta ON s.subjectId = sta.subjectId 
															 INNER JOIN classes c ON sta.classId = c.classId 
															 INNER JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId 
															 WHERE sta.teacherId = '$staffId' 
															 ORDER BY s.subjectName ASC, yg.yearGroupName ASC";
											$res = $connection->query($subjectQuery);
											while($row = $res->fetch_assoc()){
												$val = $row['subjectId'] . '|' . $row['yearGroupId'];
												$label = $row['subjectName'] . ' ' . $row['yearGroupName'];
											?>
											<option value="<?php echo $val; ?>"><?php echo htmlspecialchars($label); ?></option>
											<?php }
										} else {
											$subjects = "SELECT s.*, yg.yearGroupName, yg.yearGroupId FROM subjects s CROSS JOIN yeargroup yg ORDER BY s.subjectName ASC";
											$res = $connection->query($subjects);
											while($row = $res->fetch_assoc()){
												$val = $row['subjectId'] . '|' . $row['yearGroupId'];
												$label = $row['subjectName'] . ' ' . $row['yearGroupName'];
											?>
											<option value="<?php echo $val; ?>"><?php echo htmlspecialchars($label); ?></option>
											<?php }
										}
										?>
									</select>
									<input type="hidden" name="subjectId1" id="subjectId1">
									<input type="hidden" name="yearGroup1" id="yearGroup1">
								</div>
								<div class="edit-field">
									<label><span>Class (Arm): </span> <span class="required" style="color:red;">*</span></label>
									<select class="form-control" name="classId1" id="classId1" required onchange="updateGeneratedEditName()">
										<option value="">... Select Class ...</option>
									</select>
								</div>
							</div>
						</div>

						<div class="edit-section">
							<div class="edit-section-title">Test Type And Delivery</div>
							<div class="edit-form-grid">
								<div class="edit-field">
									<label><span>Type of test: </span> <span class="required" style="color:red;">*</span></label>
									<select class="form-control" name="testType1" id="testType1" required>
										<option value="">... Select Test Type ...</option>
										<?php foreach ($configuredTestTypes as $configuredTestType) { ?>
										<option value="<?php echo htmlspecialchars($configuredTestType); ?>"><?php echo htmlspecialchars($configuredTestType); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="edit-field edit-highlight" id="customTestTypeGroup1">
									<label><span>Enter kind of test: </span> <span class="required" style="color:red;">*</span></label>
									<input type="text" class="form-control" name="customTestType1" id="customTestType1" placeholder="e.g. MIDTERM, PRACTICAL, QUIZ">
									<small>This custom label replaces OTHER in the generated test name.</small>
								</div>
								<div class="edit-field edit-highlight" id="mockPaperGroup1">
									<label><span>Mock paper: </span> <span class="required" style="color:red;">*</span></label>
									<select class="form-control" name="mockPaperLabel1" id="mockPaperLabel1">
										<option value="">... Select Mock Paper ...</option>
										<option value="PAPER 1">PAPER 1</option>
										<option value="PAPER 2">PAPER 2</option>
										<option value="PAPER 3">PAPER 3</option>
									</select>
									<small>Select the paper for this mock exam.</small>
								</div>
								<div class="edit-field">
									<label><span>Does test have essay? </span> <span class="required" style="color:red;">*</span></label>
									<select class="form-control" name="essayOption1" id="essayOption1" required>
										<option value="">... Select Essay option ...</option>
										<option value="Yes">Yes</option>
										<option value="No">No</option>
									</select>
								</div>
								<div class="edit-field" id="addEssayTime1"></div>
								<div class="edit-field">
									<label><span>Randomize Questions? </span> <span class="required" style="color:red;">*</span></label>
									<select class="form-control" name="randomizeQuestions1" id="randomizeQuestions1" required>
										<option value="">... Select option ...</option>
										<option value="Yes">Yes - Randomize</option>
										<option value="No">No - Keep Original Order</option>
									</select>
								</div>
								<div class="edit-field">
									<label><span>Randomize Options? </span> <span class="required" style="color:red;">*</span></label>
									<select class="form-control" name="randomizeOptions1" id="randomizeOptions1" required>
										<option value="">... Select option ...</option>
										<option value="Yes">Yes - Randomize Options</option>
										<option value="No">No - Keep Original Order</option>
									</select>
								</div>
							</div>
						</div>

						<div class="edit-section">
							<div class="edit-actions">
								<button class="btn btn-default" type="reset">Reset</button>
								<button type="button" class="btn btn-success" onclick="editTestDetails()">Update</button>
							</div>
							<div class="message3" id="message3" style="color:red; margin-top:12px;" align="center"></div>
						</div>
					</form>
				</div>
			</div>
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
							<label><span style="color:#000000;"><b>Test details:</b> </span> <span id="testDetails" style="color:#000000;"></span></label><br>
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
		
		//Function declaration to turn review option on or off
		function getReviewFlagging(testId, theReviewOption)
		{
			if(theReviewOption == "Yes")
			{
				var newReviewOption = "No";
				return " - <a onclick='flagReviewOption(\""+testId+"\",\""+newReviewOption+"\")' style='cursor:pointer' title='Click to turn on option that allows students to review test when ended by invigilator'>Turn off</a>";
			}
			else if(theReviewOption == "No")
			{
				var newReviewOption = "Yes";
				return " - <a onclick='flagReviewOption(\""+testId+"\",\""+newReviewOption+"\")' style='cursor:pointer' title='Click to turn off option that allows students to review test when ended by invigilator'>Turn on</a>";
			}
		}
		
		//Function declaration to turn on or turn off the review option
		function flagReviewOption(testId, newReviewOption)
		{	
			$('.message4').html("");
			$('.message5').html("");
			
			var displayText = "";
			if(newReviewOption == "Yes")
			{
				displayText = "on";
			}
			else if(newReviewOption == "No")
			{
				displayText = "off";
			}
			dlhsConfirm("Are you sure you wish to turn "+ displayText + " student review for this test?", function() {
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
						}
						else if (html==2)	//If deletion is unsuccessful	
						{                              
							 $('.message5').html("");
							 $('.message4').html('<i class="fa fa-times"></i> Could turn ' + newReviewOption + ' review option. Please try again.').fadeIn('slow');
						}
					}				
				});
			});
		}

		function getSetupStatusText(setupStatus, studentCount, questionCount, testId)
		{
			// Build a clear visual CTA for each step of the setup workflow
			var hasStudents = studentCount > 0;
			var hasQuestions = questionCount > 0;

			var studentsBtn = hasStudents
				? '<a href="addStudentsToTestForm.php?testId=' + testId + '" title="' + studentCount + ' students added — click to manage" style="display:inline-flex;align-items:center;gap:4px;margin:2px;padding:4px 10px;background:linear-gradient(135deg,#27ae60,#2ecc71);color:#fff;border-radius:20px;font-size:11px;font-weight:700;text-decoration:none;"><i class="fa fa-users"></i> ' + studentCount + ' students</a>'
				: '<a href="addStudentsToTestForm.php?testId=' + testId + '" title="No students added yet" style="display:inline-flex;align-items:center;gap:4px;margin:2px;padding:4px 10px;background:linear-gradient(135deg,#e74c3c,#c0392b);color:#fff;border-radius:20px;font-size:11px;font-weight:700;text-decoration:none;animation:dlhs-pulse 1.6s infinite;"><i class="fa fa-user-plus"></i> Add Students</a>';

			var questionsBtn = hasQuestions
				? '<a href="addQuestionForm.php?testId=' + testId + '" title="' + questionCount + ' question sets — click to manage" style="display:inline-flex;align-items:center;gap:4px;margin:2px;padding:4px 10px;background:linear-gradient(135deg,#2980b9,#3498db);color:#fff;border-radius:20px;font-size:11px;font-weight:700;text-decoration:none;"><i class="fa fa-question-circle"></i> ' + questionCount + ' questions</a>'
				: '<a href="addQuestionForm.php?testId=' + testId + '" title="No questions yet — click to add" style="display:inline-flex;align-items:center;gap:4px;margin:2px;padding:4px 10px;background:linear-gradient(135deg,#f39c12,#e67e22);color:#fff;border-radius:20px;font-size:11px;font-weight:700;text-decoration:none;animation:dlhs-pulse 1.6s infinite;"><i class="fa fa-pencil-square-o"></i> Add Questions →</a>';

			return '<div style="display:flex;flex-direction:column;align-items:center;gap:3px;">' + studentsBtn + questionsBtn + '</div>';
		}

		function buildGeneratedTestName(sessionText, yearGroupText, subjectText, testTypeText)
		{
			var normalizedSession = $.trim(sessionText || '');
			var normalizedYearGroup = $.trim(yearGroupText || '').toUpperCase();
			var subjectCode = $.trim(subjectText || '').toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 3);
			if (subjectCode === '') {
				subjectCode = 'SUB';
			}
			var normalizedTestType = $.trim(testTypeText || '').toUpperCase();
			return $.trim([normalizedSession, normalizedYearGroup, subjectCode, normalizedTestType].join(' '));
		}

		function toggleCustomTestTypeField(mode)
		{
			var suffix = mode === 'edit' ? '1' : '';
			var typeSelector = '#testType' + suffix;
			var fieldSelector = '#customTestTypeGroup' + suffix;
			var mockSelector = '#mockPaperGroup' + suffix;
			var inputSelector = '#customTestType' + suffix;
			var mockInputSelector = '#mockPaperLabel' + suffix;
			var selectedType = $(typeSelector).val();

			if (selectedType === 'OTHER')
			{
				$(fieldSelector).stop(true, true).slideDown(150);
			}
			else
			{
				$(fieldSelector).hide();
				$(inputSelector).val('');
			}

			if (selectedType === 'MOCK')
			{
				$(mockSelector).stop(true, true).slideDown(150);
			}
			else
			{
				$(mockSelector).hide();
				$(mockInputSelector).val('');
			}
		}

		function getSelectedTestTypeLabel(mode)
		{
			var suffix = mode === 'edit' ? '1' : '';
			var selectedType = $('#testType' + suffix).val() || '';
			var customType = $.trim($('#customTestType' + suffix).val() || '');

			if (selectedType === 'OTHER' && customType !== '')
			{
				return customType;
			}

			if (selectedType === 'MOCK')
			{
				var mockPaper = $.trim($('#mockPaperLabel' + suffix).val() || '');
				return $.trim([selectedType, mockPaper].join(' '));
			}

			return selectedType;
		}

		function updateGeneratedCreateName()
		{
			var sessionText = $('#academicSession').val() ? $('#academicSession option:selected').val() : '';
			var subjectText = $('#subject_composite').val() ? $('#subject_composite option:selected').text() : '';
			var armText = $('#classId').val() && $('#classId').val() !== 'all' ? $('#classId option:selected').text() : ($('#classId').val() === 'all' ? 'ALL' : '');
			var testTypeText = getSelectedTestTypeLabel('create');
			
			// Format: [Session] [Subject+YG] [Arm] [Type]
			var generatedName = $.trim([sessionText, subjectText, armText, testTypeText].filter(Boolean).join(' '));
			
			$('#testNameTyping').val(generatedName);
			$('#testNamePreview').val(generatedName);
		}

		function updateGeneratedEditName()
		{
			var sessionText = $('#academicSession1').val() ? $('#academicSession1 option:selected').val() : '';
			var subjectText = $('#subject_composite1').val() ? $('#subject_composite1 option:selected').text() : '';
			var armText = $('#classId1').val() && $('#classId1').val() !== 'all' ? $('#classId1 option:selected').text() : ($('#classId1').val() === 'all' ? 'ALL' : '');
			var testTypeText = getSelectedTestTypeLabel('edit');
			
			var generatedName = $.trim([sessionText, subjectText, armText, testTypeText].filter(Boolean).join(' '));
			
			$('#testName1').val(generatedName);
		}
		
		function callTable()	//Declaration of the data table function
		{
			$.ajax({
					url: 'getTests.php',
					type: 'get',
					data: {testType: $('#testTypeFilter').val()},
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var testId = response[i].testId;
							var testName = response[i].testName;
							var testDate = response[i].testDate;
							var duration = response[i].duration;						
							var subjectId = response[i].subjectId;						
							var subjectName = response[i].subjectName;						
							var yearGroupId = response[i].yearGroupId;						
							var yearGroupName = response[i].yearGroupName;						
							var testYear = response[i].testYear;
							var theReviewOption = response[i].reviewOption;
							var essayOption = response[i].essayOption;
							var essayTime = response[i].essayTime;
							var randomizeQuestions = response[i].randomizeQuestions;
							var randomizeOptions = response[i].randomizeOptions;
							var testType = response[i].testType;
							var testTypeLabel = response[i].testTypeLabel || testType;
							var customTestType = response[i].customTestType || '';
							var mockPaperLabel = response[i].mockPaperLabel || '';
							var academicSession = response[i].academicSession || "";
							var setupStatus = response[i].setupStatus;
							var studentCount = response[i].studentCount;
							var questionCount = response[i].questionCount;
							// store mapping so edit modal can pick up current value without changing string concatenation
							window.randomizeMap = window.randomizeMap || {};
							window.randomizeMap[testId] = randomizeQuestions;
							window.randomizeOptionsMap = window.randomizeOptionsMap || {};
							window.randomizeOptionsMap[testId] = randomizeOptions;
							var status = response[i].status;
							
							var reviewOptionTODisplay = ""
							if(theReviewOption == "Yes")
							{
								reviewOptionTODisplay = "Status:ON";
							}
							else if(theReviewOption == "No")
							{
								reviewOptionTODisplay = "Status:OFF";
							}
							
							var tr_str = "<tr style='font-size:13px;'>" +
							"<td width='1'><center>" + (i+1) + "</center></td>" +
							"<td width='21'><center>" + testName + " (" + subjectName + ")</center></td>" +
							"<td width='8'><center>" + testTypeLabel + "</center></td>" +
							"<td width='11'><center>" + testDate + "</center></td>" +
							"<td width='7'><center>" + duration + " minutes</center></td>" +
							"<td width='7'><center>" + yearGroupName + "</center></td>" +
							"<td width='9'><center>" + academicSession + "</center></td>" +
							"<td width='5'><center>" + testYear + "</center></td>" +
							"<td width='11' style='font-size:13px;'><center>" + reviewOptionTODisplay + getReviewFlagging(testId, theReviewOption) + "</center></td>" +
							"<td width='11'><center>" + getSetupStatusText(setupStatus, studentCount, questionCount, testId) + "</center></td>" +
							"<td width='16'><center>" + getStatus(testId, testName, status) + "</center></td>" +
							"<td align='center' width='12'><a onClick='openEditModal(\""+testId+"\",\""+testName+"\",\""+testDate+"\",\""+duration+"\",\""+subjectId+"\",\""+yearGroupId+"\",\""+academicSession+"\",\""+testType+"\",\""+encodeURIComponent(customTestType)+"\",\""+encodeURIComponent(mockPaperLabel)+"\",\""+theReviewOption+"\",\""+essayOption+"\",\""+essayTime+"\")' title='Edit this test' style='cursor:pointer'><i class='fa fa-pencil-square-o'></i></a> &nbsp; &nbsp;<a onClick='deleteTest(\""+testId+"\",\""+testName+"\")' title='Delete this test' style='cursor:pointer;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a></td>" +
							
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
						"responsive": true
						});
					}
			});
		}
		//calling the data table function
		callTable();
		
		//function call to open modal for editing a category
		function openEditModal(testId, testName, testDate, duration, subjectId, yearGroupId, academicSession, testType, customTestType, mockPaperLabel, theReviewOption, essayOption, essayTime)
        {
            var modal1 = document.getElementById("myModal1");
			document.getElementById("testId").value = testId;  
			document.getElementById("testName1").value = testName;  
			document.getElementById("testDate1").value = testDate;  
			document.getElementById("testDuration1").value = duration;  
			$("#academicSession1").val(academicSession);
			$("#testType1").val(testType);
			$("#customTestType1").val(customTestType ? decodeURIComponent(customTestType) : '');
			$("#mockPaperLabel1").val(mockPaperLabel ? decodeURIComponent(mockPaperLabel) : '');
			toggleCustomTestTypeField('edit');
			$("#reviewOption1").val(theReviewOption);
			$("#essayOption1").val(essayOption);

			// Handle dependent dropdowns for edit
			if (yearGroupId && subjectId) {
				var compositeVal = subjectId + '|' + yearGroupId;
				$("#subject_composite1").val(compositeVal);
				$("#subjectId1").val(subjectId);
				$("#yearGroup1").val(yearGroupId);

				$.ajax({
					url: 'getTeacherAssignedClassesAndSubjects.php',
					type: 'GET',
					data: { type: 'findClass', yearGroupId: yearGroupId, subjectId: subjectId },
					dataType: 'json',
					success: function(response) {
						var foundClassId = response.data;
						
						// Re-trigger classesBySubject to populate the class dropdown
						$.ajax({
							url: 'getTeacherAssignedClassesAndSubjects.php',
							type: 'GET',
							data: { type: 'classesBySubject', subjectId: subjectId, yearGroupId: yearGroupId },
							dataType: 'json',
							success: function(classResponse) {
								var options = '<option value="">... Select Class ...</option>';
								if (classResponse.success && classResponse.data.length > 0) {
									if (classResponse.data.length > 1) {
										options += '<option value="all">ALL ASSIGNED ARMS</option>';
									}
									classResponse.data.forEach(function(item) {
										options += '<option value="' + item.classId + '">' + item.className + '</option>';
									});
								}
								$("#classId1").html(options);
								if (foundClassId) {
									$("#classId1").val(foundClassId);
								}
								updateGeneratedEditName();
							}
						});
					}
				});
			} else {
				updateGeneratedEditName();
			}
			if(essayOption == "Yes")
			{
				$("#addEssayTime1").html('<label><span style="color:#000000;">Essay time in minutes:</span> <span class="required" style="color:red;">*</span></label><br><input type="text" class="form-control" name="essayTime1" id="essayTime1" onkeypress="javascript:return isNumber(event)" placeholder="Enter the essay duration in minutes">');
				document.getElementById("essayTime1").value = essayTime;
			}
			else
			{
				$("#addEssayTime1").html('');
			}

						// pre-select randomizeQuestions in edit modal using mapping saved earlier
						var rand = '';
						if(window.randomizeMap && typeof window.randomizeMap[testId] !== 'undefined'){
							rand = window.randomizeMap[testId];
						}
						$("#randomizeQuestions1").val(rand);
						var randOptions = '';
						if(window.randomizeOptionsMap && typeof window.randomizeOptionsMap[testId] !== 'undefined'){
							randOptions = window.randomizeOptionsMap[testId];
						}
						$("#randomizeOptions1").val(randOptions);
                
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
		
		//Delete test
		function deleteTest(testId, testName)
		{
			$('.message4').html("");
			$('.message5').html("");
			
			dlhsConfirm("Are you sure you wish to delete this test? This will also delete all test examinees, questions and answers.", function() {
				var form_data = 'testId='+testId;
					  
					$.ajax({
						url: "deleteTest.php",
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
								 $('.message4').html('<i class="fa fa-check"></i> '+testName+' successfully deleted.').fadeIn('slow');
								 $('#example').DataTable().clear().destroy();
								 callTable();
							}
							else if (html==2)	//If deletion is unsuccessful	
							{                              
								 $('.message5').html("");
								 $('.message4').html('<i class="fa fa-times"></i> Could not delete subject. Please try again.').fadeIn('slow');
							}
						}
									
					});
			});
		}
		
		//Start of edit test
		function editTestDetails()
		{
			$('.message1').html("");
			$('.message2').html("");
			$('.message3').html("");
			$('.message4').html("");
			$('.message5').html("");
			$('.message6').html("");
			
			// Collect all values first
			var testId = $('input[name=testId]').val();
			var testName = $('input[name=testName1]').val();
			var testDate = $('input[name=testDate1]').val();
			var testDuration = $('input[name=testDuration1]').val();
			var startHour = '8';
			var startMinute = '0';
			var amOrPm = 'AM';
			var subjectId = document.getElementById('subjectId1').value;
			var yearGroupId = document.getElementById('yearGroup1').value;
			var academicSession = document.getElementById('academicSession1').value;
			var testType = document.getElementById('testType1').value;
			var customTestType = $.trim(document.getElementById('customTestType1').value);
			var mockPaperLabel = $.trim(document.getElementById('mockPaperLabel1').value);
			var reviewOption = document.getElementById('reviewOption1').value;
			var essayOption = document.getElementById('essayOption1').value;
			var randomizeQuestions = document.getElementById('randomizeQuestions1').value;
			var randomizeOptions = document.getElementById('randomizeOptions1').value;
			
			var theEssayTime = "";
			if(essayOption == "Yes")
			{
				var theEssayTime = document.getElementById('essayTime1').value;					 
			}
			
			// Validate first — show inline errors before showing the confirm modal
			if (testDate=="")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> ' + ' Please select test date');
			}
			else if (testDuration=="")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> ' + ' Please set test duration in minutes');
			}
			else if (subjectId=="")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> ' + ' Please select test subject');
			}
			else if (yearGroupId=="")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> ' + ' Please select test year group');
			}
			else if (academicSession=="")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> Please select academic session');
			}
			else if (testType=="")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> Please select test type');
			}
			else if (testType=="OTHER" && customTestType=="")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> Please enter the kind of test for OTHER');
			}
			else if (testType=="MOCK" && mockPaperLabel=="")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> Please select the mock paper');
			}
			else if (reviewOption=="")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> ' + ' Please select test review option');
			}
			else if (essayOption=="")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> Please select essay option');
			}
			else if (essayOption =="Yes" && theEssayTime == "")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> Please enter essay time in minutes');
			}
			else if (randomizeQuestions=="")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> Please select question randomization option');
			}
			else if (randomizeOptions=="")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> Please select answer option randomization');
			}
			else
			{
				// All valid — now ask for confirmation
				dlhsConfirm("Are you sure you wish to update this test?", function() {
					$('.message3').html("");
					$('.message4').html("");
					
					var form_data = {
					  testId: testId,
					  testName: testName,
					  testDate: testDate,
					  testDuration: testDuration,
					  startHour: startHour,
					  startMinute: startMinute,
					  amOrPm: amOrPm,
					  subjectId: subjectId,
					  yearGroupId: yearGroupId,
					  academicSession: academicSession,
					  testType: testType,
					  customTestType: customTestType,
					  mockPaperLabel: mockPaperLabel,
					  reviewOption: reviewOption,
					  essayOption: essayOption,
					  theEssayTime: theEssayTime,
					  randomizeQuestions: randomizeQuestions,
					  randomizeOptions: randomizeOptions
					};
							  
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
							else if (html==2)	//If update is unsuccessful	
							{                              
								 $('.message4').html("");
								 $('.message3').html('<i class="fa fa-times"></i> Could not update test. Please try again.').fadeIn('slow');
							}
							else if (html==3)
							{
								 $('.message4').html("");
								 $('.message3').html('<i class="fa fa-times"></i> Please select a valid test type.').fadeIn('slow');
							}
							else if (html==4)
							{
								 $('.message4').html("");
								 $('.message3').html('<i class="fa fa-times"></i> Please select a valid academic session.').fadeIn('slow');
							}
							else if (html==5)
							{
								 $('.message4').html("");
								 $('.message3').html('<i class="fa fa-times"></i> Unable to generate the test name from the selected fields.').fadeIn('slow');
							}
							else if (html==6)
							{
								 $('.message4').html("");
								 $('.message3').html('<i class="fa fa-times"></i> Please enter the kind of test for OTHER.').fadeIn('slow');
							}
							else if (html==7)
							{
								 $('.message4').html("");
								 $('.message3').html('<i class="fa fa-times"></i> Please select the mock paper.').fadeIn('slow');
							}
						}				
					});
				});
			}
		}	
		//End of edit test
		
		//Start of add/subtract test time
		function addOrSubtractTestTime()
		{
			$('.message4').html("");
			$('.message6').html("");
			
			var testId = $('input[name=testId2]').val();
			var timeValue = $('input[name=testDuration2]').val();
			var timeAction = $('input[name="addOrSubtract"]:checked').val();
			
			// Validate first, then confirm
			if (timeValue=="")
			{
				$('.message6').html('<i class="fa fa-info-circle"></i> Please enter time to be added or subtracted');
			}
			else if ($('input[name="addOrSubtract"]:checked').length < 1)
			{
				$('.message6').html('<i class="fa fa-info-circle"></i> Please select action to be carried out');
			}
			else
			{
				dlhsConfirm("Are you sure you wish to update this test's time?", function() {
					var form_data = 
					  'testId='+testId+
					  '&timeValue='+timeValue+
					  '&timeAction='+timeAction;
							  
					$.ajax({
						url: "editOverAllTestTime.php",
						type: "POST",        
						data: form_data,
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
							else if (html==2)	//If update is unsuccessful	
							{                              
								 $('.message4').html("");
								 $('.message6').html('<i class="fa fa-times"></i> Could not update test time. Please try again.').fadeIn('slow');
							}
							else if (html==3)	//If time to subtract is more than duration	
							{                              
								 $('.message4').html("");
								 $('.message6').html('<i class="fa fa-times"></i> Time to be subtracted is less than current test duration.').fadeIn('slow');
							}
						}				
					});
				});
			}
		}	
		//End of add/subtract test time
		
		//function to start a test
		function clickToStart(testId, testName)
		{
			dlhsConfirm("Are you sure you wish to start this test?", function() {
				var form_data = 'testId='+testId;
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
			});
		}	//End of function to start a test
		
		//function to end a test
		function clickToEnd(testId, testName)
		{
			dlhsConfirm("Are you sure you wish to end this test?", function() {
				var form_data = 'testId='+testId;
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
							 callTable();
						}
						else if (html==2)	//If update is unsuccessful	
						{                              
							$('.message5').html("");
							$('.message4').html('<i class="fa fa-times"></i> Could not end test. Please try again.').fadeIn('slow');
						}
					}
				});
			});
		}	//End of function to end a test
		
		//function to reschedule a test
		function rescheduleTest(testId, testName)
		{
			dlhsConfirm("Are you sure you wish to reschedule this test?", function() {
				var form_data = 'testId='+testId;
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
							 callTable();
						}
						else if (html==2)	//If update is unsuccessful	
						{                              
							$('.message5').html("");
							$('.message4').html('<i class="fa fa-times"></i> Could not reschedule test. Please try again.').fadeIn('slow');
						}
					}
				});
			});
		}	//End of function to reschedule a test
		
		
		function addTest()
		{
			$('.message1').html('');
			$('.message2').html('');
						
			var testDate = document.getElementById('testDate').value;
			var testDuration = $('input[name=testDuration]').val();
			var testName = document.getElementById('testNameTyping').value;
			var startHour = '8';
			var startMinute = '0';
			var amOrPm = 'AM';
			var subject = document.getElementById('subject').value;
			var yearGroup = document.getElementById('yearGroup').value;					 
			var academicSession = document.getElementById('academicSession').value;
			var testType = document.getElementById('testType').value;
			var customTestType = $.trim(document.getElementById('customTestType').value);
			var mockPaperLabel = $.trim(document.getElementById('mockPaperLabel').value);
			var reviewOption = document.getElementById('reviewOption').value;
			var essayOption = document.getElementById('essayOption').value;
			var randomizeQuestions = document.getElementById('randomizeQuestions').value;
			var randomizeOptions = document.getElementById('randomizeOptions').value;
			var classId = document.getElementById('classId').value;
			
			var theEssayTime = "";
			if(essayOption == "Yes")
			{
				var theEssayTime = document.getElementById('essayTime').value;					 
			}
			
			if (academicSession=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select academic session')
			}
			else if (subject=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select subject')
			}
			else if (yearGroup=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select year group')
			}
			else if (classId=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select class arm')
			}
			else if (testType=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select test type')
			}
			else if (testType=="OTHER" && customTestType=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter the kind of test for OTHER')
			}
			else if (testType=="MOCK" && mockPaperLabel=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select the mock paper')
			}
			else if (testDate=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select test date')
			}
			else if (testDuration=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter test duration')
			}
			else if (reviewOption=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select review option')
			}
			else if (essayOption=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select essay option')
			}
			else if (essayOption =="Yes" && theEssayTime == "")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter essay time in minutes')
			}
			else if (randomizeQuestions=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select question randomization option')
			}
			else if (randomizeOptions=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select answer option randomization')
			}
			else
			{
				var form_data = {
				  testName: testName,
				  testDate: testDate,
				  testDuration: testDuration,
				  startHour: startHour,
				  startMinute: startMinute,
				  amOrPm: amOrPm,
				  subject: subject,
				  yearGroup: yearGroup,
				  academicSession: academicSession,
				  testType: testType,
				  customTestType: customTestType,
				  mockPaperLabel: mockPaperLabel,
				  reviewOption: reviewOption,
				  essayOption: essayOption,
				  theEssayTime: theEssayTime,
				  randomizeQuestions: randomizeQuestions,
				  randomizeOptions: randomizeOptions,
				  classId: classId
				};
															 
				//start the ajax
				$.ajax({
					url: "addTest.php",
					type: "POST",    
					data: form_data,    
					 
					success: function (html) {  
						var response = html;
						try {
							response = JSON.parse(html);
						} catch (e) {}

						if (response==0)	//If session is expired.
						{                              
							window.location.replace("logout.php");
						}
						else if (response.status==1)	//If test successfully added
						{                              
							$('.message2').html('');
							// Redirect directly to adding students as requested
							window.location.replace("addStudentsToTestForm.php?testId=" + response.testId + "&setup=1");
						}
						else if (response.status==2)	//If insertion is unsuccessful	
						{                              
							 $('.message1').html('');
							 $('.message2').html('<i class="fa fa-times"></i> Could not add Test. Please try again.').fadeIn('slow');
						}
						else if (response.status==3)
						{
							 $('.message1').html('');
							 $('.message2').html('<i class="fa fa-info-circle"></i> Opps! Something went wrong. Please try again.').fadeIn('slow');
						}
						else if (response.status==4)
						{
							 $('.message1').html('');
							 $('.message2').html('<i class="fa fa-info-circle"></i>Opps! That test name already exists. Please use another test name.').fadeIn('slow');
						}
						else if (response.status==5)
						{
							 $('.message1').html('');
							 $('.message2').html('<i class="fa fa-info-circle"></i> Please select a valid test type.').fadeIn('slow');
						}
						else if (response.status==6)
						{
							 $('.message1').html('');
							 $('.message2').html('<i class="fa fa-info-circle"></i> Please select a valid academic session.').fadeIn('slow');
						}
						else if (response.status==7)
						{
							 $('.message1').html('');
							 $('.message2').html('<i class="fa fa-info-circle"></i> Unable to generate the test name from the selected fields.').fadeIn('slow');
						}
						else if (response.status==8)
						{
							 $('.message1').html('');
							 $('.message2').html('<i class="fa fa-info-circle"></i> Please enter the kind of test for OTHER.').fadeIn('slow');
						}
						else if (response.status==9)
						{
							 $('.message1').html('');
							 $('.message2').html('<i class="fa fa-info-circle"></i> Please select the mock paper.').fadeIn('slow');
						}
						else
						{
							 $('.message1').html('');
							 $('.message2').html('<i class="fa fa-info-circle"></i> Error: ' + (response.message || 'Unknown error occurred.')).fadeIn('slow');
							 console.error('Add Test Error:', response);
						}
					}
				});
			}
		}

		$('#testTypeFilter').on('change', function(){
			$('#example').DataTable().clear().destroy();
			callTable();
		});

		function dlhsHandleSubjectChange(mode) {
			var compositeVal = mode === 'create' ? $('#subject_composite').val() : $('#subject_composite1').val();
			var classDropdown = mode === 'create' ? $('#classId') : $('#classId1');
			var subjectHidden = mode === 'create' ? $('#subject') : $('#subjectId1');
			var yearGroupHidden = mode === 'create' ? $('#yearGroup') : $('#yearGroup1');
			var updateNameFunc = mode === 'create' ? updateGeneratedCreateName : updateGeneratedEditName;

			if (!compositeVal) {
				subjectHidden.val('');
				yearGroupHidden.val('');
				classDropdown.html('<option value="">... Select Class ...</option>');
				updateNameFunc();
				return;
			}

			var parts = compositeVal.split('|');
			var sId = parts[0];
			var ygId = parts[1];

			subjectHidden.val(sId);
			yearGroupHidden.val(ygId);

			classDropdown.html('<option value="">... Loading ...</option>');

			$.ajax({
				url: 'getTeacherAssignedClassesAndSubjects.php',
				type: 'GET',
				data: { type: 'classesBySubject', subjectId: sId, yearGroupId: ygId },
				dataType: 'json',
				success: function(response) {
					var options = '<option value="">... Select Class ...</option>';
					if (response.success && response.data.length > 0) {
						if (response.data.length > 1) {
							options += '<option value="all">ALL ASSIGNED ARMS</option>';
						}
						response.data.forEach(function(item) {
							options += '<option value="' + item.classId + '">' + item.className + '</option>';
						});
					}
					classDropdown.html(options);
					updateNameFunc();
				}
			});
		}

		function dlhsUpdateClassesForCreate() {
			// This function is now deprecated in favor of dlhsHandleSubjectChange
		}

		function dlhsUpdateSubjectsForCreate() {
			// This function is now deprecated in favor of dlhsHandleSubjectChange
		}

		function dlhsUpdateClassesForEdit(callback) {
			// This function is now deprecated in favor of dlhsHandleSubjectChange
		}

		function dlhsUpdateSubjectsForEdit(callback) {
			// This function is now deprecated in favor of dlhsHandleSubjectChange
		}

		$('#academicSession, #subject_composite, #testType').on('change', function(){
			toggleCustomTestTypeField('create');
			updateGeneratedCreateName();
		});

		$('#academicSession1, #subject_composite1, #testType1').on('change', function(){
			toggleCustomTestTypeField('edit');
			updateGeneratedEditName();
		});
		$('#customTestType, #customTestType1, #mockPaperLabel, #mockPaperLabel1').on('keyup change', function(){
			updateGeneratedCreateName();
			updateGeneratedEditName();
		});
		toggleCustomTestTypeField('create');
		toggleCustomTestTypeField('edit');
		updateGeneratedCreateName();
		
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

			// Explicit load button: fetch essay and populate editor for editing
			function loadEssayForEdit(){
				var testId = document.getElementById('testName').value;
				if(!testId){
					dlhsAlert('Please select a test first.');
					return;
				}
				$('#btnLoadEssay').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');
				$.ajax({
					url: 'getEssayQuestion.php',
					type: 'POST',
					data: {testId : testId},
					dataType: 'JSON',
					success: function(response){
						if(response && response.length > 0){
							try {
								if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances && CKEDITOR.instances.essay) {
									CKEDITOR.instances.essay.setData(response[0].question);
									CKEDITOR.instances.essay.focus();
								} else {
									$('#essay').val(response[0].question);
									$('#essay').focus();
								}
								$('.message1').html('<i class="fa fa-check"></i> Essay loaded into editor for editing.');
							} catch (e) {
								console.error('Error loading essay into editor', e);
								dlhsAlert('Could not load essay into editor. See console for details.', 'error');
							}
						} else {
							dlhsAlert('No essay has been uploaded for this test yet.');
						}
					}
				}).always(function(){
					$('#btnLoadEssay').prop('disabled', false).html('<i class="fa fa-pencil"></i> Edit essay');
				});
			}
	</script>
	



	<!-- ===== DLHS Universal Confirm Modal ===== -->
	<div id="dlhsConfirmModal" style="display:none; position:fixed; inset:0; z-index:999990; background:rgba(0,0,0,0.48); backdrop-filter:blur(5px); -webkit-backdrop-filter:blur(5px); align-items:center; justify-content:center;">
		<div style="background:#fff; width:100%; max-width:460px; border-radius:20px; box-shadow:0 28px 64px rgba(0,0,0,0.22); overflow:hidden; margin:0 16px;" onclick="event.stopPropagation()">
			<div id="dlhsConfirmIconWrap" style="padding:30px 24px 4px; text-align:center; font-size:42px; color:#f59e0b;">
				<i class="fa fa-question-circle"></i>
			</div>
			<div style="padding:12px 32px 32px;">
				<p id="dlhsConfirmMsg" style="font-size:15px; color:#222; text-align:center; margin:0 0 26px; line-height:1.65;"></p>
				<div style="display:flex; gap:12px; justify-content:center;">
					<button id="dlhsConfirmCancelBtn" type="button" style="flex:1; max-width:150px; padding:12px 0; border-radius:50px; border:1.5px solid #ddd; background:#f5f5f5; color:#555; font-size:14px; font-weight:600; cursor:pointer; transition:background 0.2s;">Cancel</button>
					<button id="dlhsConfirmOkBtn" type="button" style="flex:1; max-width:150px; padding:12px 0; border-radius:50px; border:none; background:linear-gradient(135deg,#003366,#0055aa); color:#ffd700; font-size:14px; font-weight:700; cursor:pointer; box-shadow:0 4px 16px rgba(0,51,102,0.28); transition:all 0.2s;">Confirm</button>
				</div>
			</div>
		</div>
	</div>

	<!-- ===== DLHS Universal Alert Modal ===== -->
	<div id="dlhsAlertModal" style="display:none; position:fixed; inset:0; z-index:999991; background:rgba(0,0,0,0.48); backdrop-filter:blur(5px); -webkit-backdrop-filter:blur(5px); align-items:center; justify-content:center;">
		<div style="background:#fff; width:100%; max-width:420px; border-radius:20px; box-shadow:0 28px 64px rgba(0,0,0,0.22); overflow:hidden; margin:0 16px;" onclick="event.stopPropagation()">
			<div id="dlhsAlertIconWrap" style="padding:30px 24px 4px; text-align:center; font-size:42px; color:#3b82f6;">
				<i class="fa fa-info-circle"></i>
			</div>
			<div style="padding:12px 32px 32px;">
				<p id="dlhsAlertMsg" style="font-size:15px; color:#222; text-align:center; margin:0 0 26px; line-height:1.65;"></p>
				<div style="text-align:center;">
					<button id="dlhsAlertOkBtn" type="button" style="padding:12px 48px; border-radius:50px; border:none; background:linear-gradient(135deg,#003366,#0055aa); color:#ffd700; font-size:14px; font-weight:700; cursor:pointer; box-shadow:0 4px 16px rgba(0,51,102,0.28);">OK</button>
				</div>
			</div>
		</div>
	</div>

	<style>
		@keyframes dlhsModalPop {
			from { opacity:0; transform:scale(0.86) translateY(16px); }
			to   { opacity:1; transform:scale(1) translateY(0); }
		}
		#dlhsConfirmModal.dlhs-open, #dlhsAlertModal.dlhs-open { display:flex !important; }
		#dlhsConfirmModal.dlhs-open > div, #dlhsAlertModal.dlhs-open > div {
			animation: dlhsModalPop 0.28s cubic-bezier(0.34,1.56,0.64,1);
		}
		#dlhsConfirmCancelBtn:hover { background:#e8e8e8; }
		#dlhsConfirmOkBtn:hover { background:linear-gradient(135deg,#00285c,#004499); transform:translateY(-1px); }
		#dlhsAlertOkBtn:hover { background:linear-gradient(135deg,#00285c,#004499); transform:translateY(-1px); }
	</style>

	<script>
	(function(){
		var _dlhsCb = null;

		window.dlhsConfirm = function(message, onConfirm) {
			_dlhsCb = onConfirm || null;
			document.getElementById('dlhsConfirmMsg').textContent = message;
			var iconWrap = document.getElementById('dlhsConfirmIconWrap');
			iconWrap.innerHTML = '<i class="fa fa-question-circle" style="color:#f59e0b;"></i>';
			document.getElementById('dlhsConfirmModal').classList.add('dlhs-open');
		};

		window.dlhsAlert = function(message, type) {
			document.getElementById('dlhsAlertMsg').textContent = message;
			var iconWrap = document.getElementById('dlhsAlertIconWrap');
			if (type === 'error') {
				iconWrap.innerHTML = '<i class="fa fa-times-circle" style="color:#ef4444;"></i>';
			} else if (type === 'success') {
				iconWrap.innerHTML = '<i class="fa fa-check-circle" style="color:#22c55e;"></i>';
			} else {
				iconWrap.innerHTML = '<i class="fa fa-info-circle" style="color:#3b82f6;"></i>';
			}
			document.getElementById('dlhsAlertModal').classList.add('dlhs-open');
		};

		document.addEventListener('DOMContentLoaded', function(){
			// Confirm – OK
			document.getElementById('dlhsConfirmOkBtn').addEventListener('click', function(){
				document.getElementById('dlhsConfirmModal').classList.remove('dlhs-open');
				if (typeof _dlhsCb === 'function') { var cb = _dlhsCb; _dlhsCb = null; cb(); }
			});
			// Confirm – Cancel
			document.getElementById('dlhsConfirmCancelBtn').addEventListener('click', function(){
				document.getElementById('dlhsConfirmModal').classList.remove('dlhs-open');
				_dlhsCb = null;
			});
			// Confirm – backdrop click
			document.getElementById('dlhsConfirmModal').addEventListener('click', function(e){
				if (e.target === this) { this.classList.remove('dlhs-open'); _dlhsCb = null; }
			});
			// Alert – OK
			document.getElementById('dlhsAlertOkBtn').addEventListener('click', function(){
				document.getElementById('dlhsAlertModal').classList.remove('dlhs-open');
			});
			// Alert – backdrop click
			document.getElementById('dlhsAlertModal').addEventListener('click', function(e){
				if (e.target === this) { this.classList.remove('dlhs-open'); }
			});
		});
	})();
	</script>

  </body>
</html>
