<?php

require_once("classes/backtracking.php");

/*
* @desc Klasse zum generieren einer Rücksprungadresse
*
*/

class kibo_backtracking extends backtracking {

  var $uid;
  
  /**
  * @return void
  * @param string $ssid sessionid
  * @desc Analysiert die aktuelle url und erstellt eine Rücksprungadresse
  */
  function run($uid) {
    
    if(!$uid) return false;
    $this->uid = $uid;
    parent::run();
    
  }
  
  /**
  * @return array
  * @desc Liefert den Trackingbuffer
  * @private
  */
  function _get() {
    $tracking = array();
    $result = selectsqlline("select backtracking from useronline where uid = ".$this->uid);
    if($result['backtracking']) {
      $tracking = split("\n",$result['backtracking']);
    }
    return $tracking;
  }
  
  /**
  * @return void
  * @desc Schreibt den aktuellen Stack in die db
  */
  function _refresh() {
    query("update useronline set backtracking = '".join("\n",$this->backlinks)."' where uid = ".$this->uid);
  }

}
?>