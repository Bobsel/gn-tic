<?php
require_once("functions/bootstrap.php");
require_once(CLASS_DIR."/attplan.class.php");

$kibopage = new attplan;
$kibopage->run();
?>