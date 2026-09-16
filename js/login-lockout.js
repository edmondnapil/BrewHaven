// Login Lockout Management Module
// Handles all lockout-related functionality: attempts tracking, timer, prevention mechanisms

(function() {
    'use strict';
    
    // Lockout state variables
    let loginAttempts = parseInt(localStorage.getItem('loginAttempts')) || 0;
    let lockoutEndTime = parseInt(localStorage.getItem('lockoutEndTime')) || 0;
    const MAX_LOCKOUT_LEVEL = 2; // 0:15s, 1:30s, 2:60s (cap)
    let lockoutLevel = parseInt(localStorage.getItem('lockoutLevel')) || 0;
    let timerInterval = null;
    let refreshKeydownHandler = null;
    let backButtonInterval = null;
    let isLockoutActive = false;
    let backButtonPreventionHandler = null;
    let wasPageRefreshedDuringLockout = false;
    let suppressResetCTAOnLoad = false;
    let lockoutMessageActive = false;
    
    // DOM elements (will be set by initialize function)
    let loginBtn = null;
    let usernameInput = null;
    let passwordInput = null;
    let registerLink = null;
    let headerActionLinks = [];
    let timerTextSpan = null;
    let generalErrorContainer = null;
    let showGeneralErrorFn = null;
    let showInlineResetPasswordCTAFn = null;
    let updateAttemptsDisplayFn = null;
    let disableResetPasswordButtonFn = null;
    
    const RESET_CTA_SUPPRESS_KEY = 'resetCtaSuppressed';
    
    // Back button prevention function
    function preventBackButton(e) {
        if (!isLockoutActive) return;
        
        // Immediately push multiple states to prevent navigation (more aggressive)
        history.pushState(null, null, window.location.href);
        history.pushState(null, null, window.location.href);
        history.replaceState(null, null, window.location.href);
        
        // Push another state immediately to ensure we stay locked
        setTimeout(() => {
            if (isLockoutActive) {
                history.pushState(null, null, window.location.href);
                history.pushState(null, null, window.location.href);
            }
        }, 0);
    }
    
    // Store reference for cleanup
    backButtonPreventionHandler = preventBackButton;
    
    // Initialize lockout system with DOM elements and callback functions
    function initialize(elements, callbacks) {
        loginBtn = elements.loginBtn;
        usernameInput = elements.usernameInput;
        passwordInput = elements.passwordInput;
        registerLink = elements.registerLink;
        headerActionLinks = elements.headerActionLinks || [];
        timerTextSpan = elements.timerTextSpan;
        generalErrorContainer = elements.generalErrorContainer;
        
        showGeneralErrorFn = callbacks.showGeneralError;
        showInlineResetPasswordCTAFn = callbacks.showInlineResetPasswordCTA;
        updateAttemptsDisplayFn = callbacks.updateAttemptsDisplay;
        disableResetPasswordButtonFn = callbacks.disableResetPasswordButton;
        
        // Load state from localStorage
        loginAttempts = parseInt(localStorage.getItem('loginAttempts')) || 0;
        lockoutEndTime = parseInt(localStorage.getItem('lockoutEndTime')) || 0;
        lockoutLevel = parseInt(localStorage.getItem('lockoutLevel')) || 0;
        suppressResetCTAOnLoad = isResetCtaSuppressedInStorage();
        
        // Check lockout status immediately on page load
        const now = Date.now();
        const savedLockoutEndTime = parseInt(localStorage.getItem('lockoutEndTime')) || 0;
        const isPageRefresh = performance.navigation.type === 1;
        const pageRefreshTime = isPageRefresh ? performance.timing.navigationStart : null;
        
        // Check if lockout is active
        if (savedLockoutEndTime > now) {
            // Lockout is active - enable prevention immediately
            isLockoutActive = true;
            
            // Check if this is a refresh during lockout
            if (isPageRefresh && pageRefreshTime < savedLockoutEndTime) {
                wasPageRefreshedDuringLockout = true;
                localStorage.setItem('wasRefreshedDuringLockout', 'true');
            }
            
            // If page was refreshed during lockout
            const wasRefreshed = localStorage.getItem('wasRefreshedDuringLockout') === 'true';
            if (wasRefreshed) {
                wasPageRefreshedDuringLockout = true;
                try {
                    const resetCta = document.querySelector('.inline-reset-cta');
                    if (resetCta) {
                        resetCta.style.display = 'none';
                    }
                } catch (e) {}
            }
            
            // Visually indicate lockout state
            try { document.body.classList.add('account-locked'); } catch (e) {}
            
            // Push multiple states immediately to lock user in
            history.pushState(null, null, window.location.href);
            history.pushState(null, null, window.location.href);
            history.replaceState(null, null, window.location.href);
            
            // Set up very frequent interval for immediate and continuous protection
            if (!backButtonInterval) {
                backButtonInterval = setInterval(() => {
                    if (isLockoutActive) {
                        history.pushState(null, null, window.location.href);
                    } else {
                        clearInterval(backButtonInterval);
                        backButtonInterval = null;
                    }
                }, 50);
            }
            
            // Add event listeners immediately
            window.addEventListener('popstate', preventBackButton, true);
            window.addEventListener('popstate', preventBackButton);
        }
    }
    
    // Get lockout duration based on level
    function getLockoutDuration() {
        const lockoutDurations = [15000, 30000, 60000];
        const index = Math.min(lockoutLevel, lockoutDurations.length - 1);
        return lockoutDurations[index];
    }
    
    // Start lockout timer
    function startLockout(seconds) {
        if (timerInterval) {
            clearInterval(timerInterval);
        }
        
        disableLogin();
        disableAllButtons();
        enableNoRefresh();
        
        updateTimerDisplay(seconds);
        
        timerInterval = setInterval(() => {
            seconds--;
            
            if (seconds <= 0) {
                clearInterval(timerInterval);
                timerInterval = null;
                enableLogin();
                disableNoRefresh();
                disableBackButtonPrevention();
                loginAttempts = 0;
                lockoutEndTime = 0;
                localStorage.setItem('loginAttempts', '0');
                localStorage.removeItem('lockoutEndTime');
                setResetCtaSuppressed(false);
                if (updateAttemptsDisplayFn) updateAttemptsDisplayFn();
            } else {
                updateTimerDisplay(seconds);
            }
        }, 1000);
    }
    
    // Disable login form during lockout
    function disableLogin() {
        if (loginBtn) loginBtn.disabled = true;
        if (registerLink) {
            registerLink.style.pointerEvents = 'none';
            registerLink.style.color = '#666';
        }
        if (usernameInput) usernameInput.disabled = true;
        if (passwordInput) passwordInput.disabled = true;
        
        // Disable header action links during lockout
        headerActionLinks.forEach(function(link) {
            link.style.pointerEvents = 'none';
            link.style.color = '#666';
            link.setAttribute('aria-disabled', 'true');
            link.setAttribute('tabindex', '-1');
        });
        
        // Disable reset password button during lockout
        if (disableResetPasswordButtonFn) disableResetPasswordButtonFn();
        
        // Disable browser back button during lockout
        enableBackButtonPrevention();
        
        // Add visual/UI lockout class
        try { document.body.classList.add('account-locked'); } catch (e) {}
    }
    
    // Disable all buttons during lockout
    function disableAllButtons() {
        if (loginBtn) loginBtn.disabled = true;
        if (usernameInput) usernameInput.disabled = true;
        if (passwordInput) passwordInput.disabled = true;
        
        // Disable header links
        headerActionLinks.forEach(function(link) {
            link.style.pointerEvents = 'none';
            link.style.color = '#666';
            link.setAttribute('aria-disabled', 'true');
            link.setAttribute('tabindex', '-1');
        });
        
        // Disable reset password button
        if (disableResetPasswordButtonFn) disableResetPasswordButtonFn();
        
        // Handle button visibility based on refresh status
        const resetCta = document.querySelector('.inline-reset-cta');
        if (resetCta) {
            resetCta.style.display = 'flex';
        }
    }
    
    // Enable login form after lockout
    function enableLogin() {
        isLockoutActive = false;
        if (loginBtn) loginBtn.disabled = false;
        if (showGeneralErrorFn) showGeneralErrorFn('', false);
        
        if (registerLink) {
            registerLink.style.pointerEvents = 'auto';
            registerLink.style.color = '#4CAF50';
        }
        
        if (usernameInput) usernameInput.disabled = false;
        if (passwordInput) passwordInput.disabled = false;
        if (timerTextSpan) timerTextSpan.style.display = 'none';
        
        // Re-enable header action links
        headerActionLinks.forEach(function(link) {
            link.style.pointerEvents = 'auto';
            link.style.color = '';
            link.removeAttribute('aria-disabled');
            link.removeAttribute('tabindex');
        });
        
        // Re-enable browser back button
        disableBackButtonPrevention();
        
        // Remove lockout UI class
        try { 
            document.body.classList.remove('account-locked'); 
        } catch (e) {}
        
        setResetCtaSuppressed(false);
        if (showInlineResetPasswordCTAFn) showInlineResetPasswordCTAFn();
    }
    
    // Prevent refresh/reload during lockout
    function enableNoRefresh() {
        if (!refreshKeydownHandler) {
            refreshKeydownHandler = function(e) {
                if (!isLockoutActive) return;
                const key = (e.key || '').toLowerCase();
                // Prevent F5, Ctrl+R, Ctrl+Shift+R
                if (key === 'f5' || 
                    e.keyCode === 116 || 
                    (e.ctrlKey && (key === 'r' || e.keyCode === 82)) ||
                    (e.ctrlKey && e.shiftKey && (key === 'r' || e.keyCode === 82))) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    return false;
                }
            };
            window.addEventListener('keydown', refreshKeydownHandler, true);
        }
    }
    
    function disableNoRefresh() {
        if (refreshKeydownHandler) {
            window.removeEventListener('keydown', refreshKeydownHandler, true);
            refreshKeydownHandler = null;
        }
    }
    
    // Enable back button prevention
    function enableBackButtonPrevention() {
        if (isLockoutActive) {
            // Push states immediately to reinforce lock
            history.pushState(null, null, window.location.href);
            history.pushState(null, null, window.location.href);
            
            // Ensure interval is running
            if (!backButtonInterval) {
                backButtonInterval = setInterval(() => {
                    if (isLockoutActive) {
                        history.pushState(null, null, window.location.href);
                    } else {
                        clearInterval(backButtonInterval);
                        backButtonInterval = null;
                    }
                }, 50);
            }
            
            window.addEventListener('popstate', preventBackButton, true);
            window.addEventListener('popstate', preventBackButton);
            return;
        }
        
        isLockoutActive = true;
        
        // Push multiple states immediately
        history.pushState(null, null, window.location.href);
        history.pushState(null, null, window.location.href);
        history.replaceState(null, null, window.location.href);
        
        // Listen for back button events
        window.addEventListener('popstate', preventBackButton, true);
        window.addEventListener('popstate', preventBackButton);
        
        // Continuously re-push state
        if (!backButtonInterval) {
            backButtonInterval = setInterval(() => {
                if (isLockoutActive) {
                    history.pushState(null, null, window.location.href);
                } else {
                    clearInterval(backButtonInterval);
                    backButtonInterval = null;
                }
            }, 50);
        }
    }
    
    // Disable back button prevention
    function disableBackButtonPrevention() {
        if (!isLockoutActive) return;
        
        isLockoutActive = false;
        
        // Clear the interval
        if (backButtonInterval) {
            clearInterval(backButtonInterval);
            backButtonInterval = null;
        }
        
        // Remove event listeners
        if (backButtonPreventionHandler) {
            window.removeEventListener('popstate', backButtonPreventionHandler, true);
            window.removeEventListener('popstate', backButtonPreventionHandler);
        }
    }
    
    // Update timer display
    function updateTimerDisplay(seconds) {
        const lockoutMessage = `Too many failed attempt. Please try again after ${seconds} seconds`;
        if (timerTextSpan) {
            timerTextSpan.style.display = 'block';
            timerTextSpan.textContent = lockoutMessage;
            timerTextSpan.style.color = '#ff4757';
            timerTextSpan.style.fontWeight = 'bold';
        }
        if (showGeneralErrorFn) {
            showGeneralErrorFn(lockoutMessage, true);
        }
        lockoutMessageActive = true;
    }
    
    // Check if user is locked out
    function isLockedOut() {
        const now = Date.now();
        return lockoutEndTime > now;
    }
    
    // Handle login failure and increment attempts
    function handleLoginFailure() {
        loginAttempts++;
        localStorage.setItem('loginAttempts', loginAttempts.toString());
        
        // Clear refresh flag when user reaches 2 failed attempts
        if (loginAttempts >= 2) {
            localStorage.removeItem('wasRefreshedDuringLockout');
            wasPageRefreshedDuringLockout = false;
            if (!suppressResetCTAOnLoad) {
                setResetCtaSuppressed(false);
            }
        }
        
        // Update display
        if (updateAttemptsDisplayFn) updateAttemptsDisplayFn();
        
        if (loginAttempts >= 3) {
            // Start lockout period immediately on 3rd attempt
            const lockoutDuration = getLockoutDuration();
            lockoutEndTime = Date.now() + lockoutDuration;
            localStorage.setItem('lockoutEndTime', lockoutEndTime.toString());
            
            // Increase lockout level for next time
            lockoutLevel = Math.min(lockoutLevel + 1, MAX_LOCKOUT_LEVEL);
            localStorage.setItem('lockoutLevel', lockoutLevel.toString());
            
            isLockoutActive = true;
            startLockout(Math.ceil(lockoutDuration / 1000));
            disableAllButtons();
            if (updateAttemptsDisplayFn) updateAttemptsDisplayFn();
        }
    }
    
    // Reset lockout state after successful login
    function resetLockoutState() {
        loginAttempts = 0;
        lockoutEndTime = 0;
        lockoutLevel = 0;
        localStorage.removeItem('loginAttempts');
        localStorage.removeItem('lockoutEndTime');
        localStorage.removeItem('lockoutLevel');
        setResetCtaSuppressed(false);
    }
    
    // Get remaining lockout seconds
    function getRemainingLockoutSeconds() {
        if (!isLockedOut()) return 0;
        return Math.max(1, Math.ceil((lockoutEndTime - Date.now()) / 1000));
    }
    
    // Reset CTA suppression functions
    function setResetCtaSuppressed(value) {
        suppressResetCTAOnLoad = Boolean(value);
        try {
            if (suppressResetCTAOnLoad) {
                localStorage.setItem(RESET_CTA_SUPPRESS_KEY, '1');
            } else {
                localStorage.removeItem(RESET_CTA_SUPPRESS_KEY);
            }
        } catch (e) {}
    }
    
    function isResetCtaSuppressedInStorage() {
        try {
            return localStorage.getItem(RESET_CTA_SUPPRESS_KEY) === '1';
        } catch (e) {
            return false;
        }
    }
    
    function shouldShowResetPasswordCTA() {
        if (suppressResetCTAOnLoad) {
            return false;
        }
        if (isLockedOut()) {
            return true;
        }
        return loginAttempts >= 2;
    }
    
    // Initialize lockout on page load
    function initializeLockoutOnLoad() {
        const now = Date.now();
        lockoutEndTime = parseInt(localStorage.getItem('lockoutEndTime')) || 0;
        loginAttempts = parseInt(localStorage.getItem('loginAttempts')) || 0;
        lockoutLevel = parseInt(localStorage.getItem('lockoutLevel')) || 0;
        
        if (lockoutEndTime > now) {
            // Still in lockout period
            isLockoutActive = true;
            enableBackButtonPrevention();
            const remainingTime = Math.ceil((lockoutEndTime - now) / 1000);
            startLockout(remainingTime);
            disableAllButtons();
        } else if (lockoutEndTime > 0) {
            // Lockout period has just ended - reset state
            isLockoutActive = false;
            loginAttempts = 0;
            lockoutEndTime = 0;
            localStorage.setItem('loginAttempts', '0');
            localStorage.removeItem('lockoutEndTime');
            disableBackButtonPrevention();
            setResetCtaSuppressed(false);
        }
    }
    
    // Export public API
    window.LoginLockout = {
        initialize: initialize,
        initializeLockoutOnLoad: initializeLockoutOnLoad,
        isLockedOut: isLockedOut,
        getRemainingLockoutSeconds: getRemainingLockoutSeconds,
        handleLoginFailure: handleLoginFailure,
        resetLockoutState: resetLockoutState,
        getLoginAttempts: function() { return loginAttempts; },
        setResetCtaSuppressed: setResetCtaSuppressed,
        isResetCtaSuppressedInStorage: isResetCtaSuppressedInStorage,
        shouldShowResetPasswordCTA: shouldShowResetPasswordCTA,
        getLockoutEndTime: function() { return lockoutEndTime; },
        getLockoutLevel: function() { return lockoutLevel; },
        getIsLockoutActive: function() { return isLockoutActive; },
        getWasPageRefreshedDuringLockout: function() { return wasPageRefreshedDuringLockout; }
    };
})();

