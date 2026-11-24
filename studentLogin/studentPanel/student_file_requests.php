<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/userExpiredSession.php";
require_once __DIR__ . "/../../db_connection/dlhs_db_connection.php";
require_once __DIR__ . "/../../scripts/file_request_helper.php";

// Check if user is logged in
if (!isset($_SESSION['studentLoggedIn']) || $_SESSION['studentLoggedIn'] !== "yes") {
    header("location:../");
    exit();
}

dlhsEnsureFileRequestTablesExist($connection);

$studentId = $_SESSION['studentId'];
$studentName = $_SESSION['studentName'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="description" content="File Submission Requests | DLHS Student Portal">
    <meta name="author" content="DLHS">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <title>File Requests | DLHS Student Portal</title>

    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap theme -->
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <!-- External css -->
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />    
    <!-- Custom styles -->
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
            --dlhs-card-shadow: 0 4px 20px rgba(2, 11, 26, 0.06);
            --dlhs-success: #10b981;
            --dlhs-warning: #f59e0b;
            --dlhs-danger: #ef4444;
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f7f6;
            color: #334155;
            -webkit-tap-highlight-color: transparent;
        }

        .wrapper {
            padding: 24px !important;
        }

        /* DLHS Brand Header Banner */
        .dlhs-banner {
            background: linear-gradient(135deg, #020b1a 0%, #0077b6 60%, #00AEEF 100%);
            border-radius: 14px;
            color: #ffffff;
            padding: 22px 24px;
            margin-bottom: 22px;
            box-shadow: 0 4px 20px rgba(0, 174, 239, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .dlhs-banner h3 {
            margin: 0 0 6px 0;
            font-weight: 700;
            font-size: 22px;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dlhs-banner p {
            margin: 0;
            opacity: 0.92;
            font-size: 13.5px;
            line-height: 1.4;
        }

        .dlhs-banner .banner-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .dlhs-banner .btn-refresh {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.35);
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .dlhs-banner .btn-refresh:hover {
            background: rgba(255, 255, 255, 0.3);
            color: #ffffff;
            text-decoration: none;
        }

        /* Stat Counter Cards */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid var(--dlhs-border);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            display: flex;
            align-items: center;
            gap: 14px;
            transition: var(--transition);
            cursor: pointer;
        }

        .stat-card:hover, .stat-card.active {
            transform: translateY(-2px);
            border-color: var(--dlhs-primary);
            box-shadow: 0 6px 18px rgba(0, 174, 239, 0.12);
        }

        .stat-card.active {
            background: #f0f9ff;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            flex-shrink: 0;
        }

        .stat-icon.blue { background: rgba(0, 174, 239, 0.12); color: var(--dlhs-primary); }
        .stat-icon.amber { background: rgba(245, 158, 11, 0.12); color: var(--dlhs-warning); }
        .stat-icon.green { background: rgba(16, 185, 129, 0.12); color: var(--dlhs-success); }
        .stat-icon.red { background: rgba(239, 68, 68, 0.12); color: var(--dlhs-danger); }

        .stat-info .stat-num {
            font-size: 20px;
            font-weight: 700;
            color: var(--dlhs-navy);
            line-height: 1.1;
        }

        .stat-info .stat-lbl {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
            margin-top: 3px;
        }

        /* Filter Controls Bar */
        .controls-bar {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid rgba(226, 232, 240, 0.9);
            padding: 12px 16px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .filter-tabs {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .filter-tab {
            border: none;
            background: transparent;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 600;
            color: #64748b;
            transition: var(--transition);
            cursor: pointer;
        }

        .filter-tab:hover {
            background: #f1f5f9;
            color: var(--dlhs-navy);
        }

        .filter-tab.active {
            background: var(--dlhs-primary);
            color: #ffffff;
        }

        .search-box {
            position: relative;
            min-width: 220px;
            flex-grow: 1;
            max-width: 340px;
        }

        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 13px;
        }

        .search-box input {
            width: 100%;
            padding: 7px 12px 7px 34px;
            border-radius: 20px;
            border: 1px solid #cbd5e1;
            font-size: 13px;
            outline: none;
            transition: var(--transition);
        }

        .search-box input:focus {
            border-color: var(--dlhs-primary);
            box-shadow: 0 0 0 3px rgba(0, 174, 239, 0.15);
        }

        /* Requests Grid Layout */
        .requests-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .student-request-card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: var(--dlhs-card-shadow);
            border: 1px solid rgba(0, 174, 239, 0.15);
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        .student-request-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 28px rgba(2, 11, 26, 0.09);
            border-color: rgba(0, 174, 239, 0.35);
        }

        .student-request-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--dlhs-primary);
        }

        .student-request-card.submitted::before {
            background: var(--dlhs-success);
        }

        .student-request-card.overdue::before {
            background: var(--dlhs-danger);
        }

        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .request-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--dlhs-navy);
            margin: 0;
            line-height: 1.35;
            word-break: break-word;
            flex: 1 1 65%;
        }

        .teacher-badge {
            font-size: 12px;
            background: rgba(0, 174, 239, 0.08);
            color: var(--dlhs-secondary);
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
        }

        .instructions-text {
            background: #f8fafc;
            border-left: 3px solid #cbd5e1;
            padding: 9px 12px;
            border-radius: 4px;
            font-size: 12.5px;
            color: #475569;
            margin-bottom: 14px;
            font-style: italic;
            white-space: pre-wrap;
            line-height: 1.45;
            word-break: break-word;
        }

        .meta-list {
            margin-bottom: 16px;
        }

        .meta-item {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 6px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.4;
            word-break: break-word;
        }

        .meta-item i {
            color: var(--dlhs-secondary);
            width: 16px;
            text-align: center;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .meta-item strong {
            color: #334155;
        }

        /* Status badges */
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        .badge-pending {
            background: rgba(245, 158, 11, 0.12);
            color: #b45309;
            border: 1px solid rgba(245, 158, 11, 0.25);
        }

        .badge-submitted {
            background: rgba(16, 185, 129, 0.12);
            color: var(--dlhs-success);
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .badge-overdue {
            background: rgba(239, 68, 68, 0.12);
            color: var(--dlhs-danger);
            border: 1px solid rgba(239, 68, 68, 0.25);
        }

        .submitted-file-info {
            background: rgba(16, 185, 129, 0.05);
            border: 1px dashed rgba(16, 185, 129, 0.3);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 12.5px;
            margin-bottom: 12px;
        }

        .submitted-file-info p {
            margin: 3px 0;
            color: #334155;
            word-break: break-word;
        }

        /* Buttons styling */
        .btn-action {
            width: 100%;
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: 700;
            font-size: 13.5px;
            border: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            cursor: pointer;
            min-height: 42px;
        }

        .btn-action-primary {
            background: linear-gradient(135deg, var(--dlhs-secondary) 0%, var(--dlhs-primary) 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 174, 239, 0.25);
        }

        .btn-action-primary:hover, .btn-action-primary:focus {
            background: linear-gradient(135deg, #020b1a 0%, var(--dlhs-secondary) 100%);
            color: #ffffff;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 174, 239, 0.35);
        }

        .btn-action-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--dlhs-success);
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .btn-action-success:hover, .btn-action-success:focus {
            background: var(--dlhs-success);
            color: #ffffff;
            text-decoration: none;
        }

        .btn-action-danger {
            background: linear-gradient(135deg, #f87171 0%, var(--dlhs-danger) 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }

        .btn-action-danger:hover, .btn-action-danger:focus {
            background: linear-gradient(135deg, var(--dlhs-danger) 0%, #b91c1c 100%);
            color: #ffffff;
            text-decoration: none;
            transform: translateY(-1px);
        }

        /* Progress Bar */
        .progress-bar-custom {
            height: 10px;
            border-radius: 6px;
            background-color: #e2e8f0;
            overflow: hidden;
            margin-top: 14px;
            display: none;
        }

        .progress-bar-custom-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(135deg, var(--dlhs-secondary) 0%, var(--dlhs-primary) 100%);
            transition: width 0.1s linear;
        }

        /* Modal Dialog Styling */
        .modal {
            text-align: center;
            padding: 0 !important;
            z-index: 2200 !important;
        }

        .modal:before {
            content: '';
            display: inline-block;
            height: 100%;
            vertical-align: middle;
            margin-right: -4px;
        }

        .modal-dialog {
            display: inline-block;
            text-align: left;
            vertical-align: middle;
            width: 100%;
            max-width: 520px;
            margin: 20px auto;
        }

        .modal-content {
            border-radius: 16px;
            border: 1px solid rgba(0, 174, 239, 0.2);
            box-shadow: 0 15px 45px rgba(2, 11, 26, 0.2);
            overflow: hidden;
            background: #ffffff;
        }

        .modal-header {
            background: linear-gradient(135deg, #020b1a 0%, var(--dlhs-secondary) 60%, var(--dlhs-primary) 100%);
            color: #ffffff;
            padding: 18px 22px;
            border: none;
        }

        .modal-header .modal-title {
            font-weight: 700;
            font-size: 17px;
            margin: 0;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-header .close {
            color: #ffffff;
            opacity: 0.85;
            font-size: 24px;
            text-shadow: none;
            margin-top: -3px;
        }

        .modal-header .close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 22px;
        }

        .modal-footer {
            border-top: 1px solid #f1f5f9;
            padding: 14px 22px;
            background: #ffffff;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .dropzone-area {
            border: 2px dashed #93c5fd;
            border-radius: 12px;
            padding: 28px 16px;
            text-align: center;
            background: #f0f9ff;
            cursor: pointer;
            transition: var(--transition);
        }

        .dropzone-area:hover, .dropzone-area.dragover {
            border-color: var(--dlhs-primary);
            background: rgba(0, 174, 239, 0.08);
            transform: scale(1.01);
        }

        .dropzone-area i {
            font-size: 36px;
            color: var(--dlhs-primary);
            margin-bottom: 8px;
            display: block;
        }

        .dropzone-area p {
            margin: 0;
            font-size: 13.5px;
            color: #334155;
            line-height: 1.4;
        }

        /* Mobile & Responsive Optimizations */
        @media (max-width: 767px) {
            .wrapper {
                padding: 14px !important;
            }

            .dlhs-banner {
                padding: 18px 16px;
                border-radius: 10px;
            }

            .dlhs-banner h3 {
                font-size: 18px;
            }

            .dlhs-banner p {
                font-size: 12.5px;
            }

            .stat-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .stat-card {
                padding: 12px;
            }

            .stat-icon {
                width: 38px;
                height: 38px;
                font-size: 16px;
            }

            .stat-info .stat-num {
                font-size: 17px;
            }

            .stat-info .stat-lbl {
                font-size: 11px;
            }

            .controls-bar {
                padding: 10px 12px;
                flex-direction: column;
                align-items: stretch;
            }

            .filter-tabs {
                justify-content: space-between;
                width: 100%;
            }

            .filter-tab {
                padding: 5px 10px;
                font-size: 11.5px;
            }

            .search-box {
                max-width: 100%;
                min-width: 100%;
            }

            .requests-container {
                grid-template-columns: 1fr;
                gap: 14px;
            }

            .student-request-card {
                padding: 16px;
                border-radius: 12px;
            }

            .request-title {
                font-size: 15px;
            }

            /* Fix modal for small screens */
            .modal:before {
                display: none !important;
            }

            .modal-dialog {
                display: block !important;
                margin: 12px auto !important;
                width: calc(100% - 20px) !important;
                max-width: 100% !important;
            }

            .modal-header {
                padding: 14px 16px;
            }

            .modal-body {
                padding: 16px;
            }

            .modal-footer {
                padding: 12px 16px;
            }

            .dropzone-area {
                padding: 20px 12px;
            }

            .dropzone-area i {
                font-size: 28px;
            }
        }

        @media (max-width: 400px) {
            .stat-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<section id="container">
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Sidebar -->
    <?php include 'sideBar_index.php'; ?>

    <section id="main-content">
        <section class="wrapper">
            
            <!-- DLHS Signature Banner -->
            <div class="dlhs-banner">
                <div>
                    <h3><i class="fa fa-inbox"></i> File Submission Requests</h3>
                    <p>Review files requested by your subject teachers and submit your completed documents before the due date.</p>
                </div>
                <div class="banner-meta">
                    <button class="btn-refresh" onclick="loadStudentFileRequests(true)">
                        <i class="fa fa-refresh" id="refreshIcon"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Summary Stats Counters -->
            <div class="stat-grid">
                <div class="stat-card active" data-filter="all" onclick="applyTabFilter('all')">
                    <div class="stat-icon blue">
                        <i class="fa fa-folder-open"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-num" id="statAll">0</div>
                        <div class="stat-lbl">All Requests</div>
                    </div>
                </div>

                <div class="stat-card" data-filter="pending" onclick="applyTabFilter('pending')">
                    <div class="stat-icon amber">
                        <i class="fa fa-hourglass-half"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-num" id="statPending">0</div>
                        <div class="stat-lbl">Pending Upload</div>
                    </div>
                </div>

                <div class="stat-card" data-filter="submitted" onclick="applyTabFilter('submitted')">
                    <div class="stat-icon green">
                        <i class="fa fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-num" id="statSubmitted">0</div>
                        <div class="stat-lbl">Submitted</div>
                    </div>
                </div>

                <div class="stat-card" data-filter="overdue" onclick="applyTabFilter('overdue')">
                    <div class="stat-icon red">
                        <i class="fa fa-clock-o"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-num" id="statOverdue">0</div>
                        <div class="stat-lbl">Overdue</div>
                    </div>
                </div>
            </div>

            <!-- Controls: Filter Tabs & Search -->
            <div class="controls-bar">
                <div class="filter-tabs">
                    <button class="filter-tab active" data-filter="all" onclick="applyTabFilter('all')">All</button>
                    <button class="filter-tab" data-filter="pending" onclick="applyTabFilter('pending')">Pending</button>
                    <button class="filter-tab" data-filter="submitted" onclick="applyTabFilter('submitted')">Submitted</button>
                    <button class="filter-tab" data-filter="overdue" onclick="applyTabFilter('overdue')">Overdue</button>
                </div>
                <div class="search-box">
                    <i class="fa fa-search"></i>
                    <input type="text" id="requestSearchInput" placeholder="Search by title, teacher, subject..." oninput="handleSearch()">
                </div>
            </div>

            <!-- File Requests Grid -->
            <div id="fileRequestsList" class="requests-container">
                <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #64748b;">
                    <i class="fa fa-spinner fa-spin fa-2x" style="color: var(--dlhs-primary); margin-bottom: 12px;"></i>
                    <p style="font-size: 14px; font-weight: 500;">Loading file requests...</p>
                </div>
            </div>

        </section>
    </section>
</section>

<!-- ------------------- UPLOAD FILE MODAL ------------------- -->
<div class="modal fade" id="uploadRequestModal" tabindex="-1" role="dialog" aria-labelledby="uploadRequestModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="uploadRequestModalLabel">
                    <i class="fa fa-cloud-upload"></i> Submit Requested File
                </h4>
            </div>
            <form id="uploadRequestForm" enctype="multipart/form-data">
                <input type="hidden" name="requestId" id="modalRequestId">
                <div class="modal-body">
                    
                    <div style="margin-bottom: 16px;">
                        <h4 style="font-weight: 700; color: var(--dlhs-navy); margin: 0 0 4px 0; font-size: 16px;" id="modalRequestTitle">Request Title</h4>
                        <p style="color: #64748b; font-size: 13px; margin: 0;" id="modalTeacherName">From: Teacher</p>
                    </div>

                    <!-- Allowed types/size notice -->
                    <div style="background: #f8fafc; border: 1px solid var(--dlhs-border); border-radius: 10px; padding: 12px 14px; font-size: 12.5px; color: #475569; margin-bottom: 18px;">
                        <div style="margin-bottom: 4px;">
                            <i class="fa fa-file-text-o" style="color: var(--dlhs-secondary); width: 16px;"></i> 
                            Allowed Formats: <strong style="text-transform: uppercase; color: var(--dlhs-navy);" id="modalAllowedTypes">PDF, DOCX</strong>
                        </div>
                        <div>
                            <i class="fa fa-balance-scale" style="color: var(--dlhs-secondary); width: 16px;"></i> 
                            Maximum Size: <strong style="color: var(--dlhs-navy);" id="modalMaxSize">10 MB</strong>
                        </div>
                    </div>

                    <!-- Elegant Touch & Dropzone Area -->
                    <div class="dropzone-area" id="dropzoneClickable">
                        <i class="fa fa-cloud-upload"></i>
                        <p><strong>Tap or click to browse file</strong><br><span style="font-size: 12px; color: #64748b;">(or drag & drop here)</span></p>
                        <div id="selectedFileName" style="display: none; margin-top: 10px; font-weight: 600; color: var(--dlhs-secondary); font-size: 13px; word-break: break-all;"></div>
                    </div>
                    <input type="file" name="requestFile" id="requestFileInput" style="display: none;" required>

                    <!-- Alert / Status feedback inside modal -->
                    <div id="modalAlertBox" style="display: none; margin-top: 14px; padding: 10px 14px; border-radius: 8px; font-size: 13px;"></div>

                    <!-- Progress bar -->
                    <div class="progress-bar-custom" id="progressBarCustom">
                        <div class="progress-bar-custom-fill" id="progressBarFill"></div>
                    </div>
                    <div id="progressText" style="display: none; font-size: 12px; font-weight: 600; text-align: right; margin-top: 5px; color: #64748b;">0%</div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 8px; padding: 8px 16px;">Cancel</button>
                    <button type="submit" class="btn btn-action-primary" id="modalSubmitBtn" style="border-radius: 8px; font-weight: 600; padding: 8px 22px; width: auto; min-height: 38px;">
                        <i class="fa fa-upload"></i> Submit File
                    </button>
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
let allRequestsData = [];
let currentFilter = 'all';
let currentSearch = '';

$(document).ready(function() {
    loadStudentFileRequests();

    // Trigger file picker
    $("#dropzoneClickable").on("click", function() {
        $("#requestFileInput").click();
    });

    // File selection display & client-side validation
    $("#requestFileInput").on("change", function(e) {
        let file = e.target.files[0];
        if (file) {
            $("#selectedFileName").html(`<i class="fa fa-file-text-o"></i> ${escapeHtml(file.name)} <span style="color:#64748b;">(${formatBytes(file.size)})</span>`).fadeIn();
            $("#modalAlertBox").hide();
        } else {
            $("#selectedFileName").hide();
        }
    });

    // Handle drag & drop events on dropzone
    let dropzone = document.getElementById("dropzoneClickable");
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('dragover');
        }, false);
    });

    dropzone.addEventListener("drop", function(e) {
        let dt = e.dataTransfer;
        let files = dt.files;
        if (files && files.length > 0) {
            document.getElementById("requestFileInput").files = files;
            let file = files[0];
            $("#selectedFileName").html(`<i class="fa fa-file-text-o"></i> ${escapeHtml(file.name)} <span style="color:#64748b;">(${formatBytes(file.size)})</span>`).fadeIn();
            $("#modalAlertBox").hide();
        }
    });

    // Handle AJAX Form Submission with Upload Progress Bar & Resilient Diagnostics
    $("#uploadRequestForm").on("submit", function(e) {
        e.preventDefault();
        
        let fileInput = document.getElementById("requestFileInput");
        if (!fileInput.files || fileInput.files.length === 0) {
            showModalAlert("Please select a file to submit.", "danger");
            return;
        }

        let formData = new FormData(this);
        let submitBtn = $("#modalSubmitBtn");
        
        submitBtn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Uploading...');
        $("#progressBarCustom").show();
        $("#progressText").show();
        $("#modalAlertBox").hide();

        $.ajax({
            url: "submit_file_request.php",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            xhr: function() {
                let myXhr = $.ajaxSettings.xhr();
                if (myXhr.upload) {
                    myXhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            let percent = Math.round((e.loaded / e.total) * 100);
                            $("#progressBarFill").css("width", percent + "%");
                            $("#progressText").text(percent + "%");
                        }
                    }, false);
                }
                return myXhr;
            },
            success: function(response) {
                if (response.success) {
                    showModalAlert(response.message, "success");
                    setTimeout(function() {
                        $("#uploadRequestModal").modal("hide");
                        loadStudentFileRequests();
                    }, 1200);
                } else {
                    let errMsg = response.message || "Failed to submit file.";
                    showModalAlert(errMsg, "danger");
                    resetProgress();
                }
            },
            error: function(xhr, status, error) {
                let errMsg = "Upload failed. Please check your connection and try again.";
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                } else if (xhr.status === 413) {
                    errMsg = "The file size exceeds the maximum limit accepted by the server. Please upload a smaller file.";
                } else if (xhr.status === 401) {
                    errMsg = "Your session has expired. Please refresh the page and log in again.";
                } else if (xhr.responseText) {
                    try {
                        let parsed = JSON.parse(xhr.responseText);
                        if (parsed.message) errMsg = parsed.message;
                    } catch(e) {}
                }
                showModalAlert(errMsg, "danger");
                resetProgress();
            }
        });
    });

    function showModalAlert(msg, type) {
        let box = $("#modalAlertBox");
        box.removeClass("alert-danger alert-success")
           .css({
               "background": type === "success" ? "rgba(16, 185, 129, 0.12)" : "rgba(239, 68, 68, 0.12)",
               "color": type === "success" ? "#065f46" : "#991b1b",
               "border": "1px solid " + (type === "success" ? "rgba(16, 185, 129, 0.3)" : "rgba(239, 68, 68, 0.3)")
           })
           .html((type === "success" ? '<i class="fa fa-check-circle"></i> ' : '<i class="fa fa-exclamation-triangle"></i> ') + escapeHtml(msg))
           .fadeIn();
    }

    function resetProgress() {
        $("#modalSubmitBtn").prop("disabled", false).html('<i class="fa fa-upload"></i> Submit File');
        $("#progressBarCustom").hide();
        $("#progressBarFill").css("width", "0%");
        $("#progressText").hide().text("0%");
    }

    $("#uploadRequestModal").on("hidden.bs.modal", function() {
        resetProgress();
        $("#uploadRequestForm")[0].reset();
        $("#selectedFileName").hide();
        $("#modalAlertBox").hide();
    });
});

// Load pending/active/completed requests for the student
function loadStudentFileRequests(showSpinner = false) {
    let refreshIcon = $("#refreshIcon");
    if (showSpinner) {
        refreshIcon.addClass("fa-spin");
    }

    $.ajax({
        url: "get_student_file_requests.php?t=" + new Date().getTime(),
        type: "GET",
        dataType: "json",
        success: function(data) {
            refreshIcon.removeClass("fa-spin");
            allRequestsData = Array.isArray(data) ? data : [];
            updateStatsCounters();
            renderRequestsList();
        },
        error: function(xhr) {
            refreshIcon.removeClass("fa-spin");
            let errText = "Failed to load file requests. Please check your network connection.";
            if (xhr.status === 401) {
                errText = "Session expired. Please log in again.";
            }
            $("#fileRequestsList").html(`
                <div style="grid-column: 1/-1; text-align: center; padding: 45px 20px; background: #ffffff; border-radius: 12px; border: 1px dashed #cbd5e1;">
                    <i class="fa fa-exclamation-triangle fa-2x" style="color: var(--dlhs-danger); margin-bottom: 10px;"></i>
                    <h4 style="font-weight: 700; color: var(--dlhs-navy); margin: 0 0 6px 0;">Notice</h4>
                    <p style="color: #64748b; font-size: 13.5px; margin: 0;">${escapeHtml(errText)}</p>
                    <button class="btn btn-default btn-sm" onclick="loadStudentFileRequests(true)" style="margin-top: 14px; border-radius: 8px;">
                        <i class="fa fa-refresh"></i> Try Again
                    </button>
                </div>
            `);
        }
    });
}

function updateStatsCounters() {
    let total = allRequestsData.length;
    let submitted = 0;
    let overdue = 0;
    let pending = 0;
    let now = Date.now();

    allRequestsData.forEach(function(req) {
        if (req.submitted) {
            submitted++;
        } else {
            let isOverdue = false;
            if (req.dueDate) {
                let dueTime = new Date(req.dueDate.replace(/-/g, "/")).getTime();
                if (now > dueTime) {
                    isOverdue = true;
                }
            }
            if (isOverdue) {
                overdue++;
            } else {
                pending++;
            }
        }
    });

    $("#statAll").text(total);
    $("#statPending").text(pending);
    $("#statSubmitted").text(submitted);
    $("#statOverdue").text(overdue);
}

function applyTabFilter(filter) {
    currentFilter = filter;
    $(".filter-tab").removeClass("active");
    $(`.filter-tab[data-filter="${filter}"]`).addClass("active");

    $(".stat-card").removeClass("active");
    $(`.stat-card[data-filter="${filter}"]`).addClass("active");

    renderRequestsList();
}

function handleSearch() {
    currentSearch = $("#requestSearchInput").val().toLowerCase().trim();
    renderRequestsList();
}

function renderRequestsList() {
    let container = $("#fileRequestsList");
    let now = Date.now();

    let filtered = allRequestsData.filter(function(req) {
        let isOverdue = false;
        if (req.dueDate) {
            let dueTime = new Date(req.dueDate.replace(/-/g, "/")).getTime();
            if (now > dueTime) isOverdue = true;
        }

        // Tab filter
        if (currentFilter === 'submitted' && !req.submitted) return false;
        if (currentFilter === 'pending' && (req.submitted || isOverdue)) return false;
        if (currentFilter === 'overdue' && (req.submitted || !isOverdue)) return false;

        // Search filter
        if (currentSearch !== '') {
            let title = (req.title || '').toLowerCase();
            let teacher = (req.teacherName || '').toLowerCase();
            let subject = (req.subjectName || '').toLowerCase();
            let instructions = (req.instructions || '').toLowerCase();
            if (!title.includes(currentSearch) && !teacher.includes(currentSearch) && !subject.includes(currentSearch) && !instructions.includes(currentSearch)) {
                return false;
            }
        }

        return true;
    });

    if (filtered.length === 0) {
        let emptyMessage = "No file requests match your current filter.";
        if (allRequestsData.length === 0) {
            emptyMessage = "Your teachers haven't requested any file collections from your class at this time. Check back later!";
        }
        container.html(`
            <div style="grid-column: 1/-1; text-align: center; padding: 70px 20px; background: #ffffff; border-radius: 14px; border: 1px dashed #cbd5e1; box-shadow: var(--dlhs-card-shadow);">
                <i class="fa fa-inbox fa-3x" style="color: var(--dlhs-primary); opacity: 0.5; margin-bottom: 14px;"></i>
                <h4 style="font-weight: 700; color: var(--dlhs-navy); margin: 0 0 8px 0;">No File Requests Found</h4>
                <p style="color: #64748b; font-size: 13.5px; max-width: 440px; margin: 0 auto;">${escapeHtml(emptyMessage)}</p>
            </div>
        `);
        return;
    }

    let html = '';
    filtered.forEach(function(req) {
        let statusClass = 'pending';
        let statusBadge = '';
        let isOverdue = false;
        
        if (req.dueDate) {
            let dueTime = new Date(req.dueDate.replace(/-/g, "/")).getTime();
            if (now > dueTime) {
                isOverdue = true;
            }
        }

        if (req.submitted) {
            statusClass = 'submitted';
            statusBadge = '<span class="status-badge badge-submitted"><i class="fa fa-check-circle"></i> Submitted</span>';
        } else if (isOverdue) {
            statusClass = 'overdue';
            statusBadge = '<span class="status-badge badge-overdue"><i class="fa fa-clock-o"></i> Overdue</span>';
        } else {
            statusClass = 'pending';
            statusBadge = '<span class="status-badge badge-pending"><i class="fa fa-hourglass-half"></i> Pending</span>';
        }

        let dueText = req.dueDate ? formatDate(req.dueDate) : 'No due date';
        let allowedTypesClean = (req.allowedTypes || 'pdf,doc,docx,jpg,png,zip')
            .split(',')
            .map(t => t.trim().replace(/^\./, ''))
            .filter(t => t !== '')
            .join(', ');
        
        html += `
            <div class="student-request-card ${statusClass}">
                <div>
                    <div class="request-header">
                        <h4 class="request-title">${escapeHtml(req.title)}</h4>
                        ${statusBadge}
                    </div>
                    
                    <div class="teacher-badge">
                        <i class="fa fa-user"></i> ${escapeHtml(req.teacherName)}
                    </div>

                    ${req.instructions ? `
                        <div class="instructions-text">${escapeHtml(req.instructions)}</div>
                    ` : ''}

                    <div class="meta-list">
                        <div class="meta-item">
                            <i class="fa fa-book"></i>
                            <span><strong>Subject:</strong> ${escapeHtml(req.subjectName)}</span>
                        </div>
                        <div class="meta-item">
                            <i class="fa fa-calendar"></i>
                            <span style="${(!req.submitted && isOverdue) ? 'color: var(--dlhs-danger); font-weight: 600;' : ''}">
                                <strong>Due Date:</strong> ${dueText}
                            </span>
                        </div>
                        <div class="meta-item">
                            <i class="fa fa-file-text-o"></i>
                            <span><strong>Allowed:</strong> <strong style="text-transform: uppercase;">${escapeHtml(allowedTypesClean)}</strong> (Max ${req.maxFileSizeMB} MB)</span>
                        </div>
                    </div>
                </div>

                <div>
                    ${req.submitted ? `
                        <div class="submitted-file-info">
                            <p style="font-weight: 700; color: var(--dlhs-success); font-size: 13px;">
                                <i class="fa fa-check-circle"></i> File Uploaded ${req.submission.isLate ? '<span style="color:var(--dlhs-danger);font-size:11px;">(Late)</span>' : ''}
                            </p>
                            <p style="font-size: 12px; color: #475569;"><strong>File:</strong> ${escapeHtml(req.submission.originalName)}</p>
                            <p style="font-size: 11.5px; color: #64748b;"><strong>Size:</strong> ${formatBytes(req.submission.fileSize)} | <strong>On:</strong> ${formatDate(req.submission.submittedAt)}</p>
                        </div>
                        <a href="../../download_file.php?submissionId=${req.submission.submissionId}&type=file_request" class="btn-action btn-action-success">
                            <i class="fa fa-download"></i> Download Submitted File
                        </a>
                    ` : `
                        <button type="button" onclick="openUploadModal(${req.requestId}, '${escapeJs(req.title)}', '${escapeJs(req.teacherName)}', '${escapeJs(allowedTypesClean)}', ${req.maxFileSizeMB})" class="btn-action ${isOverdue ? 'btn-action-danger' : 'btn-action-primary'}">
                            <i class="fa fa-upload"></i> ${isOverdue ? 'Submit Late File' : 'Upload File'}
                        </button>
                    `}
                </div>
            </div>
        `;
    });

    container.html(html);
}

function openUploadModal(requestId, title, teacherName, allowedTypes, maxSizeMB) {
    $("#modalRequestId").val(requestId);
    $("#modalRequestTitle").text(title);
    $("#modalTeacherName").text("Requested by: " + teacherName);
    $("#modalAllowedTypes").text(allowedTypes);
    $("#modalMaxSize").text(maxSizeMB + " MB");
    $("#modalAlertBox").hide();
    
    // Set file input accept attribute dynamically
    let extensions = allowedTypes.split(',').map(ext => '.' + ext.trim().replace(/^\./, '')).join(',');
    $("#requestFileInput").attr("accept", extensions);

    $("#uploadRequestModal").modal("show");
}

// Helpers
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
    hours = hours ? hours : 12;
    minutes = minutes < 10 ? '0' + minutes : minutes;
    return `${month} ${day}, ${year} ${hours}:${minutes} ${ampm}`;
}

function formatBytes(bytes, decimals = 1) {
    if (!bytes || isNaN(bytes)) return '—';
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

function escapeJs(text) {
    if (!text) return '';
    return text
        .toString()
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/"/g, '\\"')
        .replace(/\n/g, '\\n')
        .replace(/\r/g, '');
}
</script>
</body>
</html>
