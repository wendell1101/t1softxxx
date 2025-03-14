<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Getting Agent Handicap by using a player name.
 * * Creating Player
 * * Query player balances
 * * Deposit to game
 * * withdraw from game
 * * transfer credits
 *
 * The functions implemented by child class:
 * * Populating payment form parameters
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


class Game_api_ab extends Abstract_game_api {

	private $api_url = null;
	private $property_id = null;
	private $des_key = null;
	private $md5_key = null;
	private $agent = null;
	private $vipHandicap = null;
	private $orHandicaps = null;
	private $orHallRebate = 0;
	private $laxHallRebate = 0;
	private $lstHallRebate = 0;

    const API_queryBetLogQuery = 'client_betlog_query';
    const API_betlog_daily_histories = 'betlog_daily_histories';
    const API_betlog_daily_modified_histories = 'betlog_daily_modified_histories';
    const API_modify_client = 'modify_client';
    const API_setup_client_password = 'setup_client_password';
    const API_betlog_pieceof_histories_in30days = 'betlog_pieceof_histories_in30days';
    const API_maintain_state_setting = 'maintain_state_setting';
    const API_client_history_surplus = 'client_history_surplus';
    const API_query_transfer_state = 'query_transfer_state';

	const URI_MAP = array(
		self::API_queryAgentHandicap => '/query_handicap',
		self::API_queryPlayerBalance => '/get_balance',
		self::API_createPlayer => '/check_or_create',
		// self::API_transferCredit => '/agent_client_transfer',
		self::API_withdrawFromGame => '/agent_client_transfer',
		self::API_depositToGame => '/agent_client_transfer',

		self::API_queryForwardGame => '/forward_game',
		self::API_changePassword => '/setup_client_password',
		self::API_logout => '/logout_game',
		self::API_syncGameRecords => '/betlog_pieceof_histories_in30days',
        self::API_queryBetLogQuery => '/client_betlog_query',
        self::API_betlog_daily_histories => '/betlog_daily_histories',
        self::API_betlog_daily_modified_histories => '/betlog_daily_modified_histories',
        self::API_modify_client => '/modify_client',
        self::API_setup_client_password => '/setup_client_password',
        self::API_betlog_pieceof_histories_in30days =>'/betlog_pieceof_histories_in30days',
        self::API_maintain_state_setting =>'/maintain_state_setting',
        self::API_client_history_surplus =>'/client_history_surplus',
        self::API_query_transfer_state => '/query_transfer_state',
		self::API_isPlayerExist => '/get_balance'
	);

	const API_queryAgentHandicap = 'query_handicap';
	// const API_transferCredit = 'transferCredit';

	const SUCCESS_CODE = 'OK';
	const DEPOSIT = 1;
	const WITHDRAW = 0;
	const DEFAULT_SYNC_SLEEP_TIME = 60;

	const BET_TYPE = array(
		1001 => "Banker",
		1002 => "Player",
		1003 => "Tie",
		1004 => "Big",
		1005 => "Small",
		1006 => "Banker Pair",
		1007 => "Player Pair",
		1100 => "Lucky Six",
		1211 => "Banker Natural",
		1212 => "Player Natural",
		1223 => "Any Pair",
		1224 => "Perfect Pair",
		1231 => "Banker Dragon Bonus",
		1232 => "Player Dragon Bonus",
		1401 => "Tiger",
		1402 => "Small Tiger",
		1403 => "Big Tiger",
		1404 => "Tiger Pair",
		1405 => "Tiger Tie",
		1501 => "Banker Fabulous 4",
		1502 => "Player Fabulous 4",
		1503 => "Banker Precious Pair",
		1504 => "Player Precious Pair",
		1601 => "Banker Black",
		1602 => "Banker Red",
		1603 => "Player Black",
		1604 => "Player Red",
		1605 => "Any 6",
		5001 => "Supper Six",
		2001 => "Dragon",
		2002 => "Tiger",
		2003 => "Tie",
		3001 => "Small",
		3002 => "Odd",
		3003 => "Even",
		3004 => "Big",
		3005 => "Specific Triples One",
		3006 => "Specific Triples Two",
		3007 => "Specific Triples Three",
		3008 => "Specific Triples Four",
		3009 => "Specific Triples Five",
		3010 => "Specific Triples Six",
		3011 => "Any Triple",
		3012 => "Specific Double One",
		3013 => "Specific Double Two",
		3014 => "Specific Double Three",
		3015 => "Specific Double Four",
		3016 => "Specific Double Five",
		3017 => "Specific Double Six",
		3018 => "Sum of Points 4",
		3019 => "Sum of Points 5",
		3020 => "Sum of Points 6",
		3021 => "Sum of Points 7",
		3022 => "Sum of Points 8",
		3023 => "Sum of Points 9",
		3024 => "Sum of Points 10",
		3025 => "Sum of Points 11",
		3026 => "Sum of Points 12",
		3027 => "Sum of Points 13",
		3028 => "Sum of Points 14",
		3029 => "Sum of Points 15",
		3030 => "Sum of Points 16",
		3031 => "Sum of Points 17",
		3033 => "Two Dice Combination: 1,2",
		3034 => "Two Dice Combination: 1,3",
		3035 => "Two Dice Combination: 1,4",
		3036 => "Two Dice Combination: 1,5",
		3037 => "Two Dice Combination: 1,6",
		3038 => "Two Dice Combination: 2,3",
		3039 => "Two Dice Combination: 2,4",
		3040 => "Two Dice Combination: 2,5",
		3041 => "Two Dice Combination: 2,6",
		3042 => "Two Dice Combination: 3,4",
		3043 => "Two Dice Combination: 3,5",
		3044 => "Two Dice Combination: 3,6",
		3045 => "Two Dice Combination: 4,5",
		3046 => "Two Dice Combination: 4,6",
		3047 => "Two Dice Combination: 5,6",
		3048 => "One Dice 1",
		3049 => "One Dice 2",
		3050 => "One Dice 3",
		3051 => "One Dice 4",
		3052 => "One Dice 5",
		3053 => "One Dice 6",
		4001 => "Small",
		4002 => "Even",
		4003 => "Red",
		4004 => "Black",
		4005 => "Odd",
		4006 => "Big",
		4007 => "Dozen 1",
		4008 => "Dozen 2",
		4009 => "Dozen 3",
		4010 => "Column 1",
		4011 => "Column 2",
		4012 => "Column 3",
		4013 => "Direct 0",
		4014 => "Direct 1",
		4015 => "Direct 2",
		4016 => "Direct 3",
		4017 => "Direct 4",
		4018 => "Direct 5",
		4019 => "Direct 6",
		4020 => "Direct 7",
		4021 => "Direct 8",
		4022 => "Direct 9",
		4023 => "Direct 10",
		4024 => "Direct 11",
		4025 => "Direct 12",
		4026 => "Direct 13",
		4027 => "Direct 14",
		4028 => "Direct 15",
		4029 => "Direct 16",
		4030 => "Direct 17",
		4031 => "Direct 18",
		4032 => "Direct 19",
		4033 => "Direct 20",
		4034 => "Direct 21",
		4035 => "Direct 22",
		4036 => "Direct 23",
		4037 => "Direct 24",
		4038 => "Direct 25",
		4039 => "Direct 26",
		4040 => "Direct 27",
		4041 => "Direct 28",
		4042 => "Direct 29",
		4043 => "Direct 30",
		4044 => "Direct 31",
		4045 => "Direct 32",
		4046 => "Direct 33",
		4047 => "Direct 34",
		4048 => "Direct 35",
		4049 => "Direct 36",
		4050 => "Three Numbers (0/1/2)",
		4051 => "Three Numbers (0/2/3)",
		4052 => "Four Numbers (0/1/2/3)",
		4053 => "Separate: (0/1)",
		4054 => "Separate: (0/2)",
		4055 => "Separate: (0/1)",
		4056 => "Separate: (1/2)",
		4057 => "Separate: (2/3)",
		4058 => "Separate: (4/5)",
		4059 => "Separate: (5/6)",
		4060 => "Separate: (7/8)",
		4061 => "Separate: (8/9)",
		4062 => "Separate: (10/11)",
		4063 => "Separate: (11/12)",
		4064 => "Separate: (13/14)",
		4065 => "Separate: (14/15)",
		4066 => "Separate: (16/17)",
		4067 => "Separate: (17/18)",
		4068 => "Separate: (19/20)",
		4069 => "Separate: (20/21)",
		4070 => "Separate: (22/23)",
		4071 => "Separate: (23/24)",
		4072 => "Separate: (25/26)",
		4073 => "Separate: (26/27)",
		4074 => "Separate: (28/29)",
		4075 => "Separate: (29/30)",
		4076 => "Separate: (31/32)",
		4077 => "Separate: (32/33)",
		4078 => "Separate: (34/35)",
		4079 => "Separate: (35/36)",
		4080 => "Separate: (1/4)",
		4081 => "Separate: (4/7)",
		4082 => "Separate: (7/10)",
		4083 => "Separate: (10/13)",
		4084 => "Separate: (13/16)",
		4085 => "Separate: (16/19)",
		4086 => "Separate: (19/22)",
		4087 => "Separate: (22/25)",
		4088 => "Separate: (25/28)",
		4089 => "Separate: (28/31)",
		4090 => "Separate: (31/34)",
		4091 => "Separate: (2/5)",
		4092 => "Separate: (5/8)",
		4093 => "Separate: (8/11)",
		4094 => "Separate: (11/14)",
		4095 => "Separate: (14/17)",
		4096 => "Separate: (17/20)",
		4097 => "Separate: (20/23)",
		4098 => "Separate: (23/26)",
		4099 => "Separate: (26/29)",
		4100 => "Separate: (29/32)",
		4101 => "Separate: (32/35)",
		4102 => "Separate: (3/6)",
		4103 => "Separate: (6/9)",
		4104 => "Separate: (9/12)",
		4105 => "Separate: (12/15)",
		4106 => "Separate: (15/18)",
		4107 => "Separate: (18/21)",
		4108 => "Separate: (21/24)",
		4109 => "Separate: (24/27)",
		4110 => "Separate: (27/30)",
		4111 => "Separate: (30/33)",
		4112 => "Separate: (33/36)",
		4113 => "Triangle : (1/5)",
		4114 => "Triangle : (2/6)",
		4115 => "Triangle : (4/8)",
		4116 => "Triangle : (5/9)",
		4117 => "Triangle : (7/11)",
		4118 => "Triangle : (8/12)",
		4119 => "Triangle : (10/14)",
		4120 => "Triangle : (11/15)",
		4121 => "Triangle : (13/17)",
		4122 => "Triangle : (14/18)",
		4123 => "Triangle : (16/20)",
		4124 => "Triangle : (17/21)",
		4125 => "Triangle : (19/23)",
		4126 => "Triangle : (20/24)",
		4127 => "Triangle : (22/26)",
		4128 => "Triangle : (23/27)",
		4129 => "Triangle : (25/29)",
		4130 => "Triangle : (26/30)",
		4131 => "Triangle : (28/32)",
		4132 => "Triangle : (29/33)",
		4133 => "Triangle : (31/35)",
		4134 => "Triangle : (32/36)",
		4135 => "Street : (1~3)",
		4136 => "Street : (4~6)",
		4137 => "Street : (7~9)",
		4138 => "Street : (10~12)",
		4139 => "Street : (13~15)",
		4140 => "Street : (16~18)",
		4141 => "Street : (19~21)",
		4142 => "Street : (22~24)",
		4143 => "Street : (25~27)",
		4144 => "Street : (28~20)",
		4145 => "Street : (31~33)",
		4146 => "Street : (34~36)",
		4147 => "Line : (1~6)",
		4148 => "Line : (4~9)",
		4149 => "Line : (7~12)",
		4150 => "Line : (10~15)",
		4151 => "Line : (13~18)",
		4152 => "Line : (16~21)",
		4153 => "Line : (19~24)",
		4154 => "Line : (22~27)",
		4155 => "Line : (28~33)",
		4156 => "Line : (31~36)",
		4157 => "Line : (25~30)",
		6001 => "Lucky symbol(1) 80x",
		6002 => "Lucky symbol(2) 18x",
		6003 => "Lucky symbol(3) 3x",
		6004 => "Lucky symbol(4) 1x",
		6005 => "Lucky symbol(5) 3x",
		6006 => "Lucky symbol(6) 18x",
		6007 => "Lucky symbol(7) 80x",
		7000 => "1 - play",
		7001 => "1 - insurance",
		7002 => "1 - double down",
		7003 => "1 - split",
		7004 => "1 - blackjack 21+3",
		7005 => "1 - perfect pairs",
		7010 => "2 - play",
		7011 => "2 - insurance",
		7012 => "2 - double down",
		7013 => "2 - split",
		7014 => "2 - blackjack 21+3",
		7015 => "2 - perfect pairs",
		7020 => "3 - play",
		7021 => "3 - insurance",
		7022 => "3 - double down",
		7023 => "3 - split",
		7024 => "3 - blackjack 21+3",
		7025 => "3 - perfect pairs",
		7030 => "4 - play",
		7031 => "4 - insurance",
		7032 => "4 - double down",
		7033 => "4 - split",
		7034 => "4 - blackjack 21+3",
		7035 => "4 - perfect pairs",
		7040 => "5 - play",
		7041 => "5 - insurance",
		7042 => "5 - double down",
		7043 => "5 - split",
		7044 => "5 - blackjack 21+3",
		7045 => "5 - perfect pairs",
		7050 => "6 - play",
		7051 => "6 - insurance",
		7052 => "seat 6 - double down",
		7053 => "seat 6 - split",
		7054 => "seat 6 - blackjack 21+3",
		7055 => "seat 6 - perfect pairs",
		7060 => "seat 7 - play",
		7061 => "seat 7 - insurance",
		7062 => "seat 7 - double down",
		7063 => "seat 7 - split",
		7064 => "seat 7 - blackjack 21+3",
		7065 => "seat 7 - perfect pairs",
		7100 => "bet behind seat 1 - play",
		7101 => "bet behind seat 1 - insurance",
		7102 => "bet behind seat 1 - double down",
		7103 => "bet behind seat 1 - split",
		7110 => "bet behind seat 2 - play",
		7111 => "bet behind seat 2 - insurance",
		7112 => "bet behind seat 2 - double down",
		7113 => "bet behind seat 2 - split",
		7120 => "bet behind seat 3 - play",
		7121 => "bet behind seat 3 - insurance",
		7122 => "bet behind seat 3 - double down",
		7123 => "bet behind seat 3 - split",
		7130 => "bet behind seat 4 - play",
		7131 => "bet behind seat 4 - insurance",
		7132 => "bet behind seat 4 - double down",
		7133 => "bet behind seat 4 - split",
		7140 => "bet behind seat 5 - play",
		7141 => "bet behind seat 5 - insurance",
		7142 => "bet behind seat 5 - double down",
		7143 => "bet behind seat 5 - split",
		7150 => "bet behind seat 6 - play",
		7151 => "bet behind seat 6 - insurance",
		7152 => "bet behind seat 6 - double down",
		7153 => "bet behind seat 6 - split",
		7160 => "bet behind seat 7 - play",
		7161 => "bet behind seat 7 - insurance",
		7162 => "bet behind seat 7 - double down",
		7163 => "bet behind seat 7 - split",
		8001 => "Banker 1 equal",
		8011 => "Banker 1 double",
		8101 => "Player 1 equal",
		8111 => "Player 1 double",
		8002 => "Banker 2 equal",
		8012 => "Banker 2 double",
		8102 => "Player 2 equal",
		8112 => "Player 2 double",
		8003 => "Banker 3 equal",
		8013 => "Banker 3 double",
		8103 => "Player 3 equal",
		8113 => "Player 3 double",
		8021 => "Banker 1 Super Bull",
		8121 => "Player 1 Super Bull",
		8022 => "Banker 2 Super Bull",
		8122 => "Player 2 Super Bull",
		8023 => "Banker 3 Super Bull",
		8123 => "Player 2 Super Bul"
	);

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->property_id = $this->getSystemInfo('property_id');
		$this->des_key = $this->getSystemInfo('key');
		$this->md5_key = $this->getSystemInfo('secret');
		$this->agent = $this->getSystemInfo('account');
		$this->orHallRebate = $this->getSystemInfo('orHallRebate');
		$this->laxHallRebate = $this->getSystemInfo('laxHallRebate');
		$this->lstHallRebate = $this->getSystemInfo('lstHallRebate');
		$this->syncSleepTime = $this->getSystemInfo('syncSleepTime') ? : self::DEFAULT_SYNC_SLEEP_TIME;
		$this->password_append_string = $this->getSystemInfo('password_append_string', '1');
		$this->language = $this->getSystemInfo('language');
		$this->is_redirect = $this->getSystemInfo('is_redirect', false);


	}

	// protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
	// 	// $statusCode = intval($statusCode, 10);
	// 	return $errCode || (intval($statusCode, 10) >= 400 && intval($statusCode, 10) != 500);
	// 	// return false;
	// }

	public function queryAgentHandicap($playerName) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForqueryAgentHandicap',
			'playerName' => $playerName,
		);

		$params = array(
			'random' => mt_rand(),
			'agent' => $this->agent,
		);

		return $this->callApi(self::API_queryAgentHandicap, $params, $context);

	}

	public function processResultForqueryAgentHandicap($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, array('handicaps' => $resultJson['handicaps']));
	}

	# START CREATE PLAYER #################################################################################################################################

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

		$this->CI->load->helper('string');

		// $handicaps = $this->queryAgentHandicap($playerName);

		// if (!$handicaps['success']) {
		// 	return array('success' => false);
		// }

		$vipHandicap = null;
		$orHandicaps = array();

		$handicaps = $this->getSystemInfo('handicaps');
		if(!empty($handicaps)){
			foreach ($handicaps as $handicap) {
				if ($handicap['handicapType'] == 1 && $vipHandicap == null) {
					$vipHandicap = $handicap['id'];
				} else if ($handicap['handicapType'] == 0 && count($orHandicaps) < 3) {
					$orHandicaps[] = $handicap['id'];
				}
			}
		}

		// $password = random_string('alnum', 12);
		$aResult = $this->processPassword($password);
		$password = $aResult['password'];

		parent::createPlayer($playerName, $playerId, $password, $email, $extra);

		// For some instances since the parent::createPlayer may trigger in some way not only here on game_api
		if ($aResult['changed']) {
			parent::changePasswordInDB($playerName, $password);
		}

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId'=>$playerId,
		);

		$params = array(
			'random' => mt_rand(),
			'agent' => $this->agent,
			'client' => $playerName,
			'password' => $password,
			'vipHandicaps' => $vipHandicap,
			'orHandicaps' => implode(',', $orHandicaps),
			'orHallRebate' => $this->orHallRebate,
			'laxHallRebate' => $this->laxHallRebate,
			'lstHallRebate' => $this->lstHallRebate,
		);

		return $this->callApi(self::API_createPlayer, $params, $context);

	}

	public function processResultForCreatePlayer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName) || $resultJson['error_code'] == 'CLIENT_EXIST';

		$result=['response_result_id'=>$responseResultId];

		$result['exists']=null;

		//update register
		if ($success) {
			$this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
			$result['exists']=true;
		}

		return array($success, $result);

	}

	# END CREATE PLAYER #################################################################################################################################

	public function isPlayerExist($playerName) {

		$password = $this->getPasswordString($playerName);
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForIsPlayerExist',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername,
		);

		$params = array(
			"random" => mt_rand(),
			"client" => $gameUsername,
			"password" => $password
		);



		return $this->callApi(self::API_isPlayerExist, $params, $context);

		//return $this->createPlayer($playerName, $playerId, $password);

		// $result = $this->queryPlayerBalance($playerName);
		// $result["exists"] = isset($result["balance"]); // $result['success'];
		// $result['success'] = $result['success'];
		// return $result;
	}

	public function processResultForIsPlayerExist($params){

		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultArr = $this->getResultJsonFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
		$result = array();

		if($success){
			$result['exists'] = true;
		}else{
			# Player not Exists
			if(isset($resultArr['error_code'])&&$resultArr['error_code']=='CLIENT_NOT_EXIST'){
				$success = true; // meaning request success
				$result['exists'] = false;
			}else{
				$result['exists'] = null; // INTERNAL_ERROR, ILLEGAL_ARGUMENT
			}

		}

		return array($success, $result);
    }

	# START QUERY PLAYER BALANCE #################################################################################################################################

	public function queryPlayerBalance($playerName) {

		$password = $this->getPasswordString($playerName);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
		);

		$params = array(
			'random' => mt_rand(),
			// 'agent' => $this->agent,
			'client' => $playerName,
			'password' => $password,
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);

	}

	public function processResultForQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$result=[];
		if($success){
			if(isset($resultJson['balance'])){
				$result['balance'] =floatval($resultJson['balance']);
				//reset to 0 if <1
				if($result['balance']<1){
					$result['balance']=0;
				}
			}
		}

		return [$success, $result];
		// return array($success, array(
		// 	'balance' => $success && isset($resultJson['balance']) ? @floatval($resultJson['balance']) : 0,
		// ));

	}

	# END QUERY PLAYER BALANCE #################################################################################################################################

	public function preDepositToGame($player_id, $playerName, $transfer_from, $transfer_to, $amount, $extra_details = []) {

        if($amount < 1){
            return array(
                    'success' => false,
                    'message' => lang('not_allow_decimal')
                );
            }

        return $this->returnUnimplemented();
    }

	# START DEPOSIT TO GAME #################################################################################################################################

	public function depositToGame($playerName, $amount,$transfer_secure_id=null) {

		$result = $this->transferCredit($playerName, $amount, self::DEPOSIT, $transfer_secure_id);
		return $result;

	}

	# END DEPOSIT TO GAME #################################################################################################################################

	# START WITHDRAW FROM GAME #################################################################################################################################

	public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {

		$result = $this->transferCredit($playerName, $amount, self::WITHDRAW, $transfer_secure_id);
		return $result;

	}

	# END WITHDRAW FROM GAME #################################################################################################################################

	# START PREPARE TRANSFER CREDIT #################################################################################################################################

	public function transferCredit($playerName, $amount, $operFlag, $transfer_secure_id=null) {

		$this->CI->load->helper('string');

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$sn = $this->property_id . random_string('numeric', 13);

		$transfer_type='';
		switch ($operFlag) {
		case self::DEPOSIT:
			$api = self::API_depositToGame;
			$transType = 'transTypeMainWalletToSubWallet';
			$transfer_type=self::API_depositToGame;
			break;

		case self::WITHDRAW:
			$api = self::API_withdrawFromGame;
			$transType = 'transTypeSubWalletToMainWallet';
			$transfer_type=self::API_withdrawFromGame;
			break;

		default:
			$transType = '';
			break;
		}

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForTransferCredit',
			'playerName' => $playerName,
			'amount' => $amount,
			'transType' => $transType,
			'transfer_type'=>$transfer_type,
			'external_transaction_id' => $sn,
		);

		if($operFlag==self::DEPOSIT){
            $context['enabled_guess_success_for_curl_errno_on_this_api']=$this->enabled_guess_success_for_curl_errno_on_this_api;
            // $context['is_timeout_mock']=$this->getSystemInfo('is_timeout_mock', false);
		}

		$params = array(
			'random' => mt_rand(),
			'agent' => $this->agent,
			'sn' => $sn,
			'client' => $playerName,
			'operFlag' => $operFlag,
			'credit' => $amount,
			'beforeCredit' => null,
			'afterCredit' => null,
		);

		return $this->callApi($api, $params, $context);

	}

	public function processResultForTransferCredit($params) {
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$amount = $this->getVariableFromContext($params, 'amount');
		$transType = $this->getVariableFromContext($params, 'transType');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$result = array(
				'response_result_id' => $responseResultId,
				'external_transaction_id'=>$external_transaction_id,
				'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
				'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {
			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;
			// $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
			// if ($playerId) {

			// 	$playerBalance = $this->queryPlayerBalance($playerName);
			// 	$afterBalance = $playerBalance['balance'];

			// 	$this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId, $this->{$transType}());

			// } else {
			// 	$this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
			// }

		} else {
			$error_code = @$resultJson['error_code'];
			switch($error_code) {
				case 'LACK_OF_MONEY' :
					$result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
					break;
				case 'ILLEGAL_ARGUMENT' :
					$result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
					break;
				case 'SYSTEM_MATAINING' :
					$result['reason_id']=self::REASON_API_MAINTAINING;
					break;
				case 'AGENT_NOT_EXIST' :
					$result['reason_id']=self::REASON_AGENT_NOT_EXISTED;
					break;
				case 'DUPLICATE_CONFIRM' :
					$result['reason_id']=self::REASON_DUPLICATE_TRANSFER;
					break;
				case 'FORBIDDEN' :
					$result['reason_id']=self::REASON_IP_NOT_AUTHORIZED;
					break;
				case 'INVALID_SIGN' :
					$result['reason_id']=self::REASON_INVALID_KEY;
					break;
				case 'CLIENT_NOT_EXIST' :
					$result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
					break;
			}
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);

	}

	# END PREPARE TRANSFER CREDIT #################################################################################################################################

	# START SYNC ORIGINAL GAME LOGS #################################################################################################################################

	public function syncOriginalGameLogs($token) {

		$syncId = parent::getValueFromSyncInfo($token, 'syncId');
		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $dateTimeFrom = new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
        $dateTimeTo = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));
        $dateTimeFrom->modify($this->getDatetimeAdjustSyncOriginal());

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'syncId' => $syncId,
		);

		$startTime = clone $dateTimeFrom;
		do {

			$endTime = clone $startTime;
			$endTime->modify('+1 hour');
			if($endTime->format('Y-m-d H:i:s')>$dateTimeTo->format('Y-m-d H:i:s')){
				$endTime=clone $dateTimeTo;
			}
			// $endTime = min($endTime, $dateTimeTo);

			$params = array(
				'random' 	=> mt_rand(),
				'startTime' => $startTime->format('Y-m-d H:i:s'),
				'endTime' 	=> $endTime->format('Y-m-d H:i:s'),
			);

			$this->CI->utils->debug_log('syncOriginalGameLogs', 'sync_date_range', $dateTimeFrom->format('Y-m-d H:i:s') . ' to ' . $dateTimeTo->format('Y-m-d H:i:s'), 'api_call_date_range', $params['startTime'] . ' to ' . $params['endTime']);

			$result = $this->callApi(self::API_syncGameRecords, $params, $context);

			if (isset($result['success']) && $result['success']) {
				$startTime = $endTime;
			} else if (isset($result['error_code']) && $result['error_code'] == 'TOO_FREQUENT_REQUEST') {
				$this->CI->utils->debug_log(sprintf('TOO_FREQUENT_REQUEST. sleeping for %s seconds', $this->syncSleepTime));
				sleep($this->syncSleepTime);
				//go back
				$endTime=$startTime;
			} else {
				return $result;
			}

		} while ($endTime->format('Y-m-d H:i:s') < $dateTimeTo->format('Y-m-d H:i:s'));

		$this->CI->utils->debug_log('quit syncOriginalGameLogs');

		return array('success' => TRUE);
	}

	public function processResultForSyncGameRecords($params) {

		$this->CI->load->model('ab_game_logs');

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$count = 0;

		// [histories] => Array
		//  (
		//      [0] => Array
		//          (
		//              [betAmount] => 20
		//              [betNum] => 1971985498814619
		//              [betTime] => 2016-04-01 21:23:06
		//              [betType] => 1001
		//              [client] => ogtest9867
		//              [commission] => 100
		//              [currency] => CNY
		//              [exchangeRate] => 1
		//              [gameResult] => {303,108,-1},{407,302,-1}
		//              [gameRoundEndTime] => 2016-04-01 21:23:18
		//              [gameRoundId] => 197198549
		//              [gameRoundStartTime] => 2016-04-01 21:22:28
		//              [gameType] => 101
		//              [state] => 0
		//              [tableName] => B003
		//              [validAmount] => 19
		//              [winOrLoss] => 19
		//          )
		//      [1] => Array
		//          (
		//              [betAmount] => 20
		//              [betNum] => 1971986147612193
		//              [betTime] => 2016-04-01 21:23:45
		//              [betType] => 1002
		//              [client] => ogtest9867
		//              [commission] => 100
		//              [currency] => CNY
		//              [exchangeRate] => 1
		//              [gameResult] => {413,112,-1},{108,213,-1}
		//              [gameRoundEndTime] => 2016-04-01 21:24:24
		//              [gameRoundId] => 197198614
		//              [gameRoundStartTime] => 2016-04-01 21:23:34
		//              [gameType] => 101
		//              [state] => 0
		//              [tableName] => B003
		//              [validAmount] => 20
		//              [winOrLoss] => -20
		//          )
		//  )

		$gameRecords = isset($resultJson['histories']) ? $resultJson['histories'] : null;
		$success = $this->processResultBoolean($responseResultId, $resultJson, NULL);

		if ($success && !empty($gameRecords)) {
			$availableRows = $this->CI->ab_game_logs->getAvailableRows($gameRecords);
			$this->CI->utils->debug_log('availableRows', count($availableRows), 'gameRecords', count($gameRecords));

			foreach ($availableRows as $record) {
				$data['client'] = isset($record['client']) ? $record['client'] : NULL;
              	$data['betNum'] = isset($record['betNum']) ? $record['betNum'] : NULL;
              	$data['gameRoundId'] = isset($record['gameRoundId']) ? $record['gameRoundId'] : NULL;
              	$data['gameType'] = isset($record['gameType']) ? $record['gameType'] : NULL;
              	$data['betAmount'] = isset($record['betAmount']) ? $record['betAmount'] : NULL;
              	$data['validAmount'] = isset($record['validAmount']) ? $record['validAmount'] : NULL;
              	$data['winOrLoss'] = isset($record['winOrLoss']) ? $record['winOrLoss'] : NULL;
              	$data['state'] = isset($record['state']) ? $record['state'] : NULL;
              	$data['currency'] = isset($record['currency']) ? $record['currency'] : NULL;
              	$data['exchangeRate'] = isset($record['exchangeRate']) ? $record['exchangeRate'] : NULL;
              	$data['betType'] = isset($record['betType']) ? $record['betType'] : NULL;
              	$data['gameResult'] = isset($record['gameResult']) ? $record['gameResult'] : NULL;
              	$data['tableName'] = isset($record['tableName']) ? $record['tableName'] : NULL;
              	$data['commission'] = isset($record['commission']) ? $record['commission'] : NULL;

				$data['betTime'] = $this->gameTimeToServerTime($record['betTime']);
                $data['gameRoundEndTime'] = $this->gameTimeToServerTime($record['gameRoundEndTime']);
                $data['gameRoundStartTime'] = $this->gameTimeToServerTime($record['gameRoundStartTime']);
                $this->CI->ab_game_logs->insertAbGameLogs($data);
                $count++;
			}
		}

		return array($success, $resultJson, $count);
	}



	# END SYNC ORIGINAL GAME LOGS #################################################################################################################################

	# START SYNC MERGE TO GAME LOGS #################################################################################################################################

	public function syncMergeToGameLogs($token) {
		$this->CI->load->model(array('game_logs', 'player_model', 'ab_game_logs','game_description_model'));

		$dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
		$dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $dateTimeFrom->modify($this->getDatetimeAdjustSyncMerge());

		$this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

		$rlt = array('success' => true);
		// $result = $this->CI->ab_game_logs->getAbGameLogStatistics($dateTimeFrom->format('Y-m-d 00:00:00'), $dateTimeTo->format('Y-m-d 23:59:59'));
		$result = $this->CI->ab_game_logs->getGameLogStatistics($dateTimeFrom->format('Y-m-d 00:00:00'), $dateTimeTo->format('Y-m-d 23:59:59'));

		$cnt = 0;
		if ($result) {
			//$unknownGame = $this->getUnknownGame();
			$gameDescIdMap = $this->CI->game_description_model->getGameCodeMap($this->getPlatformCode());

			foreach ($result as $ab_data) {
				if (!$player_id = $this->getPlayerIdInGameProviderAuth($ab_data->gameUsername)) {
					continue;
				}
				$cnt++;

				$player = $this->CI->player_model->getPlayerById($player_id);

				$bet_amount = $this->gameAmountToDB($ab_data->validAmount);
				$result_amount = $this->gameAmountToDB($ab_data->winOrLoss);
				$real_bet = $this->gameAmountToDB($ab_data->betAmount);

				// list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($ab_data, $gameDescIdMap);
				list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($ab_data, $this->getUnknownGame());
				// $game_description_id = $ab_data->game_description_id;
				// $game_type_id = $ab_data->game_type_id;

				// if (empty($game_description_id)) {
				// 	$game_description_id = $unknownGame->id;
				// 	$game_type_id = $unknownGame->game_type_id;
				// }

				// $extra = array('trans_amount' => $real_bet, 'table' => $ab_data->gameRoundId,);
				$extraData = json_decode($ab_data->extra, true);
				$betType = $extraData['isMultiBet'] ? 'Combo Bet':'Single Bet';
				unset($extraData['isMultiBet']);
				$extra = array(
					'trans_amount' => $real_bet,
					'table' => $ab_data->gameRoundId,
					'bet_details'  => json_encode($extraData),
                    'bet_type'     => $betType,
				);

				$this->syncGameLogs(
					$game_type_id,
					$game_description_id,
					$ab_data->game_code,
					$game_type_id,
					$ab_data->game,
					$player_id,
					$player->username,
					$bet_amount,
					$result_amount,
					null, # win_amount
					null, # loss_amount
					null, # after_balance
					0, # has_both_side
					$ab_data->betNum,
					$ab_data->gameRoundStartTime,
					$ab_data->gameRoundEndTime,
					null, # response_result_id
					Game_logs::FLAG_GAME,
                    $extra
				);
			}
		}

		$this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);

		return $rlt;
	}


	private function getGameDescriptionInfo($row, $unknownGame) {

		// $game_description_id = null;
		// if (isset($row->game_description_id)) {
		// 	$game_description_id = $row->game_description_id;
		// }

		// $game_type_id = null;
		// if (isset($row->game_type_id)) {
		// 	$game_type_id = $row->game_type_id;
		// }

		// $externalGameId = $row->game_code;
		// if (empty($game_description_id)) {
		// 	//search game_description_id by code
		// 	if (isset($gameDescIdMap[$externalGameId]) && !empty($gameDescIdMap[$externalGameId])) {
		// 		$game_description_id = $gameDescIdMap[$externalGameId]['game_description_id'];
		// 		$game_type_id = $gameDescIdMap[$externalGameId]['game_type_id'];
		// 		if ($gameDescIdMap[$externalGameId]['void_bet'] == 1) {
		// 			return array(null, null);
		// 		}
		// 	}
		// }

		// $extra = array('game_code' => strtolower($row->game_name_str));
		// // if( ! $game_type_id){
		// 	$game_type_str = 'Live Games';
		// // }
		// return $this->processUnknownGame($game_description_id, $game_type_id, 'ab.'.strtolower($row->game_name_str), $game_type_str, $externalGameId, $extra);
		if (isset($row->game_description_id)) {
			$game_description_id = $row->game_description_id;
			$game_type_id = $row->game_type_id;
			return [$game_description_id, $game_type_id];
		}

		if(empty($row->game_description_id)){
			$game_description_id = null;
			$external_game_id = $row->game_code;
	        $extra = array('game_code' => $external_game_id,'game_name' => "unknownGame");
	        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
	        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

			return $this->processUnknownGame(
				$game_description_id, $game_type_id,
				$external_game_id, $game_type, $external_game_id, $extra,
				$unknownGame);
		}
	}


	# END SYNC MERGE TO GAME LOGS #################################################################################################################################

	# START CHANGE PASSWORD #################################################################################################################################

	public function changePassword($playerName, $oldPassword, $newPassword) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$aResult = $this->processPassword($newPassword);
		$newPassword = $aResult['password'];

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForChangePassword',
			'playerName' => $playerName,
			'newPassword' => $newPassword,
		);

		$params = array(
			'random' => mt_rand(),
			'client' => $playerName,
			'newPassword' => $newPassword,
		);

		return $this->callApi(self::API_changePassword, $params, $context);

	}

	public function processResultForChangePassword($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$newPassword = $this->getVariableFromContext($params, 'newPassword');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		if ($success) {

			if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
				$this->updatePasswordForPlayer($playerId, $newPassword);
			} else {
				$this->CI->utils->debug_log('cannot find player', $playerName);
			}

		}

		return array($success, $resultJson);

	}

	# END CHANGE PASSWORD #################################################################################################################################

	public function logout($playerName, $password = null) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
		);

		$params = array(
			'random' => mt_rand(),
			'client' => $playerName,
		);

		return $this->callApi(self::API_logout, $params, $context);

	}

	public function processResultForLogout($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		return array($success, $resultJson);

	}

    public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'zh_CN';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
                $lang = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $lang = 'vi';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
                $lang = 'ko';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                $lang = 'th';
                break;
            default:
                $lang = 'en';
                break;
        }
        return $lang;
    }

	# START QUERY FORWARD GAME #################################################################################################################################

	public function queryForwardGame($playerName, $extra = null) {
		$nextUrl = $this->generateGotoUri($playerName, $extra);
        $result = $this->forwardToWhiteDomain($playerName, $nextUrl);

        if($result['success']){
            return $result;
        }

		$password = $this->getPasswordString($playerName);
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName,
		);

		$params = array_filter(array(
			'random' => mt_rand(),
			'client' => $playerName,
			'password' => $password,
			'targetSite' => isset($extra['targetSite']) ? $extra['targetSite'] : null,
            'language' => isset($this->language) ? $this->language : $this->getLauncherLanguage($extra['language'])
		));

		$protocol = $this->CI->utils->isHttps() ? 'https' : 'http';

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$url='';
        $is_redirect = $this->is_redirect;
		if(empty($resultJson['gameLoginUrl'])){
			$success=false;
		}else{
			$url=$resultJson['gameLoginUrl'];
		}
		return array($success, array('url' => $url, 'is_redirect' => $is_redirect));

	}

	# END QUERY FORWARD GAME #################################################################################################################################

	# IMPROVISED / UTILS ##############################################################################################################################################################################
	public function getPlatformCode() {

		return AB_API;
	}

	public function generateUrl($apiName, $params) {

		$apiUri = self::URI_MAP[$apiName];

		$real_param = http_build_query($params);
		$data = $this->encryptText($real_param, $this->des_key);

		$to_sign = $data . $this->md5_key;
		$sign = base64_encode(md5($to_sign, TRUE));

		$url = $this->api_url . $apiUri . '?' . http_build_query(array(
			'data' => $data,
			'sign' => $sign,
			'propertyId' => $this->property_id,
		));

		// var_dump(array('url' => $url, 'params' => $params));

		// $ch = curl_init($url);
		// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// $output 	= curl_exec($ch);
		// $http_code 	= curl_getinfo($ch, CURLINFO_HTTP_CODE);
		// echo "<br/>http_code: " . $http_code;
		// echo "<br/>result: " . $output;
		// curl_close($ch);

		return $url;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		// afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText, $extra, $resultObj);
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultJson, $playerName) {

		$success = (!empty($resultJson)) && $resultJson['error_code'] == self::SUCCESS_CODE;

		if (!$success) {
			$this->setResponseResultToError($responseResultId);
			$this->CI->utils->error_log('AB got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
		}

		return $success;
	}

	public function blockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->blockUsernameInDB($playerName);
		return array("success" => $result);
	}

	public function unblockPlayer($playerName) {
		$playerName = $this->getGameUsernameByPlayerUsername($playerName);
		$result = $this->unblockUsernameInDB($playerName);
		return array("success" => $result);
	}

	public function gameAmountToDB($amount) {
		$amount = floatval($amount);
		return round($amount, 2);
	}

	# NOT IMPLEMENTED ##############################################################################################################################################################################
	public function updatePlayerInfo($playerName, $infos) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
		return $this->returnUnimplemented();
	}

	public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
		return $this->returnUnimplemented();
	}

	public function checkLoginToken($playerName, $token) {
		return $this->returnUnimplemented();
	}

	public function totalBettingAmount($playerName, $dateFrom, $dateTo) {
		return $this->returnUnimplemented();
	}

	public function queryTransaction($transactionId, $extra) {
		$playerName=$extra['playerName'];
		$playerId=$extra['playerId'];
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryTransaction',
			'external_transaction_id' => $transactionId,
			'gameUsername'=>$gameUsername,
			'playerId'=>$playerId
		);

		$params = array(
			'random' => mt_rand(),
			'sn' => $transactionId,
		);

		return $this->callApi(self::API_query_transfer_state, $params, $context);
	}

	public function processResultForQueryTransaction( $params ){
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

		$result = array(
			'response_result_id' => $responseResultId,
			'external_transaction_id'=>$external_transaction_id,
			'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
			'reason_id'=>self::REASON_UNKNOWN
		);
		if ($success) {
			$result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
		} else {
			$error_code = @$resultJson['error_code'];
			switch($error_code) {
				case 'ILLEGAL_ARGUMENT' :   // invalid trans, invalid param
					$result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
					break;
				case 'SYSTEM_MATAINING' :
					$result['reason_id']=self::REASON_API_MAINTAINING;
					break;
				case 'AGENT_NOT_EXIST' :
					$result['reason_id']=self::REASON_AGENT_NOT_EXISTED;
					break;
				case 'FORBIDDEN' :
					$result['reason_id']=self::REASON_IP_NOT_AUTHORIZED;
					break;
				case 'INVALID_SIGN' :
					$result['reason_id']=self::REASON_INVALID_KEY;
					break;
				case 'CLIENT_NOT_EXIST' :
					$result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
					break;
			}
			$result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
		}

		return array($success, $result);
	}

	public function login($playerName, $password = null) {
		return $this->returnUnimplemented();
	}

	public function checkLoginStatus($playerName) {
		return $this->returnUnimplemented();
	}

	public function queryPlayerInfo($playerName) {
		return $this->returnUnimplemented();
	}

	public function processResult($url, $apiName, $params, $resultText, $statusCode, $statusText = null, $extra = null, $errCode = null, $error = null, $resultObj = null, $context = null, $costMs=null) {
		return parent::processResult($url, $apiName, $params, $resultText, 200, $statusText, $extra, $errCode, $error, $resultObj, $context, $costMs);
	}

    // Additional Function Tested by Marck 07/30/2017
    // Credit to Jerbey

    public function queryBetLogQuery($token, $username) {
        $syncId = parent::getValueFromSyncInfo($token, 'syncId');
        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $playerName = $this->getGameUsernameByPlayerUsername($username);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForqueryBetLogQuery',
            'playerName' => $playerName,
        );

        $params = array(
            'random'    => mt_rand(),
            'client'    => $playerName,
            'startTime' => "2017-06-25 01:00:00",
            'endTime'   => "2017-06-26 02:00:00",
            'pageIndex' => 1,
            'pageSize'  => 100
        );

        return $this->callApi(self::API_queryBetLogQuery, $params, $context);
    }

    public function processResultForqueryBetLogQuery($params) {
        $resultJson = $this->getResultJsonFromParams($params);
    }

    public function betlog_daily_histories($token) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForBetlogDailyHistories',
        );

        $params = array(
            'random'    => mt_rand(),
            'startDate' => "2017-06-09",
            'endDate'   => "2017-06-10",
        );

        // return $this->callApi(self::API_betlog_daily_modified_histories, $params, $context);
        return $this->callApi(self::API_betlog_daily_histories, $params, $context);
    }

    public function processResultForBetlogDailyHistories($params) {
        $resultJson = $this->getResultJsonFromParams($params);
    }

    public function betlog_daily_modified_histories($token) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForBetlogDailyHistories',
        );

        $params = array(
            'random'    => mt_rand(),
            'startDate' => "2017-06-08",
            'endDate'   => "2017-06-09",
        );

        return $this->callApi(self::API_betlog_daily_modified_histories, $params, $context);
    }

    public function processResultForBetlogDailyModifiedHistories($params) {
        $resultJson = $this->getResultJsonFromParams($params);
    }

    public function modify_client($playerName,$vipHandicap,$orHandicaps) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForModifyClient',
            'playerName' => $playerName,
        );

        $params = array(
            'random' => mt_rand(),
            'client' => $playerName,
            'vipHandicaps' => $vipHandicap,
            'orHandicaps' => $orHandicaps
        );

        return $this->callApi(self::API_modify_client, $params, $context);
    }

    public function processResultForModifyClient($params) {
        $resultJson = $this->getResultJsonFromParams($params);
    }

    public function setup_client_password($username) {
        $password = $this->getPasswordString($username);
        $playerName = $this->getGameUsernameByPlayerUsername($username);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSetupClientPassword',
            'playerName' => $playerName,
        );

        $params = array(
            'random' => mt_rand(),
            'client' =>  $playerName,
            'newPassword' => $password,
        );

        return $this->callApi(self::API_setup_client_password, $params, $context);
    }

    public function processResultForSetupClientPassword($params) {
        $resultJson = $this->getResultJsonFromParams($params);
    }

    public function betlog_pieceof_histories_in30days($token) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForbetlog_pieceof_histories_in30dayss',
        );

        $params = array(
            'random'    => mt_rand(),
            'startTime' => "2017-06-04 22:00:00",
            'endTime'   => "2017-06-04 23:00:00"
        );

        return $this->callApi(self::API_betlog_pieceof_histories_in30days, $params, $context);
    }

    public function processResultForbetlog_pieceof_histories_in30dayss($params) {
        $resultJson = $this->getResultJsonFromParams($params);
    }

    public function maintain_state_setting($state= null) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForMaintainStateSetting',
        );

        $params = array(
            'random' => mt_rand(),
            // 'state' => "4",
        );

        return $this->callApi(self::API_maintain_state_setting, $params, $context);
    }

    public function processResultForMaintainStateSetting($params) {
        $resultJson = $this->getResultJsonFromParams($params);
    }

    public function client_history_surplus($username,$operation_type) {
        $playerName = $this->getGameUsernameByPlayerUsername($username);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForClientHistorySurplus',
        );

        $params = array(
            'random' => mt_rand(),
            'client' => $playerName,
            'operationType' =>$operation_type
        );

        return $this->callApi(self::API_client_history_surplus, $params, $context);
    }

    public function processResultForClientHistorySurplus($params) {
        $resultJson = $this->getResultJsonFromParams($params);
    }

    public function query_transfer_state($sn) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransferState',
        );

        $params = array(
            'random' => mt_rand(),
            'sn' => $sn,
        );

        return $this->callApi(self::API_query_transfer_state, $params, $context);
    }

    public function processResultForQueryTransferState($params) {
        $resultJson = $this->getResultJsonFromParams($params);
    }

	public function onlyTransferPositiveInteger(){
		return true;
	}

	public function processPassword($password) {
		$passwordCount = strlen($password);
		$minPass = $this->getMinSizePassword();
		$maxPass = $this->getMaxSizePassword();
		$bChanged = false;

		if ($passwordCount > $maxPass) {
			$password = substr($password, 0, 12);
			$bChanged = true;
		} else if ($passwordCount < $minPass) {
			$countDiff = $minPass - $passwordCount;
			$password = str_repeat($this->password_append_string, $countDiff) . $password;
			$bChanged = true;
		}

		$aResult = ['changed' => $bChanged, 'password' => $password];
		$this->CI->utils->debug_log('AB PROCESS_PASSWORD LOG ====>', $aResult);

		return $aResult;
	}

	public function pkcs5Pad($text, $blocksize) {
	    $pad = $blocksize - (strlen($text) % $blocksize);
	    return $text . str_repeat(chr($pad), $pad);
	}

	public function encryptText($string, $key)
    {
        $key= base64_decode($key);
		$string = $this->pkcs5Pad($string, 8);
        $data =  openssl_encrypt($string, 'DES-EDE3-CBC', $key,OPENSSL_RAW_DATA | OPENSSL_NO_PADDING,base64_decode("AAAAAAAAAAA="));
        $data =base64_encode($data);
        return $data;
    }

    // End of testing
}