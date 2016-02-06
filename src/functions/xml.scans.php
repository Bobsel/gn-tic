<?php

require_once(DATABASE_DIR.'/db.scans.php');

$timeout = 24*60*60; // scan is maximal 24 stunden alt

$xmlrpc_methods["scan.get"] = array("function" => "xmlrpc_scan_get","signature" => array(array("struct","int","int")));

/** 
     * Returns true if $string is valid UTF-8 and false otherwise. 
     * 
     * @since        1.14 
     * @param [mixed] $string     string to be tested 
     * @subpackage 
     */ 
function is_utf8($string) { 
   
	// From http://w3.org/International/questions/qa-forms-utf-8.html 
	return preg_match('%^(?: 
		  [\x09\x0A\x0D\x20-\x7E]            # ASCII 
		| [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte 
		|  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs 
		| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte 
		|  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates 
		|  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3 
		| [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15 
		|  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16 
	)*$%xs', $string); 
   
} 

function fleet2xml($fleet) {
    $data = array();
		$data['kleptoren'] = new XML_RPC_Value($fleet['kleptoren'],"int");
    $data['cancris'] = new XML_RPC_Value($fleet['cancris'],"int");
    $data['fregatten'] = new XML_RPC_Value($fleet['fregatten'],"int");
    $data['zerstoerer'] = new XML_RPC_Value($fleet['zerstoerer'],"int");
    $data['kreuzer'] = new XML_RPC_Value($fleet['kreuzer'],"int");
    $data['schlachter'] = new XML_RPC_Value($fleet['schlachter'],"int");
    $data['traeger'] = new XML_RPC_Value($fleet['traeger'],"int");
    $data['jaeger'] = new XML_RPC_Value($fleet['jaeger'],"int");
    $data['bomber'] = new XML_RPC_Value($fleet['bomber'],"int");
    if(isset($fleet['status'])) $data['status'] = new XML_RPC_Value($fleet['status'],"int");
    if(isset($fleet['return_flight'])) $data['return_flight'] = new XML_RPC_Value($fleet['return_flight'],"int");
    if(isset($fleet['type'])) $data['type'] = new XML_RPC_Value(utf8_encode($fleet['type']));
    if(isset($fleet['dir'])) $data['dir'] = new XML_RPC_Value(utf8_encode($fleet['dir']));
    return new XML_RPC_Value($data,"struct");
}

function xmlrpc_scan_get($params) {
  global $timeout,$xmlrpc_errors,$logger;

  $gala = $params->getParam(0);
	$pos = $params->getParam(1);
	$scan = getScan(array("gala"=>$gala->scalarval(),"pos"=>$pos->scalarval(),"hideold"=>1),$timeout);
	if(!$scan) {
		$logger->debug("scan not found");
	  return new XML_RPC_Response(0,$xmlrpc_errors["scan_not_found"]["code"],$xmlrpc_errors["scan_not_found"]["msg"]);
	} else {
	  $logger->debug(var_export($scan,true));
	}
	$data = array();
  $data['nick'] = new XML_RPC_Value(utf8_encode($scan['nick']));
  $data['gala'] = new XML_RPC_Value($scan['gala'],"int");
  $data['pos'] = new XML_RPC_Value($scan['pos'],"int");
	if ($scan['hassector']) {
    $data['hassector'] = new XML_RPC_Value(true,"boolean");
    $data['sector_prec'] = new XML_RPC_Value($scan['sector_prec'],"int");
    if($scan['sector_svs']) $data['sector_svs'] = new XML_RPC_Value($scan['sector_svs'],"int");
    if($scan['sector_scanner']) $data['sector_scanner'] = new XML_RPC_Value(utf8_encode($scan['sector_scanner']));
    $data['sector_scanage'] = new XML_RPC_Value($scan['sector_scanage']);
    $data['sector_ships'] = new XML_RPC_Value($scan['sector_ships'],"int");
    $data['sector_deff'] = new XML_RPC_Value($scan['sector_deff'],"int");
    $data['sector_exen'] = new XML_RPC_Value($scan['sector_exen'],"int");
    $data['sector_kristall'] = new XML_RPC_Value($scan['sector_kristall'],"int");
    $data['sector_metall'] = new XML_RPC_Value($scan['sector_metall'],"int");
    $data['sector_roids'] = new XML_RPC_Value($scan['sector_roids'],"int");
    $data['sector_punkte2'] = new XML_RPC_Value($scan['sector_punkte2']);
    $data['sector_timeout'] = new XML_RPC_Value((boolean)$scan['sector_timeout'],"boolean");
  } else {
    $data['hassector'] = new XML_RPC_Value(false,"boolean");
  }
  if($scan['hasgscan']) {
    $data['hasgscan'] = new XML_RPC_Value(true,"boolean");

    if($scan['gscan_horus']) $data['gscan_horus'] = new XML_RPC_Value($scan['gscan_horus'],"int");
    if($scan['gscan_rubium']) $data['gscan_rubium'] = new XML_RPC_Value($scan['gscan_rubium'],"int");
    if($scan['gscan_pulsar']) $data['gscan_pulsar'] = new XML_RPC_Value($scan['gscan_pulsar'],"int");
    if($scan['gscan_coon']) $data['gscan_coon'] = new XML_RPC_Value($scan['gscan_coon'],"int");
    if($scan['gscan_centurion']) $data['gscan_centurion'] = new XML_RPC_Value($scan['gscan_centurion'],"int");
    
    $data['gscan_prec'] = new XML_RPC_Value($scan['gscan_prec'],"int");
    $data['gscan_scanage'] = new XML_RPC_Value($scan['gscan_scanage']);
    $data['gscan_timeout'] = new XML_RPC_Value((boolean)$scan['gscan_timeout'],"boolean");
    
    if($scan['gscan_svs']) $data['gscan_svs'] = new XML_RPC_Value($scan['gscan_svs'],"int");
    if($scan['gscan_scanner']) $data['gscan_scanner'] = new XML_RPC_Value(utf8_encode($scan['gscan_scanner']));
  } else {
    $data['hasgscan'] = new XML_RPC_Value(false,"boolean");
  }
  if($scan['hasunit']) {
    $data['hasunit'] = new XML_RPC_Value(true,"boolean");
    $data['unit_prec'] = new XML_RPC_Value($scan['unit_prec'],"int");
    
    $data['kleptoren'] = new XML_RPC_Value($scan['kleptoren'],"int");
    $data['cancris'] = new XML_RPC_Value($scan['cancris'],"int");
    $data['fregatten'] = new XML_RPC_Value($scan['fregatten'],"int");
    $data['zerstoerer'] = new XML_RPC_Value($scan['zerstoerer'],"int");
    $data['kreuzer'] = new XML_RPC_Value($scan['kreuzer'],"int");
    $data['schlachter'] = new XML_RPC_Value($scan['schlachter'],"int");
    $data['traeger'] = new XML_RPC_Value($scan['traeger'],"int");
    $data['jaeger'] = new XML_RPC_Value($scan['jaeger'],"int");
    $data['bomber'] = new XML_RPC_Value($scan['bomber'],"int");
    
    $data['unit_scanage'] = new XML_RPC_Value($scan['unit_scanage']);
    $data['unit_timeout'] = new XML_RPC_Value((boolean)$scan['unit_timeout'],"boolean");
    if($scan['unit_svs']) $data['unit_svs'] = new XML_RPC_Value($scan['unit_svs'],"int");
    if($scan['unit_scanner']) $data['unit_scanner'] = new XML_RPC_Value(utf8_encode($scan['unit_scanner']));
  } else {
    $data['hasunit'] = new XML_RPC_Value(false,"boolean");
  }
  if($scan['hasmili']) {
    $data['hasmili'] = new XML_RPC_Value(true,"boolean");
    
    $fleets = array();
    
    $fleets[0] = fleet2xml($scan['mili_fleets'][0]);
		$fleets[1] = fleet2xml($scan['mili_fleets'][1]);
    $fleets[2] = fleet2xml($scan['mili_fleets'][2]);

    $data['mili_fleets'] = new XML_RPC_Value($fleets,"array");
    $data['mili_prec'] = new XML_RPC_Value($scan['mili_prec'],"int");
    $data['mili_scanage'] = new XML_RPC_Value($scan['mili_scanage']);
    $data['mili_timeout'] = new XML_RPC_Value((boolean)$scan['mili_timeout'],"boolean");
    if($scan['mili_svs']) $data['mili_svs'] = new XML_RPC_Value($scan['mili_svs'],"int");
    if($scan['mili_scanner']) $data['mili_scanner'] = new XML_RPC_Value(utf8_encode($scan['mili_scanner']));
  } else {
    $data['hasmili'] = new XML_RPC_Value(false,"boolean");
  }
  if($scan['hasnews']) {
    $data['hasnews'] = new XML_RPC_Value(true,"boolean");
    
    $data['news_data'] = new XML_RPC_Value(utf8_encode($scan['news_newsdata']));
    $data['news_prec'] = new XML_RPC_Value($scan['news_prec'],"int");
    $data['news_scanage'] = new XML_RPC_Value($scan['news_scanage']);
    $data['news_timeout'] = new XML_RPC_Value((boolean)$scan['news_timeout'],"boolean");
    if($scan['news_svs']) $data['news_svs'] = new XML_RPC_Value($scan['news_svs'],"int");
    if($scan['news_scanner']) $data['news_scanner'] = new XML_RPC_Value(utf8_encode($scan['news_scanner']));
  } else {
    $data['hasnews'] = new XML_RPC_Value(false,"boolean");
  }
  return new XML_RPC_Response(new XML_RPC_Value($data,"struct"));
}

$xmlrpc_methods["scan.add"] = array("function" => "xmlrpc_scan_add","signature" => array(array("boolean","struct")));

function xmlrpc_scan_add($params) {
	global $logger;
	$data = XML_RPC_Decode($params->getParam(0));
	if($data['type'] == "mili") {
		$data['fleets'][0] = $data['fleet'][0];
		$data['fleets'][1] = $data['fleet'][1];
		$data['fleets'][2] = $data['fleet'][2];
		for($i =0;$i < 3;$i++) {
			$data['fleets'][$i]["type"] = utf8_decode($data['fleet'][$i]["type"]);
			$data['fleets'][$i]["dir"] = utf8_decode($data['fleet'][$i]["dir"]);
		}
		unset($data['fleet']);
	}
	if($data['type'] == "news") {
		$data["newsdata"] = utf8_decode($data["newsdata"]);
	}
	$logger->debug("add scan, data: ".var_export($data,true));
	updateScan($data);
	return new XML_RPC_Response(new XML_RPC_Value(true,"boolean"));
}


function xmlrpc_scans_addmili($auth,$gala,$pos,$nick,$prec,$orbit,$fleet1,$fleet2,$svs,$scanner){
  $fleets[0] = tfa($orbit);
  $fleets[1] = tfa($fleet1);
  $fleets[2] = tfa($fleet2);
  $scan = array("gala" => $gala, "pos" => $pos, "nick" => $nick, "prec" => trim($prec), "fleets"=>$fleets, "type" => "mili", "svs" => trim($svs) , "scanner" => trim($scanner));
  updateScan($scan);
}

function xmlrpc_scans_addsector($auth,$scan){
	$scan = tfa($scan);
	$scan['punkte'] = trim(preg_replace("/\./s","",$scan['punkte']));
	updateScan($scan);
}

function xmlrpc_scans_addunit($auth,$scan){
	$scan = tfa($scan);
	updateScan($scan);
}

function xmlrpc_scans_addgscan($auth,$scan){
	$scan = tfa($scan);
	updateScan($scan);
}

function xmlrpc_scans_addnews($auth,$scan){
    $scan = tfa($scan);
    updateScan($scan);
}

?>
