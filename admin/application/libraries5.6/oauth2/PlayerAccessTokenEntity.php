<?php

use \League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use \League\OAuth2\Server\Entities\ClientEntityInterface;
use \League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use \League\OAuth2\Server\Entities\Traits\EntityTrait;
use \League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

class PlayerAccessTokenEntity implements AccessTokenEntityInterface{

	use AccessTokenTrait, EntityTrait, TokenEntityTrait;

	/**
	 * Create a new token instance.
	 *
	 * @param  string  $userIdentifier
	 * @param  array  $scopes
	 * @param  \League\OAuth2\Server\Entities\ClientEntityInterface  $client
	 * @return void
	 */
	public function __construct($userIdentifier, array $scopes, ClientEntityInterface $client)
	{
		$this->setUserIdentifier($userIdentifier);

		foreach ($scopes as $scope) {
			$this->addScope($scope);
		}

		$this->setClient($client);
	}

}
