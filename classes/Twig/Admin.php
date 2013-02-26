<?php

namespace CMF\Twig;

use Twig_Extension,
	Twig_Function_Function,
	Twig_Function_Method,
	Twig_Filter_Method;

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
			'pluralize' => new Twig_Function_Function('Inflector::pluralize'),
			'singularize' => new Twig_Function_Function('Inflector::singularize'),
			'str_repeat' => new Twig_Function_Function('str_repeat'),
			'slug' => new Twig_Function_Function('CMF::slug')
		);
	}
	
	public function getFilters()
    {
        return array(
        	'item_links' => new Twig_Filter_Method($this, 'itemLinks'),
        	'placeholder' => new Twig_Filter_Method($this, 'placeholder')
        );
    }
    
    /**
	 * For the placeholder tags that Redactor produces
	 */
	public function placeholder($text, $name, $template = '', $data = array())
	{
	    $pattern = '/\\{\\{ '.$name.' \\}\\}/sUi';
	    preg_match_all($pattern, $text, $hits);
	    $tags = $hits[0];
	    if (count($tags) === 0) return $text;
	    
	    if (empty($template))
	    {
	        return str_replace("{{ $name }}", '', $text);
	    }
	    else
	    {
	        $parts = preg_split($pattern, $text);
	        $offset = 1;
	        
	        for ($i = 0; $i < count($tags); $i++) {
	            
	            array_splice($parts, $offset, 0, strval(\View::forge($template, \Arr::merge($data, array( 'placeholder_num' => $i )), false)));
	            $offset += 2;
	            
	        }
	        
	        return implode('', $parts);
	    }
	}
	
	/**
	 * For the item links that Redactor produces
	 */
	public function itemLinks($value, $field, $prefix='', $suffix='')
	{
		// Process the item links
		return \CMF::processItemLinks($value, function($item) use($field, $prefix, $suffix) {
		    return $prefix.$item->get($field).$suffix;
		});
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
