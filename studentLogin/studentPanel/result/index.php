<?php
session_start();
	if (!isset($_SESSION['studentLoggedIn']))
	{
		header('location:../logout.php');
        exit;
	}
	else
	{
		require_once "../../../db_connection/dlhs_db_connection.php";
		require_once "../answer_grading_helper.php";
	}
$studentId = isset($_SESSION['studentId']) ? (int) $_SESSION['studentId'] : 0;
$testId = isset($_SESSION['idOfTest']) ? (int) $_SESSION['idOfTest'] : (isset($_SESSION['testId']) ? (int) $_SESSION['testId'] : 0);

// Get student name
$query = "SELECT * FROM studentlogin WHERE studentId='$studentId'";
$result = $connection->query($query);
$row = $result ? $result->fetch_array(MYSQLI_NUM) : null;
$firstName = $row ? $row[2] : '';

// Get test info
$query1 = "SELECT * FROM tests WHERE testId='$testId'";
$result1 = $connection->query($query1);
$row1 = $result1 ? $result1->fetch_array(MYSQLI_NUM) : null;
$testName = $row1 ? $row1[2] : '';
$testedTableName = $row1 ? $row1[13] : '';
$answersTableName = $row1 ? $row1[15] : '';
$questionsTableName = $row1 ? $row1[12] : '';

// Get questions array
$query2 = "SELECT * FROM `$testedTableName` WHERE examineeUserId='$studentId' AND testId='$testId'";
$result2 = $testedTableName ? $connection->query($query2) : false;
$row2 = $result2 ? $result2->fetch_array(MYSQLI_NUM) : null;
$questionsArray = array();
if ($row2 && isset($row2[6]) && trim((string) $row2[6]) !== '') {
    $decodedQuestions = @unserialize($row2[6]);
    if (is_array($decodedQuestions)) {
        $questionsArray = $decodedQuestions;
    }
}
$numberOfQuestions = count($questionsArray);

$totalmarksToBeEarned = 0;
$totalMarksEarned = 0;
$totalMarksLost = 0;
$totalNoCorrect = 0;
$totalNoNotCorrect = 0;
$totalQuestionsAnswered = 0;
$totalQuestionsNotAnswered = 0;

for($i = 0; $i < $numberOfQuestions; $i++) {
    $questionId = $questionsArray[$i];
    $query3 = "SELECT * FROM `$questionsTableName` WHERE questionId='$questionId'";
    $result3 = $questionsTableName ? $connection->query($query3) : false;
    $row3 = $result3 ? $result3->fetch_array(MYSQLI_NUM) : null;
    
    if ($row3) {
        $correctOption = dlhsNormalizeOptionValue($row3[7]);
        $mark = $row3[8];
        $totalmarksToBeEarned += $mark;
    } else {
        continue;
    }

    $query4 = "SELECT * FROM `$answersTableName` WHERE userLoginId='$studentId' AND questionId='$questionId'";
    $result4 = $answersTableName ? $connection->query($query4) : false;
    if($result4 && $result4->num_rows > 0) {
        $row4 = $result4->fetch_array(MYSQLI_NUM);
        $selectedOption = dlhsNormalizeOptionValue($row4[4]);
        if($selectedOption == $correctOption) {
            $totalMarksEarned += $mark;
            $totalNoCorrect++;
        } else {
            if($selectedOption == "0" || $selectedOption == "") {
                $totalQuestionsNotAnswered++;
            } else {
                $totalQuestionsAnswered++;
                $totalNoNotCorrect++;
            }
        }
    } else {
        $totalQuestionsNotAnswered++;
    }
}
$totalMarksLost = $totalmarksToBeEarned - $totalMarksEarned;
$percentMarksEarned = $totalmarksToBeEarned > 0 ? ($totalMarksEarned/$totalmarksToBeEarned) * 100 : 0;
// Add encouraging remark based on performance
$remark = "Keep going!";
if ($percentMarksEarned >= 90) {
    $remark = "Outstanding! 🌟 You aced it!";
} elseif ($percentMarksEarned >= 75) {
    $remark = "Great job! 🎉 Keep up the good work!";
} elseif ($percentMarksEarned >= 50) {
    $remark = "Good effort! 👍 Review your mistakes and try again!";
} elseif ($percentMarksEarned > 0) {
    $remark = "Don't give up! Every mistake is a step to success!";
} else {
    $remark = "Let's get started! You can do it!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="author" content="colorlib.com">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>DLHS :: Test Result</title>

    <!-- Font Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <!-- Main css -->
    <link rel="stylesheet" href="css/style.css">
	<script src="code/highcharts.js"></script>
	<script src="code/highcharts-3d.js"></script>
	<script src="code/modules/exporting.js"></script>
	<script src="code/modules/export-data.js"></script>
	<script src="code/modules/accessibility.js"></script>
	<script type = "text/javascript" src = "jQuery3.3.1.js"></script>
	<style>
		.modal1 {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            padding-top: 100px; /* Location of the box */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgb(0,0,0); /* Fallback color */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            }
			
			.modal-content {
            border-radius:7px;
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            width: 60%;
            color:black;
            }

            /* The Close Button */
            .close1{
            color: #ffffff;;
            float: right;
			padding-right:20px;
			padding-top:20px;
            font-size: 28px;
            font-weight: bold;
            }

            .close1:hover, .close1:focus {
            color: red;
            text-decoration: none;
            cursor: pointer;
            }
			
			body{
			overflow-x:hidden;
			overflow-y:auto;
			height:900px;
			}
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ef 100%);
            min-height: 100vh;
            margin: 0;
        }
        .result-header {
            background: #1a237e;
            color: #fff;
            padding: 2rem 0 1rem 0;
            border-radius: 0 0 32px 32px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.08);
        }
        .result-header h2 {
            font-weight: 700;
            letter-spacing: 1px;
        }
        .result-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 2px 16px rgba(26,35,126,0.07);
            padding: 2rem 2.5rem;
            margin-top: -60px;
            margin-bottom: 2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        .result-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .summary-item {
            flex: 1 1 120px;
            background: #f5f7fa;
            border-radius: 12px;
            padding: 1.2rem 1rem;
            text-align: center;
            box-shadow: 0 1px 4px rgba(26,35,126,0.04);
        }
        .summary-item .icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .summary-item .label {
            font-size: 1rem;
            color: #6c757d;
        }
        .summary-item .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a237e;
        }
        .progress {
            height: 1.5rem;
            border-radius: 12px;
            background: #e3e6f0;
            margin-bottom: 1.5rem;
        }
        .progress-bar {
            font-weight: 600;
            font-size: 1rem;
            background: linear-gradient(90deg, #43cea2 0%, #185a9d 100%);
        }
        .chart-container {
            margin: 2rem 0 1rem 0;
        }
        .welcome-msg {
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        .logout-btn {
            float: right;
            color: #fff;
            background: #e53935;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: background 0.2s;
        }
        .logout-btn:hover {
            background: #b71c1c;
        }
        .start-essay-btn {
            background: #1a237e;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.7rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 1.2rem;
            transition: background 0.2s;
        }
        .start-essay-btn:hover {
            background: #3949ab;
        }
        @media (max-width: 600px) {
            .result-card {
                padding: 1rem 0.5rem;
            }
            .result-summary {
                flex-direction: column;
                gap: 0.7rem;
            }
			}
	</style>
</head>
<body>
    <div class="result-header text-center">
        <h2>Test Performance Analysis</h2>
    </div>
    <div class="result-card" style="text-align:center;">
        <?php
            // Show Proceed to Essay if essay exists for this test
            $essayCheck = $connection->query("SELECT question FROM essay_questions WHERE testId='".$connection->real_escape_string($testId)."' LIMIT 1");
            if ($essayCheck && $essayCheck->num_rows > 0) {
                // echo '<div style="margin:10px 0 4px; font-weight:600; color:#0a7f5a;">Essay available for this test.</div>';
                // Removed Proceed to Essay button as requested
            } else {
                echo '<div style="margin:10px 0 4px; font-weight:600; color:#a94442;">No essay uploaded for this test.</div>';
            }
        ?>
    </div>
    <div class="result-card">
        <div class="welcome-msg">
            <span>Hello <b><?php echo $firstName; ?></b>,</span>
            <button class="logout-btn" onclick="window.location.href='../logout.php'" title="Logout"><i class="fa fa-power-off"></i></button>
        </div>
        <div style="font-size:1.1rem; margin-bottom:1.2rem;">
            Test: <b><?php echo $testName; ?></b> <span style="color:#888; font-size:0.95rem;">(<?php echo date('d M, Y'); ?>)</span>
        </div>
        <div class="result-message" style="padding:2rem; text-align:center;">
            <div style="font-size:1.3rem; color:#333; font-weight:600;">Your objective responses have been reordered.</div>
            <div style="margin-top:0.8rem; color:#666;">They will be processed — the detailed summary is not available right now.</div>
        </div>
    </div>
</body>
</html>
