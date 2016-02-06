<?php
  function _dumpFile($msg,$tracking=1) {
  	$lines = array();
  	if(is_array($msg)) {
      $msg = var_export($msg,true);
    }
    $msg .= "\n";
    $lines[] = $msg;
    if($tracking) {
      $debug_tracking = debug_backtrace();
      for ($i=0;$i < $tracking;$i++) {
        $lines[] = $debug_tracking[$i]['file'].":".$debug_tracking[$i]['line']."\n";
      }
    }
  	$h = fopen("debug.log","a+");
  	if($h === false) return;
  	foreach ($lines as $line) {
	  	fwrite($h,"[".date("d.m.Y H:i")."] ".$line);
  	}
  	fclose($h);
  }
?>