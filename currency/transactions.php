<?php
// transactions.php
require_once __DIR__ . '/config.php';
requireLogin();
$uid   = $_SESSION['user_id'];
$pg    = max((int)($_GET['page']??1),1);
$lim   = 20; $off=($pg-1)*$lim;
$type  = $_GET['type']??'';
$where = 'WHERE user_id=?'; $params=[$uid];
if($type){$where.=' AND type=?';$params[]=$type;}
$total = (int)(DB::one("SELECT COUNT(*) c FROM transactions $where",$params)['c']??0);
$pages = ceil($total/$lim);
$txs   = DB::all("SELECT * FROM transactions $where ORDER BY created_at DESC LIMIT $lim OFFSET $off",$params);
pageHead('Transactions — CryptoNexus');
navbar();
?>
<main class="pw" style="padding-bottom:3rem">
<div style="max-width:1200px;margin:0 auto;padding:0 1.25rem">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem">
    <div><h4 style="font-weight:700;margin:0">Transactions</h4><p style="color:var(--muted);font-size:.82rem;margin:0"><?=number_format($total)?> total</p></div>
    <div style="display:flex;gap:.5rem">
      <select class="fc" style="width:auto" onchange="location.href='?type='+this.value">
        <option value="" <?=!$type?'selected':''?>>All Types</option>
        <?php foreach(['buy','sell','send','receive','deposit','withdraw'] as $t): ?>
        <option value="<?=$t?>" <?=$type===$t?'selected':''?>><?=ucfirst($t)?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-out btn-sm" onclick="window.print()"><i class="bi bi-download"></i> Export</button>
    </div>
  </div>
  <div class="card" style="overflow:hidden">
  <div style="overflow-x:auto">
  <table class="tbl">
    <thead><tr><th>Type</th><th>Asset</th><th style="text-align:right">Amount</th><th style="text-align:right">Price</th><th style="text-align:right">Total</th><th style="text-align:right">Fee</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>
    <?php if(!$txs): ?>
    <tr><td colspan="8" style="text-align:center;padding:3rem;color:var(--muted)"><i class="bi bi-receipt" style="font-size:2.5rem;display:block;opacity:.3;margin-bottom:.5rem"></i>No transactions yet. <a href="<?=APP_URL?>/trade.php" style="color:var(--primary)">Start trading!</a></td></tr>
    <?php endif; ?>
    <?php
    $tmap=['buy'=>['tx-buy','bi-arrow-down-circle-fill'],'sell'=>['tx-sell','bi-arrow-up-circle-fill'],'send'=>['tx-send','bi-send-fill'],'receive'=>['tx-rcv','bi-inbox-fill'],'deposit'=>['tx-rcv','bi-download'],'withdraw'=>['tx-send','bi-upload']];
    $smap=['completed'=>'badge-up','pending'=>'badge-neu','failed'=>'badge-dn'];
    foreach($txs as $tx):
      [$cls,$ico]=$tmap[$tx['type']]??['tx-buy','bi-circle'];
      $isIn=in_array($tx['type'],['buy','receive','deposit']);
      $tot=(float)$tx['amount']*(float)($tx['price_usd']??0);
    ?>
    <tr>
      <td><div style="display:flex;align-items:center;gap:.5rem"><div class="tx-ico <?=$cls?>" style="width:28px;height:28px;border-radius:7px;font-size:.78rem"><i class="bi <?=$ico?>"></i></div><span style="font-weight:600;font-size:.85rem"><?=ucfirst($tx['type'])?></span></div></td>
      <td><span style="font-weight:600;font-size:.85rem"><?=htmlspecialchars($tx['currency_to']??$tx['currency_from']??'—')?></span></td>
      <td style="text-align:right" class="mono <?=$isIn?'up':'dn'?>"><?=$isIn?'+':'-'?><?=number_format((float)$tx['amount'],6)?></td>
      <td style="text-align:right;font-size:.83rem;color:var(--muted)"><?=$tx['price_usd']?'$'.number_format((float)$tx['price_usd'],2):'—'?></td>
      <td style="text-align:right;font-size:.83rem;color:var(--muted)"><?=$tot>0?'$'.number_format($tot,2):'—'?></td>
      <td style="text-align:right;font-size:.78rem;color:var(--muted)">$<?=number_format((float)($tx['fee']??0),4)?></td>
      <td><span class="badge <?=$smap[$tx['status']]??'badge-neu'?>"><?=ucfirst($tx['status'])?></span></td>
      <td style="font-size:.8rem;color:var(--muted)"><?=date('M j, Y H:i',strtotime($tx['created_at']))?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  </div>
  <?php if($pages>1): ?>
  <div style="display:flex;justify-content:center;gap:.35rem;margin-top:1.25rem">
    <?php for($p=1;$p<=$pages;$p++): ?>
    <a href="?page=<?=$p?><?=$type?"&type=$type":''?>" class="btn <?=$p===$pg?'btn-pri':'btn-out'?> btn-sm"><?=$p?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>
</main>
<?php pageFooter(); ?>
