<?php
// View products template:
// - Reads filters + pagination from controller
// - Renders filter bar that auto-submits
// - Renders product table + detail modals
// - Renders pagination links while preserving filters
$filters = $filters ?? [];
$searchQuery = $filters['q'] ?? '';
$selectedCategoryId = (int) ($filters['category_id'] ?? 0);
$selectedStockLevel = $filters['stock_level'] ?? '';
$priceMin = $filters['price_min'] ?? '';
$priceMax = $filters['price_max'] ?? '';
$selectedSort = $filters['sort'] ?? '';
$lowStockThreshold = (int) ($filters['low_stock_threshold'] ?? 5);
$categories = $categories ?? [];
$pagination = $pagination ?? [
    'page' => 1,
    'per_page' => 10,
    'total' => count($products ?? []),
    'total_pages' => 1
];
// Pagination defaults when controller doesn't supply them
// View helpers to keep the template readable
$perPage = (int) ($pagination['per_page'] ?? 10);


if ((isset($_GET['created']) && $_GET['created'] == 1) || (isset($_GET['success']) && $_GET['success'] == 1) || (isset($_GET['updated']) && $_GET['updated'] == 1)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Product saved successfully!
        <a href="/admin/products/create" class="btn btn-sm btn-outline-success ms-3">
            Add Another Product
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($deleted)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Product deleted successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h3 class="text-center mb-4 text-info fw-bold">View All Products</h3>

       <!-- Filter/search bar (auto-submits on change) -->
       <!-- Tip: for other projects, keep filters in GET so links/bookmarks preserve state -->
<form id="filterForm" class="row g-3 align-items-center mb-3 mt-2" style="margin-top: 0 !important;" method="get" action="/admin/products/view">
  <!-- Search by Product Name -->
  <!-- Using a clear button instead of a reset keeps UI minimal but still reversible -->
  <div class="col-md-4 position-relative">
    <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
    <input 
      type="text" 
      name="q"
      class="form-control ps-5" 
      placeholder="Search by product name"
      value="<?= htmlspecialchars($searchQuery) ?>"
    >
    <button type="button" class="btn btn-sm btn-outline-secondary position-absolute top-50 end-0 translate-middle-y me-2" data-clear-search>
      <i class="fas fa-times"></i>
    </button>
</div>
  <!-- Filter by Category -->
  <!-- Category options come from $categories passed by controller -->
  <div class="col-md-3 position-relative">
    <i class="fas fa-tags position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
    <select name="category_id" class="form-select ps-5">
      <option value="">All categories</option>
      <?php foreach ($categories as $category): ?>
        <option value="<?= $category->getId(); ?>" <?= $selectedCategoryId === (int) $category->getId() ? 'selected' : '' ?>>
          <?= htmlspecialchars($category->getName()) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- Filter by Stock Level -->
  <!-- Low stock threshold label uses $lowStockThreshold -->
  <div class="col-md-3 position-relative">
    <i class="fas fa-boxes position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
    <select name="stock_level" class="form-select ps-5">
      <option value="">All stock</option>
      <option value="in_stock" <?= $selectedStockLevel === 'in_stock' ? 'selected' : '' ?>>In stock</option>
      <option value="low_stock" <?= $selectedStockLevel === 'low_stock' ? 'selected' : '' ?>>Low stock (<= <?= $lowStockThreshold ?>)</option>
      <option value="out_of_stock" <?= $selectedStockLevel === 'out_of_stock' ? 'selected' : '' ?>>Out of stock</option>
    </select>
  </div>

  <!-- Sort -->
  <!-- Sort affects ordering, not which rows are shown -->
  <div class="col-md-2 position-relative">
    <i class="fas fa-sort position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
    <select name="sort" class="form-select ps-5">
      <option value="">Sort</option>
      <option value="name_asc" <?= $selectedSort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
      <option value="name_desc" <?= $selectedSort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
      <option value="price_asc" <?= $selectedSort === 'price_asc' ? 'selected' : '' ?>>Price (Low-High)</option>
      <option value="price_desc" <?= $selectedSort === 'price_desc' ? 'selected' : '' ?>>Price (High-Low)</option>
      <option value="stock_asc" <?= $selectedSort === 'stock_asc' ? 'selected' : '' ?>>Stock (Low-High)</option>
      <option value="stock_desc" <?= $selectedSort === 'stock_desc' ? 'selected' : '' ?>>Stock (High-Low)</option>
    </select>
  </div>

  <!-- Per Page -->
  <div class="col-md-2 position-relative">
    <i class="fas fa-list position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
    <select name="per_page" class="form-select ps-5">
      <option value="10" <?= $perPage === 10 ? 'selected' : '' ?>>10 / page</option>
      <option value="25" <?= $perPage === 25 ? 'selected' : '' ?>>25 / page</option>
      <option value="50" <?= $perPage === 50 ? 'selected' : '' ?>>50 / page</option>
    </select>
  </div>

  <!-- Price Range -->
  <!-- Numeric inputs allow quick min/max filtering without a separate modal -->
  <div class="col-md-3 position-relative">
    <i class="fas fa-dollar-sign position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
    <input
      type="number"
      name="price_min"
      class="form-control ps-5"
      placeholder="Min price"
      step="0.01"
      min="0"
      value="<?= htmlspecialchars((string) $priceMin) ?>"
    >
  </div>

  <div class="col-md-3 position-relative">
    <i class="fas fa-dollar-sign position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
    <input
      type="number"
      name="price_max"
      class="form-control ps-5"
      placeholder="Max price"
      step="0.01"
      min="0"
      value="<?= htmlspecialchars((string) $priceMax) ?>"
    >
  </div>
</form>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('filterForm');
    if (!form) return;

    const searchInput = form.querySelector('input[name="q"]');
    const categorySelect = form.querySelector('select[name="category_id"]');
    const stockSelect = form.querySelector('select[name="stock_level"]');
    const sortSelect = form.querySelector('select[name="sort"]');
    const perPageSelect = form.querySelector('select[name="per_page"]');
    const priceMinInput = form.querySelector('input[name="price_min"]');
    const priceMaxInput = form.querySelector('input[name="price_max"]');
    const clearSearchBtn = form.querySelector('[data-clear-search]');

    // Debounce to avoid submitting on every keystroke
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

    if (priceMinInput) {
      priceMinInput.addEventListener('input', scheduleSubmit);
    }

    if (priceMaxInput) {
      priceMaxInput.addEventListener('input', scheduleSubmit);
    }

    // Clear button resets all filters at once
    if (clearSearchBtn && searchInput) {
      clearSearchBtn.addEventListener('click', () => {
        if (searchTimer) {
          clearTimeout(searchTimer);
        }
        searchInput.value = '';
        if (categorySelect) {
          categorySelect.value = '';
        }
        if (stockSelect) {
          stockSelect.value = '';
        }
        if (sortSelect) {
          sortSelect.value = '';
        }
        if (priceMinInput) {
          priceMinInput.value = '';
        }
        if (priceMaxInput) {
          priceMaxInput.value = '';
        }
        form.submit();
      });
    }

    // Auto-apply on dropdown changes
    if (categorySelect) {
      categorySelect.addEventListener('change', () => form.submit());
    }
    if (stockSelect) {
      stockSelect.addEventListener('change', () => form.submit());
    }
    if (sortSelect) {
      sortSelect.addEventListener('change', () => form.submit());
    }
    if (perPageSelect) {
      perPageSelect.addEventListener('change', () => form.submit());
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
  Showing <?= $start ?>-<?= $end ?> of <?= $total ?> products
</div>

        <table class="table table-bordered table-striped align-middle text-center">
            <thead class="table-info text-dark">
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price ($)</th>
                    <th>Stock</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
<tbody>
<?php if (empty($products)): ?>
<tr>
    <td colspan="7" class="text-muted">No products found.</td>
</tr>
<?php else: ?>
<?php foreach ($products as $product): ?>
<tr>
    <td><?= $product->id ?></td>
    <td><?= htmlspecialchars($product->name) ?></td>
    <td><?= htmlspecialchars($product->categoryName) ?></td>
    <td>$<?= number_format($product->price, 2) ?></td>
    <td><?= (int) $product->stock ?></td>
    <td>
        <?php if ($product->imagePath): ?>
            <img src="/storage/<?= $product->imagePath ?>" width="60" class="rounded">
        <?php else: ?>
            <span class="text-muted">No image</span>
        <?php endif; ?>
    </td>
    <td>
        <button type="button" class="btn btn-sm btn-outline-secondary me-2"
                data-bs-toggle="modal" data-bs-target="#productModal-<?= $product->id ?>">
            <i class="fa fa-eye"></i>
        </button>
        <a href="/admin/products/edit?id=<?= $product->id ?>" class="btn btn-sm btn-outline-info">
            <i class="fa fa-edit"></i>
        </a>
        <a href="/admin/products/delete?id=<?= $product->id ?>" class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Delete this product?')">
            <i class="fa fa-trash"></i>
        </a>
    </td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>

        </table>

        <?php
          // Build pagination links while preserving active filters
          $page = (int) ($pagination['page'] ?? 1);
          $totalPages = (int) ($pagination['total_pages'] ?? 1);
          $queryParams = array_filter([
              'q' => $searchQuery,
              'category_id' => $selectedCategoryId > 0 ? $selectedCategoryId : null,
              'stock_level' => $selectedStockLevel !== '' ? $selectedStockLevel : null,
              'price_min' => $priceMin !== '' ? $priceMin : null,
              'price_max' => $priceMax !== '' ? $priceMax : null,
              'sort' => $selectedSort !== '' ? $selectedSort : null,
              'per_page' => $perPage !== 10 ? $perPage : null
          ], static fn($value) => $value !== null && $value !== '');
        ?>

        <?php if ($totalPages > 1): ?>
        <!-- Pagination stays on server side; links include filters so state is preserved -->
        <nav aria-label="Products pagination" class="mt-3">
          <ul class="pagination justify-content-center">
            <?php
              $prevParams = $queryParams;
              $prevParams['page'] = $page > 1 ? $page - 1 : 1;
              $nextParams = $queryParams;
              $nextParams['page'] = $page < $totalPages ? $page + 1 : $totalPages;
            ?>
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="/admin/products/view?<?= http_build_query($prevParams) ?>">Previous</a>
            </li>

            <?php
              $range = 2;
              $start = max(1, $page - $range);
              $end = min($totalPages, $page + $range);
            ?>

            <?php if ($start > 1): ?>
              <?php $firstParams = $queryParams; $firstParams['page'] = 1; ?>
              <li class="page-item">
                <a class="page-link" href="/admin/products/view?<?= http_build_query($firstParams) ?>">1</a>
              </li>
              <?php if ($start > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
              <?php $pageParams = $queryParams; $pageParams['page'] = $i; ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="/admin/products/view?<?= http_build_query($pageParams) ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>

            <?php if ($end < $totalPages): ?>
              <?php if ($end < $totalPages - 1): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
              <?php endif; ?>
              <?php $lastParams = $queryParams; $lastParams['page'] = $totalPages; ?>
              <li class="page-item">
                <a class="page-link" href="/admin/products/view?<?= http_build_query($lastParams) ?>"><?= $totalPages ?></a>
              </li>
            <?php endif; ?>

            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="/admin/products/view?<?= http_build_query($nextParams) ?>">Next</a>
            </li>
          </ul>
        </nav>
        <?php endif; ?>

        <?php foreach ($products as $product): ?>
        <!-- Per-product modal for quick detail view -->
        <div class="modal fade" id="productModal-<?= $product->id ?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars($product->name) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="d-flex align-items-start gap-3">
                  <div style="min-width: 90px;">
                    <?php if ($product->imagePath): ?>
                      <img src="/storage/<?= $product->imagePath ?>" width="90" class="rounded">
                    <?php else: ?>
                      <div class="text-muted small">No image</div>
                    <?php endif; ?>
                  </div>
                  <div>
                    <div class="mb-2"><strong>Category:</strong> <?= htmlspecialchars($product->categoryName) ?></div>
                    <div class="mb-2"><strong>Price:</strong> $<?= number_format($product->price, 2) ?></div>
                    <div class="mb-2"><strong>Stock:</strong> <?= (int) $product->stock ?></div>
                    <div><strong>Description:</strong> <?= htmlspecialchars($product->description ?? 'No description') ?></div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
