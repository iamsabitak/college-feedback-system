<?php
/** Shared confirmation dialog for consequential portal actions. */
?>
<section class="action-confirm-modal" id="actionConfirmModal" role="dialog" aria-modal="true" aria-labelledby="actionConfirmTitle" hidden>
  <div class="action-confirm-backdrop" data-action-confirm-cancel></div>
  <div class="action-confirm-card">
    <h2 id="actionConfirmTitle">Confirm action</h2>
    <p id="actionConfirmMessage">Are you sure you want to continue?</p>
    <div class="action-confirm-actions"><button type="button" class="btn btn-outline" data-action-confirm-cancel>Cancel</button><button type="button" class="btn btn-primary" id="actionConfirmAccept">Confirm</button></div>
  </div>
</section>
<script>
(function () {
  var modal = document.getElementById('actionConfirmModal');
  if (!modal || modal.dataset.initialized) return;
  modal.dataset.initialized = 'true';
  var title = document.getElementById('actionConfirmTitle');
  var message = document.getElementById('actionConfirmMessage');
  var accept = document.getElementById('actionConfirmAccept');
  var opener = null, callback = null, pendingForm = null, pendingSubmitter = null, parentDialog = null;
  function close(restoreParentDialog) {
    modal.hidden = true; callback = null; pendingForm = null; pendingSubmitter = null;
    if (restoreParentDialog && parentDialog && !parentDialog.open) parentDialog.showModal();
    parentDialog = null;
    if (opener) opener.focus();
  }
  window.openActionConfirmation = function (options, onAccept) {
    opener = document.activeElement; callback = onAccept || null;
    title.textContent = options.title || 'Confirm action'; message.textContent = options.message || 'Are you sure you want to continue?';
    accept.textContent = options.confirmLabel || 'Confirm'; accept.className = 'btn ' + (options.danger ? 'btn-danger' : 'btn-primary');
    modal.hidden = false; accept.focus();
  };
  document.querySelectorAll('[data-table-status-form], [data-status-form]').forEach(function (form) {
    form.dataset.confirm = ''; form.dataset.confirmTitle = 'Update feedback status?';
    form.dataset.confirmMessage = 'This changes the feedback status for everyone who can view it.';
    form.dataset.confirmLabel = 'Update status';
  });
  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.matches('[data-confirm]') || form.dataset.confirmed === 'true') { if (form.dataset.confirmed) delete form.dataset.confirmed; return; }
    event.preventDefault(); pendingForm = form; pendingSubmitter = event.submitter || null;
    parentDialog = form.closest('dialog');
    // A native dialog is in the browser's top layer, above every z-index.
    // Close it while the shared confirmation is displayed, then restore it on cancel.
    if (parentDialog && parentDialog.open) parentDialog.close();
    window.openActionConfirmation({ title: form.dataset.confirmTitle, message: form.dataset.confirmMessage, confirmLabel: form.dataset.confirmLabel, danger: form.dataset.confirmDanger === 'true' }, function () {
      form.dataset.confirmed = 'true'; form.requestSubmit(pendingSubmitter || undefined);
    });
  }, true);
  accept.addEventListener('click', function () { var run = callback; close(false); if (run) run(); });
  modal.querySelectorAll('[data-action-confirm-cancel]').forEach(function (button) { button.addEventListener('click', close); });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !modal.hidden) close(); });
}());
</script>
