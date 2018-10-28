<?php

namespace CMF\Auth\TwoFactor;

use Hautelook\Phpass\PasswordHash,
    CMF\Auth,
    CMF\Model\User,
    Admin\Model_User,
    CMF\Model\Permission,
    CMF\Model\Role,
    Google\Authenticator\GoogleAuthenticator;

/**
 * Driver
 *
 * @package    CMF
 * @subpackage Auth
 */
class GoogleAuth
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
        $userId = \Session::get('Admin\\Model_User_logged');
		$this->user = \Admin\Model_User::find($userId);
    }

   
    public function createCode($user)
    {
        $username = $user->username;
        $auth = new GoogleAuthenticator();
        $secret = $auth->generateSecret();
        $user->saveTwofactorSecret($secret);
        $host = $_SERVER['HTTP_HOST'];
        $url = $auth->getURL($username, $host, $secret);
        return $url;
    }

    public function enabledUserTwoFactorAuth(){
        $this->user->EnabledTwoFactorAuth();
        \CMF::adminRedirect('/login', 'location');
    }

    public function hasUserEnabledTwoFactor($user){
        if($this->user->twofa_enabled == 1){
            return true;
        }else{
            return false;
        }
    }

    public function twoFactorSetup(){
		$data = array();
		if(!empty($this->user)){
            $data['img'] = \CMF\TwoFactorAuth::createCode($this->user);
		}
		return \View::forge('admin/auth/twofactor.twig',$data);
    }

    public function twoFactorCodeInput(){
        $data = array();
        return \View::forge('admin/auth/googlecodeinput.twig',$data);
    }

    public function checkCode(){
        if(!empty($this->user)){
            if(!isset($_POST['code'])){
                return false;
            }
            $code = str_replace(" ", "", $_POST['code']);
            $secret = $this->user->getTwoFactorSecret();
            $auth = new GoogleAuthenticator();
            return $auth->checkCode($secret,$code);
            
        }
        
        return false;
    }

    public function codeFailed(){
        \Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => 'Invaild Code, Please try again' ));
        \CMF::adminRedirect("/admin/twofactorinput", 'location');
    }

    public function codePassed(){
        \CMF::adminRedirect('/', 'location');
    }

}