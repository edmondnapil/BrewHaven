// Registration Form Validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.register-form');
    if (!form) {
        console.error('Registration form not found!');
        return;
    }
    const inputs = form.querySelectorAll('input, select');
    
    // Define maximum character limits for each field
    const maxLengths = {
        'firstname': 30,
        'lastname': 30,
        'middlename': 30,
        'extension': 10,
        'username': 20,
        'email': 30,
        'password': 30,
        'repassword': 30,
        'street': 30,
        'barangay': 30,
        'city_municipality': 30,
        'province': 30,
        'country': 30,
        'zipcode': 4,
        'idnumber': 9, // YYYY-XXXX format
        'auth_answer1': 30,
        'auth_answer2': 30,
        'auth_answer3': 30
    };
    
    // Function to get max length for a field (counting only letters for name fields)
    function getMaxLength(fieldName, value) {
        const max = maxLengths[fieldName];
        if (!max) return null;
        
        // For name fields, count only letters (not spaces)
        if (['firstname', 'lastname', 'middlename'].includes(fieldName)) {
            const letterCount = (value || '').replace(/\s/g, '').replace(/[^a-zA-ZñÑ]/g, '').length;
            return { max: max, current: letterCount, type: 'letters' };
        }
        
        // For zipcode and idnumber, count all characters
        if (['zipcode', 'idnumber'].includes(fieldName)) {
            return { max: max, current: (value || '').length, type: 'characters' };
        }
        
        // For other fields, count all characters except spaces for address fields
        if (['street', 'barangay', 'city_municipality', 'province', 'country'].includes(fieldName)) {
            const charCount = (value || '').replace(/\s/g, '').length;
            return { max: max, current: charCount, type: 'characters' };
        }
        
        // For username, email, extension, auth answers - count all characters
        return { max: max, current: (value || '').length, type: 'characters' };
    }
    
    // Function to prevent input when max length is reached
    function enforceMaxLength(field) {
        const fieldName = field.name || field.id;
        if (!fieldName) return;
        
        const maxInfo = getMaxLength(fieldName, field.value);
        if (!maxInfo) return;
        
        // Check if max is reached
        if (maxInfo.current >= maxInfo.max) {
            // Prevent further input
            field.addEventListener('input', function preventMaxInput(e) {
                const currentMaxInfo = getMaxLength(fieldName, this.value);
                if (currentMaxInfo && currentMaxInfo.current >= currentMaxInfo.max) {
                    // Check if user is trying to add more characters
                    if (this.value.length > e.target.value.length) {
                        // This shouldn't happen, but just in case
                        return;
                    }
                    // Prevent paste or any other input that would exceed limit
                    const newValue = e.target.value;
                    const newMaxInfo = getMaxLength(fieldName, newValue);
                    if (newMaxInfo && newMaxInfo.current > newMaxInfo.max) {
                        // Restore previous value
                        e.preventDefault();
                        e.stopPropagation();
                        this.value = this.defaultValue || '';
                        return false;
                    }
                }
            }, { once: false });
            
            // Show error message
            const errorDiv = document.getElementById(fieldName + '-error');
            if (errorDiv) {
                const errorMsg = `You've already reached the maximum of ${maxInfo.max} ${maxInfo.type === 'letters' ? 'letters' : 'characters'}.`;
                errorDiv.textContent = errorMsg;
                errorDiv.classList.add('show');
                field.style.borderColor = '#f51a2cff';
            }
        } else {
            // Clear max length error if under limit
            const errorDiv = document.getElementById(fieldName + '-error');
            if (errorDiv && errorDiv.textContent.includes('reached the maximum')) {
                // Only clear if it's the max length error, not other validation errors
                const otherErrors = ['required', 'format', 'invalid', 'already exists', 'must be', 'cannot', 'not allowed'];
                const isMaxLengthError = !otherErrors.some(err => errorDiv.textContent.toLowerCase().includes(err));
                if (isMaxLengthError) {
                    errorDiv.textContent = '';
                    errorDiv.classList.remove('show');
                    field.style.borderColor = '';
                }
            }
        }
    }
    
    // Function to handle input events and prevent exceeding max length
    function handleMaxLengthInput(e) {
        const field = e.target;
        const fieldName = field.name || field.id;
        if (!fieldName) return;
        
        const maxInfo = getMaxLength(fieldName, field.value);
        if (!maxInfo) return;
        
        // Special handling for password fields - show max length error in strength message area
        if (fieldName === 'password' || fieldName === 'repassword') {
            if (maxInfo.current >= maxInfo.max) {
                // Truncate if exceeded
                if (field.value.length > maxInfo.max) {
                    field.value = field.value.substring(0, maxInfo.max);
                }
                
                // Show max length error in strength message area (for password) or error div (for repassword)
                if (fieldName === 'password') {
                    const strengthMessage = document.querySelector('.password-strength-message');
                    if (strengthMessage) {
                        const errorMsg = `You've already reached the maximum of ${maxInfo.max} characters.`;
                        strengthMessage.textContent = errorMsg;
                        strengthMessage.style.color = '#ff4444';
                    }
                    // Clear strength bar
                    const strengthBar = document.querySelector('.strength-bar');
                    if (strengthBar) {
                        strengthBar.className = 'strength-bar';
                        strengthBar.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
                    }
                } else {
                    // For repassword, show in error div
                    const errorDiv = document.getElementById(fieldName + '-error');
                    if (errorDiv) {
                        const errorMsg = `You've already reached the maximum of ${maxInfo.max} characters.`;
                        errorDiv.textContent = errorMsg;
                        errorDiv.classList.add('show');
                        field.style.borderColor = '#f51a2cff';
                    }
                }
            } else {
                // Clear max length error if under limit
                if (fieldName === 'password') {
                    const strengthMessage = document.querySelector('.password-strength-message');
                    if (strengthMessage && strengthMessage.textContent.includes('reached the maximum')) {
                        // Clear the max length error, but don't clear other messages like "Password already exists"
                        strengthMessage.textContent = '';
                    }
                } else {
                    const errorDiv = document.getElementById(fieldName + '-error');
                    if (errorDiv && errorDiv.textContent.includes('reached the maximum')) {
                        errorDiv.textContent = '';
                        errorDiv.classList.remove('show');
                    }
                }
            }
            return; // Exit early for password fields
        }
        
        // If max is reached, truncate and show error
        if (maxInfo.current >= maxInfo.max) {
            // For name fields, we need to truncate based on letter count
            if (['firstname', 'lastname', 'middlename'].includes(fieldName)) {
                // Truncate to keep only max letters
                let letterCount = 0;
                let truncatedValue = '';
                for (let i = 0; i < field.value.length; i++) {
                    const char = field.value[i];
                    if (/[a-zA-ZñÑ]/.test(char)) {
                        if (letterCount >= maxInfo.max) {
                            break;
                        }
                        letterCount++;
                    }
                    truncatedValue += char;
                }
                if (field.value !== truncatedValue) {
                    field.value = truncatedValue;
                }
            } else if (['street', 'barangay', 'city_municipality', 'province', 'country'].includes(fieldName)) {
                // For address fields, truncate based on character count (excluding spaces)
                let charCount = 0;
                let truncatedValue = '';
                for (let i = 0; i < field.value.length; i++) {
                    const char = field.value[i];
                    if (char !== ' ') {
                        if (charCount >= maxInfo.max) {
                            break;
                        }
                        charCount++;
                    }
                    truncatedValue += char;
                }
                if (field.value !== truncatedValue) {
                    field.value = truncatedValue;
                }
            } else {
                // For other fields, simple character truncation
                if (field.value.length > maxInfo.max) {
                    field.value = field.value.substring(0, maxInfo.max);
                }
            }
            
            // Show error message
            const errorDiv = document.getElementById(fieldName + '-error');
            if (errorDiv) {
                const errorMsg = `You've already reached the maximum of ${maxInfo.max} ${maxInfo.type === 'letters' ? 'letters' : 'characters'}.`;
                // Only show if not already showing this error
                if (!errorDiv.textContent.includes('reached the maximum')) {
                    errorDiv.textContent = errorMsg;
                    errorDiv.classList.add('show');
                    field.style.borderColor = '#f51a2cff';
                }
            }
        } else {
            // Clear max length error if under limit (but preserve other errors)
            const errorDiv = document.getElementById(fieldName + '-error');
            if (errorDiv && errorDiv.textContent.includes('reached the maximum')) {
                errorDiv.textContent = '';
                errorDiv.classList.remove('show');
                // Don't reset border color here - let validation handle it
            }
        }
    }
    
    // Function to handle keydown to prevent input when max is reached
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
            // Special handling for password fields
            if (fieldName === 'password') {
                e.preventDefault();
                e.stopPropagation();
                // Show max length error in strength message area
                const strengthMessage = document.querySelector('.password-strength-message');
                if (strengthMessage) {
                    const errorMsg = `You've already reached the maximum of ${maxInfo.max} characters.`;
                    strengthMessage.textContent = errorMsg;
                    strengthMessage.style.color = '#ff4444';
                }
                // Clear strength bar
                const strengthBar = document.querySelector('.strength-bar');
                if (strengthBar) {
                    strengthBar.className = 'strength-bar';
                    strengthBar.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
                }
                return false;
            } else if (fieldName === 'repassword') {
                // For repassword, check character count
                if (field.value.length >= maxInfo.max) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Show error message
                    const errorDiv = document.getElementById(fieldName + '-error');
                    if (errorDiv) {
                        const errorMsg = `You've already reached the maximum of ${maxInfo.max} characters.`;
                        errorDiv.textContent = errorMsg;
                        errorDiv.classList.add('show');
                        field.style.borderColor = '#f51a2cff';
                    }
                    return false;
                }
            } else if (['firstname', 'lastname', 'middlename'].includes(fieldName)) {
                // For name fields, check if adding would exceed letter count
                const testValue = field.value + e.key;
                const testMaxInfo = getMaxLength(fieldName, testValue);
                if (testMaxInfo && testMaxInfo.current > testMaxInfo.max) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Show error message
                    const errorDiv = document.getElementById(fieldName + '-error');
                    if (errorDiv) {
                        const errorMsg = `You've already reached the maximum of ${maxInfo.max} letters.`;
                        errorDiv.textContent = errorMsg;
                        errorDiv.classList.add('show');
                        field.style.borderColor = '#f51a2cff';
                    }
                    return false;
                }
            } else {
                // For other fields, check character count
                if (field.value.length >= maxInfo.max) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Show error message
                    const errorDiv = document.getElementById(fieldName + '-error');
                    if (errorDiv) {
                        const errorMsg = `You've already reached the maximum of ${maxInfo.max} characters.`;
                        errorDiv.textContent = errorMsg;
                        errorDiv.classList.add('show');
                        field.style.borderColor = '#f51a2cff';
                    }
                    return false;
                }
            }
        }
    }
    
    // Function to handle paste events
    function handleMaxLengthPaste(e) {
        const field = e.target;
        const fieldName = field.name || field.id;
        if (!fieldName) return;
        
        const maxInfo = getMaxLength(fieldName, field.value);
        if (!maxInfo) return;
        
        // Get pasted text
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        const testValue = field.value + pastedText;
        const testMaxInfo = getMaxLength(fieldName, testValue);
        
        if (testMaxInfo && testMaxInfo.current > testMaxInfo.max) {
            e.preventDefault();
            e.stopPropagation();
            
            // Truncate pasted text to fit within limit
            let allowedText = '';
            if (['firstname', 'lastname', 'middlename'].includes(fieldName)) {
                // For name fields, count letters
                let letterCount = maxInfo.current;
                for (let i = 0; i < pastedText.length && letterCount < maxInfo.max; i++) {
                    const char = pastedText[i];
                    if (/[a-zA-ZñÑ]/.test(char)) {
                        letterCount++;
                    }
                    allowedText += char;
                }
            } else if (['street', 'barangay', 'city_municipality', 'province', 'country'].includes(fieldName)) {
                // For address fields, count characters (excluding spaces)
                let charCount = maxInfo.current;
                for (let i = 0; i < pastedText.length && charCount < maxInfo.max; i++) {
                    const char = pastedText[i];
                    if (char !== ' ') {
                        charCount++;
                    }
                    allowedText += char;
                }
            } else {
                // For other fields, simple character limit
                const remainingChars = maxInfo.max - field.value.length;
                allowedText = pastedText.substring(0, Math.max(0, remainingChars));
            }
            
            // Insert allowed text
            if (allowedText.length > 0) {
                const start = field.selectionStart || 0;
                const end = field.selectionEnd || 0;
                field.value = field.value.substring(0, start) + allowedText + field.value.substring(end);
                field.setSelectionRange(start + allowedText.length, start + allowedText.length);
            }
            
            // Show error message - special handling for password field
            if (fieldName === 'password') {
                const strengthMessage = document.querySelector('.password-strength-message');
                if (strengthMessage) {
                    const errorMsg = `You've already reached the maximum of ${maxInfo.max} characters.`;
                    strengthMessage.textContent = errorMsg;
                    strengthMessage.style.color = '#ff4444';
                }
                // Clear strength bar
                const strengthBar = document.querySelector('.strength-bar');
                if (strengthBar) {
                    strengthBar.className = 'strength-bar';
                    strengthBar.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
                }
            } else {
                const errorDiv = document.getElementById(fieldName + '-error');
                if (errorDiv) {
                    const errorMsg = `You've already reached the maximum of ${maxInfo.max} ${maxInfo.type === 'letters' ? 'letters' : 'characters'}.`;
                    errorDiv.textContent = errorMsg;
                    errorDiv.classList.add('show');
                    field.style.borderColor = '#f51a2cff';
                }
            }
            return false;
        }
    }
    
    // Apply max length enforcement to all relevant input fields
    inputs.forEach(input => {
        const fieldName = input.name || input.id;
        if (maxLengths[fieldName] && input.tagName === 'INPUT' && input.type !== 'checkbox' && input.type !== 'radio') {
            // Add event listeners for max length enforcement
            input.addEventListener('keydown', handleMaxLengthKeydown);
            input.addEventListener('input', handleMaxLengthInput);
            input.addEventListener('paste', handleMaxLengthPaste);
        }
    });
    
		// Preallocate persistent error containers under each input to avoid layout shifts
		inputs.forEach(function(input) {
			if (!input.name) return;
			let errorDiv = document.getElementById(input.name + '-error');
			if (!errorDiv) {
				errorDiv = document.createElement('div');
				errorDiv.id = input.name + '-error';
				errorDiv.className = 'error-message';
				errorDiv.textContent = '';
				if (input.parentNode) {
					input.parentNode.insertBefore(errorDiv, input.nextSibling);
				}
			}
			// CSS handles min-height and display - do not set inline styles that could cause layout shifts
			// Error messages already have reserved space via CSS (min-height: 20px)
		});
		
    // Save form data to localStorage (save ALL fields including passwords for session persistence)
    function saveFormData() {
        const data = {};
        
        // Save all form fields (including passwords, security answers, age, etc.)
        inputs.forEach(input => {
            // Save the value based on input type
            if (input.type === 'checkbox' || input.type === 'radio') {
                if (input.checked) {
                    data[input.name] = input.value;
                }
            } else if (input.tagName === 'SELECT') {
                data[input.name] = input.value;
            } else {
                data[input.name] = input.value;
            }
        });
        
        // Save to localStorage
        localStorage.setItem('registrationFormData', JSON.stringify(data));
    }
    
    // Check if page was loaded via refresh
    function isPageRefresh() {
        // Check using Performance Navigation API
        if (performance.navigation) {
            return performance.navigation.type === 1; // TYPE_RELOAD
        }
        // Fallback for newer browsers
        const navigationEntries = performance.getEntriesByType('navigation');
        if (navigationEntries.length > 0) {
            return navigationEntries[0].type === 'reload';
        }
        return false;
    }
    
    // Check if this is a new session (system reopened)
    // sessionStorage is automatically cleared when the browser is closed,
    // so if the marker doesn't exist, it means the system was reopened
    function isNewSession() {
        // Check if sessionStorage has a session marker
        // If not, it means the browser was closed and reopened (new session)
        return !sessionStorage.getItem('registrationSessionActive');
    }
    
    // Clear all form input values
    function clearFormInputs() {
        inputs.forEach(input => {
            // Customer ID is a readonly, server-generated preview (see register.php) —
            // regenerated fresh on every page load, so it's never stale user data to clear.
            if (input.id === 'idnumber') return;
            if (input.tagName === 'SELECT') {
                input.value = '';
            } else {
                input.value = '';
            }
        });
        // Clear age field
        const ageField = document.getElementById('age');
        if (ageField) {
            ageField.value = '';
        }
    }
    
    // Restore form data from localStorage
    function restoreFormData() {
        // Clear form data if page was refreshed
        if (isPageRefresh()) {
            clearFormData();
            clearFormInputs();
            return;
        }
        
        // If this is a new session (system reopened), clear everything
        // This ensures no data (including id_number) persists across browser sessions
        if (isNewSession()) {
            // Clear localStorage to remove all saved form data
            clearFormData();
            // Clear all form fields including id_number and reset its state
            clearFormInputs();
            // Mark this as an active session so data persists during navigation
            sessionStorage.setItem('registrationSessionActive', 'true');
            return;
        }
        
        // Same session - restore form data
        try {
            const savedData = localStorage.getItem('registrationFormData');
            if (!savedData) {
                return;
            }
            
            const data = JSON.parse(savedData);
            
            // Restore each field (including passwords, age, and all other fields)
            inputs.forEach(input => {
                const fieldName = input.name;
                if (data.hasOwnProperty(fieldName)) {
                    // Restore empty values too (to clear fields that were cleared)
                    if (input.tagName === 'SELECT') {
                        input.value = data[fieldName] || '';
                        // Trigger change event for select elements
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        input.value = data[fieldName] || '';
                        // Trigger input event to trigger validation and other handlers
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            });
            
            // If birth_date was restored, calculate age (but also restore age if it was saved)
            const birthDate = document.getElementById('birth_date');
            const ageField = document.getElementById('age');
            if (birthDate && birthDate.value) {
                calculateAge();
            } else if (ageField && data.hasOwnProperty('age')) {
                // Restore age even if birth_date is empty (in case age was manually set)
                ageField.value = data.age || '';
            }
            
            // Restore id_number field state if it has a value (make it readOnly)
            const idNumberField = document.getElementById('idnumber');
            if (idNumberField && data.hasOwnProperty('idnumber') && data.idnumber) {
                idNumberField.readOnly = true;
                idNumberField.style.cursor = 'not-allowed';
            }
        } catch (error) {
            console.error('Error restoring form data:', error);
            // Clear corrupted data
            clearFormData();
        }
    }
    
    // Clear saved form data
    function clearFormData() {
        localStorage.removeItem('registrationFormData');
    }
    
    // Restore form data from localStorage on page load
    restoreFormData();
    
    // Note: Session marker is already set in restoreFormData() if it's a new session
    // For same-session navigation, ensure marker exists
    if (!sessionStorage.getItem('registrationSessionActive')) {
        sessionStorage.setItem('registrationSessionActive', 'true');
    }
    
    // Real-time validation
    inputs.forEach(input => {
        // Special handling for ID Number field - don't validate on focus
        if (input.name === 'idnumber') {
            // Only validate on input (after typing) or blur (when leaving with invalid value)
            // Do NOT validate on focus
            input.addEventListener('input', validateField); // Validate while typing
            input.addEventListener('blur', validateField); // Validate when leaving field
        } else {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', validateField); // Validate while typing
        }
        // Ensure border color stays golden-brown on focus
        input.addEventListener('focus', function() {
            // Always set to golden-brown on focus to override any JavaScript-set colors
            this.style.borderColor = 'rgba(196, 122, 44, 0.3)';
        });
        // Save form data when field changes (save ALL fields including passwords)
        input.addEventListener('input', saveFormData);
        input.addEventListener('change', saveFormData);
    });
    
    // Add event listeners for password fields
    const passwordField = document.getElementById('password');
    const repasswordField = document.getElementById('repassword');
    
    // Debounce timer for password existence check
    let passwordExistsDebounce;
    // Track the password value that triggered "already exists" error
    let passwordWithExistsError = null;
    
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            const passwordValue = this.value;
            const strengthMessage = document.querySelector('.password-strength-message');
            const strengthBar = document.querySelector('.strength-bar');
            const errorDiv = document.getElementById('password-error');
            
            // Check if max length is reached - if so, skip strength checking
            const maxInfo = getMaxLength('password', passwordValue);
            const isMaxLengthReached = maxInfo && maxInfo.current >= maxInfo.max;
            
            // If max length error is showing, don't override it with strength messages
            if (isMaxLengthReached && strengthMessage && strengthMessage.textContent.includes('reached the maximum')) {
                return; // Skip strength checking when max length error is showing
            }
            
            // Clear previous debounce timer
            clearTimeout(passwordExistsDebounce);
            
            // Check if password value has changed from the one that triggered "already exists" error
            const passwordChanged = passwordWithExistsError !== null && passwordValue !== passwordWithExistsError;
            
            // If password value changed, clear the "already exists" error state
            if (passwordChanged) {
                passwordWithExistsError = null;
                // Clear the message from strength message area
                if (strengthMessage && strengthMessage.textContent === 'Password already exists') {
                    strengthMessage.textContent = '';
                }
            }
            
            // Check if password exists error is currently showing - if so, skip strength evaluation
            const hasPasswordExistsError = passwordWithExistsError !== null && passwordValue === passwordWithExistsError;
            
            // Handle empty password - show "Password is required" in strength message area
            if (!passwordValue) {
                // Keep strength bar neutral/empty
                if (strengthBar) {
                    strengthBar.className = 'strength-bar';
                    strengthBar.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
                }
                // Show "Password is required" in strength message area
                if (strengthMessage) {
                    strengthMessage.textContent = 'Password is required';
                    strengthMessage.style.color = '#ff4444';
                }
                // Clear password exists error state if password is empty
                passwordWithExistsError = null;
                // Clear error div
                if (errorDiv) {
                    errorDiv.textContent = '';
                    errorDiv.style.display = 'none';
                    errorDiv.classList.remove('show');
                }
                return; // Skip strength checking
            }
            
            // Check password existence (debounced) - only if password has minimum length
            if (passwordValue.length >= 8) {
                passwordExistsDebounce = setTimeout(function() {
                    checkPasswordExists(passwordValue);
                }, 400);
            } else {
                // Clear password exists error if password is too short
                if (hasPasswordExistsError || passwordWithExistsError !== null) {
                    passwordWithExistsError = null;
                    // Clear the message from strength message area
                    if (strengthMessage && strengthMessage.textContent === 'Password already exists') {
                        strengthMessage.textContent = '';
                    }
                }
            }
            
            // Skip password strength evaluation if password exists error is showing
            if (hasPasswordExistsError) {
                return; // Don't evaluate strength or show other validation errors
            }
            
            // Check password strength and validate (only if password doesn't exist and max length not reached)
            if (!isMaxLengthReached) {
                const strengthResult = checkPasswordStrength();
                const isValid = validatePassword(passwordValue, this);
            }
            
            // Only validate match if repassword has a value
            if (repasswordField && repasswordField.value) {
                validatePasswordMatch();
            }
            
            // Clear error div (strength messages are shown in strength message area)
            if (errorDiv) {
                errorDiv.textContent = '';
                errorDiv.style.display = 'none';
                errorDiv.classList.remove('show');
            }
            
            // Clear "Password is required" message if password has a value
            if (passwordValue && strengthMessage && strengthMessage.textContent === 'Password is required') {
                strengthMessage.textContent = '';
            }
        });
    }
    
    if (repasswordField) {
        repasswordField.addEventListener('input', function() {
            validatePasswordMatch();
        });
    }

    // Add event listeners for form validation
    const registrationForm = document.querySelector('.register-form');
    if (registrationForm) {
        // Add input event listeners for all form fields except password fields
        registrationForm.querySelectorAll('input:not(#password):not(#repassword), select').forEach(field => {
            field.addEventListener('input', validateField);
            field.addEventListener('blur', validateField);
        });

        // Add submit event listener for the form
        registrationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate all fields including passwords
            const isFormValid = validateForm();
            const isPasswordValid = passwordField ? validatePassword(passwordField.value, passwordField) : false;
            const isPasswordMatch = repasswordField ? validatePasswordMatch() : false;
            
            if (isFormValid && isPasswordValid && isPasswordMatch) {
                // Form is valid, you can submit it here
                console.log('Form is valid, submitting...');
                // registrationForm.submit();
            } else {
                // If form is invalid, focus on first error
                const firstError = this.querySelector('.error-message[style*="display: block"]');
                if (firstError) {
                    const inputId = firstError.id.replace('-error', '');
                    const inputField = document.getElementById(inputId);
                    if (inputField) {
                        inputField.focus();
                    }
                }
            }
        });
    }
    
    // Zip Code: restrict to digits only (typing, paste, drag-drop)
    (function enforceNumericZipOnly() {
        const zip = document.getElementById('zipcode');
        if (!zip) return;
        // Hint mobile keyboards to show numeric keypad without changing type semantics
        try { zip.setAttribute('inputmode', 'numeric'); zip.setAttribute('pattern', '\\d*'); } catch (e) {}
        // Sanitize on any input
        zip.addEventListener('input', function() {
            const digitsOnly = this.value.replace(/\D/g, '');
            if (this.value !== digitsOnly) {
                this.value = digitsOnly;
                // Persist sanitized value
                saveFormData();
            }
        });
        // Prevent non-digit keypresses while allowing navigation and editing keys
        zip.addEventListener('keydown', function(e) {
            const allowedKeys = [
                'Backspace','Delete','Tab','ArrowLeft','ArrowRight','ArrowUp','ArrowDown','Home','End','Escape','Enter'
            ];
            // Allow Ctrl/Meta shortcuts (copy/paste/select all)
            if (e.ctrlKey || e.metaKey) return;
            if (allowedKeys.includes(e.key)) return;
            // Allow digits from main keyboard and numpad
            if (/^\d$/.test(e.key)) return;
            // Block everything else (letters, symbols)
            e.preventDefault();
        });
    })();
    
    // Special handlers
    let usernameDebounce;
    document.getElementById('birth_date').addEventListener('change', calculateAge);
    document.getElementById('birth_date').addEventListener('input', function() {
        const birthInput = document.getElementById('birth_date');
        const ageField = document.getElementById('age');
        if (!birthInput.value) {
            // Clear age when birthdate is cleared
            ageField.value = '';
            // Clear any errors on birth_date and age fields
            clearError({ target: birthInput });
            clearError({ target: ageField });
            // Save form data after clearing age
            saveFormData();
        }
    });
    document.getElementById('password').addEventListener('input', function() {
        // Skip strength checking if password exists error is active for current password value
        const passwordValue = this.value;
        if (passwordWithExistsError !== null && passwordValue === passwordWithExistsError) {
            return; // Don't evaluate strength when password exists
        }
        checkPasswordStrength();
    });
    document.getElementById('repassword').addEventListener('input', validatePasswordMatch);
    document.getElementById('username').addEventListener('blur', checkUsernameAvailability);
    document.getElementById('username').addEventListener('input', function() {
        clearTimeout(usernameDebounce);
        usernameDebounce = setTimeout(checkUsernameAvailability, 400);
    });
    document.getElementById('email').addEventListener('blur', checkEmailAvailability);
    const idNumberField = document.getElementById('idnumber');
    idNumberField.addEventListener('blur', checkIdAvailability);
    // Removed auto-generation on focus - user must explicitly click or interact with field
    // On click: lock size and suppress errors while generating to prevent movement and messages
	let suppressIdErrorDisplay = false;
	let idSizeLocked = false;
	let prevIdWidth = '';
	let prevIdHeight = '';
	idNumberField.addEventListener('click', function() {
		// Only generate if field is empty
		if (this.value.trim() === '') {
			// Lock current visual size to prevent any shift
			const rect = this.getBoundingClientRect();
			prevIdWidth = this.style.width;
			prevIdHeight = this.style.height;
			this.style.width = rect.width + 'px';
			this.style.height = rect.height + 'px';
			idSizeLocked = true;
			// Suppress any error display during click-initiated generation
			suppressIdErrorDisplay = true;
			generateIdNumber();
		}
	});
    // Prevent manual typing in ID number field - generate ID instead
    idNumberField.addEventListener('keydown', function(e) {
        if (!this.readOnly && this.value.trim() === '') {
            e.preventDefault();
            generateIdNumber();
        }
    });
    idNumberField.addEventListener('paste', function(e) {
        if (!this.readOnly) {
            e.preventDefault();
            if (this.value.trim() === '') {
                generateIdNumber();
            }
        }
    });
    
    // Next button handler - redirects to question.php after validating basic form
    const nextButton = document.getElementById('next-button');
    if (!nextButton) {
        console.error('Next button not found!');
        return;
    }
    
    // Ensure button is enabled and clickable
    nextButton.disabled = false;
    nextButton.removeAttribute('disabled');
    nextButton.setAttribute('tabindex', '0');
    nextButton.setAttribute('type', 'button');
    nextButton.style.pointerEvents = 'auto';
    nextButton.style.cursor = 'pointer';
    nextButton.style.zIndex = '1000';
    nextButton.style.position = 'relative';
    nextButton.style.visibility = 'visible';
    nextButton.style.opacity = '1';
    
    // Function to handle button click
    function handleNextButtonClick(e) {
        e.preventDefault();
        e.stopPropagation();

        // First, explicitly validate ALL required fields to show errors
        const requiredIds = [
            'idnumber','lastname','firstname','birth_date','age','gender',
            'email','username','password','repassword',
            'street','barangay','city_municipality','province','country','zipcode'
        ];
        
        // Validate each required field explicitly with submit event type
        requiredIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                // Ensure ID number field stays readOnly if it has a value
                if (id === 'idnumber' && el.value && el.value.trim() !== '') {
                    el.readOnly = true;
                    el.setAttribute('readonly', 'readonly');
                    el.style.cursor = 'not-allowed';
                }
                // Create a submit event to trigger validation that shows required errors
                validateField({ target: el, type: 'submit' });
            }
        });

        // Also validate all other form fields to catch format errors
        inputs.forEach(input => {
            if (input.name && !requiredIds.includes(input.id)) {
                validateField({ target: input, type: 'submit' });
            }
        });

        // Use consolidated basic-form validator (register page only, no security questions)
        const isValid = validateBasicFormWithoutSecurity();

        // If invalid (including any visible error-message.show), block navigation
        if (!isValid) {
            const visibleErrors = document.querySelectorAll('.error-message.show');
            if (visibleErrors.length > 0) {
                const firstError = visibleErrors[0];
                const fieldId = firstError.id ? firstError.id.replace(/-error$/, '') : null;
                if (fieldId) {
                    const fieldEl = document.getElementById(fieldId);
                    if (fieldEl && typeof fieldEl.focus === 'function') {
                        try { fieldEl.focus(); } catch (_) {}
                    }
                }
            }
            // Ensure ID number field stays readOnly even if validation fails
            const idNumberField = document.getElementById('idnumber');
            if (idNumberField && idNumberField.value && idNumberField.value.trim() !== '') {
                idNumberField.readOnly = true;
                idNumberField.setAttribute('readonly', 'readonly');
                idNumberField.style.cursor = 'not-allowed';
            }
            return; // stay on register.php until all errors are resolved
        }

        // Ensure ID number field stays readOnly before saving and navigating
        const idNumberField = document.getElementById('idnumber');
        if (idNumberField && idNumberField.value && idNumberField.value.trim() !== '') {
            idNumberField.readOnly = true;
            idNumberField.setAttribute('readonly', 'readonly');
            idNumberField.style.cursor = 'not-allowed';
        }

        // Save current form state and proceed to questions
        saveFormData();
        // Use path relative to current page location
        const currentPath = window.location.pathname;
        let basePath = '';
        if (currentPath.lastIndexOf('/') >= 0) {
            basePath = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);
        }
        window.location.href = basePath + 'question.php';
    }
    
    // Add click event listener
    nextButton.addEventListener('click', handleNextButtonClick, false);
    
    // Also handle Enter key press for accessibility
    nextButton.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
            handleNextButtonClick(e);
        }
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Trigger validation on all inputs to surface inline errors
        // Note: validateField now preserves email and username errors during form submission
        inputs.forEach(input => validateField({ target: input }));

        // Validate full form and submit directly (questions included in the same form)
        if (validateBasicForm()) {
            const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
            const originalText = submitButton ? submitButton.textContent : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Registering...';
            }

            const formData = new FormData(form); // includes security questions now embedded in the form

            fetch('register.php', { method: 'POST', body: formData })
            .then(async (response) => {
                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    return response.json();
                }
                const text = await response.text();
                // Normalize plain-text responses from PHP
                if (text && text.trim().toLowerCase() === 'success') {
                    return { success: true };
                }
                return { success: false, message: text || 'Registration failed. Please try again.' };
            })
            .then(data => {
                if (data && data.success) {
                    // Clear saved form data on successful registration
                    clearFormData();
                    alert('Successfully registered');
                    window.location.href = 'login.php';
                } else {
                    const message = (data && (data.message || data.error)) || 'Registration failed. Please try again.';
                    // Map common server messages to specific fields
                    const lower = String(message).toLowerCase();
                    const fieldMap = [
                        { match: 'id number already exists', id: 'idnumber' },
                        { match: 'username already exists', id: 'username' },
                        { match: 'email already exists', id: 'email' },
                        { match: 'passwords do not match', id: 'repassword' },
                        { match: 'must be at least 18 years old', id: 'age' },
                        { match: 'all required fields', id: 'firstname' }
                    ];
                    let handled = false;
                    for (const rule of fieldMap) {
                        if (lower.includes(rule.match)) {
                            const f = document.getElementById(rule.id);
                            if (f) {
                                showError(f, message);
                                handled = true;
                                break;
                            }
                        }
                    }
                    if (!handled) {
                        // Fallback: show near username if available, else first required field
                        const fallback = document.getElementById('username') || form.querySelector('[required]');
                        if (fallback) {
                            showError(fallback, message);
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Registration error:', error);
                const fallback = document.getElementById('username') || form.querySelector('[required]');
                if (fallback) {
                    showError(fallback, 'An unexpected error occurred. Please try again.');
                }
            })
            .finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalText || 'Register';
                }
            });
        }
    });
    
    function getRequiredMessage(field) {
        const name = field.name;
        switch (name) {
            case 'idnumber': return 'ID number is required';
            case 'firstname': return 'First name is required';
            case 'lastname': return 'Last name is required';
            case 'gender': return 'Sex is required';
            case 'email': return 'Email is required';
            case 'username': return 'Username is required';
            case 'password': return 'Password is required';
            case 'repassword': return 'Confirm password is required';
            case 'birth_date': return 'Birth date is required';
            case 'age': return 'Age is required';
            case 'street': return 'Street is required';
            case 'barangay': return 'Barangay is required';
            case 'city_municipality': return 'City/Municipality is required';
            case 'province': return 'Province is required';
            case 'country': return 'Country is required';
            case 'zipcode': return 'Zip code is required';
            default: return 'This field is required';
        }
    }

    function shouldRunTrailingSpaceValidation(eventType) {
        if (!eventType) return true;
        return eventType === 'blur' || eventType === 'submit';
    }
    
    function validateField(e) {
        const field = e.target;
        const fieldName = field.name;
        const rawValue = field.value;
        const value = rawValue.trim();
        const isSubmitTriggered = !e || !e.type;
        const eventType = (e && e.type) ? e.type : 'submit';
        
        // For email and username, preserve errors during form submission
        // For password, preserve "Password already exists" error at all times
        let preserveError = false;
        let preservedErrorText = null;
        
        if (fieldName === 'password') {
            const strengthMessage = document.querySelector('.password-strength-message');
            // Always preserve password exists error - don't clear it
            // Check strength message area (where "Password already exists" is shown)
            if (strengthMessage && strengthMessage.textContent === 'Password already exists') {
                preserveError = true;
                preservedErrorText = strengthMessage.textContent;
            }
        } else if (isSubmitTriggered && (fieldName === 'email' || fieldName === 'username')) {
            const errorDiv = document.getElementById(fieldName + '-error');
            if (errorDiv && errorDiv.classList.contains('show')) {
                preserveError = true;
                preservedErrorText = errorDiv.textContent;
            }
        }
        
        // Keep layout stable: clear errors but don't trigger layout unless visibility changes
        // Skip clearing for email/username if preserving error, or for password if "already exists" error
        // Don't clear border color on focus - let CSS handle it
        if (!preserveError && e.type !== 'focus') {
            clearError(e);
        }
        
        // Ensure ID number field stays readOnly if it has a value (after any validation)
        if (fieldName === 'idnumber' && field.value && field.value.trim() !== '') {
            field.readOnly = true;
            field.setAttribute('readonly', 'readonly');
            field.style.cursor = 'not-allowed';
        }
        
        // Check for leading spaces for all fields except excluded ones
        // (Trailing spaces are handled by specific validators as second-to-last checks)
        const spaceExcludedFields = ['birth_date', 'age', 'gender', 'sex', 'idnumber', 'zipcode'];
        if (!spaceExcludedFields.includes(fieldName) && rawValue.length > 0) {
            // Check for any leading spaces first (single or multiple)
            if (rawValue.startsWith(' ')) {
                showError(field, 'Leading space is not allowed');
                return;
            }
        }

        // Generic required validation for any field that has the HTML required attribute
        // Skip password field - handled separately to show message in strength message area
        if (field.required && value.length === 0 && fieldName !== 'password') {
            if (fieldName === 'age') {
                const birthInput = document.getElementById('birth_date');
                if (birthInput && birthInput.value && birthInput.value.trim() !== '') {
                    validateBirthDate(birthInput.value, birthInput);
                    return;
                }
            }
            if (isSubmitTriggered || e.type === 'blur' || e.type === 'submit') {
                showError(field, getRequiredMessage(field));
            }
            return;
        }

        // Explicit required validation for key register fields that may not have the required attribute in HTML
        // Skip password field - handled separately to show message in strength message area
        const alwaysRequired = [
            'idnumber', 'firstname', 'lastname', 'gender', 'username', 'repassword',
            'birth_date', 'age',
            'street', 'barangay', 'city_municipality', 'province', 'country', 'zipcode'
        ];
        if (alwaysRequired.includes(fieldName) && value.length === 0) {
            if (fieldName === 'age') {
                const birthInput = document.getElementById('birth_date');
                if (birthInput && birthInput.value && birthInput.value.trim() !== '') {
                    validateBirthDate(birthInput.value, birthInput);
                    return;
                }
            }
            // IMPORTANT: show these required errors on submit/Next button click
            if (isSubmitTriggered || e.type === 'submit') {
                showError(field, getRequiredMessage(field));
            }
            return;
        }
        
        switch(fieldName) {
            case 'idnumber':
                // Only validate on input (after typing) or blur (when leaving), not on focus
                // Pass event type to control when to show "required" error
                // If no event type (programmatic validation), treat as submit to show required error
                validateIdNumber(value, field, eventType);
                break;
            case 'firstname':
            case 'lastname':
            case 'middlename':
                validateName(rawValue, value, field, fieldName, eventType);
                break;
            case 'extension':
                validateExtension(value, field);
                break;
            case 'birth_date':
                validateBirthDate(value, field);
                break;
            case 'age':
                validateAge(value, field);
                break;
            case 'email':
                validateEmail(rawValue, field, eventType);
                break;
            case 'username':
                validateUsername(rawValue, field);
                break;
            case 'password':
                // Skip password validation if password exists error is active
                if (passwordWithExistsError !== null && rawValue === passwordWithExistsError) {
                    // Don't validate - keep the password exists error
                    return;
                }
                validatePassword(value, field);
                break;
            case 'repassword':
                validatePasswordMatch();
                break;
            case 'auth_answer1':
            case 'auth_answer2':
            case 'auth_answer3': {
                const len = value.length;
                if (!value) {
                    showError(field, 'This field is required');
                    return;
                }
                if (len < 3) {
                    showError(field, 'Answer must be at least 3 characters');
                    return;
                }
                if (len > 30) {
                    showError(field, 'Answer must be at most 30 characters');
                    return;
                }
                // Removed capitalization requirement for security answers
                break;
            }
            case 'street':
            case 'barangay':
            case 'city_municipality':
            case 'province':
            case 'country':
                validateAddress(rawValue, field, fieldName, eventType);
                break;
            case 'zipcode':
                validateZipCode(value, field);
                break;
        }
        
        // For email and username, restore preserved error if validation didn't show a new error
        // For password, restore "Password already exists" in strength message area
        if (preserveError && preservedErrorText) {
            if (fieldName === 'password' && preservedErrorText === 'Password already exists') {
                const strengthMessage = document.querySelector('.password-strength-message');
                if (strengthMessage && strengthMessage.textContent !== 'Password already exists') {
                    // Restore "Password already exists" in strength message area
                    strengthMessage.textContent = 'Password already exists';
                    strengthMessage.style.color = '#ff4444';
                }
            } else {
                const errorDiv = document.getElementById(fieldName + '-error');
                if (!errorDiv || !errorDiv.classList.contains('show')) {
                    // No error was shown by validation, restore the preserved error
                    showError(field, preservedErrorText);
                }
            }
        }
    }
    
    // Helper function to find incorrect capitalization with exact letter and word number
    function findIncorrectCapitalization(value) {
        const words = value.split(' ');
        for (let wordIndex = 0; wordIndex < words.length; wordIndex++) {
            const word = words[wordIndex];
            if (word.length > 1) {
                const restOfWord = word.slice(1);
                for (let i = 0; i < restOfWord.length; i++) {
                    const char = restOfWord[i];
                    if (char === char.toUpperCase() && char !== char.toLowerCase()) {
                        return { 
                            letter: char, 
                            wordNumber: wordIndex + 1,
                            position: i + 2 // +2 because we skip first letter and use 1-based indexing
                        };
                    }
                }
            }
        }
        return null;
    }
    
    // Helper function to get position text
    function getPositionText(position) {
        const ordinals = ['', 'first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth',
                         'eleventh', 'twelfth', 'thirteenth', 'fourteenth', 'fifteenth', 'sixteenth', 'seventeenth', 
                         'eighteenth', 'nineteenth', 'twentieth'];
        if (position < ordinals.length) {
            return ordinals[position];
        }
        return position + 'th';
    }
    
    function validateIdNumber(value, field, eventType) {
        // New pattern: YYYY-XXXX (e.g., 2025-0001)
        const pattern = /^[0-9]{4}-[0-9]{4}$/;
        if (!value || value.trim() === '') {
            // Show "required" error on blur (when leaving field) or on submit/Next button
            // This prevents error from appearing immediately on focus
            if (eventType === 'blur' || eventType === 'submit' || !eventType) {
                showError(field, 'ID Number is required');
            }
            return; // Return early to prevent further validation
        } else if (/[a-zA-Z]/.test(value)) {
            // Show format errors immediately after user types something invalid
            showError(field, 'ID number must not contain letters');
        } else if (/[^0-9-]/.test(value)) {
            // Show format errors immediately after user types something invalid
            showError(field, 'ID number must not contain special characters');
        } else if (!pattern.test(value)) {
            // Show format errors immediately after user types something invalid
            showError(field, 'ID Number must be in format YYYY-XXXX (e.g., 2025-0001)');
        } else {
            // Check if ID already exists
            checkIdAvailability(value, field);
        }
    }
    

    
function validateName(rawValue, value, field, fieldName, eventType = 'input') {
    clearError({ target: field });

    if (field.required && !value) {
        showError(field, getRequiredMessage(field));
        return;
    }

    if (!value) return;

    const trimmedValue = value.trim();
    const allowTrailingCheck = shouldRunTrailingSpaceValidation(eventType);
    let earliestIdx = Infinity;
    let earliestMsg = '';

    const consider = (idx, msg) => {
        if (idx !== -1 && idx < earliestIdx) {
            earliestIdx = idx;
            earliestMsg = msg;
        }
    };

    // ----------------------------
    // Leading checks
    // ----------------------------
    if (/^\s/.test(rawValue)) consider(0, "Leading or trailing spaces are not allowed");

    if (/^[^A-Za-zñÑ0-9]/.test(rawValue) && fieldName !== "middlename") {
        consider(0, "Special characters are not allowed");
    }

    if (/^[0-9]/.test(rawValue)) consider(0, "Numbers are not allowed");

    if (/^[a-z]/.test(rawValue)) consider(0, "First letter must be uppercase");

    // ----------------------------
    // Main scanning rules
    // ----------------------------
    if (earliestIdx === Infinity) {
        // Numbers anywhere (catch all) — **MUST BE FIRST**  
        const numberIdx = rawValue.search(/[0-9]/);
        if (numberIdx !== -1) {
            consider(numberIdx, "Numbers are not allowed");
        }

        // Special characters anywhere (after numbers)
        let specialCharPattern = /[^a-zA-ZñÑ0-9\s]/; // period is now invalid for all
        const specialCharIdx = rawValue.search(specialCharPattern);
        if (specialCharIdx !== -1) {
            consider(specialCharIdx, "Special characters are not allowed");
        }

        // Multiple spaces (not at end)
        const doubleSpace = rawValue.match(/\s{2,}(?!$)/);
        if (doubleSpace) consider(doubleSpace.index, "Double spaces are not allowed");

        // Triple identical letters
        const tripleLetter = rawValue.toLowerCase().match(/([a-zñ])\1{2,}/);
        if (tripleLetter) consider(tripleLetter.index, "Three identical consecutive letters are not allowed");

        // All caps word (only letters)
        const words = trimmedValue.split(/\s+/).filter(Boolean);
        for (let i = 0; i < words.length; i++) {
            const w = words[i];
            const lettersOnly = w.replace(/[^A-Za-zñÑ]/g, "");
            if (lettersOnly.length > 1 && lettersOnly === lettersOnly.toUpperCase()) {
                const pos = rawValue.indexOf(w);
                if (pos !== -1) {
                    consider(pos, "All capital letters are not allowed");
                    break;
                }
            }
        }

        // Consecutive capital letters
        const consecCaps = rawValue.match(/[A-ZÑ]{2,}/);
        if (consecCaps) consider(consecCaps.index, "Consecutive capital letters are not allowed");

        // First letter of each word uppercase
        for (let i = 0; i < words.length; i++) {
            const w = words[i];
            if (w[0] !== w[0].toUpperCase()) {
                const pos = rawValue.indexOf(w);
                consider(pos, `First letter of the ${getOrdinalWord(i + 1)} word must be uppercase.`);
                break;
            }
        }

        // Incorrect capitalization inside a word
        const incorrect = findIncorrectCapitalization(rawValue);
        if (incorrect) {
            const allWords = rawValue.split(/\s+/);
            let charPos = 0;
            for (let i = 0; i < incorrect.wordNumber - 1; i++) charPos += allWords[i].length + 1;
            charPos += incorrect.position - 1;
            if (incorrect.wordNumber === 1) {
                consider(charPos, `The (${incorrect.letter}) must be lowercase.`);
            } else {
                consider(charPos, `The (${incorrect.letter}) in the ${getOrdinalWord(incorrect.wordNumber)} word must be lowercase.`);
            }
        }
    }

    // ----------------------------
    // Length checks
    // ----------------------------
    if (earliestIdx === Infinity) {
        const letterCount = trimmedValue.replace(/\s/g, "").length;
        if (letterCount < 2) {
            showError(field, "Minimum of 2 letters is required");
            return;
        }
        if (letterCount > 30) {
            showError(field, "Maximum of 30 letters is allowed");
            return;
        }
    }

    // Trailing spaces
    if (allowTrailingCheck && earliestIdx === Infinity && rawValue.endsWith(" ") && !rawValue.endsWith("  ")) {
        consider(rawValue.length - 1, "Trailing space is not allowed");
    }
    if (earliestIdx === Infinity && rawValue.endsWith("  ")) {
        consider(rawValue.lastIndexOf("  "), "Double spaces are not allowed");
    }

    // Final display
    if (earliestIdx !== Infinity) {
        showError(field, earliestMsg);
        return;
    }
}


function validateExtension(value, field) {
    if (value) {
        value = value.trim();

        // 1. Periods are not allowed anywhere
        if (value.includes('.')) {
            showError(field, 'Period are not allowed.');
            return;
        }

        // 2. No numbers allowed
        if (/[0-9]/.test(value)) {
            showError(field, 'Numbers are not allowed.');
            return;
        }

        // 3. Only letters allowed
        if (/[^a-zA-Z]/.test(value)) {
            showError(field, 'Special characters are not allowed.');
            return;
        }

        // 4. Define patterns
        const romanPattern = /^(I|II|III|IV|V|VI|VII|VIII|IX|X)$/;

        // 5. Reject all-uppercase like JR or SR (but skip for Roman numerals)
        if (!romanPattern.test(value) && value === value.toUpperCase() && value.length > 1) {
            showError(field, 'All uppercase is not allowed. Use proper case (e.g., Jr, Sr, III).');
            return;
        }

        // 6. Check capitalization (only first letter uppercase, skip for Roman)
        const correctFormat = value.charAt(0).toUpperCase() + value.slice(1).toLowerCase();
        if (!romanPattern.test(value) && value !== correctFormat) {
            showError(field, 'Follow the correct format (e.g., Jr, Sr, III).');
            return;
        }

        // 7. Validate that extension is either Jr, Sr, or Roman numerals
        const namePattern = /^(Jr|Sr)$/;
        if (!(namePattern.test(value) || romanPattern.test(value))) {
            showError(field, 'Input a valid extension (e.g., Jr, Sr, III).');
            return;
        }
    }
}

    function clearAgeRequiredError() {
        const ageField = document.getElementById('age');
        if (!ageField) return;
        const ageErrorDiv = document.getElementById('age-error');
        if (!ageErrorDiv) return;
        const isAgeRequiredVisible = ageErrorDiv.classList.contains('show') && ageErrorDiv.textContent === 'Age is required';
        if (isAgeRequiredVisible) {
            clearError({ target: ageField });
        }
    }

    function validateBirthDate(value, field) {
        const ageField = document.getElementById('age');
        if (!value) {
            showError(field, 'Birth date is required');
            // Also clear age field when birthdate is empty
            ageField.value = '';
            return;
        }
        clearAgeRequiredError();
        
        const birthDate = new Date(value);
        const today = new Date();
        if (isNaN(birthDate.getTime())) {
            if (ageField) {
                ageField.value = '';
            }
            showError(field, 'Invalid birth date');
            return;
        }
        if (birthDate > today) {
            if (ageField) {
                ageField.value = '';
                showError(ageField, 'Birth date cannot be in the future');
            } else {
                showError(field, 'Birth date cannot be in the future');
            }
            clearError({ target: field });
            return;
        }
        clearError({ target: field });
    }
    
    function calculateAge() {
        const birthInput = document.getElementById('birth_date');
        const ageField = document.getElementById('age');
        const birthDate = birthInput.value;
        if (birthDate) {
            clearAgeRequiredError();
            const today = new Date();
            const birth = new Date(birthDate);
            if (isNaN(birth.getTime())) {
                ageField.value = '';
                showError(birthInput, 'Invalid birth date');
                saveFormData();
                return;
            }
            if (birth > today) {
                ageField.value = '';
                if (ageField) {
                    showError(ageField, 'Birth date cannot be in the future');
                } else {
                    showError(birthInput, 'Birth date cannot be in the future');
                }
                clearError({ target: birthInput });
                // Save form data after clearing age
                saveFormData();
                return;
            }
            clearError({ target: birthInput });
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();           
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            ageField.value = age;
            // Validate age and display error next to Age field
            validateAge(String(age), ageField);
            // Save form data after calculating age (since readonly fields don't trigger input events)
            saveFormData();
        } else {
            // Clear age and save when birthdate is empty
            ageField.value = '';
            saveFormData();
        }
    }
 
    
    function validateAge(value, field) {
        if (!value) {
            showError(field, 'Age is required');
            return;
        }
        
        const age = parseInt(value);
        if (isNaN(age) || age < 0) {
            showError(field, 'Invalid age');
            return;
        }
        if (age < 18) {
            showError(field, 'Must be at least 18 years old');
            return;
        }
    }

    
function validateEmail(rawValue, field, eventType = 'input') {
    if (!rawValue) {
        showError(field, 'Email is required');
        return;
    }

    const value = rawValue.trim();
    const allowTrailingCheck = shouldRunTrailingSpaceValidation(eventType);

    // Earliest-index prioritization: find the first error position in the string
    let earliestIdx = Infinity;
    let earliestMsg = '';

    // Helper function to consider an error at a specific index
    const consider = (idx, msg) => {
        if (idx !== -1 && idx < earliestIdx) {
            earliestIdx = idx;
            earliestMsg = msg;
        }
    };

    

    // Check for uppercase letters (priority check)
    const uppercaseMatch = rawValue.match(/[A-Z]/);
    if (uppercaseMatch) {
        consider(uppercaseMatch.index, 'Invalid email format: use small letters only.');
    }

    // Check for @ symbol
    const atIndex = rawValue.indexOf('@');
    if (atIndex === -1 && earliestIdx === Infinity) {
        consider(0, 'Invalid email format: email must contain \'@\'');
    }

    // Leading character checks (all at index 0)
    if (/^\s/.test(rawValue)) {
        consider(0, 'Email cannot contain spaces');
    }
    if (/^[0-9]/.test(rawValue)) {
        consider(0, 'Email must not start with a number');
    }
    if (/^[^a-zA-Z0-9]/.test(rawValue) && !rawValue.startsWith('@')) {
        consider(0, 'Email must not start with a special character');
    }
    if (rawValue.startsWith('@')) {
        consider(0, 'Email cannot start with "@"');
    }

    // Scan through the string for other errors
    // Double spaces at the end - MUST BE FIRST (check before other validations)
    if (rawValue.endsWith('  ')) {
        const doubleSpaceEndIdx = rawValue.lastIndexOf('  ');
        consider(doubleSpaceEndIdx, 'Double spaces are not allowed');
    }

    // Double spaces (not at end) - check after end double spaces
    if (earliestIdx === Infinity) {
        const doubleSpaceMatch = rawValue.match(/\s{2,}(?!$)/);
        if (doubleSpaceMatch) {
            consider(doubleSpaceMatch.index, 'Double spaces are not allowed');
        }
    }

    // Space anywhere (but not trailing - that's checked second-to-last)
    // Check for spaces that are NOT at the end (only if no double space error found)
    if (earliestIdx === Infinity) {
        const spaceMatch = rawValue.match(/\s(?!\s*$)/);
        if (spaceMatch) {
            consider(spaceMatch.index, 'Email cannot contain spaces');
        }
    }

    // Second @ symbol
    const secondAtPos = atIndex !== -1 ? rawValue.indexOf('@', atIndex + 1) : -1;
    consider(secondAtPos, 'Email cannot contain more than one "@" symbol');

    // Consecutive special characters (e.g., .., __) - only if no space errors found
    if (earliestIdx === Infinity) {
        const consecSpecialMatch = rawValue.match(/[^A-Za-z0-9]{2,}/);
        if (consecSpecialMatch) {
            consider(consecSpecialMatch.index, 'Email cannot contain consecutive periods (..)');
        }
    }

    // If @ exists, check local and domain parts
    if (atIndex !== -1 && earliestIdx === Infinity) {
        const emailLocalPart = rawValue.substring(0, atIndex);
        const domainPart = rawValue.substring(atIndex + 1);

        // Invalid characters in local part (only dot allowed)
        // Skip spaces - they are handled by trailing space checks
        for (let i = 0; i < emailLocalPart.length; i++) {
            const ch = emailLocalPart[i];
            if (ch === ' ') continue; // Skip spaces - handled separately
            if (!/[A-Za-z0-9.]/.test(ch)) {
                consider(i, 'Dot "." is the only allowed special character in the local part');
                break;
            }
        }

        // Invalid characters in domain part (only dot allowed)
        // Skip spaces - they are handled by trailing space checks
        const domainStart = atIndex + 1;
        for (let i = 0; i < domainPart.length; i++) {
            const ch = domainPart[i];
            if (ch === ' ') continue; // Skip spaces - handled separately
            if (!/[A-Za-z0-9.]/.test(ch)) {
                consider(domainStart + i, 'Dot "." is the only allowed special character in the domain part');
                break;
            }
        }

        // Local part validation
        if (!emailLocalPart) {
            consider(atIndex, 'Email must have text before "@"');
        } else {
            if (emailLocalPart.length < 3) {
                consider(0, 'At least 3 characters before the @');
            }
            if (/^[0-9]/.test(emailLocalPart)) {
                consider(0, 'Email must not start with a number');
            }
            if (/^[^a-zA-Z0-9]/.test(emailLocalPart)) {
                consider(0, 'Email must not start with a special character');
            }
            // Local part must not end with special character
            if (emailLocalPart.length > 0 && !/[A-Za-z0-9]$/.test(emailLocalPart)) {
                consider(emailLocalPart.length - 1, 'Local part must not end with a special character');
            }
        }

        // Domain part validation
        if (!domainPart) {
            consider(atIndex + 1, 'Email must have text after "@"');
        } else {
            if (domainPart.startsWith('.')) {
                consider(atIndex + 1, 'Domain part cannot start with a period (.)');
            }
            if (!domainPart.includes('.')) {
                consider(atIndex + 1, 'Email must contain a period (.)');
            }
            if (domainPart.endsWith('.')) {
                consider(rawValue.length - 1, 'Email cannot end with a period (.)');
            }
            // Domain labels must be non-empty
            const labels = domainPart.split('.');
            let labelOffset = atIndex + 1;
            for (let i = 0; i < labels.length; i++) {
                if (!labels[i]) {
                    consider(labelOffset, 'Email domain should not be empty');
                    break;
                }
                labelOffset += labels[i].length + 1;
            }
        }
    } else if (atIndex === -1 && earliestIdx === Infinity) {
        // No @ symbol found - this should already be handled above, but keep as fallback
        consider(0, 'Invalid email format: email must contain \'@\'');
    }

    // Ending character checks (at end of string)
    if (earliestIdx === Infinity && value.length > 0) {
        const lastChar = value[value.length - 1];
        if (/\d/.test(lastChar)) {
            consider(rawValue.length - 1, 'Email must not end with a number');
        } else if (!/[A-Za-z]/.test(lastChar)) {
            consider(rawValue.length - 1, 'Email must not end with a special character');
        }
    }

    // Trailing space check (second-to-last - only if no earlier errors found)
    // Note: Double spaces at end are already checked at the beginning
    if (allowTrailingCheck && earliestIdx === Infinity && rawValue.endsWith(' ') && !rawValue.endsWith('  ')) {
        const trailingIdx = rawValue.length - 1;
        consider(trailingIdx, 'Trailing space is not allowed');
    }

    // Show the earliest error if found
    if (earliestIdx !== Infinity) {
        showError(field, earliestMsg);
        return;
    }

    // Final validation checks (only if no earlier errors found)
    const atIdx = value.indexOf('@');
    if (atIdx !== -1) {
        const emailLocalPart = value.substring(0, atIdx);
        const domainPart = value.substring(atIdx + 1);

        // Basic pattern validation
        const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!pattern.test(value)) {
            showError(field, 'Please enter a valid email address');
            return;
        }

        // Approved-domain check. A syntactically perfect address on a domain we
        // do not accept is still refused — here for immediate feedback, and
        // again by bh_validate_email_address() on the server, which is what
        // actually decides whether the registration is stored.
        const approvedDomains = (window.BHValidation && typeof window.BHValidation.approvedEmailDomains === 'function')
            ? window.BHValidation.approvedEmailDomains()
            : (Array.isArray(window.BH_APPROVED_EMAIL_DOMAINS) ? window.BH_APPROVED_EMAIL_DOMAINS : []);
        if (approvedDomains.length && approvedDomains.indexOf(domainPart.toLowerCase()) === -1) {
            showError(field, 'Email domain is not accepted. Please use one of: ' + approvedDomains.join(', ') + '.');
            return;
        }
    }
}



    
function validateUsername(rawValue, field) {
    // Sequential rule evaluation: stop at first failing rule
    if (!rawValue) {
        showError(field, 'Username is required');
        return;
    }

    const value = rawValue.trim();

    // 1) Min length
    if (value.length < 4) {
        showError(field, 'Username must be at least 4 characters');
        return;
    }

    // 2) Max length
    if (value.length > 20) {
        showError(field, 'Username must be less than 20 characters');
        return;
    }

    // 3) First char must be lowercase letter
    if (!/^[a-z]/.test(value)) {
        showError(field, 'invalid input. Format must be "juan1234"');
        return;
    }

    // 4) Only lowercase letters and digits allowed
    if (/[^a-z0-9]/.test(value)) {
        showError(field, 'invalid input. Format must be "juan1234"');
        return;
    }

    // 5) Letters must come before digits (no letters after a digit)
    let seenDigit = false;
    for (let i = 0; i < value.length; i++) {
        const ch = value[i];
        if (/[0-9]/.test(ch)) {
            seenDigit = true;
        } else if (/[a-z]/.test(ch) && seenDigit) {
            showError(field, 'invalid input. Format must be "juan1234"');
            return;
        }
    }

    // 6) Require at least one digit (to match juan1234 pattern)
    if (!/[0-9]/.test(value)) {
        showError(field, 'invalid input. Format must be "juan1234"');
        return;
    }

    // Passed all rules
    clearError({ target: field });
}




    
    function validatePassword(value, field) {
        // Skip validation if password exists error is active for current password value
        if (passwordWithExistsError !== null && value === passwordWithExistsError) {
            return false; // Don't validate strength when password exists
        }
        
        const strengthMessage = document.querySelector('.password-strength-message');
        const strengthBar = document.querySelector('.strength-bar');
        
        if (!value) {
            // Show "Password is required" in strength message area (same location as strength labels)
            if (strengthMessage) {
                strengthMessage.textContent = 'Password is required';
                strengthMessage.style.color = '#ff4444';
            }
            // Keep strength bar neutral/empty
            if (strengthBar) {
                strengthBar.className = 'strength-bar';
                strengthBar.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
            }
            // Clear error div
            const errorDiv = document.getElementById('password-error');
            if (errorDiv) {
                errorDiv.textContent = '';
                errorDiv.style.display = 'none';
                errorDiv.classList.remove('show');
            }
            return false;
        }

        const result = getPasswordStrength(value);
        
        // For validation, we'll only reject weak passwords
        // Weak password messages are shown in strength message area via checkPasswordStrength
        if (result.level === 'weak') {
            // Don't show error in error div - strength message is shown in strength message area
            return false;
        }

        // Clear error div (strength messages are shown in strength message area)
        const errorDiv = document.getElementById('password-error');
        if (errorDiv) {
            errorDiv.textContent = '';
            errorDiv.style.display = 'none';
            errorDiv.classList.remove('show');
        }
        return true;
    }
    
    function validatePasswordMatch() {
        const password = document.getElementById('password')?.value || '';
        const repassword = document.getElementById('repassword')?.value || '';
        const repasswordField = document.getElementById('repassword');
        const errorDiv = document.getElementById('repassword-error');
        
        // Clear previous errors when field is empty
        if (!repassword) {
            if (errorDiv) {
                errorDiv.textContent = '';
                errorDiv.classList.remove('show');
                repasswordField.style.borderColor = '';
            }
            return false;
        }
        
        // Only validate if both fields have values
        if (password && repassword) {
            if (password !== repassword) {
                // Show error message - explicitly set color to red
                if (errorDiv) {
                    errorDiv.textContent = 'Passwords do not match';
                    errorDiv.style.color = '#ff4444'; // Explicitly set to red
                    errorDiv.style.display = 'block';
                    errorDiv.classList.add('show');
                    repasswordField.style.borderColor = '#f51a2cff';
                }
                return false;
            }
            
            // Passwords match - show success message
            if (errorDiv) {
                errorDiv.textContent = 'Passwords match';
                errorDiv.style.color = '#00a854';
                errorDiv.classList.add('show');
                repasswordField.style.borderColor = '#00a854';
            }
        }
        
        return true;
    }
    
function validateAddress(rawValue, field, fieldName, eventType = 'input') {
    if (!rawValue) {
        showError(field, `${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)} is required`);
        return;
    }

    const value = rawValue.trim();
    const allowTrailingCheck = shouldRunTrailingSpaceValidation(eventType);

    // Earliest-index prioritization: find the first error position in the string
    let earliestIdx = Infinity;
    let earliestMsg = '';

    // Helper function to consider an error at a specific index
    const consider = (idx, msg) => {
        if (idx !== -1 && idx < earliestIdx) {
            earliestIdx = idx;
            earliestMsg = msg;
        }
    };

    // Leading character checks (all at index 0)
    if (/^\s/.test(rawValue)) {
        if (fieldName === 'barangay') {
            consider(0, "Leading or trailing spaces are not allowed");
        } else {
            consider(0, 'Leading space is not allowed');
        }
    }
    if (/^[^A-Za-zñÑ0-9]/.test(rawValue)) {
        if (fieldName === 'barangay') {
            consider(0, "Special characters are not allowed");
        } else {
            consider(0, 'Must not start with a special character.');
        }
    }
    if (/^[0-9]/.test(rawValue) && !(fieldName === 'street' && /^\d/.test(rawValue))) {
        if (fieldName === 'barangay') {
            consider(0, "Numbers are not allowed");
        } else if (fieldName === 'country' || fieldName === 'city_municipality' || fieldName === 'province') {
            consider(0, 'Must not start with a number.');
        }
    }
    if (/^[a-z]/.test(rawValue) && !(fieldName === 'street' && /^[0-9]/.test(rawValue))) {
        consider(0, 'First letter must be uppercase');
    }

    // Country, city, province - apply same sequence logic as name fields
    if (fieldName === 'country' || fieldName === 'city_municipality' || fieldName === 'province') {
        // ----------------------------
        // Main scanning rules (same sequence as validateName)
        // ----------------------------
        if (earliestIdx === Infinity) {
            // Numbers anywhere (catch all) — **MUST BE FIRST**
            const numberIdx = rawValue.search(/[0-9]/);
            if (numberIdx !== -1) {
                consider(numberIdx, "Numbers are not allowed");
            }

            // Special characters anywhere (after numbers)
            let specialCharPattern = /[^a-zA-ZñÑ0-9\s-]/; // allow hyphen
            const specialCharIdx = rawValue.search(specialCharPattern);
            if (specialCharIdx !== -1) {
                consider(specialCharIdx, "Special characters are not allowed");
            }

            // Multiple spaces (not at end)
            const doubleSpace = rawValue.match(/\s{2,}(?!$)/);
            if (doubleSpace) consider(doubleSpace.index, "Double spaces are not allowed");

            // Triple identical letters
            const tripleLetter = rawValue.toLowerCase().match(/([a-zñ])\1{2,}/);
            if (tripleLetter) consider(tripleLetter.index, "Three identical consecutive letters are not allowed");

            // All caps word (only letters)
            const words = value.split(/\s+/).filter(Boolean);
            for (let i = 0; i < words.length; i++) {
                const w = words[i];
                const lettersOnly = w.replace(/[^A-Za-zñÑ-]/g, "");
                if (lettersOnly.length > 1 && lettersOnly === lettersOnly.toUpperCase() && !lettersOnly.includes('-')) {
                    const pos = rawValue.indexOf(w);
                    if (pos !== -1) {
                        consider(pos, "All capital letters are not allowed");
                        break;
                    }
                }
            }

            // Consecutive capital letters
            const consecCaps = rawValue.match(/[A-ZÑ]{2,}/);
            if (consecCaps) consider(consecCaps.index, "Consecutive capital letters are not allowed");

            // First letter of each word uppercase
            for (let i = 0; i < words.length; i++) {
                const w = words[i];
                if (w[0] !== w[0].toUpperCase()) {
                    const pos = rawValue.indexOf(w);
                    consider(pos, `First letter of the ${getOrdinalWord(i + 1)} word must be uppercase.`);
                    break;
                }
            }

            // Incorrect capitalization inside a word
            const incorrect = findIncorrectCapitalization(rawValue);
            if (incorrect) {
                const allWords = rawValue.split(/\s+/);
                let charPos = 0;
                for (let i = 0; i < incorrect.wordNumber - 1; i++) charPos += allWords[i].length + 1;
                charPos += incorrect.position - 1;
                if (incorrect.wordNumber === 1) {
                    consider(charPos, `The (${incorrect.letter}) must be lowercase.`);
                } else {
                    consider(charPos, `The (${incorrect.letter}) in the ${getOrdinalWord(incorrect.wordNumber)} word must be lowercase.`);
                }
            }
        }
    }

    // Street and barangay validations
    if (fieldName === 'street' || fieldName === 'barangay') {
        const streetTypeErrorMsg = 'Must be followed by a street type (e.g., Street, Road, Avenue)';
        
        // Street - apply same sequence logic as name fields
        if (fieldName === 'street' && earliestIdx === Infinity) {
            // ----------------------------
            // Main scanning rules (same sequence as validateName)
            // ----------------------------
            // Numbers anywhere (catch all) — **MUST BE FIRST**
            // Note: Street allows numbers at start (like "1st Street") and patterns like "P-1A", so we check for invalid positions
            const numberMatch = rawValue.match(/[0-9]/);
            if (numberMatch) {
                const numberIdx = numberMatch.index;
                const prevChar = numberIdx > 0 ? rawValue[numberIdx - 1] : '';
                const nextChar = numberIdx < rawValue.length - 1 ? rawValue[numberIdx + 1] : '';
                
                // Check if number is in an allowed pattern:
                // - At start (will be validated later for street type requirement) - skip here
                // - After single letter (P1 pattern) - allowed ONLY if number is at end or followed by space/hyphen
                // - After hyphen that follows single letter (P-1 pattern) - allowed, skip check
                // - After space (like "1st Street") - allowed, skip check (will be validated later)
                
                const isAfterSpace = prevChar === ' ';
                const isAfterHyphen = prevChar === '-';
                const isAfterSingleLetterAtStart = numberIdx === 1 && /[A-Za-zñÑ]/.test(rawValue[0]);
                const isAfterHyphenWithSingleLetter = isAfterHyphen && numberIdx === 2 && /[A-Za-zñÑ]/.test(rawValue[0]);
                
                // For P1 pattern, check if number is followed by more letters (not allowed)
                const isP1PatternWithMoreLetters = isAfterSingleLetterAtStart && /[A-Za-zñÑ]/.test(nextChar);
                
                // Numbers are NOT allowed if:
                // - Inside a word (letter before, not space/hyphen, and not in allowed single-letter patterns)
                // - P1 pattern but followed by more letters (like "P1u")
                if (numberIdx > 0 && /[A-Za-zñÑ]/.test(prevChar) && !isAfterSpace && !isAfterHyphen && (!isAfterSingleLetterAtStart || isP1PatternWithMoreLetters) && !isAfterHyphenWithSingleLetter) {
                    // Number inside a word (after letter, not in allowed patterns, or P1 followed by letters)
                    consider(numberIdx, "Numbers are not allowed");
                } else if (numberIdx > 0 && !/[A-Za-zñÑ\s-]/.test(prevChar)) {
                    // Number after special character (not letter/space/hyphen) - not allowed
                    consider(numberIdx, "Numbers are not allowed");
                }
            }

            // Special characters anywhere (after numbers)
            let specialCharPattern = /[^a-zA-ZñÑ0-9\s-]/; // allow hyphen
            const specialCharIdx = rawValue.search(specialCharPattern);
            if (specialCharIdx !== -1) {
                consider(specialCharIdx, "Special characters are not allowed");
            }

            // Multiple spaces (not at end)
            const doubleSpace = rawValue.match(/\s{2,}(?!$)/);
            if (doubleSpace) consider(doubleSpace.index, "Double spaces are not allowed");

            // Triple identical letters
            const tripleLetter = rawValue.toLowerCase().match(/([a-zñ])\1{2,}/);
            if (tripleLetter) consider(tripleLetter.index, "Three identical consecutive letters are not allowed");

            // All caps word (only letters)
            const words = value.split(/\s+/).filter(Boolean);
            for (let i = 0; i < words.length; i++) {
                const w = words[i];
                const lettersOnly = w.replace(/[^A-Za-zñÑ-]/g, "");
                if (lettersOnly.length > 1 && lettersOnly === lettersOnly.toUpperCase() && !lettersOnly.includes('-')) {
                    const pos = rawValue.indexOf(w);
                    if (pos !== -1) {
                        consider(pos, "All capital letters are not allowed");
                        break;
                    }
                }
            }

            // Consecutive capital letters
            const consecCaps = rawValue.match(/[A-ZÑ]{2,}/);
            if (consecCaps) consider(consecCaps.index, "Consecutive capital letters are not allowed");

            // First letter of each word uppercase
            for (let i = 0; i < words.length; i++) {
                const w = words[i];
                // Skip if word starts with number (like "1st")
                if (/^[0-9]/.test(w)) continue;
                if (w[0] !== w[0].toUpperCase()) {
                    const pos = rawValue.indexOf(w);
                    consider(pos, `First letter of the ${getOrdinalWord(i + 1)} word must be uppercase.`);
                    break;
                }
            }

            // Incorrect capitalization inside a word
            const incorrect = findIncorrectCapitalization(rawValue);
            if (incorrect) {
                const allWords = rawValue.split(/\s+/);
                let charPos = 0;
                for (let i = 0; i < incorrect.wordNumber - 1; i++) charPos += allWords[i].length + 1;
                charPos += incorrect.position - 1;
                if (incorrect.wordNumber === 1) {
                    consider(charPos, `The (${incorrect.letter}) must be lowercase.`);
                } else {
                    consider(charPos, `The (${incorrect.letter}) in the ${getOrdinalWord(incorrect.wordNumber)} word must be lowercase.`);
                }
            }
        }

        // Hyphen at start
        const hyphenStartIdx = rawValue.search(/^-/);
        consider(hyphenStartIdx, 'Cannot start with a hyphen');

        // Consecutive hyphens
        const doubleHyphenIdx = rawValue.indexOf('--');
        consider(doubleHyphenIdx, 'Cannot have consecutive hyphens');

        // Hyphen at end
        const hyphenEndIdx = rawValue.search(/-$/);
        if (hyphenEndIdx !== -1) {
            consider(hyphenEndIdx, 'Cannot end with a hyphen');
        }

        // Barangay - apply same sequence logic as city/municipality
        if (fieldName === 'barangay' && earliestIdx === Infinity) {
            // Allow patterns like "Poblacion 12" but disallow leading numbers or trailing numbers without space
            const trimmed = value; // already trimmed above
            // ❌ Case 1: Starts with number(s) then letters (e.g., "12 Poblacion")
            if (/^[0-9]+\s*[A-Za-zñÑ]+/.test(trimmed)) {
                showError(field, "Numbers followed by letters are not allowed");
                return;
            }
            // ❌ Case 2: Letters then trailing numbers without space (e.g., "Poblacion12")
            if (/^[A-Za-zñÑ]+(?:\s+[A-Za-zñÑ]+)*[0-9]+$/.test(trimmed) && !/\s[0-9]+$/.test(trimmed)) {
                showError(field, "Trailing numbers are not allowed");
                return;
            }
            // ✅ Case 3: Allowed – letters then space then digits at end (e.g., "Poblacion 12", "Zone 3", "Sitio 5")
            if (/^[A-Za-zñÑ]+(?:\s+[A-Za-zñÑ]+)*\s+[0-9]+$/.test(trimmed)) {
                clearError({ target: field });
                return; // accept and skip further numeric scans
            }
            // ----------------------------
            // Main scanning rules (same sequence as validateName and city/municipality)
            // ----------------------------
            // Numbers anywhere (catch all) — **MUST BE FIRST**
            const numberIdx = rawValue.search(/[0-9]/);
            if (numberIdx !== -1) {
                consider(numberIdx, "Numbers are not allowed");
            }

            // Special characters anywhere (after numbers)
            let specialCharPattern = /[^a-zA-ZñÑ0-9\s-]/; // allow hyphen
            const specialCharIdx = rawValue.search(specialCharPattern);
            if (specialCharIdx !== -1) {
                consider(specialCharIdx, "Special characters are not allowed");
            }

            // Multiple spaces (not at end)
            const doubleSpace = rawValue.match(/\s{2,}(?!$)/);
            if (doubleSpace) consider(doubleSpace.index, "Double spaces are not allowed");

            // Triple identical letters
            const tripleLetter = rawValue.toLowerCase().match(/([a-zñ])\1{2,}/);
            if (tripleLetter) consider(tripleLetter.index, "Three identical consecutive letters are not allowed");

            // All caps word (only letters)
            const words = value.split(/\s+/).filter(Boolean);
            for (let i = 0; i < words.length; i++) {
                const w = words[i];
                const lettersOnly = w.replace(/[^A-Za-zñÑ-]/g, "");
                if (lettersOnly.length > 1 && lettersOnly === lettersOnly.toUpperCase() && !lettersOnly.includes('-')) {
                    const pos = rawValue.indexOf(w);
                    if (pos !== -1) {
                        consider(pos, "All capital letters are not allowed");
                        break;
                    }
                }
            }

            // Consecutive capital letters
            const consecCaps = rawValue.match(/[A-ZÑ]{2,}/);
            if (consecCaps) consider(consecCaps.index, "Consecutive capital letters are not allowed");

            // First letter of each word uppercase
            for (let i = 0; i < words.length; i++) {
                const w = words[i];
                if (w[0] !== w[0].toUpperCase()) {
                    const pos = rawValue.indexOf(w);
                    consider(pos, `First letter of the ${getOrdinalWord(i + 1)} word must be uppercase.`);
                    break;
                }
            }

            // Incorrect capitalization inside a word
            const incorrect = findIncorrectCapitalization(rawValue);
            if (incorrect) {
                const allWords = rawValue.split(/\s+/);
                let charPos = 0;
                for (let i = 0; i < incorrect.wordNumber - 1; i++) charPos += allWords[i].length + 1;
                charPos += incorrect.position - 1;
                if (incorrect.wordNumber === 1) {
                    consider(charPos, `The (${incorrect.letter}) must be lowercase.`);
                } else {
                    consider(charPos, `The (${incorrect.letter}) in the ${getOrdinalWord(incorrect.wordNumber)} word must be lowercase.`);
                }
            }
        }

        // Street number validation (only if no earlier errors)
        if (earliestIdx === Infinity && /^[0-9]/.test(value)) {
            if (fieldName === 'street') {
                const numericOnly = /^\d+$/.test(value);
                const numberPrefixMatch = value.match(/^\d+(?:st|nd|rd|th)?/i);

                const ensureStreetType = (afterRaw, prefixLength) => {
                    const trimmed = afterRaw.trimStart();
                    const leadingSpacesCount = afterRaw.length - trimmed.length;
                    const firstContentIdx = prefixLength + leadingSpacesCount;
                    if (trimmed.length === 0) {
                        const fallbackIdx = Math.min(firstContentIdx, Math.max(0, value.length - 1));
                        consider(fallbackIdx, streetTypeErrorMsg);
                        return false;
                    }
                    const firstStreetTypeChar = trimmed[0];
                    if (/[a-zñ]/.test(firstStreetTypeChar)) {
                        consider(firstContentIdx, `Street type '${firstStreetTypeChar}' must be uppercase.`);
                        return false;
                    }
                    if (!/[A-ZÑ]/.test(firstStreetTypeChar)) {
                        consider(firstContentIdx, streetTypeErrorMsg);
                        return false;
                    }
                    return true;
                };

                const markStreetTypeError = () => {
                    if (!numberPrefixMatch) {
                        consider(0, streetTypeErrorMsg);
                        return;
                    }
                    const afterNumberRaw = value.slice(numberPrefixMatch[0].length);
                    ensureStreetType(afterNumberRaw, numberPrefixMatch[0].length);
                };

                if (earliestIdx === Infinity) {
                    // Check if it's using ordinal format (1st, 2nd, 3rd, etc.)
                    const ordinalMatch = value.match(/^(\d+)(st|nd|rd|th)\b/i);
                    if (ordinalMatch) {
                        const num = parseInt(ordinalMatch[1]);
                        const ordinalSuffixRaw = ordinalMatch[2];
                        const suffix = ordinalSuffixRaw.toLowerCase();
                        const suffixStartIdx = ordinalMatch.index + ordinalMatch[1].length;
                        const afterOrdinalRaw = value.slice(ordinalMatch[0].length);
                        if (ordinalSuffixRaw !== ordinalSuffixRaw.toLowerCase() && suffix === 'th') {
                            consider(suffixStartIdx, 'Ordinal must be in small letters.');
                        } else {
                            let correctSuffix = 'th';
                            const lastDigit = num % 10;
                            const lastTwoDigits = num % 100;
                            if (lastDigit === 1 && lastTwoDigits !== 11) correctSuffix = 'st';
                            else if (lastDigit === 2 && lastTwoDigits !== 12) correctSuffix = 'nd';
                            else if (lastDigit === 3 && lastTwoDigits !== 13) correctSuffix = 'rd';
                            if (suffix !== correctSuffix) {
                                consider(suffixStartIdx, `Incorrect ordinal (e.g., ${num}${correctSuffix}).`);
                            } else {
                                ensureStreetType(afterOrdinalRaw, ordinalMatch[0].length);
                            }
                        }
                    }
                }

                if (earliestIdx === Infinity) {
                    if (numericOnly) {
                        markStreetTypeError();
                    } else if (numberPrefixMatch) {
                        const afterNumberRaw = value.slice(numberPrefixMatch[0].length);
                        if (!ensureStreetType(afterNumberRaw, numberPrefixMatch[0].length)) {
                            // ensureStreetType already reported the issue
                        }
                    }
                }
            } else {
                consider(0, `${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)} must not start with a number`);
            }
        }

        // Street field: must not contain spaces around dashes
        if (fieldName === 'street' && earliestIdx === Infinity) {
            const spaceDashMatch = rawValue.match(/\s-/);
            if (spaceDashMatch) {
                consider(spaceDashMatch.index, 'Purok must not contain spaces around the dash.');
            }
            const dashSpaceMatch = rawValue.match(/-\s/);
            if (dashSpaceMatch) {
                consider(dashSpaceMatch.index, 'Purok must not contain spaces around the dash.');
            }
        }

        // Hyphen rules: different rules for street vs barangay
        if (earliestIdx === Infinity) {
            const hyphenParts = value.split('-');
            if (hyphenParts.length > 1) {
                let offset = 0;
                for (let i = 1; i < hyphenParts.length; i++) {
                    const part = hyphenParts[i].trim();
                    offset += hyphenParts[i-1].length + 1; // +1 for hyphen
                    if (part.length === 0) continue;
                    const firstChar = part[0];
                    const hyphenIndex = rawValue.indexOf('-', offset - hyphenParts[i-1].length - 1);
                    const leadingSpaces = hyphenParts[i].length - part.length;
                    const charPos = hyphenIndex + 1 + leadingSpaces;
                    
                    if (fieldName === 'street') {
                        // Allow pattern like "P-1A" (single letter + hyphen + number + letter)
                        // Check if previous part is a single letter
                        const prevPart = hyphenParts[i-1].trim();
                        const isSingleLetterPattern = prevPart.length === 1 && /[A-Za-z]/.test(prevPart);
                        
                        if (isSingleLetterPattern && /[0-9]/.test(firstChar)) {
                            // Allow number after hyphen if previous part is single letter (like "P-1")
                            // Also allow letter after number in this pattern (like "P-1A")
                            continue;
                        }
                        
                        // Otherwise, block letters directly after hyphen
                        if (/[a-zA-Z]/.test(firstChar)) {
                            consider(charPos, 'Letters are not allowed after the hyphen');
                            break;
                        }
                    } else if (fieldName === 'barangay') {
                        if (/[0-9]/.test(firstChar)) {
                            consider(charPos, 'Numbers are not allowed after the hyphen');
                            break;
                        }
                        if (/[A-Z]/.test(firstChar)) {
                            consider(charPos, 'Letter must be lowercase after hyphen');
                            break;
                        }
                    }
                }
            }
        }

        if ((fieldName === 'barangay' || fieldName === 'street') && earliestIdx === Infinity) {
            for (let i = 0; i < value.length; i++) {
                const ch = value[i];
                if (!/\d/.test(ch)) continue;
                const prevChar = i > 0 ? value[i - 1] : '';
                
                // Check if number is part of "P-1A" pattern (single letter + hyphen + number + letter)
                if (prevChar === '-') {
                    // Check if previous part before hyphen is a single letter
                    let hyphenPos = i - 1;
                    let letterCountBeforeHyphen = 0;
                    for (let j = hyphenPos - 1; j >= 0; j--) {
                        if (/[A-Za-zñÑ]/.test(value[j])) {
                            letterCountBeforeHyphen++;
                        } else if (value[j] === ' ' || value[j] === '-') {
                            break;
                        } else {
                            break;
                        }
                    }
                    // If single letter before hyphen (like "P-1"), allow number and letter after it (like "P-1A")
                    if (letterCountBeforeHyphen === 1) {
                        continue; // Allow "P-1A" pattern
                    }
                    // If not "P-1A" pattern, continue to check other rules
                }
                
                if (/[A-Za-zñÑ]/.test(prevChar)) {
                    // Check if it's a single letter followed by number (like "P1") - this is allowed
                    // But if there are multiple letters before the number, it's not allowed
                    let letterCountBefore = 0;
                    for (let j = i - 1; j >= 0; j--) {
                        if (/[A-Za-zñÑ]/.test(value[j])) {
                            letterCountBefore++;
                        } else if (value[j] === ' ' || value[j] === '-') {
                            break; // Stop at space or hyphen
                        } else {
                            break;
                        }
                    }
                    
                    // If only one letter before the number (like "P1"), allow it
                    if (letterCountBefore === 1) {
                        continue; // Allow single letter + number pattern
                    }
                    
                    // Otherwise, check the next character
                    const nextChar = i < value.length - 1 ? value[i + 1] : '';
                    if (/[A-Za-zñÑ]/.test(nextChar)) {
                        // Check if this is part of "P-1A" pattern (single letter + hyphen + number + letter)
                        let isP1APattern = false;
                        if (i > 1 && value[i - 1] === '-') {
                            // Check if previous part before hyphen is a single letter
                            let letterCountBeforeHyphen = 0;
                            for (let j = i - 2; j >= 0; j--) {
                                if (/[A-Za-zñÑ]/.test(value[j])) {
                                    letterCountBeforeHyphen++;
                                } else if (value[j] === ' ' || value[j] === '-') {
                                    break;
                                } else {
                                    break;
                                }
                            }
                            // If single letter before hyphen (like "P-1"), allow letter after number (like "P-1A")
                            if (letterCountBeforeHyphen === 1) {
                                isP1APattern = true;
                            }
                        }
                        if (!isP1APattern) {
                            consider(i, 'Numbers are not allowed inside the word.');
                        }
                    } else {
                        consider(i, 'A space is required before the number.');
                    }
                    break;
                }
            }
        }

        // Check each word for all caps (except patterns like P-A3, Purok-A3, P-1A)
        if (earliestIdx === Infinity) {
            const words = value.split(' ');
            let wordOffset = 0;
            for (let part of words) {
                // Skip patterns like -A3 (dash, capital letter, number)
                if (/-[A-Z][0-9]/.test(part)) {
                    wordOffset += part.length + 1;
                    continue;
                }
                // Skip patterns like P-1A (letter, dash, number, optional capital letter)
                if (/^[A-Z]-\d+[A-Z]?$/.test(part)) {
                    wordOffset += part.length + 1;
                    continue;
                }
                let lettersOnly = part.replace(/[^a-zA-ZñÑ]/g, '');
                if (lettersOnly.length > 1 && lettersOnly === lettersOnly.toUpperCase()) {
                    const wordStart = rawValue.indexOf(part, wordOffset);
                    if (wordStart !== -1) {
                        consider(wordStart, 'All capital letters are not allowed');
                        break;
                    }
                }
                wordOffset += part.length + 1;
            }
        }
    }

    // Common validations (only if no earlier errors)
    // Exclude city/province/country from duplicate checks (already handled in specific section above)
    const isCityProvinceCountry = fieldName === 'country' || fieldName === 'city_municipality' || fieldName === 'province';
    if (earliestIdx === Infinity) {
        // Double spaces (but not at the end - that's checked last)
        // Skip for city/province/country (already checked in specific section)
        if (!isCityProvinceCountry) {
            const doubleSpaceMatch = rawValue.match(/\s{2,}(?!$)/);
            if (doubleSpaceMatch) {
                consider(doubleSpaceMatch.index, 'Double spaces are not allowed');
            }
        }

        // Check for small letters after numbers (with exceptions)
        if ((fieldName === 'street' || fieldName === 'barangay') && 
            /(?<=\d)[a-z]/.test(value) && 
            !/^P-\d+[A-Z]?(\b|$)/.test(value) && 
            !/^Purok\s+\d+[A-Z]?(\b|$)/i.test(value) &&
            !/^P\d+(\b|$)/i.test(value)) {
            const smallAfterNumMatch = value.match(/(\d)([a-z])/);
            if (smallAfterNumMatch) {
                const numPos = value.indexOf(smallAfterNumMatch[0]);
                const substringFromDigit = value.slice(numPos);
                const isOrdinalSuffix = /^\d+(st|nd|rd|th)\b/i.test(substringFromDigit);
                if (!isOrdinalSuffix) {
                    consider(numPos + 1, 'Small letters cannot come after numbers.');
                }
            }
        }

        // Last character must not be special
        const lastChar = value.charAt(value.length - 1);
        if (/[^a-zA-ZñÑ0-9\s]/.test(lastChar)) {
            consider(rawValue.length - 1, 'Last character cannot be a special character');
        }

        // Three consecutive identical letters
        // Skip for city/province/country (already checked in specific section)
        if (!isCityProvinceCountry) {
            const tripleLetterMatch = rawValue.toLowerCase().match(/([a-zñ])\1{2,}/);
            if (tripleLetterMatch) {
                consider(tripleLetterMatch.index, 'Three consecutive same letters not allowed');
            }
        }
    }

    // Length checks (only if no earlier errors found)
    if (earliestIdx === Infinity) {
        const letterCount = value.replace(/\s/g, '').length;
        if (letterCount < 2) {
            if (fieldName === 'barangay') {
                showError(field, "Minimum of 2 letters is required");
            } else {
                showError(field, 'Minimum of 2 characters is required');
            }
            return;
        }
        if (letterCount > 30) {
            if (fieldName === 'barangay') {
                showError(field, "Maximum of 30 letters is allowed");
            } else {
                showError(field, 'Must not exceed 30 characters');
            }
            return;
        }
    }

    // Trailing space check (second-to-last - only if no earlier errors found)
    if (allowTrailingCheck && earliestIdx === Infinity && rawValue.endsWith(' ') && !rawValue.endsWith('  ')) {
        const trailingIdx = rawValue.length - 1;
        consider(trailingIdx, 'Trailing space is not allowed');
    }

    // Double spaces at the end (very last check - only if no earlier errors found)
    if (earliestIdx === Infinity && rawValue.endsWith('  ')) {
        const doubleSpaceEndIdx = rawValue.lastIndexOf('  ');
        consider(doubleSpaceEndIdx, 'Double spaces not allowed');
    }

    // Show the earliest error if found
    if (earliestIdx !== Infinity) {
        showError(field, earliestMsg);
        return;
    }

    // Capitalization checks (only if no earlier errors found)
    // Skip for city/province/country, barangay, and street (already handled in specific section with proper sequence)
    if (!isCityProvinceCountry && fieldName !== 'barangay' && fieldName !== 'street') {
        // First letter must be uppercase
        if (!(fieldName === 'street' && /^[0-9]/.test(value))) {
            if (!/^[A-ZÑ]/.test(value)) {
                showError(field, 'First letter must be uppercase');
                return;
            }
        }

        // Every word must start with uppercase
        const wordsForCap = value.split(' ');
        for (let i = 1; i < wordsForCap.length; i++) {
            const word = wordsForCap[i];
            if (word.length > 0 && word[0] !== word[0].toUpperCase()) {
                if (fieldName === 'street' && i === 1 && /^[0-9]+$/.test(wordsForCap[0])) {
                    showError(field, 'The first letter of the second word must be capital letter');
                    return;
                }
                const wordPositionText = getOrdinalWord(i + 1);
                showError(field, `First letter of the ${wordPositionText} word must be uppercase.`);
                return;
            }
        }

        // Check for incorrectly capitalized letters after first letter
        const allWords = value.split(' ');
        for (let wordIndex = 0; wordIndex < allWords.length; wordIndex++) {
            const word = allWords[wordIndex];
            if (word.length > 1) {
                const parts = word.split('-');
            for (let part of parts) {
                if (part.length > 1) {
                    const lettersOnly = part.replace(/[^a-zA-ZñÑ]/g, '');
                    if (lettersOnly.length > 1) {
                        const restOfLetters = lettersOnly.slice(1);
                        for (let i = 0; i < restOfLetters.length; i++) {
                            const char = restOfLetters[i];
                            if (char === char.toUpperCase() && char !== char.toLowerCase()) {
                                const wordNumber = wordIndex + 1;
                                // Find position of this character in original string
                                let charPos = 0;
                                for (let w = 0; w < wordIndex; w++) {
                                    charPos += allWords[w].length + 1;
                                }
                                charPos += word.indexOf(part);
                                const letterIdxInPart = part.indexOf(lettersOnly[0]) + 1 + i;
                                charPos += letterIdxInPart;
                                
                                if (wordNumber === 1) {
                                    showError(field, `The (${char}) must be lowercase.`);
                                } else {
                                    const wordOrdinal = getOrdinalWord(wordNumber);
                                    showError(field, `The (${char}) in the ${wordOrdinal} word must be lowercase.`);
                                }
                                return;
                            }
                        }
                    }
                }
            }
        }
    }
    }
}

function getOrdinalWord(n) {
    const ordinals = ["first", "second", "third", "fourth", "fifth", "sixth", "seventh", "eighth", "ninth", "tenth"];
    return ordinals[n - 1] || `${n}th`;
}



    
    
    function validateZipCode(value, field) {
        if (!value) {
            showError(field, 'Zip code is required');
            return;
        }
        
        // Note: Input is restricted to digits only at the field level.
        
        // Check if not 4 digits
        if (value.length < 4) {
            showError(field, 'Zip code must be 4 digits');
            return;
        }
        
        if (value.length > 4) {
            showError(field, 'Zip code must not exceed 4 digits');
            return;
        }
        
        // Check if all characters are digits
        if (!/^\d+$/.test(value)) {
            showError(field, 'Zip code must be digits only');
            return;
        }
    }
    
    // Helper: compute password strength level and feedback
    function getPasswordStrength(password) {
        if (!password) {
            return {
                level: 'empty',
                message: '',
                missing: ['letter', 'number', 'length']
            };
        }

        const hasLetter = /[A-Za-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[^A-Za-z0-9]/.test(password);
        const hasMinLength = password.length >= 8;

        // Determine strength level
        let level = 'weak';
        let message = '';
        let missing = [];

        // Check weak conditions - prioritize letter message
        if (!hasLetter) {
            level = 'weak';
            message = 'Weak: Add a letter';
            return { level, message, missing: [] };
        }
        
        if (!hasNumber) {
            level = 'weak';
            message = 'Weak: Add a number';
            return { level, message, missing: [] };
        }

        if (!hasMinLength) {
            level = 'weak';
            message = 'Weak: Password must be at least 8 characters';
            return { level, message, missing: [] };
        }

        // Medium: has letter + number + min length but NO special characters
        if (!hasSpecial) {
            level = 'medium';
            message = 'Medium: Add special characters to strengthen';
            return { level, message, missing: [] };
        }

        // Strong: has letter + number + special characters + min length
        return {
            level: 'strong',
            message: 'Strong password!',
            missing: []
        };
    }

    function checkPasswordStrength() {
        const password = document.getElementById('password').value;
        const strengthBar = document.querySelector('.strength-bar');
        const strengthMessage = document.querySelector('.password-strength-message');
        const passwordError = document.getElementById('password-error');
        
        // Skip strength checking if password is empty (handled separately)
        if (!password) {
            return { level: 'empty', message: '' };
        }
        
        // Check if max length is reached - if so, don't override the max length error
        const maxInfo = getMaxLength('password', password);
        const isMaxLengthReached = maxInfo && maxInfo.current >= maxInfo.max;
        if (isMaxLengthReached && strengthMessage && strengthMessage.textContent.includes('reached the maximum')) {
            return { level: 'empty', message: '' };
        }
        
        // Don't update strength bar if password exists error is active for current password value
        if (passwordWithExistsError !== null && password === passwordWithExistsError) {
            // Keep neutral state - don't update strength bar
            // Ensure strength bar remains neutral
            if (strengthBar) {
                strengthBar.className = 'strength-bar';
                strengthBar.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
            }
            // Don't override "Password already exists" message
            if (strengthMessage && strengthMessage.textContent !== 'Password already exists') {
                strengthMessage.textContent = '';
            }
            return { level: 'empty', message: '' };
        }
        
        // If password exists, clear "Password is required" message and evaluate strength
        if (strengthMessage && strengthMessage.textContent === 'Password is required' && password) {
            strengthMessage.textContent = '';
        }
        
        const result = getPasswordStrength(password);
        
        // Update strength bar
        if (strengthBar) {
            strengthBar.className = 'strength-bar';
            if (result.level === 'weak') {
                strengthBar.classList.add('strength-weak');
            } else if (result.level === 'medium') {
                strengthBar.classList.add('strength-medium');
            } else if (result.level === 'strong') {
                strengthBar.classList.add('strength-strong');
            }
        }
        
        // Only show the message in the strength message area, not in the error div
        // Don't override error messages (max length, password exists, required)
        if (strengthMessage && 
            strengthMessage.textContent !== 'Password already exists' && 
            strengthMessage.textContent !== 'Password is required' &&
            !strengthMessage.textContent.includes('reached the maximum')) {
            strengthMessage.textContent = result.message;
            strengthMessage.style.color = result.level === 'weak' ? '#ff4444' : 
                                         result.level === 'medium' ? '#ffbb33' : '#00C851';
        }
        
        // Clear error div (strength messages are shown in strength message area)
        if (passwordError) {
            passwordError.textContent = '';
            passwordError.style.display = 'none';
            passwordError.classList.remove('show');
        }
        
        // Return the result for validation
        return result;
    }
    
    function generateIdNumber() {
        const field = document.getElementById('idnumber');
        
        // Only generate if field is empty
        if (field.value.trim() === '') {
            field.disabled = true;
            
            fetch('../php/get_next_id_number.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.id_number) {
                    field.value = data.id_number;
                    // Set readOnly and make it permanent
                    field.readOnly = true;
                    field.setAttribute('readonly', 'readonly');
                    field.style.cursor = 'not-allowed';
                    // Keep the same styling as other input fields - don't change background or text color
                    clearError({ target: field });
                } else {
					if (!suppressIdErrorDisplay) {
						showError(field, data.message || 'Failed to generate ID number');
					}
                }
            })
            .catch(error => {
                console.error('Error generating ID:', error);
				if (!suppressIdErrorDisplay) {
					showError(field, 'Error generating ID number. Please try again.');
				}
            })
            .finally(() => {
                field.disabled = false;
                // Ensure readOnly stays true if ID was generated
                if (field.value && field.value.trim() !== '') {
                    field.readOnly = true;
                    field.setAttribute('readonly', 'readonly');
                    field.style.cursor = 'not-allowed';
                }
				// Reset suppression after click-initiated flow completes
				suppressIdErrorDisplay = false;
				// Restore size lock if it was applied during click
				if (idSizeLocked) {
					field.style.width = prevIdWidth;
					field.style.height = prevIdHeight;
					idSizeLocked = false;
				}
            });
        } else {
            // If field already has a value, ensure it stays readOnly
            if (field.value.trim() !== '') {
                field.readOnly = true;
                field.setAttribute('readonly', 'readonly');
                field.style.cursor = 'not-allowed';
            }
        }
    }
    
    function checkIdAvailability() {
        const idnumber = document.getElementById('idnumber').value.trim();
        const field = document.getElementById('idnumber');
        
        if (idnumber.length >= 9) { // Check if ID format is complete (2025-0001)
            fetch('../html/check_userID.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_number=${encodeURIComponent(idnumber)}`
            })
        }
    }
    
    function checkUsernameAvailability() {
        const username = document.getElementById('username').value;
        const field = document.getElementById('username');
        
        // If length is insufficient, don't clear errors; keep them visible until fixed
        if (username.length < 4) {
            return;
        }

        if (username.length >= 4) {
            fetch('../php/check_username.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `username=${encodeURIComponent(username)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.exists) {
                    showError(field, 'Username already exists');
                } else if (data.success && !data.data.exists) {
                    // Only clear the duplicate-username error; preserve any other validation messages
                    const errorDiv = document.getElementById(field.name + '-error');
                    if (errorDiv && errorDiv.textContent === 'Username already exists') {
                        clearError({ target: field });
                    }
                }
            })
            .catch(error => {
                console.error('Error checking username:', error);
            });
        }
    }
    
    function checkEmailAvailability() {
        const email = document.getElementById('email').value;
        const field = document.getElementById('email');
        
        if (email.length >= 5 && email.includes('@')) {
            fetch('../php/check_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `email=${encodeURIComponent(email)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.exists) {
                    showError(field, 'Email already exists');
                }
            })
            .catch(error => {
                console.error('Error checking email:', error);
            });
        }
    }
    
    function checkPasswordExists(password) {
        const passwordField = document.getElementById('password');
        const strengthMessage = document.querySelector('.password-strength-message');
        const strengthBar = document.querySelector('.strength-bar');
        const errorDiv = document.getElementById('password-error');
        
        if (!password || password.length < 8) {
            return;
        }
        
        fetch('../php/check_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `password=${encodeURIComponent(password)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.exists) {
                // Store the password value that triggered the error
                passwordWithExistsError = password;
                
                // Show password exists error in strength message area (same location as strength labels)
                if (strengthMessage) {
                    strengthMessage.textContent = 'Password already exists';
                    strengthMessage.style.color = '#ff4444';
                }
                
                // Set strength bar to neutral/empty state
                if (strengthBar) {
                    strengthBar.className = 'strength-bar';
                    // Remove all strength classes (weak, medium, strong)
                    strengthBar.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
                }
                
                // Clear error div
                if (errorDiv) {
                    errorDiv.textContent = '';
                    errorDiv.style.display = 'none';
                    errorDiv.classList.remove('show');
                }
            } else if (data.success && !data.data.exists) {
                // Password doesn't exist - clear the error state and restore strength bar
                passwordWithExistsError = null;
                // Clear the message from strength message area
                if (strengthMessage && strengthMessage.textContent === 'Password already exists') {
                    strengthMessage.textContent = '';
                }
                // Clear error div
                if (errorDiv) {
                    errorDiv.textContent = '';
                    errorDiv.style.display = 'none';
                    errorDiv.classList.remove('show');
                }
                // Restore password strength display
                checkPasswordStrength();
            }
        })
        .catch(error => {
            console.error('Error checking password:', error);
        });
    }
    
    function showError(field, message) {
        const errorDiv = document.getElementById(field.name + '-error');
        if (errorDiv) {
            const wasShown = errorDiv.classList.contains('show');
            errorDiv.textContent = message;
            // Do not set inline fontSize - let CSS handle it to prevent layout shifts
            errorDiv.classList.add('show');
            if (!wasShown) {
                updateFormWidth();
            }
        }
        field.style.borderColor = '#f51a2cff';
    }
    
    function clearError(e) {
        const field = e.target;
        const errorDiv = document.getElementById(field.name + '-error');
        if (errorDiv) {
            const wasShown = errorDiv.classList.contains('show');
            errorDiv.textContent = '';
            errorDiv.classList.remove('show');
            if (wasShown) {
                updateFormWidth();
            }
        }
        // Use the same golden-brown color as the original border
        field.style.borderColor = 'rgba(196, 122, 44, 0.3)';
        // Ensure ID number field stays readOnly if it has a value
        if (field.id === 'idnumber' && field.value && field.value.trim() !== '') {
            field.readOnly = true;
            field.setAttribute('readonly', 'readonly');
            field.style.cursor = 'not-allowed';
        }
    }
    
    function updateFormWidth() {
        const heroInner = document.querySelector('.hero-inner');
        const visibleErrors = document.querySelectorAll('.error-message.show');
        const currentCount = visibleErrors.length;
        const lastCount = Number(heroInner.dataset.errCount || -1);

        // Only toggle classes when the count of visible errors changes
        if (currentCount === lastCount) {
            return;
        }

        heroInner.dataset.errCount = String(currentCount);
        heroInner.classList.remove('has-errors', 'has-many-errors');
        if (currentCount >= 4) {
            heroInner.classList.add('has-many-errors');
        } else if (currentCount >= 1) {
            heroInner.classList.add('has-errors');
        }
    }
    
    function validateBasicFormWithoutSecurity() {
        let isValid = true;

        

        // 1) Required fields: every element with required attribute must be non-empty (excluding security questions)
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            // Skip security question fields
            if (field.name && (field.name.startsWith('auth_question') || field.name.startsWith('auth_answer'))) {
                return;
            }
            // Skip password field - handled separately in password validation section
            if (field.name === 'password') {
                return;
            }
            const value = (field.value || '').trim();
            if (!value) {
                showError(field, getRequiredMessage(field));
                isValid = false;
            }
        });

        const explicitRequired = [
        'idnumber', 'firstname', 'lastname', 'gender', 'username', 'repassword',
        'birth_date', 'age',
        'street', 'barangay', 'city_municipality', 'province', 'country', 'zipcode'
    ];
    explicitRequired.forEach(name => {
        // Skip password - handled separately in password validation section
        if (name === 'password') {
            return;
        }
        const f = document.getElementById(name);
        if (f && !(f.value || '').trim()) {
            showError(f, getRequiredMessage(f));
            isValid = false;
        }
    });

        // 2) Password validation
        const passwordEl = document.getElementById('password');
        const repasswordEl = document.getElementById('repassword');
        const strengthMessage = document.querySelector('.password-strength-message');
        const strengthBar = document.querySelector('.strength-bar');
        
        // Check if password field exists and has a value
        if (passwordEl) {
            const password = (passwordEl.value || '').trim();
            if (!password) {
                // Show "Password is required" in strength message area
                if (strengthMessage) {
                    strengthMessage.textContent = 'Password is required';
                    strengthMessage.style.color = '#ff4444';
                }
                // Keep strength bar neutral/empty
                if (strengthBar) {
                    strengthBar.className = 'strength-bar';
                    strengthBar.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
                }
                isValid = false;
            } else {
                // Skip validation if password exists error is active
                if (passwordWithExistsError !== null && password === passwordWithExistsError) {
                    isValid = false;
                } else {
                    // Validate password strength
                    const result = getPasswordStrength(password);
                    if (result.level === 'weak') {
                        // Weak password messages are shown in strength message area via checkPasswordStrength
                        isValid = false;
                    }
                }
            }
        }
        
        // 3) Password match
        if (passwordEl && repasswordEl) {
            const password = (passwordEl.value || '').trim();
            const repassword = (repasswordEl.value || '').trim();
            const repasswordErrorDiv = document.getElementById('repassword-error');
            
            if (!repassword) {
                showError(repasswordEl, 'Please confirm your password');
                isValid = false;
            } else if (password !== repassword) {
                // Show error message - explicitly set color to red
                if (repasswordErrorDiv) {
                    repasswordErrorDiv.textContent = 'Passwords do not match';
                    repasswordErrorDiv.style.color = '#ff4444'; // Explicitly set to red
                    repasswordErrorDiv.style.display = 'block';
                    repasswordErrorDiv.classList.add('show');
                    repasswordEl.style.borderColor = '#f51a2cff';
                }
                isValid = false;
            } else if (repassword) {
                // Clear any previous error if passwords match
                clearError({ target: repasswordEl });
            }
        }

        // 4) Specific format checks (only if still valid so far)
        if (isValid) {
            // Customer ID is a readonly, server-generated preview (see register.php) —
            // never user-typed, so it needs no required/format validation here.

            // Email format
            const emailEl = document.getElementById('email');
            if (emailEl) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test((emailEl.value || '').trim())) {
                    showError(emailEl, 'Please enter a valid email address');
                    isValid = false;
                }
            }

            // Zip code 4 digits
            const zipEl = document.getElementById('zipcode');
            if (zipEl) {
                const zipPattern = /^[0-9]{4}$/;
                if (!zipPattern.test((zipEl.value || '').trim())) {
                    showError(zipEl, 'Zip code must be 4 digits');
                    isValid = false;
                }
            }
        }

        // 5) If any error messages are visible, block submission
        const visibleErrors = document.querySelectorAll('.error-message.show');
        if (visibleErrors.length > 0) {
            isValid = false;
        }

        return isValid;

        
    }
    
    function validateBasicForm() {
        let isValid = true;

        // 1) Required fields: every element with required attribute must be non-empty
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            // Skip password field - handled separately in password validation section
            if (field.name === 'password') {
                return;
            }
            const value = (field.value || '').trim();
            if (!value) {
                showError(field, getRequiredMessage(field));
                isValid = false;
            }
        });

        // Also enforce required for fields that may not have the required attribute in HTML
        const explicitRequired = [
            'idnumber', 'firstname', 'lastname', 'gender', 'username', 'repassword',
            'birth_date', 'age',
            'street', 'barangay', 'city_municipality', 'province', 'country', 'zipcode'
        ];
        explicitRequired.forEach(name => {
            // Skip password - handled separately in password validation section
            if (name === 'password') {
                return;
            }
            const f = document.getElementById(name);
            if (f && !(f.value || '').trim()) {
                showError(f, getRequiredMessage(f));
                isValid = false;
            }
        });

        // Explicitly validate security questions/answers even if they lack the required attribute
        const securityFieldNames = [
            'auth_question1', 'auth_answer1',
            'auth_question2', 'auth_answer2',
            'auth_question3', 'auth_answer3'
        ];
        securityFieldNames.forEach(name => {
            const f = document.getElementById(name);
            if (f) {
                const v = (f.value || '').trim();
                if (!v) {
                    showError(f, 'This field is required');
                    isValid = false;
                }
            }
        });

        // Enforce min/max length for answers: 3 to 30
        ['auth_answer1','auth_answer2','auth_answer3'].forEach(name => {
            const f = document.getElementById(name);
            if (!f) return;
            const v = (f.value || '').trim();
            if (v) {
                if (v.length < 3) {
                    showError(f, 'Answer must be at least 3 characters');
                    isValid = false;
                } else if (v.length > 30) {
                    showError(f, 'Answer must be at most 30 characters');
                    isValid = false;
                }
            }
        });

        // 2) Password validation
        const passwordEl = document.getElementById('password');
        const repasswordEl = document.getElementById('repassword');
        const strengthMessage = document.querySelector('.password-strength-message');
        const strengthBar = document.querySelector('.strength-bar');
        
        // Check if password field exists and has a value
        if (passwordEl) {
            const password = (passwordEl.value || '').trim();
            if (!password) {
                // Show "Password is required" in strength message area
                if (strengthMessage) {
                    strengthMessage.textContent = 'Password is required';
                    strengthMessage.style.color = '#ff4444';
                }
                // Keep strength bar neutral/empty
                if (strengthBar) {
                    strengthBar.className = 'strength-bar';
                    strengthBar.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
                }
                isValid = false;
            } else {
                // Skip validation if password exists error is active
                if (passwordWithExistsError !== null && password === passwordWithExistsError) {
                    isValid = false;
                } else {
                    // Validate password strength
                    const result = getPasswordStrength(password);
                    if (result.level === 'weak') {
                        // Weak password messages are shown in strength message area via checkPasswordStrength
                        isValid = false;
                    }
                }
            }
        }
        
        // 3) Password match
        if (passwordEl && repasswordEl) {
            const password = (passwordEl.value || '').trim();
            const repassword = (repasswordEl.value || '').trim();
            const repasswordErrorDiv = document.getElementById('repassword-error');
            
            if (!repassword) {
                showError(repasswordEl, 'Please confirm your password');
                isValid = false;
            } else if (password !== repassword) {
                // Show error message - explicitly set color to red
                if (repasswordErrorDiv) {
                    repasswordErrorDiv.textContent = 'Passwords do not match';
                    repasswordErrorDiv.style.color = '#ff4444'; // Explicitly set to red
                    repasswordErrorDiv.style.display = 'block';
                    repasswordErrorDiv.classList.add('show');
                    repasswordEl.style.borderColor = '#f51a2cff';
                }
                isValid = false;
            } else if (repassword) {
                // Clear any previous error if passwords match
                clearError({ target: repasswordEl });
            }
        }

        // 4) Specific format checks (only if still valid so far)
        if (isValid) {
            // Customer ID is a readonly, server-generated preview (see register.php) —
            // never user-typed, so it needs no required/format validation here.

            // Email format
            const emailEl = document.getElementById('email');
            if (emailEl) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test((emailEl.value || '').trim())) {
                    showError(emailEl, 'Please enter a valid email address');
                    isValid = false;
                }
            }

            // Zip code 4 digits
            const zipEl = document.getElementById('zipcode');
            if (zipEl) {
                const zipPattern = /^[0-9]{4}$/;
                if (!zipPattern.test((zipEl.value || '').trim())) {
                    showError(zipEl, 'Zip code must be 4 digits');
                    isValid = false;
                }
            }
        }

        // 5) If any error messages are visible, block submission
        const visibleErrors = document.querySelectorAll('.error-message.show');
        if (visibleErrors.length > 0) {
            isValid = false;
        }

        return isValid;
    }
    
});

