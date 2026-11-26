<?php
error_reporting(0);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'userExpiredSession.php';

if (!isset($_SESSION['staffLoggedIn']) && !isset($_SESSION['adminLoggedIn'])) {
    header('location:../index.php');
    exit;
}

include "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/test_workflow_helper.php";
require_once "../../scripts/question_authoring_helper.php";

if (isset($_POST['testName'])) {
    $testId = (int) $_POST['testName'];
    $qstring = "";

    $testRow = dlhsFetchTestRowById($connection, $testId);
    if (!$testRow) {
        header("location:addQuestionForm.php?status=err");
        exit;
    }

    $staffId = isset($_SESSION['staffId']) ? (int) $_SESSION['staffId'] : 0;
    $isAdmin = isset($_SESSION['adminLoggedIn']) && $_SESSION['adminLoggedIn'] === 'yes' && empty($_SESSION['staffLoggedIn']);
    if (($staffId > 0 && (int)$testRow['staffId'] !== $staffId) || ($staffId === 0 && !$isAdmin)) {
        header("location:addQuestionForm.php?status=err");
        exit;
    }

    $questionEntryCheck = dlhsCheckQuestionEntryAllowed($connection, $testRow);
    if (!$questionEntryCheck['allowed']) {
        header("location:addQuestionForm.php?status=students_required");
        exit;
    }

    // Allowed mime types
    $csvMimes = array(
        'text/x-comma-separated-values', 
        'text/comma-separated-values', 
        'application/octet-stream', 
        'application/vnd.ms-excel', 
        'application/x-csv', 
        'text/x-csv', 
        'text/csv', 
        'application/csv', 
        'application/excel', 
        'application/vnd.msexcel', 
        'text/plain'
    );

    $fileExtension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

    // Validate whether selected file is a CSV file
    if (!empty($_FILES['file']['name']) && (in_array($_FILES['file']['type'], $csvMimes) || $fileExtension === 'csv')) {
        if (is_uploaded_file($_FILES['file']['tmp_name'])) {
            $questionsTableName = $testRow['tableName'];
            if (empty($questionsTableName)) {
                header("location:addQuestionForm.php?status=err");
                exit;
            }
            $questionsTableSql = dlhsEscapeIdentifier($questionsTableName);

            // Read the file and detect delimiter
            $content = file_get_contents($_FILES['file']['tmp_name']);
            if ($content === false || strlen(trim($content)) === 0) {
                header("location:addQuestionForm.php?status=invalid_file");
                exit;
            }

            // Remove UTF-8 BOM
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

            // Auto-detect delimiter
            $firstLine = strtok($content, "\r\n");
            $delimiter = ',';
            if (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
                $delimiter = ';';
            } elseif (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) {
                $delimiter = "\t";
            }

            $csvFile = fopen($_FILES['file']['tmp_name'], 'r');

            // Find the header row (skip PHP notices, blank lines, or file metadata)
            $header = false;
            $headerRowFound = false;
            while (($row = fgetcsv($csvFile, 10000, $delimiter, '"', "\\")) !== FALSE) {
                if ($row === NULL || count($row) === 0) continue;
                $rowStr = strtolower(implode(' ', $row));
                if (strpos($rowStr, 'php ') === 0 || strpos($rowStr, 'deprecated') !== false) continue;
                if (strpos($rowStr, 'question') !== false || (strpos($rowStr, 'option') !== false && (strpos($rowStr, 'correct') !== false || strpos($rowStr, 'answer') !== false))) {
                    $header = $row;
                    $headerRowFound = true;
                    break;
                }
                // If it looks like a header with multiple columns
                if (count($row) >= 5 && !is_numeric($row[0])) {
                    $header = $row;
                    $headerRowFound = true;
                    break;
                }
            }

            if ($header === FALSE || count($header) < 5) {
                fclose($csvFile);
                header("location:addQuestionForm.php?status=missing_columns");
                exit;
            }

            // Flexible column mapping
            $colMap = array(
                'serial' => -1,
                'question' => -1,
                'optA' => -1,
                'optB' => -1,
                'optC' => -1,
                'optD' => -1,
                'optE' => -1,
                'correct' => -1,
                'mark' => -1
            );

            foreach ($header as $c => $hVal) {
                $norm = preg_replace('/[^a-z0-9]/', '', strtolower(trim((string)$hVal)));
                if (in_array($norm, array('questionserialno', 'serialno', 'sno', 'sn', 'no', 'id'), true)) {
                    $colMap['serial'] = $c;
                } elseif (in_array($norm, array('question', 'questiontext', 'quest', 'q'), true)) {
                    $colMap['question'] = $c;
                } elseif (in_array($norm, array('optiona', 'opta', 'a'), true)) {
                    $colMap['optA'] = $c;
                } elseif (in_array($norm, array('optionb', 'optb', 'b'), true)) {
                    $colMap['optB'] = $c;
                } elseif (in_array($norm, array('optionc', 'optc', 'c'), true)) {
                    $colMap['optC'] = $c;
                } elseif (in_array($norm, array('optiond', 'optd', 'd'), true)) {
                    $colMap['optD'] = $c;
                } elseif (in_array($norm, array('optione', 'opte', 'e'), true)) {
                    $colMap['optE'] = $c;
                } elseif (in_array($norm, array('correctoption', 'correct', 'answer', 'ans', 'key', 'correctanswer'), true)) {
                    $colMap['correct'] = $c;
                } elseif (in_array($norm, array('markforquestion', 'mark', 'marks', 'score', 'points'), true)) {
                    $colMap['mark'] = $c;
                }
            }

            // Fallback to positional mapping if named headers weren't matched
            if ($colMap['question'] === -1 || $colMap['optA'] === -1) {
                if (count($header) >= 8) {
                    $colMap['serial'] = 0;
                    $colMap['question'] = 1;
                    $colMap['optA'] = 2;
                    $colMap['optB'] = 3;
                    $colMap['optC'] = 4;
                    $colMap['optD'] = 5;
                    if (count($header) >= 9) {
                        $colMap['optE'] = 6;
                        $colMap['correct'] = 7;
                        $colMap['mark'] = 8;
                    } else {
                        $colMap['correct'] = 6;
                        $colMap['mark'] = 7;
                    }
                } else {
                    $colMap['question'] = 0;
                    $colMap['optA'] = 1;
                    $colMap['optB'] = 2;
                    $colMap['optC'] = 3;
                    $colMap['optD'] = 4;
                    $colMap['correct'] = 5;
                    $colMap['mark'] = 6;
                }
            }

            $holdUpdateSuccess = 0;
            $holdNewInsertionSuccess = 0;
            $autoSerialCounter = 1;

            // Fetch max serial number if needed
            $maxSnoRes = $connection->query("SELECT MAX(CAST(questionSeriaNo AS UNSIGNED)) as maxSno FROM {$questionsTableSql}");
            if ($maxSnoRes && $sRow = $maxSnoRes->fetch_assoc()) {
                $autoSerialCounter = ((int)$sRow['maxSno']) + 1;
            }

            // Parse data from CSV file line by line
            while (($line = fgetcsv($csvFile, 10000, $delimiter, '"', "\\")) !== FALSE) {
                if ($line === NULL || count($line) === 0) continue;
                $allEmpty = true;
                foreach ($line as $cell) {
                    if (trim((string)$cell) !== '') { $allEmpty = false; break; }
                }
                if ($allEmpty) continue;

                $rawQuestion = $colMap['question'] !== -1 && isset($line[$colMap['question']]) ? trim((string)$line[$colMap['question']]) : '';
                if ($rawQuestion === '') continue;

                $qSerial = $colMap['serial'] !== -1 && isset($line[$colMap['serial']]) ? trim((string)$line[$colMap['serial']]) : '';
                if ($qSerial === '') {
                    $qSerial = (string) $autoSerialCounter++;
                }

                $rawOptA = $colMap['optA'] !== -1 && isset($line[$colMap['optA']]) ? trim((string)$line[$colMap['optA']]) : '';
                $rawOptB = $colMap['optB'] !== -1 && isset($line[$colMap['optB']]) ? trim((string)$line[$colMap['optB']]) : '';
                $rawOptC = $colMap['optC'] !== -1 && isset($line[$colMap['optC']]) ? trim((string)$line[$colMap['optC']]) : '';
                $rawOptD = $colMap['optD'] !== -1 && isset($line[$colMap['optD']]) ? trim((string)$line[$colMap['optD']]) : '';
                $rawOptE = $colMap['optE'] !== -1 && isset($line[$colMap['optE']]) ? trim((string)$line[$colMap['optE']]) : '';
                
                $rawCorrect = $colMap['correct'] !== -1 && isset($line[$colMap['correct']]) ? strtoupper(trim((string)$line[$colMap['correct']])) : 'A';
                if (preg_match('/^[A-E]/', $rawCorrect, $cMatch)) {
                    $rawCorrect = $cMatch[0];
                } else {
                    $rawCorrect = 'A';
                }

                $rawMark = $colMap['mark'] !== -1 && isset($line[$colMap['mark']]) ? trim((string)$line[$colMap['mark']]) : '1';
                if ($rawMark === '' || !is_numeric($rawMark)) $rawMark = '1';

                $questionSerialNo = mysqli_real_escape_string($connection, $qSerial);
                $question = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($rawQuestion));
                $optionA = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($rawOptA));
                $optionB = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($rawOptB));
                $optionC = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($rawOptC));
                $optionD = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($rawOptD));
                $optionE = mysqli_real_escape_string($connection, dlhsEncodeHtmlForStorage($rawOptE));
                $correctOption = mysqli_real_escape_string($connection, $rawCorrect);
                $markForQuestion = mysqli_real_escape_string($connection, $rawMark);

                // Checking if this record has already been uploaded before
                $checkIfAlreadyUploaded = "SELECT * FROM {$questionsTableSql} WHERE questionSeriaNo='$questionSerialNo'";
                $result1 = $connection->query($checkIfAlreadyUploaded);
                if ($result1 && ($result1->num_rows) > 0) {
                    $updateRecord = "UPDATE {$questionsTableSql} SET question='$question', optionA='$optionA', optionB='$optionB', optionC='$optionC', optionD='$optionD', optionE='$optionE', correctOption='$correctOption', markForQuestion='$markForQuestion' WHERE questionSeriaNo='$questionSerialNo'";
                    $result2 = $connection->query($updateRecord);
                    if ($result2) {
                        $holdUpdateSuccess = 1;
                    }
                } else {
                    $query2 = "INSERT INTO {$questionsTableSql}(question, optionA, optionB, optionC, optionD, optionE, correctOption, markForQuestion, solvedSolution, questionSeriaNo) VALUES('{$question}', '{$optionA}', '{$optionB}', '{$optionC}', '{$optionD}', '{$optionE}', '{$correctOption}', '{$markForQuestion}', '', '{$questionSerialNo}')";
                    $result2 = $connection->query($query2);
                    if ($result2) {
                        $holdNewInsertionSuccess = 1;
                    }
                }
            }

            fclose($csvFile);

            if (($holdUpdateSuccess == 1) && ($holdNewInsertionSuccess == 1)) {
                $qstring = '?status=succ_update';
            } elseif (($holdUpdateSuccess == 0) && ($holdNewInsertionSuccess == 1)) {
                $qstring = '?status=succ_insert';
            } elseif (($holdUpdateSuccess == 1) && ($holdNewInsertionSuccess == 0)) {
                $qstring = '?status=succ_update';
            } elseif (($holdUpdateSuccess == 0) && ($holdNewInsertionSuccess == 0)) {
                $qstring = '?status=err';
            }
        } else {
            $qstring = '?status=err';
        }
    } else {
        $qstring = '?status=invalid_file';
    }

    header("location:addQuestionForm.php".$qstring);
    exit;
}
