<?php
session_start();
if (!isset($_SESSION['adminLoggedIn'])) {
    echo 0;
    exit;
}

if (isset($_POST['password1'])) {
    require_once "../../scripts/password_policy.php";
    
    $passwordRaw = (string) $_POST['password1'];
    $password2Raw = (string) $_POST['password2'];

    if ($passwordRaw !== $password2Raw) {
        echo "3|Passwords do not match.";
        exit;
    }

    $policyMessage = dlhsPasswordPolicyMessage($passwordRaw, 'staff'); // Use staff policy for admin
    if ($policyMessage !== '') {
        echo "3|" . $policyMessage;
        exit;
    }

    include "../../db_connection/dlhs_db_connection.php";
    $password1Escaped = mysqli_real_escape_string($connection, $passwordRaw);
    $adminId = $_SESSION['adminId'];

    // If adminId is 'env-admin', we can't update it in the DB
    if ($adminId === 'env-admin') {
        echo "3|Environment-based admin password cannot be changed through this panel.";
        exit;
    }

    $query = "UPDATE admintable SET password='$password1Escaped' WHERE id='$adminId'";
    $result = $connection->query($query);
    if (!$result) {
        echo "3|" . $connection->error;
        exit;
    }

    if ($connection->affected_rows >= 0) {
        $_SESSION['forcePasswordChange'] = 0;
        echo 1;
    } else {
        echo 2;
    }
} else {
    echo 0;
}
?>
