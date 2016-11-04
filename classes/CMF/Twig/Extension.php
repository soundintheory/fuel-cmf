<?php

namespace CMF\Twig;

use Twig_Extension,
	Twig_Function_Function,
	Twig_Function_Method,
	Twig_Filter_Function,
	Twig_Filter_Method;

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
			'field_list_value' => new Twig_Function_Method($this, 'fieldListValue'),
			'get_flash' => new Twig_Function_Function('Session::get_flash'),
			'get_link' => new Twig_Function_Function('CMF::getLink'),
			'video_embed' => new Twig_Function_Function('CMF\\Field\\Object\\VideoEmbed::getEmbedCode'),
			'get_route' => new Twig_Function_Function('Router::get'),
			'get_setting' => new Twig_Function_Method($this, 'settings'),
			'static_url' => new Twig_Function_Function('CMF::getStaticUrl'),
			'pluralize' => new Twig_Function_Function('Inflector::pluralize'),
			'singularize' => new Twig_Function_Function('Inflector::singularize'),
			'ordinalize' => new Twig_Function_Function('Inflector::ordinalize'),
			'str_repeat' => new Twig_Function_Function('str_repeat'),
			'array_to_attr' => new Twig_Function_Function('array_to_attr'),
			'phpinfo' => new Twig_Function_Function('phpinfo'),
			'basename' => new Twig_Function_Function('basename'),
			'uri' => new Twig_Function_Method($this, 'uri'),
			'base' => new Twig_Function_Method($this, 'base'),
			'image' => new Twig_Function_Function('CMF\\Image::getUrl'),
			'crop_url' => new Twig_Function_Function('CMF\\Image::getUrl'),
			'asset' => new Twig_Function_Function('CMF::asset'),
			'link' => new Twig_Function_Function('CMF::link'),
			'lang_enabled' => new Twig_Function_Function('CMF::langEnabled'),
			'language' => new Twig_Function_Function('CMF::lang'),
			'default_language' => new Twig_Function_Function('CMF::defaultLang'),
			'languages' => new Twig_Function_Function('CMF::languages'),
			'languageUrls' => new Twig_Function_Function('CMF::languageUrls'),
			'all_languages' => new Twig_Function_Function('Admin::languages'),
			'_' => new Twig_Function_Function('Lang::get'),
			'session_set' => new Twig_Function_Function('Session::set'),
			'session_get' => new Twig_Function_Function('Session::get'),
			'get_content' => new Twig_Function_Function('file_get_contents'),
			'module_url' => new Twig_Function_Function('CMF::moduleUrl'),
			'current_module' => new Twig_Function_Function('CMF::currentModule'),
			'get_options' => new Twig_Function_Function('CMF::getOptions'),
			'get_options_select' => new Twig_Function_Method($this, 'getOptionsSelect'),
			'get_hostname' => new Twig_Function_Method($this, 'getHostname'),
			'array_as_hidden_inputs' => new Twig_Function_Function('CMF::arrayAsHiddenInputs')
		);
	}
	
	public function getFilters()
    {
        return array(
        	'time_ago' => new Twig_Filter_Function('Date::time_ago'),
        	'item_links' => new Twig_Filter_Method($this, 'itemLinks'),
        	'placeholder' => new Twig_Filter_Method($this, 'placeholder'),
        	'slug' => new Twig_Filter_Function('CMF::slug'),
        	'rtrim' => new Twig_Filter_Function('rtrim'),
        	'delimiter' => new Twig_Filter_Method($this, 'delimiterFilter')
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
