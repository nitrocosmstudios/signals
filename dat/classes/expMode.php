<?php 

/**
* This is an experimental digital mode.
* 8-bit values are expressed as a combination of 8 tones.
* ASCII value are zero-padded.
*/

class expMode extends audioGen{

  /**
  * Generate the tone set.
  * $f = the center frequency.
  * $d = the distance between frequencies.
  * returns an array of frequencies.
  */
  function genToneSet($f,$d){
    $toneset = Array();
    for($i=4;$i>0;$i--){
      $toneset[] = $f - ($i * $d);
    }
    for($i=0;$i<4;$i++){
      $toneset[] = $f + ($i * $d);
    }
    return $toneset;
  }
  
  /**
  * Formats and prepares the data.
  * Conversion to binary digits does not take place here.
  */
  function prepData($s){
  
    // Compress and encode payload
    $s = base64_encode(gzencode($s));
  
    $data = Array(
    
      'flag_head'   => str_repeat(chr(255),8),
      'timestamp'   => time(),
      'crc32'       => abs(crc32($s)),
      'md5'         => md5($s),
      'length'      => strlen($s),
      'format'      => 'BASE64/GZIP',
      'flag_start'  => str_repeat(chr(255),8),
      'payload'     => $s,
      'flag_end'    => str_repeat(chr(255),8),
    
    );
  
    return implode(' ',$data);
  
  }

  /**
  * Generates tone encoding for a string.
  * $s = the string to encode.
  * $f = the center frequency, in Hz.
  * $g = the tone gap (distance between tone frequencies).
  * $rate = the data rate, in symbols per second.
  * $vol = volume, in percentage.
  */
  function genSignal($s='',$f=1000,$g=200,$rate=1200,$vol=50){
  
    $s = $this->prepData($s); // Prepare the data.
  
    $o = ''; // Output waveform.
  
    $space_duration = $this->samplerate / ($rate * 10);
    $tone_duration = ($this->samplerate / $rate) - $space_duration;
    
    $fade_duration = $tone_duration / 2;
    $strlen = strlen($s);
  
    // Create the tones to be used.
    $freqs = Array();
    $freqs = $this->genToneSet($f,$g);
    
    foreach($freqs as $freq){
      $tones[] = $this->edgeFade($this->genWave('sine',$freq,$tone_duration,$vol),$fade_duration);
    }
    
    $silence = $this->genWave('silence',$freq,$tone_duration,0);
    $space = $this->genWave('silence',$freq,$space_duration,0);
  
    for($i=0;$i<$strlen;$i++){
    
      // Break the character's ASCII code down to eight (zero-padded) 1's and 0's.
      $cur_binary = sprintf("%08d",decbin(ord($s[$i])));
      
      // In the binary string, set a tone or silence for each position.
      $cur_tones = Array();
      for($k=0;$k<8;$k++){
        $cur_tones[$k] = ($cur_binary[$k] == 1) ? $tones[$k] : $silence;     
      }
      
      // Now mix the tones.
      $o .= $this->normalize($this->mixWAVArray($cur_tones),$vol).$space;
    
    }
    
    // Add tail
    $tail_duration = $tone_duration * 10;
    $tail  = $this->edgeFade($this->genWave('sine',$freqs[4],$tail_duration,$vol/2),$fade_duration);
    $tail .= $this->edgeFade($this->mix($this->genWave('sine',$freqs[0],$tail_duration,$vol/2),$this->genWave('sine',$freqs[7],$tail_duration,$vol/2)),$fade_duration);
  
    return $tail.$silence.$o;
  
  }

}

?>