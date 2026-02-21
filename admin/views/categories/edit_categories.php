<?php
if (!isset($category)) {
    echo '<div class="alert alert-danger">Category data missing.</div>';
    return;
}
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
        <label for="status" class="form-label">Status</label>
        <select name="status" id="status" class="form-select">
          <option value="active" <?= $category->getStatus() === 'active' ? 'selected' : ''; ?>>Active</option>
          <option value="inactive" <?= $category->getStatus() === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
        </select>
      </div>

      <div class="text-center">
        <button type="submit" class="btn btn-info text-white">Update Category</button>
        <a href="/admin/categories/view" class="btn btn-outline-secondary ms-2">Cancel</a>
      </div>
    </form>
  </div>
</div>
