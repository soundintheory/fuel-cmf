<?php

namespace Admin;

class Controller_Auth extends \Controller {
	
	public function action_login()
	{
		return \View::forge('admin/auth/login.twig', array( 'next' => \Input::get('next') ));
	}
	
	public function action_perform_login()
	{
	    if (\CMF\Auth::authenticate(\Input::post('username'), \Input::post('password'))) {
            \Response::redirect('/'.ltrim(\Input::post('next', 'admin/'.\Config::get('cmf.admin.default_section')), '/'), 'location');
        } else {
        	\Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => \Lang::get('admin.errors.account.invalid') ));
            return \View::forge('admin/auth/login.twig', array( 'next' => \Input::get('next') ));
        }
	}
	
	public function action_logout()
	{
	    if (\CMF\Auth::logout()) {
	    	\Session::delete('cmf.admin.language');
            \Response::redirect('/admin/login', 'location');
        }
	}
        
        public function action_forgotpassword()
	{
		return \View::forge('admin/auth/forgotpassword.twig');
	}
        
        public function action_submit_forgot()
	{
            //select user from db
            $userArr = Model_User::select('item')
                                ->where('item.email = :email')
                                ->setParameter('email', \Input::post('email'))
                                ->getQuery()
                                ->getResult();
            
            //check if user returned
            if(count($userArr) == 0){
                
                \Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => 'Your email address was not recognised' ));    
                return \View::forge('admin/auth/forgotpassword.twig');
                    
            } else {
                
                //set user object
                $user = $userArr['0'];

                //generate token
                $resetPasswordToken = \CMF\Auth::forge()->generate_token();
                $resetPasswordSentAt = new \Datetime();
                
                //update row in db along with reset time
                $user->setResetPasswordToken($resetPasswordToken);
                $user->setResetPasswordSentAt($resetPasswordSentAt);
                
                //send email
                $email = \Email::forge();
                $email->from('my@email.me', 'My Name');
                $email->to(\Input::post('email'));
                $email->subject('Forgotten Password');
                $email->body('Please visit the following link to reset your password 
                        http://localhost/sit/test-project/public/admin/resetpassword?token='.$resetPasswordToken);
                try
                {
                    $email->send();
                }
                catch(\EmailValidationFailedException $e)
                {
                    // The validation failed
                }
                catch(\EmailSendingFailedException $e)
                {
                    // The driver could not send the email
                }

                //var_dump($user);
                //exit();
                     
                //save the user
                \D::manager()->persist($user);
                \D::manager()->flush();
                
                //send the user to check email
                \Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => 'Please check your email inbox for a password reset link' ));
                return \View::forge('admin/auth/login.twig', array( 'next' => \Input::get('next') ));
                
            }
	}
        
        public function action_resetpassword()
	{
		return \View::forge('admin/auth/resetpassword.twig');
	}
        
        public function action_submit_reset()
	{
            //find the user in the database from the token
            $userArr = Model_User::select('item')
                ->where('item.reset_password_token = :reset_password_token')
                ->setParameter('reset_password_token', \Input::get('token'))
                ->getQuery()
                ->getResult();
            
            var_dump($userArr);
            exit();
            
            //check if user returned
            if(count($userArr) == 0){
                
                \Session::set_flash('main_alert', array( 'attributes' => array( 'class' => 'alert-danger' ), 'msg' => 'Your token was not recognised' ));    
                return \View::forge('admin/auth/forgotpassword.twig');
                    
            } else {
                
                //set user object
                $user = $userArr['0'];
            }
            
            
            
            //confirm the new passwords match
            $val = \Validation::forge();
            
            //update the password
            //remove the token
            //remove the token sent at
        }
}