<?php

function renderPortalPagination(int $totalResults, int $page, int $totalPages, int $perPage, array $allowedPageSizes, array $queryParams): void
{
    $offset = ($page - 1) * $perPage;
    $firstResult = $totalResults > 0 ? $offset + 1 : 0;
    $lastResult = min($offset + $perPage, $totalResults);
    $pageUrl = static function (int $targetPage) use ($queryParams, $perPage): string {
        return '?' . htmlspecialchars(http_build_query(array_merge($queryParams, [
            'per_page' => $perPage,
            'page' => $targetPage,
        ])), ENT_QUOTES, 'UTF-8');
    };
    ?>
    <div class="pagination-bar">
      <div class="pagination-left-group">
        <p class="pagination-summary">Showing <?php echo $firstResult; ?> to <?php echo $lastResult; ?> of <?php echo $totalResults; ?> results</p>
        <form method="GET" class="pagination-size-form">
          <?php foreach ($queryParams as $name => $value): if (!in_array((string) $name, ['page', 'per_page'], true)): ?><input type="hidden" name="<?php echo htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; endforeach; ?>
          <input type="hidden" name="page" value="1">
          <label for="portalPerPage">Rows per page:</label>
          <select id="portalPerPage" name="per_page" onchange="this.form.submit()"><?php foreach ($allowedPageSizes as $size): ?><option value="<?php echo $size; ?>" <?php echo $perPage === $size ? 'selected' : ''; ?>><?php echo $size; ?></option><?php endforeach; ?></select>
        </form>
      </div>
      <nav class="pagination-nav" aria-label="List pages">
        <?php if ($page <= 1): ?><span class="pagination-page-btn" aria-disabled="true"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3 4 8l4 5M12 3 8 8l4 5"/></svg></span><span class="pagination-page-btn" aria-disabled="true"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m10 3-5 5 5 5"/></svg></span><?php else: ?><a class="pagination-page-btn" href="<?php echo $pageUrl(1); ?>" aria-label="First page"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3 4 8l4 5M12 3 8 8l4 5"/></svg></a><a class="pagination-page-btn" href="<?php echo $pageUrl($page - 1); ?>" rel="prev" aria-label="Previous page"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m10 3-5 5 5 5"/></svg></a><?php endif; ?>
        <span class="pagination-page-label">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
        <?php if ($page >= $totalPages): ?><span class="pagination-page-btn" aria-disabled="true"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m6 3 5 5-5 5"/></svg></span><span class="pagination-page-btn" aria-disabled="true"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3l4 5-4 5M4 3l4 5-4 5"/></svg></span><?php else: ?><a class="pagination-page-btn" href="<?php echo $pageUrl($page + 1); ?>" rel="next" aria-label="Next page"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m6 3 5 5-5 5"/></svg></a><a class="pagination-page-btn" href="<?php echo $pageUrl($totalPages); ?>" aria-label="Last page"><svg aria-hidden="true" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3l4 5-4 5M4 3l4 5-4 5"/></svg></a><?php endif; ?>
      </nav>
    </div>
    <?php
}