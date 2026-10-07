<?php
function getEndpointsfppferrisjukebox(){
 return array(
  array('method'=>'GET','endpoint'=>'status','callback'=>'fjStatus'),
  array('method'=>'POST','endpoint'=>'request/:playlist','callback'=>'fjRequest')
 );
}
function fjConfig(){
 $d=array('enabled'=>true,'allowedPlaylists'=>array(),'queueEnabled'=>true,'queueMax'=>3,'allowDuplicateQueue'=>false);
 $f=__DIR__.'/jukebox-config.json';
 if(is_file($f)){ $x=json_decode(file_get_contents($f),true); if(is_array($x))$d=array_merge($d,$x); }
 return $d;
}
function fjGet($path){
 $ch=curl_init('http://127.0.0.1/'.ltrim($path,'/'));
 curl_setopt_array($ch,array(CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>5));
 $b=curl_exec($ch); $c=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
 if($b===false||$c<200||$c>=300)return null;
 $j=json_decode($b,true); return $j===null?$b:$j;
}
function fjCurrent($s){
 if(!is_array($s))return '';
 if(isset($s['current_playlist']['playlist']))return (string)$s['current_playlist']['playlist'];
 if(isset($s['current_playlist'])&&is_string($s['current_playlist']))return $s['current_playlist'];
 if(isset($s['playlist']['playlist']))return (string)$s['playlist']['playlist'];
 return '';
}
function fjLockFile(){ return __DIR__.'/.jukebox-active'; }
function fjActiveRequest(){ $f=fjLockFile(); return is_file($f)?trim((string)file_get_contents($f)):''; }
function fjQueueFile(){return __DIR__.'/.jukebox-queue.json';}
function fjQueue(){ $f=fjQueueFile(); if(!is_file($f))return array(); $q=json_decode(file_get_contents($f),true); return is_array($q)?array_values($q):array(); }
function fjSaveQueue($q){file_put_contents(fjQueueFile(),json_encode(array_values($q),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX);}
function fjStartShow($p){file_put_contents(fjLockFile(),$p,LOCK_EX);$r=fjGet('/api/command/Insert%20Playlist%20Immediate/'.rawurlencode($p).'/0/0/false');if($r===null){@unlink(fjLockFile());return false;}fjRecordPlay($p);return true;}
function fjStatus(){
 $c=fjConfig(); $s=fjGet('/api/fppd/status'); $p=fjCurrent($s); $j=fjActiveRequest();
 // An allowed playlist may also be the scheduled playlist, so the allowlist alone
 // cannot identify a guest request. Lock only when this plugin started the active show.
 $busy=($j!=='' && $p===$j);$q=fjQueue();
 if($j!=='' && $p!==$j){
  if(is_file(fjLockFile()))@unlink(fjLockFile());
  if(!empty($c['queueEnabled'])&&!empty($q)){
   $next=array_shift($q);fjSaveQueue($q);
   if(fjStartShow($next)){$s=fjGet('/api/fppd/status');$p=fjCurrent($s);$j=$next;$busy=true;}else{$j='';$busy=false;}
  }
 }
 $remaining=null;
 if($busy&&is_array($s)){
  // Insert Playlist Immediate can report the parent/scheduled playlist's remaining time.
  // For our single-MP4 guest playlists, calculate against the guest playlist duration instead.
  $elapsed=isset($s['seconds_elapsed'])?max(0,(int)$s['seconds_elapsed']):null;
  $pi=fjGet('/api/playlist/'.rawurlencode($j));$total=null;
  if(is_array($pi)){
   if(isset($pi['playlistInfo']['total_duration']))$total=(float)$pi['playlistInfo']['total_duration'];
   elseif(isset($pi['total_duration']))$total=(float)$pi['total_duration'];
   elseif(isset($pi['mainPlaylist'])&&is_array($pi['mainPlaylist'])){$total=0;foreach($pi['mainPlaylist'] as $item)$total+=(float)($item['duration']??0);}
  }
  if($total!==null&&$total>0&&$elapsed!==null)$remaining=max(0,(int)ceil($total-$elapsed));
  elseif(isset($s['seconds_remaining']))$remaining=max(0,(int)$s['seconds_remaining']);
 }
 return json(array('ok'=>$s!==null,'enabled'=>!empty($c['enabled']),'busy'=>$busy,'currentPlaylist'=>$p,'jukeboxPlaylist'=>$busy?$j:'','secondsRemaining'=>$busy?$remaining:null,'queueEnabled'=>!empty($c['queueEnabled']),'queue'=>$q,'queueCount'=>count($q),'queueMax'=>max(1,(int)($c['queueMax']??3))));
}
function fjStatsFile(){return __DIR__.'/jukebox-stats.json';}
function fjRecordPlay($p){$f=fjStatsFile();$d=array('plays'=>array(),'history'=>array());if(is_file($f)){$x=json_decode(file_get_contents($f),true);if(is_array($x))$d=array_merge($d,$x);}if(!isset($d['plays'][$p]))$d['plays'][$p]=0;$d['plays'][$p]++;$d['history'][]=array('playlist'=>$p,'time'=>date('c'));file_put_contents($f,json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX);}
function fjRequest(){
 global $settings;
 $c=fjConfig(); $p=rawurldecode((string)params('playlist'));
 if(empty($c['enabled'])){http_response_code(403);return json(array('ok'=>false,'message'=>'The jukebox is closed.'));}
 if(!in_array($p,$c['allowedPlaylists'],true)){http_response_code(403);return json(array('ok'=>false,'message'=>'That show is not available.'));}
 $s=fjGet('/api/fppd/status'); $now=fjCurrent($s); $j=fjActiveRequest();
 if($j!=='' && $now===$j){
  if(empty($c['queueEnabled'])){http_response_code(409);return json(array('ok'=>false,'message'=>'A Jukebox show is already playing.','currentPlaylist'=>$now));}
  $q=fjQueue();$max=max(1,min(10,(int)($c['queueMax']??3)));
  if(count($q)>=$max){http_response_code(409);return json(array('ok'=>false,'message'=>'The guest queue is full.','queueCount'=>count($q)));}
  if(empty($c['allowDuplicateQueue'])&&($p===$j||in_array($p,$q,true))){http_response_code(409);return json(array('ok'=>false,'message'=>'That show is already playing or waiting in the queue.'));}
  $q[]=$p;fjSaveQueue($q);
  return json(array('ok'=>true,'queued'=>true,'playlist'=>$p,'position'=>count($q),'queueCount'=>count($q)));
 }
 if(!fjStartShow($p)){http_response_code(502);return json(array('ok'=>false,'message'=>'FPP did not accept the request.'));}
 if(isset($settings['logDirectory']))file_put_contents($settings['logDirectory'].'/plugin-fpp-ferris-jukebox.log',date('c').' requested approved playlist: '.$p."\n",FILE_APPEND|LOCK_EX);
 return json(array('ok'=>true,'playlist'=>$p));
}
?>