<?php
# Allianzfunktionen

/**
 * Listet die Allianzen auf
 * @param filter Filter
 */
function listAllys($filter,$pages,$page,$rows) {
  
  if ($filter && is_array($filter)) {
    if ($filter['sort'] == "name") $sort = " a.name ";
      elseif ($filter['sort'] == "member") $sort = " member ";
      else $sort = " a.tag ";
    if ($filter['order'] == "asc") $order = " ASC ";
      else $order = " DESC ";
  } else {
    $sort = " a.tag ";
    $order = " asc ";
  }
  $count = selectsqlLine("select count(*) as count from alliance");
  #Seitenamzahl berechnen
  $pages = ceil($count['count']/$rows);
  #Seite checken
  if ($pages < $page && $pages != 0) {
    $page = $pages;
  }
  return selectsql("
    select a.*,count(u.uid) as member
    from alliance a
    left join galaxy ga on (ga.aid = a.aid)
    left join user u on (u.gala = ga.gala)
    group by (a.aid)
    order by $sort $order
    LIMIT ".($rows*($page-1)).",$rows");
}

function getAllyList() {
  
  return selectsql("select * from alliance order by tag asc");
}

function getAlly($aid) {
  
  return selectsqlLine("
    select a.*,count(u.uid) as member,count(distinct ga.gala) as galas
    from alliance a
    left join galaxy ga on (ga.aid = a.aid)
    left join user u on (u.gala = ga.gala)
    where a.aid = $aid
    group by (a.aid)
    ");
}

function getAllyByTag($tag) {
  
  return selectsqlLine("
    select * from alliance where tag = '$tag'
    ");
}

function addAlly($name,$tag,$descr="",$url="",$irc="") {
  
  if (!$name) {
    echo "db.ally::addAlly: name fehlt";
    return;
  }
  if (!$tag) {
    echo "db.ally::addAlly: name fehlt";
    return;
  }
  return insertsql("
    insert into alliance (name,tag,descr,irc,url)
    values ('$name','$tag','$descr','$irc','$url')
  ");
}

function updateAlly($id,$name,$tag,$descr,$url,$irc) {
  
  if (!$name) {
    echo "db.ally::updateAlly: name fehlt";
    return;
  }
  if (!$tag) {
    echo "db.ally::updateAlly: name fehlt";
    return;
  }
  if (!$name) {
    echo "db.ally::addAlly: name fehlt";
    return;
  }
  if (!$id || !is_numeric($id)) {
    echo "db.ally::updateAlly: id ungültig";
    return;
  }
  return query("update alliance
    set name = '$name', tag = '$tag', descr = '$descr',
    irc = '$irc', url = '$url'
    where aid = $id");
}

function deleteAlly($id) {
  
  $galas = selectsql("select gala from galaxy where aid = $id");
  if ($galas){
    #Galaxien löschen
    deleteGalaxy(getArrayFromList($galas,"gala"));
  }
  return query("delete from alliance where aid = $id");
}

?>