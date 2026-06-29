<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
requireStaff();

$pageTitle = 'Dashboard';
$db   = getDB();
$user = currentUser();

// Stats
$stats = $db->query("
    SELECT
      (SELECT COUNT(*) FROM `CASE`)             AS total_cases,
      (SELECT COUNT(*) FROM `CASE` WHERE Status='Active')  AS active_cases,
      (SELECT COUNT(*) FROM `CASE` WHERE Status='Open')    AS open_cases,
      (SELECT COUNT(*) FROM COMPLAINT)          AS total_complaints,
      (SELECT COUNT(*) FROM COMPLAINT WHERE Status='Pending') AS pending_complaints,
      (SELECT COUNT(*) FROM SUSPECT)            AS total_suspects,
      (SELECT COUNT(*) FROM DIGITAL_EVIDENCE)   AS total_evidence,
      (SELECT COUNT(*) FROM WARRANT WHERE Status='Active') AS active_warrants
")->fetch();

// Recent cases
$recentCases = $db->query("
    SELECT c.CaseID, c.CaseNumber, c.Status, c.Priority, c.OpenDate,
           ct.TypeName AS CrimeType,
           CONCAT(cit.Fname,' ',cit.Lname) AS Complainant
    FROM `CASE` c
    JOIN COMPLAINT cp ON cp.ComplaintID = c.ComplaintID
    JOIN CYBER_CRIME_TYPE ct ON ct.CrimeTypeID = cp.CrimeTypeID
    LEFT JOIN CITIZEN cit ON cit.CitizenID = cp.CitizenID
    ORDER BY c.OpenDate DESC
    LIMIT 8
")->fetchAll();

// Recent complaints
$recentComplaints = $db->query("
    SELECT cp.ComplaintID, cp.LodgeDate, cp.Status, ct.TypeName,
           CONCAT(cit.Fname,' ',cit.Lname) AS Complainant
    FROM COMPLAINT cp
    JOIN CYBER_CRIME_TYPE ct ON ct.CrimeTypeID = cp.CrimeTypeID
    LEFT JOIN CITIZEN cit ON cit.CitizenID = cp.CitizenID
    ORDER BY cp.LodgeDate DESC
    LIMIT 5
")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
  <div>
    <div class="page-title">Dashboard</div>
    <div class="page-sub">Welcome back, <?= htmlspecialchars($user['name']) ?> · <?= htmlspecialchars($user['role']) ?></div>
  </div>
  <a href="/ccrims/pages/officer/cases.php?action=new" class="btn">+ New Case</a>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-label">Total Cases</div>
    <div class="stat-value"><?= $stats['total_cases'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Active Cases</div>
    <div class="stat-value" style="color:var(--ok)"><?= $stats['active_cases'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Open Cases</div>
    <div class="stat-value" style="color:var(--accent)"><?= $stats['open_cases'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Pending Complaints</div>
    <div class="stat-value" style="color:var(--warn)"><?= $stats['pending_complaints'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Suspects</div>
    <div class="stat-value"><?= $stats['total_suspects'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Evidence Items</div>
    <div class="stat-value"><?= $stats['total_evidence'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Active Warrants</div>
    <div class="stat-value" style="color:var(--critical)"><?= $stats['active_warrants'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Complaints</div>
    <div class="stat-value"><?= $stats['total_complaints'] ?></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start;">

  <!-- Recent Cases -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Recent Cases</span>
      <a href="/ccrims/pages/officer/cases.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Case No.</th><th>Crime Type</th><th>Complainant</th><th>Priority</th><th>Status</th><th>Opened</th></tr>
        </thead>
        <tbody>
          <?php foreach ($recentCases as $c): ?>
          <tr>
            <td><a href="/ccrims/pages/officer/case_detail.php?id=<?= urlencode($c['CaseID']) ?>" class="mono" style="color:var(--accent);text-decoration:none;"><?= htmlspecialchars($c['CaseNumber']) ?></a></td>
            <td><?= htmlspecialchars($c['CrimeType']) ?></td>
            <td><?= htmlspecialchars($c['Complainant'] ?: '—') ?></td>
            <td><span class="tag tag-<?= strtolower($c['Priority']) ?>"><?= htmlspecialchars($c['Priority']) ?></span></td>
            <td><span class="tag tag-<?= strtolower($c['Status']) ?>"><?= htmlspecialchars($c['Status']) ?></span></td>
            <td class="mono"><?= htmlspecialchars($c['OpenDate']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Complaints -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Pending Complaints</span>
      <a href="/ccrims/pages/officer/complaints.php" class="btn btn-sm btn-outline">View All</a>
    </div>
    <div style="padding:0;">
      <?php if (empty($recentComplaints)): ?>
        <div class="empty"><div class="empty-text">No complaints yet</div></div>
      <?php else: ?>
        <?php foreach ($recentComplaints as $cp): ?>
        <div style="padding:12px 16px;border-bottom:1px solid var(--border2);display:flex;flex-direction:column;gap:4px;">
          <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
            <span style="font-size:13px;font-weight:500;"><?= htmlspecialchars($cp['TypeName']) ?></span>
            <span class="tag tag-<?= strtolower(str_replace(' ','-',$cp['Status'])) ?>"><?= htmlspecialchars($cp['Status']) ?></span>
          </div>
          <div style="font-size:12px;color:var(--text3);"><?= htmlspecialchars($cp['Complainant'] ?: 'Organization') ?> · <?= htmlspecialchars($cp['LodgeDate']) ?></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
