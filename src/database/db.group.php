<?PHP
/**
 * @return unknown
 * @param $filter unknown
 * @param $pages unknown
 * @param $page unknown
 * @param $rows unknown
 * @desc Enter description here...
 */
function listGroups($filter="",$pages=1,$page=1,$rows=10) {
	
	if ($filter && is_array($filter)) {
		if ($filter['sort'] == "name") $sort = " name ";
			elseif ($filter['sort'] == "descr") $sort = " descr ";
			elseif ($filter['sort'] == "member") $sort = " member ";
			else $sort = " name ";
		if ($filter['order'] == "asc") $order = " ASC ";
			else $order = " DESC ";
	} else {
		$sort = "name";
		$order = "asc";
	}
	$count = selectsqlLine("select count(*) as count from groups");
	#Seitenamzahl berechnen
	$pages = ceil($count['count']/$rows);
	#Seite checken
	if ($pages < $page && $pages != 0) {
		$page = $pages;
	}

	return selectsql("select g.*,count(u.uid) as member from groups g
						left join user u using(gid)
						group by gid
						order by $sort $order
						LIMIT ".($rows*($page-1)).",$rows");
}

/**
 * @return unknown
 * @desc Enter description here...
 */
function getGroupList($rank=0) {
	
  if ($rank){
    return selectsql("SELECT g.gid, g.name, g.usertitle, g.descr, min( gr.rank )  AS minrank, gr.rank as grouprank
                        FROM groups g
                        LEFT  JOIN group_rights gr ON ( gr.gid = g.gid )
                        GROUP  BY g.gid
                        HAVING minrank >=$rank OR grouprank IS  NULL
                        ORDER  BY name ASC ");
  } else {
    return selectsql("select * from groups order by name asc");
  }
}

function getGroup($gid) {
	
	if (!$gid || !is_numeric($gid)) return ;
	$return = selectsqlLine("select * from groups where gid = $gid");
	return $return;
}

function listRightsByGroup($gid) {
	
	return selectsql("select r.*,gr.rank,gr.gid as isset from
            rights r
						left join group_rights gr on(gr.rid = r.rid and gr.gid = $gid)
            order by r.pos asc");
}

function updateRights($gid,$rank,$ids) {
  
  query("delete from group_rights where gid = $gid and rank = $rank");
  $rights = selectsql("select rid from group_rights
    where gid = $gid and rank >= $rank");
  for($i=0;$i < count($ids);$i++){
    if (searchArray("rid",$ids[$i],$rights)){
      query("update group_rights
        set rank = $rank where rid = ".$ids[$i]." AND gid = $gid");
    } else {
      query("insert into group_rights(rank,rid,gid)
        values ($rank,".$ids[$i].",$gid)");
    }
  }
}

function updateGroup($gid,$name,$descr,$title) {
	
	if (!$gid || !is_numeric($gid)) {echo "db.group:updateGroup Ungültiger Parameter!";return ;}
	if (!$name || !$descr ) {echo "db.group:updateGroup Fehlender Parameter!";return ;}
	query("update groups set name = '$name', descr = '$descr', usertitle = '$title' where gid = $gid");
}

function addGroup($name,$descr,$title) {
	
	if (!$name || !$descr) {echo "db.group:addGroup Fehlender Parameter!";return ;}
	$gid = insertSQL("insert into groups (name, descr, usertitle) values('$name','$descr','$title')");
	if (!$gid) {echo "db.group:addGroup Einfügen fehlgeschlagen!";return ;}
	return $gid;
}

function deleteGroup($gid) {
	
	if (!$gid || !is_numeric($gid)) {echo "db.group:deleteGroup Ungültiger Parameter!";return ;}
	query("update user set gid = 0 where gid = $gid");
	query("delete from group_rights where gid = $gid");
	query("delete from groups where gid = $gid");
}

function getGroupByName($name) {
	
	if (!$name ) {echo "db.group:getGroupByName Fehlender Parameter!";return ;}
	return selectsqlLine("select * from groups where name = '$name'");
}

function listGroupsByRank($rank) {
  
  if (!isset($rank) || !is_numeric($rank)) {echo "db.group:listGroupByRank Ungültiger Parameter!";return ;}
  $list = selectsql("
    SELECT  * , max(rank) as maxrank, min(rank) as minrank FROM groups g, group_rights gr
      WHERE gr.gid = g.gid
      AND gr.rank <= $rank
      group by g.gid
      order by g.gid asc
    ");
  for($i=0;$i < count($list);$i++){
    if ($list[$i]['maxrank'] == $rank) {
      $list[$i]['changed'] = 1;
    }
  }
  return $list;
}

#
# liefert den rank von einem Right eines Users
#

function getUserRightLevel($uid,$right) {
  
  if (!isset($uid) || !is_numeric($uid)) {echo "db.group:getUserRightLevel Ungültiger Parameter!";return ;}
  $result = selectsqlLIne("
    select gr.rank from user u, group g, group_rights gr, rights r
    where
      g.gid = u.gid AND
      gr.gid = g.gid AND
      r.rid = gr.rid AND
      r.name = '$right'"
  );
  if ($result) {
    switch ($result) {
       case 0: $result = "global";
         break;
       case 1: $result = "ally";
         break;
       case 2: $result = "gala";
         break;
    }
    return $result;
  } else {
    return;
  }
}

?>