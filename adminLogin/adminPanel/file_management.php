<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

dlhsEnsureAllFileManagementTablesExist($connection);

if (!isset($_SESSION['adminLoggedIn']) || $_SESSION['adminLoggedIn'] !== "yes") {
    header("location:../index.php");
    exit();
}

$adminId = isset($_SESSION['adminId']) && is_numeric($_SESSION['adminId']) ? (int)$_SESSION['adminId'] : 1;
$adminName = isset($_SESSION['adminName']) ? $_SESSION['adminName'] : (isset($_SESSION['adminEmail']) ? $_SESSION['adminEmail'] : 'Admin Portal');

// Fetch categories for upload dropdown
$catRes = $connection->query("SELECT * FROM file_categories ORDER BY categoryId ASC");
$categories = [];
if ($catRes) {
    while ($c = $catRes->fetch_assoc()) {
        $categories[] = $c;
    }
}

// Fetch subjects for dropdown
$subjRes = $connection->query("SELECT * FROM subjects ORDER BY subjectName ASC");
$subjects = [];
if ($subjRes) {
    while ($s = $subjRes->fetch_assoc()) {
        $subjects[] = $s;
    }
}

// Fetch classes
$classRes = $connection->query("SELECT c.*, y.yearGroupName FROM classes c LEFT JOIN yeargroup y ON c.classYearGroup = y.yearGroupId ORDER BY y.yearGroupName, c.className");
$classes = [];
if ($classRes) {
    while ($cl = $classRes->fetch_assoc()) {
        $classes[] = $cl;
    }
}

// Fetch teaching staff for targeted sharing
$staffRes = $connection->query("SELECT s.staffId, CONCAT(s.surname, ' ', s.firstName) as fullName, s.username,
                                       GROUP_CONCAT(DISTINCT sub.subjectName ORDER BY sub.subjectName SEPARATOR ', ') as subjects
                                FROM stafflogin s
                                LEFT JOIN subject_teacher_assignment sta ON s.staffId = sta.teacherId
                                LEFT JOIN subjects sub ON sta.subjectId = sub.subjectId
                                GROUP BY s.staffId, s.surname, s.firstName, s.username
                                ORDER BY s.surname ASC, s.firstName ASC");
$teachers = [];
if ($staffRes) {
    while ($t = $staffRes->fetch_assoc()) {
        $teachers[] = $t;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <title>File Sharing Hub | DLHS Admin</title>

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />    
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
    <link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>

    <style>
        :root {
            --primary: #00AEEF;
            --primary-dark: #0087ba;
            --accent: #0acca2;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f7f6;
            color: #333;
        }

        .wrapper {
            padding: 25px !important;
        }

        .stat-card {
            border-radius: 12px;
            padding: 20px 24px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .stat-card i.stat-bg-icon {
            position: absolute;
            right: 15px;
            bottom: -10px;
            font-size: 80px;
            opacity: 0.15;
        }

        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 5px 0;
        }

        .stat-card p {
            font-size: 13px;
            margin: 0;
            opacity: 0.9;
            font-weight: 500;
        }

        .stat-card-blue { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); }
        .stat-card-teal { background: linear-gradient(135deg, #00AEEF 0%, #0077b6 100%); }
        .stat-card-orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-card-purple { background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%); }

        .premium-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(0, 174, 239, 0.15);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .premium-card-header {
            background: linear-gradient(135deg, #00AEEF 0%, #0077b6 100%);
            color: white;
            padding: 18px 24px;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .premium-card-body {
            padding: 25px;
        }

        .nav-tabs-custom {
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .nav-tabs-custom li a {
            border: none !important;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 14px;
            color: #64748b;
            border-radius: 8px 8px 0 0;
            background: transparent;
            transition: all 0.2s;
        }

        .nav-tabs-custom li.active a, .nav-tabs-custom li a:hover {
            color: #00AEEF !important;
            background: #f0f9ff !important;
            border-bottom: 3px solid #00AEEF !important;
        }

        .btn-action-hub {
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 4px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-share-main {
            background: linear-gradient(135deg, #0acca2 0%, #0087ba 100%);
            color: white;
            border: none;
            padding: 10px 22px;
            font-weight: 600;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(10, 204, 162, 0.25);
            transition: all 0.2s;
        }
        .btn-share-main:hover {
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(10, 204, 162, 0.35);
        }

        .upload-area {
            border: 2px dashed #00AEEF;
            border-radius: 8px;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(0, 174, 239, 0.03);
        }
        .upload-area:hover, .upload-area.dragover {
            background: rgba(0, 174, 239, 0.08);
            border-color: #0077b6;
        }

        .teachers-list-panel {
            max-height: 180px;
            overflow-y: auto;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px;
            background: #f8fafc;
        }

        .teacher-item {
            padding: 6px 8px;
            border-radius: 4px;
            transition: background 0.15s;
        }
        .teacher-item:hover {
            background: #e2e8f0;
        }
    </style>
</head>
<body>
  <section id="container">
      <?php include 'header.php'; ?>
      <?php include 'sideBar.php'; ?>
      <?php session_write_close(); ?>

      <section id="main-content">
          <section class="wrapper">
              <!-- Page Header -->
              <div class="row">
                  <div class="col-lg-12">
                      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                          <div>
                              <h3 class="page-header" style="font-weight: 700; color: #0c2340; margin: 0 0 5px 0;">
                                  <i class="fa fa-share-alt"></i> File Sharing Hub
                              </h3>
                              <p style="margin: 0; color: #64748b; font-size: 13px;">
                                  Review teacher submissions (lesson plans, reports) and broadcast circulars/memos to staff & students
                              </p>
                          </div>
                          <div>
                              <button class="btn btn-share-main" onclick="openShareModal()">
                                  <i class="fa fa-plus-circle"></i> Share New Document / Circular
                              </button>
                          </div>
                      </div>
                  </div>
              </div>

              <!-- Stats Dashboard -->
              <div class="row">
                  <div class="col-md-3 col-sm-6">
                      <div class="stat-card stat-card-orange">
                          <i class="fa fa-inbox stat-bg-icon"></i>
                          <h3 id="statSubmissions">0</h3>
                          <p id="statSubmissionsSub">Teacher Submissions to Admin</p>
                      </div>
                  </div>
                  <div class="col-md-3 col-sm-6">
                      <div class="stat-card stat-card-teal">
                          <i class="fa fa-paper-plane stat-bg-icon"></i>
                          <h3 id="statAdminShared">0</h3>
                          <p>Documents Shared by Admin</p>
                      </div>
                  </div>
                  <div class="col-md-3 col-sm-6">
                      <div class="stat-card stat-card-blue">
                          <i class="fa fa-users stat-bg-icon"></i>
                          <h3 id="statStaffCount"><?php echo count($teachers); ?></h3>
                          <p>Active Teaching Staff</p>
                      </div>
                  </div>
                  <div class="col-md-3 col-sm-6">
                      <div class="stat-card stat-card-purple">
                          <i class="fa fa-hdd-o stat-bg-icon"></i>
                          <h3 id="statStorage">0 MB</h3>
                          <p id="statStorageSub">Total School Shared Files</p>
                      </div>
                  </div>
              </div>

              <!-- Main Card with Tabs -->
              <div class="premium-card">
                  <div class="premium-card-header">
                      <span><i class="fa fa-folder-open"></i> Institutional Document Management</span>
                      <button class="btn btn-default btn-xs" onclick="loadAdminFiles()" title="Refresh Data" style="color: #0077b6; font-weight: 600;">
                          <i class="fa fa-refresh"></i> Refresh
                      </button>
                  </div>
                  <div class="premium-card-body">
                      <!-- Tabs Navigation -->
                      <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                          <li role="presentation" class="active">
                              <a href="#tabSubmissions" aria-controls="tabSubmissions" role="tab" data-toggle="tab">
                                  <i class="fa fa-inbox"></i> Submissions from Teachers 
                                  <span class="badge" id="badgeSubmissions" style="background-color: #f59e0b; margin-left: 5px;">0</span>
                              </a>
                          </li>
                          <li role="presentation">
                              <a href="#tabAdminShared" aria-controls="tabAdminShared" role="tab" data-toggle="tab">
                                  <i class="fa fa-bullhorn"></i> Shared by Admin 
                                  <span class="badge" id="badgeAdminShared" style="background-color: #00AEEF; margin-left: 5px;">0</span>
                              </a>
                          </li>
                          <li role="presentation">
                              <a href="#tabAllFiles" aria-controls="tabAllFiles" role="tab" data-toggle="tab">
                                  <i class="fa fa-database"></i> All School Files Directory 
                                  <span class="badge" id="badgeAllFiles" style="background-color: #6366f1; margin-left: 5px;">0</span>
                              </a>
                          </li>
                      </ul>

                      <!-- Tab Panes -->
                      <div class="tab-content">
                          <!-- Tab 1: Submissions from Teachers -->
                          <div role="tabpanel" class="tab-pane active" id="tabSubmissions">
                              <div style="margin-bottom: 15px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                  <label style="font-size: 13px; font-weight: 600; margin: 0;">Filter Status:</label>
                                  <button class="btn btn-xs btn-default filter-sub-btn active" onclick="filterSubmissions('all')">All</button>
                                  <button class="btn btn-xs btn-warning filter-sub-btn" onclick="filterSubmissions('pending')">Pending Review</button>
                                  <button class="btn btn-xs btn-success filter-sub-btn" onclick="filterSubmissions('reviewed')">Reviewed / Acknowledged</button>
                              </div>
                              <div id="submissionsTableContainer">
                                  <p class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading teacher submissions...</p>
                              </div>
                          </div>

                          <!-- Tab 2: Shared by Admin -->
                          <div role="tabpanel" class="tab-pane" id="tabAdminShared">
                              <div id="adminSharedTableContainer">
                                  <p class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading admin shared documents...</p>
                              </div>
                          </div>

                          <!-- Tab 3: All School Files Directory -->
                          <div role="tabpanel" class="tab-pane" id="tabAllFiles">
                              <div id="allFilesTableContainer">
                                  <p class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading school files directory...</p>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </section>
      </section>
  </section>

  <!-- Modal: Share New File / Circular -->
  <div class="modal fade" id="shareModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
              <div class="modal-header" style="background: linear-gradient(135deg, #00AEEF 0%, #0077b6 100%); color: white;">
                  <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.8;">&times;</button>
                  <h4 class="modal-title" style="font-weight: 700;"><i class="fa fa-share-alt"></i> Share Document or Circular</h4>
              </div>
              <form id="adminUploadForm" enctype="multipart/form-data">
                  <div class="modal-body" style="padding: 25px;">
                      <!-- File vs Link Toggle -->
                      <div class="d-flex justify-content-center" style="gap: 20px; margin-bottom: 20px; display: flex;">
                          <label style="font-weight: 600; cursor: pointer;">
                              <input type="radio" name="uploadType" value="file" checked onchange="toggleAdminUploadType('file')"> File Upload
                          </label>
                          <label style="font-weight: 600; cursor: pointer; margin-left: 20px;">
                              <input type="radio" name="uploadType" value="link" onchange="toggleAdminUploadType('link')"> YouTube Link
                          </label>
                      </div>

                      <!-- Dropzone -->
                      <div class="upload-area" id="adminUploadArea">
                          <i class="fa fa-cloud-upload" style="font-size: 38px; color: #00AEEF; margin-bottom: 8px;"></i>
                          <h5 style="margin: 0 0 4px 0; font-weight: 600;">Drag & Drop Document Here</h5>
                          <p style="color: #64748b; font-size: 12px; margin: 0;">or click to browse files (PDF, Word, Excel, PowerPoint, Zip, etc. Max: 100MB)</p>
                          <input type="file" id="adminFileInput" name="files[]" style="display: none;" multiple>
                      </div>
                      <div id="adminFilePreview" style="margin-bottom: 15px;"></div>

                      <!-- YouTube Input -->
                      <div id="adminYoutubeArea" style="display: none; margin-bottom: 15px;">
                          <label style="font-weight: 600; font-size: 13px;">YouTube URL *</label>
                          <input type="url" class="form-control" id="adminYoutubeLink" name="youtubeLink" placeholder="https://www.youtube.com/watch?v=...">
                      </div>

                      <!-- Title -->
                      <div class="form-group">
                          <label style="font-weight: 600; font-size: 13px;">Document Title *</label>
                          <input type="text" class="form-control" name="title" id="adminTitle" placeholder="e.g. 2026/2027 Academic Calendar & Staff Guidelines" required>
                      </div>

                      <div class="row">
                          <!-- Category -->
                          <div class="col-md-6 form-group">
                              <label style="font-weight: 600; font-size: 13px;">Category *</label>
                              <select class="form-control" name="categoryId" id="adminCategoryId" required>
                                  <?php foreach ($categories as $cat): ?>
                                      <option value="<?php echo $cat['categoryId']; ?>" <?php echo $cat['categoryId'] == 9 ? 'selected' : ''; ?>>
                                          <?php echo htmlspecialchars($cat['categoryName']); ?>
                                      </option>
                                  <?php endforeach; ?>
                              </select>
                          </div>

                          <!-- Target Audience -->
                          <div class="col-md-6 form-group">
                              <label style="font-weight: 600; font-size: 13px;">Share With *</label>
                              <select class="form-control" name="uploadedFor" id="adminUploadedFor" onchange="handleAdminAudienceChange()" required>
                                  <option value="all_teachers">👨‍🏫 All Teaching Staff (Broadcast)</option>
                                  <option value="specific_teachers">👤 Specific Teacher(s)</option>
                                  <option value="all">👥 All Students</option>
                                  <option value="specific_class">🏫 Specific Class Arm</option>
                              </select>
                          </div>
                      </div>

                      <!-- Target Specific Teachers (Hidden by default) -->
                      <div class="form-group" id="adminTargetTeachersRow" style="display: none;">
                          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                              <label style="font-weight: 600; font-size: 13px; margin: 0;">Select Target Teachers *</label>
                              <input type="text" id="teacherSearchBox" placeholder="Search teacher or subject..." class="form-control input-sm" style="width: 220px;" oninput="filterTeacherSelection()">
                          </div>
                          <div class="teachers-list-panel" id="teachersChecklist">
                              <div style="margin-bottom: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">
                                  <label style="font-weight: 700; cursor: pointer;">
                                      <input type="checkbox" id="selectAllTeachersAdmin" onchange="toggleSelectAllTeachersAdmin()"> Select All Listed Teachers
                                  </label>
                              </div>
                              <?php foreach ($teachers as $t): ?>
                                  <div class="teacher-item teacher-row-item" data-text="<?php echo strtolower(htmlspecialchars($t['fullName'] . ' ' . $t['subjects'])); ?>">
                                      <label style="font-weight: normal; cursor: pointer; display: flex; align-items: center; gap: 8px; margin: 0;">
                                          <input type="checkbox" name="selectedTeachers[]" value="<?php echo $t['staffId']; ?>" class="adminTeacherCheckbox">
                                          <span>
                                              <strong><?php echo htmlspecialchars($t['fullName']); ?></strong> 
                                              <small class="text-muted">(<?php echo htmlspecialchars($t['subjects'] ?: 'General'); ?>)</small>
                                          </span>
                                      </label>
                                  </div>
                              <?php endforeach; ?>
                          </div>
                      </div>

                      <!-- Target Class (Hidden by default) -->
                      <div class="form-group" id="adminTargetClassRow" style="display: none;">
                          <label style="font-weight: 600; font-size: 13px;">Select Class Arm *</label>
                          <select class="form-control" name="targetClass" id="adminTargetClass">
                              <option value="">-- Select Class --</option>
                              <?php foreach ($classes as $cl): ?>
                                  <option value="<?php echo $cl['classId']; ?>"><?php echo htmlspecialchars($cl['yearGroupName'] . ' - ' . $cl['className']); ?></option>
                              <?php endforeach; ?>
                          </select>
                      </div>

                      <!-- Subject (Optional) -->
                      <div class="form-group">
                          <label style="font-weight: 600; font-size: 13px;">Related Subject (Optional)</label>
                          <select class="form-control" name="subject" id="adminSubject">
                              <option value="0">General / Institutional / Not Subject Specific</option>
                              <?php foreach ($subjects as $sb): ?>
                                  <option value="<?php echo $sb['subjectId']; ?>"><?php echo htmlspecialchars($sb['subjectName']); ?></option>
                              <?php endforeach; ?>
                          </select>
                      </div>

                      <!-- Description -->
                      <div class="form-group">
                          <label style="font-weight: 600; font-size: 13px;">Notes / Instructions (Optional)</label>
                          <textarea class="form-control" name="description" id="adminDescription" rows="3" placeholder="Add instructions, context, or deadlines..."></textarea>
                      </div>

                      <!-- Progress -->
                      <div class="progress" id="adminUploadProgress" style="display: none; height: 10px; margin-top: 15px; border-radius: 4px;">
                          <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%; background-color: #00AEEF;"></div>
                      </div>
                  </div>
                  <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-primary" style="background: #00AEEF; border-color: #0087ba; font-weight: 600;">
                          <i class="fa fa-paper-plane"></i> Broadcast / Share Document
                      </button>
                  </div>
              </form>
          </div>
      </div>
  </div>

  <!-- Modal: Review & Feedback for Teacher Submission -->
  <div class="modal fade" id="reviewModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
          <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
              <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                  <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                  <h4 class="modal-title" style="font-weight: 700;"><i class="fa fa-pencil-square-o"></i> Review Teacher Submission</h4>
              </div>
              <form id="reviewForm">
                  <input type="hidden" name="fileId" id="reviewFileId">
                  <div class="modal-body" style="padding: 20px;">
                      <div style="background: #f8fafc; padding: 12px 16px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #e2e8f0;">
                          <h5 id="reviewDocTitle" style="font-weight: 700; margin: 0 0 5px 0; color: #1e293b;"></h5>
                          <p id="reviewTeacherInfo" style="margin: 0; font-size: 13px; color: #64748b;"></p>
                      </div>

                      <div class="form-group">
                          <label style="font-weight: 600; font-size: 13px;">Review Status *</label>
                          <select class="form-control" name="status" id="reviewStatus" required>
                              <option value="reviewed">✅ Reviewed (Approval / Feedback provided)</option>
                              <option value="acknowledged">👍 Acknowledged (Received and noted)</option>
                              <option value="pending">⏳ Pending Review (Keep under review)</option>
                          </select>
                      </div>

                      <div class="form-group">
                          <label style="font-weight: 600; font-size: 13px;">Administrator Feedback & Comments</label>
                          <textarea class="form-control" name="notes" id="reviewNotes" rows="4" placeholder="Enter comments, corrections, or approval notes for the teacher..."></textarea>
                          <small class="text-muted">The teacher will be able to see these comments and the review status in their File Sharing Hub.</small>
                      </div>
                  </div>
                  <div class="modal-footer" style="background: #f8fafc;">
                      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-success" style="font-weight: 600;">
                          <i class="fa fa-check"></i> Save Review & Feedback
                      </button>
                  </div>
              </form>
          </div>
      </div>
  </div>

  <!-- Modal: File Content Preview -->
  <div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
              <div class="modal-header" style="background: #1e293b; color: white;">
                  <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                  <h4 class="modal-title" style="font-weight: 600;"><i class="fa fa-eye"></i> Document Preview</h4>
              </div>
              <div class="modal-body" id="previewModalBody" style="padding: 20px; max-height: 80vh; overflow-y: auto;">
                  <p class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading preview...</p>
              </div>
          </div>
      </div>
  </div>

  <!-- JS Dependencies -->
  <script src="js/jquery.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/jquery.scrollTo.min.js"></script>
  <script src="js/jquery.nicescroll.js" type="text/javascript"></script>
  <script type="text/javascript" src="../../datatables/js/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="../../datatables/js/responsive.dataTables.min.js"></script>
  <script src="js/custom.js"></script>
  <script src="js/premium_modal.js"></script>

  <script>
      let submissionsData = [];
      let adminSharedData = [];
      let allFilesData = [];
      let subDataTable = null;
      let adminDataTable = null;
      let allDataTable = null;

      $(document).ready(function() {
          loadAdminFiles();

          // Upload dropzone events
          const uploadArea = document.getElementById('adminUploadArea');
          const fileInput = document.getElementById('adminFileInput');

          uploadArea.addEventListener('dragover', (e) => {
              e.preventDefault();
              uploadArea.classList.add('dragover');
          });

          uploadArea.addEventListener('dragleave', () => {
              uploadArea.classList.remove('dragover');
          });

          uploadArea.addEventListener('drop', (e) => {
              e.preventDefault();
              uploadArea.classList.remove('dragover');
              fileInput.files = e.dataTransfer.files;
              handleAdminFiles(fileInput.files);
          });

          uploadArea.addEventListener('click', () => {
              fileInput.click();
          });

          fileInput.addEventListener('change', (e) => {
              handleAdminFiles(e.target.files);
          });

          // Upload Form submit
          $('#adminUploadForm').on('submit', function(e) {
              e.preventDefault();
              submitAdminUpload();
          });

          // Review Form submit
          $('#reviewForm').on('submit', function(e) {
              e.preventDefault();
              submitReview();
          });
      });

      function loadAdminFiles() {
          $.ajax({
              url: 'get_admin_files.php',
              type: 'GET',
              dataType: 'json',
              success: function(res) {
                  if (res.success) {
                      submissionsData = res.teacherSubmissions || [];
                      adminSharedData = res.adminSharedFiles || [];
                      allFilesData = res.allSystemFiles || [];

                      // Update Stats
                      const s = res.stats || {};
                      $('#statSubmissions').text(s.totalSubmissions || 0);
                      $('#statSubmissionsSub').html(`Teacher Submissions <small style="color: #fef08a;">(${s.pendingCount || 0} pending review)</small>`);
                      $('#statAdminShared').text(s.adminSharedCount || 0);
                      $('#statStaffCount').text(s.staffCount || 0);
                      $('#statStorage').text(formatFileSize(s.totalStorageBytes || 0));
                      $('#statStorageSub').text(`${s.allFilesCount || 0} Total Shared Files`);

                      // Badges
                      $('#badgeSubmissions').text(submissionsData.length);
                      $('#badgeAdminShared').text(adminSharedData.length);
                      $('#badgeAllFiles').text(allFilesData.length);

                      renderSubmissionsTable(submissionsData);
                      renderAdminSharedTable(adminSharedData);
                      renderAllFilesTable(allFilesData);
                  }
              },
              error: function() {
                  $('#submissionsTableContainer').html('<div class="alert alert-danger">Error loading files data.</div>');
              }
          });
      }

      function renderSubmissionsTable(data) {
          if (subDataTable) {
              subDataTable.destroy();
          }

          if (data.length === 0) {
              $('#submissionsTableContainer').html('<div class="alert alert-info" style="text-align: center; margin-top: 10px;">No teacher submissions received yet.</div>');
              return;
          }

          let html = '<table id="subTable" class="table table-striped table-bordered" style="width:100%">' +
              '<thead><tr>' +
              '<th>Teacher</th>' +
              '<th>Document Title</th>' +
              '<th>Subject / Category</th>' +
              '<th>Date Submitted</th>' +
              '<th>Size</th>' +
              '<th>Status</th>' +
              '<th style="width: 130px;">Action</th>' +
              '</tr></thead><tbody>';

          data.forEach(item => {
              let statusBadge = '<span class="label label-warning"><i class="fa fa-clock-o"></i> Pending Review</span>';
              if (item.adminStatus === 'reviewed') {
                  statusBadge = '<span class="label label-info"><i class="fa fa-check"></i> Reviewed</span>';
              } else if (item.adminStatus === 'acknowledged') {
                  statusBadge = '<span class="label label-success"><i class="fa fa-check-circle"></i> Acknowledged</span>';
              }

              html += '<tr>';
              html += `<td><strong>${escapeHtml(item.teacherName || 'Teacher')}</strong><br><small class="text-muted">${escapeHtml(item.teacherEmail || '')}</small></td>`;
              html += `<td><strong>${escapeHtml(item.title)}</strong><br><small class="text-muted">${escapeHtml(item.originalName)}</small></td>`;
              html += `<td><span class="label label-default">${escapeHtml(item.subjectName || 'General')}</span> <br><small class="text-muted">${escapeHtml(item.categoryName || '')}</small></td>`;
              html += `<td>${formatDate(item.created_at)}</td>`;
              html += `<td>${formatFileSize(item.fileSize)}</td>`;
              html += `<td>${statusBadge}</td>`;
              html += `<td>
                  <button class="btn btn-default btn-action-hub" onclick="previewFile(${item.fileId})" title="Preview Document"><i class="fa fa-eye"></i></button>
                  <a href="../../download_file.php?fileId=${item.fileId}" class="btn btn-primary btn-action-hub" title="Download Document"><i class="fa fa-download"></i></a>
                  <button class="btn btn-warning btn-action-hub" onclick="openReviewModal(${item.fileId})" title="Review & Feedback"><i class="fa fa-pencil"></i> Review</button>
              </td>`;
              html += '</tr>';
          });

          html += '</tbody></table>';
          $('#submissionsTableContainer').html(html);

          subDataTable = $('#subTable').DataTable({
              "pageLength": 10,
              "order": [[3, "desc"]]
          });
      }

      function renderAdminSharedTable(data) {
          if (adminDataTable) {
              adminDataTable.destroy();
          }

          if (data.length === 0) {
              $('#adminSharedTableContainer').html('<div class="alert alert-info" style="text-align: center; margin-top: 10px;">No files shared by admin yet. Click "Share New Document" to broadcast.</div>');
              return;
          }

          let html = '<table id="adminTable" class="table table-striped table-bordered" style="width:100%">' +
              '<thead><tr>' +
              '<th>Document Title</th>' +
              '<th>Audience</th>' +
              '<th>Category</th>' +
              '<th>Downloads</th>' +
              '<th>Date</th>' +
              '<th>Size</th>' +
              '<th style="width: 100px;">Action</th>' +
              '</tr></thead><tbody>';

          data.forEach(item => {
              let aud = 'All Teachers';
              if (item.uploadedFor === 'specific_teachers') {
                  aud = `<span class="label label-info">${item.recipientStaffCount || 0} Specific Teachers</span>`;
              } else if (item.uploadedFor === 'all') {
                  aud = '<span class="label label-success">All Students</span>';
              } else if (item.uploadedFor === 'specific_class') {
                  aud = `<span class="label label-primary">${escapeHtml(item.targetClassName || 'Class')}</span>`;
              } else {
                  aud = '<span class="label label-info">All Teachers</span>';
              }

              html += '<tr>';
              html += `<td><strong>${escapeHtml(item.title)}</strong><br><small class="text-muted">${escapeHtml(item.originalName)}</small></td>`;
              html += `<td>${aud}</td>`;
              html += `<td>${escapeHtml(item.categoryName || 'Administrative')}</td>`;
              html += `<td><span class="badge">${item.downloadCount || 0}</span></td>`;
              html += `<td>${formatDate(item.created_at)}</td>`;
              html += `<td>${formatFileSize(item.fileSize)}</td>`;
              html += `<td>
                  <button class="btn btn-default btn-action-hub" onclick="previewFile(${item.fileId})" title="Preview"><i class="fa fa-eye"></i></button>
                  <a href="../../download_file.php?fileId=${item.fileId}" class="btn btn-primary btn-action-hub" title="Download"><i class="fa fa-download"></i></a>
                  <button class="btn btn-danger btn-action-hub" onclick="deleteAdminFile(${item.fileId})" title="Delete"><i class="fa fa-trash"></i></button>
              </td>`;
              html += '</tr>';
          });

          html += '</tbody></table>';
          $('#adminSharedTableContainer').html(html);

          adminDataTable = $('#adminTable').DataTable({
              "pageLength": 10,
              "order": [[4, "desc"]]
          });
      }

      function renderAllFilesTable(data) {
          if (allDataTable) {
              allDataTable.destroy();
          }

          if (data.length === 0) {
              $('#allFilesTableContainer').html('<div class="alert alert-info">No files in system.</div>');
              return;
          }

          let html = '<table id="allTable" class="table table-striped table-bordered" style="width:100%">' +
              '<thead><tr>' +
              '<th>Title</th>' +
              '<th>Uploader</th>' +
              '<th>Category / Subject</th>' +
              '<th>Audience</th>' +
              '<th>Date</th>' +
              '<th>Downloads</th>' +
              '<th style="width: 80px;">Action</th>' +
              '</tr></thead><tbody>';

          data.forEach(item => {
              let uploaderBadge = item.uploaderType === 'admin' 
                  ? '<span class="label label-primary"><i class="fa fa-shield"></i> Administration</span>'
                  : `<span class="label label-default">${escapeHtml(item.uploaderName)}</span>`;

              html += '<tr>';
              html += `<td><strong>${escapeHtml(item.title)}</strong><br><small class="text-muted">${escapeHtml(item.originalName)}</small></td>`;
              html += `<td>${uploaderBadge}</td>`;
              html += `<td>${escapeHtml(item.categoryName || '')} <br><small class="text-muted">${escapeHtml(item.subjectName || 'General')}</small></td>`;
              html += `<td>${escapeHtml(item.uploadedFor)}</td>`;
              html += `<td>${formatDate(item.created_at)}</td>`;
              html += `<td>${item.downloadCount || 0}</td>`;
              html += `<td>
                  <button class="btn btn-default btn-action-hub" onclick="previewFile(${item.fileId})" title="Preview"><i class="fa fa-eye"></i></button>
                  <a href="../../download_file.php?fileId=${item.fileId}" class="btn btn-primary btn-action-hub" title="Download"><i class="fa fa-download"></i></a>
              </td>`;
              html += '</tr>';
          });

          html += '</tbody></table>';
          $('#allFilesTableContainer').html(html);

          allDataTable = $('#allTable').DataTable({
              "pageLength": 10,
              "order": [[4, "desc"]]
          });
      }

      function filterSubmissions(status) {
          $('.filter-sub-btn').removeClass('active');
          if (status === 'all') {
              $('.filter-sub-btn:eq(0)').addClass('active');
              renderSubmissionsTable(submissionsData);
          } else if (status === 'pending') {
              $('.filter-sub-btn:eq(1)').addClass('active');
              const filtered = submissionsData.filter(i => i.adminStatus !== 'reviewed' && i.adminStatus !== 'acknowledged');
              renderSubmissionsTable(filtered);
          } else {
              $('.filter-sub-btn:eq(2)').addClass('active');
              const filtered = submissionsData.filter(i => i.adminStatus === 'reviewed' || i.adminStatus === 'acknowledged');
              renderSubmissionsTable(filtered);
          }
      }

      function openShareModal() {
          $('#adminUploadForm')[0].reset();
          $('#adminFilePreview').html('');
          $('#adminTargetTeachersRow').hide();
          $('#adminTargetClassRow').hide();
          $('#adminYoutubeArea').hide();
          $('#adminUploadArea').show();
          $('#shareModal').modal('show');
      }

      function toggleAdminUploadType(type) {
          if (type === 'file') {
              $('#adminUploadArea').show();
              $('#adminYoutubeArea').hide();
              $('#adminFilePreview').show();
          } else {
              $('#adminUploadArea').hide();
              $('#adminYoutubeArea').show();
              $('#adminFilePreview').hide();
          }
      }

      function handleAdminAudienceChange() {
          const val = $('#adminUploadedFor').val();
          if (val === 'specific_teachers') {
              $('#adminTargetTeachersRow').slideDown();
              $('#adminTargetClassRow').slideUp();
          } else if (val === 'specific_class') {
              $('#adminTargetTeachersRow').slideUp();
              $('#adminTargetClassRow').slideDown();
          } else {
              $('#adminTargetTeachersRow').slideUp();
              $('#adminTargetClassRow').slideUp();
          }
      }

      function toggleSelectAllTeachersAdmin() {
          const isChecked = $('#selectAllTeachersAdmin').is(':checked');
          $('.adminTeacherCheckbox').prop('checked', isChecked);
      }

      function filterTeacherSelection() {
          const q = ($('#teacherSearchBox').val() || '').toLowerCase();
          $('.teacher-row-item').each(function() {
              const text = $(this).attr('data-text') || '';
              if (!q || text.includes(q)) {
                  $(this).show();
              } else {
                  $(this).hide();
              }
          });
      }

      function handleAdminFiles(files) {
          const preview = document.getElementById('adminFilePreview');
          preview.innerHTML = '';
          if (!files || files.length === 0) return;

          Array.from(files).forEach(f => {
              const div = document.createElement('div');
              div.style.cssText = 'padding: 8px 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; margin-bottom: 6px; font-size: 13px; display: flex; justify-content: space-between;';
              div.innerHTML = `<span><i class="fa fa-file-text-o" style="color: #16a34a;"></i> <strong>${escapeHtml(f.name)}</strong></span> <span class="text-muted">${formatFileSize(f.size)}</span>`;
              preview.appendChild(div);
          });
      }

      function submitAdminUpload() {
          const formData = new FormData(document.getElementById('adminUploadForm'));
          const uploadType = $('input[name="uploadType"]:checked').val() || 'file';

          if (uploadType === 'file') {
              const files = document.getElementById('adminFileInput').files;
              if (files.length === 0) {
                  alert('Please select at least one file to upload.');
                  return;
              }
          } else {
              if (!$('#adminYoutubeLink').val().trim()) {
                  alert('Please enter a YouTube link.');
                  return;
              }
          }

          if (!$('#adminTitle').val().trim()) {
              alert('Please enter a document title.');
              return;
          }

          const aud = $('#adminUploadedFor').val();
          if (aud === 'specific_teachers') {
              if ($('.adminTeacherCheckbox:checked').length === 0) {
                  alert('Please select at least one teacher.');
                  return;
              }
          }

          $('#adminUploadProgress').show();
          $('#adminUploadProgress .progress-bar').css('width', '50%');

          $.ajax({
              url: 'admin_file_upload_handler.php',
              type: 'POST',
              data: formData,
              processData: false,
              contentType: false,
              dataType: 'json',
              success: function(res) {
                  $('#adminUploadProgress').hide();
                  if (res.success) {
                      $('#shareModal').modal('hide');
                      alert(res.message);
                      loadAdminFiles();
                  } else {
                      alert('Upload failed: ' + res.message);
                  }
              },
              error: function() {
                  $('#adminUploadProgress').hide();
                  alert('Network error occurred during upload.');
              }
          });
      }

      function openReviewModal(fileId) {
          const item = submissionsData.find(i => i.fileId == fileId);
          if (!item) return;

          $('#reviewFileId').val(item.fileId);
          $('#reviewDocTitle').text(item.title);
          $('#reviewTeacherInfo').html(`<strong>Teacher:</strong> ${escapeHtml(item.teacherName)} (${escapeHtml(item.subjectName || 'General')}) &bull; Submitted: ${formatDate(item.created_at)}`);
          $('#reviewStatus').val(item.adminStatus || 'reviewed');
          $('#reviewNotes').val(item.adminNotes || '');
          $('#reviewModal').modal('show');
      }

      function submitReview() {
          const formData = $('#reviewForm').serialize();
          $.ajax({
              url: 'update_file_status.php',
              type: 'POST',
              data: formData,
              dataType: 'json',
              success: function(res) {
                  if (res.success) {
                      $('#reviewModal').modal('hide');
                      alert(res.message);
                      loadAdminFiles();
                  } else {
                      alert('Update failed: ' + res.message);
                  }
              },
              error: function() {
                  alert('Error updating status.');
              }
          });
      }

      function previewFile(fileId) {
          $('#previewModalBody').html('<p class="text-center" style="padding: 40px;"><i class="fa fa-spinner fa-spin fa-2x"></i><br><br>Loading preview...</p>');
          $('#previewModal').modal('show');
          $.ajax({
              url: '../../staffLogin/staffPanel/get_file_preview.php',
              type: 'POST',
              data: { fileId: fileId },
              success: function(html) {
                  $('#previewModalBody').html(html);
              },
              error: function() {
                  $('#previewModalBody').html('<div class="alert alert-danger">Failed to load preview.</div>');
              }
          });
      }

      function deleteAdminFile(fileId) {
          if (!confirm('Are you sure you want to permanently delete this shared document?')) return;
          $.ajax({
              url: 'delete_file.php',
              type: 'POST',
              data: { fileId: fileId },
              dataType: 'json',
              success: function(res) {
                  if (res.success) {
                      loadAdminFiles();
                  } else {
                      alert('Delete failed: ' + res.message);
                  }
              },
              error: function() {
                  alert('Network error while deleting.');
              }
          });
      }

      function escapeHtml(str) {
          if (!str) return '';
          return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
      }

      function formatFileSize(bytes) {
          if (!bytes || bytes === 0) return '0 Bytes';
          const k = 1024;
          const sizes = ['Bytes', 'KB', 'MB', 'GB'];
          const i = Math.floor(Math.log(bytes) / Math.log(k));
          return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
      }

      function formatDate(dateStr) {
          if (!dateStr) return '';
          const d = new Date(dateStr);
          return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
      }
  </script>
</body>
</html>
