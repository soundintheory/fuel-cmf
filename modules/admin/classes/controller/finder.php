<?php

namespace Admin;

class Controller_Finder extends Controller_Base {
	
	public function action_index()
	{
		// We only have one root, the settings for which are defined in the CMF config file
	    $opts = \Config::get('cmf.finder');
	    $opts['URL'] = \Uri::base(false).ltrim($opts['path'], '/');
	    $opts['path'] = rtrim(DOCROOT.ltrim($opts['path'], '/'), '/').'/';

	    if ($startPath = \Input::get('start', false)) {
	    	$opts['startPath'] = $opts['path'].trim($startPath, '/').'/';
	    }

	    // Make sure the various directories exist
	    if (!is_dir($opts['path'])) @mkdir($opts['path'], 0775, true);
	    if (isset($opts['startPath']) && !is_dir($opts['startPath'])) @mkdir($opts['startPath'], 0775, true);

	    // elFinder's default session forces secure cookies, which breaks it over plain http (local development)
	    $session = new \elFinderSession(array(
	        'base64encode' => false,
	        'keys' => array( 'default' => 'elFinderCaches', 'netvolume' => 'elFinderNetVolumes' ),
	        'cookieParams' => array(
	            'path' => '/',
	            'secure' => \Input::protocol() == 'https',
	            'httponly' => true,
	            'samesite' => 'Lax'
	        )
	    ));

	    $connector = new \elFinderConnector(new \elFinder(array(
	        //'debug' => true,
	        'session' => $session,
	        'roots' => array(
	        	$opts
	        )
	    )));

	    $connector->run();
	}

	public function action_browser()
	{
		$this->start = \Input::get('start', false);
		$this->template = 'admin/finder/browser.twig';
	}
	
}