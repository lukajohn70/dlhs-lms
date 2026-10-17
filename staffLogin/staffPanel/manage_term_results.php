<?php
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['staffLoggedIn'])) { header('location:../index.php'); exit; }
require_once '../../db_connection/dlhs_db_connection.php';
$staffId = $_SESSION['staffId'];

// Fetch year groups assigned to the teacher
$yearGroups = $connection->query("
    SELECT DISTINCT yg.yearGroupId, yg.yearGroupName 
    FROM subject_teacher_assignment sta 
    INNER JOIN classes c ON sta.classId = c.classId 
    INNER JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId 
    WHERE sta.teacherId = '$staffId' AND yg.yearGroupId NOT IN (34,37) 
    ORDER BY yg.yearGroupId
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Score Entry | DLHS</title>
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
        .score-table { width:100%; border-collapse:separate; border-spacing:0; font-size:13px; }
        .score-table thead th { background:#003366; color:#ffd700; padding:11px 10px; font-size:12px; text-transform:uppercase; letter-spacing:.05em; position:sticky; top:0; z-index:5; }
        .score-table tbody tr:nth-child(even) { background:#f8fafb; }
        .score-table tbody tr:hover { background:#e8f4fd; }
        .score-table td { padding:8px 10px; border-bottom:1px solid #f1f1f1; vertical-align:middle; }
        .score-input { width:64px; text-align:center; border:1px solid #dfe6e9; border-radius:6px; padding:5px 6px; font-size:13px; font-weight:600; transition:border-color .2s; }
        .score-input:focus { border-color:#00AEEF; outline:none; box-shadow:0 0 0 2px rgba(0,174,239,.15); }
        .score-input.saved { border-color:#22c55e; background:#f0fdf4; }
        .score-input.error-val { border-color:#ef4444; background:#fef2f2; }
        .total-cell { font-weight:800; color:#003366; font-size:14px; }
        .remark-cell { font-weight:700; font-size:11px; padding:3px 8px; border-radius:12px; }
        .remark-EXCELLENT  { background:#d1fae5; color:#065f46; }
        .remark-VERY-GOOD  { background:#dbeafe; color:#1e40af; }
        .remark-GOOD       { background:#fef9c3; color:#713f12; }
        .remark-FAIR       { background:#ffedd5; color:#9a3412; }
        .remark-POOR       { background:#fee2e2; color:#991b1b; }
        .save-indicator { font-size:11px; margin-left:4px; }
        .type-tabs { display:flex; gap:8px; margin-bottom:18px; }
        .type-tab { padding:8px 20px; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; border:2px solid #003366; color:#003366; background:#fff; transition:all .2s; }
        .type-tab.active { background:#003366; color:#ffd700; }
        .col-label { font-size:11px; color:#888; display:block; }
        .max-label { font-size:10px; color:#b2bec3; }
        #loadingRow td { text-align:center; padding:30px; color:#999; }

        /* Premium Alert Modal Styles */
        .premium-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 51, 102, 0.4);
            backdrop-filter: blur(8px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease-in-out;
        }
        .premium-modal-backdrop.active {
            opacity: 1;
            pointer-events: auto;
        }
        .premium-modal-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-top: 4px solid #003366;
            width: 90%;
            max-width: 420px;
            padding: 24px;
            text-align: center;
            transform: scale(0.9) translateY(20px);
            transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .premium-modal-backdrop.active .premium-modal-card {
            transform: scale(1) translateY(0);
        }
        .premium-modal-icon {
            font-size: 44px;
            color: #ff9f1c;
            margin-bottom: 16px;
            animation: bounceIcon 1s ease infinite alternate;
        }
        .premium-modal-title {
            font-size: 15px;
            font-weight: 800;
            color: #003366;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .premium-modal-message {
            font-size: 14px;
            color: #4a5568;
            line-height: 1.5;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .premium-modal-btn {
            background: linear-gradient(135deg, #003366, #005599);
            color: #ffd700;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            padding: 10px 28px;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0, 51, 102, 0.2);
            transition: all 0.2s;
            outline: none;
        }
        .premium-modal-btn:hover {
            transform: translateY(-2px);
            filter: brightness(115%);
            box-shadow: 0 6px 16px rgba(0, 51, 102, 0.3);
        }

        @keyframes bounceIcon {
            0% { transform: translateY(0); }
            100% { transform: translateY(-6px); }
        }
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
                    <h3 class="page-header"><i class="fa fa-edit"></i> Score Entry — Term Results</h3>
                    <ol class="breadcrumb">
                        <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                        <li>Result Processing</li>
                        <li>Score Entry</li>
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
                        <label>Subject</label>
                        <select id="filterSubject" class="form-control" style="min-width:200px;">
                            <option value="">-- Select Class First --</option>
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

            <!-- Type Toggle -->
            <div class="page-card" id="entrySection" style="display:none;">
                <div class="type-tabs">
                    <div class="type-tab active" id="tabMidTerm" onclick="switchType('mid_term')">
                        <i class="fa fa-pencil"></i> Mid-Term Scores
                    </div>
                    <div class="type-tab" id="tabEndTerm" onclick="switchType('end_of_term')">
                        <i class="fa fa-graduation-cap"></i> End-of-Term Scores
                    </div>
                </div>

                <div id="headerEndTerm" style="display:none;">
                    <div style="background:#e8f4fd; border-radius:8px; padding:10px 14px; margin-bottom:14px; font-size:13px; color:#003366;">
                        <strong><i class="fa fa-info-circle"></i> End-of-Term:</strong>
                        CA Test 1 (max 20) [Auto-loaded from Mid-Term Total] + CA Test 2 (max 20) + Exam (max 60) = Total (max 100)
                    </div>
                </div>
                <div id="headerMidTerm">
                    <div style="background:#fef9c3; border-radius:8px; padding:10px 14px; margin-bottom:14px; font-size:13px; color:#713f12;">
                        <strong><i class="fa fa-info-circle"></i> Mid-Term:</strong>
                        Assignment (max 5) + Project (max 5) + Test (max 10) = Total (max 20)
                    </div>
                </div>

                <div style="overflow-x:auto;">
                    <table class="score-table">
                        <thead id="tableHead"></thead>
                        <tbody id="scoreTableBody">
                            <tr id="loadingRow"><td colspan="8" style="text-align:center;padding:30px;color:#999;"><i class="fa fa-spinner fa-spin"></i> Load students using the filter above.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:12px; font-size:12px; color:#888;">
                    <i class="fa fa-info-circle"></i> Scores save automatically as you type. Green border = saved. 
                    <strong id="saveCounter" style="color:#003366;"></strong>
                </div>
            </div>
        </section>
    </section>
    <div class="text-right"><div class="credits"><?php include "footer.php"; ?></div></div>
</section>

<script src="js/bootstrap.min.js"></script>
<script src="js/scripts.js"></script>
<script>
var currentType    = 'mid_term';
var saveTimers     = {};
var totalSaved     = 0;
var currentSession = '';
var currentTerm    = 1;
var currentSubject = 0;

function switchType(type) {
    currentType = type;
    if(type === 'end_of_term') {
        $('#tabEndTerm').addClass('active'); $('#tabMidTerm').removeClass('active');
        $('#headerEndTerm').show(); $('#headerMidTerm').hide();
    } else {
        $('#tabMidTerm').addClass('active'); $('#tabEndTerm').removeClass('active');
        $('#headerMidTerm').show(); $('#headerEndTerm').hide();
    }
    buildTableHead();
    renderRows(window._studentsCache || []);
}

function buildTableHead() {
    var h = '<tr>';
    h += '<th width="4%">#</th>';
    h += '<th width="22%">Student Name</th>';
    h += '<th width="10%">Adm. No.</th>';
    
    if(currentType === 'end_of_term') {
        // Add columns for previous terms' totals dynamically in End-of-Term only
        if (currentTerm >= 2) {
            h += '<th width="8%"><span class="col-label">1st Term Total</span><span class="max-label">Max 100</span></th>';
        }
        if (currentTerm >= 3) {
            h += '<th width="8%"><span class="col-label">2nd Term Total</span><span class="max-label">Max 100</span></th>';
        }
        
        h += '<th width="10%"><span class="col-label">CA Test 1 (Mid-Term)</span><span class="max-label">Max 20 (Read-Only)</span></th>';
        h += '<th width="10%"><span class="col-label">CA Test 2</span><span class="max-label">Max 20</span></th>';
        h += '<th width="10%"><span class="col-label">Exam</span><span class="max-label">Max 60</span></th>';
        h += '<th width="8%"><span class="col-label">Total</span><span class="max-label">Max 100</span></th>';
        h += '<th width="10%">Grade &amp; Remark</th>';
    } else {
        h += '<th width="10%"><span class="col-label">Assignment</span><span class="max-label">Max 5</span></th>';
        h += '<th width="10%"><span class="col-label">Project</span><span class="max-label">Max 5</span></th>';
        h += '<th width="10%"><span class="col-label">Test</span><span class="max-label">Max 10</span></th>';
        h += '<th width="8%"><span class="col-label">Total</span><span class="max-label">Max 20</span></th>';
        h += '<th width="10%">Remark</th>';
    }
    h += '<th width="8%">Status</th>';
    h += '</tr>';
    $('#tableHead').html(h);
}

function loadStudents() {
    var classId   = $('#filterClass').val();
    var subjectId = $('#filterSubject').val();
    var term      = $('#filterTerm').val();
    var session   = $('#filterSession').val().trim();
    if (!classId || !subjectId || !session) {
        showPremiumModal('Please select a class, subject and enter an academic session.', 'Selection Required');
        return;
    }
    currentSession = session;
    currentTerm    = parseInt(term);
    currentSubject = parseInt(subjectId);
    $('#entrySection').show();
    buildTableHead();
    $('#scoreTableBody').html('<tr><td colspan="9" style="text-align:center;padding:30px;"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>');

    $.ajax({
        url: 'get_class_students.php',
        type: 'POST',
        data: { classId: classId },
        dataType: 'json',
        success: function(students) {
            // Also fetch existing scores for ALL terms of this session
            $.ajax({
                url: 'get_term_results.php',
                type: 'POST',
                data: { classId: classId, subjectId: subjectId, academicTerm: term, academicSession: session },
                dataType: 'json',
                success: function(scores) {
                    var scoreMap = {};
                    if (scores) {
                        scores.forEach(function(s){
                            if (!scoreMap[s.studentId]) scoreMap[s.studentId] = {};
                            scoreMap[s.studentId][s.academicTerm] = s;
                        });
                    }
                    window._studentsCache = students;
                    window._scoreMap      = scoreMap;
                    renderRows(students);
                },
                error: function(){ window._studentsCache = students; window._scoreMap = {}; renderRows(students); }
            });
        },
        error: function(){ $('#scoreTableBody').html('<tr><td colspan="9" style="color:red;text-align:center;padding:20px;">Failed to load students.</td></tr>'); }
    });
}

function renderRows(students) {
    var totalCols = 9;
    if (currentType === 'end_of_term') {
        if (currentTerm === 2) totalCols = 10;
        if (currentTerm === 3) totalCols = 11;
    }
    
    if (!students || students.length === 0) {
        $('#scoreTableBody').html('<tr><td colspan="' + totalCols + '" style="text-align:center;padding:30px;color:#999;">No students found for this class.</td></tr>');
        return;
    }
    var scoreMap = window._scoreMap || {};
    var html = '';
    students.forEach(function(s, i) {
        var sid  = s.studentId;
        var ex   = (scoreMap[sid] && scoreMap[sid][currentTerm]) || {};
        var name = s.fullName;
        html += '<tr id="row-'+sid+'">';
        html += '<td>'+(i+1)+'</td>';
        html += '<td style="font-weight:600;">'+name+'</td>';
        html += '<td style="color:#888;">'+s.admissionNumber+'</td>';
        
        if (currentType === 'end_of_term') {
            // Calculate and render 1st term total
            if (currentTerm >= 2) {
                var firstTermTotal = '—';
                if (scoreMap[sid] && scoreMap[sid][1]) {
                    var t1 = scoreMap[sid][1];
                    var t1_as = t1.assignmentScore !== null ? parseFloat(t1.assignmentScore) || 0 : 0;
                    var t1_pr = t1.projectScore !== null ? parseFloat(t1.projectScore) || 0 : 0;
                    var t1_mt = t1.midTermTest !== null ? parseFloat(t1.midTermTest) || 0 : 0;
                    var t1_t2 = t1.test2Score !== null ? parseFloat(t1.test2Score) || 0 : 0;
                    var t1_ex = t1.examScore !== null ? parseFloat(t1.examScore) || 0 : 0;
                    var t1_hasAny = t1.assignmentScore !== null || t1.projectScore !== null || t1.midTermTest !== null || t1.test2Score !== null || t1.examScore !== null;
                    if (t1_hasAny) {
                        firstTermTotal = (t1_as + t1_pr + t1_mt + t1_t2 + t1_ex).toFixed(1);
                    }
                }
                html += '<td style="font-weight:700; color:#4a5568; background:#f8fafb; text-align:center;">' + firstTermTotal + '</td>';
            }
            
            // Calculate and render 2nd term total
            if (currentTerm >= 3) {
                var secondTermTotal = '—';
                if (scoreMap[sid] && scoreMap[sid][2]) {
                    var t2 = scoreMap[sid][2];
                    var t2_as = t2.assignmentScore !== null ? parseFloat(t2.assignmentScore) || 0 : 0;
                    var t2_pr = t2.projectScore !== null ? parseFloat(t2.projectScore) || 0 : 0;
                    var t2_mt = t2.midTermTest !== null ? parseFloat(t2.midTermTest) || 0 : 0;
                    var t2_t2 = t2.test2Score !== null ? parseFloat(t2.test2Score) || 0 : 0;
                    var t2_ex = t2.examScore !== null ? parseFloat(t2.examScore) || 0 : 0;
                    var t2_hasAny = t2.assignmentScore !== null || t2.projectScore !== null || t2.midTermTest !== null || t2.test2Score !== null || t2.examScore !== null;
                    if (t2_hasAny) {
                        secondTermTotal = (t2_as + t2_pr + t2_mt + t2_t2 + t2_ex).toFixed(1);
                    }
                }
                html += '<td style="font-weight:700; color:#4a5568; background:#f8fafb; text-align:center;">' + secondTermTotal + '</td>';
            }

            // Calculate CA 1 from mid term scores
            var mid_as = ex.assignmentScore !== undefined && ex.assignmentScore !== null ? parseFloat(ex.assignmentScore) : 0;
            var mid_pr = ex.projectScore    !== undefined && ex.projectScore    !== null ? parseFloat(ex.projectScore) : 0;
            var mid_mt = ex.midTermTest     !== undefined && ex.midTermTest     !== null ? parseFloat(ex.midTermTest) : 0;
            var t1 = (ex.assignmentScore !== undefined && ex.assignmentScore !== null) || (ex.projectScore !== undefined && ex.projectScore !== null) || (ex.midTermTest !== undefined && ex.midTermTest !== null) ? (mid_as + mid_pr + mid_mt) : null;
            
            var t2   = ex.test2Score  !== undefined && ex.test2Score  !== null ? ex.test2Score  : '';
            var exam = ex.examScore   !== undefined && ex.examScore   !== null ? ex.examScore   : '';
            var total = (t1!==null?t1:0) + (parseFloat(t2)||0) + (parseFloat(exam)||0);
            var totalStr = (t1!==null||t2!==''||exam!=='') ? total.toFixed(1) : '—';
            
            html += '<td style="font-weight:700; color:#4a5568;"><input type="hidden" id="t1-'+sid+'" value="'+(t1!==null?t1:'')+'">'+(t1!==null?t1.toFixed(1):'—')+'</td>';
            html += '<td><input class="score-input '+(t2!==''?'saved':'')+'" type="number" min="0" max="20" step="0.25" id="t2-'+sid+'" value="'+t2+'" onchange="schedSave('+sid+',\'test2Score\',this,20)" oninput="calcTotal('+sid+')"></td>';
            html += '<td><input class="score-input '+(exam!==''?'saved':'')+'" type="number" min="0" max="60" step="0.25" id="ex-'+sid+'" value="'+exam+'" onchange="schedSave('+sid+',\'examScore\',this,60)" oninput="calcTotal('+sid+')"></td>';
            html += '<td class="total-cell" id="tot-'+sid+'">'+totalStr+'</td>';
            html += '<td id="rmk-'+sid+'">'+getGradeHtml(total, t1!==null||t2!==''||exam!=='')+'</td>';
        } else {
            var asgn = ex.assignmentScore !== undefined && ex.assignmentScore !== null ? ex.assignmentScore : '';
            var proj = ex.projectScore    !== undefined && ex.projectScore    !== null ? ex.projectScore    : '';
            var mid  = ex.midTermTest     !== undefined && ex.midTermTest     !== null ? ex.midTermTest     : '';
            var tot  = (parseFloat(asgn)||0) + (parseFloat(proj)||0) + (parseFloat(mid)||0);
            var totStr = (asgn!==''||proj!==''||mid!=='') ? tot.toFixed(2) : '—';
            html += '<td><input class="score-input '+(asgn!==''?'saved':'')+'" type="number" min="0" max="5" step="0.25" id="as-'+sid+'" value="'+asgn+'" onchange="schedSave('+sid+',\'assignmentScore\',this,5)" oninput="calcTotalMid('+sid+')"></td>';
            html += '<td><input class="score-input '+(proj!==''?'saved':'')+'" type="number" min="0" max="5" step="0.25" id="pr-'+sid+'" value="'+proj+'" onchange="schedSave('+sid+',\'projectScore\',this,5)" oninput="calcTotalMid('+sid+')"></td>';
            html += '<td><input class="score-input '+(mid!==''?'saved':'')+'" type="number" min="0" max="10" step="0.25" id="mt-'+sid+'" value="'+mid+'" onchange="schedSave('+sid+',\'midTermTest\',this,10)" oninput="calcTotalMid('+sid+')"></td>';
            html += '<td class="total-cell" id="tot-'+sid+'">'+totStr+'</td>';
            html += '<td id="rmk-'+sid+'">'+getMidRemark(tot, asgn!==''||proj!==''||mid!=='')+'</td>';
        }
        var hasData = false;
        if (ex && Object.keys(ex).length > 0) {
            hasData = ex.assignmentScore !== undefined || ex.projectScore !== undefined || ex.midTermTest !== undefined || ex.test2Score !== undefined || ex.examScore !== undefined;
        }
        html += '<td id="st-'+sid+'"><span style="font-size:11px;font-weight:700;color:'+(hasData?'#22c55e':'#b2bec3')+'">'+(hasData?'✓ Entered':'Pending')+'</span></td>';
        html += '</tr>';
    });
    $('#scoreTableBody').html(html);
}

function calcTotal(sid) {
    var t1   = parseFloat($('#t1-'+sid).val()) || 0;
    
    var t2Val = parseFloat($('#t2-'+sid).val());
    var t2 = (!isNaN(t2Val) && t2Val >= 0 && t2Val <= 20) ? t2Val : 0;
    
    var examVal = parseFloat($('#ex-'+sid).val());
    var exam = (!isNaN(examVal) && examVal >= 0 && examVal <= 60) ? examVal : 0;
    
    var tot  = t1 + t2 + exam;
    var hasAny = $('#t1-'+sid).val()!=='' || ($('#t2-'+sid).val()!=='' && !isNaN(t2Val) && t2Val >= 0 && t2Val <= 20) || ($('#ex-'+sid).val()!=='' && !isNaN(examVal) && examVal >= 0 && examVal <= 60);
    $('#tot-'+sid).text(hasAny ? tot.toFixed(1) : '—');
    $('#rmk-'+sid).html(getGradeHtml(tot, hasAny));
}

function calcTotalMid(sid) {
    var asVal = parseFloat($('#as-'+sid).val());
    var a = (!isNaN(asVal) && asVal >= 0 && asVal <= 5) ? asVal : 0;
    
    var prVal = parseFloat($('#pr-'+sid).val());
    var p = (!isNaN(prVal) && prVal >= 0 && prVal <= 5) ? prVal : 0;
    
    var mtVal = parseFloat($('#mt-'+sid).val());
    var m = (!isNaN(mtVal) && mtVal >= 0 && mtVal <= 10) ? mtVal : 0;
    
    var tot = a + p + m;
    var hasAny = ($('#as-'+sid).val()!=='' && !isNaN(asVal) && asVal >= 0 && asVal <= 5) || 
                 ($('#pr-'+sid).val()!=='' && !isNaN(prVal) && prVal >= 0 && prVal <= 5) || 
                 ($('#mt-'+sid).val()!=='' && !isNaN(mtVal) && mtVal >= 0 && mtVal <= 10);
    $('#tot-'+sid).text(hasAny ? tot.toFixed(2) : '—');
    $('#rmk-'+sid).html(getMidRemark(tot, hasAny));
}

function getGradeHtml(total, hasData) {
    if(!hasData) return '<span style="color:#ccc;font-size:11px;">—</span>';
    var pct = total; // already out of 100
    var grade, remark, color;
    if(pct>=80){grade='A1';remark='DISTINCTION';color='#065f46';bg='#d1fae5';}
    else if(pct>=75){grade='B2';remark='VERY GOOD';color='#1e40af';bg='#dbeafe';}
    else if(pct>=70){grade='B3';remark='VERY GOOD';color='#1e40af';bg='#dbeafe';}
    else if(pct>=65){grade='C4';remark='CREDIT';color='#713f12';bg='#fef9c3';}
    else if(pct>=60){grade='C5';remark='CREDIT';color='#713f12';bg='#fef9c3';}
    else if(pct>=55){grade='C6';remark='CREDIT';color='#713f12';bg='#fef9c3';}
    else if(pct>=50){grade='D7';remark='PASS';color='#9a3412';bg='#ffedd5';}
    else if(pct>=45){grade='E8';remark='PASS';color='#9a3412';bg='#ffedd5';}
    else{grade='F9';remark='FAIL';color='#991b1b';bg='#fee2e2';}
    return '<span style="background:'+bg+';color:'+color+';font-weight:800;font-size:11px;padding:3px 7px;border-radius:10px;">'+grade+' – '+remark+'</span>';
}

function getMidRemark(total, hasData) {
    if(!hasData) return '<span style="color:#ccc;font-size:11px;">—</span>';
    var r, bg, color;
    if(total>=18){r='EXCELLENT';bg='#d1fae5';color='#065f46';}
    else if(total>=14){r='VERY GOOD';bg='#dbeafe';color='#1e40af';}
    else if(total>=12){r='GOOD';bg='#fef9c3';color='#713f12';}
    else if(total>=10){r='FAIR';bg='#ffedd5';color='#9a3412';}
    else{r='POOR';bg='#fee2e2';color='#991b1b';}
    return '<span style="background:'+bg+';color:'+color+';font-weight:800;font-size:11px;padding:3px 7px;border-radius:10px;">'+r+'</span>';
}

function schedSave(sid, field, el, max) {
    var val = parseFloat($(el).val());
    if($(el).val() !== '' && (isNaN(val) || val < 0 || val > max)) {
        $(el).addClass('error-val').removeClass('saved');
        showPremiumModal("Invalid score: Value cannot be greater than the maximum allowed (" + max + ").", "Invalid Score Limit");
        $(el).val(''); // Instantly clear invalid input
        $(el).removeClass('error-val');
        
        // Recalculate totals immediately with the cleared value
        if (currentType === 'end_of_term') {
            calcTotal(sid);
        } else {
            calcTotalMid(sid);
        }
        return;
    }
    $(el).removeClass('error-val');
    
    // Update the in-memory cache IMMEDIATELY to prevent scores from being lost on tab switch
    if (!window._scoreMap[sid]) window._scoreMap[sid] = {};
    if (!window._scoreMap[sid][currentTerm]) window._scoreMap[sid][currentTerm] = {};
    
    var inputVal = $(el).val() !== '' ? $(el).val() : null;
    window._scoreMap[sid][currentTerm][field] = inputVal;
    
    // Capture state variables inside closure to prevent cross-tab background overwrite issues
    var termToSave = currentTerm;
    var typeToSave = currentType;
    
    clearTimeout(saveTimers[sid+'-'+field]);
    saveTimers[sid+'-'+field] = setTimeout(function(){ 
        doSave(sid, el, termToSave, typeToSave); 
    }, 800);
}

function doSave(sid, triggerEl, termToSave, typeToSave) {
    var termData = (window._scoreMap[sid] && window._scoreMap[sid][termToSave]) || {};
    var data = {
        studentId: sid,
        subjectId: currentSubject,
        academicTerm: termToSave,
        academicSession: currentSession,
        resultType: typeToSave
    };
    if(typeToSave === 'end_of_term') {
        data.test2Score = termData.test2Score !== undefined && termData.test2Score !== null ? termData.test2Score : '';
        data.examScore  = termData.examScore !== undefined && termData.examScore !== null ? termData.examScore : '';
    } else {
        data.assignmentScore = termData.assignmentScore !== undefined && termData.assignmentScore !== null ? termData.assignmentScore : '';
        data.projectScore    = termData.projectScore !== undefined && termData.projectScore !== null ? termData.projectScore : '';
        data.midTermTest     = termData.midTermTest !== undefined && termData.midTermTest !== null ? termData.midTermTest : '';
    }
    
    $.ajax({
        url: 'save_term_result.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                // Only style the DOM element if the user is still looking at the active tab and term
                if (currentTerm === termToSave && currentType === typeToSave) {
                    $(triggerEl).addClass('saved');
                    $('#st-'+sid).html('<span style="font-size:11px;font-weight:700;color:#22c55e;">✓ Saved</span>');
                }
                totalSaved++;
                $('#saveCounter').text(totalSaved + ' score(s) saved this session.');
            }
        }
    });
}

// Load classes when year group changes
$('#filterYearGroup').change(function(){
    var yg = $(this).val();
    if(!yg) { 
        $('#filterClass').html('<option value="">-- Select Year Group First --</option>'); 
        $('#filterSubject').html('<option value="">-- Select Class First --</option>'); 
        return; 
    }
    $.ajax({
        url: 'getTeacherAssignedClassesAndSubjects.php',
        type: 'GET',
        data: { type: 'classes', yearGroupId: yg },
        dataType: 'json',
        success: function(res){
            var opts = '<option value="">-- Select Class --</option>';
            if(res.success && res.data && res.data.length) {
                res.data.forEach(function(c){ 
                    opts += '<option value="'+c.classId+'">'+c.className+'</option>'; 
                });
            }
            $('#filterClass').html(opts);
            $('#filterSubject').html('<option value="">-- Select Class First --</option>');
        }
    });
});

// Load subjects assigned to the teacher when class changes
$('#filterClass').change(function(){
    var cid = $(this).val();
    if(!cid) { 
        $('#filterSubject').html('<option value="">-- Select Class First --</option>'); 
        return; 
    }
    $.ajax({
        url: 'getTeacherAssignedClassesAndSubjects.php',
        type: 'GET',
        data: { type: 'subjects', classId: cid },
        dataType: 'json',
        success: function(res){
            var opts = '<option value="">-- Select Subject --</option>';
            if(res.success && res.data && res.data.length) {
                res.data.forEach(function(s){ 
                    opts += '<option value="'+s.subjectId+'">'+s.subjectName+'</option>'; 
                });
            }
            $('#filterSubject').html(opts);
        }
    });
});

function showPremiumModal(message, title) {
    $('#premiumModalTitle').text(title || 'Validation Alert');
    $('#premiumModalMessage').text(message);
    $('#premiumAlertModal').addClass('active');
}

function closePremiumModal() {
    $('#premiumAlertModal').removeClass('active');
}
</script>

<!-- Premium Alert Modal -->
<div class="premium-modal-backdrop" id="premiumAlertModal">
    <div class="premium-modal-card">
        <div class="premium-modal-icon"><i class="fa fa-exclamation-triangle"></i></div>
        <div class="premium-modal-title" id="premiumModalTitle">Validation Alert</div>
        <div class="premium-modal-message" id="premiumModalMessage">Message goes here.</div>
        <button class="premium-modal-btn" onclick="closePremiumModal()">OK</button>
    </div>
</div>

</body>
</html>
