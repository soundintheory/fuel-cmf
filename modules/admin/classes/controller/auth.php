<?php

namespace Admin;

use CMF\Auth,
    \CMF\Auth\PasswordValidator as PasswordValidator,
    Fuel\Core\Response,
    CMF\Auth\Mailer;

class Controller_Auth extends \Controller
{
    public function action_login()
    {
        return \View::forge('admin/auth/login.twig', array('next' => \Input::get('next')));
    }

    public function action_perform_login()
    {
        if (\CMF\Auth::authenticate(\Input::post('username'), \Input::post('password'))) {
            \Response::redirect('/' . ltrim(\Input::post('next', 'admin/' . \Config::get('cmf.admin.default_section')), '/'), 'location');
        } else {
            \Session::set_flash('main_alert', array('attributes' => array('class' => 'alert-danger'), 'msg' => \Lang::get('admin.errors.account.invalid')));
            return \View::forge('admin/auth/login.twig', array('next' => \Input::get('next')));
        }
    }

    public function action_logout()
    {
        if (\CMF\Auth::logout()) {
            \Session::delete('cmf.admin.language');
            \Response::redirect('/admin/login', 'location');
        }
    }

    public function action_forgot()
    {
        return \View::forge('admin/auth/forgot.twig');
    }

    public function action_reset()
    {
        $token = \Input::get('token');

        if (empty($token)) {
            $msg = 'If you\'re trying to reset your password, you need to request it below.';
            \Session::set_flash('main_alert', array('attributes' => array('class' => 'alert-warning'), 'msg' => $msg));
            return \Response::redirect('admin/auth/login.twig', 'refresh');
        }

        return \View::forge('admin/auth/reset.twig', array('token' => $token));
    }

    public function action_reset_password()
    {
        $token = $this->get_token();

        $user = $this->find_user_by(array('reset_password_token' => $token));

        if (empty($user)) {
            $msg = 'If you\'re trying to reset your password, you need to request below.';
            \Session::set_flash('main_alert', array('attributes' => array('class' => 'alert-warning'), 'msg' => $msg));
            return \View::forge('admin/auth/forgot.twig');
        }

        $password = \Input::post('password');
        if (!empty($password)) {

            $pwd = new PasswordValidator(\Input::post('password'));

            if (!$pwd->isValid()) {
                \Session::set_flash('main_alert', array('attributes' => array('class' => 'alert-warning'), 'msg' => 'Oops, your password didn\'t meet our requirements, please try again.'));
                return \View::forge('admin/auth/reset.twig', array('token' => $token));
            }
        }

        $user->reset_password($password);
        \Session::set_flash('main_aler/t', array('attributes' => array('class' => 'alert-success'), 'msg' => 'Your password has been updated.'));
        return \View::forge('admin/auth/login.twig');
    }


    public function action_forgot_password()
    {
        $email_address = \Input::post('email');

        // Find user by supplied email address
        $user = $this->find_user_by(array('email' => $email_address));

        if (empty($user)) {
            // No user has been found with that email, but notice should be given with no reset
            $temp_user = new Model_User();
            $temp_user->setEmail($email_address);
            Mailer::warning($temp_user);

            \Session::set_flash('main_alert', array('attributes' => array('class' => 'alert-success'), 'msg' => 'Instructions have been sent to: ' . $email_address));
            return \View::forge('admin/auth/login.twig');
        }

        if ($user->send_reset_password_instructions()) {
            $msg = 'A email has been sent to ' . $email_address . ' with instructions on how to change your password.';
            \Session::set_flash('main_alert', array('attributes' => array('class' => 'alert-success'), 'msg' => $msg));
            return \View::forge('admin/auth/login.twig', array('next' => \Input::get('next')));
        }
    }

    private function find_user_by($attr)
    {
        if (!empty($attr)) {
            return Model_User::findOneBy($attr);
        }
        return null;
    }

    private function get_token()
    {
        $token = \Input::post('token');

        if (empty($token)) {
            $token = \Input::get('token');

            if (empty($token)) {
                // No token is present at all - redirect back to login?
                return \Response::redirect('admin/auth/login.twig', 'location');
            }
        }
        return $token;
    }

}