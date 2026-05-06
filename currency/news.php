<?php
require_once __DIR__ . '/config.php';
startSession();
$articles = DB::all('SELECT * FROM articles WHERE is_published=1 ORDER BY created_at DESC LIMIT 20');
$cats     = ['All','Market News','Education','DeFi','Security','Regulation','Technology'];
$grads    = ['linear-gradient(135deg,#1e3a5f,#0d1f3c)','linear-gradient(135deg,#1a1a2e,#0f3460)','linear-gradient(135deg,#0f2027,#2c5364)','linear-gradient(135deg,#2d1b69,#11998e)','linear-gradient(135deg,#1a1a2e,#6a0572)','linear-gradient(135deg,#0d1220,#1a4a6b)'];
pageHead('Crypto News — CryptoNexus');
navbar();
?>
<main class="pw" style="padding-bottom:3rem">
<div style="max-width:1200px;margin:0 auto;padding:0 1.25rem">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
    <div><h4 style="font-weight:700;margin:0">Crypto News</h4><p style="color:var(--muted);font-size:.82rem;margin:0">Latest market insights & analysis</p></div>
  </div>

  <!-- Category filter -->
  <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1.5rem">
    <?php foreach($cats as $cat): ?>
    <button class="btn <?=$cat==='All'?'btn-pri':'btn-out'?> btn-sm cat-btn" data-cat="<?=$cat?>"><?=$cat?></button>
    <?php endforeach; ?>
  </div>

  <!-- Featured -->
  <?php if(!empty($articles)): $f=$articles[0]; ?>
  <div class="card" style="overflow:hidden;margin-bottom:2.5rem;display:grid;grid-template-columns:2fr 3fr">
    <div style="background:<?=$grads[0]?>;min-height:220px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center">
      <i class="bi bi-newspaper" style="font-size:5rem;opacity:.1;color:#fff"></i>
      <div style="position:absolute;bottom:1rem;left:1rem"><span class="badge badge-neu"><?=htmlspecialchars($f['category']??'News')?></span></div>
    </div>
    <div style="padding:2rem;display:flex;flex-direction:column;justify-content:center">
      <div style="font-size:.76rem;font-weight:600;text-transform:uppercase;letter-spacing:1.5px;color:var(--primary);margin-bottom:.5rem">Featured</div>
      <h3 style="font-weight:700;line-height:1.3;margin-bottom:.75rem"><?=htmlspecialchars($f['title'])?></h3>
      <p style="color:var(--muted);font-size:.875rem;line-height:1.65;margin-bottom:1rem"><?=htmlspecialchars($f['excerpt']??'')?></p>
      <div style="display:flex;align-items:center;justify-content:space-between">
        <span style="color:var(--muted);font-size:.78rem"><?=date('F j, Y',strtotime($f['created_at']))?></span>
        <button class="btn btn-pri btn-sm">Read More <i class="bi bi-arrow-right"></i></button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Articles grid -->
  <div id="grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem">
  <?php
  $placeholders=[
    ['Bitcoin ETF Approval Signals New Era for Institutional Crypto','The approval of spot Bitcoin ETFs marks a watershed moment for cryptocurrency adoption.','Market News'],
    ['Understanding DeFi: Yield Farming and Liquidity Mining','A deep dive into decentralized finance protocols that allow users to earn passive income.','DeFi'],
    ['Ethereum Gas Fees Hit All-Time Low After Upgrade','The latest hard fork introduces proto-danksharding, reducing L2 transaction costs.','Technology'],
    ['Regulatory Clarity: SEC Updates Crypto Asset Guidelines','New guidance provides clearer frameworks for cryptocurrency businesses.','Regulation'],
    ['Top Security Practices for Protecting Your Crypto Assets','Hardware wallets, multi-sig setups — a guide to keeping digital assets safe.','Security'],
  ];
  $allArticles = !empty($articles) ? array_slice($articles,1) : [];
  $showItems = !empty($allArticles) ? $allArticles : array_map(fn($p)=>['title'=>$p[0],'excerpt'=>$p[1],'category'=>$p[2],'created_at'=>date('Y-m-d'),'views'=>0],$placeholders);
  foreach($showItems as $i=>$a):
  ?>
  <div class="ncard art-card" data-cat="<?=htmlspecialchars($a['category']??'News')?>">
    <div class="ncard-img" style="background:<?=$grads[$i%count($grads)]?>">
      <div style="display:flex;align-items:center;justify-content:center;height:100%"><i class="bi bi-newspaper" style="font-size:2.8rem;opacity:.2;color:#fff"></i></div>
      <div style="position:absolute;bottom:.85rem;left:.85rem"><span class="badge" style="background:rgba(0,0,0,.5)"><?=htmlspecialchars($a['category']??'News')?></span></div>
      <div style="position:absolute;top:.85rem;right:.85rem"><span class="badge badge-neu" style="font-size:.65rem"><i class="bi bi-eye"></i> <?=number_format($a['views']??0)?></span></div>
    </div>
    <div class="ncard-body">
      <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:var(--primary);margin-bottom:.35rem"><?=htmlspecialchars($a['category']??'News')?></div>
      <h6 style="font-weight:600;line-height:1.4;margin-bottom:.6rem"><?=htmlspecialchars($a['title'])?></h6>
      <p style="color:var(--muted);font-size:.82rem;line-height:1.6;margin-bottom:.75rem"><?=htmlspecialchars(substr($a['excerpt']??'',0,110))?>…</p>
      <div style="display:flex;align-items:center;justify-content:space-between">
        <span class="badge badge-neu" style="font-size:.7rem">CryptoNexus</span>
        <span style="color:var(--muted);font-size:.72rem"><?=date('M j, Y',strtotime($a['created_at']))?></span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  </div>

  <!-- Sentiment widget -->
  <div class="card" style="padding:1.5rem;margin-top:3rem">
    <div style="display:grid;grid-template-columns:1fr 2fr;gap:2rem;align-items:center">
      <div><h5 style="font-weight:700;margin-bottom:.35rem">Market Sentiment</h5><p style="color:var(--muted);font-size:.85rem;margin:0">Fear & Greed Index</p></div>
      <div>
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.5rem;margin-bottom:.85rem;text-align:center">
          <?php $cur=67; foreach([['Extreme Fear','#ef4444',5],['Fear','#f97316',25],['Neutral','#94a3b8',50],['Greed','#84cc16',75],['Extreme Greed','#10b981',90]] as [$l,$c,$p]):
            $on=$cur>=$p-20&&$cur<$p+20; ?>
          <div style="padding:.4rem;border-radius:8px;<?=$on?"border:1px solid {$c};background:{$c}20":''?>">
            <div style="font-weight:700;font-size:.78rem;color:<?=$c?>"><?=$l?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="display:flex;justify-content:space-between;color:var(--muted);font-size:.72rem;margin-bottom:.3rem">
          <span>0 Extreme Fear</span><span style="color:var(--green);font-weight:700">Current: <?=$cur?> (Greed)</span><span>100 Extreme Greed</span>
        </div>
        <div class="prog" style="height:8px"><div class="prog-bar" style="width:<?=$cur?>%;background:linear-gradient(90deg,#ef4444,#f97316,#94a3b8,#84cc16,#10b981)"></div></div>
      </div>
    </div>
  </div>
</div>
</main>
<?php pageFooter(); ?>
<script>
document.querySelectorAll('.cat-btn').forEach(b=>b.addEventListener('click',function(){
  document.querySelectorAll('.cat-btn').forEach(x=>{x.className='btn btn-out btn-sm cat-btn';});
  this.className='btn btn-pri btn-sm cat-btn';
  const cat=this.dataset.cat;
  document.querySelectorAll('.art-card').forEach(c=>{c.style.display=(cat==='All'||c.dataset.cat===cat)?'':'none';});
}));
</script>
