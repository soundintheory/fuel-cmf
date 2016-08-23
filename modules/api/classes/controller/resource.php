<?php

namespace Api;

/**
 * The Base Controller.
 * 
 * @package  app
 * @extends  Controller
 */
class Controller_Resource extends \Controller_Rest
{
	protected $rest_format = 'json';
	protected $config;
	protected $class;
	protected $multiple;
	protected $resource;
	protected $action;

	public $secret = true;

	public function __construct(\Request $request)
	{
		parent::__construct($request);
		$this->config = \Config::load('api', true);
	}

	public function cache($param = 'something')
    {
        return false;
    }

    public function authorise()
    {
    	// If there's a valid session already, allow access
    	$user_type = \Input::param('user_type') ?: 'Admin\\Model_User';

		if (\CMF\Auth::logged_in(null, $user_type)) {
			return;
		}

    	$auth = explode(' ', \Input::headers('Authorization', ' '));
    	$sent_key = \Arr::get($auth, 1);

    	// Try and find a valid key
    	$key = \CMF\Model\User\Apikey::select('item')
    		->where('item.access_token = :key')
    		->andWhere('item.expires_at > :now')
    		->setParameter('key', $sent_key)
    		->setParameter('now', new \DateTime())
    		->getQuery()->getResult();

    	// Check the scope of the key, if one was found
    	if (count($key)) {
    		$key = $key[0];
    		if ($key->scope == 'api') {
    			return;
    		}
    	}

    	throw new \HttpException('Login Required', \HttpException::UNAUTHORIZED);
    }

	/**
	 * Doesn't do anything, just a welcome message
	 * 
	 * @return Response
	 */
	public function action_index()
	{
		return $this->response(array(
			'welcome' => 'This is the default CMF API'
		));
	}

	public function action_languageCanonicals(){
		$lang = \Config::get('language');
		$em = \D::manager();
		if(empty($lang))
			throw new \Exception("You do not have set any language for this site , this action is not available");

		$canonicalLanguage = "";
		if(isset($_SERVER["HTTP_CONTENT_LANGUAGE"])) {
			$canonicalLanguage = $_SERVER["HTTP_CONTENT_LANGUAGE"];
			if ($canonicalLanguage == $lang)
				throw new \Exception("Canonical Language id the same as Main site language");
		}else
			throw new \Exception("The Request has got not language set");

		$jsonObject = null;
		try {
			$jsonObject = json_decode(file_get_contents('php://input'));
		}
		catch(\Exception $e){}

		if(!empty($jsonObject) && !empty($jsonObject->data))
		{
			foreach($jsonObject->data as $table => $items)
			{
				foreach($items as $canonical)
				{
					$class = $canonical->class;
					$item = $class::find($canonical->id);
					if(!empty($item) && !empty($item->settings)) {
						$settings = $item->settings;
						if (!isset($settings['languages'])) {
							$settings['languages'] = array();
						}
						if (!empty($canonical->url)){
							if (!isset($settings['languages'][$canonicalLanguage]))
								$settings['languages'][$canonicalLanguage] = \Uri::base(false) . $item->url;

							$settings['languages'][$canonicalLanguage] = $canonical->url;
							$item->set('settings', $settings);
						}
						else{
							if (isset($settings['languages'][$canonicalLanguage]))
								unset($settings['languages'][$canonicalLanguage]);
						}
						$em->persist($item);
					}
				}
			}
		}
		$em->flush();
		exit(true);
	}

	/**
	 * Takes a resource name, works out whether it is a model and determines if it's singular or plural
	 */
	public function action_render($name = null, $id = null, $action = null)
	{
		if (!$name)
			throw new \HttpException('No resource was specified', \HttpException::BAD_REQUEST);

		// Get singular and plural versions of the resource name
		$name = \Inflector::friendly_title($name, '_');
		$singular = \Inflector::singularize($name);
		$plural = \Inflector::pluralize($singular);
		$model = \Admin::getClassForTable($name);
		if (empty($model)) $model = \Admin::getClassForTable($plural);
		$controller = null;

		// If there isn't a custom controller for this, it must be a model, no?
		if (!class_exists($controller = 'Api\\Controller_'.\Inflector::classify($singular)))
		{
			if ($model) {
				$controller = 'Api\\Controller_Model';
			} else {
				throw new \HttpException('No resource named "'.$plural.'" could be found', \HttpException::NOT_FOUND);
			}
		}

		// Create an instance of the controller
		$controller = new $controller($this->request);
		$controller_refl = new \ReflectionClass($controller);

		// Authorise if necessary
		if ($controller_refl->hasProperty('secret') && $controller->secret) {
			$this->authorise();
		}

		// Execute the action on the controller, including calls to 'before' and 'after'
		$controller_refl->hasMethod('before') and $controller_refl->getMethod('before')->invoke($controller);

		// Treat model controllers a little bit differently...
		if ($controller instanceof Controller_Model) {
			$response = $controller_refl->getMethod('router')->invoke($controller, $action, array(
				'name' => $name,
				'singular' => $singular,
				'plural' => $plural,
				'model' => $model,
				'id' => $id
			));
		} else {
			$response = $controller_refl->getMethod('router')->invoke($controller, $action, array($id));
		}

		$controller_refl->hasMethod('after') and $response = $controller_refl->getMethod('after')->invoke($controller, $response);

		//if There is a language add set header language
		if(!empty(\Config::get('language'))) {
			if ($response instanceof \Fuel\Core\Response)
				$response->set_header('Content-Language', \Config::get('language'), true);
			else
				$this->response->set_header('Content-Language', \Config::get('language'), true);
		}

		return $response;
	}
}