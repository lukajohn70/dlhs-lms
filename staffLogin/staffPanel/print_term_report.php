<?php
session_start();
require_once '../../db_connection/dlhs_db_connection.php';
require_once 'userExpiredSession.php';

$isAdmin = isset($_SESSION['adminLoggedIn']) && $_SESSION['adminLoggedIn'] === 'yes';
$isStaff = isset($_SESSION['staffLoggedIn']) && $_SESSION['staffLoggedIn'] === 'yes';

if (!$isAdmin && !$isStaff) {
    header('location:../index.php');
    exit;
}

$isFormTeacher = false;
if ($isStaff) {
    $staffId = $_SESSION['staffId'];
    $stmt = $connection->prepare("SELECT 1 FROM form_teacher_assignment WHERE teacherId = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $staffId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $isFormTeacher = true;
        }
        $stmt->close();
    }
}

if (!$isAdmin && !$isFormTeacher) {
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
            <p>This page is restricted to Form Teachers and Administrators only. If you believe this is an error, please contact the IT Administrator.</p>
            <a href='index.php' class='btn-back'>Back to Dashboard</a>
        </div>
    </body>
    </html>";
    exit;
}

// Fetch year groups
$yearGroups = $connection->query("SELECT * FROM yeargroup WHERE yearGroupId NOT IN (34,37) ORDER BY yearGroupId");

// Helpers for grading and remarks
function getGradeScale($pct) {
    if ($pct >= 85) return ['grade' => 'A1', 'remark' => 'DISTINCTION', 'color' => '#065f46', 'bg' => '#d1fae5'];
    if ($pct >= 80) return ['grade' => 'A1', 'remark' => 'VERY GOOD', 'color' => '#065f46', 'bg' => '#d1fae5'];
    if ($pct >= 75) return ['grade' => 'B2', 'remark' => 'VERY GOOD', 'color' => '#1e40af', 'bg' => '#dbeafe'];
    if ($pct >= 70) return ['grade' => 'B3', 'remark' => 'VERY GOOD', 'color' => '#1e40af', 'bg' => '#dbeafe'];
    if ($pct >= 65) return ['grade' => 'C4', 'remark' => 'GOOD', 'color' => '#713f12', 'bg' => '#fef9c3'];
    if ($pct >= 60) return ['grade' => 'C5', 'remark' => 'CREDIT', 'color' => '#713f12', 'bg' => '#fef9c3'];
    if ($pct >= 55) return ['grade' => 'C6', 'remark' => 'CREDIT', 'color' => '#713f12', 'bg' => '#fef9c3'];
    if ($pct >= 50) return ['grade' => 'D7', 'remark' => 'PASS', 'color' => '#9a3412', 'bg' => '#ffedd5'];
    if ($pct >= 45) return ['grade' => 'E8', 'remark' => 'PASS', 'color' => '#9a3412', 'bg' => '#ffedd5'];
    return ['grade' => 'F9', 'remark' => 'FAIL', 'color' => '#991b1b', 'bg' => '#fee2e2'];
}

function getRatingTicks($val) {
    $ticks = ['', '', '', '', ''];
    $val = intval($val);
    if ($val >= 1 && $val <= 5) {
        $ticks[$val - 1] = '✓';
    }
    return $ticks;
}

// Check if we are rendering a report card (either single or class-wide)
$studentId = intval($_GET['studentId'] ?? 0);
$classId   = intval($_GET['classId']   ?? 0);
$term      = intval($_GET['academicTerm'] ?? 0);
$session   = trim($_GET['academicSession'] ?? '');
$resultType = trim($_GET['resultType'] ?? 'end_of_term');

$isPrintMode = ($studentId > 0 || $classId > 0) && $term > 0 && !empty($session);

$termConfig = null;
if ($isPrintMode) {
    $configRes = $connection->query("SELECT * FROM dlhs_result_config WHERE academicSession = '" . $connection->real_escape_string($session) . "' AND academicTerm = $term LIMIT 1");
    if ($configRes) {
        $termConfig = $configRes->fetch_assoc();
    }
}

if ($isPrintMode):
    // Gather all students to render
    $studentsToRender = [];
    if ($studentId > 0) {
        $studentsToRender[] = $studentId;
    } else {
        $res = $connection->query("SELECT studentId FROM studentlogin WHERE classId = $classId AND status = 1 ORDER BY surname, firstName");
        while ($row = $res->fetch_assoc()) {
            $studentsToRender[] = intval($row['studentId']);
        }
    }
    
    if (empty($studentsToRender)) {
        die("No active students found for report generation.");
    }

    $termNames = [1 => 'FIRST TERM', 2 => 'SECOND TERM', 3 => 'THIRD TERM'];
    $termName = $termNames[$term] ?? 'UNKNOWN TERM';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= ($resultType === 'mid_term' ? 'Mid-Term' : 'End-of-Term') ?> Report | DLHS</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Great+Vibes&family=Inter:wght@400;600;700;800&display=swap');
        
        body { 
            font-family: 'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            background: #f0f3f6; 
            color: #1e293b; 
            margin: 0; 
            padding: 20px; 
            font-size: 9px; 
            line-height: 1.25; 
        }
        
        .report-page { 
            max-width: 960px; 
            margin: 0 auto 30px auto; 
            padding: 24px; 
            border: 1px solid #e2e8f0; 
            background: #fff; 
            page-break-after: always; 
            position: relative; 
            box-shadow: 0 10px 25px rgba(0, 51, 102, 0.05); 
            border-radius: 8px;
        }
        
        .report-page:last-child { 
            page-break-after: avoid; 
            margin-bottom: 0; 
        }

        /* Common layout tools */
        .w-100 { width: 100%; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: 700; }
        .uppercase { text-transform: uppercase; }
        
        /* Two columns structure */
        .two-cols { 
            display: flex; 
            gap: 16px; 
            margin-top: 10px;
        }
        .col-left { 
            width: 71%; 
        }
        .col-right { 
            width: 29%; 
        }

        /* Subjects table styling */
        .subject-table { 
            width: 100%; 
            border-collapse: collapse; 
            border: 1.5px solid #003366; 
            font-size: 8.5px;
        }
        .subject-table th { 
            background: #003366; 
            color: #fff; 
            border: 1px solid #003366; 
            padding: 5px 3px; 
            font-weight: 800; 
            text-align: center;
            vertical-align: middle;
        }
        .subject-table td { 
            border: 1px solid #cbd5e1; 
            padding: 4px 3px; 
            text-align: center; 
            font-weight: 600;
        }
        .subject-table tr:nth-child(even) { 
            background: #f8fafc; 
        }
        .subject-table td.sub-name { 
            text-align: left; 
            padding-left: 6px; 
            font-weight: 700; 
            color: #1e293b;
        }

        /* Sidebar table styling */
        .side-table { 
            width: 100%; 
            border-collapse: collapse; 
            border: 1px solid #cbd5e1; 
            font-size: 8px;
            margin-bottom: 10px;
        }
        .side-table th { 
            background: #f1f5f9; 
            border: 1px solid #cbd5e1; 
            padding: 4px; 
            font-weight: 800; 
            text-align: center;
        }
        .side-table td { 
            border: 1px solid #cbd5e1; 
            padding: 3px; 
            text-align: center;
            font-weight: 600;
        }
        .side-table td:first-child { 
            text-align: left; 
            padding-left: 5px;
            font-weight: 700;
            color: #334155;
        }
        .tick-cell {
            color: #003366;
            font-weight: 900;
            font-size: 10px;
        }

        /* Attendance grid inside right column */
        .att-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px;
            margin-bottom: 10px;
            background: #fafafa;
        }
        .att-title {
            font-weight: 800;
            color: #003366;
            border-bottom: 1.5px solid #003366;
            padding-bottom: 2px;
            margin-bottom: 5px;
            text-transform: uppercase;
            font-size: 8px;
        }
        .att-item {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dashed #e2e8f0;
            padding: 2.5px 0;
            font-weight: 700;
        }
        .att-item:last-child {
            border-bottom: none;
        }

        /* Comparative Chart styling */
        .comp-chart-box {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px;
            margin-bottom: 10px;
            background: #fafafa;
        }
        .chart-bar-wrap {
            position: relative; 
            width: 100%; 
            height: 11px; 
            background: #e2e8f0; 
            border-radius: 3px; 
            margin-bottom: 4px;
        }
        .chart-bar {
            position: absolute; 
            left: 0; 
            top: 0; 
            height: 100%; 
            border-radius: 3px;
        }

        /* Summary percentage strip */
        .summary-strip {
            display: flex;
            justify-content: space-between;
            background: #003366;
            color: #ffd700;
            padding: 6px 14px;
            font-weight: 800;
            font-size: 9.5px;
            border-radius: 4px;
            margin: 10px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Comments / Remarks section */
        .remarks-wrap {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px;
            background: #fafafa;
            margin-bottom: 10px;
        }
        .remark-row {
            margin-bottom: 6px;
        }
        .remark-row:last-child {
            margin-bottom: 0;
        }
        .remark-label {
            font-weight: 800;
            color: #003366;
            text-transform: uppercase;
            font-size: 8px;
            margin-bottom: 2px;
        }
        .remark-text {
            background: #fff;
            border: 1px solid #e2e8f0;
            padding: 4px 8px;
            border-radius: 3px;
            font-style: italic;
            font-weight: 600;
            min-height: 14px;
            color: #334155;
        }

        /* Signatures row */
        .signatures-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 16px;
            padding: 0 15px;
        }
        .signature-box {
            text-align: center;
            width: 220px;
            position: relative;
        }
        .signature-graphic-area {
            height: 50px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            margin-bottom: 2px;
        }
        .signature-line {
            border-top: 1.5px solid #003366;
            padding-top: 4px;
            font-weight: 800;
            color: #003366;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
        }
        .cursive-signature {
            font-family: 'Great Vibes', cursive;
            font-size: 26px;
            color: #1e3a8a;
            transform: rotate(-2deg);
            user-select: none;
        }
        .class-teacher-sig {
            font-family: 'Great Vibes', cursive;
            font-size: 22px;
            color: #475569;
            opacity: 0.85;
            user-select: none;
        }
        .official-seal {
            position: absolute;
            width: 65px;
            height: 65px;
            border: 1.5px dashed rgba(212, 175, 55, 0.8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            bottom: 5px;
            right: 0px;
            transform: rotate(12deg);
            pointer-events: none;
        }
        .official-seal-inner {
            width: 57px;
            height: 57px;
            border: 1px solid rgba(212, 175, 55, 0.8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 5px;
            font-weight: 800;
            color: rgba(212, 175, 55, 0.9);
            text-transform: uppercase;
            line-height: 1.1;
        }

        /* Non-printable bar styling */
        .no-print-bar { 
            background: #003366; 
            color: #fff; 
            padding: 10px 20px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            border-radius: 6px; 
            max-width: 960px; 
            margin: 0 auto 20px auto; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
            font-size: 11px;
        }
        .btn-print { 
            background: #ffd700; 
            color: #003366; 
            border: none; 
            padding: 6px 14px; 
            border-radius: 4px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: all 0.2s; 
            font-size: 11px;
        }
        .btn-print:hover { 
            filter: brightness(115%); 
        }

        @media print {
            body { 
                padding: 0; 
                background: #fff; 
            }
            .report-page { 
                border: none; 
                padding: 0; 
                box-shadow: none; 
                margin-bottom: 0; 
                border-radius: 0;
            }
            .no-print-bar { 
                display: none !important; 
            }
        }
    </style>
</head>
<body>
    <!-- Print utility bar -->
    <div class="no-print-bar">
        <div>
            <strong style="color:#ffd700; font-size:12px;">DLHS Kaduna Campus &mdash; Report Card Printing</strong>
            <span style="margin-left:12px; opacity:0.8;">Format: <strong><?= ($resultType === 'mid_term' ? 'Mid-Term Result' : 'End-of-Term Result') ?></strong></span>
        </div>
        <button class="btn-print" onclick="window.print()">Print Report Cards</button>
    </div>

    <?php
    foreach ($studentsToRender as $currStudentId):
        // 1. Fetch Student Details
        $studRes = $connection->query("
            SELECT s.*, c.className, y.yearGroupName, h.houseName, sp.sportName 
            FROM studentlogin s
            LEFT JOIN classes c  ON s.classId      = c.classId
            LEFT JOIN yeargroup y ON s.yearGroupId  = y.yearGroupId
            LEFT JOIN houses h   ON s.houseMasterId = h.houseId
            LEFT JOIN sports sp  ON s.sportMasterId = sp.sportId
            WHERE s.studentId = $currStudentId
        ");
        if (!$studRes) continue;
        $student = $studRes->fetch_assoc();
        if (!$student) continue;
        
        $studName = trim($student['surname'] . ' ' . $student['firstName'] . ' ' . $student['middleName']);
        $genderSymbol = strtoupper($student['gender']) === 'FEMALE' ? 'FEMALE' : 'MALE';
        
        // 2. Fetch Assessment & Comments
        $assessRes = $connection->query("SELECT * FROM dlhs_student_assessments WHERE studentId = $currStudentId AND academicTerm = $term AND academicSession = '$session'");
        $assessments = $assessRes ? ($assessRes->fetch_assoc() ?: []) : [];

        // 3. Fetch Form Teacher
        $formTeacherName = 'Class Teacher';
        $classIdVal = intval($student['classId']);
        $ftRes = $connection->query("
            SELECT s.surname, s.firstName 
            FROM form_teacher_assignment f 
            JOIN stafflogin s ON f.teacherId = s.staffId 
            WHERE f.classId = $classIdVal LIMIT 1
        ");
        if ($ftRes && $ftRow = $ftRes->fetch_assoc()) {
            $formTeacherName = $ftRow['firstName'] . ' ' . $ftRow['surname'];
        }

        // 4. Calculate total class student count
        $cntRes = $connection->query("SELECT COUNT(*) FROM studentlogin WHERE classId = $classIdVal AND status = 1");
        $totalInClass = $cntRes ? $cntRes->fetch_row()[0] : 0;

        // 5. Calculate class average per subject and class rankings
        $classAverages = [];
        $avgRes = $connection->query("
            SELECT r.subjectId, s.subjectName, AVG(r.test1Score + r.test2Score + r.examScore) as classAvg
            FROM dlhs_term_results r
            JOIN studentlogin stud ON r.studentId = stud.studentId
            JOIN subjects s ON r.subjectId = s.subjectId
            WHERE stud.classId = $classIdVal 
              AND r.academicSession = '$session' 
              AND r.academicTerm = $term
            GROUP BY r.subjectId, s.subjectName
        ");
        if ($avgRes) {
            while ($row = $avgRes->fetch_assoc()) {
                $classAverages[strtoupper($row['subjectName'])] = round($row['classAvg'], 2);
            }
        }

        // Rank calculation
        $studentRanks = [];
        $rankQuery = $connection->query("
            SELECT r.studentId, AVG(r.test1Score + r.test2Score + r.examScore) as termAvg
            FROM dlhs_term_results r
            JOIN studentlogin s ON r.studentId = s.studentId
            WHERE s.classId = $classIdVal 
              AND r.academicSession = '$session' 
              AND r.academicTerm = $term
            GROUP BY r.studentId
            ORDER BY termAvg DESC
        ");
        if ($rankQuery) {
            $rank = 1;
            while ($rRow = $rankQuery->fetch_assoc()) {
                $studentRanks[intval($rRow['studentId'])] = sprintf('%02d', $rank++);
            }
        }
        $myPosition = $studentRanks[$currStudentId] ?? 'N/A';

        // Fetch scores for all terms for this student
        $resultsBySubject = [];
        $res = $connection->query("
            SELECT r.*, s.subjectName 
            FROM dlhs_term_results r 
            JOIN subjects s ON r.subjectId = s.subjectId 
            WHERE r.studentId = $currStudentId AND r.academicSession = '$session'
        ");
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $resultsBySubject[strtoupper($row['subjectName'])][$row['academicTerm']] = $row;
            }
        }
    ?>

    <div class="report-page">
        <?php if ($resultType === 'mid_term'): ?>
            <!-- ============================== PATH A: MID TERM RESULT ============================== -->
            <div class="midterm-header" style="text-align: center; margin-bottom: 18px;">
                <div style="font-size: 19px; font-weight: 800; color: #003366; letter-spacing: 0.5px;">DEEPER LIFE HIGH SCHOOL</div>
                <div style="font-size: 13px; font-weight: 700; color: #003366; text-transform: uppercase; margin-top: 1px;">KADUNA CAMPUS</div>
                <div style="font-size: 10px; font-weight: 700; color: #555; margin-top: 2px; text-transform: uppercase;"><?= $termName ?> <?= htmlspecialchars($session) ?> SESSION</div>
                <div style="font-size: 12px; font-weight: 800; color: #003366; text-transform: uppercase; margin-top: 5px; letter-spacing: 0.5px; border-bottom: 2px solid #003366; display: inline-block; padding-bottom: 2px;">MID-TERM RESULT</div>
            </div>

            <!-- Info Grid -->
            <table style="width: 100%; margin-bottom: 12px; border-collapse: collapse; border: 1.5px solid #003366; font-size: 10px;">
                <tr>
                    <td style="padding: 5px 8px; border-right: 1.5px solid #003366; border-bottom: 1.5px solid #003366; width: 50%; font-weight: 700; color: #003366;">NAME: <span style="color: #1e293b;"><?= htmlspecialchars(strtoupper($studName)) ?></span></td>
                    <td style="padding: 5px 8px; border-bottom: 1.5px solid #003366; width: 50%; font-weight: 700; color: #003366;">CLASS: <span style="color: #1e293b;"><?= htmlspecialchars(strtoupper($student['yearGroupName'] . ' ' . $student['className'])) ?></span></td>
                </tr>
                <tr>
                    <td style="padding: 5px 8px; border-right: 1.5px solid #003366; font-weight: 700; color: #003366;">GENDER: <span style="color: #1e293b;"><?= htmlspecialchars(strtoupper($genderSymbol)) ?></span></td>
                    <td style="padding: 5px 8px; font-weight: 700; color: #003366;">HOUSE: <span style="color: #1e293b;"><?= htmlspecialchars(strtoupper($student['houseName'] ?? 'N/A')) ?></span></td>
                </tr>
            </table>

            <!-- Subjects Table -->
            <table class="subject-table" style="margin-bottom: 15px;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding-left: 8px; width: 42%;">SUBJECTS</th>
                        <th style="width: 11%;">ASSIGN-<br>MENT (05)</th>
                        <th style="width: 11%;">PROJECT<br>(05)</th>
                        <th style="width: 11%;">TEST<br>(10)</th>
                        <th style="width: 13%;">TOTAL<br>SCORE (20)</th>
                        <th style="text-align: left; padding-left: 8px; width: 12%;">REMARK</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $subjectSum = 0;
                    $subjectCount = 0;
                    $printedSubjects = [];

                    // 1. Loop through registered subjects
                    foreach ($resultsBySubject as $subName => $termsData) {
                        if (isset($termsData[$term])) {
                            $rRow = $termsData[$term];
                            $asgn  = $rRow['assignmentScore'] !== null ? floatval($rRow['assignmentScore']) : null;
                            $proj  = $rRow['projectScore'] !== null ? floatval($rRow['projectScore']) : null;
                            $tScore = $rRow['midTermTest'] !== null ? floatval($rRow['midTermTest']) : null;
                            
                            $tot = null;
                            $remarkStr = '';
                            if ($asgn !== null || $proj !== null || $tScore !== null) {
                                $tot = ($asgn ?? 0) + ($proj ?? 0) + ($tScore ?? 0);
                                $subjectSum += $tot;
                                $subjectCount++;

                                if ($tot >= 18)      $remarkStr = 'EXCELLENT';
                                elseif ($tot >= 15)  $remarkStr = 'VERY GOOD';
                                elseif ($tot >= 12)  $remarkStr = 'GOOD';
                                elseif ($tot >= 10)  $remarkStr = 'FAIR';
                                else                 $remarkStr = 'POOR';
                            }

                            $printedSubjects[] = $subName;
                            ?>
                            <tr>
                                <td class="sub-name"><?= htmlspecialchars($subName) ?></td>
                                <td><?= $asgn !== null ? floatval($asgn) : '—' ?></td>
                                <td><?= $proj !== null ? floatval($proj) : '—' ?></td>
                                <td><?= $tScore !== null ? floatval($tScore) : '—' ?></td>
                                <td style="font-weight: 700; color: #003366;"><?= $tot !== null ? number_format($tot, 2) : '—' ?></td>
                                <td style="text-align: left; padding-left: 8px; font-weight: 700;"><?= $remarkStr ?></td>
                            </tr>
                            <?php
                        }
                    }

                    // 2. Render standard extra subjects at bottom if they aren't already printed
                    $extras = ['VISUAL ARTS', 'AI/ROBOTICS', 'LET'];
                    foreach ($extras as $extraSub) {
                        if (!in_array($extraSub, $printedSubjects)) {
                            ?>
                            <tr>
                                <td class="sub-name"><?= $extraSub ?></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <?php
                        }
                    }

                    $classAverageVal = $subjectCount > 0 ? ($subjectSum / $subjectCount) : 0;
                    ?>
                    
                    <!-- Totals and Averages -->
                    <tr style="background: #f1f5f9 !important; font-weight: 800;">
                        <td colspan="4" style="text-align: right; padding-right: 12px; font-weight: 800;">TOTAL:</td>
                        <td style="font-weight: 800; color: #003366; text-align: center;"><?= number_format($subjectSum, 2) ?></td>
                        <td></td>
                    </tr>
                    <tr style="background: #e2e8f0 !important; font-weight: 800;">
                        <td colspan="4" style="text-align: right; padding-right: 12px; font-weight: 800;">AVERAGE:</td>
                        <td style="font-weight: 800; color: #003366; text-align: center;"><?= number_format($classAverageVal, 2) ?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <!-- Comments Area -->
            <div class="remarks-wrap" style="margin-top: 15px;">
                <div class="remark-row">
                    <div class="remark-label">CLASS MASTER/MISTRESS' REMARK:</div>
                    <div class="remark-text"><?= htmlspecialchars($assessments['midTermComment'] ?? 'WELL BEHAVED AND HARDWORKING') ?></div>
                </div>
                <div class="remark-row">
                    <div class="remark-label">HOUSE MASTER/MISTRESS' REMARK:</div>
                    <div class="remark-text">WELL BEHAVED</div>
                </div>
                <div class="remark-row">
                    <div class="remark-label">GAMES MASTERS' REMARK:</div>
                    <div class="remark-text">ACTIVE IN SPORTS</div>
                </div>
                <div class="remark-row">
                    <div class="remark-label">PRINCIPAL'S COMMENT:</div>
                    <div class="remark-text"><?= htmlspecialchars($assessments['principalRemark'] ?? 'GOOD RESULT! KEEP IT UP.') ?></div>
                </div>
            </div>

            <!-- Signatures Row -->
            <div class="signatures-row" style="margin-top: 30px;">
                <div class="signature-box">
                    <div class="signature-graphic-area">
                        <div class="class-teacher-sig"><?= htmlspecialchars($formTeacherName) ?></div>
                    </div>
                    <div class="signature-line">Class Teacher Signature</div>
                </div>
                <div class="signature-box">
                    <div class="signature-graphic-area">
                        <div class="cursive-signature">Mrs. O. O. Olayinka</div>
                        <div class="official-seal">
                            <div class="official-seal-inner">
                                DLHS KADUNA<br>CAMPUS<br>★ SEAL ★
                            </div>
                        </div>
                    </div>
                    <div class="signature-line">Principal Signature</div>
                </div>
            </div>

        <?php else: ?>
            <!-- ============================== PATH B: END OF TERM RESULT ============================== -->
            <!-- School Header -->
            <div class="school-header-wrap" style="text-align: center; border-bottom: 2.5px double #003366; padding-bottom: 8px; margin-bottom: 10px;">
                <div style="font-size: 20px; font-weight: 800; color: #003366; letter-spacing: 0.5px;">DEEPER LIFE HIGH SCHOOL</div>
                <div style="font-size: 8.5px; font-weight: 700; color: #334155; margin-top: 2px; word-spacing: 1px;">KM 16, EASTERN BYE-PASS, MARABA RIDO KADUNA</div>
                <div style="font-size: 8px; font-weight: 600; color: #64748b; margin-top: 1px;">TEL:08158190115; DLHSEXAMSKADUNA@YAHOO.COM; WWW.DEEPERLIFEHIGHSCHOOL.ORG</div>
                <div style="font-size: 8.5px; font-weight: 800; color: #d4af37; margin-top: 2px; letter-spacing: 0.5px; text-transform: uppercase;">MOTTO: LEADERSHIP WITH DISTINCTION</div>
                <div style="font-size: 11px; font-weight: 800; color: #003366; margin-top: 4px; letter-spacing: 0.5px; text-transform: uppercase;"><?= $termName ?>, <?= htmlspecialchars($session) ?> SESSION</div>
            </div>

            <div class="two-cols">
                <!-- Left Column (Personal Info & Subject Grid) -->
                <div class="col-left">
                    <!-- Personal Info Grid -->
                    <table style="width: 100%; border-collapse: collapse; border: 1.5px solid #003366; font-size: 8.5px; margin-bottom: 8px;">
                        <tr>
                            <td style="padding: 4px 6px; border-right: 1.5px solid #003366; border-bottom: 1.5px solid #003366; font-weight: 800; color: #003366; width: 55%;">FULLNAME: <span style="color:#1e293b; font-weight:700;"><?= htmlspecialchars(strtoupper($studName)) ?></span></td>
                            <td style="padding: 4px 6px; border-right: 1.5px solid #003366; border-bottom: 1.5px solid #003366; font-weight: 800; color: #003366; width: 22%;">SEX: <span style="color:#1e293b; font-weight:700;"><?= htmlspecialchars(strtoupper($genderSymbol)) ?></span></td>
                            <td style="padding: 4px 6px; border-bottom: 1.5px solid #003366; font-weight: 800; color: #003366; width: 23%;">POSITION: <span style="color:#1e293b; font-weight:700;"><?= $myPosition ?></span></td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 6px; border-right: 1.5px solid #003366; font-weight: 800; color: #003366;">CURRENT CLASS: <span style="color:#1e293b; font-weight:700;"><?= htmlspecialchars(strtoupper($student['yearGroupName'] . ' ' . $student['className'])) ?></span></td>
                            <td style="padding: 4px 6px; border-right: 1.5px solid #003366; font-weight: 800; color: #003366;">NUMBER IN CLASS: <span style="color:#1e293b; font-weight:700;"><?= $totalInClass ?></span></td>
                            <td style="padding: 4px 6px; font-weight: 800; color: #003366;">HOUSE: <span style="color:#1e293b; font-weight:700;"><?= htmlspecialchars(strtoupper($student['houseName'] ?? 'N/A')) ?></span></td>
                        </tr>
                    </table>

                    <!-- Subjects Grid -->
                    <table class="subject-table">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding-left: 6px; width: 26%;">SUBJECTS</th>
                                <th style="width: 7%;">1ST<br>TEST<br>(20%)</th>
                                <th style="width: 7%;">2ND<br>TEST<br>(20%)</th>
                                <th style="width: 7%;">EXAM<br>(60%)</th>
                                <th style="width: 7.5%;">1ST<br>TERM<br>TOTAL</th>
                                <th style="width: 7.5%;">2ND<br>TERM<br>TOTAL</th>
                                <th style="width: 7.5%;">3RD<br>TERM<br>TOTAL</th>
                                <th style="width: 7.5%;">CUMMU-<br>LATIVE</th>
                                <th style="width: 5%;">GRD</th>
                                <th style="width: 8%;">STUD.<br>AVG</th>
                                <th style="width: 8%;">CLASS<br>AVG</th>
                                <th style="text-align: left; padding-left: 5px; width: 9.5%;">REMARK</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $staticSubjects = [
                                'ENGLISH LANGUAGE', 'MATHEMATICS', 'AGRICULTURAL SCIENCE', 'ANIMAL HUSBANDRY',
                                'BIOLOGY', 'CATERING CRAFT PRACTICE', 'CHRISTIAN RELIGIOUS STUDIES',
                                'CIVIC EDUCATION', 'COMMERCE', 'COMPUTER STUDIES', 'DATA PROCESSING',
                                'ECONOMICS', 'FINANCIAL ACCOUNTING', 'FOOD AND NUTRITION', 'FRENCH LANGUAGE',
                                'GOVERNMENT', 'LITERATURE IN ENGLISH', 'VISUAL ARTS', 'AI/ROBOTICS', 'LET'
                            ];

                            $totalsSum = [
                                'test1' => 0, 'test2' => 0, 'exam' => 0,
                                't1' => 0, 't2' => 0, 't3' => 0,
                                'cum' => 0, 'studAvg' => 0, 'classAvg' => 0
                            ];

                            $scoredCount = 0;

                            foreach ($staticSubjects as $idx => $subName) {
                                $t1 = $resultsBySubject[$subName][1] ?? null;
                                $t2 = $resultsBySubject[$subName][2] ?? null;
                                $t3 = $resultsBySubject[$subName][3] ?? null;

                                $t1Total = $t1 ? (($t1['test1Score'] ?? 0) + ($t1['test2Score'] ?? 0) + ($t1['examScore'] ?? 0)) : null;
                                $t2Total = $t2 ? (($t2['test1Score'] ?? 0) + ($t2['test2Score'] ?? 0) + ($t2['examScore'] ?? 0)) : null;
                                $t3Total = $t3 ? (($t3['test1Score'] ?? 0) + ($t3['test2Score'] ?? 0) + ($t3['examScore'] ?? 0)) : null;

                                // Active Term Scores
                                $activeResult = $resultsBySubject[$subName][$term] ?? null;
                                $test1Active = $activeResult ? $activeResult['test1Score'] : null;
                                $test2Active = $activeResult ? $activeResult['test2Score'] : null;
                                $examActive = $activeResult ? $activeResult['examScore'] : null;

                                // Cumulative & Average
                                $activeTerms = 0;
                                $cumulativeSum = 0;
                                if ($t1Total !== null) { $activeTerms++; $cumulativeSum += $t1Total; }
                                if ($t2Total !== null && $term >= 2) { $activeTerms++; $cumulativeSum += $t2Total; }
                                if ($t3Total !== null && $term >= 3) { $activeTerms++; $cumulativeSum += $t3Total; }

                                $studAvg = $activeTerms > 0 ? ($cumulativeSum / $activeTerms) : null;
                                $classAvg = $classAverages[$subName] ?? null;

                                $gradeScale = $studAvg !== null ? getGradeScale($studAvg) : null;

                                // Accumulate totals for bottom bar
                                if ($studAvg !== null) {
                                    $scoredCount++;
                                    $totalsSum['test1'] += $test1Active ?? 0;
                                    $totalsSum['test2'] += $test2Active ?? 0;
                                    $totalsSum['exam'] += $examActive ?? 0;
                                    $totalsSum['t1'] += $t1Total ?? 0;
                                    $totalsSum['t2'] += $t2Total ?? 0;
                                    $totalsSum['t3'] += $t3Total ?? 0;
                                    $totalsSum['cum'] += $cumulativeSum;
                                    $totalsSum['studAvg'] += $studAvg;
                                    $totalsSum['classAvg'] += $classAvg ?? 0;
                                }

                                ?>
                                <tr>
                                    <td class="sub-name"><?= $subName ?></td>
                                    <td><?= $test1Active !== null ? floatval($test1Active) : '—' ?></td>
                                    <td><?= $test2Active !== null ? floatval($test2Active) : '—' ?></td>
                                    <td><?= $examActive !== null ? floatval($examActive) : '—' ?></td>
                                    
                                    <td style="font-weight: 700;"><?= $t1Total !== null ? floatval($t1Total) : '—' ?></td>
                                    <td style="font-weight: 700;"><?= $t2Total !== null && $term >= 2 ? floatval($t2Total) : '—' ?></td>
                                    <td style="font-weight: 700;"><?= $t3Total !== null && $term >= 3 ? floatval($t3Total) : '—' ?></td>
                                    
                                    <td style="font-weight: 700; color: #003366;"><?= $activeTerms > 0 ? floatval($cumulativeSum) : '—' ?></td>
                                    <td style="font-weight: 700;"><?= $gradeScale ? $gradeScale['grade'] : '—' ?></td>
                                    <td style="font-weight: 700;"><?= $studAvg !== null ? number_format($studAvg, 1) : '—' ?></td>
                                    <td><?= $classAvg !== null ? number_format($classAvg, 1) : '—' ?></td>
                                    <td style="text-align: left; padding-left: 5px; font-weight: 700; font-size:7.5px;"><?= $gradeScale ? $gradeScale['remark'] : '—' ?></td>
                                </tr>
                                <?php
                            }

                            // Averages
                            $myAcademicAverage = $scoredCount > 0 ? ($totalsSum['studAvg'] / $scoredCount) : 0;
                            $overallClassAverage = $scoredCount > 0 ? ($totalsSum['classAvg'] / $scoredCount) : 0;
                            ?>

                            <!-- CUMMULATIVE ROW -->
                            <tr style="background: #f1f5f9 !important; font-weight: 800; font-size: 8px;">
                                <td class="sub-name" style="font-weight: 800;">CUMMULATIVE:</td>
                                <td><?= number_format($totalsSum['test1'], 2) ?></td>
                                <td><?= number_format($totalsSum['test2'], 2) ?></td>
                                <td><?= number_format($totalsSum['exam'], 2) ?></td>
                                <td><?= number_format($totalsSum['t1'], 2) ?></td>
                                <td><?= $term >= 2 ? number_format($totalsSum['t2'], 2) : '—' ?></td>
                                <td><?= $term >= 3 ? number_format($totalsSum['t3'], 2) : '—' ?></td>
                                <td style="color: #003366; font-weight: 800;"><?= number_format($totalsSum['cum'], 2) ?></td>
                                <td></td>
                                <td><?= number_format($myAcademicAverage, 2) ?></td>
                                <td><?= number_format($overallClassAverage, 2) ?></td>
                                <td></td>
                            </tr>

                            <!-- CUMMULATIVE (%) ROW -->
                            <tr style="background: #e2e8f0 !important; font-weight: 800; font-size: 8px;">
                                <td class="sub-name" style="font-weight: 800;">CUMMULATIVE (%):</td>
                                <td><?= number_format(($totalsSum['test1'] / 400) * 100, 2) ?>%</td>
                                <td><?= number_format(($totalsSum['test2'] / 400) * 100, 2) ?>%</td>
                                <td><?= number_format(($totalsSum['exam'] / 1200) * 100, 2) ?>%</td>
                                <td><?= number_format(($totalsSum['t1'] / 2000) * 100, 2) ?>%</td>
                                <td><?= $term >= 2 ? number_format(($totalsSum['t2'] / 2000) * 100, 2) . '%' : '—' ?></td>
                                <td><?= $term >= 3 ? number_format(($totalsSum['t3'] / 2000) * 100, 2) . '%' : '—' ?></td>
                                <td style="color: #003366; font-weight: 800;"><?= number_format(($totalsSum['cum'] / 4000) * 100, 2) ?>%</td>
                                <td></td>
                                <td><?= number_format($myAcademicAverage, 2) ?></td>
                                <td><?= number_format($overallClassAverage, 2) ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Right Column (Attendance, Chart, Character, Psychomotor, Scale) -->
                <div class="col-right">
                    <!-- Attendance Box -->
                    <div class="att-box">
                        <div class="att-title">Attendance Summary</div>
                        <?php
                        $daysPresent = intval($assessments['presentDays'] ?? 120);
                        $daysAbsent = intval(($assessments['totalDays'] ?? 120) - $daysPresent);
                        $totalDays = intval($assessments['totalDays'] ?? 120);
                        if ($totalDays <= 0) $totalDays = 120;
                        ?>
                        <div class="att-item"><span>Present:</span><span><?= $daysPresent ?></span></div>
                        <div class="att-item"><span>Absent:</span><span><?= $daysAbsent ?></span></div>
                        <div class="att-item"><span>Total Days:</span><span><?= $totalDays ?></span></div>
                    </div>

                    <!-- Comparative Chart -->
                    <div class="comp-chart-box">
                        <div class="att-title">Comparative Chart</div>
                        <div style="font-size: 8px; color: #64748b; margin-bottom: 4px; display:flex; justify-content:space-between;">
                            <span>0</span><span>25</span><span>50</span><span>75</span><span>100</span>
                        </div>
                        <div class="chart-bar-wrap">
                            <div class="chart-bar" style="width: <?= $myAcademicAverage ?>%; background: #ffd700;"></div>
                            <span style="position:absolute; left:4px; top:1.5px; font-size:7px; font-weight:800; color:#003366;">Student Avg (<?= number_format($myAcademicAverage, 1) ?>%)</span>
                        </div>
                        <div class="chart-bar-wrap" style="margin-bottom:0;">
                            <div class="chart-bar" style="width: <?= $overallClassAverage ?>%; background: #003366;"></div>
                            <span style="position:absolute; left:4px; top:1.5px; font-size:7px; font-weight:800; color:#fff;">Class Avg (<?= number_format($overallClassAverage, 1) ?>%)</span>
                        </div>
                    </div>

                    <!-- Character Development -->
                    <div class="att-box" style="padding: 4px;">
                        <div class="att-title" style="margin-bottom:3px; padding-bottom:1px;">Character Development</div>
                        <table class="side-table" style="margin-bottom:0;">
                            <thead>
                                <tr><th>Trait</th><th>5</th><th>4</th><th>3</th><th>2</th><th>1</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $charTraits = [
                                    'punctuality' => 'Punctuality', 'neatness' => 'Neatness',
                                    'politeness' => 'Politeness', 'honesty' => 'Honesty',
                                    'teamSpirit' => 'Team Spirit', 'leadership' => 'Leadership',
                                    'helpingOthers' => 'Helping Others', 'emotionalStability' => 'Emotional Stability',
                                    'health' => 'Health', 'attitudeToWork' => 'Attitude to work',
                                    'attentiveness' => 'Attentiveness', 'perseverance' => 'Perseverance',
                                    'spokenEnglish' => 'Spoken English'
                                ];
                                $characterSum = 0;
                                foreach ($charTraits as $k => $label) {
                                    $val = intval($assessments[$k] ?? 5);
                                    if ($val <= 0) $val = 5;
                                    $characterSum += $val;
                                    $ticks = getRatingTicks($val);
                                    ?>
                                    <tr>
                                        <td><?= $label ?></td>
                                        <?php for($i=4; $i>=0; $i--): ?>
                                            <td class="tick-cell"><?= $ticks[$i] ? '✅' : '' ?></td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Psychomotor Skills -->
                    <div class="att-box" style="padding: 4px; margin-bottom: 5px;">
                        <div class="att-title" style="margin-bottom:3px; padding-bottom:1px;">Psychomotor Skills</div>
                        <table class="side-table" style="margin-bottom:0;">
                            <thead>
                                <tr><th>Skill Capability</th><th>5</th><th>4</th><th>3</th><th>2</th><th>1</th></tr>
                            </thead>
                            <tbody>
                                <?php
                                $psySkills = [
                                    'handwriting' => 'Handwriting', 'verbalFluency' => 'Verbal Fluency',
                                    'sports' => 'Sports', 'handlingTools' => 'Handling Tools',
                                    'musical' => 'Musical', 'drawingPainting' => 'Drawing/Painting'
                                ];
                                $psychomotorSum = 0;
                                foreach ($psySkills as $k => $label) {
                                    $val = intval($assessments[$k] ?? 5);
                                    if ($val <= 0) $val = 5;
                                    $psychomotorSum += $val;
                                    $ticks = getRatingTicks($val);
                                    ?>
                                    <tr>
                                        <td><?= $label ?></td>
                                        <?php for($i=4; $i>=0; $i--): ?>
                                            <td class="tick-cell"><?= $ticks[$i] ? '✅' : '' ?></td>
                                        <?php endfor; ?>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Scale Legend -->
                    <div style="font-size: 7px; color: #475569; font-weight:700; line-height:1.2; text-align:center;">
                        SCALE: 5-EXCELLENT, 4-VERY GOOD, 3-GOOD, 2-FAIR, 1-POOR
                    </div>
                </div>
            </div>

            <!-- Summary Bar -->
            <?php
            $attendancePercentage = ($daysPresent / $totalDays) * 100;
            $characterPercentage = ($characterSum / 65) * 100;
            $psychomotorPercentage = ($psychomotorSum / 30) * 100;
            ?>
            <div class="summary-strip">
                <span>% ACADEMIC: <?= number_format($myAcademicAverage, 2) ?></span>
                <span>ATTENDANCE: <?= number_format($attendancePercentage, 1) ?>%</span>
                <span>CHARACTER: <?= number_format($characterPercentage, 1) ?>%</span>
                <span>PSYCHOMOTOR: <?= number_format($psychomotorPercentage, 1) ?>%</span>
            </div>

            <!-- Bottom comments grid -->
            <div style="display: flex; gap: 15px; font-size: 8.5px;">
                <!-- Awards and Comments (71% width) -->
                <div style="width: 71%; display:flex; flex-direction:column; gap:6px;">
                    <div class="remarks-wrap" style="padding: 6px; margin-bottom: 0;">
                        <div class="bold" style="color:#003366; margin-bottom:4px;">AWARDS/PRIZES</div>
                        <div style="font-weight:700; color:#475569;">1. NILL</div>
                        <div style="font-weight:700; color:#475569;">2. NILL</div>
                    </div>
                    <div class="remarks-wrap" style="padding: 6px; margin-bottom: 0; flex:1;">
                        <div class="remark-row">
                            <span class="bold" style="color:#003366;">Overall Evaluation:</span>
                            <span style="font-style:italic; font-weight:700; color:#334155; margin-left:6px;"><?= htmlspecialchars(strtoupper($assessments['classTeacherComment'] ?? 'SHE IS FOCUSED')) ?></span>
                        </div>
                        <div class="remark-row" style="margin-top: 4px;">
                            <span class="bold" style="color:#003366;">Class Teacher's Comment:</span>
                            <span style="font-style:italic; font-weight:700; color:#334155; margin-left:6px;"><?= htmlspecialchars($assessments['endOfTermComment'] ?? '') ?></span>
                        </div>
                        <div class="remark-row" style="margin-top: 4px;">
                            <span class="bold" style="color:#003366;">Principal's Remark:</span>
                            <span style="font-style:italic; font-weight:700; color:#334155; margin-left:6px;"><?= htmlspecialchars($assessments['principalRemark'] ?? 'A GOOD PERFORMANCE, BUT YOU CAN DO BETTER') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Calendar and Signatures (29% width) -->
                <div style="width: 29%; display:flex; flex-direction:column; justify-content:space-between; border: 1px solid #cbd5e1; padding: 6px; border-radius:4px; background:#fafafa;">
                    <div>
                        <div class="bold" style="color:#003366; border-bottom:1.5px solid #003366; padding-bottom:2px; margin-bottom:5px; text-transform:uppercase; font-size:8px;">Calendar &amp; Dates</div>
                        <div class="att-item"><span>Vacation Date:</span><span><?= !empty($termConfig['termEndDate']) ? date('d/M/Y', strtotime($termConfig['termEndDate'])) : '16/Dec/2023' ?></span></div>
                        <div class="att-item"><span>Resumption Date:</span><span><?= !empty($termConfig['nextTermResumptionDate']) ? date('d/M/Y', strtotime($termConfig['nextTermResumptionDate'])) : '06/Jan/2024' ?></span></div>
                        <div class="att-item"><span>Sport Activities:</span><span><?= htmlspecialchars(strtoupper($student['sportName'] ?? 'BADMINTON')) ?></span></div>
                    </div>
                </div>
            </div>

            <!-- Signatures Row -->
            <div class="signatures-row" style="margin-top: 15px;">
                <div class="signature-box">
                    <div class="signature-graphic-area">
                        <div class="class-teacher-sig"><?= htmlspecialchars($formTeacherName) ?></div>
                    </div>
                    <div class="signature-line">Class Teacher Signature</div>
                </div>
                <div class="signature-box">
                    <div class="signature-graphic-area">
                        <div class="cursive-signature">Mrs. O. O. Olayinka</div>
                        <div class="official-seal">
                            <div class="official-seal-inner">
                                DLHS KADUNA<br>CAMPUS<br>★ SEAL ★
                            </div>
                        </div>
                    </div>
                    <div class="signature-line">Principal Signature</div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php endforeach; ?>
</body>
</html>
<?php
else:
    // Display Selection UI
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card Selection | DLHS</title>
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
        
        .student-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
        .student-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between; transition: all 0.2s; }
        .student-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); border-color: #cbdcf7; }
        
        .student-card-title { font-size: 14px; font-weight: 700; color: #003366; margin-bottom: 4px; }
        .student-card-sub { font-size: 11px; color: #718096; margin-bottom: 12px; }
        
        .action-row { display: flex; gap: 8px; margin-top: 14px; border-top: 1px solid #f1f5f9; padding-top: 12px; }
        .btn-action { flex: 1; padding: 7px 12px; font-size: 11px; font-weight: 700; border-radius: 6px; text-align: center; text-transform: uppercase; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-block; }
        .btn-view-card { background: #e8f4fd; color: #003366; border: 1px solid #cbdcf7; }
        .btn-view-card:hover { background: #003366; color: #ffd700; }
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
                    <h3 class="page-header"><i class="fa fa-print"></i> Report Card Generator</h3>
                    <ol class="breadcrumb">
                        <li><i class="fa fa-home"></i><a href="index.php">Home</a></li>
                        <li>Result Processing</li>
                        <li>Report Cards</li>
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
                        <label>Result Type</label>
                        <select id="filterResultType" class="form-control">
                            <option value="end_of_term">End of Term</option>
                            <option value="mid_term">Mid Term</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Session</label>
                        <input type="text" id="filterSession" class="form-control" value="2024/2025" style="width:100px;" placeholder="e.g. 2024/2025">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn-dlhs btn btn-default" onclick="loadClassStudents()"><i class="fa fa-search"></i> Load Class</button>
                    </div>
                </div>
            </div>

            <!-- Student Grid -->
            <div class="page-card" id="studentsSection" style="display:none;">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid #f1f5f9; padding-bottom:12px; margin-bottom:18px;">
                    <h4 style="font-weight:800; color:#003366; margin:0;">Printable Student Lists</h4>
                    <button class="btn-dlhs btn btn-default" onclick="printAllReportCards()"><i class="fa fa-print"></i> Print Entire Class</button>
                </div>
                <div class="student-grid" id="studentsGridContainer"></div>
            </div>
        </section>
    </section>
    <div class="text-right"><div class="credits"><?php include "footer.php"; ?></div></div>
</section>

<script src="js/bootstrap.min.js"></script>
<script src="js/scripts.js"></script>
<script>
var currentSession = '';
var currentTerm    = 1;
var currentClass   = null;
var currentResultType = 'end_of_term';

function loadClassStudents() {
    var classId = $('#filterClass').val();
    var term    = $('#filterTerm').val();
    var session = $('#filterSession').val().trim();
    var resultType = $('#filterResultType').val();
    if (!classId || !session) {
        alert('Please select a class and enter an academic session.');
        return;
    }
    currentSession = session;
    currentTerm    = parseInt(term);
    currentClass   = parseInt(classId);
    currentResultType = resultType;
    
    $('#studentsSection').show();
    $('#studentsGridContainer').html('<div style="text-align:center; padding:30px; width:100%;"><i class="fa fa-spinner fa-spin" style="font-size:24px;"></i> Loading students...</div>');
    
    $.ajax({
        url: 'get_class_students.php',
        type: 'POST',
        data: { classId: classId },
        dataType: 'json',
        success: function(students) {
            if (!students || students.length === 0) {
                $('#studentsGridContainer').html('<div style="text-align:center; padding:20px; color:#999; width:100%;">No active students found in this class.</div>');
                return;
            }
            
            var html = '';
            students.forEach(function(s) {
                html += '<div class="student-card">';
                html += '  <div>';
                html += '    <div class="student-card-title">' + s.fullName + '</div>';
                html += '    <div class="student-card-sub">Adm No: ' + s.admissionNumber + ' | Gender: ' + s.gender + '</div>';
                html += '  </div>';
                html += '  <div class="action-row">';
                html += '    <a class="btn-action btn-view-card" href="print_term_report.php?studentId=' + s.studentId + '&academicTerm=' + currentTerm + '&academicSession=' + encodeURIComponent(currentSession) + '&resultType=' + encodeURIComponent(currentResultType) + '" target="_blank"><i class="fa fa-print"></i> Report Card</a>';
                html += '  </div>';
                html += '</div>';
            });
            $('#studentsGridContainer').html(html);
        },
        error: function() {
            $('#studentsGridContainer').html('<div style="text-align:center; padding:20px; color:red; width:100%;">Failed to load students.</div>');
        }
    });
}

function printAllReportCards() {
    if (!currentClass) return;
    var printUrl = 'print_term_report.php?classId=' + currentClass + '&academicTerm=' + currentTerm + '&academicSession=' + encodeURIComponent(currentSession) + '&resultType=' + encodeURIComponent(currentResultType);
    window.open(printUrl, '_blank');
}

// Load classes when year group changes
$('#filterYearGroup').change(function(){
    var yg = $(this).val();
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
        },
        error: function(){
            $.post('get_my_classes.php', {yearGroupId: yg}, function(d){
                try { var data = JSON.parse(d); var opts='<option value="">-- Select Class --</option>';
                data.forEach(function(c){ opts+='<option value="'+c.classId+'">'+c.className+'</option>'; });
                $('#filterClass').html(opts); } catch(e){}
            });
        }
    });
});
</script>

<?php if (isset($_GET['autoPrint']) && $_GET['autoPrint'] == '1'): ?>
<script>
    // Auto-trigger print dialog after fonts and images have loaded
    window.addEventListener('load', function() {
        setTimeout(function() { window.print(); }, 800);
    });
</script>
<?php endif; ?>

</body>
</html>
<?php
endif;
?>
