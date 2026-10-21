<?php

namespace CMF\Twig;

use Twig\Extension\AbstractExtension,
	Twig\TwigFunction,
	Twig\TwigFilter;

/**
 * Provides Twig support for commonly used FuelPHP classes and methods.
 */
class Extension extends AbstractExtension
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
			new TwigFunction('field_list_value', array($this, 'fieldListValue')),
			new TwigFunction('get_flash', 'Session::get_flash'),
			new TwigFunction('get_link', 'CMF::getLink'),
            new TwigFunction('get_type', 'CMF::getType'),
            new TwigFunction('get_slug', 'CMF::getSlug'),
			new TwigFunction('video_embed', 'CMF\\Field\\Object\\VideoEmbed::getEmbedCode'),
			new TwigFunction('get_route', 'Router::get'),
			new TwigFunction('get_setting', array($this, 'settings')),
			new TwigFunction('static_url', 'CMF::getStaticUrl'),
			new TwigFunction('pluralize', 'Inflector::pluralize'),
			new TwigFunction('singularize', 'Inflector::singularize'),
			new TwigFunction('ordinalize', 'Inflector::ordinalize'),
			new TwigFunction('str_repeat', 'str_repeat'),
			new TwigFunction('array_to_attr', 'array_to_attr'),
			new TwigFunction('basename', 'basename'),
			new TwigFunction('uri', array($this, 'uri')),
			new TwigFunction('base', array($this, 'base')),
			new TwigFunction('image', 'CMF\\Image::getUrl'),
			new TwigFunction('crop_url', 'CMF\\Image::getUrl'),
			new TwigFunction('asset', 'CMF::asset'),
			new TwigFunction('link' , 'CMF::link'),
			new TwigFunction('lang_enabled', 'CMF::langEnabled'),
			new TwigFunction('language', 'CMF::lang'),
			new TwigFunction('default_language', 'CMF::defaultLang'),
			new TwigFunction('languages', 'CMF::languages'),
			new TwigFunction('languageUrls', 'CMF::languageUrls'),
			new TwigFunction('all_languages', 'Admin::languages'),
			new TwigFunction('_', 'Lang::get'),
			new TwigFunction('session_set', 'Session::set'),
			new TwigFunction('session_get', 'Session::get'),
			new TwigFunction('get_content', 'file_get_contents'),
			new TwigFunction('module_url', 'CMF::moduleUrl'),
			new TwigFunction('current_module', 'CMF::currentModule'),
			new TwigFunction('get_options', 'CMF::getOptions'),
			new TwigFunction('get_options_select', array($this, 'getOptionsSelect')),
			new TwigFunction('get_hostname', array($this, 'getHostname')),
			new TwigFunction('array_as_hidden_inputs', 'CMF::arrayAsHiddenInputs'),
            new TwigFunction('get_class', 'get_class'),
            new TwigFunction('is_subclass_of', 'is_subclass_of'),
            new TwigFunction('admin_url', 'CMF::adminUrl'),
            new TwigFunction('admin_path', 'CMF::adminPath')
		);
	}
	
	public function getFilters()
    {
        return array(
        	new TwigFilter('time_ago', 'Date::time_ago'),
        	new TwigFilter('item_links', array($this, 'itemLinks')),
        	new TwigFilter('placeholder', array($this, 'placeholder')),
        	new TwigFilter('slug', 'CMF::slug'),
        	new TwigFilter('rtrim', 'rtrim'),
        	new TwigFilter('delimiter', array($this, 'delimiterFilter'))
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
