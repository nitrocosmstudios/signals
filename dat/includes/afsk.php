<?php 

function buildAFSKForm(){

  global $cfg;

  $form_name = 'afsk_form';
  $form = new forms();
  $fields = Array(
  
    Array(
      'id'        => 'string',
      'name'      => 'string',
      'label'     => 'String to encode (any valid character in morse code):',
      'type'      => 'text',
      'length'    => 255,
      'value'     => 'Here is some sample text to encode.',
      'required'  => true,  
    ),
    Array(
      'id'        => 'from_call',
      'name'      => 'from_call',
      'label'     => 'Source callsign (AX.25 "from", up to 6 characters):',
      'type'      => 'text',
      'length'    => 6,
      'value'     => 'NOCALL',
      'required'  => true,  
    ),
    Array(
      'id'        => 'baud',
      'name'      => 'baud',
      'label'     => 'Baud rate (will conform to Bell standards):',
      'type'      => 'text',
      'length'    => 8,
      'value'     => '1200',
      'required'  => true,  
    ),
    Array(
      'id'        => 'mark_freq',
      'name'      => 'mark_freq',
      'label'     => '"Mark" frequency:',
      'type'      => 'text',
      'length'    => 8,
      'value'     => '1200',
      'required'  => true,  
    ),
    Array(
      'id'        => 'space_freq',
      'name'      => 'space_freq',
      'label'     => '"Space" frequency:',
      'type'      => 'text',
      'length'    => 8,
      'value'     => '2200',
      'required'  => true,  
    ),
    Array(
      'id'        => 'sample_rate',
      'name'      => 'sample_rate',
      'label'     => 'Audio Sample Rate:',
      'type'      => 'menu',
      'value'     => '48000',
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
      require('./dat/classes/afsk.php');
      $generator = new afsk($d['sample_rate']);
      $generator->default_from = filter::alphanumeric($d['from_call'],6);
      $audio = $generator->genAFSK($d['string'],$d['mark_freq'],$d['space_freq'],$d['baud'],$d['volume']);
      $generator->addSamples($audio);      
      // Set headers to download as file.
      $filename = 'AFSK_'.date($cfg['datetime_format']).'_'.filter::alphanumeric($d['string'],16).'_'.$d['sample_rate'].'.wav';
      header('Content-Disposition: attachment; filename="'.$filename.'"');
      echo $generator->buildWAV();
      exit();
      
  } else {
  
    return $html;
    
  }
  
}

$HTML['main'] .= buildAFSKForm();
$HTML['description'] .= "AFSK Bell 202 with AX.25 UI framing (APRS-compatible). Decode with multimon-ng or Dire Wolf, not minimodem: <code>multimon-ng -a AFSK1200 -t wav file.wav</code>. Use 48000 Hz sample rate for best results at 1200 baud.";

?>