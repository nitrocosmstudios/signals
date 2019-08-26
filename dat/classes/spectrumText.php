<?php 

/**
* This creates text in an audio spectrum by generating tones.
*/

class spectrumText extends audioGen{

  var $char_width = 16; // Length units (pixels).
  var $char_height = 16; // Tones per character (pixels).
    
  /**
  * Gets and parses the character art lookup data.
  * The character art data is in a bitmap.
  */
  function getCharSheet(){
  
    $d = Array();  // Array to hold final character image lookup data.
    
    $w = $this->char_width;
    $h = $this->char_height;
  
    $starting_ascii_code = 32; // ASCII code for space.
    $ending_ascii_code = 95; // ASCII code for underscore.
  
    $bmp = file_get_contents('./dat/data/CharSheet.dat'); // This file is a RAW greyscale image. (no header).
    $bitmap_length = strlen($bmp);
    $bitmap_pos = 0; // Track position in bitmap.
    
    $ascii_code = $starting_ascii_code; // Track current ASCII code.
    
    while($bitmap_pos < $bitmap_length){ // Outer loop to go through entire bitmap.    
      $d[chr($ascii_code)] = Array();
      for($ky=1;$ky<=$h;$ky++){ // Loop for each line in the current character's bitmap data.
        $d[chr($ascii_code)][$ky] = Array();
        for($kx=1;$kx<=$w;$kx++){ // Loop for each pixel in the current line in the character's bitmap data.        
          $d[chr($ascii_code)][$ky][$kx] = ($bmp[$bitmap_pos] == chr(0)) ? 1 : 0; // Mark pixel as 1 or zero.  Inverted.
          $bitmap_pos++;
        }      
      }
      $ascii_code++; // Move on to next ASCII code.      
    }
    
    return $d;
  
  }
  
  // A function for quickly testing the character sheet data.
  function testCharSheet($cs){
    $o = '';
    foreach($cs as $char){
      foreach($char as $line){
        foreach($line as $pixel){
          $o .= ($pixel == 1) ? '#' : '-';        
        }
        $o .= "\n";      
      }
      $o .= "\n\n\n";
    }
    file_put_contents('./char_sheet_test.dat',$o);
  }

  /**
  * Generate the tone set.
  * $f = the center frequency.
  * $d = the distance between frequencies.
  * returns an array of frequencies.
  */
  function genToneSet($f,$d){
    $tonecount = $this->char_height; // Number of tones to make.
    $toneset = Array();
    for($i=($tonecount / 2);$i>0;$i--){
      $toneset[] = $f - ($i * $d);
    }
    for($i=0;$i<($tonecount / 2);$i++){
      $toneset[] = $f + ($i * $d);
    }
    return $toneset;
  }
  
  /**
  * Generates tone encoding for a string.
  * $s = the string to encode.
  * $f = the center frequency, in Hz.
  * $g = the tone gap (distance between tone frequencies).
  * $char_duration = the duration per character, in milliseconds.
  * $vol = volume, in percentage.
  */
  function genSpectrumText($s='',$f=1000,$g=200,$char_duration=1000,$vol=50){
  
    $o = ''; // Output waveform.
    
    $s = strtoupper($s); // Make string all caps.
    $strlen = strlen($s);
  
    $tone_duration = $char_duration / $this->char_width;
    $space_duration = $tone_duration / 2; // Spaces are 1/2 the width of characters.
    
    // Get the character art data.
    $char_art_table = $this->getCharSheet();
    // $this->testCharSheet($char_art_table); exit(); // testing character sheet data.
  
    // Create the tones to be used.
    $freqs = Array();
    $freqs = array_reverse($this->genToneSet($f,$g));
    
    foreach($freqs as $freq){
      $tones[] = $this->genWave('sine',$freq,$tone_duration,$vol);
    }
    
    $silence = $this->genWave('silence',$freq,$tone_duration,0);
    $space = $this->genWave('silence',$freq,$space_duration,0);
  
    for($i=0;$i<$strlen;$i++){
    
      // If the character is not available, skip this character.
      if(array_key_exists($s[$i],$char_art_table)){
    
        // Get matrix for character.
        $char_art = $char_art_table[$s[$i]];
                
        // WAV data for the current character.
        $cur_wav_character_lines = Array(); // Array because it will be mixed down.
        
        // Render each horizontal line for the current character.
        foreach($char_art as $line_no => $line){        
          $cur_wav_line = ''; // WAV data for the current line in the character.
          // Render each 'pixel' in the character's current horizontal line.
          foreach($line as $pixel){
            $cur_wav_line .= ($pixel == 1) ? $tones[$line_no - 1] : $silence; // Add tone segment or silence.
          }
          $cur_wav_character_lines[] = $cur_wav_line; // Add line to array for character waveform data.      
        }
        
        // Mix down the audio for the current character and add a space after it (not a character space but an inter-character space).
        $o .= $this->normalize($this->mixWAVArray($cur_wav_character_lines),$vol).$space;
      
      }
          
    }
  
    return $o;
  
  }

}

?>