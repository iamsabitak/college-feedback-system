<?php

function feedbackFormOptions(PDO $pdo, string $portal): array
{
    $stmt = $pdo->prepare('SELECT option_group, option_value, parent_value FROM feedback_dropdown_options WHERE portal = :portal ORDER BY option_group, parent_value, sort_order');
    $stmt->execute([':portal' => $portal]);
    $options = [];
    foreach ($stmt->fetchAll() as $row) {
        if ($row['option_group'] === 'semesters') $options['semesters'][$row['parent_value']][] = $row['option_value'];
        else $options[$row['option_group']][] = $row['option_value'];
    }
    return $options;
}
