// Validation for the Super Admin's "Create Admin / Super Admin" modal.
// Standardized to match Customer Registration validation behavior, password rules, and error handling.
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('createStaffForm');
    if (!form) return;

    var formErrorDiv = document.getElementById('ca_form-error');
    var usernameDebounce;
    var emailDebounce;

    var usernameEl = document.getElementById('ca_username');
    var emailEl = document.getElementById('ca_email');
    var passwordEl = document.getElementById('ca_password');
    var repasswordEl = document.getElementById('ca_repassword');
    var roleEl = document.getElementById('ca_role');

    var strengthBarEl = form.querySelector('.strength-bar');
    var strengthMsgEl = form.querySelector('.password-strength-message');

    function showError(field, message) {
        var errorDiv = document.getElementById(field.id + '-error');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.color = '#ff4444';
            errorDiv.classList.add('show');
        }
        field.classList.add('bh-field-error');
    }

    function clearError(field) {
        var errorDiv = document.getElementById(field.id + '-error');
        if (errorDiv) {
            errorDiv.textContent = '';
            errorDiv.classList.remove('show');
        }
        field.classList.remove('bh-field-error');
        field.style.borderColor = '';
    }

    function clearAllErrors() {
        form.querySelectorAll('.error-message').forEach(function (el) {
            el.textContent = '';
            el.classList.remove('show');
            el.style.color = '';
        });
        form.querySelectorAll('.bh-field-error').forEach(function (el) {
            el.classList.remove('bh-field-error');
            el.style.borderColor = '';
        });
        if (formErrorDiv) {
            formErrorDiv.textContent = '';
            formErrorDiv.classList.remove('show');
        }
    }

    // ---- Username validation & duplicate check ----
    function validateUsernameField(showRequired) {
        if (!usernameEl) return true;
        var val = usernameEl.value;
        if (!val.trim()) {
            if (showRequired) {
                showError(usernameEl, 'Username is required');
                return false;
            }
            clearError(usernameEl);
            return false;
        }
        var res = (window.BHValidation && window.BHValidation.validateUsername)
            ? window.BHValidation.validateUsername(val)
            : { valid: true, error: '' };
        if (!res.valid) {
            showError(usernameEl, res.error);
            return false;
        }
        clearError(usernameEl);
        return true;
    }

    function checkUsernameAvailability() {
        if (!usernameEl) return;
        var value = usernameEl.value.trim();
        if (value.length < 4) return;
        fetch('../php/check_username.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'username=' + encodeURIComponent(value)
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data.success && data.data && data.data.exists) {
                showError(usernameEl, 'Username already exists');
            } else if (data.success && data.data && !data.data.exists) {
                var errorDiv = document.getElementById('ca_username-error');
                if (errorDiv && errorDiv.textContent === 'Username already exists') {
                    clearError(usernameEl);
                }
            }
        }).catch(function () {});
    }

    if (usernameEl) {
        usernameEl.addEventListener('input', function () {
            if (validateUsernameField(false)) {
                clearTimeout(usernameDebounce);
                usernameDebounce = setTimeout(checkUsernameAvailability, 400);
            }
        });
        usernameEl.addEventListener('blur', function () {
            if (validateUsernameField(true)) {
                checkUsernameAvailability();
            }
        });
    }

    // ---- Email validation & duplicate check ----
    function validateEmailField(showRequired) {
        if (!emailEl) return true;
        var val = emailEl.value;
        if (!val.trim()) {
            if (showRequired) {
                showError(emailEl, 'Email is required');
                return false;
            }
            clearError(emailEl);
            return false;
        }
        var res = (window.BHValidation && window.BHValidation.validateEmail)
            ? window.BHValidation.validateEmail(val)
            : { valid: true, error: '' };
        if (!res.valid) {
            showError(emailEl, res.error);
            return false;
        }
        clearError(emailEl);
        return true;
    }

    function checkEmailAvailability() {
        if (!emailEl) return;
        var value = emailEl.value.trim();
        if (!value || !value.includes('@') || !value.includes('.')) return;
        fetch('../php/check_email.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'email=' + encodeURIComponent(value)
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (data.success && data.data && data.data.exists) {
                showError(emailEl, 'Email already exists');
            } else if (data.success && data.data && !data.data.exists) {
                var errorDiv = document.getElementById('ca_email-error');
                if (errorDiv && errorDiv.textContent === 'Email already exists') {
                    clearError(emailEl);
                }
            }
        }).catch(function () {});
    }

    if (emailEl) {
        emailEl.addEventListener('input', function () {
            if (validateEmailField(false)) {
                clearTimeout(emailDebounce);
                emailDebounce = setTimeout(checkEmailAvailability, 400);
            }
        });
        emailEl.addEventListener('blur', function () {
            if (validateEmailField(true)) {
                checkEmailAvailability();
            }
        });
    }

    // ---- Password validation & live strength meter ----
    function updatePasswordStrength() {
        if (!passwordEl) return { valid: false, level: 'empty' };
        var val = passwordEl.value;
        if (!val) {
            if (strengthBarEl) {
                strengthBarEl.className = 'strength-bar';
            }
            if (strengthMsgEl) {
                strengthMsgEl.textContent = '';
            }
            clearError(passwordEl);
            return { valid: false, level: 'empty' };
        }
        var result = (window.BHValidation && window.BHValidation.updatePasswordStrengthUI)
            ? window.BHValidation.updatePasswordStrengthUI(val, strengthBarEl, strengthMsgEl)
            : { valid: val.length >= 8, level: 'strong' };
        
        clearError(passwordEl);
        return result;
    }

    if (passwordEl) {
        passwordEl.addEventListener('input', function () {
            updatePasswordStrength();
            if (repasswordEl && repasswordEl.value) {
                validatePasswordMatch();
            }
        });
        passwordEl.addEventListener('blur', function () {
            if (!this.value) {
                if (strengthMsgEl) {
                    strengthMsgEl.textContent = 'Password is required';
                    strengthMsgEl.style.color = '#ff4444';
                }
                showError(this, 'Password is required');
            } else {
                var res = (window.BHValidation && window.BHValidation.validatePasswordStrength)
                    ? window.BHValidation.validatePasswordStrength(this.value)
                    : { valid: true };
                if (res.level === 'weak') {
                    // Message already visible in strength message area
                    this.classList.add('bh-field-error');
                } else {
                    this.classList.remove('bh-field-error');
                }
            }
        });
    }

    // ---- Confirm Password validation ----
    function validatePasswordMatch() {
        if (!repasswordEl) return true;
        var p1 = passwordEl ? passwordEl.value : '';
        var p2 = repasswordEl.value;
        var errorDiv = document.getElementById('ca_repassword-error');

        if (!p2) {
            clearError(repasswordEl);
            return false;
        }

        if (p1 && p2) {
            if (p1 !== p2) {
                if (errorDiv) {
                    errorDiv.textContent = 'Passwords do not match';
                    errorDiv.style.color = '#ff4444';
                    errorDiv.classList.add('show');
                }
                repasswordEl.classList.add('bh-field-error');
                repasswordEl.style.borderColor = '#f51a2cff';
                return false;
            } else {
                if (errorDiv) {
                    errorDiv.textContent = 'Passwords match';
                    errorDiv.style.color = '#00a854';
                    errorDiv.classList.add('show');
                }
                repasswordEl.classList.remove('bh-field-error');
                repasswordEl.style.borderColor = '#00a854';
                return true;
            }
        }
        return false;
    }

    if (repasswordEl) {
        repasswordEl.addEventListener('input', validatePasswordMatch);
        repasswordEl.addEventListener('blur', function () {
            if (!this.value) {
                showError(this, 'Please confirm your password');
            } else {
                validatePasswordMatch();
            }
        });
    }

    // ---- Role selection ----
    function isCustomerRole() {
        return roleEl && roleEl.value === 'customer';
    }

    function applyRole() {
        form.setAttribute('data-bh-confirm', isCustomerRole()
            ? 'Are you sure you want to create this User / Customer account?'
            : 'Are you sure you want to create this account?');
        // Keep the privilege list under the dropdown in step with the choice,
        // including when the wizard is reset back to an empty selection.
        if (typeof window.bhRenderRolePrivileges === 'function') {
            window.bhRenderRolePrivileges('ca_role', 'ca_privileges', 'ca_selectedRole', 'ca_roleNote');
        }
    }

    if (roleEl) {
        roleEl.addEventListener('change', function () {
            if (this.value) clearError(this);
            applyRole();
        });
    }

    window.bhResetCreateStaffWizard = function () {
        clearAllErrors();
        form.reset();
        if (strengthBarEl) strengthBarEl.className = 'strength-bar';
        if (strengthMsgEl) strengthMsgEl.textContent = '';
        if (repasswordEl) repasswordEl.style.borderColor = '';
        applyRole();
    };

    applyRole();

    // Exposed to the shared action-confirmation dialog so these checks run
    // BEFORE the Super Admin is asked to re-enter their password.
    form.bhValidate = function () {
        clearAllErrors();

        if (roleEl && !roleEl.value) {
            showError(roleEl, 'Please select a role.');
            roleEl.focus();
            return false;
        }

        if (!validateUsernameField(true)) {
            usernameEl.focus();
            return false;
        }

        if (usernameEl.classList.contains('bh-field-error')) {
            usernameEl.focus();
            return false;
        }

        if (!validateEmailField(true)) {
            emailEl.focus();
            return false;
        }

        if (emailEl.classList.contains('bh-field-error')) {
            emailEl.focus();
            return false;
        }

        if (passwordEl) {
            var pVal = passwordEl.value;
            if (!pVal) {
                showError(passwordEl, 'Password is required');
                if (strengthMsgEl) {
                    strengthMsgEl.textContent = 'Password is required';
                    strengthMsgEl.style.color = '#ff4444';
                }
                passwordEl.focus();
                return false;
            }
            var pRes = (window.BHValidation && window.BHValidation.validatePasswordStrength)
                ? window.BHValidation.validatePasswordStrength(pVal)
                : { valid: true, level: 'strong' };
            if (pRes.level === 'weak') {
                showError(passwordEl, pRes.error || 'Password is too weak');
                passwordEl.focus();
                return false;
            }
        }

        if (repasswordEl) {
            if (!repasswordEl.value) {
                showError(repasswordEl, 'Please confirm your password');
                repasswordEl.focus();
                return false;
            }
            if (!validatePasswordMatch()) {
                repasswordEl.focus();
                return false;
            }
        }

        return true;
    };

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!form.bhValidate()) return;

        var submitBtn = document.getElementById('createStaffSubmit');
        var originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating...';

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data && data.success) {
                    window.location.href = 'super-admin-accounts.php?msg=' +
                        encodeURIComponent(data.msg || (isCustomerRole() ? 'customer_created' : 'staff_created'));
                } else if (data) {
                    var targetField = document.getElementById('ca_' + (data.field || 'form'));
                    if (targetField) {
                        showError(targetField, data.message || 'Unable to create the account.');
                        targetField.focus();
                    } else {
                        formErrorDiv.textContent = data.message || 'Unable to create the account.';
                        formErrorDiv.classList.add('show');
                    }
                } else {
                    formErrorDiv.textContent = 'Unable to create the account. Please try again.';
                    formErrorDiv.classList.add('show');
                }
            })
            .catch(function () {
                formErrorDiv.textContent = 'An unexpected error occurred. Please try again.';
                formErrorDiv.classList.add('show');
            })
            .finally(function () {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
    });
});
