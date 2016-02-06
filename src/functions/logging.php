<?php

require_once('FirePHPCore/FirePHP.class.php');

ini_set("memory_limit","80M");

$firephp = FirePHP::getInstance(true);
$firephp->setEnabled($debug_firephp);
$options = array('maxObjectDepth' => 10,
             'maxArrayDepth' => 20,
             'useNativeJsonEncode' => false,
             'includeLineNumbers' => true);
$firephp->setOptions($options);
$firephp->registerExceptionHandler();

// we will do our own error handling
error_reporting(E_ALL & ~ E_NOTICE);

// user defined error handling function
function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars) {
    global $firephp;
    if(!(error_reporting() & $errno)) {
        return;
    }
            //configparameter
    global $debug, $error_log;
//    $logger = & LoggerManager::getLogger("error");
    $dt = date("Y-m-d H:i:s (T)");

    $errortype = array (
        
        E_ERROR=>"Error",
        E_WARNING=>"Warning",
        E_PARSE=>"Parsing Error",
        E_NOTICE=>"Notice",
        E_CORE_ERROR=>"Core Error",
        E_CORE_WARNING=>"Core Warning",
        E_COMPILE_ERROR=>"Compile Error",
        E_COMPILE_WARNING=>"Compile Warning",
        E_USER_ERROR=>"User Error",
        E_USER_WARNING=>"User Warning",
        E_USER_NOTICE=>"User Notice",
        E_STRICT=>"Runtime Notice"
    );
    // set of errors for which a var trace will be saved
    $user_errors = array (E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
    
    $notices = array(E_NOTICE, E_USER_NOTICE,E_STRICT);
    $warnings = array(E_WARNING,E_CORE_WARNING,E_COMPILE_WARNING,E_USER_WARNING);
    
    if($debug){
        $message = array();
        
        $message[] = $errortype[$errno];
        
        if (is_array(split("\n", $errmsg))) {
            $errmsg = split("\n", $errmsg);
            foreach ($errmsg as $line) {
                $line = htmlspecialchars($line);
                $message[] = "\t".$line;
            }
        } else {
            $message[] = "\t".$errmsg;
        }
        $debug_tracking = debug_backtrace();
        if(is_array($debug_tracking) && count($debug_tracking) > 0) {
            $message[] = "Caused by";
            foreach ($debug_tracking as $line) {
            if((!isset($line["file"]) || substr($line["file"],-11,11) !== "logging.php") &&(
                !isset($line["function"]) || $line["function"] !== "userErrorHandler"
            )) {
                    $item = array();
                    if ( isset ($line['file']))$item[] = $line['file'];
                    if ( isset ($line['class']))$item[] = $line['class'];
                    if ( isset ($line['function']))$item[] = $line['function'];
                    if ( isset ($line['line']))$item[] = $line['line'];
                    $message[] = "\t".join(":", $item);
                }
            }
        }
        if (in_array($errno, $user_errors)) {
            $message[] = "variable trace:";
            $message[] = "\t".var_export($vars,true);
        }
        $message = join("\r\n",$message)."\r\n";
        //header("Content-Type: plain/text; charset=UTF-8");
        echo "<pre>";
        echo $message;
        echo "</pre>";
    }
    $message = array("type"=>$errortype[$errno],"message"=>$errmsg,"file"=>$filename,"line"=>$linenum,"vars"=>$vars);
    $debug_tracking = debug_backtrace();
    if(is_array($debug_tracking) && count($debug_tracking) > 0) {
        $message["trace"] = array();
        foreach ($debug_tracking as $line) {
            if((!isset($line["file"]) || substr($line["file"],-11,11) !== "logging.php") &&(
                !isset($line["function"]) || $line["function"] !== "userErrorHandler"
            )) {
                $item = array();
                if ( isset ($line['file']))$item[] = $line['file'];
                if ( isset ($line['class']))$item[] = $line['class'];
                if ( isset ($line['function']))$item[] = $line['function'];
                if ( isset ($line['line']))$item[] = $line['line'];
                $message["trace"][] = join(":", $item);
            }
        }
    }
    $logger = & LoggerManager::getLogger("error");
    if(in_array($errno, $notices)) {
        //$firephp->info($message,$errortype[$errno]);
        $logger->debug($message);
    } else if(in_array($errno, $warnings)) {
        $logger->warn($message);
        //$firephp->warn($message,$errortype[$errno]);
    } else {
        $logger->fatal($message);
//        $firephp->error($message,$errortype[$errno]);
        $firephp->trace("fatal error stack trace");
        if(!$debug) {
            //header("Content-Type: plain/text; charset=UTF-8");
            echo "<pre>";
            echo "Es ist ein schwerwiegender Fehler im Skript aufgetreten, bitte wenden Sie sich an den Entwickler!";
            echo "</pre>";
        }
        die();
    }
    
}

$old_error_handler = set_error_handler("userErrorHandler");

require_once(LOG4PHP_DIR . '/LoggerLog.php');

LoggerLog::internalDebugging($debug_logger);

require_once(LOG4PHP_DIR . '/LoggerManager.php');

?>