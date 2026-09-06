<?php
session_start(); 
require_once dirname(__DIR__) . "/config/database.php";

function dlhs_admin_fallback_login($username, $password)
{
    $fallbackUsername = dlhs_env_first(array('DLHS_ADMIN_USERNAME', 'ADMIN_USERNAME'), '');
    $fallbackPassword = dlhs_env_first(array('DLHS_ADMIN_PASSWORD', 'ADMIN_PASSWORD'), '');
    $fallbackPasswordHash = dlhs_env_first(array('DLHS_ADMIN_PASSWORD_HASH', 'ADMIN_PASSWORD_HASH'), '');

    if ($fallbackUsername === '') {
        return false;
    }

    if (!hash_equals((string) $fallbackUsername, (string) $username)) {
        return false;
    }

    if ($fallbackPasswordHash !== '' && function_exists('password_verify')) {
        return password_verify($password, $fallbackPasswordHash);
    }

    return $fallbackPassword !== '' && hash_equals((string) $fallbackPassword, (string) $password);
}

function dlhs_set_admin_session($adminId, $adminEmail)
{
    $_SESSION['adminLast_login'] = time();
    $_SESSION['adminId'] = $adminId;
    $_SESSION['adminEmail'] = $adminEmail;
    $_SESSION['adminLoggedIn'] = "yes";
}
    
if(isset($_POST['adminUsername']))
{
    $username = trim($_POST['adminUsername']);
    $password = isset($_POST['adminPassword']) ? (string) $_POST['adminPassword'] : '';
    $connectionError = null;
    $connection = dlhs_try_database_connection(false, $connectionError);

    if (!($connection instanceof mysqli)) {
        if (dlhs_admin_fallback_login($username, $password)) {
            dlhs_set_admin_session('env-admin', $username);
            echo 1;
            exit;
        }

        http_response_code(503);
        echo "DB_ERROR";
        exit;
    }
    
    $query = "SELECT * FROM admintable WHERE username=? AND password=? LIMIT 1";
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        http_response_code(500);
        echo "DB_ERROR";
        exit;
    }

    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (($result->num_rows)>0)
    {
        $row = $result->fetch_array(MYSQLI_NUM);
                                
        dlhs_set_admin_session($row[0], $row[1]);
        $_SESSION['forcePasswordChange'] = ($password === '1234' || $password === '4321') ? 1 : 0;
        
        $result->close();
        $stmt->close();
        echo ($_SESSION['forcePasswordChange'] === 1) ? 2 : 1;
    }
    else
    {
        $result->close();
        $stmt->close();
        echo 0;
    }
}
?>
