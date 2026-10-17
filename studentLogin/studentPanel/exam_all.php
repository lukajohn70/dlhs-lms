<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
if (!isset($_SESSION['studentLast_login'])) {
    header("location:logout.php");
    exit();
}

// Check for required session variables
if (!isset($_SESSION['idOfTest']) || 
    !isset($_SESSION['studentId']) || 
    !isset($_SESSION['questionsIdsArray']) || 
    !isset($_SESSION['totalQuestions']) || 
    !isset($_SESSION['questTableName']) || 
    !isset($_SESSION['answersTableName']) || 
    !isset($_SESSION['testedTableName'])) {
    header("location:exam.php");
    exit();
}


$studentName = $_SESSION['studentName'];
$testId = $_SESSION['idOfTest'];
$studentId = $_SESSION['studentId'];
$testedTableName = $_SESSION['testedTableName'];

// Get test time and name from database
require_once('../../db_connection/dlhs_db_connection.php');
require_once('../../track_test_activity.php');

$testName = '';
$testTime = 0;
$query = "SELECT testName, duration FROM tests WHERE testId = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $testId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $testName = $row['testName'];
    $testTime = $row['duration'] * 60; // duration in seconds
    $_SESSION['testName'] = $testName;
}

// Track test activity
$totalQuestions = $_SESSION['totalQuestions'];
$questionsTableName = $_SESSION['questTableName'];
$answersTableName = $_SESSION['answersTableName'];

// Count answered questions
$answeredCount = 0;
$countQuery = "SELECT COUNT(*) as count FROM `$answersTableName` WHERE userLoginId = ? AND testId = ? AND selectedOption != 0";
$countStmt = $connection->prepare($countQuery);
if ($countStmt) {
    $countStmt->bind_param("ii", $studentId, $testId);
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    if ($countRow = $countResult->fetch_assoc()) {
        $answeredCount = $countRow['count'];
    }
    $countStmt->close();
}

// Track this test activity
trackTestActivity($testId, $testName, $testedTableName, $testTime, $answeredCount, $totalQuestions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>DLHS | Exam (All Questions)</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css1/util.css">
    <link rel="stylesheet" type="text/css" href="css1/main.css">
    <link rel="stylesheet" type="text/css" href="../fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <script src="jQuery3.3.1.js"></script>
    <script src="exam_security.js"></script>
    <style>
        body {
            background: #f5f8fa;
            font-family: 'Poppins-Regular', sans-serif;
            padding-top: 70px; /* Slightly reduced padding */
            padding-bottom: 128px;
        }
        .exam-header {
            background: #003366;
            padding: 15px 40px;
            color: #FFD700;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
        }
        .exam-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }
        .student-info {
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .test-name {
            font-size: 22px;
            font-weight: 600;
            color: #FFD700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .student-name {
            font-size: 15px;
            color: rgba(255, 215, 0, 0.9);
            font-weight: 500;
        }
        .student-name strong {
            color: #FFD700;
            font-weight: 600;
        }
        .timer {
            background: #FFD700;
            color: #003366;
            padding: 12px 25px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            min-width: 140px;
            text-align: center;
        }
        .timer:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .status-pill {
            padding: 10px 18px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-width: 180px;
            justify-content: center;
        }
        .status-pill.online {
            background: rgba(40, 167, 69, 0.15);
            color: #0f7b2e;
        }
        .status-pill.offline {
            background: rgba(220, 53, 69, 0.15);
            color: #b42318;
        }
        .status-pill.pending {
            background: rgba(255, 193, 7, 0.2);
            color: #8a6100;
        }
        .status-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .resync-btn {
            border: none;
            border-radius: 999px;
            padding: 10px 16px;
            background: #ffffff;
            color: #003366;
            font-weight: 700;
            font-size: 13px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .resync-btn:hover:not(:disabled) {
            transform: translateY(-1px);
        }
        .resync-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            padding-bottom: 80px;
        }
        .exam-status-board {
            max-width: 1200px;
            margin: 1rem auto 0;
            padding: 0 1rem;
        }
        .status-summary {
            background: #fff;
            border-radius: 18px;
            padding: 20px 24px;
            box-shadow: 0 4px 15px rgba(0, 51, 102, 0.08);
            margin-bottom: 20px;
        }
        .status-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-top: 16px;
        }
        .status-card {
            border-radius: 14px;
            padding: 16px;
            border: 1px solid rgba(0, 51, 102, 0.08);
            background: #f8fbff;
        }
        .status-card strong {
            display: block;
            font-size: 24px;
            color: #003366;
            margin-top: 6px;
        }
        .question-navigator {
            background: #fff;
            border-radius: 18px;
            padding: 20px 24px;
            box-shadow: 0 4px 15px rgba(0, 51, 102, 0.08);
            margin-bottom: 20px;
        }
        .navigator-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 16px;
        }
        .legend-pill {
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
        }
        .legend-not-answered { background: #eef2f6; color: #44556b; }
        .legend-answered { background: #e8f1ff; color: #004085; }
        .legend-pending { background: #fff4cc; color: #8a6100; }
        .legend-saved { background: #e7f8ec; color: #166534; }
        .legend-error { background: #fdecec; color: #b42318; }
        .question-chip-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(72px, 1fr));
            gap: 10px;
        }
        .question-chip {
            border: none;
            border-radius: 12px;
            padding: 12px 10px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 51, 102, 0.08);
        }
        .question-chip:hover {
            transform: translateY(-1px);
        }
        .question-chip.not_answered { background: #eef2f6; color: #44556b; }
        .question-chip.answered { background: #e8f1ff; color: #004085; }
        .question-chip.pending { background: #fff4cc; color: #8a6100; }
        .question-chip.saved { background: #e7f8ec; color: #166534; }
        .question-chip.error { background: #fdecec; color: #b42318; }
        .question-chip.active {
            outline: 3px solid rgba(0, 51, 102, 0.2);
        }
        .card {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            box-shadow: 0 4px 15px rgba(0, 51, 102, 0.1);
            border-radius: 15px;
            border: none;
        }
        .question-block {
            margin-bottom: 30px;
            padding: 25px;
            border: 2px solid #003366;
            border-radius: 15px;
            background: #fff;
            box-shadow: 0 4px 15px rgba(0, 51, 102, 0.1);
            transition: all 0.3s ease;
        }
        .question-block:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 51, 102, 0.15);
        }
        .question-block.state-not_answered {
            border-left: 6px solid #96a4b5;
        }
        .question-block.state-answered {
            border-left: 6px solid #4c8bf5;
        }
        .question-block.state-pending {
            border-left: 6px solid #f4b400;
        }
        .question-block.state-saved {
            border-left: 6px solid #2ea44f;
        }
        .question-block.state-error {
            border-left: 6px solid #dc3545;
        }
        .question-title {
            font-family: 'Poppins-Medium', sans-serif;
            font-size: 1.1rem;
            color: #003366;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px dashed rgba(0, 51, 102, 0.2);
        }
        .math-equation {
            display: inline-block;
            padding: 2px 6px;
            margin: 2px 0;
            border-radius: 6px;
            background: rgba(0, 51, 102, 0.06);
            font-family: Cambria, "Times New Roman", serif;
            letter-spacing: 0.02em;
        }
        .question-stimulus-card {
            margin-bottom: 18px;
            padding: 18px 20px;
            border-radius: 14px;
            background: linear-gradient(180deg, #f7fbff 0%, #eef6ff 100%);
            border: 1px solid rgba(0, 51, 102, 0.12);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.85);
        }
        .question-stimulus-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .question-stimulus-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(0, 51, 102, 0.1);
            color: #003366;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        .question-stimulus-title {
            font-size: 15px;
            font-weight: 700;
            color: #003366;
        }
        .question-stimulus-content {
            color: #31465f;
            line-height: 1.65;
        }
        .question-stimulus-content img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin: 10px 0;
        }
        .question-stimulus-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }
        .question-stimulus-content table td,
        .question-stimulus-content table th {
            border: 1px solid rgba(0, 51, 102, 0.18);
            padding: 8px 10px;
        }
        .option-container {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 8px 15px;
            border-radius: 10px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .option-container:hover {
            background: rgba(0, 51, 102, 0.05);
        }
        .option-container input[type="radio"] {
            margin: 0;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .option-container input[type="radio"]:checked + .option-label {
            color: #003366;
            font-weight: 500;
        }
        .option-label {
            margin-left: 12px;
            font-size: 1rem;
            color: #666;
            cursor: pointer;
            flex: 1;
        }
        .btn-pill {
            border-radius: 50px !important;
            padding: 12px 35px !important;
            font-family: 'Poppins-Medium', sans-serif;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease !important;
            background: #003366 !important;
            border: none !important;
            box-shadow: 0 4px 15px rgba(0, 51, 102, 0.3);
        }
        .btn-pill:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 51, 102, 0.4);
            background: #004080 !important;
        }
        .btn-pill i {
            margin-right: 8px;
        }
        /* Toast Styles */
        .toast {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 51, 102, 0.9);
            color: white;
            padding: 15px 30px;
            border-radius: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            animation: fadeInOut 1.5s ease-in-out;
            z-index: 1001;
        }
        
        @keyframes fadeInOut {
            0% { opacity: 0; }
            15% { opacity: 1; }
            85% { opacity: 1; }
            100% { opacity: 0; }
        }
        
        .toast i {
            margin-right: 10px;
            font-size: 1.2rem;
            color: #FFD700;
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease-out;
        }
        .modal-dialog {
            position: relative;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            animation: slideDown 0.3s ease-out;
        }
        .modal-content {
            background-color: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .modal-header {
            padding: 20px;
            margin: 15px 15px 0;
            background-color: #003366;
            color: #FFD700;
            border-radius: 15px;
            text-align: center;
        }
        .modal-header i {
            font-size: 1.5rem;
            margin-right: 10px;
        }
        .modal-body {
            background-color: #f8f9fa;
            margin: 15px;
            padding: 25px;
            border-radius: 15px;
        }
        .modal-footer {
            padding: 20px;
            text-align: center;
        }
        .modal-btn {
            padding: 12px 30px;
            width: 180px;
            border-radius: 50px;
            margin: 10px;
            font-family: 'Poppins-Medium', sans-serif;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        .modal-btn-yes {
            background-color: #003366;
            border-color: #003366;
            color: #FFD700;
        }
        .modal-btn-no {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        .modal-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideDown {
            from {
                transform: translateY(-100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        /* Progress indicator */
        .progress-container {
            margin-bottom: 2rem;
            text-align: center;
        }
        .progress-text {
            font-family: 'Poppins-Medium', sans-serif;
            color: #003366;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        .progress-bar {
            height: 10px;
            background: rgba(0, 51, 102, 0.1);
            border-radius: 50px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: #003366;
            border-radius: 50px;
            transition: width 0.3s ease-out;
            position: relative;
            overflow: hidden;
        }
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                90deg,
                rgba(255, 215, 0, 0) 0%,
                rgba(255, 215, 0, 0.1) 50%,
                rgba(255, 215, 0, 0) 100%
            );
            transform: translateX(-100%);
            animation: shimmer 2s infinite;
        }
        @keyframes shimmer {
            100% {
                transform: translateX(100%);
            }
        }
        .progress-counter-wrapper {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            z-index: 1000;
        }
        
        .progress-counter {
            background: #003366;
            color: #FFD700;
            padding: 12px 30px;
            text-align: center;
            font-size: 16px;
            font-weight: 500;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
            border-radius: 30px;
            min-width: 200px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .progress-counter.updating {
            transform: scale(1.05);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        
        .progress-counter span {
            font-weight: 600;
            font-size: 18px;
            color: #FFD700;
            transition: color 0.3s ease;
        }
        
        .progress-counter span.updating {
            color: #ffffff;
        }
        .exam-page {
            max-width: 1440px;
            margin: 0 auto;
            padding: 24px 18px 140px;
            display: flex;
            gap: 24px;
            align-items: flex-start;
        }
        .exam-main {
            flex: 1 1 auto;
            min-width: 0;
        }
        .exam-sidebar {
            width: 340px;
            flex: 0 0 340px;
            position: sticky;
            top: 94px;
            max-height: calc(100vh - 110px);
            display: flex;
            flex-direction: column;
        }
        .sidebar-panel {
            background: #fff;
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 10px 28px rgba(0, 51, 102, 0.08);
            border: 1px solid rgba(0, 51, 102, 0.08);
            margin-bottom: 18px;
        }
        .exam-sidebar .sidebar-panel:last-child {
            margin-bottom: 0;
        }
        .sidebar-heading {
            margin: 0 0 16px;
            color: #003366;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .status-summary,
        .question-navigator {
            margin-bottom: 0;
            box-shadow: none;
            border-radius: 0;
            padding: 0;
            background: transparent;
        }
        .question-navigator {
            display: flex;
            flex-direction: column;
            min-height: 0;
            flex: 1 1 auto;
        }
        .status-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .status-card {
            background: #f7fbff;
        }
        .question-chip-grid {
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 8px;
            overflow-y: auto;
            padding-right: 4px;
            min-height: 0;
        }
        .question-chip {
            padding: 10px 0;
            width: 100%;
            font-size: 13px;
        }
        .exam-question-card {
            max-width: none;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        .exam-question-card .card-body {
            padding: 0;
        }
        .question-block {
            margin-bottom: 20px;
        }
        .question-block:last-child {
            margin-bottom: 0;
        }
        .question-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .question-number-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(0, 51, 102, 0.08);
            color: #003366;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .question-state-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }
        .question-state-badge.not_answered { background: #eef2f6; color: #44556b; }
        .question-state-badge.answered { background: #e8f1ff; color: #004085; }
        .question-state-badge.pending { background: #fff4cc; color: #8a6100; }
        .question-state-badge.saved { background: #e7f8ec; color: #166534; }
        .question-state-badge.error { background: #fdecec; color: #b42318; }
        .progress-panel {
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .progress-panel.updating {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(0, 51, 102, 0.12);
        }
        .progress-panel .progress-text {
            margin-bottom: 10px;
            text-align: left;
        }
        .progress-panel .progress-bar {
            margin-bottom: 18px;
        }
        .exam-submit-bar {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1200;
            display: none;
            justify-content: center;
            padding: 14px 18px;
            background: rgba(245, 248, 250, 0.96);
            border-top: 1px solid rgba(0, 51, 102, 0.12);
            box-shadow: 0 -8px 24px rgba(0, 51, 102, 0.12);
            backdrop-filter: blur(8px);
        }
        .exam-submit-bar.is-visible {
            display: flex;
        }
        .exam-submit-inner {
            width: min(900px, 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            color: #003366;
            font-weight: 700;
        }
        .exam-submit-note {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        .exam-submit-note i {
            color: #198754;
            font-size: 22px;
        }
        .exam-submit-trigger {
            white-space: nowrap;
        }
        .calculator-toggle {
            position: fixed;
            left: 18px;
            bottom: 22px;
            z-index: 1300;
            width: 54px;
            height: 54px;
            border: none;
            border-radius: 50%;
            background: #003366;
            color: #FFD700;
            box-shadow: 0 8px 24px rgba(0, 51, 102, 0.28);
            font-size: 22px;
            cursor: pointer;
        }
        .calculator-panel {
            position: fixed;
            left: 18px;
            bottom: 88px;
            z-index: 1290;
            width: 260px;
            display: none;
            background: #ffffff;
            border: 1px solid rgba(0, 51, 102, 0.12);
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(0, 51, 102, 0.18);
            overflow: hidden;
        }
        .calculator-panel.is-open {
            display: block;
        }
        .exam-submit-bar.is-visible ~ .calculator-toggle {
            bottom: 96px;
        }
        .exam-submit-bar.is-visible ~ .calculator-panel {
            bottom: 162px;
        }
        .calculator-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            background: #003366;
            color: #FFD700;
            font-weight: 700;
        }
        .calculator-close {
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            cursor: pointer;
        }
        .calculator-display {
            width: calc(100% - 24px);
            margin: 12px;
            min-height: 44px;
            border: 1px solid rgba(0, 51, 102, 0.16);
            border-radius: 8px;
            padding: 10px 12px;
            background: #f8fbff;
            color: #003366;
            font-size: 21px;
            font-weight: 700;
            text-align: right;
            overflow-x: auto;
            white-space: nowrap;
        }
        .calculator-mode-switch {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
            padding: 0 12px 10px;
        }
        .calculator-mode-switch button {
            min-height: 34px;
            border: 1px solid rgba(0, 51, 102, 0.14);
            border-radius: 999px;
            background: #ffffff;
            color: #003366;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }
        .calculator-mode-switch button.active {
            background: #003366;
            color: #FFD700;
        }
        .calculator-keys {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 7px;
            padding: 0 12px 12px;
        }
        .calculator-keys button {
            min-height: 40px;
            border: 1px solid rgba(0, 51, 102, 0.12);
            border-radius: 8px;
            background: #ffffff;
            color: #003366;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }
        .calculator-keys button:hover {
            background: #eef6ff;
        }
        .calculator-keys .calc-op {
            background: #eef6ff;
        }
        .calculator-keys .calc-clear {
            background: #fdecec;
            color: #b42318;
        }
        .calculator-keys .calc-zero {
            grid-column: span 2;
        }
        .calculator-keys .calc-equals {
            background: #003366;
            color: #FFD700;
        }
        .scientific-keys {
            display: none;
            padding-bottom: 8px;
        }
        .calculator-panel.mode-scientific .scientific-keys {
            display: grid;
        }
        .scientific-keys button {
            min-height: 36px;
            font-size: 13px;
        }
        @media (max-width: 1199px) {
            .exam-page {
                flex-direction: column;
            }
            .exam-sidebar {
                width: 100%;
                flex: 1 1 auto;
                position: static;
                max-height: none;
            }
            .question-chip-grid {
                grid-template-columns: repeat(auto-fit, minmax(64px, 1fr));
                overflow-y: visible;
            }
        }
        @media (max-width: 767px) {
            body {
                padding-top: 88px;
                padding-bottom: 158px;
            }
            .exam-header {
                padding: 14px 16px;
            }
            .exam-info {
                flex-direction: column;
                gap: 12px;
                align-items: stretch;
            }
            .student-info {
                text-align: center;
            }
            .status-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .question-header-row {
                align-items: flex-start;
            }
            .exam-page {
                padding: 18px 12px 170px;
            }
            .exam-submit-inner {
                align-items: stretch;
                flex-direction: column;
                gap: 10px;
            }
            .exam-submit-trigger {
                width: 100%;
            }
            .calculator-panel {
                left: 12px;
                right: 12px;
                bottom: 96px;
                width: auto;
            }
            .calculator-toggle {
                left: 12px;
                bottom: 18px;
            }
            .exam-submit-bar.is-visible ~ .calculator-toggle {
                bottom: 158px;
            }
            .exam-submit-bar.is-visible ~ .calculator-panel {
                bottom: 222px;
            }
        }

        /* Timer Urgency States */
        .timer.timer-warning {
            background: #f59e0b !important;
            color: #ffffff !important;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
        }
        .timer.timer-critical {
            background: #dc2626 !important;
            color: #ffffff !important;
            animation: timerPulse 1s infinite alternate;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.5);
        }
        @keyframes timerPulse {
            0% { transform: scale(1); box-shadow: 0 4px 15px rgba(220, 38, 38, 0.4); }
            100% { transform: scale(1.08); box-shadow: 0 6px 22px rgba(220, 38, 38, 0.8); }
        }

        /* Accessibility Font Zoom Controls */
        body.fs-sm .question-title { font-size: 0.95rem; }
        body.fs-sm .option-label { font-size: 0.88rem; }
        body.fs-sm .question-stimulus-content { font-size: 0.88rem; }

        body.fs-md .question-title { font-size: 1.1rem; }
        body.fs-md .option-label { font-size: 1rem; }
        body.fs-md .question-stimulus-content { font-size: 1rem; }

        body.fs-lg .question-title { font-size: 1.35rem; }
        body.fs-lg .option-label { font-size: 1.22rem; }
        body.fs-lg .question-stimulus-content { font-size: 1.22rem; }

        .font-zoom-group {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 999px;
            padding: 3px;
            gap: 2px;
        }
        .font-zoom-btn {
            border: none;
            background: transparent;
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            padding: 5px 10px;
            border-radius: 999px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .font-zoom-btn:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        .font-zoom-btn.active {
            background: #FFD700;
            color: #003366;
        }
        .shortcuts-toggle-btn {
            border: none;
            border-radius: 999px;
            padding: 9px 14px;
            background: rgba(255, 255, 255, 0.15);
            color: #FFD700;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .shortcuts-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* Flag and Clear Answer Controls */
        .btn-flag-question {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid rgba(245, 158, 11, 0.4);
            background: #fffdf5;
            color: #b45309;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-flag-question:hover {
            background: #fef3c7;
            transform: translateY(-1px);
        }
        .btn-flag-question.is-flagged {
            background: #f59e0b;
            color: #ffffff;
            border-color: #d97706;
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.35);
        }
        .btn-clear-answer {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            border: 1px solid rgba(220, 53, 69, 0.3);
            background: #fff8f8;
            color: #dc3545;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-clear-answer:hover {
            background: #dc3545;
            color: #ffffff;
            transform: translateY(-1px);
        }
        .question-chip {
            position: relative;
        }
        .question-chip.flagged {
            box-shadow: 0 0 0 2px #f59e0b, 0 2px 8px rgba(245, 158, 11, 0.25) !important;
        }
        .question-chip.flagged::after {
            content: '\f02e';
            font-family: FontAwesome;
            position: absolute;
            top: -5px;
            right: -4px;
            font-size: 11px;
            color: #d97706;
            background: #fff;
            border-radius: 50%;
            width: 15px;
            height: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.25);
            line-height: 1;
        }
        .chip-filter-tabs {
            display: flex;
            gap: 6px;
            margin-bottom: 12px;
        }
        .chip-filter-btn {
            flex: 1;
            padding: 6px 4px;
            border: 1px solid rgba(0, 51, 102, 0.15);
            background: #f8fbff;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            color: #003366;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .chip-filter-btn:hover {
            background: #e8f1ff;
        }
        .chip-filter-btn.active {
            background: #003366;
            color: #FFD700;
            border-color: #003366;
        }

        /* Diagram / Lightbox Modal Styles */
        .question-title img,
        .option-label img,
        .question-stimulus-content img {
            cursor: zoom-in;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid rgba(0, 51, 102, 0.1);
            border-radius: 8px;
        }
        .question-title img:hover,
        .option-label img:hover,
        .question-stimulus-content img:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(0, 51, 102, 0.2);
        }
        .img-lightbox-modal {
            display: none;
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.88);
            backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .img-lightbox-modal.is-open {
            display: flex;
        }
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: #fff;
            font-size: 40px;
            font-weight: 700;
            cursor: pointer;
            transition: color 0.2s;
            line-height: 1;
            z-index: 100001;
        }
        .lightbox-close:hover {
            color: #FFD700;
        }
        .lightbox-content-wrapper {
            max-width: 90%;
            max-height: 85%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .lightbox-image {
            max-width: 100%;
            max-height: 75vh;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            background: #fff;
            padding: 8px;
        }
        .lightbox-caption {
            color: #e2e8f0;
            margin-top: 12px;
            font-size: 15px;
            text-align: center;
        }

        /* Scorecard Dialog Styles */
        .modal-dialog-scorecard {
            max-width: 540px;
        }
        .modal-scorecard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin: 16px 0;
        }
        .modal-scorecard-card {
            border-radius: 12px;
            padding: 14px 16px;
            text-align: center;
            border: 1px solid rgba(0, 51, 102, 0.1);
        }
        .modal-scorecard-card .card-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }
        .modal-scorecard-card .card-val {
            font-size: 26px;
            font-weight: 800;
        }
        .modal-scorecard-card.card-total {
            background: #f8fbff;
            color: #003366;
        }
        .modal-scorecard-card.card-answered {
            background: #e7f8ec;
            color: #166534;
        }
        .modal-scorecard-card.card-unanswered {
            background: #fff4cc;
            color: #8a6100;
        }
        .modal-scorecard-card.card-flagged {
            background: #fef3c7;
            color: #b45309;
        }
        kbd {
            background-color: #003366;
            color: #FFD700;
            padding: 3px 7px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="exam-header">
        <div class="exam-info">
            <div class="student-info">
                <div class="test-name" id="testName"><?php echo htmlspecialchars($testName); ?></div>
                <div class="student-name">Welcome, <strong><?php echo htmlspecialchars($studentName); ?></strong></div>
            </div>
            <div class="status-actions">
                <div class="font-zoom-group" title="Adjust question text size">
                    <button type="button" class="font-zoom-btn" data-size="sm" title="Small text">A-</button>
                    <button type="button" class="font-zoom-btn active" data-size="md" title="Default text">A</button>
                    <button type="button" class="font-zoom-btn" data-size="lg" title="Large text">A+</button>
                </div>
                <button type="button" class="shortcuts-toggle-btn" id="shortcutsToggleBtn" title="Keyboard shortcuts guide">
                    <i class="fa fa-keyboard-o"></i> <span>Keys</span>
                </button>
                <div class="status-pill online" id="connectionStatus">
                    <i class="fa fa-wifi"></i><span>Connected to server</span>
                </div>
                <button type="button" class="resync-btn" id="resyncAnswersBtn" disabled>
                    <i class="fa fa-refresh"></i> Resync pending saves
                </button>
                <div class="timer" id="hms"></div>
            </div>
        </div>
    </div>

    <div class="exam-page">
        <div class="exam-main">
            <div class="card exam-question-card">
                <div class="card-body">
                    <form id="allQuestionsForm">
                        <input type="hidden" name="testId" value="<?php echo htmlspecialchars($testId); ?>">
                        <input type="hidden" name="studentId" value="<?php echo htmlspecialchars($studentId); ?>">
                        <div id="questionsContainer">
                            <div class="text-center" style="padding:30px;">Loading questions...</div>
                        </div>
                    </form>
                    <div id="submitMessage" class="mt-3" style="padding:0 25px 25px;"></div>
                </div>
            </div>
        </div>
        <aside class="exam-sidebar">
            <div class="sidebar-panel">
                <div class="status-summary">
                    <h4 class="sidebar-heading">Exam Status</h4>
                    <div class="status-summary-grid">
                        <div class="status-card">
                            Not answered
                            <strong id="countNotAnswered">0</strong>
                        </div>
                        <div class="status-card">
                            Answered
                            <strong id="countAnswered">0</strong>
                        </div>
                        <div class="status-card">
                            Saved
                            <strong id="countSaved">0</strong>
                        </div>
                        <div class="status-card">
                            Pending save
                            <strong id="countPending">0</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="sidebar-panel">
                <div class="question-navigator">
                    <h4 class="sidebar-heading">Question Map</h4>
                    <div class="navigator-legend">
                        <span class="legend-pill legend-not-answered">Not answered</span>
                        <span class="legend-pill legend-answered">Answered locally</span>
                        <span class="legend-pill legend-pending">Saving</span>
                        <span class="legend-pill legend-saved">Saved successfully</span>
                        <span class="legend-pill legend-error">Save failed</span>
                    </div>
                    <div class="chip-filter-tabs">
                        <button type="button" class="chip-filter-btn active" data-filter="all">All (<span id="filterCountAll">0</span>)</button>
                        <button type="button" class="chip-filter-btn" data-filter="unanswered">Unanswered (<span id="filterCountUnanswered">0</span>)</button>
                        <button type="button" class="chip-filter-btn" data-filter="flagged">Flagged (<span id="filterCountFlagged">0</span>)</button>
                    </div>
                    <div class="question-chip-grid" id="questionNavigator"></div>
                </div>
            </div>
            <div class="sidebar-panel progress-panel">
                <div class="progress-container" style="margin-bottom:0;">
                    <div class="progress-text">
                        Questions Answered: <span id="progressAnsweredCount">0</span>/<span id="progressTotalCount">0</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div class="exam-submit-bar" id="examSubmitBar">
        <div class="exam-submit-inner">
            <div class="exam-submit-note">
                <i class="fa fa-check-circle"></i>
                <span>All questions are answered. You can submit now.</span>
            </div>
            <button type="button" class="btn btn-success btn-lg btn-pill exam-submit-trigger" id="fixedSubmitButton">
                <i class="fa fa-check" style="margin-right: 8px;"></i>Submit
            </button>
        </div>
    </div>

    <button type="button" class="calculator-toggle" id="calculatorToggle" aria-label="Open calculator">
        <i class="fa fa-calculator"></i>
    </button>
    <div class="calculator-panel" id="calculatorPanel" aria-label="Calculator">
        <div class="calculator-header">
            <span><i class="fa fa-calculator"></i> Calculator</span>
            <button type="button" class="calculator-close" id="calculatorClose" aria-label="Close calculator">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="calculator-display" id="calculatorDisplay">0</div>
        <div class="calculator-mode-switch" aria-label="Calculator mode">
            <button type="button" class="active" data-calculator-mode="basic">Basic</button>
            <button type="button" data-calculator-mode="scientific">Scientific</button>
        </div>
        <div class="calculator-keys scientific-keys">
            <button type="button" class="calc-op" data-calc-value="Math.sin(">sin</button>
            <button type="button" class="calc-op" data-calc-value="Math.cos(">cos</button>
            <button type="button" class="calc-op" data-calc-value="Math.tan(">tan</button>
            <button type="button" class="calc-op" data-calc-value="Math.sqrt(">sqrt</button>
            <button type="button" class="calc-op" data-calc-value="Math.log10(">log</button>
            <button type="button" class="calc-op" data-calc-value="Math.log(">ln</button>
            <button type="button" class="calc-op" data-calc-value="**2">x^2</button>
            <button type="button" class="calc-op" data-calc-value="**">x^y</button>
            <button type="button" class="calc-op" data-calc-value="Math.PI">pi</button>
            <button type="button" class="calc-op" data-calc-value="Math.E">e</button>
            <button type="button" class="calc-op" data-calc-value="(">(</button>
            <button type="button" class="calc-op" data-calc-value=")">)</button>
        </div>
        <div class="calculator-keys">
            <button type="button" class="calc-clear" data-calc-action="clear">C</button>
            <button type="button" data-calc-action="backspace"><i class="fa fa-long-arrow-left"></i></button>
            <button type="button" class="calc-op" data-calc-value="%">%</button>
            <button type="button" class="calc-op" data-calc-value="/">/</button>
            <button type="button" data-calc-value="7">7</button>
            <button type="button" data-calc-value="8">8</button>
            <button type="button" data-calc-value="9">9</button>
            <button type="button" class="calc-op" data-calc-value="*">x</button>
            <button type="button" data-calc-value="4">4</button>
            <button type="button" data-calc-value="5">5</button>
            <button type="button" data-calc-value="6">6</button>
            <button type="button" class="calc-op" data-calc-value="-">-</button>
            <button type="button" data-calc-value="1">1</button>
            <button type="button" data-calc-value="2">2</button>
            <button type="button" data-calc-value="3">3</button>
            <button type="button" class="calc-op" data-calc-value="+">+</button>
            <button type="button" class="calc-zero" data-calc-value="0">0</button>
            <button type="button" data-calc-value=".">.</button>
            <button type="button" class="calc-equals" data-calc-action="equals">=</button>
        </div>
    </div>

    <!-- Confirmation Modal with Live Scorecard -->
    <div id="confirmationModal" class="modal">
        <div class="modal-dialog modal-dialog-scorecard">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-center w-100"><i class="fa fa-clipboard"></i> Exam Submission Summary</h5>
                </div>
                <div class="modal-body">
                    <p class="text-muted text-center" style="font-size: 14px; margin-bottom: 12px;">
                        Review your exam progress before final submission:
                    </p>
                    <div class="modal-scorecard-grid">
                        <div class="modal-scorecard-card card-total">
                            <span class="card-label">Total Questions</span>
                            <strong class="card-val" id="modalTotalQuestions">0</strong>
                        </div>
                        <div class="modal-scorecard-card card-answered">
                            <span class="card-label">Answered</span>
                            <strong class="card-val" id="modalAnsweredQuestions">0</strong>
                        </div>
                        <div class="modal-scorecard-card card-unanswered">
                            <span class="card-label">Unanswered</span>
                            <strong class="card-val" id="modalUnansweredQuestions">0</strong>
                        </div>
                        <div class="modal-scorecard-card card-flagged">
                            <span class="card-label">Flagged for Review</span>
                            <strong class="card-val" id="modalFlaggedQuestions">0</strong>
                        </div>
                    </div>

                    <div id="modalUnansweredWarning" class="alert alert-warning mt-3 text-center" style="display:none; border-radius:10px; font-size:14px;">
                        <i class="fa fa-exclamation-triangle"></i> <strong>Attention:</strong> You still have <span id="modalWarningCount">0</span> unanswered questions!
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-warning" id="btnReviewUnanswered" style="border-radius:20px; font-weight:700;">
                                <i class="fa fa-arrow-circle-right"></i> Review First Unanswered Question
                            </button>
                        </div>
                    </div>

                    <div id="modalUnsavedWarning" class="alert alert-warning mt-2 text-center" style="display:none; border-radius:10px; font-size:13px;"></div>
                    <div id="modalSubmitError" class="alert alert-danger mt-2 text-center" style="display:none; border-radius:10px; font-size:13px;"></div>

                    <p class="text-danger text-center mt-3" style="font-size: 13px; font-weight: 500;">
                        <i class="fa fa-info-circle"></i> Once submitted, your exam will end immediately and cannot be continued.
                    </p>

                    <div class="d-flex justify-content-center mt-3" style="gap: 12px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-primary modal-btn modal-btn-yes" id="confirmSubmit" onclick="confirmSubmitHandler()">
                            <i class="fa fa-check"></i> Submit Exam
                        </button>
                        <button type="button" class="btn btn-secondary modal-btn modal-btn-no" data-dismiss="modal">
                            <i class="fa fa-arrow-left"></i> Return to Exam
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Keyboard Shortcuts Cheatsheet Modal -->
    <div id="shortcutsModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-center w-100"><i class="fa fa-keyboard-o"></i> CBT Keyboard Navigation</h5>
                </div>
                <div class="modal-body">
                    <p class="text-muted text-center" style="font-size: 13px; margin-bottom: 14px;">
                        Use standard JAMB/WAEC hotkeys to answer and navigate questions quickly:
                    </p>
                    <table class="table table-bordered table-sm text-center" style="font-size: 14px; background:#fff; border-radius:10px; overflow:hidden;">
                        <thead style="background:#003366; color:#FFD700;">
                            <tr>
                                <th style="width: 45%;">Hotkey</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><kbd>A</kbd> <kbd>B</kbd> <kbd>C</kbd> <kbd>D</kbd> <kbd>E</kbd></td>
                                <td>Select corresponding option</td>
                            </tr>
                            <tr>
                                <td><kbd>N</kbd> or <kbd>&darr;</kbd> / <kbd>&rarr;</kbd></td>
                                <td>Next question</td>
                            </tr>
                            <tr>
                                <td><kbd>P</kbd> or <kbd>&uarr;</kbd> / <kbd>&larr;</kbd></td>
                                <td>Previous question</td>
                            </tr>
                            <tr>
                                <td><kbd>F</kbd></td>
                                <td>Toggle Flag / Bookmark</td>
                            </tr>
                            <tr>
                                <td><kbd>C</kbd></td>
                                <td>Toggle On-Screen Calculator</td>
                            </tr>
                            <tr>
                                <td><kbd>S</kbd></td>
                                <td>Open Submit Exam confirmation</td>
                            </tr>
                            <tr>
                                <td><kbd>Esc</kbd></td>
                                <td>Close modal or zoom view</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="text-center mt-3">
                        <button type="button" class="btn btn-primary modal-btn modal-btn-yes" id="closeShortcutsModal">
                            <i class="fa fa-check"></i> Got it
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Diagram Lightbox Modal -->
    <div id="imgLightboxModal" class="img-lightbox-modal" role="dialog" aria-modal="true" aria-label="Enlarged Diagram">
        <span class="lightbox-close" id="lightboxCloseBtn" title="Close zoom">&times;</span>
        <div class="lightbox-content-wrapper">
            <img class="lightbox-image" id="lightboxTargetImg" alt="Enlarged Diagram">
            <div class="lightbox-caption" id="lightboxCaption">Diagram View</div>
        </div>
    </div>

    <script>
    function showToast(message) {
        $('.toast').remove();
        const toast = $('<div class="toast"><i class="fa fa-check-circle"></i>' + message + '</div>');
        $('body').append(toast);
        setTimeout(function() {
            toast.remove();
        }, 1500);
    }
    </script>
    <script>
    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }
    </script>
    <script>
        $(document).ready(function() {
            // Initialize Exam Security System
            const studentName = <?php echo json_encode($_SESSION['studentName'] ?? 'Student'); ?>;
            const testName = <?php echo json_encode($testName); ?>;
            ExamSecurity.init(studentName, testName);
            
            let totalQuestions = 0;
            let answeredQuestions = new Set();
            const questionStates = {};
            let serverConnected = true;
            let isResyncing = false;
            const testId = <?php echo json_encode($testId); ?>;
            const studentId = <?php echo json_encode($_SESSION['studentId']); ?>;
            const connectionStatus = $('#connectionStatus');
            const resyncButton = $('#resyncAnswersBtn');

            // Flagged Questions & LocalStorage Backups
            window.currentActiveQuestionId = null;
            let flaggedQuestions = new Set();
            try {
                const savedFlags = JSON.parse(localStorage.getItem('dlhs_exam_flagged_' + testId + '_' + studentId) || '[]');
                flaggedQuestions = new Set(savedFlags.map(String));
            } catch(e) {}

            function getLocalAnswerCache() {
                try {
                    return JSON.parse(localStorage.getItem('dlhs_exam_answers_' + testId + '_' + studentId) || '{}');
                } catch(e) {
                    return {};
                }
            }

            function setLocalAnswerCache(questionId, answer) {
                try {
                    const cache = getLocalAnswerCache();
                    const qidStr = String(questionId);
                    if (answer && answer !== '0') {
                        cache[qidStr] = answer;
                    } else {
                        delete cache[qidStr];
                    }
                    localStorage.setItem('dlhs_exam_answers_' + testId + '_' + studentId, JSON.stringify(cache));
                } catch(e) {}
            }

            function clearLocalExamCache() {
                try {
                    localStorage.removeItem('dlhs_exam_answers_' + testId + '_' + studentId);
                    localStorage.removeItem('dlhs_exam_flagged_' + testId + '_' + studentId);
                } catch(e) {}
            }
            window.clearLocalExamCache = clearLocalExamCache;

            function toggleFlagQuestion(questionId) {
                const qid = String(questionId);
                if (flaggedQuestions.has(qid)) {
                    flaggedQuestions.delete(qid);
                    showToast('Question unflagged');
                } else {
                    flaggedQuestions.add(qid);
                    showToast('Question flagged for review');
                }
                try {
                    localStorage.setItem('dlhs_exam_flagged_' + testId + '_' + studentId, JSON.stringify([...flaggedQuestions]));
                } catch(e) {}

                updateQuestionFlagUI(qid);
                updateStatusSummary();
            }

            function updateQuestionFlagUI(questionId) {
                const qid = String(questionId);
                const isFlagged = flaggedQuestions.has(qid);
                const $btn = $('.btn-flag-question[data-question-id="' + qid + '"]');
                if ($btn.length) {
                    $btn.toggleClass('is-flagged', isFlagged);
                    $btn.find('i').attr('class', isFlagged ? 'fa fa-bookmark' : 'fa fa-bookmark-o');
                    $btn.find('span').text(isFlagged ? 'Flagged' : 'Flag');
                }
                const $chip = $('.question-chip[data-question-id="' + qid + '"]');
                if ($chip.length) {
                    $chip.toggleClass('flagged', isFlagged);
                }
            }

            let currentChipFilter = 'all';
            function applyChipFilter() {
                $('.question-chip').each(function() {
                    const qid = String($(this).data('question-id'));
                    const isFlagged = flaggedQuestions.has(qid);
                    const isUnanswered = $(this).hasClass('not_answered');

                    if (currentChipFilter === 'all') {
                        $(this).show();
                    } else if (currentChipFilter === 'unanswered') {
                        $(this).toggle(isUnanswered);
                    } else if (currentChipFilter === 'flagged') {
                        $(this).toggle(isFlagged);
                    }
                });
            }

            $(document).on('click', '.chip-filter-btn', function() {
                $('.chip-filter-btn').removeClass('active');
                $(this).addClass('active');
                currentChipFilter = $(this).data('filter');
                applyChipFilter();
            });

            function setConnectionState(state, label) {
                connectionStatus.removeClass('online offline pending').addClass(state);
                connectionStatus.find('span').text(label);
            }
            window.examConnectionHelpers = {
                setState: setConnectionState,
                markDisconnected: function(label) {
                    serverConnected = false;
                    setConnectionState('offline', label || 'Server unreachable');
                    updateResyncButton();
                }
            };

            function getUnsyncedQuestionIds() {
                return Object.keys(questionStates).filter(function(questionId) {
                    return questionStates[questionId] && (questionStates[questionId].status === 'pending' || questionStates[questionId].status === 'error');
                });
            }

            function updateResyncButton() {
                const unsyncedCount = getUnsyncedQuestionIds().length;
                const buttonLabel = unsyncedCount > 0
                    ? 'Resync pending saves (' + unsyncedCount + ')'
                    : 'Resync pending saves';
                resyncButton.html('<i class="fa fa-refresh"></i> ' + buttonLabel);
                resyncButton.prop('disabled', unsyncedCount === 0 || !serverConnected || isResyncing);
            }

            function refreshConnectionStatus(showPending) {
                if (!navigator.onLine) {
                    serverConnected = false;
                    setConnectionState('offline', 'You are offline');
                    updateResyncButton();
                    return;
                }
                if (showPending) {
                    setConnectionState('pending', 'Checking server...');
                }
                $.ajax({
                    url: 'connectionStatus.php',
                    method: 'GET',
                    dataType: 'json',
                    cache: false
                }).done(function(resp) {
                    serverConnected = !!(resp && resp.connected);
                    setConnectionState(serverConnected ? 'online' : 'offline', serverConnected ? 'Connected to server' : 'Server unreachable');
                    
                    if (resp && resp.isPaused) {
                        showPauseModal();
                    } else {
                        hidePauseModal();
                    }
                    
                    updateResyncButton();
                }).fail(function() {
                    serverConnected = false;
                    setConnectionState('offline', 'Server unreachable');
                    updateResyncButton();
                });
            }
            
            let isExamPaused = false;
            function showPauseModal() {
                if ($('#pauseModalOverlay').length === 0) {
                    $('body').append(`
                        <div id="pauseModalOverlay" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:9999999;display:flex;flex-direction:column;align-items:center;justify-content:center;color:white;backdrop-filter:blur(5px);">
                            <i class="fa fa-pause-circle" style="font-size:64px;color:#FFD700;margin-bottom:20px;"></i>
                            <h2 style="font-family:'Outfit',sans-serif;font-weight:700;font-size:32px;margin-bottom:10px;">Exam Paused</h2>
                            <p style="font-family:'Inter',sans-serif;font-size:18px;opacity:0.9;max-width:500px;text-align:center;">Your invigilator has paused this exam. The timer and inputs are locked until the exam is resumed.</p>
                        </div>
                    `);
                }
                isExamPaused = true;
            }

            function hidePauseModal() {
                $('#pauseModalOverlay').remove();
                isExamPaused = false;
            }

            function scrollToQuestion(questionId) {
                const questionBlock = $('.question-block[data-question-id="' + questionId + '"]');
                if (!questionBlock.length) {
                    return;
                }
                window.currentActiveQuestionId = questionId;
                $('.question-chip').removeClass('active');
                $('.question-chip[data-question-id="' + questionId + '"]').addClass('active');
                $('html, body').animate({
                    scrollTop: questionBlock.offset().top - 120
                }, 250);
            }

            function getQuestionStateLabel(state) {
                switch (state) {
                    case 'answered':
                        return 'Answered locally';
                    case 'pending':
                        return 'Saving';
                    case 'saved':
                        return 'Saved successfully';
                    case 'error':
                        return 'Save failed';
                    case 'not_answered':
                    default:
                        return 'Not answered';
                }
            }

            function syncActiveQuestionFromViewport() {
                let activeQuestionId = null;
                const threshold = 170;

                $('.question-block').each(function() {
                    const topOffset = $(this).offset().top - $(window).scrollTop();
                    if (topOffset <= threshold) {
                        activeQuestionId = $(this).data('question-id');
                    }
                });

                if (activeQuestionId === null) {
                    const firstQuestion = $('.question-block').first();
                    if (firstQuestion.length) {
                        activeQuestionId = firstQuestion.data('question-id');
                    }
                }

                $('.question-chip').removeClass('active');
                if (activeQuestionId !== null) {
                    window.currentActiveQuestionId = activeQuestionId;
                    $('.question-chip[data-question-id="' + activeQuestionId + '"]').addClass('active');
                }
            }

            function updateQuestionCardState(questionId) {
                const state = questionStates[questionId] ? questionStates[questionId].status : 'not_answered';
                const block = $('.question-block[data-question-id="' + questionId + '"]');
                block.removeClass('state-not_answered state-answered state-pending state-saved state-error')
                     .addClass('state-' + state);
                $('.question-chip[data-question-id="' + questionId + '"]')
                    .removeClass('not_answered answered pending saved error')
                    .addClass(state);
                $('[data-question-state="' + questionId + '"]')
                    .removeClass('not_answered answered pending saved error')
                    .addClass(state)
                    .text(getQuestionStateLabel(state));
                updateResyncButton();
            }

            function renderNavigator() {
                let html = '';
                Object.keys(questionStates).forEach(function(questionId, index) {
                    const state = questionStates[questionId].status;
                    const isFlagged = flaggedQuestions.has(String(questionId));
                    html += '<button type="button" class="question-chip ' + state + (isFlagged ? ' flagged' : '') + '" data-question-id="' + questionId + '">Q' + (index + 1) + '</button>';
                });
                $('#questionNavigator').html(html);
                Object.keys(questionStates).forEach(function(questionId) {
                    updateQuestionCardState(questionId);
                });
                applyChipFilter();
            }

            function updateStatusSummary() {
                const counts = {
                    not_answered: 0,
                    answered: 0,
                    pending: 0,
                    saved: 0,
                    error: 0
                };
                answeredQuestions.clear();

                // 1. Sync any checked radio inputs in DOM with questionStates
                $('input[type="radio"]:checked').each(function() {
                    const qid = String($(this).attr('name').replace('answer[', '').replace(']', ''));
                    const val = $(this).val();
                    if (!questionStates[qid]) {
                        questionStates[qid] = { status: 'saved', answer: val };
                    } else {
                        questionStates[qid].answer = val;
                        if (questionStates[qid].status === 'not_answered') {
                            questionStates[qid].status = 'saved';
                        }
                    }
                });

                // 2. Tally states across all questions
                Object.keys(questionStates).forEach(function(questionId) {
                    const qData = questionStates[questionId] || {};
                    let state = qData.status || 'not_answered';
                    const ans = qData.answer;

                    // If marked not_answered but has a valid answer value, elevate to saved
                    if (state === 'not_answered' && ans && ans !== '0' && ans !== '') {
                        state = 'saved';
                        qData.status = 'saved';
                    }

                    if (counts.hasOwnProperty(state)) {
                        counts[state] += 1;
                    }

                    // A question is answered if state is not 'not_answered' or it has an answer selected
                    if (state !== 'not_answered' || (ans && ans !== '0' && ans !== '')) {
                        answeredQuestions.add(String(questionId));
                    }
                });

                const totalAnswered = answeredQuestions.size;
                const totalUnanswered = Math.max(0, totalQuestions - totalAnswered);

                // Update all UI elements from the single source of truth
                $('#countNotAnswered').text(totalUnanswered);
                $('#countAnswered').text(totalAnswered);
                $('#countSaved').text(counts.saved);
                $('#countPending').text(counts.pending + counts.error);
                $('#progressAnsweredCount').text(totalAnswered);

                $('#filterCountAll').text(totalQuestions);
                $('#filterCountUnanswered').text(totalUnanswered);
                $('#filterCountFlagged').text(flaggedQuestions.size);

                updateSubmitButton();
                updateResyncButton();
                applyChipFilter();

                const percentage = totalQuestions > 0 ? (totalAnswered / totalQuestions) * 100 : 0;
                $('.progress-fill').css('width', percentage + '%');
            }

            // Update the submit button visibility
            function updateSubmitButton() {
                if (totalQuestions > 0) {
                    $('#fixedSubmitButton').show();
                    $('#examSubmitBar').addClass('is-visible');
                    const answered = answeredQuestions.size;
                    const unanswered = Math.max(0, totalQuestions - answered);
                    if (unanswered === 0) {
                        $('.exam-submit-note').html('<i class="fa fa-check-circle"></i> <span>All ' + totalQuestions + ' questions answered. You can submit now.</span>');
                    } else {
                        $('.exam-submit-note').html('<i class="fa fa-info-circle"></i> <span>' + answered + ' of ' + totalQuestions + ' questions answered (' + unanswered + ' unanswered).</span>');
                    }
                } else {
                    $('#fixedSubmitButton').hide();
                    $('#examSubmitBar').removeClass('is-visible');
                }
            }

            // Initialize answeredQuestions set from checked radios
            function initializeAnsweredSet() {
                $('input[type="radio"]').each(function() {
                    const qid = String($(this).attr('name').replace('answer[', '').replace(']', ''));
                    if (!questionStates[qid]) {
                        questionStates[qid] = { status: 'not_answered', answer: '' };
                    }
                });
                $('input[type="radio"]:checked').each(function() {
                    const qid = String($(this).attr('name').replace('answer[', '').replace(']', ''));
                    questionStates[qid] = { status: 'saved', answer: $(this).val() };
                });
                renderNavigator();
                updateStatusSummary();
            }

            // When questions are loaded, set totalQuestions and initialize the set
            function onQuestionsLoaded() {
                totalQuestions = $('.question-block').length;
                $('#progressTotalCount').text(totalQuestions);
                initializeAnsweredSet();
                syncActiveQuestionFromViewport();
            }

            function persistAnswer(questionId, answer, options) {
                const settings = options || {};
                const isCleared = (answer === '0' || answer === '');

                return $.ajax({
                    url: 'exam/saveAnswer.php',
                    method: 'POST',
                    data: {
                        questionId: questionId,
                        answer: answer,
                        testId: testId,
                        studentId: studentId
                    },
                    dataType: 'json'
                }).done(function(response) {
                    if (response && response.status === 'success') {
                        questionStates[questionId] = {
                            status: isCleared ? 'not_answered' : 'saved',
                            answer: isCleared ? '' : answer
                        };
                        updateQuestionCardState(questionId);
                        updateStatusSummary();
                        refreshConnectionStatus(false);
                        if (!settings.silent) {
                            showToast(isCleared ? 'Answer cleared' : 'Answer saved');
                        }
                    } else {
                        console.error('Error saving answer:', response ? response.message : 'Unknown error');
                        questionStates[questionId] = { status: 'error', answer: answer };
                        updateQuestionCardState(questionId);
                        updateStatusSummary();
                        setConnectionState('offline', 'Save failed - check connection');
                        if (!settings.silent) {
                            showToast('Error saving answer');
                        }
                    }
                }).fail(function(xhr, status, error) {
                    console.error('Error saving answer:', error);
                    questionStates[questionId] = { status: 'error', answer: answer };
                    updateQuestionCardState(questionId);
                    updateStatusSummary();
                    setConnectionState('offline', 'Save failed - check connection');
                    if (!settings.silent) {
                        showToast('Error saving answer');
                    }
                });
            }

            function resyncPendingAnswers() {
                const unsyncedQuestionIds = getUnsyncedQuestionIds();
                if (!serverConnected || unsyncedQuestionIds.length === 0 || isResyncing) {
                    updateResyncButton();
                    return;
                }

                isResyncing = true;
                updateResyncButton();
                setConnectionState('pending', 'Resyncing pending saves...');
                $('#submitMessage').html('<div class="alert alert-info"><i class="fa fa-refresh fa-spin"></i> Resyncing pending answers with the server...</div>');

                let completedCount = 0;

                function finalizeResync() {
                    isResyncing = false;
                    updateStatusSummary();
                    if (getUnsyncedQuestionIds().length === 0) {
                        $('#submitMessage').html('<div class="alert alert-success"><i class="fa fa-check-circle"></i> All pending answers have been resynced successfully.</div>');
                        refreshConnectionStatus(false);
                    } else {
                        $('#submitMessage').html('<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Some answers still need attention. Please try resync again.</div>');
                        setConnectionState(serverConnected ? 'online' : 'offline', serverConnected ? 'Connected to server' : 'Server unreachable');
                    }
                    updateResyncButton();
                }

                unsyncedQuestionIds.forEach(function(questionId) {
                    const answer = questionStates[questionId] ? questionStates[questionId].answer : '';
                    if (!answer) {
                        completedCount += 1;
                        if (completedCount === unsyncedQuestionIds.length) {
                            finalizeResync();
                        }
                        return;
                    }

                    questionStates[questionId].status = 'pending';
                    updateQuestionCardState(questionId);
                    updateStatusSummary();

                    persistAnswer(questionId, answer, { silent: true }).always(function() {
                        completedCount += 1;
                        if (completedCount === unsyncedQuestionIds.length) {
                            finalizeResync();
                        }
                    });
                });
            }

            // Listen for Clear Choice button clicks
            $(document).on('click', '.btn-clear-answer', function(e) {
                e.preventDefault();
                const questionId = String($(this).data('question-id'));
                
                // Deselect radios for this question
                $('input[name="answer[' + questionId + ']"]').prop('checked', false);
                $(this).hide();
                
                setLocalAnswerCache(questionId, '0');
                questionStates[questionId] = {
                    status: 'pending',
                    answer: '0'
                };
                updateQuestionCardState(questionId);
                updateStatusSummary();
                setConnectionState('pending', 'Clearing answer...');
                persistAnswer(questionId, '0').always(function() {
                    $('.progress-panel').addClass('updating');
                    $('#progressAnsweredCount').addClass('updating');
                    setTimeout(() => {
                        $('.progress-panel').removeClass('updating');
                        $('#progressAnsweredCount').removeClass('updating');
                    }, 500);
                });
            });

            // Listen for Flag button clicks
            $(document).on('click', '.btn-flag-question', function(e) {
                e.preventDefault();
                const questionId = $(this).data('question-id');
                toggleFlagQuestion(questionId);
            });

            // Listen for radio button changes
            $(document).on('change', 'input[type="radio"]', function() {
                const questionId = $(this).attr('name').replace('answer[', '').replace(']', '');
                const answer = $(this).val();
                
                // Show Clear Answer button
                $('.btn-clear-answer[data-question-id="' + questionId + '"]').show();
                setLocalAnswerCache(questionId, answer);

                questionStates[questionId] = {
                    status: 'pending',
                    answer: answer
                };
                updateQuestionCardState(questionId);
                updateStatusSummary();
                setConnectionState('pending', 'Saving answer...');
                persistAnswer(questionId, answer).always(function() {
                    $('.progress-panel').addClass('updating');
                    $('#progressAnsweredCount').addClass('updating');
                    setTimeout(() => {
                        $('.progress-panel').removeClass('updating');
                        $('#progressAnsweredCount').removeClass('updating');
                    }, 500);
                });
            });

            // Then load questions
            $.ajax({
                type: "GET",
                url: 'fetchAllQuestions.php',
                dataType: 'json',
                cache: false,
                success: function(questions) {
                    if (!questions || !questions.length) {
                        $('#questionsContainer').html(`
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> No questions found for this test.
                            </div>
                        `);
                        return;
                    }
                    const localAnswerCache = getLocalAnswerCache();
                    let restoredCount = 0;
                    let html = '';
                    questions.forEach((q, idx) => {
                        if (!q.questionId) {
                            console.error('Invalid question data:', q);
                            return;
                        }
                        const qidStr = String(q.questionId);
                        let selected = q.selectedOption;
                        if (!selected || selected === '0') {
                            if (localAnswerCache[qidStr] && localAnswerCache[qidStr] !== '0') {
                                selected = localAnswerCache[qidStr];
                                q.selectedOption = selected;
                                restoredCount++;
                                persistAnswer(q.questionId, selected, { silent: true });
                            }
                        }

                        const hasAnswer = (selected && selected !== '0');
                        const isFlagged = flaggedQuestions.has(qidStr);

                        questionStates[q.questionId] = {
                            status: hasAnswer ? 'saved' : 'not_answered',
                            answer: hasAnswer ? selected : ''
                        };
                        const stimulusHtml = q.stimulusContent ? `
                            <div class="question-stimulus-card">
                                <div class="question-stimulus-meta">
                                    <span class="question-stimulus-badge"><i class="fa fa-book"></i>${escapeHtml(q.stimulusType || 'SHARED')}</span>
                                    <span class="question-stimulus-title">${escapeHtml(q.stimulusTitle || 'Shared material')}</span>
                                </div>
                                <div class="question-stimulus-content">${q.stimulusContent}</div>
                            </div>
                        ` : '';

                        const optionEHtml = (q.optionE && String(q.optionE).trim() !== '') ? `
                            <div class="option-container">
                                <input type="radio" name="answer[${q.questionId}]" value="E" id="q${q.questionId}E" ${selected === 'E' ? 'checked' : ''}>
                                <label class="option-label" for="q${q.questionId}E">${q.optionE}</label>
                            </div>
                        ` : '';

                        html += `
                            <div class="question-block ${hasAnswer ? 'state-saved' : 'state-not_answered'}" data-question-id="${q.questionId}">
                                ${stimulusHtml}
                                <div class="question-header-row">
                                    <div class="d-flex align-items-center" style="gap:8px; flex-wrap:wrap;">
                                        <span class="question-number-pill">Question ${idx + 1}</span>
                                        <button type="button" class="btn-flag-question ${isFlagged ? 'is-flagged' : ''}" data-question-id="${q.questionId}" title="Flag / Bookmark for review">
                                            <i class="fa ${isFlagged ? 'fa-bookmark' : 'fa-bookmark-o'}"></i> <span>${isFlagged ? 'Flagged' : 'Flag'}</span>
                                        </button>
                                        <button type="button" class="btn-clear-answer" data-question-id="${q.questionId}" style="${hasAnswer ? '' : 'display:none;'}" title="Clear chosen answer">
                                            <i class="fa fa-eraser"></i> <span>Clear</span>
                                        </button>
                                    </div>
                                    <span class="question-state-badge ${hasAnswer ? 'saved' : 'not_answered'}" data-question-state="${q.questionId}">${hasAnswer ? 'Saved successfully' : 'Not answered'}</span>
                                </div>
                                <div class="question-title">${q.question}</div>
                                <div class="option-container">
                                    <input type="radio" name="answer[${q.questionId}]" value="A" id="q${q.questionId}A" ${selected === 'A' ? 'checked' : ''}>
                                    <label class="option-label" for="q${q.questionId}A">${q.optionA}</label>
                                </div>
                                <div class="option-container">
                                    <input type="radio" name="answer[${q.questionId}]" value="B" id="q${q.questionId}B" ${selected === 'B' ? 'checked' : ''}>
                                    <label class="option-label" for="q${q.questionId}B">${q.optionB}</label>
                                </div>
                                <div class="option-container">
                                    <input type="radio" name="answer[${q.questionId}]" value="C" id="q${q.questionId}C" ${selected === 'C' ? 'checked' : ''}>
                                    <label class="option-label" for="q${q.questionId}C">${q.optionC}</label>
                                </div>
                                <div class="option-container">
                                    <input type="radio" name="answer[${q.questionId}]" value="D" id="q${q.questionId}D" ${selected === 'D' ? 'checked' : ''}>
                                    <label class="option-label" for="q${q.questionId}D">${q.optionD}</label>
                                </div>
                                ${optionEHtml}
                            </div>
                        `;
                    });
                    $('#questionsContainer').html(html);
                    onQuestionsLoaded();
                    if (restoredCount > 0) {
                        showToast(`Restored ${restoredCount} saved answer${restoredCount > 1 ? 's' : ''} from local cache`);
                    }
                    refreshConnectionStatus(false);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error loading questions:', jqXHR.responseText, textStatus, errorThrown);
                    setConnectionState('offline', 'Failed to load questions');
                    $('#questionsContainer').html(`
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-circle"></i> Failed to load questions. Please try refreshing the page.
                            <br><small>${jqXHR.responseText || errorThrown}</small>
                        </div>
                    `);
                }
            });

            // Timer functionality with server sync to prevent reset on reload
            (function initPersistentTimer() {
                var remaining = <?php echo $testTime; ?>;
                var timerDisplay = document.getElementById('hms');
                if (!remaining || remaining < 0) {
                    remaining = 0;
                }

                let warned5Min = false;
                let warned1Min = false;

                function checkUrgency(seconds) {
                    const $timer = $('#hms');
                    if (seconds <= 60) {
                        $timer.removeClass('timer-warning').addClass('timer-critical');
                        if (!warned1Min && seconds > 0) {
                            warned1Min = true;
                            showToast('🚨 1 minute remaining! Exam will auto-submit at 00:00:00.');
                        }
                    } else if (seconds <= 300) {
                        $timer.removeClass('timer-critical').addClass('timer-warning');
                        if (!warned5Min) {
                            warned5Min = true;
                            showToast('⚠️ 5 minutes remaining! Please review your answers.');
                        }
                    } else {
                        $timer.removeClass('timer-warning timer-critical');
                    }
                }

                function render(seconds) {
                    var hour = Math.floor(seconds/3600);
                    var min = Math.floor(seconds%3600/60);
                    var sec = Math.floor(seconds%3600%60);
                    timerDisplay.innerHTML =
                        (hour = hour < 10 ? "0" + hour : hour) + " : " +
                        (min = min < 10 ? "0" + min : min) + " : " +
                        (sec = sec < 10 ? "0" + sec : sec);
                    checkUrgency(seconds);
                }

                function autosubmit() {
                    // Clear local storage cache
                    clearLocalExamCache();

                    // DISABLE ALL SECURITY FEATURES BEFORE AUTO-SUBMISSION
                    if (typeof ExamSecurity !== 'undefined' && typeof ExamSecurity.disableAll === 'function') {
                        ExamSecurity.disableAll();
                        console.log("✅ Security features disabled (auto-submit)");
                    }
                    
                    // Bypass confirmation; submit answers and redirect to results
                    let formData = $('#allQuestionsForm').serialize();
                    $.post('submitAllAnswers.php', formData, function(response) {
                        if (response && response.success) {
                            console.log("Auto-submission successful, redirecting...");
                            // Set a fallback timeout just in case
                            setTimeout(function() { window.location.href = 'result/index.php'; }, 100);
                            window.location.href = 'result/index.php';
                        } else {
                            serverConnected = false;
                            setConnectionState('offline', 'Submission not confirmed');
                            $('#submitMessage').html('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Time expired, but the server did not confirm your submission. Reconnect and try again immediately.</div>');
                        }
                    }, 'json').fail(function(){
                        serverConnected = false;
                        setConnectionState('offline', 'Submission not confirmed');
                        $('#submitMessage').html('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Time expired, but the server could not be reached. Reconnect and try submitting again.</div>');
                    });
                }

                render(remaining);

                // Local second ticker for smooth countdown
                var localTick = setInterval(function(){
                    if (isExamPaused) {
                        return; // Freeze local countdown when paused
                    }
                    if (remaining <= 1) {
                        clearInterval(localTick);
                        clearInterval(syncTick);
                        autosubmit();
                        return;
                    }
                    remaining--;
                    render(remaining);
                }, 1000);

                // Server sync every 5 seconds to persist and correct drift
                function syncOnce() {
                    $.ajax({
                        url: 'tickTimer.php',
                        method: 'POST',
                        dataType: 'json',
                        cache: false
                    }).done(function(resp){
                        if (resp && resp.success) {
                            if (typeof resp.remainingTime === 'number') {
                                remaining = resp.remainingTime;
                                render(remaining);
                            }
                            if (resp.shouldSubmit) {
                                clearInterval(localTick);
                                clearInterval(syncTick);
                                autosubmit();
                            }
                        } else {
                            if (resp && resp.shouldSubmit) {
                                clearInterval(localTick);
                                clearInterval(syncTick);
                                autosubmit();
                            }
                        }
                    }).fail(function(){
                        // On failure, keep local timer; next sync may succeed
                    });
                }
                var syncTick = setInterval(syncOnce, 15000);
                // Immediate first sync to align if reloaded
                syncOnce();
                
                // Track test activity every 30 seconds
                function updateTestTracking() {
                    $.ajax({
                        url: 'update_test_tracking.php',
                        method: 'POST',
                        data: {
                            remainingTime: remaining
                        },
                        cache: false
                    });
                }
                // Update tracking every 30 seconds
                setInterval(updateTestTracking, 30000);
                setInterval(function() {
                    refreshConnectionStatus(false);
                }, 30000);
                // Initial tracking update
                updateTestTracking();
            })();

            // Handle form submission
            $('#allQuestionsForm').on('submit', function(e) {
                e.preventDefault();
                showModal();
            });

            // Handle submit button click directly
            $(document).on('click', '#fixedSubmitButton, .exam-submit-trigger', function(e) {
                e.preventDefault();
                showModal();
            });

            // Modal functions
            function showModal() {
                updateStatusSummary();
                window.isExamSubmitting = false;
                const total = totalQuestions;
                const answered = answeredQuestions.size;
                const unanswered = Math.max(0, total - answered);
                const flagged = flaggedQuestions.size;

                $('#modalTotalQuestions').text(total);
                $('#modalAnsweredQuestions').text(answered);
                $('#modalUnansweredQuestions').text(unanswered);
                $('#modalFlaggedQuestions').text(flagged);

                if (unanswered > 0) {
                    $('#modalWarningCount').text(unanswered);
                    $('#modalUnansweredWarning').show();
                } else {
                    $('#modalUnansweredWarning').hide();
                }

                const unsavedQuestions = Object.keys(questionStates).filter(function(questionId) {
                    return questionStates[questionId].status === 'pending' || questionStates[questionId].status === 'error';
                });
                if (!serverConnected || unsavedQuestions.length > 0) {
                    $('#modalUnsavedWarning').html('<i class="fa fa-exclamation-triangle"></i> Notice: ' + (unsavedQuestions.length > 0 ? unsavedQuestions.length + ' answer(s) are pending server sync.' : 'Server connection is slow/offline.') + ' Submitting now will attempt to sync all answers.').show();
                } else {
                    $('#modalUnsavedWarning').hide();
                }

                $('#modalSubmitError').hide().empty();
                $('#confirmSubmit').prop('disabled', false).html('<i class="fa fa-check"></i> Submit Exam');
                $('.modal-btn-no').prop('disabled', false);

                $('#confirmationModal').fadeIn(300);
            }
            window.showModal = showModal;

            function hideModal() {
                $('#confirmationModal').fadeOut(200);
            }
            window.hideModal = hideModal;

            $('#btnReviewUnanswered').on('click', function() {
                hideModal();
                const qids = Object.keys(questionStates);
                const firstUnansweredQid = qids.find(function(qid) {
                    return !questionStates[qid] || questionStates[qid].status === 'not_answered' || !questionStates[qid].answer || questionStates[qid].answer === '0';
                });
                if (firstUnansweredQid) {
                    scrollToQuestion(firstUnansweredQid);
                }
            });

            // Close modal when clicking No button or clicking outside
            $('.modal-btn-no').on('click', hideModal);
            $(window).on('click', function(e) {
                if ($(e.target).is('#confirmationModal')) {
                    hideModal();
                }
            });
            $(document).on('click', '.question-chip', function() {
                scrollToQuestion($(this).data('question-id'));
            });
            $(window).on('scroll', function() {
                syncActiveQuestionFromViewport();
            });
            $(window).on('online', function() {
                refreshConnectionStatus(true);
                if (getUnsyncedQuestionIds().length > 0) {
                    $('#submitMessage').html('<div class="alert alert-info"><i class="fa fa-wifi"></i> Connection restored. You can now click <strong>Resync pending saves</strong>.</div>');
                }
            });
            $(window).on('offline', function() {
                serverConnected = false;
                setConnectionState('offline', 'You are offline');
                updateResyncButton();
            });
            resyncButton.on('click', function() {
                resyncPendingAnswers();
            });

            (function initCalculator() {
                const calculatorPanel = $('#calculatorPanel');
                const calculatorDisplay = $('#calculatorDisplay');
                let expression = '';
                let resultDisplayed = false;

                function updateCalculatorDisplay(value) {
                    let displayValue = value ? String(value) : '0';
                    displayValue = displayValue
                        .replace(/Math\.sqrt\(/g, 'sqrt(')
                        .replace(/Math\.log10\(/g, 'log(')
                        .replace(/Math\.log\(/g, 'ln(')
                        .replace(/Math\.sin\(/g, 'sin(')
                        .replace(/Math\.cos\(/g, 'cos(')
                        .replace(/Math\.tan\(/g, 'tan(')
                        .replace(/Math\.PI/g, 'pi')
                        .replace(/Math\.E/g, 'e')
                        .replace(/\*\*2/g, '^2')
                        .replace(/\*\*/g, '^')
                        .replace(/\*/g, 'x');
                    calculatorDisplay.text(displayValue);
                }

                function appendCalculatorValue(value) {
                    const token = String(value);
                    const operators = ['+', '-', '*', '/', '%', '**'];
                    const lastToken = expression.slice(-1);

                    if (resultDisplayed) {
                        expression = '';
                        resultDisplayed = false;
                        updateCalculatorDisplay(expression);
                    }

                    if (operators.indexOf(token) !== -1) {
                        if (!expression && token !== '-') {
                            return;
                        }
                        if (expression.slice(-2) === '**') {
                            expression = expression.slice(0, -2) + token;
                            updateCalculatorDisplay(expression);
                            return;
                        }
                        if (['+', '-', '*', '/', '%'].indexOf(lastToken) !== -1) {
                            expression = expression.slice(0, -1) + token;
                            updateCalculatorDisplay(expression);
                            return;
                        }
                    }

                    if (token === '.') {
                        const currentNumber = expression.split(/[+\-*/%]/).pop();
                        if (currentNumber.indexOf('.') !== -1) {
                            return;
                        }
                    }

                    expression += token;
                    updateCalculatorDisplay(expression);
                }

                function calculateExpression() {
                    if (!expression) {
                        return;
                    }
                    if (!/^[0-9+\-*/%.(),\sA-Za-z]+$/.test(expression)) {
                        updateCalculatorDisplay('Error');
                        expression = '';
                        resultDisplayed = true;
                        return;
                    }
                    try {
                        const result = Function('"use strict"; return (' + expression + ')')();
                        if (!Number.isFinite(result)) {
                            throw new Error('Invalid result');
                        }
                        expression = String(Math.round((result + Number.EPSILON) * 100000000) / 100000000);
                        resultDisplayed = true;
                        updateCalculatorDisplay(expression);
                    } catch (error) {
                        updateCalculatorDisplay('Error');
                        expression = '';
                        resultDisplayed = true;
                    }
                }

                $('#calculatorToggle').on('click', function() {
                    calculatorPanel.toggleClass('is-open');
                });
                $('#calculatorClose').on('click', function() {
                    calculatorPanel.removeClass('is-open');
                });
                $('.calculator-mode-switch').on('click', 'button', function() {
                    const mode = $(this).data('calculator-mode');
                    $('.calculator-mode-switch button').removeClass('active');
                    $(this).addClass('active');
                    calculatorPanel.toggleClass('mode-scientific', mode === 'scientific');
                });
                $('.calculator-keys').on('click', 'button', function() {
                    const action = $(this).data('calc-action');
                    const value = $(this).data('calc-value');

                    if (action === 'clear') {
                        expression = '';
                        resultDisplayed = false;
                        updateCalculatorDisplay(expression);
                        return;
                    }
                    if (action === 'backspace') {
                        if (resultDisplayed) {
                            expression = '';
                            resultDisplayed = false;
                            updateCalculatorDisplay(expression);
                            return;
                        }
                        resultDisplayed = false;
                        expression = expression.slice(0, -1);
                        updateCalculatorDisplay(expression);
                        return;
                    }
                    if (action === 'equals') {
                        calculateExpression();
                        return;
                    }
                    if (typeof value !== 'undefined' && expression.length < 48) {
                        appendCalculatorValue(value);
                    }
                });
            })();

            // Image diagram click-to-zoom
            $(document).on('click', '.question-title img, .option-label img, .question-stimulus-content img', function() {
                const src = $(this).attr('src');
                const alt = $(this).attr('alt') || 'Question diagram';
                $('#lightboxTargetImg').attr('src', src);
                $('#lightboxCaption').text(alt);
                $('#imgLightboxModal').addClass('is-open');
            });
            $('#lightboxCloseBtn, #imgLightboxModal').on('click', function(e) {
                if (e.target === this || e.target.id === 'lightboxCloseBtn') {
                    $('#imgLightboxModal').removeClass('is-open');
                }
            });

            // Font zoom controls
            $('.font-zoom-btn').on('click', function() {
                const size = $(this).data('size');
                $('.font-zoom-btn').removeClass('active');
                $(this).addClass('active');
                $('body').removeClass('fs-sm fs-md fs-lg').addClass('fs-' + size);
                try {
                    localStorage.setItem('dlhs_exam_fontsize', size);
                } catch(e) {}
            });
            try {
                const savedFontSize = localStorage.getItem('dlhs_exam_fontsize') || 'md';
                $('body').removeClass('fs-sm fs-md fs-lg').addClass('fs-' + savedFontSize);
                $('.font-zoom-btn[data-size="' + savedFontSize + '"]').addClass('active').siblings().removeClass('active');
            } catch(e) {}

            // Shortcuts Modal handlers
            $('#shortcutsToggleBtn').on('click', function() {
                $('#shortcutsModal').fadeIn(200);
            });
            $('#closeShortcutsModal, #shortcutsModal .modal-btn-no').on('click', function() {
                $('#shortcutsModal').fadeOut(200);
            });
            $(window).on('click', function(e) {
                if ($(e.target).is('#shortcutsModal')) {
                    $('#shortcutsModal').fadeOut(200);
                }
            });

            // CBT Keyboard shortcuts navigation
            function navigateToAdjacentQuestion(currentQid, direction) {
                const qids = Object.keys(questionStates);
                const currentIndex = qids.indexOf(String(currentQid));
                if (currentIndex === -1) return;
                const targetIndex = currentIndex + direction;
                if (targetIndex >= 0 && targetIndex < qids.length) {
                    scrollToQuestion(qids[targetIndex]);
                }
            }

            $(document).on('keydown', function(e) {
                if ($(e.target).is('input[type="text"], textarea') || ($(e.target).is(':input') && !$(e.target).is(':radio, :button'))) {
                    return;
                }

                if ($('#confirmationModal').is(':visible') || $('#shortcutsModal').is(':visible') || $('#imgLightboxModal').hasClass('is-open')) {
                    if (e.key === 'Escape' || e.keyCode === 27) {
                        hideModal();
                        $('#shortcutsModal').fadeOut(200);
                        $('#imgLightboxModal').removeClass('is-open');
                    }
                    return;
                }

                const key = e.key ? e.key.toUpperCase() : '';

                if (key === 'C' && !e.ctrlKey && !e.altKey && !e.metaKey) {
                    $('#calculatorToggle').trigger('click');
                    return;
                }

                if ((e.key === 'Escape' || e.keyCode === 27) && $('#calculatorPanel').hasClass('is-open')) {
                    $('#calculatorClose').trigger('click');
                    return;
                }

                const currentQid = window.currentActiveQuestionId;
                if (!currentQid) return;

                if (['A', 'B', 'C', 'D', 'E'].indexOf(key) !== -1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
                    const $radio = $('input[name="answer[' + currentQid + ']"][value="' + key + '"]');
                    if ($radio.length) {
                        $radio.prop('checked', true).trigger('change');
                        $radio.focus();
                    }
                    return;
                }

                if (key === 'F' && !e.ctrlKey && !e.altKey && !e.metaKey) {
                    toggleFlagQuestion(currentQid);
                    return;
                }

                if ((key === 'N' || e.keyCode === 40 || e.keyCode === 39) && !e.ctrlKey && !e.altKey && !e.metaKey) {
                    e.preventDefault();
                    navigateToAdjacentQuestion(currentQid, 1);
                    return;
                }

                if ((key === 'P' || e.keyCode === 38 || e.keyCode === 37) && !e.ctrlKey && !e.altKey && !e.metaKey) {
                    e.preventDefault();
                    navigateToAdjacentQuestion(currentQid, -1);
                    return;
                }

                if (key === 'S' && !e.ctrlKey && !e.altKey && !e.metaKey) {
                    e.preventDefault();
                    showModal();
                    return;
                }

                if ((key === 'ENTER' || e.keyCode === 13) && $('#confirmationModal').is(':visible')) {
                    e.preventDefault();
                    window.confirmSubmitHandler();
                    return;
                }
            });

            refreshConnectionStatus(true);
        });
    </script>
    <script>
    // Make confirmSubmitHandler globally accessible for auto-submit
    window.confirmSubmitHandler = function() {
        console.log("Confirm submit clicked!");
        
        if (window.isExamSubmitting) {
            console.log("Submission already in progress, ignoring duplicate trigger.");
            return;
        }
        window.isExamSubmitting = true;

        $('#confirmSubmit').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Submitting...');
        $('.modal-btn-no').prop('disabled', true);
        $('#modalSubmitError').hide().empty();
        
        try {
            // DISABLE ALL SECURITY FEATURES BEFORE SUBMISSION
            if (typeof ExamSecurity !== 'undefined' && typeof ExamSecurity.disableAll === 'function') {
                try {
                    ExamSecurity.disableAll();
                } catch(e) {
                    console.warn("Could not disable security features:", e);
                }
                console.log("✅ Security features disabled after confirmation");
            }
            
            // Clear local storage cache safely
            try {
                if (typeof window.clearLocalExamCache === 'function') {
                    window.clearLocalExamCache();
                } else if (typeof clearLocalExamCache === 'function') {
                    clearLocalExamCache();
                }
            } catch(e) {
                console.warn("Could not clear local exam cache:", e);
            }
            
            let formData = $('#allQuestionsForm').serialize();
            if (!formData || formData.indexOf('testId=') === -1) {
                const tid = <?php echo json_encode($testId); ?>;
                const sid = <?php echo json_encode($_SESSION['studentId']); ?>;
                formData = (formData ? formData + '&' : '') + 'testId=' + encodeURIComponent(tid) + '&studentId=' + encodeURIComponent(sid);
            }
            console.log("Form data to submit:", formData);
            
            // Submit with explicit timeout and error recovery
            $.ajax({
                url: 'submitAllAnswers.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    console.log("AJAX response:", response);
                    if (response && response.success) {
                        console.log("Redirecting to result page...");
                        $('#confirmationModal').fadeOut(100);
                        setTimeout(function() {
                            window.location.href = 'result/index.php';
                        }, 100);
                        window.location.href = 'result/index.php';
                    } else {
                        window.isExamSubmitting = false;
                        $('#confirmSubmit').prop('disabled', false).html('<i class="fa fa-check"></i> Submit Exam');
                        $('.modal-btn-no').prop('disabled', false);
                        const errMsg = (response && response.message) ? response.message : "Submission failed. Please try again.";
                        $('#modalSubmitError').html('<i class="fa fa-exclamation-circle"></i> ' + errMsg).show();
                        $('#submitMessage').html('<div class="alert alert-danger">' + errMsg + '</div>');
                        if (window.examConnectionHelpers) {
                            window.examConnectionHelpers.markDisconnected('Submission not confirmed');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Submission failed:", xhr.responseText, status, error);
                    window.isExamSubmitting = false;
                    $('#confirmSubmit').prop('disabled', false).html('<i class="fa fa-check"></i> Submit Exam');
                    $('.modal-btn-no').prop('disabled', false);
                    let errMsg = "Submission failed because the server could not be reached. Please check your connection and submit again.";
                    if (status === 'timeout') {
                        errMsg = "Submission request timed out. Please check your connection and click Submit Exam again.";
                    } else {
                        try {
                            if (xhr.responseText) {
                                const parsed = JSON.parse(xhr.responseText);
                                if (parsed && parsed.message) errMsg = parsed.message;
                            }
                        } catch(e) {}
                    }
                    $('#modalSubmitError').html('<i class="fa fa-exclamation-circle"></i> ' + errMsg).show();
                    $('#submitMessage').html('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> ' + errMsg + '</div>');
                    if (window.examConnectionHelpers) {
                        window.examConnectionHelpers.markDisconnected('Submission not confirmed');
                    }
                }
            });
        } catch (err) {
            console.error("Synchronous error during submission setup:", err);
            window.isExamSubmitting = false;
            $('#confirmSubmit').prop('disabled', false).html('<i class="fa fa-check"></i> Submit Exam');
            $('.modal-btn-no').prop('disabled', false);
            $('#modalSubmitError').html('<i class="fa fa-exclamation-circle"></i> An unexpected error occurred: ' + err.message).show();
        }
    };

    $(document).on('click', '#confirmSubmit', function(e) {
        e.preventDefault();
        window.confirmSubmitHandler();
    });
    </script>
    <!-- Bootstrap JS dependencies -->
    <script src="js/bootstrap.min.js"></script>
</body>
</html> 
