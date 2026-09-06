<?php
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn']))
{
    header('location:../index.php');
    exit();
}

include "../../db_connection/dlhs_db_connection.php";

// Get test ID from URL
$testId = isset($_GET['testId']) ? intval($_GET['testId']) : 0;

if($testId == 0) {
    die("Invalid test ID");
}

// Get test details
$query = "SELECT * FROM tests WHERE testId = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param("i", $testId);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    die("Test not found");
}

$row = $result->fetch_array(MYSQLI_NUM);
if(!$row) {
    die("Test not found");
}

$testName = $row[2];
$duration = $row[4];
$questionsTableName = $row[13];
$subjectId = $row[8];

// Get subject name
$subjectQuery = "SELECT * FROM subjects WHERE subjectId = ?";
$subjectStmt = $connection->prepare($subjectQuery);
$subjectStmt->bind_param("i", $subjectId);
$subjectStmt->execute();
$subjectResult = $subjectStmt->get_result();
$subjectName = "";
if($subjectResult->num_rows > 0) {
    $subjectRow = $subjectResult->fetch_array(MYSQLI_NUM);
    $subjectName = $subjectRow[1];
}

// Get all questions for this test
$questionsQuery = "SELECT * FROM `$questionsTableName` ORDER BY questionSeriaNo ASC";
$questionsResult = $connection->query($questionsQuery);
$totalQuestions = $questionsResult->num_rows;

if($totalQuestions == 0) {
    die("No questions found for this test");
}

// Store questions in an array
$questionsArray = [];
while($qRow = $questionsResult->fetch_assoc()) {
    $questionsArray[] = $qRow;
}

// Get staff/admin name for display
$staffName = "";
if(isset($_SESSION['staffName'])) {
    $staffName = $_SESSION['staffName'];
} elseif(isset($_SESSION['adminName'])) {
    $staffName = $_SESSION['adminName'];
}

// Fetch Shared Stimulus Map
require_once '../../scripts/question_authoring_helper.php';
$questionIdsForMap = array_column($questionsArray, 'questionId');
$stimulusMap = dlhsFetchSharedStimulusMapForQuestions($connection, $testId, $questionIdsForMap);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: <?php echo htmlspecialchars($testName); ?> - DLHS</title>
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <script src="../../libs/jquery.min.js"></script>
    <style>
        body {
            background: #f5f8fa;
            font-family: 'Poppins', sans-serif;
            padding-top: 100px;
        }
        
        /* Preview Badge */
        .preview-badge {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #e91e8c 0%, #f02d95 100%);
            color: #fff;
            padding: 0.6rem 1rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
            z-index: 1001;
            box-shadow: 0 2px 10px rgba(233,30,140,0.3);
        }
        
        .preview-badge i {
            margin-right: 8px;
        }
        
        /* Exam Header */
        .exam-header {
            background: #003366;
            padding: 15px 40px;
            color: #FFD700;
            text-align: center;
            position: fixed;
            top: 42px;
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
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            padding-bottom: 80px;
        }
        
        .card {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            box-shadow: 0 4px 15px rgba(0, 51, 102, 0.1);
            border-radius: 15px;
            border: none;
        }
        
        /* Question Blocks */
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
        
        .question-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            color: #003366;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px dashed rgba(0, 51, 102, 0.2);
            font-weight: 500;
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
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease !important;
            background: #e91e8c !important;
            border: none !important;
            box-shadow: 0 4px 15px rgba(233,30,140,0.3);
            color: #fff !important;
        }
        
        .btn-pill:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(233,30,140,0.4);
            background: #f02d95 !important;
        }
        
        .btn-pill i {
            margin-right: 8px;
        }
        
        /* Progress Counter */
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
        }
        
        .progress-counter span {
            font-weight: 600;
            font-size: 18px;
            color: #FFD700;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .exam-info {
                flex-direction: column;
                gap: 1rem;
            }
            
            body {
                padding-top: 140px;
            }
        }
    </style>
</head>
<body>
    <!-- Preview Badge -->
    <div class="preview-badge">
        <i class="fa fa-eye"></i> PREVIEW MODE - This is exactly what students see when taking this test
    </div>
    
    <!-- Exam Header -->
    <div class="exam-header">
        <div class="exam-info">
            <div class="student-info">
                <div class="test-name"><?php echo htmlspecialchars($testName); ?></div>
                <div class="student-name">Welcome, <strong><?php echo htmlspecialchars($staffName); ?></strong> (Teacher Preview)</div>
            </div>
            <div class="timer"><?php echo $duration; ?>:00</div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container" style="margin-top: 40px;">
        <div class="card">
            <div class="card-body" style="padding: 20px;">
                
                <!-- All Questions Display -->
                <?php foreach($questionsArray as $index => $q): 
                    $stimulusMeta = isset($stimulusMap[(int) $q['questionId']]) ? $stimulusMap[(int) $q['questionId']] : null;
                ?>
                <div class="question-block" data-question-id="<?php echo $q['questionId']; ?>">
                    <?php if ($stimulusMeta): ?>
                    <div class="question-stimulus-card" style="margin-bottom: 18px; padding: 18px 20px; border-radius: 14px; background: linear-gradient(180deg, #f7fbff 0%, #eef6ff 100%); border: 1px solid rgba(0, 51, 102, 0.12);">
                        <div class="question-stimulus-meta" style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin-bottom: 12px;">
                            <span class="question-stimulus-badge" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: rgba(0, 51, 102, 0.1); color: #003366; font-size: 12px; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase;"><i class="fa fa-book"></i><?php echo htmlspecialchars($stimulusMeta['stimulusType'] ?: 'SHARED'); ?></span>
                            <span class="question-stimulus-title" style="font-size: 15px; font-weight: 700; color: #003366;"><?php echo htmlspecialchars($stimulusMeta['stimulusTitle'] ?: 'Shared material'); ?></span>
                        </div>
                        <div class="question-stimulus-content" style="color: #31465f; line-height: 1.65;"><?php echo $stimulusMeta['stimulusContent']; ?></div>
                    </div>
                    <?php endif; ?>

                    <div class="question-title">Q<?php echo ($index + 1); ?>: <?php echo htmlspecialchars_decode($q['question']); ?></div>
                    
                    <?php if(!empty($q['optionA'])): ?>
                    <div class="option-container">
                        <input type="radio" name="answer[<?php echo $q['questionId']; ?>]" value="A" id="q<?php echo $q['questionId']; ?>A">
                        <label class="option-label" for="q<?php echo $q['questionId']; ?>A"><?php echo htmlspecialchars_decode($q['optionA']); ?></label>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($q['optionB'])): ?>
                    <div class="option-container">
                        <input type="radio" name="answer[<?php echo $q['questionId']; ?>]" value="B" id="q<?php echo $q['questionId']; ?>B">
                        <label class="option-label" for="q<?php echo $q['questionId']; ?>B"><?php echo htmlspecialchars_decode($q['optionB']); ?></label>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($q['optionC'])): ?>
                    <div class="option-container">
                        <input type="radio" name="answer[<?php echo $q['questionId']; ?>]" value="C" id="q<?php echo $q['questionId']; ?>C">
                        <label class="option-label" for="q<?php echo $q['questionId']; ?>C"><?php echo htmlspecialchars_decode($q['optionC']); ?></label>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($q['optionD'])): ?>
                    <div class="option-container">
                        <input type="radio" name="answer[<?php echo $q['questionId']; ?>]" value="D" id="q<?php echo $q['questionId']; ?>D">
                        <label class="option-label" for="q<?php echo $q['questionId']; ?>D"><?php echo htmlspecialchars_decode($q['optionD']); ?></label>
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($q['optionE'])): ?>
                    <div class="option-container">
                        <input type="radio" name="answer[<?php echo $q['questionId']; ?>]" value="E" id="q<?php echo $q['questionId']; ?>E">
                        <label class="option-label" for="q<?php echo $q['questionId']; ?>E"><?php echo htmlspecialchars_decode($q['optionE']); ?></label>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <!-- Close Preview Button -->
                <div class="text-center mt-4">
                    <button type="button" class="btn btn-lg btn-pill" onclick="window.close()">
                        <i class="fa fa-times"></i> Close Preview
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Counter (Total Questions) -->
    <div class="progress-counter-wrapper">
        <div class="progress-counter">
            Total Questions: <span><?php echo $totalQuestions; ?></span>
        </div>
    </div>
</body>
</html>

