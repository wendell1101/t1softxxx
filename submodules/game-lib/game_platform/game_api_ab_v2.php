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


class Game_api_ab_v2 extends Abstract_game_api {

	private $api_url = null;
	private $property_id = null;
	private $api_key = null;
	private $agent = null;
	private $suffix = null;
	private $syncSleepTime = null;

	private $original_gamelogs_table = 'ab_game_logs';

    public $prefix_for_username;
    public $fix_username_limit;
    public $minimum_user_length;
    public $maximum_user_length;
    public $default_fix_name_length;

	const BET_STATUS_BETTING = 100;
	const BET_STATUS_FAILED = 101;
	const BET_STATUS_NOT_SETTLED = 110;
	const BET_STATUS_SETTLED = 111;
	const BET_STATUS_REFUND = 120;

	const URI_MAP = array(
		self::API_queryAgentHandicap => '/query_handicap',
		self::API_queryPlayerBalance => '/GetBalances',
		self::API_createPlayer => '/CheckOrCreate',
		self::API_withdrawFromGame => '/Transfer',
		self::API_depositToGame => '/Transfer',
		self::API_queryTransaction => '/GetTransferState',
		self::API_syncGameRecords => '/PagingQueryBetRecords',
		self::API_queryForwardGame => '/Login',
		self::API_logout => '/Logout',
		self::API_isPlayerExist => '/GetPlayerSetting'
	);

	const API_queryAgentHandicap = 'query_handicap';
	// const API_transferCredit = 'transferCredit';

	const SUCCESS_CODE = 'OK';
	const DEPOSIT = 1;
	const WITHDRAW = 0;
	const DEFAULT_SYNC_SLEEP_TIME = 60;


	const MD5_FIELDS_FOR_GAME_LOGS=[
        'external_uniqueid', 'client', 'betNum', 'gameType', 'betAmount',
        'validAmount', 'winOrLoss', 'state', 'currency', 'exchangeRate','betType','gameResult','gameResult2',
		'tableName','commission','betTime','gameRoundEndTime','gameRoundStartTime','uniqueid','betMode','ip'];


	# Values of these fields will be rounded when calculating MD5
	const MD5_FLOAT_AMOUNT_FIELDS_FOR_GAME_LOGS = [
		'betAmount',
		'validAmount',
		'winOrLoss'
	];

	const MD5_FIELDS_FOR_MERGE = [
        'real_bet_amount',
        'bet_amount',
        'result_amount',
        'start_datetime',
        'end_datetime',
        'round_id',
        'game_id',
        'external_uniqueid'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'real_bet_amount',
        'bet_amount',
        'result_amount'
    ];


	// GAME TYPES
	const GAME_TYPE_NORMAL_BACCARAT = 101;
	const GAME_TYPE_VIP_BACCARAT = 102;
	const GAME_TYPE_QUICK_BACCARAT = 103;
	const GAME_TYPE_SEE_CARD_BACCARAT = 104;
	const GAME_TYPE_INSURANCE_BACCARAT = 110;
	const GAME_TYPE_SICBO_HILO = 201;
	const GAME_TYPE_DRAGON_TIGER = 301;
	const GAME_TYPE_ROULETTE = 401;
	const GAME_TYPE_POK_DENG = 501;
	const GAME_TYPE_BULL_BULL =	801;
	const GAME_TYPE_WIN_THREE_CARDS = 901;

	const GAME_TYPE_NAME = array(
		self::GAME_TYPE_NORMAL_BACCARAT => "Normal Baccarat",
		self::GAME_TYPE_VIP_BACCARAT => "VIP Baccarat",
		self::GAME_TYPE_QUICK_BACCARAT => "Quick Baccarat",
		self::GAME_TYPE_SEE_CARD_BACCARAT => "See Card Baccarat",
		self::GAME_TYPE_INSURANCE_BACCARAT => "Insurance Baccarat",
		self::GAME_TYPE_SICBO_HILO => "Sicbo(HiLo)",
		self::GAME_TYPE_DRAGON_TIGER => "Dragon Tiger",
		self::GAME_TYPE_ROULETTE => "Roulette",
		self::GAME_TYPE_POK_DENG => "Pok Deng",
		self::GAME_TYPE_BULL_BULL => "Bull Bull",
		self::GAME_TYPE_WIN_THREE_CARDS => "Win Three Cards / Three Pictures"
	);



	const BET_TYPE = array(
		// -- START: Baccarat (including ordinary Baccarat, VIP Baccarat, Quick Baccarat, See Card Baccarat)
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
		1301 => "Banker Insurance(First)",
		1302 => "Banker Insurance(Second)",
		1303 => "Player Insurance(First)",
		1304 => "Player Insurance(Second)",
		// -- END: Baccarat (including ordinary Baccarat, VIP Baccarat, Quick Baccarat, See Card Baccarat)

		// START: Fabulous 4
		1501 => "Banker Fabulous 4",
		1502 => "Player Fabulous 4",
		1503 => "Banker Precious Pair",
		1504 => "Player Precious Pair",
		// END: Fabulous 4

		// START: Tiger
		1401 => "Tiger",
		1402 => "Small Tiger",
		1403 => "Big Tiger",
		1404 => "Tiger Pair",
		1405 => "Tiger Tie",
		// END: Tiger

		// -- START: Super Baccarat
		1601 => "Banker Black",
		1602 => "Banker Red",
		1603 => "Player Black",
		1604 => "Player Red",
		1605 => "Any 6",
		// -- END: Super Baccarat

		// 5001 => "Supper Six",
		// -- START: Dragon Tiger
		2001 => "Dragon",
		2002 => "Tiger",
		2003 => "Tie",
		// END : Dragon Tiger

		// START: Sicbo(HiLo)
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
		3018 => "Tie Value: 4",
		3019 => "Tie Value: 5",
		3020 => "Tie Value: 6",
		3021 => "Tie Value: 7",
		3022 => "Tie Value: 8",
		3023 => "Tie Value: 9",
		3024 => "Tie Value: 10",
		3025 => "Tie Value: 11",
		3026 => "Tie Value: 12",
		3027 => "Tie Value: 13",
		3028 => "Tie Value: 14",
		3029 => "Tie Value: 15",
		3030 => "Tie Value: 16",
		3031 => "Tie Value: 17",
		3033 => "Pai Gow Type: 1,2",
		3034 => "Pai Gow Type: 1,3",
		3035 => "Pai Gow Type: 1,4",
		3036 => "Pai Gow Type: 1,5",
		3037 => "Pai Gow Type: 1,6",
		3038 => "Pai Gow Type: 2,3",
		3039 => "Pai Gow Type: 2,4",
		3040 => "Pai Gow Type: 2,5",
		3041 => "Pai Gow Type: 2,6",
		3042 => "Pai Gow Type: 3,4",
		3043 => "Pai Gow Type: 3,5",
		3044 => "Pai Gow Type: 3,6",
		3045 => "Pai Gow Type: 4,5",
		3046 => "Pai Gow Type: 4,6",
		3047 => "Pai Gow Type: 5,6",
		3048 => "Single: 1",
		3049 => "Single: 2",
		3050 => "Single: 3",
		3051 => "Single: 4",
		3052 => "Single: 5",
		3053 => "Single: 6",
		3200 => "Hi",
		3201 => "Lo",
		3202 => "11 Hi-Lo",
		3203 => "Dice 1",
		3204 => "Dice 2",
		3205 => "Dice 3",
		3206 => "Dice 4",
		3207 => "Dice 5",
		3208 => "Dice 6",
		3209 => "1-2",
		3210 => "1-3",
		3211 => "1-4",
		3212 => "1-5",
		3213 => "1-6",
		3214 => "2-3",
		3215 => "2-4",
		3216 => "2-5",
		3217 => "2-6",
		3218 => "3-4",
		3219 => "3-5",
		3220 => "3-6",
		3221 => "4-5",
		3222 => "4-6",
		3223 => "5-6",
		3224 => "1-Lo",
		3225 => "2-Lo",
		3226 => "3-Lo",
		3227 => "4-Lo",
		3228 => "5-Lo",
		3229 => "6-Lo",
		3230 => "3-Hi",
		3231 => "4-Hi",
		3232 => "5-Hi",
		3233 => "6-Hi",
		3234 => "1,2,3",
		3235 => "2,3,4",
		3236 => "3,4,5",
		3237 => "4,5,6",
		// END - Sicbo (HiLo)

		// START: ROULETTE
		4001 => "Small",
		4002 => "Even",
		4003 => "Red",
		4004 => "Black",
		4005 => "Odd",
		4006 => "Big",
		4007 => "First Dozen",
		4008 => "Second Dozen",
		4009 => "Third Dozen",
		4010 => "First Column",
		4011 => "Second Column",
		4012 => "Third Column",
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
		4050 => "Trio Bet (0/1/2)",
		4051 => "Trio Bet (0/2/3)",
		4052 => "Corner Bets (0/1/2/3)",
		4053 => "Split: (0/1)",
		4054 => "Split: (0/2)",
		4055 => "Split: (0/1)",
		4056 => "Split: (1/2)",
		4057 => "Split: (2/3)",
		4058 => "Split: (4/5)",
		4059 => "Split: (5/6)",
		4060 => "Split: (7/8)",
		4061 => "Split: (8/9)",
		4062 => "Split: (10/11)",
		4063 => "Split: (11/12)",
		4064 => "Split: (13/14)",
		4065 => "Split: (14/15)",
		4066 => "Split: (16/17)",
		4067 => "Split: (17/18)",
		4068 => "Split: (19/20)",
		4069 => "Split: (20/21)",
		4070 => "Split: (22/23)",
		4071 => "Split: (23/24)",
		4072 => "Split: (25/26)",
		4073 => "Split: (26/27)",
		4074 => "Split: (28/29)",
		4075 => "Split: (29/30)",
		4076 => "Split: (31/32)",
		4077 => "Split: (32/33)",
		4078 => "Split: (34/35)",
		4079 => "Split: (35/36)",
		4080 => "Split: (1/4)",
		4081 => "Split: (4/7)",
		4082 => "Split: (7/10)",
		4083 => "Split: (10/13)",
		4084 => "Split: (13/16)",
		4085 => "Split: (16/19)",
		4086 => "Split: (19/22)",
		4087 => "Split: (22/25)",
		4088 => "Split: (25/28)",
		4089 => "Split: (28/31)",
		4090 => "Split: (31/34)",
		4091 => "Split: (2/5)",
		4092 => "Split: (5/8)",
		4093 => "Split: (8/11)",
		4094 => "Split: (11/14)",
		4095 => "Split: (14/17)",
		4096 => "Split: (17/20)",
		4097 => "Split: (20/23)",
		4098 => "Split: (23/26)",
		4099 => "Split: (26/29)",
		4100 => "Split: (29/32)",
		4101 => "Split: (32/35)",
		4102 => "Split: (3/6)",
		4103 => "Split: (6/9)",
		4104 => "Split: (9/12)",
		4105 => "Split: (12/15)",
		4106 => "Split: (15/18)",
		4107 => "Split: (18/21)",
		4108 => "Split: (21/24)",
		4109 => "Split: (24/27)",
		4110 => "Split: (27/30)",
		4111 => "Split: (30/33)",
		4112 => "Split: (33/36)",
		4113 => "Corner Bets : (1/5)",
		4114 => "Corner Bets : (2/6)",
		4115 => "Corner Bets : (4/8)",
		4116 => "Corner Bets : (5/9)",
		4117 => "Corner Bets : (7/11)",
		4118 => "Corner Bets : (8/12)",
		4119 => "Corner Bets : (10/14)",
		4120 => "Corner Bets : (11/15)",
		4121 => "Corner Bets : (13/17)",
		4122 => "Corner Bets : (14/18)",
		4123 => "Corner Bets : (16/20)",
		4124 => "Corner Bets : (17/21)",
		4125 => "Corner Bets : (19/23)",
		4126 => "Corner Bets : (20/24)",
		4127 => "Corner Bets : (22/26)",
		4128 => "Corner Bets : (23/27)",
		4129 => "Corner Bets : (25/29)",
		4130 => "Corner Bets : (26/30)",
		4131 => "Corner Bets : (28/32)",
		4132 => "Corner Bets : (29/33)",
		4133 => "Corner Bets : (31/35)",
		4134 => "Corner Bets : (32/36)",
		4135 => "Street Bets : (1~3)",
		4136 => "Street Bets : (4~6)",
		4137 => "Street Bets : (7~9)",
		4138 => "Street Bets : (10~12)",
		4139 => "Street Bets : (13~15)",
		4140 => "Street Bets : (16~18)",
		4141 => "Street Bets : (19~21)",
		4142 => "Street Bets : (22~24)",
		4143 => "Street Bets : (25~27)",
		4144 => "Street Bets : (28~20)",
		4145 => "Street Bets : (31~33)",
		4146 => "Street Bets : (34~36)",
		4147 => "Line Bets : (1~6)",
		4148 => "Line Bets : (4~9)",
		4149 => "Line Bets : (7~12)",
		4150 => "Line Bets : (10~15)",
		4151 => "Line Bets : (13~18)",
		4152 => "Line Bets : (16~21)",
		4153 => "Line Bets : (19~24)",
		4154 => "Line Bets : (22~27)",
		4155 => "Line Bets : (28~33)",
		4156 => "Line Bets : (31~36)",
		4157 => "Line Bets : (25~30)",
		// END - Roulette

		// START - POKDENG
		5001 => "Player 1",
		5002 => "Player 2",
		5003 => "Player 3",
		5004 => "Player 4",
		5005 => "Player 5",
		5011 => "Player 1 Pair",
		5012 => "Player 2 Pair",
		5013 => "Player 3 Pair",
		5014 => "Player 4 Pair",
		5015 => "Player 5 Pair",
		5101 => "Player 1 (Two Sides Pok Deng)",
		5102 => "Player 2 (Two Sides Pok Deng)",
		5103 => "Player 3 (Two Sides Pok Deng)",
		5104 => "Player 4 (Two Sides Pok Deng)",
		5105 => "Player 5 (Two Sides Pok Deng)",
		5106 => "Banker 1",
		5107 => "Banker 2",
		5108 => "Banker 3",
		5109 => "Banker 4",
		5110 => "Banker 5",
		5111 => "Banker Pair",
		// END: POKDENG

		6001 => "Gold Rock",
		6002 => "Gold Paper",
		6003 => "Gold Scissors",
		6004 => "Silver Rock",
		6005 => "Silver Paper",
		6006 => "Silver Scissors",
		6007 => "Bronze Rock",
		6008 => "Bronze Paper",
		6009 => "Bronze Scissors",

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

		// START: Bull Bull
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
		8123 => "Player 2 Super Bull",

		// End BullBull

		// START: Win Three Cards
		9001 => "Dragon",
		9002 => "Phoenix",
		9003 => "Pair 8 Plus",
		9004 => "Straight",
		9005 => "Flush",
		9006 => "Straight Flush",
		9007 => "Three of a Kind",
        9101 => "(Three Pictures) Dragon",
        9102 => "(Three Pictures) Phoenix",
        9103 => "Tie",
        9114 => "Dragon Three Pictures",
        9124 => "Phoenix Three Pictures",
	);

	public function __construct() {
		parent::__construct();
		$this->api_url = $this->getSystemInfo('url');
		$this->property_id = $this->getSystemInfo('property_id');
		$this->api_key = $this->getSystemInfo('key');
		$this->agent = $this->getSystemInfo('agent');
		$this->suffix = $this->getSystemInfo('suffix_for_username'); // this is part of credential for version 2 and this is required
		$this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+1 hour');
		$this->syncSleepTime = $this->getSystemInfo('syncSleepTime', '10'); // should be change to self::DEFAULT_SYNC_SLEEP_TIME

		$this->password_append_string = $this->getSystemInfo('password_append_string', '1');
		$this->language = $this->getSystemInfo('language');
		$this->is_redirect = $this->getSystemInfo('is_redirect', false);

        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 7);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', !empty($this->suffix) ? 28 - strlen($this->suffix) : 28);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 7);
	}

	protected function customHttpCall($ch, $params) {

		$path = $params["uri"];


		unset($params["uri"]);

		$requestBodyString = json_encode($params);
		$contentMD5 =  base64_encode(pack('H*', md5($requestBodyString)));
		$contentType = "application/json";

		$date   = new DateTime();
		$requestTime = $date->format('d M Y H:m:s T');




		$stringToSign = "POST" . "\n"
			. $contentMD5 . "\n"
			. $contentType . "\n"
			. $requestTime . "\n"
			. $path;

		$authorization = $this->generateAuthorizationString($stringToSign);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","Accept:application/json","Content-MD5:$contentMD5","Authorization:$authorization","Date:$requestTime"));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBodyString);

    }

	private function generateAuthorizationString($stringToSign) {

		$deKey = base64_decode($this->api_key);

		$hash_hmac = hash_hmac("sha1", $stringToSign, $deKey, true);
		$encrypted = base64_encode($hash_hmac);

		$authorization = "AB" . " " . $this->property_id . ":" . $encrypted;

		return $authorization;

	}

	# START CREATE PLAYER #################################################################################################################################

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

		$this->CI->load->helper('string');

		// $password = random_string('alnum', 12);
		$aResult = $this->processPassword($password);
		$password = $aResult['password'];

        $extra = [
            'prefix' => $this->prefix_for_username,

            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
            'check_username_only' => true
        ];

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

		$api_url = self::API_createPlayer;
		$params = array(
			'agent' => $this->agent,
			'player' => $playerName . $this->suffix,
			'uri' => self::URI_MAP[$api_url]
		);

		return $this->callApi(self::API_createPlayer, $params, $context);

	}

	public function processResultForCreatePlayer($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$playerName = $this->getVariableFromContext($params, 'playerName');
		$playerId = $this->getVariableFromContext($params, 'playerId');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $playerName) || $resultJson['resultCode'] == 'PLAYER_EXIST';

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

		$playerId=$this->getPlayerIdFromUsername($playerName);

		$game_username = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' 		=> $this,
			'callback_method' 	=> 'processResultForIsPlayerExist',
			'gameUsername' 	=> $game_username,
			'playerName' 		=> $playerName,
			'playerId' => $playerId,
		);


		$api_url = self::API_isPlayerExist;
		$params = array(
			'player' => $game_username . $this->suffix, // suffix is part of credential
			'uri' => self::URI_MAP[$api_url]
		);

		return $this->callApi(self::API_isPlayerExist, $params, $context);
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
			if(isset($resultArr['resultCode'])&&$resultArr['resultCode']=='PLAYER_NOT_EXIST'){
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

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryPlayerBalance',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$api_url = self::API_queryPlayerBalance;

		$params = array(
			"agent" => $this->agent,
			"pageSize" => 1000,
			"pageIndex" => 1,
			"recursion" => 0,
			"players" => [$gameUsername . $this->suffix],
			"uri" => self::URI_MAP[$api_url]
		);

		return $this->callApi(self::API_queryPlayerBalance, $params, $context);

	}

	public function processResultForQueryPlayerBalance($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $gameUsername);

		$result=[];

		if($success){

			if(isset($resultJson['data']['list'])) {

				if(count($resultJson['data']['list']) > 0) {

					$data = $resultJson['data']['list'][0];

					$result['balance'] =floatval($data['amount']);

					if($result['balance']<1){
						$result['balance']=0;
					}

				}

			}
		}

		return [$success, $result];

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

	public function transferCredit($playerName, $amount, $type, $transfer_secure_id=null) {

		$this->CI->load->helper('string');

		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
		$sn = $this->property_id . random_string('numeric', 13);

		$transfer_type='';
		switch ($type) {
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
			'gameUsername' => $gameUsername,
			'amount' => $amount,
			'transType' => $transType,
			'transfer_type'=>$transfer_type,
			'external_transaction_id' => $sn,
		);

		if($type==self::DEPOSIT){
            $context['enabled_guess_success_for_curl_errno_on_this_api']=$this->enabled_guess_success_for_curl_errno_on_this_api;
            // $context['is_timeout_mock']=$this->getSystemInfo('is_timeout_mock', false);
		}

		$params = array(
			'agent' => $this->agent,
			'sn' => $sn,
			'player' => $gameUsername . $this->suffix,
			'type' => $type,
			'amount' => $amount,
			'uri' => self::URI_MAP[$api]
		);

		return $this->callApi($api, $params, $context);

	}

	public function processResultForTransferCredit($params) {
		$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$amount = $this->getVariableFromContext($params, 'amount');
		$transType = $this->getVariableFromContext($params, 'transType');
		$statusCode = $this->getStatusCodeFromParams($params);
		$success = $this->processResultBoolean($responseResultId, $resultJson, $gameUsername);

		$result = array(
				'response_result_id' => $responseResultId,
				'external_transaction_id'=>$external_transaction_id,
				'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
				'reason_id'=>self::REASON_UNKNOWN
		);

		if ($success) {

			$result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;


		} else {
			$error_code = @$resultJson['resultCode'];
			switch($error_code) {
				case 'LACK_OF_MONEY' :
					$result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
					break;
				case 'ILLEGAL_ARGUMENT' :
					$result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
					break;
				case 'SYSTEM_MAINTENANCE' :
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
				case 'PLAYER_NOT_EXIST' :
					$result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
				case 'INTERNAL_ERROR' :
					$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
					break;
			}
			$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
			
			if((in_array($statusCode, $this->other_status_code_treat_as_success) || in_array($error_code, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
				$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
				$result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
				$success=true;
			}

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
		$dateTimeFrom->modify($this->getDatetimeAdjustSyncOriginal());
        $dateTimeTo = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));

		$start_date = $dateTimeFrom->format('Y-m-d H:i:s');
		$end_date   = $dateTimeTo->format('Y-m-d H:i:s');



		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForSyncGameRecords',
			'syncId' => $syncId,
			'startDate' => $start_date,
			'endDate' => $end_date
		);



		$self = $this;

		$result = array();

		$api_url = self::API_syncGameRecords;

		$this->CI->utils->loopDateTimeStartEnd($start_date, $end_date, $this->sync_time_interval, function($start_date, $end_date) use ($context, $self, $api_url, $result)
		{




			$dates = array();
			$dates = $this->CI->utils->dateRange($this->CI->utils->formatDateForMysql($start_date), $this->CI->utils->formatDateForMysql($end_date));

			$count_date = 1;
			foreach($dates as $date) {


				$dateYmd = new DateTime(date('Y-m-d',strtotime($date)));
				$dateNow = new DateTime(date('Y-m-d'));
				$date_diff = date_diff($dateNow,$dateYmd);

				# you cannot search the modified data 90 days ago.
				if(($date_diff->days > 89)){
					continue;
				}

				if(count($dates)  == 1 )
				{
					$startTime = $start_date->format('H:i:s');
					$endTime = $end_date->format('H:i:s');
				} else {

					if($count_date > 1){
						$startTime = '00:00:00';
						$endTime = '01:00:00';
					} else {
						$startTime = $start_date->format('H:i:s');
						$endTime = "24:00:00"; // OGP-24395 as per game provider the last second of the day will be 24:00:00
					}

				}


				$params = array(
					"agent" => $this->agent,
					"date" => $dateYmd->format('Y-m-d'),
					"startTime" => $startTime,
					"endTime" =>  $endTime,
					"pageNum" => 1,
					"pageSize" => 1000,
					'uri' => self::URI_MAP[$api_url]
				);

				$self->CI->utils->debug_log('syncOriginalGameLogs', 'sync_date_range', $start_date->format('Y-m-d H:i:s') . ' to ' . $end_date->format('Y-m-d H:i:s'), 'api_call_date_range', $params['startTime'] . ' to ' . $params['endTime']);

				$result[] = $self->callApi(self::API_syncGameRecords, $params, $context);

				$count_date++;

				sleep($self->syncSleepTime);
			}

			return true;
		});

		return array("success" => true, "results"=>$result);

	}

	public function processResultForSyncGameRecords($params) {

		$this->CI->load->model('ab_game_logs');

		$startDate = $this->getVariableFromContext($params, 'startDate');
		$endDate = $this->getVariableFromContext($params, 'endDate');
        $resultJson = $this->getResultJsonFromParams($params);
		$responseResultId = $this->getResponseResultIdFromParams($params);

		$count = 0;

		$gameRecords = isset($resultJson['data']['list']) ? $resultJson['data']['list']	 : null;

		$success = $this->processResultBoolean($responseResultId, $resultJson, NULL);

		$dataCount = 0;

		if ($success && !empty($gameRecords)) {

			$result['startDate'] = $startDate;
			$result['endDate'] = $endDate;

			$extra = array("response_result_id" => $responseResultId);


			$gameRecords = $this->rebuildGameRecords($gameRecords,$extra);

			if ($gameRecords) {
				$this->CI->load->model('original_game_logs_model');
				list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
					$this->original_gamelogs_table,
					$gameRecords,
					'external_uniqueid',
					'external_uniqueid',
					self::MD5_FIELDS_FOR_GAME_LOGS,
					'md5_sum',
					'id',
					self::MD5_FLOAT_AMOUNT_FIELDS_FOR_GAME_LOGS
				);
				$this->CI->utils->debug_log('AllBet V2 after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));
	            // $insertRows = json_encode($insertRows);
	            unset($gameRecords);
				if (!empty($insertRows)) {
					$dataCount += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
				}
				unset($insertRows);
				if (!empty($updateRows)) {
					$dataCount += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
				}
				unset($updateRows);



			}

		}

		$result["dataCount"] = $dataCount;

		return array($success, $resultJson, $count);
	}


	private function rebuildGameRecords($gRecords,$extra)
    {
        $newGR =[];

		if(!empty($gRecords)){
			//
			foreach ($gRecords as $i => $value) {

				$newGR[$i]['client'] = isset($value['player']) ? str_replace($this->suffix, "", $value['player']) : NULL;
              	$newGR[$i]['betNum'] = isset($value['betNum']) ? $value['betNum'] : NULL;
              	$newGR[$i]['gameRoundId'] = isset($value['gameRoundId']) ? $value['gameRoundId'] : NULL;
              	$newGR[$i]['gameType'] = isset($value['gameType']) ? $value['gameType'] : NULL;
              	$newGR[$i]['betAmount'] = isset($value['betAmount']) ? $value['betAmount'] : NULL;
              	$newGR[$i]['validAmount'] = isset($value['validAmount']) ? $value['validAmount'] : NULL;
              	$newGR[$i]['winOrLoss'] = isset($value['winOrLossAmount']) ? $value['winOrLossAmount'] : NULL;
              	$newGR[$i]['state'] = isset($value['status']) ? $value['status'] : NULL;
              	$newGR[$i]['currency'] = isset($value['currency']) ? $value['currency'] : NULL;
              	$newGR[$i]['exchangeRate'] = isset($value['exchangeRate']) ? $value['exchangeRate'] : NULL;
              	$newGR[$i]['betType'] = isset($value['betType']) ? $value['betType'] : NULL;
              	$newGR[$i]['gameResult'] = isset($value['gameResult']) ? $value['gameResult'] : NULL;
				$newGR[$i]['gameResult2'] = isset($value['gameResult2']) ? $value['gameResult2'] : NULL;
              	$newGR[$i]['tableName'] = isset($value['tableName']) ? $value['tableName'] : NULL;
              	$newGR[$i]['commission'] = isset($value['commission']) ? $value['commission'] : NULL;
				$newGR[$i]['appType'] = isset($value['appType']) ? $value['appType'] : NULL;
				$newGR[$i]['betMode'] = isset($value['betMethod']) ? $value['betMethod'] : NULL;
				$newGR[$i]['ip'] = isset($value['ip']) ? $value['ip'] : NULL;

				$newGR[$i]['betTime'] = $this->gameTimeToServerTime($value['betTime']);
                $newGR[$i]['gameRoundEndTime'] = $this->gameTimeToServerTime($value['gameRoundEndTime']);
                $newGR[$i]['gameRoundStartTime'] = $this->gameTimeToServerTime($value['gameRoundStartTime']);

				//

				# SBE USE
				$newGR[$i]['uniqueid'] = isset($value['betNum']) ? $value['betNum'] : NULL;
				$newGR[$i]['external_uniqueid'] = isset($value['betNum']) ? $value['betNum'] : NULL;
				$newGR[$i]['response_result_id'] = $extra['response_result_id'];
				$newGR[$i]['createdAt'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
				$newGR[$i]['updatedAt'] = $this->utils->getNowDateTime()->format('Y-m-d H:i:s');
				$newGR[$i]['external_game_id'] = isset($value['gameRoundId']) ? $value['gameRoundId'] : NULL;

			}


		}


		return $newGR;
    }


	private function updateOrInsertOriginalGameLogs($data, $queryType, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($data)){

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

        return $dataCount;
    }

	# END SYNC ORIGINAL GAME LOGS #################################################################################################################################

	# START SYNC MERGE TO GAME LOGS #################################################################################################################################

	public function syncMergeToGameLogs($token) {

		$enabled_game_logs_unsettle=true;
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
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {


    	$sqlTime = "ab.updatedAt >= ? AND ab.updatedAt <= ?";
    	if($use_bet_time){
    		$sqlTime = "ab.gameRoundStartTime >= ? AND ab.gameRoundStartTime <= ?";
    	}
		$sql = <<<EOD
			SELECT
				ab.id as sync_index,
				ab.validAmount as bet_amount,
				ab.winOrLoss as result_amount,
				ab.betAmount as real_bet_amount,
				ab.gameType as game_type,
				ab.gameType as game_id,
				ab.gameRoundId as round_id,
				ab.gameRoundStartTime as start_datetime,
				ab.gameRoundEndTime  as end_datetime,
				ab.extra,
				ab.external_uniqueid,
				ab.response_result_id,
				ab.state as status,
				ab.ip as ip_address,
				ab.gameResult,
				ab.gameResult2,
				ab.betType as bet_type,
				ab.updatedAt as updated_at,
				ab.md5_sum,
				game_provider_auth.player_id,
				game_provider_auth.login_name as player_username,
				gd.id as game_description_id,
				gd.game_name as game,
				gd.game_type_id,
				gd.void_bet as void_bet
			FROM
				ab_game_logs as ab
			LEFT JOIN
				game_description as gd ON ab.gameType = gd.external_game_id and gd.void_bet!=1 and gd.game_platform_id = ?
			JOIN
				game_provider_auth
			ON
				ab.client = game_provider_auth.login_name AND
				game_provider_auth.game_provider_id = ?
			WHERE
				{$sqlTime};
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

	/**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {

		if(!array_key_exists('md5_sum', $row)){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_id'],
                'game_type'             => $row['game_type_id'],
                'game'                  => $row['game']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet_amount'],
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $row['bet_amount'], #to question
                'real_betting_amount'   => $row['real_bet_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => null,
            ],
            'date_info' => [
                'start_at'              => $row['start_datetime'],
                'end_at'                => $row['end_datetime'] ?:$row['start_datetime'],
                'bet_at'                => $row['start_datetime'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $this->getGameRecordsStatus($row),
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => $row['bet_type']
            ],
            'bet_details' => $row['bet_details'],
            'extra' => [

				'trans_amount' 			=> $row["real_bet_amount"],
				'table' 	 			=> $row["round_id"],
				'ip_address' 			=> $row['ip_address'],
            ],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;

    }

	public function getGameRecordsStatus($row) {

        $status = Game_logs::STATUS_SETTLED;

		switch($row["status"]){
			case self::BET_STATUS_BETTING:
				$status = Game_logs::STATUS_PENDING;
				break;
			case self::BET_STATUS_FAILED:
				$status = Game_logs::STATUS_REJECTED;
				break;
			case self::BET_STATUS_NOT_SETTLED:
				$status = Game_logs::STATUS_PENDING;
				break;
			case self::BET_STATUS_SETTLED:
				$status = Game_logs::STATUS_SETTLED;
				break;
			case self::BET_STATUS_REFUND:
				$status = Game_logs::STATUS_REFUND;
				break;
		}

        return $status;
    }

	public function processGameBetDetail($row,$result_amount,$bet_amount){
		if( ! empty($row["game_id"])){

			//interpret the value of gameResult and gameResult2
			/***
			 * 	gameResult show you each card what is it, the size, the suit
				gameResult2 show you what the result adds up each cards.
			 */

			$gameResult = $row["gameResult"];
			$processedGameResult = $this->processGameResult($gameResult, $row["game_id"], $row["bet_type"]);


			$gameResult2 = $row["gameResult2"];
			$processedGameResult2 = null;

			if(!empty($gameResult2)) {
				$processedGameResult2 = $this->processGameResult2($gameResult2, $row["game_id"], $row["bet_type"]);
			}


			$game_type_name = self::GAME_TYPE_NAME[$row["game_id"]];

			$bet_type_name = self::BET_TYPE[$row["bet_type"]];

			$bet_details = array(
				"Bet Amount" =>  $bet_amount,
				"WinLoss Amount" => $result_amount,
				"Game Type" => $game_type_name,
				"Bet Type" => $bet_type_name,
				"Game Result" => $processedGameResult,
				"Game Result 2" => $processedGameResult2
			);

			return $bet_details;
		}
		return false;
	}

	private function processGameResult($resultString, $gameType, $betType) {
		/**const GAME_TYPE_NORMAL_BACCARAT = 101;
	const GAME_TYPE_VIP_BACCARAT = 102;
	const GAME_TYPE_QUICK_BACCARAT = 103;
	const GAME_TYPE_SEE_CARD_BACCARAT = 104;
	const GAME_TYPE_INSURANCE_BACCARAT = 110;
	const GAME_TYPE_SICBO_HILO = 201;
	const GAME_TYPE_DRAGON_TIGER = 301;
	const GAME_TYPE_ROULETTE = 401;
	const GAME_TYPE_POK_DENG = 501;
	const GAME_TYPE_BULL_BULL =	801;
	const GAME_TYPE_WIN_THREE_CARDS = 901; */

		$bet_info = array();

		switch ($gameType) {
			case self::GAME_TYPE_NORMAL_BACCARAT:
			case self::GAME_TYPE_VIP_BACCARAT:
			case self::GAME_TYPE_QUICK_BACCARAT:
			case self::GAME_TYPE_SEE_CARD_BACCARAT:
			case self::GAME_TYPE_INSURANCE_BACCARAT:
				$bet_info = $this->processGameResultForBaccarat($resultString);
				break;
			case self::GAME_TYPE_SICBO_HILO:
				$bet_info = $this->processGameResultForSicbo($resultString);
				break;
			case self::GAME_TYPE_DRAGON_TIGER:
				$bet_info = $this->processGameResultForDragonTiger($resultString);
				break;
			case self::GAME_TYPE_ROULETTE:
				$bet_info = $this->processGameResultForRoulette($resultString);
				break;
			case self::GAME_TYPE_POK_DENG:
				$bet_info = $this->processGameResultForPokdeng($resultString);
				break;
			case self::GAME_TYPE_BULL_BULL:
				$bet_info = $this->processGameResultForBullBull($resultString);
				break;
			case self::GAME_TYPE_WIN_THREE_CARDS:
				$bet_info = $this->processGameResultForWinThreeCards($resultString);
				break;

		}

		return $bet_info;

	}

	private function processGameResultForBaccarat($resultString) {

		// Use two groups of numbers enclosed in braces to indicate game result. The first one is Player’s tiles, the second one is Banker’s tiles.

		$str = str_replace("},{","}|{",$resultString);

		$resultArr = explode("|", $str);

		$player_card_info = [];
		$banker_card_info = [];

		if(isset($resultArr[0])) { // Player Tiles

			$dataSetString = $resultArr[0];
			$dataSetString = ltrim($dataSetString,"{");
			$dataSetString = rtrim($dataSetString,"}");

			$playerArr = explode(",", $dataSetString);



			foreach($playerArr as $index => $val) {

				$val = (int)$val;

				if($val != -1){

					$player_card_info[] = $this->getDataValueInfo($val);

				} else {

					$player_card_info[] = "No " . $this->addOrdinalNumberSuffix($index + 1) . " card";

				}

			}

		}


		if(isset($resultArr[1])) { // Banker Tiles

			$dataSetString = $resultArr[1];
			$dataSetString = ltrim($dataSetString,"{");
			$dataSetString = rtrim($dataSetString,"}");

			$bankerArr = explode(",", $dataSetString);



			foreach($bankerArr as $index => $val) {

				$val = (int)$val;

				if($val != -1){

					$banker_card_info[] = $this->getDataValueInfo($val);

				} else {

					$banker_card_info[] = "No " . $this->addOrdinalNumberSuffix($index + 1) . " card";

				}

			}

		}

		return array(
			"Player" => $player_card_info,
			"Banker" => $banker_card_info
		);

	}

	private function processGameResultForSicbo($resultString) {

		$resultString = ltrim($resultString,"{");
		$resultString = rtrim($resultString,"}");

		$result = [];

		$arr = explode(",", $resultString);

		foreach($arr as $index => $val) {
			$result[] = $this->addOrdinalNumberSuffix($index + 1) . " - " . $val;
		}

		return $result;

	}

	private function processGameResultForDragonTiger($resultString) {

		// Use two groups of numbers enclosed in braces to indicate game result. The first one is Dragon, the second one is Tiger.

		$str = str_replace("},{","}|{",$resultString);

		$resultArr = explode("|", $str);

		$dragon = "";
		$tiger = "";

		if(isset($resultArr[0])) { // Dragon

			$dataSetString = $resultArr[0];
			$dataSetString = ltrim($dataSetString,"{");
			$dataSetString = rtrim($dataSetString,"}");

			$num = (int)$dataSetString;

			if($num != -1){

				$dragon = $this->getDataValueInfo($num);

			} else {
				$dragon = "No Card";
			}




		}


		if(isset($resultArr[1])) { // Tiger

			$dataSetString = $resultArr[1];
			$dataSetString = ltrim($dataSetString,"{");
			$dataSetString = rtrim($dataSetString,"}");

			$num = (int)$dataSetString;

			if($num != -1){

				$tiger = $this->getDataValueInfo($num);

			} else {
				$tiger = "No Card";
			}

		}

		return array(
			"dragon" => $dragon,
			"tiger" => $tiger
		);

	}

	private function processGameResultForRoulette($resultString) {

		$resultString = ltrim($resultString,"{");
		$resultString = rtrim($resultString,"}");

		$result = $resultString;

		return $result;

	}

	private function processGameResultForPokdeng($resultString) {

		// Use two groups of numbers enclosed in braces to indicate game result. The first one is Player’s tiles, the second one is Banker’s tiles.

		$str = str_replace("},{","}|{",$resultString);

		$resultArr = explode("|", $str);


		$banker_card_info = [];

		if(isset($resultArr[0])) { // Banker Tiles

			$dataSetString = $resultArr[0];
			$dataSetString = ltrim($dataSetString,"{");
			$dataSetString = rtrim($dataSetString,"}");

			$playerArr = explode(",", $dataSetString);



			foreach($playerArr as $index => $val) {

				$val = (int)$val;

				if($val != -1){

					$banker_card_info[] = $this->getDataValueInfo($val);

				} else {

					$banker_card_info[] = "No " . $this->addOrdinalNumberSuffix($index + 1) . " card";

				}

			}

			unset($resultArr[0]);

		}


		$player_datas = array();
		$count = 1;
		foreach($resultArr as $arr) {



			$dataSetString = $arr;
			$dataSetString = ltrim($dataSetString,"{");
			$dataSetString = rtrim($dataSetString,"}");

			$playerArr = explode(",", $dataSetString);

			$player_card_info = array();



			foreach($playerArr as $index => $val) {

				$val = (int)$val;

				if($val != -1){

					$player_card_info[] = $this->getDataValueInfo($val);

				} else {

					$player_card_info[] = "No " . $this->addOrdinalNumberSuffix($index + 1) . " card";

				}

			}

			$player_datas["Player " . $count] = $player_card_info;

			$count++;

		}


		$banker = array(
			"Banker" => $banker_card_info
		);



		return array_merge($banker, $player_datas);

	}

	private function processGameResultForBullBull($resultString) {

		// Use two groups of numbers enclosed in braces to indicate game result. The first one is Player’s tiles, the second one is Banker’s tiles.

		$str = str_replace("},{","}|{",$resultString);

		$resultArr = explode("|", $str);


		$banker_card_info = [];
		$first_card = "";

		if(isset($resultArr[0])) { // Banker Tiles

			$dataSetString = $resultArr[0];
			$dataSetString = ltrim($dataSetString,"{");
			$dataSetString = rtrim($dataSetString,"}");

			$first_card = $this->getDataValueInfo($dataSetString);;
		}

		if(isset($resultArr[1])) { // Banker Tiles

			$dataSetString = $resultArr[1];
			$dataSetString = ltrim($dataSetString,"{");
			$dataSetString = rtrim($dataSetString,"}");

			$playerArr = explode(",", $dataSetString);



			foreach($playerArr as $index => $val) {

				$val = (int)$val;

				if($val != -1){

					$banker_card_info[] = $this->getDataValueInfo($val);

				} else {

					$banker_card_info[] = "No " . $this->addOrdinalNumberSuffix($index + 1) . " card";

				}

			}



		}

		unset($resultArr[0]);
		unset($resultArr[1]);


		$player_datas = array();
		$count = 1;
		foreach($resultArr as $arr) {



			$dataSetString = $arr;
			$dataSetString = ltrim($dataSetString,"{");
			$dataSetString = rtrim($dataSetString,"}");

			$playerArr = explode(",", $dataSetString);

			$player_card_info = array();



			foreach($playerArr as $index => $val) {

				$val = (int)$val;

				if($val != -1){

					$player_card_info[] = $this->getDataValueInfo($val);

				} else {

					$player_card_info[] = "No " . $this->addOrdinalNumberSuffix($index + 1) . " card";

				}

			}

			$player_datas["Player " . $count] = $player_card_info;

			$count++;

		}


		$banker = array(
			"First Card" => $first_card,
			"Banker" => $banker_card_info
		);



		return array_merge($banker, $player_datas);

	}

	private function processGameResultForWinThreeCards($resultString) {

		// Use two groups of numbers enclosed in braces to indicate game result. The first one is Player’s tiles, the second one is Banker’s tiles.

		$str = str_replace("},{","}|{",$resultString);

		$resultArr = explode("|", $str);

		$dragon_card_info = [];
		$phoenix_card_info = [];

		if(isset($resultArr[0])) { // Player Tiles

			$dataSetString = $resultArr[0];
			$dataSetString = ltrim($dataSetString,"{");
			$dataSetString = rtrim($dataSetString,"}");

			$dragonArr = explode(",", $dataSetString);



			foreach($dragonArr as $index => $val) {

				$val = (int)$val;

				if($val != -1){

					$dragon_card_info[] = $this->getDataValueInfo($val);

				} else {

					$dragon_card_info[] = "No " . $this->addOrdinalNumberSuffix($index + 1) . " card";

				}

			}

		}


		if(isset($resultArr[1])) { // Banker Tiles

			$dataSetString = $resultArr[1];
			$dataSetString = ltrim($dataSetString,"{");
			$dataSetString = rtrim($dataSetString,"}");

			$phonixArr = explode(",", $dataSetString);



			foreach($phonixArr as $index => $val) {

				$val = (int)$val;

				if($val != -1){

					$phoenix_card_info[] = $this->getDataValueInfo($val);

				} else {

					$phoenix_card_info[] = "No " . $this->addOrdinalNumberSuffix($index + 1) . " card";

				}

			}

		}

		return array(
			"Dragon" => $dragon_card_info,
			"Phoenix" => $phoenix_card_info
		);

	}


	private function addOrdinalNumberSuffix($num) {
		if (!in_array(($num % 100),array(11,12,13))){
		  switch ($num % 10) {
			// Handle 1st, 2nd, 3rd
			case 1:  return $num.'st';
			case 2:  return $num.'nd';
			case 3:  return $num.'rd';
		  }
		}
		return $num.'th';
	}


	private function getDataValueInfo($num) { // this will interpret the value in baccarat game type

		// Data format: Suit + card number
		$str = (string)$num;
		$first = $str[0];

		$second = ltrim($str,$first);

		switch($first) {
			case 1:
				$suit = "spade";
				break;
			case 2:
				$suit = "heart";
				break;
			case 3:
				$suit = "club";
				break;
			case 4:
				$suit = "diamond";
				break;
			default:
				$suit = "";
				break;
		}

		$second = (int)$second;

		$card_number = $this->getCardValue($second);


		return $suit . " " . $card_number;

	}

	private function getCardValue($num) {

		$num = (int)$num;

		switch ($num) {
			case 0:
				$val = "J, Q, K";
				break;
            case 1:
				$val = "Ace";
				break;
			case 11:
				$val = "J";
				break;
			case 12:
				$val = "Q";
				break;
			case 13:
				$val = "K";
				break;
            default:
                $val = $num;
                break;
        }
        return $val;

	}

	private function getPokdengValue($num) { // this is for gameResult2

		switch ($num) {
			case 0:
				$val = "0 Point";
				break;
            case 1:
				$val = "1 Point";
				break;
			case 2:
				$val = "2 Point";
				break;
			case 3:
				$val = "3 Point";
				break;
			case 4:
				$val = "4 Point";
				break;
			case 5:
				$val = "5 Point";
				break;
			case 6:
				$val = "6 Point";
				break;
			case 7:
				$val = "7 Point";
				break;
			case 7.1:
				$val = "Flush with No Point";
				break;
			case 7.2:
				$val = "Ten-suit Combo";
				break;
			case 7.3:
				$val = "Suits Combo";
				break;
			case 7.4:
				$val = "Special Pairs";
				break;
			case 7.5:
				$val = "AK Flush";
				break;
			case 8:
				$val = "8 Point";
				break;
			case 9:
				$val = "9 Point";
				break;
            default:
                $val = $num;
                break;
        }
        return $val;

	}

	private function getBullBullValue($num) { // this is for gameResult2

		switch ($num) {
			case 0:
				$val = "No Bull";
				break;
            case 1:
				$val = "Bull 1";
				break;
			case 2:
				$val = "Bull 2";
				break;
			case 3:
				$val = "Bull 3";
				break;
			case 4:
				$val = "Bull 4";
				break;
			case 5:
				$val = "Bull 5";
				break;
			case 6:
				$val = "Bull 6";
				break;
			case 7:
				$val = "Bull 7";
				break;
			case 8:
				$val = "Bull 8";
				break;
			case 9:
				$val = "Bull 9";
				break;
			case 10:
				$val = "Bull Bull";
				break;
			case 11:
				$val = "5 Dukes";
				break;
            default:
                $val = $num;
                break;
        }
        return $val;

	}

	private function getWinThreeCardValue($num) { // this is for gameResult2

		/**111: 235
110: Three of a kind
101: Straight Flush
100: Flush
011: Straight
010: Pair
001: High Card */

		switch ($num) {
			case 111:
				$val = 235;
				break;
            case 110:
				$val = "Three of a kind";
				break;
			case 101:
				$val = "Straight Flush";
				break;
			case 100:
				$val = "Flush";
				break;
			case 011:
				$val = "Straight";
				break;
			case 010:
				$val = "Pair";
				break;
			case 001:
				$val = "High Card";
				break;
			case 30:
				$val = "Three Pictures";
				break;
			case 29:
				$val = "Double Pictures 9";
				break;
			case 19:
				$val = "Single Picture 9";
				break;
			case "09":
				$val = "9 Point";
				break;
			case 28:
				$val = "Double Pictures 8";
				break;
			case 18:
				$val = "Single Picture 8";
				break;
			case "08":
				$val = "8 Point";
				break;
			case 27:
				$val = "Double Pictures 7";
				break;
			case 17:
				$val = "Single Picture 7";
				break;
			case 07:
				$val = "7 Point";
				break;
			case 26:
				$val = "Double Pictures 6";
				break;
			case 16:
				$val = "Single Picture 6";
				break;
			case 06:
				$val = "6 Point";
				break;
			case 25:
				$val = "Double Pictures 5";
				break;
			case 15:
				$val = "Single Picture 5";
				break;
			case 05:
				$val = "5 Point";
				break;
			case 24:
				$val = "Double Pictures 4";
				break;
			case 14:
				$val = "Single Picture 4";
				break;
			case 04:
				$val = "4 Point";
				break;
			case 23:
				$val = "Double Pictures 3";
				break;
			case 13:
				$val = "Single Picture 3";
				break;
			case 03:
				$val = "3 Point";
				break;
			case 22:
				$val = "Double Pictures 2";
				break;
			case 12:
				$val = "Single Picture 2";
				break;
			case 02:
				$val = "2 Point";
				break;
			case 21:
				$val = "Double Pictures 1";
				break;
			case 11:
				$val = "Single Picture 1";
				break;
			case 01:
				$val = "1 Point";
				break;
			case 20:
				$val = "Double Pictures 0";
				break;
			case 10:
				$val = "Single Picture 0";
				break;
			case 00:
				$val = "0 Point";
				break;
            default:
                $val = $num;
                break;
        }
        return $val;

	}

	private function processGameResult2($resultString, $gameType, $betType) {
		/**const GAME_TYPE_NORMAL_BACCARAT = 101;
	const GAME_TYPE_VIP_BACCARAT = 102;
	const GAME_TYPE_QUICK_BACCARAT = 103;
	const GAME_TYPE_SEE_CARD_BACCARAT = 104;
	const GAME_TYPE_INSURANCE_BACCARAT = 110;
	const GAME_TYPE_SICBO_HILO = 201;
	const GAME_TYPE_DRAGON_TIGER = 301;
	const GAME_TYPE_ROULETTE = 401;
	const GAME_TYPE_POK_DENG = 501;
	const GAME_TYPE_BULL_BULL =	801;
	const GAME_TYPE_WIN_THREE_CARDS = 901; */

		$bet_info = array();

		switch ($gameType) {
			case self::GAME_TYPE_NORMAL_BACCARAT:
			case self::GAME_TYPE_VIP_BACCARAT:
			case self::GAME_TYPE_QUICK_BACCARAT:
			case self::GAME_TYPE_SEE_CARD_BACCARAT:
			case self::GAME_TYPE_INSURANCE_BACCARAT:
				$bet_info = $this->processGameResult2ForBaccarat($resultString);
				break;
			// Game Result 2 is not available for Sicbo(HiLo). An empty string will be returned in gameResult2.
			// Game Result 2 is not available for Roulette. An empty string will be returned in gameResult2.
			case self::GAME_TYPE_DRAGON_TIGER:
				$bet_info = $this->processGameResult2ForDragonTiger($resultString);
				break;
			case self::GAME_TYPE_POK_DENG:
				$bet_info = $this->processGameResult2ForPokdeng($resultString);
				break;
			case self::GAME_TYPE_BULL_BULL:
				$bet_info = $this->processGameResult2ForBullBull($resultString);
				break;
			case self::GAME_TYPE_WIN_THREE_CARDS:
				$bet_info = $this->processGameResult2ForWinThreeCard($resultString);
				break;

		}

		return $bet_info;

	}

	private function processGameResult2ForBaccarat($resultString) {

		// Use two groups of numbers enclosed in braces to indicate game result. The first one is Player’s tiles, the second one is Banker’s tiles.

		$resultString = ltrim($resultString,"{");
		$resultString = rtrim($resultString,"}");

		$resultArr = explode(",", $resultString);

		$playerVal = (int)$resultArr[0];

		if($playerVal != -1){

			$player_card_info = $this->getCardValue($playerVal);

		} else {

			$player_card_info = "No card";

		}

		$bankerVal = (int)$resultArr[1];

		if($bankerVal != -1){

			$banker_card_info = $this->getCardValue($bankerVal);

		} else {

			$banker_card_info = "No card";

		}

		return array(
			"Player" => $player_card_info,
			"Banker" => $banker_card_info
		);

	}

	private function processGameResult2ForDragonTiger($resultString) {

		// Use two groups of numbers enclosed in braces to indicate game result. The first one is Player’s tiles, the second one is Banker’s tiles.

		$resultString = ltrim($resultString,"{");
		$resultString = rtrim($resultString,"}");

		$resultArr = explode(",", $resultString);

		$dragonVal = (int)$resultArr[0];

		if($dragonVal != -1){

			$dragon_card_info = $this->getCardValue($dragonVal);

		} else {

			$dragon_card_info = "No card";

		}

		$tigerVal = (int)$resultArr[1];

		if($tigerVal != -1){

			$tiger_card_info = $this->getCardValue($tigerVal);

		} else {

			$tiger_card_info = "No card";

		}

		return array(
			"Dragon" => $dragon_card_info,
			"Tiger" => $tiger_card_info
		);

	}

	private function processGameResult2ForPokdeng($resultString) {

		// Use a group of six numbers enclosed in braces to indicate game result 2. The first one is banker's result. The 2 to 6 indicate game results of player 1 to player 5.

		$resultString = ltrim($resultString,"{");
		$resultString = rtrim($resultString,"}");

		$resultArr = explode(",", $resultString);

		$bankerVal = $resultArr[0];

		$bankerInfo = $this->getPokdengValue($bankerVal);

		unset($resultArr[0]);

		$player_datas = [];

		$count = 1;

		foreach($resultArr as $val) {

			$player_datas["Player " . $count] = $this->getPokdengValue($val);
			$count++;
		}


		$result = array(
			"Banker" => $bankerInfo
		);

		return array_merge($result, $player_datas);

	}

	private function processGameResult2ForBullBull($resultString) {

		// Use a group of six numbers enclosed in braces to indicate game result 2. The first one is banker's result. The 2 to 6 indicate game results of player 1 to player 5.

		$resultString = ltrim($resultString,"{");
		$resultString = rtrim($resultString,"}");

		$resultArr = explode(",", $resultString);

		$bankerVal = (int)$resultArr[0];

		$bankerInfo = $this->getBullBullValue($bankerVal);

		unset($resultArr[0]);

		$player_datas = [];

		$count = 1;

		foreach($resultArr as $val) {

			$val = (int)$val;

			$player_datas["Player " . $count] = $this->getBullBullValue($val);
			$count++;
		}


		$result = array(
			"Banker" => $bankerInfo
		);

		return array_merge($result, $player_datas);

	}

	private function processGameResult2ForWinThreeCard($resultString) {

		// Use a group of six numbers enclosed in braces to indicate game result 2. The first one is banker's result. The 2 to 6 indicate game results of player 1 to player 5.

		$resultString = ltrim($resultString,"{");
		$resultString = rtrim($resultString,"}");

		$resultArr = explode(",", $resultString);

		$dragonVal = $resultArr[0];

		$dragon_card_info = $this->getWinThreeCardValue($dragonVal);



		$phoenixVal = $resultArr[1];

		$phoenix_card_info = $this->getWinThreeCardValue($phoenixVal);



		return array(
			"Dragon" => $dragon_card_info,
			"Phoenix" => $phoenix_card_info
		);

	}



	public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

		$row['bet_amount']= $this->gameAmountToDB($row['bet_amount']);
		$row['result_amount'] = $this->gameAmountToDB($row['result_amount']);
		$row['real_bet_amount'] = $this->gameAmountToDB($row['real_bet_amount']);

		$row['bet_details']=$this->processGameBetDetail($row,$row['result_amount'], $row['bet_amount']);
    }

	private function getGameDescriptionInfo($row, $unknownGame) {

		$game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game'], $row['game_id']);
        }

        return [$game_description_id, $game_type_id];

	}


	# END SYNC MERGE TO GAME LOGS #################################################################################################################################


	public function logout($playerName, $password = null) {

		$playerName = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLogout',
			'playerName' => $playerName,
		);

		$api_url = self::API_logout;

		$params = array(
			'player' => $playerName . $this->suffix,
			'uri' => self::URI_MAP[$api_url]
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
			case 'cn':
			case 'zh-cn':
                $lang = 'zh_CN';
                break;
			case 'tw':
			case 'zh-tw':
				$lang = 'zh_TW';
                break;
				
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
			case 'ko-kr':
			case 'ko':
                $lang = 'kr';
                break;

            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
			case 'id-id':
			case 'id':
                $lang = 'id';
                break;

            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
			case 'vi':
			case 'vi-vn':
                $lang = 'vi';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
                $lang = 'ko';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
			case 'th':
			case 'th-th':
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
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryForwardGame',
			'playerName' => $playerName,
			'gameUsername' => $gameUsername
		);

		$api_url = self::API_queryForwardGame;

		$lang = $this->getSystemInfo('language', $extra['language']);

		if(isset($extra['language']) && !empty($extra['language'])){
            $language=$this->getLauncherLanguage($extra['language']);
        }else{
            $language=$this->getLauncherLanguage($lang);
        }

		$params = array_filter(array(
			'player' => $gameUsername . $this->suffix,
			'password' => $password,
			'targetUrl' => isset($extra['targetUrl']) ? $extra['targetUrl'] : null,
            'language' => $language,
			'uri' => self::URI_MAP[$api_url]
		));

		$is_mobile = isset($extra['is_mobile'])?$extra['is_mobile']:false;

		#OGP-33475 removing appType params
		// if($is_mobile) {
		// 	$params["appType"] = 3;
		// }

		$return_url = $this->getSystemInfo('return_url', null);

		if(!empty($return_url)){
			$params["returnUrl"] = $return_url;
		}

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

	public function processResultForQueryForwardGame($params) {

		$responseResultId = $this->getResponseResultIdFromParams($params);
		$resultJson = $this->getResultJsonFromParams($params);
		$gameUsername = $this->getVariableFromContext($params, 'gameUsername');
		$success = $this->processResultBoolean($responseResultId, $resultJson, $gameUsername);

		$url='';
        $is_redirect = $this->is_redirect;
		if(!isset($resultJson['data']['gameLoginUrl'])){
			$success=false;
		}else{
			if(empty($resultJson['data']['gameLoginUrl'])) {
				$success = false;
			} else {
				$url=$resultJson['data']['gameLoginUrl'];
			}

		}
		return array($success, array('url' => $url, 'is_redirect' => $is_redirect));

	}

	# END QUERY FORWARD GAME #################################################################################################################################

	# IMPROVISED / UTILS ##############################################################################################################################################################################
	public function getPlatformCode() {

		return AB_V2_GAME_API;
	}

	public function generateUrl($apiName, $params) {

		$apiUri = self::URI_MAP[$apiName];


		$url = $this->api_url . $apiUri;

		return $url;
	}

	public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
		// afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText, $extra, $resultObj);
		return array(false, null);
	}

	public function processResultBoolean($responseResultId, $resultJson, $playerName) {

		$success = (!empty($resultJson)) && $resultJson['resultCode'] == self::SUCCESS_CODE;

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

		$api_url = self::API_queryTransaction;


		$params = array(
			'sn' => $transactionId,
			'uri' => self::URI_MAP[$api_url]
		);

		return $this->callApi(self::API_queryTransaction, $params, $context);
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
				case 'SYSTEM_MAINTENANCE' :
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
				case 'PLAYER_NOT_EXIST' :
					$result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
					break;
				case 'INTERNAL_ERROR' :
					$result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
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

	// public function onlyTransferPositiveInteger(){
	// 	return true;
	// }

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
    // End of testing
}