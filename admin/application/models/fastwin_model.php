<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/base_model.php';

/**
 * General behaviors include :
 *
 * * Get player details
 * * Get/insert/update/ players
 * * Check active player
 * * Get player total bets
 * * Check referrals
 * * Get sub wallet accounts
 * * Deposit history
 * * Get pending balance
 * * Update total deposit/total betting amount
 *
 * @category Player Management
 * @version 1.8.10
 * @copyright 2013-2024 tot
 */
class Fastwin_model extends BaseModel {

    const REGISTERED_BY_IMPORTER = 'importer';

	public function __construct() {
		parent::__construct();

	}

	/**
	 * Import fastwin player from external source.
	 * Updated 20241025 for function import fastwin
	 *
	 * @param int		$externalId
	 * @param int		$levelId
	 * @param string	$username
	 * @param string	$password
	 * @param double 	$balance
	 * @param array		$extra
	 * @param string	$details
	 * @param string	$message
	 * @param object	$group_level
	 * @param object	$wallet_model,
	 * @param object	$player_model
	 * @param object	$$http_request
     * @param array     $playerDetailsExtra
     * @param object    $player_library
     * @param object    $player_preference
	 * @return int
	 */
	public function importPlayer( $externalId // #1
                                , $levelId // #2
                                , $username // #3
                                , $password // #4
                                , $balance // #5
                                , $extra = null // #6
                                , $details = null // #7
                                , &$message = null // #8
                                , $group_level = null // #9
                                , $wallet_model // #10
                                , $player_model // #11
                                , $http_request // #12
                                , $playerDetailsExtra // #13
                                , $player_library // #14
                                , $player_preference // #15
    ) {

		$this->load->library(array('salt'));

		if (empty($username)) {
			$message = "Empty username: [$username]";
            $this->utils->debug_log('invalid import', $message);
			return false;
		}

        $username_on_register = $username;
        $username = strtolower($username);
        $usernameExist = $player_model->checkUsernameExist($username);
        if($usernameExist){
            $message = "Duplicate username: [$username]";
            $this->utils->debug_log('invalid import', $message);
            return false;
        }

		if(empty($password)){
			$password='';
		}

        $contactNumber = !empty($details['contactNumber']) ? $details['contactNumber'] : null;
        if(!empty($contactNumber)){
            $contactExist = $player_model->getDuplicateContactNumberUser($contactNumber);
            if(!empty($contactExist)){
                $message = "Duplicate contactNumber: [$contactNumber]";
                $this->utils->debug_log('invalid import', $message, $contactExist);
                return false;
            }
        }

		# Basic player fields
		$data = array(
			// 'playerId' => $externalId,
			'username' => $username,
			'gameName' => $username,
			'password' => empty($password) ? '' : $this->salt->encrypt($password, $this->getDeskeyOG()),
			'active' => Player_model::OLD_STATUS_ACTIVE,
			'blocked' => Player_model::OLD_STATUS_ACTIVE,
			'status' => Player_model::OLD_STATUS_ACTIVE, // active:0, inactive:1
			'registered_by' => Player_model::REGISTERED_BY_IMPORTER,
			'enabled_withdrawal' => Player_model::DB_TRUE,
			'levelId' => $levelId,
			'external_id' => $externalId,
			'codepass' => $password,
		);

		$data = array_merge($data, $extra);

        // for $isBlocked
		$inBlockedStatusList =[];
		$inBlockedStatusList[] = Player_model::BLOCK_STATUS;
		$inBlockedStatusList[] = Player_model::SUSPENDED_STATUS;
        if( in_array( $data['blocked'], $inBlockedStatusList) ){
            $data['blocked_status_last_update'] = $this->utils->getNowForMysql();
        }

        $playerId = null;
        if( is_numeric($externalId) ){
            $this->db->select('playerId')->from('player')->where('playerId', $externalId);
            $playerId = $this->runOneRowOneField('playerId');
        }

		$this->db->select('playerId')->from('player')->where('username', $username);
		$playerId2 = $this->runOneRowOneField('playerId');
		// duplicate remedy
		if(empty($playerId)){
			if(!empty($playerId2)){
				$playerId = $playerId2;
			}
		}

		$exists=false; // before import, is exists?
		# Create / Update the player record
		if (!empty($playerId)) {
			$exists=true;
			$this->db->set($data)->where('playerId', $playerId)->update('player');
		} else {
			$exists=false;
			$this->db->set($data)->insert('player');
			$playerId = $this->db->insert_id();
		}

        # Save username_on_register in Player preference
        $player_preference->storeUsernameOnRegister($username_on_register, $playerId);

		# Player level
		$group_level->adjustPlayerLevel($playerId, $levelId);

		# Playerdetails fields
		$this->db->select('playerDetailsId')->from('playerdetails')->where('playerId', $playerId);
		$playerDetailsId = $this->runOneRowOneField('playerDetailsId');
		$data = $details;
		$data['playerId'] = $playerId;
		if (!empty($playerDetailsId)) {
			$this->db->set($data)->where('playerDetailsId', $playerDetailsId)->update('playerdetails');
		} else {
			$this->db->set($data)->insert('playerdetails');
		}

		$lastActivityTime = (!empty($extra['lastLogoutTime'])) ? $extra['lastLogoutTime'] : '';
		$lastLogoutTime = (!empty($extra['lastLogoutTime'])) ? $extra['lastLogoutTime'] : '';
		$lastLoginTime = (!empty($extra['lastLoginTime'])) ? $extra['lastLoginTime'] : '';
		$lastLoginIp =  (!empty($extra['lastLoginIp'])) ? $extra['lastLoginIp'] : '';
		$lastLogoutIp = (!empty($extra['lastLogoutIp'])) ? $extra['lastLogoutIp'] : '';

		$this->utils->debug_log('player_runtime_data'.$playerId, $lastActivityTime, $lastLogoutTime, $lastLoginTime, $lastLoginIp, $lastLogoutIp);
		$player_model->updateLastActivity($playerId, $lastActivityTime, $lastLoginTime, $lastLogoutTime, $lastLoginIp, $lastLogoutIp);

        if(!empty($playerDetailsExtra)){
            $this->utils->debug_log('player_details_extra_data',$playerDetailsExtra);
            $player_model->updatePlayerDetailsExtra($playerId, $playerDetailsExtra);
        }

		if(!$exists){
			if(!empty($extra['lastLoginIp'])){
				$http_request_data=[
					'playerId'=>$playerId,
					'ip'=>$extra['lastLoginIp'],
					'createdat'=>$extra['createdOn'],
					'type'=>Http_request::TYPE_LAST_LOGIN, //login
				];

				$http_request_id=$http_request->insertHttpRequest($http_request_data);
			}
			if(!empty($details['registrationIP'])){
				$http_request_data=[
					'playerId'=>$playerId,
					'ip'=>$details['registrationIP'],
					'createdat'=>$extra['createdOn'],
					'type'=>Http_request::TYPE_REGISTRATION, //reg
				];

				$http_request_id=$http_request->insertHttpRequest($http_request_data);
			}

			if(!empty($extra['lastLoginIp'])){

				$last_request=[
					'player_id'=>$playerId,
					'ip'=>$extra['lastLoginIp'],
					'last_datetime'=>$extra['createdOn'],
					'http_request_id'=>$http_request_id,
				];

				$http_request->insertData('player_ip_last_request', $last_request);
			}else{
				//convert ip to http_request and player_ip_last_request
				if(!empty($details['registrationIP'])){

					$last_request=[
						'player_id'=>$playerId,
						'ip'=>$details['registrationIP'],
						'last_datetime'=>$extra['createdOn'],
						'http_request_id'=>$http_request_id,
					];

					$http_request->insertData('player_ip_last_request', $last_request);
				}


			}//else
        }

        //admin is 1
        $notes='import player '.$username.', balance: '.$balance.', level: '.$levelId.' at '.$this->utils->getNowForMysql();
        $player_model->addPlayerNote($playerId, 1, $notes);

        $currency = $player_model->getActiveCurrencyCode();

        $success = $this->lockAndTransForPlayerBalance($playerId, function()
        use($wallet_model, $playerId, $balance, $currency, $username, $player_model, $exists) {

            $playeraccount = array(
                'playerId' => $playerId,
                'currency' =>  $player_model->getActiveCurrencyCode(),
                'type' => Wallet_model::TYPE_MAINWALLET,
                'typeOfPlayer' => Wallet_model::TYPE_OF_PLAYER_REAL,
                'totalBalanceAmount' => $balance,
                'typeId' => Wallet_model::MAIN_WALLET_ID ,
                'status' => Player_model::DEFAULT_PLAYERACCOUNT_STATUS
            );
            if( ! $exists){
                $player_model->insertData('playeraccount',  $playeraccount);
            }else{
                $this->db->set($playeraccount)->where('playerId', $playerId)->update('playeraccount');
            }

            $wallet_model->syncAllWallet($playerId, $balance, $currency);
            $wallet_model->moveAllToRealOnMainWallet($playerId);
            return true;
        });

        $player_library->syncPlayerCurrentToMDBWithLock($playerId, $username, false);

		return $playerId;
	}// EOF importPlayer

}


