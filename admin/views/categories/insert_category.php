
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <h3 class="text-center mb-4 text-info fw-bold">Insert Category</h3>

    <form action="/admin/categories/create" method="POST">
      <div class="mb-3">
        <label for="categoryName" class="form-label">Category Name</label>
        <input type="text" name="name" id="categoryName" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="status" class="form-label">Status</label>
        <select name="status" id="status" class="form-select">
          <option value="active" selected>Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>

      <button type="submit" class="btn btn-info text-white">Add Category</button>
    </form>
  </div>
</div>


