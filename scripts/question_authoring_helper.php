<?php

require_once __DIR__ . '/test_workflow_helper.php';

if (!function_exists('dlhsEnsureQuestionStimulusTables')) {
    function dlhsEnsureQuestionStimulusTables($connection)
    {
        static $initialized = false;

        if ($initialized) {
            return true;
        }

        $stimuliTable = "
            CREATE TABLE IF NOT EXISTS `question_shared_stimuli` (
                `stimulusId` int NOT NULL AUTO_INCREMENT,
                `testId` int NOT NULL DEFAULT '0',
                `staffId` int NOT NULL DEFAULT '0',
                `stimulusType` varchar(20) NOT NULL DEFAULT 'PASSAGE',
                `stimulusTitle` varchar(255) NOT NULL DEFAULT '',
                `stimulusContent` mediumtext NOT NULL,
                `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updatedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`stimulusId`),
                KEY `idx_qss_test` (`testId`),
                KEY `idx_qss_staff` (`staffId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";

        $linksTable = "
            CREATE TABLE IF NOT EXISTS `question_shared_stimulus_links` (
                `linkId` int NOT NULL AUTO_INCREMENT,
                `testId` int NOT NULL DEFAULT '0',
                `questionId` int NOT NULL DEFAULT '0',
                `stimulusId` int NOT NULL DEFAULT '0',
                `createdAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`linkId`),
                UNIQUE KEY `uq_qssl_test_question` (`testId`,`questionId`),
                KEY `idx_qssl_stimulus` (`stimulusId`),
                KEY `idx_qssl_test_stimulus` (`testId`,`stimulusId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";

        if (!$connection->query($stimuliTable)) {
            return false;
        }

        if (!$connection->query($linksTable)) {
            return false;
        }

        $initialized = true;

        return true;
    }
}

if (!function_exists('dlhsQuestionStimulusTypes')) {
    function dlhsQuestionStimulusTypes()
    {
        return array('PASSAGE', 'IMAGE', 'TABLE', 'MIXED');
    }
}

if (!function_exists('dlhsNormalizeStimulusType')) {
    function dlhsNormalizeStimulusType($value)
    {
        $value = strtoupper(trim((string) $value));

        if (in_array($value, dlhsQuestionStimulusTypes(), true)) {
            return $value;
        }

        return 'PASSAGE';
    }
}

if (!function_exists('dlhsEditorHtmlToPlainText')) {
    function dlhsEditorHtmlToPlainText($html)
    {
        $html = html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $html = str_replace(array('&nbsp;', chr(194) . chr(160)), ' ', $html);
        $text = strip_tags($html);
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string) $text);
    }
}

if (!function_exists('dlhsNormalizeImportedText')) {
    function dlhsNormalizeImportedText($value)
    {
        $value = (string) $value;
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);

        if (function_exists('mb_detect_encoding') && !mb_detect_encoding($value, 'UTF-8', true)) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');
            if ($converted !== false) {
                $value = $converted;
            }
        }

        return str_replace(array("\r\n", "\r"), "\n", $value);
    }
}

if (!function_exists('dlhsEncodeHtmlForStorage')) {
    function dlhsEncodeHtmlForStorage($value)
    {
        return htmlspecialchars(
            dlhsNormalizeImportedText($value),
            ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
            'UTF-8'
        );
    }
}

if (!function_exists('dlhsEditorHtmlHasContent')) {
    function dlhsEditorHtmlHasContent($html)
    {
        return dlhsEditorHtmlToPlainText($html) !== '';
    }
}

if (!function_exists('dlhsOptionLooksLikePlaceholder')) {
    function dlhsOptionLooksLikePlaceholder($html)
    {
        $text = strtoupper(dlhsEditorHtmlToPlainText($html));
        $text = preg_replace('/[^A-Z0-9]+/', ' ', $text);
        $text = trim((string) $text);

        return in_array($text, array(
            'A', 'B', 'C', 'D', 'E',
            'OPTION A', 'OPTION B', 'OPTION C', 'OPTION D', 'OPTION E'
        ), true);
    }
}

if (!function_exists('dlhsValidateObjectiveQuestionEntry')) {
    function dlhsValidateObjectiveQuestionEntry(array $payload)
    {
        $optionCount = isset($payload['optionCount']) && (int) $payload['optionCount'] === 5 ? 5 : 4;

        $requiredFields = array(
            'question' => 'Enter the question text.',
            'optionA' => 'Enter option A.',
            'optionB' => 'Enter option B.',
            'optionC' => 'Enter option C.',
            'optionD' => 'Enter option D.'
        );

        if ($optionCount === 5) {
            $requiredFields['optionE'] = 'Enter option E.';
        }

        foreach ($requiredFields as $field => $message) {
            if (!isset($payload[$field]) || !dlhsEditorHtmlHasContent($payload[$field])) {
                return array(
                    'valid' => false,
                    'code' => 'missing_field',
                    'message' => $message
                );
            }
        }

        $optionFields = array('optionA', 'optionB', 'optionC', 'optionD');
        if ($optionCount === 5) {
            $optionFields[] = 'optionE';
        }

        foreach ($optionFields as $field) {
            if (dlhsOptionLooksLikePlaceholder($payload[$field])) {
                return array(
                    'valid' => false,
                    'code' => 'placeholder_option',
                    'message' => $optionCount === 5
                        ? 'Enter the real option text in A-E. Do not type only option letters.'
                        : 'Enter the real option text in A-D. Do not type only option letters.'
                );
            }
        }

        return array(
            'valid' => true,
            'code' => 'ok',
            'message' => ''
        );
    }
}

if (!function_exists('dlhsFetchValidQuestionIdsForTest')) {
    function dlhsFetchValidQuestionIdsForTest($connection, array $testRow)
    {
        $questionIds = array();
        $questionsTableName = isset($testRow['tableName']) ? $testRow['tableName'] : '';

        if (!dlhsTableExists($connection, $questionsTableName)) {
            return $questionIds;
        }

        $tableSql = dlhsEscapeIdentifier($questionsTableName);
        $result = $connection->query("SELECT questionId FROM {$tableSql}");
        if (!$result) {
            return $questionIds;
        }

        while ($row = $result->fetch_assoc()) {
            $questionIds[] = (int) $row['questionId'];
        }

        return $questionIds;
    }
}

if (!function_exists('dlhsFilterQuestionIdsForTest')) {
    function dlhsFilterQuestionIdsForTest($connection, array $testRow, array $questionIds)
    {
        $validIds = dlhsFetchValidQuestionIdsForTest($connection, $testRow);
        if (empty($validIds)) {
            return array();
        }

        $validLookup = array_fill_keys($validIds, true);
        $filtered = array();

        foreach ($questionIds as $questionId) {
            $questionId = (int) $questionId;
            if ($questionId > 0 && isset($validLookup[$questionId])) {
                $filtered[$questionId] = $questionId;
            }
        }

        return array_values($filtered);
    }
}

if (!function_exists('dlhsFetchSharedStimuliForTest')) {
    function dlhsFetchSharedStimuliForTest($connection, $testId, $staffId = 0)
    {
        $testId = (int) $testId;
        $staffId = (int) $staffId;

        if ($testId <= 0 || !dlhsEnsureQuestionStimulusTables($connection)) {
            return array();
        }

        $where = "WHERE testId='{$testId}'";
        if ($staffId > 0) {
            $where .= " AND staffId='{$staffId}'";
        }

        $stimuli = array();
        $result = $connection->query(
            "SELECT stimulusId, stimulusType, stimulusTitle, stimulusContent
             FROM question_shared_stimuli
             {$where}
             ORDER BY updatedAt DESC, stimulusId DESC"
        );

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $stimulusId = (int) $row['stimulusId'];
                $stimuli[$stimulusId] = array(
                    'stimulusId' => $stimulusId,
                    'stimulusType' => dlhsNormalizeStimulusType(isset($row['stimulusType']) ? $row['stimulusType'] : ''),
                    'stimulusTitle' => html_entity_decode((string) $row['stimulusTitle'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'stimulusContent' => html_entity_decode((string) $row['stimulusContent'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'questionIds' => array()
                );
            }
        }

        if (empty($stimuli)) {
            return array();
        }

        $linksResult = $connection->query(
            "SELECT stimulusId, questionId
             FROM question_shared_stimulus_links
             WHERE testId='{$testId}'"
        );

        if ($linksResult) {
            while ($linkRow = $linksResult->fetch_assoc()) {
                $stimulusId = (int) $linkRow['stimulusId'];
                if (isset($stimuli[$stimulusId])) {
                    $stimuli[$stimulusId]['questionIds'][] = (int) $linkRow['questionId'];
                }
            }
        }

        foreach ($stimuli as &$stimulus) {
            $stimulus['questionIds'] = array_values(array_unique(array_map('intval', $stimulus['questionIds'])));
            sort($stimulus['questionIds']);
            $title = trim((string) $stimulus['stimulusTitle']);
            $stimulus['stimulusLabel'] = trim(($title !== '' ? $title : 'Shared material') . ' (' . $stimulus['stimulusType'] . ')');
        }
        unset($stimulus);

        return array_values($stimuli);
    }
}

if (!function_exists('dlhsFetchSharedStimulusMapForQuestions')) {
    function dlhsFetchSharedStimulusMapForQuestions($connection, $testId, array $questionIds)
    {
        $map = array();
        $testId = (int) $testId;
        $questionIds = array_values(array_filter(array_map('intval', $questionIds)));

        if ($testId <= 0 || empty($questionIds) || !dlhsEnsureQuestionStimulusTables($connection)) {
            return $map;
        }

        $idList = implode(',', $questionIds);
        $query = "
            SELECT l.questionId, s.stimulusId, s.stimulusType, s.stimulusTitle, s.stimulusContent
            FROM question_shared_stimulus_links l
            INNER JOIN question_shared_stimuli s
                ON s.stimulusId = l.stimulusId
            WHERE l.testId = '{$testId}'
              AND l.questionId IN ({$idList})
        ";

        $result = $connection->query($query);
        if (!$result) {
            return $map;
        }

        while ($row = $result->fetch_assoc()) {
            $questionId = (int) $row['questionId'];
            $stimulusType = dlhsNormalizeStimulusType(isset($row['stimulusType']) ? $row['stimulusType'] : '');
            $stimulusTitle = html_entity_decode((string) $row['stimulusTitle'], ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $map[$questionId] = array(
                'stimulusId' => (int) $row['stimulusId'],
                'stimulusType' => $stimulusType,
                'stimulusTitle' => $stimulusTitle,
                'stimulusContent' => html_entity_decode((string) $row['stimulusContent'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'stimulusLabel' => trim(($stimulusTitle !== '' ? $stimulusTitle : 'Shared material') . ' (' . $stimulusType . ')')
            );
        }

        return $map;
    }
}

if (!function_exists('dlhsAssignStimulusToQuestion')) {
    function dlhsAssignStimulusToQuestion($connection, $testId, $questionId, $stimulusId)
    {
        $testId = (int) $testId;
        $questionId = (int) $questionId;
        $stimulusId = (int) $stimulusId;

        if ($testId <= 0 || $questionId <= 0 || !dlhsEnsureQuestionStimulusTables($connection)) {
            return false;
        }

        $connection->query("DELETE FROM question_shared_stimulus_links WHERE testId='{$testId}' AND questionId='{$questionId}'");

        if ($stimulusId <= 0) {
            return true;
        }

        $exists = $connection->query(
            "SELECT stimulusId
             FROM question_shared_stimuli
             WHERE stimulusId='{$stimulusId}'
               AND testId='{$testId}'
             LIMIT 1"
        );

        if (!$exists || $exists->num_rows < 1) {
            return false;
        }

        return (bool) $connection->query(
            "INSERT INTO question_shared_stimulus_links (testId, questionId, stimulusId)
             VALUES ('{$testId}', '{$questionId}', '{$stimulusId}')"
        );
    }
}

if (!function_exists('dlhsReplaceStimulusQuestionLinks')) {
    function dlhsReplaceStimulusQuestionLinks($connection, array $testRow, $stimulusId, array $questionIds)
    {
        $testId = isset($testRow['testId']) ? (int) $testRow['testId'] : 0;
        $stimulusId = (int) $stimulusId;

        if ($testId <= 0 || $stimulusId <= 0 || !dlhsEnsureQuestionStimulusTables($connection)) {
            return false;
        }

        $questionIds = dlhsFilterQuestionIdsForTest($connection, $testRow, $questionIds);

        if (!$connection->query("DELETE FROM question_shared_stimulus_links WHERE testId='{$testId}' AND stimulusId='{$stimulusId}'")) {
            return false;
        }

        foreach ($questionIds as $questionId) {
            $questionId = (int) $questionId;
            if ($questionId <= 0) {
                continue;
            }

            if (!$connection->query("DELETE FROM question_shared_stimulus_links WHERE testId='{$testId}' AND questionId='{$questionId}'")) {
                return false;
            }

            if (!$connection->query(
                "INSERT INTO question_shared_stimulus_links (testId, questionId, stimulusId)
                 VALUES ('{$testId}', '{$questionId}', '{$stimulusId}')"
            )) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('dlhsDeleteStimulusLinksForQuestions')) {
    function dlhsDeleteStimulusLinksForQuestions($connection, $testId, array $questionIds)
    {
        $testId = (int) $testId;
        $questionIds = array_values(array_filter(array_map('intval', $questionIds)));

        if ($testId <= 0 || empty($questionIds) || !dlhsEnsureQuestionStimulusTables($connection)) {
            return true;
        }

        $idsList = implode(',', $questionIds);

        return (bool) $connection->query(
            "DELETE FROM question_shared_stimulus_links
             WHERE testId='{$testId}'
               AND questionId IN ({$idsList})"
        );
    }
}
