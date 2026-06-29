<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireStaff();

$pageTitle = 'Digital Evidence';
$db        = getDB();
$user      = currentUser();
$action    = $_GET['action'] ?? '';
$caseID    = $_GET['case_id'] ?? '';
$error = $success = '';

// Add evidence
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form'] === 'add_evidence') {
    $eid  = bin2hex(random_bytes(16));
    $eid  = substr($eid,0,8).'-'.substr($eid,8,4).'-'.substr($eid,12,4).'-'.substr($eid,16,4).'-'.substr($eid,20);
    try {
        $db->prepare("INSERT INTO DIGITAL_EVIDENCE (EvidenceID,EvidenceType,FileName,FileHash,FileSize,CollectedDate,ChainOfCustody,CaseID,CollectedBy)
                      VALUES (?,?,?,?,?,CURDATE(),?,?,?)")
           ->execute([
               $eid,
               $_POST['evidence_type'],
               $_POST['file_name'],
               $_POST['file_hash'],
               $_POST['file_size'],
               'Collected by ' . $user['name'] . ' on ' . date('Y-m-d'),
               $_POST['case_id'],
               $user['id'],
           ]);
        $success = 'Evidence logged successfully.';
        $action  = '';
    } catch (PDOException $e) {
        $error  = 'Failed to add evidence. File hash must be unique.';
        $action = 'new';
    }
}

$cases = $db->query("SELECT CaseID, CaseNumber FROM `CASE` ORDER BY CaseNumber")->fetchAll();

$where = ['1=1']; $params = [];
if ($caseID) { $where[] = 'de.CaseID = ?'; $params[] = $caseID; }

$rows = $db->prepare("
    SELECT de.*, c.CaseNumber, CONCAT(s.Fname,' ',s.Lname) AS CollectorName
    FROM DIGITAL_EVIDENCE de
    JOIN `CASE` c ON c.CaseID=de.CaseID
    JOIN STAFF s ON s.StaffID=de.CollectedBy
    WHERE " . implode(' AND ', $where) . "
    ORDER BY de.CollectedDate DESC
");
$rows->execute($params);
$evidence = $rows->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Digital Evidence</div>
    <div class="page-sub"><?= count($evidence) ?> item<?= count($evidence)!==1?'s':'' ?> on record</div>
  </div>
  <a href="?action=new<?= $caseID?'&case_id='.urlencode($caseID):'' ?>" class="btn">+ Log Evidence</a>
</div>

<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if ($action === 'new'): ?>
<div class="card" style="max-width:700px;margin-bottom:24px;">
  <div class="card-header"><span class="card-title">Log New Evidence</span></div>
  <form method="POST">
    <input type="hidden" name="form" value="add_evidence">
    <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">
      <div class="form-group">
        <label>Case <span style="color:var(--danger)">*</span></label>
        <select name="case_id" required>
          <option value="">— Select Case —</option>
          <?php foreach ($cases as $c): ?>
            <option value="<?= htmlspecialchars($c['CaseID']) ?>" <?= $caseID===$c['CaseID']?'selected':'' ?>><?= htmlspecialchars($c['CaseNumber']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-grid">
        <div class="form-group">
          <label>Evidence Type <span style="color:var(--danger)">*</span></label>
          <select name="evidence_type" required>
            <?php foreach (['Email','Screenshot','Log File','Malware Sample','Memory Dump','Disk Image','Network Capture','Other'] as $t): ?>
              <option value="<?= $t ?>"><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>File Name <span style="color:var(--danger)">*</span></label>
          <input type="text" name="file_name" required placeholder="evidence_file.eml">
        </div>
        <div class="form-group">
          <label>File Size</label>
          <input type="text" name="file_size" placeholder="1.2MB">
        </div>
      </div>
      <div class="form-group">
        <label>SHA-256 Hash <span style="color:var(--danger)">*</span></label>
        <input type="text" name="file_hash" required placeholder="64-character SHA-256 hash" style="font-family:var(--mono);font-size:12px;">
        <span class="form-hint">Generate with: <code style="font-family:var(--mono)">Get-FileHash filename -Algorithm SHA256</code> (PowerShell) or <code style="font-family:var(--mono)">sha256sum filename</code> (Linux)</span>
      </div>
    </div>
    <div class="card-footer">
      <a href="evidence.php" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn">Log Evidence</button>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Filter by case -->
<div class="card" style="margin-bottom:20px;">
  <div class="card-body" style="padding:14px 20px;">
    <form method="GET" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
      <select name="case_id" onchange="this.form.submit()">
        <option value="">All Cases</option>
        <?php foreach ($cases as $c): ?>
          <option value="<?= htmlspecialchars($c['CaseID']) ?>" <?= $caseID===$c['CaseID']?'selected':'' ?>><?= htmlspecialchars($c['CaseNumber']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($caseID): ?><a href="evidence.php" class="btn btn-sm btn-outline">Clear</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrap">
    <?php if (empty($evidence)): ?>
      <div class="empty"><div class="empty-icon">🗂️</div><div class="empty-text">No evidence logged.</div></div>
    <?php else: ?>
    <table>
      <thead><tr><th>Case</th><th>Type</th><th>File Name</th><th>Size</th><th>Hash (partial)</th><th>Collected By</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($evidence as $e): ?>
        <tr>
          <td class="mono"><?= htmlspecialchars($e['CaseNumber']) ?></td>
          <td><?= htmlspecialchars($e['EvidenceType']) ?></td>
          <td class="mono"><?= htmlspecialchars($e['FileName']) ?></td>
          <td class="mono"><?= htmlspecialchars($e['FileSize'] ?: '—') ?></td>
          <td class="mono" style="font-size:10px;" title="<?= htmlspecialchars($e['FileHash']) ?>"><?= htmlspecialchars(substr($e['FileHash'],0,20)).'...' ?></td>
          <td><?= htmlspecialchars($e['CollectorName']) ?></td>
          <td class="mono"><?= htmlspecialchars($e['CollectedDate']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
