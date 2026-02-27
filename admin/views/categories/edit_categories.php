<?php
if (!isset($category)) {
    echo '<div class="alert alert-danger">Category data missing.</div>';
    return;
}

$isCollectionRoot = in_array(
    strtolower($category->getSlug()),
    ['men', 'ladies', 'unisex'],
    true
);
?>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <h3 class="text-center mb-4 text-info fw-bold">Edit Category</h3>

    <form action="/admin/categories/edit" method="POST">
      <input type="hidden" name="id" value="<?= (int) $category->getId(); ?>">

      <div class="mb-3">
        <label for="categoryName" class="form-label">Category Name</label>
        <input type="text" name="name" id="categoryName" class="form-control"
               value="<?= htmlspecialchars($category->getName()); ?>" required>
      </div>

      <div class="mb-3">
        <label for="parent_id" class="form-label">Collection Group</label>
        <?php if ($isCollectionRoot): ?>
          <input type="text" class="form-control" value="Top-level parent category" readonly>
          <input type="hidden" name="parent_id" value="">
        <?php else: ?>
          <select name="parent_id" id="parent_id" class="form-select" required>
            <option value="">Select collection group</option>
            <?php foreach (($collectionRoots ?? []) as $root): ?>
              <option value="<?= (int) $root['id']; ?>" <?= (int) $category->getParentId() === (int) $root['id'] ? 'selected' : ''; ?>>
                <?= htmlspecialchars($root['label']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label for="status" class="form-label">Status</label>
        <?php if ($isCollectionRoot): ?>
          <input type="text" class="form-control" value="Active (required for collection roots)" readonly>
          <input type="hidden" name="status" value="active">
        <?php else: ?>
          <select name="status" id="status" class="form-select">
            <option value="active" <?= $category->getStatus() === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?= $category->getStatus() === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
          </select>
        <?php endif; ?>
      </div>

      <div class="text-center">
        <button type="submit" class="btn btn-info text-white">Update Category</button>
        <a href="/admin/categories/view" class="btn btn-outline-secondary ms-2">Cancel</a>
      </div>
    </form>
  </div>
</div>
