<?php
session_start();
	error_reporting(0);
include "../../db_connection/dlhs_db_connection.php";

header('Content-Type: application/json');

if (!isset($_SESSION['adminLoggedIn'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login again.']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'fetch') {
    $query = "SELECT sta.subjectTeacherAssignmentId as assignmentId, yg.yearGroupName, c.className, s.subjectName, 
                     CONCAT(UPPER(IFNULL(sl.surname, '')), ' ', IFNULL(sl.firstName, ''), ' ', IFNULL(sl.middleName, '')) as teacherName
              FROM subject_teacher_assignment sta
              LEFT JOIN classes c ON sta.classId = c.classId
              LEFT JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId
              LEFT JOIN subjects s ON sta.subjectId = s.subjectId
              LEFT JOIN stafflogin sl ON sta.teacherId = sl.staffId
              ORDER BY yg.yearGroupName, c.className, s.subjectName";
    
    $result = $connection->query($query);
    $assignments = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $assignments[] = $row;
        }
    }
    
    echo json_encode(['status' => 'success', 'data' => $assignments]);
} 
elseif ($action == 'assign') {
    $classIds = isset($_POST['classIds']) ? explode(',', $_POST['classIds']) : [];
    $subjectId = isset($_POST['subjectId']) ? mysqli_real_escape_string($connection, $_POST['subjectId']) : '';
    $teacherId = isset($_POST['teacherId']) ? mysqli_real_escape_string($connection, $_POST['teacherId']) : '';
    
    if (empty($classIds) || empty($subjectId) || empty($teacherId)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit;
    }
    
    $success = 0;
    $updated = 0;
    $errors = [];
    
    foreach ($classIds as $classId) {
        $classId = trim($classId);
        if (empty($classId)) continue;
        
        // Check for existing assignment
        $check = $connection->query("SELECT subjectTeacherAssignmentId FROM subject_teacher_assignment WHERE classId='$classId' AND subjectId='$subjectId'");
        
        if ($check && $check->num_rows > 0) {
            $row = $check->fetch_assoc();
            $aid = $row['subjectTeacherAssignmentId'];
            $q = "UPDATE subject_teacher_assignment SET teacherId='$teacherId' WHERE subjectTeacherAssignmentId='$aid'";
            if ($connection->query($q)) $updated++;
            else $errors[] = "Update failed for class ID $classId: " . $connection->error;
        } else {
            $q = "INSERT INTO subject_teacher_assignment (classId, subjectId, teacherId) VALUES ('$classId', '$subjectId', '$teacherId')";
            if ($connection->query($q)) $success++;
            else $errors[] = "Insert failed for class ID $classId: " . $connection->error;
        }
    }
    
    if (count($errors) > 0) {
        echo json_encode(['status' => 'partial', 'message' => 'Some operations failed.', 'success' => $success, 'updated' => $updated, 'errors' => $errors]);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Assignments processed successfully.', 'new' => $success, 'updated' => $updated]);
    }
} 
elseif ($action == 'delete') {
    $id = isset($_POST['id']) ? mysqli_real_escape_string($connection, $_POST['id']) : '';
    
    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID.']);
        exit;
    }
    
    if ($connection->query("DELETE FROM subject_teacher_assignment WHERE subjectTeacherAssignmentId='$id'")) {
        echo json_encode(['status' => 'success', 'message' => 'Assignment deleted.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Delete failed: ' . $connection->error]);
    }
}
elseif ($action == 'get_arms') {
    $yearGroupId = isset($_GET['yearGroupId']) ? mysqli_real_escape_string($connection, $_GET['yearGroupId']) : '';
    
    $query = "SELECT classId, className FROM classes WHERE classYearGroup = '$yearGroupId' ORDER BY className";
    $result = $connection->query($query);
    $arms = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $arms[] = $row;
        }
    }
    
    echo json_encode(['status' => 'success', 'data' => $arms]);
}
else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
?>
