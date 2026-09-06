<?php
session_start();
require_once 'sessionTime.php';

if ((time() - $_SESSION['staffLast_login']) > $allottedTime) {
    require_once 'unsetSessions.php';
    echo 0;
} else {
    include "../../db_connection/dlhs_db_connection.php";
    
    if (isset($_POST['testId']) && isset($_POST['studentId'])) {
        $testId = mysqli_real_escape_string($connection, $_POST['testId']);
        $studentId = mysqli_real_escape_string($connection, $_POST['studentId']);
        $staffId = $_SESSION['staffId'];
        
        // Verify that the test belongs to the current staff member
        $query = "SELECT * FROM tests WHERE testId='$testId' AND staffId='$staffId'";
        $result = $connection->query($query);
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_array(MYSQLI_NUM);
            $examineesTableName = $row[14];
            $answersTableName = $row[16];
            $testDuration = $row[4];
            
            // Check if student exists in the examinees table
            $checkStudent = "SELECT * FROM $examineesTableName WHERE examineeUserId='$studentId' AND testId='$testId'";
            $studentResult = $connection->query($checkStudent);
            
            if ($studentResult->num_rows > 0) {
                $studentRow = $studentResult->fetch_array(MYSQLI_NUM);
                $currentStatus = $studentRow[5];
                
                // Allow retake if student has completed the test (status = 2) or run out of time (status = 1 with remaining time <= 0)
                $remainingTime = $studentRow[7];
                $questionsArray = unserialize($studentRow[6]);
                $totalQuestions = 0;
                $questionsAnswered = 0;
                
                if ($questionsArray !== false && is_array($questionsArray)) {
                    $totalQuestions = count($questionsArray);
                    
                    // Count how many questions the student has answered
                    foreach($questionsArray as $questionId) {
                        $checkAnswer = "SELECT * FROM $answersTableName WHERE userLoginId='$studentId' AND questionId='$questionId' AND selectedOption != '0'";
                        $answerResult = $connection->query($checkAnswer);
                        if($answerResult->num_rows > 0) {
                            $questionsAnswered++;
                        }
                    }
                }
                
                if (($currentStatus == 2 || ($currentStatus == 1 && $remainingTime <= 0)) && $questionsAnswered < $totalQuestions) {
                    // Calculate additional time: 2 minutes per unanswered question
                    $unansweredQuestions = $totalQuestions - $questionsAnswered;
                    $additionalTimeMinutes = $unansweredQuestions * 2;
                    $additionalTimeSeconds = $additionalTimeMinutes * 60;
                    $totalTimeSeconds = ($testDuration * 60) + $additionalTimeSeconds;
                    
                    // Reset student's test status to "Yet to start" (0) but preserve their answers
                    $resetStatus = "UPDATE $examineesTableName SET 
                                    testStatus = 0, 
                                    questArray = '', 
                                    remainingTime = " . $totalTimeSeconds . ", 
                                    totalToBeEarned = 0, 
                                    totalEarned = 0, 
                                    noOfQuestions = 0, 
                                    noCorrect = 0, 
                                    timeStartedTest = '', 
                                    timeSubmittedTest = '' 
                                    WHERE examineeUserId='$studentId' AND testId='$testId'";
                    
                    if ($connection->query($resetStatus)) {
                        // Note: We do NOT delete previous answers - they are preserved
                        // The student can now retake the test and their previous answers will remain
                        // If they answer the same questions again, it will update their answers
                        // If they answer new questions, those will be added
                        
                        // Return success with additional time information
                        echo json_encode(array(
                            'success' => 1,
                            'unansweredQuestions' => $unansweredQuestions,
                            'additionalTimeMinutes' => $additionalTimeMinutes,
                            'totalTimeMinutes' => round($totalTimeSeconds / 60, 2)
                        ));
                    } else {
                        echo 2; // Database error
                    }
                } else {
                    echo 3; // Student has answered all questions or hasn't completed the test yet
                }
            } else {
                echo 4; // Student not found in test
            }
        } else {
            echo 5; // Test not found or not authorized
        }
    } else {
        echo 6; // Missing parameters
    }
}
?>
