<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireLogin();
if ($_SESSION['user_type'] !== 'citizen') { header('Location: /ccrims/pages/officer/dashboard.php'); exit; }

$pageTitle = 'My Dashboard';
$db  = getDB();
$uid = currentUser()['id'];

$complaints = $db->prepare("
    SELECT c.ComplaintID, c.Description, c.LodgeDate, c.Status,
           ct.TypeName,
           ca.CaseNumber, ca.Status AS CaseStatus
    FROM COMPLAINT c
    JOIN CYBER_CRIME_TYPE ct ON ct.CrimeTypeID = c.CrimeTypeID
    LEFT JOIN `CASE` ca ON ca.ComplaintID = c.ComplaintID
    WHERE c.CitizenID = ?
    ORDER BY c.LodgeDate DESC
");
$complaints->execute([$uid]);
$rows = $complaints->fetchAll();

$total    = count($rows);
$pending  = count(array_filter($rows, fn($r) => $r['Status'] === 'Pending'));
$resolved = count(array_filter($rows, fn($r) => $r['Status'] === 'Resolved'));

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Welcome back, <?= htmlspecialchars(currentUser()['name']) ?></div>
    <div class="page-sub">Your complaint history and case statuses</div>
  </div>
  <a href="/ccrims/pages/citizen/complaint_form.php" class="btn">+ File New Complaint</a>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Total Complaints</div>
    <div class="stat-value"><?= $total ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Pending</div>
    <div class="stat-value"><?= $pending ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Resolved</div>
    <div class="stat-value"><?= $resolved ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Active Cases</div>
    <div class="stat-value"><?= count(array_filter($rows, fn($r) => $r['CaseStatus'] === 'Active')) ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">My Complaints</span>
  </div>
  <div class="table-wrap">
    <?php if (empty($rows)): ?>
      <div class="empty">
        <div class="empty-icon">📋</div>
        <div class="empty-text">No complaints filed yet. <a href="/ccrims/pages/citizen/complaint_form.php">File your first complaint →</a></div>
      </div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Crime Type</th>
          <th>Description</th>
          <th>Status</th>
          <th>Case No.</th>
          <th>Case Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td class="mono"><?= htmlspecialchars($r['LodgeDate']) ?></td>
          <td><?= htmlspecialchars($r['TypeName']) ?></td>
          <td style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($r['Description']) ?></td>
          <td><span class="tag tag-<?= strtolower(str_replace(' ','-',$r['Status'])) ?>"><?= htmlspecialchars($r['Status']) ?></span></td>
          <td class="mono"><?= $r['CaseNumber'] ? htmlspecialchars($r['CaseNumber']) : '<span style="color:var(--text3)">—</span>' ?></td>
          <td><?= $r['CaseStatus'] ? '<span class="tag tag-'.strtolower($r['CaseStatus']).'">'.htmlspecialchars($r['CaseStatus']).'</span>' : '<span style="color:var(--text3)">—</span>' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
