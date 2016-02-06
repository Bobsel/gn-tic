<?php
#new index.php
require_once("functions/bootstrap.php");
require_once(CLASS_DIR."/scans.class.php");
$kibopage = new scanpage;
$kibopage->run();
?>