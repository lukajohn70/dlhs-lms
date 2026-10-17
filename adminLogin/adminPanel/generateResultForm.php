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
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DLHS Admin - Generate Results">
    <meta name="author" content="DLHS IT Department">
    <title>Generate Student Results | DLHS</title>

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- bootstrap theme -->
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <!-- font icon -->
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
	<link href="fontAwesome/css/fontawesome.css" rel="stylesheet">
	<link href="fontAwesome/css/solid.css" rel="stylesheet">
    <!-- Custom styles -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
	<link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>

	<style>
        .filter-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,51,102,0.07);
            padding: 24px 28px 20px 28px;
            margin-bottom: 24px;
            border: 1px solid #e8edf3;
        }
        .filter-card h4 {
            color: #003366;
            font-weight: 700;
            margin-bottom: 18px;
            border-bottom: 2px solid #003366;
            padding-bottom: 10px;
        }
        .btn-generate {
            background: linear-gradient(135deg, #003366, #005599);
            color: #ffd700;
            border: none;
            border-radius: 7px;
            font-weight: 700;
            padding: 10px 28px;
            font-size: 14px;
            transition: all 0.2s;
        }
        .btn-generate:hover { filter: brightness(115%); color: #ffd700; }
        .result-table-wrap { display: none; }
        .student-row-link { cursor: pointer; }
        .student-row-link:hover td { background: #e8f4fd !important; }
        #statusMsg { font-size: 13px; font-weight: 600; }
        .badge-term {
            background: #003366; color: #ffd700;
            border-radius: 5px; padding: 3px 10px;
            font-size: 12px; font-weight: 700;
        }
        .print-btn-col a {
            color: #003366;
            font-size: 18px;
        }
        .print-btn-col a:hover { color: #d4a000; }
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
					<h3 class="page-header"><i class="fa fa-file-text-o"></i> Generate Student Results</h3>
					<ol class="breadcrumb">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-file-text-o"></i>Result Processing</li>
						<li>Generate Result</li>
						<a href="#" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
					</ol>
				</div>
			</div>

            <!-- Filter Panel -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="filter-card">
                        <h4><i class="fa fa-filter"></i> Select Class &amp; Term</h4>
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Year Group</label>
                                    <select class="form-control" id="yearGroupId" onchange="getClasses()">
                                        <option value="">-- Select Year Group --</option>
                                        <?php
                                            $yeargroups = "SELECT * FROM yeargroup ORDER BY yearGroupName ASC";
                                            $result1 = $connection->query($yeargroups);
                                            while($row1 = $result1->fetch_array(MYSQLI_NUM)){
                                        ?>
                                        <option value="<?php echo $row1[0]; ?>"><?php echo $row1[1]; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Class</label>
                                    <select class="form-control" id="classId">
                                        <option value="">-- Select Class --</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Academic Session</label>
                                    <select class="form-control" id="academicSession">
                                        <option value="">-- Select Session --</option>
                                        <?php
                                            $sessions = "SELECT * FROM academic_year ORDER BY academicYearId DESC";
                                            $result2 = $connection->query($sessions);
                                            if ($result2) while($row2 = $result2->fetch_array(MYSQLI_NUM)){
                                        ?>
                                        <option value="<?php echo $row2[0]; ?>"><?php echo $row2[1]; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Term</label>
                                    <select class="form-control" id="termId">
                                        <option value="">-- Select Term --</option>
                                        <?php
                                            $terms = "SELECT * FROM terms ORDER BY termId ASC";
                                            $result3 = $connection->query($terms);
                                            while($row3 = $result3->fetch_array(MYSQLI_NUM)){
                                        ?>
                                        <option value="<?php echo $row3[0]; ?>"><?php echo $row3[1]; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Result Type</label>
                                    <select class="form-control" id="resultType">
                                        <option value="end_of_term">End of Term</option>
                                        <option value="mid_term">Mid Term</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-1" style="display:flex; align-items:flex-end; padding-bottom:15px;">
                                <button class="btn-generate" onclick="loadStudents()">
                                    <i class="fa fa-search"></i> Load
                                </button>
                            </div>
                        </div>
                        <div id="statusMsg" style="color:#003366; margin-top:6px;"></div>
                    </div>
                </div>
            </div>

            <!-- Results Table -->
            <div class="row result-table-wrap" id="resultTableWrap">
                <div class="col-lg-12">
                    <section class="panel">
                        <header class="panel-heading" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                            <div>
                                <i class="fa fa-list"></i> Students &mdash;
                                <span id="classLabel" class="badge-term"></span>
                                <span style="font-size:12px; color:#888; margin-left:10px;">Click a student row or the print icon to view/print their report card.</span>
                            </div>
                            <button id="btnPrintAll" class="btn-generate" onclick="printAllResults()" title="Open all report cards for bulk printing" style="font-size:12px; padding:7px 16px;">
                                <i class="fa fa-print"></i> Print All Results
                            </button>
                        </header>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table id="studentTable" class="table table-striped table-bordered" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>S/N</th>
                                            <th>Student Name</th>
                                            <th>Student ID</th>
                                            <th>Class</th>
                                            <th style="text-align:center;">View / Print Report</th>
                                        </tr>
                                    </thead>
                                    <tbody id="studentTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

          </section><!-- End of section wrapper -->
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
    <!-- custom form component script -->
    <script src="js/form-component.js"></script>
    <!-- custome script for all page -->
    <script src="js/scripts.js"></script>
	<script src="../../datatables/js/jquery.dataTables.min.js"></script>
	<script src="../../datatables/js/dataTables.responsive.min.js"></script>
	<script src="../../datatables/js/dataTables.buttons.min.js"></script>
	<script src="../../datatables/js/buttons.flash.min.js"></script>
	<script src="../../datatables/js/jszip.min.js"></script>
	<script src="../../datatables/js/pdfmake.min.js"></script>
	<script src="../../datatables/js/vfs_fonts.js"></script>
	<script src="../../datatables/js/buttons.html5.min.js"></script>
	<script src="../../datatables/js/buttons.print.min.js"></script>
	<script>

        // Fetch classes when year group changes
        function getClasses() {
            var selectedYearGroup = $("#yearGroupId option:selected").val();
            var theYearGroup = $("#yearGroupId option:selected").text();
            if (!selectedYearGroup) {
                $("#classId").html('<option value="">-- Select Class --</option>');
                return;
            }
            $.ajax({
                type: "POST",
                url: "getClassToAssignFormTeacher.php",
                data: { yearGroup: selectedYearGroup, yearGroupName: theYearGroup }
            }).done(function(data) {
                $("#classId").html(data);
            });
        }

        var dtInstance = null;

        function loadStudents() {
            var yearGroupId  = $("#yearGroupId").val();
            var classId      = $("#classId").val();
            var sessionId    = $("#academicSession").val();
            var termId       = $("#termId").val();

            if (!yearGroupId || !classId || !sessionId || !termId) {
                $("#statusMsg").html('<span style="color:red;"><i class="fa fa-exclamation-circle"></i> Please select Year Group, Class, Session and Term.</span>');
                return;
            }

            var yearGroupName = $("#yearGroupId option:selected").text();
            var className     = $("#classId option:selected").text();
            var sessionName   = $("#academicSession option:selected").text();
            var termName      = $("#termId option:selected").text();

            $("#statusMsg").html('<i class="fa fa-spinner fa-spin"></i> Loading students...');

            $.ajax({
                url: "getStudentsRecordsBasedOnCriteria.php",
                type: "POST",
                data: { selectedYearGroupId: yearGroupId, selectedClassId: classId },
                dataType: "JSON",
                success: function(response) {
                    if (!response || response.length === 0) {
                        $("#statusMsg").html('<span style="color:#e74c3c;"><i class="fa fa-info-circle"></i> No students found for the selected class.</span>');
                        $("#resultTableWrap").hide();
                        return;
                    }

                    // Build report URL base — uses correct params expected by print_term_report.php
                    var reportBase = "../../staffLogin/staffPanel/print_term_report.php";

                    // Store class-level params for Print All button
                    window._dlhsPrintAll = {
                        classId:     classId,
                        sessionName: sessionName,
                        termId:      termId
                    };

                    // Destroy old DataTable if exists
                    if (dtInstance) {
                        dtInstance.clear().destroy();
                        dtInstance = null;
                    }
                    $("#studentTableBody").html("");

                    $.each(response, function(i, student) {
                        var studentId   = student.studentId;
                        var firstName   = student.firstName || '';
                        var lastName    = student.surname   || '';
                        var studentName = (firstName + ' ' + lastName).trim();
                        if (!studentName) studentName = 'Student #' + (i+1);

                        // NOTE: print_term_report.php expects academicSession (name) and academicTerm (id)
                        var resultType = $("#resultType").val();
                        var reportUrl = reportBase
                            + "?studentId="       + encodeURIComponent(studentId)
                            + "&academicSession=" + encodeURIComponent(sessionName)
                            + "&academicTerm="    + encodeURIComponent(termId)
                            + "&classId="         + encodeURIComponent(classId)
                            + "&resultType="      + encodeURIComponent(resultType)
                            + "&autoPrint=1";

                        var tr = "<tr class='student-row-link' onclick=\"window.open('" + reportUrl + "', '_blank')\" title='Click to view/print report for " + studentName + "'>" +
                            "<td>" + (i + 1) + "</td>" +
                            "<td><i class='fa fa-user-o' style='color:#003366; margin-right:6px;'></i>" + studentName + "</td>" +
                            "<td>" + studentId + "</td>" +
                            "<td>" + yearGroupName + " " + className + "</td>" +
                            "<td class='print-btn-col' style='text-align:center;'>" +
                                "<a href='" + reportUrl + "' target='_blank' title='View/Print Report Card' onclick='event.stopPropagation();'>" +
                                    "<i class='fa fa-print'></i>" +
                                "</a>" +
                            "</td>" +
                        "</tr>";

                        $("#studentTableBody").append(tr);
                    });

                    // Initialise DataTable
                    dtInstance = $('#studentTable').DataTable({
                        "paging":    true,
                        "ordering":  true,
                        "info":      true,
                        "responsive": true,
                        dom: 'lBfrtip',
                        buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
                    });

                    $("#classLabel").text(yearGroupName + " " + className + " | " + sessionName + " | " + termName);
                    $("#resultTableWrap").show();
                    $("#statusMsg").html('<span style="color:green;"><i class="fa fa-check-circle"></i> ' + response.length + ' student(s) loaded.</span>');
                },
                error: function() {
                    $("#statusMsg").html('<span style="color:red;"><i class="fa fa-times-circle"></i> Failed to load students. Please try again.</span>');
                }
            });
        }

        // Print All: opens class-wide report in one tab (all students, page-break per student)
        function printAllResults() {
            if (!window._dlhsPrintAll || !window._dlhsPrintAll.classId) {
                alert('Please load students first before using Print All.');
                return;
            }
            var p = window._dlhsPrintAll;
            var resultType = $("#resultType").val();
            var reportBase = "../../staffLogin/staffPanel/print_term_report.php";
            var url = reportBase
                + "?classId="         + encodeURIComponent(p.classId)
                + "&academicSession=" + encodeURIComponent(p.sessionName)
                + "&academicTerm="    + encodeURIComponent(p.termId)
                + "&resultType="      + encodeURIComponent(resultType)
                + "&autoPrint=1";
            window.open(url, '_blank');
        }

	</script>


  </body>
</html>
