<?php
require_once '../../track_user_presence.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$isTests = in_array($currentPage, array('pendingTests.php', 'testsInProgress.php', 'takenTests.php'));
$isMessages = ($currentPage === 'message.php');
$isFiles = ($currentPage === 'student_files.php');
$isRequests = ($currentPage === 'student_file_requests.php');

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

$studentName = isset($_SESSION['studentName']) && trim((string) $_SESSION['studentName']) !== ''
    ? trim((string) $_SESSION['studentName'])
    : 'Student User';
?>
<script>
    function setNext()
    {
        <?php
            $_SESSION['setNext'] = 1;
            $_SESSION['pages'] = 1;
        ?>
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

    function dlhsToggleSidebar(forceOpen)
    {
        var body = document.body;
        var shouldOpen = typeof forceOpen === 'boolean' ? forceOpen : !body.classList.contains('dlhs-sidebar-open');
        body.classList.toggle('dlhs-sidebar-open', shouldOpen);
        dlhsSyncSidebarButton();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.body.classList.add('dlhs-has-unified-sidebar');
        dlhsSyncSidebarButton();

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
            }
        });
    });
</script>
<button type="button" class="dlhs-mobile-menu-button" id="dlhsSidebarToggle" onclick="dlhsToggleSidebar()" aria-controls="sidebar" aria-expanded="false" aria-label="Toggle navigation">
    <i class="fa fa-bars"></i>
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
            <a class="nav-link<?php if ($isTests) echo ' is-active'; ?>" href="pendingTests.php" onclick="setNext(); dlhsToggleSidebar(false)">
                <i class="fa fa-hourglass-half"></i>
                <span>Tests</span>
            </a>
            <a class="nav-link<?php if ($isFiles) echo ' is-active'; ?>" href="student_files.php" onclick="setNext(); dlhsToggleSidebar(false)">
                <i class="fa fa-folder-open"></i>
                <span>My Shared Files</span>
            </a>
            <a class="nav-link<?php if ($isRequests) echo ' is-active'; ?>" href="student_file_requests.php" onclick="setNext(); dlhsToggleSidebar(false)">
                <i class="fa fa-inbox"></i>
                <span>File Requests</span>
            </a>
            <a class="nav-link<?php if ($currentPage === 'student_game_controller.php') echo ' is-active'; ?>" href="student_game_controller.php" onclick="setNext(); dlhsToggleSidebar(false)">
                <i class="fa fa-gamepad"></i>
                <span>Live Classroom Game</span>
            </a>
            <a class="nav-link<?php if ($currentPage === 'changePassword.php') echo ' is-active'; ?>" href="changePassword.php" onclick="setNext(); dlhsToggleSidebar(false)">
                <i class="fa fa-key"></i>
                <span>Change Password</span>
            </a>
        </nav>

        <div class="sidebar-footer dlhs-sidepanel-footer">
            <div class="identity dlhs-sidepanel-user">
                <div class="identity-badge dlhs-sidepanel-avatar"><?php echo htmlspecialchars(dlhsSidebarInitials($studentName)); ?></div>
                <div class="dlhs-sidepanel-usercopy">
                    <h3><?php echo htmlspecialchars($studentName); ?></h3>
                    <p>Student Portal</p>
                </div>
            </div>
            <a class="dlhs-sidepanel-logout" href="logout.php" onclick="dlhsToggleSidebar(false)">
                <i class="fa fa-power-off"></i>
                <span>Sign out</span>
            </a>
        </div>
    </div>
</aside>
