<?php

require_once(LOG4PHP_DIR . '/LoggerAppenderSkeleton.php');
require_once(LOG4PHP_DIR . '/LoggerLog.php');

require_once('FirePHPCore/FirePHP.class.php');


class LoggerAppenderFirePHP extends LoggerAppenderSkeleton {
    
    private $firephp;
    
    /**
     * Constructor.
     *
     * @param string $name appender name
     */
    function LoggerAppenderFirePHP($name)
    {
        $this->LoggerAppenderSkeleton($name);
    }

    function activateOptions()
    {
        global $debug_firephp;
        $this->firephp = & FirePHP::getInstance(false);
        if(!isset($this->firephp)) {
            $this->firephp = & FirePHP::getInstance(true);
            $this->firephp->setEnabled($debug_firephp);
            $options = array('maxObjectDepth' => 10,
                             'maxArrayDepth' => 20,
                             'useNativeJsonEncode' => true,
                             'includeLineNumbers' => true);
            $this->firephp->setOptions($options);
    //        $this->firephp->registerErrorHandler();
            $this->firephp->registerExceptionHandler();
        }
        return;
    }
    
    function close()
    {
    }
    
    function get_ancestors ($class) {
        $classes = array($class);
        while($class = get_parent_class($class)) { $classes[] = $class; }
        return $classes;
    }

    function append($event)
    {
        LoggerLog::debug("LoggerAppenderFirePHP::append()");
        $message = array("message" => $event->getMessage());
        if (function_exists('debug_backtrace')) {
            $prevHop = null;
            $trace = debug_backtrace();
            // make a downsearch to identify the caller
            $hop = array_pop($trace);
            $step = array();
            while ($hop !== null) {
                $className = @$hop['class'];
                if ( !empty($className) and ($className == 'loggercategory' or in_array("LoggerCategory",$this->get_ancestors($className))) ) {
                    $step["file"] = str_replace("\\","/",str_replace(getcwd(), "", $hop["file"]));
                    $step["line"] = $hop['line'];
                    break;
                }
                $prevHop = $hop;
                $hop = array_pop($trace);
            }
            $step['class'] = isset($prevHop['class']) ? $prevHop['class'] : 'main';
            if (isset($prevHop['function']) and
                $prevHop['function'] !== 'include' and
                $prevHop['function'] !== 'include_once' and
                $prevHop['function'] !== 'require' and
                $prevHop['function'] !== 'require_once') {                                        

                $step['function'] = $prevHop['function'];
            } else {
                $step['function'] = 'main';
            }
            $message["caller"] = join(":",array($step["file"],$step["class"],$step["function"],$step["line"]));
        }
        $label = "";
        if(isset($message["caller"])) {
            $label = " ".$message["caller"];
        }
        $level = & $event->getLevel();
        switch ($level->level) {
        	case LOG4PHP_LEVEL_INFO_INT: 
                $code = FirePHP::INFO;
                break;
        	case LOG4PHP_LEVEL_WARN_INT: 
                $code = FirePHP::WARN;
                break;
        	case LOG4PHP_LEVEL_ERROR_INT: 
                $code = FirePHP::ERROR;
                break;
            case LOG4PHP_LEVEL_FATAL_INT: 
                $code = FirePHP::ERROR;
                break;
            default:
                $code = FirePHP::LOG;
            break;
        }
        $this->firephp->fb($message,($level->levelStr).$label,$code);
    }
}

?>