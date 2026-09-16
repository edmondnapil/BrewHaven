// Security Questions Form Validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.register-form');
    if (!form) return;
    
    const inputs = form.querySelectorAll('input, select');
    
    // Define maximum character limits for security answer fields
    const maxLengths = {
        'auth_answer1': 30,
        'auth_answer2': 30,
        'auth_answer3': 30
    };
    
    // Function to handle keydown to prevent input when max is reached
    function handleMaxLengthKeydown(e) {
        const field = e.target;
        const fieldName = field.name || field.id;
        if (!fieldName || !maxLengths[fieldName]) return;
        
        // Allow backspace, delete, arrow keys, etc.
        const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End', 'Tab'];
        if (allowedKeys.includes(e.key)) return;
        
        // Allow Ctrl/Cmd combinations (copy, paste, select all, etc.)
        if (e.ctrlKey || e.metaKey) return;
        
        // Check if adding this character would exceed the limit
        if (field.value.length >= maxLengths[fieldName]) {
            e.preventDefault();
            e.stopPropagation();
            // Show error message
            const errorDiv = document.getElementById(fieldName + '-error');
            if (errorDiv) {
                const errorMsg = `You've already reached the maximum of ${maxLengths[fieldName]} characters.`;
                errorDiv.textContent = errorMsg;
                errorDiv.classList.add('show');
                field.style.borderColor = '#f51a2cff';
            }
            return false;
        }
    }
    
    // Function to handle input events and prevent exceeding max length
    function handleMaxLengthInput(e) {
        const field = e.target;
        const fieldName = field.name || field.id;
        if (!fieldName || !maxLengths[fieldName]) return;
        
        // If max is reached, prevent further input
        if (field.value.length >= maxLengths[fieldName]) {
            // Store the current valid value
            const validValue = field.value.substring(0, maxLengths[fieldName]);
            
            // Truncate if exceeded
            if (field.value.length > maxLengths[fieldName]) {
                field.value = validValue;
                // Show error message
                const errorDiv = document.getElementById(fieldName + '-error');
                if (errorDiv) {
                    const errorMsg = `You've already reached the maximum of ${maxLengths[fieldName]} characters.`;
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
            }
        }
    }
    
    // Function to handle paste events
    function handleMaxLengthPaste(e) {
        const field = e.target;
        const fieldName = field.name || field.id;
        if (!fieldName || !maxLengths[fieldName]) return;
        
        // Get pasted text
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        const testValue = field.value + pastedText;
        
        if (testValue.length > maxLengths[fieldName]) {
            e.preventDefault();
            e.stopPropagation();
            // Truncate to max length
            const remainingChars = maxLengths[fieldName] - field.value.length;
            if (remainingChars > 0) {
                field.value = field.value + pastedText.substring(0, remainingChars);
            }
            // Show error message
            const errorDiv = document.getElementById(fieldName + '-error');
            if (errorDiv) {
                const errorMsg = `You've already reached the maximum of ${maxLengths[fieldName]} characters.`;
                errorDiv.textContent = errorMsg;
                errorDiv.classList.add('show');
                field.style.borderColor = '#f51a2cff';
            }
            return false;
        }
    }
    
    // Apply max length enforcement to security answer fields
    inputs.forEach(input => {
        const fieldName = input.name || input.id;
        if (maxLengths[fieldName] && input.tagName === 'INPUT' && input.type === 'password') {
            // Add event listeners for max length enforcement
            input.addEventListener('keydown', handleMaxLengthKeydown);
            input.addEventListener('input', handleMaxLengthInput);
            input.addEventListener('paste', handleMaxLengthPaste);
        }
    });
    
    // Check if registration data exists in localStorage
    const savedData = localStorage.getItem('registrationFormData');
    if (!savedData) {
        // Redirect back to register if no data
        window.location.href = 'register.php';
        return;
    }
    
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
    });
    
    const shouldSkipRealtimeValidation = (event) => event && event.isTrusted === false;

    // Real-time validation
    inputs.forEach(input => {
        input.addEventListener('blur', function(e) {
            if (shouldSkipRealtimeValidation(e)) return;
            validateField({ target: input });
        });
        input.addEventListener('input', function(e) {
            if (shouldSkipRealtimeValidation(e)) return;
            clearError({ target: input });
            validateField({ target: input });
        });
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Trigger validation on all inputs to surface inline errors
        inputs.forEach(input => validateField({ target: input }));

        // Validate security questions form
        if (validateSecurityForm()) {
            const submitButton = form.querySelector('button[type="submit"]');
            // Get registration data from localStorage
            const registrationData = JSON.parse(localStorage.getItem('registrationFormData'));
            
            // Get authentication questions data
            const authData = {
                auth_question1: document.getElementById('auth_question1').value,
                auth_answer1: document.getElementById('auth_answer1').value,
                auth_question2: document.getElementById('auth_question2').value,
                auth_answer2: document.getElementById('auth_answer2').value,
                auth_question3: document.getElementById('auth_question3').value,
                auth_answer3: document.getElementById('auth_answer3').value
            };
            
            // Combine all data
            const completeData = { ...registrationData, ...authData };
            
            const buildFormData = () => {
                const formData = new FormData();
                for (let [key, value] of Object.entries(completeData)) {
                    formData.append(key, value);
                }
                return formData;
            };

            const redirectToLogin = () => {
                localStorage.removeItem('registrationFormData');
                sessionStorage.removeItem('registrationSessionActive');
                sessionStorage.removeItem('registrationFormDataSession');
                window.location.href = 'login.php';
            };

            if (navigator.sendBeacon) {
                try {
                    navigator.sendBeacon('register.php', buildFormData());
                    redirectToLogin();
                    return;
                } catch (err) {
                    // Fallback to fetch if beacon fails
                }
            }

            if (submitButton) {
                submitButton.disabled = true;
            }

            // Submit to PHP
            fetch('register.php', {
                method: 'POST',
                body: buildFormData()
            })
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
                    redirectToLogin();
                } else {
                    const message = (data && (data.message || data.error)) || 'Registration failed. Please try again.';
                    // Map common server messages to specific fields
                    const lower = String(message).toLowerCase();
                    const fieldMap = [
                        { match: 'id number already exists', id: 'auth_question1' },
                        { match: 'username already exists', id: 'auth_question1' },
                        { match: 'email already exists', id: 'auth_question1' },
                        { match: 'passwords do not match', id: 'auth_question1' },
                        { match: 'must be at least 18 years old', id: 'auth_question1' },
                        { match: 'all required fields', id: 'auth_question1' }
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
                        // Fallback: show near first question field
                        const fallback = document.getElementById('auth_question1');
                        if (fallback) {
                            showError(fallback, message);
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Registration error:', error);
                const fallback = document.getElementById('auth_question1');
                if (fallback) {
                    showError(fallback, 'An unexpected error occurred. Please try again.');
                }
            })
            .finally(() => {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            });
        }
    });
    
    function validateField(e) {
        const field = e.target;
        const fieldName = field.name;
        const value = field.value.trim();
        
        clearError(e);
        
        switch(fieldName) {
            case 'auth_question1':
            case 'auth_question2':
            case 'auth_question3':
                validateAuthQuestion(value, field);
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
                break;
            }
        }
    }
    
    function validateAuthQuestion(value, field) {
        if (!value) {
            showError(field, 'Please select a question');
            return false;
        }
        
        // Check for duplicate questions
        const allQuestions = [
            document.getElementById('auth_question1').value,
            document.getElementById('auth_question2').value,
            document.getElementById('auth_question3').value
        ];
        
        const duplicates = allQuestions.filter((question, index) => 
            question && allQuestions.indexOf(question) !== index
        );
        
        return true;
    }
    
    function validateSecurityForm() {
        let isValid = true;
        
        // Validate all authentication questions and answers
        const authQuestions = ['auth_question1', 'auth_question2', 'auth_question3'];
        const authAnswers = ['auth_answer1', 'auth_answer2', 'auth_answer3'];
        
        // Check for duplicate questions
        const selectedQuestions = authQuestions.map(q => document.getElementById(q).value);
        const uniqueQuestions = [...new Set(selectedQuestions.filter(q => q))];
        
        if (uniqueQuestions.length !== 3) {
            // Find which question is duplicated
            const questionCounts = {};
            const duplicatedQuestions = [];
            
            selectedQuestions.forEach((q, index) => {
                if (q) {
                    if (questionCounts[q]) {
                        const selectElement = document.getElementById(authQuestions[index]);
                        const questionText = selectElement.options[selectElement.selectedIndex].text;
                        if (!duplicatedQuestions.includes(questionText)) {
                            duplicatedQuestions.push(questionText);
                        }
                    }
                    questionCounts[q] = (questionCounts[q] || 0) + 1;
                }
            });
            
            isValid = false;
        }
        
        authQuestions.forEach((questionName, index) => {
            const questionField = document.getElementById(questionName);
            const answerField = document.getElementById(authAnswers[index]);
            
            if (!questionField.value.trim()) {
                showError(questionField, 'Please select a question');
                isValid = false;
            }
            
            if (!answerField.value.trim()) {
                showError(answerField, 'This field is required');
                isValid = false;
            } else {
                const answerValue = answerField.value.trim();
                if (answerValue.length < 3) {
                    showError(answerField, 'Answer must be at least 3 characters');
                    isValid = false;
                } else if (answerValue.length > 30) {
                    showError(answerField, 'Answer must be at most 30 characters');
                    isValid = false;
                }
            }
        });
        
        // If any error messages are visible, block submission
        const visibleErrors = document.querySelectorAll('.error-message.show');
        if (visibleErrors.length > 0) {
            isValid = false;
        }
        
        return isValid;
    }
    
    function showError(field, message) {
        const errorDiv = document.getElementById(field.name + '-error');
        if (errorDiv) {
            const wasShown = errorDiv.classList.contains('show');
            errorDiv.textContent = message;
            errorDiv.style.fontSize = '15px';
            errorDiv.classList.add('show');
            if (!wasShown) {
                updateFormWidth();
            }
        }
        field.style.borderColor = '#ff4757';
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
        field.style.borderColor = '#3a2a20';
    }
    
    function updateFormWidth() {
        const heroInner = document.querySelector('.hero-inner');
        if (!heroInner) return;
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
});
