<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';
    $userType = $_POST['user_type'] ?? 'citizen'; // citizen | staff

    $db = getDB();

    if ($userType === 'citizen') {
        $stmt = $db->prepare("SELECT CitizenID AS id, Fname, Lname, Password FROM CITIZEN WHERE Email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if ($row && password_verify($password, $row['Password'])) {
            $_SESSION['user_id']   = $row['id'];
            $_SESSION['user_name'] = $row['Fname'] . ' ' . $row['Lname'];
            $_SESSION['user_role'] = 'Citizen';
            $_SESSION['user_type'] = 'citizen';
            header('Location: /ccrims/pages/citizen/dashboard.php');
            exit;
        }
    } else {
        $stmt = $db->prepare("SELECT StaffID AS id, Fname, Lname, Role, Password FROM STAFF WHERE Email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if ($row && password_verify($password, $row['Password'])) {
            $_SESSION['user_id']   = $row['id'];
            $_SESSION['user_name'] = $row['Fname'] . ' ' . $row['Lname'];
            $_SESSION['user_role'] = $row['Role'];
            $_SESSION['user_type'] = 'staff';
            header('Location: /ccrims/pages/officer/dashboard.php');
            exit;
        }
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CCRIMS — Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/ccrims/css/main.css">
<style>
body { display:flex; align-items:center; justify-content:center; min-height:100vh; }
.site-header, .site-footer { display:none; }
.site-main { padding:0; display:flex; align-items:center; justify-content:center; width:100%; }
</style>
</head>
<body>
<main class="site-main">
<div class="auth-card">
  <div class="auth-logo">
    <span class="logo-badge">⬡</span>
    <span class="logo-text">CCRIMS</span>
    <span class="logo-sub">Cyber Crime Reporting &amp; Investigation Management System</span>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
    <div class="alert alert-warn">You are not authorized to access that page.</div>
  <?php endif; ?>

  <div class="auth-tabs">
    <div class="auth-tab active" onclick="switchTab('citizen',this)">Citizen</div>
    <div class="auth-tab" onclick="switchTab('staff',this)">Staff / Officer</div>
  </div>

  <form method="POST" action="">
    <input type="hidden" name="user_type" id="user_type" value="citizen">
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" required placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn" style="width:100%;justify-content:center;margin-top:4px;">Sign In</button>
    </div>
  </form>

  <div class="auth-switch">
    Don't have an account? <a href="/ccrims/register.php">Register as Citizen</a>
  </div>

  <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border2);">
    

  </div>
</div>
</main>

<script>
function switchTab(type, el) {
  document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('user_type').value = type;
}
</script>
</body>
</html>
