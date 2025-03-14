<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 *
 * player oauth2 model
 */
class Player_oauth2_model extends BaseModel{

	/**
	 * updateSecretByName
	 * @param  string $name
	 * @param  string $secret
	 * @param  string &$id
	 * @return boolean
	 */
	public function updateSecretByName($name, $secret, &$id){
		$id=$this->queryClientIdByName($name);
		if(!empty($id)){
			return $this->updateSecretById($id, $secret);
		}else{
			$this->utils->error_log('cannot find name', $name);
			return false;
		}
	}

	/**
	 * generatePasswordClient
	 * @param  string $id
	 * @param  string $name
	 * @param  string $secret
	 * @return boolean
	 */
	public function generatePasswordClient($id, $name, $secret){
		$client=$this->queryClientById($id);
		if(empty($client)){
			$id=$this->generateClient($id, $name, $secret, null, null, self::DB_FALSE, self::DB_TRUE);
			return !empty($id);
		}else{
			$this->utils->error_log('duplicate name, create client failed', $name);
			return false;
		}
	}

	/**
	 * updateSecretById
	 * @param  string $id
	 * @param  string $secret
	 * @return boolean
	 */
	public function updateSecretById($id, $secret){
		$this->db->set([
			'secret'=> $secret,
			'updated_at'=>$this->utils->getNowForMysql()
		])->where('id', $id);
		return $this->runAnyUpdate('player_oauth2_clients');
	}

	/**
	 * revokeClientByName
	 * @param  string $name
	 * @return boolean
	 */
	public function revokeClientByName($name){
		$id=$this->queryClientIdByName($name);
		if(!empty($id)){
			$this->db->set([
				'revoked'=> self::DB_TRUE,
				'updated_at'=>$this->utils->getNowForMysql()
			])->where('id', $id);
			return $this->runAnyUpdate('player_oauth2_clients');
		}else{
			$this->utils->error_log('cannot find name', $name);
			return false;
		}
	}

	/**
	 * queryClientIdByName
	 * @param  string $name
	 * @return array
	 */
	public function queryClientIdByName($name){
		$this->db->select('id')->from('player_oauth2_clients')
			->where('name', $name);
		return $this->runOneRowOneField('id');
	}

	/**
	 * generateClient
	 * @param  string $name
	 * @param  string $secret
	 * @param  string $provider
	 * @param  string $redirect
	 * @param  boolean $personal_access_client
	 * @param  boolean $password_client
	 * @param  int $userId
	 * @return string
	 */
	public function generateClient($id, $name, $secret, $provider, $redirect,
		$personal_access_client, $password_client, $userId='', $confidential=1){
		// $id=random_string('alnum', 36);
		$data=[
			'id'=>$id,
			'name'=>$name,
			'secret'=>$secret,
			'provider'=>$provider,
			'redirect'=>$redirect,
			'personal_access_client'=>$personal_access_client,
			'password_client'=>$password_client,
			'user_id'=>$userId,
			'revoked'=>self::DB_FALSE,
			'confidential'=>$confidential,
			'created_at'=>$this->utils->getNowForMysql(),
			'updated_at'=>$this->utils->getNowForMysql(),
		];
		$succ=$this->runInsertDataWithBoolean('player_oauth2_clients', $data);
		if($succ){
			return $id;
		}
		return null;
	}

	/**
	 * queryClientById
	 * @param  string $clientIdentifier
	 * @return array
	 */
	public function queryClientById($clientIdentifier){
		$this->db->from('player_oauth2_clients')
			->where('id', $clientIdentifier);
		$client=$this->runOneRowArray();
		if(!empty($client)){
			$this->processGrantTypes($client);
		}
		return $client;
	}

	/**
	 * queryActiveClient
	 * @param  string $clientIdentifier
	 * @return array
	 */
	public function queryActiveClient($clientIdentifier){
		$this->db->from('player_oauth2_clients')
			->where('id', $clientIdentifier)
			->where('revoked', self::DB_FALSE);
		$client=$this->runOneRowArray();
		if(!empty($client)){
			$this->processGrantTypes($client);
		}
		return $client;
	}

	protected function processGrantTypes(&$client){
		$client['grant_types']=['authorization_code', 'refresh_token'];
		//add grant types
		if($client['password_client']){
			$client['grant_types'][]='password';
		}
		if($client['personal_access_client']){
			$client['grant_types'][]='personal_access';
		}
		if($client['confidential']){
			$client['grant_types'][]='client_credentials';
		}
		return $client;
	}

	/**
	 * createAccessToken
	 * @param  array $data
	 * @return boolean
	 */
	public function createAccessToken($data, $db=null){
		if(empty($db)){
			$db=$this->db;
		}
		$newData=[
			'id'=>$data['id'],
			'user_id'=>$data['user_id'],
			'client_id'=>$data['client_id'],
			'scopes'=>json_encode($data['scopes']),
			'revoked'=>$data['revoked'] ? self::DB_TRUE : self::DB_FALSE,
			'created_at'=>$data['created_at'],
			'updated_at'=>$data['updated_at'],
			'expires_at'=>$data['expires_at'],
		];
		return $this->runInsertDataWithBoolean('player_oauth2_access_tokens', $newData, $db);
	}

	/**
	 * isAccessTokenRevoked
	 * @param  string  $tokenId
	 * @return boolean
	 */
	public function isAccessTokenRevoked($tokenId){
		$this->db->select('revoked')->from('player_oauth2_access_tokens')->where('id', $tokenId);
		$row=$this->runOneRowArray();
		if(!empty($row)){
			if($row['revoked']==self::DB_FALSE){
				return false;
			}
		}
		return true;
	}

	/**
	 * revokeAccessToken
	 * @param  string $tokenId
	 * @return boolean
	 */
	public function revokeAccessToken($tokenId){
		$this->db->where('id', $tokenId)->set('revoked', self::DB_TRUE);
		return $this->runAnyUpdate('player_oauth2_access_tokens');
	}

	/**
	 * createRefreshToken
	 * @param  array $data
	 * @return boolean
	 */
	public function createRefreshToken($data){
		// [
		//     'id' => $refreshTokenEntity->getIdentifier(),
		//     'access_token_id' => $refreshTokenEntity->getAccessToken()->getIdentifier(),
		//     'revoked' => false,
		//     'expires_at' => $refreshTokenEntity->getExpiryDateTime(),
		// ]
		$newData=[
			'id'=>$data['id'],
			'access_token_id'=>$data['access_token_id'],
			'revoked'=>$data['revoked'] ? self::DB_TRUE : self::DB_FALSE,
			'expires_at'=>$data['expires_at'],
		];
		return $this->runInsertDataWithBoolean('player_oauth2_refresh_tokens', $newData);
	}

	/**
	 * revokeRefreshToken
	 * @param  string $tokenId
	 * @return boolean
	 */
	public function revokeRefreshToken($tokenId){
		$this->db->where('id', $tokenId)->set('revoked', self::DB_TRUE);
		return $this->runAnyUpdate('player_oauth2_refresh_tokens');
	}

	/**
	 * isRefreshTokenRevoked
	 * @param  string  $tokenId
	 * @return boolean
	 */
	public function isRefreshTokenRevoked($tokenId){
		$this->db->select('revoked')->from('player_oauth2_refresh_tokens')->where('id', $tokenId);
		$row=$this->runOneRowArray();
		if(!empty($row)){
			if($row['revoked']==self::DB_FALSE){
				return false;
			}
		}

		return true;
	}

	/**
	 * findAndValidatePlayer
	 * @param  string $username
	 * @param  string $password
	 * @return string
	 */
	public function findAndValidatePlayer($username, $password){


        $this->load->library(['player_library']);
        $this->load->model(['player_model']);

        $_is_username_case_sensitive_checking = true;
        // OGP-27581
        $_username = $username; // from POST
        $enable_restrict_username_more_options = $this->utils->getConfig('enable_restrict_username_more_options');
        $player_id = $this->player_model->getPlayerIdByUsername($_username);
        $usernameRegDetails = [];
        $username_on_register = $this->player_library->get_username_on_register($player_id, $usernameRegDetails);
        if( empty($usernameRegDetails['username_case_insensitive']) && $enable_restrict_username_more_options){ // Case Sensitive
            if ( $username_on_register != $_username) {
                $_is_username_case_sensitive_checking = false;
            }
        } // EOF if( empty($usernameRegDetails['username_case_insensitive']) ){...


		//query player
		// $this->load->model(['player_model']);
		$player=$this->player_model->getPlayerArrayByUsername($username);

		if(!empty($player)){
			//white ip and acl
			if($player['status']==0 && empty($player['deleted_at'])){
				if (!empty($player['password'])) {
					$this->load->library('salt');
					$passDB=$this->salt->decrypt($player['password'], $this->getDeskeyOG());
					if($passDB==$password){
                        if(!$_is_username_case_sensitive_checking){
                            $this->utils->debug_log('the username case sensitive Not match in the player', $username, $password);
                            return null;
                        }
						//found and right password
						$this->utils->debug_log('found player, right password', $username);
						return $player['username'];
					}
				}
			}else{
				$this->utils->debug_log('player status is not normal or deleted');
			}
		}
		$this->utils->debug_log('not found player', $username, $password);

		return null;
	}

	/**
	 * deleteAccessToken
	 * @param string $token
	 * @param string $userId it's actually username of player
	 * @return boolean
	 */
	public function deleteAllToken($tokenId, $userId){
		$success=false;
		//search first
		$this->db->select('id')->from('player_oauth2_access_tokens')->where('id', $tokenId)->where('user_id', $userId);
		$id=$this->runOneRowOneField('id');
		$this->utils->printLastSQL();
		if(!empty($id)){
			$this->db->where('id', $id);
			$success=$this->runRealDelete('player_oauth2_access_tokens');
			if($success){
				//delete refresh token
				$this->db->where('access_token_id', $id);
				$success=$this->runRealDelete('player_oauth2_refresh_tokens');
			}
		}

		return $success;
	}

	/**
	 * retain access_token_id of player
	 * @param  string $userId it's username of player
	 * @param  string $access_token_id it's access token Id of player
	 * @return boolean
	 */
	function retainCurrentToken($user_id, $access_token_id){
		$this->db->select('id')->from('player_oauth2_access_tokens');
		$this->db->where('user_id', $user_id);
		$this->db->where('revoked', self::DB_FALSE);
		$this->db->where_not_in('id', $access_token_id);
		$rows = $this->runMultipleRow();
		$revoked = [];
		$success = false;

        if(!empty($rows)){
        	foreach ($rows as $row){
        		$revoked[] = $row->id;
        	}
        	//revoked access tokens
	        $this->db->where_in('id', $revoked)->set('revoked', self::DB_TRUE);
			$success = $this->runAnyUpdate('player_oauth2_access_tokens');

			if($success){
				//revoked refresh tokens
				$this->db->where_in('access_token_id', $revoked)->set('revoked', self::DB_TRUE);
				$success = $this->runAnyUpdate('player_oauth2_refresh_tokens');
			}
        }

        return true;
	}

	/**
	 * cancelPlayerToken
	 * @param  string $userId it's username of player
	 * @return boolean
	 */
	public function cancelPlayerTokens($username) {
		$this->db->select('id')
				 ->from('player_oauth2_access_tokens')
				 ->where('user_id', $username)
				 ->where('revoked', self::DB_FALSE);
		$access_tokens = $this->runMultipleRow();
		$access_token_ids = [];
		$success = false;

		if (!empty($access_tokens)) {
			$access_token_ids = array_column($access_tokens, 'id');

			$this->db->trans_start();
			$this->db->where_in('id', $access_token_ids)
					 ->set('revoked', self::DB_TRUE)
					 ->update('player_oauth2_access_tokens');

			$this->db->where_in('access_token_id', $access_token_ids)
					 ->set('revoked', self::DB_TRUE)
					 ->update('player_oauth2_refresh_tokens');
			$this->db->trans_complete();

			$success = $this->db->trans_status();
		}

		$this->utils->debug_log('cancelPlayerToken result', $access_tokens, $access_token_ids, $success, $username, $this->db->last_query());
		return $success;
	}

	/**
	 * cancelPlayerTokensToOtherCurrency
	 * @param  string $userId it's username of player
	 */
	public function cancelPlayerTokensToOtherCurrency($username) {
		$this->utils->debug_log(__METHOD__, $username);
		$success=false;
		$success=$this->utils->foreachMultipleDBToCIDB(function($db) use($username){
			$success = $this->cancelPlayerTokens($username);
			return $success;
		});
		return $success;
	}

	/**
	 * deleteAllTokenToOtherCurrency
	 *
	 * @param string $tokenId
	 * @param string $userId 
	 * @return boolean
	 */
	public function deleteAllTokenToOtherCurrency($tokenId, $userId){
		$this->utils->debug_log(__METHOD__, $tokenId, $userId);
		$success=false;
		if(!empty($tokenId)){
			$success=$this->utils->foreachMultipleDBToCIDB(function($db) use($tokenId, $userId){
				$success=$this->dbtransOnly(function () use($tokenId, $userId){
					$success = $this->deleteAllToken($tokenId, $userId);
					return $success;
				});
				return $success;
			});
		}
		return $success;
	}

	/**
	 * sync token to other currency
	 *
	 * @param string $tokenId
	 * @param string $userId it's username of player
	 * @return boolean
	 */
	public function syncTokenToOtherCurrency($tokenId, $userId){
		$success=false;
		$this->db->from('player_oauth2_access_tokens')->where('id', $tokenId)->where('user_id', $userId);
		$row=$this->runOneRowArray();
		if(!empty($row)){
			// $newData=[
			// 	'id'=>$data['id'],
			// 	'user_id'=>$data['user_id'],
			// 	'client_id'=>$data['client_id'],
			// 	'scopes'=>json_encode($data['scopes']),
			// 	'revoked'=>$data['revoked'] ? self::DB_TRUE : self::DB_FALSE,
			// 	'created_at'=>$data['created_at'],
			// 	'updated_at'=>$data['updated_at'],
			// 	'expires_at'=>$data['expires_at'],
			// ];

			$result=$this->foreachMultipleDB(function($db, &$result) use($row){
				// update to other db
				$succ=$this->createAccessToken($row, $db);
				$result=$succ;
				return $succ;
			}, false, [$this->utils->getActiveTargetDB()]);
			$success=!empty($result);
			if($success){
				foreach($result as $dbKey=>$rlt){
					if(!$rlt['success']){
						$success=false;
					}
				}
			}
		}
		return $success;
	}

	/**
	 * delete tokens to other currency
	 *
	 * @param string $userId it's username of player
	 * @return boolean
	 */
	public function retainCurrentTokenToOtherCurrency($user_id, $access_token_id){
		$success=false;
		$success=$this->utils->foreachMultipleDBToCIDB(function($db) use($user_id, $access_token_id){
			$success = $this->retainCurrentToken($user_id, $access_token_id);
			return $success;
		});
		return $success;
	}
}
