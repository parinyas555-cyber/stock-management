<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_login();
$id=(int)($_GET['id']??0);
if($id<1){http_response_code(404);exit;}
try{$s=db()->prepare('SELECT image_data,image_mime FROM products WHERE id=?');$s->execute([$id]);$row=$s->fetch();if(!$row || $row['image_data']===null){http_response_code(404);exit;}
$data=$row['image_data'];
if(is_resource($data)){$data=stream_get_contents($data);}
if($data===false || $data===''){http_response_code(404);exit;}
header('Content-Type: '.($row['image_mime']?:'image/jpeg'));
header('Content-Length: '.strlen($data));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
echo $data;}catch(Throwable $e){http_response_code(404);}
