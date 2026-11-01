<?php

function notifyAllAdministrators(PDO $pdo, string $type, string $title, string $message, ?int $feedbackId = null): void
{
    $stmt = $pdo->prepare('INSERT INTO notifications (feedback_id, notification_type, title, message, admin_id, is_read) SELECT :feedback_id, :type, :title, :message, id, 0 FROM admin');
    $stmt->execute([':feedback_id' => $feedbackId, ':type' => $type, ':title' => $title, ':message' => $message]);
}
