<?php 

/**
* Bell 202 AFSK
*/

class afsk extends audioGen{

  function crc16($s){

    $crctab16 = Array(
    
      0X0000, 0XC0C1, 0XC181, 0X0140, 0XC301, 0X03C0, 0X0280, 0XC241,
      0XC601, 0X06C0, 0X0780, 0XC741, 0X0500, 0XC5C1, 0XC481, 0X0440,
      0XCC01, 0X0CC0, 0X0D80, 0XCD41, 0X0F00, 0XCFC1, 0XCE81, 0X0E40,
      0X0A00, 0XCAC1, 0XCB81, 0X0B40, 0XC901, 0X09C0, 0X0880, 0XC841,
      0XD801, 0X18C0, 0X1980, 0XD941, 0X1B00, 0XDBC1, 0XDA81, 0X1A40,
      0X1E00, 0XDEC1, 0XDF81, 0X1F40, 0XDD01, 0X1DC0, 0X1C80, 0XDC41,
      0X1400, 0XD4C1, 0XD581, 0X1540, 0XD701, 0X17C0, 0X1680, 0XD641,
      0XD201, 0X12C0, 0X1380, 0XD341, 0X1100, 0XD1C1, 0XD081, 0X1040,
      0XF001, 0X30C0, 0X3180, 0XF141, 0X3300, 0XF3C1, 0XF281, 0X3240,
      0X3600, 0XF6C1, 0XF781, 0X3740, 0XF501, 0X35C0, 0X3480, 0XF441,
      0X3C00, 0XFCC1, 0XFD81, 0X3D40, 0XFF01, 0X3FC0, 0X3E80, 0XFE41,
      0XFA01, 0X3AC0, 0X3B80, 0XFB41, 0X3900, 0XF9C1, 0XF881, 0X3840,
      0X2800, 0XE8C1, 0XE981, 0X2940, 0XEB01, 0X2BC0, 0X2A80, 0XEA41,
      0XEE01, 0X2EC0, 0X2F80, 0XEF41, 0X2D00, 0XEDC1, 0XEC81, 0X2C40,
      0XE401, 0X24C0, 0X2580, 0XE541, 0X2700, 0XE7C1, 0XE681, 0X2640,
      0X2200, 0XE2C1, 0XE381, 0X2340, 0XE101, 0X21C0, 0X2080, 0XE041,
      0XA001, 0X60C0, 0X6180, 0XA141, 0X6300, 0XA3C1, 0XA281, 0X6240,
      0X6600, 0XA6C1, 0XA781, 0X6740, 0XA501, 0X65C0, 0X6480, 0XA441,
      0X6C00, 0XACC1, 0XAD81, 0X6D40, 0XAF01, 0X6FC0, 0X6E80, 0XAE41,
      0XAA01, 0X6AC0, 0X6B80, 0XAB41, 0X6900, 0XA9C1, 0XA881, 0X6840,
      0X7800, 0XB8C1, 0XB981, 0X7940, 0XBB01, 0X7BC0, 0X7A80, 0XBA41,
      0XBE01, 0X7EC0, 0X7F80, 0XBF41, 0X7D00, 0XBDC1, 0XBC81, 0X7C40,
      0XB401, 0X74C0, 0X7580, 0XB541, 0X7700, 0XB7C1, 0XB681, 0X7640,
      0X7200, 0XB2C1, 0XB381, 0X7340, 0XB101, 0X71C0, 0X7080, 0XB041,
      0X5000, 0X90C1, 0X9181, 0X5140, 0X9301, 0X53C0, 0X5280, 0X9241,
      0X9601, 0X56C0, 0X5780, 0X9741, 0X5500, 0X95C1, 0X9481, 0X5440,
      0X9C01, 0X5CC0, 0X5D80, 0X9D41, 0X5F00, 0X9FC1, 0X9E81, 0X5E40,
      0X5A00, 0X9AC1, 0X9B81, 0X5B40, 0X9901, 0X59C0, 0X5880, 0X9841,
      0X8801, 0X48C0, 0X4980, 0X8941, 0X4B00, 0X8BC1, 0X8A81, 0X4A40,
      0X4E00, 0X8EC1, 0X8F81, 0X4F40, 0X8D01, 0X4DC0, 0X4C80, 0X8C41,
      0X4400, 0X84C1, 0X8581, 0X4540, 0X8701, 0X47C0, 0X4680, 0X8641,
      0X8201, 0X42C0, 0X4380, 0X8341, 0X4100, 0X81C1, 0X8081, 0X4040,
      
    );

    $nLength = strlen($s);
    $fcs = 0xFFFF;
    $pos = 0;
    while($nLength > 0){
      $fcs = ($fcs >> 8) ^ $crctab16[($fcs ^ ord($s[$pos])) & 0xFF];
      $nLength--;
      $pos++;
    }
    $crc_semi_inverted = sprintf('%04X', $fcs);//modbus crc invert the high and low bit so we need to put the last two letter in the begining
    $crc = substr($crc_semi_inverted,2,2).substr($crc_semi_inverted,0,2);
    return $crc;
  }
  
  function buildAddressField($to,$from){
    $o = '';
    $o .= pack('A*',sprintf("%-6s",$to));
    $o .= pack('v',0xE0);
    $o .= pack('A*',sprintf("%-6s",$from));
    $o .= pack('v',0x61);
    return $o;
  }
  
  /** 
  * Encapsulates data in an ax.25 packet.
  * I don't entirely know what I'm doing here so this will change soon.
  */
  function ax25($s){
    
    $null = '00000000';
    $flag = '01111110';
  
    $bin_fields = Array(
      'address'   => $this->buildAddressField('KB8RDM','KD8FUD'),
      'control'   => pack('v',0x3E),
      'PID'       => pack('v',0xF0),
      'Info'      => pack('A*',$s),
      'Test'      => pack('A*',str_repeat('A',100)),
      'FCS'       => '',  
    );
    
    $bin_fields['FCS'] = pack('v*',$this->crc16(implode('',array_values($bin_fields)))); // This really does need to run twice (so the checksum itself is included).
    $bin_fields['FCS'] = pack('v*',$this->crc16(implode('',array_values($bin_fields)))); // This really does need to run twice (so the checksum itself is included).
    
    $bin = implode('',array_values($bin_fields));
    
    // $this->debugHex($bin); // Testing.
    
    $bin = $this->binString($bin); // Serialize as string of 1's and 0's.
    $bin = str_replace('11111','111110',$bin); // Bit stuffing (add a '0' to the end of every '11111')
    $bin = $flag.$bin.$flag; // Wrap in flags.
    
    $preamble = str_repeat($null,20).str_repeat($flag,100); // Create preamble (20 'NULL' octets and 100 flags).
    
    return $preamble.$bin; // Return binary stream with preamble.
  
  }
  
  function debugHex($s){
    $l = strlen($s);
    $o = '';
    $t = '';
    for($i=1;$i<=$l;$i++){
      if($i % 8 == 0){
        $o .= sprintf("%02s",bin2hex($s[$i-1]))."\t".substr($s,$i-8,8)."\n";
      } else if($i == $l){
        $r = ($i % 8);
        $o .= sprintf("%02s",bin2hex($s[$i-1])).str_repeat("\t",$r-1).substr($s,$i-$r,$r-1)."\n";
      } else {
        $o .= sprintf("%02s",bin2hex($s[$i-1]))."\t";
      }
    }
    file_put_contents('debug.txt',$o);
  }
  
  // Converts binary data, by octet, to a string of 1's and 0's.
  function binString($d){
    $o = '';
    $l = strlen($d);
    for($i=0;$i<$l;$i++){
      $x = ord($d[$i]);
      $o .= strrev(sprintf("%08d",decbin($x)));
    }
    return $o;
  }
  
  function NRZI($s){
    $o = '';
    $l = strlen($s);
    $b = 0;
    for($i=0;$i<$l;$i++){
      $b = ($s[$i] == 0) ? (($b == 0) ? 1 : 0) : $b;
      $o .= $b;
    }
    return $o;
  }

  /**
  * Converts a data string into a binary sequence of 1 and 0 integers.
  */
  function prepDataStream($s){
    return $this->NRZI($this->ax25($s));
  }

  /**
  * Generates AFSK.
  * $stream = The binary stream. (ones and zeros).
  * $f1 and $f2 = low and high frequencies, respectively, in Hz.
  * $rate = data rate.  will determine frequencies.
  * $a = amplitude (percentage of max volume).
  */
  function genRawAFSK($stream='',$f1=1200,$f2=2200,$rate=1200,$a=75){
    
    $w = ''; // Wave data
    $p = 0; // Phase
       
    $l = $this->samplerate / $rate; // Length in samples.    
    $stream_length = strlen($stream);
    
    $f = $f1;    
    
    for($i=0;$i<$stream_length;$i++){
    
      $pf = $f;
    
      $f = ($stream[$i] == 0) ? $f2 : $f1;
      
      $fm = (($f * (M_PI * 2)) / $this->samplerate); // Frequency multiplier

      $start = round(($this->samplerate / $f) * ($p / ($this->samplerate / $pf))) + 1;
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


  function genAFSK($data,$f1=1200,$f2=2200,$rate=1200,$vol=25){
    $stream = $this->prepDataStream($data);
    return $this->genRawAFSK($stream,$f1,$f2,$rate,$vol);    
  }








}

?>