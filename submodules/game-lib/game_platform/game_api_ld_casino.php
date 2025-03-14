<?php
/***********************************************************************************************************

VALID BETS COMPUTATION
	 - if bet amount is different   ------------> valid_bet = different between bet amount
	 - if bet amount is the same    ------------> valid_bet = different between win lose amunt

***********************************************************************************************************/

require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_ld_casino extends Abstract_game_api {
	const GAME_PROVIDER = 'LD_CASINO';

	# Action Type
	const ONLY_CREATE_USER 		= "ocu";	// create user only
	const CREATE_LOGIN_USER		= "cru";	// if user not exist create the player then login
	const CHECK_USER_EXIST 		= "cue";
	const TRANS_CREDIT 			= "trc";
	const GET_BALANCE 			= "geb";
	const GET_ORDER_STATUS 		= "gos";
	const GET_BILL_STATUS 		= "gbs";
	const GET_LOGIN_SLOT 		= "gls";
	const CREATE_LOGIN_QR 		= "clq";
	const EDIT_USER_PASS 		= "eup";
	const EXPRESS_PLAY 			= "exp";
	const EDIT_ODD	 			= "eod";
	const LOGIN_OUT	 			= "lot";
	const SUSPEND	 			= "lus";
	const START_STOP 			= "stp";
	const TRANS_CREDIT_INT		= "tri";
	# end of Action Type
	
	const DATA_FEEDS_URI_MAP = array(		
		"GET_TOKEN" 				=> "dataFeed/getToken.lt",
		"GET_SUM" 					=> "dataFeed/getSum.lt",
		"POKER_TYPE_RESULT" 		=> "dataFeed/pokerTypeResult.lt",
		"REMEDY_POKER_TYPE_RESULT" 	=> "dataFeed/remedyPokerTypeResult.lt",
		"BETS_SLOTS_DATA" 			=> "dataFeed/betsSlotData.lt",
		"EXP_BETS_DATA" 			=> "dataFeed/expBetsData.lt",
		"BETS_DATA" 				=> "dataFeed/betsData.lt",
		"REMEDY_BETS_DATA" 			=> "dataFeed/remedyBetsData.lt",
		"REPORT_DATA" 				=> "dataFeed/reportData.lt",
	);
	
	const TRANSFER_IN 	= 2;		// Deposit
	const TRANSFER_OUT 	= 4;		// Withdraw
	const TRANSFER_DESC = array( 
		self::TRANSFER_IN => "Transfer In",
		self::TRANSFER_OUT => "Transfer Out"
	);
	const DEMO_MODE_LIST = array("demo", "trial", "fun");

	const PLAY_TYPE = array(
		1 => "Banker",
		2 => "Player",
		3 => "Tie",
		4 => "Banker pair",
		5 => "Player pair",
		6 => "Big",
		7 => "Small",
		8 => "Banker-no fees",
		9 => "Dragon",
		10 => "Tiger",
		11 => "Tie",
		12 => "1 point",
		13 => "2 points",
		14 => "3 points",
		15 => "Single point 4",
		16 => "Single point 5",
		17 => "Single point 6",
		18 => "Double 1",
		19 => "Double 2",
		20 => "Double 3",
		21 => "Double 4",
		22 => "Double 5",
		23 => "Double 6",
		24 => "Triple 1",
		25 => "Triple 2",
		26 => "Triple 3",
		27 => "Triple 4",
		28 => "Triple 5",
		29 => "Triple 6",
		30 => "Combination",
		31 => "Small",
		32 => "Big",
		33 => "Single",
		34 => "Pair",
		35 => "Pai Gow 12",
		36 => "Pai Gow 13",
		37 => "Pai Gow 14",
		38 => "Pai Gow 15",
		39 => "Pai Gow 16",
		40 => "Pai Gow 23",
		41 => "Pai Gow 24",
		42 => "Pai Gow 25",
		43 => "Pai Gow 26",
		44 => "Pai Gow 34",
		45 => "Pai Gow 35",
		46 => "Pai Gow 36",
		47 => "Pai Gow 45",
		48 => "Pai Gow 46",
		49 => "Pai Gow 56",
		50 => "Point 4",
		51 => "Point 5",
		52 => "Point 6",
		53 => "Point 7",
		54 => "Point 8",
		55 => "Point 9",
		56 => "Point 10",
		57 => "Point 11",
		58 => "Point 12",
		59 => "Point 13",
		60 => "Point 14",
		61 => "Point 15",
		62 => "Point 16",
		63 => "Point 17",
		71 => "Player1平倍",
		72 => "Player2平倍",
		73 => "Player3平倍",
		81 => "Player1翻倍",
		82 => "Player2翻倍",
		83 => "Player3翻倍",
		100 => "直接注0",
		101 => "直接注1",
		102 => "直接注2",
		103 => "直接注3",
		104 => "直接注4",
		105 => "直接注5",
		106 => "直接注6",
		107 => "直接注7",
		108 => "直接注8",
		109 => "直接注9",
		110 => "直接注10",
		111 => "直接注11",
		112 => "直接注12",
		113 => "直接注13",
		114 => "直接注14",
		115 => "直接注15",
		116 => "直接注16",
		117 => "直接注17",
		118 => "直接注18",
		119 => "直接注19",
		120 => "直接注20",
		121 => "直接注21",
		122 => "直接注22",
		123 => "直接注23",
		124 => "直接注24",
		125 => "直接注25",
		126 => "直接注26",
		127 => "直接注27",
		128 => "直接注28",
		129 => "直接注29",
		130 => "直接注30",
		131 => "直接注31",
		132 => "直接注32",
		133 => "直接注33",
		134 => "直接注34",
		135 => "直接注35",
		136 => "直接注36",
		138 => "分注0-1",
		139 => "分注0-2",
		140 => "分注0-3",
		141 => "分注1-2",
		142 => "分注1-4",
		143 => "分注2-3",
		144 => "分注2-5",
		145 => "分注3-6",
		146 => "分注4-5",
		147 => "分注4-7",
		148 => "分注5-6",
		149 => "分注5-8",
		150 => "分注6-9",
		151 => "分注7-8",
		152 => "分注7-10",
		153 => "分注8-9",
		154 => "分注8-11",
		155 => "分注9-12",
		156 => "分注10-11",
		157 => "分注10-13",
		158 => "分注11-12",
		159 => "分注11-14",
		160 => "分注12-15",
		161 => "分注13-14",
		162 => "分注13-16",
		163 => "分注14-15",
		164 => "分注14-17",
		165 => "分注15-18",
		166 => "分注16-17",
		167 => "分注16-19",
		168 => "分注17-18",
		169 => "分注17-20",
		170 => "分注18-21",
		171 => "分注19-20",
		172 => "分注19-22",
		173 => "分注20-21",
		174 => "分注20-23",
		175 => "分注21-24",
		176 => "分注22-23",
		177 => "分注22-25",
		178 => "分注23-24",
		179 => "分注23-26",
		180 => "分注24-27",
		181 => "分注25-26",
		182 => "分注25-28",
		183 => "分注26-27",
		184 => "分注26-29",
		185 => "分注27-30",
		186 => "分注28-29",
		187 => "分注28-31",
		188 => "分注29-30",
		189 => "分注29-32",
		190 => "分注30-33",
		191 => "分注31-32",
		192 => "分注31-34",
		193 => "分注32-33",
		194 => "分注32-35",
		195 => "分注33-36",
		196 => "分注34-35",
		197 => "分注35-36",
		201 => "街注1-2-3",
		202 => "街注4-5-6",
		203 => "街注7-8-9",
		204 => "街注10-11-12",
		205 => "街注13-14-15",
		206 => "街注16-17-18",
		207 => "街注19-20-21",
		208 => "街注22-23-24",
		209 => "街注25-26-27",
		210 => "街注28-29-30",
		211 => "街注31-32-33",
		212 => "街注34-35-36",
		215 => "三数0-1-2",
		216 => "三数0-2-3",
		221 => "角注1-2-4-5",
		222 => "角注2-3-5-6",
		223 => "角注4-5-7-8",
		224 => "角注5-6-8-9",
		225 => "角注7-8-10-11",
		226 => "角注8-9-11-12",
		227 => "角注10-11-13-14",
		228 => "角注11-12-14-15",
		229 => "角注13-14-16-17",
		230 => "角注14-15-17-18",
		231 => "角注16-17-19-20",
		232 => "角注17-18-20-21",
		233 => "角注19-20-22-23",
		234 => "角注20-21-23-24",
		235 => "角注22-23-25-26",
		236 => "角注23-24-26-27",
		237 => "角注25-26-28-29",
		238 => "角注26-27-29-30",
		239 => "角注28-29-31-32",
		240 => "角注29-30-32-33",
		241 => "角注31-32-34-35",
		242 => "角注32-33-35-36",
		250 => "四数0123",
		261 => "线注1(1 2 3 4 5 6)",
		262 => "线注2(4 5 6 7 8 9 )",
		263 => "线注3(7 8 9 10 11 12)",
		264 => "线注4(10 11 12 13 14 15)",
		265 => "线注5(13 14 15 16 17 18)",
		266 => "线注6(16 17 18 19 20 21)",
		267 => "线注7(19 20 21 22 23 24)",
		268 => "线注8(22 23 24 25 26 27)",
		269 => "线注9(25 26 27 28 29 30)",
		270 => "线注10(28 29 30 31 32 33)",
		271 => "线注11(31 32 33 34 35 36)",
		281 => "列注1",
		282 => "列注2",
		283 => "列注3",
		286 => "第一打",
		287 => "第二打",
		288 => "第三打",
		301 => "红",
		302 => "黑",
		303 => "单",
		304 => "双",
		305 => "Big",
		306 => "Small",
		401 => "单",
		402 => "双",
		411 => "1番",
		412 => "2番",
		413 => "3番",
		414 => "4番",
		421 => "1念2",
		422 => "1念3",
		423 => "1念4",
		424 => "2念1",
		425 => "2念3",
		426 => "2念4",
		427 => "3念1",
		428 => "3念2",
		429 => "3念4",
		430 => "4念1",
		431 => "4念2",
		432 => "4念3",
		441 => "角12",
		442 => "角23",
		443 => "角34",
		444 => "角41",
		451 => "23通1",
		452 => "24通1",
		453 => "34通1",
		454 => "13通2",
		455 => "14通2",
		456 => "34通2",
		457 => "12通3",
		458 => "14通3",
		459 => "24通3",
		460 => "12通4",
		461 => "13通4",
		462 => "23通4",
		471 => "三门432",
		472 => "三门143",
		473 => "三门214",
		474 => "三门321"
	);

	const GAME_TYPE = array (
		1 => "Baccarat",
		2 => "Sic Bo",
		3 => "Dragon & Tiger",
		4 => "Roulette",
		5 => "Fan-Tan",
		6 => "Niuniu"
	);

	public function __construct() {
		parent::__construct();
		$this->url = $this->getSystemInfo('url');
		$this->data_feeds_url = $this->getSystemInfo('data_feeds_url');
		$this->currency = $this->getSystemInfo('currency');
		$this->agent = $this->getSystemInfo('agent');		
		$this->key = $this->getSystemInfo('key');		
		$this->agent_prefix = $this->getSystemInfo('agent_prefix');
		$this->isLive = $this->getSystemInfo('live_mode');
		$this->demo_suffix = $this->getSystemInfo('demo_username_suffix');
		$this->getTokenUrl = $this->getSystemInfo('get_token_url');
		$this->sync_time_interval = $this->getSystemInfo("sync_time_interval", "+2 hours");
	}

	public function getPlatformCode() {
		return LD_CASINO_API;
	}

	public function getHttpHeaders($params){
		return array("Content-Type" => "application/json");
	}

	protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params,true));		
	}

	public function generateUrl($url, $params) {
		$domain = $this->url;

		if (!empty($url)) {
			$domain = $url;			
		}

		// echo $domain . "?" . http_build_query($params);
		return $domain . "?" . http_build_query($params);
	}

	public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
		$success = false;
		if(isset($resultArr['ec']) && $resultArr['ec'] == '0'){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('LD Casino got error ', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
		}

		return $success;
	}

	public function isPlayerExist($userName, $extra = null){        
		$playerName = $this->getGameUsernameByPlayerUsername($userName);

		if ($this->isDemoMode($extra)) {
            $playerName .= $this->demo_suffix;
        }

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist', 
			'playerName' => $playerName,
			'sbe_userName' => $userName
		);
		
		$api_params = array(
			'ac' => self::CHECK_USER_EXIST,
			'uname' => $playerName,
		);

		# encrypt value parameters
		$encrypted_params = $this->rc4($this->key, http_build_query($api_params));

		$params = array (
			'agent' => $this->agent,
			'val' => $encrypted_params,
		);	

        return $this->callApi(null, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
    	$success = false;
        $responseResultId = $this->getResponseResultIdFromParams($params);
      	$resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $sbe_userName = $this->getVariableFromContext($params, 'sbe_userName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $playerId = $this->getPlayerIdInPlayer($sbe_userName);

        # something wrong with the API
        if(empty($resultArr)){
        	$result = array('exists' => null);
        }else{
        	$success = true;        	
	        if ($resultArr['ec']=="0") {
	        	if (!empty($resultArr['data']['isExist'])) {
					$result = array('exists' => true);
					# update fqlag to registered = true
	        		$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE); 
	        	} else {
	        		$result = array('exists' => false); # Player not found	
	        	}
	        } else {
	        	$result = array('exists' => null);
	        }
	    }

        return array($success, $result);
    }

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);		
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		if ($this->isDemoMode($extra)) {
            $playerName .= $this->demo_suffix;
        }

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId
        );

		$api_params = array(
			'ac' => self::ONLY_CREATE_USER,
			'uname' => $playerName,
			'upass' => $password,
			'ctype' => $this->currency,
			'istry' => (int) $this->isDemoMode($extra),							// trial user account ?			
		);	

		# encrypt value parameters
		$encrypted_params = $this->rc4($this->key, http_build_query($api_params));

		$params = array (
			'agent' => $this->agent,
			'val' => $encrypted_params,
		);		
		
		return $this->callApi(null, $params, $context);
	}

	public function processResultForCreatePlayer($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);

		$result = array(
			"message" => $resultArr['msg'],
		);

		return array($success, $result);
	}
	
	public function changePassword($userName, $oldPassword = null, $newPassword) {
		$playerName = $this->getGameUsernameByPlayerUsername($userName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'sbe_userName' => $userName,
			'playerName' => $playerName,
			'newPassword' => $newPassword
		);

		$api_params = array(
			'ac' => self::EDIT_USER_PASS,
			'uname' => $playerName,
			'upass' => $newPassword,
		);

		# encrypt value parameters
		$encrypted_params = $this->rc4($this->key, http_build_query($api_params));

		$params = array (
			'agent' => $this->agent,
			'val' => $encrypted_params,
		);

		return $this->callApi(null, $params, $context);
	}

	public function processResultForChangePassword($params){
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$sbe_userName = $this->getVariableFromContext($params, 'sbe_userName');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$newPassword = $this->getVariableFromContext($params, 'newPassword');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $sbe_userName);
		
		if ($success) {
			$playerId = $this->getPlayerIdInPlayer($sbe_userName);
			//sync password to game_provider_auth
			$this->updatePasswordForPlayer($playerId, $newPassword);
		}

		$result = array(
			"player" => $playerName
		);

		return array($success, $result);
	}

	public function queryPlayerBalance($userName) {
		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$playerPass = $this->getPassword($userName);
		
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance', 
			'playerName' => $playerName
		);

		$api_params = array(
			'ac' => self::GET_BALANCE,
			'uname' => $playerName,
			'upass' => $playerPass['password'],
		);

		# encrypt value parameters
		$encrypted_params = $this->rc4($this->key, http_build_query($api_params));

		$params = array (
			'agent' => $this->agent,
			'val' => $encrypted_params,
		);

		return $this->callApi(null, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

		$result = array();
		if($success){
			$result['balance'] = $this->convertYuanAmountInSBE(@floatval($resultArr['data']['balance']));
		}

		return array($success, $result);
	}

	public function depositToGame($userName, $amount, $transfer_secure_id=null){
		return $this->transferCredit($userName, $amount, self::TRANSFER_IN, $transfer_secure_id);
	}

	public function withdrawFromGame($userName, $amount, $transfer_secure_id=null){		
		return $this->transferCredit($userName, $amount, self::TRANSFER_OUT, $transfer_secure_id);
	}

	public function transferCredit($userName, $amount,$type, $transfer_secure_id=null) {
		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$playerPass = $this->getPassword($userName);

		$orderId = md5($this->getSecureId('transfer_request', 'secure_id', false, 'T')); 	// this api need character length from 20 - 40
        // $orderId = $transfer_secure_id ? $transfer_secure_id : $secure_id;
        
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForTransferCredit',
			'playerName' => $playerName,
			'sbe_playerName' => $userName,
			'amount' => $amount,
			'type' => $type
		);

		$api_params = array(
			'ac' => self::TRANS_CREDIT ,
			'uname' => $playerName,
			'upass' => $playerPass['password'],
			'amount' => $this->convertYuanAmountInApi($amount),
			'oid' => $this->agent_prefix.$orderId,
			'otype' => $type,
		);
		
		$this->CI->utils->debug_log('transfer params', '===============> params :  '. implode(">", $api_params).' type :' . $type);

		# encrypt value parameters
		$encrypted_params = $this->rc4($this->key, http_build_query($api_params));

		$params = array (
			'agent' => $this->agent,
			'val' => $encrypted_params,
		);

		return $this->callApi(null, $params, $context);
	}

	public function processResultForTransferCredit($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
		$type = $this->getVariableFromContext($params, 'type');
		$amount = $this->getVariableFromContext($params, 'amount');
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

		if ($success) {
            $playerId = $this->getPlayerIdInGameProviderAuth($playerName);

            if ($playerId) {
                $playerBalance = $this->queryPlayerBalance($sbe_playerName);
                $afterBalance = 0;

              	if ($playerBalance && $playerBalance['success']) {
                    $afterBalance = $playerBalance['balance'];
                } else {
                    //IF GET PLAYER BALANCE FAILED
                    $rlt = $this->CI->wallet_model->getSubWalletBy($playerId, $this->getPlatformCode());
                    $afterBalance = $rlt->totalBalanceAmount;
                    $this->CI->utils->debug_log('============= ' . self::GAME_PROVIDER . ' PLAY AFTER BALANCE FROM WALLET '.self::TRANSFER_DESC[$type].' ######### ', $afterBalance);
                }

				if ($type == self::TRANSFER_IN) {
                	$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());
                }

                if ($type == self::TRANSFER_OUT) {
                	$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());                
                }
            } else {
                $this->CI->utils->debug_log('error', '=============== cannot get player id from '.$playerName.' getPlayerIdInGameProviderAuth');
            }
        }

        $result = array(
        	'playerName' => $playerName
        );

        return array($success, $result);

	}
	
    public function login($userName, $password = null, $extra = null) {    	
		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$playerPass = $this->getPassword($userName);

		if ($this->isDemoMode($extra)) {
            $playerName .= $this->demo_suffix;
        }
		
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $playerName,
		);

		$api_params = array(
			'ac' => self::CREATE_LOGIN_USER,
			'uname' => $playerName,
			'upass' => $playerPass['password'],
			'ctype' => $this->currency,
			'istry' => $this->isDemoMode($extra),							// trial user account ?			
		);

		# encrypt value parameters
		$encrypted_params = $this->rc4($this->key, http_build_query($api_params));

		$params = array (
			'agent' => $this->agent,
			'val' => $encrypted_params,
		);		
		
		return $this->callApi(null, $params, $context);
	}

	public function processResultForLogin($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$result = array();
		$result['url'] = '';
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		
		if($success){
			$result['url'] = $resultArr['data']['url'];
		}

		return array($success, $result);
	}

	public function generateQRLogin($userName, $password = null, $extra = null) {    	
		$playerName = $this->getGameUsernameByPlayerUsername($userName);
		$playerPass = $this->getPassword($userName);
		
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGenerateQRLogin',
			'playerName' => $playerName,
		);
		
		$api_params = array(
			'ac' => self::CREATE_LOGIN_QR,
			'uname' => $playerName,
			'upass' => $playerPass['password'],
			'expTime' => 120,
		);

		# encrypt value parameters
		$encrypted_params = $this->rc4($this->key, http_build_query($api_params));

		$params = array (
			'agent' => $this->agent,
			'val' => $encrypted_params,
		);		
		
		return $this->callApi(null, $params, $context);
	}

	public function processResultForGenerateQRLogin($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		
		$result = array();
		$result['url'] = '';
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		
		if($success){
			$result['url'] = $resultArr['data']['url'];
		}

		return array($success, $result);
	}

	public function queryForwardGame($userName,$extra=null) {
		$result = null;

		if (!$extra['is_mobile']) {
			$result = $this->login($userName, null, $extra);
		} else {
			$result = $this->generateQRLogin($userName, null, $extra);
		}

		return $result["success"] ? array('url' => $result['url']) : null;		
	}

	public function syncOriginalGameLogs($token = false) {		
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
    	$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
    	$startDate->modify($this->getDatetimeAdjust());

		//observer the date format
        $queryDateTimeStart = $startDate->format('d-m-Y H:i:s');
        $queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('d-m-Y H:i:s');
        $queryDateTimeMax = $endDate->format('d-m-Y H:i:00');

        $result = [];
        
        while ($queryDateTimeMax  > $queryDateTimeStart) {        	
            $startDateParam=new DateTime($queryDateTimeStart);
            if($queryDateTimeEnd>$queryDateTimeMax){
                $endDateParam=new DateTime($queryDateTimeMax);
            }else{
                $endDateParam=new DateTime($queryDateTimeEnd);
            }
            $startDateParam = $startDateParam->format('d-m-Y H:i:00');
            $endDateParam = $endDateParam->format('d-m-Y H:i:00');

            $result['QueryResul : '.$startDateParam.' to '.$endDateParam] = $this->syncLDCasinoGameLogs($startDateParam, $endDateParam, $this->getToken());
            
            $queryDateTimeStart = $endDateParam;
            $queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('d-m-Y H:i:00');
            
        }

        return array_merge(array("success"=>true),array("details"=>$result));

	}

	public function syncLDCasinoGameLogs($startDate, $endDate, $token) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncOriginalGameLogs',
			'startDate' => $startDate,
			'endDate' => $endDate
		);

		// The data period must be >= 60 seconds, <= 7200 seconds, request more times if the period is exceeded		
		$params = array (
			'tk' => $token['token'],
			'sTime' => strtotime($startDate),			// Start Date/Time			
			'eTime' => strtotime($endDate),				// end Date/Time			
		);

		return $this->callApi($this->data_feeds_url . self::DATA_FEEDS_URI_MAP["BETS_DATA"], $params, $context);
	}

	public function processResultForSyncOriginalGameLogs($params) {
		$dataCount = 0;
		$result = array();
		$resultArr = $this->getResultJsonFromParams($params);

		$this->CI->load->model(array('ld_casino_game_logs'));
		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		
		$gameRecords = isset($resultArr['data']) ? $resultArr['data'] : array();

		if($success && !empty($gameRecords)) {
			$dataCount = $this->syncRecords($gameRecords, $responseResultId);
		}

		
		// $availableRows = $this->CI->ld_casino_game_logs->getAvailableRows($gameRecords);		

		// if($success && !empty($availableRows)){			
		// 	foreach ($availableRows as $record) {
		// 		$playerID = $this->getPlayerIdInGameProviderAuth(strtolower($record['userLoginName']));
        // 		$playerUsername = $this->getGameUsernameByPlayerId($playerID);

		// 		$insertRecord = array();
		// 		//Data from DG API
		// 		$insertRecord['bet_id'] = isset($record['betId']) ? $record['betId'] : NULL;
		// 		$insertRecord['round_id'] = isset($record['roundId']) ? $record['roundId'] : NULL;
		// 		$insertRecord['play_type'] = isset($record['playType']) ? $record['playType'] : NULL;
		// 		$insertRecord['user_login_name'] = isset($record['userLoginName']) ? $record['userLoginName'] : NULL;
		// 		$insertRecord['game_type'] = isset($record['gameType']) ? $record['gameType'] : NULL;
		// 		$insertRecord['account_type'] = isset($record['accountType']) ? $record['accountType'] : NULL;
		// 		$insertRecord['amount'] = isset($record['amount']) ? $this->convertYuanAmountInSBE($record['amount']) : NULL;
		// 		$insertRecord['valid_bet'] = isset($record['validBet']) ? $this->convertYuanAmountInSBE($record['validBet']) : NULL;
		// 		$insertRecord['win_lose'] = isset($record['winLose']) ? $this->convertYuanAmountInSBE($record['winLose']) : NULL;
		// 		$insertRecord['bet_type'] = isset($record['betType']) ? $record['betType'] : NULL;
		// 		$insertRecord['bet_time'] = isset($record['betTime']) ? $this->gameTimeToServerTime($record['betTime']) : NULL;
		// 		$insertRecord['calcu_time'] = isset($record['calcuTime']) ? $this->gameTimeToServerTime($record['calcuTime']) : NULL;				

		// 		//extra info from SBE
		// 		$insertRecord['player_id'] = isset($playerID) ? $playerID : NULL;
		// 		$insertRecord['username'] = isset($playerUsername) ? $playerUsername : NULL;
		// 		$insertRecord['external_uniqueid'] = isset($record['betId']) ? $record['betId'] : NULL;
		// 		$insertRecord['response_result_id'] = $responseResultId;				
				


		// 		//insert data to Pragmatic Play gamelogs table database
		// 		$this->CI->ld_casino_game_logs->insertGameLogs($insertRecord);				
		// 		$dataCount++;
		// 	}
		// }

		$result['data_count'] = $dataCount;
		return array($success, $result);
	}

	public function syncRecords($gameRecords, $responseResultId) {        
        $round_ids = array();
        $externalUniqueIds = array();
        $map_external_to_round_id = array();
        $map = array();
        $count = 0;        

        if (!empty($gameRecords)) {
            $data = array();

            foreach ($gameRecords as $row) {
            	# check multiple bets or same round id but diffent player on the same round
                if (!in_array($row['roundId'], $round_ids) || 
                	(in_array($row['roundId'], $round_ids) && isset($map[$map_external_to_round_id[$row['roundId']]]) &&
                		($map[$map_external_to_round_id[$row['roundId']]]['user_login_name'] != $row['userLoginName'])
                	))
               	{
                    array_push($round_ids, $row['roundId']);
                    array_push($externalUniqueIds, $row['betId']);
                    $betDetails = array();

                    $data['bet_id']         = isset($row['betId']) ? $row['betId'] : NULL;
                    $data['round_id']       = isset($row['roundId']) ? $row['roundId'] : NULL;
                    $data['play_type']      = isset($row['playType']) ? $row['playType'] : NULL;
                    $data['user_login_name'] = isset($row['userLoginName']) ? $row['userLoginName'] : NULL;
                    $data['game_type']      = isset($row['gameType']) ? $row['gameType'] : NULL;
                    $data['account_type']   = isset($row['accountType']) ? $row['accountType'] : NULL;
                    $data['amount'] 		= isset($row['amount']) ? $this->convertYuanAmountInSBE($row['amount']) : NULL;
					$data['valid_bet'] 		= isset($row['validBet']) ? $this->convertYuanAmountInSBE($row['validBet']) : NULL;
					$data['win_lose'] 		= isset($row['winLose']) ? $this->convertYuanAmountInSBE($row['winLose']) : NULL;
					$data['bet_type'] 		= isset($row['betType']) ? $row['betType'] : NULL;
					$data['bet_time'] 		= isset($row['betTime']) ? $this->gameTimeToServerTime($row['betTime']) : NULL;
					$data['calcu_time'] 	= isset($row['calcuTime']) ? $this->gameTimeToServerTime($row['calcuTime']) : NULL;

                    $extra_win_amount = $data['win_lose'] > 0 ? $data['win_lose'] : 0;
                    $won_side = $data['win_lose'] > 0 ? "Yes" : "No";
                  
					$betDetails['bet_details'][$row['betId']] = array(
                    	"odds" => null,
                    	'win_amount' => $extra_win_amount,
                    	'bet_amount' => $data['amount'],
                    	"bet_placed" => self::PLAY_TYPE[$data['play_type']],
                    	"won_side" => $won_side,
                    	"winloss_amount" => $data['win_lose'],
                    );
                    $betDetails['isMultiBet'] = false;
                    $data['extra'] = json_encode($betDetails);

                    $playerID = $this->getPlayerIdInGameProviderAuth(strtolower($row['userLoginName']));
  		            $playerUsername = $this->getGameUsernameByPlayerId($playerID);

                    //extra info from SBE
                    $data['player_id']      = isset($playerID) ? $playerID : NULL;
                    $data['username']       = isset($playerUsername) ? $playerUsername : NULL;
                    $data['external_uniqueid'] = isset($row['betId']) ? $row['betId'] : NULL;
                    $data['response_result_id'] = $responseResultId;

                    if (!isset($map[$row['betId']])) {
                        $map[$row['betId']] = $data;
                        $map_external_to_round_id[$data['round_id']]  = $data['bet_id'];
                    }
                } else {
                	# merge amount, valid bet, win lose, and valid bet then add it extra info if multiple bets
					$tmp_data = $map[$map_external_to_round_id[$row['roundId']]];
					$extra = array();
                	$extra = json_decode($tmp_data['extra'], true);

                	$extra_win_amount = $row['winLose'] > 0 ? $this->convertYuanAmountInSBE($row['winLose']) : 0;
                	$won_side = $row['winLose'] > 0 ? "Yes" : "No";
					
					$extra['bet_details'][$row['betId']] = array(
						"odds" => null,
						'win_amount' => $extra_win_amount,
						'bet_amount' => $this->convertYuanAmountInSBE($row['amount']),
						"bet_placed" => self::PLAY_TYPE[$row['playType']],
						"won_side" => $won_side,
						"winloss_amount" => $this->convertYuanAmountInSBE($row['winLose']),
					);
					$extra['isMultiBet'] = true;
                	
                	$map[$map_external_to_round_id[$row['roundId']]]['amount'] += $this->convertYuanAmountInSBE($row['amount']);
                    $map[$map_external_to_round_id[$row['roundId']]]['valid_bet'] += $this->convertYuanAmountInSBE($row['validBet']);
                    $map[$map_external_to_round_id[$row['roundId']]]['win_lose'] += $this->convertYuanAmountInSBE($row['winLose']);   
                	$map[$map_external_to_round_id[$row['roundId']]]['extra'] = json_encode($extra);
                }
            }
        }

        $existingRecord = $this->CI->ld_casino_game_logs->getExistingBetIds($externalUniqueIds);       

        # Update data
        if (!empty($existingRecord)) {
        	foreach ($existingRecord as $rec) {        		
        		$this->CI->ld_casino_game_logs->updateGameLog($rec['id'], $map[$rec['bet_id']]);  
        		unset($map[$rec['bet_id']]);
        		$count++;
        	}	
       }

        # insert data
        if (!empty($map)) {
        	foreach ($map as $key => $row) {
        		$this->CI->ld_casino_game_logs->insertGameLogs($row);
        		$count++;
        	}
        }

       	return $count;
    }


	public function syncMergeToGameLogs($token) {

		$this->CI->load->model(array('game_logs', 'player_model', 'ld_casino_game_logs'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		//observer the date format
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');

		$rlt = array('success' => true);

		$result = $this->CI->ld_casino_game_logs->getGameLogStatistics($startDate, $endDate);

		$cnt = 0;
		if (!empty($result)) {
			$unknownGame = $this->getUnknownGame();

			foreach ($result as $row) {
				
				$cnt++;
				
				$game_description_id = $row->game_description_id;
				$game_type_id = $row->game_type_id;
				$betAmount = $row->real_bet_amount;
				$real_bet_amount = $row->bet_amount;

				if (empty($game_description_id)) {
					$game_description_id = $unknownGame->id;
                	$game_type_id = $unknownGame->game_type_id;
				}

				$extraData = json_decode($row->extra, true);
				$betType = $extraData['isMultiBet'] ? 'Combo Bet':'Single Bet';
				unset($extraData['isMultiBet']);

				$extra = array(
					'trans_amount' => $row->bet_amount,
					'table' => $row->round_id,
					'bet_details'  => json_encode($extraData),
                    'bet_type'     => $betType,
				);

                $this->syncGameLogs(
                    $game_type_id,
                    $game_description_id,
                    $row->game_code,
                    $row->game_type,
                    $row->game_name,
                    $row->player_id,
                    $row->username,
                    $row->real_bet_amount,
                    $row->result_amount,
                    null, // win_amount
                    null, // loss_amount
                    null, // after balance
                    0,    // has both side
                    $row->external_uniqueid,
                    $row->betTime, //start
                    $row->endTime, // end
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

	public function logout($userName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra) {
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

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);
	}
	

	public function getParams($params) {		
		$temp = array();	
		$val = null;

		if (!empty($params)) {
			foreach ($params as $key => $value) {				
				array_push($temp, $key . "=" . $value);				
			}
			$val = implode("&", $temp);
		}

		return $val;
	}

	public function rc4($pwd, $data)//$pwd密钥 $data需加密字符串
    {
        $key[] ="";
        $box[] ="";
		$cipher = "";     
        $pwd_length = strlen($pwd);
        $data_length = strlen($data);
     
        for ($i = 0; $i < 256; $i++)
        {
            $key[$i] = ord($pwd[$i % $pwd_length]);
            $box[$i] = $i;
        }
     
        for ($j = $i = 0; $i < 256; $i++)
        {
            $j = ($j + $box[$i] + $key[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
     
        for ($a = $j = $i = 0; $i < $data_length; $i++)
        {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
     
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
     
            $k = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }         
         return bin2hex($cipher);
    }

   public function getToken() { 
   		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultGetToken',
			'agent' => $this->agent,
		);

		# encrypt value parameters
		$encrypted_params = $this->rc4($this->key, $this->agent);

		$params = array (
			'loginName' => $this->agent,
			'key' => $encrypted_params,
		);

		return $this->callApi($this->data_feeds_url . self::DATA_FEEDS_URI_MAP["GET_TOKEN"], $params, $context);
   }

    public function processResultGetToken($params) {
    	$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr);
		
		$result = array(
			"token" => $resultArr['data']['tk'],
		);

		return array($success, $result);
    }

    public function convertYuanAmountInApi($amount) {
       return $amount * 100;      // ld casino back office
    }

    public function convertYuanAmountInSBE($amount) {
        return $amount / 100;      // smartbackend
    }

    public function isDemoMode($extra) {        
        return in_array(strtolower($extra["game_mode"]), self::DEMO_MODE_LIST);
    }
}

/*end of file*/