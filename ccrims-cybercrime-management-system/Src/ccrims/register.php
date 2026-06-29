<?php
session_start();
require_once __DIR__ . '/includes/db.php';

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname  = trim($_POST['fname'] ?? '');
    $lname  = trim($_POST['lname'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $nid    = trim($_POST['national_id'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $city   = trim($_POST['city'] ?? '');
    $pass   = $_POST['password'] ?? '';
    $pass2  = $_POST['password2'] ?? '';

    if ($pass !== $pass2) {
        $error = 'Passwords do not match.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $db = getDB();
        try {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $id   = bin2hex(random_bytes(16));
            $uid  = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20);

            $db->prepare("INSERT INTO CITIZEN (CitizenID,Fname,Lname,Email,NationalID,Phone,City,Password) VALUES (?,?,?,?,?,?,?,?)")
               ->execute([$uid,$fname,$lname,$email,$nid,$phone?:null,$city?:null,$hash]);

            $success = 'Account created! You can now log in.';
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate')) {
                $error = 'Email or National ID already registered.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CCRIMS — Register</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/ccrims/css/main.css">
<style>
body{display:flex;align-items:center;justify-content:center;min-height:100vh;}
.site-main{padding:0;display:flex;align-items:center;justify-content:center;width:100%;}
.auth-card{max-width:480px;}
</style>
</head>
<body>
<main class="site-main">
<div class="auth-card">
  <div class="auth-logo">
    <span class="logo-badge">⬡</span>
    <span class="logo-text">CCRIMS</span>
    <span class="logo-sub">Citizen Registration</span>
  </div>

  <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?> <a href="/ccrims/index.php">Login →</a></div><?php endif; ?>

  <?php if (!$success): ?>
  <form method="POST">
    <div style="display:flex;flex-direction:column;gap:14px;">
      <div class="form-grid" style="grid-template-columns:1fr 1fr;">
        <div class="form-group">
          <label>First Name</label>
          <input type="text" name="fname" required value="<?= htmlspecialchars($_POST['fname']??'') ?>">
        </div>
        <div class="form-group">
          <label>Last Name</label>
          <input type="text" name="lname" required value="<?= htmlspecialchars($_POST['lname']??'') ?>">
        </div>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email']??'') ?>">
      </div>
      <div class="form-group">
        <label>National ID (CNIC)</label>
        <input type="text" name="national_id" required placeholder="3520112345671" value="<?= htmlspecialchars($_POST['national_id']??'') ?>">
      </div>
      <div class="form-grid" style="grid-template-columns:1fr 1fr;">
        <div class="form-group">
          <label>Phone <span style="font-weight:400;color:var(--text3)">(optional)</span></label>
          <input type="text" name="phone" placeholder="+923001234567" value="<?= htmlspecialchars($_POST['phone']??'') ?>">
        </div>
        <div class="form-group">
          <label>City <span style="font-weight:400;color:var(--text3)">(optional)</span></label>
          <input type="text" name="city" placeholder="Lahore" value="<?= htmlspecialchars($_POST['city']??'') ?>">
        </div>
      </div>
      <div class="form-grid" style="grid-template-columns:1fr 1fr;">
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" required minlength="6">
        </div>
        <div class="form-group">
          <label>Confirm Password</label>
          <input type="password" name="password2" required>
        </div>
      </div>
      <button type="submit" class="btn" style="width:100%;justify-content:center;margin-top:4px;">Create Account</button>
    </div>
  </form>
  <?php endif; ?>

  <div class="auth-switch">Already have an account? <a href="/ccrims/index.php">Sign in</a></div>
</div>
</main>
</body>
</html>
