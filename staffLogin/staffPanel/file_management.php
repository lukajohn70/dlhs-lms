<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    header("location:../");
    exit();
}

$staffId = $_SESSION['staffId'];
$staffName = $_SESSION['staffName'];

// Get subjects for dropdown (only those assigned to the teacher, linked with year groups)
$subjectsQuery = "SELECT DISTINCT s.subjectId, s.subjectName, yg.yearGroupId, yg.yearGroupName 
                  FROM subjects s 
                  INNER JOIN subject_teacher_assignment sta ON s.subjectId = sta.subjectId 
                  INNER JOIN classes c ON sta.classId = c.classId 
                  INNER JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId 
                  WHERE sta.teacherId = '$staffId' 
                  ORDER BY s.subjectName ASC, yg.yearGroupName ASC";
$subjectsResult = $connection->query($subjectsQuery);

// Get classes for targeting (only those assigned to the teacher as subject or form teacher)
$classesQuery = "SELECT DISTINCT c.*, y.yearGroupName FROM classes c 
                 LEFT JOIN yeargroup y ON c.classYearGroup = y.yearGroupId 
                 WHERE c.classId IN (
                     SELECT classId FROM subject_teacher_assignment WHERE teacherId = $staffId
                     UNION
                     SELECT classId FROM form_teacher_assignment WHERE teacherId = $staffId
                 )
                 ORDER BY c.classYearGroup, c.className";
$classesResult = $connection->query($classesQuery);

// Expose assignments and classes to JavaScript for dynamic filtering
$allClasses = [];
if ($classesResult && $classesResult->num_rows > 0) {
    while ($row = $classesResult->fetch_assoc()) {
        $allClasses[] = $row;
    }
    $classesResult->data_seek(0);
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
    <!-- bootstrap theme -->
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <!--external css-->
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />    
    <!-- Custom styles -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
    <link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f7f6;
            color: #333;
        }
        .wrapper {
            padding: 25px !important;
        }
        .premium-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(104, 138, 126, 0.15);
            margin-bottom: 25px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .premium-card-header {
            background: linear-gradient(135deg, #688a7e 0%, #4f6e63 100%);
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
        .upload-area {
            border: 2px dashed #688a7e;
            border-radius: 8px;
            padding: 35px 20px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(104, 138, 126, 0.03);
        }
        .upload-area:hover, .upload-area.dragover {
            background: rgba(104, 138, 126, 0.08);
            border-color: #4f6e63;
        }
        .upload-area i {
            font-size: 40px;
            color: #688a7e;
            margin-bottom: 10px;
            transition: transform 0.3s ease;
        }
        .upload-area:hover i {
            transform: translateY(-5px);
        }
        .form-control {
            border-radius: 6px;
            border: 1px solid #ccc;
            padding: 8px 12px;
            height: auto;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #688a7e;
            box-shadow: 0 0 0 3px rgba(104, 138, 126, 0.15);
        }
        .btn-premium {
            background: linear-gradient(135deg, #688a7e 0%, #5a7569 100%);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 14px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(104, 138, 126, 0.2);
        }
        .btn-premium:hover, .btn-premium:focus {
            background: linear-gradient(135deg, #5a7569 0%, #4a5f55 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(104, 138, 126, 0.3);
        }
        .file-card {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            padding: 18px;
            background: #fff;
            margin-bottom: 15px;
            position: relative;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        .file-card:hover {
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.05);
            transform: translateY(-2px);
            border-color: rgba(104, 138, 126, 0.3);
        }
        .file-icon-wrapper {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            background: rgba(104, 138, 126, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .file-icon-wrapper i {
            font-size: 24px;
            color: #688a7e;
        }
        .file-info {
            flex-grow: 1;
        }
        .file-title {
            font-weight: 600;
            font-size: 15px;
            margin: 0 0 4px 0;
            color: #333;
        }
        .file-meta {
            font-size: 12px;
            color: #777;
            margin: 0;
        }
        .file-actions {
            margin-left: 15px;
            flex-shrink: 0;
        }
        .btn-delete-file {
            background: rgba(217, 83, 79, 0.1);
            color: #d9534f;
            border: none;
            border-radius: 6px;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .btn-delete-file:hover {
            background: #d9534f;
            color: white;
            transform: scale(1.05);
        }
        .students-list-panel {
            border: 1px solid #ddd;
            border-radius: 6px;
            max-height: 200px;
            overflow-y: auto;
            padding: 10px;
            background: #fafafa;
        }
        .checkbox-item {
            margin: 5px 0;
        }
        .checkbox-item label {
            font-weight: normal;
            cursor: pointer;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.85);
            display: none;
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .loading-overlay.active {
            display: flex;
        }
        .spinner {
            border: 4px solid rgba(104, 138, 126, 0.15);
            border-top: 4px solid #688a7e;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .view-btn {
            background: white;
            border: 1px solid #ccc;
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
            transition: all 0.2s;
        }
        .view-btn.active, .view-btn:hover {
            background: #688a7e;
            color: white;
            border-color: #688a7e;
        }
    </style>
</head>
<body>
  <!-- Loading Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
      <div class="spinner"></div>
      <p style="margin-top: 15px; font-weight: 500; color: #4f6e63;" id="progressText">Processing...</p>
  </div>

  <section id="container" class="">
	<!--Header-->
	<?php include 'header.php'; ?>

    <!--Sidebar-->
	<?php include 'sideBar.php'; ?>
	       
      <section id="main-content">
          <section class="wrapper">            
			  <div class="row">
				<div class="col-lg-12">
					<h3 class="page-header" style="font-weight: 700; color: #2e3e38;"><i class="fa fa-share-alt"></i> File Sharing Hub</h3>
					<ol class="breadcrumb" style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-share-alt"></i>Share Files</li>
					</ol>
				</div>
			  </div>

              <div class="row">
                  <!-- Left Column: Upload & Share Form -->
                  <div class="col-md-5">
                      <div class="premium-card">
                          <div class="premium-card-header">
                              <span><i class="fa fa-upload"></i> Share New File</span>
                          </div>
                          <div class="premium-card-body">
                              <form id="uploadForm" enctype="multipart/form-data">
                                  <div class="mb-3 d-flex justify-content-center" style="gap: 15px; margin-bottom: 15px;">
                                      <div class="custom-control custom-radio custom-control-inline">
                                          <input type="radio" id="uploadTypeFile" name="uploadType" class="custom-control-input" value="file" checked onchange="toggleUploadType('file')">
                                          <label class="custom-control-label" for="uploadTypeFile" style="font-weight: 600; cursor: pointer;">Upload File</label>
                                      </div>
                                      <div class="custom-control custom-radio custom-control-inline">
                                          <input type="radio" id="uploadTypeLink" name="uploadType" class="custom-control-input" value="link" onchange="toggleUploadType('link')">
                                          <label class="custom-control-label" for="uploadTypeLink" style="font-weight: 600; cursor: pointer;">YouTube Link</label>
                                      </div>
                                  </div>

                                  <!-- File Dropzone -->
                                  <div class="upload-area" id="uploadArea">
                                      <i class="fa fa-cloud-upload"></i>
                                      <h5 style="margin: 5px 0 2px 0; font-weight: 600;">Drag & Drop File Here</h5>
                                      <p style="color: #888; font-size: 12px; margin: 0;">or click to browse files</p>
                                      <input type="file" id="fileInput" name="files[]" style="display: none;" multiple>
                                  </div>
                                  
                                  <!-- YouTube Link Input -->
                                  <div id="youtubeLinkArea" style="display: none; margin-bottom: 15px;">
                                      <label for="youtubeLink" style="font-weight: 600; font-size: 13px;">YouTube URL *</label>
                                      <div class="input-group">
                                          <span class="input-group-addon" style="background: rgba(255, 0, 0, 0.1); color: red; border-color: #ccc;"><i class="fa fa-youtube-play"></i></span>
                                          <input type="url" class="form-control" id="youtubeLink" name="youtubeLink" placeholder="https://www.youtube.com/watch?v=...">
                                      </div>
                                  </div>
                                  
                                  <!-- Upload File Preview Area -->
                                  <div id="filePreview" class="mb-3"></div>

                                  <!-- Title -->
                                  <div class="form-group">
                                      <label for="title" style="font-weight: 600; font-size: 13px;">File Title *</label>
                                      <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Week 1 Slides - Robotics Introduction" required>
                                  </div>

                                  <!-- Description -->
                                  <div class="form-group">
                                      <label for="description" style="font-weight: 600; font-size: 13px;">Description (Optional)</label>
                                      <textarea class="form-control" id="description" name="description" rows="3" placeholder="Add some notes about the file..."></textarea>
                                  </div>

                                  <!-- Subject Selection -->
                                  <div class="form-group">
                                      <label for="subject_composite" style="font-weight: 600; font-size: 13px;">Subject *</label>
                                      <select class="form-control" id="subject_composite" name="subject_composite" required>
                                          <option value="">-- Select Subject --</option>
                                          <?php while($row = $subjectsResult->fetch_assoc()): ?>
                                              <option value="<?php echo $row['subjectId'] . '|' . $row['yearGroupId']; ?>"><?php echo htmlspecialchars($row['subjectName'] . ' ' . $row['yearGroupName']); ?></option>
                                          <?php endwhile; ?>
                                      </select>
                                      <input type="hidden" name="subject" id="subject">
                                  </div>

                                  <!-- Target Audience Dropdown -->
                                  <div class="form-group">
                                      <label for="uploadedFor" style="font-weight: 600; font-size: 13px;">Share With *</label>
                                      <select class="form-control" id="uploadedFor" name="uploadedFor" required>
                                          <option value="all">All Students</option>
                                          <option value="specific_class">Specific Class</option>
                                          <option value="specific_students">Specific Students</option>
                                      </select>
                                  </div>

                                  <!-- Target Class (Hidden by default) -->
                                  <div class="form-group" id="targetClassRow" style="display: none;">
                                      <label for="targetClass" style="font-weight: 600; font-size: 13px;">Select Class *</label>
                                      <select class="form-control" id="targetClass" name="targetClass">
                                          <option value="">-- Select Class --</option>
                                      </select>
                                  </div>

                                  <!-- Target Students (Hidden by default) -->
                                  <div class="form-group" id="studentsSelectionRow" style="display: none;">
                                      <label style="font-weight: 600; font-size: 13px;">Select Target Students *</label>
                                      <div class="students-list-panel" id="studentsList">
                                          <p class="text-muted" style="font-size: 12px; margin: 0;">Please select a class first to load students.</p>
                                      </div>
                                  </div>

                                  <!-- Upload Button -->
                                  <button type="submit" class="btn-premium" style="margin-top: 15px;">
                                      <i class="fa fa-paper-plane"></i> Share File with Students
                                  </button>
                              </form>

                              <!-- Upload Progress -->
                              <div class="progress" id="uploadProgress" style="display: none; height: 10px; margin-top: 15px; border-radius: 4px; overflow: hidden;">
                                  <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%; background-color: #688a7e;"></div>
                              </div>
                          </div>
                      </div>
                  </div>

                  <!-- Right Column: List of Shared Files -->
                  <div class="col-md-7">
                      <div class="premium-card">
                          <div class="premium-card-header">
                              <span><i class="fa fa-folder-open"></i> My Shared Files</span>
                              <div>
                                  <button class="view-btn active" id="tableViewBtn" onclick="switchView('table')"><i class="fa fa-table"></i> Table</button>
                                  <button class="view-btn" id="gridViewBtn" onclick="switchView('grid')"><i class="fa fa-th"></i> Grid</button>
                                  <button class="view-btn" onclick="loadMyFiles()"><i class="fa fa-refresh"></i></button>
                              </div>
                          </div>
                          <div class="premium-card-body">
                              <!-- Search Area -->
                              <div style="margin-bottom: 20px;">
                                  <input type="text" class="form-control" id="searchFiles" placeholder="Search shared files..." oninput="searchFiles()">
                              </div>

                              <div id="filesList">
                                  <!-- Files loaded dynamically via AJAX -->
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </section>
      </section>
  </section>

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
  <script src="js/premium_modal.js"></script>
  <script src="file_management.js"></script>
</body>
</html>
