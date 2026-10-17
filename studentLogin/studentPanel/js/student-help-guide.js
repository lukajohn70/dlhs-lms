(function(window, document) {
    'use strict';

    /* ─────────────────────────────────────────────
       DLHS Student Help Guide — Context-based help
       ───────────────────────────────────────────── */

    var helpData = {
        'index.php': {
            title: 'Dashboard',
            icon: 'fa-th-large',
            intro: 'Welcome to your learning dashboard. Here you can see your test summary and upcoming assessments.',
            steps: [
                { icon: 'fa-th-large', text: '<b>Overview:</b> The dashboard shows how many tests you have not started, in progress, or completed.' },
                { icon: 'fa-calendar', text: '<b>Calendar:</b> Check the calendar for upcoming test dates and school events.' },
                { icon: 'fa-hourglass-half', text: '<b>Quick Links:</b> Use the quick action buttons to jump straight to your tests or change your password.' }
            ]
        },

        'pendingTests.php': {
            title: 'My Tests',
            icon: 'fa-hourglass-half',
            intro: 'View and start your assigned assessments from this page.',
            steps: [
                { icon: 'fa-search', text: '<b>Search:</b> Use the search bar to find a specific test by name, subject, or date.' },
                { icon: 'fa-play-circle', text: '<b>Starting a Test:</b> Click <b>Start Objective Test</b> or <b>Start Essay</b> to begin an assessment.' },
                { icon: 'fa-exclamation-triangle', text: '<b>Test Status:</b> If a test is "Not Enabled", you must wait for the invigilator to start it globally.' },
                { icon: 'fa-clock-o', text: '<b>Attendance:</b> If you get a message about attendance, wait for the teacher to mark you present.' }
            ]
        },

        'changePassword.php': {
            title: 'Change Password',
            icon: 'fa-key',
            intro: 'Update your account password to keep your student portal secure.',
            steps: [
                { icon: 'fa-lock', text: '<b>Current Password:</b> Enter your current password first for verification.' },
                { icon: 'fa-key', text: '<b>New Password:</b> Choose a strong password that you can remember.' },
                { icon: 'fa-check-circle', text: '<b>Confirm:</b> Re-type the new password and click the change button.' }
            ]
        }
    };

    var defaultHelp = {
        title: 'Help',
        icon: 'fa-question-circle',
        intro: 'Need help? Here are some general tips for using your student portal.',
        steps: [
            { icon: 'fa-bars', text: 'Use the <b>sidebar</b> on the left to navigate between different sections.' },
            { icon: 'fa-sign-out', text: 'Always <b>Sign out</b> when you are finished, especially on shared computers.' },
            { icon: 'fa-desktop', text: 'For the best experience during tests, ensure you have a stable internet connection.' }
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
        if (document.getElementById('dlhsHelpFab')) return;

        var helpBtn = document.createElement('button');
        helpBtn.type = 'button';
        helpBtn.className = 'dlhs-help-fab';
        helpBtn.id = 'dlhsHelpFab';
        helpBtn.title = 'Help Guide';
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

        var closeBtn = helpPanel.querySelector('#dlhsHelpClose');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                toggleHelp(false);
            });
        }

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
