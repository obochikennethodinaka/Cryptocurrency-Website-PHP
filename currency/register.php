<?php
require_once __DIR__ . '/config.php';
startSession();
if (isLoggedIn()) { header('Location: ' . APP_URL . '/dashboard.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uname = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    $conf  = $_POST['confirm'] ?? '';
    $fname = trim($_POST['full_name'] ?? '');

    if (strlen($uname) < 3 || strlen($uname) > 30)  $err = 'Username must be 3-30 characters.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Invalid email address.';
    elseif (strlen($pass) < 8)                        $err = 'Password must be at least 8 characters.';
    elseif ($pass !== $conf)                           $err = 'Passwords do not match.';
    elseif (DB::one('SELECT id FROM users WHERE email=? OR username=?', [$email,$uname])) $err = 'Email or username already taken.';
    else {
        $uid = DB::insert('users', ['username'=>htmlspecialchars($uname),'email'=>$email,'password_hash'=>password_hash($pass,PASSWORD_BCRYPT,['cost'=>10]),'full_name'=>htmlspecialchars($fname),'is_verified'=>1]);
        foreach (['BTC'=>0.05,'ETH'=>1.5,'USDT'=>500,'BNB'=>2,'SOL'=>10] as $cur=>$bal) {
            DB::insert('wallets', ['user_id'=>$uid,'currency'=>$cur,'balance'=>$bal]);
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $uid; $_SESSION['username'] = $uname; $_SESSION['email'] = $email; $_SESSION['is_admin'] = 0;
        header('Location: ' . APP_URL . '/dashboard.php?welcome=1'); exit;
    }
}
pageHead('Create Account — CryptoNexus');
?>
<div class="auth-wrap" style="padding:2rem 1rem">
<div class="auth-card" style="max-width:480px">
  <div style="text-align:center;margin-bottom:1.5rem">
    <a href="<?= APP_URL ?>/index.php" style="text-decoration:none;display:inline-flex;align-items:center;gap:.5rem">
      <i class="bi bi-hexagon-fill" style="color:var(--primary);font-size:1.8rem"></i>
      <span style="font-size:1.3rem;font-weight:700;color:#fff">Crypto<span style="color:var(--primary)">Nexus</span></span>
    </a>
    <p style="color:var(--muted);font-size:.85rem;margin:.5rem 0 0">Create your free trading account</p>
  </div>

  <?php if ($err): ?>
  <div style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:.75rem 1rem;color:#ef4444;font-size:.875rem;margin-bottom:1rem">
    <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($err) ?>
  </div>
  <?php endif; ?>

  <form method="POST">
    <div style="margin-bottom:.9rem">
      <label class="flbl">Full Name</label>
      <input class="fc" type="text" name="full_name" placeholder="John Doe" value="<?= htmlspecialchars($_POST['full_name']??'') ?>">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:.9rem">
      <div>
        <label class="flbl">Username *</label>
        <input class="fc" type="text" name="username" placeholder="johndoe" required minlength="3" maxlength="30" pattern="[a-zA-Z0-9_]+" value="<?= htmlspecialchars($_POST['username']??'') ?>">
      </div>
      <div>
        <label class="flbl">Email *</label>
        <input class="fc" type="email" name="email" placeholder="you@email.com" required value="<?= htmlspecialchars($_POST['email']??'') ?>">
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:.9rem">
      <div>
        <label class="flbl">Password *</label>
        <div style="position:relative">
          <input class="fc" type="password" name="password" id="pw1" placeholder="••••••••" required minlength="8">
          <button type="button" class="toggle-pw" data-t="#pw1" style="position:absolute;right:.6rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer">👁</button>
        </div>
      </div>
      <div>
        <label class="flbl">Confirm *</label>
        <input class="fc" type="password" name="confirm" placeholder="••••••••" required id="pw2">
      </div>
    </div>
    <!-- Strength bar -->
    <div style="display:flex;gap:3px;margin-bottom:.3rem" id="sb">
      <div style="flex:1;height:3px;border-radius:2px;background:var(--bg4)" id="b1"></div>
      <div style="flex:1;height:3px;border-radius:2px;background:var(--bg4)" id="b2"></div>
      <div style="flex:1;height:3px;border-radius:2px;background:var(--bg4)" id="b3"></div>
      <div style="flex:1;height:3px;border-radius:2px;background:var(--bg4)" id="b4"></div>
    </div>
    <div style="font-size:.76rem;color:var(--muted);margin-bottom:1rem" id="st">Enter a password</div>
    <div style="margin-bottom:1.1rem">
      <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.85rem;color:var(--muted)">
        <input type="checkbox" required style="accent-color:var(--primary)"> I agree to the <a href="#" style="color:var(--primary)">Terms</a> and <a href="#" style="color:var(--primary)">Privacy Policy</a>
      </label>
    </div>
    <button type="submit" class="btn btn-pri" style="width:100%;padding:.7rem;font-size:.95rem"><i class="bi bi-person-plus"></i> Create Account</button>
  </form>

  <div style="background:rgba(16,185,129,.07);border:1px solid rgba(16,185,129,.2);border-radius:10px;padding:.85rem;margin-top:1.1rem">
    <div style="font-size:.78rem;color:var(--muted);margin-bottom:.4rem">What you get free:</div>
    <div style="display:flex;flex-wrap:wrap;gap:.4rem">
      <?php foreach(['Live prices','Portfolio tracker','Trade simulator','Price alerts','News feed'] as $f): ?>
      <span style="background:rgba(16,185,129,.15);color:var(--green);padding:.15rem .55rem;border-radius:5px;font-size:.76rem;font-weight:600">✓ <?=$f?></span>
      <?php endforeach; ?>
    </div>
  </div>

  <p style="text-align:center;font-size:.85rem;color:var(--muted);margin-top:1.1rem 0 0">
    Already have an account? <a href="<?= APP_URL ?>/login.php" style="color:var(--primary);font-weight:600;text-decoration:none">Sign in</a>
  </p>
</div>
</div>
<?php pageFooter(); ?>
<script>
const pw1=document.getElementById('pw1'),bars=[1,2,3,4].map(i=>document.getElementById('b'+i)),st=document.getElementById('st');
const lvl=[['#ef4444','Weak'],['#f59e0b','Fair'],['#3b82f6','Good'],['#10b981','Strong']];
pw1.addEventListener('input',()=>{
  const v=pw1.value;let s=0;
  if(v.length>=8)s++;if(/[A-Z]/.test(v))s++;if(/[0-9]/.test(v))s++;if(/[^A-Za-z0-9]/.test(v))s++;
  const[c,l]=lvl[Math.min(s,3)];
  bars.forEach((b,i)=>{b.style.background=i<s?c:'var(--bg4)';});
  st.textContent=v.length?l+' password':'Enter a password';st.style.color=c;
});
document.getElementById('pw2').addEventListener('input',function(){this.style.borderColor=this.value&&this.value!==pw1.value?'var(--red)':'';});
</script>
