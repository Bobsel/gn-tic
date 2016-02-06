<?php
function bot_list () {
  
  return selectsql("select * from ircbots order by name asc");
}

function bot_get($id) {
  
  if (!is_numeric($id)) return;
  return selectsqlline("select * from ircbots where botid = $id");
}

function bot_get_bylogin($login,$password) {
  
  return selectsqlline("select * from ircbots where login = '$login' AND password = '$password'");
}

function bot_add($name,$login,$password,$soapurl,$host="") {
  
  return insertSQL("
    insert into ircbots (name,login,password,host,soapurl)
    values ('$name','$login','$password','$host','$soapurl')
  ");
}

function bot_update($id,$name,$login,$password,$soapurl,$host="") {
  
  if (!is_numeric($id)) return;
  return query("
    update ircbots set name = '$name', login='$login', password = '$password',
    soapurl='$soapurl', host = '$host' where botid = $id
  ");
}

function bot_delete($id) {
  
  if (!is_numeric($id)) return;
  return query("
    delete from ircbots where botid = $id
  ");
}

function bot_firstauth($id,$auth) {
  
  if (!is_numeric($id)) return;
  $auth = addslashes($auth);
  return query("
    update ircbots set authstring = '$auth', firstauth=unix_timestamp(),lastauth=unix_timestamp() where botid = $id");
}

function bot_refreshauth($id,$auth) {
  
  if (!is_numeric($id)) return;
  $auth = addslashes($auth);
  return query("
    update ircbots set authstring = '$auth', lastauth=unix_timestamp() where botid = $id");
}

function bot_refresh($min=60) {
  
  if (!is_numeric($min)) return;
  query("update ircbots set authstring = NULL where lastauth < (unix_timestamp()-($min*60))");
}

?>