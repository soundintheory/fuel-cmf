<?php

namespace Api;

/**
 * @package  app
 * @extends  Controller_Model
 */
class Controller_Session extends Controller_Model
{
	public $secret = false;

	/**
	 * Returns details about currently logged session, if any
	 * @return array
	 */
	public function action_current()
	{
		$user_type = \Input::param('user_type') ?: 'Admin\\Model_User';
		$scope = \Input::param('scope') ?: 'api';

		if (\CMF\Auth::logged_in(null, $user_type)) {

			// Find the logged in user and get an API key for it
			$user = \CMF\Auth::current_user();
			$key = $this->getKey($user, $user_type, $scope);

			$user_data = $user->toArray();
			unset($user_data['encrypted_password']);

			return array(
				'user' => $user_data,
				'api_key' => $key->toArray()
			);

		}

		throw new \HttpException('No valid session was found', \HttpException::NOT_FOUND);
	}

	/**
	 * Creates a new session and API key
	 * @return array
	 */
	public function action_add()
	{
		$user_type = \Input::param('user_type') ?: 'Admin\\Model_User';
		$scope = \Input::param('scope') ?: 'api';

		if (\CMF\Auth::authenticate(\Input::post('username'), \Input::post('password'), $user_type)) {

			// Purge old keys
			$this->removeOldKeys();

			// Find the logged in user and get an API key for it
			$user = \CMF\Auth::current_user();
			$key = $this->getKey($user, $user_type, $scope);

			$user_data = $user->toArray();
			unset($user_data['encrypted_password']);

			return array(
				'user' => $user_data,
				'api_key' => $key->toArray()
			);
		}
		
		throw new \HttpException('Invalid Login', \HttpException::UNAUTHORIZED);
	}

	/**
	 * Create an API key for a user in a particular scope
	 * @return \CMF\Model\User\Apikey
	 */
	public function getKey($user, $type, $scope)
	{
		$key = new \Model_User_Apikey();
		$key->populate(array(
			'user_id' => $user->id,
			'user_type' => $type,
			'expires_at' => new \DateTime('@'.(strtotime('now') + (25 * 60 * 60))),
			'access_token' => \CMF\Auth::forge()->generate_token(),
			'scope' => $scope
		));

		\D::manager()->persist($key);
		\D::manager()->flush();

		return $key;
	}

	/**
	 * Purge all expired API keys from the database
	 */
	public function removeOldKeys()
	{		$keys = \CMF\Model\User\Apikey::select('item')
			->andWhere('item.expires_at < :now')
			->setParameter('now', new \DateTime())
			->getQuery()->getResult();

		foreach ($keys as $key) {
			\D::manager()->remove($key);
		}

		\D::manager()->flush();
	}
}