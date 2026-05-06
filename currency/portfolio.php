<?php
require_once __DIR__ . '/config.php';
requireLogin();
$uid     = $_SESSION['user_id'];
$wallets = DB::all('SELECT * FROM wallets WHERE user_id=?', [$uid]);
$port    = portfolioValue($wallets);
$colors  = ['#3b82f6','#10b981','#8b5cf6','#f59e0b','#06b6d4','#ef4444','#ec4899','#84cc16'];
pageHead('Portfolio — CryptoNexus');
navbar();
?>
<main class="pw" style="padding-bottom:3rem">
<div style="max-width:1200px;margin:0 auto;padding:0 1.25rem">

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
  <div><h4 style="font-weight:700;margin:0">My Portfolio</h4><p style="color:var(--muted);font-size:.82rem;margin:0">Track your crypto holdings & performance</p></div>
  <a href="<?=APP_URL?>/trade.php" class="btn btn-pri btn-sm"><i class="bi bi-plus"></i> Trade</a>
</div>

<!-- Top row -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1.25rem">
  <!-- Total value -->
  <div class="scard" style="padding:1.5rem;border-color:rgba(59,130,246,.25);box-shadow:0 0 25px rgba(59,130,246,.08)">
    <div class="slbl">Total Portfolio Value</div>
    <div style="font-size:2rem;font-weight:700;font-family:'JetBrains Mono',monospace;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">$<?=number_format($port['total'],2)?></div>
    <div style="display:flex;gap:1.5rem;margin-top:.9rem">
      <div><div style="color:var(--muted);font-size:.78rem">Assets</div><div style="font-weight:700"><?=count(array_filter($port['items'],fn($i)=>$i['balance']>0))?></div></div>
      <div><div style="color:var(--muted);font-size:.78rem">Largest</div><div style="font-weight:700"><?=!empty($port['items'])?htmlspecialchars($port['items'][0]['currency']):'—'?></div></div>
    </div>
    <a href="<?=APP_URL?>/trade.php" class="btn btn-pri btn-sm" style="margin-top:1rem;width:100%"><i class="bi bi-arrow-left-right"></i> Trade Now</a>
  </div>

  <!-- Donut -->
  <div class="card" style="padding:1.25rem">
    <h6 style="font-weight:700;margin-bottom:.85rem">Allocation</h6>
    <div style="position:relative;height:185px;display:flex;align-items:center;justify-content:center">
      <?php if(!empty($port['items'])): ?>
      <canvas id="donut"></canvas>
      <div style="position:absolute;text-align:center;pointer-events:none"><div class="mono fw7">$<?=fmtBig($port['total'])?></div><div style="font-size:.7rem;color:var(--muted)">TOTAL</div></div>
      <?php else: ?><div style="color:var(--muted);text-align:center"><i class="bi bi-pie-chart" style="font-size:2.5rem;display:block;opacity:.3"></i>No assets</div><?php endif; ?>
    </div>
  </div>

  <!-- Breakdown bars -->
  <div class="card" style="padding:1.25rem">
    <h6 style="font-weight:700;margin-bottom:.85rem">Breakdown</h6>
    <?php foreach(array_slice($port['items'],0,6) as $i=>$item): if($item['balance']<=0) continue; $pct=$port['total']>0?$item['value']/$port['total']*100:0; ?>
    <div style="margin-bottom:.85rem">
      <div style="display:flex;justify-content:space-between;font-size:.82rem;margin-bottom:.3rem">
        <div style="display:flex;align-items:center;gap:.4rem"><div style="width:9px;height:9px;border-radius:50%;background:<?=$colors[$i%count($colors)]?>;flex-shrink:0"></div><span style="font-weight:600"><?=htmlspecialchars($item['currency'])?></span></div>
        <span><span style="font-weight:600">$<?=number_format($item['value'],2)?></span> <span style="color:var(--muted)"><?=number_format($pct,1)?>%</span></span>
      </div>
      <div class="prog"><div class="prog-bar" style="width:<?=min($pct,100)?>%;background:<?=$colors[$i%count($colors)]?>"></div></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Holdings table -->
<div class="card" style="overflow:hidden">
<div style="padding:1.1rem 1.25rem;border-bottom:1px solid var(--border)"><h6 style="font-weight:700;margin:0">Holdings</h6></div>
<div style="overflow-x:auto">
<table class="tbl">
  <thead><tr><th>Asset</th><th style="text-align:right">Balance</th><th style="text-align:right">Price</th><th style="text-align:right">Value (USD)</th><th style="text-align:right">24h</th><th style="text-align:right">Allocation</th><th style="text-align:right">Action</th></tr></thead>
  <tbody>
  <?php $hasAny=false; foreach($port['items'] as $i=>$item): if($item['balance']<=0) continue; $hasAny=true;
    $pct=$port['total']>0?$item['value']/$port['total']*100:0; $ch=$item['change']??0; ?>
  <tr>
    <td><div style="display:flex;align-items:center;gap:.5rem"><div style="width:10px;height:10px;border-radius:50%;background:<?=$colors[$i%count($colors)]?>"></div><span style="font-weight:600"><?=htmlspecialchars($item['currency'])?></span></div></td>
    <td style="text-align:right" class="mono fw7"><?=number_format($item['balance'],6)?></td>
    <td style="text-align:right" class="mono"><?=fmtPrice($item['price']??0)?></td>
    <td style="text-align:right" class="mono fw7">$<?=number_format($item['value'],2)?></td>
    <td style="text-align:right"><span class="<?=$ch>=0?'up':'dn'?>"><?=$ch>=0?'▲':'▼'?> <?=number_format(abs($ch),2)?>%</span></td>
    <td style="text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:.4rem"><div class="prog" style="width:70px"><div class="prog-bar" style="width:<?=min($pct,100)?>%;background:<?=$colors[$i%count($colors)]?>"></div></div><span style="font-size:.78rem;color:var(--muted)"><?=number_format($pct,1)?>%</span></div></td>
    <td style="text-align:right"><a href="<?=APP_URL?>/trade.php" class="btn btn-out-pri btn-sm">Trade</a></td>
  </tr>
  <?php endforeach; if(!$hasAny): ?>
  <tr><td colspan="7" style="text-align:center;padding:3rem;color:var(--muted)"><i class="bi bi-wallet2" style="font-size:2.5rem;display:block;opacity:.3;margin-bottom:.5rem"></i>No holdings yet. <a href="<?=APP_URL?>/trade.php" style="color:var(--primary)">Start trading!</a></td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
</div>
</div>
</main>
<?php pageFooter(); ?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  const items=<?=json_encode(array_values(array_filter($port['items'],fn($i)=>$i['balance']>0)))?>;
  if(items.length) CN.donutChart('donut',items.map(i=>({currency:i.currency,value:i.value})));
});
</script>
