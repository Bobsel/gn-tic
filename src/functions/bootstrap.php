<?php
    define('FUNCTION_DIR','functions');
    define('DATABASE_DIR','database');
    define('CONFIG_DIR','configs');
    define('CLASS_DIR','classes');
    ini_set('include_path', 'lib/pear/PEAR/' . PATH_SEPARATOR . ini_get('include_path'));
    
    ob_start();
    
    #load configs
    require_once(CONFIG_DIR.'/config.php');
    
    
    #log4php
    define('LOG4PHP_DIR', 'lib/log4php');
    define('LOG4PHP_CONFIGURATION', 'configs/log4php.xml');
    
    require_once(FUNCTION_DIR.'/logging.php');
    
    require_once(CLASS_DIR."/Assert.php");
    
   
?>