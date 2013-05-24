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
    
    public static $static_urls = array();
    public static $module = '';
    public static $path = '';
    public static $template = '';
    public static $action = '';
    public static $root = false;
    public static $routed = false;
    
    /**
     * Uses Fuel's Inflector::friendly_title(), but also replaces a few extra characters
     * to make URLs more readable
     * 
     * @param string $input The string to transform
     * @return string The URL friendly slug
     */
    public static function slug($input, $lowercase = true)
    {
        $input = str_replace(array(".", ",", "'", '"'), "", $input);
        return \Inflector::friendly_title($input, '-', $lowercase);
    }
    
    public static function fieldId($input)
    {
        return trim('field-'.str_replace(array('][', '[', ']'), '-', $input), '-');
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
	    
	    $url = '/'.trim(\Input::uri(), '/');
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
        
	    $url = \CMF\Model\URL::findBy($filters)->getQuery()->getResult();
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
     * A bit like PHP's get_class, but makes sure we don't end up with a weird Proxy model class
     */
    public static function getClass($item)
    {
        $class = get_class($item);
        return (strpos($class, 'Proxy') === 0) ? get_parent_class($item) : $class;
    }
    
    /**
     * Retrieves the url to a static model
     * @param string $model The fully qualified class name of the static model
     * @return string Url of the model
     */
    public static function getStaticUrl($model)
    {
        if (isset(\CMF::$static_urls[$model])) return \CMF::$static_urls[$model];
        
        $url = $model::select('url.url')
        ->leftJoin('item.url', 'url')
        ->setMaxResults(1)
        ->getQuery()->getSingleScalarResult();
        
        return \CMF::$static_urls[$model] = $url;
    }
    
    /**
     * Returns a unique path for a file. Check that the name does not exist,
     * and appends a suffix otherwise.
     * @param string $directory Target directory
     * @param string $filename The name of the file to use.
     */
    public static function getUniqueFilePath($directory, $filename)
    {
        // Allow only one process at the time to get a unique file name, otherwise
        // if multiple people would upload a file with the same name at the same time
        // only the latest would be saved.

        if (function_exists('sem_acquire')){
            $lock = sem_get(ftok(__FILE__, 'u'));
            sem_acquire($lock);
        }
        
        $pathinfo = pathinfo($filename);
        $base = \CMF::slug($pathinfo['filename'], false);
        $ext = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';
        $ext = $ext == '' ? $ext : '.' . $ext;
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);

        $unique = $base;
        $suffix = 0;
        
        if (file_exists($directory . DIRECTORY_SEPARATOR . $unique . $ext)) {
            
            // Get unique file name for the file, by appending an integer suffix.
            $counter = 0;
            do {
                $suffix = '_'.++$counter;
                $unique = $base.$suffix;
            } while (file_exists($directory . DIRECTORY_SEPARATOR . $unique . $ext));
            
        }
        
        $result =  $directory . DIRECTORY_SEPARATOR . $unique . $ext;

        // Create an empty target file
        if (!touch($result)){
            // Failed
            $result = false;
        }

        if (function_exists('sem_acquire')){
            sem_release($lock);
        }

        return $result;
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
        } else {
            $output = "http://" . $output;
        }
        return $output;
    }
    
    /**
     * Given a string containing item links generated from Redactor, will loop through each and provide
     * the details to a callback. The value returned from the callback will be used as the href in each link
     * @return void
     */
    public static function processItemLinks($content, $callback)
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        @$doc->loadHTML("<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />".$content);
        $doc->encoding = 'UTF-8';
        
        $xpath = new \DOMXPath($doc);
        $item_links = $xpath->query('//a[@data-item-id]');
        
        foreach ($item_links as $num => $item_link) {
            
            $item_type = $item_link->getAttribute('data-item-type');
            $item_id = intval($item_link->getAttribute('data-item-id'));
            $item = $item_type::find($item_id);
            $item_href = $callback($item, $item_type);
            $item_link->setAttribute('href', strval($item_href));
            
        }
        
        // Apparently there's no better way of outputting the html without the <body>, doctype and other crap around it
        return preg_replace(array("/^\<\!DOCTYPE.*?<html>.*<body>/si", "!</body></html>$!si"), "", $doc->saveHTML());
        
    }
    
    public static function getCropUrl($image, $cropid, $w, $h)
    {
        $crop = empty($image) ? false : \Arr::get($image, 'crop.'.$cropid, false);
        $src = (isset($image['src']) && !empty($image['src'])) ? $image['src'] : 'placeholder.png';
        
        if ($crop === false) return '/image/2/'.$w.'/'.$h.'/'.$src;
        
        return '/image/'.$crop['x'].'/'.$crop['y'].'/'.$crop['width'].'/'.$crop['height'].'/'.$w.'/'.$h.'/'.$src;
    }
	
}