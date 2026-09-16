/**
 * Live validation for every "edit account information" surface:
 *   - the customer / staff self-service profile form (profile.php)
 *   - the Edit Account modal on the Super Admin accounts page
 *   - the Edit Account modal on the Admin accounts page
 *   - the Change Password form on profile.php
 *   - the first-login Complete Your Profile form (complete-profile.php)
 *
 * The rules are NOT written again here. Everything defers to BHValidation
 * (js/bh-validation-rules.js), which is the front-end mirror of the
 * bh_validate_* helpers in php/auth_helpers.php that registration and the
 * first-login setup already run. So an edit is held to exactly the same
 * standard as a registration, and there is one place to change a rule rather
 * than three that quietly drift apart.
 *
 * As always, this is only the immediate feedback. account_update.php re-runs
 * bh_validate_user_profile() and change_password.php re-runs
 * bh_validate_password_strength() on the server, which is what actually
 * decides whether anything is written.
 */
(function () {
  'use strict';

  var V = window.BHValidation;
  if (!V) {
    // bh-validation-rules.js has to load first. Without it there is no shared
    // rule set to enforce, and silently running a weaker local copy is exactly
    // the drift this file exists to avoid.
    if (window.console && console.warn) {
      console.warn('[brew-haven] bh-validation-rules.js must load before user-profile-validation.js; live edit validation is off.');
    }
    return;
  }

  function messageElement(field) {
    // Reuse the field's own error box when the page already provides one
    // (the modals and the first-login form both do), so the message lands in
    // the same spot the rest of the system puts it.
    if (field.id) {
      var existing = document.getElementById(field.id + '-error');
      if (existing) { return existing; }
    }
    var message = field.parentElement.querySelector('.user-profile-error');
    if (!message) {
      message = document.createElement('div');
      message.className = 'user-profile-error error-message';
      field.parentElement.appendChild(message);
    }
    return message;
  }

  function setError(field, message) {
    var element = messageElement(field);
    element.textContent = message;
    element.classList.toggle('show', Boolean(message));
    field.classList.toggle('bh-field-error', Boolean(message));
    // Keeps the browser's own required-field bubble in step with ours.
    field.setCustomValidity(message || '');
    return !message;
  }

  function apply(field, result) {
    if (result.valid) {
      // A server-side check (e.g. "Email already exists") recorded for this
      // exact value still stands until the value changes.
      if (field.dataset.bhRemoteError && field.dataset.bhRemoteValue === field.value) {
        return setError(field, field.dataset.bhRemoteError);
      }
      return setError(field, '');
    }
    return setError(field, result.error);
  }

  var ADDRESS_FIELDS = ['street', 'barangay', 'city_municipality', 'province', 'zipcode', 'country'];
  var NAME_LABELS = { firstname: 'First Name', lastname: 'Last Name', middlename: 'Middle Name' };

  // The account form always carries a full address, so every address field is
  // required. This is what makes an emptied Barangay fail as you type instead
  // of only after the round trip to the server.
  function validateField(field, form) {
    var name = field.name;

    if (NAME_LABELS[name]) {
      return apply(field, V.validateName(field.value, NAME_LABELS[name], name !== 'middlename'));
    }
    if (name === 'email') {
      return apply(field, V.validateEmail(field.value));
    }
    if (name === 'username') {
      return apply(field, V.validateUsername(field.value));
    }
    if (name === 'extension') {
      if (field.value && !/^(Jr|Sr|I|II|III|IV|V|VI|VII|VIII|IX|X)$/.test(field.value)) {
        return setError(field, 'Input a valid extension (e.g., Jr, Sr, III).');
      }
      return setError(field, '');
    }
    if (name === 'gender') {
      if (['Male', 'Female', 'Other'].indexOf(field.value) === -1) {
        return setError(field, 'Please select a valid gender.');
      }
      return setError(field, '');
    }
    if (name === 'birth_date') {
      var birth = V.validateBirthDateAndAge(field.value);
      // The age box is derived from the date, so keep the two consistent and
      // clear any stale error sitting on it.
      var ageField = form.querySelector('[name="age"]');
      if (birth.valid && ageField) {
        ageField.value = birth.age;
        setError(ageField, '');
      }
      return apply(field, birth);
    }
    if (name === 'age') {
      var dateField = form.querySelector('[name="birth_date"]');
      if (dateField && dateField.value) {
        var fromDate = V.validateBirthDateAndAge(dateField.value);
        // bh_validate_user_profile() rejects an age that disagrees with the
        // birth date, so the mismatch is caught here rather than on submit.
        if (fromDate.valid && String(fromDate.age) !== String(field.value)) {
          return setError(field, 'Age does not match the birth date.');
        }
      }
      if (!/^\d+$/.test(field.value) || Number(field.value) < 18) {
        return setError(field, 'Must be at least 18 years old.');
      }
      return setError(field, '');
    }
    if (ADDRESS_FIELDS.indexOf(name) !== -1) {
      return apply(field, V.validateAddressField(name, field.value, true));
    }

    // --- Change Password form ---------------------------------------------
    if (name === 'new_password') {
      var strength = V.validatePasswordStrength(field.value);
      if (!strength.valid) { return setError(field, strength.error); }
      var current = form.querySelector('[name="current_password"]');
      if (current && current.value && current.value === field.value) {
        return setError(field, 'Your new password must be different from your current one.');
      }
      return setError(field, '');
    }
    if (name === 'confirm_password') {
      var newField = form.querySelector('[name="new_password"]');
      return apply(field, V.validatePasswordMatch(newField ? newField.value : '', field.value));
    }
    if (name === 'current_password') {
      if (!field.value) { return setError(field, 'Current password is required.'); }
      return setError(field, '');
    }

    return true;
  }

  function wire(form) {
    var fields = Array.prototype.slice.call(form.querySelectorAll('input, select, textarea')).filter(function (field) {
      return field.name && field.type !== 'hidden' && field.type !== 'file';
    });

    fields.forEach(function (field) {
      var check = function () { validateField(field, form); };
      field.addEventListener('input', check);
      field.addEventListener('change', check);
      // Catches a field left empty and tabbed past, which neither input nor
      // change ever fires for.
      field.addEventListener('blur', check);
    });

    function validateAll() {
      // Every field is checked, not just up to the first failure, so the user
      // sees all of the problems at once instead of one per attempt.
      var firstBad = null;
      fields.forEach(function (field) {
        if (!validateField(field, form) && !firstBad) { firstBad = field; }
      });
      if (firstBad) { firstBad.focus(); }
      return !firstBad;
    }

    // The shared confirmation dialog asks a form for its verdict before it
    // prompts for a reason or a password, so an invalid edit is stopped before
    // anyone is asked to re-authenticate for it.
    form.bhValidate = validateAll;

    form.addEventListener('submit', function (event) {
      if (!validateAll()) {
        event.preventDefault();
        event.stopPropagation();
      }
    }, true);
  }

  // Lets a page run the same validation on demand (e.g. after restoring a draft).
  window.BHProfileValidation = { validateField: validateField };

  document.querySelectorAll('form[action*="account_update.php"], form[action*="change_password.php"], form[action*="save_initial_profile.php"]').forEach(wire);
})();
