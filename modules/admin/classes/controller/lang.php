<?php

namespace Admin;

class Controller_Lang extends Controller_Base {
	
	public function action_set($code = null)
	{
		$referrer = \Input::referrer(\CMF::adminUrl());
		
		// Don't bother if no code
		if (is_null($code)) return \Response::redirect($referrer);

		// Case
		if (strpos($code, '_') !== false) {
			$parts = explode('_', $code);
			$code = strtolower($parts[0]).'_'.strtoupper($parts[1]);
		}
		
		// Don't bother if it's not an active language
		$languages = \Admin::languages();
		if (!isset($languages[$code])) return \Response::redirect($referrer);
		
		// Got this far, set the session!
		\Session::set('cmf.admin.language', $code);
		
		// Redirect back
		return \Response::redirect($referrer);
	}
	
	/**
	 * An editor view for the fuel lang entries
	 */
	public function get_terms()
	{
		$this->template = 'admin/lang/terms.twig';

		// Determine what's in the left and right cols
		$this->lang_lft = \Arr::get(\Lang::$fallback, '0', 'en');
		$this->lang_rgt = \CMF::lang();

		// Get the common group
		$result_lft = \Lang::load("common.db", 'common', $this->lang_lft, true, true);
		$result_rgt = \Lang::load("common.db", 'common', $this->lang_rgt, true, true);

		$this->result_lft = $result_lft;
		$this->result_rgt = $result_rgt;
		$this->lines = \Arr::get(\Lang::$lines, \Lang::$fallback[0], array());
	}

	/**
	 * An editor view for the fuel lang entries
	 */
	public function post_terms()
	{
		$terms = \Input::post('terms', array());

		try {
			foreach ($terms as $lang => $phrases) {
				\Lang::save('common.db', $phrases, $lang);
			}
		} catch (\Exception $e) {
			// Nothing
		}

		\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-success' ), 'msg' => \Lang::get('admin.messages.translations_save_success') ));

		$referrer = \Input::referrer(\CMF::adminPath());
		return \Response::redirect($referrer);
	}
	
}