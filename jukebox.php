<?php
$c=array('title'=>"Ferris & Heidi's Haunted Pirate Cove",'subtitle'=>'Choose Your Adventure','enabled'=>true,'allowedPlaylists'=>array());
$f=__DIR__.'/jukebox-config.json'; if(is_file($f)){ $x=json_decode(file_get_contents($f),true); if(is_array($x))$c=array_merge($c,$x); }
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?=htmlspecialchars($c['title'])?></title><style>
:root{color-scheme:dark}*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:system-ui,-apple-system,sans-serif;background:radial-gradient(circle at top,#4a2918,#17100c 48%,#080706);color:#fff}
.wrap{max-width:760px;margin:auto;padding:28px 18px 50px;text-align:center}.flag{font-size:42px}.eyebrow{text-transform:uppercase;letter-spacing:.18em;font-size:.72rem;color:#e5bd72}
h1{font-family:Georgia,serif;font-size:clamp(2rem,8vw,3.6rem);line-height:1;margin:.3em 0;color:#ffd27a;text-shadow:0 3px 0 #5e2c13}.sub{font-size:1.15rem;color:#f4e2c2;margin-bottom:22px}
.status{padding:12px 14px;border:1px solid #8c673f;border-radius:14px;background:#1d1713;margin:0 auto 20px;min-height:48px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px}
.show{border:1px solid #b88443;border-radius:16px;padding:18px 14px;background:linear-gradient(#6b351d,#3a1d12);color:#fff;font:700 1.08rem Georgia,serif;box-shadow:0 5px 0 #1d0e09;min-height:70px}.show:disabled{opacity:.38}
.foot{margin-top:26px;color:#a9957b;font-size:.78rem}.closed{padding:22px;border:1px solid #744;border-radius:16px;background:#241515}
</style></head><body><main class="wrap"><div class="flag">☠️</div><div class="eyebrow">Welcome aboard</div><h1><?=htmlspecialchars($c['title'])?></h1><div class="sub"><?=htmlspecialchars($c['subtitle'])?></div>
<div id="status" class="status">Checking the waters…</div>
<?php if(empty($c['enabled'])):?><div class="closed">The Jukebox is closed for now. Check back soon, matey.</div>
<?php elseif(empty($c['allowedPlaylists'])):?><div class="closed">No adventures have been opened yet.</div>
<?php else:?><div class="grid"><?php foreach($c['allowedPlaylists'] as $p):?><button class="show" data-playlist="<?=htmlspecialchars($p,ENT_QUOTES)?>">▶ <?=htmlspecialchars($p)?></button><?php endforeach;?></div><?php endif;?>
<div class="foot">One adventure at a time • Please wait for the current show to finish</div></main><script>
const box=document.getElementById('status'),buttons=[...document.querySelectorAll('.show')];let requesting=false;
const lock=x=>buttons.forEach(b=>b.disabled=x);
async function refresh(){try{const r=await fetch('/api/plugin/fpp-ferris-jukebox/status',{cache:'no-store'}),s=await r.json();
if(!s.enabled){box.textContent='🏴‍☠️ The Jukebox is closed for now.';lock(true)}else if(s.busy){box.textContent='🎬 Now Playing: '+(s.currentPlaylist||'Current Show');lock(true)}else if(!requesting){box.textContent='⚓ Ready — choose a show!';lock(false)}}catch(e){box.textContent='⚠️ Jukebox connection unavailable';lock(true)}}
buttons.forEach(b=>b.onclick=async()=>{if(requesting)return;requesting=true;lock(true);box.textContent='🏴‍☠️ Launching '+b.dataset.playlist+'…';
try{const r=await fetch('/api/plugin/fpp-ferris-jukebox/request/'+encodeURIComponent(b.dataset.playlist),{method:'POST'}),d=await r.json();if(!r.ok)throw new Error(d.message||'Request failed');box.textContent='🎬 Now Playing: '+b.dataset.playlist}
catch(e){box.textContent='⚠️ '+e.message;setTimeout(refresh,1200)}finally{requesting=false}});
refresh();setInterval(refresh,2500);
</script></body></html>