    <?php $environmentName = getenv('APP_ENV') ?: 'Local'; ?>
    <footer class="admin-site-footer">
      <span>&copy; <?php echo date('Y'); ?> Patan Nistha Campus. All Rights Reserved. | College Online Feedback System</span>
    </footer>
  </main>
</div>

<?php include dirname(__DIR__, 2) . '/confirmation_modal.php'; ?>
<script src="js/admin.js"></script>
</body>
</html>
