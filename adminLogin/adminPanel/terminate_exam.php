<?php
session_start();
require_once 'userExpiredSession.php';
require_once '../../db_connection/dlhs_db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['adminId'])) {
    header("Location: ../index.php");
    exit();
}

$pageTitle = "Terminate Student Exam";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | DLHS Admin</title>
    
    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    <link href="sideBar_style.css" rel="stylesheet" />
    
    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            margin: -20px -20px 30px -20px;
            border-radius: 10px;
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 28px;
        }
        
        .page-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
        }
        
        .stat-card.warning {
            border-left-color: #f39c12;
        }
        
        .stat-card.danger {
            border-left-color: #e74c3c;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .test-taker-card {
            background: white;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .test-taker-info {
            flex: 1;
        }
        
        .test-taker-name {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .test-taker-details {
            color: #6c757d;
            font-size: 14px;
        }
        
        .test-taker-details i {
            margin-right: 5px;
        }
        
        .violation-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .violation-badge.low {
            background: #d1f2eb;
            color: #0e6655;
        }
        
        .violation-badge.medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .violation-badge.high {
            background: #f8d7da;
            color: #721c24;
        }
        
        .terminate-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .terminate-btn:hover {
            background: #c0392b;
            transform: scale(1.05);
        }
        
        .terminate-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }
        
        .view-violations-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        
        .view-violations-btn:hover {
            background: #2980b9;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .no-data i {
            font-size: 64px;
            color: #cbd5e0;
            margin-bottom: 20px;
        }
        
        .alert-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-box i {
            color: #856404;
            margin-right: 10px;
        }
        
        .refresh-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        
        .refresh-btn:hover {
            background: #059669;
        }
        
        .auto-refresh-indicator {
            display: inline-block;
            margin-left: 10px;
            color: #10b981;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <section id="container">
        <?php include 'header.php'; ?>
        <?php include 'sideBar.php'; ?>
        
        <section id="main-content">
            <section class="wrapper">
                <div class="page-header">
                    <h1><i class="fa fa-stop-circle"></i> <?php echo $pageTitle; ?></h1>
                    <p>Monitor and terminate student exams when security violations occur</p>
                </div>
                
                <div class="alert-box">
                    <i class="fa fa-info-circle"></i>
                    <strong>Note:</strong> Auto-submission has been disabled. You can manually terminate any student's exam if you observe suspicious activity or security violations.
                </div>
                
                <button class="refresh-btn" onclick="loadActiveTests()">
                    <i class="fa fa-refresh"></i> Refresh Now
                </button>
                <span class="auto-refresh-indicator">
                    <i class="fa fa-circle" style="animation: pulse 2s infinite;"></i> Auto-refreshing every 10 seconds
                </span>
                
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-label">Active Test Takers</div>
                        <div class="stat-value" id="activeCount">0</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-label">With Violations</div>
                        <div class="stat-value" id="violationCount">0</div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-label">High Risk</div>
                        <div class="stat-value" id="highRiskCount">0</div>
                    </div>
                </div>
                
                <div id="activeTestsList">
                    <!-- Loaded via AJAX -->
                </div>
            </section>
        </section>
    </section>
    
    <!-- jQuery -->
    <script src="js/jquery.js"></script>
    <!-- Bootstrap -->
    <script src="js/bootstrap.min.js"></script>
    
    <script>
        function loadActiveTests() {
            $.ajax({
                url: 'get_active_test_takers_detailed.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    // Update stats
                    $('#activeCount').text(data.total);
                    $('#violationCount').text(data.withViolations);
                    $('#highRiskCount').text(data.highRisk);
                    
                    // Display test takers
                    if (data.testTakers.length === 0) {
                        $('#activeTestsList').html(`
                            <div class="no-data">
                                <i class="fa fa-check-circle"></i>
                                <h3>No Active Test Takers</h3>
                                <p>All students have completed their exams or no exams are currently in progress.</p>
                            </div>
                        `);
                    } else {
                        let html = '';
                        data.testTakers.forEach(function(taker) {
                            let violationClass = 'low';
                            if (taker.violationCount >= 5) {
                                violationClass = 'high';
                            } else if (taker.violationCount >= 3) {
                                violationClass = 'medium';
                            }
                            
                            html += `
                                <div class="test-taker-card" id="taker-${taker.studentId}-${taker.testId}">
                                    <div class="test-taker-info">
                                        <div class="test-taker-name">
                                            ${taker.studentName}
                                            <span class="violation-badge ${violationClass}">
                                                ${taker.violationCount} Violations
                                            </span>
                                        </div>
                                        <div class="test-taker-details">
                                            <i class="fa fa-file-text"></i> ${taker.testName}
                                            &nbsp;|&nbsp;
                                            <i class="fa fa-clock-o"></i> ${taker.remainingTime} left
                                            &nbsp;|&nbsp;
                                            <i class="fa fa-check-square"></i> ${taker.questionsAnswered}/${taker.totalQuestions} answered
                                            &nbsp;|&nbsp;
                                            <i class="fa fa-map-marker"></i> ${taker.ipAddress}
                                        </div>
                                    </div>
                                    <div>
                                        <button class="view-violations-btn" onclick="viewViolations(${taker.studentId}, '${taker.studentName}')">
                                            <i class="fa fa-eye"></i> View Violations
                                        </button>
                                        <button class="terminate-btn" onclick="terminateExam(${taker.studentId}, ${taker.testId}, '${taker.studentName}', '${taker.testName}')">
                                            <i class="fa fa-stop-circle"></i> Terminate Exam
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                        $('#activeTestsList').html(html);
                    }
                },
                error: function() {
                    $('#activeTestsList').html(`
                        <div class="no-data">
                            <i class="fa fa-exclamation-triangle"></i>
                            <h3>Error Loading Data</h3>
                            <p>Could not retrieve active test takers. Please refresh the page.</p>
                        </div>
                    `);
                }
            });
        }
        
        function terminateExam(studentId, testId, studentName, testName) {
            const reason = prompt(`You are about to terminate the exam for:\n\n` +
                                  `Student: ${studentName}\n` +
                                  `Test: ${testName}\n\n` +
                                  `Please enter the reason for termination:`);
            
            if (reason === null || reason.trim() === '') {
                alert('Termination cancelled. Reason is required.');
                return;
            }
            
            if (!confirm(`Confirm termination?\n\nThis will immediately submit ${studentName}'s exam and they will not be able to continue.`)) {
                return;
            }
            
            // Disable button
            $(`#taker-${studentId}-${testId} .terminate-btn`).prop('disabled', true).text('Terminating...');
            
            $.ajax({
                url: 'terminate_student_exam.php',
                type: 'POST',
                data: {
                    studentId: studentId,
                    testId: testId,
                    reason: reason
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(`Exam terminated successfully!\n\n${response.message}`);
                        loadActiveTests(); // Refresh list
                    } else {
                        alert('Error: ' + response.message);
                        $(`#taker-${studentId}-${testId} .terminate-btn`).prop('disabled', false).html('<i class="fa fa-stop-circle"></i> Terminate Exam');
                    }
                },
                error: function() {
                    alert('Network error. Please try again.');
                    $(`#taker-${studentId}-${testId} .terminate-btn`).prop('disabled', false).html('<i class="fa fa-stop-circle"></i> Terminate Exam');
                }
            });
        }
        
        function viewViolations(studentId, studentName) {
            // Redirect to security violations page filtered by student
            window.open('security_violations.php?studentId=' + studentId, '_blank');
        }
        
        // Auto-refresh every 10 seconds
        $(document).ready(function() {
            loadActiveTests();
            setInterval(loadActiveTests, 10000);
        });
        
        // Pulse animation for auto-refresh indicator
        $('<style>@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }</style>').appendTo('head');
    </script>
</body>
</html>



