<?php
session_start(); 
require_once "../db_connection/dlhs_db_connection.php";
require_once "../scripts/password_security_helper.php";

if (isset($_POST['staffUsername'])) {
    $username = trim((string)$_POST['staffUsername']);
    $password = (string)($_POST['staffPassword'] ?? '');

    if ($username === '' || $password === '') {
        echo 0;
        exit;
    }

    $stmt = $connection->prepare("SELECT * FROM stafflogin WHERE username = ? LIMIT 1");
    if (!$stmt) {
        die(0);
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $staffId = (int)$row['staffId'];
        $storedPassword = $row['password'];

        $isValid = dlhs_verify_and_rehash_password($password, $storedPassword, function($newHash) use ($connection, $staffId) {
            $upd = $connection->prepare("UPDATE stafflogin SET password = ? WHERE staffId = ?");
            if ($upd) {
                $upd->bind_param("si", $newHash, $staffId);
                $upd->execute();
                $upd->close();
            }
        });

        if ($isValid) {
            session_regenerate_id(true);

            // Setting up session for logged in user
            $_SESSION['staffLast_login'] = time();
            $_SESSION['staffId'] = $staffId;
            $_SESSION['staffEmail'] = $row['username'];
            $_SESSION['staffName'] = trim($row['firstName'] . " " . $row['surname']);
            $_SESSION['staffLoggedIn'] = "yes";
            $isDefaultPassword = in_array(strtolower($password), array('4321', '1234', 'password', 'admin'));
            $_SESSION['forcePasswordChange'] = $isDefaultPassword ? 1 : 0;

            if (empty($row['passport'])) {
                $_SESSION['passportNameAndLocation'] = '../../images/dlhslogo3.jpg';
            } else {
                $_SESSION['passportNameAndLocation'] = '../../adminLogin/adminPanel/staffPassports/' . $row['passport'];
            }

            // Log staff login safely
            $finalDateAndTime = date('d-m-Y h:i A');
            $finalDate = date('Y-m-d');
            $finalTime = date('h:i A');
            $nameOfStaff = trim($row['firstName'] . " " . $row['surname']);

            $logStmt = $connection->prepare("INSERT INTO staffloginlog (staffId, username, nameOfStaff, loginTime, loginDate, loginTimeDate) VALUES (?, ?, ?, ?, ?, ?)");
            if ($logStmt) {
                $logStmt->bind_param("isssss", $staffId, $username, $nameOfStaff, $finalTime, $finalDate, $finalDateAndTime);
                $logStmt->execute();
                $logStmt->close();
            }

            echo $isDefaultPassword ? 2 : 1;
        } else {
            echo 0;
        }
    } else {
        echo 0;
    }
    $stmt->close();
} else {
    header("location:../staffLogin/");
}
?>
