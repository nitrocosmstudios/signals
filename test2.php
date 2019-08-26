<?php 

require('./dat/classes/audioGen.php');
//require('./dat/classes/dtmf.php');
//require('./dat/classes/mf.php');
//require('./dat/classes/morse.php');
require('./dat/classes/afsk.php');
//require('./dat/classes/spectrumText.php');
//require('./dat/classes/rtty.php');

/*******************************
******* DTMF DEMO **************

$dtmf = new dtmf(44100);

//$audio = $dtmf->genDialTone(4000,75); // Generates a dial tone.
//$audio = $dtmf->genWave('sine',440,2000,50); // Generates a plain sine wave.

$audio = $dtmf->genDTMF('123456789',1,50,500,8); // Generates DTMF tones.

$dtmf->addSamples($audio);
file_put_contents('test.wav',$dtmf->buildWAV());

********************************/

/********* MORSE CODE DEMO *********

$morse = new morse(8000);

$message = "Hello.  I like pie.  123456789.";
$audio = $morse->genMorse($message,20,440,25,20);

$morse->addSamples($audio);
file_put_contents('test.wav',$morse->buildWAV());

********************************/


/*

$afsk = new afsk(44100);

$msg = 'Nitrocosm Studios.  The biscuits and gravy of the Internet.  Lots of pie and yes it is.  Bagels and Meatloaf!';

$audio = $afsk->genAFSK($msg,1200,2200,1200,95); // Bell 202

$afsk->addSamples($audio);
file_put_contents('test.wav',$afsk->buildWAV());


// Test decoding.
exec('minimodem -r -f ./test.wav 1200 > test.txt');
$afsk->debugHex(file_get_contents('test.txt'));

*/



/*****************************

$mf = new mf(44100);

$audio = $mf->genMF('123456789',1,50,500,8); // Generates MF tones.

$mf->addSamples($audio);
file_put_contents('test.wav',$mf->buildWAV());

*********************************/


/*****************************

$specText = new spectrumText(44100);

// $audio = $specText->genSpectrumText('Bacon is yum!  Tuna!',2000,200,200,75);

$audio = $specText->genSpectrumText('Bacon is yum!  Tuna!',12000,200,80,75);

$specText->addSamples($audio);
file_put_contents('test.wav',$specText->buildWAV());

*********************************/

/********************************
$rtty = new rtty(44100);

$msg = "I am Nitrocosm.";
$audio = $rtty->genRTTY($msg,500,170,45.45,50);
$rtty->addSamples($audio);
file_put_contents('rtty_test.wav',$rtty->buildWAV());

**********************************/


?>