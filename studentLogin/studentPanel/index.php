<?php
session_start();

require_once 'userExpiredSession.php';
if (!isset($_SESSION['studentLoggedIn'])) {
    header('location:../index.php');
    exit;
}

require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/dashboard_theme_helper.php";

if (!function_exists('dlhsStudentDashboardFetchSummary')) {
    function dlhsStudentDashboardFetchSummary($connection, $studentId, $studentYearGroup, $limit = 6)
    {
        $studentId = (int) $studentId;
        $studentYearGroup = $connection->real_escape_string((string) $studentYearGroup);
        $limit = max(1, (int) $limit);

        $summary = array(
            'counts' => array(
                'pending' => 0,
                'inProgress' => 0,
                'completed' => 0,
            ),
            'upcomingTests' => array(),
        );

        $query = "SELECT testId, testName, testDate, subject, yearGroup, testType, customTestType, mockPaperLabel, examineesTableName
                  FROM tests
                  WHERE yearGroup = '{$studentYearGroup}'
                  ORDER BY (testDate < CURDATE()) ASC, testDate ASC, testName ASC";
        $result = $connection->query($query);
        if (!$result) {
            return $summary;
        }

        while ($row = $result->fetch_assoc()) {
            $testId = isset($row['testId']) ? (int) $row['testId'] : 0;
            $examineesTableName = isset($row['examineesTableName']) ? trim((string) $row['examineesTableName']) : '';
            if ($testId <= 0 || $examineesTableName === '' || !preg_match('/^[A-Za-z0-9_]+$/', $examineesTableName)) {
                continue;
            }

            $statusQuery = "SELECT testStatus FROM `{$examineesTableName}` WHERE testId = {$testId} AND examineeUserId = {$studentId} LIMIT 1";
            $statusResult = $connection->query($statusQuery);
            if (!$statusResult || $statusResult->num_rows === 0) {
                continue;
            }

            $statusRow = $statusResult->fetch_assoc();
            $studentStatus = isset($statusRow['testStatus']) ? (int) $statusRow['testStatus'] : 0;

            if ($studentStatus === 1) {
                $summary['counts']['inProgress'] += 1;
            } elseif ($studentStatus === 2) {
                $summary['counts']['completed'] += 1;
            } else {
                $summary['counts']['pending'] += 1;
            }

            if ($studentStatus === 2) {
                continue;
            }

            if (count($summary['upcomingTests']) >= $limit) {
                continue;
            }

            $displayType = function_exists('dlhsGetDisplayTestType')
                ? dlhsGetDisplayTestType(
                    isset($row['testType']) ? $row['testType'] : '',
                    isset($row['customTestType']) ? $row['customTestType'] : '',
                    isset($row['mockPaperLabel']) ? $row['mockPaperLabel'] : ''
                )
                : (isset($row['testType']) ? $row['testType'] : 'TEST');

            $summary['upcomingTests'][] = array(
                'testId' => $testId,
                'testName' => isset($row['testName']) ? $row['testName'] : 'Untitled test',
                'subject' => dlhsDashboardResolveSubjectName($connection, isset($row['subject']) ? $row['subject'] : ''),
                'yearGroup' => dlhsDashboardResolveYearGroupName($connection, isset($row['yearGroup']) ? $row['yearGroup'] : ''),
                'status' => $studentStatus,
                'testType' => $displayType,
                'testDate' => isset($row['testDate']) ? $row['testDate'] : '',
            );
        }

        return $summary;
    }
}

$studentId = isset($_SESSION['studentId']) ? (int) $_SESSION['studentId'] : 0;
if ($studentId > 0) {
    $refreshRes = $connection->query("SELECT yearGroupId FROM studentlogin WHERE studentId = {$studentId}");
    if ($refreshRes && $refreshRow = $refreshRes->fetch_assoc()) {
        $_SESSION['studentYearGroup'] = $refreshRow['yearGroupId'];
    }
}
$studentYearGroup = isset($_SESSION['studentYearGroup']) ? $_SESSION['studentYearGroup'] : '';
$studentName = isset($_SESSION['studentName']) && trim((string) $_SESSION['studentName']) !== ''
    ? trim((string) $_SESSION['studentName'])
    : 'Student';
$sessionLabel = dlhsDashboardGetCurrentSessionLabel($connection);
$summary = dlhsStudentDashboardFetchSummary($connection, $studentId, $studentYearGroup, 6);

$navItems = dlhsDashboardGetNavItems('student');

$quickActions = array(
    array('label' => 'Open my tests', 'href' => 'pendingTests.php', 'icon' => 'fa-hourglass-start', 'variant' => 'primary'),
    array('label' => 'Security settings', 'href' => 'changePassword.php', 'icon' => 'fa-lock', 'variant' => 'secondary'),
);

dlhsRenderDashboardPage(array(
    'role' => 'student',
    'title' => 'Student | DLHS',
    'pageHeading' => 'Student Learning Hub',
    'subHeading' => 'Track your assessments, view results, and manage your account from your secure learning portal.',
    'sessionLabel' => $sessionLabel,
    'userName' => $studentName,
    'userRoleLabel' => 'Student Portal',
    'counts' => $summary['counts'],
    'upcomingTests' => $summary['upcomingTests'],
    'navItems' => $navItems,
    'quickActions' => $quickActions,
    'calendarEventsUrl' => 'getDates.php',
    'fetchChatUrl' => '../../dashboard_chat_fetch.php',
    'sendChatUrl' => '../../dashboard_chat_send.php',
    'logoutUrl' => 'logout.php',
    'fontAwesomeCss' => 'css/font-awesome.min.css',
    'calendarCss' => array(
        'assets/fullcalendar/fullcalendar/bootstrap-fullcalendar.css',
        'assets/fullcalendar/fullcalendar/fullcalendar.css',
    ),
    'jqueryJs' => 'js/jquery.js',
    'bootstrapJs' => 'js/bootstrap.min.js',
    'extraScripts' => array(
        'js/fullcalendar.min.js'
    ),
    'fullcalendarJs' => 'assets/fullcalendar/fullcalendar/fullcalendar.js',
));
