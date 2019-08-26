<?php

/**
* PHP Form Generator
* @author Troy W. McQuinn
* @date 2008-12-04
* @abstract
*
* This class generates HTML forms in PHP, including form input validation and filtering of user data.
* The purpose is to eliminate tedious and repetitive coding for creating forms so that more development
* time can be devoted to business logic and customization.
*
*/

$tabIndex = 0;

class forms{

  /*****************
  * PROPERTIES
  *****************/
  
  var $valid = true; // Keeps track of validation status
  var $submitted = false; // Keeps track of whether or not the form was submitted
  
  var $action_url = '';

  var $fields = Array(); // Stores all information pertaining to fields in the form
  var $errors = Array(); // Stores validation error messages.  If this array is empty, the form is considered valid.
  var $messages = Array(); // Stores non-error messages.
  var $error_fields = Array(); // Stores validation error field ids
  var $elements = Array(); // Stores the id, name, and type of each element in the form
  var $form_open = "<form id=\"<!--ID-->\" name=\"<!--NAME-->\" action=\"<!--ACTION-->\" method=\"POST\" enctype=\"<!--ENCTYPE-->\" onSubmit=\"return validateForm();\" />\n";
  var $form_close = "</form>\n";
  var $element_template = "<div class=\"form_element\"><!--LABEL--> <!--ELEMENT--></div>\n"; // The HTML template that each element should be wrapped in.
  var $upload_flag = false; // This is set to true only if a file upload field exists in the form, but is set whether something was uploaded or not.
  
  // Static values
  var $states = Array('AL','AK','AS','AZ','AR','CA','CO','CT','DE','DC','FM','FL','GA','GU','HI','ID','IL','IN','IA','KS','KY','LA','ME','MH','MD','MA','MI','MN','MS','MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','MP','OH','OK','OR','PW','PA','PR','RI','SC','SD','TN','TX','UT','VT','VI','VA','WA','WV','WI','WY','AE','AA','AE','AP');
  var $months = Array('January','February','March','April','May','June','July','August','September','October','November','December');
  var $weekdays = Array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
  
  // Error message texts
  var $error_texts = Array(
    'phone'     => "Please enter a valid 10-digit number",
    'name'      => "Please enter a valid name",
    'email'     => "Please enter a valid e-mail address",
    'zip'       => "Please enter a valid ZIP code (5 digits or 5 digits + 4 digits)",
    'state'     => "Please select a state",
    'menu'      => "Please make a selection",
    'date'      => "Please specify a date",
    'time'      => "Please specify a time",
    'password'  => "The password is blank or does not match",
    'upload'    => "Please select a file to upload",
    'text'      => "Must not be blank",
  );
  // Message texts
  var $message_texts = Array(
    'success'   => "Your information has been submitted"  
  );
  
  // Store the current form HTML for re-use or revision
  var $form_html = '';
  
  /*******************
  * BASIC CONSTRUCTION
  *******************/

  /**
  * Sets the wrapper template for each form element to be rendered.
  * @param string $x = the wrapper template to go around each form element.
  */
  function setElementTemplate($x){
    $this->element_template = $x;
  }
  
  /**
  * Retrieves the wrapper template for form elements.
  * @return string $x = the wrapper template to go around each form element.
  */
  function getElementTemplate(){
    return $this->element_template;
  }
  
  /**
  * Integrates a form element's label and itself with the element template.
  * @param string $label = the element label text.
  * @param string $element = the element HTML itself.
  * @return string $o = the final HTML for the form element, wrapped in the element template.
  */
  function wrapElement($label,$element){
    $o = $this->getElementTemplate();
    $o = str_replace('<!--LABEL-->',$label,$o);
    $o = str_replace('<!--ELEMENT-->',$element,$o);
    return $o;
  }
  
  /**
  * Outputs the tab index and increments it by one.
  */
  function tabIndex(){
    global $tabIndex;
    ++$tabIndex;
    return $tabIndex;
  }
  
  /**
  * Helper function to determine whether or not an array is associative (indexed instead of simple).
  * @param array $array = array to analyze.
  * @return boolean = true if associative, false if not.
  */
  function is_assoc($array){
    foreach(array_keys($array) as $k => $v) {
      if($k !== $v) return true;
    }
    return false;
  }
  
  /**
  * Helper function to split telephone numbers
  */
  function splitPhone($x){
    return Array(trim(substr($x,0,3)),trim(substr($x,3,3)),trim(substr($x,6,4)));
  }
  
  function mergePhone($x){
    $o = '';
    $lengths = Array(3,3,4);
    for($k=0;$k<3;$k++){
      for($i=0;$i<$lengths[$k];$i++){
        $o .= (isset($x[$k]{$i})) ? $x[$k]{$i} : ' ';
      }
    }    
    return $o;
  }
  
  /**
  * Helper function to split ZIP Codes
  */
  function splitZip($x){
    return Array(substr($x,0,5),substr($x,5,4));
  }
  
  /**
  * Creates a block of CSS to mark invalid fields.
  */
  function buildErrorCSS(){
    $o  = '';
    $o .= "<style type=\"text/css\">\n";
    // Field borders
    foreach($this->error_fields as $error_field){
      $o .= '#'.$error_field.', ';
      $o .= '#'.$error_field.' select, ';
      $o .= '#'.$error_field.' input, ';
      $o .= '#'.$error_field.' textarea, ';
    }
    $o .= "#void{ color: #FF0000; border-color: #FF0000; }\n";
    // Text
    foreach($this->error_fields as $error_field){
      $o .= '#'.$error_field.', ';
    }
    $o .= "#void_field{ color: #FF0000; }\n";
    // Element labels
    foreach($this->error_fields as $error_field){
      $o .= '#'.$error_field.'_label, ';
    }
    $o .= "#void_label{ color: #FF0000; font-weight: bold; }\n";
    $o .= "</style>\n\n";
    return $o;  
  }
  
  /**
  * Creates a hidden field to store detailed information about the form, in order to preserve the data from page to page.
  * @param string $form_name = the name of the form.
  * @return string $o = the hidden field HTML.
  */
  function createFormStateField($name){
    $d = base64_encode(serialize($this->fields));
    return "<input type=\"hidden\" name=\"".$name."_state_transfer_data\" value=\"".$d."\" />\n";  
  }
  
  /**
  * Decodes the state transfer field.
  */
  function readFormStateField($name){
    if(isset($_POST[$name.'_state_transfer_data'])){
      $this->fields = unserialize(base64_decode($_POST[$name.'_state_transfer_data']));
    }
  }
  
  /**
  * Displays any error messages
  */
  function showErrorMessages(){
    if(count($this->errors) > 0){
      $o  = "<div class=\"form_error_messages\">\n";
      $o .= implode("<br />\n",$this->errors);
      $o .= "</div>\n";
      return $o;
    }
  }
  
  /**
  * Displays any non-error messages
  */
  function showMessages(){
    if(count($this->messages) > 0){
      $o  = "<div class=\"form_messages\">\n";
      $o .= implode("<br />\n",$this->messages);
      $o .= "</div>\n";
      return $o;
    }
  }
  
  /**
  * Builds any javascript functions for the form
  */
  function buildJavaScript(){
    $o  = "<script type=\"text/javascript\">\n";
    
    $o .= "</script>\n\n";
    return $o;
  }
  
  /**
  * Splits a date formatted as such:  2008-12-20 into $month, $day, and $year.
  * @param date (YYYY-MM-DD) $date = the date to split.
  * @return array $month, $day, and $year.
  */
  function splitDate($date){
    $date = explode('-',$date);
    if(count($date) < 3) return false;
    return Array(intval($date[0]),sprintf("%02d",intval($date[1] - 1)),sprintf("%02d",intval($date[2])));
  }
  
  /**
  * Splits a time formatted as such:  6:12 or 5:47:04 into $hour, $minute, $second ($second omitted if not provided), and $ampm.
  * @param time (HH:MM) or (HH:MM:SS) $time = the time to split.
  * @param $format = 0 for 24-hour input format, 1 for 12-hour input format.
  * Output format is always 12-hour.
  * @return array $hour, $minute, $second ($second omitted if not provided) and $ampm.
  */
  function splitTime($time,$format=0){
    $time = explode(':',$time);
    if(count($time) < 2) return false;
    $r = Array(sprintf("%02d",intval($time[0])),sprintf("%02d",intval($time[1])));
    if(isset($time[2])){
      $r[2] = sprintf("%02d",intval($time[2]));
    } else {
      $r[2] = '00';
    }
    $hour = $r[0];
    if($hour < 12){
      $ampm = 'AM';
    } else {
      $ampm = 'PM';
      $r[0] = $r[0] - 12;
    }
    $r[3] = $ampm;
    return $r; 
  }
  
  /**
  * Assembles a time stamp as HH:MM from input.
  * @param $hour = the hour, either 12-hour or 24-hour format.
  * @param $minute = the minutes (obviously).
  * @param $ampm = if set, $hour will be interpreted as 12-hour format.  If not, $hour is interpreted as 24-hour format.
  * @return $time = the formatted time as HH:MM, in 24-hour format.
  */
  function mergeTime($hour,$minute,$ampm=false){
    if($ampm === false){
      if(($hour < 0) || ($hour > 23)) return false; // Invalid hour
    } else {
      if(($hour < 1) || ($hour > 12)){  // Invalid hour
        return false;
      } else if(($hour < 12) && ($ampm == 'PM')){
        $hour += 12;
      } else if(($hour >= 12) && ($ampm == 'AM')){ // Invalid hour
        return false;
      }
    } 
    if(($minute < 0) || ($minute > 59)){ // Invalid minute
      return false;
    }
    return sprintf("%02d",$hour).':'.sprintf("%02d",$minute);  
  }
  
  /**
  * Arranges field data; sets defaults and fills in blanks where needed and makes substitutions
  */
  function fieldDataSetup($f){
    $defaults = Array('id' => false, 'name' => false, 'label' => false, 'type' => false, 'label' => false, 'required' => false, 'value' => false, 'values' => Array(), 'chosen' => Array(), 'available' => Array(), 'width' => false, 'length' => false);
    if(empty($f['name'])) return $defaults;
    foreach($defaults as $attribute => $data){
      $f[$attribute] = (!empty($f[$attribute])) ? $f[$attribute] : $defaults[$attribute];
    }
    if(empty($f['id'])){
      $f['id'] = $f['name'];
    }
    if(empty($f['label'])){
      $f['label'] = ucwords(str_replace('_',' ',$f['name']));
    }
    return $f;  
  }
  
  /****************************************
  * HIGH-LEVEL ELEMENT GENERATION FUNCTIONS
  ****************************************/
  
  /**
  * MAIN FUNCTION
  * Constructs the form.
  * @param array $fields = a two-dimensional array, as follows:
  * Array(
  *   [0] = Array(
  *     ['id'] => 'field_id', (not neccessarily a numeric id; this is an HTML id)
  *     ['name'] => 'first_name', (the name to be used for the field in the HTML)
  *     ['label'] => 'Field Label:',
  *     ['type'] => 'text',
  *     ['length'] => 32, (not applicable to menu type elements)
  *     ['value'] => 'value',
  *     ['required'] => 'true', (boolean - whether or not the field is required or can be left blank)
  *     ['values'] => Array(label => value) (for menu type elements only)
  *     ['onchange'] => 'void();' (for menu type elements only)
  *   );
  * @param string $id = the id of the form.
  * @param string $name = the name of the form.
  * @param string (optional) $submit_label = the label for the form's main submit button.
  * @return string $o = the HTML of the entire form.
  */
  function buildForm($fields=false,$id=false,$name=false,$submit_label='Submit', $validateFormData = true){
    // Store form field data in object
    foreach($fields as $field_id => $field){
      // Store form field element as long as it isn't an HTML insertion.
      if($field['type'] != 'html'){
        $fields[$field_id] = $this->fields[] = $this->fieldDataSetup($field);  // Swaps in required fields if blank, etc.
      }
    }
    // ID and Name for the main submit button for the form
    $submit_button_name = 'form_'.$name.'_submit';
    $submit_button_id = 'form_'.$id.'_submit';
    // Validate if the form was submitted
    if($validateFormData && isset($_POST[$submit_button_name])){ // This means the form was submitted and incoming data needs to be processed.
      // Mark that the form is being processed
      $this->submitted = true;
      // Filter all input
      $this->filterAll();
      // Validate all input
      $this->validateAll();
      // Swap in new data for fields
      $fields = $this->fields;
    }
    $o  = '';    
    // Generate any error-marking CSS
    $o .= $this->buildErrorCSS();
    // Display any error messages
    $o .= $this->showErrorMessages();
    // Display any non-error messages
    $o .= $this->showMessages();
    // Generate any javascript functions
    $o .= $this->buildJavaScript();
    // Create main form
    $form_open = $this->form_open;
    $form_open = str_replace('<!--ID-->',$id,$form_open);
    $form_open = str_replace('<!--NAME-->',$name,$form_open);
    $form_open = str_replace('<!--ACTION-->',$this->action_url,$form_open);
    $o .= $form_open;
    foreach($fields as $field_id => $field){
      // Add "required" if the field is required
      $field['label'] = (isset($field['label'])) ? $field['label'] : '';
      $field['label'] .= ((isset($field['required'])) && ($field['required'] === true)) ? ' [required] ' : '';
      // Dynamically create field
      switch($field['type']){
        case 'html':
          $o .= $field['value']; // Just inserts HTML.
        break;
        case 'phone':
          $o .= $this->buildPhone($field['id'],$field['name'],$field['label'],$field['value']);
        break;
        case 'zip':
          $o .= $this->buildZip($field['id'],$field['name'],$field['label'],$field['value']);
        break;
        case 'submit':
          $o .= $this->buildSubmit($field['id'],$field['name'],$field['label'],'button');
        break;
        case 'menu':
          $use_assoc = ((isset($field['assoc'])) && ($field['assoc'] === true));
          $o .= $this->buildMenu($field['id'],$field['name'],$field['label'],$field['values'],$field['value'],$field['onchange'],true,$use_assoc);
        break;
        case 'transfer':
          $use_assoc = ((isset($field['assoc'])) && ($field['assoc'] === true));
          $this->fields[$field_id]['values'] = $field['available']; // Store the full set of available values before chosen values are removed
          $o .= $this->buildTransfer($field['id'],$field['name'],$field['label'],$field['chosen'],$field['available'],$use_assoc);
        break;
        case 'state':
          $o .= $this->buildStatesMenu($field['id'],$field['name'],$field['label'],$field['value'],$field['onchange']);
        break;
        case 'date':
          $o .= $this->buildDateMenu($field['id'],$field['name'],$field['label'],$field['value'],$field['onchange']);
        break;
        case 'time':
          $o .= $this->buildTimeMenu($field['id'],$field['name'],$field['label'],$field['value'],$field['onchange'],1);
        break;
        case 'checkbox':
          $o .= $this->buildCheckBox($field['id'],$field['name'],$field['label'],$field['value']);
        break;
        case 'radio':
        	$use_assoc = ((isset($field['assoc'])) && ($field['assoc'] === true));
          $o .= $this->buildRadioSeries($field['id'],$field['name'],$field['label'],$field['values'],$field['value'],$field['onchange'],$use_assoc);
        break;
        case 'password': // Password entry field, as in logging in
          $o .= $this->buildPasswordField($field['id'],$field['name'],$field['label'],$field['value'],$field['length']);
        break;
        case 'password_set': // Password creation field, with confirmation field
          $o .= $this->buildPasswordSetField($field['id'],$field['name'],$field['label'],$field['value'],$field['length']);
        break;
        case 'password_reset': // Password change field, with confirmation AND 'old password' field
          $o .= $this->buildPasswordResetField($field['id'],$field['name'],$field['label'],$field['old_value'],$field['value'],$field['length']);
        break;
        case 'textarea':
          $o .= $this->buildTextArea($field['id'],$field['name'],$field['label'],$field['value']);
        break;
        case 'email':
          $o .= $this->buildField($field['id'],$field['name'],$field['label'],$field['value'],$field['length']);
        break;
        case 'hidden':
          $o .= $this->buildHiddenField($field['id'],$field['name'],$field['value']);
        break;
        case 'upload':
          $o .= $this->buildUploadField($field['id'],$field['name'],$field['label']);
        break;
        case 'text_alpha':
        case 'text_numeric':
        case 'text':
        default:
          $o .= $this->buildField($field['id'], $field['name'], $field['label'], $field['value'], $field['length'], false, false, (isset($field['helpText']) ? $field['helpText'] : null));
        break;      
      }
    }
    // Store detailed information about the fields in a hidden form variable.
    $o .= $this->createFormStateField($name);
    // Add the main submit button
    $o .= $this->buildSubmit($submit_button_id,$submit_button_name,$submit_label,'button');
    $o .= $this->form_close;
    // Set the encoding type for the form
    $enctype = ($this->upload_flag === true) ? "multipart/form-data" : "application/x-www-form-urlencoded";
    $o = str_replace('<!--ENCTYPE-->',$enctype,$o);
    $this->form_html = $o;
    return $o;
  }
  
  function buildStatesMenu($id=false,$name=false,$label=false,$value=false,$onchange=false){
    return $this->buildMenu($id,$name,$label,$this->states,$value,$onchange);
  }
  
  function buildDateMenu($id=false,$name=false,$label=false,$value=false,$onchange=false){
    // Break apart the date value
    list($year,$month,$day) = ($value !== false) ? $this->splitDate($value) : Array(false,false,false);
    // Month value must be mapped to the month name
    $month = $this->months[intval($month)];
    $o  = "<label id=\"".$id."_label\" for=\"".$id."_month\">".$label."</label>\n";
    $o .= "<div class=\"date_selector\" id=\"".$id."\" >\n";
    $o .= $this->buildMonthMenu($id.'_month',$name.'[month]','',$month,$onchange);
    $o .= $this->buildDayMenu($id.'_day',$name.'[day]','',$day,$onchange);
    $o .= $this->buildYearMenu($id.'_year',$name.'[year]','',$year,$onchange);
    $o .= "</div>\n";
    return $o;
  }
  
  function buildTimeMenu($id=false,$name=false,$label=false,$value=false,$onchange=false,$hour_format=0){
    // Break apart the time value
    list($hour,$minute,$seconds,$ampm) = ($value !== false) ? $this->splitTime($value) : Array(false,false,false,false);
    $o  = "<label id=\"".$id."_label\" for=\"".$id."_month\">".$label."</label>\n";
    $o .= "<div class=\"date_selector\" id=\"".$id."\" >\n";
    if($hour_format == 0){
      $o .= $this->build24HourMenu($id.'_hour',$name.'[hour]','',$hour,$onchange);
    } else {
      $o .= $this->build12HourMenu($id.'_hour',$name.'[hour]','',$hour,$onchange);
    }
    $o .= ':';
    $o .= $this->buildMinuteMenu($id.'_minute',$name.'[minute]','',$minute,$onchange);
    if($hour_format == 1){
      $o .= $this->buildAMPMMenu($id.'_ampm',$name.'[ampm]','',$ampm,$onchange);
    }
    $o .= "</div>\n";
    return $o;
  }
  
  function buildMonthMenu($id=false,$name=false,$label=false,$value=false,$onchange=false,$container=false){
    return $this->buildMenu($id,$name,$label,$this->months,$value,$onchange,$container);  
  }
  
  function buildDayMenu($id=false,$name=false,$label=false,$value=false,$onchange=false,$container=false){
    for($i=1,$days=Array();$i<=31;$i++){
      $days[$i] = $i;
    }
    return $this->buildMenu($id,$name,$label,$days,$value,$onchange,$container);  
  }
  
  function buildYearMenu($id=false,$name=false,$label=false,$value=false,$onchange=false,$container=false){
    $cur_year = date("Y");
    $value = (empty($value)) ? date("Y") : $value;
    for($i=($cur_year - 100),$years=Array();($i<=$cur_year);$i++){
      $years[$i] = $i;
    }
    return $this->buildMenu($id,$name,$label,$years,$value,$onchange,$container);  
  }
  
  function build24HourMenu($id=false,$name=false,$label=false,$value=false,$onchange=false,$container=false){
    for($i=0,$hours=Array();$i<=23;$i++){
      $hours[$i] = $i;
    }
    return $this->buildMenu($id,$name,$label,$hours,$value,$onchange,$container);  
  }
  
  function build12HourMenu($id=false,$name=false,$label=false,$value=false,$onchange=false,$container=false){
    for($i=1,$hours=Array();$i<=12;$i++){
      $hours[$i] = $i;
    }
    return $this->buildMenu($id,$name,$label,$hours,$value,$onchange,$container);  
  }
  
  function buildAMPMMenu($id=false,$name=false,$label=false,$value=false,$onchange=false,$container=false){
    $k = Array('AM','PM');
    return $this->buildMenu($id,$name,$label,$k,$value,$onchange,$container);  
  }
  
  function buildMinuteMenu($id=false,$name=false,$label=false,$value=false,$onchange=false,$container=false){
    for($i=0,$minutes=Array();$i<=59;$i++){
      $i = sprintf("%02d",$i);
      $minutes[$i] = $i;
    }
    return $this->buildMenu($id,$name,$label,$minutes,$value,$onchange,$container);  
  }
  
  function buildWeekDayMenu($id=false,$name=false,$label=false,$value=false,$onchange=false,$container=false){
    return $this->buildMenu($id,$name,$label,$weekdays,$value,$onchange,$container);  
  }
  
  function buildPasswordField($id=false,$name=false,$label=false,$value=false,$maxlength=32,$width=false){
    $o  = "<div class=\"password\">\n";
    $o .= $this->buildField($id,$name,$label,$value,$maxlength,$width,true);
    $o .= "</div>\n";
    return $o;
  }
  
  function buildPasswordSetField($id=false,$name=false,$label=false,$value=false,$maxlength=32,$width=false){
    $o  = "<div class=\"password\">\n";
    $o .= $this->buildField($id,$name.'[main]',$label,$value,$maxlength,$width,true);
    $o .= $this->buildField($id.'_confirm',$name.'[confirm]','Confirm '.$label,$value,$maxlength,$width,true);
    $o .= "</div>\n";
    return $o;
  }
  
  function buildPasswordResetField($id=false,$name=false,$label=false,$old_value=false,$value=false,$maxlength=32,$width=false){
    $o  = "<div class=\"password\">\n";
    $o .= $this->buildField($id.'_old',$name.'[old]','Old '.$label,$old_value,$maxlength,$width,true);
    $o .= $this->buildField($id,$name.'[main]',$label,$value,$maxlength,$width,true);
    $o .= $this->buildField($id.'_confirm',$name.'[confirm]','Confirm '.$label,$value,$maxlength,$width,true);
    $o .= "</div>\n";
    return $o;
  }
  
  /***************************************
  * LOW-LEVEL ELEMENT GENERATION FUNCTIONS
  ***************************************/
  
  /**
  * Creates a pulldown menu.
  * @param string $id = the id of the form element.  This is used for CSS and javascript.
  * @param string $name = the name of the form element, representing the submitted data's variable name.
  * @param string $label = the label to show for the form element.
  * @param array $values = an array of label => value pairs for menu options.
  * @param int or string $selected = the selected value.
  * @param string $onchange = any javascript to call upon changing the value.
  * @param boolean $container = if true (default), menu will be contained in the element template.  If false, it won't be.
  * @return string $o = the HTML output for the form element.
  */
  function buildMenu($id=false,$name=false,$label=false,$values=false,$selected=false,$onchange=false,$container=true,$use_assoc=false){
    // Reality checks
    if($name === false){
      return false;
    }    
    // If $id and / or $label are not specified, use $name.
    if($id === false) $id = $name;
    if($label === false) $label = ucwords(str_replace('_',' ',$name));
    // Create onChange clause if specified.
    $onchange = ($onchange === false) ? '' : " onChange=\"".$onchange."\" ";
    $o  = '';
    $o .= "<select id=\"".$id."\" name=\"".$name."\"".$onchange." tabindex=\"".$this->tabIndex()."\" >\n";
    $assoc = ($this->is_assoc($values));
    foreach($values as $n => $v){
      if((!($assoc)) && ($use_assoc === false)) $n = $v; // Make the value equal the label if the array is not associative
      $sel = ($n == $selected) ? " selected=\"selected\" " : '';
      $o .= "<option value=\"".$n."\"".$sel.">".$v."</option>\n";
    }
    $o .= "</select>\n";
    $label = "<label id=\"".$id."_label\" for=\"".$id."\">".$label."</label>\n";
    $o = ($container === true) ? $this->wrapElement($label,$o) : $label.$o;
    return $o;
  }
  
  /**
  * Creates a multi-select transfer menu.
  * This element consists of two multi-select menus; the first one holding a current set of values and the second containing anything
  * available that isn't in the first one.
  * Javascript is used to move items from one box to another.
  * @param string $id = the id of the form element.  This is used for CSS and javascript.
  * @param string $name = the name of the form element, representing the submitted data's variable name.
  * @param string $label = the label to show for the form element.
  * @param array $values_chosen = an array of label => value pairs that appear in the 'selected' box.
  * @param array $values_available = an array of label => value pairs that are available.  The 'selected' box values will be subtracted from these.
  * @return string $o = the HTML output for the form element.
  */
  function buildTransfer($id=false,$name=false,$label=false,$values_chosen=false,$values_available=false,$use_assoc=false){
    // Reality checks
    if($name === false){
      return false;
    }
    // If $id and / or $label are not specified, use $name.
    if($id === false) $id = $name;
    if($label === false) $label = ucwords(str_replace('_',' ',$name));
    $o  = '';
    // Remove currently selected items from the available items list
    foreach($values_available as $n => $v){
      if((isset($values_chosen[$n])) || (in_array($v,$values_chosen))){
        unset($values_available[$n]);
      }
    }
    // The size of the multiple-select boxes is based on the number of available choices.
    $size = (count($values_available) > 25) ? floor(count($values_available) / 5) : 5;
    $o .= "<div id=\"transfer_container_".$id."\" class=\"transfer_container\">\n";
    // First, 'chosen' select box.
    $o .= "<div class=\"transfer_chosen\">\n";
    $o .= "<label for=\"transfer_chosen_".$id."\">Selected:</label>\n";
    $o .= "<select id=\"transfer_chosen_".$id."\" name=\"transfer_chosen_".$name."\" tabindex=\"".$this->tabIndex()."\" size=\"".$size."\" >\n";
    $assoc = ($this->is_assoc($values_chosen));
    foreach($values_chosen as $n => $v){
      if((!($assoc)) && ($use_assoc === false)) $n = $v; // Make the value equal the label if the array is not associative
      $o .= "<option value=\"".$n."\">".$v."</option>\n";
    }   
    $o .= "</select>\n";
    $o .= "<a class=\"transfer_button\" href=\"#\" onClick=\"transferBoxRemove('".$id."','".$name."');\">Remove &raquo;</a>\n";
    $o .= "</div>\n";
    // Next, 'available' select box.
    $o .= "<div class=\"transfer_available\">\n";
    $o .= "<label for=\"transfer_available_".$id."\">Available:</label>\n";
    $o .= "<select id=\"transfer_available_".$id."\" name=\"transfer_available_".$name."\" tabindex=\"".$this->tabIndex()."\" size=\"".$size."\" >\n";
    $assoc = ($this->is_assoc($values_available));
    foreach($values_available as $n => $v){
      if((!($assoc)) && ($use_assoc === false)) $n = $v; // Make the value equal the label if the array is not associative
      $o .= "<option value=\"".$n."\">".$v."</option>\n";
    }   
    $o .= "</select>\n";
    $o .= "<a class=\"transfer_button\" href=\"#\" onClick=\"transferBoxAdd('".$id."','".$name."');\">&laquo; Add</a>\n";
    $o .= "</div>\n";
    $o .= "</div>\n";
    $o .= "<div id=\"transfer_values_container_".$id."\">\n";
    foreach($values_chosen as $n => $v){
      $o .= "<input type=\"hidden\" name=\"".$name."[".$n."]\" value=\"".$n."\" />\n";    
    }    
    $o .= "</div>\n";
    $label = "<label id=\"".$id."_label\" for=\"".$id."\">".$label."</label>\n";
    $o = $label.$o;
    return $o;
  }
  
  /**
  * Generates a normal input field.
  * @param string $id = the id of the form element.  This is used for CSS and javascript.
  * @param string $name = the name of the form element, representing the submitted data's variable name.
  * @param string $label = the label to show for the form element.
  * @param string $value = the value of the field.  Can be blank.
  * @param int $maxlength = the maximum allowed character count for the field.
  * @param int (optional) $width = the width, in pixels.
  * @param boolean (optional) $password = whether or not the field is a password.
  * @return string $o = the HTML output for the form element.
  */
  function buildField($id=false,$name=false,$label=false,$value=false,$maxlength=32,$width=false,$password=false,$helpText=null){
    // Reality checks
    if($name === false){
      return false;
    }    
    // If $id and / or $label are not specified, use $name.
    if($id === false) $id = $name;
    if($label === false) $label = ucwords(str_replace('_',' ',$name));
  	$width = ($width === false) ? '' : " style=\"width: ".$width."px;\"";
    $type = ($password === false) ? 'text' : 'password';
    $o = (isset($helpText) ? '<div class="helpText">' . $helpText . '</div>' : '') . "<input type=\"".$type."\" id=\"".$id."\" name=\"".$name."\" value=\"".$value."\" maxlength=\"".$maxlength."\"".$width." tabindex=\"".$this->tabIndex()."\" onKeyUp=\"checkField(this);\" />\n";
  	$label = "<label id=\"".$id."_label\" for=\"".$id."\">".$label."</label>\n";
  	$o = $this->wrapElement($label,$o);
    return $o;
  }
  
  /**
  * Generates a hidden input field.
  * @param string $id = the id of the form element.  This is used for CSS and javascript.
  * @param string $name = the name of the form element, representing the submitted data's variable name.
  * @param string $value = the value of the field.  Can be blank.
  * @return string $o = the HTML output for the form element.
  */
  function buildHiddenField($id=false,$name=false,$value=false){
    // Reality checks
    if($name === false){
      return false;
    }    
    // If $id is not specified, use $name.
    if($id === false) $id = $name;
  	$o = "<input type=\"hidden\" id=\"".$id."\" name=\"".$name."\" value=\"".$value."\" />\n";
    return $o;
  }
  
  /**
  * Generates a text area field.
  * @param string $id = the id of the form element.  This is used for CSS and javascript.
  * @param string $name = the name of the form element, representing the submitted data's variable name.
  * @param string $label = the label to show for the form element.
  * @param string $value = the value of the field.  Can be blank.
  * @return string $o = the HTML output for the form element.
  */
  function buildTextArea($id=false,$name=false,$label=false,$value=false,$cols=32,$rows=6){
    // Reality checks
    if($name === false){
      return false;
    }    
    // If $id and / or $label are not specified, use $name.
    if($id === false) $id = $name;
    if($label === false) $label = ucwords(str_replace('_',' ',$name));
  	$o = "<textarea id=\"".$id."\" name=\"".$name."\" cols=\"".$cols."\" rows=\"".$rows."\" tabindex=\"".$this->tabIndex()."\" >".$value."</textarea>\n";
  	$label = "<label id=\"".$id."_label\" for=\"".$id."\">".$label."</label>\n";
  	$o = $this->wrapElement($label,$o);
    return $o;
  }
  
  /**
  * Generates a U.S. Telephone Number Field series.
  * @param string $id = the id of the form element.  This is used for CSS and javascript.
  * @param string $name = the name of the form element, representing the submitted data's variable name.
  * @param string $label = the label to show for the form element.
  * @param string $value = the value of the field.  Can be blank.
  * @param int $maxlength = the maximum allowed character count for the field.
  * @param int (optional) $width = the width, in pixels.
  * @return string $o = the HTML output for the form element.
  */
  function buildPhone($id=false,$name=false,$label=false,$value=false){
    // Reality checks
    if($name === false){
      return false;
    }    
    // If $id and / or $label are not specified, use $name.
    if($id === false) $id = $name;
    if($label === false) $label = ucwords(str_replace('_',' ',$name));
  	$o  = '';
  	$lengths = Array(3,3,4);
    $widths = Array(40,40,70);
    $values = $this->splitPhone($value);
  	for($i=0;$i<3;$i++){
      $e  = "<input type=\"text\" id=\"".$id."_".$i."\" name=\"".$name.'['.$i."]\" value=\"".$values[$i]."\" maxlength=\"".$lengths[$i]."\" style=\"width: ".$widths[$i]."px;\" tabindex=\"".$this->tabIndex()."\" onkeyup=\"checkPhone".$i."('".$id."');\" />\n";
      $o .= ($i == 0) ? '( '.$e.' ) ' : ' - '.$e;
  	}
    $label = "<label id=\"".$id."_label\" for=\"".$id."_0\">".$label."</label>\n";

    $o = "<div class=\"form_phone\" id=\"".$id."\">".$o."</div>\n";
  	$o = $this->wrapElement($label,$o);
    return $o;
  }
  
  /**
  * Generates a U.S. ZIP Code input field.
  * @param string $id = the id of the form element.  This is used for CSS and javascript.
  * @param string $name = the name of the form element, representing the submitted data's variable name.
  * @param string $label = the label to show for the form element.
  * @param string $value = the value of the field.  Can be blank.
  * @return string $o = the HTML output for the form element.
  */
  function buildZip($id=false,$name=false,$label=false,$value=false){
    // Reality checks
    if($name === false){
      return false;
    }    
    // If $id and / or $label are not specified, use $name.
    if($id === false) $id = $name;
    if($label === false) $label = ucwords(str_replace('_',' ',$name));
  	$o  = '';
  	$lengths = Array(5,4);
    $widths = Array(80,70);
    $values = $this->splitZip($value);
    for($i=0;$i<2;$i++){
      $e  = "<input type=\"text\" id=\"".$id."_".$i."\" name=\"".$name.'['.$i."]\" value=\"".$values[$i]."\" maxlength=\"".$lengths[$i]."\" style=\"width: ".$widths[$i]."px;\" tabindex=\"".$this->tabIndex()."\" onkeyup=\"checkZip".($i+1)."('".$id."');\" />\n";
      $o .= ($i == 0) ? $e : ' - '.$e;
  	}
    $label = "<label id=\"".$id."_label\" for=\"".$id."_0\">".$label."</label>\n";
    $o = "<div class=\"form_zip\" id=\"".$id."\">".$o."</div>\n";
  	$o = $this->wrapElement($label,$o);
    return $o;
  }
  
  /**
  * Creates a checkbox.
  * @param string $id = the id of the form element.  This is used for CSS and javascript.
  * @param string $name = the name of the form element, representing the submitted data's variable name.
  * @param string $label = the label to show for the form element.
  * @param boolean $value = true or false; true = checked, false = not checked.
  * @return string $o = the HTML output for the form element.
  */
  function buildCheckBox($id=false,$name=false,$label=false,$value=false){
    $label = "<label id=\"".$id."_label\" for=\"".$id."\">".$label."</label>\n";
    $checked = ($value === true) ? " checked=\"checked\" " : '';
    $o = "<input type=\"checkbox\" id=\"".$id."\" name=\"".$name."\"".$checked." tabindex=\"".$this->tabIndex()."\" />\n";
    $o = $this->wrapElement($label,$o);
    return $o; 
  }
  
  /**
  * Creates a radio button series for a form element.
  * @param string $id = the id of the form element.  This is used for CSS and javascript.
  * @param string $name = the name of the form element, representing the submitted data's variable name.
  * @param string $label = the label to show for the form element.
  * @param array $values = an array of label => value pairs for radio button options.
  * @param int or string $selected = the selected value.
  * @param string $onchange = any javascript to call upon changing the value.
  * @return string $o = the HTML output for the form element.
  */
  function buildRadioSeries($id=false,$name=false,$label=false,$values=false,$value=false,$onchange=false,$use_assoc=false){
    $label = "<label id=\"".$id."_label\" for=\"".$id."\">".$label."</label>\n";
    $o  = "<ul class=\"radio_container\" id=\"".$id."\">\n";
    $assoc = ($this->is_assoc($values));
    foreach($values as $n => $v){
      if((!($assoc)) && ($use_assoc === false)) $n = $v; // Make the value equal the label if the array is not associative
      $checked = ($n == $value) ? " checked=\"checked\" " : '';
      $o .= "<li><input type=\"radio\" name=\"".$name."\" value=\"".$n."\"".$checked." tabindex=\"".$this->tabIndex()."\" />".$v."</li>\n";
    }
    $o .= "</ul>\n"; 
    $o = $this->wrapElement($label,$o);
    return $o;    
  }
  
  /**
  * Creates a file upload field.
  * @param string $id = the id of the form element.  This is used for CSS and javascript.
  * @param string $name = the name of the form element, representing the submitted data's variable name.
  * @param string $label = the label to show for the form element.
  * @return string $o = the HTML output for the form element.
  * NOTE:  The data returned from this field upon form submission will be the contents of the uploaded file.  
  */
  function buildUploadField($id=false,$name=false,$label=false){
    // Reality checks
    if($name === false){
      return false;
    }    
    // If $id and / or $label are not specified, use $name.
    if($id === false) $id = $name;
    if($label === false) $label = ucwords(str_replace('_',' ',$name));
  	$o = "<input type=\"file\" id=\"".$id."\" name=\"".$name."\" size=\"32\" tabindex=\"".$this->tabIndex()."\" />\n";
  	$label = "<label id=\"".$id."_label\" for=\"".$id."\">".$label."</label>\n";
  	$o = $this->wrapElement($label,$o);
    $this->upload_flag = true;
    return $o;
  }
  
  /**
  * Creates a submit button.
  * @param string $id = the id to use for the button.
  * @param string $name = the name, which will be used as the input variable, of the button.
  * @param string $label = the text to appear on the button.
  * @param string (optional) $class = the CSS class to use for the button.
  * @return string $o = the HTML for the submit button.
  */  
  function buildSubmit($id=false,$name=false,$label=false,$class='button'){
    // Reality checks
    if($name === false){
      return false;
    }    
    // If $id and / or $label are not specified, use $name.
    if($id === false) $id = $name;
    if($label === false) $label = ucwords(str_replace('_',' ',$name));
    $o = "<input type=\"submit\" class=\"".$class."\" id=\"".$id."\" name=\"".$name."\" value=\"".$label."\" tabindex=\"".$this->tabIndex()."\" />\n";
    return $o;
  }
  
  /*********************
  * VALIDATION FUNCTIONS
  *********************/
  
  function validateEmail_OLD($x){
    return ereg("^[^@ ]+@[^@ ]+\.[^@ ]+$",$x,$t);  
  }
  
  /**
  Validate an email address.
  Provide email address (raw input)
  Returns true if the email address has the email 
  address format and the domain exists.
  */
  function validateEmail($email){
    $isValid = true;
    $atIndex = strrpos($email, "@");
    if (is_bool($atIndex) && !$atIndex){
      $isValid = false;
    } else {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64) {
        // local part length exceeded
        $isValid = false;
      } else if ($domainLen < 1 || $domainLen > 255) {
        // domain part length exceeded
        $isValid = false;
      } else if ($local[0] == '.' || $local[$localLen-1] == '.') {
        // local part starts or ends with '.'
        $isValid = false;
      } else if (preg_match('/\\.\\./', $local)) {
        // local part has two consecutive dots
        $isValid = false;
      } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
        // character not valid in domain part
        $isValid = false;
      } else if (preg_match('/\\.\\./', $domain)) {
        // domain part has two consecutive dots
        $isValid = false;
      } else if(!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',str_replace("\\\\","",$local))){
        // character not valid in local part unless 
        // local part is quoted
        if (!preg_match('/^"(\\\\"|[^"])+"$/',str_replace("\\\\","",$local))){
          $isValid = false;
        }
      }
      /*
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))){
        // domain not found in DNS
        $isValid = false;
      }
      */
    }
    return $isValid;
  }
  
  function validatePhone($x){
    return((isset($x{9})) && (!isset($x{10})) && (is_numeric($x)));
  }
  
  function validateZip($x){
    return(((strlen($x) == 5) || (strlen($x) == 9)) && (is_numeric($x)));
  }
  
  function validateOption($value,$values){
//    echo "<pre>\n\n\n\n\n";
//    print_r($_REQUEST);
//    print_r($value);
//    print_r($values);
//    echo '</pre>';

    $flipped = array_flip($values);
    return(((in_array($value,$values)) || (in_array($value,$flipped))) && ($value != ''));
  }
  
  function validateAll(){
    foreach($this->fields as $field_id => $field){
      if(($field['required'] === true) || (($field['value'] != '') && ($field['value'] != 0)) && (!is_array($field['value']))){ // Validate if required OR not empty
        switch($field['type']){  // Values have been filtered and processed / assembled in the filterAll() function already
          case 'phone':
            if($this->validatePhone($field['value']) === false){
              $this->errors[] = str_replace(':','',$field['label']).': '.$this->error_texts['phone'];
              $this->error_fields[] = $field['id'];
              $this->valid = false;
            }
          break;
          case 'zip':
            if($this->validateZip($field['value']) === false){
              $this->errors[] = str_replace(':','',$field['label']).': '.$this->error_texts['zip'];
              $this->error_fields[] = $field['id'];
              $this->valid = false;
            }
          break;
          case 'submit':

          break;
          case 'menu':
          case 'radio':
            if($this->validateOption($field['value'],$field['values']) === false){
              $this->errors[] = str_replace(':','',$field['label']).': '.$this->error_texts['menu'];
              $this->error_fields[] = $field['id'];
              $this->valid = false;
            }
          break;
          case 'transfer':
            $transfer_valid = true;
            if((!empty($field['value'])) && (is_array($field['value'])) && (count($field['value']) > 0)){
              foreach($field['value'] as $n => $v){
                if($this->validateOption($v,$field['available']) === false){
                  $transfer_valid = false;
                }              
              }
            } else {
              $transfer_valid = false;
            }
            if($transfer_valid === false){
              $this->errors[] = str_replace(':','',$field['label']).': '.$this->error_texts['menu'];
              $this->error_fields[] = 'transfer_available_'.$field['id'];
              $this->error_fields[] = 'transfer_chosen_'.$field['id'];
              $this->valid = false;
            }
          break;
          case 'state':
            if($this->validateOption($field['value'],$this->states) === false){
              $this->errors[] = str_replace(':','',$field['label']).': '.$this->error_texts['state'];
              $this->error_fields[] = $field['id'];
              $this->valid = false;
            }
          break;
          case 'date':
            if(empty($field['value'])){ // Filter makes invalid dates empty
              $this->errors[] = str_replace(':','',$field['label']).': '.$this->error_texts['date'];
              $this->error_fields[] = $field['id'];
              $this->valid = false;            
            }
          break;
          case 'time':
            if($field['value'] === false){ // Filter makes invalid times false
              $this->errors[] = str_replace(':','',$field['label']).': '.$this->error_texts['time'];
              $this->error_fields[] = $field['id'];
              $this->valid = false;  
            }
          break;
          case 'checkbox':
            // Checkboxes need not validate.
          break;
          case 'password':  // Password entry field, as in logging in
            // Must not be empty.  Matched elsewhere (outside the scope of this library)
            if(empty($field['value'])){
              $this->errors[] = str_replace(':','',$field['label']).': '.$this->error_texts['password'];
              $this->error_fields[] = $field['id'];
              $this->error_fields[] = $field['id'].'_confirm';
              $this->valid = false;            
            }
          break;
          case 'password_set':  // Password creation field, with confirmation field
            // Password and password confirmation must match
            if((empty($field['value'])) || ($field['value'] != $field['value_confirm'])){
              $this->errors[] = str_replace(':','',$field['label']).': '.$this->error_texts['password'];
              $this->error_fields[] = $field['id'];
              $this->valid = false;            
            }
          break;
          case 'password_reset':  // Password change field, with confirmation AND 'old password' field
            // Password and password confirmation must match; Old must not be empty.  Matched elsewhere (outside the scope of this library)
            if((empty($field['old_value']) && (empty($field['value'])) && (empty($field['value_confirm'])))){ // If all are blank, it's ok and skip.
              // Gets a pass if all three fields are empty.
            }else if((empty($field['value'])) || ($field['value'] != $field['value_confirm'])){
              $this->errors[] = str_replace(':','',$field['label']).': '.$this->error_texts['password'];
              $this->error_fields[] = $field['id'];
              $this->error_fields[] = $field['id'].'_confirm';
              $this->valid = false;            
            }           
          break;
          case 'email':
            if($this->validateEmail($field['value']) === false){
              $this->errors[] = str_replace(':','',$field['label']).': '.$this->error_texts['email'];
              $this->error_fields[] = $field['id'];
              $this->valid = false;
            }
          break;
          case 'upload':
            if(!isset($field['value'])){
              $this->errors[] = str_replace(':','',$field['label']).': '.$this->error_texts['upload'];
              $this->error_fields[] = $field['id'];
              $this->valid = false;   
            }          
          break;
          case 'text_alpha':
          case 'text_numeric':
          case 'textarea':
          case 'text':
          default:
            if(empty($field['value'])){
              $this->errors[] = str_replace(':','',$field['label']).': '.$this->error_texts['text'];
              $this->error_fields[] = $field['id'];
              $this->valid = false;            
            }
          break;      
        }
      }
    }
  }
  
  /**
  * Function to externally trigger a form validation error; i.e. a password that doesn't match the database.
  */
  function setError($error_message=false,$error_field=false){
    if((!empty($error_message)) && ($error_field)){
      $this->errors[] = $error_message;
      $this->error_fields[] = $error_field;
      $this->valid = false;
    } else {
      return false;
    }    
  }
  
  /****************************************
  * FILTERING / DATA MANIPULATION FUNCTIONS
  ****************************************/
  
  function filterNumber($x){
    return preg_replace("/[^0-9]/",'',$x);
  }
  
  function filterAlpha($x){
    return preg_replace("/[^A-Za-z]/",'',$x);
  }
  
  function filterAlphaNumeric($x){
    return preg_replace("/[^0-9A-Za-z]/",'',$x);
  }
  
  function filterExtended($x){
    return preg_replace("/[^0-9A-Za-z \-_'@.]/",'',$x);
  }
  
  function filterEmail($x){
    return preg_replace("/[^0-9A-Za-z \-_'@.]/",'',$x);
  }
  
  // Filtering input also includes processing boolean values, etc.
  function filterAll(){
    // Restrict character range and length; merge inputs with pre-defined values
    foreach($this->fields as $field_id => $field){
      switch($field['type']){
        case 'phone':
          if(isset($_POST[$field['name']])){
            $phone = Array();
            $phone[0] = $this->filterNumber($_POST[$field['name']][0],3);
            $phone[1] = $this->filterNumber($_POST[$field['name']][1],3);
            $phone[2] = $this->filterNumber($_POST[$field['name']][2],4);
            $this->fields[$field_id]['value'] = $this->mergePhone($phone);
          }
        break;
        case 'zip':
          if(isset($_POST[$field['name']])){
            $zip_length = (!empty($_POST[$field['name']][1])) ? 9 : 5;
            $zip = implode('',$_POST[$field['name']]);
            $this->fields[$field_id]['value'] = $this->filterNumber($zip,$zip_length);
          }
        break;
        case 'submit':
          if(isset($_POST[$field['name']])){
            $this->fields[$field_id]['value'] = true;
          } else {
            $this->fields[$field_id]['value'] = false;
          }
        break;
        case 'menu':
        case 'radio':
          if(isset($_POST[$field['name']])){
            $flipped = array_flip($this->fields[$field_id]['values']);
            if(in_array($_POST[$field['name']],$this->fields[$field_id]['values'])){
              $this->fields[$field_id]['value'] = $this->filterExtended($_POST[$field['name']],32);
            } else if(in_array($_POST[$field['name']],$flipped)){
              $this->fields[$field_id]['value'] = $this->filterNumber($_POST[$field['name']],16);
            } else {
              $this->fields[$field_id]['value'] = '';
            }
          }
        break;
        case 'transfer':
          if((isset($_POST[$field['name']])) && (is_array($_POST[$field['name']]))){
            $this->fields[$field_id]['chosen'] = Array(); // Clear all values first to avoid old data that may have just been removed.
            foreach($_POST[$field['name']] as $v){
              if(isset($this->fields[$field_id]['available'][$v])){
                $this->fields[$field_id]['value'][] = $this->filterNumber($v,16);
                $this->fields[$field_id]['chosen'][$v] = $this->fields[$field_id]['available'][$v];
              }
            }         
          }      
        break;
        case 'state':
          if(isset($_POST[$field['name']])){
            if(in_array($_POST[$field['name']],$this->states)){
              $this->fields[$field_id]['value'] = $this->filterAlpha($_POST[$field['name']],2);
            } else {
              $this->fields[$field_id]['value'] = '';
            }
          }
        break;
        case 'date':
          if(isset($_POST[$field['name']])){
            $month = 0;
            $day = 0;
            $year = 0;
            if(in_array($_POST[$field['name']]['month'],$this->months)){
              $months = array_flip($this->months);
              $month = $months[$_POST[$field['name']]['month']] + 1;
              $month = sprintf("%02d",intval($month));
            }
            if(($_POST[$field['name']]['day'] >= 1) && ($_POST[$field['name']]['day'] <= 31)){
              $day = sprintf("%02d",intval($_POST[$field['name']]['day']));
            }
            if(($_POST[$field['name']]['year'] >= 1900) && ($_POST[$field['name']]['day'] <= intval(date("Y") + 100))){
              $year = sprintf("%04d",intval($_POST[$field['name']]['year']));
            }
            if(($month != 0) && ($day != 0) && ($year != 0)){
              $this->fields[$field_id]['value'] = $year.'-'.$month.'-'.$day;     
            } else {
              $this->fields[$field_id]['value'] = '';
            }
          }
        break;
        case 'time':
          if(isset($_POST[$field['name']])){
            $hour = 0;
            $minute = 0;
            $ampm = (isset($_POST[$field['name']]['ampm'])) ? $_POST[$field['name']]['ampm'] : false;
            if(isset($_POST[$field['name']]['ampm'])){
              if(($_POST[$field['name']]['hour'] >= 1) && ($_POST[$field['name']]['hour'] <= 12)){
                $hour = $_POST[$field['name']]['hour'];
              }            
            } else {
              if(($_POST[$field['name']]['hour'] >= 0) && ($_POST[$field['name']]['hour'] <= 23)){
                $hour = $_POST[$field['name']]['hour'];
              }
            }
            if(($_POST[$field['name']]['minute'] >= 0) && ($_POST[$field['name']]['minute'] <= 59)){
              $minute = $_POST[$field['name']]['minute'];
            }
            $this->fields[$field_id]['value'] = (isset($_POST[$field['name']]['ampm'])) ? $this->mergeTime($hour,$minute,$ampm) : $this->mergeTime($hour,$minute);
          }
        break;
        case 'checkbox':
          if(isset($_POST[$field['name']])){
            $this->fields[$field_id]['value'] = true;
          } else {
            $this->fields[$field_id]['value'] = false;
          }
        break;
        case 'password':  // Password entry field, as in logging in
          if(isset($_POST[$field['name']])){
            $this->fields[$field_id]['value'] = $this->filterExtended($_POST[$field['name']],32);  // POST variable is not an array
          }        
        break;
        case 'password_set':  // Password creation field, with confirmation field
          if(isset($_POST[$field['name']])){
            $this->fields[$field_id]['value'] = $this->filterExtended($_POST[$field['name']]['main'],32); // POST var IS an array
            $this->fields[$field_id]['value_confirm'] = $this->filterExtended($_POST[$field['name']]['confirm'],32);
          }
        break;
        case 'password_reset':  // Password change field, with confirmation AND 'old password' field
          if(isset($_POST[$field['name']])){
            $this->fields[$field_id]['value'] = $this->filterExtended($_POST[$field['name']]['main'],32); // POST var IS an array
            $this->fields[$field_id]['value_confirm'] = $this->filterExtended($_POST[$field['name']]['confirm'],32);
            $this->fields[$field_id]['value_old'] = $this->filterExtended($_POST[$field['name']]['old'],32);
          }
        break;
        case 'email':
          if(isset($_POST[$field['name']])){
            $this->fields[$field_id]['value'] = $this->filterEmail($_POST[$field['name']]);          
          }
        break;
        case 'text_alpha':
          if(isset($_POST[$field['name']])){
            $this->fields[$field_id]['value'] = $this->filterAlpha($_POST[$field['name']],64);
          }
        break;
        case 'text_numeric':
          if(isset($_POST[$field['name']])){
            $this->fields[$field_id]['value'] = $this->filterNumber($_POST[$field['name']],64);
          }
        break;
        case 'upload':
          if((isset($_FILES[$field['name']])) && (!empty($_FILES[$field['name']]))){
            if((isset($field['use'])) && ($field['use'] == 'raw')){
              $this->fields[$field_id]['value'] = (filesize($_FILES[$field['name']]['tmp_name']) > 0) ? file_get_contents($_FILES[$field['name']]['tmp_name']) : false; // Uses the raw file contents
              if(file_exists($_FILES[$field['name']]['tmp_name'])){
              	unlink($_FILES[$field['name']]['tmp_name']); // Delete tmp file for security purposes.
              }
            } else {
              $this->fields[$field_id]['value'] = $_FILES[$field['name']]['tmp_name']; // Uses the temporary filename
            }
            $this->fields[$field_id]['file_type'] = $_FILES[$field['name']]['type'];
            $this->fields[$field_id]['original_name'] = $_FILES[$field['name']]['name'];
            $this->fields[$field_id]['file_size'] = intval($_FILES[$field['name']]['size']);
          }       
        break;
        case 'textarea':
        case 'text':
        default:
          if(isset($_POST[$field['name']])){
            $this->fields[$field_id]['value'] = $_POST[$field['name']];
          }
        break;      
      }    
    }
  }
  
  /**
  * Gets a simple array of the form's final output data (name => value pairs)
  * Data is trimmed and escaped; ready for the database.
  */
  function getData(){
    $d = Array();
    foreach($this->fields as $field){
      if(is_array($field['value'])){
        foreach($field['value'] as $vn => $vv){
          $d[$field['name']][$vn] = ($field['type'] != 'upload') ? $this->SQLEscape(trim($vv)) : $vv;
        }      
      } else {
        $d[$field['name']] = ($field['type'] != 'upload') ? $this->SQLEscape(trim($field['value'])) : $field['value'];
      }
      // If the field is a password reset, include the old password field in the output data.
      if(($field['type'] == 'password_reset')){
        $d[$field['name'].'_old'] = (!empty($field['value_old'])) ? $this->SQLEscape(trim($field['value_old'])) : '';
      }
    }
    return $d;
  }
  
  function SQLEscape($x){
    return str_replace("'","''",$x);
  }
  
  /**
  * Gets a boolean response as to whether or not the form validated.
  */
  function isValid(){
    return ($this->valid && $this->submitted);
  }
  
}

?>