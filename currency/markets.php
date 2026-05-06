<?php
require_once __DIR__ . '/config.php';
startSession();
$coins = getTopCoins(50);
$gs    = getGlobalStats();
pageHead('Live Markets — CryptoNexus');
navbar();
?>
<main class="pw" style="padding-bottom:3rem">
<div style="max-width:1300px;margin:0 auto;padding:0 1.25rem">

<!-- Global stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.85rem;margin-bottom:1.5rem">
<?php
$mc=$gs['total_market_cap']['usd']??0; $vol=$gs['total_volume']['usd']??0;
$btcd=$gs['market_cap_percentage']['btc']??0; $ethd=$gs['market_cap_percentage']['eth']??0;
$mchg=$gs['market_cap_change_percentage_24h_usd']??0;
foreach([
  ['Market Cap',fmtBig($mc),($mchg>=0?'▲':'▼').' '.number_format(abs($mchg),2).'% 24h','var(--primary)'],
  ['24h Volume',fmtBig($vol),'Global trading','var(--green)'],
  ['BTC Dominance',number_format($btcd,1).'%','Bitcoin share','var(--gold)'],
  ['ETH Dominance',number_format($ethd,1).'%','Ethereum share','var(--purple)'],
] as [$l,$v,$sub,$col]):
?>
<div class="scard"><div class="slbl"><?=$l?></div><div class="sval mono" style="font-size:1.2rem"><?=$v?></div><div style="font-size:.76rem;color:var(--muted);margin-top:.25rem"><?=$sub?></div></div>
<?php endforeach; ?>
</div>

<!-- Controls -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.9rem;flex-wrap:wrap;gap:.75rem">
  <div><h5 style="font-weight:700;margin:0">All Markets</h5><p style="color:var(--muted);font-size:.8rem;margin:0">Top <?=count($coins)?> by market cap</p></div>
  <div style="display:flex;gap:.5rem;align-items:center">
    <div style="position:relative"><i class="bi bi-search" style="position:absolute;left:.7rem;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.82rem"></i>
      <input class="fc" id="filter" placeholder="Filter coins…" style="padding-left:2.1rem;width:180px">
    </div>
    <select class="fc" id="sortBy" style="width:auto">
      <option value="rank">Rank</option><option value="price">Price</option>
      <option value="change">24h Change</option><option value="mcap">Market Cap</option>
    </select>
  </div>
</div>

<div class="card" style="overflow:hidden">
<div style="overflow-x:auto">
<table class="tbl" id="mkt">
  <thead><tr><th>#</th><th>Name</th><th style="text-align:right">Price</th><th style="text-align:right">1h</th><th style="text-align:right">24h</th><th style="text-align:right">7d</th><th style="text-align:right">Mkt Cap</th><th style="text-align:right">Volume</th><th style="text-align:right">Action</th></tr></thead>
  <tbody>
  <?php foreach($coins as $c):
    $c1=$c['price_change_percentage_1h_in_currency']??null;
    $c24=$c['price_change_percentage_24h']??0;
    $c7=$c['price_change_percentage_7d_in_currency']??null;
    $sym=strtoupper($c['symbol']);
  ?>
  <tr class="mr" data-rank="<?=$c['market_cap_rank']??0?>" data-name="<?=strtolower($c['name'].' '.$sym)?>" data-price="<?=$c['current_price']??0?>" data-change="<?=$c24?>" data-mcap="<?=$c['market_cap']??0?>"
      onclick="location.href='<?=APP_URL?>/coin.php?id=<?=urlencode($c['id'])?>'" style="cursor:pointer">
    <td><span class="rank-badge"><?=$c['market_cap_rank']??'—'?></span></td>
    <td><div style="display:flex;align-items:center;gap:.55rem">
      <?php if(!empty($c['image'])): ?><img src="<?=htmlspecialchars($c['image'])?>" class="coin-img" alt=""><?php endif; ?>
      <div><div style="font-weight:600"><?=htmlspecialchars($c['name'])?></div><div style="font-size:.75rem;color:var(--muted)"><?=$sym?></div></div>
    </div></td>
    <td style="text-align:right" class="mono fw7"><?=fmtPrice($c['current_price']??0)?></td>
    <td style="text-align:right"><?php if($c1!==null): ?><span class="<?=$c1>=0?'up':'dn'?>"><?=$c1>=0?'▲':'▼'?> <?=number_format(abs($c1),2)?>%</span><?php else: echo '<span style="color:var(--muted)">—</span>'; endif; ?></td>
    <td style="text-align:right"><span class="<?=$c24>=0?'up':'dn'?>"><?=$c24>=0?'▲':'▼'?> <?=number_format(abs($c24),2)?>%</span></td>
    <td style="text-align:right"><?php if($c7!==null): ?><span class="<?=$c7>=0?'up':'dn'?>"><?=$c7>=0?'▲':'▼'?> <?=number_format(abs($c7),2)?>%</span><?php else: echo '<span style="color:var(--muted)">—</span>'; endif; ?></td>
    <td style="text-align:right;font-size:.83rem;color:var(--muted)"><?=fmtBig($c['market_cap']??0)?></td>
    <td style="text-align:right;font-size:.83rem;color:var(--muted)"><?=fmtBig($c['total_volume']??0)?></td>
    <td style="text-align:right" onclick="event.stopPropagation()"><a href="<?=APP_URL?>/trade.php?coin=<?=urlencode($c['id'])?>" class="btn btn-out-pri btn-sm">Trade</a></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</div>
<p style="color:var(--muted);font-size:.78rem;text-align:right;margin-top:.5rem"><i class="bi bi-clock"></i> Prices cached 60s · CoinGecko API</p>
</div>
</main>
<?php pageFooter(); ?>
<script>
const rows=document.querySelectorAll('.mr');
document.getElementById('filter').addEventListener('input',function(){
  const q=this.value.toLowerCase();
  rows.forEach(r=>r.style.display=r.dataset.name.includes(q)?'':'none');
});
document.getElementById('sortBy').addEventListener('change',function(){
  const k=this.value,tbody=document.querySelector('#mkt tbody');
  [...rows].sort((a,b)=>{
    if(k==='rank')return+a.dataset.rank-+b.dataset.rank;
    if(k==='price')return+b.dataset.price-+a.dataset.price;
    if(k==='change')return+b.dataset.change-+a.dataset.change;
    return+b.dataset.mcap-+a.dataset.mcap;
  }).forEach(r=>tbody.appendChild(r));
});
</script>
