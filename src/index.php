<?php
#new index.php
require_once("functions/bootstrap.php");
require_once(CLASS_DIR."/index.class.php");


//user_activity_check("moep");

$kibopage = new indexpage;
$kibopage->run();

?>