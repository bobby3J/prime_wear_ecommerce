
<div class="card border-0 shadow-sm">
  <div class="card-body">
    <h3 class="text-center mb-4 text-info fw-bold">List of Users</h3>

    <!-- Filter/Search Bar -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <form id="filterForm" class="row g-3 align-items-center">

          <!-- Search by Name or Email -->
          <div class="col-md-6">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0">
                <i class="bi bi-search text-secondary"></i>
              </span>
              <input 
                type="text" 
                id="searchUser" 
                class="form-control border-start-0 shadow-none" 
                placeholder="Search by name or email"
              >
            </div>
          </div>

          <!-- Filter by Role -->
          <div class="col-md-4">
            <div class="input-group">
              <span class="input-group-text bg-white border-end-0">
                <i class="bi bi-person-badge text-secondary"></i>
              </span>
              <select id="filterRole" class="form-select border-start-0 shadow-none">
                <option value="" selected hidden>Filter by role</option>
                <option value="Admin">Admin</option>
                <option value="Customer">Customer</option>
                <option value="SuperAdmin">Super Admin</option>
              </select>
            </div>
          </div>

          <!-- Reset Button -->
          <div class="col-md-2">
            <button type="button" class="btn btn-outline-secondary w-100 shadow-sm">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </button>
          </div>

        </form>
      </div>
    </div>

    <!-- Users Table -->
    <table class="table table-bordered table-striped align-middle text-center">
      <thead class="table-info text-dark">
        <tr>
          <th>#</th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Joined</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // Placeholder data (replace with SQL later)
        $users = [
          ['id' => 1, 'fullname' => 'John Doe', 'email' => 'john@example.com', 'role' => 'Admin', 'joined' => '2025-01-15', 'status' => 'Active'],
          ['id' => 2, 'fullname' => 'Jane Smith', 'email' => 'jane@example.com', 'role' => 'Customer', 'joined' => '2025-03-20', 'status' => 'Inactive'],
          ['id' => 3, 'fullname' => 'Michael Brown', 'email' => 'mike@example.com', 'role' => 'SuperAdmin', 'joined' => '2025-02-10', 'status' => 'Active']
        ];

        foreach ($users as $user): 
          $status = $user['status'];
          $badgeClass = match ($status) {
            'Active' => 'bg-success',
            'Inactive' => 'bg-danger',
            default => 'bg-secondary'
          };
        ?>
        <tr>
          <td><?= $user['id'] ?></td>
          <td><?= $user['fullname'] ?></td>
          <td><?= $user['email'] ?></td>
          <td><?= $user['role'] ?></td>
          <td><?= $user['joined'] ?></td>
          <td><span class="badge <?= $badgeClass ?>"><?= $status ?></span></td>
          <td>
            <a href="edit-user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-info me-2">
              <i class="fa fa-edit"></i>
            </a>
            <a href="delete-user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger">
              <i class="fa fa-trash"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

