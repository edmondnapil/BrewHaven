/**
 * Brew Haven - 5-Minute Inactivity Auto-Logout Tracker
 * Tracks user activity (mouse, keyboard, touches, scrolling, clicks).
 * Automatically logs out and redirects to login.php?msg=inactivity after 300 seconds (5 minutes) of inactivity.
 */
(function() {
    'use strict';

    // 300 seconds (5 minutes) of inactivity timeout
    const INACTIVITY_TIMEOUT_MS = 300 * 1000;
    let timer = null;
    let lastActivityTime = Date.now();
    let throttleTimer = null;

    function getLogoutUrl() {
        const pathname = window.location.pathname.replace(/\\/g, '/');
        if (pathname.includes('/html/')) {
            return 'logout.php?reason=inactivity';
        }
        return '../html/logout.php?reason=inactivity';
    }

    function triggerTimeout() {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
        try {
            window.location.replace(getLogoutUrl());
        } catch (e) {
            window.location.href = getLogoutUrl();
        }
    }

    function resetTimer() {
        lastActivityTime = Date.now();
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(triggerTimeout, INACTIVITY_TIMEOUT_MS);
    }

    function handleActivity() {
        if (!throttleTimer) {
            throttleTimer = setTimeout(function() {
                throttleTimer = null;
            }, 1000);
            resetTimer();
        }
    }

    const activityEvents = [
        'mousemove',
        'mousedown',
        'keydown',
        'keypress',
        'touchstart',
        'touchmove',
        'scroll',
        'click',
        'wheel'
    ];

    activityEvents.forEach(function(evt) {
        window.addEventListener(evt, handleActivity, { passive: true, capture: true });
    });

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            const elapsed = Date.now() - lastActivityTime;
            if (elapsed >= INACTIVITY_TIMEOUT_MS) {
                triggerTimeout();
            } else {
                resetTimer();
            }
        }
    });

    resetTimer();

    window.BHInactivityTracker = {
        reset: resetTimer,
        trigger: triggerTimeout,
        getRemainingTime: function() {
            return Math.max(0, INACTIVITY_TIMEOUT_MS - (Date.now() - lastActivityTime));
        }
    };
})();
