<?php
require_once __DIR__ . '/../src/partials.php';
$pdo=db();
$from=$_GET['from']??date('Y-m-01');
$to=$_GET['to']??date('Y-m-d');
$allowedPerPage=[10,20,30,50,100];
$perPage=(int)($_GET['per_page']??10);
if(!in_array($perPage,$allowedPerPage,true))$perPage=10;
$count=$pdo->prepare("SELECT COUNT(*) FROM products p");
$count->execute();
$total=(int)$count->fetchColumn();
$page=max(1,(int)($_GET['page']??1));
$totalPages=max(1,(int)ceil($total/$perPage));
$page=min($page,$totalPages);
$offset=($page-1)*$perPage;
$s=$pdo->prepare("SELECT p.code,p.name,p.unit,p.current_stock,p.min_stock,COALESCE(SUM(CASE WHEN m.movement_type='IN' THEN m.quantity ELSE 0 END),0) qty_in,COALESCE(SUM(CASE WHEN m.movement_type='OUT' THEN m.quantity ELSE 0 END),0) qty_out FROM products p LEFT JOIN stock_movements m ON m.product_id=p.id AND m.created_at::date BETWEEN ? AND ? GROUP BY p.id ORDER BY p.name LIMIT ? OFFSET ?");
$s->execute([$from,$to,$perPage,$offset]);
$rows=$s->fetchAll();
$page_start=$total>0?$offset+1:0;
$page_end=min($offset+$perPage,$total);
$queryBase='from='.rawurlencode($from).'&to='.rawurlencode($to).'&per_page='.$perPage;
page_start('รายงานสรุป');
?>
<div class="card"><form class="actions"><label>จาก</label><input type="date" name="from" value="<?=h($from)?>"><label>ถึง</label><input type="date" name="to" value="<?=h($to)?>"><input type="hidden" name="page" value="1"><input type="hidden" name="per_page" value="<?=$perPage?>"><button class="primary">ดูรายงาน</button><a class="button success" href="/export.php?type=xlsx&from=<?=h($from)?>&to=<?=h($to)?>">Export Excel</a><a class="button danger" href="/export.php?type=pdf&from=<?=h($from)?>&to=<?=h($to)?>" target="_blank">Export PDF</a><?php if(is_admin()): ?><a class="button warning" href="/reset.php">คืนค่าโรงงาน</a><?php endif; ?></form></div>
<?php if(is_admin()): ?><div class="card" style="margin-top:16px"><div class="actions" style="justify-content:space-between;align-items:center"><div><strong>คืนค่าโรงงานข้อมูลคลัง</strong><div class="muted">ล้างประวัติรับเข้า/เบิกออก และรีเซ็ตยอดคงเหลือเป็น 0 โดยไม่ลบสินค้าและผู้ใช้งาน</div></div><a class="button warning" href="/reset.php">เปิดหน้าคืนค่าโรงงาน</a></div></div><?php endif; ?>
<div class="table-wrap"><table><tr><th>รหัส</th><th>สินค้า</th><th>หน่วย</th><th>คงเหลือ</th><th>เข้า</th><th>ออก</th><th>ขั้นต่ำ</th></tr><?php foreach($rows as $r):?><tr><td><?=h($r['code'])?></td><td><?=h($r['name'])?></td><td><?=h($r['unit'])?></td><td><?=number_format($r['current_stock'])?></td><td><?=number_format($r['qty_in'])?></td><td><?=number_format($r['qty_out'])?></td><td><?=number_format($r['min_stock'])?></td></tr><?php endforeach;?></table></div>
<?php if($total>0): ?>
<div class="card" style="margin-top:18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
  <div style="color:#64748b;font-size:14px">แสดงรายการ <?=number_format($page_start)?>–<?=number_format($page_end)?> จากทั้งหมด <?=number_format($total)?> รายการ</div>
  <form class="actions" method="get" style="margin:0"><input type="hidden" name="from" value="<?=h($from)?>"><input type="hidden" name="to" value="<?=h($to)?>"><input type="hidden" name="page" value="1"><label style="margin:0">แสดงต่อหน้า</label><select name="per_page" onchange="this.form.submit()"><?php foreach([10,20,30,50,100] as $n): ?><option value="<?=$n?>" <?=$perPage===$n?'selected':''?>><?=$n?> รายการ</option><?php endforeach; ?></select></form>
  <div class="actions" style="margin:0"><?php if($page>1): ?><a class="button secondary" href="/reports.php?<?=$queryBase?>&page=1">« หน้าแรก</a><a class="button secondary" href="/reports.php?<?=$queryBase?>&page=<?=$page-1?>">‹ ก่อนหน้า</a><?php else: ?><button class="button secondary" type="button" disabled>« หน้าแรก</button><button class="button secondary" type="button" disabled>‹ ก่อนหน้า</button><?php endif; ?><span class="button secondary" style="cursor:default">หน้า <?=$page?> / <?=$totalPages?></span><?php if($page<$totalPages): ?><a class="button primary" href="/reports.php?<?=$queryBase?>&page=<?=$page+1?>">ถัดไป ›</a><a class="button primary" href="/reports.php?<?=$queryBase?>&page=<?=$totalPages?>">หน้าสุดท้าย »</a><?php else: ?><button class="button primary" type="button" disabled>ถัดไป ›</button><button class="button primary" type="button" disabled>หน้าสุดท้าย »</button><?php endif; ?></div>
</div>
<?php else: ?><div class="card" style="margin-top:18px;text-align:center;color:#64748b">ไม่พบรายการสินค้า</div><?php endif; ?>
<?php page_end(); ?>