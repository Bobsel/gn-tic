<?php
# DB Funktionen für den Attplaner

require_once("database/db.scans.php");

function attack_add($title,$owner,$descr=null,$access=null,$hidden=0) {
  
  if(!$title) return;
  if(!is_numeric($owner)) return;
  if($access && !is_numeric($access)) return;
  if(!is_numeric($hidden)) return;
  
  $insert = array();
  $insert['title'] = "'".mysql_escape_string($title)."'";
  if($descr) $insert['descr'] = "'".mysql_escape_string($descr)."'";
  if($access) $insert['access_time'] = $access; else $insert['access_time'] = "unix_timestamp()";
  $insert['owner'] = $owner;
  if($hidden) $insert['hidden'] = 1; else $insert['hidden'] = 0;
  
  return insertsql("
    insert into attack
    (".join(",",array_keys($insert)).",created)
    values
    (".join(",",array_values($insert)).",unix_timestamp())
  ");
}

function attack_edit($attid,$title,$descr=null,$access=null,$hidden=0) {
  
  if(!is_numeric($attid)) return;
  if(!$title) return;
  if($access && !is_numeric($access)) return;
  if(!is_numeric($hidden)) return;
  
  $update = array();
  
  $update[] = "title = '".mysql_escape_string($title)."'";
  if($descr) $update[] = "descr = '".mysql_escape_string($descr)."'"; else $update[] = "descr = NULL";
  if($access) $update[] = "access_time = $access"; else $update[] = "acess_time = unix_timestamp()";
  if($hidden) $update[] = "hidden = 1"; else $update[] = "hidden = 0";

  return query("
    update attack
    set ".join(",",$update)."
    where attid = $attid
  ");
}

function attack_list($filter=null) {
  
  return selectsql("
    select *,(access_time < unix_timestamp()) as isopen from attack
    order by access_time desc
  ");
}

function attack_get($id) {
  
  if(!is_numeric($id)) return;
  return selectsqlline("select *,(access_time < unix_timestamp()) as isopen from attack where attid = $id");
}



function targets_add($attid,$gala,$pos) {
  
  if(!is_numeric($attid)) return;
  if(!is_numeric($gala)) return;
  if(!$pos) return;
  if(!is_array($pos)) $pos = array($pos);
  //vorhandene scans ermitteln
  $scans = selectsql("select * from scans where gala = $gala and pos IN (".join(",",$pos).")");
  $sid_list = array();
  $pos_list = array();
  if($scans){
    foreach($scans as $scan){
      $sid_list[] = $scan['sid'];
      $pos_list[] = $scan['pos'];
    }
  }
  //noch zu erstellende scans
  $todo_list = array_diff($pos,$pos_list);
  if(count($todo_list)){
    if($list = addScans($gala,$todo_list)) {
      $sid_list = $sid_list + $list;
    }
  }
  
  // bereits eingetragene Ziele ermitteln
  $targets_sid = selectsql("select sid from attack_target","sid");
  if($targets_sid) {
    $todo_list = array_diff($sid_list,$targets_sid);
  } else {
    $todo_list = $sid_list;
  }
  foreach($todo_list as $sid) {
    insertsql("insert into attack_target (attid,sid) values ($attid,$sid)");
  }
  return $todo_list;
}

function attack_get_galalist($attid,$pages,$page=1,$rows=5) {
  
  if (!is_numeric($page) || !is_numeric($rows) || $page < 0 || $rows < 0) return;
  if(!is_numeric($attid)) return;

  $count = selectsql("
    select distinct(s.gala) as gala
    from attack_target at
    left join scans s on(s.sid = at.sid)
    where at.attid = $attid
    order by s.gala asc
  ");
  #Seitenamzahl berechnen
  $pages = ceil(count($count)/$rows);
  #Seite checken
  if ($pages < $page && $pages != 0) {
    $page = $pages;
  }

  return selectsql("
    select distinct(s.gala) as gala
    from attack_target at
    left join scans s on(s.sid = at.sid)
    where at.attid = $attid
    order by s.gala asc
    LIMIT ".($rows*($page-1)).",$rows
  ","gala");
}

function attack_gala_list($attid) {
  if(!is_numeric($attid)) return false;
  return selectsql("
    select distinct(s.gala) as gala
    from attack_target at
    left join scans s on(s.sid = at.sid)
    where at.attid = $attid
    order by s.gala asc
  ");
}

function target_reserve($sid,$uid) {
 query("insert into attack_atter (sid,uid) VALUES ($sid,$uid)");
}

function target_unreserve($sid) {
 query("delete from attack_atter where sid = $sid");
}


function target_list($filter,$col=null) {
  
  if(!$filter || !is_array($filter)) return;
  if($filter['gala']) {
    if(!is_array($filter['gala'])) $filter['gala'] = array($filter['gala']);
    $awhere[] = "s.gala IN (".join(",",$filter['gala']).")";
  }
  if($filter['attid'] && is_numeric($filter['attid'])) {
    $awhere[] = "at.attid = ".$filter['attid'];
  }
  if($awhere) {
    $where = "WHERE ".join(" AND ",$awhere);
  }
  return selectsql("
    select at.*,s.*,count(aa.uid) as atter from
    attack_target at
    left join scans s on(s.sid = at.sid)
    left join attack_atter aa on (aa.sid = at.sid)
    $where
    group by at.sid
    order by s.gala asc, s.pos asc
  ",$col);
}

function targets_delete_bysid($sids) {
  
  if(!$sids) return;
  if(!is_array($sids)) $sids = array($sids);
  // Atter löschen
  atter_delete_bysid($sids);
  // Targets löschen  
  return query("
    delete from attack_target
    where
    sid IN (".join(",",$sids).")
  ");
}

function atter_delete_bysid($sids){
  
  if(!$sids) return;
  if(!is_array($sids)) $sids = array($sids);
  return query("
    delete from attack_atter
    where
    sid IN (".join(",",$sids).")
  ");
}

function targets_close($sids){
  
  if(empty($sids)) return;
  if(!is_array($sids)) $sids = array($sids);
  return query("
    update attack_target
    set closed = 1
    where
    sid IN (".join(",",$sids).")
  ");
}

function targets_open($sids){
  
  if(empty($sids)) return;
  if(!is_array($sids)) $sids = array($sids);
  return query("
    update attack_target
    set closed = 0
    where
    sid IN (".join(",",$sids).")
  ");
}

function attack_delete($id) {
  if(!is_numeric($id)) return;
  query("delete from attack where attid = $id");
  query("delete from attack_target where attid = $id");
  $atter = selectsql("
    select  aa.sid from attack_atter aa
    left join attack_target at on (at.sid = aa.sid)
    where at.sid is null
  ","sid");
  if($atter) {
    query("delete from attack_atter where sid IN (".join(",",$atter).")");
  }
}

?>