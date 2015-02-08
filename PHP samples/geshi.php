<?

// Include the GeSHi library
include('geshi/geshi.php');

// Choose file
$myFile = $_SERVER['argv'][0];
$fh = fopen($myFile, 'r');
$contents = fread($fh, 20000);
fclose($fh);

// Make a new GeSHi object, with the source, language and path set
$source = $contents;

$language = 'php';
$path = 'geshi/';

$geshi = new GeSHi($source, $language, $path);


// and simply dump the code!
echo $geshi->parse_code();
?>