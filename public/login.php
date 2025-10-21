<?php
session_start();
include '../backend/config/db_connect.php';
include '../backend/controllers/login_backend.php';

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role_id'])) {
    switch ((int)$_SESSION['role_id']) {
        case 1: header('Location: student_dashboard.php'); exit;
        case 2: header('Location: super_dashboard.php'); exit;
        case 3: header('Location: admin_dashboard.php'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    /* Narrow header and center it */
    header .container { max-width: 980px; margin: 0 auto; padding: 12px 20px; display:flex; align-items:center; justify-content:space-between; }

    .login-shell {
      min-height: 80vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 12px;
    }
    .login-card {
      width: 100%;
      max-width: 420px;
      border-radius: 14px;
      padding: 28px;
      box-shadow: 0 12px 40px rgba(2,6,23,0.06);
      background: #ffffff;
      border: 1px solid rgba(0,0,0,0.04);
    }
    .brand {
      display:flex;
      align-items:center;
      gap:12px;
      margin-bottom:18px;
    }
    .brand .logo {
      width:48px;height:48px;border-radius:10px;
      background: linear-gradient(135deg,var(--saffron),var(--saffron-dark));
      display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;
      box-shadow: 0 6px 18px rgba(15,23,42,0.06);
      font-family: 'Merriweather', serif;
    }
    .brand h2 { margin:0; font-size:1.15rem; color:#0f172a; font-family: 'Merriweather', serif; }

    .helper { color:var(--muted); font-size:0.95rem; margin-bottom:14px; }

    /* align checkbox and label */
    .checkbox-row {
      display:flex;
      align-items:center;
      gap:8px;
      font-weight:600;
      color:var(--muted);
      font-size:0.95rem;
    }
    .checkbox-row input[type="checkbox"] {
      width:16px; height:16px;
      margin:0;
      transform: translateY(0.5px);
    }

    .login-footer { margin-top:14px; display:flex; justify-content:flex-end; align-items:center; gap:10px; flex-wrap:wrap; }

    .linkless { text-decoration:none; color:var(--saffron-dark); font-weight:700; }

    @media (max-width:480px) {
      .login-card { padding:20px; }
    }
  </style>
</head>
<body>
  <header>
    <div class="container">
      <h1 style="margin:0;font-size:1.1rem;">Reynold System</h1>
      <nav>
        <a href="login.php">Login</a>
      </nav>
    </div>
  </header>

  <main class="login-shell">
    <div class="login-card">
      <div class="brand">
        <div class="logo">R</div>
        <h2>Sign in to your account</h2>
      </div>

      <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php elseif (!empty($success)): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <p class="helper">Enter your email and password to continue.</p>

      <form method="POST" action="">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">

        <label for="password">Password</label>
        <input id="password" type="password" name="password" required>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;margin-bottom:12px;">
          <label class="checkbox-row">
            <input type="checkbox" name="remember"> Remember me
          </label>
          <a href="#" class="linkless" style="font-size:0.9rem;">Forgot?</a>
        </div>

        <button type="submit" class="btn" style="width:100%;">Sign In</button>

        <div class="login-footer">
          <!-- registration removed as requested -->
        </div>
      </form>
    </div>
  </main>

  <footer>
    <div class="container" style="text-align:center;padding:18px 0;color:var(--muted);">
      &copy; <?= date('Y') ?> Reynold Hostel
    </div>
  </footer>
</body>
</html>
