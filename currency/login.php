<?php
require_once __DIR__ . '/config.php';
startSession();
if (isLoggedIn()) { header('Location: ' . APP_URL . '/dashboard.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $user  = DB::one('SELECT * FROM users WHERE email=? OR username=?', [$email, $email]);
    if ($user && password_verify($pass, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email']    = $user['email'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
    $err = 'Invalid email or password.';
}
pageHead('Login — CryptoNexus');
?>
<div class="auth-wrap">
<div class="auth-card">
  <div style="text-align:center;margin-bottom:1.75rem">
    <a href="<?= APP_URL ?>/index.php" style="text-decoration:none;display:inline-flex;align-items:center;gap:.5rem">
      <i class="bi bi-hexagon-fill" style="color:var(--primary);font-size:1.8rem"></i>
      <span style="font-size:1.3rem;font-weight:700;color:#fff">Crypto<span style="color:var(--primary)">Nexus</span></span>
    </a>
    <p style="color:var(--muted);font-size:.85rem;margin:.5rem 0 0">Sign in to your account</p>
  </div>

  <?php if ($err): ?>
  <div style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:.75rem 1rem;color:#ef4444;font-size:.875rem;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem">
    <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($err) ?>
  </div>
  <?php endif; ?>

  <form method="POST">
    <div style="margin-bottom:1rem">
      <label class="flbl">Email or Username</label>
      <div class="ig ig-pre">
        <div class="ig-txt"><i class="bi bi-envelope"></i></div>
        <input class="fc" type="text" name="email" placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email']??'') ?>" required>
      </div>
    </div>
    <div style="margin-bottom:1.25rem">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem">
        <label class="flbl" style="margin:0">Password</label>
        <a href="#" style="font-size:.78rem;color:var(--primary);text-decoration:none">Forgot password?</a>
      </div>
      <div class="ig ig-pre" style="position:relative">
        <div class="ig-txt"><i class="bi bi-lock"></i></div>
        <input class="fc" type="password" name="password" id="pw" placeholder="••••••••" required>
        <button type="button" class="toggle-pw" data-t="#pw" style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem">👁</button>
      </div>
    </div>
    <button type="submit" class="btn btn-pri" style="width:100%;padding:.7rem;font-size:.95rem"><i class="bi bi-box-arrow-in-right"></i> Sign In</button>
  </form>

  <div style="text-align:center;margin:1.25rem 0;color:var(--muted);font-size:.8rem;position:relative">
    <div style="border-top:1px solid var(--border);position:absolute;top:50%;left:0;right:0"></div>
    <span style="background:var(--bg2);padding:0 .75rem;position:relative">Demo Accounts</span>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem;margin-bottom:1.25rem">
    <button class="btn btn-out btn-sm demo" data-e="john@example.com" data-p="Demo@12345"><i class="bi bi-person"></i> Demo User</button>
    <button class="btn btn-out btn-sm demo" data-e="admin@cryptonexus.com" data-p="Demo@12345" style="color:var(--gold);border-color:rgba(245,158,11,.4)"><i class="bi bi-shield"></i> Admin</button>
  </div>

  <p style="text-align:center;font-size:.85rem;color:var(--muted);margin:0">
    No account? <a href="<?= APP_URL ?>/register.php" style="color:var(--primary);text-decoration:none;font-weight:600">Create one free</a>
  </p>
</div>
</div>
<?php pageFooter(); ?>
<script>
document.querySelectorAll('.demo').forEach(b=>b.addEventListener('click',()=>{
  document.querySelector('[name=email]').value=b.dataset.e;
  document.querySelector('[name=password]').value=b.dataset.p;
  b.closest('form')?.submit()||document.querySelector('form').submit();
}));
</script>
