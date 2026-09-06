<?php

if (!function_exists('dlhsEnsureEssayAttemptsTable')) {
    function dlhsEnsureEssayAttemptsTable($connection)
    {
        static $essayAttemptsReady = false;

        if ($essayAttemptsReady) {
            return true;
        }

        $query = "CREATE TABLE IF NOT EXISTS essay_attempts (
                    essayAttemptId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    testId INT NOT NULL,
                    studentId INT NOT NULL,
                    startedAt DATETIME NOT NULL,
                    expiresAt DATETIME NOT NULL,
                    submittedAt DATETIME NULL,
                    isSubmitted TINYINT(1) NOT NULL DEFAULT 0,
                    UNIQUE KEY uniq_test_student (testId, studentId),
                    KEY idx_expires (expiresAt)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

        $essayAttemptsReady = (bool) $connection->query($query);

        return $essayAttemptsReady;
    }
}

if (!function_exists('dlhsTestHasEssay')) {
    function dlhsTestHasEssay($connection, $testId)
    {
        $testId = (int) $testId;

        if ($testId <= 0) {
            return false;
        }

        $stmt = $connection->prepare("SELECT 1 FROM essay_questions WHERE testId = ? LIMIT 1");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $testId);
        $stmt->execute();
        $stmt->store_result();
        $hasEssay = $stmt->num_rows > 0;
        $stmt->close();

        return $hasEssay;
    }
}

if (!function_exists('dlhsHasSubmittedEssay')) {
    function dlhsHasSubmittedEssay($connection, $testId, $studentId)
    {
        $testId = (int) $testId;
        $studentId = (int) $studentId;

        if ($testId <= 0 || $studentId <= 0) {
            return false;
        }

        if (dlhsEnsureEssayAttemptsTable($connection)) {
            $attemptStmt = $connection->prepare(
                "SELECT isSubmitted
                 FROM essay_attempts
                 WHERE testId = ? AND studentId = ?
                 LIMIT 1"
            );

            if ($attemptStmt) {
                $attemptStmt->bind_param('ii', $testId, $studentId);
                $attemptStmt->execute();
                $attemptResult = $attemptStmt->get_result();
                $attemptRow = $attemptResult ? $attemptResult->fetch_assoc() : null;
                $attemptStmt->close();

                if ($attemptRow && !empty($attemptRow['isSubmitted'])) {
                    return true;
                }
            }
        }

        $tableCheck = $connection->query("SHOW TABLES LIKE 'essay_answers'");
        if (!$tableCheck || $tableCheck->num_rows < 1) {
            return false;
        }

        $answerStmt = $connection->prepare(
            "SELECT 1
             FROM essay_answers
             WHERE testId = ? AND studentId = ? AND TRIM(COALESCE(essayAnswer, '')) <> ''
             LIMIT 1"
        );

        if (!$answerStmt) {
            return false;
        }

        $answerStmt->bind_param('ii', $testId, $studentId);
        $answerStmt->execute();
        $answerStmt->store_result();
        $hasSubmitted = $answerStmt->num_rows > 0;
        $answerStmt->close();

        return $hasSubmitted;
    }
}

if (!function_exists('dlhsGetOrCreateEssayAttempt')) {
    function dlhsGetOrCreateEssayAttempt($connection, $testId, $studentId, $essayMinutes)
    {
        $testId = (int) $testId;
        $studentId = (int) $studentId;
        $essayMinutes = (int) $essayMinutes;

        if ($testId <= 0 || $studentId <= 0 || $essayMinutes <= 0) {
            return null;
        }

        if (!dlhsEnsureEssayAttemptsTable($connection)) {
            return null;
        }

        $selectStmt = $connection->prepare(
            "SELECT essayAttemptId, startedAt, expiresAt, submittedAt, isSubmitted
             FROM essay_attempts
             WHERE testId = ? AND studentId = ?
             LIMIT 1"
        );

        if (!$selectStmt) {
            return null;
        }

        $selectStmt->bind_param('ii', $testId, $studentId);
        $selectStmt->execute();
        $result = $selectStmt->get_result();
        $attemptRow = $result ? $result->fetch_assoc() : null;
        $selectStmt->close();

        if ($attemptRow) {
            return $attemptRow;
        }

        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', time() + ($essayMinutes * 60));

        $insertStmt = $connection->prepare(
            "INSERT INTO essay_attempts (testId, studentId, startedAt, expiresAt)
             VALUES (?, ?, ?, ?)"
        );

        if (!$insertStmt) {
            return null;
        }

        $insertStmt->bind_param('iiss', $testId, $studentId, $now, $expiresAt);
        $insertStmt->execute();
        $insertStmt->close();

        return array(
            'startedAt' => $now,
            'expiresAt' => $expiresAt,
            'submittedAt' => null,
            'isSubmitted' => 0
        );
    }
}

if (!function_exists('dlhsGetEssayRemainingSeconds')) {
    function dlhsGetEssayRemainingSeconds(array $attemptRow, $nowTimestamp = null)
    {
        $expiresAt = isset($attemptRow['expiresAt']) ? strtotime((string) $attemptRow['expiresAt']) : false;
        if ($expiresAt === false) {
            return 0;
        }

        $nowTimestamp = $nowTimestamp === null ? time() : (int) $nowTimestamp;

        return max(0, $expiresAt - $nowTimestamp);
    }
}

if (!function_exists('dlhsMarkEssaySubmitted')) {
    function dlhsMarkEssaySubmitted($connection, $testId, $studentId)
    {
        $testId = (int) $testId;
        $studentId = (int) $studentId;

        if ($testId <= 0 || $studentId <= 0) {
            return false;
        }

        if (!dlhsEnsureEssayAttemptsTable($connection)) {
            return false;
        }

        $submittedAt = date('Y-m-d H:i:s');
        $updateStmt = $connection->prepare(
            "UPDATE essay_attempts
             SET isSubmitted = 1, submittedAt = ?
             WHERE testId = ? AND studentId = ?"
        );

        if (!$updateStmt) {
            return false;
        }

        $updateStmt->bind_param('sii', $submittedAt, $testId, $studentId);
        $success = $updateStmt->execute();
        $updateStmt->close();

        return $success;
    }
}
