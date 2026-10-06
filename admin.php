<?php
$configFile=__DIR__.'/jukebox-config.json';
$config=array('title'=>"Ferris & Heidi's Haunted Pirate Cove",'subtitle'=>'Choose Your Adventure','enabled'=>true,'allowedPlaylists'=>array());
if(is_file($configFile)){ $x=json_decode(file_get_contents($configFile),true); if(is_array($x))$config=array_merge($config,$x); }
$dir=isset($settings['playlistDirectory'])?$settings['playlistDirectory']:'/home/fpp/media/playlists';
$available=array();
foreach(glob($dir.'/*.json')?:array() as $f)$available[]=pathinfo($f,PATHINFO_FILENAME);
natcasesort($available); $available=array_values($available);
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $sel=isset($_POST['playlists'])&&is_array($_POST['playlists'])?$_POST['playlists']:array();
 $config=array(
  'title'=>trim($_POST['title']??'')?:"Ferris & Heidi's Haunted Pirate Cove",
  'subtitle'=>trim($_POST['subtitle']??'')?:'Choose Your Adventure',
  'enabled'=>isset($_POST['enabled']),
  'allowedPlaylists'=>array_values(array_intersect($available,$sel))
 );
 file_put_contents($configFile,json_encode($config,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),LOCK_EX);
 $msg='Jukebox settings saved.';
}
?>
<div class="container-fluid">
<h2>Ferris Jukebox</h2>
<p>Select the FPP playlists guests may launch. MP4-only playlists are supported; no sequence is required.</p>
<?php if($msg):?><div class="alert alert-success"><?=htmlspecialchars($msg)?></div><?php endif;?>
<form method="post">
<div class="form-group"><label>Guest page title</label><input class="form-control" name="title" value="<?=htmlspecialchars($config['title'])?>"></div>
<div class="form-group"><label>Subtitle</label><input class="form-control" name="subtitle" value="<?=htmlspecialchars($config['subtitle'])?>"></div>
<div class="form-check my-3"><input class="form-check-input" type="checkbox" name="enabled" id="enabled" <?=$config['enabled']?'checked':''?>><label class="form-check-label" for="enabled">Enable guest requests</label></div>
<h4>Allowed Playlists</h4>
<?php if(!$available):?><div class="alert alert-warning">No playlists found. Create your MP4 playlists in FPP first.</div><?php endif;?>
<?php foreach($available as $p):?><div class="form-check py-1"><input class="form-check-input" type="checkbox" name="playlists[]" value="<?=htmlspecialchars($p)?>" id="p<?=md5($p)?>" <?=in_array($p,$config['allowedPlaylists'],true)?'checked':''?>><label class="form-check-label" for="p<?=md5($p)?>"><?=htmlspecialchars($p)?></label></div><?php endforeach;?>
<button class="btn btn-success mt-3" type="submit">Save Jukebox Settings</button>
</form><hr>
<a class="btn btn-primary" target="_blank" href="plugin.php?plugin=fpp-ferris-jukebox&page=jukebox.php&nopage=1">Open Guest Jukebox</a>
</div>
