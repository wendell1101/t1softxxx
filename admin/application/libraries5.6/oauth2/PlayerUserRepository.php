<?php

use \League\OAuth2\Server\Repositories\UserRepositoryInterface;
use \League\OAuth2\Server\Entities\ClientEntityInterface;
// use RuntimeException;

class PlayerUserRepository implements UserRepositoryInterface{

	/**
	 * The hasher implementation.
	 *
	 */
	// protected $hasher;
	private $player_oauth2_model;
	private $CI;
	private $utils;

	/**
	 * Create a new repository instance.
	 *
	 * @return void
	 */
	public function __construct($CI, $player_oauth2_model)
	{
		$this->player_oauth2_model=$player_oauth2_model;
		$this->CI=$CI;
		$this->utils=$this->CI->utils;
		// $this->hasher = $hasher;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
	{
		//get and validate from db
		$authId=$this->player_oauth2_model->findAndValidatePlayer($username, $password);
		if(empty($authId)){
			return;
		}
		return new PlayerUserEntity($authId);
		// $provider = $clientEntity->provider ?: config('auth.guards.api.provider');

		// if (is_null($model = config('auth.providers.'.$provider.'.model'))) {
		//     throw new RuntimeException('Unable to determine authentication model from configuration.');
		// }

		// if (method_exists($model, 'findAndValidateForPassport')) {
		//     $user = (new $model)->findAndValidateForPassport($username, $password);

		//     if (! $user) {
		//         return;
		//     }

		//     return new User($user->getAuthIdentifier());
		// }

		// if (method_exists($model, 'findForPassport')) {
		//     $user = (new $model)->findForPassport($username);
		// } else {
		//     $user = (new $model)->where('email', $username)->first();
		// }

		// if (! $user) {
		//     return;
		// } elseif (method_exists($user, 'validateForPassportPasswordGrant')) {
		//     if (! $user->validateForPassportPasswordGrant($password)) {
		//         return;
		//     }
		// } elseif (! $this->hasher->check($password, $user->getAuthPassword())) {
		//     return;
		// }

		// return new User($user->getAuthIdentifier());
	}

}

