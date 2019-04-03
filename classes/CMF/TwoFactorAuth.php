<?php

namespace CMF;

use Hautelook\Phpass\PasswordHash,
    Admin\Model_User,
    CMF\Model\User,
    CMF\Model\Permission,
    CMF\Model\Role,
    CMF\Model\Settings,
    Google\Authenticator\GoogleAuthenticator;
/**
 * A static class through which all two factor authentication functionality can be accessed
 *
 * @package CMF
 */
class TwoFactorAuth
{
    protected static $_default_actions = array('view', 'edit', 'create', 'delete');
    protected static $_all_actions = null;
    protected static $_extra_resources = null;
    
    
    public $driver;

    /**
     * This prevents instantiation.
     */
    final private function __construct() {}

    /**
     * Return a static instance.
     *
     * @return TwoFactorAuth
     */
    public static function forge($config = array())
    {
        static $instance = null;
        // Load the Auth instance
        if ($instance === null) {
            $settings = \CMF\CMF::settings();
            $method = $settings->twofa_method;
            $config = array_merge(\Config::get('cmf.auth.two_factor_methods', array()), $config);
            $driver = \Arr::get($config, $method, 'CMF\\Auth\\TwoFactor\\GoogleAuth');
            $instance = new static;
            $instance->driver = new $driver($config);
        }

        return $instance;
    }

    public static function createCode(User $user){
        return static::driver()->createCode($user);
    }
    //called when the code input page is requested
    //can return a view to controller
    public static function twoFactorCodeInput(){
        return static::driver()->twoFactorCodeInput();
    }
    //called when the user submits a code
    public static function checkCode(){
        if(static::driver()->checkCode()){
            \Session::set('twoFactorAuth','1'); //code passed so set to 1
            static::driver()->codePassed();
        }else{
            static::driver()->codeFailed();
        }
    }
    //called when the setup page is requested
    //Can return a view to controller
    public static function twoFactorSetup(){
        return static::driver()->twoFactorSetup();
    }
    //enables two factor auth for user
    public static function enabledUserTwoFactorAuth(){
        static::driver()->enabledUserTwoFactorAuth();
    }
    //check to see is user has enabled two factor
    public static function hasUserEnabledTwoFactor($user){
        return static::driver()->hasUserEnabledTwoFactor($user);
    }

    //checks settings to see if user has enabled two factor auth on account
    public static function isGlobalTwoFactorEnabled(){
        $settings = \CMF\CMF::settings();
        if(!empty($settings) && $settings->twofa_enabled == 1){
            return true;
        }else{
            return false;
        }
    }
    //checks session to see if auth has been completed, returns false is nothing has been set
    public static function isTwoFactorAuthComplete(){
        $complete = \Session::get('twoFactorAuth','0');
        if($complete == '1'){
            return true;
        }else{
            return false;
        }
    }
    
    //default routing for twofactor auth, either takes user to setup page or code entry page
    public static function defaultRoutingForUser(){
        $currentUser = \CMF\Auth::current_user();
        if($currentUser){
            if(\CMF\TwoFactorAuth::hasUserEnabledTwoFactor($currentUser)){ //the user has enabled two factor auth
                \CMF::adminRedirect("/admin/twofactorinput", 'location');
            }else{
                \CMF::adminRedirect("/admin/twofactorsetup", 'location');
            }
        }else{
            \CMF::adminRedirect('/login','location');
        }
    }

    /**
     * Fetches the auth driver instance
     *
     */
    protected static function driver()
    {
        return static::forge()->driver;
    }
}