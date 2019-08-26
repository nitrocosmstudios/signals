<?php 

class audioGen{  

  var $max_sample_value = 65536; // Unsigned.  will be converted.
  var $samplerate = 44100; // In Hertz.
  var $wavdata = '';
  var $bf = 's'; // Signed short, little endian
  
  function __construct($samplerate=44100){
    $this->samplerate = $samplerate;  
  }
  
  /**
  * Builds the header and final output for the WAV file.
  */
  function buildWAV(){
  
    //Create first subchunk
    $subchunk1 = pack("NVvvVVvv", 0x666d7420, 16, 1, 1, $this->samplerate, $this->samplerate, 1, 16);
    //Create second subchunk
    $subchunk2 = pack("NV", 0x64617461, strlen($this->wavdata));
    $subchunk2 .= $this->wavdata;
    //Build chunk descriptor
    $h = pack("NVN", 0x52494646, (36 + strlen($subchunk2)), 0x57415645);
    //Construct file
    $output = $h.$subchunk1.$subchunk2;    
    return $output;
  
  }
  
  function signed2un($x){
    return 0 - ($x / 2) + $x;
  }
  
  /**
  * Adds audio samples to wave data.
  */
  function addSamples($w){
    $this->wavdata .= $w;
  }

  /**
  * Generates a wave.
  * $type = type of wave (sine, square, triangle, saw, etc.).
  * $f = frequency of wave, in Hertz.
  * $d = duration of wave, in milliseconds.
  * $a = amplitude (percentage of max volume).
  */
  function genWave($type='sine',$f=440,$d=1000,$a=75){
  
    $w = ''; // Wave data
    $l = ($this->samplerate / 1000) * $d; // Length in samples.
    $fm = (($f * (M_PI * 2)) / $this->samplerate); // Frequency multiplier

    for($x=0;$x<=$l;$x++){
      
      switch ($type){
      
        case 'silence':
        
          $ym = 0; // That was easy.
        
        break;
      
        case 'square':
        
          $ym = ((sin($x * $fm)) >= 0) ? 1 : -1; // Calculate sample value multiplier
        
        break;
        
        case 'saw':
        
          $ym = ((($x * $fm) - (2 * M_PI * floor((($x * $fm) / M_PI) / 2))) / M_PI) / 2; // This works but needs a proper DC offset.
        
        break;
        
        case 'sine':
        default:
        
          $ym = sin($x * $fm); // Calculate sample value multiplier
         
        break;   
      
      }   
    
      $y = $this->max_sample_value * $ym; // Calculate current sample.
      $y = $this->signed2un($y); // Convert from unsigned value to signed value.
      $y = round(($a / 100) * $y); // Adjust volume.
      $w .= pack($this->bf,$y); // Add sample
      
    }
    
    return $w;
  }
  
  /**
  * Helper function for mixWAV: Blends two samples together.
  */
  function blendSamples($a,$b){
    return ($a + $b) / 2;
  }
  
  /**
  * Normalizes a waveform to an amplitude without clipping.
  * $w = the wave data.
  * $v = the maxiumum volume.
  */
  function normalize($w,$v){
    $c = ''; // Output
    $w = array_values(unpack($this->bf.'*',$w));
    if(max($w) != 0){
      $adj = ((($this->max_sample_value / 2) / max($w)) * ($v / 100)) / 2;
    } else {
      $adj = 1;
    }
    foreach($w as $sam){
      $polarity = ($sam < ($this->max_sample_value / 2)) ? 1 : -1;
      $c .= pack($this->bf,round($sam * ($adj * $polarity)));
    }
    return $c;  
  }
  
  /**
  * Mixes two waveforms together.
  */
  function mix($a,$b){
    
    // Unpack waveforms and convert arrays to indexed instead of associative.
    $a = array_values(unpack($this->bf.'*',$a));
    $b = array_values(unpack($this->bf.'*',$b));
    $c = ''; // will hold result.
    
    // Determine which of the two is longer and mix the shortest into the longest.
    if(count($a) > count($b)){      
      foreach($a as $ai => $av){
        $c .= pack($this->bf,$this->blendSamples($av,$b[$ai]));      
      }
    } else {
      foreach($b as $bi => $bv){
        $c .= pack($this->bf,$this->blendSamples($bv,$a[$bi]));
      }
    }
  
    return $c;  
  
  }
  
  /**
  * Mixes multiple waveforms together.
  * NOTE:  All waveforms must be the same length!
  */
  function mixWAVArray($wav_array){
  
    $r = ''; // will hold result.
    
    // Unpack waveforms and convert arrays to indexed instead of associative.
    foreach($wav_array as $n => $wavdata){
      $wav_array[$n] = array_values(unpack($this->bf.'*',$wavdata));
    }
        
    $wav_count = count($wav_array);
    $sam_count = count($wav_array[0]);
    
    for($i=0;$i<$sam_count;$i++){
      $samples = Array();
      for($k=0;$k<$wav_count;$k++){
        $samples[] = $wav_array[$k][$i];
      }
      $averaged_sample = array_sum($samples) / count($samples);
      $r .= pack($this->bf,$averaged_sample);
    }
  
    return $r;  
  
  }
  
  /**
  * Fades the edges of wav data.
  * $w = the wav data.
  * $d = the duration of the fades, in milliseconds.
  */
  function edgeFade($w,$d=20){
      
    // Unpack waveform and convert array to indexed instead of associative.
    $w = array_values(unpack($this->bf.'*',$w));
  
    // Get the length of the wav data.
    $l = count($w);
    
    // Fade the beginning.
    for($i=0;$i<=$d;$i++){
      $a = $i / $d; // Amplitude adjustment factor.
      $w[$i] = $w[$i] * $a; // Adjust amplitude of sample.
    }
    
    $w = array_reverse($w);
    
    // Fade the end.
    for($i=0;$i<=$d;$i++){
      $a = $i / $d; // Amplitude adjustment factor.
      $w[$i] = $w[$i] * $a; // Adjust amplitude of sample.
    }
    
    $w = array_reverse($w);
    
    // Re-pack and return!
    $output = '';
    foreach($w as $ws){
      $output .= pack($this->bf,$ws);
    }
  
    return $output;
  
  }

}

?>