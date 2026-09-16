/**
 * Brew Haven - Shared Validation Rules Module
 * Reuses the exact validation logic and error messages from user registration
 * across first-login personal information, password change, and security questions.
 */
(function(window) {
    'use strict';

    const BHValidation = {
        /**
         * Validates name fields (First name, Last name, Middle name)
         */
        validateName: function(rawValue, fieldName = 'Name', isRequired = true) {
            if (!rawValue || !rawValue.trim()) {
                if (isRequired) {
                    return { valid: false, error: fieldName + ' is required' };
                }
                return { valid: true, error: '' };
            }

            const str = rawValue;
            const trimmed = str.trim();

            if (/^\s/.test(str) || /\s$/.test(str)) {
                return { valid: false, error: 'Leading or trailing spaces are not allowed' };
            }

            if (/^[0-9]/.test(str) || /[0-9]/.test(str)) {
                return { valid: false, error: 'Numbers are not allowed' };
            }

            if (/[^A-Za-zñÑ\s\-']/.test(str)) {
                return { valid: false, error: 'Special characters are not allowed' };
            }

            if (/^[a-zñ]/.test(str)) {
                return { valid: false, error: 'First letter must be uppercase' };
            }

            if (/\s{2,}/.test(str)) {
                return { valid: false, error: 'Double spaces are not allowed' };
            }

            if (/([a-zñA-ZÑ])\1{2,}/i.test(str)) {
                return { valid: false, error: 'Three identical consecutive letters are not allowed' };
            }

            // Check each word has first letter uppercase
            const words = trimmed.split(/\s+/).filter(Boolean);
            for (let i = 0; i < words.length; i++) {
                const w = words[i];
                if (w.length > 1 && w === w.toUpperCase() && /^[A-ZÑ]+$/.test(w)) {
                    return { valid: false, error: 'All capital letters are not allowed' };
                }
                if (w[0] !== w[0].toUpperCase()) {
                    return { valid: false, error: `First letter of word ${i + 1} must be uppercase` };
                }
            }

            if (/[A-ZÑ]{2,}/.test(str)) {
                return { valid: false, error: 'Consecutive capital letters are not allowed' };
            }

            if (trimmed.length < 2) {
                return { valid: false, error: fieldName + ' must be at least 2 letters' };
            }

            if (trimmed.length > 50) {
                return { valid: false, error: fieldName + ' must not exceed 50 characters' };
            }

            return { valid: true, error: '' };
        },

        /**
         * Validates birth date and calculates age
         */
        validateBirthDateAndAge: function(birthDateStr) {
            if (!birthDateStr) {
                return { valid: false, error: 'Birth date is required', age: null };
            }

            const birth = new Date(birthDateStr);
            const today = new Date();

            if (isNaN(birth.getTime())) {
                return { valid: false, error: 'Invalid birth date', age: null };
            }

            if (birth > today) {
                return { valid: false, error: 'Birth date cannot be in the future', age: null };
            }

            let age = today.getFullYear() - birth.getFullYear();
            const m = today.getMonth() - birth.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                age--;
            }

            if (age < 0) {
                return { valid: false, error: 'Invalid age', age: 0 };
            }

            if (age < 18) {
                return { valid: false, error: 'Must be at least 18 years old', age: age };
            }

            return { valid: true, error: '', age: age };
        },

        /**
         * Validates address components (street, barangay, city, province, country, zipcode)
         */
        validateAddressField: function(fieldName, rawValue, isRequired = true) {
            const prettyName = fieldName.charAt(0).toUpperCase() + fieldName.slice(1).replace(/_/g, ' ');
            if (!rawValue || !rawValue.trim()) {
                if (isRequired) {
                    return { valid: false, error: prettyName + ' is required' };
                }
                return { valid: true, error: '' };
            }

            const str = rawValue;
            const trimmed = str.trim();

            if (/^\s/.test(str)) {
                return { valid: false, error: 'Leading space is not allowed' };
            }

            if (/^[^A-Za-zñÑ0-9]/.test(str)) {
                return { valid: false, error: 'Must not start with a special character.' };
            }

            if (fieldName === 'zipcode') {
                if (!/^\d+$/.test(trimmed)) {
                    return { valid: false, error: 'Zip code must be digits only' };
                }
                if (trimmed.length !== 4) {
                    return { valid: false, error: 'Zip code must be 4 digits' };
                }
            } else {
                if (fieldName === 'street' && trimmed.length < 5) {
                    return { valid: false, error: 'Street address must be at least 5 characters' };
                }
                if (trimmed.length < 2) {
                    return { valid: false, error: prettyName + ' must be at least 2 characters' };
                }
                if (trimmed.length > 100) {
                    return { valid: false, error: prettyName + ' must not exceed 100 characters' };
                }
            }

            return { valid: true, error: '' };
        },

        /**
         * Validates password strength (minimum 8 chars, letter, number, special char)
         */
        validatePasswordStrength: function(password) {
            if (!password) {
                return { valid: false, level: 'empty', error: 'Password is required' };
            }

            const hasLetter = /[A-Za-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[^A-Za-z0-9]/.test(password);
            const hasMinLength = password.length >= 8;

            if (!hasLetter) {
                return { valid: false, level: 'weak', error: 'Weak: Add a letter' };
            }
            if (!hasNumber) {
                return { valid: false, level: 'weak', error: 'Weak: Add a number' };
            }
            if (!hasMinLength) {
                return { valid: false, level: 'weak', error: 'Weak: Password must be at least 8 characters' };
            }
            if (!hasSpecial) {
                return { valid: false, level: 'medium', error: 'Medium: Add special characters to strengthen' };
            }

            return { valid: true, level: 'strong', error: 'Strong password!' };
        },

        /**
         * Updates password strength bar and message UI (matching customer registration)
         */
        updatePasswordStrengthUI: function(password, strengthBarEl, strengthMessageEl) {
            const result = this.validatePasswordStrength(password);
            if (strengthBarEl) {
                strengthBarEl.className = 'strength-bar';
                if (result.level === 'weak') {
                    strengthBarEl.classList.add('strength-weak');
                } else if (result.level === 'medium') {
                    strengthBarEl.classList.add('strength-medium');
                } else if (result.level === 'strong') {
                    strengthBarEl.classList.add('strength-strong');
                }
            }
            if (strengthMessageEl) {
                if (!password) {
                    strengthMessageEl.textContent = 'Password is required';
                    strengthMessageEl.style.color = '#ff4444';
                } else {
                    strengthMessageEl.textContent = result.error;
                    strengthMessageEl.style.color = result.level === 'weak' ? '#ff4444' : 
                                                 result.level === 'medium' ? '#ffbb33' : '#00c851';
                }
            }
            return result;
        },

        /**
         * Validates username (Format must be lowercase letter followed by letters/numbers like juan1234, 4-20 chars)
         */
        validateUsername: function(rawValue) {
            if (!rawValue || !rawValue.trim()) {
                return { valid: false, error: 'Username is required' };
            }
            const value = rawValue.trim();
            if (value.length < 4) {
                return { valid: false, error: 'Username must be at least 4 characters' };
            }
            if (value.length > 20) {
                return { valid: false, error: 'Username must be less than 20 characters' };
            }
            if (!/^[a-z]/.test(value)) {
                return { valid: false, error: 'invalid input. Format must be "juan1234"' };
            }
            if (/[^a-z0-9]/.test(value)) {
                return { valid: false, error: 'invalid input. Format must be "juan1234"' };
            }
            let seenDigit = false;
            for (let i = 0; i < value.length; i++) {
                const ch = value[i];
                if (/[0-9]/.test(ch)) {
                    seenDigit = true;
                } else if (/[a-z]/.test(ch) && seenDigit) {
                    return { valid: false, error: 'invalid input. Format must be "juan1234"' };
                }
            }
            if (!/[0-9]/.test(value)) {
                return { valid: false, error: 'invalid input. Format must be "juan1234"' };
            }
            return { valid: true, error: '' };
        },

        /**
         * Validates email format matching customer registration rules
         */
        validateEmail: function(rawValue) {
            if (!rawValue || !rawValue.trim()) {
                return { valid: false, error: 'Email is required' };
            }
            const str = rawValue;
            const value = rawValue.trim();

            if (/[A-Z]/.test(str)) {
                return { valid: false, error: 'Invalid email format: use small letters only.' };
            }
            if (!str.includes('@')) {
                return { valid: false, error: 'Invalid email format: email must contain \'@\'' };
            }
            if (/^\s/.test(str)) {
                return { valid: false, error: 'Email cannot contain spaces' };
            }
            if (/^[0-9]/.test(str)) {
                return { valid: false, error: 'Email must not start with a number' };
            }
            if (/^[^a-z0-9@]/i.test(str)) {
                return { valid: false, error: 'Email must not start with a special character' };
            }
            if (str.startsWith('@')) {
                return { valid: false, error: 'Email cannot start with "@"' };
            }
            if (/\s/.test(str)) {
                return { valid: false, error: 'Email cannot contain spaces' };
            }
            const atIndex = str.indexOf('@');
            const secondAt = str.indexOf('@', atIndex + 1);
            if (secondAt !== -1) {
                return { valid: false, error: 'Email cannot contain more than one "@" symbol' };
            }
            if (/[^a-z0-9]{2,}/i.test(str)) {
                return { valid: false, error: 'Email cannot contain consecutive periods (..)' };
            }
            const localPart = str.substring(0, atIndex);
            const domainPart = str.substring(atIndex + 1);
            if (!domainPart || !domainPart.includes('.')) {
                return { valid: false, error: 'Invalid email format: domain must contain a dot "."' };
            }
            for (let i = 0; i < localPart.length; i++) {
                if (!/[a-z0-9.]/i.test(localPart[i])) {
                    return { valid: false, error: 'Dot "." is the only allowed special character in the local part' };
                }
            }
            for (let i = 0; i < domainPart.length; i++) {
                if (!/[a-z0-9.]/i.test(domainPart[i])) {
                    return { valid: false, error: 'Dot "." is the only allowed special character in the domain part' };
                }
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                return { valid: false, error: 'Please provide a valid email address' };
            }
            // Correct syntax is not enough — the domain has to be one the
            // system accepts. This is the courtesy message; the same list is
            // re-checked by bh_validate_email_domain() on the server, which is
            // what actually decides.
            const approved = BHValidation.approvedEmailDomains();
            if (approved.length && approved.indexOf(domainPart.toLowerCase()) === -1) {
                return { valid: false, error: 'Email domain is not accepted. Please use one of: ' + approved.join(', ') + '.' };
            }
            return { valid: true, error: '' };
        },

        /**
         * The approved email domains. A page can publish the backend's own list
         * as window.BH_APPROVED_EMAIL_DOMAINS (see bh_approved_email_domains_json()
         * in php/auth_helpers.php) so the live check never drifts from the rule
         * the server enforces; this literal is only the fallback.
         */
        approvedEmailDomains: function() {
            if (Array.isArray(window.BH_APPROVED_EMAIL_DOMAINS) && window.BH_APPROVED_EMAIL_DOMAINS.length) {
                return window.BH_APPROVED_EMAIL_DOMAINS.map(function (d) { return String(d).toLowerCase(); });
            }
            return ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'icloud.com', 'brewhaven.local', 'csucc.edu.ph'];
        },

        /**
         * Validates password match
         */
        validatePasswordMatch: function(password, confirmPassword) {
            if (!confirmPassword) {
                return { valid: false, error: 'Please confirm your password' };
            }
            if (password !== confirmPassword) {
                return { valid: false, error: 'Passwords do not match' };
            }
            return { valid: true, error: '' };
        },

        /**
         * Validates 3 security questions and answers
         */
        validateSecurityQuestions: function(q1, a1, q2, a2, q3, a3) {
            const fieldErrors = {};
            let isValid = true;

            const questions = [q1, q2, q3];
            const answers = [a1, a2, a3];

            for (let i = 0; i < 3; i++) {
                const qNum = i + 1;
                const qVal = (questions[i] || '').trim();
                const aVal = (answers[i] || '').trim();

                if (!qVal) {
                    fieldErrors['auth_question' + qNum] = 'Please select a question';
                    isValid = false;
                }

                if (!aVal) {
                    fieldErrors['auth_answer' + qNum] = 'This field is required';
                    isValid = false;
                } else if (aVal.length < 3) {
                    fieldErrors['auth_answer' + qNum] = 'Answer must be at least 3 characters';
                    isValid = false;
                } else if (aVal.length > 30) {
                    fieldErrors['auth_answer' + qNum] = 'Answer must be at most 30 characters';
                    isValid = false;
                }
            }

            // Check uniqueness of selected questions
            const filledQuestions = questions.filter(Boolean);
            const uniqueQuestions = new Set(filledQuestions);
            if (filledQuestions.length === 3 && uniqueQuestions.size !== 3) {
                fieldErrors['general'] = 'Security questions must all be different';
                isValid = false;
            }

            return { valid: isValid, fieldErrors: fieldErrors };
        },

        /**
         * Helper to show error below an input element
         */
        showFieldError: function(inputEl, message) {
            if (!inputEl) return;
            const fieldName = inputEl.name || inputEl.id;
            let errorEl = document.getElementById(fieldName + '-error');
            if (!errorEl) {
                errorEl = document.createElement('div');
                errorEl.id = fieldName + '-error';
                errorEl.className = 'error-message';
                inputEl.parentNode.insertBefore(errorEl, inputEl.nextSibling);
            }
            errorEl.textContent = message || '';
            errorEl.style.display = message ? 'block' : 'none';
            if (message) {
                errorEl.classList.add('show');
                inputEl.style.borderColor = '#ff4757';
            } else {
                errorEl.classList.remove('show');
                inputEl.style.borderColor = '';
            }
        },

        /**
         * Helper to clear error below an input element
         */
        clearFieldError: function(inputEl) {
            if (!inputEl) return;
            const fieldName = inputEl.name || inputEl.id;
            const errorEl = document.getElementById(fieldName + '-error');
            if (errorEl) {
                errorEl.textContent = '';
                errorEl.style.display = 'none';
                errorEl.classList.remove('show');
            }
            inputEl.style.borderColor = '';
        }
    };

    window.BHValidation = BHValidation;
})(window);
