'use strict';
const CSRF = document.querySelector('meta[name="cn-csrf"]')?.content || '';
const BASE = document.querySelector('meta[name="cn-base"]')?.content || '';

const fUSD = n => {
  if (!n && n !== 0) return '$—';
  const a = Math.abs(n);
  if (a >= 1e12) return '$' + (n/1e12).toFixed(2) + 'T';
  if (a >= 1e9)  return '$' + (n/1e9).toFixed(2) + 'B';
  if (a >= 1e6)  return '$' + (n/1e6).toFixed(2) + 'M';
  if (a >= 1000) return '$' + n.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
  if (a >= 1)    return '$' + n.toFixed(4);
  if (a >= 0.01) return '$' + n.toFixed(6);
  return '$' + n.toFixed(8);
};
const fNum = n => !n ? '—' : n >= 1e9 ? (n/1e9).toFixed(2)+'B' : n >= 1e6 ? (n/1e6).toFixed(2)+'M' : n >= 1e3 ? (n/1e3).toFixed(1)+'K' : n.toFixed(2);

// ─── Toast ────────────────────────────────────────────────────────────────────
const toast = (msg, type = 'info') => {
  let wrap = document.getElementById('toast-wrap');
  if (!wrap) { wrap = document.createElement('div'); wrap.id = 'toast-wrap'; wrap.className = 'toast-wrap'; document.body.appendChild(wrap); }
  const colors = {success:'var(--green)',error:'var(--red)',warning:'var(--gold)',info:'var(--primary)'};
  const icons  = {success:'✓',error:'✗',warning:'!',info:'i'};
  const el = document.createElement('div');
  el.className = 'toast';
  el.style.borderLeft = '3px solid ' + colors[type];
  el.innerHTML = `<span style="color:${colors[type]};font-weight:700">${icons[type]}</span><span>${msg}</span><button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;color:var(--muted);cursor:pointer;font-size:1rem">×</button>`;
  wrap.appendChild(el);
  setTimeout(() => el.remove(), 4500);
};

// ─── Ticker ───────────────────────────────────────────────────────────────────
const initTicker = async () => {
  const track = document.getElementById('ticker-track');
  if (!track) return;
  try {
    const res = await fetch('https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&per_page=15&page=1&sparkline=false');
    if (!res.ok) throw 0;
    const coins = await res.json();
    const html = coins.map(c => {
      const ch = c.price_change_percentage_24h || 0;
      return `<span style="display:inline-flex;align-items:center;gap:.35rem">
        <b class="t-sym">${c.symbol.toUpperCase()}</b>
        <span class="t-px">${fUSD(c.current_price)}</span>
        <span class="${ch>=0?'t-up':'t-dn'}">${ch>=0?'▲':'▼'}${Math.abs(ch).toFixed(2)}%</span>
      </span>`;
    }).join('');
    track.innerHTML = html + html;
    track.style.animation = `tick ${track.scrollWidth / 160}s linear infinite`;
  } catch { track.innerHTML = '<span style="color:var(--muted);padding:0 1rem">Live prices loading...</span>'; }
};

// ─── Navbar ───────────────────────────────────────────────────────────────────
const initNav = () => {
  const nav = document.getElementById('nav');
  if (nav) window.addEventListener('scroll', () => nav.classList.toggle('scrolled', scrollY > 30), {passive:true});

  // Dropdowns
  document.querySelectorAll('[data-dd]').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      const menu = document.getElementById(btn.dataset.dd);
      if (menu) menu.classList.toggle('show');
    });
  });
  document.addEventListener('click', () => document.querySelectorAll('.ddmenu,.search-drop').forEach(m => m.classList.remove('show')));
};

// ─── Search ───────────────────────────────────────────────────────────────────
const initSearch = () => {
  const inp = document.getElementById('nav-search');
  const drop = document.getElementById('search-drop');
  if (!inp || !drop) return;
  let t;
  inp.addEventListener('input', () => {
    clearTimeout(t);
    const q = inp.value.trim();
    if (q.length < 2) { drop.classList.remove('show'); return; }
    t = setTimeout(async () => {
      try {
        const d = await fetch(`https://api.coingecko.com/api/v3/search?query=${encodeURIComponent(q)}`).then(r=>r.json());
        const coins = (d.coins||[]).slice(0,6);
        if (!coins.length) { drop.classList.remove('show'); return; }
        drop.innerHTML = coins.map(c => `<div class="search-item" onclick="location.href='${BASE}/coin.php?id=${c.id}'">
          <img src="${c.thumb}" width="22" height="22" style="border-radius:50%" onerror="this.style.display='none'">
          <div><div style="font-weight:600;font-size:.83rem">${c.name}</div><div style="font-size:.7rem;color:var(--muted)">${c.symbol.toUpperCase()} · #${c.market_cap_rank||'—'}</div></div>
        </div>`).join('');
        drop.classList.add('show');
      } catch { drop.classList.remove('show'); }
    }, 380);
  });
  inp.addEventListener('click', e => e.stopPropagation());
  drop.addEventListener('click', e => e.stopPropagation());
};

// ─── Sparkline ────────────────────────────────────────────────────────────────
const sparkline = (canvas, prices, up=true) => {
  if (!canvas || !prices?.length) return;
  const ctx = canvas.getContext('2d');
  const c = up ? '#10b981' : '#ef4444';
  new Chart(ctx, {
    type:'line',
    data:{labels:prices.map((_,i)=>i),datasets:[{data:prices,borderColor:c,borderWidth:1.5,pointRadius:0,tension:.4,fill:true,backgroundColor:ctx2=>{const g=ctx2.chart.ctx.createLinearGradient(0,0,0,40);g.addColorStop(0,c+'40');g.addColorStop(1,c+'00');return g}}]},
    options:{animation:false,scales:{x:{display:false},y:{display:false}},plugins:{legend:{display:false},tooltip:{enabled:false}}}
  });
};

// ─── Portfolio donut ──────────────────────────────────────────────────────────
const donutChart = (id, items) => {
  const c = document.getElementById(id);
  if (!c || !items?.length) return;
  const colors = ['#3b82f6','#10b981','#8b5cf6','#f59e0b','#06b6d4','#ef4444','#ec4899','#84cc16'];
  new Chart(c.getContext('2d'), {
    type:'doughnut',
    data:{labels:items.map(d=>d.currency),datasets:[{data:items.map(d=>d.value),backgroundColor:colors.slice(0,items.length),borderColor:'#0d1220',borderWidth:3}]},
    options:{cutout:'72%',plugins:{legend:{display:false},tooltip:{backgroundColor:'#111827',bodyColor:'#94a3b8',callbacks:{label:ctx=>` ${ctx.label}: ${fUSD(ctx.raw)}`}}}}
  });
};

// ─── Price chart ──────────────────────────────────────────────────────────────
const priceChart = (id, labels, prices, sym='') => {
  const c = document.getElementById(id);
  if (!c) return null;
  const up = prices[prices.length-1] >= prices[0];
  const col = up ? '#10b981' : '#ef4444';
  return new Chart(c.getContext('2d'), {
    type:'line',
    data:{labels,datasets:[{label:sym,data:prices,borderColor:col,borderWidth:2,pointRadius:0,pointHoverRadius:4,tension:.3,fill:true,backgroundColor:ctx=>{const g=ctx.chart.ctx.createLinearGradient(0,0,0,c.offsetHeight);g.addColorStop(0,col+'30');g.addColorStop(1,col+'00');return g}}]},
    options:{responsive:true,maintainAspectRatio:false,interaction:{intersect:false,mode:'index'},
      scales:{x:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#64748b',font:{size:11},maxTicksLimit:8},border:{display:false}},
              y:{grid:{color:'rgba(255,255,255,.04)'},ticks:{color:'#64748b',font:{size:11},callback:v=>fUSD(v)},border:{display:false},position:'right'}},
      plugins:{legend:{display:false},tooltip:{backgroundColor:'#111827',borderColor:'rgba(255,255,255,.1)',borderWidth:1,titleColor:'#e2e8f0',bodyColor:'#94a3b8',callbacks:{label:ctx=>` ${fUSD(ctx.raw)}`}}}}
  });
};

// ─── AJAX POST ────────────────────────────────────────────────────────────────
const post = async (url, data) => {
  const r = await fetch(url, {method:'POST',headers:{'Content-Type':'application/json','X-CSRF':CSRF},body:JSON.stringify({...data,csrf:CSRF})});
  return r.json();
};

// ─── Loading state ────────────────────────────────────────────────────────────
const setLoading = (btn, on) => {
  if (on) { btn._t = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span style="display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;margin-right:.4rem"></span>Loading...'; }
  else { btn.disabled = false; btn.innerHTML = btn._t || btn.innerHTML; }
};

// ─── Fade in on scroll ────────────────────────────────────────────────────────
const initFade = () => {
  const obs = new IntersectionObserver(entries => entries.forEach(e => { if (e.isIntersecting) { e.target.style.opacity='1'; e.target.style.transform='translateY(0)'; obs.unobserve(e.target); } }), {threshold:.1,rootMargin:'0px 0px -50px 0px'});
  document.querySelectorAll('.fu').forEach(el => obs.observe(el));
};

// ─── Password toggle ──────────────────────────────────────────────────────────
document.querySelectorAll('.toggle-pw').forEach(btn => {
  btn.addEventListener('click', () => {
    const inp = document.querySelector(btn.dataset.t);
    if (!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.textContent = inp.type === 'password' ? '👁' : '🙈';
  });
});

document.addEventListener('DOMContentLoaded', () => { initNav(); initTicker(); initSearch(); initFade(); });

window.CN = { fUSD, fNum, toast, sparkline, donutChart, priceChart, post, setLoading };
