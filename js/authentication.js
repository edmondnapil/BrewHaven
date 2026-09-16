// Improved Forgot Password Flow: ID Number -> OTP Verification (60s Timer) -> Security Questions (Registration Dropdowns) -> Reset Password
document.addEventListener('DOMContentLoaded', function () {
    const identifierPhase = document.getElementById('identifierPhase');
    const otpPhase        = document.getElementById('otpPhase');
    const questionsPhase  = document.getElementById('questionsPhase');

    const idInput         = document.getElementById('fp_id_number');
    const idError         = document.getElementById('identifierError');
    const findAccountBtn  = document.getElementById('findAccountBtnId');

    const otpInput        = document.getElementById('fp_otp_code');
    const otpError        = document.getElementById('otpError');
    const verifyOtpBtn    = document.getElementById('verifyOtpBtnId');
    const resendOtpBtn    = document.getElementById('resendOtpBtnId');
    const resendStatusText= document.getElementById('resendStatusText');
    const backToIdBtn     = document.getElementById('backToIdBtnId');

    const formTitle       = document.getElementById('authFormTitle');
    const authForm        = document.getElementById('authForm');
    const verifyAnswersBtn= document.getElementById('verifyAnswersBtnId');

    let countdownInterval = null;

    function showError(el, msg) {
        if (el) {
            el.textContent = msg;
            el.classList.add('show');
        }
    }

    function clearError(el) {
        if (el) {
            el.textContent = '';
            el.classList.remove('show');
        }
    }

    // ---- 60-Second Resend Countdown Timer ----
    function startResendTimer(seconds) {
        if (countdownInterval) clearInterval(countdownInterval);
        if (!resendOtpBtn) return;

        let remaining = seconds || 60;
        resendOtpBtn.classList.add('disabled');
        resendOtpBtn.style.pointerEvents = 'none';
        resendOtpBtn.textContent = 'Resend Code in ' + remaining + 's';

        countdownInterval = setInterval(function () {
            remaining--;
            if (remaining > 0) {
                resendOtpBtn.textContent = 'Resend Code in ' + remaining + 's';
            } else {
                clearInterval(countdownInterval);
                countdownInterval = null;
                resendOtpBtn.classList.remove('disabled');
                resendOtpBtn.style.pointerEvents = '';
                resendOtpBtn.textContent = 'Resend Code';
            }
        }, 1000);
    }

    // ---- Phase 1: Find Account by ID Number ----
    function findAccount() {
        clearError(idError);
        const idVal = idInput.value.trim();
        if (!idVal) {
            showError(idError, 'Please enter your ID Number.');
            return;
        }

        findAccountBtn.disabled = true;
        findAccountBtn.textContent = 'Searching...';

        fetch('authentication.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'find_account_by_id', id_number: idVal }).toString()
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.success) {
                showError(idError, (data && data.message) || 'No account was found for this ID Number. Please check your ID Number and try again.');
                return;
            }

            document.getElementById('otpMaskedEmail').textContent = data.masked_email || 'your email';

            const userInfoHeader = document.getElementById('userInfoHeader');
            const hdrMaskedId = document.getElementById('hdrMaskedId');
            const hdrMaskedUsername = document.getElementById('hdrMaskedUsername');

            if (hdrMaskedId && data.masked_id) { hdrMaskedId.textContent = data.masked_id; }
            if (hdrMaskedUsername && data.masked_username) { hdrMaskedUsername.textContent = data.masked_username; }
            if (userInfoHeader) { userInfoHeader.style.display = 'flex'; }

            identifierPhase.style.display = 'none';
            otpPhase.style.display = '';
            if (formTitle) { formTitle.textContent = 'Enter Verification Code'; }
            if (authForm) { authForm.classList.remove('phase-2', 'phase-3'); }
            if (otpInput) { otpInput.value = ''; otpInput.focus(); }

            startResendTimer(data.cooldown || 60);
        })
        .catch(err => {
            console.error('Find account error', err);
            showError(idError, 'An error occurred. Please try again.');
        })
        .finally(() => {
            findAccountBtn.disabled = false;
            findAccountBtn.textContent = 'Find Account';
        });
    }

    if (findAccountBtn) {
        findAccountBtn.addEventListener('click', findAccount);
    }
    if (idInput) {
        idInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); findAccount(); }
        });
        idInput.addEventListener('input', function () { clearError(idError); });
    }

    const cancelIdentifierBtn = document.getElementById('cancelIdentifierBtnId');
    if (cancelIdentifierBtn) {
        cancelIdentifierBtn.addEventListener('click', function () {
            window.location.href = 'login.php';
        });
    }

    // ---- Phase 2: Verify OTP & Resend ----
    function verifyOtp() {
        clearError(otpError);
        const otpVal = otpInput.value.trim();
        if (!otpVal) {
            showError(otpError, 'Please enter the verification code.');
            return;
        }
        if (otpVal.length < 6) {
            showError(otpError, 'Verification code must be 6 digits.');
            return;
        }

        verifyOtpBtn.disabled = true;
        verifyOtpBtn.textContent = 'Verifying...';

        fetch('authentication.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'verify_otp', otp_code: otpVal }).toString()
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.success) {
                showError(otpError, (data && data.message) || 'Invalid verification code. Please try again.');
                if (data && (data.expired_session || data.expired_otp)) {
                    verifyOtpBtn.disabled = true;
                }
                return;
            }

            // Auto-select user's saved questions in the registration-style dropdowns if provided
            const q = data.questions || {};
            if (q.q1) { selectMatchingOption(document.getElementById('fp_question1'), q.q1); }
            if (q.q2) { selectMatchingOption(document.getElementById('fp_question2'), q.q2); }
            if (q.q3) { selectMatchingOption(document.getElementById('fp_question3'), q.q3); }

            otpPhase.style.display = 'none';
            questionsPhase.style.display = '';
            if (formTitle) { formTitle.textContent = 'Security Questions'; }
            if (authForm) { authForm.classList.add('phase-3'); }
        })
        .catch(err => {
            console.error('Verify OTP error', err);
            showError(otpError, 'An error occurred. Please try again.');
        })
        .finally(() => {
            verifyOtpBtn.disabled = false;
            verifyOtpBtn.textContent = 'Verify Code';
        });
    }

    function toggleAnswerVisibility(selectEl, wrapId) {
        const wrap = document.getElementById(wrapId);
        if (!wrap) return;
        if (selectEl && selectEl.value) {
            wrap.style.display = 'flex';
        } else {
            wrap.style.display = 'none';
        }
    }

    function selectMatchingOption(selectEl, textVal) {
        if (!selectEl || !textVal) return;
        for (let i = 0; i < selectEl.options.length; i++) {
            if (selectEl.options[i].value.toLowerCase() === textVal.toLowerCase()) {
                selectEl.selectedIndex = i;
                break;
            }
        }
        const num = selectEl.id.replace('fp_question', '');
        toggleAnswerVisibility(selectEl, 'answerWrap' + num);
    }

    if (verifyOtpBtn) {
        verifyOtpBtn.addEventListener('click', verifyOtp);
    }
    if (otpInput) {
        otpInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); verifyOtp(); }
        });
        otpInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
            clearError(otpError);
        });
    }

    if (backToIdBtn) {
        backToIdBtn.addEventListener('click', function () {
            if (countdownInterval) clearInterval(countdownInterval);
            otpPhase.style.display = 'none';
            identifierPhase.style.display = '';
            const userInfoHeader = document.getElementById('userInfoHeader');
            if (userInfoHeader) { userInfoHeader.style.display = 'none'; }
            if (formTitle) { formTitle.textContent = 'Enter ID Number'; }
            if (authForm) { authForm.classList.remove('phase-2', 'phase-3'); }
        });
    }

    if (resendOtpBtn) {
        resendOtpBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (resendOtpBtn.classList.contains('disabled')) return;

            clearError(otpError);
            resendOtpBtn.classList.add('disabled');
            resendOtpBtn.style.pointerEvents = 'none';

            fetch('authentication.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'resend_otp' }).toString()
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success) {
                    if (resendStatusText) {
                        resendStatusText.textContent = 'A new verification code has been sent.';
                        setTimeout(() => { resendStatusText.textContent = ''; }, 5000);
                    }
                    verifyOtpBtn.disabled = false;
                    startResendTimer(data.cooldown || 60);
                } else {
                    showError(otpError, (data && data.message) || 'Unable to send verification code. Please try again.');
                    if (data && data.cooldown_left) {
                        startResendTimer(data.cooldown_left);
                    } else {
                        resendOtpBtn.classList.remove('disabled');
                        resendOtpBtn.style.pointerEvents = '';
                        resendOtpBtn.textContent = 'Resend Code';
                    }
                }
            })
            .catch(() => {
                showError(otpError, 'We were unable to send the verification code. Please try again later.');
                resendOtpBtn.classList.remove('disabled');
                resendOtpBtn.style.pointerEvents = '';
                resendOtpBtn.textContent = 'Resend Code';
            });
        });
    }

    // ---- Phase 3: Verify Security Question Answers ----
    const answerInputs = ['security_answer1_id', 'security_answer2_id', 'security_answer3_id'];
    answerInputs.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.addEventListener('input', function () {
                const num = id.replace('security_answer', '').replace('_id', '');
                clearError(document.getElementById('ans' + num + 'Error'));
                clearError(document.getElementById('sqErrorId'));
            });
        }
    });

    ['fp_question1', 'fp_question2', 'fp_question3'].forEach((id, idx) => {
        const select = document.getElementById(id);
        const wrapId = 'answerWrap' + (idx + 1);
        if (select) {
            select.addEventListener('change', function () {
                toggleAnswerVisibility(this, wrapId);
                clearError(document.getElementById('q' + (idx + 1) + 'Error'));
                clearError(document.getElementById('sqErrorId'));
            });
            // Initial sync based on value
            toggleAnswerVisibility(select, wrapId);
        }
    });

    if (verifyAnswersBtn) {
        verifyAnswersBtn.addEventListener('click', function () {
            const q1 = document.getElementById('fp_question1')?.value || '';
            const q2 = document.getElementById('fp_question2')?.value || '';
            const q3 = document.getElementById('fp_question3')?.value || '';

            const answer1 = document.getElementById('security_answer1_id')?.value.trim() || '';
            const answer2 = document.getElementById('security_answer2_id')?.value.trim() || '';
            const answer3 = document.getElementById('security_answer3_id')?.value.trim() || '';

            ['q1Error', 'q2Error', 'q3Error', 'ans1Error', 'ans2Error', 'ans3Error', 'sqErrorId'].forEach(id => {
                clearError(document.getElementById(id));
            });

            if (!q1 && !q2 && !q3) {
                showError(document.getElementById('sqErrorId'), 'Please select at least one security question.');
                return;
            }
            if (!answer1 && !answer2 && !answer3) {
                showError(document.getElementById('sqErrorId'), 'Please enter at least one answer.');
                return;
            }

            const formData = new URLSearchParams();
            formData.append('action', 'verify_security_answers');
            formData.append('q1', q1);
            formData.append('q2', q2);
            formData.append('q3', q3);
            formData.append('ans1', answer1);
            formData.append('ans2', answer2);
            formData.append('ans3', answer3);

            verifyAnswersBtn.disabled = true;
            fetch('authentication.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.expired_session) {
                    questionsPhase.style.display = 'none';
                    identifierPhase.style.display = '';
                    if (formTitle) { formTitle.textContent = 'Forgot Password'; }
                    if (authForm) { authForm.classList.remove('phase-2', 'phase-3'); }
                    showError(idError, 'Your session has expired. Please find your account again.');
                    return;
                }

                const applyStatuses = (statuses) => {
                    const map = { q1: 'ans1Error', q2: 'ans2Error', q3: 'ans3Error' };
                    Object.keys(map).forEach(key => {
                        const el = document.getElementById(map[key]);
                        if (!el || !statuses) return;
                        const status = statuses[key];
                        if (status === 'empty') {
                            el.textContent = 'This field is empty';
                            el.style.color = '#ff4757';
                            el.style.fontSize = '13px';
                            el.classList.add('show');
                        } else if (status === 'incorrect') {
                            el.textContent = 'Incorrect question or answer';
                            el.style.color = '#ff4757';
                            el.style.fontSize = '13px';
                            el.classList.add('show');
                        } else if (status === 'correct') {
                            el.textContent = 'Correct';
                            el.style.color = '#2ed573';
                            el.style.fontSize = '13px';
                            el.classList.add('show');
                        }
                    });
                };

                if (data && data.success) {
                    applyStatuses(data.fieldStatuses);
                    setTimeout(() => {
                        window.location.href = 'forgot-password.php';
                    }, 800);
                } else {
                    applyStatuses(data && data.fieldStatuses);
                    showError(document.getElementById('sqErrorId'), (data && data.message) || 'Verification failed.');
                }
            })
            .catch(err => {
                console.error('Verify error', err);
                showError(document.getElementById('sqErrorId'), 'An error occurred. Please try again.');
            })
            .finally(() => { verifyAnswersBtn.disabled = false; });
        });
    }

    const cancelQuestionsBtn = document.getElementById('cancelQuestionsBtnId');
    if (cancelQuestionsBtn) {
        cancelQuestionsBtn.addEventListener('click', function () {
            window.location.href = 'login.php';
        });
    }
});
