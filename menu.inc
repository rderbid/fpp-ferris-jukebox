<?php
$menuEntries = array(array('text'=>'Ferris Jukebox','type'=>'content','page'=>'admin.php','wrap'=>1));
foreach ($menuEntries as $entry) {
 if ($entry['type'] != $menu) continue;
 printf("<li><a href='plugin.php?plugin=%s&page=%s'>%s</a></li>\n",
   htmlspecialchars($plugin), htmlspecialchars($entry['page']), htmlspecialchars($entry['text']));
}
?>
