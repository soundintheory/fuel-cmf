<?php

namespace CMF\Auth;

use CMF\Auth;

/**
 * AccessDenied
 *
 * Thrown when a user isn't allowed to access a given controller action.
 * This usually happens within a call to Auth::authorize() but can be
 * thrown manually.
 *
 * <code>
 * throw new CMF\Auth\AccessDenied('Not authorized!', 'read', 'Article');
 * </code>
 * 
 * @package    CMF
 * @subpackage Auth
 */
class AccessDenied extends \FuelException
{
    public $action;
    public $resource;

    public function __construct($message = null, $action = null, $resource = null)
    {
        $this->action = $action;
        $this->resource = $resource;

        $message || $message = __('admin.errors.unauthorized.default');

        if (empty($message)) {
            $message = 'You are not authorized to access this page.';
        }

        parent::__construct($message);
    }
}