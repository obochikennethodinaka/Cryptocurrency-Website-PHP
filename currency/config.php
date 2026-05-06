<?php
// ─── Database ────────────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'cryptonexus');
define('DB_USER',    'root');
define('DB_PASS',    '');          // blank by default on XAMPP
define('DB_CHARSET', 'utf8mb4');

// ─── App settings ─────────────────────────────────────────────────────────────
define('APP_NAME',    'CryptoNexus');
define('APP_VERSION', '1.0.0');
define('COINGECKO_API_URL', 'https://api.coingecko.com/api/v3');
define('SESSION_LIFETIME',  7200);
define('CSRF_TOKEN_LENGTH', 32);

// ─── Auto-detect URL (works in any folder name) ───────────────────────────────
if (!defined('APP_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $appRoot  = str_replace('\\', '/', __DIR__);
    $docRoot  = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
    $subPath  = '/' . trim(str_replace($docRoot, '', $appRoot), '/');
    define('APP_URL', $protocol . '://' . $host . $subPath);
}

// ─── Database class ───────────────────────────────────────────────────────────
class DB {
    private static ?PDO $pdo = null;

    public static function get(): PDO {
        if (!self::$pdo) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }

    public static function run(string $sql, array $p = []): PDOStatement {
        $s = self::get()->prepare($sql);
        $s->execute($p);
        return $s;
    }

    public static function all(string $sql, array $p = []): array {
        return self::run($sql, $p)->fetchAll();
    }

    public static function one(string $sql, array $p = []): array|false {
        return self::run($sql, $p)->fetch();
    }

    public static function insert(string $tbl, array $data): int {
        $cols = implode(',', array_keys($data));
        $vals = implode(',', array_fill(0, count($data), '?'));
        self::run("INSERT INTO {$tbl} ({$cols}) VALUES ({$vals})", array_values($data));
        return (int) self::get()->lastInsertId();
    }

    public static function update(string $tbl, array $data, string $where, array $wp = []): int {
        $set  = implode(',', array_map(fn($c) => "{$c}=?", array_keys($data)));
        return self::run("UPDATE {$tbl} SET {$set} WHERE {$where}", [...array_values($data), ...$wp])->rowCount();
    }
}

// ─── Session ──────────────────────────────────────────────────────────────────
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php?r=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (empty($_SESSION['is_admin'])) {
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $u = DB::one('SELECT id,username,email,full_name,is_admin,is_verified,created_at FROM users WHERE id=?', [$_SESSION['user_id']]);
    return $u ?: null;
}

function csrfToken(): string {
    startSession();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf'];
}

function verifyCsrf(string $t): bool {
    startSession();
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

// ─── CoinGecko helpers ────────────────────────────────────────────────────────
function cgFetch(string $endpoint, array $params = []): array|false {
    $url = COINGECKO_API_URL . $endpoint;
    if ($params) $url .= '?' . http_build_query($params);
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10, CURLOPT_HTTPHEADER => ['Accept: application/json','User-Agent: CryptoNexus/1.0'],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($res && $code === 200) ? (json_decode($res, true) ?? false) : false;
}

function cgCache(string $key, callable $fn, int $ttl = 60): mixed {
    $file = sys_get_temp_dir() . '/cn_' . md5($key) . '.cache';
    if (file_exists($file) && (time() - filemtime($file)) < $ttl) {
        $d = @unserialize(file_get_contents($file));
        if ($d !== false) return $d;
    }
    $data = $fn();
    if ($data) @file_put_contents($file, serialize($data));
    return $data;
}

function getTopCoins(int $limit = 20): array {
    return cgCache("top_{$limit}", fn() => cgFetch('/coins/markets', [
        'vs_currency' => 'usd', 'order' => 'market_cap_desc',
        'per_page' => $limit, 'page' => 1,
        'sparkline' => 'true', 'price_change_percentage' => '1h,24h,7d',
    ]), 60) ?: DB::all('SELECT * FROM crypto_coins WHERE is_active=1 ORDER BY rank LIMIT ' . $limit);
}

function getGlobalStats(): array {
    $d = cgCache('global', fn() => cgFetch('/global'), 120);
    return $d['data'] ?? ['total_market_cap'=>['usd'=>2.3e12],'total_volume'=>['usd'=>85e9],
                          'market_cap_percentage'=>['btc'=>52,'eth'=>17],'active_cryptocurrencies'=>13500,
                          'market_cap_change_percentage_24h_usd'=>0];
}

function getCoinHistory(string $id, int $days = 7): array {
    return cgCache("hist_{$id}_{$days}", fn() => cgFetch("/coins/{$id}/market_chart", [
        'vs_currency' => 'usd', 'days' => $days,
    ]), 300) ?: ['prices' => []];
}

function getCoinDetail(string $id): array|false {
    return cgCache("coin_{$id}", fn() => cgFetch("/coins/{$id}", [
        'localization' => 'false', 'tickers' => 'false',
        'market_data' => 'true', 'community_data' => 'false',
        'developer_data' => 'false',
    ]), 300);
}

function fmtPrice(float $p): string {
    if ($p >= 1000) return '$' . number_format($p, 2);
    if ($p >= 1)    return '$' . number_format($p, 4);
    if ($p >= 0.01) return '$' . number_format($p, 6);
    return '$' . number_format($p, 8);
}

function fmtBig(float $n): string {
    if ($n >= 1e12) return '$' . round($n/1e12, 2) . 'T';
    if ($n >= 1e9)  return '$' . round($n/1e9, 2) . 'B';
    if ($n >= 1e6)  return '$' . round($n/1e6, 2) . 'M';
    return '$' . number_format($n, 0);
}

function symbolToId(): array {
    return [
        'BTC'=>'bitcoin','ETH'=>'ethereum','USDT'=>'tether','BNB'=>'binancecoin',
        'SOL'=>'solana','XRP'=>'ripple','USDC'=>'usd-coin','ADA'=>'cardano',
        'AVAX'=>'avalanche-2','DOGE'=>'dogecoin','DOT'=>'polkadot','MATIC'=>'matic-network',
        'LINK'=>'chainlink','LTC'=>'litecoin','UNI'=>'uniswap','ATOM'=>'cosmos',
        'XLM'=>'stellar','BCH'=>'bitcoin-cash','NEAR'=>'near','FTM'=>'fantom',
    ];
}

function portfolioValue(array $wallets): array {
    if (!$wallets) return ['total'=>0,'items'=>[]];
    $map  = symbolToId();
    $ids  = array_filter(array_map(fn($w) => $map[strtoupper($w['currency'])] ?? null, $wallets));
    if (!$ids) return ['total'=>0,'items'=>[]];

    $prices = cgCache('prices_'.md5(implode(',',$ids)), fn() => cgFetch('/simple/price', [
        'ids' => implode(',', array_unique($ids)),
        'vs_currencies' => 'usd', 'include_24hr_change' => 'true',
    ]), 60) ?: [];

    $total = 0; $items = [];
    foreach ($wallets as $w) {
        $sym  = strtoupper($w['currency']);
        $id   = $map[$sym] ?? null;
        $usd  = $id ? ($prices[$id]['usd'] ?? 0) : 0;
        $chg  = $id ? ($prices[$id]['usd_24h_change'] ?? 0) : 0;
        $val  = (float)$w['balance'] * $usd;
        $total += $val;
        $items[] = ['currency'=>$sym,'balance'=>(float)$w['balance'],'price'=>$usd,'value'=>$val,'change'=>$chg];
    }
    usort($items, fn($a,$b) => $b['value'] <=> $a['value']);
    return ['total'=>$total,'items'=>$items];
}

// ─── Shared HTML helpers ──────────────────────────────────────────────────────
function pageHead(string $title, string $extra = ''): void {
    startSession();
    $csrf = csrfToken();
    $base = APP_URL;
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="cn-csrf" content="{$csrf}">
<meta name="cn-base" content="{$base}">
<title>{$title}</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link href="{$base}/style.css" rel="stylesheet">
{$extra}
</head>
<body>
<div class="bg-wrap"><div class="bg-grid"></div><div class="orb o1"></div><div class="orb o2"></div><div class="orb o3"></div></div>
HTML;
}

function navbar(): void {
    $u    = currentUser();
    $base = APP_URL;
    $csrf = csrfToken();
    $pg   = basename($_SERVER['PHP_SELF'], '.php');
    $a    = fn($p) => $pg === $p ? 'active' : '';
    echo <<<HTML
<nav id="nav">
  <div class="nav-inner">
    <a class="brand" href="{$base}/index.php"><i class="bi bi-hexagon-fill" style="color:var(--primary)"></i> Crypto<span>Nexus</span></a>
    <ul class="nav-links">
      <li><a href="{$base}/index.php" class="{$a('index')}"><i class="bi bi-house"></i> Home</a></li>
      <li><a href="{$base}/markets.php" class="{$a('markets')}"><i class="bi bi-bar-chart"></i> Markets</a></li>
HTML;
    if (isLoggedIn()) {
        echo <<<HTML
      <li><a href="{$base}/dashboard.php" class="{$a('dashboard')}"><i class="bi bi-grid"></i> Dashboard</a></li>
      <li><a href="{$base}/portfolio.php" class="{$a('portfolio')}"><i class="bi bi-pie-chart"></i> Portfolio</a></li>
      <li><a href="{$base}/trade.php" class="{$a('trade')}"><i class="bi bi-arrow-left-right"></i> Trade</a></li>
HTML;
    }
    echo <<<HTML
      <li><a href="{$base}/news.php" class="{$a('news')}"><i class="bi bi-newspaper"></i> News</a></li>
    </ul>
    <div class="nav-right">
      <div class="nav-search">
        <span class="si"><i class="bi bi-search"></i></span>
        <input id="nav-search" type="text" placeholder="Search coins…" autocomplete="off">
        <div class="search-drop" id="search-drop"></div>
      </div>
HTML;
    if (isLoggedIn() && $u) {
        $init = strtoupper(substr($u['username'], 0, 1));
        $name = htmlspecialchars($u['username']);
        echo <<<HTML
      <div style="position:relative">
        <button class="user-btn" data-dd="user-menu">
          <div class="avatar av-sm">{$init}</div> {$name} <i class="bi bi-chevron-down" style="font-size:.7rem"></i>
        </button>
        <div class="ddmenu" id="user-menu">
          <div class="dh">{$name}<br><span style="font-size:.72rem;color:var(--muted)">{$u['email']}</span></div>
          <div class="ddiv"></div>
          <a href="{$base}/dashboard.php"><i class="bi bi-grid"></i> Dashboard</a>
          <a href="{$base}/profile.php"><i class="bi bi-person"></i> Profile</a>
          <a href="{$base}/alerts.php"><i class="bi bi-bell"></i> Alerts</a>
HTML;
        if (!empty($u['is_admin'])) {
            echo "<a href=\"{$base}/admin.php\" style=\"color:var(--gold)\"><i class=\"bi bi-shield-check\"></i> Admin</a>";
        }
        echo <<<HTML
          <div class="ddiv"></div>
          <form method="POST" action="{$base}/logout.php" style="margin:0">
            <input type="hidden" name="csrf" value="{$csrf}">
            <button type="submit" style="color:var(--red)"><i class="bi bi-box-arrow-right"></i> Logout</button>
          </form>
        </div>
      </div>
HTML;
    } else {
        echo <<<HTML
      <a href="{$base}/login.php" class="btn btn-out btn-sm">Login</a>
      <a href="{$base}/register.php" class="btn btn-pri btn-sm">Get Started</a>
HTML;
    }
    echo <<<HTML
    </div>
  </div>
</nav>
<div class="ticker"><div class="ticker-track" id="ticker-track"><span style="color:var(--muted);padding:0 1rem;font-size:.78rem">Loading prices…</span></div></div>
HTML;
}

function pageFooter(): void {
    $base = APP_URL;
    $year = date('Y');
    echo <<<HTML
<footer>
  <div class="ft" style="padding:3rem 0">
    <div style="max-width:1200px;margin:0 auto;padding:0 1.5rem">
      <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:2rem">
        <div>
          <a class="brand" href="{$base}/index.php" style="margin-bottom:.75rem;display:inline-flex"><i class="bi bi-hexagon-fill" style="color:var(--primary)"></i> Crypto<span style="color:var(--primary)">Nexus</span></a>
          <p style="color:var(--muted);font-size:.85rem;line-height:1.7;margin-top:.5rem">Professional cryptocurrency trading platform. Trade, manage, and grow your crypto portfolio.</p>
          <div style="display:flex;gap:.5rem;margin-top:1rem">
            <a href="#" class="btn btn-out btn-sm" style="padding:.3rem .5rem"><i class="bi bi-twitter-x"></i></a>
            <a href="#" class="btn btn-out btn-sm" style="padding:.3rem .5rem"><i class="bi bi-discord"></i></a>
            <a href="#" class="btn btn-out btn-sm" style="padding:.3rem .5rem"><i class="bi bi-telegram"></i></a>
          </div>
        </div>
        <div><h6 style="font-weight:700;margin-bottom:1rem;font-size:.875rem">Platform</h6>
          <div style="display:flex;flex-direction:column;gap:.5rem">
            <a href="{$base}/markets.php" class="flink">Markets</a>
            <a href="{$base}/trade.php" class="flink">Trade</a>
            <a href="{$base}/portfolio.php" class="flink">Portfolio</a>
            <a href="{$base}/news.php" class="flink">News</a>
          </div>
        </div>
        <div><h6 style="font-weight:700;margin-bottom:1rem;font-size:.875rem">Company</h6>
          <div style="display:flex;flex-direction:column;gap:.5rem">
            <a href="#" class="flink">About</a><a href="#" class="flink">Blog</a><a href="#" class="flink">Careers</a><a href="#" class="flink">Contact</a>
          </div>
        </div>
        <div><h6 style="font-weight:700;margin-bottom:1rem;font-size:.875rem">Legal</h6>
          <div style="display:flex;flex-direction:column;gap:.5rem">
            <a href="#" class="flink">Privacy</a><a href="#" class="flink">Terms</a><a href="#" class="flink">Risk</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="fb" style="padding:1rem 1.5rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem">
    <p style="color:var(--muted);font-size:.8rem;margin:0">&copy; {$year} CryptoNexus. All rights reserved.</p>
    <p style="color:var(--muted);font-size:.78rem;margin:0"><i class="bi bi-exclamation-triangle"></i> Crypto trading involves substantial risk of loss.</p>
  </div>
</footer>
<script src="{$base}/main.js"></script>
</body></html>
HTML;
}
