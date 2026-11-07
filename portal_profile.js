document.addEventListener('DOMContentLoaded', () => {
  const toastData = document.querySelector('[data-portal-toast-message]');
  if (toastData) {
    const toast = document.createElement('div');
    toast.className = 'portal-toast ' + (toastData.dataset.portalToastType || 'success');
    toast.setAttribute('role', 'status');
    toast.textContent = toastData.dataset.portalToastMessage || '';
    document.body.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('visible'));
    window.setTimeout(() => toast.classList.remove('visible'), 4000);
  }
  document.querySelectorAll('[data-portal-profile]').forEach((profile) => {
    const tabs = [...profile.querySelectorAll('[data-profile-tab]')];
    const panels = [...profile.querySelectorAll('[data-profile-panel]')];
    tabs.forEach((tab) => tab.addEventListener('click', () => { tabs.forEach((item) => { const active = item === tab; item.classList.toggle('active', active); item.setAttribute('aria-selected', String(active)); }); panels.forEach((panel) => { panel.hidden = panel.dataset.profilePanel !== tab.dataset.profileTab; panel.classList.toggle('active', !panel.hidden); }); }));
  });
  document.querySelectorAll('[data-portal-otp-form]').forEach((form) => { const boxes = [...form.querySelectorAll('[data-portal-otp-digit]')]; const hidden = form.querySelector('[data-portal-otp-code]'); const sync = () => { hidden.value = boxes.map((box) => box.value).join(''); }; boxes.forEach((box, index) => box.addEventListener('input', () => { box.value = box.value.replace(/\D/g, '').slice(0, 1); sync(); if (box.value && boxes[index + 1]) boxes[index + 1].focus(); })); form.querySelectorAll('[data-portal-password-toggle]').forEach((button) => button.addEventListener('click', () => { const input = document.getElementById(button.dataset.portalPasswordToggle); input.type = input.type === 'password' ? 'text' : 'password'; button.textContent = input.type === 'password' ? 'Show' : 'Hide'; })); const timer = form.dataset.otpExpires * 1000; const display = form.querySelector('[data-portal-otp-countdown]'); const tick = () => { const seconds = Math.max(0, Math.ceil((timer - Date.now()) / 1000)); display.textContent = Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0'); }; tick(); setInterval(tick, 1000); });
  const resendButton = document.querySelector('button[form="portalOtpResend"]');
  if (resendButton && window.portalProfileResendAt) {
    const status = document.createElement('small'); status.className = 'resend-status-text'; resendButton.after(status);
    const tick = () => { const seconds = Math.max(0, Math.ceil((window.portalProfileResendAt - Date.now()) / 1000)); resendButton.disabled = seconds > 0; status.textContent = seconds ? 'Resend code in ' + seconds + 's' : 'You can request a new code.'; };
    tick(); setInterval(tick, 1000);
  }
});
