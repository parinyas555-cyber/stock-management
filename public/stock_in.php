<?php
require_once __DIR__ . '/../src/partials.php';
$pdo=db();
if($_SERVER['REQUEST_METHOD']==='POST'){
 check_csrf(); $pid=(int)($_POST['product_id']??0); $qty=(int)($_POST['quantity']??0);
 try{
  $pdo->beginTransaction();
  $check=$pdo->prepare('SELECT active FROM products WHERE id=? FOR UPDATE');$check->execute([$pid]);$active=$check->fetchColumn();
  if($active===false||!filter_var($active,FILTER_VALIDATE_BOOLEAN)||$qty<1) throw new Exception('ไม่พบสินค้าหรือสินค้านี้ถูกปิดใช้งาน');
  $s=$pdo->prepare("INSERT INTO stock_movements(product_id,movement_type,quantity,reference_no,note,user_id) VALUES(?,'IN',?,?,?,?)");
  $s->execute([$pid,$qty,trim($_POST['reference_no']??''),trim($_POST['note']??''),$_SESSION['user']['id']]);
  $s=$pdo->prepare('UPDATE products SET current_stock=current_stock+? WHERE id=?');$s->execute([$qty,$pid]);
  $pdo->commit(); flash('success','รับสินค้าเข้าสต๊อกแล้ว'); header('Location:/stock_in.php'); exit;
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash('danger',$e->getMessage());}
}
$products=$pdo->query("SELECT id,code,name,unit,current_stock,image_mime FROM products WHERE active=TRUE ORDER BY name")->fetchAll();
page_start('สินค้าเข้า');
?>
<div class="stock-entry-layout">
 <div class="card form-card stock-entry-form">
  <h3>บันทึกสินค้าเข้า</h3>
  <form method="post" id="stockInForm">
   <input type="hidden" name="csrf" value="<?=h(csrf())?>">
   <input type="hidden" name="product_id" id="stockInProductId" required>
   <label>ค้นหา / เลือกสินค้า</label>
   <div class="product-search-box">
    <input type="search" id="stockInSearch" autocomplete="off" placeholder="พิมพ์ชื่อสินค้า หรือรหัสสินค้า...">
    <div class="product-search-list" id="stockInList">
     <?php foreach($products as $p): ?>
      <button type="button" class="product-search-item" data-id="<?=h($p['id'])?>" data-name="<?=h($p['name'])?>" data-code="<?=h($p['code'])?>" data-unit="<?=h($p['unit'])?>" data-stock="<?=h($p['current_stock'])?>" data-has-image="<?=!empty($p['image_mime'])?'1':'0'?>">
       <span><strong><?=h($p['name'])?></strong><small><?=h($p['code'])?></small></span><span class="product-search-stock">คงเหลือ <?=number_format($p['current_stock'])?></span>
      </button>
     <?php endforeach; ?>
     <div class="product-search-empty" id="stockInEmpty" style="display:none">ไม่พบสินค้าที่ค้นหา</div>
     <div class="product-search-pagination" id="stockInPagination" style="display:none"></div>
    </div>
   </div>
   <div class="selected-product-inline" id="stockInSelectedText">ยังไม่ได้เลือกสินค้า</div>
   <div class="form-grid">
    <div><label>จำนวน</label><input type="number" name="quantity" min="1" required></div>
    <div><label>เลขที่อ้างอิง</label><input name="reference_no"></div>
   </div>
   <label>หมายเหตุ</label><textarea name="note"></textarea>
   <button class="primary">บันทึกสินค้าเข้า</button>
  </form>
 </div>
 <div class="card selected-product-card stock-selected-card">
  <h3>รูปสินค้าที่เลือก</h3>
  <div id="stockInImageEmpty" class="selected-product-empty">เลือกสินค้าเพื่อแสดงรูป</div>
  <div id="stockInImageContent" style="display:none">
   <div class="selected-product-image"><img id="stockInImage" src="" alt="รูปสินค้าที่เลือก"></div>
   <div class="selected-product-name" id="stockInImageName"></div>
   <div class="selected-product-code" id="stockInImageCode"></div>
  </div>
 </div>
</div>
<script>
(function(){
 const input=document.getElementById('stockInSearch'), hidden=document.getElementById('stockInProductId'), list=document.getElementById('stockInList'), empty=document.getElementById('stockInEmpty'), pagination=document.getElementById('stockInPagination'), selectedText=document.getElementById('stockInSelectedText');
 const imgEmpty=document.getElementById('stockInImageEmpty'), imgContent=document.getElementById('stockInImageContent'), img=document.getElementById('stockInImage'), imgName=document.getElementById('stockInImageName'), imgCode=document.getElementById('stockInImageCode');
 const allItems=Array.from(document.querySelectorAll('#stockInList .product-search-item'));
 const pageSize=5; let filteredItems=allItems.slice(), currentPage=1;
 function selectProduct(btn){
  document.querySelectorAll('#stockInList .product-search-item.selected').forEach(x=>x.classList.remove('selected')); btn.classList.add('selected');
  hidden.value=btn.dataset.id; input.value=btn.dataset.name+' ('+btn.dataset.code+')'; selectedText.textContent='สินค้าที่เลือก: '+btn.dataset.code+' - '+btn.dataset.name;
  imgContent.style.display='none'; imgEmpty.style.display='flex'; img.removeAttribute('src'); img.onload=null; img.onerror=null;
  imgName.textContent=btn.dataset.name||''; imgCode.textContent='รหัสสินค้า: '+(btn.dataset.code||'');
  if(btn.dataset.hasImage==='1'){
   imgEmpty.style.display='none'; imgContent.style.display='block';
   img.onload=()=>{img.style.display='block';}; img.onerror=()=>{imgContent.style.display='none';imgEmpty.textContent='ไม่สามารถแสดงรูปภาพสินค้าได้';imgEmpty.style.display='flex';};
   img.src='/image.php?id='+encodeURIComponent(btn.dataset.id)+'&v='+Date.now();
  }else{imgEmpty.textContent='สินค้านี้ยังไม่มีรูปภาพ';}
 }
 function renderPage(){
  const totalPages=Math.max(1,Math.ceil(filteredItems.length/pageSize)); currentPage=Math.min(currentPage,totalPages);
  allItems.forEach(btn=>btn.style.display='none');
  const start=(currentPage-1)*pageSize;
  filteredItems.slice(start,start+pageSize).forEach(btn=>btn.style.display='flex');
  empty.style.display=filteredItems.length?'none':'block';
  if(filteredItems.length>pageSize){
   pagination.style.display='flex';
   pagination.innerHTML='<span>หน้า '+currentPage+' / '+totalPages+'</span><div class="actions" style="margin:0;gap:6px">'+
    (currentPage>1?'<button type="button" class="button secondary" data-page="'+(currentPage-1)+'">‹ ก่อนหน้า</button>':'<button type="button" class="button secondary" disabled>‹ ก่อนหน้า</button>')+
    (currentPage<totalPages?'<button type="button" class="button primary" data-page="'+(currentPage+1)+'">ถัดไป ›</button>':'<button type="button" class="button primary" disabled>ถัดไป ›</button>')+'</div>';
   pagination.querySelectorAll('[data-page]').forEach(b=>b.addEventListener('click',()=>{currentPage=Number(b.dataset.page);renderPage();}));
  }else{pagination.style.display='none';pagination.innerHTML='';}
 }
 function filter(){const q=input.value.trim().toLowerCase();filteredItems=allItems.filter(btn=>{const hay=(btn.dataset.name+' '+btn.dataset.code).toLowerCase();return !q||hay.includes(q);});currentPage=1;renderPage();list.classList.add('open');}
 input.addEventListener('focus',filter); input.addEventListener('input',filter);
 allItems.forEach(btn=>btn.addEventListener('click',()=>{selectProduct(btn);list.classList.remove('open');}));
 document.getElementById('stockInForm').addEventListener('submit',e=>{if(!hidden.value){e.preventDefault();alert('กรุณาเลือกสินค้า');input.focus();}});
 document.addEventListener('click',e=>{if(!e.target.closest('.product-search-box'))list.classList.remove('open');});
 renderPage();
})();
</script>
<?php page_end();
