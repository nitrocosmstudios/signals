<?php 

/**
* Radio Teletype
*/

class rtty extends audioGen{

  var $baudot_table = Array(
  
    0   => Array('',''),
    1   => Array('E','3'),
    2   => Array("\n","\n"),
    3   => Array('A',''),
    4   => Array(' ',' '),
    5   => Array('S',''),
    6   => Array('I',8),
    7   => Array('U',7),
    8   => Array("\r","\r"),
    9   => Array('D','$'),
    10  => Array('R',4),
    11  => Array('J','\''),
    12  => Array('N',','),
    13  => Array('F','!'),
    14  => Array('C',':'),
    15  => Array('K','('),
    16  => Array('T',5),
    17  => Array('Z','"'),
    18  => Array('L',')'),
    19  => Array('W',2),
    20  => Array('H','#'),
    21  => Array('Y',6),
    22  => Array('P',0),
    23  => Array('Q',1),
    24  => Array('O',9),
    25  => Array('B','?'),
    26  => Array('G','&'),
    // 27 is the figures shift code.
    28  => Array('M','.'),
    29  => Array('X','/'),
    30  => Array('V',';'),
    // 31 is the letters shift code.
  
  );
  
  // Returns the baudot code and the relevant shift state.
  function getBaudotCode($c){
    foreach($this->baudot_table as $code => $pair){
      foreach($pair as $i => $v){
        if($v == $c){
          return Array($code,($i == 1)); // Returns the corresponding baudot code and the shift state.
        }
      }
    }
    return false;  
  }
  
  // Accepts an ASCII string and returns a baudot binary 7-bit code stream.
  function getBaudotCodeStream($s){
    $s = strtoupper($s); // RTTY text is all upper case.
    $shift = false; // False is letter mode; true is figure mode.
    $l = strlen($s);
    $o = ''; // Main output (will be string of 1's and 0's)
    
    for($i=0;$i<$l;$i++){
    
      if($baudot = $this->getBaudotCode($s[$i]) !== false){
      
        list($code,$shift_state) = $baudot;

        // Handle letter / figure shift transitions.
        if($shift !== $shift_state){
          $shift = $shift_state;
          $c = $shift ? 27 : 31;
          $o .= '1'.strrev(decbin($c)).'0';
        }
      
        $o .= '1'.strrev(decbin($code)).'0';  
  
      }
    }
    
    return $o;
    
  }

  /**
  * Generates AFSK for RTTY.
  * $stream = The binary stream. (ones and zeros).
  * $f = the center frequency, in Hz.
  * $shift = the frequency shift, in Hz.
  * $rate = data rate.  will determine frequencies.
  * $a = amplitude (percentage of max volume).
  */
  function genRawAFSK($stream='',$f=1000,$shift=100,$rate=45,$a=75){
    
    $w = ''; // Wave data
    $p = 0; // Phase
       
    $l = $this->samplerate / $rate; // Length in samples.    
    $stream_length = strlen($stream);
    
    $f1 = $f + round($shift / 2);
    $f2 = $f - round($shift / 2);
    
    $f = $f1;    
    
    for($i=0;$i<$stream_length;$i++){
    
      $pf = $f;
    
      $f = ($stream[$i] == 0) ? $f2 : $f1;
      
      $fm = (($f * (M_PI * 2)) / $this->samplerate); // Frequency multiplier

      $start = round(($this->samplerate / $f) * ($p / ($this->samplerate / $pf))) + 0;
      $stop = $l + $start;
      for($x=$start;$x<=$stop;$x++){
            
        $ym = sin($x * $fm); // Calculate sample value multiplier
        
        // Only create wav data and increment counter once phase is matched.
        $y = $this->max_sample_value * $ym; // Calculate current sample.
        $y = $this->signed2un($y); // Convert from unsigned value to signed value.
        $y = round(($a / 100) * $y); // Adjust volume.
        $w .= pack($this->bf,$y); // Add sample
        
        $p = ($x == 0) ? 0 : $x % ($this->samplerate / $f);
        
      }
    
    }
    
    return $w;
  }


  function genRTTY($data,$f=1000,$shift=100,$rate=45,$vol=25){
    $stream = $this->getBaudotCodeStream($data);
    return $this->genRawAFSK($stream,$f,$shift,$rate,$vol);    
  }








}

?>