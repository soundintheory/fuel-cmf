<?php

namespace CMF\Model;

use CMF\Auth,
    CMF\Auth\Failure,
    CMF\Auth\Driver,
    CMF\Auth\Mailer,
    Doctrine\ORM\Mapping as ORM,
	Fuel\Core\Database_Exception,
	Gedmo\Mapping\Annotation as Gedmo,
	Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 *
 * @package    CMF
 * @subpackage Model
 **/
class User extends Base
{
	
	/**
     * Validation regular expression for username
     */
    const REGEX_USERNAME = '/^[a-zA-Z0-9_]+$/';
    
    /**
     * Validation regular expression for password
     */
    const REGEX_PASSWORD = '/^[.a-zA-Z_0-9-!@#$%\^&*()]{6,32}$/';

	/**
     * User's plaintext password, used for validation purposes
     *
     * @var string
     */
	public $password;
    
    /**
     * Authenticates and allows a user to enter either their email address or
     * their username into the username field.
     *
     * @param string $username_or_email_or_id The username or email or id of the user to authenticate
     * @param bool   $force             	  Whether to force an authentication, if this is
     *                                  	  true, it will bypass checking whether the user
     *                                  	  account is locked or confirmed.
     *
     * @return \CMF\Model\User|null The user that matches the tokens or
     *                                 null if no user matches that condition.
     *
     * @throws \CMF\Auth\Failure If the user needs to be confirmed
     */
    public static function authenticate($username_or_email_or_id, $force = false)
    {
        if (empty($username_or_email_or_id)) {
            return null;
        }

		$em = \DoctrineFuel::manager();
        $called_class = get_called_class();

		if (is_int($username_or_email_or_id)) {
            
            $record = $called_class::select('item')
            ->where("item.id = $username_or_email_or_id")
            ->getQuery()->getResult();
            
			if (count($record) == 0) return null;
            
		} else {
            
	        $username_or_email = \DB::escape(\Str::lower($username_or_email_or_id));
            $record = $called_class::select('item')
            ->where("item.email = $username_or_email")
            ->orWhere("item.username = $username_or_email")
            ->getQuery()->getResult();
            
            if (count($record) == 0) return null;
            
		}
		
		$record = $record[0];
		
        if ($record) {
            if ($force) {
                return $record;
            }
            
            if ($record->is_confirmation_required()) {
                throw new Failure('unconfirmed');
            } elseif ($record->is_access_locked()) {
                throw new Failure('locked');
            }
            
            // Unlock the user if the lock is expired, no matter
            // if the user can login or not (wrong password, etc)
            if ($record->is_lock_expired()) {
                // unlock but do not save, saving handled by Driver::complete_login()
                $record->unlock_access(false);
            }
            
            return $record;
        }

        return null;
    }

    /**
     * Creates an anonymous user. An anonymous user is basically an auto-generated
     * {@link \CMF\Model\User} account that is created behind the scenes and its
     * completely transparent.
     *
     * All "guests" must have a {@link \CMF\Model\User} so this is necessary
     * (eg. when adding to the "cart" and before the customer has a chance to
     * provide an email or to register).
     *
     * @return \CMF\Model\User
     */
    public static function anonymous()
    {
        static $user = null;

        $user || $user = \Session::get('auth.anonymous_user');

        if ($user instanceof static) {
            return $user;
        }

        // Create a new token
        $persistence_token = \Str::random('unique');

        $user = new static();
        $user->username = $persistence_token;
        $user->email    = "{$persistence_token}@anonymous.net";
        $user->password = $persistence_token;

        \Session::set('cmf.anonymous_user', $user);
        \Session::instance()->rotate();

        return $user;
    }

    /**
     * Returns whether a user is an anonymous user (guest)
     *
     * @return bool
     */
    public function is_anonymous()
    {
        return (bool)(preg_match('/@cmf_anonymous.net$/', $this->email) === 1);
    }

    /**
     * Track information about user sign ins. It tracks the following columns:
     *
     * - sign_in_count      - Increased every time a sign in is made (by form, openid, oauth)
     * - current_sign_in_at - A timestamp updated when the user signs in
     * - last_sign_in_at    - Holds the timestamp of the previous sign in
     * - current_sign_in_ip - The remote ip updated when the user sign in
     * - last_sign_in_at    - Holds the remote ip of the previous sign in
     *
     * @return bool
     */
	
    public function update_tracked_fields()
    {
        if (\Config::get('cmf.auth.trackable') !== true) {
            return false;
        }

        $old_current = $this->current_sign_in_at;
        $new_current = \DB::expr('CURRENT_TIMESTAMP');
		return false;
		
		//TODO: Port this for doctrine2
		//
        //$this->last_sign_in_at = ($old_current != static::$_properties['last_sign_in_at']['default'])
        //                       ? $old_current
        //                       : $new_current;
        //
        //$this->current_sign_in_at = $new_current;
        //
        //$old_current = $this->current_sign_in_ip;
        //$this->current_sign_in_ip = null;
        //
        //$new_current = \Input::real_ip();
        //
        //$this->last_sign_in_ip = ($old_current != static::$_properties['last_sign_in_ip']['default'])
        //                       ? $old_current
        //                       : $new_current;
        //
        //$this->current_sign_in_ip = $new_current;
        //
        //if (\Config::get('cmf.auth.lockable.in_use') === true &&
        //    \Config::get('cmf.auth.lockable.lock_strategy') == 'sign_in_count')
        //{
        //    $this->sign_in_count = 0;
        //} else {
        //    $this->sign_in_count += 1;
        //}
        //
        //$return = $this->save(false);
        //
        //return $return;
        
    }

    /**
     * Update password saving the record and clearing token.
     *
     * @param string $new_password The new plaintext password to set
     *
     * @return bool
     */
    public function reset_password($new_password)
    {
		$this->password = $new_password;
        $this->clear_reset_password_token();
		
		$em = \DoctrineFuel::manager();
		$em->persist($this);
		$em->flush();
        return true;
		
    }

    /**
     * Attempt to find a user by it's reset_password_token to reset its
     * password and automatically try saving the record.
     *
     * @param string $reset_password_token
     * @param string $new_password
     *
     * @return \CMF\Model\User|null Returns a user if is found and token is still valid,
     *                                 or null if no user is found.
     *
     * @throws Failure If the token has expired
     */
    public static function reset_password_by_token($reset_password_token, $new_password)
    {
		$em = \DoctrineFuel::manager();
		$query = $em->createQuery("SELECT u FROM CMF\Model\User u WHERE u.reset_password_token = '$reset_password_token'");
		$recoverable = $query->getSingleResult();

        if ($recoverable) {
            if ($recoverable->is_reset_password_period_valid()) {
                $recoverable->reset_password($new_password);
            } else {
                throw new Failure('expired_token', array('name' => 'Reset password'));
            }
        }

        return $recoverable;
    }

    /**
     * Checks if the reset password token sent is within the limit time.
     *
     * <code>
     * \Config::set('cmf.auth.reset_password_within', '+1 day');
     * $user->reset_password_sent_at = \Date::time()->format('mysql');
     * $user->is_reset_password_period_valid(); // returns true
     * </code>
     *
     * @return bool Returns true if the user is not responding to reset_password_sent_at at all.
     */
    public function is_reset_password_period_valid()
    {
        if (\Config::get('cmf.auth.recoverable.in_use') === false) {
            return true;
        }

        if ($this->reset_password_sent_at == "0000-00-00 00:00:00") {
            return false;
        }

        $lifetime = \Config::get('cmf.auth.recoverable.reset_password_within');
        $expires  = strtotime($lifetime, strtotime($this->reset_password_sent_at));

        return (bool)($expires >= time());
    }

    /**
     * Generates a new random token for reset password and save the record
     *
     * @return bool
     */
    public function generate_reset_password_token()
    {
        if ($this->reset_password_token && $this->is_reset_password_period_valid()) {
            return true;
        }

        $this->reset_password_token   = Auth::forge()->generate_token();
        $this->reset_password_sent_at = \Date::time('UTC')->format('mysql');

        $em = \DoctrineFuel::manager();
		$em->persist($this);
		$em->flush();
		return true;
    }

    /**
     * Generates and sends the reset password instructions to a user's email address.
     *
     * @return bool
     */
    public function send_reset_password_instructions()
    {
        if (\Config::get('cmf.auth.recoverable.in_use') === true &&
            $this->generate_reset_password_token())
        {
            return Mailer::send_reset_password_instructions($this);
        }

        return false;
    }

    /**
     * Attempt to find a user by it's confirmation token and try to confirm it.
     *
     * @param string $confirmation_token
     *
     * @return \CMF\Model\User|null Returns a user if is found and token is still valid,
     *                                 or null if no user is found.
     *
     * @throws \CMF\Auth\Failure If the token has expired or the user is already confirmed
     */
    public static function confirm_by_token($confirmation_token)
    {
        $called_class = get_called_class();
        $confirmable = $called_class::select('item')
        ->where("item.confirmation_token = '$confirmation_token'")
        ->getQuery()->getResult();
        
        $confirmable = (count($confirmable) > 0) ? $confirmable[0] : false;

        if ($confirmable) {
            if ($confirmable->is_confirmation_period_valid()) {
                $confirmable->confirm();
            } else {
                throw new Failure('expired_token', array('name' => 'Confirmation'));
            }
        }

        return $confirmable;
    }

    /**
     * Confirm a user.
     *
     * @return bool
     *
     * @throws \CMF\Auth\Failure If the user is already confirmed
     */
    public function confirm()
    {
        if ($this->is_confirmation_required()) {
            $this->is_confirmed = true;
            $this->confirmation_token = null;
			$em = \DoctrineFuel::manager();
			$em->persist($this);
			$em->flush();
			return true;
        }

        throw new Failure('already_confirmed', array('email' => $this->email));
    }

    /**
     * Verifies whether a user is confirmed or not
     *
     * @return bool
     */
    public function is_confirmed()
    {
        if (\Config::get('cmf.auth.confirmable.in_use') === true) {
            return (bool)$this->is_confirmed === true;
        }

        return true;
    }

    /**
     * Checks if confirmation is required or not
     *
     * @return bool
     */
    public function is_confirmation_required()
    {
        return !$this->is_confirmed();
    }

    /**
     * Checks if the confirmation for the user is within the limit time.
     *
     * @return bool Returns true if the user is not responding to reset_password_sent_at at all.
     */
    public function is_confirmation_period_valid()
    {
        if (\Config::get('cmf.auth.confirmable.in_use') === false) {
            return true;
        }

        if ($this->confirmation_sent_at == "0000-00-00 00:00:00") {
            return false;
        }

        $lifetime = \Config::get('cmf.auth.confirmable.confirm_within');
        $expires  = strtotime($lifetime, strtotime($this->confirmation_sent_at));

        return (bool)($expires >= time());
    }

    /**
     * Generates a new random token for confirmation, and stores the time
     * this token is being generated.
     *
     * @param  bool $save Whether to save the record after generating the token
     *
     * @return bool
     */
    public function generate_confirmation_token($save = true)
    {
        if ($this->confirmation_token && $this->is_confirmation_period_valid()) {
            return true;
        }

        $this->is_confirmed         = false;
        $this->confirmation_token   = Auth::forge()->generate_token();
        $this->confirmation_sent_at = \Date::time('UTC')->format('mysql');

		if ($save === true) {
			$em = \DoctrineFuel::manager();
			$em->persist($this);
			$em->flush();
		}
		
		return true;

        //return (bool)($save === true ? $this->save(false) : true);
    }

    /**
     * Generates and sends the confirmation instructions to a user's email address.
     *
     * @return bool
     */
    public function send_confirmation_instructions()
    {
        if (\Config::get('cmf.auth.confirmable.in_use') === true &&
            $this->generate_confirmation_token())
        {
            return Mailer::send_confirmation_instructions($this);
        }

        return false;
    }

    /**
     * Finds a user by it's unlock token and try to unlock it.
     *
     * @param type $unlock_token
     *
     * @return \CMF\Model\User|null Returns a user if is found and token is still valid,
     *                                 or null if no user is found.
     *
     * @throws \CMF\Auth\Failure If the token has expired or the user is not locked
     */
    public static function unlock_access_by_token($unlock_token)
    {
        $called_class = get_called_class();
        $lockable = $called_class::select('item')
        ->where("item.unlock_token = '$unlock_token'")
        ->getQuery()->getResult();
        
        $lockable = (count($lockable) > 0) ? $lockable[0] : false;

        if ($lockable) {
            if ($lockable->is_access_locked()) {
                $lockable->unlock_access();
            } else {
                throw new Failure('expired_token', array('name' => 'Unlock'));
            }
        }

        return $lockable;
    }

    /**
     * Lock a user setting it's locked_at to actual time.
     *
     * @return bool
     */
    public function lock_access()
    {
        $this->locked_at = \Date::time('UTC')->format('mysql');

        if ($this->is_unlock_strategy_enabled('email')) {
            $this->generate_unlock_token();
        }

        // Revoke authentication token
        $this->authentication_token = null;
		
		// Save and make sure session is destroyed completely
		$em = \DoctrineFuel::manager();
		$em->persist($this);
		$em->flush();
		
        if (Auth::logout(true)) {
            // Only send instructions after token was saved successfully
            if ($this->is_unlock_strategy_enabled('email')) {
                return $this->send_unlock_instructions();
            }

            return true;
        }

        return false;
    }

    /**
     * Unlock a user by cleaning locked_at and lock strategy field.
     *
     * @param  bool $save Whether to save the record after unlocking.
     *
     * @return bool
     */
    public function unlock_access($save = true)
    {
        if (\Config::get('cmf.auth.lockable.in_use') === false) {
            return true;
        }

        if ($this->locked_at == static::$_properties['locked_at']['default'] ||
            $this->unlock_token === null)
        {
            return true;
        }

        $this->locked_at = static::$_properties['locked_at']['default'];

        $strategy = \Config::get('cmf.auth.lockable.lock_strategy');

        if (!empty($strategy) && $strategy != 'none') {
            $this->{$strategy} = 0;
        }

        $this->unlock_token = null;

		if ($save === true) {
			\DoctrineFuel::manager()->persist($this)->flush();
			return true;
		}
		
		return false;
		
        //return (bool)($save === true ? $this->save(false) : false);
    }

    /**
     * Increments the user's attempts.
     *
     * @param int $attempts The attempts to increment by
     *
     * @throws \CMF\Auth\Failure If attempts exceeded
     */
    public function update_attempts($attempts)
    {
        $strategy = \Config::get('cmf.auth.lockable.lock_strategy');

        if (!empty($strategy) && $strategy != 'none') {
            $this->{$strategy} += (int)$attempts;
        }

        if ($this->is_attempts_exceeded()) {
            $this->lock_access();
            throw new Failure('locked');
        } else {
			\DoctrineFuel::manager()->persist($this)->flush();
        }
    }

    /**
     * Checks whether the attempts have exceeded maximum number of attempts.
     *
     * @return bool
     */
    public function is_attempts_exceeded()
    {
        $strategy = \Config::get('cmf.auth.lockable.lock_strategy');

        if (empty($strategy) || $strategy == 'none') {
            return false;
        }

        return $this->{$strategy} > \Config::get('cmf.auth.lockable.maximum_attempts');
    }

    /**
     * Is the unlock enabled for the given unlock strategy.
     *
     * @param string $strategy The strategy to check for
     *
     * @return bool
     */
    public function is_unlock_strategy_enabled($strategy)
    {
        return in_array(\Config::get('cmf.auth.lockable.unlock_strategy'), array($strategy, 'both'));
    }

    /**
     * Tells if the lock is expired if 'time' unlock strategy is active.
     *
     * @return bool
     */
    public function is_lock_expired()
    {
        if (\Config::get('cmf.auth.lockable.in_use') === true &&
            $this->is_unlock_strategy_enabled('time'))
        {
            if ($this->locked_at == "0000-00-00 00:00:00") {
                return true;
            }

            $lifetime = \Config::get('cmf.auth.lockable.unlock_in');
            $expires  = strtotime($lifetime, strtotime($this->locked_at));

            return (bool)($expires <= time());
        }

        return false;
    }

    /**
     * Checks whether the record is locked or not.
     */
    public function is_access_locked()
    {
        return (\Config::get('cmf.auth.lockable.in_use') === true && !$this->is_lock_expired());
    }

    /**
     * Generates a new random token for unlocking, and stores the time
     * this token is being generated.
     *
     * @return bool
     */
    public function generate_unlock_token()
    {
        if ($this->unlock_token === null) {
            $this->unlock_token = Auth::forge()->generate_token();
        }

        return true;
    }

    /**
     * Generates and sends the unlock instructions to a user's email address.
     *
     * @return bool
     */
    public function send_unlock_instructions()
    {
        if (\Config::get('cmf.auth.lockable.in_use') === true &&
            $this->generate_unlock_token())
        {
            return Mailer::send_unlock_instructions($this);
        }

        return false;
    }

    /**
	 * @ORM\PrePersist
     * Event that does the following:
     *
     * - tests if a username or email exists in the database.
     * - downcases and trims username and email.
     * - validates and ensures an encrypted password exists if the password has changed.
     * - checks for unique fields.
     * - adds a default role, if one was specified in the config file
     */
    public function _event_before_save()
    {
		$this->_is_tracking = true;
		//$this->_add_default_role();
    }
    
    /**
	 * @ORM\PreFlush
     */
    public function _event_before_update()
    {
		$this->_is_tracking = true;
		$this->_strip_and_downcase_username_and_email();
		$this->_ensure_and_validate_password();
    }

    /**
	 * 
     * Event that makes sure confirmation token is set if enabled.
     *
     * @return void
     */
    public function _event_before_insert()
    {
        if ($this->is_confirmation_required()) {
            // Generate but do not save
            $this->generate_confirmation_token(false);
        }
    }

    /**
	 * @ORM\PostUpdate
     * Event that makes sure password is unset once the model is saved.
     *
     * @return void
     */
    public function _event_after_save()
    {
        $this->_is_tracking = false;
        unset($this->password);
    }

    /**
     * Removes reset password token
     */
    protected function clear_reset_password_token()
    {
        $this->reset_password_token   = null;
        $this->reset_password_sent_at = "0000-00-00 00:00:00";
    }

    /**
     * Gets a user's sign in ip address, handling ip to int conversion
     *
     * @return int $ip
     */
    protected function get_ip_as_int($ip)
    {
        return sprintf('%u', ip2long($ip));
    }

    /**
     * Gets a user's sign in ip address, handling int to ip conversion
     *
     * @param  string $column
     *
     * @return string|int
     */
    protected function get_sign_in_ip($column)
    {
        $ip = parent::__get($column);
        $value = ($ip != 0) ? long2ip($ip) : 0;
        return $value;
    }

    /**
     * Removes trailing whitespace from username and email and
     * converts them to lower case
     *
     * @see \CMF\Model\User::_event_before_save()
     */
    private function _strip_and_downcase_username_and_email()
    {
        if (!empty($this->username)) {
            $this->username = \Str::lower(trim($this->username));
        }

        if (!empty($this->email)) {
            $this->email = \Str::lower(trim($this->email));
        }
    }

    /**
     * Validates a user password & ensures an encrypted password is set
     *
     * @see \CMF\Model\User::_event_before_save()
     */
    private function _ensure_and_validate_password()
    {
        if (!empty($this->password)) {
            $this->encrypted_password = Auth::encrypt_password($this->password);
        }

        if (empty($this->encrypted_password)) {
            throw new \Exception(__('cmf.auth.validation.password.required'));
        }
    }

    /**
     * Adds default role to a new user if enabled in config
     *
     * @see \CMF\Model\User::_event_before_save()
     */
    private function _add_default_role()
    {
        // Make sure no roles exist already
        if (empty($this->roles) || !static::query()->related('roles')->get_one()) {
            // Check for default role
            if (($default_role = \Config::get('cmf.auth.default_role'))) {
				
				$em = \DoctrineFuel::manager();
				$query = $em->createQuery("SELECT r FROM CMF\Model\Role r WHERE r.name = '$default_role'");
				$record = $query->getSingleResult();

                if (!is_null($role)) {
                    $this->roles[] = $role;
                }
				
            }
        }
    }

    /**
     * Checks that the trackable feature is enabled and adds its required
     * fields to the properties
     *
     * @see \CMF\Model\User::_init()
     */
    private static function _init_trackable()
    {
		/*
        if (\Config::get('cmf.auth.trackable') === true) {
            static::$_properties = array_merge(static::$_properties, array(
                'sign_in_count'      => array('default' => 0),
                'current_sign_in_at' => array('default' => '0000-00-00 00:00:00'),
                'last_sign_in_at'    => array('default' => '0000-00-00 00:00:00'),
                'current_sign_in_ip' => array('default' => 0),
                'last_sign_in_ip'    => array('default' => 0),
            ));
        }
		*/
    }

    /**
     * Checks that the recoverable feature is enabled and adds its required
     * fields to the properties
     *
     * @see \CMF\Model\User::_init()
     */
    private static function _init_recoverable()
    {
		/*
        if (\Config::get('cmf.auth.recoverable.in_use') === true) {
            static::$_properties = array_merge(static::$_properties, array(
                'reset_password_token'   => array('default' => null),
                'reset_password_sent_at' => array('default' => '0000-00-00 00:00:00')
            ));
        }
		*/
    }

    /**
     * Checks that the confirmable feature is enabled and adds its required
     * fields to the properties
     *
     * @see \CMF\Model\User::_init()
     */
    private static function _init_confirmable()
    {
		/*
        if (\Config::get('cmf.auth.confirmable.in_use') === true) {
            static::$_properties = array_merge(static::$_properties, array(
                'is_confirmed'         => array('default' => false),
                'confirmation_token'   => array('default' => null),
                'confirmation_sent_at' => array('default' => '0000-00-00 00:00:00')
            ));
        }
*/
    }

    /**
     * Checks that the lockable feature is enabled and adds its required
     * fields to the properties
     *
     * @see \CMF\Model\User::_init()
     */
    private static function _init_lockable()
    {
		/*
        if (\Config::get('cmf.auth.lockable.in_use') === true) {
            $strategy = \Config::get('cmf.auth.lockable.lock_strategy');
            if ($strategy !== null) {
                static::$_properties[$strategy] = array('default' => 0);
            }

            static::$_properties = array_merge(static::$_properties, array(
                'unlock_token'    => array('default' => null),
                'locked_at'       => array('default' => '0000-00-00 00:00:00')
            ));
        }
		*/
    }

    /**
     * Checks that the profilable feature is enabled and sets up its relation.
     *
     * @see \CMF\Model\User::_init()
     */
    private static function _init_profilable()
    {
		/*
        if (\Config::get('cmf.auth.profilable') === true) {
            static::$_has_one = array_merge(static::$_has_one, array(
                'profile' => array(
                    'key_from'       => 'id',
                    'model_to'       => 'Model_Profile',
                    'key_to'         => 'user_id',
                    'cascade_save'   => true,
                    'cascade_delete' => true,
                )
            ));
        }
		*/
    }

    /**
     * Checks that the omniauthable feature is enabled and sets up its relation.
     *
     * @see \CMF\Model\User::_init()
     */
    private static function _init_omniauthable()
    {
		/*
        if (\Config::get('cmf.auth.omniauthable.in_use') === true) {
            $relation = (\Config::get('cmf.auth.omniauthable.link_multiple', false)
                      ? array('_has_many', 'services')
                      : array('_has_one', 'service'));

            static::${$relation[0]} = array_merge(static::${$relation[0]}, array(
                $relation[1] => array(
                    'key_from'       => 'id',
                    'model_to'       => 'Model_Service',
                    'key_to'         => 'user_id',
                    'cascade_save'   => true,
                    'cascade_delete' => true,
                )
            ));
        }
		*/
    }
    
    /** inheritdoc */
    public function blank($ignore_fields = null)
    {
        parent::blank($ignore_fields);
        $this->email = 'temp@email.com';
        $this->username = 'changeme';
        $this->password = 'changeme';
    }
    
    protected static $_fields = array(
        'encrypted_password' => false,
        'authentication_token' => false,
        'remember_token' => false,
        'super_user' => false,
        'email' => true,
        'username' => true,
        'password' => array(
            'field' => 'CMF\\Field\\Auth\\Password',
            'title' => 'Password',
            'mapping' => array( 'fieldName' => 'password' )
        ),
        'roles' => array(
            'field' => 'CMF\\Field\\Collection\\Checkbox'
        ),
    );
    
    protected static $_list_fields = array(
        'username',
        'email'
    );
    
    protected static $_icon = 'user';
    
    public function display()
    {
        return $this->username;
    }
    
	/////////////////// BEGIN DB PROPERTIES ///////////////////
	
	/**
     * @ORM\ManyToMany(targetEntity="\CMF\Model\Role")
     **/
    protected $roles;
	
    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Email
     * @Assert\NotBlank
     **/
    protected $email;

	/**
     * @ORM\Column(type="string", length=32, nullable=true)
     **/
    protected $username;
	
	/**
     * @ORM\Column(type="binary", length=60)
     **/
    protected $encrypted_password;
	
	/**
     * @ORM\Column(type="binary", length=60, nullable=true)
     **/
    protected $authentication_token = null;
	
	/**
     * @ORM\Column(type="binary", length=60, nullable=true)
     **/
    protected $remember_token = null;
    
    /**
     * @ORM\Column(type="boolean")
     **/
    protected $super_user = false;
}