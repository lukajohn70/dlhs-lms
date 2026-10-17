<?php
session_start();
require_once 'userExpiredSession.php';

if (!isset($_SESSION['staffLoggedIn'])) {
    header('location:../index.php');
    exit;
}

header('location:examineesStatus.php');
exit;
?>
