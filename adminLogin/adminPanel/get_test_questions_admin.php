<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
require_once __DIR__ . '/userExpiredSession.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['adminLoggedIn'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

include __DIR__ . '/../../db_connection/dlhs_db_connection.php';

$testId = isset($_POST['testId']) ? intval($_POST['testId']) : 0;

if ($testId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid test ID']);
    exit();
}

if (!function_exists('dlhsCleanQuestionHtml')) {
    function dlhsCleanQuestionHtml($content)
    {
    if ($content === null || $content === '') {
        return '';
    }

    $decoded = htmlspecialchars_decode((string)$content, ENT_QUOTES);
    if (strpos($decoded, '&lt;') !== false || strpos($decoded, '&gt;') !== false || strpos($decoded, '&amp;') !== false) {
        $decoded = htmlspecialchars_decode($decoded, ENT_QUOTES);
    }

    // Normalize image paths for questUploadImages so relative paths always work from adminPanel
    $decoded = preg_replace_callback(
        '#src\s*=\s*["\']?(?:\.\./)*questUploadImages/([^"\'>\s]+)["\']?#i',
        function ($matches) {
            return 'src="../../questUploadImages/' . $matches[1] . '"';
        },
        $decoded
    );

        return $decoded;
    }
}

// Get test information with joined names
$testQuery = "SELECT 
    t.*,
    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(st.firstName, ''), ' ', COALESCE(st.surname, ''))), ''), 'Unknown Teacher') as teacherName,
    COALESCE(s.subjectName, 'Unknown Subject') as subjectName,
    COALESCE(y.yearGroupName, 'Unknown Year Group') as yearGroupName
    FROM tests t
    LEFT JOIN stafflogin st ON t.staffId = st.staffId
    LEFT JOIN subjects s ON t.subject = s.subjectId
    LEFT JOIN yeargroup y ON t.yearGroup = y.yearGroupId
    WHERE t.testId = $testId";

$testResult = $connection->query($testQuery);

if (!$testResult || $testResult->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Test not found']);
    exit();
}

$test = $testResult->fetch_assoc();
$questionsTableName = trim((string)($test['tableName'] ?? ''));
$duration = (int)($test['duration'] ?? 0);
$essayOption = trim((string)($test['essayOption'] ?? ''));
$essayTime = trim((string)($test['essayTime'] ?? ''));
$testName = trim((string)($test['testName'] ?? ''));
$teacherName = trim((string)($test['teacherName'] ?? 'Unknown Teacher'));
$subjectName = trim((string)($test['subjectName'] ?? 'Unknown Subject'));
$yearGroupName = trim((string)($test['yearGroupName'] ?? 'Unknown Year Group'));

$safeTableName = preg_replace('/[^a-zA-Z0-9_]/', '', $questionsTableName);
$questions = [];

if (!empty($safeTableName)) {
    $checkTable = $connection->query("SHOW TABLES LIKE '$safeTableName'");
    if ($checkTable && $checkTable->num_rows > 0) {
        // Fetch shared stimulus / reading passages if available
        $stimulusMap = [];
        $helperPath = dirname(__DIR__, 2) . '/scripts/question_authoring_helper.php';
        if (file_exists($helperPath)) {
            require_once $helperPath;
            if (function_exists('dlhsFetchSharedStimulusMapForQuestions') && function_exists('dlhsFetchValidQuestionIdsForTest')) {
                $stimulusMap = dlhsFetchSharedStimulusMapForQuestions(
                    $connection,
                    $testId,
                    dlhsFetchValidQuestionIdsForTest($connection, ['tableName' => $safeTableName])
                );
            }
        }

        $questionsQuery = "SELECT * FROM `$safeTableName` ORDER BY COALESCE(NULLIF(questionSeriaNo, 0), questionId) ASC, questionId ASC";
        $questionsResult = $connection->query($questionsQuery);

        if ($questionsResult && $questionsResult->num_rows > 0) {
            while ($row = $questionsResult->fetch_assoc()) {
                $qId = (int)$row['questionId'];
                $stimulusMeta = isset($stimulusMap[$qId]) ? $stimulusMap[$qId] : null;

                $questions[] = [
                    'questionId' => $qId,
                    'question' => dlhsCleanQuestionHtml($row['question']),
                    'optionA' => dlhsCleanQuestionHtml($row['optionA']),
                    'optionB' => dlhsCleanQuestionHtml($row['optionB']),
                    'optionC' => dlhsCleanQuestionHtml($row['optionC']),
                    'optionD' => dlhsCleanQuestionHtml($row['optionD']),
                    'optionE' => !empty($row['optionE']) ? dlhsCleanQuestionHtml($row['optionE']) : '',
                    'correctOption' => strtoupper(trim((string)($row['correctOption'] ?? ''))),
                    'mark' => (float)($row['markForQuestion'] ?? $row['mark'] ?? 0),
                    'solution' => !empty($row['solvedSolution']) ? dlhsCleanQuestionHtml($row['solvedSolution']) : '',
                    'stimulusId' => $stimulusMeta ? (int)$stimulusMeta['stimulusId'] : 0,
                    'stimulusTitle' => $stimulusMeta ? (string)$stimulusMeta['stimulusTitle'] : '',
                    'stimulusContent' => $stimulusMeta ? dlhsCleanQuestionHtml($stimulusMeta['stimulusContent']) : '',
                    'stimulusLabel' => $stimulusMeta ? (string)$stimulusMeta['stimulusLabel'] : ''
                ];
            }
        }
    }
}

// Get essay question if essay option is enabled or exists in essay_questions table
$essayQuestion = null;
$hasEssay = strcasecmp($essayOption, 'Yes') === 0 || $essayOption === '1' || $essayOption == 1;

$essayQuery = "SELECT * FROM essay_questions WHERE testId = $testId LIMIT 1";
$essayResult = $connection->query($essayQuery);
if ($essayResult && $essayResult->num_rows > 0) {
    $essayRow = $essayResult->fetch_assoc();
    $essayQuestion = [
        'question' => dlhsCleanQuestionHtml($essayRow['question']),
        'time' => $essayTime
    ];
    $hasEssay = true;
}

echo json_encode([
    'success' => true,
    'questions' => $questions,
    'essayQuestion' => $essayQuestion,
    'testInfo' => [
        'testId' => $testId,
        'testName' => $testName,
        'teacherName' => $teacherName,
        'subjectName' => $subjectName,
        'yearGroupName' => $yearGroupName,
        'duration' => $duration,
        'totalQuestions' => count($questions),
        'hasEssay' => $hasEssay,
        'essayTime' => $essayTime
    ]
]);
