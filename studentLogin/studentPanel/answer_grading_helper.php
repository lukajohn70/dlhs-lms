<?php

function dlhsNormalizeOptionValue($value)
{
    $normalized = trim((string) $value);

    if ($normalized === '') {
        return '';
    }

    return strtoupper($normalized);
}

function dlhsQuoteIdentifier($identifier)
{
    if (!is_string($identifier) || !preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
        throw new InvalidArgumentException('Invalid table identifier supplied.');
    }

    return '`' . $identifier . '`';
}

function dlhsFetchQuestionScoringMeta(mysqli $connection, $questionsTableName, $questionId)
{
    $questionsTableSql = dlhsQuoteIdentifier($questionsTableName);
    $query = "SELECT correctOption, markForQuestion FROM {$questionsTableSql} WHERE questionId = ?";
    $stmt = $connection->prepare($query);

    if (!$stmt) {
        throw new RuntimeException('Unable to prepare question metadata query: ' . $connection->error);
    }

    $stmt->bind_param("i", $questionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        throw new RuntimeException("Question metadata not found for questionId {$questionId}.");
    }

    return [
        'correctOption' => dlhsNormalizeOptionValue($row['correctOption'] ?? ''),
        'markForQuestion' => (float) ($row['markForQuestion'] ?? 0),
    ];
}

function dlhsSaveAnswerWithMetadata(mysqli $connection, $answersTableName, $questionsTableName, $studentId, $testId, $questionId, $selectedOption)
{
    $answersTableSql = dlhsQuoteIdentifier($answersTableName);
    $meta = dlhsFetchQuestionScoringMeta($connection, $questionsTableName, $questionId);
    $selectedOption = dlhsNormalizeOptionValue($selectedOption);
    $correctOption = $meta['correctOption'];
    $markForQuestion = $meta['markForQuestion'];

    $checkQuery = "SELECT testAnswerId FROM {$answersTableSql} WHERE userLoginId = ? AND testId = ? AND questionId = ?";
    $checkStmt = $connection->prepare($checkQuery);

    if (!$checkStmt) {
        throw new RuntimeException('Unable to prepare answer lookup query: ' . $connection->error);
    }

    $checkStmt->bind_param("iii", $studentId, $testId, $questionId);
    $checkStmt->execute();
    $existing = $checkStmt->get_result();
    $rowExists = $existing && $existing->num_rows > 0;
    $checkStmt->close();

    if ($rowExists) {
        $query = "UPDATE {$answersTableSql}
                  SET selectedOption = ?, correctOption = ?, markForQuestion = ?
                  WHERE userLoginId = ? AND testId = ? AND questionId = ?";
        $stmt = $connection->prepare($query);

        if (!$stmt) {
            throw new RuntimeException('Unable to prepare answer update query: ' . $connection->error);
        }

        $stmt->bind_param("ssdiii", $selectedOption, $correctOption, $markForQuestion, $studentId, $testId, $questionId);
    } else {
        $query = "INSERT INTO {$answersTableSql}
                  (userLoginId, testId, questionId, selectedOption, correctOption, markForQuestion)
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($query);

        if (!$stmt) {
            throw new RuntimeException('Unable to prepare answer insert query: ' . $connection->error);
        }

        $stmt->bind_param("iiissd", $studentId, $testId, $questionId, $selectedOption, $correctOption, $markForQuestion);
    }

    $ok = $stmt->execute();
    if (!$ok) {
        $error = $stmt->error;
        $stmt->close();
        throw new RuntimeException('Unable to save answer: ' . $error);
    }

    $stmt->close();

    return $meta;
}

function dlhsRepairAnswerMetadata(mysqli $connection, $answersTableName, $questionsTableName, $studentId = null, $testId = null)
{
    $answersTableSql = dlhsQuoteIdentifier($answersTableName);
    $questionsTableSql = dlhsQuoteIdentifier($questionsTableName);

    $query = "UPDATE {$answersTableSql} a
              INNER JOIN {$questionsTableSql} q ON q.questionId = a.questionId
              SET a.selectedOption = UPPER(TRIM(a.selectedOption)),
                  a.correctOption = UPPER(TRIM(q.correctOption)),
                  a.markForQuestion = q.markForQuestion";

    $params = [];
    $types = '';
    $clauses = [];

    if ($studentId !== null) {
        $clauses[] = "a.userLoginId = ?";
        $types .= 'i';
        $params[] = (int) $studentId;
    }

    if ($testId !== null) {
        $clauses[] = "a.testId = ?";
        $types .= 'i';
        $params[] = (int) $testId;
    }

    if (!empty($clauses)) {
        $query .= ' WHERE ' . implode(' AND ', $clauses);
    }

    $stmt = $connection->prepare($query);
    if (!$stmt) {
        throw new RuntimeException('Unable to prepare answer repair query: ' . $connection->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    return $affectedRows;
}
