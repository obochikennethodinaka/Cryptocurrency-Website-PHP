<?php
require_once __DIR__ . '/config.php';
requireLogin();
$uid      = $_SESSION['user_id'];
$wallets  = DB::all('SELECT * FROM wallets WHERE user_id=?', [$uid]);
$wmap     = array_column($wallets, 'balance', 'currency');
$defCoin  = $_GET['coin'] ?? 'bitcoin';
$topCoins = getTopCoins(20);

// Handle trade POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_trade'])) {
    header('Content-Type: application/json');
    $action  = $_POST['action'] ?? '';
    $cur     = strtoupper($_POST['currency'] ?? '');
    $amtUsd  = (float)($_POST['amount_usd'] ?? 0);
    $priceUsd = (float)($_POST['price_usd'] ?? 0);

    if (!$cur || $amtUsd < 1 || $priceUsd <= 0) { echo json_encode(['ok'=>false,'msg'=>'Invalid parameters.']); exit; }

    $coinAmt = $amtUsd / $priceUsd;
    $fee     = $amtUsd * 0.001;
    $db      = DB::get(); $db->beginTransaction();
    try {
        if ($action === 'buy') {
            $uw = DB::one('SELECT * FROM wallets WHERE user_id=? AND currency=?', [$uid,'USDT']);
            if (!$uw || (float)$uw['balance'] < $amtUsd + $fee) throw new Exception('Insufficient USDT balance.');
            DB::update('wallets',['balance'=>(float)$uw['balance']-$amtUsd-$fee],'user_id=? AND currency=?',[$uid,'USDT']);
            $cw = DB::one('SELECT * FROM wallets WHERE user_id=? AND currency=?',[$uid,$cur]);
            if ($cw) DB::update('wallets',['balance'=>(float)$cw['balance']+$coinAmt],'user_id=? AND currency=?',[$uid,$cur]);
            else DB::insert('wallets',['user_id'=>$uid,'currency'=>$cur,'balance'=>$coinAmt]);
        } else {
            $cw = DB::one('SELECT * FROM wallets WHERE user_id=? AND currency=?',[$uid,$cur]);
            if (!$cw || (float)$cw['balance'] < $coinAmt) throw new Exception('Insufficient '.$cur.' balance.');
            DB::update('wallets',['balance'=>(float)$cw['balance']-$coinAmt],'user_id=? AND currency=?',[$uid,$cur]);
            $uw = DB::one('SELECT * FROM wallets WHERE user_id=? AND currency=?',[$uid,'USDT']);
            $earned = $amtUsd - $fee;
            if ($uw) DB::update('wallets',['balance'=>(float)$uw['balance']+$earned],'user_id=? AND currency=?',[$uid,'USDT']);
            else DB::insert('wallets',['user_id'=>$uid,'currency'=>'USDT','balance'=>$earned]);
        }
        DB::insert('transactions',['user_id'=>$uid,'tx_hash'=>'SIM-'.strtoupper(bin2hex(random_bytes(6))),'type'=>$action,'currency_from'=>$action==='buy'?'USDT':$cur,'currency_to'=>$action==='buy'?$cur:'USDT','amount'=>$coinAmt,'price_usd'=>$priceUsd,'fee'=>$fee,'status'=>'completed']);
        $db->commit();
        echo json_encode(['ok'=>true,'msg'=>sprintf('%s filled: %.6f %s @ $%s', ucfirst($action), $coinAmt, $cur, number_format($priceUsd,2)), 'coinAmt'=>$coinAmt,'fee'=>$fee]);
    } catch (Exception $e) { $db->rollBack(); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]); }
    exit;
}

// Chart history AJAX
if (isset($_GET['history'])) {
    header('Content-Type: application/json');
    $data = getCoinHistory($_GET['id']??'bitcoin', (int)($_GET['days']??7));
    echo json_encode($data); exit;
}

pageHead('Trade — CryptoNexus');
navbar();
?>
<main class="pw" style="padding-bottom:3rem">
<div style="max-width:1400px;margin:0 auto;padding:0 1.25rem">
<div style="display:grid;grid-template-columns:1fr 360px;gap:1.25rem;align-items:start">

<!-- Left: chart area -->
<div>
<!-- Coin selector bar -->
<div class="card" style="padding:1rem;margin-bottom:1rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem">
  <div style="display:flex;align-items:center;gap:.85rem">
    <img id="cImg" src="" width="42" height="42" style="border-radius:50%;display:none">
    <div><div style="display:flex;align-items:center;gap:.5rem"><span id="cName" style="font-size:1.1rem;font-weight:700">Loading…</span><span id="cSym" class="badge badge-neu">—</span></div>
      <div style="display:flex;align-items:center;gap:.75rem;margin-top:.2rem"><span id="cPx" class="mono fw7" style="font-size:1.35rem">$—</span><span id="cChg" class="badge">—</span></div>
    </div>
  </div>
  <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
    <select class="fc" id="coinSel" style="width:auto">
      <?php foreach($topCoins as $c): ?>
      <option value="<?=htmlspecialchars($c['id'])?>" data-price="<?=$c['current_price']??0?>" data-sym="<?=strtoupper(htmlspecialchars($c['symbol']))?>" data-chg="<?=$c['price_change_percentage_24h']??0?>" data-img="<?=htmlspecialchars($c['image']??'')?>" data-name="<?=htmlspecialchars($c['name'])?>" <?=$c['id']===$defCoin?'selected':''?>><?=htmlspecialchars($c['name'])?> (<?=strtoupper($c['symbol'])?>)</option>
      <?php endforeach; ?>
    </select>
    <div style="display:flex;gap:.25rem">
      <?php foreach(['1'=>'1D','7'=>'7D','30'=>'1M','90'=>'3M','365'=>'1Y'] as $d=>$l): ?>
      <button class="btn btn-out btn-sm tf" data-d="<?=$d?>" style="<?=$d==='7'?'border-color:var(--primary);color:var(--primary)':''?>"><?=$l?></button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Chart -->
<div class="card" style="padding:1.25rem;margin-bottom:1rem">
  <div style="position:relative;height:320px">
    <div id="chartLoad" style="display:flex;align-items:center;justify-content:center;height:100%">
      <div style="width:36px;height:36px;border:3px solid var(--border);border-top-color:var(--primary);border-radius:50%;animation:spin .8s linear infinite"></div>
    </div>
    <canvas id="priceChart" style="display:none"></canvas>
  </div>
</div>

<!-- Order Book -->
<div class="card" style="padding:1.25rem">
  <h6 style="font-weight:700;margin-bottom:.85rem">Order Book</h6>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
    <div><div style="display:flex;justify-content:space-between;font-size:.7rem;color:var(--muted);margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.5px"><span>Price</span><span>Amt</span><span>Total</span></div><div id="asks"></div></div>
    <div><div style="display:flex;justify-content:space-between;font-size:.7rem;color:var(--muted);margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.5px"><span>Price</span><span>Amt</span><span>Total</span></div><div id="bids"></div></div>
  </div>
</div>
</div>

<!-- Right: Trade Panel -->
<div>
<div class="trade-panel" style="padding:1.25rem;margin-bottom:1rem">
  <div style="display:flex;gap:.25rem;background:var(--bg3);padding:.25rem;border-radius:10px;margin-bottom:1.1rem">
    <button class="ttab buy-on" id="buyTab"><i class="bi bi-arrow-down-circle"></i> Buy</button>
    <button class="ttab" id="sellTab"><i class="bi bi-arrow-up-circle"></i> Sell</button>
  </div>
  <div style="display:flex;justify-content:space-between;margin-bottom:1rem;font-size:.82rem">
    <div><div style="color:var(--muted)">USDT Balance</div><div class="mono fw7"><?=number_format((float)($wmap['USDT']??0),2)?> USDT</div></div>
    <div style="text-align:right"><div style="color:var(--muted)"><span id="tSym">BTC</span> Balance</div><div class="mono fw7" id="coinBal">0.000000</div></div>
  </div>
  <div style="margin-bottom:.85rem">
    <label class="flbl">Amount (USD)</label>
    <div class="ig ig-pre"><div class="ig-txt">$</div><input class="fc" type="number" id="tAmt" placeholder="0.00" min="1" step="0.01"></div>
    <div style="display:flex;gap:.35rem;margin-top:.5rem">
      <?php foreach([25,50,100,250] as $a): ?><button class="btn btn-out btn-sm" style="flex:1" onclick="document.getElementById('tAmt').value=<?=$a?>;calcEst()">$<?=$a?></button><?php endforeach; ?>
    </div>
  </div>
  <div style="background:var(--bg3);border-radius:10px;padding:.85rem;margin-bottom:1rem;font-size:.83rem">
    <div style="display:flex;justify-content:space-between;margin-bottom:.4rem"><span style="color:var(--muted)">You'll receive</span><span class="mono fw7" id="est">—</span></div>
    <div style="display:flex;justify-content:space-between;margin-bottom:.4rem"><span style="color:var(--muted)">Price</span><span class="mono" id="cxPx">$—</span></div>
    <div style="display:flex;justify-content:space-between"><span style="color:var(--muted)">Fee (0.1%)</span><span class="mono" id="feeD">$0.00</span></div>
  </div>
  <button class="btn btn-buy" id="tradeBtn" style="width:100%"><i class="bi bi-arrow-down-circle"></i> Buy BTC</button>
</div>

<!-- Holdings -->
<div class="card" style="padding:1.25rem">
  <h6 style="font-weight:700;margin-bottom:.85rem">My Holdings</h6>
  <?php foreach($wallets as $w): if((float)$w['balance']<=0) continue; ?>
  <div style="display:flex;justify-content:space-between;padding:.45rem 0;border-bottom:1px solid var(--border);font-size:.85rem">
    <span style="color:var(--muted)"><?=htmlspecialchars($w['currency'])?></span>
    <span class="mono fw7"><?=number_format((float)$w['balance'],6)?></span>
  </div>
  <?php endforeach; ?>
</div>
</div>

</div>
</div>
</main>

<?php pageFooter(); ?>
<style>@keyframes spin{to{transform:rotate(360deg)}}</style>
<script>
const wmap=<?=json_encode($wmap)?>;
const base='<?=APP_URL?>';
let coinId='<?=htmlspecialchars($defCoin)?>',sym='BTC',price=0,buying=true,chart=null;
const $=id=>document.getElementById(id);

function uiCoin(opt){
  price=parseFloat(opt.dataset.price)||0; sym=opt.dataset.sym||'BTC'; coinId=opt.value;
  $('cName').textContent=opt.dataset.name; $('cSym').textContent=sym;
  $('cPx').textContent=CN.fUSD(price); $('tSym').textContent=sym;
  const ch=parseFloat(opt.dataset.chg)||0;
  $('cChg').textContent=(ch>=0?'▲':'▼')+' '+Math.abs(ch).toFixed(2)+'%';
  $('cChg').className='badge '+(ch>=0?'badge-up':'badge-dn');
  const img=$('cImg'); if(opt.dataset.img){img.src=opt.dataset.img;img.style.display='';}else{img.style.display='none';}
  $('coinBal').textContent=parseFloat(wmap[sym]||0).toFixed(6)+' '+sym;
  $('cxPx').textContent=CN.fUSD(price);
  updateBtn(); calcEst(); loadChart(coinId); orderBook(price);
}
$('coinSel').addEventListener('change',function(){uiCoin(this.options[this.selectedIndex]);});

$('buyTab').addEventListener('click',()=>{buying=true;$('buyTab').className='ttab buy-on';$('sellTab').className='ttab';updateBtn();});
$('sellTab').addEventListener('click',()=>{buying=false;$('sellTab').className='ttab sell-on';$('buyTab').className='ttab';updateBtn();});
function updateBtn(){$('tradeBtn').className='btn '+(buying?'btn-buy':'btn-sell')+' '+'';$('tradeBtn').style.width='100%';$('tradeBtn').innerHTML=`<i class="bi bi-arrow-${buying?'down':'up'}-circle"></i> ${buying?'Buy':'Sell'} ${sym}`;}
$('tAmt').addEventListener('input',calcEst);
function calcEst(){const a=parseFloat($('tAmt').value)||0;const f=a*.001;const c=price>0?(a-f)/price:0;$('est').textContent=c.toFixed(6)+' '+sym;$('feeD').textContent='$'+f.toFixed(4);}

$('tradeBtn').addEventListener('click',async()=>{
  const a=parseFloat($('tAmt').value);
  if(!a||a<1){CN.toast('Enter a valid amount (min $1)','warning');return;}
  const btn=$('tradeBtn'); CN.setLoading(btn,true);
  const fd=new FormData();
  fd.append('do_trade','1');fd.append('action',buying?'buy':'sell');fd.append('currency',sym);fd.append('amount_usd',a);fd.append('price_usd',price);
  try{
    const r=await fetch(location.href,{method:'POST',body:fd}).then(x=>x.json());
    if(r.ok){
      CN.toast(r.msg,'success');$('tAmt').value='';calcEst();
      if(buying){wmap['USDT']=Math.max(0,(wmap['USDT']||0)-a);wmap[sym]=(wmap[sym]||0)+r.coinAmt;}
      else{wmap[sym]=Math.max(0,(wmap[sym]||0)-r.coinAmt);wmap['USDT']=(wmap['USDT']||0)+a*.999;}
      $('coinBal').textContent=parseFloat(wmap[sym]||0).toFixed(6)+' '+sym;
    }else CN.toast(r.msg,'error');
  }catch{CN.toast('Trade failed. Try again.','error');}
  CN.setLoading(btn,false); updateBtn();
});

async function loadChart(id,days=7){
  $('chartLoad').style.display='flex';$('priceChart').style.display='none';
  if(chart){chart.destroy();chart=null;}
  try{
    const d=await fetch(`${base}/trade.php?history=1&id=${id}&days=${days}`).then(r=>r.json());
    if(!d.prices?.length)throw 0;
    const labels=d.prices.map(p=>{const dt=new Date(p[0]);return days<=1?dt.toLocaleTimeString('en',{hour:'2-digit',minute:'2-digit'}):dt.toLocaleDateString('en',{month:'short',day:'numeric'});});
    $('chartLoad').style.display='none';$('priceChart').style.display='';
    chart=CN.priceChart('priceChart',labels,d.prices.map(p=>p[1]),sym);
  }catch{$('chartLoad').innerHTML='<p style="color:var(--muted);font-size:.85rem">Chart unavailable</p>';}
}

document.querySelectorAll('.tf').forEach(b=>b.addEventListener('click',function(){
  document.querySelectorAll('.tf').forEach(x=>{x.style.borderColor='';x.style.color='';});
  this.style.borderColor='var(--primary)';this.style.color='var(--primary)';
  loadChart(coinId,this.dataset.d);
}));

function orderBook(px){
  const mk=(type)=>Array.from({length:8},(_,i)=>{
    const p=type==='ask'?px*(1+(i+1)*.0005+Math.random()*.001):px*(1-(i+1)*.0005-Math.random()*.001);
    const a=(Math.random()*5+.1).toFixed(4);const pct=Math.random()*80+10;
    const cls=type==='ask'?'up':'dn';const bgcol=type==='ask'?'var(--red)':'var(--green)';
    return `<div class="order-row"><div class="${cls}">${p.toFixed(2)}</div><div style="color:var(--muted)">${a}</div><div style="color:var(--muted)">${(p*a).toFixed(2)}</div><div class="order-bg" style="width:${pct}%;background:${bgcol};${type==='ask'?'right:0':'left:0'}"></div></div>`;
  }).join('');
  $('asks').innerHTML=mk('ask');$('bids').innerHTML=mk('bid');
}

document.addEventListener('DOMContentLoaded',()=>{
  const s=$('coinSel'); uiCoin(s.options[s.selectedIndex]);
});
</script>
