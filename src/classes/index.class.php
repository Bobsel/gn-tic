<?php

require_once('classes/kibo.page.class.php');

require_once('functions/bbcode.php');

require_once('database/db.news.php');
require_once('database/db.ally.php');

class indexpage extends kibopage  {

  #
  # Eventhandler
  #
  function run () {
    
    parent::run();
    
    #functionhash
    $functions["members"] = "_Member_list()";
    $functions["addnews"] = "_News_add()";
    $functions["editnews"] = "_News_edit()";
    $functions["deletenews"] = "_News_delete()";
    $functions["comments"] = "_Comment_list()";
    $functions["deletecom"] = "_Comment_delete()";
    $functions["addcom"] = "_Comment_add()";
    $functions["scanner"] = "Scanner_list()";
    $functions["highscore"] = "Highscore_list()";

    #handle action
    if ($functions[$this->action]) {
      eval("\$this->".$functions[$this->action].";");
    }
    #default
    $this->_News_list();
  }

  function highscore_list() {
    $page = param_num("page",1);
    $show = param_str("show");
    if($show == "day") {
      $highscore['list'] = highscore_list(array("hours"=>24),&$pages,&$page);
      $highscore['title'] = "Meisten Punkte in den letzten 24 Stunden";
    } elseif($show =="week") {
      $highscore['list'] = highscore_list(array("days"=>7),&$pages,&$page);
      $highscore['title'] = "Meisten Punkte in der letzten Woche";
    } else {
      $highscore['list'] = highscore_list(null,&$pages,&$page);
      $highscore['title'] = "Komplette Übersicht";
    }
    $this->template->assign('pages',showPageBar($page,$pages,"index.php?action=highscore&show=$show","page","menu"));
    $this->template->assign('page',$page);
    $this->template->assign('highscore',$highscore);
    $this->show('highscore_index','Highscoreliste');
  }

  function Scanner_list() {
    $this->template->assign('mililist',user_get_scannerlist(1,10));
    $this->template->assign('newslist',user_get_scannerlist(0,10));
    $this->show('scanner_list','Nachrichten');
  }

  #
  # Newsliste
  #
  function _News_list() {
    $page = param_num("page",1);
    $newslist = listNews(&$pages,&$page);
    for ($i=0;$i<count($newslist);$i++) {
      $newslist[$i]['content'] = formatTextData($newslist[$i]['content']);
      if ($newslist[$i]['comments'] == 0) {
        $newslist[$i]['commenttitle'] = "keine Kommentare vorhanden";
      } elseif ($newslist[$i]['comments'] == 1) {
        $newslist[$i]['commenttitle'] = "1 Kommentar";
        $newslist[$i]['blink'] = "_blink";
      } else {
        $newslist[$i]['commenttitle'] = $newslist[$i]['comments']." Kommentare";
        $newslist[$i]['blink'] = "_blink";
      }
    }
    if ($this->userdata['rights']['news']) {
      $this->forms['newslist']['canedit'] = 1;
    }
    $this->forms['newslist']['pages'] = showPageBar($page,$pages,"index.php","page","menu");
    $this->template->assign('page',$page);
    $this->template->assign('newslist',$newslist);
    $this->template->assign('title','Nachrichten');
    $this->show('news_list','Nachrichten');
  }

  #
  # listet die Member auf
  #
  function _Member_list() {
    $page = param_num("page",1);
    $rows = 12;

    $filter = $_SESSION['memberfilter'];
    if (!$filter){
      $filter['sort'] = "koords";
      $filter['order'] = "asc";
      $filter['page'] = 1;
      $_SESSION['memberfilter'] = $filter;
    }
    if ($_REQUEST['sort'] && $_REQUEST['order']) {
      $filter['sort'] = $_REQUEST['sort'];
      $filter['order'] = $_REQUEST['order'];
      $_SESSION['memberfilter'] = $filter;
    }
    if($_POST['send']) {
      if ($_POST['ally'] && is_numeric($_POST['ally'])
        && getAlly($_POST['ally'])) {
        $filter['ally'] = $_POST['ally'];
      } else {
        unset($filter['ally']);
      }
      $_SESSION['memberfilter'] = $filter;
    }
    $sort[$filter['sort']][$filter['order']] = '_active';

    $allylist = getAllyList();
    if ($filter['ally']) {
      for($i=0;$i < count($allylist);$i++){
        if ($allylist[$i]['aid'] == $filter['ally']) {
          $allylist[$i]['selected'] = "selected";
          break;
        }
      }
    } else {
      $this->template->assign("ally0","selected");
    }


    $userlist = listUser($filter,&$pages,&$page,$rows);
    $this->template->assign("pages",showPageBar($page,$pages,"index.php?action=members","page","menu"));
    $setback = "index.php?action=members&page=".$page."&".$this->session['link'];
    $_SESSION['setback'] = $setback;
    $this->template->assign("sort",$sort);
    $this->template->assign('userlist',$userlist);
    $this->template->assign('allylist',$allylist);
    $this->show('member_list','Memberliste');
  }

  #
  # fügt neue Nachrichten hinzu
  #
  function _News_add() {
    if (!$this->userdata['rights']['news']) {
      $this->_header("index.php");
    }
    $data = $_SESSION['steps'];
    $page = param_num("page",1);
    #information message, step 2
    if ($data['addnews']) {
      #save step
      unset($data['addnews']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Nachrichten hinzufügen";
      $this->forms['information']['message'] = "Nachricht hinzugefügt";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Nachrichten hinzufügen");
    }
    #addnews, step 1
    #formular send
    if ($_REQUEST['send']) {
      if ($_REQUEST['next_x']) {
        $items['title'] = param_str("title",true);
        $items['message'] = param_str("message",true);
        $errors = false;
        #check if empty
        foreach ( $items as $key => $value) {
          if (!$value) {
            $this->forms['addnews']['fields'][$key]['error'] = 'Feld darf nicht leer sein!';
            $this->forms['addnews']['fields'][$key]['bgrd'] = '_error';
            $errors = true;
          } else {
            $this->forms['addnews']['fields'][$key]['value'] = $value;
          }
        }
        if (!$errors) {
          #save step
          $data['addnews'] = 1;
          $_SESSION['steps'] = $data;
          $id = addnews($items['title'],editpostdata($items['message']),$this->userdata['uid']);
          addToLogfile("Nachricht <b>".$items['title']."</b> hinzugefügt",
              "News",$this->userdata['uid']);
          $this->_header("index.php?action=addnews&send");
        }
      } else {
        $this->_header("index.php?page=".$page);
      }
    }
    $this->forms['addnews']['name'] = $return['name'];
    $this->forms['addnews']['url'] = "index.php";
    $this->forms['addnews']['action'] = 'addnews';
    $this->template->assign("backlink",$this->backtracking->backlink());
    $this->show('news_add_form',"Nachricht hinzufügen");
  }
  #
  # bearbeitet eine Nachricht
  #
  function _News_edit() {
    if (!$this->userdata['rights']['news']) {
      $this->_header("index.php");
    }
    $id = param_num("id");
    if (!$id) $this->_header("index.php");
    $newsdata = getNews($id);
    if (!$newsdata) $this->_header("index.php");
    $page = param_num("page",1);
    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['editnews']) {
      #save step
      unset($data['editnews']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Nachrichten bearbeiten";
      $this->forms['information']['message'] = "Nachricht erfolgreich bearbeitet";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Nachrichten bearbeiten");
    }
    #editnews, step 1
    #formular send
    if ($_REQUEST['send']) {
      if ($_REQUEST['next_x']) {
        $items['title'] = param_str("title",true);
        $items['message'] = param_str("message",true);
        $errors = false;
        #check if empty
        foreach ( $items as $key => $value) {
          if (!$value) {
            $this->forms['editnews']['fields'][$key]['error'] = 'Feld darf nicht leer sein!';
            $this->forms['editnews']['fields'][$key]['bgrd'] = '_error';
            $errors = true;
          } else {
            $this->forms['editnews']['fields'][$key]['value'] = $value;
          }
        }
        #optional parameters
        if (!$errors) {
          #save step
          $data['editnews'] = 1;
          $_SESSION['steps'] = $data;
          addToLogfile("Nachricht <b>".$newsdata['title']."</b> bearbeitet",
            "News",$this->userdata['uid']);
          updatenews($newsdata['nid'],$items['title'],
            editpostdata($items['message']),$this->userdata['uid']);
          $this->_header("index.php?action=editnews&id=".$newsdata['nid']."&send");
        }
      }
    } else {
      $this->forms['editnews']['fields']['message']['value'] = editdbdata($newsdata['content']);
      $this->forms['editnews']['fields']['title']['value'] = editdbdata($newsdata['title']);
    }
    $this->forms['editnews']['url'] = "index.php?id=".$newsdata['nid'];
    $this->forms['editnews']['action'] = 'editnews';
    $this->show('news_edit_form',"Nachricht bearbeiten");
  }
  #
  # löscht eine Nachricht
  #
  function _News_delete() {
    if (!$this->userdata['rights']['news']) {
      $this->_header("index.php");
    }
    $data = $_SESSION['steps'];
    $page = param_num("page",1);
    #information message, step 2
    if ($data['deletenews']) {
      #save step
      unset($data['deletenews']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Nachrichten löschen";
      $this->forms['information']['message'] = "Nachricht erfolgreich gelöscht";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Nachrichten löschen");
    }
    $id = param_num("id");
    if (!$id) $this->_header("index.php");
    $return = getNews($id);
    if (!$return) $this->_header("index.php");
    #deletenews, send
    if ($_REQUEST['send']) {
      if ($_REQUEST['yes_x']) {
        addToLogfile("Nachricht <b>".$return['title']."</b> gelöscht","News",
          $this->userdata['uid']);
        deletenews($return['nid']);
        #save step
        $data['deletenews'] = 1;
        $_SESSION['steps'] = $data;
        $this->_header("index.php?action=deletenews&send");
      } else {
        $this->_header($this->backtracking->backlink());
      }
    } else {
      $this->forms['information']['url'] = "index.php?id=".$return['nid']."&page=".$page;
      $this->forms['information']['action'] = "deletenews";
      $this->forms['information']['title'] = "Nachricht löschen";
      $this->forms['information']['message'] = "Nachricht <b>".$return['title']."</b> löschen ?";
      $this->forms['information']['style'] = "red";
      $this->show('message_question', "Nachricht löschen");
    }
  }
  #
  # list Comments
  #
  function _Comment_list() {
    $id = param_num("id");
    if (!$id) $this->_header("index.php");
    $newsdata = getNews($id);
    if (!$newsdata) $this->_header("index.php");
    $newsdata['content'] = formatTextData($newsdata['content']);

    $page = param_num("page",1);
    $comrows = 6;
    #commentlist
    $comlist = listNewsComments($id,&$pages,&$page,$comrows);
    for ($i=0;$i<count($comlist);$i++) {
      $comlist[$i]['content'] = formatTextData(($comlist[$i]['content']));
      $comlist[$i]['cdate'] = formatdate("d.m.y",$comlist[$i]['cdate']);
    }
    #admin mode
    if ($this->userdata['rights']['news']) {
      $this->template->assign('canedit',1);
    }
    $this->forms['comlist']['pages'] =
        showPageBar($page,$pages,"index.php?action=comments&id=".$id
            ,"page","menu");

    $this->template->assign('comlist',$comlist);
    $this->template->assign('news',$newsdata);
    $this->template->assign('page',$page);
    $this->show('com_list', "Kommentare");
  }

  #
  # add Comment
  #
  function _Comment_add() {
    $nid = param_num("nid");
    if (!$nid) $this->_header("index.php");
    $newsdata = getNews($nid);
    if (!$newsdata) $this->_header("index.php");

    $comrows = 6;

    $data = $_SESSION['steps'];
    #information message, step 2
    if ($data['addcom']) {
      #save step
      unset($data['addcom']);
      $_SESSION['steps'] = $data;
      $pages = getNewsCommentsPages($nid,$comrows);
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['title'] = "Kommentar hinzufügen";
      $this->forms['information']['message'] = "Kommentar hinzugefügt";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Kommentar hinzufügen");
    }
    #formular send
    if ($_REQUEST['send']) {
      if ($_REQUEST['next_x']) {
        $items['message'] = param_str("message",true);
        $errors = false;
        #check if empty
        foreach ( $items as $key => $value) {
          if (!$value) {
            $this->forms['addcom']['fields'][$key]['error'] = 'Feld darf nicht leer sein!';
            $this->forms['addcom']['fields'][$key]['bgrd'] = '_error';
            $errors = true;
          } else {
            $this->forms['addcom']['fields'][$key]['value'] = $value;
          }
        }
        #optional parameters
        if (!$errors) {
          #save step
          $data['addcom'] = 1;
          $_SESSION['steps'] = $data;
          $id = addcom($nid,editPostdata($items['message']),$this->userdata['uid']);
          addToLogfile("Kommentar zu News <b>".$newsdata['title'].
            "</b> hinzugefügt","News",$this->userdata['uid']);
          $this->_header("index.php?action=addcom&nid=".$nid."&send");
        }
      } else {
        $this->_header($this->backtracking->backlink());
      }
    }
    $this->forms['addcom']['name'] = $return['name'];
    $this->forms['addcom']['url'] = "index.php?nid=".$nid;
    $this->forms['addcom']['action'] = 'addcom';
    $this->show('com_add_form',"Kommentar hinzufügen");
  }

  #
  # delete Comment
  #
  function _Comment_delete() {
    if (!$this->userdata['rights']['news']) {
      $this->_header("index.php");
    }
    $data = $_SESSION['steps'];
    $nid = param_num("nid");
    $cid = param_num("cid");
    #information message, step 2
    if ($data['deletecom']) {
      #save step
      unset($data['deletecom']);
      $_SESSION['steps'] = $data;
      $this->forms['information']['url'] = $this->backtracking->backlink();
      $this->forms['information']['action'] ="comments";
      $this->forms['information']['title'] = "Kommentar löschen";
      $this->forms['information']['message'] = "Kommentar erfolgreich gelöscht";
      $this->forms['information']['style'] = "green";
      $this->show('message_information', "Kommentar löschen");
    }
    if (!$cid) $this->_header($this->backtracking->backlink());
    $return = getNewsComment($cid);
    if (!$return) $this->_header($this->backtracking->backlink());
    $newsdata = getNews($nid);
    if (!$newsdata) $this->_header($this->backtracking->backlink());
    #deletecom, send
    if ($_REQUEST['send']) {
      if ($_REQUEST['yes_x']) {
        addToLogfile("Kommentar bei <b>".$newsdata['title']."</b> gelöscht","News",
          $this->userdata['uid']);
        deleteNewsCom($return['cid']);
        #save step
        $data['deletecom'] = 1;
        $_SESSION['steps'] = $data;
        $this->_header("index.php?action=deletecom&nid=".$nid."&send");
      } else {
        $this->_header($this->backtracking->backlink());
      }
    } else {
      $this->forms['information']['url'] = "index.php?nid=$nid&cid=$cid";
      $this->forms['information']['action'] = "deletecom";
      $this->forms['information']['title'] = "Kommentar löschen";
      $this->forms['information']['message'] = "Kommentar löschen ? <br><br>".
        formatTextData($return['content']);
      $this->forms['information']['style'] = "black";
      $this->show('message_question', "Kommentar löschen");
    }
  }
}
?>