<?php

use \League\OAuth2\Server\Entities\ClientEntityInterface;
use \League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

class PlayerScopeRepository implements ScopeRepositoryInterface{

    /**
     * All of the scopes defined for the application.
     *
     * @var array
     */
    private $scopes = [
        //
    ];

	private $CI;
	private $utils;
	public function __construct($CI){
		$this->CI=$CI;
		//load scopes from config
		$this->utils=$this->CI->utils;
		$player_oauth2_settings=$this->utils->getConfig('player_oauth2_settings');
		$this->scopes=$player_oauth2_settings['scopes'];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getScopeEntityByIdentifier($identifier)
	{
		//create new scope entity
        // $this->utils->debug_log('get scope entity from', $identifier, $this->scopes);
		if ($this->hasScope($identifier)) {
            // $this->utils->debug_log('create new scope');
		    return new PlayerScopeEntity($identifier);
		}
	}

    /**
     * Determine if the given scope has been defined.
     *
     * @param  string  $id
     * @return bool
     */
    public function hasScope($id)
    {
        return $id === '*' || in_array($id, $this->scopes);
    }

	/**
	 * {@inheritdoc}
	 */
	public function finalizeScopes(
		array $scopes, $grantType,
		ClientEntityInterface $clientEntity, $userIdentifier = null)
	{
		if (! in_array($grantType, ['password', 'personal_access', 'client_credentials'])) {
		    // $scopes = collect($scopes)->reject(function ($scope) {
		    //     return trim($scope->getIdentifier()) === '*';
		    // })->values()->all();
		    //reject *
		    $newScopes=[];
			foreach ($scopes as $scope) {
				if(trim($scope->getIdentifier()) === '*'){
					continue;
				}
				$newScopes[]=$scope;
			}
			$scopes=$newScopes;
		}
		$result=[];
		//available scope
		foreach ($scopes as $scope) {
			if($this->hasScope($scope->getIdentifier())){
				$result[]=$scope;
			}
		}
		return $result;
	}

}
