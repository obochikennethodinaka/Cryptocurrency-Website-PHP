<?php
require_once __DIR__ . '/config.php';
requireLogin();
$uid=$_SESSION['user_id']; $msg=''; $err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if($_POST['action']==='add'){
    $cur=strtoupper(trim($_POST['currency']??'')); $px=(float)($_POST['price']??0); $cond=$_POST['cond']??'above';
    if($cur&&$px>0){ DB::insert('price_alerts',['user_id'=>$uid,'currency'=>$cur,'target_price'=>$px,'cond'=>$cond]); $msg="Alert set for $cur $cond \$$px"; }
  } elseif($_POST['action']==='delete'){
    DB::run('DELETE FROM price_alerts WHERE id=? AND user_id=?',[(int)$_POST['aid'],$uid]); $msg='Alert deleted.';
  }
}
$alerts=DB::all('SELECT * FROM price_alerts WHERE user_id=? ORDER BY created_at DESC',[$uid]);
$syms=array_keys(symbolToId());
pageHead('Price Alerts — CryptoNexus');
navbar();
?>
<main class="pw" style="padding-bottom:3rem">
<div style="max-width:900px;margin:0 auto;padding:0 1.25rem">
  <div style="margin-bottom:1.25rem"><h4 style="font-weight:700;margin:0">Price Alerts</h4><p style="color:var(--muted);font-size:.82rem;margin:0">Get notified when coins hit your target</p></div>
  <?php if($msg): ?><div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:10px;padding:.7rem 1rem;color:var(--green);font-size:.875rem;margin-bottom:1rem"><i class="bi bi-check-circle"></i> <?=htmlspecialchars($msg)?></div><?php endif; ?>

  <div class="card" style="padding:1.25rem;margin-bottom:1.25rem">
    <h6 style="font-weight:700;margin-bottom:.85rem"><i class="bi bi-bell-plus" style="color:var(--primary)"></i> Create Alert</h6>
    <form method="POST"><input type="hidden" name="action" value="add">
    <div style="display:grid;grid-template-columns:1fr 1fr 2fr 1fr;gap:.75rem;align-items:end">
      <div><label class="flbl">Coin</label><select class="fc" name="currency"><?php foreach($syms as $s): ?><option><?=$s?></option><?php endforeach; ?></select></div>
      <div><label class="flbl">Condition</label><select class="fc" name="cond"><option value="above">Goes above</option><option value="below">Goes below</option></select></div>
      <div><label class="flbl">Target Price (USD)</label><div class="ig ig-pre"><div class="ig-txt">$</div><input class="fc" type="number" name="price" placeholder="0.00" step="0.01" min="0.000001" required></div></div>
      <div><button type="submit" class="btn btn-pri" style="width:100%"><i class="bi bi-bell"></i> Set Alert</button></div>
    </div>
    </form>
  </div>

  <div class="card" style="overflow:hidden">
    <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center"><h6 style="font-weight:700;margin:0">Active Alerts</h6><span class="badge badge-neu"><?=count($alerts)?></span></div>
    <?php if(!$alerts): ?><div style="text-align:center;padding:3rem;color:var(--muted)"><i class="bi bi-bell-slash" style="font-size:2.5rem;display:block;opacity:.3;margin-bottom:.5rem"></i>No alerts yet</div><?php endif; ?>
    <?php foreach($alerts as $a): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;border-bottom:1px solid var(--border)">
      <div style="display:flex;align-items:center;gap:.85rem">
        <div class="sico" style="background:<?=$a['cond']==='above'?'rgba(16,185,129,.15)':'rgba(239,68,68,.15)'?>;color:<?=$a['cond']==='above'?'var(--green)':'var(--red)'?>"><i class="bi bi-bell<?=$a['is_triggered']?'-fill':''?>"></i></div>
        <div><div style="font-weight:700"><?=htmlspecialchars($a['currency'])?></div>
        <div style="font-size:.82rem;color:var(--muted)">Alert when price <?=$a['cond']==='above'?'↑ goes above':'↓ falls below'?> <span class="mono fw7" style="color:var(--text)">$<?=number_format((float)$a['target_price'],2)?></span></div></div>
      </div>
      <div style="display:flex;align-items:center;gap:.85rem">
        <span class="badge <?=$a['is_triggered']?'badge-dn':'badge-up'?>"><?=$a['is_triggered']?'Triggered':'Active'?></span>
        <span style="color:var(--muted);font-size:.78rem"><?=date('M j',strtotime($a['created_at']))?></span>
        <form method="POST" style="margin:0"><input type="hidden" name="action" value="delete"><input type="hidden" name="aid" value="<?=$a['id']?>"><button type="submit" class="btn-ghost" style="color:var(--red)"><i class="bi bi-trash"></i></button></form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</main>
<?php pageFooter(); ?>
