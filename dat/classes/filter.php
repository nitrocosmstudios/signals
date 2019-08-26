<?php

  /**
  *  @author Troy McQuinn
  *  This class contains functions for filtering and validating user input.
  */

  class filter{
  
    /**
    * This function performs basic user input filtering to prevent attacks.
    * It restricts everything to alphanumeric data under 96 characters in length.
    * @param $x = the string to filter
    * @return $x = the filtered string.
    * This function will accept 1-dimensional arrays, too.
    */
    static function basic_filter($x){
      if(is_array($x)){
        $b = Array();
        foreach($x as $n => $v){
          $b[$n] = substr(preg_replace("/[^A-Za-z0-9_\-]/",'',$v),0,96);
        }
        return $b;
      } else {
        return substr(preg_replace("/[^A-Za-z0-9_\-]/",'',$x),0,96);
      }
    }
    
    // Performs the same function as above but allows data up to 1024 characters.
    static function long_filter($x){
      return substr(preg_replace("/[^A-Za-z0-9]/",'',$x),0,1024);   
    }
  
    /**
    * This function filters an email address.
    * @param $x = the string to filter
    * @param $s = the maximum size.
    * @return $x = the filtered string.
    */
    static function email($x){
      return substr(preg_replace("/[^A-Za-z0-9_\@.\- ]/",'',$x),0,64);   
    }
    
    /**
    * Filters GET URL 'go' variables.
    * @param $x = value to filter.
    * @return $x = filtered value.
    */
    static function go($x){
      return substr(preg_replace("/[^A-Za-z0-9_ ]/",'',$x),0,16);  
    }
    
    /**
    * Filters strings to alpha-only.
    * @param $x = string to filter.
    * @param $s = maximum size
    * @return $x = filtered string.
    */
    static function alpha($x,$s){
      return substr(preg_replace("/[^A-Za-z']/",'',$x),0,$s);
    }
    
    /**
    * Filters integers.
    * @param $x = number to filter.
    * @param $s = maximum size
    * @return $x = filtered number.
    */
    static function numeric($x,$s){
      return substr(preg_replace("/[^0-9]/",'',$x),0,$s);
    }
    
    /**
    * Filters alphanumeric strings; allows hyphens, spaces, underscores, and periods.
    * @param $x = the string to filter
    * @param $s = maximum size
    * @return $x = the filtered string.
    */
    static function alphanumeric_extended($x,$s){
      return filter::SQLEscape(substr(preg_replace("/[^A-Za-z0-9\-_ .'\/]/",'',$x),0,$s));
    }
    
    /**
    * Filters alphanumeric strings.
    * @param $x = the string to filter
    * @param $s = maximum size
    * @return $x = the filtered string.
    */
    static function alphanumeric($x,$s){
      return substr(preg_replace("/[^A-Za-z0-9']/",'',$x),0,$s);
    }
    
    /**
    * Splits a phone number into an array of area code, prefix, and suffix.
    * @param string $x = phone number.
    * @return array $n = three-element array.  0 = area code, 1 = prefix, 2 = suffix.
    */
    static function splitPhone($x){
      $n = Array();
      $n[0] = substr($x,0,3);
      $n[1] = substr($x,3,3);
      $n[2] = substr($x,6,4);
      return $n;
    }
    
    /**
    * Re-assembles a phone number.
    * @param array $n = three-element array.  0 = area code, 1 = prefix, 2 = suffix.
    * @return string $x = phone number.
    */
    static function mergePhone($n){
      return implode('',$n);
    }
    
    /**
    * This function determines if an array is associative or not.
    * @param array $array = the array to test.
    * @return boolean $x = true if associative, false if not.
    */
    static function is_assoc($array){
      foreach (array_keys($array) as $k => $v){
        if ($k !== $v){
          return true;
        }
      }
      return false;
    }
    
    static function validateEmailOLD($x){
      $t = explode('@',$x);
      $user = $t[0];
      if(empty($user)) return false;
      // Require domain portion
      if(!isset($t[1])) return false;
      $domain = $t[1];
      // Require domain portion to be at least two characters long
      if(!isset($domain{2})) return false;
      $t = explode('.',$domain);
      // There must be at least one period and at least two characters after the period.
      if(!isset($t[1])) return false;
      $ending = $t[1];
      if(!isset($ending{1})) return false;
      return true;
    }
    
    static function validateEmailOLD2($x){
      $p = "\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*([,;]\s*\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*)*";
      return preg_match('/'.$p.'/',$x);    
    }
    
    /**
    Validate an email address.
    Provide email address (raw input)
    Returns true if the email address has the email 
    address format and the domain exists.
    */
    static function validateEmail($email){
       $isValid = true;
       $atIndex = strrpos($email, "@");
       if (is_bool($atIndex) && !$atIndex){
          $isValid = false;
       } else {
          $domain = substr($email, $atIndex+1);
          $local = substr($email, 0, $atIndex);
          $localLen = strlen($local);
          $domainLen = strlen($domain);
          if ($localLen < 1 || $localLen > 64) {
             // local part length exceeded
             $isValid = false;
          } else if ($domainLen < 1 || $domainLen > 255) {
             // domain part length exceeded
             $isValid = false;
          } else if ($local[0] == '.' || $local[$localLen-1] == '.') {
             // local part starts or ends with '.'
             $isValid = false;
          } else if (preg_match('/\\.\\./', $local)) {
             // local part has two consecutive dots
             $isValid = false;
          } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
             // character not valid in domain part
             $isValid = false;
          } else if (preg_match('/\\.\\./', $domain)) {
             // domain part has two consecutive dots
             $isValid = false;
          } else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',str_replace("\\\\","",$local))){
             // character not valid in local part unless 
             // local part is quoted
             if (!preg_match('/^"(\\\\"|[^"])+"$/',str_replace("\\\\","",$local))){
                $isValid = false;
             }
          }
          /*
          if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))){
             // domain not found in DNS
             $isValid = false;
          }
          */
       }
       return $isValid;
    }
    
    static function validateAddress($x){
      $p = "/[A-Za-z0-9.\- ]{1,255}/";
      return preg_match($p,$x);    
    }
    
    static function validateName($x){
      $p = "/[A-Za-z'.\-]{1,32}/";
      return preg_match($p,$x);
    }
    
    static function validateState($x){
      global $cfg;
      $states = explode(',',$cfg['general']['states']);
      $states = array_flip($states);
      return isset($states[$x]);
    }
    
    static function validateZip($x){
      return !((!isset($x{4})) || (isset($x{5})));
    }
    
    static function validatePassword($p,$c){
      // Password and confirm MUST match, be at least 6 characters and no more than 64 characters.
      return (($p == $c) && (isset($p{5})) && (!isset($p{63})));
    }
    
    static function validatePhrase($x){
      return (isset($x{5}));
    }
    
    static function validatePhone($x){
      return !((!isset($x{9})) || (isset($x{10})) || (!is_numeric($x)));
    }
    
    /**
    * Converts an array so that the values are identical to the keys
    * @param array $d = the array to process.
    * @return array $d = the processed array.
    */
    static function arrayMirror($d){
      if(!is_array($d)) return false;
      foreach($d as $n => $v){
        $d[$n] = $n;
      }
      return $d;
    }
    
    /**
    * Converts an array so that the 'id' field of the second dimension becomes the index,
    * and the 'name' field of the second dimension becomes the string value of the first dimension.
    * In other words, this function converts two-dimensional arrays from the database to simple arrays.
    * @param $array = the 2-dimensional database result array (returned by the database abstraction object / class)
    * @return $array = the flattened array using the 'id' and 'name' fields as index => value.
    */
    static function arrayFlatten($d){
      $a = Array(); // The new array to populate
      if(!is_array($d)) return false;
      foreach($d as $row){
        $a[$row['id']] = $row['name'];      
      }
      return $a;   
    }
    
    /**
    * This function returns the name of the first index of an array.
    * For example, an array of $var['toast'] = 'value' would return 'toast'.
    * @param array $a = the array to use.
    * @return string $k = the first key of the array.
    */
    static function arrayFirstKey($a){
      if(!is_array($a)) return false;
      foreach($a as $n => $v){ // Will only perform one iteration.
        return $n;
      }    
      return false;
    }   
    
    /**
    * Escapes single quotes for database insertion.
    */
    static function SQLEscape($x){
      return str_replace("'","''",$x);
    }
    
    // Function to convert hour from 24 hour format to 12 hour format
    static function formatHour($x){
      if($x == 0){
        return '12 am';
      } else if($x == 12){
        return '12 pm';
      } else if($x < 12) {
        return $x.' am';
      } else {
        return ($x - 12).' pm';
      }
    }
    
    // Function to convert seconds to minutes:seconds
    static function formatSeconds($x){
      $minutes = floor($x / 60);
      $seconds = sprintf("%02d",($x % 60));
      return ($minutes > 0) ? $minutes.':'.$seconds : ':'.$seconds;
    }
    
  }
  
?>