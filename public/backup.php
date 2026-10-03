<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_admin();
$pdo = db();
$type = $_GET['type'] ?? 'products';
if (!in_array($type, ['products','movements'], true)) { http_response_code(400); exit('Invalid backup type'); }
$filename = 'stock_management_' . $type . '_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
$out = fopen('php://output','w');
fwrite($out, "\xEF\xBB\xBF");
if ($type === 'products') {
    fputcsv($out, ['ID','รหัสสินค้า','ชื่อสินค้า','หน่วย','คงเหลือ','ขั้นต่ำ','สถานะ','สร้างเมื่อ','แก้ไขเมื่อ']);
    $q = $pdo->query('SELECT id,code,name,unit,current_stock,min_stock,active,created_at,updated_at FROM products ORDER BY id');
    while ($r = $q->fetch(PDO::FETCH_ASSOC)) fputcsv($out, [$r['id'],$r['code'],$r['name'],$r['unit'],$r['current_stock'],$r['min_stock'],$r['active']?'ใช้งาน':'ปิดใช้งาน',$r['created_at'],$r['updated_at']]);
} else {
    fputcsv($out, ['ID','รหัสสินค้า','ชื่อสินค้า','ประเภท','จำนวน','เลขที่อ้างอิง','หมายเหตุ','ผู้ทำรายการ','วันที่']);
    $q = $pdo->query("SELECT m.id,p.code,p.name,m.movement_type,m.quantity,m.reference_no,m.note,COALESCE(u.full_name,u.username,'-') AS user_name,m.created_at FROM stock_movements m JOIN products p ON p.id=m.product_id LEFT JOIN users u ON u.id=m.user_id ORDER BY m.id");
    while ($r = $q->fetch(PDO::FETCH_ASSOC)) fputcsv($out, [$r['id'],$r['code'],$r['name'],$r['movement_type']==='IN'?'รับเข้า':'เบิกออก',$r['quantity'],$r['reference_no'],$r['note'],$r['user_name'],$r['created_at']]);
}
fclose($out); exit;
