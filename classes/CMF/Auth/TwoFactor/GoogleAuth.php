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
    }

   
    public function createCode($user)
    {
        $username = $user->username;
        $auth = new GoogleAuthenticator();
        $salt = '7WAOQFANYIKBFLSWEUUWLVMTVBgrwaeg';
        $secret = $username.$salt;
        $secret = $auth->generateSecret();
        $user->saveTwofactorSecret($secret);
        $host = "www.randomsite.com";
        $url = $auth->getURL($username, $host, $secret);
        
        return $url;
    }

    public function enabledUserTwoFactorAuth(){
        $userId = \Session::get('Admin\\Model_User_logged');
		$user = \Admin\Model_User::find($userId);
		
        $user->EnabledTwoFactorAuth();
        \CMF::adminRedirect('/login', 'location');
    }

    public function twoFactorSetup(){
		$userId = \Session::get('Admin\\Model_User_logged');
		$data = array();
		if(!empty($userId)){
			$user = \Admin\Model_User::find($userId);
            $data['img'] = \CMF\TwoFactorAuth::createCode($user);
            
		}
		
		return \View::forge('admin/auth/twofactor.twig',$data);
    }

    public function twoFactorCodeInput(){
        $data = array();
        return \View::forge('admin/auth/googlecodeinput.twig',$data);
    }

    public function checkCode(){
        $userId = \Session::get('Admin\\Model_User_logged');
        if(!empty($userId)){
            $code = $_POST['code'];
            $user = \Admin\Model_User::find($userId);
            $username = $user->username;
            $secret = $user->twofa_secret;
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