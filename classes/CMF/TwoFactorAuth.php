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
 * A static class through which all authentication functionality can be accessed
 *
 * @package CMF
 */
class TwoFactorAuth
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
            $driver = \Arr::get($config, 'driver', 'CMF\\Auth\\TwoFactor\\GoogleAuth');
            $instance = new static;
            $instance->driver = new $driver($config);
        }

        return $instance;
    }

    public static function createCode(User $user){
        return static::driver()->createCode($user);
    }

    public static function twoFactorCodeInput(){
        return static::driver()->twoFactorCodeInput();
    }

    public static function checkCode(){
        if(static::driver()->checkCode()){
            \Session::set('twoFactorAuth','1'); //code passed so remove the session check
            static::driver()->codePassed();
        }else{
            static::driver()->codeFailed();
        }
    }

    public static function twoFactorSetup(){
        return static::driver()->twoFactorSetup();
    }

    public static function enabledUserTwoFactorAuth(){
        static::driver()->enabledUserTwoFactorAuth();
    }

    public static function isGlobalTwoFactorEnabled(){
        $settings = \CMF\CMF::settings();
        if(!empty($settings) && $settings->twofa_enabled == 1){
            return true;
        }else{
            return false;
        }

    }

    public static function isTwoFactorAuthComplete(){
        $complete = \Session::get('twoFactorAuth','0');
        
        if($complete == '1'){
            return true;
        }else{
            return false;
        }
    }
    public static function hasUserEnabledTwoFactor($user){
        if($user->twofa_enabled == 1){
            return true;
        }else{
            return false;
        }
    }

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
     * @return \CMF\Auth\Driver
     */
    protected static function driver()
    {
        return static::forge()->driver;
    }
}