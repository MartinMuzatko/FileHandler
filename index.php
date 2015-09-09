<?php

use Core\Utility\File;
use Core\Utility\FileHandler;

echo '<pre>';
include 'Filehandler.php';
include 'File.php';


function getMillis()
{
	return microtime(true);
}
$x = getMillis();


$file = new File('new');
$file
	->create()
	->write('keks')
	->rename('old')
	->concat('meow')

;



$fs = FileHandler::listFiles(__DIR__, true, true);
var_dump($fs);

echo '<hr>';
echo 'execution time: '.number_format(getMillis() - $x, 3).'s';

echo '</pre>';





