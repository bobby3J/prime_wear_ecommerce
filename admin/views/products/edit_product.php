<?php
if (!isset($product)) {
    echo '<div class="alert alert-danger">Product data missing.</div>';
    return;
}
?>

<?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        Product updated successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h3 class="text-center mb-4 text-info fw-bold">Edit Product</h3>

        <form action="/admin/products/edit" method="post" enctype="multipart/form-data" class="p-3 bg-light rounded">
            <input type="hidden" name="id" value="<?= (int) $product->getId(); ?>">

            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Product Name</label>
                <input type="text" class="form-control" id="name" name="name"
                       value="<?= htmlspecialchars($product->getName()); ?>" required>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label fw-semi-bold">Price</label>
                <input type="text" class="form-control" id="price" name="price"
                       value="<?= htmlspecialchars((string) $product->getPrice()); ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">Description</label>
                <textarea name="description" id="description" class="form-control" rows="4" required><?= htmlspecialchars($product->getDescription() ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="stock" class="form-label fw-semibold">Stock</label>
                <input type="text" class="form-control" id="stock" name="stock"
                       value="<?= htmlspecialchars((string) $product->getStock()); ?>" required>
            </div>

            <div class="mb-3">
                <label for="category" class="form-label fw-semibold">Category</label>
                <select name="category_id" class="form-select" required>
                    <option value="">Select Category</option>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category->getId(); ?>" <?= $product->getCategoryId() === (int) $category->getId() ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($category->getName()); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Current Image</label>
                <div>
                    <?php if (!empty($imagePath)): ?>
                        <img src="/storage/<?= htmlspecialchars($imagePath); ?>" width="90" class="rounded">
                    <?php else: ?>
                        <span class="text-muted">No image</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label fw-semibold">Replace Image</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                <small class="text-muted">Leave empty to keep current image.</small>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-info text-white px-4">
                    <i class="fa fa-save me-2"></i>Update Product
                </button>
                <a href="/admin/products/view" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
