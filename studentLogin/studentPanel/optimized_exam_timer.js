/**
 * Optimized Exam Timer
 * Client-side countdown with periodic server synchronization
 * Reduces server load from 1 req/sec to 1 req/30sec per student
 */

class OptimizedExamTimer {
    constructor(initialTime, displayElementId = 'hms') {
        this.remainingTime = initialTime;
        this.displayElement = document.getElementById(displayElementId);
        this.syncInterval = 30; // Sync with server every 30 seconds
        this.lastSync = Date.now();
        this.isRunning = false;
        this.intervalId = null;
        this.syncTimeoutId = null;
        this.hasSubmitted = false;
        
        // Visibility change handling to prevent time manipulation
        this.handleVisibilityChange = this.handleVisibilityChange.bind(this);
        document.addEventListener('visibilitychange', this.handleVisibilityChange);
        
        // Prevent page close without warning
        this.beforeUnloadHandler = this.beforeUnloadHandler.bind(this);
        window.addEventListener('beforeunload', this.beforeUnloadHandler);
    }
    
    /**
     * Start the countdown timer
     */
    start() {
        if (this.isRunning) return;
        
        this.isRunning = true;
        
        // Update display immediately
        this.updateDisplay();
        
        // Start client-side countdown (updates every second)
        this.intervalId = setInterval(() => {
            this.tick();
        }, 1000);
        
        // Schedule periodic server sync
        this.scheduleServerSync();
    }
    
    /**
     * Stop the timer
     */
    stop() {
        this.isRunning = false;
        
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
        
        if (this.syncTimeoutId) {
            clearTimeout(this.syncTimeoutId);
            this.syncTimeoutId = null;
        }
    }
    
    /**
     * Client-side tick (1 second)
     */
    tick() {
        if (this.remainingTime > 0) {
            this.remainingTime--;
            this.updateDisplay();
        }
        
        // Auto-submit when time runs out
        if (this.remainingTime <= 0 && !this.hasSubmitted) {
            this.handleTimeExpired();
        }
    }
    
    /**
     * Update the display element
     */
    updateDisplay() {
        if (!this.displayElement) return;
        
        const hours = Math.floor(this.remainingTime / 3600);
        const minutes = Math.floor((this.remainingTime % 3600) / 60);
        const seconds = Math.floor(this.remainingTime % 60);
        
        const formattedTime = this.pad(hours) + ' : ' + this.pad(minutes) + ' : ' + this.pad(seconds);
        this.displayElement.innerHTML = formattedTime;
        
        // Add visual warning when time is running low (last 5 minutes)
        if (this.remainingTime <= 300 && this.remainingTime > 0) {
            this.displayElement.style.backgroundColor = '#ff6b6b';
            this.displayElement.style.color = '#ffffff';
            
            // Flash effect in last minute
            if (this.remainingTime <= 60) {
                this.displayElement.style.animation = 'blink 1s linear infinite';
            }
        }
    }
    
    /**
     * Pad number with leading zero
     */
    pad(num) {
        return num < 10 ? '0' + num : num;
    }
    
    /**
     * Schedule server synchronization
     */
    scheduleServerSync() {
        if (!this.isRunning) return;
        
        this.syncTimeoutId = setTimeout(() => {
            this.syncWithServer();
        }, this.syncInterval * 1000);
    }
    
    /**
     * Synchronize with server
     */
    syncWithServer() {
        if (!this.isRunning || this.hasSubmitted) return;
        
        // Send current client time for validation
        const formData = new FormData();
        formData.append('clientTime', this.remainingTime);
        
        fetch('optimized_tickTimer.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Timer sync failed:', data.message);
                
                // Handle redirect if needed
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                
                if (data.shouldSubmit && !this.hasSubmitted) {
                    this.handleTimeExpired();
                }
                return;
            }
            
            // Update client time from server (authoritative)
            const serverTime = parseInt(data.remainingTime);
            const timeDiff = Math.abs(this.remainingTime - serverTime);
            
            // If difference is significant, adjust client time
            if (timeDiff > 5) {
                console.warn('Time drift detected. Adjusting from', this.remainingTime, 'to', serverTime);
                this.remainingTime = serverTime;
                this.updateDisplay();
            }
            
            // Update sync interval if server provides one
            if (data.syncInterval) {
                this.syncInterval = data.syncInterval;
            }
            
            // Check if should submit
            if (data.shouldSubmit && !this.hasSubmitted) {
                this.handleTimeExpired();
            } else {
                // Schedule next sync
                this.scheduleServerSync();
            }
        })
        .catch(error => {
            console.error('Timer sync error:', error);
            // Still schedule next sync on error
            this.scheduleServerSync();
        });
    }
    
    /**
     * Handle time expiration
     */
    handleTimeExpired() {
        this.hasSubmitted = true;
        this.stop();
        
        // Call the global submit function if it exists
        if (typeof clickedSubmit === 'function') {
            clickedSubmit();
        } else {
            alert('Time is up! Your exam will be submitted automatically.');
            window.location.href = 'submit.php';
        }
    }
    
    /**
     * Handle visibility change (tab switching)
     */
    handleVisibilityChange() {
        if (document.hidden) {
            // Tab is hidden - sync with server when user returns
            this.lastHidden = Date.now();
        } else {
            // Tab is visible again - sync immediately to prevent cheating
            if (this.lastHidden && this.isRunning) {
                const hiddenDuration = Math.floor((Date.now() - this.lastHidden) / 1000);
                
                // If hidden for more than 2 seconds, sync with server
                if (hiddenDuration > 2) {
                    console.log('Tab was hidden for', hiddenDuration, 'seconds. Syncing...');
                    this.syncWithServer();
                }
            }
        }
    }
    
    /**
     * Warn before page unload
     */
    beforeUnloadHandler(e) {
        if (this.isRunning && !this.hasSubmitted) {
            e.preventDefault();
            e.returnValue = 'Your exam is still in progress. Are you sure you want to leave?';
            return e.returnValue;
        }
    }
    
    /**
     * Get remaining time
     */
    getRemainingTime() {
        return this.remainingTime;
    }
    
    /**
     * Cleanup
     */
    destroy() {
        this.stop();
        document.removeEventListener('visibilitychange', this.handleVisibilityChange);
        window.removeEventListener('beforeunload', this.beforeUnloadHandler);
    }
}

// CSS for blinking animation (add to page if not present)
if (!document.getElementById('exam-timer-styles')) {
    const style = document.createElement('style');
    style.id = 'exam-timer-styles';
    style.textContent = `
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.5; }
        }
    `;
    document.head.appendChild(style);
}

// Export for use in pages
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OptimizedExamTimer;
}




