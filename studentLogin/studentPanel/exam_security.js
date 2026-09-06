/**
 * Exam Security System
 * Prevents screenshots, screen recording, and cheating attempts
 */

(function() {
    'use strict';
    
    // Security Configuration
    const SECURITY_CONFIG = {
        enableFullscreen: true,
        blockScreenshots: true,
        blockRightClick: true,
        blockKeyboardShortcuts: true,
        blockTextSelection: true,
        detectTabSwitch: true,
        detectDevTools: true,
        watermarkEnabled: false,  // DISABLED - Watermark removed
        maxViolations: 999,  // Effectively disabled
        autoSubmitOnViolation: false  // DISABLED - Admin will manually terminate if needed
    };
    
    let violationCount = 0;
    let studentName = '';
    let testName = '';
    let fullscreenLocked = false;  // Track fullscreen state for beforeunload warning

    // Minimal debug gating: set `window.APP_DEBUG = true` on the page to enable verbose console.log output.
    const APP_DEBUG = window.APP_DEBUG === true;
    if (!APP_DEBUG) {
        console.log = function(){};
    }
    
    // Store event handler references for removal
    let handleContextMenu, handleCopy, handleCut, handlePaste;
    
    // Initialize security when document is ready
    function initSecurity(studentNameParam, testNameParam) {
        studentName = studentNameParam || 'Student';
        testName = testNameParam || 'Test';
        
        console.log('%c🔒 EXAM SECURITY ENABLED', 'color: red; font-size: 20px; font-weight: bold;');
        console.log('%c⚠️ This exam is being monitored. All activities are logged.', 'color: orange; font-size: 14px;');
        
        if (SECURITY_CONFIG.blockRightClick) disableRightClick();
        if (SECURITY_CONFIG.blockKeyboardShortcuts) blockKeyboardShortcuts();
        if (SECURITY_CONFIG.blockTextSelection) disableTextSelection();
        if (SECURITY_CONFIG.detectTabSwitch) detectWindowSwitch();
        if (SECURITY_CONFIG.detectDevTools) detectDevToolsOpen();
        if (SECURITY_CONFIG.blockScreenshots) preventScreenshots();
        if (SECURITY_CONFIG.watermarkEnabled) addWatermark();
        if (SECURITY_CONFIG.enableFullscreen) enforceFullscreen();
        
        // Monitor copy/paste
        blockCopyPaste();
        
        // Disable print
        disablePrint();
        
        // Security notice removed - violations logged silently
        // showSecurityNotice();
    }
    
    // 1. DISABLE RIGHT CLICK
    function disableRightClick() {
        handleContextMenu = function(e) {
            e.preventDefault();
            logViolation('Right-click attempted');
            // Warning removed - logged silently
            return false;
        };
        document.addEventListener('contextmenu', handleContextMenu);
    }
    
    // 2. BLOCK KEYBOARD SHORTCUTS
    function blockKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Print Screen
            if (e.key === 'PrintScreen') {
                e.preventDefault();
                logViolation('PrintScreen key pressed');
                // Warning removed - logged silently
                navigator.clipboard.writeText('');
                return false;
            }
            
            // Ctrl/Cmd combinations
            if (e.ctrlKey || e.metaKey) {
                // Ctrl+P (Print)
                if (e.key === 'p') {
                    e.preventDefault();
                    logViolation('Print shortcut attempted (Ctrl+P)');
                    // Warning removed - logged silently
                    return false;
                }
                // Ctrl+S (Save)
                if (e.key === 's') {
                    e.preventDefault();
                    logViolation('Save shortcut attempted (Ctrl+S)');
                    // Warning removed - logged silently
                    return false;
                }
                // Ctrl+C (Copy)
                if (e.key === 'c') {
                    e.preventDefault();
                    logViolation('Copy shortcut attempted (Ctrl+C)');
                    // Warning removed - logged silently
                    return false;
                }
                // Ctrl+V (Paste)
                if (e.key === 'v') {
                    e.preventDefault();
                    logViolation('Paste shortcut attempted (Ctrl+V)');
                    return false;
                }
                // Ctrl+U (View Source)
                if (e.key === 'u') {
                    e.preventDefault();
                    logViolation('View source attempted (Ctrl+U)');
                    return false;
                }
                // Ctrl+Shift+I (DevTools)
                if (e.shiftKey && e.key === 'I') {
                    e.preventDefault();
                    logViolation('DevTools shortcut attempted (Ctrl+Shift+I)');
                    return false;
                }
                // Ctrl+Shift+J (Console)
                if (e.shiftKey && e.key === 'J') {
                    e.preventDefault();
                    logViolation('Console shortcut attempted (Ctrl+Shift+J)');
                    return false;
                }
                // Ctrl+Shift+C (Inspect)
                if (e.shiftKey && e.key === 'C') {
                    e.preventDefault();
                    logViolation('Inspect shortcut attempted (Ctrl+Shift+C)');
                    return false;
                }
            }
            
            // F12 (DevTools)
            if (e.key === 'F12') {
                e.preventDefault();
                logViolation('F12 DevTools key pressed');
                return false;
            }
            
            // Escape (Exits fullscreen)
            if (e.key === 'Escape') {
                e.preventDefault();
                logViolation('Escape key pressed - attempt to exit fullscreen');
                // Warning removed - logged silently
                return false;
            }
            
            // Windows Key + Shift + S (Windows Snipping Tool)
            if (e.key === 'Meta' || (e.shiftKey && e.key === 's')) {
                // Note: Can't fully block Windows key, but we can log it
                logViolation('Possible snipping tool shortcut detected');
            }
        });
    }
    
    // 3. DISABLE TEXT SELECTION
    function disableTextSelection() {
        const style = document.createElement('style');
        style.innerHTML = `
            body, * {
                -webkit-user-select: none !important;
                -moz-user-select: none !important;
                -ms-user-select: none !important;
                user-select: none !important;
            }
            input[type="radio"], input[type="checkbox"], textarea {
                -webkit-user-select: auto !important;
                -moz-user-select: auto !important;
                -ms-user-select: auto !important;
                user-select: auto !important;
            }
        `;
        document.head.appendChild(style);
    }
    
    // 4. DETECT WINDOW/TAB SWITCHING AND MINIMIZE (Enhanced Prevention)
    function detectWindowSwitch() {
        let refocusAttempts = 0;
        
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                refocusAttempts++;
                
                // Log the violation (this increments violationCount and checks for auto-submit)
                logViolation('Tab/Window switched away or browser minimized (Attempt #' + refocusAttempts + ')');
                // Warning removed - logged silently
                
                // Immediately try to bring them back to the exam
                setTimeout(function() {
                    if (document.hidden) {
                        // Still hidden, try to refocus
                        window.focus();
                        
                        // Audio alert removed - logged silently
                        
                        // Alert removed - logged silently
                    }
                }, 100);
            } else {
                // They came back - logged silently
                if (refocusAttempts > 0) {
                    console.log('Student returned to exam after ' + refocusAttempts + ' violations');
                }
            }
        });
        
        window.addEventListener('blur', function() {
            // Window lost focus - try to refocus
            if (fullscreenLocked) {
                setTimeout(function() {
                    window.focus();
                }, 100);
            }
        });
        
        window.addEventListener('beforeunload', handleBeforeUnload);

        // Security Violation Handler
        window.logSecurityViolation = function(type, details) {
            logViolation(type + ': ' + details);
        };
    }
    
    // 5. DETECT DEVTOOLS OPEN
    function detectDevToolsOpen() {
        const threshold = 160;
        
        setInterval(function() {
            if (window.outerWidth - window.innerWidth > threshold || 
                window.outerHeight - window.innerHeight > threshold) {
                logViolation('DevTools possibly opened');
                // Warning removed - logged silently
            }
        }, 1000);
    }
    
    // 6. PREVENT SCREENSHOTS
    function preventScreenshots() {
        // Detect PrintScreen key (already handled in keyboard shortcuts)
        
        // Clear clipboard on PrintScreen
        window.addEventListener('keyup', function(e) {
            if (e.key === 'PrintScreen') {
                navigator.clipboard.writeText('');
                logViolation('PrintScreen key released - clipboard cleared');
            }
        });
        
        // Detect focus loss (possible screenshot tool)
        let blurTimeout;
        window.addEventListener('blur', function() {
            blurTimeout = setTimeout(function() {
                logViolation('Window blur detected - possible screenshot tool');
            }, 100);
        });
        
        window.addEventListener('focus', function() {
            clearTimeout(blurTimeout);
        });
    }
    
    // 7. ADD WATERMARK
    function addWatermark() {
        const watermark = document.createElement('div');
        watermark.id = 'exam-watermark';
        watermark.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
            opacity: 0.1;
            font-size: 20px;
            color: #000;
            transform: rotate(-45deg);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
        `;
        
        const now = new Date();
        const watermarkText = studentName + ' | ' + testName + ' | ' + now.toLocaleString();
        
        for (let i = 0; i < 50; i++) {
            const text = document.createElement('div');
            text.textContent = watermarkText;
            text.style.cssText = 'width: 100%; text-align: center; padding: 20px;';
            watermark.appendChild(text);
        }
        
        document.body.appendChild(watermark);
    }
    
    // 8. ENFORCE FULLSCREEN (Enhanced)
    function enforceFullscreen() {
        let fullscreenExitCount = 0;
        // fullscreenLocked is now a module-level variable (declared at top)
        
        function requestFullscreen() {
            const elem = document.documentElement;
            
            if (elem.requestFullscreen) {
                elem.requestFullscreen({ navigationUI: "hide" }).then(() => {
                    fullscreenLocked = true;
                    console.log('%c🔒 FULLSCREEN LOCKED', 'color: green; font-weight: bold;');
                }).catch(err => {
                    console.error('Fullscreen request failed:', err);
                    // Keep trying if user denied
                    setTimeout(requestFullscreen, 3000);
                });
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
                fullscreenLocked = true;
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
                fullscreenLocked = true;
            } else if (elem.mozRequestFullScreen) {
                elem.mozRequestFullScreen();
                fullscreenLocked = true;
            }
        }
        
        // Force fullscreen immediately after security notice
        setTimeout(requestFullscreen, 500);
        
        // Monitor ALL fullscreen exit events
        document.addEventListener('fullscreenchange', handleFullscreenExit);
        document.addEventListener('webkitfullscreenchange', handleFullscreenExit);
        document.addEventListener('mozfullscreenchange', handleFullscreenExit);
        document.addEventListener('MSFullscreenChange', handleFullscreenExit);
        
        function handleFullscreenExit() {
            const isFullscreen = document.fullscreenElement || 
                                document.webkitFullscreenElement || 
                                document.mozFullScreenElement || 
                                document.msFullscreenElement;
            
            if (!isFullscreen && fullscreenLocked) {
                fullscreenExitCount++;
                logViolation('Attempted to exit fullscreen (Count: ' + fullscreenExitCount + ')');
                
                // Warning removed - logged silently
                
                // IMMEDIATELY re-request fullscreen (no delay!)
                requestFullscreen();
                
                // Auto-submit disabled (admin manual control only)
            }
        }
        
        // Prevent window minimize/resize
        window.addEventListener('resize', function() {
            if (fullscreenLocked) {
                const isFullscreen = document.fullscreenElement || 
                                    document.webkitFullscreenElement || 
                                    document.mozFullScreenElement || 
                                    document.msFullscreenElement;
                
                if (!isFullscreen) {
                    logViolation('Window resize detected - possible minimize attempt');
                    requestFullscreen();
                }
            }
        });
        
        // Prevent Escape key (exits fullscreen)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && fullscreenLocked) {
                e.preventDefault();
                e.stopPropagation();
                logViolation('Escape key pressed - attempted to exit fullscreen');
                // Warning removed - logged silently
                return false;
            }
        }, true);
        
        // Note: Browser minimize is now handled by detectWindowSwitch() function
        // to avoid duplicate visibilitychange listeners
        
        // Prevent Alt+F4 (close window) - Log only, can't fully prevent
        window.addEventListener('beforeunload', handleBeforeUnload);
        
        // Detect mouse movement to top area (where restore button appears)
        let topAreaWarningShown = false;
        document.addEventListener('mousemove', function(e) {
            if (fullscreenLocked && e.clientY < 30) {
                if (!topAreaWarningShown) {
                    logViolation('Mouse moved to top area - possible restore button attempt');
                    topAreaWarningShown = true;
                    setTimeout(function() {
                        topAreaWarningShown = false;
                    }, 5000);
                }
            }
        });
        
        // Monitor for any clicks in top area
        document.addEventListener('click', function(e) {
            if (fullscreenLocked && e.clientY < 50) {
                logViolation('Click detected in top area - possible restore button click');
                // Warning removed - logged silently
            }
        }, true);
    }
    
    // 9. BLOCK COPY/PASTE
    function blockCopyPaste() {
        handleCopy = function(e) {
            e.preventDefault();
            logViolation('Copy attempted');
            // Warning removed - logged silently
            return false;
        };
        document.addEventListener('copy', handleCopy);
        
        handleCut = function(e) {
            e.preventDefault();
            logViolation('Cut attempted');
            return false;
        };
        document.addEventListener('cut', handleCut);
        
        // Allow paste in textarea (for essay answers)
        handlePaste = function(e) {
            if (e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                logViolation('Paste attempted');
                return false;
            }
        };
        document.addEventListener('paste', handlePaste);
    }
    
    // 10. DISABLE PRINT
    function disablePrint() {
        window.addEventListener('beforeprint', function(e) {
            e.preventDefault();
            logViolation('Print dialog opened');
            // Warning removed - logged silently
            return false;
        });
        
        // Override window.print
        window.print = function() {
            logViolation('window.print() called');
            // Warning removed - logged silently
        };
    }
    
    // LOG VIOLATION
    function logViolation(message) {
        violationCount++;
        
        const logData = {
            violation: message,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent,
            screenResolution: window.screen.width + 'x' + window.screen.height,
            count: violationCount
        };
        
        console.warn('🚨 SECURITY VIOLATION:', logData);
        
        // Send to server via AJAX
        $.ajax({
            url: 'log_security_violation.php',
            method: 'POST',
            data: logData,
            async: true
        });
        
        // Auto-submit disabled - admin manual control only
        // Violations are logged silently for admin review
    }
    
    // SHOW WARNING
    function showWarning(message) {
        const warning = document.createElement('div');
        warning.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: #ff9800;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10000;
            font-size: 14px;
            font-weight: bold;
        `;
        warning.textContent = message;
        document.body.appendChild(warning);
        
        setTimeout(function() {
            warning.remove();
        }, 3000);
    }
    
    // SHOW CRITICAL WARNING
    function showCriticalWarning(message) {
        const warning = document.createElement('div');
        warning.style.cssText = `
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: #f44336;
            color: white;
            padding: 20px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
            z-index: 10001;
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            animation: shake 0.5s;
        `;
        warning.textContent = message;
        document.body.appendChild(warning);
        
        setTimeout(function() {
            warning.remove();
        }, 5000);
    }
    
    // SHOW SECURITY NOTICE
    function showSecurityNotice() {
        const notice = document.createElement('div');
        notice.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        `;
        
        notice.innerHTML = `
            <div style="text-align: center; max-width: 600px; padding: 40px; background: #1a1a1a; border-radius: 15px;">
                <h2 style="color: #ff9800; margin-bottom: 20px;">🔒 EXAM SECURITY NOTICE</h2>
                <p style="font-size: 16px; line-height: 1.8; margin-bottom: 20px;">
                    This exam is monitored for security violations. The following are <strong>PROHIBITED</strong>:
                </p>
                <ul style="text-align: left; font-size: 14px; line-height: 2;">
                    <li>❌ Taking screenshots or screen recordings</li>
                    <li>❌ Switching tabs or windows</li>
                    <li>❌ Copying or printing exam content</li>
                    <li>❌ Using external tools or software</li>
                    <li>❌ Right-clicking or using keyboard shortcuts</li>
                    <li>❌ Exiting fullscreen mode or minimizing browser</li>
                    <li>❌ Pressing Escape key</li>
                </ul>
                <p style="font-size: 14px; color: #ff9800; margin-top: 20px;">
                    <strong>⚠️ Warning:</strong> Violations are logged and may result in automatic exam submission.
                </p>
                <p style="font-size: 16px; color: #4CAF50; margin-top: 15px; font-weight: bold;">
                    📺 The exam will enter FULLSCREEN MODE automatically.
                </p>
                <button onclick="this.parentElement.parentElement.remove()" style="
                    margin-top: 30px;
                    padding: 12px 40px;
                    background: #4CAF50;
                    color: white;
                    border: none;
                    border-radius: 6px;
                    font-size: 16px;
                    cursor: pointer;
                    font-weight: bold;
                ">I Understand - Start Exam</button>
            </div>
        `;
        
        document.body.appendChild(notice);
    }
    
    // AUTO-SUBMIT EXAM
    function autoSubmitExam(reason) {
        console.error('🚨 AUTO-SUBMITTING EXAM. Reason:', reason);
        
        // Disable security features first
        disableAllSecurity();
        
        // Log the auto-submit
        // Log the auto-submit asynchronously and then proceed to attempt submission.
        $.ajax({
            url: 'log_security_violation.php',
            method: 'POST',
            data: {
                violation: 'AUTO-SUBMIT: ' + reason,
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent,
                autoSubmit: true
            },
            async: true
        }).always(function() {
            // Try multiple methods to submit the exam
            let submitted = false;

            // Method 1: Call global confirmSubmitHandler if it exists
            if (typeof window.confirmSubmitHandler === 'function') {
                console.log('Method 1: Calling confirmSubmitHandler');
                window.confirmSubmitHandler();
                submitted = true;
            }
            // Method 2: Trigger the submit button click
            else if (document.getElementById('submitButton')) {
                console.log('Method 2: Clicking submit button');
                document.getElementById('submitButton').click();
                submitted = true;
            }
            // Method 3: Directly submit the form via AJAX
            else if (document.getElementById('allQuestionsForm')) {
                console.log('Method 3: AJAX form submission');
                const formData = $('#allQuestionsForm').serialize();
                $.post('submitAllAnswers.php', formData, function(response) {
                    if (response && response.success) {
                        window.location.href = 'result/index.php';
                    } else {
                        window.location.href = 'result/index.php'; // Force redirect anyway
                    }
                }, 'json').fail(function() {
                    window.location.href = 'result/index.php'; // Force redirect on fail
                });
                submitted = true;
            }

            if (!submitted) {
                console.error('❌ Failed to auto-submit exam - no submission method found!');
                // Alert removed - logged silently
                window.location.href = 'result/index.php';
            }
        });
    }
    
    // Add shake animation and hide fullscreen controls
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes shake {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-50%) translateY(-10px); }
            20%, 40%, 60%, 80% { transform: translateX(-50%) translateY(10px); }
        }
        
        /* Hide browser fullscreen notification/exit buttons */
        ::-webkit-full-screen-ancestor:not(iframe) {
            display: none !important;
        }
        
        /* Hide Chrome fullscreen notification */
        :-webkit-full-screen-ancestor:not(iframe) {
            display: none !important;
        }
        
        /* Hide fullscreen exit notification (Chrome/Edge) */
        #fullscreen-exit-button,
        .fullscreen-exit-button,
        [class*="fullscreen"],
        [id*="fullscreen"] {
            opacity: 0 !important;
            pointer-events: none !important;
            display: none !important;
        }
        
        /* Force fullscreen styling */
        html:fullscreen,
        html:-webkit-full-screen,
        html:-moz-full-screen,
        html:-ms-fullscreen {
            width: 100% !important;
            height: 100% !important;
        }
        
        /* Disable mouse interaction with top area where restore button appears */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 50px;
            z-index: 999999;
            pointer-events: none;
        }
    `;
    document.head.appendChild(style);
    
    // Defined handlers so they can be removed
    function handleBeforeUnload(e) {
        if (fullscreenLocked && document.visibilityState === 'visible') {
            e.preventDefault();
            e.returnValue = 'You are in an active exam session. Leaving now will submit your test automatically.';
            return e.returnValue;
        }
    }
    
    // Function to disable all security features (called on exam submit)
    function disableAllSecurity() {
        console.log("ExamSecurity: Disabling all security features...");
        fullscreenLocked = false;
        
        // Remove all listeners
        window.removeEventListener('beforeunload', handleBeforeUnload);
        
        // Stop all intervals and event listeners
        SECURITY_CONFIG.enableFullscreen = false;
        SECURITY_CONFIG.blockScreenshots = false;
        SECURITY_CONFIG.blockRightClick = false;
        SECURITY_CONFIG.blockKeyboardShortcuts = false;
        SECURITY_CONFIG.blockTextSelection = false;
        SECURITY_CONFIG.detectTabSwitch = false;
        SECURITY_CONFIG.detectDevTools = false;
        SECURITY_CONFIG.watermarkEnabled = false;
        SECURITY_CONFIG.autoSubmitOnViolation = false;
        
        // Exit fullscreen if active
        try {
            if (document.fullscreenElement || document.webkitFullscreenElement || 
                document.mozFullscreenElement || document.msFullscreenElement) {
                if (document.exitFullscreen) {
                    document.exitFullscreen().catch(err => console.warn("Fullscreen exit error:", err));
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        } catch (e) {
            console.error("Error during security disable:", e);
        }
        
        // Re-enable right-click
        document.removeEventListener('contextmenu', handleContextMenu);
        
        // Re-enable text selection
        document.body.style.userSelect = 'auto';
        document.body.style.webkitUserSelect = 'auto';
        document.body.style.mozUserSelect = 'auto';
        document.body.style.msUserSelect = 'auto';
        
        // Re-enable copy/paste
        document.removeEventListener('copy', handleCopy);
        document.removeEventListener('cut', handleCut);
        document.removeEventListener('paste', handlePaste);
        
        // Remove watermark if exists
        const watermark = document.getElementById('exam-watermark');
        if (watermark) {
            watermark.remove();
        }
        
        // Remove security warning modal
        const securityModal = document.getElementById('securityModal');
        if (securityModal) {
            securityModal.style.display = 'none';
        }
        
        // Remove any warning overlays
        const warnings = document.querySelectorAll('.security-warning-overlay');
        warnings.forEach(w => w.remove());
        
        // Show success message in console (for debugging)
        console.log('%c✓ Right-click enabled', 'color: green;');
        console.log('%c✓ Keyboard shortcuts enabled', 'color: green;');
        console.log('%c✓ Text selection enabled', 'color: green;');
        console.log('%c✓ Copy/paste enabled', 'color: green;');
        console.log('%c✓ Fullscreen exited', 'color: green;');
        console.log('%c✓ All security features disabled', 'color: green; font-weight: bold;');
    }
    
    // Export to global scope
    window.ExamSecurity = {
        init: initSecurity,
        logViolation: logViolation,
        disableAll: disableAllSecurity
    };
    
})();

