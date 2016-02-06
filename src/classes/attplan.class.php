<?php

require_once("classes/kibo.page.class.php");
require_once("functions/bbcode.php");
require_once("database/db.attplan.php");

class attplan  extends kibopage {

  #
  # Eventhandler
  #
  function run () {
    parent::run();

    #functionhash
    $functions["add"] = "Attack_add()";
    $functions["edit"] = "Attack_edit()";
    $functions["delete"] = "Attack_delete()";
    $functions["view"] = "Attack_view()";
/*    $functions["tactic"] = "Attack_tactic()";
    $functions["search"] = "Attack_search()";
    $functions["mytargets"] = "Attack_mytargets()";
*/
    $functions["reserve"] = "Target_reserve()";
    $functions["unreserve"] = "Target_unreserve()";
    
    $functions["targets"] = "Attack_targets()";
    $functions["addtargets"] = "Attack_targets_add()";
    $functions["deletetargets"] = "Attack_targets_delete()";
    $functions["closetargets"] = "Attack_targets_close()";
    $functions["opentargets"] = "Attack_targets_open()";
    $functions["deleteallatter"] = "Attack_atter_deleteall()";

    #handle action
    if ($functions[$this->action]) {
      eval("\$this->".$functions[$this->action].";");
    }
    #default
    $this->Attack_list();
  }

  #
  # Attacklist
  #
  function Attack_list() {
    $list = attack_list();
    foreach ($list as $item) {
      if(!$item['isopen']) {
        $item['scan_time'] = "Scans ab ".date("H:i",$item['access_time'])." Uhr ".date("d.m.Y",$item['access_time']);
      }
      
      if($item['isopen'] || 
        $item['owner'] == $this->userdata['uid'] ||
        $this->_checkUserRights("attorga")) 
      {
        $item['showlink'] = true;
      }
      if($item['isopen'] || 
        !$item['hidden'] || 
        $item['owner'] == $this->userdata['uid'] ||
        $this->_checkUserRights("attorga")) 
      {
        if(!$items['list']) $items['list'] = array();
        $items['list'][] = $item;
      }
    }
    $this->template->assign("items",$items);
    $this->show("attplan_list","Attplaner");
  }

  function Attack_targets_add() {
    $attid = param_num("id");
    if(!$attid) $this->_header();
    $attplan = attack_get($attid);

    //rightcheck
    if($this->userdata['uid'] != $attplan['owner'] &&
      !$this->_checkUserRights("attorga"))
      $this->_header();

    $steps = $_SESSION['steps'];
    if($steps['addtargets']) {
      unset($_SESSION['steps']['addtargets']);
      $sids = trim($_REQUEST['sids']);
      if($sids){
        $sids = split(",",urldecode($sids));
        if(count($sids)){
          $message[] = "Folgende Ziele wurden hinzugefügt:<br>";
          $scans = getScansBySid($sids);
          foreach($scans as $scan){
            $message[] = "(".$scan['gala'].":".$scan['pos'].")";
          }
          $message[] = "<br><b><span class=\"red\">fehlende Ziele sind möglicherweise in diesem oder anderen Plänen schon vorhanden</span></b>";
        }
      } else {
        $message[] = "Alle Ziele sind bereits in diesem oder anderen Attplänen vorhanden";
      }
      $this->forms['information']['action'] ="";
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Ziele hinzufügen";
      $this->forms['information']['message'] = join("<br>",$message);
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Ziele hinzufügen");
    }

    $form = new formContainer();
    $form->add(new formInput("galaxy_1","Galaxie","numeric",true,5));
    $form->add(new formInput("galaxy_2","Galaxie","numeric",true,5));
    $form->add(new formInput("galaxy_3","Galaxie","numeric",true,5));
    $form->add(new formInput("start_2","Start","numeric",true,5));
    $form->add(new formInput("end_2","Ende","numeric",true,5));
    $form->add(new formInput("pos_3","Position","numeric",true,5));
    $form->add(new formRadio("addtyp","Auswahltyp","numeric",array(1,2,3),3));

    if($_POST['send']) {
      $form->submit("addtyp");
      switch($form->get("addtyp")) {
        case 1 :
          $form->submit(array("galaxy_1"));
          if(!$form->hasErrors()) {
            $galaxy = $form->get("galaxy_1");
            $addtarget = array(1,2,3,4,5,6,7,8,9,10,11,12);
          }
          break;
        case 2 :
          $form->submit(array("galaxy_2","start_2","end_2"));
          if(!$form->hasErrors() && $form->get("start_2") > $form->get("end_2")) {
            $form->addError("die Startposition sollte niedriger sein als das Ende");
            $form->setError(array("start_2","end_2"));
          }
          if(!$form->hasErrors()) {
            $galaxy = $form->get("galaxy_2");
            $start = $form->get("start_2");
            $end = $form->get("end_2");
            for($i=$start;$i <= $end;$i++) {
              $addtarget[] = $i;
            }
          }
          break;
        default :
          $form->submit(array("galaxy_3","pos_3"));
          if(!$form->hasErrors()) {
            $galaxy = $form->get("galaxy_3");
            $addtarget[] = $form->get("pos_3");
          }
          break;
      }
      if(!$form->hasErrors()) {
        $sid_list = targets_add($attid,$galaxy,$addtarget);
        $steps['addtargets'] = 1;
        $_SESSION['steps'] = $steps;
        if(count($sid_list)){
          $sids = urlencode(join(",",$sid_list));
        }
        $this->_header("attplan.php?action=addtargets&id=$attid&sids=$sids&send");
      }
    } else {
      $form->select("addtyp",1);
    }

    $form->registerVars(&$this->template);

    $this->template->assign("attplan",$attplan);

    $this->show("attplan_targets_add","Ziele hinzufügen");
  }

  function Attack_targets() {
    $attid = param_num("id");
    $page = param_num("page",1);
    if(!$attid) $this->_header();
    $attplan = attack_get($attid);

    //rightcheck
    if($this->userdata['uid'] != $attplan['owner'] &&
      !$this->_checkUserRights("attorga"))
      $this->_header();

    
    $gala_list = attack_get_galalist($attid,&$pages,$page);
    $pagebar = showPageBar($page,$pages,"attplan.php?action=targets&id=$attid","page","menu");

    if(count($gala_list) < 5) {
      $spacer['colspan'] = 5 - count($gala_list);
      $spacer['width'] = $spacer['colspan']*20;
      $this->template->assign("spacer",$spacer);
    }

    $list = target_list(array("attid"=>$attid,"gala"=>$gala_list));
    $count = count($list);
    $gala = "";
    for($i=0;$i<$count;$i++) {
      if($list[$i]['gala'] != $gala){
        $gala = $list[$i]['gala'];
      }
      $targets[$gala][] = $list[$i];
    }

    $this->template->assign("pagebar",$pagebar);
    $this->template->assign("targets",$targets);
    $this->template->assign("attplan",$attplan);
    $this->show("attplan_targets","Ziele festlegen");
  }

  function Attack_targets_delete() {
    $attid = param_num("id");
    if(!$attid) $this->_header();
    $attplan = attack_get($attid);

    //rightcheck
    if($this->userdata['uid'] != $attplan['owner'] &&
      !$this->_checkUserRights("attorga"))
      $this->_header();

    if($_SESSION['steps']['deletetargets']) {
      unset($_SESSION['steps']['deletetargets']);
      $this->forms['information']['action'] ="";
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Ziele löschen";
      $this->forms['information']['message'] = "Angriffsziele wurden gelöscht!";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Ziele löschen");
    }
    if ($_POST['send']) {
      if ($_REQUEST['yes_x']) {
        $sids = $_REQUEST['sids'];
        if(empty($sids)) $this->_header();
        $sids = split(",",urldecode($sids));
        if(!count($sids)) $this->_header();
        targets_delete_bysid($sids);
        #save step
        $_SESSION['steps']['deletetargets'] = 1;
        $this->_header("attplan.php?action=deletetargets&id=$attid&send");
      } else {
        $this->_header();
      }
    } else {
      $sids = $_REQUEST['sids'];
      if(empty($sids)) $this->_header();
      if(!is_array($sids)) {
        $sids = split(",",urldecode($sids));
      }
      if(!count($sids)) $this->_header();
      $scans = getScansBySid($sids);
      foreach($scans as $scan){
        if($gala != $scan['gala']) {
          if($gala) $message[$gala] = join(", ",$message[$gala]);
          $gala = $scan['gala'];
        }
        $message[$gala][] = "(".$scan['gala'].":".$scan['pos'].") ".$scan['nick'];
      }
      if($gala) $message[$gala] = join(", ",$message[$gala]);
      $this->forms['information']['url'] = "attplan.php?id=$attid&sids=".urlencode(join(",",$sids));
      $this->forms['information']['action'] = "deletetargets";
      $this->forms['information']['title'] = "Angriffsziele löschen";
      $this->forms['information']['message'] = "
        <b>".$attplan{'title'}."</b><br><br>
        Folgende Ziele werden gelöscht:<br>".join("<br>",$message);
      $this->forms['information']['style'] = "red";
      $this->show('message_question', "Angriffsziele löschen");
    }
  }

  function Attack_atter_deleteall() {
    $attid = param_num("id");
    if(!$attid) $this->_header();
    $attplan = attack_get($attid);

    $steps = $_SESSION['steps'];

    if($steps['deleteallatter']) {
      unset($steps['deleteallatter']);
      $_SESSION['steps'] = $steps;
      $this->forms['information']['action'] ="";
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "alle Angreifer löschen";
      $this->forms['information']['message'] = "Angreifer wurden gelöscht!";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "alle Angreifer löschen");
    }
    if ($_POST['send']) {
      if ($_REQUEST['yes_x']) {
        $sids = split(",",urldecode($_REQUEST['sids']));
        if(!count($sids)) $this->_header();
        atter_delete_bysid($attid,$sids);
        #save step
        $steps['deleteallatter'] = 1;
        $_SESSION['steps'] = $steps;
        $this->_header("attplan.php?action=deleteallatter&id=$attid&send");
      } else {
        $this->_header();
      }
    } else {
      $sids = $_REQUEST['sids'];
      if(empty($sids)) $this->_header();
      if(!is_array($sids)) {
        $sids = split(",",urldecode($sids));
      }
      if(!count($sids)) $this->_header();
      $scans = getScansBySid($sids);
      foreach($scans as $scan){
        if($gala != $scan['gala']) {
          if($gala) $message[$gala] = join(", ",$message[$gala]);
          $gala = $scan['gala'];
        }
        $message[$gala][] = "(".$scan['gala'].":".$scan['pos'].") ".$scan['nick'];
      }
      if($gala) $message[$gala] = join(", ",$message[$gala]);
      $this->forms['information']['url'] = "attplan.php?id=$attid&sids=".urlencode(join(",",$sids));
      $this->forms['information']['action'] = "deleteallatter";
      $this->forms['information']['title'] = "alle Angreifer löschen";
      $this->forms['information']['message'] = "
        <b>".$attplan{'title'}."</b><br><br>
        zu folgenden Zielen werden die Angreifer gelöscht:<br>".join("<br>",$message);
      $this->forms['information']['style'] = "red";
      $this->show('message_question', "alle Angreifer löschen");
    }
  }

  function Attack_targets_close() {
    $attid = param_num("id");
    if(!$attid) $this->_header();
    $attplan = attack_get($attid);

    //rightcheck
    if($this->userdata['uid'] != $attplan['owner'] &&
      !$this->_checkUserRights("attorga"))
      $this->_header();
    
    
    $steps = $_SESSION['steps'];

    if($steps['closetargets']) {
      unset($steps['closetargets']);
      $_SESSION['steps'] = $steps;
      $this->forms['information']['action'] ="";
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Ziele schliessen";
      $this->forms['information']['message'] = "Angriffsziele wurden als geschlossen markiert, es können sich keine weiteren Angreifer eintragen!";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Ziele schliessen");
    }
    if ($_POST['send']) {
      if ($_REQUEST['yes_x']) {
        $sids = $_REQUEST['sids'];
        if(empty($sids)) $this->_header();
        $sids = split(",",urldecode($sids));
        if(!count($sids)) $this->_header();
        targets_close($sids);
        #save step
        $steps['closetargets'] = 1;
        $_SESSION['steps'] = $steps;
        $this->_header("attplan.php?action=closetargets&id=$attid&send");
      } else {
        $this->_header();
      }
    } else {
      $sids = $_REQUEST['sids'];
      if(empty($sids)) $this->_header();
      if(!is_array($sids)) {
        $sids = split(",",urldecode($sids));
      }
      if(!count($sids)) $this->_header();
      $scans = getScansBySid($sids);
      foreach($scans as $scan){
        if($gala != $scan['gala']) {
          if($gala) $message[$gala] = join(", ",$message[$gala]);
          $gala = $scan['gala'];
        }
        $message[$gala][] = "(".$scan['gala'].":".$scan['pos'].") ".$scan['nick'];
      }
      if($gala) $message[$gala] = join(", ",$message[$gala]);
      $this->forms['information']['url'] = "attplan.php?id=$attid&sids=".urlencode(join(",",$sids));
      $this->forms['information']['action'] = "closetargets";
      $this->forms['information']['title'] = "Angriffsziele schliessen";
      $this->forms['information']['message'] = "
        <b>".$attplan{'title'}."</b><br><br>
        Folgende Ziele werden als geschlossen markiert:<br>".join("<br>",$message);
      $this->forms['information']['style'] = "red";
      $this->show('message_question', "Angriffsziele schliessen");
    }
  }

  function Attack_targets_open() {
    $attid = param_num("id");
    if(!$attid) $this->_header();
    $attplan = attack_get($attid);

    //rightcheck
    if($this->userdata['uid'] != $attplan['owner'] &&
      !$this->_checkUserRights("attorga"))
      $this->_header();

    $steps = $_SESSION['steps'];

    if($steps['opentargets']) {
      unset($steps['opentargets']);
      $_SESSION['steps'] = $steps;
      $this->forms['information']['action'] ="";
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Ziele öffnen";
      $this->forms['information']['message'] = "Angriffsziele wurden als offen markiert, es können sich weitere Angreifer eintragen!";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Ziele öffnen");
    }
    if ($_POST['send']) {
      if ($_REQUEST['yes_x']) {
        $sids = $_REQUEST['sids'];
        if(empty($sids)) $this->_header();
        $sids = split(",",urldecode($sids));
        if(!count($sids)) $this->_header();
        targets_open($sids);
        #save step
        $steps['opentargets'] = 1;
        $_SESSION['steps'] = $steps;
        $this->_header("attplan.php?action=opentargets&id=$attid&send");
      } else {
        $this->_header();
      }
    } else {
      $sids = $_REQUEST['sids'];
      if(empty($sids)) $this->_header();
      if(!is_array($sids)) {
        $sids = split(",",urldecode($sids));
      }
      if(!count($sids)) $this->_header();
      $scans = getScansBySid($sids);
      foreach($scans as $scan){
        if($gala != $scan['gala']) {
          if($gala) $message[$gala] = join(", ",$message[$gala]);
          $gala = $scan['gala'];
        }
        $message[$gala][] = "(".$scan['gala'].":".$scan['pos'].") ".$scan['nick'];
      }
      if($gala) $message[$gala] = join(", ",$message[$gala]);
      $this->forms['information']['url'] = "attplan.php?id=$attid&sids=".urlencode(join(",",$sids));
      $this->forms['information']['action'] = "opentargets";
      $this->forms['information']['title'] = "Angriffsziele öffnen";
      $this->forms['information']['message'] = "
        <b>".$attplan{'title'}."</b><br><br>
        Folgende Ziele werden für weitere Angreifer geöffnet:<br>".join("<br>",$message);
      $this->forms['information']['style'] = "red";
      $this->show('message_question', "Angriffsziele öffnen");
    }
  }

  function Attack_mytargets() {
    $attplan['content'] = $this->template->fetch("attplan_content_mytargets.html");
    $this->template->assign("attplan",$attplan);
    $this->show("attplan_view","Angriffsplan");
  }

  function Attack_tactic() {
    $attplan['content'] = $this->template->fetch("attplan_content_tactic.html");
    $this->template->assign("attplan",$attplan);
    $this->show("attplan_view","Angriffsplan");
  }

  function Attack_search() {
    $attplan['content'] = $this->template->fetch("attplan_content_search.html");
    $this->template->assign("attplan",$attplan);
    $this->show("attplan_view","Angriffsplan");
  }

  /**
  *  Scanansicht von einem Att
  **/
  function Attack_view() {
    if(!($attid = param_num("id")) || !($attplan = attack_get($attid))) $this->_header();
    $form = new formContainer();

    $attplan['isadmin'] = $this->userdata['uid'] == $attplan['owner'] ||
      $this->_checkUserRights("attorga");
    
    if(!$attplan['isopen'] && !$attplan['isadmin']) $this->_header();
    
    $galalist = attack_gala_list($attid);
    // ziele vorhanden ?
    if($galalist) {
      $list = array();
      foreach ($galalist as $item) {
        $list[] = array("value" => $item['gala'],"title" => $item['gala']);
      }
      $form->add(new formSelectBox("gala","Galaxie","numeric",$list,false));
      
      if($_POST['send'] && $_POST['subaction'] == "changegala") {
        $form->submit();
        if(!$form->hasErrors()) $_SESSION['attplanfilter'][$attid]['gala'] = $form->get("gala");
        $gala = $form->get("gala");
      } else {
        if($_SESSION['attplanfilter'][$attid]['gala']) {
          $form->select("gala",$_SESSION['attplanfilter'][$attid]['gala']);
          $gala = $_SESSION['attplanfilter'][$attid]['gala'];
        } else {
          $gala = $galalist[0]['gala'];
         }
      }
      
      $sids = target_list(array("gala" => $gala),"sid");
      $scanlist = listScans(array("sids" => $sids,"order"=>"asc","sort"=>"koords","hassektor" => true,"showattackscans" => true),&$pages,1,15);
      
		  for ($i=0;$i<count($scanlist);$i++) {
        $scanlist[$i]['backlink'] = "&backlink=".urlencode("attplan.php?action=view&id=".$attid."#".$scanlist[$i]['sid']);
        $scanlist[$i]['expand_backlink'] = 
            "&backlink=".urlencode("attplan.php?action=view&id=".$attid."&expand=".$scanlist[$i]['sid']."#".$scanlist[$i]['sid']);
        #mili oder news ausklappen
        if($scanlist[$i]['sid'] == $expand) {
          $scanlist[$i]['expand'] = 1;
        }
        if($scanlist[$i]['uid']) {
          if($this->userdata['uid'] == $scanlist[$i]['uid'] || $this->_checkUserRights("attorga")) {
            $scanlist[$i]['candelete'] = true;
          }
          if($this->userdata['uid'] == $scanlist[$i]['uid']) {
            $scanlist[$i]['reserveclass'] = "green";
          } else {
            $scanlist[$i]['reserveclass'] = "red";
          }
        } else {
          $scanlist[$i]['canreserve'] = true;
        }
        $scanlist[$i] = scan_format($scanlist[$i]);
		  }        
        
      $this->template->assign("scanlist",$scanlist);
      $this->template->assign("hastargets",true);
      $this->template->assign("selectedgala",$gala);
    }
    
    
    $form->registerVars($this->template);
    $this->template->assign("attplan",$attplan);
    $this->template->assign("type","ziele");
    $this->show("attplan_view","Angriffsplan");
  }
  
  function Target_reserve() {
    if (!($sid = param_num("id"))) $this->_header();
    
    $reservation = getScanById($sid,true);
    // gibts den att ?
    if(!$reservation['attid'] || $reservation['closed']) $this->_header();
    // is noch offen ?
    if(!$reservation['isopen'] && 
      !$this->_checkUserRights('attorga')
      && $this->userdata['uid'] != $reservation['owner']
      ) $this->_header();
    if ($reservation['uid']) {
     if ($reservation['uid'] == $this->userdata['uid']) {
      $this->show_message("Reservierung","Ziel bereits von DIR reserviert",$this->backtracking->backlink(),"red",array("send" => 1));
     } else {
      $this->show_message("Reservierung","Ziel bereits von jmd. reserviert",$this->backtracking->backlink(),"red",array("send" => 1));
     }
    }
    target_reserve($sid, $this->userdata['uid']);
    $this->_header();
  }
  
  function Target_unreserve() {
    if (!($sid = param_num("id"))) $this->_header();
    
    $reservation = getScanById($sid,true);
    // gibts den att ?
    if(!$reservation['attid'] || !$reservation['uid'] || $reservation['closed']) $this->_header();
    //att is offen für reservierungen ?
    if(!$reservation['isopen'] && 
      !$this->_checkUserRights('attorga')
      && $this->userdata['uid'] != $reservation['owner']
      ) $this->_header();
    if ($reservation['uid'] == $this->userdata['uid'] || $this->_checkUserRights('attorga')) {
      target_unreserve($sid);
    } 
    $this->_header();
  }

  function Attack_add() {

    $data = $_SESSION['steps'];
    if($data['addattack']) {
      unset($_SESSION['steps']['addattack']);
      $attid = param_num("id");
      if(!$attid) $this->_header();
      $this->forms['information']['action'] ="";
      $this->forms['information']['url'] = "attplan.php?action=view&id=$attid";
      $this->forms['information']['title'] = "Angriffsplan erstellen";
      $this->forms['information']['message'] = "Angriffsplan wurde hinzugefügt";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Angriffsplan erstellen");
    }
    $form = new formContainer();
    $form->add(new formInput("title","Titel","string",true,255));
    $form->add(new formInput("descr","Beschreibung","string",true,null,true));
    $form->add(new formInput("reserve_date","Datum für die Reservierung","date",false,40,true,"'(\d{1,2})\.(\d{1,2})\.(\d{2,4})'is"));
    $form->add(new formInput("reserve_time","Startzeit für die Reservierung","time",false,40,true,"'(\d{1,2}):(\d{2})'is"));
    $form->add(new formRadio("hidden","Plan verstecken","numeric",array(1,0)));

    if($_POST['send']) {
      $form->submit();
      if(($form->get("reserve_date") && !$form->get("reserve_time")) ||
          (!$form->get("reserve_date") && $form->get("reserve_time"))
      ) {
        $form->setError("reserve_date");
        $form->setError("reserve_time");
        $form->addError("Datum und Uhrzeit für den Start der Reservierung müssen beide ausgefüllt sein");
      }
      if(!$form->hasErrors()) {
        if($form->get("reserve_date")) {
          $date = $form->getregex("reserve_date");
          $time = $form->getregex("reserve_time");
          $reserve = mktime($time[1],$time[2],0,$date[2],$date[1],$date[3]);
        }
        $id = attack_add(
          $form->get("title"),
          $this->userdata['uid'],
          $form->get("descr"),
          $reserve,
          $form->get("hidden")
        );
        $_SESSION['steps']['addattack'] = 1;
        $this->_header("attplan.php?action=add&id=$id&send");
      }
    } else {
      $form->select("hidden",0);
      $form->set("reserve_date",date("d.m.Y"));
      $form->set("reserve_time",date("H:i"));
    }
    $form->registerVars(&$this->template);
    $this->show("attplan_add","Attplaner");
  }

  function Attack_edit() {
    $attid = param_num("id");
    if(!$attid) $this->_header();
    $attplan = attack_get($attid);
    if(empty($attplan))$this->_header();
    
    //rightcheck
    if($this->userdata['uid'] != $attplan['owner'] &&
      !$this->_checkUserRights("attorga"))
      $this->_header();
    
    if($_SESSION['steps']['editattack']) {
      unset($_SESSION['steps']['editattack']);
      $this->forms['information']['action'] ="";
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Angriffsplan bearbeiten";
      $this->forms['information']['message'] = "Daten wurden erfolgreich geändert!";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Angriffsplan bearbeiten");
    }
    $form = new formContainer();
    $form->add(new formInput("title","Titel","string",true,255));
    $form->add(new formInput("descr","Beschreibung","string",true,null,true));
    $form->add(new formInput("reserve_date","Datum für die Reservierung","date",false,40,true,"'(\d{1,2})\.(\d{1,2})\.(\d{2,4})'is"));
    $form->add(new formInput("reserve_time","Startzeit für die Reservierung","time",false,40,true,"'(\d{1,2}):(\d{2})'is"));
    $form->add(new formRadio("hidden","Plan verstecken","numeric",array(1,0)));

    if($_POST['send']) {
      $form->submit();
      if(!$form->hasErrors()) {
        if($form->get("reserve_date")) {
          $date = $form->getregex("reserve_date");
          $time = $form->getregex("reserve_time");
          $reserve = mktime($time[1],$time[2],0,$date[2],$date[1],$date[3]);
        }
        attack_edit(
          $attid,
          $form->get("title"),
          $form->get("descr"),
          $reserve,
          $form->get("hidden")
        );
        $_SESSION['steps']['editattack'] = 1;
        $this->_header("attplan.php?action=edit&id=$attid&send");
      }
    } else {
      $form->select("hidden",$attplan['hidden']);
      $form->set("title",$attplan['title']);
      $form->set("descr",$attplan['descr']);
      $form->set("reserve_date",date("d.m.Y",$attplan['access_time']));
      $form->set("reserve_time",date("H:i",$attplan['access_time']));
    }
    $form->registerVars(&$this->template);
    $this->template->assign("attplan",$attplan);
    $this->show("attplan_edit","Attplaner");
  }
  
  function Attack_delete() {
    
    if($_SESSION['steps']['deleteattack']) {
      unset($_SESSION['steps']['deleteattack']);
      $this->forms['information']['action'] ="";
      $this->forms['information']['url'] = "attplan.php";
      $this->forms['information']['title'] = "Plan gelöscht";
      $this->forms['information']['message'] = "Plan wurde gelöscht";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Angriffsplan löschen");
    }

    $attid = param_num("id");
    if(!$attid) $this->_header();
    $attplan = attack_get($attid);
    if(empty($attplan))$this->_header();
    
    //rightcheck
    if($this->userdata['uid'] != $attplan['owner'] &&
      !$this->_checkUserRights("attorga"))
      $this->_header();

    if($_POST['send']) {
      if($_POST['yes_x']) {
        attack_delete($attid);
        $_SESSION['steps']['deleteattack'] = 1;
        $this->_header("attplan.php?action=delete");
      }
      $this->_header();
    } else {
      $this->forms['information']['url'] = "attplan.php?id=$attid";
      $this->forms['information']['action'] = "delete";
      $this->forms['information']['title'] = "Angriffsplan löschen";
      $this->forms['information']['message'] = "
        <b>'".$attplan{'title'}."'</b> komplett löschen ?";
      $this->forms['information']['style'] = "red";
      $this->show('message_question', "Angriffsplan löschen");
    }
  }
}
?>