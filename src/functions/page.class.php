<?php
/**
  \file page.class.php
  \brief Oberklasse der Seiten

  Oberklasse von denen die Seiten abgeleitet sind.
  Übernimmt alle Initialisierungen und die Authentifikation.
  Die Kinder überschreiben die run Methode für die
  Funktionalitäten.
*/
define("SMARTY_DIR",getcwd()."/functions/smarty/");

#load configs
require_once(SMARTY_DIR."Smarty.class.php");
require_once('configs/config.php');
require_once("functions/error_logging.php");
require_once('database/database.php');
require_once('functions/functions.php');
require_once('classes/backtracking.php');


require_once('classes/form.handler.php');

#load db-engine
/** \brief globales Datenbankhandle

  Datenbankobjekt wird erzeugt.
*/
//$db = new mysql_database($db_user,$db_password,$db_host,$db_database);

/**
  \class Page
  \brief Oberklasse
  \author Stefan Dieringer

  Stellt globale Funktionen für alle Seiten zur Verfügung, übernimmt
  alle Initialisierungen und übernimmt den Loginvorgang.
*/
  /**
  * @return void
  * @param string $msg Variable
  * @param integer $tracking Anzahl der Funktionsaufrufe die zurückverfolgt werden
  * @desc Debugausgabe von Variablen
  */
  function _dump($msg,$tracking=1) {
    echo "<pre>";
    if(is_array($msg)) {
      var_dump($msg);
    } else {
      echo $msg."\n";
    }
    if($tracking) {
      $debug_tracking = debug_backtrace();
      for ($i=0;$i < $tracking;$i++) {
        echo $debug_tracking[$i]['file'].":".$debug_tracking[$i]['line']."\n";
      }
    }
    echo "</pre>";
  }

  class Page {
  /**
    \brief Templateobjekt

    Smarty templateobjekt, hilfe unter http.smarty.php.net
  */
  var $template;
  /**
    \brief Sessiondaten

    Sessiondaten als Array
  */
  var $session;

  /**
    \brief Aktion

    per GET oder POST übergebener "action" Parameter
  */
  var $action;
  
  /**
    \brief debugmodus

    true setzen für debugmodus AN
  */
  var $debug;


  /**
    \brief Constructor

    Initialisiert das system.
  */
  function Page () {

    //load Template Engine
    $this->template = new Smarty();
    $this->template->plugins_dir = SMARTY_DIR."plugins/";
    if (!$this->template) $this->_error("Templateengine lässt sich nicht starten!");
    
    //start session
    $this->_startSession("ssid");
    
  }

  function _error($msg) {
    #$this->_dump($msg);
    exit();
  }
   
  
  #
  # Run & Eventhandler
  #
  function run ($debug=0) {
    
    #debugmode
    $this->debug = $debug;
    $this->action = $_REQUEST["action"];
  }

  #
  # Sendet einen Redirect Header
  #
  function _header($link,$message="") {
    if(preg_match("/^(.*?)(#.*?)$/is",$link,$items)) {
      $link = $items[1];
      $anker = $items[2];
    }
    if (strpos($link,"?")) {
      header("Location: ".$link."&".$this->session['link'].$anker);
    } else {
      header("Location: ".$link."?".$this->session['link'].$anker);
    }
    exit();
  }

  #
  # startet die Session
  #
  function _startSession ($name="") {
    #start session
    if($name) session_name($name);
    else $name = session_name();
    session_start();
    $this->session = array();
    $this->session['id'] = $_SESSION[$name];
    $this->session['name'] = $name;
    if(!$this->session['id']) {
      $this->session['id'] = $_REQUEST[$name];
      if (!$this->session['id']) {
        $this->session['id'] = session_id();
      }
      $_SESSION[$name] = $this->session['id'];
    }
    $this->session['link'] = "$name=".$this->session['id'];
    $this->template->assign('session',$this->session);
  }
}

?>
