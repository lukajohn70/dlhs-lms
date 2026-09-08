<?php
session_start();

require_once 'userExpiredSession.php';
if (!isset($_SESSION['studentLoggedIn'])) {
    header('location:../index.php');
    exit;
}

require_once "../../db_connection/dlhs_db_connection.php";
require_once "../../scripts/dashboard_theme_helper.php";

// Optimised student summary: single query per examinee table instead of N+1.
// Groups tests by examinee table, then does one query per unique table (usually 1).
if (!function_exists('dlhsStudentDashboardFetchSummary')) {
    function dlhsStudentDashboardFetchSummary($connection, $studentId, $studentYearGroup, $limit = 6)
    {
        $studentId       = (int) $studentId;
        $studentYearGroup = $connection->real_escape_string((string) $studentYearGroup);
        $limit           = max(1, (int) $limit);

        $summary = array(
            'counts'       => array('pending' => 0, 'inProgress' => 0, 'completed' => 0),
            'upcomingTests' => array(),
        );

        // Fetch all tests for this year group in one query
        $query  = "SELECT testId, testName, testDate, subject, yearGroup, testType,
                          customTestType, mockPaperLabel, examineesTableName
                   FROM tests
                   WHERE yearGroup = '{$studentYearGroup}'
                   ORDER BY (testDate < CURDATE()) ASC, testDate ASC, testName ASC";
        $result = $connection->query($query);
        if (!$result) {
            return $summary;
        }

        $tests = array();
        while ($row = $result->fetch_assoc()) {
            $tests[] = $row;
        }

        // Group tests by examinee table — do one query per unique table
        $byTable = array();
        foreach ($tests as $row) {
            $tbl = isset($row['examineesTableName']) ? trim((string) $row['examineesTableName']) : '';
            if ($tbl === '' || !preg_match('/^[A-Za-z0-9_]+$/', $tbl)) {
                continue;
            }
            $byTable[$tbl][] = $row;
        }

        // Build a status map: testId => testStatus
        $statusMap = array();
        foreach ($byTable as $tbl => $rows) {
            $testIds = array_filter(array_map('intval', array_column($rows, 'testId')));
            if (empty($testIds)) {
                continue;
            }
            $ids = implode(',', $testIds);
            $tblRes = $connection->query(
                "SELECT testId, testStatus FROM `{$tbl}`
                 WHERE testId IN ({$ids}) AND examineeUserId = {$studentId}"
            );
            if ($tblRes) {
                while ($sr = $tblRes->fetch_assoc()) {
                    $statusMap[(int) $sr['testId']] = (int) $sr['testStatus'];
                }
            }
        }

        // Build summary using the pre-fetched status map
        foreach ($tests as $row) {
            $testId   = isset($row['testId']) ? (int) $row['testId'] : 0;
            $tbl      = isset($row['examineesTableName']) ? trim((string) $row['examineesTableName']) : '';
            if ($testId <= 0 || $tbl === '' || !preg_match('/^[A-Za-z0-9_]+$/', $tbl)) {
                continue;
            }

            // Skip tests the student isn't enrolled in
            if (!isset($statusMap[$testId])) {
                continue;
            }

            $studentStatus = $statusMap[$testId];

            if ($studentStatus === 1) {
                $summary['counts']['inProgress'] += 1;
            } elseif ($studentStatus === 2) {
                $summary['counts']['completed']  += 1;
            } else {
                $summary['counts']['pending']     += 1;
            }

            if ($studentStatus === 2 || count($summary['upcomingTests']) >= $limit) {
                continue;
            }

            $displayType = function_exists('dlhsGetDisplayTestType')
                ? dlhsGetDisplayTestType(
                    isset($row['testType'])        ? $row['testType']        : '',
                    isset($row['customTestType'])   ? $row['customTestType']  : '',
                    isset($row['mockPaperLabel'])   ? $row['mockPaperLabel']  : ''
                )
                : (isset($row['testType']) ? $row['testType'] : 'TEST');

            $summary['upcomingTests'][] = array(
                'testId'    => $testId,
                'testName'  => isset($row['testName'])  ? $row['testName']  : 'Untitled test',
                'subject'   => dlhsDashboardResolveSubjectName($connection,  isset($row['subject'])    ? $row['subject']    : ''),
                'yearGroup' => dlhsDashboardResolveYearGroupName($connection, isset($row['yearGroup'])  ? $row['yearGroup']  : ''),
                'status'    => $studentStatus,
                'testType'  => $displayType,
                'testDate'  => isset($row['testDate'])  ? $row['testDate']  : '',
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
$studentName      = isset($_SESSION['studentName']) && trim((string) $_SESSION['studentName']) !== ''
    ? trim((string) $_SESSION['studentName'])
    : 'Student';
$sessionLabel = dlhsDashboardGetCurrentSessionLabel($connection);
$summary      = dlhsStudentDashboardFetchSummary($connection, $studentId, $studentYearGroup, 6);

$navItems     = dlhsDashboardGetNavItems('student');

$quickActions = array(
    array('label' => 'Open my tests',      'href' => 'pendingTests.php',   'icon' => 'fa-hourglass-start', 'variant' => 'primary'),
    array('label' => 'Security settings',  'href' => 'changePassword.php', 'icon' => 'fa-lock',            'variant' => 'secondary'),
);

dlhsRenderDashboardPage(array(
    'role'           => 'student',
    'title'          => 'Student | DLHS',
    'pageHeading'    => 'Student Learning Hub',
    'subHeading'     => 'Track your assessments, view results, and manage your account from your secure learning portal.',
    'sessionLabel'   => $sessionLabel,
    'userName'       => $studentName,
    'userRoleLabel'  => 'Student Portal',
    'counts'         => $summary['counts'],
    'upcomingTests'  => $summary['upcomingTests'],
    'navItems'       => $navItems,
    'quickActions'   => $quickActions,
    'calendarEventsUrl' => 'getDates.php',
    'logoutUrl'      => 'logout.php',
    'fontAwesomeCss' => 'css/font-awesome.min.css',
    'calendarCss'    => array(
        'assets/fullcalendar/fullcalendar/bootstrap-fullcalendar.css',
        'assets/fullcalendar/fullcalendar/fullcalendar.css',
    ),
    'jqueryJs'       => 'js/jquery.js',
    'bootstrapJs'    => 'js/bootstrap.min.js',
    'extraScripts'   => array('js/fullcalendar.min.js'),
    'fullcalendarJs' => 'assets/fullcalendar/fullcalendar/fullcalendar.js',
));
