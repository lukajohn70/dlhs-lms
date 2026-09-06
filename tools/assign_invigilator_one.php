<?php
require_once __DIR__ . '/../db_connection/dlhs_db_connection.php';
$testId = 470;
$staffId = 98;

$stmt = $connection->prepare("UPDATE tests SET invigilatorId = ? WHERE testId = ?");
if (!$stmt) { echo "PREPARE_FAILED: " . $connection->error . "\n"; exit(2); }
$stmt->bind_param('ii', $staffId, $testId);
$ok = $stmt->execute();
if ($ok) {
    echo "UPDATED testId=$testId -> invigilatorId=$staffId\n";
} else {
    echo "UPDATE_FAILED: " . $stmt->error . "\n";
}

?>
