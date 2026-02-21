

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        Product inserted successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h3 class="text-center mb-4 text-info fw-bold">Insert New Product</h3>
        <form action="/admin/products/create" method="post" enctype="multipart/form-data" class="p-3 bg-light rounded">
            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Product Name</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Enter product name" required>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label fw-semi-bold">Price</label>
                <input type="text" class="form-control" id="price" name="price" placeholder="Enter product price" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">Description</label>
                <textarea name="description" id="description" class="form-control" rows="4" placeholder="Enter product description" required></textarea>
            </div>

            <div class="mb-3">
                <label for="stock" class="form-label fw-semibold">Stock</label>
                <input type="text" class="form-control" id="stock" name="stock" placeholder="Enter product stock" required>
            </div>

        <div class="mb-3">
            <label for="category" class="form-label fw-semibold">Category</label>
            <select name="category_id" class="form-select" required>
                <option value="">Select Category</option>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category->getId(); ?>">
                            <?= htmlspecialchars($category->getName()); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>


            <div class="mb-3">
                <label for="image" class="form-label fw-semibold">Product</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-info text-white px-4">
                    <i class="fa fa-save me-2"></i>Insert Product
                </button>
            </div>
        </form>
    </div>
</div>


