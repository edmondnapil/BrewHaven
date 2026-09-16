// Reset Password Step 5 - Create New Password (session-gated server-side;
// the account being reset is never taken from a URL param or hidden field).
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgotPasswordForm');
    const newPasswordInput = document.getElementById('new_password');
    const reenterPasswordInput = document.getElementById('reenter_password');
    const passwordStrengthDiv = document.getElementById('passwordStrength');
    if (passwordStrengthDiv) {
        passwordStrengthDiv.textContent = '';
        passwordStrengthDiv.style.display = 'none';
    }
    let passwordExistsDebounce = null;
    let passwordWithExistsError = null;

    // Define maximum character limits for password fields
    const maxLengths = {
        'new_password': 30,
        'reenter_password': 30
    };

    // Function to get max length for a field
    function getMaxLength(fieldName, value) {
        const max = maxLengths[fieldName];
        if (!max) return null;
        // For password fields, count all characters
        return { max: max, current: (value || '').length, type: 'characters' };
    }

    // Form submission
    form.addEventListener('submit', handleForgotPassword);
    
    newPasswordInput.addEventListener('input', handleNewPasswordInput);
    newPasswordInput.addEventListener('keydown', handleMaxLengthKeydown);
    reenterPasswordInput.addEventListener('input', handleReenterPasswordInput);
    reenterPasswordInput.addEventListener('keydown', handleMaxLengthKeydown);
    function handleNewPasswordInput() {
        const value = newPasswordInput.value;
        const trimmedValue = value.trim();
        const errorDiv = document.getElementById('new_password-error');
        
        // Check max length
        const maxInfo = getMaxLength('new_password', value);
        const isMaxLengthReached = maxInfo && maxInfo.current >= maxInfo.max;
        
        // Truncate if exceeded
        if (maxInfo && value.length > maxInfo.max) {
            newPasswordInput.value = value.substring(0, maxInfo.max);
            return;
        }
        
        // Show max length error if reached
        if (isMaxLengthReached) {
            if (errorDiv) {
                const errorMsg = `You've already reached the maximum of ${maxInfo.max} characters.`;
                errorDiv.textContent = errorMsg;
                errorDiv.style.color = '#ff4444';
                errorDiv.style.fontSize = '0.78rem';
                errorDiv.style.display = 'block';
                errorDiv.classList.add('show');
            }
            newPasswordInput.style.borderColor = '#ff4444';
            return;
        } else {
            // Clear max length error if under limit
            if (errorDiv && errorDiv.textContent.includes('reached the maximum')) {
                errorDiv.textContent = '';
                errorDiv.classList.remove('show');
            }
        }
        
        clearTimeout(passwordExistsDebounce);
        
        if (!trimmedValue) {
            passwordWithExistsError = null;
        } else if (trimmedValue.length >= 8 && !isMaxLengthReached) {
            passwordExistsDebounce = setTimeout(() => {
                checkPasswordExistsForReset(trimmedValue);
            }, 350);
        } else if (passwordWithExistsError !== null && trimmedValue !== passwordWithExistsError) {
            passwordWithExistsError = null;
        }
        
        // Check password strength and validate (only if max length not reached)
        if (!isMaxLengthReached) {
            validateNewPassword();
        }
        
        if (reenterPasswordInput.value.trim()) {
            validateReenterPassword();
        }
    }
    
    function handleForgotPassword(e) {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        const formData = new FormData(form);

        fetch('forgot-password.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reset login attempts and lockout state in local storage
                localStorage.setItem('loginAttempts', '0');
                localStorage.removeItem('lockoutEndTime');
                localStorage.removeItem('lockoutLevel');
                localStorage.removeItem('showResetCTA');

                form.style.display = 'none';
                const successView = document.getElementById('resetSuccessView');
                if (successView) { successView.style.display = 'block'; }
            } else if (data.expired_session) {
                window.location.href = 'authentication.php';
            } else {
                alert(data.message || 'Unable to reset password');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }

    function validateForm() {
        let isValid = true;
        if (!validateNewPassword()) {
            isValid = false;
        }
        if (!validateReenterPassword()) {
            isValid = false;
        }
        return isValid;
    }
    
    function getPasswordStrength(password) {
        if (window.BHValidation && window.BHValidation.validatePasswordStrength) {
            const res = window.BHValidation.validatePasswordStrength(password);
            return {
                level: res.level,
                message: res.error,
                missing: []
            };
        }
        if (!password) {
            return { level: 'empty', message: 'Password is required', missing: [] };
        }
        const hasLetter = /[A-Za-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[^A-Za-z0-9]/.test(password);
        const hasMinLength = password.length >= 8;

        if (!hasLetter) return { level: 'weak', message: 'Weak: Add a letter', missing: [] };
        if (!hasNumber) return { level: 'weak', message: 'Weak: Add a number', missing: [] };
        if (!hasMinLength) return { level: 'weak', message: 'Weak: Password must be at least 8 characters', missing: [] };
        if (!hasSpecial) return { level: 'medium', message: 'Medium: Add special characters to strengthen', missing: [] };
        return { level: 'strong', message: 'Strong password!', missing: [] };
    }
    
    function validateNewPassword() {
        const value = newPasswordInput.value.trim();
        const errorDiv = document.getElementById('new_password-error');
        const strengthBarEl = form ? form.querySelector('.strength-bar') : null;
        const strengthMessageEl = form ? form.querySelector('.password-strength-message') : null;
        
        // Update strength bar indicator & message
        if (window.BHValidation && window.BHValidation.updatePasswordStrengthUI) {
            window.BHValidation.updatePasswordStrengthUI(value, strengthBarEl, strengthMessageEl);
        }
        
        // Check max length first
        const maxInfo = getMaxLength('new_password', newPasswordInput.value);
        const isMaxLengthReached = maxInfo && maxInfo.current >= maxInfo.max;
        
        // Skip validation if password exists error is active for current password value
        if (passwordWithExistsError !== null && value === passwordWithExistsError) {
            setFieldError(newPasswordInput, errorDiv, 'Password already exists');
            return false; // Don't validate strength when password exists
        }
        
        // Skip validation if max length reached
        if (isMaxLengthReached) {
            if (errorDiv && !errorDiv.textContent.includes('reached the maximum')) {
                const errorMsg = `You've already reached the maximum of ${maxInfo.max} characters.`;
                setFieldError(newPasswordInput, errorDiv, errorMsg);
            }
            return false;
        }
        
        if (!value) {
            setFieldError(newPasswordInput, errorDiv, 'Password is required');
            return false;
        }
        
        const result = getPasswordStrength(value);
        
        // For validation, we'll only reject weak passwords
        // Medium and strong passwords are acceptable
        if (result.level === 'weak') {
            setFieldError(newPasswordInput, errorDiv, result.message);
            return false;
        }
        
        // Clear error div for medium and strong passwords
        if (errorDiv) {
            // Don't clear if it's a max length error
            if (!errorDiv.textContent.includes('reached the maximum')) {
                errorDiv.textContent = '';
                errorDiv.classList.remove('show');
            }
        }
        
        // Show appropriate message for medium and strong passwords
        if (result.level === 'strong') {
            setFieldSuccess(newPasswordInput, errorDiv, '');
        } else if (result.level === 'medium') {
            if (errorDiv) {
                errorDiv.textContent = '';
                errorDiv.classList.remove('show');
            }
            newPasswordInput.style.borderColor = '#ffbb33';
        }
        
        return true;
    }
    
    function checkPasswordExistsForReset(password) {
        if (!password || password.length < 8) return;
        const currentValue = newPasswordInput.value.trim();
        
        fetch('../php/check_password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `password=${encodeURIComponent(password)}`
        })
        .then(response => response.json())
        .then(data => {
            if (newPasswordInput.value.trim() !== password) {
                return;
            }
            if (data && data.success && data.data && data.data.exists) {
                passwordWithExistsError = password;
                const errorDiv = document.getElementById('new_password-error');
                setFieldError(newPasswordInput, errorDiv, 'Password already exists');
            } else {
                if (passwordWithExistsError !== null && passwordWithExistsError === password) {
                    passwordWithExistsError = null;
                }
                validateNewPassword();
            }
        })
        .catch(() => {});
    }
    
    function handleReenterPasswordInput() {
        const value = reenterPasswordInput.value;
        const errorDiv = document.getElementById('reenter_password-error');
        
        // Check max length
        const maxInfo = getMaxLength('reenter_password', value);
        const isMaxLengthReached = maxInfo && maxInfo.current >= maxInfo.max;
        
        // Truncate if exceeded
        if (maxInfo && value.length > maxInfo.max) {
            reenterPasswordInput.value = value.substring(0, maxInfo.max);
            return;
        }
        
        // Show max length error if reached
        if (isMaxLengthReached) {
            if (errorDiv) {
                const errorMsg = `You've already reached the maximum of ${maxInfo.max} characters.`;
                errorDiv.textContent = errorMsg;
                errorDiv.style.color = '#ff4444';
                errorDiv.style.fontSize = '0.78rem';
                errorDiv.style.display = 'block';
                errorDiv.classList.add('show');
            }
            reenterPasswordInput.style.borderColor = '#ff4444';
            return;
        } else {
            // Clear max length error if under limit
            if (errorDiv && errorDiv.textContent.includes('reached the maximum')) {
                errorDiv.textContent = '';
                errorDiv.classList.remove('show');
            }
        }
        
        // Validate password match
        validateReenterPassword();
    }
    
    function validateReenterPassword() {
        const password = newPasswordInput.value.trim();
        const confirmPassword = reenterPasswordInput.value.trim();
        const errorDiv = document.getElementById('reenter_password-error');
        
        // Check max length first
        const maxInfo = getMaxLength('reenter_password', reenterPasswordInput.value);
        const isMaxLengthReached = maxInfo && maxInfo.current >= maxInfo.max;
        
        if (isMaxLengthReached) {
            if (errorDiv && !errorDiv.textContent.includes('reached the maximum')) {
                const errorMsg = `You've already reached the maximum of ${maxInfo.max} characters.`;
                setFieldError(reenterPasswordInput, errorDiv, errorMsg);
            }
            return false;
        }
        
        if (!confirmPassword) {
            setFieldError(reenterPasswordInput, errorDiv, 'Please re-enter your password');
            return false;
        }
        if (!password) {
            setFieldError(reenterPasswordInput, errorDiv, 'Enter a new password first');
            return false;
        }
        if (password !== confirmPassword) {
            setFieldError(reenterPasswordInput, errorDiv, 'Passwords do not match');
            return false;
        }
        setFieldSuccess(reenterPasswordInput, errorDiv, 'Passwords match');
        return true;
    }
    
    function handleMaxLengthKeydown(e) {
        const field = e.target;
        const fieldName = field.name || field.id;
        if (!fieldName) return;
        
        const maxInfo = getMaxLength(fieldName, field.value);
        if (!maxInfo) return;
        
        // Allow backspace, delete, arrow keys, etc.
        const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'Tab'];
        if (allowedKeys.includes(e.key)) return;
        
        // Allow Ctrl/Cmd combinations (copy, paste, select all, etc.)
        if (e.ctrlKey || e.metaKey) return;
        
        // Check if adding this character would exceed the limit
        if (maxInfo.current >= maxInfo.max) {
            e.preventDefault();
            e.stopPropagation();
            // Show error message
            const errorDiv = document.getElementById(fieldName + '-error');
            if (errorDiv) {
                const errorMsg = `You've already reached the maximum of ${maxInfo.max} characters.`;
                errorDiv.textContent = errorMsg;
                errorDiv.style.color = '#ff4444';
                errorDiv.style.fontSize = '0.78rem';
                errorDiv.style.display = 'block';
                errorDiv.classList.add('show');
                field.style.borderColor = '#ff4444';
            }
            return false;
        }
    }
    
    function setFieldError(field, errorDiv, message) {
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.color = '#ff4444';
            errorDiv.style.fontSize = '0.78rem';
            errorDiv.style.display = 'block';
            errorDiv.classList.add('show');
        }
        field.style.borderColor = '#ff4444';
    }
    
    function setFieldSuccess(field, errorDiv, message) {
        if (errorDiv) {
            errorDiv.textContent = message || '';
            errorDiv.style.color = '#00a854';
            errorDiv.style.fontSize = '0.78rem';
            errorDiv.style.display = 'block';
            errorDiv.classList.add('show');
        }
        field.style.borderColor = '#00a854';
    }
    
    function clearFieldState(field, errorDiv) {
        if (errorDiv) {
            errorDiv.textContent = '';
            errorDiv.classList.remove('show');
        }
        field.style.borderColor = 'rgba(196, 122, 44, 0.3)';
    }
    
    function clearError(e) {
        const field = e.target;
        if (!field) return;
        if (field.value.trim() !== '') {
            return;
        }
        const errorDiv = document.getElementById(field.name + '-error');
        clearFieldState(field, errorDiv);
    }
});
