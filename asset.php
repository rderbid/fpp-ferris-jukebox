<?php
$type=(string)($_GET['type']??'');$file=basename((string)($_GET['file']??''));
$base=$type==='artwork'?__DIR__.'/artwork':($type==='asset'?__DIR__.'/assets':'');
if(!$base||!preg_match('/\.(jpe?g|png|webp)$/i',$file)){http_response_code(404);exit;}
$path=$base.'/'.$file;if(!is_file($path)){http_response_code(404);exit;}
$ext=strtolower(pathinfo($path,PATHINFO_EXTENSION));$mime=$ext==='png'?'image/png':($ext==='webp'?'image/webp':'image/jpeg');
header('Content-Type: '.$mime);header('Content-Length: '.filesize($path));header('Cache-Control: public, max-age=86400');header('X-Content-Type-Options: nosniff');
readfile($path);exit;
?>