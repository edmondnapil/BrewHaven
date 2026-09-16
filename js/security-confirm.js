'use strict';

/**
 * Shared "confirm this sensitive action" dialog.
 *
 * Any existing <form> — or plain <a> link, such as Logout — becomes a confirmed
 * action by adding data attributes; no form, link, endpoint or markup has to be
 * restructured:
 *
 *   data-bh-confirm          (required) message shown in the dialog
 *   data-bh-confirm-title    optional heading, defaults to "Confirm Action"
 *   data-bh-confirm-password "1" to require the logged-in user's password in
 *                            the frontmost Confirm Password modal
 *                            (forms only — a link cannot carry one)
 *   data-bh-confirm-reason   "1" to require a written reason for the action
 *                            (posted as "action_reason")
 *   data-bh-confirm-proof    "1" to require a supporting document
 *                            (posted as "action_proof"). Used only for Delete
 *                            workflows. Do not set this on approve, reject,
 *                            block, unblock, edit, role change, etc.
 *                            "optional" is supported but unused for non-delete.
 *   data-bh-confirm-role     JSON {value: label} of roles; shows a required
 *                            "New Role" dropdown on the first step
 *                            (posted as "new_role")
 *   data-bh-confirm-button   optional confirm-button label
 *   data-bh-confirm-danger   "1" to use the danger button style
 *
 * Flow: the confirmation question (with the role dropdown when asked for),
 * then "Reason for this Action" with the reason (and proof) fields. Each step
 * validates before moving on. When the action needs a password, a separate
 * "Confirm Password" modal then opens on top of everything. The password is
 * checked on the SERVER (php/verify_action_password.php) against the stored
 * hash; a wrong password keeps the modal open with "Incorrect password.". A
 * correct one returns a single-use token for this action's endpoint, the form
 * is submitted once with that token, and the endpoint consumes it through
 * bh_confirm_password() before it changes anything.
 *
 * Both modals are built from the existing .bh-modal / .bh-btn design-system
 * classes so they match every other modal in the app.
 */
(function () {
    var modal, titleEl, messageEl, okBtn, cancelBtn;
    var reasonWrap, reasonInput, reasonError, reasonCount;
    var proofWrap, proofInput, proofError, proofHint, proofLabel;
    var roleWrap, roleSelect, roleError;
    var pwModal, pwInput, pwError, pwOkBtn, pwCancelBtn;
    var pendingForm = null;
    var stages = [];
    var stageIndex = 0;
    var submitting = false;
    var verifying = false;

    // Kept in step with php/auth_helpers.php — bh_validate_action_reason()
    // and bh_proof_allowed_types() are what actually enforce these.
    var REASON_MIN = 5;
    var REASON_MAX = 500;
    var PROOF_MAX_BYTES = 5 * 1024 * 1024;
    var PROOF_ACCEPT = '.jpg,.jpeg,.png,.gif,.webp,.pdf';
    var PROOF_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];

    function build() {
        if (modal) return;
        modal = document.createElement('div');
        modal.className = 'bh-modal-backdrop';
        modal.id = 'bhConfirmActionModal';
        // Layered above the page's own modals (.bh-modal-backdrop is z-index 2000)
        // so it can confirm an action started from inside one of them.
        modal.style.zIndex = '2100';
        modal.innerHTML =
            '<div class="bh-modal">' +
                '<h3 id="bhConfirmActionTitle">Confirm Action</h3>' +
                '<p id="bhConfirmActionMessage" style="color:var(--bh-text-muted);font-size:0.85rem;"></p>' +
                '<div id="bhConfirmActionRoleWrap" class="bh-form-grid" style="display:none;grid-template-columns:1fr;">' +
                    '<div>' +
                        '<label for="bhConfirmActionRole">New Role <span style="color:var(--bh-danger);">*</span></label>' +
                        '<select id="bhConfirmActionRole"></select>' +
                        '<div id="bhConfirmActionRoleError" class="error-message"></div>' +
                    '</div>' +
                '</div>' +
                '<div id="bhConfirmActionReasonWrap" class="bh-form-grid" style="display:none;grid-template-columns:1fr;">' +
                    '<div>' +
                        '<label for="bhConfirmActionReason">Reason <span style="color:var(--bh-danger);">*</span></label>' +
                        '<textarea id="bhConfirmActionReason" rows="3" maxlength="' + REASON_MAX + '" placeholder="Explain why this action is being taken..."></textarea>' +
                        '<div style="display:flex;justify-content:space-between;gap:0.5rem;">' +
                            '<div id="bhConfirmActionReasonError" class="error-message"></div>' +
                            '<span id="bhConfirmActionReasonCount" style="font-size:0.72rem;color:var(--bh-text-muted);white-space:nowrap;">0/' + REASON_MAX + '</span>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div id="bhConfirmActionProofWrap" class="bh-form-grid" style="display:none;grid-template-columns:1fr;">' +
                    '<div>' +
                        '<label for="bhConfirmActionProof" id="bhConfirmActionProofLabel">Supporting Proof</label>' +
                        '<input type="file" id="bhConfirmActionProof" name="action_proof" accept="' + PROOF_ACCEPT + '">' +
                        '<div id="bhConfirmActionProofHint" style="font-size:0.72rem;color:var(--bh-text-muted);margin-top:2px;">JPG, PNG, GIF, WEBP or PDF. Maximum 5 MB.</div>' +
                        '<div id="bhConfirmActionProofError" class="error-message"></div>' +
                    '</div>' +
                '</div>' +
                '<div class="bh-modal-actions">' +
                    '<button type="button" class="bh-btn bh-btn-secondary" id="bhConfirmActionCancel">Cancel</button>' +
                    '<button type="button" class="bh-btn bh-btn-primary" id="bhConfirmActionOk">Confirm</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(modal);

        // The Confirm Password modal: frontmost, above the action dialog and
        // any page modal underneath it.
        pwModal = document.createElement('div');
        pwModal.className = 'bh-modal-backdrop';
        pwModal.id = 'bhConfirmPasswordModal';
        pwModal.style.zIndex = '2200';
        pwModal.innerHTML =
            '<div class="bh-modal" role="dialog" aria-modal="true" aria-labelledby="bhConfirmPasswordTitle">' +
                '<h3 id="bhConfirmPasswordTitle">Confirm Password</h3>' +
                '<p style="color:var(--bh-text-muted);font-size:0.85rem;">For security, enter your current password to perform this action.</p>' +
                '<div class="bh-form-grid" style="grid-template-columns:1fr;">' +
                    '<div>' +
                        '<label for="bhConfirmPasswordInput">Password <span style="color:var(--bh-danger);">*</span></label>' +
                        '<input type="password" id="bhConfirmPasswordInput" autocomplete="current-password" placeholder="Your account password">' +
                        '<div id="bhConfirmPasswordError" class="error-message"></div>' +
                    '</div>' +
                '</div>' +
                '<div class="bh-modal-actions">' +
                    '<button type="button" class="bh-btn bh-btn-secondary" id="bhConfirmPasswordCancel">Cancel</button>' +
                    '<button type="button" class="bh-btn bh-btn-primary" id="bhConfirmPasswordOk">Confirm</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(pwModal);

        titleEl = modal.querySelector('#bhConfirmActionTitle');
        messageEl = modal.querySelector('#bhConfirmActionMessage');
        reasonWrap = modal.querySelector('#bhConfirmActionReasonWrap');
        reasonInput = modal.querySelector('#bhConfirmActionReason');
        reasonError = modal.querySelector('#bhConfirmActionReasonError');
        reasonCount = modal.querySelector('#bhConfirmActionReasonCount');
        proofWrap = modal.querySelector('#bhConfirmActionProofWrap');
        proofInput = modal.querySelector('#bhConfirmActionProof');
        proofError = modal.querySelector('#bhConfirmActionProofError');
        proofHint = modal.querySelector('#bhConfirmActionProofHint');
        proofLabel = modal.querySelector('#bhConfirmActionProofLabel');
        roleWrap = modal.querySelector('#bhConfirmActionRoleWrap');
        roleSelect = modal.querySelector('#bhConfirmActionRole');
        roleError = modal.querySelector('#bhConfirmActionRoleError');
        okBtn = modal.querySelector('#bhConfirmActionOk');
        cancelBtn = modal.querySelector('#bhConfirmActionCancel');
        pwInput = pwModal.querySelector('#bhConfirmPasswordInput');
        pwError = pwModal.querySelector('#bhConfirmPasswordError');
        pwOkBtn = pwModal.querySelector('#bhConfirmPasswordOk');
        pwCancelBtn = pwModal.querySelector('#bhConfirmPasswordCancel');

        cancelBtn.addEventListener('click', close);
        okBtn.addEventListener('click', advance);
        modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
        roleSelect.addEventListener('change', function () { validateRole(); });
        // Live validation: the reason and the attachment are checked as they
        // are entered, so the problem is visible before the button is pressed.
        reasonInput.addEventListener('input', function () {
            reasonCount.textContent = reasonInput.value.trim().length + '/' + REASON_MAX;
            if (reasonInput.value.trim() !== '') { validateReason(true); }
        });
        reasonInput.addEventListener('blur', function () { validateReason(true); });
        proofInput.addEventListener('change', function () { validateProof(true); });

        pwCancelBtn.addEventListener('click', closePassword);
        pwOkBtn.addEventListener('click', verifyPassword);
        pwModal.addEventListener('click', function (e) { if (e.target === pwModal) closePassword(); });
        pwInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); verifyPassword(); }
        });
        pwInput.addEventListener('input', function () { setFieldError(pwInput, pwError, ''); });

        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            // Escape closes the frontmost dialog only.
            if (pwModal.classList.contains('open')) { closePassword(); }
            else if (modal.classList.contains('open')) { close(); }
        });
    }

    function setFieldError(el, box, message) {
        box.textContent = message || '';
        box.classList.toggle('show', !!message);
        if (message) { el.classList.add('bh-field-error'); }
        else { el.classList.remove('bh-field-error'); }
        return !message;
    }

    function clearError() {
        setFieldError(reasonInput, reasonError, '');
        setFieldError(proofInput, proofError, '');
        setFieldError(roleSelect, roleError, '');
    }

    // Mirrors bh_validate_action_reason() on the server.
    function validateReason(live) {
        var value = reasonInput.value.trim();
        var message = '';
        if (value === '') {
            message = live ? '' : 'A reason is required for this action.';
            if (!live) { return setFieldError(reasonInput, reasonError, message); }
        } else if (value.length < REASON_MIN) {
            message = 'Please give a reason of at least ' + REASON_MIN + ' characters.';
        } else if (value.length > REASON_MAX) {
            message = 'The reason must not exceed ' + REASON_MAX + ' characters.';
        }
        return setFieldError(reasonInput, reasonError, message);
    }

    // Mirrors bh_store_action_proof(); the server re-reads the real file type.
    function validateProof(live) {
        var required = proofInput.getAttribute('data-required') === '1';
        var file = proofInput.files && proofInput.files[0];
        if (!file) {
            if (required && !live) {
                return setFieldError(proofInput, proofError, 'A supporting proof document is required for this action.');
            }
            return setFieldError(proofInput, proofError, '');
        }
        var name = file.name || '';
        var dot = name.lastIndexOf('.');
        var extension = dot > -1 ? name.substring(dot + 1).toLowerCase() : '';
        if (PROOF_EXTENSIONS.indexOf(extension) === -1) {
            return setFieldError(proofInput, proofError, 'Only JPG, PNG, GIF, WEBP or PDF files may be attached as proof.');
        }
        if (file.size <= 0) {
            return setFieldError(proofInput, proofError, 'The proof file is empty.');
        }
        if (file.size > PROOF_MAX_BYTES) {
            return setFieldError(proofInput, proofError, 'The proof file is too large. Maximum size is 5 MB.');
        }
        return setFieldError(proofInput, proofError, '');
    }

    // {value: label} from data-bh-confirm-role, or null when the action has none.
    function roleOptions(target) {
        if (target.tagName === 'A') { return null; }
        var raw = target.getAttribute('data-bh-confirm-role');
        if (!raw) { return null; }
        try {
            var parsed = JSON.parse(raw);
            return (parsed && typeof parsed === 'object' && Object.keys(parsed).length) ? parsed : null;
        } catch (err) {
            return null;
        }
    }

    function fillRoleSelect(options) {
        roleSelect.innerHTML = '';
        roleSelect.appendChild(new Option('Select Role', ''));
        Object.keys(options).forEach(function (value) {
            roleSelect.appendChild(new Option(options[value], value));
        });
        roleSelect.value = '';
    }

    function validateRole() {
        var options = pendingForm ? roleOptions(pendingForm) : null;
        if (!options) { return true; }
        var value = roleSelect.value;
        var message = (value === '' || !Object.prototype.hasOwnProperty.call(options, value))
            ? 'Please select a new role before continuing.'
            : '';
        return setFieldError(roleSelect, roleError, message);
    }

    // A link has no request body to carry a token in, so link-triggered
    // actions (Logout) are confirmation-only.
    function needsPassword(target) {
        if (target.tagName === 'A') { return false; }
        return target.getAttribute('data-bh-confirm-password') === '1';
    }

    function needsReason(target) {
        if (target.tagName === 'A') { return false; }
        return target.getAttribute('data-bh-confirm-reason') === '1';
    }

    // Proof upload is opt-in via data-bh-confirm-proof. Only Delete workflows
    // set this; reason-only actions must not show a file field.
    function proofMode(target) {
        if (target.tagName === 'A') { return ''; }
        var mode = target.getAttribute('data-bh-confirm-proof');
        if (mode === '1' || mode === 'optional') { return mode; }
        return '';
    }

    // The dialog stages this action needs, in order. The password is not a
    // stage here: it gets its own frontmost modal after the last stage.
    function buildStages(target) {
        var list = ['confirm'];
        if (needsReason(target) || proofMode(target) !== '') { list.push('reason'); }
        return list;
    }

    function currentStage() {
        return stages[stageIndex] || 'confirm';
    }

    function renderStage(form) {
        var danger = form.getAttribute('data-bh-confirm-danger') === '1';
        var stage = currentStage();
        var isLast = (stageIndex === stages.length - 1);
        var finalLabel = needsPassword(form)
            ? 'Yes, Continue'
            : (form.getAttribute('data-bh-confirm-button') || 'Confirm');

        reasonWrap.style.display = 'none';
        proofWrap.style.display = 'none';
        roleWrap.style.display = 'none';

        if (stage === 'confirm') {
            titleEl.textContent = form.getAttribute('data-bh-confirm-title') || 'Confirm Action';
            messageEl.textContent = form.getAttribute('data-bh-confirm') || 'Please confirm this action.';
            if (roleOptions(form)) { roleWrap.style.display = ''; }
            okBtn.textContent = isLast ? finalLabel : 'Yes, Continue';
        } else {
            var mode = proofMode(form);
            titleEl.textContent = 'Reason for this Action';
            if (mode === '1') {
                messageEl.textContent = 'This action is recorded in the audit log. Give a reason and attach the supporting document.';
            } else if (mode === 'optional') {
                messageEl.textContent = 'This action is recorded in the audit log. Please give a reason, and attach a supporting document if you have one.';
            } else {
                messageEl.textContent = 'This action is recorded in the audit log. Please give a reason.';
            }
            reasonWrap.style.display = '';
            if (mode !== '') {
                proofWrap.style.display = '';
                proofInput.setAttribute('data-required', mode === '1' ? '1' : '0');
                proofLabel.innerHTML = mode === '1'
                    ? 'Supporting Proof <span style="color:var(--bh-danger);">*</span>'
                    : 'Supporting Proof <span style="color:var(--bh-text-muted);">(optional)</span>';
                proofHint.textContent = 'JPG, PNG, GIF, WEBP or PDF. Maximum 5 MB.';
            }
            okBtn.textContent = isLast ? finalLabel : 'Continue';
        }
        okBtn.className = 'bh-btn ' + (danger ? 'bh-btn-danger' : 'bh-btn-primary');
        okBtn.disabled = false;
    }

    function open(form) {
        build();
        pendingForm = form;
        stages = buildStages(form);
        stageIndex = 0;
        submitting = false;
        verifying = false;
        reasonInput.value = '';
        reasonCount.textContent = '0/' + REASON_MAX;
        proofInput.value = '';
        proofInput.removeAttribute('data-required');
        var options = roleOptions(form);
        if (options) { fillRoleSelect(options); } else { roleSelect.innerHTML = ''; }
        clearError();
        renderStage(form);

        // Close any open row action menu so it is not left hanging behind the dialog.
        if (typeof window.bhCloseAllMenus === 'function') { window.bhCloseAllMenus(); }

        modal.classList.add('open');
        if (options) { roleSelect.focus(); } else { okBtn.focus(); }
    }

    function close() {
        if (!modal) return;
        closePassword();
        modal.classList.remove('open');
        reasonInput.value = '';
        proofInput.value = '';
        clearError();
        pendingForm = null;
        stages = [];
        stageIndex = 0;
        submitting = false;
        verifying = false;
    }

    // ---- Confirm Password modal --------------------------------------------

    function openPassword() {
        pwInput.value = '';
        setFieldError(pwInput, pwError, '');
        pwOkBtn.disabled = false;
        pwOkBtn.textContent = 'Confirm';
        var danger = pendingForm && pendingForm.getAttribute('data-bh-confirm-danger') === '1';
        pwOkBtn.className = 'bh-btn ' + (danger ? 'bh-btn-danger' : 'bh-btn-primary');
        pwModal.classList.add('open');
        pwInput.focus();
    }

    function closePassword() {
        if (!pwModal || verifying || submitting) return;
        pwModal.classList.remove('open');
        pwInput.value = '';
        setFieldError(pwInput, pwError, '');
    }

    function endpointName(form) {
        var action = form.getAttribute('action') || '';
        return action.split('?')[0].split('/').pop();
    }

    function verifyUrl(form) {
        // Resolved next to the action endpoint, so it works from any page.
        // Read the attribute, not form.action: the account forms carry an
        // <input name="action">, which shadows the form's .action property.
        var endpoint = new URL(form.getAttribute('action') || '', document.baseURI);
        return new URL('verify_action_password.php', endpoint).href;
    }

    function targetId(form) {
        var field = form.querySelector('[name="id_number"], [name="admin_id_number"]');
        return field ? field.value : '';
    }

    function verifyPassword() {
        var form = pendingForm;
        if (!form || verifying || submitting) return;
        if (pwInput.value === '') {
            setFieldError(pwInput, pwError, 'Please enter your password.');
            pwInput.focus();
            return;
        }

        verifying = true;
        pwOkBtn.disabled = true;
        pwOkBtn.textContent = 'Verifying...';

        function fail(message) {
            verifying = false;
            pwOkBtn.disabled = false;
            pwOkBtn.textContent = 'Confirm';
            setFieldError(pwInput, pwError, message);
        }

        var request;
        try {
            var body = new FormData();
            body.append('password', pwInput.value);
            body.append('endpoint', endpointName(form));
            body.append('target_id', targetId(form));
            request = fetch(verifyUrl(form), {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
        } catch (err) {
            fail('Password confirmation could not start. Please refresh the page and try again.');
            return;
        }

        request
            .then(function (response) {
                var contentType = (response.headers.get('content-type') || '').toLowerCase();
                if (contentType.indexOf('application/json') === -1) {
                    // Apache / PHP returned HTML (403 page, login redirect, etc.).
                    // Do not treat this as a wrong-password failure.
                    return {
                        success: false,
                        code: 'bad_response',
                        httpStatus: response.status,
                        message: response.status === 403
                            ? 'Password confirmation is blocked by the server configuration. Please contact support.'
                            : 'Password confirmation is temporarily unavailable. Please refresh the page and try again.'
                    };
                }
                return response.json().catch(function () {
                    return {
                        success: false,
                        code: 'bad_response',
                        httpStatus: response.status,
                        message: 'Password confirmation returned an invalid response. Please refresh the page and try again.'
                    };
                }).then(function (data) {
                    data.httpStatus = response.status;
                    return data;
                });
            })
            .then(function (data) {
                verifying = false;
                if (data && data.success && data.token) {
                    pwInput.value = '';
                    submitAction(form, data.token);
                    return;
                }
                // Session expired / signed out: follow the server's redirect.
                if (data && (data.timeout || data.httpStatus === 401) && data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                if (data && data.code === 'confirm_password_incorrect') {
                    fail(data.message || 'Incorrect password.');
                } else {
                    fail((data && data.message) ||
                        'Password confirmation failed. Please try again.');
                }
                pwInput.select();
                pwInput.focus();
            })
            .catch(function () {
                fail('Password confirmation could not reach the server. Please check your connection and try again.');
            });
    }

    // ---- Submitting the confirmed action -------------------------------------

    // Copies a value into the form as a hidden field, creating it on first use.
    function setHidden(form, name, value) {
        var field = form.querySelector('input[name="' + name + '"][data-bh-transient="1"]');
        if (!field) {
            field = document.createElement('input');
            field.type = 'hidden';
            field.name = name;
            field.setAttribute('data-bh-transient', '1');
            form.appendChild(field);
        }
        field.value = value;
        return field;
    }

    function clearTransient(form) {
        var fields = form.querySelectorAll('[data-bh-transient="1"]');
        for (var i = 0; i < fields.length; i++) { fields[i].remove(); }
    }

    function advance() {
        var form = pendingForm;
        if (!form || submitting || verifying) return;

        var stage = currentStage();

        // Each stage validates before it hands over to the next one.
        if (stage === 'confirm' && roleOptions(form)) {
            if (!validateRole()) { roleSelect.focus(); return; }
        }
        if (stage === 'reason') {
            var reasonOk = needsReason(form) ? validateReason(false) : true;
            var proofOk = (proofMode(form) === '') ? true : validateProof(false);
            if (!reasonOk) { reasonInput.focus(); return; }
            if (!proofOk) { proofInput.focus(); return; }
        }

        // More stages to walk through before the action runs.
        if (stageIndex < stages.length - 1) {
            stageIndex++;
            renderStage(form);
            if (currentStage() === 'reason') { reasonInput.focus(); }
            return;
        }

        // A confirmed link just follows itself, so the page's own destination
        // (e.g. the existing logout.php) runs exactly as it always has.
        if (form.tagName === 'A') {
            submitting = true;
            okBtn.disabled = true;
            okBtn.textContent = 'Please wait...';
            var href = form.href;
            close();
            window.location.href = href;
            return;
        }

        // Everything is valid: now, and only now, ask for the password.
        if (needsPassword(form)) {
            openPassword();
            return;
        }
        submitAction(form, null);
    }

    function submitAction(form, confirmToken) {
        if (submitting) return;

        if (needsReason(form) || proofMode(form) !== '') {
            setHidden(form, 'action_reason', reasonInput.value.trim());
        }
        if (roleOptions(form)) {
            setHidden(form, 'new_role', roleSelect.value);
        }
        if (confirmToken) {
            setHidden(form, 'confirm_token', confirmToken);
        }
        // A file cannot be copied into a hidden field, so the file input itself
        // is moved into the form for the submit and taken straight back out
        // again. Moving the node keeps its FileList intact.
        var movedProof = false;
        if (proofMode(form) !== '' && proofInput.files && proofInput.files.length > 0) {
            form.setAttribute('enctype', 'multipart/form-data');
            form.setAttribute('method', 'post');
            proofInput.setAttribute('data-bh-transient', '1');
            form.appendChild(proofInput);
            movedProof = true;
        }

        // Guard against a double click producing two accounts / two updates.
        // The token is single-use on the server as well.
        submitting = true;
        okBtn.disabled = true;
        okBtn.textContent = 'Please wait...';
        if (pwOkBtn) {
            pwOkBtn.disabled = true;
            pwOkBtn.textContent = 'Please wait...';
        }

        try {
            // Re-submit through the normal submit path so any page-specific
            // submit handler (e.g. the AJAX create-account validator) still runs.
            form.setAttribute('data-bh-confirmed', '1');
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                var evt = new Event('submit', { bubbles: true, cancelable: true });
                if (form.dispatchEvent(evt)) { form.submit(); }
            }
        } finally {
            // Form submission builds its entry list synchronously, so by here
            // the attachment and the token are already on their way and neither
            // needs to linger in the page. The finally block makes sure the
            // borrowed file input is returned to the dialog even if a
            // page-specific submit handler throws.
            if (movedProof) {
                proofInput.removeAttribute('data-bh-transient');
                proofWrap.querySelector('div').insertBefore(proofInput, proofHint);
            }
            clearTransient(form);
            form.removeAttribute('data-bh-confirmed');
            submitting = false;
            close();
        }
    }

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || !form.hasAttribute || !form.hasAttribute('data-bh-confirm')) return;
        if (form.getAttribute('data-bh-confirmed') === '1') return;
        e.preventDefault();
        e.stopPropagation();
        // A form with its own client-side validation (e.g. the create-account
        // wizard) can expose it here so the user is not asked for a password
        // before their input is known to be valid.
        if (typeof form.bhValidate === 'function' && !form.bhValidate()) return;
        open(form);
    }, true);

    // Links opt in the same way. Used by Logout, which is a plain <a> and stays
    // one — the confirmation only gates the click, logout.php itself is
    // untouched and still does the session teardown and redirect.
    document.addEventListener('click', function (e) {
        if (!e.target || !e.target.closest) return;
        var link = e.target.closest('a[data-bh-confirm]');
        if (!link) return;
        // Let modified clicks (new tab/window, download) behave normally.
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
        e.preventDefault();
        e.stopPropagation();
        open(link);
    }, true);
})();

// Automatically load inactivity tracker on all pages including security-confirm.js
(function() {
    if (typeof window.BHInactivityTracker !== 'undefined') return;
    var script = document.createElement('script');
    var isHtmlDir = window.location.pathname.replace(/\\/g, '/').includes('/html/');
    script.src = isHtmlDir ? '../js/inactivity-tracker.js' : 'js/inactivity-tracker.js';
    script.async = true;
    document.head.appendChild(script);
})();
