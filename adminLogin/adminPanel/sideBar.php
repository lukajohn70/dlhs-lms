<?php
require_once '../../track_user_presence.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$isSettings = in_array($currentPage, array('security_violations.php', 'terminate_exam.php', 'cleanup_tests.php'));

$isTests = in_array($currentPage, array('allTestsForm.php', 'yetToBeStartedTests.php', 'testsInProgress.php', 'endedTests.php', 'view_teacher_questions.php'));
$isQueries = in_array($currentPage, array('reportQueries.php', 'namesAndIds.php'));
$isResultProcessing = in_array($currentPage, array('assignFormTeachersForm.php', 'manageGradingForm.php', 'manageCharacterForm.php', 'managePsychomotorForm.php', 'manageHouseForm.php', 'manageSportForm.php', 'generateResultForm.php', 'result_config.php'));

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

$adminName = isset($_SESSION['adminName']) && trim((string) $_SESSION['adminName']) !== ''
    ? trim((string) $_SESSION['adminName'])
    : (isset($_SESSION['adminEmail']) ? trim((string) $_SESSION['adminEmail']) : 'Administrator');
?>
<script>
    function dlhsSyncSidebarButton()
    {
        var toggleButton = document.getElementById('dlhsSidebarToggle');
        if (!toggleButton) return;
        var body = document.body;
        var isOpen = body.classList.contains('dlhs-sidebar-open');
        var icon = toggleButton.querySelector('i');
        if (icon) {
            icon.className = isOpen ? 'fa fa-times' : 'fa fa-bars';
        }
        toggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    function setNext()
    {
        <?php $_SESSION['setNext'] = 1; ?>
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
        
        // Close other submenus if opening a new one (optional, but cleaner)
        document.querySelectorAll('.has-submenu.is-open').forEach(function(openItem) {
            if (openItem !== item) {
                openItem.classList.remove('is-open');
                var toggle = openItem.querySelector('.nav-toggle');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
            }
        });

        item.classList.toggle('is-open', willOpen);
        button.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }

    (function() {
        try {
            if (localStorage.getItem('dlhsSidebarCollapsed') === '1' && window.innerWidth > 1023) {
                // Only add to body when it's ready to avoid layout issues
                document.addEventListener('DOMContentLoaded', function() {
                    document.body.classList.add('dlhs-sidebar-collapsed');
                    dlhsSyncSidebarCollapseButton();
                });
            }
        } catch (e) {}
    })();

    document.addEventListener('DOMContentLoaded', function () {
        document.body.classList.add('dlhs-has-unified-sidebar');
        dlhsSyncSidebarButton();
        dlhsSyncSidebarCollapseButton();
        
        var legacyToggle = document.querySelector('.toggle-nav');
        if (legacyToggle) {
            legacyToggle.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();
                
                if (window.innerWidth > 1023) {
                    dlhsToggleSidebarCollapse();
                } else {
                    dlhsToggleSidebar();
                }
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

<style>
    .dlhs-unified-sidebar .sidebar-menu {
        overflow-y: auto;
        overflow-x: hidden;
        height: calc(100vh - 180px); /* Adjust based on brand and footer height */
        scrollbar-width: thin;
        scrollbar-color: rgba(255,255,255,0.1) transparent;
    }
    
    .dlhs-unified-sidebar .sidebar-menu::-webkit-scrollbar {
        width: 4px;
    }
    
    .dlhs-unified-sidebar .sidebar-menu::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.1);
        border-radius: 10px;
    }

    .has-submenu.is-open .submenu-caret {
        transform: rotate(180deg);
    }

    .nav-submenu {
        display: none;
        margin: 6px 0 6px 0;
        padding: 0 0 0 16px;
        background: transparent;
        gap: 4px;
    }
    
    .has-submenu.is-open .nav-submenu {
        display: grid;
    }
    
    .nav-sublink {
        display: flex;
        align-items: center;
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.72);
        transition: all 0.2s ease;
        text-decoration: none;
    }
    
    .nav-sublink:hover, .nav-sublink.is-active {
        color: #fff;
        background: rgba(255, 255, 255, 0.1);
        transform: translateX(4px);
    }
    
    .nav-sublink.is-active {
        background: rgba(0, 174, 239, 0.15);
        color: #00AEEF;
    }

    .nav-toggle {
        width: 100%;
        text-align: left;
        background: none;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        outline: none;
    }
    
    .dlhs-sidebar-collapse-button {
        position: fixed;
        left: 190px;
        top: 24px;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #020b1a;
        color: #fff;
        border: 1px solid rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 1001;
        transition: left 0.3s ease;
    }
    
    .dlhs-sidebar-collapsed .dlhs-sidebar-collapse-button {
        left: 90px;
    }
    
    .dlhs-sidebar-collapsed .dlhs-unified-sidebar,
    .dlhs-sidebar-collapsed #sidebar {
        width: 80px !important;
        overflow: hidden;
    }
    
    .dlhs-sidebar-collapsed .nav-link span,
    .dlhs-sidebar-collapsed .brand-copy,
    .dlhs-sidebar-collapsed .dlhs-sidepanel-usercopy,
    .dlhs-sidebar-collapsed .submenu-caret,
    .dlhs-sidebar-collapsed .nav-submenu {
        display: none !important;
    }
    
    .dlhs-sidebar-collapsed #main-content {
        margin-left: 80px !important;
    }
    
    @media (max-width: 1023px) {
        .dlhs-sidebar-collapse-button { display: none; }
        #main-content { margin-left: 0 !important; }
        
        .dlhs-unified-sidebar {
            position: fixed !important;
            inset: 0;
            width: 0;
            height: 100vh;
            z-index: 1300;
            pointer-events: none;
            transition: width 0.25s ease;
        }
        
        .dlhs-sidebar-open .dlhs-unified-sidebar {
            width: 286px;
            pointer-events: auto;
        }

        #sidebar {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 286px !important;
            max-width: calc(100vw - 48px);
            height: 100vh !important;
            transform: translateX(-108%);
            transition: transform 0.25s ease;
            z-index: 1301;
            pointer-events: auto;
        }

        body.dlhs-sidebar-open #sidebar {
            transform: translateX(0);
        }
        
        .dlhs-mobile-sidebar-backdrop {
            z-index: 1299;
        }
    }
</style>

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

        <nav class="sidebar-menu nav-group">
            <a class="nav-link<?php if ($currentPage === 'index.php') echo ' is-active'; ?>" href="index.php" onclick="dlhsToggleSidebar(false)">
                <i class="fa fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            <a class="nav-link<?php if ($currentPage === 'academic_settings.php') echo ' is-active'; ?>" href="academic_settings.php" onclick="setNext(); dlhsToggleSidebar(false)">
                <i class="fa fa-calendar"></i>
                <span>Academic Setup</span>
            </a>
            <a class="nav-link<?php if ($currentPage === 'addStaffForm.php') echo ' is-active'; ?>" href="addStaffForm.php" onclick="setNext(); dlhsToggleSidebar(false)">
                <i class="fa fa-user-plus"></i>
                <span>Staff</span>
            </a>
            <a class="nav-link<?php if ($currentPage === 'addYearGroupForm.php') echo ' is-active'; ?>" href="addYearGroupForm.php" onclick="setNext(); dlhsToggleSidebar(false)">
                <i class="fa fa-sitemap"></i>
                <span>Year Groups</span>
            </a>
            <a class="nav-link<?php if ($currentPage === 'addClassForm.php') echo ' is-active'; ?>" href="addClassForm.php" onclick="setNext(); dlhsToggleSidebar(false)">
                <i class="fa fa-building-o"></i>
                <span>Classes</span>
            </a>
            <a class="nav-link<?php if ($currentPage === 'addStudentForm.php') echo ' is-active'; ?>" href="addStudentForm.php" onclick="setNext(); dlhsToggleSidebar(false)">
                <i class="fa fa-graduation-cap"></i>
                <span>Students</span>
            </a>
            <a class="nav-link<?php if ($currentPage === 'addSubjectForm.php') echo ' is-active'; ?>" href="addSubjectForm.php" onclick="setNext(); dlhsToggleSidebar(false)">
                <i class="fa fa-book"></i>
                <span>Subjects</span>
            </a>
            <a class="nav-link<?php if ($currentPage === 'subjectAssignment.php') echo ' is-active'; ?>" href="subjectAssignment.php" onclick="setNext(); dlhsToggleSidebar(false)">
                <i class="fa fa-random"></i>
                <span>Subject Assignment</span>
            </a>

            <div class="nav-item has-submenu<?php echo $isQueries ? ' is-open' : ''; ?>">
                <button type="button" class="nav-link nav-toggle" aria-expanded="<?php echo $isQueries ? 'true' : 'false'; ?>">
                    <i class="fa fa-search"></i>
                    <span>Queries</span>
                    <i class="fa fa-chevron-down submenu-caret"></i>
                </button>
                <div class="nav-submenu">
                    <a class="nav-sublink<?php if ($currentPage === 'reportQueries.php') echo ' is-active'; ?>" href="reportQueries.php" onclick="dlhsToggleSidebar(false)">Query Reports</a>
                    <a class="nav-sublink<?php if ($currentPage === 'namesAndIds.php') echo ' is-active'; ?>" href="namesAndIds.php" onclick="dlhsToggleSidebar(false)">Names & IDs</a>
                </div>
            </div>

            <div class="nav-item has-submenu<?php echo $isTests ? ' is-open' : ''; ?>">
                <button type="button" class="nav-link nav-toggle" aria-expanded="<?php echo $isTests ? 'true' : 'false'; ?>">
                    <i class="fa fa-files-o"></i>
                    <span>Tests</span>
                    <i class="fa fa-chevron-down submenu-caret"></i>
                </button>
                <div class="nav-submenu">
                    <a class="nav-sublink<?php if ($currentPage === 'allTestsForm.php') echo ' is-active'; ?>" href="allTestsForm.php" onclick="dlhsToggleSidebar(false)">All Tests</a>
                    <a class="nav-sublink<?php if ($currentPage === 'view_teacher_questions.php') echo ' is-active'; ?>" href="view_teacher_questions.php" onclick="dlhsToggleSidebar(false)">Teacher Questions</a>
                    <a class="nav-sublink<?php if ($currentPage === 'yetToBeStartedTests.php') echo ' is-active'; ?>" href="yetToBeStartedTests.php" onclick="dlhsToggleSidebar(false)">Yet To Be Started</a>
                    <a class="nav-sublink<?php if ($currentPage === 'testsInProgress.php') echo ' is-active'; ?>" href="testsInProgress.php" onclick="dlhsToggleSidebar(false)">In Progress</a>
                    <a class="nav-sublink<?php if ($currentPage === 'endedTests.php') echo ' is-active'; ?>" href="endedTests.php" onclick="dlhsToggleSidebar(false)">Ended Tests</a>
                </div>
            </div>

            <a class="nav-link<?php if ($currentPage === 'question_document_hub.php') echo ' is-active'; ?>" href="question_document_hub.php" onclick="dlhsToggleSidebar(false)">
                <i class="fa fa-folder-open"></i>
                <span>Question Document Hub</span>
            </a>



            <div class="nav-item has-submenu<?php echo $isResultProcessing ? ' is-open' : ''; ?>">
                <button type="button" class="nav-link nav-toggle" aria-expanded="<?php echo $isResultProcessing ? 'true' : 'false'; ?>">
                    <i class="fa fa-bar-chart"></i>
                    <span>Results</span>
                    <i class="fa fa-chevron-down submenu-caret"></i>
                </button>
                <div class="nav-submenu">
                    <a class="nav-sublink<?php if ($currentPage === 'assignFormTeachersForm.php') echo ' is-active'; ?>" href="assignFormTeachersForm.php" onclick="dlhsToggleSidebar(false)">Form Teachers</a>
                    <a class="nav-sublink<?php if ($currentPage === 'result_config.php') echo ' is-active'; ?>" href="result_config.php" onclick="dlhsToggleSidebar(false)">Term Date Config</a>
                    <a class="nav-sublink<?php if ($currentPage === 'manageGradingForm.php') echo ' is-active'; ?>" href="manageGradingForm.php" onclick="dlhsToggleSidebar(false)">Grading</a>
                    <a class="nav-sublink<?php if ($currentPage === 'generateResultForm.php') echo ' is-active'; ?>" href="generateResultForm.php" onclick="dlhsToggleSidebar(false)">Generate Result</a>
                </div>
            </div>



            <div class="nav-item has-submenu<?php echo $isSettings ? ' is-open' : ''; ?>">
                <button type="button" class="nav-link nav-toggle" aria-expanded="<?php echo $isSettings ? 'true' : 'false'; ?>">
                    <i class="fa fa-cogs"></i>
                    <span>Settings</span>
                    <i class="fa fa-chevron-down submenu-caret"></i>
                </button>
                <div class="nav-submenu">

                    <a class="nav-sublink<?php if ($currentPage === 'cleanup_tests.php') echo ' is-active'; ?>" href="cleanup_tests.php" onclick="dlhsToggleSidebar(false)">Test Cleanup</a>
                    <a class="nav-sublink<?php if ($currentPage === 'security_violations.php') echo ' is-active'; ?>" href="security_violations.php" onclick="dlhsToggleSidebar(false)">Security</a>
                    <a class="nav-sublink<?php if ($currentPage === 'terminate_exam.php') echo ' is-active'; ?>" href="terminate_exam.php" onclick="dlhsToggleSidebar(false)">Terminate Exam</a>
                </div>
            </div>
        </nav>

        <div class="sidebar-footer dlhs-sidepanel-footer">
            <div class="identity dlhs-sidepanel-user">
                <div class="identity-badge dlhs-sidepanel-avatar"><?php echo htmlspecialchars(dlhsSidebarInitials($adminName)); ?></div>
                <div class="dlhs-sidepanel-usercopy">
                    <h3><?php echo htmlspecialchars($adminName); ?></h3>
                    <p>Admin Portal</p>
                </div>
            </div>
            <a class="dlhs-sidepanel-logout" href="logout.php" onclick="dlhsToggleSidebar(false)">
                <i class="fa fa-power-off"></i>
                <span>Sign out</span>
            </a>
        </div>
    </div>
</aside>

<!-- Help Guide -->
<link href="css/admin-help-guide.css" rel="stylesheet">
<script src="js/admin-help-guide.js" defer></script>
