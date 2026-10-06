<?php
$c=array('title'=>"Ferris & Heidi's Haunted Pirate Cove",'subtitle'=>'Choose Your Adventure','enabled'=>true,'allowedPlaylists'=>array(),'artworkSettings'=>array());
$f=__DIR__.'/jukebox-config.json'; if(is_file($f)){ $x=json_decode(file_get_contents($f),true); if(is_array($x))$c=array_merge($c,$x); }
$shell=''; $sf=__DIR__.'/assets/jukebox-shell.jpg'; if(is_file($sf)){ $shell='data:image/jpeg;base64,'.base64_encode(file_get_contents($sf)); }
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?=htmlspecialchars($c['title'])?></title><style>
:root{color-scheme:dark}*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:system-ui,-apple-system,sans-serif;background:#07111b;color:#fff}
.wrap{max-width:760px;margin:auto;padding:14px 10px 40px;text-align:center;border-left:6px solid #5b3518;border-right:6px solid #5b3518;box-shadow:inset 10px 0 16px #0008,inset -10px 0 16px #0008;background:linear-gradient(#06111b73,#06111b73),<?= $shell ? "url('".$shell."') center top/100% auto no-repeat," : "" ?>#07111b}.flag{font-size:34px}.eyebrow{text-transform:uppercase;letter-spacing:.18em;font-size:.72rem;color:#e5bd72}
h1{font-family:Georgia,serif;font-size:clamp(1.7rem,7vw,3rem);line-height:1;margin:.2em 0;color:#ffd27a;text-shadow:0 3px 0 #5e2c13}.sub{display:inline-block;font:700 1rem Georgia,serif;color:#2b170a;background:#d7aa63;padding:5px 16px;margin-bottom:10px;border-radius:4px}
.status{padding:9px 10px;border:1px solid #b98a4c;border-radius:10px;background:#120e0bd9;margin:0 auto 14px;min-height:40px;box-shadow:0 3px 10px #000b}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 16px;padding:0 14px}
.show{position:relative;overflow:hidden;width:88%;justify-self:center;aspect-ratio:1.65/1;border:3px solid #d8a653;border-radius:9px;padding:0;background:radial-gradient(circle,#75401f,#211008);color:#fff;font:700 clamp(.78rem,3.4vw,1.05rem) Georgia,serif;box-shadow:0 0 0 1px #4b260f,0 6px 12px #000d;min-height:0}.show:disabled{opacity:.42}.show img{width:100%;height:100%;display:block;object-fit:cover}.show span{position:absolute;left:0;right:0;bottom:0;padding:7px 3px;background:linear-gradient(transparent,#000 38%);text-shadow:0 2px 2px #000}.show.noart span{top:0;display:grid;place-items:center;background:transparent;padding:8px}
.foot{margin-top:26px;color:#a9957b;font-size:.78rem}.closed{padding:22px;border:1px solid #744;border-radius:16px;background:#241515}
</style></head><body><main class="wrap"><div class="flag">☠️</div><div class="eyebrow">Welcome aboard</div><h1><?=htmlspecialchars($c['title'])?></h1><div class="sub"><?=htmlspecialchars($c['subtitle'])?></div>
<div id="status" class="status">Checking the waters…</div>
<?php if(empty($c['enabled'])):?><div class="closed">The Jukebox is closed for now. Check back soon, matey.</div>
<?php elseif(empty($c['allowedPlaylists'])):?><div class="closed">No adventures have been opened yet.</div>
<?php else:?><div class="grid"><?php foreach($c['allowedPlaylists'] as $p):$art='';foreach(array('jpg','jpeg','png','webp') as $ext){$af=__DIR__.'/artwork/'.$p.'.'.$ext;if(is_file($af)){$mime=$ext==='png'?'image/png':($ext==='webp'?'image/webp':'image/jpeg');$art='data:'.$mime.';base64,'.base64_encode(file_get_contents($af));break;}}?><button class="show <?=$art?'':'noart'?>" data-playlist="<?=htmlspecialchars($p,ENT_QUOTES)?>"><?php if($art):$as=$c['artworkSettings'][$p]??array('x'=>50,'y'=>50,'zoom'=>100);?><img src="<?=htmlspecialchars($art,ENT_QUOTES)?>" alt="<?=htmlspecialchars($p,ENT_QUOTES)?>" style="object-position:<?=$as['x']?>% <?=$as['y']?>%;transform:scale(<?=$as['zoom']/100?>)"><?php else:?><span><?=htmlspecialchars($p)?></span><?php endif;?></button><?php endforeach;?></div><?php endif;?>
<div class="foot">One adventure at a time • The regular show resumes automatically</div></main><script>
const box=document.getElementById('status'),buttons=[...document.querySelectorAll('.show')];let requesting=false,remaining=null,playing='';
const fmt=n=>{n=Math.max(0,Math.round(n||0));return Math.floor(n/60)+':'+String(n%60).padStart(2,'0')};
const paint=()=>{if(remaining!==null&&playing)box.textContent='🎬 Now Playing: '+playing+' • ⏳ '+fmt(remaining)+' remaining'};
setInterval(()=>{if(remaining!==null&&remaining>0){remaining--;paint()}},1000);
const lock=x=>buttons.forEach(b=>b.disabled=x);
async function refresh(){try{const r=await fetch('/api/plugin/fpp-ferris-jukebox/status',{cache:'no-store'}),s=await r.json();
if(!s.enabled){box.textContent='🏴‍☠️ The Jukebox is closed for now.';lock(true)}else if(s.busy){playing=s.currentPlaylist||'Current Show';remaining=Number.isFinite(Number(s.secondsRemaining))?Number(s.secondsRemaining):null;if(remaining!==null)paint();else box.textContent='🎬 Now Playing: '+playing;lock(true)}else if(!requesting){remaining=null;playing='';box.textContent='⚓ Ready — choose a show!';lock(false)}}catch(e){box.textContent='⚠️ Jukebox connection unavailable';lock(true)}}
buttons.forEach(b=>b.onclick=async()=>{if(requesting)return;requesting=true;lock(true);box.textContent='🏴‍☠️ Launching '+b.dataset.playlist+'…';
try{const r=await fetch('/api/plugin/fpp-ferris-jukebox/request/'+encodeURIComponent(b.dataset.playlist),{method:'POST'}),d=await r.json();if(!r.ok)throw new Error(d.message||'Request failed');box.textContent='🎬 Now Playing: '+b.dataset.playlist}
catch(e){box.textContent='⚠️ '+e.message;setTimeout(refresh,1200)}finally{requesting=false}});
refresh();setInterval(refresh,2500);
</script></body></html>