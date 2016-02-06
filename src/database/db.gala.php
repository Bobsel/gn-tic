<?php
# Allianzfunktionen

function getGalaListByAlly($aid,$as_array=false) {
  
  if($as_array){
    return selectsql("select * from galaxy where aid = $aid order by gala asc","gala");
  } else {
    return selectsql("select * from galaxy where aid = $aid order by gala asc");
  }
}

function getGala($gala) {
  
  return selectsqlLine("
    select a.*,count(u.uid) as member,ga.*
    from galaxy ga
    left join alliance a on (a.aid = ga.aid)
    left join user u on (u.gala = ga.gala)
    where ga.gala = $gala
    group by (ga.gala)
    ");
}

#liefert ein array mit allen galaxien
function getGalaList($as_array=false) {
  
  if($as_array) {
    return selectsql("
      select gala
      from galaxy ga
      order by gala ASC
      ","gala");
  } else {
    return selectsql("
      select *
      from galaxy ga
      order by gala ASC
      ");
  }
}

function addGala($gala,$aid){
  
  return insertSQL("insert into galaxy(aid,gala) values ($aid,$gala)");
}

function updateGala($id,$gala,$ally) {
  
  if ($id != $gala) {
    if (query("update galaxy set aid = $ally, gala = $gala where gala = $id")) {
      #cascade update  at user
      query("update user set gala = $gala where gala = $id");
    }
  } else {
    query("update galaxy set aid = $ally where gala = $id");
  }
}

/**
 * @return array|liste
 * @param string $col Spaltenname (optinal)
 * @desc Liefert die Liste der Galaxien
*/
function gala_get($col=null) {
  return selectsql("
    select * from galaxy order by aid, gala
  ",$col);
}

function listGalaxys($filter,$pages,$page,$rows) {
  
  if ($filter && is_array($filter)) {
    if ($filter['sort'] == "tag") $sort = " a.tag ";
      elseif ($filter['sort'] == "member") $sort = " member ";
      else $sort = " ga.gala ";
    if ($filter['order'] == "asc") $order = " ASC ";
      else $order = " DESC ";
  } else {
    $sort = " ga.gala ";
    $order = " asc ";
  }
  if ($filter['ally']) {
    $awhere[] = " ga.aid = ".$filter['ally'];
  }
  if ($awhere) {
    $where = " where ".implode("AND",$awhere);
  }
  $count = selectsqlLine("select count(*) as count from galaxy ga ".$where);
  #Seitenamzahl berechnen
  $pages = ceil($count['count']/$rows);
  #Seite checken
  if ($pages < $page && $pages != 0) {
    $page = $pages;
  }
  return selectsql("
    select a.*,count(u.uid) as member,ga.*
    from galaxy ga
    left join alliance a on (a.aid = ga.aid)
    left join user u on (u.gala = ga.gala)
    ".$where."
    group by (ga.gala)
    order by $sort $order
    LIMIT ".($rows*($page-1)).",$rows");
}

function deleteGalaxy($id){
  
  if (is_array($id)) {
    $sql = "(".join(",",$id).")";
    if (!$sql) return;
    $user = selectsql("select uid from user where gala IN ".$sql);
  } else {
    $user = selectsql("select uid from user where gala = $id");
  }
  if ($user){
    #delete user from galaxy
    deleteUser(getArrayFromList($user,"uid"));
  }
  #delete galaxys
  if (is_array($id)) {
    $user = query("delete from galaxy where gala IN ".$sql);
  } else {
    $user = query("delete from galaxy where gala = $id");
  }
  return 1;
}

?>