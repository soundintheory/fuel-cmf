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
			'slug' => new Twig_Function_Function('CMF::slug'),
			'phpinfo' => new Twig_Function_Function('phpinfo')
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
	public function itemLinks($value, $opts, $prefix = '', $suffix = '')
	{
		$default = array(
			'field' => 'url',
			'prefix' => $prefix,
			'suffix' => $suffix
		);
		
		if (!is_array($opts)) {
			$default['field'] = $opts;
			$opts = array();
		} elseif (isset($opts['field'])) {
			$default = $opts;
			$default['prefix'] = isset($default['prefix']) ? $default['prefix'] : '';
			$default['suffix'] = isset($default['suffix']) ? $default['suffix'] : '';
		}
		
		foreach ($opts as $opts_type => &$type_opts) {
			if (!is_array($type_opts)) continue;
			$type_opts['field'] = isset($type_opts['field']) ? $type_opts['field'] : $default['field'];
			$type_opts['prefix'] = isset($type_opts['prefix']) ? $type_opts['prefix'] : $default['prefix'];
			$type_opts['suffix'] = isset($type_opts['suffix']) ? $type_opts['suffix'] : $default['suffix'];
		}
		
		// Process the item links
		return \CMF::processItemLinks($value, function($item, $type) use($opts, $default) {
			$item_opts = \Arr::get($opts, $type, $default);
		    return $item_opts['prefix'].$item->get($item_opts['field']).$item_opts['suffix'];
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
