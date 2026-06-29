<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireStaff();

$pageTitle = 'Suspects';
$db        = getDB();
$action    = $_GET['action'] ?? '';
$viewID    = $_GET['id']     ?? '';
$error = $success = '';

// Add suspect
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form'] === 'add_suspect') {
    $sid = bin2hex(random_bytes(16));
    $sid = substr($sid,0,8).'-'.substr($sid,8,4).'-'.substr($sid,12,4).'-'.substr($sid,16,4).'-'.substr($sid,20);
    try {
        $db->prepare("INSERT INTO SUSPECT (SuspectID,Fname,Lname,Alias,Phone,City,NationalID,Status) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$sid,$_POST['fname'],$_POST['lname'],$_POST['alias'],$_POST['phone']?:null,$_POST['city']?:null,$_POST['national_id']?:null,$_POST['status']]);
        if ($_POST['link_case'] && $_POST['involvement']) {
            $db->prepare("INSERT INTO CASE_SUSPECT (CaseID,SuspectID,InvolvementType) VALUES (?,?,?)")
               ->execute([$_POST['link_case'], $sid, $_POST['involvement']]);
        }
        $success = 'Suspect record added.';
        $action = '';
    } catch (PDOException $e) {
        $error = 'Failed to add suspect. National ID may already exist.';
        $action = 'new';
    }
}

// Link to case
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['form'] === 'link_case') {
    try {
        $db->prepare("INSERT INTO CASE_SUSPECT (CaseID,SuspectID,InvolvementType) VALUES (?,?,?)")
           ->execute([$_POST['case_id'], $_POST['suspect_id'], $_POST['involvement']]);
        $success = 'Suspect linked to case.';
    } catch (PDOException $e) {
        $error = 'Already linked to that case.';
    }
}

$cases = $db->query("SELECT CaseID, CaseNumber FROM `CASE` ORDER BY CaseNumber")->fetchAll();

$suspects = $db->query("
    SELECT s.*, COUNT(DISTINCT cs.CaseID) AS CaseCount
    FROM SUSPECT s
    LEFT JOIN CASE_SUSPECT cs ON cs.SuspectID=s.SuspectID
    GROUP BY s.SuspectID
    ORDER BY s.Fname
")->fetchAll();

// Single suspect detail
$suspect = null;
if ($viewID) {
    $stmt = $db->prepare("SELECT * FROM SUSPECT WHERE SuspectID=?");
    $stmt->execute([$viewID]);
    $suspect = $stmt->fetch();

    $suspectCases = $db->prepare("
        SELECT c.CaseNumber, c.Status, cs.InvolvementType, ct.TypeName
        FROM CASE_SUSPECT cs
        JOIN `CASE` c ON c.CaseID=cs.CaseID
        JOIN COMPLAINT cp ON cp.ComplaintID=c.ComplaintID
        JOIN CYBER_CRIME_TYPE ct ON ct.CrimeTypeID=cp.CrimeTypeID
        WHERE cs.SuspectID=?
    ");
    $suspectCases->execute([$viewID]);
    $suspectCases = $suspectCases->fetchAll();
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Suspects</div>
    <div class="page-sub"><?= count($suspects) ?> suspect<?= count($suspects)!==1?'s':'' ?> on record</div>
  </div>
  <a href="?action=new" class="btn">+ Add Suspect</a>
</div>

<?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<?php if ($action === 'new'): ?>
<div class="card" style="max-width:700px;margin-bottom:24px;">
  <div class="card-header"><span class="card-title">Add New Suspect</span></div>
  <form method="POST">
    <input type="hidden" name="form" value="add_suspect">
    <div class="card-body" style="display:flex;flex-direction:column;gap:16px;">
      <div class="form-grid">
        <div class="form-group"><label>First Name *</label><input type="text" name="fname" required></div>
        <div class="form-group"><label>Last Name *</label><input type="text" name="lname" required></div>
        <div class="form-group"><label>Alias / Handle</label><input type="text" name="alias" placeholder="Online alias or nickname"></div>
        <div class="form-group"><label>National ID (CNIC)</label><input type="text" name="national_id" placeholder="Leave blank if unknown"></div>
        <div class="form-group"><label>Phone Number</label><input type="text" name="phone"></div>
        <div class="form-group"><label>City</label><input type="text" name="city" placeholder="Lahore"></div>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <option value="Person of Interest">Person of Interest</option>
            <option value="Suspect">Suspect</option>
            <option value="Charged">Charged</option>
            <option value="Acquitted">Acquitted</option>
            <option value="Convicted">Convicted</option>
          </select>
        </div>
      </div>
      <div style="border-top:1px solid var(--border2);padding-top:14px;">
        <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--text3);margin-bottom:10px;">Link to Case (optional)</div>
        <div class="form-grid">
          <div class="form-group">
            <label>Case</label>
            <select name="link_case">
              <option value="">— Don't link now —</option>
              <?php foreach ($cases as $c): ?>
                <option value="<?= htmlspecialchars($c['CaseID']) ?>"><?= htmlspecialchars($c['CaseNumber']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Involvement Type</label>
            <input type="text" name="involvement" placeholder="Primary Suspect / Accomplice">
          </div>
        </div>
      </div>
    </div>
    <div class="card-footer">
      <a href="suspects.php" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn">Add Suspect</button>
    </div>
  </form>
</div>
<?php endif; ?>

<?php if ($suspect): ?>
<div class="breadcrumb">
  <a href="suspects.php">Suspects</a>
  <span class="breadcrumb-sep">/</span>
  <span><?= htmlspecialchars($suspect['Fname'].' '.$suspect['Lname']) ?></span>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
  <div class="card">
    <div class="card-header"><span class="card-title">Suspect Profile</span></div>
    <div>
      <?php foreach ([
        'Name'        => $suspect['Fname'].' '.$suspect['Lname'],
        'Alias'       => $suspect['Alias'] ?: '—',
        'National ID' => $suspect['NationalID'] ?: 'Unknown',
        'Phone'       => $suspect['Phone'] ?: '—',
        'City'        => $suspect['City'] ?: '—',
        'Status'      => $suspect['Status'],
      ] as $lbl => $val): ?>
      <div class="detail-row">
        <span class="detail-label"><?= $lbl ?></span>
        <span class="detail-value"><?= htmlspecialchars($val) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">Cases Involved In</span></div>
    <div class="table-wrap">
      <?php if (empty($suspectCases)): ?>
        <div class="empty"><div class="empty-text">Not linked to any case.</div></div>
      <?php else: ?>
      <table>
        <thead><tr><th>Case No.</th><th>Crime</th><th>Involvement</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($suspectCases as $sc): ?>
          <tr>
            <td class="mono"><?= htmlspecialchars($sc['CaseNumber']) ?></td>
            <td><?= htmlspecialchars($sc['TypeName']) ?></td>
            <td><?= htmlspecialchars($sc['InvolvementType']) ?></td>
            <td><span class="tag tag-<?= strtolower($sc['Status']) ?>"><?= htmlspecialchars($sc['Status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php else: ?>
<div class="card">
  <div class="table-wrap">
    <?php if (empty($suspects)): ?>
      <div class="empty"><div class="empty-icon">🔍</div><div class="empty-text">No suspects on record.</div></div>
    <?php else: ?>
    <table>
      <thead><tr><th>Name</th><th>Alias</th><th>National ID</th><th>Phone</th><th>City</th><th>Status</th><th>Cases</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($suspects as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['Fname'].' '.$s['Lname']) ?></td>
          <td class="mono"><?= htmlspecialchars($s['Alias'] ?: '—') ?></td>
          <td class="mono"><?= htmlspecialchars($s['NationalID'] ?: 'Unknown') ?></td>
          <td class="mono"><?= htmlspecialchars($s['Phone'] ?: '—') ?></td>
          <td><?= htmlspecialchars($s['City'] ?: '—') ?></td>
          <td><span class="tag tag-<?= strtolower(str_replace(' ','-',$s['Status'])) ?>"><?= htmlspecialchars($s['Status']) ?></span></td>
          <td class="mono"><?= $s['CaseCount'] ?></td>
          <td><a href="?id=<?= urlencode($s['SuspectID']) ?>" class="btn btn-sm btn-outline">View</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
