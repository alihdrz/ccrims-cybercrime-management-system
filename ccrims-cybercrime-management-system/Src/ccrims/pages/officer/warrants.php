<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireStaff();

$pageTitle = 'Warrants';
$db        = getDB();
$action    = $_GET['action'] ?? '';
$error = $success = '';

// Issue warrant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form'] === 'issue_warrant') {
    $wid = bin2hex(random_bytes(16));
    $wid = substr($wid,0,8).'-'.substr($wid,8,4).'-'.substr($wid,12,4).'-'.substr($wid,16,4).'-'.substr($wid,20);
    try {
        $db->prepare("INSERT INTO WARRANT (WarrantID,WarrantType,IssuedDate,ExpiryDate,IssuedBy,Status,CaseID) VALUES (?,?,?,?,?,'Active',?)")
           ->execute([$wid, $_POST['warrant_type'], $_POST['issued_date'], $_POST['expiry_date'] ?: null, $_POST['issued_by'], $_POST['case_id']]);
        $success = 'Warrant issued successfully.';
        $action  = '';
    } catch (PDOException $e) {
        $error  = 'Failed to issue warrant: ' . $e->getMessage();
        $action = 'new';
    }
}

// Update warrant status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form'] === 'update_warrant') {
    $db->prepare("UPDATE WARRANT SET Status=? WHERE WarrantID=?")
       ->execute([$_POST['status'], $_POST['warrant_id']]);
    $success = 'Warrant status updated.';
}

$cases = $db->query("SELECT CaseID, CaseNumber FROM `CASE` ORDER BY CaseNumber")->fetchAll();

$filterCase   = $_GET['case_id'] ?? '';
$filterStatus = $_GET['status']  ?? '';
$where = ['1=1']; $params = [];
if ($filterCase)   { $where[] = 'w.CaseID = ?';  $params[] = $filterCase; }
if ($filterStatus) { $where[] = 'w.Status = ?';  $params[] = $filterStatus; }

$warrants = $db->prepare("
    SELECT w.*, c.CaseNumber,
           cp_ct.TypeName AS CrimeType
    FROM WARRANT w
    JOIN `CASE` c ON c.CaseID = w.CaseID
    JOIN COMPLAINT cp ON cp.ComplaintID = c.ComplaintID
    JOIN CYBER_CRIME_TYPE cp_ct ON cp_ct.CrimeTypeID = cp.CrimeTypeID
    WHERE " . implode(' AND ', $where) . "
    ORDER BY w.IssuedDate DESC
");
$warrants->execute($params);
$rows = $warrants->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Warrants</div>
    <div class="page-sub"><?= count($rows) ?> warrant<?= count($rows)!==1?'s':'' ?> on record</div>
  </div>
  <a href="?action=new" class="btn">+ Issue Warrant</a>
</div>

<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if ($action === 'new'): ?>
<div class="card" style="max-width:640px;margin-bottom:24px;">
  <div class="card-header"><span class="card-title">Issue New Warrant</span></div>
  <form method="POST">
    <input type="hidden" name="form" value="issue_warrant">
    <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">
      <div class="form-group">
        <label>Case <span style="color:var(--danger)">*</span></label>
        <select name="case_id" required>
          <option value="">— Select Case —</option>
          <?php foreach ($cases as $c): ?>
            <option value="<?= htmlspecialchars($c['CaseID']) ?>"><?= htmlspecialchars($c['CaseNumber']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Warrant Type <span style="color:var(--danger)">*</span></label>
          <select name="warrant_type" required>
            <option value="Search Warrant">Search Warrant</option>
            <option value="Arrest Warrant">Arrest Warrant</option>
            <option value="Seizure Warrant">Seizure Warrant</option>
            <option value="Surveillance Warrant">Surveillance Warrant</option>
            <option value="Production Order">Production Order</option>
          </select>
        </div>
        <div class="form-group">
          <label>Issued By <span style="color:var(--danger)">*</span></label>
          <input type="text" name="issued_by" required placeholder="Islamabad High Court">
        </div>
        <div class="form-group">
          <label>Issued Date <span style="color:var(--danger)">*</span></label>
          <input type="date" name="issued_date" required value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label>Expiry Date</label>
          <input type="date" name="expiry_date">
        </div>
      </div>
    </div>
    <div class="card-footer">
      <a href="warrants.php" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn">Issue Warrant</button>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-body" style="padding:14px 20px;">
    <form method="GET" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
      <select name="case_id" onchange="this.form.submit()">
        <option value="">All Cases</option>
        <?php foreach ($cases as $c): ?>
          <option value="<?= htmlspecialchars($c['CaseID']) ?>" <?= $filterCase===$c['CaseID']?'selected':'' ?>><?= htmlspecialchars($c['CaseNumber']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['Active','Expired','Executed','Revoked'] as $s): ?>
          <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($filterCase || $filterStatus): ?><a href="warrants.php" class="btn btn-sm btn-outline">Clear</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <?php if (empty($rows)): ?>
      <div class="empty"><div class="empty-icon">⚖️</div><div class="empty-text">No warrants issued.</div></div>
    <?php else: ?>
    <table>
      <thead>
        <tr><th>Case</th><th>Crime Type</th><th>Warrant Type</th><th>Issued By</th><th>Issued</th><th>Expires</th><th>Status</th><th>Update</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $w): ?>
        <tr>
          <td class="mono"><a href="case_detail.php?id=<?= urlencode($w['CaseID']) ?>" style="color:var(--accent);text-decoration:none;"><?= htmlspecialchars($w['CaseNumber']) ?></a></td>
          <td><?= htmlspecialchars($w['CrimeType']) ?></td>
          <td><?= htmlspecialchars($w['WarrantType']) ?></td>
          <td><?= htmlspecialchars($w['IssuedBy']) ?></td>
          <td class="mono"><?= htmlspecialchars($w['IssuedDate']) ?></td>
          <td class="mono"><?= htmlspecialchars($w['ExpiryDate'] ?: '—') ?></td>
          <td><span class="tag tag-<?= strtolower($w['Status']) ?>"><?= htmlspecialchars($w['Status']) ?></span></td>
          <td>
            <form method="POST" style="display:flex;gap:6px;align-items:center;">
              <input type="hidden" name="form" value="update_warrant">
              <input type="hidden" name="warrant_id" value="<?= htmlspecialchars($w['WarrantID']) ?>">
              <select name="status" style="font-size:12px;padding:4px 6px;">
                <?php foreach (['Active','Expired','Executed','Revoked'] as $s): ?>
                  <option value="<?= $s ?>" <?= $w['Status']===$s?'selected':'' ?>><?= $s ?></option>
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
