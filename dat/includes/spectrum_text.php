<?php 

set_time_limit(120);

function buildSpectrumTextForm(){

  global $cfg;

  $form_name = 'spectrum_text';
  $form = new forms();
  $fields = Array(
  
    Array(
      'id'        => 'string',
      'name'      => 'string',
      'label'     => 'String to encode:',
      'type'      => 'text',
      'length'    => 2048,
      'value'     => 'HELLO, MY NAME IS: ',
      'required'  => true,  
    ),
    Array(
      'id'        => 'char_duration',
      'name'      => 'char_duration',
      'label'     => 'The duration of each character (in milliseconds):',
      'type'      => 'numeric',
      'length'    => 8,
      'value'     => '250',
      'required'  => true,  
    ),
    Array(
      'id'        => 'frequency',
      'name'      => 'frequency',
      'label'     => 'Center tone frequency (in Hz):',
      'type'      => 'text',
      'length'    => 8,
      'value'     => '2000',
      'required'  => true,  
    ),
    Array(
      'id'        => 'frequency_spacing',
      'name'      => 'frequency_spacing',
      'label'     => 'Spacing between tones (in Hz):',
      'type'      => 'text',
      'length'    => 8,
      'value'     => '200',
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
      require('./dat/classes/spectrumText.php');
      $generator = new spectrumText($d['sample_rate']);
      $audio = $generator->genSpectrumText($d['string'],$d['frequency'],$d['frequency_spacing'],$d['char_duration'],$d['volume']);
      $generator->addSamples($audio);      
      // Set headers to download as file.
      $filename = 'SpectrumText_'.date($cfg['datetime_format']).'_'.filter::alphanumeric($d['string'],16).'_'.$d['sample_rate'].'.wav';
      header('Content-Disposition: attachment; filename="'.$filename.'"');
      echo $generator->buildWAV();
      exit();
      
  } else {
  
    return $html;
    
  }
  
}

$HTML['main'] .= buildSpectrumTextForm();
$HTML['description'] .= "This format \"paints\" text in an audio signal in such a fashion that viewing the audio in an FFT (spectrogram) will display the text.";

?>