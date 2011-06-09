<?php

Autoloader::add_core_namespace('Casset');

Autoloader::add_classes(array(
	'Casset\\Casset'				=> __DIR__.'/classes/casset.php',
	'Casset\\Casset_Jsmin'			=> __DIR__.'/classes/casset/jsmin.php',
	'Casset\\Casset_Csscompressor'	=> __DIR__.'/classes/casset/csscompressor.php',
	'Casset\\Casset_Cssurirewriter'	=> __DIR__.'/classes/casset/cssurirewriter.php',
));
?>
