<?php
/**
  \file admin.class.php
  \brief Admin Seite

  Klasse für die Adminfunktionalitäten
*/
require_once("classes/kibo.page.class.php");

require_once("functions/functions.php");
require_once("database/db.group.php");
require_once("database/db.scans.php");
require_once("database/db.ally.php");
require_once("database/db.gala.php");
require_once("database/db.bot.php");
require_once("functions/bbcode.php");

/**
  \brief Adminfunktionalitäten
  \author Stefan Dieringer

  Stellt sämtliche Funktionen bereit, die die Administrierung
  des TCs betreffen.
*/
class adminpage extends kibopage  {

  /**
  * Eventhandler
  * @param nix
  *
  */
  function run () {
    parent::run();
    #functionhash
    $functions['logfile'] ="Logfile_list()";
    $functions['user'] ="User_list()";
    $functions['edituser'] ="User_edit()";
    $functions['adduser'] ="User_add()";
    $functions['deleteuser'] ="User_delete()";
    $functions['groups'] ="Group_list()";
    $functions['groupdetails'] ="Group_details()";
    $functions['editgroup'] ="Group_edit()";
    $functions['deletegroup'] ="Group_delete()";
    $functions['addgroup'] ="Group_add()";
    $functions['grouprights'] ="Group_rights()";
    $functions['editgrouprights'] ="Group_Rights_edit()";
    $functions['userstats'] ="User_stats()";
    $functions['userdetails'] ="User_details()";
    $functions['ally'] ="Ally_list()";
    $functions['allydetails'] ="Ally_details()";
    $functions['addally'] ="Ally_add()";
    $functions['editally'] ="Ally_edit()";
    $functions['deleteally'] ="Ally_delete()";
    $functions['addgala'] ="Galaxy_add()";
    $functions['editgala'] ="Galaxy_edit()";
    $functions['galaxy'] ="Galaxy_list()";
    $functions['galadetails'] ="Galaxy_details()";
    $functions['deletegala'] ="Galaxy_delete()";
    $functions['bots'] ="Bot_list()";
    $functions['addbot'] ="Bot_add()";
    $functions['editbot'] ="Bot_edit()";
    $functions['deletebot'] ="Bot_delete()";
    #handle action
    if ($functions[$this->action]) {
      eval("\$this->".$functions[$this->action].";");
    }
    #default
    $this->_header("index.php");
  }

  /** \brief Logfile anzeigen

      Listet alle Einträge des Logfile auf
   */
  function Logfile_list() {
    if (!$this->userdata['rights']['logfile']) {
      #no permission
      $this->_header("","no permission");
    }
    $logfilefilter = $_SESSION['logfilefilter'];
    if ($_REQUEST['subaction'] == 'newsearch' && $logfilefilter) {
      unset($logfilefilter);
      unset($_SESSION['logfilefilter']);
    }
    $page = param_num("page",1);
    $rows = 12;
    if ($_REQUEST['sort'] && $_REQUEST['order'] && $logfilefilter) {
      $sort = trim($_REQUEST['sort']);
      $order = trim($_REQUEST['order']);
      if ($sort != 'username' && $sort != 'cat' && $sort != 'date') $sort = 'date';
      if ($order != "asc" && $order != "desc") $order = "desc";
      $logfilefilter['sort'] = $sort;
      $logfilefilter['order'] = $order;
      $_SESSION['logfilefilter'] = $logfilefilter;
    }
    if ($_REQUEST['subaction'] == "search") {
      $logfilefilter['username'] = param_str("username");
      $logfilefilter['gala'] = param_str("gala");
      $logfilefilter['cat'] = $_REQUEST['cat'];
      $logfilefilter['sort'] = "date";
      $logfilefilter['order'] = "desc";
      $_SESSION['logfilefilter'] = $logfilefilter;
    }
    if (!$logfilefilter) {
      $catlist = getLogFilecatList();
      $this->template->assign('catlist',$catlist);
      $this->forms['logfile']['action'] = "logfile";
      $this->forms['logfile']['url'] = "admin.php";
      $this->show('logfile_list_form','Logfile durchsuchen');
    }
    $this->forms['logfile'][$logfilefilter['sort']][$logfilefilter['order']] = '_active';
    $logfile = listLogFile($logfilefilter,&$pages,&$page,$rows);
    $this->forms['logfile']['pages'] = showPageBar($page,$pages,"admin.php?action=logfile","page","menu");
    for ($i=0;$i<count($logfile);$i++) {
      $item = &$logfile[$i];
      preg_match("/^(\d{4})-(\d{2})-(\d{2})\s(\d{2}):(\d{2}):(\d{2})/s",$item['time'],$result);
      $item['date'] = $result[3].".".$result[2].".".$result[1];
      $item['time'] = $result[4].":".$result[5];
    }
    $this->template->assign('logfile',$logfile);
    $this->show('logfile_list','Logfile');
  }
  /** \brief User anzeigen

      Listet alle User auf, vorher wird ein Filter angeboten.
      Filter ist abhängig von den Rechten die der User hat.
   */
  function User_list() {
    #check rights
    $rank = $this->userdata['rights']['useredit']['rank'];
    if (!$rank){
      #no permission
      $this->_header("","no permission");
    }
    $userlistfilter = $_SESSION['userlistfilter'];
    $page = param_num("page",1);
    $rows = 12;
    if ($_REQUEST['subaction'] == 'newsearch' && $userlistfilter) {
      unset($userlistfilter);
      unset($_SESSION['userlistfilter']);
    }
    if ($_REQUEST['sort'] && $_REQUEST['order'] && $userlistfilter) {
      $sort = trim($_REQUEST['sort']);
      $order = trim($_REQUEST['order']);
      if ($sort != 'username' && $sort != 'koords'
        && $sort != 'group' && $sort != 'phone') $sort = 'koords';
      if ($order != "asc" && $order != "desc") $order = "asc";
      $userlistfilter['sort'] = $sort;
      $userlistfilter['order'] = $order;
      $_SESSION['userlistfilter'] = $userlistfilter;
    }

    if ($_REQUEST['subaction'] == "search") {
      $userlistfilter['username'] = param_str("username");
      $userlistfilter['gala'] = param_str("gala");
      if ($rank > 1) {
        $userlistfilter['ally'] = $this->userdata["aid"];
        #checken ob galas auch zur allys gehören
        $userlistfilter['checkallygalas'] = true;
      } else {
        $userlistfilter['ally'] = param_num("ally");
      }
      $userlistfilter['group'] = $_REQUEST['group'];
      $userlistfilter['phone'] = param_str("phone");
      $userlistfilter['sort'] = "koords";
      $userlistfilter['order'] = "asc";
      $_SESSION['userlistfilter'] = $userlistfilter;
    }
    if (!$userlistfilter) {
      if ($rank == 1) {
        $this->template->assign('allylist',getAllyList());
        $this->template->assign('rank1',1);
      } else {
        $this->template->assign('ally',$this->userdata['tag']);
      }
      $this->template->assign('grouplist',getGroupList());
      $this->forms['userlist']['action'] = "user";
      $this->forms['userlist']['url'] = "admin.php";
      $this->show('user_list_form','Benutzer suchen');
      exit();
    }
    #nochmal rang check
    if ($rank > 1 && $userlistfilter['ally'] != $this->userdata["aid"]) {
      $userlistfilter['ally'] = $this->userdata["aid"];
      #checken ob galas auch zur allys gehören
      $userlistfilter['checkallygalas'] = true;
      $_SESSION['userlistfilter'] = $userlistfilter;
    }
    $this->forms['userlist'][$userlistfilter['sort']][$userlistfilter['order']] = '_active';
    $userlist = listUser($userlistfilter,&$pages,&$page,$rows);
    $this->forms['userlist']['pages'] = showPageBar($page,$pages,"admin.php?action=user","page","menu");
    $setback = "admin.php?action=user&page=".$page;
    $_SESSION['setback'] = $setback;
    $this->template->assign('userlist',$userlist);
    $this->show('user_list','Benutzerliste');
  }
  /**
      \brief Zeigt Userstatistiken

      zeigt Statistiken zu den Usern an
   */
  function User_Stats() {
    if (!$this->_checkUserRights(array("admin")))
     $this->_header("index.php");
    $userstatsfilter = $_SESSION['userstatsfilter'];
    $page = param_num("page",1);
    $rows = 12;
    if ($_REQUEST['sort'] && $_REQUEST['order'] && $userstatsfilter) {
      $sort = trim($_REQUEST['sort']);
      $order = trim($_REQUEST['order']);
      if ($sort != 'username' && $sort != 'koords' && $sort != 'login') $sort = 'username';
      if ($order != "asc" && $order != "desc") $order = "asc";
      $userstatsfilter['sort'] = $sort;
      $userstatsfilter['order'] = $order;
      $_SESSION['userstatsfilter'] = $userstatsfilter;
    }
    if (!$userstatsfilter) {
      $userstatsfilter['sort'] = 'koords';
      $userstatsfilter['order'] = 'asc';
      $_SESSION['userstatsfilter'] = $userstatsfilter;
    }
    $this->forms['userlist'][$userstatsfilter['sort']][$userstatsfilter['order']] = '_active';
    $userlist = listUser($userstatsfilter,&$pages,&$page,$rows);
    for($i=0;$i<count($userlist);$i++) {
      if ($userlist[$i]['loggedin']) {
        $item = &$userlist[$i];
        preg_match("/^(\d{4})-(\d{2})-(\d{2})\s(\d{2}):(\d{2}):(\d{2})/s",$item['loggedin'],$result);
        $item['date'] = $result[3].".".$result[2].".".$result[1];
        $item['time'] = $result[4].":".$result[5];
      }
    }
    $this->forms['userlist']['pages'] = showPageBar($page,$pages,"admin.php?action=userstats","page","menu");
    $this->template->assign('userlist',$userlist);
    $this->show('user_stats','Benutzerstatistik');
  }
  /**
    \brief Gruppen auflisten

    Listet die Rechtegruppen des TC auf
   */
  function Group_list()
  {
    if (!$this->userdata['rights']['groupedit']) {
      #no permission
      $this->_header("","no permission");
    }
    $page = param_num("page",1);
    $rows = 6;
    $grouplistfilter = $_SESSION['grouplistfilter'];
    if (($_REQUEST['sort'] && $_REQUEST['order']) || !$grouplistfilter) {
      $sort = trim($_REQUEST['sort']);
      $order = trim($_REQUEST['order']);
      if ($sort != 'name' && $sort != 'descr' && $sort != 'member') $sort = 'name';
      if ($order != "asc" && $order != "desc") $order = "asc";
      $grouplistfilter['sort'] = $sort;
      $grouplistfilter['order'] = $order;
      $_SESSION['grouplistfilter'] = $grouplistfilter;
    }
    $this->forms['grouplist'][$grouplistfilter['sort']][$grouplistfilter['order']] = '_active';
    $grouplist = listGroups($grouplistfilter,&$pages,&$page,$rows);
    $this->forms['grouplist']['pages'] = showPageBar($page,$pages,"admin.php?action=groups","page","menu");
    $this->template->assign('grouplist',$grouplist);
    $this->show('group_list','Gruppenliste');
  }
  /**
    \brief Gruppeninfos

    Zeigt Informationen zu einer Gruppe an
   */
  function Group_details(){
    if (!$this->userdata['rights']['groupedit']) {
      #no permission
      $this->_header("","no permission");
    }
    $id = param_num("id");
    if (!$id) $this->_header();
    $return = getGroup($id);
    if (!$return) $this->_header();
    $this->template->assign("searchlink","admin.php");
    $this->template->assign("editlink","admin.php?action=editgroup&id=$id");
    $this->template->assign("deletelink","admin.php?action=deletegroup&id=$id");
    $this->template->assign("groupid",$id);
    $this->forms['groupdetails']['fields'] = $return;
    $this->show('group_details_admin',"Gruppendetails");
  }

  /**
    \brief Gruppe bearbeiten

     Ändert die Daten einer Gruppe
   */
  function Group_edit() {
    if (!$this->userdata['rights']['groupedit']) {
      #no permission
      $this->_header("","no permission");
    }
    $id = param_num("id");
    if (!$id) $this->_header();
    $page = param_num("page",1);
    $return = getGroup($id);
    if (!$return) $this->_header();
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['groupedit']) {
      #save step
      unset($data['groupedit']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['action'] = "groupdetails";
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Gruppe bearbeiten";
      $this->forms['information']['message'] = "&Auml;nderung erfolgreich";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Gruppe bearbeiten");
    }
    #formular send
    if ($_REQUEST['send']) {
      $items['name'] = param_str("name",true);
      $items['descr'] = param_str("descr",true);
      if (!$items['name']) {
        $errors[] = "Name darf nicht leer sein!";
        $this->forms['addgroup']['fields']['name']['bgrd'] = '_error';
      }
      if (!$items['descr']) {
        $errors[] = "Beschreibung darf nicht leer sein!";
        $this->forms['addgroup']['fields']['descr']['bgrd'] = '_error';
      }
      #optional parameters
      $items['usertitle'] = param_str("usertitle",true);
      if (!$errors &&  $items['name'] != $return['name'] && getGroupByName($items['name'])) {
        $errors[] = 'Gruppe existiert bereits!';
        $this->forms['addgroup']['fields']['name']['bgrd'] = '_error';
      }
      if (!$errors) {
        #save step
        $data['groupedit'] = 1;
        $_SESSION['steps'] = $data;
        addToLogfile("Gruppe ".$return['name']." bearbeitet","Admin",$this->userdata['uid']);
        updateGroup($return['gid'],$items['name'],$items['descr'],$items['usertitle']);
        $this->_header("admin.php?action=editgroup&id=".$return['gid']."&send");
      } else {
        $this->template->assign("errors",$errors);
      }
    } else {
      $this->forms['groupedit']['fields']['usertitle']['value'] = $return['usertitle'];
      $this->forms['groupedit']['fields']['name']['value'] = $return['name'];
      $this->forms['groupedit']['fields']['descr']['value'] = $return['descr'];
    }
    $this->forms['groupedit']['id'] = $return['gid'];
    $this->forms['groupedit']['name'] = $return['name'];
    $this->forms['groupedit']['url'] = 'admin.php?id='.$return['gid'];
    $this->forms['groupedit']['action'] = 'editgroup';
    $this->show('group_edit_form',"Gruppe bearbeiten");
  }
  /**
    \brief Gruppe hinzufügen

    Fügt eine neue Gruppe hinzu
   */
  function Group_add() {
    if (!$this->userdata['rights']['groupedit']) {
      #no permission
      $this->_header("","no permission");
    }
    $page = param_num("page",1);
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['addgroup']) {
      $id = param_num("id");
      if ($id) {
        $return = getGroup($id);
        if (!$return) $this->_header();
      }
      #save step
      unset($data['addgroup']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['action'] = "groupdetails";
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Gruppe hinzufügen";
      $this->forms['information']['message'] = "Gruppe ".$return['name']." hinzugefügt";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Gruppe hinzufügen");
    }
    #formular send
    if ($_REQUEST['send']) {
      $items['name'] = param_str("name",true);
      $items['descr'] = param_str("descr",true);
      $errors = false;
      #check if empty
      if (!$items['name']) {
        $errors[] = "Name darf nicht leer sein!";
        $this->forms['addgroup']['fields']['name']['bgrd'] = '_error';
      }
      if (!$items['descr']) {
        $errors[] = "Beschreibung darf nicht leer sein!";
        $this->forms['addgroup']['fields']['descr']['bgrd'] = '_error';
      }
      #optional parameters
      $items['usertitle'] = param_str("usertitle",true);
#      for ($i=0;$i<count($rights);$i++) {
#        if ($_POST[$rights[$i]['rid']."_".$rights[$i]['name']]) $rights[$i]['isset'] = 1;
#        else $rights[$i]['isset'] = 0;
#      }
      if (!$errors && getGroupByName($items['name'])) {
        $errors[] = "Gruppe existiert bereits!";
        $this->forms['addgroup']['fields']['name']['bgrd'] = '_error';
      }
      if (!$errors) {
        #save step
        $data['addgroup'] = 1;
        $_SESSION['steps'] = $data;
        $gid = addGroup($items['name'],$items['descr'],$items['usertitle']);
        if ($gid) {
          addToLogfile("Gruppe ".$items['name']." hinzugefügt","Admin",$this->userdata['uid']);
          $this->_header("admin.php?action=addgroup&id=".$gid."&send");
        }
      } else {
        $this->forms['addgroup']['fields']['descr']['value'] = $items['descr'];
        $this->forms['addgroup']['fields']['name']['value'] = $items['name'];
        $this->forms['addgroup']['fields']['usertitle']['value'] = $items['usertitle'];
        $this->template->assign("errors",$errors);
      }
    }
    $this->forms['addgroup']['name'] = $return['name'];
    $this->forms['addgroup']['url'] = "admin.php";
    $this->forms['addgroup']['action'] = 'addgroup';
    $this->show('group_add_form',"Gruppe hinzufügen");
  }
  /**
    \brief Userinfos

     Zeigt ausführliche Informationen zu einem User
   */
  function User_details() {
    #check rights
    $rank = $this->userdata['rights']['useredit']['rank'];
    if (!$rank){
      #no permission
      $this->_header("","no permission");
    }
    $id = param_num("id");
    if (!$id) $this->_header();
    $return = getUserByID($id);
    if (!$return) $this->_header();
    #ally/galaxyebene
    if ($rank > 1 && $return['aid'] != $this->userdata['aid']){
      #no permission
      $this->_header("","no permission");
    }
    if ($rank == 1 ||
        ($rank == 2 && $return['aid'] == $this->userdata['aid']) ||
        ($rank == 3 && $return['gala'] == $this->userdata['gala']))
    {
      $this->template->assign("editlink","admin.php?action=edituser&id=$id");
      $this->template->assign("deletelink","admin.php?action=deleteuser&id=$id");
    }
    $this->template->assign("user",$return);
    $this->template->assign("koords",$return['gala'].":".$return['pos']);
    $this->show('user_details_admin',"Benutzerdetails");
  }
  /**
    \brief User bearbeiten

    Ändert die Daten eines Users
   */
  function User_edit() {
    #check rights
    $rank = $this->userdata['rights']['useredit']['rank'];
    if (!$rank){
      #no permission
      $this->_header("","no permission");
    }
    $page = param_num("page",1);
    $id = param_num("id");
    if (!$id) $this->_header();
    $return = getUserByID($id);
    if (!$return) $this->_header();
    #check rights
    if (($rank > 1 && $this->userdata['aid'] != $return['aid']) ||
        ($rank > 2 && $this->userdata['gala'] != $return['gala'])){
      #no permission
      $this->_header("","no permission");
    }
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['useredit']) {
      #save step
      unset($data['useredit']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Benutzerdaten &auml;ndern";
      $this->forms['information']['message'] = "&Auml;nderung erfolgreich";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Benutzerdaten &auml;ndern");
    }
    #formular send
    if ($this->userdata['rights']['changegroup']) {
      $grouplist = getGroupList($this->userdata['rights']['changegroup']['rank']);
      if ($return['gid']) {
        for($i=0;$i < count($grouplist);$i++){
          if ($grouplist[$i]['gid'] == $return['gid']) {
            $canchangegroup = true;
            break;
          }
        }
      } else {
        $canchangegroup = true;
      }
    }
    if($canchangegroup) {
      $this->template->assign("changegroup",1);
    } else {
      $this->template->assign("group",$return['groupname']);
    }
    if ($rank == 1) {
      $allylist = getAllyList();
    } else {
      $this->template->assign("ally",$this->userdata['tag']);
    }
    $this->template->assign("rank",$rank);
    $galalist = array();
    if ($_REQUEST['send']) {
      $items['login']['value'] = param_str("login",true);
      $items['nick']['value'] = param_str("nick",true);
      $items['ircauth']['value'] = param_str("ircauth",true);
      $items['pos']['value'] = param_num("pos",null,true);
      $items['gala']['value'] = param_num("gala",null,true);
      $password = param_str("password",true);
      if ($rank == 1) {
        $items['aid']['value'] = param_num("ally",0,true);
        #check allyid
        if ($items['aid']['value']) {
          $ally = 0;
          for ($i=0;$i < count($allylist);$i++) {
            if ($items['aid']['value'] == $allylist[$i]['aid']) {
              $ally = &$allylist[$i];
              $ally['selected'] = "selected";
              break;
            }
          }
        }
        if (!$ally) $this->_header("","Ungültige Allianzid!");
      } else {
        $items['aid']['value'] = $this->userdata['aid'];
      }
      #check gala
      if ($rank < 3) {
        $galalist = getGalaListbyAlly($items['aid']['value']);
        if (!$galalist){
          $errors[] = "Die Allianz hat keine Galaxien!";
          $galalist[] = array("gala" => "keine");
        }
      } else {
        $items['gala']['value'] = $this->userdata['gala'];
        $this->template->assign("gala",$this->userdata['gala']);
      }
      if ($_REQUEST['next_x']) {
        if (!$items['nick']['value']) {
          $errors[] = "Nickname darf nicht leer sein!";
          $items['nick']['bgrd'] = '_error';
        }
        if (!$items['login']['value']) {
          $errors[] = "Login darf nicht leer sein!";
          $items['login']['bgrd'] = '_error';
        }
        if (!$items['pos']['value']) {
          $items['pos']['bgrd'] = '_error';
          $errors[] = "Die Position darf nicht leer sein!";
        }
        if ($canchangegroup) {
          #check gid
          $items['gid']['value'] = param_num('group',0,true);
          if ($items['gid']['value']) {
            $group = 0;
            for ($i=0;$i < count($grouplist);$i++) {
              if ($items['gid']['value'] == $grouplist[$i]['gid']) {
                $group = &$grouplist[$i];
                $group['selected'] = "selected";
                break;
              }
            }
            if (!$group) $this->_header();
          }
        } else {
          $items['gid']['value'] = $return['gid'];
        }
        #check nickname
        if ($items['nick']['value'] && strtolower($items['nick']['value']) != strtolower($return['nick'])
             && getUserByNick($items['nick']['value'])) {
          $errors[] = 'User existiert bereits!';
          $items['nick']['bgrd'] = '_error';
        }
        #check login
        if ($items['login']['value'] && strtolower($items['login']['value']) != strtolower($return['login'])
             && getUserByLogin($items['login']['value'])) {
          $errors[] = 'Login existiert bereits!';
          $items['login']['bgrd'] = '_error';
        }
        #check galaid
        if ($items['gala']['value'] && $rank < 3) {
          $galaxy = 0;
          for ($i=0;$i < count($galalist);$i++) {
            if ($items['gala']['value'] == $galalist[$i]['gala']) {
              $galaxy = &$galalist[$i];
              $galaxy['selected'] = "selected";
              break;
            }
          }
          if (!$galaxy) $this->_header("index.php","Ungültige Galaid!");
        }
        if (!$errors && ($return['gala'] != $items['gala']['value'] || $return['pos'] != $items['pos']['value'])){
          $chkuser = getUserByPos($items['gala']['value'],$items['pos']['value']);
          if ($chkuser) {
            $errors[] = "User existiert bereits, <a href=\"admin.php?action=userdetails&id=".$chkuser['uid']."\">".$chkuser['nick']." (".$chkuser['gala'].":".$chkuser['pos'].")</a>";
            $items['pos']['bgrd'] = '_error';
          }
        }
        if (!$errors) {
          #save step
          $data['useredit'] = 1;
          $_SESSION['steps'] = $data;
          if ($password) {
            #eigenes pw geändert
            if ($return['uid'] == $this->userdata['uid']) {
              updateUserPassword($return['uid'],$password);
              $sessionuserdata['id'] = $this->userdata['uid'];
              $sessionuserdata['password'] = md5($password);
              $_SESSION['sessionuserdata'] = $sessionuserdata;
            } else {
              updatePassword($return['uid'],$password);
            }
            addToLogfile("Passwort von ".$return['nick']." geändert","Admin",$this->userdata['uid']);
          }
          addToLogfile("User ".$return['nick']." bearbeitet","Admin",$this->userdata['uid']);
          updateAdminUser($return['uid'],
                          $items['nick']['value'],
                          $items['login']['value'],
                          $items['gala']['value'],
                          $items['pos']['value'],
                          $items['gid']['value'],
                          $items['ircauth']['value']
                          );
          $this->_header("admin.php?action=edituser&id=".$return['uid']."&send");
        }
      }
    } else {
      if ($return['gid'] && $this->userdata['rights']['changegroup']) {
        for ($i=0;$i < count($grouplist);$i++) {
          if ($return['gid'] == $grouplist[$i]['gid']) {
            $grouplist[$i]['selected'] = "selected";
            break;
          }
        }
      }
      if ($rank == 1) {
        #select ally
        for ($i=0;$i < count($allylist);$i++) {
          if ($return['aid'] == $allylist[$i]['aid']) {
            $ally = &$allylist[$i];
            $ally['selected'] = "selected";
            break;
          }
        }
      }
      if ($rank < 3) {
        $galalist = getGalaListbyAlly($return['aid']);
        if (!$galalist){
          $errors[] = "Die Allianz hat keine Galaxien!";
          $galalist[] = array("gala" => "keine");
        } else {
          #select gala
          for ($i=0;$i < count($galalist);$i++) {
            if ($return['gala'] == $galalist[$i]['gala']) {
              $galalist[$i]['selected'] = "selected";
              break;
            }
          }
        }
      } else {
        $this->template->assign("gala",$this->userdata['gala']);
      }
      $items['ircauth']['value'] = $return['ircauth'];
      $items['nick']['value'] = $return['nick'];
      $items['login']['value'] = $return['login'];
      $items['pos']['value'] = $return['pos'];
    }
    $this->template->assign("errors",$errors);
    $this->template->assign("galalist",$galalist);
    $this->template->assign("allylist",$allylist);
    if (!$items['ircauth']['value']) {
      $items['ircauth']['bgrd'] = "_optional";
    }
    if (!$items['password']['value']) {
      $items['password']['bgrd'] = "_optional";
    }
    $this->template->assign("items",$items);
    $this->template->assign("grouplist",$grouplist);
    $this->template->assign("id",$return['uid']);
    $this->template->assign("username",$return['nickname']);
    $this->show('user_edit_form',"Benutzerdaten &auml;ndern");
  }
  /**
    \brief User hinzufügen

    Fügt eines User hinzu.
   */
  function User_add() {
    #check rights
    $rank = $this->userdata['rights']['useredit']['rank'];
    if (!$rank){
      #no permission
      $this->_header("","no permission");
    }
    $page = param_num("page",1);
    $id = param_num("id");
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['adduser']) {
      if ($id) {
        $return = getUserByID($id);
      }
      if ($return) {
        $this->forms['information']['url'] = "admin.php?action=showdetails&id=".$return['uid']."&force";
      } else {
        $this->forms['information']['url'] = $this->backtracking->backlink();
      }
      #save step
      unset($data['adduser']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['action'] = "userdetails";
      $this->forms['information']['title'] = "Benutzer hinzufügen";
      $this->forms['information']['message'] = "Erfolgreich hinzugefügt";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Benutzer hinzufügen");
    }
    #formular send
    if ($this->userdata['rights']['changegroup']) {
      $grouplist = getGroupList($this->userdata['rights']['changegroup']['rank']);
      $this->template->assign("changegroup",1);
    }
    if ($rank == 1) {
      $allylist = getAllyList();
    } else {
      $this->template->assign("ally",$this->userdata['tag']);
    }
    $this->template->assign("rank",$rank);
    $galalist = array();
    if ($_REQUEST['send']) {
      $items['nickname']['value'] = param_str("nickname",true);
      $items['password']['value'] = param_str("password",true);
      $items['ircauth']['value']= param_str("ircauth",true);
      $items['login']['value']= param_str("login",true);
      $items['gala']['value'] = param_num("gala",null,true);
      $items['pos']['value'] = param_num("pos",null,true);
      $items['gala']['value'] = param_num("gala",0,true);
      if ($rank == 1) {
        $items['aid']['value'] = param_num("ally",0,true);
        #check allyid
        if ($items['aid']['value']) {
          $ally = 0;
          for ($i=0;$i < count($allylist);$i++) {
            if ($items['aid']['value'] == $allylist[$i]['aid']) {
              $ally = &$allylist[$i];
              $ally['selected'] = "selected";
              break;
            }
          }
        }
        if (!$ally) $this->_header("","Ungültige Allianzid!");
      } else {
        $items['aid']['value'] = $this->userdata['aid'];
      }
      if ($rank < 3) {
        $galalist = getGalaListbyAlly($items['aid']['value']);
        if (!$galalist){
          $errors[] = "Die Allianz hat keine Galaxien!";
          $galalist[] = array("gala" => "keine");
        }
      } else {
        $items['gala']['value'] = $this->userdata['gala'];
        $this->template->assign("gala",$this->userdata['gala']);
      }
      #auf weiter geklickt
      if ($_REQUEST['next_x']) {
        if (!$items['login']['value']) {
          $items['login']['bgrd'] = '_error';
          $errors[] = "Login darf nicht leer sein!";
        }
        if (!$items['nickname']['value']) {
          $items['nickname']['bgrd'] = '_error';
          $errors[] = "Nickname darf nicht leer sein!";
        }
        if (!$items['password']['value']) {
          $items['password']['bgrd'] = '_error';
          $errors[] = "Password darf nicht leer sein!";
        }
        if (!$items['pos']['value']) {
          $items['pos']['bgrd'] = '_error';
          $errors[] = "Position leer oder ungültig!";
        }
        if ($items['nickname']['value'] && getUserByNick($items['nickname']['value'])) {
          $items['nickname']['bgrd'] = '_error';
          $errors[] = "User existiert bereits!";
        }
        if ($items['login']['value'] && getUserByLogin($items['login']['value'])) {
          $items['login']['bgrd'] = '_error';
          $errors[] = "Login existiert bereits!";
        }
        if ($this->userdata['rights']['changegroup']) {
          #check gid
          $items['gid']['value'] = param_num("group",0,true);
          if ($items['gid']['value']) {
            $group = 0;
            for ($i=0;$i < count($grouplist);$i++) {
              if ($items['gid']['value'] == $grouplist[$i]['gid']) {
                $group = &$grouplist[$i];
                $group['selected'] = "selected";
                break;
              }
            }
            if (!$group) $this->_header("","Ungültige Gruppe, gid!");
          }
        } else {
          $items['gid']['value'] = 0;
        }
        #check galaid
        if ($items['gala']['value'] && $rank < 3) {
          $galaxy = 0;
          for ($i=0;$i < count($galalist);$i++) {
            if ($items['gala']['value'] == $galalist[$i]['gala']) {
              $galaxy = &$galalist[$i];
              $galaxy['selected'] = "selected";
              break;
            }
          }
          if (!$galaxy) $this->_header("","Ungültige Galaid!");
        }
        if (!$errors){
          $chkuser = getUserByPos($items['gala']['value'],$items['pos']['value']);
          if ($chkuser) {
            $errors[] = "User existiert bereits, <a href=\"admin.php?action=userdetails&id=".$chkuser['uid']."\">".$chkuser['nick']." (".$chkuser['gala'].":".$chkuser['pos'].")</a>";
            $items['pos']['bgrd'] = '_error';
          }
        }
        if (!$errors) {
          #save step
          $data['adduser'] = 1;
          $_SESSION['steps'] = $data;
          $id = addUser(
                          $items['nickname']['value'],
                          $items['login']['value'],
                          $items['password']['value'],
                          $items['gid']['value'],
                          $items['gala']['value'],
                          $items['pos']['value'],
                          $items['ircauth']['value']
          );
          addToLogfile("User ".$items['nickname']['value']." hinzugefügt","Admin",$this->userdata['uid']);
          $this->_header("admin.php?action=adduser&id=$id&send");
        }
      }
      $this->template->assign("errors",$errors);
    } else {
      if ($rank == 1) {
        $galalist = getGalaListbyAlly($allylist[0]['aid']);
        if (!$galalist){
          $errors[] = "Die Allianz hat keine Galaxien!";
          $this->template->assign("errors",$errors);
          $galalist[] = array("gala" => "keine");
        }
      } elseif ($rank == 2) {
        $galalist = getGalaListbyAlly($this->userdata['aid']);
        if (!$galalist){
          $errors[] = "Die Allianz hat keine Galaxien!";
          $this->template->assign("errors",$errors);
          $galalist[] = array("gala" => "keine");
        }
      } elseif ($rank == 3) {
        $this->template->assign("gala",$this->userdata['gala']);
      }
    }
    if (!$items['ircauth']['value']) {
      $items['ircauth']['bgrd'] = "_optional";
    }
    $this->template->assign("items",$items);
    $this->template->assign("galalist",$galalist);
    $this->template->assign("allylist",$allylist);
    $this->template->assign("grouplist",$grouplist);
    $this->show('user_add_form',"Benutzer hinzufügen");
  }
  /**
    \brief User löschen

    Löscht einen User aus dem TC
   */
  function User_delete() {
    #check rights
    $rank = $this->userdata['rights']['useredit']['rank'];
    if (!$rank){
      #no permission
      $this->_header("","no permission");
    }
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['deleteuser']) {
      #save step
      unset($data['deleteuser']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Benutzer löschen";
      $this->forms['information']['message'] = "Benutzer erfolgreich gelöscht";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Benutzer löschen");
    }
    $id = param_num("id");
    if (!$id) $this->_header();
    $return = getUserByID($id);
    if (!$return) $this->_header();
    #check rights
    if (($rank > 1 && $this->userdata['aid'] != $return['aid']) ||
        ($rank > 2 && $this->userdata['gala'] != $return['gala'])){
      #no permission
      $this->_header("","no permission");
    }
    #deleteuser, send
    if ($_REQUEST['send']) {
      if ($_REQUEST['yes_x']) {
        addToLogfile("User ".$return['nick']." gelöscht","Admin",$this->userdata['uid']);
        deleteUser($return['uid']);
        #save step
        $data['deleteuser'] = 1;
        $_SESSION['steps'] = $data;
        $this->_header("admin.php?action=deleteuser&send");
      } else {
        $this->_header();
      }
    } else {
      $this->forms['information']['url'] = "admin.php?id=".$return['uid'];
      $this->forms['information']['action'] = "deleteuser";
      $this->forms['information']['title'] = "Benutzer löschen";
      $this->forms['information']['message'] = "Benutzer <b>".$return['nick']." (".$return['tag'].")</b> löschen ?";
      if ($return['uid'] == $this->userdata['uid']) {
          $this->forms['information']['message'] .= "
          <br><br><b>WARNUNG!!</b><br>
          <b>Sie sind im Begriff sich selbst zu löschen!<br>
          Sie können sich danach nicht mehr einloggen!<b/><br>
          ";
      }
      $this->forms['information']['style'] = "red";
      $this->show('message_question', "Benutzer löschen");
    }
  }
  /**
    \brief Gruppe löschen

    Löscht eine Gruppe.
   */
  function Group_delete() {
    if (!$this->userdata['rights']['groupedit']) {
      #no permission
      $this->_header("","no permission");
    }
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['deletegroup']) {
      #save step
      unset($data['deletegroup']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Gruppe löschen";
      $this->forms['information']['message'] = "Gruppe erfolgreich gelöscht";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Gruppe löschen");
    }
    $id = param_num("id");
    if (!$id) $this->_header("","id fehlt");
    $return = getGroup($id);
    if (!$return) $this->_header("","ungültige Gruppe");
    #deletegroup, send
    if ($_REQUEST['send']) {
      if ($_REQUEST['yes_x']) {
        addToLogfile("Gruppe ".$return['name']." gelöscht","Admin",$this->userdata['uid']);
        deletegroup($return['gid']);
        #save step
        $data['deletegroup'] = 1;
        $_SESSION['steps'] = $data;
        $this->_header("admin.php?action=deletegroup&send");
      } else {
          $this->_header();
      }
    } else {
      $this->forms['information']['url'] = "admin.php?id=".$return['gid'];
      $this->forms['information']['action'] = "deletegroup";
      $this->forms['information']['title'] = "Gruppe löschen";
      $this->forms['information']['message'] = "Gruppe <b>".$return['name']."</b> löschen ?";
      if ($return['gid'] == $this->userdata['gid']) {
          $this->forms['information']['message'] .= "
          <br><br><b>WARNUNG!!</b><br>
          <b>Sie sind im Begriff ihre eigene Gruppe zu löschen,<br>
          Sie könnten dadurch wichtige Rechte unwiederbringlich verlieren!<b/><br>
          ";
      }
      $this->forms['information']['style'] = "red";
      $this->show('message_question', "Gruppe löschen");
    }
  }
  /**
    \brief Gruppenrechte anzeigen

    Zeigt die Rechte der Gruppen in einer Übersicht an.
   */
  function Group_rights() {
    if (!$this->userdata['rights']['groupedit']) {
      #no permission
      $this->_header("","no permission");
    }
    $this->template->assign("rank1",listGroupsbyRank(1));
    $this->template->assign("rank2",listGroupsbyRank(2));
    $this->template->assign("rank3",listGroupsbyRank(3));
    $this->show('group_rights',"Gruppenrechte");
  }
  /**
   * Ändert die rechte eienr Gruppe
   */
  function Group_rights_edit() {
    if (!$this->userdata['rights']['groupedit']) {
      #no permission
      $this->_header("","no permission");
    }
    $rank = param_num("rank",1);
    $gid = param_num("id");
    $grouplist = getGroupList();
    if (!$grouplist) $this->_header("","Keine Gruppen vorhanden");
    if ($gid) {
      $hasfound = false;
      for($i=0;$i < count($grouplist);$i++){
        if ($gid == $grouplist[$i]['gid']) {
          $hasfound = true;
          $grouplist[$i]['selected'] = "selected";
          break;
        }
      }
      if (!$hasfound) $this->_header("","Ungültige gid");
    } else {
      $grouplist[0]['selected'] = "selected";
      $gid = $grouplist[0]['gid'];
    }
    $rightslist = listRightsbyGroup($gid);
    if (!$rightslist) $this->_header("","Keine Rechte vorhanden");
    #formular abgeschickt
    if ($_POST{'send'} && $_POST['next_x']) {
      for($i=0;$i < count($rightslist);$i++){
        $right = &$rightslist[$i];
        #nicht gesetzt und maxlevel überschritten
        if ($rank > $right['maxrank'] && !$right['isset']) {
          unset($rightslist[$i]['rid']);
          continue;
        }
        if ($right['isset'] && $right['rank'] < $rank) {
          $right['disabled'] = "disabled";
          $right['vererbt'] = "(vererbt)";
          if ($right['isset']) {
            $right['isset'] = "checked";
          } else {
            $right['notset'] = "checked";
            unset($right['isset']);
          }
          continue;
        }
        if ($_POST["r_".$right['rid']]) {
          $right['isset'] = "checked";
          $ids[] = $right['rid'];
        } else {
          $right['notset'] = "checked";
          unset($right['isset']);
        }
      }
      updateRights($gid,$rank,$ids);
    } else {
      for($i=0;$i < count($rightslist);$i++){
        $right = &$rightslist[$i];
        #nicht gesetzt und maxlevel überschritten
        if ($rank > $right['maxrank'] && !$right['isset']) {
          unset($rightslist[$i]['rid']);
#          for($j=$i;$j < count($rightslist)-1;$j++){
#            $rightslist[$j]= $rightslist[$j+1];
#          }
          continue;
        }
        if ($right['isset'] && $right['rank'] < $rank) {
          $right['disabled'] = "disabled";
          $right['vererbt'] = "(vererbt)";
        }
        if ($right['isset'] && $right['rank'] <= $rank) {
          $right['isset'] = "checked";
        } else {
          $right['notset'] = "checked";
          unset($right['isset']);
        }
      }
    }
    switch ($rank) {
       case 1: $ebene = "(Global)";
         break;
       case 2: $ebene = "(Allianzebene)";
         break;
       case 3: $ebene = "(Galaxieebene)";
         break;
    }
#    for($i=0;$i < count($rightslist);$i++){
#      echo "rid: ".$rightslist[$i]['rid']."<br>";
#      echo "isset: ".$rightslist[$i]['isset']."<br>";
#      echo "rank: ".$rightslist[$i]['rank']."<br>";
#      echo "descr: ".$rightslist[$i]['descr']."<br>";
#    }
    $this->template->assign("ebene",$ebene);
    $this->template->assign("grouplist",$grouplist);
    $this->template->assign("rightslist",$rightslist);
    $this->template->assign("rank",$rank);
    $this->template->assign("url","admin.php");
    $this->template->assign("action","editgrouprights");
    $this->show('group_editrights',"Gruppenrechte bearbeiten");
  }
  /**
    \brief Allianzen anzeigen

    Listet die Allianzen des TCs auf
   */
  function Ally_list() {
    #check rights
    if (!$this->_checkUserRights(array("addally","editally","deleteally"))){
      #no permission
      $this->_header("","no permission");
    }
    $allylistfilter = $_SESSION['allylistfilter'];
    $page = param_num("page",1);
    $rows = 6;
    if (($_REQUEST['sort'] && $_REQUEST['order']) || !$allylistfilter) {
      $sort = trim($_REQUEST['sort']);
      $order = trim($_REQUEST['order']);
      if ($sort != 'name' && $sort != 'tag' && $sort != 'member') $sort = 'tag';
      if ($order != "asc" && $order != "desc") $order = "asc";
      $allylistfilter['sort'] = $sort;
      $allylistfilter['order'] = $order;
      $_SESSION['allylistfilter'] = $allylistfilter;
    }
    $this->forms['list'][$allylistfilter['sort']][$allylistfilter['order']] = '_active';
    $allylist = listAllys($allylistfilter,&$pages,&$page,$rows);
    $this->forms['list']['pages'] = showPageBar($page,$pages,"admin.php?action=ally","page","menu");
    $this->template->assign("allylist",$allylist);
    $this->show('ally_list',"Allianzen");
  }
  /**
    \brief Allianzinfos

    Zeigt Informationen zu einer Allianz an
   */
  function Ally_details() {
    #check rights
    if (!$this->_checkUserRights(array("addally","editally","deleteally"))){
      #no permission
      $this->_header("","no permission");
    }
    $id = param_num("id");
    if (!$id) $this->_header("","Fehlende allyid!");
    $ally = getAlly($id);
    if (!$ally) $this->_header("","Ungültige allyid!");
    $ally[id]= $id;
    if ($ally['url']) $ally['url'] = formaturl($ally['url']);
    #can edit
    if ($this->userdata['rights']['editally']['rank'] == 1 ||
        ($this->userdata['rights']['editally']['rank'] == 2 &&
         $this->userdata['aid'] == $ally['aid']))
    {
      $this->template->assign("editlink","admin.php?action=editally&id=$id");
    }
    #can delete
    if ($this->userdata['rights']['deleteally']['rank'] == 1 ||
        ($this->userdata['rights']['deleteally']['rank'] == 2 &&
         $this->userdata['aid'] == $ally['aid']))
    {
      $this->template->assign("deletelink","admin.php?action=deleteally&id=$id");
    }
    $this->template->assign("ally",$ally);
    $this->show('ally_details',"Allianzdetails");
  }
  /**
    \brief Allianz hinzufügen

    Fügt eine Allianz hinzu
   */
  function Ally_add() {
    #check rgihts
    if ($this->userdata['rights']['addally']['rank'] != 1)
    {
      #no permission
      $this->_header("","no permission");
    }
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['addally']) {
      $id = param_num("id");
      if ($id) {
        $return = getAlly($id);
      }
      if (!$return) $this->_header();
      #save step
      unset($data['addally']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['action'] = "allydetails";
      $this->forms['information']['url'] = "admin.php?action=allydetails&id=".$return['aid']."&force";
      $this->forms['information']['title'] = "Allianz hinzufügen";
      $this->forms['information']['message'] = "Allianz <b>".$return['name']."</b> hinzugefügt";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Allianz hinzufügen");
    }
    #formular send
    if ($_REQUEST['send']) {
      $name = param_str("name",true);
      $tag = param_str("tag",true);
      $irc = param_str("irc",true);
      $url = param_str("url",true);
      $descr = param_str("descr",true);
      $errors = false;
      #check if empty
      if (!$name) {
        $errors[] = "Name darf nicht leer sein!";
        $items['name']['class'] = '_error';
      }
      if (!$tag) {
        $errors[] = "Tag darf nicht leer sein!";
        $items['tag']['class'] = '_error';
      }
      if (!$errors && getAllyByTag($tag)) {
        $errors[] = "Allianz mit diesem Tag existiert bereits!";
        $items['tag']['class'] = '_error';
      }
      if (!$errors) {
        #save step
        $data['addally'] = 1;
        $_SESSION['steps'] = $data;
        $id = addAlly($name,$tag,$descr,$url,$irc);
        if ($id) {
          addToLogfile("Allianz ".$name." hinzugefügt","Admin",$this->userdata['uid']);
          $this->_header("admin.php?action=addally&id=".$id."&send");
        }
      } else {
        $items['descr']['value'] = $descr;
        $items['tag']['value'] = $tag;
        $items['name']['value'] = $name;
        $items['irc']['value'] = $irc;
        $items['url']['value'] = $url;
        $this->template->assign("items",$items);
        $this->template->assign("errors",$errors);
      }
    }
    $this->show('ally_add',"Allianz hinzufügen");
  }
  /**
    \brief Allianz bearbeiten

    Ändert die Daten einer Allianz
   */
  function Ally_edit() {
    $page = param_num("page",1);
    $data = $_SESSION['steps'];
    $id = param_num("id");
    if ($id) {
      $return = getAlly($id);
    }
    if (!$return) $this->_header("","Ungültige oder fehlende Allyid!");
    #check rgihts
    if ($this->userdata['rights']['editally']['rank'] != 1 &&
        ($this->userdata['rights']['editally']['rank'] != 2 ||
         $this->userdata['aid'] != $id))
    {
      #no permission
      $this->_header("","no permission");
    }
    #information message, step 2
    if ($data['editally']) {
      #save step
      unset($data['editally']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['action'] = "allydetails";
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Allianz bearbeiten";
      $this->forms['information']['message'] = "Daten der Allianz <b>".$return['name']."</b> geändert";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Allianz bearbeiten");
    }
    #formular send
    if ($_REQUEST['send']) {
      $name = param_str("name",true);
      $tag = param_str("tag",true);
      $irc = param_str("irc",true);
      $url = param_str("url",true);
      $descr = param_str("descr",true);
      $errors = false;
      #check if empty
      if (!$name) {
        $errors[] = "Name darf nicht leer sein!";
        $items['name']['class'] = '_error';
      }
      if (!$tag) {
        $errors[] = "Tag darf nicht leer sein!";
        $items['tag']['class'] = '_error';
      }
      if (!$errors && $tag != $return['tag'] && getAllyByTag($tag)) {
        $errors[] = "Allianz mit diesem Tag existiert bereits!";
        $items['tag']['class'] = '_error';
      }
      if (!$errors) {
        #save step
        $data['editally'] = 1;
        $_SESSION['steps'] = $data;
        updateAlly($id,$name,$tag,$descr,$url,$irc);
        addToLogfile("Allianz ".$name." bearbeitet","Admin",$this->userdata['uid']);
        $this->_header("admin.php?action=editally&id=".$id."&send");
      } else {
        $items['descr']['value'] = $descr;
        $items['tag']['value'] = $tag;
        $items['name']['value'] = $name;
        $items['irc']['value'] = $irc;
        $items['url']['value'] = $url;
        $this->template->assign("errors",$errors);
      }
    } else {
        $items['descr']['value'] = $return['descr'];
        $items['tag']['value'] = $return['tag'];
        $items['name']['value'] = $return['name'];
        $items['irc']['value'] = $return['irc'];
        $items['url']['value'] = $return['url'];
    }
    $this->template->assign("id",$id);
    $this->template->assign("items",$items);
    $this->show('ally_edit',"Allianz bearbeiten");
  }
  /**
    \brief Galaxie hinzufügen

    Fügt eine Galaxie hinzu.
   */
  function Galaxy_add() {
    #check rights
    $rank = $this->userdata['rights']['addgala']['rank'];
    if ($rank != 1 && $rank != 2){
      #no permission
      $this->_header("","no permission");
    }
    $page = param_num("page",1);
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['addgala']) {
      $id = param_num("id");
      if ($id) {
        $return = getGala($id);
      }
      if (!$return) $this->_header();
      #save step
      unset($data['addgala']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['action'] = "galadetails";
      $this->forms['information']['url'] = "admin.php?id=".$return['gala']."&force";
      $this->forms['information']['title'] = "Galaxie hinzufügen";
      $this->forms['information']['message'] = "Galaxie <b>".$return['gala']." (".$return['tag'].")</b> hinzugefügt";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Galaxie hinzufügen");
    }
    if ($rank == 1) {
      #global
      $allylist = getAllyList();
    } else {
      #allianz
      $this->template->assign("ally",$this->userdata['tag']);
    }
    $this->template->assign("rank",$rank);
    #formular send
    if ($_REQUEST['send']) {
      $gala = param_num("gala");
      $errors = false;
      #check if empty
      if (!$gala) {
        $errors[] = "Galaxie darf nicht leer sein!";
        $items['gala']['class'] = '_error';
      }
      if (!$errors && getGala($gala)) {
        $errors[] = "Galaxie existiert bereits!";
        $items['gala']['class'] = '_error';
      }
      if ($rank == 1) {
        $allyid = param_num("allyid",true);
        if ($allyid){
          for($i=0;$i < count($allylist);$i++){
            if ($allylist[$i]['aid'] == $allyid) {
              $ally = &$allylist[$i];
              $ally['selected'] = "selected";
              break;
            }
          }
        }
        if (!$ally) $this->_header("","Ungültige oder fehlende Allyid");
      } else {
        $allyid = $this->userdata['aid'];
      }
      if (!$errors) {
        #save step
        $data['addgala'] = 1;
        $_SESSION['steps'] = $data;
        addGala($gala,$allyid);
        addToLogfile("Galaxie ".$gala." hinzugefügt","Admin",$this->userdata['uid']);
        $this->_header("admin.php?action=addgala&id=".$gala."&send");
      } else {
        $items['gala']['value'] = $gala;
        $this->template->assign("items",$items);
        $this->template->assign("errors",$errors);
      }
    }
    $this->template->assign("allylist",$allylist);
    $this->show('gala_add',"Galaxie hinzufügen");
  }
  /**
    \brief Galaxien anzeigen

    Listet die Galaxien auf, nach Allianzen sortiert
    je nach Rechten des Users.
   */
  function Galaxy_list() {
    #check rights
    $rank = $this->_getMinRightRank(array("addgala","editgala","deletegala"));
    if (!$rank){
      #no permission
      $this->_header("","no permission");
    }
    $galaxylistfilter = $_SESSION['galaxylistfilter'];
    $page = param_num("page",1);
    $rows = 12;
    #allianzebene & galaxieebene
    if ($rank > 1) {
      $galaxylistfilter['ally'] = $this->userdata['aid'];
    }
    if (($_REQUEST['sort'] && $_REQUEST['order']) || !$galaxylistfilter) {
      $sort = trim($_REQUEST['sort']);
      $order = trim($_REQUEST['order']);
      if ($sort != 'gala' && $sort != 'tag' && $sort != 'member') $sort = 'gala';
      if ($order != "asc" && $order != "desc") $order = "asc";
      $galaxylistfilter['sort'] = $sort;
      $galaxylistfilter['order'] = $order;
      $_SESSION['galaxylistfilter'] = $galaxylistfilter;
    }
    $this->forms['list'][$galaxylistfilter['sort']][$galaxylistfilter['order']] = '_active';
    $galaxylist = listGalaxys($galaxylistfilter,&$pages,&$page,$rows);
    $this->forms['list']['pages'] = showPageBar($page,$pages,"admin.php?action=galaxy","page","menu");
    $this->template->assign("galaxylist",$galaxylist);
    $this->show('gala_list',"Galaxien");
  }
  /**
    \brief Galaxieinformationen

    Zeigt Informationen zu einer Galaxie
   */
  function Galaxy_details() {
    #check rights
    $rank = $this->_getMinRightRank(array("addgala","editgala","deletegala"));
    if (!$rank){
      #no permission
      $this->_header("","no permission");
    }
    $id = param_num("id");
    if (!$id) $this->_header("","Fehlende galaid!");
    $galaxy = getGala($id);
    if (!$galaxy) $this->_header("","Ungültige galaid!");
    #allianz & galaxieebene
    if ($rank > 1 && $galaxy['aid'] != $this->userdata['aid']) {
      #no permission
      $this->_header("","no permission");
    }
    if ($this->userdata['rights']['editgala']['rank'] == 1 ||
        ($this->userdata['rights']['editgala']['rank'] == 2 &&
         $this->userdata['aid'] == $galaxy['aid']) ||
        ($this->userdata['rights']['editgala']['rank'] == 3 &&
         $this->userdata['gala'] == $galaxy['gala']))
    {
      $this->template->assign("editlink","admin.php?action=editgala&id=$id");
    }
    $this->template->assign("searchlink","admin.php");
    if ($this->userdata['rights']['deletegala']['rank'] == 1 ||
        ($this->userdata['rights']['deletegala']['rank'] == 2 &&
         $this->userdata['aid'] == $galaxy['aid']))
    {
      $this->template->assign("deletelink","admin.php?action=deletegala&id=$id");
    }
    $this->template->assign("galaxy",$galaxy);
    $this->show('gala_details',"Galaxiedetails");
  }
  /**
    \brief Galaxie hinzugügen

    Fügt eine Galaxie hinzu
   */
  function Galaxy_edit() {
    $page = param_num("page",1);
    $data = $_SESSION['steps'];
    $id = param_num("id");
    if ($id) {
      $return = getGala($id);
    }
    if (!$return) $this->_header("","ungültige id");
    #check rights
    $rank = $this->userdata['rights']['editgala']['rank'];
    if (!$rank ||
        ($rank > 1 && $return['aid'] != $this->userdata['aid']) ||
        ($rank > 2 && $return['gala'] != $this->userdata['gala'])){
      #no permission
      $this->_header("","no permission");
    }
    #information message, step 2
    if ($data['editgala']) {
      #save step
      unset($data['editgala']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Galaxie bearbeiten";
      $this->forms['information']['message'] = "Galaxie <b>".$return['gala']."</b> bearbeitet";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Galaxie bearbeitet");
    }
    if ($rank > 1) {
      #allianz
      $this->template->assign("ally",$this->userdata['tag']);
    } else {
      $allylist = getAllyList();
    }
    $this->template->assign("rank",$rank);
    #formular send
    if ($_REQUEST['send']) {
      $gala = param_num("gala");
      $errors = false;
      #check if empty
      if (!$gala) {
        $errors[] = "Galaxie darf nicht leer sein!";
        $items['gala']['class'] = '_error';
      }
      if (!$errors && $return['gala'] != $gala && getGala($gala)) {
        $errors[] = "Galaxie existiert bereits!";
        $items['gala']['class'] = '_error';
      }
      if ($rank == 1) {
        $allyid = param_num("allyid",true);
        if ($allyid){
          for($i=0;$i < count($allylist);$i++){
            if ($allylist[$i]['aid'] == $allyid) {
              $ally = &$allylist[$i];
              $ally['selected'] = "selected";
              break;
            }
          }
        }
        if (!$ally) $this->_header("","Ungültige oder fehlende Allyid");
      } else {
        $allyid = $this->userdata['aid'];
      }
      if (!$errors) {
        #save step
        $data['editgala'] = 1;
        $_SESSION['steps'] = $data;
        updateGala($id,$gala,$allyid);
        addToLogfile("Galaxie ".$gala." bearbeitet","Admin",$this->userdata['uid']);
        $this->_header("admin.php?action=editgala&id=".$gala."&send");
      } else {
        $items['gala']['value'] = $gala;
        $this->template->assign("errors",$errors);
      }
    } else {
      $items['gala']['value'] = $return['gala'];
      for($i=0;$i < count($allylist);$i++){
        if ($allylist[$i]['aid'] == $return['aid']) {
          $ally = &$allylist[$i];
          $ally['selected'] = "selected";
          break;
        }
      }
    }
    $this->template->assign("items",$items);
    $this->template->assign("id",$id);
    $this->template->assign("allylist",$allylist);
    $this->show('gala_edit',"Galaxie bearbeiten");
  }
  /**
    \brief Allianz löschen

    Löscht eine Allianz mit allen Mitgliedern und Galaxien
   */
  function Ally_delete() {
    #check rgihts
    if ($this->userdata['rights']['deleteally']['rank'] != 1)
    {
      #no permission
      $this->_header("","no permission");
    }
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['deleteally']) {
      #save step
      unset($data['deleteally']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Allianz löschen";
      $this->forms['information']['message'] = "Allianz erfolgreich gelöscht";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Allianz löschen");
    }
    $id = param_num("id");
    if (!$id) $this->_header();
    $return = getAlly($id);
    if (!$return) $this->_header();
    #deleteally, send
    if ($_REQUEST['send']) {
      if ($_REQUEST['yes_x']) {
        addToLogfile("Allianz <b>".$return['name']."</b> gelöscht","Admin",$this->userdata['uid']);
        deleteAlly($return['aid']);
        #save step
        $data['deleteally'] = 1;
        $_SESSION['steps'] = $data;
        $this->_header("admin.php?action=deleteally&send");
      } else {
        $this->_header();
      }
    } else {
      $this->forms['information']['url'] = "admin.php?id=".$return['aid'];
      $this->forms['information']['action'] = "deleteally";
      $this->forms['information']['title'] = "Allianz löschen";
      $this->forms['information']['message'] = "Allianz <b>".$return['name']." (".$return['tag'].")</b> löschen ?";
      if ($return['galas']) {
        if ($return['galas'] == 1) {
          $this->forms['information']['message'] .= "
          <br><br><b>".$return['galas']."</b> Galaxie wird mitgelöscht.";
        } else {
          $this->forms['information']['message'] .= "
          <br><br><b>".$return['galas']."</b> Galaxien werden mitgelöscht.
          ";
        }
        if ($return['member']) {
          if ($return['member'] == 1) {
            $s = "wird";
          } else {
            $s = "werden";
          }
          $this->forms['information']['message'] .= "
          <br>Es $s <b>".$return['member']."</b> User gelöscht !
          ";
        }
      }
      if ($return['aid'] == $this->userdata['aid']) {
          $this->forms['information']['message'] .= "
          <br><br><b>WARNUNG!!</b><br>
          <b>Sie sind im Begriff ihre eigene Allianz (und damit sich selbst) zu löschen,<br>
          Sie können sich danach nicht mehr einloggen!<b/><br>
          ";
      }
      $this->forms['information']['style'] = "red";
      $this->show('message_question', "Allianz löschen");
    }
  }
  /**
    \brief Galaxie löschen

    Löscht eine Galaxie
   */
  function Galaxy_delete() {
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['deletegala']) {
      #save step
      unset($data['deletegala']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Galaxie löschen";
      $this->forms['information']['message'] = "Galaxie erfolgreich gelöscht";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Galaxie löschen");
    }
    $id = param_num("id");
    if (!$id) $this->_header();
    $return = getGala($id);
    if (!$return) $this->_header();
    #check rights
    $rank = $this->userdata['rights']['deletegala']['rank'];
    if (!$rank || ($rank > 2) ||
        ($rank == 2 && $return['aid'] != $this->userdata['aid'])){
      #no permission
      $this->_header("","no permission");
    }
    #deletegala, send
    if ($_REQUEST['send']) {
      if ($_REQUEST['yes_x']) {
        addToLogfile("Galaxie <b>".$return['gala']."</b> gelöscht","Admin",$this->userdata['uid']);
        deletegalaxy($return['gala']);
        #save step
        $data['deletegala'] = 1;
        $_SESSION['steps'] = $data;
        $this->_header("admin.php?action=deletegala&send");
      } else {
        $this->_header();
      }
    } else {
      $this->forms['information']['url'] = "admin.php?id=".$return['gala'];
      $this->forms['information']['action'] = "deletegala";
      $this->forms['information']['title'] = "Galaxie löschen";
      $this->forms['information']['message'] = "Galaxie <b>".$return['gala']." (".$return['tag'].")</b> löschen ?";
      if ($return['member']) {
        if ($return['member'] == 1) {
          $s = "wird";
        } else {
          $s = "werden";
        }
        $this->forms['information']['message'] .= "
        <br><br>Es $s <b>".$return['member']."</b> User gelöscht !
        ";
      }
      if ($return['gala'] == $this->userdata['gala']) {
          $this->forms['information']['message'] .= "
          <br><br><b>WARNUNG!!</b><br>
          <b>Sie sind im Begriff ihre eigene Galaxie (und damit sich selbst) zu löschen,<br>
          Sie können sich danach nicht mehr einloggen!<b/><br>
          ";
      }
      $this->forms['information']['style'] = "red";
      $this->show('message_question', "Galaxie löschen");
    }
  }
  /**
    \brief IRCBots auflisten

    Listet die IRCBots auf
   */
  function Bot_list()
  {
    if (!$this->userdata['rights']['bots']) {
      #no permission
      $this->_header("","no permission");
    }
    $list = bot_list();
    for($i=0;$i < count($list);$i++){
      $list[$i]['firstauth'] = date("H:i d.m",$list[$i]['firstauth']);
      if($list[$i]['lastauth']) $list[$i]['lastauth'] = date("H:i d.m",$list[$i]['lastauth']);
    }
    $this->template->assign('list',$list);
    $this->show('bot_list','IRC Bots');
  }

  /**
    \brief IRC bot hinzufügen

    Fügt einen IRC Bot hinzu
   */
  function Bot_add() {
    if (!$this->userdata['rights']['bots']) {
      #no permission
      $this->_header("","no permission");
    }
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['addbot']) {
      #save step
      unset($data['addbot']);
      $_SESSION['steps'] = $data;
      $id = param_num("id");
      if (!$id) $this->_header();
      $bot = bot_get($id);
      if (!$bot) $this->_header();
      $this->forms['information']['url'] = "admin.php?action=bots&force";
      $this->forms['information']['title'] = "IRC Bot hinzufügen";
      $this->forms['information']['message'] = "Bot '<b>".$bot['name']."</b>' hinzugefügt";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "IRC Bot hinzufügen");
    }
    if ($_REQUEST['send']) {
      $name = param_str("name",true);
      $login = param_str("login",true);
      $password = param_str("password",true);
      $soapurl = param_str("soapurl",true);
      $host = param_str("host",true);
      $errors = false;
      #check if empty
      if (!$name) {
        $errors[] = "Name darf nicht leer sein!";
        $items['name']['class'] = '_error';
      }
      if (!$login) {
        $errors[] = "Login darf nicht leer sein!";
        $items['login']['class'] = '_error';
      }
      if (!$password) {
        $errors[] = "Passwort darf nicht leer sein!";
        $items['password']['class'] = '_error';
      }
      if (!$soapurl){
        $errors[] = "Es muss eine Adresse für den SOAP Server angegeben werden!";
        $items['soapurl']['class'] = '_error';
      }
      if (!$host){
        $items['host']['class'] = '_optional';
      }
      if (!$errors && bot_get_bylogin($login,$password)){
        $errors[] = "Diese Login - Passwort Paarung ist bereits vergeben!";
        $items['login']['class'] = '_error';
        $items['password']['class'] = '_error';
      }
      if (!$errors) {
        #save step
        $data['addbot'] = 1;
        $_SESSION['steps'] = $data;
        $id = bot_add($name,$login,$password,$soapurl,$host);
        addToLogfile("IRC Bot <b>".$name."</b> hinzugefügt","Bots",$this->userdata['uid']);
        $this->_header("admin.php?action=addbot&id=$id&send");
      } else {
        $items['login']['value'] = $login;
        $items['password']['value'] = $password;
        $items['name']['value'] = $name;
        $items['host']['value'] = $host;
        $items['soapurl']['value'] = $soapurl;
        $this->template->assign("errors",$errors);
      }
    } else {
      $items['host']['class'] = '_optional';
    }
    $this->template->assign("items",$items);
    $this->show('bot_add',"IRC Bot hinzufügen");
   }
  /**
    \brief IRC bot bearbeiten

    Ändert die Daten von einem IRC Bot
   */
  function Bot_edit() {
    if (!$this->userdata['rights']['bots']) {
      #no permission
      $this->_header("","no permission");
    }
    $id = param_num("id");
    if (!$id) $this->_header();
    $bot = bot_get($id);
    if (!$bot) $this->_header();
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['editbot']) {
      #save step
      unset($data['editbot']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "IRC Bot bearbeiten";
      $this->forms['information']['message'] = "Bot '<b>".$bot['name']."</b>' bearbeitet";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "IRC Bot bearbeiten");
    }
    if ($_REQUEST['send']) {
      $name = param_str("name",true);
      $login = param_str("login",true);
      $password = param_str("password",true);
      $soapurl = param_str("soapurl",true);
      $host = param_str("host",true);
      $errors = false;
      #check if empty
      if (!$name) {
        $errors[] = "Name darf nicht leer sein!";
        $items['name']['class'] = '_error';
      }
      if (!$login) {
        $errors[] = "Login darf nicht leer sein!";
        $items['login']['class'] = '_error';
      }
      if (!$password) {
        $errors[] = "Passwort darf nicht leer sein!";
        $items['password']['class'] = '_error';
      }
      if (!$soapurl){
        $errors[] = "Es muss eine Adresse für den SOAP Server angegeben werden!";
        $items['soapurl']['class'] = '_error';
      }
      if (!$host){
        $items['host']['class'] = '_optional';
      }
      if (!$errors && ($login != $bot['login'] || $password != $bot['password']) && bot_get_bylogin($login,$password)){
        $errors[] = "Diese Login - Passwort Paarung ist bereits vergeben!";
        $items['login']['class'] = '_error';
        $items['password']['class'] = '_error';
      }
      if (!$errors) {
        #save step
        $data['editbot'] = 1;
        $_SESSION['steps'] = $data;
        bot_update($id,$name,$login,$password,$soapurl,$host);
        addToLogfile("IRC Bot <b>".$bot['name']."</b> bearbeitet","Bots",$this->userdata['uid']);
        $this->_header("admin.php?action=editbot&id=$id&send");
      } else {
        $items['login']['value'] = $login;
        $items['password']['value'] = $password;
        $items['name']['value'] = $name;
        $items['host']['value'] = $host;
        $items['soapurl']['value'] = $soapurl;
        $this->template->assign("errors",$errors);
      }
    } else {
      $items['login']['value'] = $bot['login'];
      $items['password']['value'] = $bot['password'];
      $items['name']['value'] = $bot['name'];
      $items['host']['value'] = $bot['host'];
      $items['soapurl']['value'] = $bot['soapurl'];
      if (!$bot['host']) $items['host']['class'] = '_optional';
    }
    $this->template->assign("id",$id);
    $this->template->assign("items",$items);
    $this->show('bot_edit',"IRC Bot bearbeiten");
   }
  /**
    \brief IRC bot löschen

    Löscht einen IRC Bot
   */
  function Bot_delete() {
    if (!$this->userdata['rights']['bots']) {
      #no permission
      $this->_header("","no permission");
    }
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['deletebot']) {
      #save step
      unset($data['deletebot']);
      $_SESSION['steps'] = $data;
      $name = param_str("name");
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "IRC Bot löschen";
      $this->forms['information']['message'] = "Bot '$name' erfolgreich gelöscht";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "IRC Bot löschen");
    }
    $id = param_num("id");
    if (!$id) $this->_header();
    $bot = bot_get($id);
    if (!$bot) $this->_header();
    #deletenews, send
    if ($_REQUEST['send']) {
      if ($_REQUEST['yes_x']) {
        addToLogfile("Bot <b>".$bot['name']."</b> gelöscht","Bots",
          $this->userdata['uid']);
        bot_delete($id);
        #save step
        $data['deletebot'] = 1;
        $_SESSION['steps'] = $data;
        $this->_header("admin.php?action=deletebot&name=".$bot['name']."&send");
      } else {
        $this->_header();
      }
    } else {
      $this->forms['information']['url'] = "admin.php?id=".$bot['botid'];
      $this->forms['information']['action'] = "deletebot";
      $this->forms['information']['title'] = "IRC Bot löschen";
      $this->forms['information']['message'] = "Bot '<b>".$bot['name']."</b>' löschen ?";
      $this->forms['information']['style'] = "red";
      $this->show('message_question', "IRC Bot löschen");
    }
  }
}
?>