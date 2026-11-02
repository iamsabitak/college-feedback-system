<?php

function renderPortalPieChart(string $title, array $values, array $colors, string $totalLabel): void
{
    $total = array_sum($values);
    $chartId = 'portal-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
    $circumference = 301.592894;
    $chartDescription = [];
    foreach ($values as $label => $value) {
        $chartDescription[] = $label . ': ' . $value;
    }
    [$kicker, $summary] = match ($title) {
        'Feedback ratings' => ['Sentiment', 'How you rated your feedback submissions.'],
        'Feedback topics' => ['Topics', 'Your submissions by feedback topic.'],
        'Feedback status' => ['Progress', 'The current status of your submissions.'],
        default => ['Feedback', 'A breakdown of your feedback submissions.'],
    };
    ?>
    <section class="dashboard-chart-panel portal-donut-panel" aria-labelledby="<?php echo htmlspecialchars($chartId, ENT_QUOTES, 'UTF-8'); ?>-title">
      <div class="dashboard-panel-heading"><div><span class="chart-kicker"><?php echo htmlspecialchars($kicker, ENT_QUOTES, 'UTF-8'); ?></span><h2 id="<?php echo htmlspecialchars($chartId, ENT_QUOTES, 'UTF-8'); ?>-title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2><p><?php echo htmlspecialchars($summary, ENT_QUOTES, 'UTF-8'); ?></p></div></div>
      <div class="donut-layout">
        <div class="donut-wrap">
          <svg class="donut-chart" viewBox="0 0 120 120" role="img" aria-label="<?php echo htmlspecialchars($title . ' breakdown. ' . implode(', ', $chartDescription), ENT_QUOTES, 'UTF-8'); ?>">
            <circle class="donut-track" cx="60" cy="60" r="48"/>
            <?php $offset = 0; foreach ($values as $label => $value): $segmentLength = $total > 0 ? ((int) $value / $total) * $circumference : 0; ?><circle class="donut-segment" cx="60" cy="60" r="48" stroke="<?php echo htmlspecialchars($colors[$label] ?? '#4a6b57', ENT_QUOTES, 'UTF-8'); ?>" stroke-dasharray="<?php echo number_format($segmentLength, 3, '.', '') . ' ' . $circumference; ?>" stroke-dashoffset="<?php echo number_format(-$offset, 3, '.', ''); ?>"/><?php $offset += $segmentLength; endforeach; ?>
          </svg>
          <div class="donut-center"><strong><?php echo $total; ?></strong><span><?php echo htmlspecialchars($totalLabel, ENT_QUOTES, 'UTF-8'); ?></span></div>
        </div>
        <ul class="donut-legend">
          <?php foreach ($values as $label => $value): ?>
            <li><span class="legend-dot" style="background:<?php echo htmlspecialchars($colors[$label] ?? '#4a6b57', ENT_QUOTES, 'UTF-8'); ?>"></span><span><strong><?php echo (int) $value; ?></strong> <?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>
    <?php
}