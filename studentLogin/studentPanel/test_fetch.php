<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
$_SESSION['studentLast_login'] = time();
$_SESSION['idOfTest'] = 3; // Use a known testId, or fetch one from db.
$_SESSION['studentId'] = 1;
$_SESSION['questionsIdsArray'] = [1, 2, 3]; // mock array
require_once "fetchAllQuestions.php";