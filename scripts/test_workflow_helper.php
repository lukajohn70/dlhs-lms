<?php

if (!function_exists('dlhsEscapeIdentifier')) {
    function dlhsEscapeIdentifier($identifier)
    {
        return '`' . str_replace('`', '``', (string) $identifier) . '`';
    }
}

if (!function_exists('dlhsGetAllowedTestTypes')) {
    function dlhsGetAllowedTestTypes()
    {
        return array('CAT 1', 'CAT 2', 'EXAMS', 'MOCK', 'OTHER');
    }
}

if (!function_exists('dlhsUppercaseText')) {
    function dlhsUppercaseText($value)
    {
        $value = (string) $value;

        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($value, 'UTF-8');
        }

        return strtoupper($value);
    }
}

if (!function_exists('dlhsNormalizeTestType')) {
    function dlhsNormalizeTestType($value)
    {
        $normalized = strtoupper(trim((string) $value));
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $collapsed = str_replace(array(' ', '-', '_'), '', $normalized);

        switch ($collapsed) {
            case 'CAT1':
            case 'CA1':
                return 'CAT 1';

            case 'CAT2':
            case 'CA2':
                return 'CAT 2';

            case 'EXAM':
            case 'EXAMS':
                return 'EXAMS';

            case 'MOCK':
            case 'MOCKS':
                return 'MOCK';

            case 'OTHER':
            case 'OTHERS':
                return 'OTHER';
        }

        return in_array($normalized, dlhsGetAllowedTestTypes(), true) ? $normalized : '';
    }
}

if (!function_exists('dlhsGetEffectiveTestType')) {
    function dlhsGetEffectiveTestType($value)
    {
        $normalized = dlhsNormalizeTestType($value);

        return $normalized === '' ? 'OTHER' : $normalized;
    }
}

if (!function_exists('dlhsNormalizeCustomTestType')) {
    function dlhsNormalizeCustomTestType($value)
    {
        $normalized = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = strip_tags($normalized);
        $normalized = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $normalized);
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized));

        if ($normalized === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($normalized, 0, 120, 'UTF-8');
        }

        return substr($normalized, 0, 120);
    }
}

if (!function_exists('dlhsNormalizeMockPaperLabel')) {
    function dlhsNormalizeMockPaperLabel($value)
    {
        $normalized = dlhsUppercaseText(dlhsNormalizeCustomTestType($value));
        if ($normalized === '') {
            return '';
        }

        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $collapsed = str_replace(array(' ', '-', '_'), '', $normalized);

        if (preg_match('/^PAPER([123])$/', $collapsed, $matches)) {
            return 'PAPER ' . $matches[1];
        }

        if (preg_match('/^([123])$/', $collapsed, $matches)) {
            return 'PAPER ' . $matches[1];
        }

        return in_array($normalized, array('PAPER 1', 'PAPER 2', 'PAPER 3'), true) ? $normalized : '';
    }
}

if (!function_exists('dlhsGetDisplayTestType')) {
    function dlhsGetDisplayTestType($testType, $customTestType = '', $mockPaperLabel = '')
    {
        $effectiveTestType = dlhsGetEffectiveTestType($testType);
        $customTestType = dlhsNormalizeCustomTestType($customTestType);
        $mockPaperLabel = dlhsNormalizeMockPaperLabel($mockPaperLabel);

        if ($effectiveTestType === 'OTHER' && $customTestType !== '') {
            return dlhsUppercaseText($customTestType);
        }

        if ($effectiveTestType === 'MOCK' && $mockPaperLabel !== '') {
            return 'MOCK ' . $mockPaperLabel;
        }

        return $effectiveTestType;
    }
}

if (!function_exists('dlhsEnsureTestsCustomTypeColumn')) {
    function dlhsEnsureTestsCustomTypeColumn($connection)
    {
        static $isEnsured = false;

        if ($isEnsured) {
            return true;
        }

        $result = $connection->query("SHOW COLUMNS FROM tests LIKE 'customTestType'");
        if ($result && $result->num_rows > 0) {
            $isEnsured = true;
            return true;
        }

        $altered = $connection->query("ALTER TABLE tests ADD COLUMN customTestType VARCHAR(120) NOT NULL DEFAULT '' AFTER testType");
        if ($altered) {
            $isEnsured = true;
            return true;
        }

        return false;
    }
}

if (!function_exists('dlhsEnsureTestsMockPaperColumn')) {
    function dlhsEnsureTestsMockPaperColumn($connection)
    {
        static $isEnsured = false;

        if ($isEnsured) {
            return true;
        }

        $result = $connection->query("SHOW COLUMNS FROM tests LIKE 'mockPaperLabel'");
        if ($result && $result->num_rows > 0) {
            $isEnsured = true;
            return true;
        }

        $altered = $connection->query("ALTER TABLE tests ADD COLUMN mockPaperLabel VARCHAR(30) NOT NULL DEFAULT '' AFTER customTestType");
        if ($altered) {
            $isEnsured = true;
            return true;
        }

        return false;
    }
}

if (!function_exists('dlhsGetTestTypeSortWeight')) {
    function dlhsGetTestTypeSortWeight($value)
    {
        switch (dlhsGetEffectiveTestType($value)) {
            case 'CAT 1':
                return 1;

            case 'CAT 2':
                return 2;

            case 'EXAMS':
                return 3;

            case 'MOCK':
                return 4;

            case 'OTHER':
            default:
                return 5;
        }
    }
}

if (!function_exists('dlhsGetConfiguredTestTypes')) {
    function dlhsGetConfiguredTestTypes($connection)
    {
        $types = array();

        $tableCheck = $connection->query("SHOW TABLES LIKE 'exam_types'");
        if ($tableCheck && $tableCheck->num_rows > 0) {
            $result = $connection->query("SELECT examTypeName FROM exam_types WHERE isActive = 1 ORDER BY isDefault DESC, displayOrder ASC, examTypeName ASC");
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $normalized = dlhsNormalizeTestType($row['examTypeName']);
                    if ($normalized !== '' && !in_array($normalized, $types, true)) {
                        $types[] = $normalized;
                    }
                }
            }
        }

        foreach (dlhsGetAllowedTestTypes() as $defaultType) {
            if (!in_array($defaultType, $types, true)) {
                $types[] = $defaultType;
            }
        }

        return $types;
    }
}

if (!function_exists('dlhsSortTestsByTypeAndDate')) {
    function dlhsSortTestsByTypeAndDate(array &$tests, $testTypeKey = 'testType', $dateKey = 'testDate', $nameKey = 'testName')
    {
        usort($tests, function ($left, $right) use ($testTypeKey, $dateKey, $nameKey) {
            $leftTypeWeight = dlhsGetTestTypeSortWeight(isset($left[$testTypeKey]) ? $left[$testTypeKey] : '');
            $rightTypeWeight = dlhsGetTestTypeSortWeight(isset($right[$testTypeKey]) ? $right[$testTypeKey] : '');

            if ($leftTypeWeight !== $rightTypeWeight) {
                return $leftTypeWeight - $rightTypeWeight;
            }

            $leftDate = isset($left[$dateKey]) ? strtotime((string) $left[$dateKey]) : false;
            $rightDate = isset($right[$dateKey]) ? strtotime((string) $right[$dateKey]) : false;

            if ($leftDate !== $rightDate) {
                if ($leftDate === false) {
                    return 1;
                }
                if ($rightDate === false) {
                    return -1;
                }

                return $leftDate <=> $rightDate;
            }

            $leftName = isset($left[$nameKey]) ? (string) $left[$nameKey] : '';
            $rightName = isset($right[$nameKey]) ? (string) $right[$nameKey] : '';

            return strcasecmp($leftName, $rightName);
        });
    }
}

if (!function_exists('dlhsGetAcademicSessions')) {
    function dlhsGetAcademicSessions($connection, $activeOnly = false)
    {
        $sessions = array();

        $tableCheck = $connection->query("SHOW TABLES LIKE 'academic_sessions'");
        if (!$tableCheck || $tableCheck->num_rows < 1) {
            return $sessions;
        }

        $query = "SELECT sessionId, sessionName, startDate, endDate, isCurrentSession, isActive, displayOrder
                  FROM academic_sessions";
        if ($activeOnly) {
            $query .= " WHERE isActive = 1";
        }
        $query .= " ORDER BY isCurrentSession DESC, isActive DESC, displayOrder ASC, startDate DESC, sessionName ASC";

        $result = $connection->query($query);
        if (!$result) {
            return $sessions;
        }

        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }

        return $sessions;
    }
}

if (!function_exists('dlhsGetCurrentAcademicSessionName')) {
    function dlhsGetCurrentAcademicSessionName($connection)
    {
        $sessions = dlhsGetAcademicSessions($connection, false);
        foreach ($sessions as $sessionRow) {
            if (!empty($sessionRow['isCurrentSession'])) {
                return isset($sessionRow['sessionName']) ? trim((string) $sessionRow['sessionName']) : '';
            }
        }

        foreach ($sessions as $sessionRow) {
            if (!empty($sessionRow['isActive'])) {
                return isset($sessionRow['sessionName']) ? trim((string) $sessionRow['sessionName']) : '';
            }
        }

        return '';
    }
}

if (!function_exists('dlhsResolveAcademicSessionName')) {
    function dlhsResolveAcademicSessionName($connection, $requestedValue = '')
    {
        $requestedValue = trim((string) $requestedValue);
        $sessions = dlhsGetAcademicSessions($connection, false);

        if ($requestedValue !== '') {
            foreach ($sessions as $sessionRow) {
                $sessionId = isset($sessionRow['sessionId']) ? (string) $sessionRow['sessionId'] : '';
                $sessionName = isset($sessionRow['sessionName']) ? trim((string) $sessionRow['sessionName']) : '';
                if ($requestedValue === $sessionId || strcasecmp($requestedValue, $sessionName) === 0) {
                    return $sessionName;
                }
            }
        }

        return dlhsGetCurrentAcademicSessionName($connection);
    }
}

if (!function_exists('dlhsBuildSubjectShortCode')) {
    function dlhsBuildSubjectShortCode($subjectName)
    {
        $subjectName = strtoupper(trim((string) $subjectName));
        $subjectCode = preg_replace('/[^A-Z0-9]/', '', $subjectName);

        if ($subjectCode === '') {
            return 'SUB';
        }

        return substr($subjectCode, 0, min(3, strlen($subjectCode)));
    }
}

if (!function_exists('dlhsBuildGeneratedTestName')) {
    function dlhsBuildGeneratedTestName($academicSession, $yearGroupName, $subjectName, $testType, $customTestType = '', $mockPaperLabel = '')
    {
        $academicSession = trim((string) $academicSession);
        $yearGroupName = dlhsUppercaseText(trim((string) $yearGroupName));
        $subjectCode = dlhsBuildSubjectShortCode($subjectName);
        $effectiveTestType = dlhsGetDisplayTestType($testType, $customTestType, $mockPaperLabel);

        $parts = array_filter(array($academicSession, $yearGroupName, $subjectCode, $effectiveTestType), function ($value) {
            return trim((string) $value) !== '';
        });

        return trim(implode(' ', $parts));
    }
}

if (!function_exists('dlhsFetchScalarValue')) {
    function dlhsFetchScalarValue($connection, $query, array $params = array(), $types = '')
    {
        $stmt = $connection->prepare($query);
        if (!$stmt) {
            return '';
        }

        if (!empty($params)) {
            if ($types === '') {
                $types = str_repeat('s', count($params));
            }
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $value = '';
        if ($result && $row = $result->fetch_row()) {
            $value = isset($row[0]) ? (string) $row[0] : '';
        }
        $stmt->close();

        return $value;
    }
}

if (!function_exists('dlhsBuildGeneratedTestNameFromIds')) {
    function dlhsBuildGeneratedTestNameFromIds($connection, $academicSession, $yearGroupId, $subjectId, $testType, $customTestType = '', $mockPaperLabel = '')
    {
        $yearGroupId = (int) $yearGroupId;
        $subjectId = (int) $subjectId;

        $yearGroupName = '';
        if ($yearGroupId > 0) {
            $yearGroupName = dlhsFetchScalarValue(
                $connection,
                "SELECT yearGroupName FROM yeargroup WHERE yearGroupId = ? LIMIT 1",
                array($yearGroupId),
                'i'
            );
        }

        $subjectName = '';
        if ($subjectId > 0) {
            $subjectName = dlhsFetchScalarValue(
                $connection,
                "SELECT subjectName FROM subjects WHERE subjectId = ? LIMIT 1",
                array($subjectId),
                'i'
            );
        }

        return dlhsBuildGeneratedTestName($academicSession, $yearGroupName, $subjectName, $testType, $customTestType, $mockPaperLabel);
    }
}

if (!function_exists('dlhsTableExists')) {
    function dlhsTableExists($connection, $tableName)
    {
        if ($tableName === '') {
            return false;
        }

        $escapedTable = mysqli_real_escape_string($connection, $tableName);
        $result = $connection->query("SHOW TABLES LIKE '{$escapedTable}'");

        return $result && $result->num_rows > 0;
    }
}

if (!function_exists('dlhsFetchTestRowById')) {
    function dlhsFetchTestRowById($connection, $testId)
    {
        $testId = (int) $testId;
        if ($testId <= 0) {
            return null;
        }

        $result = $connection->query("SELECT * FROM tests WHERE testId='{$testId}' LIMIT 1");
        if (!$result || $result->num_rows < 1) {
            return null;
        }

        return $result->fetch_assoc();
    }
}

if (!function_exists('dlhsCountRowsInDynamicTable')) {
    function dlhsCountRowsInDynamicTable($connection, $tableName)
    {
        if (!dlhsTableExists($connection, $tableName)) {
            return 0;
        }

        $tableSql = dlhsEscapeIdentifier($tableName);
        $result = $connection->query("SELECT COUNT(*) AS totalCount FROM {$tableSql}");
        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();

        return isset($row['totalCount']) ? (int) $row['totalCount'] : 0;
    }
}

if (!function_exists('dlhsHasEssayQuestionsForTest')) {
    function dlhsHasEssayQuestionsForTest($connection, $testId)
    {
        $testId = (int) $testId;
        if ($testId <= 0) {
            return false;
        }

        $result = $connection->query(
            "SELECT COUNT(*) AS totalCount
             FROM essay_questions
             WHERE testId='{$testId}'
               AND TRIM(COALESCE(question, '')) <> ''"
        );

        if (!$result) {
            return false;
        }

        $row = $result->fetch_assoc();

        return isset($row['totalCount']) && (int) $row['totalCount'] > 0;
    }
}

if (!function_exists('dlhsBuildTestSetupSummary')) {
    function dlhsBuildTestSetupSummary($connection, array $testRow)
    {
        $studentCount = dlhsCountRowsInDynamicTable($connection, isset($testRow['examineesTableName']) ? $testRow['examineesTableName'] : '');
        $objectiveQuestionCount = dlhsCountRowsInDynamicTable($connection, isset($testRow['tableName']) ? $testRow['tableName'] : '');
        $hasEssayQuestions = dlhsHasEssayQuestionsForTest($connection, isset($testRow['testId']) ? $testRow['testId'] : 0);
        $questionsAdded = ($objectiveQuestionCount > 0) || $hasEssayQuestions;
        $studentsAdded = $studentCount > 0;

        $setupStatus = 'Pending students';
        $setupMessage = 'Add students to complete test setup.';

        if ($studentsAdded && !$questionsAdded) {
            $setupStatus = 'Ready for questions';
            $setupMessage = 'Students are added. You can now add questions.';
        } elseif ($studentsAdded && $questionsAdded) {
            $setupStatus = 'Ready for invigilation';
            $setupMessage = 'Students and questions are in place.';
        }

        return array(
            'studentCount' => $studentCount,
            'objectiveQuestionCount' => $objectiveQuestionCount,
            'hasEssayQuestions' => $hasEssayQuestions,
            'questionsAdded' => $questionsAdded,
            'studentsAdded' => $studentsAdded,
            'isReadyForInvigilation' => $studentsAdded && $questionsAdded,
            'setupStatus' => $setupStatus,
            'setupMessage' => $setupMessage
        );
    }
}

if (!function_exists('dlhsCheckQuestionEntryAllowed')) {
    function dlhsCheckQuestionEntryAllowed($connection, array $testRow)
    {
        $summary = dlhsBuildTestSetupSummary($connection, $testRow);

        if (!$summary['studentsAdded']) {
            return array(
                'allowed' => false,
                'code' => 'students_required',
                'message' => 'Add students to this test before adding questions.',
                'summary' => $summary
            );
        }

        return array(
            'allowed' => true,
            'code' => 'ok',
            'message' => '',
            'summary' => $summary
        );
    }
}

if (!function_exists('dlhsCheckTestCanStart')) {
    function dlhsCheckTestCanStart($connection, array $testRow)
    {
        $summary = dlhsBuildTestSetupSummary($connection, $testRow);

        if (!$summary['studentsAdded']) {
            return array(
                'allowed' => false,
                'code' => 'students_required',
                'message' => 'You cannot start invigilation until students are added to this test.',
                'summary' => $summary
            );
        }

        if (!$summary['questionsAdded']) {
            return array(
                'allowed' => false,
                'code' => 'questions_required',
                'message' => 'You cannot start invigilation until questions are added to this test.',
                'summary' => $summary
            );
        }

        return array(
            'allowed' => true,
            'code' => 'ok',
            'message' => '',
            'summary' => $summary
        );
    }
}

if (!function_exists('dlhsEnsureTestTablesExist')) {
    function dlhsEnsureTestTablesExist($connection, array $testRow)
    {
        $testId = (int)$testRow['testId'];
        $qTable = $testRow['tableName'];
        $eTable = $testRow['examineesTableName'];
        $aTable = $testRow['answersTable'];

        if (!empty($qTable)) {
            $connection->query("CREATE TABLE IF NOT EXISTS `$qTable` (
                questionId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                question TEXT NOT NULL,
                optionA TEXT NOT NULL,
                optionB TEXT NOT NULL,
                optionC TEXT NOT NULL,
                optionD TEXT NOT NULL,
                optionE TEXT NOT NULL,
                correctOption VARCHAR(10) NOT NULL,
                markForQuestion FLOAT NOT NULL,
                solvedSolution TEXT NOT NULL,
                questionSeriaNo INT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8");
        }

        if (!empty($eTable)) {
            $connection->query("CREATE TABLE IF NOT EXISTS `$eTable` (
                examineeId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                testId INT NOT NULL,
                examineeUserId INT NOT NULL,
                studentClassId INT NOT NULL,
                studentYearGroupId INT NOT NULL,
                testStatus INT NOT NULL,
                questArray TEXT NOT NULL, 
                remainingTime INT NOT NULL DEFAULT 0,
                totalToBeEarned DECIMAL NOT NULL DEFAULT 0,
                totalEarned DECIMAL NOT NULL DEFAULT 0,
                noOfQuestions INT NOT NULL DEFAULT 0,
                noCorrect INT NOT NULL DEFAULT 0,
                timeStartedTest VARCHAR(10) NOT NULL DEFAULT '',
                timeSubmittedTest VARCHAR(10) NOT NULL DEFAULT '',
                attendance INT(1) NOT NULL DEFAULT 0,
                isStarted INT(1) NOT NULL DEFAULT 0,
                isPaused INT(1) NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8");
        }

        if (!empty($aTable)) {
            $connection->query("CREATE TABLE IF NOT EXISTS `$aTable` (
                testAnswerId INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                userLoginId INT NOT NULL,
                testId INT NOT NULL,
                questionId INT NOT NULL,
                selectedOption VARCHAR(10) NOT NULL,
                correctOption VARCHAR(10) NOT NULL,
                markForQuestion FLOAT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8");
        }
    }
}

