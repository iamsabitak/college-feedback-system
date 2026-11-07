document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
  button.innerHTML = '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>';
  button.addEventListener('click', function () {
    var input = document.getElementById(button.dataset.passwordToggle);
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    button.classList.toggle('is-visible', input.type === 'text');
  });
});

document.querySelectorAll('#otp_code').forEach(function (otpInput) {
  var form = otpInput.closest('form');
  if (!form || form.querySelector('[data-otp-boxes]')) return;
  otpInput.type = 'hidden';
  var boxes = document.createElement('div'); boxes.className = 'otp-boxes'; boxes.dataset.otpBoxes = '';
  for (var index = 0; index < 6; index += 1) {
    var box = document.createElement('input'); box.type = 'text'; box.inputMode = 'numeric'; box.maxLength = 1; box.autocomplete = index === 0 ? 'one-time-code' : 'off'; box.setAttribute('aria-label', 'Verification code digit ' + (index + 1));
    box.addEventListener('input', function () { this.value = this.value.replace(/\D/g, '').slice(-1); otpInput.value = Array.from(boxes.querySelectorAll('input')).map(function (item) { return item.value; }).join(''); if (this.value && this.nextElementSibling) this.nextElementSibling.focus(); });
    box.addEventListener('keydown', function (event) { if (event.key === 'Backspace' && !this.value && this.previousElementSibling) this.previousElementSibling.focus(); });
    boxes.appendChild(box);
  }
  otpInput.parentNode.insertBefore(boxes, otpInput.nextSibling);
  var hint = document.createElement('small'); hint.className = 'field-hint'; hint.textContent = 'Enter the 6-digit code sent to the associated email.'; boxes.after(hint);
  var banner = document.createElement('div'); banner.className = 'portal-otp-banner'; banner.innerHTML = '<span>Code expires in <strong>10:00</strong></span><strong>Code is active</strong>';
  form.insertBefore(banner, form.querySelector('.field'));
  if (window.portalOtpExpiry) {
    var timerText = banner.querySelector('strong');
    var timerStatus = banner.lastElementChild;
    var updateTimer = function () {
      var seconds = Math.max(0, Math.ceil((window.portalOtpExpiry - Date.now()) / 1000));
      timerText.textContent = Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0');
      if (seconds === 0) { timerStatus.textContent = 'Code expired. Request a new one.'; }
    };
    updateTimer(); window.setInterval(updateTimer, 1000);
  }
  var resendForm = document.querySelector('.otp-resend');
  var resendButton = resendForm && resendForm.querySelector('button');
  if (resendButton && window.portalOtpResendAt) {
    var resendStatus = document.createElement('small');
    resendStatus.className = 'resend-status-text';
    resendButton.after(resendStatus);
    var updateResend = function () {
      var seconds = Math.max(0, Math.ceil((window.portalOtpResendAt - Date.now()) / 1000));
      resendButton.disabled = seconds > 0;
      resendStatus.textContent = seconds > 0 ? 'Resend code in ' + seconds + 's' : 'You can request a new code.';
    };
    updateResend(); window.setInterval(updateResend, 1000);
  }
  form.addEventListener('submit', function (event) {
    if (!/^\d{6}$/.test(otpInput.value)) {
      event.preventDefault();
      boxes.querySelector('input').focus();
      hint.textContent = 'Enter all 6 digits from the verification code.';
      hint.classList.add('is-error');
    }
  });
});
