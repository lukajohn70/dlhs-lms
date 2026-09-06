<?php
session_start();

if (!isset($_SESSION['studentId'])) {
    header('location:logout.php');
    exit;
}

require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/essay_timer_helper.php";

$studentId = (int) $_SESSION['studentId'];
$testId = isset($_SESSION['testId']) ? (int) $_SESSION['testId'] : (isset($_SESSION['idOfTest']) ? (int) $_SESSION['idOfTest'] : 0);

$testName = '';
$essayExists = false;
$essaySubmitted = false;
$objectiveStatus = 0;

if ($testId > 0) {
    $testStmt = $connection->prepare("SELECT testName, examineesTableName FROM tests WHERE testId = ? LIMIT 1");
    if ($testStmt) {
        $testStmt->bind_param('i', $testId);
        $testStmt->execute();
        $testResult = $testStmt->get_result();
        $testRow = $testResult ? $testResult->fetch_assoc() : null;
        $testStmt->close();

        if ($testRow) {
            $testName = isset($testRow['testName']) ? $testRow['testName'] : '';
            $essayExists = dlhsTestHasEssay($connection, $testId);
            $essaySubmitted = $essayExists ? dlhsHasSubmittedEssay($connection, $testId, $studentId) : false;

            $examineesTableName = isset($testRow['examineesTableName']) ? $testRow['examineesTableName'] : '';
            if (preg_match('/^[a-zA-Z0-9_]+$/', $examineesTableName)) {
                $statusStmt = $connection->prepare("SELECT testStatus FROM `" . $examineesTableName . "` WHERE testId = ? AND examineeUserId = ? LIMIT 1");
                if ($statusStmt) {
                    $statusStmt->bind_param('ii', $testId, $studentId);
                    $statusStmt->execute();
                    $statusResult = $statusStmt->get_result();
                    $statusRow = $statusResult ? $statusResult->fetch_assoc() : null;
                    $statusStmt->close();
                    if ($statusRow) {
                        $objectiveStatus = isset($statusRow['testStatus']) ? (int) $statusRow['testStatus'] : 0;
                    }
                }
            }
        }
    }
}

if ($essayExists && $essaySubmitted && $objectiveStatus === 0) {
    header('Location: exam.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DLHS :: Next Step</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(135deg, #eef6ff 0%, #f7fbff 100%);
            color: #16324f;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .next-step-card {
            width: 100%;
            max-width: 620px;
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 16px 40px rgba(0, 51, 102, 0.12);
            padding: 36px 32px;
            text-align: center;
        }
        .status-icon {
            width: 74px;
            height: 74px;
            border-radius: 50%;
            margin: 0 auto 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            background: #e8f7ee;
            color: #1d7a46;
        }
        h1 {
            margin: 0 0 12px;
            font-size: 28px;
        }
        p {
            line-height: 1.65;
            margin: 0 0 14px;
        }
        .test-name {
            font-weight: 700;
            color: #003366;
        }
        .button-row {
            margin-top: 28px;
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            border: none;
            border-radius: 999px;
            padding: 14px 28px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .btn-primary {
            background: #003366;
            color: #ffd700;
        }
        .btn-secondary {
            background: #e9eff6;
            color: #23415d;
        }
    </style>
</head>
<body>
    <div class="next-step-card">
        <div class="status-icon">
            <i class="fa fa-check"></i>
        </div>
        <?php if ($essayExists && $essaySubmitted && $objectiveStatus === 0) { ?>
            <h1>Essay Submitted</h1>
            <p>Your essay section for <span class="test-name"><?php echo htmlspecialchars($testName); ?></span> has been submitted successfully.</p>
            <p>The next step is to open the objective section.</p>
            <div class="button-row">
                <a class="btn btn-primary" href="exam.php"><i class="fa fa-arrow-right"></i> Proceed to Objectives</a>
            </div>
        <?php } elseif ($essayExists && !$essaySubmitted) { ?>
            <h1>Essay Required First</h1>
            <p>This test includes an essay section, and the objective section stays locked until the essay is submitted.</p>
            <div class="button-row">
                <a class="btn btn-primary" href="viewEssay.php?testId=<?php echo urlencode((string) $testId); ?>"><i class="fa fa-file-text"></i> Go to Essay</a>
            </div>
        <?php } elseif ($objectiveStatus === 1) { ?>
            <h1>Objective In Progress</h1>
            <p>Your objective section is already in progress for <span class="test-name"><?php echo htmlspecialchars($testName); ?></span>.</p>
            <div class="button-row">
                <a class="btn btn-primary" href="exam.php"><i class="fa fa-play-circle"></i> Continue Objectives</a>
            </div>
        <?php } else { ?>
            <h1>Next Step Ready</h1>
            <p>You can continue with the available section for <span class="test-name"><?php echo htmlspecialchars($testName); ?></span>.</p>
            <div class="button-row">
                <a class="btn btn-secondary" href="index.php"><i class="fa fa-home"></i> Back to Dashboard</a>
            </div>
        <?php } ?>
    </div>
</body>
</html>
