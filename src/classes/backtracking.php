<?php

/*
* @desc Klasse zum generieren einer Rücksprungadresse
*
*/

class backtracking {

  var $backlink;
  var $backlinks;
  var $session;
  var $debug;
  var $buffer;
  var $default;
  var $execludes;
  
  /**
  * @return void
  * @param string $msg
  * @desc Debugmessage hinzufügen
  */
  function _debugmsg($msg) {
    $this->debug[] = $msg;
  }
  
  
  /**
  * @return backtracking
  * @param string $default Default Rücksprungadresse, index.php
  * @param integer $buffer Grösse des Rücksprungbuffers
  * @desc Konstruktor
  */
  function backtracking(&$session,$default="index.php",$buffer = 10) {
    
    
    $this->debug = array();
    $this->session = $session;
    $this->buffer = $buffer;
    $this->default = $default;
    $this->execludes = array();
  }
  
  /**
  * @return void
  * @param unknown $params
  * @desc mit Execlude können Urls mit bestimmten Parameter/werten vom tracking ausgeschlossen werden.
  * Möglichkeiten:
  * params(param => value) : parameter mit wert wird nicht getrackt
  * params(param => "*") : parameter wird generell nicht getrackt, egal was fürn wert
  * params(parameter => array()) : parameter mit den jeweiligen Werten im Array wird nicht getrackt
  */
  function execlude($params) {
    if(!$params || !is_array($params)) return;
    $this->execludes = $params;  
  }
  
  /**
  * @return void
  * @desc Analysiert die aktuelle url und erstellt eine Rücksprungadresse
  */
  function run() {
    
    $this->backlinks = $this->_get();
    
    $backlink = urldecode($_REQUEST['backlink']);
    $link = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
#    if(preg_match("'^(?:(.*?\/)|)([^\/]*?)$'is",$link,$subs)) {
#      $backlink = $subs[1].$backlink;
#    }
    $back = $_REQUEST['back'];
    
    $notrack = isset($_REQUEST['notrack']);
    if(strcmp($link,"http://".$_SERVER['SERVER_NAME']."/") == 0) {
      $this->_debugmsg("double: $link");
    } else {
      if($backlink) {$this->_set($backlink);}
      
      if(isset($back)) {
        //rücksprung
        $this->_pop($link);
        $this->_refresh();
        $this->backlink = $this->_generate_backlink();
      } else {
        // formular abgeschickt, kein tracking
        if(isset($_REQUEST['send'])) {$this->backlink = $this->_generate_backlink();return;}
        // ausnahmen 
        if(!$notrack && count($this->execludes)) {
          foreach ($this->execludes as $param => $value) {
            if(is_array($value)) {
              if($_REQUEST[$param] && in_array($_REQUEST[$param],$value)) {
                $notrack = true;
                break;
              }
            } elseif($value == "*") {
              if(isset($_REQUEST[$param])) {$notrack = true;break;}
            } else {
              if($_REQUEST[$param] == $value) {$notrack = true;break;}
            }
          }
        }
        if($notrack == false) {
          // neuer link
          $this->_push($link);
          $this->_refresh();
          $this->backlink = $this->_generate_backlink();
        }
      }
    }
    if($notrack && !isset($back)) {
      $this->backlink = $this->last();
    }
  }
  
  /**
  * @return void, false bei fehlschlag
  * @param string $link
  * @param integer $index
  * @desc Ändert einen Link im stack
  */
  function _set($link,$index=null) {
    if(!$link) return false;
    $backlinks_count = count($this->backlinks);
    if($backlinks_count == 0) return false;
    if(!$index || !is_numeric($index) || $index >= $backlinks_count || $index < 0) {
      $index = $backlinks_count-1;
    }
    
    if(($link = $this->_decode_url($link)) === false) return false;
    
    if($url['actual']['params'][$this->sessionname]) unset($url['actual']['params'][$this->sessionname]);
    
    $this->backlinks[$index] = urlencode($this->_encode_url($link));
  }
  
  /**
  * @return array
  * @desc Liefert den Trackingbuffer, muss überschrieben werden
  * @protected
  */
  function _get() {
    $tracking = array();
    return $tracking;
  }
  
  /**
  * @return void
  * @desc Sichert den aktuellen Stack, muss überschrieben werden
  * @protected
  */
  function _refresh() {
  }

  /**
  * @return unknown
  * @param unknown $link
  * @desc decodiert eine Url und liefert ein array mit server,params und ancer als felder zurück
  */
  function _decode_url($link) {
    $result = array();
    if(preg_match("/^([^\?&]*?)(?:\?([^\?#]*?)|)(?:#([^\?&#]*?)|)$/is",$link,$matches)) {
      $result['server'] = $matches[1];
      if($matches[2]) {
        foreach (split("&",$matches[2]) as $item) {
          $param = split("=",$item);
          if(strlen($param[0])) {
            $result['params'][$param[0]] = $param[1];
          }
        }
      }
      if($matches[3]) {
        $result['ancer'] = $matches[3];
      }
    } else {
      return false;
    }
    return $result;
  }
  
  /**
  * @return string
  * @param array $url
  * @desc codiert eine voher decodierte url
  */
  function _encode_url($url) {
    $link = $url['server'];
    if($url['params']) {
      $params = array();
      foreach ($url['params'] as $key => $val) {
        if(strlen($val)) {
          $params[] = "$key=$val";
        } else {
          $params[] = "$key";
        }
      }
      $link .= "?".join("&",$params);
    }
    if($url['ancer']) {
      $link .= "#".$url['ancer'];
    }
    return $link;
  }

  /**
  * @return boolean
  * @param array $params1
  * @param array $params2
  * @param array|string $param parameter, die beim vergleich nicht berücksichtigt werden sollen
  * @desc Vergleicht 2 arrays mit parametern auf gleichniss
  */
  function _diff_params($params1,$params2,$param=null) {
    if($param) {
      if(is_array($param)) {
        foreach($param as $name) {
          unset($params1[$name]);
          unset($params2[$name]);
        }
      } else {
        unset($params1[$param]);
        unset($params2[$param]);
      }
    }
    if(!$params1) $params1 = array();
    if(!$params2) $params2 = array();
    return (!array_diff_assoc($params1,$params2) &&
        !array_diff_assoc($params2,$params1));
  }
  
  /**
  * @return void
  * @param string $link
  * @desc packt einen neuen Link auf den Stack
  */
  function _push($link) {
    
    if(!$link) return false;
    
    $backlinks_count = count($this->backlinks);
    
    $url['actual'] = $this->_decode_url($link);
    
    unset($url['actual']['params']['backlink']);
    unset($url['actual']['params'][$this->session['name']]);
    
    foreach ($_POST as $key => $val) {
      if(is_array($val)) {
        $url['actual']['params'][$key] = urlencode(join(",",$val));
      } elseif(isset($_POST[$key])) $url['actual']['params'][$key] = $val;
    }
    if($url['actual']['params'][$this->sessionname]) unset($url['actual']['params'][$this->sessionname]);

    if($backlinks_count > 0) {
      $last = urldecode(end($this->backlinks));
      
      $url['last'] = $this->_decode_url($last);
      
      $servercheck = $url['last']['server'] == $url['actual']['server'];
      #$paramcheck = _diff_params($url['last']['params'],$url['actual']['params']);
      $pagecheck = $this->_diff_params($url['last']['params'],$url['actual']['params'],"page");
      
      if($servercheck && $pagecheck) {
        $url['last']['params']['page'] = $url['actual']['params']['page'];
        $this->backlinks[$backlinks_count-1] = urlencode($this->_encode_url($url['last']));
        return;
      }
    }
    array_push($this->backlinks,urlencode($this->_encode_url($url['actual'])));
    if($backlinks_count+1 > $this->buffer){
      array_shift($this->backlinks);
    }
  }
  
  /**
  * @return void
  * @param string $link
  * @desc Rücksprung, entfernt den letzten Eintrag
  */
  function _pop($link) {
    if(count($this->backlinks) > 0) {
      $link = $this->_decode_url($link);
      $last = $this->_decode_url(urldecode(end($this->backlinks)));
      $check = $this->_diff_params($link['params'],$last['params'],array("page",$this->session['name'],"back"));
      if(!$check) {
        array_pop($this->backlinks);
      }
    }
  }
  
  /**
  * @return string
  * @desc Erzeugt die Rücksprungadresse
  */
  function _generate_backlink() {
    $backlink = $this->default;
    $backlinks_count= count($this->backlinks);
    if($backlinks_count > 1) {
     $url = $this->_decode_url(urldecode($this->backlinks[$backlinks_count-2]));
     $url['params']['back'] = "";
     $url['params'][$this->session['name']] = $this->session['id'];
     $backlink = $this->_encode_url($url);
    }
    return $backlink;
  }
  
  /**
  * @return string
  * @desc Liefert den letzten Eintrag im Buffer
  */
  function last() {
    $backlinks_count= count($this->backlinks);
    if($backlinks_count > 0) {
      return urldecode($this->backlinks[$backlinks_count-1]);
    } else {
      return $this->default;
    }
  }
  
  /**
  * @return string
  * @desc Gibt die Rücksprungadresse zurück
  */
  function backlink() {
    return $this->backlink;
  }
}
?>