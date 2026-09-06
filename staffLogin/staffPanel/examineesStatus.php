<?php
session_start();
	require_once 'userExpiredSession.php';
	if (!isset($_SESSION['staffLoggedIn']))
	{
		header('location:../index.php');
	}
	include "../../db_connection/dlhs_db_connection.php";
	$staffId = $_SESSION['staffId'];
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

    <title>Student Results | DLHS</title>

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
    <script src="assets/chart-master/Chart.js"></script>
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
			.modal1, .modal2 {
	            display: none; /* Hidden by default */
	            position: fixed; /* Stay in place */
	            z-index: 2100; /* Keep modals above the fixed staff sidebar */
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
            width: 97%;
            color:black;
            }

            /* The Close Button */
            .close1, .close2{
            color: #ffffff;
            float: right;
			padding-right:20px;
			padding-top:20px;
            font-size: 28px;
            font-weight: bold;
            }

            .close1:hover, .close1:focus, .close2:hover, .close2:focus {
            color: red;
            text-decoration: none;
            cursor: pointer;
            }
			
			body{
				overflow-x:hidden;
				overflow-y:auto;
				height:auto;
                background: #f4f7f6;
			}

            .stats-card {
                background: rgba(255, 255, 255, 0.9);
                border-radius: 15px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.05);
                border: 1px solid rgba(255,255,255,0.3);
                transition: transform 0.3s ease;
            }
            .stats-card:hover {
                transform: translateY(-5px);
            }
            .stats-icon {
                font-size: 2.5rem;
                margin-bottom: 10px;
                opacity: 0.8;
            }
            .stats-number {
                font-size: 1.8rem;
                font-weight: 700;
                color: #2d3436;
            }
            .stats-label {
                font-size: 0.9rem;
                color: #636e72;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            .chart-container {
                background: #fff;
                border-radius: 15px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            }
            .status-badge {
                padding: 5px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
            }
            .status-submitted { background: #d4edda; color: #155724; }
            .status-inprogress { background: #fff3cd; color: #856404; }
            .status-notstarted { background: #e2e3e5; color: #383d41; }
            
            .progress-small {
                height: 8px;
                margin-top: 5px;
                border-radius: 4px;
                background: #eee;
            }
            .progress-bar-fill {
                height: 100%;
                border-radius: 4px;
                transition: width 0.5s ease;
            }

            /* Modal Question Cards */
            .question-card {
                background: #fff;
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 20px;
                border: 1px solid #eee;
                box-shadow: 0 4px 12px rgba(0,0,0,0.03);
                transition: all 0.3s ease;
            }
            .question-card:hover {
                box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            }
            .question-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 1px solid #f8f9fa;
            }
            .option-item {
                padding: 10px 15px;
                border-radius: 8px;
                margin-bottom: 8px;
                border: 1px solid #f1f2f6;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .option-item.correct {
                background: #d4edda;
                border-color: #c3e6cb;
                color: #155724;
            }
            .option-item.incorrect {
                background: #f8d7da;
                border-color: #f5c6cb;
                color: #721c24;
            }
            .option-item.selected-correct {
                background: #28a745;
                color: white;
                border-color: #28a745;
            }
            .option-item.selected-incorrect {
                background: #dc3545;
                color: white;
                border-color: #dc3545;
            }
            .filter-tabs {
                display: flex;
                gap: 10px;
                margin-bottom: 20px;
                padding: 5px;
                background: #f1f2f6;
                border-radius: 10px;
                width: fit-content;
            }
            .filter-tab {
                padding: 8px 16px;
                border-radius: 8px;
                cursor: pointer;
                font-weight: 600;
                font-size: 13px;
                transition: all 0.2s ease;
            }
            .filter-tab.active {
                background: #00AEEF;
                color: white;
                box-shadow: 0 4px 10px rgba(0, 174, 239, 0.3);
            }
            .filter-tab:not(.active):hover {
                background: rgba(0, 174, 239, 0.1);
            }
            .modal-stats-bar {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
                margin-bottom: 25px;
            }
            .modal-stat-box {
                background: #fff;
                padding: 15px;
                border-radius: 10px;
                text-align: center;
                border: 1px solid #eee;
            }
            .modal-stat-val { font-size: 1.5rem; font-weight: 700; }
            .modal-stat-label { font-size: 0.8rem; color: #636e72; text-transform: uppercase; }

            /* Score Progress Bar */
            .score-progress-container {
                width: 100%;
                background: #eee;
                border-radius: 4px;
                height: 8px;
                margin-top: 5px;
            }
            .score-progress-fill {
                height: 100%;
                border-radius: 4px;
                transition: width 0.3s ease;
            }
            .score-high { background: #2ecc71; }
            .score-mid { background: #f1c40f; }
            .score-low { background: #e74c3c; }
            /* Unified Control Bar */
            .control-bar {
                background: white;
                border-radius: 12px;
                padding: 15px 25px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.06);
                display: flex;
                align-items: center;
                gap: 20px;
                margin-bottom: 30px;
                border: 1px solid #edf2f7;
                flex-wrap: wrap;
            }
            .control-group {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .control-label {
                font-weight: 700;
                font-size: 13px;
                color: #2d3436;
                white-space: nowrap;
            }
            .recent-chip {
                padding: 6px 14px;
                background: #f1f2f6;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                color: #2f3542;
                cursor: pointer;
                transition: all 0.2s;
                border: 1px solid transparent;
            }
            .recent-chip:hover {
                background: #e1fdf6;
                color: #009999;
                border-color: #009999;
            }
            .recent-chip.active {
                background: #009999;
                color: white;
            }
            .action-btn-main {
                background: #009999;
                color: white;
                border: none;
                padding: 8px 18px;
                border-radius: 8px;
                font-weight: 700;
                font-size: 13px;
                box-shadow: 0 4px 10px rgba(0, 153, 153, 0.2);
                transition: all 0.2s;
            }
            .action-btn-main:hover {
                transform: translateY(-2px);
                filter: brightness(110%);
            }
            .action-btn-outline {
                background: transparent;
                color: #009999;
                border: 1px solid #009999;
                padding: 8px 18px;
                border-radius: 8px;
                font-weight: 700;
                font-size: 13px;
                transition: all 0.2s;
            }
            .action-btn-outline:hover {
                background: #e1fdf6;
            }

            .omni-search-container {
                margin-top: 10px;
                margin-bottom: 20px;
                position: relative;
            }
            .omni-search-input {
                width: 100%;
                padding: 12px 20px 12px 45px;
                border-radius: 30px;
                border: 1px solid #ddd;
                background: #fff;
                font-size: 14px;
                transition: all 0.3s ease;
            }
            .omni-search-icon {
                position: absolute;
                left: 18px;
                top: 50%;
                transform: translateY(-50%);
                color: #b2bec3;
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
					<h3 class="page-header"><i class="fa fa-bar-chart"></i> Student Results</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-bar-chart"></i>Student Results</li>
						<a href="#" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
					</ol>
				</div>
			</div>
                <!-- Modern Unified Control Bar -->
                <div class="control-bar">
                    <div class="control-group" style="flex: 1; min-width: 300px;">
                        <span class="control-label"><i class="fa fa-search"></i> SELECT TEST:</span>
                        <select name="testId" id="testId" class="form-control" style="border-radius:8px; height: 38px; border: 1px solid #ddd;">
                            <option value="">-- Choose an Assessment --</option>
                                <?php
                                $getTests = "SELECT * FROM tests WHERE staffId='$staffId' ORDER BY testId DESC";
                                $result = $connection->query($getTests);
                                while($row = $result->fetch_assoc()){
                                    $subjectId = $row['subject'];
                                    $getSubjectName = "SELECT subjectName FROM subjects WHERE subjectId='$subjectId'";
                                    $resSub = $connection->query($getSubjectName);
                                    $subRow = $resSub->fetch_assoc();
                                    echo "<option value='".$row['testId']."'>".$row['testName']." (".$subRow['subjectName'].")</option>";
                                }
                            ?>
                        </select>
                    </div>
                    
                    <div class="control-group" id="quickActions" style="display:none;">
                        <button class="action-btn-main" onclick="viewCompleteExamineesResult()">
                            <i class="fa fa-list-ol"></i> VIEW ALL SCORES
                        </button>
                        <button class="action-btn-outline" onclick="$('#analyticsSection').fadeToggle();">
                            <i class="fa fa-pie-chart"></i> ANALYTICS
                        </button>
                    </div>

                    <div class="control-group" style="border-left: 1px solid #eee; padding-left: 20px;">
                        <span class="control-label" style="color:#009999;">RECENTLY VIEWED:</span>
                        <div style="display:flex; gap:8px;">
                            <?php
                            $getRecentTests = "SELECT * FROM tests WHERE staffId='$staffId' ORDER BY testId DESC LIMIT 6";
                            $resRecent = $connection->query($getRecentTests);
                            while($recent = $resRecent->fetch_assoc()){
                                $testName = $recent['testName'];
                                if(strlen($testName) > 12) $testName = substr($testName, 0, 10)."...";
                                echo "<div class='recent-chip' id='chip-".$recent['testId']."' onclick='selectTestChip(\"".$recent['testId']."\", \"".$recent['testName']."\")'>
                                        <i class='fa fa-history'></i> ".$testName."
                                      </div>";
                            }
                        ?>
                        </div>
                    </div>
                </div>

				<div class="row" id="analyticsSection" style="display:none; margin-bottom: 20px;">
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <div class="stats-icon text-primary"><i class="fa fa-users"></i></div>
                            <div class="stats-number" id="statTotalExaminees">0</div>
                            <div class="stats-label">Total Examinees</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <div class="stats-icon text-success"><i class="fa fa-check-circle"></i></div>
                            <div class="stats-number" id="statAvgScore">0%</div>
                            <div class="stats-label">Average Score</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <div class="stats-icon text-warning"><i class="fa fa-pie-chart"></i></div>
                            <div class="stats-number" id="statCompletionRate">0%</div>
                            <div class="stats-label">Completion Rate</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stats-card">
                            <div class="stats-icon text-info"><i class="fa fa-trophy"></i></div>
                            <div class="stats-number" id="statTopScore">0%</div>
                            <div class="stats-label">Top Score</div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="chart-container">
                            <h4 style="margin-top:0; margin-bottom:20px; font-weight:600; color:#2d3436;">Score Distribution</h4>
                            <canvas id="scoreDistChart" height="250"></canvas>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="chart-container">
                            <h4 style="margin-top:0; margin-bottom:20px; font-weight:600; color:#2d3436;">Participation</h4>
                            <canvas id="participationChart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <div id="tableSection" style="display:none;">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="omni-search-container">
                                <i class="fa fa-search omni-search-icon"></i>
                                <input type="text" id="omniSearch" class="omni-search-input" placeholder="Quick find student by name, class, or admission ID...">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-12">
                            <section class="panel glass-card">
                                <header class="panel-heading" style="background: linear-gradient(135deg, #009999, #0acca2); color: white; border-radius: 10px 10px 0 0; padding: 15px; display:flex; justify-content:space-between; align-items:center;">
                                    <div><i class="fa fa-list-alt"></i> <span id="activeTestLabel">Detailed Examinees Status</span></div>
                                </header>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table id="example" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
                                            <thead>
                                                <tr>
                                                    <th width="4%"><center>S/NO</center></th>
                                                    <th width="15%"><center>NAME</center></th>
                                                    <th width="13%"><center>CLASS</center></th>
                                                    <th width="16%"><center>TEST DATE</center></th>
                                                    <th width="8%"><center>DURATION</center></th>
                                                    <th width="8%"><center>STARTED</center></th>
                                                    <th width="8%"><center>ENDED</center></th>
                                                    <th width="10%"><center>REMAINING</center></th>
                                                    <th width="18%"><center>STATUS</center></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                    
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>				
                </div>
                </div>
              </div>
              <!-- page end-->
          </section>
      </section>
	  
	<div id="myModal1" class="modal1">
		<div class="modal-content" style="background: #f8f9fa;">
			<span class="close1" style="color:#2d3436; margin-top:-10px;">&times;</span>
			<div style="padding:15px; color:#fff; border-radius:10px 10px 0 0; margin-bottom:20px; background: linear-gradient(135deg, #009999, #007373);">
                <h3 style="margin:0; font-weight:600;"><i class="fa fa-user"></i> <span id="examineeName">Student</span> - Test Review</h3>
            </div>
            
            <div style="padding: 0 10px;">
                <!-- Summary Stats for Modal -->
                <div class="modal-stats-bar">
                    <div class="modal-stat-box">
                        <div class="modal-stat-val text-primary" id="mStatScore">0%</div>
                        <div class="modal-stat-label">Percentage</div>
                    </div>
                    <div class="modal-stat-box">
                        <div class="modal-stat-val text-success" id="mStatCorrect">0</div>
                        <div class="modal-stat-label">Correct</div>
                    </div>
                    <div class="modal-stat-box">
                        <div class="modal-stat-val text-danger" id="mStatWrong">0</div>
                        <div class="modal-stat-label">Incorrect</div>
                    </div>
                    <div class="modal-stat-box">
                        <div class="modal-stat-val text-muted" id="mStatSkipped">0</div>
                        <div class="modal-stat-label">Skipped</div>
                    </div>
                </div>

                <!-- Filters for Questions -->
                <div class="filter-tabs">
                    <div class="filter-tab active" data-filter="all">All Questions</div>
                    <div class="filter-tab" data-filter="correct">Correct</div>
                    <div class="filter-tab" data-filter="wrong">Incorrect</div>
                    <div class="filter-tab" data-filter="skipped">Skipped</div>
                </div>

                <!-- Card Container -->
                <div id="questionCardsContainer" style="max-height: 60vh; overflow-y: auto; padding-right: 5px;">
                    <!-- Cards will be injected here -->
                    <div class="text-center" id="modalLoader">
                        <i class="fa fa-spinner fa-spin fa-3x text-primary"></i>
                        <p>Loading result details...</p>
                    </div>
                </div>
            </div>
			<hr>
		</div>
	</div>

	<div id="myModal2" class="modal2">
		<div class="modal-content" style="background: #f8f9fa;">
			<span class="close2" style="color:#2d3436; margin-top:-10px;">&times;</span>
			<div style="padding:15px; color:#fff; border-radius:10px 10px 0 0; margin-bottom:20px; background: linear-gradient(135deg, #0acca2, #009999);">
                <h3 style="margin:0; font-weight:600;"><i class="fa fa-list-alt"></i> Students' Test results (<span id="testName">Test</span>)</h3>
            </div>
            
            <div style="padding: 0 10px;">
                <!-- New Performance Highlight Section -->
                <div id="topPerformersSection" style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                    <!-- Top performers will be injected here -->
                </div>

                <!-- Summary Stats for Modal 2 -->
                <div class="modal-stats-bar" style="grid-template-columns: repeat(3, 1fr);">
                    <div class="modal-stat-box">
                        <div class="modal-stat-val text-primary" id="m2StatAvg">0%</div>
                        <div class="modal-stat-label">Class Average</div>
                    </div>
                    <div class="modal-stat-box">
                        <div class="modal-stat-val text-success" id="m2StatHigh">0%</div>
                        <div class="modal-stat-label">Highest Score</div>
                    </div>
                    <div class="modal-stat-box">
                        <div class="modal-stat-val text-danger" id="m2StatLow">0%</div>
                        <div class="modal-stat-label">Lowest Score</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="example2" class="table table-striped table-bordered bulk_action" style="width:100%" width="100%">
                        <thead>
                            <tr style="background: #f1f2f6; color: #2d3436;">
                                <th width="3%"><center>S/NO</center></th>
                                <th width="20%"><center>STUDENT NAME</center></th>
                                <th width="8%"><center>TOTAL QUES</center></th>
                                <th width="8%"><center>ANS</center></th>
                                <th width="8%"><center>CORRECT</center></th>
                                <th width="8%"><center>FAILED</center></th>
                                <th width="10%"><center>POINTS EARNED</center></th>
                                <th width="15%"><center>PERCENTAGE SCORE</center></th>
                            </tr>
                        </thead>
                        <tbody>
                                
                        </tbody>
                    </table>
                </div>
            </div>
			<hr>
		</div>
	</div>
		  
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
		function getStatus(testId, examineeTeststatus, examineeUserId, examineeName)
		{
			if(examineeTeststatus == 0)
			{
				return "<span class='status-badge status-notstarted'><i class='fa fa-clock-o'></i> Yet to start</span>";
			}
			else if(examineeTeststatus == 1)
			{
				return "<span class='status-badge status-inprogress'><i class='fa fa-spinner fa-spin'></i> In progress</span><br><a onclick='viewResult(\""+testId+"\",\""+examineeUserId+"\",\""+examineeName+"\")' style='cursor:pointer; font-size:11px; margin-top:5px; display:inline-block;' class='text-primary' title='View result'>View details</a>";
			}
			else if(examineeTeststatus == 2)
			{
				return "<span class='status-badge status-submitted'><i class='fa fa-check-circle'></i> Submitted</span><br><a onclick='viewResult(\""+testId+"\",\""+examineeUserId+"\",\""+examineeName+"\")' style='cursor:pointer; color:green; font-size:11px; margin-top:5px; display:inline-block;' title='View result'>View score</a>";
			}
		}
		
		function callExamineesStatus(testId) {
            var testName = $("#testId option:selected").text();
			$.ajax({
				url: "getExamineesStatus.php",
				type: "POST",
				data: {testId: testId},
				success: function(data) {
                    if(!data) return;
					var result = JSON.parse(data);
					var table = $('#example').DataTable();
					table.clear().destroy();
					
					var html = "";
					$.each(result, function(i, r) {
						var statusText = "";
						var statusClass = "";
						
						if (r.examineeTeststatus == 0) { statusText = "Not Started"; statusClass = "status-notstarted"; }
						else if (r.examineeTeststatus == 1) { 
                            statusText = r.isPaused == 1 ? "PAUSED" : "In Progress"; 
                            statusClass = r.isPaused == 1 ? "status-inprogress" : "status-submitted"; 
                        }
						else { statusText = "Submitted"; statusClass = "status-submitted"; }

						html += "<tr>";
						html += "<td>" + (i+1) + "</td>";
						html += "<td>" + r.examineeName + "</td>";
						html += "<td>" + r.classAndYearGroupName + "</td>";
						html += "<td>" + r.testDate + "</td>";
						html += "<td>" + r.duration + " mins</td>";
						html += "<td>" + r.timeStarted + "</td>";
						html += "<td>" + r.timeSubmitted + "</td>";
						html += "<td>" + r.remainingTime + " mins</td>";
						html += "<td>" + getStatus(testId, r.examineeTeststatus, r.examineeUserId, r.examineeName) + "</td>";
						html += "</tr>";
					});
					
					$("#example tbody").html(html);
					$('#example').DataTable({
						"paging": true,
						"lengthChange": true,
						"searching": true,
						"ordering": true,
						"info": true,
						"autoWidth": false,
                        "responsive": true
					});
				}
			});
		}
		//callExamineesStatus();
		
		//function call to open modal for editing a category
		function openEditModal(testId, testName, testDate, duration, startHour, startMinute, isAmOrPm, subjectId, yearGroupId, theReviewOption)
        {
            var modal1 = document.getElementById("myModal1");
			document.getElementById("testId").value = testId;  
			document.getElementById("testName1").value = testName;  
			document.getElementById("testDate1").value = testDate;  
			document.getElementById("testDuration1").value = duration;  
			$("#startHour1 option[value="+startHour+"]").attr('selected', 'selected');
			$("#startMinute1 option[value="+startMinute+"]").attr('selected', 'selected');
			$("#amOrPm1 option[value="+isAmOrPm+"]").attr('selected', 'selected');
			$("#subjectId1 option[value="+subjectId+"]").attr('selected', 'selected');
			$("#yearGroup1 option[value="+yearGroupId+"]").attr('selected', 'selected');
			$("#reviewOption1 option[value="+theReviewOption+"]").attr('selected', 'selected');
                
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
		
		//function to get text if an option is selected or not.
		function getSelectionText(selectedOption)
		{
			if(selectedOption == 0)
			{
				return "Not answered";
			}
			else if(selectedOption == "")
			{
				return "Not viewed";
			}
			else
			{
				return selectedOption;
			}
		}
		
		// Global variable to store student results for filtering
		var currentStudentResults = [];

		//function call to open modal viewing students' test questions answers
		function viewResult(testId, examineeUserId, examineeName)
        {
			$("#examineeName").html(examineeName);
            $("#questionCardsContainer").html('<div class="text-center" id="modalLoader"><i class="fa fa-spinner fa-spin fa-3x text-primary"></i><p>Loading result details...</p></div>');
            var modal1 = document.getElementById("myModal1");
            modal1.style.display = "block";
			
			$.ajax({
				url: "getStudentquestionsResult.php",
				type: "POST",        
				data: {testId : testId, examineeUserId : examineeUserId},
				dataType: 'JSON',
				success: function (response) {
					currentStudentResults = response || [];
					renderQuestionCards('all');
					updateModalStats();
				},
				error: function(xhr, status, error) {
					$("#questionCardsContainer").html('<div class="alert alert-danger">Error loading result details.</div>');
				}				
			});
		}

        function updateModalStats() {
            var totalMarkToBeEarned = 0;
            var totalMarkedObtained = 0;
            var correct = 0;
            var wrong = 0;
            var skipped = 0;

            currentStudentResults.forEach(function(r) {
                totalMarkToBeEarned += (+r.mark);
                totalMarkedObtained += (+r.markObtained);
                if (r.markStatus == 1) correct++;
                else if (r.markStatus == 2) wrong++;
                else skipped++;
            });

            var percentage = totalMarkToBeEarned > 0 ? Math.round((totalMarkedObtained/totalMarkToBeEarned) * 100) : 0;
            $("#mStatScore").text(percentage + "%");
            $("#mStatCorrect").text(correct);
            $("#mStatWrong").text(wrong);
            $("#mStatSkipped").text(skipped);
        }

        function renderQuestionCards(filter) {
            var container = $("#questionCardsContainer");
            container.empty();
            
            var filtered = currentStudentResults.filter(function(r) {
                if (filter === 'all') return true;
                if (filter === 'correct') return r.markStatus == 1;
                if (filter === 'wrong') return r.markStatus == 2;
                if (filter === 'skipped') return r.markStatus == 3;
                return true;
            });

            if (filtered.length === 0) {
                container.html('<div class="text-center" style="padding:40px; color:#95a5a6;"><i class="fa fa-info-circle fa-2x"></i><p>No questions found for this filter.</p></div>');
                return;
            }

            filtered.forEach(function(r, index) {
                var statusClass = "";
                var statusIcon = "";
                if (r.markStatus == 1) {
                    statusClass = "text-success";
                    statusIcon = "<i class='fa fa-check-circle'></i> Correct";
                } else if (r.markStatus == 2) {
                    statusClass = "text-danger";
                    statusIcon = "<i class='fa fa-times-circle'></i> Incorrect";
                } else {
                    statusClass = "text-muted";
                    statusIcon = "<i class='fa fa-minus-circle'></i> Not Answered";
                }

                var cardHtml = '<div class="question-card animated fadeIn">' +
                    '<div class="question-header">' +
                        '<span style="font-weight:700; color:#2d3436;">Question ' + (index + 1) + '</span>' +
                        '<span class="' + statusClass + '" style="font-weight:600;">' + statusIcon + ' (' + r.markObtained + '/' + r.mark + ' Marks)</span>' +
                    '</div>' +
                    '<div style="font-size:16px; color:#2d3436; margin-bottom:20px;">' + r.question + '</div>' +
                    '<div class="options-container">';

                ['A', 'B', 'C', 'D', 'E'].forEach(function(opt) {
                    var optionText = r['option' + opt];
                    if (!optionText || optionText.trim() === "") return;

                    var itemClass = "option-item";
                    var icon = "<i class='fa fa-circle-o'></i>";

                    // Logic for highlighting
                    if (r.selectedOption == opt) {
                        if (r.markStatus == 1) {
                            itemClass += " selected-correct";
                            icon = "<i class='fa fa-check-circle'></i>";
                        } else {
                            itemClass += " selected-incorrect";
                            icon = "<i class='fa fa-times-circle'></i>";
                        }
                    } else if (r.correctOption == opt) {
                        itemClass += " correct";
                        icon = "<i class='fa fa-check-circle-o'></i>";
                    }

                    cardHtml += '<div class="' + itemClass + '"><span>' + opt + '.</span> ' + optionText + ' <span style="margin-left:auto">' + icon + '</span></div>';
                });

                cardHtml += '</div>';
                if (r.solution && r.solution.trim() !== "") {
                    cardHtml += '<div style="margin-top:15px; padding:10px; background:#f1f2f6; border-radius:8px; font-size:13px; color:#636e72;">' +
                        '<strong><i class="fa fa-lightbulb-o"></i> Solution/Explanation:</strong> ' + r.solution + '</div>';
                }
                cardHtml += '</div>';
                container.append(cardHtml);
            });
        }

        // Handle filter clicks
        $(document).on('click', '.filter-tab', function() {
            $('.filter-tab').removeClass('active');
            $(this).addClass('active');
            renderQuestionCards($(this).data('filter'));
        });
		
		var scoreDistChart = null;
		var participationChart = null;

		function initCharts(scoreData, participationData) {
			var scoreCtx = document.getElementById('scoreDistChart').getContext('2d');
			var participationCtx = document.getElementById('participationChart').getContext('2d');

			if (scoreDistChart) scoreDistChart.destroy();
			if (participationChart) participationChart.destroy();

			scoreDistChart = new Chart(scoreCtx).Bar({
				labels: ["0-20%", "21-40%", "41-60%", "61-80%", "81-100%"],
				datasets: [{
					fillColor: "rgba(0, 174, 239, 0.5)",
					strokeColor: "rgba(0, 174, 239, 0.8)",
					highlightFill: "rgba(0, 174, 239, 0.75)",
					highlightStroke: "rgba(0, 174, 239, 1)",
					data: scoreData
				}]
			}, { responsive: true, scaleShowGridLines: false });

			participationChart = new Chart(participationCtx).Doughnut([
				{ value: participationData.submitted, color: "#27ae60", highlight: "#2ecc71", label: "Submitted" },
				{ value: participationData.inProgress, color: "#f39c12", highlight: "#f1c40f", label: "In Progress" },
				{ value: participationData.notStarted, color: "#95a5a6", highlight: "#bdc3c7", label: "Not Started" }
			], { responsive: true, segmentShowStroke: false, percentageInnerCutout: 60 });
		}

		function updateAnalytics(testId) {
            $("#tableSection").fadeIn();
            $("#analyticsSection").hide(); // Hidden by default now
			$.ajax({
				url: "getAllStudentsResults.php",
				type: "POST",
				data: {testId: testId},
				dataType: 'JSON',
				success: function(response) {
					if (!response || response.length === 0) {
                        $("#analyticsSection").fadeOut();
                        return;
                    }
					
					var len = response.length;
					var totalScore = 0;
					var submittedCount = 0;
					var topScore = 0;
					var distribution = [0, 0, 0, 0, 0];
					
					response.forEach(function(r) {
						var score = parseFloat(r.percentageScore);
						if (r.totalQuestionsAnswered > 0 || r.percentageScore > 0) {
							submittedCount++;
							totalScore += score;
							if (score > topScore) topScore = score;
							
							if (score <= 20) distribution[0]++;
							else if (score <= 40) distribution[1]++;
							else if (score <= 60) distribution[2]++;
							else if (score <= 80) distribution[3]++;
							else distribution[4]++;
						}
					});
					
					var avgScore = submittedCount > 0 ? Math.round(totalScore / submittedCount) : 0;
					var completionRate = Math.round((submittedCount / len) * 100);

					$("#statTotalExaminees").text(len);
					$("#statAvgScore").text(avgScore + "%");
					$("#statCompletionRate").text(completionRate + "%");
					$("#statTopScore").text(Math.round(topScore) + "%");

					var participationData = {
						submitted: submittedCount,
						inProgress: 0,
						notStarted: len - submittedCount
					};
					
					initCharts(distribution, participationData);
				}
			});
		}

		// Function to select a pulse card
		function selectTestChip(testId, testName) {
            $(".recent-chip").removeClass("active");
            $("#chip-" + testId).addClass("active");
            
            $("#testId").val(testId);
            $("#activeTestLabel").text("Status: " + testName);
            $("#quickActions").fadeIn();
            
            updateAnalytics(testId);
            callExamineesStatus(testId);
        }

        // Quick Harvest Function
        function quickHarvest(testId, testName) {
            $("#testId").val(testId);
            viewCompleteExamineesResult();
        }

		// Omni-Search Implementation
		$(document).on('keyup', '#omniSearch', function() {
            var table = $('#example').DataTable();
            table.search(this.value).draw();
        });

		//Fetching students of a test based on selected test
		$('#testId').on('change', function(){
			var testId = $(this).val();
			var testName = $("#testId option:selected").text();
			if(testId == "")
			{
				$('#example').DataTable().clear().destroy();
				$("#example tbody").empty();
                $("#analyticsSection, #tableSection, #quickActions").fadeOut();
                $(".recent-chip").removeClass("active");
			}
			else
			{
                $("#quickActions").fadeIn();
                $(".recent-chip").removeClass("active");
                $("#chip-" + testId).addClass("active");
                
				$("#activeTestLabel").text("Status: " + testName);
				updateAnalytics(testId);
				callExamineesStatus(testId);
			}
		});

		//Function to view results of all students for the selected test
		function viewCompleteExamineesResult()
		{
			$('#example2').DataTable().clear().destroy();
            
			var testId = document.getElementById('testId').value;
			var testName1 = $("#testId option:selected").text();
			if (testId == "")
			{
				$('.message5').html('<i class="fa fa-info-circle"></i> Please select test first')
			}
			else
			{
				$.ajax({
					url: "getAllStudentsResults.php",
					type: "POST",        
					data: {testId : testId},
					dataType: 'JSON',
					success: function (response) {
						if(!response || !Array.isArray(response))
						{
							response = [];
						}
						var len = response.length;
                        var rowsHtml = "";
                        var totalPercentage = 0;
                        var highest = 0;
                        var lowest = len > 0 ? 100 : 0;
                        var gradedCount = 0;

						for(var i=0; i<len; i++){
							var studentName = response[i].studentName;
							var totalQuestionsToBeAnswered = response[i].totalQuestionsToBeAnswered;
							var totalQuestionsAnswered = Number(response[i].totalQuestionsAnswered);
							var totalQuestionsAnsweredCorrectly = Number(response[i].totalQuestionsAnsweredCorrectly);
							var totalQuestionsAnsweredAndFailed = Number(response[i].totalQuestionsAnsweredAndFailed);
							var totalMarksEarned = response[i].totalMarksEarned;											
							var percentageScore = parseFloat(response[i].percentageScore);						
								
                            totalPercentage += percentageScore;
                            if (percentageScore > highest) highest = percentageScore;
                            if (percentageScore < lowest) lowest = percentageScore;
                            if (totalQuestionsAnswered > 0 || percentageScore > 0) gradedCount++;

                            var barColor = "score-low";
                            if (percentageScore >= 70) barColor = "score-high";
                            else if (percentageScore >= 45) barColor = "score-mid";

							rowsHtml += "<tr>" +
								"<td><center>" + (i+1) + "</center></td>" +
								"<td><center>" + studentName + "</center></td>" +
								"<td><center>" + totalQuestionsToBeAnswered + "</center></td>" +
								"<td><center>" + totalQuestionsAnswered + "</center></td>" +
								"<td><center>" + totalQuestionsAnsweredCorrectly + "</center></td>" +
								"<td><center>" + totalQuestionsAnsweredAndFailed + "</center></td>" +
								"<td><center>" + totalMarksEarned + "</center></td>" +
								"<td>" +
                                    "<div style='display:flex; align-items:center; gap:10px;'>" +
                                        "<div style='font-weight:700; width:40px;'>" + percentageScore + "%</div>" +
                                        "<div class='score-progress-container' style='flex:1;'>" +
                                            "<div class='score-progress-fill " + barColor + "' style='width:" + percentageScore + "%'></div>" +
                                        "</div>" +
                                    "</div>" +
                                "</td>" +
							"</tr>";
						}

                        // Generate Top Performers Highights
                        var sortedResponse = [...response].sort((a, b) => b.percentageScore - a.percentageScore);
                        var topHtml = "";
                        for(var k=0; k < Math.min(3, sortedResponse.length); k++) {
                            if (sortedResponse[k].percentageScore == 0) continue;
                            var medalColor = k==0 ? "#FFD700" : (k==1 ? "#C0C0C0" : "#CD7F32");
                            var medalIcon = k==0 ? "fa-trophy" : "fa-medal";
                            topHtml += `<div style="background:#fff; border:1px solid #eee; padding:10px 15px; border-radius:8px; display:flex; align-items:center; gap:10px; flex:1; min-width:150px; border-left: 4px solid ${medalColor}">
                                <div style="color:${medalColor}; font-size:20px;"><i class="fa ${medalIcon}"></i></div>
                                <div>
                                    <div style="font-size:11px; color:#636e72; text-transform:uppercase; font-weight:700;">Rank #${k+1}</div>
                                    <div style="font-weight:700; font-size:13px; color:#2d3436;">${sortedResponse[k].studentName}</div>
                                    <div style="font-size:12px; font-weight:700; color:${medalColor};">${sortedResponse[k].percentageScore}%</div>
                                </div>
                            </div>`;
                        }
                        $("#topPerformersSection").html(topHtml);

                        var avg = len > 0 ? Math.round(totalPercentage / len) : 0;
                        $("#m2StatAvg").text(avg + "%");
                        $("#m2StatHigh").text(highest + "%");
                        $("#m2StatLow").text(lowest + "%");

                        $("#example2 tbody").html(rowsHtml);
						$('#example2').DataTable( {
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

						
						$("#testName").html(testName1);
						var modal2 = document.getElementById("myModal2");
						modal2.style.display = "block";
					},
					error: function(xhr, status, error)
					{
						console.error("Error loading all students results:", error);
						$('.message5').html('<i class="fa fa-exclamation-circle"></i> Error loading results.');
					}				
				});
			}
		}
		var modal2 = document.getElementById("myModal2");
		var span2 = document.getElementsByClassName("close2")[0];
            
        // When the user clicks on <span> (x), close the modal1
        span2.onclick = function() {
			modal2.style.display = "none";
        }
	</script>
	


  </body>
</html>
