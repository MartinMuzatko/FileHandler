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

$customers = ['01-jake', '02-mike', '03-francis', '04-martin', '05-jane'];
foreach ($customers as $customer)
{
	$file = new File($customer.'/info.json');
	$file
		->create()
		->write('[{ title: "Read instructions."}]')
		->chmod(0644);
}


$fs = FileHandler::listFiles(__DIR__, true, true);
var_dump($fs);

echo '<hr>';
echo 'execution time: '.number_format(getMillis() - $x, 3).'s';

echo '</pre>';





