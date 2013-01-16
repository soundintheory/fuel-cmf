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
        parent::__construct(__("auth.failure.{$lang_key}", $params));
    }
}