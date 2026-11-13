document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-portal-filter-open]').forEach((button) => {
    const panel = document.getElementById(button.getAttribute('aria-controls'));
    const menu = button.closest('.portal-filter-menu');
    if (!panel || !menu) return;
    const setOpen = (open) => { panel.hidden = !open; button.setAttribute('aria-expanded', String(open)); };
    button.addEventListener('click', (event) => { event.stopPropagation(); setOpen(panel.hidden); });
    panel.addEventListener('click', (event) => event.stopPropagation());
    document.addEventListener('click', (event) => { if (!menu.contains(event.target)) setOpen(false); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !panel.hidden) { setOpen(false); button.focus(); } });
  });
  document.querySelectorAll('[data-portal-live-search]').forEach((form) => {
    const input = form.querySelector('input[type="search"]');
    if (!input) return;
    let timer = 0;
    input.addEventListener('input', () => { window.clearTimeout(timer); timer = window.setTimeout(() => form.requestSubmit(), 280); });
  });
});
