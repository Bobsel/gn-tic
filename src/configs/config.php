<?php
#db config
$db_user = '';
$db_host = '';
$db_password = '';
$db_database = '';

$project_home = getcwd();

$debug = false;
$debug_database = false;
$debug_firephp = false;
$debug_logger = false;  

$session_save_path = $project_home."/sessions/";

define(TIC_VERSION, "TIC 1.6.1b");
define(TIC_COPYRIGHT, "Copyright by <a href=\"mailto:stefan.dieringer@googlemail.com\">Huhn</a> 2004-2009");

?>