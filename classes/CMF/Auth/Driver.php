<?php

namespace CMF\Auth;

use Hautelook\Phpass\PasswordHash,
    CMF\Auth,
    CMF\Model\User,
    Admin\Model_User,
    CMF\Model\Permission,
    CMF\Model\Role;

/**
 * Driver
 *
 * @package    CMF
 * @subpackage Auth
 */
class Driver
{
    /**
     * Configuration
     *
     * @var array
     */
    protected $config;

    /**
     * Current logged in user
     *
     * @var \CMF\Model\User
     */
    protected $user;

    /**
     * Creates a new driver instance, loading the session and storing config.
     *
     * @param  array $config configuration
     *
     * @return void
     */
    public function __construct(array $config)
    {
        // Store config
        $this->config = $config;
    }

    /**
     * Checks if a session is active.
     *
     * @param  string $role The role name (optional)
     *
     * @return bool
     */
    public function logged_in($role = null, $type = 'Admin\\Model_User')
    {
        $auth_token = \Session::get($type.'_logged');

        if (!empty($auth_token) &&
            (!$this->user || $this->user->get('id') != $auth_token))
        {
            $this->user = null;
            
            try {
                $user = $type::select('item')->where("item.id = $auth_token")->getQuery()->getResult();
            } catch (\Doctrine\DBAL\DBALException $e) {
                throw new \Exception("There was a problem loading the CMF user. Try running 'php oil r cmf:sync', as there may be a necessary database upgrade.", 1);
            }
            
            if (count($user) > 0 && !$user[0]->is_access_locked()) {
                $this->set_user($user[0]);
            }
        }
        
        return $this->has_access($role ? $role : null, $this->user);
    }

    /**
     * Verify role access
     *
     * @param mixed           $role The role name to check
     * @param \CMF\Model\User $user The user to check against, if no user is given (null)
     *                                 it will check against the currently logged in user
     *
     * @return bool
     */
    public function has_access($role, User $user = null)
    {
        $status = !!$user;

        if (!empty($role) && $status) {
            
            if ($user->super_user) return true;
            $role = (is_array($role) ? $role : array($role));
            $user_roles = $user->get('roles')->toArray();
            
            $diff = array_udiff($role, $user_roles, function ($r, $ur) {
                // check for a role object
                $r = (is_object($r) ? $r->get('name') : $r);
                // compare each given role against the user's roles
                return $r != $ur->get('name');
            });

            // empty = true
            $status = empty($diff);
        }

        return $status;
    }

    /**
     * Check if the user has permission to perform a given action on a resource.
     *
     * @param mixed $action   The action for the permission.
     * @param mixed $resource The resource for the permission.
     *
     * @return bool
     */
    public function can_user($action, $resource)
    {
        $user   = $this->current_user();
        $status = !!$user;

        if ($status) {
            if ($user->super_user) return true;
            
            $status   = false;
            $action   = (is_array($action) ? $action : array($action));
            $resource_id = ($resource instanceof \CMF\Doctrine\Model) ? $resource->id : null;
            $resource = (is_object($resource) ? get_class($resource) : $resource);
            $resource = (is_array($resource) ? $resource : array($resource));
            $roles = $user->roles;
            $passed = 0;
            
            if ($resource_id !== null) {
                
                // The resource is an entity, so check permissions against the id as well
                foreach ($roles as $role) {
                    
                    $role_passed = 0;
                    $resource_permissions = 0;
                    foreach ($role->permissions as $permission) {
                        if ($permission->action == 'all' && in_array($permission->resource, $resource) && $permission->item_id === $resource_id) {
                            $role_passed = count($action);
                            break;
                        }
                        if ($permission->item_id === $resource_id) {
                            if (in_array($permission->action, $action) && in_array($permission->resource, $resource)) {
                                $role_passed++;
                            }
                            $resource_permissions++;
                        }
                        
                    }
                    if ($resource_permissions === 0) $role_passed = count($action);
                    $passed += $role_passed;
                    
                }
                
            } else {
                
                // The resource is just a class name
                foreach ($roles as $role) {
                    
                    $role_passed = 0;
                    $resource_permissions = 0;
                    foreach ($role->permissions as $permission) {
                        if ($permission->action == 'all' && in_array($permission->resource, $resource)) {
                            $role_passed = count($action);
                            break;
                        }
                        if (in_array($permission->resource, $resource)) {
                            if (in_array($permission->action, $action)) {
                                $role_passed++;
                            }
                            $resource_permissions++;
                        }
                    }
                    // if ($resource_permissions === 0) $role_passed = count($action);
                    $passed += $role_passed;
                    
                }
                
            }
            
            $status = $passed >= (count($action) * count($resource));
            $status && $this->_run_event('after_authorization');
        }

        return $status;
    }

    /**
     * Logs a user in.
     *
     * @param string $username_or_email
     * @param string $password
     * @param bool   $remember
     *
     * @return bool
     *
     * @throws \CMF\Auth\Failure If lockable enabled & attempts exceeded
     */
    public function authenticate_user($username_or_email, $password, $type, $remember)
    {
        if (($user = $type::authenticate($username_or_email)) &&
             Auth::has_password($user, $password))
        {
            
            if ($remember === true) {
                // Set token data
                $remember_token = Auth::forge()->generate_token();

                $user->set('remember_token', $remember_token);
                \D::manager()->persist($user); 
                \D::manager()->flush();
                // Set the remember-me cookie
                \Cookie::set('remember_token',
                             $user->remember_token,
                             $this->config['lifetime'],
                             null, null, null, true);
            }

            return $this->complete_login($user, $type);
        }
        
        /*
        if (!is_null($user) && $this->config['lockable']['in_use'] === true) {
            $user->update_attempts(1);
        }
        */

        // Login failed
        return false;
    }

    /**
     * Logs a user in using Http based authentication
     *
     * @see \CMF\Auth\Driver::_http_basic()
     * @see \CMF\Auth\Driver::_http_digest()
     *
     * @return array
     */
    public function http_authenticate_user()
    {
        $method = "_http_{$this->config['http_authenticatable']['method']}";
        return $this->{$method}(new \Response(\View::forge('auth/401.twig'), 401));
    }

    /**
     * Sets the currently logged in user.
     *
     * @param \CMF\Model\User The user to set
     */
    public function set_user(User $user)
    {
        $this->user = $user;
        $this->_run_event('after_set_user');
    }

    /**
     * Gets the currently logged in user from the session.
     *
     * @return mixed Returns FALSE if no user is currently logged in, otherwise
     *               returns a \CMF\Model\User object.
     */
    public function current_user()
    {
        return $this->user;
    }

    /**
     * Forces a user to be logged in, without specifying a password.
     *
     * @param mixed $username_or_email_or_id
     *
     * @return bool
     */
    public function force_login($username_or_email_or_id, $type = 'Admin\\Model_User')
    {
        $user = $type::authenticate($username_or_email_or_id, true);
        return $user && $this->complete_login($user, $type);
    }

    /**
     * Logs a user in, based on stored credentials, typically cookies.
     *
     * @param string $role The role name (optional)
     *
     * @return bool
     */
    public function auto_login($role = null, $type = 'Admin\\Model_User')
    {
        if (($token = \Cookie::get('remember_token'))) {
            $user = $type::find_by_remember_token($token);

            if ($user) {
                if ($this->has_access($role, $user)) {
                    // Complete the login with the found data
                    $this->complete_login($user, $type);

                    // Automatic login was successful
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Log a user out.
     *
     * @param bool $destroy Whether to completely destroy the session
     *
     * @return bool
     */
    public function logout($destroy, $type = 'Admin\\Model_User')
    {
        $this->_run_event('before_logout');

        $this->user = null;

        // Delete the session identifier for the user
        \Session::delete($type.'_logged');

        // Delete http server variables
        if ($this->config['http_authenticatable']['in_use']) {
            unset($_SERVER['PHP_AUTH_USER'],
                  $_SERVER['PHP_AUTH_PW'],
                  $_SERVER['PHP_AUTH_DIGEST']);
        }

        if (\Cookie::get('remember_token')) {
            // Delete the remember-me cookie to prevent re-login
            \Cookie::delete('remember_token');
        }

        if ($destroy === true) {
            // Destroy the session completely
            \Session::destroy();
        } else {
            // Regenerate session_id
            \Session::instance()->rotate();
        }

        // Double check
        return !$this->logged_in(null, $type);
    }

    /**
     * Completes a login by assigning the user to the session key.
     *
     * @param \CMF\Model\User $user
     *
     * @return bool
     */
    protected function complete_login(User $user, $type = 'Admin\\Model_User')
    {
        // Create and set new authentication token
        $token = $user->get('id');

        try {
            
            \Session::set($type.'_logged', $token);
            \Session::instance()->rotate();
            
            $this->set_user($user);
            $this->_run_event('after_authentication');
            
            return true;
        } catch(\Exception $ex) {
            logger(\Fuel::L_ERROR,
                   'CMF authentication failed because an exception was thrown: '.
                   $ex->getMessage()
            );
            return false;
        }
    }

    /**
     * Handler for HTTP Basic Authentication
     *
     * @return array A key/value array of the username => value and password => value
     */
    private function _http_basic(\Response $response)
    {
        $users = $this->config['http_authenticatable']['users'];
        $username = \Input::server('PHP_AUTH_USER');
        $password = \Input::server('PHP_AUTH_PW');

        if (!isset($users[$username]) || $users[$username] !== $password) {
            $realm = $this->config['http_authenticatable']['realm'];
            $response->set_header('WWW-Authenticate', "Basic realm=\"$realm\"");
            $response->send(true);
            exit;
        }

        return array('username' => $username, 'password' => $password);
    }

    /**
     * Handler for HTTP Digest Authentication
     *
     * @return array A key/value array of the username => value and password => value
     */
    private function _http_digest(\Response $response)
    {
        $realm = $this->config['http_authenticatable']['realm'];

        $data = array(
            'nonce'    => null,
            'nc'       => null,
            'cnonce'   => null,
            'qop'      => null,
            'username' => null,
            'uri'      => null,
            'response' => null
        );

        foreach (explode(',', \Input::server('PHP_AUTH_DIGEST')) as $string) {
            $parts = explode('=', trim($string), 2) + array('', '');
            $data[$parts[0]] = trim($parts[1], '"');
        }

        $users    = $this->config['http_authenticatable']['users'];
        $password = !empty($users[$data['username']]) ? $users[$data['username']] : null;

        $A1   = md5("{$data['username']}:{$realm}:{$password}");
        $A2   = "{$data['nonce']}:{$data['nc']}:{$data['cnonce']}:{$data['qop']}";
        $A3   = md5(\Input::server('REQUEST_METHOD').':'.$data['uri']);
        $hash = md5("{$A1}:{$A2}:{$A3}");

        if (!$data['username'] || $hash !== $data['response']) {
            $nonce        = uniqid();
            $opaque       = md5($realm);
            $header_value = "Digest realm=\"{$realm}\",qop=\"auth\", nonce=\"{$nonce}\",opaque=\"{$opaque}\"";

            $response->set_header('WWW-Authenticate', $header_value);
            $response->send(true);
            exit;
        }

        return array('username' => $data['username'], 'password' => $password);
    }

    /**
     * Runs a CMF callback event if its been registered
     *
     * @param string $name The event to run
     */
    private function _run_event($name)
    {
        $event = "cmf_{$name}";

        if (\Event::has_events($event)) {
            \Event::trigger($event, $this->user);
        }
    }
}