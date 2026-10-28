<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

// Ensure all database tables exist
dlhsEnsureAllFileManagementTablesExist($connection);

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    header("location:../");
    exit();
}

$staffId = (int)$_SESSION['staffId'];
$staffName = $_SESSION['staffName'] ?? 'Teacher';

// Get categories for dropdown
$categoriesResult = $connection->query("SELECT * FROM file_categories ORDER BY categoryId ASC");
$categories = [];
if ($categoriesResult && $categoriesResult->num_rows > 0) {
    while ($cat = $categoriesResult->fetch_assoc()) {
        $categories[] = $cat;
    }
}

// Get subjects for dropdown (assigned to this teacher)
$subjectsQuery = "SELECT DISTINCT s.subjectId, s.subjectName, yg.yearGroupId, yg.yearGroupName 
                  FROM subjects s 
                  INNER JOIN subject_teacher_assignment sta ON s.subjectId = sta.subjectId 
                  INNER JOIN classes c ON sta.classId = c.classId 
                  INNER JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId 
                  WHERE sta.teacherId = '$staffId' 
                  ORDER BY s.subjectName ASC, yg.yearGroupName ASC";
$subjectsResult = $connection->query($subjectsQuery);
$assignedSubjects = [];
if ($subjectsResult && $subjectsResult->num_rows > 0) {
    while ($sub = $subjectsResult->fetch_assoc()) {
        $assignedSubjects[] = $sub;
    }
}

// Get classes for targeting (assigned as subject or form teacher)
$classesQuery = "SELECT DISTINCT c.*, y.yearGroupName FROM classes c 
                 LEFT JOIN yeargroup y ON c.classYearGroup = y.yearGroupId 
                 WHERE c.classId IN (
                     SELECT classId FROM subject_teacher_assignment WHERE teacherId = $staffId
                     UNION
                     SELECT classId FROM form_teacher_assignment WHERE teacherId = $staffId
                 )
                 ORDER BY c.classYearGroup, c.className";
$classesResult = $connection->query($classesQuery);
$allClasses = [];
if ($classesResult && $classesResult->num_rows > 0) {
    while ($row = $classesResult->fetch_assoc()) {
        $allClasses[] = $row;
    }
}

$assignmentsQuery = "SELECT DISTINCT sta.subjectId, sta.classId, c.className, y.yearGroupName
                     FROM subject_teacher_assignment sta
                     INNER JOIN classes c ON sta.classId = c.classId
                     LEFT JOIN yeargroup y ON c.classYearGroup = y.yearGroupId
                     WHERE sta.teacherId = $staffId";
$assignmentsResult = $connection->query($assignmentsQuery);
$teacherAssignments = [];
if ($assignmentsResult && $assignmentsResult->num_rows > 0) {
    while ($row = $assignmentsResult->fetch_assoc()) {
        $teacherAssignments[] = $row;
    }
}

// Get teaching colleagues for specific teacher sharing
$colleaguesQuery = "SELECT s.staffId, CONCAT(s.surname, ' ', s.firstName) as fullName, s.username,
                           GROUP_CONCAT(DISTINCT sub.subjectName ORDER BY sub.subjectName SEPARATOR ', ') as subjects
                    FROM stafflogin s
                    LEFT JOIN subject_teacher_assignment sta ON s.staffId = sta.teacherId
                    LEFT JOIN subjects sub ON sta.subjectId = sub.subjectId
                    WHERE s.staffId != $staffId
                    GROUP BY s.staffId, s.surname, s.firstName, s.username
                    ORDER BY s.surname ASC, s.firstName ASC";
$colleaguesResult = $connection->query($colleaguesQuery);
$colleagues = [];
if ($colleaguesResult && $colleaguesResult->num_rows > 0) {
    while ($col = $colleaguesResult->fetch_assoc()) {
        $colleagues[] = $col;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="File Sharing Hub - Deeper Life High School">
    <meta name="author" content="DLHS">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <title>File Sharing Hub | Deeper Life High School</title>

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
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f7f6;
            color: #333;
        }
        .wrapper {
            padding: 25px !important;
        }

        /* Stat Cards */
        .stat-card-row {
            margin-bottom: 25px;
        }
        .stat-card {
            border-radius: 12px;
            padding: 20px 22px;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 174, 239, 0.08);
            margin-bottom: 15px;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 174, 239, 0.15);
        }
        .stat-card i.stat-bg-icon {
            position: absolute;
            right: 15px;
            bottom: -8px;
            font-size: 72px;
            opacity: 0.16;
        }
        .stat-card h3 {
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 4px 0;
        }
        .stat-card p {
            font-size: 13px;
            margin: 0;
            opacity: 0.92;
            font-weight: 500;
        }
        .stat-card-blue   { background: linear-gradient(135deg, #020b1a 0%, #1e3c72 100%); }
        .stat-card-cyan   { background: linear-gradient(135deg, #00AEEF 0%, #0077b6 100%); }
        .stat-card-orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-card-emerald{ background: linear-gradient(135deg, #0acca2 0%, #059669 100%); }

        /* Premium Cards */
        .premium-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 174, 239, 0.06);
            border: 1px solid rgba(0, 174, 239, 0.15);
            margin-bottom: 25px;
            overflow: hidden;
        }
        .premium-card-header {
            background: linear-gradient(135deg, #00AEEF 0%, #0077b6 100%);
            color: white;
            padding: 16px 22px;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .premium-card-body {
            padding: 22px;
        }
        .upload-area {
            border: 2px dashed #00AEEF;
            border-radius: 8px;
            padding: 30px 18px;
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
        .upload-area i {
            font-size: 38px;
            color: #00AEEF;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            height: auto;
            font-size: 13px;
        }
        .form-control:focus {
            border-color: #00AEEF;
            box-shadow: 0 0 0 3px rgba(0, 174, 239, 0.15);
        }
        .btn-premium {
            background: linear-gradient(135deg, #00AEEF 0%, #0077b6 100%);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 14px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 174, 239, 0.25);
        }
        .btn-premium:hover {
            color: white;
            box-shadow: 0 6px 16px rgba(0, 174, 239, 0.35);
            transform: translateY(-1px);
        }

        /* Tabs styling */
        .nav-tabs-custom {
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 18px;
            display: flex;
            gap: 8px;
        }
        .nav-tabs-custom li a {
            border: none !important;
            padding: 11px 18px;
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

        /* Filter Pills */
        .filter-pill {
            border: 1px solid #cbd5e1;
            background: white;
            color: #475569;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            margin-right: 4px;
            margin-bottom: 6px;
            display: inline-block;
        }
        .filter-pill.active, .filter-pill:hover {
            background: #00AEEF;
            color: white;
            border-color: #00AEEF;
        }

        /* Checklist Panels */
        .checklist-panel {
            max-height: 160px;
            overflow-y: auto;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 10px;
            background: #f8fafc;
        }
        .checklist-item {
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .checklist-item:hover {
            background: #e2e8f0;
        }

        .btn-action-sm {
            border-radius: 5px;
            padding: 5px 10px;
            font-size: 12px;
            margin-right: 3px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* File Cards for Grid View */
        .file-card-box {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }
        .file-card-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 174, 239, 0.09);
            border-color: #00AEEF;
        }
        .file-card-icon {
            font-size: 32px;
            color: #00AEEF;
            margin-right: 12px;
        }

        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.85);
            z-index: 99999;
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .spinner {
            width: 45px;
            height: 45px;
            border: 4px solid rgba(0, 174, 239, 0.2);
            border-top: 4px solid #00AEEF;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .view-btn {
            background: white;
            border: 1px solid #cbd5e1;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
        }
        .view-btn.active, .view-btn:hover {
            background: #00AEEF;
            color: white;
            border-color: #00AEEF;
        }
    </style>
</head>
<body>
  <div class="loading-overlay" id="loadingOverlay">
      <div class="spinner"></div>
      <p style="margin-top: 15px; font-weight: 600; color: #4f6e63;" id="progressText">Processing...</p>
  </div>

  <section id="container">
      <?php include 'header.php'; ?>
      <?php include 'sideBar.php'; ?>
      <?php session_write_close(); ?>

      <section id="main-content">
          <section class="wrapper">            
              <div class="row">
                  <div class="col-lg-12">
                      <h3 class="page-header" style="font-weight: 700; color: #2e3e38; margin: 0 0 5px 0;">
                          <i class="fa fa-share-alt"></i> File Sharing Hub
                      </h3>
                      <ol class="breadcrumb" style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); margin-bottom: 20px;">
                          <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                          <li><i class="fa fa-share-alt"></i>Share Files</li>
                      </ol>
                  </div>
              </div>

              <!-- Top Statistics Dashboard -->
              <div class="row stat-card-row">
                  <div class="col-md-3 col-sm-6">
                      <div class="stat-card stat-card-blue">
                          <i class="fa fa-inbox stat-bg-icon"></i>
                          <h3 id="statReceivedCount">0</h3>
                          <p id="statReceivedSub">Received from Admin & Staff</p>
                      </div>
                  </div>
                  <div class="col-md-3 col-sm-6">
                      <div class="stat-card stat-card-green">
                          <i class="fa fa-paper-plane stat-bg-icon"></i>
                          <h3 id="statSentCount">0</h3>
                          <p>Documents Shared by Me</p>
                      </div>
                  </div>
                  <div class="col-md-3 col-sm-6">
                      <div class="stat-card stat-card-orange">
                          <i class="fa fa-shield stat-bg-icon"></i>
                          <h3 id="statAdminSubmissions">0</h3>
                          <p id="statAdminSubmissionsSub">Submissions to Admin</p>
                      </div>
                  </div>
                  <div class="col-md-3 col-sm-6">
                      <div class="stat-card stat-card-purple">
                          <i class="fa fa-download stat-bg-icon"></i>
                          <h3 id="statDownloads">0</h3>
                          <p>Total Download Engagement</p>
                      </div>
                  </div>
              </div>

              <div class="row">
                  <!-- Left Column: Upload & Share Form -->
                  <div class="col-md-5">
                      <div class="premium-card">
                          <div class="premium-card-header">
                              <span><i class="fa fa-upload"></i> Share New File / Document</span>
                          </div>
                          <div class="premium-card-body">
                              <form id="uploadForm" enctype="multipart/form-data">
                                  <div class="mb-3 d-flex justify-content-center" style="gap: 15px; margin-bottom: 15px; display: flex;">
                                      <label style="font-weight: 600; cursor: pointer;">
                                          <input type="radio" id="uploadTypeFile" name="uploadType" value="file" checked onchange="toggleUploadType('file')"> Upload File
                                      </label>
                                      <label style="font-weight: 600; cursor: pointer; margin-left: 15px;">
                                          <input type="radio" id="uploadTypeLink" name="uploadType" value="link" onchange="toggleUploadType('link')"> YouTube Link
                                      </label>
                                  </div>

                                  <!-- File Dropzone -->
                                  <div class="upload-area" id="uploadArea">
                                      <i class="fa fa-cloud-upload"></i>
                                      <h5 style="margin: 5px 0 2px 0; font-weight: 600;">Drag & Drop File Here</h5>
                                      <p style="color: #888; font-size: 12px; margin: 0;">or click to browse files (PDF, Word, Excel, PPT, Zip, Images, Audio, Video. Max: 100MB)</p>
                                      <input type="file" id="fileInput" name="files[]" style="display: none;" multiple accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt,.rtf,.odt,.ods,.odp,.zip,.rar,.7z,.tar,.gz,.jpg,.jpeg,.png,.gif,.webp,.svg,.mp3,.wav,.m4a,.aac,.ogg,.mp4,.webm,.mkv,.mov,.avi">
                                  </div>
                                  
                                  <!-- YouTube Link Input -->
                                  <div id="youtubeLinkArea" style="display: none; margin-bottom: 15px;">
                                      <label for="youtubeLink" style="font-weight: 600; font-size: 13px;">YouTube URL *</label>
                                      <div class="input-group">
                                          <span class="input-group-addon" style="background: rgba(255, 0, 0, 0.1); color: red; border-color: #ccc;"><i class="fa fa-youtube-play"></i></span>
                                          <input type="url" class="form-control" id="youtubeLink" name="youtubeLink" placeholder="https://www.youtube.com/watch?v=...">
                                      </div>
                                      <div id="youtubePreviewBox" style="display: none; margin-top: 10px; border-radius: 8px; border: 1px solid #e2e8f0; padding: 10px; background: #f8fafc;">
                                          <div style="display: flex; gap: 12px; align-items: center;">
                                              <img id="ytThumbImg" src="" alt="Thumbnail" style="width: 110px; height: 62px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;">
                                              <div>
                                                  <span class="label label-danger"><i class="fa fa-youtube-play"></i> YouTube Video</span>
                                                  <p style="margin: 4px 0 0; font-size: 12px; color: #334155; font-weight: 600;" id="ytVideoIdText"></p>
                                                  <small style="color: #10b981;"><i class="fa fa-check-circle"></i> Valid YouTube URL recognized</small>
                                              </div>
                                          </div>
                                      </div>
                                  </div>
                                  
                                  <!-- Upload File Preview Area -->
                                  <div id="filePreview" class="mb-3"></div>

                                  <!-- Title -->
                                  <div class="form-group">
                                      <label for="title" style="font-weight: 600; font-size: 13px;">Document Title *</label>
                                      <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Term 1 Scheme of Work - Week 1 to 6" required>
                                  </div>

                                  <!-- Category -->
                                  <div class="form-group">
                                      <label for="categoryId" style="font-weight: 600; font-size: 13px;">Category *</label>
                                      <select class="form-control" id="categoryId" name="categoryId" required>
                                          <?php foreach ($categories as $cat): ?>
                                              <option value="<?php echo $cat['categoryId']; ?>" <?php echo $cat['categoryId'] == 8 ? 'selected' : ''; ?>>
                                                  <?php echo htmlspecialchars($cat['categoryName']); ?>
                                              </option>
                                          <?php endforeach; ?>
                                      </select>
                                  </div>

                                  <!-- Recipient Target Audience -->
                                  <div class="form-group">
                                      <label for="uploadedFor" style="font-weight: 600; font-size: 13px;">Share With *</label>
                                      <select class="form-control" id="uploadedFor" name="uploadedFor" onchange="handleAudienceChange()" required>
                                          <optgroup label="── Colleagues & Administration ──">
                                              <option value="admin">🏛️ School Administration (Admin)</option>
                                              <option value="all_teachers">👨‍🏫 All Teachers (Staff Broadcast)</option>
                                              <option value="subject_teachers">📚 Teachers of My Subject (Department)</option>
                                              <option value="specific_teachers">👤 Specific Teacher(s)</option>
                                          </optgroup>
                                          <optgroup label="── Students ──">
                                              <option value="all">👥 All Students</option>
                                              <option value="specific_class">🏫 Specific Class Arm</option>
                                              <option value="multiple_classes">🏫 Multiple Class Arms</option>
                                              <option value="specific_students">🎯 Specific Students</option>
                                          </optgroup>
                                      </select>
                                  </div>

                                  <!-- Admin Notice Box -->
                                  <div id="adminNoticePanel" class="alert alert-info" style="display: none; font-size: 13px; border-radius: 6px; padding: 10px 14px; margin-bottom: 15px;">
                                      <strong><i class="fa fa-shield"></i> Submission to Administration:</strong><br>
                                      This document will be delivered directly to School Administration for official review (e.g., Lesson Plan, Termly Scheme, Question Draft, or Official Report).
                                  </div>

                                  <!-- All Teachers Notice Box -->
                                  <div id="allTeachersNoticePanel" class="alert alert-info" style="display: none; font-size: 13px; border-radius: 6px; padding: 10px 14px; margin-bottom: 15px;">
                                      <strong><i class="fa fa-users"></i> Staff Broadcast:</strong><br>
                                      All teaching staff across the school will have access to view and download this resource.
                                  </div>

                                  <!-- Target Specific Teachers (Checklist) -->
                                  <div class="form-group" id="teachersSelectionRow" style="display: none;">
                                      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                          <label style="font-weight: 600; font-size: 13px; margin: 0;">Select Target Teachers *</label>
                                          <input type="text" id="teacherSearchStaff" placeholder="Filter colleagues..." class="form-control input-sm" style="width: 170px;" oninput="filterColleaguesChecklist()">
                                      </div>
                                      <div class="checklist-panel" id="colleaguesChecklist">
                                          <div style="margin-bottom: 6px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">
                                              <label style="font-weight: 700; cursor: pointer; font-size: 12px; margin: 0;">
                                                  <input type="checkbox" id="selectAllColleagues" onchange="toggleSelectAllColleagues()"> Select All Colleagues
                                              </label>
                                          </div>
                                          <?php foreach ($colleagues as $c): ?>
                                              <div class="checklist-item colleague-item" data-text="<?php echo strtolower(htmlspecialchars($c['fullName'] . ' ' . $c['subjects'])); ?>">
                                                  <label style="font-weight: normal; cursor: pointer; display: flex; align-items: center; gap: 8px; margin: 0;">
                                                      <input type="checkbox" name="selectedTeachers[]" value="<?php echo $c['staffId']; ?>" class="colleagueCheckbox">
                                                      <span>
                                                          <strong><?php echo htmlspecialchars($c['fullName']); ?></strong> 
                                                          <small class="text-muted">(<?php echo htmlspecialchars($c['subjects'] ?: 'General'); ?>)</small>
                                                      </span>
                                                  </label>
                                              </div>
                                          <?php endforeach; ?>
                                      </div>
                                  </div>

                                  <!-- Subject Selection -->
                                  <div class="form-group" id="subjectSelectionRow">
                                      <label for="subject_composite" style="font-weight: 600; font-size: 13px;">Subject <span id="subjectRequiredStar">*</span></label>
                                      <select class="form-control" id="subject_composite" name="subject_composite">
                                          <option value="">-- Select Subject (or General) --</option>
                                          <?php foreach ($assignedSubjects as $row): ?>
                                              <option value="<?php echo $row['subjectId'] . '|' . $row['yearGroupId']; ?>">
                                                  <?php echo htmlspecialchars($row['subjectName'] . ' ' . $row['yearGroupName']); ?>
                                              </option>
                                          <?php endforeach; ?>
                                      </select>
                                      <input type="hidden" name="subject" id="subject">
                                  </div>

                                  <!-- Target Class (Student Option) -->
                                  <div class="form-group" id="targetClassRow" style="display: none;">
                                      <label for="targetClass" style="font-weight: 600; font-size: 13px;">Select Class *</label>
                                      <select class="form-control" id="targetClass" name="targetClass">
                                          <option value="">-- Select Class --</option>
                                      </select>
                                  </div>

                                  <!-- Target Multiple Classes (Student Option) -->
                                  <div class="form-group" id="multiClassRow" style="display: none;">
                                      <label style="font-weight: 600; font-size: 13px;">Select Target Classes *</label>
                                      <div class="checklist-panel" id="multiClassesList" style="max-height: 140px;">
                                          <p class="text-muted" style="font-size: 12px; margin: 0;">Please select a subject first to load classes.</p>
                                      </div>
                                  </div>

                                  <!-- Target Students (Student Option) -->
                                  <div class="form-group" id="studentsSelectionRow" style="display: none;">
                                      <label style="font-weight: 600; font-size: 13px;">Select Target Students *</label>
                                      <div class="checklist-panel" id="studentsList" style="max-height: 160px;">
                                          <p class="text-muted" style="font-size: 12px; margin: 0;">Please select a class first to load students.</p>
                                      </div>
                                  </div>

                                  <!-- Description -->
                                  <div class="form-group">
                                      <label for="description" style="font-weight: 600; font-size: 13px;">Description / Notes (Optional)</label>
                                      <textarea class="form-control" id="description" name="description" rows="3" placeholder="Add notes, summary, or submission details..."></textarea>
                                  </div>

                                  <!-- Submit Button -->
                                  <button type="submit" class="btn-premium" id="submitShareBtn" style="margin-top: 15px;">
                                      <i class="fa fa-paper-plane"></i> Share Document
                                  </button>
                              </form>

                              <!-- Progress -->
                              <div class="progress" id="uploadProgress" style="display: none; height: 10px; margin-top: 15px; border-radius: 4px; overflow: hidden;">
                                  <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%; background-color: #00AEEF;"></div>
                              </div>
                          </div>
                      </div>
                  </div>

                  <!-- Right Column: Tabs (Received vs Sent) -->
                  <div class="col-md-7">
                      <div class="premium-card">
                          <div class="premium-card-header">
                              <span><i class="fa fa-folder-open"></i> Document Hub Directory</span>
                              <div>
                                  <button class="view-btn active" id="tableViewBtn" onclick="switchView('table')"><i class="fa fa-table"></i> Table</button>
                                  <button class="view-btn" id="gridViewBtn" onclick="switchView('grid')"><i class="fa fa-th"></i> Grid</button>
                                  <button class="view-btn" onclick="loadMyFiles()" title="Refresh"><i class="fa fa-refresh"></i></button>
                              </div>
                          </div>
                          <div class="premium-card-body">
                              <!-- Tabs Navigation -->
                              <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                                  <li role="presentation" class="active">
                                      <a href="#tabReceived" aria-controls="tabReceived" role="tab" data-toggle="tab">
                                          <i class="fa fa-inbox"></i> Files Shared With Me 
                                          <span class="badge" id="badgeReceivedTab" style="background-color: #1e3c72; margin-left: 5px;">0</span>
                                      </a>
                                  </li>
                                  <li role="presentation">
                                      <a href="#tabSent" aria-controls="tabSent" role="tab" data-toggle="tab">
                                          <i class="fa fa-paper-plane"></i> Files I've Shared 
                                          <span class="badge" id="badgeSentTab" style="background-color: #00AEEF; margin-left: 5px;">0</span>
                                      </a>
                                  </li>
                              </ul>

                              <div class="tab-content">
                                  <!-- Tab 1: Received Files -->
                                  <div role="tabpanel" class="tab-pane active" id="tabReceived">
                                      <!-- Filter Bar -->
                                      <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; align-items: center;">
                                          <div style="flex: 1; min-width: 200px;">
                                              <input type="text" class="form-control" id="searchReceivedFiles" placeholder="Search received documents..." oninput="filterReceivedFiles()">
                                          </div>
                                          <div>
                                              <span class="filter-pill active" onclick="setReceivedFilter('all', this)">All</span>
                                              <span class="filter-pill" onclick="setReceivedFilter('admin', this)"><i class="fa fa-shield"></i> From Admin</span>
                                              <span class="filter-pill" onclick="setReceivedFilter('teachers', this)"><i class="fa fa-user"></i> From Colleagues</span>
                                              <span class="filter-pill" onclick="setReceivedFilter('students', this)"><i class="fa fa-graduation-cap"></i> From Students</span>
                                              <span class="filter-pill" onclick="setReceivedFilter('subject', this)">Departmental</span>
                                          </div>
                                      </div>

                                      <div id="receivedFilesContainer">
                                          <p class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading received documents...</p>
                                      </div>
                                  </div>

                                  <!-- Tab 2: Sent Files -->
                                  <div role="tabpanel" class="tab-pane" id="tabSent">
                                      <!-- Filter Bar -->
                                      <div style="display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; align-items: center;">
                                          <div style="flex: 1; min-width: 200px;">
                                              <input type="text" class="form-control" id="searchSentFiles" placeholder="Search my uploaded files..." oninput="filterSentFiles()">
                                          </div>
                                          <div>
                                              <span class="filter-pill active" onclick="setSentFilter('all', this)">All</span>
                                              <span class="filter-pill" onclick="setSentFilter('admin', this)"><i class="fa fa-shield"></i> To Admin</span>
                                              <span class="filter-pill" onclick="setSentFilter('teachers', this)">To Teachers</span>
                                              <span class="filter-pill" onclick="setSentFilter('students', this)">To Students</span>
                                          </div>
                                      </div>

                                      <div id="sentFilesContainer">
                                          <p class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading your shared files...</p>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </section>
      </section>
  </section>

  <!-- Modal: File Content Preview -->
  <div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
      <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
              <div class="modal-header" style="background: #2e3e38; color: white;">
                  <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.8;">&times;</button>
                  <h4 class="modal-title" style="font-weight: 600;"><i class="fa fa-eye"></i> Document Preview</h4>
              </div>
              <div class="modal-body" id="previewModalBody" style="padding: 20px; max-height: 80vh; overflow-y: auto;">
                  <p class="text-center" style="padding: 40px;"><i class="fa fa-spinner fa-spin fa-2x"></i><br>Loading preview...</p>
              </div>
          </div>
      </div>
  </div>

  <!-- Modal: Admin Feedback & Review Details -->
  <div class="modal fade" id="adminFeedbackModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
          <div class="modal-content" style="border-radius: 12px; overflow: hidden;">
              <div class="modal-header" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
                  <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 0.8;">&times;</button>
                  <h4 class="modal-title" style="font-weight: 700;"><i class="fa fa-shield"></i> Administration Review & Feedback</h4>
              </div>
              <div class="modal-body" style="padding: 20px;">
                  <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                      <h4 id="fbDocTitle" style="margin: 0 0 6px 0; font-weight: 700; color: #1e293b;"></h4>
                      <p id="fbDocMeta" style="margin: 0; color: #64748b; font-size: 13px;"></p>
                  </div>
                  <div class="form-group">
                      <label style="font-weight: 600; font-size: 13px;">Review Status:</label>
                      <div id="fbStatusBadge"></div>
                  </div>
                  <div class="form-group">
                      <label style="font-weight: 600; font-size: 13px;">Administration Notes / Instructions:</label>
                      <div id="fbNotesBox" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 14px; font-size: 14px; color: #1e3a8a; white-space: pre-line;"></div>
                  </div>
                  <p id="fbReviewedDate" style="color: #94a3b8; font-size: 12px; margin: 0;"></p>
              </div>
              <div class="modal-footer" style="background: #f8fafc;">
                  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
              </div>
          </div>
      </div>
  </div>

  <!-- Javascripts -->
  <script src="js/jquery.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/jquery.scrollTo.min.js"></script>
  <script src="js/jquery.nicescroll.js" type="text/javascript"></script>
  
  <!-- DataTables -->
  <script type="text/javascript" src="../../datatables/js/jquery.dataTables.min.js"></script>
  <script type="text/javascript" src="../../datatables/js/responsive.dataTables.min.js"></script>

  <!-- Custom Scripts -->
  <script src="js/custom.js"></script>
  <script>
      const allAssignedClasses = <?php echo json_encode($allClasses); ?>;
      const teacherAssignments = <?php echo json_encode($teacherAssignments); ?>;
  </script>
  <script src="js/premium_modal.js?v=<?php echo filemtime(__DIR__ . '/js/premium_modal.js'); ?>"></script>
  <script src="file_management.js?v=<?php echo filemtime(__DIR__ . '/file_management.js'); ?>"></script>
</body>
</html>
