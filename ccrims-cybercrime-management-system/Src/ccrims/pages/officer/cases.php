<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireStaff();

$pageTitle = 'Cases';
$db        = getDB();
$user      = currentUser();
$action    = $_GET['action'] ?? '';
$error = $success = '';

// Handle new case creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form'] === 'new_case') {
    $complaintID  = $_POST['complaint_id'] ?? '';
    $desc         = trim($_POST['description'] ?? '');
    $priority     = $_POST['priority'] ?? 'Medium';
    $jurisdiction = trim($_POST['jurisdiction'] ?? '');

    if (!$complaintID || !$desc) {
        $error = 'Complaint and description are required.';
        $action = 'new';
    } else {
        try {
            $id  = bin2hex(random_bytes(16));
            $cid = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20);
            $num = 'CASE-' . date('Y') . '-' . str_pad($db->query("SELECT COUNT(*)+1 FROM `CASE`")->fetchColumn(), 4, '0', STR_PAD_LEFT);

            $db->prepare("INSERT INTO `CASE` (CaseID,CaseNumber,Description,Status,Priority,OpenDate,Jurisdiction,ComplaintID)
                          VALUES (?,?,?,'Open',?,CURDATE(),?,?)")
               ->execute([$cid, $num, $desc, $priority, $jurisdiction, $complaintID]);

            // Update complaint status
            $db->prepare("UPDATE COMPLAINT SET Status='Under Review' WHERE ComplaintID=?")
               ->execute([$complaintID]);

            $success = "Case $num created successfully.";
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate')) {
                $error = 'That complaint already has a case assigned.';
            } else {
                $error = 'Failed to create case: ' . $e->getMessage();
            }
            $action = 'new';
        }
    }
}

// Filter
$status   = $_GET['status']   ?? '';
$priority = $_GET['priority'] ?? '';
$where    = ['1=1'];
$params   = [];
if ($status)   { $where[] = 'c.Status = ?';   $params[] = $status;   }
if ($priority) { $where[] = 'c.Priority = ?'; $params[] = $priority; }

$cases = $db->prepare("
    SELECT c.CaseID, c.CaseNumber, c.Status, c.Priority, c.OpenDate, c.CloseDate, c.Jurisdiction,
           ct.TypeName, CONCAT(cit.Fname,' ',cit.Lname) AS Complainant,
           (SELECT COUNT(*) FROM CASE_STAFF cs WHERE cs.CaseID=c.CaseID) AS StaffCount,
           (SELECT COUNT(*) FROM DIGITAL_EVIDENCE de WHERE de.CaseID=c.CaseID) AS EvidenceCount
    FROM `CASE` c
    JOIN COMPLAINT cp ON cp.ComplaintID = c.ComplaintID
    JOIN CYBER_CRIME_TYPE ct ON ct.CrimeTypeID = cp.CrimeTypeID
    LEFT JOIN CITIZEN cit ON cit.CitizenID = cp.CitizenID
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.OpenDate DESC
");
$cases->execute($params);
$rows = $cases->fetchAll();

// For new case form — unassigned complaints
$freeComplaints = $db->query("
    SELECT cp.ComplaintID, ct.TypeName, cp.LodgeDate,
           CONCAT(cit.Fname,' ',cit.Lname) AS Complainant
    FROM COMPLAINT cp
    JOIN CYBER_CRIME_TYPE ct ON ct.CrimeTypeID = cp.CrimeTypeID
    LEFT JOIN CITIZEN cit ON cit.CitizenID = cp.CitizenID
    LEFT JOIN `CASE` ca ON ca.ComplaintID = cp.ComplaintID
    WHERE ca.CaseID IS NULL
    ORDER BY cp.LodgeDate DESC
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Cases</div>
    <div class="page-sub"><?= count($rows) ?> case<?= count($rows) !== 1 ? 's' : ''?> found</div>
  </div>
  <a href="?action=new" class="btn">+ New Case</a>
</div>

<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<!-- New Case Form -->
<?php if ($action === 'new'): ?>
<div class="card" style="max-width:700px;margin-bottom:24px;">
  <div class="card-header"><span class="card-title">Create New Case from Complaint</span></div>
  <form method="POST">
    <input type="hidden" name="form" value="new_case">
    <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">
      <div class="form-group">
        <label>Complaint <span style="color:var(--danger)">*</span></label>
        <select name="complaint_id" required>
          <option value="">— Select unassigned complaint —</option>
          <?php foreach ($freeComplaints as $fc): ?>
            <option value="<?= htmlspecialchars($fc['ComplaintID']) ?>">
              <?= htmlspecialchars($fc['TypeName']) ?> — <?= htmlspecialchars($fc['Complainant'] ?: 'Unknown') ?> (<?= htmlspecialchars($fc['LodgeDate']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (empty($freeComplaints)): ?><span class="form-hint" style="color:var(--warn)">All complaints already have cases assigned.</span><?php endif; ?>
      </div>
      <div class="form-group">
        <label>Case Description <span style="color:var(--danger)">*</span></label>
        <textarea name="description" required rows="3" placeholder="Brief case summary..."></textarea>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Priority</label>
          <select name="priority">
            <option value="Low">Low</option>
            <option value="Medium" selected>Medium</option>
            <option value="High">High</option>
            <option value="Critical">Critical</option>
          </select>
        </div>
        <div class="form-group">
          <label>Jurisdiction</label>
          <input type="text" name="jurisdiction" placeholder="Federal / Provincial / District">
        </div>
      </div>
    </div>
    <div class="card-footer">
      <a href="cases.php" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn">Create Case</button>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-body" style="padding:14px 20px;">
    <form method="GET" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
      <select name="status" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['Open','Active','Closed','Suspended'] as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
      <select name="priority" onchange="this.form.submit()">
        <option value="">All Priorities</option>
        <?php foreach (['Low','Medium','High','Critical'] as $p): ?>
          <option value="<?= $p ?>" <?= $priority === $p ? 'selected' : '' ?>><?= $p ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($status || $priority): ?>
        <a href="cases.php" class="btn btn-sm btn-outline">Clear</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Cases Table -->
<div class="card">
  <div class="table-wrap">
    <?php if (empty($rows)): ?>
      <div class="empty"><div class="empty-icon">📁</div><div class="empty-text">No cases found.</div></div>
    <?php else: ?>
    <table>
      <thead>
        <tr><th>Case No.</th><th>Crime Type</th><th>Complainant</th><th>Priority</th><th>Status</th><th>Staff</th><th>Evidence</th><th>Opened</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="case_detail.php?id=<?= urlencode($r['CaseID']) ?>" class="mono" style="color:var(--accent);text-decoration:none;"><?= htmlspecialchars($r['CaseNumber']) ?></a></td>
          <td><?= htmlspecialchars($r['TypeName']) ?></td>
          <td><?= htmlspecialchars($r['Complainant'] ?: '—') ?></td>
          <td><span class="tag tag-<?= strtolower($r['Priority']) ?>"><?= $r['Priority'] ?></span></td>
          <td><span class="tag tag-<?= strtolower($r['Status']) ?>"><?= $r['Status'] ?></span></td>
          <td class="mono"><?= $r['StaffCount'] ?></td>
          <td class="mono"><?= $r['EvidenceCount'] ?></td>
          <td class="mono"><?= htmlspecialchars($r['OpenDate']) ?></td>
          <td><a href="case_detail.php?id=<?= urlencode($r['CaseID']) ?>" class="btn btn-sm btn-outline">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
