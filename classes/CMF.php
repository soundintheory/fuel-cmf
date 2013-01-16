<?php

namespace CMF;

/**
 * Provides some simple static methods for use in CMF based apps. Also stores some information
 * about the current application state
 * 
 * @package  CMF
 */
class CMF
{
    protected static $model;
    protected static $static_urls = array();
    
    public static $module = '';
    public static $path = '';
    public static $template = '';
    public static $action = '';
    public static $root = false;
    
    /**
     * Uses Fuel's Inflector::friendly_title(), but also replaces a few extra characters
     * to make URLs more readable
     * 
     * @param string $input The string to transform
     * @return string The URL friendly slug
     */
    public static function slug($input)
    {
        $input = str_replace(array(".", ",", "'", '"'), "", $input);
        return \Inflector::friendly_title($input, '-', true);
    }
    
    /**
     * Finds the model associated with the current URL and returns it.
     * 
     * @param class $type The model class, in case you want to narrow down the search
     * @return object The model
     */
    public static function currentModel($type = null)
    {
        if (isset(static::$model)) return static::$model;
	    
	    $url = \Input::uri();
        if (empty($url)) $url = '/';
        
	    $model = static::getItemByUrl($url, $type);
        
	    if (is_null($model)) {
	        
	        $segments = explode('/', $url);
	        $url = implode('/', array_slice($segments, 0, -1));
	        static::$root = empty($url);
	        static::$action = array_pop($segments);
	        
	        $model = static::getItemByUrl($url, $type);
	        
	    }
        
        if (is_null($model)) return null;
	    
	    return static::setCurrentModel($model);
    }
    
    /**
     * Sets the current model, so \CMF::currentModel() will return this one.
     * 
     * @param object $model
     */
    public static function setCurrentModel($model)
    {
    	$model_class = get_class($model);
    	static::$template = $model_class::template();
        return static::$model = $model;
    }
    
    /**
     * Finds the model associated with the given URL
     * 
     * @param string $url The URL to search against (no trailing slashes please)
     * @param string|null $type The model class, in case you want to narrow down the search
     * @return object The model
     */
    public static function getItemByUrl($url, $type = null)
    {
        $qb = \CMF\Model\URL::select('item')->where("item.url = '$url'");
        if (!is_null($type)) $qb->andWhere("item.type = '$type'");
	    $url_item = $qb->getQuery()->getResult();
        
	    if (count($url_item) === 0 && $url == '/') {
            $url_item = static::settings()->start_page;
            if (is_null($url_item)) return null;
        } else if (count($url_item) === 0) {
            return null;
        } else {
            $url_item = $url_item[0];
        }
        
	    $item = $url_item->item();
	    
	    if (is_array($item) && count($item) > 0) {
	        $item = $item[0];
	    } else {
	        $item = null;
	    }
	    
	    return $item;
    }
    
    /**
     * Finds the model with the given slug
     * 
     * @param $slug The slug to search against
     * @param string $type The model class, in case you want to narrow down the search
     * @return object The model
     */
    public static function getItemBySlug($slug = null, $type = null)
    {
        $filters = array("slug = '$slug'");
        if (!is_null($type)) $filters[] = "type = '$type'";
        
	    $url = \CMF\Model\URL::findBy($filters);
	    if (count($url) == 0) return null;
	    
	    $url = $url[0];
	    $item = $url->item();
	    
	    if (is_array($item) && count($item) > 0) {
	        $item = $item[0];
	    } else {
	        $item = null;
	    }
	    
	    return $item;
    }
    
    /**
     * Used in the 'behind the scenes' logic during a frontend request, this checks whether
     * there is a viewmodel associated with a particular template.
     * 
     * @param string $template Path to the template, relative to the root of the views directory
     * @return boolean
     */
    public static function hasViewModel($template)
	{
	    static::$path = strpos($template, '.') === false ? $template : substr($template, 0, -strlen(strrchr($template, '.')));
		
		// determine the viewmodel namespace and classname
		static::$module = \Request::active() ? ucfirst(\Request::active()->module) : '';
		$viewmodel_class = static::$module.'\\View_'.\Inflector::words_to_upper(ucfirst(str_replace(array('/', DS), '_', static::$path)));
		
		return class_exists($viewmodel_class);
	}
	
	/**
	 * Used in the 'behind the scenes' logic during a frontend request, this checks whether
     * there is a controller associated with a particular template
     * 
	 * @param string $template Path to the template, relative to the root of the views directory
	 * @return boolean
	 */
	public static function hasController($template)
	{
	    static::$path = strpos($template, '.') === false ? $template : substr($template, 0, -strlen(strrchr($template, '.')));
		
		// determine the viewmodel namespace and classname
		static::$module = \Request::active() ? ucfirst(\Request::active()->module) : '';
		$controller_class = static::$module.'\\Controller_'.\Inflector::words_to_upper(ucfirst(str_replace(array('/', DS), '_', static::$path)));
		
		return class_exists($controller_class);
	}
    
    /**
     * Gets an instance of the settings model
     * @param string $class The class name of the settings model, in case you want to get a different one.
     * @return \CMF\Model\Settings
     */
    public static function settings($class = '\\Model_Settings')
    {
        return $class::instance();
    }
    
    /**
     * Retrieves the url to a static model
     * @param string $model The fully qualified class name of the static model
     * @return string Url of the model
     */
    public function getStaticUrl($model)
    {
        if (isset(static::$static_urls[$model])) return static::$static_urls[$model];
        
        $url = $model::select('url.url')
        ->leftJoin('item.url', 'url')
        ->setMaxResults(1)
        ->getQuery()->getSingleScalarResult();
        
        return static::$static_urls[$model] = $url;
    }
    
    /**
     * Given the value of a link object field (external or internal), will return the correct url
     * @param object $data
     * @return string
     */
    public static function getLink($data)
    {
        // Get the link attribute
        if ($is_array = is_array($data)) {
            $output = isset($data['href']) ? $data['href'] : '';
        } else {
            $output = $data;
        }
        
        // Query the urls table if it's an ID
        if (is_numeric($output)) {
            $link = \CMF\Model\URL::select('item.url')->where('item.id = '.$output)->getQuery()->getArrayResult();
            $output = (count($link) > 0) ? $link[0]['url'] : null;
        }
        
        return $output;
    }
	
}