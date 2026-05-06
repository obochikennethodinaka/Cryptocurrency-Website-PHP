<?php
require_once __DIR__ . '/config.php';
requireLogin();
$u=$_SESSION['user_id']; $msg=''; $err='';
$user=currentUser();

if($_SERVER['REQUEST_METHOD']==='POST'){
  $act=$_POST['action']??'';
  if($act==='profile'){
    $fn=trim(htmlspecialchars($_POST['full_name']??''));
    DB::update('users',['full_name'=>$fn],'id=?',[$u]);
    $msg='Profile updated.'; $user=currentUser();
  } elseif($act==='password'){
    $cur=$_POST['cur_pw']??''; $new=$_POST['new_pw']??''; $cnf=$_POST['cnf_pw']??'';
    $dbUser=DB::one('SELECT password_hash FROM users WHERE id=?',[$u]);
    if(!password_verify($cur,$dbUser['password_hash']??'')) $err='Current password incorrect.';
    elseif($new!==$cnf) $err='New passwords do not match.';
    elseif(strlen($new)<8) $err='Password must be 8+ characters.';
    else{ DB::update('users',['password_hash'=>password_hash($new,PASSWORD_BCRYPT,['cost'=>10])],'id=?',[$u]); $msg='Password updated.'; }
  }
}
pageHead('Profile & Settings — CryptoNexus');
navbar();
?>
<main class="pw" style="padding-bottom:3rem">
<div style="max-width:760px;margin:0 auto;padding:0 1.25rem">
  <div style="margin-bottom:1.25rem"><h4 style="font-weight:700;margin:0">Profile & Settings</h4><p style="color:var(--muted);font-size:.82rem;margin:0">Manage your account</p></div>
  <?php if($msg): ?><div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:10px;padding:.7rem 1rem;color:var(--green);font-size:.875rem;margin-bottom:1rem"><i class="bi bi-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif; ?>
  <?php if($err): ?><div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:.7rem 1rem;color:var(--red);font-size:.875rem;margin-bottom:1rem"><i class="bi bi-exclamation-circle"></i> <?=htmlspecialchars($err)?></div><?php endif; ?>

  <div class="card" style="padding:1.5rem;margin-bottom:1.1rem">
    <div style="display:flex;align-items:center;gap:1.1rem;margin-bottom:1.5rem">
      <div class="avatar av-xl"><?=strtoupper(substr($user['username'],0,1))?></div>
      <div>
        <h5 style="font-weight:700;margin:0"><?=htmlspecialchars($user['full_name']?:$user['username'])?></h5>
        <div style="color:var(--muted);font-size:.85rem"><?=htmlspecialchars($user['email'])?></div>
        <div style="display:flex;gap:.4rem;margin-top:.5rem">
          <?php if($user['is_verified']): ?><span class="badge badge-up"><i class="bi bi-patch-check"></i> Verified</span><?php endif; ?>
          <?php if($user['is_admin']): ?><span class="badge badge-warn"><i class="bi bi-shield-check"></i> Admin</span><?php endif; ?>
          <span class="badge badge-neu">Since <?=date('M Y',strtotime($user['created_at']))?></span>
        </div>
      </div>
    </div>
    <form method="POST"><input type="hidden" name="action" value="profile">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.85rem;margin-bottom:.85rem">
        <div><label class="flbl">Full Name</label><input class="fc" type="text" name="full_name" value="<?=htmlspecialchars($user['full_name']??'')?>"></div>
        <div><label class="flbl">Username</label><input class="fc" type="text" value="<?=htmlspecialchars($user['username'])?>" disabled style="opacity:.6"></div>
      </div>
      <div style="margin-bottom:.85rem"><label class="flbl">Email</label><input class="fc" type="email" value="<?=htmlspecialchars($user['email'])?>" disabled style="opacity:.6"></div>
      <button type="submit" class="btn btn-pri"><i class="bi bi-check"></i> Save Changes</button>
    </form>
  </div>

  <div class="card" style="padding:1.5rem;margin-bottom:1.1rem">
    <h6 style="font-weight:700;margin-bottom:1rem"><i class="bi bi-lock" style="color:var(--primary)"></i> Change Password</h6>
    <form method="POST"><input type="hidden" name="action" value="password">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.75rem;margin-bottom:.85rem">
        <div><label class="flbl">Current Password</label><input class="fc" type="password" name="cur_pw" required></div>
        <div><label class="flbl">New Password</label><input class="fc" type="password" name="new_pw" required minlength="8"></div>
        <div><label class="flbl">Confirm</label><input class="fc" type="password" name="cnf_pw" required></div>
      </div>
      <button type="submit" class="btn btn-out-pri"><i class="bi bi-lock"></i> Update Password</button>
    </form>
  </div>

  <div class="card" style="padding:1.5rem">
    <h6 style="font-weight:700;margin-bottom:.85rem"><i class="bi bi-sliders" style="color:var(--primary)"></i> Preferences</h6>
    <?php foreach([['Email Notifications','Trade confirmations and price alerts'],['Price Alerts','Notify when coins hit target'],['Dark Mode','Currently using dark theme',true]] as $item): $l = $item[0]; $d = $item[1]; $dis = $item[2] ?? false; ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem 0;border-bottom:1px solid var(--border)">
      <div><div style="font-weight:600;font-size:.875rem"><?=$l?></div><div style="color:var(--muted);font-size:.8rem"><?=$d?></div></div>
      <label style="position:relative;display:inline-block;width:42px;height:22px;cursor:pointer"><input type="checkbox" checked <?=$dis?'disabled':''?> style="opacity:0;width:0;height:0"><span style="position:absolute;inset:0;background:var(--primary);border-radius:100px;transition:.3s;<?=$dis?'opacity:.5':''?>"><span style="position:absolute;width:16px;height:16px;background:#fff;border-radius:50%;top:3px;left:23px"></span></span></label>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</main>
<?php pageFooter(); ?>