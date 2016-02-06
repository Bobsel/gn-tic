<?php

class formItem{
  var $name;
  var $title;
  var $error;
  var $has_error;
  var $classes;
  var $onlypost;
  var $value;
  var $format;
  var $isSubmit;
  var $smartyitems;

  function formItem($name,$title,$format,$onlypost=true) {
    $this->name = $name;
    $this->title = $title;
    $this->onlypost = $onlypost;
    $this->format = $format;
    $this->classes = array("error" => "form_error", "optional" => "form_optional", "default" => "form");
    $this->value = null;
    $this->isSubmit = false;
    $this->has_error = false;
  }
  
  function getName() {
    return $this->name;
  }

  function get() {
    return $this->value;
  }
    
  function title() {
    return $this->title;
  }
  
  function is_set() {
    return isset($this->value);
  }

  function set($value) {
    $this->value = $value;
    return $value;
  }

  function submit() {
    if($this->onlypost) {
      $this->value = $_POST[$this->getName()];
      #echo"post wert: ";var_dump($this->value);echo"<br>";
    } else {
      $this->value = $_REQUEST[$this->getName()];
      #echo"request wert: ";var_dump($this->value);echo"<br>";
    }
    $this->isSubmit = true;
  }

  function setError($msg="") {
    if($msg){
      $this->error = $this->title.": ".$msg;
    }
    $this->has_error = true;
    return false;
  }

  function checkFormat() {
    if(is_array($this->value)) {
      foreach($this->value as $key => $value) {
        if($this->format == "string") {
          $this->value[$key] = trim($this->value[$key]);
        } elseif($this->format == "numeric") {
          if(!is_numeric($this->value[$key]) || $this->value[$key] < 0) {
            return false;
            break;
          }
        } elseif($this->format == "integer") {
          if(!is_numeric($this->value[$key])) {
            return false;
            break;
          }
        }
      }
    } else {
      if($this->format == "string" || $this->format == "time" || $this->format == "date") {
        $this->value = trim($this->value);
      } elseif($this->format == "numeric") {
        if(!is_numeric($this->value) || $this->value < 0) {
          return false;
        }
      } elseif($this->format == "integer") {
        if(!is_numeric($this->value)) {
          return false;
        }
      }
    }
    return true;
  }

  function getError() {
    return $this->error;
  }

  function hasError() {
    return $this->has_error;
  }

  function getClass() {
    if($this->hasError()) {
      return $this->classes['error'];
    } else {
      return $this->classes['default'];
    }
  }

  function registerSmarty() {
    //dummy
    $item['title'] = $this->title;
    return $item;
  }
  
  function select($value) {
    // dummy
  }

  function isSubmit() {
    return $this->isSubmit;
  }
}

// text,password,textarea felder
class formInput extends formItem {
  var $optional;
  var $length;
  
  var $regex;
  var $regex_matchings;
  var $regex_result;
  
  
  function formInput($name,$title,$format,$onlypost=true,$length=null,$optional=false,$regex="") {
    parent::formItem($name,$title,$format,$onlypost);
    $this->length = $length;
    $this->optional = $optional;
    $this->regex = $regex;
    $this->regex_result = false;
  }

  function submit() {
    parent::submit();
    if(is_array($this->get())) return $this->setError("Wert ist ungültig");
    if($this->optional && strlen($this->get()) == 0)  return true;
    if(!$this->optional && strlen(trim($this->get())) == 0)  return $this->setError("Feld ist leer");
    if(!$this->checkFormat()) return $this->setError("Wert ist ungültig");
    if($this->length && strlen($this->get()) > $this->length) return $this->setError("Eingabe zu lang");
    if($this->format == "time") {
      $this->regex_result = preg_match("'^(\d{1,2}):(\d{2})$'is",$this->get(),$this->regex_matchings);
      if(!$this->regex_result) return $this->setError("Wert ist eine ungültige Zeit");
      if( $this->regex_matchings[1] < 0 || $this->regex_matchings[1] > 24 ||
          $this->regex_matchings[2] < 0 || $this->regex_matchings[2] > 59
      ) {
        return $this->setError("Wert ist eine ungültige Zeit");
      }
    }
    if($this->format == "date") {
      $this->regex_result = preg_match("'^(\d{1,2})\.(\d{1,2})\.(\d{4})$'is",$this->get(),$this->regex_matchings);
      if(!$this->regex_result) return $this->setError("Wert ist ein ungültiges Datum");
      if( $this->regex_matchings[1] < 1 || $this->regex_matchings[1] > 31 ||
          $this->regex_matchings[2] < 1 || $this->regex_matchings[2] > 12 ||
          $this->regex_matchings[3] < 2004 || $this->regex_matchings[3] > 2020
      ) {
        return $this->setError("Wert ist ein ungültiges Datum");
      }
    }
    if($this->regex) {
      $this->regex_result =  preg_match($this->regex,$this->get(),$this->regex_matchings);
      if(!$this->regex_result) return $this->setError("Wert entspricht nicht der vorgegebenen Formatierung");
    }
    return true;
  }

  function getMatching() {
    if($this->regex_result) {
      return $this->regex_matchings;
    }
  }

  /**
  * @return array smarty daten
  * @desc liefert Daten für die Smartyengine
  * @protected
  */
  function registerSmarty() {
    $item = parent::registerSmarty();
    if($this->optional) $item['title'] .= " (optional)";
    $item['value'] = $this->get();
    if($this->hasError()) {
      $item['class'] = $this->classes['error'];
    } else {
      if(strlen($this->get()) || !$this->optional) {
        $item['class'] = $this->classes['default'];
      } else {
        $item['class'] = $this->classes['optional'];
      }
    }
    return $item;
  }
}

// radio
class formRadio extends formItem {
  // liste der werte, list[i]['value']
  var $list;
  // ausgewählter wert per default
  var $default;

  //liste = array()
  function formRadio($name,$title,$format,$list,$default="",$onlypost=true) {
    parent::formItem($name,$title,$format,$onlypost);
    // liste baun
    for($i=0;$i < count($list);$i++) {
      $this->list[] = array("value" => $list[$i]);
    }
    // defaultwert überhaupt in der liste ?
    if($default && in_array($default,$list)) {
      $this->default = $default;
    }
  }

  function submit() {
    parent::submit();
    // darf kein array sein
    if(is_array($this->get())) return $this->setError("Wert ungültig");
    // muss da sein, nicht optional
    if(strlen(trim($this->get())) == 0) return $this->setError("Feld leer");
    // formatcheck
    if(!$this->checkFormat()) return $this->setError("Wert ungültig");
    // ist in den Vorgaben vorhanden
    if(($key = array_search(array("value" => $this->get()),$this->list)) === FALSE) {
      return $this->setError("Wert existiert nicht in den Vorgaben");
    } else {
      // selektiertes Element auswählen
      $this->select($this->get());
    }
#    var_dump($this->list);
#    echo "<br>wert: ".$this->get()."<br>result: ".in_array($this->get(),array_keys($this->list))."<br>";
    return true;
  }

  function registerSmarty() {
    $item = parent::registerSmarty();
    // falls kein Wert gesetzt oder Fehler da ist, default wert auswählen
    if($this->default && $this->isSubmit() && (!$this->get() || $this->getError())) {
      $this->select($this->default);
    }
    $item['value'] = $this->get();
    $item['default'] = $this->default;
    $item['list'] = $this->list;
    return $item;
  }
  
  function select($value) {
    if(is_array($value)) return;
    if(!(($key = array_search(array("value" => $value),$this->list)) === FALSE)) {
      $this->list[$key]['checked'] = 'checked';
    }
  }
}

// checkbox
class formCheckBox extends formItem {
  // liste der werte, list[i]['value']
  var $list;
  // ausgewählte werte per default
  var $default;
  // erwartet ein Array als HTTP variable
  var $is_array;

  //liste = array()
  function formCheckBox($name,$title,$format,$list,$multiple=true,$default=null,$onlypost=true) {
    parent::formItem($name,$title,$format,$onlypost);
    if(!$list) $list = array();
    if(!is_array($list)) $list = array($list);
    if($multiple) {
      // liste baun
      for($i=0;$i < count($list);$i++) {
        $this->list[] = array("value" => $list[$i]);
      }
    } else {
      $this->list[0] = array("value" => $list[0]);
    }
    $this->is_array = $multiple;
    // defaultwerte überhaupt in der liste ?
    if(is_array($default)) {
      for($i=0;$i < count($default);$i++) {
        if(in_array($default[$i],$list)) {
          $this->default[] = $default[$i];
        }
      }
    } else {
      if(in_array($default,$list)) {
        $this->default = $default;
      }
    }
  }

  function submit() {
    parent::submit();
    // darf kein array sein
    if(!$this->is_array) {
      if(is_array($this->get())) return $this->setError("Wert ungültig");
      // optional
      if(strlen(trim($this->get())) > 0) {
        // formatcheck
        if(!$this->checkFormat()) return $this->setError("Wert ungültig");
        // ist in den Vorgaben vorhanden
        if(($key = array_search(array("value" => $this->get()),$this->list)) === FALSE) {
          return $this->setError("Wert existiert nicht in den Vorgaben");
        } else {
          // selektiertes Element auswählen
          $this->select($this->get());
        }
      }
    } else {
      if($this->get() && !is_array($this->get())) return $this->setError("Wert ungültig");
      // optional
      if(count($this->get()) > 0) {
        // formatcheck
        if(!$this->checkFormat()) return $this->setError("Wert(e) ungültig");
        // ist in den Vorgaben vorhanden
        $noerror = true;        
        foreach($this->get() as $key => $value) {
          if(($result = array_search(array("value" => $value),$this->list)) === FALSE) {
            $noerror = false;
            break;
          }
        }
        if(!$noerror) return $this->setError("Wert existiert nicht in den Vorgaben");
        $this->select($this->get());
      }
    }
#    var_dump($this->list);
#    echo "<br>wert: ".$this->get()."<br>result: ".in_array($this->get(),array_keys($this->list))."<br>";
    return true;
  }

  function registerSmarty() {
    $item = parent::registerSmarty();
    // falls kein Wert gesetzt oder Fehler da ist, default wert(e) auswählen
    if($this->default && $this->isSubmit() && (!$this->get() || $this->getError())) {
      $this->select($this->default);
    }
    $item['default'] = $this->default;
    if($this->is_array) {
      $item['list'] = $this->list;
      $item['value'] = $this->get();
    } else {
      $item['value'] = $this->list[0]['value'];
      $item['checked'] = $this->list[0]['checked'];
    }
    return $item;
  }

  function select($value) {
    if(is_array($value)) {
      foreach($value as $key => $val) {
        if(!(($result = array_search(array("value" => $val),$this->list)) === FALSE)) {
          // selektiertes Element auswählen
          $this->list[$result]['checked'] = 'checked';
        }
      }
    } else {
      if(!(($result = array_search(array("value" => $value),$this->list)) === FALSE)) {
        // selektiertes Element auswählen
        $this->list[$result]['checked'] = 'checked';
      }
    }
  }
}

// selectBox
class formSelectBox extends formItem {
  // liste der werte, list[i]['value'],$list[i][title]
  var $list;
  // ausgewählte werte per default
  var $default;
  // erwartet ein Array als HTTP variable, multiple auswahl
  var $is_array;

  // sucht den Wert in der Liste und gibt den Index zurück
  function _findItem($val) {
    $count = count($this->list);
    for($i=0;$i < $count;$i++) {
      if($this->list[$i]['value'] == $val) {
        return $i;
        break;
      }
    }
    return false;
  }

  // liste[] = array('value' => wert, 'title' => title)
  function formSelectBox($name,$title,$format,$list,$multiple=true,$default=null,$onlypost=true) {
    parent::formItem($name,$title,$format,$onlypost);
    // liste baun
    $this->list = array();
    if($list && is_array($list)) {
      foreach($list as $value) {
        $this->list[] = array("value" => $value['value'],"title" => $value['title']);
      }
    }
    $this->is_array = $multiple;
    // defaultwerte überhaupt in der liste ?
    if(is_array($default)) {
      for($i=0;$i < count($default);$i++) {
        if(!($this->_findItem($default[$i]) === false)) {
          $this->default[] = $default[$i];
        }
      }
    } else {
      if(!($this->_findItem($default) === false)) {
        $this->default = $default;
      }
    }
  }

  function submit() {
    parent::submit();
    if(!$this->is_array) {
      // darf kein array sein
      if(is_array($this->get())) return $this->setError("Wert ungültig");
      // optional
      if(strlen(trim($this->get())) > 0) {
        // formatcheck
        if(!$this->checkFormat()) return $this->setError("Wert ungültig");
        // ist in den Vorgaben vorhanden
        if($this->_findItem($this->get()) === FALSE) {
          return $this->setError("Wert existiert nicht in den Vorgaben");
        } else {
          // selektiertes Element auswählen
          $this->select($this->get());
        }
      }
    } else {
      if($this->get() && !is_array($this->get())) return $this->setError("Wert ungültig");
      // optional
      if(count($this->get()) > 0) {
        // formatcheck
        if(!$this->checkFormat()) return $this->setError("Wert(e) ungültig");
        // ist in den Vorgaben vorhanden
        $error = false;
        foreach($this->get() as $key => $value) {
          if($this->_findItem($value) === FALSE) {
            $error = true;
            break;
          }
        }
        if ($error) return $this->setError("Wert existiert nicht in den Vorgaben");
        $this->select($this->get());
      }
    }
#    var_dump($this->list);
#    echo "<br>wert: ".$this->get()."<br>result: ".in_array($this->get(),array_keys($this->list))."<br>";
    return true;
  }

  function registerSmarty() {
    $item = parent::registerSmarty();
    // falls kein Wert gesetzt oder Fehler da ist, default wert(e) auswählen
    if($this->default && $this->isSubmit() && (!$this->get() || $this->getError())) {
      $this->select($this->default);
    }
    $item['default'] = $this->default;
    $item['list'] = $this->list;
    $item['value'] = $this->get();
    return $item;
  }

  function select($value) {
    if(is_array($value)) {
      foreach($value as $key => $val) {
        if(!(($result = $this->_findItem($val)) === FALSE)) {
          // selektiertes Element auswählen
          $this->list[$result]['selected'] = 'selected';
          $this->value = $value;
        }
      }
    } else {
      if(!(($result = $this->_findItem($value)) === FALSE)) {
        // selektiertes Element auswählen
        $this->list[$result]['selected'] = 'selected';
        $this->value = $value;
      }
    }
  }
}


class formContainer {
  var $items;
  var $errors;

  function formContainer() {
    $this->items = array();
    $this->errors = array();
  }

  function add($obj) {
    if(is_a($obj,"formItem")) {
      $this->items[$obj->getName()] = $obj;
    }
  }

  function get($name) {
    if(is_object($this->items[$name])) {
      return $this->items[$name]->get();
    }
  }

  function is_set($name) {
    if(is_object($this->items[$name])) {
      return $this->items[$name]->is_set();
    }
  }
  
  function get_obj($name,$obj) {
    if(is_object($this->items[$name])) {
      $obj = $this->items[$name];
    }
  }

  function select($name,$value) {
    if(is_object($this->items[$name])) {
      $this->items[$name]->select($value);
    }
  }

  function set($name,$value) {
    if(is_object($this->items[$name])) {
      $this->items[$name]->set($value);
    }
  }

  function submit($names="") {
    if($names && !is_array($names))$names = array($names);
    if($names){
      foreach($names as $name) {
        if(is_a($this->items[$name],"formItem")) {
          if(!$this->items[$name]->submit()) {
            $this->errors[] = $this->items[$name]->getError();
          }
        }
      }
    } else {
      $this->errors = array();
      foreach($this->items as $name => $obj) {
        if(!$this->items[$name]->submit()) {
          $this->errors[] = $this->items[$name]->getError();
        }
      }
    }
    if(count($this->errors)) return false;
    else return true;
  }

  function getRegex($name){
    if(is_a($this->items[$name],"formInput")) {
      return $this->items[$name]->getMatching();
    }
  }

  function setError($names) {
    if(!$names) return;
    if(!is_array($names)) $names = array($names);
    foreach ($names as $name) {
      if(is_a($this->items[$name],"formItem")){
        $this->items[$name]->setError();
      }
    }
  }

  function addError($msg){
    $this->errors[] = $msg;
  }

  function hasErrors() {
    foreach($this->items as $name => $obj) {
      if($this->items[$name]->hasError()) return true;
    }
    return false;
  }

  function getErrors() {
    return $this->errors;
  }

  // smartyklasse per referenz übergeben
  function registerVars(&$smarty) {
    $items = array();
    $this->smartyitems = array();
    foreach($this->items as $name => $obj) {
      $this->smartyitems[$name] = $this->items[$name]->registerSmarty();
    }
#    echo"items:";var_dump($this->items);echo"<br>";
    $smarty->assign("items",$this->smartyitems);
    $smarty->assign("errors",$this->getErrors());
  }
}

?>
