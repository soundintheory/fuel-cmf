<?php

namespace Admin;

class Controller_Lang extends Controller_Base {
	
	public function action_set($code = null)
	{
		$referrer = \Input::referrer('/admin');
		
		// Don't bother if no code
		if (is_null($code)) return \Response::redirect($referrer);
		
		// Don't bother if it's not an active language
		$languages = \Admin::languages();
		if (!isset($languages[$code]))  return \Response::redirect($referrer);
		
		// Got this far, set the session!
		\Session::set('cmf.admin.language', $code);
		
		// Redirect back
		return \Response::redirect($referrer);
	}
	
	/**
	 * An editor view for the fuel lang entries
	 */
	public function action_terms()
	{
		//var_dump(\Lang::$lines); exit();

		$this->template = 'admin/lang/terms.twig';

		// Determine what's in the left and right cols
		$this->lang_lft = \Arr::get(\Lang::$fallback, '0', 'en');
		$this->lang_rgt = \CMF::lang();

		// Find out all out the different groups
		$groups = \DB::query("SELECT DISTINCT identifier FROM lang")->execute()->as_array();
		$groups = \Arr::pluck($groups, 'identifier');

		foreach ($groups as $group) {

			// Load in both the languages
			$result_lft = \Lang::load("$group.db", $group, $this->lang_lft, false, true);
			$result_rgt = \Lang::load("$group.db", $group, $this->lang_rgt, false, true);

		}

		$this->lines = \Arr::get(\Lang::$lines, \Lang::$fallback[0], array());

	}
	
}