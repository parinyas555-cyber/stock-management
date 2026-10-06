<?php
require_once __DIR__ . '/bootstrap.php';
require_login();
$flash=get_flash();
function nav($path,$label,$icon='•'){$active=basename($_SERVER['PHP_SELF'])===$path?'active':''; echo '<a class="nav-link '.$active.'" href="/'.$path.'"><span class="nav-icon">'.$icon.'</span><span>'.$label.'</span></a>';}
function page_start($title){global $appName,$flash,$dbError;?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0f172a"><title><?=h($title)?> - <?=h($appName)?></title><link rel="stylesheet" href="/style.css"></head><body>
<aside class="sidebar" id="sidebar">
  <div class="brand"><div class="brand-mark">📦</div><div><div class="brand-title">Stock Management</div><div class="brand-sub">ระบบจัดการคลังสินค้า</div></div></div>
  <div class="nav-section">เมนูหลัก</div>
  <?php nav('dashboard.php','แดชบอร์ด','⌂'); nav('products.php','คลังสินค้า','▣'); nav('stock_in.php','สินค้าเข้า','＋'); nav('stock_out.php','สินค้าออก','−'); nav('movements.php','รายการเคลื่อนไหว','↕'); nav('reports.php','รายงานสรุป','▤'); ?>
  <div class="nav-section">บัญชีผู้ใช้</div>
  <?php nav('password.php','เปลี่ยนรหัสผ่าน','🔑'); if(is_admin()) { nav('users.php','การจัดการผู้ใช้งาน','♙'); nav('reset.php','Factory Reset','⚠'); } ?>
  <div class="sidebar-divider"></div>
  <a class="nav-link" href="/logout.php"><span class="nav-icon">↪</span><span>ออกจากระบบ</span></a>
  <div style="margin:14px 8px 4px;padding:12px;border:1px solid rgba(148,163,184,.14);border-radius:14px;background:rgba(255,255,255,.045);font-size:11px;color:#94a3b8;line-height:1.5"><div class="system-status"><span id="systemStatusLed" class="status-led"></span><strong id="systemStatusText" style="color:#cbd5e1">กำลังตรวจสอบระบบ...</strong></div><div id="systemStatusDetail" style="margin-top:4px">ตรวจสอบการเชื่อมต่อ PostgreSQL</div></div>
</aside>
<div class="mobile-overlay" id="mobileOverlay" onclick="closeSidebar()"></div>
<main>
<header class="topbar"><div class="page-heading"><button class="menu-toggle" type="button" onclick="toggleSidebar()">☰</button><div><div class="eyebrow">STOCK MANAGEMENT</div><h2><?=h($title)?></h2><div class="muted">จัดการสินค้าและติดตามการเคลื่อนไหวของคลัง</div></div></div><div class="user-pill"><span class="avatar">👤</span><span><?=h($_SESSION['user']['full_name'])?></span><span class="muted role-text">· <?=h($_SESSION['user']['role'])?></span></div></header>
<?php if(!empty($dbError)):?><div class="alert danger">Database Error: <?=h($dbError)?></div><?php endif;?><?php if($flash):?><div class="alert <?=h($flash[0])?>"><?=h($flash[1])?></div><?php endif;?>
<?php }
function page_end(){?><script>
function toggleSidebar(){document.getElementById('sidebar')?.classList.toggle('open');document.getElementById('mobileOverlay')?.classList.toggle('show')}
function closeSidebar(){document.getElementById('sidebar')?.classList.remove('open');document.getElementById('mobileOverlay')?.classList.remove('show')}
async function checkSystemStatus(){const led=document.getElementById('systemStatusLed'),text=document.getElementById('systemStatusText'),detail=document.getElementById('systemStatusDetail');if(!led)return;try{const r=await fetch('/health.php?ts='+Date.now(),{cache:'no-store'});const d=await r.json();if(r.ok&&d.status==='ok'){led.className='status-led online';text.textContent='ระบบพร้อมใช้งาน';detail.textContent='ออนไลน์ • PostgreSQL เชื่อมต่อปกติ';}else{led.className='status-led offline';text.textContent='ระบบไม่พร้อมใช้งาน';detail.textContent='ออฟไลน์ • ไม่สามารถเชื่อมต่อฐานข้อมูล';}}catch(e){led.className='status-led offline dark';text.textContent='ระบบออฟไลน์';detail.textContent='ไม่สามารถตรวจสอบสถานะระบบได้';}}
checkSystemStatus();setInterval(checkSystemStatus,15000);
</script></main>
<script>
(function(){
  let busy=false;
  function isPaginationLink(a){
    if(!a || !a.href || a.target==='_blank') return false;
    const text=(a.textContent||'').trim();
    if(!/ก่อนหน้า|ถัดไป|Previous|Next/.test(text)) return false;
    const u=new URL(a.href,location.href);
    return u.origin===location.origin && u.pathname===location.pathname;
  }
  function runScripts(root){
    root.querySelectorAll('script').forEach(old=>{
      const s=document.createElement('script');
      for(const attr of old.attributes) s.setAttribute(attr.name,attr.value);
      s.textContent=old.textContent;
      old.replaceWith(s);
    });
  }
  async function loadPage(url,push=true){
    if(busy)return;
    busy=true;
    document.body.classList.add('ajax-loading');
    try{
      const r=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest'},cache:'no-store'});
      if(!r.ok)throw new Error('HTTP '+r.status);
      const html=await r.text();
      const doc=new DOMParser().parseFromString(html,'text/html');
      const nextMain=doc.querySelector('main');
      const currentMain=document.querySelector('main');
      if(!nextMain||!currentMain)throw new Error('ไม่พบเนื้อหาหน้าเว็บ');
      currentMain.innerHTML=nextMain.innerHTML;
      const nextTitle=doc.querySelector('title'); if(nextTitle) document.title=nextTitle.textContent;
      runScripts(currentMain);
      if(push)history.pushState({ajax:true},'',url);
      window.scrollTo({top:0,behavior:'smooth'});
    }catch(e){console.error(e);location.href=url;}
    finally{busy=false;document.body.classList.remove('ajax-loading');}
  }
  document.addEventListener('click',function(e){
    const a=e.target.closest('a');
    if(!isPaginationLink(a))return;
    e.preventDefault();loadPage(a.href,true);
  });
  document.addEventListener('submit',function(e){
    const form=e.target;
    if(!(form instanceof HTMLFormElement))return;
    const select=form.querySelector('select[name="per_page"]');
    if(!select || form.method.toLowerCase()==='post')return;
    e.preventDefault();
    const fd=new FormData(form);
    const url=new URL(form.action||location.href,location.href);
    for(const [k,v] of fd.entries())url.searchParams.set(k,v);
    loadPage(url.toString(),true);
  });
  window.addEventListener('popstate',function(){loadPage(location.href,false);});
})();
</script></body></html><?php }
