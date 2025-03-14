<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting platform code
 * * Generate URL
 * * Generate Soap Method
 * * Checks if the Player Exist
 * * Create Player
 * * Extract XML Content
 * * Block/Unblock Player
 * * Deposit to Game
 * * Withdraw From Game
 * * login
 * * Check if session is alive
 * * Check Player balance
 * * Check Player Daily Balance
 * * Check Game Records
 * * Check Forward Game
 * * Synchronize Original Game Logs
 * * Synchronize Old Game Logs to Current Game Logs
 * * Get Game Description Information

 *  All below behaviors are not implemented yet
 * * check player info
 * * logout
 * * update player information
 * * Check Login Status
 * * Check Total Betting Amount
 * * Check Transaction
 * * Check batch player balance
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Game_api_common_fg extends Abstract_game_api {
	# Fields in og_v2_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'party_id',
        'user_id',
        'game_info_id',
        'game_tran_id',
        'platform_code',
        'platform_tran_id',
        'game_id',
        'amount',
        'date_time',
        'amount',
        'currency',
        'balance',
    ];


    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'amount',
        'balance'
    ];

    const MD5_FIELDS_FOR_MERGE = [
    	'game_tran_id',
		'player_username',
		'platform_code',
		'bet_amount',
		'after_balance',
		'date_time',
		'result_amount',
		'currency',
		'platform_code',
		'party_id',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
    	'bet_amount',
		'result_amount',
		'after_balance',
    ];

    const CONVERSION_RATE = [
    	"IDR" => 1000,
        "CNY" => 1,
        "THB" => 1,
        "VND" => 1
    ];

    const DEFAULT_CONVERSION_RATE = 1;
	const WIN_FLAG = 1;
	const BET_FLAG = 0;

	public $fg_api_url;
	public $fg_brand_id;
	public $fg_brand_pw;
	public $fg_lang_id;
	public $fg_currency_code;
	public $fg_game_url;
	public $fg_game_history_url;
	public $is_http_call;

	private $conversionRates;

	public function __construct() {
		parent::__construct();

		$this->fg_api_url = $this->getSystemInfo('url');
		$this->live_mode = $this->getSystemInfo('live_mode');

        if (empty($this->fg_api_url)) {
        	$this->fg_api_url = APPPATH . "libraries/game_platform/FGSecondaryWalletSTaging.xml";
            
            if($this->live_mode==1){
                $this->fg_api_url = APPPATH . "libraries/game_platform/FGSecondaryWallet.xml";
            }
        }

		$this->fg_brand_id = $this->getSystemInfo('key');
		$this->fg_brand_pw = $this->getSystemInfo('secret');
		$this->fg_lang_id = $this->getSystemInfo('fg_lang_id');
		$this->fg_currency_code = $this->getSystemInfo('fg_currency_code','CNY');
		$this->fg_game_url = $this->getSystemInfo('fg_game_url');
		$this->fg_game_platform = $this->getSystemInfo('fg_game_platform');
		$this->fg_game_history_url = $this->getSystemInfo('fg_game_history_url');
        $this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', '60');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->client_www_domain = $this->getSystemInfo('client_www_domain');

        $this->conversionRates = $this->getSystemInfo('conversion_rate', self::CONVERSION_RATE);

        $this->use_www_domain_for_mobile_redirection = $this->utils->getSystemUrl('use_www_domain_for_mobile_redirection',false);
        
        # Mobile redirection m as default
        $domain_redirection = $this->utils->getSystemUrl('m');
        if($this->use_www_domain_for_mobile_redirection){
        	$domain_redirection = $this->utils->getSystemUrl('www');
        }
	}

	/**
	 * overview : return platform code
	 *
	 * @return int
	 */
	public function getPlatformCode() {
		return $this->returnUnimplemented();
	}

	/**
	 * overview : get call type
	 *
	 * @param $apiName
	 * @param $params
	 * @return int
	 */
	protected function getCallType($apiName, $params) {
		//overwrite in sub-class
		if (!$this->is_http_call) {
			return self::CALL_TYPE_SOAP;
		}
		return self::CALL_TYPE_HTTP;
	}

	/**
	 * overview : generate url
	 *
	 * @param $apiName
	 * @param $params
	 * @return string
	 */
	public function generateUrl($apiName, $params) {

		if ($apiName == self::API_syncGameRecords) {
            if ($this->getSystemInfo('gethistory_by_last_transid')) {
                $this->fg_api_url = $this->fg_game_history_url . "?brandId=" . $this->fg_brand_id . "&brandPassword=" . $this->fg_brand_pw;
                if (!empty($params)) {
                    $this->fg_api_url .= "&lastTransactionId=" . @$params['lastTransactionId'];
                }
            } else {
                $url = $this->fg_game_history_url . "?" . http_build_query($params);
                return $url;
            }
        }
        return $this->fg_api_url;
    }

	/**
	 * overview : generate soap method
	 *
	 * @param $apiName
	 * @param $params
	 * @return array
	 */
	protected function generateSoapMethod($apiName, $params) {

		switch ($apiName) {
		case self::API_isPlayerExist:
			return array('doesPlayerExist', $params);
			break;

		case self::API_queryPlayerBalance:
			return array('getBalance', $params);
			break;

		case self::API_createPlayer:
			return array('createPlayer', $params);
			break;

		case self::API_depositToGame:
			return array('transfer', $params);
			break;

		case self::API_withdrawFromGame:
			return array('transfer', $params);
			break;

		case self::API_syncGameRecords:
			return array('GetGameStatistics', $params);
			break;

		case self::API_login:
			return array('loginPlayer', $params);
			break;

		case self::API_checkLoginStatus:
			return array('isPlayerSessionAlive', $params);
			break;

        case self::API_logout:
            return array('logoutSession', $params);
            break;

        case self::API_queryTransaction:
            return array('GetTransaction', $params);
            break;

		default:
			# code...
			break;
		}

		return parent::generateSoapMethod($apiName, $params);
	}

	/**
	 * overview : after process result
	 *
	 * @param $apiName
	 * @param $params
	 * @param $responseResultId
	 * @param $resultText
	 * @param $statusCode
	 * @param null $statusText
	 * @param null $extra
	 * @param null $resultObj
	 * @return array
	 */
	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		return $this->returnUnimplemented();
	}

	/**
	 * overview : process result
	 *
	 * @param $responseResultId
	 * @param $resultArr
	 * @param $playerName
	 * @param bool|false $isGetHistory
	 * @return bool
	 */
	protected function processResultBoolean($responseResultId, $resultArr, $playerName, $isGetHistory = false) {
		$success = !empty($resultArr);
		if ($isGetHistory) {
			if ($resultArr['status'] == "SUCCESS") {
				$success = true;
			} else {
				$success = false;
			}
		}
		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('FG got error', $responseResultId, 'result', $resultArr);
		}
		return $success;
	}

	/**
	 * overview : check if player exists
	 *
	 * @param $playerName
	 * @return array
	 */
	public function isPlayerExists($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExists',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

        $params = array(
                "brandId" => $this->fg_brand_id,
                "brandPassword" => $this->fg_brand_pw,
                "uuid" => $gameUsername,
            );

		return $this->callApi(self::API_isPlayerExist, $params, $context);
	}

	/**
	 * overview : process result for isPlayerExists
	 * @param $params
	 * @return array
	 */
	public function processResultForIsPlayerExists($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->extractXmlContent(@$params['resultText'], 'doesPlayerExistResponse');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getPlayerIdInPlayer($playerName);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		if ($success) {
			$success = true;
			$result['exists'] = true;    
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE); 
			return array($success, $result);
		}
		return array($success, array("exists"=>false));
	}

	/**
	 * overview : create game player
	 * @param $playerName
	 * @param $playerId
	 * @param $password
	 * @param null $email
	 * @param null $extra
	 * @return array
	 */
	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerData = $this->isPlayerExists($playerName);
        if ($playerData['exists'] == 'false') {
            parent::createPlayer($playerName, $playerId, $password, $email, $extra);

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForCreatePlayer',
                'playerName' => $playerName,
            );

            $params = array(
                    "brandId" => $this->fg_brand_id,
                    "brandPassword" => $this->fg_brand_pw,
                    "address" => 'na',
                    "birthDate" => '1983-06-30',
                    "city" => 'na',
                    "country" => 'na',
                    "email" => $gameUsername . '@gmail.com',
                    "firstName" => $gameUsername,
                    "gender" => 'M',
                    "homePhone" => 'na',
                    "iso3CurrencyCode" => $this->getPlayerFGCurrency($gameUsername),
                    "language" => 'en',
                    "lastName" => $gameUsername,
                    "loginName" => $gameUsername,
                    "mobilePhone" => 'na',
                    "postalCode" => 'na',
                    "region" => 'na',
                    "uuid" => $gameUsername,
                );

            return $this->callApi(self::API_createPlayer, $params, $context);
        } else {
            if($playerData['exists'] == 'true'){
                return array("success" => true);
            }
            return array(false, array());
        }
	}

	/**
	 * overview : process result for create player
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForCreatePlayer($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->extractXmlContent(@$params['resultText'], 'createPlayerResponse');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		if ($success) {
			if (isset($resultArr['partyId'])) {
				$success = true;
			} else {
				$success = false;
			}
		}
		return array($success, $resultArr);
	}

	/**
	 * overview : extract the xml content
	 *
	 * @param $resultText
	 * @param $responseString
	 * @return mixed
	 */
	public function extractXmlContent($resultText, $responseString) {
		$xml = new DOMDocument();
		$xml->preserveWhiteSpace = false;
		$xml->loadXML($resultText);
		$xmlArray = $this->xml2array($xml);

		$xmlContent = $xmlArray['soap:Envelope']['soap:Body']['ns2:' . $responseString];
		return $xmlContent;
	}

	/**
	 * overview : convert xml to array data
	 *
	 * @param $n
	 * @return array
	 */
	public function xml2array($n) {
		$return = array();
		foreach ($n->childNodes as $nc) {
			($nc->hasChildNodes())
			? ($n->firstChild->nodeName == $n->lastChild->nodeName && $n->childNodes->length > 1)
			? $return[$nc->nodeName][] = $this->xml2array($item)
			: $return[$nc->nodeName] = $this->xml2array($nc)
			: $return = $nc->nodeValue;
		}

		return $return;
	}

	/**
	 * overview : query player information
	 * @param $playerName
	 * @return array
	 */
	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	/**
	 * overview : change player password
	 * @param $playerName
	 * @param $oldPassword
	 * @param $newPassword
	 * @return array
	 */
	public function changePassword($playerName, $oldPassword, $newPassword) {
		return $this->returnUnimplemented();
	}


	/**
	 * overview : block player
	 *
	 * @param $playerName
	 * @return array
	 */
	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->blockUsernameInDB($playerName);
		return array("success" => true);
	}


	/**
	 * overview : unblock player
	 *
	 * @param $playerName
	 * @return array
	 */
	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$success = $this->unblockUsernameInDB($playerName);
		return array("success" => true);
	}

	private function getConversionRate($currencyCode = null) 
	{
		$convertion_rate = $this->conversionRates;
    	
    	if(is_array($convertion_rate)){
    		if ($currencyCode != null) {
    			$convertion_rate = isset($convertion_rate[$currencyCode]) ? $convertion_rate[$currencyCode] : 1;	
    		} else {
    			$convertion_rate = self::DEFAULT_CONVERSION_RATE;
    		}			
    	}
        
        return floatval($convertion_rate);
	}

	public function gameFgAmountToDB($amount, $currencyCode = null) 
	{
		$conversion_rate = $this->getConversionRate($currencyCode);
        $value = floatval($amount / $conversion_rate);
    	return $this->round_down($value,3);
        // return $amount / $conversion_rate;
    }

    public function dbToFgGameAmount($amount, $currencyCode = null) 
    {
		$conversion_rate = $this->getConversionRate($currencyCode);
        $value = floatval($amount * $conversion_rate);
        return $this->round_down($value,3);
        
        // return $amount * $conversion_rate;
    }

	private function round_down($number, $precision = 2){
	    $fig = (int) str_pad('1', $precision, '0');
	    return (floor($number * $fig) / $fig);
	}
    
	/**
	 * overview : deposit player to game
	 *
	 * @param $playerName
	 * @param $amount
	 * @param null $transfer_secure_id
	 * @return array
	 */
	public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$platformTranId = random_string('numeric');
		$playerCurrency = $this->getPlayerFGCurrency($gameUsername); 

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			"gameUsername" => $gameUsername,
			"amount" => $amount,
			"platformTranId" => $platformTranId,
			"brandId" => $this->fg_brand_id,
			"brandPassword" => $this->fg_brand_pw,
			"playerCurrency" => $playerCurrency,
		);

        $params = array(
                "brandId" => $this->fg_brand_id,
                "brandPassword" => $this->fg_brand_pw,
                "uuid" => $gameUsername,
                "playerName" => $gameUsername,
                "amount" => $this->dbToFgGameAmount($amount,$playerCurrency),
                "iso3Currency" => $playerCurrency,
                "platformTranId" => $platformTranId,
            );

		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	/**
	 * overview : process result for deposit game
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForDepositToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->extractXmlContent(@$params['resultText'], 'transferResponse');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
        $playerCurrency = $this->getVariableFromContext($params, 'playerCurrency');

        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$afterBalance = null;
		if ($success) {
            # note : can only use this in queryTransaction
            $result['external_transaction_id'] = @$resultArr['transaction']['transactionId'];

			//for sub wallet
			// $afterBalance = $this->gameFgAmountToDB($resultArr['balanceWithdrawable'],$playerCurrency);

			// //update
			// $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
			// if ($playerId) {
			// 	//deposit
			// 	$this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,
			// 		$this->transTypeMainWalletToSubWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		}else{
            // no callback error here if api failed
            // display default (network error, reason id) from processGuessSuccess
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
		// $result['after_balance'] = $afterBalance;

		return array($success, $result);
	}

	/**
	 * overview : withdraw player from game
	 *
	 * @param $playerName
	 * @param $amount
	 * @param null $transfer_secure_id
	 * @return array
	 */
	public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$platformTranId = random_string('numeric');
		$playerCurrency = $this->getPlayerFGCurrency($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawToGame',
			"gameUsername" => $gameUsername,
			"amount" => $this->invertSign($amount),
			"platformTranId" => $platformTranId,
			"brandId" => $this->fg_brand_id,
			"brandPassword" => $this->fg_brand_pw,
			"playerCurrency" => $playerCurrency
		);

        $params = array(
                "brandId" => $this->fg_brand_id,
                "brandPassword" => $this->fg_brand_pw,
                "uuid" => $gameUsername,
                "playerName" => $gameUsername,
                "amount" => $this->invertSign($this->dbToFgGameAmount($amount,$playerCurrency)),
                "iso3Currency" => $playerCurrency,
                "platformTranId" => $platformTranId,
            );
		return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	/**
	 * overview : invert sign
	 *
	 * @param $amount
	 * @param bool|false $toPositive
	 * @return number
	 */
	public function invertSign($amount, $toPositive = false) {
		$amount = $toPositive ? abs($amount) : -$amount;
		return $amount;
	}

	/**
	 * overview : process result from withdraw to game
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForWithdrawToGame($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->extractXmlContent(@$params['resultText'], 'transferResponse');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$playerCurrency = $this->getVariableFromContext($params, 'playerCurrency');

        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$afterBalance = null;
		if ($success) {
			//for sub wallet
			// $afterBalance = $this->gameFgAmountToDB($resultArr['balanceWithdrawable'],$playerCurrency);

			// //update
			// $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			// if ($playerId) {
			// 	//withdrawal
			// 	$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
			// 		$this->transTypeSubWalletToMainWallet());
			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			// }
			$result['didnot_insert_game_logs']=true;
            $result['external_transaction_id'] = @$resultArr['transaction']['transactionId'];
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		}else {
            // no callback error here if api failed
            // display default (network error, reason id) from processGuessSuccess
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

		// $result['after_balance'] = $afterBalance;

		return array($success, $result);
	}

	/**
	 * overview : login player
	 *
	 * @param $playerName
	 * @param null $password
	 * @return array
	 */
	public function login($playerName, $password = null) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogin',
			'playerName' => $gameUsername,
		);

        $params = array(
                "brandId" => $this->fg_brand_id,
                "brandPassword" => $this->fg_brand_pw,
                "uuid" => $gameUsername,
                "loginName" => $gameUsername,
                "firstName" => $gameUsername,
                "lastName" => $gameUsername,
                "address" => 'na',
                "city" => 'na',
                "country" => 'na',
                "postalCode" => 'na',
                "homePhone" => 'na',
                "mobilePhone" => 'na',
                "birthDate" => '1983-06-30',
                "email" => $gameUsername . '@gmail.com',
                "gender" => 'M',
                "language" => $this->fg_lang_id,
                "iso3CurrencyCode" => $this->getPlayerFGCurrency($gameUsername),
            );

		return $this->callApi(self::API_login, $params, $context);
	}

	/**
	 * overview : process result for login
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForLogin($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->extractXmlContent(@$params['resultText'], 'loginPlayerResponse');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
		if ($success) {
			if (isset($resultArr['partyId'])) {
				$this->CI->utils->debug_log('FG LOGIN RAW SESSION KEY ----------------------> ', $resultArr['sessionKey']);
				$this->CI->load->model('external_common_tokens');
				$this->CI->external_common_tokens->addPlayerToken($playerId, $resultArr['sessionKey'], FG_API);
				$success = true;
			} else {
				$success = false;
			}
		}
		return array($success, $resultArr);
	}

	/**
	 * overview : check if session alive
	 *
	 * @param $playerName
	 * @return array|bool
	 */
	public function isSessionAlive($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$this->CI->load->model('external_common_tokens');
		$playerId = $this->getPlayerIdInGameProviderAuth($playerName);
		$sessionKey = $this->CI->external_common_tokens->getExternalToken($playerId, $this->getPlatformCode());

		if (!$sessionKey) {
			return false;
		}
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsSessionAlive',
			'sessionKey' => $sessionKey,
			'playerName' => $playerName,
		);

        $params = array(
                "brandId" => $this->fg_brand_id,
                "brandPassword" => $this->fg_brand_pw,
                "sessionKey" => $sessionKey,
            );

		$res = $this->callApi(self::API_checkLoginStatus, $params,
			$context);
		return $res;
	}

	/**
	 * overview : process result for isSessionAlive
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForIsSessionAlive($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->extractXmlContent(@$params['resultText'], 'isPlayerSessionAliveResponse');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$sessionKey = $this->getVariableFromContext($params, 'sessionKey');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$result = array();

		$result['response_result_id'] = $responseResultId;
		$result['sessionKey'] = $sessionKey;
		if ($success) {
			$result['isSessionAlive'] = @$resultArr['isAlive'];
			$success = true;
		} else {
			$success = false;
		}
		return array($success, $result);
	}

	/**
	 * overview : logout player
	 *
	 * @param $playerName
	 * @param null $password
	 * @return array
	 */
	public function logout($playerName, $password = null) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'playerName' => $playerName,
        );

        $isSessionAlive = $this->isSessionAlive($playerName);
        $params = array();
        if(!empty($isSessionAlive['success'])){
            $params = array(
                "brandId" => $this->fg_brand_id,
                "brandPassword" => $this->fg_brand_pw,
                'sessionKey' => $isSessionAlive['sessionKey']
            );
        }

        return $this->callApi(self::API_logout, $params, $context);
    }

    public function processResultForLogout($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $resultArr = $params['resultText'];
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = array();

        if (!empty($resultArr)) {
            $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        }else{
            $success = true;
        }

        return array($success, $result);
    }

	/**
	 * overview : update player info
	 * @param $playerName
	 * @param $infos
	 * @return array
	 */
	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	/**
	 * overview : get player balance
	 *
	 * @param $playerName
	 * @return array
	 */
	public function queryPlayerBalance($playerName) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerCurrency = $this->getPlayerFGCurrency($gameUsername);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
		);

        $params = array("brandId" => $this->fg_brand_id,
                "brandPassword" => $this->fg_brand_pw,
                "uuid" => $gameUsername
            );
		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	/**
	 * overview : process result for queryPlayerBalance
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForQueryPlayerBalance($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->extractXmlContent(@$params['resultText'], 'getBalanceResponse');
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$playerCurrency = $this->getPlayerFGCurrency($gameUsername);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
		$result = array();
		if ($success) {
			if (!empty($resultArr)) {
				$success = true;
				$result['balance'] = @floatval($resultArr['withdrawable']);
                $result['balance'] = $this->gameFgAmountToDB($result['balance'],$playerCurrency);
			} else {
				$success = false;
			}
		}
		return array($success, $result);
	}

	/**
	 * @param $playerName
	 * @param $playerId
	 * @param null $dateFrom
	 * @param null $dateTo
	 * @return array
	 */
	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		$daily_balance = parent::getPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null);

		$result = array();

		if ($daily_balance != null) {
			foreach ($daily_balance as $key => $value) {
				$result[$value['updated_at']] = $value['balance'];
			}
		}

		return array_merge(array('success' => true, "balanceList" => $result));
	}

	/**
	 * overview : get game records
	 *
	 * @param $dateFrom
	 * @param $dateTo
	 * @param null $playerName
	 * @return array
	 */
	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		$gameRecords = parent::getGameRecords($dateFrom, $dateTo, $playerName, $this->getPlatformCode());
		return array('success' => true, 'gameRecords' => $gameRecords);
	}

	/**
	 * overview : checklogin status
	 *
	 * @param $playerName
	 * @return array
	 */
	public function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	/**
	 * overview : total betting amount
	 *
	 * @param $playerName
	 * @param $dateFrom
	 * @param $dateTo
	 * @return array
	 */
	public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
		return $this->returnUnimplemented();
	}

	/**
	 * overview : query transaction
	 *
	 * @param $transactionId
	 * @param $extra
	 * @return array
	 */
	public function queryTransaction($transactionId, $extra) {
        $playerName=$extra['playerName'];
        $playerId=$extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        // $transactionId ='1898966476';

        if(!empty($transactionId)) {
            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForQueryTransaction',
                'gameUsername' => $gameUsername,
                'playerName' => $playerName,
                'playerId'=>$playerId,
                'external_transaction_id' => $transactionId
            );

            $params = array(
                "brandId" =>$this->fg_brand_id,
                "brandPassword" => $this->fg_brand_pw,
                "uuid" => $gameUsername,
                'transactionId' => $transactionId,
            );

            return $this->callApi(self::API_queryTransaction, $params, $context);
        }
        return $this->returnFailed('Bad Request');
	}

	/**
	 * overview : process result for queryTransaction
	 * @param $apiName
	 * @param $params
	 * @param $responseResultId
	 * @param $resultXml
	 * @return array
	 */
	public function processResultForQueryTransaction($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->extractXmlContent(@$params['resultText'], 'getTransactionResponse');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        $success = !empty($resultArr) ? true : false;

        if($success) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            // no callback error here if api failed
            // display default (network error, reason id) from processGuessSuccess
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
	}

	/**
	 * overview : process game list
	 *
	 * @param $game
	 * @return array
	 */
	public function processGameList($game) {
		$game_platform = $game['attributes'];
		$game = parent::processGameList($game);
		$this->CI->load->model(array('game_description_model'));
		$game['gp'] = "iframe_module/goto_fg/" . $game_platform . "/" . $game['c']; //game param
		return $game;
	}

	public function getLauncherLanguage($language){
		$lang='';
		switch ($language) {
	        case 1:
            case 'en-us':
	        	$lang = 'en';
	        	break;
	        case 2:
            case 'zh-cn':
	            $lang = 'zh';
	            break;
	        case 3:
            case 'id-id':
	            $lang = 'id';
	            break;
	        case 4:
            case 'vi-vn':
	            $lang = 'vi';
	            break;
	        case 5:
            case 'ko-kr':
	            $lang = 'ko';
	            break;
	        case 6:
            case 'th-th':
	            $lang = 'th';
	            break;
	        default:
	            $lang = 'en';
	            break;
	    }
	    return $lang;
	}

	/**
	 * overview : query forward game
	 *
	 * @param $playerName
	 * @param $param
	 * @return array
	 */
	public function queryForwardGame($playerName, $param) 
	{
		$merchantCode 	= null;		
		$gamePlatform 	= null;
		$isMobile 		= false;
		$clientWWW 		= null;
		$clientTitle 	= null;
		
		$language 		= isset($param['language']) ? $this->getLauncherLanguage($param['language']) : 'zh-cn';		
		$gameCode 		= isset($param['game_code']) ? $param['game_code'] : null;
		$isFunGame		= in_array(strtolower($param['game_mode']), ["fun","demo", 'false']);
		$gamePlatform 	= isset($param['game_platform']) ? $param['game_platform'] : (isset($param['extra']['game_platform']) ?  $param['extra']['game_platform'] : null);
		$isMobile 		= isset($param['is_mobile_flag']) ? $param['is_mobile_flag'] : (isset($param['extra']['is_mobile_flag']) ?  $param['extra']['is_mobile_flag'] : null);

		# for t1 fg game launch parameter
		if (isset($param['extra']['merchant_code']) && !empty($param['extra']['merchant_code'])) {
			$merchantCode 	= isset($param['extra']['merchant_code']) ? $merchantCode = $param['extra']['merchant_code'] : null;
		}


		$lobbyUrl = $this->utils->getSystemUrl('m') . $this->getSystemInfo('lobby_url');
		if (!empty($merchantCode)) {
			$clientWWW 		= isset($this->client_www_domain[$merchantCode]['url']) ? $this->client_www_domain[$merchantCode]['url'] : null;
			$clientTitle 	= isset($this->client_www_domain[$merchantCode]['title']) ? $this->client_www_domain[$merchantCode]['title'] : null;
			$lobbyUrl 		= isset($this->client_www_domain[$merchantCode]['mobile_game_lobby']) ? $this->client_www_domain[$merchantCode]['mobile_game_lobby'] : null;
		}

		if (isset($param['extra']['t1_lobby_url']) && !empty($param['extra']['t1_lobby_url'])) {
		    $lobbyUrl = $param['extra']['t1_lobby_url'];  
		}

		# build http query
		$urlParam = [
			'platform' 		=> $gamePlatform,
			'gameId' 		=> $gameCode,
			'lang' 			=> $language,
			'brandId' 		=> $this->fg_brand_id,
			'isMobile' 		=> $isMobile ? 'true' : 'false',
			'playForReal' 	=> $isFunGame ? 'false' : 'true',
			'lobbyUrl' 		=> $lobbyUrl,
		];


		if ($isMobile) {
			if ($isFunGame && $gamePlatform != 'PLAYSON') {								
				$urlParam['clienttype'] = 'html5';
			}

			#check the current sub game provider, this parameter is for NYX_CAS only as of 10/31/2017
	        if($gamePlatform == 'NYX_CAS'){
	            $urlParam['clienttype'] = 'html5';
	        }
		}
       
		if ($playerName) {
			$data = $this->isSessionAlive($playerName);

			if (!empty($data)) {
                #check if session is alive
				if ($data['isSessionAlive'] == 'false') {
					$loginResult = $this->login($playerName);
					$urlParam['sessionKey'] = @$loginResult['sessionKey'];
				} else {
					$urlParam['sessionKey'] = $data['sessionKey'];
				}
			} else {
				$loginResult = $this->login($playerName);
				$urlParam['sessionKey'] = @$loginResult['sessionKey'];
			}
		}

		$url = $this->fg_game_url . "?" . http_build_query($urlParam);

		return [
			'success' 			=> true,
			'url' 				=> $url,
			'iframeName' 		=> "FG API",
			'is_redirect' 		=> $isMobile,
			'client_www_domain' => $clientWWW,
			'client_title' 		=> $clientTitle
		];
	}

	/**
	 * overview : sync original game logs
	 *
	 * note : Should run sync every minute and will get 1 hour of data
	 *
	 * @param $token
	 * @return array
	 */
	public function syncOriginalGameLogs($token) {		
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
        $endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
        $startDate->modify($this->getDatetimeAdjust());
        $this->CI->utils->debug_log('startDate', $startDate, 'endDate', $endDate);        
        $this->is_http_call = true;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
        );

        $data = array(
            "brandId" => $this->fg_brand_id,
            "brandPassword" => $this->fg_brand_pw,            
        );
        
		# get the last version key in db			
		$last_version_key = $this->CI->external_system->getLastSyncIdByGamePlatform($this->getPlatformCode());

		if ($last_version_key) {
			$data['lastTransactionId'] = $last_version_key;
		}		

        return $this->callApi(self::API_syncGameRecords, $data, $context);
	}

	public function processResultForSyncGameRecords($params) 
	{
        $this->CI->load->model('original_game_logs_model');
        $startDate = $this->getVariableFromContext($params, 'startDate');
        $endDate = $this->getVariableFromContext($params, 'endDate');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,null,true);
        $gameRecords = !empty($resultArr['transactions']) ? $resultArr['transactions']:[];

        $result = array(
            'data_count' => 0,
            'responseResultId' => $responseResultId
        );

        # for local testing of data only
        // if (!$success) {
        // 	$gameRecords = $this->fakeData();
        // 	$success = true;
        // 	$responseResultId = 1111;        
        // }

        if ($success && !empty($gameRecords)) {
            $extra = ['response_result_id' => $responseResultId];

            $this->rebuildGameRecords($gameRecords,$extra);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                static::ORIGINAL_TABLE_NAME,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );
            $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

            unset($gameRecords);

            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs(
                	$insertRows, 
                	'insert', 
                	['responseResultId' => $responseResultId]
                );
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs(
                	$updateRows, 
                	'update', 
                	['responseResultId'=>$responseResultId]
                );
            }
            unset($updateRows);
        }

        return array($success, $result);
    }

    public function rebuildGameRecords(&$gameRecords,$extra)
    {
		if (empty($gameRecords)) {
			return;
		}

		$rebuildRecords = [];

		foreach($gameRecords as $index => $record) {
            if (!in_array(strtoupper($record['tranType']), ['GAME_BET', 'GAME_WIN'])) {
                continue;
            }
            $logs = [
	            'trans_id'				=> isset($record['id'])?$record['id']:null,
	            'party_id'				=> isset($record['partyId'])?$record['partyId']:null,
	            'user_id'				=> isset($record['userId'])?$record['userId']:null,
	            'game_info_id'			=> isset($record['gameInfoId'])?$record['gameInfoId']:null,
	            'game_tran_id'			=> isset($record['gameTranId'])?$record['gameTranId']:null,
	            'platform_code'			=> isset($record['platformCode'])?$record['platformCode']:null,
	            'platform_tran_id'		=> isset($record['platformTranId'])?$record['platformTranId']:null,
	            'tran_type'				=> isset($record['tranType'])?$record['tranType']:null,
	            'game_id'				=> isset($record['gameId'])?$record['gameId']:null,
	            'amount'				=> isset($record['tranType'])?$record['tranType']:null,
	            'date_time'				=> isset($record['dateTime'])?$this->gameTimeToServerTime($record["dateTime"]):null,
	            'amount'				=> isset($record['amount'])?$this->invertSign($record["amount"], true):null,
	            'currency'				=> isset($record['currency'])?$record["currency"]:null,
	            'balance'				=> isset($record['balance'])?$record["balance"]:null,
	            'rollback_tran_id'		=> isset($record['rollbackTranId'])?$record["rollbackTranId"]:null,
	            'rollback_tran_type'	=> isset($record['rollbackTranType'])?$record["rollbackTranType"]:null,
	            'external_uniqueid'		=> isset($record['id'])?$record['id']:null,
	            'response_result_id'	=> $extra['response_result_id'],
            ];

            if (strtoupper($record['tranType']) == 'GAME_WIN')  {
            	$logs['win_flag'] = 1;
            }

            if (isset($record['id']) && !empty($record['id'])) {
            	$this->CI->external_system->updateLastSyncId($this->getPlatformCode(), array("last_sync_id" => $record['id']));
            }

            array_push($rebuildRecords, $logs);
        }

        $gameRecords = $rebuildRecords;
	}
	
    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount=0;
        if (!empty($rows)) {
            foreach ($rows as $record) {

            	$record["last_sync_time"] = $this->CI->utils->getNowForMysql();
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal(static::ORIGINAL_TABLE_NAME, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal(static::ORIGINAL_TABLE_NAME, $record);
                }
                $dataCount++;
            }
        }

        return $dataCount;
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

	public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
		$table = static::ORIGINAL_TABLE_NAME;
        $sqlTime =  'fg.date_time >= ? and fg.date_time <= ?';

        $sql = <<<EOD
SELECT
    fg.id as sync_index,
    fg.trans_id,
    fg.external_uniqueid,
    fg.user_id AS player_username,
    fg.amount AS bet_amount,
	fg.external_uniqueid,
	fg.response_result_id,
	fg.date_time,
	fg.platform_code,
	fg.win_flag,
	fg.tran_type,
	fg.party_id,
	fg.currency,
	fg.balance as after_balance,
	fg.game_tran_id,
	fg.game_info_id,
	fg.md5_sum,
	fg.last_sync_time,
	fg.game_id as game_code,
    fg.game_id as game,
    game_provider_auth.player_id,
    game_description.id AS game_description_id,
    game_description.game_type_id
FROM
    {$table} fg
    JOIN game_provider_auth
        ON fg.user_id = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
    LEFT JOIN game_description
        ON game_description.external_game_id = fg.game_id
        AND game_description.void_bet != 1
        AND game_description.game_platform_id = ?
WHERE
    {$sqlTime} AND win_flag = 0
    GROUP BY game_tran_id, user_id
EOD;
        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
	    list($row['bet_amount'], $row['result_amount'], $row['after_balance']) = $this->generateBetAfterBalanceAndResultAmount($row);

		$row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);

		return [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet_amount'],
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['bet_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance']
            ],
            'date_info' => [
                'start_at'              => $row['date_time'],
                'end_at'                => $row['date_time'],
                'bet_at'                => $row['date_time'],
                'updated_at'            => $row['last_sync_time']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['game_tran_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => ['Created At' => $this->CI->utils->getNowForMysql()],
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,            
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row['status'] = Game_logs::STATUS_SETTLED;	
    }

    private function generateBetAfterBalanceAndResultAmount($row)
    {
    	$gameTransId 	= $row['game_tran_id'];
    	$playerGameName = $row['player_username'];
    	$afterBalance 	= $row['after_balance'];
    	$currency 		= $row['currency'];

		$gameRecords = $this->fetchBetRecordByGameTransID($gameTransId, $playerGameName);

		list($betAmount, $resultAmount, $afterBalance) = $this->getBetWinRecord($gameRecords, $afterBalance);

       	return [
       		$this->gameFgAmountToDB($betAmount, $currency),
       		$this->gameFgAmountToDB($resultAmount, $currency),
       		$this->gameFgAmountToDB($afterBalance, $currency),
       	];
    }

	public function getBetWinRecord($gameRecord,$afterBalance) {

		$betRecords = [];
		$winRecords = [];
		if(!empty($gameRecord)) {
			foreach($gameRecord as $key => $record ) {
				if($record['win_flag'] == self::BET_FLAG) {
					array_push($betRecords,$record);
				}
				if($record['win_flag'] == self::WIN_FLAG) {
					array_push($winRecords,$record);
				}
			}
		}

		$betAmount = 0;
		if(!empty($betRecords)) {
			foreach($betRecords as $key => $betRecord ) {
				$betAmount += $betRecord['amount'];
			}
		}
		// set result amount to -betAmount (incase there is no GAME_WIN trans_type)
		$resultAmount 	= -$betAmount;

		if (!empty($winRecords)) {
			$this->CI->utils->debug_log('===========> FG Win Record ', $winRecords);
			$payout = 0;

			foreach ($winRecords as $key => $winRecord) {
				$payout += $winRecord['amount'];
				$afterBalance = $winRecord['after_balance'];
			}

			$resultAmount = $payout - $betAmount;
		}

		return array($betAmount, $resultAmount, $afterBalance);
	}

	private function fetchBetRecordByGameTransID($transId, $playerName)
    {
    	$table = static::ORIGINAL_TABLE_NAME;

    	$sql = <<<EOD
SELECT
    amount,
    balance as after_balance,
    win_flag,
    currency
FROM
    {$table} USE INDEX (idx_game_tran_id)
WHERE
    game_tran_id = ? AND user_id = ?
ORDER BY trans_id ASC
EOD;
        $params = [
            $transId,
            $playerName
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }  


	public function getGameDescriptionInfo($row, $unknownGame) {
		$game_description_id = null;
		$game_type_id = null;
		if (isset($row['game_description_id'])) {
			$game_description_id = $row['game_description_id'];
			$game_type_id = $row['game_type_id'];
		}

		if(empty($game_description_id)){
			$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
					$unknownGame->game_type_id, $row['game'], $row['game_code']);
		}

		return [$game_description_id, $game_type_id];
	}

	/**
	 * overview : batch query player balance
	 *
	 * @param $playerNames
	 * @param null $syncId
	 * @return array
	 */
	public function batchQueryPlayerBalance($playerNames, $syncId = null) {
		return $this->returnUnimplemented();
	}

    public function queryBetDetailLink($playerUsername, $betid = NULL, $extra = NULL){
        return array("success"=>false);
    }

    public function getPlayerFGCurrency($gameUsername){
		# use correct currency code
		$playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
		if(!is_null($playerId)){
			$this->CI->load->model(array('player_model'));
			$currencyCode = $this->CI->player_model->getPlayerCurrencyByPlayerId($playerId);
			if(!is_null($currencyCode)){
				switch ($currencyCode) {
					case 'RMB':
					case 'CNY':
						$currencyCode = 'CNY';
						break;
					case 'USD':
						$currencyCode = 'USD';
						break;
					case 'IDR':
						$currencyCode = 'IDR';
						break;
					case 'THB':
						$currencyCode = 'THB';
						break;
				}
				return $currencyCode;
			}else{
				return $this->fg_currency_code;
			}
		}else{
			return $this->fg_currency_code;
		}
	}

	private function fakeData() {	
		return [
			0 => [
               'id' => 2955426677,
               'partyId' => 2992994,
               'userId' => 'peidjogorogo2',
               'gameInfoId' => '',
               'gameTranId' => '',
               'platformCode' => 'LSLSWAPI',
               'platformTranId' => 81188093,
               'gameId' => '',
               'tranType' => 'TRANSF_IN',
               'dateTime' => '2019-02-13 05:19:10.703',
               'amount' => 25340,
               'currency' => 'IDR',
               'balance' => 25349,
               'rollbackTranId' => '',
               'rollbackTranType' => '', 
	        ],
	        1 => [
                'id' => 2955428192,
                'partyId' => '1950101',
                'userId' => 'peidiamketdogs',
                'gameInfoId' => '1207',
                'gameTranId' => '8149631514',
                'platformCode' => 'PLAYNGO',
                'platformTranId' => '16378449068',
                'gameId' => '100334',
                'tranType' => 'GAME_BET',
                'dateTime' => '2019-02-13 05:19:45.187',
                'amount' => '-2000',
                'currency' => 'IDR',
                'balance' => '170238.99',
                'rollbackTranId' => '',
                'rollbackTranType' => '',
            ],

            2 => [
		        'id' => 2955428814,
		        'partyId' => '1950101',
		        'userId' => 'peidiamketdogs',
		        'gameInfoId' => '1207',
		        'gameTranId' => '8149631514',
		        'platformCode' => 'PLAYNGO',
		        'platformTranId' => '16378450655',
		        'gameId' => '100334',
		        'tranType' => 'GAME_WIN',
		        'dateTime' => '2019-02-13 05:19:59.97',
		        'amount' => '3100',
		        'currency' => 'IDR',
		        'balance' => '173338.99',
		        'rollbackTranId' => '',
		        'rollbackTranType' => '',
            ]
	    ];
	}
	
}

/*end of file*/