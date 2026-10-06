<?php
require_once __DIR__ . '/../src/partials.php';
$pdo=db();
$from=$_GET['from']??date('Y-m-01');
$to=$_GET['to']??date('Y-m-d');
$allowedPerPage=[10,20,30,50,100];
$perPage=(int)($_GET['per_page']??10);
if(!in_array($perPage,$allowedPerPage,true))$perPage=10;
$count=$pdo->prepare("SELECT COUNT(*) FROM stock_movements m JOIN products p ON p.id=m.product_id LEFT JOIN users u ON u.id=m.user_id WHERE m.created_at::date BETWEEN ? AND ?");
$count->execute([$from,$to]);
$total=(int)$count->fetchColumn();
$page=max(1,(int)($_GET['page']??1));
$totalPages=max(1,(int)ceil($total/$perPage));
$page=min($page,$totalPages);
$offset=($page-1)*$perPage;
$s=$pdo->prepare("SELECT m.*,p.code,p.name,u.full_name FROM stock_movements m JOIN products p ON p.id=m.product_id LEFT JOIN users u ON u.id=m.user_id WHERE m.created_at::date BETWEEN ? AND ? ORDER BY m.created_at DESC LIMIT ? OFFSET ?");
$s->execute([$from,$to,$perPage,$offset]);
$rows=$s->fetchAll();
$page_start=$total>0?$offset+1:0;
$page_end=min($offset+$perPage,$total);
$queryBase='from='.rawurlencode($from).'&to='.rawurlencode($to).'&per_page='.$perPage;
page_start('รายการเคลื่อนไหวล่าสุด');
?>
<div class="card"><form class="actions"><label>จาก</label><input type="date" name="from" value="<?=h($from)?>"><label>ถึง</label><input type="date" name="to" value="<?=h($to)?>"><input type="hidden" name="page" value="1"><input type="hidden" name="per_page" value="<?=$perPage?>"><button class="primary">กรอง</button></form></div>
<div class="table-wrap"><table><tr><th>วันที่</th><th>ประเภท</th><th>รหัส</th><th>สินค้า</th><th>จำนวน</th><th>อ้างอิง</th><th>ผู้ทำรายการ</th></tr><?php foreach($rows as $r):?><tr><td><?=h($r['created_at'])?></td><td><?=h($r['movement_type']==='IN'?'สินค้าเข้า':'สินค้าออก')?></td><td><?=h($r['code'])?></td><td><?=h($r['name'])?></td><td><?=h($r['quantity'])?></td><td><?=h($r['reference_no'])?></td><td><?=h($r['full_name'])?></td></tr><?php endforeach;?></table></div>
<?php if($total>0): ?>
<div class="card" style="margin-top:18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
  <div style="color:#64748b;font-size:14px">แสดงรายการ <?=number_format($page_start)?>–<?=number_format($page_end)?> จากทั้งหมด <?=number_format($total)?> รายการ</div>
  <form class="actions" method="get" style="margin:0">
    <input type="hidden" name="from" value="<?=h($from)?>"><input type="hidden" name="to" value="<?=h($to)?>"><input type="hidden" name="page" value="1">
    <label style="margin:0">แสดงต่อหน้า</label><select name="per_page" onchange="this.form.submit()"><?php foreach([10,20,30,50,100] as $n): ?><option value="<?=$n?>" <?=$perPage===$n?'selected':''?>><?=$n?> รายการ</option><?php endforeach; ?></select>
  </form>
  <div class="actions" style="margin:0">
    <?php if($page>1): ?><a class="button secondary" href="/movements.php?<?=$queryBase?>&page=<?=$page-1?>">‹ ก่อนหน้า</a><?php else: ?><button class="button secondary" type="button" disabled>‹ ก่อนหน้า</button><?php endif; ?>
    <span class="button secondary" style="cursor:default">หน้า <?=$page?> / <?=$totalPages?></span>
    <?php if($page<$totalPages): ?><a class="button primary" href="/movements.php?<?=$queryBase?>&page=<?=$page+1?>">ถัดไป ›</a><?php else: ?><button class="button primary" type="button" disabled>ถัดไป ›</button><?php endif; ?>
  </div>
</div>
<?php else: ?><div class="card" style="margin-top:18px;text-align:center;color:#64748b">ไม่พบรายการเคลื่อนไหวในช่วงวันที่ที่เลือก</div><?php endif; ?>
<?php page_end(); ?>