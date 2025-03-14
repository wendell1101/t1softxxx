<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
	* API NAME: SBOBET GAME (sportsbook,live casino)
	*
	* @category Game_platform
	* @version 1.31
	* @copyright 2013-2022 tot
	* @integrator @andy.php.ph
**/
class Game_api_common_sbobet extends Abstract_game_api {
	const SUCCESS_CODE = 0;
	const PLAYER_EXIST_CODE = 4103;
	const ORIGINAL_GAMELOGS_TABLE = 'sbobet_game_logs';
	const GAME_STATUS_RUNNING = 'running';
	const IS_PARLAY = "Mix Parlay";
	
	const THEME_TYPES = [
		"black","blue","emerald","green","ocean","sbo","lawn","sbolite","sbobet-m",
		"china-layout-m","euro-layout-m"
	];

	const ODDSTYLE = [
		"MY","HK","EU","ID"
	];

	const ODDSMODE = [
		"Single","Double"
	];

	# Fields in sbobet_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL_CASINO = [
        'refno',
        'ordertime',
        'status',
        'accountid',
        'tablename',
        'gameid',
        'producttype',
        'stake',
        'winlose',
        'turnover',
    ];

    const MD5_FIELDS_FOR_ORIGINAL_SPORTS = [
        'refno',
        'ordertime',
        'status',
        'username',
        'livescore',
        'htscore',
        'ftscore',
        'islive',
        'oddsstyle',
        'betoption',
        'sporttype',
        'markettype',
        'league',
        'match',
        'winlostdate',
        'customeizedbettype',
        'currency',
        'ip',
        'stake',
        'actualstake',
        'winlose',
        'turnover',
        'hdp',
        'odds',
    ];

    const MD5_FIELDS_FOR_ORIGINAL_VIRTUALSPORTS = [
        'refno',
        'ordertime',
        'status',
        'accountid',
        'tablename',
        'gameid',
        'producttype',
        'stake',
        'actualstake',
        'winlose',
        'turnover',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_CASINO = [
        'stake',
        'winlose',
        'turnover',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_SPORTS = [
        'stake',
        'actualstake',
        'winlose',
        'turnover',
        'hdp',
        'odds',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_VIRTUALSPORTS = [
        'stake',
        'winlose',
        'turnover',
    ];

    # Fields in sbobet_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_MERGE = [
    	'external_uniqueid',
    	'game_status',
    	'bet_amount',
    	'result_amount',
    	'username',
    	'donetime',
    	'odds',
    	'external_game_id'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_SEAMLESSGAMEPROVIDER = [
	    'stake',
	    'winlose',
	    'winLost',
	    'actualStake'
	];

	// WHOLE ROW JSON ENCODED
	const MD5_FIELDS_FOR_ORIGINAL_SEAMLESSGAMEPROVIDER = [
	    'external_game_id',
	    'accountId',
	    'stake',
	    'winlose',
	    'actualStake',
	    'turnover',
	    'winlostDate',

	    // Some response on SEAMLESS GMAE PROVIDER is the same with SPORTS
	    'refno',
	    'ordertime',
	    'status'
	];

    const EXTRA_API = [
    	'registerAgent' => '/web-root/restricted/agent/register-agent.aspx',
    	'updateDomains' => '/web-root/restricted/agent/update-domains.aspx',
    	'updateAgentBetSettings' => '/web-root/restricted/agent/update-agent-bet-settings.aspx',
    	'updatePlayerBetSettings' => '/web-root/restricted/player/update-player-bet-settings.aspx',
    	'updatePlayerBetSettingsBySportTypeAndMarketType' => '/web-root/restricted/betsetting/update-bet-setting-by-sportid-and-markettype.aspx',
    	'updateAgentPresetBetSettingsBySportTypeAndMarketType' => '/web-root/restricted/betsetting/update-agent-preset-bet-setting-by-sportid-and-markettype.aspx',
    	// FOR REPORTS
    	'getCustomerBetListByModifyDate' => '/web-root/restricted/report/get-customer-bet-list-by-modifydate.aspx',
    	'getCasinoBetListByModifyDate' => '/web-root/restricted/report/get-casino-bet-list-by-modifydate.aspx',
    	'getVirtualSportsBetListByModifyDate' => '/web-root/restricted/report/get-virtualsports-bet-list-by-modifydate.aspx',
    	'resendSeamlessWallet' => '/web-root/restricted/sw/resend-seamless-wallet-for-sports.aspx',
    	'getCustomerLiveCasinoBeautyBetListByModifyDate' => '/web-root/restricted/report/v2/get-livecasinobeauty-bet-list-by-modifydate.aspx',
    	'getSeamlessGameProviderBetListByModifydate' => '/web-root/restricted/report/get-seamlessgameprovider-bet-list-by-modifydate.aspx',
    	'getSeamlessGameProviderBonusBetListByModifyDate' => '/web-root/restricted/report/get-seamlessgameprovider-bonus-bet-list-by-modifydate.aspx',
    ];
    
    const URI_MAP = array(
			self::API_createPlayer => '/web-root/restricted/player/register-player.aspx',
			self::API_queryPlayerBalance => '/web-root/restricted/player/get-player-balance.aspx',
	        self::API_depositToGame => '/web-root/restricted/player/deposit.aspx',
	        self::API_withdrawFromGame => '/web-root/restricted/player/withdraw.aspx',
	        self::API_queryTransaction => '/web-root/restricted/player/check-transaction-status.aspx',
	        self::API_login => '/web-root/restricted/player/login.aspx',
	        self::API_getGameProviderGamelist => '/web-root/restricted/information/get-game-list.aspx',
		);

    const SBO_GAME_TYPE = [
        "sportsbook" => 1,
        "casino" => 7,
        "games" => 3,
        "virtualsports" => 3,
        "seamlessgameprovider" => 9,
    ];

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->server_id = $this->getSystemInfo('server_id');
		$this->company_key = $this->getSystemInfo('company_key');
		$this->language = $this->getSystemInfo('language','zh-cn');
		$this->agent_username = $this->getSystemInfo('agent_username');
		$this->agent_password = $this->getSystemInfo('agent_password');
		$this->currency = $this->getSystemInfo('currency');
		$this->agent_theme_id = $this->getSystemInfo('agent_theme_id',1);
		$this->agent_min_bet = $this->getSystemInfo('agent_min_bet',10);
        $this->agent_max_bet = $this->getSystemInfo('agent_max_bet',1000);
        $this->agent_maxpermatch = $this->getSystemInfo('agent_maxpermatch',5000);
        $this->agent_casino_table_limit = $this->getSystemInfo('agent_casino_table_limit', 4); #Preset casino table limit of this agent. Available value for 1: Low, 2: Medium, 3: High, and 4: VIP(ALL)
        $this->use_default_agent_betsettings = $this->getSystemInfo('use_default_agent_betsettings',true);
        $this->agent_custom_bet_settings = $this->getSystemInfo('agent_custom_bet_settings');
        $this->player_custom_bet_settings = $this->getSystemInfo('player_custom_bet_settings',[]);
        $this->default_theme = $this->getSystemInfo('default_theme');
		$this->default_oddstyle = $this->getSystemInfo('default_oddstyle');
		$this->default_oddsmode = $this->getSystemInfo('default_oddsmode');
		$this->gamelaunch_url = $this->getSystemInfo('gamelaunch_url');
		$this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
		$this->transfer_code_for_resend_settle_and_void = $this->getSystemInfo('transfer_code_for_resend_settle_and_void',[]);
		$this->seamless_game_provider_code = $this->getSystemInfo('seamless_game_provider_code', []);
	}

	public function getPlatformCode()
	{
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params)
	{
		return $this->api_url.$params['method'];
	}

	protected function customHttpCall($ch, $params) 
	{
		unset($params["method"]); //unset action not need on params
		curl_setopt($ch, CURLOPT_POST, TRUE);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, "param=".json_encode($params, true));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, true));
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode) 
	{
		$success = false;
		if((@$statusCode == 200 || @$statusCode == 201) && ($resultArr['error']['id'] == self::SUCCESS_CODE)){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('SBOBETGAME got error: ', $responseResultId,' Result:', $resultArr);
		}
		$this->CI->utils->debug_log('SBOBETGAME RawSuccessResponse: ', $responseResultId,'Result:', $resultArr);
		return $success;
	}

	public function createAgent($agentUsername=null) 
	{
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreateAgent',
			'agentUsername' => $agentUsername
		);

		$params = array(
			"CompanyKey" 	=> $this->company_key,
			"ServerId" 		=> $this->server_id,
			"Username" 		=> $this->agent_username,
			"Password" 		=> $this->agent_password,
			"Currency" 		=> $this->currency,
			// "language" 		=> $this->language,
			// "themeId" 		=> $this->agent_theme_id,
			"Min" 			=> $this->agent_min_bet,
			"Max" 			=> $this->agent_max_bet,
			"MaxPerMatch" 	=> $this->agent_maxpermatch,
			"CasinoTableLimit" => $this->agent_casino_table_limit,
			"method" 		=> self::EXTRA_API['registerAgent']
		);

		if(!$this->use_default_agent_betsettings){
			$params["betSettings"] = $this->agent_custom_bet_settings;
		}
		return $this->callApi(self::API_createAgent, $params, $context);
	}

	public function processResultForCreateAgent($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);
        // $update = $this->updateExternalSystemExtraInfo($this->getPlatformCode(),["is_agent_created" => $success]);
        return array(true, $resultJsonArr);
    }

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) 
	{
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
			'gameUsername' => $gameUsername,
			'playerId' => $playerId,
		);

		$params = array(
			"CompanyKey" => $this->company_key,
			"ServerId" => $this->server_id,
			"Username" => $gameUsername,
			"Agent" => $this->agent_username,
			// "language" => $this->language,
			// "serverId" => $this->server_id,
			"method" => self::URI_MAP[self::API_createPlayer]
		);
		return $this->callApi(self::API_createPlayer, $params, $context);
	}

	public function processResultForCreatePlayer($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);   
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);
		if ($success) {
			# update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
		} else{
			if(isset($resultJsonArr['error']['id']) && $resultJsonArr['error']['id'] == self::PLAYER_EXIST_CODE){
				$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
				$success = true;
			}
		}
		return array($success, $resultJsonArr);
    }

    public function queryPlayerBalance($playerName) 
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'gameUsername' => $gameUsername
		);

		$params = array(
			"companyKey" => $this->company_key,
			"username" => $gameUsername,
			"serverId" => $this->server_id,
			"method" => self::URI_MAP[self::API_queryPlayerBalance]
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);
	}

	public function processResultForQueryPlayerBalance($params) 
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
		$result = [];
		if($success){
			$result['balance'] = @$resultArr['balance'] ?: 0;
		}
		return array($success, $result);
	}

	public function batchQueryPlayerBalance($playerNames, $syncId = null) 
	{
        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }
        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null)
    {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'T'.$this->generateUnique() : $transfer_secure_id;
		
		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'amount' => $amount,
			'external_transaction_id' => $external_transaction_id
        );

        $params = array(
			"companyKey" => $this->company_key,
			"username" => $gameUsername,
			"amount" => $amount,
			"txnId" => $external_transaction_id,
			"serverId" => $this->server_id,
			"method" => self::URI_MAP[self::API_depositToGame]
		);
		return $this->callApi(self::API_depositToGame, $params, $context);
	}

	public function processResultForDepositToGame($params) 
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId,$resultArr,$statusCode);
		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($statusCode);
        }
        return array($success, $result);
	}

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$external_transaction_id = empty($transfer_secure_id) ? 'W'.$this->generateUnique() : $transfer_secure_id;

		$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'amount' => $amount,
			'external_transaction_id' => $external_transaction_id
        );

        $params = array(
			"companyKey" => $this->company_key,
			"username" => $gameUsername,
			"amount" => $amount,
			"txnId" => $external_transaction_id,
			"serverId" => $this->server_id,
			"method" => self::URI_MAP[self::API_withdrawFromGame]
		);
        return $this->callApi(self::API_withdrawFromGame, $params, $context);
	}

	public function processResultForWithdrawFromGame($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id' => $external_transaction_id,
			'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id' => self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
			$result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			$result['reason_id'] = $this->getReasons($statusCode);
        }
        return array($success, $result);
	}

	/*
	 *	To Launch Game
	 *
	 *  Game launch URL
	 *  ~~~~~~~~~~~~~~~
	 *
	 *  goto_sbobet_game/<game_platform_id>/<language>/<is_mobile>/<theme>/<oddstyle>/<is_redirect>
	 *  Default: player_center/goto_sbobet_game/2129/en
	 *  Desktop: player_center/goto_sbobet_game/2129/en/false/blue/EU/true
	 *  Mobile: player_center/goto_sbobet_game/2129/en/true/blue/EU/true
	 *
	 */
	public function queryForwardGame($playerName, $extra = null) 
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$params = array(
			"companyKey" => $this->company_key,
			"username" => $gameUsername,
			"portfolio" => $this->default_lauch_game_type,
			"serverId" => $this->server_id,
			"method" => self::URI_MAP[self::API_login]
		);

		$context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'extra' => $extra,
            'player_name' => $playerName
        ];
        return $this->callApi(self::API_login, $params, $context);
	}

	public function processResultForLogin($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$extra = $this->getVariableFromContext($params, 'extra');
		$playerName = $this->getVariableFromContext($params, 'player_name');
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
		$response = array("url" => null);
		if($success){
			$url = isset($resultArr['url']) ? $this->utils->getServerProtocol() . ":" .$resultArr['url'] : null;
			$generatedUrl = $this->buildGameLauncher($playerName, $url, $extra);
			$response['url'] = $generatedUrl;
		}
        return array($success, $response);
	}

	private function buildGameLauncher($playerName, $url,$extraParams)
	{
		#GET LANG FROM PLAYER DETAILS
		$playerId = $this->getPlayerIdFromUsername($playerName);
		$language = $this->getLauncherLanguage($this->getPlayerDetails($playerId)->language) ?: $this->language;		

		#IDENTIFY IF LANGUAGE IS INVOKED IN GAME URL, ELSE USE PLAYER LANG
		if(isset($extraParams['language'])){
			$language = $this->getLauncherLanguage($extraParams['language']);
		}

		#IDENTIFY IF THEME IS INVOKED IN GAME URL, ELSE USE DEFAULT SKIN
		# GAME API REMARKS: 
		# - Please note that setting themes will only affect 
		#   to SportsBook instead of Casino and Games.
		$theme = $this->default_theme;
		if(isset($extraParams['extra']['theme'])){
			$themeVal = $extraParams['extra']['theme'];
			if(in_array($themeVal,self::THEME_TYPES)){
				$theme = $themeVal;
			}
		}

		#IDENTIFY IF ODDSTYLE IS INVOKED IN GAME URL, ELSE USE EXTRA INFO DATA
		$oddstyle = $this->default_oddstyle;
		if(isset($extraParams['extra']['oddstyle'])){
			$oddStyleVal = $extraParams['extra']['oddstyle'];
			if(in_array($oddStyleVal,self::ODDSTYLE)){
				$oddstyle = $oddStyleVal;
			}
		}

		#IDENTIFY IF ODDSMODE IS INVOKED IN GAME URL, ELSE USE EXTRA INFO DATA
		$oddsmode = $this->default_oddsmode;
		if(isset($extraParams['extra']['oddsmode'])){
			$oddsModeVal = $extraParams['extra']['oddsmode'];
			if(in_array($oddsModeVal,self::ODDSMODE)){
				$oddsmode = $oddsModeVal;
			}
		}

		$urlParams = [
						// "token" => $gameToken,
						"lang" => $language,
						"oddstyle" => $oddstyle,
						"theme" => $theme,
						"oddsmode" => $oddsmode,
						"device" => "d",#desktop
					 ];

		#IDENTIFY IF LAUNCH IN MOBILE MODE
		if(isset($extraParams['is_mobile'])){
			$isMobile = $extraParams['is_mobile'] ? true : false;
			if($isMobile){
				$urlParams['device'] = "m";
			}
		}
		$url =  $url.'&'.http_build_query($urlParams);
		// $url = ltrim($url, '//');
		return $url;
	}

	public function syncOriginalGameLogs($token = false)
	{
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());
        //observer the date format
        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate = $endDate->format('Y-m-d H:i:s');

        $gameType = $this->getValueFromSyncInfo($token, 'gameType');
        if(!empty($gameType)){
            switch ($gameType) {
                case 'sports':
                    return $this->getSportsBookGamelogs($startDate, $endDate);
                    break;
                case 'casino':
                    return $this->getCasinoGameLogs($startDate, $endDate);
                    break;
                case 'casino_beauty':
                    return $this->getCasinoBeautyGameLogs($startDate, $endDate);
                    break;
                case 'virtual':
                    return $this->getVirtualSportsGameLogs($startDate, $endDate);
                    break;                
                case 'seamless_gameprovider':
                    return $this->getSeamlessGameProviderGameLogs($startDate, $endDate);
                    break;
                case 'seamless_gameprovider_bonus':
                    return $this->getSeamlessGameProviderBonusBetListByModifyDate($startDate, $endDate);
                    break;

            }
        }
		
        $result['casino_beauty'] = $this->getCasinoBeautyGameLogs($startDate, $endDate);
        $result['sportsbook'] = $this->getSportsBookGamelogs($startDate, $endDate);
        $result['casino'] = $this->getCasinoGameLogs($startDate, $endDate);
        $result['virtualsports'] = $this->getVirtualSportsGameLogs($startDate, $endDate);
        $result['seamless_gameprovider'] = $this->getSeamlessGameProviderGameLogs($startDate, $endDate);
        $result['seamless_gameprovider_bonus'] = $this->getSeamlessGameProviderBonusBetListByModifyDate($startDate, $endDate);

        return array('success' => true,$result);	
	}

	public function getSportsBookGamelogs($startDate, $endDate) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameRecords',
            'type' => 'sports',
        );

        $params = array(
            "companyKey" => $this->company_key,
            "serverId" => $this->server_id,
            "username" => $this->agent_username,
            "startDate" => $startDate,
            "endDate" => $endDate,
            "method" => self::EXTRA_API['getCustomerBetListByModifyDate']
        );

        return $this->callApi(self::EXTRA_API['getCustomerBetListByModifyDate'], $params, $context);
    }

    public function getCasinoGameLogs($startDate, $endDate) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameRecords',
            'type' => 'casino',
        );

        $params = array(
            "companyKey" => $this->company_key,
            "serverId" => $this->server_id,
            "username" => $this->agent_username,
            "startDate" => $startDate,
            "endDate" => $endDate,
            "lang" => $this->language,
            "method" => self::EXTRA_API['getCasinoBetListByModifyDate']
        );

        return $this->callApi(self::EXTRA_API['getCasinoBetListByModifyDate'], $params, $context);
    }

    public function getVirtualSportsGameLogs($startDate, $endDate) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameRecords',
            'type' => 'virtualsports',
        );

        $params = array(
            "companyKey" => $this->company_key,
            "serverId" => $this->server_id,
            "username" => $this->agent_username,
            "startDate" => $startDate,
            "endDate" => $endDate,
            "lang" => $this->language,
            "method" => self::EXTRA_API['getVirtualSportsBetListByModifyDate']
        );

        return $this->callApi(self::EXTRA_API['getVirtualSportsBetListByModifyDate'], $params, $context);
    }

    public function getCasinoBeautyGameLogs($startDate, $endDate) {
        $result = array();
        $result[] = $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+30 minutes', function($startDate, $endDate)  {
            $startDate = $startDate->format('Y-m-d H:i:s');
            $endDate = $endDate->format('Y-m-d H:i:s');
            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncOriginalGameRecords',
                'type' => 'casino',
            );

            $params = array(
                "companyKey"    => $this->company_key,
                "serverId"      => $this->server_id,
                "username"      => $this->agent_username,
                "startDate"     => $startDate,
                "endDate"       => $endDate,
                "method"        => self::EXTRA_API['getCustomerLiveCasinoBeautyBetListByModifyDate']
            );

            $this->CI->utils->debug_log('-----------------------sbobet getCasinoBeautyGameLogs params ----------------------------',$params);
            return $this->callApi(self::EXTRA_API['getCustomerLiveCasinoBeautyBetListByModifyDate'], $params, $context);
        });
        return array(true, $result);
    }

    public function getSeamlessGameProviderGameLogs($startDate, $endDate) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameRecords',
            'type' => 'seamless_gameprovider',
        );

        $result = [];

        if (!is_array($this->seamless_game_provider_code) || empty($this->seamless_game_provider_code)) {
            $this->CI->utils->info_log('<=== SEAMLESS GAME_PROVIDER MUST BE IN ARRAY AND CANNOT BE EMPTY ===>');
            return;
        }

        foreach ($this->seamless_game_provider_code as $game_provider) {
            $params = array(
                "companyKey" => $this->company_key,
                "gameProvider" => $game_provider,
                "serverId" => $this->server_id,
                "username" => $this->agent_username,
                "startDate" => $startDate,
                "endDate" => $endDate,
                "method" => self::EXTRA_API['getSeamlessGameProviderBetListByModifydate']
            );
            $result += $this->callApi(self::EXTRA_API['getSeamlessGameProviderBetListByModifydate'], $params, $context);
        }
        return $result;

    }

    public function getSeamlessGameProviderBonusBetListByModifyDate($startDate, $endDate){
    	$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameRecords',
            'type' => 'seamless_gameprovider_bonus',
        );

        $result = [];

        if (!is_array($this->seamless_game_provider_code) || empty($this->seamless_game_provider_code)) {
            $this->CI->utils->info_log('<=== SEAMLESS GAME_PROVIDER MUST BE IN ARRAY AND CANNOT BE EMPTY ===>');
            return;
        }

        foreach ($this->seamless_game_provider_code as $game_provider) {
            $params = array(
                "companyKey" => $this->company_key,
                "gameProvider" => $game_provider,
                "serverId" => $this->server_id,
                "username" => $this->agent_username,
                "startDate" => $startDate,
                "endDate" => $endDate,
                "method" => self::EXTRA_API['getSeamlessGameProviderBonusBetListByModifyDate']
            );
            $result += $this->callApi('getSeamlessGameProviderBonusBetListByModifyDate', $params, $context);
        }
        return $result;
    }

    public function processResultForSyncOriginalGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $resultArr = $this->getResultJsonFromParams($params);
        $type = $this->getVariableFromContext($params, 'type');
        $responseResultId = $this->getResponseResultIdFromParams($params);

        if ($type == "casino") {
            $success = isset($resultArr['result']) && count($resultArr['result']);
            $gameRecords = $resultArr['result'];
            $md5Fields = self::MD5_FIELDS_FOR_ORIGINAL_CASINO;
            $md5FloatFields = self::MD5_FLOAT_AMOUNT_FIELDS_CASINO;
        } elseif ($type == "virtualsports") {
            $success = isset($resultArr['result']) && count($resultArr['result']);
            $gameRecords = $resultArr['result'];
            $md5Fields = self::MD5_FIELDS_FOR_ORIGINAL_VIRTUALSPORTS;
            $md5FloatFields = self::MD5_FLOAT_AMOUNT_FIELDS_VIRTUALSPORTS;
        } else if ($type == "seamless_gameprovider") {
            $success = isset($resultArr['result']) && count($resultArr['result']);
            $gameRecords = isset($resultArr['result']) ? $resultArr['result'] : [];
            $md5Fields = self::MD5_FIELDS_FOR_ORIGINAL_SEAMLESSGAMEPROVIDER;
            $md5FloatFields = self::MD5_FLOAT_AMOUNT_FIELDS_SEAMLESSGAMEPROVIDER;
            $this->CI->utils->info_log('<==== SEAMLESS GAME_PROVIDER ====> ', $resultArr);
        } else if ($type == "seamless_gameprovider_bonus") {
            $success = isset($resultArr['result']) && count($resultArr['result']);
            $gameRecords = isset($resultArr['result']) ? $resultArr['result'] : [];
            $md5Fields = [];
            $md5FloatFields = [];
            $this->CI->utils->info_log('<==== SEAMLESS GAME_PROVIDER BONUS ====> ', $resultArr);
        }else{
            $success = isset($resultArr['PlayerBetList']) && count($resultArr['PlayerBetList']);
            $gameRecords = $resultArr['PlayerBetList'];
            $md5Fields = self::MD5_FIELDS_FOR_ORIGINAL_SPORTS;
            $md5FloatFields = self::MD5_FLOAT_AMOUNT_FIELDS_SPORTS;
        }

        $result = array();
        if ($success) {
            $dateTimeNow = date('Y-m-d H:i:s');

            if (!empty($gameRecords)) {
                $result['data_count'] = $count = 0;
                $extra = ['responseResultId'=>$responseResultId];
                $this->rebuildGameRecords($gameRecords,$extra,$type);

                list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    $this->original_gamelogs_table,
                    $gameRecords,
                    'external_uniqueid',
                    'external_uniqueid',
                    $md5Fields,
                    'md5_sum',
                    'id',
                    $md5FloatFields
                );

                if (!empty($insertRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
                }
                unset($insertRows);

                if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
                }
                unset($updateRows);

                $count++;
                $result['data_count'] = $count;
            }
        }
        return array($success, $result);
    }

	public function rebuildGameRecords(&$gameRecords,$extra,$type){
        $tempGameRec = [];
        foreach($gameRecords as $index => &$record) {
            $tempGameRec[$index]['refno'] = $record['refNo'];
            $tempGameRec[$index]['external_uniqueid'] = $record['refNo'];
            if ($type=="casino") {
                $tempGameRec[$index]['accountid'] = $record['accountId'];
                $tempGameRec[$index]['gameid'] = $record['gameId'];
                $tempGameRec[$index]['producttype'] = $record['ProductType'];
                $tempGameRec[$index]['tablename'] = $record['tableName'];
                $tempGameRec[$index]['winlose'] = $this->gameAmountToDB($record['winlost']);
                $tempGameRec[$index]['username'] = $record['accountId'];
                $tempGameRec[$index]['donetime'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['orderTime'])));
                unset($record['winlost']);
            } elseif ($type=="virtualsports") {
                $tempGameRec[$index]['accountid'] = $record['accountId'];
                $tempGameRec[$index]['gameid'] = $record['gameId'];
                $tempGameRec[$index]['tablename'] = $record['tableName'];
                $tempGameRec[$index]['winlose'] = $this->gameAmountToDB($record['winlost']);
                $tempGameRec[$index]['producttype'] = $record['ProductType'];
                $tempGameRec[$index]['username'] = $record['accountId'];
                $tempGameRec[$index]['subbet'] = json_encode($record['SubBets']);//parlay
                $tempGameRec[$index]['actualstake'] = $this->gameAmountToDB($record['actualStake']);
                unset($record['winlost']);
            } elseif ($type == "seamless_gameprovider") {
                $tempGameRec[$index]['ProductType'] = isset($record['gameType']) ? $record['gameType'] : null;
                $tempGameRec[$index]['accountId'] = isset($record['accountId']) ? $record['accountId'] : null;
                $tempGameRec[$index]['username'] = isset($record['accountId']) ? $record['accountId'] : (isset($record['username']) ? $record['username'] : null);
                $tempGameRec[$index]['external_game_id'] = isset($record['gameType']) ? $record['gameType'] : (isset($record['sportType']) ? $record['sportType'] : null);
                $tempGameRec[$index]['winlose'] = isset($record['winLost']) ? $this->gameAmountToDB($record['winLost']) : (isset($record['winlose']) ? $this->gameAmountToDB($record['winlose']) : null);
                $tempGameRec[$index]['actualStake'] = isset($record['turnOverStake']) ? $this->gameAmountToDB($record['turnOverStake']) : (isset($record['actualStake']) ? $this->gameAmountToDB($record['actualStake']) : null);
                $tempGameRec[$index]['turnover'] = isset($record['turnOverStake']) ? $this->gameAmountToDB($record['turnOverStake']) : (isset($record['actualStake']) ? $this->gameAmountToDB($record['actualStake']) : null);

                $WinLostDate = isset($record['WinLostDate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['WinLostDate']))) : (isset($record['winlostDate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['winlostDate']))) : null);
                $tempGameRec[$index]['oddsStyle'] = isset($record['oddsStyle']) ? $record['oddsStyle'] : null;
                $tempGameRec[$index]['sportType'] = isset($record['sportType']) ? $record['sportType'] : null;
                $tempGameRec[$index]['currency'] = isset($record['currency']) ? $record['currency'] : null;
                $tempGameRec[$index]['Ip'] = isset($record['Ip']) ? $record['Ip'] : null;
                $tempGameRec[$index]['isLive'] = isset($record['isLive']) ? $record['isLive'] : null;
                $tempGameRec[$index]['odds'] = isset($record['odds']) ? $record['odds'] : null;
                $tempGameRec[$index]['betOption'] = isset($record['subBet'][0]['betOption']) ? $record['subBet'][0]['betOption'] : null;
                $tempGameRec[$index]['marketType'] = isset($record['subBet'][0]['marketType']) ? $record['subBet'][0]['marketType'] : null;
                $tempGameRec[$index]['hdp'] = isset($record['subBet'][0]['hdp']) ? $record['subBet'][0]['hdp'] : null;
                $tempGameRec[$index]['league'] = isset($record['subBet'][0]['league']) ? $record['subBet'][0]['league'] : null;
                $tempGameRec[$index]['match'] = isset($record['subBet'][0]['match']) ? $record['subBet'][0]['match'] : null;
                $tempGameRec[$index]['winlostDate'] = isset($record['subBet'][0]['winlostDate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['subBet'][0]['winlostDate']))) : $WinLostDate;
                $tempGameRec[$index]['liveScore'] = isset($record['subBet'][0]['liveScore']) ? $record['subBet'][0]['liveScore'] : null;
                $tempGameRec[$index]['htScore'] = isset($record['subBet'][0]['htScore']) ? $record['subBet'][0]['htScore'] : null;
                $tempGameRec[$index]['ftScore'] = isset($record['subBet'][0]['ftScore']) ? $record['subBet'][0]['ftScore'] : null;
                $tempGameRec[$index]['customeizedBetType'] = isset($record['subBet'][0]['customeizedBetType']) ? $record['subBet'][0]['customeizedBetType'] : null;

                $record['ProductType'] = isset($record['gameType']) ? $record['gameType'] : null;
                $record['turnover'] = isset($record['turnOverStake']) ? $this->gameAmountToDB($record['turnOverStake']) : (isset($record['actualStake']) ? $this->gameAmountToDB($record['actualStake']) : null);

                unset($record['winlost']);
            } elseif ($type == "seamless_gameprovider_bonus") {
                $tempGameRec[$index]['ProductType'] = $type;
                $tempGameRec[$index]['accountId'] = isset($record['accountId']) ? $record['accountId'] : null;
                $tempGameRec[$index]['username'] = isset($record['accountId']) ? $record['accountId'] : null;
                $tempGameRec[$index]['external_game_id'] = $type;
                $tempGameRec[$index]['winlose'] = isset($record['winLost']) ? $this->gameAmountToDB($record['winLost']) : null;
                $tempGameRec[$index]['actualStake'] = isset($record['turnOverStake']) ? $this->gameAmountToDB($record['turnOverStake']) : null;
                $tempGameRec[$index]['turnover'] = isset($record['turnOverStake']) ? $this->gameAmountToDB($record['turnOverStake']) : null;

                $tempGameRec[$index]['winlostDate']  = isset($record['WinLostDate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['WinLostDate']))) : null;
                $record['ProductType'] = $type;
                $record['turnover'] = isset($record['turnOverStake']) ? $this->gameAmountToDB($record['turnOverStake']) : null;

                unset($record['winlost']);
            } else {
                $tempGameRec[$index]['username'] = $record['username'];
                $tempGameRec[$index]['oddsstyle'] = $record['oddsStyle'];
                $tempGameRec[$index]['sporttype'] = $record['sportType'];
                $tempGameRec[$index]['currency'] = $record['currency'];
                $tempGameRec[$index]['ip'] = $record['Ip'];
                $tempGameRec[$index]['islive'] = $record['isLive'];
                $tempGameRec[$index]['odds'] = $record['odds'];
                $tempGameRec[$index]['betoption'] = $record['subBet'][0]['betOption'];
                $tempGameRec[$index]['markettype'] = $record['subBet'][0]['marketType'];
                $tempGameRec[$index]['hdp'] = $record['subBet'][0]['hdp'];
                $tempGameRec[$index]['league'] = $record['subBet'][0]['league'];
                $tempGameRec[$index]['match'] = $record['subBet'][0]['match'];
                $tempGameRec[$index]['winlostdate'] = $record['subBet'][0]['winlostDate'];
                $tempGameRec[$index]['livescore'] = $record['subBet'][0]['liveScore'];
                $tempGameRec[$index]['htscore'] = $record['subBet'][0]['htScore'];
                $tempGameRec[$index]['ftscore'] = $record['subBet'][0]['ftScore'];
                $tempGameRec[$index]['customeizedbettype'] = $record['subBet'][0]['customeizedBetType'];
                $tempGameRec[$index]['winlostdate'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['winlostDate'])));
                $tempGameRec[$index]['subbet'] = json_encode($record['subBet']);//parlay
                $tempGameRec[$index]['actualstake'] = $this->gameAmountToDB($record['actualStake']);
                $tempGameRec[$index]['winlose'] = $this->gameAmountToDB($record['winlose']);
            }

            if ($record['modifyDate'] && $record['status'] != self::GAME_STATUS_RUNNING ) {
                $tempGameRec[$index]['modifydate'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['modifyDate'])));
                $tempGameRec[$index]['donetime'] = $tempGameRec[$index]['modifydate'];
            }

            $tempGameRec[$index]['status'] = $record['status'];
            $tempGameRec[$index]['stake'] = $this->gameAmountToDB($record['stake']);
            $tempGameRec[$index]['turnover'] = $this->gameAmountToDB($record['turnover']);
            $tempGameRec[$index]['external_game_id'] = !empty($record['sportType']) ? $record['sportType']:$record['ProductType'];//parlay

            $tempGameRec[$index]['response_result_id'] = $extra['responseResultId'];
            $tempGameRec[$index]['ordertime'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['orderTime'])));
        }
        $gameRecords = $tempGameRec;
        unset($tempGameRec);
    }

	private function updateOrInsertOriginalGameLogs($data, $queryType)
    {
        $dataCount=0;
        if(!empty($data)){
            if (!is_array($data)) {
                $data = json_decode($data,true);
            }
            if (is_array($data)) {
                foreach ($data as $record) {
                    if ($queryType == 'update') {
                        $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                    } else {
                        unset($record['id']);
                        $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                    }
                    $dataCount++;
                    unset($record);
                }
            }
        }
        return $dataCount;
    }

    public function syncMergeToGameLogs($token) {
    	$this->unknownGame = $this->getUnknownGame($this->getPlatformCode());
		$enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
	}

	public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
    	$sqlTime='IFNULL(`sbobet`.`donetime`,`sbobet`.`ordertime`) >= ?
          AND IFNULL(`sbobet`.`donetime`,`sbobet`.`ordertime`) <= ?';
        if($use_bet_time){
            $sqlTime='`sbobet`.`ordertime` >= ?
          AND `sbobet`.`ordertime` <= ?';
        }

        $sql = <<<EOD
            SELECT
                sbobet.external_uniqueid,
                sbobet.ordertime AS start_date,
                sbobet.donetime AS end_date,
                sbobet.producttype,
                sbobet.response_result_id,
                sbobet.winlose AS result_amount,
                sbobet.stake AS bet_amount,
                sbobet.actualstake AS real_betting_amount,
                sbobet.status as game_status,
                sbobet.refno,
                sbobet.actualstake,
                sbobet.accountid,
                sbobet.gameid,
                sbobet.subbet,
                sbobet.tablename,
                sbobet.donetime,
                sbobet.stake,
                sbobet.odds,
                sbobet.match,
                sbobet.islive,
                sbobet.betoption,
                sbobet.hdp,
                sbobet.sporttype,
                sbobet.livescore,
                sbobet.htscore,
                sbobet.ftscore,
                sbobet.oddsstyle,
                sbobet.markettype,
                sbobet.league,
                sbobet.username,
                sbobet.winlostdate,
                sbobet.customeizedbettype,
                sbobet.ordertime,
                sbobet.currency,
                sbobet.ip,
                sbobet.external_game_id,
                sbobet.md5_sum,
                game_provider_auth.player_id,
                game_description.id AS game_description_id,
                game_description.game_name AS game,
                game_description.game_code,
                game_description.game_type_id,
                game_description.void_bet,
                game_type.game_type
            FROM
                $this->original_gamelogs_table as sbobet
                    LEFT JOIN
                `game_description` ON sbobet.external_game_id = game_description.external_game_id
                    AND game_description.game_platform_id = ?
                    AND game_description.void_bet != 1
                    LEFT JOIN
                `game_type` ON game_description.game_type_id = game_type.id
                JOIN game_provider_auth ON sbobet.username = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
            WHERE
                {$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /**
     * * Payout of Sports: winlose - actualStake
     * * Payout of Casino: winlost
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
    {
        $status = $this->getGameRecordsStatus($row['game_status']);
        $this->processExtraDetails($row,$extra,$betDetails);

        $extra = array_merge($extra,[
            'match_details' => $row['match'],
            'match_type' => $row['islive'],
            'bet_info' => $row['betoption'],
            'handicap' => $row['hdp'],
            'table' => $row['external_uniqueid'],
            'is_parlay' => ($row['sporttype'] == self::IS_PARLAY) ? true : false,
            'real_betting_amount' => $row['real_betting_amount'],
        ]);

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
        $bet_amount = $row['bet_amount'];
        $game_status = strtolower($row['game_status']);
        if($game_status == "draw"){ //set bet amount to zero when draw
            $bet_amount = 0;
            $extra['note'] = lang("Draw");
        }

    	if (empty($row['producttype'])) { // if empty means game type is sports
            $result_amount = $row['result_amount']-$row['actualstake'];
        } else {
            // (winlost in api) for casino
            $result_amount = $row['result_amount'];
        }

        $processedRow = [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount,
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null
            ],
            'date_info' => [
                'start_at' => $row['start_date'],
                'end_at' => isset($row['end_date']) ? $row['end_date']:$row['start_date'],
                'bet_at' => $row['start_date'],
                'updated_at' => isset($row['end_date']) ? $row['end_date']:$row['start_date'],
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $status,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['external_uniqueid'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['response_result_id'],
                'bet_type' => stripos($row['sporttype'], 'Mix') === false ? 'Single' : $row['sporttype'],
            ],
            'bet_details' => $betDetails,
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $processedRow;
    }

    private function processExtraDetails($row,&$extra,&$betDetails)
    {
        $is_parlay = ($row['sporttype'] == self::IS_PARLAY) ? true : false;
        $betDetails = array_merge(
                array('is_parlay' => $is_parlay,'bet' => $row['stake'], 'rate' => $row['odds']),
                $this->processGameBetDetail($row));

        if(!empty($row['accountid']) && !empty($row['gameid']) ){
            $resultAmount = $row['result_amount'];
            $extra = array(
                'trans_amount'  =>  $row['bet_amount'],
            );
        }else{
            $resultAmount = ($row['game_status'] == "game_status") ? $row['result_amount'] : -($row['bet_amount']);
            $extra = array(
                'trans_amount'  =>  $row['actualstake'],
            );
        }
        $odds_style = strtolower($this->default_oddstyle);
        $odds_type = in_array($odds_style, self::ODDSTYLE) ? $odds_style : 'eu'; # Invalid odd style is defaulted to 'eu'
        $other_extra = array(
            'table'         =>  $row['gameid'],
            'odds'          =>  $row['odds'],
            'odds_type'     =>  $odds_type,
            'note'          =>  '',
        );
        $extra = array_merge($extra, $other_extra);
    }

    public function processGameBetDetail($rowArray){
        $betDetails =  array('sports_bet' => $this->setBetDetails($rowArray));
        $this->CI->utils->debug_log('=====> Bet Details return', $betDetails);
        return $betDetails;
    }

    public function setBetDetails($field){
        $data = json_decode($field['subbet'],true);
        $set = array();
        if(!empty($data)){
            foreach ($data as $key => $game) {
            	if(isset($game['liveScore'])){
            		$live = explode(':',$game['liveScore']);
            		$live = ($live[0] > 0 || $live[1]) > 0;	
            	}else{
            		$live = null;
            	}
                
                $set[$key] = array(
                    'yourBet' => isset($game['betOption'])?$game['betOption']:null,
                    'isLive' => $live,
                    'odd' => isset($game['odds'])?$game['odds']:null,
                    'hdp'=> isset($game['hdp'])?$game['hdp']:null,
                    'htScore'=> isset($game['htScore'])?$game['htScore']:null,
                    'eventName' => isset($game['match'])?$game['match']:null,
                    'league' => isset($game['league'])?$game['league']:null,
                );
            }
        }
        return $set;
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {
       if (empty($row['game_description_id'])) {
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$this->unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        if(empty($row['real_betting_amount'])){
        	$row['real_betting_amount'] = $row['bet_amount'];
        }
        $row['status'] = $this->getGameRecordsStatus($row['game_status']);
    }

    /**
	 * overview : get game record status
	 *
	 * @param $status
	 * @return int
	 */
	private function getGameRecordsStatus($status) 
	{
		$this->CI->load->model(array('game_logs'));
		$status = strtolower($status);

		switch ($status) {
		case 'running':
			$status = Game_logs::STATUS_ACCEPTED;
			break;
		case 'reject':
			$status = Game_logs::STATUS_REJECTED;
			break;
		case 'void':
			$status = Game_logs::STATUS_VOID;
			break;
		case 'refund':
			$status = Game_logs::STATUS_REFUND;
			break;
		case 'bonus':
		case 'won':
		case 'draw':
		case 'lose':
		case 'half won':
		case 'half lose':
			$status = Game_logs::STATUS_SETTLED;
			break;
		}
		return $status;
	}

	private function getGameDescriptionInfo($row, $unknownGame)
	{
		$game_description_id = null;
		$game_type_id = null;
		if (isset($row['game_description_id'])) {
			$game_description_id = $row['game_description_id'];
			$game_type_id = $row['game_type_id'];
		}

		if(empty($game_description_id)){
			$gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
				$unknownGame->game_type_id, $row['external_game_id'], $row['external_game_id']);
			$game_description_id = $gameDescId;
		}

		return [$game_description_id, $game_type_id];
	}

	public function generateUnique()
	{
		$dt = new DateTime($this->utils->getNowForMysql());
		return $dt->format('YmdHis').random_string('numeric', 6);
	}

	private function getReasons($statusCode)
	{
		switch ($statusCode) {
			case 400:
				return self::REASON_INCOMPLETE_INFORMATION;
				break;
			case 401:
				return self::REASON_INVALID_KEY;
				break;
			case 404:
				return self::REASON_INVALID_TRANSACTION_ID;
				break;
			case 409:
				return self::REASON_INVALID_TRANSFER_AMOUNT;
				break;
			case 500:
				return self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				break;		
			default:
                return self::REASON_UNKNOWN;
                break;
		}
	}

	private function getLauncherLanguage($language)
	{
		switch ($language) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE :
                $language = 'zh-cn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
                $language = 'id-id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
                $language = 'vi-vn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
                $language = 'ko-kr';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case Language_function::PLAYER_LANG_THAI :
                $language = 'th-th';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case Language_function::PLAYER_LANG_PORTUGUESE :
                $language = 'pt-pt';
                break;
            // case LANGUAGE_FUNCTION::INT_LANG_INDIA:
            // case Language_function::INT_LANG_PORTUGUESE :
            //     $language = 'in';
            //     break;
            default:
                $language = 'en';
                break;
        }
        return $language;
	}
    
	public function queryTransaction($transactionId, $extra) {
		$playerName=$extra['playerName'];
		$playerId=$extra['playerId'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName,
			'playerId'=>$playerId,
			'external_transaction_id' => $transactionId
		);

        $params = array(
            "companyKey" => $this->company_key,
            "txnId" => $transactionId,
            "serverId" => $this->server_id,
            "method" => self::URI_MAP[self::API_queryTransaction]
        );
        return $this->callApi(self::API_queryTransaction, $params, $context);
	}

	public function processResultForQueryTransaction($params) 
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$transId = $this->getVariableFromContext($params, 'external_transaction_id');
		$success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);

		$this->CI->utils->debug_log('oneworks query response', $resultJsonArr, 'transaction id', $transId);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$transId,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);
		if($success) {
			$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			$result['reason_id'] = $this->getTransferErrorReasonCode($resultJsonArr['error_code']);
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}
		return array($success, $result);
	}

	public function getTransferErrorReasonCode($apiErrorCode) {
		$reasonCode = self::COMMON_TRANSACTION_STATUS_APPROVED;

		switch ((int)$apiErrorCode) {
			case 1:
				$reasonCode = self::REASON_UNKNOWN;						# Failed during executed
				break;
			case 2:
			$reasonCode = self::REASON_NOT_FOUND_PLAYER;				# User does not exist
				break;
			case 3:
				$reasonCode = self::REASON_NO_ENOUGH_BALANCE;			# not enough balance
				break;
			case 4:
				$reasonCode = self::REASON_LOWER_OR_GREATER_THAN_MIN_OR_MAX_TRANSFER;	# lower or greater than min or max transfer
				break;
			case 5:
				$reasonCode = self::REASON_DUPLICATE_TRANSFER;			# dupplicate transaction id
				break;
			case 6:
				$reasonCode = self::REASON_CURRENCY_ERROR;				# currency error
				break;
			case 7:
				$reasonCode = self::REASON_INCOMPLETE_INFORMATION;		# input parameters error
				break;
			case 8:
				$reasonCode = self::REASON_UNKNOWN;						# member reach limit
				break;
			case 9:
				$reasonCode = self::REASON_AGENT_NOT_EXISTED;			# invalid vendor id
				break;
			case 10:
				$reasonCode = self::REASON_API_MAINTAINING;				# under maintenance
				break;
			case 11:
				$reasonCode = self::REASON_SESSION_TIMEOUT;				# system busy, try again after 2 minutes
				break;
		}

		return $reasonCode;
	}

	public function updatePlayerBetSettings($gameUsername,$minBet,$maxBet,$maxPerMatch,$casinoTableLimit) 
	{
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUpdatePlayerBetSettings',
			'gameUsername' => $gameUsername,
		);

		$params = array(
			"companyKey" => $this->company_key,
			"username" => $gameUsername,
			"min" => $minBet,
			"max" => $maxBet,
			"maxPerMatch" => $maxPerMatch,
			"casinoTableLimit" => $casinoTableLimit,
			"serverId" => $this->server_id,
			"method" => self::EXTRA_API['updatePlayerBetSettings']
		);
		return $this->callApi('updatePlayerBetSettings', $params, $context);
	}

	public function processResultForUpdatePlayerBetSettings($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);
        $update = $this->updateExternalSystemExtraInfo($this->getPlatformCode(),["is_player_betsettings_updated" => $success]);
        return array(true, $resultJsonArr);
    }

    public function updateAgentBetSettings($agentUsername,$minBet,$maxBet,$maxPerMatch,$casinoTableLimit) 
	{
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUpdateAgentBetSettings',
			'agentUsername' => $agentUsername,
		);

		$params = array(
			"companyKey" => $this->company_key,
			"username" => $agentUsername,
			"min" => $minBet,
			"max" => $maxBet,
			"maxPerMatch" => $maxPerMatch,
			"casinoTableLimit" => $casinoTableLimit,
			"serverId" => $this->server_id,
			"method" => self::EXTRA_API['updateAgentBetSettings']
		);
		return $this->callApi('updateAgentBetSettings', $params, $context);
	}

	public function processResultForUpdateAgentBetSettings($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);
        $update = $this->updateExternalSystemExtraInfo($this->getPlatformCode(),["is_player_betsettings_updated" => $success]);
        return array(true, $resultJsonArr);
    }

    public function updatePlayerBetSettingsBySportTypeAndMarketType($gameUsername) 
	{
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUpdatePlayerBetSettingsBySportTypeAndMarketType',
			'gameUsername' => $gameUsername,
		);

        $betSettings = $this->player_custom_bet_settings;
        if(empty($betSettings)){
        	 $betSettings = $this->agent_custom_bet_settings;
        	 if(empty($betSettings)){
        	 	return array("success" => true, "message" => "empty bet settings");
        	 }
        }

		$params = array(
			"companyKey" => $this->company_key,
			"username" => $gameUsername,
			"serverId" => $this->server_id,
			"betSettings" => $betSettings,
			"method" => self::EXTRA_API['updatePlayerBetSettingsBySportTypeAndMarketType']
		);
		return $this->callApi('updatePlayerBetSettingsBySportTypeAndMarketType', $params, $context);
	}

	public function updateAgentPresetBetSettingsBySportTypeAndMarketType($agentUsername) 
	{
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUpdateAgentPresetBetSettingsBySportTypeAndMarketType',
			'gameUsername' => $agentUsername,
		);

        $betSettings = $this->agent_custom_bet_settings;
       
		if(empty($betSettings)){
			return array("success" => true, "message" => "empty bet settings");
		}
        

		$params = array(
			"companyKey" => $this->company_key,
			"username" => $agentUsername,
			"serverId" => $this->server_id,
			"betSettings" => $betSettings,
			"method" => self::EXTRA_API['updateAgentPresetBetSettingsBySportTypeAndMarketType']
		);
		return $this->callApi('updateAgentPresetBetSettingsBySportTypeAndMarketType', $params, $context);
	}

	public function processResultForUpdateAgentPresetBetSettingsBySportTypeAndMarketType($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);
        // $update = $this->updateExternalSystemExtraInfo($this->getPlatformCode(),["is_player_betsettings_updated" => $success]);
        return array($success, $resultJsonArr);
    }

	public function processResultForUpdatePlayerBetSettingsBySportTypeAndMarketType($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);
        // $update = $this->updateExternalSystemExtraInfo($this->getPlatformCode(),["is_player_betsettings_updated" => $success]);
        return array($success, $resultJsonArr);
    }

    /*
	    This function will resend seamless wallet request only for settled or void sports bet
		settled bet will send SETTLE
		void bet will send CANCEL
	*/
    public function resendSeamlessWallet($transferCode = null){
    	$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForResendSeamlessWallet'
		);

		if(empty($transferCode)){
			$transferCode = $this->transfer_code_for_resend_settle_and_void;
		}

		if(is_array($transferCode)){
			$transferCode = implode(",", $transferCode);
		}

		$params = array(
			"companyKey" => $this->company_key,
			"transferCode" => $transferCode,
			"serverId" => $this->server_id,
			"method" => self::EXTRA_API['resendSeamlessWallet']
		);
		return $this->callApi(self::EXTRA_API['resendSeamlessWallet'], $params, $context);
    }

    public function processResultForResendSeamlessWallet($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);
        return array($success, $resultJsonArr);
    }

    private function getCacheKey($cacheId){
    	return 'game-api-'.$this->getPlatformCode().$cacheId;
    }

    public function logout($gameUsername, $password = null) {
    	return $this->returnUnimplemented();
	}

	public function updatePlayerInfo($playerName, $infos=null) {
		return $this->returnUnimplemented();
	}

	public function login($playerName, $password = null, $extra = null) {
		return $this->returnUnimplemented();
	}

	public function syncPlayerAccount($playerName, $password, $playerId) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
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

	public function changePassword($playerName, $oldPassword = null, $newPassword) {
		return $this->returnUnimplemented();
	}
}