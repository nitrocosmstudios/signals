<?php 

/**
* Bell 202 AFSK with AX.25 UI framing
*/

class afsk extends audioGen{

  var $default_to = 'APRS';
  var $default_to_ssid = 0;
  var $default_from = 'NOCALL';
  var $default_from_ssid = 0;

  /**
  * AX.25 CRC-CCITT (init 0xFFFF, poly 0x8408, LSB-first), complemented.
  */
  function crc16($s){
    $crc = 0xFFFF;
    $l = strlen($s);
    for($i = 0; $i < $l; $i++){
      $crc ^= ord($s[$i]);
      for($j = 0; $j < 8; $j++){
        if($crc & 1){
          $crc = ($crc >> 1) ^ 0x8408;
        } else {
          $crc >>= 1;
        }
      }
    }
    return ($crc ^ 0xFFFF) & 0xFFFF;
  }

  /**
  * Encodes one AX.25 address field (6 callsign bytes + SSID byte).
  */
  function encodeCall($call, $ssid = 0, $last = false){
    $call = strtoupper(substr($call, 0, 6));
    $call = str_pad($call, 6, ' ', STR_PAD_RIGHT);
    $o = '';
    for($i = 0; $i < 6; $i++){
      $o .= chr(ord($call[$i]) << 1);
    }
    $ssidByte = ($last ? 0x61 : 0x60) | (($ssid & 0x0F) << 1);
    $o .= chr($ssidByte);
    return $o;
  }

  function buildAddressField($to, $toSSID, $from, $fromSSID){
    return $this->encodeCall($to, $toSSID, false)
         . $this->encodeCall($from, $fromSSID, true);
  }

  /**
  * Encapsulates data in an AX.25 UI frame.
  */
  function ax25($s){
    
    $flag = '01111110';
  
    $address = $this->buildAddressField(
      $this->default_to,
      $this->default_to_ssid,
      $this->default_from,
      $this->default_from_ssid
    );
    $control = chr(0x03);
    $pid = chr(0xF0);
    $info = $s;

    $frameBody = $address . $control . $pid . $info;
    $fcs = $this->crc16($frameBody);
    $bin = $frameBody . pack('v', $fcs);
    
    $bits = $this->binString($bin);
    $bits = $this->bitStuff($bits);
    $frame = $flag . $bits . $flag;
    $preamble = str_repeat($flag, 50);
    
    return $preamble . $frame;
  
  }

  /**
  * HDLC bit stuffing: insert a 0 after five consecutive 1 bits.
  */
  function bitStuff($bits){
    $o = '';
    $ones = 0;
    $l = strlen($bits);
    for($i = 0; $i < $l; $i++){
      $bit = $bits[$i];
      $o .= $bit;
      if($bit == '1'){
        $ones++;
        if($ones == 5){
          $o .= '0';
          $ones = 0;
        }
      } else {
        $ones = 0;
      }
    }
    return $o;
  }
  
  function debugHex($s){
    $l = strlen($s);
    $o = '';
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
  
  // Converts binary data, by octet, to a string of 1's and 0's (LSB first).
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
    $b = 1; // AX.25 NRZI starts in mark (high)
    for($i=0;$i<$l;$i++){
      $b = ($s[$i] == '0') ? (($b == 0) ? 1 : 0) : $b;
      $o .= $b;
    }
    return $o;
  }

  /**
  * Returns a sample rate with an integer samples-per-bit ratio for the given baud.
  */
  function effectiveSampleRate($baud){
    if($this->samplerate % $baud == 0){
      return $this->samplerate;
    }
    foreach(Array(48000, 24000, 9600, 32000, 16000, 8000) as $rate){
      if($rate % $baud == 0){
        return $rate;
      }
    }
    return $this->samplerate;
  }

  /**
  * Linear resample of signed 16-bit PCM between sample rates.
  */
  function resample($wav, $fromRate, $toRate){
    if($fromRate == $toRate){
      return $wav;
    }
    $samples = array_values(unpack($this->bf.'*', $wav));
    $count = count($samples);
    if($count == 0){
      return $wav;
    }
    $outLen = (int)floor($count * ($toRate / $fromRate));
    $out = '';
    for($i=0;$i<$outLen;$i++){
      $srcPos = $i * ($fromRate / $toRate);
      $idx = (int)floor($srcPos);
      $frac = $srcPos - $idx;
      $s0 = $samples[$idx];
      $s1 = ($idx + 1 < $count) ? $samples[$idx + 1] : $s0;
      $out .= pack($this->bf, (int)round($s0 + ($s1 - $s0) * $frac));
    }
    return $out;
  }

  /**
  * Converts a data string into a binary sequence of 1 and 0 integers.
  */
  function prepDataStream($s){
    return $this->NRZI($this->ax25($s));
  }

  /**
  * Generates AFSK with continuous-phase Bell 202 modulation.
  * $stream = The binary stream (ones and zeros).
  * $f1 and $f2 = mark and space frequencies, respectively, in Hz.
  * $rate = data rate in baud.
  * $a = amplitude (percentage of max volume).
  */
  function genRawAFSK($stream='',$f1=1200,$f2=2200,$rate=1200,$a=75){
    
    $w = '';
    $phase = 0.0;
    $samplesPerBit = $this->samplerate / $rate;
    $stream_length = strlen($stream);
    $peak = (int)round(32767 * ($a / 100));
    
    for($i=0;$i<$stream_length;$i++){
    
      $f = ($stream[$i] == '0') ? $f2 : $f1;
      $phaseIncrement = (2 * M_PI * $f) / $this->samplerate;

      for($x=0;$x<$samplesPerBit;$x++){
        $phase += $phaseIncrement;
        $y = (int)round($peak * sin($phase));
        $w .= pack($this->bf, $y);
      }
    
    }
    
    return $w;
  }


  function genAFSK($data,$f1=1200,$f2=2200,$rate=1200,$vol=25){
    $stream = $this->prepDataStream($data);
    $targetRate = $this->samplerate;
    $genRate = $this->effectiveSampleRate($rate);
    $this->samplerate = $genRate;
    $audio = $this->genRawAFSK($stream, $f1, $f2, $rate, $vol);
    $this->samplerate = $targetRate;
    if($genRate != $targetRate){
      $audio = $this->resample($audio, $genRate, $targetRate);
    }
    return $audio;
  }

}

?>
