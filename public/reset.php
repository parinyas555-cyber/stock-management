<?php
require_once __DIR__ . '/../src/partials.php';
require_admin();
$pdo = db();

$productCount = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$movementCount = (int)$pdo->query('SELECT COUNT(*) FROM stock_movements')->fetchColumn();
$stockTotal = (int)$pdo->query('SELECT COALESCE(SUM(current_stock),0) FROM products')->fetchColumn();
$lastReset = $pdo->query("SELECT r.created_at, r.product_count_before, r.movement_count_before, u.full_name, u.username FROM warehouse_reset_logs r LEFT JOIN users u ON u.id=r.user_id ORDER BY r.id DESC LIMIT 1")->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $confirmation = strtoupper(trim($_POST['confirmation'] ?? ''));
    if ($confirmation !== 'RESET') {
        flash('danger', 'กรุณาพิมพ์ RESET เพื่อยืนยันการรีเซ็ตคลังสินค้า');
        header('Location:/reset.php'); exit;
    }

    try {
        $pdo->beginTransaction();
        $beforeProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $beforeMovements = (int)$pdo->query('SELECT COUNT(*) FROM stock_movements')->fetchColumn();
        $beforeStock = (int)$pdo->query('SELECT COALESCE(SUM(current_stock),0) FROM products')->fetchColumn();

        // Keep product master data and users. Remove only movement history and reset all stock balances.
        $pdo->exec('DELETE FROM stock_movements');
        $pdo->exec('ALTER SEQUENCE stock_movements_id_seq RESTART WITH 1');
        $pdo->exec('UPDATE products SET current_stock=0, updated_at=CURRENT_TIMESTAMP');

        $s = $pdo->prepare('INSERT INTO warehouse_reset_logs(user_id,product_count_before,movement_count_before,stock_total_before,created_at) VALUES(?,?,?,?,CURRENT_TIMESTAMP)');
        $s->execute([current_user_id(), $beforeProducts, $beforeMovements, $beforeStock]);
        $pdo->commit();

        flash('success', 'รีเซ็ตคลังสินค้าเรียบร้อยแล้ว ยอดคงเหลือและประวัติรับเข้า/เบิกออกถูกล้างแล้ว โดยรายการสินค้าและผู้ใช้งานยังคงอยู่');
        header('Location:/reset.php'); exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash('danger', 'รีเซ็ตไม่สำเร็จ: '.$e->getMessage());
        header('Location:/reset.php'); exit;
    }
}

page_start('รีเซ็ตคลังสินค้า');
?>
<div class="card reset-hero">
  <div class="reset-icon">⚠</div>
  <div>
    <h3>Factory Reset คลังสินค้า</h3>
    <p>ฟังก์ชันนี้สำหรับเริ่มรอบคลังใหม่ โดยจะล้างเฉพาะข้อมูลการเคลื่อนไหวและยอดคงเหลือ ไม่ลบรายการสินค้าและผู้ใช้งาน</p>
  </div>
</div>

<div class="grid reset-stats">
  <div class="card stat-card"><div class="stat-head"><span>รายการสินค้า</span><span class="stat-icon">▣</span></div><div class="metric"><?=number_format($productCount)?></div><div class="metric-sub">ยังคงรายการสินค้าไว้</div></div>
  <div class="card stat-card"><div class="stat-head"><span>ประวัติรับ/จ่าย</span><span class="stat-icon red">↕</span></div><div class="metric"><?=number_format($movementCount)?></div><div class="metric-sub">จะถูกล้างทั้งหมด</div></div>
  <div class="card stat-card"><div class="stat-head"><span>ยอดคงเหลือรวม</span><span class="stat-icon orange">▤</span></div><div class="metric"><?=number_format($stockTotal)?></div><div class="metric-sub">จะถูกรีเซ็ตเป็น 0</div></div>
  <div class="card stat-card"><div class="stat-head"><span>ผู้ใช้งาน</span><span class="stat-icon green">♙</span></div><div class="metric"><?=number_format((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn())?></div><div class="metric-sub">ไม่ถูกลบ</div></div>
</div>

<div class="card reset-card">
  <h3>ก่อนรีเซ็ต แนะนำให้สำรองข้อมูล</h3>
  <p class="muted">ดาวน์โหลดข้อมูลปัจจุบันเก็บไว้ก่อนดำเนินการ หากต้องการเก็บเป็นหลักฐานหรือใช้ตรวจสอบย้อนหลัง</p>
  <div class="actions">
    <a class="button secondary" href="/backup.php?type=products">ดาวน์โหลดรายการสินค้า CSV</a>
    <a class="button secondary" href="/backup.php?type=movements">ดาวน์โหลดประวัติรับ/จ่าย CSV</a>
  </div>
</div>

<div class="card reset-card danger-panel">
  <h3>ข้อมูลที่จะถูกล้าง</h3>
  <ul>
    <li>ประวัติการรับสินค้าและเบิกสินค้าใน <code>stock_movements</code></li>
    <li>ยอดคงเหลือของสินค้าทั้งหมดจะถูกตั้งเป็น <strong>0</strong></li>
    <li>เลขลำดับของประวัติการเคลื่อนไหวจะเริ่มใหม่ที่ 1</li>
  </ul>
  <h3>ข้อมูลที่จะไม่ถูกล้าง</h3>
  <ul>
    <li>รายการสินค้า รหัสสินค้า หน่วย และขั้นต่ำ</li>
    <li>สถานะ Active / Inactive ของสินค้า</li>
    <li>บัญชีผู้ใช้งานและสิทธิ์</li>
    <li>โครงสร้างฐานข้อมูลและโปรแกรม</li>
  </ul>
  <div class="reset-confirm-box">
    <strong>⚠ การดำเนินการนี้ไม่สามารถย้อนกลับจากหน้าเว็บได้</strong>
    <p>ก่อนกดยืนยัน ให้ดาวน์โหลดไฟล์สำรองด้านบนก่อน จากนั้นพิมพ์ <code>RESET</code> เพื่อเปิดใช้งานปุ่มรีเซ็ต</p>
    <form method="post" id="resetForm">
      <input type="hidden" name="csrf" value="<?=h(csrf())?>">
      <label>พิมพ์ RESET เพื่อยืนยัน</label>
      <input id="resetConfirm" name="confirmation" autocomplete="off" placeholder="RESET" oninput="toggleResetButton()">
      <button id="resetButton" class="danger reset-button" type="submit" disabled>⚠ รีเซ็ตคลังสินค้า</button>
    </form>
  </div>
</div>

<?php if ($lastReset): ?>
<div class="card reset-card">
  <h3>ประวัติการรีเซ็ตล่าสุด</h3>
  <div class="reset-log"><span><?=h($lastReset['created_at'])?></span><span>โดย <?=h($lastReset['full_name'] ?: $lastReset['username'] ?: '-')?></span><span>สินค้า <?=number_format((int)$lastReset['product_count_before'])?></span><span>ประวัติ <?=number_format((int)$lastReset['movement_count_before'])?></span></div>
</div>
<?php endif; ?>
<script>
function toggleResetButton(){
  const input=document.getElementById('resetConfirm');
  const btn=document.getElementById('resetButton');
  btn.disabled=input.value.trim().toUpperCase()!=='RESET';
}
document.getElementById('resetForm')?.addEventListener('submit',function(e){
  if(!confirm('ยืนยัน Factory Reset คลังสินค้า?\n\nประวัติรับเข้า/เบิกออกจะถูกลบ และยอดสินค้าจะเป็น 0\nรายการสินค้าและผู้ใช้งานจะยังคงอยู่')) e.preventDefault();
});
</script>
<?php page_end();
