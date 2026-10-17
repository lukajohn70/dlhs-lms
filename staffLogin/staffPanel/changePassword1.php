<?php
session_start();
require_once 'sessionTime.php';

if (!isset($_SESSION['staffLast_login']) || (time() - $_SESSION['staffLast_login']) > $allottedTime) {	
    require_once 'unsetSessions.php';
    echo 0;
    exit;
}

if (isset($_POST['password1'])) {    
    require_once "../../scripts/password_policy.php";
    require_once "../../db_connection/dlhs_db_connection.php";
    
    $passwordRaw = isset($_POST['password1']) ? (string)$_POST['password1'] : '';
    $password2Raw = isset($_POST['password2']) ? (string)$_POST['password2'] : '';

    if ($passwordRaw !== $password2Raw) {
        echo "3|Passwords do not match.";
        exit;
    }

    $policyMessage = dlhsPasswordPolicyMessage($passwordRaw, 'staff');
    if ($policyMessage !== '') {
        echo "3|" . $policyMessage;
        exit;
    }

    $hashedPassword = password_hash($passwordRaw, PASSWORD_DEFAULT);
    $staffId = (int)$_SESSION['staffId'];
                        
    $stmt = $connection->prepare("UPDATE stafflogin SET password = ? WHERE staffId = ?");
    if (!$stmt) {
        die($connection->error);
    }
    $stmt->bind_param("si", $hashedPassword, $staffId);

    if ($stmt->execute()) {
        $_SESSION['forcePasswordChange'] = 0;
        echo 1;
    } else {		
        echo 2;
    }
    $stmt->close();
} else {
    echo 0;
}
?>
