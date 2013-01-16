<?php

namespace CMF\Twig;

use Twig_Extension;
use Twig_Function_Function;
use Twig_Function_Method;

/**
 * Provides Twig support for commonly used FuelPHP classes and methods.
 */
class Admin extends Twig_Extension
{
	/**
	 * Gets the name of the extension.
	 *
	 * @return  string
	 */
	public function getName()
	{
		return 'admin';
	}

	/**
	 * Sets up all of the functions this extension makes available.
	 *
	 * @return  array
	 */
	public function getFunctions()
	{
		return array(
			'field_list_value' => new Twig_Function_Method($this, 'fieldListValue'),
			'get_flash' => new Twig_Function_Function('Session::get_flash'),
			'get_link' => new Twig_Function_Function('CMF::getLink'),
			'get_route' => new Twig_Function_Function('Router::get'),
			'get_setting' => new Twig_Function_Method($this, 'settings'),
			'static_url' => new Twig_Function_Function('CMF::getStaticUrl'),
		);
	}
	
	public function settings($name, $default=null)
	{
		return \CMF\Model\Settings::instance()->get($name, $default);
	}
	
	public function fieldListValue($value, $edit_link, $settings, $model)
	{
		$class_name = $settings['field'];
		return $class_name::displayList($value, $edit_link, $settings, $model);
	}
	
}
