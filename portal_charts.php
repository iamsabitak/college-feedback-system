<?php

function renderPortalPieChart(string $title, array $values, array $colors, string $totalLabel): void
{
    $total = array_sum($values);
    $segments = [];
    $start = 0;
    $index = 0;
    $valueCount = count($values);

    if ($total > 0) {
        foreach ($values as $label => $value) {
            $end = ++$index === $valueCount ? 100 : $start + ((int) $value / $total * 100);
            $color = $colors[$label] ?? '#4a6b57';
            $segments[] = $color . ' ' . number_format($start, 3, '.', '') . '% ' . number_format($end, 3, '.', '') . '%';
            $start = $end;
        }
    }

    $gradient = $segments ? 'conic-gradient(' . implode(', ', $segments) . ')' : '#e8ece8';
    $chartDescription = [];
    foreach ($values as $label => $value) {
        $chartDescription[] = $label . ': ' . $value;
    }
    ?>
    <article class="content-card chart-card">
      <div class="card-header"><h2><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2></div>
      <div class="pie-chart-layout">
        <div class="pie-chart" role="img" aria-label="<?php echo htmlspecialchars($title . ' breakdown. ' . implode(', ', $chartDescription), ENT_QUOTES, 'UTF-8'); ?>" style="background: <?php echo htmlspecialchars($gradient, ENT_QUOTES, 'UTF-8'); ?>">
          <span class="pie-hole"><strong><?php echo $total; ?></strong><small><?php echo htmlspecialchars($totalLabel, ENT_QUOTES, 'UTF-8'); ?></small></span>
        </div>
        <ul class="pie-legend">
          <?php foreach ($values as $label => $value): ?>
            <li>
              <span class="pie-swatch" style="--slice-color: <?php echo htmlspecialchars($colors[$label] ?? '#4a6b57', ENT_QUOTES, 'UTF-8'); ?>"></span>
              <span class="pie-status"><?php echo htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8'); ?></span>
              <strong><?php echo (int) $value; ?></strong>
              <small><?php echo $total ? number_format($value / $total * 100, 1) : '0.0'; ?>%</small>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </article>
    <?php
}