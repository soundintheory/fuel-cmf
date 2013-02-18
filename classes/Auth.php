<?php

namespace CMF;

use PasswordHash,
    Admin\Model_User,
    CMF\Model\User,
    CMF\Model\Permission,
    CMF\Model\Role;

/**
 * A static class through which all authentication functionality can be accessed
 *
 * @package CMF
 */
class Auth
{
    protected static $_default_actions = array('view', 'edit', 'create', 'delete');
    protected static $_all_actions = null;
    protected static $_extra_resources = null;
    
    /**
     * The driver used. Override the default driver and set it via the 'cmf.auth.driver'
     * setting to add custom functionality.
     *
     * @var \CMF\Auth\Driver
     */
    public $driver;

    /**
     * This prevents instantiation.
     */
    final private function __construct() {}

    /**
     * Return a static instance.
     *
     * @return Auth
     */
    public static function forge($config = array())
    {
        static $instance = null;

        // Load the Auth instance
        if ($instance === null) {
            $config = array_merge(\Config::get('cmf.auth', array()), $config);
            $driver = \Arr::get($config, 'driver', 'CMF\\Auth\\Driver');
            $instance = new static;
            $instance->driver = new $driver($config);
        }

        return $instance;
    }

    /**
     * Checks for validated login. This checks for current session as well as
     * a remember me cookie.
     * Whereas {@link Auth::logged_in()} only checks the current session login.
     *
     * <code>
     * if (Auth::check()) {
     *     echo "I'm logged in :D";
     * } else {
     *     echo "Failed, I'm NOT logged in :(";
     * }
     * </code>
     *
     * Checking a role has permission:
     * <code>
     * if (Auth::check('admin', 'delete', 'User')) {
     *     echo "User is an admin and has permission to delete other users";
     * } else {
     *     throw new Auth\AccessDenied();
     * }
     * </code>
     *
     * If an action is given and no resource is given, it will assume that the
     * resource has the same name as the role.
     * <code>
     * if (Auth::check('admin', 'delete')) {
     *     echo "User is an admin and has permission to delete other "admin"";
     * } else {
     *     throw new Auth\AccessDenied();
     * }
     * </code>
     *
     * @param mixed $role     The role name (optional)
     * @param mixed $action   The action permission for the role (optional)
     * @param mixed $resource The resource permission for the role (optional)
     *
     * @return bool Returns true on success or false on failure
     */
    public static function check($role = null, $action = null, $resource = null, $type = 'Admin\\Model_User')
    {
        $status = false;

        if (static::logged_in($role, $type) || static::auto_login($role, $type)) {
            $status = true;
        }

        if (!empty($action)) {
            $resource = (empty($resource) ? $role : $resource);
            return $status && static::can($action, $resource);
        }

        return $status;
    }

    /**
     * Check if there is an active session. Optionally allows checking for a
     * specific role.
     *
     * <code>
     * if (Auth::logged_in()) {
     *     echo "I'm logged in";
     * }
     *
     * if (Auth::logged_in('admin')) {
     *     echo "I'm logged in as an admin";
     * }
     * </code>
     *
     * @param mixed $role The role name (optional)
     *
     * @return bool Returns true on success or false on failure
     */
    public static function logged_in($role = null, $type = 'Admin\\Model_User')
    {
        return static::driver()->logged_in($role, $type);
    }

    /**
     * Verify user-role access
     *
     * <code>
     * if (Auth::has_access('admin')) {
     *     echo "Hey, admin";
     * } else {
     *     echo "Halt! You are not an admin!";
     * }
     *
     * // OR
     *
     * $user = Model_Model_User::find(2);
     * if (Auth::has_access(array('editor', 'moderator'), $user)) {
     *     echo "Hey, editor - moderator";
     * } else {
     *     echo "Fail!";
     * }
     *
     * // Checking a user has a role permission
     * if (Auth::has_access('admin', 'delete', $user)) {
     *     echo "User is an admin and has deleting rights";
     * } else {
     *     echo "Failed, you're not an admin with deleting rights";
     * }
     * </code>
     *
     * @param mixed              $role The role name(s) to check
     * @param \CMF\Model\User $user The user to check against, if no user is given (null)
     *                                 it will check against the currently logged in user
     *
     * @return bool Returns true on success or false on failure
     */
    public static function has_access($role, User $user = null)
    {
        return static::driver()->has_access($role, $user);
    }

    /**
     * Explicitly set the current user.
     *
     * <code>
     * if (($user = Model_User::find(1))) {
     *      Auth::set_user($user);
     * }
     * </code>
     *
     * @param \CMF\Model\User The user to set
     */
    public static function set_user(User $user)
    {
        static::driver()->set_user($user);
    }

    /**
     * Returns the currently logged in user, or null.
     *
     * <code>
     * if (Auth::check()) {
     *     $current_user = Auth::current_user();
     *     $current_user->username;
     * }
     * </code>
     *
     * @return \CMF\Model\User|null Returns a user object on success or null on failure
     */
    public static function current_user()
    {
        return static::driver()->current_user();
    }

    /**
     * Attempt to log in a user by using a username or email and plain-text password.
     *
     * <code>
     * if (Input::method() === 'POST') {
     *     if (Auth::authenticate(Input::post('username_or_email'), Input::post('password'))) {
     *         Session::set_flash('success', 'Logged in successfully');
     *     } else {
     *         Session::set_flash('error', 'Username or password invalid');
     *     }
     *
     *     Response::redirect();
     * }
     * </code>
     *
     * @param string $username_or_email The username or email to log in
     * @param string $password          The password to check against
     * @param bool   $remember          Whether to set remember-me cookie
     * 
     * @see \CMF\Auth\Driver::http_authenticate_user()
     *
     * @return bool Returns true on success or false on failure
     *
     * @throws \CMF\Auth\Failure If lockable enabled & attempts exceeded
     */
    public static function authenticate($username_or_email, $password, $type = 'Admin\\Model_User', $remember = false)
    {
        if (empty($username_or_email) || empty($password)) {
            return false;
        }

        return static::driver()->authenticate_user($username_or_email, $password, $type, $remember);
    }

    /**
     * Attempt to log in a user by using an http based authentication method.
     *
     * <code>
     * if (($user = Auth::http_authenticate())) {
     *      Session::set_flash('success', "Logged in as {$user['username']}");
     * }
     * </code>
     *
     * @see \CMF\Auth\Driver::http_authenticate_user()
     *
     * @return array A key/value array of the username => value and password => value
     */
    public static function http_authenticate()
    {
        return static::driver()->http_authenticate_user();
    }

    /**
     * Attempt to automatically log a user in.
     *
     * <code>
     * if (Auth::auto_login()) {
     *     $remembered_user = Auth::current_user();
     *     echo $remembered_user->username.' was retrieved from a remember-me cookie';
     * }
     * </code>
     *
     * @param mixed $role The role name (optional)
     *
     * @return bool
     */
    public static function auto_login($role = null, $type = 'Admin\\Model_User')
    {
        return static::driver()->auto_login($role, $type);
    }

    /**
     * Force a login for a specific username.
     *
     * <code>
     * if (Auth::force_login('username')) {
     *     $forced_user = Auth::current_user();
     *     echo $forced_user->username.' was forced to login only with a username';
     * }
     * </code>
     *
     * @param mixed $username_or_email_or_id
     *
     * @return bool
     */
    public static function force_login($username_or_email_or_id, $type = 'Admin\\Model_User')
    {
        return static::driver()->force_login($username_or_email_or_id, $type);
    }

    /**
     * Log out a user by removing the related session variables.
     *
     * <code>
     * if (Auth::logout()) {
     *     echo "I'm logged out";
     * }
     * </code>
     *
     * @param bool $destroy completely destroy the session
     *
     * @return bool
     */
    public static function logout($destroy = false, $type = 'Admin\\Model_User')
    {
        return static::driver()->logout($destroy, $type);
    }

    /**
     * Check if the user has permission to perform a given action on a resource.
     *
     * <code>
     * if (Auth::can('destroy', $project)) {
     *      echo 'User can destroy';
     * }
     * </code>
     *
     * You can also pass the class instead of an instance (if you don't have one handy).
     * <code>
     * if (Auth::can('destroy', 'Project')) {
     *      echo 'User can destroy';
     * }
     * </code>
     *
     * Multiple actions/resources can be passed through an array. It will return
     * true if one of the supplied actions/resources are found.
     * <code>
     * if (Auth::can('destroy', array('Project', 'Task'))) {
     *      echo 'User can destroy a project/task';
     * }
     *
     * if (Auth::can(array('destroy', 'create'), array('Project', 'Task'))) {
     *      echo 'User can create/destroy a project/task';
     * }
     * </code>
     *
     * You can pass 'all' to match any resource and 'manage' to match any action.
     * <code>
     * if (Auth::can('manage', 'all')) {
     *      echo 'User can do something on one of the resources';
     * }
     *
     * if (Auth::can('manage', 'Project')) {
     *      echo 'User can do something on a Project';
     * }
     * </code>
     *
     * @param mixed $action   The action for the permission.
     * @param mixed $resource The resource for the permission.
     *
     * @return bool
     */
    public static function can($action, $resource)
    {
        return static::driver()->can_user($action, $resource);
    }

    /**
     * Convenience method which works the same as {@link Auth::can()}
     * but returns the opposite value.
     *
     * <code>
     * if (Auth::cannot('create', 'Project') {
     *      die('Unauthorized user');
     * }
     * </code>
     */
    public static function cannot($action, $resource)
    {
        return !static::can($action, $resource);
    }

    /**
     * An alias for {@link Auth::can()} except throws an exception on failure and allows
     * extra options.
     *
     * A 'message' option can be passed to specify a different message. By default it will look
     * for a lang line 'auth.unauthorized.[resource].[action]' first.
     * <code>
     * Auth::authorize('read', $article, array('message' => "Not authorized to read {$article->name}"));
     * </code>
     *
     * @param mixed $action   The action for the permission.
     * @param mixed $resource The resource for the permission.
     * @param array $options
     *
     * @see {@link \CMF\Auth\AccessDenied}
     *
     * @throws \CMF\Auth\AccessDenied If the current user cannot perform the given action
     */
    public static function authorize($action, $resource, array $options = array())
    {
        $message = null;
        if (isset($options['message'])) {
            $message = $options['message'];
        }

        if (static::cannot($action, $resource)) {
            $message || $message = __("auth.unauthorized.{$resource}.{$action}");
            throw new Auth\AccessDenied($message, $action, $resource);
        }
    }

    /**
     * This is called every time the user is set.
     * The user is set:
     *
     *      - when the user is initially authenticated
     *      - when the user is set via Auth::set_user()
     *
     * <code>
     * Auth::after_set_user(function($user) {
     *      if (!$user->is_active) {
     *          Auth::logout();
     *      }
     * });
     *
     * // OR
     *
     * Auth::after_set_user('Myclass::method');
     * </code>
     *
     * @param mixed $callback The callable function to execute
     *
     * @uses \Fuel\Core\Event::register()
     */
    public static function after_set_user($callback)
    {
        \Event::register('after_set_user', $callback);
    }

    /**
     * Executed every time the user is authenticated.
     *
     * <code>
     * Auth::after_authentication(function($user) {
     *      $user->last_login = time();
     * });
     *
     * // OR
     *
     * Auth::after_authentication('Myclass::method');
     * </code>
     *
     * @param mixed $callback The callable function to execute
     *
     * @uses \Fuel\Core\Event::register()
     */
    public static function after_authentication($callback)
    {
        \Event::register('after_authentication', $callback);
    }

    /**
     * Executed before each user is logged out.
     *
     * <code>
     * Auth::before_logout(function($user) {
     *      logger(\Fuel::L_INFO, 'User '.$user->id.' logging out', 'Auth::before_logout');
     * });
     *
     * // OR
     *
     * Auth::before_logout('Myclass::method');
     * </code>
     *
     * @param mixed $callback The callable function to execute
     *
     * @uses \Fuel\Core\Event::register()
     */
    public static function before_logout($callback)
    {
        \Event::register('before_logout', $callback);
    }

    /**
     * Executed every time a user is authorized.
     *
     * <code>
     * Auth::after_authorization(function($user) {
     *      logger(\Fuel::L_INFO, 'User '.$user->id.' was successfully authorized to access '.\Input::server('REQUEST_URI'));
     * });
     *
     * // OR
     *
     * Auth::after_authorization('Myclass::method');
     * </code>
     *
     * @param mixed $callback The callable function to execute
     *
     * @uses \Fuel\Core\Event::register()
     */
    public static function after_authorization($callback)
    {
        \Event::register('after_authorization', $callback);
    }

    /**
     * Encrypts a user password using the Blowfish algo
     *
     * @param string $password The plaintext password
     *
     * @return string The hashed password string
     */
    public static function encrypt_password($password)
    {
        $hasher = new PasswordHash(8, false);
        return $hasher->HashPassword($password);
    }

    /**
     * Checks that a submitted password matches the users password
     *
     * @param \CMF\Auth\User $user
     * @param string         $submitted_password
     *
     * @return bool
     */
    public static function has_password(User $user, $submitted_password)
    {
        $user_password = $user->get('encrypted_password');
        if (empty($user_password) || empty($submitted_password)) {
            return false;
        }

        $hasher = new PasswordHash(8, false);
        return $hasher->CheckPassword($submitted_password, $user_password);
    }

    /**
     * Generate a unique friendly string to be used as a token.
     *
     * @return string
     */
    public static function generate_token()
    {
        $token = join(':', array(\Str::random('alnum', 15), time()));
        return str_replace(
	        array('+', '/', '=', 'l', 'I', 'O', '0'), 
	        array('p', 'q', 'r', 's', 'x', 'y', 'z'), 
	        base64_encode($token)
		);
    }
    
    /**
     * Gets just the core actions that are hard-coded into the system (view,edit,create,delete)
     * @return array
     */
    public static function default_actions()
    {
        return static::$_default_actions;
    }
    
    /**
     * Gets all the possible actions, including ones defined in custom resources
     * @return array
     */
    public static function all_actions()
    {
        static::extra_resources();
        return static::$_all_actions;
    }
    
    /**
     * Get the extra resources defined in the config
     * @return array
     */
    public static function extra_resources()
    {
        if (!is_null(static::$_extra_resources)) return static::$_extra_resources;
        
        $all_actions = static::$_default_actions;
        $extra_resources = \Config::get('cmf.auth.resources', array());
        $output = array();
        
        foreach ($extra_resources as $resource_id => $extra_resource) {
            
            if (is_string($extra_resource)) {
                $new_resource = array(
                    'title' => $extra_resource,
                    'icon' => 'lock',
                    'actions' => array('view')
                );
            } else {
                $new_resource = $extra_resource;
                if (!isset($new_resource['actions'])) $new_resource['actions'] = array('view');
                if (!isset($new_resource['icon'])) $new_resource['icon'] = 'lock';
                if (!isset($new_resource['title'])) $new_resource['title'] = 'untitled';
            }
            
            // Add any extra actions to all_actions
            foreach ($new_resource['actions'] as $resource_action) {
                if (!in_array($resource_action, $all_actions)) $all_actions[] = $resource_action;
            }
            
            // Append the resource to the output
            $output[$resource_id] = $new_resource;
            
        }
        
        static::$_all_actions = $all_actions;
        return static::$_extra_resources = $output;
        
    }
    
    /**
     * Attempts to retrieve a permission and creates it if not found
     * @param string $action
     * @param string $resource
     * @return \CMF\Model\Permission
     */
    public static function get_permission($action, $resource, $item_id=null)
    {
        $qb = Permission::select("item")
        ->where("item.action = '$action'")
        ->andWhere("item.resource = '$resource'");
        
        if ($item_id !== null) {
            $qb->andWhere("item.item_id = ".$item_id);
        }
        
        $permission = $qb->setMaxResults(1)->getQuery()->getResult();
        
        if (count($permission) === 0) {
            $em = \DoctrineFuel::manager();
            $permission = new Permission();
            $permission->set('action', $action);
            $permission->set('resource', $resource);
            if ($item_id !== null) { $permission->set('item_id', $item_id); }
            $em->persist($permission);
            return $permission;
        }
        
        return $permission[0];
    }
    
    /**
     * Ensures that there is at least an 'all' permission set for every resource
     * @return void
     */
    public static function create_permissions()
    {
        $actions = static::all_actions();
        $actions[] = 'all';
        $activeClasses = \CMF\Admin::activeClasses();
        $activeClasses['user_defined'] = array_keys(\Config::get('cmf.auth.resources', array()));
        
        $roles = Role::select('item')->getQuery()->getResult();
        $em = \DoctrineFuel::manager();
        
        foreach ($activeClasses as $parent_class => $classes) {
            
            foreach ($classes as $class_name) {
                
                $count = intval(Permission::select("count(item)")
                ->where("item.resource = '$class_name'")
                ->andWhere("item.action = 'all'")
                ->getQuery()->getSingleScalarResult());
                
                if ($count == 0) {
                    
                    $permission = new Permission();
                    $permission->set('action', 'all');
                    $permission->set('resource', $class_name);
                    $em->persist($permission);
                    
                    foreach ($roles as $role) {
                        $role->add('permissions', $permission);
                        $em->persist($role);
                    }
                    
                }
                
            }
            
        }
        
        $em->flush();
        
    }

    /**
     * Fetches the auth driver instance
     *
     * @return \CMF\Auth\Driver
     */
    protected static function driver()
    {
        return static::forge()->driver;
    }
}