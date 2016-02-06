<?php
# Taktikschirm
require_once("database/db.scans.php");
require_once("database/db.logfile.php");
require_once("database/db.gala.php");

function takscreen_info () {
  $incs = selectsqlline("
    select count(*) as count,count(distinct u.uid) as user from user u, fleet_status fs
    where 
      u.gala = fs.tgala and
      u.pos = fs.tpos and
      fs.status = 1 and 
      fs.return_flight = 0
  ");
  if ($incs) {
    $info['atter'] = $incs['count'];
    $info['incs'] = $incs['user'];
    $safe = selectsqlline("
      select count(distinct u.uid) as count from user u, fleet_status fs
      where 
        u.gala = fs.tgala and
        u.pos = fs.tpos and
        fs.status = 1 and 
        fs.return_flight = 0 and
        u.safe = 1
    ");
    $info['safe'] = $safe['count'];
    $info['unsafe'] = $incs['user'] - $info['safe'];
    
  }
  return $info;
}
/*
function deffer_list($incid,$fids=null){
  
  if (!is_numeric($incid)) return;
  if(isset($fids) && is_array($fids)) {
    $where = " AND fs.fid NOT IN (".join(",",$fids).")";
  }
  return selectsql("
    select id.*, id.egala as isextern,
    u.nick,a.tag,u.gala,u.pos,
    fs.arrival-unix_timestamp() as unixeta,
    fs.arrival,fs.fleetnum,fs.ticks,fs.returntime,fs.status,a.*,
    u.uid,u2.gala as ogala,a.aid,
    f.*
    from inc_deffer id
    left join fleetstatus fs on (fs.fid = id.defferid)
    left join userfleet uf on (uf.fid = fs.fid)
    left join user u on (u.uid = uf.uid)
    left join user u2 on (u2.uid = id.incid)
    left join galaxy ga on(ga.gala = u.gala)
    left join alliance a on(a.aid = ga.aid)
    left join fleet f on (f.fid = id.defferid)
    where id.incid = $incid $where
    order by arrival asc
  ");
}
*/
/*
function deffer_get($defferid) {
  
  if (!is_numeric($defferid)) return;
  return selectsqlline("
    select id.*, id.egala as isextern,
    u.nick,a.tag,u.gala,u.pos,
    fs.arrival-unix_timestamp() as unixeta,fs.*,
    u.uid,u2.gala as ogala,u2.pos as opos,a.aid,
    f.*
    from inc_deffer id
    left join fleetstatus fs on (fs.fid = id.defferid)
    left join userfleet uf on (uf.fid = fs.fid)
    left join user u on (u.uid = uf.uid)
    left join user u2 on (u2.uid = id.incid)
    left join galaxy ga on(ga.gala = u.gala)
    left join alliance a on(a.aid = ga.aid)
    left join fleet f on (f.fid = id.defferid)
    where id.defferid = $defferid
  ");
}

function deffer_recall($defferid) {
  
  if (!is_numeric($defferid)) return;
  $deffer = deffer_get($defferid);
  if ($deffer['isextern']) {
    query("delete from fleetstatus where fid = ".$defferid);
    deletefleet($defferid);
  } else {
    user_fleet_recall($defferid);
  }
  delete_fleet_filter($defferid);
  query("delete from inc_deffer where defferid = $defferid");
}

function deffer_sum($incid,$fids=null) {
  
  if (!is_numeric($incid)) return;
  if(isset($fids) && is_array($fids)) {
    $where = " AND f.fid NOT IN (".join(",",$fids).")";
  }
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
      inc_deffer id
      left join fleet f on (f.fid = id.defferid)
    where
      id.incid = $incid $where
    group by id.incid
  ");
}

function atter_sum($incid,$fids=null) {
  
  if (!is_numeric($incid)) return;
  if(isset($fids) && is_array($fids)) {
    $where = " AND ia.fid NOT IN (".join(",",$fids).")";
  }
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
      inc_atter ia
      left join fleet f on (f.fid = ia.fid)
    where
      ia.incid = $incid $where
    group by ia.incid
  ");
}

function deffer_intern_add($incid,$uid,$eta,$fleetnum,$ticks=20,$return=330) {
  
  if (!is_numeric($incid)) return;
  if (!is_numeric($uid)) return;
  if (!is_numeric($eta)) return;
  if (!is_numeric($fleetnum)) return;
  $fid = selectsqlline("
    select fs.fid
    from userfleet uf
    left join fleetstatus fs on(fs.fid = uf.fid)
    where uf.uid = $uid and fs.fleetnum = $fleetnum");
  $fid = $fid['fid'];
  #flottenstatus update
  $user = getUserByID($incid);
  user_fleet_status_update($uid,$fleetnum,2,0,$eta,$user['gala'],$user['pos'],$ticks,$return);
  $deffer = selectsqlline("select * from inc_deffer where defferid = $fid");
  if ($deffer){
    query("update inc_deffer set incid = $incid where defferid = $fid");
  } else {
    insertsql("
      insert into inc_deffer (defferid,incid)
      values ($fid,$incid)
    ");
  }
  return $fid;
}

function deffer_extern_add($incid,$nickname,$gala,$pos,$eta,$fleetnum,$fleet,$ticks=20) {
  
  if (!is_numeric($incid)) return;
  if (!is_numeric($gala)) return;
  if (!is_numeric($eta)) return;
  if (!is_numeric($fleetnum)) return;
  if (!is_numeric($pos)) return;
  $fid = addFleet($fleet['kleptoren'],
                  $fleet['cancris'],
                  $fleet['fregatten'],
                  $fleet['zerstoerer'],
                  $fleet['kreuzer'],
                  $fleet['schlachter'],
                  $fleet['traeger'],
                  $fleet['jaeger'],
                  $fleet['bomber']
                  );
  if (!$fid) {return;}
  $arrival = time()+$eta*60;
  insertsql("
    insert into inc_deffer(incid,defferid,enickname,egala,epos)
    values($incid,$fid,'$nickname',$gala,$pos)
  ");
  insertsql("
    insert into fleetstatus (arrival,status,fid,ticks,fleetnum)
    values($arrival,2,$fid,$ticks,$fleetnum)
  ");
  return $defferid;
}

function deffer_extern_update($incid,$defferid,$fleetnum,$time,$fleet,$ticks=20) {
  
  if (!is_numeric($incid)) return;
  if (!is_numeric($defferid)) return;
  if (!is_numeric($fleetnum)) return;
  if (!is_numeric($time)) return;
  if (!is_array($fleet)) return;
  updateFleet($defferid,
                $fleet['kleptoren'],
                  $fleet['cancris'],
                  $fleet['fregatten'],
                  $fleet['zerstoerer'],
                  $fleet['kreuzer'],
                  $fleet['schlachter'],
                  $fleet['traeger'],
                  $fleet['jaeger'],
                  $fleet['bomber']
                  );
  query("update fleetstatus set fleetnum = $fleetnum where fid = $defferid");
  $user = getUserByID($incid);
  fleet_status_update($defferid,$fleetnum,2,$time,$user['gala'],$user['pos'],$ticks,'');
}


function inc_get_data($id) {
  
  if (!is_numeric($id)) return;
  return selectsqlline("
    select i.*,u.gala as ogala, u.pos as opos, u.nick as onick,u.fleetupdate,u.deffupdate,a.tag,a.aid,
    u.horus,u.rubium,u.pulsar,u.coon,u.centurion,u.uid
    from incomings i
    left join user u on (u.uid = i.incid)
    left join galaxy ga on (ga.gala = u.gala)
    left join alliance a on (a.aid = ga.aid)
    where incid = $id
  ");
}

function inc_data_add($id) {
  
  if (!is_numeric($id)) return;
  return insertsql("
    insert into incomings (incid) values ($id)
  ");
}
*/

function user_set_safe($id,$safe) {
  if(!$id) return false;
  if($safe != 0 && $safe != 1) $safe = 0;
  if(!is_array($id)) $id = array($id);
  return query("
    update user set safe = $safe where uid IN (".join(",",$id).")
  ");
}

function inc_list($filter,&$pages,$page=1,$rows=10,$user=null) {
  
  if ($filter['order'] == "asc") {
    $order = "ASC";
  } else {
    $order = "DESC";
  }
  if ($filter['sort'] == "time") {
    $sort = "maxtime ".$order;
  } else {
    $sort = "u.gala ".$order.","."u.pos ".$order;
  }
  if ($filter['safe']) {
    $awhere[] = " u.safe = 0 ";
  }
  
  if ($filter['undertime'] && isset($user)) {
    $allygalas = getGalaListByAlly($user['aid'],true);
    $ohaving[] = "(u.gala = ".$user['gala']." and max(fs.arrival)-unix_timestamp() > 15300)";
    $ohaving[] = "(u.gala IN (".join(",",$allygalas).") and max(fs.arrival)-unix_timestamp() > 17100)";
    $ohaving[] = "(max(fs.arrival)-unix_timestamp() > 18900)";
    $having = "HAVING (".join(" OR ",$ohaving).")";
  }
  
  if ($filter['ally']) {
    $awhere[] = " g.aid = ".$filter['ally']." ";
  }
  if ($filter['gala']) {
    $awhere[] = " fs.tgala = ".$filter['gala']." ";
  }
  if ($awhere){
    $where = " where ".join("AND",$awhere);
    $where2 = join("AND",$awhere);
    $where3 = "AND ".join("AND",$awhere);
  }
  
  $victims = selectsql("
        select *,max(arrival) as maxtime
        from fleet_status fs, user u, galaxy g
        where 
        u.gala = fs.tgala and
        u.pos = fs.tpos and
        g.gala = u.gala and
        fs.status = 1 
        $where3
        group by u.uid
        $having
        order by $sort
        ","uid");
  
  #Seitenamzahl berechnen
#  echo " pages: ".$pages;
#  echo " page: ".$page;
  $pages = ceil(count($victims)/$rows);
  #Seite checken
  if ($pages < $page && $pages != 0) {
    $page = $pages;
  }
  if(!$victims) return array();
  $victims = array_splice($victims,($page-1)*$rows,$rows);
   
    #echo " pages: ".$pages;
    #echo " page: ".$page;
  return selectsql("
    select u.*,g.*,a.*,max(arrival) as maxtime
    from fleet_status fs, user u,galaxy g, alliance a
    where 
    u.gala = fs.tgala and
    u.pos = fs.tpos and
    g.gala = u.gala and
    a.aid = g.aid and 
    fs.return_flight != 1 
    and u.uid IN (".join(",",$victims).") 
    group by u.uid
    order by $sort
    "
  );
}


function inc_list_byuser($gala,$pos) {
  if(!is_numeric($gala) || !is_numeric($pos)) return false;
  return selectsql("
    select fs.*,fs.arrival - unix_timestamp() as unixeta,s.nick,u.uid,ga.aid,a.tag,f.*
    from fleet_status fs
    left join scans s on(s.gala = fs.gala and s.pos = fs.pos)
    left join fleet f on(f.fid = fs.fid)
    left join user u on(u.gala = fs.gala and u.pos = fs.pos)
    left join galaxy ga on(ga.gala = u.gala)
    left join alliance a on(a.aid = ga.aid)
    where 
    fs.tgala = $gala and fs.tpos = $pos and fs.return_flight = 0 
    ORDER BY status asc,arrival asc
    ");
}

/*
function atter_list($id,$fids=null) {
  
  if (!is_numeric($id)) return;
  if(isset($fids) && is_array($fids)) {
    $where = " AND ia.fid NOT IN (".join(",",$fids).")";
  }
  return selectsql("
    select ia.*,f.*,
    u.gala as ogala,
    arrival-unix_timestamp() as unixeta,
    s.sid,(s.mili_time > unix_timestamp()-60*450) as hasmili
    from inc_atter ia
    left join fleet f on (f.fid = ia.fid)
    left join user u on (u.uid = ia.incid)
    left join scans s on(s.gala = ia.igala AND s.pos = ia.ipos)
    where ia.incid = $id ".$where."
    order by arrival asc
  ");
}

function atter_list_bynick($nick) {
  
  return selectsql("
    select ia.*,f.*,
    u.gala as ogala,u.pos as opos, u.nick as onick,
    arrival-unix_timestamp() as unixeta
    from inc_atter ia
    left join fleet f on (f.fid = ia.fid)
    left join user u on (u.uid = ia.incid)
    where lower(ia.inickname) = lower('$nick')
    order by arrival asc
  ");
}

function atter_list_bykoords($gala,$pos) {
  
  if(!is_numeric($gala)) return;
  if(!is_numeric($pos)) return;
  return selectsql("
    select ia.*,f.*,
    u.gala as ogala,u.pos as opos, u.nick as onick,
    arrival-unix_timestamp() as unixeta
    from inc_atter ia
    left join fleet f on (f.fid = ia.fid)
    left join user u on (u.uid = ia.incid)
    where ia.igala = $gala AND ia.ipos = $pos 
    order by arrival asc
  ");
}

#l�scht einen oder mehrere incs
function inc_recall_all($incid) {
  
  if (is_array($incid)){
    $where = join(",",$incid);
  } else {
    if (!is_numeric($incid)) return;
    $where = $incid;
  }
  $fids = selectsql("select fid from inc_atter where incid IN ($where)","fid");
  deleteFleet($fids);
  delete_fleet_filter($fids);
  query("delete from inc_atter where incid IN ($where)");
  $externe = selectsql("
    select id.defferid
    from inc_deffer id
    where
    id.enickname IS NOT NULL
    AND id.incid IN ($where)
  ");
  if ($externe){
    $fids = getArrayFromList($externe,"defferid");
    deleteFleet($fids);
    delete_fleet_filter($fids);
    query("delete from fleetstatus where fid IN (".join(",",$fids).")");
  }
  $interne = selectsql("
    select id.defferid
    from inc_deffer id
    where
    id.enickname IS NULL
    AND id.incid IN ($where)
  ");
  if ($interne){
    $fids = getArrayFromList($interne,"defferid");
    user_fleet_recall($fids);
    delete_fleet_filter($fids);
  }
  query("delete from inc_deffer where incid IN ($where)");
  query("delete from incomings where incid IN ($where)");
}

function atter_recall($atterid) {
  
  if (is_array($atterid)){
    $atter = atter_get($atterid);
    $fids = getArrayFromList($atter, "fid");
    deleteFleet($fids);
    delete_fleet_filter($fids);
    $where = join(",",$atterid);
    query("delete from inc_atter where atterid IN ($where)");
    inc_check_empty(getArrayFromList($atter, "incid"));
  } else {
    if (!is_numeric($atterid)) return;
    $atter = atter_get($atterid);
    deleteFleet($atter['fid']);
    delete_fleet_filter($atter['fid']);
    query("delete from inc_atter where atterid = $atterid");
    inc_check_empty($atter['incid']);
  }
}

function inc_check_empty($incid) {
  
  if (is_array($incid) && count($incid)) {
    $where = join(",",$incid);
    $result = selectsql("
      select count(ia.atterid) as atter, i.incid
      from incomings i
      left join inc_atter ia on (ia.incid = i.incid)
      where i.incid IN ($where) group by i.incid");
    foreach ($result as $incdetails) {
      if (!$incdetails['atter']) $delete[] = $incdetails['incid'];
    }
    if (count($delete)) inc_recall_all($delete);
  } else {
    if (!is_numeric($incid)) return;
    $result = selectsqlline("
      select count(ia.atterid) as atter, i.incid
      from incomings i
      left join inc_atter ia on (ia.incid = i.incid)
      where i.incid = $incid group by i.incid
    ");
    if (!$result['atter']) inc_recall_all($incid);
  }
}
# checkt die incs ob sie  bereits 45min im Orbit sind
function inc_check_undertime() {
  
  $atterlist = selectsql("
    select atterid
    from inc_atter
    where
      arrival+4500-unix_timestamp() < 0
  ","atterid");
  if (count($atterlist)) {
    atter_recall($atterlist);
    addToLogFile("<b>System:</b> ".count($atterlist)." Atter recallt","Incomings");
  }
}

function atter_fleetnum_set($fid,$fleetnum) {
  
  if (!is_numeric($fid)) return;
  if (!is_numeric($fleetnum)) return;
  query("update inc_atter set fleetnum = $fleetnum where fid = $fid");
}

function atter_update($atterid,$prec,$svs,$fleetnum,$time,$fleet) {
  
  if (!is_numeric($atterid)) return;
  if (!is_numeric($prec)) return;
  if (!is_numeric($svs)) return;
  if (!is_numeric($fleetnum)) return;
  if (!is_numeric($time)) return;
  $atter = atter_get($atterid);
  if (!$atter) return;
  updateFleet($atter['fid'],
                $fleet['kleptoren'],
                  $fleet['cancris'],
                  $fleet['fregatten'],
                  $fleet['zerstoerer'],
                  $fleet['kreuzer'],
                  $fleet['schlachter'],
                  $fleet['traeger'],
                  $fleet['jaeger'],
                  $fleet['bomber']
                  );
  if (!$prec) $prec = 0;
  if (!$svs) $svs = 0;
  $arrival = mktime(date("H"),date("i")+$time,0,date("m"),date("d"),date("Y"));
  query("
    update inc_atter set prec = $prec, svs = $svs, fleetnum = $fleetnum, arrival = $arrival
    where atterid = $atterid
  ");
}



function atter_update_bynick($nick,$prec,$svs) {
  
  if (!$prec) $prec = 0;
  if (!$svs) $svs = 0;
  query("
    update inc_atter set prec = $prec, svs = $svs
    where lower(inickname) = lower('$nick')
  ");
}

function atter_update_bykoords($gala,$pos,$prec,$svs) {
  
  if (!$prec) $prec = 0;
  if (!$svs) $svs = 0;
  if(!is_numeric($gala)) return;
  if(!is_numeric($pos)) return;
  query("
    update inc_atter set prec = $prec, svs = $svs
    where igala = $gala and ipos = $pos 
  ");
}

function atter_get($atterid) {
  
  if (is_array($atterid)){
    $where = join(",",$atterid);
    return selectsql("
      select ia.*,f.*,
      u.gala as ogala,u.pos as opos, u.nick as onick,
      arrival-unix_timestamp() as unixeta
      from inc_atter ia
      left join fleet f on (f.fid = ia.fid)
      left join user u on (u.uid = ia.incid)
      where ia.atterid IN ($where)
    ");
  } else {
    if (!is_numeric($atterid)) return;
    return selectsqlline("
      select ia.*,f.*,
      u.gala as ogala,u.pos as opos, u.nick as onick,
      arrival-unix_timestamp() as unixeta
      from inc_atter ia
      left join fleet f on (f.fid = ia.fid)
      left join user u on (u.uid = ia.incid)
      where ia.atterid = $atterid
    ");
  }
}


function inc_add($uid,$gala,$pos,$nickname,$time,$fleetnum,$prec,$svs,$fleet) {
  
  if (!$prec) $prec = 0;
  if (!$svs) $svs = 0;
  $inc = inc_get_data($uid);
  if ($inc){
    if ($inc['save']) {
      inc_set_unsave($inc['incid']);
    }
  } else {
    inc_data_add($uid);
    #vorhandene deffer eintragen
    $user = getUserByID($uid);
    insertsql("
      insert into inc_deffer (defferid,incid)
        select fid,$uid from fleetstatus
        where gala = ".$user['gala']." AND pos = ".$user['pos']);
  }
  $fid = addFleet($fleet['kleptoren'],
                  $fleet['cancris'],
                  $fleet['fregatten'],
                  $fleet['zerstoerer'],
                  $fleet['kreuzer'],
                  $fleet['schlachter'],
                  $fleet['traeger'],
                  $fleet['jaeger'],
                  $fleet['bomber']
                  );
  $arrival = mktime(date("H"),date("i")+$time,0,date("m"),date("d"),date("Y"));
  if (!$prec) $prec = 0;
  if (!$svs) $svs = 0;
  return insertsql("
    insert into inc_atter(incid,inickname,igala,ipos,time,arrival,svs,prec,fid,fleetnum)
    values($uid,'$nickname',$gala,$pos,now(),$arrival,$svs,$prec,$fid,$fleetnum)
  ");
}

*/

function hide_fleets($fleets,$user) {
  
  if(!$fleets) return;
  if(!is_array($fleets)) $fleets = array($fleets);
  foreach($fleets as $fleet) {
    if(!is_numeric($fleet)) continue;
    insertsql("insert into fleet_filter (uid,fsid) values(".$user['uid'].",$fleet)");
  }
}

function delete_fleet_filter($fsids) {
  
  if(!$fsids) return;
  if(!is_array($fsids)) $fsids = array($fsids);
  query("delete from fleet_filter where fsid IN (".join(",",$fsids).")");
}

function reset_fleet_filter($user,$fsids) {
  
  if(!$user) return;
  if(!$fsids || (is_array($fsids) && !count($fsids))) {
    query("delete from fleet_filter where uid = ".$user['uid']);
    return;
  }
  if(!is_array($fsids)) $fsids = array($fsids);
  query("delete from fleet_filter where uid = ".$user['uid']." AND fsid IN (".join(",",$fsids).")");
}

function get_fleet_filter($user) {
  
  if(!$user) return;
  return selectsql("select fsid from fleet_filter where uid = ".$user['uid'],"fsid");
}



function atter_add($gala,$pos,$tgala,$tpos,$fleetnum,$eta=null,$fleet=null,$nick=null) {
  $logger = & LoggerManager::getLogger("db.takscreen");
  if(!is_numeric($gala)  || !is_numeric($pos) || !is_numeric($tgala) 
      || !is_numeric($tpos) || !is_numeric($fleetnum)) return false;
  $insert = array();
  $insert['gala'] = $gala;
  $insert['pos'] = $pos;
  $insert['tgala'] = $tgala;
  $insert['tpos'] = $tpos;
  $insert['status'] = 1;
  $insert['orbittime'] = 75;
  $insert['returntime'] = 450;
  
  if(($check1 = fleetstatus_get_bykoords($gala,$pos,$fleetnum))) {
    if($fleetnum == 1) $fleetnum = 2;
    else $fleetnum = 1;
    if($check2 = fleetstatus_get_bykoords($gala,$pos,$fleetnum)) {
//      $maxupdate = selectsqlline("
//        select * from fleet_status where gala = $gala and pos = $pos
//        order by lastupdate asc
//      ");
//      query("delete from fleet_status where fsid = ".$maxupdate['fsid']);
//      $fleetnum = $maxupdate['fleetnum'];
        return check1;
    }
  }
  $insert['fleetnum'] = $fleetnum;
  if($fleet) {
    $insert['fid'] = fleet_add($fleet);
    $insert['svs'] = $fleet['svs'];
    $insert['prec'] = $fleet['prec'];
  }
  if($nick) {
    scan_update_nick($gala,$pos,$nick);
  }
  
  if(is_numeric($eta)) $insert['arrival'] = gnarrival($eta);
  //_dump($insert);
  return fleetstatus_add($insert);
}

function deffer_add($gala,$pos,$tgala,$tpos,$fleetnum,$returntime,$orbittime,$eta=null,$nick=null) {
  if(!is_numeric($gala)  || !is_numeric($pos) || !is_numeric($tgala) 
      || !is_numeric($tpos) || !is_numeric($fleetnum)) return false;
  $insert = array();
  $insert['gala'] = $gala;
  $insert['pos'] = $pos;
  $insert['tgala'] = $tgala;
  $insert['tpos'] = $tpos;
  $insert['status'] = 2;
  $insert['orbittime'] = $orbittime;
  $insert['returntime'] = $returntime;
  
  if($fleetnum) {
    $insert['fleetnum'] = $fleetnum;
    $user = user_fleet_get($gala,$pos,$fleetnum);
    if($user['fid']) $insert['fid'] = $user['fid'];
  }
  
  if($nick) {
    scan_update_nick($gala,$pos,$nick);
  }
  
  if(is_numeric($eta)) $insert['arrival'] = gnarrival($eta);
  return fleetstatus_add($insert);
}

function fleetstatus_get_byfleetnum($gala,$pos,$null=false) {
  if($null) $null = "="; else $null = "!=";
  return selectsql("
    select * from fleet_status fs
    where gala = $gala and pos = $pos and fleetnum $null NULL
    order by lastupdate asc
  ");
}

function fleetstatus_get_filter($filter=null) {
  if($filter) {
    $awhere = array();
    $_check = array("tgala" => "tgala","tpos" => "tpos","gala" => "fs.gala","pos" => "fs.pos","fleetnum" => "fleetnum");
    foreach($_check  as $check => $sqlconst) {
      if(is_numeric($filter[$check])) {
        $awhere[] =$sqlconst." = ".$filter[$check];
      }
    }
    if($awhere) $where = "WHERE ".join(" AND ",$awhere);
  }
  return selectsql("
    select fs.*,u.nick as tnick from fleet_status fs
    left join user u on (u.gala = fs.tgala and u.pos = fs.tpos)
    $where
    order by arrival asc
  ");
}

function fleet_update($fid,$fleet) {
    Assert::isId($fid);
      $logger = & LoggerManager::getLogger("db.takscreen");
      $logger->debug(array(
          "function"=>"fleet_update",
          "fid" => $fid,
          "fleet" => $fleet
      ));
    $checkfleet = array(
      "jaeger","bomber","fregatten",
      "zerstoerer","kreuzer","schlachter",
      "traeger","kleptoren","cancris"
    );
    $update = array();
    foreach ($checkfleet as $key) {
      if(isset($fleet[$key]) && is_numeric($fleet[$key])) {
          $update[] = "$key = ".(int)$fleet[$key];
      }else {
          $update[] = "$key = 0";
      }
    }
    return query("
      update fleet set ".join(",",$update)." where fid = $fid
    ");
    $logger->debug("fleetupdate done");
}

function fleet_copy($src,$dst=0) {
  
	if (!$src || !is_numeric($src)) return;
  $fleet = getFleet($src);
  if (!$fleet) return;
	if ($dst) {
    return fleet_update($dst,$fleet);
  } else {
    return fleet_add($fleet);
  }
}

function fleet_get($fid) {
	
	if (!$fid || !is_numeric($fid)) return;
	return selectsqlLine("select * from fleet where fid = $fid");
}


/**
 * @return integer fid der Flotte
 * @param array $fleet
 * @desc F�gt eine Flotte hinzu
*/
function fleet_add($fleet=null) {
  $insert = array();
  if($fleet) {
    $checkfleet = array(
      "jaeger","bomber","fregatten",
      "zerstoerer","kreuzer","schlachter",
      "traeger","kleptoren","cancris"
    );
    foreach ($checkfleet as $key) {
      if($fleet[$key]) $insert[$key] = $fleet[$key];
    }
  }
  return insertsql("
    insert into fleet(".join(",",array_keys($insert)).")
    values (".join(",",array_values($insert)).")
  ");
}

function fleetstatus_get_bykoords($gala,$pos,$num=null,$col=null) {
  if(isset($num)) {
    $result = selectsqlline("
      select fs.*,s.*,u.uid from 
      fleet_status fs
      left join scans s on (s.gala = fs.gala and s.pos = fs.pos)
      left join user u on(u.gala = fs.gala and u.pos = fs.pos)
      where 
        fs.gala = $gala and fs.pos = $pos and fs.fleetnum = $num
    ");
  } else {
    $result = selectsql("
      select fs.*,s.*,u.uid from
      fleet_status fs
      left join scans s on (s.gala = fs.gala and s.pos = fs.pos)
      left join user u on(u.gala = fs.gala and u.pos = fs.pos)
      where 
        fs.gala = $gala and fs.pos = $pos
    ");
  }
  return $result;
}

function fleetstatus_get($fsid) {
  if(!$fsid || !is_numeric($fsid)) return false;
  return selectsqlline("
    select fs.*,s.nick,f.*,u.uid
      from fleet_status fs
      left join scans s on (s.gala = fs.gala and s.pos = fs.pos)
      left join user u on (u.gala = fs.gala and u.pos = fs.pos)
      left join fleet f on (f.fid = fs.fid)
    where fs.fsid = $fsid
  ");
}

function fleetstatus_recall($fsid) {
  $status = fleetstatus_get($fsid);
  
  if($status['uid']) {
    // interne flotte
    if($status['fleetnum'] && $status['arrival']) {
      $eta = $status['arrival'] - time();
      if($eta < 0) $eta = 0;
      $eta = floor($eta/60);
      $data = array();
      $data['orbittime'] = "";
      $data['returntime'] = "";
      $data['arrival'] = gnarrival($status['returntime'] - $eta);
      $data['return_flight'] = 1;
      fleetstatus_update($fsid,$data);
    } else {
      fleetstatus_delete($fsid);
    }
  } else {
    // externe flotte
    fleetstatus_delete($fsid);
    if($status['fid']) fleet_delete($status['fid']);
  }
  //letzter atter recallt ?
  if($status['status'] == 1 && !fleetstatus_get_bytarget($status['tgala'],$status['tpos'],1)) {
    $user = getUserByPos($status['tgala'],$status['tpos']);
    user_set_safe($user['uid'],0);
  }
}

function fleetstatus_update_fleet($fsid,$fleetdata,$svs=0,$prec=0) {
  if(!$fsid || !is_numeric($fsid)) return false;
  if(!($status = fleetstatus_get($fsid))) return false;
  
  if($status['fid']) {
    $fid = $status['fid'];
    fleet_update($fid,$fleetdata);
  } else {
    if($status['uid']) {
      // intern neu, geht normalerweise gar net
    } else {
      // extern neu
      $fid = fleet_add($fleetdata);
    }
  }
  if(!strlen($svs)) $svs = "NULL";
  if(!strlen($prec)) $prec = "NULL";
  
  return query("update fleet_status set 
      fid = $fid,
      prec = $prec,
      svs = $svs,
      lastupdate = unix_timestamp()
      where fsid = $fsid
  ");
}

/**
 * �ndert die Nummer der Flotte beim Flottenstatus.
 *
 * Dabei wird beachtet, ob es bereits einen Eintrag f�r die Nummer gibt, und ob es sich
 * um eine interne Flotte handelt. ggf. werden die Flottendaten vertauscht.
 *
 * @param (int|array) $fsid flottenstatus id
 * @param int $fleetnum neue Flottennummer
 */
function fleetstatus_change_fleetnum($fsid,$fleetnum) {
  
  if(is_array($fsid)) $fsid = $fsid['fsid'];
  if(!is_numeric($fsid)) return false;
  
  $status = fleetstatus_get($fsid);
  if($status['fleetnum'] != $fleetnum) {
    $check = fleetstatus_get_bykoords($status['gala'],$status['pos'],$fleetnum);
    if($check) {
      // es existiert ein anderer eintrag mit der flottennummer, flottendaten/nummern werden vertauscht
      fleetstatus_update($check['fsid'],array("fleetnum" => $status['fleetnum'],"fid" => $status['fid']));
      $fid = $check['fid'];
    } else {
      // kein anderer eintrag da, check obs ne interne flotte is
      if(($user = user_fleet_get($status['gala'],$status['pos'],$fleetnum))) {
        // flottendaten der internen flotte werden geladen
        $fid = $user['fid'];
      } else {
        // is eine externe
        // flottendaten bleiben erhalten
        $fid = $status['fid'];
      }
    }
    #_dump("fsid: $fsid, fleetnum: $fleetnum,fid: $fid <br>");
    fleetstatus_update($fsid,array("fleetnum" => $fleetnum,"fid" => $fid));
  }
}

function fleetstatus_update($fsid,$data) {
  $update = array();
  $_values = array(
    "orbittime" => "NULL",
    "returntime" => "NULL",
    "return_flight" => "0",
    "arrival" => "NULL",
    "tgala" => "NULL",
    "tpos" => "NULL",
    "gala" => null,
    "pos" => null,
    "status" => "0",
    "fleetnum" => "NULL",
    "fid" => "NULL",
    "svs" => "NULL",
    "prec" => "NULL"
    );
  
  foreach ($_values as $key => $default) {
    if(isset($data[$key])) {
      if(strlen($data[$key])) {
        $update[] = $key." = ".$data[$key];
      } else {
        $update[] = $key." = ".$default;
      }
    }
  }
  if($update) {
    return query("
      update fleet_status set ".join(",",$update).",lastupdate = unix_timestamp() where fsid = $fsid
    ");
  }
}

function fleetstatus_add($data) {
  
  if(!($data['status'] || $data['return_flight']) || !$data['gala'] || !$data['pos']) return false;
  $insert = array();
  $insert['gala'] = $data['gala'];
  $insert['pos'] = $data['pos'];

  $check = array("status","orbittime","returntime","return_flight","arrival","tgala","tpos","fid","svs","prec");

  foreach ($check as $key) {
    if(strlen($data[$key] > 0)) $insert[$key] = "'".$data[$key]."'";
  }
  if($data['fleetnum']) $insert['fleetnum'] = $data['fleetnum'];
  $insert['lastupdate'] = "unix_timestamp()";
  $query = "insert into fleet_status (".join(",",array_keys($insert)).") values (".join(",",array_values($insert)).")";
  
  return query($query);
}

function fleetstatus_delete($fsid) {
  $intern = fleetstatus_get($fsid);
  if(!$intern['uid'] && $intern['fid']) {
    fleet_delete($intern['fid']);
  }
  return query("delete from fleet_status where fsid = $fsid");
}

function fleetstatus_delete_bykoords($gala,$pos) {
  return query("delete from fleet_status where gala = $gala AND pos = $pos");
}

function fleet_delete($fid) {
  if(!$fid) return false;
  if(!is_array($fid)) $fid = array($fid);
  return query("
    delete from fleet where fid IN (".join($fid).")");
}

function fleetstatus_get_bytarget($gala,$pos,$status=0,$returnflight = 0){
  
  if(!is_numeric($gala)) return;
  if(!is_numeric($pos)) return;
  if($status && is_numeric($status)) $sqlstatus = "status = $status and";
  return selectsql("select * from fleet_status where $sqlstatus tgala = $gala and tpos = $pos and return_flight = $returnflight");
}

function fleetstatus_refresh(){
  
  $ids = selectsql("
    select fs.fsid,fid from fleet_status fs
    left join user u on (u.gala = fs.gala and u.pos = fs.pos)
    where fs.arrival+(fs.orbittime*60)-unix_timestamp() < 0 and fs.arrival is not null and u.uid is null
  ");
  if(count($ids)) {
//    addToLogFile("<b>System:</b> ".count($ids)." Deffer recallt","Incomings");
    $fids = array();
    $fsids = array();
    for($i=0;$i < count($ids);$i++) {
      if($ids[$i]['fid']) $fids[] = $ids[$i]['fid'];
      $fsids[] = $ids[$i]['fsid'];
    }
    if($fids) query("delete from fleet where fid IN (".join(",",$fids).")");
    query("delete from fleet_status where fsid IN (".join(",",$fsids).")");
  }
  query("
    update fleet_status set return_flight = 1,
      arrival = returntime*60+arrival+orbittime*60,
      returntime = NULL, orbittime = NULL
      where return_flight = 0 AND arrival+orbittime*60-unix_timestamp() < 0 and arrival is not null");
  query("
    delete from fleet_status  
      where return_flight = 1 AND arrival-unix_timestamp() < 0 and arrival is not null");
}

?>
