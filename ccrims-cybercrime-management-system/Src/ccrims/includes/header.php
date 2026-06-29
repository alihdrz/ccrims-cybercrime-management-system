<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
$user = [
    'id'   => $_SESSION['user_id']   ?? '',
    'name' => $_SESSION['user_name'] ?? '',
    'role' => $_SESSION['user_role'] ?? '',
    'type' => $_SESSION['user_type'] ?? '',
];
$isStaff = in_array($user['role'], ['Officer','Analyst','Forensic Expert','Admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'CCRIMS') ?> — CCRIMS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/ccrims/css/main.css">
</head>
<body>

<header class="site-header">
  <div class="header-inner">
    <a href="/ccrims/index.php" class="logo">
      <span class="logo-badge">⬡</span>
      <span class="logo-text">CCRIMS</span>
      <span class="logo-sub">Cyber Crime Investigation</span>
    </a>
    <nav class="main-nav">
      <?php if ($user['id']): ?>
        <?php if (!$isStaff): ?>
          <a href="/ccrims/pages/citizen/dashboard.php">My Complaints</a>
          <a href="/ccrims/pages/citizen/complaint_form.php">File Complaint</a>
        <?php else: ?>
          <a href="/ccrims/pages/officer/dashboard.php">Dashboard</a>
          <a href="/ccrims/pages/officer/cases.php">Cases</a>
          <a href="/ccrims/pages/officer/complaints.php">Complaints</a>
          <a href="/ccrims/pages/officer/evidence.php">Evidence</a>
          <a href="/ccrims/pages/officer/suspects.php">Suspects</a>
          <a href="/ccrims/pages/officer/warrants.php">Warrants</a>
          <?php if ($user['role'] === 'Forensic Expert'): ?>
            <a href="/ccrims/pages/forensics/reports.php">Reports</a>
          <?php endif; ?>
          <?php if ($user['role'] === 'Admin'): ?>
            <a href="/ccrims/pages/admin/staff.php">Staff</a>
          <?php endif; ?>
        <?php endif; ?>
        <div class="nav-user">
          <span class="nav-user-name"><?= htmlspecialchars($user['name']) ?></span>
          <span class="nav-user-role tag tag-<?= strtolower(str_replace(' ', '-', $user['role'] ?: 'citizen')) ?>">
            <?= $isStaff ? htmlspecialchars($user['role']) : 'Citizen' ?>
          </span>
          <a href="/ccrims/logout.php" class="btn btn-sm btn-outline">Logout</a>
        </div>
      <?php else: ?>
        <a href="/ccrims/index.php" class="btn btn-sm">Login</a>
        <a href="/ccrims/register.php" class="btn btn-sm btn-outline">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main class="site-main">
