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

// Get all objective questions for this test
$questionsQuery = "SELECT * FROM `$questionsTableName` ORDER BY questionSeriaNo ASC";
$questionsResult = $connection->query($questionsQuery);
$totalQuestions = $questionsResult->num_rows;

// Store questions in an array
$questionsArray = [];
while($qRow = $questionsResult->fetch_assoc()) {
    $questionsArray[] = $qRow;
}

// Get essay question if exists
$essayQuery = "SELECT * FROM essay_questions WHERE testId = ?";
$essayStmt = $connection->prepare($essayQuery);
$essayStmt->bind_param("i", $testId);
$essayStmt->execute();
$essayResult = $essayStmt->get_result();
$essayQuestion = "";
$hasEssay = false;
if($essayResult->num_rows > 0) {
    $essayRow = $essayResult->fetch_array(MYSQLI_NUM);
    $essayQuestion = $essayRow[3]; // question column
    $hasEssay = true;
}

// Get staff/admin name for display
$staffName = "";
if(isset($_SESSION['staffName'])) {
    $staffName = $_SESSION['staffName'];
} elseif(isset($_SESSION['adminName'])) {
    $staffName = $_SESSION['adminName'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Set Questions: <?php echo htmlspecialchars($testName); ?> - DLHS</title>
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    
    <link href="https://fonts.googleapis.com/css?family=Poppins:400,500,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
                padding: 20px;
            }
            .container {
                max-width: 100%;
            }
        }
        
        body {
            background: #fff;
            font-family: 'Poppins', sans-serif;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
        }
        
        .header-section {
            border: 2px solid #003366;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #003366 0%, #004080 100%);
            color: #fff;
        }
        
        .header-section h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            color: #FFD700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .header-section .info-row {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            font-size: 14px;
        }
        
        .header-section .info-row strong {
            color: #FFD700;
        }
        
        .objective-section {
            margin-bottom: 40px;
        }
        
        .section-title {
            background: #003366;
            color: #FFD700;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .question-block {
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #003366;
            border-radius: 10px;
            background: #fff;
            page-break-inside: avoid;
        }
        
        .question-number {
            color: #003366;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .question-text {
            font-size: 15px;
            color: #333;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .options-container {
            margin-left: 20px;
        }
        
        .option-item {
            margin-bottom: 10px;
            padding: 8px 15px;
            border-radius: 5px;
            background: #f8f9fa;
            border-left: 4px solid #003366;
            font-size: 14px;
        }
        
        .option-letter {
            font-weight: 600;
            color: #003366;
            margin-right: 8px;
        }
        
        .essay-section {
            margin-bottom: 40px;
        }
        
        .essay-question {
            padding: 25px;
            border: 2px solid #e91e8c;
            border-radius: 10px;
            background: #fff;
            page-break-inside: avoid;
        }
        
        .essay-question-title {
            color: #e91e8c;
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .essay-text {
            font-size: 15px;
            color: #333;
            line-height: 1.8;
        }
        
        .btn-print {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.5);
        }
        
        .btn-print i {
            margin-right: 8px;
        }
        
        @page {
            margin: 1cm;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header-section">
            <h1><?php echo htmlspecialchars($testName); ?></h1>
            <div class="info-row">
                <div>
                    <strong>Subject:</strong> <?php echo htmlspecialchars($subjectName); ?>
                </div>
                <div>
                    <strong>Duration:</strong> <?php echo $duration; ?> minutes
                </div>
            </div>
            <div class="info-row" style="margin-top: 10px;">
                <div>
                    <strong>Total Objective Questions:</strong> <?php echo $totalQuestions; ?>
                </div>
                <div>
                    <strong>Essay Questions:</strong> <?php echo $hasEssay ? '1' : '0'; ?>
                </div>
            </div>
        </div>
        
        <!-- Objective Questions Section -->
        <?php if($totalQuestions > 0): ?>
        <div class="objective-section">
            <div class="section-title">
                <i class="fa fa-list-ol"></i> Objective Questions (<?php echo $totalQuestions; ?> Questions)
            </div>
            
            <?php foreach($questionsArray as $index => $q): ?>
            <div class="question-block">
                <div class="question-number">Question <?php echo ($index + 1); ?></div>
                <div class="question-text"><?php echo htmlspecialchars_decode($q['question']); ?></div>
                
                <div class="options-container">
                    <?php if(!empty($q['optionA'])): ?>
                    <div class="option-item">
                        <span class="option-letter">A.</span>
                        <?php echo htmlspecialchars_decode($q['optionA']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($q['optionB'])): ?>
                    <div class="option-item">
                        <span class="option-letter">B.</span>
                        <?php echo htmlspecialchars_decode($q['optionB']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($q['optionC'])): ?>
                    <div class="option-item">
                        <span class="option-letter">C.</span>
                        <?php echo htmlspecialchars_decode($q['optionC']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($q['optionD'])): ?>
                    <div class="option-item">
                        <span class="option-letter">D.</span>
                        <?php echo htmlspecialchars_decode($q['optionD']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($q['optionE'])): ?>
                    <div class="option-item">
                        <span class="option-letter">E.</span>
                        <?php echo htmlspecialchars_decode($q['optionE']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Essay Section -->
        <?php if($hasEssay): ?>
        <div class="essay-section">
            <div class="section-title" style="background: linear-gradient(135deg, #e91e8c 0%, #f02d95 100%);">
                <i class="fa fa-file-text-o"></i> Essay Question
            </div>
            
            <div class="essay-question">
                <div class="essay-question-title">Essay Question</div>
                <div class="essay-text"><?php echo htmlspecialchars_decode($essayQuestion); ?></div>
            </div>
            
            <!-- Answer space for essay -->
            <div style="margin-top: 20px; border: 2px dashed #ddd; border-radius: 10px; padding: 20px; min-height: 400px;">
                <div style="color: #666; font-size: 14px; margin-bottom: 10px;">
                    <i class="fa fa-pencil"></i> Answer Space (Student will write here):
                </div>
                <div style="line-height: 2; color: #999; font-style: italic;">
                    <!-- Blank space for students to write -->
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Footer Note -->
        <div style="margin-top: 40px; padding: 15px; border-top: 2px solid #ddd; color: #666; font-size: 12px; text-align: center;">
            <p style="margin: 0;">This is a printable version of the test questions. You can print or save as PDF using your browser's print function.</p>
            <p style="margin: 5px 0 0 0;">Generated by: <?php echo htmlspecialchars($staffName); ?> | DLHS</p>
        </div>
    </div>
    
    <!-- Print Button -->
    <button class="btn-print no-print" onclick="window.print()">
        <i class="fa fa-print"></i> Print / Save as PDF
    </button>
    
    <script>
        // Auto trigger print dialog on load (optional - uncomment to enable)
        // window.onload = function() {
        //     window.print();
        // };
    </script>
</body>
</html>


