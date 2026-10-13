/**
 * Session Keep-Alive Manager
 * Keeps the session active only while the user is actively using the dashboard.
 */

(function() {
    'use strict';
    
    // Ping server every 5 minutes (300,000 milliseconds)
    const PING_INTERVAL = 5 * 60 * 1000;
    const ACTIVE_WINDOW = 5 * 60 * 1000;
    
    // Keep track of ping status
    let lastPingTime = Date.now();
    let consecutiveFailures = 0;
    const MAX_FAILURES = 3;
    let lastInteractionTime = Date.now();

    ['pointerdown', 'keydown', 'touchstart', 'click', 'scroll'].forEach(eventName => {
        document.addEventListener(eventName, () => {
            lastInteractionTime = Date.now();
        }, { passive: true });
    });
    
    function pingServer() {
        if (Date.now() - lastInteractionTime >= ACTIVE_WINDOW) return;

        fetch('../shared/keep_session_alive.php', {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-cache'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                lastPingTime = Date.now();
                consecutiveFailures = 0;
                console.log('Session kept alive:', data.timestamp);
            } else {
                if (data.status === 'expired') {
                    window.location.href = '../EvalMain.php?expired=1';
                    return;
                }
                consecutiveFailures++;
                console.warn('Session ping failed:', data.message);
                
                // If session expired after multiple attempts, redirect to login
                if (consecutiveFailures >= MAX_FAILURES) {
                    console.error('Session expired. Redirecting to login...');
                    window.location.href = '../EvalMain.php';
                }
            }
        })
        .catch(error => {
            consecutiveFailures++;
            console.error('Keep-alive ping error:', error);
            
            // If repeated failures, session might be lost
            if (consecutiveFailures >= MAX_FAILURES) {
                console.error('Multiple ping failures. Session may be expired.');
            }
        });
    }
    
    // Start pinging when page loads
    function startKeepAlive() {
        // Initial ping after 1 minute
        setTimeout(pingServer, 60 * 1000);
        
        // Then ping every 5 minutes
        setInterval(pingServer, PING_INTERVAL);
        
        console.log('Session keep-alive initialized. Pinging every 5 minutes.');
    }
    
    // Handle page visibility - ping when page becomes visible again
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            const timeSinceLastPing = Date.now() - lastPingTime;
            if (timeSinceLastPing > 4 * 60 * 1000 && Date.now() - lastInteractionTime < ACTIVE_WINDOW) {
                console.log('Page visible again, refreshing session...');
                pingServer();
            }
        }
    });
    
    // Start the keep-alive mechanism
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startKeepAlive);
    } else {
        startKeepAlive();
    }
    
})();
