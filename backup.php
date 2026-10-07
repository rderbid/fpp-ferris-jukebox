<?php
// Standalone Ferris Jukebox backup download endpoint.
while(ob_get_level()>0)@ob_end_clean();
$bundle=array('format'=>'Ferris Jukebox Backup','version'=>2,'created'=>date('c'),'files'=>array());
foreach(array('jukebox-config.json','jukebox-stats.json') as $bn){$src=__DIR__.'/'.$bn;if(is_file($src))$bundle['files'][$bn]=base64_encode(file_get_contents($src));}
foreach(array('artwork','assets') as $dn){foreach(glob(__DIR__.'/'.$dn.'/*')?:array() as $src){if(is_file($src))$bundle['files'][$dn.'/'.basename($src)]=base64_encode(file_get_contents($src));}}
$config=@json_decode(@file_get_contents(__DIR__.'/jukebox-config.json'),true);
$allowed=is_array($config)&&isset($config['allowlist'])&&is_array($config['allowlist'])?$config['allowlist']:array();
foreach($allowed as $playlist){
 $playlist=(string)$playlist;
 if($playlist===''||strcasecmp($playlist,'Halloween 2026')===0)continue;
 $src='/home/fpp/media/playlists/'.$playlist.'.json';
 if(is_file($src))$bundle['files']['fpp-playlists/'.basename($src)]=base64_encode(file_get_contents($src));
}
$body=json_encode($bundle,JSON_UNESCAPED_SLASHES);
if($body===false){http_response_code(500);header('Content-Type:text/plain');echo 'Could not create Ferris Jukebox backup.';exit;}
$name='ferris-jukebox-backup-'.date('Ymd-His').'.fjb';
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="'.$name.'"');
header('Content-Length: '.strlen($body));
header('Cache-Control: no-store, no-cache, must-revalidate');
echo $body;exit;
?>