<?php

namespace CMF\Auth;

/**
 * Failure
 *
 * @package    CMF
 * @subpackage Auth
 */
class Failure extends \FuelException
{
    public function __construct($lang_key, array $params = array())
    {
        parent::__construct(\Lang::get("admin.errors.account.{$lang_key}", $params));
    }
}