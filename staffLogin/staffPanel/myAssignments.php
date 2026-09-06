<!DOCTYPE html>
<html lang="en">
<?php
session_start();
	require_once 'userExpiredSession.php';
include "../../db_connection/dlhs_db_connection.php";

if (!isset($_SESSION['staffLoggedIn'])) {
    header("Location: ../staffLogout.php");
    exit();
}

$staffId = $_SESSION['staffId'];
$staffName = $_SESSION['staffName'];
?>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="My Teaching Assignments">
    <meta name="author" content="DLHS">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <title>My Teaching Assignments | DLHS</title>
    
    <!-- Bootstrap CSS -->    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- bootstrap theme -->
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <!--external css-->
    <!-- font icon -->
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
	<link href="fontAwesome/css/fontawesome.css" rel="stylesheet">
	<link href="fontAwesome/css/brands.css" rel="stylesheet">
	<link href="fontAwesome/css/solid.css" rel="stylesheet">
    
    <!-- Custom styles -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #1d5fbf 0%, #0f9f8f 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --card-shadow: 0 8px 32px rgba(31, 38, 135, 0.07);
        }

        .assignment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .assignment-card {
            background: var(--glass-bg);
            border-radius: 16px;
            padding: 0;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: var(--card-shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }
        
        .assignment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(31, 38, 135, 0.12);
        }
        
        .assignment-header {
            background: linear-gradient(135deg, #051937 0%, #004d7a 100%);
            padding: 26px 20px;
            color: #ffffff !important;
            border-bottom: 2px solid rgba(255, 255, 255, 0.15);
        }
        
        .subject-title {
            font-size: 20px;
            font-weight: 800;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff !important;
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.2), 0 2px 4px rgba(0, 0, 0, 0.5);
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .assignment-body {
            padding: 20px;
            flex: 1;
        }

        .class-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f4f8;
        }

        .class-item:last-child {
            border-bottom: none;
        }

        .class-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 15px;
        }

        .student-count-pill {
            background: #eef2f7;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            color: #526176;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .student-count-pill i {
            color: #1d5fbf;
        }
        
        .no-assignments {
            text-align: center;
            padding: 80px 20px;
            background: #fff;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
        }
        
        .no-assignments i {
            font-size: 80px;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
        
        .loading-spinner {
            text-align: center;
            padding: 60px;
        }
        
        .loading-spinner i {
            font-size: 50px;
            color: #1d5fbf;
            animation: spin 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
<section id="container">
    <!--Including the header-->
    <?php include 'header.php'; ?>
    
    <!--Including the sidebar-->
    <?php include 'sideBar.php'; ?>
    
    <!--main content start-->
    <section id="main-content">
        <section class="wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h3 class="page-header"><i class="fa fa-chalkboard-teacher"></i> My Teaching Assignments</h3>
                    <ol class="breadcrumb">
                        <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                        <li><i class="fa fa-chalkboard-teacher"></i>My Assignments</li>
                        <a href="#" style="color:#0acca2; padding-left:10px;"><i class="fa fa-calendar-o"></i> <?php echo date('d')." ".date('M').", ".date("Y"); ?></a>
                    </ol>
                </div>
            </div>
            
            <div id="loadingSpinner" class="loading-spinner">
                <i class="fa fa-circle-o-notch"></i>
                <p style="margin-top: 15px; color: #526176;">Syncing your teaching records...</p>
            </div>
            
            <div id="assignmentsContainer" class="assignment-grid" style="display:none;">
                <!-- Assignments will be loaded here via JavaScript -->
            </div>
            
            <div id="noAssignments" class="no-assignments" style="display:none;">
                <i class="fa fa-folder-open"></i>
                <h3>No Assignments Found</h3>
                <p>Your teaching schedule hasn't been assigned for this term yet.</p>
                <p style="color: #95a5a6; font-size: 13px;">Please coordinate with the academic office if this seems incorrect.</p>
            </div>
        </section>
    </section>
    
    <?php include 'footer.php'; ?>
</section>

<!-- JavaScript -->
<script src="../../libs/jquery.min.js"></script>
<script src="jQuery3.3.1.js"></script>
<script src="js/bootstrap.min.js"></script>
<script class="include" type="text/javascript" src="js/jquery.dcjqaccordion.2.7.js"></script>
<script src="js/jquery.scrollTo.min.js"></script>
<script src="js/jquery.nicescroll.js" type="text/javascript"></script>
<script src="js/common-scripts.js"></script>

<script>
$(document).ready(function(){
    loadAssignments();
});

function loadAssignments() {
    $.ajax({
        url: 'get_my_assigned_subjects.php',
        type: 'GET',
        dataType: 'json',
        success: function(assignments) {
            $('#loadingSpinner').hide();
            
            if(!assignments || assignments.length === 0) {
                $('#noAssignments').show();
                return;
            }
            
            // Group assignments by subject
            let groupedAssignments = {};
            assignments.forEach(function(assignment) {
                if(!groupedAssignments[assignment.subjectId]) {
                    groupedAssignments[assignment.subjectId] = {
                        subjectName: assignment.subjectName,
                        classes: []
                    };
                }
                groupedAssignments[assignment.subjectId].classes.push(assignment);
            });
            
            // Build HTML for assignments
            let html = '';
            Object.keys(groupedAssignments).forEach(function(subjectId) {
                let subject = groupedAssignments[subjectId];
                let subjectAssignmentIds = subject.classes.map(c => c.assignmentId);
                
                html += '<div class="assignment-card">';
                html += '  <div class="assignment-header">';
                html += '    <h4 class="subject-title"><i class="fa fa-book"></i> ' + subject.subjectName + '</h4>';
                html += '    <div style="font-size: 11px; opacity: 0.8; margin-top: 5px; font-weight: 600;">' + subject.classes.length + ' ASSIGNED CLASS' + (subject.classes.length !== 1 ? 'ES' : '') + '</div>';
                html += '  </div>';
                html += '  <div class="assignment-body">';
                
                // List classes for this subject
                subject.classes.forEach(function(cls) {
                    html += '  <div class="class-item">';
                    html += '    <div class="class-name">' + cls.className + '</div>';
                    html += '    <div class="student-count-pill" id="studentCount_' + cls.assignmentId + '">';
                    html += '      <i class="fa fa-spinner fa-spin"></i> Syncing...';
                    html += '    </div>';
                    html += '  </div>';
                    
                    // Load student count for this class
                    loadStudentCount(cls.classId, cls.assignmentId);
                });
                
                html += '  </div>';
                html += '</div>';
            });
            
            $('#assignmentsContainer').html(html).fadeIn();
        },
        error: function(xhr, status, error) {
            $('#loadingSpinner').hide();
            $('#assignmentsContainer').html(
                '<div class="alert alert-danger" style="grid-column: 1/-1;">' +
                '<i class="fa fa-exclamation-triangle"></i> ' +
                'Unable to retrieve assignments. Please try again later.' +
                '</div>'
            ).fadeIn();
        }
    });
}

function loadStudentCount(classId, assignmentId) {
    $.ajax({
        url: 'get_class_student_count.php',
        type: 'GET',
        data: { classId: classId },
        dataType: 'json',
        success: function(response) {
            const count = response.count || 0;
            $('#studentCount_' + assignmentId).html(
                '<i class="fa fa-users"></i> ' + count + ' ' + (count === 1 ? 'student' : 'students')
            );
        },
        error: function() {
            $('#studentCount_' + assignmentId).html(
                '<i class="fa fa-exclamation-circle"></i> Error'
            );
        }
    });
}
</script>

</body>
</html>
