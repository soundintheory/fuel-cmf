<?php

namespace CMF\Auth;

use PasswordHash,
    Model\User,
    Model\Permission,
    Model\Role;

/**
 * Mailer
 *
 * @package    CMF
 * @subpackage Auth
 */
class Mailer
{
    public static function __callStatic($method, $arguments)
    {
        $name = str_replace(array('send_', '_instructions'), '', $method);
        return static::_send_instructions($name, $arguments[0]);
    }

    /**
     * Sends the instructions to a user's email address.
     *
     * @return bool
     */
    private static function _send_instructions($name, $user)
    {
        $config_key = null;

        switch ($name) {
            case 'confirmation':
                $config_key = 'confirmable';
                break;
            case 'reset_password':
                $config_key = 'recoverable';
                break;
            case 'unlock':
                $config_key = 'lockable';
                break;
            default:
                throw new \InvalidArgumentException("Invalid instruction: $name");
        }

        $mail = \Email::forge();
        $mail->from(\Config::get('email.defaults.from.email'), \Config::get('email.defaults.from.name'));
        $mail->to($user->email);
        $mail->subject(__("cmf.auth.mailer.subject.$name"));

        $token_name = "{$name}_token";
        $mail->html_body(\View::forge("auth/emails/{$name}_instructions", array(
            'username' => $user->username,
            'uri'      => \Uri::create(':url/:token', array(
                'url'   => rtrim(\Config::get("cmf.auth.{$config_key}.url"), '/'),
                'token' => $user->{$token_name}
            ))
        )));

        $mail->priority(\Email::P_HIGH);

        try {
            return $mail->send();
        } catch (\EmailSendingFailedException $ex) {
            logger(\Fuel::L_ERROR, "Mailer failed to send {$name} instructions.");
            return false;
        }
    }
}