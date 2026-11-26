<?php
session_start();
require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn'])) {
    header('location:../index.php');
    exit;
}

include "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/password_security_helper.php";

if (isset($_POST['yearGroup4'])) {
    $yearGroupId = mysqli_real_escape_string($connection, $_POST['yearGroup4']);
    $classId = mysqli_real_escape_string($connection, $_POST['className4']);

    $csvMimes = array(
        'text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream',
        'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv',
        'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain'
    );

    $fileExt = strtolower(pathinfo($_FILES['file4']['name'], PATHINFO_EXTENSION));

    // Validate whether selected file is a CSV file
    if (!empty($_FILES['file4']['name']) && (in_array($_FILES['file4']['type'], $csvMimes) || $fileExt === 'csv')) {
        if (is_uploaded_file($_FILES['file4']['tmp_name'])) {
            $content = file_get_contents($_FILES['file4']['tmp_name']);
            if ($content === false || strlen(trim($content)) === 0) {
                header("location:addStudentForm.php?msgStatus3=2");
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

            $csvFile = fopen($_FILES['file4']['tmp_name'], 'r');

            // Find header row
            $header = false;
            while (($row = fgetcsv($csvFile, 10000, $delimiter, '"', "\\")) !== FALSE) {
                if ($row === NULL || count($row) === 0) continue;
                $rowStr = strtolower(implode(' ', $row));
                if (strpos($rowStr, 'surname') !== false || strpos($rowStr, 'firstname') !== false || strpos($rowStr, 'admission') !== false) {
                    $header = $row;
                    break;
                }
                if (count($row) >= 5 && !is_numeric($row[0])) {
                    $header = $row;
                    break;
                }
            }

            $colMap = array(
                'surname' => -1,
                'firstName' => -1,
                'middleName' => -1,
                'gender' => -1,
                'admissionNumber' => -1,
                'studentEmail' => -1,
                'password' => -1
            );

            if ($header) {
                foreach ($header as $c => $hVal) {
                    $norm = preg_replace('/[^a-z0-9]/', '', strtolower(trim((string)$hVal)));
                    if (in_array($norm, array('surname', 'lastname', 'sname'), true)) {
                        $colMap['surname'] = $c;
                    } elseif (in_array($norm, array('firstname', 'fname'), true)) {
                        $colMap['firstName'] = $c;
                    } elseif (in_array($norm, array('middlename', 'mname', 'othername'), true)) {
                        $colMap['middleName'] = $c;
                    } elseif (in_array($norm, array('gender', 'sex'), true)) {
                        $colMap['gender'] = $c;
                    } elseif (in_array($norm, array('admissionnumber', 'admissionno', 'admno', 'studentno', 'regno', 'regnumber'), true)) {
                        $colMap['admissionNumber'] = $c;
                    } elseif (in_array($norm, array('studentemail', 'email', 'emailaddress'), true)) {
                        $colMap['studentEmail'] = $c;
                    } elseif (in_array($norm, array('password', 'pass', 'pwd'), true)) {
                        $colMap['password'] = $c;
                    }
                }
            }

            // Positional fallback
            if ($colMap['surname'] === -1 || $colMap['firstName'] === -1) {
                $colMap['surname'] = 0;
                $colMap['firstName'] = 1;
                $colMap['middleName'] = 2;
                $colMap['gender'] = 3;
                $colMap['admissionNumber'] = 4;
                $colMap['studentEmail'] = 5;
                $colMap['password'] = 6;
            }

            $studentsRecordsArray = array();
            $oneRequiredFieldEmptyOrNot = 0;

            while (($line = fgetcsv($csvFile, 10000, $delimiter, '"', "\\")) !== FALSE) {
                if ($line === NULL || count($line) === 0) continue;
                $allEmpty = true;
                foreach ($line as $cell) {
                    if (trim((string)$cell) !== '') { $allEmpty = false; break; }
                }
                if ($allEmpty) continue;

                $rawSurname = $colMap['surname'] !== -1 && isset($line[$colMap['surname']]) ? trim((string)$line[$colMap['surname']]) : '';
                $rawFirstName = $colMap['firstName'] !== -1 && isset($line[$colMap['firstName']]) ? trim((string)$line[$colMap['firstName']]) : '';
                $rawMiddleName = $colMap['middleName'] !== -1 && isset($line[$colMap['middleName']]) ? trim((string)$line[$colMap['middleName']]) : '';
                $rawGender = $colMap['gender'] !== -1 && isset($line[$colMap['gender']]) ? trim((string)$line[$colMap['gender']]) : '';
                $rawAdm = $colMap['admissionNumber'] !== -1 && isset($line[$colMap['admissionNumber']]) ? trim((string)$line[$colMap['admissionNumber']]) : '';
                $rawEmail = $colMap['studentEmail'] !== -1 && isset($line[$colMap['studentEmail']]) ? trim((string)$line[$colMap['studentEmail']]) : '';
                $rawPass = $colMap['password'] !== -1 && isset($line[$colMap['password']]) ? trim((string)$line[$colMap['password']]) : '';

                if ($rawSurname === '' && $rawFirstName === '' && $rawAdm === '') continue;

                if ($rawSurname === '' || $rawFirstName === '' || $rawGender === '' || $rawAdm === '') {
                    $oneRequiredFieldEmptyOrNot = 1;
                    continue;
                }

                if ($rawEmail === '') {
                    $rawEmail = strtolower($rawAdm) . '@dlhs.edu.ng';
                }
                if ($rawPass === '') {
                    $rawPass = 'password123';
                }

                $surname = htmlspecialchars(ucfirst(strtolower(mysqli_real_escape_string($connection, $rawSurname))));
                $firstName = htmlspecialchars(ucfirst(strtolower(mysqli_real_escape_string($connection, $rawFirstName))));
                $middleName = htmlspecialchars(ucfirst(strtolower(mysqli_real_escape_string($connection, $rawMiddleName))));
                $gender = htmlspecialchars(ucfirst(strtolower(mysqli_real_escape_string($connection, $rawGender))));
                $admissionNumber = htmlspecialchars(mysqli_real_escape_string($connection, $rawAdm));
                $studentEmail = htmlspecialchars(mysqli_real_escape_string($connection, $rawEmail));
                $password = mysqli_real_escape_string($connection, dlhs_hash_password($rawPass));

                $studentsRecordsArray[] = array(
                    "surname" => $surname,
                    "firstName" => $firstName,
                    "middleName" => $middleName,
                    "gender" => $gender,
                    "admissionNumber" => $admissionNumber,
                    "studentEmail" => $studentEmail,
                    "password" => $password
                );
            }
            fclose($csvFile);

            if (count($studentsRecordsArray) > 0) {
                $updatedOrNotStatus = 0;

                foreach ($studentsRecordsArray as $value) {
                    $theSurname = $value['surname'];
                    $theFirstName = $value['firstName'];
                    $theMiddleName = $value['middleName'];
                    $theGender = $value['gender'];
                    $theAdmissionNumber = $value['admissionNumber'];
                    $theStudentEmail = $value['studentEmail'];
                    $thePassword = $value['password'];

                    $checkForAlreadyInserted = "SELECT * FROM studentlogin WHERE admissionNumber='$theAdmissionNumber'";
                    $result3 = $connection->query($checkForAlreadyInserted);
                    if ($result3 && ($result3->num_rows) > 0) {
                        $updateQuery = "UPDATE studentlogin SET surname='$theSurname', firstName='$theFirstName', middleName='$theMiddleName', gender='$theGender', studentEmail='$theStudentEmail', password='$thePassword', yearGroupId='$yearGroupId', classId='$classId' WHERE admissionNumber='$theAdmissionNumber'";
                        $connection->query($updateQuery);
                        $updatedOrNotStatus = 1;
                    } else {
                        $insertQuery = "INSERT INTO studentlogin(surname, firstName, middleName, gender, admissionNumber, studentEmail, password, yearGroupId, classId, passport, status, houseMasterId, sportMasterId) VALUES('{$theSurname}', '{$theFirstName}', '{$theMiddleName}', '{$theGender}', '{$theAdmissionNumber}', '{$theStudentEmail}', '{$thePassword}', '{$yearGroupId}', '{$classId}', '', 1, 1, 1)";
                        $connection->query($insertQuery);
                    }
                }

                if ($updatedOrNotStatus == 1) {
                    header("location:addStudentForm.php?msgStatus3=11");
                } else {
                    header("location:addStudentForm.php?msgStatus3=1");
                }
                exit;
            } else {
                header("location:addStudentForm.php?msgStatus3=2");
                exit;
            }
        } else {
            header("location:addStudentForm.php?msgStatus3=2");
            exit;
        }
    } else {
        header("location:addStudentForm.php?msgStatus3=3");
        exit;
    }
} else {
    header('location:../index.php');
    exit;
}
