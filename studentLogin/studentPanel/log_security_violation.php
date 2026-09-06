<?php
session_start();

if (!isset($_SESSION['studentId']) || !isset($_SESSION['idOfTest'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once('../../db_connection/dlhs_db_connection.php');

$studentId = $_SESSION['studentId'];
$testId = $_SESSION['idOfTest'];

// Create security_violations table if it doesn't exist
$createTableQuery = "
CREATE TABLE IF NOT EXISTS security_violations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    studentId INT NOT NULL,
    testId INT NOT NULL,
    violation VARCHAR(255) NOT NULL,
    timestamp DATETIME NOT NULL,
    userAgent TEXT,
    screenResolution VARCHAR(50),
    violationCount INT DEFAULT 1,
    autoSubmit TINYINT(1) DEFAULT 0,
    ipAddress VARCHAR(45),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_test (studentId, testId),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

$connection->query($createTableQuery);

// Get violation details
$violation = $_POST['violation'] ?? 'Unknown violation';
$timestamp = $_POST['timestamp'] ?? date('Y-m-d H:i:s');
$userAgent = $_POST['userAgent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$screenResolution = $_POST['screenResolution'] ?? 'Unknown';
$violationCount = intval($_POST['count'] ?? 1);
$autoSubmit = isset($_POST['autoSubmit']) ? 1 : 0;
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

// Insert violation record
$stmt = $connection->prepare("
    INSERT INTO security_violations 
    (studentId, testId, violation, timestamp, userAgent, screenResolution, violationCount, autoSubmit, ipAddress)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iissssiis",
    $studentId,
    $testId,
    $violation,
    $timestamp,
    $userAgent,
    $screenResolution,
    $violationCount,
    $autoSubmit,
    $ipAddress
);

if ($stmt->execute()) {
    // If this is an auto-submit violation, flag the test
    if ($autoSubmit) {
        $testedTableName = $_SESSION['testedTableName'] ?? null;
        if ($testedTableName) {
            $flagQuery = "UPDATE `$testedTableName` SET 
                         testStatus = 2,
                         timeSubmittedTest = NOW()
                         WHERE examineeUserId = ? AND testId = ?";
            $flagStmt = $connection->prepare($flagQuery);
            $flagStmt->bind_param("ii", $studentId, $testId);
            $flagStmt->execute();
            $flagStmt->close();
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Violation logged',
        'violationCount' => $violationCount
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to log violation'
    ]);
}

$stmt->close();
?>



