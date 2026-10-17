<?php
session_start();
require_once "../../db_connection/dlhs_db_connection.php";
require_once "answer_grading_helper.php";

header('Content-Type: application/json');

if (!isset($_SESSION['studentLast_login'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit();
}

$testId = $_SESSION['idOfTest'];
$studentId = $_SESSION['studentId'];
$cachedQuestionsTable = $_SESSION['questTableName'] ?? $_SESSION['tableName'] ?? '';
$cachedAnswersTable = $_SESSION['answersTableName'] ?? '';

// Release session lock immediately so other AJAX calls (timer, tracking) are never blocked
session_write_close();

if (!isset($_POST['questionId']) || !isset($_POST['selectedOption'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

try {
    $questionId = intval($_POST['questionId']);
    $selectedOption = $connection->real_escape_string($_POST['selectedOption']);

    // Get test details and table names (use session cache if available)
    if (!empty($cachedQuestionsTable) && !empty($cachedAnswersTable)) {
        $questionsTable = $cachedQuestionsTable;
        $answersTable = $cachedAnswersTable;
    } else {
        $stmt = $connection->prepare("SELECT tableName, answersTable FROM tests WHERE testId = ?");
        $stmt->bind_param("i", $testId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Test not found");
        }

        $row = $result->fetch_assoc();
        $questionsTable = $row['tableName'];
        $answersTable = $row['answersTable'];
        $stmt->close();
    }

    dlhsSaveAnswerWithMetadata(
        $connection,
        $answersTable,
        $questionsTable,
        (int) $studentId,
        (int) $testId,
        (int) $questionId,
        $selectedOption
    );

    echo json_encode(['success' => true, 'message' => 'Answer saved successfully']);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error saving answer: ' . $e->getMessage()
    ]);
}
?>
