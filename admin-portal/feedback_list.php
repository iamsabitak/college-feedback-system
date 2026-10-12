<?php
/**
 * Reusable feedback list page that includes the dashboard data logic.
 *
 * Application-owned logic for feedback_list.php.
 */
// The dashboard template owns the filtering and table markup. This flag makes
// its overview-only counterpart render the feedback list instead.
$feedbackListOnly = true;
require __DIR__ . '/dashboard.php';
