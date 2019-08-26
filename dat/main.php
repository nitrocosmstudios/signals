<?php

// error_reporting(E_ALL);

require('./dat/classes/audioGen.php');
require('./dat/classes/forms.php');
require('./dat/classes/filter.php');

require('./dat/functions.php');

$cfg = parse_ini_file('./dat/config.ini');

$cfg['base_url'] = getBaseURL();

$HTML = Array();
$HTML['menu'] = buildMenu();
$HTML['main'] = '';
$HTML['description'] = '';

$default_section = 'dtmf';
$section = (!isset($section)) ? $default_section : $section;

$cfg['title'] = ucwords(str_replace('_',' ',$section));

$include = './dat/includes/'.$section.'.php';
if(file_exists($include)){
  include($include);
} else {
  include('./dat/includes/'.$default_section.'.php');
}

?>