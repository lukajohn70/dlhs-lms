<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$oldErrorReporting = error_reporting(0);
$oldDisplayErrors = ini_get('display_errors');
ini_set('display_errors', 0);
include "../../db_connection/dlhs_db_connection.php";
error_reporting($oldErrorReporting);
ini_set('display_errors', $oldDisplayErrors);

$action = isset($_POST['action']) ? $_POST['action'] : '';
$response = [
    'status' => 'error',
    'message' => 'Invalid action'
];

function jsonExit($response) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

function validateRequired($fields, $data) {
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            return $field;
        }
    }
    return null;
}

try {
    switch ($action) {
        case 'create_session':
            $missing = validateRequired(['sessionName', 'startDate', 'endDate'], $_POST);
            if ($missing) {
                $response['message'] = "Missing field: $missing";
                jsonExit($response);
            }

            $sessionName = $connection->real_escape_string(trim($_POST['sessionName']));
            $startDate = $connection->real_escape_string($_POST['startDate']);
            $endDate = $connection->real_escape_string($_POST['endDate']);
            $displayOrder = isset($_POST['displayOrder']) ? (int) $_POST['displayOrder'] : 0;

            $stmt = $connection->prepare("INSERT INTO academic_sessions (sessionName, startDate, endDate, isCurrentSession, isActive, displayOrder) VALUES (?, ?, ?, 0, 0, ?)");
            $stmt->bind_param('sssi', $sessionName, $startDate, $endDate, $displayOrder);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Session created successfully'
                ];
            } else {
                $response['message'] = 'Unable to create session. It may already exist.';
            }
            $stmt->close();
            break;

        case 'update_session':
            $missing = validateRequired(['sessionId', 'sessionName', 'startDate', 'endDate'], $_POST);
            if ($missing) {
                $response['message'] = "Missing field: $missing";
                jsonExit($response);
            }

            $sessionId = (int) $_POST['sessionId'];
            $sessionName = $connection->real_escape_string(trim($_POST['sessionName']));
            $startDate = $connection->real_escape_string($_POST['startDate']);
            $endDate = $connection->real_escape_string($_POST['endDate']);
            $displayOrder = isset($_POST['displayOrder']) ? (int) $_POST['displayOrder'] : 0;

            $stmt = $connection->prepare("UPDATE academic_sessions SET sessionName = ?, startDate = ?, endDate = ?, displayOrder = ? WHERE sessionId = ?");
            $stmt->bind_param('sssii', $sessionName, $startDate, $endDate, $displayOrder, $sessionId);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Session updated successfully'
                ];
            } else {
                $response['message'] = 'Unable to update session.';
            }
            $stmt->close();
            break;

        case 'toggle_session':
            $missing = validateRequired(['sessionId', 'isActive'], $_POST);
            if ($missing) {
                $response['message'] = "Missing field: $missing";
                jsonExit($response);
            }

            $sessionId = (int) $_POST['sessionId'];
            $isActive = (int) $_POST['isActive'];

            if ($isActive === 1) {
                $stmt = $connection->prepare("UPDATE academic_sessions SET isActive = CASE WHEN sessionId = ? THEN 1 ELSE 0 END, isCurrentSession = CASE WHEN sessionId = ? THEN isCurrentSession ELSE 0 END");
                $stmt->bind_param('ii', $sessionId, $sessionId);
            } else {
                $stmt = $connection->prepare("UPDATE academic_sessions SET isActive = 0, isCurrentSession = 0 WHERE sessionId = ?");
                $stmt->bind_param('i', $sessionId);
            }

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Session status updated'
                ];
            } else {
                $response['message'] = 'Unable to update session status.';
            }
            $stmt->close();
            break;

        case 'set_current_session':
            $missing = validateRequired(['sessionId'], $_POST);
            if ($missing) {
                $response['message'] = "Missing field: $missing";
                jsonExit($response);
            }

            $sessionId = (int) $_POST['sessionId'];

            $connection->query("UPDATE academic_sessions SET isCurrentSession = 0, isActive = 0");
            $stmt = $connection->prepare("UPDATE academic_sessions SET isCurrentSession = 1, isActive = 1 WHERE sessionId = ?");
            $stmt->bind_param('i', $sessionId);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Current session updated'
                ];
            } else {
                $response['message'] = 'Unable to update current session.';
            }
            $stmt->close();
            break;

        case 'create_term':
            $missing = validateRequired(['termName'], $_POST);
            if ($missing) {
                $response['message'] = "Missing field: $missing";
                jsonExit($response);
            }

            $termName = $connection->real_escape_string(trim($_POST['termName']));
            $displayOrder = isset($_POST['displayOrder']) ? (int) $_POST['displayOrder'] : 0;
            $isDefault = isset($_POST['isDefault']) ? (int) $_POST['isDefault'] : 0;

            if ($isDefault === 1) {
                $connection->query("UPDATE academic_terms SET isDefault = 0");
            }

            $stmt = $connection->prepare("INSERT INTO academic_terms (termName, displayOrder, isDefault) VALUES (?, ?, ?)");
            $stmt->bind_param('sii', $termName, $displayOrder, $isDefault);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Term created successfully'
                ];
            } else {
                $response['message'] = 'Unable to create term. It may already exist.';
            }
            $stmt->close();
            break;

        case 'update_term':
            $missing = validateRequired(['termId', 'termName'], $_POST);
            if ($missing) {
                $response['message'] = "Missing field: $missing";
                jsonExit($response);
            }

            $termId = (int) $_POST['termId'];
            $termName = $connection->real_escape_string(trim($_POST['termName']));
            $displayOrder = isset($_POST['displayOrder']) ? (int) $_POST['displayOrder'] : 0;
            $isDefault = isset($_POST['isDefault']) ? (int) $_POST['isDefault'] : 0;

            if ($isDefault === 1) {
                $connection->query("UPDATE academic_terms SET isDefault = 0");
            }

            $stmt = $connection->prepare("UPDATE academic_terms SET termName = ?, displayOrder = ?, isDefault = ? WHERE termId = ?");
            $stmt->bind_param('siii', $termName, $displayOrder, $isDefault, $termId);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Term updated successfully'
                ];
            } else {
                $response['message'] = 'Unable to update term.';
            }
            $stmt->close();
            break;

        case 'toggle_term':
            $missing = validateRequired(['termId', 'isActive'], $_POST);
            if ($missing) {
                $response['message'] = "Missing field: $missing";
                jsonExit($response);
            }

            $termId = (int) $_POST['termId'];
            $isActive = (int) $_POST['isActive'];

            $stmt = $connection->prepare("UPDATE academic_terms SET isActive = ? WHERE termId = ?");
            $stmt->bind_param('ii', $isActive, $termId);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Term status updated'
                ];
            } else {
                $response['message'] = 'Unable to update term status.';
            }
            $stmt->close();
            break;

        case 'set_current_term':
            $missing = validateRequired(['termId'], $_POST);
            if ($missing) {
                $response['message'] = "Missing field: $missing";
                jsonExit($response);
            }

            $termId = (int) $_POST['termId'];
            $stmt = $connection->prepare("UPDATE academic_terms SET isDefault = CASE WHEN termId = ? THEN 1 ELSE 0 END, isActive = CASE WHEN termId = ? THEN 1 ELSE isActive END");
            $stmt->bind_param('ii', $termId, $termId);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Current term updated'
                ];
            } else {
                $response['message'] = 'Unable to update current term.';
            }
            $stmt->close();
            break;

        case 'create_exam_type':
            $missing = validateRequired(['examTypeName'], $_POST);
            if ($missing) {
                $response['message'] = "Missing field: $missing";
                jsonExit($response);
            }

            $examTypeName = $connection->real_escape_string(trim($_POST['examTypeName']));
            $displayOrder = isset($_POST['displayOrder']) ? (int) $_POST['displayOrder'] : 0;
            $isDefault = isset($_POST['isDefault']) ? (int) $_POST['isDefault'] : 0;

            if ($isDefault === 1) {
                $connection->query("UPDATE exam_types SET isDefault = 0");
            }

            $stmt = $connection->prepare("INSERT INTO exam_types (examTypeName, displayOrder, isDefault) VALUES (?, ?, ?)");
            $stmt->bind_param('sii', $examTypeName, $displayOrder, $isDefault);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Exam type created successfully'
                ];
            } else {
                $response['message'] = 'Unable to create exam type. It may already exist.';
            }
            $stmt->close();
            break;

        case 'update_exam_type':
            $missing = validateRequired(['examTypeId', 'examTypeName'], $_POST);
            if ($missing) {
                $response['message'] = "Missing field: $missing";
                jsonExit($response);
            }

            $examTypeId = (int) $_POST['examTypeId'];
            $examTypeName = $connection->real_escape_string(trim($_POST['examTypeName']));
            $displayOrder = isset($_POST['displayOrder']) ? (int) $_POST['displayOrder'] : 0;
            $isDefault = isset($_POST['isDefault']) ? (int) $_POST['isDefault'] : 0;

            if ($isDefault === 1) {
                $connection->query("UPDATE exam_types SET isDefault = 0");
            }

            $stmt = $connection->prepare("UPDATE exam_types SET examTypeName = ?, displayOrder = ?, isDefault = ? WHERE examTypeId = ?");
            $stmt->bind_param('siii', $examTypeName, $displayOrder, $isDefault, $examTypeId);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Exam type updated successfully'
                ];
            } else {
                $response['message'] = 'Unable to update exam type.';
            }
            $stmt->close();
            break;

        case 'toggle_exam_type':
            $missing = validateRequired(['examTypeId', 'isActive'], $_POST);
            if ($missing) {
                $response['message'] = "Missing field: $missing";
                jsonExit($response);
            }

            $examTypeId = (int) $_POST['examTypeId'];
            $isActive = (int) $_POST['isActive'];

            $stmt = $connection->prepare("UPDATE exam_types SET isActive = ? WHERE examTypeId = ?");
            $stmt->bind_param('ii', $isActive, $examTypeId);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Exam type status updated'
                ];
            } else {
                $response['message'] = 'Unable to update exam type status.';
            }
            $stmt->close();
            break;

        case 'set_current_exam_type':
            $missing = validateRequired(['examTypeId'], $_POST);
            if ($missing) {
                $response['message'] = "Missing field: $missing";
                jsonExit($response);
            }

            $examTypeId = (int) $_POST['examTypeId'];
            $stmt = $connection->prepare("UPDATE exam_types SET isDefault = CASE WHEN examTypeId = ? THEN 1 ELSE 0 END, isActive = CASE WHEN examTypeId = ? THEN 1 ELSE isActive END");
            $stmt->bind_param('ii', $examTypeId, $examTypeId);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Current exam type updated'
                ];
            } else {
                $response['message'] = 'Unable to update current exam type.';
            }
            $stmt->close();
            break;

        default:
            $response['message'] = 'Unsupported action';
    }
} catch (Exception $ex) {
    $response['message'] = $ex->getMessage();
}

jsonExit($response);
?>
