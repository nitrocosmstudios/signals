<?php 

class mf extends audioGen{

  /**
  * DTMF tone tables
  */

  var $first_freqs = Array(
      '700'  => '1,2,4,7',
      '900'  => '3,5,8,A,C',
      '1100' => '6,9,*',
      '1300' => '0,B',
      '1500' => '#',
      );
    
  var $second_freqs = Array(
      '900'  => '1',
      '1100' => '2,3',
      '1300' => '4,5,6',
      '1500' => '7,8,9,0',
      '1700' => '*,#,A,B,C',
      );
      
  var $valid_chars = Array(0,1,2,3,4,5,6,7,8,9,'*','#','A','B','C','D');

  /**
  * Generates a dial tone.
  * $d = the duration, in milliseconds.
  * $v = volume, in percentage of total amplitude.
  */
  function genDialTone($d,$v){
  
    $sine350 = $this->genWave('sine',350,$d,$v);
    $sine440 = $this->genWave('sine',440,$d,$v);
    return $this->mix($sine350,$sine440);
  
  }
  
  /**
  * Generates a MF tone for a given character.
  * $c = The character for which the MF tone is made - 0-9, *, #, and A,B,C.
  * $d = The duration of the tones (in milliseconds).
  * $v = The volume (in percentage).
  * $fade = The edge fade time, in milliseconds.
  */
  function genMFTone($c,$d=1000,$v=50,$fade=10){
  
    $first_freq = 0;
    $second_freq = 0;
  
    
    if(!in_array($c,$this->valid_chars)) return false;
    
    // Look up first tones for character.
    foreach($this->first_freqs as $freq => $chars){
      $chars = explode(',',$chars);
      if(in_array($c,$chars)){
        $first_freq = $freq;
        break;
      }    
    }
    
    // Look up second tones for character.
    foreach($this->second_freqs as $freq => $chars){
      $chars = explode(',',$chars);
      if(in_array($c,$chars)){
        $second_freq = $freq;
        break;
      }    
    }
    
    $first = $this->genWave('sine',$first_freq,$d,$v);
    $second = $this->genWave('sine',$second_freq,$d,$v);
    
    // Add edge fading to avoid popping
    $first = $this->edgeFade($first,$fade);
    $second = $this->edgeFade($second,$fade);
    
    return $this->mix($first,$second);
  
  }
  
  /**
  * Generates a series of MF tones for a given string.
  * $s = The string of characters.  Invalid ones will be skipped.
  * $d = The total duration of the string (in seconds).
  * $v = The volume, in percentage.
  * $fade = The edge fade amount, in milliseconds.
  * $gap = The gap time between characters, in milliseconds.
  */
  function genMF($s,$d=2,$v=75,$fade=10,$gap=40){
  
    // Calculate per-character duration.
    $d = round(($d * 1000) / (strlen($s) + $gap));
    $l = strlen($s);
  
    $wav = ''; // The WAV data output.
    
    for($i=0;$i<$l;$i++){      
      if(in_array($s[$i],$this->valid_chars)){;
        $wav .= $this->genMFTone($s[$i],$d,$v,$fade);    
        $wav .= $this->gap($gap);
      }
    }
  
    return $wav;
  
  }
  
  /**
  * Generates an audio silence gap.
  * $d = duration in ms.
  */
  function gap($d){
    return $this->genWave('silence',0,$d,0);
  }











}

?>