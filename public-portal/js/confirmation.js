/**
 * College Feedback - Confirmation Page
 * Animations, interactivity, and confetti effect
 */

document.addEventListener('DOMContentLoaded', () => {
  initializeAnimations();
  initializeCampusHelp();
  playConfetti();
  setupAccessibility();
});

// ===== ANIMATIONS =====
function initializeAnimations() {
  // Timeline progress animation
  const timeline = document.querySelector('.timeline');
  if (timeline) {
    const steps = timeline.querySelectorAll('.timeline-item');
    let step = 1;
    
    const fillInterval = setInterval(() => {
      if (step <= 3 && !prefersReducedMotion()) {
        const progressFill = document.querySelector('.timeline-progress-fill');
        const percentage = (step / 3) * 100;
        progressFill.style.width = percentage + '%';
        
        if (step < 3) {
          steps[step].classList.add('completed');
        }
        step++;
      } else {
        clearInterval(fillInterval);
      }
    }, 400);
  }
}

// ===== CAMPUS HELP CHATBOT =====
function initializeCampusHelp() {
  const helpToggle = document.getElementById('helpToggle');
  const helpClose = document.getElementById('helpClose');
  const helpPanel = document.getElementById('helpPanel');
  const helpForm = document.getElementById('helpForm');
  const helpInput = document.getElementById('helpInput');
  const helpMessages = document.getElementById('helpMessages');
  const suggestions = document.querySelectorAll('.help-suggestions button');

  const responses = {
    'How do I submit feedback?': 'You can submit feedback by going to the "Give Feedback" page and filling out the form. It takes just a few minutes!',
    'Is feedback anonymous?': 'Yes! Your feedback can be submitted anonymously. You have the option to include your name if you wish.',
    'What programs are available?': 'Patan Nistha Campus offers BCA (Bachelor of Computer Applications), BBS (Bachelor of Business Studies), and MBS (Master of Business Studies) programs.'
  };

  helpToggle.addEventListener('click', () => {
    helpPanel.hidden = !helpPanel.hidden;
    if (!helpPanel.hidden) {
      helpInput.focus();
    }
  });

  helpClose.addEventListener('click', () => {
    helpPanel.hidden = true;
  });

  suggestions.forEach(btn => {
    btn.addEventListener('click', () => {
      const question = btn.getAttribute('data-question');
      addMessage(question, 'user');
      setTimeout(() => {
        addMessage(responses[question] || 'Thank you for your question!', 'bot');
      }, 300);
    });
  });

  helpForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const question = helpInput.value.trim();
    if (question) {
      addMessage(question, 'user');
      helpInput.value = '';
      
      setTimeout(() => {
        const response = responses[question] || 'Thank you for your question! Please contact us directly for more detailed assistance.';
        addMessage(response, 'bot');
      }, 300);
    }
  });

  function addMessage(text, sender) {
    const msgDiv = document.createElement('div');
    msgDiv.className = `help-message ${sender}`;
    msgDiv.textContent = text;
    helpMessages.appendChild(msgDiv);
    helpMessages.scrollTop = helpMessages.scrollHeight;
  }
}

// ===== CONFETTI EFFECT =====
function playConfetti() {
  if (prefersReducedMotion()) return;

  const canvas = document.getElementById('confetti');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;

  const confettiPieces = [];
  const colors = ['#f5b82e', '#2f8f5b', '#12183a', '#edeff6'];

  class ConfettiPiece {
    constructor() {
      this.x = Math.random() * canvas.width;
      this.y = -10;
      this.size = Math.random() * 8 + 4;
      this.speedY = Math.random() * 3 + 2;
      this.speedX = (Math.random() - 0.5) * 4;
      this.rotate = Math.random() * Math.PI * 2;
      this.rotateSpeed = (Math.random() - 0.5) * 0.2;
      this.color = colors[Math.floor(Math.random() * colors.length)];
      this.opacity = 1;
    }

    update() {
      this.x += this.speedX;
      this.y += this.speedY;
      this.rotate += this.rotateSpeed;
      this.speedY += 0.1; // gravity
      this.opacity -= 0.01;
    }

    draw() {
      ctx.save();
      ctx.globalAlpha = this.opacity;
      ctx.fillStyle = this.color;
      ctx.translate(this.x, this.y);
      ctx.rotate(this.rotate);
      ctx.fillRect(-this.size / 2, -this.size / 2, this.size, this.size);
      ctx.restore();
    }
  }

  function createConfetti() {
    for (let i = 0; i < 60; i++) {
      confettiPieces.push(new ConfettiPiece());
    }
  }

  function animate() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    for (let i = confettiPieces.length - 1; i >= 0; i--) {
      const piece = confettiPieces[i];
      piece.update();
      piece.draw();

      if (piece.opacity <= 0 || piece.y > canvas.height) {
        confettiPieces.splice(i, 1);
      }
    }

    if (confettiPieces.length > 0) {
      requestAnimationFrame(animate);
    }
  }

  // Start confetti after 700ms delay
  setTimeout(() => {
    createConfetti();
    animate();
  }, 700);

  window.addEventListener('resize', () => {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  });
}

// ===== ACCESSIBILITY =====
function setupAccessibility() {
  // Visible focus rings
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Tab') {
      document.body.classList.add('keyboard-nav');
    }
  });

  document.addEventListener('mousedown', () => {
    document.body.classList.remove('keyboard-nav');
  });

  // Focus trap in help panel
  const helpPanel = document.getElementById('helpPanel');
  const helpForm = document.getElementById('helpForm');
  const helpClose = document.getElementById('helpClose');

  helpPanel.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !helpPanel.hidden) {
      helpPanel.hidden = true;
    }
  });
}

function prefersReducedMotion() {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

// Scroll header blur effect
window.addEventListener('scroll', () => {
  const header = document.querySelector('.confirmation-header');
  if (window.scrollY > 10) {
    header.style.backdropFilter = 'blur(12px)';
    header.style.boxShadow = '0 2px 8px rgba(18, 24, 58, 0.08)';
  } else {
    header.style.boxShadow = 'none';
  }
});
