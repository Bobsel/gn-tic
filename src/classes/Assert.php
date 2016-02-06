<?php



class Assert {
    
    public function isId($num, $message=null) {
        $value = (int)$num;
        if((string)$value !== (string)$num || $value < 1) {
            if(!$message) {
                $message = "positve not zero id expected. got '".$num."'";
            }
            trigger_error($message,E_USER_ERROR);
        }
    }
    
    public function isNumeric($num, $message=null) {
        if(is_numeric($num) === false) {
            if(!$message) {
                $message = "numeric value expected. got '".$num."'";
            }
            trigger_error($message,E_USER_ERROR);
        }
    }
    
    public function isIdArray($num,$message=null) {
        if(!$message) {
            $message = "check for id array";
        }
        if(is_array($num)) {
            if(count($num) > 0) {
                for($i = 0; $i < count($num);$i++) {
                    Assert::isId($num[$i],$message.", id at index ".$i." invalid");
                }
            } else {
                trigger_error($message.", id array is empty",E_USER_ERROR);
            }
        } else {
            Assert::isId($num,$message.", ");
        }
    }
    
    public function isTrue($value, $message=null) {
        if($value !== true) {
            if(!$message) {
                $message = "expected true";
            }
            trigger_error($message,E_USER_ERROR);
        }
    }
    
    public function notEmpty($value, $message=null)     {
        $result = false;
        if( !is_numeric($value) ) { 
            if( is_array($value) ) { // Is array? 
                if( count($value, 1) < 1 ) $result = true; 
            } 
            elseif(!isset($value) || strlen(trim($value)) == 0) {
                $result = true; 
            }
        }
        if($result === true) {
            if(!$message) {
                $message = "expected non empty value";
            }
            trigger_error($message,E_USER_ERROR);
        }
    }
}

?>