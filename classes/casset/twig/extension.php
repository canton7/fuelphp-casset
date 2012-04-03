<?php

/**
 * Casset: Convenient asset library for FuelPHP.
 *
 * @package    Casset
 * @version    v1.14
 * @author     Antony Male
 * @license    MIT License
 * @copyright  2011 Antony Male
 * @link       http://github.com/canton7/fuelphp-casset
 */

namespace Casset;

class Casset_Twig_Extension extends Twig_Extension
{
	/**
	 * Gets the name of the extension.
	 *
	 * @return  string
	 */
	public function getName()
	{
		return 'casset';
	}

	/**
	 * Sets up all of the functions this extension makes available.
	 *
	 * @return  array
	 */
	public function getFunctions()
	{
		return array(
			'display_css' => new Twig_Function_Function('Casset::render_css'),
			'display_js'  => new Twig_Function_Function('Casset::render_js'),
			'display_img' => new Twig_Function_Function('Casset::img'),
		);
	}
}

/* End of file extension.php */
