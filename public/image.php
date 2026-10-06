<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_login();
$id=(int)($_GET['id']??0);
if($id<1){http_response_code(404);exit;}
try{$s=db()->prepare('SELECT image_data,image_mime FROM products WHERE id=?');$s->execute([$id]);$row=$s->fetch();if(!$row || empty($row['image_data'])){http_response_code(404);exit;}header('Content-Type: '.($row['image_mime']?:'image/jpeg'));header('Cache-Control: public, max-age=86400');echo $row['image_data'];}catch(Throwable $e){http_response_code(404);}
