<?php
require_once 'includes/config.php';

// If already logged in, go to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $sql = "SELECT * FROM users WHERE username = '$username' AND status = 'active' LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if ($row = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id']   = $row['id'];
                $_SESSION['username']  = $row['username'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['role']      = $row['role'];
                header("Location: dashboard.php");
                exit();
            }
        }
        $error = "❌ Invalid username or password!";
    } else {
        $error = "⚠️ Please enter both username and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Pharmacy Management System</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <div class="login-logo">
      <span class="icon">💊</span>
      <h1>PharmaMS</h1>
      <p>Pharmacy Management System</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" placeholder="Enter username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Enter password" required>
      </div>
      <button type="submit" class="login-btn">Login →</button>
    </form>

    <div class="demo-creds">
      <strong>Demo Accounts (password: password)</strong><br>
      👑 admin / password — Full access<br>
      💊 pharmacist / password — Sales & Inventory<br>
      📊 manager / password — View & Reports
    </div>
  </div>
</div>
</body>
</html>
