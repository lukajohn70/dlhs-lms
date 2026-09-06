<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_request_helper.php";

// Check if user is logged in
if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    header("location:../");
    exit();
}

dlhsEnsureFileRequestTablesExist($connection);

$staffId = $_SESSION['staffId'];
$staffName = $_SESSION['staffName'];

// Get teacher's assigned classes for the "New Request" modal
$classesQuery = "SELECT DISTINCT c.classId, c.className, y.yearGroupName FROM classes c 
                 LEFT JOIN yeargroup y ON c.classYearGroup = y.yearGroupId 
                 WHERE c.classId IN (
                     SELECT classId FROM subject_teacher_assignment WHERE teacherId = $staffId
                     UNION
                     SELECT classId FROM form_teacher_assignment WHERE teacherId = $staffId
                 )
                 ORDER BY y.yearGroupName, c.className";
$classesResult = $connection->query($classesQuery);

// Get teacher's assigned subjects
$subjectsQuery = "SELECT DISTINCT s.subjectId, s.subjectName 
                  FROM subjects s 
                  INNER JOIN subject_teacher_assignment sta ON s.subjectId = sta.subjectId 
                  WHERE sta.teacherId = '$staffId' 
                  ORDER BY s.subjectName ASC";
$subjectsResult = $connection->query($subjectsQuery);

// Get teacher's year groups (for entire year group targeting)
$yearGroupsQuery = "SELECT DISTINCT yg.yearGroupId, yg.yearGroupName
                    FROM yeargroup yg
                    INNER JOIN classes c ON c.classYearGroup = yg.yearGroupId
                    WHERE c.classId IN (
                        SELECT classId FROM subject_teacher_assignment WHERE teacherId = '$staffId'
                        UNION
                        SELECT classId FROM form_teacher_assignment WHERE teacherId = '$staffId'
                    )
                    ORDER BY yg.yearGroupName";
$yearGroupsResult = $connection->query($yearGroupsQuery);
$yearGroupsData = [];
if ($yearGroupsResult) {
    while ($yg = $yearGroupsResult->fetch_assoc()) {
        $yearGroupsData[] = $yg;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="File Requests Management | DLHS">
    <meta name="author" content="DLHS">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <title>File Requests Hub | DLHS</title>

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- bootstrap theme -->
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <!-- external css -->
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />    
    <!-- Custom styles -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #00AEEF;
            --primary-light: #53d0ff;
            --primary-dark: #0087ba;
            --accent: #0acca2;
            --danger: #d9534f;
            --bg-light: #f4f7f6;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: #333;
        }

        .wrapper {
            padding: 25px !important;
        }

        /* Premium Custom Cards */
        .premium-card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(104, 138, 126, 0.15);
            margin-bottom: 25px;
            overflow: hidden;
            transition: var(--transition);
        }

        .premium-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        .premium-card-header {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
            color: white;
            padding: 20px 24px;
            font-weight: 600;
            font-size: 17px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .premium-card-body {
            padding: 25px;
        }

        /* Custom buttons */
        .btn-premium {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
            box-shadow: 0 4px 10px rgba(104, 138, 126, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-premium:hover, .btn-premium:focus {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(104, 138, 126, 0.3);
            text-decoration: none;
        }

        .btn-accent {
            background: linear-gradient(135deg, #0f9f8f 0%, #0d8275 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-accent:hover {
            background: linear-gradient(135deg, #0d8275 0%, #0a695e 100%);
            color: white;
            transform: translateY(-1px);
            text-decoration: none;
        }

        /* Requests Grid */
        .requests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 25px;
        }

        .request-card {
            background: white;
            border-radius: 12px;
            border: 1px solid rgba(0, 0, 0, 0.06);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.03);
            padding: 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .request-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-light);
        }

        .request-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-color: rgba(104, 138, 126, 0.25);
        }

        .request-title {
            font-size: 17px;
            font-weight: 700;
            color: #2e3e38;
            margin: 0 0 10px 0;
            line-height: 1.4;
        }

        .request-meta {
            font-size: 13px;
            color: #666;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .request-meta i {
            color: var(--primary-light);
            width: 16px;
            text-align: center;
        }

        .badge-submissions {
            background: rgba(104, 138, 126, 0.12);
            color: var(--primary-dark);
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
        }

        .request-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #f0f3f2;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Form elements */
        .form-control {
            border-radius: 8px;
            border: 1px solid #dcdfdc;
            padding: 10px 14px;
            height: auto;
            font-size: 14px;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(104, 138, 126, 0.15);
        }

        /* Submissions Table Dashboard */
        .dashboard-header-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .stat-card {
            flex: 1;
            min-width: 180px;
            background: #fafbfa;
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            padding: 15px 20px;
            text-align: center;
        }

        .stat-num {
            font-size: 26px;
            font-weight: 800;
            color: var(--primary-dark);
            margin: 0;
        }

        .stat-label {
            font-size: 12px;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        .table-responsive {
            border: none;
            margin-top: 15px;
        }

        .table-custom {
            width: 100%;
            margin-bottom: 0;
            background: white;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .table-custom th {
            background: #f0f3f2 !important;
            color: #555;
            font-weight: 600;
            font-size: 13px;
            border: none !important;
            padding: 14px 18px !important;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-custom td {
            background: #fafcfb;
            border: none !important;
            padding: 16px 18px !important;
            font-size: 14px;
            vertical-align: middle !important;
            border-top: 1px solid rgba(0,0,0,0.02) !important;
            border-bottom: 1px solid rgba(0,0,0,0.02) !important;
        }

        .table-custom tr td:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
            border-left: 1px solid rgba(0,0,0,0.02);
        }

        .table-custom tr td:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            border-right: 1px solid rgba(0,0,0,0.02);
        }

        .table-custom tr:hover td {
            background: #f4f7f6;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-submitted {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .status-pending {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .status-late {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .btn-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: none;
            transition: var(--transition);
        }

        .btn-circle-download {
            background: rgba(15, 159, 143, 0.1);
            color: #0f9f8f;
        }

        .btn-circle-download:hover {
            background: #0f9f8f;
            color: white;
            transform: scale(1.08);
        }

        .btn-circle-delete {
            background: rgba(217, 83, 79, 0.1);
            color: #d9534f;
        }

        .btn-circle-delete:hover {
            background: #d9534f;
            color: white;
            transform: scale(1.08);
        }

        /* Centered Premium Modal Override using Absolute Positioning */
        .modal {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            z-index: 2200 !important;
            overflow: hidden !important;
        }
        
        .modal-dialog {
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            -webkit-transform: translate(-50%, -50%) !important;
            -ms-transform: translate(-50%, -50%) !important;
            transform: translate(-50%, -50%) !important;
            margin: 0 !important;
            width: 90% !important;
            max-width: 550px !important;
            z-index: 2210 !important;
            float: none !important;
        }

        .modal-content {
            border-radius: 16px !important;
            border: none !important;
            box-shadow: 0 15px 45px rgba(0, 0, 0, 0.2) !important;
            overflow: hidden !important;
            background: #ffffff !important;
        }

        .modal-header {
            background: linear-gradient(135deg, #2e7d6b 0%, #1a5244 100%) !important;
            color: white !important;
            border-top-left-radius: 16px !important;
            border-top-right-radius: 16px !important;
            padding: 20px 24px !important;
            border: none !important;
        }

        .modal-header .close {
            color: white !important;
            opacity: 0.8 !important;
            font-size: 24px !important;
            text-shadow: none !important;
        }

        .modal-header .close:hover {
            opacity: 1 !important;
        }

        .modal-footer {
            border-top: 1px solid #f0f3f2 !important;
            padding: 15px 24px 20px 24px !important;
            background: #ffffff !important;
        }
    </style>
</head>
<body>

<section id="container">
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Sidebar -->
    <?php include 'sideBar.php'; ?>

    <section id="main-content">
        <section class="wrapper">
            <!-- View Mode Switch -->
            <?php if (isset($_GET['view']) && $_GET['view'] === 'submissions'): 
                $reqId = intval($_GET['id']);
            ?>
                <!-- ------------------- SUBMISSIONS VIEW ------------------- -->
                <div class="row">
                    <div class="col-lg-12">
                        <h3 class="page-header" style="font-weight: 700; color: #2e3e38;">
                            <i class="fa fa-inbox"></i> Submissions Dashboard
                        </h3>
                        <ol class="breadcrumb" style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                            <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                            <li><i class="fa fa-inbox"></i><a href="file_requests.php">File Requests</a></li>
                            <li><i class="fa fa-list"></i>Submissions</li>
                        </ol>
                    </div>
                </div>

                <div class="premium-card">
                    <div class="premium-card-header">
                        <span id="dashboardTitle"><i class="fa fa-info-circle"></i> Loading Request Details...</span>
                        <a href="file_requests.php" class="btn btn-default btn-sm" style="color: #333; border-radius: 6px;">
                            <i class="fa fa-arrow-left"></i> Back to Requests
                        </a>
                    </div>
                    <div class="premium-card-body">
                        <!-- Instructions Box -->
                        <div id="requestInstructionsBox" style="background: rgba(104, 138, 126, 0.05); border-left: 4px solid var(--primary-light); padding: 15px 20px; border-radius: 4px; margin-bottom: 25px; display: none;">
                            <strong style="color: var(--primary-dark);"><i class="fa fa-align-left"></i> Instructions:</strong><br>
                            <span id="requestInstructionsText" style="color: #555; font-size: 13.5px; white-space: pre-wrap;"></span>
                        </div>

                        <!-- Statistics Dashboard -->
                        <div class="dashboard-header-stats">
                            <div class="stat-card">
                                <p class="stat-num" id="statTotal">0</p>
                                <p class="stat-label">Total Students</p>
                            </div>
                            <div class="stat-card" style="border-top: 3px solid #28a745;">
                                <p class="stat-num" id="statSubmitted" style="color: #28a745;">0</p>
                                <p class="stat-label">Submitted</p>
                            </div>
                            <div class="stat-card" style="border-top: 3px solid #6c757d;">
                                <p class="stat-num" id="statPending" style="color: #6c757d;">0</p>
                                <p class="stat-label">Pending</p>
                            </div>
                            <div class="stat-card" style="border-top: 3px solid #dc3545;">
                                <p class="stat-num" id="statLate" style="color: #dc3545;">0</p>
                                <p class="stat-label">Late Submissions</p>
                            </div>
                        </div>

                        <!-- Search & Download All Action Bar -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
                            <div style="flex: 1; max-width: 350px; position: relative;">
                                <input type="text" class="form-control" id="searchStudents" placeholder="Search by student name..." oninput="filterSubmissionsTable()">
                                <i class="fa fa-search" style="position: absolute; right: 12px; top: 12px; color: #888;"></i>
                            </div>
                            <div>
                                <a href="download_request_submissions_zip.php?requestId=<?php echo $reqId; ?>" class="btn-accent" id="downloadZipBtn">
                                    <i class="fa fa-file-archive-o"></i> Download All as ZIP
                                </a>
                            </div>
                        </div>

                        <!-- Submissions Table -->
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Class</th>
                                        <th>Status</th>
                                        <th>Submitted File</th>
                                        <th>File Size</th>
                                        <th>Submission Date</th>
                                        <th style="width: 80px; text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="submissionsTableBody">
                                    <!-- Loaded via AJAX -->
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px; color: #666;">
                                            <i class="fa fa-spinner fa-spin fa-2x"></i><br>
                                            <span style="margin-top: 10px; display: inline-block;">Fetching student submission details...</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        loadRequestSubmissions(<?php echo $reqId; ?>);
                    });
                </script>

            <?php else: ?>
                <!-- ------------------- REQUESTS LIST VIEW ------------------- -->
                <div class="row">
                    <div class="col-lg-12">
                        <h3 class="page-header" style="font-weight: 700; color: #2e3e38;">
                            <i class="fa fa-inbox"></i> File Requests Hub
                        </h3>
                        <ol class="breadcrumb" style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                            <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                            <li><i class="fa fa-inbox"></i>File Requests</li>
                        </ol>
                    </div>
                </div>

                <!-- Top Controls -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <div style="flex: 1; max-width: 320px; position: relative;">
                        <input type="text" class="form-control" id="searchRequests" placeholder="Search requests..." oninput="filterRequestsGrid()">
                        <i class="fa fa-search" style="position: absolute; right: 12px; top: 12px; color: #888;"></i>
                    </div>
                    <button type="button" class="btn-premium" data-toggle="modal" data-target="#newRequestModal">
                        <i class="fa fa-plus-circle"></i> Create File Request
                    </button>
                </div>

                <!-- Grid of Requests -->
                <div class="requests-grid" id="requestsGrid">
                    <!-- Loaded dynamically via AJAX -->
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #777;">
                        <i class="fa fa-spinner fa-spin fa-3x" style="color: var(--primary-light);"></i>
                        <h4 style="margin-top: 15px; font-weight: 500;">Loading requests dashboard...</h4>
                    </div>
                </div>
            <?php endif; ?>

        </section>
    </section>
</section>

<!-- ------------------- NEW REQUEST MODAL ------------------- -->
<div class="modal fade" id="newRequestModal" tabindex="-1" role="dialog" aria-labelledby="newRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="newRequestModalLabel" style="font-weight: 700; margin: 0;">
                    <i class="fa fa-plus-circle"></i> Create New File Collection
                </h4>
            </div>
            <form id="createRequestForm">
                <div class="modal-body" style="padding: 24px;">
                    
                    <!-- Request Title -->
                    <div class="form-group">
                        <label for="title" style="font-weight: 600; font-size: 13.5px;">Collection Title *</label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Science Project Term 3 Draft" required>
                    </div>

                    <!-- Instructions -->
                    <div class="form-group">
                        <label for="instructions" style="font-weight: 600; font-size: 13.5px;">Instructions for Students</label>
                        <textarea class="form-control" id="instructions" name="instructions" rows="4" placeholder="Mention file contents, guidelines, or presentation details..."></textarea>
                    </div>

                    <!-- Target Type Toggle -->
                    <div class="form-group">
                        <label style="font-weight: 600; font-size: 13.5px;">Send Request To *</label>
                        <div style="display: flex; gap: 20px; margin-top: 8px; padding: 12px 16px; background: #f8fafb; border-radius: 8px; border: 1px solid #e0e6e3;">
                            <label style="display: flex; align-items: center; gap: 7px; cursor: pointer; font-weight: 500; font-size: 13.5px; margin: 0; color: #333;">
                                <input type="radio" name="targetType" id="targetTypeClass" value="class" checked onchange="toggleTargetType()" style="width:15px;height:15px;accent-color:var(--primary);">
                                <i class="fa fa-users" style="color: var(--primary);"></i> Specific Class
                            </label>
                            <label style="display: flex; align-items: center; gap: 7px; cursor: pointer; font-weight: 500; font-size: 13.5px; margin: 0; color: #333;">
                                <input type="radio" name="targetType" id="targetTypeYearGroup" value="yeargroup" onchange="toggleTargetType()" style="width:15px;height:15px;accent-color:var(--accent);">
                                <i class="fa fa-graduation-cap" style="color: var(--accent);"></i> Entire Year Group
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Specific Class selector -->
                        <div class="col-md-6" id="classSelectorWrap">
                            <div class="form-group">
                                <label for="classId" style="font-weight: 600; font-size: 13.5px;">Target Class *</label>
                                <select class="form-control" id="classId" name="classId">
                                    <option value="">-- Select Class --</option>
                                    <?php if ($classesResult && $classesResult->num_rows > 0): ?>
                                        <?php while ($row = $classesResult->fetch_assoc()): ?>
                                            <option value="<?php echo $row['classId']; ?>"><?php echo htmlspecialchars($row['yearGroupName'] . ' - ' . $row['className']); ?></option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Year Group selector (hidden by default) -->
                        <div class="col-md-6" id="yearGroupSelectorWrap" style="display:none;">
                            <div class="form-group">
                                <label for="yearGroupId" style="font-weight: 600; font-size: 13.5px;">Target Year Group *</label>
                                <select class="form-control" id="yearGroupId" name="yearGroupId" disabled>
                                    <option value="">-- Select Year Group --</option>
                                    <?php foreach ($yearGroupsData as $yg): ?>
                                        <option value="<?php echo htmlspecialchars($yg['yearGroupId']); ?>"><?php echo htmlspecialchars($yg['yearGroupName']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Subject -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="subjectId" style="font-weight: 600; font-size: 13.5px;">Subject (Optional)</label>
                                <select class="form-control" id="subjectId" name="subjectId">
                                    <option value="">-- Select Subject --</option>
                                    <?php if ($subjectsResult && $subjectsResult->num_rows > 0): ?>
                                        <?php while ($row = $subjectsResult->fetch_assoc()): ?>
                                            <option value="<?php echo $row['subjectId']; ?>"><?php echo htmlspecialchars($row['subjectName']); ?></option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Due Date -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="dueDate" style="font-weight: 600; font-size: 13.5px;">Due Date & Time</label>
                                <input type="datetime-local" class="form-control" id="dueDate" name="dueDate">
                            </div>
                        </div>

                        <!-- Max Size -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="maxFileSizeMB" style="font-weight: 600; font-size: 13.5px;">Max File Size</label>
                                <select class="form-control" id="maxFileSizeMB" name="maxFileSizeMB">
                                    <option value="5">5 MB</option>
                                    <option value="10" selected>10 MB</option>
                                    <option value="20">20 MB</option>
                                    <option value="50">50 MB</option>
                                    <option value="100">100 MB</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Allowed Types -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="allowedTypes" style="font-weight: 600; font-size: 13.5px;">Allowed File Extensions</label>
                        <input type="text" class="form-control" id="allowedTypes" name="allowedTypes" value="pdf,doc,docx,jpg,png,zip" placeholder="e.g. pdf,doc,docx (comma separated)">
                        <small style="color: #777; margin-top: 5px; display: inline-block;">Students will only be allowed to upload these extensions.</small>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-premium"><i class="fa fa-check"></i> Create Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ------------------- EDIT REQUEST MODAL ------------------- -->
<div class="modal fade" id="editRequestModal" tabindex="-1" role="dialog" aria-labelledby="editRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="editRequestModalLabel" style="font-weight: 700; margin: 0;">
                    <i class="fa fa-edit"></i> Edit File Collection
                </h4>
            </div>
            <form id="editRequestForm">
                <input type="hidden" id="editRequestId" name="requestId">
                <div class="modal-body" style="padding: 24px;">
                    
                    <!-- Request Title -->
                    <div class="form-group">
                        <label for="editTitle" style="font-weight: 600; font-size: 13.5px;">Collection Title *</label>
                        <input type="text" class="form-control" id="editTitle" name="title" required>
                    </div>

                    <!-- Instructions -->
                    <div class="form-group">
                        <label for="editInstructions" style="font-weight: 600; font-size: 13.5px;">Instructions for Students</label>
                        <textarea class="form-control" id="editInstructions" name="instructions" rows="4"></textarea>
                    </div>

                    <!-- Target Type Toggle -->
                    <div class="form-group">
                        <label style="font-weight: 600; font-size: 13.5px;">Send Request To *</label>
                        <div style="display: flex; gap: 20px; margin-top: 8px; padding: 12px 16px; background: #f8fafb; border-radius: 8px; border: 1px solid #e0e6e3;">
                            <label style="display: flex; align-items: center; gap: 7px; cursor: pointer; font-weight: 500; font-size: 13.5px; margin: 0; color: #333;">
                                <input type="radio" name="editTargetType" id="editTargetTypeClass" value="class" onchange="toggleEditTargetType()" style="width:15px;height:15px;accent-color:var(--primary);">
                                <i class="fa fa-users" style="color: var(--primary);"></i> Specific Class
                            </label>
                            <label style="display: flex; align-items: center; gap: 7px; cursor: pointer; font-weight: 500; font-size: 13.5px; margin: 0; color: #333;">
                                <input type="radio" name="editTargetType" id="editTargetTypeYearGroup" value="yeargroup" onchange="toggleEditTargetType()" style="width:15px;height:15px;accent-color:var(--accent);">
                                <i class="fa fa-graduation-cap" style="color: var(--accent);"></i> Entire Year Group
                            </label>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Specific Class selector -->
                        <div class="col-md-6" id="editClassSelectorWrap">
                            <div class="form-group">
                                <label for="editClassId" style="font-weight: 600; font-size: 13.5px;">Target Class *</label>
                                <select class="form-control" id="editClassId" name="classId">
                                    <option value="">-- Select Class --</option>
                                    <?php 
                                    if ($classesResult && $classesResult->num_rows > 0) {
                                        $classesResult->data_seek(0);
                                        while ($row = $classesResult->fetch_assoc()) {
                                            echo '<option value="' . $row['classId'] . '">' . htmlspecialchars($row['yearGroupName'] . ' - ' . $row['className']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Year Group selector (hidden by default) -->
                        <div class="col-md-6" id="editYearGroupSelectorWrap" style="display:none;">
                            <div class="form-group">
                                <label for="editYearGroupId" style="font-weight: 600; font-size: 13.5px;">Target Year Group *</label>
                                <select class="form-control" id="editYearGroupId" name="yearGroupId" disabled>
                                    <option value="">-- Select Year Group --</option>
                                    <?php foreach ($yearGroupsData as $yg): ?>
                                        <option value="<?php echo htmlspecialchars($yg['yearGroupId']); ?>"><?php echo htmlspecialchars($yg['yearGroupName']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Subject -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editSubjectId" style="font-weight: 600; font-size: 13.5px;">Subject (Optional)</label>
                                <select class="form-control" id="editSubjectId" name="subjectId">
                                    <option value="">-- Select Subject --</option>
                                    <?php 
                                    if ($subjectsResult && $subjectsResult->num_rows > 0) {
                                        $subjectsResult->data_seek(0);
                                        while ($row = $subjectsResult->fetch_assoc()) {
                                            echo '<option value="' . $row['subjectId'] . '">' . htmlspecialchars($row['subjectName']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Due Date -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editDueDate" style="font-weight: 600; font-size: 13.5px;">Due Date & Time</label>
                                <input type="datetime-local" class="form-control" id="editDueDate" name="dueDate">
                            </div>
                        </div>

                        <!-- Max Size -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editMaxFileSizeMB" style="font-weight: 600; font-size: 13.5px;">Max File Size</label>
                                <select class="form-control" id="editMaxFileSizeMB" name="maxFileSizeMB">
                                    <option value="5">5 MB</option>
                                    <option value="10">10 MB</option>
                                    <option value="20">20 MB</option>
                                    <option value="50">50 MB</option>
                                    <option value="100">100 MB</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Allowed Types -->
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="editAllowedTypes" style="font-weight: 600; font-size: 13.5px;">Allowed File Extensions</label>
                        <input type="text" class="form-control" id="editAllowedTypes" name="allowedTypes" placeholder="e.g. pdf,doc,docx (comma separated)">
                        <small style="color: #777; margin-top: 5px; display: inline-block;">Students will only be allowed to upload these extensions.</small>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-premium"><i class="fa fa-check"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="js/jquery.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/jquery.scrollTo.min.js"></script>
<script src="js/jquery.nicescroll.js" type="text/javascript"></script>
<script src="js/custom.js"></script>

<script>
$(document).ready(function() {
    <?php if (!isset($_GET['view'])): ?>
        loadTeacherFileRequests();
    <?php endif; ?>

    // Handle form submit
    $("#createRequestForm").on("submit", function(e) {
        e.preventDefault();
        
        $.ajax({
            url: "create_file_request.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $('#newRequestModal').modal('hide');
                    $("#createRequestForm")[0].reset();
                    toggleTargetType(); // reset UI to default
                    alert(response.message);
                    loadTeacherFileRequests();
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function() {
                alert("Failed to connect to the server. Please try again.");
            }
        });
    });

    // Handle edit form submit
    $("#editRequestForm").on("submit", function(e) {
        e.preventDefault();
        
        $.ajax({
            url: "update_file_request.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $('#editRequestModal').modal('hide');
                    $("#editRequestForm")[0].reset();
                    alert(response.message);
                    loadTeacherFileRequests();
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function() {
                alert("Failed to connect to the server. Please try again.");
            }
        });
    });

    // Handle edit button click
    $(document).on("click", ".edit-request-btn", function() {
        let id = $(this).data("id");
        let title = $(this).data("title");
        let instructions = $(this).data("instructions");
        let classId = $(this).data("class-id");
        let yearGroupId = $(this).data("year-group-id");
        let subjectId = $(this).data("subject-id");
        let dueDate = $(this).data("due-date");
        let maxSize = $(this).data("max-size");
        let allowedTypes = $(this).data("allowed-types");
        
        $("#editRequestId").val(id);
        $("#editTitle").val(title);
        $("#editInstructions").val(instructions);
        $("#editSubjectId").val(subjectId || "");
        $("#editMaxFileSizeMB").val(maxSize || "10");
        $("#editAllowedTypes").val(allowedTypes || "pdf,doc,docx,jpg,png,zip");
        
        if (dueDate) {
            let formattedDate = dueDate.replace(' ', 'T').substring(0, 16);
            $("#editDueDate").val(formattedDate);
        } else {
            $("#editDueDate").val("");
        }
        
        if (yearGroupId && yearGroupId !== 'null' && yearGroupId !== '') {
            $("#editTargetTypeYearGroup").prop("checked", true);
            $("#editYearGroupId").val(yearGroupId);
            $("#editClassId").val("");
        } else {
            $("#editTargetTypeClass").prop("checked", true);
            $("#editClassId").val(classId);
            $("#editYearGroupId").val("");
        }
        
        toggleEditTargetType();
        $("#editRequestModal").modal("show");
    });
});

// Toggle between Specific Class and Entire Year Group selectors
function toggleTargetType() {
    var type = $('input[name="targetType"]:checked').val();
    if (type === 'yeargroup') {
        $('#classSelectorWrap').hide();
        $('#yearGroupSelectorWrap').show();
        $('#classId').prop('disabled', true).val('');
        $('#yearGroupId').prop('disabled', false);
    } else {
        $('#classSelectorWrap').show();
        $('#yearGroupSelectorWrap').hide();
        $('#classId').prop('disabled', false);
        $('#yearGroupId').prop('disabled', true).val('');
    }
}

// Toggle between Specific Class and Entire Year Group selectors for Edit
function toggleEditTargetType() {
    var type = $('input[name="editTargetType"]:checked').val();
    if (type === 'yeargroup') {
        $('#editClassSelectorWrap').hide();
        $('#editYearGroupSelectorWrap').show();
        $('#editClassId').prop('disabled', true).val('');
        $('#editYearGroupId').prop('disabled', false);
    } else {
        $('#editClassSelectorWrap').show();
        $('#editYearGroupSelectorWrap').hide();
        $('#editClassId').prop('disabled', false);
        $('#editYearGroupId').prop('disabled', true).val('');
    }
}

// Load the list of requests created by the teacher
function loadTeacherFileRequests() {
    $.ajax({
        url: "get_file_requests.php",
        type: "GET",
        dataType: "json",
        success: function(data) {
            let container = $("#requestsGrid");
            if (!data || data.length === 0) {
                container.html(`
                    <div style="grid-column: 1/-1; text-align: center; padding: 80px 20px; background: white; border-radius: 12px; border: 1px dashed rgba(104,138,126,0.4);">
                        <i class="fa fa-inbox fa-4x" style="color: var(--primary-light); opacity: 0.6; margin-bottom: 15px;"></i>
                        <h4 style="font-weight: 600; color: #2e3e38; margin-bottom: 6px;">No File Requests Found</h4>
                        <p style="color: #666; max-width: 400px; margin: 0 auto 20px auto; font-size: 13.5px;">
                            You haven't created any file collection requests yet. Click the button above to request files from your classes!
                        </p>
                        <button type="button" class="btn btn-premium" data-toggle="modal" data-target="#newRequestModal">
                            <i class="fa fa-plus-circle"></i> Create Your First Request
                        </button>
                    </div>
                `);
                return;
            }

            let html = '';
            data.forEach(function(req) {
                let dueText = req.dueDate ? formatDate(req.dueDate) : 'No due date';
                let instructionsSnippet = req.instructions ? 
                    (req.instructions.length > 80 ? req.instructions.substring(0, 80) + '...' : req.instructions) : 
                    'No instructions provided.';

                html += `
                    <div class="request-card" data-title="${req.title.toLowerCase()}" data-class="${req.className.toLowerCase()}">
                        <div>
                            <h4 class="request-title">${escapeHtml(req.title)}</h4>
                            <p style="color: #666; font-size: 13px; line-height: 1.5; margin-bottom: 15px; font-style: italic;">
                                "${escapeHtml(instructionsSnippet)}"
                            </p>
                            <div class="request-meta">
                                <i class="fa fa-graduation-cap"></i>
                                <span><strong>Class:</strong> ${escapeHtml(req.className)}</span>
                            </div>
                            <div class="request-meta">
                                <i class="fa fa-book"></i>
                                <span><strong>Subject:</strong> ${escapeHtml(req.subjectName)}</span>
                            </div>
                            <div class="request-meta">
                                <i class="fa fa-calendar"></i>
                                <span><strong>Due:</strong> ${dueText}</span>
                            </div>
                            <div class="request-meta">
                                <i class="fa fa-cog"></i>
                                <span style="font-size: 12px; color: #777;">
                                    Allowed: <strong style="text-transform: uppercase;">${req.allowedTypes}</strong> (${req.maxFileSizeMB}MB max)
                                </span>
                            </div>
                        </div>

                        <div class="request-footer">
                            <span class="badge-submissions">
                                <i class="fa fa-cloud-upload"></i> ${req.submissionCount} Submissions
                            </span>
                            <div style="display: flex; gap: 8px;">
                                <a href="file_requests.php?view=submissions&id=${req.requestId}" class="btn btn-sm btn-default" style="font-weight: 600; border-radius: 6px;">
                                    <i class="fa fa-eye"></i> View
                                </a>
                                <button class="btn btn-sm btn-primary edit-request-btn" 
                                        data-id="${req.requestId}" 
                                        data-title="${escapeHtml(req.title)}" 
                                        data-instructions="${escapeHtml(req.instructions || '')}" 
                                        data-class-id="${req.classId || ''}" 
                                        data-year-group-id="${req.yearGroupId || ''}" 
                                        data-subject-id="${req.subjectId || ''}" 
                                        data-due-date="${req.dueDate || ''}" 
                                        data-max-size="${req.maxFileSizeMB || '10'}" 
                                        data-allowed-types="${req.allowedTypes || ''}"
                                        style="border-radius: 6px; padding: 5px 10px;" 
                                        title="Edit Request">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button onclick="deleteRequest(${req.requestId})" class="btn btn-sm btn-danger" style="border-radius: 6px; padding: 5px 10px;" title="Delete Request">
                                    <i class="fa fa-trash-o"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            container.html(html);
        },
        error: function() {
            $("#requestsGrid").html(`
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #dc3545;">
                    <i class="fa fa-exclamation-triangle fa-2x"></i><br>
                    <strong>Failed to load requests from server. Please try refreshing.</strong>
                </div>
            `);
        }
    });
}

// Delete request function
function deleteRequest(requestId) {
    if (confirm("Are you sure you want to delete this file collection request? Students will no longer see it, and you won't be able to retrieve submissions here.")) {
        $.ajax({
            url: "delete_file_request.php",
            type: "POST",
            data: { requestId: requestId },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    loadTeacherFileRequests();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert("Failed to delete request.");
            }
        });
    }
}

// Load submission dashboard for a specific request ID
let submissionsData = [];
function loadRequestSubmissions(requestId) {
    $.ajax({
        url: "get_request_submissions.php",
        type: "GET",
        data: { requestId: requestId },
        dataType: "json",
        success: function(data) {
            if (data.error) {
                $("#submissionsTableBody").html(`
                    <tr>
                        <td colspan="6" style="text-align: center; color: red; padding: 40px;">
                            <i class="fa fa-exclamation-triangle"></i> ${data.error}
                        </td>
                    </tr>
                `);
                return;
            }

            // Fill header details
            $("#dashboardTitle").html(`<i class="fa fa-inbox"></i> ${escapeHtml(data.request.title)} | ${escapeHtml(data.request.className)}`);
            
            if (data.request.instructions) {
                $("#requestInstructionsText").text(data.request.instructions);
                $("#requestInstructionsBox").fadeIn();
            } else {
                $("#requestInstructionsBox").hide();
            }

            submissionsData = data.students;

            // Calculate counts
            let total = submissionsData.length;
            let submitted = 0;
            let late = 0;

            submissionsData.forEach(function(s) {
                if (s.submitted) {
                    submitted++;
                    if (s.isLate) late++;
                }
            });

            $("#statTotal").text(total);
            $("#statSubmitted").text(submitted);
            $("#statPending").text(total - submitted);
            $("#statLate").text(late);

            // Handle ZIP download visibility
            if (submitted === 0) {
                $("#downloadZipBtn").addClass("disabled").attr("href", "javascript:void(0)").css("opacity", "0.6");
            } else {
                $("#downloadZipBtn").removeClass("disabled").attr("href", "download_request_submissions_zip.php?requestId=" + requestId).css("opacity", "1");
            }

            renderSubmissionsTable(submissionsData);
        },
        error: function() {
            $("#submissionsTableBody").html(`
                <tr>
                    <td colspan="6" style="text-align: center; color: red; padding: 40px;">
                        <i class="fa fa-exclamation-triangle"></i> Failed to connect to server.
                    </td>
                </tr>
            `);
        }
    });
}

// Render the submissions table from data array
function renderSubmissionsTable(data) {
    let tbody = $("#submissionsTableBody");
    if (!data || data.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="7" style="text-align: center; padding: 30px; color: #777;">
                    No student records found in this class.
                </td>
            </tr>
        `);
        return;
    }

    let html = '';
    data.forEach(function(s) {
        let fullName = `${s.surname}, ${s.firstName} ${s.middleName || ''}`.trim();
        let statusHtml = '';
        let fileHtml = '—';
        let sizeHtml = '—';
        let dateHtml = '—';
        let actionHtml = '';

        if (s.submitted) {
            if (s.isLate) {
                statusHtml = '<span class="status-badge status-late"><i class="fa fa-clock-o"></i> Submitted (Late)</span>';
            } else {
                statusHtml = '<span class="status-badge status-submitted"><i class="fa fa-check-circle"></i> Submitted</span>';
            }
            fileHtml = `<span style="font-weight: 600; color: var(--primary-dark);"><i class="fa fa-file-text-o"></i> ${escapeHtml(s.originalName)}</span>`;
            sizeHtml = formatBytes(s.fileSize);
            dateHtml = formatDate(s.submittedAt);
            actionHtml = `
                <a href="../../download_file.php?submissionId=${s.submissionId}&type=file_request" class="btn-circle btn-circle-download" title="Download File">
                    <i class="fa fa-download"></i>
                </a>
            `;
        } else {
            statusHtml = '<span class="status-badge status-pending"><i class="fa fa-hourglass-o"></i> Pending</span>';
        }

        html += `
            <tr class="student-row" data-name="${fullName.toLowerCase()}">
                <td style="font-weight: 600; color: #333;">${escapeHtml(fullName)}</td>
                <td style="font-size: 12.5px; color: #555;">${escapeHtml(s.className || '')}</td>
                <td>${statusHtml}</td>
                <td style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${fileHtml}</td>
                <td>${sizeHtml}</td>
                <td>${dateHtml}</td>
                <td style="text-align: center;">${actionHtml}</td>
            </tr>
        `;
    });

    tbody.html(html);
}

// Client-side text filter for requests grid
function filterRequestsGrid() {
    let query = $("#searchRequests").val().toLowerCase();
    $(".request-card").each(function() {
        let title = $(this).attr("data-title");
        let className = $(this).attr("data-class");
        if (title.indexOf(query) !== -1 || className.indexOf(query) !== -1) {
            $(this).fadeIn(200);
        } else {
            $(this).fadeOut(200);
        }
    });
}

// Client-side text filter for student submissions table
function filterSubmissionsTable() {
    let query = $("#searchStudents").val().toLowerCase();
    $(".student-row").each(function() {
        let name = $(this).attr("data-name");
        if (name.indexOf(query) !== -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

// Helper formats
function formatDate(dateString) {
    if (!dateString) return '';
    let d = new Date(dateString.replace(/-/g, "/"));
    let months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    let day = d.getDate();
    let month = months[d.getMonth()];
    let year = d.getFullYear();
    let hours = d.getHours();
    let minutes = d.getMinutes();
    let ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // 0 should be 12
    minutes = minutes < 10 ? '0' + minutes : minutes;
    return `${month} ${day}, ${year} ${hours}:${minutes} ${ampm}`;
}

function formatBytes(bytes, decimals = 1) {
    if (!bytes) return '—';
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>
</body>
</html>
