<?php
session_start();
error_reporting(0);

header('Content-Type: application/json');

if (!isset($_SESSION['adminLoggedIn'])) {
    echo json_encode(['status' => 'error', 'message' => 'Session expired. Please login again.']);
    exit;
}

// Release session lock immediately to prevent blocking other tabs and parallel requests
session_write_close();

include "../../db_connection/dlhs_db_connection.php";

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'fetch') {
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
elseif ($action === 'assign') {
    $rawClassIds = isset($_POST['classIds']) ? explode(',', $_POST['classIds']) : [];
    $classIds = array_values(array_filter(array_map('intval', $rawClassIds), function($id) { return $id > 0; }));
    $subjectId = isset($_POST['subjectId']) ? intval($_POST['subjectId']) : 0;
    $teacherId = isset($_POST['teacherId']) ? intval($_POST['teacherId']) : 0;
    
    if (empty($classIds) || $subjectId <= 0 || $teacherId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit;
    }
    
    // Batch lookup existing assignments in 1 query instead of N queries
    $inList = implode(',', $classIds);
    $checkQuery = "SELECT subjectTeacherAssignmentId, classId FROM subject_teacher_assignment WHERE subjectId = {$subjectId} AND classId IN ({$inList})";
    $checkResult = $connection->query($checkQuery);
    
    $existingMap = [];
    if ($checkResult) {
        while ($cRow = $checkResult->fetch_assoc()) {
            $existingMap[(int)$cRow['classId']] = (int)$cRow['subjectTeacherAssignmentId'];
        }
    }
    
    $success = 0;
    $updated = 0;
    $errors = [];
    
    // Use transaction and prepared statements for high-throughput batch write
    $connection->begin_transaction();
    
    $updStmt = $connection->prepare("UPDATE subject_teacher_assignment SET teacherId = ? WHERE subjectTeacherAssignmentId = ?");
    $insStmt = $connection->prepare("INSERT INTO subject_teacher_assignment (classId, subjectId, teacherId) VALUES (?, ?, ?)");
    
    foreach ($classIds as $classId) {
        if (isset($existingMap[$classId])) {
            $aid = $existingMap[$classId];
            $updStmt->bind_param("ii", $teacherId, $aid);
            if ($updStmt->execute()) {
                $updated++;
            } else {
                $errors[] = "Update failed for class ID {$classId}: " . $connection->error;
            }
        } else {
            $insStmt->bind_param("iii", $classId, $subjectId, $teacherId);
            if ($insStmt->execute()) {
                $success++;
            } else {
                $errors[] = "Insert failed for class ID {$classId}: " . $connection->error;
            }
        }
    }
    
    $connection->commit();
    $updStmt->close();
    $insStmt->close();
    
    if (count($errors) > 0) {
        echo json_encode(['status' => 'partial', 'message' => 'Some operations failed.', 'success' => $success, 'updated' => $updated, 'errors' => $errors]);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Assignments processed successfully.', 'new' => $success, 'updated' => $updated]);
    }
} 
elseif ($action === 'delete') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid ID.']);
        exit;
    }
    
    $stmt = $connection->prepare("DELETE FROM subject_teacher_assignment WHERE subjectTeacherAssignmentId = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Assignment deleted.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Delete failed: ' . $connection->error]);
    }
    $stmt->close();
}
elseif ($action === 'get_arms') {
    $yearGroupId = isset($_GET['yearGroupId']) ? intval($_GET['yearGroupId']) : 0;
    
    $stmt = $connection->prepare("SELECT classId, className FROM classes WHERE classYearGroup = ? ORDER BY className");
    $stmt->bind_param("i", $yearGroupId);
    $stmt->execute();
    $result = $stmt->get_result();
    $arms = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $arms[] = $row;
        }
    }
    $stmt->close();
    
    echo json_encode(['status' => 'success', 'data' => $arms]);
}
else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}
?>
