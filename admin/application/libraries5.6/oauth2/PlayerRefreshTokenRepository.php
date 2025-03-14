<?php

use \League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

use \League\OAuth2\Server\Entities\RefreshTokenEntityInterface;

class PlayerRefreshTokenRepository implements RefreshTokenRepositoryInterface{

	/**
	 * The refresh token repository instance.
	 *
	 */
	// protected $refreshTokenRepository;

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
		// $this->refreshTokenRepository = $refreshTokenRepository;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getNewRefreshToken()
	{
		return new PlayerRefreshTokenEntity;
	}

	/**
	 * {@inheritdoc}
	 */
	public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
	{
		//create in db
		$success=$this->player_oauth2_model->createRefreshToken([
		    'id' => $refreshTokenEntity->getIdentifier(),
		    'access_token_id' => $refreshTokenEntity->getAccessToken()->getIdentifier(),
		    'revoked' => false,
		    'expires_at' => $this->utils->formatDateTimeForMysql($refreshTokenEntity->getExpiryDateTime()),
		]);
		if(!$success){
			throw new RuntimeException('save refresh token failed');
		}
		// $this->refreshTokenRepository->create([
		//     'id' => $id = $refreshTokenEntity->getIdentifier(),
		//     'access_token_id' => $accessTokenId = $refreshTokenEntity->getAccessToken()->getIdentifier(),
		//     'revoked' => false,
		//     'expires_at' => $refreshTokenEntity->getExpiryDateTime(),
		// ]);

		// $this->events->dispatch(new RefreshTokenCreated($id, $accessTokenId));
	}

	/**
	 * {@inheritdoc}
	 */
	public function revokeRefreshToken($tokenId)
	{
		//update db
		$success=$this->player_oauth2_model->revokeRefreshToken($tokenId);
		if(!$success){
			throw new RuntimeException('revoke refresh token failed');
		}
		// $this->refreshTokenRepository->revokeRefreshToken($tokenId);
	}

	/**
	 * {@inheritdoc}
	 */
	public function isRefreshTokenRevoked($tokenId)
	{
		//query db
		return $this->player_oauth2_model->isRefreshTokenRevoked($tokenId);
		// return $this->refreshTokenRepository->isRefreshTokenRevoked($tokenId);
	}

}

