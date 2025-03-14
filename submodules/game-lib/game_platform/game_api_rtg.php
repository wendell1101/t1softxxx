<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: RTG Transfer Wallet and Launch game API Specifications
	* Document Number: V.1.5


	*
	* @category Game_platform
	* @version 1.8.10
	* @copyright 2013-2022 tot
**/

class game_api_rtg extends Abstract_game_api {

	const POST = "POST";
	const GET = "GET";
	const PUT = "PUT";
	const START_PAGE = 1;
	const FISHING_GAME = 330;

	const MD5_FIELDS_FOR_ORIGINAL=[
		'login',
		'player_id',
		'game_number',
		'game_id',
		'machine_id',
		'bet_amount',
		'bonus_bet_amount',
		'payout',
		'date_started',
		'date_finished',
		'side_bet_jackpot_total_contribution',
		'side_bet_jackpot_payout'
	];
	const MD5_FLOAT_AMOUNT_FIELDS=[
		'bet_amount',
		'bonus_bet_amount',
		'payout',
		'side_bet_jackpot_total_contribution',
		'side_bet_jackpot_payout'
	];

	const MD5_FIELDS_FOR_MERGE=[
		'login',
		'player_id',
		'game_number',
		'game_id',
		'machine_id',
		'bet_amount',
		'bonus_bet_amount',
		'payout',
		'date_started',
		'date_finished',
		'side_bet_jackpot_total_contribution',
		'side_bet_jackpot_payout'
	];
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
		'bet_amount',
		'bonus_bet_amount',
		'payout',
		'side_bet_jackpot_total_contribution',
		'side_bet_jackpot_payout'
	];

	const ORIGINAL_LOGS_TABLE_NAME = 'rtg_game_logs';

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->currencyCode = $this->getSystemInfo('currency');
		$this->countryCode = $this->getSystemInfo('country_code');
		$this->language = $this->getSystemInfo('language');
		$this->apiKey = $this->getSystemInfo('api_key');
		$this->game_url = $this->getSystemInfo('game_launcher_url');
		$this->genericAccountingMethodId = $this->getSystemInfo('generic_accounting_method_id',10);
		$this->skinId = $this->getSystemInfo('skin_id',1);
		$this->method = "POST"; # default as POST

		$this->URI_MAP = array(
			self::API_createPlayer => '/players/external',
			self::API_queryPlayerBalance => '/players/{pid}/balance',
			self::API_isPlayerExist => '/userInfo',
			self::API_changePassword => '/accounts/set-password',
	        self::API_depositToGame => '/cashier/common-wallet-deposit',
	        self::API_withdrawFromGame => '/cashier/common-wallet-withdrawal',
	        self::API_blockPlayer => '/games/block',
	        self::API_unblockPlayer => '/games/block',
	        self::API_login => '/players/{pid}/token',
			self::API_syncGameRecords => '/reports/gaming-stats'
		);
	}

	public function getPlatformCode() {
		return RTG_API;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = $this->URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;
		if($apiUri == $this->URI_MAP[self::API_queryPlayerBalance]){
			$apiUri = str_replace("{pid}", $params['pid'], $apiUri);
			unset($params['pid']);
			$url = $this->api_url.$apiUri.'?'.http_build_query($params);
		}
		if($apiUri == $this->URI_MAP[self::API_syncGameRecords]){
			$url = $this->api_url.$apiUri.'?'.http_build_query($params);
		}
		if($apiUri == $this->URI_MAP[self::API_login]){
			$apiUri = str_replace("{pid}", $params['pid'], $apiUri);
			unset($params['pid']);
			$url = $this->api_url.$apiUri;
		}
		return $url;
	}

	protected function getHttpHeaders($params){
		if(isset($params['pid'])){
			unset($params['pid']);
		}
		switch ($this->method) {
			case self::PUT:
			case self::POST:
				$contentLenght = strlen(json_encode($params));
				break;
			case self::GET:
				$contentLenght = 0;
				break;
			
			default:
				$contentLenght = 0;
				break;
		}
		$headers = array(
			'Content-type' => 'application/json',
			'Content-Length' => $contentLenght,
			'api_key' => $this->apiKey
		);
		return $headers;
	}

	protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 410;
    }

	protected function customHttpCall($ch, $params) {
		if(isset($params['pid'])){
			unset($params['pid']);
		}
		if($this->method == self::POST){
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);     
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($params));
			// echo json_encode($params);exit;
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}
		if($this->method == self::PUT){
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
	        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($params));
		}
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;

		if($resultArr['statusCode'] < 400){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('WMG API got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

		$params = array(
			"token_type" => "external_token",
			"currency_id" => $this->currencyCode,
		    "login" => $gameUsername,
		    "first_name" => $gameUsername,
		    "last_name" => $gameUsername,
		    "password" => $password,
		    "email" => $gameUsername."@test.com",
		    "day_phone" => "12345",
		    "evening_phone" => "12345",
		    "address_1" => "12345",
		    "address_2" => "12345",
		    "city" => "",
		    "state" => "",
		    "zip" => "",
		    "country" => $this->countryCode,
		    "cell_phone" => "",
		    "sms_message" => false,
		    "gender" => true,
		    "ip_address" => $this->CI->input->ip_address(),
            "mac_address" => "",
            "user_id" => 0,
            "download_id" => 0,
		    "birth_date" => "1980-08-03T09:37:07.085Z",
		    "called_from_casino" => 0,
		    "skin_id" => $this->skinId,
		   	"no_spam" => true
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $params, $gameUsername);

		$result = array(
			"player" => $gameUsername,
			"exists" => false
		);

		if($success){
			$pid = isset($resultArr['pid'])?$resultArr['pid']:null;
			//update external AccountID 
			$this->updateExternalAccountIdForPlayer($playerId,$pid);
			# update flag to registered = truer
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE); 
	        $result["exists"] = true;
		}

		return array($success, $result);
	}

	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerID = $this->getExternalAccountIdByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance', 
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$params = array(
			'pid' => $playerID,
			'forMoney' => 'true',
		);

		$this->method = self::GET;

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params, $gameUsername);
		$result = array();
		if($success){
			$result['balance'] = @floatval($resultArr[0]['balance']);
		}

		return array($success, $result);

	}

	public function isPlayerExist($playerName){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerID = $this->getExternalAccountIdByPlayerUsername($playerName);
		if($playerID){
			$return = array("success"=>true,"exists"=>true);
	    }else{
	    	$return = array("success"=>true,"exists"=>false);
	    }

	    return $return;
    }

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerID = $this->getExternalAccountIdByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword', 
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'newPassword' => $newPassword
		);

		$params = array(
			'player' => array(
				'player_id' => $playerID
			),
			'password' => $newPassword
		);

		$this->method = self::POST;

		return $this->callApi(self::API_changePassword, $params, $context);
	}

	public function processResultForChangePassword($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$newPassword = $this->getVariableFromContext($params, 'newPassword');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params, $gameUsername);
		$result = array();

		if($success){
			$playerId = $this->getPlayerIdInPlayer($playerName);
			if ($playerId) {
				$result["password"] = $newPassword;
				//sync password to game_provider_auth
				$this->updatePasswordForPlayer($playerId,$newPassword);
			} else {
				$this->CI->utils->debug_log('cannot find player', $playerName);
			}
		}

		return array($success, $result);
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {

        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }

        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);

    }


	public function depositToGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerID = $this->getExternalAccountIdByPlayerUsername($playerName);
		$external_transaction_id = 'DEP'.$gameUsername.time(); 

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            // 'external_transaction_id' => $external_transaction_id
        );

		$params = array( 
			"player_id"      => $playerID,
		    "method_id"	=> $this->genericAccountingMethodId,
		    "amount"	=> $amount,
		    "tracking_one"	=> $external_transaction_id,
		    "tracking_two"	=> "",
		    "tracking_three"	=> "",
		    "tracking_four"	=> "",
		    "session_id"	=> 0,
		    "user_id"	=> 0,
		    "skin_id"	=> $this->skinId,
		    "depositor"	=> "player"
		);

		$this->method = self::POST;

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		// $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params,$playerName);
		$statusCode = $this->getStatusCodeFromParams($params);

		$result = array(
			'response_result_id' => $responseResultId,
			// 'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {			
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id']=$this->getReasons($statusCode);
        }

        return array($success, $result);

	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerID = $this->getExternalAccountIdByPlayerUsername($playerName);
		$external_transaction_id = 'DEP'.$gameUsername.time(); 

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
        );

		$params = array( 
			"player_id" => $playerID,
		    "method_id"	=> $this->genericAccountingMethodId,
		    "amount"	=> $amount,
		    "tracking_one"	=> $external_transaction_id,
		    "tracking_two"	=> "",
		    "tracking_three"=> "",
		    "tracking_four"	=> "",
		    "session_id"	=> 0,
		    "user_id"	=> 0,
		    "skin_id"	=> $this->skinId,
		    "depositor"	=> "player"
		);

		$this->method = self::POST;

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params,$playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs']=true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id']=$this->getReasons($statusCode);
        }

        return array($success, $result);
	}

	private function getReasons($statusCode){
        switch ($statusCode) {
            case 404:
                return self::REASON_NOT_FOUND_PLAYER;
                break;
            case 400:
                return self::REASON_INCOMPLETE_INFORMATION;
                break;
            case 401:
                return self::REASON_IP_NOT_AUTHORIZED;
                break;
            case 409:
                return self::REASON_INVALID_TRANSFER_AMOUNT;
                break;
            case 202:
                return self::REASON_TRANSACTION_PENDING;
                break;
            case 417:
                return self::REASON_TRANSACTION_DENIED;
                break;
            default:
                return self::REASON_UNKNOWN;
                break;
        }
	}

	public function blockPlayer($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerID = $this->getExternalAccountIdByPlayerUsername($playerName);	

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForBlockPlayer',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        );

		$params = array( 
			"players" => array(
				"players" => $playerID
			),
			"lock_type" => 0,
			"transaction_type" => "block"
		);

		$this->method = self::POST;

		return $this->callApi(self::API_blockPlayer, $params, $context);

    }

    public function processResultForBlockPlayer($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params,$playerName);
		$result = array();
    	if ($success) {
			$this->blockUsernameInDB($gameUsername);
		}

		return array($success, $result);
    }

    public function unblockPlayer($playerName) {
    	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerID = $this->getExternalAccountIdByPlayerUsername($playerName);	

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForUnblockPlayer',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        );

		$params = array( 
			"players" => array(
				"players" => $playerID
			),
			"lock_type" => 0,
			"transaction_type" => "unblock"
		);

		$this->method = self::POST;

		return $this->callApi(self::API_blockPlayer, $params, $context);
    }

    public function processResultForUnblockPlayer($params){
    	$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params,$playerName);
		$result = array();
    	if ($success) {
        	$this->unblockUsernameInDB($gameUsername);
		}

		return array($success, $result);

    }

    public function login($playerName, $password = null, $extra = null) {
    	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerID = $this->getExternalAccountIdByPlayerUsername($playerName);	

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
        );

		$params = array(
			'pid' => $playerID,
			'token_type' => 'external_token'
		);

		$this->method = self::POST;

    	return $this->callApi(self::API_login, $params, $context);
	}

	public function processResultForLogin($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params,$playerName);
		$result = array();

		if($success){
			$result['token'] = $resultArr;
		}

		return array($success, $result);
	}	

	public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
        	case 1:
            case 'en-us':
                $lang = 1; // english
                break;
            case 2:
            case 'zh-cn':
                $lang = 2; // chinese
                break;
            case '3':
            case 'id-id':
                $lang = 1; // indonesia
                break;
            case '4':
            case 'vi-vn':
                $lang = 5; // vietnamese
                break;
            case '5':
            case 'ko-kr':
                $lang = 6; // korean
                break;
            default:
                $lang = 1; // default as english
                break;
        }
        return $lang;
    }

	public function queryForwardGame($playerName, $extra = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$login = $this->login($playerName);
		$dynamicDomain=$this->getCurrentProtocol().'://'.$this->getCurrentDomain();
		if($login["success"]){
			$params = array(
				'cdkModule' => 'gameLauncher',
				'skinid' => $this->getLauncherLanguage($extra['language']),
				'user' => $gameUsername,
				'forReal' => $extra['is_demo_flag'],
				'token' => $login['token'],
				'gameId' => $extra['game_code'],
				'machId' => $extra['machid'],
				'betDenomination' => 0,
				'numOfHands' => 0,
				'width' => 'auto',
				'height' => 'auto',
				'returnurl' => $dynamicDomain,
			);
			$url = $this->game_url.'?'.http_build_query($params);
			return array("success"=>$login["success"],"url"=>$url);
		}else{
			return array("success"=>"false","url"=>null);
		}

	}

	// GetGamingStats
	//
	// Returns a report of gaming stats for all players for a 15 day date range, starting
	//   from the start date passed in.
	//
	// Args:
	//   forMoney   - Set to true to retrieve stats for real money games, false for
	//                   fun mode games.
	//   startDate  - Starting date of the report - 15 days worth of data starting with
	//                 this date/time range will be displayed
	//   endDate    - Pass in this date time to reduce length of the report.  Anything
	//                 more than 15 days past the start date will be ignored.
	//                 
	public function syncOriginalGameLogs($token = false) {

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs',
			'startDate' => $startDate,
			'endDate' => $endDate
		);

		$params = array(
			'forMoney' => 'true',
			'startDate' => $startDate->format("Y-m-d H:i:s"),
			'endDate' => $endDate->format("Y-m-d H:i:s")
		);

		$this->method = self::GET;

		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	public function processGameRecords(&$gameRecords,$responseResultId){
		foreach($gameRecords as $index => $record) {
				$gameRecords[$index]['gameid_machineid'] = isset($record['game_number']) ? $record['game_id'].$record['machine_id']: NULL;
				//extra info from SBE
				$gameRecords[$index]['uniqueid'] = isset($record['game_number']) ? $record['game_number'].'-'.$record['game_id'].$record['machine_id']: NULL;
				$gameRecords[$index]['external_uniqueid'] = isset($record['game_number']) ? $record['game_number'].'-'.$record['game_id'].$record['machine_id'].$record['player_id'].$record['session_id']: NULL;
				$gameRecords[$index]['response_result_id'] = $responseResultId;
		}
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
            	$row['login'] = isset($record['login'])?$record['login']:null;
				$row['player_id'] = isset($record['player_id'])?$record['player_id']:null;
				$row['session_id'] = isset($record['session_id'])?$record['session_id']:null;
				$row['date_started'] = isset($record['date_started'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['date_started']))):null;
				$row['date_finished'] = isset($record['date_finished'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['date_finished']))):null;
				$row['game_number'] = isset($record['game_number'])?$record['game_number']:null;
				$row['game_name'] = isset($record['game_name'])?$record['game_name']:null;
				$row['machine_name'] = isset($record['machine_name'])?$record['machine_name']:null;
				$row['gameid_machineid'] = isset($record['gameid_machineid'])?$record['gameid_machineid']:null;
				$row['game_id'] = isset($record['game_id'])?$record['game_id']:null;
				$row['machine_id'] = isset($record['machine_id'])?$record['machine_id']:null;
				$row['bet_amount'] = isset($record['bet_amount'])?$record['bet_amount']:null;
				$row['bet_amount_featured_guarantee'] = isset($record['bet_amount_featured_guarantee'])?$record['bet_amount_featured_guarantee']:null;
				$row['bet_description'] = isset($record['bet_description'])?$record['bet_description']:null;
				$row['payout'] = isset($record['payout'])?$record['payout']:null;
				$row['jackpot_contribution_mini'] = isset($record['jackpot_contribution_mini'])?$record['jackpot_contribution_mini']:null;
				$row['jackpot_contribution_minor'] = isset($record['jackpot_contribution_minor'])?$record['jackpot_contribution_minor']:null;
				$row['jackpot_contribution_major'] = isset($record['jackpot_contribution_major'])?$record['jackpot_contribution_major']:null;
				$row['jackpot_win_mini'] = isset($record['jackpot_win_mini'])?$record['jackpot_win_mini']:null;
				$row['jackpot_win_minor'] = isset($record['jackpot_win_minor'])?$record['jackpot_win_minor']:null;
				$row['jackpot_win_major'] = isset($record['jackpot_win_major'])?$record['jackpot_win_major']:null;
				$row['game_mode'] = isset($record['game_mode'])?$record['game_mode']:null;
				$row['currency_code'] = isset($record['currency_code'])?$record['currency_code']:null;
				$row['currency_symbol'] = isset($record['currency_symbol'])?$record['currency_symbol']:null;
				$row['session_machine_name'] = isset($record['session_machine_name'])?$record['session_machine_name']:null;
				$row['seamless_reference_id'] = isset($record['seamless_reference_id'])?$record['seamless_reference_id']:null;
				$row['balance_before'] = isset($record['balance_before'])?$record['balance_before']:null;
				$row['balance_after'] = isset($record['balance_after'])?$record['balance_after']:null;
				$row['bet_ip_address'] = isset($record['bet_ip_address'])?$record['bet_ip_address']:null;
				$row['jackpot_type'] = isset($record['jackpot_type'])?$record['jackpot_type']:null;
				$row['client_type'] = isset($record['client_type'])?$record['client_type']:null;
				$row['bonus_bet_amount'] = isset($record['bonus_bet_amount'])?$record['bonus_bet_amount']:null;

				// added new column
				$row['jackpot_contribution_maxi'] = isset($record['jackpot_contribution_maxi'])?$record['jackpot_contribution_maxi']:null;
				$row['jackpot_contribution_rdm'] = isset($record['jackpot_contribution_rdm'])?$record['jackpot_contribution_rdm']:null;
				$row['jackpot_contribution_ssd'] = isset($record['jackpot_contribution_ssd'])?$record['jackpot_contribution_ssd']:null;
				$row['jackpot_win_grand'] = isset($record['jackpot_win_grand'])?$record['jackpot_win_grand']:null;
				$row['jackpot_win_maxi'] = isset($record['jackpot_win_maxi'])?$record['jackpot_win_maxi']:null;
				$row['jackpot_win_rdm'] = isset($record['jackpot_win_rdm'])?$record['jackpot_win_rdm']:null;
				$row['jackpot_win_ssd'] = isset($record['jackpot_win_ssd'])?$record['jackpot_win_ssd']:null;
				$row['insurance_amount'] = isset($record['insurance_amount'])?$record['insurance_amount']:null;
				$row['valid_bet'] = isset($record['valid_bet'])?$record['valid_bet']:0;
				$row['side_bet_jackpot_total_contribution'] = isset($record['side_bet_jackpot_total_contribution'])?$record['side_bet_jackpot_total_contribution']:null;
				$row['side_bet_jackpot_payout'] = isset($record['side_bet_jackpot_payout'])?$record['side_bet_jackpot_payout']:null;
				$row['side_bet_jackpot_game_id'] = isset($record['side_bet_jackpot_game_id'])?$record['side_bet_jackpot_game_id']:null;

				//extra info from SBE
				$row['uniqueid'] = isset($record['uniqueid']) ? $record['uniqueid']: NULL;
				$row['external_uniqueid'] = isset($record['external_uniqueid']) ? $record['external_uniqueid']: NULL;
				$row['response_result_id'] = $record['response_result_id'];
				$row['md5_sum'] = $record['md5_sum'];
                if ($queryType == 'update') {
                	$row['updated_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
                	$row['id'] = $record['id'];
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $row);
                } else {
                	$row['created_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $row);
                }
                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

	public function processResultForSyncOriginalGameLogs($params) {
		$this->CI->load->model(array('rtg_game_logs', 'external_system','original_game_logs_model'));
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$resultArr = $this->getResultJsonFromParams($params);

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $params);

		$result = array(
			'data_count'=> 0
		);
		if($success){
			$gameRecords = $resultArr;
			$this->processGameRecords($gameRecords,$responseResultId);	
			list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
				self::ORIGINAL_LOGS_TABLE_NAME,		# original table logs
				$gameRecords,						# api record (format array)
				'uniqueid',							# unique field in api
				'external_uniqueid',				# unique field in rtg_game_logs table
				self::MD5_FIELDS_FOR_ORIGINAL,
				'md5_sum',
				'id',
				self::MD5_FLOAT_AMOUNT_FIELDS
			);
			$this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

			if (!empty($insertRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
			}
			unset($insertRows);

			if (!empty($updateRows)) {
				$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
			}
			unset($updateRows);
		}

		return array($success, $result);
	}

	public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
		$sqlTime='rtg.date_finished >= ? and rtg.date_finished <= ?';
		if($use_bet_time){
		#	$sqlTime='rtg.date_started >= ? and rtg.date_started <= ?';
		}

		$sql = <<<EOD
SELECT
rtg.id as sync_index,
rtg.id,
rtg.login,
rtg.response_result_id,
rtg.gameid_machineid as machine_id,
rtg.gameid_machineid as game_id,
rtg.gameid_machineid as game_code,
rtg.game_name as game_type,
rtg.machine_name as game_name,
rtg.bet_amount,
rtg.bet_amount as real_bet_amount,
rtg.balance_after as after_balance,
rtg.payout,
rtg.payout AS result_amount,
rtg.game_number,
rtg.game_number AS round_id,
rtg.balance_after AS after_balance,
rtg.date_started,
rtg.date_finished,
rtg.external_uniqueid,
rtg.response_result_id,
rtg.bonus_bet_amount,
rtg.side_bet_jackpot_total_contribution,
rtg.side_bet_jackpot_payout,
rtg.md5_sum,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM rtg_game_logs as rtg

left JOIN game_description as gd ON rtg.gameid_machineid = gd.external_game_id and gd.game_platform_id=?
JOIN game_provider_auth ON rtg.login = game_provider_auth.login_name and game_provider_auth.game_provider_id=?


WHERE

{$sqlTime}

EOD;

		$params=[$this->getPlatformCode(), $this->getPlatformCode(), $dateFrom,$dateTo];

		$result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

		return $result;
	}

	public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

		$extra_info=[];

		if(empty($row['md5_sum'])){
			$row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
					self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

		$bet_details = [];
		if($row['side_bet_jackpot_total_contribution'] || $row['side_bet_jackpot_payout']) {
			$bet_details = [
				'side_bet_jackpot_total_contribution' => $row['side_bet_jackpot_total_contribution'],
				'side_bet_jackpot_payout' => $row['side_bet_jackpot_payout'],
			];
		}
		$data = [
			'game_info'=>['game_type_id'=>$row['game_type_id'], 'game_description_id'=>$row['game_description_id'],
				'game_code'=>$row['game_code'], 'game_type'=>null, 'game'=>$row['game_code']],
			'player_info'=>['player_id'=>$row['player_id'], 'player_username'=>$row['login']],
			'amount_info'=>['bet_amount'=>$row['bet_amount'], 'result_amount'=>$row['result_amount'],
				'bet_for_cashback'=>$row['bet_amount'], 'real_betting_amount'=>$row['real_bet_amount'],
				'win_amount'=>null, 'loss_amount'=>null, 'after_balance'=>$row['after_balance']],
			'date_info'=>['start_at'=>$row['date_started'], 'end_at'=>$row['date_finished'], 'bet_at'=>$row['date_started'],
				'updated_at'=>$row['date_finished']],
			'flag'=>Game_logs::FLAG_GAME,
			'status'=>$row['status'],
			'additional_info'=>['has_both_side'=>0, 'external_uniqueid'=>$row['external_uniqueid'], 'round_number'=>$row['round_id'],
				'md5_sum'=>$row['md5_sum'], 'response_result_id'=>$row['response_result_id'], 'sync_index'=>$row['sync_index'],
				'bet_type'=>null ],
			'bet_details'=> $bet_details,
				'extra'=>$extra_info,
			//from exists game logs
			'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
			'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
		];

		return $data;
	}

	public function preprocessOriginalRowForGameLogs(array &$row){
		$game_description_id = $row['game_description_id'];
		$game_type_id = $row['game_type_id'];
		if (empty($game_description_id)) {
			list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
		}
		$row['game_description_id']=$game_description_id;
		$row['game_type_id']=$game_type_id;

		$row['status'] = Game_logs::STATUS_SETTLED;

		$bet_amount = $row['bet_amount'];
		$win_amount =  $row['result_amount'];
		$side_bet_jackpot_payout = $row['side_bet_jackpot_payout'];
		$side_bet_jackpot_total_contribution = $row['side_bet_jackpot_total_contribution'];

		if($row['game_id'] == self::FISHING_GAME){
			$bet_amount = $row['bet_amount'] + $row['bonus_bet_amount'];
			$row['result_amount'] = (float)$win_amount - (float)$bet_amount;
		} else {
			// side_bet_jackpot_payout can find in Real-Series Video Slots game
			// from original payout =(payout - side_bet_jackpot_payout) -  bet_amount

			// include side bet total contribution
			$row['result_amount'] = ((float)$side_bet_jackpot_total_contribution-(float)$side_bet_jackpot_payout) - ((float)$bet_amount-(float)$win_amount);
			$bet_amount = $bet_amount - $side_bet_jackpot_total_contribution;
		}
		$row['bet_amount'] = $bet_amount;
	}

	public function syncMergeToGameLogs($token) {
		$enabled_game_logs_unsettle = false;
		return $this->commonSyncMergeToGameLogs($token,
			$this,
			[$this, 'queryOriginalGameLogs'],
			[$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
			[$this, 'preprocessOriginalRowForGameLogs'],
			$enabled_game_logs_unsettle);
	}

	private function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;

		$external_game_id = $row['game_code'];
		$extra = array('game_code' => $row['game_code']);

		$game_type_id = $unknownGame->game_type_id;
		$game_type = $unknownGame->game_name;

		return $this->processUnknownGame(
				$game_description_id, $game_type_id,
				$external_game_id, $game_type, $external_game_id, $extra,
				$unknownGame);
	}

	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function syncPlayerAccount($playerName, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}

	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	public function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();
	}

	public function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

}

/*end of file*/