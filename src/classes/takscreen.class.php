<?php

require_once("classes/kibo.page.class.php");
require_once("functions/functions.php");
require_once("functions/parsing.php");
require_once("database/db.takscreen.php");
require_once("database/db.user.php");
require_once("database/db.ally.php");
require_once("database/db.gala.php");

class taktikscreen  extends kibopage {

  #
  # Eventhandler
  #
  function run () {
    parent::run();
    #functionhash
    
    $functions["addatter"] = "Incoming_addatter()";
    $functions["adddeffer"] = "Incoming_adddeffer()";
    
    $functions["fleetstatus"] = "Incoming_fleetstatus()";
    
    $functions["details"] = "Incoming_details()";
    $functions["recall"] = "Fleet_recall()";
//    $functions["defferrecall"] = "Deffer_recall()";
//    $functions["atterrecall"] = "Atter_recall()";
    $functions["recallall"] = "Atter_recallall()";
    
    $functions["updateatter"] = "Atter_update()";
    $functions["updatedeffer"] = "Deffer_update()";
    
    $functions["safe"] = "Incoming_setsafe()";
    $functions["unsafe"] = "Incoming_setunsafe()";
    
    $functions["miliparser"] = "parser_mili()";
    $functions["takparser"] = "parser_takscreen()";
    $functions["fleetparser"] = "parser_fleet()";
    $functions["hidefleets"] = "incoming_details_hidefleets()";
    $functions["resetfilter"] = "incoming_details_resetfilter()";

    #handle action
    if ($functions[$this->action]) {
      eval("\$this->".$functions[$this->action].";");
    }
    #default
    $this->Incoming_list();
  }
  #############################################################################
  function _match_atter($gala,$pos,$line,$messages,$errors) {
    $user_atterlist = fleetstatus_get_bytarget($gala,$pos,1);
    $insert = array();
    if(preg_match_all("/(\d{1,4}):(\d{1,2})(.*?)<br>/ism",$line[8],$tak_atterlist,PREG_SET_ORDER)) {
      if(count($tak_atterlist)){
        $tak_timelist = parse_time($line[9]);
        #atterliste
        for($j=0;$j < count($tak_atterlist);$j++){
          unset($atter);
          $tak_eta = $tak_timelist[$j];
          if($tak_eta == "error") {
              $errors[] = "atter (".$tak_atterlist[$j][1].":".$tak_atterlist[$j][2].") zeit nicht erkannt";
              continue;
          }
          #5 ticks im orbit
          if ($tak_eta == 0) {
            $tak_eta_low = -75;
            $tak_eta_high = 0;
          } else {
            #toleranz von einem tick
            $tak_eta_low = $tak_eta - 14;
            $tak_eta_high = $tak_eta + 14;
          }
          #passenden Atter finden
          for($k=0;$k < count($user_atterlist);$k++){
            $eta = floor(($user_atterlist[$k]['arrival'] - time())/60);
            if( $user_atterlist[$k]['gala'] == $tak_atterlist[$j][1] &&
                $user_atterlist[$k]['pos'] == $tak_atterlist[$j][2] &&
                !($user_atterlist[$k]['checked'])  &&
                $eta > $tak_eta_low &&
                $eta < $tak_eta_high
            ) {
              #passenden atter gefunden
              $atter = &$user_atterlist[$k];
              break;
            }
          }
          if (isset($atter)) {
            #atter matched
            $atter['checked'] = true;
          } else {
            $insert[] = array(
              "gala" => trim($tak_atterlist[$j][1]),
              "pos" => trim($tak_atterlist[$j][2]),
              "tgala" => $gala,
              "tpos" => $pos,
              "fleetnum" => 2,
              "eta" => $tak_eta,
              "nick" => trim($tak_atterlist[$j][3])
            );
          }
        }
      }
    }
    if(!$errors) {      
      #incs auf recall prüfen
      for($j=0;$j < count($user_atterlist);$j++){
        $atter = &$user_atterlist[$j];
        
        if(!$atter['checked']) {
          #nach zweiter flotte suchen die im selben tick fliegt aber nicht recallt hat
          $eta = floor(($atter['arrival']-time())/60);
          if ($eta > 0) {
            $eta_high = $eta + 14;
            $eta_low = $eta-14;
            if($eta_low < 0) {
              $eta_low = 1;
            }
          } else {
            $eta_low = 0;
            $eta_high = 0;
          }
          $found = false;
          for($k=0;$k < count($user_atterlist);$k++){
            $eta_search = floor(($user_atterlist[$k]['arrival']-time())/60);
            #im orbit
            if($eta_search < 0) {
              $eta_search = 0;
            }
            if( $user_atterlist[$k]['checked'] &&
                $user_atterlist[$k]['gala'] == $atter['gala'] &&
                $user_atterlist[$k]['pos'] == $atter['pos'] &&
                $eta_search >= $eta_low &&
                $eta_search <= $eta_high
            ) {
              #flotte gefunden die im selben tick fliegt und gematcht wurde
              #-> unklar welche von beiden recallt hat
              $messages[] = "<b><span class=\"red\">(".$atter['gala'].":".$atter['pos'].") ".$atter['nick']." attet mit 2 Flotten und hat eine von beiden recallt, bitte überprüfen</span></b>";
              $found = true;
              break;
            }
          }
          if(!$found){
            $messages[] = "<span class=\"blue\">Atter (".$atter['gala'].":".$atter['pos'].") ".$atter['nick']." Flotte ".$atter['fleetnum']." ETA ".$this->formattime($atter['arrival']-time(),true)." hat recalled</span>";
            fleetstatus_recall($atter['fsid']);
          }
        }
      }
      foreach ($insert as $line) {
        atter_add(
          $line['gala'],
          $line['pos'],
          $line['tgala'],
          $line['tpos'],
          &$line['fleetnum'],
          $line['eta'],
          null,
          $line['nick']
        );
        $messages[] = "<span class=\"red\">Atter (".$line['gala'].":".$line['pos'].") ".$line['nick']." Flotte ".$line['fleetnum']." ETA ".$this->formattime($line['eta']*60,true)." hinzugefügt</span>";
      }
    }
  }
  
  function _match_deffer($tgala,$tpos,$line,$messages,$errors) {
    unset($defferadd_list);
      $user_defferlist = fleetstatus_get_bytarget($tgala,$tpos,2);
      if(preg_match_all("/(\d{1,4}):(\d{1,2})(.*?)<br>/ism",$line[10],$tak_list,PREG_SET_ORDER)) {
        if(count($tak_list)){
          $tak_timelist = parse_time($line[11]);
          #defferliste
          for($j=0;$j < count($tak_list);$j++){
            unset($deffer);
            $tak_eta = $tak_timelist[$j];
            if($tak_eta == "error") {
                $errors[] = "deffer (".$tak_list[$j][1].":".$tak_list[$j][2].") zeit nicht erkannt";
                continue;
            }
            #20 ticks im orbit
            if ($tak_eta == 0) {
              $tak_eta_low = -300;
              $tak_eta_high = 0;
            } else {
              #toleranz von einem tick
              $tak_eta_low = $tak_eta - 14;
              $tak_eta_high = $tak_eta + 14;
            }
            #passenden deffer finden
            for($k=0;$k < count($user_defferlist);$k++){
              $eta = floor(($user_defferlist[$k]['arrival']-time())/60);
            if( $user_defferlist[$k]['gala'] == $tak_list[$j][1] &&
                $user_defferlist[$k]['pos'] == $tak_list[$j][2] &&
                !($user_defferlist[$k]['checked'])  &&
                $eta > $tak_eta_low &&
                $eta < $tak_eta_high
            ) {
              #passenden deffer gefunden!
              $deffer = &$user_defferlist[$k];
              break;
            } 
            }
            if($deffer){
              $deffer['checked'] = true;
            } else {
              $gala = trim($tak_list[$j][1]);
              $pos = trim($tak_list[$j][2]);
              $nick = trim($tak_list[$j][3]);
              $adddeffer = array(
                  "gala" => $gala,"pos" => $pos,"nick" => $nick,"fleetnum" => 1,"eta" => $tak_eta
              );
                $messages[] = "<span class=\"green\">Deffer (".$gala.":".$pos.") ".$nick." Flotte 1 ETA ".$this->formattime($tak_eta*60,true)." hinzugefügt</span>";
              
              
              $defferadd_list[] = $adddeffer;
            } #else
          } #for
        } #if count(tak:list)
      } # if (pregmatchall)
  if(!$errors) {
      #deffer auf recall prüfen
      for($j=0;$j < count($user_defferlist);$j++){
        $deffer = &$user_defferlist[$j];
        if(!$deffer['checked']) {
          #nach zweiter flotte suchen die im selben tick fliegt aber nicht recallt hat
          $eta = floor(($deffer['arrival']-time())/60);
          $gala = $deffer['gala'];
          $pos = $deffer['pos'];
          $nick = $deffer['nick'];
          if ($eta > 0) {
            $eta_high = $eta + 14;
            $eta_low = $eta-14;
            if($eta_low < 0) {
              $eta_low = 1;
            }
          } else {
            $eta_low = 0;
            $eta_high = 0;
          }
          $found = false;
          for($k=0;$k < count($user_defferlist);$k++){
            $eta_search = floor(($user_defferlist[$k]['arrival']-time())/60);
            #im orbit
            if($eta_search < 0) {
              $eta_search = 0;
            }
              if( $user_defferlist[$k]['checked'] &&
                  $user_defferlist[$k]['gala'] == $gala &&
                  $user_defferlist[$k]['pos'] == $pos &&
                  $eta_search >= $eta_low &&
                  $eta_search <= $eta_high
              ) {
                $found = true;
                break;
              }
          }
          if(!$found){
            $messages[] = "<span class=\"blue\">Deffer (".$gala.":".$pos.") ".$nick." Flotte ".$deffer['fleetnum']." ETA ".$this->formattime(($deffer['arrival']-time()),true)." hat recalled</span>";
            fleetstatus_recall($deffer['fsid']);
          } else {
            #flotte gefunden die im selben tick fliegt und gematcht wurde
            #-> unklar welche von beiden recallt hat
            $messages[] = "<span class=\"red\"><b>(".$gala.":".$pos.") ".$nick." defft mit 2 Flotten im selben Tick und hat eine von beiden recallt, bitte überprüfen </b></span>";
          } #else
        } #if not deffer_checked
      } #for
      #deffer in die db eintragen
      for($j=0;$j < count($defferadd_list);$j++){
        $deffer = &$defferadd_list[$j];
        if(($check = fleetstatus_get_bykoords($deffer['gala'],$deffer['pos'],$deffer['fleetnum']))) {
          if($check['return_flight']){
            fleetstatus_delete($check['fsid']);
          } else {
            if($deffer['fleetnum'] == 1) $deffer['fleetnum'] = 2;
            else $deffer['fleetnum'] = 1;
          }
        }
        deffer_add(
            $deffer['gala'],
            $deffer['pos'],
            $tgala,
            $tpos,
            $deffer['fleetnum'],
            360,
            300,
            $deffer['eta'],
            $deffer['nick']
        );
      }
  }
  }

  function _is_matched_fleet($db_fleet,$parsed_fleet) {
    if( $db_fleet['return_flight'] != $parsed_fleet['return_flight'] ||
        $db_fleet['status'] != $parsed_fleet['status'] ||
        $db_fleet['gala'] != $parsed_fleet['gala'] ||
        $db_fleet['pos'] != $parsed_fleet['pos']
    ) {
      return false;
    }
    $eta_db = ($db_fleet['arrival']-time())*60;
    if(abs($eta_db - $parsed_fleet['eta']) > 14 && $eta_db > 0) return false;
    return true;
  }

  function _match_user(&$user,$line) {
    #attflotten matching
#    var_dump($line);
#    echo "<br><br>";
    $fleet_list = array();
    if(preg_match_all("'(?:(Rückflug)\s*?<br>|)[\s\(]*?(\d{1,4}):(\d{1,2})\s*?([^\s\)<]+).*?<br>'is",$line[4],$att_fleets,PREG_SET_ORDER)) {
      $timelist = parse_time($line[5]);
      for($i=0;$i < count($att_fleets);$i++) {
        $fleet = &$att_fleets[$i];
        if($fleet[1] == "Rückflug") {
          $fleet['return_flight'] = 1;
        } else {
          $fleet['return_flight'] = 0;
        }
        $fleet['status'] = 1;
        if($fleet['return_flight']) {
          $title = "<span class=\"blue\">Angriff Rückflug</span> von";
          $select = "Angriff Rückflug von";
        } else {
          $title = "<span class=\"red\">Angriff</span> auf";
          $select = "Angriff auf";
        }
        $eta = $timelist[$i]*60;
        $title .= " $fleet[4] ($fleet[2]:$fleet[3]) ETA ".$this->formattime($eta,true);
        $select .= " $fleet[4] ($fleet[2]:$fleet[3]) ETA ".$this->formattime($eta,true);
        $fleet_list[] = array("status" => $fleet['status'],"gala" => $fleet[2],"pos" => $fleet[3],"nick" => $fleet[4], "eta" => $eta,
        "return_flight" => $fleet['return_flight']);
        $titles[] = $title;
        $select_box[] = array("value" => $i+1, "title" => $select);
      }
    }
    if(preg_match_all("'(?:(Rückflug)\s*?<br>|)[\s\(]*?(\d{1,4}):(\d{1,2})\s*?([^\s\)<]+).*?<br>'is",$line[6],$att_fleets,PREG_SET_ORDER)) {
      $timelist = parse_time($line[7]);
      for($i=0;$i < count($att_fleets);$i++) {
        $fleet = &$att_fleets[$i];
        if($fleet[1] == "Rückflug") {
          $fleet['return_flight'] = 1;
        } else {
          $fleet['return_flight'] = 0;
        }
        $fleet['status'] = 2;
        if($fleet['return_flight']) {
          $title = "<span class=\"blue\">Verteidigung Rückflug</span> von";
          $select = "Verteidigung Rückflug von";
        } else {
          $title = "<span class=\"green\">Verteidigung</span> von";
          $select = "Verteidigung von";
        }
        $eta = $timelist[$i]*60;
        $title .= " $fleet[4] ($fleet[2]:$fleet[3]) ETA ".$this->formattime($eta,true);
        $select .= " $fleet[4] ($fleet[2]:$fleet[3]) ETA ".$this->formattime($eta,true);
        
        $fleet_list[] = array("status" => $fleet['status'],
          "gala" => $fleet[2],"pos" => $fleet[3],"nick" => $fleet[4],
          "eta" => $eta,"return_flight" => $fleet["return_flight"]);
        
        $titles[] = $title;
        $select_box[] = array("value" => $i+1, "title" => $select);
      }
    }
    $fleet = array();
    $userfleets = listFleetsByUser($user['uid']);
    if(count($fleet_list) == 0) {
      // beide flotten im orbit
      $do_update = array(1,1);
      $do_selectbox = array(0,0);
      $fleet = array();
    } elseif ( count($fleet_list) == 1 && $user['fleettype'] && $fleet_list[0]['status'] == 1 && $user['fleettype'] == 2) {
      // flotte 1 attet, flotte 2 im orbit
      // mit db checken
      if($this->_is_matched_fleet($userfleets[1],$fleet_list[0])) {
        // is in db, kein update
        $do_update = array(0,1);
      } else {
        // is nicht in db
        $do_update = array(1,1);
      }
      $do_selectbox = array(0,0);

      $fleet[0] = $fleet_list[0];

    } elseif ( count($fleet_list) == 1 && $user['fleettype'] && $fleet_list[0]['status'] == 2 && $user['fleettype'] == 1) {
      // flotte 1 defft, flotte 2 im orbit
      // mit db checken
      if($this->_is_matched_fleet($userfleets[1],$fleet_list[0])) {
        // is in db, kein update
        $do_update = array(0,1);
      } else {
        // is nicht in db
        $do_update = array(1,1);
      }
      $do_selectbox = array(0,0);
      $fleet[0] = $fleet_list[0];
      #echo "flotte 1 defft, flotte 2 im orbit<br>";
    } elseif( count($fleet_list) == 2 &&  $user['fleettype'] && $fleet_list[0]['status'] != $fleet_list[1]['status']) {
      // flotten beide unterwegs, status unterschiedlich
      if($user['fleettype'] == 2) {
        // flotte 2 defft, flotte 1 attet
        $fleet[0] = $fleet_list[0];
        $fleet[1] = $fleet_list[1];
      } else {
        // flotte 1 defft, flotte 2 attet
        $fleet[0] = $fleet_list[1];
        $fleet[1] = $fleet_list[0];
        $title_dummy = $titles[0];
        $titles[0] = $titles[1];
        $titles[1] = $title_dummy;
      }
      if($this->_is_matched_fleet($userfleets[1],$fleet_list[0])) {
        // is in db, kein update
        $do_update[0] = 0;
      } else {
        // is nicht in db
        $do_update[0] = 1;
      }
      if($this->_is_matched_fleet($userfleets[2],$fleet_list[1])) {
        // is in db, kein update
        $do_update[1] = 0;
      } else {
        // is nicht in db
        $do_update[1] = 1;
      }
    } else {
      // keine ahnung
      $do_update = array(1,1);
      $do_selectbox = array(1,1);
      $fleet = $fleet_list;
      if(!count($fleet[1])) {
        $fleet[] = array("status" => 0);
        $titles[] = "Im Orbit";
        $select_box[] = array("value" => 2, "title" => "Im Orbit");
      }
    }
    for($i=0;$i < 2; $i++) {
      $hidden_string[$i]['do_update'] = $do_update[$i];
      $hidden_string[$i]['do_selectbox'] = $do_selectbox[$i];
      $hidden_string[$i]['status'] = $fleet[$i]['status'];
      $hidden_string[$i]['gala'] = $fleet[$i]['gala'];
      $hidden_string[$i]['pos'] = $fleet[$i]['pos'];
      $hidden_string[$i]['eta'] = $fleet[$i]['eta'];
      $hidden_string[$i]['return_flight'] = $fleet[$i]['return_flight'];
#      var_dump($hidden_string[$i]);echo"<br>";
#      var_dump(join(",",$hidden_string[$i]));echo"<br>";
      $hidden_string[$i] = urlencode(join(",",$hidden_string[$i]));
      if(!$titles[$i]) $titles[$i] = "Im Orbit";
      $user['form_data'][] = array(
        "title" => $titles[$i],
        "fleet" => $i+1,
        "do_selectbox" => $do_selectbox[$i],
        "select_box" => $select_box
      );
    }
    $user['hidden_string'] = join("#",$hidden_string);
#    if($user['nick'] == "Schmog") {
#      var_dump($fleet_list);
#      echo "<br><br>";
#      echo "userfleettype: ".$user['fleettype']."<br>";
#      var_dump($fleet);
#      echo "<br><br>";
#      var_dump($titles);
#      echo "<br><br>";
#      exit();
#    }
  }

  function parser_takscreen() {

    function parse_time($timedata) {
      $timelist = array();
      #mode1
      preg_match_all("/(\d+?)\sMin/ism",$timedata,$tak_timelist,PREG_SET_ORDER);
      if (count($tak_timelist)){
        for($j=0;$j < count($tak_timelist);$j++){
          $timelist[] = $tak_timelist[$j][1];
        }
      } else {
        #mode3
        preg_match_all("/(\d{1,2}):(\d{1,2}):(\d{1,2})/ism",$timedata,$tak_timelist_mode3,PREG_SET_ORDER);
        if(count($tak_timelist_mode3)){
          for($j=0;$j < count($tak_timelist_mode3);$j++){
            $timelist[] = ($tak_timelist_mode3[$j][1])*60+$tak_timelist_mode3[$j][2];
          }
        } else {
          #mode4
          preg_match_all("/(\d{1,2}):(\d{1,2})/ism",$timedata,$tak_timelist_mode4,PREG_SET_ORDER);
          if(count($tak_timelist_mode4)){
            for($j=0;$j < count($tak_timelist_mode4);$j++){
              $timelist[] = ($tak_timelist_mode4[$j][1])*60+$tak_timelist_mode4[$j][2];
            }
          } else {
            #mode 5
            preg_match_all("/(\d{1,2})/ism",$timedata,$tak_timelist_mode5,PREG_SET_ORDER);
            if(count($tak_timelist_mode5)){
              for($j=0;$j < count($tak_timelist_mode5);$j++){
                $timelist[] = ($tak_timelist_mode5[$j][1])*15;
              }
            } else {
              $timelist[] = "error";
            }
          }
        }
      }
      return $timelist;
    }

    $form = new formContainer();

    $form->add(new formCheckBox("parse_user","User parsen","numeric",array(1),false));
    $form->add(new formCheckBox("parse_atter","Atter parsen","numeric",array(1),false));
    $form->add(new formCheckBox("parse_deffer","Deffer parsen","numeric",array(1),false));
    $form->add(new formInput("data","Taktikschirmdaten","string"));

    if ($_POST['userparse_step2']) {
      //parse userfleets, step 2
      $uids = split(",",urldecode($_POST['uids']));
      if($uids && is_array($uids) && count($uids)){
        $user = getUserbyID($uids[0]);
        $allygalas = getGalaListByAlly($user['aid'],true);
        $metagalas = getGalaList(true);
        foreach($uids as $uid) {
          $fleetdata = split("#",$_POST["user_".$uid]);
          $fleetdata[0] = split(",",urldecode($fleetdata[0]));
          $fleetdata[1] = split(",",urldecode($fleetdata[1]));
#          echo "fleetdata:<br>";
#          echo "<br>";
          for($i=0;$i < 2; $i++) {
#            echo"<br>";var_dump($fleetdata[$i]);echo"<br>";
            if($fleetdata[$i][0]) {
              // do_update
              if($fleetdata[$i][1]) {
                // do selectbox
                $id = $_POST["select_".$uid."_".($i+1)];
                if(!$id || !is_numeric($id)) continue;
                if($_POST["select_".$uid."_1"] == $_POST["select_".$uid."_2"]) continue;
                $id--;
              } else {
                // else
                $id = $i;
              }
              $status = $fleetdata[$id][2];
              $gala = $fleetdata[$id][3];
              $pos = $fleetdata[$id][4];
              $eta = $fleetdata[$id][5];
              $return_flight = $fleetdata[$id][6];
              if(!$return_flight) {
                if($status == 1) {
                  $ticks = 5;
                  $returntime = 450;
                } elseif ($status == 2) {
                  $ticks = 20;
                  if($gala == $user['gala']) {
                    $returntime = 270;
                  } elseif(in_array($gala,$allygalas)){
                    $returntime = 300;
                  } elseif(in_array($gala,$metagalas)){
                    $returntime = 330;
                  } else {
                    $returntime = 360;
                  }
                }
              }
#              echo "$uid: flotte ".($i+1).": do_update: ".$fleetdata[$i][0].", do_selectbox: ".$fleetdata[$i][1].", status: $status, gala: $gala, pos: $pos, eta: $eta, return_flight: $return_flight <br>";
              User_fleet_status_update(
                $uid,
                $id+1,
                $status,
                $return_flight,
                floor($eta/60),
                $gala,
                $pos,
                $ticks,
                $returntime
              );
            }
          }
        } // foreach
      } // if uids
      $this->_header("takscreen.php?action=takparser");
      exit();
    } elseif($_POST['takscreenparse_step1']) {
      $form->submit();
      if (!$form->hasErrors()) {
        #echo $data."<br><br>";
        $data = stripslashes($form->get("data"));
        if (preg_match(
            "/Sektor(.*?)<\/table>/ism",
            $data,$res)){
          $res[1] = preg_replace("/<a.*?\>(.*?)<\/a>/ism","\$1",$res[1]);
          $res[1] = preg_replace("/<nobr>(.*?)<\/nobr>/ism","\$1",$res[1]);
          $res[1] = preg_replace("/<span.*?\>(.*?)<\/span>/ism","\$1",$res[1]);
          $res[1] = preg_replace("/\*/ism","",$res[1]);
          $res[1] = html_entity_decode($res[1]);
          preg_match_all("/<tr[^>]*?class=\"r\"\>\s*?".
          "<td.*?\>.*?(\d{1,4}):(\d{1,2}).*?<\/td>\s*?".
          "<td.*?\>(.*?)<\/td>\s*?".
          "<td.*?\>(.*?)<\/td>\s*?".
          "<td.*?\>(.*?)<\/td>\s*?".
          "<td.*?\>(.*?)<\/td>\s*?".
          "<td.*?\>(.*?)<\/td>\s*?".
          "<td.*?\>(.*?)<\/td>\s*?".
          "<td.*?\>(.*?)<\/td>\s*?".
          "<td.*?\>(.*?)<\/td>\s*?".
          "<td.*?\>(.*?)<\/td>\s*?".
          "<\/tr>/ism",$res[1],$res,PREG_SET_ORDER);
          $galalist = getGalaList();
          $parse_messages = array();
          for($i=0;$i < count($res);$i++){
            #print_r($res[$i]);
            $line = &$res[$i];
            $gala = $line[1];
            $pos = $line[2];
            $nick = trim($line[3]);
            $user = getUserByPos($gala,$pos);
            if ($user){
              $parse_messages[] = "<b>User ".$user['nick']." (".$user['gala'].":".$user['pos'].") aktualisiert</b>";
              if(strtolower($user['nick']) != strtolower($nick)) {
                $parse_errors[] = "<span class=\"red\"><b>".$user['nick']." (".$user['gala'].":".$user['pos'].") hat falschen GN Nick oder ist ungültig</b></span>";
                  #echo "'".strtolower($nick)."' <> '".strtolower($user['nick'])."' <br>";
              }
              #atter matching
              if($form->get("parse_atter")) {
                $this->_match_atter($gala,$pos,$line,&$parse_messages,&$parse_errors);
              }

              #deffer
              if($form->get("parse_deffer")) {
                $this->_match_deffer($gala,$pos,$line,&$parse_messages,&$parse_errors);
              }
/*              #parse user
              if($form->get("parse_user")) {
                $this->_match_user(&$user,$line,&$fleet_select);
                $uids[] = $user['uid'];
                $userparse_list[] = $user;
              }*/
            } else {
              $parse_errors[] = "<b>User ".$nick." (".$gala.":".$pos.") existiert nicht in der Datenbank</b>";
            }
          }
          // result
          if($form->get("parse_user")) {
            $this->template->assign("uids",urlencode(join(",",$uids)));
            $this->template->assign("userparse",$form->get("parse_user"));
            $this->template->assign("userparse_list",$userparse_list);
          }
          if($parse_errors) {
              $this->template->assign("parse_errors",$parse_errors);
          } else {
              $this->template->assign("parse_messages",$parse_messages);
            }
          $form->registerVars(&$this->template);
          $this->show('takscreen_parser_takscreen_step2','Taktikschirm parsen');
          exit();
        } else {
          $parse_errors[] = "Taktikschirm nicht erkannt";
        }
      }
    } else {
      $parse_errors[] = "<b>ACHTUNG!</b> Unbedingt darauf achten dass euer Taktikschirm nicht älter als 15 min ist!";
      $form->select("parse_user",0);
      $form->select("parse_deffer",1);
      $form->select("parse_atter",1);
    }
    $this->template->assign("parse_errors",$parse_errors);
    $form->registerVars(&$this->template);
    $this->show('takscreen_parser_takscreen','Taktikschirm parsen');
  }
  #############################################################################
  function parser_fleet() {
    $form = new formContainer();
    $form->add(new formInput("data","Flottendaten","string"));
    if($_POST['send']) {
      $form->submit();
      if(!$form->hasErrors()) {
        $text = $form->get("data");
        $items = parse_taktikansicht($text);
        if($items)  {
          foreach($items as $item) {
            $nick = $item['nick'];
            $gala = $item['gala'];
            $pos = $item['pos'];
            $user = getUserByPos($gala,$pos);
            if(!$user) {$parse_errors[] = "($gala:$pos) $nick nicht im System";continue;}
            if(strtolower($user['nick']) != strtolower($nick)) $parse_errors[] = "($gala:$pos) $nick hat falschen GN-Nick oder ist ungültig";
            
            user_fleets_update($user['uid'],$item['fleets']);
            user_deff_update($user['uid'],$item['deff']);
            
            $parse_messages[] = "($gala:$pos) $nick erkannt";
          }
          $this->template->assign("parse_messages",$parse_messages);
          $this->template->assign("parse_errors",$parse_errors);
        } else {
          $form->setError("data");
          $form->addError("Es wurde nichts erkannt!");
        }
      }
    }
    $form->registerVars(&$this->template);
    $this->show('takscreen_parser_fleet','Flotten/Deff parsen');
  }  
  #############################################################################
  /**
    Blendet Flotten in der Incdetailansicht aus
  **/
  function incoming_details_hidefleets() {
    if(!($id = param_num("id")) || !($inc = getuserbyid($id))) $this->_header();
    $fleets = $_POST['hidefleets'];
    if(!$fleets || !is_array($fleets) || !count($fleets)) $this->_header("takscreen.php?action=details&id=$id&send");
    $fleets = preg_grep("'^\d+?$'is",$fleets);
    hide_fleets($fleets,$this->userdata);
    $this->_header("takscreen.php?action=details&id=$id&send");
  }
  #############################################################################
  function incoming_details_resetfilter() {
    $id = param_num("id");
    if(!($id = param_num("id")) || !($inc = getuserbyid($id))) $this->_header();
    $fids = array();
    $fids = getArrayFromList(fleetstatus_get_bytarget($inc['gala'],$inc['pos']),"fsid");
    if(count($fids)) reset_fleet_filter($this->userdata,$fids);
    $this->_header();
  }
  #############################################################################
  function parser_mili() {
    $step = param_num("step");
    $data = $_SESSION['steps'];
    if($data['miliparser']) {
      unset($data['miliparser']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['action'] ="";
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Miliscan laden";
      $this->forms['information']['message'] = "Miliscan wurde geladen und die Flotten aktualisiert";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Miliscan laden");
      exit();
    }
    if (!$step){
      # parsen
      if ($_POST['send']) {
        $data = $_POST['data'];
        if ($data) {
          $scans = parseScan($data);
          if($scans) {
            $miliids = array();
            foreach ($scans as $scan) {
              $sid = updateScan($scan);
              if($scan['type'] == "mili") {
                $miliids[] = $sid;
              }
            }
            if($miliids) {
              $_SESSION['parse_mili']['sids'] = $miliids;
              $this->_header("takscreen.php?action=miliparser&send&step=1");
            } else {
              $message = "<div class = \"tbl_red\">keine Miliscans erkannt</div>";
            }
          } else {
            $message = "<div class = \"tbl_red\">keine Scans erkannt</div>";
          }
        } else {
          $message = "<div class = \"tbl_red\">Feld leer</div>";
        }
      } else {
        $message = "Unterstützte Formate: <br><br>Galaxy Network Scan (IRC Copy)";
        $message .="<br><b>WurstScript Miliscan</b>";
      }
      $this->template->assign("message",$message);
      $this->show('takscreen_miliparser','Mili parsen');
    }elseif($step==1) {
      
      $miliids = $_SESSION['parse_mili']['sids'];
      if(!is_array($miliids) || !count($miliids)) $this->_header();
      // miliscan auswerten
      
      $mili = getScan(array("sid"=>reset($_SESSION['parse_mili']['sids'])));
      
      $fleets = &$mili['mili_fleets'];
      
      // mili formatieren
      for($i=0;$i < count($fleets);$i++){
        if ($fleets[$i]['status'] == 1) {
          $fleets[$i]['class'] = "red";
        } elseif($fleets[$i]['status'] == 2) {
          $fleets[$i]['class'] = "green";
        } elseif ($fleets[$i]['return_flight']) {
          $fleets[$i]['class'] = "blue";
        }
      }
      $form = new formContainer();
      
      $formitems = array();
      
      // darf kein intener user sein
      if(!($user = getUserByPos($mili['gala'],$mili['pos']))) {
        if(($status = fleetstatus_get_filter(array(
            "gala" => $mili['gala'],
            "pos" => $mili['pos'],
            "return_flight" => "0"
        )))) 
        {
          $list = array();
          $fleetstatus = array();
          $list[] = array("title" => "nicht zuweisen","value" => 0);
          foreach ($status as $key => $value) {
            $item = &$status[$key];
            if(($eta = $item['arrival'] - time()) < 0) {
              $eta = $eta + $item['orbittime']*60;
              $item['eta'] = "noch ".$this->formattime($eta,true)." im Orbit";
            } else {
              $item['eta'] = "ETA ".$this->formattime($eta,true);
            }
            if($item['status'] == 1) {
              $type = "Angriff";
            } else {
              $type = "Verteidigung";
            }
            #$item['title'] = $type." ".$item['tnick']." (".$item['tgala'].":".$item['tpos'].") ".$item['eta']." Flotte ".$item['fleetnum'];
            $list[] = array("value" => $item['fsid'],"title" => $type." ".$item['tnick']." (".$item['tgala'].":".$item['tpos'].") ".$item['eta']." Flotte ".$item['fleetnum']);
            $fleetstatus[$item['fsid']] = $item;
          }
          for ($i=1;$i < 3;$i++) {
            if($fleets[$i]['status'] == 1 || $fleets[$i]['status'] == 2) {
              //if($status) $formitems[$i]['select_items'] = $status;
              $form->add(new formSelectBox("fleet".$i,"Flotte $i","integer",$list,false));
            }
          }
        }
      } else {
        $form->setError("User ist Metamitglied, nicht erlaubt");
      }
      if($_POST['send']) {
        $form->submit();
        $fleet1 = $form->get("fleet1");
        $fleet2 = $form->get("fleet2");
        if($fleet1 && $fleet2 && $fleet1 == $fleet2) {
          $form->addError("beide Flotten wurden auf den selben Eintrag gesetzt");
          $form->setError(array("fleet1","fleet2"));
        }
        if(!$form->hasErrors()) {
          if($fleet1) {
            if($fleetstatus[$fleet1]['fleetnum'] != 1) {
              fleetstatus_change_fleetnum($fleet1,1);
              // muss neu geladen werden
              if($fleet2) $fleetstatus[$fleet2] = fleetstatus_get($fleet2);
            }
            fleetstatus_update_fleet($fleet1,$fleets[1],$mili['mili_svs'],$mili['mili_prec']);
          }
          if($fleet2) {
            if($fleetstatus[$fleet2]['fleetnum'] != 2) {
              fleetstatus_change_fleetnum($fleet2,2);
            }
            fleetstatus_update_fleet($fleet2,$fleets[2],$mili['mili_svs'],$mili['mili_prec']);
          }
          array_shift($_SESSION['parse_mili']['sids']);
          $this->_header("takscreen.php?action=miliparser&send&step=1");
        }
      }
      
      $this->template->assign("step",$step);
      $form->registerVars($this->template);
      #$this->template->assign("fleets",$fleets);
      $this->template->assign("mili",$mili);
      $this->show('takscreen_miliparser_step2','Mili parsen');
    }
  }
  #############################################################################
  function Incoming_setunsafe() {
    if(!($id = param_num("id")) || !($user = getUserByID($id)) || !($user['safe'])) $this->_header();
    addToLogFile("Ziel not safe: (".$user['gala'].":".$user['pos'].")","Incomings",$this->userdata['uid']);
    user_set_safe($id,0);
    $this->_header($this->backtracking->backlink());
  }
  #############################################################################
  function Incoming_setsafe() {
    if(!($id = param_num("id")) || !($user = getUserByID($id)) || ($user['safe'])) $this->_header();
    addToLogFile("Ziel safe: (".$user['gala'].":".$user['pos'].")","Incomings",$this->userdata['uid']);
    user_set_safe($id,1);
    $this->_header($this->backtracking->backlink());
  }
  #############################################################################
/*  function Deffer_recall() {
    $id = param_num("id");
    if(!$id)$this->_header($this->backtracking->backlink(),"fehlende id");
    $deffer = deffer_get($id);
    if(!$deffer)$this->_header($this->backtracking->backlink(),"deffer existiert nicht");
#    if ($deffer['gala'] != $this->userdata['gala'] && $deffer['ogala'] != $this->userdata['gala'])
#      $this->_header("takscreen.php","keine Berechtigung zum recalln");
    if ($_REQUEST['send']) {
      if ($_REQUEST['yes_x']) {
        deffer_recall($id);
        $inc = inc_get_data($deffer['incid']);
        if ($deffer['isextern']) {
          addToLogFile("externer Deffer (".$deffer['egala'].":".$deffer['epos'].") bei (".$inc['ogala'].":".$inc['opos'].") recallt","Incomings",$this->userdata['uid']);
        } else {
          addToLogFile("interner Deffer (".$deffer['gala'].":".$deffer['pos'].") bei (".$inc['ogala'].":".$inc['opos'].") recallt","Incomings",$this->userdata['uid']);
        }
      }
      $this->_header();
    } else {
      $this->forms['information']['url'] = "takscreen.php?id=$id";
      $this->forms['information']['action'] = "defferrecall";
      $this->forms['information']['title'] = "Deffer recalln";
      $this->forms['information']['message'] = "Deffer bei (".$deffer['ogala'].":".$deffer['opos'].") ".$deffer['onick']." recalln ?";
      $this->forms['information']['style'] = "red";
      $this->show('message_question', "Deffer recalln");
    }
  }
*/  #############################################################################
  function Atter_recallall() {
    if(!($id = param_num("id")) || !($user = getUserByID($id)) || !($atter = fleetstatus_get_bytarget($user['gala'],$user['pos']))) $this->_header();;
    if ($_REQUEST['send']) {
      if ($_REQUEST['yes_x']) {
        foreach ($atter as $data) {
          fleetstatus_recall($data['fsid']);
        }
        addToLogFile("alle Flotten bei (".$user['gala'].":".$user['pos'].") recallt","Incomings",$this->userdata['uid']);
      }
      $this->_header();
    } else {
      $this->forms['information']['url'] = "takscreen.php?id=$id";
      $this->forms['information']['action'] = "recallall";
      $this->forms['information']['title'] = "alle Atter recalln";
      $this->forms['information']['message'] = "alle Flotten auf (".$user['gala'].":".$user['pos'].") ".$user['nick']." recalln ?";
      $this->forms['information']['style'] = "red";
      $this->show('message_question', "alle Atter recalln");
    }
  }
  #############################################################################
  
  function Fleet_recall() {
    $id = param_num("id");
    if(!$id || !($info = fleetstatus_get($id))) $this->_header();
    
    if ($_REQUEST['send']) {
      if ($_REQUEST['yes_x']) {
        fleetstatus_recall($info['fsid']);
        #addToLogFile("Atter (".$atter['igala'].":".$atter['ipos'].") bei: (".$atter['ogala'].":".$atter['opos'].") recallt","Incomings",$this->userdata['uid']);
      }
      $this->_header();
    } else {
      $this->forms['information']['url'] = "takscreen.php?id=$id";
      $this->forms['information']['action'] = "recall";
      $this->forms['information']['title'] = "Flotte recalln";
      $this->forms['information']['message'] = " (".$info['gala'].":".$info['pos'].") ".$info['nick']." recalln ?";
      $this->forms['information']['style'] = "red";
      $this->show('message_question', "Flotte recalln");
    }
  }
  
  #############################################################################
  function Incoming_details() {
    if(!($id = param_num("id")) || !($inc = getuserbyid($id)))
      $this->_header();
    
    if(!($incfleets = inc_list_byuser($inc['gala'],$inc['pos']))) 
      $this->_header();
    
    $result = user_fleet_list_byuser($id);
    
    //atter-deffersummen
    $deffersum = array(); $attersum = array();
    
    $checkfleet = array("jaeger" => "Jäger","bomber"=>"Bomber",
    "fregatten" => "Fregatten","zerstoerer"=>"Zerstörer","kreuzer"=>"Kreuzer",
    "schlachter"=>"Schlachter","traeger"=>"Träger","kleptoren"=>"Kleptoren",
    "cancris"=>"Cancris");
    
    for($i=0;$i < count($result);$i++){
      if($result[$i]['return_flight']) {
        $result[$i]['name'] = "Rückflug";
        $result[$i]['class'] = "class=\"blue\"";
      } else {
        if ($result[$i]['status'] == 1) {
          $result[$i]['name'] = "Angriff";
          $result[$i]['class'] = "class=\"red\"";
        } elseif ($result[$i]['status'] == 2) {
          $result[$i]['name'] = "Verteidigung";
          $result[$i]['class'] = "class=\"green\"";
        } else {
          $result[$i]['name'] = "Im Orbit";
        }
      }
      if($result[$i]['tgala']) $result[$i]['name'] .= " (".$result[$i]['tgala'].":".$result[$i]['tpos'].")";
      if($result[$i]['status']) {
        if($result[$i]['arrival']) {
          $eta = $result[$i]['arrival'] - time();
          if($eta < 0) {
            $result[$i]['name'] .= " im Orbit: ".$this->formattime($result[$i]['orbittime']*60 + $eta,true);
          } else {
            $result[$i]['name'] .= " ETA ".$this->formattime($eta,true);
          }
        } else {
          $result[$i]['name'] .= "ETA n/a";
        }
      }
      // flotte im orbit
      if(!$result[$i]['status']) {
          foreach($checkfleet as $key => $val) {
              $deffersum[$key] += $result[$i][$key];
          }
      }
    }
    $this->template->assign("userfleet",$result);
    $fleetfilter = get_fleet_filter($this->userdata);
    
    // flottenfilter, eta formatierung
    $atter = 0; $deffer = 0;
    $atterlist = array(); $defferlist = array();
    
    // kann editieren
    if($inc['aid'] == $this->userdata['aid']) {
      $inc['canedit'] = 1;
    }
    
    $inc['cansetsafe'] = 1;
    
    foreach($incfleets as $fleet) {
        if($fleet['status'] == 1) $inc['atter']++;
        elseif($fleet['status'] == 2) $inc['deffer']++;
      if(count($fleetfilter)) {
        if($fleet['fsid'] && in_array($fleet['fsid'],$fleetfilter)) {
            if($fleet['status'] == 1) $inc['atter_filter'] = 1;
            elseif($fleet['status'] == 2) $inc['deffer_filter'] = 1;
            continue;
        }
      }
      if($fleet['arrival']) {
        $eta = $fleet['arrival']-time();
        if($eta < 0) {
          $fleet['eta'] = "im Orbit: ".$this->formattime($fleet['orbittime']*60 + $eta,true);
        } else {
          $fleet['eta'] = $this->formattime($eta);
          $fleet['title'] = $this->formattime($fleet['orbittime']*60,true)." im Orbit";
        }
      }
      $fleet['canrecall'] = 1;
      if($fleet['status'] == 2) {
        
        $deffer++;
        if(!$fleet['uid'] || $fleet['aid'] == $this->userdata['aid']) {
          $fleet['canedit'] = 1;
        }
        $defferlist[] = $fleet;
        foreach($checkfleet as $key => $val) {
          $deffersum[$key] += $fleet[$key];
        }
      } elseif($fleet['status'] == 1) {
        $atter++;
        $fleet['canedit'] = 1;
        $atterlist[] = $fleet;
        foreach($checkfleet as $key => $val) {
          $attersum[$key] += $fleet[$key];
        }
      }
    }
    if(!$inc['atter']) $this->_header();
    
    
    
    $inc['canupdateatter'] = 1;
    $this->template->assign("attersum",$attersum);
    $this->template->assign("deffersum",$deffersum);
    $this->template->assign("ircattdata",generate_irc_inc_summary($inc,$attersum,$deffersum));
    $this->template->assign("defferlist",$defferlist);
    if ($inc['fleetupdate']) {
      $inc['fleetupdate'] = formatdate_unix("d.m.Y",$inc['fleetupdate']).", ".date("H:i",$inc['fleetupdate'])." Uhr";
    } else {
      $inc['fleetupdate'] = "noch nie";
    }
    if ($inc['deffupdate']) {
      $inc['deffupdate'] = formatdate_unix("d.m.Y",$inc['deffupdate']).", ".date("H:i",$inc['deffupdate'])." Uhr";
    } else {
      $inc['deffupdate'] = "noch nie";
    }
    $inc['cansetsave'] = 1;
    $inc['canrecallall'] = 1;
    $inc['fleetfilter_set'] = 1;
    $this->template->assign("inc",$inc);
    $this->template->assign("atterlist",$atterlist);
    #$_SESSION['backlink'] = urlencode("takscreen.php?action=details&id=$id");
    $this->show('takscreen_inc_details', "Taktikschirm");
  }
  
  #############################################################################
  function Incoming_list() {
    $filter = &$_SESSION['incfilter'];
    if (!$filter){
      $filter['sort'] = "koords";
      $filter['order'] = "asc";
    }
    if ($_REQUEST['sort'] && $_REQUEST['order']) {
      $filter['sort'] = $_REQUEST['sort'];
      $filter['order'] = $_REQUEST['order'];
    }
    
    $filter['page'] = param_num("page",1);
    
    $allylist = getAllyList();
    
    $formlist = array();
    $formlist[] = array("title" => "Allianz", "value" => 0);
    foreach ($allylist as $ally) {
      $formlist[] = array("title" => "[".$ally['tag']."] ".$ally['name'],"value" => $ally['aid']);
    }
    
    $form = new formContainer();
    
    $form->add(new formSelectBox("ally","Allianz","numeric",$formlist,false));
    $form->add(new formCheckBox("safe","Safestatus","numeric",1,false));
    $form->add(new formCheckBox("undertime","unter der Deffzeit","numeric",1,false));
    if ($_POST['send']) {
      $form->submit(array("ally","safe","undertime"));
      $ally = $form->get("ally");
      $filter['undertime'] = $form->get("undertime");
      $filter['safe'] = $form->get("safe");
      
      if ($ally && !getAlly($ally)) {
        unset($ally);
      }
      if($ally) {
        $galalist = getGalaListByAlly($ally,true);
      } else {
        $galalist = getGalaList(true);
      }
      if($filter['ally'] != $ally) {
        $gala = 0;
      } else {
        $gala = $_POST['gala'];
      }
      $galaitems = array(array("title" => "Galaxie","value" => 0));
      $selectedgala = 0;
      foreach ($galalist as $item) {
        $galaitem = array("title" => $item, "value" => $item);
        if($item == $gala) {
          $galaitem['selected'] = "selected";
          $selectedgala = $gala;
        }
        $galaitems[] = $galaitem;
      }
      $filter['gala'] = $selectedgala;
      $filter['ally'] = $ally;
    } else {
      if($filter['ally']) {
        $form->select("ally",$filter['ally']);
        $galalist = getGalaListByAlly($filter['ally'],true);
      } else {
        $galalist = getGalaList(true);
      }
      if($filter['undertime']) $form->select("undertime",1);
      if($filter['safe']) $form->select("safe",1);
      
      $galaitems = array(array("title" => "Galaxie","value" => 0));
      $selectedgala = 0;
      foreach ($galalist as $item) {
        $galaitem = array("title" => $item, "value" => $item);
        if($filter['gala'] == $item) {
          $galaitem['selected'] = "selected";
          $selectedgala = $filter['gala'];
        }
        $galaitems[] = $galaitem;
      }
      $filter['gala'] = $selectedgala;
    }
    $this->template->assign("galalist",$galaitems);
    $sort[$filter['sort']][$filter['order']] = '_active';

    $list = inc_list($filter,&$pages,&$filter['page'],10,$this->userdata);
    
    for($i=0;$i < count($list);$i++){
      $canrecallall = 0;
      $_incs = inc_list_byuser($list[$i]['gala'],$list[$i]['pos']);
      
      foreach ($_incs as $inc) {
        if($inc['arrival']) {
            if ($inc['unixeta'] < 0) {
              $inc['orbit'] = true;
              $inc['orbittime'] = $this->formattime($inc['unixeta']+$inc['orbittime']*60,true);
              $inc['eta'] = 0;
            } else {
              $inc['eta'] = $this->formattime($inc['unixeta'],true);
              $inc['orbittime'] = $this->formattime($inc['orbittime']*60,true);
            }
        } else {
            $inc['eta'] = "n/a";
        }
        if(strlen($inc['nick']) > 20){
          $inc['nick'] = substr($nick['nick'],0,20)."..";
        }
        $inc['canrecall'] = 1;
        if($inc['status'] == 1) {
          // atter
          $list[$i]['atterlist'][] = $inc;
        } else {
          // deffer
          if(!$inc['uid'] || $inc['aid'] == $this->userdata['aid']) {
            $inc['canedit'] = 1;
          }
          $list[$i]['defferlist'][] = $inc;
        }
        $list[$i]['canrecallall'] = $canrecallall;
        $list[$i]['cansetsave'] = 1;
        $list[$i]['backlink'] = urlencode("takscreen.php#".$list[$i]['uid']);
      }
/*      $list[$i]['atterlist'] = atter_list($list[$i]['incid']);
      $list[$i]['defferlist'] = deffer_list($list[$i]['incid']);
      for($j=0;$j < count($list[$i]['atterlist']);$j++){
        $atter = &$list[$i]['atterlist'][$j];

        if ($atter['unixeta'] < 0) {
          $atter['orbit'] = $atter['unixeta']+75*60;
          if ($atter['orbit'] < 0) $atter['orbit'] = 0;
          $atter['orbit'] = $this->formattime($atter['orbit'],true);
          $atter['unixeta'] = 0;
        }
        $atter['eta'] = $this->formattime($atter['unixeta'],true);
        if(strlen($atter['inickname']) > 20){
          $atter['inickname'] = substr($atter['inickname'],0,20)."..";
        }
#        if ($atter['ogala'] == $this->userdata['gala']) {
          $canrecallall=1;
#        }
      }
      for($j=0;$j < count($list[$i]['defferlist']);$j++){
        $deffer = &$list[$i]['defferlist'][$j];
        if ($deffer['unixeta'] < 0) {
          $deffer['orbit'] = $deffer['unixeta']+$deffer['ticks']*15*60;
          if ($deffer['orbit'] < 0) $deffer['orbit'] = 0;
          $deffer['orbit'] = $this->formattime($deffer['orbit'],true);
          $deffer['unixeta'] = 0;
        }
        $deffer['eta'] = $this->formattime($deffer['unixeta'],true);
        if($deffer['isextern']) {
          $deffer['nick'] = $deffer['enickname'];
          $deffer['gala'] = $deffer['egala'];
          $deffer['pos'] = $deffer['epos'];
        }
        if($deffer['aid'] == $this->userdata['aid']) {
          $deffer['canupdatefleet'] = 1;
        }
        if(strlen($deffer['nick']) > 15){
          $deffer['nick'] = substr($deffer['nick'],0,15)."..";
        }

#        if ($deffer['uid'] == $this->userdata['uid'] ||
#        $deffer['ogala'] == $this->userdata['gala']) {
          $deffer['canrecall'] = 1;
#        }
      }
#      if ($list[$i]['gala'] == $this->userdata['gala']) {
#      }
      $list[$i]['backlink'] = urlencode("takscreen.php#".$list[$i]['incid']);
      */
    }
    
    $form->registerVars($this->template);
    $this->template->assign("pages",showPageBar($filter['page'],$pages,"takscreen.php?","page","menu"));
    $this->template->assign("allylist",$allylist);
    $this->template->assign("sort",$sort);
    $this->template->assign("list",$list);
    $this->show('takscreen_index', "Taktikschirm");
  }
  #############################################################################
  function Incoming_adddeffer() {
    $id = param_num("id");
    if ($id && !($inc = getUserByID($id))) $this->_header();
    
    if ($_SESSION['steps']['adddeffer']) {
      #save step
      unset($_SESSION['steps']['adddeffer']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['action'] ="";
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Deffer hinzufügen";
      $this->forms['information']['message'] = "Deffer eingetragen";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Deffer hinzufügen");
    }
    $form = new formContainer();
    $form->add(new formInput("tgala","Zielgala","numeric",true,4));
    $form->add(new formInput("tpos","Zielposition","numeric",true,2));
    $form->add(new formInput("gala","Gala vom Deffer","numeric",true,4));
    $form->add(new formInput("pos","Position vom Deffer","numeric",true,2));
    $list = array(
      array("title" => "Flotte 1","value" => 1),
      array("title" => "Flotte 2","value" => 2)
    );
    $form->add(new formSelectBox("fleet","Flotte","numeric",$list,false));
    $form->add(new formInput("time","Flugzeit","string",true,20,false,"'^(?:(\d+?):(\d+?)|(\d+?))$'is"));
    $list = array(
      array("title" => "extern (360)","value" => 360),
      array("title" => "Meta intern (330)","value" => 330),
      array("title" => "Allianz intern (300)","value" => 300),
      array("title" => "Galaxie intern (270)","value" => 270)
    );
    $form->add(new formSelectBox("defftype","Verteidigung","numeric",$list,false));
    $form->add(new formInput("orbit","Zeit im Orbit","string",true,20,false,"'^(?:(\d+):(\d+)|(\d+))$'is"));
    if ($_POST['send']) {
      $form->submit();
      $tgala = $form->get("tgala");
      $tpos = $form->get("tpos");
      $gala = $form->get("gala");
      $pos = $form->get("pos");
      
      #opfer existiert nicht
      if (!$form->hasErrors() && !($deffer = getUserByPos($tgala,$tpos))) {
        $form->addError("Member existiert nicht im System");
        $form->setError(array("tpos","tgala"));
      }
      
      #kann sich net selber deff0rn
      if (!$form->hasErrors() && $tgala == $gala && $tpos == $pos) {
        $form->addError("Man kann sich nicht selber deffen");
        $form->setError(array("tgala","tpos","gala","pos"));
      }
      
      #flotte schon eingetragen
      if (!$form->hasErrors() && $form->get("fleet") && ($data = fleetstatus_get_bykoords($gala,$pos,$fleet))) {
        $form->addError("Flotte ist bereits eingetragen");
        $form->setError(array("gala","pos","fleet"));
      }
      if(!$form->hasErrors() && ($check = fleetstatus_get_bykoords($gala,$pos)) && count($check) >= 2) {
        $form->addError("Es sind bereits 2 Einträge zu ($gala:$pos) vorhanden");
        $form->setError(array("gala","pos","fleet"));
      }
      if (!$form->hasErrors()) {
        $_SESSION['steps']['adddeffer'] = 1;
        $time = $form->getRegex("time");
        if(strlen($time[1])) {
          $eta = $time[1] * 60 + $time[2];
        } else $eta = $time[3];
        $time = $form->getRegex("orbit");
        if(strlen($time[1])) {
          $orbit = $time[1] * 60 + $time[2];
        } else $orbit = $time[3];
        if($eta == 0) {
          $orbit = gnticktime($orbit);
        }
        deffer_add(
          $gala,$pos,
          $tgala,$tpos,
          $form->get("fleet"),
          $form->get("defftype"),
          $orbit,
          $eta
        );
        $this->_header("takscreen.php?action=adddeffer&send");
      }
      $this->template->assign("errors",$errors);
      $this->template->assign("defftype".$items['defftype']['value'],"selected");
      $this->template->assign("tick".$ticks,"selected");
    } else {
      if ($inc) {
        $form->set("gala",$this->userdata['gala']);
        $form->set("pos",$this->userdata['pos']);
        $form->set("tgala",$inc['gala']);
        $form->set("tpos",$inc['pos']);
        if($inc['gala'] == $this->userdata['gala']) {
          $form->select("defftype",270);
          $form->set("time",$this->formattime(270*60));
        } elseif ($inc['aid'] == $this->userdata['aid'])
        {
          $form->select("defftype",300);
          $form->set("time",$this->formattime(300*60));
        } else {
          $form->select("defftype",330);
          $form->set("time",$this->formattime(330*60));
        }
        if($this->userdata['fleettype'] == 1 || $this->userdata['fleettype'] == 3 || $this->userdata['fleettype'] == 0) {
          $form->select("fleet",1);
        } else {
          $status = fleetstatus_get_byfleetnum($this->userdata['gala'],$this->userdata['pos']);
          if(!$status || count($status) == 2 || $status[0]['fleetnum'] == 1) {
            $form->select("fleet",2);
          } else {
            $form->select("fleet",1);
          }
        }
      } else {
          $form->select("defftype",330);
          $form->set("time",$this->formattime(330*60));
      }
      
      $form->set("orbit",$this->formattime(300*60));
    }
    $form->registerVars($this->template);
    $this->show('takscreen_deffer_add', "Deffer hinzufügen");
  }
  #############################################################################
/*  function Incoming_deffextern() {
    $checkfleet = array("jaeger" => "Jäger","bomber"=>"Bomber",
    "fregatten" => "Fregatten","zerstoerer"=>"Zerstörer","kreuzer"=>"Kreuzer",
    "schlachter"=>"Schlachter","traeger"=>"Träger","kleptoren"=>"Kleptoren",
    "cancris"=>"Cancris","prec"=>"Scangenauigkeit","svs"=>"Scanverstärker");
    $data = $_SESSION['steps'];
    if ($data['externdeffer']) {
      #save step
      unset($data['externdeffer']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['action'] ="";
      $this->forms['information']['title'] = "externen Deffer melden";
      $this->forms['information']['message'] = "Deffer eingetragen";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "externen Deffer melden");
    }
    if ($_POST['send']) {
      $this->template->assign("errors",$errors);
      $items['gala']['value'] = $_POST['gala'];
      $items['pos']['value'] = $_POST['pos'];
      #Opfer
      if (!$items['gala']['value'] || !is_numeric($items['gala']['value'])) {
        $errors[] = "Ungültige Opfergalaxie";
        $items['gala']['class'] = "_error";
      }
      if (!$items['pos']['value'] || !is_numeric($items['pos']['value'])) {
        $errors[] = "Ungültige Opferposition";
        $items['pos']['class'] = "_error";
      }
      #ticks
      $items['ticks']['value'] = param_num("ticks",20);

      #deffer
      #koords
      $items['agala']['value'] = $_POST['agala'];
      $items['apos']['value'] = $_POST['apos'];
      if (!$items['agala']['value'] || !is_numeric($items['agala']['value'])) {
        $errors[] = "Ungültige Deffergalaxie";
        $items['agala']['class'] = "_error";
      }
      if (!$items['apos']['value'] || !is_numeric($items['apos']['value'])) {
        $errors[] = "Ungültige Defferposition";
        $items['apos']['class'] = "_error";
      }
      #nickname
      $items['nickname']['value'] = $_POST['nickname'];
      if (!$items['nickname']['value']) {
        $errors[] = "Fehlender Nickname";
        $items['nickname']['class'] = "_error";
      }
      #eta
      $items['time']['value'] = $_POST['time'];
      if (!isset($items['time']['value'])) {
        $errors[] = "Fehlende Ankunftzeit";
        $items['time']['class'] = "_error";
      } else {
        if (preg_match("/^(\d+?):(\d+?)$/i",$items['time']['value'],$result)) {
          $eta = ($result[1] * 60) + $result[2];
        } elseif (is_numeric($items['time']['value'])) {
          $eta = $items['time']['value'];
        } else {
          $errors[] = "Ungültige Zeit";
          $items['time']['class'] = "_error";
        }
      }
      #flottendaten
      $fleet = array();
      foreach ($checkfleet as $key => $value) {
        $items[$key]['value'] = $_POST[$key];
        if ($items[$key]['value']) {
          if (!is_numeric($items[$key]['value'])) {
            $errors[] = "$value ungültig";
            $items[$key]['class'] = "_error";
          } else {
            $fleet[$key] = $items[$key]['value'];
          }
        } else {
          $items[$key]['class'] = "_optional";
        }
      }
      #flottenummer
      $items['fleet']['value'] = $_POST['fleet'];
      if ($items['fleet']['value'] != 1 && $items['fleet']['value'] != 2) {
        $errors[] = "Flotte ungültig";
      } else {
        $this->template->assign("fleet".$items['fleet']['value'],"selected");
      }
      #opfer existiert nicht
      if (!$errors && !($opfer = getUserByPos($items['gala']['value'],$items['pos']['value']))) {
        $items['gala']['class'] = "_error";
        $items['pos']['class'] = "_error";
        $errors[] = "Ziel existiert nicht";
      }
      #deffer ist benutzer des tps
      if (!$errors && ($deffer = getUserByPos($items['agala']['value'],$items['apos']['value']))) {
        $items['agala']['class'] = "_error";
        $items['apos']['class'] = "_error";
        $errors[] = "Koordinaten gehören zu einem Benutzer, muss als intern gemeldet werden !";
      }
      #inc gibts net
      if (!$errors && !($incdata = inc_get_data($opfer['uid']))) {
        $items['gala']['class'] = "_error";
        $items['pos']['class'] = "_error";
        $errors[] = "Ziel hat kein Incoming";
      }
      #kann sich net selber deff0rn
      if (!$errors && ($items['agala']['value'] == $items['gala']['value']
      && $items['apos']['value'] == $items['pos']['value'])) {
        $items['agala']['class'] = "_error";
        $items['apos']['class'] = "_error";
        $items['gala']['class'] = "_error";
        $items['pos']['class'] = "_error";
        $errors[] = "Man kann sich nicht selber deffen";
      }
      if (!$errors) {
        $data['externdeffer'] = 1;
        $_SESSION['steps'] = $data;
        deffer_extern_add(
          $opfer['uid'],
          $items['nickname']['value'],
          $items['agala']['value'],
          $items['apos']['value'],
          $eta,
          $items['fleet']['value'],
          $fleet,
          $items['ticks']['value']
        );
        $this->_header("takscreen.php?action=adddeffextern&send");
      }
      $this->template->assign("errors",$errors);
    } else {
      foreach ($checkfleet as $key => $value) {
        $items[$key]['class'] = "_optional";
      }
      $items['ticks']['value'] = 20;
    }
    $this->template->assign("ticklist",$this->getTicklist(20,$items['ticks']['value']));
    $this->template->assign("items",$items);
    $this->show('takscreen_deffer_extern', "externen Deffer melden");
  }
  
*/ /* #############################################################################
  function Deffer_update() {
    $id = param_num("id");
    if(!$id) $this->_header($this->backtracking->backlink(),"fehlende id");
    $deffer = deffer_get($id);
    if(!$deffer) $this->_header($this->backtracking->backlink(),"deffer existiert nicht");
    if(!$deffer['isextern']) $this->_header($this->backtracking->backlink(),"deffer ist kein externer deffer");
    $checkfleet = array("jaeger" => "Jäger","bomber"=>"Bomber",
    "fregatten" => "Fregatten","zerstoerer"=>"Zerstörer","kreuzer"=>"Kreuzer",
    "schlachter"=>"Schlachter","traeger"=>"Träger","kleptoren"=>"Kleptoren",
    "cancris"=>"Cancris");
    $data = $_SESSION['steps'];
    if ($data['updatedeffer']) {
      #save step
      unset($data['updatedeffer']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "externen Deffer updaten";
      $this->forms['information']['message'] = "Deffer erfolgreich upgedated";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Deffer updaten");
    }
    if ($_POST['send']) {
      #eta
      $items['eta']['value'] = $_POST['eta'];
      if (!isset($items['eta']['value'])) {
        $errors[] = "ETA leer";
        $items['eta']['class'] = "_error";
      } else {
        if (preg_match("/^(\d+?):(\d+?)$/i",$items['eta']['value'],$result)) {
          $etatime = ($result[1] * 60) + $result[2];
        } elseif (is_numeric($items['eta']['value'])) {
          $etatime = $items['eta']['value'];
        } else {
          $errors[] = "Ungültige Zeit";
          $items['eta']['class'] = "_error";
        }
      }
      #flottendaten
      foreach ($checkfleet as $key => $value) {
        $items[$key]['value'] = $_POST[$key];
        if($items[$key]['value']) {
          if (!is_numeric($items[$key]['value'])) {
            $errors[] = "$value ungültig";
            $items[$key]['class'] = "_error";
          } else {
            $fleet[$key] = $items[$key]['value'];
          }
        } else {
          $fleet[$key] = 0;
          $items[$key]['value'] = 0;
        }
      }
      #flottenummer
      $items['fleet']['value'] = $_POST['fleet'];
      if ($items['fleet']['value'] != 1 && $items['fleet']['value'] != 2) {
        $errors[] = "Flotte ungültig";
      } else {
        $this->template->assign("fleet".$items['fleet']['value'],"selected");
      }
      #ticks
      $items['ticks']['value'] = param_num("ticks",20);
      if (!$errors) {
        deffer_extern_update(
          $deffer['incid'],
          $deffer['defferid'],
          $items['fleet']['value'],
          $etatime,
          $fleet,
          $items['ticks']['value']
        );
        $data['updatedeffer'] = 1;
        $_SESSION['steps'] = $data;
        $this->_header("takscreen.php?action=updatedeffer&id=$id&send");
      }
    } else {
      #flottendaten
      foreach ($checkfleet as $key => $value) {
        $items[$key]['value'] = $deffer[$key];
      }
      $items['ticks']['value'] = $deffer['ticks'];
      $items['eta']['value'] = $this->formattime($deffer['unixeta']);
      $this->template->assign("fleet".$deffer['fleetnum'],"selected");
    }
    $this->template->assign("ticklist",$this->getTicklist(20,$items['ticks']['value']));
    $this->template->assign("id",$id);
    $this->template->assign("errors",$errors);
    $this->template->assign("deffer",$deffer);
    $this->template->assign("items",$items);
    $this->show('takscreen_deffer_update', "Deffer updaten");
  }*/
  
  function incoming_fleetstatus() {
    if(!($fsid = param_num("id")) || !($status = fleetstatus_get($fsid))) $this->_header();
    if($status['uid']) $this->_header("user.php?action=fleet&id=".$status['uid']."&send");
    $page = param_num("page",1);
    $form = new formContainer();
    $form_params = array();
    $form_params[] = array("value" => $this->session['id'],"name" => $this->session['name']);
    $form_params[] = array("value" => "fleetstatus","name" => "action");
    $form_params[] = array("value" => $page,"name" => "page");
    $form_params[] = array("value" => 1,"name" => "send");
    $form_params[] = array("value" => $fsid,"name" => "id");
    if($_SESSION['steps']['fleetstatus']) {
      $this->template->assign("message",$_SESSION['steps']['fleetstatus']);
      unset($_SESSION['steps']['fleetstatus']);
    }
    
    switch ($page) {
      // daten ändern
      case 1: {
        $form->add(new formInput("eta","Verbleibene Flugzeit (ETA)","string",true,255,true,"'^(?:(\d+?):(\d+?)|(\d+?))$'is"));
        $list = array(
          array("title" => "Flotte 1","value" => 1),
          array("title" => "Flotte 2","value" => 2)
        );
        $form->add(new formSelectBox("fleetnum","Flotte","numeric",$list,false));
        $form->add(new formInput("orbit","Zeit im Orbit","string",true,255,true,"'^(?:(\d+?):(\d+?)|(\d+?))$'is"));
        if($_POST['send']) {
          $form->submit();
          $eta = $form->getRegex("eta");
          if(strlen($eta[1])) {
            $eta = $eta[1] * 60 + $eta[2];
          } else {
            $eta = $eta[3];
          }
          if($eta < 0) $eta = 0;
          $time = $form->getRegex("orbit");
          if(strlen($time[1])) {
            $orbittime = $time[1] * 60 + $time[2];
          } else {
            $orbittime = $time[3];
          }
          if($orbittime < 0) $orbittime = 0;
          if($eta == 0) $orbittime = gnticktime($orbittime);
          if(!$form->hasErrors()) {
            if($form->get("fleetnum") != $status['fleetnum']) {
              fleetstatus_change_fleetnum($status['fsid'],$form->get("fleetnum"));
            }
            fleetstatus_update($fsid,array("arrival" => gnarrival($eta),"orbittime" => $orbittime));
            $_SESSION['steps']['fleetstatus'] = "Taktikdaten geändert";
            $this->_header("takscreen.php?action=fleetstatus&id=$fsid&page=$page&send");
          }
        } else {
          if($status['arrival']) {
            $eta = $status['arrival'] - time();
            if($eta <= 0) {
              if(isset($status['orbittime'])) $orbittime = $status['orbittime']*60+$eta;
              $eta = 0;
            } else {
              $orbittime = $status['orbittime']*60;
            }
            $form->set("eta",$this->formattime($eta));
            $form->set("orbit",$this->formattime($orbittime));
          }
          if($status['fleetnum']) $form->select("fleetnum",$status['fleetnum']);
        }
        break;
      }
      // scan laden
      case 2: {
        $scan = getScan(array("gala" => $status['gala'],"pos" => $status['pos']));
        if($scan['hasunit'] || $scan['hasmili']) {
          $scantypes = array();
          if($scan['hasmili']) {
            $scantypes[] = "mili";
            $list = array(
              array("title" => "Flotte 1","value" => 1),
              array("title" => "Flotte 2","value" => 2)
            );
            $form->add(new formSelectBox("fleet","Flotte","numeric",$list,false));
          }
          if($scan['hasunit']) $scantypes[] = "unit";
          $form->add(new formRadio("scan","Scan laden","string",$scantypes));
          if($_POST['send']) {
            $form->submit();
            if(!$form->hasErrors()) {
              if($form->get("scan") == "unit") {
                // unit laden
                $updatefleetdata = fleet_get($scan['unit_fid']);
                $updatefleetdata['svs'] = $scan['unit_svs'];
                $updatefleetdata['prec'] = $scan['unit_prec'];
                $updatefleetdata['fleetnum'] = $status['fleetnum'];
                $message = "Einheitenscan";
              } else {
                // mili laden
                $updatefleetdata = miliscan_fleet_get_bykoords($scan['gala'],$scan['pos'],$form->get("fleet"));
                $updatefleetdata['svs'] = $scan['mili_svs'];
                $updatefleetdata['prec'] = $scan['mili_prec'];
                $updatefleetdata['fleetnum'] = $form->get("fleet");
                $message = "Militärscan Flotte ".$form->get("fleet");
              }
              if($form->get("fleetnum") != $updatefleetdata['fleetnum']) {
                fleetstatus_change_fleetnum($status['fsid'],$updatefleetdata["fleetnum"]);
              }
              fleetstatus_update_fleet($fsid,$updatefleetdata,$updatefleetdata['svs'],$updatefleetdata['prec']);
              $_SESSION['steps']['fleetstatus'] = $message." wurde geladen";
              $this->_header("takscreen.php?action=fleetstatus&id=$fsid&page=2&send");
            }
          } else {
            $form->select("scan",$scantypes[0]);
            if($scan['hasmili']) $form->select("fleet",$status['fleetnum']);
          }
          $this->template->assign("scan",scan_format($scan));
        }
        break;
      }
      // manuelle flotteneingabe
      case 3: {
        $checkfleet = array(
          "jaeger" => "Jäger",
          "bomber"=>"Bomber",
          "fregatten" => "Fregatten",
          "zerstoerer"=>"Zerstörer",
          "kreuzer"=>"Kreuzer",
          "schlachter"=>"Schlachter",
          "traeger"=>"Träger",
          "kleptoren"=>"Kleptoren",
          "cancris"=>"Cancris",
          "prec"=>"Scangenauigkeit",
          "svs"=>"Scanverstärker"
        );
        foreach ($checkfleet as $key => $value) {
          $form->add(new formInput($key,$value,"numeric"));
        }
        if($_POST['send']) {
            $form->submit();
            if(!$form->hasErrors()) {
                $updatefleetdata = array();
                foreach ($checkfleet as $key => $value) {
                  $updatefleetdata[$key] = $form->get($key);
                }
                fleetstatus_update_fleet($fsid,$updatefleetdata,$updatefleetdata['svs'],$updatefleetdata['prec']);
                $_SESSION['steps']['fleetstatus'] = "Flottendaten gespeichert";
                $this->_header("takscreen.php?action=fleetstatus&id=$fsid&page=3&send");
            }
        } else {
            foreach ($checkfleet as $key => $value) {
              $form->set($key,$status[$key]);
            }
        }
        break;
      }
      case 4: {
        $form->add(new formInput("code","Text","string"));
        if($_POST['send']) {
          $form->submit();
          if(!$form->hasErrors()) {
            $result = parseWurstFleet($form->get("code"));
            if($result !== false) {
              fleetstatus_update_fleet($fsid,$result,"0","100");
              $_SESSION['steps']['fleetstatus'] = "Flottendaten gespeichert";
              $this->_header("takscreen.php?action=fleetstatus&id=$fsid&page=4&send");
            } else {
              $form->addError("Fehler beim Parsen");
              $form->setError("code");
            }
          }
        }
      }
    }
    $form->registerVars($this->template);
    // baue manuelles flottenupdate formular
    if($page == 3) {
      $formitems = array();
      foreach($checkfleet as $key => $value) {
        $formitems[] = array(
            "value" => $form->smartyitems[$key]['value'],
            "title" => $form->smartyitems[$key]['title'],
            "name" => $key,
            "class" => $form->smartyitems[$key]['class']
        );
      }
      $this->template->assign("fleetupdateform",$formitems);
    }
    $this->template->assign(array("status" => $status,"page" => $page,"form_params" => $form_params));
    $this->show('takscreen_fleetstatus',"Flottendaten");
  }
  
  ############################################################
  function Incoming_addatter () {
    $checkfleet = array(
      "jaeger" => "Jäger",
      "bomber"=>"Bomber",
      "fregatten" => "Fregatten",
      "zerstoerer"=>"Zerstörer",
      "kreuzer"=>"Kreuzer",
      "schlachter"=>"Schlachter",
      "traeger"=>"Träger",
      "kleptoren"=>"Kleptoren",
      "cancris"=>"Cancris",
      "prec"=>"Scangenauigkeit",
      "svs"=>"Scanverstärker"
    );
    if ($_SESSION['steps']['addatter']) {
      unset($_SESSION['steps']['addatter']);
      
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['action'] ="";
      $this->forms['information']['title'] = "Incoming melden";
      $this->forms['information']['message'] = "Inc eingetragen";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Incoming melden");
    }
    
    $form = new formContainer();
    
    foreach ($checkfleet as $key => $value) {
      $form->add(new formInput($key,$value,"numeric",true,20,true));
    }
    
    $form->add(new formInput("gala","Zielgalaxie","numeric",true,20));
    $form->add(new formInput("pos","Zielposition","numeric",true,20));
    $form->add(new formInput("agala","Angreifergalaxie","numeric",true,20));
    $form->add(new formInput("apos","Angreiferposition","numeric",true,20));
    $form->add(new formInput("time","ETA bis zur Ankunft","string",true,255));
    
    $form->add(new formSelectBox("fleet","Flottennummer","numeric",array(array("title" => "Flotte 1","value" => "1"),array("title" => "Flotte 2","value" => "2")),false));
    
    if ($_POST['send']) {
      $form->submit();
      if(!$form->hasErrors() && strlen($form->get("time"))) {
        $time = $form->get("time");
        if (preg_match("/^(\d+?):(\d+?)$/i",$time,$result)) {
          $eta = ($result[1] * 60) + $result[2];
        } elseif (is_numeric($time)) {
          $eta = $time;
        } else {
          $form->setError("time");
          $form->addError("ETA: Ungültige Zeit");
        }
      }
      $tgala = $form->get("gala");
      $tpos = $form->get("pos");
      $gala = $form->get("agala");
      $pos = $form->get("apos");
      $fleetnum = $form->get("fleet");
      #user existiert nicht
      if (!$form->hasErrors() && !($user = user_get_bypos($tgala,$tpos))) {
        $form->setError(array("gala","pos"));
        $form->adderror("User existiert nicht");
      }
      if (!$form->hasErrors() && ($user = user_get_bypos($gala,$pos))) {
        $form->setError(array("agala","apos"));
        $form->adderror("Angreifer ist Metamitglied!");
      }
      foreach ($checkfleet as $key => $item) {
        if(strlen($form->get($key))>0) {
          if(isset($fleetdata)) $fleetdata = array();
          $fleetdata[$key] = $form->get($key);
        }
      }
      if (!$form->hasErrors() && (!$form->get("fleet") && $fleetdata)) {
        $form->adderror("Flottendaten ohne Flottennummer ist nicht zulässig!");
        $form->setError("fleet");
      }
      
      if (!$form->hasErrors() && (count(fleetstatus_get_bykoords($gala,$pos)) == 2)) {
        $form->adderror("Es sind bereits 2 Flotten von $gala:$pos eingetragen");
        $form->setError("fleet");
      }
      if (!$form->hasErrors() && $fleetnum && ($info = fleetstatus_get_bykoords($gala,$pos,$fleetnum))) {
        $form->adderror("Angreifer bereits eingetragen: attet (".$info['tgala'].":".$info['tpos'].")" );
        $form->setError("fleet");
        $form->setError("agala");
        $form->setError("apos");
      }
      if (!$form->hasErrors()) {
        atter_add(
          $form->get("agala"),
          $form->get("apos"),
          $form->get("gala"),
          $form->get("pos"),
          $form->get("fleet"),
          $eta,
          $fleetdata
        );
                
        $_SESSION['steps']['addatter'] = 1;
        $this->_header("takscreen.php?action=addatter&send");
      }
    }
    $form->registerVars($this->template);
    $this->show('takscreen_atter_add', "Incoming melden");
  }
  ############################################################
 /* function Atter_update () {
    $id = param_num("id");
    if(!$id) $this->_header($this->backtracking->backlink(),"fehlende id");
    $atter = atter_get($id);
    if(!$atter) $this->_header($this->backtracking->backlink(),"atter existiert nicht");
    $checkfleet = array("jaeger" => "Jäger","bomber"=>"Bomber",
    "fregatten" => "Fregatten","zerstoerer"=>"Zerstörer","kreuzer"=>"Kreuzer",
    "schlachter"=>"Schlachter","traeger"=>"Träger","kleptoren"=>"Kleptoren",
    "cancris"=>"Cancris","prec"=>"Scangenauigkeit","svs"=>"Scanverstärker");
    $data = $_SESSION['steps'];
    if ($data['updateatter']) {
      #save step
      unset($data['updateatter']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['action'] ="";
      $this->forms['information']['title'] = "Atter updaten";
      $this->forms['information']['message'] = "Atter erfolgreich upgedated";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Atter updaten");
    }
    if ($_POST['send']) {
      #eta
      $items['eta']['value'] = $_POST['eta'];
      if (!isset($items['eta']['value'])) {
        $errors[] = "ETA leer";
        $items['eta']['class'] = "_error";
      } else {
        if (preg_match("/^(\d+?):(\d+?)$/i",$items['eta']['value'],$result)) {
          $etatime = ($result[1] * 60) + $result[2];
        } elseif (is_numeric($items['eta']['value'])) {
          $etatime = $items['eta']['value'];
        } else {
          $errors[] = "Ungültige Zeit";
          $items['eta']['class'] = "_error";
        }
        if (!$etatime) $etatime = 0;
      }
      #flottendaten
      foreach ($checkfleet as $key => $value) {
        $items[$key]['value'] = $_POST[$key];
        if($items[$key]['value']) {
          if (!is_numeric($items[$key]['value'])) {
            $errors[] = "$value ungültig";
            $items[$key]['class'] = "_error";
          } else {
            $fleet[$key] = $items[$key]['value'];
          }
        }
      }
      if (!$items['prec']['value']) $items['prec']['value'] = 0;
      if (!$items['svs']['value']) $items['svs']['value'] = 0;
      if($items['prec']['value'] > 100) {
        $items['prec']['value'] = 100;
      }
      #flottenummer
      $items['fleet']['value'] = $_POST['fleet'];
      if ($items['fleet']['value'] != 1 && $items['fleet']['value'] != 2) {
        $errors[] = "Flotte ungültig";
      } else {
        $this->template->assign("fleet".$items['fleet']['value'],"selected");
      }
      if (!$errors) {
        atter_update($id,
          $items['prec']['value'],
          $items['svs']['value'],
          $items['fleet']['value'],
          $etatime,
          $fleet);
        $data['updateatter'] = 1;
        $_SESSION['steps'] = $data;
        $this->_header("takscreen.php?action=updateatter&id=$id&send");
      }
      $this->template->assign("errors",$errors);
    } else {
      #eta
      $items['eta']['value'] = $this->formattime($atter['unixeta']);
      #flottendaten
      foreach ($checkfleet as $key => $value) {
        $items[$key]['value'] = $atter[$key];
      }
      $this->template->assign("fleet".$atter['fleetnum'],"selected");
      $items['prec']['value'] = $atter['prec'];
      $items['svs']['value'] = $atter['svs'];
    }
    $this->template->assign("id",$id);
    $this->template->assign("atter",$atter);
    $this->template->assign("items",$items);
    $this->show('takscreen_inc_update', "Atter updaten");
  }*/
}
?>
