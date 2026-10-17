(function(window, document) {
    'use strict';

    /* ─────────────────────────────────────────────
       DLHS Staff Help Guide — Context-based help
       ───────────────────────────────────────────── */

    var helpData = {
        'index.php': {
            title: 'Dashboard',
            icon: 'fa-th-large',
            intro: 'This is your main dashboard. It gives you a quick overview of your tests, notifications, and activity.',
            steps: [
                { icon: 'fa-eye', text: 'View a summary of your pending, in-progress, and completed tests from the notification bell in the top bar.' },
                { icon: 'fa-pencil-square-o', text: 'Use the sidebar to navigate to <b>Create Test</b>, <b>Questions</b>, <b>Test Access</b>, and other sections.' },
                { icon: 'fa-bar-chart', text: 'Check your test results and examinee status from the <b>Results</b> link in the sidebar.' },
                { icon: 'fa-key', text: 'To change your password, click your name in the top-right corner and select <b>Change Password</b>.' }
            ]
        },

        'addTestForm.php': {
            title: 'Create / Edit Tests',
            icon: 'fa-pencil-square-o',
            intro: 'Create new assessments or modify existing ones. The form is divided into clear sections to guide you through the process.',
            steps: [
                { icon: 'fa-book', text: '<b>Step 1 — Academic Session:</b> Select the current <b>Academic Session</b> (e.g., 2024/2025). The current session is pre-selected for you.' },
                { icon: 'fa-graduation-cap', text: '<b>Step 2 — Subject:</b> Choose your <b>Subject</b>. The dropdown shows only subjects assigned to you, along with the year group (e.g., "ECONOMICS SS1"). If a year group has no subject assigned to you, it will not appear.' },
                { icon: 'fa-users', text: '<b>Step 3 — Class Arm:</b> Pick the specific <b>Class (Arm)</b> for the test (e.g., "SCIENCE A"). If you teach multiple arms, you can select <b>ALL ASSIGNED ARMS</b> to create the test for all of them at once.' },
                { icon: 'fa-list-ul', text: '<b>Step 4 — Test Type:</b> Select the type (e.g., CAT 1, EXAM, MOCK). For <i>OTHER</i>, type a custom label. For <i>MOCK</i>, select the paper number.' },
                { icon: 'fa-calendar', text: '<b>Step 5 — Schedule:</b> Set the <b>test date</b> and <b>duration in minutes</b>. The start time can be customized during invigilation.' },
                { icon: 'fa-random', text: '<b>Step 6 — Options:</b> Choose review, essay, and randomization settings. Randomizing questions and answer options is recommended for exam security.' },
                { icon: 'fa-magic', text: '<b>Auto-Generated Name:</b> The test name is automatically built from your selections (e.g., "2024/2025 ECONOMICS SS1 SCIENCE A CAT 1"). You do not need to type it.' },
                { icon: 'fa-arrow-right', text: '<b>Next Steps:</b> After creating a test, you will be redirected to <b>Add Students</b>. You must add students before you can add questions.' }
            ]
        },

        'addQuestionForm.php': {
            title: 'Add Questions',
            icon: 'fa-list-alt',
            intro: 'Add objective and essay questions to your tests from this page.',
            steps: [
                { icon: 'fa-check-square-o', text: '<b>Step 1:</b> Select the test you want to add questions to from the dropdown. Tests without students will be disabled.' },
                { icon: 'fa-upload', text: '<b>CSV Import:</b> Upload a CSV file with multiple questions at once. Download the template first to see the required format.' },
                { icon: 'fa-file-word-o', text: '<b>Word Import:</b> Upload a .docx file and the system will extract questions automatically.' },
                { icon: 'fa-pencil', text: '<b>Manual Entry:</b> Type the question in the rich text editor, fill in Options A–D (or E), select the correct answer, and set the mark.' },
                { icon: 'fa-magic', text: '<b>Split Tool:</b> If you paste the full question with options into the question box (e.g. "What is 2+2? A. 4 B. 3 C. 2 D. 1"), click the <b>Split into options</b> button to auto-fill the option fields.' },
                { icon: 'fa-clone', text: '<b>Shared Material:</b> Use the "Shared Passage" section when multiple questions share the same reading passage, image, or table.' },
                { icon: 'fa-eye', text: 'Click <b>Preview test questions</b> to see all questions added so far. You can edit or delete from there.' }
            ]
        },

        'viewQuestionForm.php': {
            title: 'View / Manage Questions',
            icon: 'fa-list-alt',
            intro: 'View, edit, and manage all questions for your tests.',
            steps: [
                { icon: 'fa-filter', text: 'Select a test from the dropdown to load its questions.' },
                { icon: 'fa-pencil', text: 'Click the <b>edit icon</b> (pencil) on any question row to modify it.' },
                { icon: 'fa-trash', text: 'Click the <b>delete icon</b> (trash) to remove a question. You\'ll be asked to confirm.' },
                { icon: 'fa-download', text: 'Use the export buttons (CSV, Excel, PDF, Print) to download your questions.' }
            ]
        },

        'editQuestionForm.php': {
            title: 'Edit Question',
            icon: 'fa-pencil',
            intro: 'Modify an existing question\'s text, options, correct answer, or mark.',
            steps: [
                { icon: 'fa-pencil', text: 'Edit the question text and options using the rich text editors.' },
                { icon: 'fa-check-circle', text: 'Make sure to select the correct answer radio button.' },
                { icon: 'fa-save', text: 'Click <b>Update Question</b> to save your changes.' }
            ]
        },

        'addStudentsToTestForm.php': {
            title: 'Add Students to Test',
            icon: 'fa-user-plus',
            intro: 'Assign students to a test so they can take it. Students must be added before questions can be uploaded.',
            steps: [
                { icon: 'fa-check-square-o', text: '<b>Step 1:</b> Select the test from the dropdown at the top.' },
                { icon: 'fa-users', text: '<b>Step 2:</b> Choose the year group and class to filter students.' },
                { icon: 'fa-check', text: '<b>Step 3:</b> Use the checkboxes to select individual students, or use <b>Select All</b>.' },
                { icon: 'fa-plus', text: '<b>Step 4:</b> Click <b>Add Selected Students</b> to assign them to the test.' },
                { icon: 'fa-exclamation-circle', text: '<b>Note:</b> You must add at least one student before you can add questions to the test.' }
            ]
        },

        'manageStudentsAndTestForm.php': {
            title: 'Manage Test Access',
            icon: 'fa-users',
            intro: 'View and manage which students are assigned to each test. Remove students or adjust individual timing.',
            steps: [
                { icon: 'fa-filter', text: 'Select a test to see which students are currently assigned.' },
                { icon: 'fa-trash', text: 'Remove students from the test by selecting them and clicking <b>Remove</b>.' },
                { icon: 'fa-clock-o', text: 'Adjust individual student timing if needed.' }
            ]
        },



        'examineesStatus.php': {
            title: 'Examinee Status & Results',
            icon: 'fa-bar-chart',
            intro: 'Monitor which students have started, completed, or are currently taking your tests.',
            steps: [
                { icon: 'fa-filter', text: 'Select a test to see the status of all assigned students.' },
                { icon: 'fa-circle', text: 'Status indicators: <b>Green</b> = Completed, <b>Yellow</b> = In Progress, <b>Grey</b> = Not Started.' },
                { icon: 'fa-download', text: 'Export results using the CSV, Excel, or PDF buttons.' },
                { icon: 'fa-eye', text: 'Click on a student to see their detailed question-by-question results.' }
            ]
        },

        'invigilations.php': {
            title: 'Invigilation',
            icon: 'fa-shield',
            intro: 'Monitor active tests in real time. See which students are online and track suspicious activity.',
            steps: [
                { icon: 'fa-play', text: 'Select a test that is currently <b>In Progress</b> to begin monitoring.' },
                { icon: 'fa-desktop', text: 'The live dashboard shows each student\'s progress, time remaining, and connection status.' },
                { icon: 'fa-clock-o', text: 'You can add or subtract time for individual students or all students at once.' },
                { icon: 'fa-stop', text: 'Use <b>End Test</b> to force-submit all remaining students when the test period is over.' }
            ]
        },

        'modifyTimeBeforeTestStarts.php': {
            title: 'Timing & Reschedule',
            icon: 'fa-clock-o',
            intro: 'Modify test timing or reschedule a test to a different date and time.',
            steps: [
                { icon: 'fa-filter', text: 'Select the test you want to modify.' },
                { icon: 'fa-clock-o', text: 'Adjust the overall test duration or individual student times.' },
                { icon: 'fa-calendar', text: 'Reschedule the test start time if needed.' }
            ]
        },

        'endTest.php': {
            title: 'End / Cancel Test',
            icon: 'fa-stop-circle',
            intro: 'End an active test or cancel a pending one.',
            steps: [
                { icon: 'fa-filter', text: 'Select the test from the dropdown.' },
                { icon: 'fa-stop', text: 'Click <b>End Test</b> to force-submit all students and close the test.' },
                { icon: 'fa-exclamation-triangle', text: '<b>Warning:</b> Ending a test cannot be undone. All student submissions will be finalized.' }
            ]
        },

        'file_management.php': {
            title: 'File Sharing Hub',
            icon: 'fa-folder-open',
            intro: 'Send documents, slides, and study resources to your students easily.',
            steps: [
                { icon: 'fa-cloud-upload', text: '<b>Drag & Drop Upload:</b> Drop your file into the upload zone, set a title and subject, then share it.' },
                { icon: 'fa-users', text: '<b>Targeted Sharing:</b> Choose whether to share globally with <i>All Students</i>, targeting a <i>Specific Class</i>, or selection of <i>Specific Students</i>.' },
                { icon: 'fa-filter', text: '<b>Assigned Targets Only:</b> The class dropdown only populates classes that are allocated and assigned to you.' },
                { icon: 'fa-trash', text: '<b>Stop Sharing:</b> Review your shared history in the real-time search list (supports Table/Grid view toggles) and click delete to stop sharing instantly.' }
            ]
        },



        'changePassword.php': {
            title: 'Change Password',
            icon: 'fa-key',
            intro: 'Update your login password for security.',
            steps: [
                { icon: 'fa-magic', text: 'Use the <b>Generate Password</b> button to create a strong, cryptographically secure password instantly.' },
                { icon: 'fa-lock', text: 'Enter your <b>current password</b> for verification.' },
                { icon: 'fa-key', text: 'Enter your <b>new password</b> (must be at least 6 characters).' },
                { icon: 'fa-check', text: 'Re-enter the new password to confirm, then click <b>Change Password</b>.' }
            ]
        },

        'myAssignments.php': {
            title: 'My Assignments',
            icon: 'fa-tasks',
            intro: 'View and manage assignments for your classes.',
            steps: [
                { icon: 'fa-filter', text: 'Filter assignments by class and subject.' },
                { icon: 'fa-eye', text: 'Click on an assignment to view student submissions.' },
                { icon: 'fa-check-circle', text: 'Grade submissions and provide feedback.' }
            ]
        }
    };

    // Fallback for pages not explicitly listed
    var defaultHelp = {
        title: 'Help',
        icon: 'fa-question-circle',
        intro: 'Need help? Here are some general tips for using the staff panel.',
        steps: [
            { icon: 'fa-bars', text: 'Use the <b>sidebar</b> on the left to navigate between different sections.' },
            { icon: 'fa-bell', text: 'The <b>notification bell</b> in the top bar shows your pending, in-progress, and completed tests.' },
            { icon: 'fa-user', text: 'Click your <b>name</b> in the top-right to access your profile, change password, or sign out.' },
            { icon: 'fa-question-circle', text: 'Each page has its own contextual help. Navigate to the page you need help with and click this button again.' }
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
        // Create the floating help button
        var helpBtn = document.createElement('button');
        helpBtn.type = 'button';
        helpBtn.className = 'dlhs-help-fab';
        helpBtn.id = 'dlhsHelpFab';
        helpBtn.setAttribute('aria-label', 'Open help guide');
        helpBtn.title = 'Help Guide';
        helpBtn.innerHTML = '<i class="fa fa-question"></i>';
        document.body.appendChild(helpBtn);

        // Create the help panel
        var helpPanel = document.createElement('div');
        helpPanel.className = 'dlhs-help-panel';
        helpPanel.id = 'dlhsHelpPanel';
        document.body.appendChild(helpPanel);

        // Create backdrop
        var backdrop = document.createElement('div');
        backdrop.className = 'dlhs-help-backdrop';
        backdrop.id = 'dlhsHelpBackdrop';
        document.body.appendChild(backdrop);

        // Populate panel
        var help = getHelpForCurrentPage();
        helpPanel.innerHTML = buildHelpHTML(help);

        // Event listeners
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

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHelpGuide);
    } else {
        initHelpGuide();
    }

    window.dlhsToggleHelp = toggleHelp;

})(window, document);
