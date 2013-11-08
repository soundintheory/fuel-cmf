<?php

namespace Admin;

class Controller_Lang extends Controller_Base {
	
	public function action_set($code = null)
	{
		$referrer = \Input::referrer('/admin');
		
		// Don't bother if no code
		if (is_null($code)) return \Response::redirect($referrer);
		
		// Don't bother if it's not an active language
		$languages = \CMF::languages();
		if (!isset($languages[$code]))  return \Response::redirect($referrer);
		
		// Got this far, set the session!
		\Session::set('cmf.admin.language', $code);
		
		// Redirect back
		return \Response::redirect($referrer);
	}
	
	/**
	 * An editor view for the fuel lang entries
	 */
	public function action_snippets()
	{
		$this->template = 'admin/lang/snippets.twig';
		
	}
	
}