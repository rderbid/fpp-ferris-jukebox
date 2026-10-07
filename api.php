<?php
function getEndpointsfppferrisjukebox(){
 return array(
  array('method'=>'GET','endpoint'=>'status','callback'=>'fjStatus'),
  array('method'=>'POST','endpoint'=>'request/:playlist','callback'=>'fjRequest')
 );
}
function fjConfig(){
 $d=array('enabled'=>true,'allowedPlaylists'=>array());
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
function fjStatus(){
 $c=fjConfig(); $s=fjGet('/api/fppd/status'); $p=fjCurrent($s); $j=fjActiveRequest();
 // An allowed playlist may also be the scheduled playlist, so the allowlist alone
 // cannot identify a guest request. Lock only when this plugin started the active show.
 $busy=($j!=='' && $p===$j);
 if($j!=='' && $p!==$j && is_file(fjLockFile())) @unlink(fjLockFile());
 $remaining=(is_array($s)&&isset($s['seconds_remaining']))?max(0,(int)$s['seconds_remaining']):null;
 return json(array('ok'=>$s!==null,'enabled'=>!empty($c['enabled']),'busy'=>$busy,'currentPlaylist'=>$p,'jukeboxPlaylist'=>$busy?$j:'','secondsRemaining'=>$busy?$remaining:null));
}
function fjStatsFile(){return __DIR__.'/jukebox-stats.json';}
function fjRecordPlay($p){$f=fjStatsFile();$d=array('plays'=>array(),'history'=>array());if(is_file($f)){$x=json_decode(file_get_contents($f),true);if(is_array($x))$d=array_merge($d,$x);}if(!isset($d['plays'][$p]))$d['plays'][$p]=0;$d['plays'][$p]++;$d['history'][]=array('playlist'=>$p,'time'=>date('c'));file_put_contents($f,json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX);}
function fjRequest(){
 global $settings;
 $c=fjConfig(); $p=rawurldecode((string)params('playlist'));
 if(empty($c['enabled'])){http_response_code(403);return json(array('ok'=>false,'message'=>'The jukebox is closed.'));}
 if(!in_array($p,$c['allowedPlaylists'],true)){http_response_code(403);return json(array('ok'=>false,'message'=>'That show is not available.'));}
 $s=fjGet('/api/fppd/status'); $now=fjCurrent($s); $j=fjActiveRequest();
 if($j!=='' && $now===$j){http_response_code(409);return json(array('ok'=>false,'message'=>'A Jukebox show is already playing.','currentPlaylist'=>$now));}
 // Mark this specific request so scheduled playback (even of an allowed playlist)
 // does not lock the guest interface.
 file_put_contents(fjLockFile(),$p,LOCK_EX);
 // Insert Playlist Immediate pauses the running scheduled playlist, runs the guest
 // selection once, then resumes the original playlist at the exact paused position.
 $r=fjGet('/api/command/Insert%20Playlist%20Immediate/'.rawurlencode($p).'/0/0/false');
 if($r===null){if(is_file(fjLockFile()))@unlink(fjLockFile());http_response_code(502);return json(array('ok'=>false,'message'=>'FPP did not accept the request.'));}
 fjRecordPlay($p);
 if(isset($settings['logDirectory']))file_put_contents($settings['logDirectory'].'/plugin-fpp-ferris-jukebox.log',date('c').' requested approved playlist: '.$p."\n",FILE_APPEND|LOCK_EX);
 return json(array('ok'=>true,'playlist'=>$p));
}
?>