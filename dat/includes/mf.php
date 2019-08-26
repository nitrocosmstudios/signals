<?php 

function buildMFForm(){

  global $cfg;

  $form_name = 'mf_form';
  $form = new forms();
  $fields = Array(
  
    Array(
      'id'        => 'string',
      'name'      => 'string',
      'label'     => 'String to encode (0-9,#,*,A,B,C):',
      'type'      => 'text',
      'length'    => 64,
      'value'     => '1234567890*#',
      'required'  => true,  
    ),
    Array(
      'id'        => 'duration',
      'name'      => 'duration',
      'label'     => 'Total duration (in seconds):',
      'type'      => 'numeric',
      'length'    => 8,
      'value'     => '2',
      'required'  => true,  
    ),
    Array(
      'id'        => 'number_spacing',
      'name'      => 'number_spacing',
      'label'     => 'Digit spacing (in milliseconds):',
      'type'      => 'text',
      'length'    => 64,
      'value'     => '40',
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
      require('./dat/classes/mf.php');
      $generator = new mf($d['sample_rate']);
      $audio = $generator->genMF($d['string'],$d['duration'],$d['volume'],$d['number_spacing'],$d['edge_fading']);
      $generator->addSamples($audio);      
      // Set headers to download as file.
      $filename = 'MF_'.date($cfg['datetime_format']).'_'.filter::alphanumeric($d['string'],16).'_'.$d['sample_rate'].'.wav';
      header('Content-Disposition: attachment; filename="'.$filename.'"');
      echo $generator->buildWAV();
      exit();
      
  } else {
  
    return $html;
    
  }
  
}

$HTML['main'] .= buildMFForm();
$HTML['description'] .= "MF tones are the predecessor of DTMF tones.  Both were used in telephone systems before the digital conversion.  MF tones are rare since they have been replaced by out-of-band digital signals in modern systems.";

?>