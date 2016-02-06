<?php
#new index.php
require_once("functions/bootstrap.php");
require_once(CLASS_DIR."/simulator.class.php");
$kibopage = new simulatorpage;
$kibopage->run();
?>