<?php
// CryptoNexus First-Run Setup
// Visit: http://localhost/my-crypto/first-run.php
// Delele this file after setup is complete!

$host = 'localhost';
$user = 'root';
$pass = '';          // ← change if your MySQL has a password
$db   = 'cryptonexus';

$steps = []; $ok = true;

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $steps[] = ['✓','Connected to MySQL','green'];
} catch(Exception $e) {
    $steps[] = ['✗','Cannot connect: '.$e->getMessage(),'red'];
    $ok = false; goto show;
}

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db`");
    $steps[] = ['✓','Database "cryptonexus" ready','green'];
} catch(Exception $e) { $steps[] = ['✗','DB error: '.$e->getMessage(),'red']; $ok=false; goto show; }

$sql = file_get_contents(__DIR__.'/database.sql');
foreach(array_filter(array_map('trim',explode(';',$sql)),fn($s)=>strlen($s)>5&&!preg_match('/^(CREATE DATABASE|USE)\b/i,ltrim($s))) as $stmt) {
    try { $pdo->exec($stmt); } catch(Exception $e) { /* ignore duplicate key */ }
}
$steps[] = ['✓','Tables created','green'];

$pw   = 'Demo@12345';
$hash = password_hash($pw, PASSWORD_BCRYPT, ['cost'=>10]);

$pdo->exec("DELETE FROM users WHERE email IN ('admin@cryptonexus.com','john@example.com')");
$pdo->prepare("INSERT INTO users (username,email,password_hash,full_name,is_verified,is_admin) VALUES (?,?,?,?,1,1)")
    ->execute(['admin','admin@cryptonexus.com',$hash,'CryptoNexus Admin']);
$pdo->prepare("INSERT INTO users (username,email,password_hash,full_name,is_verified) VALUES (?,?,?,?,1)")
    ->execute(['johndoe','john@example.com',$hash,'John Doe']);

foreach($pdo->query("SELECT id FROM users WHERE email IN ('admin@cryptonexus.com','john@example.com')")->fetchAll(PDO::FETCH_COLUMN) as $uid) {
    foreach(['BTC'=>0.05,'ETH'=>1.5,'USDT'=>500,'BNB'=>2,'SOL'=>10] as $cur=>$bal) {
        try { $pdo->prepare("INSERT IGNORE INTO wallets (user_id,currency,balance) VALUES (?,?,?)")->execute([$uid,$cur,$bal]); } catch(Exception $e){}
    }
}
$steps[] = ['✓',"Demo accounts created — password: <b>{$pw}</b>",'green'];

show:
?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CryptoNexus Setup</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}body{background:#070b14;color:#e2e8f0;font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:2rem}
.box{background:#111827;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:2.5rem;max-width:600px;width:100%}
h2{font-size:1.5rem;font-weight:700;margin-bottom:1.5rem}h2 span{color:#3b82f6}
.step{display:flex;gap:.75rem;align-items:flex-start;margin-bottom:1rem}
.ico{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0}
.green-ico{background:rgba(16,185,129,.15);color:#10b981}.red-ico{background:rgba(239,68,68,.15);color:#ef4444}
.msg{font-size:.9rem;padding-top:4px}
.cta{background:#3b82f6;color:#fff;border:none;padding:.75rem 1.5rem;border-radius:10px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;margin-top:1.5rem;font-size:.95rem}
.cta:hover{background:#2563eb}.out{background:transparent;border:1px solid rgba(255,255,255,.15);color:#e2e8f0}
.acc{background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);border-radius:10px;padding:1rem;margin-top:1.25rem}
.acc b{color:#e2e8f0}code{background:#1e293b;padding:2px 7px;border-radius:5px;color:#93c5fd;font-size:.85rem}
.warn{background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:8px;padding:.75rem 1rem;margin-top:1rem;font-size:.83rem;color:#f59e0b}
</style></head><body>
<div class="box">
  <h2>⬡ Crypto<span>Nexus</span> Setup</h2>
  <?php foreach($steps as [$i,$m,$c]): ?>
  <div class="step"><div class="ico <?=$c?>-ico"><?=$i?></div><div class="msg"><?=$m?></div></div>
  <?php endforeach; ?>
  <?php if($ok): ?>
  <div class="acc">
    <b>✓ Setup complete! Login with either account:</b><br><br>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-top:.25rem">
      <div style="background:rgba(255,255,255,.04);border-radius:8px;padding:.75rem">
        <div style="font-weight:600;margin-bottom:.25rem">👤 Demo User</div>
        <div style="font-size:.82rem;color:#94a3b8">john@example.com</div>
        <div style="font-size:.82rem;color:#94a3b8">Password: <code>Demo@12345</code></div>
      </div>
      <div style="background:rgba(255,255,255,.04);border-radius:8px;padding:.75rem">
        <div style="font-weight:600;margin-bottom:.25rem">🛡 Admin</div>
        <div style="font-size:.82rem;color:#94a3b8">admin@cryptonexus.com</div>
        <div style="font-size:.82rem;color:#94a3b8">Password: <code>Demo@12345</code></div>
      </div>
    </div>
  </div>
  <div style="display:flex;gap:.75rem;flex-wrap:wrap">
    <a href="index.php" class="cta">🚀 Open CryptoNexus</a>
    <a href="login.php" class="cta out">Login Page</a>
  </div>
  <div class="warn">⚠ Delete <code>first-run.php</code> from your folder after setup!</div>
  <?php else: ?>
  <div style="margin-top:1rem;color:#ef4444;font-size:.88rem">Fix the error above, then refresh this page. <br>If DB connection failed, open <code>first-run.php</code> and update the <code>$pass</code> variable on line 5.</div>
  <?php endif; ?>
</div></body></html>
