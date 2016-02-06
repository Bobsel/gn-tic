<?PHP

function addNews($title,$message,$uid) {
	
	return insertSQL("insert into news (title,content,uid,time) values('$title','$message',$uid,now())");
}

function addCom($nid,$message,$uid) {
	
	$cid = insertSQL("insert into comments (content,uid,time) values('$message',$uid,now())");
	if ($cid) {
		insertsql("insert into com_news(cid,nid) values($cid,$nid)");
	}
	return $cid;
}

function updateNews($id,$title,$message,$uid) {
	
	if (!is_numeric($id)) return;
	return query("update news set title = '$title', content = '$message', uid = $uid, time = now() where nid = $id");
}

function deleteNews($id) {
	
	if (!is_numeric($id)) return;
	$coms = selectsql("select cid from com_news where nid = $id");
	if ($coms) {
		$where = "where cid = ".$coms[0]['cid'];
		for ($i=1;$i<count($coms);$i++) {
			$where .= " OR cid = ".$coms[$i]['cid'];
		}
		query("delete from comments ".$where);
	}
	query("delete from news where nid = $id");
}

function deleteNewsCom($cid) {
	
	if (!is_numeric($cid)) return;
	if(query("delete from com_news where cid = $cid")) {
		$result = query("delete from comments where cid = $cid");
	}
	return $result;
}

function listNews($pages,$page=1,$rows=10) {
	
	if ($page < 0 || $rows < 0 || !is_numeric($page) || !is_numeric($rows)) return;
	$count = selectsqlLine("select count(*) as count from news ");
	#Seitenamzahl berechnen
	$pages = ceil($count['count']/$rows);
	#Seite checken
	if ($pages < $page && $pages != 0) {
		$page = $pages;
	}
	$return = selectsql("select n.*,u.*,g.usertitle,count(cn.cid) as comments,DATE_FORMAT(n.time, '%d.%m.%y') as ndate,
							DATE_FORMAT(n.time, '%H:%i') as ntime 
								from news n
								left join  user u on (u.uid = n.uid)
								left join groups g on (g.gid = u.gid)
								left join com_news cn on (cn.nid = n.nid)
								group by n.nid
								order by time desc 
								LIMIT ".($rows*($page-1)).",$rows");
	return $return;
}

function listNewsComments($nid,$pages,$page=1,$rows=10) {
	
	if (!is_numeric($nid)) return;
	if ($page < 0 || $rows < 0 || !is_numeric($page) || !is_numeric($rows)) return;
	$pages = getNewsCommentsPages($nid,$rows);
	#Seite checken
	if ($pages < $page && $pages != 0) {
		$page = $pages;
	}
	$return = selectsql("select c.*,u.*,g.usertitle,DATE_FORMAT(c.time, '%d.%m.%y') as cdate,
							DATE_FORMAT(c.time, '%H:%i') as ctime, cn.nid  
								from com_news cn
								left join comments c on(c.cid = cn.cid)
								left join  user u on (u.uid = c.uid)
								left join groups g on (g.gid = u.gid)
								where cn.nid = $nid 
								order by time asc 
								LIMIT ".($rows*($page-1)).",$rows");
	return $return;
}

function getNewsComment($cid) {
	
	if (!is_numeric($cid)) return;
	$return = selectsqlLine("select c.*,u.*,g.usertitle,DATE_FORMAT(c.time, '%d.%m.%y') as cdate,
							DATE_FORMAT(c.time, '%H:%i') as ctime, cn.nid  
								from com_news cn
								left join comments c on(c.cid = cn.cid)
								left join  user u on (u.uid = c.uid)
								left join groups g on (g.gid = u.gid)
								where cn.cid = $cid");
	return $return;
}

function getNewsCommentsPages($nid,$rows=10) {
	
	if (!is_numeric($nid)) return;
	$count = selectsqlLine("select count(*) as count from com_news where nid = $nid");
	#Seitenamzahl berechnen
	$pages = ceil($count['count']/$rows);
	return $pages;
}

function getNews($id) {
	
	if (!is_numeric($id)) return;
	return selectsqlLine("select n.*,u.*,g.usertitle,count(cn.cid) as comments,DATE_FORMAT(n.time, '%d.%m.%y') as ndate,
							DATE_FORMAT(n.time, '%H:%i') as ntime 
								from news n
								left join  user u on (u.uid = n.uid)
								left join groups g on (g.gid = u.gid)
								left join com_news cn on (cn.nid = n.nid)
								where n.nid = $id group by n.nid ");
}

?>