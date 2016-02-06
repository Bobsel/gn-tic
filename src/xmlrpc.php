<?php

require_once("functions/bootstrap.php");

require_once 'XML/RPC/Server.php';
require_once 'XML/RPC/Dump.php';

#load db-engine
require_once(DATABASE_DIR.'/database.php');
require_once(FUNCTION_DIR.'/debug.php');

$logger = & LoggerManager::getLogger("xmlrpcserver");


#load xmlrpc functions
#require_once(FUNCTION_DIR.'xml.auth.php');
#require_once(FUNCTION_DIR.'xml.user.php');

$xmlrpc_methods = array();

require_once(FUNCTION_DIR.'/xml.scans.php');

$xmlrpc_errors = array();

$xmlrpc_errors["scan_not_found"] = array("code" => $XML_RPC_erruser+1,"msg" => "kein Scan vorhanden");


$logger->debug("rpc request");
$server = new XML_RPC_Server($xmlrpc_methods);

$response = ob_get_contents();
ob_end_clean();
$logger->debug("xml response:\n".$response);
echo $response;
$logger->debug("rpc request done");
?>