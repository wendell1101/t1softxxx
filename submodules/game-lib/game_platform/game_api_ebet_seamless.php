<?php
/**
 * eBet Seamless game integration
 * OGP-26861
 *
 * @author  Kristallynn Tolentino
 *
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';

class Game_api_ebet_seamless extends Abstract_game_api {

    const POST = 'POST';
    const GET = 'GET';

    const SUCCESS = 200;

    const FLAG_UPDATED = 1;
    const FLAG_NOT_UPDATED = 0;

    const QUERY_FORWARD = "queryForwardGame";

    const URI_SLOTS = "/api/loginslot";
    const URI_LIVE = "/api/launchUrl";
    const URI_MINI_GAMES = "/api/loginTableGame";

    const URI_MAP = [
        self::API_createPlayer => "/api/syncuser",
        self::API_logout => '/api/logout',
        self::API_queryForwardGame => '/api/launchUrl',
		self::API_queryDemoGame => '/api/demo',
        // self::API_syncGameRecords => "/api/usertransaction",
        // self::API_queryGameListFromGameProvider => '/seamless/games',
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'status',
        'updated_at'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'result_amount',
    ];

    const GAME_TYPE_LIVE = "live_dealer";
    const GAME_TYPE_SLOTS = "slots";
    const GAME_TYPE_MINI_GAMES = "mini_games";

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_CANCEL = 'cancel';

    public $product_id;
    public $channel_id;
    public $applink_prefix;
    public $original_transaction_table_name;
    public $redirect_url;

    public $slots_provider_id = "genesis";

    public function __construct() {
        parent::__construct();

        $this->api_url = $this->getSystemInfo('url', 'http://j106sambabetbrl.ebet.im:8888');
        $this->lang = $this->getSystemInfo('lang');

        $this->currency = $this->getSystemInfo('currency', 'BRL');
        $this->channel_id = $this->getSystemInfo("channel_id", 3396);
        $this->sub_channel = $this->getSystemInfo("sub_channel", "J106Sambabet_BRL");
        $this->applink_prefix = $this->getSystemInfo("applink_prefix", "j106sambabetbrl");
        $this->pub_key = $this->getSystemInfo("pub_key", "MFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBAKYOU7xys05E6hctm/8LX8HuJOT5J6thDWwY+A6kV+0ZRvyl0RPIS+SUHd4zzNnBJlrt2tTfbvbl8iA4r/C1T48CAwEAAQ==");
        $this->priv_key = $this->getSystemInfo("priv_key", "MIIBUwIBADANBgkqhkiG9w0BAQEFAASCAT0wggE5AgEAAkEApg5TvHKzTkTqFy2b/wtfwe4k5Pknq2ENbBj4DqRX7RlG/KXRE8hL5JQd3jPM2cEmWu3a1N9u9uXyIDiv8LVPjwIDAQABAkAWfWJB55b5RsQdl4PFKxkw/rvodwY0Y9SZi1gtQ3zVE5rqI/TYs7dF20wgIS4iXeizXvXfw3exNk7NjhcR9rahAiEA7dkPFThbHC0ayZp9IwDx2X6FzmDlqa/hdAS5yzlfIs0CIQCyuqMbekViqebe89d6VCLaYTQ2e8gtqkqFJhVuAG2TywIgDSQyDiUX+52OXlc31MhHlJHGCNoXtmFuXn+oWE8qL30CIDx1R+FunfP/FxrKD1TRCy0l/nyDqLZRyX164XrhaC+7AiBSRX9OoYLYnxf13sUNFTeHZlcgIljQhWXYMms6MnSElA==");
        $this->redirect_url = $this->getSystemInfo('redirect_url');

        $this->original_transaction_table_name = 'ebet_seamless_wallet_transactions';

        # init RSA
        $this->rsa = new Crypt_RSA();
        $this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $this->rsa->loadKey($this->priv_key);
        $this->rsa->setHash("md5");
    }

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode() {
        return EBET_SEAMLESS_GAME_API;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function generateUrl($apiName, $params) {

        /* if($apiName == self::QUERY_FORWARD){

            if($params['actions']['login_type'] == self::GAME_TYPE_SLOTS){
                $uri = self::URI_SLOTS;
            }else{
                $uri = self::URI_LIVE;
            }
            // else{
                // $uri = self::URI_MINI_GAMES;
            // }
        }else{
            $uri = self::URI_MAP[$apiName];
        } */
        $uri = self::URI_MAP[$apiName];
        $url = $this->api_url . $uri;

        if ($params["actions"]["method"] == self::GET) {
            $url .= '?' . http_build_query($params["main_params"]);
        }

        return $url;
    }

    protected function customHttpCall($ch, $params) {
        if($params["actions"]["method"] == self::POST)
        {
            $function = $params["actions"]['function'];

            unset($params["actions"]);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, TRUE);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params["main_params"]));

        }

	}

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForCreatePlayer',
			'playerName' => $playerName,
			'playerId' => $playerId,
			'gameUsername' => $gameUsername
		);

        //Signature. String splicing :username
        $signature = $this->rsa->sign($gameUsername);

        $main_params = [
            "username" => $gameUsername,
            "channelId" => $this->channel_id,
            "signature" => base64_encode($signature),
            "currency" => $this->currency
        ];

        $params = array(
            "main_params" => $main_params ,
            "actions" => [
                "function" => self::API_createPlayer,
                "method" => self::POST
            ]
        );

		return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success
        );

        if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array($success, $result);
	}

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        $this->CI->utils->debug_log('EBET: (' . __FUNCTION__ . ')', 'PARAMS:', $playerName, 'RESULT:', $result);

        return $result;
    }

    public function depositToGame($userName, $amount, $transfer_secure_id = null){
        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id = null){
        return array(
            'success' => true,
            'external_transaction_id' => $transfer_secure_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function queryForwardGame($playerName,$extra=[]) {
        $game_type = !empty($extra['game_type']) ? $extra['game_type'] : null;
        $game_code = !empty($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = !empty($extra['game_mode']) ? $extra['game_mode'] : null;
        $is_demo_mode = $this->utils->isDemoMode($game_mode);

        switch ($game_type) {
            case self::GAME_TYPE_LIVE_DEALER:
                $category = 'Live';
                break;
            case self::GAME_TYPE_LOTTERY:
                $category = 'Lottery';
                break;
            case self::GAME_TYPE_FISHING_GAME:
                $category = 'Fishing';
                break;
            case self::GAME_TYPE_SLOTS:
                $category = 'Slot';
                break;
            case self::GAME_TYPE_SPORTS:
            case self::GAME_TYPE_E_SPORTS:
                $category = 'Sportbook';
                break;
            default:
                $category = 'Live';
                break;
        }

        $return = array(
            "success" => false,
            "url" => "",
        );

        $language = $this->getLauncherLanguage($this->lang);

        // if($extra['game_type'] == self::GAME_TYPE_MINI_GAMES){
        //     $result = $this->loginViaMiniGame($playerName, $extra);

        //     if(isset($result['success']) && $result['success']==true){
        //         $return = array(
        //             "success" => true,
        //             "url"=> $result['results']['url']
        //         );
        //     }
        // }else
        /* if($extra['game_type'] == self::GAME_TYPE_SLOTS){
            if($extra['game_mode']=="real"){
                $result = $this->loginViaSlots($playerName, $extra);

                if(isset($result['success']) && $result['success']==true){
                    $return = array(
                        "success" => true,
                        "url"=> $result['results']['slotURL']
                    );

                }
            }else{
                $result = $this->loginViaH5Link($playerName, $extra);

                if(isset($result['success']) && $result['success']){
                    $return = array(
                        "success" => true,
                        "url" => $result['results']['launchUrl']."&username=".$result['username']."&accessToken=".$result['accessToken']."&language=".$language."&mode=trial",
                    );
                }
            }

        } */

        if (!empty($game_code) && $game_code != '_null') {
            if (($game_type == self::GAME_TYPE_SPORTS || $game_type == self::GAME_TYPE_E_SPORTS) && $game_code != 'WEB_GAME') {
                $params['gameCode'] = $extra['game_code'];
            }

            $params['tableCode'] = $extra['game_code'];
        }

        if (!empty($game_type)) {
            $params['category'] = $category;
        }

        $params['language'] = $language;

        $redirect_url_params = $params;
        unset($redirect_url_params['gameCode'], $redirect_url_params['tableCode']);

        if ($is_demo_mode) {
            $result = $this->queryGameDemohUrl($playerName, $extra);

            if (isset($result['success']) && $result['success']) {
                $redirect_url = $result['results']['launchUrl'] . '?' . http_build_query($redirect_url_params);
                $params['redirecturl'] = !empty($this->redirect_url) ? $this->redirect_url : $redirect_url;

                $return = [
                    'success' => true,
                    'url' => $result['results']['launchUrl'] . '?' . http_build_query($params),
                ];
            }
        } else {
            $result = $this->loginViaH5Link($playerName, $extra);

            if (isset($result['success']) && $result['success']) {
                $params['username'] = $result['username'];
                $params['accessToken'] = $result['accessToken'];
                $redirect_url = $result['results']['launchUrl'] . '?' . http_build_query($redirect_url_params);
                $params['redirecturl'] = !empty($this->redirect_url) ? $this->redirect_url : $redirect_url;

                $return = [
                    'success' => true,
                    'url' => $result['results']['launchUrl'] . '?' . http_build_query($params),
                ];
            }
        }

        /* $result = $this->loginViaH5Link($playerName, $extra);

        if(isset($result['success']) && $result['success']){
            if(isset($result['tableCode']) && $result['tableCode']!='' && $result['tableCode']!='_null'){
                if($extra['game_mode']=="real"){
                    $return = array(
                        "success" => true,
                        "url" => $result['results']['launchUrl']."&username=".$result['username']."&accessToken=".$result['accessToken']."&tableCode=".$result['tableCode']."&language=".$language,
                    );
                }else{
                    $return = array(
                        "success" => true,
                        "url" => $result['results']['launchUrl']."&username=".$result['username']."&accessToken=".$result['accessToken']."&tableCode=".$result['tableCode']."&language=".$language."&mode=trial",
                    );
                }

            }else{
                if($extra['game_mode']=="real"){
                    $return = array(
                        "success" => true,
                        "url" => $result['results']['launchUrl']."&username=".$result['username']."&accessToken=".$result['accessToken']."&language=".$language,
                    );
                }else{
                    $return = array(
                        "success" => true,
                        "url" => $result['results']['launchUrl']."&username=".$result['username']."&accessToken=".$result['accessToken']."&language=".$language."&mode=trial",
                    );
                }
            }
        } */

        return $return;

    }

    public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en-us':
            case 'en_us':
            case 'EN':
                $lang = 'en_us'; #english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh-cn':
            case 'zh_cn':
            case 'ZH':
                $lang = 'zh_cn'; #chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id-id':
            case 'in_id':
            case 'ID':
                $lang = 'in_id';
                break;
            case 'ms_my':
            case 'MS':
                $lang = 'ms_my'; #malay
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi-vn':
            case 'vi_vn':
            case 'VI':
                $lang = 'vi_vn'; #vietnamese
                break;
            case 'ja_jp':
            case 'JA':
                $lang = 'ja_jp'; #japanese
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
                $lang = 'ko_KR';
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
            case 'th_th':
            case 'TH':
                $lang = 'th_th';
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt-pt':
            case 'pt_pt':
            case 'PT':
                $lang = 'pt_pt';
                break;
            case 'tr':
            case 'tr-tr':
                $lang = 'tr_tr';
                break;
            case 'es':
            case 'es-es':
                $lang = 'es_es';
                break;
            default:
                $lang = 'en_us'; // default as english
                break;
        }
        return $lang;
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $apiName = '') {
        $success = false;

        $this->CI->utils->debug_log('EBET_SEAMLESS: (' . __FUNCTION__ . ')', $resultArr);

        if($resultArr['status'] == self::SUCCESS){
            $success = true;
        }else{
            $this->setResponseResultToError($responseResultId);
        }

        return $success;
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    ////////////////////////////////////////////

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

        $sqlTime='transaction.updated_at >= ? and transaction.updated_at <= ?';

        if($use_bet_time) {
            $sqlTime='transaction.start_at >= ? and transaction.start_at <= ?';
        }
        $sql = <<<EOD
SELECT
    transaction.id as sync_index,
    transaction.bet_amount as bet_amount,
    transaction.amount as result_amount,
    transaction.before_balance,
    transaction.after_balance,
    transaction.status,
    transaction.start_at,
    transaction.end_at,
    transaction.transaction_type,
    transaction.game_id,

    transaction.external_unique_id as external_uniqueid,
    transaction.updated_at,
    transaction.response_result_id,
    transaction.round_id,
    transaction.md5_sum,

    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.game_code as game_code,
    game_description.game_type_id
FROM
    {$this->original_transaction_table_name} as transaction
    LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON transaction.player_id = game_provider_auth.player_id and game_provider_auth.game_provider_id = ?
WHERE
transaction_type != "refunded" and transaction_type != "cancelled" and {$sqlTime} and transaction.game_platform_id = ?

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
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

        if(empty($row['md5_sum']))
        {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
		}

        $bet_amount = isset($row['bet_amount']) ? abs($row['bet_amount']) : 0;
        $data = [
            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => null,
                'game'                  => isset($row['game_description_name']) ? $row['game_description_name'] : null,
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $bet_amount,
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $bet_amount,
                'real_betting_amount'   => $bet_amount,
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => $row['after_balance'],
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['end_at'],
                'bet_at'                => $row['start_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => "",
            'extra' => [],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->utils->debug_log('EBET_SEAMLESS ', $data);
        return $data;

    }

    /**
    *
    * perpare original rows, include process unknown game, pack bet details, convert game status
    *
    * @param  array &$row
    */
    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }
        switch($row['status']) {
            case 'ok':
            case 'SETTLED':
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            case 'REFUNDED':
                $row['note'] = 'Refund';
                $row['status'] = Game_logs::STATUS_REFUND;
                break;
            case 'CANCELLED':
                $row['note'] = 'Cancelled';
                $row['status'] = Game_logs::STATUS_CANCELLED;
                break;
            default:
                $row['status'] = Game_logs::STATUS_PENDING;
                break;
        }
    }

    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $game_description_id=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game_id'], $row['game_id']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }


    public function timestamps_milliseconds() {
        $date = new DateTimeImmutable();
        $timestampMs = (int) ($date->getTimestamp() . $date->format('v'));
        return $timestampMs;
    }

    public function loginViaSlots($playerName, $extra = null)
    {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPassword($playerName);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $token = $this->getPlayerToken($playerId);

    	$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLoginViaSlots',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername
		);

        $timestamp = $this->timestamps_milliseconds();

        //Signature. String splicing :username+timestamp
        //Note: timestamp can be used alone if no username
        $signature = $this->rsa->sign($gameUsername.$timestamp);

        $language = (isset($extra['language']) && $extra['language']!="_null") ? $extra['language'] : $this->lang;

        $main_params = [
            "username" => $gameUsername,
            "channelId" => $this->channel_id,
            "timestamp" => $timestamp,
            "signature" => base64_encode($signature),
            "gameID" => $extra['game_code'],
            "providerId" => $extra['provider_id'],
            "loginEventType" => 4, //1 -Normal Login , 4 - Token login
            "currency" => $this->currency,
            "pwd" => $password['password'],
            "token" => $token,
            "ip" => $this->CI->input->ip_address(),
            "language" => $this->getLauncherLanguage($language)
        ];

        $params = array(
            "main_params" => $main_params ,
            "actions" => [
                "function" => self::API_queryForwardGame,
                "method" => self::POST,
                "login_type" => $extra['game_type']
            ]
        );

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

    public function processResultForLoginViaSlots($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success,
            "results" => $resultArr
        );

        return array($success, $result);
	}

    public function loginViaMiniGame($playerName, $extra = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPassword($playerName);

    	$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForloginViaMiniGame',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername
		);

        $timestamp = $this->timestamps_milliseconds();

        //Signature. String splicing :username+timestamp
        //Note: timestamp can be used alone if no username
        $signature = $this->rsa->sign($gameUsername.$timestamp);

        $language = isset($extra['language']) ? $extra['language'] : $this->lang;

        $main_params = [
            "username" => $gameUsername,
            "channelId" => $this->channel_id,
            "timestamp" => $timestamp,
            "signature" => base64_encode($signature),
            "gameID" => $extra['game_code'],
            "providerId" => "",
            "loginEventType" => 1, //1 -Normal Login , 4 - Token login
            "currency" => $this->currency,
            "pwd" => $password,
            "ip" => $this->CI->input->ip_address(),
            "language" => $this->getLauncherLanguage($language)
        ];

        $params = array(
            "main_params" => $main_params ,
            "actions" => [
                "function" => self::API_queryForwardGame,
                "method" => self::POST,
                "login_type" => $extra['game_type']
            ]
        );

		return $this->callApi(self::API_queryForwardGame, $params, $context);
	}

    public function processResultForloginViaMiniGame($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success,
            "results" => $resultArr
        );

        return array($success, $result);
	}

    public function loginViaH5Link($playerName, $extra = null){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $token = $this->getPlayerToken($playerId);

    	$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForLoginViaH5Link',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername
		);

        $timestamp = $this->timestamps_milliseconds();

        //Signature. String splicing :channelId+timestamp
        $signature = $this->rsa->sign($this->channel_id.$timestamp);

        if(isset($extra['game_code'])){
            $main_params = [
                "channelId" => $this->channel_id,
                "timestamp" => $timestamp,
                "signature" => base64_encode($signature),
                "currency" => $this->currency,
                "tableCode" => $extra['game_code'],
                "username" => $gameUsername,
                "accessToken" => $token
            ];
        }else{
            $main_params = [
                "channelId" => $this->channel_id,
                "timestamp" => $timestamp,
                "signature" => base64_encode($signature),
                "currency" => $this->currency,
                "username" => $gameUsername,
                "accessToken" => $token
            ];
        }

        $params = array(
            "main_params" => $main_params,
            "actions" => [
                "function" => self::API_queryForwardGame,
                "method" => self::POST,
                "login_type" => $extra['game_type']
            ]
        );

		return $this->callApi(self::API_queryForwardGame, $params, $context);

    }

    public function processResultForLoginViaH5Link($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success,
            "results" => $resultArr,
            "tableCode" => $params['params']['main_params']['tableCode'],
            "username" => $params['params']['main_params']['username'],
            "accessToken" => $params['params']['main_params']['accessToken']
        );

        return array($success, $result);
	}

    public function queryGameDemohUrl($playerName, $extra = null){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $token = $this->getPlayerToken($playerId);

    	$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForQueryGameDemohUrl',
			'playerName' => $playerName,
            'gameUsername' => $gameUsername
		);

        $timestamp = $this->timestamps_milliseconds();

        //Signature. String splicing :channelId+timestamp
        $signature = $this->rsa->sign($this->channel_id.$timestamp);

        if(isset($extra['game_code'])){
            $main_params = [
                "channelId" => $this->channel_id,
                "timestamp" => $timestamp,
                "signature" => base64_encode($signature),
                "currency" => $this->currency,
                "tableCode" => $extra['game_code'],
            ];
        }else{
            $main_params = [
                "channelId" => $this->channel_id,
                "timestamp" => $timestamp,
                "signature" => base64_encode($signature),
                "currency" => $this->currency,
            ];
        }

        $params = array(
            "main_params" => $main_params,
            "actions" => [
                "function" => self::API_queryDemoGame,
                "method" => self::POST,
                "login_type" => $extra['game_type']
            ]
        );

		return $this->callApi(self::API_queryDemoGame, $params, $context);

    }

    public function processResultForQueryGameDemohUrl($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $result = array(
            "response_result_id" => $responseResultId,
            "success" => $success,
            "results" => $resultArr,
            "tableCode" => $params['params']['main_params']['tableCode'],
        );

        return array($success, $result);
	}

    public function queryTransactionByDateTime($startDate, $endDate){

        $sql = <<<EOD
SELECT
t.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_unique_id as external_uniqueid,
t.transaction_type trans_type,
t.extra_info extra_info
FROM {$this->original_transaction_table_name} as t
WHERE t.game_platform_id = ? and `t`.`updated_at` >= ? AND `t`.`updated_at` <= ?
ORDER BY t.updated_at asc;

EOD;

        $params=[$this->getPlatformCode(),$startDate, $endDate];

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
                    $extra_info = @json_decode($transaction['extra_info'], true);
                    $extra=[];
                    $extra['trans_type'] = $transaction['trans_type'];
                    $extra['extra'] = $extra_info;
                    $temp_game_record['extra_info'] = json_encode($extra);
                    $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                    if($transaction['amount'] < 0){
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                    }else{
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                    }

                    $temp_game_records[] = $temp_game_record;
                    unset($temp_game_record);
                }
            }

            $transactions = $temp_game_records;

            $this->CI->utils->debug_log('TALLYNN: (' . __FUNCTION__ . ')', 'transactions:', $transactions);
        }
}
