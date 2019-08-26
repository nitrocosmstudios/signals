<?php

function getSampleRates(){
  $d = Array();
  for($i=1;$i<=6;$i++){
    $d[] = 8000 * $i;
  }
  for($i=1;$i<=4;$i++){
    $d[] = 11025 * $i;
  }
  sort($d);
  return $d;
}

function getVolumes(){
  $d = Array();
  for($i=0;$i<=10;$i++){
    $d[] = $i * 10;
  }
  return $d;
}

/**
* Converts paths in configuration to implement HTTPS if the current URL uses HTTPS.
*/
function checkPathHTTPS(){
  return !empty($_SERVER['HTTPS']);
}

// Determines the base URL.
function getBaseURL(){
  $url = ((checkPathHTTPS()) ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
  if(strpos($url,'/go/') !== false){
    $url = explode('/go/',$url);
    return $url[0].'/';
  } else {
    return $url;
  }
}

function buildMenu(){  
  global $cfg;
  $o  = '';
  $o .= "<ul>\n";  
  $files = scandir('./dat/includes/');
  foreach($files as $file){
    if(($file != '.') && ($file != '..')){
      $sec = substr($file,0,-4);
      $label = strtoupper(str_replace('_',' ',$sec));
      $url = $cfg['base_url'].'go/'.$sec.'/';
      $o .= "<li><a href=\"".$url."\" title=\"".$label."\">".$label."</a></li>\n";
    }
  }
  $o .= "</ul>\n";
  return $o;
}



?>