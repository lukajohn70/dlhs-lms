<?php
session_start();
if (!isset($_SESSION['adminLoggedIn'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}
include "../../db_connection/dlhs_db_connection.php";

$testId = $_POST['testId'] ?? 0;

// 1. Get Test Info (YearGroup & ClassId)
$testQuery = $connection->query("SELECT yearGroup, classId FROM tests WHERE testId='$testId'");
if(!$testQuery || $testQuery->num_rows == 0) {
    echo json_encode(["status" => "error", "message" => "Test not found"]);
    exit;
}
$testRow = $testQuery->fetch_assoc();
$yearGroupId = $testRow['yearGroup'];
$testClassId = (int)$testRow['classId'];

// 2. Get Classes
$classes = [];
if ($testClassId > 0) {
    // Test is specific to ONE class arm
    $classQuery = $connection->query("SELECT classId, className FROM classes WHERE classId='$testClassId'");
} else {
    // Test is for the whole Year Group
    $classQuery = $connection->query("SELECT classId, className FROM classes WHERE classYearGroup='$yearGroupId'");
}

while($row = $classQuery->fetch_assoc()) {
    $classes[] = $row;
}

// 3. Get Existing Assignments
$assignments = [];
$assQuery = $connection->query("SELECT classId, invigilatorId FROM test_class_invigilators WHERE testId='$testId'");
while($row = $assQuery->fetch_assoc()) {
    $assignments[$row['classId']] = $row['invigilatorId'];
}

// 4. Get Staff List
$staff = [];
$staffQuery = $connection->query("SELECT staffId, surname, firstName, middleName FROM stafflogin");
while($row = $staffQuery->fetch_assoc()) {
    $staff[] = [
        "id" => $row['staffId'],
        "name" => trim($row['surname'] . " " . $row['firstName'] . " " . $row['middleName'])
    ];
}

echo json_encode([
    "status" => "success",
    "classes" => $classes,
    "assignments" => $assignments,
    "staff" => $staff
]);
?>
