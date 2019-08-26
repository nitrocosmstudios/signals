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
      require('./dat/classes/afsk.php');
      $generator = new afsk($d['sample_rate']);
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
$HTML['description'] .= "AFSK is typically used in dial-up modems, APRS, EAS SAME, and caller ID.  Please note that this version is an imperfect, experimental implementation.";

?>