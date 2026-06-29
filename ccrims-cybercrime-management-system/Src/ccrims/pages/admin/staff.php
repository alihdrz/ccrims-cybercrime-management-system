<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireLogin();
if ($_SESSION['user_role'] !== 'Admin') { header('Location: /ccrims/pages/officer/dashboard.php'); exit; }

$pageTitle = 'Staff Management';
$db        = getDB();
$action    = $_GET['action'] ?? '';
$error = $success = '';

// Add staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form'] === 'add_staff') {
    $pass = $_POST['password'] ?? '';
    if (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
        $action = 'new';
    } else {
        $sid  = bin2hex(random_bytes(16));
        $sid  = substr($sid,0,8).'-'.substr($sid,8,4).'-'.substr($sid,12,4).'-'.substr($sid,16,4).'-'.substr($sid,20);
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        try {
            $db->prepare("INSERT INTO STAFF (StaffID,Fname,Lname,Role,BadgeNumber,Department,Email,Password) VALUES (?,?,?,?,?,?,?,?)")
               ->execute([$sid, $_POST['fname'], $_POST['lname'], $_POST['role'], $_POST['badge'], $_POST['department'], $_POST['email'], $hash]);
            $success = 'Staff member added successfully.';
            $action  = '';
        } catch (PDOException $e) {
            $error  = str_contains($e->getMessage(),'Duplicate') ? 'Email or badge number already exists.' : 'Failed to add staff.';
            $action = 'new';
        }
    }
}

// Delete staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form'] === 'delete_staff') {
    $db->prepare("DELETE FROM STAFF WHERE StaffID=?")->execute([$_POST['staff_id']]);
    $success = 'Staff member removed.';
}

$staff = $db->query("
    SELECT s.*,
           COUNT(DISTINCT cs.CaseID) AS CaseCount
    FROM STAFF s
    LEFT JOIN CASE_STAFF cs ON cs.StaffID=s.StaffID
    GROUP BY s.StaffID
    ORDER BY s.Role, s.Fname
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Staff Management</div>
    <div class="page-sub"><?= count($staff) ?> staff member<?= count($staff)!==1?'s':'' ?> registered</div>
  </div>
  <a href="?action=new" class="btn">+ Add Staff</a>
</div>

<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if ($action === 'new'): ?>
<div class="card" style="max-width:640px;margin-bottom:24px;">
  <div class="card-header"><span class="card-title">Add New Staff Member</span></div>
  <form method="POST">
    <input type="hidden" name="form" value="add_staff">
    <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">
      <div class="form-grid">
        <div class="form-group"><label>First Name *</label><input type="text" name="fname" required></div>
        <div class="form-group"><label>Last Name *</label><input type="text" name="lname" required></div>
        <div class="form-group">
          <label>Role *</label>
          <select name="role" required>
            <option value="Officer">Officer</option>
            <option value="Analyst">Analyst</option>
            <option value="Forensic Expert">Forensic Expert</option>
            <option value="Admin">Admin</option>
          </select>
        </div>
        <div class="form-group"><label>Badge Number *</label><input type="text" name="badge" required placeholder="OFF-102"></div>
        <div class="form-group"><label>Department</label><input type="text" name="department" placeholder="Investigations"></div>
        <div class="form-group"><label>Email *</label><input type="email" name="email" required placeholder="name@ccrims.gov"></div>
        <div class="form-group"><label>Password *</label><input type="password" name="password" required minlength="6"></div>
      </div>
    </div>
    <div class="card-footer">
      <a href="staff.php" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn">Add Staff Member</button>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Stats by role -->
<?php
$roleCounts = [];
foreach ($staff as $s) { $roleCounts[$s['Role']] = ($roleCounts[$s['Role']] ?? 0) + 1; }
?>
<div class="stats-grid" style="margin-bottom:20px;">
  <?php foreach (['Officer','Analyst','Forensic Expert','Admin'] as $r): ?>
  <div class="stat-card">
    <div class="stat-label"><?= $r ?>s</div>
    <div class="stat-value"><?= $roleCounts[$r] ?? 0 ?></div>
  </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="table-wrap">
    <?php if (empty($staff)): ?>
      <div class="empty"><div class="empty-text">No staff members found.</div></div>
    <?php else: ?>
    <table>
      <thead><tr><th>Name</th><th>Role</th><th>Badge</th><th>Department</th><th>Email</th><th>Active Cases</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($staff as $s): ?>
        <tr>
          <td style="font-weight:500;"><?= htmlspecialchars($s['Fname'].' '.$s['Lname']) ?></td>
          <td><span class="tag tag-<?= strtolower(str_replace(' ','-',$s['Role'])) ?>"><?= htmlspecialchars($s['Role']) ?></span></td>
          <td class="mono"><?= htmlspecialchars($s['BadgeNumber']) ?></td>
          <td><?= htmlspecialchars($s['Department'] ?: '—') ?></td>
          <td><?= htmlspecialchars($s['Email']) ?></td>
          <td class="mono"><?= $s['CaseCount'] ?></td>
          <td>
            <?php if ($s['StaffID'] !== currentUser()['id']): ?>
            <form method="POST" onsubmit="return confirm('Remove this staff member?')">
              <input type="hidden" name="form" value="delete_staff">
              <input type="hidden" name="staff_id" value="<?= htmlspecialchars($s['StaffID']) ?>">
              <button type="submit" class="btn btn-sm btn-danger">Remove</button>
            </form>
            <?php else: ?>
              <span style="font-size:12px;color:var(--text3);">You</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
