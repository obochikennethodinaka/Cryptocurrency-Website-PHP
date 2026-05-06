<?php
// coin.php
require_once __DIR__ . '/config.php';
startSession();
$id   = $_GET['id'] ?? 'bitcoin';
$coin = getCoinDetail($id);
if (!$coin) { header('Location: ' . APP_URL . '/markets.php'); exit; }
$md  = $coin['market_data'] ?? [];
$name = $coin['name'] ?? '';
$sym  = strtoupper($coin['symbol'] ?? '');
$px   = $md['current_price']['usd'] ?? 0;
$c24  = $md['price_change_percentage_24h'] ?? 0;
$c7   = $md['price_change_percentage_7d'] ?? 0;
$c30  = $md['price_change_percentage_30d'] ?? 0;
$mcap = $md['market_cap']['usd'] ?? 0;
$vol  = $md['total_volume']['usd'] ?? 0;
$ath  = $md['ath']['usd'] ?? 0;
$atl  = $md['atl']['usd'] ?? 0;
$sup  = $md['circulating_supply'] ?? 0;
$maxs = $md['max_supply'] ?? 0;
$rank = $coin['market_cap_rank'] ?? 0;
$img  = $coin['image']['large'] ?? '';
$desc = strip_tags($coin['description']['en'] ?? '');

// Chart history AJAX
if (isset($_GET['history'])) {
    header('Content-Type: application/json');
    echo json_encode(getCoinHistory($id, (int)($_GET['days']??7))); exit;
}
pageHead("$name ($sym) — CryptoNexus");
navbar();
?>
<main class="pw" style="padding-bottom:3rem">
<div style="max-width:1200px;margin:0 auto;padding:0 1.25rem">
  <nav style="font-size:.82rem;margin-bottom:1.25rem"><a href="<?=APP_URL?>/markets.php" style="color:var(--muted);text-decoration:none">Markets</a> <span style="color:var(--muted)"> / </span> <span style="color:var(--text)"><?=htmlspecialchars($name)?></span></nav>

  <div style="display:grid;grid-template-columns:1.2fr 1fr;gap:1.25rem;margin-bottom:1.25rem">
    <div class="card" style="padding:1.5rem">
      <div style="display:flex;align-items:start;gap:.85rem;margin-bottom:1.25rem">
        <?php if($img): ?><img src="<?=htmlspecialchars($img)?>" width="52" height="52" style="border-radius:50%"><?php endif; ?>
        <div>
          <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap"><h3 style="font-weight:700;margin:0"><?=htmlspecialchars($name)?></h3><span class="badge badge-neu"><?=$sym?></span><?php if($rank): ?><span class="badge badge-warn">#<?=$rank?></span><?php endif; ?></div>
          <div style="display:flex;align-items:baseline;gap:.85rem;margin-top:.4rem"><span style="font-size:2rem;font-weight:700;font-family:'JetBrains Mono',monospace"><?=fmtPrice($px)?></span><span class="badge <?=$c24>=0?'badge-up':'badge-dn'?>"><?=$c24>=0?'▲':'▼'?> <?=number_format(abs($c24),2)?>%</span></div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem;text-align:center">
        <?php foreach([['1h',$md['price_change_percentage_1h_in_currency']['usd']??null],['24h',$c24],['7d',$c7],['30d',$c30]] as [$p,$v]): ?>
        <div><div style="font-size:.76rem;color:var(--muted);margin-bottom:.25rem"><?=$p?></div>
          <?php if($v!==null): ?><div style="font-weight:700" class="<?=$v>=0?'up':'dn'?>"><?=$v>=0?'▲':'▼'?> <?=number_format(abs($v),2)?>%</div>
          <?php else: ?><div style="color:var(--muted)">—</div><?php endif; ?></div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card" style="padding:1.5rem">
      <h6 style="font-weight:700;margin-bottom:.85rem">Market Stats</h6>
      <?php foreach([['Market Cap',fmtBig($mcap)],['24h Volume',fmtBig($vol)],['All-Time High',fmtPrice($ath)],['All-Time Low',fmtPrice($atl)],['Circulating Supply',$sup?number_format($sup/1e6,2).'M '.$sym:'—'],['Max Supply',$maxs?number_format($maxs/1e6,2).'M '.$sym:'∞']] as [$l,$v]): ?>
      <div style="display:flex;justify-content:space-between;padding:.45rem 0;border-bottom:1px solid var(--border);font-size:.85rem"><span style="color:var(--muted)"><?=$l?></span><span class="mono fw7"><?=$v?></span></div>
      <?php endforeach; ?>
      <?php if(isLoggedIn()): ?>
      <a href="<?=APP_URL?>/trade.php?coin=<?=urlencode($id)?>" class="btn btn-pri" style="width:100%;margin-top:1rem"><i class="bi bi-arrow-left-right"></i> Trade <?=$sym?></a>
      <?php endif; ?>
    </div>
  </div>

  <div class="card" style="padding:1.25rem;margin-bottom:1.25rem">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.85rem">
      <h6 style="font-weight:700;margin:0"><?=htmlspecialchars($name)?> Price Chart</h6>
      <div style="display:flex;gap:.25rem">
        <?php foreach(['1'=>'1D','7'=>'7D','30'=>'1M','90'=>'3M','365'=>'1Y'] as $d=>$l): ?>
        <button class="btn btn-out btn-sm tf" data-d="<?=$d?>" style="<?=$d==='7'?'border-color:var(--primary);color:var(--primary)':''?>"><?=$l?></button>
        <?php endforeach; ?>
      </div>
    </div>
    <div style="position:relative;height:320px">
      <div id="chartLoad" style="display:flex;align-items:center;justify-content:center;height:100%"><div style="width:36px;height:36px;border:3px solid var(--border);border-top-color:var(--primary);border-radius:50%;animation:spin .8s linear infinite"></div></div>
      <canvas id="pChart" style="display:none"></canvas>
    </div>
  </div>

  <?php if($desc): ?>
  <div class="card" style="padding:1.25rem">
    <h6 style="font-weight:700;margin-bottom:.75rem">About <?=htmlspecialchars($name)?></h6>
    <p id="shortDesc" style="color:var(--muted);font-size:.875rem;line-height:1.7;margin:0"><?=htmlspecialchars(substr($desc,0,600))?>…</p>
    <p id="fullDesc" style="display:none;color:var(--muted);font-size:.875rem;line-height:1.7;margin:0"><?=htmlspecialchars($desc)?></p>
    <button onclick="document.getElementById('shortDesc').style.display='none';document.getElementById('fullDesc').style.display='';this.style.display='none'" class="btn btn-out btn-sm" style="margin-top:.75rem">Read more</button>
  </div>
  <?php endif; ?>
</div>
</main>
<?php pageFooter(); ?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
<script>
const coinId='<?=htmlspecialchars($id)?>';let chart=null;
async function loadChart(days){
  document.getElementById('chartLoad').style.display='flex';document.getElementById('pChart').style.display='none';
  if(chart){chart.destroy();chart=null;}
  try{
    const d=await fetch(`<?=APP_URL?>/coin.php?id=${coinId}&history=1&days=${days}`).then(r=>r.json());
    if(!d.prices?.length)throw 0;
    const labels=d.prices.map(p=>{const dt=new Date(p[0]);return+days<=1?dt.toLocaleTimeString('en',{hour:'2-digit',minute:'2-digit'}):dt.toLocaleDateString('en',{month:'short',day:'numeric'});});
    document.getElementById('chartLoad').style.display='none';document.getElementById('pChart').style.display='';
    chart=CN.priceChart('pChart',labels,d.prices.map(p=>p[1]),'<?=$sym?>');
  }catch{document.getElementById('chartLoad').innerHTML='<p style="color:var(--muted)">Chart unavailable</p>';}
}
document.querySelectorAll('.tf').forEach(b=>b.addEventListener('click',function(){
  document.querySelectorAll('.tf').forEach(x=>{x.style.borderColor='';x.style.color='';});
  this.style.borderColor='var(--primary)';this.style.color='var(--primary)';loadChart(this.dataset.d);
}));
document.addEventListener('DOMContentLoaded',()=>loadChart(7));
</script>
