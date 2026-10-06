<?php require_once __DIR__ . '/../src/partials.php'; $pdo=db();
if($_SERVER['REQUEST_METHOD']==='POST'){ check_csrf(); $action=$_POST['action']??''; try {
 if($action==='save') {
  $id=(int)($_POST['id']??0); $code=trim($_POST['code']??''); $name=trim($_POST['name']??''); $unit=trim($_POST['unit']??'pcs'); $min=max(0,(int)($_POST['min_stock']??0));
  if(!$code||!$name) throw new Exception('กรุณากรอกรหัสและชื่อสินค้า');
  $imageData=null; $imageMime=null; $hasImage=isset($_FILES['image']) && ($_FILES['image']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;
  if($hasImage){
    if($_FILES['image']['error']!==UPLOAD_ERR_OK) throw new Exception('ไม่สามารถอัปโหลดรูปภาพได้');
    if((int)$_FILES['image']['size']>2*1024*1024) throw new Exception('รูปภาพต้องมีขนาดไม่เกิน 2 MB');
    $raw=file_get_contents($_FILES['image']['tmp_name']); if($raw===false) throw new Exception('ไม่สามารถอ่านรูปภาพได้');
    $info=@getimagesizefromstring($raw); if(!$info || empty($info['mime']) || !in_array($info['mime'],['image/jpeg','image/png','image/gif','image/webp'],true)) throw new Exception('รองรับเฉพาะ JPG, PNG, GIF หรือ WebP');
    $src=@imagecreatefromstring($raw); if(!$src) throw new Exception('รูปภาพไม่ถูกต้องหรือไม่รองรับ');
    $w=imagesx($src); $h=imagesy($src); $max=800; $scale=min(1,$max/max($w,$h)); $nw=max(1,(int)round($w*$scale)); $nh=max(1,(int)round($h*$scale));
    $dst=imagecreatetruecolor($nw,$nh); imagealphablending($dst,false); imagesavealpha($dst,true); $transparent=imagecolorallocatealpha($dst,255,255,255,127); imagefilledrectangle($dst,0,0,$nw,$nh,$transparent); imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$w,$h);
    ob_start(); if(function_exists('imagewebp')){ imagewebp($dst,null,82); $imageMime='image/webp'; } else { imagejpeg($dst,null,82); $imageMime='image/jpeg'; } $imageData=ob_get_clean(); imagedestroy($src); imagedestroy($dst);
    if(!$imageData) throw new Exception('ไม่สามารถประมวลผลรูปภาพได้');
  }
  if($id){
    if($hasImage){$s=$pdo->prepare('UPDATE products SET code=?,name=?,unit=?,min_stock=?,image_data=?,image_mime=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');$s->bindValue(1,$code);$s->bindValue(2,$name);$s->bindValue(3,$unit);$s->bindValue(4,$min,PDO::PARAM_INT);$s->bindValue(5,$imageData,PDO::PARAM_LOB);$s->bindValue(6,$imageMime);$s->bindValue(7,$id,PDO::PARAM_INT);$s->execute();}
    elseif(isset($_POST['remove_image'])){$s=$pdo->prepare('UPDATE products SET code=?,name=?,unit=?,min_stock=?,image_data=NULL,image_mime=NULL,updated_at=CURRENT_TIMESTAMP WHERE id=?');$s->execute([$code,$name,$unit,$min,$id]);}
    else{$s=$pdo->prepare('UPDATE products SET code=?,name=?,unit=?,min_stock=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');$s->execute([$code,$name,$unit,$min,$id]);}
    flash('success','แก้ไขข้อมูลสินค้าแล้ว');
  }else{
    if($hasImage){$s=$pdo->prepare('INSERT INTO products(code,name,unit,min_stock,image_data,image_mime) VALUES(?,?,?,?,?,?)');$s->bindValue(1,$code);$s->bindValue(2,$name);$s->bindValue(3,$unit);$s->bindValue(4,$min,PDO::PARAM_INT);$s->bindValue(5,$imageData,PDO::PARAM_LOB);$s->bindValue(6,$imageMime);$s->execute();}
    else{$s=$pdo->prepare('INSERT INTO products(code,name,unit,min_stock) VALUES(?,?,?,?)');$s->execute([$code,$name,$unit,$min]);}
    flash('success','เพิ่มสินค้าแล้ว');
  }
}
 elseif($action==='delete') { require_admin(); $id=(int)$_POST['id']; $pdo->beginTransaction(); try { $s=$pdo->prepare('SELECT id,code,name FROM products WHERE id=? FOR UPDATE');$s->execute([$id]);$product=$s->fetch(); if(!$product) throw new Exception('ไม่พบสินค้าที่ต้องการลบ'); $s=$pdo->prepare('DELETE FROM stock_movements WHERE product_id=?');$s->execute([$id]); $s=$pdo->prepare('DELETE FROM products WHERE id=?');$s->execute([$id]); $pdo->commit(); flash('success','ลบสินค้า "'.($product['code'].' - '.$product['name']).'" และประวัติการเคลื่อนไหวของสินค้านี้แล้ว'); } catch(Throwable $e) { if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }}
 elseif($action==='toggle') { require_admin(); $id=(int)$_POST['id']; $s=$pdo->prepare('UPDATE products SET active=NOT active,updated_at=CURRENT_TIMESTAMP WHERE id=?');$s->execute([$id]);$s=$pdo->prepare('SELECT active FROM products WHERE id=?');$s->execute([$id]);$active=$s->fetchColumn();flash('success',$active?'เปิดใช้งานสินค้าแล้ว':'ปิดใช้งานสินค้าแล้ว');}
 header('Location:/products.php'.(!empty($_POST['show'])?'?show='.rawurlencode($_POST['show']):'')); exit;
} catch(Throwable $e){flash('danger','ดำเนินการไม่สำเร็จ: '.$e->getMessage());header('Location:/products.php');exit;}}
$edit=null;if(isset($_GET['edit'])){$s=$pdo->prepare('SELECT * FROM products WHERE id=?');$s->execute([(int)$_GET['edit']]);$edit=$s->fetch();}
$q=trim($_GET['q']??'');$show=$_GET['show']??'active';
$where=[];$params=[];if($show==='active'){$where[]='active=TRUE';}elseif($show==='inactive'){$where[]='active=FALSE';}if($q!==''){$where[]='(code ILIKE ? OR name ILIKE ?)';$params[]='%'.$q.'%';$params[]='%'.$q.'%';}$countSql='SELECT COUNT(*) FROM products'.($where?' WHERE '.implode(' AND ',$where):'');$cs=$pdo->prepare($countSql);$cs->execute($params);$total=(int)$cs->fetchColumn();$allowedPerPage=[10,20,30,50,100];$perPage=(int)($_GET['per_page']??10);if(!in_array($perPage,$allowedPerPage,true))$perPage=10;$page=max(1,(int)($_GET['page']??1));$totalPages=max(1,(int)ceil($total/$perPage));$page=min($page,$totalPages);$offset=($page-1)*$perPage;$sql='SELECT * FROM products'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY active DESC,name LIMIT '.$perPage.' OFFSET '.$offset;$s=$pdo->prepare($sql);$s->execute($params);$items=$s->fetchAll();$page_start=$total>0?$offset+1:0;$page_end=min($offset+$perPage,$total);$queryBase='show='.rawurlencode($show).($q!==''?'&q='.rawurlencode($q):'').'&per_page='.$perPage;page_start('คลังสินค้า');
?><div class="card"><form class="actions"><input type="hidden" name="show" value="<?=h($show)?>"><input name="q" placeholder="ค้นหาด้วยรหัสหรือชื่อสินค้า" value="<?=h($q)?>"><button class="primary">ค้นหา</button><?php if($q):?><a class="button secondary" href="/products.php?show=<?=h($show)?>">ล้างค้นหา</a><?php endif;?><a class="button <?= $show==='active'?'success':'secondary'?>" href="/products.php?show=active">ใช้งาน</a><a class="button <?= $show==='inactive'?'warning':'secondary'?>" href="/products.php?show=inactive">ปิดใช้งาน</a><a class="button secondary" href="/products.php?show=all">ทั้งหมด</a></form></div>
<div class="product-form-layout">
<div class="card form-card" style="margin-top:18px"><h3><?= $edit?'แก้ไขสินค้า':'เพิ่มสินค้า'?></h3><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=h($edit['id']??0)?>"><div class="form-grid"><div><label>รหัสสินค้า</label><input name="code" value="<?=h($edit['code']??'')?>" required></div><div><label>ชื่อสินค้า</label><input name="name" value="<?=h($edit['name']??'')?>" required></div><div><label>หน่วย</label><input name="unit" value="<?=h($edit['unit']??'pcs')?>"></div><div><label>สต๊อกขั้นต่ำ</label><input type="number" name="min_stock" value="<?=h($edit['min_stock']??500)?>" min="0"></div></div><div class="product-image-editor"><div class="product-image-preview" id="imagePreviewWrap"><?php if(!empty($edit['image_mime'])):?><img id="imagePreview" src="/image.php?id=<?=h($edit['id'])?>" alt="รูปสินค้า"><?php else:?><div id="imagePlaceholder">ไม่มีรูปสินค้า</div><img id="imagePreview" alt="ตัวอย่างรูปสินค้า" style="display:none"><?php endif;?></div><div style="flex:1;min-width:220px"><label>รูปสินค้า</label><input id="productImage" type="file" name="image" accept="image/*"><div class="muted" style="margin-top:7px">คอมพิวเตอร์: เลือกไฟล์จากเครื่อง • มือถือ: สามารถถ่ายรูปหรือเลือกจากแกลลอรี่ได้</div><div class="muted" style="margin-top:4px">รองรับ JPG, PNG, GIF, WebP และขนาดไม่เกิน 2 MB ระบบจะย่อภาพให้อัตโนมัติ</div><?php if($edit && !empty($edit['image_mime'])):?><label class="check" style="margin-top:10px"><input type="checkbox" name="remove_image"> ลบรูปเดิม</label><?php endif;?></div></div><button class="primary"><?= $edit?'บันทึกการแก้ไข':'เพิ่มสินค้า'?></button><?php if($edit):?><a class="button secondary" href="/products.php?show=<?=h($show)?>">ยกเลิก</a><?php endif;?></form></div>
<div class="card selected-product-card" style="margin-top:18px"><h3>รูปสินค้าที่เลือก</h3><div id="selectedProductEmpty" class="selected-product-empty">คลิกที่รายการสินค้าในตารางเพื่อแสดงรูป</div><div id="selectedProductContent" style="display:none"><div class="selected-product-image"><img id="selectedProductImage" src="" alt="รูปสินค้าที่เลือก"></div><div class="selected-product-name" id="selectedProductName"></div><div class="selected-product-code" id="selectedProductCode"></div></div></div>
</div>
<div class="table-wrap"><table><tr><th>รหัส</th><th>สินค้า</th><th>หน่วย</th><th>คงเหลือ</th><th>ขั้นต่ำ</th><th>สถานะ</th><th>จัดการ</th></tr><?php foreach($items as $x):?><tr class="product-row" data-product-id="<?=h($x['id'])?>" data-product-name="<?=h($x['name'])?>" data-product-code="<?=h($x['code'])?>" data-has-image="<?=!empty($x['image_mime'])?'1':'0'?>"><td><?=h($x['code'])?></td><td><?=h($x['name'])?></td><td><?=h($x['unit'])?></td><td><?=number_format($x['current_stock'])?></td><td><?=number_format($x['min_stock'])?></td><td><?php if(!$x['active']):?><span class="badge secondary-bg">ปิดใช้งาน</span><?php elseif($x['current_stock']<=$x['min_stock']):?><span class="badge danger-bg">ต่ำกว่าขั้นต่ำ</span><?php else:?><span class="badge success-bg">ปกติ</span><?php endif;?></td><td class="actions"><a class="button secondary" href="?edit=<?=h($x['id'])?>&show=<?=h($show)?>">แก้ไข</a><?php if(is_admin()):?><form method="post" onsubmit="return confirm('<?= $x['active']?'ยืนยันการปิดใช้งานสินค้า?':'ยืนยันการเปิดใช้งานสินค้า?' ?>')"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=h($x['id'])?>"><input type="hidden" name="show" value="<?=h($show)?>"><button class="button <?= $x['active']?'warning':'success'?>" type="submit"><?= $x['active']?'ปิดใช้งาน':'เปิดใช้งาน'?></button></form><form method="post" onsubmit="return confirm('ยืนยันการลบสินค้า?\n\nสินค้าและประวัติรับเข้า/เบิกออกของสินค้านี้จะถูกลบถาวร และไม่สามารถกู้คืนได้')"><input type="hidden" name="csrf" value="<?=h(csrf())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=h($x['id'])?>"><input type="hidden" name="show" value="<?=h($show)?>"><button class="button danger" type="submit">ลบถาวร</button></form><?php endif;?></td></tr><?php endforeach;?></table></div>
<?php if($total>0): ?>
<div class="card" style="margin-top:18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
  <div style="color:#64748b;font-size:14px">แสดงรายการ <?=number_format($page_start)?>–<?=number_format($page_end)?> จากทั้งหมด <?=number_format($total)?> รายการ</div>
  <form class="actions" method="get" style="margin:0">
    <input type="hidden" name="show" value="<?=h($show)?>">
    <?php if($q!==''): ?><input type="hidden" name="q" value="<?=h($q)?>"><?php endif; ?>
    <input type="hidden" name="page" value="1">
    <label style="margin:0">แสดงต่อหน้า</label>
    <select name="per_page" onchange="this.form.submit()">
      <?php foreach([10,20,30,50,100] as $n): ?><option value="<?=$n?>" <?=$perPage===$n?'selected':''?>><?=$n?> รายการ</option><?php endforeach; ?>
    </select>
  </form>
  <div class="actions" style="margin:0">
    <?php if($page>1): ?><a class="button secondary" href="/products.php?<?=$queryBase?>&page=<?=$page-1?>">‹ ก่อนหน้า</a><?php else: ?><button class="button secondary" type="button" disabled>‹ ก่อนหน้า</button><?php endif; ?>
    <span class="button secondary" style="cursor:default">หน้า <?=$page?> / <?=$totalPages?></span>
    <?php if($page<$totalPages): ?><a class="button primary" href="/products.php?<?=$queryBase?>&page=<?=$page+1?>">ถัดไป ›</a><?php else: ?><button class="button primary" type="button" disabled>ถัดไป ›</button><?php endif; ?>
  </div>
</div>
<?php else: ?>
<div class="card" style="margin-top:18px;text-align:center;color:#64748b">ไม่พบรายการสินค้า</div>
<?php endif; ?><script>
const productImageInput=document.getElementById('productImage');
const productImagePreview=document.getElementById('imagePreview');
const productImagePlaceholder=document.getElementById('imagePlaceholder');
if(productImageInput){productImageInput.addEventListener('change',()=>{const f=productImageInput.files?.[0];if(!f)return;if(f.size>2*1024*1024){alert('รูปภาพต้องมีขนาดไม่เกิน 2 MB');productImageInput.value='';return;}const u=URL.createObjectURL(f);if(productImagePreview){productImagePreview.src=u;productImagePreview.style.display='block';}if(productImagePlaceholder)productImagePlaceholder.style.display='none';});}
const selectedEmpty=document.getElementById('selectedProductEmpty');
const selectedContent=document.getElementById('selectedProductContent');
const selectedImage=document.getElementById('selectedProductImage');
const selectedName=document.getElementById('selectedProductName');
const selectedCode=document.getElementById('selectedProductCode');
function selectProductRow(row){
 document.querySelectorAll('.product-row.selected').forEach(r=>r.classList.remove('selected'));
 row.classList.add('selected');
 selectedName.textContent=row.dataset.productName||'';
 selectedCode.textContent='รหัสสินค้า: '+(row.dataset.productCode||'');
 if(row.dataset.hasImage==='1'){selectedImage.src='/image.php?id='+encodeURIComponent(row.dataset.productId);selectedImage.style.display='block';selectedEmpty.style.display='none';selectedContent.style.display='block';}
 else{selectedImage.removeAttribute('src');selectedImage.style.display='none';selectedContent.style.display='block';selectedEmpty.textContent='สินค้านี้ยังไม่มีรูปภาพ';selectedEmpty.style.display='block';}
}
document.querySelectorAll('.product-row').forEach(row=>row.addEventListener('click',e=>{if(e.target.closest('a,button,input,select,textarea,form'))return;selectProductRow(row);}));
</script><?php page_end();
