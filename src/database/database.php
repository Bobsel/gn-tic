<?php

  function _dump_query($query) {
    echo"<pre>";
      var_dump($query);
      $debug_tracking = debug_backtrace();
      for($i=1;$i < 3;$i++) {
          $line = $debug_tracking[$i];
          $attrs = array();
          if(isset($line['function'])) $attrs[] = "function=\"".$line['function']."\"";
          if(isset($line['file'])) $attrs[] = "file=\"".$line['file']."\"";
          if(isset($line['line'])) $attrs[] = "line=\"".$line['line']."\"";
          if(isset($line['class'])) $attrs[] = "class=\"".$line['class']."\"";
          echo "".join(" ",$attrs)."\n";
      }
    echo"</pre>";
  }

  function selectsql($query,$col_name=null) {
    global $debug_database, $firephp;
    $result = mysql_query ($query) or db_error(array("ungültige Abfrage",$query));
    $lines = array();
    while(($row = mysql_fetch_array($result, MYSQL_ASSOC))) {
      if(isset($col_name)) {
        if(is_array($col_name)) {
          foreach ($col_name as $col) {
            $lines[$col][] = $row[$col];
          }
        } else {
          $lines[] = $row[$col_name];
        }
      } else {
        $lines[] = $row;
      }
    }
    mysql_free_result($result);
    if($debug_database) {
        $firephp->group("select sql");
        $firephp->log($query,"query");
        $firephp->log($lines,"select sql result");
        $firephp->trace("stacktrace");
        $firephp->groupEnd();
    }
    
    return $lines;
  }

  function selectsqlline($query) {
    global $debug_database, $firephp;
    $result = mysql_query ($query) or db_error(array("ungültige Abfrage",$query));
    $row = mysql_fetch_array($result, MYSQL_ASSOC);
    mysql_free_result($result);

    if($debug_database) {
        $firephp->group("select sql line");
        $firephp->log($query,"query");
        $firephp->log($row,"result");
        $firephp->trace("stacktrace");
        $firephp->groupEnd();
    }
    return $row;
  }

  function insertsql($query) {
    global $debug_database, $firephp;
    mysql_query ($query) or db_error(array("ungültige Abfrage",$query));
    $result = mysql_insert_id();
    if($debug_database) {
        $firephp->group("insert sql");
        $firephp->log($query,"query");
        $firephp->log($result,"result");
        $firephp->trace("stacktrace");
        $firephp->groupEnd();
    }
    return $result;
  }
  
  function query($query) {
    global $debug_database, $firephp;
    mysql_query ($query) or db_error(array("ungültige Abfrage",$query));
    $result = mysql_affected_rows();
    if($debug_database) {
        $firephp->group("query");
        $firephp->log($query,"query");
        $firephp->log($result,"result");
        $firephp->trace("stacktrace");
        $firephp->groupEnd();
    }
    return $result;
  }
  
  function db_error($msg) {
    if(!is_array($msg)) {
        $msg = trim(str_replace("\t", "", $msg));
        $msg = array($msg);
    }
    $msg[] = trim(mysql_error());
//    $firephp->error(join(",",$msg));
//    $firephp->trace("db error");
    trigger_error(join("\n",$msg),E_USER_ERROR);
    die();
  }
  
  $db = mysql_connect($db_host, $db_user, $db_password) or die();
  mysql_select_db($db_database) or db_error("Auswahl der Datenbank fehlgeschlagen");


?>