<?php
require_once dirname(__DIR__) . '/config/app.php';
requireAdmin();

$pdo = getDb();

// Handle inline status update if submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $enquiryId = (int)($_POST['enquiry_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $allowed = ['new', 'in_progress', 'contacted', 'converted', 'closed'];
    if ($enquiryId > 0 && in_array($newStatus, $allowed, true)) {
        $stmt = $pdo->prepare("UPDATE enquiries SET status = ?, notes = ? WHERE id = ?");
        $stmt->execute([$newStatus, $notes, $enquiryId]);
        header("Location: dashboard.php?msg=updated");
        exit;
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $enquiryId = (int)($_POST['enquiry_id'] ?? 0);
    if ($enquiryId > 0) {
        $stmt = $pdo->prepare("DELETE FROM enquiries WHERE id = ?");
        $stmt->execute([$enquiryId]);
        header("Location: dashboard.php?msg=deleted");
        exit;
    }
}

// Metrics
$totalEnquiries = (int)$pdo->query("SELECT COUNT(*) FROM enquiries")->fetchColumn();
$newEnquiries = (int)$pdo->query("SELECT COUNT(*) FROM enquiries WHERE status = 'new'")->fetchColumn();
$contactedEnquiries = (int)$pdo->query("SELECT COUNT(*) FROM enquiries WHERE status IN ('contacted', 'in_progress')")->fetchColumn();

$totalCandidates = 0;
try {
    $totalCandidates = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
} catch (Exception $e) {}

// Filters
$search = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR organisation LIKE ?)";
    $term = "%$search%";
    $params[] = $term; $params[] = $term; $params[] = $term; $params[] = $term;
}
if (!empty($roleFilter)) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
}
if (!empty($statusFilter)) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("SELECT * FROM enquiries $whereSql ORDER BY created_at DESC LIMIT 50");
$stmt->execute($params);
$enquiries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — PRAYAS Platform</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#0b1220;--surface:#151f32;--card:#1a2742;--line:#22314d;--ink:#f8fafc;--muted:#94a3b8;--brand:#e11d48;--green:#10b981;--blue:#38bdf8;--amber:#f59e0b;}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Manrope',sans-serif}
body{background:var(--bg);color:var(--ink);min-height:100vh}
header{background:var(--surface);border-bottom:1px solid var(--line);padding:14px 24px;display:flex;align-items:center;justify-content:space-between}
.brand{display:flex;align-items:center;gap:12px}
.brand h2{font-family:'Sora',sans-serif;font-size:20px;font-weight:800;color:#fff}
.brand span{background:var(--brand);color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;text-transform:uppercase}
.nav-actions{display:flex;align-items:center;gap:16px}
.nav-actions a{color:#cbd5e1;text-decoration:none;font-size:14px;font-weight:600}
.nav-actions a:hover{color:#fff}
.btn{background:var(--brand);color:#fff;padding:8px 16px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:700;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.btn-secondary{background:var(--card);border:1px solid var(--line)}
.btn-secondary:hover{background:var(--line)}
.wrap{max-width:1300px;margin:24px auto;padding:0 24px}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:20px}
.stat-card span{display:block;font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:1px;font-weight:700}
.stat-card h3{font-family:'Sora',sans-serif;font-size:32px;font-weight:800;margin-top:6px}
.card{background:var(--surface);border:1px solid var(--line);border-radius:12px;padding:20px;margin-bottom:24px}
.filter-bar{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px}
.filter-bar input,.filter-bar select{background:var(--bg);border:1px solid var(--line);color:#fff;padding:10px 14px;border-radius:8px;font-size:14px;outline:none}
.filter-bar input{flex:1;min-width:240px}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse;text-align:left;font-size:13px}
th{background:#0d1627;padding:12px 14px;font-weight:700;color:var(--muted);border-bottom:1px solid var(--line);text-transform:uppercase;letter-spacing:.5px}
td{padding:14px;border-bottom:1px solid var(--line);vertical-align:top}
tr:hover{background:rgba(255,255,255,.02)}
.badge{display:inline-block;padding:3px 8px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase}
.badge-new{background:rgba(225,29,72,.2);color:#fda4af;border:1px solid rgba(225,29,72,.4)}
.badge-in_progress{background:rgba(245,158,11,.2);color:#fde68a;border:1px solid rgba(245,158,11,.4)}
.badge-contacted{background:rgba(56,189,248,.2);color:#bae6fd;border:1px solid rgba(56,189,248,.4)}
.badge-converted{background:rgba(16,185,129,.2);color:#a7f3d0;border:1px solid rgba(16,185,129,.4)}
.badge-closed{background:rgba(148,163,184,.2);color:#cbd5e1;border:1px solid rgba(148,163,184,.4)}
.action-btn{background:transparent;border:1px solid var(--line);color:#cbd5e1;padding:4px 8px;border-radius:6px;font-size:11px;cursor:pointer}
.action-btn:hover{background:var(--line);color:#fff}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.7);z-index:999;align-items:center;justify-content:center}
.modal.on{display:flex}
.modal-content{background:var(--surface);border:1px solid var(--line);border-radius:12px;max-width:540px;width:90%;padding:24px}
.modal-content h3{margin-bottom:16px}
.modal-content label{display:block;font-size:12px;font-weight:600;margin-bottom:4px;color:var(--muted)}
.modal-content select,.modal-content textarea{width:100%;background:var(--bg);border:1px solid var(--line);color:#fff;padding:10px;border-radius:8px;font-size:13px;margin-bottom:12px;outline:none}
</style>
</head>
<body>

<header>
  <div class="brand">
    <h2>PRAYAS</h2>
    <span>Master Admin</span>
  </div>
  <div class="nav-actions">
    <a href="../student-portal/admin/dashboard.php" target="_blank">Candidate Portal Admin ↗</a>
    <a href="../index.html" target="_blank">View Site ↗</a>
    <span style="color:var(--muted);font-size:13px">Logged in as: <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></strong></span>
    <a href="logout.php" class="btn btn-secondary" style="padding:6px 12px">Logout</a>
  </div>
</header>

<div class="wrap">
  <div class="stats-grid">
    <div class="stat-card">
      <span>Total Website Enquiries</span>
      <h3 style="color:var(--blue)"><?= number_format($totalEnquiries) ?></h3>
    </div>
    <div class="stat-card">
      <span>New Unhandled</span>
      <h3 style="color:var(--brand)"><?= number_format($newEnquiries) ?></h3>
    </div>
    <div class="stat-card">
      <span>In Progress / Contacted</span>
      <h3 style="color:var(--amber)"><?= number_format($contactedEnquiries) ?></h3>
    </div>
    <div class="stat-card">
      <span>Student Candidates</span>
      <h3 style="color:var(--green)"><?= number_format($totalCandidates) ?></h3>
    </div>
  </div>

  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px">
      <div>
        <h3 style="font-family:'Sora',sans-serif">Website Enquiries & Leads</h3>
        <p style="color:var(--muted);font-size:13px;margin-top:4px">All enquiries submitted through the contact and application forms.</p>
      </div>
      <div>
        <a href="../api/enquiries-export.php?search=<?= urlencode($search) ?>&role=<?= urlencode($roleFilter) ?>&status=<?= urlencode($statusFilter) ?>" class="btn btn-secondary">📥 Export to CSV</a>
      </div>
    </div>

    <form method="GET" class="filter-bar">
      <input type="text" name="search" placeholder="Search by name, email, phone, organisation..." value="<?= htmlspecialchars($search) ?>">
      <select name="role">
        <option value="">All Roles</option>
        <option value="Student / Graduate" <?= $roleFilter === 'Student / Graduate' ? 'selected' : '' ?>>Student / Graduate</option>
        <option value="MSME / Manufacturer" <?= $roleFilter === 'MSME / Manufacturer' ? 'selected' : '' ?>>MSME / Manufacturer</option>
        <option value="Government / Policy" <?= $roleFilter === 'Government / Policy' ? 'selected' : '' ?>>Government / Policy</option>
        <option value="Academic Institution" <?= $roleFilter === 'Academic Institution' ? 'selected' : '' ?>>Academic Institution</option>
        <option value="CSR / Foundation" <?= $roleFilter === 'CSR / Foundation' ? 'selected' : '' ?>>CSR / Foundation</option>
      </select>
      <select name="status">
        <option value="">All Statuses</option>
        <option value="new" <?= $statusFilter === 'new' ? 'selected' : '' ?>>New</option>
        <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
        <option value="contacted" <?= $statusFilter === 'contacted' ? 'selected' : '' ?>>Contacted</option>
        <option value="converted" <?= $statusFilter === 'converted' ? 'selected' : '' ?>>Converted</option>
        <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
      </select>
      <button type="submit" class="btn">Filter</button>
      <?php if (!empty($search) || !empty($roleFilter) || !empty($statusFilter)): ?>
        <a href="dashboard.php" class="btn btn-secondary">Reset</a>
      <?php endif; ?>
    </form>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name & Contact</th>
            <th>Organisation</th>
            <th>Role</th>
            <th>Message</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($enquiries)): ?>
            <tr>
              <td colspan="8" style="text-align:center;padding:30px;color:var(--muted)">No enquiries match the criteria.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($enquiries as $row): ?>
              <tr>
                <td>#<?= $row['id'] ?></td>
                <td>
                  <strong><?= htmlspecialchars($row['name']) ?></strong><br>
                  <a href="mailto:<?= htmlspecialchars($row['email']) ?>" style="color:var(--blue);text-decoration:none"><?= htmlspecialchars($row['email']) ?></a><br>
                  <a href="tel:<?= htmlspecialchars($row['phone']) ?>" style="color:var(--muted);text-decoration:none"><?= htmlspecialchars($row['phone']) ?></a>
                </td>
                <td><?= htmlspecialchars($row['organisation'] ?: '-') ?></td>
                <td><span style="font-weight:600"><?= htmlspecialchars($row['role']) ?></span></td>
                <td style="max-width:320px;word-break:break-word">
                  <?= nl2br(htmlspecialchars($row['message'])) ?>
                  <?php if (!empty($row['notes'])): ?>
                    <div style="margin-top:6px;background:#0d1627;padding:6px 10px;border-radius:6px;font-size:12px;color:#cbd5e1">
                      <strong>Note:</strong> <?= htmlspecialchars($row['notes']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge badge-<?= htmlspecialchars($row['status']) ?>">
                    <?= htmlspecialchars(str_replace('_', ' ', $row['status'])) ?>
                  </span>
                </td>
                <td style="color:var(--muted);font-size:12px;white-space:nowrap">
                  <?= date('M d, Y h:i A', strtotime($row['created_at'])) ?>
                </td>
                <td>
                  <button class="action-btn" onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)">Edit Status</button>
                  <form method="POST" style="display:inline" onsubmit="return confirm('Delete enquiry #<?= $row['id'] ?>?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="enquiry_id" value="<?= $row['id'] ?>">
                    <button type="submit" class="action-btn" style="color:#fda4af;border-color:rgba(225,29,72,.3)">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
  <div class="modal-content">
    <h3>Update Enquiry Status</h3>
    <form method="POST" id="editForm">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="enquiry_id" id="modalEnquiryId">
      
      <p id="modalName" style="font-weight:700;margin-bottom:12px"></p>

      <label for="modalStatus">Status</label>
      <select name="status" id="modalStatus">
        <option value="new">New</option>
        <option value="in_progress">In Progress</option>
        <option value="contacted">Contacted</option>
        <option value="converted">Converted</option>
        <option value="closed">Closed</option>
      </select>

      <label for="modalNotes">Internal Admin Notes</label>
      <textarea name="notes" id="modalNotes" rows="3" placeholder="Add follow-up notes, conversation logs, etc."></textarea>

      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:10px">
        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
        <button type="submit" class="btn">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(row) {
  document.getElementById('modalEnquiryId').value = row.id;
  document.getElementById('modalName').textContent = '#' + row.id + ' — ' + row.name + ' (' + row.role + ')';
  document.getElementById('modalStatus').value = row.status;
  document.getElementById('modalNotes').value = row.notes || '';
  document.getElementById('editModal').classList.add('on');
}
function closeEditModal() {
  document.getElementById('editModal').classList.remove('on');
}
window.onclick = function(e) {
  if (e.target === document.getElementById('editModal')) {
    closeEditModal();
  }
};
</script>
</body>
</html>
