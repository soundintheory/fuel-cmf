<?php

namespace CMF\Twig;

use Twig_Extension,
	Twig_SimpleFunction,
	Twig_SimpleFilter;

/**
 * Provides Twig support for commonly used FuelPHP classes and methods.
 */
class Extension extends Twig_Extension
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
			new Twig_SimpleFunction('field_list_value', array($this, 'fieldListValue')),
			new Twig_SimpleFunction('get_flash', 'Session::get_flash'),
			new Twig_SimpleFunction('get_link', 'CMF::getLink'),
			new Twig_SimpleFunction('video_embed', 'CMF\\Field\\Object\\VideoEmbed::getEmbedCode'),
			new Twig_SimpleFunction('get_route', 'Router::get'),
			new Twig_SimpleFunction('get_setting', array($this, 'settings')),
			new Twig_SimpleFunction('static_url', 'CMF::getStaticUrl'),
			new Twig_SimpleFunction('pluralize', 'Inflector::pluralize'),
			new Twig_SimpleFunction('singularize', 'Inflector::singularize'),
			new Twig_SimpleFunction('ordinalize', 'Inflector::ordinalize'),
			new Twig_SimpleFunction('str_repeat', 'str_repeat'),
			new Twig_SimpleFunction('array_to_attr', 'array_to_attr'),
			new Twig_SimpleFunction('phpinfo', 'phpinfo'),
			new Twig_SimpleFunction('basename', 'basename'),
			new Twig_SimpleFunction('uri', array($this, 'uri')),
			new Twig_SimpleFunction('base', array($this, 'base')),
			new Twig_SimpleFunction('image', 'CMF\\Image::getUrl'),
			new Twig_SimpleFunction('crop_url', 'CMF\\Image::getUrl'),
			new Twig_SimpleFunction('asset', 'CMF::asset'),
			new Twig_SimpleFunction('link' , 'CMF::link'),
			new Twig_SimpleFunction('lang_enabled', 'CMF::langEnabled'),
			new Twig_SimpleFunction('language', 'CMF::lang'),
			new Twig_SimpleFunction('default_language', 'CMF::defaultLang'),
			new Twig_SimpleFunction('languages', 'CMF::languages'),
			new Twig_SimpleFunction('languageUrls', 'CMF::languageUrls'),
			new Twig_SimpleFunction('all_languages', 'Admin::languages'),
			new Twig_SimpleFunction('_', 'Lang::get'),
			new Twig_SimpleFunction('session_set', 'Session::set'),
			new Twig_SimpleFunction('session_get', 'Session::get'),
			new Twig_SimpleFunction('get_content', 'file_get_contents'),
			new Twig_SimpleFunction('module_url', 'CMF::moduleUrl'),
			new Twig_SimpleFunction('current_module', 'CMF::currentModule'),
			new Twig_SimpleFunction('get_options', 'CMF::getOptions'),
			new Twig_SimpleFunction('get_options_select', array($this, 'getOptionsSelect')),
			new Twig_SimpleFunction('get_hostname', array($this, 'getHostname')),
			new Twig_SimpleFunction('array_as_hidden_inputs', 'CMF::arrayAsHiddenInputs'),
            new Twig_SimpleFunction('get_class', 'get_class')
		);
	}
	
	public function getFilters()
    {
        return array(
        	new Twig_SimpleFilter('time_ago', 'Date::time_ago'),
        	new Twig_SimpleFilter('item_links', array($this, 'itemLinks')),
        	new Twig_SimpleFilter('placeholder', array($this, 'placeholder')),
        	new Twig_SimpleFilter('slug', 'CMF::slug'),
        	new Twig_SimpleFilter('rtrim', 'rtrim'),
        	new Twig_SimpleFilter('delimiter', array($this, 'delimiterFilter'))
        );
    }

    public function uri($includeBaseUrl = false)
    {
    	$base = \Uri::base(false);
    	$baseUrl = $this->generateBaseUrl();
    	if ($includeBaseUrl && strpos($base, $baseUrl) === false && strpos($base, 'http') !== 0 && strpos($base, '//') !== 0) {
    		$base = $baseUrl.trim($base, '/');
    	}
    	return rtrim($base, '/').'/'.trim(\Input::uri(), '/');
    }

    public function base()
    {
    	$base = \Uri::base(false);
    	$baseUrl = $this->generateBaseUrl();
    	if (strpos($base, $baseUrl) === false && strpos($base, 'http') !== 0 && strpos($base, '//') !== 0) {
    		$base = $baseUrl.trim($base, '/');
    	}
    	return $base;
    }

    public function delimiterFilter($value, $tag = 'b', $delimiter_start = '{', $delimiter_end = '}')
    {
    	if (strpos($value, $delimiter_start) === false) return $value;
    	$pattern = '/'.preg_quote($delimiter_start).'(.*?)'.preg_quote($delimiter_end).'/sUi';
    	return preg_replace($pattern, "<$tag>$1</$tag>", $value);
    }
    
    public function getOptionsSelect($model, $field = null, $values = array(), $attributes = array())
    {
    	$options = \CMF::getOptions($model, $field);
    	$field = \Arr::get($attributes, 'name', $field !== null ? $field : \Inflector::friendly_title($model));
    	
    	return \Form::select($field, $values, $options, $attributes);
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

	public function getHostname($url = null)
	{
		if (empty($url)) $url = \Uri::base(false);
		return parse_url($url, PHP_URL_HOST);
	}
	
	public function settings($name, $default=null)
	{
		return \CMF\Model\Settings::instance()->get($name, $default);
	}

	public function fieldListValue($value, $edit_link, $settings, $model)
	{
		$class_name = @$settings['field'];
		if (!$class_name) $class_name = 'CMF\\Field\\Text';
		return $class_name::displayList($value, $edit_link, $settings, $model);
	}

	/**
	 * Generates a base url.
	 *
	 * @return  string  the base url
	 */
	protected $_base_url = null;
	protected function generateBaseUrl()
	{
		if ($this->_base_url === null)
		{
			$base_url = '';
			if (\Input::server('http_host')) {
				$base_url .= \Input::protocol().'://'.\Input::server('http_host');
			}
			$this->_base_url = rtrim($base_url, '/').'/';
		}
		return $this->_base_url;
	}
	
}
