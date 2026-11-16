<?php
require_once dirname(__DIR__) . '/config/app.php';

$error = '';
if (isAdminLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        $pdo = getDb();
        $stmt = $pdo->prepare("SELECT id, name, email, password, role, status FROM admin WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            if (($admin['status'] ?? 'active') !== 'active') {
                $error = "This admin account is inactive.";
            } else {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_logged_in'] = true;

                $pdo->prepare("UPDATE admin SET last_login = CURRENT_TIMESTAMP WHERE id = ?")->execute([$admin['id']]);
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — PRAYAS Platform</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#0b1220;--surface:#151f32;--line:#22314d;--ink:#f8fafc;--muted:#94a3b8;--brand:#e11d48;--brand-hover:#be123c;}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Manrope',sans-serif}
body{background:var(--bg);color:var(--ink);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.login-box{background:var(--surface);border:1px solid var(--line);border-radius:16px;max-width:420px;width:100%;padding:36px;box-shadow:0 20px 40px rgba(0,0,0,0.4)}
.brand{text-align:center;margin-bottom:28px}
.brand h1{font-family:'Sora',sans-serif;font-size:24px;font-weight:800;letter-spacing:1px;color:#fff}
.brand span{display:block;font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:1.5px;margin-top:4px}
.field{margin-bottom:18px}
label{display:block;font-size:13px;font-weight:600;margin-bottom:6px;color:#cbd5e1}
input{width:100%;padding:12px 14px;background:#0b1220;border:1px solid var(--line);border-radius:8px;color:#fff;font-size:14px;outline:none;transition:border-color .2s}
input:focus{border-color:var(--brand)}
.btn{width:100%;padding:12px;background:var(--brand);color:#fff;border:none;border-radius:8px;font-weight:700;font-size:15px;cursor:pointer;transition:background .2s}
.btn:hover{background:var(--brand-hover)}
.alert{background:rgba(225,29,72,.15);border:1px solid var(--brand);color:#fda4af;padding:12px;border-radius:8px;font-size:13px;margin-bottom:18px}
.meta-links{margin-top:20px;text-align:center;font-size:13px;color:var(--muted)}
.meta-links a{color:#38bdf8;text-decoration:none}
.meta-links a:hover{text-decoration:underline}
</style>
</head>
<body>
<div class="login-box">
  <div class="brand">
    <h1>PRAYAS</h1>
    <span>Administration Portal</span>
  </div>

  <?php if (!empty($error)): ?>
    <div class="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="field">
      <label for="email">Admin Email</label>
      <input type="email" id="email" name="email" placeholder="admin@prayas.org" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn">Sign In to Dashboard</button>
  </form>

  <div class="meta-links">
    <a href="../index.html">← Back to Main Website</a> &nbsp;|&nbsp; 
    <a href="../student-portal/admin/login.php">Candidate Admin Panel →</a>
  </div>
</div>
</body>
</html>
