<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['studentLoggedIn']) || $_SESSION['studentLoggedIn'] !== "yes") {
    header("location:../");
    exit();
}

$studentId = $_SESSION['studentId'];
$studentName = $_SESSION['studentName'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="My Files - Deeper Life High School">
    <meta name="author" content="DLHS">
    <link rel="icon" type="image/jpg" href="../images/dlhslogo3.jpg">
    <title>My Shared Files | Deeper Life High School</title>

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
        .file-card {
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 10px;
            padding: 20px;
            background: #fff;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            min-height: 240px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .file-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
            transform: translateY(-3px);
            border-color: rgba(104, 138, 126, 0.25);
        }
        .file-top {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .file-icon-wrapper {
            width: 46px;
            height: 46px;
            border-radius: 8px;
            background: rgba(104, 138, 126, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .file-icon-wrapper i {
            font-size: 22px;
            color: #688a7e;
        }
        .file-title {
            font-weight: 600;
            font-size: 15px;
            margin: 0 0 4px 0;
            color: #2e3e38;
            line-height: 1.3;
        }
        .file-subject {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            color: #688a7e;
            margin-bottom: 8px;
            display: inline-block;
        }
        .file-desc {
            font-size: 13px;
            color: #666;
            margin: 10px 0;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 54px;
        }
        .file-meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #999;
            border-top: 1px solid #f0f0f0;
            padding-top: 12px;
            margin-top: 10px;
        }
        .btn-download-premium {
            background: linear-gradient(135deg, #688a7e 0%, #5a7569 100%);
            color: white !important;
            border: none;
            border-radius: 6px;
            padding: 10px 16px;
            font-weight: 600;
            font-size: 13px;
            text-align: center;
            display: block;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(104, 138, 126, 0.15);
            margin-top: 15px;
            text-decoration: none !important;
        }
        .btn-download-premium:hover {
            background: linear-gradient(135deg, #5a7569 0%, #4f6e63 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(104, 138, 126, 0.25);
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
    </style>
</head>
<body>
  <!-- Loading Overlay -->
  <div class="loading-overlay" id="loadingOverlay">
      <div class="spinner"></div>
      <p style="margin-top: 15px; font-weight: 500; color: #4f6e63;">Retrieving files...</p>
  </div>

  <section id="container" class="">
	<!--Header-->
	<?php include 'header.php'; ?>

    <!--Sidebar-->
	<?php include 'sideBar_index.php'; ?>
	       
      <section id="main-content">
          <section class="wrapper">            
			  <div class="row">
				<div class="col-lg-12">
					<h3 class="page-header" style="font-weight: 700; color: #2e3e38;"><i class="fa fa-folder"></i> My Shared Files</h3>
					<ol class="breadcrumb" style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
						<li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
						<li><i class="fa fa-folder-open"></i>Shared Files</li>
					</ol>
				</div>
			  </div>

              <div class="row">
                  <div class="col-md-12">
                      <div class="premium-card">
                          <div class="premium-card-header">
                              <span><i class="fa fa-files-o"></i> Available Study Files & Slides</span>
                              <button class="btn btn-default btn-xs" onclick="loadStudentFiles()" style="background: rgba(255,255,255,0.2); border: none; color: white; border-radius: 4px; padding: 4px 8px;"><i class="fa fa-refresh"></i> Refresh</button>
                          </div>
                          <div class="premium-card-body">
                              <!-- Search Area -->
                              <div style="margin-bottom: 25px; max-width: 400px;">
                                  <input type="text" class="form-control" id="searchFiles" placeholder="Search files by title, subject, or teacher..." oninput="searchFiles()">
                              </div>

                              <!-- Grid View of Files -->
                              <div id="filesGrid" class="row">
                                  <!-- Dynamically loaded -->
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

  <!-- Custom Scripts -->
  <script src="js/custom.js"></script>
  <script src="student_files.js"></script>
</body>
</html>
