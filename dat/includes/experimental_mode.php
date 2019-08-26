<?php 

function buildExperimentalModeForm(){

  global $cfg;

  $form_name = 'spectrum_text';
  $form = new forms();
  $fields = Array(
  
    Array(
      'id'        => 'string',
      'name'      => 'string',
      'label'     => 'String to encode:',
      'type'      => 'text',
      'length'    => 1024,
      'value'     => 'Here is an example string of data to encode.',
      'required'  => true,  
    ),
    Array(
      'id'        => 'rate',
      'name'      => 'rate',
      'label'     => 'The data (baud) rate:',
      'type'      => 'numeric',
      'length'    => 8,
      'value'     => '1200',
      'required'  => true,  
    ),
    Array(
      'id'        => 'frequency',
      'name'      => 'frequency',
      'label'     => 'Center tone frequency (in Hz):',
      'type'      => 'text',
      'length'    => 8,
      'value'     => '1000',
      'required'  => true,  
    ),
    Array(
      'id'        => 'frequency_spacing',
      'name'      => 'frequency_spacing',
      'label'     => 'Spacing between tones (in Hz):',
      'type'      => 'text',
      'length'    => 8,
      'value'     => '125',
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
      require('./dat/classes/expMode.php');
      $generator = new expMode($d['sample_rate']);
      $audio = $generator->genSignal($d['string'],$d['frequency'],$d['frequency_spacing'],$d['rate'],$d['volume']);
      $generator->addSamples($audio);      
      // Set headers to download as file.
      $filename = 'ExperimentalMode_'.date($cfg['datetime_format']).'_'.filter::alphanumeric($d['string'],16).'_'.$d['sample_rate'].'.wav';
      header('Content-Disposition: attachment; filename="'.$filename.'"');
      echo $generator->buildWAV();
      exit();
      
  } else {
  
    return $html;
    
  }
  
}

$HTML['main'] .= buildExperimentalModeForm();
$HTML['description'] .= "This is a non-standard, highly experimental encoding scheme that distributes 8 bits across 8 tones.  The data is compressed using gzip.";

?>