<?php
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['staffLoggedIn'])) { header('location:../index.php'); exit; }
require_once '../../db_connection/dlhs_db_connection.php';
$staffId = $_SESSION['staffId'];

// Fetch form teacher assignment
$assignedYearGroupId = null;
$assignedClassId = null;
$stmt = $connection->prepare("SELECT yearGroupId, classId FROM form_teacher_assignment WHERE teacherId = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $staffId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $assignedYearGroupId = intval($row['yearGroupId']);
        $assignedClassId = intval($row['classId']);
    }
    $stmt->close();
}

// RESTRICT ACCESS TO FORM TEACHERS ONLY
if ($assignedClassId === null) {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Access Denied | DLHS</title>
        <link href='css/bootstrap.min.css' rel='stylesheet'>
        <link href='css/style.css' rel='stylesheet'>
        <style>
            body { background: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
            .denied-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); text-align: center; max-width: 500px; width: 90%; }
            .denied-icon { font-size: 64px; color: #d9534f; margin-bottom: 20px; }
            h2 { color: #003366; margin-bottom: 15px; font-weight: 700; }
            p { color: #666; font-size: 16px; line-height: 1.6; margin-bottom: 25px; }
            .btn-back { background: linear-gradient(135deg, #003366, #005599); color: #ffd700; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.2s; }
            .btn-back:hover { filter: brightness(115%); color: #ffd700; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class='denied-card'>
            <div class='denied-icon'>⚠️</div>
            <h2>Access Denied</h2>
            <p>This page is restricted to Form Teachers only. If you believe this is an error, please contact the IT Administrator.</p>
            <a href='index.php' class='btn-back'>Back to Dashboard</a>
        </div>
    </body>
    </html>";
    exit;
}

// Fetch year groups
$yearGroups = $connection->query("SELECT * FROM yeargroup WHERE yearGroupId NOT IN (34,37) ORDER BY yearGroupId");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Behavior & Psychomotor Entry | DLHS</title>
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="fontAwesome/css/fontawesome.css" rel="stylesheet">
    <link href="fontAwesome/css/solid.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet">
    <script src="../../libs/jquery.min.js"></script>
    <style>
        body { background:#f4f7f6; overflow-x:hidden; }
        .page-card { background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.06); padding:24px; margin-bottom:24px; }
        .filter-bar { display:flex; flex-wrap:wrap; gap:14px; align-items:flex-end; }
        .filter-bar .form-group { margin:0; }
        .filter-bar label { font-weight:700; font-size:12px; color:#636e72; text-transform:uppercase; letter-spacing:.05em; }
        .filter-bar select, .filter-bar input { border-radius:8px; border:1px solid #dfe6e9; padding:7px 12px; font-size:13px; }
        .btn-dlhs { background:linear-gradient(135deg,#003366,#005599); color:#ffd700; border:none; border-radius:8px; font-weight:700; padding:9px 22px; font-size:13px; transition:all .2s; }
        .btn-dlhs:hover { filter:brightness(115%); color:#ffd700; }
        
        /* Master-Detail Layout */
        .workspace-layout { display: flex; gap: 24px; margin-top: 10px; min-height: 600px; }
        .student-sidebar { width: 30%; min-width: 280px; max-width: 360px; background: #fff; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.04); padding: 20px; height: fit-content; }
        .student-details { flex-grow: 1; background: #fff; border-radius: 14px; box-shadow: 0 4px 20px rgba(0,0,0,.04); padding: 24px; display: none; }
        
        .student-list-title { font-size: 14px; font-weight: 700; color: #003366; text-transform: uppercase; margin-bottom: 12px; border-bottom: 2px solid #f1f1f1; padding-bottom: 8px; }
        .student-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border-radius: 10px; cursor: pointer; margin-bottom: 8px; transition: all 0.2s; border: 1px solid #f1f1f1; }
        .student-item:hover { background: #f0f7ff; border-color: #cbdcf7; }
        .student-item.active { background: #003366; color: #ffd700; border-color: #003366; }
        .student-item.active .student-adm { color: #ffd700; opacity: 0.8; }
        .student-item.active .status-badge { background: rgba(255,255,255,0.2); color: #fff; }
        
        .student-info { display: flex; flex-direction: column; }
        .student-name { font-weight: 600; font-size: 13px; }
        .student-adm { font-size: 11px; color: #888; margin-top: 2px; }
        .status-badge { font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 12px; }
        .status-entered { background: #d1fae5; color: #065f46; }
        .status-pending { background: #f3f4f6; color: #6b7280; }
        
        /* Assessment Forms Styling */
        .details-header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #f1f1f1; padding-bottom: 14px; margin-bottom: 20px; }
        .details-header h4 { font-weight: 800; color: #003366; margin: 0; }
        .details-header .adm-no { font-size: 13px; color: #636e72; font-weight: 600; }
        
        .section-title { font-size: 14px; font-weight: 700; color: #003366; text-transform: uppercase; border-bottom: 2px solid #003366; padding-bottom: 6px; margin: 24px 0 16px 0; display: flex; align-items: center; gap: 8px; }
        
        /* Rating Grid Table */
        .rating-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 20px; }
        .rating-table th { background: #f8fafb; color: #636e72; padding: 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; text-align: center; }
        .rating-table th:first-child { text-align: left; width: 40%; }
        .rating-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; text-align: center; font-size: 13px; font-weight: 600; }
        .rating-table td:first-child { text-align: left; color: #2d3748; }
        
        /* Premium Radio Styling */
        .rating-radio { appearance: none; width: 22px; height: 22px; border: 2px solid #cbd5e1; border-radius: 50%; outline: none; cursor: pointer; transition: all 0.2s; position: relative; margin: 0 auto; display: block; }
        .rating-radio:checked { border-color: #003366; background: #ffd700; }
        .rating-radio:checked::after { content: ''; width: 8px; height: 8px; background: #003366; border-radius: 50%; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }
        .rating-radio:hover { border-color: #003366; box-shadow: 0 0 0 4px rgba(0, 51, 102, 0.1); }
        
        /* Attendance form columns */
        .attendance-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .attendance-input { border-radius: 8px; border: 1px solid #dfe6e9; padding: 10px 12px; font-size: 14px; font-weight: 600; width: 100%; }
        .attendance-input:focus { border-color: #003366; outline: none; box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1); }
        
        .remark-textarea { width: 100%; border-radius: 8px; border: 1px solid #dfe6e9; padding: 12px; font-size: 13px; font-weight: 500; resize: vertical; min-height: 80px; }
        .remark-textarea:focus { border-color: #003366; outline: none; box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1); }
        
        .save-banner { display: flex; align-items: center; justify-content: flex-end; gap: 8px; font-weight: 700; font-size: 12px; color: #22c55e; margin-bottom: 12px; min-height: 20px; }
        
        /* Instructions card */
        .instruction-box { background: #e8f4fd; border-radius: 10px; padding: 16px; font-size: 13px; color: #003366; border-left: 4px solid #003366; margin-bottom: 16px; }
    </style>
</head>
<body>
<section id="container">
    <?php include 'header.php'; ?>
    <?php include 'sideBar.php'; ?>
    <section id="main-content">
        <section class="wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h3 class="page-header"><i class="fa fa-child"></i> Behavior &amp; Psychomotor Assessment</h3>
                    <ol class="breadcrumb">
                        <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                        <li>Result Processing</li>
                        <li>Assessments</li>
                    </ol>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="page-card">
                <div class="filter-bar">
                    <div class="form-group">
                        <label>Year Group</label>
                        <select id="filterYearGroup" class="form-control" style="min-width:140px;">
                            <option value="">-- Select --</option>
                            <?php while($yg = $yearGroups->fetch_assoc()): ?>
                            <option value="<?= $yg['yearGroupId'] ?>"><?= htmlspecialchars($yg['yearGroupName']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Class</label>
                        <select id="filterClass" class="form-control" style="min-width:140px;">
                            <option value="">-- Select Year Group First --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Academic Term</label>
                        <select id="filterTerm" class="form-control">
                            <option value="1">First Term</option>
                            <option value="2">Second Term</option>
                            <option value="3">Third Term</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Session</label>
                        <input type="text" id="filterSession" class="form-control" value="2024/2025" style="width:100px;" placeholder="e.g. 2024/2025">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn-dlhs btn btn-default" onclick="loadStudents()"><i class="fa fa-search"></i> Load Students</button>
                    </div>
                </div>
            </div>

            <!-- Workspace Section -->
            <div class="workspace-layout">
                <!-- Student Sidebar List -->
                <div class="student-sidebar">
                    <div class="student-list-title">Class Students</div>
                    <div id="studentListContainer">
                        <div style="text-align:center; padding:30px; color:#888;">
                            <i class="fa fa-users" style="font-size:32px; margin-bottom:10px; display:block;"></i>
                            Select a class and term above to load students.
                        </div>
                    </div>
                </div>

                <!-- Student Assessments Form Details -->
                <div class="student-details" id="studentDetailsSection">
                    <div class="save-banner" id="saveBanner"></div>
                    <div class="details-header">
                        <div>
                            <h4 id="selectedStudentName">Student Name</h4>
                            <div class="adm-no" id="selectedStudentAdm">Admission No: DLHS/12345</div>
                        </div>
                        <div>
                            <span class="badge" style="background:#003366; color:#ffd700; padding:6px 12px; font-weight:700;" id="termSessionBadge">TERM 1 (2024/2025)</span>
                        </div>
                    </div>

                    <div class="instruction-box">
                        <strong><i class="fa fa-info-circle"></i> Scoring Guidelines:</strong><br>
                        Rate character and psychomotor skills on a scale of <strong>1 to 5</strong>:<br>
                        5 = Excellent | 4 = Very Good | 3 = Good | 2 = Fair | 1 = Poor.<br>
                        All entries, comments, and attendance records are automatically saved as you enter them.
                    </div>

                    <form id="assessmentForm">
                        <input type="hidden" name="studentId" id="studentIdField">
                        <input type="hidden" name="academicTerm" id="termField">
                        <input type="hidden" name="academicSession" id="sessionField">

                        <!-- Attendance Section -->
                        <div class="section-title"><i class="fa fa-calendar-check-o"></i> Attendance Record</div>
                        <div class="attendance-grid">
                            <div>
                                <label style="font-weight:700; font-size:12px; color:#636e72; display:block; margin-bottom:6px;">Total Days Term Opened</label>
                                <input type="number" name="totalDays" id="totalDays" class="attendance-input" min="0" onchange="autoSave()">
                            </div>
                            <div>
                                <label style="font-weight:700; font-size:12px; color:#636e72; display:block; margin-bottom:6px;">Days Present</label>
                                <input type="number" name="presentDays" id="presentDays" class="attendance-input" min="0" onchange="autoSave()">
                            </div>
                        </div>

                        <!-- Character Development Section -->
                        <div class="section-title"><i class="fa fa-heart"></i> Character Development</div>
                        <table class="rating-table">
                            <thead>
                                <tr>
                                    <th>Behavior / Trait</th>
                                    <th>1</th>
                                    <th>2</th>
                                    <th>3</th>
                                    <th>4</th>
                                    <th>5</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $charTraits = [
                                    'punctuality' => 'Punctuality',
                                    'neatness' => 'Neatness',
                                    'politeness' => 'Politeness',
                                    'honesty' => 'Honesty',
                                    'teamSpirit' => 'Cooperation',
                                    'leadership' => 'Leadership',
                                    'helpingOthers' => 'Helpfulness',
                                    'emotionalStability' => 'Emotion',
                                    'health' => 'Health',
                                    'attitudeToWork' => 'Attitude',
                                    'attentiveness' => 'Attentiveness',
                                    'perseverance' => 'Perseverance',
                                    'spokenEnglish' => 'Spoken English'
                                ];
                                foreach ($charTraits as $key => $label):
                                ?>
                                <tr>
                                    <td><?= $label ?></td>
                                    <?php for($i=1; $i<=5; $i++): ?>
                                    <td>
                                        <input type="radio" name="<?= $key ?>" value="<?= $i ?>" class="rating-radio" onclick="autoSave()">
                                    </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Psychomotor Skills Section -->
                        <div class="section-title"><i class="fa fa-gears"></i> Psychomotor Skills</div>
                        <table class="rating-table">
                            <thead>
                                <tr>
                                    <th>Skill / Capability</th>
                                    <th>1</th>
                                    <th>2</th>
                                    <th>3</th>
                                    <th>4</th>
                                    <th>5</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $psySkills = [
                                    'handwriting' => 'Handwriting',
                                    'verbalFluency' => 'Verbal Fluency',
                                    'sports' => 'Sports',
                                    'handlingTools' => 'Tools Handling',
                                    'drawingPainting' => 'Drawing/Painting',
                                    'musical' => 'Music'
                                ];
                                foreach ($psySkills as $key => $label):
                                ?>
                                <tr>
                                    <td><?= $label ?></td>
                                    <?php for($i=1; $i<=5; $i++): ?>
                                    <td>
                                        <input type="radio" name="<?= $key ?>" value="<?= $i ?>" class="rating-radio" onclick="autoSave()">
                                    </td>
                                    <?php endfor; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- Remarks and Comments Section -->
                        <div class="section-title"><i class="fa fa-commenting"></i> Descriptive Remarks</div>
                        <div class="form-group" style="margin-bottom:18px;">
                            <label style="font-weight:700; font-size:12px; color:#636e72; display:block; margin-bottom:6px;">Form Teacher's Mid-Term Comment</label>
                            <textarea name="midTermComment" id="midTermComment" class="remark-textarea" placeholder="Enter mid-term comments on academic progress and conduct..." onblur="autoSave()"></textarea>
                        </div>
                        <div class="form-group" style="margin-bottom:18px;">
                            <label style="font-weight:700; font-size:12px; color:#636e72; display:block; margin-bottom:6px;">Form Teacher's End-of-Term Comment</label>
                            <textarea name="endOfTermComment" id="endOfTermComment" class="remark-textarea" placeholder="Enter end-of-term comments on academic progress and conduct..." onblur="autoSave()"></textarea>
                        </div>
                        <div class="form-group" style="margin-bottom:18px;">
                            <label style="font-weight:700; font-size:12px; color:#636e72; display:block; margin-bottom:6px;">Principal's Remark</label>
                            <textarea name="principalRemark" id="principalRemark" class="remark-textarea" placeholder="Enter principal's final administrative remarks..." onblur="autoSave()"></textarea>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </section>
    <div class="text-right"><div class="credits"><?php include "footer.php"; ?></div></div>
</section>

<script src="js/bootstrap.min.js"></script>
<script src="js/scripts.js"></script>
<script>
var assignedYearGroupId = <?= json_encode($assignedYearGroupId) ?>;
var assignedClassId = <?= json_encode($assignedClassId) ?>;

var currentSession = '';
var currentTerm    = 1;
var activeStudentId = null;
var isSaving = false;
var saveTimeout = null;
var pendingSave = null;

function flushPendingSave(isUnloading) {
    if (pendingSave) {
        clearTimeout(pendingSave.timeoutId);
        if (isUnloading) {
            // Perform synchronous AJAX save on page unload to guarantee safety
            $.ajax({
                url: 'save_assessment.php',
                type: 'POST',
                data: pendingSave.formData,
                async: false,
                dataType: 'json'
            });
            pendingSave = null;
        } else {
            pendingSave.execute();
        }
    }
}

$(window).on('beforeunload', function() {
    flushPendingSave(true);
});

function loadStudents() {
    var classId = $('#filterClass').val();
    var term    = $('#filterTerm').val();
    var session = $('#filterSession').val().trim();
    if (!classId || !session) {
        showPremiumModal('Please select a class and enter an academic session before loading students.', 'Selection Required', 'warning');
        return;
    }
    currentSession = session;
    currentTerm    = parseInt(term);
    
    // Hide details section
    $('#studentDetailsSection').hide();
    activeStudentId = null;
    
    $('#studentListContainer').html('<div style="text-align:center; padding:30px;"><i class="fa fa-spinner fa-spin" style="font-size:24px;"></i> Loading students...</div>');
    
    $.ajax({
        url: 'get_class_students.php',
        type: 'POST',
        data: { classId: classId },
        dataType: 'json',
        success: function(students) {
            if (!students || students.length === 0) {
                $('#studentListContainer').html('<div style="text-align:center; padding:20px; color:#999;">No students found for this class.</div>');
                return;
            }
            
            var html = '';
            students.forEach(function(s) {
                html += '<div class="student-item" id="student-' + s.studentId + '" onclick="selectStudent(' + s.studentId + ', \'' + s.fullName.replace(/'/g, "\\'") + '\', \'' + s.admissionNumber + '\')">';
                html += '  <div class="student-info">';
                html += '    <span class="student-name">' + s.fullName + '</span>';
                html += '    <span class="student-adm">' + s.admissionNumber + '</span>';
                html += '  </div>';
                html += '  <span class="status-badge status-pending" id="badge-' + s.studentId + '">Pending</span>';
                html += '</div>';
            });
            $('#studentListContainer').html(html);
            
            // Check statuses by querying existing assessments
            // (A student has entered assessment if they have any saved row for term/session)
            students.forEach(function(s) {
                checkStudentStatus(s.studentId);
            });
        },
        error: function() {
            $('#studentListContainer').html('<div style="text-align:center; padding:20px; color:red;">Failed to load class students.</div>');
        }
    });
}

function checkStudentStatus(studentId) {
    $.ajax({
        url: 'get_assessment.php',
        type: 'POST',
        data: { studentId: studentId, academicTerm: currentTerm, academicSession: currentSession },
        dataType: 'json',
        success: function(res) {
            if (res.success && res.data) {
                $('#badge-' + studentId).removeClass('status-pending').addClass('status-entered').text('✓ Entered');
            } else {
                $('#badge-' + studentId).removeClass('status-entered').addClass('status-pending').text('Pending');
            }
        }
    });
}

function selectStudent(studentId, fullName, admNo) {
    flushPendingSave();
    
    activeStudentId = studentId;
    $('.student-item').removeClass('active');
    $('#student-' + studentId).addClass('active');
    
    // Set headers
    $('#selectedStudentName').text(fullName);
    $('#selectedStudentAdm').text('Admission No: ' + admNo);
    $('#termSessionBadge').text('TERM ' + currentTerm + ' (' + currentSession + ')');
    
    // Set fields in form
    $('#studentIdField').val(studentId);
    $('#termField').val(currentTerm);
    $('#sessionField').val(currentSession);
    
    // Reset form fields
    $('#assessmentForm')[0].reset();
    
    // Clear save banner
    $('#saveBanner').html('');
    
    // Load student assessment data
    $.ajax({
        url: 'get_assessment.php',
        type: 'POST',
        data: { studentId: studentId, academicTerm: currentTerm, academicSession: currentSession },
        dataType: 'json',
        success: function(res) {
            if (res.success && res.data) {
                var d = res.data;
                
                // Set attendance
                $('#totalDays').val(d.totalDays);
                $('#presentDays').val(d.presentDays);
                
                // Set comments
                $('#midTermComment').val(d.midTermComment);
                $('#endOfTermComment').val(d.endOfTermComment);
                $('#principalRemark').val(d.principalRemark);
                
                // Set radios
                Object.keys(d).forEach(function(key) {
                    if (d[key] !== null && d[key] !== undefined && !['assessmentId', 'studentId', 'academicTerm', 'academicSession', 'totalDays', 'presentDays', 'classTeacherComment', 'principalRemark', 'midTermComment', 'endOfTermComment', 'updatedAt'].includes(key)) {
                        $('input[name="' + key + '"][value="' + d[key] + '"]').prop('checked', true);
                    }
                });
            }
            $('#studentDetailsSection').fadeIn(200);
        },
        error: function() {
            showPremiumModal('Could not load assessment details. Please try again.', 'Load Error', 'error');
        }
    });
}

function autoSave() {
    if (!activeStudentId) return;
    
    $('#saveBanner').html('<i class="fa fa-spinner fa-spin" style="color:#003366;"></i> Saving changes...');
    
    var studentIdToSave = activeStudentId;
    var formData = $('#assessmentForm').serialize();
    
    if (pendingSave) {
        clearTimeout(pendingSave.timeoutId);
    }
    
    var executeSave = function() {
        pendingSave = null;
        $.ajax({
            url: 'save_assessment.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    if (activeStudentId === studentIdToSave) {
                        $('#saveBanner').html('<i class="fa fa-check-circle"></i> All changes saved.');
                    }
                    $('#badge-' + studentIdToSave).removeClass('status-pending').addClass('status-entered').text('✓ Entered');
                } else {
                    if (activeStudentId === studentIdToSave) {
                        $('#saveBanner').html('<span style="color:red;"><i class="fa fa-exclamation-circle"></i> Error saving changes: ' + res.msg + '</span>');
                    }
                }
            },
            error: function() {
                if (activeStudentId === studentIdToSave) {
                    $('#saveBanner').html('<span style="color:red;"><i class="fa fa-exclamation-circle"></i> Network error while saving</span>');
                }
            }
        });
    };
    
    var timeoutId = setTimeout(executeSave, 800);
    
    pendingSave = {
        timeoutId: timeoutId,
        execute: executeSave,
        formData: formData
    };
}

// Load classes when year group changes
function loadClasses(yg, selectedClassId, callback) {
    if(!yg) { $('#filterClass').html('<option value="">-- Select Year Group First --</option>'); return; }
    $.ajax({
        url: 'get_students_for_class.php',
        type: 'POST',
        data: { yearGroupId: yg, listClasses: 1 },
        dataType: 'json',
        success: function(data){
            var opts = '<option value="">-- Select Class --</option>';
            if(data && data.length) data.forEach(function(c){ opts += '<option value="'+c.classId+'">'+c.className+'</option>'; });
            $('#filterClass').html(opts);
            if (selectedClassId) {
                $('#filterClass').val(selectedClassId);
            }
            if (callback) callback();
        },
        error: function(){
            $.post('get_my_classes.php', {yearGroupId: yg}, function(d){
                try { 
                    var data = JSON.parse(d); 
                    var opts='<option value="">-- Select Class --</option>';
                    data.forEach(function(c){ opts+='<option value="'+c.classId+'">'+c.className+'</option>'; });
                    $('#filterClass').html(opts); 
                    if (selectedClassId) {
                        $('#filterClass').val(selectedClassId);
                    }
                    if (callback) callback();
                } catch(e){}
            });
        }
    });
}

$('#filterYearGroup').change(function(){
    loadClasses($(this).val());
});

$(document).ready(function() {
    if (assignedYearGroupId && assignedClassId) {
        $('#filterYearGroup').val(assignedYearGroupId);
        loadClasses(assignedYearGroupId, assignedClassId, function() {
            loadStudents();
        });
    }
});
</script>
<script src="js/premium_modal.js"></script>
</body>
</html>
