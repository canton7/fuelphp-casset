<?php

/**
 * Casset: Convenient asset library for FuelPHP.
 *
 * @package    Casset
 * @version    v1.1
 * @author     Antony Male
 * @license    MIT License
 * @link       http://github.com/canton7/fuelphp-casset
 */


Autoloader::add_core_namespace('Casset');

Autoloader::add_classes(array(
	'Casset\\Casset'				=> __DIR__.'/classes/casset.php',
	'Casset\\Casset_Jsmin'			=> __DIR__.'/classes/casset/jsmin.php',
	'Casset\\Casset_Csscompressor'	=> __DIR__.'/classes/casset/csscompressor.php',
	'Casset\\Casset_Cssurirewriter'	=> __DIR__.'/classes/casset/cssurirewriter.php',
));

/* End of file bootstrap.php */