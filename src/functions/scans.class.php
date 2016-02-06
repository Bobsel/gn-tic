<?php

require_once("functions/functions.php");
require_once("functions/parsing.php");
require_once('database/db.scans.php');
require_once('classes/kibo.page.class.php');


class scanpage extends kibopage {

  #
  # Eventhandler
  #
  function run () {
    parent::run();
    #functionhash
    $functions['add'] = "_addScan()";
    $functions['targets'] = "_listScans(\"targets\")";

    #handle action
    if ($functions[$this->action]) {
      eval("\$this->".$functions[$this->action].";");
    }
    #default
    $this->_listScans("scans");
  }

  #
  # fügt einen oder mehrere Scans hinzu
  #
  function _addScan() {
    if ($_REQUEST['send']) {
      $data = $_POST['data'];
      if ($data) {
        $scans = parseScan($data);
        if ($scans) {
          $this->forms['addscan']['message'] ="<b>Folgende Scans wurden erkannt:</b><br><br>";
          $this->template->assign("scanlist",$scans);
          for ($i=0;$i<count($scans);$i++) {
            updateScan($scans[$i]);
          }
        } else {
          $this->forms['addscan']['message'] = "<div class = \"tbl_red\">Scan(s) nicht erkannt</div>";
        }
  #      $this->forms['addscan']['fields']['data']['value'] = $data;
      } else {
        $this->forms['addscan']['message'] = "<div class = \"tbl_red\">Feld leer</div>";
      }
    } else {
      $this->forms['addscan']['message'] = "Der Scanner arbeitet mit Copy&Paste. Dabei ist es egal ob ihr
      den Scan aus dem IRC Channel kopiert, oder den IRC Copy Knopf benutzt.Beim Kopieren aus dem IRC heraus ist nur wichtig,
      daß ihr die kompletten Zeilen kopiert. (mit nickname, uhrzeit, was weiß ich nicht noch alles).Zeilen die nicht zum scan gehören
      werden vom Parser ignoriert. Vorhandene Scans werden upgedated, fehlende neu hinzugefügt.Weiterhin werden beliebig viele Scans 
gleichzeitig 
geparst,
      ihr könnt also alle Scans mit einem Mal einfügen.<br><br>
      Unterstütze Formate:<br>
      <b>Galaxy Network Scan</b><br>";
    }
    $this->forms['addscan']['action'] = "add";
    $this->forms['addscan']['url'] = "scans.php";
    $this->show('scan_add_form','Scan hinzufügen/updaten');
  }
  #
  # zeigt scandetails an
  #
  function scan_details($gala,$pos) {
    $scan = getScan(array("gala" => $gala, "pos" => $pos));
    $this->template->assign("gala",$gala);
    $this->template->assign("pos",$pos);
    $this->template->assign("scan",scan_format($scan));
    $this->show('scan_details',"Details von ($gala:$pos)");
  }

  #
  # Listet die Scans auf
  #
  function _listScans($option) {
    $gala = param_num("gala");
    $pos = param_num("pos");
    #scandetails
    if($gala && $pos) $this->scan_details($gala,$pos);
    #scanlist
    $page = param_num("page",1);
    $rows = 20;
    
		$link_options = array();
		
		if ($option == "targets") {
      $scanlistfilter = & $_SESSION['targetlistfilter'];
      $this->template->assign("showtargets",1);
      $this->template->assign("scantitle","Ziele suchen");
      #zielsuche
      if ($_REQUEST['subaction'] == 'newsearch' && $scanlistfilter) {
        unset($_SESSION['targetlistfilter']);
				unset($scanlistfilter);
      }
/*      if ($_REQUEST['subaction'] == "search") {
        $scanlistfilter['exen'] = param_num("exen");
        $scanlistfilter['punkte'] = param_num("punkte");
      }
			*/
      if (!$scanlistfilter || $_POST['subaction'] == "search") {
        $form = new formContainer();
				$form->add(new formInput("exen","Exenmindestanzahl","numeric",true,10,true));
				$form->add(new formInput("macht","Machtmindestwert","numeric",true,10,true));
				$form->add(new formCheckbox("hideold","Alte Scans nicht berücksichtigen","numeric",array(1),false));
				if($_POST['send']) {
						$form->submit();
						if(!$form->hasErrors()) {
								if($form->get("macht")) $scanlistfilter['macht'] = $form->get("macht");
								if($form->get("exen")) $scanlistfilter['exen'] = $form->get("exen");
								$scanlistfilter['sort'] = "exen";
								$scanlistfilter['order'] = "desc";
								$scanlistfilter['hassektor'] = "1";
								$scanlistfilter['hideold'] = $form->get("hideold");
								$scanlistfilter['except_galas'] = getGalaList(true);
								$this->_header("scans.php?action=targets&send");
						}
				} else {
						$form->select("hideold",1);
				}
				
				$form->registerVars($this->template);
        $this->show('scan_target_form','Ziele suchen');
      }
      #sortierung
      if ($_REQUEST['sort'] && $_REQUEST['order'] && $scanlistfilter) {
        $sort = trim($_REQUEST['sort']);
        $order = trim($_REQUEST['order']);
        if ($sort != 'koords' && $sort != 'exen') $sort = 'koords';
        if ($order != "asc" && $order != "desc") $order = "asc";
        $scanlistfilter['sort'] = $sort;
        $scanlistfilter['order'] = $order;
      }
      $link_options[] = "action=targets";
      $link2 = "?action=targets";
      #normale Scananzeige, galaweise
    } else {
      $scanlistfilter = &$_SESSION['scanlistfilter'];
      $this->template->assign("scantitle","Scans");
      if (!$scanlistfilter) {
        $scanlistfilter['gala'] = 1;
        $scanlistfilter['sort'] = "koords";
        $scanlistfilter['order'] = "asc";
        $scanlistfilter['hideold'] = 1;
      }
      #alte einblenden/ausblenden
      if (isset($_REQUEST["hideold"]) && is_numeric($_REQUEST["hideold"])) {
        $scanlistfilter['hideold'] = $_REQUEST["hideold"];
      }
      $hideold = $scanlistfilter["hideold"];
      if ($hideold){
        $title = "Alle Anzeigen";
        $subtitle = "Zeigt alle Scans an";
        $param = 0;
      } else {
        $title = "Alte Ausblenden";
        $subtitle = "Scans die älter als 1 Tag sind werden nicht angezeigt";
        $param = 1;
      }
      $this->template->assign("hideoldtitle",$title);
      $this->template->assign("hideoldparam",$param);
      $this->template->assign("hideoldsubtitle",$subtitle);
      #neue gala anzeigen
      if ($gala = param_num("gala")) {
        $scanlistfilter['gala'] = $gala;
      } else {
        $gala = $scanlistfilter["gala"];
      }
      if ($this->userdata["gid"] == 1) { $hideblock = false; } else { $hideblock = true; }
      $next = getNextGala($gala,$hideold,$hideblock);
      $prev = getPrevGala($gala,$hideold,$hideblock);
      if ($next && $prev) {
        #gala suche anzeigen
        $this->template->assign("showform",1);
      }
      #zeigen beide auf die selbe, -> die aktuelle
      if ($next == $prev && $next == $gala) {
        unset($prev);
        unset($next);
      }
      $this->template->assign("prev",$prev);
      $this->template->assign("next",$next);
      #sortierung
      if ($_REQUEST['sort'] && $_REQUEST['order'] && $scanlistfilter) {
        $sort = trim($_REQUEST['sort']);
        $order = trim($_REQUEST['order']);
        if ($sort != 'koords' && $sort != 'exen') $sort = 'koords';
        if ($order != "asc" && $order != "desc") $order = "asc";
        $scanlistfilter['sort'] = $sort;
        $scanlistfilter['order'] = $order;
      }
				$link_options[] = "gala=".$scanlistfilter['gala'];
		}
    #mili & news ausklappen
    $expand = param_num("expand");
    #scans holen
    $this->forms['scanlist'][$scanlistfilter['sort']][$scanlistfilter['order']] = '_active';
		$scanlist = listScans($scanlistfilter,&$pages,&$page,$rows);
		for ($i=0;$i<count($scanlist);$i++) {
        $scanlist[$i]['backlink'] = "&backlink=".urlencode("scans.php?".join("&",$link_options)."#".$scanlist[$i]['sid']);
        $scanlist[$i]['expand_backlink'] = 
    "&backlink=".urlencode("scans.php?".join("&",$link_options)."&expand=".$scanlist[$i]['sid']."#".$scanlist[$i]['sid']);
        #mili oder news ausklappen
        if($scanlist[$i]['sid'] == $expand) {
          $scanlist[$i]['expand'] = 1;
        }
        if($scanlist[$i]['uid']) {
          if($scanlist[$i]['uid'] == $this->userdata['uid']) {
            $scanlist[$i]['atter_class'] = "green";
          } else {
            $scanlist[$i]['atter_class'] = "red";
          }
        }
        $scanlist[$i] = scan_format($scanlist[$i]);
    }
    
		$this->forms['scanlist']['pages'] = showPageBar($page,$pages,"scans.php".$link2,"page","menu");
    $this->forms['scanlist']['gala'] = $scanlistfilter['gala'];
    
		$this->template->assign('scanlist',$scanlist);
    $this->template->assign('page',$page);

    $this->show('scan_list','Scans');
  }

  ###################################




  /*function _Target_set() {
    $id = param_num("id");
    if (!$id) {
      #kein target da
      if ($_POST['step']) {
        $items['gala']['value'] = param_num("gala");
        $items['pos']['value'] = param_num("pos");
        if (!$items['pos']['value']) {
          $errors[] = "Position fehlt oder ungültig!";
          $items['pos']['class'] = "_error";
        }
        if (!$items['gala']['value']) {
          $errors[] = "Galaxie fehlt oder ungültig!";
          $items['gala']['class'] = "_error";
        }
        if (!$errors){
          $scan = getScan($items['gala']['value'],$items['pos']['value']);
          if (!$scan) {
            $id = addMainScan($items['gala']['value'],$items['pos']['value']);
          } else {
            $id = $scan['sid'];
          }
          if ($scan['closed']) {
            $errors[] = "Ziel ist bereits reserviert";
          }
        }
      }
      $this->template->assign("showcomplete",1);
    } else {
      $scan = getScanbyId($id);
      if (!$scan) $this->_header(back_link(),"ungültig: $id");
      if ($scan['closed']) $this->_header(back_link(),"Target geschlossen");
    }
    if ($_POST['step']) {
      $items['fleet']['value'] = param_num("fleet");
      $items['time']['value'] = $_POST['time'];
      #flotte ungültig
      if ($items['fleet']['value'] != 1 && $items['fleet']['value'] != 2) {
        $this->_header(back_link(),"Ungültige flotte");
      }
      if ($items['time']['value']) {
        if (!preg_match("/(\d{1,2}):(\d{2})/i",$items['time']['value'],$data)) {
          $errors[] = "Ungültige Zeit!";
          $items['time']['class'] ="_error";
        }
      }
      if (!$errors) {
        if($atter = getAtter($this->userdata['uid'],$items['fleet']['value'])) {
          $errors[] = "Flotte ".$items['fleet']['value']." bereits für (".$atter['gala'].":".$atter['pos'].") eingetragen";
        }
      }
      if (!$errors) {
        if (addAtter($id,$this->userdata['uid'],$items['fleet']['value'],$items['time']['value']) != -1) {
          $this->_header(back_link());
        } else {
          $errors[] = "DB Error";
        }
      }
      $this->template->assign("errors",$errors);
    } else {
      $items['time']['class'] = "_optional";
      $items['fleet']['value'] = 1;
    }
    $this->template->assign("fleet".$items['fleet']['value'],"checked");
    $this->template->assign("items",$items);
    $this->template->assign("id",$id);
    $this->show('scan_target_set','Ziel reservieren');
  }
  ###############################################################
  function _Target_close() {
    #exists id ?
    $id = param_num("id");
    if (!$id) $this->_header(back_link(),"fehlende id");
    #exists scan ?
    $scan = getScanbyId($id);
    if (!$scan) $this->_header(back_link(),"ungültig: $id");
    # is atter ?
    $atterlist = getAtterlist($id);
    $isatter = 0;
    for($j=0;$j < count($atterlist);$j++){
      $atter = &$atterlist[$j];
      if ($atter['uid'] == $this->userdata['uid']) {
        $isatter = 1;
        break;
      }
    }
    if (!$isatter) $this->_header(back_link(),"no permission");
    if (!$scan['closed']) {
      closeTarget($id);
    }
    $this->_header(back_link());
  }
  ###############################################################
  function _Target_open() {
    #exists id ?
    $id = param_num("id");
    if (!$id) $this->_header(back_link(),"fehlende id");
    #exists scan ?
    $scan = getScanbyId($id);
    if (!$scan) $this->_header(back_link(),"ungültig: $id");
    # is atter ?
    $atterlist = getAtterlist($id);
    $isatter = 0;
    for($j=0;$j < count($atterlist);$j++){
      $atter = &$atterlist[$j];
      if ($atter['uid'] == $this->userdata['uid']) {
        $isatter = 1;
        break;
      }
    }
    if (!$isatter) $this->_header(back_link(),"no permission");
    if ($scan['closed']) {
      openTarget($id);
    }
    $this->_header(back_link());
  }
  ###############################################################
  function _Target_drop() {
    #exists id ?
    $uid = param_num("uid");
    $fleet = param_num("fleet");
    if (!$uid || !$fleet) $this->_header(back_link(),"fehlende id");
    if ($fleet != 1 && $fleet != 2) $this->_header(back_link(),"falsche flotte");
    #exists scan ?
    $atter = getAtter($uid,$fleet);
    # is atter ?
    if (!$atter) $this->_header(back_link(),"atter nicht gefunden");
    if ($atter['uid'] != $this->userdata['uid'])
      $this->_header(back_link(),"no permission");
    deleteAtter($uid,$fleet);
    $this->_header(back_link());
  }                 */

}
?>
