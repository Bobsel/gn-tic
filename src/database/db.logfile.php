<?PHP

function getLogFilecatList() {
	
	return selectsql("select distinct cat from actionlog order by cat asc");
}

function addToLogfile($descr,$cat,$uid="NULL") {
	
	$descr = $descr."<br>IP: ".$_SERVER['REMOTE_ADDR'];
  $descr = addslashes($descr);
	return insertSQL("insert into actionlog (descr,uid,time,cat) values('$descr',$uid,Now(),'$cat')");
}

function listLogfile($filter,$pages,$page=1,$rows=10) {
	
	if ($page < 0 || $rows < 0 || !is_numeric($page) || !is_numeric($rows)) return;
	#username
	if ($filter['username']) {
		if ($where) $where .= " and ";
		$where .= " u.nick LIKE '".$filter['username']."%'";
	}
	#koords
	if ($filter['gala']) {
		if ($where) $where .= " and ";
		$where .= " u.gala = ".$filter['gala']." ";
	}
	#category
	if ($filter['cat']) {
		if ($where) $where .= " and ";
		$where .= " l.cat = '".$filter['cat']."'";
		
	}
	if ($where) $where = " where ".$where;
	
	#order by
	if ($filter['order'] == "asc") $order = " ASC ";
	else $order = " DESC ";
	if ($filter['sort'] == "username") $sort = " u.nick ";
	elseif ($filter['sort'] == "cat") $sort = " l.cat ";
	elseif ($filter['sort'] == "date") $sort = " l.time ";
	else $sort = " l.time ";
	$count = selectsqlLine("select count(*) as count from actionlog l left join user u using(uid)
								".$where);
	#Seitenamzahl berechnen
	$pages = ceil($count['count']/$rows);
	#Seite checken
	if ($pages < $page && $pages != 0) {
		$page = $pages;
	}
	$return = selectsql("select * from actionlog l left join user u using(uid)
								".$where."
								order by ".$sort." ".$order."
							LIMIT ".($rows*($page-1)).",$rows");
	return $return;
}


?>