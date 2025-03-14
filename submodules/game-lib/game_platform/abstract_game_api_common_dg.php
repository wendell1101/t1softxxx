<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/******************************
	API NAME: DreamGame Game API Interface
	API version: [ v 1.0.9 ]

	Sample Extra Info:
	{
	    "limit_group": "A",
	    "language": "en",
	    "win_limit": 0,
	    "currency": "CNY",
	    "agent_name": "DGTE010140",
	    "api_key": "3b2bb255601b43a1abf569ef18f96f81",
	    "prefix_for_username": "t1",
	    "adjust_datetime_minutes": 10
	}
*******************************/

abstract class Abstract_game_api_dg_common extends Abstract_game_api {

	protected $apiReportUrl;
	public $agent_name;
	public $api_key;

	const API_TransferCredit ='TransferCredit';
	const API_MarkGotBetList ='MarkGotBetList';
	const API_SyncMissingGameLogs = "syncMissingGameLogs";
	const API_getTipGift = "getTipGift";
	
	const TRANSFER_IN = 'IN';
	const TRANSFER_OUT = 'OUT';
	const PLAYER_ENABLED = 1;
	const PLAYER_DISABLED = 0;

	const GAMETYPE_GAME = 1;
	const GAMETYPE_GIFTTIP = 2;
	const GAMEID_MEMBER_SEND_GIFT = 1;
	const GAMEID_MEMBER_GET_GIFT = 2;
	const GAMEID_SEND_TIPS = 3;
	const GAMEID_COMPANY_SEND_GIFT = 4;
	const GAMEID_BO_BING = 5;

	const ERROR_DUPLICATE_PLAYER = 116;

	const URI_MAP = array(
		self::API_createPlayer => '/user/signup/',
		self::API_isPlayerExist => '/user/getBalance/',
		self::API_queryPlayerBalance => '/user/getBalance/',
		self::API_TransferCredit => '/account/transfer/',
		self::API_depositToGame => '/account/transfer/',
		self::API_withdrawFromGame => '/account/transfer/',
		self::API_queryTransaction => '/account/checkTransfer/',
		self::API_changePassword => '/user/update/',
		self::API_blockPlayer => '/user/update/',
		self::API_unblockPlayer => '/user/update/',
		self::API_login => '/user/login/',
		self::API_syncGameRecords => '/game/getReport/',
		self::API_MarkGotBetList => '/game/markReport/',
		self::API_updatePlayerInfo => '/game/updateLimit/',
		self::API_SyncMissingGameLogs => "/game/getReport/",
		self::API_getTipGift => "/game/getTipGift/",
		self::API_setMemberBetSetting => '/user/login/',
	);

    /** redundant/duplicate keys has been removed */
    const BET_DETAILS = [
        /** baccarat */
        'banker'          => 'Banker Bet Points',
        'banker6'         => 'No Comm Banker Bet Points',
        'player'          => 'Player Bet Points',
        'tie'             => 'Tie Bet Points',
        'bPair'           => 'Banker Pair Bet Points',
        'pPair'           => 'Player Pair Bet Points',
        'big'             => 'Big Bet Points',
        'small'           => 'Small Bet Points',
        'bBX'             => 'Banker Insurance bet amount',
        'pBX'             => 'Player Insurance bet amount',
        'super6'          => 'super6 Insurance bet amount',
        'anyPair'         => 'Any pair',
        'perfectPair'     => 'Perfect pair',
        'bBonus'	  	  => 'Banker Dragon Bonus',
        'pBonus'	  	  => 'Player Dragon Bonus',

         /** dragon */
        'dragon'          => 'Dragon Bet Points',
        'tiger'           => 'Tiger Bet Points',
        'dragonRed'       => 'Dragon Red Bet Points',
        'dragonBlack'     => 'Dragon Black Bet Points',
        'tigerRed'        => 'Tiger Red Bet Points',
        'tigerBlack'      => 'Tiger Black Bet Points',
        'dragonOdd'       => 'Dragon Odd Bet Points',
        'tigerOdd'        => 'Tiger Odd Bet Points',
        'dragonEven'      => 'Dragon Even Bet Points',
        'tigerEven'       => 'Tiger Even Bet Points',
        /** roulette */
        'direct'          =>  'Direct <Number,Bet Points>',
        'separate'        =>  'Separate <Number,Bet Points>',
        'street'          =>  'Street <Number,Bet Points>',
        'angle'           =>  'Triangle <Number,Bet Points>',
        'line'            =>  'Line <Number,Bet Points>',
        'three'           =>  'Three Numbers <Number,Bet Points>',
        'four'            =>  'Four Numbers',
        'firstRow'        =>  'Row 1 Bet Points',
        'sndRow'          =>  'Row 2 Bet Points',
        'thrRow'          =>  'Row 3 Bet Points',
        'firstCol'        =>  'Col 1 Bet Points',
        'sndCol'          =>  'Col 2 Bet Points',
        'thrCol'          =>  'Col 3 Bet Points',
        'red'             =>  'Red Bet Points',
        'black'           =>  'Black Bet Points',
        'odd'             =>  'Odd Bet Points',
        'even'            =>  'Even Bet Points',
        'low'             =>  'Low Bet Points',
        'high'            =>  'Hige Bet Points',
        /** sicbo */
        'allDices'        =>  'Any Triple Bet Points',
        'threeForces'     =>  'Three Forces <Number,Bet Points>',
        'nineWayGards'    =>  'Nine Way Gards <Number,Bet Points>',
        'pairs'           =>  'Pairs <Number,Bet Points>',
        'surroundDices'   =>  'Specific Triples <Number,Bet Points>',
        'points'          =>  'Points <Number,Bet Points>',
        /** Bull */
        'player1Double'   =>   'Player 1 Double Bet Points',
        'player2Double'   =>   'Player 2 Double Bet Points',
        'player3Double'   =>   'Player 3 Double Bet Points',
        'player1Equal'    =>   'Player 1 Equal Bet Points',
        'player2Equal'    =>   'Player 2 Equal Bet Points',
        'player3Equal'    =>   'Player 3 Equal Bet Points',
        'player1Many'     =>   'Player 1 Many Bet Points',
        'player2Many'     =>   'Player 2 Many Bet Points',
        'player3Many'     =>   'Player 3 Many Bet Points',
        /** CasinoHold'em */
        'bonus'           => 'Bouns Bet Points',
        'ante'            => 'Ante Bet Points',
        'bid'             => 'Bid Bet Points',
        'hasBid'          => '1:yes other: no',
        /** LIVE LUCKY 5 **/
        'big'             => 'TotalSum big',
        'small'           => 'TotalSum small',
        'odd'             => 'TotalSum odd',
        'even'            => 'TotalSum even',
        'dragon'          => 'TotalSum dragon',
        'tiger'           => 'TotalSum tiger',
        'tie'             => 'TotalSum tie',
        'three'           => 'TopThree ，MiddleThree，LastThree > (Remarks)TopThree，MiddleThree，LastThree KEYfor1-3，KEY in MAP Key Value is1,2,3,4,5 Specify target Three of a kind，Straight， Pair ，Haif Straight，Other，Value Bet amount',
        'rank'            => '1st to 5th > (Remarks)KEY 1st to 5th（1-5）KEY in MAP Key The value is 0,1,2,3,4,5,6,7,8,9 Place 0 to 9 balls，10big，11samll，12odd，13even，Value Bet amount',
        /** LIVE LUCKY 10 **/
        'big'             => 'Champion and runner-upSum big',
        'small'           => 'Champion and runner-upSum small',
        'odd'             => 'Champion and runner-upSum odd',
        'even'            => 'Champion and runner-upSum even',
        'sum'             => 'Champion and runner-upSum',
        'rank'            => '1-10Name > KEY 1st to 10th（1-10）KEY in MAP KEY The value is 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 positioning，11big，12samll，13odd，14even，15Dragon ，16Tiger，Value Bet amount'
    ];

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->currency = $this->getSystemInfo('currency');
		$this->agent_name = $this->getSystemInfo('agent_name');
		$this->api_key = $this->getSystemInfo('api_key');
		$this->limit_group = $this->getSystemInfo('limit_group');
		$this->win_limit = $this->getSystemInfo('win_limit');
		$this->language = $this->getSystemInfo('language');
		$this->IS_TRIAL = false;
		$this->interval_seconds_on_sync_original=$this->getSystemInfo('interval_seconds_on_sync_original', 10);
		$this->sync_step_in_seconds = $this->getSystemInfo("sync_step_in_seconds",3600);
		$this->apiReportUrl = $this->getSystemInfo("apiReportUrl","https://report.dg99.info");
		$this->strict_on_currency_syncing = $this->getSystemInfo('strict_on_currency_syncing', false);
		$this->currencyId = $this->getSystemInfo('currencyId');

		$this->back_url = $this->getSystemInfo("back_url","");

		$this->bet_limits = $this->getSystemInfo("bet_limits",[]);

		$this->mark_data_grabbed = $this->getSystemInfo('mark_data_grabbed', true);//use to determine if needed to mark data as grabbed
		$this->allow_sync_tip = $this->getSystemInfo('allow_sync_tip', false);
		$this->isAllowedQueryTransactionWithoutId = $this->getSystemInfo('isAllowedQueryTransactionWithoutId', false);
		$this->response_code_treat_as_success = $this->getSystemInfo('response_code_treat_as_success', ['406']);
		$this->enable_mock_other_status_code_treat_as_success = $this->getSystemInfo('enable_mock_other_status_code_treat_as_success', false);
		$this->mock_code_treat_as_success_test = $this->getSystemInfo('mock_code_treat_as_success_test', 406);
		$this->enable_mock_other_status_code_treat_as_success_allowed_players = $this->getSystemInfo('enable_mock_other_status_code_treat_as_success_allowed_players', []);
	}

    public function isSeamLessGame(){
		return $this->returnUnimplemented();
    }

	public function getPlatformCode() {
		return $this->returnUnimplemented();
	}

	public function getHttpHeaders($params){
		return array("Content-Type" => "application/json");
	}

	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,true));
	}

	public function generateUrl($apiName, $params) {
		$apiUri = self::URI_MAP[$apiName];
		if($this->IS_TRIAL){
			# update link if trial game
			$apiUri = str_replace('login', 'free', $apiUri);
		}

		$url = $this->api_url . $apiUri . $this->agent_name;

		if($apiName == self::API_SyncMissingGameLogs || $apiName == self::API_getTipGift){
			$url = $this->apiReportUrl . $apiUri;
		}

		return $url;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;
		if(isset($resultArr['codeId'])&&$resultArr['codeId']=='0'){
			$success = true;
		}
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('DG Casino got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}
		return $success;
	}

	public function isPlayerExist($playerName){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$key = md5($this->agent_name.$this->api_key);

		$params = array(
			'token' => $key,
			'member' => array(
				'username' => $gameUsername,
			)
		);

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){

        $responseResultId = $this->getResponseResultIdFromParams($params);
      	$resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        if(empty($resultArr)){
        	$success = false;
        	$result = array('exists' => null);
        }else{
        	$success = true;
	        if ($resultArr['codeId']=="0") {
	        	$result = array('exists' => true);
	        }else if($resultArr['codeId']=="114"){
	            $result = array('exists' => false); # Player not found
	        }else{
	        	$result = array('exists' => null);
	        }
	    }

        return array($success, $result);
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

		$key = md5($this->agent_name.$this->api_key);

		$params = array(
			'token' => $key,
			'data' => $this->limit_group,
			"member" => array(
				"username" => $gameUsername,
				"password" => $password,
		        "currencyName" => $this->currency,
		        "winLimit" => $this->win_limit
			)
		);

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$playerId = $this->getVariableFromContext($params, 'playerId');
		
		$result = array(
			"player" => $playerName
		);

		if(isset($resultArr['codeId']) && $resultArr['codeId']==self::ERROR_DUPLICATE_PLAYER	){
			$success = true;
		}

		if($success){
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);	        
		}

		return array($success, $result);
	}

	public function updatePlayerBetLimitGroup($limit_group) {
		$this->CI->load->model('game_provider_auth');
		$gameUsername = $this->CI->game_provider_auth->getAllGameRegisteredUsernames($this->getPlatformCode());
		$cnt = 0;
		foreach($gameUsername as $username) {
			sleep(3); // wait 3 seconds to call again the API.
			$this->updatePlayerBetLimit($username, $limit_group);
			$cnt ++;
		}
		return "Count ".$cnt;
	}

	public function updatePlayerBetLimit($gameUsername, $limit_group = null) {

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUpdatePlayerBetLimitGroup',
			'gameUsername' => $gameUsername
		);

		$key = md5($this->agent_name.$this->api_key);
		$limit_group = $limit_group != null ? $limit_group : $this->limit_group;

		$params = array(
			'token' => $key,
			'data' => $limit_group,
			"member" => array(
				"username" => $gameUsername
			)
		);

		$this->CI->utils->debug_log("updatePlayerBetLimitGroup params ==========================>", $params);
		return $this->callApi(self::API_updatePlayerInfo, $params, $context);
	}

	public function setMemberBetSettingByGameUsername($gameUsername, $limit_group = null) {
        if(empty($limit_group)){
            $limit_group = $this->limit_group;
        }
        return $this->updatePlayerBetLimit($gameUsername, $limit_group);
	}

	public function processResultForUpdatePlayerBetLimitGroup($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);

		$this->CI->utils->debug_log('processResultForUpdatePlayerBetSettingsBySportTypeAndMarketType ==========================>', $resultArr);
		return array($success, $resultArr);
	}

	public function blockPlayer($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForBlockPlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$key = md5($this->agent_name.$this->api_key);

		$params = array(
			'token' => $key,
			"member" => array(
				"username" => $gameUsername,
		        "status" => self::PLAYER_DISABLED
			)
		);

		return $this->callApi(self::API_blockPlayer, $params, $context);
	}

	public function processResultForBlockPlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		if ($success) {
			$this->blockUsernameInDB($gameUsername);
		}

		$result = array(
			"playerName" => $playerName
		);

		return array($success, $result);
	}

	public function unblockPlayer($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUnblockPlayer',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$key = md5($this->agent_name.$this->api_key);

		$params = array(
			'token' => $key,
			"member" => array(
				"username" => $gameUsername,
		        "status" => self::PLAYER_ENABLED
			)
		);
		return $this->callApi(self::API_unblockPlayer, $params, $context);
	}

	public function processResultForUnblockPlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		if ($success) {
			$this->unblockUsernameInDB($gameUsername);
		}

		$result = array(
			"playerName" => $playerName
		);

		return array($success, $result);
	}

	public function changePassword($playerName, $oldPassword = null, $newPassword = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'newPassword' => $newPassword
		);

		$key = md5($this->agent_name.$this->api_key);

		$params = array(
			'token' => $key,
			"member" => array(
				"username" => $gameUsername,
				"password" => md5($newPassword)
			)
		);

		return $this->callApi(self::API_changePassword, $params, $context);
	}

	public function processResultForChangePassword($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$newPassword = $this->getVariableFromContext($params, 'newPassword');

		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		if ($success) {
			$playerId = $this->getPlayerIdInPlayer($gameUsername);
			//sync password to game_provider_auth
			$this->updatePasswordForPlayer($playerId, $newPassword);
		}

		$result = array(
			"player" => $playerName
		);

		return array($success, $result);
	}

	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        if($this->isSeamLessGame()) {

            $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
            $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

            $result = array(
                'success' => true,
                'balance' => $balance
            );

            return $result;
        }

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$key = md5($this->agent_name.$this->api_key);

		$params = array(
			'token' => $key,
			'member' => array(
				'username' => $gameUsername,
			)
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$result = array();
		if($success){
			$result['balance'] = @floatval($resultArr['member']['balance']);
			$result['balance'] = $this->gameAmountToDBTruncateNumber($result['balance']);
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
		if (!$this->validateWhitePlayer($playerName)) {
            return array('success' => false);
        }
        if($this->isSeamLessGame()) {
            $external_transaction_id = $transfer_secure_id;
            return array(
                'success' => true,
                'external_transaction_id' => $external_transaction_id,
                'response_result_id ' => NULL,
                'didnot_insert_game_logs'=> true,
            );
        }
		$type = self::TRANSFER_IN;
		$amount = $this->dBtoGameAmount($amount);

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = $transfer_secure_id;
		if(empty($transfer_secure_id)){
			$external_transaction_id = $gameUsername.date("ymdHis");
		}
		$key = md5($this->agent_name.$this->api_key);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'amount' => $amount,
			'type' => $type,
			'external_transaction_id' => $external_transaction_id
		);

		$params = array(
			'token' => $key,
			'data' => $external_transaction_id,
			'member' => array(
				'username' => $gameUsername,
				'amount' => $type==self::TRANSFER_IN?$amount:'-'.$amount  # self::TRANSFER_IN positive number self::TRANSFER_OUT negative number
			)
		);


		$this->CI->utils->debug_log("DG @depositToGame final params", $params);

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$type = $this->getVariableFromContext($params, 'type');
		$amount = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);
		$statusCode = $this->getStatusCodeFromParams($params);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		$this->CI->utils->debug_log("=========================================== processResultForDepositToGame response", $resultArr);
		$code = @$resultArr['codeId'];
		if($this->enable_mock_other_status_code_treat_as_success && in_array($playerName, $this->enable_mock_other_status_code_treat_as_success_allowed_players)){
			$this->CI->utils->debug_log("=========================================== enable_mock_other_status_code_treat_as_success :true");
			$code = $this->mock_code_treat_as_success_test;
			$success = false;
		}

		if ($success) {
            $result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
			if($this->enable_mock_other_status_code_treat_as_success && in_array($playerName, $this->enable_mock_other_status_code_treat_as_success_allowed_players)){
				$code = $this->mock_code_treat_as_success_test;
			}

			$result['reason_id'] = $this->getReasons($code);
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;

			if($type==self::TRANSFER_IN){ //status code check
				if((in_array($statusCode, $this->other_status_code_treat_as_success) || in_array($code, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
					$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
					$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
					$success=true;
				}

				if(in_array($code, $this->response_code_treat_as_success)){ // codeId check
					$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
					$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
					$success=true;
				}
			}

        }

        return array($success, $result);
		
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
		if (!$this->validateWhitePlayer($playerName)) {
            return array('success' => false);
        }
        if($this->isSeamLessGame()) {
            $external_transaction_id = $transfer_secure_id;
            return array(
                'success' => true,
                'external_transaction_id' => $external_transaction_id,
                'response_result_id ' => NULL,
                'didnot_insert_game_logs'=> true,
            );
        }
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$type = self::TRANSFER_OUT;
		$amount = $this->dBtoGameAmount($amount);
		$external_transaction_id = $transfer_secure_id;
		if(empty($transfer_secure_id)){
			$external_transaction_id = $gameUsername.date("ymdHis");
		}
		$key = md5($this->agent_name.$this->api_key);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultWithdrawFromGame',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'amount' => $amount,
			'type' => $type,
			'external_transaction_id' => $external_transaction_id
		);

		$params = array(
			'token' => $key,
			'data' => $external_transaction_id,
			'member' => array(
				'username' => $gameUsername,
				'amount' => $type==self::TRANSFER_IN?$amount:'-'.$amount  # self::TRANSFER_IN positive number self::TRANSFER_OUT negative number
			)
		);

		$this->CI->utils->debug_log("DG @depositToGame final params", $params);

		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultWithdrawFromGame($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$type = $this->getVariableFromContext($params, 'type');
		$amount = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);
		$statusCode = $this->getStatusCodeFromParams($params);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		$code = @$resultArr['codeId'];
		if ($success) {
            $result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
			$result['reason_id'] = $this->getReasons($code);
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
	}

	public function isAllowedQueryTransactionWithoutId(){
		$allowed = $this->isAllowedQueryTransactionWithoutId;
		$this->CI->utils->debug_log('isAllowedQueryTransactionWithoutId >>>>>>>>>>>>>>>>',$allowed);
        return $allowed;
    }

	public function queryTransaction($transactionId, $extra) {
		$playerName=$extra['playerName'];
		$playerId=$extra['playerId'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$random = $gameUsername.date("ymdHis");
		$key = md5($this->agent_name.$this->api_key);

		if($this->isAllowedQueryTransactionWithoutId){
			if(empty($transactionId) && isset($extra['secure_id'])){
				$transactionId = $extra['secure_id'];
			}
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'external_transaction_id' => $transactionId,
			'gameUsername'=>$gameUsername,
			'playerName'=>$playerName,
			'playerId'=>$playerId,
			'external_transaction_id'=>$transactionId
		);

		$params = array(
			'token' => $key,
			'data' => $transactionId
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params){
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
            $code = @$resultArr['codeId'];
            $result['reason_id'] = $this->getReasons($code);
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	// This code is no longer in use
	public function transferCredit($playerName, $amount,$type, $transfer_secure_id=null){
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = $transfer_secure_id;
		if(empty($transfer_secure_id)){
			$external_transaction_id = $gameUsername.date("ymdHis");
		}
		$key = md5($this->agent_name.$this->api_key);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForTransferCredit',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'amount' => $amount,
			'type' => $type,
			'external_transaction_id' => $external_transaction_id
		);

		$params = array(
			'token' => $key,
			'data' => $external_transaction_id,
			'member' => array(
				'username' => $gameUsername,
				'amount' => $type==self::TRANSFER_IN?$amount:'-'.$amount  # self::TRANSFER_IN positive number self::TRANSFER_OUT negative number
			)
		);

		# correct Method
		if($type == self::TRANSFER_IN){
			$method = self::API_depositToGame;
		}else{
			$method = self::API_withdrawFromGame;
		}

		return $this->callApi($method, $params, $context);
	}

	public function processResultForTransferCredit($params) {
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$type = $this->getVariableFromContext($params, 'type');
		$amount = $this->getVariableFromContext($params, 'amount');
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);
		$statusCode = $this->getStatusCodeFromParams($params);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);

		$code = @$resultArr['codeId'];

		if($this->enable_mock_other_status_code_treat_as_success && in_array($playerName, $this->enable_mock_other_status_code_treat_as_success_players)){
			$code = $this->mock_code_treat_as_success_test;
			$success = false;
		}

		if ($success) {
            $result['didnot_insert_game_logs']=true;
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;

    //         $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

    //         if ($playerId) {
    //             $afterBalance = 0;

    //             if($type == self::TRANSFER_IN){ // Deposit
	   //              // Deposit
	   //              $this->insertTransactionToGameLogs($playerId, $playerName, null, $amount, $responseResultId,
	   //                  $this->transTypeMainWalletToSubWallet());
    //             }else{ // Withdraw
	   //              // Withdraw
	   //              $this->insertTransactionToGameLogs($playerId, $playerName, null, $amount, $responseResultId,
	   //                  $this->transTypeSubWalletToMainWallet());
    //             }

				// $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;

    //         }else{
    //         	$result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
    //         	$this->CI->utils->debug_log('error', '=============== cannot get player id from '.$playerName.' getPlayerIdInGameProviderAuth');
    //         }
        }else{
			$code = @$resultArr['codeId'];
			if($this->enable_mock_other_status_code_treat_as_success && in_array($playerName, $this->enable_mock_other_status_code_treat_as_success_players)){
				$code = $this->mock_code_treat_as_success_test;
			}

			$result['reason_id'] = $this->getReasons($code);
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;

			if($type==self::TRANSFER_IN){ //status code check
				if((in_array($statusCode, $this->other_status_code_treat_as_success) || in_array($code, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
					$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
					$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
					$success=true;
				}

				if(in_array($code, $this->other_status_code_treat_as_success)){ // codeId check
					$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
					$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
					$success=true;
				}
			}

        }

        return array($success, $result);
	}

	private function getReasons($code){
        switch ($code) {
        	case '1':
        		return self::REASON_INCOMPLETE_INFORMATION;
        		break;
        	case '2':
        		return self::REASON_INVALID_KEY;
        		break;
        	case '100':
        		return self::REASON_GAME_ACCOUNT_LOCKED;
        		break;
        	case '114':
        		return self::REASON_NOT_FOUND_PLAYER;
        		break;
        	case '120':
        		return self::REASON_NO_ENOUGH_BALANCE;
        		break;
        	case '300':
        		return self::REASON_API_MAINTAINING;
        		break;
        	case '320':
        		return self::REASON_INVALID_KEY;
        		break;
        	case '324':
        		return self::REASON_FAILED_FROM_API;
        		break;
        	case '400':
        		return self::REASON_IP_NOT_AUTHORIZED;
        		break;
        	case '501':
        		return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
        		break;
        	default:
        		return self::REASON_UNKNOWN;
        		break;
        }
	}

	public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
        	case 'cn':
			case 'zh-cn':
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'cn';
                break;
			case 'en':			
            case 'en-us':
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
                $lang = 'en';
                break;
			case 'vi':
			case 'vi-vn':
			case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
				$lang = 'vi';
				break;			
            case 'th':
			case 'th-th':
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                $lang = 'th';
                break;
            default:
                $lang = 'en';
                break;
        }

        return $lang;
    }

    public function login($playerName, $password = null, $extra = null) {
    	$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
		);

		$key = md5($this->agent_name.$this->api_key);
		//$language = $this->getLauncherLanguage($extra['language']);
		$language = $this->getLauncherLanguage($this->language);
		if($extra['is_mobile']){
			$device = 5; #mobile HTML5
		}else{
			$device = 1; #Flash Login
		}

		if($extra['game_mode']=='demo'||$extra['game_mode']=='trial'){
			$this->IS_TRIAL = true;
			$params = array(
				'token' => $key,
				'lang' => $language,
				'device' => $device
			);
		}else{
			$params = array(
				'token' => $key,
				'lang' => $language,
				'device' => $device,
				'member' => array(
					'username' => $gameUsername
				),

			);

			$params['limits'] = $this->bet_limits;
		}

		return $this->callApi(self::API_login, $params, $context);
	}

	public function processResultForLogin($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		return array($success, $resultArr);
	}

	public function queryForwardGame($playerName,$extra=null) {
		if (!$this->validateWhitePlayer($playerName)) {
            return array('success' => false);
        }
		$resultArr = $this->login($playerName,null,$extra);

		$language = (isset($extra['language']) && $extra['language'])?$this->getLauncherLanguage($extra['language']):$this->language;

        $url ='';
        if($resultArr['success']) {

			if (isset($extra['home_link'])) {
				$backUrl = $extra['home_link'];
			}else{
				$backUrl = $this->back_url;
			}

			if($extra['is_mobile']){ #mobile
                $url = $resultArr['list'][1].$resultArr['token'].'&language='.$language.'&backUrl='.$backUrl;
            }else{
                $url = $resultArr['list'][0].$resultArr['token'].'&language='.$language.'&backUrl='.$backUrl;
            }
        }
		return array("success"=>$resultArr['success'], "url" => $url);
	}

	/**
	 * Take sleep for specific time
	 *
	 *
	 * @return void
	*/
	public function takeSleep(){

		if($this->interval_seconds_on_sync_original>0){
			$this->CI->utils->info_log('sleeping '. $this->interval_seconds_on_sync_original .' seconds for sync original,please wait');
			sleep($this->interval_seconds_on_sync_original);
			# we sleep for 10 secs, so we need to reconnect to database
			$this->CI->db->_reset_select();
			$this->CI->db->reconnect();
			$this->CI->db->initialize();
		}
	}

	/**
	 * Sync DG missing Data, where data is more than 24 hours in the game provider API
	 *
	 * Ticket Number: OGP-15845
	 */
	public function syncMissingGameLogs($token = false){
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());

		$result = array();
		$this->CI->utils->loopDateTimeStartEndDaily($startDate, $endDate, function($startDate, $endDate) use(&$result) {
			$startDate = $startDate->format('Y-m-d H:i:s');
			$endDate   = $endDate->format('Y-m-d H:i:s');
			
			$key = md5($this->agent_name.$this->api_key);

			#logs checking date
			$date_params = array(
				"s" => $startDate,
				"e" => $endDate,
			);

			$this->CI->utils->info_log('date_params '. json_encode($date_params));
			
			$current_page = self::DEFAULT_PAGE;
			$page_number = self::DEFAULT_PAGE_NUMBER;
		    while($current_page <= $page_number) {
		    	$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncOriginalGameLogs',
					'startDate' => $startDate,
					'endDate' => $endDate,
					"newDataGrab" => true,
					"page_number" => $page_number,
					"current_page" => $current_page
				);
				$params = array(
					"token" => $key,
				    "beginTime" =>  $startDate,
				    "endTime" =>  $endDate,
				    "agentName" =>  $this->agent_name,
				    "pageNow" =>  $current_page
				);

				$this->CI->utils->info_log('syncMissingGameLogs params '. json_encode($params));

				$response =  $this->callApi(self::API_SyncMissingGameLogs, $params, $context);
				$this->CI->utils->info_log('syncMissingGameLogs response '. json_encode($response));

				if(isset($response['success']) && $response['success']){
					$next_page = $response['pageNow'] + self::DEFAULT_PAGE;
					$current_page = $next_page;
					$page_number = $response['pageCount'];
					$result[] = $response;
				} else {
					if(isset($response['codeId']) && $response['codeId'] == self::CODE_ID_FREQUENT_REQUEST){
						$current_page = $response['pageNow'];
						$this->CI->utils->info_log('sleeping '. $this->interval_seconds_on_sync_original .' seconds for sync original,please wait');
						sleep($this->interval_seconds_on_sync_original);
					}else {
						$current_page = $page_number + self::DEFAULT_PAGE;#stop
					}
				}
			}

			return true;
	    });
	    return $result;

		// $tokenStartDate = clone parent::getValueFromSyncInfo($token,'dateTimeFrom');
		// $TokenEndDate = clone parent::getValueFromSyncInfo($token,'dateTimeTo');
		// $isManualSync = $this->getValueFromSyncInfo($token, 'isManualSync');

		// $startDateTime = new DateTime($this->serverTimeToGameTime($tokenStartDate->format("Y-m-d H:i:s")));
		// $endDateTime = new DateTime($this->serverTimeToGameTime($TokenEndDate->format("Y-m-d H:i:s")));

		// $start = clone $startDateTime;
		// $end = clone $endDateTime;
		// $now = new DateTime();

		// if($end > $now){
		// 	$end = $now;
		// }

		// $context = [
		// 	"callback_obj" => $this,
		// 	"callback_method" => "processResultForSyncOriginalGameLogs",
		// 	"isManualSync" => $isManualSync
		// ];

		// $step = $this->sync_step_in_seconds; # steps in seconds
		// $key = md5($this->agent_name.$this->api_key);

		// while($start < $end){
		// 	$endDate = $this->CI->utils->getNextTimeBySeconds($start,$step);
		// 	$takeSleep = true;

		// 	if($endDate > $end){
		// 		$endDate = $end;
		// 	}

		// 	$params = [
		// 		"token" => $key,
		// 		"beginTime" => $start->format('Y-m-d H:i:s'),
		// 		"endTime" => $endDate->format('Y-m-d H:i:s'),
		// 		"agentName" => $this->agent_name
		// 	];

		// 	$this->CI->utils->debug_log('DG_API params >>>>>>>>>>>>>>>>',$params);

		// 	$apiResult = $this->callApi(self::API_SyncMissingGameLogs,$params,$context);

		// 	# we check if if not success and codeId is 405 means to sleep for 10 seconds
		// 	if(isset($apiResult["success"]) && !$apiResult["success"] && isset($apiResult["codeId"]) && $apiResult["codeId"] == 405){

		// 		$this->takeSleep();

		// 		continue;
		// 	}

		// 	# we check if API call is success
		// 	if(isset($apiResult["success"]) && ! $apiResult["success"]){

		// 		$this->CI->utils->debug_log('DG_API ERROR in calling API: ',$apiResult);

		// 		break;
		// 	}

		// 	if(isset($apiResult["is_max_return"]) && $apiResult["is_max_return"]){

		// 		$this->CI->utils->debug_log('DG_API is max return of API ',$apiResult["is_max_return"]);
		// 		$this->CI->utils->debug_log('DG_API is max return row count ',$apiResult["rowCount"]);

		// 		$step = $step / 2; # we divide by two the step here, meaning cut to half the end date
		// 	 }else{
		// 		$start = $endDate;
		// 	 }

		// 	if($takeSleep){
		// 		$this->takeSleep();
		// 	}
		// }

		// return [
		// 	true,
		// 	$apiResult
		// ];
	}


	public function getTipGift($token = false){
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());

		$result = array();
		$this->CI->utils->loopDateTimeStartEndDaily($startDate, $endDate, function($startDate, $endDate) use(&$result) {
			$startDate = $startDate->format('Y-m-d H:i:s');
			$endDate   = $endDate->format('Y-m-d H:i:s');
			
			$key = md5($this->agent_name.$this->api_key);

			#logs checking date
			$date_params = array(
				"s" => $startDate,
				"e" => $endDate,
			);

			$this->CI->utils->info_log('date_params '. json_encode($date_params));
			
			$current_page = self::DEFAULT_PAGE;
			$page_number = self::DEFAULT_PAGE_NUMBER;
		    while($current_page <= $page_number) {
		    	$context = array(
					'callback_obj' => $this,
					'callback_method' => 'processResultForSyncOriginalGameLogs',
					'startDate' => $startDate,
					'endDate' => $endDate,
					"newDataGrab" => true,
					"page_number" => $page_number,
					"current_page" => $current_page
				);
				$params = array(
					"token" => $key,
				    "beginTime" =>  $startDate,
				    "endTime" =>  $endDate,
				    "agentName" =>  $this->agent_name,
				    "pageNow" =>  $current_page
				);

				$this->CI->utils->info_log('getTipGift params '. json_encode($params));

				$response =  $this->callApi(self::API_getTipGift, $params, $context);
				$this->CI->utils->info_log('getTipGift response '. json_encode($response));

				if(isset($response['success']) && $response['success']){
					$next_page = $response['pageNow'] + self::DEFAULT_PAGE;
					$current_page = $next_page;
					$page_number = $response['pageCount'];
					$result[] = $response;
				} else {
					if(isset($response['codeId']) && $response['codeId'] == self::CODE_ID_FREQUENT_REQUEST){
						$current_page = $response['pageNow'];
						$this->CI->utils->info_log('sleeping '. $this->interval_seconds_on_sync_original .' seconds for sync original,please wait');
						sleep($this->interval_seconds_on_sync_original);
					}else {
						$current_page = $page_number + self::DEFAULT_PAGE;#stop
					}
				}
			}

			return true;
	    });
	    return $result;
	}

	public function syncLostAndFound($token) {
		if($this->allow_sync_tip){
			return $this->getTipGift($token);
		}
		return $this->returnUnimplemented();
	}

	public function syncOriginalGameLogs($token = false) {

		// $ignore_public_sync = $this->getValueFromSyncInfo($token, 'ignore_public_sync');

		// if ($ignore_public_sync == true) {
		// 	$this->CI->utils->debug_log('ignore manually sync'); // ignore public sync
		// 	return array('success' => true);
		// }

		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());

		//observer the date format
		$startDate=$startDate->format('Y-m-d H:i:s');
		$endDate=$endDate->format('Y-m-d H:i:s');

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs',
			'startDate' => $startDate,
			'endDate' => $endDate,
			"newDataGrab" => false
		);

		$key = md5($this->agent_name.$this->api_key);

		$params = array(
			'token' => $key
		);

		return $this->callApi(self::API_syncGameRecords, $params, $context);

	}
	const DEFAULT_PAGE = 1;
	const DEFAULT_PAGE_NUMBER = 1;
	const ORIGINAL_LOGS_TABLE_NAME = 'dg_game_logs';
	const MD5_FIELDS_FOR_ORIGINAL=['betTime','calTime','availableBet','result','betDetail'];
	const MD5_FLOAT_AMOUNT_FIELDS=['winOrLoss','balanceBefore','betPoints','betPointsz','availableBet'];
	const CODE_ID_FREQUENT_REQUEST = 405;

	public function processResultForSyncOriginalGameLogs($params) {
		$resultArr = $this->getResultJsonFromParams($params);
		$this->CI->load->model(array('dg_game_logs','original_game_logs_model'));
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$isManualSync = $this->getVariableFromContext($params, 'isManualSync');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		$gameRecords = isset($resultArr['list'])?$resultArr['list']:array();
		
		$page_number = $this->getVariableFromContext($params, 'page_number');
		$current_page =  $this->getVariableFromContext($params, 'current_page');

		$dataResult = array(
			'data_count' => 0,
			'data_count_insert'=> 0,
			'data_count_update'=> 0,
			'pageNow'=> !empty($current_page) ? $current_page : self::DEFAULT_PAGE,
			'pageCount'=> !empty($page_number) ? $page_number : self::DEFAULT_PAGE_NUMBER,
			'pageSize'=> 0,
			'rowCount'=> 0,
			'codeId' => isset($resultArr['codeId']) ? $resultArr['codeId'] : null
		);

		if($success){
			$newDataGrab = $this->getVariableFromContext($params, 'newDataGrab');
			if($newDataGrab){
				$gameRecords = isset($resultArr['data']['records'])?$resultArr['data']['records']:array();
				$dataResult['pageCount'] = $resultArr['data']['pageCount'];
				$dataResult['pageSize'] = $resultArr['data']['pageSize'];
				$dataResult['pageNow'] = $resultArr['data']['pageNow'];
				$dataResult['rowCount'] = $resultArr['data']['rowCount'];
			}
			if(!empty($gameRecords)){
				$this->processGameRecords($gameRecords, $responseResultId);
				list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
	                self::ORIGINAL_LOGS_TABLE_NAME,
	                $gameRecords,
	                'external_uniqueid',
	                'external_uniqueid',
	                self::MD5_FIELDS_FOR_ORIGINAL,
	                'md5_sum',
	                'id',
	                self::MD5_FLOAT_AMOUNT_FIELDS
	            );

	            $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));
	            // echo "<pre>";
	            // print_r($insertRows);exit();
	            $dataResult['data_count'] = count($gameRecords);
				if (!empty($insertRows)) {
					$dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
				}
				unset($insertRows);

				if (!empty($updateRows)) {
					$dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
				}
				unset($updateRows);
			}
		}
		return array($success, $dataResult);
		/*
		# check first if trigger in manual sync
		if($isManualSync){
			if(isset($resultArr["codeId"]) && $resultArr["codeId"] == 0){
				$rowCount = isset($resultArr["data"]["rowCount"]) ? $resultArr["data"]["rowCount"] : null;
				# check if row count is more than 1000 meaning max return
				if($rowCount && $rowCount > 1000){

					return [
						true,
						"rowCount" => $rowCount,
						"is_max_return" => true
					];
				}else{
					$gameRecords = isset($resultArr["data"]["records"])?$resultArr['data']["records"]:array();
				}
			}else{
				return [false,$resultArr];
			}
		}

		$result = array();
		$dataCount = 0;
		if(!empty($gameRecords)){
			# we checked here if trigger by manual sync
			list($availableRow, $skipForManualSync, $existsId) = $this->CI->dg_game_logs->getAvailableRows($gameRecords);
			if($isManualSync){
				if($skipForManualSync){
					$availableRows = $availableRow;
				}else{
					$availableRows = $gameRecords;
				}
			}else{
				$availableRows = $availableRow;
			}

			if($success && !empty($availableRows)){
				# Mark Got Bet List
				$ticketIdSet = array();
				if(!empty($existsId)){
					array_merge($existsId,$existsId);
				}
				foreach ($availableRows as $record) {
					if ($record['isRevocation']!=1) {
						continue; # Status：0:Unsettled, 1:Settled, 2:Revoked(The Ticket is a Hedge)
					}

					## FOR CLIENT THAT SUPPORT MULTIPLE CURRENCY with SINGLE API CREDENTIALS will get only records of their currency
					if ($this->strict_on_currency_syncing && $this->currencyId != $record['currencyId']) {
						continue;
					}

					$insertRecord = array();
					//Data from DG API
					$insertRecord['dg_id'] = isset($record['id']) ? $record['id'] : NULL;
					$insertRecord['tableId'] = isset($record['tableId']) ? $record['tableId'] : NULL;
					$insertRecord['shoeId'] = isset($record['shoeId']) ? $record['shoeId'] : NULL;
					$insertRecord['playId'] = isset($record['playId']) ? $record['playId'] : NULL;
					$insertRecord['lobbyId'] = isset($record['lobbyId']) ? $record['lobbyId'] : NULL;
					$insertRecord['gameType'] = isset($record['gameType']) ? $record['gameType'] : NULL;
					$insertRecord['gameId'] = isset($record['gameId']) ? $record['gameId'] : NULL;
					$insertRecord['memberId'] = isset($record['memberId']) ? $record['memberId'] : NULL;
					$insertRecord['parentId'] = isset($record['parentId']) ? $record['parentId'] : NULL;
					$insertRecord['betTime'] = isset($record['betTime']) ? $this->gameTimeToServerTime($record['betTime']) : NULL;
					$insertRecord['calTime'] = isset($record['calTime']) ? $this->gameTimeToServerTime($record['calTime']) : NULL;
					$insertRecord['winOrLoss'] = isset($record['winOrLoss']) ? $record['winOrLoss'] : NULL;
					$insertRecord['balanceBefore'] = isset($record['balanceBefore']) ? $record['balanceBefore'] : NULL;
					$insertRecord['betPoints'] = isset($record['betPoints']) ? $record['betPoints'] : NULL;
					$insertRecord['betPointsz'] = isset($record['betPointsz']) ? $record['betPointsz'] : NULL;
					$insertRecord['availableBet'] = isset($record['availableBet']) ? $record['availableBet'] : NULL;
					$insertRecord['userName'] = isset($record['userName']) ? $record['userName'] : NULL;
					$insertRecord['result'] = isset($record['result']) ? $record['result'] : NULL;
					$insertRecord['betDetail'] = isset($record['betDetail']) ? $record['betDetail'] : NULL;
					$insertRecord['ip'] = isset($record['ip']) ? $record['ip'] : NULL;
					$insertRecord['ext'] = isset($record['ext']) ? $record['ext'] : NULL;
					$insertRecord['isRevocation'] = isset($record['isRevocation']) ? $record['isRevocation'] : NULL;
					$insertRecord['currencyId'] = isset($record['currencyId']) ? $record['currencyId'] : NULL;
					$insertRecord['deviceType'] = isset($record['deviceType']) ? $record['deviceType'] : NULL;
					$insertRecord['roadid'] = isset($record['roadid']) ? $record['roadid'] : NULL;
					$insertRecord['pluginid'] = isset($record['pluginid']) ? $record['pluginid'] : NULL;

					//extra info from SBE
					$insertRecord['external_uniqueid'] = isset($record['id']) ? $record['id'] : NULL;
					$insertRecord['response_result_id'] = $responseResultId;
					//insert data to Pragmatic Play gamelogs table database
					$succ = $this->CI->dg_game_logs->insertGameLogs($insertRecord);
					if($succ){# push array ticket if success insert 
						array_push($ticketIdSet, $record['id']); # add Id to marked
					}
					$dataCount++;
				}
			}
			#2.9 Get Bet List required to use 2.10 Mark Got Bet List
			if(!empty($ticketIdSet) && $this->mark_data_grabbed){
				$this->markGotBetList($ticketIdSet);
			}
		}

		if ($this->interval_seconds_on_sync_original > 0 && !$isManualSync) {
			$this->CI->utils->debug_log('sleep 10s when sync original');
			sleep($this->interval_seconds_on_sync_original);
			# we sleep for 10 secs, so we need to reconnect to database
			$this->CI->db->_reset_select();
			$this->CI->db->reconnect();
			$this->CI->db->initialize();
		}

		$result['data_count'] = $dataCount;

		return array($success, $result);
		*/
	}

	public function processGameRecords(&$gameRecords, $responseResultId) {
		if(!empty($gameRecords)){
			foreach($gameRecords as $index => $record) {
				$insertRecord['dg_id'] = isset($record['id']) ? $record['id'] : NULL;
				$insertRecord['tableId'] = isset($record['tableId']) ? $record['tableId'] : NULL;
				$insertRecord['shoeId'] = isset($record['shoeId']) ? $record['shoeId'] : NULL;
				$insertRecord['playId'] = isset($record['playId']) ? $record['playId'] : NULL;
				$insertRecord['lobbyId'] = isset($record['lobbyId']) ? $record['lobbyId'] : NULL;
				$insertRecord['gameType'] = isset($record['gameType']) ? $record['gameType'] : NULL;
				$insertRecord['gameId'] = isset($record['gameId']) ? $record['gameId'] : NULL;
				$insertRecord['memberId'] = isset($record['memberId']) ? $record['memberId'] : NULL;
				$insertRecord['parentId'] = isset($record['parentId']) ? $record['parentId'] : NULL;
				$insertRecord['betTime'] = isset($record['betTime']) ? $this->gameTimeToServerTime($record['betTime']) : NULL;
				$insertRecord['calTime'] = isset($record['calTime']) ? $this->gameTimeToServerTime($record['calTime']) : NULL;
				$insertRecord['winOrLoss'] = isset($record['winOrLoss']) ? $record['winOrLoss'] : NULL;
				$insertRecord['balanceBefore'] = isset($record['balanceBefore']) ? $record['balanceBefore'] : NULL;
				$insertRecord['betPoints'] = isset($record['betPoints']) ? $record['betPoints'] : NULL;
				$insertRecord['betPointsz'] = isset($record['betPointsz']) ? $record['betPointsz'] : NULL;
				$insertRecord['availableBet'] = isset($record['availableBet']) ? $record['availableBet'] : NULL;
				$insertRecord['userName'] = isset($record['userName']) ? $record['userName'] : NULL;
				$insertRecord['result'] = isset($record['result']) ? $record['result'] : NULL;
				$insertRecord['betDetail'] = isset($record['betDetail']) ? $record['betDetail'] : NULL;
				$insertRecord['ip'] = isset($record['ip']) ? $record['ip'] : NULL;
				$insertRecord['ext'] = isset($record['ext']) ? $record['ext'] : NULL;
				$insertRecord['isRevocation'] = isset($record['isRevocation']) ? $record['isRevocation'] : NULL;
				$insertRecord['currencyId'] = isset($record['currencyId']) ? $record['currencyId'] : NULL;
				$insertRecord['deviceType'] = isset($record['deviceType']) ? $record['deviceType'] : NULL;
				$insertRecord['roadid'] = isset($record['roadid']) ? $record['roadid'] : NULL;
				$insertRecord['pluginid'] = isset($record['pluginid']) ? $record['pluginid'] : NULL;

				//extra info from SBE
				$insertRecord['external_uniqueid'] = isset($record['id']) ? $record['id'] : NULL;
				$insertRecord['response_result_id'] = $responseResultId;
				$gameRecords[$index] = $insertRecord;
				unset($insertRecord);
			}
		}
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        $ticketIdSet = array();
        
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                	$record['updated_at'] = $this->utils->getNowForMysql();
                    $succ = $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $succ = $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                }
                if($succ){# push array ticket if success insert/update 
					array_push($ticketIdSet, $record['dg_id']); # add Id to marked
				}
                $dataCount++;
                unset($record);
            }
        }

        #2.9 Get Bet List required to use 2.10 Mark Got Bet List
		if(!empty($ticketIdSet) && $this->mark_data_grabbed){
			$this->markGotBetList($ticketIdSet);
			unset($ticketIdSet);
		}
        return $dataCount;
    }

	public function markGotBetList($ticketIdSet) {
		$key = md5($this->agent_name.$this->api_key);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForMarkGotBetList'
		);

		$params = array(
			'token' => $key,
			'list' => $ticketIdSet
		);

		return $this->callApi(self::API_MarkGotBetList, $params, $context);
	}

	public function processResultForMarkGotBetList($params){
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		return array($success, null);
	}

	public function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'dg_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$rlt = array('success' => true);

		$result = $this->CI->dg_game_logs->getGameLogStatistics($startDate, $endDate, $this->getPlatformCode());
		$cnt = 0;
		if (!empty($result)) {

			$unknownGame = $this->getUnknownGame();
			foreach ($result as $row) {

				$playerId = $this->getPlayerIdInGameProviderAuth($row->userName);
				$win_loss_amount = $row->result_amount;
				if($row->orig_game_type==self::GAMETYPE_GIFTTIP && $row->orig_game_id==self::GAMEID_MEMBER_GET_GIFT){
					//OGP-19773 Holiday bonus
					$result_amount = (float)$row->real_bet_amount;
					$row->real_bet_amount = 0;
				}else{
					$result_amount = (float)$row->result_amount - (float)$row->real_bet_amount;
				}
                
				$cnt++;

				$game_description_id = $row->game_description_id;
				$game_type_id = $row->game_type_id;

				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
					$game_type_id = $unknownGame->game_type_id;
				}

				//apply conversion
				$result_amount = $this->gameAmountToDBTruncateNumber($result_amount);
				$row->real_bet_amount = $this->gameAmountToDBTruncateNumber($row->real_bet_amount);
				$row->bet_amount = $this->gameAmountToDBTruncateNumber($row->bet_amount);
				$row->balanceBefore = $this->gameAmountToDBTruncateNumber($row->balanceBefore);
				$win_loss_amount = $this->gameAmountToDBTruncateNumber($win_loss_amount);
				
				$extra = array(
                    'trans_amount' => $row->real_bet_amount,
                    'table' => $row->ext,
                    'bet_details' => $this->processGameBetDetail($row, $result_amount, $row->bet_amount),
                    'sync_index' => $row->id,
                );

                $after_balance = ($row->balanceBefore - $row->real_bet_amount) + $win_loss_amount;

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$row->game_code,
					$row->game_type,
					$row->game,
					$playerId,
					$row->userName,
					$row->bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					$after_balance, # after_balance
					0, # has_both_side
					$row->dg_id,
					$row->betTime, //start
					(empty($row->calTime)?$row->betTime:$row->calTime), //end
					$row->response_result_id,
					Game_logs::FLAG_GAME,
                    $extra
				);

			}
		}

		$this->CI->utils->debug_log('DG PLAY API =========================>', 'startDate: ', $startDate,'EndDate: ', $endDate);
		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
		return $rlt;
	}

    public function processGameBetDetail($row,$result_amount,$bet_amount){
        $row = json_decode(json_encode($row),true);
        $betDetails = json_decode($row['betDetail'],true);

        $bet_placed = "";
        $won_side = "";
        if(!empty($betDetails)){
           foreach ($betDetails as $key => $bet_detail) {
               if ( ! strpos($key, "W")) {
                    if (array_key_exists($key,self::BET_DETAILS)) {
                        if(! empty(self::BET_DETAILS[$key])){
                          $bet_placed .= self::BET_DETAILS[$key] . ", ";
                        }
                    }
                }else{
                    $key = str_replace("W", "", $key);
                    if (array_key_exists($key,self::BET_DETAILS)) {
                        if(! empty(self::BET_DETAILS[$key])){
                           $won_side = self::BET_DETAILS[$key] . " Won";
                        }
                    }
                }
           }
        }
        if( ! empty($row['game_code'])){
            $bet_details = array(
                "win_amount" => ($bet_amount > 0) ? $bet_amount:0,
                "bet_amount" =>  $bet_amount,
                "bet_placed" => $bet_placed,
                "won_side" => $won_side,
                "winloss_amount" => $result_amount,
            );
            return $this->CI->utils->encodeJson($bet_details);
        }

        return false;
    }

	public function logout($userName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function syncPlayerAccount($username, $password, $playerId) {
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

    public function updatePlayerBetSettings($gameUsername)
    {
        return $this->updatePlayerBetLimit($gameUsername);
    }

    public function queryTransactionByDateTime($startDate, $endDate){
        $this->CI->load->model(array('original_game_logs_model'));

        $original_transactions_table = $this->getTransactionsTable();

        $sql = <<<EOD
SELECT 
t.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.ticket_id as round_no,
t.unique_transaction_id as external_uniqueid,
t.transaction_type trans_type
FROM {$original_transactions_table} as t
WHERE `t`.`updated_at` >= ? AND `t`.`updated_at` <= ? 
ORDER BY t.updated_at asc;
EOD;
        
        $params=[$startDate, $endDate];
        
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

	public function processTransactions(&$transactions){
        $temp_game_records = [];

        if(!empty($transactions)){
            foreach($transactions as $transaction){
                
                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];                
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];

                //$extra_info = @json_encode($transaction['extra_info'], true);                
                $extra=[];                
                $extra['trans_type'] = $transaction['trans_type'];                
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                if(in_array($transaction['trans_type'], $this->seamless_debit_transaction_type)){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }
				
                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }

    public function updatePlayerBetLimitRange($request_params) {
        if (empty($request_params['game_username'])) {
            return [
                'success' => false,
                'message' => 'game_username required',
            ];
        }

        if (empty($request_params['bet_limit'])) {
            return [
                'success' => false,
                'message' => 'bet_limit required',
            ];
        }

        $random = random_string('alnum', 32);
        $key = md5($this->agent_name.$this->api_key.$random);
        $game_username = $request_params['game_username'];
        $bet_limit = $request_params['bet_limit'];

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForUpdatePlayerBetLimitRange',
            'game_username' => $game_username,
        ];

        $params = array(
            'token' => $key,
            'random' => $random,
            'limit' => $bet_limit,
            'member' => [
                "username" => $game_username,
            ]
        );

        $this->utils->debug_log(__METHOD__,$context, $params);

        return $this->callApi(self::API_setMemberBetSetting, $params, $context);
    }

    public function processResultForUpdatePlayerBetLimitRange($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        return array($success, $resultArr);
    }
}

/*end of file*/