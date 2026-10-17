<?php
session_start();
if (!isset($_SESSION['adminLoggedIn'])) {
    echo 0;
    exit;
}

if (isset($_POST['password1'])) {
    require_once "../../scripts/password_policy.php";
    require_once "../../db_connection/dlhs_db_connection.php";
    
    $passwordRaw = (string)$_POST['password1'];
    $password2Raw = (string)$_POST['password2'];

    if ($passwordRaw !== $password2Raw) {
        echo "3|Passwords do not match.";
        exit;
    }

    $policyMessage = dlhsPasswordPolicyMessage($passwordRaw, 'staff');
    if ($policyMessage !== '') {
        echo "3|" . $policyMessage;
        exit;
    }

    $adminId = $_SESSION['adminId'];
    if ($adminId === 'env-admin') {
        echo "3|Environment-based admin password cannot be changed through this panel.";
        exit;
    }

    $hashedPassword = password_hash($passwordRaw, PASSWORD_DEFAULT);
    $adminIdInt = (int)$adminId;

    $stmt = $connection->prepare("UPDATE admintable SET password = ? WHERE adminId = ?");
    if (!$stmt) {
        echo "3|" . $connection->error;
        exit;
    }
    $stmt->bind_param("si", $hashedPassword, $adminIdInt);

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
