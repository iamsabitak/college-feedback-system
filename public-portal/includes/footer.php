<footer class="site-footer">
  <div class="footer-inner">
    <section class="footer-about">
      <a class="footer-brand" href="index.php">
        <span class="brand-mark small"><img class="brand-logo" src="../assets/logo.svg" alt=""></span>
        <span>College Feedback System<small>Patan Nistha Campus</small></span>
      </a>
      <p>A simple, secure way for students, staff, and visitors to share feedback that helps improve the campus experience.</p>
      <div class="footer-social" aria-label="Campus social links">
        <a href="https://www.facebook.com/lalitpurnist" target="_blank" rel="noopener" aria-label="Patan Nistha Campus on Facebook"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M14 8h3V4h-3c-3.1 0-5 1.9-5 5v3H6v4h3v6h4v-6h3.1l.9-4H13V9c0-.7.3-1 1-1Z" fill="currentColor"/></svg><span>Facebook</span></a>
        <a href="https://www.instagram.com/nistnss/" target="_blank" rel="noopener" aria-label="Patan Nistha Campus on Instagram"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.4" cy="6.6" r=".8" fill="currentColor" stroke="none"/></svg><span>Instagram</span></a>
        <a href="https://www.youtube.com/@nationalcollege5800/featured" target="_blank" rel="noopener" aria-label="Patan Nistha Campus on YouTube"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M21.6 7.2a2.8 2.8 0 0 0-2-2C17.8 4.7 12 4.7 12 4.7s-5.8 0-7.6.5a2.8 2.8 0 0 0-2 2C2 9 2 12 2 12s0 3 .4 4.8a2.8 2.8 0 0 0 2 2c1.8.5 7.6.5 7.6.5s5.8 0 7.6-.5a2.8 2.8 0 0 0 2-2C22 15 22 12 22 12s0-3-.4-4.8ZM10 15.5v-7l6 3.5-6 3.5Z" fill="currentColor"/></svg><span>YouTube</span></a>
      </div>
    </section>

    <nav class="footer-nav" aria-label="Footer navigation">
      <h2>Quick Links</h2>
      <div class="footer-link-grid">
        <a href="index.php">Home</a>
        <a href="about.php">About the System</a>
        <a href="feedback.php">Give Feedback</a>
        <a href="faq.php">FAQ</a>
        <a href="privacy.php">Privacy</a>
        <a href="contact.php">Contact Us</a>
        <a href="../admin-portal/login.php">Admin Login</a>
      </div>
    </nav>

    <section class="footer-contact">
      <h2>Contact Us</h2>
      <a href="https://maps.google.com/?q=Balkumari+Gwarko+Lalitpur" target="_blank" rel="noopener"><span aria-hidden="true">⌖</span>Balkumari, Gwarko, Lalitpur</a>
      <a href="tel:+977015186497"><span aria-hidden="true">☎</span>+977-01-5186497</a>
      <a href="mailto:info@patannistha.edu.np"><span aria-hidden="true">✉</span>info@patannistha.edu.np</a>
      <span class="footer-hours"><span aria-hidden="true">◷</span>Sun–Fri, 6:30 AM–5:00 PM</span>
      <a href="https://patannistha.edu.np/" target="_blank" rel="noopener"><span aria-hidden="true">↗</span>Official College Website</a>
    </section>
  </div>
  <div class="footer-note"><span>&copy; <?php echo date('Y'); ?> Patan Nistha Campus. All Rights Reserved.</span><span>College Online Feedback System</span></div>
</footer>

<section class="chatbot" aria-label="Campus help chat">
  <button class="chatbot-toggle" id="chatbotToggle" type="button" aria-expanded="false" aria-controls="chatbotPanel">
    <span aria-hidden="true">?</span>
    <span>Campus Help</span>
  </button>
  <div class="chatbot-panel" id="chatbotPanel" hidden>
    <div class="chatbot-head">
      <div><strong>Campus Help</strong><span>Quick answers about feedback &amp; campus</span></div>
      <button class="chatbot-close" id="chatbotClose" type="button" aria-label="Close chat">&times;</button>
    </div>
    <div class="chatbot-messages" id="chatbotMessages" aria-live="polite">
      <div class="chat-message bot">Hi! I can help with feedback, anonymity, programs, facilities, or campus contact details.</div>
    </div>
    <div class="chatbot-prompts" aria-label="Suggested questions">
      <button type="button" data-chat-question="How do I submit feedback?">Submit feedback</button>
      <button type="button" data-chat-question="Is feedback anonymous?">Anonymous feedback</button>
      <button type="button" data-chat-question="What programs are available?">Programs</button>
    </div>
    <form class="chatbot-form" id="chatbotForm">
      <label class="sr-only" for="chatbotInput">Ask a question</label>
      <input id="chatbotInput" type="text" autocomplete="off" placeholder="Ask a question...">
      <button type="submit" aria-label="Send question">Send</button>
    </form>
  </div>
</section>

<script src="js/main.js"></script>
</body>
</html>
