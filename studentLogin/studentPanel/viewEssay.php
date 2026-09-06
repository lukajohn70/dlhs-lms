<?php
session_start();

if (!isset($_SESSION['studentLoggedIn'])) {
    header('location:logout.php');
    exit;
}

require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/essay_timer_helper.php";

$testId = 0;
if (isset($_GET['testId']) && $_GET['testId'] !== '') {
    $testId = (int) $_GET['testId'];
} elseif (isset($_SESSION['testId'])) {
    $testId = (int) $_SESSION['testId'];
} elseif (isset($_SESSION['idOfTest'])) {
    $testId = (int) $_SESSION['idOfTest'];
}

$studentId = isset($_SESSION['studentId']) ? (int) $_SESSION['studentId'] : 0;
$studentName = isset($_SESSION['studentName']) ? $_SESSION['studentName'] : '';
$testName = '';
$subjectName = '';
$remainingSeconds = 0;
$essayMinutes = 0;

if ($testId > 0) {
    $_SESSION['testId'] = $testId;
    $_SESSION['idOfTest'] = $testId;
}

if ($testId > 0 && $studentId > 0) {
    $testStmt = $connection->prepare("SELECT * FROM tests WHERE testId = ? LIMIT 1");
    if ($testStmt) {
        $testStmt->bind_param('i', $testId);
        $testStmt->execute();
        $testResult = $testStmt->get_result();
        $testRow = $testResult ? $testResult->fetch_assoc() : null;
        $testStmt->close();
    } else {
        $testRow = null;
    }

    if (!empty($testRow)) {
        $testName = isset($testRow['testName']) ? $testRow['testName'] : '';
        $essayMinutes = isset($testRow['essayTime']) ? (int) $testRow['essayTime'] : 0;
        if ($essayMinutes <= 0) {
            $essayMinutes = isset($testRow['duration']) ? (int) $testRow['duration'] : 0;
        }

        $subjectId = isset($testRow['subject']) ? (int) $testRow['subject'] : 0;
        if ($subjectId > 0) {
            $subjectStmt = $connection->prepare("SELECT subjectName FROM subjects WHERE subjectId = ? LIMIT 1");
            if ($subjectStmt) {
                $subjectStmt->bind_param('i', $subjectId);
                $subjectStmt->execute();
                $subjectResult = $subjectStmt->get_result();
                if ($subjectResult && $subjectInfo = $subjectResult->fetch_assoc()) {
                    $subjectName = $subjectInfo['subjectName'];
                }
                $subjectStmt->close();
            }
        }

        $examineesTable = isset($testRow['examineesTableName']) ? $testRow['examineesTableName'] : '';
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $examineesTable)) {
            echo "<div style='padding:30px; font-family:Arial,Helvetica,sans-serif;'><h2>Error</h2><p>This test is not configured correctly.</p><p><a href=\"testsInProgress.php\">Back to Tests</a></p></div>";
            exit;
        }

        $authSql = "SELECT 1 FROM `" . $examineesTable . "` WHERE examineeUserId = ? LIMIT 1";
        $authStmt = $connection->prepare($authSql);
        if (!$authStmt) {
            echo "<div style='padding:30px; font-family:Arial,Helvetica,sans-serif;'><h2>Error</h2><p>Unable to verify access. Please try again later.</p></div>";
            exit;
        }

        $authStmt->bind_param('i', $studentId);
        $authStmt->execute();
        $authStmt->store_result();
        if ($authStmt->num_rows === 0) {
            $authStmt->close();
            echo "<div style='padding:30px; font-family:Arial,Helvetica,sans-serif;'><h2>Access denied</h2><p>You are not authorized to view this test's essay.</p><p><a href=\"testsInProgress.php\">Back to Tests</a></p></div>";
            exit;
        }
        $authStmt->close();

        if ($essayMinutes > 0) {
            $essayAttempt = dlhsGetOrCreateEssayAttempt($connection, $testId, $studentId, $essayMinutes);
            if ($essayAttempt) {
                $remainingSeconds = dlhsGetEssayRemainingSeconds($essayAttempt);
            }
        }
    }
}

if ($testId > 0) {
    if (!isset($_SESSION['essay_viewed'])) {
        $_SESSION['essay_viewed'] = array();
    }
    $_SESSION['essay_viewed'][$testId] = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Essay Section | DLHS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="jQuery3.3.1.js"></script>
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: linear-gradient(135deg, #e3f5ff 0%, #d6f0ff 100%);
            min-height: 100vh;
            margin: 0;
        }
        .essay-header {
            background: linear-gradient(135deg, #0095d9 0%, #00a8e8 100%);
            color: #fff;
            padding: 2rem 0 1rem 0;
            border-radius: 0 0 32px 32px;
            box-shadow: 0 4px 24px rgba(0,168,232,0.3);
        }
        .essay-header h2 {
            font-weight: 700;
            letter-spacing: 1px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .timer-box {
            display: inline-block;
            background: #fff;
            color: #0095d9;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .essay-floating-timer {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 1200;
            background: rgba(255,255,255,0.98);
            color: #0095d9;
            border-radius: 999px;
            padding: 12px 18px;
            font-weight: 700;
            box-shadow: 0 10px 24px rgba(0, 149, 217, 0.18);
            border: 1px solid rgba(0, 149, 217, 0.12);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .essay-floating-timer .timer-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #5f7d8b;
        }
        .essay-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 20px rgba(0,168,232,0.15);
            padding: 2rem 2.5rem;
            margin-top: -60px;
            margin-bottom: 2rem;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
            border-top: 3px solid #e91e8c;
        }
        .question-box {
            background: linear-gradient(to bottom, #f0f9ff 0%, #fafcff 100%);
            border: 2px solid #bae6fd;
            border-radius: 12px;
            padding: 2rem;
            min-height: 200px;
            margin: 1.5rem 0;
            font-size: 1.1rem;
            line-height: 1.8;
        }
        .question-box img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 1rem auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .submit-btn {
            background: linear-gradient(135deg, #e91e8c 0%, #f02d95 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.8rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 200px;
            box-shadow: 0 4px 12px rgba(233,30,140,0.3);
        }
        .submit-btn:hover {
            background: linear-gradient(135deg, #f02d95 0%, #e91e8c 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(233,30,140,0.4);
        }
        .submit-btn:disabled {
            background: #9e9e9e;
            cursor: not-allowed;
            transform: none;
        }
        .logout-btn {
            float: right;
            color: #fff;
            background: #e74c3c;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.2s;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(231,76,60,0.3);
        }
        .logout-btn:hover {
            background: #c0392b;
            transform: scale(1.05);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .test-info {
            color: #6c757d;
            font-size: 0.95rem;
        }
        h5 {
            color: #0095d9 !important;
        }
        @media (max-width: 767px) {
            .essay-floating-timer {
                top: 12px;
                right: 12px;
                left: 12px;
                justify-content: center;
                border-radius: 16px;
            }
            .essay-header {
                padding-top: 5rem;
            }
        }
    </style>
</head>
<body>
    <div class="essay-floating-timer">
        <span class="timer-label"><i class="fa fa-clock"></i> Time left</span>
        <span id="floatingHms">00:00:00</span>
    </div>
    <div class="essay-header text-center">
        <h2>Essay Section</h2>
        <div style="margin-top: 1rem;">
            <span class="timer-box">
                <i class="fa fa-clock"></i> <span id="hms">00:00:00</span>
            </span>
        </div>
    </div>
    
    <div class="essay-card">
        <div class="info-row">
            <div>
                <span style="font-size: 1.1rem;">Hello <b><?php echo htmlspecialchars($studentName ?: (isset($_SESSION['studentName']) ? $_SESSION['studentName'] : '')); ?></b></span>
            </div>
            <button class="logout-btn" onclick="window.location.href='logout.php'" title="Logout">
                <i class="fa fa-power-off"></i>
            </button>
        </div>
        
        <div class="test-info" style="margin-bottom: 1.5rem;">
            <strong>Test:</strong> <?php echo htmlspecialchars($testName ?: '-'); ?>
            <?php if($subjectName): ?>
            | <strong>Subject:</strong> <?php echo htmlspecialchars($subjectName); ?>
            <?php endif; ?>
            <span style="color:#888; font-size:0.9rem;">(<?php echo date('d M, Y'); ?>)</span>
        </div>
        
        <h5 style="color: #1a237e; margin-bottom: 1rem;">
            <i class="fa fa-file-text"></i> Essay Question
        </h5>
        
        <div id="theQuestion" class="question-box">
            <center><i class="fa fa-spinner fa-spin" style="font-size: 2rem; color: #1a237e;"></i></center>
        </div>
        
        <div id="msg" style="margin: 1.5rem 0; font-weight: 600; text-align: center;"></div>
        
        <div class="text-center">
            <button type="button" class="submit-btn" id="submitEssayBtn">
                <i class="fa fa-paper-plane"></i> Submit Essay
            </button>
        </div>
    </div>

<script>
    var remaining = <?php echo (int) $remainingSeconds; ?>;
    var testId = <?php echo (int) $testId; ?>;
    var autoSubmitted = false;
    var manualSubmitted = false;
    
    function formatTime(totalSeconds) {
        var hours = Math.floor(totalSeconds / 3600);
        var minutes = Math.floor((totalSeconds % 3600) / 60);
        var seconds = totalSeconds % 60;
        var h = (hours < 10) ? "0" + hours : hours;
        var m = (minutes < 10) ? "0" + minutes : minutes;
        var s = (seconds < 10) ? "0" + seconds : seconds;
        return h + ":" + m + ":" + s;
    }
    
    function autoSubmitEssay() {
        if (autoSubmitted || manualSubmitted) return;
        autoSubmitted = true;
        
        $('#submitEssayBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Auto-submitting...');
        $('#msg').html('<span style="color:orange;"><i class="fa fa-info-circle"></i> Time is up! Auto-submitting essay section...</span>');
        
        $.ajax({
            url: 'submitEssay.php',
            type: 'POST',
            data: {
                testId: testId,
                essayAnswer: 'Essay completed (Time Expired)',
                autoSubmit: 1
            },
            dataType: 'JSON',
            success: function(response){
                if(response.success){
                    $('#msg').html('<span style="color:green;"><i class="fa fa-check-circle"></i> Essay auto-submitted! Unlocking objectives...</span>');
                    $('#submitEssayBtn').html('<i class="fa fa-check"></i> Submitted');
                    setTimeout(function(){
                        window.location.href = 'postSubmit.php';
                    }, 2000);
                } else {
                    $('#msg').html('<span style="color:red;"><i class="fa fa-exclamation-circle"></i> Auto-submit failed: ' + response.message + '</span>');
                    setTimeout(function(){
                        window.location.href = 'postSubmit.php';
                    }, 3000);
                }
            },
            error: function(){
                $('#msg').html('<span style="color:red;"><i class="fa fa-exclamation-circle"></i> Network error during auto-submit. Redirecting...</span>');
                setTimeout(function(){
                    window.location.href = 'postSubmit.php';
                }, 3000);
            }
        });
    }
    
    function tick() {
        var formatted = formatTime(remaining);
        document.getElementById('hms').textContent = formatted;
        document.getElementById('floatingHms').textContent = formatted;
        
        if (remaining <= 0) {
            autoSubmitEssay();
            return;
        }

        remaining -= 1;
        setTimeout(tick, 1000);
    }

    function loadEssay() {
        $.ajax({
            url: 'getEssayQuestion.php',
            type: 'POST',
            data: {testId : testId},
            dataType: 'JSON',
            success: function(response){
                try {
                    if(response && response.length > 0){
                        $("#theQuestion").html(response[0].question);
                    } else {
                        $("#theQuestion").html("<center><font style='font-size:16px; color: red;'>No essay uploaded for this test!</font></center>");
                        $('#submitEssayBtn').prop('disabled', true);
                    }
                } catch(e) {
                    $("#theQuestion").html("<center><font style='font-size:16px; color: red;'>Error parsing response.</font></center>");
                    $('#submitEssayBtn').prop('disabled', true);
                }
            },
            error: function(xhr, status, err) {
                $("#theQuestion").html("<center><font style='font-size:16px; color: red;'>Could not load essay question. ("+status+")</font></center>");
                $('#submitEssayBtn').prop('disabled', true);
                console.error('getEssayQuestion error', status, err, xhr.responseText);
            }
        });
        
        $.ajax({
            url: 'getEssayAnswer.php',
            type: 'POST',
            data: {testId : testId},
            dataType: 'JSON',
            success: function(response){
                if(response && response.hasAnswer){
                    autoSubmitted = true;
                    $('#msg').html('<span style="color:green;"><i class="fa fa-check-circle"></i> You have already submitted this essay.</span>');
                    $('#submitEssayBtn').html('<i class="fa fa-check"></i> Submitted').prop('disabled', true);
                }
            }
        });
    }

    $(function(){
        tick();
        loadEssay();
        
        $('#submitEssayBtn').on('click', function(){
            if (manualSubmitted || autoSubmitted) return;
            
            if(remaining <= 0){
                $('#msg').html('<span style="color:red;">Time is up. Your essay can no longer be submitted.</span>');
                return;
            }
            
            if(!confirm('Are you sure you want to submit? This will mark your essay section as complete.')){
                return;
            }
            
            manualSubmitted = true;
            $('#submitEssayBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Submitting...');
            $('#msg').html('<span style="color:#009999;">Submitting...</span>');
            
            $.ajax({
                url: 'submitEssay.php',
                type: 'POST',
                data: {
                    testId: testId,
                    essayAnswer: 'Essay completed'
                },
                dataType: 'JSON',
                success: function(response){
                    if(response.success){
                        $('#msg').html('<span style="color:green;"><i class="fa fa-check-circle"></i> Essay submitted successfully!</span>');
                        $('#submitEssayBtn').html('<i class="fa fa-check"></i> Submitted');
                        setTimeout(function(){
                            window.location.href = 'postSubmit.php';
                        }, 2000);
                    } else {
                        manualSubmitted = false;
                        $('#msg').html('<span style="color:red;"><i class="fa fa-exclamation-circle"></i> ' + response.message + '</span>');
                        $('#submitEssayBtn').prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Submit Essay');
                    }
                },
                error: function(){
                    manualSubmitted = false;
                    $('#msg').html('<span style="color:red;"><i class="fa fa-exclamation-circle"></i> Error submitting. Please try again.</span>');
                    $('#submitEssayBtn').prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Submit Essay');
                }
            });
        });
    });
</script>
</body>
</html>
