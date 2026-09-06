<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn']))
	{
		header('location:../index.php');
	}
	else
	{
		include "../../db_connection/dlhs_db_connection.php";
		require_once "../../scripts/test_workflow_helper.php";
		
		if(!empty($_GET['status']))
		{
			switch($_GET['status']){
				case 'succ_insert':
					$statusType = 'alert-success';
					$statusMsg = '<i class="fa fa-check-circle"></i> Questions have been imported successfully';
					break;
				case 'succ_update':
					$statusType = 'alert-success';
					$statusMsg = '<i class="fa fa-check-circle"></i> Questions have been updated successfully.';
					break;
				case 'err':
					$statusType = 'alert-danger';
					$statusMsg = '<i class="fa fa-times"></i> Some problem occurred, please try again.';
					break;
				case 'invalid_file':
					$statusType = 'alert-danger';
					$statusMsg = '<i class="fa fa-times"></i> Please upload a valid CSV file.';
					break;
				case 'missing_columns':
					$statusType = 'alert-danger';
					$statusMsg = '<i class="fa fa-times"></i> CSV is missing required columns. Please use the correct template.';
					break;
				case 'students_required':
					$statusType = 'alert-danger';
					$statusMsg = '<i class="fa fa-times"></i> Add students to the test before uploading or typing questions.';
					break;
				case 'invalid_option_placeholders':
					$statusType = 'alert-danger';
					$statusMsg = '<i class="fa fa-times"></i> CSV upload rejected because one or more options contained only A, B, C, or D instead of the real option text.';
					break;
				default:
					$statusType = '';
					$statusMsg = '';
			}
		}
		
		if(!empty($_GET['editStatus']))
		{
			switch($_GET['editStatus']){
				case 'success':
					$statusType1 = 'alert-success';
					$statusMsg1 = '<i class="fa fa-check-circle"></i> Question successfully edited';
					break;
				default:
					$statusType1 = '';
					$statusMsg1 = '';
			}
		}

		$staffId = isset($_SESSION['staffId']) ? (int) $_SESSION['staffId'] : 0;
		$preselectedTestId = isset($_GET['testId']) ? (int) $_GET['testId'] : 0;
		$questionTestOptions = array();

		if ($staffId > 0) {
			$testsResult = $connection->query("SELECT * FROM tests WHERE staffId='{$staffId}' ORDER BY testName ASC");
			if ($testsResult) {
				while ($testRow = $testsResult->fetch_assoc()) {
					$setupSummary = dlhsBuildTestSetupSummary($connection, $testRow);
					$testType = dlhsNormalizeTestType(isset($testRow['testType']) ? $testRow['testType'] : '');
					$optionLabel = $testRow['testName'];
					if ($testType !== '') {
						$optionLabel .= " ({$testType})";
					}
					if (!$setupSummary['studentsAdded']) {
						$optionLabel .= " - add students first";
					}

					$questionTestOptions[] = array(
						'testId' => (int) $testRow['testId'],
						'label' => $optionLabel,
						'studentsAdded' => $setupSummary['studentsAdded'],
						'setupStatus' => $setupSummary['setupStatus'],
						'setupMessage' => $setupSummary['setupMessage']
					);
				}
			}
		}
	}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">

    <title>Add Question | DLHS</title>

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
<!-- rowReorder removed: <link rel="stylesheet" type="text/css" href="../../datatables/css/rowReorder.dataTables.min.css"/> -->
	<link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>
	<script src="../../libs/jquery.min.js"></script>
	<style>
		.modal1 {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
	            z-index: 2100; /* Keep preview modals above the fixed staff sidebar */
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
				position: relative;
				z-index: 2101;
	            border-radius:7px;
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 90%;
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
			.shared-stimulus-panel {
				margin: 10px 0 25px;
				padding: 18px;
				border: 1px solid #d9edf7;
				border-radius: 8px;
				background: #f8fcff;
			}
			.shared-stimulus-trigger {
				display: flex;
				flex-wrap: wrap;
				justify-content: space-between;
				align-items: center;
				gap: 12px;
			}
			.shared-stimulus-panel h4 {
				margin-top: 0;
				color: #0a5a88;
			}
			.shared-stimulus-panel .help-block {
				margin-bottom: 8px;
			}
			.shared-stimulus-question-list {
				max-height: 220px;
				overflow-y: auto;
				border: 1px solid #dfe7ee;
				border-radius: 6px;
				padding: 10px 12px;
				background: #ffffff;
			}
			.shared-stimulus-question-item {
				display: block;
				margin-bottom: 8px;
				font-weight: normal;
			}
			.shared-stimulus-toolbar {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				margin-bottom: 12px;
			}
			.split-helper-card {
				margin-top: 12px;
				padding: 12px 15px;
				border-radius: 8px;
				border: 1px solid #ffe08a;
				background: #fff8e1;
				display: flex;
				flex-wrap: wrap;
				align-items: center;
				gap: 10px;
			}
			.split-helper-card strong {
				color: #8a6100;
			}
			.word-import-panel {
				margin: 0 0 20px;
				padding: 14px 16px;
				border: 1px solid #cfe2f3;
				border-radius: 8px;
				background: #f7fbff;
			}
			.option-grid {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
				gap: 14px;
			}
			.option-field textarea {
				min-height: 190px;
			}
			.option-count-toggle {
				display: flex;
				flex-wrap: wrap;
				gap: 14px;
				margin: 8px 0 4px;
			}
				.option-count-toggle label {
					font-weight: 600;
				}
				.question-form-shell {
					max-width: 1320px;
					margin: 0 auto;
				}
				.authoring-section {
					margin-bottom: 22px;
					padding: 18px;
					border: 1px solid #dde7f1;
					border-radius: 8px;
					background: #ffffff;
					box-shadow: 0 8px 20px rgba(18, 38, 63, 0.05);
				}
				.authoring-section-header {
					display: flex;
					flex-wrap: wrap;
					align-items: center;
					justify-content: space-between;
					gap: 10px;
					margin-bottom: 16px;
					padding-bottom: 12px;
					border-bottom: 1px solid #edf2f7;
				}
				.authoring-section-header h4 {
					margin: 0;
					color: #17324e;
					font-weight: 700;
				}
				.authoring-section .form-group:last-child {
					margin-bottom: 0;
				}
				.import-grid {
					display: grid;
					grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
					gap: 16px;
				}
				.import-card {
					padding: 16px;
					border: 1px solid #e1e8f0;
					border-radius: 8px;
					background: #f9fbfd;
				}
				.import-card h5 {
					margin: 0 0 12px;
					color: #17324e;
					font-weight: 700;
				}
				.import-actions {
					display: flex;
					flex-wrap: wrap;
					gap: 10px;
					align-items: center;
					margin-top: 12px;
				}
				.editor-toolbar-row {
					display: flex;
					flex-wrap: wrap;
					align-items: center;
					justify-content: space-between;
					gap: 12px;
					margin-bottom: 10px;
				}
				.answer-card {
					padding: 16px;
					border: 1px solid #e1e8f0;
					border-radius: 8px;
					background: #f9fbfd;
				}
				.correct-option-list {
					display: grid;
					grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
					gap: 8px;
					margin: 12px 0;
				}
				.correct-option-list label {
					padding: 9px 12px;
					border: 1px solid #d9e4ee;
					border-radius: 6px;
					background: #ffffff;
					font-weight: 600;
				}
				.form-action-row {
					display: flex;
					flex-wrap: wrap;
					align-items: center;
					justify-content: flex-end;
					gap: 12px;
				}
		</style>
	
	 <!-- bootstrap-wysiwyg -->
<script src="js/jquery.hotkeys.js"></script>
    <script src="js/bootstrap-wysiwyg.js"></script>
    <script src="js/bootstrap-wysiwyg-custom.js"></script>
    <!-- ck editor -->
    <script type="text/javascript" src="assets/ckeditor/ckeditor.js"></script>
<!-- Duplicate jQuery removed: <script src="jQuery3.3.1.js"></script> -->
	<script>
		function isNumberKey(evt, element) {
			var charCode = (evt.which) ? evt.which : event.keyCode
			if (charCode > 31 && (charCode < 48 || charCode > 57) && !(charCode == 46 || charCode == 8))
				return false;
			else {
				var len = $(element).val().length;
				var index = $(element).val().indexOf('.');
				if (index > 0 && charCode == 46) {
				  return false;
				}
				if (index > 0) {
					var CharAfterdot = (len + 1) - index;
					if (CharAfterdot > 3) {
						return false;
					}
				}
			}
			return true;
		}
	</script>
  </head>
  <body>

	<!-- container section start -->
	<section id="container" class="">
		<!--Including the header-->
		<?php include 'header.php'; ?>

		<!--Including the sidebar-->
		<?php include 'sideBarAddQuestions.php'; ?>

      <!--main content start-->
      <section id="main-content">
          <section class="wrapper">
		  <div class="row">
				<div class="col-lg-12">
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Add Question</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Add Question</li>
						<a href="javascript:void(0)" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
					</ol>
				</div>
			</div>
              
              
				<div class="row">
					<div class="col-lg-12">
						<section class="panel">
							<header class="panel-heading">
								Add Questions to selected Test
							</header>
								<div class="panel-body">
									<div class="question-form-shell">
										<?php if(!empty($statusMsg)){ ?>
											<div class="alert <?php echo $statusType; ?>" style="font-size:16px;"><a href="javascript:void(0)" class="close" data-dismiss="alert" aria-label="close">&times;</a><?php echo $statusMsg; ?></div>
										<?php } ?>
										<?php if(!empty($statusMsg1)){ ?>
											<div class="alert <?php echo $statusType1; ?>" style="font-size:16px;"><a href="javascript:void(0)" class="close" data-dismiss="alert" aria-label="close">&times;</a><?php echo $statusMsg1; ?></div>
										<?php } ?>
										<div class="message1" style="color:green; font-size:17px;" align="center"></div>

										<form class="form-horizontal" name="csvQuestionForm" method="post" action="uploadQuestions.php" enctype="multipart/form-data">
											<div class="authoring-section">
												<div class="authoring-section-header">
													<h4><i class="fa fa-check-square-o"></i> Test Setup</h4>
													<div>
														<button type="button" class="btn btn-primary" onclick="previewTestQuestions()"><i class="fa fa-eye"></i> Preview test questions</button>
														<span id="essayAvailability" style="margin-left:10px; font-weight:bold;"></span>
													</div>
												</div>
												<div class="form-group">
													<label for="testName" class="control-label col-sm-3"><strong>Select test:</strong></label>
													<div class="col-sm-9">
														<select class="form-control" name="testName" id="testName" required>
															<option value="">... Select Test ...</option>
															<?php
																foreach ($questionTestOptions as $testOption) {
																	$isSelected = $preselectedTestId === (int) $testOption['testId'] ? 'selected' : '';
																	$isDisabled = $testOption['studentsAdded'] ? '' : 'disabled';
															?>
															<option value="<?php echo $testOption['testId']; ?>" data-students-added="<?php echo $testOption['studentsAdded'] ? '1' : '0'; ?>" data-setup-status="<?php echo htmlspecialchars($testOption['setupStatus']); ?>" data-setup-message="<?php echo htmlspecialchars($testOption['setupMessage']); ?>" <?php echo $isSelected; ?> <?php echo $isDisabled; ?>><?php echo htmlspecialchars($testOption['label']); ?></option>
															<?php } ?>
														</select>
														<div id="questionSetupHint" style="font-weight:bold; color:#a94442; margin-top:8px;"></div>
													</div>
												</div>
											</div>

											<div class="authoring-section">
												<div class="authoring-section-header">
													<h4><i class="fa fa-upload"></i> Imports</h4>
												</div>
												<div class="import-grid">
													<div class="import-card">
														<h5>CSV Questions</h5>
														<input type="file" name="file" id="csvFileInput" accept=".csv" class="form-control" required>
														<div class="import-actions">
															<button type="button" class="btn btn-primary" id="submitUpload" onclick="triggerCSVPreview()"><i class="fa fa-upload"></i> Submit CSV</button>
															<a href="downloadQuestionTemplate.php" class="btn btn-default"><i class="fa fa-download"></i> Download template</a>
														</div>
													</div>
													<div class="import-card">
														<h5>Word Document</h5>
														<input type="file" id="wordImportFile" accept=".docx" class="form-control">
														<div class="import-actions">
															<button type="button" class="btn btn-info" onclick="importWordDocument('objective')"><i class="fa fa-file-word-o"></i> To objective</button>
															<button type="button" class="btn btn-default" onclick="importWordDocument('essay')"><i class="fa fa-file-word-o"></i> To essay</button>
														</div>
														<span id="wordImportMessage" style="display:block; margin-top:10px; font-weight:bold;"></span>
													</div>
													<div class="import-card">
														<h5>Bulk Paste Questions</h5>
														<p class="help-block" style="font-size:12px;">Paste questions and options directly from your document.</p>
														<div class="import-actions">
															<button type="button" class="btn btn-success" onclick="openBulkPasteModal()"><i class="fa fa-paste"></i> Open Paste Tool</button>
														</div>
													</div>
												</div>
											</div>
										</form>

										<form class="form-horizontal" name="questionEntryForm">
											<div class="authoring-section">
												<div class="shared-stimulus-trigger">
													<div>
														<h4><i class="fa fa-clone"></i> Shared Passage, Table, or Image</h4>
														<p class="help-block">Use this when several questions share the same source material.</p>
													</div>
													<button type="button" class="btn btn-info" id="toggleStimulusManager"><i class="fa fa-clone"></i> Open shared material</button>
												</div>
												<div id="stimulusManager" style="display:none; margin-top:15px;">
													<div class="form-group">
														<label class="control-label col-sm-3"><strong>For this question:</strong></label>
														<div class="col-sm-9">
															<select class="form-control" id="sharedStimulusId" name="sharedStimulusId">
																<option value="0">No shared material</option>
															</select>
														</div>
													</div>
													<div class="shared-stimulus-toolbar">
														<select class="form-control" id="stimulusManagerSelect" style="max-width:280px;">
															<option value="0">Create new shared material</option>
														</select>
														<button type="button" class="btn btn-default" id="newStimulusButton"><i class="fa fa-plus"></i> New</button>
														<button type="button" class="btn btn-danger" id="deleteStimulusButton"><i class="fa fa-trash"></i> Delete selected</button>
													</div>
													<div class="row">
														<div class="col-sm-5">
															<label><strong>Title</strong></label>
															<input type="text" class="form-control" id="stimulusTitle" placeholder="e.g. Passage A">
														</div>
														<div class="col-sm-3">
															<label><strong>Type</strong></label>
															<select class="form-control" id="stimulusType">
																<option value="PASSAGE">Passage</option>
																<option value="IMAGE">Image</option>
																<option value="TABLE">Table</option>
																<option value="MIXED">Mixed</option>
															</select>
														</div>
													</div>
													<div class="form-group" style="margin-top:14px;">
														<div class="col-sm-12">
															<label><strong>Shared content</strong></label>
															<textarea class="form-control ckeditor" id="stimulusContent" rows="6"></textarea>
														</div>
													</div>
													<div class="form-group">
														<div class="col-sm-12">
															<label><strong>Apply to existing questions</strong></label>
															<div id="stimulusQuestionChecklist" class="shared-stimulus-question-list">
																<div class="text-muted">Select a test first to load questions.</div>
															</div>
														</div>
													</div>
													<button type="button" class="btn btn-primary" id="saveStimulusButton"><i class="fa fa-save"></i> Save shared material</button>
													<span id="stimulusMessage" style="margin-left:10px; font-weight:bold;"></span>
												</div>
											</div>

											<div class="authoring-section">
												<div class="editor-toolbar-row">
													<label><strong>Objective question</strong> <font id="questionNo" style="font-size:15px;background-color:#EAF7FB; color:#000000;"></font></label>
												</div>
												<textarea class="form-control ckeditor" name="question" id="question" rows="6"></textarea>
												<!-- <div class="split-helper-card" id="splitQuestionHelper">
													<strong><i class="fa fa-magic"></i> Pasted the full question with options?</strong>
													<button type="button" class="btn btn-danger" onclick="splitPastedQuestionIntoOptions()"><i class="fa fa-columns"></i> Split into options</button>
													<span>Lines marked A., B., C., and D. will move into the option fields.</span>
												</div> -->
												<div class="split-helper-card" id="splitQuestionDetected" style="display:none; border-color:#b7eb8f; background:#f6ffed;">
													<strong style="color:#237804;"><i class="fa fa-check-circle"></i> Options detected.</strong>
													<button type="button" class="btn btn-success" onclick="splitPastedQuestionIntoOptions()"><i class="fa fa-bolt"></i> Split now</button>
												</div>
											</div>

											<div class="authoring-section">
												<div class="authoring-section-header">
													<h4><i class="fa fa-list-ol"></i> Options</h4>
													<div class="option-count-toggle">
														<label class="radio-inline"><input type="radio" name="optionCount" id="optionCount4" value="4" checked onchange="setOptionCount(4)"> 4 options</label>
														<label class="radio-inline"><input type="radio" name="optionCount" id="optionCount5" value="5" onchange="setOptionCount(5)"> 5 options</label>
													</div>
												</div>
												<div class="option-grid" id="optionGrid">
													<div class="option-field">
														<label><strong>Option A</strong></label>
														<textarea class="form-control ckeditor" id="optionA" name="optionA" rows="6"></textarea>
													</div>
													<div class="option-field">
														<label><strong>Option B</strong></label>
														<textarea class="form-control ckeditor" id="optionB" name="optionB" rows="6"></textarea>
													</div>
													<div class="option-field">
														<label><strong>Option C</strong></label>
														<textarea class="form-control ckeditor" id="optionC" name="optionC" rows="6"></textarea>
													</div>
													<div class="option-field">
														<label><strong>Option D</strong></label>
														<textarea class="form-control ckeditor" id="optionD" name="optionD" rows="6"></textarea>
													</div>
													<div class="option-field" id="optionEField" style="display:none;">
														<label><strong>Option E</strong></label>
														<textarea class="form-control ckeditor" id="optionE" name="optionE" rows="6"></textarea>
													</div>
												</div>
											</div>

											<div class="authoring-section">
												<div class="row">
													<div class="col-sm-5">
														<div class="answer-card">
															<strong>Correct answer</strong>
															<div class="correct-option-list">
																<label><input type="radio" name="options" id="A" value="A"> Option A</label>
																<label><input type="radio" name="options" id="B" value="B"> Option B</label>
																<label><input type="radio" name="options" id="C" value="C"> Option C</label>
																<label><input type="radio" name="options" id="D" value="D"> Option D</label>
																<label id="optionESelector" style="display:none;"><input type="radio" name="options" id="E" value="E"> Option E</label>
															</div>
															<label><strong>Mark for question</strong></label>
															<input type="text" name="mark" id="mark" class="form-control" placeholder="e.g. 1" onkeypress="return isNumberKey(event,this)">
															<div class="message2" id="message2" style="color:red; font-size:18px; margin-top:10px;" align="center"></div>
															<div class="form-action-row" style="margin-top:14px;">
																<button type="button" class="btn btn-primary" onclick="submitQuestion()"><i class="fa fa-upload"></i> Upload objective question</button>
															</div>
														</div>
													</div>
													<div class="col-sm-7">
														<label><strong>Essay questions</strong></label>
														<textarea class="form-control ckeditor" name="essay" id="essay" rows="6"></textarea>
														<div class="message3" style="color:red; font-size:18px; margin-top:10px;" align="center"></div>
														<div class="form-action-row" style="margin-top:14px;">
															<button type="button" class="btn btn-default" id="btnLoadEssay" onclick="loadEssayForEdit()"><i class="fa fa-pencil"></i> Edit essay</button>
															<button type="button" class="btn btn-primary" onclick="submitEssay()"><i class="fa fa-upload"></i> Upload essay questions</button>
														</div>
													</div>
												</div>
											</div>
										</form>
									</div>
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
				<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#009999;"><center style="font-size:22px;">Preview questions (<font id="toViewTestName" style="color:#ffffff; font-size:18px;"></font>)</center></div>
				<div style="background-color:#E9F1EA; padding:15px; margin:0px 10px 0px 10px;">
				<div class="message4" style="color:green; font-size:18px;" align="center"></div>
								<div class="table-responsive">
									<div style="margin-bottom:10px;">
										<button type="button" class="btn btn-danger" id="btnDeleteSelected" onclick="deleteSelected()"><i class="fa fa-trash"></i> Delete selected</button>
										<button type="button" class="btn btn-danger" id="btnDeleteAll" style="margin-left:8px;" onclick="deleteAll()"><i class="fa fa-trash"></i> Delete all</button>
									</div>
					<table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
						<thead>
							<tr>
												<th width="3%"><center><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></center></th>
								<th width="3%"><center>S/NO</center></th>
								<th width="30%"><center>QUESTION</center></th>
								<th width="10%"><center>SHARED MATERIAL</center></th>
								<th width="10%"><center>OPTION A</center></th>
								<th width="10%"><center>OPTION B</center></th>
								<th width="10%"><center>OPTION C</center></th>
								<th width="10%"><center>OPTION D</center></th>
								<th width="10%"><center>OPTION E</center></th>
								<th width="7%"><center>ANSWER</center></th>
								<th width="6%"><center>MARK</center></th>
								<th width="12%"><center>ACTION</center></th>
							</tr>
						</thead>
						<tbody>
										
						</tbody>
					</table>
				</div>
				<form method="post" action="editQuestionForm.php" id="submitToEditQuestionForm">
					<input type="hidden" name="testIdToEdit" id="testIdToEdit">
					<input type="hidden" name="questionIdToEdit" id="questionIdToEdit">
					<input type="hidden" name="frmAddQuestOrViewQuestStatus" id="frmAddQuestOrViewQuestStatus">
				</form>
				<!-- End of table-responsive -->
				</div>
				<br>
				<hr>
			</div>	<!-- End of modal content-->
		</div><!-- End of modal to add/subtract time for all or selected students-->
		<!-- Delete confirmation modal for questions -->
			<div id="myModalDeleteQuestions" class="modal1" style="display:none; align-items:center; justify-content:center; padding-top:0; z-index:2200;">
			<div class="modal-content" style="width:100%; max-width:560px; box-shadow:0 10px 30px rgba(0,0,0,0.25);">
				<span class="close1" onclick="closeDeleteQuestionsModal()">&times;</span>
				<div style="padding:15px; color:#fff; margin:10px 10px 0px 10px; background-color:#c9302c;"><center style="font-size:20px;">Confirm Delete Questions</center></div>
				<div style="background-color:#FDF2F2; padding:15px; margin:10px; border:1px solid #f5c6cb;">
					<div id="deleteQuestionsModalBody" style="color:#721c24;"></div>
					<input type="text" id="deleteQuestionsConfirmInput" class="form-control" placeholder="Type DELETE to confirm" onkeyup="onDeleteQuestionsInput()" style="margin:10px 0;" />
					<div id="deleteQuestionsError" style="color:#a94442; font-size:14px; margin-bottom:10px;"></div>
					<div id="deleteQuestionsMessage" style="color:#31708f; font-size:14px; margin-bottom:10px;"></div>
					<button type="button" id="confirmDeleteQuestionsBtn" class="btn btn-danger" onclick="confirmDeleteQuestions()" disabled>Confirm Delete</button>
					<button type="button" class="btn btn-default" onclick="closeDeleteQuestionsModal()" style="margin-left:8px;">Cancel</button>
				</div>
			</div>
		</div>
			<!--main content end-->
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
   
   
    <!-- custom form component script for this page-->
    <script src="js/form-component.js"></script>
    <!-- custome script for all page -->
    <script src="js/scripts.js"></script>
	<script src="js/question-authoring.js"></script>
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
		var preselectedQuestionTestId = <?php echo (int) $preselectedTestId; ?>;
		var sharedStimulusCache = [];
		var questionReferenceCache = [];
		var selectedOptionCount = 4;

		function setOptionCount(count)
		{
			selectedOptionCount = (count === 5) ? 5 : 4;
			$('#optionCount4').prop('checked', selectedOptionCount === 4);
			$('#optionCount5').prop('checked', selectedOptionCount === 5);
			$('#optionEField').toggle(selectedOptionCount === 5);
			$('#optionESelector').toggle(selectedOptionCount === 5);
			if (selectedOptionCount === 4 && document.getElementById('E').checked) {
				document.getElementById('E').checked = false;
			}
		}

		function updateQuestionSetupHint()
		{
			var selectedOption = $('#testName option:selected');
			var hasStudents = selectedOption.data('students-added');
			var setupMessage = selectedOption.data('setup-message');

			if (!selectedOption.val()) {
				$('#questionSetupHint').html('');
				return;
			}

			if (String(hasStudents) === '1') {
				$('#questionSetupHint').css('color', '#0a7f5a').html('<i class="fa fa-check-circle"></i> ' + setupMessage);
			}
			else {
				$('#questionSetupHint').css('color', '#a94442').html('<i class="fa fa-info-circle"></i> ' + setupMessage + ' Use the "Manage Test Access" page first.');
			}
		}

		DLHSQuestionAuthoring.createEditor('question', 'uploadEditorImage.php', { height: 300 });
		DLHSQuestionAuthoring.createEditor('optionA', 'uploadEditorImage.php', { height: 220 });
		DLHSQuestionAuthoring.createEditor('optionB', 'uploadEditorImage.php', { height: 220 });
		DLHSQuestionAuthoring.createEditor('optionC', 'uploadEditorImage.php', { height: 220 });
		DLHSQuestionAuthoring.createEditor('optionD', 'uploadEditorImage.php', { height: 220 });
		DLHSQuestionAuthoring.createEditor('optionE', 'uploadEditorImage.php', { height: 220 });
		DLHSQuestionAuthoring.createEditor('essay', 'uploadEditorImage.php', { height: 300 });
		DLHSQuestionAuthoring.createEditor('stimulusContent', 'uploadEditorImage.php', { height: 260 });

		function setStimulusMessage(message, isError)
		{
			$('#stimulusMessage').css('color', isError ? '#a94442' : '#0a7f5a').html(message || '');
		}

		function renderStimulusOptions(selectedStimulusId)
		{
			var options = '<option value="0">No shared material</option>';
			var managerOptions = '<option value="0">Create new shared material</option>';

			sharedStimulusCache.forEach(function(stimulus) {
				var isSelected = String(selectedStimulusId || 0) === String(stimulus.stimulusId) ? ' selected' : '';
				options += '<option value="' + stimulus.stimulusId + '"' + isSelected + '>' + DLHSQuestionAuthoring.escapeHtml(stimulus.stimulusLabel) + '</option>';
				managerOptions += '<option value="' + stimulus.stimulusId + '">' + DLHSQuestionAuthoring.escapeHtml(stimulus.stimulusLabel) + '</option>';
			});

			$('#sharedStimulusId').html(options);
			$('#stimulusManagerSelect').html(managerOptions);
			if (selectedStimulusId) {
				$('#stimulusManagerSelect').val(String(selectedStimulusId));
			}
		}

		function getSelectedStimulus()
		{
			var selectedId = $('#stimulusManagerSelect').val() || $('#sharedStimulusId').val() || '0';
			var selected = null;
			sharedStimulusCache.forEach(function(stimulus) {
				if (String(stimulus.stimulusId) === String(selectedId)) {
					selected = stimulus;
				}
			});
			return selected;
		}

		function renderStimulusQuestionChecklist(selectedIds)
		{
			selectedIds = selectedIds || [];
			if (!questionReferenceCache.length) {
				$('#stimulusQuestionChecklist').html('<div class="text-muted">No objective questions have been added yet. Save the shared material now, then link it after you add questions.</div>');
				return;
			}

			var html = '';
			questionReferenceCache.forEach(function(questionRef, index) {
				var checked = selectedIds.indexOf(String(questionRef.questionId)) !== -1 || selectedIds.indexOf(questionRef.questionId) !== -1 ? ' checked' : '';
				html += '<label class="shared-stimulus-question-item"><input type="checkbox" class="stimulus-question-link" value="' + questionRef.questionId + '"' + checked + '> Q' + (index + 1) + ': ' + DLHSQuestionAuthoring.escapeHtml(questionRef.questionText) + '</label>';
			});
			$('#stimulusQuestionChecklist').html(html);
		}

		function clearStimulusManager()
		{
			$('#stimulusManagerSelect').val('0');
			$('#stimulusTitle').val('');
			$('#stimulusType').val('PASSAGE');
			CKEDITOR.instances.stimulusContent.setData('');
			renderStimulusQuestionChecklist([]);
			setStimulusMessage('', false);
		}

		function loadStimulusIntoManager(stimulusId)
		{
			if (!stimulusId || String(stimulusId) === '0') {
				clearStimulusManager();
				return;
			}

			var selected = null;
			sharedStimulusCache.forEach(function(stimulus) {
				if (String(stimulus.stimulusId) === String(stimulusId)) {
					selected = stimulus;
				}
			});

			if (!selected) {
				clearStimulusManager();
				return;
			}

			$('#stimulusManagerSelect').val(String(selected.stimulusId));
			$('#sharedStimulusId').val(String(selected.stimulusId));
			$('#stimulusTitle').val(selected.stimulusTitle || '');
			$('#stimulusType').val(selected.stimulusType || 'PASSAGE');
			CKEDITOR.instances.stimulusContent.setData(selected.stimulusContent || '');
			renderStimulusQuestionChecklist(selected.questionIds || []);
			setStimulusMessage('', false);
		}

		function loadSharedStimuli(testId, selectedStimulusId)
		{
			if (!testId) {
				sharedStimulusCache = [];
				renderStimulusOptions(0);
				clearStimulusManager();
				return;
			}

			$.ajax({
				url: 'getSharedQuestionStimuli.php',
				type: 'POST',
				dataType: 'json',
				data: {testId: testId},
				success: function(response) {
					sharedStimulusCache = response && response.success && response.stimuli ? response.stimuli : [];
					renderStimulusOptions(selectedStimulusId || $('#sharedStimulusId').val() || 0);
					if (selectedStimulusId) {
						loadStimulusIntoManager(selectedStimulusId);
					} else {
						renderStimulusQuestionChecklist([]);
					}
				}
			});
		}

		function loadQuestionReferenceList(testId)
		{
			if (!testId) {
				questionReferenceCache = [];
				renderStimulusQuestionChecklist([]);
				return;
			}

			$.ajax({
				url: 'getTestQuestion.php',
				type: 'POST',
				dataType: 'json',
				data: {testId : testId},
				success: function(response) {
					questionReferenceCache = [];
					(response || []).forEach(function(item) {
						questionReferenceCache.push({
							questionId: item.questionId,
							questionText: DLHSQuestionAuthoring.normalizeText(DLHSQuestionAuthoring.htmlToText(item.question)).substring(0, 120)
						});
					});
					var selectedStimulus = getSelectedStimulus();
					renderStimulusQuestionChecklist(selectedStimulus ? (selectedStimulus.questionIds || []) : []);
				}
			});
		}

		function saveSharedStimulus()
		{
			var testId = $('#testName').val();
			var stimulusId = $('#stimulusManagerSelect').val();
			var questionIds = [];
			$('.stimulus-question-link:checked').each(function() {
				questionIds.push($(this).val());
			});

			if (!testId) {
				setStimulusMessage('<i class="fa fa-info-circle"></i> Select a test first.', true);
				return;
			}

			$.ajax({
				url: 'saveSharedQuestionStimulus.php',
				type: 'POST',
				dataType: 'json',
				data: {
					testId: testId,
					stimulusId: stimulusId,
					stimulusTitle: $('#stimulusTitle').val(),
					stimulusType: $('#stimulusType').val(),
					stimulusContent: CKEDITOR.instances.stimulusContent.getData(),
					questionIds: questionIds
				},
				success: function(response) {
					if (response && response.success) {
						sharedStimulusCache = response.stimuli || [];
						renderStimulusOptions(response.stimulusId);
						loadStimulusIntoManager(response.stimulusId);
						setStimulusMessage('<i class="fa fa-check"></i> ' + response.message, false);
					} else {
						setStimulusMessage('<i class="fa fa-times"></i> ' + (response && response.message ? response.message : 'Shared material could not be saved.'), true);
					}
				},
				error: function() {
					setStimulusMessage('<i class="fa fa-times"></i> Shared material could not be saved.', true);
				}
			});
		}

		function deleteSharedStimulus()
		{
			var testId = $('#testName').val();
			var stimulusId = $('#stimulusManagerSelect').val();

			if (!testId || !stimulusId || String(stimulusId) === '0') {
				setStimulusMessage('<i class="fa fa-info-circle"></i> Select a shared material to delete.', true);
				return;
			}

			dlhsConfirm('Delete this shared material and remove it from every linked question?', function() {
				$.ajax({
					url: 'deleteSharedQuestionStimulus.php',
					type: 'POST',
					dataType: 'json',
					data: {
						testId: testId,
						stimulusId: stimulusId
					},
					success: function(response) {
						if (response && response.success) {
							sharedStimulusCache = response.stimuli || [];
							renderStimulusOptions(0);
							clearStimulusManager();
							setStimulusMessage('<i class="fa fa-check"></i> ' + response.message, false);
						} else {
							setStimulusMessage('<i class="fa fa-times"></i> ' + (response && response.message ? response.message : 'Shared material could not be deleted.'), true);
						}
					},
					error: function() {
						setStimulusMessage('<i class="fa fa-times"></i> Shared material could not be deleted.', true);
					}
				});
			});
		}
		
		//function to fetch the next question number
		function fetchNextQuestionNumber(testId)
		{
			$.ajax({
				url: "getNextQuestNo.php",
				type: "POST",       
				data: {testId:testId},
				success: function (html) {           								                         
					$('#questionNo').html(" You are currently entering question: <b>"+html+"</b>");
				}
			});
		}

		//View questions based on change of test select
		$("#testName").on('change', function() {
			var testId = document.getElementById('testName').value;
			updateQuestionSetupHint();
			fetchNextQuestionNumber(testId);
			checkEssayAvailability(testId);
			loadSharedStimuli(testId, 0);
			loadQuestionReferenceList(testId);
		});

		$(function() {
			setOptionCount(4);
			updateQuestionSetupHint();
			if (preselectedQuestionTestId > 0 && $('#testName').val()) {
				fetchNextQuestionNumber($('#testName').val());
				checkEssayAvailability($('#testName').val());
				loadSharedStimuli($('#testName').val(), 0);
				loadQuestionReferenceList($('#testName').val());
			}

			$('#toggleStimulusManager').on('click', function() {
				$('#stimulusManager').slideToggle(150, function() {
					// Ensure CKEditor is initialized for stimulus content when opened
					if (typeof CKEDITOR !== 'undefined' && !CKEDITOR.instances.stimulusContent) {
						DLHSQuestionAuthoring.createEditor('stimulusContent', 'uploadEditorImage.php', { height: 260 });
					}
				});
			});
			$('#saveStimulusButton').on('click', saveSharedStimulus);
			$('#deleteStimulusButton').on('click', deleteSharedStimulus);
			$('#newStimulusButton').on('click', function() {
				clearStimulusManager();
			});
			$('#stimulusManagerSelect').on('change', function() {
				loadStimulusIntoManager($(this).val());
			});
			$('#sharedStimulusId').on('change', function() {
				if ($(this).val() !== '0') {
					loadStimulusIntoManager($(this).val());
				}
			});
			// Helper: strip leading question numbers like "1.", "Q1.", "(1)", "1)" from plain text
			function dlhsStripLeadingQuestionNumber(html) {
				// Convert to temp element to get text, then strip from start of first text node
				var tmp = $('<div>').html(html);
				var firstText = tmp.find('*').addBack().contents().filter(function(){ return this.nodeType === 3; }).first();
				if (firstText.length) {
					// Strip patterns: "1.", "1)", "(1)", "Q1.", "Q1 ", optional trailing space
					firstText[0].nodeValue = firstText[0].nodeValue.replace(/^\s*(?:Q?\d+[\.\)]\s*|\(\d+\)\s*)/i, '');
				}
				return tmp.html();
			}

			// Helper: strip leading option labels like "A.", "A)", "(A)" from option fields
			function dlhsStripLeadingOptionLabel(html) {
				var tmp = $('<div>').html(html);
				var firstText = tmp.find('*').addBack().contents().filter(function(){ return this.nodeType === 3; }).first();
				if (firstText.length) {
					firstText[0].nodeValue = firstText[0].nodeValue.replace(/^\s*(?:[A-Ea-e][\.\)]\s*|\([A-Ea-e]\)\s*)/i, '');
				}
				return tmp.html();
			}

			setTimeout(function() {
				if (CKEDITOR.instances.question) {
					CKEDITOR.instances.question.on('change', updateSplitAssistVisibility);
					CKEDITOR.instances.question.on('afterPaste', function() {
						// Strip leading question number after paste
						setTimeout(function() {
							var cleaned = dlhsStripLeadingQuestionNumber(CKEDITOR.instances.question.getData());
							if (cleaned !== CKEDITOR.instances.question.getData()) {
								CKEDITOR.instances.question.setData(cleaned);
							}
							updateSplitAssistVisibility();
						}, 200);
					});
					updateSplitAssistVisibility();
				}

				// Strip option letter labels from option fields on paste
				var optionFields = ['optionA', 'optionB', 'optionC', 'optionD', 'optionE'];
				optionFields.forEach(function(fieldId) {
					if (CKEDITOR.instances[fieldId]) {
						CKEDITOR.instances[fieldId].on('afterPaste', function() {
							var inst = CKEDITOR.instances[fieldId];
							setTimeout(function() {
								var cleaned = dlhsStripLeadingOptionLabel(inst.getData());
								if (cleaned !== inst.getData()) {
									inst.setData(cleaned);
								}
							}, 200);
						});
					}
				});
			}, 600);
		});
				function checkEssayAvailability(testId){
					if(!testId){ $('#essayAvailability').html(''); return; }
					$.ajax({
					url: 'getEssayQuestion.php',
					type: 'POST',
					data: {testId : testId},
					dataType: 'JSON',
					success: function(response){
						if(response && response.length > 0){
							$('#essayAvailability').css('color','#0a7f5a').html('Essay available');
							// Prefill the CKEditor with the saved essay HTML so staff can edit it
							try {
								if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances && CKEDITOR.instances.essay) {
									CKEDITOR.instances.essay.setData(response[0].question);
								} else {
									// If CKEditor isn't ready yet, set the textarea value as a fallback
									$('#essay').val(response[0].question);
								}
							} catch (e) {
								console.error('Error setting essay editor data', e);
							}
						}else{
							$('#essayAvailability').css('color','#a94442').html('No essay uploaded');
							// Clear editor when no essay exists
							try {
								if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances && CKEDITOR.instances.essay) {
									CKEDITOR.instances.essay.setData('');
								} else {
									$('#essay').val('');
								}
							} catch (e) {
								console.error('Error clearing essay editor', e);
							}
						}
						}
					});
				}
			function loadEssayForEdit()
			{
				var testId = document.getElementById('testName').value;
				if (!testId) {
					$('.message3').html('<i class="fa fa-info-circle"></i> Please select Test name');
					return;
				}
				$('.message3').html('<i class="fa fa-spinner fa-spin"></i> Loading essay...');
				$.ajax({
					url: 'getEssayQuestion.php',
					type: 'POST',
					data: {testId : testId},
					dataType: 'JSON',
					success: function(response) {
						if(response && response.length > 0) {
							CKEDITOR.instances.essay.setData(response[0].question);
							$('.message3').html('');
							$('#essayAvailability').css('color','#0a7f5a').html('Essay available');
						} else {
							CKEDITOR.instances.essay.setData('');
							$('.message3').html('<i class="fa fa-info-circle"></i> No essay has been uploaded for this test yet.');
							$('#essayAvailability').css('color','#a94442').html('No essay uploaded');
						}
					},
					error: function() {
				$('.message3').html('<i class="fa fa-times"></i> Could not load essay. Please try again.');
					}
				});
			}
			function splitPastedQuestionIntoOptions()
			{
				var editorHtml = CKEDITOR.instances.question.getData();
				var parsed = null;

				// Strategy 1: Try handleSplitOptions (split-based regex parsing)
				var parsedPlain = DLHSQuestionAuthoring.handleSplitOptions(editorHtml, {
					editor: CKEDITOR.instances.question,
					toast: function() {} // suppress toast, we handle errors below
				});

				if (parsedPlain) {
					parsed = {
						questionHtml: DLHSQuestionAuthoring.textToEditorHtml(parsedPlain.question_text),
						optionA: DLHSQuestionAuthoring.textToEditorHtml(parsedPlain.option_a),
						optionB: DLHSQuestionAuthoring.textToEditorHtml(parsedPlain.option_b),
						optionC: DLHSQuestionAuthoring.textToEditorHtml(parsedPlain.option_c),
						optionD: DLHSQuestionAuthoring.textToEditorHtml(parsedPlain.option_d),
						optionE: parsedPlain.option_e ? DLHSQuestionAuthoring.textToEditorHtml(parsedPlain.option_e) : ''
					};
				}

				// Strategy 2: Fall back to parseQuestionBlock (multi-strategy line-based parsing)
				if (!parsed) {
					parsed = DLHSQuestionAuthoring.parseQuestionBlock(editorHtml);
				}

				if (!parsed) {
					$('.message2').html('<i class="fa fa-info-circle"></i> Could not split the question. Please ensure options are marked with <b>A.</b>, <b>(A)</b>, or <b>[A]</b> through D (or E).');
					console.error('handleSplitOptions: Could not parse question text into options. Input HTML:', editorHtml);
					return;
				}

				// Update the question editor to contain only the question text
				CKEDITOR.instances.question.setData(parsed.questionHtml);

				CKEDITOR.instances.optionA.setData(parsed.optionA);
				CKEDITOR.instances.optionB.setData(parsed.optionB);
				CKEDITOR.instances.optionC.setData(parsed.optionC);
			CKEDITOR.instances.optionD.setData(parsed.optionD);
			if (parsed.optionE && parsed.optionE.trim() !== '') {
				setOptionCount(5);
				CKEDITOR.instances.optionE.setData(parsed.optionE);
			} else {
				setOptionCount(selectedOptionCount); // keep current selection
				CKEDITOR.instances.optionE.setData('');
			}

			$('.message1').html('<i class="fa fa-check"></i> Options were moved into the proper fields. Please choose the correct answer and save.').fadeIn('slow');
			$('.message2').html('');
			updateSplitAssistVisibility();
		}

		function updateSplitAssistVisibility()
		{
			var detected = DLHSQuestionAuthoring.parseQuestionBlock(CKEDITOR.instances.question.getData());
			$('#splitQuestionDetected').toggle(!!detected);
		}

		function importWordDocument(target)
		{
			var fileInput = document.getElementById('wordImportFile');
			if (!fileInput || !fileInput.files || !fileInput.files.length) {
				$('#wordImportMessage').css('color', '#a94442').html('<i class="fa fa-times"></i> Select a Word .docx file first.');
				return;
			}

			var formData = new FormData();
			formData.append('file', fileInput.files[0]);
			$('#wordImportMessage').css('color', '#0a5a88').html('<i class="fa fa-spinner fa-spin"></i> Importing document...');

			$.ajax({
				url: 'importWordDocument.php',
				type: 'POST',
				data: formData,
				processData: false,
				contentType: false,
				dataType: 'json',
				success: function(response) {
					if (!(response && response.success && response.html)) {
						$('#wordImportMessage').css('color', '#a94442').html('<i class="fa fa-times"></i> ' + (response && response.message ? response.message : 'The Word file could not be imported.'));
						return;
					}

					if (target === 'essay') {
						CKEDITOR.instances.essay.setData(response.html);
						$('#wordImportMessage').css('color', '#0a7f5a').html('<i class="fa fa-check"></i> Word content loaded into the essay editor.');
						return;
					}

					CKEDITOR.instances.question.setData(response.html, function() {
						updateSplitAssistVisibility();
						var parsed = DLHSQuestionAuthoring.parseQuestionBlock(response.html);
						if (parsed) {
							splitPastedQuestionIntoOptions();
							$('#wordImportMessage').css('color', '#0a7f5a').html('<i class="fa fa-check"></i> Word content imported and split into question and options.');
						} else {
							$('#wordImportMessage').css('color', '#0a7f5a').html('<i class="fa fa-check"></i> Word content loaded into the question editor.');
						}
					});
				},
				error: function() {
					$('#wordImportMessage').css('color', '#a94442').html('<i class="fa fa-times"></i> The Word file could not be imported.');
				}
			});
		}
		//if submit button is clicked for adding an objective question.
		function submitQuestion()
		{       
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
				  	  
			let testId = document.getElementById('testName').value;
			let question = CKEDITOR.instances.question.getData().replaceAll('&nbsp;', '');
			let optionA = CKEDITOR.instances.optionA.getData().replaceAll('&nbsp;', '');
			let optionB = CKEDITOR.instances.optionB.getData().replaceAll('&nbsp;', '');
			let optionC = CKEDITOR.instances.optionC.getData().replaceAll('&nbsp;', '');
			let optionD = CKEDITOR.instances.optionD.getData().replaceAll('&nbsp;', '');
			let optionE = CKEDITOR.instances.optionE.getData().replaceAll('&nbsp;', '');
			let checkRadio = document.querySelector('input[name="options"]:checked'); 
			let mark = $('input[name=mark]').val();
			let sharedStimulusId = $('#sharedStimulusId').val() || 0;
			let optionCount = ($('input[name="optionCount"]:checked').val() === '5') ? 5 : 4;
			selectedOptionCount = optionCount;
			
			if (testId=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select Test name')
			}	
			else if (question.length == 0)
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter question')
			}
			else if (optionA.length == 0)
			{
				$('.message2').html(' <i class="fa fa-info-circle"></i> Please enter option A')
			}
			else if (optionB.length == 0)
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter option B')
			}
			else if (optionC.length == 0)
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter option C')
			}
			else if (optionD.length == 0)
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter option D')
			}
			else if (optionCount === 5 && optionE.length == 0)
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter option E')
			}
			else if (checkRadio == null)
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please select correct answer')
			}
			else if (mark=="")
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Please enter mark for question')
			}
			else if (DLHSQuestionAuthoring.looksLikePlaceholder(optionA) ||
				DLHSQuestionAuthoring.looksLikePlaceholder(optionB) ||
				DLHSQuestionAuthoring.looksLikePlaceholder(optionC) ||
				DLHSQuestionAuthoring.looksLikePlaceholder(optionD) ||
				(optionCount === 5 && DLHSQuestionAuthoring.looksLikePlaceholder(optionE)))
			{
				$('.message2').html('<i class="fa fa-info-circle"></i> Enter the real option text in ' + (optionCount === 5 ? 'A-E' : 'A-D') + '. If you pasted the full block into the question area, use "Split pasted question into options".')
			}
			else
			{						
				$.ajax({
					url: "addQuestion.php",
					type: "POST",       
					data: {testId:testId, question:question, optionA:optionA, optionB:optionB, optionC:optionC, optionD:optionD, optionE:optionCount === 5 ? optionE : '', optionCount:optionCount, selectedValue:checkRadio.value, mark:mark, sharedStimulusId:sharedStimulusId},
					success: function (html) {             								
						if (html==0)	//If session is expired.
						{                              
							window.location.replace("logout.php");
						}
						else if (html==1)	//If question successfully added
						{                              
							$('.message1').html('<i class="fa fa-check"></i> Question successfully added.').fadeIn('slow');
							document.getElementById("A").checked = false;
							document.getElementById("B").checked = false;
							document.getElementById("C").checked = false;
							document.getElementById("D").checked = false;
							if (document.getElementById("E")) { document.getElementById("E").checked = false; }
							CKEDITOR.instances.question.setData( '', function() {this.updateElement();})
							CKEDITOR.instances.optionA.setData( '', function() {this.updateElement();})
							CKEDITOR.instances.optionB.setData( '', function() {this.updateElement();})
							CKEDITOR.instances.optionC.setData( '', function() {this.updateElement();})
							CKEDITOR.instances.optionD.setData( '', function() {this.updateElement();})
							CKEDITOR.instances.optionE.setData( '', function() {this.updateElement();})
							document. getElementById("mark").value = "";
							setOptionCount(optionCount);
							window.scrollTo(0, 0);
							fetchNextQuestionNumber(testId);
							loadSharedStimuli(testId, sharedStimulusId);
							loadQuestionReferenceList(testId);
						}
						else if (html==2)	//If insertion is unsuccessful	
						{                              
							$('.message2').html('<i class="fa fa-times"></i> Could not add Question. Please try again.').fadeIn('slow');
						}
						else if (html==3)	//If class already exists.
						{                              
							$('.message2').html('<i class="fa fa-times"></i> Oops! Something went wrong. Please contact Admin').fadeIn('slow');
						}
						else if (html==4)
						{
							$('.message2').html('<i class="fa fa-times"></i> Add students to this test before adding questions.').fadeIn('slow');
						}
						else if (html==5)
						{
							$('.message2').html('<i class="fa fa-times"></i> Enter the real option text in A-E. The system no longer accepts only option letters.').fadeIn('slow');
						}
					}
				});
			}
		}
		//End of function adding an objective question
		
		//if submit button is clicked for adding an essay question.
		function submitEssay()
		{       
			$('.message1').html('');
			$('.message2').html('');
			$('.message3').html('');
				  	  
			let testId = document.getElementById('testName').value;
			let essayQuestion = CKEDITOR.instances.essay.getData().replaceAll('&nbsp;', '');			
			
			if (testId=="")
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> Please select Test name')
			}	
			else if (essayQuestion.length == 0)
			{
				$('.message3').html('<i class="fa fa-info-circle"></i> Please enter essay question(s)')
			}
			else
			{		
				$.ajax({
					url: "addEssayQuestion.php",
					type: "POST",       
					data: {testId:testId, essayQuestion:essayQuestion},
					success: function (html) {             								
						if (html==0)	//If session is expired.
						{                              
							window.location.replace("logout.php");
						}
						else if (html==1)	//If question successfully added
						{                              
							$('.message1').html('<i class="fa fa-check"></i> Essay Question successfully added.').fadeIn('slow');
							CKEDITOR.instances.essay.setData( '', function() {this.updateElement();})
							window.scrollTo(0, 0);
						}
						else if (html==2)	//If insertion is unsuccessful	
						{                              
							$('.message1').html('<i class="fa fa-check"></i> Essay Question successfully updated.').fadeIn('slow');
							CKEDITOR.instances.essay.setData( '', function() {this.updateElement();})
							window.scrollTo(0, 0);
						}
						else if (html==3)	//If class already exists.
						{                              
							$('.message3').html('<i class="fa fa-times"></i> Oops! Something went wrong. Please contact Admin').fadeIn('slow');
						}
						else if (html==4)
						{
							$('.message3').html('<i class="fa fa-times"></i> Add students to this test before uploading essay questions.').fadeIn('slow');
						}
					}
				});
			}
		}

		//Previewing the entered questions of a test
		//View questions based on change of test select
		function previewTestQuestions()
		{			
			$('.message4').html('');
			let selectedTestId = document.getElementById('testName').value;
			
			if(selectedTestId == "")
			{
				dlhsAlert("Please select test first", "error");
			}
			else
			{
				let testName = $('#testName option:selected').text();
				$('#example').DataTable().clear().destroy();
				$.ajax({
					url: 'getTestQuestion.php',
					type: 'POST',
					data: {testId : selectedTestId},
					dataType: 'JSON',
					success: function(response)
					{
						var len = response.length;
						for(var i=0; i<len; i++){
							var questionId = response[i].questionId;
							var question = response[i].question;
							var stimulusLabel = response[i].stimulusLabel || '';
							var optionA = response[i].optionA;
							var optionB = response[i].optionB;
							var optionC = response[i].optionC;						
							var optionD = response[i].optionD;												
							var optionE = response[i].optionE;												
							var correctOption = response[i].correctOption;										
							var markForQuestion = response[i].markForQuestion;										
							var solvedSolution = response[i].solvedSolution;										
							
									var tr_str = "<tr>" +
										"<td width='3%'><center><input type='checkbox' class='row-select' data-question-id='"+questionId+"'></center></td>" +
										"<td width='3%'><center>" + (i+1) + "</center></td>" +
								"<td width='30%'>" + question + "</td>" +
								"<td width='10%'><center>" + stimulusLabel + "</center></td>" +
								"<td width='10%'><center>" + optionA + "</center></td>" +
								"<td width='10%'><center>" + optionB + "</center></td>" +
								"<td width='10%'><center>" + optionC + "</center></td>" +
								"<td width='10%'><center>" + optionD + "</center></td>" +
								"<td width='10%'><center>" + (optionE || '') + "</center></td>" +
								"<td width='7%'><center>" + correctOption + "</center></td>" +
								"<td width='6%'><center>" + markForQuestion + "</center></td>" +
								"<td align='center' width='12%'><a onclick='beforeSubmitToEditTest(\""+selectedTestId+"\",\""+questionId+"\")' title='Edit this question' style='cursor:pointer;'><i class='fa fa-pencil-square-o' aria-hidden='true' style='color:blue;'></i></a> &nbsp; &nbsp;<a onClick='deleteQuestion(\""+selectedTestId+"\",\""+questionId+"\")' title='Delete this question' style='cursor:pointer;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a></td>" +
								
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
						$('#toViewTestName').html(testName);
						
					}
				});
				var modal1 = document.getElementById("myModal1");				
				modal1.style.display = "block";
			}
			
		}
		//calling the data table function
		var modal1 = document.getElementById("myModal1");	
		var span1 = document.getElementsByClassName("close1")[0];
            
		var closeSpans = document.getElementsByClassName("close1");
		for (var i = 0; i < closeSpans.length; i++) {
			closeSpans[i].onclick = function() {
				var modals = ['myModal1', 'myModal2', 'myModal3', 'myModal4', 'myModal5', 'myModal6', 'myModal7', 'myModal8', 'myModalDeleteQuestions'];
				modals.forEach(function(mId) {
					var m = document.getElementById(mId);
					if (m) m.style.display = "none";
				});
			}
		}

		//Refreshing the table after deleting a question
		function refreshQuestionsTable(testId)
		{
			$('.message4').html('');
			$('#example').DataTable().clear().destroy();
			$.ajax({
				url: 'getTestQuestion.php',
				type: 'POST',
				data: {testId : testId},
				dataType: 'JSON',
				success: function(response)
				{
					var len = response.length;
						for(var i=0; i<len; i++){
							var questionId = response[i].questionId;
							var question = response[i].question;
							var stimulusLabel = response[i].stimulusLabel || '';
							var optionA = response[i].optionA;
							var optionB = response[i].optionB;
							var optionC = response[i].optionC;						
							var optionD = response[i].optionD;												
							var optionE = response[i].optionE;												
							var correctOption = response[i].correctOption;										
							var markForQuestion = response[i].markForQuestion;										
							var solvedSolution = response[i].solvedSolution;										
							
									var tr_str = "<tr>" +
										"<td width='3%'><center><input type='checkbox' class='row-select' data-question-id='"+questionId+"'></center></td>" +
										"<td width='3%'><center>" + (i+1) + "</center></td>" +
								"<td width='30%'>" + question + "</td>" +
								"<td width='10%'><center>" + stimulusLabel + "</center></td>" +
								"<td width='10%'><center>" + optionA + "</center></td>" +
								"<td width='10%'><center>" + optionB + "</center></td>" +
								"<td width='10%'><center>" + optionC + "</center></td>" +
								"<td width='10%'><center>" + optionD + "</center></td>" +
								"<td width='10%'><center>" + (optionE || '') + "</center></td>" +
								"<td width='7%'><center>" + correctOption + "</center></td>" +
								"<td width='6%'><center>" + markForQuestion + "</center></td>" +
								"<td align='center' width='12%'><a onclick='beforeSubmitToEditTest(\""+testId+"\",\""+questionId+"\")' title='Edit this question' style='cursor:pointer;'><i class='fa fa-pencil-square-o' aria-hidden='true' style='color:blue;'></i></a> &nbsp; &nbsp;<a onClick='deleteQuestion(\""+testId+"\",\""+questionId+"\")' title='Delete this question' style='cursor:pointer;'><i class='fa fa-trash' aria-hidden='true' style='color:red;'></i></a></td>" +
								
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

		function toggleSelectAll(master){
			var checked = master.checked;
			$(".row-select").prop('checked', checked);
		}
		function collectSelectedIds(){
			var ids = [];
			$(".row-select:checked").each(function(){
				ids.push($(this).data('question-id'));
			});
			return ids;
		}
		var QDEL_MODE = null; var QDEL_IDS = [];
		function deleteSelected(){
			$('.message4').html('');
			var testId = document.getElementById('testName').value;
			if(testId == ""){ dlhsAlert('Please select test first', 'error'); return; }
			var ids = collectSelectedIds();
			if(ids.length === 0){ dlhsAlert('Please select at least one question', 'error'); return; }
			QDEL_MODE = 'selected'; QDEL_IDS = ids; openDeleteQuestionsModal(ids.length);
		}
		function deleteAll(){
			$('.message4').html('');
			var testId = document.getElementById('testName').value;
			if(testId == ""){ dlhsAlert('Please select test first', 'error'); return; }
			QDEL_MODE = 'all'; QDEL_IDS = []; openDeleteQuestionsModal(null);
		}
		function generateConfirmationCode(){
			var chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			var result = '';
			for (var i = 0; i < 8; i++) { result += chars.charAt(Math.floor(Math.random() * chars.length)); }
			return result;
		}
		var QDEL_CODE = null;
		function openDeleteQuestionsModal(count){
			var msg = (QDEL_MODE === 'selected') ? ('You are about to permanently delete '+count+' selected question(s).') : 'You are about to permanently delete ALL questions for this test.';
			QDEL_CODE = generateConfirmationCode();
			var body = ''+
				'<p style="margin:0 0 10px 0;">'+msg+'</p>'+
				'<div style="background-color:#fff3cd; border:1px solid #ffeaa7; border-radius:5px; padding:10px; margin-bottom:10px;">'+
				'<div style="color:#856404; font-weight:bold;">Security confirmation required</div>'+
				'<div>Type this code: <span id="qdelCodeDisplay" style="color:#dc3545; font-family:monospace; padding:6px 10px; border:2px solid #dc3545; border-radius:4px; letter-spacing:2px;">'+QDEL_CODE+'</span></div>'+
				'</div>'+
				'<p style="color:#721c24; font-weight:bold; margin:0 0 10px 0;">This action cannot be undone.</p>';
			document.getElementById('deleteQuestionsModalBody').innerHTML = body;
			document.getElementById('deleteQuestionsConfirmInput').value = '';
			document.getElementById('deleteQuestionsError').innerHTML = '';
			document.getElementById('deleteQuestionsMessage').innerHTML = '';
			document.getElementById('confirmDeleteQuestionsBtn').disabled = true;
			document.getElementById('myModalDeleteQuestions').style.display = 'block';
		}
		function closeDeleteQuestionsModal(){ document.getElementById('myModalDeleteQuestions').style.display = 'none'; }
		function onDeleteQuestionsInput(){
			var val = document.getElementById('deleteQuestionsConfirmInput').value.trim();
			var ok = (QDEL_CODE && val === QDEL_CODE);
			document.getElementById('confirmDeleteQuestionsBtn').disabled = !ok;
			document.getElementById('deleteQuestionsError').innerHTML = ok ? '' : '';
		}
		function confirmDeleteQuestions(){
			var testId = document.getElementById('testName').value;
			var val = document.getElementById('deleteQuestionsConfirmInput').value.trim();
			if(!(QDEL_CODE && val === QDEL_CODE)){ document.getElementById('deleteQuestionsError').innerHTML = 'Please type the confirmation code exactly as shown.'; return; }
			document.getElementById('deleteQuestionsMessage').innerHTML = '<i class="fa fa-spinner fa-spin"></i> Deleting, please wait...';
			document.getElementById('confirmDeleteQuestionsBtn').disabled = true;
			if(QDEL_MODE === 'selected'){
				$.ajax({ url: 'deleteSelectedQuestions.php', type: 'POST', data: {testId: testId, questionIds: QDEL_IDS}, success: function(html){
					if(html==0){ window.location.replace('logout.php'); }
					else if(html==1){ document.getElementById('deleteQuestionsMessage').innerHTML = '<i class="fa fa-check"></i> Selected questions deleted.'; setTimeout(function(){ closeDeleteQuestionsModal(); previewTestQuestions(); }, 1200); }
					else if(html==3){ document.getElementById('deleteQuestionsMessage').innerHTML = 'Cannot delete; some examinees have started or completed this test.'; document.getElementById('confirmDeleteQuestionsBtn').disabled = false; }
					else { document.getElementById('deleteQuestionsMessage').innerHTML = 'Could not delete selected questions. Please try again.'; document.getElementById('confirmDeleteQuestionsBtn').disabled = false; }
				}});
			}else{
				$.ajax({ url: 'deleteAllQuestions.php', type: 'POST', data: {testId: testId}, success: function(html){
					if(html==0){ window.location.replace('logout.php'); }
					else if(html==1){ document.getElementById('deleteQuestionsMessage').innerHTML = '<i class="fa fa-check"></i> All questions deleted.'; setTimeout(function(){ closeDeleteQuestionsModal(); previewTestQuestions(); }, 1200); }
					else if(html==3){ document.getElementById('deleteQuestionsMessage').innerHTML = 'Cannot delete; some examinees have started or completed this test.'; document.getElementById('confirmDeleteQuestionsBtn').disabled = false; }
					else { document.getElementById('deleteQuestionsMessage').innerHTML = 'Could not delete all questions. Please try again.'; document.getElementById('confirmDeleteQuestionsBtn').disabled = false; }
				}});
			}
		}

		//Start of function to delete a Question
		function deleteQuestion(testId, questionId)
		{
			$('.message1').html('');
			$('.message2').html('');
			
			dlhsConfirm("Are you sure you wish to delete this question? This action cannot be undone.", function() {
				$.ajax({
					url: "deleteQuestion.php",
					type: "POST", 
					data: {testId:testId, questionId:questionId},
					success: function (html) {             
						if (html==0)	//If session is expired.
						{                              
							 window.location.replace("logout.php");
						}
						else if (html==1)	
						{                              
							 $('.message4').html('<i class="fa fa-check"></i> Question successfully deleted.').fadeIn('slow');
							 var modal1 = document.getElementById("myModal1");				
							modal1.style.display = "none";
							previewTestQuestions();

						}
						else if (html==2)	//If deletion is unsuccessful	
						{                              
							dlhsAlert("Cannot delete question. Please try again or contact Admin.", "error");
						}
						else if (html==3)	//If deletion is unsuccessful	
						{                              
							dlhsAlert("Cannot delete this question as one or more candidates have started or completed the test it belongs to.", "error");
						}
					}
									
				});
			});
		}
		
		//Function to send data of test to be edited
		function beforeSubmitToEditTest(testId, questionId)
		{
			document.getElementById('testIdToEdit').value = testId;
			document.getElementById('questionIdToEdit').value = questionId;
			document.getElementById('frmAddQuestOrViewQuestStatus').value = 1;
			document.getElementById("submitToEditQuestionForm").submit();
			
		}
	</script>


  
	<!-- Bulk Paste Modal moved to bottom for better centering -->
	<div id="myModalBulkPaste" style="display:none; position:fixed; z-index:99999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.4); backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px); align-items:center; justify-content:center;">
		<div style="background:rgba(255,255,255,0.95); width:95%; max-width:900px; border-radius:24px; box-shadow:0 20px 40px rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.3); overflow:hidden; position:relative; animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
			<button type="button" class="closeBulkPaste" style="position:absolute; top:20px; right:20px; width:36px; height:36px; border-radius:50%; border:none; background:rgba(0,0,0,0.05); color:#333; font-size:20px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s; z-index:10;">&times;</button>
			
			<div style="padding:25px; border-bottom:1px solid rgba(0,0,0,0.05); background:#fcfcfc;">
				<h4 style="margin:0; color:#003366; font-weight:700;"><i class="fa fa-paste" style="margin-right:10px; color:#e91e8c;"></i> Bulk Paste Questions</h4>
			</div>
			
			<div style="padding:25px; max-height:80vh; overflow-y:auto;">
				<div id="bulkPasteInputArea">
					<!-- Instructions with Glassmorphism -->
					<div style="background:rgba(233, 30, 140, 0.05); border:1px solid rgba(233, 30, 140, 0.1); padding:15px; border-radius:12px; margin-bottom:15px; display:flex; align-items:flex-start; gap:12px;">
						<i class="fa fa-info-circle" style="color:#e91e8c; font-size:18px; margin-top:2px;"></i>
						<div style="font-size:14px; color:#555;">
							<strong style="color:#e91e8c;">Instructions:</strong> Paste questions and options below. 
							<br>Format example: <code>1. Which country...? [A] Nigeria [B] Ghana [C] UK [D] USA</code>
						</div>
					</div>
					
					<!-- General Support with Glassmorphism -->
					<div style="background:rgba(0, 51, 102, 0.03); border:1px solid rgba(0, 51, 102, 0.08); padding:18px; border-radius:15px; margin-bottom:20px;">
						<div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
							<div style="width:32px; height:32px; background:#003366; color:#fff; border-radius:8px; display:flex; align-items:center; justify-content:center;"><i class="fa fa-lightbulb-o"></i></div>
							<strong style="color:#003366;">Support for Complex Content (Math, Tech Drawing, etc.)</strong>
						</div>
						<div style="font-size:13px; color:#444; line-height:1.6;">
							If your questions include complex equations, symbols, or diagrams, we've got you covered:
							<ul style="margin:8px 0 0 0; padding-left:20px;">
								<li><strong>Diagrams:</strong> Upload them via <span style="color:#e91e8c; font-weight:600;">Shared Material</span> first, then link them after pasting.</li>
								<li><strong>Symbols & Equations:</strong> Paste them as plain text (e.g., <code>x²</code>, <code>&pi;</code>). Once imported, use the rich editor to refine them.</li>
								<li><strong>Cleanup:</strong> Leading numbers (1., 2.) are automatically detected and handled.</li>
							</ul>
						</div>
					</div>

					<textarea id="bulkPasteTextarea" class="form-control" rows="12" placeholder="1. Paste your questions here..." style="border-radius:12px; border:2px solid #eee; padding:15px; font-family:'Courier New', monospace; font-size:15px; transition:border-color 0.2s;"></textarea>
					
					<div style="margin-top:20px; text-align:right;">
						<button type="button" class="btn" onclick="processBulkPaste()" style="background:#003366; color:#fff; padding:12px 30px; border-radius:50px; font-weight:600; border:none; box-shadow:0 4px 12px rgba(0,51,102,0.2);"><i class="fa fa-magic"></i> Parse & Preview Questions</button>
					</div>
				</div>

				<div id="bulkPastePreviewArea" style="display:none;">
					<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
						<h5 style="margin:0; font-weight:700; color:#003366;">Parsed Questions (<span id="parsedCount" style="color:#e91e8c;">0</span>)</h5>
						<span style="font-size:12px; color:#888;">Review and set correct options below</span>
					</div>
					<div id="parsedQuestionsList" style="padding:5px;"></div>
					<div style="margin-top:25px; padding-top:20px; border-top:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
						<button type="button" class="btn" onclick="backToPaste()" style="background:#f0f0f0; color:#555; padding:10px 25px; border-radius:50px; border:none;"><i class="fa fa-arrow-left"></i> Edit Text</button>
						<button type="button" class="btn" onclick="saveBulkParsedQuestions()" style="background:#28a745; color:#fff; padding:12px 35px; border-radius:50px; font-weight:700; border:none; box-shadow:0 4px 12px rgba(40,167,69,0.2);"><i class="fa fa-check-circle"></i> Save All to Database</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<style>
		@keyframes modalPop {
			from { transform: scale(0.95); opacity: 0; }
			to { transform: scale(1); opacity: 1; }
		}
		.closeBulkPaste:hover {
			background: rgba(233, 30, 140, 0.1) !important;
			color: #e91e8c !important;
			transform: rotate(90deg);
		}
		.parsed-q-item {
			transition: all 0.2s;
		}
		.parsed-q-item:hover {
			border-color: #e91e8c !important;
			box-shadow: 0 5px 15px rgba(0,0,0,0.08) !important;
		}
		#bulkPasteTextarea:focus {
			border-color: #003366 !important;
			outline: none;
			box-shadow: 0 0 0 4px rgba(0,51,102,0.1);
		}
	</style>

	<script src="js/bulkPasteParser.js"></script>
	<script>
		var currentParsedQuestions = [];

		function openBulkPasteModal() {
			$('#myModalBulkPaste').css('display', 'flex');
			$('#bulkPasteInputArea').show();
			$('#bulkPastePreviewArea').hide();
		}

		function closeBulkPasteModal() {
			$('#myModalBulkPaste').hide();
		}

		$('.closeBulkPaste').on('click', closeBulkPasteModal);

		function backToPaste() {
			$('#bulkPasteInputArea').show();
			$('#bulkPastePreviewArea').hide();
		}

		function processBulkPaste() {
			var text = $('#bulkPasteTextarea').val();
			currentParsedQuestions = DLHSBulkParser.parse(text);
			
			if (currentParsedQuestions.length === 0) {
				dlhsAlert("No questions could be parsed. Please check the format.", "error");
				return;
			}
			
			$('#parsedCount').text(currentParsedQuestions.length);
			var html = '';
			currentParsedQuestions.forEach(function(q, index) {
				html += '<div class="parsed-q-item" style="margin-bottom:20px; padding:15px; background:white; border:1px solid #eee; border-radius:6px; box-shadow:0 2px 4px rgba(0,0,0,0.05);">';
				html += '  <div style="margin-bottom:10px;"><strong>Question ' + (index + 1) + ':</strong> ' + q.question + '</div>';
				html += '  <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; font-size:13px; color:#555;">';
				html += '    <div>A: ' + q.optionA + '</div>';
				html += '    <div>B: ' + q.optionB + '</div>';
				html += '    <div>C: ' + q.optionC + '</div>';
				html += '    <div>D: ' + q.optionD + '</div>';
				if (q.optionE) html += '    <div>E: ' + q.optionE + '</div>';
				html += '  </div>';
				html += '  <div style="margin-top:10px; display:flex; align-items:center; gap:15px;">';
				html += '    <label>Correct: <select class="parsed-correct" data-index="' + index + '" style="padding:2px 5px; border-radius:4px; border:1px solid #ccc;">';
				['A', 'B', 'C', 'D', 'E'].forEach(function(opt) {
					if (opt === 'E' && !q.optionE) return;
					html += '<option value="' + opt + '">' + opt + '</option>';
				});
				html += '    </select></label>';
				html += '    <label>Mark: <input type="number" class="parsed-mark" data-index="' + index + '" value="1" step="0.5" style="width:50px; padding:2px 5px; border-radius:4px; border:1px solid #ccc;"></label>';
				html += '    <button class="btn btn-xs btn-danger" onclick="removeParsedQuestion(' + index + ')"><i class="fa fa-trash"></i> Remove</button>';
				html += '  </div>';
				html += '</div>';
			});
			
			$('#parsedQuestionsList').html(html);
			$('#bulkPasteInputArea').hide();
			$('#bulkPastePreviewArea').show();
		}

		function removeParsedQuestion(index) {
			currentParsedQuestions.splice(index, 1);
			if (currentParsedQuestions.length === 0) {
				backToPaste();
			} else {
				processBulkPaste(); // Refresh preview
			}
		}

		function saveBulkParsedQuestions() {
			var testId = $('#testName').val();
			if (!testId) {
				dlhsAlert("Please select a test first.", "error");
				return;
			}
			
			// Update correct options and marks from the preview UI
			$('.parsed-q-item').each(function() {
				var idx = $(this).find('.parsed-correct').data('index');
				currentParsedQuestions[idx].correctOption = $(this).find('.parsed-correct').val();
				currentParsedQuestions[idx].mark = $(this).find('.parsed-mark').val();
			});
			
			dlhsConfirm("Are you sure you want to save these " + currentParsedQuestions.length + " questions?", function() {
				var btn = $('.btn-success');
				var originalHtml = btn.html();
				btn.prop('disabled', true).html('<i class="fa fa-refresh fa-spin"></i> Saving...');
				
				$.ajax({
					url: 'bulkSaveQuestions.php',
					type: 'POST',
					data: {
						testId: testId,
						questions: JSON.stringify(currentParsedQuestions)
					},
					dataType: 'json',
					success: function(response) {
						if (response && response.success) {
							dlhsAlert(response.message, "success");
							closeBulkPasteModal();
							previewTestQuestions(); // Refresh the main table
						} else {
							dlhsAlert("Error: " + (response ? response.message : "Unknown error"), "error");
						}
					},
					error: function() {
						dlhsAlert("Failed to reach server.", "error");
					},
					complete: function() {
						btn.prop('disabled', false).html(originalHtml);
					}
				});
			});
		}

		// --- CSV Parser & Interactive Preview Modal Logic ---
		var csvParsedQuestions = [];

		function triggerCSVPreview() {
			var testId = $('#testName').val();
			if (!testId) {
				dlhsAlert("Please select a test first.", "error");
				return;
			}
			
			var fileInput = document.getElementById('csvFileInput');
			if (!fileInput || fileInput.files.length === 0) {
				dlhsAlert("Please select a CSV file first.", "error");
				return;
			}
			
			var file = fileInput.files[0];
			var reader = new FileReader();
			reader.onload = function(e) {
				var text = e.target.result;
				processCSVContent(text);
			};
			reader.readAsText(file);
		}

		function processCSVContent(text) {
			var rows = parseCSV(text);
			if (rows.length === 0) {
				showCSVError("The CSV file is empty.");
				return;
			}
			
			// Validate header
			var header = rows[0];
			var expectedHeaders = ['questionserialno', 'question', 'optiona', 'optionb', 'optionc', 'optiond', 'correctoption', 'markforquestion'];
			var headerValid = true;
			if (header.length < 8) {
				headerValid = false;
			} else {
				for (var i = 0; i < 8; i++) {
					var h = (header[i] || '').trim().toLowerCase();
					if (h !== expectedHeaders[i]) {
						headerValid = false;
						break;
					}
				}
			}
			
			if (!headerValid) {
				var gotHeaders = header.map(function(h) { return "'" + h.trim() + "'"; }).join(', ');
				showCSVError("<strong>Invalid CSV Header Structure!</strong><br>Your file columns: [" + gotHeaders + "]<br>Expected columns exactly in this order: <code>questionSerialNo, question, optionA, optionB, optionC, optionD, correctOption, markForQuestion</code>.<br>Please download and use the official CSV template.");
				return;
			}
			
			// Parse rows
			csvParsedQuestions = [];
			var errors = [];
			var warnings = [];
			var serialNos = {};
			
			for (var r = 1; r < rows.length; r++) {
				var row = rows[r];
				// Skip empty rows
				if (row.length === 0 || (row.length === 1 && row[0].trim() === '')) {
					continue;
				}
				
				// Pad to at least 8 elements
				while (row.length < 8) {
					row.push('');
				}
				
				var serialNo = row[0].trim();
				var questionText = row[1].trim();
				var optA = row[2].trim();
				var optB = row[3].trim();
				var optC = row[4].trim();
				var optD = row[5].trim();
				var correctOpt = row[6].trim().toUpperCase();
				var mark = row[7].trim();
				
				var rowErrors = [];
				var rowWarnings = [];
				
				// 1. Validate Serial Number
				if (serialNo === '') {
					rowErrors.push("S/NO is missing");
				} else {
					var snInt = parseInt(serialNo, 10);
					if (isNaN(snInt) || snInt <= 0) {
						rowErrors.push("S/NO must be a positive integer (got '" + serialNo + "')");
					} else if (serialNos[serialNo]) {
						rowErrors.push("S/NO '" + serialNo + "' is duplicated (already defined on Row " + serialNos[serialNo] + ")");
					} else {
						serialNos[serialNo] = r + 1; // 1-indexed row number
					}
				}
				
				// 2. Validate Question Text
				if (questionText === '') {
					rowErrors.push("Question text is empty");
				}
				
				// 3. Validate Options A-D
				if (optA === '') rowErrors.push("Option A is empty");
				if (optB === '') rowErrors.push("Option B is empty");
				if (optC === '') rowErrors.push("Option C is empty");
				if (optD === '') rowErrors.push("Option D is empty");
				
				// 4. Validate Correct Option
				if (correctOpt === '') {
					rowErrors.push("Correct Option is missing");
				} else if (['A', 'B', 'C', 'D'].indexOf(correctOpt) === -1) {
					rowErrors.push("Correct Option must be A, B, C, or D (got '" + correctOpt + "')");
				}
				
				// 5. Validate Mark
				var markVal = 1;
				if (mark !== '') {
					markVal = parseFloat(mark);
					if (isNaN(markVal) || markVal < 0) {
						rowErrors.push("Mark must be a non-negative number (got '" + mark + "')");
					}
				}
				
				// 6. Placeholders Checks
				function isPlaceholder(text) {
					var t = text.toLowerCase();
					return t === '--' || t.indexOf('[option') > -1 || t.indexOf('option') === 0;
				}
				if (isPlaceholder(optA)) rowWarnings.push("Option A looks like a placeholder");
				if (isPlaceholder(optB)) rowWarnings.push("Option B looks like a placeholder");
				if (isPlaceholder(optC)) rowWarnings.push("Option C looks like a placeholder");
				if (isPlaceholder(optD)) rowWarnings.push("Option D looks like a placeholder");
				
				if (rowErrors.length > 0) {
					errors.push({ rowNum: r + 1, sn: serialNo, messages: rowErrors });
				}
				if (rowWarnings.length > 0) {
					warnings.push({ rowNum: r + 1, sn: serialNo, messages: rowWarnings });
				}
				
				csvParsedQuestions.push({
					serialNo: serialNo,
					question: questionText,
					optionA: optA,
					optionB: optB,
					optionC: optC,
					optionD: optD,
					correctOption: correctOpt,
					mark: markVal,
					hasErrors: rowErrors.length > 0,
					hasWarnings: rowWarnings.length > 0
				});
			}
			
			displayCSVPreview(csvParsedQuestions, errors, warnings);
		}

		function parseCSV(text) {
			let lines = [];
			let row = [""];
			let inQuotes = false;
			for (let i = 0; i < text.length; i++) {
				let c = text[i];
				let next = text[i+1];
				if (c === '"') {
					if (inQuotes && next === '"') {
						row[row.length - 1] += '"';
						i++;
					} else {
						inQuotes = !inQuotes;
					}
				} else if (c === ',' && !inQuotes) {
					row.push("");
				} else if ((c === '\r' || c === '\n') && !inQuotes) {
					if (c === '\r' && next === '\n') { i++; }
					lines.push(row);
					row = [""];
				} else {
					row[row.length - 1] += c;
				}
			}
			if (row.length > 1 || row[0] !== "") {
				lines.push(row);
			}
			return lines;
		}

		function showCSVError(message) {
			$('#csvValidationStatus').html(
				'<div style="background:rgba(220, 53, 69, 0.08); border:2px solid #dc3545; border-radius:15px; padding:20px; color:#c9302c; font-size:14px; line-height:1.6; margin-bottom:20px;">' +
				'  <div style="font-weight:bold; font-size:16px; margin-bottom:8px;"><i class="fa fa-exclamation-triangle"></i> CSV Import Failed!</div>' +
				'  ' + message +
				'</div>'
			);
			$('#csvPreviewTableBody').html('<tr><td colspan="9" style="text-align:center; color:#999; padding:30px;">Invalid CSV File. Please correct the errors above and try again.</td></tr>');
			$('#confirmCSVUploadBtn').prop('disabled', true).css('opacity', '0.5');
			$('#csvParsedCount').text('0');
			$('#myModalCSVPreview').css('display', 'flex');
		}

		function displayCSVPreview(questions, errors, warnings) {
			$('#csvParsedCount').text(questions.length);
			
			// Build status markup
			var statusHtml = '';
			if (errors.length > 0) {
				statusHtml += '<div style="background:rgba(220, 53, 69, 0.06); border:2px solid #dc3545; border-radius:15px; padding:20px; margin-bottom:20px; font-size:14px; line-height:1.6;">';
				statusHtml += '  <div style="font-weight:bold; color:#dc3545; font-size:16px; margin-bottom:10px;"><i class="fa fa-times-circle"></i> CSV Validation Failed: ' + errors.length + ' row(s) contain critical errors!</div>';
				statusHtml += '  <div style="color:#721c24; margin-bottom:12px;">Please fix the following errors in your CSV file and select the file again:</div>';
				statusHtml += '  <ul style="margin:0; padding-left:20px; color:#c9302c;">';
				errors.forEach(function(err) {
					statusHtml += '    <li><strong>Row ' + err.rowNum + ' (S/NO ' + (err.sn || 'None') + '):</strong> ' + err.messages.join(', ') + '</li>';
				});
				statusHtml += '  </ul>';
				statusHtml += '</div>';
				
				$('#confirmCSVUploadBtn').prop('disabled', true).css('opacity', '0.5');
			} else {
				// No critical errors!
				$('#confirmCSVUploadBtn').prop('disabled', false).css('opacity', '1');
				
				statusHtml += '<div style="background:rgba(40, 167, 69, 0.06); border:2px solid #28a745; border-radius:15px; padding:20px; margin-bottom:20px; font-size:14px; line-height:1.6;">';
				statusHtml += '  <div style="font-weight:bold; color:#28a745; font-size:16px; margin-bottom:5px;"><i class="fa fa-check-circle"></i> Validation Success!</div>';
				statusHtml += '  <div style="color:#155724;">All ' + questions.length + ' questions are perfectly formatted. Click "Confirm & Upload" below to save them.</div>';
				statusHtml += '</div>';
			}
			
			if (warnings.length > 0) {
				statusHtml += '<div style="background:rgba(255, 193, 7, 0.08); border:2px solid #ffc107; border-radius:15px; padding:20px; margin-bottom:20px; font-size:14px; line-height:1.6;">';
				statusHtml += '  <div style="font-weight:bold; color:#d39e00; font-size:16px; margin-bottom:10px;"><i class="fa fa-exclamation-triangle"></i> Style Warnings (' + warnings.length + ' row(s)):</div>';
				statusHtml += '  <ul style="margin:0; padding-left:20px; color:#856404;">';
				warnings.forEach(function(warn) {
					statusHtml += '    <li><strong>Row ' + warn.rowNum + ' (S/NO ' + warn.sn + '):</strong> ' + warn.messages.join(', ') + '</li>';
				});
				statusHtml += '  </ul>';
				statusHtml += '</div>';
			}
			
			$('#csvValidationStatus').html(statusHtml);
			
			// Build table preview rows
			var tableHtml = '';
			questions.forEach(function(q, index) {
				var rowBg = q.hasErrors ? 'background-color:#fdf2f2;' : (q.hasWarnings ? 'background-color:#fffdf5;' : '');
				var statusBadge = q.hasErrors ? '<span class="label label-danger"><i class="fa fa-times"></i> Error</span>' : (q.hasWarnings ? '<span class="label label-warning"><i class="fa fa-warning"></i> Warning</span>' : '<span class="label label-success"><i class="fa fa-check"></i> Ready</span>');
				
				tableHtml += '<tr style="' + rowBg + '">';
				tableHtml += '  <td><center><strong>' + q.serialNo + '</strong></center></td>';
				tableHtml += '  <td>' + escapeHTML(q.question) + '</td>';
				tableHtml += '  <td style="' + (q.optionA === '' ? 'border:1px solid #dc3545;' : '') + '">' + escapeHTML(q.optionA) + '</td>';
				tableHtml += '  <td style="' + (q.optionB === '' ? 'border:1px solid #dc3545;' : '') + '">' + escapeHTML(q.optionB) + '</td>';
				tableHtml += '  <td style="' + (q.optionC === '' ? 'border:1px solid #dc3545;' : '') + '">' + escapeHTML(q.optionC) + '</td>';
				tableHtml += '  <td style="' + (q.optionD === '' ? 'border:1px solid #dc3545;' : '') + '">' + escapeHTML(q.optionD) + '</td>';
				tableHtml += '  <td><center><span style="padding:2px 8px; background:#e0f0ff; color:#0055a5; font-weight:bold; border-radius:4px;">' + q.correctOption + '</span></center></td>';
				tableHtml += '  <td><center>' + q.mark + '</center></td>';
				tableHtml += '  <td><center>' + statusBadge + '</center></td>';
				tableHtml += '</tr>';
			});
			
			$('#csvPreviewTableBody').html(tableHtml);
			$('#myModalCSVPreview').css('display', 'flex');
		}

		function escapeHTML(str) {
			if (!str) return '';
			return str
				.replace(/&/g, "&amp;")
				.replace(/</g, "&lt;")
				.replace(/>/g, "&gt;")
				.replace(/"/g, "&quot;")
				.replace(/'/g, "&#039;");
		}

		function closeCSVPreviewModal() {
			$('#myModalCSVPreview').hide();
		}

		function submitCSVQuestionsToServer() {
			var testId = $('#testName').val();
			if (!testId) {
				dlhsAlert("Please select a test first.", "error");
				return;
			}
			
			if (csvParsedQuestions.length === 0) {
				dlhsAlert("No parsed questions to upload.", "error");
				return;
			}
			
			var btn = $('#confirmCSVUploadBtn');
			var originalHtml = btn.html();
			btn.prop('disabled', true).html('<i class="fa fa-refresh fa-spin"></i> Uploading & Saving...');
			
			$.ajax({
				url: 'uploadQuestionsAjax.php',
				type: 'POST',
				data: {
					testId: testId,
					questions: JSON.stringify(csvParsedQuestions)
				},
				dataType: 'json',
				success: function(response) {
					if (response && response.success) {
						dlhsAlert("Success! " + response.insertedCount + " new question(s) inserted, " + response.updatedCount + " question(s) updated successfully.", "success");
						closeCSVPreviewModal();
						previewTestQuestions(); // Refresh the main table
					} else {
						dlhsAlert("Upload Error: " + (response ? response.error : "Unknown error"), "error");
					}
				},
				error: function() {
					dlhsAlert("Failed to connect to the server. Please check your internet connection.", "error");
				},
				complete: function() {
					btn.prop('disabled', false).html(originalHtml);
				}
			});
		}
	</script>

	<!-- CSV Preview Modal -->
	<div id="myModalCSVPreview" style="display:none; position:fixed; z-index:99999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.4); backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px); align-items:center; justify-content:center;">
		<div style="background:rgba(255,255,255,0.95); width:95%; max-width:1100px; border-radius:24px; box-shadow:0 20px 40px rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.3); overflow:hidden; position:relative; animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
			<button type="button" class="closeCSVPreview" onclick="closeCSVPreviewModal()" style="position:absolute; top:20px; right:20px; width:36px; height:36px; border-radius:50%; border:none; background:rgba(0,0,0,0.05); color:#333; font-size:20px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s; z-index:10;">&times;</button>
			
			<div style="padding:25px; border-bottom:1px solid rgba(0,0,0,0.05); background:#fcfcfc;">
				<h4 style="margin:0; color:#003366; font-weight:700;"><i class="fa fa-file-excel-o" style="margin-right:10px; color:#28a745;"></i> CSV Questions Upload Preview</h4>
			</div>
			
			<div style="padding:25px; max-height:80vh; overflow-y:auto;">
				<!-- Validation Status Area -->
				<div id="csvValidationStatus" style="margin-bottom:20px;"></div>
				
				<!-- Parsed Questions Table Preview -->
				<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
					<h5 style="margin:0; font-weight:700; color:#003366;">Parsed Questions (<span id="csvParsedCount" style="color:#28a745;">0</span>)</h5>
					<span style="font-size:12px; color:#888;">Review the questions before saving</span>
				</div>
				
				<div class="table-responsive" style="border:1px solid #ddd; border-radius:12px; overflow:hidden;">
					<table class="table table-striped table-bordered" style="margin-bottom:0; font-size:13px;">
						<thead>
							<tr style="background:#f5f5f5; color:#003366;">
								<th width="5%"><center>S/NO</center></th>
								<th width="30%">QUESTION</th>
								<th width="10%">OPTION A</th>
								<th width="10%">OPTION B</th>
								<th width="10%">OPTION C</th>
								<th width="10%">OPTION D</th>
								<th width="8%"><center>ANSWER</center></th>
								<th width="7%"><center>MARK</center></th>
								<th width="10%"><center>STATUS</center></th>
							</tr>
						</thead>
						<tbody id="csvPreviewTableBody">
							<!-- Populated via Javascript -->
						</tbody>
					</table>
				</div>
				
				<div style="margin-top:25px; padding-top:20px; border-top:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
					<button type="button" class="btn" onclick="closeCSVPreviewModal()" style="background:#f0f0f0; color:#555; padding:10px 25px; border-radius:50px; border:none;">Cancel</button>
					<button type="button" class="btn" id="confirmCSVUploadBtn" onclick="submitCSVQuestionsToServer()" style="background:#28a745; color:#fff; padding:12px 35px; border-radius:50px; font-weight:700; border:none; box-shadow:0 4px 12px rgba(40,167,69,0.2);"><i class="fa fa-cloud-upload"></i> Confirm & Upload Questions</button>
				</div>
			</div>
		</div>
	</div>

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
