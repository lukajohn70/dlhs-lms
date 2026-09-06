<?php
session_start();
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
}

// Track test activity
$totalQuestions = $_SESSION['totalQuestions'];
$questionsTableName = $_SESSION['questTableName'];
$answersTableName = $_SESSION['answersTableName'];

// Count answered questions
$answeredCount = 0;
$countQuery = "SELECT COUNT(*) as count FROM `$answersTableName` WHERE userLoginId = ? AND testId = ? AND selectedOption != 0";
$countStmt = $connection->prepare($countQuery);
$countStmt->bind_param("ii", $studentId, $testId);
$countStmt->execute();
$countResult = $countStmt->get_result();
if ($countRow = $countResult->fetch_assoc()) {
    $answeredCount = $countRow['count'];
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
    <link rel="stylesheet" type="text/css" href="vendor1/bootstrap/css/bootstrap.min.css">
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
    </style>
</head>
<body>
    <div class="exam-header">
        <div class="exam-info">
            <div class="student-info">
                <div class="test-name" id="testName"></div>
                <div class="student-name">Welcome, <strong><?php echo htmlspecialchars($studentName); ?></strong></div>
            </div>
            <div class="status-actions">
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
            <button type="submit" form="allQuestionsForm" class="btn btn-success btn-lg btn-pill exam-submit-trigger" id="fixedSubmitButton">
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

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-center w-100"><i class="fa fa-warning"></i> Please confirm</h5>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="text-dark text-center d-block mb-4">Are you sure you wish to submit? Note that you will not be able to restart or continue this test after submitting.</label>
                        <div class="text-center">
                            <button type="button" class="btn btn-primary modal-btn modal-btn-yes" id="confirmSubmit" onclick="confirmSubmitHandler()">
                                <i class="fa fa-check"></i> Yes
                            </button>
                        </div>
                        <div class="text-center">
                            <button type="button" class="btn btn-primary modal-btn modal-btn-no" data-dismiss="modal">
                                <i class="fa fa-times"></i> No
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <hr>
                </div>
            </div>
        </div>
    </div>

    <script src="vendor1/jquery/jquery-3.2.1.min.js"></script>
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
                    html += '<button type="button" class="question-chip ' + state + '" data-question-id="' + questionId + '">Q' + (index + 1) + '</button>';
                });
                $('#questionNavigator').html(html);
                Object.keys(questionStates).forEach(function(questionId) {
                    updateQuestionCardState(questionId);
                });
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

                Object.keys(questionStates).forEach(function(questionId) {
                    const state = questionStates[questionId].status;
                    if (counts.hasOwnProperty(state)) {
                        counts[state] += 1;
                    }
                    if (state !== 'not_answered') {
                        answeredQuestions.add(questionId);
                    }
                });

                $('#countNotAnswered').text(counts.not_answered);
                $('#countAnswered').text(counts.answered + counts.pending + counts.saved + counts.error);
                $('#countSaved').text(counts.saved);
                $('#countPending').text(counts.pending + counts.error);
                $('#progressAnsweredCount').text(answeredQuestions.size);
                updateSubmitButton();
                updateResyncButton();

                const percentage = totalQuestions > 0 ? (answeredQuestions.size / totalQuestions) * 100 : 0;
                $('.progress-fill').css('width', percentage + '%');
            }

            // Update the submit button visibility
            function updateSubmitButton() {
                if (answeredQuestions.size === totalQuestions && totalQuestions > 0) {
                    $('#fixedSubmitButton').show();
                    $('#examSubmitBar').addClass('is-visible');
                } else {
                    $('#fixedSubmitButton').hide();
                    $('#examSubmitBar').removeClass('is-visible');
                }
            }

            // Initialize answeredQuestions set from checked radios
            function initializeAnsweredSet() {
                $('input[type="radio"]').each(function() {
                    const qid = $(this).attr('name').replace('answer[', '').replace(']', '');
                    if (!questionStates[qid]) {
                        questionStates[qid] = { status: 'not_answered' };
                    }
                });
                $('input[type="radio"]:checked').each(function() {
                    const qid = $(this).attr('name').replace('answer[', '').replace(']', '');
                    questionStates[qid] = { status: 'saved' };
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
                        questionStates[questionId] = { status: 'saved', answer: answer };
                        updateQuestionCardState(questionId);
                        updateStatusSummary();
                        refreshConnectionStatus(false);
                        if (!settings.silent) {
                            showToast('Answer saved');
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

            // Listen for radio button changes
            $(document).on('change', 'input[type="radio"]', function() {
                const questionId = $(this).attr('name').replace('answer[', '').replace(']', '');
                const answer = $(this).val();
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

            // First get the test name
            $.ajax({
                type: "POST",
                url: "fetchQuestion.php",
                data: { testId: testId },
                dataType: "json",
                cache: false,
                success: function(data) {
                    if (data && data.g) {
                        $('#testName').html(data.g); // Display test name
                    } else {
                        console.error('Test name not found in response:', data);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Error fetching test name:', textStatus, errorThrown);
                }
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
                    let html = '';
                    questions.forEach((q, idx) => {
                        if (!q.questionId) {
                            console.error('Invalid question data:', q);
                            return;
                        }
                        questionStates[q.questionId] = {
                            status: q.selectedOption ? 'saved' : 'not_answered',
                            answer: q.selectedOption || ''
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
                        html += `
                            <div class="question-block" data-question-id="${q.questionId}">
                                ${stimulusHtml}
                                <div class="question-header-row">
                                    <span class="question-number-pill">Question ${idx + 1}</span>
                                    <span class="question-state-badge ${q.selectedOption ? 'saved' : 'not_answered'}" data-question-state="${q.questionId}">${q.selectedOption ? 'Saved successfully' : 'Not answered'}</span>
                                </div>
                                <div class="question-title">${q.question}</div>
                                <div class="option-container">
                                    <input type="radio" name="answer[${q.questionId}]" value="A" id="q${q.questionId}A" ${q.selectedOption === 'A' ? 'checked' : ''}>
                                    <label class="option-label" for="q${q.questionId}A">${q.optionA}</label>
                                </div>
                                <div class="option-container">
                                    <input type="radio" name="answer[${q.questionId}]" value="B" id="q${q.questionId}B" ${q.selectedOption === 'B' ? 'checked' : ''}>
                                    <label class="option-label" for="q${q.questionId}B">${q.optionB}</label>
                                </div>
                                <div class="option-container">
                                    <input type="radio" name="answer[${q.questionId}]" value="C" id="q${q.questionId}C" ${q.selectedOption === 'C' ? 'checked' : ''}>
                                    <label class="option-label" for="q${q.questionId}C">${q.optionC}</label>
                                </div>
                                <div class="option-container">
                                    <input type="radio" name="answer[${q.questionId}]" value="D" id="q${q.questionId}D" ${q.selectedOption === 'D' ? 'checked' : ''}>
                                    <label class="option-label" for="q${q.questionId}D">${q.optionD}</label>
                                </div>
                            </div>
                        `;
                    });
                    $('#questionsContainer').html(html);
                    onQuestionsLoaded();
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

                function render(seconds) {
                    var hour = Math.floor(seconds/3600);
                    var min = Math.floor(seconds%3600/60);
                    var sec = Math.floor(seconds%3600%60);
                    timerDisplay.innerHTML =
                        (hour = hour < 10 ? "0" + hour : hour) + " : " +
                        (min = min < 10 ? "0" + min : min) + " : " +
                        (sec = sec < 10 ? "0" + sec : sec);
                }

                function autosubmit() {
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
                var syncTick = setInterval(syncOnce, 5000);
                // Immediate first sync to align if reloaded
                syncOnce();
                
                // Track test activity every 10 seconds
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
                // Update tracking every 10 seconds
                setInterval(updateTestTracking, 10000);
                setInterval(function() {
                    refreshConnectionStatus(false);
                }, 15000);
                // Initial tracking update
                updateTestTracking();
            })();

            // Handle form submission
            $('#allQuestionsForm').on('submit', function(e) {
                e.preventDefault();
                const unsavedQuestions = Object.keys(questionStates).filter(function(questionId) {
                    return questionStates[questionId].status === 'pending' || questionStates[questionId].status === 'error';
                });
                if (!serverConnected || unsavedQuestions.length > 0) {
                    $('#submitMessage').html('<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Some answers are not yet confirmed on the server. Please wait for all questions to show as saved before submitting.</div>');
                    return;
                }
                showModal();
            });

            // Modal functions
            function showModal() {
                $('#confirmationModal').fadeIn(300);
            }

            function hideModal() {
                $('#confirmationModal').fadeOut(200);
            }

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
            refreshConnectionStatus(true);
        });
    </script>
    <script>
    // Make confirmSubmitHandler globally accessible for auto-submit
    window.confirmSubmitHandler = function() {
        console.log("Confirm submit clicked!");
        
        // DISABLE ALL SECURITY FEATURES BEFORE SUBMISSION
        if (typeof ExamSecurity !== 'undefined' && typeof ExamSecurity.disableAll === 'function') {
            ExamSecurity.disableAll();
            console.log("✅ Security features disabled after confirmation");
        }
        
        let formData = $('#allQuestionsForm').serialize();
        console.log("Form data to submit:", formData);
        $('#confirmationModal').fadeOut(200);
        
        // Submit and redirect immediately to results page
        $.post('submitAllAnswers.php', formData, function(response) {
            console.log("AJAX response:", response);
            if (response.success) {
                // Redirect IMMEDIATELY to results page (no delay, no warnings)
                console.log("Redirecting to result page...");
                
                // Fallback redirect in case the immediate one is blocked by browser transition
                setTimeout(function() {
                    window.location.href = 'result/index.php';
                }, 200);
                
                window.location.href = 'result/index.php';
            } else {
                // Only show error if submission failed
                if (window.examConnectionHelpers) {
                    window.examConnectionHelpers.markDisconnected('Submission not confirmed');
                }
                $('#submitMessage').html('<div class="alert alert-danger">' + (response.message || "Submission failed. Please try again.") + '</div>');
            }
        }, 'json').fail(function(xhr) {
            console.error("Submission failed:", xhr.responseText);
            if (window.examConnectionHelpers) {
                window.examConnectionHelpers.markDisconnected('Submission not confirmed');
            }
            $('#submitMessage').html('<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> Submission failed because the server could not be reached. Please reconnect and submit again.</div>');
        });
    }
    </script>
    <!-- Bootstrap JS dependencies -->
    <script src="vendor1/bootstrap/js/popper.js"></script>
    <script src="vendor1/bootstrap/js/bootstrap.min.js"></script>
</body>
</html> 
