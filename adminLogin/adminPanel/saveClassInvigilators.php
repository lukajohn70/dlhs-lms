<?php
session_start();
if (!isset($_SESSION['adminLoggedIn'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}
include "../../db_connection/dlhs_db_connection.php";

$testId = $_POST['testId'] ?? 0;
$assignments = $_POST['assignments'] ?? []; // Expected format: [classId => invigilatorId, classId => invigilatorId]

if (!$testId) {
    echo json_encode(["status" => "error", "message" => "Invalid test ID"]);
    exit;
}

// Clear existing assignments for this test
$connection->query("DELETE FROM test_class_invigilators WHERE testId='$testId'");

$success = true;
$firstInvigilatorId = 0;

foreach ($assignments as $classId => $invigilatorId) {
    if ($invigilatorId > 0) {
        if ($firstInvigilatorId == 0) $firstInvigilatorId = $invigilatorId;
        $classId = (int)$classId;
        $invigilatorId = (int)$invigilatorId;
        
        $query = "INSERT INTO test_class_invigilators (testId, classId, invigilatorId) VALUES ('$testId', '$classId', '$invigilatorId')";
        if (!$connection->query($query)) {
            $success = false;
        }
    }
}

// Optional: Update the legacy invigilatorId in tests table just for backward compatibility
if ($firstInvigilatorId > 0) {
    $connection->query("UPDATE tests SET invigilatorId='$firstInvigilatorId' WHERE testId='$testId'");
} else {
    $connection->query("UPDATE tests SET invigilatorId='0' WHERE testId='$testId'");
}

if ($success) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Some assignments failed to save"]);
}
?>
