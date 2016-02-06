<?php

require_once("classes/kibo.page.class.php");
require_once("classes/gnsimu.php");
require_once("functions/functions.php");


class simulatorpage  extends kibopage {

  #
  # Eventhandler
  #
  function run () {
    parent::run();
    #functionhash

    #handle action
#    if ($functions[$this->action]) {
 #     eval("\$this->".$functions[$this->action].";");
  #  }
    #default
    $this->_showSimulator();
  }
  #
  # stellt den Simulator dar mit allen Funktionen
  #
  function _showSimulator() {
    for ($i=0;$i < 14;$i++) {
      if (isset($_REQUEST['deff'.$i]) && is_numeric($_REQUEST['deff'.$i]) && $_REQUEST['deff'.$i] >= 0) {
        $deff[$i] = $_REQUEST['deff'.$i];
      } else {
        $deff[$i] = 0;
      }
    }
    for ($i=0;$i < 9;$i++) {
      if (isset($_REQUEST['attack'.$i]) && is_numeric($_REQUEST['attack'.$i]) && $_REQUEST['attack'.$i] >= 0) {
        $attack[$i] = $_REQUEST['attack'.$i];
      } else {
        $attack[$i] = 0;
      }
    }

    $kristall = param_num("kristall",0);
    $metall = param_num("metall",0);

    $Simu = new GNSimu();
    $Simu->attacking = $attack;
    $Simu->deffending = $deff;
    $Simu->mexen = $metall;
    $Simu->kexen = $kristall;

    $ticks = param_num("ticks",1);

    $fleets_loaded = $_SESSION['fleets_loaded'];

    if ($_POST['send']) {
      if ($fleets_loaded){
        for ($i=0;$i<count($fleets_loaded);$i++) {
          if ($fleets_loaded[$i] && $_POST["deleteattfleet_".$fleets_loaded[$i]['uid']."_x"]) {
            #echo $fleets_loaded[$i]['nick']." löschen <br>";
            $attack[0] -= $fleets_loaded[$i]['jaeger'];
            $attack[1] -= $fleets_loaded[$i]['bomber'];
            $attack[2] -= $fleets_loaded[$i]['fregatten'];
            $attack[3] -= $fleets_loaded[$i]['zerstoerer'];
            $attack[4] -= $fleets_loaded[$i]['kreuzer'];
            $attack[5] -= $fleets_loaded[$i]['schlachter'];
            $attack[6] -= $fleets_loaded[$i]['traeger'];
            $attack[7] -= $fleets_loaded[$i]['kleptoren'];
            $attack[8] -= $fleets_loaded[$i]['cancris'];
            for ($j=0;$j<count($attack);$j++) {
              if ($attack[$j] < 0) $attack[$j] = 0;
            }
            #element löschen und rest verschieben
            for ($j=$i;$j<count($fleets_loaded)-1;$j++) {
              $fleets_loaded[$j] = $fleets_loaded[$j+1];
            }
            unset($fleets_loaded[count($fleets_loaded)-1]);
            $_SESSION['fleets_loaded'] = $fleets_loaded;
            break;
          }
        }
      }
      if ($_POST['resetatter'] && $this->userdata) {
        unset($_SESSION['fleets_loaded']);
        unset($fleets_loaded);
        for ($i=0;$i<count($attack);$i++) {
          $attack[$i] = 0;
        }
      }
      if ($_POST['loadattfleet'] && $this->userdata) {
        $loadattfleet = $_POST['loadattfleet'];
        $userfleet = user_fleet_sum($loadattfleet);
        $user = getUserByID($loadattfleet);
        #user wrong or no fleet
        if (!$userfleet) {
          $this->_header("index.php","Userid falsch oder keine Flotte");
        }
        #wrong attfleet, no permission
        if (!$this->userdata['rights']['admin'] && !$this->userdata['rights']['attorga'] && $user['aid'] != $this->userdata['aid']){
          $this->_header("index.php","Keine Rechte zum Flotte laden");
        }
        for ($i=0;$i<count($fleets_loaded);$i++) {
          if ($fleets_loaded[$i] && $fleets_loaded[$i]['uid'] == $loadattfleet) {
            $isloaded = $i;
            break;
          }
        }
        #bash
        if ($_POST['attfleetselect'] == 1) {
          $userfleet['kleptoren'] = 0;
          $userfleet['cancris'] = 0;
        }
        #bash + cleps
        if ($_POST['attfleetselect'] == 2) {
          $userfleet['cancris'] = 0;
        }
        #cleponly
        if ($_POST['attfleetselect'] == 3) {
          $userfleet['jaeger'] = 0;
          $userfleet['bomber'] = 0;
          $userfleet['fregatten'] = 0;
          $userfleet['zerstoerer'] = 0;
          $userfleet['kreuzer'] = 0;
          $userfleet['schlachter'] = 0;
          $userfleet['traeger'] = 0;
          $userfleet['cancris'] = 0;
        }
        #clepdeff
        if ($_POST['attfleetselect'] == 4) {
          $userfleet['jaeger'] = 0;
          $userfleet['bomber'] = 0;
          $userfleet['fregatten'] = 0;
          $userfleet['zerstoerer'] = 0;
          $userfleet['kreuzer'] = 0;
          $userfleet['schlachter'] = 0;
          $userfleet['kleptoren'] = 0;
          $userfleet['cancris'] = 0;
        }
        #flotte schon geladen
        if (isset($isloaded)) {
          $fleets_loaded[$isloaded]['jaeger'] += $userfleet['jaeger'];
          $fleets_loaded[$isloaded]['bomber'] += $userfleet['bomber'];
          $fleets_loaded[$isloaded]['fregatten'] += $userfleet['fregatten'];
          $fleets_loaded[$isloaded]['zerstoerer'] += $userfleet['zerstoerer'];
          $fleets_loaded[$isloaded]['kreuzer'] += $userfleet['kreuzer'];
          $fleets_loaded[$isloaded]['schlachter'] += $userfleet['schlachter'];
          $fleets_loaded[$isloaded]['traeger'] += $userfleet['traeger'];
          $fleets_loaded[$isloaded]['kleptoren'] += $userfleet['kleptoren'];
        #neu laden
        } else {
          $userfleet['fleetdate'] = formatdate("d.m.y",$userfleet['fleetdate']);
          $fleets_loaded[] = $userfleet;
        }
        $attack[0] += $userfleet['jaeger'];
        $attack[1] += $userfleet['bomber'];
        $attack[2] += $userfleet['fregatten'];
        $attack[3] += $userfleet['zerstoerer'];
        $attack[4] += $userfleet['kreuzer'];
        $attack[5] += $userfleet['schlachter'];
        $attack[6] += $userfleet['traeger'];
        $attack[7] += $userfleet['kleptoren'];
        $attack[8] += $userfleet['cancris'];
        $_SESSION['fleets_loaded'] = $fleets_loaded;
      }
      # tick laden
      if ($_POST['loadtick_0'] || $_POST['loadtick_1'] || $_POST['loadtick_2'] || $_POST['loadtick_3']
            || $_POST['loadtick_4'] || $_POST['loadtick_5'] || $_POST['loadtick_6']) {
        # 2 vorticks
        if ($_POST['loadtick_0']) $tickload = 0;
        if ($_POST['loadtick_1']) $tickload = 1;
        #5 hauptticks
        if ($_POST['loadtick_2']) $tickload = 2;
        if ($_POST['loadtick_3']) $tickload = 3;
        if ($_POST['loadtick_4']) $tickload = 4;
        if ($_POST['loadtick_5']) $tickload = 5;
        if ($_POST['loadtick_6']) $tickload = 6;

        #vorticks berechnen oder laden
        if ($_POST['vorticks'] || $tickload < 2) {
          $Simu->vorticks(0);
          if ($_POST['vorticks'] && $tickload != 0) {
            $Simu->vorticks(1);
          }
        }
        for ($i=2;($i< $tickload);$i++) {
          $Simu->Compute(0);
        }
        if ($tickload > 1) {
          if ($ticks == $tickload-1) {
            #den letzten tick laden
            $Simu->Compute(1);
          } else {
            #tick laden
            $Simu->Compute(0);
          }
        }
        $attack = $Simu->attacking;
        $deff = $Simu->deffending;
      }
      if ($_POST['calculate']) {
        if ($_POST['vorticks']) {
          $Simu->vorticks(0);
          $tick[1] = $Simu->attacking;
          $tick[0] = $Simu->Oldatt;
          $tick[3] = $Simu->deffending;
          $tick[2] = $Simu->Olddeff;
          $tick['metall'] = $Simu->stolenmexen;
          $tick['kristall'] = $Simu->stolenkexen;
          $tick['name'] = "Vortick 1";
          $tick['nr'] = 0;
          $ticklist[] = $tick;
          $Simu->vorticks(1);
          $tick[1] = $Simu->attacking;
          $tick[0] = $Simu->Oldatt;
          $tick[3] = $Simu->deffending;
          $tick[2] = $Simu->Olddeff;
          $tick['metall'] = $Simu->stolenmexen;
          $tick['kristall'] = $Simu->stolenkexen;
          $tick['name'] = "Vortick 2";
          $tick['nr'] = 1;
          $ticklist[] = $tick;
        }
        for ($i=0;$i<$ticks-1;$i++) {
          $Simu->Compute(0);
          $tick[1] = $Simu->attacking;
          $tick[0] = $Simu->Oldatt;
          $tick[3] = $Simu->deffending;
          $tick[2] = $Simu->Olddeff;
          $tick['metall'] = $Simu->stolenmexen;
          $tick['kristall'] = $Simu->stolenkexen;
          $tick['name'] = "Tick ".($i+1);
          $tick['nr'] = $i+2;
          $ticklist[] = $tick;
        }
        $Simu->Compute(1);
        $tick[1] = $Simu->attacking;
        $tick[0] = $Simu->Oldatt;
        $tick[3] = $Simu->deffending;
        $tick[2] = $Simu->Olddeff;
        $tick['metall'] = $Simu->stolenmexen;
        $tick['kristall'] = $Simu->stolenkexen;
        $tick['name'] = "Tick ".$ticks;
        $tick['nr'] = $ticks+1;
        $ticklist[] = $tick;
        $this->template->assign('ticklist',$ticklist);
        $this->template->assign('attlost',$Simu->geslostshipsatt);
        $this->template->assign('defflost',$Simu->geslostshipsdeff);
        $ress[0][0] = $Simu->getlostmetall[0];
        $ress[1][0] = $Simu->getlostmetall[1];
        $ress[0][1] = $Simu->getlostkristall[0];
        $ress[1][1] = $Simu->getlostkristall[1];
        $ress[0][2] = $ress[0][1]+$ress[0][0];
        $ress[1][2] = $ress[1][1]+$ress[1][0];

        for ($i=0;$i<2;$i++) {
          for ($j=0;$j<3;$j++) {
            $ress[$i][$j] = substr_replace(strrev(chunk_split(strrev($ress[$i][$j]),3,'.')),'',0,1);
          }
        }
        $this->template->assign('ress',$ress);
        $this->template->assign('metallstolen',$Simu->gesstolenexenm);
        $this->template->assign('kristallstolen',$Simu->gesstolenexenk);
      }
      $this->template->assign('deffsel'.$ticks,'selected');
      if ($_POST['vorticks']) $this->template->assign('vortickssel','checked');
      $this->template->assign('attfleetselect'.$_POST['attfleetselect'],'selected');
    } else {
      $this->template->assign('deffsel1','selected');
      $this->template->assign('attfleetselect1','selected');
    }

    $this->template->assign('metall',$metall);
    $this->template->assign('kristall',$kristall);
    $this->template->assign('deff',$deff);
    $this->template->assign('attack',$attack);

    $attfleets = array();
    if ($this->userdata['rights']['attorga'] == 1) {
      $except = array();
      #for ($i=0;$i<count($fleets_loaded);$i++) {
      #  if ($fleets_loaded[$i]) $except[] = $fleets_loaded[$i]['id'];
      #}
      $attfleets = user_get_except($except);
    } else {
        $attfleets = user_get_except($except,$this->userdata['aid']);
#        if ($this->userdata['fleetupdate']) {
#          $attfleets[] = array("id" => $this->userdata['uid'],"wert" => $this->userdata['nick']." (".$this->userdata['gala'].":".$this->userdata['pos'].")");
#        }
  #    if (!$fleets_loaded[0] && $this->userdata['fleet']) {
  #      $attfleets[] = array("id" => $this->userdata['uid'],"wert" => $this->userdata['nick']." (".$this->userdata['gala'].":".$this->userdata['pos'].")");
  #    }
    }
    for ($i=0;$i<count($attfleets);$i++) {
      $attfleets[$i]['id'] = $attfleets[$i]['uid'];
      $attfleets[$i]['wert'] = $attfleets[$i]['nick']." (".$attfleets[$i]['gala'].":".$attfleets[$i]['pos'].")";
    }
    $this->template->assign("fleets_loaded",$fleets_loaded);
    $this->template->assign("attfleets",$attfleets);
    $this->show('simulator_form','Kampfsimulator');
  }

}
?>