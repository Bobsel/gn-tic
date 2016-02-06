<?php

require_once("classes/kibo.page.class.php");
require_once("functions/functions.php");
require_once("functions/parsing.php");
require_once("database/db.scans.php");
require_once("database/db.ally.php");
require_once("database/db.gala.php");


class userpage extends kibopage {

  #
  # Eventhandler
  #
  function run () {
    parent::run();
    #functionhash

    $functions['changepwd'] = "changePassword()";
    $functions['fleet'] = "User_fleet()";
    $functions['fleetparser'] = "User_fleet_parser()";
    $functions['fleetmanuell'] = "User_fleet_manuell()";
    $functions['orbitmanuell'] = "User_orbit_manuell()";
    $functions['orbitparser'] = "User_orbit_parser()";
    $functions['settings'] = "UserSettings()";
    $functions['showdetails'] = "showUserDetails()";
    $functions['fleets'] = "Fleets_List()";
    $functions['fleetstatus'] = "User_fleet_status()";
    $functions['activitycheck'] = "User_activity_check()";
    
    #handle action
    if ($functions[$this->action]) {
      eval("\$this->".$functions[$this->action].";");
    }
    #default
    $this->_header("index.php");
  }

  function User_activity_check() {
    if(!do_check_activity($this->userdata)) $this->_header();
    user_activity_check($this->userdata['uid']);
    $this->_header();
  }

  #############################################################################
  function Fleets_list() {
    $filter = $_SESSION['userfleetfilter'];
    #filter setzen
    if (!$filter){
      $galalist = getGalaListbyAlly($this->userdata['aid']);
      if (count($galalist)){
        $filter['gala'] = $galalist[0]['gala'];
      }
    }
    $filter['ally'] = $this->userdata['aid'];
    $filter['order'] = "asc";
    $filter['sort'] = "koords";
    #filter neu setzen
    if ($_POST['subaction'] == "filter") {
      $gala = param_num("galaxy");
    } else {
      $gala = $filter['gala'];
    }
    $ally = $filter['ally'];
    $galalist = getGalaListbyAlly($ally);
    if (count($galalist)) {
      if ($gala) {
        for($i=0;$i < count($galalist);$i++){
          if($galalist[$i]['gala'] == $gala){
            $filter['gala'] = $gala;
            $galalist[$i]['selected'] = "selected";
            break;
          }
        }
      } else {
        $gala = $galalist[0]['gala'];
        $filter['gala'] = $gala;
      }
    } else {
      unset($filter['gala']);
    }
    if ($_POST['subaction'] == "filter") {
      $_SESSION['userfleetfilter'] = $filter;
    }
    if ($gala){
      $list = listUser($filter,&$pages,1,12);
      for($i=0;$i < count($list);$i++){
        $list[$i]['fleets'] = user_fleet_list_byuser($list[$i]['uid']);
        $gesamt = array();
        $gesamt['dir'] = "Gesamt";
        $gesamt['name'] = "Gesamt";
        $gesamt['class'] = "bold";
        $list[$i]['fleets'][0]['name'] = "Orbit";
        $list[$i]['fleets'][1]['name'] = "Flotte 1";
        $list[$i]['fleets'][2]['name'] = "Flotte 2";
        for($j=0;$j < count($list[$i]['fleets']);$j++){
          $fleet = &$list[$i]['fleets'][$j];
          if($fleet['return_flight']) {
            $fleet['dir'] = "Rückflug";
            $fleet['class'] = "blue";
          } else {
            if ($fleet['status'] == 1) {
              $fleet['dir'] = "Angriff";
              $fleet['class'] = "red";
            }
            elseif ($fleet['status'] == 2) {
              $fleet['dir'] = "Verteidigung";
              $fleet['class'] = "green";
            } else {
              $fleet['dir'] = "Im Orbit";
            }
          }
          if($fleet['tgala']) $fleet['dir'] .= " (".$fleet['tgala'].":".$fleet['tpos'].")";
          $fleet['irc'] = generate_irc_user_fleet($j,$fleet,$list[$i]);
          $gesamt['cancris'] += $fleet['cancris'];
          $gesamt['kleptoren'] += $fleet['kleptoren'];
          $gesamt['fregatten'] += $fleet['fregatten'];
          $gesamt['zerstoerer'] += $fleet['zerstoerer'];
          $gesamt['bomber'] += $fleet['bomber'];
          $gesamt['jaeger'] += $fleet['jaeger'];
          $gesamt['schlachter'] += $fleet['schlachter'];
          $gesamt['traeger'] += $fleet['traeger'];
          $gesamt['kreuzer'] += $fleet['kreuzer'];
        }
        $gesamt['irc'] = generate_irc_user_fleet(3,$gesamt,$list[$i]);
        $list[$i]['fleets'][3] = $gesamt;
        if($list[$i]['fleetupdate']) {
          $list[$i]['fleetdate'] = formatdate_unix("d.m.Y",$list[$i]['fleetupdate']);
          $list[$i]['fleettime'] = date("H:i",$list[$i]['fleetupdate']);
        }
      }
      $this->template->assign('list',$list);
    }
    $this->template->assign("ally",getAlly($this->userdata['aid']));
    $this->template->assign('galalist',$galalist);
    $this->show('userfleets_index',"Flottenpflege");
  }

  #############################################################################

  function User_fleet() {
    $id = param_num("id");
    if ($id) {
      $user = getUserByID($id);
      if(!$user || $user['aid'] != $this->userdata['aid']) $this->_header();
      $this->template->assign("id",$id);
    } else {
      $id = $this->userdata['uid'];
      $user = $this->userdata;
    }

    $deff['rubium'] = $user['rubium'];
    $deff['pulsar'] = $user['pulsar'];
    $deff['horus'] = $user['horus'];
    $deff['coon'] = $user['coon'];
    $deff['centurion'] = $user['centurion'];
    $this->template->assign("deff",$deff);
    $fleets = user_fleet_list_byuser($id);
    if($user['fleetupdate']) {
      $this->template->assign("fleettime",date("H:i",$user['fleetupdate']));
      $this->template->assign("fleetdate",formatdate_unix("d.m.Y",$user['fleetupdate']));
    }
    if($user['deffupdate']) {
      $this->template->assign("defftime",date("H:i",$user['deffupdate']));
      $this->template->assign("deffdate",formatdate_unix("d.m.Y",$user['deffupdate']));
    }
    for($i=0;$i < 3;$i++){
      if($fleets[$i]['return_flight']) {
        $fleets[$i]['dir'] = "Rückflug";
        $fleets[$i]['class'] = "blue";
      } else {
        if ($fleets[$i]['status'] == 1) {
          $fleets[$i]['dir'] = "Angriff";
          $fleets[$i]['class'] = "red";
        }
        elseif ($fleets[$i]['status'] == 2) {
          $fleets[$i]['dir'] = "Verteidigung";
          $fleets[$i]['class'] = "green";
        } else {
          $fleets[$i]['dir'] = "Im Orbit";
        }
      }
      if($fleets[$i]['tgala']) $fleets[$i]['dir'] .= " (".$fleets[$i]['tgala'].":".$fleets[$i]['tpos'].")";
      if($fleets[$i]['arrival']) {
        $fleets[$i]['eta'] = $fleets[$i]['arrival']-time();
        if ($fleets[$i]['eta'] < 0) {
          $fleets[$i]['orbit'] = "noch ".$this->formattime($fleets[$i]['orbittime']*60 + $fleets[$i]['eta'],true)." im Orbit";
          $fleets[$i]['eta'] = 0;
        } else {
          $fleets[$i]['orbit'] = $this->formattime($fleets[$i]['orbittime']*60,true)." im Orbit";
        }
        $fleets[$i]['eta'] = "ETA ".$this->formattime($fleets[$i]['eta'],true);
      }
    }
    $this->template->assign("fleets",$fleets);
    $this->template->assign("user",$user);
    $this->show("user_fleetupdate","Flottenupdate");
  }
  #############################################################################

  function User_fleet_status() {
    $id = param_num("id");
    if ($id) { $this->template->assign("id",$id); } else { $id = $this->userdata['uid']; }
    
    if(!($user = getUserByID($id)) || $user['aid'] != $this->userdata['aid']) $this->_header();
    
    #information message, step 2
    if ($_SESSION['steps']['fleetstatus']) {
      #unset step
      unset($_SESSION['steps']['fleetstatus']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Flottenstatus updaten";
      $this->forms['information']['message'] = "Flottenstatus aktualisiert";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Flottenstatus updaten");
    }
    
    $fleetnum = param_num("fleet");
    $fleet = user_fleet_bynum($id,$fleetnum);
    
    if(!$fleet) $this->_header("index.php");
    
    $form = new formContainer();
    $form->add(new formInput("target","Zielkoordinaten","string",true,10,true,"'^\s*(\d{1,4}):(\d{1,2})\s*$'is"));
    $form->add(new formInput("eta","Flugzeit (ETA)","string",true,10,false,"'^\s*(?:(\d{1,3})|(\d{1,2}):(\d{1,2}))\s*$'is"));
    
    $status_titles[] = array("title" => "Im Orbit","value"=>0);
    $status_titles[] = array("title"=>"Angriffsflug","value" => 1);
    $status_titles[] = array("title"=>"Verteidigung","value"=>2);

    if(!$fleet['status']) {
      $status_titles[] = array("value"=>3,"title"=>"Rückflug");
    } else {
      if($fleet['tgala'] && $fleet['tpos']) {
        $galastr = " von ".$fleet['tgala'].":".$fleet['tpos'];
      }
      if($fleet['status'] == 1) $typestr = "Angriff";
      else $typestr = "Verteidigung";
      $status_titles[] = array("title"=>"Rückflug ".$typestr.$galastr,"value"=>3);
    }

    $form->add(new formSelectBox("status","Flottenstatus","numeric",$status_titles,false));
    
    $orbit = new formInput("orbit","Zeit im Orbit","string",true,10,false,"'^(?:(\d+):(\d+)|(\d+))$'is");
    $defftype = new formSelectBox("defftype","Verteidigung","numeric",
      array(
        array("title" => "Galaintern (".$this->formattime(270*60,true).")", "value" => 270),
        array("title" => "Allyintern (".$this->formattime(300*60,true).")", "value" => 300),
        array("title" => "Meta (".$this->formattime(330*60,true).")", "value" => 330),
        array("title" => "extern (".$this->formattime(360*60,true).")", "value" => 360),
      ),false);
    
    if ($_POST['send']) {
      $form->submit("status");
      $data['status'] = $form->get("status");
      if($_POST['next_x']) {
        switch ($data['status']) {
          case 1: {
            $form->add(&$orbit);
            $form->submit(array("target","orbit","eta"));
            $data['returntime'] = 450;
            break;
          }
          case 2: {
            $form->add(&$orbit);
            $form->add(&$defftype);
            $form->submit(array("target","orbit","defftype","eta"));
            $data['returntime'] = $form->get("defftype");
            break;
          }
          case 3: {
            $form->submit("eta");
          }
        }
        
        if (!$form->hasErrors()) {
          // save step
          if($data['status'] == 3) {
            $eta = $form->getregex("eta");
            if($eta[1]) $eta = $eta[1];
            else $eta = $eta[2]*60+$eta[3];
            if($eta < 0) $eta = 0;
            $data['arrival'] = gnarrival($eta);
          }
          if($data['status'] == 1 || $data['status'] == 2) {
            $eta = $form->getregex("eta");
            if($eta[1]) $eta = $eta[1];
            else $eta = $eta[2]*60+$eta[3];
            
            $orbit = $form->getregex("orbit");
            if($orbit[1]) $orbit = $orbit[1]*60+$orbit[2];
            else $orbit = $orbit[3];
            
            
            if($eta == 0){
              $data['orbittime'] = gnticktime($orbit);
              $data['arrival'] = time();
            } else {
              $data['arrival'] = gnarrival($eta);
              $data['orbittime'] = $orbit;
            }
            $target = $form->getRegex("target");
            $data['tgala'] = $target[1];
            $data['tpos'] = $target[2];
            $data['return_flight'] = 0;
          }
          if($data['status'] == 1 && $data['tgala'] && (user_get_bypos($data['tgala'],$data['tpos']))) {
            $form->setError("target");
            $form->addError("Ziel ist Metamuitglied");
          }
        }
        if (!$form->hasErrors()) {
          $_SESSION['steps']['fleetstatus'] = 1;
          if($data['status'] == 3) {
            $data['status'] = $fleet['status'];
            $data['tgala'] = $fleet['tgala'];
            $data['tpos'] = $fleet['tpos'];
            $data['return_flight'] = 1;
          }
          $data['fleetnum'] = $fleetnum;
          $data['gala'] = $user['gala'];
          $data['pos'] = $user['pos'];
          $data['fid'] = $fleet['fid'];
          if($data['status'] || $data['return_flight']) {
            if($fleet['fsid']) {
              fleetstatus_update($fleet['fsid'],$data);
            } else {
              #$check = fleetstatus_get_byfleetnum($user['gala'],$user['pos'],true);
              #if(count($check) >= 2) fleetstatus_delete($check[0]['fsid']);
              fleetstatus_add($data);
            }
          } else {
            if($fleet['fsid']) fleetstatus_delete($fleet['fsid']);
          }
          $this->_header("user.php?action=fleetstatus&send");
        }
      } else {
        if($data['status'] == 1) $form->add(&$orbit);
        if($data['status'] == 2) {$form->add(&$orbit);$form->add(&$defftype);}
        
        if($data['status'] == 1) {
          if($data['status'] == $fleet['status'] && !$fleet['return_flight']) {
            $form->set("orbit",$this->formattime($fleet['orbittime']*60));
            if($fleet['tgala']) {
              $form->set("target","$fleet[tgala]:$fleet[tpos]");
            }
          } else {
            $form->set("orbit",$this->formattime(75*60));
          }
        }
        if($data['status'] == 2) {
          if($data['status'] == $fleet['status'] && !$fleet['return_flight']) {
            $defftype->select($fleet['returntime']);
            $form->set("orbit",$this->formattime($fleet['orbittime']*60));
            if($fleet['tgala']) {
              $form->set("target",$fleet['tgala'].":".$fleet['tpos']);
            }
          } else {
            $form->set("orbit",$this->formattime(300*60));
            $defftype->select(360);
          }
        }
        if (($fleet['status'] == 2 || $fleet['status'] == 1) && $data['status'] == 3 && !$fleet['return_flight']) {
          $eta = $fleet['arrival']-time();
          if($eta < 0) $eta = 0;
          $form->set("eta",$this->formattime($fleet['returntime']*60-$eta));
        }
        if($data['status'] == $fleet['status'] || $data['status'] == 3 && $fleet['return_flight']) {
          $form->set("eta",$this->formattime($fleet['arrival']-time()));
        }
      }
    } else {
      if($fleet['return_flight'] ) {
        $data['status'] = 3;
      } else {
        $data['status'] = $fleet['status'];
        if(($fleet['status'] == 1 || $fleet['status'] == 2) && $fleet['tgala']) {
          $form->set("target",$fleet['tgala'].":".$fleet['tpos']);
        }
      }
      $form->select("status",$data['status']);
      #$form->set("status",$data['status']);
      if($data['status'] == 1 || $data['status'] == 2) {
        $form->add(&$orbit);
      }
      if ($fleet['arrival']) {
        $eta = $fleet['arrival']-time();
        if($eta < 0) $eta_exact = 0; else $eta_exact = $eta;
        $form->set("eta",$this->formattime($eta_exact));
      }
      if($data['status'] == 2) {
        if($fleet['orbittime']) {
          if($eta < 0) $orbittime = $fleet['orbittime']*60+$eta; else $orbittime = $fleet['orbittime']*60;
          $form->set("orbit",$this->formattime($orbittime));
        }
      }
      if($data['status'] == 1) {
        #echo "eta: ".($eta/60)."<br>";
        if($fleet['orbittime']) {
          if($eta < 0) $orbittime = $fleet['orbittime']*60+$eta; else $orbittime = $fleet['orbittime']*60;
          $form->set("orbit",$this->formattime($orbittime));
        }
      }
      if($data['status'] == 2) {
        $form->add(&$defftype);
        $form->select("defftype",$fleet['returntime']);
      }
    }
    
    $form->registerVars(&$this->template);
    
    $this->template->assign("fleet",$fleetnum);
    $this->show("user_fleetstatus","Flottenstatus updaten");
  }
  #############################################################################
  function User_fleet_parser() {
    $id = param_num("id");
    if ($id) {
      $user = getUserByID($id);
      if(!$user) $this->_header();
      $this->template->assign("id",$id);
    } else {
      $id = $this->userdata['uid'];
    }
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['fleetparser']) {
      #unset step
      unset($data['fleetparser']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Flotte/Deff updaten";
      $this->forms['information']['message'] = "Flotte/Deff aktualisiert";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Flotte/Deff updaten");
    }
    $form = new formContainer();
    $form->add(new formInput("fleet","Text","string"));
    if ($_POST['send'] && $_POST['next_x']) {
      $form->submit();
      if(!$form->hasErrors()) {
        $text = $form->get("fleet");
        if(!($taktik = parse_taktikansicht($text))) {
          $fleets = parse_user_flottenbewegung($text,$this->userdata["gala"],$this->userdata["pos"]);
          //echo "<pre>".print_r($fleets,1)."</pre>";
          $deff = parse_user_verteidigung($text);
          if($fleets === false && $deff === false) {
            $form->setError("fleet");
            $form->addError("es konnte nichts erkannt werden");
          }
        } else {
          $fleets = $taktik[0]['fleets'];
          $deff = $taktik[0]['deff'];
        }
        if(!$form->hasErrors()) {
          $_SESSION['steps']['fleetparser'] = 1;
          if(!($fleets === false)) {
            user_fleets_update($id,$fleets);
          }
          if(!($deff === false)) {
            user_deff_update($id,$deff);
          }
          $this->_header("user.php?action=fleetparser&send");
        }
      }
    }
    $form->registerVars($this->template);
    $this->show("user_fleetupdate_parser","Flottenupdate");
  }
  #############################################################################
  function User_fleet_manuell() {
    $id = param_num("id");
    if ($id) {
      $user = getUserByID($id);
      if(!$user || $user['aid'] != $this->userdata['aid']) $this->_header();
      $this->template->assign("id",$id);
    } else {
      $id = $this->userdata['uid'];
    }
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['fleetmanuell']) {
      #unset step
      unset($data['fleetmanuell']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Flotte updaten";
      $this->forms['information']['message'] = "Flotte aktualisiert";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Flotte updaten");
    }
    $names = array("Jäger"=>"jaeger","Bomber"=>"bomber","Fregatten"=>"fregatten","Zerstörer"=>"zerstoerer","Kreuzer"=>"kreuzer","Schlachtschiffe"=>"schlachter","Träger"=>"traeger","Kleptoren"=>"kleptoren","Schutzschiffe"=>"cancris");
    $fleets = user_fleet_list_byuser($id);
    $form = new formContainer();
    foreach ($names as $title => $name) {
      for ($i=0;$i<3;$i++) {
        $form->add(new formInput($name.$i,$title,"numeric"));
      }
    }
    if ($_POST['send'] && $_POST['next_x']) {
      $form->submit();
      if (!$form->hasErrors()){
        $_result = array();
        foreach ($fleets as $fleet) {
          foreach ($names as $title => $name) {
            $_result[$fleet['fleetnum']][$name] = $form->get($name.$fleet['fleetnum']);
          }
        }
        user_fleets_update($id,$_result);
        $_SESSION['steps']['fleetmanuell'] = 1;
        $this->_header("user.php?action=fleetmanuell&send");
      }
    } else {
      foreach($fleets as $fleet){
        $num = $fleet['fleetnum'];
        foreach ($names as $key => $name){
          $val = &$fleet[$name];
          if ($val){
            $form->set($name.$num,$val);
          } else {
            $form->set($name.$num,0);
          }
        }
      }
    }
#    $this->template->assign("errors",$errors);
#    $this->template->assign("items",$items);
    $form->registerVars($this->template);
    $this->show("user_fleetupdate_manuell","Flottenupdate");
  }
  #############################################################################
  function User_orbit_manuell() {
    $id = param_num("id");
    if ($id) {
      $user = getUserByID($id);
      if(!$user || $user['aid'] != $this->userdata['aid']) $this->_header();
      $this->template->assign("id",$id);
    } else {
      $id = $this->userdata['uid'];
    }
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['orbitmanuell']) {
      #unset step
      unset($data['orbitmanuell']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Verteidigung updaten";
      $this->forms['information']['message'] = "Verteidigung aktualisiert";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Verteidigung updaten");
    }
    $names = array("Abfangjäger"=>"horus","Rubis"=>"rubium","Pulsare"=>"pulsar","Coons"=>"coon","Centurions"=>"centurion");
    if ($_POST['send'] && $_POST['next_x']) {
      foreach ($names as $key => $name){
        $val = trim($_POST[$name]);
        if (strlen($val)){
          if ((is_numeric($val) && $val >= 0)) {
            $deff[$name]= $val;
          } else {
            $errors[] = "$key ungültig";
            $items[$name]['class'] = "_error";
          }
          $items[$name]['value'] = $val;
        }
      }
      if (!$errors){
        $data['orbitmanuell'] = 1;
        $_SESSION['steps'] = $data;
        User_deff_update($id,$deff);
        $this->_header("user.php?action=orbitmanuell&send");
      }
    } else {
      $deff = User_deff_get($id);
      foreach ($names as $key => $name){
        $val = $deff[$name];
        if ($val){
          $items[$name]['value'] = $val;
        } else {
          $items[$name]['value'] = 0;
        }
      }
    }
    $this->template->assign("errors",$errors);
    $this->template->assign("items",$items);
    $this->show("user_orbitalupdate_manuell","Orbitalverteidigung updaten");
  }
  #############################################################################
  function User_orbit_parser() {
    $id = param_num("id");
    if ($id) {
      $user = getUserByID($id);
      if(!$user || $user['aid'] != $this->userdata['aid']) $this->_header();
      $this->template->assign("id",$id);
    } else {
      $id = $this->userdata['uid'];
    }
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['orbitparser']) {
      #unset step
      unset($data['orbitparser']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Verteidigung updaten";
      $this->forms['information']['message'] = "Verteidigung aktualisiert";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Verteidigung updaten");
    }
    $names = array("Horus"=>"horus","Rubium"=>"rubium","Pulsar"=>"pulsar","Coon"=>"coon","Centurion"=>"centurion");
    if ($_POST['send'] && $_POST['next_x']) {
      $val = stripslashes(param_str("deff"));
      $items['deff']['value'] = $val;
      if (!$val){
        $errors[] = "Feld ist leer!";
        $items['deff']['class'] = "_error";
      } else {
         if(preg_match("/^.*?".
          "Leichtes Orbitalgeschütz \"Rubium\":.*?(\d+?)\s*?".
          "Leichtes Raumgeschütz \"Pulsar\":.*?(\d+?)\s*?".
          "Mittleres Raumgeschütz \"Coon\":.*?(\d+?)\s*?".
          "Schweres Raumgeschütz \"Centurion\":.*?(\d+?)\s*?".
          "Abfangjäger \"Horus\":.*?(\d+?)\s*?".
          "$/si",$val,$result)) {
          $deff['rubium'] = $result[1];
          $deff['pulsar'] = $result[2];
          $deff['coon'] = $result[3];
          $deff['centurion'] = $result[4];
          $deff['horus'] = $result[5];
         } else {
          $errors[] = "Verteidigung nicht erkannt!";
          $items['deff']['class'] = "_error";
         }
      }
      if (!$errors){
        $data['orbitparser'] = 1;
        $_SESSION['steps'] = $data;
        User_deff_update($id,$deff);
        $this->_header("user.php?action=orbitparser&send");
      }
    }
    $this->template->assign("errors",$errors);
    $this->template->assign("items",$items);
    $this->show("user_orbitalupdate_parser","Orbitalverteidigung updaten");
  }  #############################################################################
  #
  # Password ändern
  #
  function changePassword() {
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['changepwd']) {
      #save registration step
      unset($data['changepwd']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['action'] = "";
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Passwort &auml;ndern";
      $this->forms['information']['message'] = "Passwort&auml;nderung erfolgreich";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Passwort &auml;ndern");
    }
    #formular send
    if ($_REQUEST['step']) {
      $items['oldpassword'] = param_str("oldpassword",true);;
      $items['password'] = param_str("password",true);
      $items['password2'] = param_str("password2",true);
      $errors = false;
      #check if empty
      foreach ( $items as $key => $value) {
        if (!$value) {
          $this->forms['changepwd']['fields'][$key]['error'] = 'Feld darf nicht leer sein!';
          $this->forms['changepwd']['fields'][$key]['bgrd'] = '_error';
          $errors = true;
        } else {
          $this->forms['changepwd']['fields'][$key]['value'] = $value;
        }
      }
      #check passwords
      if (!$errors && $items['password'] != $items['password2']) {
        $errors = true;
        $this->forms['changepwd']['fields']['password']['error'] = 'Passw&ouml;rter m&uuml;ssen gleich sein!';
        $this->forms['changepwd']['fields']['password']['bgrd'] = '_error';
        $this->forms['changepwd']['fields']['password2']['error'] = 'Passw&ouml;rter m&uuml;ssen gleich sein!';
        $this->forms['changepwd']['fields']['password2']['bgrd'] = '_error';
      }
      #check old password
      if (!$errors && $this->userdata['password'] != md5($items['oldpassword'])) {
        $errors = true;
        $this->forms['changepwd']['fields']['oldpassword']['error'] = 'Passwort ung&uuml;ltig!';
        $this->forms['changepwd']['fields']['oldpassword']['bgrd'] = '_error';
      }
      if (!$errors) {
        updateUserPassword($this->userdata['uid'],$items['password']);
        $sessionuserdata['id'] = $this->userdata['uid'];
        $sessionuserdata['password'] = md5($items['password']);
        $_SESSION['sessionuserdata'] = $sessionuserdata;
        addToLogfile("Passwort geändert","User",$this->userdata['uid']);

        #save step
        $data['changepwd'] = 1;
        $_SESSION['steps'] = $data;
        $this->_header("user.php?action=changepwd&send");
      }
    }
    if ($this->userdata['changepw']) {
      $this->forms['changepwd']['message'] = "Sie müssen ihr Passwort jetzt ändern !";
    }
    $this->forms['changepwd']['url'] = 'user.php';
    $this->forms['changepwd']['action'] = 'changepwd';
    $this->show('user_changepwd_form',"Passwort &auml;ndern");
  }
  #
  # ändert die Benutzerdaten
  #
  function UserSettings() {
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['usersettings']) {
      #save step
      unset($data['usersettings']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['action'] = "";
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Benutzerdaten &auml;ndern";
      $this->forms['information']['message'] = "&Auml;nderung erfolgreich";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Benutzerdaten &auml;ndern");
    }
    #formular send
    if ($_REQUEST['step']) {
      $items['email']['value'] = param_str("email",true);
      $items['nick']['value'] = param_str("nick",true);
      $items['login']['value'] = param_str("login",true);
      $items['svs']['value'] = param_num("svs",0);
      $items['fleettype']['value'] = param_num("fleettype",1);
      $items['scantype']['value'] = param_num("scantype",0);
      $items['timeview']['value'] = param_num("timeview",0);

      if (!$items['email']['value']) {
        $errors[] = "Email fehlt!";
        $items['email']['bgrd'] = "_error";
      }
      if (!$items['nick']['value']) {
        $errors[] = "GN Nickname fehlt!";
        $items['nick']['bgrd'] = "_error";
      }
      if (!$items['login']['value']) {
        $errors[] = "Login fehlt!";
        $items['login']['bgrd'] = "_error";
      }
      if ($_POST['emailvisible']) {
        $items['emailvisible']['value'] = 1;
      } else {
        $items['emailvisible']['value'] = 0;
      }
      #optional parameters
      $items['phone']['value'] = param_str("phone",true);

      #check nickname
      if ($items['nick']['value'] && strtolower($items['nick']['value']) != strtolower($this->userdata['nick']) && getUserByNick($items['nick']['value'])) {
        $errors[] = "Nickname existiert bereits";
        $items['nick']['bgrd'] = "_error";
      }
      #check login
      if ($items['login']['value'] && strtolower($items['login']['value']) != strtolower($this->userdata['login']) && getUserByLogin($items['login']['value'])) {
        $errors[] = "Login bereits vergeben";
        $items['login']['bgrd'] = "_error";
      }
      if (!$errors) {
        #save step
        $data['usersettings'] = 1;
        $_SESSION['steps'] = $data;
        addToLogfile("Benutzereinstellungen geändert","User",$this->userdata['uid']);
        updateUser($this->userdata['uid'],
          $items['nick']['value'],
          $items['login']['value'],
          $items['email']['value'],
          $items['emailvisible']['value'],
          $items['phone']['value'],
          $items['scantype']['value'],
          $items['svs']['value'],
          $items['timeview']['value'],
          $items['fleettype']['value']
          );
        $this->_header("user.php?action=settings&send");
      }
    } else {
      $items['login']['value'] = $this->userdata['login'];
      $items['nick']['value'] = $this->userdata['nick'];
      $items['email']['value'] = $this->userdata['email'];
      $items['emailvisible']['value'] = $this->userdata['emailvisible'];
      $items['phone']['value'] = $this->userdata['phone'];
      $items['scantype']['value'] = $this->userdata['scantype'];
      $items['svs']['value'] = $this->userdata['svs'];
      $items['timeview']['value'] = $this->userdata['timeview'];
      $items['fleettype']['value'] = $this->userdata['fleettype'];
    }
    if (!$items['phone']['value']) {
      $items['phone']['bgrd'] = "_optional";
    }
    $this->template->assign("scantype".$items['scantype']['value'],"checked");
    $this->template->assign("timeview".$items['timeview']['value'],"checked");
    $this->template->assign("fleettype".$items['fleettype']['value'],"checked");
    $this->template->assign("errors",$errors);
    $this->template->assign("items",$items);
    $this->show('user_settings_form',"Benutzerdaten &auml;ndern");
  }
  #
  # Zeigt Benutzerinfos
  #
  function showUserDetails() {
    $id = param_num("id");
    if (!$id) $this->_header($this->backtracking->backlink());
    $return = getUserByID($id);
    if (!$return) $this->_header($this->backtracking->backlink());
    $this->forms['userdetails']['registerdate'] = $return['registerdate'];
    $this->forms['userdetails']['nickname'] = $return['nick'];
    $this->forms['userdetails']['allyname'] = $return['name'];
    $this->forms['userdetails']['allytag'] = $return['tag'];
    if($return['emailvisible']) {
            $this->forms['userdetails']['email'] = $return['email'];
    }
    $this->forms['userdetails']['koords'] = $return['gala'].":".$return['pos'];
    if($return['usertitle']) {
            $this->forms['userdetails']['usertitle'] = $return['usertitle'];
    }
    if($return['phone']) {
            $this->forms['userdetails']['phone'] = $return['phone'];
    }
    $this->show('user_details',"Benutzerdetails");
  }
}
?>