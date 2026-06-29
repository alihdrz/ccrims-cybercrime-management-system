<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireLogin();
if ($_SESSION['user_type'] !== 'citizen') { header('Location: /ccrims/pages/officer/dashboard.php'); exit; }

$pageTitle = 'File a Complaint';
$db        = getDB();
$uid       = currentUser()['id'];
$error     = $success = '';

// Load crime types
$types = $db->query("SELECT CrimeTypeID, TypeName, Description FROM CYBER_CRIME_TYPE ORDER BY TypeName")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $desc       = trim($_POST['description'] ?? '');
    $crimeType  = $_POST['crime_type'] ?? '';

    if (!$desc || !$crimeType) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $cid = bin2hex(random_bytes(16));
            $cid = substr($cid,0,8).'-'.substr($cid,8,4).'-'.substr($cid,12,4).'-'.substr($cid,16,4).'-'.substr($cid,20);

            $db->prepare("INSERT INTO COMPLAINT (ComplaintID,Description,LodgeDate,Status,CitizenID,CrimeTypeID) VALUES (?,?,CURDATE(),'Pending',?,?)")
               ->execute([$cid, $desc, $uid, $crimeType]);

            $success = 'Complaint filed successfully. Reference ID: ' . htmlspecialchars($cid);
        } catch (PDOException $e) {
            $error = 'Failed to submit complaint. Please try again.';
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="breadcrumb">
  <a href="/ccrims/pages/citizen/dashboard.php">Dashboard</a>
  <span class="breadcrumb-sep">/</span>
  <span>File a Complaint</span>
</div>

<div class="page-header">
  <div>
    <div class="page-title">File a Cyber Crime Complaint</div>
    <div class="page-sub">Provide as much detail as possible to help investigators</div>
  </div>
</div>

<?php if ($error):   ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= $success ?> — <a href="/ccrims/pages/citizen/dashboard.php">View my complaints →</a></div><?php endif; ?>

<?php if (!$success): ?>
<div class="card" style="max-width:700px;">
  <div class="card-header"><span class="card-title">Complaint Details</span></div>
  <form method="POST">
    <div class="card-body" style="display:flex;flex-direction:column;gap:18px;">

      <div class="form-group">
        <label>Crime Type <span style="color:var(--danger)">*</span></label>
        <select name="crime_type" required onchange="updateHint(this)">
          <option value="">— Select crime type —</option>
          <?php foreach ($types as $t): ?>
            <option value="<?= htmlspecialchars($t['CrimeTypeID']) ?>"
              data-desc="<?= htmlspecialchars($t['Description']) ?>"
              <?= ($_POST['crime_type'] ?? '') === $t['CrimeTypeID'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($t['TypeName']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <span class="form-hint" id="type-hint"></span>
      </div>

      <div class="form-group">
        <label>Detailed Description <span style="color:var(--danger)">*</span></label>
        <textarea name="description" required rows="6" placeholder="Describe the incident in detail: what happened, when it happened, any links or phone numbers involved, financial loss if any..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        <span class="form-hint">Be specific — include dates, amounts, platforms, or any suspicious contact information.</span>
      </div>

    </div>
    <div class="card-footer">
      <a href="/ccrims/pages/citizen/dashboard.php" class="btn btn-outline">Cancel</a>
      <button type="submit" class="btn">Submit Complaint</button>
    </div>
  </form>
</div>
<?php endif; ?>

<script>
function updateHint(sel) {
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('type-hint').textContent = opt.dataset.desc || '';
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
