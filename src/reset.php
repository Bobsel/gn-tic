<?php

function deltree($dir) {

  $fh = opendir($dir);
  while($entry = readdir($fh)) {
    if($entry == ".." || $entry == ".")
      continue;
    if(is_dir($dir . $entry))
      deltree($dir . $entry . "/");
    else
      unlink($dir . $entry);
  }
  closedir($fh);
  rmdir($dir);
}

#reset templates_c
echo "<b>reset templates_c</b>:<br>";
$dir = "./templates_c/";
$fh = opendir($dir);
while($entry = readdir($fh)) {
  if($entry == ".." || $entry == ".")
    continue;
  if(is_dir($dir . $entry)) {
    echo "delete dir ".$dir . $entry . "/<br>";
    deltree($dir . $entry . "/");
  } else {
    echo "delete file ".$dir . $entry . "/<br>";
    unlink($dir . $entry);
  }
}
closedir($fh);

?>