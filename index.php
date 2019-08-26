<?php include('dat/main.php'); // Include main backend ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" >

  <head>
    
    <title>Signal Generator: <?php echo $cfg['title']; ?></title>
    
    <base href="<?php echo $cfg['base_url'];?>" />
    
    <script type="text/javascript" src="js/js.js"></script>
    <link rel="stylesheet" type="text/css" href="css/style.css" />
    <link rel="icon" href="img/favicon.ico" type="image/x-icon" />
    
  </head>

  <body id="body">
  
    <div id="header">
    
      <h1>Audio Signal Experiment Workshop</h1>
      <h2>Basic digital audio encoding systems</h2>
      <h3>Current mode: <?php echo $cfg['title']; ?></h3>
    
    </div>
    
    <div id="menu">
    
      <?php echo $HTML['menu']; ?>
    
    </div>
      
    <div id="main">
      
      <?php echo $HTML['main']; ?>
      
    </div>
    
    <div id="description">
    
      <p><?php echo $HTML['description']; ?></p>
      
      <p>After submitting the form, the resulting file will download to your computer.</p>
    
    </div>
    
  </body>
</html>