<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
* Game Provider: HO Gaming
* Game Type: Live Casino
* Wallet Type: Seamless
*
/**
* API NAME: HOGAMING_SEAMLESS_API
*
* @category game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @emmanuel.php.ph
**/
abstract class Abstract_game_api_common_hogaming_seamless extends Abstract_game_api
{
    const API_NAME = 'HOGAMING SEAMLESS API';

    // Methods.
    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';
    const METHOD_PUT = 'PUT';

    // User account mode.
    const MODE_FUN = 0;
    const MODE_REAL = 1;

    const COMMON_WALLET = 'true';

    // Direct callable game type IDs.
    const GAMETYPE_ROULLETE = '0000000000000001';
    const GAMETYPE_BLACKJACK = '0000000000000003';
    const GAMETYPE_BACCARAT = '0000000000000004';
    const GAMETYPE_SICBO = '0000000000000012';
    const GAMETYPE_DRAGONTIGER = '0000000000000500';

    // Table games: game type ID list.
    const TABLEGAMES_GAMETYPE_IDS = array(
        'roullete' => self::GAMETYPE_ROULLETE,
        'blackjack' => self::GAMETYPE_BLACKJACK,
        'baccarat' => self::GAMETYPE_BACCARAT,
        'sicbo' => self::GAMETYPE_SICBO,
        'dragontiger' => self::GAMETYPE_DRAGONTIGER
    );

    // Direct callable bet type IDs.
    const BETTYPE_PRIVATE = '0';
    const BETTYPE_REGULAR = '1';
    const BETTYPE_HIGHROLLER = '2';
    const BETTYPE_VIP = '3';
    const BETTYPE_LOWROLLER = '4';
    const BETTYPE_AGENT1 = '5';
    const BETTYPE_AGENT2 = '6';

    // Bet type ID list.
    const BETTYPE_IDS = array(
        'private' => self::BETTYPE_PRIVATE,
        'regular' => self::BETTYPE_REGULAR,
        'high_roller' => self::BETTYPE_HIGHROLLER,
        'vip' => self::BETTYPE_VIP,
        'low_roller' => self::BETTYPE_LOWROLLER,
        'agent_1' => self::BETTYPE_AGENT1,
        'agent_2' => self::BETTYPE_AGENT2
    );

    // Version codes.
    const VERSION_V3 = 'V3';
    const VERSION_V4 = 'V4';

    // Lobby version list.
    public static $lobby_versions = array(
        'version_v3' => self::VERSION_V3,
        'version_v4' => self::VERSION_V4
    );

    // Skin IDs.
    const SKIN_001 = 'SKIN001';

    # Fields in game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'bet_start_date',
        'bet_end_date',
        'account_id',
        'table_id',
        'table_name',
        'game_id',
        'bet_id',
        'bet_amount',
        'payout',
        'currency',
        'game_type',
        'bet_spot',
        'bet_no',
        'bet_mode',
        'status'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_ORIGINAL = [
        'bet_amount',
        'payout'
    ];

    # Fields in game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_TRANSACTION=[
        'uname',
        'cur',
        'txnid',
        'gametypeid',
        'txnsubtypeid',
        'gameid',
        'txn_reverse_id',
        'category',
        'operator',
        'provider_id'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_TRANSACTION = [
        'amt',
        'bAmt',
        'before_balance',
        'after_balance'
    ];

    # Fields in game_logs we want to detect changes for merge, and when game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        'bet_amount',
        'round',
        'game_code',
        'game_name',
        'after_balance',
        'valid_bet',
        'result_amount',
        'username',
        'start_at',
        'end_at',
        'bet_at'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'valid_bet',
        'result_amount',
    ];

    const API_syncCancelledGames = "GetBetdetails";
    const API_syncAllCancelledGames = "GetAllbetdetails";
    const API_syncGames = "GetAllBetDetailsPerTimeInterval";

    public static $uri_map = array(
        parent::API_login => '/cgibin/ClientLoginServlet',
        parent::API_createPlayer => '/cgibin/ClientLoginServlet',
        parent::API_queryForwardGame => '/login/visitor/cwlogin.jsp',
        self::API_generateToken => '/api/token/endpoint',
        self::API_syncGameRecords => '/api/betinfo'
    );

    ## RESPONSE RESULT CODES
    const PROCESS_API_HEADER = 'HOGAMINGSEAMLESS_PROCESS_API_HEADER';
    const PROCESS_API_URL = 'HOGAMINGSEAMLESS_PROCESS_API_URL';
    const SAVE_GPA_SESSION = 'HOGAMINGSEAMLESS_SAVE_GPA_SESSION';
    const CREATE_PLAYER = 'HOGAMINGSEAMLESS_CREATE_PLAYER';
    const QUERY_PLAYER_BALANCE = 'HOGAMINGSEAMLESS_QUERY_PLAYER_BALANCE';
    const DEPOSIT = 'HOGAMINGSEAMLESS_DEPOSIT';
    const WITHDRAW = 'HOGAMINGSEAMLESS_WITHDRAW';
    const QUERY_FORWARD_GAME = 'HOGAMINGSEAMLESS_QUERY_FORWARD_GAME';
    const QUERY_FORWARD_GAME_RESPONSE = 'HOGAMINGSEAMLESS_QUERY_FORWARD_GAME_RESPONSE';
    const PROCESS_API_UPDATE_BALANCE = 'PROCESS_API_UPDATE_BALANCE';
    const PROCESS_API_GAME_LIST = 'PROCESS_API_GAME_LIST';
    const PROCESS_API_GAME_LIST_RESPONSE = 'PROCESS_API_GAME_LIST_RESPONSE';
    const PROCESS_API_SYNC_BY_SESSION = 'PROCESS_API_SYNC_BY_SESSION';
    const PROCESS_API_SYNC_BY_SESSION_RESPONSE = 'PROCESS_API_SYNC_BY_SESSION_RESPONSE';
    const PROCESS_API_SYNC_BY_SESSION_DETAILS = 'PROCESS_API_SYNC_BY_SESSION_DETAILS';
    ## --------------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
        $this->currency_type = $this->getSystemInfo('currency', 'THB');
        $this->api_url = $this->getSystemInfo('url', 'https://sexycasinouat.hointeractive.com');
        $this->api_key = $this->getSystemInfo('API_KEY');
        $this->api_secret = $this->getSystemInfo('API_SECRET');
        $this->player_mode = $this->getSystemInfo('player_mode', self::MODE_REAL);
        $this->prefix = $this->getSystemInfo('prefix_for_username', "SXYstg");
        $this->language = strtolower($this->getSystemInfo('language', 'th'));
        $this->lobby_version = strtoupper($this->getSystemInfo('lobby_version'));
        $this->skin_id = strtoupper($this->getSystemInfo('skin_id', self::SKIN_001));

        $this->api_bet_logs = $this->getSystemInfo('api_bet_logs', 'http://webapi-asia.hointeractive.com/Betapi.asmx');
        $this->web_api_username = $this->getSystemInfo('web_api_username', 'Sexy@uat@');
        $this->web_api_password = $this->getSystemInfo('web_api_password', 'zxc456#');
        $this->casino_id = $this->getSystemInfo('casino_id', 's1e2x3ashnxx1uat');

        $this->gameTimeToServerTime = $this->getSystemInfo('gameTimeToServerTime');
        $this->serverTimeToGameTime = $this->getSystemInfo('serverTimeToGameTime');

        $this->use_xml_body = false;
        $this->common_wait_seconds = $this->getSystemInfo('common_wait_seconds', 63);
        $this->action = '';
        $this->is_bet_logs = false;

        $this->add_cancelled_bets_in_original_game_logs = $this->getSystemInfo('add_cancelled_bets_in_original_game_logs', true);
        $this->testuser = $this->getSystemInfo('testuser', 'true');
        $this->use_transaction_table = $this->getSystemInfo('use_transaction_table', false);
        $this->use_new_sync_version = $this->getSystemInfo('use_new_sync_version', true);
        $this->new_api_url = $this->getSystemInfo('new_api_url', 'https://v4webapi.hointeractive.com');
        $this->use_new_api_url = false;

        $this->seamless_debit_transaction_type = $this->getSystemInfo('seamless_debit_transaction_type', ['placebet']);
    }

    public function getPlatformCode()
    {
        return $this->returnUnimplemented();
    }

    public function isSeamLessGame()
    {
        return true;
    }

    private function _setPlayerMode($player_mode)
    {
        $this->CI->utils->debug_log('HogamingSeamless: (' . __FUNCTION__ . ')', 'PARAMS:', $player_mode);

        switch ($player_mode) {
            case 'real':
                $this->player_mode = self::MODE_REAL;
                return $this->player_mode;
            case 'demo':
            case 'fun':
            case 'trial':
                $this->player_mode = self::MODE_FUN;
                return $this->player_mode;
            default:
                $this->player_mode = self::MODE_REAL;
                return $this->player_mode;
        }
    }

    public function login($playerName, $extra = null)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        if (!empty($extra['game_mode'])) {
            $this->_setPlayerMode($extra['game_mode']);
        }

        $params = array(
            'uname' => $gameUsername,
            'mode' => $this->player_mode,
            'fn' => $gameUsername,
            'ln' => $gameUsername,
            'currency' => $this->currency_type,
            'testuser' => $this->testuser,
            'commonwallet' => self::COMMON_WALLET // Main/required parameter.
        );

        $this->CI->utils->debug_log('HogamingSeamless: (' . __FUNCTION__ . ')', 'PARAMS:', $params, 'CONTEXT:', $context);

        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForLogin($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $result_json = json_encode($resultXml);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);

        $this->CI->utils->debug_log("========== HOGAMING_SEAMLESS_API LOGIN PROCESS RESULT JSON/ARRAY=============", $result_json, $resultArr, $success);

        if ($success) {
            // TODO-LM
            // $result = array(
            //     'gameName' => $resultArr['element']['properties'][0],
            //     'ticketId' => $resultArr['element']['properties'][1]
            // );

            if (isset($resultArr['attribute'][2]['name']) && $resultArr['attribute'][2]['name'] == 'sessionid') {
                $result = array(
                    'session_id' => $resultArr['attribute'][2]['value']
                );

                return array($success, $result);
            }

            return array($success);
        }

        return array($success);
    }

    public function processResultBoolean($responseResultId, $resultArray, $playerName)
    {
        $success = true;
        if (isset($resultArray['attribute'][1]['name']) && $resultArray['attribute'][1]['name'] == 'errorcode') {
            $success = false;
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log("========== HOGAMING_SEAMLESS_API GOT ERROR=============", $resultArray['attribute'][1]['name'], $resultArray['attribute'][1]['value'], $playerName);
        }

        return $success;
    }

    public function generateUrl($apiName, $params)
    {
        if ($this->is_bet_logs) {
            $url = $this->api_bet_logs."/".self::API_syncGames;
        } else {
            $params = http_build_query($params);
            $url = $this->api_url;
            $url .= self::$uri_map[$apiName];
            $url .= '?' . $params;
        }

        if($this->use_new_api_url){
            $apiUri = self::$uri_map[$apiName];
            $url = $this->new_api_url . $apiUri;
        }

        $this->debug_log('HOGAMING_SEAMLESS_API GeneratedUrl: '.$apiName, $url);
        return $url;
    }

    public function generateXMLParams($params, $method = 'request', $response_id = null)
    {
        if ($this->is_bet_logs) {
            $response = array( $this->action => $params);
        } else {
            $data = array();
            foreach ($params as $key => $value) {
                $api_params = array(
                    'name_attr' => $key,
                    '_value' => $value
                );
                array_push($data, $api_params);
            }

            $response = array(
                $method => array(
                    'action_attr' => $this->action,
                    'element' => array(
                        'properties' => $data
                    )
                )
            );
        }

        if (!empty($response_id)) {
            $response['response']['element']['id_attr']=$response_id;
        }
        $params = $this->utils->arrayToXml($response);
        $params = strtr($params, array("\n" => '',"\r" => ''));
        return $params;
    }

    protected function customHttpCall($ch, $params)
    {
        if($this->use_new_api_url){
            if(isset($params['is_new_sync'])){
                unset($params['is_new_sync']);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        else{
            if ($this->use_xml_body) {
                $params = $this->generateXMLParams($params);
            }

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));

            $this->use_xml_body = false;
        }
        
        $this->utils->debug_log("HOGAMING_SEAMLESS_API: (customHttpCall) Params:", $params);
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $this->login($playerName, $extra);

        // Create player on game provider auth.
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account for HO Gaming Seamless API";
        if ($return) {
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = "Successfull create account for HO Gaming Seamless API";
        }

        $this->CI->utils->debug_log('HogamingSeamless: (' . __FUNCTION__ . ')', 'RETURN:', $return, 'MESSAGE:', $message);

        return array("success" => $success, "message" => $message);
    }

    // public function changePassword($playerName, $oldPassword = null, $newPassword)
    // {
    //     $success=true;
    //     $playerId = $this->getPlayerIdInPlayer($playerName);
    //     if (!empty($playerId)) {
    //         $this->updatePasswordForPlayer($playerId, $newPassword);
    //     }

    //     return array('success' => $success);
    // }

    public function changePassword($playerName, $oldPassword, $newPassword)
    {
        return $this->returnUnimplemented();
    }

    public function queryPlayerBalance($playerName)
    {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        $this->CI->utils->debug_log('HogamingSeamless: (' . __FUNCTION__ . ')', 'PARAMS:', $playerName, 'RESULT:', $result);

        return $result;
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        $this->CI->utils->debug_log('HogamingSeamless: (' . __FUNCTION__ . ')', 'PLAYER NAME:', $playerName, 'AMOUNT:', $amount, 'TRANSFER SECURE ID:', $transfer_secure_id);

        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => null,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        $this->CI->utils->debug_log('HogamingSeamless: (' . __FUNCTION__ . ')', 'PLAYER NAME:', $playerName, 'AMOUNT:', $amount, 'TRANSFER SECURE ID:', $transfer_secure_id);

        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => null,
            'didnot_insert_game_logs'=>true,
        );
    }

    /*
     * Game Link
     *
     *  #FOR RNG GAMES W/ BET LIMIT
     *  NOTES: version is default to V3, will not work if use V4, see docs for bet limit for each game
     *  /goto_hggame/<game_platform_id>/<game_type_id>/<lang>/<table_id>/<game_mode>/<bet_limit>
     *  /goto_hggame/2078/0000000000000019/en/m777FH/real/1
     *
     *  #To load specific game type while loading the lobby for V3
     *  /goto_hggame/<game_platform_id>/<game_type_id>/<lang>/<table_id>/<game_mode>/<bet_limit>/<is_mobile>/<version>/<skin_id>
     *  /goto_hggame/2078/0000000000000001/en/null/real/NULL/null/V3
     *
     *  #To load specific game type while loading the lobby for V4
     *  /goto_hggame/<game_platform_id>/<game_type_id>/<lang>/<table_id>/<game_mode>/<bet_limit>/<is_mobile>/<version>/<skin_id>
     *  /goto_hggame/2078/0000000000000001/en/null/real/null/null/V4
     *
     *  #To direct load Live / Slot / Poker / Table / Video / EVO Game for V3
     *  NOTE: Some tableId does not accept more than bet_limit
     *  /goto_hggame/<game_platform_id>/<game_type_id>/<lang>/<table_id>/<game_mode>/<bet_limit>/<is_mobile>/<version>/<skin_id>
     *  /goto_hggame/2078/0000000000000004/en/l8i2hq4jo2hjj9ca/real/1/null/V4
     */
    public function queryForwardGame($playerName, $extra = null)
    {
        if (!empty($extra['game_mode'])) {
            $player_mode = $this->_setPlayerMode($extra['game_mode']);

            if ($player_mode == self::MODE_FUN) {
                $this->login($playerName, $extra);
            }
        }

        $result = $this->login($playerName, $extra);
        if (isset($result['session_id'])) {
            $params['sessionid'] = $result['session_id'];
        }

        if ($result['success']) {
            if (!empty($extra['language'])) {
                $player_lang = $this->getLauncherLanguage($extra['language']);
            } else {
                #GET LANG FROM PLAYER DETAILS
                $playerId = $this->getPlayerIdFromUsername($playerName);
                $player_lang = $this->getLauncherLanguage($this->getPlayerDetails($playerId)->language);

                if (empty($player_lang)) {
                    $player_lang = $this->language;
                }
            }

            // $params = array(
            //     'ticketId' => $result['ticketId'],
            //     'lang' => isset($extra['language']) ? $this->getLauncherLanguage($extra['language']) : $player_lang
            // );

            #IDENTIFY IF LAUNCH WITH GAME TYPE (game_type as gameType)
            if (!empty($extra['game_type']) && $extra['game_type']) {
                $params['gameType'] = $this->getGameTypeId($extra['game_type']);
            }

            #IDENTIFY IF LAUNCH WITH TABLE ID (game_code as tableId)
            if (isset($extra['game_code']) && $extra['game_code'] && $extra['game_code'] != 'null') {
                $game_code = $extra['game_code'];
                $params['tableId'] = $game_code;
                if (!isset($params['gameType'])) {
                    foreach (self::TABLEGAMES_GAMETYPE_IDS as $key => $val) {
                        if ($val == $game_code) {
                            $params['gameType'] = $game_code;
                            unset($params['tableId']);
                        }
                    }
                }
            }

            // #IDENTIFY IF LAUNCH WITH BET TYPE (bet_limit as betType)
            // if (isset($extra['extra']['bet_limit']) && $extra['extra']['bet_limit'] && $extra['extra']['bet_limit'] != 'null') {
            //     $params['betType'] = $this->getBetLimitTypeId($extra['extra']['bet_limit']);
            // }

            // #IDENTIFY IF LAUNCH WITH REFERRER
            // if (isset($extra['extra']['referrer']) && $extra['extra']['referrer']) {
            //     $params['ref'] = $extra['extra']['referrer'];
            // }

            // #IDENTIFY IF LAUNCH WITH EXIT URL
            // if (isset($extra['extra']['exit_url']) && $extra['extra']['exit_url']) {
            //     $params['exitUrl'] = $extra['extra']['exit_url'];
            // }

            if (!empty($player_lang)) {
                $params['lang'] = $player_lang;
            }

            if (!empty($this->lobby_version)) {
                $params['version'] = $this->lobby_version;
            }

            #IDENTIFY MOBILE GAME
            if (isset($extra['is_mobile']) && $extra['is_mobile']) {
                $this->CI->utils->debug_log('HogamingSeamless: (' . __FUNCTION__ . ')', 'BEFORE MOBILE:', $extra['is_mobile']);
                $params['mobile'] = "true";
                $params['version'] = self::VERSION_V4;
                $this->CI->utils->debug_log('HogamingSeamless: (' . __FUNCTION__ . ')', 'AFTER MOBILE:', $params);
            }

            if (!empty($this->skin_id)) {
                $params['skinId'] = $this->skin_id;
            }

            // Main/required parameter.
            $params['commonwallet'] = self::COMMON_WALLET;
            $url = $this->api_url . self::$uri_map[parent::API_queryForwardGame] . '?' . http_build_query($params);

            $this->CI->utils->debug_log('HogamingSeamless: (' . __FUNCTION__ . ')', 'PARAMS:', $params, 'URL:', $url);

            return array('success'=>true,'url' => $url);
        }

        return array('success'=>false,'url' => '');
    }

    public function getLauncherLanguage($lang)
    {
        /*
            const LANGUAGE_ENGLISH = 'en';
            const LANGUAGE_JAPANESE = 'jp';
            const LANGUAGE_CHINESE_SIMPLIFIED = 'ch';
            const LANGUAGE_CHINESE_TRADITIONAL = 'tr';
            const LANGUAGE_SPANISH = 'sp';
            const LANGUAGE_THAI = 'th';
         */
        $lang = strtolower($lang);
        switch ($lang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "chinese":
            case "zh-cn":
            case "zh":
            case "cn":
                return "ch";
            case "japanese":
            case "jp-jp":
            case "jp":
                return "jp";
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "thai":
            case "th-th":
            case "th":
                return "th";
            case "eng":
            case "en-en":
            case "en-us":
            case "en-usa":
            case "us-en":
            case "usa-en":
            case "en":
                return "en";
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case 'id-id':
            case "id":
                return "id";
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case 'vi-vn':
            case "vi":
                return "vi";
            default:
                return "en";
        }
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $table_name, $additionalInfo = [])
    {
        $dataCount = 0;
        if (!empty($rows)) {
            foreach ($rows as $key => $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($table_name, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($table_name, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function doSyncOriginal($data, $table_name, $process = null)
    {
        $success = false;
        $result = ['data_count' => 0];

        $md5_fields = self::MD5_FIELDS_FOR_ORIGINAL;
        $md5_float = self::MD5_FLOAT_AMOUNT_FIELDS_FOR_ORIGINAL;

        if (!empty($data)) {
            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $table_name,
                $data,
                'external_uniqueid',
                'external_uniqueid',
                $md5_fields,
                'md5_sum',
                'id',
                $md5_float
            );

            unset($data);

            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert', $table_name, []);
                $success = true;
            }

            unset($insertRows);

            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update', $table_name, []);
                $success = true;
            }

            unset($updateRows);
        }

        return array('success' => $success);
    }

    const FUNDTRANS_PLACE_BET_REQUEST = 500;
    const FUNDTRANS_CANCEL_BET_REQUEST = 501;
    const FUNDTRANS_PLAYER_WIN_REQUEST = 510;
    const FUNDTRANS_PLAYER_LOSE_REQUEST = 520;
    const FUNDTRANS_PLACE_BET_CANCELLATION_REQUEST = 502;
    const CATEGORY_WIN = "playerwin";
    const CATEGORY_CANCELBET = "cancelbet";
    const CATEGORY_PLACE_BET_CANCEL = "placebetcancel";

    public function doSyncTransaction($data, $table_name, $process = null)
    {
        #Try update main bet transaction
        $related_data = call_user_func_array('array_merge', $data);
        if( isset($related_data['txnsubtypeid']) && ($related_data['txnsubtypeid'] != self::FUNDTRANS_PLACE_BET_REQUEST) ){
            $bet_transaction = $this->queryBetTransaction($related_data['gameid'], $related_data['uname']);
            if(!empty($bet_transaction) && empty($bet_transaction['related_data'])){
                $bet_transaction['related_data'] = json_encode($related_data);
                $this->CI->original_game_logs_model->updateRowsToOriginal($this->transaction_logs_table, $bet_transaction);
            }
        }

        $success = false;
        $result = ['data_count' => 0];

        $md5_fields = self::MD5_FIELDS_FOR_TRANSACTION;
        $md5_float = self::MD5_FLOAT_AMOUNT_FIELDS_FOR_TRANSACTION;

        if (!empty($data)) {
            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $table_name,
                $data,
                'external_uniqueid',
                'external_uniqueid',
                $md5_fields,
                'md5_sum',
                'id',
                $md5_float
            );

            unset($data);

            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert', $table_name, []);
                $success = true;
            }

            unset($insertRows);

            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update', $table_name, []);
                $success = true;
            }

            unset($updateRows);
        }

        return array('success' => $success);
    }

     public function queryBetTransaction($game_id, $game_username)
    {
        $sqlParam='`trans`.`gameid` = ?
          AND `trans`.`uname` = ? AND `trans`.`txnsubtypeid` = ?';

        $sql = <<<EOD
            SELECT
                trans.id,
                trans.external_uniqueid,
                trans.related_data
            FROM $this->transaction_logs_table as trans
            WHERE
            {$sqlParam}
EOD;

        $params=[
            $game_id,
            $game_username,
            self::FUNDTRANS_PLACE_BET_REQUEST
        ];

        return $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
    }

    private function getGameTypeId($gameType)
    {
        if (array_key_exists($gameType, self::TABLEGAMES_GAMETYPE_IDS)) {
            return self::TABLEGAMES_GAMETYPE_IDS[$gameType];
        }
        return $gameType;
    }

    private function getBetLimitTypeId($betLimitType)
    {
        if (array_key_exists($betLimitType, self::BETTYPE_IDS)) {
            return self::BETTYPE_IDS[$betLimitType];
        }
        return $betLimitType;
    }

    public function saveResponseResult($success, $apiName, $params, $resultText, $statusCode, $statusText = null,
            $extra = null, $field = null, $dont_save_response_in_api = false, $costMs=null) {
        return parent::saveResponseResult($success, $apiName, $params, $resultText, $statusCode, $statusText,
            $extra, $field, $dont_save_response_in_api, $costMs);
    }

    /**
     * If the Data requested is very large to retrieve,
     * the following error will be displayed by the system. Application
     * throws the following error based on the date/time range selected by the player.
     *
     * Status – 0011: The data you have requested is too huge to be retrieved. Reduce the Date/Time Range and try again.
     */
    public function syncOriginalGameLogs($token = false)
    {
        #check if fetch from transaction enabled
        if($this->use_transaction_table){
            return $this->syncFromTransactiontable($token);
        }

        if($this->use_new_sync_version){
            return $this->syncOriginalGameLogsV2($token);
        }

        $three_minutes = 60 * 3;

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs',
            'startDate' => $startDate,
            'endDate' => $endDate,
        );

        $this->is_bet_logs = true;

        sleep($this->common_wait_seconds);
        $cancelledGameLogsRes = $this->callCancelledBets($context);
        sleep($this->common_wait_seconds);
        $settledGameLogsRes = $this->callSettledBets($context);

        return [
                "success" => true,
                "result" => ["cancelled"=>$cancelledGameLogsRes,"settled"=>$settledGameLogsRes],
                "response_result_id" =>$settledGameLogsRes['response_result_id']
               ];
    }

    private function callSettledBets($context)
    {
        // $hogamingTimeout = $this->utils->getJsonFromCache("settled-HOGAMING-timeout");
        // if ($hogamingTimeout >= time()) {
        //     sleep($this->common_wait_seconds);
        //     $this->CI->utils->debug_log('settled-HOGAMING-timeout API ==============> skip Syncing due to 1 call per minute restriction.');
        //     return array("success"=>true,"details"=>"[Settled Game Logs] skip Syncing due to 1 call per minute restriction. ");
        // }
        $this->use_xml_body = true;

        $context['isCancelledFlag'] = false;

        $params = array(
            'Username' => $this->web_api_username,
            'Password' => $this->web_api_password,
            'CasinoId' => $this->casino_id,
            'StartTime' => $context['startDate']->format("Y-m-d H:i:s"),
            'EndTime' => $context['endDate']->format("Y-m-d H:i:s"),
            'Usertype' => 'Play',
        );

        $this->action = self::API_syncGames;

        return $this->callApi($this->action, $params, $context);
    }

    private function callCancelledBets($context)
    {
        // $hogamingTimeout = $this->utils->getJsonFromCache("cancelled-HOGAMING-timeout");
        // if ($hogamingTimeout >= time()) {
        //     sleep($this->common_wait_seconds);
        //     $this->CI->utils->debug_log('cancelled-HOGAMING-timeout API ==============> skip Syncing due to 1 call per minute restriction.');
        //     return array("success"=>true,"details"=>"[Cancelled Game Logs] skip Syncing due to 1 call per minute restriction. ");
        // }
        $this->use_xml_body = true;

        $context['isCancelledFlag'] = true;
        $params = array(
            'Username' => $this->web_api_username,
            'Password' => $this->web_api_password,
            'CasinoId' => $this->casino_id,
            'StartTime' => $context['startDate']->format("Y-m-d H:i:s"),
            'EndTime' => $context['endDate']->format("Y-m-d H:i:s"),
            'Usertype' => 'Play',
            'Status' => 'cancel'
        );

        $this->action = self::API_syncGames;

        return $this->callApi($this->action, $params, $context);
    }

    public function processResultForSyncOriginalGameLogs($params)
    {
        $this->CI->load->model('original_game_logs_model');
        $isCancelledFlag = $this->getVariableFromContext($params, 'isCancelledFlag');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);

        $logsResult = $this->generateLogsResult($resultArr[0]);

        $this->CI->utils->debug_log("HOGAMING_SEAMLESS_API GAME LOGS DATA ===>", 'RESULT XML: ', $resultXml, 'LOG RESULT: ', $logsResult);

        $success = true;
        $data_count = 0;

        if (!empty($logsResult['STATUS_CODE'])) {
            $timeOutCachedId = $isCancelledFlag ? "cancelled" : "settled";
            $this->utils->saveJsonToCache($timeOutCachedId."-HOGAMING-timeout", strtotime("+ 91 seconds"));
            $this->CI->utils->debug_log('Ho GAMING API ADD timeout ===> + 1 minutes');
            return array(true,['error'=>"add sleep time"]);
        } else {
            $gameRecords = $logsResult['Betinfo'] === array_values($logsResult['Betinfo']) ? $logsResult['Betinfo'] : array($logsResult['Betinfo']);
            $this->CI->utils->debug_log("HOGAMING GAME LOGS DATA ===>", $gameRecords);

            $result = ['data_count' => 0];

            if ($success&&!empty($gameRecords)) {
                $extra = [
                            'response_result_id' => $responseResultId,
                            'is_cancelled' => $isCancelledFlag,
                         ];
                $this->rebuildGameRecords($gameRecords, $extra);

                list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    $this->original_gamelogs_table,
                    $gameRecords,
                    'external_uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS_FOR_ORIGINAL
                );

                $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

                unset($gameRecords);
                if (!empty($insertRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs(
                        $insertRows,
                        'insert',
                        $this->original_gamelogs_table,
                        ['responseResultId'=>$responseResultId]
                    );
                }
                unset($insertRows);

                if (!empty($updateRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs(
                        $updateRows,
                        'update',
                        $this->original_gamelogs_table,
                        ['responseResultId'=>$responseResultId]
                    );
                }
                unset($updateRows);
            }
        }

        return array($success, $result);
    }

    private function rebuildGameRecords(&$gameRecords, $extra)
    {
        $newGR =[];
        foreach ($gameRecords as $i => $gr) {
            $this->useServerTimeToGameTimeInOrigSync = $this->getSystemInfo('useServerTimeToGameTimeInOrigSync', false);
            if ($this->useServerTimeToGameTimeInOrigSync) {
                $betStartDate = isset($gr['BetStartDate'])?$this->utils->modifyDateTime($gr['BetStartDate'], $this->gameTimeToServerTime):null;
                $betEndDate = isset($gr['BetEndDate'])?$this->utils->modifyDateTime($gr['BetEndDate'], $this->gameTimeToServerTime):null;
            } else {
                $betStartDate = isset($gr['BetStartDate'])?$this->utils->modifyDateTime($gr['BetStartDate'], 0):null;
                $betEndDate = isset($gr['BetEndDate'])?$this->utils->modifyDateTime($gr['BetEndDate'], 0):null;
            }
            $newGR[$i]['bet_start_date'] = $betStartDate;
            $newGR[$i]['bet_end_date'] = $betEndDate;
            $newGR[$i]['account_id'] = isset($gr['AccountId'])?$gr['AccountId']:null;
            $newGR[$i]['table_id'] = isset($gr['TableId'])?$gr['TableId']:null;
            $newGR[$i]['table_name'] = isset($gr['TableName'])?$gr['TableName']:null;
            $newGR[$i]['game_id'] = isset($gr['GameId'])?$gr['GameId']:null;
            $newGR[$i]['bet_id'] = isset($gr['BetId'])?$gr['BetId']:null;
            $newGR[$i]['bet_amount'] = isset($gr['BetAmount'])?$gr['BetAmount']:null;
            $newGR[$i]['payout'] = isset($gr['Payout'])?$gr['Payout']:null;
            $newGR[$i]['currency'] = isset($gr['Currency'])?$gr['Currency']:null;
            $newGR[$i]['game_type'] = isset($gr['GameType'])?$gr['GameType']:null;
            $newGR[$i]['bet_spot'] = isset($gr['BetSpot'])?$gr['BetSpot']:null;
            $newGR[$i]['bet_no'] = isset($gr['BetNo'])?$gr['BetNo']:null;

            if ($this->add_cancelled_bets_in_original_game_logs && $extra['is_cancelled']) {
                $newGR[$i]['bet_mode'] = 'cancelled';
                $newGR[$i]['status'] = 'cancelled';
            } else {
                $newGR[$i]['bet_mode'] = isset($gr['BetMode'])?$gr['BetMode']:null;
                $newGR[$i]['status'] = isset($gr['BetMode'])?$gr['BetMode']:null;
            }

            $newGR[$i]['external_uniqueid'] = isset($gr['BetId'])?$gr['BetId']:null;
            $newGR[$i]['response_result_id'] = $extra['response_result_id'];
        }
        $gameRecords = $newGR;
    }

    public function generateLogsResult($result)
    {
        $logs =  strtr($result, array("\n" => ''));

        // strip Total Records tag. convert to xml will error if not remove
        $logs = preg_replace('/<TotalRecords[^>]*>.*?<\/TotalRecords>/i', '', $logs);

        $logsXml = new SimpleXMLElement($logs);
        $logsResult = json_decode(json_encode($logsXml), true);

        return $logsResult;
    }

    public function syncMergeToGameLogs($token)
    {
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs(
            $token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle
        );
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime='`hog`.`bet_end_date` >= ?
          AND `hog`.`bet_end_date` <= ?';
        if ($use_bet_time) {
            $sqlTime='`hog`.`bet_start_date` >= ?
          AND `hog`.`bet_start_date` <= ?';
        }

        $sql = <<<EOD
            SELECT
                hog.id as sync_index,
                hog.response_result_id,
                hog.game_id as round,
                hog.account_id as username,
                hog.bet_amount as bet_amount,
                hog.bet_amount as valid_bet,
                hog.payout as result_amount,
                hog.bet_start_date as start_at,
                hog.bet_end_date as end_at,
                hog.bet_start_date as bet_at,
                hog.table_id as game_code,
                hog.game_type as game_name,
                hog.external_uniqueid,
                hog.md5_sum,
                hog.status,

                game_provider_auth.player_id,

                gd.id as game_description_id,
                gd.game_name as game_description_name,
                gd.game_type_id

            FROM $this->original_gamelogs_table as hog
            LEFT JOIN game_description as gd ON hog.table_id = gd.external_game_id AND gd.game_platform_id = ?
            LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
            JOIN game_provider_auth ON hog.account_id = game_provider_auth.login_name
            AND game_provider_auth.game_provider_id=?
            AND hog.bet_mode != 'cancelled'
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
            'table' =>  $row['round'],
        ];

        if (empty($row['md5_sum'])) {
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row,
                self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
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
                'end_at' => $row['end_at'],#OGP-14059 as per data team, end_at should be based on bet_start_time, but as per james we should retain it as end_at
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            // 'status' => Game_logs::STATUS_SETTLED,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => [],
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row)
    {
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row, $unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        if($this->use_transaction_table){
            $row['status'] = $row['status'];
        } else {
            $row['status'] = Game_logs::STATUS_SETTLED;
        }
    }

    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
        $game_name = str_replace(
            "알수없음",
            $row['game_code'],
            str_replace(
                "不明",
                $row['game_code'],
                str_replace("Unknown", $row['game_code'], $unknownGame->game_name)
            )
        );
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id,
            $game_type_id,
            $external_game_id,
            $game_type,
            $external_game_id,
            $extra,
            $unknownGame
        );
    }

    public function blockPlayer($playerName)
    {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->blockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function unblockPlayer($playerName)
    {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->unblockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function queryPlayerInfo($playerName)
    {
        return $this->returnUnimplemented();
    }

    public function updatePlayerInfo($playerName, $infos)
    {
        return $this->returnUnimplemented();
    }

    public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null)
    {
        return $this->returnUnimplemented();
    }

    public function queryGameRecords($dateFrom, $dateTo, $playerName = null)
    {
        return $this->returnUnimplemented();
    }

    public function checkLoginStatus($playerName)
    {
        return $this->returnUnimplemented();
    }

    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null)
    {
        return array(false, null);
    }


    public function syncFromTransactiontable($token)
    {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
        $endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
        $startDate->modify($this->getDatetimeAdjust());


        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate   = $endDate->format('Y-m-d H:i:s');
        $rows = $this->queryTransactionTable($startDate, $endDate);
        $result = ['data_count' => 0];
        if (!empty($rows)) {
            $this->rebuildTransactionRows($rows);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $rows,
                'external_uniqueid',
                'external_uniqueid',
                ['bet_start_date','bet_end_date','account_id','game_id','bet_no','bet_amount','currency','payout','status','table_id'],
                'md5_sum',
                'id',
                ['bet_amount', 'payout']
            );

            unset($rows);

            if (!empty($insertRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert', $this->original_gamelogs_table, []);
                $success = true;
            }

            unset($insertRows);

            if (!empty($updateRows)) {
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update', $this->original_gamelogs_table, []);
                $success = true;
            }

            unset($updateRows);
        }
        return array("success" => true, $result);
    }

    public function queryTransactionTable($startDate, $endDate)
    {
        $this->CI->load->model('original_game_logs_model');
        $sqlTime='`trans`.`created_at` >= ?
          AND `trans`.`updated_at` <= ? AND `trans`.`txnsubtypeid` = ?';

        $sql = <<<EOD
            SELECT
                trans.created_at as bet_start_date,
                trans.updated_at as bet_end_date,
                trans.uname as account_id,
                trans.gameid as game_id,
                trans.txnid as bet_id,
                trans.txnid as bet_no,
                trans.amt as bet_amount,
                trans.cur as currency,
                trans.related_data,
                trans.external_uniqueid,
                trans.gametypeid as table_id,
                trans.md5_sum

            FROM $this->transaction_logs_table as trans
            WHERE
            {$sqlTime}
EOD;

        $params=[
            $startDate,
            $endDate,
            self::FUNDTRANS_PLACE_BET_REQUEST
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function rebuildTransactionRows(&$rows){
        if(!empty($rows)){
            foreach ($rows as $key => $row) {
                // $row['table_id'] = "unknown";
                $row['bet_mode'] = lang("seamless");
                $row['payout'] = - $row['bet_amount'];
                $row['status'] = Game_logs::STATUS_SETTLED;
                if(!empty($row['related_data'])){
                    $related_data = json_decode($row['related_data'], true);
                    if(isset($related_data['category']) && $related_data['category'] == self::CATEGORY_WIN){
                        $payout_amount = $related_data['amt'];
                        $row['payout'] = $payout_amount - $row['bet_amount'];
                    }

                    if(isset($related_data['category']) && ($related_data['category'] == self::CATEGORY_CANCELBET || $related_data['category'] == self::CATEGORY_PLACE_BET_CANCEL)){
                        $row['status'] = Game_logs::STATUS_CANCELLED;
                    }
                }
                unset($row['related_data']);
                // $row['status'] = Game_logs::STATUS_SETTLED;
                $rows[$key] = $row;
            }
        }
    }

    public function syncOriginalGameLogsV2($token){
        $this->use_new_api_url = true;

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());

        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate   = $endDate->format('Y-m-d H:i:s');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecordstest',
        );

        $params = array(
            'starttime' => $startDate,
            'endtime' => $endDate,
            'is_new_sync' => true
        );
        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForSyncGameRecordstest($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );
        $success = true;
        if( isset($resultJsonArr['data']) && !empty($resultJsonArr['data'])){
            $gameRecords = $resultJsonArr['data'];
            $this->processGameRecords($gameRecords, $responseResultId);
            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_ORIGINAL
            );

            $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));
            $dataResult['data_count'] = count($gameRecords);
            unset($gameRecords);
            if (!empty($insertRows)) {
                $dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs(
                    $insertRows,
                    'insert',
                    $this->original_gamelogs_table,
                    ['responseResultId'=>$responseResultId]
                );
            }
            unset($insertRows);

            if (!empty($updateRows)) {
                $dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs(
                    $updateRows,
                    'update',
                    $this->original_gamelogs_table,
                    ['responseResultId'=>$responseResultId]
                );
            }
            unset($updateRows);
        }
        return array($success, $dataResult);
    }

    public function processGameRecords(&$gameRecords, $responseResultId) {
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $data['bet_start_date'] = isset($record['betStartDate']) ? $this->gameTimeToServerTime($record['betStartDate']) : null;
                $data['bet_end_date'] = isset($record['betEndDate']) ? $this->gameTimeToServerTime($record['betEndDate']) : null;
                $data['account_id'] = isset($record['accountId']) ? $record['accountId'] : null;
                $data['table_id'] = isset($record['tableId']) ? $record['tableId'] : null;
                $data['table_name'] = isset($record['tableName']) ? $record['tableName'] : null;
                $data['game_id'] = isset($record['gameId']) ? $record['gameId'] : null;
                $data['bet_id'] = isset($record['betId']) ? $record['betId'] : null;
                $data['bet_amount'] = isset($record['betAmount']) ? $this->gameAmountToDB($record['betAmount']) : null;
                $data['winning_amount'] = isset($record['payout']) ? $this->gameAmountToDB($record['payout']) : null;
                $data['payout'] = $data['winning_amount'] - $data['bet_amount'];
                $data['currency'] = isset($record['currency']) ? $record['currency'] : null;
                $data['game_type'] = isset($record['gameType']) ? $record['gameType'] : null;
                $data['bet_spot'] = isset($record['betSpot']) ? $record['betSpot'] : null;
                $data['bet_no'] = isset($record['betId']) ? $record['betId'] : null;
                $data['bet_mode'] = isset($record['version']) ? $record['version'] : null;
                $data['status'] = isset($record['status']) ? $record['status'] : null;
                #default
                $data['external_uniqueid'] = $data['bet_id'];
                $data['response_result_id'] = $responseResultId;
                $gameRecords[$index] = $data;
                unset($data);
            }
        }    
    }

    public function getOperatorToken(){
        $this->use_new_api_url = true;
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetOperatorToken',
        );

        $params = array(
            'username' => $this->web_api_username,
            'password' => $this->web_api_password,
            'casinoId' => $this->casino_id,
        );

        return $this->callApi(self::API_generateToken, $params, $context);
    }

    public function processResultForGetOperatorToken($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $result = array(
            "token" => null,
        );
        $success = false;
        if(isset($resultJsonArr['access_token'])){
            $success = true;
            $token_timeout = new DateTime($this->utils->getNowForMysql());
            $minutes = 4;
            $token_timeout->modify("+".$minutes." minutes");
            $result['api_token']=$resultJsonArr['access_token'];
            $result['api_token_timeout_datetime']=$token_timeout->format('Y-m-d H:i:s');
        }
        return array($success, $result);
    }

    public function getHttpHeaders($params)
    {

        if(isset($params['is_new_sync'])){
            $bearer_token = $this->getAvailableApiToken();
            $headers = array(
                "Authorization" => "Bearer {$bearer_token}"
            );
            return $headers;
        }
    }

    /**
     * will check timeout, if timeout then call again
     * @return token
     */
    public function getAvailableApiToken(){
        $token = $this->getCommonAvailableApiToken(function(){
           return $this->getOperatorToken();
        });

        $this->utils->debug_log("Hogaming Bearer Token: ".$token);
        return $token;
    }

    public function queryTransactionByDateTime($startDate, $endDate){
        $this->CI->load->model(array('original_game_logs_model'));
        $this->original_transactions_table = $this->getTransactionsTable();

$sql = <<<EOD
SELECT 
gpa.player_id as player_id,
t.created_at transaction_date,
t.amt as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.txnid as round_no,
t.external_uniqueid as external_uniqueid,
t.category trans_type
FROM {$this->original_transactions_table} as t
JOIN game_provider_auth gpa on gpa.login_name = t.uname
WHERE `t`.`updated_at` >= ? AND `t`.`updated_at` <= ? 
ORDER BY t.updated_at asc;

EOD;

$params=[$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }


}
