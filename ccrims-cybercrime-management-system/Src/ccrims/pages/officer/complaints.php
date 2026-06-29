<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireStaff();

$pageTitle = 'Complaints';
$db        = getDB();
$error = $success = '';

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form'] === 'update_status') {
    $db->prepare("UPDATE COMPLAINT SET Status=? WHERE ComplaintID=?")
       ->execute([$_POST['status'], $_POST['complaint_id']]);
    $success = 'Complaint status updated.';
}

$status = $_GET['status'] ?? '';
$type   = $_GET['type']   ?? '';

$where = ['1=1']; $params = [];
if ($status) { $where[] = 'cp.Status = ?';       $params[] = $status; }
if ($type)   { $where[] = 'cp.CrimeTypeID = ?';  $params[] = $type; }

$rows = $db->prepare("
    SELECT cp.ComplaintID, cp.Description, cp.LodgeDate, cp.Status,
           ct.TypeName, ct.CrimeTypeID,
           CONCAT(cit.Fname,' ',cit.Lname) AS CitizenName,
           ca.CaseNumber
    FROM COMPLAINT cp
    JOIN CYBER_CRIME_TYPE ct ON ct.CrimeTypeID = cp.CrimeTypeID
    LEFT JOIN CITIZEN cit ON cit.CitizenID = cp.CitizenID
    LEFT JOIN `CASE` ca ON ca.ComplaintID = cp.ComplaintID
    WHERE " . implode(' AND ', $where) . "
    ORDER BY cp.LodgeDate DESC
");
$rows->execute($params);
$complaints = $rows->fetchAll();

$types = $db->query("SELECT CrimeTypeID, TypeName FROM CYBER_CRIME_TYPE ORDER BY TypeName")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Complaints</div>
    <div class="page-sub"><?= count($complaints) ?> complaint<?= count($complaints) !== 1 ? 's' : '' ?> found</div>
  </div>
</div>

<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<!-- Filters -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-body" style="padding:14px 20px;">
    <form method="GET" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
      <select name="status" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['Pending','Under Review','Resolved','Rejected'] as $s): ?>
          <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
      <select name="type" onchange="this.form.submit()">
        <option value="">All Crime Types</option>
        <?php foreach ($types as $t): ?>
          <option value="<?= htmlspecialchars($t['CrimeTypeID']) ?>" <?= $type===$t['CrimeTypeID']?'selected':'' ?>><?= htmlspecialchars($t['TypeName']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($status || $type): ?><a href="complaints.php" class="btn btn-sm btn-outline">Clear</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <?php if (empty($complaints)): ?>
      <div class="empty"><div class="empty-icon">📬</div><div class="empty-text">No complaints found.</div></div>
    <?php else: ?>
    <table>
      <thead>
        <tr><th>Date</th><th>Crime Type</th><th>Complainant</th><th>Description</th><th>Status</th><th>Case</th><th>Update Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($complaints as $c): ?>
        <tr>
          <td class="mono"><?= htmlspecialchars($c['LodgeDate']) ?></td>
          <td><?= htmlspecialchars($c['TypeName']) ?></td>
          <td><?= htmlspecialchars($c['CitizenName'] ?: '—') ?></td>
          <td style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($c['Description']) ?></td>
          <td><span class="tag tag-<?= strtolower(str_replace(' ','-',$c['Status'])) ?>"><?= htmlspecialchars($c['Status']) ?></span></td>
          <td><?= $c['CaseNumber'] ? '<span class="mono" style="color:var(--accent)">'.htmlspecialchars($c['CaseNumber']).'</span>' : '<a href="cases.php?action=new" style="font-size:12px;color:var(--accent);">+ Create case</a>' ?></td>
          <td>
            <form method="POST" style="display:flex;gap:6px;align-items:center;">
              <input type="hidden" name="form" value="update_status">
              <input type="hidden" name="complaint_id" value="<?= htmlspecialchars($c['ComplaintID']) ?>">
              <select name="status" style="font-size:12px;padding:4px 6px;">
                <?php foreach (['Pending','Under Review','Resolved','Rejected'] as $s): ?>
                  <option value="<?= $s ?>" <?= $c['Status']===$s?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-sm btn-outline">Save</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
