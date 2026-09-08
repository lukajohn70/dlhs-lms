<?php
session_start();

require_once 'userExpiredSession.php';
if (!isset($_SESSION['adminLoggedIn'])) {
    header('location:../index.php');
    exit;
}

require_once '../../track_user_presence.php';
require_once '../../db_connection/dlhs_db_connection.php';
require_once '../../scripts/dashboard_theme_helper.php';

$sessionLabel = dlhsDashboardGetCurrentSessionLabel($connection);
$userName = dlhsDashboardGetUserName('admin');

$counts = dlhsDashboardCountAllTests($connection);

$upcomingTests = dlhsDashboardFetchUpcomingTests($connection, null, 6);
$recentAssignments = dlhsDashboardFetchRecentAssignments($connection, 5);

dlhsRenderDashboardPage(array(
    'role' => 'admin',
    'title' => 'Admin | DLHS',
    'pageHeading' => 'Operations Command Centre',
    'subHeading' => 'See school-wide testing activity at a glance, support teachers quickly, and manage assessment operations from a cleaner admin dashboard.',
    'sessionLabel' => $sessionLabel,
    'userName' => $userName,
    'userRoleLabel' => 'Admin Portal',
    'counts' => $counts,
    'upcomingTests' => $upcomingTests,
    'recentAssignments' => $recentAssignments,
    'navItems' => dlhsDashboardGetNavItems('admin'),
    'quickActions' => dlhsDashboardGetQuickActions('admin'),
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
