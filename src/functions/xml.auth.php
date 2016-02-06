<?php

function check_auth($auth){
  global $botlist;
  for($i=0;$i < count($botlist);$i++){
    if ($botlist[$i]['authstring'] == $auth) return $botlist[$i];
  }
  return false;
}

function xml_firstauth($p) {
  // just sends back a string
  $login = $p->getParam(0);
  $login = $login->scalarval();
  $password = $p->getParam(1);
  $password = $password->scalarval();
  $bot = bot_get_bylogin($login,$password);
  if (!$bot) return new xmlrpcresp(new xmlrpcval("Oo"));
  $authstring = md5(time().$bot['botid'].rand(1,100)."kibocenter botmanagement");
  bot_firstauth($bot['botid'],$authstring);
  return new xmlrpcresp(new xmlrpcval($authstring));
}



function soap_refreshauth($auth){
  if (!($bot = check_auth($auth))) return null;
  $authstring = md5(time().$bot['botid'].rand(1,100)."kibocenter botmanagement");
  bot_refreshauth($bot['botid'],$authstring);
  return $authstring;
}
?>