<?PHP

require_once(FUNCTION_DIR.'/functions.php');
require_once(DATABASE_DIR.'/db.takscreen.php');

function listScans($filter,$pages,$page=1,$rows=10) {
	
  if ($page < 0 || $rows < 0 || !is_numeric($page) || !is_numeric($rows)) return;
  $awhere = array();
  $owhere = array();
  # 10 stunden
  $timeout = time()-(10*60*60);
  if ($filter['hideold']) {
		$owhere =
      "(s.sector_time > $timeout) OR
      (s.mili_time > $timeout) OR
      (s.unit_time > $timeout) OR
      (s.news_time > $timeout) OR
      (s.gscan_time > $timeout)";
    $get =
          "(s.sector_time > $timeout) as hassector,
          (s.mili_time > $timeout) as hasmili,
          (s.unit_time > $timeout) as hasunit,
          (s.news_time > $timeout) as hasnews,
          (s.gscan_time > $timeout) as hasgscan";

  } else {
    $get =
          "(s.sector_time IS NOT NULL) as hassector,
          (s.mili_time IS NOT NULL) as hasmili,
          (s.unit_time IS NOT NULL) as hasunit,
          (s.news_time IS NOT NULL) as hasnews,
          (s.gscan_time IS NOT NULL) as hasgscan";
    $owhere =
      "(s.sector_time is not null) OR
      (s.mili_time is not null) OR
      (s.unit_time is not null) OR
      (s.news_time is not null) OR
      (s.gscan_time is not null)";
  }
	if ($filter['gala'] && is_numeric($filter['gala'])) {
		$awhere[] = "s.gala = ".$filter['gala'];
	}
	// galaxien ausblenden
	if ($filter['except_galas'] && is_array($filter['except_galas'])) {
		$awhere[] = "s.gala NOT IN (".join(",",$filter['except_galas']).")";
	}
	if ($filter['punkte'] && is_numeric($filter['punkte'])) {
		$awhere[] = "s.sector_punkte >= ".$filter['punkte'];
	}
	if ($filter['macht'] && is_numeric($filter['macht'])) {
		$awhere[] = "s.sector_macht >= ".$filter['macht'];
	}
	if ($filter['exen'] && is_numeric($filter['exen'])) {
		$awhere[] = "(s.sector_metall + s.sector_kristall >= ".$filter['exen'].")";
	}
	if ($filter['hassektor']) {
		$awhere[] = "(s.sector_time is not null)";
	}
	if ($filter['sids']) {
		if(!is_array($filter['sids'])) $filter['sids'] = array($filter['sids']);
	  $awhere[] = "(s.sid IN (".join(",",$filter['sids'])."))";
	}
	
	if (!$filter['showattackscans']) {
		$awhere[] = "(a.access_time is null or a.access_time < unix_timestamp())";
	}
  if($owhere){
    $awhere[] = "(".$owhere.")";
  }
  if ($awhere) {
    $where = " WHERE ".join(" AND ",$awhere);
  }
  #order by
	if ($filter['order'] == "asc") $order = " ASC ";
	else $order = " DESC ";
	if ($filter['sort'] == "koords") $sort = " s.gala ".$order.", s.pos ".$order;
	elseif ($filter['sort'] == "exen") $sort = " sector_exen ".$order;
	else $sort = " s.gala ".$order;

	$count = selectsqlLine("
	 select count(s.sid) as count from scans s
          left join attack_target at on (at.sid = s.sid)
	        left join attack a on (a.attid = at.attid)
    ".$where);
#  echo "count: ".$count['count']."<br>";
#  print_r($where."<br>");
  #Seitenamzahl berechnen
	$pages = ceil($count['count']/$rows);
	#Seite checken
	if ($pages < $page && $pages != 0) {
		$page = $pages;
	}
	$return = selectsql("
          select s.*,f.*,at.closed,(s.sector_metall+s.sector_kristall) as sector_exen,u.uid,u.gala as atter_gala,u.pos as atter_pos,u.nick as atter_nick,
          ".$get."
					from scans s
          left join fleet f on (f.fid = s.unit_fid)
          left join attack_target at on (at.sid = s.sid)
	        left join attack a on (a.attid = at.attid)
	        left join attack_atter aa on (aa.sid = s.sid)
	        left join user u on (u.uid = aa.uid)
          ".$where."
				order by ".$sort."
        LIMIT ".($rows*($page-1)).",$rows
        ");
  if (count($return)) {
    for($i=0;$i < count($return);$i++) {
      $return[$i] = formatScan($return[$i]);
    }
  }
	return $return;
}

function getScan($filter,$timeout=null) {
	
  $awhere = array();
  $owhere = array();
  if ($filter['hideold']) {
    if (!$timeout){
      # 10 stunden
      $timeout = 600;
    }
    $timeout = time()-($timeout*60);
    $owhere =
      "(s.sector_time > $timeout) OR
      (s.mili_time > $timeout) OR
      (s.unit_time > $timeout) OR
      (s.news_time > $timeout) OR
      (s.gscan_time > $timeout)";
    $get =
          "(s.sector_time > $timeout) as hassector,
          (s.mili_time > $timeout) as hasmili,
          (s.unit_time > $timeout) as hasunit,
          (s.news_time > $timeout) as hasnews,
          (s.gscan_time > $timeout) as hasgscan";

  } else {
    $get =
          "(s.sector_time IS NOT NULL) as hassector,
          (s.mili_time IS NOT NULL) as hasmili,
          (s.unit_time IS NOT NULL) as hasunit,
          (s.news_time IS NOT NULL) as hasnews,
          (s.gscan_time IS NOT NULL) as hasgscan";
  }
	if (is_numeric($filter['gala']) && $filter['gala'] > 0) {
		$awhere[] = "s.gala = ".$filter['gala'];
	}
	if (is_numeric($filter['pos']) && $filter['pos'] > 0) {
		$awhere[] = "s.pos = ".$filter['pos'];
	}
  if (is_numeric($filter['sid']) && $filter['sid'] > 0) {
    $awhere[] = "s.sid = ".$filter['sid'];
  }
	if (strlen(trim($filter['nick'])) > 0) {
		$awhere[] = "s.nick = ".addslashes(trim($filter['nick']));
	}
  if($owhere){
    $awhere[] = "(".$owhere.")";
  }
	if (!$filter['showattackscans']) {
		$awhere[] = "(a.access_time is null or a.access_time < unix_timestamp())";
	}
  if ($awhere) {
    $where = " WHERE ".join(" AND ",$awhere);
  } else {
    #kein filter gesetzt
    return;
  }
	$return = selectsqlLine("
          select s.*,f.*,(s.sector_metall+s.sector_kristall) as sector_exen,u.uid,u.gala as atter_gala,u.pos as atter_pos,u.nick as atter_nick,at.closed,
          ".$get."
					from scans s
          left join fleet f on (f.fid = s.unit_fid)
          left join attack_target at on (at.sid = s.sid)
	        left join attack a on (a.attid = at.attid)
	        left join attack_atter aa on (aa.sid = s.sid)
	        left join user u on (u.uid = aa.uid)
          ".$where."
        ");
	return formatScan($return);
}


function getPrevGala($gala,$hideold) {
	
  $timeout = time()-(600*60);
  if ($hideold) {
    $result = selectsqlLine("
      SELECT DISTINCT max( gala ) AS prevgala
      FROM scans s
      WHERE
      (
        (sector_time > $timeout)
        OR
        (mili_time > $timeout)
        OR
        (unit_time > $timeout)
        OR
        (news_time > $timeout)
        OR
        (gscan_time > $timeout)
      )
      AND
        s.gala < $gala
      ");
  	if (!$result['prevgala'])
  		$result = selectsqlLine("
        SELECT DISTINCT max( gala ) AS prevgala
        FROM scans s
        WHERE
        (
          (sector_time > $timeout)
          OR
          (mili_time > $timeout)
          OR
          (unit_time > $timeout)
          OR
          (news_time > $timeout)
          OR
          (gscan_time > $timeout)
        )
  					");
  } else {
    $result = selectsqlLine("
      SELECT DISTINCT max( gala ) AS prevgala
      FROM scans s
      WHERE
      (
        (sector_time IS NOT NULL)
        OR
        (mili_time IS NOT NULL)
        OR
        (unit_time IS NOT NULL)
        OR
        (news_time IS NOT NULL)
        OR
        (gscan_time IS NOT NULL)
      )
      AND
        s.gala < $gala
                  ");
    if (!$result['prevgala'])
      $result = selectsqlLine("
      SELECT DISTINCT max( gala ) AS prevgala
      FROM scans s
      WHERE
      (
        (sector_time IS NOT NULL)
        OR
        (mili_time IS NOT NULL)
        OR
        (unit_time IS NOT NULL)
        OR
        (news_time IS NOT NULL)
        OR
        (gscan_time IS NOT NULL)
      )
      ");
  }
	return $result['prevgala'];
}

function getNextGala($gala,$hideold) {
	
  $timeout = time()-(600*60);
  if ($hideold) {
    $result = selectsqlLine("
      SELECT DISTINCT min( gala ) AS nextgala
      FROM scans s
      WHERE
      (
        (sector_time > $timeout)
        OR
        (mili_time > $timeout)
        OR
        (unit_time > $timeout)
        OR
        (news_time > $timeout)
        OR
        (gscan_time > $timeout)
      )
      AND
        s.gala > $gala
      group by s.gala
      ");
    	if (!$result['nextgala'])
    		$result = selectsqlLine("
          SELECT DISTINCT min( gala ) AS nextgala
          FROM scans s
          WHERE
          (
            (sector_time > $timeout)
            OR
            (mili_time > $timeout)
            OR
            (unit_time > $timeout)
            OR
            (news_time > $timeout)
            OR
            (gscan_time > $timeout)
          )
          group by s.gala
  				");
    } else {
    $result = selectsqlLine("
      SELECT DISTINCT min( gala ) AS nextgala
      FROM scans s
      WHERE
      (
        (sector_time IS NOT NULL)
        OR
        (mili_time IS NOT NULL)
        OR
        (unit_time IS NOT NULL)
        OR
        (news_time IS NOT NULL)
        OR
        (gscan_time IS NOT NULL)
      )
      AND
        s.gala > $gala
      group by s.gala
     ");
      if (!$result['nextgala'])
        $result = selectsqlLine("
          SELECT DISTINCT min( gala ) AS nextgala
          FROM scans s
          WHERE
          (
        (sector_time IS NOT NULL)
        OR
        (mili_time IS NOT NULL)
        OR
        (unit_time IS NOT NULL)
        OR
        (news_time IS NOT NULL)
        OR
        (gscan_time IS NOT NULL)
          )
        group by s.gala
          ");
    }
  	return $result["nextgala"];
}

/*
function getScan($gala,$pos) {
	
	return selectsqlLine("
  select s.*,
					 (sector_metall+sector_kristall) as exen,
					(sector_time IS NOT NULL) as hassector,
					(mili_time IS NOT NULL) as hasmili,
					(unit_time IS NOT NULL) as hasunit,
					(news_time IS NOT NULL) as hasnews,
					(gscan_time IS NOT NULL) as hasgscan
					from scans s
					where s.gala = $gala and s.pos = $pos");
}
*/

function addScans($gala,$pos_list) {
	
	if (!is_numeric($gala)) return;
  if(!$pos_list) return;
  if(!is_array($pos_list)) $pos_list = array($pos_list);
  $sid_list = array();
  foreach($pos_list as $pos) {
    $sid_list[] = insertSQL("insert into scans (gala,pos) values ($gala,$pos)");
  }
  return $sid_list;
}

function getScansBySid($sid_list){
  
  if(!$sid_list) return;
  if(!is_array($sid_list)) $sid_list = array($sid_list);
  if(!count($sid_list))return;
  return selectsql("select * from scans where sid IN (".join(",",$sid_list).") order by gala,pos");
}
/*
function updateMainScan($sid,$nick){
  
  if (!is_numeric($sid)) return;
  if (!$nick) $nick = "NULL"; else {
    $nick = "'".addslashes($nick)."'";
    #nicknames unique halten
    query("update scans set nick = NULL WHERE nick = $nick");
  }
  return query("update scans set nick = $nick where sid = $sid");
}
*/

function getScansByGala($gala) {
	
	if (!is_numeric($gala) || $gala < 1) return;
	return selectsql("select s.* from scans s
							where gala = $gala");
}

function formatScan($data,$timeout_hours=4) {
  
  if(!is_array($data) || !$data) return;
	
	// timeout
	$timeout = time() - $timeout_hours*60*60;
	$data['timeout_hours'] = $timeout_hours;
	
	if ($data['hassector']) {
    $metall = $data['sector_metall'];
    $kristall = $data['sector_kristall'];
    for($i=0;$i < 5;$i++){
      $metalllost = (int)floor($metall * 0.1);
      $kristalllost = (int)floor($kristall * 0.1);
      $data['sector_metall_5'] += $metalllost;
      $data['sector_kristall_5'] += $kristalllost;
      $metall -= $metalllost;
      $kristall -= $kristalllost;
    }
    $data['sector_metall_10'] = $data['sector_metall_5'];
    $data['sector_kristall_10'] = $data['sector_kristall_5'];
    for($i=0;$i < 5;$i++){
      $metalllost = (int)floor($metall * 0.1);
      $kristalllost = (int)floor($kristall * 0.1);
      $data['sector_metall_10'] += $metalllost;
      $data['sector_kristall_10'] += $kristalllost;
      $metall -= $metalllost;
      $kristall -= $kristalllost;
    }
    $data['sector_exen_5'] = $data['sector_metall_5']+$data['sector_kristall_5'];
    $data['sector_exen_10'] = $data['sector_metall_10']+$data['sector_kristall_10'];
    $data['sector_attexen'] = $data['sector_exen'] * 3;
    $data['sector_punkte2'] = substr_replace(strrev(chunk_split(strrev($data['sector_punkte']),3,'.')),'',0,1);
		$data['sector_scanage'] = getscanage($data['sector_time']);
		$data['sector_timeout'] = $data['sector_time'] < $timeout;
	}
  if ($data['hasunit']) {
		$data['unit_scanage'] = getscanage($data['unit_time']);;
		$data['unit_timeout'] = $data['unit_time'] < $timeout;
	}
	if ($data['hasgscan']) {
		$data['gscan_scanage'] = getscanage($data['gscan_time']);
		$data['gscan_timeout'] = $data['gscan_time'] < $timeout;
	}
	if ($data['hasmili']){
    $data['mili_fleets'] = miliscan_fleet_get($data['sid']);
		$data['mili_scanage'] = getscanage($data['mili_time']);
		$data['mili_timeout'] = $data['mili_time'] < $timeout;
	}
	if ($data['hasnews']){
		$data['news_scanage'] = getscanage($data['news_time']);
		$data['news_timeout'] = $data['news_time'] < $timeout;
	}
  return $data;
}
/*
function getScan($sid,$time=null) {
	
	if (!is_numeric($sid) || $sid < 1) {return;}
  if (is_numeric($time) && $time > 0) {
    $timeout = time()-$time*60;
    $owhere[] =
      "(s.sector_time > $timeout) OR
      (s.mili_time > $timeout) OR
      (s.unit_time > $timeout) OR
      (s.news_time > $timeout) OR
      (s.gscan_time > $timeout)";
    $get =
          "(s.sector_time > $timeout) as hassector,
          (s.mili_time > $timeout) as hasmili,
          (s.unit_time > $timeout) as hasunit,
          (s.news_time > $timeout) as hasnews,
          (s.gscan_time > $timeout) as hasgscan";

  } else {
    $get =
          "(s.sector_time IS NOT NULL) as hassector,
          (s.mili_time IS NOT NULL) as hasmili,
          (s.unit_time IS NOT NULL) as hasunit,
          (s.news_time IS NOT NULL) as hasnews,
          (s.gscan_time IS NOT NULL) as hasgscan";
  }
	if(count($owhere)) {
    $awhere = " AND ".join(" AND ",$owhere);
  }
  $data = selectsqlLine("select *,".$get.",
	            (sector_kristall+sector_metall) as sector_exen
	             from scans s
							 where s.sid = $sid $awhere ");
	return formatScan($data);
}
*/
#get fleet, veraltet
function getFleet($fid) {
	
	if (!$fid || !is_numeric($fid)) return;
	return selectsqlLine("select * from fleet where fid = $fid");
}


// veraltet !
function FleetCopy($src,$dst=0) {
  
	if (!$src || !is_numeric($src)) return;
  $fleet = getFleet($src);
  if (!$fleet) return;
	if ($dst) {
    return fleet_update($dst,$fleet);
  } else {
    return fleet_add($fleet);
  }
}

#add fleet
function addFleet($kleptoren=0, $cancris=0, $fregatten=0, $zerstoerer=0, $kreuzer=0, $schlachter=0, $traeger=0,
					$jaeger=0, $bomber=0)
{
	
  if (!is_numeric($kleptoren)) $kleptoren = 0;
  if (!is_numeric($cancris)) $cancris = 0;
  if (!is_numeric($fregatten)) $fregatten = 0;
  if (!is_numeric($zerstoerer)) $zerstoerer = 0;
  if (!is_numeric($kreuzer)) $kreuzer = 0;
  if (!is_numeric($schlachter)) $schlachter = 0;
  if (!is_numeric($traeger)) $traeger = 0;
  if (!is_numeric($jaeger)) $jaeger = 0;
  if (!is_numeric($bomber)) $bomber = 0;
	return insertSQL("insert into fleet (kleptoren, cancris, fregatten, zerstoerer, kreuzer,
								schlachter, traeger, jaeger, bomber)
							values(	$kleptoren, $cancris, $fregatten, $zerstoerer, $kreuzer,
									$schlachter, $traeger, $jaeger, $bomber)");
}

function deleteFleet($fid) {
  
  if (is_array($fid)){
    $fids = join(",",$fid);
    if ($fids) {
      return query("delete from fleet where fid IN ($fids)");
    }
  } else {
    if (!is_numeric($fid)) return;
    return query("delete from fleet where fid = $fid");
  }
}

#update fleet
function updateFleet($fid,$kleptoren=0, $cancris=0, $fregatten=0, $zerstoerer=0, $kreuzer=0, $schlachter=0, $traeger=0,
					$jaeger=0, $bomber=0)
{
	
  if (!is_numeric($fid)) return;
  if (!is_numeric($kleptoren)) $kleptoren = 0;
  if (!is_numeric($cancris)) $cancris = 0;
  if (!is_numeric($fregatten)) $fregatten = 0;
  if (!is_numeric($zerstoerer)) $zerstoerer = 0;
  if (!is_numeric($kreuzer)) $kreuzer = 0;
  if (!is_numeric($schlachter)) $schlachter = 0;
  if (!is_numeric($traeger)) $traeger = 0;
  if (!is_numeric($jaeger)) $jaeger = 0;
  if (!is_numeric($bomber)) $bomber = 0;
	return query("update fleet set
						kleptoren = $kleptoren,
						cancris = $cancris,
						fregatten = $fregatten,
						zerstoerer = $zerstoerer,
						kreuzer = $kreuzer,
						schlachter = $schlachter,
						traeger = $traeger,
						jaeger = $jaeger,
						bomber = $bomber
				where fid = $fid");
}

/*
#add sector scan
function addSectorScan($sid,$punkte,$kristall, $metall, $roids, $ships, $deff, $prec, $svs, $scanner) {
	
	if (!$sid || !is_numeric($sid)) return;
  if (!is_numeric($punkte)) return;
  if (!is_numeric($kristall)) return;
  if (!is_numeric($metall)) return;
  if (!is_numeric($roids)) return;
  if (!is_numeric($ships)) return;
  if (!is_numeric($deff)) return;
  if (!is_numeric($prec)) return;
  if (!is_numeric($svs)) $svs = "NULL";
  if (isset($scanner) && strlen($scanner)) $scanner = "'".addslashes($scanner)."'"; else $scanner = "NULL";
  return insertSQL("insert into scansector (sid, kristall, metall, punkte, ships, prec, scanner, svs, roids, deff, time)
							VALUES ($sid,$kristall,$metall,$punkte, $ships, $prec, $scanner, $svs, $roids, $deff, unix_timestamp())");
}

#update sector scan
function updateSectorScan($sid,$punkte,$kristall, $metall, $roids, $ships, $deff, $prec, $svs, $scanner) {
	
	if (!$sid || !is_numeric($sid)) return;
  if (!is_numeric($punkte)) return;
  if (!is_numeric($kristall)) return;
  if (!is_numeric($metall)) return;
  if (!is_numeric($roids)) return;
  if (!is_numeric($ships)) return;
  if (!is_numeric($deff)) return;
  if (!is_numeric($prec)) return;
  if (!is_numeric($svs)) $svs = "NULL";
  if (isset($scanner) && strlen($scanner)) $scanner = "'".addslashes($scanner)."'"; else $scanner = "NULL";
	return query("update scansector
						SET punkte = $punkte,
						kristall = $kristall,
						metall = $metall,
						roids = $roids,
						ships = $ships,
						deff = $deff,
						prec = $prec,
						svs = $svs,
						scanner = $scanner,
						time = unix_timestamp()
					Where sid = $sid");
}

#add unitscan
function addUnitScan($sid,$fleet,$prec,$svs,$scanner) {
	
	if (!$sid || !is_numeric($sid)) return;
  if (!is_numeric($prec)) return;
  if (!is_numeric($svs)) $svs = "NULL";
  if (isset($scanner) && strlen($scanner)) $scanner = "'".addslashes($scanner)."'"; else $scanner = "NULL";
  $fid =	addFleet($fleet['kleptoren'],$fleet['cancris'],$fleet['fregatten'],$fleet['zerstoerer'],
        $fleet['kreuzer'],$fleet['schlachter'],$fleet['traeger'],$fleet['jaeger'],$fleet['bomber']);
  if (!$fid) return; #irgendwas schiefgelaufen beim einf�gen
	return insertSQL("
    insert into scanunit (sid,fid,prec,svs,scanner,time) 
    values($sid,$fid,$prec,$svs,$scanner,unix_timestamp())
  ");
}
#update unit scan
function updateUnitScan($sid,$fleet,$prec,$svs,$scanner) {
	
	if (!$sid || !is_numeric($sid)) return;
  if (!is_numeric($prec)) return;
  if (!is_numeric($svs)) $svs = "NULL";
  if (isset($scanner) && strlen($scanner)) $scanner = "'".addslashes($scanner)."'"; else $scanner = "NULL";

  $fid = selectsqlline("select * from scanunit where sid = $sid");
  if (!$fid) return;
  $fid = $fid['fid'];
  updateFleet($fid,$fleet['kleptoren'],$fleet['cancris'],$fleet['fregatten'],$fleet['zerstoerer'],
        $fleet['kreuzer'],$fleet['schlachter'],$fleet['traeger'],$fleet['jaeger'],$fleet['bomber']);
	
  return query("
    update scanunit set
      prec = $prec,
      svs = $svs,
      scanner = $scanner,
      time = unix_timestamp()
      where sid = $sid
    ");
}

#add gscan
function addGScan($sid,$horus,$rubium,$pulsar,$coon,$centurion,$prec,$svs, $scanner) {
	
	if (!$sid || !is_numeric($sid)) return;
  if (!is_numeric($horus)) $horus = 0;
  if (!is_numeric($rubium)) $rubium = 0;
  if (!is_numeric($pulsar)) $pulsar = 0;
  if (!is_numeric($coon)) $coon = 0;
  if (!is_numeric($centurion)) $centurion = 0;
  if (!is_numeric($prec)) return;
  if (!is_numeric($svs)) $svs = "NULL";
  if (isset($scanner) && strlen($scanner)) $scanner = "'".addslashes($scanner)."'"; else $scanner = "NULL";
	return insertSQL("
    insert into scangscan (sid,horus,rubium, pulsar, coon, centurion, prec, svs, scanner, time)
		values($sid,$horus,$rubium,$pulsar,$coon,$centurion,$prec,$svs,$scanner,unix_timestamp())
  ");
}

#update gscan
function updateGScan($sid,$horus,$rubium,$pulsar,$coon,$centurion,$prec,$svs, $scanner) {
	
	if (!$sid || !is_numeric($sid)) return;
  if (!is_numeric($horus)) $horus = 0;
  if (!is_numeric($rubium)) $rubium = 0;
  if (!is_numeric($pulsar)) $pulsar = 0;
  if (!is_numeric($coon)) $coon = 0;
  if (!is_numeric($centurion)) $centurion = 0;
  if (!is_numeric($prec)) return;
  if (!is_numeric($svs)) $svs = "NULL";
  if (isset($scanner) && strlen($scanner)) $scanner = "'".addslashes($scanner)."'"; else $scanner = "NULL";
	return query("
    update scangscan set 
      horus = $horus,
      rubium=$rubium,
      pulsar = $pulsar,
      coon = $coon,
      centurion = $centurion,
      prec = $prec,
      svs = $svs,
      scanner = scanner,
      time = unix_timestamp()
      where sid = $sid");
}

#add newsscan
function addNewsScan($sid,$newsdata,$prec,$svs,$scanner) {
	
	if (!$sid || !is_numeric($sid)) return;
  if (!is_numeric($prec)) return;
  if (!is_numeric($svs)) $svs = "NULL";
  if (isset($scanner) && strlen($scanner)) $scanner = "'".addslashes($scanner)."'"; else $scanner = "NULL";
  $newsdata = addslashes($newsdata);
	return insertSQL("
    insert into scannews (sid,newsdata,prec,svs,scanner,time)
	  values($sid,'$newsdata',$prec,$svs,$scanner,unix_timestamp())
  ");
}

#update news scan
function updateNewsScan($sid,$newsdata,$prec,$svs,$scanner) {
	
	if (!$sid || !is_numeric($sid)) return;
  if (!is_numeric($prec)) return;
  if (!is_numeric($svs)) $svs = "NULL";
  if (isset($scanner) && strlen($scanner)) $scanner = "'".addslashes($scanner)."'"; else $scanner = "NULL";
  $newsdata = addslashes($newsdata);
	return query("
    update scannews set
      newsdata = '$newsdata',
      prec = $prec,
      svs = $svs,
      scanner = $scanner,
      time = unix_timestamp()
		where sid = $sid
  ");
}

#add mili
# orbit: orbithash
# fleet1: fleet1hash
# fleet2: fleet2hash
function addMiliScan($sid,$fleets,$prec,$svs,$scanner) {
	
	if (!$sid || !is_numeric($sid)) return;
  if (!is_numeric($prec)) return;
  if (!is_numeric($svs)) $svs = "NULL";
  if (isset($scanner) && strlen($scanner)) $scanner = "'".addslashes($scanner)."'"; else $scanner = "NULL";

  for($i=0;$i < 3;$i++){
    miliscan_fleet_add($sid,$i,$fleets[$i]);
  }

  return insertSQL("
    insert into scanmili (sid,scanner, prec, svs, time)
		  values($sid,$scanner, $prec, $svs, unix_timestamp())
  ");
}
*/

function miliscan_fleet_add($sid,$fleetnum,$fleet) {
      $logger = & LoggerManager::getLogger("db.scans");
      $logger->debug(array(
          "sid" => $sid,
          "fleetnum" => $fleetnum,
          "fleet" => $fleet
      ));
  
      Assert::isId($sid);
      Assert::isNumeric($fleetnum);
  
	$fid = fleet_add($fleet);
  
	$insert = array("sid" => $sid,"fid" => $fid,"num" => $fleetnum);
  
	if (strlen($fleet['type'])) $insert['type'] = "'".addslashes($fleet['type'])."'";
	if (strlen($fleet['dir'])) $insert['dir'] = "'".addslashes($fleet['dir'])."'";
	if (strlen($fleet['status'])) $insert['status'] = $fleet['status'];
	if (strlen($fleet['return_flight'])) $insert['return_flight'] = $fleet['return_flight'];
  insertSQL("
    insert into scanmili_fleet (".join(",",array_keys($insert)).")
      values (".join(",",$insert).")
  ");
  return $fid;
}

function miliscan_fleet_update($fid,$fleet) {
      $logger = & LoggerManager::getLogger("db.scans");
      $logger->debug(array(
          "function"=>"miliscan_fleet_update",
          "fid" => $fid,
          "fleet" => $fleet
      ));
    Assert::isId($fid);
      
	fleet_update($fid,$fleet);
  
  $update = array();
  
  if (strlen($fleet['type'])) $update[] = "type = '".addslashes($fleet['type'])."'";
  else $update[] = "type = NULL";
  if (strlen($fleet['dir'])) $update[] = "dir = '".addslashes($fleet['dir'])."'";
  else $update[] = "dir = NULL";
  if (strlen($fleet['status'])) $update[] = "status = ".$fleet['status'];
  else $update[] = "status = NULL";
  if (strlen($fleet['return_flight'])) $update[] = "return_flight = ".$fleet['return_flight'];
  else $update[] = "return_flight = NULL";
  query("
      update scanmili_fleet 
      set ".join(",",$update)."
      where fid = $fid
  ");
  $logger->debug("miliscan_fleet_update done");
}

function miliscan_fleet_get($sid) {
    Assert::isId($sid);
  return  selectsql("
    select *,u.gala as tgala,u.pos as tpos,u.uid from scanmili_fleet mf
      left join fleet f on (f.fid = mf.fid)
      left join user u on(u.nick = mf.dir)
      where mf.sid = $sid
      order by mf.num ASC
  ");
}

function miliscan_fleet_get_bykoords($gala,$pos,$num) {
    Assert::isId($gala);
    Assert::isId($pos);
    Assert::isNumeric($num);
return  selectsqlline("
    select * from scans s
      left join scanmili_fleet mf using(sid)
      left join fleet f on (f.fid = mf.fid)
      where s.gala = $gala and s.pos = $pos and mf.num = $num
  ");
}

/*
#update mili
function updateMiliScan($sid,$fleets,$prec,$svs,$scanner) {
	
	if (!$sid || !is_numeric($sid)) return;
  if (!is_numeric($prec)) return;
  if (!is_numeric($svs)) $svs = "NULL";
  if (isset($scanner) && strlen($scanner)) $scanner = "'".addslashes($scanner)."'"; else $scanner = "NULL";

  $fleetlist = selectsql("select * from scanmili_fleet where sid = $sid order by num ASC");
  for($i=0;$i < 3;$i++){
    miliscan_fleet_update($fleetlist[$i]['fid'],$fleets[$i]);
  }

  return query("
      update scanmili set 
        prec = $prec,
        svs = $svs,
        scanner = $scanner,
        time = unix_timestamp()
			where sid = $sid
    ");
}
*/

function existScan($gala,$pos) {
  
  if (!is_numeric($gala) || $gala < 1) return;
  if (!is_numeric($pos) || $pos < 1) return;
  return selectsqlline("
    select *,
    (s.sector_time IS NOT NULL) as hassector,
    (s.mili_time IS NOT NULL) as hasmili,
    (s.unit_time IS NOT NULL) as hasunit,
    (s.news_time IS NOT NULL) as hasnews,
    (s.gscan_time IS NOT NULL) as hasgscan
     from scans s
     where s.gala = $gala and s.pos = $pos
          ");
}

function updateScan($scan) {
      $logger = & LoggerManager::getLogger("db.scans");
      $logger->debug(array("function" => "updateScan","scan" => $scan));
	if (!is_numeric($scan['gala']) || !is_numeric($scan['pos'])) return;

  #scan exists
	$dbscan = existScan($scan['gala'],$scan['pos']);
  if(!$dbscan) {
    $sid = insertsql(" insert into scans (gala,pos,nick) values (".$scan['gala'].",".$scan['pos'].",'".addslashes($scan['nick'])."')");
    if(!$sid) return;
    $dbscan = array("sid" => $sid, "gala" => $scan['gala'], "pos" => $scan['pos']);
  }
  #sektorscan
	if ($scan['type'] == "sector") {
		#existiert schon -> update
    $val = array("sector_punkte" => $scan['punkte'], "sector_kristall" => $scan['kristall'],
                 "sector_metall" => $scan['metall'], "sector_roids" => $scan['roids'],
                 "sector_ships" => $scan['ships'], "sector_deff" => $scan['deff'],
                 "sector_prec" => $scan['prec'], "sector_svs" => $scan['svs'],
                 "sector_scanner" => $scan['scanner'], "sector_time" => time()
                 );
	}
	if ($scan['type'] == "unit") {
    if ($dbscan['hasunit'] && $dbscan['unit_fid']) {
      updateFleet(
        $dbscan['unit_fid'],
        $scan['kleptoren'],
        $scan['cancris'],
        $scan['fregatten'],
        $scan['zerstoerer'],
        $scan['kreuzer'],
        $scan['schlachter'],
        $scan['traeger'],
        $scan['jaeger'],
        $scan['bomber']
        );
      $fid = $dbscan['unit_fid'];
		} else {
      $fid = addFleet(
        $scan['kleptoren'],
        $scan['cancris'],
        $scan['fregatten'],
        $scan['zerstoerer'],
        $scan['kreuzer'],
        $scan['schlachter'],
        $scan['traeger'],
        $scan['jaeger'],
        $scan['bomber']
        );
      if(!$fid) return;
		}
    $val = array("unit_prec" => $scan['prec'], "unit_svs" => $scan['svs'], "unit_scanner" => $scan['scanner'],"unit_fid" => $fid, "unit_time" =>
time());
	}
	if ($scan['type'] == "gscan") {
    $val = array(
      "gscan_prec" => $scan['prec'],
      "gscan_svs" => $scan['svs'],
      "gscan_scanner" => $scan['scanner'],
      "gscan_horus" => $scan['horus'],
      "gscan_rubium" => $scan['rubium'],
      "gscan_coon" => $scan['coon'],
      "gscan_pulsar" => $scan['pulsar'],
      "gscan_centurion" => $scan['centurion'],
      "gscan_time" => time());
	}
	// miliscan updaten
	if ($scan['type'] == "mili") {
	  
    	if (miliscan_fleet_get($dbscan['sid'])) {
    		$fleetlist = selectsql("select * from scanmili_fleet where sid = ".$dbscan['sid']." order by num ASC");
    		for($i=0;$i < 3;$i++){
    			miliscan_fleet_update($fleetlist[$i]['fid'],$scan['fleets'][$i]);
    		}
    	} else {
            for($i=0;$i < 3;$i++){
                miliscan_fleet_add($dbscan['sid'],$i,$scan['fleets'][$i]);
            }
		}
		// 100% scan und user ist tic member
//		if($scan['prec'] == "100" && ($user = user_get_bypos($scan['gala'],$scan['pos']))) {
//      
//		} 
        $val = array("mili_prec" => $scan['prec'], "mili_svs" => $scan['svs'], "mili_scanner" => $scan['scanner'], "mili_time" => time());
	    $logger->debug(array("message"=>"mili update done","vals"=>$val));
    }
	if ($scan['type'] == "news") {
        $val = array("news_prec" => $scan['prec'], "news_svs" => $scan['svs'], "news_scanner" => $scan['scanner'],"news_newsdata" => $scan['newsdata'], 
        "news_time" => time());
	}
    if(!$dbscan['nick'] || strtolower($dbscan['nick']) != strtolower($scan['nick'])) $val['nick'] = $scan['nick'];
    if(strtolower($dbscan['nick']) != strtolower($scan['nick'])) {
        query("update scans set nick = null where lower(nick) = lower('".mysql_escape_string($scan['nick'])."')");
    }
    if(count($val)) {
        $updates = array();
        foreach($val as $key => $value) {
          if(!isset($value)) {
            $value = "NULL";
          } else {
            if(!is_numeric($value)) {
              $value = "'".mysql_escape_string($value)."'";
            }
          }
          $updates[] = "$key = $value";
        }
        $logger->debug(array("function"=>"updateScan","message"=>"before update scans","vals"=>$vals,"updates"=>$updates));;
        query("update scans set ".join(",",$updates)." where sid = ".$dbscan['sid']);
    }
  $logger->debug("update scan done");
  return $dbscan['sid'];
}

function scan_add($gala,$pos,$values=null) {
  if(!is_numeric($gala) || !is_numeric($pos)) return false;
  if($values && is_array($values)) {
    $check = array(
      "nick"
    );
    $insert = array();
    foreach ($values as $key => $val) {
      if(in_array($key,$check)) $insert[$key] = "'".mysql_escape_string($val)."'";
    }
    if($insert) {
      $sqlkeys = ",".join(",",array_keys($insert));
      $sqlvals = ",".join(",",array_values($insert));
    }
  }
  return insertsql("
    insert into scans (gala,pos".$sqlkeys.") values ($gala,$pos".$sqlvals.")");
}

function scan_update_nick($gala,$pos,$nick) {
  if(!$nick || !is_numeric($pos) || !is_numeric($gala)) return false;
  $scan = scan_get_bynick($nick);
  if($scan) {
    if($scan['gala'] != $gala || $scan['pos'] != $pos) {
      query("update scans set nick = null where gala = ".$scan['gala']." and pos = ".$scan['pos']);
    }
    return query("update scans set nick = '".mysql_escape_string(trim($nick))."' where gala = ".$gala." and pos = ".$pos);
  } else {
    $scan = getScan(array("gala" => $gala, "pos" => $pos));
    if($scan) {
      return query("update scans set nick = '".mysql_escape_string(trim($nick))."' where gala = ".$gala." and pos = ".$pos);
    } else {
      return scan_add($gala,$pos,array("nick" => $nick));
    }
  }
}

function scan_get_bynick($nick) {
  if(!$nick) return false;
  $nick = mysql_escape_string(trim($nick));
  return selectsqlline("select * from scans s where nick = '$nick'");
}

#liefert die Liste der Atter zu einem Target
/*
function getAtterlist($sid) {
  
  return selectsql("
    select u.nick,u.gala,u.pos,u.uid,a.time,a.fleetnum,
          DATE_FORMAT(a.start, '%H:%i') as starttime,
          DATE_FORMAT(a.start, '%d.%m.%y') as startdate,
          al.tag
    from atter a
    left join user u on (u.uid = a.uid)
    left join galaxy g on (g.gala = u.gala)
    left join alliance al on (al.aid = g.aid)
    where
      a.sid = $sid
    order by a.start ASC, a.fleetnum asc
  ");
}

#liefert das Target anhand uid/flotte oder liefert die targets eines users

function getAtter($uid,$num=null) {
  
  if ($num) {
    return selectsqlLine("
      select u.nick,u.uid,a.time,a.fleetnum,
            DATE_FORMAT(a.start, '%H:%i') as starttime,
            DATE_FORMAT(a.start, '%d.%m.%y') as startdate,
            s.gala,s.pos
      from atter a
      left join user u on (u.uid = a.uid)
      left join scans s on (s.sid = a.sid)
      where
        a.uid = $uid AND
        a.fleetnum = $num
    ");
  } else {
    return selectsql("
      select u.nick,u.uid,a.time,a.fleetnum,
            DATE_FORMAT(a.start, '%H:%i') as starttime,
            DATE_FORMAT(a.start, '%d.%m.%y') as startdate,
            s.gala,s.pos
      from atter a
      left join user u on (u.uid = a.uid)
      left join scans s on (s.sid = a.sid)
      where
        a.uid = $uid
      order by a.start asc, a.fleetnum asc
    ");
  }
}

#f�gt einen Atter hinzu

function addAtter($sid,$uid,$fleet,$time=null) {
  
  if ($fleet != 1 && $fleet != 2) {
    return -1;
  }
  if ($time) {
    if (preg_match("/(\d{1,2}):(\d{2})/i",$time,$data)) {
      $hour = date("G",mktime($data[1],$data[2],0,date("m"),date("d"),date("Y")));
      $acthour = date("G");
      if (  ($acthour > $hour) ||
            ($acthour == $hour && date("i") > $data[2])) {
        $dayoffset = 1;
      }
      $date = "'".date("Y-m-d H-i-s",mktime($data[1],$data[2],0,date("m"),date("d")+$dayoffset,date("Y")))."'";
    } else {
      return -1;
    }
  } else {
    $date = "NULL";
  }
  return insertsql("
    insert into atter (sid,uid,fleetnum,start,time)
    values ($sid,$uid,$fleet,$date,Now())
  ");
}

#setzt ein target auf closed
function closeTarget($id) {
  
  return query("
    update scans set closed = 1 where sid = $id
  ");
}

#setzt ein target auf open
function openTarget($id) {
  
  return query("
    update scans set closed = NULL where sid = $id
  ");
}
*/

#liefert nen scan anhand der sid
function getScanById($sid,$attackscans=false) {
  if(!$attackscans) {
    $where = " AND (a.access_time is null or a.access_time < unix_timestamp())";
  }
  return selectsqlline("
    select s.*,a.attid,a.owner,at.closed,u.uid,u.gala as atter_gala,u.pos as atter_pos,u.nick as atter_nick,(a.access_time is null or a.access_time < unix_timestamp()) as isopen
    from scans s
    left join attack_target at on (at.sid = s.sid)
    left join attack_atter aa on (aa.sid = s.sid)
    left join user u on (u.uid = aa.uid)
    left join attack a on (a.attid = at.attid)
    where s.sid = $sid $where
  ");
}



/*
#l�scht den atter
function deleteAtter($uid,$fleet) {
  
  $target = selectsqlline("
    select sid
    from atter
    where uid = $uid and fleetnum = $fleet");
  $target = $target['sid'];
  if (!$target) return;
  query("
    delete from atter
    where uid = $uid and fleetnum = $fleet
  ");
  $atter = selectsql("
    select * from atter
    where
      sid = $target
  ");
  if (!$atter){
    query("
      update scans set closed = NULL where sid = $target
    ");
  }
  return 1;
}
*/

/*
#l�scht veraltete reservierungen
function updateTargets($hours=8) {
  
  $date = $date = date("Y-m-d H-i-s",mktime(date("H")-$hours,date("i"),0,date("m"),date("d")+$dayoffset,date("Y")));
  query("delete from atter where time < '$date'");
  $targets = selectsql("
    select s.sid from scans s
    left join atter t on (t.sid = s.sid)
    where t.uid IS NULL and s.closed = 1
    group by s.sid
  ","sid");
  if ($targets){
    $where = join(",",$targets);
    query("
      update scans set closed = NULL where sid IN ($where)
    ");
  }
}
*/

?>
