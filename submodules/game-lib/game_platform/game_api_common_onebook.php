<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__).'/oneworks_betdetails_module.php';

class Game_api_common_onebook extends Abstract_game_api {
	use oneworks_betdetails_module;
	const SUCCESS_CODE = 0;
	const DUPLICATE_VENDOR_MEMBER_ID = 6;
	const ACCOUNT_EXISTS_CODE = 1001;
	const ORIGINAL_GAMELOGS_TABLE = 'onebook_game_logs';
	const DEPOSIT = 1;
	const WITHDRAWAL = 0;

	const API_getLeagueName = "GetLeagueName";
    const API_getTeamName = "GetTeamName";
    const API_getGameDetail = "GetGameDetail";
    const API_getVirtualResult = "GetVirtualGameResult";
    const API_getBetDetailByTransId = "GetBetDetailByTransId";
    const API_getSystemParlayDetail = "GetSystemParlayDetail";
    const API_getMemberBetSetting = "GetMemberBetSetting";
    const API_getSabaUrl = "GetSabaUrl";
    const API_getLoginUrl = "GetLoginUrl";

	const VIRTUAL_WINPLACE_BET = [
		1231,1232,1233,1237,1238
	];

	#SPORT TYPE
	const VIRTUAL_SPORTS = [180,181,182,183,184,185,186];
	const VIRTUAL_SPORTS2 = [190,191,192,193,194,195,196,199];
	const LG_VIRTUAL_SPORTS = 231;
	const RNG_CASINO = 251;
	const RNG = [208,209,210,219];
	const KENO = [202,220];
	const THIRD_PARTY = [212];
	const NUMBER_GAME = [161,164];
	const LIVE_CASINO = 162;
	const RACING = 154;
	//END OF SPORT TYPE

	const SPORTS = 'sports';
	const ESPORTS_GAME = 'esports';
	const E_SPORTS_GAME = 'e_sports';

	const SPORT_TYPE = [
		self::ESPORTS_GAME=>43
	];

	const NUMBER_BALL = 37.5;
	const OUTRIGHT = 10;
	const HANDICAP_BET = [
		1,7,17,20,21,25,27,153,154,155,
		168,183,219,301,303,453,44,460,
		609,613,614,2705,2801,2802,2805,
		2806,2809,1303,1308,501,1201,1311,
		1316,1324,468
	];
	const OVERUNDER_BET =[
		//sports
		3,8,18,85,156,178,197,198,204,205,220,302,304,
		401,402,403,404,461,462,463,464,610,615,616,2703,
		2704,2803,2804,2811,2812,1306,1203,
		//number games
		51,52,53,54,55,56,81,82,
		//x total games
		1312
	];
	const ODDEVEN_BET =[
		//sports
		2,12,86,136,137,138,139,157,
		184,194,203,428,611,1305,1318,
		//number games
		83,84
	];
	const ONEXTWO_BET = [
		5,15,28,124,125,160,164,
		176,177,430,458,459,502,
		2701,2702
	];
	const CORRECT_SCORE_BET = [
		4,30,152,158,165,166,
		405,413,414,416,2707
	];
	const YES_NO_BET = [
		133,438,134,439,135,145,146,
		433,147,436,148,437,149,440,
		150,41,173,174,188,427,189,
		434,190,435,210,211,212,213,
		214,215
	];
	const HALF_SCORING_BET = [
		140,141,142,442,443,444,452
	];
	const DOUBLE_CHANCE_BET = [
		24,151,186,410,431
	];
	const EXACT_GOALS_BET = [
		129,130,159,161,162,
		179,181,182,187,195,
		196,200,201,40,407,409,412
	];
	const FIRST_LAST_CORNER_BET = [
		206,207,208,209
	];
	const QUARTERXY_BETTYPE= [
		609,610,611,612,
		613,614,615,616,61,
		//other
		154,155,156,219,220
	];
	const NO_HDP_DISPLAY = [5];
	const IS_LIVE = 1;
	const IS_HOME = 'h';
	const HAPPY_5_SPORTTYPE = 168;

	const WALLET_TYPE = [
		"SPORTSBOOK" => 1,
		"AG" => 5,
		"GD" => 6,
		"EXCHANGE" => 13,
	];

	const SKINCOLOR_TYPES = [
		"blue1" => "bl001",
		"blue2" => "bl002",
		"blue3" => "bl003",
		"red1" => "rd001",
		"red2" => "rd002",
		"red3" => "rd003",
		"green1" => "gn001",
		"green2" => "gn002",
		"orange1" => "or001",
		"purple1" => "pp001",
	];

	const ODDSTYPE = [
		"malay" => 1,
		"china" => 2,
		"decimal" => 3,
		"indo" => 4,
		"us" => 5
	];
	const BETTYPE_PENALTYSHOOTOUTCOMBO = 376;

	const STATUS_HALF_WON = "half won";
	const STATUS_HALF_LOSE = "half lose";
	const STATUS_WON = "won";
	const STATUS_LOSE = "lose";
	const STATUS_RUNNING = "running";
	const STATUS_DRAW = "draw";
	const STATUS_REJECT = "reject";
	const STATUS_REFUND = "refund";
	const STATUS_WAITING = "waiting";

	const VALID_BET_IS_ZERO_STATUSES = [
		self::STATUS_DRAW, self::STATUS_REJECT, self::STATUS_REFUND
	];

	# Fields in onebook_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
		"trans_id",
		"vendor_member_id",
		"operator_id",
		"league_id",
		"match_id",
		"team_id",
		"home_id",
		"away_id",
		"match_datetime",
		"sport_type",
		"bet_type",
		"parlay_ref_no",
		"odds",
		"original_stake",
		"stake",
		"range",
		"validbetamount",
		"transaction_time",
		"ticket_status",
		"commission",
		"buyback_amount",
		"winlost_amount",
		"after_amount",
		"currency",
		"winlost_datetime",
		"odds_type",
		"odds_info",
		"lottery_bettype",
		"bet_team",
		"exculding",
		"islucky",
		"parlay_type",
		"combo_type",
		"bet_tag",
		"pool_type",
		"betchoice",
		"home_hdp",
		"away_hdp",
		"hdp",
		"betfrom",
		"islive",
		"home_score",
		"away_score",
		"os",
		"browser",
		"settlement_time",
		"custominfo1",
		"custominfo2",
		"custominfo3",
		"custominfo4",
		"custominfo5",
		"ba_status",
		"bonus_code",
		"wallet_type",
		"ref_code",
		"version_key",
		"parlaydata",
		"colossusbetdata",
		"cashoutdata",
		"last_ball_no",
		"race_number",
		"race_lane",
		"percentage",
	];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
    	"stake",
    	"winlost_amount",
    	"after_amount",
    	"buyback_amount",
    	"validbetamount",
    	"original_stake",
    	"odds",
    	"home_hdp",
    	"away_hdp",
    	"hdp",
    ];

    # Fields in nttech_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'trans_id',
        'status_in_db',
        'version_key',
        'sport_type',
        'winlost_datetime',
		'settlement_time',
        'odds_type',
        'odds',
        'bet_amount',
        // 'round',
        'game_code',
        // 'game_name',
        'after_balance',
        'valid_bet',
        'result_amount',
        'username',
        'bet_at',
        'end_at'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];

    const URI_MAP = array(
			self::API_createPlayer => 'CreateMember',
			self::API_queryPlayerBalance => 'CheckUserBalance',
	        self::API_depositToGame => 'FundTransfer',
	        self::API_withdrawFromGame => 'FundTransfer',
	        self::API_queryTransaction => 'CheckFundTransfer',
	        self::API_login => 'Login',
	        self::API_logout => 'KickUser',
			self::API_syncGameRecords => 'GetBetDetail',
			self::API_blockPlayer => "LockMember",
    		self::API_unblockPlayer => "UnlockMember",
			self::API_updatePlayerInfo => "UpdateMember",
        	self::API_getLeagueName => "GetLeagueName",
			self::API_getTeamName => "GetTeamName",
		    self::API_getVirtualResult => 'GetVirtualGameResult',
		    self::API_getBetDetailByTransId => 'GetBetDetailByTransID',
		    self::API_getSystemParlayDetail => 'GetSystemParlayDetail',
		    self::API_getMemberBetSetting => 'GetMemberBetSetting',
		    self::API_setMemberBetSetting => 'SetMemberBetSetting',
		    self::API_operatorLogin => 'GetLoginToken',
		    self::API_getGameDetail => 'GetGameDetail',
            self::API_getSabaUrl => "GetSabaUrl",
            self::API_getLoginUrl => "GetLoginUrl", 

            // seamless API URL
            self::API_queryForwardGame => 'Login',
            self::API_checkTicketStatus => 'checkticketstatus',
            self::API_retryOperation => 'retryoperation',
            self::API_getReachLimitTrans => 'getreachlimittrans',
		);

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->gamelaunch_url = $this->getSystemInfo('web_gamelaunch_url');
		$this->esports_gamelaunch_url = $this->getSystemInfo('esports_gamelaunch_url');
		$this->vendor_id = $this->getSystemInfo('vendor_id');
		$this->operator_id = $this->getSystemInfo('operator_id');
		$this->currency_id = $this->getSystemInfo('currency_id');
		$this->player_min_transfer = $this->getSystemInfo('player_min_transfer');
		$this->player_max_transfer = $this->getSystemInfo('player_max_transfer');
		$this->odds_type = $this->getSystemInfo('odds_type');
		$this->home_url = $this->getSystemInfo('home_url');
		$this->extend_session_url = $this->getSystemInfo('extend_session_url');
		$this->login_url = $this->getSystemInfo('login_url');
		$this->signup_url = $this->getSystemInfo('signup_url');
		$this->original_gamelogs_table = self::ORIGINAL_GAMELOGS_TABLE;
		$this->bet_detail_default_lang = $this->getSystemInfo('bet_detail_default_lang','en');
		$this->mobile_skin = $this->getSystemInfo('mobile_skin');
		$this->cert = $this->getSystemInfo('cert');
		$this->agent_id = $this->getSystemInfo('agent_id');
        $this->web_skin_type = $this->getSystemInfo('web_skin_type','0');
		$this->skin_color = $this->getSystemInfo('skincolor', self::SKINCOLOR_TYPES['blue1']);
		$this->default_game_type = $this->getSystemInfo('default_game_type', '');
		$this->use_force_language = $this->getSystemInfo('use_force_language', false);

		$this->use_sportsid_for_esports = $this->getSystemInfo('use_sportsid_for_esports', false);

		$this->max_payout_per_match_multiplier = $this->getSystemInfo('max_payout_per_match_multiplier', 8);
		$this->fixed_parlay_game_description_enabled = $this->getSystemInfo('fixed_parlay_game_description_enabled', false);#for manual fix only
        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 7);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 30);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 7);
	}

	public function getPlatformCode()
	{
		return $this->returnUnimplemented();
	}

	public function generateUrl($apiName, $params)
	{
		$apiUri = self::URI_MAP[$apiName];
		return $this->api_url . "/" . $apiUri;
	}

	protected function customHttpCall($ch, $params)
	{
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
	}

	public function processResultBoolean($responseResultId, $resultArr, $statusCode)
	{
		$success = false;
		if((@$statusCode == 200 || @$statusCode == 201) && ($resultArr['error_code'] == self::SUCCESS_CODE || $resultArr['error_code'] == self::DUPLICATE_VENDOR_MEMBER_ID)){
			$success = true;
		}

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->debug_log('ONEBOOK got error: ', $responseResultId,' Result:', $resultArr);
		}
		$this->CI->utils->debug_log('ONEBOOK RawSuccessResponse: ', $responseResultId,'Result:', $resultArr);
		return $success;
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
			'bet_limit_settings' => null,
		);

		if(!empty($extra) && is_array($extra)){
			if(array_key_exists('bet_limit_settings',$extra)){
				$context['bet_limit_settings'] = $extra['bet_limit_settings'];
			}
		}

		$params = array(
			"vendor_id" => $this->vendor_id,
			"vendor_member_id" => $gameUsername,
			"operatorid" => $this->operator_id,
			"currency" => $this->currency_id,
			"username" => $gameUsername,
			"oddstype" => $this->odds_type,
			"maxtransfer" => $this->player_max_transfer,
			"mintransfer" => $this->player_min_transfer,
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
			// $bet_limit_settings = $this->getVariableFromContext($params, 'bet_limit_settings');
			// $this->setMemberBetSetting($playerName,$bet_limit_settings);
		}
		$this->CI->utils->debug_log('create player result: ' . json_encode($resultJsonArr));
		return array($success, $resultJsonArr);
    }

    /**
	 * overview : set member settings
	 *
	 * @param $playerName
	 * @param $newBetSetting
	 * @return array
	 */
	public function setMemberBetSetting($playerName,$newBetSetting=null) {		
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForMemberSettings',
			'playerName' => $playerName,
		);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$sportsTypeArr = $this->oneworks_bet_setting;
		$betSettingArr = array();

		if (!empty($sportsTypeArr)) {
			foreach ($sportsTypeArr as $key) {
				if (!empty($key['sport_type'])) {
					foreach ($key['sport_type'] as $type) {
						$betSettingArr[$type] = array(
							"sport_type" => $type,
							"min_bet" => $key['min_bet'],
							"max_bet" => $key['max_bet'],
							"max_bet_per_match" => $key['max_bet_per_match'],
							"max_payout_per_match" => (isset($key['max_payout_per_match'])?$key['max_payout_per_match']:$key['max_bet_per_match']*$this->max_payout_per_match_multiplier),
							"max_bet_per_ball" => $key['max_bet_per_ball'],
						);
					}
				}
			}
		}

		# Update new settings
		if(!empty($newBetSetting)){
			foreach ($newBetSetting as $setting) {
				$betSettingArr[$setting['sport_type']] = $setting;
			}
		}

		# RE-KEY ARRAY
		$betSettingArr = array_values($betSettingArr);

		$params = array(
			"vendor_id" => $this->vendor_id,
			"vendor_member_id" => $gameUsername,
			"bet_setting" => json_encode($betSettingArr),
		);

		return $this->callApi(self::API_setMemberBetSetting, $params, $context);
	}

	/**
	 * overview : process result for member settings
	 *
	 * @param $params
	 * @return array
	 */
	public function processResultForMemberSettings($params) {
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
		$result = array('response_result_id' => $responseResultId, 'result' => $resultJson);
		return array($success, $result);
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
			"vendor_id" => $this->vendor_id,
			"vendor_member_ids" => $gameUsername,
			"wallet_id" => self::WALLET_TYPE[$this->wallet_type],
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
		$result['balance'] = 0;
		if($success){
			$result['balance'] = @$resultArr['Data'][0]['balance'] ?: 0;
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
			"vendor_id" => $this->vendor_id,
			"vendor_member_id" => $gameUsername,
			"vendor_trans_id" => $this->getSystemInfo('prefix_for_username').$external_transaction_id,
			"amount" => $amount,
			"currency" => $this->currency_id,
			"direction" => self::DEPOSIT,
			"wallet_id" => self::WALLET_TYPE[$this->wallet_type],
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

			if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
				$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				$success=true;
			}else{
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
				$result['reason_id'] = $this->getReasons($statusCode);
			}
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
			"vendor_id" => $this->vendor_id,
			"vendor_member_id" => $gameUsername,
			"vendor_trans_id" => $this->getSystemInfo('prefix_for_username').$external_transaction_id,
			"amount" => $amount,
			"currency" => $this->currency_id,
			"direction" => self::WITHDRAWAL,
			"wallet_id" => self::WALLET_TYPE[$this->wallet_type],
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

    # generates newGameUrl for login&gamelaunch
    public function getSabaUrl($playerName, $extra){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $is_mobile = $extra['is_mobile'];

        if($is_mobile)
        {
            $platform = 2;
        }
        else
        {
            $platform = 1;
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetSabaUrl',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $params = array(
            "vendor_id" => $this->vendor_id,
            // "operatorid" => $this->operator_id,
            "vendor_member_id" => $gameUsername,
            "platform" => $platform,
        );

        $this->CI->utils->debug_log('GetSabaUrl params: ' . http_build_query($params));
        return $this->callApi(self::API_getSabaUrl, $params, $context);
    }

    public function processResultForGetSabaUrl($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJsonArr, $statusCode);
        $result = array();
        $result['gameUrl'] = NULL;
        if($success){
            $result['gameUrl'] = isset($resultJsonArr['Data']) ? $resultJsonArr['Data'] : NULL;
        }
        return array($success, $result);
    }

	/*
	 *	To Launch Game
	 *
	 *  Game launch URL
	 *  ~~~~~~~~~~~~~~~
	 *
	 *  goto_onebook_game/<game_platform_id>/<language>/<is_mobile>/<skin_id>/<oddstype>
	 *  Default: player_center/goto_onebook_game/2123/en
	 *  Desktop: player_center/goto_onebook_game/2123/en/false/blue1/5
	 *  Mobile: player_center/goto_onebook_game/2123/en/true/blue1
	 *
	 */
	public function queryForwardGame($playerName, $extra = null)
	{
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
	
        $url = $this->buildGameLauncher($playerName,$gameToken=null,$extra);
        $this->CI->utils->debug_log('ONEBOOK queryForwardgame URL:', $url);
        return [
                'success'=>true,
                'url'=> $url
                ];
	}

	private function buildGameLauncher($playerName,$gameToken,$extraParams)
	{
		#GET LANG FROM PLAYER DETAILS
		$playerId = $this->getPlayerIdFromUsername($playerName);

        $language = $this->getSystemInfo('language', $extraParams['language']);
        $language = $this->getLauncherLanguage($language);

		if($this->use_force_language){
			$language = $this->getLauncherLanguage($this->use_force_language);
		}

		#IDENTIFY IF SKINCOLOR IS INVOKED IN GAME URL, ELSE USE DEFAULT SKIN
		if(isset($extraParams['extra']['skincolor'])){
			$skincolorVal = $extraParams['extra']['skincolor'];
			if(in_array($skincolorVal,self::SKINCOLOR_TYPES)){
				$skincolor = $skincolorVal;
			}else{
				$skincolor = $this->skin_color;
			}
		}else{
			$skincolor = $this->skin_color;
		}

		#IDENTIFY IF ODDSTYPE IS INVOKED IN GAME URL, ELSE USE EXTRA INFO DATA
		if(isset($extraParams['extra']['oddstype'])){
			$oddstypeVal = $extraParams['extra']['oddstype'];
			if(in_array($oddstypeVal,self::ODDSTYPE)){
				$oddstype = $oddstypeVal;
			}else{
				$oddstype = $this->odds_type;
			}
		}else{
			$oddstype = $this->odds_type;
		}

		$urlParams = [
						"token" => $gameToken,
						"lang" => $language,
						"Otype" => $oddstype,
						"skincolor" => $skincolor,
					 ];

		$gameType = isset($extraParams['game_type'])?$extraParams['game_type']:null;
		$gameType = $this->getGameType($gameType);

		if(!empty($gameType)){
			$urlParams['game'] = $gameType;
		}

		//launch specific game type
		switch ($gameType) {
			case self::ESPORTS_GAME:
				$urlParams['act'] = self::SPORT_TYPE[self::ESPORTS_GAME];
				unset($urlParams['game']);
			break;
		}

		if($this->home_url){
			$urlParams['homeUrl'] = $this->home_url;
		}

		if(isset($extraParams['home_link'])&&!empty($extraParams['home_link'])){
			$urlParams['homeUrl'] = $extraParams['home_link'];
		}

		if($this->extend_session_url){
			$urlParams['extendSessionUrl'] = $this->extend_session_url;
		}

		#IDENTIFY IF LAUNCH IN MOBILE MODE
		if(isset($extraParams['is_mobile'])){
			$isMobile = $extraParams['is_mobile'] ? true : false;
			if($isMobile){
				$this->gamelaunch_url = $this->getSystemInfo('mobile_gamelaunch_url');
				if($this->mobile_skin){
					$urlParams['skin'] = $this->mobile_skin;
				}
				
				if($this->use_sportsid_for_esports && $gameType==self::ESPORTS_GAME){
					$urlParams['sportid'] = self::SPORT_TYPE[self::ESPORTS_GAME];
				}else{
					$urlParams['types'] = $gameType;
				}
				
			}
		}
        // uses URL from getSabaUrl for game launch
        $sabaUrl = $this->getSabaUrl($playerName, $extraParams);
        $newGameUrl = isset($sabaUrl['gameUrl']) ? $sabaUrl['gameUrl'] : NULL;
        $url = null;
  
            if($newGameUrl != null){
                $url = $newGameUrl.'&'.http_build_query($urlParams);
            }
            else {
                $url = $this->gamelaunch_url.'?'.http_build_query($urlParams);
            }

        $this->CI->utils->debug_log('oneworks queryForwardGame login URL--------->: ', $url);

        return $url;
	}

	public function getGameType($type){
		switch ($type) {
			case 'esports':
			case 'e_sports':
				return self::ESPORTS_GAME;
			default:
				return $this->default_game_type;
			}
	}

	// public function processResultForLogin($params)
	// {
	// 	$statusCode = $this->getStatusCodeFromParams($params);
	// 	$resultArr = $this->getResultJsonFromParams($params);
	// 	$responseResultId = $this->getResponseResultIdFromParams($params);
	// 	$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
    //     return array($success, $resultArr);
	// }

	public function logout($playerName, $password = null)
	{
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
    	$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'gameUsername' => $gameUsername,
        );
        $params = array(
			"vendor_id" => $this->vendor_id,
			"vendor_member_id" => $gameUsername
		);
		return $this->callApi(self::API_logout, $params, $context);
	}

	public function processResultForLogout($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr,$statusCode);
		return array($success,["logout"=>true]);
	}

	public function syncOriginalGameLogs($token = false)
	{
		$startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
		$endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
		$startDate->modify($this->getDatetimeAdjust());
		$this->CI->utils->debug_log('startDate', $startDate, 'endDate', $endDate);

		$context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs',
        ];

		$done = false;
		$result = ["success" => false];

		while (!$done) {
			# get the last version key in db
			# check if last version key exist, if null it means it needs to call the first record which is 0 version key
			$last_version_key = $this->CI->external_system->getLastSyncIdByGamePlatform($this->getPlatformCode()) ?: 0;
			$params = [
						"vendor_id" => $this->vendor_id,
						"version_key" => $last_version_key
					  ];
			$resultData = $this->callApi(self::API_syncGameRecords, $params, $context);
			$result = ["success" => $resultData['success']];

			//error or done
			$done = $resultData['success'];
			if(!$resultData['success']){
				$this->CI->utils->error_log('wrong result', $resultData);
				$result['error_message']=@$resultData['error_message'];
			}
		}
		return $result;
	}

	public function processResultForSyncOriginalGameLogs($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId,$resultArr,$statusCode);

		$lastVersionKey = isset($resultArr['Data']['last_version_key'])?$resultArr['Data']['last_version_key']:null;
		$result = ['data_count' => 0,'last_version_key' => $lastVersionKey];

		$betDetailsGameRecords = !empty($resultArr['Data']['BetDetails'])?$resultArr['Data']['BetDetails']:[];
		$betNumberDetailsGameRecords = !empty($resultArr['Data']['BetNumberDetails'])?$resultArr['Data']['BetNumberDetails']:[];
		$betVirtualSportGameRecords = !empty($resultArr['Data']['BetVirtualSportDetails'])?$resultArr['Data']['BetVirtualSportDetails']:[];
		$gameRecords = !empty($resultArr['Data']['BetDetails'])?$resultArr['Data']['BetDetails']:[];

		$gameRecords = array_merge($betDetailsGameRecords,$betNumberDetailsGameRecords,$betVirtualSportGameRecords);

		if($success && !empty($gameRecords)){
            $extra = ['response_result_id' => $responseResultId];
            // $this->CI->utils->debug_log('onebook 1THBGAMERECORDS', $gameRecords);
            $this->rebuildGameRecords($gameRecords,$extra);

            $oldCnt=count($gameRecords);
            $this->CI->load->model(array('original_game_logs_model'));
            $this->CI->original_game_logs_model->removeDuplicateUniqueid($gameRecords, 'trans_id', function($row1st, $row2nd){
				//compare status
				$status1st=strtolower($row1st['ticket_status']);
				$status2nd=strtolower($row2nd['ticket_status']);
				//if same status, keep second
				if($status1st==$status2nd){
					return 2;
				}else if($status1st=='waiting'){
					return 2;
				}else if($status2nd=='waiting'){
					return 1;
				}else if($status1st=='running'){
					return 2;
				}else if($status2nd=='running'){
					return 1;
				}
				//default is last
				return 2;
			});
			$cnt=count($gameRecords);

			$this->CI->utils->debug_log('removeDuplicateUniqueid oldCnt:'.$oldCnt.', cnt:'.$cnt);

			$this->CI->load->model('original_game_logs_model');
			list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    $this->original_gamelogs_table,
                    $gameRecords,
                    'external_uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );

			$this->CI->utils->debug_log('ONEBOOK after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));
            $insertRows = json_encode($insertRows);
            unset($gameRecords);
            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows,'insert',$lastVersionKey);
            }
            unset($insertRows);
            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows,'update',$lastVersionKey);
            }
            unset($updateRows);
		}

		//will update last sync id
		if (!empty($lastVersionKey)) {
			$this->CI->external_system->setLastSyncId($this->getPlatformCode(),$lastVersionKey);
		}
		return array($success, $result);
	}

	public function rebuildGameRecords(&$gameRecords,$extra)
	{
		// echo "<pre>";
		// print_r($gameRecords);
		$availableFields=self::MD5_FIELDS_FOR_ORIGINAL;
        foreach($gameRecords as &$gr)
        {
        	// $this->CI->utils->debug_log('onebook GRINDEXNOTFILTER', $gr);
        	$gr=array_filter($gr, function($key) use($availableFields){
	            return in_array(strtolower($key), $availableFields);
	        }, ARRAY_FILTER_USE_KEY);

	        $gr['parlaydata'] = isset($gr['ParlayData'])?json_encode($gr['ParlayData']):null;
	        $gr['custominfo1'] = isset($gr['customInfo1'])&&$gr['customInfo1']?$gr['customInfo1']:null;
	        $gr['custominfo2'] = isset($gr['customInfo2'])&&$gr['customInfo2']?$gr['customInfo2']:null;
	        $gr['custominfo3'] = isset($gr['customInfo3'])&&$gr['customInfo3']?$gr['customInfo3']:null;
	        $gr['custominfo4'] = isset($gr['customInfo4'])&&$gr['customInfo4']?$gr['customInfo4']:null;
	        $gr['custominfo5'] = isset($gr['customInfo5'])&&$gr['customInfo5']?$gr['customInfo5']:null;
	        $gr['islucky'] = isset($gr['isLucky'])? $gr['isLucky']: null;
	        $gr['islive'] = isset($gr['islive'])? $gr['islive']: null;
	        $gr['home_score'] = isset($gr['home_score'])? $gr['home_score']: null;
	        $gr['away_score'] = isset($gr['away_score'])? $gr['away_score']: null;
	        $gr['league_id'] = isset($gr['league_id'])? $gr['league_id']: null;
	        $gr['odds_info'] = isset($gr['odds_Info'])? $gr['odds_Info']: null;
	        unset($gr['ParlayData'],$gr['customInfo1'],$gr['customInfo2'],$gr['customInfo3'],$gr['customInfo4'],$gr['customInfo5'],$gr['isLucky'],$gr['odds_Info']);

	        $gr['transaction_time'] = $this->gameTimeToServerTime($gr['transaction_time']);

	        $gr['match_datetime'] =  isset($gr['match_datetime'])?$this->gameTimeToServerTime($gr['match_datetime']):null;
	        $gr['winlost_datetime'] = isset($gr['winlost_datetime'])?$this->gameTimeToServerTime($gr['winlost_datetime']):null;
	        $gr['settlement_time'] = isset($gr['settlement_time'])?$this->gameTimeToServerTime($gr['settlement_time']):null;
	        $gr['external_uniqueid'] = $gr['trans_id'];
            $gr['response_result_id'] = $extra['response_result_id'];
            $gr['last_sync_time'] = $this->CI->utils->getNowForMysql();

            #UNCOMMON fields, make sure its in the list of fields even the game logs return does not have it, so that md5 keys checking wont failed
            $gr['parlay_type'] = isset($gr['parlay_type'])? $gr['parlay_type']: null;
            $gr['combo_type'] = isset($gr['combo_type'])? $gr['combo_type']: null;
            $gr['pool_type'] = isset($gr['pool_type'])? $gr['pool_type']: null;
            $gr['betchoice'] = isset($gr['betchoice'])? $gr['betchoice']: null;
            $gr['os'] = isset($gr['os'])? $gr['os']: null;
            $gr['bonus_code'] = isset($gr['bonus_code'])? $gr['bonus_code']: null;
            $gr['browser'] = isset($gr['browser'])? $gr['browser']: null;
            $gr['wallet_type'] = isset($gr['wallet_type'])? $gr['wallet_type']: null;
            $gr['ref_code'] = isset($gr['ref_code'])? $gr['ref_code']: null;
            $gr['colossusbetdata'] = isset($gr['colossusbetdata'])? $gr['colossusbetdata']: null;
            $gr['cashoutdata'] = isset($gr['cashoutdata'])? $gr['cashoutdata']: null;

            $gr['last_ball_no'] = isset($gr['last_ball_no'])? @$gr['last_ball_no']: null;
            $gr['parlay_ref_no'] = isset($gr['parlay_ref_no'])? @$gr['parlay_ref_no']: null;
            $gr['home_hdp'] = isset($gr['home_hdp'])? @$gr['home_hdp']: null;
            $gr['away_hdp'] = isset($gr['away_hdp'])? @$gr['away_hdp']: null;
            $gr['hdp'] = isset($gr['hdp'])? @$gr['hdp']: null;
            $gr['race_number'] = isset($gr['race_number'])? $gr['race_number']: null;
            $gr['race_lane'] = isset($gr['race_lane'])? $gr['race_lane']: null;
            $gr['bet_tag'] = isset($gr['bet_tag'])? $gr['bet_tag']: null;
	        $gr['team_id'] = isset($gr['team_id'])? $gr['team_id']: null;
	        $gr['home_id'] = isset($gr['home_id'])? $gr['home_id']: null;
	        $gr['away_id'] = isset($gr['away_id'])? $gr['away_id']: null;
	        $gr['range'] = isset($gr['range'])? $gr['range']: null;
	        $gr['commission'] = isset($gr['commission'])? $gr['commission']: null;
	        $gr['buyback_amount'] = isset($gr['buyback_amount'])? $gr['buyback_amount']: null;
	        // $gr['odds_info'] = isset($gr['odds_info'])? $gr['odds_info']: null;
	        $gr['lottery_bettype'] = isset($gr['lottery_bettype'])? $gr['lottery_bettype']: null;
	        $gr['exculding'] = isset($gr['exculding'])? $gr['exculding']: null;
	        $gr['validbetamount'] = isset($gr['validbetamount'])? $gr['validbetamount']: null;
			$gr['original_stake'] = isset($gr['original_stake'])? $gr['original_stake']: null;

			//if sportstype is null
			$gr['sport_type'] = isset($gr['sport_type']) && $gr['sport_type']!=null? $gr['sport_type']: 'unknown';
			if($gr['sport_type'] == 'unknown' && isset($gr['parlay_type'])){
    			$gr['sport_type'] = ($gr['parlay_type'] == "MixParlay") ? "99MP" : "parlay";
    		}
			$gr['after_amount'] = isset($gr['after_amount'])? $gr['after_amount']: null;
			$gr['bet_team'] = isset($gr['bet_team'])? $gr['bet_team']: null;
			$gr['match_id'] = isset($gr['match_id'])? $gr['match_id']: null;
			$gr['percentage'] = isset($gr['percentage'])? $gr['percentage']: null;
			$gr['ref_no'] = isset($gr['ref_no'])? $gr['ref_no']: null;
			$gr['voucher_quota'] = isset($gr['voucher_quota'])? $gr['voucher_quota']: null;
			// $this->CI->utils->debug_log('onebook GRINDEX', $gr);
			// $gr = array_unique($gr);
        }
	}

	public function updateOrInsertOriginalGameLogs($data, $queryType, $lastVersionKey)
	{
        $dataCount=0;
        if(!empty($data)){
            if (!is_array($data)) {
                $data = json_decode($data,true);
            }
            if (is_array($data)) {
                foreach ($data as $record) {
                	$record['last_version_key'] = $lastVersionKey;
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
    	$sqlTime='onebook.last_sync_time >= ? and onebook.last_sync_time <= ?';
        if($use_bet_time){
            $sqlTime='`onebook`.`transaction_time` >= ?
          AND `onebook`.`transaction_time` <= ?';
        }

        $sql = <<<EOD
			SELECT
				onebook.id as sync_index,
				onebook.response_result_id,
				onebook.match_id,
				onebook.vendor_member_id as username,
				onebook.stake as bet_amount,
				onebook.stake as valid_bet,
				onebook.winlost_amount as result_amount,
				onebook.ref_code,
				onebook.odds,
				onebook.odds_type,
				onebook.hdp,
				onebook.league_id,
				onebook.home_id,
				onebook.away_id,
				onebook.bet_team,
				onebook.parlaydata,
				onebook.islive,
				onebook.trans_id,
				onebook.sport_type,
				onebook.parlay_ref_no,
				onebook.parlay_type,
				onebook.combo_type,
				onebook.cashoutdata,
				onebook.home_hdp,
				onebook.away_hdp,
				onebook.original_stake,
				onebook.betchoice as bet_choice,
				onebook.after_amount as after_balance,
				onebook.last_sync_time as updated_at,
				onebook.bet_type as bet_type_oneworks,
				onebook.ticket_status as status_in_db,
				onebook.transaction_time as bet_at,
				onebook.settlement_time as end_at,
				onebook.sport_type as game_code,
				onebook.sport_type as game,
				onebook.external_uniqueid,
				onebook.md5_sum,
				onebook.winlost_datetime,
				onebook.version_key,
				onebook.percentage,
				game_provider_auth.player_id,
				gd.id as game_description_id,
				gd.game_name as game_description_name,
				gd.game_type_id
			FROM $this->original_gamelogs_table as onebook
			LEFT JOIN game_description as gd ON onebook.sport_type = gd.external_game_id AND gd.game_platform_id = ?
			LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
			JOIN game_provider_auth ON onebook.vendor_member_id = game_provider_auth.login_name
			AND game_provider_auth.game_provider_id=?
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

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row)
	{
		$extra = [
        	'match_type' => $row['match_type'],
        	'handicap' => $row['hdp'],
        	'odds' => $row['odds'],
        	'odds_type' => $row['odds_type'],
        	'is_parlay' => $row['parlaydata']
        ];

        if(isset($row['percentage_note']) ){ // For System parlay only
    		$extra['note'] = $row['percentage_note'];
    	}

        $has_both_side=0;
        if(!empty($row['cashoutdata']))
        {
        	$cash_out_stake = 0;
        	$cashoutdata = json_decode($row['cashoutdata']);
    		foreach ($cashoutdata as $key => $cashout) {
    			$row['result_amount'] += ((float)$cashout->buyback_amount - $cashout->real_stake);
    			$cash_out_stake += (float)$cashout->stake;
            }
            $extra['trans_amount'] = (!empty($row['original_stake'])) ? $row['original_stake'] : $cash_out_stake + $row['bet_amount'];
        }

        # no available amount when draw status
        if(strtolower($row['status_in_db']) == self::STATUS_DRAW){
        	$row['bet_amount'] = 0;
        	$extra['note'] = lang("Draw");
        }

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row,
            	self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        if(!empty($row['end_at'])){
        	$row['end_at'] = $row['end_at'];
        }else{
        	$row['end_at'] = $row['bet_at'];
		}

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => $row['game_type_id'],
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $row['valid_bet'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['valid_bet'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null,
            ],
            'date_info' => [
                'start_at' => $row['bet_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['external_uniqueid'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => $row['bet_type']
            ],
            'bet_details' => $row['bet_details'],
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {
		/*
		###ODDS table

			0	Special odds type, not belong to any odds type (only when sport_type=157, 168, 245
				OR bet_type=468, 469, 8700 OR Sport Group is Casino and Live Casino)
			1 Malay Odds
			2 China Odds
			3 Decimal Odds
			4 Indo Odds
			5 American Odds
			6 Myanmar Odds
		*/

		$initialOddsType = $this->getOddsType($row['odds_type']);

		$oddsType = $this->getUnifiedOddsType(strtolower($initialOddsType));
		
		###### START PROCESS BET AMOUNT CONDITIONS
		# get bet conditions for status
		$betConditionsParams = [];
		$betConditionsParams['bet_status'] = strtolower($row['status_in_db']);

        $betConditionsParams['valid_bet_amount'] =  $row['bet_amount'];
        $betConditionsParams['bet_amount_for_cashback'] =  $row['bet_amount'];
        $betConditionsParams['real_betting_amount'] = $row['bet_amount'];

		# get bet conditions for odds
        $betConditionsParams['odds_type'] = $oddsType;


        $betConditionsParams['odds_amount'] = $row['odds'];

		# get bet conditions for win/loss
		$betConditionsParams['win_loss_status'] = null;

		if(in_array($row['status_in_db'], [self::STATUS_HALF_WON, self::STATUS_HALF_LOSE])){
			$status = null;
			if($row['status_in_db'] == self::STATUS_HALF_WON){
				$betConditionsParams['win_loss_status'] = 'half_win';
			}
			if($row['status_in_db'] == self::STATUS_HALF_LOSE){
				$betConditionsParams['win_loss_status'] = 'half_lose';
			}
		}

		$row['bet_for_cashback'] = $row['bet_amount'];
		$row['real_bet_amount'] = $row['bet_amount'];
		$row['note'] 			 = null;

		list($_appliedBetRules, $_validBetAmount, $_betAmountForCashback, $_realBettingAmount, $_betconditionsDetails, $note) = $this->processBetAmountByConditions($betConditionsParams);

		if(!empty($_appliedBetRules)){
            $row['bet_amount'] = $_validBetAmount;
            $row['bet_for_cashback'] = $_betAmountForCashback;
            $row['real_bet_amount'] = $_realBettingAmount;
		}
		$row['note'] = $note;

        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        } else {
    		if($this->fixed_parlay_game_description_enabled && $row['sport_type'] == 'unknown' && isset($row['parlay_type'])){
    			$parlay_code = $row['parlay_type'] == "MixParlay" ? "99MP" : "parlay";
    			$this->CI->load->model('game_description_model');
            	$parlay_game_description = $this->CI->game_description_model->getGameDescByGameCode($parlay_code, $this->getPlatformCode());
            	if(!empty($parlay_game_description)){
            		$row['game_description_id']= $parlay_game_description['id'];
            		$row['game_type_id'] = $parlay_game_description['game_type_id'];
            		$row['game_description_name'] = $parlay_game_description['game_name'];
            		$row['game'] = $row['game_code'] = $parlay_code;
            	}
    		}
        }

        $status = $this->getGameRecordsStatus($row['status_in_db']);
        $row['status'] = $status;
    	$row['bet_type'] = $row['parlaydata'] ? Game_logs::BET_TYPE_MULTI_BET : Game_logs::BET_TYPE_SINGLE_BET;

        $bet_details = [
			'bet_details' => $this->generateBetDetails($row),
			'match_details' => $row['parlaydata'] ? 'N/A': $this->generateMatchDetails($row),
		];
		$row['bet_details']=json_encode($bet_details);
		$row['match_type']= $this->getBetType($row['bet_type_oneworks']);
    	$row['is_parlay']= !empty($row['parlaydata']) ? true : false;

    	if(!empty($row['percentage']) && $row['sport_type'] == "unknown" ){ // System parlay
    		$row['valid_bet'] = $row['valid_bet'] - ( $row['valid_bet'] * $row['percentage'] );
    		$row['percentage_note'] = $row['percentage'] * 100 . "%" . lang('Discount');
    	}
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
		$game_name = str_replace("알수없음",$row['game_code'],
					 str_replace("不明",$row['game_code'],
					 str_replace("Unknown",$row['game_code'],$unknownGame->game_name)));
		$external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

		return $this->processUnknownGame(
			$game_description_id, $game_type_id,
			$external_game_id, $game_type, $external_game_id, $extra,
			$unknownGame);
	}

	private function generateUnique()
	{
		$dt = new DateTime($this->utils->getNowForMysql());
		return $dt->format('YmdHis').random_string('numeric', 6);
	}

	public function getReasons($statusCode)
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

	public function getLauncherLanguage($language)
	{
		$language = strtolower($language);
		$lang='';
		switch ($language) {
			case 'cn':
			case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
			case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE:
				$lang = 'cs';
				break;
			case 'id':
			case Language_function::INT_LANG_INDONESIAN:
			case Language_function::PLAYER_LANG_INDONESIAN :
				$lang = 'id';
				break;
			case 'vn':
			case 'vi':
			case Language_function::INT_LANG_VIETNAMESE:
			case Language_function::PLAYER_LANG_VIETNAMESE :
				$lang = 'vn';
				break;
			case 'ko':
			case 'ko-kr':
			case Language_function::INT_LANG_KOREAN:
			case Language_function::PLAYER_LANG_KOREAN :
				$lang = 'ko';
				break;
			case 'th':
			case Language_function::INT_LANG_THAI:
			case Language_function::PLAYER_LANG_THAI :
				$lang = 'th';
				break;
			case 'pt':
			case 'pt-br':
			case 'pt-pt':
			case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
			case Language_function::PLAYER_LANG_PORTUGUESE :
				$lang = 'ptbr';
				break;
			case 'hi':
			case 'hi-in':
			case 'hi-IN':
			case Language_function::INT_LANG_INDIA:
			case Language_function::PLAYER_LANG_INDIA:
				$lang = 'hi';
				break;
			default: 
				$lang = 'en';
				break;
		}
		return $lang;
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
			"vendor_id" => $this->vendor_id,
			"vendor_trans_id" => $this->getSystemInfo('prefix_for_username').$transactionId,
			"wallet_id" => self::WALLET_TYPE[$this->wallet_type],
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

	public function getTeamName($team_id, $bet_type = null)
	{
    	$teamKey=$this->getCacheKey('-team-'.$team_id.'-'.$bet_type);

    	//try get from cache
    	$rlt=$this->CI->utils->getJsonFromCache($teamKey);
    	if(!empty($rlt)){
    		return $rlt;
    	}

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetTeamName',
        );

        $params = array(
        	"operatorid"=> $this->operator_id,
            "vendor_id" => $this->vendor_id,
            "team_id" => $team_id,
        );
        //remove by OGP-31178
        // if(!empty($bet_type)){
        // 	$params['bet_type'] = $bet_type;
        // }

        $rlt=$this->callApi(self::API_getTeamName, $params, $context);
        //save it to cache
        if($rlt['success']){
        	$this->CI->utils->saveJsonToCache($teamKey, $rlt);
        }
        return $rlt;
    }

    public function processResultForGetTeamName($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);
        $result = array();
        $data = array();
        $availableLanguages = array('cs','en','ko','vi','id');
        if($success){
            foreach ($resultJsonArr['Data']['names'] as $key => $teamNames) {
                if(in_array($teamNames['lang'], $availableLanguages)){
                    $data['teamName'][$teamNames['lang']] = $teamNames['name'];
                }
            }
            if (!array_key_exists($this->bet_detail_default_lang,$data['teamName'])){
            	//if set language not exist on the team name translatetion, will create and default will be english
            	$data['teamName'][$this->bet_detail_default_lang] = $data['teamName']['en'];
            }
        }
        $result['teamName'] = isset($data['teamName'][$this->bet_detail_default_lang])? $data['teamName'][$this->bet_detail_default_lang] : null;
        return array($success, $result);
    }

    public function getLeagueName($league_id)
    {
    	$leagueKey=$this->getCacheKey('-league-'.$league_id);

    	//try get from cache
    	$rlt=$this->CI->utils->getJsonFromCache($leagueKey);
    	if(!empty($rlt)){
    		return $rlt;
    	}

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetLeagueName',
        );

        $params = array(
        	"operatorid"=> $this->operator_id,
            "vendor_id" => $this->vendor_id,
            "league_id" => $league_id,
        );

        $rlt=$this->callApi(self::API_getLeagueName, $params, $context);
        if($rlt['success']){
        	$this->CI->utils->saveJsonToCache($leagueKey, $rlt);
        }
        return $rlt;
    }

    public function processResultForGetLeagueName($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);
        $result = array();
        $data = array();

        $availableLanguages = array('cs','en','ko','vi','id');
        if($success){
            foreach ($resultJsonArr['Data']['names'] as $key => $leagueName) {
                if(in_array($leagueName['lang'], $availableLanguages)){
                    $data['leagueName'][$leagueName['lang']] = $leagueName['name'];
                }
            }
            if (!array_key_exists($this->bet_detail_default_lang,$data['leagueName'])){
            	//if set language not exist on the team name translatetion, will create and default will be english
            	$data['leagueName'][$this->bet_detail_default_lang] = $data['leagueName']['en'];
            }
        }
        $result['leagueName'] = isset($data['leagueName'][$this->bet_detail_default_lang])? $data['leagueName'][$this->bet_detail_default_lang] : null;
        return array($success, $result);
    }

    public function getVirtualResult($sport_type,$event_ids)
    {
    	$key=$this->getCacheKey('-virtualresult-'.$event_ids.'-'.$sport_type);
    	$rlt=$this->CI->utils->getJsonFromCache($key);
    	if(!empty($rlt)){
    		return $rlt;
    	}

    	$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetVirtualResult',
        );

        $params = array(
        	"operatorid"=> $this->operator_id,
            "vendor_id" => $this->vendor_id,
            "sport_type" => $sport_type,
            "event_ids" => $event_ids
        );

        $rlt=$this->callApi(self::API_getVirtualResult, $params, $context);

        if($rlt['success']){
        	$this->CI->utils->saveJsonToCache($key, $rlt);
        }
        return $rlt;
    }

    public function processResultForGetVirtualResult($params)
    {
    	$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);
        return array($success, $resultJsonArr);
    }

    public function getBetDetailByTransId($trans_id,$sport_type = null,$callback = "processResultForGetBetDetailByTransId"){

    	$key=$this->getCacheKey('-transid-'.$trans_id.'-'.$sport_type);
    	$rlt=$this->CI->utils->getJsonFromCache($key);
    	if(!empty($rlt)){
    		return $rlt;
    	}

    	$context = array(
            'callback_obj' => $this,
            'callback_method' => $callback,
			'sport_type' => $sport_type,
			'sync_by_trans_id' => true
        );

        $params = array(
        	"operatorid"=> $this->operator_id,
            "vendor_id" => $this->vendor_id,
            "trans_id" => $trans_id,
        );

        $rlt=$this->callApi(self::API_getBetDetailByTransId, $params, $context);
        if($rlt['success']){
        	$this->CI->utils->saveJsonToCache($key, $rlt);
        }
        return $rlt;
    }

    public function processResultForGetBetDetailByTransId($params){
    	$statusCode = $this->getStatusCodeFromParams($params);
    	$sport_type = $this->getVariableFromContext($params, 'sport_type');
    	$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $result = array();
        if(!empty($resultJsonArr)){
        	if($sport_type == self::NUMBER_GAME) {
        		$result = $resultJsonArr['Data']['BetNumberDetails'][0];
        	} else if(in_array($sport_type, self::VIRTUAL_SPORTS)) {
        		$result = $resultJsonArr['Data']['BetVirtualSportDetails'][0];
        	} else {
        		$result = $resultJsonArr['Data']['BetDetails'][0];
        	}
        }
        $success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);
        return array($success, $result);
    }

    public function getSystemParlayDetail($parlay_ref_no)
    {
    	$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetSystemParlayDetail',
        );

        $params = array(
        	"operatorid"=> $this->operator_id,
            "vendor_id" => $this->vendor_id,
            "ref_no" => $parlay_ref_no,
        );

        return $this->callApi(self::API_getSystemParlayDetail, $params, $context);
    }

    public function processResultForGetSystemParlayDetail($params)
    {
    	$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);
        $result = array();
        if(!empty($resultJsonArr)){
        	$result = $resultJsonArr['Data'][0];
        }
        return array($success, $result);
    }

    public function getMemberBetSetting($playerName)
    {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetMemberBetSetting',
			'playerName' => $playerName,
		);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$params = array(
			"vendor_id" => $this->vendor_id,
			"vendor_member_id" => $gameUsername,
		);
		return $this->callApi(self::API_getMemberBetSetting, $params, $context);
	}

	public function processResultForGetMemberBetSetting($params)
	{
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);

		$result = array('response_result_id' => $responseResultId, 'result' => $resultJson);
		return array($success, $result);
	}

    private function getCacheKey($cacheId){
    	return 'game-api-'.$this->getPlatformCode().$cacheId;
    }

    public function syncOriginalGameResult($token){
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
		$dateTimeFrom->modify($this->getDatetimeAdjust());
		$this->CI->load->model(array('oneworks_game_logs'));
		$data = $this->CI->oneworks_game_logs->getOneworksGameMatchIds($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'), $this->original_gamelogs_table);
		$match_ids = implode(",",array_unique($data));
		if(!empty($match_ids)){
			return $this->getGameDetail($match_ids);
		}
		return array("success"=> true);
	}

	public function getGameDetail($matchIds)
    {
    	$context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetGameDetail',
        );

        $params = array(
        	"operatorid"=> $this->operator_id,
            "vendor_id" => $this->vendor_id,
            "match_ids" => $matchIds,
        );
        return $this->callApi(self::API_getGameDetail, $params, $context);
    }

    public function processResultForGetGameDetail($params)
    {
		$statusCode = $this->getStatusCodeFromParams($params);
    	$this->CI->load->model(array('oneworks_game_logs'));
    	$responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $statusCode);
        $dataResult = array(
			'data_count' => 0,
			'data_count_insert'=> 0,
			'data_count_update'=> 0,
		);
        if($success){
        	$results =  $resultJsonArr['Data'];
        	$dataResult['data_count'] = count($results);
        	if(!empty($results)){
        		foreach ($results as $record) {
        			$insertRecord = array();
        			//Data from Oneworks API
					$insertRecord['match_id'] 		= isset($record['match_id']) ? $record['match_id'] : NULL;
					$insertRecord['league_id'] 		= isset($record['league_id']) ? $record['league_id'] : NULL;
					$insertRecord['home_id'] 		= isset($record['home_id']) ? $record['home_id'] : NULL;
					$insertRecord['away_id'] 		= isset($record['away_id']) ? $record['away_id'] : NULL;
					$insertRecord['home_score'] 		= isset($record['home_score']) ? $record['home_score'] : NULL;
					$insertRecord['away_score'] 		= isset($record['away_score']) ? $record['away_score'] : NULL;
					$insertRecord['ht_home_score'] 		= isset($record['ht_home_score']) ? $record['ht_home_score'] : NULL;
					$insertRecord['ht_away_score'] 		= isset($record['ht_away_score']) ? $record['ht_away_score'] : NULL;
					$insertRecord['game_status'] 		= isset($record['game_status']) ? $record['game_status'] : NULL;
					$insertRecord['sport_type'] 		= isset($record['sport_type']) ? $record['sport_type'] : NULL;
					$insertRecord['is_neutral'] 		= isset($record['is_neutral']) ? $record['is_neutral'] : NULL;
					$insertRecord['VirtualSport_info'] 		= isset($record['VirtualSport_info']) ? $record['VirtualSport_info'] : NULL;
					$insertRecord['first_ball'] 		= isset($record['first_ball']) ? $record['first_ball'] : NULL;
					$insertRecord['second_ball'] 		= isset($record['second_ball']) ? $record['second_ball'] : NULL;
					$insertRecord['third_ball'] 		= isset($record['third_ball']) ? $record['third_ball'] : NULL;
					$insertRecord['match_datetime'] 		= isset($record['match_datetime']) ? $record['match_datetime'] : NULL;
					$insertRecord['total_sum'] 		= isset($record['total_sum']) ? $record['total_sum'] : NULL;
					$insertRecord['over_count'] 		= isset($record['over_count']) ? $record['over_count'] : NULL;
					$insertRecord['win_item'] 		= isset($record['win_item']) ? $record['win_item'] : NULL;
					$insertRecord['table_no'] 		= isset($record['table_no']) ? $record['table_no'] : NULL;
					$insertRecord['shoe_no'] 		= isset($record['shoe_no']) ? $record['shoe_no'] : NULL;
					$insertRecord['hand_no'] 		= isset($record['hand_no']) ? $record['hand_no'] : NULL;
					$insertRecord['game_no'] 		= isset($record['GameNo']) ? $record['GameNo'] : NULL;
					$insertRecord['settlement_time'] 		= isset($record['settlement_time']) ? $record['settlement_time'] : NULL;
					// $insertRecord['bonus_code']		= isset($record['bonus_code']) ? $record['bonus_code'] : NULL;
					// $insertRecord['wallet_type']		= isset($record['wallet_type']) ? $record['wallet_type'] : NULL;
					// $insertRecord['ref_code']		= isset($record['ref_code']) ? $record['ref_code'] : NULL;
					//virtual sports result
					$insertRecord['colours'] 		= isset($record['Colours']) ? $record['Colours'] : NULL;
					$insertRecord['event_date'] 		= isset($record['EventDate']) ? $record['EventDate'] : NULL;
					$insertRecord['event_id'] 		= isset($record['EventID']) ? $record['EventID'] : NULL;
					$insertRecord['event_status'] 		= isset($record['EventStatus']) ? $record['EventStatus'] : NULL;
					$insertRecord['human_id'] 		= isset($record['HumanID']) ? $record['HumanID'] : NULL;
					$insertRecord['is_favor'] 		= isset($record['isFavor']) ? $record['isFavor'] : NULL;
					$insertRecord['key'] 		= isset($record['Key']) ? $record['Key'] : NULL;
					$insertRecord['kick_off_time'] 		= isset($record['KickOffTime']) ? $record['KickOffTime'] : NULL;
					$insertRecord['lane'] 		= isset($record['Lane']) ? $record['Lane'] : NULL;
					$insertRecord['place_odds'] 		= isset($record['PlaceOdds']) ? $record['PlaceOdds'] : NULL;
					$insertRecord['placing'] 		= isset($record['Placing']) ? $record['Placing'] : NULL;
					$insertRecord['racer_id'] 		= isset($record['RacerID']) ? $record['RacerID'] : NULL;
					$insertRecord['racer_num'] 		= isset($record['RacerNum']) ? $record['RacerNum'] : NULL;
					$insertRecord['win_odds'] 		= isset($record['WinOdds']) ? $record['WinOdds'] : NULL;
					//extra info from SBE
					$insertRecord['response_result_id'] = $responseResultId;
					$isExists = $this->CI->oneworks_game_logs->isMatchIdAlreadyExists($insertRecord['match_id']);
					if ($isExists) {
						$insertRecord['updated_at'] = date('Y-m-d H:i:s');
						$dataResult['data_count_update'] += $this->CI->oneworks_game_logs->updateOneworksGameResult($insertRecord);
					} else {
						$insertRecord['created_at'] = date('Y-m-d H:i:s');
						$dataResult['data_count_insert'] += $this->CI->oneworks_game_logs->insertOneworksGameResult($insertRecord);
					}
        		}
        	}
        }
        $this->CI->utils->debug_log('getGameDetail result ', $dataResult);
        return array($success, $dataResult);
    }

    public function checkTicketStatus($refId) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCheckTicketStatus',
		);

		$params = array(
			"vendor_id" => $this->vendor_id,
			"refId" => $refId,
		);
        return $this->callApi(self::API_checkTicketStatus, $params, $context);
	}

	public function retryOperation($operationId) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCheckTicketStatus',
		);

		$params = array(
			"vendor_id" => $this->vendor_id,
			"operationId" => $operationId,
		);
        return $this->callApi(self::API_retryOperation, $params, $context);
	}

	public function getreachlimittrans($dateTime) {
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCheckTicketStatus',
		);

		$params = array(
			"vendor_id" => $this->vendor_id,
			"start_Time" => $dateTime,
		);

        return $this->callApi(self::API_getReachLimitTrans, $params, $context);
	}

	public function processResultForCheckTicketStatus($params)
	{
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);
		return array($success, $resultJsonArr);
	}

	#applies to seamless integration cause match details is not included on the transaction
	#basically the same purpose with getGameDetail. 
	public function getMatchDetailsByRound($round){
		$match_ids = $this->getMatchIdsByParams($round);
		$data = $this->getMatchDetails($match_ids);
		$data_parsed = $data['Data'];
		return $data_parsed;
	}

	private function getMatchDetails($match_ids){
		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForGetMatchDetails',
		);

		$params = array(
			"operatorid"=> $this->operator_id,
			"vendor_id" => $this->vendor_id,
			"match_ids" => $match_ids,
		);
		return $this->callApi(self::API_getGameDetail, $params, $context);
	}

	public function processResultForGetMatchDetails($params){
		$statusCode = $this->getStatusCodeFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJsonArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId,$resultJsonArr,$statusCode);
		return array($success, $resultJsonArr);
	}

	private function getMatchIdsByParams($params){
		#get the match id from extra_info transactions
		$this->CI->load->model(array('original_seamless_wallet_transactions'));
		$extra_info = $this->CI->original_seamless_wallet_transactions->getSpecificField($this->original_transactions_table, 'extra_info', ['external_unique_id' => $params]);
		$extra_info = json_decode($extra_info, true);
		
		if ($extra_info['message']['action'] == 'PlaceBet') {
			$match_ids = !empty($extra_info['message']['matchId']) ? $extra_info['message']['matchId'] : null;
		}
		elseif($extra_info['message']['action'] == 'PlaceBetParlay') {
			foreach ($extra_info['message']['ticketDetail'] as $key => $value) {
				$match_ids[] = $value['matchId'];
			}
			$match_ids = implode(',', $match_ids);
		}else{
			$match_ids = null;
		}

		return (string) $match_ids;
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
