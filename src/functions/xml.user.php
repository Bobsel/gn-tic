<?php

require_once(DATABASE_DIR.'/db.gala.php');
require_once(DATABASE_DIR.'/db.user.php');


function xml_user_getid($p) {
  $auth = $p->getParam(0);
  $auth = $auth->scalarval();
  if (check_auth($auth) == false) { return new xmlrpcresp(0, 801, "Bot not authed."); }

  $authnick = $p->getParam(1);
  $authnick = $authnick->scalarval();
  if (($uid = getUseridbyAuth($authnick)) == null) { return new xmlrpcresp(0, 811, "No user found with this auth."); }
  return new xmlrpcresp(new xmlrpcval($uid, "int"));
}

function xml_user_get($id) {
  if (!is_numeric($id)) {
    #soap_fault('Client','',"Ung�ltiger Parameter: '$id'")
    return null;
  }
  return getUserByID($id);
}

function xml_userlist_get($allytag,$nick) {
  if ($nick == "all") { $nick = "%"; }
  $filter['username'] = '"$nick"';
  $filter['ally']  = '"$allytag"';
  $filter['checkallygalas'] = "true";
#  return ":C:CC :C:C";
  return listUser($filter,1,1,100);
}

function xml_scannerlist($auth,$type,$items) {
  if(!check_auth($auth)) return null; # not authenticated
  if (!is_numeric($type)) return null;# new soap_fault('Client','',"Ung�ltiger Parameter: '$id'");
  return user_get_scannerlist($type,$items);
}


?>