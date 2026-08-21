<?php
// modules/users.php — AYON BHAI — User Management (Admin Only)
require_once '../includes/config.php';
requireRole('admin');

$pageTitle = "User Management";
$root = "../";

// ── ADD USER ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $full_name = clean($_POST['full_name']);
    $username  = clean($_POST['username']);
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role      = clean($_POST['role']);
    $email     = clean($_POST['email']);
    $phone     = clean($_POST['phone']);

    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'");
    if (mysqli_num_rows($check) > 0) {
        setMessage('error', "Username '$username' already exists!");
    } else {
        mysqli_query($conn, "INSERT INTO users (full_name,username,password,role,email,phone) VALUES ('$full_name','$username','$password','$role','$email','$phone')");
        setMessage('success', "✅ User '$full_name' added successfully!");
    }
    header("Location: users.php"); exit();
}

// ── EDIT USER ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {
    $id        = (int)$_POST['id'];
    $full_name = clean($_POST['full_name']);
    $role      = clean($_POST['role']);
    $email     = clean($_POST['email']);
    $phone     = clean($_POST['phone']);
    $status    = clean($_POST['status']);

    $sql = "UPDATE users SET full_name='$full_name',role='$role',email='$email',phone='$phone',status='$status' WHERE id=$id";
    if (!empty($_POST['password'])) {
        $pw = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE users SET full_name='$full_name',role='$role',email='$email',phone='$phone',status='$status',password='$pw' WHERE id=$id";
    }
    mysqli_query($conn, $sql);
    setMessage('success', "✅ User updated successfully!");
    header("Location: users.php"); exit();
}

// ── DELETE USER ──
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id == $_SESSION['user_id']) {
        setMessage('error', "❌ You cannot delete your own account!");
    } else {
        mysqli_query($conn, "DELETE FROM users WHERE id=$id");
        setMessage('success', "🗑️ User deleted.");
    }
    header("Location: users.php"); exit();
}

// ── FETCH EDIT USER ──
$editUser = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $editUser = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$id"));
}

// ── ALL USERS ──
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY role, full_name");

include '../includes/header.php';
?>

<?= getMessage() ?>

<!-- ADD USER FORM -->
<div class="card">
  <div class="card-title"><?= $editUser ? '✏️ Edit User' : '➕ Add New User' ?></div>
  <form method="POST">
    <input type="hidden" name="action" value="<?= $editUser ? 'edit' : 'add' ?>">
    <?php if ($editUser): ?><input type="hidden" name="id" value="<?= $editUser['id'] ?>"><?php endif; ?>

    <div class="form-grid">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="full_name" required value="<?= htmlspecialchars($editUser['full_name'] ?? '') ?>">
      </div>
      <?php if (!$editUser): ?>
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" required>
      </div>
      <?php endif; ?>
      <div class="form-group">
        <label><?= $editUser ? 'New Password (leave blank to keep)' : 'Password' ?></label>
        <input type="password" name="password" <?= $editUser ? '' : 'required' ?> placeholder="<?= $editUser ? 'Leave blank to keep current' : 'Min 6 characters' ?>">
      </div>
      <div class="form-group">
        <label>Role</label>
        <select name="role" required>
          <option value="pharmacist" <?= ($editUser['role']??'')==='pharmacist'?'selected':'' ?>>Pharmacist</option>
          <option value="manager"    <?= ($editUser['role']??'')==='manager'?'selected':'' ?>>Manager</option>
          <option value="admin"      <?= ($editUser['role']??'')==='admin'?'selected':'' ?>>Admin</option>
        </select>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($editUser['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($editUser['phone'] ?? '') ?>">
      </div>
      <?php if ($editUser): ?>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <option value="active"   <?= ($editUser['status']==='active')?'selected':'' ?>>Active</option>
          <option value="inactive" <?= ($editUser['status']==='inactive')?'selected':'' ?>>Inactive</option>
        </select>
      </div>
      <?php endif; ?>
    </div>

    <div class="flex gap-2 mt-3">
      <button type="submit" class="btn btn-primary"><?= $editUser ? '💾 Update User' : '➕ Add User' ?></button>
      <?php if ($editUser): ?>
      <a href="users.php" class="btn btn-secondary">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- USERS TABLE -->
<div class="card">
  <div class="card-title">👥 All Users</div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th><th>Name</th><th>Username</th><th>Role</th>
          <th>Email</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php while ($u = mysqli_fetch_assoc($users)): ?>
      <tr>
        <td class="text-muted"><?= $u['id'] ?></td>
        <td><?= htmlspecialchars($u['full_name']) ?></td>
        <td style="font-family:monospace;color:var(--accent2)"><?= $u['username'] ?></td>
        <td>
          <span class="badge <?= $u['role']==='admin'?'badge-admin':($u['role']==='manager'?'badge-info':'badge-success') ?>">
            <?= ucfirst($u['role']) ?>
          </span>
        </td>
        <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
        <td>
          <span class="badge <?= $u['status']==='active'?'badge-success':'badge-danger' ?>">
            <?= ucfirst($u['status']) ?>
          </span>
        </td>
        <td>
          <div class="flex gap-2">
            <a href="users.php?edit=<?= $u['id'] ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
            <?php if ($u['id'] != $_SESSION['user_id']): ?>
            <a href="users.php?delete=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')">🗑️</a>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
