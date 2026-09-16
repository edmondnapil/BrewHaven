// Login Form Validation and Security
document.addEventListener('DOMContentLoaded', function() {
    // Ensure lockout module is loaded
    if (typeof window.LoginLockout === 'undefined') {
        console.error('LoginLockout module not loaded. Please include login-lockout.js before login-validation.js');
        return;
    }
    
    // Clear any URL parameters without refreshing the page
    if (window.location.search) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    const form = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const loginBtn = document.getElementById('loginBtn');
    const registerLink = document.querySelector('.login-form a[href="register.php"]');
    const loginAttemptsDiv = document.getElementById('loginAttempts');
    const attemptCountSpan = document.getElementById('attemptCount');
    const timerTextSpan = document.getElementById('timerText');
    const headerActionLinks = Array.from(document.querySelectorAll('.header-actions .nav-link'));
    const generalErrorContainer = document.createElement('div');
    generalErrorContainer.id = 'generalErrorMessage';
    generalErrorContainer.className = 'general-error-message';
    generalErrorContainer.setAttribute('role', 'alert');
    generalErrorContainer.style.display = 'none';
    generalErrorContainer.style.color = '#ff4757';
    generalErrorContainer.style.fontWeight = '600';
    generalErrorContainer.style.fontSize = '0.85rem';
    generalErrorContainer.style.marginTop = '0.55rem';
    generalErrorContainer.style.marginBottom = '0.25rem';
    generalErrorContainer.style.lineHeight = '1.35';
    generalErrorContainer.style.textAlign = 'center';
    form.insertBefore(generalErrorContainer, loginBtn);
    
    let lockoutMessageActive = false;

    function showGeneralError(message, isLockoutMessage = false) {
        if (!generalErrorContainer) return;
        
        // Allow lockout messages (countdown timer) to be displayed
        if (isLockoutMessage && message) {
            generalErrorContainer.textContent = message || '';
            generalErrorContainer.style.display = message ? 'block' : 'none';
            lockoutMessageActive = true;
        } else {
            // Don't show other general error messages above login button
            // All other errors should be shown under specific fields
            generalErrorContainer.textContent = '';
            generalErrorContainer.style.display = 'none';
        }
    }

    function clearGeneralError(force = false) {
        if (!generalErrorContainer) return;
        if (lockoutMessageActive && !force) return;
        generalErrorContainer.textContent = '';
        generalErrorContainer.style.display = 'none';
        lockoutMessageActive = false;
    }

    function disableResetPasswordButton() {
        const resetBtns = document.querySelectorAll('.inline-reset-cta .reset-btn, .reset-btn');
        resetBtns.forEach(function(resetBtn) {
            resetBtn.disabled = true;
            resetBtn.style.pointerEvents = 'none';
            resetBtn.style.opacity = '0.5';
            resetBtn.style.cursor = 'not-allowed';
        });
    }
    
    function updateAttemptsDisplay() {
        loginAttemptsDiv.style.display = 'none';
        attemptCountSpan.textContent = '';
        try {
            showInlineResetPasswordCTA();
            } catch (e) {}
        }
    
    function shouldShowResetPasswordCTA() {
        return window.LoginLockout.shouldShowResetPasswordCTA();
    }
    
    // Initialize lockout module with DOM elements and callbacks
    window.LoginLockout.initialize({
        loginBtn: loginBtn,
        usernameInput: usernameInput,
        passwordInput: passwordInput,
        registerLink: registerLink,
        headerActionLinks: headerActionLinks,
        timerTextSpan: timerTextSpan,
        generalErrorContainer: generalErrorContainer
    }, {
        showGeneralError: showGeneralError,
        showInlineResetPasswordCTA: showInlineResetPasswordCTA,
        updateAttemptsDisplay: updateAttemptsDisplay,
        disableResetPasswordButton: disableResetPasswordButton
    });
    
    // Initialize lockout on page load
    window.LoginLockout.initializeLockoutOnLoad();
    
    // Restore persisted credentials (username/password) after refresh
    function restoreCredentials() {
        try {
            const savedUsername = sessionStorage.getItem('login_username');
            const savedPassword = sessionStorage.getItem('login_password');
            
            if (savedUsername !== null) { 
                usernameInput.value = savedUsername; 
            }
            if (savedPassword !== null) { 
                passwordInput.value = savedPassword; 
            }
        } catch (e) {}
    }
    
    // Restore immediately
    restoreCredentials();
    
    // Also restore after a small delay to ensure it happens after all lockout initialization
    setTimeout(function() {
        if (window.LoginLockout.isLockedOut()) {
            restoreCredentials();
        }
    }, 100);
    
    // Initialize login
    initializeLogin();
    
    // Form submission
    form.addEventListener('submit', handleLogin);
    
    // Input validation
    usernameInput.addEventListener('blur', validateUsername);
    passwordInput.addEventListener('blur', validatePassword);
    
    // Clear errors on input
    usernameInput.addEventListener('input', clearError);
    passwordInput.addEventListener('input', clearError);
    
    // Persist credentials on input so they survive refresh
    usernameInput.addEventListener('input', function() {
        try { sessionStorage.setItem('login_username', usernameInput.value); } catch (e) {}
    });
    passwordInput.addEventListener('input', function() {
        try { sessionStorage.setItem('login_password', passwordInput.value); } catch (e) {}
    });
    
    function initializeLogin() {
        const now = Date.now();
        const lockoutEndTime = window.LoginLockout.getLockoutEndTime();
        const loginAttempts = window.LoginLockout.getLoginAttempts();
        const isPageRefresh = performance.navigation.type === 1;
        
        const refreshedAtSecondAttempt = isPageRefresh && loginAttempts === 2 && lockoutEndTime <= now;
        if (refreshedAtSecondAttempt) {
            window.LoginLockout.setResetCtaSuppressed(true);
        }
        
        if (lockoutEndTime > now) {
            // Still in lockout period - handled by lockout module
            const remainingTime = Math.ceil((lockoutEndTime - now) / 1000);
            // Lockout module will handle this via initializeLockoutOnLoad
        } else if (lockoutEndTime > 0) {
            // Lockout period has just ended - reset state
            window.LoginLockout.resetLockoutState();
        } else if (loginAttempts >= 2) {
            // Not in lockout but had previous failed attempts
            showInlineResetPasswordCTA();
        }
        
        updateAttemptsDisplay();
    }
    
    function validateUsername() {
        const username = usernameInput.value.trim();
        const errorDiv = document.getElementById('username-error');
        clearError({ target: usernameInput });
        
        if (!username) {
            showError(usernameInput, 'Username is required');
            return false;
        }
        
        // Clear error if valid
        if (errorDiv) {
            errorDiv.textContent = '';
            errorDiv.classList.remove('show');
        }
        usernameInput.style.borderColor = '';
        return true;
    }
    
    function validatePassword() {
        const password = passwordInput.value;
        const errorDiv = document.getElementById('password-error');
        clearError({ target: passwordInput });
        
        if (!password) {
            showError(passwordInput, 'Password is required');
            return false;
        }
        
        // Clear error if valid
        if (errorDiv) {
            errorDiv.textContent = '';
            errorDiv.classList.remove('show');
        }
        passwordInput.style.borderColor = '';
        return true;
    }
    
    function handleLogin(e) {
        e.preventDefault();
        clearGeneralError();
        
        // Validate both fields and show specific errors
        const isUsernameValid = validateUsername();
        const isPasswordValid = validatePassword();
        
        if (!isUsernameValid || !isPasswordValid) {
            return;
        }
        
        const username = usernameInput.value.trim();
        const password = passwordInput.value;
        
        // Check if user is locked out
        if (window.LoginLockout.isLockedOut()) {
            const remainingSeconds = window.LoginLockout.getRemainingLockoutSeconds();
            // Timer display is handled by lockout module
            return;
        }
        
        // Attempt login
        attemptLogin(username, password);
    }
    
    function attemptLogin(username, password) {
        const formData = new FormData();
        formData.append('username', username);
        formData.append('password', password);
        
fetch('login.php', {
    method: 'POST',
    body: formData
})
.then(response => response.json())
.then(data => {
    try { console.debug('login.php response:', data); } catch (e) {}
    if (data.success === true) {
                // Login successful - reset lockout state and redirect
                window.LoginLockout.resetLockoutState();
        clearGeneralError(true);
                var target = (data.data && data.data.home) ? data.data.home : 'dashboard.php';
                // Prefer replace to avoid back navigation into login
                try { window.location.replace(target); } catch (e) { window.location.href = target; }
                // Fallback in case some scripts block navigation
                setTimeout(function(){
                  if (window.location.pathname.indexOf(target) === -1) {
                    window.location.href = target;
                  }
                }, 150);
    } else {
                // Login failed - show specific error under the appropriate field
        const errorMessage = data.message || 'Login failed';
        clearGeneralError();
        
        // Keep credential failures generic to prevent account enumeration.
        const lowerMessage = errorMessage.toLowerCase();
        
        if (lowerMessage.includes('username and password are required')) {
            // This is handled by validation, but just in case
            if (!usernameInput.value.trim()) {
                showError(usernameInput, 'Username is required');
            }
            if (!passwordInput.value) {
                showError(passwordInput, 'Password is required');
            }
        } else {
            // For other errors, show error under password field
            showError(passwordInput, errorMessage);
        }
        
        if (!lowerMessage.includes('another super admin')) {
            handleLoginFailure();
        }
    }
})
.catch(error => {
    console.error('Login error:', error);
    showError(passwordInput, 'An error occurred. Please try again.');
    handleLoginFailure();
});
    }
    
    function handleLoginFailure() {
        // Use lockout module to handle failure
        window.LoginLockout.handleLoginFailure();
        
        // Update display
        updateAttemptsDisplay();
    }
    
    function showError(field, message) {
        if (!field) return;
        const fieldName = field.name || field.id;
        const errorDiv = document.getElementById(fieldName + '-error');
        
        if (errorDiv && message) {
            errorDiv.textContent = message;
            errorDiv.style.color = '#ff4757';
            errorDiv.style.fontSize = '0.75rem';
            errorDiv.style.display = 'block';
            errorDiv.classList.add('show');
        }
        field.style.borderColor = '#ff4757';
    }
    
    function clearError(e) {
        const field = e.target;
        if (field) {
            field.style.borderColor = '';
            const fieldName = field.name || field.id;
            const errorDiv = document.getElementById(fieldName + '-error');
            if (errorDiv) {
                errorDiv.textContent = '';
                errorDiv.classList.remove('show');
            }
        }
        clearGeneralError();
    }
    
    // General back button prevention (only if lockout is not active)
    const generalBackPrevention = function(e) {
        if (!window.LoginLockout.getIsLockoutActive()) {
            history.pushState(null, null, window.location.href);
        }
    };
    
    // Only add general prevention if lockout is not already active
    if (!window.LoginLockout.getIsLockoutActive()) {
        window.addEventListener('popstate', generalBackPrevention);
        history.pushState(null, null, window.location.href);
    }

    function showInlineResetPasswordCTA() {
        const shouldShow = shouldShowResetPasswordCTA();
        
        if (!shouldShow) {
            const existingCta = document.querySelector('.inline-reset-cta');
            if (existingCta) {
                existingCta.remove();
            }
            return;
        }
        
        const isCurrentlyLockedOut = window.LoginLockout.isLockedOut();
        const existingCta = document.querySelector('.inline-reset-cta');
        if (existingCta) {
            const existingBtn = existingCta.querySelector('.reset-btn');
            if (existingBtn) {
                if (isCurrentlyLockedOut) {
                    disableResetPasswordButton();
                } else {
                    existingBtn.disabled = false;
                    existingBtn.style.cursor = 'pointer';
                    existingBtn.style.opacity = '1';
                    existingBtn.style.pointerEvents = 'auto';
                }
            }
            existingCta.style.display = 'flex';
            return;
        }
        
        // Create style (once)
        if (!document.getElementById('inlineResetCtaStyle')) {
            const s = document.createElement('style');
            s.id = 'inlineResetCtaStyle';
            s.textContent = `
                .inline-reset-cta { margin-top: 0.3rem; margin-bottom: 0.12rem; display: flex; justify-content: center; width: 100%; }
                .inline-reset-cta .reset-btn {
  border: none;
  padding: 6px 10px;
  border-radius: 8px;
  font-weight: 600; /* Bold */
  cursor: pointer;
  text-decoration: none; /* Remove underline from button */
  background: none; /* Optional: remove background if needed */
  color: #0052a3; /* Default color for the button text */
  font-size: 0.9rem;
}
                .inline-reset-cta .reset-btn:disabled { color: #666; cursor: not-allowed; }
                .forgot-text { color: #5a4a3a; }
            `;
            document.head.appendChild(s);
        }
        
        const form = document.getElementById('loginForm');
        const passwordField = document.getElementById('password');
        const passwordWrapper = passwordField ? passwordField.closest('.input-with-toggle') : null;
        const passwordError = document.getElementById('password-error');
        const loginAttemptsDiv = document.getElementById('loginAttempts');
        
        const cta = document.createElement('div');
        cta.className = 'inline-reset-cta';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'reset-btn';
        btn.innerHTML = '<span class="forgot-text">Forgot password? </span><span class="reset-here">Reset Here</span>';
        btn.addEventListener('click', function() { 
            if (!window.LoginLockout.isLockedOut()) {
                window.location.href = 'authentication.php';
            }
        });
        cta.appendChild(btn);
        
        // Insert the forgot password link after the password error message, but before login attempts
        const existingCtaInForm = form.querySelector('.inline-reset-cta');
        if (existingCtaInForm) {
            existingCtaInForm.remove();
        }
        
        // Insert directly below the password input/error area
        const insertionTarget = passwordError || passwordWrapper;
        if (insertionTarget && insertionTarget.parentNode) {
            insertionTarget.parentNode.insertBefore(cta, insertionTarget.nextSibling);
        } else if (loginAttemptsDiv) {
            form.insertBefore(cta, loginAttemptsDiv);
        } else {
            form.insertBefore(cta, form.firstChild);
        }
        cta.style.display = 'flex';
        
        // If currently locked out, ensure this button is disabled but visible
        if (isCurrentlyLockedOut) {
            disableResetPasswordButton();
        } else {
            const resetBtn = cta.querySelector('.reset-btn');
            if (resetBtn) {
                resetBtn.disabled = false;
                resetBtn.style.cursor = 'pointer';
                resetBtn.style.opacity = '1';
                resetBtn.style.pointerEvents = 'auto';
            }
        }
    }
    
});
