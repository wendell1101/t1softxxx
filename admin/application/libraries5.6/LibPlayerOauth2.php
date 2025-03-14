<?php

require_once dirname(__FILE__) . '/vendor/autoload.php';

require_once dirname(__FILE__) . '/oauth2/PlayerClientRepository.php';
require_once dirname(__FILE__) . '/oauth2/PlayerScopeRepository.php';
require_once dirname(__FILE__) . '/oauth2/PlayerAccessTokenRepository.php';
require_once dirname(__FILE__) . '/oauth2/PlayerUserRepository.php';
require_once dirname(__FILE__) . '/oauth2/PlayerRefreshTokenRepository.php';
require_once dirname(__FILE__) . '/oauth2/PlayerAccessTokenEntity.php';
require_once dirname(__FILE__) . '/oauth2/PlayerClientEntity.php';
require_once dirname(__FILE__) . '/oauth2/PlayerRefreshTokenEntity.php';
require_once dirname(__FILE__) . '/oauth2/PlayerScopeEntity.php';
require_once dirname(__FILE__) . '/oauth2/PlayerUserEntity.php';
require_once dirname(__FILE__) . '/psr7/Psr17Factory.php';
require_once dirname(__FILE__) . '/psr7/Request.php';
require_once dirname(__FILE__) . '/psr7/Response.php';
require_once dirname(__FILE__) . '/psr7/ServerRequest.php';
require_once dirname(__FILE__) . '/psr7/Stream.php';
require_once dirname(__FILE__) . '/psr7/UploadedFile.php';
require_once dirname(__FILE__) . '/psr7/Uri.php';
require_once dirname(__FILE__) . '/psr7-server/ServerRequestCreator.php';
require_once dirname(__FILE__) . '/psr7-server/ServerRequestCreatorInterface.php';
require_once dirname(__FILE__) . '/psr17/RequestFactoryInterface.php';
require_once dirname(__FILE__) . '/psr17/ResponseFactoryInterface.php';
require_once dirname(__FILE__) . '/psr17/ServerRequestFactoryInterface.php';
require_once dirname(__FILE__) . '/psr17/StreamFactoryInterface.php';
require_once dirname(__FILE__) . '/psr17/UploadedFileFactoryInterface.php';
require_once dirname(__FILE__) . '/psr17/UriFactoryInterface.php';

class LibPlayerOauth2{

	private static $singleInstance=null;

	/**
	 * generate single instance
	 * @return object
	 */
	public static function generateInstance(){
		if(empty(self::$singleInstance)){
			self::$singleInstance=new LibPlayerOauth2();
		}
		return self::$singleInstance;
	}

	private $CI;
	private $utils;
	private $player_oauth2_model;
	/* \League\OAuth2\Server\AuthorizationServer */
	private $server;
	private $resourceServer;
	private $clientRepository;
	private $scopeRepository;
	private $accessTokenRepository;
	private $userRepository;
	private $refreshTokenRepository;
	private $tokenResponse;
	private $encryptionKey;

	private function __construct(){
		$this->CI = &get_instance();
		$this->CI->load->model(['player_oauth2_model']);
		$this->utils=$this->CI->utils;
		$this->player_oauth2_model=$this->CI->player_oauth2_model;
		$this->init();
	}

	/**
	 * init
	 * @throws RuntimeException
	 */
	public function init(){
		$this->clientRepository = new PlayerClientRepository($this->CI, $this->player_oauth2_model); // instance of ClientRepositoryInterface
		$this->scopeRepository = new PlayerScopeRepository($this->CI); // instance of ScopeRepositoryInterface
		$this->accessTokenRepository = new PlayerAccessTokenRepository($this->CI, $this->player_oauth2_model); // instance of AccessTokenRepositoryInterface
		$this->userRepository = new PlayerUserRepository($this->CI, $this->player_oauth2_model); // instance of UserRepositoryInterface
		$this->refreshTokenRepository = new PlayerRefreshTokenRepository($this->CI, $this->player_oauth2_model); // instance of RefreshTokenRepositoryInterface
		$this->tokenResponse=new \League\OAuth2\Server\ResponseTypes\BearerTokenResponse();
		//load from file
		$player_oauth2_settings=$this->utils->getConfig('player_oauth2_settings');
		$privateKey='file://'.$player_oauth2_settings['private_key'];
		$publicKeyPath='file://'.$player_oauth2_settings['public_key'];
		$this->encryptionKey=$player_oauth2_settings['encryption_key'];
		if(!file_exists($privateKey)){
			throw new RuntimeException('lost private key file');
		}

		$this->server = new \League\OAuth2\Server\AuthorizationServer(
			$this->clientRepository,
			$this->accessTokenRepository,
			$this->scopeRepository,
			$privateKey,
			$this->encryptionKey,
			$this->tokenResponse
		);
		$scopesString=implode(' ', $player_oauth2_settings['scopes']);
		$this->server->setDefaultScope($scopesString);

		//password grant
		$grant = new \League\OAuth2\Server\Grant\PasswordGrant(
			 $this->userRepository,
			 $this->refreshTokenRepository
		);
		$grant->setRefreshTokenTTL(new \DateInterval($player_oauth2_settings['refresh_token_ttl'])); // refresh tokens will expire after 1 month

		// Enable the password grant on the server
		$this->server->enableGrantType(
			$grant,
			new \DateInterval($player_oauth2_settings['access_token_ttl']) // access tokens will expire after 1 hour
		);
		//refresh token
		$grant = new \League\OAuth2\Server\Grant\RefreshTokenGrant($this->refreshTokenRepository);
		$grant->setRefreshTokenTTL(new \DateInterval($player_oauth2_settings['refresh_token_ttl'])); // new refresh tokens will expire after 1 month
		$this->server->enableGrantType(
		    $grant,
		    new \DateInterval($player_oauth2_settings['access_token_ttl']) // new access tokens will expire after an hour
		);

		$this->resourceServer = new \League\OAuth2\Server\ResourceServer(
			$this->accessTokenRepository,
			$publicKeyPath
		);
	}

	/**
	 * generatePsr7Request
	 * @return Psr\Http\Message\ServerRequestInterface
	 */
	public function generatePsr7Request(){
		$psr17Factory = new \Psr17Factory();

		$creator = new \ServerRequestCreator(
			$psr17Factory, // ServerRequestFactory
			$psr17Factory, // UriFactory
			$psr17Factory, // UploadedFileFactory
			$psr17Factory  // StreamFactory
		);

		$serverRequest = $creator->fromGlobals();
		return $serverRequest;
	}

	public function generatePsr7RequestFromArrays(array $server, array $headers = [], array $cookie = [],
			array $get = [], array $post = null, array $files = [], $body = null){
		$psr17Factory = new \Psr17Factory();

		$creator = new \ServerRequestCreator(
			$psr17Factory, // ServerRequestFactory
			$psr17Factory, // UriFactory
			$psr17Factory, // UploadedFileFactory
			$psr17Factory  // StreamFactory
		);

		$serverRequest = $creator->fromArrays($server, $headers, $cookie, $get, $post, $files, $body);
		return $serverRequest;
	}

	/**
	 * issueToken
	 * @param  RequestInterface $request
	 * @param ResponseInterface $response
	 * @return boolean
	 */
	public function issueToken($request, &$response){
		$success=true;
		$response=new \Response();
		try{
			$response=$this->server->respondToAccessTokenRequest($request, $response);
		} catch (\League\OAuth2\Server\Exception\OAuthServerException $exception) {
			// All instances of OAuthServerException can be formatted into a HTTP response
			$response=$exception->generateHttpResponse($response);
			$success=false;
			$this->utils->error_log('get oauth error', $exception, $exception->getHint());
		} catch (\Exception $exception) {
			// Unknown exception
			$body = Stream::create($exception->getMessage());
			$response=$response->withStatus(500)->withBody($body);
			$success=false;
			$this->utils->error_log('get unknown error from access token', $exception);
		}
		return $success;
	}

	public function issueShortTermToken($request, &$response){

		$player_oauth2_settings=$this->utils->getConfig('player_oauth2_settings');
		$refresh_token_ttl = $this->utils->safeGetArray($player_oauth2_settings, 'shourt_term_token_ttl', 'PT1M');
		$grant = new \League\OAuth2\Server\Grant\PasswordGrant(
			$this->userRepository,
			$this->refreshTokenRepository
	   );
		$grant->setRefreshTokenTTL(new \DateInterval($refresh_token_ttl)); // new refresh tokens will expire after 1 month
		$this->server->enableGrantType(
		    $grant,
		    new \DateInterval($refresh_token_ttl) // new access tokens will expire after an hour
		);

		$success=true;
		$response=new Response();
		try{
			$response=$this->server->respondToAccessTokenRequest($request, $response);
		} catch (\League\OAuth2\Server\Exception\OAuthServerException $exception) {
			// All instances of OAuthServerException can be formatted into a HTTP response
			$response=$exception->generateHttpResponse($response);
			$success=false;
			$this->utils->error_log('get oauth error', $exception, $exception->getHint());
		} catch (\Exception $exception) {
			// Unknown exception
			$body = Stream::create($exception->getMessage());
			$response=$response->withStatus(500)->withBody($body);
			$success=false;
			$this->utils->error_log('get unknown error from access token', $exception);
		}
		return $success;
	}

	/**
	 * refreshToken
	 * @param  RequestInterface $request
	 * @param ResponseInterface $response
	 * @return boolean
	 */
	public function refreshToken($request, &$response){
		$success=true;
		$response=new \Response();
		try{
			$response=$this->server->respondToAccessTokenRequest($request, $response);
		} catch (\League\OAuth2\Server\Exception\OAuthServerException $exception) {
			// All instances of OAuthServerException can be formatted into a HTTP response
			$response=$exception->generateHttpResponse($response);
			$success=false;
		} catch (\Exception $exception) {
			// Unknown exception
			$body = Stream::create($exception->getMessage());
			$response=$response->withStatus(500)->withBody($body);
			$success=false;
		}
		return $success;
	}

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface      $errorResponse
	 *
	 * @return boolean
	 */
	public function validateToken(&$request, &$errorResponse, &$username, &$oauth_access_token_id)
	{
		$success=true;
		try {
			$request = $this->resourceServer->validateAuthenticatedRequest($request);
			$username=$request->getAttribute('oauth_user_id');
			$oauth_access_token_id=$request->getAttribute('oauth_access_token_id');
		} catch (\League\OAuth2\Server\Exception\OAuthServerException $exception) {
			$errorResponse=$exception->generateHttpResponse(new \Response());
			$success=false;
			// @codeCoverageIgnoreStart
		} catch (\Exception $exception) {
			$errorResponse=(new \League\OAuth2\Server\Exception\OAuthServerException($exception->getMessage(), 0, 'unknown_error', 500))
				->generateHttpResponse(new \Response());
			$success=false;
			// @codeCoverageIgnoreEnd
		}

		return $success;
	}

    public function makeInvalidCredentialsResponse($hint){
		return \League\OAuth2\Server\Exception\OAuthServerException::invalidCredentialsWithHint($hint)
			->generateHttpResponse(new \Response());
	}

	public function makeAccessDeniedResponse($hint){
		return \League\OAuth2\Server\Exception\OAuthServerException::accessDenied($hint)
			->generateHttpResponse(new \Response());
	}

	public function encrypt($unencryptedData){
		return \Defuse\Crypto\Crypto::encryptWithPassword($unencryptedData, $this->encryptionKey);
	}

	public function decrypt($encryptedData){
		return \Defuse\Crypto\Crypto::decryptWithPassword($encryptedData, $this->encryptionKey);
	}

	/**
	 * deleteToken
	 * @param  string $token
	 * @param  string $userId
	 * @return boolean
	 */
	public function deleteToken($token, $userId){
		$success=false;
		if(!empty($token)){
			$success=$this->player_oauth2_model->deleteAllToken($token, $userId);
		}

		return $success;
	}

	public function makeInternalErrorResponse($hint){
		return \League\OAuth2\Server\Exception\OAuthServerException::serverError($hint)
			->generateHttpResponse(new \Response());
	}

	/**
	 *
	 * @param ResponseInterface $response
	 * @return [$username, $oauth_access_token_id]
	 */
	public function decodeResponse($response){
		$username=null;
		$oauth_access_token_id=null;
		// $success=true;
		try {
			$resultJson=$this->utils->decodeJson($response->getBody()->__toString());
			$headers=['authorization'=>'Bearer '.$resultJson['access_token']];
			$request=$this->generatePsr7RequestFromArrays($_SERVER, $headers, [], [], null, [], null);
			$request = $this->resourceServer->validateAuthenticatedRequest($request);
			$username=$request->getAttribute('oauth_user_id');
			$oauth_access_token_id=$request->getAttribute('oauth_access_token_id');
		} catch (\League\OAuth2\Server\Exception\OAuthServerException $exception) {
			// $errorResponse=$exception->generateHttpResponse(new \Response());
			// $success=false;
			// @codeCoverageIgnoreStart
		} catch (\Exception $exception) {
			// $errorResponse=(new \League\OAuth2\Server\Exception\OAuthServerException($exception->getMessage(), 0, 'unknown_error', 500))
			// 	->generateHttpResponse(new \Response());
			// $success=false;
			// @codeCoverageIgnoreEnd
		}

		return [$username, $oauth_access_token_id];
	}

}
