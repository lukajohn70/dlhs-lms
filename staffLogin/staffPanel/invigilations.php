<?php
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['staffLoggedIn'])) {
    header('location:../index.php');
}
include "../../db_connection/dlhs_db_connection.php";
$staffId = $_SESSION['staffId'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invigilation Center | DLHS</title>
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/elegant-icons-style.css" rel="stylesheet" />
    <link href="css/font-awesome.min.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet">
    <link href="css/style-responsive.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="../../datatables/css/jquery.dataTables.min.css"/>
    <link rel="stylesheet" type="text/css" href="../../datatables/css/responsive.dataTables.min.css"/>
    <script src="../../libs/jquery.min.js"></script>

    <style>
        :root {
            --primary: #009999;
            --primary-light: #0acca2;
            --accent: #ff7675;
            --glass: rgba(255, 255, 255, 0.93);
            --shadow: 0 10px 30px rgba(0, 153, 153, 0.1);
        }

        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }
        
        .glass-card {
            background: var(--glass);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 20px;
        }

        .action-btn {
            border: none; border-radius: 12px; padding: 10px 22px; font-weight: 700;
            display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
            color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.1); cursor: pointer;
        }
        .action-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
        .btn-green  { background: linear-gradient(135deg,#27ae60,#2ecc71); }
        .btn-red    { background: linear-gradient(135deg,#e74c3c,#ff7675); }
        .btn-orange { background: linear-gradient(135deg,#f39c12,#f1c40f); }
        .btn-purple { background: linear-gradient(135deg,#8e44ad,#9b59b6); }
        .btn-blue   { background: linear-gradient(135deg,#2980b9,#3498db); }

        /* Status Badges */
        .badge-locked { background: #e74c3c; padding: 5px 12px; border-radius: 20px; color: white; font-size: 11px; }
        .badge-active { background: #2ecc71; padding: 5px 12px; border-radius: 20px; color: white; font-size: 11px; animation: pulse 2s infinite; }
        .badge-ended  { background: #95a5a6; padding: 5px 12px; border-radius: 20px; color: white; font-size: 11px; }

        .monitor-table {
            width: 100%; border-collapse: separate; border-spacing: 0 8px; margin-top: 15px;
        }
        .monitor-table th {
            padding: 12px 15px; color: #636e72; font-weight: 700; text-transform: uppercase; font-size: 11px;
            border-bottom: 2px solid #eee;
        }
        .monitor-table tr { background: white; transition: all 0.2s; }
        .monitor-table td { padding: 12px 15px; border-top: 1px solid #f8f9fa; border-bottom: 1px solid #f8f9fa; }
        .monitor-table td:first-child { border-left: 1px solid #f8f9fa; border-top-left-radius: 10px; border-bottom-left-radius: 10px; }
        .monitor-table td:last-child { border-right: 1px solid #f8f9fa; border-top-right-radius: 10px; border-bottom-right-radius: 10px; }

        .monitor-table tr.live td:first-child { border-left: 5px solid #2ecc71; }
        .monitor-table tr.paused td:first-child { border-left: 5px solid #f1c40f; }
        .monitor-table tr.submitted td:first-child { border-left: 5px solid #95a5a6; opacity: 0.7; }
        .monitor-table tr.absent td:first-child { border-left: 5px solid #e74c3c; }

        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .dot-live   { background: #2ecc71; animation: pulse 2s infinite; }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }

        .student-name { font-weight: 700; font-size: 14px; color: #2d3436; display: block; }
        .class-badge  { font-size: 11px; color: #636e72; }
        .time-remaining { font-size: 18px; font-weight: 800; color: var(--primary); }
        
        .quick-btn {
            border: none; border-radius: 6px; padding: 4px 8px; font-size: 11px; font-weight: 600;
            background: #f1f2f6; color: #2d3436; transition: all 0.2s; cursor: pointer; margin-right: 4px;
        }
        .quick-btn:hover:not(:disabled) { background: #dfe4ea; }
        .quick-btn.danger { color: #e74c3c; }
        .quick-btn:disabled { opacity: 0.5; cursor: not-allowed; }

        .switch-container { display: flex; align-items: center; gap: 8px; font-size: 11px; font-weight: 700; }
        .switch { position: relative; display: inline-block; width: 32px; height: 18px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc; transition: .4s; border-radius: 20px;
        }
        .slider:before {
            position: absolute; content: ""; height: 12px; width: 12px;
            left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%;
        }
        input:checked + .slider { background-color: var(--primary); }
        input:checked + .slider:before { transform: translateX(14px); }

        .sel-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary); }
        
        #loadingOverlay {
            position: fixed; top: 20px; right: 20px; background: rgba(0,0,0,0.7);
            color: white; padding: 10px 20px; border-radius: 30px; font-size: 12px;
            z-index: 9999; display: none;
        }

        .master-control-box {
            background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 15px;
            margin-top: 15px; display: flex; align-items: center; justify-content: space-between;
        }
    </style>
</head>
<body>

<div id="loadingOverlay"><i class="fa fa-spinner fa-spin"></i> Processing...</div>

<section id="container">
    <?php include 'header.php'; ?>
    <?php include 'sideBar.php'; ?>

    <section id="main-content">
        <section class="wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h3 class="page-header" style="margin-bottom:0;"><i class="fa fa-shield"></i> Unified Invigilation Center</h3>
                    <p class="text-muted">Direct control of attendance, access, and monitoring</p>
                </div>
            </div>

            <div class="glass-card">
                <div class="row">
                    <div class="col-md-6">
                        <label style="font-weight:700; color:var(--primary);">SELECT ASSESSMENT</label>
                        <select id="testSelector" class="form-control" style="border-radius:10px; height:45px;">
                            <option value="">-- Select Test --</option>
                            <?php
                            $getTests = "SELECT DISTINCT t.* FROM tests t
                                         LEFT JOIN test_class_invigilators tci ON t.testId = tci.testId
                                         WHERE t.invigilatorId='$staffId' OR tci.invigilatorId='$staffId'
                                         ORDER BY t.testId DESC";
                            $res = $connection->query($getTests);
                            while($row = $res->fetch_assoc()) {
                                echo "<option value='".$row['testId']."'>".$row['testName']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 text-right" id="globalActions" style="display:none; padding-top:25px;">
                        <div class="att-summary" style="display:inline-block; vertical-align:middle; margin-right:15px; background:#fff; padding:8px 15px; border-radius:10px; border:1px solid #eee;">
                            <span style="font-size:12px; font-weight:700;">
                                <span style="color:#27ae60;">Enrolled: <span id="attPresentCount">0</span></span> | 
                                <span style="color:var(--primary);">Active: <span id="attActiveCount">0</span></span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Master Test Control (Global) -->
                <div id="masterTestControl" class="master-control-box" style="display:none;">
                    <div>
                        <span style="font-size:12px; font-weight:800; color:#636e72; display:block; text-transform:uppercase;">Global Test Status</span>
                        <div id="globalStatusBadge"></div>
                    </div>
                    <div id="globalStatusActions">
                        <!-- Buttons injected via JS -->
                    </div>
                </div>

                <div id="unifiedCommands" style="display:none; margin-top:20px; padding-top:20px; border-top:1px solid #eee; display:flex; gap:10px; flex-wrap:wrap;">
                    <button class="action-btn btn-green" onclick="bulkAction('mark_all_present')">
                        <i class="fa fa-unlock"></i> Mark All Present (Grant Access)
                    </button>
                    <button class="action-btn btn-blue" onclick="bulkAction('mark_selected_present')">
                        <i class="fa fa-check-square-o"></i> Mark Selected Present
                    </button>
                    <button class="action-btn btn-orange" onclick="bulkAction('arm_test_control', 0)">
                        <i class="fa fa-pause"></i> Pause All Active
                    </button>
                    <button class="action-btn btn-red" onclick="bulkAction('arm_test_control', 2)">
                        <i class="fa fa-sign-out"></i> Submit All Active
                    </button>
                </div>
            </div>

            <div id="monitorContainer" style="display:none;">
                <div class="table-responsive">
                    <table class="monitor-table">
                        <thead>
                            <tr>
                                <th width="40"><input type="checkbox" id="selectAllStudents" class="sel-checkbox"></th>
                                <th>Student</th>
                                <th>Attendee Access <i class="fa fa-question-circle" title="Enables the student to enter/start the test."></i></th>
                                <th>Live Status</th>
                                <th>Progress</th>
                                <th>Time</th>
                                <th width="280">Management <i class="fa fa-question-circle" title="Controls for active test sessions."></i></th>
                            </tr>
                        </thead>
                        <tbody id="studentList"></tbody>
                    </table>
                </div>
            </div>

            <div id="emptyState" style="text-align:center; padding:80px 0;">
                <i class="fa fa-shield fa-4x" style="color:#eee; margin-bottom:20px;"></i>
                <h4 style="color:#95a5a6;">Choose an assessment above to start monitoring</h4>
            </div>

        </section>
    </section>
</section>

<script>
    var refreshInterval = null;
    var selectedStudents = new Set();
    var isActionPending = false;

    $('#testSelector').on('change', function() {
        var testId = $(this).val();
        selectedStudents.clear();
        $('#selectAllStudents').prop('checked', false);
        if (testId) {
            $("#emptyState").hide();
            $("#monitorContainer, #globalActions, #unifiedCommands, #masterTestControl").fadeIn();
            loadStudents();
            startPolling();
        } else {
            $("#emptyState").show();
            $("#monitorContainer, #globalActions, #unifiedCommands, #masterTestControl").hide();
            stopPolling();
        }
    });

    $(document).on('change', '.student-sel-cb', function() {
        var id = String($(this).data('id'));
        if (this.checked) selectedStudents.add(id);
        else selectedStudents.delete(id);
    });

    $('#selectAllStudents').on('change', function() {
        var checked = this.checked;
        $('.student-sel-cb').each(function() {
            var id = String($(this).data('id'));
            $(this).prop('checked', checked);
            if (checked) selectedStudents.add(id);
            else selectedStudents.delete(id);
        });
    });

    function startPolling() { stopPolling(); refreshInterval = setInterval(loadStudents, 5000); }
    function stopPolling()  { if(refreshInterval) clearInterval(refreshInterval); }

    function loadStudents() {
        if (isActionPending) return;
        var testId = $('#testSelector').val();
        if(!testId) return;

        $.ajax({
            url: "getExamineesStatus.php",
            type: "POST",
            data: {testId: testId, mode: 'invigilate'},
            success: function(data) {
                if (isActionPending) return;
                var students;
                try { 
                    students = (typeof data === 'string') ? JSON.parse(data) : data; 
                } catch(e) { return; }

                // Update Master Test Status (Global)
                if (students.length > 0) {
                    // We assume test info is consistent across student rows, but let's fetch it once
                    // Actually, let's fetch test status from a separate endpoint or first student
                    // But our getExamineesStatus.php returns array of examinees. 
                    // I'll add global test status to the first student's metadata for convenience.
                    // For now, I'll assume status is available. Wait, I should update getExamineesStatus.php to include global status.
                }

                // Since I haven't updated getExamineesStatus.php yet, I'll do a quick check on a sample
                // Let's assume I'll update it now.

                var enrolledCount = 0, activeCount = 0;
                var html = "";

                $.each(students, function(i, s) {
                    // Render Master Status on first student
                    if (i === 0) renderMasterStatus(s.globalTestStatus);

                    var isActive  = parseInt(s.examineeTeststatus) === 1;
                    var isPaused  = parseInt(s.isPaused) === 1;
                    var isSubmit  = parseInt(s.examineeTeststatus) === 2;
                    var isStarted = parseInt(s.isStarted) === 1;

                    enrolledCount++;
                    if (isActive && !isPaused) activeCount++;

                    var trClass    = isSubmit ? 'submitted' : (isActive ? (isPaused ? 'paused' : 'live') : '');
                    var statusText = isSubmit ? 'FINISHED' : (isActive ? (isPaused ? 'PAUSED' : 'ACTIVE') : (isStarted ? 'STARTED' : 'WAITING'));
                    var dotClass   = (isActive && !isPaused) ? 'dot-live' : '';
                    var isChecked  = selectedStudents.has(String(s.examineeUserId)) ? 'checked' : '';

                    html += `
                    <tr class="${trClass}">
                        <td><input type="checkbox" class="sel-checkbox student-sel-cb" data-id="${s.examineeUserId}" ${isChecked}></td>
                        <td>
                            <span class="student-name">${s.examineeName}</span>
                            <span class="class-badge">${s.classAndYearGroupName}</span>
                        </td>
                        <td>
                            <span class="status-dot ${dotClass}" style="${isPaused?'background:#f1c40f':''}"></span>
                            <span style="font-size:11px; font-weight:700;">${statusText}</span>
                        </td>
                        <td>
                            <div style="font-weight: 700; color: #2c3e50; font-size: 13px;">
                                ${s.answeredCount} / ${s.totalQuestions}
                            </div>
                            <span style="font-size: 10px; color: #7f8c8d; font-weight: 600; text-transform: uppercase;">answered</span>
                        </td>
                        <td><div class="time-remaining">${s.remainingTime}<small style="font-size:10px;">m</small></div></td>
                        <td>
                            ${isActive ? `
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <div style="display: flex; gap: 4px; align-items: center;">
                                        <button class="quick-btn" onclick="performAction('toggle_pause', {studentId:'${s.examineeUserId}', status:${isPaused?0:1}})" title="${isPaused?'Resume Test':'Pause Test'}" style="background:${isPaused?'#2ecc71':'#f1c40f'}; color:white; width:30px; height:30px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center;">
                                            <i class="fa ${isPaused?'fa-play':'fa-pause'}"></i>
                                        </button>
                                        <button class="quick-btn" onclick="performAction('add_time', {studentId:'${s.examineeUserId}', minutes:5})" title="Add 5 Minutes" style="background:#3498db; color:white; font-weight:700; padding:0 8px; height:30px; border-radius:6px;">+5m</button>
                                        <button class="quick-btn" onclick="performAction('add_time', {studentId:'${s.examineeUserId}', minutes:-5})" title="Reduce 5 Minutes" style="background:#e67e22; color:white; font-weight:700; padding:0 8px; height:30px; border-radius:6px;">-5m</button>
                                        <button class="quick-btn danger" onclick="confirmAndAction('Force End this student\'s test?', 'force_submit', {studentId:'${s.examineeUserId}'})" title="Force Submit" style="width:30px; height:30px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center;"><i class="fa fa-stop"></i></button>
                                    </div>
                                    <div style="display: flex; gap: 4px; align-items: center;">
                                        <input type="number" id="customMin_${s.examineeUserId}" style="width: 55px; height: 28px; padding: 2px 4px; border-radius: 4px; border: 1px solid #bdc3c7; font-size: 12px; font-weight: 600;" placeholder="Min">
                                        <button class="quick-btn" onclick="var m=parseInt($('#customMin_${s.examineeUserId}').val())||0; if(m===0){alert('Enter non-zero minutes');}else{performAction('add_time',{studentId:'${s.examineeUserId}',minutes:m});}" style="background:#7f8c8d; color:white; font-size:11px; height:28px; padding:0 6px; border-radius:6px; font-weight: 600;">Adjust</button>
                                    </div>
                                </div>
                            ` : (isSubmit ? `<span class="badge badge-success" style="background:#95a5a6; padding: 6px 12px; border-radius: 6px; font-size:11px;">Submitted</span>` : `
                                <button class="quick-btn" onclick="performAction('unlock_student', {studentId:'${s.examineeUserId}'})" style="background:#2ecc71; color:white; font-weight: 700; padding: 6px 12px; height:auto; border-radius:6px;"><i class="fa fa-unlock"></i> Grant Access</button>
                            `)}
                        </td>
                    </tr>`;
                });

                $('#attPresentCount').text(enrolledCount);
                $('#attActiveCount').text(activeCount);
                $("#studentList").html(html);
            }
        });
    }

    function renderMasterStatus(status) {
        var badgeHtml = "";
        var actionHtml = "";
        status = parseInt(status);

        if (status === 0) {
            badgeHtml = '<span class="badge-locked"><i class="fa fa-lock"></i> GLOBAL LOCKED</span>';
            actionHtml = '<button class="quick-btn" style="background:#2ecc71; color:white; padding:8px 15px;" onclick="confirmAndAction(\'START the test for all unlocked students?\', \'global_test_control\', {status:1})"><i class="fa fa-play"></i> START TEST</button>';
        } else if (status === 1) {
            badgeHtml = '<span class="badge-active"><i class="fa fa-refresh fa-spin"></i> GLOBAL ACTIVE</span>';
            actionHtml = '<button class="quick-btn" style="background:#e74c3c; color:white; padding:8px 15px;" onclick="confirmAndAction(\'END the test globally? Students will no longer be able to submit.\', \'global_test_control\', {status:2})"><i class="fa fa-stop"></i> END TEST</button>';
        } else {
            badgeHtml = '<span class="badge-ended"><i class="fa fa-check-circle"></i> GLOBAL ENDED</span>';
            actionHtml = '<button class="quick-btn" onclick="confirmAndAction(\'Restart the test?\', \'global_test_control\', {status:1})">Restart</button>';
        }

        $("#globalStatusBadge").html(badgeHtml);
        $("#globalStatusActions").html(actionHtml);
    }

    function performAction(action, params) {
        isActionPending = true;
        $("#loadingOverlay").fadeIn(100);
        
        var payload = Object.assign({
            action: action,
            testId: $('#testSelector').val()
        }, params);

        $.ajax({
            url: "invigilationActions.php",
            type: "POST",
            data: payload,
            dataType: "json",
            success: function(res) {
                isActionPending = false;
                $("#loadingOverlay").fadeOut(200);
                if (res && res.status === 'success') {
                    loadStudents();
                } else {
                    alert("Action Failed: " + (res.message || "Unknown error"));
                    loadStudents();
                }
            },
            error: function(xhr) {
                isActionPending = false;
                $("#loadingOverlay").fadeOut(200);
                alert("Network/Server Error. Please try again.");
                loadStudents();
            }
        });
    }

    function confirmAndAction(msg, action, params) {
        if (confirm(msg)) performAction(action, params);
    }

    function bulkAction(action, status) {
        var params = {};
        if (status !== undefined) params.status = status;
        
        if (action === 'mark_selected_present') {
            var selected = Array.from(selectedStudents);
            if (selected.length === 0) return alert("Please select students using the checkboxes first.");
            params['studentIds[]'] = selected;
        }

        var msg = "Proceed with this bulk action?";
        if (action === 'mark_all_present') msg = "Unlock access for ALL students in this list?";
        if (action === 'arm_test_control' && status === 0) msg = "Pause ALL active students?";
        if (action === 'arm_test_control' && status === 2) msg = "Force submit ALL active students?";

        confirmAndAction(msg, action, params);
    }
</script>
</body>
</html>
