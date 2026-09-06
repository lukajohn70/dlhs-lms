<?php
session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/file_request_helper.php";

// Check if user is logged in
if (!isset($_SESSION['studentLoggedIn']) || $_SESSION['studentLoggedIn'] !== "yes") {
    header("location:../");
    exit();
}

dlhsEnsureFileRequestTablesExist($connection);

$studentId = $_SESSION['studentId'];
$studentName = $_SESSION['studentName'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="File Submission Requests | DLHS">
    <meta name="author" content="DLHS">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <title>File Requests | DLHS Student Portal</title>

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
            --primary: #1d5fbf;
            --primary-light: #3b7ddd;
            --primary-dark: #123e7f;
            --accent: #0f9f8f;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --bg-light: #f4f7f9;
            --card-shadow: 0 8px 32px rgba(31, 38, 135, 0.05);
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

        /* Requests Grid Layout */
        .requests-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 15px;
        }

        .student-request-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.18);
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        .student-request-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(31, 38, 135, 0.1);
        }

        .student-request-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-light);
        }

        .student-request-card.submitted::before {
            background: var(--success);
        }

        .student-request-card.overdue::before {
            background: var(--danger);
        }

        .request-header {
            margin-bottom: 12px;
        }

        .request-title {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin: 0 0 10px 0;
            line-height: 1.4;
        }

        .teacher-badge {
            font-size: 12px;
            background: rgba(29, 95, 191, 0.08);
            color: var(--primary-dark);
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 15px;
        }

        .instructions-text {
            background: #f8fafc;
            border-left: 3px solid #cbd5e1;
            padding: 10px 12px;
            border-radius: 4px;
            font-size: 13px;
            color: #555;
            margin-bottom: 18px;
            font-style: italic;
            white-space: pre-wrap;
        }

        .meta-list {
            margin-bottom: 20px;
        }

        .meta-item {
            font-size: 13.5px;
            color: #64748b;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .meta-item i {
            color: #94a3b8;
            width: 16px;
            text-align: center;
        }

        .meta-item strong {
            color: #475569;
        }

        /* Status badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-pending {
            background: rgba(255, 193, 7, 0.12);
            color: #b28500;
        }

        .badge-submitted {
            background: rgba(40, 167, 69, 0.12);
            color: var(--success);
        }

        .badge-overdue {
            background: rgba(220, 53, 69, 0.12);
            color: var(--danger);
        }

        .submitted-file-info {
            background: rgba(40, 167, 69, 0.05);
            border: 1px dashed rgba(40, 167, 69, 0.25);
            border-radius: 8px;
            padding: 12px;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .submitted-file-info p {
            margin: 4px 0;
            color: #444;
        }

        /* Buttons styling */
        .btn-action {
            width: 100%;
            border-radius: 8px;
            padding: 11px 16px;
            font-weight: 700;
            font-size: 13.5px;
            border: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-action-primary {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(29, 95, 191, 0.2);
        }

        .btn-action-primary:hover {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(29, 95, 191, 0.3);
        }

        .btn-action-success {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        .btn-action-success:hover {
            background: var(--success);
            color: white;
        }

        .btn-action-danger {
            background: linear-gradient(135deg, #f87171 0%, var(--danger) 100%);
            color: white;
        }

        .btn-action-danger:hover {
            background: linear-gradient(135deg, var(--danger) 0%, #b91c1c 100%);
            color: white;
            transform: translateY(-1px);
        }

        /* Progress Bar */
        .progress-bar-custom {
            height: 12px;
            border-radius: 6px;
            background-color: #e2e8f0;
            overflow: hidden;
            margin-top: 15px;
            display: none;
        }

        .progress-bar-custom-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
            transition: width 0.1s linear;
        }

        /* Centered Premium Modal Override */
        .modal {
            text-align: center !important;
            padding: 0 !important;
            position: fixed !important;
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            left: 0 !important;
            display: none;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            z-index: 2200 !important;
        }
        
        .modal:before {
            content: '' !important;
            display: inline-block !important;
            height: 100% !important;
            vertical-align: middle !important;
            margin-right: -4px !important;
        }
        
        .modal-dialog {
            display: inline-block !important;
            text-align: left !important;
            vertical-align: middle !important;
            width: 100% !important;
            max-width: 550px !important;
            margin: 30px auto !important;
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
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%) !important;
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

        .dropzone-area {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: var(--transition);
        }

        .dropzone-area:hover {
            border-color: var(--primary-light);
            background: rgba(29, 95, 191, 0.03);
        }

        .dropzone-area i {
            font-size: 38px;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        .dropzone-area p {
            margin: 0;
            font-size: 13.5px;
            color: #475569;
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
            <!-- Header title -->
            <div class="row">
                <div class="col-lg-12">
                    <h3 class="page-header" style="font-weight: 700; color: #2c3e50;">
                        <i class="fa fa-inbox"></i> File Submission Requests
                    </h3>
                    <ol class="breadcrumb" style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                        <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                        <li><i class="fa fa-inbox"></i>File Requests</li>
                    </ol>
                </div>
            </div>

            <!-- Dashboard view wrapper -->
            <div id="fileRequestsList" class="requests-container">
                <!-- Loaded dynamically via AJAX -->
                <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #777;">
                    <i class="fa fa-spinner fa-spin fa-3x" style="color: var(--primary-light);"></i>
                    <h4 style="margin-top: 15px; font-weight: 500;">Loading pending file requests...</h4>
                </div>
            </div>

        </section>
    </section>
</section>

<!-- ------------------- UPLOAD FILE MODAL ------------------- -->
<div class="modal fade" id="uploadRequestModal" tabindex="-1" role="dialog" aria-labelledby="uploadRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="uploadRequestModalLabel" style="font-weight: 700; margin: 0;">
                    <i class="fa fa-cloud-upload"></i> Upload File
                </h4>
            </div>
            <form id="uploadRequestForm" enctype="multipart/form-data">
                <input type="hidden" name="requestId" id="modalRequestId">
                <div class="modal-body" style="padding: 24px;">
                    
                    <div style="margin-bottom: 20px;">
                        <h4 style="font-weight: 700; color: #2c3e50; margin: 0 0 6px 0;" id="modalRequestTitle">Request Title</h4>
                        <p style="color: #64748b; font-size: 13px;" id="modalTeacherName">From: Teacher</p>
                    </div>

                    <!-- Allowed types/size notice -->
                    <div style="background: #f1f5f9; border-radius: 8px; padding: 12px 16px; font-size: 12.5px; color: #475569; margin-bottom: 20px;">
                        <i class="fa fa-info-circle" style="color: var(--primary-light);"></i> 
                        Allowed Formats: <strong style="text-transform: uppercase;" id="modalAllowedTypes">PDF, DOCX</strong><br>
                        Maximum File Size: <strong id="modalMaxSize">10 MB</strong>
                    </div>

                    <!-- Elegant File Selector Dropzone -->
                    <div class="dropzone-area" id="dropzoneClickable">
                        <i class="fa fa-cloud-upload"></i>
                        <p><strong>Click to browse files</strong> or drag your file here</p>
                        <span id="selectedFileName" style="display: none; margin-top: 10px; font-weight: 600; color: var(--primary-dark); font-size: 13.5px;"></span>
                    </div>
                    <input type="file" name="requestFile" id="requestFileInput" style="display: none;" required>

                    <!-- Progress bar -->
                    <div class="progress-bar-custom" id="progressBarCustom">
                        <div class="progress-bar-custom-fill" id="progressBarFill"></div>
                    </div>
                    <div id="progressText" style="display: none; font-size: 12px; font-weight: 600; text-align: right; margin-top: 6px; color: #555;">0%</div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="modalSubmitBtn" style="border-radius: 8px; font-weight: 600; padding: 8px 20px;">
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
$(document).ready(function() {
    loadStudentFileRequests();

    // Trigger file picker
    $("#dropzoneClickable").on("click", function() {
        $("#requestFileInput").click();
    });

    // File selection display
    $("#requestFileInput").on("change", function(e) {
        let file = e.target.files[0];
        if (file) {
            $("#selectedFileName").html(`<i class="fa fa-file-text-o"></i> ${file.name} (${formatBytes(file.size)})`).fadeIn();
        } else {
            $("#selectedFileName").hide();
        }
    });

    // Handle drag events on dropzone
    let dropzone = document.getElementById("dropzoneClickable");
    dropzone.addEventListener("dragover", function(e) {
        e.preventDefault();
        dropzone.style.borderColor = "var(--primary-light)";
        dropzone.style.background = "rgba(29, 95, 191, 0.05)";
    });

    dropzone.addEventListener("dragleave", function() {
        dropzone.style.borderColor = "#cbd5e1";
        dropzone.style.background = "#f8fafc";
    });

    dropzone.addEventListener("drop", function(e) {
        e.preventDefault();
        dropzone.style.borderColor = "#cbd5e1";
        dropzone.style.background = "#f8fafc";
        
        let file = e.dataTransfer.files[0];
        if (file) {
            document.getElementById("requestFileInput").files = e.dataTransfer.files;
            $("#selectedFileName").html(`<i class="fa fa-file-text-o"></i> ${file.name} (${formatBytes(file.size)})`).fadeIn();
        }
    });

    // Handle AJAX Form Submission with Upload Progress Bar
    $("#uploadRequestForm").on("submit", function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        let submitBtn = $("#modalSubmitBtn");
        
        submitBtn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i> Uploading...');
        $("#progressBarCustom").fadeIn();
        $("#progressText").fadeIn();

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
                    alert(response.message);
                    $("#uploadRequestModal").modal("hide");
                    $("#uploadRequestForm")[0].reset();
                    $("#selectedFileName").hide();
                    loadStudentFileRequests();
                } else {
                    alert("Error: " + response.message);
                    resetProgress();
                }
            },
            error: function() {
                alert("Upload failed. Connection error.");
                resetProgress();
            }
        });
    });

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
    });
});

// Load pending/active/completed requests for the student
function loadStudentFileRequests() {
    $.ajax({
        url: "get_student_file_requests.php",
        type: "GET",
        dataType: "json",
        success: function(data) {
            let container = $("#fileRequestsList");
            if (!data || data.length === 0) {
                container.html(`
                    <div style="grid-column: 1/-1; text-align: center; padding: 80px 20px; background: white; border-radius: 16px; border: 1px dashed #cbd5e1; box-shadow: var(--card-shadow);">
                        <i class="fa fa-inbox fa-4x" style="color: var(--primary-light); opacity: 0.4; margin-bottom: 15px;"></i>
                        <h4 style="font-weight: 700; color: #2c3e50;">No File Requests Active</h4>
                        <p style="color: #64748b; font-size: 14px; max-width: 400px; margin: 0 auto;">
                            Your teachers haven't requested any file collections from your class at this time. Check back later!
                        </p>
                    </div>
                `);
                return;
            }

            let html = '';
            data.forEach(function(req) {
                let statusClass = 'pending';
                let statusBadge = '';
                let isOverdue = false;
                
                if (req.dueDate) {
                    let dueTime = new Date(req.dueDate.replace(/-/g, "/")).getTime();
                    if (Date.now() > dueTime) {
                        isOverdue = true;
                    }
                }

                if (req.submitted) {
                    statusClass = 'submitted';
                    statusBadge = '<span class="status-badge badge-submitted"><i class="fa fa-check-circle"></i> Submitted</span>';
                } else if (isOverdue) {
                    statusClass = 'overdue';
                    statusBadge = '<span class="status-badge badge-overdue"><i class="fa fa-times-circle"></i> Overdue</span>';
                } else {
                    statusClass = 'pending';
                    statusBadge = '<span class="status-badge badge-pending"><i class="fa fa-hourglass-half"></i> Pending Upload</span>';
                }

                let dueText = req.dueDate ? formatDate(req.dueDate) : 'No due date';
                
                html += `
                    <div class="student-request-card ${statusClass}">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 10px;">
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
                                    <span style="${(!req.submitted && isOverdue) ? 'color: var(--danger); font-weight: 600;' : ''}">
                                        <strong>Due Date:</strong> ${dueText}
                                    </span>
                                </div>
                                <div class="meta-item">
                                    <i class="fa fa-file-text-o"></i>
                                    <span><strong>Allowed extensions:</strong> <strong style="text-transform: uppercase;">${req.allowedTypes}</strong></span>
                                </div>
                            </div>
                        </div>

                        <div>
                            ${req.submitted ? `
                                <div class="submitted-file-info">
                                    <p style="font-weight: 700; color: var(--success);"><i class="fa fa-check-circle"></i> File Uploaded</p>
                                    <p style="font-size: 12px; color: #555; word-break: break-all;"><strong>File:</strong> ${escapeHtml(req.submission.originalName)}</p>
                                    <p style="font-size: 11.5px; color: #777;"><strong>Size:</strong> ${formatBytes(req.submission.fileSize)} | <strong>On:</strong> ${formatDate(req.submission.submittedAt)}</p>
                                </div>
                                <a href="../../download_file.php?submissionId=${req.submission.submissionId}&type=file_request" class="btn-action btn-action-success">
                                    <i class="fa fa-download"></i> Download Submitted File
                                </a>
                            ` : `
                                <button onclick="openUploadModal(${req.requestId}, '${escapeHtml(req.title)}', '${escapeHtml(req.teacherName)}', '${req.allowedTypes}', ${req.maxFileSizeMB})" class="btn-action ${isOverdue ? 'btn-action-danger' : 'btn-action-primary'}">
                                    <i class="fa fa-upload"></i> ${isOverdue ? 'Submit Late File' : 'Upload File'}
                                </button>
                            `}
                        </div>
                    </div>
                `;
            });
            container.html(html);
        },
        error: function() {
            $("#fileRequestsList").html(`
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: red;">
                    <i class="fa fa-exclamation-triangle fa-2x"></i><br>
                    <strong>Failed to load file requests. Please check your connection.</strong>
                </div>
            `);
        }
    });
}

function openUploadModal(requestId, title, teacherName, allowedTypes, maxSizeMB) {
    $("#modalRequestId").val(requestId);
    $("#modalRequestTitle").text(title);
    $("#modalTeacherName").text("Requested by: " + teacherName);
    $("#modalAllowedTypes").text(allowedTypes);
    $("#modalMaxSize").text(maxSizeMB + " MB");
    
    // Set file input accept attribute dynamically to make browser selection clean
    let extensions = allowedTypes.split(',').map(ext => '.' + ext.trim()).join(',');
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
