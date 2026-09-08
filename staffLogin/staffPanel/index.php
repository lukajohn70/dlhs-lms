<?php
session_start();

require_once 'userExpiredSession.php';
if (!isset($_SESSION['staffLoggedIn'])) {
    header('location:../index.php');
    exit;
}

require_once '../../track_user_presence.php';
require_once '../../db_connection/dlhs_db_connection.php';
require_once '../../scripts/dashboard_theme_helper.php';

$staffId = isset($_SESSION['staffId']) ? (int) $_SESSION['staffId'] : 0;
$sessionLabel = dlhsDashboardGetCurrentSessionLabel($connection);
$userName = dlhsDashboardGetUserName('staff');

$counts = dlhsDashboardCountAllTests($connection, $staffId);

$upcomingTests = dlhsDashboardFetchUpcomingTests($connection, $staffId, 6);
$teacherAssignments = dlhsDashboardFetchTeacherAssignments($connection, $staffId, 5);

dlhsRenderDashboardPage(array(
    'role' => 'staff',
    'title' => 'Staff-Home | Deeper Life High School',
    'pageHeading' => 'Distinction Hub',
    'subHeading' => 'Your teaching dashboard now brings together test planning, question management, access control, and live support in one polished workspace.',
    'sessionLabel' => $sessionLabel,
    'userName' => $userName,
    'userRoleLabel' => 'Staff Portal',
    'counts' => $counts,
    'upcomingTests' => $upcomingTests,
    'teacherAssignments' => $teacherAssignments,
    'navItems' => dlhsDashboardGetNavItems('staff'),
    'quickActions' => dlhsDashboardGetQuickActions('staff'),
    'calendarEventsUrl' => 'getDates.php',

    'logoutUrl' => 'logout.php',
    'fontAwesomeCss' => 'css/font-awesome.min.css',
    'calendarCss' => array(
        'assets/fullcalendar/fullcalendar/bootstrap-fullcalendar.css',
        'assets/fullcalendar/fullcalendar/fullcalendar.css',
    ),
    'jqueryJs' => 'js/jquery.js',
    'bootstrapJs' => 'js/bootstrap.min.js',
    'extraScripts' => array(
        'js/fullcalendar.min.js',
    ),
    'fullcalendarJs' => 'assets/fullcalendar/fullcalendar/fullcalendar.js',
));
