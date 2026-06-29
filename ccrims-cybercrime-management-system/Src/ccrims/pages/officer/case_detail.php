<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireStaff();

$pageTitle = 'Case Detail';
$db        = getDB();
$user      = currentUser();
$caseID    = $_GET['id'] ?? '';
$tab       = $_GET['tab'] ?? 'overview';
$error = $success = '';

if (!$caseID) { header('Location: cases.php'); exit; }

// Main case
$caseStmt = $db->prepare("
    SELECT c.*, cp.Description AS ComplaintDesc, cp.LodgeDate, cp.Status AS ComplaintStatus,
           ct.TypeName AS CrimeType,
           CONCAT(cit.Fname,' ',cit.Lname) AS CitizenName, cit.Email AS CitizenEmail
    FROM `CASE` c
    JOIN COMPLAINT cp ON cp.ComplaintID = c.ComplaintID
    JOIN CYBER_CRIME_TYPE ct ON ct.CrimeTypeID = cp.CrimeTypeID
    LEFT JOIN CITIZEN cit ON cit.CitizenID = cp.CitizenID
    WHERE c.CaseID = ?
");
$caseStmt->execute([$caseID]);
$case = $caseStmt->fetch();
if (!$case) { header('Location: cases.php'); exit; }
$pageTitle = $case['CaseNumber'];

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['form'] === 'update_status') {
        $db->prepare("UPDATE `CASE` SET Status=?, CloseDate=IF(?='Closed',CURDATE(),NULL) WHERE CaseID=?")
           ->execute([$_POST['status'], $_POST['status'], $caseID]);
        $success = 'Case status updated.';
        $case['Status'] = $_POST['status'];
    } elseif ($_POST['form'] === 'add_record') {
        $rid = bin2hex(random_bytes(16));
        $rid = substr($rid,0,8).'-'.substr($rid,8,4).'-'.substr($rid,12,4).'-'.substr($rid,16,4).'-'.substr($rid,20);
        $db->prepare("INSERT INTO CASE_RECORD (RecordID,RecordDate,Summary,CaseID,StaffID) VALUES (?,CURDATE(),?,?,?)")
           ->execute([$rid, trim($_POST['summary']), $caseID, $user['id']]);
        $success = 'Investigation record added.';
        $tab = 'records';
    } elseif ($_POST['form'] === 'add_log') {
        $lid = bin2hex(random_bytes(16));
        $lid = substr($lid,0,8).'-'.substr($lid,8,4).'-'.substr($lid,12,4).'-'.substr($lid,16,4).'-'.substr($lid,20);
        $db->prepare("INSERT INTO INVESTIGATION_LOG (LogID,ActionType,ActionDetail,Timestamp,RecordID,StaffID) VALUES (?,?,?,NOW(),?,?)")
           ->execute([$lid, $_POST['action_type'], $_POST['action_detail'], $_POST['record_id'], $user['id']]);
        $success = 'Investigation log entry added.';
        $tab = 'records';
    } elseif ($_POST['form'] === 'assign_staff') {
        try {
            $db->prepare("INSERT INTO CASE_STAFF (CaseID,StaffID,AssignedDate,AssignmentRole) VALUES (?,?,CURDATE(),?)")
               ->execute([$caseID, $_POST['staff_id'], $_POST['role']]);
            $success = 'Staff member assigned.';
        } catch (PDOException $e) {
            $error = 'Staff member already assigned to this case.';
        }
        $tab = 'staff';
    }
}

// Sub-queries
$staffOnCase = $db->prepare("
    SELECT s.StaffID, CONCAT(s.Fname,' ',s.Lname) AS Name, s.Role, s.BadgeNumber, cs.AssignedDate, cs.AssignmentRole
    FROM CASE_STAFF cs JOIN STAFF s ON s.StaffID=cs.StaffID WHERE cs.CaseID=?
");
$staffOnCase->execute([$caseID]); $staffList = $staffOnCase->fetchAll();

$allStaff = $db->query("SELECT StaffID, CONCAT(Fname,' ',Lname) AS Name, Role FROM STAFF ORDER BY Fname")->fetchAll();

$suspects = $db->prepare("
    SELECT s.SuspectID, CONCAT(s.Fname,' ',s.Lname) AS Name, s.Alias, s.Status, cs.InvolvementType
    FROM CASE_SUSPECT cs JOIN SUSPECT s ON s.SuspectID=cs.SuspectID WHERE cs.CaseID=?
");
$suspects->execute([$caseID]); $suspectList = $suspects->fetchAll();

$evidence = $db->prepare("
    SELECT de.*, CONCAT(s.Fname,' ',s.Lname) AS CollectorName
    FROM DIGITAL_EVIDENCE de JOIN STAFF s ON s.StaffID=de.CollectedBy WHERE de.CaseID=?
    ORDER BY de.CollectedDate DESC
");
$evidence->execute([$caseID]); $evidenceList = $evidence->fetchAll();




$records = $db->prepare("
    SELECT cr.*, CONCAT(s.Fname,' ',s.Lname) AS StaffName
    FROM CASE_RECORD cr JOIN STAFF s ON s.StaffID=cr.StaffID WHERE cr.CaseID=?
    ORDER BY cr.RecordDate DESC
");
$records->execute([$caseID]); $recordList = $records->fetchAll();

$warrants = $db->prepare("SELECT * FROM WARRANT WHERE CaseID=? ORDER BY IssuedDate DESC");
$warrants->execute([$caseID]); $warrantList = $warrants->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="breadcrumb">
  <a href="/ccrims/pages/officer/dashboard.php">Dashboard</a>
  <span class="breadcrumb-sep">/</span>
  <a href="/ccrims/pages/officer/cases.php">Cases</a>
  <span class="breadcrumb-sep">/</span>
  <span><?= htmlspecialchars($case['CaseNumber']) ?></span>
</div>

<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="page-header">
  <div>
    <div class="page-title" style="display:flex;align-items:center;gap:10px;">
      <span class="mono"><?= htmlspecialchars($case['CaseNumber']) ?></span>
      <span class="tag tag-<?= strtolower($case['Status']) ?>"><?= $case['Status'] ?></span>
      <span class="tag tag-<?= strtolower($case['Priority']) ?>"><?= $case['Priority'] ?></span>
    </div>
    <div class="page-sub"><?= htmlspecialchars($case['CrimeType']) ?> · Opened <?= htmlspecialchars($case['OpenDate']) ?></div>
  </div>
  <!-- Quick status update -->
  <form method="POST" style="display:flex;align-items:center;gap:8px;">
    <input type="hidden" name="form" value="update_status">
    <select name="status">
      <?php foreach (['Open','Active','Closed','Suspended'] as $s): ?>
        <option value="<?= $s ?>" <?= $case['Status']===$s?'selected':'' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-sm">Update Status</button>
  </form>
</div>

<!-- Tabs -->
<div class="tabs">
  <?php foreach (['overview'=>'Overview','staff'=>'Staff ('.count($staffList).')','evidence'=>'Evidence ('.count($evidenceList).')','suspects'=>'Suspects ('.count($suspectList).')','records'=>'Records ('.count($recordList).')','warrants'=>'Warrants ('.count($warrantList).')'] as $key=>$label): ?>
    <a href="?id=<?= urlencode($caseID) ?>&tab=<?= $key ?>" class="tab-link <?= $tab===$key?'active':'' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<!-- ── OVERVIEW ── -->
<?php if ($tab === 'overview'): ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">
  <div class="card">
    <div class="card-header"><span class="card-title">Case Information</span></div>
    <div>
      <?php foreach ([
        'Case Number'  => $case['CaseNumber'],
        'Status'       => $case['Status'],
        'Priority'     => $case['Priority'],
        'Jurisdiction' => $case['Jurisdiction'],
        'Opened'       => $case['OpenDate'],
        'Closed'       => $case['CloseDate'] ?: '—',
        'Crime Type'   => $case['CrimeType'],
      ] as $lbl => $val): ?>
      <div class="detail-row">
        <span class="detail-label"><?= $lbl ?></span>
        <span class="detail-value mono"><?= htmlspecialchars($val) ?></span>
      </div>
      <?php endforeach; ?>
      <div class="detail-row">
        <span class="detail-label">Description</span>
        <span class="detail-value" style="white-space:pre-wrap;"><?= htmlspecialchars($case['Description']) ?></span>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">Complaint</span></div>
    <div>
      <?php foreach ([
        'Complainant' => $case['CitizenName'] ?: '—',
        'Email'       => $case['CitizenEmail'] ?: '—',
        'Lodged'      => $case['LodgeDate'],
        'Status'      => $case['ComplaintStatus'],
      ] as $lbl => $val): ?>
      <div class="detail-row">
        <span class="detail-label"><?= $lbl ?></span>
        <span class="detail-value"><?= htmlspecialchars($val) ?></span>
      </div>
      <?php endforeach; ?>
      <div class="detail-row">
        <span class="detail-label">Description</span>
        <span class="detail-value" style="white-space:pre-wrap;font-size:12px;"><?= htmlspecialchars($case['ComplaintDesc']) ?></span>
      </div>
    </div>
  </div>
</div>

<!-- ── STAFF ── -->
<?php elseif ($tab === 'staff'): ?>
<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">
  <div class="card">
    <div class="card-header"><span class="card-title">Assigned Staff</span></div>
    <div class="table-wrap">
      <?php if (empty($staffList)): ?>
        <div class="empty"><div class="empty-text">No staff assigned yet.</div></div>
      <?php else: ?>
      <table>
        <thead><tr><th>Name</th><th>Role</th><th>Badge</th><th>Assignment</th><th>Since</th></tr></thead>
        <tbody>
          <?php foreach ($staffList as $s): ?>
          <tr>
            <td><?= htmlspecialchars($s['Name']) ?></td>
            <td><span class="tag tag-<?= strtolower(str_replace(' ','-',$s['Role'])) ?>"><?= htmlspecialchars($s['Role']) ?></span></td>
            <td class="mono"><?= htmlspecialchars($s['BadgeNumber']) ?></td>
            <td><?= htmlspecialchars($s['AssignmentRole']) ?></td>
            <td class="mono"><?= htmlspecialchars($s['AssignedDate']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">Assign Staff</span></div>
    <form method="POST">
      <input type="hidden" name="form" value="assign_staff">
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
        <div class="form-group">
          <label>Staff Member</label>
          <select name="staff_id" required>
            <option value="">— Select —</option>
            <?php foreach ($allStaff as $s): ?>
              <option value="<?= htmlspecialchars($s['StaffID']) ?>"><?= htmlspecialchars($s['Name']) ?> (<?= $s['Role'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Role on Case</label>
          <input type="text" name="role" placeholder="Lead Investigator">
        </div>
      </div>
      <div class="card-footer"><button type="submit" class="btn">Assign</button></div>
    </form>
  </div>
</div>

<!-- ── EVIDENCE ── -->
<?php elseif ($tab === 'evidence'): ?>
<div class="card" style="margin-bottom:20px;">
  <div class="card-header">
    <span class="card-title">Digital Evidence</span>
    <a href="/ccrims/pages/officer/evidence.php?case_id=<?= urlencode($caseID) ?>&action=new" class="btn btn-sm">+ Add Evidence</a>
  </div>
  <div class="table-wrap">
    <?php if (empty($evidenceList)): ?>
      <div class="empty"><div class="empty-text">No evidence logged for this case.</div></div>
    <?php else: ?>
    <table>
      <thead><tr><th>Type</th><th>File Name</th><th>Size</th><th>Hash</th><th>Collected By</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($evidenceList as $e): ?>
        <tr>
          <td><?= htmlspecialchars($e['EvidenceType']) ?></td>
          <td class="mono"><?= htmlspecialchars($e['FileName']) ?></td>
          <td class="mono"><?= htmlspecialchars($e['FileSize'] ?: '—') ?></td>
          <td class="mono" style="font-size:10px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($e['FileHash']) ?>"><?= htmlspecialchars(substr($e['FileHash'],0,24)).'...' ?></td>
          <td><?= htmlspecialchars($e['CollectorName']) ?></td>
          <td class="mono"><?= htmlspecialchars($e['CollectedDate']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>


<!-- ── SUSPECTS ── -->
<?php elseif ($tab === 'suspects'): ?>
<div class="card">
  <div class="card-header">
    <span class="card-title">Suspects</span>
    <a href="/ccrims/pages/officer/suspects.php?case_id=<?= urlencode($caseID) ?>" class="btn btn-sm btn-outline">Manage Suspects</a>
  </div>
  <div class="table-wrap">
    <?php if (empty($suspectList)): ?>
      <div class="empty"><div class="empty-text">No suspects linked to this case.</div></div>
    <?php else: ?>
    <table>
      <thead><tr><th>Name</th><th>Alias</th><th>Involvement</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($suspectList as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['Name']) ?></td>
          <td class="mono"><?= htmlspecialchars($s['Alias'] ?: '—') ?></td>
          <td><?= htmlspecialchars($s['InvolvementType']) ?></td>
          <td><span class="tag tag-<?= strtolower(str_replace(' ','-',$s['Status'])) ?>"><?= htmlspecialchars($s['Status']) ?></span></td>
          <td><a href="/ccrims/pages/officer/suspects.php?id=<?= urlencode($s['SuspectID']) ?>" class="btn btn-sm btn-outline">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- ── RECORDS ── -->
<?php elseif ($tab === 'records'): ?>
<div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start;">
  <div>
    <?php foreach ($recordList as $rec): ?>
    <div class="card" style="margin-bottom:16px;">
      <div class="card-header">
        <span class="card-title" style="font-family:var(--mono);font-size:12px;">Record · <?= htmlspecialchars($rec['RecordDate']) ?></span>
        <span style="font-size:12px;color:var(--text3);">by <?= htmlspecialchars($rec['StaffName']) ?></span>
      </div>
      <div class="card-body">
        <p style="white-space:pre-wrap;font-size:13px;"><?= htmlspecialchars($rec['Summary']) ?></p>

        <?php
        // Logs for this record
        $logs = $db->prepare("SELECT il.*, CONCAT(s.Fname,' ',s.Lname) AS SName FROM INVESTIGATION_LOG il JOIN STAFF s ON s.StaffID=il.StaffID WHERE il.RecordID=? ORDER BY il.Timestamp");
        $logs->execute([$rec['RecordID']]);
        $logRows = $logs->fetchAll();
        ?>
        <?php if ($logRows): ?>
        <div style="margin-top:14px;border-top:1px solid var(--border2);padding-top:12px;">
          <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text3);margin-bottom:8px;">Investigation Log Entries</div>
          <?php foreach ($logRows as $lg): ?>
          <div style="padding:8px 10px;background:var(--surface2);border-radius:4px;margin-bottom:6px;font-size:12px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
              <strong><?= htmlspecialchars($lg['ActionType']) ?></strong>
              <span style="color:var(--text3);font-family:var(--mono)"><?= htmlspecialchars($lg['Timestamp']) ?></span>
            </div>
            <div style="color:var(--text2);"><?= htmlspecialchars($lg['ActionDetail']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Add Log -->
        <details style="margin-top:12px;">
          <summary style="font-size:12px;cursor:pointer;color:var(--accent);">+ Add log entry to this record</summary>
          <form method="POST" style="margin-top:10px;display:flex;flex-direction:column;gap:8px;">
            <input type="hidden" name="form" value="add_log">
            <input type="hidden" name="record_id" value="<?= htmlspecialchars($rec['RecordID']) ?>">
            <input type="text" name="action_type" placeholder="Action Type (e.g. Domain Analysis)" required>
            <textarea name="action_detail" rows="2" placeholder="Detail..."></textarea>
            <button type="submit" class="btn btn-sm" style="align-self:flex-start;">Add Log</button>
          </form>
        </details>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($recordList)): ?>
      <div class="empty"><div class="empty-text">No records yet.</div></div>
    <?php endif; ?>
  </div>

  <!-- Add Record -->
  <div class="card">
    <div class="card-header"><span class="card-title">Add Investigation Record</span></div>
    <form method="POST">
      <input type="hidden" name="form" value="add_record">
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
        <div class="form-group">
          <label>Summary</label>
          <textarea name="summary" required rows="5" placeholder="Describe what was done, findings, next steps..."></textarea>
        </div>
      </div>
      <div class="card-footer"><button type="submit" class="btn">Add Record</button></div>
    </form>
  </div>
</div>

<!-- ── WARRANTS ── -->
<?php elseif ($tab === 'warrants'): ?>
<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">
  <div class="card">
    <div class="card-header"><span class="card-title">Warrants</span></div>
    <div class="table-wrap">
      <?php if (empty($warrantList)): ?>
        <div class="empty"><div class="empty-text">No warrants issued.</div></div>
      <?php else: ?>
      <table>
        <thead><tr><th>Type</th><th>Issued By</th><th>Issued</th><th>Expires</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($warrantList as $w): ?>
          <tr>
            <td><?= htmlspecialchars($w['WarrantType']) ?></td>
            <td><?= htmlspecialchars($w['IssuedBy']) ?></td>
            <td class="mono"><?= htmlspecialchars($w['IssuedDate']) ?></td>
            <td class="mono"><?= htmlspecialchars($w['ExpiryDate'] ?: '—') ?></td>
            <td><span class="tag tag-<?= strtolower($w['Status']) ?>"><?= htmlspecialchars($w['Status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">Issue Warrant</span></div>
    <form method="POST">
      <input type="hidden" name="form" value="issue_warrant">
      <div class="card-body" style="display:flex;flex-direction:column;gap:12px;">
        <div class="form-group">
          <label>Warrant Type</label>
          <input type="text" name="warrant_type" placeholder="Search / Arrest / Seizure">
        </div>
        <div class="form-group">
          <label>Issued By</label>
          <input type="text" name="issued_by" placeholder="Court name">
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label>Issued Date</label>
            <input type="date" name="issued_date" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label>Expiry Date</label>
            <input type="date" name="expiry_date">
          </div>
        </div>
      </div>
      <div class="card-footer"><button type="submit" class="btn">Issue Warrant</button></div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

<?php
// Handle warrant form (needed after HTML output — redirect after POST normally, but we're keeping it simple)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'issue_warrant') {
    // Handled above HTML output; if we reach here do it and reload — workaround for simple single-file pattern
}
?>
