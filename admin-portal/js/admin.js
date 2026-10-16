// Admin Portal — client-side behaviour

document.addEventListener('DOMContentLoaded', function () {
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let toastTimer = 0;

  function showToast(message, type) {
    let toast = document.getElementById('adminToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'adminToast';
      toast.className = 'admin-toast';
      toast.setAttribute('role', 'status');
      toast.setAttribute('aria-live', 'polite');
      toast.innerHTML = '<span class="toast-icon" aria-hidden="true"></span><span class="toast-message"></span><button type="button" class="toast-close" aria-label="Dismiss notification">&times;</button><span class="toast-progress" aria-hidden="true"></span>';
      document.body.appendChild(toast);
      toast.querySelector('.toast-close').addEventListener('click', function () { dismissToast(toast); });
    }
    window.clearTimeout(toastTimer);
    toast.className = 'admin-toast ' + (type === 'error' ? 'error' : 'success');
    toast.querySelector('.toast-icon').textContent = type === 'error' ? '!' : '✓';
    toast.querySelector('.toast-message').textContent = message;
    toast.classList.add('visible');
    toast.style.setProperty('--toast-duration', '4s');
    toastTimer = window.setTimeout(function () { dismissToast(toast); }, 4000);
  }
  function dismissToast(toast) {
    window.clearTimeout(toastTimer);
    toast.classList.remove('visible');
  }
  window.showAdminToast = showToast;

  /* ---------- Sidebar account dropdown ---------- */
  const accountMenu = document.getElementById('accountMenu');
  const accountToggle = document.getElementById('accountMenuToggle');
  const accountPopover = document.getElementById('accountPopover');
  if (accountMenu && accountToggle && accountPopover) {
    let accountCloseTimer = 0;
    function closeAccountMenu(returnFocus) {
      if (accountPopover.hidden) return;
      accountPopover.classList.remove('open');
      accountToggle.setAttribute('aria-expanded', 'false');
      window.clearTimeout(accountCloseTimer);
      accountCloseTimer = window.setTimeout(function () { accountPopover.hidden = true; }, reduceMotion ? 0 : 180);
      if (returnFocus) accountToggle.focus();
    }
    accountToggle.addEventListener('click', function () {
      const opening = accountToggle.getAttribute('aria-expanded') !== 'true';
      window.clearTimeout(accountCloseTimer);
      if (opening) {
        accountPopover.hidden = false;
        accountToggle.setAttribute('aria-expanded', 'true');
        requestAnimationFrame(function () { accountPopover.classList.add('open'); });
        accountPopover.querySelector('a').focus();
      } else {
        closeAccountMenu(false);
      }
    });
    accountPopover.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () { closeAccountMenu(false); });
    });
    document.addEventListener('click', function (event) {
      if (!accountMenu.contains(event.target)) closeAccountMenu(false);
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeAccountMenu(true);
    });
  }

  /* ---------- Login-page progressive enhancements ---------- */
  const loginPasswordToggle = document.querySelector('[data-login-password-toggle]');
  const loginPassword = document.getElementById('password');
  const loginForm = document.querySelector('[data-login-form]');
  if (loginPasswordToggle && loginPassword) {
    loginPasswordToggle.addEventListener('click', function () {
      const visible = loginPassword.type === 'text';
      loginPassword.type = visible ? 'password' : 'text';
      loginPasswordToggle.classList.toggle('is-visible', !visible);
      loginPasswordToggle.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
    });
  }
  if (loginForm) {
    loginForm.addEventListener('submit', function () {
      const submit = loginForm.querySelector('.login-submit');
      if (!submit || submit.disabled) return;
      submit.classList.add('loading');
      submit.disabled = true;
    });
  }

  /* ---------- OTP expiry, 6-digit boxes, and resend cooldown ---------- */
  function initializeOtpForm(otpForm) {
    const codeInput = otpForm.querySelector('.otp-code');
    const digitInputs = Array.from(otpForm.querySelectorAll('[data-otp-digit]'));
    const countdown = otpForm.querySelector('[data-otp-countdown]');
    const expiredNote = otpForm.querySelector('[data-otp-expired-note]');
    const submit = otpForm.querySelector('[data-otp-submit]');
    const banner = otpForm.querySelector('[data-otp-banner]');
    const statusText = otpForm.querySelector('[data-otp-status-text]');
    const resendForm = otpForm.parentElement ? otpForm.parentElement.querySelector('.otp-resend') : null;
    const resendButton = otpForm.parentElement ? otpForm.parentElement.querySelector('[data-otp-resend], [data-resend-trigger]') : null;
    const resendCountdown = otpForm.parentElement ? otpForm.parentElement.querySelector('[data-resend-countdown]') : null;
    const expiresAt = Number(otpForm.dataset.otpExpires);
    const resendAt = resendCountdown ? Number(resendCountdown.dataset.resendAt) : 0;

    function formatTime(seconds) {
      return Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0');
    }

    function syncDigitBoxesToHidden() {
      if (!codeInput) return;
      const combined = digitInputs.map(function (inp) { return inp.value; }).join('');
      codeInput.value = combined;
      if (combined.length === 6) {
        digitInputs.forEach(function (inp) { inp.classList.remove('is-invalid'); });
        otpForm.classList.remove('otp-invalid');
      }
    }

    function updateTimers() {
      const now = Math.floor(Date.now() / 1000);
      const remaining = Math.max(0, expiresAt - now);
      if (countdown) countdown.textContent = formatTime(remaining);
      otpForm.classList.toggle('near-expiry', remaining > 0 && remaining <= 10);

      if (remaining > 0) {
        if (banner) {
          banner.classList.add('is-active');
          banner.classList.remove('is-expired');
        }
        if (statusText) statusText.textContent = 'Code is active';
        digitInputs.forEach(function (inp) { inp.disabled = false; });
        if (codeInput) codeInput.disabled = false;
        if (submit) submit.disabled = false;
        if (expiredNote) expiredNote.hidden = true;
      } else {
        if (banner) {
          banner.classList.remove('is-active');
          banner.classList.add('is-expired');
        }
        if (statusText) statusText.textContent = 'Code expired. Request a new one.';
        digitInputs.forEach(function (inp) { inp.disabled = true; });
        if (codeInput) codeInput.disabled = true;
        if (submit) submit.disabled = true;
        if (expiredNote) expiredNote.hidden = false;
      }

      if (resendButton && resendCountdown) {
        const cooldown = Math.max(0, resendAt - now);
        resendButton.disabled = cooldown > 0;
        resendCountdown.textContent = cooldown > 0
          ? 'Resend available in ' + formatTime(cooldown)
          : 'You can request a new code.';
      }
    }

    // 6-digit box handlers
    if (digitInputs.length > 0) {
      digitInputs.forEach(function (input, idx) {
        input.addEventListener('input', function () {
          const val = input.value.replace(/\D/g, '');
          input.value = val ? val[val.length - 1] : '';
          syncDigitBoxesToHidden();
          if (input.value) {
            input.classList.remove('digit-pop');
            void input.offsetWidth;
            input.classList.add('digit-pop');
            if (idx < digitInputs.length - 1) {
              digitInputs[idx + 1].focus();
              digitInputs[idx + 1].select();
            }
          }
        });

        input.addEventListener('keydown', function (e) {
          if (e.key === 'Backspace') {
            if (!input.value && idx > 0) {
              digitInputs[idx - 1].focus();
              digitInputs[idx - 1].select();
            } else {
              input.value = '';
              syncDigitBoxesToHidden();
            }
          } else if (e.key === 'ArrowLeft' && idx > 0) {
            digitInputs[idx - 1].focus();
          } else if (e.key === 'ArrowRight' && idx < digitInputs.length - 1) {
            digitInputs[idx + 1].focus();
          }
        });

        input.addEventListener('paste', function (e) {
          e.preventDefault();
          const pasteData = (e.clipboardData || window.clipboardData).getData('text');
          const digits = pasteData.replace(/\D/g, '').slice(0, 6);
          if (digits) {
            digits.split('').forEach(function (d, i) {
              if (digitInputs[i]) digitInputs[i].value = d;
            });
            syncDigitBoxesToHidden();
            const targetIndex = Math.min(digits.length, digitInputs.length - 1);
            digitInputs[targetIndex].focus();
          }
        });
      });
    }

    if (codeInput && digitInputs.length === 0) {
      let lastLength = codeInput.value.length;
      codeInput.addEventListener('input', function () {
        codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 6);
        if (codeInput.value.length > lastLength) {
          codeInput.classList.remove('digit-pop');
          void codeInput.offsetWidth;
          codeInput.classList.add('digit-pop');
        }
        lastLength = codeInput.value.length;
        codeInput.classList.remove('is-invalid');
        otpForm.classList.remove('otp-invalid');
      });
    }

    // Password strength & live rules checklist
    const newPassInput = otpForm.querySelector('#new_password');
    if (newPassInput) {
      newPassInput.addEventListener('input', function () {
        const value = this.value;
        const hasLength = value.length >= 8;
        const hasCase = /[a-z]/.test(value) && /[A-Z]/.test(value);
        const hasNumber = /\d/.test(value);
        const hasSpecial = /[^a-zA-Z0-9]/.test(value);

        let score = 0;
        if (hasLength) score++;
        if (hasCase) score++;
        if (hasNumber) score++;
        if (hasSpecial) score++;

        const strengthEl = otpForm.querySelector('[data-password-strength]');
        if (strengthEl) {
          strengthEl.className = 'password-strength-bars' + (value ? ' strength-' + score : '');
        }

        otpForm.querySelectorAll('[data-rule]').forEach(function (rule) {
          let met = false;
          if (rule.dataset.rule === 'length') met = hasLength;
          else if (rule.dataset.rule === 'case') met = hasCase;
          else if (rule.dataset.rule === 'number') met = hasNumber;
          else if (rule.dataset.rule === 'special') met = hasSpecial;
          rule.classList.toggle('met', met);
        });
      });
    }

    // Password show / hide text and icon toggles
    otpForm.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const targetId = btn.dataset.passwordToggle;
        const input = otpForm.querySelector('#' + targetId);
        if (!input) return;
        const isCurrentlyPassword = input.type === 'password';
        input.type = isCurrentlyPassword ? 'text' : 'password';
        input.focus();
        if (btn.classList.contains('password-toggle-text')) {
          btn.textContent = isCurrentlyPassword ? 'Hide' : 'Show';
        } else if (btn.querySelector('.eye-open') && btn.querySelector('.eye-closed')) {
          btn.classList.toggle('is-visible', isCurrentlyPassword);
        }
        const label = input.labels && input.labels[0] ? input.labels[0].textContent : 'password';
        btn.setAttribute('aria-label', (isCurrentlyPassword ? 'Hide ' : 'Show ') + label);
      });
    });

    if (otpForm.dataset.otpInvalid === '1') {
      otpForm.classList.add('otp-invalid');
      digitInputs.forEach(function (inp) { inp.classList.add('is-invalid'); });
    }
    updateTimers();
    otpForm.otpTimer = window.setInterval(updateTimers, 1000);
  }
  document.querySelectorAll('[data-otp-form]').forEach(initializeOtpForm);

  /* ---------- Inline admin profile editing ---------- */
  const profileRoot = document.querySelector('[data-profile]');
  if (profileRoot) {
    const csrfToken = profileRoot.dataset.csrfToken;
    const updatedAt = document.querySelector('[data-profile-updated]');

    async function profileRequest(data) {
      const body = new URLSearchParams(data);
      body.set('csrf_token', csrfToken);
      const response = await fetch('update_profile.php', { method: 'POST', body: body });
      const result = await response.json();
      if (!response.ok && !result.message) throw new Error('Profile update failed. Please try again.');
      return result;
    }
    function showProfileError(element, message) {
      element.textContent = message || '';
      element.classList.toggle('visible', Boolean(message));
    }
    function showProfileSuccess(row, message) {
      showToast(message, 'success');
    }
    function updateProfileDisplay(row, result) {
      const value = row.querySelector('[data-profile-value]');
      value.textContent = result.value || '';
      if (!result.value) {
        const empty = document.createElement('span');
        empty.className = 'profile-empty';
        empty.textContent = 'Not set';
        value.appendChild(empty);
      }
      row.querySelector('[data-profile-input]').value = result.value || '';
      if (updatedAt && result.updated_at) updatedAt.textContent = result.updated_at;
      if (result.field === 'username') {
        const adminName = document.querySelector('.sb-admin strong');
        const avatar = document.querySelector('.sb-admin .avatar');
        if (adminName) adminName.textContent = result.value;
        if (avatar) avatar.textContent = result.value.charAt(0).toUpperCase();
        const menuName = document.querySelector('.account-identity strong');
        const menuAvatar = document.querySelector('.account-avatar');
        if (menuName) menuName.textContent = result.value;
        if (menuAvatar) menuAvatar.textContent = result.value.charAt(0).toUpperCase();
      } else if (result.field === 'email') {
        const menuEmail = document.querySelector('.account-identity span');
        if (menuEmail) menuEmail.textContent = result.value || 'Email not set';
      }
    }
    function setProfileEditing(row, editing) {
      row.classList.toggle('is-editing', editing);
      row.querySelector('[data-profile-display]').hidden = editing;
      row.querySelector('[data-profile-editor]').hidden = !editing;
      if (editing) row.querySelector('[data-profile-input]').focus();
    }
    function startResendTimer(button, output, seconds) {
      let remaining = Math.max(0, Number(seconds) || 0);
      button.disabled = remaining > 0;
      if (button.dataset.resendTimer) window.clearInterval(Number(button.dataset.resendTimer));
      function tick() {
        button.disabled = remaining > 0;
        output.textContent = remaining > 0 ? 'Resend in 0:' + String(remaining).padStart(2, '0') : 'You can request a new code.';
        if (remaining <= 0) {
          window.clearInterval(Number(button.dataset.resendTimer));
          return;
        }
        remaining--;
      }
      tick();
      button.dataset.resendTimer = window.setInterval(tick, 1000);
    }

    profileRoot.querySelectorAll('[data-profile-row]').forEach(function (row) {
      const field = row.dataset.profileRow;
      const display = row.querySelector('[data-profile-display]');
      const editor = row.querySelector('[data-profile-editor]');
      const input = row.querySelector('[data-profile-input]');
      const error = row.querySelector('[data-profile-error]');
      const emailVerification = row.querySelector('[data-profile-email-verification]');
      const emailError = row.querySelector('[data-profile-email-error]');

      row.querySelector('[data-profile-edit]').addEventListener('click', function () {
        if (emailVerification) emailVerification.hidden = true;
        showProfileError(error, '');
        setProfileEditing(row, true);
      });
      row.querySelector('[data-profile-cancel]').addEventListener('click', function () {
        input.value = row.querySelector('[data-profile-value]').innerText.trim() === 'Not set' ? '' : row.querySelector('[data-profile-value]').innerText.trim();
        showProfileError(error, '');
        setProfileEditing(row, false);
      });
      row.querySelector('[data-profile-save]').addEventListener('click', async function (event) {
        const button = event.currentTarget;
        button.disabled = true;
        showProfileError(error, '');
        try {
          const result = await profileRequest({ action: 'save', field: field, value: input.value.trim() });
          if (!result.success) {
            showProfileError(error, result.message);
          } else if (result.requires_otp && emailVerification) {
            editor.hidden = true;
            emailVerification.hidden = false;
            showProfileSuccess(row, result.message);
            const resend = row.querySelector('[data-profile-email-resend]');
            startResendTimer(resend, row.querySelector('[data-profile-resend-countdown]'), result.resend_in);
            row.querySelector('[data-profile-email-code]').focus();
          } else {
            updateProfileDisplay(row, result);
            setProfileEditing(row, false);
            showProfileSuccess(row, result.message);
          }
        } catch (requestError) {
          showProfileError(error, requestError.message);
        } finally {
          button.disabled = false;
        }
      });
      if (emailVerification) {
        const codeInput = row.querySelector('[data-profile-email-code]');
        codeInput.addEventListener('input', function () { codeInput.value = codeInput.value.replace(/\D/g, '').slice(0, 6); });
        row.querySelector('[data-profile-email-cancel]').addEventListener('click', function () {
          emailVerification.hidden = true;
          setProfileEditing(row, false);
          showProfileError(emailError, '');
        });
        row.querySelector('[data-profile-email-verify]').addEventListener('click', async function () {
          const button = row.querySelector('[data-profile-email-verify]');
          button.disabled = true;
          showProfileError(emailError, '');
          try {
            const result = await profileRequest({ action: 'verify_email', code: codeInput.value });
            if (!result.success) {
              showProfileError(emailError, result.message);
            } else {
              updateProfileDisplay(row, result);
              emailVerification.hidden = true;
              codeInput.value = '';
              showProfileSuccess(row, result.message);
            }
          } catch (requestError) {
            showProfileError(emailError, requestError.message);
          } finally {
            button.disabled = false;
          }
        });
        row.querySelector('[data-profile-email-resend]').addEventListener('click', async function (event) {
          const button = event.currentTarget;
          button.disabled = true;
          try {
            const result = await profileRequest({ action: 'resend_email' });
            if (!result.success) {
              showProfileError(emailError, result.message);
              if (result.cooldown) startResendTimer(button, row.querySelector('[data-profile-resend-countdown]'), result.cooldown);
            } else {
              showProfileError(emailError, '');
              startResendTimer(button, row.querySelector('[data-profile-resend-countdown]'), result.resend_in);
            }
          } catch (requestError) {
            showProfileError(emailError, requestError.message);
            button.disabled = false;
          }
        });
      }
    });
  }

  /* ---------- Profile settings tabs and embedded password flow ---------- */
  const profileTabs = document.querySelector('[data-profile-tabs]');
  if (profileTabs) {
    const tabButtons = Array.from(profileTabs.querySelectorAll('[data-profile-tab]'));
    const tabPanels = Array.from(profileTabs.querySelectorAll('[data-profile-panel]'));
    const securityFlow = profileTabs.querySelector('[data-security-password-flow]');

    async function loadSecurityFlow() {
      if (!securityFlow || securityFlow.dataset.loaded === 'true' || securityFlow.dataset.loading === 'true') return;
      securityFlow.dataset.loading = 'true';
      try {
        const response = await fetch('change_password.php?embed=1', { cache: 'no-store' });
        if (!response.ok) throw new Error('Could not load security settings.');
        const markup = await response.text();
        const template = document.createElement('template');
        template.innerHTML = markup;
        const card = template.content.querySelector('.otp-password-card');
        if (!card) throw new Error('Could not load security settings.');
        const success = card.querySelector('.otp-success');
        const sent = card.querySelector('.otp-sent');
        if (success) {
          showToast(success.innerText.trim(), 'success');
          success.remove();
        } else if (sent) {
          showToast(sent.innerText.trim(), 'success');
          sent.remove();
        }
        securityFlow.replaceChildren(card);
        securityFlow.dataset.loaded = 'true';
        const otpForm = securityFlow.querySelector('[data-otp-form]');
        if (otpForm) initializeOtpForm(otpForm);
      } catch (error) {
        securityFlow.innerHTML = '<p class="security-load-error">' + error.message + '</p>';
      } finally {
        securityFlow.dataset.loading = 'false';
      }
    }

    function selectProfileTab(button, focusButton) {
      const tabName = button.dataset.profileTab;
      tabButtons.forEach(function (tab) {
        const active = tab === button;
        tab.classList.toggle('active', active);
        tab.setAttribute('aria-selected', String(active));
        tab.tabIndex = active ? 0 : -1;
      });
      tabPanels.forEach(function (panel) {
        const active = panel.dataset.profilePanel === tabName;
        panel.hidden = !active;
        panel.classList.toggle('active', active);
      });
      if (focusButton) button.focus();
      if (tabName === 'security') loadSecurityFlow();
    }

    tabButtons.forEach(function (button, index) {
      button.addEventListener('click', function () { selectProfileTab(button, false); });
      button.addEventListener('keydown', function (event) {
        let nextIndex = index;
        if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabButtons.length;
        else if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabButtons.length) % tabButtons.length;
        else if (event.key === 'Home') nextIndex = 0;
        else if (event.key === 'End') nextIndex = tabButtons.length - 1;
        else return;
        event.preventDefault();
        selectProfileTab(tabButtons[nextIndex], true);
      });
    });

    if (securityFlow) {
      function showSecurityFieldError(input, message) {
        const field = input.closest('.password-field');
        const error = field && field.querySelector('[data-password-error]');
        if (error) error.textContent = message || '';
        if (field) field.classList.toggle('invalid', Boolean(message));
      }
      securityFlow.addEventListener('click', function (event) {
        const resendBtn = event.target.closest('[data-resend-trigger]');
        if (resendBtn) {
          event.preventDefault();
          const resendForm = securityFlow.querySelector('[data-resend-form]');
          if (resendForm) {
            resendForm.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
          }
          return;
        }

        const toggle = event.target.closest('[data-password-toggle]');
        if (toggle) {
          event.preventDefault();
          event.stopPropagation();
          const input = document.getElementById(toggle.dataset.passwordToggle);
          if (!input) return;
          const isCurrentlyPassword = input.type === 'password';
          input.type = isCurrentlyPassword ? 'text' : 'password';
          input.focus();
          if (toggle.classList.contains('password-toggle-text')) {
            toggle.textContent = isCurrentlyPassword ? 'Hide' : 'Show';
          } else if (toggle.querySelector('.eye-open') && toggle.querySelector('.eye-closed')) {
            toggle.classList.toggle('is-visible', isCurrentlyPassword);
          }
          const label = input.labels && input.labels[0] ? input.labels[0].textContent : 'password';
          toggle.setAttribute('aria-label', (isCurrentlyPassword ? 'Hide ' : 'Show ') + label);
        }
      });

      securityFlow.addEventListener('reset', function () {
        setTimeout(function () {
          const strength = securityFlow.querySelector('[data-password-strength]');
          if (strength) strength.className = 'password-strength-bars';
          securityFlow.querySelectorAll('[data-rule]').forEach(function (rule) {
            rule.classList.remove('met');
          });
          securityFlow.querySelectorAll('.password-field').forEach(function (field) {
            field.classList.remove('invalid');
            const err = field.querySelector('[data-password-error]');
            if (err) err.textContent = '';
          });
        }, 20);
      });

      securityFlow.addEventListener('input', function (event) {
        const input = event.target;
        if (input.id === 'current_password') {
          showSecurityFieldError(input, '');
        } else if (input.id === 'new_password') {
          const form = input.form;
          const value = input.value;
          const hasLength = value.length >= 8;
          const hasCase = /[a-z]/.test(value) && /[A-Z]/.test(value);
          const hasNumber = /\d/.test(value);
          const hasSpecial = /[^a-zA-Z0-9]/.test(value);

          let score = 0;
          if (hasLength) score++;
          if (hasCase) score++;
          if (hasNumber) score++;
          if (hasSpecial) score++;

          const strength = form.querySelector('[data-password-strength]');
          if (strength) strength.className = 'password-strength-bars' + (value ? ' strength-' + score : '');
          form.querySelectorAll('[data-rule]').forEach(function (rule) {
            let met = false;
            if (rule.dataset.rule === 'length') met = hasLength;
            else if (rule.dataset.rule === 'case') met = hasCase;
            else if (rule.dataset.rule === 'number') met = hasNumber;
            else if (rule.dataset.rule === 'special') met = hasSpecial;
            rule.classList.toggle('met', met);
          });
          if (hasLength) showSecurityFieldError(input, '');
          const confirm = form.querySelector('#confirm_password');
          if (confirm && confirm.value) showSecurityFieldError(confirm, confirm.value === value ? '' : 'Passwords do not match.');
        } else if (input.id === 'confirm_password' && input.form) {
          showSecurityFieldError(input, input.value === input.form.querySelector('#new_password').value ? '' : 'Passwords do not match.');
        }
      });

      securityFlow.addEventListener('submit', async function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-password-request], [data-password-form], .otp-resend, [data-resend-form]')) return;
        event.preventDefault();
        if (!form.reportValidity()) return;

        if (form.matches('[data-password-form]')) {
          const currentPassword = form.querySelector('#current_password');
          if (currentPassword && !currentPassword.value) {
            showSecurityFieldError(currentPassword, 'Current password is required.');
            currentPassword.focus();
            return;
          }
          const otpBoxes = Array.from(form.querySelectorAll('[data-otp-digit]'));
          const hiddenOtp = form.querySelector('#otp_code');
          if (otpBoxes.length > 0 && (!hiddenOtp || !hiddenOtp.value || hiddenOtp.value.length !== 6)) {
            showToast('Please enter the complete 6-digit verification code.', 'error');
            const firstEmpty = otpBoxes.find(function (box) { return !box.value; }) || otpBoxes[0];
            firstEmpty.focus();
            return;
          }
          const newPassword = form.querySelector('#new_password');
          const confirmPassword = form.querySelector('#confirm_password');
          if (newPassword && newPassword.value.length < 8) {
            showSecurityFieldError(newPassword, 'Use at least 8 characters.');
            newPassword.focus();
            return;
          }
          if (confirmPassword && newPassword && confirmPassword.value !== newPassword.value) {
            showSecurityFieldError(confirmPassword, 'Passwords do not match.');
            confirmPassword.focus();
            return;
          }
        }

        const submit = form.querySelector('button[type="submit"]');
        if (submit) submit.disabled = true;
        try {
          // The hidden input named "action" shadows HTMLFormElement.action.
          // Read the attribute directly so fetch always receives the URL string.
          const endpoint = form.getAttribute('action') || 'change_password.php?embed=1';
          const response = await fetch(endpoint, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'text/html' } });
          const markup = await response.text();
          const template = document.createElement('template');
          template.innerHTML = markup;
          const card = template.content.querySelector('.otp-password-card');
          if (!card) throw new Error('Could not update password. Please try again.');
          const oldOtp = securityFlow.querySelector('[data-otp-form]');
          if (oldOtp && oldOtp.otpTimer) window.clearInterval(oldOtp.otpTimer);
          const success = card.querySelector('.otp-success');
          const sent = card.querySelector('.otp-sent');
          if (success) {
            showToast(success.innerText.trim(), 'success');
            success.remove();
          } else if (sent) {
            showToast(sent.innerText.trim(), 'success');
            sent.remove();
          }
          securityFlow.replaceChildren(card);
          const otpForm = securityFlow.querySelector('[data-otp-form]');
          if (otpForm) initializeOtpForm(otpForm);
        } catch (error) {
          showToast(error.message, 'error');
        } finally {
          const currentSubmit = securityFlow.querySelector('button[type="submit"]');
          if (currentSubmit) currentSubmit.disabled = false;
        }
      });
    }
  }

  /* ---------- Filter dropdown ---------- */
  const filterPanel = document.getElementById('filterPanel');
  const filterOpen = document.querySelector('[data-filter-open]');
  const filterMenu = filterOpen && filterOpen.closest('.filter-menu-wrap');
  if (filterPanel && filterOpen && filterMenu) {
    function setFilterPanel(open) {
      filterPanel.hidden = !open;
      filterOpen.setAttribute('aria-expanded', String(open));
      if (open) {
        const firstSelect = filterPanel.querySelector('select');
        if (firstSelect) firstSelect.focus();
      }
    }
    filterOpen.addEventListener('click', function (event) {
      event.stopPropagation();
      setFilterPanel(filterPanel.hidden);
    });
    filterPanel.addEventListener('click', function (event) {
      event.stopPropagation();
    });
    document.addEventListener('click', function (event) {
      if (filterMenu && !filterMenu.contains(event.target)) setFilterPanel(false);
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && filterPanel && !filterPanel.hidden) {
        setFilterPanel(false);
        filterOpen.focus();
      }
    });
  }
  document.querySelectorAll('[data-page-size]').forEach(function (select) {
    select.addEventListener('change', function () { select.form.requestSubmit(); });
  });
  document.querySelectorAll('[data-live-search]').forEach(function (form) {
    const input = form.querySelector('input[type="search"]');
    let timer = 0;
    if (!input) return;
    const syncSearchFromUrl = function () {
      const query = new URLSearchParams(window.location.search).get('q');
      if (query !== null) input.value = query;
    };
    syncSearchFromUrl();
    input.addEventListener('input', function () {
      window.clearTimeout(timer);
      const searchUrl = new URL(window.location.href);
      searchUrl.searchParams.set('q', input.value);
      window.history.replaceState(window.history.state, '', searchUrl.toString());
      timer = window.setTimeout(function () { form.requestSubmit(); }, 280);
    });
    form.addEventListener('submit', function () {
      syncSearchFromUrl();
    });
  });

  /* ---------- Live feedback notifications (authenticated polling) ---------- */
  const notificationRoot = document.getElementById('feedbackNotifications');
  if (notificationRoot) {
    const notificationToggle = document.getElementById('notificationToggle');
    const notificationPanel = document.getElementById('notificationPanel');
    const notificationCount = document.getElementById('notificationCount');
    const notificationList = notificationRoot.querySelector('[data-notification-list]');
    const notificationTotal = notificationRoot.querySelector('[data-notification-total]');
    const markReadButton = notificationRoot.querySelector('[data-notification-read]');
    const notificationTabs = Array.from(notificationRoot.querySelectorAll('[data-notification-filter]'));
    const markerKey = 'college-feedback-last-seen-' + notificationRoot.dataset.notificationScope;
    let lastSeen = null;
    let latestId = 0;
    let entries = [];
    let seenEntryIds = new Set();
    let notificationFilter = 'all';
    try {
      const stored = sessionStorage.getItem(markerKey);
      if (stored !== null && /^\d+$/.test(stored)) lastSeen = Number(stored);
    } catch (error) {
      lastSeen = null;
    }

    function relativeTime(value) {
      const submitted = new Date(String(value).replace(' ', 'T'));
      const seconds = Math.max(0, Math.floor((Date.now() - submitted.getTime()) / 1000));
      if (seconds < 60) return 'Just now';
      if (seconds < 3600) return Math.floor(seconds / 60) + ' minute' + (seconds < 120 ? '' : 's') + ' ago';
      if (seconds < 86400) return Math.floor(seconds / 3600) + ' hour' + (seconds < 7200 ? '' : 's') + ' ago';
      return Math.floor(seconds / 86400) + ' day' + (seconds < 172800 ? '' : 's') + ' ago';
    }
    function openNotificationDetail(entry) {
      const detailModal = document.getElementById('feedbackModal');
      if (!detailModal) return;
      const detailType = document.getElementById('feedbackModalType');
      const feedbackId = entry.feedback_id || entry.id;
      detailModal.dataset.feedbackId = String(feedbackId);
      const rating = Number(entry.rating) || 0;
      detailType.textContent = entry.submitter_type;
      detailType.className = 'badge ' + ({ 'Student': 'badge-student', 'Teacher & Staff': 'badge-staff', 'Visitor & Outsider': 'badge-visitor', 'Public': 'badge-visitor' }[entry.submitter_type] || '');
      document.getElementById('feedbackModalTitle').textContent = entry.display_name || 'Feedback Submission';
      document.getElementById('feedbackModalDate').textContent = 'Submitted ' + entry.submit_date;
      document.getElementById('feedbackModalCategory').textContent = entry.category;
      document.getElementById('feedbackModalRating').textContent = '★'.repeat(rating) + '☆'.repeat(5 - rating) + '  ' + rating + ' out of 5 stars';
      document.getElementById('feedbackModalMessage').textContent = entry.message || '';
      document.getElementById('feedbackModalAnonymity').textContent = entry.is_anonymous
        ? 'Submitted anonymously — no name was collected or stored.'
        : 'Submitted with name: ' + (entry.submitter_name || '—');
      detailModal.classList.add('open');
      const closeButton = detailModal.querySelector('[data-feedback-modal-close]');
      if (closeButton) closeButton.focus();
      loadFeedbackModalDetail(feedbackId);
    }
    function renderNotifications() {
      notificationList.replaceChildren();
      const visibleEntries = entries.filter(function (entry) {
        return notificationFilter === 'all' || (notificationFilter === 'read' && entry.is_read) || (notificationFilter === 'unread' && !entry.is_read);
      });
      if (!visibleEntries.length) {
        const empty = document.createElement('p');
        empty.className = 'notification-empty';
        empty.textContent = notificationFilter === 'all' ? 'No feedback submissions yet.' : 'No ' + notificationFilter + ' notifications.';
        notificationList.appendChild(empty);
        return;
      }
      visibleEntries.forEach(function (entry) {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'notification-item ' + (entry.is_read ? 'is-read' : 'is-unread') + (seenEntryIds.has(entry.notification_id) ? ' is-new' : '');
        item.dataset.notificationId = entry.notification_id;
        if (!entry.is_read) {
          const dot = document.createElement('span');
          dot.className = 'notification-unread-dot';
          dot.setAttribute('aria-label', 'Unread');
          item.appendChild(dot);
        }
        const type = document.createElement('span');
        type.className = 'badge ' + ({ 'Student': 'badge-student', 'Teacher & Staff': 'badge-staff', 'Visitor & Outsider': 'badge-visitor', 'Public': 'badge-visitor' }[entry.submitter_type] || '');
        type.textContent = entry.submitter_type;
        const detail = document.createElement('span');
        detail.className = 'notification-item-detail';
        const title = document.createElement('strong');
        title.textContent = entry.category;
        const meta = document.createElement('span');
        meta.className = 'notification-item-meta';
        meta.textContent = '★'.repeat(entry.rating) + ' · ' + relativeTime(entry.submit_date);
        detail.append(title, meta);
        item.append(type, detail);
        item.addEventListener('click', async function () {
          if (!entry.is_read) {
            try {
              const body = new URLSearchParams({ notification_id: String(entry.notification_id) });
              const response = await fetch('mark_notification_read.php', { method: 'POST', body: body, headers: { 'Accept': 'application/json' } });
              const result = await response.json();
              if (!response.ok || !result.success) throw new Error(result.message || 'Could not update notification.');
              entry.is_read = true;
              setUnreadCount(result.unread_count, false);
              if (notificationTotal) notificationTotal.textContent = 'All feedback updates';
              renderNotifications();
            } catch (error) {
              showToast(error.message, 'error');
            }
          }
          openNotificationDetail(entry);
        });
        notificationList.appendChild(item);
      });
    }
    function setUnreadCount(count, animate) {
      const unread = Math.max(0, Number(count) || 0);
      notificationCount.textContent = unread > 99 ? '99+' : String(unread);
      notificationCount.hidden = unread === 0;
      notificationToggle.setAttribute('aria-label', unread ? 'Notifications, ' + unread + ' unread' : 'Notifications');
      if (animate && unread) {
        notificationCount.classList.remove('notification-pop');
        notificationToggle.querySelector('.notification-bell-icon').classList.remove('notification-ring');
        void notificationCount.offsetWidth;
        notificationCount.classList.add('notification-pop');
        notificationToggle.querySelector('.notification-bell-icon').classList.add('notification-ring');
      }
    }
    function addEntries(newEntries) {
      const known = new Set(entries.map(function (entry) { return entry.id; }));
      newEntries.slice().reverse().forEach(function (entry) {
        if (!known.has(entry.id)) {
          entries.unshift(entry);
          known.add(entry.id);
        }
      });
      renderNotifications();
    }
    async function pollNotifications() {
      try {
        const since = lastSeen === null ? '' : '?since=' + encodeURIComponent(lastSeen);
        const response = await fetch('check_new_feedback.php' + since, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
        if (!response.ok) return;
        const data = await response.json();
        latestId = Number(data.latest_id) || 0;
        if (lastSeen === null) {
          lastSeen = latestId;
          try { sessionStorage.setItem(markerKey, String(lastSeen)); } catch (error) {}
          entries = data.entries || [];
          renderNotifications();
          setUnreadCount(data.unread_count, false);
          if (notificationTotal) notificationTotal.textContent = 'All feedback updates';
          return;
        }
        const newEntries = data.new_entries || [];
        newEntries.forEach(function (entry) { seenEntryIds.add(entry.notification_id); });
        entries = data.entries || [];
        addEntries(newEntries);
        setUnreadCount(data.unread_count, newEntries.length > 0);
        if (notificationTotal) notificationTotal.textContent = 'All feedback updates';
        lastSeen = latestId;
        try { sessionStorage.setItem(markerKey, String(lastSeen)); } catch (error) {}
      } catch (error) {
        if (!notificationList.children.length) {
          const message = document.createElement('p');
          message.className = 'notification-empty';
          message.textContent = 'Notifications are temporarily unavailable.';
          notificationList.appendChild(message);
        }
      }
    }

    notificationToggle.addEventListener('click', function () {
      const open = notificationPanel.hidden;
      notificationPanel.hidden = !open;
      notificationPanel.classList.toggle('open', open);
      notificationToggle.setAttribute('aria-expanded', String(open));
      if (open) pollNotifications();
    });
    notificationTabs.forEach(function (tab) {
      tab.addEventListener('click', function () {
        notificationFilter = tab.dataset.notificationFilter;
        notificationTabs.forEach(function (button) {
          const active = button === tab;
          button.classList.toggle('is-active', active);
          button.setAttribute('aria-selected', String(active));
          button.tabIndex = active ? 0 : -1;
        });
        renderNotifications();
      });
    });
    markReadButton.addEventListener('click', async function () {
      markReadButton.disabled = true;
      try {
        const response = await fetch('mark_all_notifications_read.php', { method: 'POST', headers: { 'Accept': 'application/json' } });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Could not mark notifications as read.');
        entries.forEach(function (entry) { entry.is_read = true; });
        seenEntryIds.clear();
        lastSeen = latestId;
        try { sessionStorage.setItem(markerKey, String(lastSeen)); } catch (error) {}
        setUnreadCount(result.unread_count, false);
        if (notificationTotal) notificationTotal.textContent = 'All feedback updates';
        renderNotifications();
      } catch (error) {
        showToast(error.message, 'error');
      } finally {
        markReadButton.disabled = false;
      }
    });
    document.addEventListener('click', function (event) {
      if (!notificationRoot.contains(event.target)) {
        notificationPanel.hidden = true;
        notificationPanel.classList.remove('open');
        notificationToggle.setAttribute('aria-expanded', 'false');
      }
    });
    pollNotifications();
    window.setInterval(pollNotifications, 12000);
  }

  /* ---------- Delete confirmation modal (Section 6.2, 13: delete requires confirmation) ---------- */
  const modal = document.getElementById('deleteModal');
  const deleteForm = document.getElementById('deleteForm');
  const deleteNameEl = document.getElementById('deleteTargetName');
  let deleteRow = null;

  document.querySelectorAll('[data-delete-id]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!modal) return;
      const id = btn.getAttribute('data-delete-id');
      const name = btn.getAttribute('data-delete-name') || 'this entry';
      deleteForm.action = 'delete_feedback.php?id=' + encodeURIComponent(id);
      deleteRow = btn.closest('[data-feedback-row]');
      if (deleteNameEl) deleteNameEl.textContent = name;
      modal.classList.add('open');
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach(function (el) {
    el.addEventListener('click', function () {
      if (modal) modal.classList.remove('open');
    });
  });

  /* ---------- Feedback detail modal ---------- */
  const feedbackModal = document.getElementById('feedbackModal');
  const feedbackFields = {
    title: document.getElementById('feedbackModalTitle'),
    type: document.getElementById('feedbackModalType'),
    date: document.getElementById('feedbackModalDate'),
    category: document.getElementById('feedbackModalCategory'),
    rating: document.getElementById('feedbackModalRating'),
    message: document.getElementById('feedbackModalMessage'),
    anonymity: document.getElementById('feedbackModalAnonymity')
  };
  function applyFeedbackModalEntry(entry) {
    const anonymous = document.getElementById('feedbackModalAnonymous');
    const status = document.getElementById('feedbackModalStatus');
    const statusSelect = document.getElementById('feedbackModalStatusSelect');
    const modalId = document.getElementById('feedbackModalId');
    const deleteButton = document.getElementById('feedbackModalDelete');
    const rating = Number(entry.rating) || 0;
    feedbackFields.title.textContent = entry.display_name || (entry.is_anonymous ? 'Anonymous Submission' : 'Unnamed');
    feedbackFields.type.textContent = entry.submitter_type;
    feedbackFields.type.className = 'badge ' + ({ 'Student': 'badge-student', 'Teacher & Staff': 'badge-staff', 'Visitor & Outsider': 'badge-visitor', 'Public': 'badge-visitor' }[entry.submitter_type] || '');
    feedbackFields.date.textContent = 'Submitted ' + entry.submit_date;
    feedbackFields.category.textContent = entry.category;
    feedbackFields.rating.textContent = '★'.repeat(rating) + '☆'.repeat(5 - rating) + '  ' + rating + ' out of 5 stars';
    feedbackFields.message.textContent = entry.message || '';
    feedbackFields.anonymity.textContent = entry.is_anonymous ? 'Submitted anonymously — no name was collected or stored.' : 'Submitted with name: ' + (entry.submitter_name || '—');
    if (anonymous) anonymous.hidden = !entry.is_anonymous;
    if (status) { status.textContent = entry.status || 'New'; status.className = 'badge badge-' + String(entry.status || 'New').toLowerCase(); }
    if (statusSelect) statusSelect.value = entry.status || 'New';
    if (modalId) modalId.value = entry.id;
    if (deleteButton) { deleteButton.dataset.deleteId = entry.id; deleteButton.dataset.deleteName = entry.display_name || 'this entry'; }
  }

  function loadFeedbackModalDetail(feedbackId) {
    return fetch('get_feedback_detail.php?id=' + encodeURIComponent(feedbackId), { headers: { 'Accept': 'application/json' }, cache: 'no-store' })
      .then(function (response) { if (!response.ok) throw new Error('Could not load feedback details.'); return response.json(); })
      .then(function (data) {
        if (data.feedback && feedbackModal.dataset.feedbackId === String(feedbackId)) applyFeedbackModalEntry(data.feedback);
      })
      .catch(function (error) { showToast(error.message, 'error'); });
  }

  document.querySelectorAll('[data-feedback-view]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!feedbackModal) return;
      const rating = Number(btn.dataset.feedbackRating);
      feedbackFields.title.textContent = btn.dataset.feedbackName;
      feedbackFields.type.textContent = btn.dataset.feedbackType;
      feedbackFields.type.className = 'badge ' + ({
        'Student': 'badge-student',
        'Teacher & Staff': 'badge-staff',
        'Visitor & Outsider': 'badge-visitor',
        'Public': 'badge-visitor'
      }[btn.dataset.feedbackType] || '');
      feedbackFields.date.textContent = 'Submitted ' + btn.dataset.feedbackDate;
      feedbackFields.category.textContent = btn.dataset.feedbackCategory;
      feedbackFields.rating.textContent = '★'.repeat(rating) + '☆'.repeat(5 - rating) + '  ' + rating + ' out of 5 stars';
      feedbackFields.message.textContent = btn.dataset.feedbackMessage;
      feedbackFields.anonymity.textContent = btn.dataset.feedbackAnonymity;
      feedbackModal.dataset.feedbackId = String(btn.dataset.feedbackId);
      feedbackModal.classList.add('open');
      feedbackModal.querySelector('[data-feedback-modal-close]').focus();

      // Reload from the server so the detail dialog always reflects the latest
      // comment and workflow status without leaving the feedback list.
      loadFeedbackModalDetail(btn.dataset.feedbackId);
    });
  });

  document.querySelectorAll('[data-feedback-modal-close]').forEach(function (el) {
    el.addEventListener('click', function () {
      if (feedbackModal) feedbackModal.classList.remove('open');
    });
  });

  [modal, feedbackModal].forEach(function (overlay) {
    if (!overlay) return;
    overlay.addEventListener('click', function (event) {
      if (event.target === overlay) overlay.classList.remove('open');
    });
  });

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    if (modal) modal.classList.remove('open');
    if (feedbackModal) feedbackModal.classList.remove('open');
    if (filterPanel && !filterPanel.hidden && filterOpen) {
      filterPanel.hidden = true;
      filterOpen.setAttribute('aria-expanded', 'false');
      filterOpen.focus();
    }
  });

  /* ---------- Dashboard number count-up ---------- */
  document.querySelectorAll('.stat-number[data-count]').forEach(function (item) {
    const target = Number(item.dataset.count);
    const suffix = item.dataset.suffix || '';
    if (reduceMotion) { item.textContent = target + suffix; return; }
    const start = performance.now();
    const duration = 420;
    function tick(now) {
      const progress = Math.min((now - start) / duration, 1);
      const value = Number.isInteger(target) ? Math.round(target * progress) : (target * progress).toFixed(1);
      item.textContent = value + suffix;
      if (progress < 1) requestAnimationFrame(tick);
    }
    item.textContent = '0' + suffix;
    requestAnimationFrame(tick);
  });

  /* ---------- Ratings chart entrance ---------- */
  const ratingChart = document.querySelector('.rating-chart');
  if (ratingChart) {
    if (reduceMotion || !('IntersectionObserver' in window)) {
      ratingChart.classList.add('in-view');
    } else {
      const chartObserver = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) { if (entry.isIntersecting) { entry.target.classList.add('in-view'); observer.unobserve(entry.target); } });
      }, { threshold: 0.25 });
      chartObserver.observe(ratingChart);
    }
  }

  /* ---------- Feedback status update, with inline confirmation ---------- */
  const statusForm = document.querySelector('[data-status-form]');
  if (statusForm) {
    statusForm.addEventListener('submit', function (event) {
      event.preventDefault();
      const toast = statusForm.querySelector('.status-toast');
      const submit = statusForm.querySelector('button[type="submit"]');
      const data = new FormData(statusForm);
      submit.disabled = true;
      fetch(statusForm.action, { method: 'POST', body: data, headers: { 'Accept': 'application/json' } })
        .then(function (response) { if (!response.ok) throw new Error('Status update failed'); return response.json(); })
        .then(function (data) {
          if (!data.updated) throw new Error('Status update failed');
          toast.textContent = 'Status updated.';
          toast.classList.add('visible');
          window.setTimeout(function () { toast.classList.remove('visible'); }, reduceMotion ? 0 : 2400);
        })
        .catch(function () { toast.textContent = 'Could not update status. Please try again.'; toast.classList.add('visible'); })
        .finally(function () { submit.disabled = false; });
    });
  }

  /* ---------- Detail-modal status workflow ---------- */
  document.querySelectorAll('[data-table-status-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      const submit = form.querySelector('button[type="submit"]');
      const selected = form.querySelector('select[name="status"]');
      if (!selected || (submit && submit.disabled)) return;
      if (submit) submit.disabled = true;
      fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (response) { if (!response.ok) throw new Error('Status update failed.'); return response.json(); })
        .then(function (result) {
          if (!result.updated) throw new Error('Status update failed.');
          const row = form.closest('[data-feedback-row]');
          if (row) {
            const badge = row.querySelector('[data-status-cell] .badge');
            if (badge) { badge.textContent = result.status; badge.className = 'badge badge-' + result.status.toLowerCase(); }
          }
          const modalBadge = document.getElementById('feedbackModalStatus');
          if (modalBadge && document.getElementById('feedbackModalId') && document.getElementById('feedbackModalId').value === form.querySelector('[name="id"]').value) {
            modalBadge.textContent = result.status;
            modalBadge.className = 'badge badge-' + result.status.toLowerCase();
          }
          showToast('Status updated to ' + result.status + '.', 'success');
        })
        .catch(function (error) { showToast(error.message, 'error'); })
        .finally(function () { if (submit) submit.disabled = false; });
    });
  });

  /* ---------- Change-password guidance and client-side validation ---------- */
  document.addEventListener('click', function (e) {
    const button = e.target.closest('[data-password-toggle]');
    if (!button) return;
    e.preventDefault();
    e.stopPropagation();
    const input = document.getElementById(button.dataset.passwordToggle);
    if (!input) return;
    const isCurrentlyPassword = input.type === 'password';
    input.type = isCurrentlyPassword ? 'text' : 'password';
    input.focus();
    if (button.querySelector('.eye-open') && button.querySelector('.eye-closed')) {
      button.classList.toggle('is-visible', isCurrentlyPassword);
    } else {
      button.textContent = isCurrentlyPassword ? 'Hide' : 'Show';
    }
    const label = input.labels && input.labels[0] ? input.labels[0].textContent : 'password';
    button.setAttribute('aria-label', (isCurrentlyPassword ? 'Hide ' : 'Show ') + label);
  });

  document.querySelectorAll('[data-password-form]').forEach(function (passwordForm) {
    const currentPassword = passwordForm.querySelector('#current_password');
    const newPassword = passwordForm.querySelector('#new_password');
    const confirmPassword = passwordForm.querySelector('#confirm_password');
    const strength = passwordForm.querySelector('.password-strength');
    const rules = {
      length: passwordForm.querySelector('[data-rule="length"]'),
      case: passwordForm.querySelector('[data-rule="case"]'),
      number: passwordForm.querySelector('[data-rule="number"]')
    };

    function showPasswordError(input, message) {
      if (!input) return;
      const field = input.closest('.password-field');
      const error = field && field.querySelector('[data-password-error]');
      if (field) field.classList.toggle('invalid', Boolean(message));
      if (error) error.textContent = message || '';
    }
    function updateStrength() {
      if (!newPassword || !strength || !rules.length || !rules.case || !rules.number) return;
      const value = newPassword.value;
      const hasLength = value.length >= 8;
      const hasCase = /[a-z]/.test(value) && /[A-Z]/.test(value);
      const hasNumber = /\d/.test(value);
      rules.length.classList.toggle('met', hasLength);
      rules.case.classList.toggle('met', hasCase);
      rules.number.classList.toggle('met', hasNumber);
      const score = Number(hasLength) + Number(hasCase) + Number(hasNumber);
      strength.className = 'password-strength' + (value ? (score === 3 ? ' strong' : score === 2 ? ' medium' : ' weak') : '');
    }
    if (newPassword) {
      newPassword.addEventListener('input', function () {
        updateStrength();
        if (newPassword.value.length >= 8) showPasswordError(newPassword, '');
        if (confirmPassword && confirmPassword.value) showPasswordError(confirmPassword, confirmPassword.value === newPassword.value ? '' : 'Passwords do not match.');
      });
    }
    if (confirmPassword) {
      confirmPassword.addEventListener('input', function () {
        showPasswordError(confirmPassword, confirmPassword.value === newPassword.value ? '' : 'Passwords do not match.');
      });
    }
    if (currentPassword) {
      currentPassword.addEventListener('input', function () { if (currentPassword.value) showPasswordError(currentPassword, ''); });
    }
    passwordForm.addEventListener('submit', function (event) {
      let valid = true;
      if (currentPassword && !currentPassword.value) { showPasswordError(currentPassword, 'Enter your current password.'); valid = false; }
      if (newPassword && newPassword.value.length < 8) { showPasswordError(newPassword, 'Use at least 8 characters.'); valid = false; }
      if (confirmPassword && newPassword && confirmPassword.value !== newPassword.value) { showPasswordError(confirmPassword, 'Passwords do not match.'); valid = false; }
      if (!valid) { event.preventDefault(); const firstInvalid = passwordForm.querySelector('.password-field.invalid input'); if (firstInvalid) firstInvalid.focus(); }
    });
    updateStrength();
  });

  /* ---------- Delete: wait for a successful response, then remove the row ---------- */
  if (deleteForm) {
    deleteForm.addEventListener('submit', function (event) {
      event.preventDefault();
      const submit = deleteForm.querySelector('button[type="submit"]');
      if (submit && submit.disabled) return;
      if (submit) { submit.disabled = true; submit.textContent = 'Deleting…'; }
      fetch(deleteForm.action, { method: 'POST', headers: { 'Accept': 'application/json' } })
        .then(function (response) {
          if (!response.ok) throw new Error('Delete failed');
          return response.json();
        })
        .then(function (data) {
          if (!data.deleted) throw new Error('Delete failed');
          if (modal) modal.classList.remove('open');
          if (feedbackModal) feedbackModal.classList.remove('open');
          if (deleteRow) {
            deleteRow.classList.add('removing');
            window.setTimeout(function () { deleteRow.remove(); }, reduceMotion ? 0 : 240);
          }
        })
        .catch(function () {
          if (submit) { submit.disabled = false; submit.textContent = 'Delete Permanently'; }
          if (deleteNameEl) deleteNameEl.textContent = 'this entry — please try again.';
        });
    });
  }
});
