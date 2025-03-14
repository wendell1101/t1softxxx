<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_nextspin extends Abstract_game_api {

    public $originalTable = 'nextspin_game_logs';

    const MD5_FIELDS_FOR_ORIGINAL = [
        'ticketId',
        'acctId',
        'categoryId',
        'gameCode',
        'ticketTime',
        'betAmount',
        'winLoss',
        'result',
        'roundId'
    ];

	const MD5_FLOAT_AMOUNT_FIELDS = [
        'betAmount',
        'winLoss',
        'balance'
    ];

	const MD5_FIELDS_FOR_MERGE = [
        'start_at',
        'end_at',
        'bet_at',
        'game_code',
        'result_amount',
        'bet_amount',
        'after_balance',
        'result',
        'player_username'
    ];

	const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'result_amount',
        'bet_amount',
        'after_balance'
    ];
    
    const STATUS_CODE = [
        "STATUS_SUCCESS" => 0,
        "STATUS_SYSTEM_ERROR" => 1,
        "STATUS_API_LIMIT" => 112,
        "STATUS_INVALID_ACCT_ID" => 113,
        "STATUS_ACCOUNT_NOT_FOUND" => 50100,
        "STATUS_INACTIVE_ACCT_ID" => 50101,
        "STATUS_LOCKED_ACCT_ID" => 50102,
        "STATUS_SUSPENDED_ACCT_ID" => 50103,
        "STATUS_INVALID_CURRENCY" => 50112,
        "STATUS_INVALID_AMOUNT" => 50113,
    ];
    const API_AUTHORIZE = '/auth/';

    public function __construct()
    {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->merchantCode = $this->getSystemInfo('merchantCode', 'ZCH535TEST');
        $this->language = $this->getSystemInfo('language');
        $this->currency = $this->getSystemInfo('currency');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->game_url = $this->getSystemInfo('game_url', 'https://lobby.lucky88dragon.com');
        $this->demo_url = $this->getSystemInfo('demo_url', 'https://play.lucky88dragon.com/game');
        $this->lobby_url = $this->getSystemInfo('lobby_url');

        $this-> URI_MAP = array(
            self::API_createPlayer =>  "deposit",
            self::API_logout =>  "kickAcct",
            self::API_isPlayerExist =>  "getAcctInfo",
            self::API_queryPlayerBalance =>  "getAcctInfo",
            self::API_depositToGame =>  "deposit",
            self::API_withdrawFromGame =>  "withdraw",
            self::API_syncGameRecords =>  "getBetHistory",
            self::API_queryTransaction => 'checkTransfer',
        );
    }

    public function getPlatformCode()
    {
		return NEXTSPIN_GAME_API;
    }

    public function generateUrl($apiName, $params) 
    {
        return $this->api_url;
    }

    protected function getHttpHeaders($params)
    {
		$this->CI->utils->debug_log('NextSpin' . __FUNCTION__ , $params);		
        $hash = strtoupper(md5(json_encode($params)));
		$headers = [
            "API" => $params['method'],
            "DataType" => "JSON",
            "Digest" => $hash,
        ];
        // echo "<br>";
        // print_r ($headers);
        return $headers;   
    }

    protected function customHttpCall($ch, $params) 
    {     
        curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, true));
        // echo "<br>";
        // print_r ($params);
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
  		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName)
    {
        $success = false;

        $success = ($resultArr['code'] == self::STATUS_CODE["STATUS_SUCCESS"]) ? true : '';

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('NextSpin got Error', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
            $success = false;
        }
    
        return $success;
    }
    
    public function generateSerialNo()
    {
        $serialNo = substr(date('YmdHis'), 2) . random_string('alnum', 5);
        return $serialNo;
    }

    public function getLauncherLanguage($language){
        
        $lang = '';
        switch ($language) {
        	case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
            case 'en_US':
                $lang = 'en_US'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'cn':
            case 'zh-cn':
            case 'zh_CN':
                $lang = 'zh_CN'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-id':
            case 'id_ID':
                $lang = 'id_ID'; // indonesia
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-vn':
            case 'vi_VN':
                $lang = 'vi_VN'; // vietnamese
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko':
            case 'ko-kr':
            case 'ko_KR':
                $lang = 'ko_KR'; // korean
                break;
            case Language_function::INT_LANG_THAI:
            case 'th':
            case 'th-th':
            case 'th_TH':
                $lang = 'th_TH'; // thai
                break;
            default: 
                $lang = 'en_US';
        }
        return $lang;
    }
 
    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        
        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
            'sbe_playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername,
            'amount' => 0.01,
		);

		$params = array(
            'acctId' => $gameUsername,
            'currency' => $this->currency,
            'merchantCode' => $this->merchantCode,
            'amount' => 0.01,
			'serialNo' => $this->generateSerialNo(),
            'method' => $this->URI_MAP[self::API_createPlayer],
		);

        $this->CI->utils->debug_log('NextSpin' . __FUNCTION__ , $params);
        return $this->callApi($this->URI_MAP[self::API_createPlayer], $params, $context);
    }

    public function processResultForCreatePlayer($params)
    {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
	
        $sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
        $this->withdrawFromGame($sbe_playerName, 0.01, null);

		if($success){
	        $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $resultArr["exists"] = true;
		}

		return array($success, $resultArr);
	}

    public function isPlayerExist($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'playerId' => $playerId
		);

        $params = array(
            "acctId" => empty($gameUsername)?$playerName:$gameUsername,
            'serialNo' => $this->generateSerialNo(),
            'merchantCode' => $this->merchantCode,
            'method' => $this->URI_MAP[self::API_isPlayerExist],
        );

        $this->CI->utils->debug_log('NextSpin' . __FUNCTION__ , $params);
        return $this->callApi($this->URI_MAP[self::API_isPlayerExist], $params, $context);
    }

    public function processResultForIsPlayerExist($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');

        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = [];
        
        if ($success) 
        {
            if ($resultArr['resultCount'] > 0)
            {
                $result = array (
                    "success" => true,
                    "exists" => true
                );
            }
            else 
            {
                $result = array (
                    "success" => true,
                    "exists" => false
                );
            }
        } 
        else 
        {
            $result = array (
                "success" => false,
                "exists" => null
            );
        }
    
        return array($success, $result);
    }

    public function queryPlayerBalance($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'playerId' => $playerId
		);

        $params = array(
            "acctId" => empty($gameUsername)?$playerName:$gameUsername,
            'serialNo' => $this->generateSerialNo(),
            'merchantCode' => $this->merchantCode,
            'method' => $this->URI_MAP[self::API_queryPlayerBalance],
        );

        $this->CI->utils->debug_log('NextSpin' . __FUNCTION__ , $params);
        return $this->callApi($this->URI_MAP[self::API_queryPlayerBalance], $params, $context);
    }

    public function processResultForQueryPlayerBalance($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');

        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $result = [];
        
        if($success)
        {
            $result['balance'] =  $this->gameAmountToDB($resultArr['list'][0]['balance']);
        }

       return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $amount = $this->dBtoGameAmount($amount);
        // $serialNo = $this->generateSerialNo();
        $serialNo = $transfer_secure_id ? $transfer_secure_id : $this->getSecureId('transfer_request', 'external_transaction_id', true, 'T');

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForDepositToGame',
			'sbe_playerName' => $playerName,
            'playerId' => $playerId,
			'gameUsername' => $gameUsername,
            'amount' => $amount,
            'external_transaction_id' => $serialNo,
		);

		$params = array(
            'acctId' => $gameUsername,
            'currency' => $this->currency,
            'merchantCode' => $this->merchantCode,
            'amount' => $amount,
			'serialNo' => $serialNo,
            'method' => $this->URI_MAP[self::API_depositToGame],
		);

        $this->CI->utils->debug_log('NextSpin' . __FUNCTION__ , $params);
        return $this->callApi(self::API_depositToGame, $params, $context);
    }
    public function processResultForDepositToGame($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $statusCode = $this->getStatusCodeFromParams($params);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if ($success && !empty($resultArr)) 
        {
            $result['external_transaction_id'] = $resultArr['serialNo'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        } 
        else 
        {
            if((in_array($statusCode, $this->other_status_code_treat_as_success) || in_array($resultArr['code'], $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
				$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
				$success=true;
			} else {
                $result['reason_id'] = $this->getTransferError($resultArr['code']);
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        }

        return array($success, $result);
    }

    public function getTransferError($transferError)
    {
        $reasonCode = self::STATUS_CODE["STATUS_SUCCESS"];

        switch((int)($transferError))
        {
            case 1:
                $reasonCode = self::STATUS_CODE["STATUS_SYSTEM_ERROR"];
                break;
            case 112:
                $reasonCode = self::STATUS_CODE["STATUS_API_LIMIT"];
                break;
            case 113:
                $reasonCode = self::STATUS_CODE["STATUS_INVALID_ACCT_ID"];
                break;
            case 50112:
                $reasonCode = self::STATUS_CODE["STATUS_INVALID_CURRENCY"];
                break;
            case 50113:
                $reasonCode = self::STATUS_CODE["STATUS_INVALID_AMOUNT"];
                break;
        }

        return($reasonCode);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $amount = $this->dBtoGameAmount($amount);
        // $serialNo = $this->generateSerialNo();
        $serialNo = $transfer_secure_id ? $transfer_secure_id : $this->getSecureId('transfer_request', 'external_transaction_id', true, 'T');

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForWithdrawFromGame',
			'sbe_playerName' => $playerName,
            'playerId' => $playerId,
			'gameUsername' => $gameUsername,
            'amount' => $amount,
            'external_transaction_id' => $serialNo,
		);

		$params = array(
            'acctId' => $gameUsername,
            'currency' => $this->currency,
            'merchantCode' => $this->merchantCode,
            'amount' => $amount,
			'serialNo' => $serialNo,
            'method' => $this->URI_MAP[self::API_withdrawFromGame],
		);

        $this->CI->utils->debug_log('NextSpin' . __FUNCTION__ , $params);
        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$resultArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if ($success && !empty($resultArr)) {
            $result['external_transaction_id'] = $resultArr['serialNo'];
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        } else {
            $result['reason_id'] = $this->getTransferError($resultArr['code']);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }

    public function queryTransaction($transactionId, $extra) {
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id' => $transactionId,
            'extra' => $extra,
        ];

        $params = [
            'merchantCode' => $this->merchantCode,
            'serialNo' => $transactionId,
            'method' => $this->URI_MAP[self::API_queryTransaction],
        ];

        return $this->callApi($this->URI_MAP[self::API_queryTransaction], $params, $context);
    }

    public function processResultForQueryTransaction($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        
        $result = [
            'external_transaction_id'=> $external_transaction_id,
            'response_result_id' => $responseResultId,
            'reason_id'=> self::REASON_UNKNOWN,
            'status'=> self::COMMON_TRANSACTION_STATUS_UNKNOWN
        ];

        if ($success && !empty($resultArr)) {
            $result['response_result'] = $resultArr;

            if ($resultArr['status'] == 1) { // 1 = Successful Transfer
                $result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            } else { // 0 = Unsuccesful Transfer
                $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
            
        } else {
            if (isset($resultArr['code'])) {
                switch ($resultArr['code']) {
                    case 1: // System Error
                        $reason_id = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                        break;
                    case 100: // Request Timeout
                        $reason_id = self::REASON_NETWORK_ERROR;
                        break;
                    case 107: // Duplicated Serial No
                        $reason_id = self::REASON_TRANSACTION_ID_ALREADY_EXISTS;
                        break;
                    default:
                        $reason_id = self::REASON_UNKNOWN;
                        break;
                }

                $result['reason_id'] = $reason_id;
                $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        }
        
        $this->CI->utils->debug_log('<---------- processResultForQueryTransaction ---------->', 'processResultForQueryTransaction_result', 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function queryForwardGame($playerName, $extra = null) 
    {
		$this->CI->load->model('common_token');
		$gameUsername   = $this->getGameUsernameByPlayerUsername($playerName);
		$playerId       = $this->getPlayerIdInGameProviderAuth($gameUsername);
		$token 			= $this->CI->common_token->createTokenBy($playerId, 'player_id');
        $merchantCode   = $this->merchantCode;
		$game_url 		= $this->game_url;
        $demo_url       = $this->demo_url;
        $game           = $extra['game_code'] ? $extra['game_code'] : "";
        $language       = $this->language;
		$auth 			= self::API_AUTHORIZE."?";

        //for lobby redirection
		if (isset($extra['home_link']) && !empty($extra['home_link']))
        {
            $this->lobby_url = $extra['home_link'];
        }
        else if (isset($extra['extra']['t1_lobby_url']) && !empty($extra['extra']['t1_lobby_url'])) 
        {
            $this->lobby_url = $extra['extra']['t1_lobby_url'];
        }
        else
        {
            $this->lobby_url = $this->getHomeLink();
        }

        //for language value
        if (isset($extra['language']) && !empty($extra['language']))
        {
            $extra['language'] = $this->language ? $this->language : $this->getLauncherLanguage($extra['language']);
        }
        else 
        {
            $extra['language'] = $this->language;
        }

        if(empty($game))
        {
            // For game lobby
            $params = array(
                "acctId" 	=> $gameUsername,
                "token"		=> $token,
                "channel"   => "Web",
                "language"  => $extra['language'],
                "isLobby"   => "true",
                "exitUrl"   => $this->lobby_url,
            );
        }
        else
        {
            // For game page
            $params = array(
                "acctId" 	=> $gameUsername,
                "token"		=> $token,
                "language"  => $extra['language'],
                "game"      => $game,
            );
        }

		if($extra['is_mobile'])
        {
			$params["channel"] = "Mobile";
		}

        $url_params  = http_build_query($params);
		// $url_params  = http_build_query(array_merge($params,$extra));
		$generateUrl = $game_url.'/'.$merchantCode.$auth.$url_params;
        
        if($extra['game_mode'] == "fun")
        {
            $params = array(
                "merchantCode" => $merchantCode,
                "game"         => $game,
                "language"     => $extra['language']
            );
            $url_params  = http_build_query($params);
            $generateUrl = $demo_url.'?'.$url_params;
        }

		$data = [
            'url' => $generateUrl,
            'success' => true
        ];
        $this->utils->debug_log('NextSpin' . __FUNCTION__ . $generateUrl);
        return $data;
	}

    public function callback($result = null, $platform = 'web') 
    {
		$success = false;
		$this->CI->load->model(array('common_token', 'player_model', 'affiliatemodel', 'users','game_provider_auth'));
    
        if($platform == "web")
        {
            $id = $this->CI->common_token->getPlayerIdByToken($result['token']);
            $this->CI->utils->debug_log('Check id' . __FUNCTION__ , $id);
            $this->CI->utils->debug_log('Check token' . __FUNCTION__ , $result['token']);
            if (!empty($id)) 
            {
                $success = true;
                $playerInfo = $this->CI->player_model->getPlayerInfoById($id);
                $this->CI->utils->debug_log('Check info' . __FUNCTION__ , $playerInfo);
            }
            if ($success) 
            {
                $this->CI->utils->debug_log('Check NEXTSPIN_GAMING_API REQUEST' . __FUNCTION__ , $result);
                $balance = $this->queryPlayerBalance($playerInfo['username']);
                $this->CI->utils->debug_log('Check NEXTSPIN_GAMING_API balance' . __FUNCTION__ , $balance);
                $gameUsername = $this->getGameUsernameByPlayerUsername($playerInfo['username']);
                $params = array(
                    'acctInfo' => [
                        "acctId" => $gameUsername,
                        "balance" => $balance['balance'],
                        "currency" => $this->currency,
                        "language" => $result['language'],
                    ],
                    'merchantCode' => $this->merchantCode,
                    'msg' => "Callback Success",
                    'token' => $result['token'],
                    'code' => self::STATUS_CODE["STATUS_SUCCESS"],
                    'serialNo' => $result['serialNo'],
                );
                $this->CI->utils->debug_log('Check NEXTSPIN_GAMING_API RESPONSE' . __FUNCTION__ , $params);
                return $params;
            }
            else
            {
                return self::STATUS_CODE["STATUS_ACCOUNT_NOT_FOUND"] . " - Account not found";
            }
        }
        else
        {
            $this->CI->utils->debug_log('Check NEXTSPIN_GAMING_API REQUEST' . __FUNCTION__ , $result);
			$playerInfo = (array)$this->CI->game_provider_auth->getPlayerInfoByGameUsername($result['acctId'],NEXTSPIN_GAMING_API);
			$this->CI->utils->debug_log('Check info'  . __FUNCTION__ , $playerInfo);
			if(!empty($playerInfo))
            {
				$balance = $this->queryPlayerBalance($playerInfo['username']);
				$this->CI->utils->debug_log('Check NEXTSPIN_GAMING_API balance' . __FUNCTION__ , $balance);
				$params = array(
                    'acctInfo' => [
                        "acctId" => $result['acctId'],
                        "balance" => $balance['balance'],
                        "currency" => $this->currency,
                        "language" => $result['language'],
                    ],
                    'merchantCode' => $this->merchantCode,
                    'msg' => "Callback Success",
                    'token' => $result['token'],
                    'code' => self::STATUS_CODE["STATUS_SUCCESS"],
                    'serialNo' => $result['serialNo'],
                );
				$this->CI->utils->debug_log('Check NEXTSPIN_GAMING_API RESPONSE' . __FUNCTION__ , $params);
				return $params;
			}
			else
            {
				return self::STATUS_CODE["STATUS_ACCOUNT_NOT_FOUND"] . " - Account not found";
			}
        }
	}

    public function syncOriginalGameLogs($token = false) 
    {
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

		$startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
		$endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
		$startDate->modify($this->getDatetimeAdjust());
		
		$startDate = $startDate->format('Y-m-d H:i:s');
		$endDate   = $endDate->format('Y-m-d H:i:s');
		$result = array();

		$result[] = $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+24 hours', function($startDate, $endDate)  
        {
			$startDate = $startDate->format('Ymd\THis');
			$endDate = $endDate->format('Ymd\THis');
			$context = array(
				'callback_obj' => $this,
				'callback_method' => 'processResultForSyncGameRecords',
				'startDate' => $startDate,
				'endDate' => $endDate,
			);

			$params = array(
				'beginDate' 	=> $startDate,
				'endDate' 		=> $endDate,
				'pageIndex' 	=> 1,
				'merchantCode'	=> $this->merchantCode,
				'serialNo'		=> $this->generateSerialNo(),
				'method'		=> $this->URI_MAP[self::API_syncGameRecords]
			);

			$rlt =  $this->callApi($this->URI_MAP[self::API_syncGameRecords], $params, $context);
			$currentPage = 1;
			$totalPage = $rlt['page_count'];
			while($currentPage < $totalPage) 
            {
				$params['pageIndex'] = $currentPage + 1;
			    $callApiByPage = $this->callApi(self::API_syncGameRecords, $params, $context);

			    if($callApiByPage['success'])
                {
			    	$currentPage = $currentPage + 1;
			    } 
                else
                {
			    	$currentPage = $totalPage;
			    }
			}
			return true;
		});

		return array('success' => true, $result);
	}

    public function processGameRecords(&$gameRecords, $responseResultId) 
    {
		if(!empty($gameRecords))
        {
            $this->CI->utils->debug_log('NextSpin Preprocess Game records', $gameRecords);
			foreach($gameRecords as $index => $record) 
            {
				$insertRecord = array();
				//Data from NextSpin API
				$insertRecord['ticketId'] 		= isset($record['ticketId']) ? $record['ticketId'] : NULL;
				$insertRecord['acctId'] 		= isset($record['acctId']) ? $record['acctId'] : NULL;
				$insertRecord['categoryId'] 	= isset($record['categoryId']) ? $record['categoryId'] : NULL;
				$insertRecord['gameCode'] 		= isset($record['gameCode']) ? $record['gameCode'] : NULL;
				$insertRecord['ticketTime'] 	= isset($record['ticketTime']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['ticketTime']))) : NULL;
				$insertRecord['betIp'] 			= isset($record['betIp']) ? $record['betIp'] : NULL;
				$insertRecord['betAmount'] 		= isset($record['betAmount']) ? $record['betAmount'] : NULL;
				$insertRecord['winLoss'] 		= isset($record['winLoss']) ? $record['winLoss'] : NULL;
				$insertRecord['currency'] 		= isset($record['currency']) ? $record['currency'] : NULL;
				$insertRecord['result'] 		= isset($record['result']) ? $record['result'] : NULL;
				$insertRecord['jackpotAmount'] 	= isset($record['jackpotAmount']) ? $record['jackpotAmount'] : NULL;
				$insertRecord['luckyDrawId'] 	= isset($record['luckyDrawId']) ? $record['luckyDrawId'] : NULL;
				$insertRecord['completed'] 		= isset($record['completed']) ? $record['completed'] : NULL;
				$insertRecord['roundId'] 		= isset($record['roundId']) ? $record['roundId'] : NULL;
				$insertRecord['sequence'] 		= isset($record['sequence']) ? $record['sequence'] : NULL;
				$insertRecord['channel'] 		= isset($record['channel']) ? $record['channel'] : NULL;
				$insertRecord['balance'] 		= isset($record['balance']) ? $record['balance'] : NULL;
				$insertRecord['jpWin'] 			= isset($record['jpWin']) ? $record['jpWin'] : NULL;

				//Extra info from SBE
				$insertRecord['external_uniqueid']  = $insertRecord['ticketId']; 
				$insertRecord['response_result_id'] = $responseResultId;
                $insertRecord['created_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
                $insertRecord['updated_at'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
				$gameRecords[$index] = $insertRecord;
			}
                // $this->CI->utils->debug_log('NextSpin Processed Game records 1', $gameRecords);
                return $gameRecords;
		}
	}

    public function processResultForSyncGameRecords($params) 
    {
		$this->CI->load->model('original_game_logs_model');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $this->CI->utils->debug_log('NextSpin result', $resultArr['list']);
        
        $result = array(
			'data_count'=> 0,
			'page_count'=> 1
		);

        $gameRecords = isset($resultArr['list']) ? $resultArr['list'] : "";
		if ($success && !empty($gameRecords)) 
        {
			$result['page_count'] = $resultArr['pageCount'];
			$gameRecords = $this->processGameRecords($gameRecords, $responseResultId);
            $this->CI->utils->debug_log('NextSpin Processed Game records', $gameRecords);

				list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
	                $this->originalTable,
	                $gameRecords,
	                'external_uniqueid',
	                'external_uniqueid',
	                self::MD5_FIELDS_FOR_ORIGINAL,
	                'md5_sum',
	                'id',
	                self::MD5_FLOAT_AMOUNT_FIELDS
	            );
                // $this->CI->utils->debug_log('NextSpin Processed Game records 3', $gameRecords);
                
				$this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));
                unset($gameRecords);

				if (!empty($insertRows)) 
                {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
				}
				unset($insertRows);

				if (!empty($updateRows)) 
                {
					$result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
				}
				unset($updateRows);
		}
		return array($success, $result);
	}

	private function updateOrInsertOriginalGameLogs($data, $queryType)
    {
        $dataCount=0;
        if(!empty($data))
        {
            foreach ($data as $record) 
            {
                if ($queryType == 'update') 
                {
                	$record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->originalTable, $record);
                } 
                else 
                {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->originalTable, $record);
                }
                $dataCount++;
                unset($record);
            }
        }

        return $dataCount;
    }

    /**
	 * overview : syncMergeTogameLogs
	 *
	 * @param $token
	 * @return array
	 */
	public function syncMergeToGameLogs($token) 
    {
        $enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
	}

	/**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime = 'original.ticketTime >= ? and original.ticketTime <= ?';

        $this->CI->utils->debug_log('NextSpin sqlTime ===>', $sqlTime);

		$sql = <<<EOD
        SELECT 
            original.id AS sync_index,
            original.external_uniqueid,
            original.acctId AS player_username,
            original.ticketId AS round_number,
            original.ticketTime AS start_at,
            original.ticketTime AS end_at,
            original.ticketTime AS bet_at,
            original.response_result_id,
            original.gameCode,
            original.winLoss AS result_amount,
            original.betAmount AS bet_amount,
            original.balance AS after_balance,
            original.updated_at,
            original.md5_sum,
            original.result,
            original.roundId,
    
            game_provider_auth.player_id,
	        gd.id as game_description_id,
	        gd.english_name as game_description_name,
            gd.game_code as game_code,
	        gd.game_type_id
        FROM {$this->originalTable} as original
            LEFT JOIN game_description as gd ON original.gameCode = gd.external_game_id AND gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            JOIN game_provider_auth ON original.acctId = game_provider_auth.login_name
            AND game_provider_auth.game_provider_id=?
        WHERE

        {$sqlTime}

EOD;

        $params = [
            $this->getPlatformCode(), 
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
       	return $result;
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        if(empty($row['md5_sum']))
        {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

        $extra = ['trans_amount'=>$row['bet_amount']];
        
        return [

            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game'                  => isset($row['game_description_name']) ? $row['game_description_name'] : null
            ],
            'player_info' => [
                'player_id'             => isset($row['player_id']) ? $row['player_id'] : null,
                'player_username'       => isset($row['player_username']) ? $row['player_username'] : null
            ],
            'amount_info' => [
                'bet_amount'            => isset($row['bet_amount']) ? $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount']) : 0,
                'result_amount'         => isset($row['result_amount']) ? $this->gameAmountToDBGameLogsTruncateNumber($row['result_amount']) : 0,
                'bet_for_cashback'      => isset($row['bet_amount']) ? $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount']) : 0,
                'real_betting_amount'   => isset($row['bet_amount']) ? $this->gameAmountToDBGameLogsTruncateNumber($row['bet_amount']) : 0,
                'win_amount'            => 0,
                'loss_amount'           => 0,
                'after_balance'         => isset($row['after_balance']) ? $this->gameAmountToDBGameLogsTruncateNumber($row['after_balance']) : 0,
            ],
            'date_info' => [
                'start_at'              => isset($row['start_at']) ? $row['start_at'] : null,
                'end_at'                => isset($row['end_at']) ? $row['end_at'] : null,
                'bet_at'                => isset($row['bet_at']) ? $row['bet_at'] : null,
                'updated_at'            => isset($row['updated_at']) ? $row['updated_at'] : null
            ],
            'flag'                      => Game_logs::FLAG_GAME,
            'status'                    => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number'          => isset($row['round_number']) ? $row['round_number'] : null,
                'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details'               => $row['bet_details'],
            'extra'                     => $extra,
            //from exists game logs
            'game_logs_id'              => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'     =>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id'])) 
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id'] = $game_description_id;
            $row['game_type_id']        = $game_type_id;
        }

        $row['status']  = Game_logs::STATUS_SETTLED;
        $row['bet_details'] = $row['result'];
    }

    /**
	 * overview : get game description information
	 *
	 * @param $row
	 * @param $unknownGame
	 * @param $gameDescIdMap
	 * @return array
	 */
	private function getGameDescriptionInfo($row, $unknownGame) 
    {
		$game_description_id = null;
		$game_type_id = null;
		if (isset($row['game_description_id']))
        {
			$game_description_id = $row['game_description_id'];
			$game_type_id = $row['game_type_id'];
		}

		if(empty($game_description_id))
        {
            $game_description_id=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
            $unknownGame->game_type_id, $row['gameCode'], $row['gameCode']);
		}

		return [$game_description_id, $game_type_id];
	}

}