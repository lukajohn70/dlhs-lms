<?php
/**
 * Auto-Sync Performance Data from Test Results - VERSION 2
 * Updated to work with YOUR specific table structure
 */

session_start();
require_once "userExpiredSession.php";
require_once "../../db_connection/dlhs_db_connection.php";

if (!isset($_SESSION['staffLoggedIn']) || $_SESSION['staffLoggedIn'] !== "yes") {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$staffId = $_SESSION['staffId'];

// Function to sync from your dynamic test tables
function syncFromDynamicTables($connection, $staffId) {
    $recordsSynced = 0;
    
    // Get all completed tests (status = 2) with correct column names
    $testsQuery = "SELECT 
                    testId, 
                    testName, 
                    staffId, 
                    subject as subjectId, 
                    testDate,
                    tableName as questionsTable,
                    examineesTableName as testedTable,
                    answersTable
                  FROM tests 
                  WHERE staffId = $staffId 
                  AND status = 2
                  ORDER BY testDate DESC";
    
    $testsResult = $connection->query($testsQuery);
    
    if (!$testsResult || $testsResult->num_rows == 0) {
        return 0; // No completed tests found
    }
    
    while ($test = $testsResult->fetch_assoc()) {
        $testId = $test['testId'];
        $subjectId = $test['subjectId'];
        $testDate = $test['testDate'];
        $testName = $test['testName'];
        $questionsTable = $test['questionsTable'];
        $testedTable = $test['testedTable'];
        $answersTable = $test['answersTable'];
        
        // Check if tables exist
        if (empty($testedTable) || empty($questionsTable) || empty($answersTable)) {
            continue; // Skip if table names are empty
        }
        
        $checkTable = $connection->query("SHOW TABLES LIKE '$testedTable'");
        if (!$checkTable || $checkTable->num_rows == 0) {
            continue; // Table doesn't exist
        }
        
        // Get all students who took this test
        $studentsQuery = "SELECT DISTINCT examineeUserId FROM `$testedTable`";
        $studentsResult = $connection->query($studentsQuery);
        
        if (!$studentsResult || $studentsResult->num_rows == 0) {
            continue; // No students took this test
        }
        
        while ($student = $studentsResult->fetch_assoc()) {
            $examineeUserId = $student['examineeUserId'];
            
            // Get student ID and verify student still exists in database
            $studentId = null;
            
            if (is_numeric($examineeUserId)) {
                $studentId = intval($examineeUserId);
                
                // CRITICAL: Check if student still exists in studentlogin table
                // This prevents sync errors when students are deleted or moved
                $checkStudent = $connection->query("SELECT studentId FROM studentlogin WHERE studentId = $studentId");
                
                if (!$checkStudent || $checkStudent->num_rows == 0) {
                    // Student was deleted or moved - skip this record but continue syncing others
                    // Log this for reference
                    error_log("Performance Sync: Skipping deleted/moved student ID $studentId from test $testId");
                    continue;
                }
            } else {
                // Skip non-numeric IDs
                continue;
            }
            
            // Check if already synced
            $checkSync = $connection->query(
                "SELECT historyId FROM student_performance_history 
                 WHERE studentId = $studentId 
                 AND assessmentId = $testId 
                 AND assessmentType = 'test'"
            );
            
            if ($checkSync && $checkSync->num_rows > 0) {
                continue; // Already synced
            }
            
            // Get student's questions array (column 6 contains serialized questions array)
            $testDataQuery = "SELECT * FROM `$testedTable` 
                             WHERE examineeUserId = '$examineeUserId'";
            $testDataResult = $connection->query($testDataQuery);
            
            if (!$testDataResult || $testDataResult->num_rows == 0) {
                continue;
            }
            
            $testData = $testDataResult->fetch_array(MYSQLI_NUM);
            $questionsArray = @unserialize($testData[6]); // Column 6 contains the serialized questions array
            
            if (!$questionsArray || !is_array($questionsArray)) {
                continue;
            }
            
            // Calculate total score
            $totalScore = 0;
            $totalMarks = 0;
            
            foreach ($questionsArray as $questionId) {
                // Get question details (using numeric indices like your existing code)
                // Column 7 = correctOption, Column 8 = mark
                $markQuery = "SELECT * FROM `$questionsTable` WHERE questionId = $questionId";
                $markResult = $connection->query($markQuery);
                
                if (!$markResult || $markResult->num_rows == 0) {
                    continue;
                }
                
                $questionData = $markResult->fetch_array(MYSQLI_NUM);
                $correctOption = $questionData[7]; // Column 7 is correctOption
                $questionMark = $questionData[8];   // Column 8 is mark
                $totalMarks += $questionMark;
                
                // Get student's answer (Column 4 = selectedOption based on your existing code)
                $answerQuery = "SELECT * FROM `$answersTable` 
                               WHERE userLoginId = '$examineeUserId' AND questionId = $questionId";
                $answerResult = $connection->query($answerQuery);
                
                if ($answerResult && $answerResult->num_rows > 0) {
                    $answerRow = $answerResult->fetch_array(MYSQLI_NUM);
                    $selectedOption = $answerRow[4]; // Column 4 is selectedOption
                    
                    if ($selectedOption == $correctOption) {
                        $totalScore += $questionMark;
                    }
                }
            }
            
            if ($totalMarks == 0) {
                continue; // Avoid division by zero
            }
            
            // Calculate percentage and grade
            $percentage = ($totalScore / $totalMarks) * 100;
            
            if ($percentage >= 90) $grade = 'A';
            elseif ($percentage >= 80) $grade = 'B';
            elseif ($percentage >= 70) $grade = 'C';
            elseif ($percentage >= 60) $grade = 'D';
            else $grade = 'F';
            
            // Insert into performance history
            $insertQuery = "INSERT INTO student_performance_history 
                           (studentId, subjectId, assessmentType, assessmentId, score, maxScore, 
                            percentage, grade, assessmentDate, recordedBy, notes)
                           VALUES (?, ?, 'test', ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $connection->prepare($insertQuery);
            if ($stmt) {
                $notes = "Auto-synced from test: " . $connection->real_escape_string($testName);
                $stmt->bind_param('iiidddssss', 
                    $studentId, $subjectId, $testId, $totalScore, $totalMarks,
                    $percentage, $grade, $testDate, $staffId, $notes
                );
                
                if ($stmt->execute()) {
                    $recordsSynced++;
                }
                $stmt->close();
            }
        }
    }
    
    return $recordsSynced;
}

// Main sync process
try {
    // Check if performance history table exists
    $checkPerfTable = $connection->query("SHOW TABLES LIKE 'student_performance_history'");
    
    if (!$checkPerfTable || $checkPerfTable->num_rows == 0) {
        echo json_encode([
            'success' => false, 
            'error' => 'Performance history table not found. Please import setup_performance_tables.sql first.'
        ]);
        exit();
    }
    
    // Sync test results
    $recordsSynced = syncFromDynamicTables($connection, $staffId);
    
    echo json_encode([
        'success' => true, 
        'recordsSynced' => $recordsSynced,
        'message' => "Successfully synced $recordsSynced test result(s)!"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>

