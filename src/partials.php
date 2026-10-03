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
  <?php nav('password.php','เปลี่ยนรหัสผ่าน','🔑'); if(is_admin()) nav('users.php','การจัดการผู้ใช้งาน','♙'); ?>
  <div class="sidebar-divider"></div>
  <a class="nav-link" href="/logout.php"><span class="nav-icon">↪</span><span>ออกจากระบบ</span></a>
  <div style="margin:14px 8px 4px;padding:12px;border:1px solid rgba(148,163,184,.14);border-radius:14px;background:rgba(255,255,255,.045);font-size:11px;color:#94a3b8;line-height:1.5"><div style="color:#cbd5e1;font-weight:700">ระบบพร้อมใช้งาน</div><div>คลังสินค้าออนไลน์ • PostgreSQL</div></div>
</aside>
<div class="mobile-overlay" id="mobileOverlay" onclick="closeSidebar()"></div>
<main>
<header class="topbar"><div class="page-heading"><button class="menu-toggle" type="button" onclick="toggleSidebar()">☰</button><div><div class="eyebrow">STOCK MANAGEMENT</div><h2><?=h($title)?></h2><div class="muted">จัดการสินค้าและติดตามการเคลื่อนไหวของคลัง</div></div></div><div class="user-pill"><span class="avatar">👤</span><span><?=h($_SESSION['user']['full_name'])?></span><span class="muted role-text">· <?=h($_SESSION['user']['role'])?></span></div></header>
<?php if(!empty($dbError)):?><div class="alert danger">Database Error: <?=h($dbError)?></div><?php endif;?><?php if($flash):?><div class="alert <?=h($flash[0])?>"><?=h($flash[1])?></div><?php endif;?>
<?php }
function page_end(){?><script>function toggleSidebar(){document.getElementById('sidebar')?.classList.toggle('open');document.getElementById('mobileOverlay')?.classList.toggle('show')}function closeSidebar(){document.getElementById('sidebar')?.classList.remove('open');document.getElementById('mobileOverlay')?.classList.remove('show')}</script></main></body></html><?php }
