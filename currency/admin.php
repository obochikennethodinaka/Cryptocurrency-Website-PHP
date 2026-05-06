<?php
require_once __DIR__ . '/config.php';
requireAdmin();
$stats = [
    'users'  => DB::one('SELECT COUNT(*) c FROM users')['c'] ?? 0,
    'trades' => DB::one('SELECT COUNT(*) c FROM transactions')['c'] ?? 0,
    'alerts' => DB::one('SELECT COUNT(*) c FROM price_alerts WHERE is_active=1')['c'] ?? 0,
    'articles'=> DB::one('SELECT COUNT(*) c FROM articles')['c'] ?? 0,
];
$users = DB::all('SELECT id,username,email,full_name,is_verified,is_admin,created_at FROM users ORDER BY created_at DESC LIMIT 15');
pageHead('Admin Panel — CryptoNexus');
navbar();
?>
<main class="pw" style="padding-bottom:3rem">
<div style="max-width:1200px;margin:0 auto;padding:0 1.25rem">

  <div style="display:flex;align-items:center;gap:.85rem;margin-bottom:1.25rem">
    <div class="sico" style="background:rgba(245,158,11,.15);color:var(--gold);width:48px;height:48px;border-radius:12px"><i class="bi bi-shield-check" style="font-size:1.3rem"></i></div>
    <div><h4 style="font-weight:700;margin:0">Admin Panel</h4><p style="color:var(--muted);font-size:.82rem;margin:0">CryptoNexus platform management</p></div>
  </div>

  <!-- Stats -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.85rem;margin-bottom:1.5rem">
    <?php foreach([
      ['Total Users','users','bi-people-fill','var(--primary)','rgba(59,130,246,.15)'],
      ['Total Trades','trades','bi-arrow-left-right','var(--green)','rgba(16,185,129,.15)'],
      ['Articles','articles','bi-newspaper','var(--purple)','rgba(139,92,246,.15)'],
      ['Active Alerts','alerts','bi-bell-fill','var(--gold)','rgba(245,158,11,.15)'],
    ] as [$lbl,$key,$ico,$col,$bg]): ?>
    <div class="scard">
      <div style="display:flex;align-items:start;justify-content:space-between">
        <div><div class="slbl"><?=$lbl?></div><div class="sval"><?=number_format((int)$stats[$key])?></div></div>
        <div class="sico" style="background:<?=$bg?>;color:<?=$col?>"><i class="bi <?=$ico?>"></i></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Users table -->
  <div class="card" style="overflow:hidden;margin-bottom:1.25rem">
    <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
      <h6 style="font-weight:700;margin:0">Recent Users</h6>
      <span class="badge badge-neu"><?=count($users)?></span>
    </div>
    <div style="overflow-x:auto">
    <table class="tbl">
      <thead><tr><th>ID</th><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
      <tbody>
      <?php foreach($users as $u): ?>
      <tr>
        <td style="color:var(--muted);font-size:.82rem">#<?=$u['id']?></td>
        <td><div style="display:flex;align-items:center;gap:.55rem">
          <div class="avatar av-sm"><?=strtoupper(substr($u['username'],0,1))?></div>
          <div><div style="font-weight:600;font-size:.85rem"><?=htmlspecialchars($u['username'])?></div>
          <?php if($u['full_name']): ?><div style="font-size:.72rem;color:var(--muted)"><?=htmlspecialchars($u['full_name'])?></div><?php endif; ?></div>
        </div></td>
        <td style="color:var(--muted);font-size:.83rem"><?=htmlspecialchars($u['email'])?></td>
        <td><span class="badge <?=$u['is_admin']?'badge-warn':'badge-neu'?>"><?=$u['is_admin']?'Admin':'User'?></span></td>
        <td><span class="badge <?=$u['is_verified']?'badge-up':'badge-neu'?>"><?=$u['is_verified']?'Verified':'Pending'?></span></td>
        <td style="color:var(--muted);font-size:.82rem"><?=date('M j, Y',strtotime($u['created_at']))?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>

  <!-- System info + Quick actions -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.1rem">
    <div class="card" style="padding:1.25rem">
      <h6 style="font-weight:700;margin-bottom:.85rem">System Information</h6>
      <?php foreach([
        ['PHP Version', phpversion()],
        ['Server', $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'],
        ['App Version', APP_VERSION],
        ['Timezone', date_default_timezone_get()],
        ['Memory Limit', ini_get('memory_limit')],
        ['APP_URL', APP_URL],
      ] as [$k,$v]): ?>
      <div style="display:flex;justify-content:space-between;padding:.45rem 0;border-bottom:1px solid var(--border);font-size:.83rem">
        <span style="color:var(--muted)"><?=$k?></span>
        <span class="mono" style="font-size:.78rem;word-break:break-all;max-width:55%;text-align:right"><?=htmlspecialchars($v)?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="card" style="padding:1.25rem">
      <h6 style="font-weight:700;margin-bottom:.85rem">Quick Actions</h6>
      <div style="display:flex;flex-direction:column;gap:.6rem">
        <a href="<?=APP_URL?>/markets.php" class="btn btn-out" style="justify-content:flex-start"><i class="bi bi-bar-chart"></i> View Markets</a>
        <a href="<?=APP_URL?>/first-run.php" class="btn btn-out" style="justify-content:flex-start;color:var(--gold);border-color:rgba(245,158,11,.3)"><i class="bi bi-arrow-clockwise"></i> Re-run Setup</a>
        <a href="<?=APP_URL?>/database.sql" class="btn btn-out" style="justify-content:flex-start"><i class="bi bi-download"></i> Download Schema</a>
        <a href="<?=APP_URL?>/logout.php" class="btn btn-out" style="justify-content:flex-start;color:var(--red);border-color:rgba(239,68,68,.3)"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </div>
    </div>
  </div>

</div>
</main>
<?php pageFooter(); ?>
