<?php

require_once("classes/page.class.php");
require_once("classes/kibo.backtracking.php");

require_once("database/db.user.php");
require_once('database/db.logfile.php');
require_once('database/db.scans.php');
require_once('database/db.takscreen.php');
require_once('database/db.bot.php');
  
/**
* class kibopage
* 
* class kibopage detailed description
* 
* @author Stefan Dieringer
*/
class kibopage extends Page {

  /**
    \brief Userdaten des eingeloggten Users

    Daten des eingeloggten Users.
  */
  var $userdata;
  
  var $backtracking;
  
  #template values
  var $forms;
  var $box;
  var $version;
  
  function kibopage () {
    
    parent::Page();
    
    $this->forms = array();
    $this->box = array();
    $this->userdata = array();
    
    $this->backtracking = new kibo_backtracking($this->session);
    
    $this->version = TIC_VERSION;
    $this->template->assign('version',TIC_VERSION." ".TIC_COPYRIGHT);
    
  }  
  
  function run() {
    
    parent::run(1);
    
    //loads Userdata from session
    $this->_loadUserData();
    
    //check loginstatus
    if (!$this->userdata) {
      //login
      $this->_loginUser();
    } else {
      //logout
      if ($this->action == "logout")
        {
          $this->_logoutUser();
        }
      $this->backtracking->run($this->userdata['uid']);
      $this->template->assign("backlink",$this->backtracking->backlink());
    }

    //botrefresh
    bot_refresh();
    //flotten refresh
    fleetstatus_refresh();
    //user has to change pw
    if ($this->userdata['changepw'] && $this->action != "changepwd")  $this->_header("user.php?action=changepwd");

    //checkt die incs auf undertime
//    inc_check_undertime();
    //läd die Takscreenbox
    $this->_loadTakscreenBox();
    //läd die Aktivitätsbox
    $this->_loadActivityBox();
    //lädt die Flottenbox
    $this->_loadFleetBox();
    //creates mainmenu
    $this->_createMenu();
  }
  
  function _header($link="",$msg="") {
    if(!$link) $link = $this->backtracking->backlink();
    parent::_header($link,$msg);
  }
  
  #
  # Zeigt die Seite an, lädt vorher die Content Boxen
  #
  function show($content,$title="") {
    $this->box['mainbox']['template'] = $content.".html";
    $this->box['mainbox']['title'] = $title;
    $this->template->assign('htmlpagetitle',TIC_VERSION." -- ".$title);
    $this->template->assign('forms',$this->forms);
    $this->template->assign('box',$this->box);
    #show page
    $this->template->display('index.html');
    exit();
  }

  #
  # Zeigt eine Fehlerseite an
  #
  function show_error($content="Fehler",$title="Fehler") {
    $this->template->assign('message',$content);
    $this->show('error',$title);
  }
  
  #
  # lädt die Userdaten aus der Session
  #
  function _loadUserData () {
    $data = $_SESSION['sessionuserdata'];
    #check session UserData
    if ($data) {
      $return = getUserByID($data['id'],$data['password']);
      if ($return) {
        $this->userdata = $return;
        updateUserOnline($this->userdata['uid']);
        #load login/logout box, useronlinebox
        refreshUserOnline();

        $this->box['userbox']['title'] = 'Benutzerinfo';
        $this->box['userbox']['username'] = $this->userdata['nick'];
        $this->box['userbox']['gala'] = $this->userdata['gala'];
        $this->box['userbox']['pos'] = $this->userdata['pos'];
        $this->box['userbox']['userid'] = $this->userdata['uid'];
        $this->box['userbox']['template'] = 'box_userdata_content.html';

        $this->box['useronline']['title'] = 'User Online';
        $list = listUserOnline();
        for($i=0;$i < count($list);$i++){
          $len = 11-strlen($list[$i]['gala'])-strlen($list[$i]['pos']);
          if(strlen($list[$i]['nick'])>$len) {
            $list[$i]['name'] = substr($list[$i]['nick'],0,$len)."..";
          } else {
            $list[$i]['name'] = $list[$i]['nick'];
          }
        }
        $this->box['useronline']['list'] = $list;
        $this->box['useronline']['template'] = 'box_useronline_content.html';

      } else {
        deleteUserOnline($this->userdata['uid']);
        unset($_SESSION['sessionuserdata']);
        unset($data);
      }
    }
  }

  #
  # lädt die Flottenbox
  #
  function _loadFleetBox() {
    $list = user_fleet_list_byuser($this->userdata['uid']);
    for($i=1;$i < 3;$i++){
      $item = $list[$i];
      if ($item['arrival']) {
        $item['eta'] = $item['arrival']-time();
        $item['orbit'] = $item['ticks']*15*60;
        if ($item['eta'] < 0) {
          $item['orbit'] = $item['orbit'] + $item['eta'];
          $item['eta'] = 0;
          if ($item['orbit']< 0) $item['orbit'] = 0;
        }
        $item['eta'] = $this->formattime($item['eta'],true);
        $item['orbit'] = $this->formattime($item['orbit'],true);
      }
      $item['num'] = $i;
      $fleets[] = $item;
    }
    $this->box['fleets']['title'] = 'Flottenstatus';
    $this->box['fleets']['list'] = $fleets;
    $this->box['fleets']['template'] = 'box_fleets_content.html';
  }

  #
  # lädt die Takscreenbox
  #
  function _loadTakscreenBox() {
    $this->box['takscreen']['title'] = 'Taktikschirm';
    $this->box['takscreen']['info'] = takscreen_info();
    $this->box['takscreen']['template'] = 'box_takscreen_content.html';
  }

  #
  # lädt die Aktivitätsbox
  #
  function _loadActivityBox() {
    $this->box['activity']['title'] = 'Aktivitätscheck';
    if(!($this->box['activity']['docheck'] = do_check_activity($this->userdata))) {
        $this->box['activity']['nextcheck'] = date("H:i",$this->userdata['activity_check']+get_activity_timeout())." Uhr";
    }
    if($this->userdata['activity_check']) {
      $this->box['activity']['lastcheck'] = date("H:i d.m.Y",$this->userdata['activity_check']);
    } else {
      $this->box['activity']['lastcheck'] = "noch nie!";
    }
    $this->box['activity']['points'] = $this->userdata['activity_points'];
    $this->box['activity']['template'] = 'box_activity_content.html';
  }

  #
  # Erstellt das Hauptmenu
  #
  function _createMenu() {
    global $debug; 
    #selected menu
    if ($_REQUEST['mod']) {
      $_SESSION['mod'] = $_REQUEST['mod'];
    }

    #create globalmenu
    $entry = 0;
    $menu[$entry] = array("caption" => "Startseite", "link" => "index.php?mod=index", "module" => "index","id" => $entry);
      $menu[$entry]['items'] = array();
      $menu[$entry]['items'][] = array("caption" => "<div title=\"Memberliste\">Member</div>", "link" => "index.php?action=members");
      $menu[$entry]['items'][] = array("caption" => "<div title=\"Top Meta Scnanner\">Scanner</div>", "link" => "index.php?action=scanner");
      $menu[$entry]['items'][] = array("caption" => "<div title=\"Highscore\">Highscore</div>", "link" => "index.php?action=highscore");

        $entry++;
        $menu[$entry] = array("caption" => "Taktikschirm", "link" => "takscreen.php?mod=takscreen", "module" => "takscreen","id" => $entry);
          $menu[$entry]['items'][] = array("caption"   => "<div title=\"neuen Atter eintragen\">Neuer Atter</div>", "link" => "takscreen.php?action=addatter");
          $menu[$entry]['items'][] = array("caption" => "<div title=\"neuen Deffer eintragen\">Neuer Deffer</div>", "link" => "takscreen.php?action=adddeffer");
          $menu[$entry]['items'][] = array("caption" => "<div title=\"Milis parsen\">Miliparser</div>", "link" => "takscreen.php?action=miliparser");
          $menu[$entry]['items'][] = array("caption" => "<div title=\"Takscreen parsen\">Takscreenparser</div>", "link" => "takscreen.php?action=takparser");

    $entry++;
    $menu[$entry] = array("caption" => "Flottenpflege", "link" => "user.php?mod=fleets&action=fleets", "module" => "fleets","id" => $entry);
      $menu[$entry]['items'][] = array("caption"   => "<div title=\"Flottenparser\">Flottenparser</div>", "link" => "takscreen.php?action=fleetparser");
    
    $entry++;
    $menu[$entry] = array("caption" => "Simulator", "link" => "simulator.php?mod=simulator", "module" => "simulator","id" => $entry);

    $entry++;
    $menu[$entry] = array("caption" => "Attplaner", "link" => "attplan.php?mod=attplan", "module" => "attplan","id" => $entry);
      $menu[$entry]['items'][] = array("caption"   => "<div title=\"Angriffsplan hinzufügen\">Hinzufügen</div>", "link" => "attplan.php?action=add");

    $entry++;
    $menu[$entry] = array("caption" => "Scancenter", "link" => "scans.php?mod=scans", "module" => "scans","id" => $entry);
      $menu[$entry]['items'][] = array("caption" => "<div title=\"Ziele suchen\">Ziele suchen</div>", "link" => "scans.php?action=targets");
      $menu[$entry]['items'][] = array("caption" => "<div title=\"Scan hinzufügen/updaten\">Hinzufügen</div>", "link" => "scans.php?action=add", "class" => "'admin'");
    
    $entry++;
    $menu[$entry] = array("caption" => "Allianz", "link" => "admin.php?action=ally&mod=ally", "module" => "ally", "admin" => true,"id" => $entry);
    $menu[$entry]['rights'] = array("addally","deleteally","editallay");
      $menu[$entry]['items'][] = array(
        "caption" => "<div title=\"Hier k&ouml;nnen Sie Allianzen anlegen\">Hinzufügen</div>",
        "link" => "admin.php?action=addally",
        "admin" => true,
        "rights" => array("addally")
      );
    
    $entry++;
    $menu[$entry] = array("caption" => "Galaxie", "link" => "admin.php?action=galaxy&mod=galaxy", "module" => "galaxy", "admin" => true,"id" => $entry);
    $menu[$entry]['rights'] = array("addgala","deletegala","editgala");
      $menu[$entry]['items'][] = array(
        "caption" => "<div title=\"Hier k&ouml;nnen Sie Galaxien anlegen\">Hinzufügen</div>",
        "link" => "admin.php?action=addgala",
        "admin" => true,
        "rights" => array("addgala")
      );
    $entry++;
    $menu[$entry] = array("caption" => "Benutzer", "link" => "admin.php?action=user&mod=user", "module" => "user", "admin" => true,"id" => $entry);
    $menu[$entry]['rights'] = array("useredit");
#      $menu[$entry]['items'][$item_entry]['rights'][]   = "fleetedit";
      $menu[$entry]['items'][] = array(
        "caption" => "<div title=\"Hier k&ouml;nnen Sie die Benutzer anlegen\">Hinzufügen</div>",
        "admin" => true,
        "link" => "admin.php?action=adduser",
        "rights" => array("useredit")
      );
      $menu[$entry]['items'][] = array(
        "caption" => "<div title=\"Logfile aller Aktionen\">Logfile</div>",
        "admin" => true,
        "link" => "admin.php?action=logfile",
        "rights" => array("logfile","resetlogfile")
      );
    $entry++;
    $menu[$entry] = array("caption" => "Gruppen", "link" => "admin.php?action=groups&mod=groups", "module" => "groups", "admin" => true,"id" => $entry);
    $menu[$entry]['rights'] = array("groupedit");
      $menu[$entry]['items'][] = array(
        "caption" => "<div title=\"Hier k&ouml;nnen Sie Gruppen anlegen\">Hinzufügen</div>",
        "admin" => true,
        "link" => "admin.php?action=addgroup",
        "rights" => array("groupedit")
      );
      $menu[$entry]['items'][] = array(
        "caption" => "<div title=\"Gruppenrechte festlegen\">Rechte</div>",
        "admin" => true,
        "link" => "admin.php?action=grouprights",
        "rights" => array("groupedit")
      );
    $entry++;
    $menu[$entry] = array("caption" => "IRCBots", "link" => "admin.php?action=bots&mod=bots", "module" => "bots", "admin" => true, "rights" => array("bots"),"id" => $entry);
      $menu[$entry]['items'][] = array(
        "caption" => "<div title=\"Gruppenrechte festlegen\">Rechte</div>",
        "admin" => true,
        "link" => "admin.php?action=grouprights",
        "rights" => array("groupedit")
      );

    $menucount = count($menu);
    $open_menus = $_COOKIE['menuitems'];
    if($open_menus) {
      $open_menus = explode(",",$open_menus);
    } else {
      $open_menus = array();
    }
    for($i = 0;$i < $menucount; $i++) {
      if(!in_array($menu[$i]['id'],$open_menus)) {
        $menu[$i]['closed'] = true;
      }
      if(count($menu[$i]['rights'])) {
        #check rights for the menu
        $hasright = false;
        if(count($this->userdata['rights'])) {
          for($j=0;$j < count($menu[$i]['rights']);$j++) {
            if(array_key_exists($menu[$i]['rights'][$j],$this->userdata['rights'])) {
              $hasright = true;
              break;
            }
          }
        }
        #delete menu and submenus
        if(!$hasright) {unset($menu[$i]);continue;}
      }
      #check submenus
      if(count($menu[$i]['items'])) {
        $items = &$menu[$i]['items'];
        $itemcount = count($items);
        for($j=0;$j < $itemcount;$j++) {
          #check rights for the submenu
          if(count($items[$j]['rights'])) {
            $hasright = false;
            if(count($this->userdata['rights'])) {
              for($k=0;$k < count($items[$j]['rights']);$k++) {
                if(array_key_exists($items[$j]['rights'][$k],$this->userdata['rights'])) {
                  $hasright = true;
                  break;
                }
              }
            }
            #delete submenu 
            if(!$hasright) {unset($items[$j]);continue;}
          }
        }
      }
    }
    $this->box['menu']['template'] = 'menu.html';
    $this->box['menu']['title'] = 'Menu';
    $this->template->assign('menu',$menu);
  }

  #
  # User Login
  #

  function _loginUser() {
    if ($_POST['userlogin']) {
      #check fields
      $logindata['username'] = trim($_POST['login_username']);
      $logindata['password'] = trim($_POST['login_password']);
      $errors = false;
      foreach ( $logindata as $key => $value) {
        if (!$value) {
          $this->forms['userlogin']['fields'][$key]['bgrd'] = '_error';
          $errors = true;
        } else {
          $this->forms['userlogin']['fields'][$key]['value'] = $value;
        }
      }
      #empty fields
      if ($errors) {
        $this->forms['userlogin']['errormessage'] = "Feld leer!";
      } else {
        $return = getUserByLogin($logindata['username'],$logindata['password']);
        if (!$return || $return['activation']) {
          #login wrong
          $this->forms['userlogin']['errormessage'] = "Login/Passwort falsch!";
          addToLogfile("Login fehlgeschlagen, User ".$logindata['username'],"Login/Logout");
        } else {
          #login ok
          #save id and password in session
          $sessionuserdata['id'] = $return['uid'];
          $sessionuserdata['password'] = $return['password'];
          $_SESSION['sessionuserdata'] = $sessionuserdata;
          LoggedIn($return['uid']);
          addToLogfile("Login","Login/Logout",$return['uid']);
          setcookie('menuitems');
          $this->_header("index.php");
        }
      }
    }
    $this->template->assign('title','Login');
    $this->template->assign('forms',$this->forms);
    $this->template->display('index_login.html');
    exit();
  }

  #
  # User Logout
  #
  function _logoutUser() {
    addToLogfile("Logout","Login/Logout",$this->userdata['uid']);
    deleteUserOnline($this->userdata['uid']);
    unset($_SESSION['sessionuserdata']);
    setcookie('menuitems');
    $this->_header("index.php");
    exit();
  }
  #
  # liefert null wenn der user das Recht nicht besitzt, sonst den minimalen rank
  #
  function _getMinRightRank($rights) {
    $rank = null;
    if ($this->userdata) {
      if (is_array($rights)) {
        for($i=0;$i<count($rights);$i++){
          if(isset($this->userdata['rights'][$rights[$i]]['rank'])) {
            $rank[] = $this->userdata['rights'][$rights[$i]]['rank'];
          }
        }
        if ($rank){
          $rank =  min($rank);
        }
      } else {
        $rank = $this->userdata['rights'][$rights]['rank'];
      }
    }
    return $rank;
  }

  #
  # liefert TRUE wenn der User das Recht oder eins der Rechte hat
  # (für Zugangscheck)
  # param $rights = right|array of rights
  #
  function _checkUserRights($rights) {
    if ($this->userdata) {
      if (is_array($rights)) {
        for($i=0;$i<count($rights);$i++){
          if($this->userdata['rights'][$rights[$i]]) {
            return true;
          }
        }
      } else {
        if ($this->userdata['rights'][$rights]) return true;
      }
    }
    return false;
  }

  function getTickList($ticks=1,$select=-1) {
    $list=array();
    for($i=1;$i <= $ticks;$i++){
      if($select == $i) {
        $list[] = array("value"=>$i,"title"=>$this->formattime($i*15*60,true), "selected" => "selected");
      } else {
        $list[] = array("value"=>$i,"title"=>$this->formattime($i*15*60,true));
      }
    }
    return $list;
  }

  function formattime($time,$string=false) {
    if (!is_numeric($time)) return;
    if($time >= 0) {
      $switch = $this->userdata['timeview'];
    } else {
      $switch = 0;
    }
    switch ($switch) {
       case 1:
          $hour = floor($time/3600);
          $minutes = floor(($time-$hour*3600)/60);
          if ($hour < 10 && $hour > 0) $hour = "0".$hour;
          if ($hour == 0) $hour = "00";
          if ($minutes < 10) $minutes = "0".$minutes;
          $time = "$hour:$minutes";
          if ($string) $time .= " Std";
         break;
       default:
          $time = floor($time/60);
          if ($string) $time .= " min";
         break;
    }
    return $time;
  }
  
  function show_message($title,$message,$link,$class=null,$params=null) {
    $this->template->assign(
      array("title" => $title,
        "message" => $message,
        "link" => $link,
        "class" => $class,
        "params" => $params)
      );
    $this->show('message',$title);
  }
}
?>
