<?php
if(! defined('BASEPATH')){
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/ag_seamless_game_syncing_utils.php';

/**
 * 
 * 
 * @integrator jason.php.ph
 */

abstract class Abstract_game_api_common_seamless_ag_gaming extends Abstract_game_api
{
    use ag_seamless_game_syncing_utils;

    /** 
     * Determine if API call is HTTP,SOAP or XMLRPC
     * 
     * @var boolean|true $isHttpCall
    */
    protected $isHttpCall = true;

    /**
     * API URL of Game Provider
     * 
     * @var string $apiUrl
     */
    protected $apiUrl;

    /** 
     * The Curreny of Game
     * 
     * @var string $currencyType
    */
    protected $currencyType;

    /** 
     * The Curreny of Game in Extra Info
     * 
     * @var string $currencyType
    */
    public $currencyTypeInExtraInfo;

    /** 
     * Proxy Code in Extra Info
     * 
     * @var string $currencyType
    */
    public $cAgent;

    /** 
     * MD5 encryption Key in Extra Info
     * 
     * @var string $currencyType
    */
    public $md5EncryptionKey;

    /** 
     * DES encryption Key in Extra Info
     * 
     * @var string $currencyType
    */
    protected $desEncryptionKey;

    /** 
     * DES encryption Key in Extra Info
     * 
     * @var string $currencyType
    */
    public $productID;

    /** 
     * Game Session URL, needed before player can launch a game
     * 
     * @var string $gameSessionUrl
    */
    protected $gameSessionUrl;

    /** 
     * Game Session URL, needed before player can launch a game
     * 
     * @var string $gameLaunchUrl
    */
    protected $gameLaunchUrl;

    /**
     * Redirect Mode in Game Launch, true when always redirect not in iframe
     * 
     * @var boolean $gameRedirectMode;
     */
    protected $gameRedirectMode;

    /**
     * Odd type of players, or the bet range
     * 
     * @var string $playerOddType
     */
    protected $playerOddType;

    /**
     * Default Lobby game code
     * 
     * @var int $defaultLobbyCode
     */
    protected $defaultLobbyCode;

    /**
     * Partner website
     * 
     * @var int $partnerWebsite
     */
    protected $partnerWebsite;

    /**
     * Default game language
     * 
     * @var int $defaultGameLanguage
     */
    public $defaultGameLanguage;

    /**
     * URI MAP of Game API Endpoints
     * 
     * @var const URI_MAP
     */
    const URI_MAP = [
        self::API_createPlayer => '/doBusiness.do',
        self::API_createPlayerGameSession => '/resource/player-tickets.ucs',
        self::API_queryForwardGame => '/forwardGame.do'
    ];

    const AG_PLATFORM_TYPE = 'AGIN';
    const AG_FISHING_PLATFORM_TYPE = 'HUNTER';
    const AG_SPORTS_PLATFORM_TYPE = 'SBTA';
    const AGBBIN_PLATFORM_TYPE = 'BBIN';
    const AGSHABA_PLATFORM_TYPE = 'SABAH';
    const AG_SLOTS_PLATFORM_TYPE = 'XIN';
    const RED_POCKET = 'RED_POCKET';
    const HUNTER_GAME_CODE= 'hunter';
    const DATA_TYPE_EBR = "EBR";
    const AG_CODE_OK = 'OK';
    const AG_CODE_SUCCESS = '0';

    const AGIN_PLAY_TYPE = array(
        1 => "Banker",
        2 => "Player",
        3 => "Tie",
        4 => "Banker Pair",
        5 => "Player Pair",
        6 => "Big",
        7 => "Small",
        8 => "Banker Insurance bets",
        9 => "Player Insurance Bets",
        11 => "Banker no commission",
        12 => "Banker dragon bonus",
        13 => "Player dragon bonus",
        14 => "Super Six",
        15 => "Any Pair",
        16 => "Perfect Pair",
        21 => "Dragon",
        22 => "Tiger",
        23 => "Tie (Dragon Tiger)",
        41 => "big",
        42 => "small",
        43 => "single",
        44 => "double",
        45 => "all wei",
        46 => "wei 1",
        47 => "wei 2",
        48 => "wei 3",
        49 => "wei 4",
        50 => "wei 5",
        51 => "wei 6",
        52 => "single 1",
        53 => "single 2",
        54 => "single 3",
        55 => "single 4",
        56 => "single 5",
        57 => "single 6",
        58 => "double 1",
        59 => "double 2",
        60 => "double 3",
        61 => "double 4",
        62 => "double 5",
        63 => "double 6",
        64 => "combine 12",
        65 => "combine 13",
        66 => "combine 14",
        67 => "combine 15",
        68 => "combine 16",
        69 => "combine 23",
        70 => "combine 24",
        71 => "combine 25",
        72 => "combine 26",
        73 => "combine 34",
        74 => "combine 35",
        75 => "combine 36",
        76 => "combine 45",
        77 => "combine 46",
        78 => "combine 56",
        79 => "sum 4",
        80 => "sum 5",
        81 => "sum 6",
        82 => "sum 7",
        83 => "sum 8",
        84 => "sum 9",
        85 => "sum 10",
        86 => "sum 11",
        87 => "sum 12",
        88 => "sum 13",
        89 => "sum 14",
        90 => "sum 15",
        91 => "sum 16",
        92 => "sum 17",
        101 => "Direct",
        102 => "Separate",
        103 => "Street",
        104 => "Three Numbers",
        105 => "Four Numbers",
        106 => "Triangle",
        107 => "Row (1st Row)",
        108 => "Row (2nd Row)",
        109 => "Row (3rd Row)",
        110 => "Line",
        111 => "1st dozen",
        112 => "2nd dozen",
        113 => "3rd dozen",
        114 => "Red",
        115 => "Black",
        116 => "Big",
        117 => "Small",
        118 => "Odd",
        119 => "Even",
        130 => "1 Fan",
        131 => "2 Fan",
        132 => "3 Fan",
        133 => "4 Fan",
        134 => "1 Nim 2",
        135 => "1 Nim 3",
        136 => "1 Nim 4",
        137 => "2 Nim 1",
        138 => "2 Nim 3",
        139 => "2 Nim 4",
        140 => "3 Nim 1",
        141 => "3 Nim 2",
        142 => "3 Nim 4",
        143 => "4 Nim 1",
        144 => "4 Nim 2",
        145 => "4 Nim 3",
        146 => "Kwok (1,2)",
        147 => "Odd",
        148 => "Kwok (1,4)",
        149 => "Kwok (2,3)",
        150 => "Even",
        151 => "Kwok (3,4)",
        152 => "142",
        153 => "132",
        154 => "143",
        155 => "123",
        156 => "134",
        157 => "124",
        158 => "243",
        159 => "213",
        160 => "234",
        161 => "214",
        162 => "324",
        163 => "314",
        164 => "3:1 (3,2,1)",
        165 => "3:1(2,1,4)",
        166 => "3:1(1,4,3)",
        167 => "3:1(4,3,2)",
        180 => "judgeResult-holdem-180",
        181 => "judgeResult-holdem-181",
        182 => "judgeResult-holdem-182",
        183 => "judgeResult-holdem-183",
        184 => "judgeResult-holdem-184",
        207 => "judgeResult-nn-207",
        208 => "judgeResult-nn-208",
        209 => "judgeResult-nn-209",
        210 => "judgeResult-nn-210",
        211 => "judgeResult-nn-211",
        212 => "judgeResult-nn-212",
        213 => "judgeResult-nn-213",
        214 => "judgeResult-nn-214",
        215 => "judgeResult-nn-215",
        216 => "judgeResult-nn-216",
        217 => "judgeResult-nn-217",
        218 => "judgeResult-nn-218",
        220 => "judgeResult-blackjack-220",
        221 => "judgeResult-blackjack-221",
        222 => "judgeResult-blackjack-222",
        223 => "judgeResult-blackjack-223",
        224 => "judgeResult-blackjack-224",
        225 => "judgeResult-blackjack-225",
        226 => "judgeResult-blackjack-226",
        227 => "judgeResult-blackjack-227",
        228 => "judgeResult-blackjack-228",
        229 => "judgeResult-blackjack-229",
        230 => "judgeResult-blackjack-230",
        231 => "judgeResult-blackjack-231",
        232 => "judgeResult-blackjack-232",
        233 => "judgeResult-blackjack-233",
        260 => "dragon",
        261 => "Phoenix",
        262 => "judgeResult-winThreeCards-262",
        263 => "judgeResult-winThreeCards-263",
        264 => "judgeResult-winThreeCards-264",
        265 => "judgeResult-winThreeCards-265",
        266 => "judgeResult-winThreeCards-266",
        270 => "judgeResult-bullFight-270",
        271 => "judgeResult-bullFight-271",
        272 => "judgeResult-bullFight-272",
        273 => "judgeResult-bullFight-273",
        274 => "judgeResult-bullFight-274",
        275 => "judgeResult-bullFight-275",
        276 => "judgeResult-bullFight-276",
        277 => "judgeResult-bullFight-277",
        278 => "judgeResult-bullFight-278",
        279 => "judgeResult-bullFight-279",
        280 => "judgeResult-bullFight-280",
        281 => "judgeResult-bullFight-281",
        282 => "judgeResult-bullFight-282",
        283 => "judgeResult-bullFight-283",
        284 => "judgeResult-bullFight-284",
        320 => 'Banker Win Player 1',
        321 => 'Player 1 Win',
        322 => 'Player 1 Tie',
        323 => 'Banker Win Player 2',
        324 => 'Player 2 Win',
        325 => 'Player 2 Tie',
        326 => 'Banker Win Player 3',
        327 => 'Player 3 Win',
        328 => 'Player 3 Tie',
        329 => 'Banker Pair Plus',
        330 => 'Player 1 Pair Plus',
        331 => 'Player 1 Three Face',
        332 => 'Player 2 Pair Plus',
        333 => 'Player 2 Three Face',
        334 => 'Player 3 Pair Plus',
        335 => 'Player 3 Three Face',
    );


    /** 
     * Model To Load
     * 
     * @var array $modelToLoad
    */
    protected  $modelToLoad = [
    ];

    public function __construct()
    {
        parent::__construct();

        /** Game API Settings */
        $this->apiUrl = $this->getSystemInfo('url');

        /** Extra Info */
        $this->currencyTypeInExtraInfo = $this->getSystemInfo('currencyType');
        $this->cAgent = $this->getSystemInfo('cAgent');
        $this->md5EncryptionKey = $this->getSystemInfo('md5_encryption_key');
        $this->desEncryptionKey = $this->getSystemInfo('des_encryption_key');
        $this->productID = $this->getSystemInfo('productID');
        $this->gameSessionUrl = $this->getSystemInfo('gameSessionUrl');
        $this->gameLaunchUrl = $this->getSystemInfo('gameLaunchUrl');
        $this->gameRedirectMode = $this->getSystemInfo('gameRedirectMode',true);
        $this->playerOddType = $this->getSystemInfo('playerOddType','A');
        $this->defaultLobbyCode = $this->getSystemInfo('defaultLobbyCode',0);
        $this->partnerWebsite = $this->getSystemInfo('partnerWebsite','https://www.sexycasino.com');
        $this->defaultGameLanguage = $this->getSystemInfo('defaultGameLanguage','th');
        $this->implementGameRedirectionEvent = $this->getSystemInfo('implementGameRedirectionEvent',false);
        $this->depositPage = $this->getSystemInfo('depositPage');
        $this->registerPage = $this->getSystemInfo('registerPage');
        $this->customerServicePage = $this->getSystemInfo('customerServicePage');
        $this->homeLink =  $this->getSystemInfo('homeLink');


        /** Load Model Here */
        $this->loadModel($this->modelToLoad);

        /** for utils  */
        $defaultIgnorePlatform=['IPM', 'BBIN', 'MG', 'SABAH', 'HG', 'PT',
        'OG', 'UGS', 'XTD', 'ENDO', 'BG'];
        $this->ignore_platformtypes = $this->getSystemInfo('ignore_platformtypes', $defaultIgnorePlatform);
        $this->allowed_transfer_type= $this->getSystemInfo('allowed_transfer_type', [self::RED_POCKET]);
        $this->ignore_type_array = $this->getSystemInfo('ignore_type_array', ['TR', 'HTR', 'GR', 'TEXGR', 'LGR']);
        $this->is_update_original_row = $this->getSystemInfo('is_update_original_row',true);
        $this->merge_game_logs = $this->getSystemInfo('merge_game_logs', false);

    }

    /** 
     * Determine if the Game API is Seamless or Transfer Wallet
     * 
     * @return boolean
    */
    public function isSeamLessGame()
    {
        return true;
    }

    /** 
     * Get API call Type
     * 
     * @param string $apiName
     * @param array $params
     * 
     * @return int
    */
    protected function getCallType($apiName,$params)
    {
        if(! $this->isHttpCall){
            return self::CALL_TYPE_SOAP;
        }

        return self::CALL_TYPE_HTTP;
    }

    protected function customHttpCall($ch, $params) {
        return $this->returnUnimplemented();
    }

    /** 
     * Abstract in Parent Class
     * Constant in apis.php, Game API unique ID
     * 
     * @return array
    */
    public function getPlatformCode()
    {
        return $this->returnUnimplemented();
    }

    /** 
     * Abstract in Parent Class,Since this is seamless, the transaction like deposit is only save in table playeraccount
     * 
     * @param string $playerName
     * @param int $amount
     * @param int|null $transfer_secure_id
     * 
     * @return
    */
    public function depositToGame($playerName,$amount,$transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        $this->logThis(__METHOD__ .' player name is: >>>>>>>>',$playerName);
  
        return [
           "success" => true,
           "external_transaction_id" => $external_transaction_id,
           "response_result_id" => null,
           "didnot_insert_game_logs" => true
        ];
    }

    /**
     * Abstract in Parent Class,Since this is seamless, the transaction like deposit is only save in table playeraccount
     * 
     * @param string $playerName
     * @param int $amount
     * @param int|null $transfer_secure_id
     * 
     * @return
     */
    public function withdrawFromGame($playerName,$amount,$transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        $this->logThis(__METHOD__ .' player name is: >>>>>>>>',$playerName);
  
        return [
           "success" => true,
           "external_transaction_id" => $external_transaction_id,
           "response_result_id" => null,
           "didnot_insert_game_logs" => true
        ];
    }

    /** 
     * Abstract in Parent Class
     * 
     * @param $playerName
     * 
     * @return
    */
    public function queryPlayerBalance($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

        $balance = $this->getPlayerSubWalletBalance($playerId);

        if(! is_null($balance)){
            return [
                'success' => true,
                'balance' => $balance
            ];
        }

        return [
            'success' => false,
            'balance' => $balance
        ];

    }
    
    /**
     * Abstract in Parent Class
     * 
     * @param string $transactionId
     * @param array $extra
     * 
     * @return
     */
    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    /** 
     * Abstract in Parent Class
     * TODO
     * 
     * @param string $playerName
     * @param array $extra
     * 
     * @return
    */
    public function queryForwardGame($playerName, $extra)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordString($playerName);
        $sid = $this->cAgent.random_string('numeric', 16);
        $currencyType = $this->gameCurrency();
        $is_mobile = isset($extra['is_mobile']) ? $extra['is_mobile'] : false;

        $playerCurrentToken = $this->getPlayerTokenByUsername($playerName);

        $isTokenValid = $this->isTokenValid($playerCurrentToken);

        if($isTokenValid){
            $playerInfo = $this->getPlayerInfoBasedInToken($playerCurrentToken);
            $playerId = isset($playerInfo["playerId"]) ? $playerInfo["playerId"] : null;
            $gameRedirectMode = $this->gameRedirectMode ?: $is_mobile;
            $language = isset($extra['language']) ? $extra['language'] : $this->defaultGameLanguage;

            $playerBalance = $this->getPlayerSubWalletBalance($playerId);


            $cPg = $this->createPlayerGameSession($this->productID,$playerName,$playerCurrentToken,$playerBalance);

            $params = [
                'cagent' => $this->cAgent,
                'loginname' => $gameUsername,
                'password' => $password,
                'dm' => $this->partnerWebsite,
                'sid' => $sid,
                'mh5' => '',
                'actype' => '1',
                'lang' => $this->getLauncherLanguage($language),
                'gameType' => $this->defaultLobbyCode,
                'oddtype' => $this->playerOddType,
                'cur' => $currencyType
            ];

            # check if game code is set, if so, override it in param
            if(isset($extra['game_code'])){
                $params['gameType'] = $extra['game_code'];
            }
            # check if game mode is set, if so, override it in param
            if(isset($extra['game_mode'])){
                $params['actype'] = ($extra['game_mode'] == 'trial') ? '0' : '1';
            }

            $this->logThis(__METHOD__ .' params >>>>>>>>',$params);
    
            $url = $this->gameLaunchUrl .'/forwardGame.do' . '?' . $this->generateUrlGetParam($params);


            # check if game redirect mode is true, if so, redirect it
            if($gameRedirectMode){
                return redirect($url);
            }
            
            return [
                'success' => true,
                'url' => $url
            ];
        }
        

        return ['success'=>false,'url'=>null];
    }


    /**
     * Abstract in Parent Class
     * Sync Original Game Logs
     * 
     * @param string $token token from sync Information, found in \Game_platform_manager::class@syncGameRecordsNoMergeOnOnePlatform
     * 
     * @return
     */
    // public function syncOriginalGameLogs($token)
    // {
    //     return $this->returnUnimplemented();
    // }

    /** 
     * Abstract in Parent Class
     * Merge Game Logs from Sync Original
     * 
     * @param string $token token from sync Information, found in \Game_platform_manager::class@mergeGameLogs
     * 
     * @return
    */
    public function syncMergeToGameLogs($token)
    {
        $this->CI->load->model(array('game_logs'));

        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $dateTimeFrom->modify($this->getDatetimeAdjust());

        $this->CI->utils->debug_log('[syncMergeToGameLogs_ag] after adjust', 'dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);
        $rlt = array('success' => true);

        //observer the date format
        $startDate = $dateTimeFrom->format('Y-m-d H:i:s');
        $endDate = $dateTimeTo->format('Y-m-d H:i:s');

        $result = $this->getOriginalGameLogsByDate($startDate, $endDate);
        if ($result) {
            $this->mergeResultGameLogs($result);
        }

        return $rlt;
    }

    public function getOriginalGameLogsByDate($startDate, $endDate)
    {
        $this->CI->load->model('agin_seamless_game_logs_thb');

        return $this->CI->agin_seamless_game_logs_thb->getGameLogStatistics($startDate, $endDate);
    }
    
    public function createGameBetDetialsJson($data, $multibetData = null) {
        $betDetails = empty($multibetData) ? array() : $multibetData;

        $extra_win_amount = $data['netamount'] > 0 ? $data['netamount'] : 0;
        $won_side = $data['netamount'] > 0 ? "Yes" : "No";
        $bet_placed = null;
        if(array_key_exists($data['playtype'], self::AGIN_PLAY_TYPE)){
            $bet_placed=self::AGIN_PLAY_TYPE[$data['playtype']];
        }

        $gameBetDetail = $this->processGameBetDetail($data);
        if(isset($gameBetDetail['bet']) && !empty($gameBetDetail['bet'])){
            $bet_placed = $gameBetDetail['bet'];
        }

        $betDetails['bet_details'][$data['billno']] = array(
            "odds" => null,
            'win_amount' => $extra_win_amount,
            'bet_amount' => $data['betamount'],
            "bet_placed" => $bet_placed,
            "won_side" => $won_side,
            "winloss_amount" => $data['netamount'],
        );
        $betDetails['isMultiBet'] = !empty($multibetData);

        return json_encode($betDetails);
    }

    public function createBetDetailsAndCheckIfComboBets($gameRecords) {

        $map = array();
        $count = 0;
        $newData = [];
        # check if combo bets and create bet details
        if (!empty($gameRecords)) {
            foreach ($gameRecords as $key => $row) {
                if (empty($row['gamecode'])) {
                    array_push($newData, $row);
                    continue;
                }

                $gameRecords[$key]['extra'] = $this->createGameBetDetialsJson($row);
                $arrayMapKey = $row['gamecode'].$row['playername'];

                # check if game code is not exist in map
                if (!array_key_exists($arrayMapKey, $map)) {
                    $map[$arrayMapKey] = array(
                        "game_code" => $row['gamecode'],
                        "isMultiBet" => false,
                    );
                } else {
                    # if exist append bet detials to extra
                    $map[$arrayMapKey]['isMultiBet'] = true;
                    // $newExtra = $this->createGameBetDetialsJson($row, $extra);
                    // $map[$arrayMapKey]['extra'] = $newExtra;
                }
            }
        }

        # append bet details to raw
        if (!empty($gameRecords)) {
            foreach ($gameRecords as $key => $value) {
                # extract extra info
                $extra = isset($value['extra'])?json_decode($value['extra'], true):[];

                # update extra if multi bet or single bet
                $mapArrayIndex = $value['gamecode'].$value['playername'];
                $extra['isMultiBet'] = isset($map[$mapArrayIndex]['isMultiBet'])?$map[$mapArrayIndex]['isMultiBet']:false;
                $gameRecords[$key]['extra'] = json_encode($extra);
            }
        }

        return $gameRecords;
    }

    public function mergeResultGameLogs($result)
    {
        $this->CI->load->model(array('agin_seamless_game_logs_result','common_seamless_wallet_transactions'));
        $unknownGame = $this->getUnknownGame();

        $this->CI->utils->debug_log('[mergeResultGameLogs] merge game logs '.$this->getPlatformCode().' count', count($result));
        foreach ($result as $key) {

            $player_id = $key->player_id;
            $username = $key->playername;
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($key, $unknownGame);

            $start_at = $key->start_at;
            $end_at = $key->end_at;
            $note = null;
            $match_details = null;
            $after_balance=0;
            switch ($key->platformtype) {
                case self::AG_PLATFORM_TYPE:
                    $end_at = $key->start_at;
                    $after_balance = $key->after_balance;
                    $roundNumber = $key->gamecode;
                    if(empty($roundNumber)){
                        $roundNumber = $key->billno;
                    }
                    $note = $key->billno;
                    $game_result =  $this->CI->agin_seamless_game_logs_result->getGameResultByGameCode($roundNumber);
                    $match_details = (!empty($game_result)) ? $game_result->card_list : null ;
                    break;

                case self::AG_SLOTS_PLATFORM_TYPE:
                    $end_at = $key->start_at;
                    $note = $key->billno."\n-\n ".$key->subbillno;
                    $roundNumber = $key->billno;
                    break; 
                default:
                    $end_at = $key->start_at;
                    $roundNumber = $key->billno;
                    break;
            }

            $real_bet_amount= isset($key->real_bet_amount) ? $key->real_bet_amount : 0;

            $betDetail= $this->processGameBetDetail((array)$key);

            $extra = array(
                'table'       => $roundNumber,
                'note'        => $note,
                'trans_amount'=> $real_bet_amount,
                'match_details' => $match_details,
                'bet_details' => $betDetail,
            );

            if(isset($key->id)) {
                $extra['sync_index'] = $key->id;
            }

            if ($key->platformtype == self::AG_PLATFORM_TYPE && isset($key->extra) && !empty($key->extra)){
                # destroy extra value then create new one
                unset($extra);
                $extraData = json_decode($key->extra, true);
                $betType = $extraData['isMultiBet'] ? 'Combo Bet':'Single Bet';
                unset($extraData['isMultiBet']);

                $extra = array(
                    'trans_amount' => $real_bet_amount,
                    'table' => $roundNumber,
                    'bet_details'  => json_encode($extraData),
                    'bet_type'     => $betType,
                    'match_details' => $match_details,
                    'note'        => $note,
                    'sync_index'  => $key->id,
                );
            }

            $bet_amount=$key->bet_amount;
            $result_amount = $key->result_amount;
            // $after_balance = $key->after_balance;
            $after_balance = 0; # default to 0
            if(empty($roundNumber)){
                $roundNumber = null;
            }
            $afterBalanceResult = $this->CI->common_seamless_wallet_transactions->getAfterBalance($roundNumber,"game_id");

            if(! empty($afterBalanceResult)){
                # update after balance
                $after_balance = $afterBalanceResult;

                $this->CI->utils->debug_log("after balance updated: ",$afterBalanceResult,'game_round_id',$roundNumber);
            }else{
                $this->CI->utils->debug_log("after balance not updated: ",'game_round_id',$roundNumber);
            }

            $flag = Game_logs::FLAG_GAME;

            if (self::DATA_TYPE_EBR == $key->datatype) {
               $after_balance = null;
            }

            $this->syncGameLogs(
                $game_type_id,
                $game_description_id,
                $key->game_code,
                $key->game_type,
                $key->game,
                $player_id,
                $username,
                $bet_amount,
                $result_amount,
                null,
                null,
                $after_balance,
                null,
                $key->external_uniqueid,
                $start_at,
                $end_at,
                $key->response_result_id,
                $flag,
                $extra
            );
            // }
        }
    }

    public function processGameBetDetail($rowArray){
        // {"bet": "Banker", "rate": 0.95, "bet_detail": ""}

        $playtype=intval(@$rowArray['playtype']);

        $bet=null;
        $rate=null;
        $bet_detail=null;

        switch (@$rowArray['gametype']) {
            case 'BAC':
            case 'CBAC':
            case 'LBAC':
            case 'SBAC':
            case 'LINK':
                if($playtype==1){
                    $bet='banker';
                    $rate=0.95;
                }elseif($playtype==2){
                    $bet='player';
                    $rate=1;
                }elseif($playtype==3){
                    $bet='tie';
                    $rate=8;
                }elseif($playtype==4){
                    $bet='bankerPair';
                    $rate=11;
                }elseif($playtype==5){
                    $bet='playerPair';
                    $rate=11;
                }elseif($playtype==6){
                    $bet='big';
                    $rate=0.5;
                }elseif($playtype==7){
                    $bet='small';
                    $rate=1.5;
                }elseif($playtype==8){
                    $bet='bankerinsurance';
                }elseif($playtype==9){
                    $bet='playerinsurance';
                }elseif($playtype==11){
                    $bet='bankernofee';
                }elseif($playtype==12){
                    $bet='bankerlongbao';
                }elseif($playtype==13){
                    $bet='playerlongbao';
                }elseif($playtype==14){
                    $bet='Super Six';
                }elseif($playtype==15){
                    $bet='Any Pair';
                }elseif($playtype==16){
                    $bet='Perfect Pair';
                }elseif($playtype==17){
                    $bet='Banker Natural';
                    $rate=4.00;
                }elseif($playtype==18){
                    $bet='Player Natural';
                    $rate=4.00;
                }elseif($playtype==30){
                    $bet='Super Tie 0';
                    $rate=150.00;
                }elseif($playtype==31){
                    $bet='Super Tie 1';
                    $rate=215.00;
                }elseif($playtype==32){
                    $bet='Super Tie 2';
                    $rate=225.00;
                }elseif($playtype==33){
                    $bet='Super Tie 3';
                    $rate=200.00;
                }elseif($playtype==34){
                    $bet='Super Tie 4';
                    $rate=120.00;
                }elseif($playtype==35){
                    $bet='Super Tie 5';
                    $rate=110.00;
                }elseif($playtype==36){
                    $bet='Super Tie 6';
                    $rate=40.00;
                }elseif($playtype==37){
                    $bet='Super Tie 7';
                    $rate=40.00;
                }elseif($playtype==38){
                    $bet='Super Tie 8';
                    $rate=80.00;
                }elseif($playtype==39){
                    $bet='Super Tie 9';
                    $rate=80.00;
                }
                break;
            case 'DT':
                if($playtype==21){
                    $bet='dragon';
                    $rate=1;
                }elseif($playtype==22){
                    $bet='tiger';
                    $rate=1;
                }elseif($playtype==23){
                    $bet='tie';
                    $rate=8;
                }elseif($playtype==130){
                    $bet='Dragon Odd';
                    $rate=0.75;
                }elseif($playtype==131){
                    $bet='Tiger Odd';
                    $rate=0.75;
                }elseif($playtype==132){
                    $bet='Dragon Even';
                    $rate=1.05;
                }elseif($playtype==133){
                    $bet='Tiger Even';
                    $rate=1.05;
                }elseif($playtype==134){
                    $bet='Dragon Red';
                    $rate=0.9;
                }elseif($playtype==135){
                    $bet='Tiger Red';
                    $rate=0.9;
                }elseif($playtype==136){
                    $bet='Dragon Black';
                    $rate=0.9;
                }elseif($playtype==137){
                    $bet='Tiger Black';
                    $rate=0.9;
                }
                break;
            case 'SHB':
                if($playtype==41){
                    $bet='big';
                    $rate=1;
                }elseif($playtype==42){
                    $bet='small';
                    $rate=1;
                }elseif($playtype==43){
                    $bet='odd';
                    $rate=1;
                }elseif($playtype==44){
                    $bet='even';
                    $rate=1;
                }elseif($playtype==45){
                    $bet='allTriple';
                    $rate=24;
                }elseif($playtype==46){
                    $bet='betMap-sicbo-110';
                    $rate=150;
                }elseif($playtype==47){
                    $bet='betMap-sicbo-111';
                    $rate=150;
                }elseif($playtype==48){
                    $bet='betMap-sicbo-112';
                    $rate=150;
                }elseif($playtype==49){
                    $bet='betMap-sicbo-113';
                    $rate=150;
                }elseif($playtype==50){
                    $bet='betMap-sicbo-114';
                    $rate=150;
                }elseif($playtype==51){
                    $bet='betMap-sicbo-115';
                    $rate=150;
                }elseif($playtype==52){
                    $bet='betMap-sicbo-134';
                }elseif($playtype==53){
                    $bet='betMap-sicbo-135';
                }elseif($playtype==54){
                    $bet='betMap-sicbo-136';
                }elseif($playtype==55){
                    $bet='betMap-sicbo-137';
                }elseif($playtype==56){
                    $bet='betMap-sicbo-138';
                }elseif($playtype==57){
                    $bet='betMap-sicbo-139';
                }elseif($playtype==58){
                    $bet='betMap-sicbo-104';
                    $rate=8;
                }elseif($playtype==59){
                    $bet='betMap-sicbo-105';
                    $rate=8;
                }elseif($playtype==60){
                    $bet='betMap-sicbo-106';
                    $rate=8;
                }elseif($playtype==61){
                    $bet='betMap-sicbo-107';
                    $rate=8;
                }elseif($playtype==62){
                    $bet='betMap-sicbo-108';
                    $rate=8;
                }elseif($playtype==63){
                    $bet='betMap-sicbo-109';
                    $rate=8;
                }elseif($playtype==64){
                    $bet='betMap-sicbo-140';
                    $rate=5;
                }elseif($playtype==65){
                    $bet='betMap-sicbo-141';
                    $rate=5;
                }elseif($playtype==66){
                    $bet='betMap-sicbo-142';
                    $rate=5;
                }elseif($playtype==67){
                    $bet='betMap-sicbo-143';
                    $rate=5;
                }elseif($playtype==68){
                    $bet='betMap-sicbo-144';
                    $rate=5;
                }elseif($playtype==69){
                    $bet='betMap-sicbo-145';
                    $rate=5;
                }elseif($playtype==70){
                    $bet='betMap-sicbo-146';
                    $rate=5;
                }elseif($playtype==71){
                    $bet='betMap-sicbo-147';
                    $rate=5;
                }elseif($playtype==72){
                    $bet='betMap-sicbo-148';
                    $rate=5;
                }elseif($playtype==73){
                    $bet='betMap-sicbo-149';
                    $rate=5;
                }elseif($playtype==74){
                    $bet='betMap-sicbo-150';
                    $rate=5;
                }elseif($playtype==75){
                    $bet='betMap-sicbo-151';
                    $rate=5;
                }elseif($playtype==76){
                    $bet='betMap-sicbo-152';
                    $rate=5;
                }elseif($playtype==77){
                    $bet='betMap-sicbo-153';
                    $rate=5;
                }elseif($playtype==78){
                    $bet='betMap-sicbo-154';
                    $rate=5;
                }elseif($playtype==79){
                    $bet='betMap-sicbo-117';
                    $rate=50;
                }elseif($playtype==80){
                    $bet='betMap-sicbo-118';
                    $rate=18;
                }elseif($playtype==81){
                    $bet='betMap-sicbo-119';
                    $rate=14;
                }elseif($playtype==82){
                    $bet='betMap-sicbo-120';
                    $rate=12;
                }elseif($playtype==83){
                    $bet='betMap-sicbo-121';
                    $rate=8;
                }elseif($playtype==84){
                    $bet='betMap-sicbo-125';
                    $rate=6;
                }elseif($playtype==85){
                    $bet='betMap-sicbo-126';
                    $rate=6;
                }elseif($playtype==86){
                    $bet='betMap-sicbo-127';
                    $rate=6;
                }elseif($playtype==87){
                    $bet='betMap-sicbo-128';
                    $rate=6;
                }elseif($playtype==88){
                    $bet='betMap-sicbo-129';
                    $rate=8;
                }elseif($playtype==89){
                    $bet='betMap-sicbo-130';
                    $rate=12;
                }elseif($playtype==90){
                    $bet='betMap-sicbo-131';
                    $rate=14;
                }elseif($playtype==91){
                    $bet='betMap-sicbo-132';
                    $rate=18;
                }elseif($playtype==92){
                    $bet='betMap-sicbo-133';
                    $rate=50;
                }
                break;
            case 'ROU':
                if($playtype==101){
                    $bet='judgeResult-rouletteWheel-200';
                    $rate=35;
                }elseif($playtype==102){
                    $bet='judgeResult-rouletteWheel-201';
                    $rate=17;
                }elseif($playtype==103){
                    $bet='judgeResult-rouletteWheel-202';
                    $rate=11;
                }elseif($playtype==104){
                    $bet='judgeResult-rouletteWheel-204';
                    $rate=11;
                }elseif($playtype==105){
                    $bet='judgeResult-rouletteWheel-205';
                    $rate=8;
                }elseif($playtype==106){
                    $bet='judgeResult-rouletteWheel-203';
                    $rate=8;
                }elseif($playtype==107){
                    $bet='judgeResult-rouletteWheel-2071';
                    $rate=2;
                }elseif($playtype==108){
                    $bet='judgeResult-rouletteWheel-2072';
                    $rate=2;
                }elseif($playtype==109){
                    $bet='judgeResult-rouletteWheel-2073';
                    $rate=2;
                }elseif($playtype==110){
                    $bet='judgeResult-rouletteWheel-206';
                    $rate=5;
                }elseif($playtype==111){
                    $bet='judgeResult-rouletteWheel-2081';
                    $rate=2;
                }elseif($playtype==112){
                    $bet='judgeResult-rouletteWheel-2082';
                    $rate=2;
                }elseif($playtype==113){
                    $bet='judgeResult-rouletteWheel-2083';
                    $rate=2;
                }elseif($playtype==114){
                    $bet='judgeResult-rouletteWheel-209';
                    $rate=1;
                }elseif($playtype==115){
                    $bet='judgeResult-rouletteWheel-210';
                    $rate=1;
                }elseif($playtype==116){
                    $bet='judgeResult-rouletteWheel-213';
                    $rate=1;
                }elseif($playtype==117){
                    $bet='judgeResult-rouletteWheel-214';
                    $rate=1;
                }elseif($playtype==118){
                    $bet='judgeResult-rouletteWheel-211';
                    $rate=1;
                }elseif($playtype==119){
                    $bet='judgeResult-rouletteWheel-212';
                    $rate=1;
                }
                break;
            case 'BJ':
                if($playtype==220){
                    $bet='judgeResult-blackjack-220';
                }elseif($playtype==221){
                    $bet='judgeResult-blackjack-221';
                }elseif($playtype==222){
                    $bet='judgeResult-blackjack-222';
                }elseif($playtype==223){
                    $bet='judgeResult-blackjack-223';
                }elseif($playtype==224){
                    $bet='judgeResult-blackjack-224';
                }elseif($playtype==225){
                    $bet='judgeResult-blackjack-225';
                }elseif($playtype==226){
                    $bet='judgeResult-blackjack-226';
                }elseif($playtype==227){
                    $bet='judgeResult-blackjack-227';
                }elseif($playtype==228){
                    $bet='judgeResult-blackjack-228';
                }elseif($playtype==229){
                    $bet='judgeResult-blackjack-229';
                }elseif($playtype==230){
                    $bet='judgeResult-blackjack-230';
                }elseif($playtype==231){
                    $bet='judgeResult-blackjack-231';
                }elseif($playtype==232){
                    $bet='judgeResult-blackjack-232';
                }elseif($playtype==233){
                    $bet='judgeResult-blackjack-233';
                }
                break;
            case 'NN':
                if($playtype==207){
                    $bet='judgeResult-nn-207';
                }elseif($playtype==208){
                    $bet='judgeResult-nn-208';
                }elseif($playtype==209){
                    $bet='judgeResult-nn-209';
                }elseif($playtype==210){
                    $bet='judgeResult-nn-210';
                }elseif($playtype==211){
                    $bet='judgeResult-nn-211';
                }elseif($playtype==212){
                    $bet='judgeResult-nn-212';
                }elseif($playtype==213){
                    $bet='judgeResult-nn-213';
                }elseif($playtype==214){
                    $bet='judgeResult-nn-214';
                }elseif($playtype==215){
                    $bet='judgeResult-nn-215';
                }elseif($playtype==216){
                    $bet='judgeResult-nn-216';
                }elseif($playtype==217){
                    $bet='judgeResult-nn-217';
                }elseif($playtype==218){
                    $bet='judgeResult-nn-218';
                }
                break;

            case 'ULPK':
                if($playtype==180){
                    $bet='judgeResult-holdem-180';
                }elseif($playtype==181){
                    $bet='judgeResult-holdem-181';
                }elseif($playtype==182){
                    $bet='judgeResult-holdem-182';
                }elseif($playtype==183){
                    $bet='judgeResult-holdem-183';
                }elseif($playtype==184){
                    $bet='judgeResult-holdem-184';
                }
                break;

            case 'FT':
                if($playtype>=130 && $playtype<=167){
                    $bet='judgeResult-ft-'.$playtype;
                }
                break;
            case '27':
            case '24':
            case '13':
            case '25':
            case '26':
            case '29':
            case '23':
                //keno,lottery
                break;

            case 'ZJH':
                if($playtype==260){
                    $bet='dragon';
                }elseif($playtype==261){
                    $bet='Phoenix';
                }elseif($playtype==262){
                    $bet='judgeResult-winThreeCards-262';
                }elseif($playtype==263){
                    $bet='judgeResult-winThreeCards-263';
                }elseif($playtype==264){
                    $bet='judgeResult-winThreeCards-264';
                }elseif($playtype==265){
                    $bet='judgeResult-winThreeCards-265';
                }elseif($playtype==266){
                    $bet='judgeResult-winThreeCards-266';
                }
                break;

        }
        return ['bet'=>$bet, 'rate'=>$rate, 'bet_detail'=>$bet_detail];

    }

    public function getGameDescriptionInfo($row, $unknownGame)
    {
        $externalGameId = $row->game_code;
        $extra = array('game_code' => $row->game_code);

        return $this->processUnknownGame(
            $row->game_description_id, $row->game_type_id,
            $row->game, $row->game_type, $externalGameId, $extra,
            $unknownGame);
    }

    /**
     * Abstract in Parent Class
     * Generate API URL
     * TODO right trim or left trim the url, to prevent double slash in URL
     * 
     * @param string $apiName
     * @param array $params
     * 
     * @return
     */
    public function generateUrl($apiName,$params)
    {
        $apiUri = self::URI_MAP[$apiName];

        if($apiName == "createPlayerGameSession"){
            $url = $this->gameSessionUrl . $apiUri . '?' . http_build_query($params);
        }else{
            $url = $this->apiUrl . $apiUri . '?' . $this->generateUrlGetParam($params);
        }

        $this->logThis(__METHOD__ .' url >>>>>>>>',$url);

        return $url;
    }

    /** 
     * Create Player to Game Provider or in our Database
     * 
     * TODO: other requirements
     * 
     * @param string $playerName
     * @param int $playerId
     * @param string $password
     * @param string $email
     * @param array $extra
     * 
     * @return mixed
    */
    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        // create player in Database
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        // trait GameApiExtraInfoManipulationsTrait@gameCurrency
        $currencyType = $this->gameCurrency();

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'gameUserName' => $gameUsername,
            'playerId' => $playerId
        ];

        $params = [
            'cagent' => $this->cAgent,
            'loginname' => $gameUsername, // TODO
            'method' => 'lg',
            'actype' => 1, // TODO
            'password' => $password, // TODO
            'oddtype' => 'A', // TODO
            'cur' => $currencyType,
        ];

        $this->logThis(__METHOD__ .' params >>>>>>>>',$params);

        return $this->callApi(self::API_createPlayer,$params,$context);
    }

    /**
     * Process the createPlayer method
     * 
     * @param array $params
     * 
     * @return array
     */
    public function processResultForCreatePlayer($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $gameUserName = $this->getVariableFromContext($params,'gameUserName');
        $playerId = $this->getVariableFromContext($params,'playerId');

        $success = $this->processResultBoolean($responseResultId,$resultXml,array('key_error', 'network_error', 'account_add_fail', 'error'), true,self::API_createPlayer);

        $result['exists'] = false;

        if($success){
            // update flag to registered = true
            $this->updateRegisterFlag($playerId,Abstract_game_api::FLAG_TRUE);
            $result['exists'] = true;
            

        }

        return [
            $success,
            $result
        ];
    }

    /** 
     * Create A Game Session for Player before launching game
     * 
     * @param string $productID
     * @param string $username
     * @param string $session_token
     * @param double $credit
    */
    public function createPlayerGameSession($productID,$username,$session_token,$credit)
    {
        //return [$productID,$username,$session_token,$credit];
        $gameUsername = $this->getGameUsernameByPlayerUsername($username);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayerGameSession',
            'gameUsername' => $gameUsername
        ];

        $params = [
            'productid' => $productID,
            'username' => $gameUsername,
            'session_token' => $session_token,
            'credit' => $credit
        ];

        $this->logThis(__METHOD__ .' params >>>>>>>>',$params);

        return $this->callApi(self::API_createPlayerGameSession,$params,$context);
    }

    /**
     * Process The API result of method processResultForCreatePlayerGameSession
     * 
     * @param array $params
     * 
     * @return
     */
    public function processResultForCreatePlayerGameSession($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml),true);

        $result = [
            'response_result_id' => $responseResultId,
            'result_array' => $resultArr
        ];

        return [
            true,
            $result
        ];
    }

    /** 
     * Change the password of player in our SBE
     * 
     * Important Note: since game provider do not see the password of player, we cannot apply this method to change the passwword of player in our DB, because if we do that we cannot recover password of player in game provider
     * 
     * @param string $playerName
     * @param string $oldPassword
     * @param string $newPassword
     * 
     * @return array
    */
    public function changePassword($playerName, $oldPassword, $newPassword)
    {
        return $this->returnUnimplemented();
    }

    /** ######## UTILS TODO move this to trait########*/

    /**
     * Return the Currency of Game, base in Extra Info or within Class
     * 
     * @return string
     */
    public function gameCurrency()
    {
        if(! empty($this->currencyTypeInExtraInfo)){
            return $this->currencyTypeInExtraInfo;
        }elseif(! empty($this->currencyType)){
            return $this->currencyType;
        }

        return $this->defaultGameCurrency;
    }

    /** 
     * Log certain Information in terminal
     * TODO make this accept multiple log
     * 
     * @param string $logMessage
     * @param string $logType
     * @param array $logValue
     * 
     * @return string
    */
    public function logThis($logMessage='',$logValue=[],$logType='debug_log')
    {
        $this->loadModel(['external_system']);

        $platformName = $this->CI->external_system->getSystemName($this->getPlatformCode());
        $logMessage = $platformName. ' ' .$logMessage;

        return $this->CI->utils->$logType($logMessage,$logValue);
    }

    /**
     * Load Model
     * 
     * @param array $model
     * 
     * @return void
     */
    public function loadModel(array $model)
    {
        return $this->CI->load->model($model);
    }

    /**
     * Generate URL GET param
     * 
     * @param array $params
     * 
     * @return string
     */
    public function generateUrlGetParam($params)
    {
        $getParam = '';

        foreach($params as $key => $value){
            $getParam .= $key . '=' . $value . '/\\\/';
        }

        $getParamRTrim = rtrim($getParam,'/\\\/');

        $this->CI->load->library(array('salt'));

        $desEncrypted = $this->CI->salt->encrypt($getParamRTrim,$this->desEncryptionKey);
        $md5Eencrypted = md5($desEncrypted . $this->md5EncryptionKey);

        return 'params='.$desEncrypted.'&key='.$md5Eencrypted;
    }

    /**
     * Process The response of Game Provider if true or false, true = success,false = error
     * 
     * @return boolean
     */
    public function processResultBoolean($responseResultId,$resultXml,$errArr, $info_must_be_0=false,$api=null)
    {
        $success = true;

        if($api == self::API_createPlayer){
            $info = $this->getAttrValueFromXml($resultXml, 'info');
            if (in_array($info, $errArr)) {
                $this->setResponseResultToError($responseResultId);
                $this->CI->utils->debug_log('AG got error', $responseResultId, 'result', $resultXml);
                $success = false;
            }elseif($info_must_be_0){
                $success= $info==self::AG_CODE_SUCCESS;
            }
        }else{
            # add API here in the future that needs this method
            $success = false;
        }

        return $success;

    }

    public function getAttrValueFromXml($resultXml, $attrName)
    {
        $info = null;
        if (!empty($resultXml)) {
            $result = $resultXml->xpath('/result');
            if (isset($result[0])) {
                $attr = $result[0]->attributes();
                if (!empty($attr)) {
                    foreach ($attr as $key => $value) {
                        if ($key == $attrName) {
                            $info = ''.$value;
                        }
                        $this->CI->utils->debug_log('key', $key, 'value', ''.$value);
                    }
                } else {
                    $this->CI->utils->debug_log('empty attr');
                }
            } else {
                $this->CI->utils->debug_log('empty /result');
            }
        } else {
            $this->CI->utils->debug_log('empty xml');
        }

        return $info;
    }

    /** 
     * Check if token is valid
     * 
     * @param string $token the token to validate
     * 
     * @return boolean
    */
    public function isTokenValid($token)
    {
        $playerInfo = parent::getPlayerInfoByToken($token);

        if(empty($playerInfo)){
            return false;
        }

        return true;
    }

    /**
     * Get Player Balance in this subwallet
     * 
     * @param int $playerId the player id
     * @param int|null $gameProviderId
     * 
     * @return mixed
     */
    public function getPlayerSubWalletBalance($playerId,$gameProviderId=null)
    {
        
        if(empty($playerId)){
            return null;
        }
        
        if(is_null($gameProviderId)){
            $gameProviderId = $this->getPlatformCode();
        }

        $this->CI->load->model('player_model');

        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId,$gameProviderId);

        return $balance;
    }

    /**
     * Get player information by valid token
     * 
     * @param string $token
     * 
     * @return mixed
     */
    public function getPlayerInfoBasedInToken($token)
    {
        if(! $this->isTokenValid($token)){
            return null;
        }

        return parent::getPlayerInfoByToken($token);
    }

    public function getLauncherLanguage($currentLang) 
    {
       switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
                $language = 1;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id":
                $language = 11;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi":
                $language = 8;
                break;
            case "en-us":
                $language = 3;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "th":
                $language = 6;
                break;
            case LANGUAGE_FUNCTION::INT_LANG_PORTUGUESE:
            case "pt":
            case "prt":
                $language = 23;
                break;
            default:
                $language = 3;
                break;
        }
        return $language;
 }

 public function processTransactions(&$transactions){
     $temp_game_records = [];
   
     if(!empty($transactions)){
         foreach($transactions as $transaction){
             
             $temp_game_record = [];
             $temp_game_record['player_id'] = $transaction['player_id'];
             $temp_game_record['game_platform_id'] = $this->getPlatformCode();
             $temp_game_record['transaction_date'] =  $this->gameTimeToServerTime($transaction['transaction_date']);                
             $temp_game_record['amount'] = abs($transaction['amount']);             
             $temp_game_record['before_balance'] = $transaction['before_balance'];
             $temp_game_record['after_balance'] = $transaction['after_balance'];
             $temp_game_record['round_no'] = $transaction['round_no'];
             $extra_info = [];
             $extra=[];
             $extra['trans_type'] = $transaction['trans_type'];
             $extra['extra'] = $extra_info;
             $temp_game_record['extra_info'] = json_encode($extra);
             $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

             $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
             if(in_array($transaction['trans_type'], ['bet'])){
                 $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
             }
             
             $temp_game_records[] = $temp_game_record;
             unset($temp_game_record);
         }
     }

     $transactions = $temp_game_records;
 }

}//end of class