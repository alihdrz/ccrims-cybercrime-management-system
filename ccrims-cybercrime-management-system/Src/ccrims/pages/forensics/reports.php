<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireStaff();

$pageTitle = 'Forensic Reports';
$db        = getDB();
$user      = currentUser();
$action    = $_GET['action'] ?? '';
$error = $success = '';

// Submit forensic report
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form'] === 'submit_report') {
    try {
        $db->prepare("INSERT INTO FORENSIC_REPORT (RecordID,ReportType,Findings,ToolsUsed,Methodology,SubmittedDate,ExpertID)
                      VALUES (?,?,?,?,?,CURDATE(),?)")
           ->execute([
               $_POST['record_id'],
               $_POST['report_type'],
               $_POST['findings'],
               $_POST['tools_used'],
               $_POST['methodology'],
               $user['id'],
           ]);
        $success = 'Forensic report submitted.';
        $action  = '';
    } catch (PDOException $e) {
        $error  = str_contains($e->getMessage(), 'Duplicate')
            ? 'A forensic report already exists for that case record.'
            : 'Failed to submit report: ' . $e->getMessage();
        $action = 'new';
    }
}

// Records that don't yet have a forensic report
$freeRecords = $db->query("
    SELECT cr.RecordID, cr.RecordDate, cr.Summary, c.CaseNumber
    FROM CASE_RECORD cr
    JOIN `CASE` c ON c.CaseID=cr.CaseID
    LEFT JOIN FORENSIC_REPORT fr ON fr.RecordID=cr.RecordID
    WHERE fr.RecordID IS NULL
    ORDER BY cr.RecordDate DESC
")->fetchAll();

// All forensic reports
$reports = $db->query("
    SELECT fr.*, cr.RecordDate, cr.Summary AS RecordSummary,
           c.CaseNumber,
           CONCAT(s.Fname,' ',s.Lname) AS ExpertName, s.BadgeNumber
    FROM FORENSIC_REPORT fr
    JOIN CASE_RECORD cr ON cr.RecordID=fr.RecordID
    JOIN `CASE` c ON c.CaseID=cr.CaseID
    JOIN STAFF s ON s.StaffID=fr.ExpertID
    ORDER BY fr.SubmittedDate DESC
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Forensic Reports</div>
    <div class="page-sub"><?= count($reports) ?> report<?= count($reports)!==1?'s':'' ?> submitted</div>
  </div>
  <?php if (in_array($user['role'], ['Forensic Expert','Admin'])): ?>
    <a href="?action=new" class="btn">+ Submit Report</a>
  <?php endif; ?>
</div>

<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if ($action === 'new' && in_array($user['role'], ['Forensic Expert','Admin'])): ?>
<div class="card" style="max-width:700px;margin-bottom:24px;">
  <div class="card-header"><span class="card-title">Submit Forensic Report</span></div>
  <form method="POST">
    <input type="hidden" name="form" value="submit_report">
    <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">
      <div class="form-group">
        <label>Case Record (attach to) <span style="color:var(--danger)">*</span></label>
        <select name="record_id" required>
          <option value="">— Select case record —</option>
          <?php foreach ($freeRecords as $r): ?>
            <option value="<?= htmlspecialchars($r['RecordID']) ?>">
              <?= htmlspecialchars($r['CaseNumber']) ?> · <?= htmlspecialchars($r['RecordDate']) ?> — <?= htmlspecialchars(substr($r['Summary'],0,60)) ?>…
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (empty($freeRecords)): ?>
          <span class="form-hint" style="color:var(--warn)">All case records already have forensic reports, or there are no records yet.</span>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label>Report Type <span style="color:var(--danger)">*</span></label>
        <select name="report_type" required>
          <option value="Malware Analysis Report">Malware Analysis Report</option>
          <option value="Disk Forensics Report">Disk Forensics Report</option>
          <option value="Memory Forensics Report">Memory Forensics Report</option>
          <option value="Network Forensics Report">Network Forensics Report</option>
          <option value="Email Analysis Report">Email Analysis Report</option>
          <option value="Mobile Device Report">Mobile Device Report</option>
          <option value="General Forensics Report">General Forensics Report</option>
        </select>
      </div>
      <div class="form-group">
        <label>Findings <span style="color:var(--danger)">*</span></label>
        <textarea name="findings" required rows="5" placeholder="Describe all forensic findings in detail..."></textarea>
      </div>
      <div class="form-group">
        <label>Tools Used</label>
        <input type="text" name="tools_used" placeholder="e.g. Autopsy, Volatility, Wireshark, VirusTotal">
      </div>
      <div class="form-group">
        <label>Methodology</label>
        <textarea name="methodology" rows="3" placeholder="Describe the forensic methodology applied..."></textarea>
      </div>
    </div>
    <div class="card-footer">
      <a href="reports.php" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn">Submit Report</button>
    </div>
  </form>
</div>
<?php endif; ?>

<!-- Reports list -->
<?php if (empty($reports)): ?>
  <div class="empty"><div class="empty-icon">🔬</div><div class="empty-text">No forensic reports submitted yet.</div></div>
<?php else: ?>
  <?php foreach ($reports as $r): ?>
  <div class="card" style="margin-bottom:18px;">
    <div class="card-header">
      <div>
        <span class="card-title"><?= htmlspecialchars($r['ReportType']) ?></span>
        <span style="font-size:12px;color:var(--text3);margin-left:10px;">Case <span class="mono"><?= htmlspecialchars($r['CaseNumber']) ?></span></span>
      </div>
      <div style="display:flex;align-items:center;gap:10px;">
        <span style="font-size:12px;color:var(--text3);">by <?= htmlspecialchars($r['ExpertName']) ?> · <?= htmlspecialchars($r['SubmittedDate']) ?></span>
      </div>
    </div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:14px;">
      <div>
        <div class="section-title">Findings</div>
        <p style="font-size:13px;white-space:pre-wrap;"><?= htmlspecialchars($r['Findings']) ?></p>
      </div>
      <?php if ($r['ToolsUsed']): ?>
      <div>
        <div class="section-title">Tools Used</div>
        <p style="font-family:var(--mono);font-size:12px;"><?= htmlspecialchars($r['ToolsUsed']) ?></p>
      </div>
      <?php endif; ?>
      <?php if ($r['Methodology']): ?>
      <div>
        <div class="section-title">Methodology</div>
        <p style="font-size:13px;white-space:pre-wrap;"><?= htmlspecialchars($r['Methodology']) ?></p>
      </div>
      <?php endif; ?>
      <div style="border-top:1px solid var(--border2);padding-top:10px;">
        <span style="font-size:11px;color:var(--text3);">Linked Case Record: <?= htmlspecialchars(substr($r['RecordSummary'],0,100)) ?>…</span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
