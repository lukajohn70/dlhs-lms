(function(window, document) {
    'use strict';

    /* ─────────────────────────────────────────────
       DLHS Admin Help Guide — Context-based help
       ───────────────────────────────────────────── */

    var helpData = {
        'index.php': {
            title: 'Admin Dashboard',
            icon: 'fa-th-large',
            intro: 'Welcome to the Admin Portal. This dashboard provides a high-level overview of the entire system.',
            steps: [
                { icon: 'fa-users', text: 'Monitor <b>Online Users</b> to see real-time activity across the platform.' },
                { icon: 'fa-calendar', text: 'Manage <b>Academic Setup</b> to configure sessions, terms, and dates.' },
                { icon: 'fa-shield', text: 'Access <b>Invigilation</b> to oversee active tests and prevent malpractice.' },
                { icon: 'fa-database', text: 'Use <b>Backup Management</b> in Settings to ensure data safety.' }
            ]
        },

        'addStaffForm.php': {
            title: 'Staff Management',
            icon: 'fa-user-plus',
            intro: 'Register and manage teaching and administrative staff members.',
            steps: [
                { icon: 'fa-plus', text: 'Fill out the form to add a new staff member. Ensure the email is unique.' },
                { icon: 'fa-upload', text: 'Use the <b>CSV Upload</b> feature to add multiple staff members at once.' },
                { icon: 'fa-pencil', text: 'Edit staff details or update their passport pictures from the table below.' },
                { icon: 'fa-key', text: 'Staff members will use their email and the password you set to log in.' }
            ]
        },

        'addStudentForm.php': {
            title: 'Student Management',
            icon: 'fa-graduation-cap',
            intro: 'Manage the student registry, including admissions and class assignments.',
            steps: [
                { icon: 'fa-user-plus', text: 'Add individual students with their admission numbers and passports.' },
                { icon: 'fa-file-excel-o', text: 'Bulk upload student records using the <b>CSV Template</b> provided.' },
                { icon: 'fa-filter', text: 'Filter students by year group or class to find specific records quickly.' },
                { icon: 'fa-trash', text: 'You can delete students based on specific criteria like graduation year.' }
            ]
        },

        'academic_settings.php': {
            title: 'Academic Setup',
            icon: 'fa-calendar',
            intro: 'Configure the foundational settings for the academic year.',
            steps: [
                { icon: 'fa-clock-o', text: 'Set the current <b>Academic Session</b> and <b>Term</b>.' },
                { icon: 'fa-calendar-check-o', text: 'Define the start and end dates for terms to control system availability.' },
                { icon: 'fa-cogs', text: 'These settings affect how tests are grouped and how results are generated.' }
            ]
        },

        'allTestsForm.php': {
            title: 'Test Management',
            icon: 'fa-files-o',
            intro: 'Oversee all tests created by teachers across all subjects.',
            steps: [
                { icon: 'fa-search', text: 'Search for any test by name, subject, or teacher.' },
                { icon: 'fa-play', text: '<b>Start</b> or <b>End</b> tests manually if needed.' },
                { icon: 'fa-clock-o', text: '<b>Reschedule</b> tests that haven\'t started yet.' },
                { icon: 'fa-trash', text: '<b>Delete</b> tests (use with caution as this removes all associated results).' }
            ]
        },

        'view_teacher_questions.php': {
            title: 'Question Bank Viewer',
            icon: 'fa-question-circle',
            intro: 'Review questions created by teachers to ensure quality and standards.',
            steps: [
                { icon: 'fa-filter', text: 'Filter by teacher, subject, or year group to find specific tests.' },
                { icon: 'fa-eye', text: 'Click <b>View Questions</b> to see the full content of any test.' },
                { icon: 'fa-check-circle', text: 'Verify correct answers and mark allocations.' },
                { icon: 'fa-download', text: 'Export questions to PDF or Excel for moderation meetings.' }
            ]
        },

        'testInvigilation.php': {
            title: 'Admin Invigilation',
            icon: 'fa-shield',
            intro: 'Monitor live examinations to ensure academic integrity.',
            steps: [
                { icon: 'fa-desktop', text: 'See all students currently taking a test and their progress.' },
                { icon: 'fa-warning', text: 'Identify students who lose connection or attempt unauthorized actions.' },
                { icon: 'fa-plus-circle', text: 'Add extra time to a test globally or for specific students.' },
                { icon: 'fa-stop', text: 'Force-submit tests for students who have finished or exceeded their time.' }
            ]
        },

        'resultSettingsForm.php': {
            title: 'Result Configuration',
            icon: 'fa-bar-chart',
            intro: 'Set up how student results are calculated and displayed on report cards.',
            steps: [
                { icon: 'fa-percent', text: 'Configure weights for CAT 1, CAT 2, and Exams.' },
                { icon: 'fa-list', text: 'Set up grade scales (A, B, C, etc.) and their corresponding marks.' },
                { icon: 'fa-check', text: 'Enable or disable result viewing for students and parents.' }
            ]
        },

        'backup_management.php': {
            title: 'System Backups',
            icon: 'fa-database',
            intro: 'Protect your data by creating and managing system backups.',
            steps: [
                { icon: 'fa-download', text: 'Generate a full database backup at any time.' },
                { icon: 'fa-history', text: 'Restore from a previous backup in case of data loss.' },
                { icon: 'fa-cloud-download', text: 'Download backups to local storage for off-site security.' }
            ]
        },

        'security_violations.php': {
            title: 'Security Logs',
            icon: 'fa-lock',
            intro: 'Monitor suspicious activity and potential security threats.',
            steps: [
                { icon: 'fa-exclamation-triangle', text: 'Review logs of students who attempted to leave the exam screen.' },
                { icon: 'fa-ban', text: 'Identify multiple login attempts or unauthorized access patterns.' },
                { icon: 'fa-user-secret', text: 'Keep track of IP addresses and device information for all logins.' }
            ]
        },

        'changePassword.php': {
            title: 'Admin Security',
            icon: 'fa-key',
            intro: 'Update your administrative password.',
            steps: [
                { icon: 'fa-magic', text: 'Use the <b>Generate Password</b> button to create a strong, secure password.' },
                { icon: 'fa-lock', text: 'Your new password will be applied immediately.' },
                { icon: 'fa-exclamation-circle', text: 'Remember to store your password securely as it provides full access to the portal.' }
            ]
        }
    };

    var defaultHelp = {
        title: 'Admin Help',
        icon: 'fa-question-circle',
        intro: 'General tips for managing the DLHS Admin Portal.',
        steps: [
            { icon: 'fa-bars', text: 'The sidebar provides access to all administrative modules.' },
            { icon: 'fa-cog', text: 'Most settings are global and affect all staff and students.' },
            { icon: 'fa-user-md', text: 'Need technical support? Contact the IT department or lead developer.' }
        ]
    };

    function getCurrentPage() {
        var path = window.location.pathname;
        var parts = path.split('/');
        return parts[parts.length - 1] || 'index.php';
    }

    function getHelpForCurrentPage() {
        var page = getCurrentPage();
        return helpData[page] || defaultHelp;
    }

    function buildHelpHTML(help) {
        var html = '';
        html += '<div class="dlhs-help-header">';
        html += '  <div class="dlhs-help-header-text">';
        html += '    <i class="fa ' + help.icon + '"></i>';
        html += '    <h3>' + help.title + '</h3>';
        html += '  </div>';
        html += '  <button type="button" class="dlhs-help-close" id="dlhsHelpClose" aria-label="Close help">';
        html += '    <i class="fa fa-times"></i>';
        html += '  </button>';
        html += '</div>';
        html += '<div class="dlhs-help-body">';
        html += '  <p class="dlhs-help-intro">' + help.intro + '</p>';
        html += '  <div class="dlhs-help-steps">';

        for (var i = 0; i < help.steps.length; i++) {
            var step = help.steps[i];
            html += '<div class="dlhs-help-step">';
            html += '  <div class="dlhs-help-step-icon"><i class="fa ' + step.icon + '"></i></div>';
            html += '  <div class="dlhs-help-step-text">' + step.text + '</div>';
            html += '</div>';
        }

        html += '  </div>';
        html += '</div>';
        html += '<div class="dlhs-help-footer">';
        html += '  <span class="dlhs-help-page-badge"><i class="fa fa-file-o"></i> ' + getCurrentPage() + '</span>';
        html += '</div>';

        return html;
    }

    function initHelpGuide() {
        var helpBtn = document.createElement('button');
        helpBtn.type = 'button';
        helpBtn.className = 'dlhs-help-fab';
        helpBtn.id = 'dlhsHelpFab';
        helpBtn.innerHTML = '<i class="fa fa-question"></i>';
        document.body.appendChild(helpBtn);

        var helpPanel = document.createElement('div');
        helpPanel.className = 'dlhs-help-panel';
        helpPanel.id = 'dlhsHelpPanel';
        document.body.appendChild(helpPanel);

        var backdrop = document.createElement('div');
        backdrop.className = 'dlhs-help-backdrop';
        backdrop.id = 'dlhsHelpBackdrop';
        document.body.appendChild(backdrop);

        var help = getHelpForCurrentPage();
        helpPanel.innerHTML = buildHelpHTML(help);

        helpBtn.addEventListener('click', function() {
            toggleHelp(true);
        });

        backdrop.addEventListener('click', function() {
            toggleHelp(false);
        });

        helpPanel.querySelector('#dlhsHelpClose').addEventListener('click', function() {
            toggleHelp(false);
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                toggleHelp(false);
            }
        });
    }

    function toggleHelp(show) {
        var panel = document.getElementById('dlhsHelpPanel');
        var backdrop = document.getElementById('dlhsHelpBackdrop');
        var fab = document.getElementById('dlhsHelpFab');

        if (!panel || !backdrop) return;

        var isOpen = panel.classList.contains('is-open');
        var shouldOpen = typeof show === 'boolean' ? show : !isOpen;

        panel.classList.toggle('is-open', shouldOpen);
        backdrop.classList.toggle('is-visible', shouldOpen);
        if (fab) fab.classList.toggle('is-hidden', shouldOpen);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHelpGuide);
    } else {
        initHelpGuide();
    }

    window.dlhsToggleHelp = toggleHelp;

})(window, document);
