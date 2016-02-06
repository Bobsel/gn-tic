<?PHP
#user db functions

require_once("database/db.logfile.php");
require_once("database/db.scans.php");

/**
 * @return array von Userinformationen
 * @param $login string
 * @param $password string
 * @desc Liefert die Userdaten anhand des Logins und des Passwords
 */
function getUserByLogin($login,$password="") {
  $login = mysql_real_escape_string($login);
  if ($password) {
    $user = selectsqlLine("select u.*,g.name as groupname, g.descr as groupdescr,g.usertitle
                   from user u
                  left join groups  g using(gid)
                  where lower(u.login) = lower('$login') and u.password = '".md5($password)."'
                ");
    if(is_numeric($user['gid']) && (int)$user['gid'] > 0) {
        getUserRights($user['gid'],&$user);
    }
  } else {
    $user = selectsqlLine("select * from user where lower(login) = lower('$login')");
  }
    return $user;
}

/**
 * @return array von Userinformationen
 * @param $id integer id des Users
 * @param $password = "" Passwort des users in md5, (optional)
 * @desc Liefert Userinformationen anhand von id und passwort
 */
function getUserByID($id, $password="") {
	
    Assert::isId($id);
    if ($password) {
	$user = selectsqlLine("select u.*,ga.aid,a.name,a.tag,
				  g.name as groupname, g.descr as groupdescr,g.usertitle
					from user u
				  left join groups  g using(gid)
          left join galaxy ga on(ga.gala = u.gala)
          left join alliance a on(a.aid = ga.aid)
					where u.uid = $id and u.password = '$password'
							");
	} else {
      	$user = selectsqlLine("select u.*,ga.aid,a.name,a.tag,
					g.name as groupname, g.descr as groupdescr,g.usertitle
          from user u
					left join groups  g using(gid)
          left join galaxy ga on(ga.gala = u.gala)
          left join alliance a on(a.aid = ga.aid)
								where u.uid = $id
							");
	}
    if(is_numeric($user['gid']) && (int)$user['gid'] > 0) {
    	getUserRights($user['gid'],&$user);
    }
	return $user;
}

function getUserByPos($gala,$pos) {
  Assert::isId($gala);
  Assert::isId($pos);
  return selectsqlLine("select * from user where gala = $gala and pos = $pos");
}


/**
 * @return array der userdaten
 * @param numeric $gala
 * @param numeric $pos
 * @desc Liefert den User anhand der galaxie/position
*/
function user_get_bypos($gala,$pos) {
  Assert::isId($gala);
  Assert::isId($pos);
  return selectsqlLine("select * from user where gala = $gala and pos = $pos");
}

function user_get_except($except,$aid=0) {
	
	$where = "";
	if ($except && is_array($except)) {
		for($i=0;$i<count($except);$i++) {
			$where .= " AND uid <> ".$except[$i];
		}
	}
  if ($aid && is_numeric($aid)) {
    $where .= " AND ga.aid = $aid ";
  }
	return selectsql("
    select *
		from user u
    left join galaxy ga on(ga.gala = u.gala)
    where
      fleetupdate IS NOT NULL
      ".$where."
      order by nick asc");
}

function user_fleet_list_byuser($uid){
  Assert::isId($uid);
  $result = selectsql("
    select u.uid,f.*,uf.*,fs.fsid,fs.arrival,fs.return_flight,fs.status,fs.tgala,fs.tpos,fs.orbittime from 
    user u
    left join user_fleet uf on (uf.gala = u.gala and uf.pos = u.pos)
    left join fleet f on (f.fid = uf.fid)
    left join fleet_status fs on (fs.fid = uf.fid)
    where u.uid = $uid
    order by uf.fleetnum ASC
  ");
  return $result;
}

function user_fleet_get($gala,$pos,$fleetnum){
  Assert::isId($gala);  
  Assert::isId($pos);
  Assert::isNumeric($fleetnum);
  $result = selectsqlline("
    select u.uid,f.*,uf.*,fs.fsid,fs.arrival,fs.return_flight,fs.status,fs.tgala,fs.tpos,fs.orbittime from 
    user u
    left join user_fleet uf on (uf.gala = u.gala and uf.pos = u.pos)
    left join fleet f on (f.fid = uf.fid)
    left join fleet_status fs on (fs.fid = uf.fid)
    where uf.gala = $gala and uf.pos = $pos and uf.fleetnum = $fleetnum
  ");
  return $result;
}

function user_fleet_get_byid($fid){
  Assert::isId($fid);
  $result = selectsqlline("
    select u.uid,f.*,uf.*,fs.fsid,fs.arrival,fs.return_flight,fs.status,fs.tgala,fs.tpos,fs.orbittime from 
    user u
    left join user_fleet uf on (uf.gala = u.gala and uf.pos = u.pos)
    left join fleet f on (f.fid = uf.fid)
    left join fleet_status fs on (fs.fid = uf.fid)
    where uf.fid = $fid
  ");
  return $result;
}


function user_fleet_sumorbit($uid) {
  Assert::isId($uid);
  return selectsqlline("
    select
      sum(f.kleptoren) as kleptoren,
      sum(f.cancris) as cancris,
      sum(f.fregatten) as fregatten,
      sum(f.zerstoerer) as zerstoerer,
      sum(f.kreuzer) as kreuzer,
      sum(f.schlachter) as schlachter,
      sum(f.traeger) as traeger,
      sum(f.jaeger) as jaeger,
      sum(f.bomber) as bomber
    from
      user u
      left join user_fleet uf on (uf.gala = u.gala and uf.pos = u.pos)
      left join fleet_status fs on (fs.fid = uf.fid)
      left join fleet f on (f.fid = fs.fid)
    where
      u.uid = $uid AND
      fs.fsid is not null and fs.return_flight = 0
    group by u.uid
  ");
}

function LoggedIn($id) {
	Assert::isId($id);
	return query("update user set loggedin=NOW() where uid = $id");
}

/**
 * @return unknown
 * @param $nick unknown
 * @desc Enter description here...
 */
function getUserByNick($nick) {
	$nick = mysql_real_escape_string($nick);
	return selectsqlLine("select * from user where lower(nick) = lower('$nick')");
}


/**
 * @return true oder false
 * @param $id integer
 * @param $password string
 * @desc Enter description here...
 */
function updateUserPassword($id, $password) {
	Assert::isId($id);
	return query("update user set password = '".md5($password)."',changepw = 0 where uid = '$id'");
}


/**
 * @return unknown
 * @param $id unknown
 * @param $email unknown
 * @param $emailvisible unknown
 * @param $phone unknown
 * @param $koords unknown
 * @desc Enter description here...
 */
function updateUser($id,$nick,$login,$email,$emailvisible,$phone="",$scantype,$svs,$timeview,$fleettype=0) {
    Assert::isId($id);
    $login = mysql_real_escape_string($login);
    $nick = mysql_real_escape_string($nick);
    $email = mysql_real_escape_string($email);
  if(!isset($scantype) || !is_numeric($scantype) || $scantype > 1 || $scantype < 0) $scantype = 0;
  if(!isset($svs) || !is_numeric($svs) || $svs < 0) $svs = 0;
  if(!isset($timeview) || !is_numeric($timeview) || $timeview < 0) $timeview = 0;
  if(!is_numeric($fleettype)) $fleettype = 0;
	
	return query("update user set email = '$email', emailvisible = $emailvisible,
						 phone = '$phone',nick='$nick' ,login = '$login', scantype = $scantype, svs = $svs, timeview = $timeview , fleettype = $fleettype where uid = '$id'");
}

function updatePassword($uid,$password) {
    Assert::isId($uid);
    Assert::notEmpty($password);
	return query("update user set password = '".md5($password)."', changepw = 1 where uid = $uid");
}

function deleteUser($id) {
  Assert::isIdArray($id);
  if(!is_array($id)) $id = array($id);
  $sql = "(".join(",",$id).")";
  #comments l�schen
  query("update comments set uid = 0 where uid IN ".$sql);
  #news auf unbekannt setzen
  query("update news set uid = 0 where uid IN ".$sql);
  
  #flotten die zu hause sind l�schen
  $fids = selectsql("
    select uf.fid from 
    user u
    left join user_fleet uf on (uf.gala = u.gala and uf.pos = u.pos)
    left join fleet_status fs on (fs.fid = uf.fid)
    where u.uid IN $sql and fs.fid is null and uf.fid is not null
  ","fid");
  if($fids) {
    $fids = join(",",$fids);
    query("delete from fleet where fid IN ($fids)");
  }
  #userflotten l�schen
  $fids = selectsql("
    select uf.fid from 
    user u,user_fleet uf
    where u.uid IN $sql and uf.gala = u.gala and uf.pos = u.pos
  ","fid");
  if($fids) {
    $fids = join(",",$fids);
    query("delete from user_fleet where fid IN ($fids)");
  }
  //filter l�schen
  query("delete from fleet_filter where uid IN ".$sql);
  //stats l�schen
  #user l�schen
  query("delete from activity where uid IN $sql");
  query("delete from user where uid IN ".$sql);
  // attplan s�ubern
  query("update attack set owner = NULL where owner IN $sql");
  query("delete from attack_atter where uid IN $sql");

  //actionlog
  query("update actionlog set uid = NULL where uid IN $sql"); 
	return true;
}

function updateAdminUser($id,$nick,$login,$gala,$pos,$gid=0,$ircauth) {
	Assert::isId($id);
	Assert::isId($gala);
    Assert::isId($pos);
    Assert::isNumeric($gid);
    
    $nick = mysql_real_escape_string($nick);
    $ircauth= mysql_real_escape_string($ircauth);
    $user = getUserByID($id);
	if($user['gala'] != $gala || $user['pos'] != $pos) {
  	query("update user_fleet set gala = $gala, pos = $pos where gala = ".$user['gala']." and pos = ".$user['pos']);
	}
  return query("update user set nick = '$nick', login='$login',gala = $gala, pos = $pos, gid = $gid, ircauth = '$ircauth' where uid = $id");
}

/**
 * @return unknown
 * @param $nick unknown
 * @param $password unknown
 * @param $email unknown
 * @param $phone unknown
 * @param $koords unknown
 * @desc Enter description here...
 */
function addUser($nick,$login,$password,$gid,$gala,$pos,$ircauth) {
	Assert::isNumeric($gid);
	Assert::isId($gala);
    Assert::isId($pos);
    
    $nick = mysql_real_escape_string($nick);
    $login = mysql_real_escape_string($login);
    $ircauth= mysql_real_escape_string($ircauth);
  $user = insertsql("insert into user (nick,login,password,gid,gala,pos,changepw,ircauth) values ('$nick','$login','".md5($password)."',$gid,$gala,$pos,1,'$ircauth')");
  for($i=0;$i < 3;$i++) {
    if($i > 0) $status = fleetstatus_get_bykoords($gala,$pos,$i);
    if($status['fid']) {
      $fid = $status['fid'];
    } else {
      $fid = fleet_add();
    }
    insertsql("insert into user_fleet (gala,pos,fid,fleetnum) values ($gala,$pos,$fid,$i)");
  }
  $position = getScan(array("gala" => $gala, "pos" => $pos));
  if($position) {
    scan_update_nick($gala,$pos,$nick);
  } else {
    scan_add($gala,$pos,array("nick" => $nick));
  }
  return $user;
}

/**
 * @return array
 * @param $gid integer
 * @param $userarray array das user data arry per referenz
 * @desc Liefert die Userrechte
 */
function getUserRights($gid,$userarray="") {
	Assert::isId($gid);
	$rights = selectsql("select r.*,gr.rank from groups g
							left join group_rights gr using(gid)
							left join rights r on(r.rid = gr.rid)
							where g.gid = $gid");
	if ($userarray && $rights) {
		for($i=0;$i < count($rights);$i++) {
			$userarray['rights'][$rights[$i]['name']] = $rights[$i];
    }
	}
	return $rights;
}

function listAllUser() {
  
  return selectsql("select u.*,a.*,
          DATE_FORMAT(u.loggedin, '%d.%m.%y') as date,
          DATE_FORMAT(u.loggedin, '%H:%i') as time
          from user u
          left join galaxy ga on (ga.gala = u.gala)
          left join alliance a on (a.aid = ga.aid)
          order by tag asc,nick asc");
}

function listUser($filter,$pages,$page=1,$rows=10) {
	Assert::isId($page);
    Assert::isId($rows);

	#username
	if ($filter['username']) {
		if ($where) $where .= " and ";
		$where .= " u.nick LIKE '".mysql_real_escape_string($filter['username'])."%'";
	}
	#gala
	if ($filter['gala']) {
	    Assert::isId($filter['gala']);
		if ($where) $where .= " and ";
		$where .= " u.gala = ".(int)$filter['gala']." ";
	}
  #ally
  if ($filter['ally']) {
    Assert::isId($filter['ally']);
    if ($where) $where .= " and ";
    $where .= " ga.aid = ".(int)$filter['ally']." ";
    if ($filter['checkallygalas']) {
      $galalist = getGalaListByAlly($filter['ally']);
      if ($galalist){
        $galalist = getArrayFromList($galalist,"gala");
        $galalist = "(".join(",",$galalist).")";
        $where .= " AND u.gala IN ".$galalist;
      } else {
        $page = 0;
        $pages = 0;
        return;
      }
    }
  }
	#phone
	if ($filter['phone']) {
		if ($where) $where .= " and ";
		$where .= " u.phone LIKE '".mysql_real_escape_string($filter['phone'])."%'";
	}
	#group
	if ($filter['group']) {
	    Assert::isTrue((int)$filter['group'] >= -1);
		if ($where) $where .= " and ";
		if ((int)$filter['group'] == -1) $where .= " u.gid = 0 ";
		else $where .= " u.gid = ".((int)$filter['group']);
	}
	if ($where) $where = " where ".$where;

	#order by
	if ($filter['order'] == "asc") $order = " ASC ";
	else $order = " DESC ";
	if ($filter['sort'] == "username") $sort = " u.nick ";
	elseif ($filter['sort'] == "group") $sort = " groupname ";
	elseif ($filter['sort'] == "login") $sort = " u.loggedin ";
	elseif ($filter['sort'] == "fleetupdate") $sort = " u.fleetupdate ";
	elseif ($filter['sort'] == "koords") $sort = " u.gala ".$order.", u.pos ";
	elseif ($filter['sort'] == "phone") $sort = " u.phone ";
	else $sort = " u.nick ";
	$count = selectsqlLine("
        select count(*) as count
        from user u
        left join galaxy ga on (ga.gala = u.gala)
								".$where);
	#Seitenamzahl berechnen
	$pages = ceil($count['count']/$rows);
	#Seite checken
	if ($pages < $page && $pages != 0) {
		$page = $pages;
	}
	$return = selectsql("select u.*,a.tag,
					DATE_FORMAT(u.loggedin, '%d.%m.%y') as date,
					DATE_FORMAT(u.loggedin, '%H:%i') as time,
					g.name as groupname, g.descr as groupdescr,g.usertitle
          from user u
          left join groups  g using(gid)
          left join galaxy ga on(ga.gala = u.gala)
          left join alliance a on(a.aid = ga.aid)
          ".$where."
					order by ".$sort." ".$order."
					LIMIT ".($rows*($page-1)).",$rows");
	return $return;
}

#
# listet die userflotten auf
#
function user_fleet_list($filter,$pages,$page=1,$rows=10) {
    Assert::isId($page);
    Assert::isId($rows);
  #order by
  if ($filter['order'] == "asc") $order = " ASC ";
  else $order = " DESC ";
  if ($filter['sort'] == "username") $sort = " u.nick ";
  elseif ($filter['sort'] == "fleetupdate") $sort = " u.fleetupdate ";
  elseif ($filter['sort'] == "koords") $sort = " u.gala ".$order.", u.pos ";
  else $sort = " u.nick ";
  $count = selectsqlLine("select count(*) as count from user u
                ".$where);
  #Seitenamzahl berechnen
  $pages = ceil($count['count']/$rows);
  #Seite checken
  if ($pages < $page && $pages != 0) {
    $page = $pages;
  }
  $return = selectsql("
        select u.*,
          sum(f.jaeger) as jaeger,
          sum(f.bomber) as bomber,
          sum(f.fregatten) fregatten,
          sum(f.zerstoerer) as zerstoerer,
          sum(f.kreuzer) as kreuzer,
          sum(f.schlachter) as schlachter,
          sum(f.traeger) as traeger,
          sum(f.cancris) as cancris,
          sum(f.kleptoren) as kleptoren
          from user u
            left join user_fleet uf on (uf.gala = u.gala and uf.pos = u.pos)
            left join fleet f on (f.fid = uf.fid)
          group by u.uid
          order by ".$sort." ".$order."
          LIMIT ".($rows*($page-1)).",$rows");
  return $return;
}

#
# listet die userflotten auf
#
function user_fleet_sum($uid) {
    Assert::isId($uid);  
  return selectsqlLine("select u.*,
          sum(f.jaeger) as jaeger,
          sum(f.bomber) as bomber,
          sum(f.fregatten) fregatten,
          sum(f.zerstoerer) as zerstoerer,
          sum(f.kreuzer) as kreuzer,
          sum(f.schlachter) as schlachter,
          sum(f.traeger) as traeger,
          sum(f.cancris) as cancris,
          sum(f.kleptoren) as kleptoren
          from user u
            left join user_fleet uf on (uf.gala = u.gala and uf.pos = u.pos)
            left join fleet f on (f.fid = uf.fid)
          where u.uid = $uid
          group by u.uid
          ");
}

#useronline
function refreshUserOnline($sec=300) {
  	$date = getdate(time());
	$date = date("Y.m.d H:i:s", mktime($date['hours'],$date['minutes'],$date['seconds']-(int)$sec,$date['mon'],$date['mday'],$date['year']));
	query("delete from useronline where time < '$date'");
}

function updateUserOnline($id) {
    Assert::isId($id);	
	$result = selectsqlLine("select * from useronline where uid = $id");
	if ($result) {
		query("update useronline set time = now() where uid = $id");
	} else {
		insertsql("insert into useronline (uid,time) values($id,Now())");
	}
}

function deleteUserOnline($id) {
    Assert::isId($id);	
	query("delete from useronline where uid = $id");
}

function listUserOnline() {
	
	return selectsql("select * from useronline left join user using(uid) order by gala asc, pos asc");
}

function getGalas() {
	
	return selectsql("select distinct gala from user order by gala asc");
}

#
# updated die flotten eines users, fleets = array(id => flotte,...)
#
/*function updateUserFleet($uid,$fleets) {
  
  if (!$uid || !is_numeric($uid)) return;
  $list = selectsql("
    select * from userfleet
    left join fleetstatus using(fid)
    where uid = $uid order by fleetnum asc
  ");
  for($i=0;$i < count($list);$i++){
    $fleet = &$fleets[$i];
    updateFleet($list[$i]['fid'],$fleet['kleptoren'],$fleet['cancris'],
        $fleet['fregatten'],$fleet['zerstoerer'],$fleet['kreuzer'],
        $fleet['schlachter'],$fleet['traeger'],$fleet['jaeger'],$fleet['bomber']);
  }
  query("update user set fleetupdate = unix_timestamp() where uid = $uid");
}
*/

function user_fleets_update($uid,$fleets) {
    Assert::isId($uid);
  $user = getUserByID($uid);
  if (!$user) {
      trigger_error("user not found",E_USER_ERROR);
      return false;
  }
  $userfleets = user_fleet_list_byuser($uid);
  if(!$userfleets || !is_array($userfleets)) {
      trigger_error("user fleets for user $uid not found",E_USER_ERROR);
      return false;
  }
  #fleetstatus_delete_bykoords($user['gala'],$user['pos']);
  foreach ($userfleets as $fleet) {
    if(isset($fleets[$fleet['fleetnum']])) {
      fleet_update($fleet['fid'],$fleets[$fleet['fleetnum']]);
      #$fleets[$fleet['fleetnum']]['fleetnum'] = $fleet['fleetnum'];
      #fleetstatus_add($fleets[$fleet['fleetnum']]);
    }
  }
  query("update user set fleetupdate = unix_timestamp() where uid = $uid");
}

function getUserIdByAuth($auth) {
  
  $auth = mysql_real_escape_string($auth);
  $res = selectsqlline("
          select u.uid
          from user u
          where u.ircauth LIKE '$auth'
  ");
  return $res['uid'];
}

function user_deff_get($id) {
    Assert::isId($id);
  return selectsqlline("
    select rubium,horus,coon,pulsar,centurion,deffupdate from user where uid = $id
  ");
}

function user_deff_update($id,$deff) {
    Assert::isId($id);
  $_check = array(
    "rubium",
    "horus",
    "pulsar",
    "coon",
    "centurion"
  );
  $update = array();
  foreach ($_check as $key) {
    if(is_numeric($deff[$key]) && (int) $deff[$key] > 0){
        $update[] = "$key = ".((int)$deff[$key]);
    } else {
        $update[] = "$key = 0";
    }
  }
  return query("
    update user set ".join(",",$update).", deffupdate = unix_timestamp()
    where uid = $id
  ");
}

function user_fleet_bynum($id,$num) {
    Assert::isId($id);
    Assert::isNumeric($num);
  return selectsqlline("
    select u.uid,uf.*,fs.fsid,fs.arrival,fs.return_flight,fs.returntime,fs.status,fs.tgala,fs.tpos,fs.orbittime from 
    user u
    left join user_fleet uf on (uf.gala = u.gala and uf.pos = u.pos)
    left join fleet f on (f.fid = uf.fid)
    left join fleet_status fs on (fs.fid = uf.fid)
    where 
      u.uid = $id AND uf.fleetnum = $num
  ");
}

/*function User_fleet_status_update($uid,$fleetnum,$status,$return_flight,$eta,$gala,$pos,$ticks,$return) {
  
  if(!is_numeric($uid)) return;
  if(!is_numeric($fleetnum)) return;
  if(!is_numeric($status)) $status = 0;
  if(!is_numeric($return_flight)) $return_flight = 0;

  if (isset($eta)) $seconds = "unix_timestamp()+".$eta*60; else $seconds = "NULL";
  if (!$ticks) $ticks = "NULL";
  if (!$return || !is_numeric($return))
  {
    $return = "NULL";
  }

  $fleet = selectsqlline("select * from userfleet uf left join fleetstatus fs on(fs.fid = uf.fid) where uf.uid = $uid AND fs.fleetnum = $fleetnum");
  if(!$fleet) return;
  if($status != 2 || $return_flight) {
    query("delete from inc_deffer where defferid = $fleet[fid]");
  } elseif(is_numeric($gala) && is_numeric($pos)) {
    // m�glichen deffer eintragen
    $inc = selectsqlline("
      select * from user u 
      left join incomings i on (i.incid = u.uid) 
      where u.gala = $gala and u.pos = $pos
    ");
    if($inc['incid']) {
      $deffs = selectsqlline("select * from inc_deffer where defferid = ".$fleet['fid']);
      if (!$deffs) insertsql("insert into inc_deffer(defferid,incid) values(".$fleet["fid"].",".$inc["incid"].")");
      else query("update inc_deffer set incid = ".$inc["incid"]." where defferid = ".$fleet["fid"]);
    }
  }
  if(!$gala) $gala = "NULL";
  if(!$pos) $pos = "NULL";
  return query("
    update fleetstatus set status = $status,arrival = $seconds, gala = $gala,pos = $pos, ticks = $ticks, returntime = $return, return_flight = $return_flight
    where fid = $fleet[fid] AND fleetnum = $fleetnum
  ");
}*/

/*
function fleet_status_update($fid,$fleetnum,$status,$eta,$gala,$pos,$ticks,$return) {
  
  if(!is_numeric($fid)) {return;}
  if(!is_numeric($fleetnum)) {return;};
  if(!is_numeric($status)) {return;};
  if(!$gala){
    $gala = "NULL";
  }
  if(!$pos){
    $pos = "NULL";
  }
  if (isset($eta)) $seconds = "unix_timestamp()+".$eta*60; else $seconds = "NULL";
  if (!$ticks) $ticks = "NULL";
  if (!$return || !is_numeric($return))
  {
    $return = "NULL";
  }
  return query("
    update fleetstatus set status = $status,arrival = $seconds, gala = $gala,pos = $pos, ticks = $ticks, returntime = $return
    where fid = $fid
  ");
}

*/

#liefert die top scanner
function user_get_scannerlist($type,$top=10) {
    Assert::isNumeric($type);
    Assert::isNumeric($top);
    Assert::isTrue((int)$top >= 0);
  return selectsql("
    select * from user u
    left join galaxy ga on(ga.gala = u.gala)
    left join alliance a on(a.aid = ga.aid)
    where scantype = ".(int)$type." and svs > 0
    order by u.svs desc
    limit 0,".(int)$top."
  ");
}

function get_activity_points() {
  $hour = (int)date("H",time());
  $day = date("l",time());
  if($day == "Saturday" || $day == "Sunday") {
    $points = array(array(22,23,0,1,7,9,10),array(2,3,8),array(4,5,6,7));
  } else {
    $points = array(array(22,23,0,7),array(1,2,6),array(3,4,5));
  }
  $bonus = 0;
  if(in_array($hour,$points[0])) $bonus = 3;
  if(in_array($hour,$points[1])) $bonus = 5;
  if(in_array($hour,$points[2])) $bonus = 8;
  if(!$bonus) $bonus = 1;
  return $bonus;
}

function get_activity_timeout() {
  return 60*45;
}

function do_check_activity($userdata) {
#  echo "hour: $hour";
  $timeout = get_activity_timeout();
  if($userdata['activity_check']+$timeout < time() &&
      get_activity_points()
  ) {
    return true;
  }
  return false;
}

function user_activity_check($uid){
    Assert::isId($uid);  
  $check = time();
  $bonus = get_activity_points();
  if(!$bonus) return;
  query("
    update user set activity_check = unix_timestamp(), activity_points = activity_points + $bonus
    where uid = $uid
  ");
  query("insert into activity (uid,stamp,bonus) values($uid,now(),$bonus)");
}

function highscore_list($filter,$pages,$page=1,$rows=50) {
    Assert::isId($page);
    Assert::isId($rows);
    if($filter['hours'] || $filter['days']) {
        if($filter['hours']) {
            Assert::isNumeric($filter['hours']);
          $timeout = date("Y-m-d H:m:s",time()-(int)$filter['hours']*60*60);
        } else {
            Assert::isNumeric($filter['days']);
              $timeout = date("Y-m-d H:m:s",time()-(int)$filter['days']*24*60*60);
        }
        $count = selectsql("
              select count(*) as count
              from user u
              left join activity a on (a.uid = u.uid)
              where a.stamp > '$timeout'
              group by u.uid
        ");
        $count = count($count);
        #Seitenamzahl berechnen
        $pages = ceil($count/$rows);
        #Seite checken
        if ($pages < $page && $pages != 0) {
          $page = $pages;
        }
        $result = selectsql("
          select u.*,ga.*,a.*,sum(ac.bonus) as activity_points
          from user u
          left join activity ac on (ac.uid = u.uid)
          left join galaxy ga on (ga.gala = u.gala)
          left join alliance a on(a.aid = ga.aid)
          where ac.stamp > '$timeout'
          group by u.uid
          order by activity_points desc, u.nick asc
          LIMIT ".($rows*($page-1)).",$rows
        ");
    } else {
        $count = selectsqlLine("
              select count(*) as count
              from user u
        ");
        #Seitenamzahl berechnen
        $pages = ceil($count['count']/$rows);
        #Seite checken
        if ($pages < $page && $pages != 0) {
          $page = $pages;
        }
        $result = selectsql("
          select * from user u
          left join galaxy ga on (ga.gala = u.gala)
          left join alliance a on(a.aid = ga.aid)
          order by u.activity_points desc, u.nick asc
          LIMIT ".($rows*($page-1)).",$rows
        ");
    }
    for($i=0;$i < count($result);$i++) {
        $result[$i]['place'] = $rows*($page-1) + $i + 1;
    }
    return $result;
}
?>