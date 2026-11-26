<?php
require_once __DIR__ . '/test_workflow_helper.php';

if (!function_exists('dlhsDashboardCountTests')) {
    function dlhsDashboardCountTests($connection, $status, $staffId = null)
    {
        $status = (int) $status;
        $where = "status='{$status}'";

        if ($staffId !== null) {
            $where .= " AND staffId='" . (int) $staffId . "'";
        }

        $result = $connection->query("SELECT COUNT(*) AS total FROM tests WHERE {$where}");
        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return isset($row['total']) ? (int) $row['total'] : 0;
    }
}

if (!function_exists('dlhsDashboardCountAllTests')) {
    function dlhsDashboardCountAllTests($connection, $staffId = null)
    {
        $counts = array('pending' => 0, 'inProgress' => 0, 'completed' => 0);
        $where = "status IN (0, 1, 2)";

        if ($staffId !== null) {
            $where .= " AND staffId='" . (int) $staffId . "'";
        }

        $result = $connection->query("SELECT status, COUNT(*) AS total FROM tests WHERE {$where} GROUP BY status");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $st = (int) $row['status'];
                if ($st === 0) {
                    $counts['pending'] = (int) $row['total'];
                } elseif ($st === 1) {
                    $counts['inProgress'] = (int) $row['total'];
                } elseif ($st === 2) {
                    $counts['completed'] = (int) $row['total'];
                }
            }
        }
        return $counts;
    }
}

if (!function_exists('dlhsDashboardFetchUpcomingTests')) {
    function dlhsDashboardFetchUpcomingTests($connection, $staffId = null, $limit = 6)
    {
        $limit = max(1, (int) $limit);
        $where = '1=1';

        if ($staffId !== null) {
            $where .= " AND t.staffId='" . (int) $staffId . "'";
        }

        $query = "SELECT t.testId, t.testName, t.testDate, t.subject, t.yearGroup, t.status, t.testType, t.customTestType, t.mockPaperLabel,
                         s.subjectName, yg.yearGroupName
                  FROM tests t
                  LEFT JOIN subjects s ON s.subjectId = t.subject
                  LEFT JOIN yeargroup yg ON yg.yearGroupId = t.yearGroup
                  WHERE {$where}
                  ORDER BY (t.testDate < CURDATE()) ASC, t.testDate ASC, t.testName ASC
                  LIMIT {$limit}";
        $result = $connection->query($query);

        if (!$result) {
            return array();
        }

        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $displayType = function_exists('dlhsGetDisplayTestType')
                ? dlhsGetDisplayTestType(
                    isset($row['testType']) ? $row['testType'] : '',
                    isset($row['customTestType']) ? $row['customTestType'] : '',
                    isset($row['mockPaperLabel']) ? $row['mockPaperLabel'] : ''
                )
                : (isset($row['testType']) ? $row['testType'] : 'TEST');

            $rows[] = array(
                'testId' => (int) $row['testId'],
                'testName' => isset($row['testName']) ? $row['testName'] : 'Untitled test',
                'subject' => !empty($row['subjectName']) ? $row['subjectName'] : (isset($row['subject']) ? (string)$row['subject'] : ''),
                'yearGroup' => !empty($row['yearGroupName']) ? $row['yearGroupName'] : (isset($row['yearGroup']) ? (string)$row['yearGroup'] : ''),
                'status' => isset($row['status']) ? (int) $row['status'] : 0,
                'testType' => $displayType,
                'testDate' => isset($row['testDate']) ? $row['testDate'] : '',
            );
        }

        return $rows;
    }
}

if (!function_exists('dlhsDashboardFetchRecentAssignments')) {
    function dlhsDashboardFetchRecentAssignments($connection, $limit = 5)
    {
        $limit = max(1, (int) $limit);
        $query = "SELECT sta.assignmentId, yg.yearGroupName, c.className, s.subjectName, 
                         CONCAT(sl.surname, ' ', sl.firstName) as teacherName
                  FROM subject_teacher_assignment sta
                  JOIN classes c ON sta.classId = c.classId
                  JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId
                  JOIN subjects s ON sta.subjectId = s.subjectId
                  JOIN stafflogin sl ON sta.teacherId = sl.staffId
                  ORDER BY sta.assignedDate DESC
                  LIMIT {$limit}";
        $result = $connection->query($query);
        if (!$result) return array();
        
        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
}

if (!function_exists('dlhsDashboardFetchTeacherAssignments')) {
    function dlhsDashboardFetchTeacherAssignments($connection, $staffId, $limit = 5)
    {
        $staffId = (int) $staffId;
        $limit = max(1, (int) $limit);
        $query = "SELECT sta.subjectTeacherAssignmentId as assignmentId, yg.yearGroupName, c.className, s.subjectName
                  FROM subject_teacher_assignment sta
                  JOIN classes c ON sta.classId = c.classId
                  JOIN yeargroup yg ON c.classYearGroup = yg.yearGroupId
                  JOIN subjects s ON sta.subjectId = s.subjectId
                  WHERE sta.teacherId = '{$staffId}'
                  ORDER BY s.subjectName ASC
                  LIMIT {$limit}";
        $result = $connection->query($query);
        if (!$result) return array();
        
        $rows = array();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
}

if (!function_exists('dlhsDashboardResolveSubjectName')) {
    function dlhsDashboardResolveSubjectName($connection, $subjectId)
    {
        static $cache = array();

        $subjectId = (int) $subjectId;
        if ($subjectId <= 0) {
            return '';
        }

        if (isset($cache[$subjectId])) {
            return $cache[$subjectId];
        }

        $subjectName = function_exists('dlhsFetchScalarValue')
            ? dlhsFetchScalarValue(
                $connection,
                "SELECT subjectName FROM subjects WHERE subjectId = ? LIMIT 1",
                array($subjectId),
                'i'
            )
            : '';

        $cache[$subjectId] = $subjectName !== '' ? $subjectName : (string) $subjectId;
        return $cache[$subjectId];
    }
}

if (!function_exists('dlhsDashboardResolveYearGroupName')) {
    function dlhsDashboardResolveYearGroupName($connection, $yearGroupId)
    {
        static $cache = array();

        $yearGroupId = (int) $yearGroupId;
        if ($yearGroupId <= 0) {
            return '';
        }

        if (isset($cache[$yearGroupId])) {
            return $cache[$yearGroupId];
        }

        $yearGroupName = function_exists('dlhsFetchScalarValue')
            ? dlhsFetchScalarValue(
                $connection,
                "SELECT yearGroupName FROM yeargroup WHERE yearGroupId = ? LIMIT 1",
                array($yearGroupId),
                'i'
            )
            : '';

        $cache[$yearGroupId] = $yearGroupName !== '' ? $yearGroupName : (string) $yearGroupId;
        return $cache[$yearGroupId];
    }
}

if (!function_exists('dlhsDashboardFormatDateLabel')) {
    function dlhsDashboardFormatDateLabel($value)
    {
        if (empty($value)) {
            return 'Date not set';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $value;
        }

        return date('D, j M Y', $timestamp);
    }
}

if (!function_exists('dlhsDashboardGetStatusMeta')) {
    function dlhsDashboardGetStatusMeta($status)
    {
        $status = (int) $status;
        if ($status === 1) {
            return array('label' => 'Test In Progress', 'class' => 'is-progress', 'icon' => 'fa-play-circle');
        }
        if ($status === 2) {
            return array('label' => 'Test Completed', 'class' => 'is-complete', 'icon' => 'fa-check-circle');
        }

        return array('label' => 'Test Not Started', 'class' => 'is-pending', 'icon' => 'fa-clock-o');
    }
}

if (!function_exists('dlhsDashboardGetCurrentSessionLabel')) {
    function dlhsDashboardGetCurrentSessionLabel($connection)
    {
        $label = function_exists('dlhsGetCurrentAcademicSessionName')
            ? dlhsGetCurrentAcademicSessionName($connection)
            : '';

        if ($label !== '') {
            return $label;
        }

        $year = date('Y');
        return $year . '/' . ($year + 1);
    }
}

if (!function_exists('dlhsDashboardGetUserName')) {
    function dlhsDashboardGetUserName($role)
    {
        if ($role === 'staff') {
            return isset($_SESSION['staffName']) && trim((string) $_SESSION['staffName']) !== ''
                ? trim((string) $_SESSION['staffName'])
                : 'Teacher';
        }

        if (isset($_SESSION['adminName']) && trim((string) $_SESSION['adminName']) !== '') {
            return trim((string) $_SESSION['adminName']);
        }

        if (isset($_SESSION['adminEmail']) && trim((string) $_SESSION['adminEmail']) !== '') {
            return trim((string) $_SESSION['adminEmail']);
        }

        return 'Administrator';
    }
}

if (!function_exists('dlhsDashboardGetUserInitials')) {
    function dlhsDashboardGetUserInitials($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return 'DL';
        }

        $parts = preg_split('/\s+/', $name);
        $initials = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $initials .= strtoupper(substr($part, 0, 1));
            if (strlen($initials) >= 2) {
                break;
            }
        }

        return $initials !== '' ? $initials : 'DL';
    }
}

if (!function_exists('dlhsDashboardGetNavItems')) {
    function dlhsDashboardGetNavItems($role)
    {
        if ($role === 'staff') {
            return array(
                array('label' => 'Dashboard', 'href' => 'index.php', 'icon' => 'fa-th-large', 'active' => true),
                array('label' => 'My Assignments', 'href' => 'myAssignments.php', 'icon' => 'fa-book'),
                array('label' => 'My Question Files', 'href' => 'my_question_files.php', 'icon' => 'fa-files-o'),
                array('label' => 'Create Test', 'href' => 'addTestForm.php', 'icon' => 'fa-pencil-square-o'),
                array('label' => 'Questions', 'href' => 'addQuestionForm.php', 'icon' => 'fa-list-alt'),
                array('label' => 'Test Access', 'href' => 'addStudentsToTestForm.php', 'icon' => 'fa-users'),
                array('label' => 'Results', 'href' => 'examineesStatus.php', 'icon' => 'fa-bar-chart'),
                array('label' => 'Files', 'href' => 'file_management.php', 'icon' => 'fa-folder-open'),
                array('label' => 'Change Password', 'href' => 'changePassword.php', 'icon' => 'fa-key'),
            );
        }

        if ($role === 'student') {
            return array(
                array('label' => 'Dashboard', 'href' => 'index.php', 'icon' => 'fa-th-large', 'active' => true),
                array('label' => 'My Tests', 'href' => 'pendingTests.php', 'icon' => 'fa-hourglass-half'),
                array('label' => 'My Shared Files', 'href' => 'student_files.php', 'icon' => 'fa-folder-open'),
                array('label' => 'File Requests', 'href' => 'student_file_requests.php', 'icon' => 'fa-inbox'),
                array('label' => 'Change Password', 'href' => 'changePassword.php', 'icon' => 'fa-key'),
            );
        }

        return array(
            array('label' => 'Dashboard', 'href' => 'index.php', 'icon' => 'fa-th-large', 'active' => true),
            array('label' => 'Question Document Hub', 'href' => 'question_document_hub.php', 'icon' => 'fa-cloud-upload'),
            array('label' => 'Academic Setup', 'href' => 'academic_settings.php', 'icon' => 'fa-calendar'),
            array('label' => 'Staff', 'href' => 'addStaffForm.php', 'icon' => 'fa-user-plus'),
            array('label' => 'Students', 'href' => 'addStudentForm.php', 'icon' => 'fa-graduation-cap'),
            array('label' => 'All Tests', 'href' => 'allTestsForm.php', 'icon' => 'fa-files-o'),
            array('label' => 'Teacher Questions', 'href' => 'view_teacher_questions.php', 'icon' => 'fa-book'),
            array('label' => 'Subject Assignment', 'href' => 'subjectAssignment.php', 'icon' => 'fa-book'),

            array('label' => 'Settings', 'href' => 'cleanup_tests.php', 'icon' => 'fa-cogs'),

        );
    }
}

if (!function_exists('dlhsDashboardGetQuickActions')) {
    function dlhsDashboardGetQuickActions($role)
    {
        if ($role === 'staff') {
            return array(
                array('label' => 'Create a test', 'href' => 'addTestForm.php', 'icon' => 'fa-plus-circle', 'variant' => 'primary'),
                array('label' => 'My subjects', 'href' => 'myAssignments.php', 'icon' => 'fa-book', 'variant' => 'secondary'),
                array('label' => 'Add questions', 'href' => 'addQuestionForm.php', 'icon' => 'fa-edit', 'variant' => 'secondary'),
                array('label' => 'Manage students', 'href' => 'addStudentsToTestForm.php', 'icon' => 'fa-users', 'variant' => 'secondary'),
                array('label' => 'View results', 'href' => 'examineesStatus.php', 'icon' => 'fa-bar-chart', 'variant' => 'secondary'),
                array('label' => 'Change password', 'href' => 'changePassword.php', 'icon' => 'fa-key', 'variant' => 'secondary'),
            );
        }

        return array(
            array('label' => 'See all tests', 'href' => 'allTestsForm.php', 'icon' => 'fa-files-o', 'variant' => 'primary'),
            array('label' => 'Academic settings', 'href' => 'academic_settings.php', 'icon' => 'fa-calendar', 'variant' => 'secondary'),
            array('label' => 'Assign subjects', 'href' => 'subjectAssignment.php', 'icon' => 'fa-book', 'variant' => 'secondary'),
            array('label' => 'Manage staff', 'href' => 'addStaffForm.php', 'icon' => 'fa-user-plus', 'variant' => 'secondary'),

        );
    }
}

if (!function_exists('dlhsRenderDashboardPage')) {
    function dlhsRenderDashboardPage($config)
    {
        $role = isset($config['role']) ? $config['role'] : 'staff';
        $title = isset($config['title']) ? $config['title'] : 'Dashboard';
        $pageHeading = isset($config['pageHeading']) ? $config['pageHeading'] : 'Distinction Hub';
        $subHeading = isset($config['subHeading']) ? $config['subHeading'] : 'Leadership with distinction.';
        $sessionLabel = isset($config['sessionLabel']) ? $config['sessionLabel'] : '';
        $userName = isset($config['userName']) ? $config['userName'] : 'User';
        $userInitials = dlhsDashboardGetUserInitials($userName);
        $userRoleLabel = isset($config['userRoleLabel']) ? $config['userRoleLabel'] : ucfirst($role);
        $counts = isset($config['counts']) ? $config['counts'] : array('pending' => 0, 'inProgress' => 0, 'completed' => 0);
        $upcomingTests = isset($config['upcomingTests']) ? $config['upcomingTests'] : array();
        $navItems = isset($config['navItems']) ? $config['navItems'] : array();
        $quickActions = isset($config['quickActions']) ? $config['quickActions'] : array();
        $calendarEventsUrl = isset($config['calendarEventsUrl']) ? $config['calendarEventsUrl'] : 'getDates.php';

        $logoutUrl = isset($config['logoutUrl']) ? $config['logoutUrl'] : 'logout.php';
        $fontAwesomeCss = isset($config['fontAwesomeCss']) ? $config['fontAwesomeCss'] : 'css/font-awesome.min.css';
        $calendarCss = isset($config['calendarCss']) ? $config['calendarCss'] : array(
            'assets/fullcalendar/fullcalendar/bootstrap-fullcalendar.css',
            'assets/fullcalendar/fullcalendar/fullcalendar.css',
        );
        $jqueryJs = isset($config['jqueryJs']) ? $config['jqueryJs'] : 'js/jquery.js';
        $bootstrapJs = isset($config['bootstrapJs']) ? $config['bootstrapJs'] : 'js/bootstrap.min.js';
        $extraScripts = isset($config['extraScripts']) ? $config['extraScripts'] : array();
        $fullcalendarJs = isset($config['fullcalendarJs']) ? $config['fullcalendarJs'] : 'assets/fullcalendar/fullcalendar/fullcalendar.js';
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="DLHS dashboard">
    <link rel="icon" type="image/jpg" href="../../images/dlhslogo3.jpg">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($fontAwesomeCss); ?>">
<?php foreach ($calendarCss as $calendarCssFile): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($calendarCssFile); ?>">
<?php endforeach; ?>
    <?php if ($role === 'admin' || $role === 'staff'): ?>
        <link href="css/style.css" rel="stylesheet">
        <link href="css/style-responsive.css" rel="stylesheet">
    <?php endif; ?>
    <style>
        :root {
            --dlhs-blue: #00AEEF;
            --dlhs-magenta: #E91E63;
            --dlhs-navy: #020b1a;
            --dlhs-ink: #10233a;
            --dlhs-soft: rgba(255, 255, 255, 0.58);
            --dlhs-border: rgba(255, 255, 255, 0.72);
            --dlhs-sidebar: rgba(2, 11, 26, 0.82);
            --dlhs-shadow: 0 24px 60px rgba(9, 27, 53, 0.18);
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #10233a;
            color: var(--dlhs-ink, #10233a);
            background: #eef5fb;
            background: linear-gradient(135deg, #eef5fb 0%, #dcecff 40%, #f9f1f6 100%);
            -webkit-text-size-adjust: 100%;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body { position: relative; }
        a { color: inherit; text-decoration: none; }
        button, a, input, select, textarea { touch-action: manipulation; }

        .dashboard-shell {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 1;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }

        .background-orb {
            position: fixed;
            border-radius: 999px;
            opacity: 0.22;
            z-index: 0;
            pointer-events: none;
            max-width: 80vw;
            max-height: 80vh;
            overflow: hidden;
        }

        .background-orb.one {
            width: 34rem;
            height: 34rem;
            top: -10rem;
            left: -8rem;
            background: #00AEEF;
            background: radial-gradient(circle, var(--dlhs-blue, #00AEEF) 0%, transparent 70%);
        }

        .background-orb.two {
            width: 30rem;
            height: 30rem;
            right: -6rem;
            bottom: -6rem;
            background: #E91E63;
            background: radial-gradient(circle, var(--dlhs-magenta, #E91E63) 0%, transparent 70%);
        }

        <?php if ($role !== 'admin' && $role !== 'staff'): ?>
        .sidebar {
            width: 290px;
            background: var(--dlhs-sidebar);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #fff;
            padding: 32px 24px;
            display: flex;
            flex-direction: column;
            gap: 28px;
            overflow: hidden;
            position: sticky;
            top: 0;
            height: 100vh;
            min-height: 100vh;
            border-right: 1px solid rgba(255, 255, 255, 0.12);
        }

        .brand, .identity {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-mark {
            width: 56px;
            height: 56px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(255,255,255,0.95), rgba(255,255,255,0.72));
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 20px 40px rgba(0, 174, 239, 0.22);
        }

        .brand-mark img { width: 34px; height: 34px; object-fit: contain; }
        .brand-copy h1 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.04em; }
        .brand-copy p { margin: 4px 0 0; font-size: 11px; color: #8be0ff; letter-spacing: 0.22em; text-transform: uppercase; font-weight: 700; }

        .nav-group { display: grid; gap: 8px; }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border-radius: 18px;
            color: rgba(255, 255, 255, 0.72);
            transition: background 0.25s ease, transform 0.25s ease, color 0.25s ease;
            font-size: 14px;
            font-weight: 600;
        }

        .nav-link:hover,
        .nav-link.is-active {
            color: #fff;
            background: linear-gradient(90deg, rgba(0, 174, 239, 0.18), rgba(255, 255, 255, 0.08));
            transform: translateX(4px);
        }

        .nav-link i { width: 18px; text-align: center; }

        .sidebar-footer {
            margin-top: auto;
            padding: 18px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
        }

        .identity { margin-bottom: 18px; }
        .identity-badge {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--dlhs-blue), #68d3ff);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            box-shadow: 0 18px 38px rgba(0, 174, 239, 0.3);
        }

        .identity p, .identity h3 { margin: 0; }
        .identity h3 { font-size: 20px; font-weight: 800; line-height: 1.1; }
        .identity p { font-size: 11px; text-transform: uppercase; letter-spacing: 0.18em; color: #8be0ff; margin-top: 4px; }
        .logout-link { display: inline-flex; align-items: center; gap: 8px; color: #ff8aa5; font-size: 13px; font-weight: 700; margin-top: 6px; }
        <?php endif; ?>

        .page {
            flex: 1;
            padding: 30px 32px;
            min-width: 0;
            width: 100%;
        }
        .page-inner {
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .hero {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: flex-start;
            margin-bottom: 26px;
            flex-wrap: wrap;
        }

        .hero-copy {
            flex: 1 1 480px;
            min-width: 0;
        }

        .hero-copy small {
            display: inline-block;
            font-size: 12px;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            color: #00AEEF;
            color: var(--dlhs-blue, #00AEEF);
            font-weight: 800;
            margin-bottom: 12px;
        }

        .hero-copy h2 {
            margin: 0;
            font-size: 34px; /* Fallback for older browsers */
            font-size: clamp(26px, 2.5vw, 42px);
            line-height: 1.1;
            letter-spacing: -0.04em;
            color: #12273f;
        }

        .hero-userline {
            margin: 10px 0 0;
            font-size: 26px; /* Fallback */
            font-size: clamp(22px, 2vw, 34px);
            font-weight: 800;
            letter-spacing: -0.04em;
            color: #16314d;
        }

        .hero-copy p {
            margin: 12px 0 0;
            max-width: 760px;
            font-size: 15px;
            line-height: 1.7;
            color: #4f667f;
        }

        .hero-meta {
            display: grid;
            gap: 12px;
            min-width: 240px;
            flex: 0 0 auto;
        }
        .glass-card {
            background: #ffffff; /* Solid fallback for older browsers */
            background: rgba(255, 255, 255, 0.92);
            -webkit-backdrop-filter: blur(12px) saturate(150%);
            backdrop-filter: blur(12px) saturate(150%);
            border: 1px solid #dce5ef; /* Solid fallback */
            border: 1px solid var(--dlhs-border, rgba(255, 255, 255, 0.72));
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(9, 27, 53, 0.08);
        }

        .meta-card { padding: 18px 20px; }
        .meta-label { font-size: 11px; letter-spacing: 0.18em; text-transform: uppercase; color: #5d7389; font-weight: 800; }
        .meta-value { margin-top: 8px; font-size: 18px; font-weight: 800; color: #12273f; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .stat-card {
            padding: 22px 20px;
            min-width: 0;
        }
        .stat-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 18px; }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-card.pending .stat-icon { background: rgba(224, 165, 69, 0.22); color: #965813; }
        .stat-card.progress .stat-icon { background: rgba(0, 174, 239, 0.14); color: #00AEEF; color: var(--dlhs-blue, #00AEEF); }
        .stat-card.complete .stat-icon { background: rgba(42, 157, 143, 0.16); color: #238c81; }
        .stat-value {
            font-size: 36px; /* Fallback */
            font-size: clamp(30px, 2.5vw, 42px);
            line-height: 1;
            font-weight: 800;
            color: #132b46;
        }
        .stat-label { font-size: 12px; letter-spacing: 0.18em; text-transform: uppercase; color: #5d7389; font-weight: 800; }

        .toggle-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .toggle-card-copy strong {
            display: block;
            font-size: 15px;
            color: #16314d;
            font-weight: 800;
        }

        .toggle-card-copy span {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #5a7187;
            line-height: 1.5;
        }

        .dlhs-switch {
            position: relative;
            display: inline-flex;
            width: 58px;
            height: 32px;
            flex-shrink: 0;
        }

        .dlhs-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .dlhs-switch-track {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            inset: 0;
            border-radius: 999px;
            background: rgba(17,39,63,0.12);
            transition: all 0.25s ease;
            cursor: pointer;
        }

        .dlhs-switch-track:before {
            content: "";
            position: absolute;
            left: 4px;
            top: 4px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 6px 16px rgba(9,27,53,0.16);
            transition: transform 0.25s ease;
        }

        .dlhs-switch input:checked + .dlhs-switch-track {
            background: #00AEEF;
            background: linear-gradient(135deg, var(--dlhs-blue, #00AEEF), #62d6ff);
        }

        .dlhs-switch input:checked + .dlhs-switch-track:before {
            transform: translateX(26px);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
            gap: 22px;
            align-items: start;
            transition: grid-template-columns 0.25s ease;
        }

        .dashboard-grid.calendar-hidden {
            grid-template-columns: 1fr;
        }

        .calendar-card, .content-card {
            padding: 24px;
            min-width: 0;
        }
        .calendar-card.is-hidden { display: none; }
        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .section-head h3 { margin: 0; font-size: 13px; letter-spacing: 0.18em; text-transform: uppercase; color: #415870; font-weight: 800; }
        .section-head span { font-size: 11px; font-weight: 700; color: #00AEEF; color: var(--dlhs-blue, #00AEEF); }
        #calendar { min-height: 480px; width: 100%; overflow: hidden; }
        .fc { background: transparent; width: 100%; }
        .fc-header { margin-bottom: 16px; width: 100%; }
        .fc-header > tbody > tr {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            width: 100%;
        }
        .fc-header-left, .fc-header-center, .fc-header-right {
            display: inline-flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 4px;
        }
        .fc-header-title h2 {
            color: #12273f;
            font-size: 18px;
            font-weight: 800;
            margin: 0;
            white-space: nowrap;
        }
        .fc-button {
            height: auto !important;
            padding: 6px 12px !important;
            font-size: 12px !important;
            line-height: 1.4 !important;
        }
        .fc-state-default { background: #ffffff; background: rgba(255, 255, 255, 0.9); border: 1px solid rgba(17, 39, 63, 0.12); box-shadow: none; color: #23415c; border-radius: 10px; text-shadow: none; }
        .fc-state-active, .fc-state-down { background: #00AEEF; background: linear-gradient(135deg, var(--dlhs-blue, #00AEEF), #64d7ff); color: #fff; border-color: transparent; }
        .fc-day-header { color: #50667a; font-weight: 700; padding: 10px 0; background: rgba(255,255,255,0.4); }
        .fc-day-number { padding: 8px 10px 0 0; color: #30516c; font-weight: 700; }
        .fc-day-grid-event { margin: 2px 4px; }
        .fc-event { border: 0; background: #00AEEF; background: linear-gradient(90deg, var(--dlhs-blue, #00AEEF), #63d4ff); color: #fff; border-radius: 8px; padding: 3px 6px; font-size: 11px; line-height: 1.35; }
        .fc-event .fc-time { display: none; }
        .fc-event .fc-title { white-space: normal; }
        .fc-limited { border-radius: 8px; background: rgba(17,39,63,0.08); color: #23415c; padding: 3px 7px; margin: 2px 4px 4px; display: inline-block; }
        .fc td, .fc th { border-color: rgba(17,39,63,0.08); }
        .fc-day-grid-container { overflow: hidden !important; height: auto !important; }

        .hero-panel { padding: 26px; margin-bottom: 22px; background: #ffffff; background: linear-gradient(135deg, rgba(255,255,255,0.9), rgba(255,255,255,0.6)); border-radius: 20px; }
        .hero-panel h4 { margin: 0; font-size: 24px; color: #12273f; letter-spacing: -0.03em; }
        .hero-panel p { margin: 10px 0 0; font-size: 14.5px; line-height: 1.7; color: #50667a; }
        .quick-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 20px; }
        .quick-action { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 14px 16px; border-radius: 16px; font-size: 13px; font-weight: 700; transition: transform 0.2s ease, box-shadow 0.2s ease; text-align: center; }
        .quick-action:hover { transform: translateY(-2px); box-shadow: 0 14px 24px rgba(11, 28, 53, 0.12); }
        .quick-action.primary { background: #00AEEF; background: linear-gradient(135deg, var(--dlhs-blue, #00AEEF), #5dd2ff); color: #fff; }
        .quick-action.secondary { background: #ffffff; background: rgba(255, 255, 255, 0.9); color: #1d3652; border: 1px solid rgba(17, 39, 63, 0.1); }
        .upcoming-list { display: grid; gap: 12px; }
        .upcoming-item { display: grid; grid-template-columns: 1fr auto; gap: 14px; padding: 16px 18px; border-radius: 18px; background: #ffffff; background: rgba(255, 255, 255, 0.8); border: 1px solid rgba(17, 39, 63, 0.07); }
        .upcoming-item h5, .upcoming-item p { margin: 0; }
        .upcoming-item h5 { font-size: 15px; font-weight: 800; color: #16314d; margin-bottom: 6px; }
        .upcoming-meta { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; font-size: 12px; color: #597086; font-weight: 600; }
        .status-pill { align-self: start; display: inline-flex; align-items: center; padding: 6px 12px; border-radius: 999px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.14em; font-weight: 800; }
        .status-pill.is-pending { background: rgba(212, 163, 115, 0.18); color: #99602a; }
        .status-pill.is-progress { background: rgba(0, 174, 239, 0.16); color: #0b8bbe; }
        .status-pill.is-complete { background: rgba(42, 157, 143, 0.18); color: #247e75; }
        .upcoming-empty { padding: 20px; border-radius: 18px; background: rgba(255, 255, 255, 0.8); color: #5a7086; font-weight: 600; }

        .info-strip { margin-top: 20px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .mini-card { padding: 18px 20px; }
        .mini-card strong { display: block; font-size: 13px; text-transform: uppercase; letter-spacing: 0.16em; color: #4c647b; margin-bottom: 8px; }
        .mini-card p { margin: 0; color: #1a334f; line-height: 1.7; font-size: 14px; }

        .dlhs-mobile-menu-button {
            display: none;
            position: fixed;
            top: 10px;
            right: 10px;
            width: 52px;
            height: 52px;
            border-radius: 16px;
            border: 0;
            background: #020b1a;
            background: rgba(2, 11, 26, 0.95);
            color: #fff;
            box-shadow: 0 14px 28px rgba(9, 27, 53, 0.3);
            z-index: 1301;
            padding: 0;
            cursor: pointer;
            touch-action: manipulation;
        }

        .dlhs-mobile-sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            inset: 0;
            background: rgba(2, 11, 26, 0.45);
            z-index: 1298;
        }

        /* Responsive Breakpoints */
        @media (max-width: 1199px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            #calendar { min-height: 440px; }
            .hero-copy h2 { font-size: 28px; }
        }

        @media (max-width: 1023px) {
            .dashboard-shell { display: block; }
            body.dlhs-sidebar-open { overflow: hidden; }
            .dlhs-mobile-menu-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
                width: 286px;
                max-width: calc(100vw - 48px);
                min-height: 100vh;
                z-index: 1300;
                transform: translateX(-108%);
                transition: transform 0.25s ease;
                border-right: 1px solid rgba(255,255,255,0.12);
                border-bottom: 0;
            }
            .page, #main-content.page {
                padding: 24px 20px !important;
                padding-top: 76px !important;
                margin-left: 0 !important;
            }
            body.dlhs-sidebar-open .sidebar {
                transform: translateX(0);
            }
            body.dlhs-sidebar-open .dlhs-mobile-sidebar-backdrop {
                display: block;
            }
        }

        @media (max-width: 991px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767px) {
            .page, #main-content.page {
                padding: 16px 14px !important;
                padding-top: 72px !important;
                margin-left: 0 !important;
            }
            .hero {
                flex-direction: column;
                gap: 16px;
            }
            .hero-meta {
                width: 100%;
                min-width: 0;
                margin-left: 0;
            }
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .quick-actions {
                grid-template-columns: 1fr;
            }
            .info-strip {
                grid-template-columns: 1fr;
            }
            .stat-card {
                padding: 18px 16px;
            }
            .fc-header-title h2 {
                font-size: 15px;
            }
            .fc-button {
                padding: 4px 8px !important;
                font-size: 11px !important;
            }
            .hero-panel {
                padding: 20px 16px;
            }
            .hero-panel h4 {
                font-size: 20px;
            }
            .calendar-card, .content-card {
                padding: 18px 14px;
            }
        }

        @media (max-width: 480px) {
            .upcoming-item {
                grid-template-columns: 1fr;
                gap: 8px;
            }
            .upcoming-item .status-pill {
                justify-self: start;
            }
        }
    </style>
</head>
<body>
    <div class="background-orb one"></div>
    <div class="background-orb two"></div>
    <?php if ($role !== 'admin' && $role !== 'staff'): ?>
        <button type="button" class="dlhs-mobile-menu-button" id="dlhsDashboardSidebarToggle" onclick="dlhsToggleSidebar()" aria-controls="dashboardSidebar" aria-expanded="false" aria-label="Toggle navigation">
            <i class="fa fa-bars"></i>
        </button>
        <div class="dlhs-mobile-sidebar-backdrop" onclick="dlhsToggleSidebar(false)"></div>
    <?php endif; ?>

    <div class="dashboard-shell">
        <?php if ($role === 'admin'): ?>
            <?php include __DIR__ . '/../adminLogin/adminPanel/sideBar.php'; ?>
        <?php elseif ($role === 'staff'): ?>
            <?php include __DIR__ . '/../staffLogin/staffPanel/sideBar.php'; ?>
        <?php else: ?>
            <aside class="sidebar" id="dashboardSidebar">
                <div class="brand">
                    <div class="brand-mark">
                        <img src="../../images/dlhslogo3.jpg" alt="DLHS">
                    </div>
                    <div class="brand-copy">
                        <h1>DLHS</h1>
                        <p>Kaduna</p>
                    </div>
                </div>

                <nav class="nav-group">
    <?php foreach ($navItems as $navItem): ?>
                    <a class="nav-link<?php echo !empty($navItem['active']) ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($navItem['href']); ?>" onclick="dlhsToggleSidebar(false)">
                        <i class="fa <?php echo htmlspecialchars($navItem['icon']); ?>"></i>
                        <span><?php echo htmlspecialchars($navItem['label']); ?></span>
                    </a>
    <?php endforeach; ?>
                </nav>

                <div class="sidebar-footer">
                    <div class="identity">
                        <div class="identity-badge"><?php echo htmlspecialchars($userInitials); ?></div>
                        <div>
                            <h3><?php echo htmlspecialchars($userName); ?></h3>
                            <p><?php echo htmlspecialchars($userRoleLabel); ?></p>
                        </div>
                    </div>
                    <a class="logout-link" href="<?php echo htmlspecialchars($logoutUrl); ?>" onclick="dlhsToggleSidebar(false)">
                        <i class="fa fa-power-off"></i>
                        <span>Sign out</span>
                    </a>
                </div>
            </aside>
        <?php endif; ?>

        <main class="page" <?php if ($role === 'admin' || $role === 'staff') echo 'id="main-content"'; ?>>
            <div class="page-inner">
                <div class="hero">
                    <div class="hero-copy">
                        <small>Academic Session <?php echo htmlspecialchars($sessionLabel); ?></small>
                        <h2><?php echo htmlspecialchars($pageHeading); ?></h2>
                        <?php if ($role === 'staff'): ?>
                            <div class="hero-teacher-name" style="font-size: 18px; font-weight: 600; color: #16314d; margin-top: 8px; margin-bottom: 8px;">
                                <i class="fa fa-user-circle-o"></i> <?php echo htmlspecialchars($userName); ?>
                            </div>
                        <?php else: ?>
                            <div class="hero-userline"><?php echo htmlspecialchars($userName); ?></div>
                        <?php endif; ?>
                        <p><?php echo htmlspecialchars($subHeading); ?></p>
                    </div>
                    <div class="hero-meta">
                        <div class="glass-card meta-card">
                            <div class="meta-label">Today</div>
                            <div class="meta-value"><?php echo htmlspecialchars(date('l, j M Y')); ?></div>
                        </div>
                        <div class="glass-card meta-card">
                            <div class="meta-label">Workspace</div>
                            <div class="meta-value"><?php echo htmlspecialchars($userRoleLabel); ?> Dashboard</div>
                        </div>
                        <div class="glass-card meta-card toggle-card">
                            <div class="toggle-card-copy">
                                <strong>Calendar</strong>
                                <span>Show or hide the planner to give the page more breathing room.</span>
                            </div>
                            <label class="dlhs-switch" for="calendarVisibilityToggle">
                                <input type="checkbox" id="calendarVisibilityToggle" checked>
                                <span class="dlhs-switch-track"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <section class="stats-grid">
                    <article class="glass-card stat-card pending" <?php if ($role === 'student'): ?>onclick="window.location.href='pendingTests.php#notStartedTab'" style="cursor:pointer; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='none'; this.style.boxShadow='none'"<?php endif; ?>>
                        <div class="stat-top">
                            <div class="stat-icon"><i class="fa fa-clock-o"></i></div>
                            <div class="stat-value"><?php echo (int) $counts['pending']; ?></div>
                        </div>
                        <div class="stat-label">Not started</div>
                    </article>
                    <article class="glass-card stat-card progress" <?php if ($role === 'student'): ?>onclick="window.location.href='pendingTests.php#inProgressTab'" style="cursor:pointer; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='none'; this.style.boxShadow='none'"<?php endif; ?>>
                        <div class="stat-top">
                            <div class="stat-icon"><i class="fa fa-play"></i></div>
                            <div class="stat-value"><?php echo (int) $counts['inProgress']; ?></div>
                        </div>
                        <div class="stat-label">In progress</div>
                    </article>
                    <article class="glass-card stat-card complete" <?php if ($role === 'student'): ?>onclick="window.location.href='pendingTests.php#endedTab'" style="cursor:pointer; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='none'; this.style.boxShadow='none'"<?php endif; ?>>
                        <div class="stat-top">
                            <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
                            <div class="stat-value"><?php echo (int) $counts['completed']; ?></div>
                        </div>
                        <div class="stat-label">Completed</div>
                    </article>
                </section>

                <section class="dashboard-grid" id="dashboardGrid">
                    <article class="glass-card calendar-card" id="calendarCard">
                        <div class="section-head">
                            <h3>Test Planner</h3>
                            <span id="calendarStatusLabel">Visible</span>
                        </div>
                        <div id="calendar"></div>
                    </article>

                    <div class="content-column">
                        <article class="glass-card hero-panel">
                            <h4><?php echo htmlspecialchars($role === 'staff' ? 'Assessment Command Centre' : 'Operations Command Centre'); ?></h4>
                            <p><?php echo htmlspecialchars($role === 'staff'
                                ? 'Create tests, manage questions, monitor access, and keep result preparation moving from one organised space.'
                                : 'Stay on top of school-wide testing activity, support teachers quickly, and keep administration flowing with one clear dashboard.'); ?></p>
                            <div class="quick-actions">
<?php foreach ($quickActions as $quickAction): ?>
                                <a class="quick-action <?php echo htmlspecialchars($quickAction['variant']); ?>" href="<?php echo htmlspecialchars($quickAction['href']); ?>">
                                    <i class="fa <?php echo htmlspecialchars($quickAction['icon']); ?>"></i>
                                    <span><?php echo htmlspecialchars($quickAction['label']); ?></span>
                                </a>
<?php endforeach; ?>
                            </div>
                        </article>
                        <article class="glass-card content-card">
                            <div class="section-head">
                                <h3>Upcoming Tests</h3>
                                <span><?php echo count($upcomingTests); ?> scheduled</span>
                            </div>
<?php if (empty($upcomingTests)): ?>
                            <div class="upcoming-empty">No scheduled tests are available yet. Once tests are created, they will appear here with their date and current state.</div>
<?php else: ?>
                            <div class="upcoming-list">
<?php foreach ($upcomingTests as $upcomingTest): ?>
<?php $statusMeta = dlhsDashboardGetStatusMeta(isset($upcomingTest['status']) ? $upcomingTest['status'] : 0); ?>
                                <div class="upcoming-item">
                                    <div>
                                        <h5><?php echo htmlspecialchars($upcomingTest['testName']); ?></h5>
                                        <div class="upcoming-meta">
                                            <span><i class="fa fa-calendar"></i> <?php echo htmlspecialchars(dlhsDashboardFormatDateLabel(isset($upcomingTest['testDate']) ? $upcomingTest['testDate'] : '')); ?></span>
                                            <span><i class="fa fa-book"></i> <?php echo htmlspecialchars(isset($upcomingTest['subject']) ? $upcomingTest['subject'] : ''); ?></span>
                                            <span><i class="fa fa-tag"></i> <?php echo htmlspecialchars(isset($upcomingTest['testType']) ? $upcomingTest['testType'] : 'TEST'); ?></span>
                                            <span><i class="fa fa-users"></i> <?php echo htmlspecialchars(isset($upcomingTest['yearGroup']) ? $upcomingTest['yearGroup'] : ''); ?></span>
                                        </div>
                                    </div>
                                    <div class="status-pill <?php echo htmlspecialchars($statusMeta['class']); ?>"><?php echo htmlspecialchars($statusMeta['label']); ?></div>
                                </div>
<?php endforeach; ?>
                            </div>
<?php endif; ?>
                        </article>
<?php if ($role === 'staff'): ?>
                        <?php
                        global $connection;
                        $tId = isset($_SESSION['staffId']) ? (int)$_SESSION['staffId'] : 0;
                        $filesRes = $connection->query("SELECT tqf.*, yg.yearGroupName, s.subjectName 
                                                       FROM teacher_question_files tqf
                                                       LEFT JOIN yeargroup yg ON tqf.yearGroupId = yg.yearGroupId
                                                       LEFT JOIN subjects s ON tqf.subjectId = s.subjectId
                                                       WHERE tqf.teacherId = $tId
                                                       ORDER BY tqf.uploadDate DESC LIMIT 5");
                        $myQuestionFiles = [];
                        if ($filesRes) {
                            while ($f = $filesRes->fetch_assoc()) {
                                $myQuestionFiles[] = $f;
                            }
                        }
                        ?>
                        <article class="glass-card content-card" style="margin-top: 24px;">
                            <div class="section-head">
                                <h3>Assigned Question Files</h3>
                                <span><?php echo count($myQuestionFiles); ?> received</span>
                            </div>
                            <?php if (empty($myQuestionFiles)): ?>
                                <div class="upcoming-empty"><i class="fa fa-info-circle"></i> No question files have been allocated to you yet.</div>
                            <?php else: ?>
                                <div class="upcoming-list">
                                    <?php foreach ($myQuestionFiles as $qFile): ?>
                                        <div class="upcoming-item">
                                            <div>
                                                <h5 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 600; color: #10233a;">
                                                    <?php echo htmlspecialchars($qFile['fileName']); ?>
                                                </h5>
                                                <div class="upcoming-meta" style="display: flex; gap: 12px; font-size: 11px; color: #627d98;">
                                                    <span><i class="fa fa-users"></i> <?php echo htmlspecialchars($qFile['yearGroupName'] ?? 'Unresolved'); ?></span>
                                                    <span><i class="fa fa-book"></i> <?php echo htmlspecialchars($qFile['subjectName'] ?? 'Unresolved'); ?></span>
                                                    <span><i class="fa fa-tag"></i> <?php echo htmlspecialchars($qFile['testType'] ?? 'N/A'); ?></span>
                                                    <span><i class="fa fa-info-circle"></i> Status: <strong><?php echo htmlspecialchars($qFile['status']); ?></strong></span>
                                                </div>
                                            </div>
                                            <a href="my_question_files.php" class="status-pill is-progress" style="background: rgba(0, 174, 239, 0.08); color: #00AEEF; font-weight: 700; border-radius: 20px; font-size: 11px; padding: 4px 10px; cursor: pointer; text-decoration: none;">VIEW HUB</a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div style="margin-top: 18px; text-align: right;">
                                <a href="my_question_files.php" style="font-size: 12px; font-weight: 700; color: var(--dlhs-blue);">Go to My Question Files Hub <i class="fa fa-arrow-right"></i></a>
                            </div>
                        </article>
<?php endif; ?>
<?php
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
?>
<?php if ($role === 'admin' && !empty($config['recentAssignments'])): ?>
                        <article class="glass-card content-card" style="margin-top: 24px;">
                            <div class="section-head">
                                <h3>Teacher Assignments</h3>
                                <span>Recent per-arm mappings</span>
                            </div>
                            <div class="upcoming-list">
<?php foreach ($config['recentAssignments'] as $assignment): ?>
                                <div class="upcoming-item">
                                    <div>
                                        <h5><?php echo htmlspecialchars($assignment['teacherName']); ?></h5>
                                        <div class="upcoming-meta">
                                            <span><i class="fa fa-book"></i> <?php echo htmlspecialchars($assignment['subjectName']); ?></span>
                                            <span><i class="fa fa-users"></i> <?php echo htmlspecialchars($assignment['yearGroupName']); ?> - <?php echo htmlspecialchars($assignment['className']); ?></span>
                                        </div>
                                    </div>
                                    <div class="status-pill is-progress" style="background: rgba(16, 35, 58, 0.08); color: #10233a;">ASSIGNED</div>
                                </div>
<?php endforeach; ?>
                            </div>
                            <div style="margin-top: 18px; text-align: right;">
                                <a href="subjectAssignment.php" style="font-size: 12px; font-weight: 700; color: var(--dlhs-blue);">Manage All Assignments <i class="fa fa-arrow-right"></i></a>
                            </div>
                        </article>
<?php endif; ?>

<?php if ($role === 'staff' && !empty($config['teacherAssignments'])): ?>
                        <article class="glass-card content-card" style="margin-top: 24px;">
                            <div class="section-head">
                                <h3>My Teaching Assignments</h3>
                                <span>Your mapped subjects & classes</span>
                            </div>
                            <div class="upcoming-list">
<?php foreach ($config['teacherAssignments'] as $assignment): ?>
                                <div class="upcoming-item">
                                    <div>
                                        <h5><?php echo htmlspecialchars($assignment['subjectName']); ?></h5>
                                        <div class="upcoming-meta">
                                            <span><i class="fa fa-users"></i> <?php echo htmlspecialchars($assignment['yearGroupName']); ?> - <?php echo htmlspecialchars($assignment['className']); ?></span>
                                        </div>
                                    </div>
                                    <div class="status-pill is-progress" style="background: rgba(0, 174, 239, 0.14); color: #0b8bbe;">ACTIVE</div>
                                </div>
<?php endforeach; ?>
                            </div>
                            <div style="margin-top: 18px; text-align: right;">
                                <a href="myAssignments.php" style="font-size: 12px; font-weight: 700; color: var(--dlhs-blue);">View Detailed List <i class="fa fa-arrow-right"></i></a>
                            </div>
                        </article>
<?php endif; ?>


                    </div>
                </section>
            </div>
        </main>
    </div>



    <script src="<?php echo htmlspecialchars($jqueryJs); ?>"></script>
    <script src="<?php echo htmlspecialchars($bootstrapJs); ?>"></script>
<?php foreach ($extraScripts as $extraScript): ?>
    <script src="<?php echo htmlspecialchars($extraScript); ?>"></script>
<?php endforeach; ?>
    <script src="<?php echo htmlspecialchars($fullcalendarJs); ?>"></script>
    <script>

        var dlhsCalendarInitialized = false;

        function dlhsInitCalendarIfNeeded() {
            if (dlhsCalendarInitialized || !window.jQuery || !jQuery.fn || !jQuery.fn.fullCalendar) {
                return;
            }
            var $cal = jQuery('#calendar');
            if (!$cal.length) {
                return;
            }
            dlhsCalendarInitialized = true;
            $cal.fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month'
                },
                editable: false,
                droppable: false,
                height: 'auto',
                fixedWeekCount: false,
                eventLimit: 2,
                displayEventTime: false,
                events: <?php echo json_encode($calendarEventsUrl); ?>
            });
        }

        function dlhsSyncSidebarButton() {
            var sidebarButton = document.getElementById('dlhsDashboardSidebarToggle');
            if (!sidebarButton) {
                return;
            }

            var isOpen = document.body.classList.contains('dlhs-sidebar-open');
            var icon = sidebarButton.querySelector('i');
            sidebarButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            sidebarButton.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
            if (icon) {
                icon.className = isOpen ? 'fa fa-times' : 'fa fa-bars';
            }
        }

        function dlhsToggleSidebar(forceOpen) {
            var shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : !document.body.classList.contains('dlhs-sidebar-open');
            document.body.classList.toggle('dlhs-sidebar-open', shouldOpen);
            dlhsSyncSidebarButton();
        }

        function setCalendarVisibility(isVisible) {
            var grid = document.getElementById('dashboardGrid');
            var calendarCard = document.getElementById('calendarCard');
            var toggle = document.getElementById('calendarVisibilityToggle');
            var statusLabel = document.getElementById('calendarStatusLabel');

            if (!grid || !calendarCard || !toggle || !statusLabel) {
                return;
            }

            toggle.checked = !!isVisible;
            grid.classList.toggle('calendar-hidden', !isVisible);
            calendarCard.classList.toggle('is-hidden', !isVisible);
            statusLabel.textContent = isVisible ? 'Visible' : 'Hidden';

            try {
                window.localStorage.setItem('dlhs-dashboard-calendar-visible', isVisible ? '1' : '0');
            } catch (error) {}

            if (isVisible) {
                dlhsInitCalendarIfNeeded();
                if (window.jQuery && jQuery.fn && jQuery.fn.fullCalendar) {
                    window.setTimeout(function() {
                        try {
                            jQuery('#calendar').fullCalendar('render');
                        } catch (error) {}
                    }, 80);
                }
            }
        }


        $(function() {
            dlhsSyncSidebarButton();

            $(document).on('keydown', function(event) {
                if (event.key === 'Escape') {
                    dlhsToggleSidebar(false);
                }
            });

            $(window).on('resize', function() {
                if (window.innerWidth > 1023) {
                    dlhsToggleSidebar(false);
                }
            });

            var savedCalendarPreference = '1';
            try {
                savedCalendarPreference = window.localStorage.getItem('dlhs-dashboard-calendar-visible') || '1';
            } catch (error) {}

            setCalendarVisibility(savedCalendarPreference !== '0');

            $('#calendarVisibilityToggle').on('change', function() {
                setCalendarVisibility(this.checked);
            });
        });
    </script>
</body>
</html>
<?php
    }
}
