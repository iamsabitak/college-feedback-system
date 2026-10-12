<?php
/**
 * Shows the redesigned feedback submission confirmation state.
 */
$pageTitle = 'Feedback received';
$active = '';
include 'includes/header.php';
?>

<style>
  .feedback-confirmation {
    text-align: center;
    padding: 60px 20px;
    animation: slideInUp 0.5s ease-out;
  }

  @keyframes slideInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .feedback-confirmation-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    background: var(--moss-light);
    color: var(--moss);
    border-radius: 50%;
    font-size: 48px;
    margin-bottom: 20px;
    animation: scaleIn 0.5s ease-out;
  }

  @keyframes scaleIn {
    from {
      transform: scale(0.5);
      opacity: 0;
    }
    to {
      transform: scale(1);
      opacity: 1;
    }
  }

  .feedback-kicker {
    display: block;
    font-size: 12.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--moss);
    margin-bottom: 10px;
  }

  .feedback-confirmation h2 {
    font-family: var(--serif);
    font-size: 42px;
    font-weight: 700;
    margin-bottom: 16px;
    color: var(--navy);
  }

  .feedback-confirmation p {
    font-size: 16px;
    color: var(--ink-soft);
    max-width: 600px;
    margin: 0 auto 30px;
    line-height: 1.6;
  }

  .feedback-alert {
    background: var(--moss-light);
    border: 1px solid #d5e1d7;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 30px;
    color: var(--moss);
    font-weight: 500;
    font-size: 14px;
  }

  .feedback-confirmation-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
  }

  .btn {
    padding: 12px 28px;
    border-radius: 8px;
    font-size: 14.5px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
  }

  .btn-primary {
    background: var(--navy);
    color: var(--white);
  }

  .btn-primary:hover {
    background: var(--navy-deep);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 26, 43, 0.25);
  }

  .btn-outline {
    background: transparent;
    color: var(--navy);
    border: 2px solid var(--navy);
  }

  .btn-outline:hover {
    background: var(--navy);
    color: var(--white);
    transform: translateY(-2px);
  }

  .container {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 20px;
  }
</style>

<section class="feedback-page" aria-labelledby="thank-you-title">
  <div class="container">
    <div class="feedback-confirmation">
      <div>
        <div class="feedback-confirmation-icon" aria-hidden="true">✓</div>
        <span class="feedback-kicker">Feedback received</span>
        <h2 id="thank-you-title">Thank you!</h2>
        <p>Your response has been received securely. It will be reviewed by the campus team and used to make your next experience even better.</p>
        <div class="feedback-alert" role="status">Your feedback has been saved successfully.</div>
        <div class="feedback-confirmation-actions">
          <a href="feedback.php" class="btn btn-outline">Submit another response</a>
          <a href="index.php" class="btn btn-primary">Back to home</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
