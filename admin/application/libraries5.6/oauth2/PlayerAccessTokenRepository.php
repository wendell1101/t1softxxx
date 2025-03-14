<?php

require_once dirname(__FILE__) . '/FormatsScopesForStorage.php';

use \League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use \League\OAuth2\Server\Entities\ClientEntityInterface;
use \League\OAuth2\Server\Entities\AccessTokenEntityInterface;

class PlayerAccessTokenRepository implements AccessTokenRepositoryInterface{

	use FormatsScopesForStorage;

	/**
	 * The token repository instance.
	 *
	 */
	// protected $tokenRepository;

	/**
	 * The event dispatcher instance.
	 *
	 */
	// protected $events;

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
		// $this->events = $events;
		// $this->tokenRepository = $tokenRepository;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
	{
		return new PlayerAccessTokenEntity($userIdentifier, $scopes, $clientEntity);
	}

	/**
	 * {@inheritdoc}
	 */
	public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
	{
		//create token
		$success=$this->player_oauth2_model->createAccessToken([
			'id' => $accessTokenEntity->getIdentifier(),
			'user_id' => $accessTokenEntity->getUserIdentifier(),
			'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
			'scopes' => $this->scopesToArray($accessTokenEntity->getScopes()),
			'revoked' => false,
			'created_at' => $this->utils->formatDateTimeForMysql(new DateTime),
			'updated_at' => $this->utils->formatDateTimeForMysql(new DateTime),
			'expires_at' => $this->utils->formatDateTimeForMysql($accessTokenEntity->getExpiryDateTime()),
		]);
		if(!$success){
			throw new RuntimeException('save access token failed');
		}
		// $this->tokenRepository->create([
		//     'id' => $accessTokenEntity->getIdentifier(),
		//     'user_id' => $accessTokenEntity->getUserIdentifier(),
		//     'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
		//     'scopes' => $this->scopesToArray($accessTokenEntity->getScopes()),
		//     'revoked' => false,
		//     'created_at' => new DateTime,
		//     'updated_at' => new DateTime,
		//     'expires_at' => $accessTokenEntity->getExpiryDateTime(),
		// ]);

		// $this->events->dispatch(new AccessTokenCreated(
		//     $accessTokenEntity->getIdentifier(),
		//     $accessTokenEntity->getUserIdentifier(),
		//     $accessTokenEntity->getClient()->getIdentifier()
		// ));
	}

	/**
	 * {@inheritdoc}
	 */
	public function revokeAccessToken($tokenId)
	{
		//update db
		$success=$this->player_oauth2_model->revokeAccessToken($tokenId);
		if(!$success){
			throw new RuntimeException('revoke access token failed: '.$tokenId);
		}
		// $this->tokenRepository->revokeAccessToken($tokenId);
	}

	/**
	 * {@inheritdoc}
	 */
	public function isAccessTokenRevoked($tokenId)
	{
		//update db
		return $this->player_oauth2_model->isAccessTokenRevoked($tokenId);
		// return $this->tokenRepository->isAccessTokenRevoked($tokenId);
	}

}
