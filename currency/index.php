<?php
require_once __DIR__ . '/config.php';
startSession();
$topCoins   = getTopCoins(10);
$globalStats = getGlobalStats();
pageHead('CryptoNexus — Professional Crypto Trading Platform');
navbar();
?>
<main class="pw">

<!-- Hero -->
<section class="hero">
<div style="max-width:1200px;margin:0 auto;padding:0 1.5rem">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center">
<div>
  <div class="hero-badge fu"><span style="width:8px;height:8px;border-radius:50%;background:var(--green);display:inline-block" class="pulse"></span> Live · 20+ Coins · Real-time Data</div>
  <h1 class="hero-title fu d1">Trade Crypto<br><span class="gtxt">with Confidence</span></h1>
  <p class="fu d2" style="font-size:1.05rem;color:var(--muted);line-height:1.7;max-width:480px;margin:.9rem 0 1.5rem">The professional platform for buying, selling, and managing cryptocurrency. Live prices, advanced charts, secure wallets.</p>
  <div class="fu d3" style="display:flex;gap:.75rem;flex-wrap:wrap">
    <?php if (!isLoggedIn()): ?>
    <a href="<?= APP_URL ?>/register.php" class="btn btn-pri btn-lg"><i class="bi bi-rocket"></i> Get Started Free</a>
    <a href="<?= APP_URL ?>/markets.php" class="btn btn-out btn-lg">View Markets</a>
    <?php else: ?>
    <a href="<?= APP_URL ?>/dashboard.php" class="btn btn-pri btn-lg"><i class="bi bi-grid"></i> Dashboard</a>
    <a href="<?= APP_URL ?>/trade.php" class="btn btn-out btn-lg"><i class="bi bi-arrow-left-right"></i> Trade Now</a>
    <?php endif; ?>
  </div>
  <!-- Stats -->
  <div class="fu d4" style="display:flex;gap:2rem;margin-top:2.5rem;flex-wrap:wrap">
    <?php
    $mc = $globalStats['total_market_cap']['usd'] ?? 0;
    $vol = $globalStats['total_volume']['usd'] ?? 0;
    $ac  = $globalStats['active_cryptocurrencies'] ?? 0;
    foreach([['Market Cap', fmtBig($mc)],['24h Volume', fmtBig($vol)],['Active Coins', number_format($ac).'+'] ] as [$l,$v]):
    ?>
    <div><div style="font-size:1.2rem;font-weight:700;font-family:'JetBrains Mono',monospace"><?= $v ?></div><div style="font-size:.8rem;color:var(--muted)"><?= $l ?></div></div>
    <?php endforeach; ?>
  </div>
</div>
<!-- Price Cards -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
  <?php foreach(array_slice($topCoins,0,6) as $i=>$c):
    $ch = $c['price_change_percentage_24h'] ?? 0;
    $up = $ch >= 0;
    $spark = $c['sparkline_in_7d']['price'] ?? [];
  ?>
  <div class="pcard fu d<?= min($i+1,4) ?>">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem">
      <div style="display:flex;align-items:center;gap:.5rem">
        <?php if (!empty($c['image'])): ?><img src="<?= htmlspecialchars($c['image']) ?>" width="26" height="26" style="border-radius:50%"><?php endif; ?>
        <div><div style="font-weight:700;font-size:.82rem"><?= strtoupper($c['symbol']) ?></div><div style="font-size:.68rem;color:var(--muted)"><?= htmlspecialchars($c['name']) ?></div></div>
      </div>
      <span class="badge <?= $up?'badge-up':'badge-dn' ?>" style="font-size:.68rem"><?= $up?'▲':'▼' ?> <?= number_format(abs($ch),2) ?>%</span>
    </div>
    <div class="mono" style="font-size:.9rem;font-weight:700"><?= fmtPrice($c['current_price']??0) ?></div>
    <?php if($spark): ?><canvas data-prices='<?= htmlspecialchars(json_encode(array_slice($spark,-20))) ?>' data-up="<?= $up?1:0 ?>" style="width:100%;height:30px;margin-top:.4rem"></canvas><?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>
</div>
</div>
</section>

<!-- Market Table -->
<section style="padding:3rem 0">
<div style="max-width:1200px;margin:0 auto;padding:0 1.5rem">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
    <div><div class="sec-eyebrow">Live Prices</div><h2 class="sec-title">Top Cryptocurrencies</h2></div>
    <a href="<?= APP_URL ?>/markets.php" class="btn btn-out btn-sm">View All <i class="bi bi-arrow-right"></i></a>
  </div>
  <div class="card" style="overflow:hidden">
  <div style="overflow-x:auto">
  <table class="tbl">
    <thead><tr><th>#</th><th>Name</th><th style="text-align:right">Price</th><th style="text-align:right">24h %</th><th style="text-align:right">Market Cap</th></tr></thead>
    <tbody>
    <?php foreach($topCoins as $coin):
      $ch = $coin['price_change_percentage_24h'] ?? 0;
      $up = $ch >= 0;
    ?>
    <tr onclick="location.href='<?= APP_URL ?>/coin.php?id=<?= urlencode($coin['id']) ?>'">
      <td><span class="rank-badge"><?= $coin['market_cap_rank']??'—' ?></span></td>
      <td><div style="display:flex;align-items:center;gap:.6rem">
        <?php if(!empty($coin['image'])): ?><img src="<?= htmlspecialchars($coin['image']) ?>" class="coin-img" alt=""><?php endif; ?>
        <div><div style="font-weight:600"><?= htmlspecialchars($coin['name']) ?></div><div style="font-size:.75rem;color:var(--muted)"><?= strtoupper($coin['symbol']) ?></div></div>
      </div></td>
      <td style="text-align:right" class="mono fw7"><?= fmtPrice($coin['current_price']??0) ?></td>
      <td style="text-align:right"><span class="<?= $up?'up':'dn' ?>"><?= $up?'▲':'▼' ?> <?= number_format(abs($ch),2) ?>%</span></td>
      <td style="text-align:right;font-size:.85rem;color:var(--muted)"><?= fmtBig($coin['market_cap']??0) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  </div>
</div>
</section>

<!-- Features -->
<section style="padding:3rem 0">
<div style="max-width:1200px;margin:0 auto;padding:0 1.5rem">
  <div style="text-align:center;margin-bottom:3rem"><div class="sec-eyebrow">Why CryptoNexus</div><h2 class="sec-title" style="margin-top:.5rem">Built for Serious Traders</h2></div>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.25rem">
    <?php foreach([
      ['bi-lightning-charge-fill','rgba(59,130,246,.15)','var(--primary)','Real-Time Data','Live prices from global exchanges. Never miss a market move.'],
      ['bi-shield-check-fill','rgba(16,185,129,.15)','var(--green)','Bank-Grade Security','2FA, encrypted wallets, session management. Your assets are protected.'],
      ['bi-graph-up-arrow','rgba(139,92,246,.15)','var(--purple)','Advanced Analytics','Interactive charts, portfolio tracking, and market sentiment.'],
      ['bi-arrow-left-right','rgba(6,182,212,.15)','var(--cyan)','Instant Swaps','Swap between 20+ cryptocurrencies with our low-fee engine.'],
      ['bi-bell-fill','rgba(245,158,11,.15)','var(--gold)','Smart Alerts','Set custom price alerts and get notified instantly.'],
      ['bi-phone-fill','rgba(59,130,246,.15)','var(--primary)','Mobile Ready','Fully responsive. Trade from any device, anywhere.'],
    ] as [$ico,$bg,$col,$title,$desc]): ?>
    <div class="fcard fu">
      <div class="fcard-ico" style="background:<?=$bg?>;color:<?=$col?>"><i class="bi <?=$ico?>"></i></div>
      <h5 style="font-weight:700;margin-bottom:.5rem"><?=$title?></h5>
      <p style="color:var(--muted);font-size:.85rem;margin:0;line-height:1.6"><?=$desc?></p>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</section>

<!-- CTA -->
<section style="padding:3rem 0 4rem">
<div style="max-width:1200px;margin:0 auto;padding:0 1.5rem">
  <div class="card" style="padding:3.5rem;text-align:center;background:linear-gradient(135deg,rgba(59,130,246,.08),rgba(139,92,246,.08));border-color:rgba(59,130,246,.2)">
    <h2 class="sec-title">Ready to Start Trading?</h2>
    <p style="color:var(--muted);margin:.75rem 0 1.75rem">Join thousands of traders. Create your account in 60 seconds.</p>
    <?php if(!isLoggedIn()): ?>
    <a href="<?= APP_URL ?>/register.php" class="btn btn-pri btn-lg"><i class="bi bi-rocket"></i> Create Free Account</a>
    <?php else: ?>
    <a href="<?= APP_URL ?>/trade.php" class="btn btn-pri btn-lg"><i class="bi bi-arrow-left-right"></i> Start Trading Now</a>
    <?php endif; ?>
  </div>
</div>
</section>
</main>

<?php pageFooter(); ?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('canvas[data-prices]').forEach(c=>{
    try{ CN.sparkline(c,JSON.parse(c.dataset.prices),c.dataset.up==='1'); }catch(e){}
  });
});
</script>
