<?php
/**
* Logic for parsing the URL and creating input variables
*/

// Set error reporting
//error_reporting(E_ALL);
error_reporting(0);

// Remove everything before "go"
$input_vars = explode('go/',$_SERVER['REQUEST_URI']);
$input_vars = $input_vars[1];
$input_vars = explode('/',$input_vars);

// Create list of navigation variables
$section = strtolower(substr(preg_replace("/[^A-Za-z0-9_]/",'',$input_vars[0]),0,64));
if(!empty($input_vars[2])){ // If a page AND an item are set in the URL
  $page = substr(intval($input_vars[1]),0,6);
  $item = substr(intval($input_vars[2]),0,6);
} else {
  $item = substr(intval($input_vars[1]),0,6);
}

// Handle specific POST variables if they are present.

// Get rid of temporary data
unset($input_vars);

// On to the main page
include_once('./index.php');
?>