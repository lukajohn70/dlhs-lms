<?php
session_start();
	error_reporting(0);
require_once '../../db_connection/dlhs_db_connection.php';
require_once '../../scripts/online_tracking_schema.php';

// Check if admin is logged in
if (!isset($_SESSION['adminId'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

dlhsEnsureOnlineTrackingTables($connection);

// Get all active test takers with their details
$query = "
    SELECT 
        att.studentId,
        att.testId,
        att.testName,
        att.remainingTime,
        att.questionsAnswered,
        att.totalQuestions,
        ou.fullName as studentName,
        ou.ipAddress,
        att.lastActivityAt
    FROM active_test_takers att
    LEFT JOIN online_users ou ON att.studentId = ou.userId AND ou.userType = 'student'
    WHERE att.isActive = 1
    ORDER BY att.lastActivityAt DESC
";

$result = $connection->query($query);

$testTakers = [];
$withViolations = 0;
$highRisk = 0;

while ($row = $result->fetch_assoc()) {
    // Get violation count for this student
    $violationQuery = "
        SELECT COUNT(*) as count 
        FROM security_violations 
        WHERE studentId = ? 
        AND testId = ?
        AND timestamp >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
    ";
    $stmt = $connection->prepare($violationQuery);
    if ($stmt) {
        $stmt->bind_param("ii", $row['studentId'], $row['testId']);
        $stmt->execute();
        $violationResult = $stmt->get_result();
        $violationCount = $violationResult->fetch_assoc()['count'];
        $stmt->close();
    } else {
        $violationCount = 0;
    }
    
    if ($violationCount > 0) {
        $withViolations++;
    }
    
    if ($violationCount >= 5) {
        $highRisk++;
    }
    
    // Format remaining time
    $remainingMinutes = ceil($row['remainingTime'] / 60);
    $remainingTime = $remainingMinutes > 0 ? $remainingMinutes . ' min' : 'Time up!';
    
    $testTakers[] = [
        'studentId' => $row['studentId'],
        'testId' => $row['testId'],
        'studentName' => $row['studentName'] ?? 'Unknown Student',
        'testName' => $row['testName'],
        'remainingTime' => $remainingTime,
        'questionsAnswered' => $row['questionsAnswered'] ?? 0,
        'totalQuestions' => $row['totalQuestions'] ?? 0,
        'violationCount' => $violationCount,
        'ipAddress' => $row['ipAddress'] ?? 'Unknown',
        'lastActivity' => $row['lastActivityAt']
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'total' => count($testTakers),
    'withViolations' => $withViolations,
    'highRisk' => $highRisk,
    'testTakers' => $testTakers
]);
?>



