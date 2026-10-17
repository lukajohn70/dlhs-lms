<?php
session_start(); 
require_once "../db_connection/dlhs_db_connection.php";
require_once "../scripts/password_security_helper.php";

if (isset($_POST['studentUsername'])) {
    $username = trim((string)$_POST['studentUsername']);
    $password = (string)($_POST['studentPassword'] ?? '');

    if ($username === '' || $password === '') {
        echo 0;
        exit;
    }

    // Use prepared statement to safely check either email or admission number
    $stmt = $connection->prepare("SELECT * FROM studentlogin WHERE studentEmail = ? OR admissionNumber = ? LIMIT 1");
    if (!$stmt) {
        die(0);
    }
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $studentId = (int)$row['studentId'];
        $storedPassword = $row['password'];

        // Verify password with auto-migration from plaintext to bcrypt
        $isValid = dlhs_verify_and_rehash_password($password, $storedPassword, function($newHash) use ($connection, $studentId) {
            $upd = $connection->prepare("UPDATE studentlogin SET password = ? WHERE studentId = ?");
            if ($upd) {
                $upd->bind_param("si", $newHash, $studentId);
                $upd->execute();
                $upd->close();
            }
        });

        if ($isValid) {
            // Prevent session fixation
            session_regenerate_id(true);

            // Setting up session for logged in user
            $_SESSION['studentLast_login'] = time();
            $_SESSION['studentId'] = $studentId;
            $_SESSION['studentEmail'] = $row['studentEmail'];
            $_SESSION['studentYearGroup'] = $row['yearGroupId'];
            $_SESSION['studentName'] = trim($row['firstName'] . " " . $row['surname']);
            $_SESSION['studentLoggedIn'] = "yes";
            $_SESSION['forcePasswordChange'] = ($password === '1234') ? 1 : 0;

            if (empty($row['passport'])) {
                $_SESSION['passportNameAndLocation'] = '../../images/dlhslogo3.jpg';
            } else {
                $_SESSION['passportNameAndLocation'] = '../../adminLogin/adminPanel/studentPassports/' . $row['passport'];
            }

            // Log user login safely with prepared statement
            $finalDateAndTime = date('d-m-Y h:i A');
            $finalDate = date('Y-m-d');
            $finalTime = date('h:i A');
            $usernameOfLoggedInUser = $row['admissionNumber'];
            $nameOfUser = trim($row['firstName'] . " " . $row['surname']);

            $logStmt = $connection->prepare("INSERT INTO studentloginlog (studentId, username, nameOfStudent, loginTime, loginDate, loginTimeDate) VALUES (?, ?, ?, ?, ?, ?)");
            if ($logStmt) {
                $logStmt->bind_param("isssss", $studentId, $usernameOfLoggedInUser, $nameOfUser, $finalTime, $finalDate, $finalDateAndTime);
                $logStmt->execute();
                $logStmt->close();
            }

            echo ($password === '1234') ? 2 : 1;
        } else {
            echo 0;
        }
    } else {
        echo 0;
    }
    $stmt->close();
} else {
    echo 0;
}
?>
