<?php
require_once __DIR__ . '/config.php';
requireLogin();
$u  = currentUser();
$uid = $_SESSION['user_id'];
$wallets = DB::all('SELECT * FROM wallets WHERE user_id=?', [$uid]);
$port    = portfolioValue($wallets);
$txs     = DB::all('SELECT * FROM transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 8', [$uid]);
pageHead('Dashboard — CryptoNexus');
navbar();
?>
<div class="pw"><div style="max-width:1400px;margin:0 auto">
<div style="display:flex">

<!-- Sidebar -->
<aside class="sidebar">
<div style="padding:.75rem .5rem .75rem">
<?php foreach([
  ['dashboard','bi-grid-fill','Dashboard'],['portfolio','bi-pie-chart-fill','Portfolio'],
  ['trade','bi-arrow-left-right','Trade'],['markets','bi-bar-chart-fill','Markets'],
  ['transactions','bi-receipt','Transactions'],['alerts','bi-bell-fill','Alerts'],
  ['profile','bi-person-fill','Profile'],
] as [$pg,$ico,$lbl]):
  $active = basename($_SERVER['PHP_SELF'],'.php') === $pg ? 'active' : '';
?>
<button class="sl <?=$active?>" onclick="location.href='<?=APP_URL?>/<?=$pg?>.php'"><i class="bi <?=$ico?>"></i> <?=$lbl?></button>
<?php endforeach; ?>
</div>
</aside>

<!-- Main -->
<main style="flex:1;padding:1.5rem;min-width:0">

<?php if (isset($_GET['welcome'])): ?>
<div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-left:3px solid var(--green);border-radius:12px;padding:.85rem 1.1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.75rem">
  <i class="bi bi-stars" style="color:var(--green);font-size:1.2rem"></i>
  <div><b>Welcome, <?= htmlspecialchars($u['full_name']?:$u['username']) ?>!</b><div style="font-size:.82rem;color:var(--muted)">Demo balances loaded. Start exploring!</div></div>
</div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem">
  <div><h4 style="font-weight:700;margin:0">Dashboard</h4><p style="color:var(--muted);font-size:.82rem;margin:0">Welcome back, <?= htmlspecialchars($u['username']) ?>!</p></div>
  <div style="display:flex;gap:.5rem"><button class="btn btn-out btn-sm" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Refresh</button><a href="<?=APP_URL?>/trade.php" class="btn btn-pri btn-sm"><i class="bi bi-plus"></i> Trade</a></div>
</div>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.85rem;margin-bottom:1.5rem">
<?php
$txCount = DB::one('SELECT COUNT(*) c FROM transactions WHERE user_id=?',[$uid])['c']??0;
$usdt = array_values(array_filter($wallets,fn($w)=>$w['currency']==='USDT'))[0]??null;
foreach([
  ['Total Portfolio','$'.number_format($port['total'],2),'bi-wallet2','rgba(59,130,246,.15)','var(--primary)'],
  ['Active Assets',count(array_filter($port['items']??[],fn($i)=>$i['balance']>0)),'bi-coin','rgba(16,185,129,.15)','var(--green)'],
  ['Total Trades',$txCount,'bi-arrow-left-right','rgba(245,158,11,.15)','var(--gold)'],
  ['USDT Balance',number_format((float)($usdt['balance']??0),2).' USDT','bi-cash-stack','rgba(6,182,212,.15)','var(--cyan)'],
] as [$lbl,$val,$ico,$bg,$col]):
?>
<div class="scard">
  <div style="display:flex;align-items:start;justify-content:space-between">
    <div><div class="slbl"><?=$lbl?></div><div class="sval"><?=$val?></div></div>
    <div class="sico" style="background:<?=$bg?>;color:<?=$col?>"><i class="bi <?=$ico?>"></i></div>
  </div>
</div>
<?php endforeach; ?>
</div>

<!-- Portfolio + Transactions -->
<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:1.25rem;margin-bottom:1.25rem">

<!-- Donut -->
<div class="card" style="padding:1.35rem">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem"><h6 style="font-weight:700;margin:0">Portfolio</h6><a href="<?=APP_URL?>/portfolio.php" class="btn btn-out btn-sm">View All</a></div>
  <?php if(!empty($port['items'])): ?>
  <div style="position:relative;height:190px;display:flex;align-items:center;justify-content:center">
    <canvas id="donut"></canvas>
    <div style="position:absolute;text-align:center;pointer-events:none">
      <div class="mono fw7" style="font-size:1.05rem">$<?=number_format($port['total'],0)?></div>
      <div style="font-size:.7rem;color:var(--muted)">TOTAL</div>
    </div>
  </div>
  <?php $colors=['#3b82f6','#10b981','#8b5cf6','#f59e0b','#06b6d4']; ?>
  <?php foreach(array_slice($port['items'],0,5) as $i=>$item): if($item['balance']<=0) continue; $pct=$port['total']>0?$item['value']/$port['total']*100:0; ?>
  <div style="display:flex;align-items:center;justify-content:space-between;padding:.45rem 0;border-bottom:1px solid var(--border)">
    <div style="display:flex;align-items:center;gap:.5rem"><div style="width:9px;height:9px;border-radius:50%;background:<?=$colors[$i]?>;flex-shrink:0"></div><span style="font-weight:600;font-size:.85rem"><?=htmlspecialchars($item['currency'])?></span></div>
    <div style="text-align:right"><div style="font-size:.83rem;font-weight:600">$<?=number_format($item['value'],2)?></div><div style="font-size:.7rem;color:var(--muted)"><?=number_format($pct,1)?>%</div></div>
  </div>
  <?php endforeach; ?>
  <?php else: ?><div style="text-align:center;padding:2rem;color:var(--muted)"><i class="bi bi-pie-chart" style="font-size:2.5rem;display:block;margin-bottom:.5rem;opacity:.3"></i>No assets yet</div><?php endif; ?>
</div>

<!-- Transactions -->
<div class="card" style="padding:1.35rem">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem"><h6 style="font-weight:700;margin:0">Recent Activity</h6><a href="<?=APP_URL?>/transactions.php" class="btn btn-out btn-sm">View All</a></div>
  <?php if ($txs): foreach($txs as $tx):
    $tmap=['buy'=>['tx-buy','bi-arrow-down-circle-fill'],'sell'=>['tx-sell','bi-arrow-up-circle-fill'],'send'=>['tx-send','bi-send-fill'],'receive'=>['tx-rcv','bi-inbox-fill'],'deposit'=>['tx-rcv','bi-download'],'withdraw'=>['tx-send','bi-upload']];
    [$cls,$ico]=$tmap[$tx['type']]??['tx-buy','bi-circle'];
    $isIn=in_array($tx['type'],['buy','receive','deposit']);
  ?>
  <div class="tx-row">
    <div class="tx-ico <?=$cls?>"><i class="bi <?=$ico?>"></i></div>
    <div style="flex:1;min-width:0">
      <div style="display:flex;justify-content:space-between">
        <span style="font-weight:600;font-size:.85rem"><?=ucfirst($tx['type'])?> <?=htmlspecialchars($tx['currency_to']??$tx['currency_from']??'')?></span>
        <span class="mono <?=$isIn?'up':'dn'?>" style="font-size:.83rem"><?=$isIn?'+':'-'?><?=number_format((float)$tx['amount'],6)?></span>
      </div>
      <div style="display:flex;justify-content:space-between">
        <span style="font-size:.73rem;color:var(--muted)"><?=date('M j, H:i',strtotime($tx['created_at']))?></span>
        <?php if($tx['price_usd']): ?><span style="font-size:.73rem;color:var(--muted)">@ $<?=number_format((float)$tx['price_usd'],2)?></span><?php endif; ?>
      </div>
    </div>
    <span class="badge badge-up" style="font-size:.68rem"><?=htmlspecialchars($tx['status'])?></span>
  </div>
  <?php endforeach; else: ?>
  <div style="text-align:center;padding:2rem;color:var(--muted)"><i class="bi bi-receipt" style="font-size:2.5rem;display:block;opacity:.3;margin-bottom:.5rem"></i>No transactions yet</div>
  <?php endif; ?>
</div>
</div>

<!-- Wallets grid -->
<div class="card" style="padding:1.35rem">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem"><h6 style="font-weight:700;margin:0">My Wallets</h6><a href="<?=APP_URL?>/trade.php" class="btn btn-pri btn-sm"><i class="bi bi-plus"></i> Trade</a></div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.75rem">
  <?php foreach($port['items'] as $item): if($item['balance']<=0) continue; $ch=$item['change']??0; ?>
  <div class="scard" style="padding:1rem">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4rem">
      <span style="font-weight:700;font-size:.85rem"><?=htmlspecialchars($item['currency'])?></span>
      <span style="font-size:.72rem" class="<?=$ch>=0?'up':'dn'?>"><?=$ch>=0?'▲':'▼'?> <?=number_format(abs($ch),2)?>%</span>
    </div>
    <div class="mono fw7" style="font-size:.95rem"><?=number_format($item['balance'],6)?></div>
    <div style="font-size:.78rem;color:var(--muted)">$<?=number_format($item['value'],2)?></div>
  </div>
  <?php endforeach; ?>
  </div>
</div>

</main>
</div>
</div>
</div>
<?php pageFooter(); ?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const items=<?=json_encode(array_values(array_filter($port['items'],fn($i)=>$i['balance']>0)))?>;
  if(items.length) CN.donutChart('donut',items.map(i=>({currency:i.currency,value:i.value})));
});
</script>
