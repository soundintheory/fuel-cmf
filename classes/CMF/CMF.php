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
	protected static $uri = null;
	protected static $lang = null;
	protected static $lang_default = null;
	protected static $lang_prefix = '';
	protected static $languages = null;
	
	public static $lang_enabled = false;
	public static $static_urls = array();
	public static $static_urls_raw = array();
	public static $static_links = array();
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
	public static function slug($input, $lowercase = true, $allow_non_ascii = false)
	{
		$input = str_replace(array(".", ",", "'", '"'), "", $input);
		return \Inflector::friendly_title($input, '-', $lowercase, $allow_non_ascii);
	}
	
	public static function fieldId($input)
	{
		return trim('field-'.str_replace(array('][', '[', ']'), '-', $input), '-');
	}
	
	public static function original_uri()
	{
		if (static::$uri !== null)
		{
			return static::$uri;
		}

		if (\Fuel::$is_cli)
		{
			if ($uri = \Cli::option('uri') !== null)
			{
				static::$uri = $uri;
			}
			else
			{
				static::$uri = \Cli::option(1);
			}

			return static::$uri;
		}

		// We want to use PATH_INFO if we can.
		if ( ! empty($_SERVER['PATH_INFO']))
		{
			$uri = $_SERVER['PATH_INFO'];
		}
		// Only use ORIG_PATH_INFO if it contains the path
		elseif ( ! empty($_SERVER['ORIG_PATH_INFO']) and ($path = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['ORIG_PATH_INFO'])) != '')
		{
			$uri = $path;
		}
		else
		{
			// Fall back to parsing the REQUEST URI
			if (isset($_SERVER['REQUEST_URI']))
			{
				$uri = urldecode($_SERVER['REQUEST_URI']);
			}
			else
			{
				throw new \FuelException('CMF was unable to detect the URI.');
			}

			// Remove the base URL from the URI
			$base_url = parse_url(\Config::get('base_url'), PHP_URL_PATH);
			if ($uri != '' and strncmp($uri, $base_url, strlen($base_url)) === 0)
			{
				$uri = substr($uri, strlen($base_url));
			}

			// If we are using an index file (not mod_rewrite) then remove it
			$index_file = \Config::get('index_file');
			if ($index_file and strncmp($uri, $index_file, strlen($index_file)) === 0)
			{
				$uri = substr($uri, strlen($index_file));
			}

			// When index.php? is used and the config is set wrong, lets just
			// be nice and help them out.
			if ($index_file and strncmp($uri, '?/', 2) === 0)
			{
				$uri = substr($uri, 1);
			}

			// Lets split the URI up in case it contains a ?.  This would
			// indicate the server requires 'index.php?' and that mod_rewrite
			// is not being used.
			preg_match('#(.*?)\?(.*)#i', $uri, $matches);

			// If there are matches then lets set set everything correctly
			if ( ! empty($matches))
			{
				$uri = $matches[1];
				$_SERVER['QUERY_STRING'] = $matches[2];
				parse_str($matches[2], $_GET);
			}
		}

		// Deal with any trailing dots
		$uri = '/'.trim(rtrim($uri, '.'), '/');

		return static::$uri = $uri;
	}
	
	public static function getOptions($model, $field = null , $default = array())
	{
		if ($field !== null) {
			$method = 'get'.\Inflector::camelize($field).'Options';
			if (method_exists($model, $method)) return call_user_func($model.'::'.$method, null);
			return $default;
		}
		
		return $model::options();
	}
	
	/**
	 * Set as a URI filter when modules are being used and custom urls have been set for them
	 * @param  string $uri
	 * @return string
	 */
	public static function module_url_filter($uri)
	{
		static::$uri = $uri;
		$segments = explode('/', ltrim($uri, '/'));
		$translate = \Config::get('cmf.module_urls.'.$segments[0], false);
		if ($translate !== false) {
			$segments[0] = static::$module = $translate;
			return '/'.implode($segments, '/');
		}
		
		return $uri;
	}
	
	/**
	 * Gets a module's URL as defined in the cmf.module_urls setting
	 * @return string
	 */
	public static function moduleUrl($module)
	{
		return array_search($module, \Config::get('cmf.module_urls', array()));
	}

	/**
	 * Gets a module's title as defined in the cmf.admin.modules setting
	 * @return string
	 */
	public static function moduleTitle($module)
	{
		return \Config::get("cmf.admin.modules.$module.title", \Config::get("cmf.admin.title"));
	}
	
	/**
	 * For the router, to translate hyphens to underscores
	 * @param  string $uri
	 * @return string
	 */
	public static function hyphens_to_underscores($uri)
	{
		return str_replace('-', '_', $uri);
	}
	
	/**
	 * The current fallback language
	 */
	public static function defaultLang()
	{
		return static::$lang_default;
	}
	
	public static function langIsDefault()
	{
		return static::$lang == static::$lang_default;
	}
	
	public static function langEnabled()
	{
		return static::$lang_enabled;
	}
	
	/**
	 * Gets the current language from either TLD, URL prefix or 
	 */
	public static function lang()
	{
		if (static::$lang !== null) return static::$lang;
		
		// Give up if we haven't enabled multi lingual
		if (!(static::$lang_enabled = \Config::get('cmf.languages.enabled', false))) return static::$lang = \Lang::get_lang();
		
		// First load our languages
		\Lang::load('languages', true);

		// Get the language from cookies
		$iso = \Cookie::get('default_language');
		$fallback = \Lang::get_lang();

		// Get the language from URL
		if (!$iso) {
			$languages = static::languages();
			$host = strtolower(\Input::server('HTTP_HOST', ''));
			foreach ($languages as $language) {
				if ($tld = \Arr::get($language, 'top_level_domain')) {
					$parts = array_filter(array_map(function($part) { return strtolower(trim($part)); }, explode(',', $tld)));
					if (in_array($host, $parts)) {
						$iso = $language['code'];
						break;
					}
				}
			}
		}
		
		// Get the language from the request
		if (!$iso) {
			$iso = \Arr::get(explode('/', static::original_uri()), 1, \Lang::get_lang())."";
			if (\Lang::get("languages.$iso") === null) $iso = \Lang::get_lang();
		}
		
		// Set the languages into Fuel for future reference
		\Config::set('language_fallback', $fallback);
		\Config::set('language', $iso);
		
		// Load the languages back in, now we might have a translation for them
		if ($fallback != $iso) {
			\Lang::load('languages', true, $iso, true);
			static::$lang_prefix = "/$iso";
		}
		
		// Set the uri filter so we don't see the lang prefix
		\Config::set('security.uri_filter', array_merge(
			array('\CMF::removeLangPrefix'),
			\Config::get('security.uri_filter')
		));
		
		// Log to console
		if (\Fuel::$profiling) {
			\Profiler::console('Language is '.$iso);
		}

		// Add shutdown event to catch unsaved translation strings
		\Event::register('shutdown', 'Lang::shutdown');
		
		// Set the lang vars
		static::$lang_default = $fallback;
		static::$lang = $iso;

		// Set locale if necessary
		if (is_array($locale_map = \Config::get('locale_map')) && $new_locale = \Arr::get($locale_map, $iso)) {
			$result = setlocale(LC_TIME, $new_locale);
			if ($result !== false) {
				\Fuel::$locale = $result;
				\Config::set('locale', $result);
				if (class_exists('Locale')) {
					\Locale::setDefault($result);
				}
			}
		}
		
		// Redirect to default language if this one isn't configured
		if (!array_key_exists($iso, static::languages()) && array_key_exists($fallback, static::languages())) {
			\Response::redirect(static::link(\Input::uri(), $fallback));
		}
		
		return $iso;
	}
	
	/**
	 * Sets the current language
	 */
	public static function setLang($lang)
	{
		static::$lang = $lang;
		\Config::set('language', $lang);
		
		if (static::$lang_default != $lang) {
			\Lang::load('languages', true, $lang, true);
			static::$lang_prefix = "/$lang";
		}
		
		\CMF\Doctrine\Extensions\Translatable::setLang($lang);
	}
	
	/**
	 * Gets a list of the active languages that have been configured
	 */
	public static function languages()
	{
		if (static::$languages !== null) return static::$languages;
		if (!static::$lang_enabled) return static::$languages = array(\Lang::get_lang());
		
		try {
			return static::$languages = \CMF\Model\Language::select('item.id, item.code, item.top_level_domain', 'item', 'item.code')
			->orderBy('item.pos', 'ASC')
			->where('item.visible = true')
			->getQuery()->getArrayResult();
		} catch (\Exception $e) {
			return array(\Lang::get_lang());
		}
	}
	
	/**
	 * Removes the current lang prefix from the given url
	 */
	public static function removeLangPrefix($url)
	{
		$prefix = '/'.static::lang();
		
		if ($url !== '/') $url = '/'.trim($url, '/');
		
		if ($url == $prefix) {
			return '/';
		} else if (strlen($url) > 3 && strpos($url, $prefix.'/') === 0) {
			return substr($url, 3);
		}
		
		return $url;
	}
	
	/**
	 * Transforms a URL to make sure it includes the correct lang prefix (unless top level domains are being used)
	 */
	public static function link($url = null, $lang = null)
	{
		if ($url === null) $url = \Input::uri();
		if (!static::$lang_enabled) return $url;

		$use_tld = \Config::get('cmf.languages.use_tld', false);
		$url = $url == '/' || $url == '' ? '/' : rtrim($url, '/');
		$lang = $lang === null ? static::lang() : $lang;
		$prefix = $use_tld ? '/' : '/'.$lang;
		
		if ($lang == static::$lang_default ||
			substr($url, 0, 1) != '/' ||
			(strlen($url) > 3 && strpos($url, $prefix.'/') === 0)) return $url;
		
		return $url == $prefix ? $url : rtrim($prefix.$url, '/');
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
		
		$url = static::original_uri();
		if (empty($url)) $url = '/';
		else $url = '/'.trim($url, '/');
		
		$model = static::getItemByUrl($url, $type);
		
		if ($model === null) {
			
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
		// Plain query for the urls table to avoid initialising Doctrine for 404s
		$url_item = \DB::query("SELECT type, item_id FROM urls WHERE url = '$url' AND alias_id IS NULL ".($type !== null ? "AND type = '$type'" : ""))->execute();

		if (count($url_item) === 0 && $url == '/') {
			$url_item = static::settings()->start_page;
			if (is_null($url_item)) return null;
			$item = $url_item->item();
		} else if (count($url_item) === 0) {
			return null;
		} else {
			$url_item = $url_item[0];
			$type = $url_item['type'];
			if (empty($type) || is_null($url_item['item_id'])) return null;
			
			$item = $type::select('item')->where('item.id = '.$url_item['item_id'])->getQuery()->getResult();
		}
		
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
	 * Gets all the urls in the site ready for generating a sitemap
	 * 
	 * @return array The urls
	 */
	public static function getUrlsForSitemap()
	{
		$types = \CMF\Model\URL::select('item.type type')->distinct()->getQuery()->getScalarResult();
		$types = array_map('current', $types);
		$urls = array();
		
		foreach ($types as $type) {
			
			$items = $type::select('item');
			if (!$type::_static()) $items->where('item.visible = true');
			$items = $items->getQuery()->getResult();
			
			if (property_exists($type, 'url')) {
				foreach ($items as $item) {
					
					$item_url = '/';
					$updated_at = $item->updated_at;
					
					if (!is_null($item->url)) {
						$item_url = $item->url->url;
						$urls[] = array( 'url' => $item_url, 'updated_at' => $updated_at );
					}
					
					$child_urls = $item->childUrls();
					if (count($child_urls) > 0) {
						foreach ($child_urls as $child_url) {
							$urls[] = array( 'url' => $item_url.'/'.$child_url, 'updated_at' => $updated_at );
						}
					}
					
				}
			}
			
		}
		
		return $urls;
		
	}
	
	public static function currentModule($default = null)
	{
		return !empty(static::$module) ? static::$module : $default;
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
		if (empty(static::$module)) static::$module = \Request::active() ? ucfirst(\Request::active()->module) : '';

		// Strip the first part of the path off for module templates
		if (!empty(static::$module) && strpos(static::$path, static::$module) === 0) {
			static::$path = str_replace(static::$module.'/', '', static::$path);
		}

		$viewmodel_class = ucfirst(static::$module).'\\View_'.\Inflector::words_to_upper(ucfirst(str_replace(array('/', DS), '_', str_replace('-', '', static::$path) )));

		if (class_exists($viewmodel_class)) return $viewmodel_class;
		return false;
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
		if (empty(static::$module)) static::$module = \Request::active() ? ucfirst(\Request::active()->module) : '';

		// Strip the first part of the path off for module templates
		if (!empty(static::$module) && strpos(static::$path, static::$module) === 0) {
			static::$path = str_replace(static::$module.'/', '', static::$path);
		}

		$controller_class = ucfirst(static::$module).'\\Controller_'.\Inflector::words_to_upper(ucfirst(str_replace(array('/', DS), '_', static::$path)));
		
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
	public static function getStaticUrl($model, $lang = true)
	{
		if ($lang === false && isset(static::$static_urls_raw[$model])) return static::$static_urls_raw[$model];
		if (isset(static::$static_urls[$model])) return static::$static_urls[$model];
		
		$url = $model::select('url.url')
		->leftJoin('item.url', 'url')
		->setMaxResults(1)
		->getQuery()->getSingleScalarResult();
		
		if ($lang === false) return \CMF::$static_urls_raw[$model] = $url;
		return \CMF::$static_urls[$model] = static::link($url);
	}
	
	/**
	 * Retrieves the url to a static model
	 * @param string $model The fully qualified class name of the static model
	 * @return string Url of the model
	 */
	public static function getStaticLink($model, $titleField = "menu_title")
	{
		if (isset(static::$static_links[$model])) return static::$static_links[$model];
		if (!property_exists($model, $titleField))
			throw new \Exception("Error getting static link: The field '$titleField' does not exist in $model");
		
		$link = $model::select("item.$titleField, url.url")
		->leftJoin('item.url', 'url')
		->setMaxResults(1)
		->getQuery()->getArrayResult();
		
		if (count($link) === 0) return false;
		
		$link[0]['url'] = static::link($link[0]['url']);
		
		return \CMF::$static_links[$model] = $link[0];
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
			$output = (count($link) > 0) ? static::link($link[0]['url']) : null;
		} elseif (empty($output)) {
			$output = null;
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