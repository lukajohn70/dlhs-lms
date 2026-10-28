<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_assignment_helper.php";

// Ensure all DB tables exist
dlhsEnsureAllFileManagementTablesExist($connection);

// Check if user is logged in
if (!isset($_SESSION['studentLoggedIn']) || $_SESSION['studentLoggedIn'] !== "yes") {
    header("location:../");
    exit();
}

$studentId = (int)$_SESSION['studentId'];
$studentName = $_SESSION['studentName'] ?? 'Student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Study Documents & Teacher Submissions - Deeper Life High School">
    <meta name="author" content="DLHS">
    <link rel="icon" type="image/jpg" href="../images/dlhslogo3.jpg">
    <title>Document Hub | Deeper Life High School</title>

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />    
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />

    <style>
        :root {
            --dlhs-primary: #00AEEF;
            --dlhs-secondary: #0077b6;
            --dlhs-navy: #020b1a;
            --dlhs-ink: #10233a;
            --dlhs-light: #f8fafc;
            --dlhs-border: rgba(0, 174, 239, 0.18);
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f7f6;
            color: #334155;
        }
        .wrapper {
            padding: 25px !important;
        }
        .dlhs-banner {
            background: linear-gradient(135deg, #020b1a 0%, #0077b6 60%, #00AEEF 100%);
            border-radius: 12px;
            color: white;
            padding: 24px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0, 174, 239, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .dlhs-banner h3 {
            margin: 0 0 6px 0;
            font-weight: 700;
            font-size: 22px;
            color: #ffffff;
        }
        .dlhs-banner p {
            margin: 0;
            opacity: 0.9;
            font-size: 13px;
        }
        .stat-card-box {
            background: #ffffff;
            border-radius: 10px;
            padding: 16px 20px;
            border: 1px solid var(--dlhs-border);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            transition: all 0.2s ease;
        }
        .stat-card-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 174, 239, 0.1);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .stat-icon.primary { background: rgba(0, 174, 239, 0.12); color: #00AEEF; }
        .stat-icon.success { background: rgba(16, 185, 129, 0.12); color: #10b981; }
        .stat-icon.warning { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }

        .premium-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 174, 239, 0.05);
            border: 1px solid var(--dlhs-border);
            margin-bottom: 25px;
            overflow: hidden;
        }
        .premium-card-header {
            background: linear-gradient(135deg, #020b1a 0%, #0077b6 100%);
            color: white;
            padding: 16px 22px;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .premium-card-body {
            padding: 24px;
        }

        /* Tabs styling */
        .nav-tabs-custom {
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 20px;
        }
        .nav-tabs-custom > li > a {
            border: none;
            color: #64748b;
            font-weight: 600;
            font-size: 14px;
            padding: 12px 20px;
            border-radius: 8px 8px 0 0;
            background: transparent;
            transition: all 0.2s ease;
        }
        .nav-tabs-custom > li.active > a,
        .nav-tabs-custom > li.active > a:hover,
        .nav-tabs-custom > li.active > a:focus {
            border: none;
            border-bottom: 3px solid #00AEEF;
            color: #0077b6;
            background: #ffffff;
        }
        .nav-tabs-custom .badge {
            background-color: #00AEEF;
            margin-left: 6px;
            font-size: 11px;
        }

        /* Filter Pills */
        .filter-pill {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            background: #f1f5f9;
            color: #475569;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-right: 6px;
            margin-bottom: 6px;
        }
        .filter-pill.active, .filter-pill:hover {
            background: #00AEEF;
            color: white;
        }

        /* File Card Styling */
        .file-card {
            border: 1px solid rgba(0, 174, 239, 0.15);
            border-radius: 10px;
            padding: 18px;
            background: #ffffff;
            margin-bottom: 20px;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            min-height: 250px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }
        .file-card:hover {
            box-shadow: 0 8px 25px rgba(0, 174, 239, 0.12);
            transform: translateY(-3px);
            border-color: #00AEEF;
        }
        .file-icon-wrapper {
            width: 44px;
            height: 44px;
            border-radius: 8px;
            background: rgba(0, 174, 239, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .file-icon-wrapper i {
            font-size: 22px;
            color: #00AEEF;
        }
        .file-title {
            font-weight: 700;
            font-size: 14px;
            margin: 0 0 4px 0;
            color: #020b1a;
            line-height: 1.3;
        }
        .file-subject {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: #0077b6;
            margin-bottom: 8px;
            display: inline-block;
        }
        .file-desc {
            font-size: 12px;
            color: #64748b;
            margin: 8px 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 48px;
        }
        .file-meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
            margin-top: 10px;
        }
        .btn-dlhs-primary {
            background: linear-gradient(135deg, #0077b6 0%, #00AEEF 100%);
            color: white !important;
            border: none;
            border-radius: 6px;
            padding: 8px 14px;
            font-weight: 600;
            font-size: 12px;
            text-align: center;
            display: inline-block;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(0, 174, 239, 0.2);
            text-decoration: none !important;
        }
        .btn-dlhs-primary:hover {
            background: linear-gradient(135deg, #020b1a 0%, #0077b6 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 174, 239, 0.3);
        }
        .btn-dlhs-outline {
            background: white;
            color: #0077b6 !important;
            border: 1px solid rgba(0, 174, 239, 0.3);
            border-radius: 6px;
            padding: 8px 14px;
            font-weight: 600;
            font-size: 12px;
            text-align: center;
            display: inline-block;
            transition: all 0.2s ease;
            text-decoration: none !important;
        }
        .btn-dlhs-outline:hover {
            background: rgba(0, 174, 239, 0.06);
            border-color: #00AEEF;
        }

        /* Drag & Drop File Upload Box */
        .file-upload-dropzone {
            border: 2px dashed rgba(0, 174, 239, 0.4);
            background: rgba(0, 174, 239, 0.02);
            border-radius: 10px;
            padding: 30px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .file-upload-dropzone:hover, .file-upload-dropzone.dragover {
            border-color: #00AEEF;
            background: rgba(0, 174, 239, 0.08);
        }

        /* Table styling for Submissions */
        .submission-table {
            width: 100%;
            margin-top: 10px;
        }
        .submission-table th {
            background: #f8fafc;
            color: #0c2340;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 14px;
            border-bottom: 2px solid #e2e8f0;
        }
        .submission-table td {
            padding: 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }

        .loading-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.85);
            display: none;
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .loading-overlay.active { display: flex; }
        .spinner {
            border: 4px solid rgba(0, 174, 239, 0.15);
            border-top: 4px solid #00AEEF;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
  <!-- Loading Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
      <div class="spinner"></div>
      <p style="margin-top: 15px; font-weight: 600; color: #0077b6;">Processing...</p>
  </div>

  <section id="container">
      <!--Header-->
      <?php include 'header.php'; ?>

      <!--Sidebar-->
      <?php include 'sideBar_index.php'; ?>
	       
      <section id="main-content">
          <section class="wrapper">            
              <!-- Banner Row -->
              <div class="dlhs-banner">
                  <div>
                      <h3><i class="fa fa-folder-open"></i> Student Document & Material Hub</h3>
                      <p>Access study materials from your teachers and school administration, or submit assignments and project files directly to your assigned teachers.</p>
                  </div>
                  <div>
                      <button class="btn btn-default" onclick="openSubmitModal()" style="background: #ffffff; color: #0077b6; font-weight: 700; border: none; padding: 10px 18px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                          <i class="fa fa-cloud-upload"></i> Submit File to Teacher
                      </button>
                  </div>
              </div>

              <!-- Top Metrics -->
              <div class="row">
                  <div class="col-md-4 col-sm-6">
                      <div class="stat-card-box">
                          <div class="stat-icon primary">
                              <i class="fa fa-book"></i>
                          </div>
                          <div>
                              <div style="font-size: 22px; font-weight: 700; color: #020b1a;" id="statReceivedCount">0</div>
                              <div style="font-size: 12px; color: #64748b; font-weight: 500;">Study Materials Received</div>
                          </div>
                      </div>
                  </div>
                  <div class="col-md-4 col-sm-6">
                      <div class="stat-card-box">
                          <div class="stat-icon success">
                              <i class="fa fa-paper-plane"></i>
                          </div>
                          <div>
                              <div style="font-size: 22px; font-weight: 700; color: #020b1a;" id="statSubmissionCount">0</div>
                              <div style="font-size: 12px; color: #64748b; font-weight: 500;">Submitted to Teachers</div>
                          </div>
                      </div>
                  </div>
                  <div class="col-md-4 col-sm-12">
                      <div class="stat-card-box">
                          <div class="stat-icon warning">
                              <i class="fa fa-shield"></i>
                          </div>
                          <div>
                              <div style="font-size: 13px; font-weight: 700; color: #020b1a;">Strict Privacy Guaranteed</div>
                              <div style="font-size: 11px; color: #64748b;">Submissions are visible only to you and your teacher.</div>
                          </div>
                      </div>
                  </div>
              </div>

              <!-- Main Document Hub Card -->
              <div class="premium-card">
                  <div class="premium-card-header">
                      <span><i class="fa fa-database"></i> My Academic Files Directory</span>
                      <div>
                          <button class="btn btn-default btn-xs" onclick="loadStudentFiles()" style="background: rgba(255,255,255,0.2); border: none; color: white; border-radius: 4px; padding: 4px 10px;">
                              <i class="fa fa-refresh"></i> Refresh
                          </button>
                      </div>
                  </div>
                  <div class="premium-card-body">
                      <!-- Tabs -->
                      <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                          <li role="presentation" class="active">
                              <a href="#tabMaterials" aria-controls="tabMaterials" role="tab" data-toggle="tab">
                                  <i class="fa fa-book"></i> Study Materials & Notices 
                                  <span class="badge" id="badgeMaterials">0</span>
                              </a>
                          </li>
                          <li role="presentation">
                              <a href="#tabSubmissions" aria-controls="tabSubmissions" role="tab" data-toggle="tab">
                                  <i class="fa fa-paper-plane"></i> My Submissions to Teachers 
                                  <span class="badge" id="badgeSubmissions">0</span>
                              </a>
                          </li>
                      </ul>

                      <div class="tab-content">
                          <!-- Tab 1: Materials Received -->
                          <div role="tabpanel" class="tab-pane active" id="tabMaterials">
                              <!-- Filter Controls -->
                              <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; justify-content: space-between;">
                                  <div style="flex: 1; min-width: 250px; max-width: 400px;">
                                      <input type="text" class="form-control" id="searchMaterials" placeholder="Search materials by title, subject, or teacher..." oninput="filterMaterials()">
                                  </div>
                                  <div>
                                      <span class="filter-pill active" onclick="setMaterialFilter('all', this)">All Documents</span>
                                      <span class="filter-pill" onclick="setMaterialFilter('staff', this)"><i class="fa fa-user"></i> From Teachers</span>
                                      <span class="filter-pill" onclick="setMaterialFilter('admin', this)"><i class="fa fa-shield"></i> From Administration</span>
                                  </div>
                              </div>

                              <!-- Grid View of Files -->
                              <div id="materialsGrid" class="row">
                                  <!-- Dynamically populated -->
                              </div>
                          </div>

                          <!-- Tab 2: My Submissions -->
                          <div role="tabpanel" class="tab-pane" id="tabSubmissions">
                              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                                  <div style="flex: 1; min-width: 250px; max-width: 400px;">
                                      <input type="text" class="form-control" id="searchSubmissions" placeholder="Search my submissions..." oninput="filterSubmissions()">
                                  </div>
                                  <div>
                                      <button class="btn-dlhs-primary" onclick="openSubmitModal()">
                                          <i class="fa fa-plus"></i> Submit New Document
                                      </button>
                                  </div>
                              </div>

                              <div class="alert alert-info" style="background: rgba(0, 174, 239, 0.08); border-left: 4px solid #00AEEF; color: #020b1a; font-size: 13px;">
                                  <i class="fa fa-lock"></i> <strong>Confidential & Private:</strong> Files submitted here are private and strictly accessible only by you and the designated teacher. Other students do not have access to your submissions.
                              </div>

                              <div id="submissionsContainer" class="table-responsive">
                                  <!-- Dynamically populated table -->
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
          </section>
      </section>
  </section>

  <!-- Submit Document to Teacher Modal -->
  <div class="modal fade" id="submitModal" tabindex="-1" role="dialog" aria-labelledby="submitModalLabel">
      <div class="modal-dialog" role="document">
          <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
              <div class="modal-header" style="background: linear-gradient(135deg, #020b1a 0%, #0077b6 100%); color: white; padding: 18px 24px;">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 0.8;"><span aria-hidden="true">&times;</span></button>
                  <h4 class="modal-title" id="submitModalLabel" style="font-weight: 700;"><i class="fa fa-cloud-upload"></i> Submit Document to Teacher</h4>
              </div>
              <form id="studentUploadForm" enctype="multipart/form-data">
                  <div class="modal-body" style="padding: 24px;">
                      <!-- Teacher Selection -->
                      <div class="form-group">
                          <label style="font-weight: 600; color: #0c2340;">Select Recipient Teacher <span class="text-danger">*</span></label>
                          <select class="form-control" id="targetStaffId" name="targetStaffId" required onchange="handleTeacherSelect(this)">
                              <option value="">-- Choose your teacher --</option>
                          </select>
                          <input type="hidden" id="selectedSubjectId" name="subjectId" value="0">
                          <small class="text-muted">You can submit documents directly to teachers assigned to your class.</small>
                      </div>

                      <!-- Document Title -->
                      <div class="form-group">
                          <label style="font-weight: 600; color: #0c2340;">Document Title <span class="text-danger">*</span></label>
                          <input type="text" class="form-control" id="docTitle" name="title" placeholder="e.g. Mathematics Week 3 Project - Chapter 4" required>
                      </div>

                      <!-- Note / Description -->
                      <div class="form-group">
                          <label style="font-weight: 600; color: #0c2340;">Notes / Message to Teacher <small class="text-muted">(Optional)</small></label>
                          <textarea class="form-control" id="docDesc" name="description" rows="3" placeholder="Add any details, question explanations, or notes for your teacher..."></textarea>
                      </div>

                      <!-- File Dropzone -->
                      <div class="form-group">
                          <label style="font-weight: 600; color: #0c2340;">Attach File <span class="text-danger">*</span></label>
                          <div class="file-upload-dropzone" id="dropzoneBox" onclick="$('#studentFileInput').click()">
                              <i class="fa fa-cloud-upload" style="font-size: 38px; color: #00AEEF; margin-bottom: 8px;"></i>
                              <h5 style="margin: 0 0 5px 0; font-weight: 600; color: #020b1a;">Click or Drag & Drop File Here</h5>
                              <p style="margin: 0; font-size: 12px; color: #64748b;">Supports PDF, Word, Excel, PowerPoint, Images, Text (Max 50MB)</p>
                          </div>
                          <input type="file" id="studentFileInput" name="files[]" style="display: none;" onchange="handleFileSelected(this)" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt,.rtf,.odt,.ods,.odp,.zip,.rar,.7z,.tar,.gz,.jpg,.jpeg,.png,.gif,.webp,.svg,.mp3,.wav,.m4a,.aac,.ogg,.mp4,.webm,.mkv,.mov,.avi">
                          <div id="selectedFileInfo" style="margin-top: 10px; display: none;"></div>
                      </div>

                      <!-- Upload Progress -->
                      <div class="progress" id="studentUploadProgress" style="display: none; height: 10px; margin-top: 15px; border-radius: 5px; overflow: hidden;">
                          <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%; background-color: #00AEEF;"></div>
                      </div>
                  </div>
                  <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 14px 24px;">
                      <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn-dlhs-primary" id="btnSubmitFile">
                          <i class="fa fa-paper-plane"></i> Submit to Teacher
                      </button>
                  </div>
              </form>
          </div>
      </div>
  </div>

  <!-- Document Preview Modal -->
  <div class="modal fade" id="previewModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel">
      <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
              <div class="modal-header" style="background: linear-gradient(135deg, #020b1a 0%, #0077b6 100%); color: white; padding: 16px 20px;">
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 0.8;"><span aria-hidden="true">&times;</span></button>
                  <h4 class="modal-title" id="previewModalLabel" style="font-weight: 700;"><i class="fa fa-eye"></i> Document Preview</h4>
              </div>
              <div class="modal-body" id="previewModalBody" style="padding: 20px; background: #f8fafc; min-height: 250px;">
                  <p class="text-center text-muted" style="padding: 40px;"><i class="fa fa-spinner fa-spin"></i> Loading preview...</p>
              </div>
              <div class="modal-footer" style="background: white; border-top: 1px solid #e2e8f0;">
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
  <script src="js/custom.js"></script>
  <script src="student_files.js?v=<?php echo filemtime(__DIR__ . '/student_files.js'); ?>"></script>
</body>
</html>
