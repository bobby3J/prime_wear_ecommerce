<?php
// View categories template:
// - Renders filter/search UI
// - Shows category list with status badges
// - Alerts for create/update/delete actions
$filters = $filters ?? [];
$searchQuery = $filters['q'] ?? '';
$selectedStatus = $filters['status'] ?? '';
$pagination = $pagination ?? [
    'page' => 1,
    'per_page' => 10,
    'total' => count($categories ?? []),
    'total_pages' => 1
];
$perPage = (int) ($pagination['per_page'] ?? 10);
$categoryNameById = $categoryNameById ?? [];
$collectionRootLabels = [
    'men' => "Men's Collection",
    'ladies' => "Ladies' Collection",
    'unisex' => 'Couples & Unisex',
];
?>
<?php if ((isset($_GET['created']) && $_GET['created'] == 1) || (isset($_GET['updated']) && $_GET['updated'] == 1)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Category saved successfully!
        <a href="/admin/categories/create" class="btn btn-sm btn-outline-success ms-3">
            Add Another Category
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($deleted)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Category deleted successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h3 class="text-center mb-4 text-info fw-bold">View All Categories</h3>

        <!-- Filter/Search Bar (UI) -->
        <!-- Tip: keep filters in GET so links/bookmarks preserve state -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form id="filterForm" class="row g-3 align-items-center" method="get" action="/admin/categories/view">
                    <!-- Search by category name -->
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search text-secondary"></i>
                            </span>
                            <input
                                type="text"
                                name="q"
                                class="form-control border-start-0 shadow-none"
                                placeholder="Search by category name"
                                value="<?= htmlspecialchars($searchQuery) ?>"
                            >
                        </div>
                    </div>

                    <!-- Filter by status -->
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-funnel text-secondary"></i>
                            </span>
                            <select name="status" class="form-select border-start-0 shadow-none">
                                <option value="">All status</option>
                                <option value="active" <?= $selectedStatus === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $selectedStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Per Page -->
                    <div class="col-md-2">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-list-ol text-secondary"></i>
                            </span>
                            <select name="per_page" class="form-select border-start-0 shadow-none">
                                <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10 / page</option>
                                <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25 / page</option>
                                <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50 / page</option>
                            </select>
                        </div>
                    </div>

                    <!-- Reset button (clear all filters) -->
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-secondary w-100 shadow-sm" data-reset-filters>
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
          document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('filterForm');
            if (!form) return;

            const searchInput = form.querySelector('input[name="q"]');
            const statusSelect = form.querySelector('select[name="status"]');
            const perPageSelect = form.querySelector('select[name="per_page"]');
            const resetButton = form.querySelector('[data-reset-filters]');

            // Debounce search input to avoid frequent submits
            let searchTimer = null;
            const scheduleSubmit = () => {
              if (searchTimer) {
                clearTimeout(searchTimer);
              }
              searchTimer = setTimeout(() => {
                form.submit();
              }, 350);
            };

            if (searchInput) {
              searchInput.addEventListener('input', scheduleSubmit);
            }

            if (statusSelect) {
              statusSelect.addEventListener('change', () => form.submit());
            }
            if (perPageSelect) {
              perPageSelect.addEventListener('change', () => form.submit());
            }

            if (resetButton) {
              resetButton.addEventListener('click', () => {
                if (searchTimer) {
                  clearTimeout(searchTimer);
                }
                if (searchInput) {
                  searchInput.value = '';
                }
                if (statusSelect) {
                  statusSelect.value = '';
                }
                form.submit();
              });
            }
          });
        </script>

        <?php
          $page = (int) ($pagination['page'] ?? 1);
          $total = (int) ($pagination['total'] ?? 0);
          $start = $total > 0 ? (($page - 1) * $perPage + 1) : 0;
          $end = $total > 0 ? min($page * $perPage, $total) : 0;
        ?>
        <div class="text-muted mb-2">
            Showing <?= $start ?>-<?= $end ?> of <?= $total ?> categories
        </div>

        <table class="table table-bordered table-striped align-middle text-center">
            <thead class="table-info text-dark">
                <tr>
                    <th>#</th>
                    <th>Category Name</th>
                    <th>Parent Category</th>
                    <th>Type</th>
                    <th>Created At</th>
                    <th>Actions</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): 
                        // Map status to a bootstrap badge color
                        $status = $category->getStatus();
                        $badgeClass = match ($status) {
                            'active' => 'bg-success',
                            'inactive' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                        $slug = strtolower($category->getSlug());
                        $isCollectionRoot = array_key_exists($slug, $collectionRootLabels);
                        $parentId = $category->getParentId();

                        if ($isCollectionRoot) {
                            $parentLabel = 'Top-level';
                            $typeLabel = 'Parent Category';
                            $typeBadgeClass = 'bg-primary';
                            $displayName = '<span class="fw-semibold">' . htmlspecialchars($category->getName()) . '</span>';
                        } elseif ($parentId !== null && isset($categoryNameById[$parentId])) {
                            $parentLabel = $categoryNameById[$parentId];
                            $typeLabel = 'Subcategory';
                            $typeBadgeClass = 'bg-info';
                            $displayName = '<span>' . htmlspecialchars($category->getName()) . '</span>';
                        } else {
                            $parentLabel = 'Unassigned';
                            $typeLabel = 'Unassigned';
                            $typeBadgeClass = 'bg-secondary';
                            $displayName = '<span>' . htmlspecialchars($category->getName()) . '</span>';
                        }
                    ?>
                        <tr>
                            <td><?= $category->getId() ?></td>
                            <td><?= $displayName ?></td>
                            <td><?= htmlspecialchars($parentLabel) ?></td>
                            <td>
                                <span class="badge <?= $typeBadgeClass ?>">
                                    <?= htmlspecialchars($typeLabel) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($category->getCreatedAt()->format('Y-m-d')) ?></td>
                            <td>
                                <a href="/admin/categories/edit?id=<?= $category->getId() ?>" class="btn btn-sm btn-outline-info me-2">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <a href="/admin/categories/delete?id=<?= $category->getId() ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Delete this category?')">
                                    <i class="fa fa-trash"></i>
                                </a>          
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-muted">No categories found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php
          // Build pagination links while preserving active filters
          $page = (int) ($pagination['page'] ?? 1);
          $totalPages = (int) ($pagination['total_pages'] ?? 1);
          $queryParams = array_filter([
              'q' => $searchQuery !== '' ? $searchQuery : null,
              'status' => $selectedStatus !== '' ? $selectedStatus : null,
              'per_page' => $perPage !== 10 ? $perPage : null
          ], static fn($value) => $value !== null && $value !== '');
        ?>

        <?php if ($totalPages > 1): ?>
        <nav aria-label="Categories pagination" class="mt-3">
          <ul class="pagination justify-content-center">
            <?php
              $prevParams = $queryParams;
              $prevParams['page'] = $page > 1 ? $page - 1 : 1;
              $nextParams = $queryParams;
              $nextParams['page'] = $page < $totalPages ? $page + 1 : $totalPages;
            ?>
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="/admin/categories/view?<?= http_build_query($prevParams) ?>">Previous</a>
            </li>

            <?php
              $range = 2;
              $start = max(1, $page - $range);
              $end = min($totalPages, $page + $range);
            ?>

            <?php if ($start > 1): ?>
              <?php $firstParams = $queryParams; $firstParams['page'] = 1; ?>
              <li class="page-item">
                <a class="page-link" href="/admin/categories/view?<?= http_build_query($firstParams) ?>">1</a>
              </li>
              <?php if ($start > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
              <?php $pageParams = $queryParams; $pageParams['page'] = $i; ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="/admin/categories/view?<?= http_build_query($pageParams) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>

            <?php if ($end < $totalPages): ?>
              <?php if ($end < $totalPages - 1): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>
              <?php $lastParams = $queryParams; $lastParams['page'] = $totalPages; ?>
              <li class="page-item">
                <a class="page-link" href="/admin/categories/view?<?= http_build_query($lastParams) ?>"><?= $totalPages ?></a>
              </li>
            <?php endif; ?>

            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="/admin/categories/view?<?= http_build_query($nextParams) ?>">Next</a>
            </li>
          </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
