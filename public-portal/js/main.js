// College Online Feedback System — Public Portal client-side behaviour

document.addEventListener('DOMContentLoaded', function () {

  /* ---------- Sticky header shadow ---------- */
  const header = document.getElementById('siteHeader');
  if (header) {
    window.addEventListener('scroll', function () {
      header.classList.toggle('scrolled', window.scrollY > 4);
    });
  }

  /* ---------- Mobile nav toggle ---------- */
  const navToggle = document.getElementById('navToggle');
  const mainNav = document.getElementById('mainNav');
  if (navToggle && mainNav) {
    navToggle.addEventListener('click', function () {
      const open = mainNav.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', open);
    });
  }

  /* ---------- Entrance and count-up motion ---------- */
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const revealItems = document.querySelectorAll('.role-card, .principle-card');
  if (reduceMotion) {
    revealItems.forEach(function (item) { item.classList.add('in-view'); });
  } else if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver(function (entries, observer) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        const siblings = Array.from(entry.target.parentElement.querySelectorAll('.role-card, .principle-card'));
        const delay = Math.max(0, siblings.indexOf(entry.target)) * 80;
        window.setTimeout(function () { entry.target.classList.add('in-view'); }, delay);
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.2 });
    revealItems.forEach(function (item) { revealObserver.observe(item); });
  } else {
    revealItems.forEach(function (item) { item.classList.add('in-view'); });
  }

  const countItems = document.querySelectorAll('[data-count]');
  function countUp(item) {
    const target = Number(item.dataset.count);
    const prefix = item.dataset.prefix || '';
    const suffix = item.dataset.suffix || '';
    if (reduceMotion) { item.textContent = prefix + target + suffix; return; }
    const started = performance.now();
    const duration = 440;
    function tick(now) {
      const progress = Math.min((now - started) / duration, 1);
      item.textContent = prefix + Math.round(target * progress) + suffix;
      if (progress < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }
  if ('IntersectionObserver' in window && !reduceMotion) {
    const countObserver = new IntersectionObserver(function (entries, observer) {
      entries.forEach(function (entry) { if (entry.isIntersecting) { countUp(entry.target); observer.unobserve(entry.target); } });
    }, { threshold: 0.25 });
    countItems.forEach(function (item) { item.textContent = '0'; countObserver.observe(item); });
  } else { countItems.forEach(countUp); }

  /* ---------- Campus help chat ---------- */
  const chatbotToggle = document.getElementById('chatbotToggle');
  const chatbotPanel = document.getElementById('chatbotPanel');
  const chatbotClose = document.getElementById('chatbotClose');
  const chatbotForm = document.getElementById('chatbotForm');
  const chatbotInput = document.getElementById('chatbotInput');
  const chatbotMessages = document.getElementById('chatbotMessages');

  if (chatbotToggle && chatbotPanel && chatbotClose && chatbotForm && chatbotInput && chatbotMessages) {
    function setChatOpen(open) {
      chatbotPanel.hidden = !open;
      chatbotToggle.setAttribute('aria-expanded', String(open));
      if (open) chatbotInput.focus();
    }
    function addMessage(message, type) {
      const bubble = document.createElement('div');
      bubble.className = 'chat-message ' + type;
      bubble.textContent = message;
      chatbotMessages.appendChild(bubble);
      chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }
    function addAnswer(response) {
      addMessage(response.text, 'bot');
      if (!response.action) return;
      const action = document.createElement('a');
      action.className = 'chat-action';
      action.href = response.action.href;
      action.textContent = response.action.label;
      if (response.action.external) { action.target = '_blank'; action.rel = 'noopener'; }
      chatbotMessages.appendChild(action);
      chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }
    function answer(question) {
      const text = question.toLowerCase();
      if (/(feedback|submit|complaint|suggestion)/.test(text)) return { text: 'You can submit feedback in about two minutes: choose your role, select a category, add a 1–5 rating, then write your comment.', action: { label: 'Open feedback form', href: 'feedback.php' } };
      if (/(anonymous|privacy|name|identity)/.test(text)) return { text: 'Yes. Enable “Submit Anonymously” in the form. The name field is disabled before submission, so no name is collected or stored.', action: { label: 'Submit anonymous feedback', href: 'feedback.php' } };
      if (/(program|course|bca|bbs|mbs|study|admission)/.test(text)) return { text: 'Patan Nistha Campus offers BCA, BBS, and MBS programs. The official site has the current admission information and notices.', action: { label: 'View official admissions info', href: 'https://patannistha.edu.np/', external: true } };
      if (/(contact|phone|email|location|address|where|gwarko|lalitpur)/.test(text)) return { text: 'The campus is in Balkumari, Gwarko, Lalitpur. Call +977-01-5186497 or email info@patannistha.edu.np.', action: { label: 'Open contact details', href: 'contact.php' } };
      if (/(hour|open|time|sunday|friday)/.test(text)) return { text: 'Listed campus hours are Sunday to Friday, 6:30 AM to 5:00 PM. It is best to confirm before visiting on holidays.', action: { label: 'View contact details', href: 'contact.php' } };
      if (/(facility|facilities|library|lab|computer)/.test(text)) return { text: 'The campus highlights learning spaces, technology-enabled and computer-based practical learning, library resources, academic guidance, and collaborative learning.', action: { label: 'Explore campus information', href: 'about.php' } };
      return { text: 'Try asking about submitting feedback, staying anonymous, BCA/BBS/MBS programs, facilities, campus hours, or contact details. You can also use the Contact page for anything else.', action: { label: 'Go to Contact', href: 'contact.php' } };
    }
    chatbotToggle.addEventListener('click', function () { setChatOpen(chatbotPanel.hidden); });
    chatbotClose.addEventListener('click', function () { setChatOpen(false); });
    chatbotForm.addEventListener('submit', function (event) {
      event.preventDefault();
      const question = chatbotInput.value.trim();
      if (!question) return;
      addMessage(question, 'user');
      chatbotInput.value = '';
      window.setTimeout(function () { addAnswer(answer(question)); }, 180);
    });
    document.querySelectorAll('[data-chat-question]').forEach(function (button) {
      button.addEventListener('click', function () {
        const question = button.dataset.chatQuestion;
        addMessage(question, 'user');
        window.setTimeout(function () { addAnswer(answer(question)); }, 180);
      });
    });
  }

  /* ---------- FAQ accordion and entrance motion ---------- */
  const faqGroups = document.querySelectorAll('[data-faq-group]');
  const faqQuestions = document.querySelectorAll('.faq-question');
  faqQuestions.forEach(function (question) {
    question.addEventListener('click', function () {
      const answer = document.getElementById(question.getAttribute('aria-controls'));
      const isOpen = question.getAttribute('aria-expanded') === 'true';
      question.setAttribute('aria-expanded', String(!isOpen));
      if (isOpen) {
        window.clearTimeout(Number(answer.dataset.closeTimer));
        answer.dataset.closeTimer = window.setTimeout(function () { answer.hidden = true; }, reduceMotion ? 0 : 350);
      } else {
        window.clearTimeout(Number(answer.dataset.closeTimer));
        answer.hidden = false;
      }
    });
  });
  document.querySelectorAll('[data-helpful]').forEach(function (button) {
    button.addEventListener('click', function () {
      const wrap = button.closest('.helpful');
      wrap.querySelectorAll('[data-helpful]').forEach(function (item) { item.classList.remove('selected'); });
      button.classList.add('selected');
      const note = wrap.querySelector('.helpful-note');
      note.textContent = 'Thanks for letting us know.';
    });
  });
  if (faqGroups.length) {
    if (reduceMotion || !('IntersectionObserver' in window)) {
      faqGroups.forEach(function (group) { group.classList.add('in-view'); });
    } else {
      const faqObserver = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) { entry.target.classList.add('in-view'); observer.unobserve(entry.target); }
        });
      }, { threshold: 0.14 });
      faqGroups.forEach(function (group) { faqObserver.observe(group); });
    }
  }

  /* ---------- Privacy policy reading progress and calm reveals ---------- */
  const progressBar = document.getElementById('readingProgressBar');
  if (progressBar) {
    function updateReadingProgress() {
      const range = document.documentElement.scrollHeight - window.innerHeight;
      progressBar.style.width = (range > 0 ? Math.min(window.scrollY / range, 1) * 100 : 0) + '%';
    }
    window.addEventListener('scroll', updateReadingProgress, { passive: true });
    updateReadingProgress();
  }
  const policySections = document.querySelectorAll('[data-policy-section]');
  if (policySections.length) {
    if (reduceMotion || !('IntersectionObserver' in window)) {
      policySections.forEach(function (section) { section.classList.add('in-view'); });
    } else {
      const policyObserver = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) { entry.target.classList.add('in-view'); observer.unobserve(entry.target); }
        });
      }, { threshold: 0.16 });
      policySections.forEach(function (section) { policyObserver.observe(section); });
    }
  }

  /* ---------- Contact form validation ---------- */
  const contactForm = document.querySelector('[data-contact-form]');
  if (contactForm) {
    const contactFields = Array.from(contactForm.querySelectorAll('[data-contact-field]'));

    function clearContactError(field) {
      field.classList.remove('has-error');
      field.querySelector('.error-text').textContent = '';
      field.querySelector('input, textarea').setAttribute('aria-invalid', 'false');
    }

    contactFields.forEach(function (field) {
      const input = field.querySelector('input, textarea');
      input.addEventListener('input', function () {
        if (input.value.trim() && input.validity.valid) clearContactError(field);
      });
    });

    contactForm.addEventListener('submit', function (event) {
      let firstInvalid = null;
      contactFields.forEach(function (field) {
        const input = field.querySelector('input, textarea');
        clearContactError(field);
        let message = '';
        if (!input.value.trim()) {
          message = 'This field is required.';
        } else if (input.type === 'email' && !input.validity.valid) {
          message = 'Please enter a valid email address.';
        }
        if (message) {
          field.classList.add('has-error');
          field.querySelector('.error-text').textContent = message;
          input.setAttribute('aria-invalid', 'true');
          if (!firstInvalid) firstInvalid = input;
        }
      });

      if (firstInvalid) {
        event.preventDefault();
        firstInvalid.focus();
        return;
      }

      const submitButton = contactForm.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Sending...';
      }
    });
  }

  /* ================= FEEDBACK FORM ================= */
  const form = document.getElementById('feedbackForm');
  if (!form) return;

  // Role -> category list (Section 6.1 / Module 1: role-based category list)
  const categoriesByRole = {
    'Student': ['Teachers', 'Courses', 'Library', 'Canteen', 'Laboratories', 'Other'],
    'Teacher & Staff': ['Facilities', 'Administration', 'Internal Processes', 'Other'],
    'Visitor & Outsider': ['Overall Impression', 'Admissions', 'Campus', 'Events', 'Other']
  };

  const rolePills = document.querySelectorAll('.role-pill');
  const roleRadios = document.querySelectorAll('input[name="submitter_type"]');
  const categorySelect = document.getElementById('category');
  const nameField = document.getElementById('nameField');
  const nameInput = document.getElementById('submitter_name');
  const anonToggle = document.getElementById('is_anonymous');

  function populateCategories(role) {
    categorySelect.innerHTML = '<option value="" disabled selected>Select a category</option>';
    (categoriesByRole[role] || []).forEach(function (cat) {
      const opt = document.createElement('option');
      opt.value = cat;
      opt.textContent = cat;
      categorySelect.appendChild(opt);
    });
    categorySelect.disabled = false;
  }

  rolePills.forEach(function (pill) {
    pill.addEventListener('click', function () {
      rolePills.forEach(function (p) { p.classList.remove('selected'); });
      pill.classList.add('selected');
      pill.classList.remove('pop');
      void pill.offsetWidth;
      pill.classList.add('pop');
      const radio = pill.querySelector('input[type="radio"]');
      radio.checked = true;
      populateCategories(radio.value);
      clearError('submitter_type');
      updateFormProgress();
    });
  });

  // If a role was already chosen (e.g. validation re-render), populate categories
  roleRadios.forEach(function (r) {
    if (r.checked) populateCategories(r.value);
  });

  /* ---------- Anonymous toggle: actually removes the name field ---------- */
  function applyAnonState() {
    if (anonToggle.checked) {
      nameField.classList.add('hidden');
      nameInput.value = '';
      nameInput.disabled = true; // ensures nothing is submitted even if CSS fails
      nameInput.required = false;
    } else {
      nameField.classList.remove('hidden');
      nameInput.disabled = false;
      nameInput.required = true;
    }
  }
  if (anonToggle) {
    anonToggle.addEventListener('change', applyAnonState);
    applyAnonState();
  }

  /* ---------- Star rating ---------- */
  const stars = document.querySelectorAll('.star');
  const ratingInput = document.getElementById('rating');

  /* ---------- Feedback form progress and live guidance ---------- */
  const ratingLabel = document.getElementById('ratingLabel');
  const characterCount = document.getElementById('characterCount');
  const messageInput = document.getElementById('message');
  const progressTrack = document.querySelector('.progress-track');
  const progressFill = document.querySelector('.progress-fill');
  const progressSummary = document.querySelector('.progress-summary');
  const progressLabels = {
    identity: document.querySelector('[data-progress="identity"]'),
    rating: document.querySelector('[data-progress="rating"]'),
    comment: document.querySelector('[data-progress="comment"]'),
    privacy: document.querySelector('[data-progress="privacy"]')
  };
  const ratingDescriptions = ['', 'Very Poor', 'Poor', 'Fair', 'Very Good', 'Excellent'];

  function updateFormProgress() {
    const identityComplete = !!document.querySelector('input[name="submitter_type"]:checked') && !!categorySelect.value;
    const ratingComplete = !!ratingInput.value;
    const commentComplete = messageInput.value.trim().length >= 5;
    const privacyComplete = anonToggle.checked || nameInput.value.trim().length > 0;
    const completion = { identity: identityComplete, rating: ratingComplete, comment: commentComplete, privacy: privacyComplete };
    let completed = 0;

    Object.keys(completion).forEach(function (section) {
      progressLabels[section].classList.toggle('completed', completion[section]);
      if (completion[section]) completed++;
    });
    progressTrack.setAttribute('aria-valuenow', completed);
    progressFill.style.width = (completed / 4 * 100) + '%';
    progressSummary.textContent = completed + ' of 4 sections complete';
  }

  function updateRatingLabel() {
    const value = parseInt(ratingInput.value, 10);
    ratingLabel.textContent = value ? value + ' out of 5 — ' + ratingDescriptions[value] : 'Select a rating';
  }

  function updateCharacterCount() {
    characterCount.textContent = messageInput.value.length + ' / 2000';
    characterCount.classList.toggle('near-limit', messageInput.value.length >= 1800);
  }

  const savedRating = parseInt(ratingInput.value, 10) || 0;
  stars.forEach(function (star) {
    star.classList.toggle('filled', parseInt(star.dataset.value, 10) <= savedRating);
  });
  categorySelect.addEventListener('change', updateFormProgress);
  messageInput.addEventListener('input', function () {
    updateCharacterCount();
    updateFormProgress();
  });
  anonToggle.addEventListener('change', updateFormProgress);
  nameInput.addEventListener('input', updateFormProgress);
  updateRatingLabel();
  updateCharacterCount();
  updateFormProgress();

  stars.forEach(function (star) {
    star.addEventListener('click', function () {
      const val = parseInt(star.dataset.value, 10);
      ratingInput.value = val;
      stars.forEach(function (s) {
        s.classList.toggle('filled', parseInt(s.dataset.value, 10) <= val);
      });
      star.classList.remove('pulse');
      void star.offsetWidth;
      star.classList.add('pulse');
      clearError('rating');
    });
  });
  stars.forEach(function (star) {
    star.addEventListener('click', function () {
      updateRatingLabel();
      updateFormProgress();
    });
  });

  /* ---------- Client-side validation (Section 6.1) ---------- */
  function showError(fieldName, message) {
    const field = document.querySelector('[data-field="' + fieldName + '"]');
    if (!field) return;
    field.classList.add('has-error');
    const err = field.querySelector('.error-text');
    if (err) err.textContent = message;
  }
  function clearError(fieldName) {
    const field = document.querySelector('[data-field="' + fieldName + '"]');
    if (!field) return;
    field.classList.remove('has-error');
  }

  form.addEventListener('submit', function (e) {
    let valid = true;
    document.querySelectorAll('.field').forEach(function (f) { f.classList.remove('has-error'); });

    const roleChosen = document.querySelector('input[name="submitter_type"]:checked');
    if (!roleChosen) { showError('submitter_type', 'Please select who you are.'); valid = false; }

    if (!categorySelect.value) { showError('category', 'Please choose a category.'); valid = false; }

    if (!ratingInput.value || parseInt(ratingInput.value, 10) < 1) {
      showError('rating', 'Please select a star rating.'); valid = false;
    }

    const message = document.getElementById('message');
    if (!message.value.trim() || message.value.trim().length < 5) {
      showError('message', 'Please write at least a short comment (5+ characters).'); valid = false;
    }

    if (!anonToggle.checked && nameInput.value.trim().length === 0) {
      showError('submitter_name', 'Please enter your name or choose anonymous submission.'); valid = false;
    }

    if (!valid) {
      e.preventDefault();
      const firstError = document.querySelector('.field.has-error');
      if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      const submitButton = form.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.classList.add('is-loading');
        submitButton.disabled = true;
        submitButton.dataset.originalText = submitButton.textContent;
        submitButton.textContent = 'Submitting…';
      }
    }
  });
});
