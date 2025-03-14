<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_uc extends Abstract_game_api {

	private $api_url;
	private $partner_id;
	private $private_key;
	private $currency;
	private $language;

	const URI_MAP = array(
		self::API_createPlayer => '/GameLauncher/GetWalletUrl',
		self::API_queryPlayerBalance => '/Funds/GetBalance',
		self::API_depositToGame => '/Funds/Transfer',
		self::API_withdrawFromGame => '/Funds/Transfer',
		self::API_queryForwardGame => '/GameLauncher/GetWalletUrl',
		self::API_syncGameRecords => '/Reporting/GetTickets'
	);

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->partner_id = $this->getSystemInfo('partnerId');
		$this->private_key = $this->getSystemInfo('privateKey');
		$this->language = $this->getSystemInfo('language');
		$this->currency = $this->getSystemInfo('currency');
	}

	public function getPlatformCode() {
		return UC_API;
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		$url = $this->api_url . $apiUri;
		return $url;
	}

	public function getHttpHeaders($params){
		return array("Content-Type" => "application/json");
	}

	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,true));
	}

	function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;
		if($resultArr['ErrorCode']==0){
			$success = true;
		}
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('UC got error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function gameAmountToDB($amount) {
		//only need 2
		return (floatval($amount)/100);
	}

	public function DBtoGameAmount($amount) {
		//only need 2
		return (floatval($amount)*100);
	}

	/**
	 * overview : get game time to server time
	 *
	 * @return string
	 */
	// function getGameTimeToServerTime() {
	// 	return '+8 hours';
	// }

	/**
	 * overview : get server time to game time
	 *
	 * @return string
	 */
	// function getServerTimeToGameTime() {
	// 	return '-8 hours';
	// }

	function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		//parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		// $playerName = $this->getGameUsernameByPlayerUsername($playerName);

		// $context = array(
		// 	'callback_obj' => $this,
		// 	'callback_method' => 'processResultForCreatePlayer',
		// 	'playerName' => $playerName
		// );
		// //SIG
		// $dateNow = date("YmdHis");//date now
		// $sig =  MD5($dateNow.$playerName.$this->private_key);
		// $params = array(
		// 	'ExternalId'=> $playerName, // Unique customer id. Must always be thesame for the same user in all sessions.
		// 	'CurrencyCode' => $this->currency,
		// 	'Name' => $playerName,
		// 	'TimeStamp' => $dateNow,
		// 	'Sig' => $sig, // The MD5 for security purpose
		// 	'GameCode' => 'SlotMachine_DemiGods', // just to create player any game
		// 	'LangCode' => $this->language,
		// 	'PartnerId' => $this->partner_id,
		// 	'PlatformId' => 1, // 1 - desktop 2 - mobile
		// );

		// return $this->callApi(self::API_createPlayer, $params, $context);

		// create player on game provider auth

		$return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$success = false;
		$message = "Unable to create Account for UC8 API";
		if($return){
			$success = true;
			$message = "Successfull create account for UC8 API";
		}

		return array("success"=>$success,"message"=>$message);

	}

	// function processResultForCreatePlayer($params){

	// 	$responseResultId = $this->getResponseResultIdFromParams($params);
	// 	$resultJsonArr = $this->getResultJsonFromParams($params);
	// 	$playerName = $this->getVariableFr`mContext($params, 'playerName');
	// 	$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
	// 	return array($success, $resultJsonArr);

	// }

	function depositToGame($userName, $amount, $transfer_secure_id=null) {

		$playerName = $this->getGameUsernameByPlayerUsername($userName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'playerName' => $playerName,
			'sbe_playerName' => $userName,
			'amount' => $amount
		);

		//SIG
		$dateNow = date("YmdHis");//date now
		$sig =  MD5($dateNow.$playerName.$this->private_key);
		$external_id = time().rand(1,1000);
		$params = array(
			'Name' => $playerName,
			'PartnerId' => $this->partner_id,
			'ExternalId' => $playerName,
			'TimeStamp' => $dateNow,
			'Sig' => $sig,
			'CurrencyCode' => $this->currency,
			'ExtTransactionId' => (int)$external_id, // 64 bit integer
			'Amount' => $this->DBtoGameAmount($amount),
			'TransactionType' => 'DEPOSIT'
		);

		return $this->callApi(self::API_depositToGame, $params, $context);

	}

	function processResultForDepositToGame($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$result = array('response_result_id' => $responseResultId);
		$success = false;

		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {
			//get current sub wallet balance
			// $playerBalance = $this->queryPlayerBalance($sbe_playerName);
			//for sub wallet
			// $afterBalance = $playerBalance['Balance'];
			$result["external_transaction_id"] = $resultArr['TransactionId'];
			// $result["currentplayerbalance"] = $afterBalance;
			// $result["userNotFound"] = false;
			$success = true;
			//update
			// $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			// if ($playerId) {
			// 	//deposit
			// 	$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());

			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
		}

		return array($success, $result);

	}

	function withdrawFromGame($userName, $amount, $transfer_secure_id=null) {

		$playerName = $this->getGameUsernameByPlayerUsername($userName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'playerName' => $playerName,
			'sbe_playerName' => $userName,
			'amount' => $amount
		);

		//SIG
		$dateNow = date("YmdHis");//date now
		$sig =  MD5($dateNow.$playerName.$this->private_key);
		$external_id = time().rand(1,1000);
		$params = array(
			'Name' => $playerName,
			'PartnerId' => $this->partner_id,
			'ExternalId' => $userName,
			'TimeStamp' => $dateNow,
			'Sig' => $sig,
			'CurrencyCode' => $this->currency,
			'ExtTransactionId' => (int)$external_id, // 64 bit integer
			'Amount' => $this->DBtoGameAmount($amount),
			'TransactionType' => 'WITHDRAW'
		);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);

	}

	function processResultForWithdrawFromGame($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$result = array('response_result_id' => $responseResultId);
		$success = false;
		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {
			//get current sub wallet balance
			// $playerBalance = $this->queryPlayerBalance($sbe_playerName);

			//for sub wallet
			// $afterBalance = $playerBalance['Balance'];
			$result["external_transaction_id"] = $resultArr['TransactionId'];
			// $result["currentplayerbalance"] = $afterBalance;
			// $result["userNotFound"] = false;
			$success = true;
			//update
			// $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			// if ($playerId) {
			// 	//withdraw
			// 	$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
			// 		$this->transTypeSubWalletToMainWallet());

			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
		} else {
			$result["userNotFound"] = true;
		}
		return array($success, $result);
	}

	function queryPlayerBalance($userName) {

		$playerName = $this->getGameUsernameByPlayerUsername($userName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName
		);
		//SIG
		$dateNow = date("YmdHis");//date now
		$sig =  MD5($dateNow.$playerName.$this->private_key);

		$params = array(
			'ExternalId'=> $playerName,
			'PartnerId' => $this->partner_id,
			'TimeStamp' => $dateNow,
			'Sig' => $sig
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);

	}

	function processResultForQueryPlayerBalance($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');

		$success = false;
		$result = array();

		if ($this->processResultBoolean($responseResultId, $resultArr,$playerName)) {

			$success = true;
			$result['balance'] = @floatval($this->gameAmountToDB($resultArr['Balance']));

			if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
				$this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
			} else {
				$this->CI->utils->debug_log('cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			}

		}
		return array($success, $result);

	}

    /*
        Current available language for UC is:
        English, Chinese
     */
    public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'zh_CN';
                break;
            // case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            //     $lang = 'id_ID';
            //     break;
            // case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            //     $lang = 'vi_VN';
            //     break;
            // case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            //     $lang = 'ko_KR';
            //     break;
            default:
                $lang = 'en_US';
                break;
        }
        return $lang;
    }

	function queryForwardGame($playerName, $extra=null) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultQueryForwardGame',
			'playerName' => $playerName
		);
		//SIG
		$dateNow = date("YmdHis");//date now
		$sig =  MD5($dateNow.$playerName.$this->private_key);
		$mode = $extra['game_mode'];
		$is_funmode = false;
		if($mode == 'fun'||$mode == 'demo'||$mode == 'trial'||$mode == false){
			$is_funmode = true;
		}

		$params = array(
			'ExternalId'=> $playerName, // Unique customer id. Must always be thesame for the same user in all sessions.
			'CurrencyCode' => $this->currency,
			'Name' => $playerName,
			'TimeStamp' => $dateNow,
			'Sig' => $sig, // The MD5 for security purpose
			'GameCode' => $extra['game_code'],
			'LangCode' => $this->getLauncherLanguage($extra['language']),
			'PartnerId' => $this->partner_id,
			'PlatformId' => $extra['is_mobile']?'2':'1', // 1 - desktop 2 - mobile
			'FunMode' => $is_funmode // boolean
		);

		return $this->callApi(self::API_queryForwardGame, $params, $context);

	}

	function processResultQueryForwardGame($params){

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
		return array($success, $resultJsonArr);
	}

	function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	function syncOriginalGameLogs($token = false) {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		//observer the date format
		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());

		$startDate=$startDate->format('YmdHis');
		$endDate=$endDate->format('YmdHis');
		$skip = 0;
		$take = 500; // max data get

		return $this->_continueSync( $startDate, $endDate, $take, $skip );

	}

	function _continueSync( $startDate, $endDate, $take = 0, $skip = 0 ){
		$return = $this->syncUCGamelogs($startDate,$endDate,$take,$skip);
		if(isset($return['count'])){
			if( $return['count'] == $take ){
				$skip += $take;
				return $this->_continueSync( $startDate, $endDate, $take, $skip );
			}
		}
		return $return;
	}


	function syncUCGamelogs($startDate,$endDate,$take,$skip){
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'startDate' => $startDate,
			'endDate' => $endDate,
			'take' => $take,
			'skip' => $skip
		);
		//SIG
		$dateNow = date("YmdHis");//date now
		$sig =  MD5($dateNow.$this->partner_id.$this->private_key);
		$params = array(
			'TimeStamp'=> $dateNow, // The exact time the request was send in UTC.
			'Sig' => $sig, // The MD5 for security purpose
			'LangCode' => $this->language,
			'PartnerId' => $this->partner_id,
			'StartDate' => $startDate,
			'EndDate' => $endDate,
			'CurrencyCode' => $this->currency,
			'Take'=>$take,
			'Skip'=>$skip,
		);

		return $this->callApi(self::API_syncGameRecords, $params, $context);
	}

	function processResultForSyncGameRecords($params) {

		$this->CI->load->model(array('uc_game_logs', 'player_model'));
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		//$result = array();
		$count = 0;
		if ($success) {
			$gameRecords = $resultArr['Data'];
			if ($gameRecords) {
				$availableRows = $this->CI->uc_game_logs->getAvailableRows($gameRecords);
				if (isset($availableRows)) {
					foreach ($availableRows as $record) {
						$record['TimeStamp'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['TimeStamp'])));
						$record['CreatedDate'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['CreatedDate'])));
						$record['BetAmount'] = $this->gameAmountToDB($record['BetAmount']);
						$record['WinAmount'] = $this->gameAmountToDB($record['WinAmount']);
						$record['ValidAmount'] = $this->gameAmountToDB($record['ValidAmount']);
						$record['Username'] = $record['ExternalId'];
						$player = $this->CI->player_model->getPlayerByUsername($record['Username']);
						$record['PlayerId'] = $player->playerId;
						$record['uniqueid'] = $record['TicketId']; //add external_uniueid for og purposes
						$record['external_uniqueid'] = $record['TicketId']; //add external_uniueid for og purposes
						$record['response_result_id'] = $responseResultId;
						$this->CI->uc_game_logs->insertGameLogs($record);
						$count++;
					}
					//$result['data'] = $availableRows[0];
				}
			}
		}

		return array($success,array('count'=>$count));
	}

	function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'uc_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');
		// $this->gameTimeToServerTime
		$this->CI->utils->debug_log('dateTimeFrom', $startDate, 'dateTimeTo', $endDate);


		$rlt = array('success' => true);
		$result = $this->CI->uc_game_logs->getGameLogStatistics($startDate, $endDate);
		$cnt = 0;

		if ($result) {

			$unknownGame = $this->getUnknownGame();

			foreach ($result as $UC_data) {
				$player_id = $UC_data->PlayerId;

				if (!$player_id) {
					continue;
				}

				$cnt++;

				$bet_amount = $UC_data->bet_amount;
				$result_amount = $UC_data->result_amount - $bet_amount;

				$game_description_id = $UC_data->game_description_id;
				$game_type_id = $UC_data->game_type_id;

				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}

				//added round no
				$extra = array('table' => $UC_data->RoundId);

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$UC_data->game_code,
					$UC_data->game_type,
					$UC_data->game,
					$player_id,
					$UC_data->Username,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$UC_data->external_uniqueid,
					$UC_data->date_created, //start
					$UC_data->date_created, //end
					$UC_data->response_result_id,
					1,
					$extra
				);

			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

	function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}


	function login($userName, $password = null) {
		return $this->returnUnimplemented();
	}

	function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}

	function syncPlayerAccount($username, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
		// return array("success" => true);
	}

	function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}

	function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();

	}

	function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
		return $this->returnUnimplemented();
	}

	function isPlayerExist($userName) {
		return $this->returnUnimplemented();
	}

}

/*end of file*/