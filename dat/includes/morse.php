<?php 

function buildMorseForm(){

  global $cfg;

  $form_name = 'morse_form';
  $form = new forms();
  $fields = Array(
  
    Array(
      'id'        => 'string',
      'name'      => 'string',
      'label'     => 'String to encode (any valid character in morse code):',
      'type'      => 'text',
      'length'    => 64,
      'value'     => 'PARIS PARIS PARIS PARIS PARIS',
      'required'  => true,  
    ),
    Array(
      'id'        => 'wpm',
      'name'      => 'wpm',
      'label'     => 'Speed in Words Per Minute (WPM):',
      'type'      => 'numeric',
      'length'    => 8,
      'value'     => '15',
      'required'  => true,  
    ),
    Array(
      'id'        => 'frequency',
      'name'      => 'frequency',
      'label'     => 'Tone frequency (in Hz):',
      'type'      => 'text',
      'length'    => 8,
      'value'     => '440',
      'required'  => true,  
    ),
    Array(
      'id'        => 'edge_fading',
      'name'      => 'edge_fading',
      'label'     => 'Amount to fade edges of tones (in milliseconds):',
      'type'      => 'text',
      'length'    => 64,
      'value'     => '40',
      'required'  => true,  
    ),
    Array(
      'id'        => 'sample_rate',
      'name'      => 'sample_rate',
      'label'     => 'Audio Sample Rate:',
      'type'      => 'menu',
      'value'     => '16000',
      'required'  => false,
      'values'    => getSampleRates(),
      'onchange'  => "",
    ),
    Array(
      'id'        => 'volume',
      'name'      => 'volume',
      'label'     => 'Volume:',
      'type'      => 'menu',
      'value'     => '50',
      'required'  => false,
      'values'    => getVolumes(),
      'onchange'  => "",
    ),
    
  );

  $html = $form->buildForm($fields,$form_name,$form_name);
  
  if($form->isValid()){
  
      $d = $form->getData();
      require('./dat/classes/morse.php');
      $generator = new morse($d['sample_rate']);
      $audio = $generator->genMorse($d['string'],$d['wpm'],$d['frequency'],$d['volume'],$d['edge_fading']);
      $generator->addSamples($audio);      
      // Set headers to download as file.
      $filename = 'CW_'.date($cfg['datetime_format']).'_'.filter::alphanumeric($d['string'],16).'_'.$d['sample_rate'].'.wav';
      header('Content-Disposition: attachment; filename="'.$filename.'"');
      echo $generator->buildWAV();
      exit();
      
  } else {
  
    return $html;
    
  }
  
}

$HTML['main'] .= buildMorseForm();
$HTML['description'] .= "Morse code is the original digital signal encoding and is human readable... with a little practice.";

?>