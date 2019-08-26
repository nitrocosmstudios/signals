<?php 

class morse extends audioGen{

  var $codes = Array(
      "A" => '.-',
      "B" => '-...',
      "C" => '-.-.',
      "D" => '-..',
      "E" => '.',
      "F" => '..-.',
      "G" => '--.',
      "H" => '....',
      "I" => '..',
      "J" => '.---',
      "K" => '-.-',
      "L" => '.-..',
      "M" => '--',
      "N" => '-.',
      "O" => '---',
      "P" => '.--.',
      "Q" => '--.-',
      "R" => '.-.',
      "S" => '...',
      "T" => '-',
      "U" => '..-',
      "V" => '...-',
      "W" => '.--',
      "X" => '-..-',
      "Y" => '-.--',
      "Z" => '--..',
      "Á" => '.--.-',
      "Ä" => '.-.-',
      "Å" => '.--.-',
      "É" => '..-..',
      "Ñ" => '--.--',
      "Ö" => '---.',
      "Ü" => '..--',
      "1" => '.----',
      "2" => '..---',
      "3" => '...--',
      "4" => '....-',
      "5" => '.....',
      "6" => '-....',
      "7" => '--...',
      "8" => '---..',
      "9" => '----.',
      "0" => '-----',
      "," => '--..--',
      "." => '.-.-.-',
      "?" => '..--..',
      ";" => '-.-.-.',
      ":" => '---...',
      "/" => '-..-.',
      "-" => '-....-',
      "'" => '.----.',
      "()" => '-.--.-',
      "_" => '..--.-',
      "\"" => '.-..-.',
      "@" => '.--.-.',
      "=" => '-...-',
      " " => ' ',
    );
  
  /**
  * Generates an audio silence gap.
  * $d = duration in ms.
  */
  function gap($d){
    return $this->genWave('silence',0,$d,0);
  }
  
  /**
  * Morse code generator!
  * $s = string to encode.  Invalid characters will be ignored.
  * $wpm = the speed of the code.  Defaults to 15 wpm.
  * $freq = The frequency of the tone, in Hertz..  Defaults to 1 kHz.
  * $vol = The volume, as a percentage.  Defaults to 50%.
  * $fade = Fade time to soften the edges of dits and dahs.  In milliseconds.
  */
  function genMorse($s,$wpm=15,$freq=1000,$vol=50,$fade=10){
  
    $wav = ''; // Output wav data.
  
    // Calculate element length based on specified WPM
    $elen = (60 / ($wpm * 50)) * 1000;
    
    // Create wav snippets for elements
    $elements = Array(
      '.'    => $this->edgeFade($this->genWave('sine',$freq,$elen*1,$vol),$fade).$this->gap($elen*1),
      '-'    => $this->edgeFade($this->genWave('sine',$freq,$elen*3,$vol),$fade).$this->gap($elen*1),
      ' '    => $this->gap($elen*4),
    );
    
    $s = strtoupper($s);
    
    $strlen = strlen($s);
    for($i=0;$i<$strlen;$i++){
    
      if(array_key_exists($s[$i],$this->codes)){
      
        $code = $this->codes[$s[$i]];
        $codelen = strlen($code);
        
        for($k=0;$k<$codelen;$k++){

          $wav .= $elements[$code[$k]];
        
        }
        
        $wav .= $this->gap($elen*3); // Inter-character spacing.
      
      }    
      
    }
    
    return $wav; 
  
  }











}

?>