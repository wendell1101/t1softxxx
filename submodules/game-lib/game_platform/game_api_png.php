<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_png extends Abstract_game_api {

    public $force_game_launch_language;

	const SOAP_SUCCESS = 200;
	const GAME_BET = 3;
	const GAME_RELEASE = 4;
	const GAME_APPLICABLE_IN_ENGLISH_ONLY = ['scratchahoy','holeinone'];
	const CASINO_TRANSACTION_RESERVE = 3;//initial bet,not yet settled(PENDING)
	const STATUS_FAILED = 2;
	Const STATUS_VOID = 4;
	Const STATUS_SUCCESS = 1;

	const API_addFreegameOffers = 'AddFreegameOffers';
	const API_cancelFreegameOffer = 'CancelFreegame';

	public function __construct() {
		parent::__construct();
		$this->api_domain = $this->getSystemInfo('url');
		$this->currency = $this->getSystemInfo('currency','CNY'); // default currency CNY
		$this->country = $this->getSystemInfo('country','CN'); // default country CN
		$this->language = $this->getSystemInfo('language','zh_CN'); // default langauge zh_CN
		$this->force_game_launch_language = $this->getSystemInfo('force_game_launch_language', false); // override game language on player center
		$this->BrandId = $this->getSystemInfo('BrandId','TestBrand');
		$this->mobile_game_url = $this->getSystemInfo('mobile_game_url');
		$this->flash_game_url = $this->getSystemInfo('flash_game_url');
		$this->pid = $this->getSystemInfo('pid');
		$this->api_allowed_demo_without_login = $this->getSystemInfo('api_allowed_demo_without_login', false);
        $this->ProductGroup = $this->getSystemInfo('ProductGroup', false);
        //don't support
        $this->is_enabled_direct_launcher_url=$this->getSystemInfo('is_enabled_direct_launcher_url', false);
		$this->html_header=$this->getSystemInfo('html_header', 'PNG API');
		$this->origin=$this->getSystemInfo('origin', '');
        $this->CI->load->model(['original_game_logs_model']);

        $this->use_freegame_offer = $this->getSystemInfo('use_freegame_offer', false);
		$this->current_domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
	}

	public function getPlatformCode() {
		return PNG_API;
	}

	protected function getCallType($apiName, $params) {
		//overwrite in sub-class
		return self::CALL_TYPE_SOAP;
	}

	public function generateUrl($apiName, $params) {
		ini_set("soap.wsdl_cache_enabled", 0);
	    return 'file://'.realpath(dirname(__FILE__)).'/wsdl/'.PNG_API.'/'.$this->getSystemInfo('wsdl_filename', 'png_live_server.wsdl');
	}

	protected function makeSoapOptions($options) {
		//overwrite in sub-class
		$options['ignore_ssl_verify'] = $this->getSystemInfo('ignore_ssl_verify', true);
		$options['basic_auth_username'] = $this->getSystemInfo('api_username');
		$options['basic_auth_password'] = $this->getSystemInfo('api_password');

		return $options;

	}

	protected function generateSoapMethod($apiName, $params) {
		switch ($apiName) {
			case self::API_createPlayer:
				return array('RegisterUser', $params);
				break;

			case self::API_isPlayerExist:
				return array('Balance', $params);
				break;

			case self::API_depositToGame:
				return array('CreditAccount', $params);
				break;

			case self::API_withdrawFromGame:
				return array('DebitAccount', $params);
				break;

			case self::API_queryPlayerBalance:
				return array('Balance', $params);
				break;

			case self::API_login:
				return array('GetTicket', $params);
				break;

			case self::API_addFreegameOffers:
				return array('AddFreegameOffers', $params);
				break;

			case self::API_cancelFreegameOffer:
				return array('CancelFreegame', $params);
				break;

		}

		return parent::generateSoapMethod($apiName, $params);
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return array(false, null);

	}

	/*public function getGameTimeToServerTime() {
		//return '+8 hours';
	}*/

	/*public function getServerTimeToGameTime() {
		//return '-8 hours';
	}*/

	protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
	    return $errCode || intval($statusCode, 10) >= 600;
	}

	protected function processResultBoolean($responseResultId, $result, $playerName = null) {
		$success = false;
		if ($result == self::SOAP_SUCCESS) {
			$success = true;
		}
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('PNG got error', $responseResultId, 'playerName', $playerName, 'result', $result);
		}

		return $success;
	}

	private function convertSoapXmlToArray($response){
		if(!empty($response)){
			$response = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response);
			$xml = new SimpleXMLElement($response);
			$body = $xml->xpath('//sBody')[0];
			$newArr = json_decode(json_encode((array)$body), TRUE);

			return $newArr;
		}
		return array();
	}

	public function getReasonId($params){
		if (array_key_exists("sFault",$params)){
			$reason = $params['sFault']['detail']['ServiceFault']['ErrorId'];
			switch ($reason) {
			    case "InvalidCurrency":
			        return self::REASON_CURRENCY_ERROR;
			        break;
			    case "AccountLocked":
			    case "AccountDisabled":
			        return self::REASON_GAME_ACCOUNT_LOCKED;
			        break;
			    case "UnknownUser":
			        return self::REASON_NOT_FOUND_PLAYER;
			        break;
			    case "NotEnoughMoney":
			        return self::REASON_INVALID_TRANSFER_AMOUNT;
			        break;
			    default:
			        return self::REASON_UNKNOWN ;
			}
		}
    	return self::REASON_UNKNOWN ;
    }

	public function isPlayerExist($playerName) {
		$username = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId   = $this->getPlayerIdInGameProviderAuth($username);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'playerId' => $playerId
		);

		$params = array(
			"ExternalUserId" => empty($username)?$playerName:$username
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	public function checkIfOfferExists($RequestId) {
		$sql = <<<EOD
            SELECT id
            FROM png_free_game_offer
            WHERE RequestId = ?
EOD;
        $params = [
            $RequestId
        ];
    	$queryResult = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql,$params);
    	if (!empty($queryResult)) {
    		return true;
    	}
    	return false;
	}

	public function checkGameName($game_codes) {
		$sql = 'SELECT english_name FROM game_description WHERE game_platform_id = 244 AND game_code IN ';
		$game_code_query = '(';
		foreach ($game_codes as $game_code) {
			$game_code_query .= $game_code . ',';
		}
		$game_code_query = substr($game_code_query, 0, -1) . ')';
		$params = [];
		$sql .= $game_code_query;

    	$queryResult = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql,$params);
    	return $queryResult;
	}

	public function checkPlayerName($game_username) {
		$sql = 'SELECT p.username FROM player as p, game_provider_auth as g WHERE g.login_name = ? AND g.player_id = p.playerId';
        $params = [
            $game_username
        ];
    	$queryResult = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql,$params);
    	return $queryResult;
	}

	public function addFreegameOffers($extra) {
		$newGames = [];
		foreach ($extra['pngGames'] as $value) {
			$newGames[] = intval($value);
		}

		$date = new DateTime($this->serverTimeToGameTime($extra['expire_time']));
		$date = $date->format('Y-m-d\TH:i:s');

		$params = array(
			'UserId' 			  => $extra['pngPlayers'],
			'Rounds' 			  => (int) $extra['rounds'],
			'ExpireTime' 		  => $date,
			'FreegameExternalId ' => $extra['request_id_hidden'],
			'RequestId' 		  => $extra['request_id_hidden'],
			'GameIdList'  		  => $newGames
		);

		if (!empty($extra['denomination'])) {
			$params['Denomination'] = (float) $extra['denomination'];
		}

		if (!empty($extra['turnover'])) {
			$params['Turnover'] = (int) $extra['turnover'];
		}

		if (!empty($extra['lines'])) {
			$params['Lines'] = (int) $extra['lines'];
		}

		if (!empty($extra['coins'])) {
			$params['Coins'] = (int) $extra['coins'];
		}

		foreach ($params as $key => $value) {
			if (empty($value)) {
				$params[$key] = null;
			}
		}

		$context = array(
			'callback_obj'    => $this,
			'callback_method' => 'processResultForAddFreegameOffers',
			'data'	      	  => $params,
			'ExpireTime'	  => $extra['expire_time']
		);
		return $this->callApi(self::API_addFreegameOffers, $params, $context);
	}

	public function processResultForAddFreegameOffers($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$result['statusCode'] = $statusCode = $params['statusCode'];
		$result['resultText'] = $this->getResultTextFromParams($params);
		$response = $this->getResultTextFromParams($params);
        $responseArray = $this->convertSoapXmlToArray($response);
		$data = $this->getVariableFromContext($params, 'data');
		$ExpireTime = $this->getVariableFromContext($params, 'ExpireTime');
		$success = $this->processResultBoolean($responseResultId, $statusCode, null);

		$data['extra'] = json_encode($data);
		$date = new DateTime($ExpireTime);
		$data['ExpireTime'] = $date->format('Y-m-d H:i:s');
		$data['status'] = 'approve';

		$game_names = $this->checkGameName($data['GameIdList']);
		$player_name = $this->checkPlayerName($data['UserId']);

		$games = '';
		foreach ($game_names as $game) {
			$games .= $game['english_name'] . ', ';
		}
		$games = substr($games, 0, -2);

		$data['Username'] = !empty($player_name) ? $player_name[0]['username'] : null;
		$data['GameIdList'] = json_encode($data['GameIdList']);
		$data['GameNameList'] = $games;

		if (!empty($data['Lines'])) {
			$data['Line'] = $data['Lines'];
			unset($data['Lines']);
		}

		$exist = $this->checkIfOfferExists($data['RequestId']);
		if ($success && !$exist) {
	    	$this->CI->original_game_logs_model->insertRowsToOriginal('png_free_game_offer', $data);
		}

		$result['responseArray'] = $responseArray;

		$this->CI->utils->debug_log('PNG GAME processResultForAddFreegameOffers', $responseArray);
		return array($success, $result);
	}

	public function cancelFreegameOffer($request_id) {

		$params = array(
			"RequestId" 	=> $request_id,
			"ProductGroup" => $this->ProductGroup
		);

		$context = array(
			'callback_obj' 	  => $this,
			'callback_method' => 'processResultForcancelFreegameOffer',
			'data' 			  => $params,
		);
		return $this->callApi(self::API_cancelFreegameOffer, $params, $context);
	}

	public function processResultForcancelFreegameOffer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$data = $this->getVariableFromContext($params, 'data');
		$result['statusCode'] = $statusCode = $params['statusCode'];
		$result['resultText'] = $this->getResultTextFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $statusCode, null);

		$newData = ['status' => 'cancel', 'RequestId' => $data['RequestId']];
		if ($success) {
	    	$this->CI->original_game_logs_model->updateRowsToOriginal('png_free_game_offer', $newData, 'RequestId');
		}

		$this->CI->utils->debug_log('PNG GAME processResultForcancelFreegameOffer', $result['resultText']);
		return array($success, $result);
	}

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		$playerInfo = $this->getPlayerDetails($playerId);
		$bdate = date('Y-m-d', strtotime('1990-11-13')); #fix date due to 18 velow restrictions
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerId' => $playerId,
			'playerName' => $playerName,
		);

		$subParam = array(
			"ExternalUserId" => $playerName,
			"Username" => $playerName,
			"Nickname" => $playerName,
			"Currency" => $this->currency,
			"Country" => $this->country,
			"Birthdate" => $bdate,
			"Registration" => date("Y-m-d"),
			"BrandId" => $this->BrandId,
			"Language" => $this->language,
			"IP" => $this->CI->input->ip_address()
		);
		$params['UserInfo'] = $subParam;

		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$result['statusCode'] = $statusCode = $params['statusCode'];
		$result['resultText'] = $this->getResultTextFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $statusCode, $playerName);

		if ($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

		return array($success, $result);
	}

	public function processResultForIsPlayerExist($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
      	$statusCode = $params['statusCode'];
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $statusCode, $playerName);
        $response = $this->getResultTextFromParams($params);
        $result['details'] = $this->convertSoapXmlToArray($response);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        if ($success) {
        	$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        	$result['exists'] = true;
        }else{
        	if(isset($result['details']['sFault']['detail']['ServiceFault']['ErrorId'])){
        		$errorId = $result['details']['sFault']['detail']['ServiceFault']['ErrorId'];
	        	if($errorId == "UnknownUser"){
	        		# Player not found
	        		$success = true;
	        		$result['exists'] = false;
	        	}
	        	else{
	        		#api other error code
	        		$result['exists'] =false;
	        	}
        	} else {
        		$result['exists'] = false;
        	}

        }
        return array($success, $result);
    }

	public function queryPlayerBalance($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName
		);

		$params = array(
			"ExternalUserId" => empty($username)?$playerName:$username
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) {

		$playerName = $this->getVariableFromContext($params, 'playerName');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$statusCode = $params['statusCode'];
		$response = $this->getResultTextFromParams($params);
        $responseArray = $this->convertSoapXmlToArray($response);
		$success = $this->processResultBoolean($responseResultId, $statusCode, $playerName);

		if($success) {
			$result['exists'] = true;
			$result['balance'] = $this->gameAmountToDB(@floatval($responseArray['BalanceResponse']['UserBalance']['Real']));
			$this->CI->utils->debug_log('PNG GAME API query balance playerName', $playerName, 'balance', $result['balance']);
		}else{
			$result['exists'] = false;
			$this->CI->utils->debug_log('PNG GAME API cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
		}
		return array($success, $result);

	}

	public function depositToGame($userName, $amount, $transfer_secure_id=null){
        $playerName = $this->getGameUsernameByPlayerUsername($userName);

        $amount = $this->dBtoGameAmount($amount);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'amount' => $amount,
            // 'external_transaction_id' => 'dp'.$playerName.date("ymdHis")// not applicable
        );

      	$subParam = array(
			"ExternalUserId" => $playerName,
			"Amount" => $amount,
			"Currency" => $this->currency,
			"ExternalTransactionId" => 'dp'.$playerName.date("ymdHis"),
		);

        return $this->callApi(self::API_depositToGame, $subParam, $context);

	}

	public function processResultForDepositToGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);

        $statusCode = $params['statusCode'];
		$response = $this->getResultTextFromParams($params);
        $responseArray = $this->convertSoapXmlToArray($response);
        // $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $result = array('response_result_id' => $responseResultId, 'external_transaction_id' => null);
		$success = $this->processResultBoolean($responseResultId, $statusCode, $playerName);

        if ($success) {
			//for sub wallet
			// $result["currentplayerbalance"] = $afterBalance = $responseArray['CreditAccountResponse']['UserAccount']['Real'];
			// $result["external_transaction_id"] = $responseArray['CreditAccountResponse']['UserAccount']['TransactionId'];not applicable

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
		$result["transfer_status"] = ($success) ? self::COMMON_TRANSACTION_STATUS_APPROVED : self::COMMON_TRANSACTION_STATUS_DECLINED;
	    $result["reason_id"] = $this->getReasonId($responseArray);
		return array($success, $result);
    }

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

        $amount = $this->dBtoGameAmount($amount);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'amount' => $amount,
            // 'external_transaction_id' => 'dp'.$playerName.date("ymdHis")// not applicable
        );

      	$subParam = array(
			"ExternalUserId" => $playerName,
			"Amount" => $amount,
			"Currency" => $this->currency,
			"ExternalTransactionId" => 'wd'.$playerName.date("ymdHis"),
		);

        return $this->callApi(self::API_withdrawFromGame, $subParam, $context);
	}

	public function processResultForWithdrawFromGame($params) {
		$playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);

        $statusCode = $params['statusCode'];
		$response = $this->getResultTextFromParams($params);
        $responseArray = $this->convertSoapXmlToArray($response);
		$success = $this->processResultBoolean($responseResultId, $statusCode, $playerName);
		// $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $result = array('response_result_id' => $responseResultId, 'external_transaction_id' => null);
        if ($success) {
			//for sub wallet
			// $result["currentplayerbalance"] = $afterBalance = $responseArray['DebitAccountResponse']['UserAccount']['Real'];
			// $result["external_transaction_id"] = $responseArray['DebitAccountResponse']['UserAccount']['TransactionId']; // not applicable

			//update
			// $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			// if ($playerId) {
			// 	//Withdraw
			// 	$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,$this->transTypeSubWalletToMainWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
		}
		$result["transfer_status"] = ($success) ? self::COMMON_TRANSACTION_STATUS_APPROVED : self::COMMON_TRANSACTION_STATUS_DECLINED;
	    $result["reason_id"] = $this->getReasonId($responseArray);
		return array($success, $result);

	}

	public function login($playerName, $password = null) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName
        );

      	$subParam = array(
			"ExternalUserId" => $playerName
		);

        return $this->callApi(self::API_login, $subParam, $context);
	}

	public function processResultForLogin($params){
		$playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);

        $statusCode = $params['statusCode'];
		$response = $this->getResultTextFromParams($params);
        $responseArray = $this->convertSoapXmlToArray($response);
		$success = $this->processResultBoolean($responseResultId, $statusCode, $playerName);

		$result['ticket']='';
		if($success){
			$result['ticket'] = $responseArray['GetTicketResponse']['Ticket'];
		}

		return array($success, $result);
	}

	public function getLauncherLanguage($language){
		switch (strtolower($language)) {
            case Language_function::INT_LANG_ENGLISH:
			case "en-us":
            case Language_function::PROMO_SHORT_LANG_ENGLISH:
                return "en_GB";
                break;
            case Language_function::INT_LANG_CHINESE:
			case "zh-cn":
            case Language_function::PROMO_SHORT_LANG_CHINESE:
                return "zh_CN";
                break;

            case Language_function::INT_LANG_INDONESIAN:
			case "id-id":
            case Language_function::PROMO_SHORT_LANG_INDONESIAN:
                return "id_ID";
                break;
            case Language_function::INT_LANG_VIETNAMESE:
			case "vi-vn":
            case Language_function::PROMO_SHORT_LANG_VIETNAMESE:
                return "vi_VN";
                break;
            case Language_function::INT_LANG_KOREAN:
			case "ko-kr":
            case Language_function::PROMO_SHORT_LANG_KOREAN:
                return "ko_KR";
                break;
            case Language_function::INT_LANG_THAI:
            case "th-th":
            case Language_function::PROMO_SHORT_LANG_THAI:
                return "th_TH";
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case "pt-br":
            case Language_function::PROMO_SHORT_LANG_PORTUGUESE:
                return "pt_BR";
                break;
            case Language_function::INT_LANG_SPANISH:
            case "es_ES":
            case "es_es":
                return "es_ES";
            case Language_function::INT_LANG_JAPANESE:
            case "ja_JP":
            case "ja_jp":
                return "ja_JP";
                break;
			default:
                return "en_GB";
                break;
        }
    }

	public function queryForwardGame($playerName, $extra = null) {
		#https://agastage.playngonetwork.com/Casino/js?div=pngCasinoGame&gid=gemix&lang=en_GB&pid=281&practice=1&width=100%&height=100%&username=9-565O547P656O464W
		$lang = (in_array($extra['game_code'], self::GAME_APPLICABLE_IN_ENGLISH_ONLY)) ? 'en_GB' : $this->getLauncherLanguage($extra['language']);
		$params = array(
			'div' => 'pngCasinoGame',
			'gid' => $extra['game_code'],
			'lang' => $lang,
			'pid' => $this->pid,
			'practice' => $extra['game_mode']=='real'?0:1
		);

		$reload_url = $this->current_domain . '/player_center/goto_pnggame/' . $extra['game_code'] . '/' . $extra['game_mode'];
		$this->CI->utils->debug_log('RELOAD URL =====>', $reload_url);

		$is_mobile = isset($extra['is_mobile']) ? $extra['is_mobile'] : false;
		$player_url = $this->utils->getSystemUrl('player');
		$mobile_player_url = $this->utils->getSystemUrl('m');

        if (isset($extra['home_link']) && !empty($extra['home_link'])){
            $params['origin'] = $extra['home_link'];
        }
        else if (isset($extra['extra']['t1_lobby_url']) && !empty($extra['extra']['t1_lobby_url'])) {
            $params['origin'] = $extra['extra']['t1_lobby_url'];
        }else if (!empty($this->origin)){ /** The value of $this->origin must be a URL to the site which is embedding the game */
            $params['origin'] = $this->origin;
        }else{
            $params['origin'] = $this->getHomeLink();
        }

		## If client allowed launching demo game without player center login
		if ($this->api_allowed_demo_without_login && $extra['game_mode'] != 'real') {
			// do nothing, don't login since some client didn't explore allowing launching demo game without player center login
			// else = require to login, regardless if the game is real or dmeo
		} else {
			$ret = $this->login($playerName);
			$ticket = $ret['ticket'];
			if (!$extra['is_mobile']) { # flash
				$params['username'] = $ticket;
			} else {
				$params['ticket'] = $ticket;
			}
		}

		if ($this->force_game_launch_language !== false) {
			$params['lang'] = $this->force_game_launch_language;
		}

		if (!$is_mobile) { # flash
            $params['lobby'] = $params['origin'];
			$params['width'] = '100%';
			$params['height'] = '100%';
			$url = $this->flash_game_url.http_build_query($params);

		} else { # Mobile
            $params['lobby'] = $params['origin'];
			$url = $this->mobile_game_url.http_build_query($params);
			if ($this->use_freegame_offer) {
				$url .= '&reloadgame=' . $reload_url;
			}
		}

        if(!isset($extra['httphost'])){
            $extra['httphost'] = $this->getHomeLink();
        }

		# IF USE GAMEGATEWAY API
		if(isset($extra['extra']['token'])){
			$result['playforreal'] = $extra['extra']['httphost']."/player_center/launch_game_with_token/".$extra['extra']['token']."/".$this->getPlatformCode()."/".$extra['game_code']."/real/".$extra['language'];
		}else{
			$result['playforreal'] = $extra['httphost']."/iframe_module/goto_pnggame/".$extra['game_code'].'/real';
		}
		$result['reload_url'] = $reload_url;
		$result['success'] = true;
		$result['script_inc'] = $url;
		$result['html_header'] = $this->html_header;
		$result['origin'] = $params['origin'];
		$this->CI->utils->debug_log('Check url ====================================>', $url);
		return $result;
	}

	public function preparePngStreamGamelogs($messages=null){
		$preparedMessages = array();
		foreach ($messages as $message) {
			if($message['MessageType'] == 1 || $message['MessageType'] == 2){
				continue;
			}
			$insertMessages = array();
			$insertMessages['TransactionId'] = isset($message['TransactionId']) ? $message['TransactionId'] : NULL;
			$insertMessages['Status']= isset($message['Status']) ? $message['Status'] : NULL;
			$insertMessages['Amount'] = isset($message['Amount']) ? $message['Amount'] : NULL;
			$insertMessages['Time'] = isset($message['Time']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($message['Time']))) : NULL;
			$insertMessages['ProductGroup'] = isset($message['ProductGroup']) ? $message['ProductGroup'] : NULL;
			$insertMessages['ExternalUserId'] = isset($message['ExternalUserId']) ? $message['ExternalUserId'] : NULL;
			$insertMessages['GamesessionId'] = isset($message['GamesessionId']) ? $message['GamesessionId'] : NULL;
			$insertMessages['RoundId'] = isset($message['RoundId']) ? $message['RoundId'] : NULL;
			$insertMessages['GameId'] = isset($message['GameId']) ? $message['GameId'] : NULL;
			$insertMessages['Currency'] = isset($message['Currency']) ? $message['Currency'] : NULL;
			$insertMessages['ExternalTransactionId']= isset($message['ExternalTransactionId']) ? $message['ExternalTransactionId'] : NULL;
			$insertMessages['Balance'] = isset($message['Balance']) ? $message['Balance'] : NULL;
			$insertMessages['MessageId'] = isset($message['MessageId']) ? $message['MessageId'] : NULL;
			$insertMessages['MessageType'] = isset($message['MessageType']) ? $message['MessageType'] : NULL;
			$insertMessages['MessageTimestamp'] = isset($message['MessageTimestamp']) ? $message['MessageTimestamp'] : NULL;
			//extra info from SBE
			$insertMessages['Username'] = isset($message['ExternalUserId'])?$message['ExternalUserId']:NULL;
			$insertMessages['external_uniqueid'] = $insertMessages['TransactionId']; //add external_uniueid for og purposes
			$insertMessages['response_result_id'] = $insertMessages['ExternalTransactionId'];
			//info open release
			$insertMessages['GamesessionState'] = isset($message['GamesessionState']) ? $message['GamesessionState'] : NULL;
			$insertMessages['RoundData'] = isset($message['RoundData']) ? $message['RoundData'] : NULL;
			$insertMessages['RoundLoss'] = isset($message['RoundLoss']) ? $message['RoundLoss'] : NULL;
			$insertMessages['JackpotLoss'] = isset($message['JackpotLoss']) ? $message['JackpotLoss'] : NULL;
			$insertMessages['JackpotGain'] = isset($message['JackpotGain']) ? $message['JackpotGain'] : NULL;
			$insertMessages['TotalGain'] = isset($message['TotalGain']) ? $message['TotalGain'] : NULL;
			$insertMessages['ExternalFreegameId'] = isset($message['ExternalFreegameId']) ? $message['ExternalFreegameId'] : NULL;
			$insertMessages['NumRounds'] = isset($message['NumRounds']) ? $message['NumRounds'] : NULL;
			$insertMessages['TotalLoss'] = isset($message['TotalLoss']) ? $message['TotalLoss'] : NULL;

			array_push($preparedMessages,$insertMessages);
		}

		return $preparedMessages;
	}

	public function syncLDF($result = null, $platform = 'web') {
		$this->CI->load->model(array('png_game_logs'));

		$this->CI->utils->debug_log('Check request ====================================>', $result);
		if(empty($result) || !array_key_exists('Messages', $result)){
			$this->CI->utils->error_log("Invalid POST data, expecting [Messages]", $result);
			return "Invalid POST data, expecting [Messages]";
		}

		$messages = $result['Messages'];
		if(empty($messages)) {
			$this->CI->utils->error_log("Invalid POST data, empty Messages", $result);
			return "Invalid POST data, empty Messages";
		}

		# Prepare and insert all messages on png_stream_game_logs table.
		$preparedMessages = $this->preparePngStreamGamelogs($messages);

        if(empty($preparedMessages)) {
            return array("success" => true);
        }

		$this->CI->png_game_logs->insertBatchToPNGStreamGameLogs($preparedMessages);

		$resultJson = json_encode($result);
		$filePath = $this->saveMessageToFile($resultJson);
		$this->CI->utils->debug_log("Valid callback Message, saving to file", $filePath);

		# Filter for the records we wanted: MessageType = 3 or 4
		# CasinoTransactionReserve 3
		# CasinoTransactionReleaseOpen 4
		$gameRecords = array_filter($messages, function($v, $k){
			return in_array($v['MessageType'], [3,4]);
		}, ARRAY_FILTER_USE_BOTH);

		$this->CI->utils->error_log("After filter, game records: ", $gameRecords);

		if(empty($gameRecords)) {
			return array("success" => true);
		}

		# Only insert game records that does not already exist
		$newGameRecords = $this->CI->png_game_logs->getAvailableRows($gameRecords);
		$roundIds = [];
		foreach($newGameRecords as $data) {
			$roundId = $this->insertOriginalGameLogs($data);
			if(!empty($roundId)) {
				$roundIds[] = $roundId;
			}
		}

		$this->CI->utils->debug_log("Insert original game logs done, round IDs: ", $roundIds);

		// $roundIds = array_unique($roundIds);
		// if(!empty($roundIds)){
		// 	foreach ($roundIds as $roundId) {
		// 		$this->insertMergeToGameLogs($roundId);
		// 	}
		// }

		return array("success" => true);
	}

	private function saveMessageToFile($content) {
		$savePath = '/var/game_platform/png';
		$dir = $savePath . '/' . date('Y-m-d');
		//create dir
		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
			@chmod($dir, 0777);
		}
		$filename = $this->CI->utils->getDatetimeNow() . "_" . random_string('alnum', 8) . ".json";
		$f = $dir . '/' . $filename;
		file_put_contents($f, $content);
		return $f;
	}

	public function insertOriginalGameLogs($record = null) {

		$this->CI->load->model(array('png_game_logs', 'player_model'));

		$insertRecord 	= array();
		$playerID 		= $this->getPlayerIdInGameProviderAuth(strtolower($record['ExternalUserId']));
		if(empty($playerID)) {
			$this->CI->utils->error_log("Ignoring game record because player ID not found", $record);
			return 0;
		}

		$playerUsername = $record['ExternalUserId'];
		//Data from PNG API
		$insertRecord['TransactionId'] 			= isset($record['TransactionId']) ? $record['TransactionId'] : NULL;
		$insertRecord['Status'] 				= isset($record['Status']) ? $record['Status'] : NULL;
		$insertRecord['Amount'] 				= isset($record['Amount']) ? $record['Amount'] : NULL;
		$insertRecord['Time'] 					= isset($record['Time']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['Time']))) : NULL;
		$insertRecord['ProductGroup'] 			= isset($record['ProductGroup']) ? $record['ProductGroup'] : NULL;
		$insertRecord['ExternalUserId'] 		= isset($record['ExternalUserId']) ? $record['ExternalUserId'] : NULL;
		$insertRecord['GamesessionId'] 			= isset($record['GamesessionId']) ? $record['GamesessionId'] : NULL;
		$insertRecord['RoundId'] 				= isset($record['RoundId']) ? $record['RoundId'] : NULL;
		$insertRecord['GameId'] 				= isset($record['GameId']) ? $record['GameId'] : NULL;
		$insertRecord['Currency'] 				= isset($record['Currency']) ? $record['Currency'] : NULL;
		$insertRecord['ExternalTransactionId'] 	= isset($record['ExternalTransactionId']) ? $record['ExternalTransactionId'] : NULL;
		$insertRecord['Balance'] 				= isset($record['Balance']) ? $record['Balance'] : NULL;
		$insertRecord['MessageId'] 				= isset($record['MessageId']) ? $record['MessageId'] : NULL;
		$insertRecord['MessageType'] 			= isset($record['MessageType']) ? $record['MessageType'] : NULL;
		$insertRecord['MessageTimestamp'] 		= isset($record['MessageTimestamp']) ? $record['MessageTimestamp'] : NULL;
		//extra info from SBE
		$insertRecord['Username'] = $playerUsername;
		$insertRecord['PlayerId'] = $playerID;
		$insertRecord['external_uniqueid'] 	= $insertRecord['TransactionId']; //add external_uniueid for og purposes
		$insertRecord['response_result_id'] = $insertRecord['ExternalTransactionId'];
		//info open release
		$insertRecord['GamesessionState'] 	= isset($record['GamesessionState']) ? $record['GamesessionState'] : NULL;
		$insertRecord['RoundData'] 			= isset($record['RoundData']) ? $record['RoundData'] : NULL;
		$insertRecord['RoundLoss'] 			= isset($record['RoundLoss']) ? $record['RoundLoss'] : NULL;
		$insertRecord['JackpotLoss'] 		= isset($record['JackpotLoss']) ? $record['JackpotLoss'] : NULL;
		$insertRecord['JackpotGain'] 		= isset($record['JackpotGain']) ? $record['JackpotGain'] : NULL;
		$insertRecord['TotalGain'] 			= isset($record['TotalGain']) ? $record['TotalGain'] : NULL;
		$insertRecord['ExternalFreegameId'] = isset($record['ExternalFreegameId']) ? $record['ExternalFreegameId'] : NULL;
		$insertRecord['NumRounds'] 			= isset($record['NumRounds']) ? $record['NumRounds'] : NULL;
		$insertRecord['TotalLoss'] 			= isset($record['TotalLoss']) ? $record['TotalLoss'] : NULL;

		// $isExists = $this->CI->png_game_logs->isRowIdAlreadyExists($insertRecord['TransactionId']);
		// if ($isExists) {
		// 	$this->CI->png_game_logs->updateGameLogs($insertRecord);
		// } else {
			$this->CI->png_game_logs->insertGameLogs($insertRecord);
		// }
		return $insertRecord['RoundId'];
	}

	public function insertMergeToGameLogs($roundId) {

		$this->CI->load->model(array('png_game_logs', 'player_model','game_logs'));
		$gameBet 		= $this->CI->png_game_logs->getOriginalGameLogsByRoundId($roundId,self::GAME_BET);
		$gameRelease 	= $this->CI->png_game_logs->getOriginalGameLogsByRoundId($roundId,self::GAME_RELEASE);
		$betAmount 		= (!empty($gameBet)) ? $this->gameAmountToDB($gameBet['total_amount']) : $this->gameAmountToDB($gameRelease['RoundLoss']);
		$resultAmount 	= (!empty($gameRelease)) ? $this->gameAmountToDB((float)$gameRelease['Amount']  - (float)$gameRelease['RoundLoss']) : $this->gameAmountToDB(abs((float)$gameBet['total_amount'])*-1);
		$afterBalance 	= (!empty($gameRelease)) ? $this->gameAmountToDB($gameRelease['Balance']) : $this->gameAmountToDB($gameBet['Balance']);
		$result 		= (!empty($gameBet)) ? $gameBet : $gameRelease;

		// $result 		= (!empty($gameBet)) ? $gameBet : $gameRelease;
		// $gameBet RESPONSE RECORD WITH NULL FIELDS SO THE !empty RETURNS TRUE ALTHOUGH THE $gameBet IS NULL
		if (empty($gameBet['PlayerId']) && empty($gameBet['external_uniqueid']) && empty($gameBet['TransactionId'])) {
			$result = $gameRelease;
		}

		$start_at = (isset($gameBet['Time'])) ? $gameBet['Time'] : $gameRelease['Time'];
		// Use payout time for end_at if payout exists
		$end_at = (!empty($gameRelease) && !empty($gameRelease['Time'])) ? $gameRelease['Time'] : $gameBet['Time'];
		if ($result) {
			$this->CI->utils->debug_log('PNG insertMergeToGameLogs', 'playerid', @$result['PlayerId'], 'external_uniqueid', @$result['external_uniqueid'], 'game_code' ,@$result['game_code']);
			$unknownGame = $this->getUnknownGame();
			if (!$result['PlayerId']) {
				return array("success" => true);
			}
			$game_description_id = $result['game_description_id'];
			$game_type_id = $result['game_type_id'];

			if (empty($game_description_id)) {
				$game_description_id = $unknownGame->id;
				$game_type_id = $unknownGame->game_type_id;
				$this->CI->utils->debug_log('PNG unknownGame', 'unknownGameId', @$unknownGame->id, 'unknownGameTypeId',  @$unknownGame->game_type_id);
			}

			$status = $result['Status'];
			//pending if status is success but dont have release open
			if($status == self::STATUS_SUCCESS && empty($gameRelease)){
				$status = self::CASINO_TRANSACTION_RESERVE;
			}

			//for real bet
			$extra = array(
						'trans_amount'	=> 	$betAmount,
						'table' 		=>  $roundId,
						'status'		=>	$this->getGameRecordsStatus($status),
						);
			//ends

			$this->syncGameLogs(
				$game_type_id,
				$game_description_id,
				$result['game_code'],
				$result['game_type'],
				$result['game'],
				$result['PlayerId'],
				$result['UserName'],
				$betAmount,
				$resultAmount,
				null, # win_amount
				null, # loss_amount
				$afterBalance, # after_balance
				0, # has_both_side
				$result['external_uniqueid'],
				$start_at, //start
				$end_at, //end
				$result['response_result_id'],
				Game_logs::FLAG_GAME,
				$extra
			);
			return array("success" => true);
		}
	}

	/**
	 * overview : get game record status
	 *
	 * @param $status
	 * @return int
	 */
	private function getGameRecordsStatus($status) {
		// $this->CI->load->model(array('game_logs'));
		$status = (int)$status;

		switch ($status) {
		case self::CASINO_TRANSACTION_RESERVE:
			$status = Game_logs::STATUS_ACCEPTED;
			break;
		case self::STATUS_FAILED:
			$status = Game_logs::STATUS_REJECTED;
			break;
		case self::STATUS_VOID:
			$status = Game_logs::STATUS_VOID;
			break;
		case self::STATUS_SUCCESS:
			$status = Game_logs::STATUS_SETTLED;
			break;
		default:
        	$status = Game_logs::STATUS_SETTLED;
		}
		return $status;
	}

	public function syncOriginalGameLogs($token) {

		return $this->returnUnimplemented();
	}

	public function syncMergeToGameLogs($token) {
		$this->CI->load->model(array('png_game_logs'));
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = $dateTimeFrom->format('Y-m-d H:i:s');
		$endDate = $dateTimeTo->format('Y-m-d H:i:s');
		$roundIds = $this->CI->png_game_logs->getRoundIdsByDate($startDate, $endDate);
		$roundIds = array_unique($roundIds);
		$this->CI->utils->debug_log('PNG Manual Sync Merge', 'dateTimeFrom', $startDate, 'dateTimeTo', $endDate, 'roundIds', $roundIds);
		foreach ($roundIds as $roundId) {
			$this->insertMergeToGameLogs($roundId);
		}
		return array("success" => true);
	}

	function getFileExtension($filename)
    {
        $path_info = pathinfo($filename);
        return $path_info['extension'];
    }


	public function syncOriginalGameLogsFromCSV($isUpdate = false){
		set_time_limit(0);
    	$this->CI->load->model(array('external_system','original_game_logs_model'));
    	$extensions = array("csv");
    	$game_logs_path = $this->getSystemInfo('csv_png_game_records_path');
    	$exported_file = array_diff(scandir($game_logs_path,1), array('..', '.'));

    	$game_records = array();

    	$count = 0;
    	if(!empty($exported_file)){
    		foreach ($exported_file as $key => $csv) {
    			$ext = $this->getFileExtension($csv);
                if (!in_array($ext,$extensions)) {//skip other extension
                    continue;
                }
                $flag = true;
				$file = fopen($game_logs_path."/".$csv,"r");
				while(! feof($file))
				{
					$entry = fgetcsv($file);
					if($flag || empty($entry[0])) { $flag = false; continue; }

					$data = array(
						"ExternalUserId" 		=> $entry[0],//game name
						"Time" 					=> $entry[1],//start time
						"RoundId" 				=> $entry[2],//round id
						"Amount" 				=> $entry[3],//winning amount
						"RoundLoss" 			=> $entry[4],//bet amount
						"Balance" 				=> $entry[5],//after balance
						"GameId"				=> $entry[6],//game unique id
						"TransactionId"			=> $entry[7],//unique transaction
						"MessageType"			=> $entry[8], // GAME_BET = 3 or  GAME_RELEASE = 4;
					);
					$game_records[] = $data;
				}
				fclose($file);
			}
    	}
    	if(!empty($game_records)){
    		foreach ($game_records as $key => $record) {
    			$count++;
    			$this->insertOriginalGameLogs($record);
    		}
    	}
    	$result = array('data_count'=>$count);
    	return array("success" => true,$result);
	}

	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return array("success" => true, "loginStatus" => true);
	}

	public function checkLoginStatus($playerName) {
		return array("success" => true, "loginStatus" => true);
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
		return $this->returnUnimplemented();
	}

	public function useQueryAvailableBalance() {
		return $this->returnUnimplemented();
	}

	public function changePassword($playerName, $oldPassword, $newPassword) {
		return $this->returnUnimplemented();
	}

	public function logout($playerName, $password = null) {
		return $this->returnUnimplemented();
	}


	public function queryTransaction($transactionId, $extra) {
		return $this->returnUnimplemented();
	}
}
