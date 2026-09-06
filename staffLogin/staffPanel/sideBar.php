<?php
require_once '../../track_user_presence.php';

// Include connection if not already present
if (!isset($connection)) {
    include_once '../../db_connection/dlhs_db_connection.php';
}

$isFormTeacher = false;
$isAdmin = isset($_SESSION['adminLoggedIn']) && $_SESSION['adminLoggedIn'] === 'yes';

if (isset($_SESSION['staffLoggedIn']) && $_SESSION['staffLoggedIn'] === 'yes') {
    $staffId = $_SESSION['staffId'];
    if (isset($connection) && $connection instanceof mysqli) {
        $stmt = $connection->prepare("SELECT 1 FROM form_teacher_assignment WHERE teacherId = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $staffId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $isFormTeacher = true;
            }
            $stmt->close();
        }
    }
}

$currentPage = basename($_SERVER['PHP_SELF']);
$isCreateTest = in_array($currentPage, array('addTestForm.php', 'editTest.php', 'editOverAllTestTime.php', 'modifyTimeBeforeTestStarts.php', 'rescheduleTest.php', 'endTest.php'));
$isQuestions = in_array($currentPage, array('addQuestionForm.php', 'viewQuestionForm.php', 'editQuestionForm.php'));
$isTestAccess = in_array($currentPage, array('addStudentsToTestForm.php', 'manageStudentsAndTestForm.php'));
$isResults = in_array($currentPage, array('examineesStatus.php', 'getAllStudentsResults.php', 'getStudentquestionsResult.php', 'formTeacherBusinessForm.php', 'classResultEntryForm.php', 'generateResultForm.php', 'manage_term_results.php', 'manage_assessments.php', 'print_term_report.php'));

if (!function_exists('dlhsSidebarInitials')) {
    function dlhsSidebarInitials($name)
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

$staffName = isset($_SESSION['staffName']) && trim((string) $_SESSION['staffName']) !== ''
    ? trim((string) $_SESSION['staffName'])
    : 'Staff User';
?>
<script>
    function setNext()
    {
        <?php $_SESSION['setNext'] = 1; ?>
    }

    function dlhsSyncSidebarButton()
    {
        var sidebarButton = document.getElementById('dlhsSidebarToggle');
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

    function dlhsSyncSidebarCollapseButton()
    {
        var collapseButton = document.getElementById('dlhsSidebarCollapseToggle');
        if (!collapseButton) {
            return;
        }

        var isCollapsed = document.body.classList.contains('dlhs-sidebar-collapsed');
        var icon = collapseButton.querySelector('i');
        collapseButton.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        collapseButton.setAttribute('aria-label', isCollapsed ? 'Expand navigation' : 'Collapse navigation');
        if (icon) {
            icon.className = isCollapsed ? 'fa fa-angle-double-right' : 'fa fa-angle-double-left';
        }
    }

    function dlhsToggleSidebar(forceOpen)
    {
        var body = document.body;
        var shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : !body.classList.contains('dlhs-sidebar-open');
        body.classList.toggle('dlhs-sidebar-open', shouldOpen);
        dlhsSyncSidebarButton();
    }

    function dlhsToggleSidebarCollapse(forceCollapsed)
    {
        if (window.innerWidth <= 1023) {
            dlhsToggleSidebar();
            return;
        }

        var body = document.body;
        var shouldCollapse = typeof forceCollapsed === 'boolean' ? forceCollapsed : !body.classList.contains('dlhs-sidebar-collapsed');
        body.classList.toggle('dlhs-sidebar-collapsed', shouldCollapse);
        try {
            localStorage.setItem('dlhsSidebarCollapsed', shouldCollapse ? '1' : '0');
        } catch (error) {}
        dlhsSyncSidebarCollapseButton();
    }

	    function dlhsToggleSubmenu(button)
	    {
	        if (!button) return;
        var item = button.closest('.has-submenu');
        if (!item) return;
        var willOpen = !item.classList.contains('is-open');
        item.classList.toggle('is-open', willOpen);
	        button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
	    }

	    window.dlhsToggleSidebar = dlhsToggleSidebar;
	    window.dlhsToggleSidebarCollapse = dlhsToggleSidebarCollapse;
	    window.dlhsToggleSubmenu = dlhsToggleSubmenu;

    document.addEventListener('DOMContentLoaded', function () {
        document.body.classList.add('dlhs-has-unified-sidebar');
        try {
            if (localStorage.getItem('dlhsSidebarCollapsed') === '1' && window.innerWidth > 1023) {
                document.body.classList.add('dlhs-sidebar-collapsed');
            }
        } catch (error) {}
        dlhsSyncSidebarButton();
        dlhsSyncSidebarCollapseButton();

        var legacyToggle = document.querySelector('.toggle-nav');
        if (legacyToggle) {
            legacyToggle.addEventListener('click', function (event) {
                if (window.innerWidth > 1023) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();
                dlhsToggleSidebar();
            }, true);
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                dlhsToggleSidebar(false);
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 1023) {
                dlhsToggleSidebar(false);
                dlhsSyncSidebarCollapseButton();
            }
        });

        document.querySelectorAll('.nav-item.has-submenu .nav-toggle').forEach(function(toggle){
            var parent = toggle.closest('.has-submenu');
            var isOpen = parent && parent.classList.contains('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggle.addEventListener('click', function(evt){
                evt.preventDefault();
                dlhsToggleSubmenu(toggle);
            });
        });
    });
</script>
<button type="button" class="dlhs-mobile-menu-button" id="dlhsSidebarToggle" onclick="dlhsToggleSidebar()" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
    <i class="fa fa-bars"></i>
</button>
<button type="button" class="dlhs-sidebar-collapse-button" id="dlhsSidebarCollapseToggle" onclick="dlhsToggleSidebarCollapse()" aria-controls="sidebar" aria-expanded="true" aria-label="Collapse navigation">
    <i class="fa fa-angle-double-left"></i>
</button>
<div class="dlhs-mobile-sidebar-backdrop" onclick="dlhsToggleSidebar(false)"></div>
<aside class="dlhs-unified-sidebar">
    <div id="sidebar" class="nav-collapse sidebar">
        <div class="brand dlhs-sidepanel-brand">
            <div class="brand-mark dlhs-sidepanel-logo">
                <img src="../../images/dlhslogo3.jpg" alt="DLHS">
            </div>
            <div class="brand-copy dlhs-sidepanel-brandcopy">
                <h1>DLHS</h1>
                <p>Kaduna</p>
            </div>
        </div>

        <nav class="nav-group sidebar-menu">
            <a class="nav-link<?php if ($currentPage === 'index.php') echo ' is-active'; ?>" href="index.php" onclick="dlhsToggleSidebar(false)">
                <i class="fa fa-th-large"></i>
                <span>Dashboard</span>
            </a>

            <div class="nav-item has-submenu<?php echo $isCreateTest ? ' is-open' : ''; ?>">
                <button type="button" class="nav-link nav-toggle" aria-expanded="<?php echo $isCreateTest ? 'true' : 'false'; ?>">
                    <i class="fa fa-pencil-square-o"></i>
                    <span>Create Test</span>
                    <i class="fa fa-chevron-down submenu-caret"></i>
                </button>
                <div class="nav-submenu">
                    <a class="nav-sublink<?php if (in_array($currentPage, array('addTestForm.php', 'editTest.php'))) echo ' is-active'; ?>" href="addTestForm.php" onclick="setNext(); dlhsToggleSidebar(false)">Create / Edit Tests</a>
                    <a class="nav-sublink<?php if (in_array($currentPage, array('modifyTimeBeforeTestStarts.php', 'editOverAllTestTime.php', 'rescheduleTest.php'))) echo ' is-active'; ?>" href="modifyTimeBeforeTestStarts.php" onclick="setNext(); dlhsToggleSidebar(false)">Timing & Reschedule</a>
                    <a class="nav-sublink<?php if ($currentPage === 'endTest.php') echo ' is-active'; ?>" href="endTest.php" onclick="setNext(); dlhsToggleSidebar(false)">End / Cancel Test</a>
                </div>
            </div>
            <div class="nav-item has-submenu<?php echo $isQuestions ? ' is-open' : ''; ?>">
                <button type="button" class="nav-link nav-toggle" aria-expanded="<?php echo $isQuestions ? 'true' : 'false'; ?>">
                    <i class="fa fa-list-alt"></i>
                    <span>Questions</span>
                    <i class="fa fa-chevron-down submenu-caret"></i>
                </button>
                <div class="nav-submenu">
                    <a class="nav-sublink<?php if ($currentPage === 'addQuestionForm.php') echo ' is-active'; ?>" href="addQuestionForm.php" onclick="setNext(); dlhsToggleSidebar(false)">Add Questions</a>
                    <a class="nav-sublink<?php if ($currentPage === 'viewQuestionForm.php') echo ' is-active'; ?>" href="viewQuestionForm.php" onclick="setNext(); dlhsToggleSidebar(false)">View / Manage</a>
                </div>
            </div>

            <a class="nav-link<?php if ($currentPage === 'my_question_files.php') echo ' is-active'; ?>" href="my_question_files.php" onclick="dlhsToggleSidebar(false)">
                <i class="fa fa-folder-open"></i>
                <span>My Question Files</span>
            </a>
            <div class="nav-item has-submenu<?php echo $isTestAccess ? ' is-open' : ''; ?>">
                <button type="button" class="nav-link nav-toggle" aria-expanded="<?php echo $isTestAccess ? 'true' : 'false'; ?>">
                    <i class="fa fa-users"></i>
                    <span>Test Access</span>
                    <i class="fa fa-chevron-down submenu-caret"></i>
                </button>
                <div class="nav-submenu">
                    <a class="nav-sublink<?php if ($currentPage === 'addStudentsToTestForm.php') echo ' is-active'; ?>" href="addStudentsToTestForm.php" onclick="setNext(); dlhsToggleSidebar(false)">Add Students</a>
                </div>
            </div>
            <div class="nav-item has-submenu<?php echo $isResults ? ' is-open' : ''; ?>">
                <button type="button" class="nav-link nav-toggle" aria-expanded="<?php echo $isResults ? 'true' : 'false'; ?>">
                    <i class="fa fa-bar-chart"></i>
                    <span>Results Processing</span>
                    <i class="fa fa-chevron-down submenu-caret"></i>
                </button>
                <div class="nav-submenu">
                    <a class="nav-sublink<?php if ($currentPage === 'examineesStatus.php') echo ' is-active'; ?>" href="examineesStatus.php" onclick="setNext(); dlhsToggleSidebar(false)">CBT Test Results</a>
                    <a class="nav-sublink<?php if ($currentPage === 'manage_term_results.php') echo ' is-active'; ?>" href="manage_term_results.php" onclick="setNext(); dlhsToggleSidebar(false)">Enter Term Scores</a>
                    <?php if ($isFormTeacher): ?>
                    <a class="nav-sublink<?php if ($currentPage === 'manage_assessments.php') echo ' is-active'; ?>" href="manage_assessments.php" onclick="setNext(); dlhsToggleSidebar(false)">Behavior Assessments</a>
                    <a class="nav-sublink<?php if ($currentPage === 'formTeacherBusinessForm.php') echo ' is-active'; ?>" href="formTeacherBusinessForm.php" onclick="setNext(); dlhsToggleSidebar(false)">Form Teacher Business</a>
                    <?php endif; ?>
                    <?php if ($isFormTeacher || $isAdmin): ?>
                    <a class="nav-sublink<?php if ($currentPage === 'print_term_report.php') echo ' is-active'; ?>" href="print_term_report.php" onclick="setNext(); dlhsToggleSidebar(false)">Print Report Cards</a>
                    <?php endif; ?>
                </div>
            </div>

            <a class="nav-link<?php if ($currentPage === 'file_management.php') echo ' is-active'; ?>" href="file_management.php" onclick="setNext(); dlhsToggleSidebar(false)">
                <i class="fa fa-folder-open"></i>
                <span>Files</span>
            </a>
            <a class="nav-link<?php if ($currentPage === 'file_requests.php') echo ' is-active'; ?>" href="file_requests.php" onclick="setNext(); dlhsToggleSidebar(false)">
                <i class="fa fa-inbox"></i>
                <span>File Requests</span>
            </a>
            <a class="nav-link<?php if ($currentPage === 'smartboard_remote.php') echo ' is-active'; ?>" href="smartboard_remote.php" onclick="setNext(); dlhsToggleSidebar(false)">
                <i class="fa fa-television"></i>
                <span>Smartboard Remote</span>
            </a>
        </nav>

        <div class="sidebar-footer dlhs-sidepanel-footer">
            <div class="identity dlhs-sidepanel-user">
                <div class="identity-badge dlhs-sidepanel-avatar"><?php echo htmlspecialchars(dlhsSidebarInitials($staffName)); ?></div>
                <div class="dlhs-sidepanel-usercopy">
                    <h3><?php echo htmlspecialchars($staffName); ?></h3>
                    <p>Staff Portal</p>
                </div>
            </div>
            <a class="dlhs-sidepanel-logout" href="logout.php" onclick="dlhsToggleSidebar(false)">
                <i class="fa fa-power-off"></i>
                <span>Sign out</span>
            </a>
        </div>
    </div>
</aside>
<link rel="stylesheet" href="css/staff-help-guide.css">
<script src="js/staff-help-guide.js" defer></script>
