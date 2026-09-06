<?php
session_start();
	error_reporting(0);
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn'])) {
    header('location:../index.php');
}
include "../../db_connection/dlhs_db_connection.php";

// Get current session
$currentSessionQuery = "SELECT * FROM academic_sessions WHERE isCurrentSession = 1 LIMIT 1";
$currentSessionResult = $connection->query($currentSessionQuery);
$currentSession = $currentSessionResult ? $currentSessionResult->fetch_assoc() : null;
$availableSessions = [];
$sessionsResult = $connection->query("SELECT sessionId, sessionName, startDate, endDate, isCurrentSession, isActive FROM academic_sessions WHERE isActive = 1 ORDER BY startDate DESC");
if ($sessionsResult) {
  while ($row = $sessionsResult->fetch_assoc()) {
    $availableSessions[] = $row;
  }
}
$availableTerms = [];
$termsResult = $connection->query("SELECT termId, termName, isDefault FROM academic_terms WHERE isActive = 1 ORDER BY isDefault DESC, displayOrder ASC, termName ASC");
if ($termsResult) {
  while ($row = $termsResult->fetch_assoc()) {
    $availableTerms[] = $row;
  }
}
$availableExamTypes = [];
$examTypesResult = $connection->query("SELECT examTypeId, examTypeName, isDefault FROM exam_types WHERE isActive = 1 ORDER BY isDefault DESC, displayOrder ASC, examTypeName ASC");
if ($examTypesResult) {
  while ($row = $examTypesResult->fetch_assoc()) {
    $availableExamTypes[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <title>Test Archive Management | DLHS</title>

    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
    <link href="fontAwesome/css/fontawesome.css" rel="stylesheet">
    <link href="fontAwesome/css/brands.css" rel="stylesheet">
    <link href="fontAwesome/css/solid.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
    
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-card.active {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card.archived {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-card h3 {
            margin: 0;
            font-size: 36px;
            font-weight: bold;
        }
        .stat-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .action-buttons {
            margin: 20px 0;
        }
        .action-buttons .btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .archive-badge {
            background: #ff6b6b;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
        }
        .active-badge {
            background: #51cf66;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
        }
        .modal-header {
            background: #667eea;
            color: white;
        }
    </style>
</head>

<body>
  <section id="container" class="">
    <?php include "header.php"; ?>
    <?php include "sideBar.php"; ?>
    
    <section id="main-content">
      <section class="wrapper">
        <div class="row">
          <div class="col-lg-12">
            <h3 class="page-header"><i class="fa fa-archive"></i> Test Archive Management</h3>
                <option value="">All Terms</option>
          </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
                <option value="">All Types</option>
              <h3 id="archivedTestsCount">0</h3>
              <p><i class="fa fa-archive"></i> Archived Tests</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stat-card">
              <h3><?php echo $currentSession ? $currentSession['sessionName'] : 'N/A'; ?></h3>
              <p><i class="fa fa-calendar"></i> Current Session</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stat-card">
              <h3 id="totalTestsCount">0</h3>
              <p><i class="fa fa-file-text"></i> Total Tests</p>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
          <button class="btn btn-primary" onclick="showArchiveModal()">
            <i class="fa fa-archive"></i> Archive Selected Tests
          </button>
          <button class="btn btn-success" onclick="window.location.href='academic_settings.php'">
            <i class="fa fa-calendar-plus-o"></i> Manage Sessions
          </button>
          <button class="btn btn-warning" onclick="bulkRestoreTests()">
            <i class="fa fa-undo"></i> Restore Selected Tests
          </button>
          <button class="btn btn-info" onclick="showFilter()">
            <i class="fa fa-filter"></i> Filter Tests
          </button>
        </div>

        <!-- Filter Section -->
        <div class="filter-section" id="filterSection" style="display:none;">
          <h4><i class="fa fa-filter"></i> Filter Tests</h4>
          <div class="row">
            <div class="col-md-3">
              <label>Session</label>
              <select id="filterSession" class="form-control">
                <option value="">All Sessions</option>
              </select>
            </div>
            <div class="col-md-3">
              <label>Term</label>
              <select id="filterTerm" class="form-control">
                <option value="">All Terms</option>
                <option value="First Term">First Term</option>
                <option value="Second Term">Second Term</option>
                <option value="Third Term">Third Term</option>
              </select>
            </div>
            <div class="col-md-3">
              <label>Test Type</label>
              <select id="filterTestType" class="form-control">
                <option value="">All Types</option>
                <option value="CA1">CA1</option>
                <option value="CA2">CA2</option>
                <option value="CA3">CA3</option>
                <option value="Exam">Exam</option>
                <option value="Mock">Mock</option>
                <option value="Quiz">Quiz</option>
              </select>
            </div>
            <div class="col-md-3">
              <label>Status</label>
              <select id="filterStatus" class="form-control">
                <option value="active">Active Tests</option>
                <option value="archived">Archived Tests</option>
                <option value="all">All Tests</option>
              </select>
            </div>
          </div>
          <div class="row" style="margin-top:15px;">
            <div class="col-md-12">
              <button class="btn btn-primary" onclick="applyFilters()">
                <i class="fa fa-check"></i> Apply Filters
              </button>
              <button class="btn btn-default" onclick="clearFilters()">
                <i class="fa fa-times"></i> Clear Filters
              </button>
            </div>
          </div>
        </div>

        <!-- Tests Table -->
        <div class="row">
          <div class="col-md-12">
            <div class="panel panel-default">
              <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-list"></i> All Tests</h3>
              </div>
              <div class="panel-body">
                <div class="message10" style="color:red;" align="center"></div>
                <table id="testsTable" class="table table-striped table-bordered">
                  <thead>
                    <tr>
                      <th><input type="checkbox" id="selectAll"></th>
                      <th>ID</th>
                      <th>Test Name</th>
                      <th>Subject</th>
                      <th>Session</th>
                      <th>Term</th>
                      <th>Type</th>
                      <th>Date</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <!-- Loaded via AJAX -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

      </section>
    </section>

    <?php include "footer.php"; ?>
  </section>

  <!-- Archive Modal -->
  <div class="modal fade" id="archiveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-archive"></i> Archive Tests</h4>
        </div>
        <div class="modal-body">
          <p>You are about to archive <strong id="archiveCount">0</strong> test(s).</p>
          <div class="form-group">
            <label>Reason for Archiving (Optional)</label>
            <textarea id="archiveReason" class="form-control" rows="3" placeholder="Enter reason..."></textarea>
          </div>
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> Archived tests will be moved to the archive and won't appear in active tests list.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="confirmArchive()">
            <i class="fa fa-archive"></i> Archive Tests
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Session Management Modal -->
  <div class="modal fade" id="sessionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title"><i class="fa fa-calendar"></i> Manage Academic Sessions</h4>
        </div>
        <div class="modal-body">
          <div id="sessionsList">
            <!-- Loaded via AJAX -->
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="../../libs/jquery.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="../../datatables/js/jquery.dataTables.min.js"></script>
  <script>
    window.academicConfig = {
      sessions: <?php echo json_encode($availableSessions); ?>,
      terms: <?php echo json_encode($availableTerms); ?>,
      examTypes: <?php echo json_encode($availableExamTypes); ?>
    };
  </script>
  <script src="test_archive_management.js"></script>

</body>
</html>
