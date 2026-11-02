document.addEventListener('DOMContentLoaded', function () {
  const maxFileSize = 5 * 1024 * 1024;
  const allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];

  document.querySelectorAll('[data-feedback-form]').forEach(function (form) {
    const optionSelects = Array.from(form.querySelectorAll('[data-feedback-options]'));
    const optionApi = window.location.pathname.indexOf('/student-portal/') !== -1 ? 'feedback_options.php' : 'feedback_options.php';
    function setOptions(select, values, placeholder) {
      const selected = select.dataset.selected || '';
      select.replaceChildren(new Option(placeholder, ''));
      values.forEach(function (value) { const option = new Option(value, value); option.selected = value === selected; select.add(option); });
      select.disabled = false;
    }
    async function loadFeedbackOptions() {
      try {
        const response = await fetch(optionApi, { headers: { Accept: 'application/json' }, cache: 'no-store' });
        if (!response.ok) throw new Error('Unable to load form options.');
        const options = await response.json();
        optionSelects.forEach(function (select) {
          const key = select.dataset.feedbackOptions;
          if (Array.isArray(options[key])) setOptions(select, options[key], key === 'topics' ? 'Choose a topic' : key === 'categories' ? 'Choose a category' : key === 'roles' ? 'Choose your role' : key === 'departments' ? (select.name === 'department_faculty' ? 'Choose your program' : 'Choose a department') : 'Choose an option');
        });
        initializeSemesters(options.semesters || {});
      } catch (error) {
        optionSelects.forEach(function (select) { select.replaceChildren(new Option('Options unavailable — retry', '')); select.disabled = true; });
        const note = document.createElement('p'); note.className = 'feedback-options-error'; note.textContent = 'Could not load form options. Please refresh and try again.'; form.prepend(note);
      }
    }
    form.querySelectorAll('[data-star-group]').forEach(function (group) {
      const stars = Array.from(group.querySelectorAll('input[type="radio"]'));
      function updateStars() {
        const selected = Number(group.querySelector('input[type="radio"]:checked')?.value || 0);
        stars.forEach(function (input) {
          input.closest('.star-rating-option').classList.toggle('is-selected', Number(input.value) <= selected);
        });
      }
      stars.forEach(function (input) { input.addEventListener('change', updateStars); });
      updateStars();
    });

    function initializeSemesters(semesterOptions) {
    const program = form.querySelector('[data-feedback-program]'); const semester = form.querySelector('[data-feedback-semester]');
    if (program && semester) {
      let savedSemester = semester.dataset.selected || '';
      function populateSemesters() {
        const options = semesterOptions[program.value] || [];
        semester.replaceChildren(new Option('Choose a semester or year', ''));
        options.forEach(function (label) {
          const option = new Option(label, label);
          option.selected = label === savedSemester;
          semester.add(option);
        });
        semester.disabled = options.length === 0;
        if (!options.includes(savedSemester)) savedSemester = '';
      }
      program.addEventListener('change', function () {
        savedSemester = '';
        semester.dataset.selected = '';
        populateSemesters();
      });
      populateSemesters();
    }
    }
    loadFeedbackOptions();

    const attachment = form.querySelector('[data-feedback-attachment]');
    if (attachment) {
      const status = form.querySelector('[data-feedback-attachment-status]');
      attachment.addEventListener('change', function () {
        const file = attachment.files && attachment.files[0];
        attachment.setCustomValidity('');
        if (!file) {
          if (status) status.textContent = '';
          return;
        }
        const extension = file.name.split('.').pop().toLowerCase();
        let error = '';
        if (!allowedExtensions.includes(extension)) error = 'Choose a JPG, JPEG, PNG, or PDF file.';
        else if (file.size > maxFileSize) error = 'The file must be 5 MB or smaller.';
        attachment.setCustomValidity(error);
        if (status) status.textContent = error || file.name + ' (' + (file.size / (1024 * 1024)).toFixed(2) + ' MB)';
      });
    }

    const followUp = form.querySelector('[data-feedback-follow-up]');
    const visibility = form.querySelector('[data-feedback-visibility]');
    const privacyNote = form.querySelector('[data-feedback-privacy-note]');
    function updatePrivacyNotice() {
      if (!privacyNote) return;
      const selectedFollowUp = form.querySelector('[data-feedback-follow-up]:checked');
      const followUpValue = selectedFollowUp ? selectedFollowUp.value : followUp && followUp.value;
      privacyNote.hidden = followUpValue !== '1';
    }
    if (followUp) form.querySelectorAll('[data-feedback-follow-up]').forEach(function (option) {
      option.addEventListener('change', updatePrivacyNotice);
    });
    if (visibility) form.querySelectorAll('[data-feedback-visibility]').forEach(function (option) {
      option.addEventListener('change', updatePrivacyNotice);
    });
    updatePrivacyNotice();
  });
});
