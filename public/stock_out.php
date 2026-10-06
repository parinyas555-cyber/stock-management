<?php
require_once __DIR__ . '/../src/partials.php';
$pdo=db();
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf(); $pid=(int)($_POST['product_id']??0); $qty=(int)($_POST['quantity']??0);
 try{
  $pdo->beginTransaction();
  $s=$pdo->prepare('SELECT current_stock,active FROM products WHERE id=? FOR UPDATE');$s->execute([$pid]);$row=$s->fetch();$stock=$row['current_stock']??false;
  if($stock===false||!filter_var($row['active']??false,FILTER_VALIDATE_BOOLEAN)||$qty<1||$stock<$qty) throw new Exception($stock===false?'ไม่พบสินค้า':'สินค้านี้ถูกปิดใช้งานหรือมีจำนวนไม่เพียงพอสำหรับการเบิก');
  $s=$pdo->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,reference_no,note,user_id) VALUES(?,'OUT',?,?,?,?)");
  $s->execute([$pid,$qty,trim($_POST['reference_no']??''),trim($_POST['note']??''),$_SESSION['user']['id']]);
  $s=$pdo->prepare('UPDATE products SET current_stock=current_stock-? WHERE id=?');$s->execute([$qty,$pid]);
  $pdo->commit(); flash('success','เบิกสินค้าออกจากสต๊อกแล้ว'); header('Location:/stock_out.php'); exit;
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash('danger',$e->getMessage());}
}
$products=$pdo->query("SELECT id,code,name,unit,current_stock,image_mime FROM products WHERE active=TRUE ORDER BY name")->fetchAll();
page_start('สินค้าออก');
?>
<div class="stock-entry-layout">
 <div class="card form-card stock-entry-form">
  <h3>บันทึกสินค้าออก</h3>
  <form method="post" id="stockOutForm">
   <input type="hidden" name="csrf" value="<?=h(csrf())?>">
   <input type="hidden" name="product_id" id="stockOutProductId" required>
   <label>ค้นหา / เลือกสินค้า</label>
   <div class="product-search-box">
    <input type="search" id="stockOutSearch" autocomplete="off" placeholder="พิมพ์ชื่อสินค้า หรือรหัสสินค้า...">
    <div class="product-search-list" id="stockOutList">
     <?php foreach($products as $p): ?>
      <button type="button" class="product-search-item" data-id="<?=h($p['id'])?>" data-name="<?=h($p['name'])?>" data-code="<?=h($p['code'])?>" data-unit="<?=h($p['unit'])?>" data-stock="<?=h($p['current_stock'])?>" data-has-image="<?=!empty($p['image_mime'])?'1':'0'?>">
       <span><strong><?=h($p['name'])?></strong><small><?=h($p['code'])?></small></span><span class="product-search-stock">คงเหลือ <?=number_format($p['current_stock'])?></span>
      </button>
     <?php endforeach; ?>
     <div class="product-search-empty" id="stockOutEmpty" style="display:none">ไม่พบสินค้าที่ค้นหา</div>
    </div>
   </div>
   <div class="selected-product-inline" id="stockOutSelectedText">ยังไม่ได้เลือกสินค้า</div>
   <div class="form-grid">
    <div><label>จำนวน</label><input type="number" name="quantity" min="1" required></div>
    <div><label>เลขที่อ้างอิง</label><input name="reference_no"></div>
   </div>
   <label>หมายเหตุ</label><textarea name="note"></textarea>
   <button class="primary">บันทึกสินค้าออก</button>
  </form>
 </div>
 <div class="card selected-product-card stock-selected-card">
  <h3>รูปสินค้าที่เลือก</h3>
  <div id="stockOutImageEmpty" class="selected-product-empty">เลือกสินค้าเพื่อแสดงรูป</div>
  <div id="stockOutImageContent" style="display:none">
   <div class="selected-product-image"><img id="stockOutImage" src="" alt="รูปสินค้าที่เลือก"></div>
   <div class="selected-product-name" id="stockOutImageName"></div>
   <div class="selected-product-code" id="stockOutImageCode"></div>
  </div>
 </div>
</div>
<script>
(function(){
 const input=document.getElementById('stockOutSearch'), hidden=document.getElementById('stockOutProductId'), list=document.getElementById('stockOutList'), empty=document.getElementById('stockOutEmpty'), selectedText=document.getElementById('stockOutSelectedText');
 const imgEmpty=document.getElementById('stockOutImageEmpty'), imgContent=document.getElementById('stockOutImageContent'), img=document.getElementById('stockOutImage'), imgName=document.getElementById('stockOutImageName'), imgCode=document.getElementById('stockOutImageCode');
 function selectProduct(btn){
  document.querySelectorAll('#stockOutList .product-search-item.selected').forEach(x=>x.classList.remove('selected')); btn.classList.add('selected');
  hidden.value=btn.dataset.id; input.value=btn.dataset.name+' ('+btn.dataset.code+')'; selectedText.textContent='สินค้าที่เลือก: '+btn.dataset.code+' - '+btn.dataset.name+' • คงเหลือ '+btn.dataset.stock;
  imgContent.style.display='none'; imgEmpty.style.display='flex'; img.removeAttribute('src'); img.onload=null; img.onerror=null;
  imgName.textContent=btn.dataset.name||''; imgCode.textContent='รหัสสินค้า: '+(btn.dataset.code||'');
  if(btn.dataset.hasImage==='1'){
   imgEmpty.style.display='none'; imgContent.style.display='block';
   img.onload=()=>{img.style.display='block';}; img.onerror=()=>{imgContent.style.display='none';imgEmpty.textContent='ไม่สามารถแสดงรูปภาพสินค้าได้';imgEmpty.style.display='flex';};
   img.src='/image.php?id='+encodeURIComponent(btn.dataset.id)+'&v='+Date.now();
  }else{imgEmpty.textContent='สินค้านี้ยังไม่มีรูปภาพ';}
 }
 function filter(){const q=input.value.trim().toLowerCase();let count=0;document.querySelectorAll('#stockOutList .product-search-item').forEach(btn=>{const hay=(btn.dataset.name+' '+btn.dataset.code).toLowerCase();const show=!q||hay.includes(q);btn.style.display=show?'flex':'none';if(show)count++;});empty.style.display=count?'none':'block';list.classList.add('open');}
 input.addEventListener('focus',filter); input.addEventListener('input',filter);
 document.querySelectorAll('#stockOutList .product-search-item').forEach(btn=>btn.addEventListener('click',()=>{selectProduct(btn);list.classList.remove('open');}));
 document.getElementById('stockOutForm').addEventListener('submit',e=>{if(!hidden.value){e.preventDefault();alert('กรุณาเลือกสินค้า');input.focus();}});
 document.addEventListener('click',e=>{if(!e.target.closest('.product-search-box'))list.classList.remove('open');});
})();
</script>
<?php page_end();
