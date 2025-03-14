<?php
if (! defined("BASEPATH")) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Game Provider: AG
 * Game Type: live_dealder, slots
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2013-2022 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -ag_seamless_service_api.php
 **/
class Game_api_ag_seamless extends Abstract_game_api {
    public $original_seamless_wallet_transactions_table;
    public $original_seamless_game_logs_table;
    public $api_url;
    public $c_agent;
    public $md5_encryption_key;
    public $des_encryption_key;
    public $product_id;
    public $language;
    public $currency;
    public $game_session_url;
    public $game_launch_url;
    public $mh5;
    public $odd_type;
    public $default_lobby_game_code;
    public $partner_website;
    public $prefix_for_username;
    public $precision;
    public $conversion;
    public $arithmetic_name;
    public $adjustment_precision;
    public $adjustment_conversion;
    public $adjustment_arithmetic_name;
    public $whitelist_ip_validate_api_methods;
    public $game_api_active_validate_api_methods;
    public $game_api_maintenance_validate_api_methods;
    public $game_api_player_blocked_validate_api_methods;
    public $sync_time_interval;
    public $sleep_time;
    public $use_transaction_data;
    public $use_error_response_description;
    public $use_default_game_launch_password;
    public $default_game_launch_password;

    const SEAMLESS_GAME_API_NAME = 'AG_SEAMLESS_GAME_API';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const LANGUAGE_CODE_ENGLISH = 3;
    const LANGUAGE_CODE_CHINESE = 1;
    const LANGUAGE_CODE_INDONESIAN = 11;
    const LANGUAGE_CODE_VIETNAMESE = 8;
    const LANGUAGE_CODE_KOREAN = 5;
    const LANGUAGE_CODE_THAI = 6;
    const LANGUAGE_CODE_HINDI = 3; // no hindi, default to english
    const LANGUAGE_CODE_PORTUGUESE = 23;
    const LANGUAGE_CODE_TAIWANESE = 2;

    // live_dealer
    const TRANSACTION_TYPE_BET = 'bet';
    const TRANSACTION_TYPE_WIN = 'win';
    const TRANSACTION_TYPE_LOSE = 'lose';
    const TRANSACTION_TYPE_REFUND = 'refund';

    // slots
    const TRANSACTION_TYPE_WITHDRAW = 'withdraw';
    const TRANSACTION_TYPE_DEPOSIT = 'deposit';
    const TRANSACTION_TYPE_ROLLBACK = 'rollback';

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED_FOR_GAME_LOGS = 1;
    const FLAG_UPDATED = 2;
    const FLAG_RETAIN = 3;

    const SUCCESS_CODE = '0';

    const URI_MAP = [
        self::API_createPlayer => '/doBusiness.do',
        self::API_createPlayerGameSession => '/resource/player-tickets.ucs',
        self::API_queryForwardGame => '/forwardGame.do'
    ];

    const MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS = [
        'status',
        'bet_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS = [
        'bet_amount',
        'win_amount',
        'result_amount',
    ];

    const MD5_FIELDS_FOR_MERGE_FROM_TRANS = [
        'player_id',
        'before_balance',
        'after_balance',
        'game_code',
        'transaction_type',
        'status',
        'response_result_id',
        'external_uniqueid',
        'start_at',
        'bet_at',
        'end_at',
        'created_at',
        'updated_at',
        'transaction_id',
        'round_number',
        'bet_amount',
        'real_betting_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE_FROM_TRANS = [
        'amount',
        'before_balance',
        'after_balance',
        'bet_amount',
        'real_betting_amount',
        'result_amount',
    ];

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(['original_game_logs_model']);
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'ag_seamless_wallet_transactions');
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'ag_seamless_game_logs');
        $this->api_url = $this->getSystemInfo('url');
        $this->c_agent = $this->getSystemInfo('c_agent');
        $this->md5_encryption_key = $this->getSystemInfo('md5_encryption_key');
        $this->des_encryption_key = $this->getSystemInfo('des_encryption_key');
        $this->product_id = $this->getSystemInfo('product_id');
        $this->language = $this->getSystemInfo('language');
        $this->currency = $this->getSystemInfo('currency');
        $this->game_session_url = $this->getSystemInfo('game_session_url');
        $this->game_launch_url = $this->getSystemInfo('game_launch_url');
        $this->mh5 = $this->getSystemInfo('mh5', '');
        $this->odd_type = $this->getSystemInfo('odd_type', 'A');
        $this->default_lobby_game_code = $this->getSystemInfo('default_lobby_game_code', 0);
        $this->partner_website = $this->getSystemInfo('partner_website', 'http://player.og.local');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->precision = $this->getSystemInfo('precision', 4);
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->arithmetic_name = $this->getSystemInfo('arithmetic_name', 'multiplication');
        $this->adjustment_precision = $this->getSystemInfo('adjustment_precision', $this->precision);
        $this->adjustment_conversion = $this->getSystemInfo('adjustment_conversion', $this->conversion);
        $this->adjustment_arithmetic_name = $this->getSystemInfo('adjustment_arithmetic_name', 'division');
        $this->whitelist_ip_validate_api_methods = $this->getSystemInfo('whitelist_ip_validate_api_methods', []);
        $this->game_api_active_validate_api_methods = $this->getSystemInfo('game_api_active_validate_api_methods', []);
        $this->game_api_maintenance_validate_api_methods = $this->getSystemInfo('game_api_maintenance_validate_api_methods', []);
        $this->game_api_player_blocked_validate_api_methods = $this->getSystemInfo('game_api_player_blocked_validate_api_methods', []);
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+2 minutes'); //minutes/hours/days
        $this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data', true);
        $this->use_error_response_description = $this->getSystemInfo('use_error_response_description', true);
        $this->use_default_game_launch_password = $this->getSystemInfo('use_default_game_launch_password', true);
        $this->default_game_launch_password = $this->getSystemInfo('default_game_launch_password', 'tot123456');
    }

    public function getPlatformCode() {
        return AG_SEAMLESS_GAME_API;
    }

    public function isSeamLessGame() {
        return true;
    }

    public function getSeamlessTransactionTable() {
        return $this->original_seamless_wallet_transactions_table;
    }

    public function getSeamlessGameLogsTable() {
        return $this->original_seamless_game_logs_table;
    }

    public function generateUrl($apiName,$params) {
        $apiUri = self::URI_MAP[$apiName];

        if($apiName == "createPlayerGameSession"){
            $url = $this->game_session_url . $apiUri . '?' . http_build_query($params);
        }else{
            $url = $this->api_url . $apiUri . '?' . $this->generateUrlGetParam($params);
        }

        return $url;
    }

    public function generateUrlGetParam($params) {
        $getParam = '';

        foreach($params as $key => $value) {
            $getParam .= $key . '=' . $value . '/\\\/';
        }

        $getParamRTrim = rtrim($getParam,'/\\\/');
        $this->CI->load->library(array('salt'));

        $des_encrypted = $this->CI->salt->encrypt($getParamRTrim, $this->des_encryption_key);
        $md5_encrypted = md5($des_encrypted . $this->md5_encryption_key);

        return 'params=' . $des_encrypted . '&key=' . $md5_encrypted;
    }

    protected function customHttpCall($ch, $params) {
        return $this->returnUnimplemented();
    }

    public function processResultBoolean($responseResultId, $resultXml, $errArr, $info_must_be_0 = false, $api = null) {
        $success = true;

        if($api == self::API_createPlayer){
            $info = $this->getAttrValueFromXml($resultXml, 'info');
            if (in_array($info, $errArr)) {
                $this->setResponseResultToError($responseResultId);
                $this->CI->utils->debug_log(self::SEAMLESS_GAME_API_NAME . ' API got error ', $responseResultId, 'result', $resultXml);
                $success = false;
            }elseif($info_must_be_0){
                $success = $info == self::SUCCESS_CODE;
            }
        }else{
            # add API here in the future that needs this method
            $success = false;
            $this->CI->utils->debug_log(self::SEAMLESS_GAME_API_NAME . ' API got error ', $responseResultId, 'result', $resultXml);
        }

        return $success;
    }

    public function getAttrValueFromXml($resultXml, $attrName) {
        $info = null;
        if (!empty($resultXml)) {
            $result = $resultXml->xpath('/result');
            if (isset($result[0])) {
                $attr = $result[0]->attributes();
                if (!empty($attr)) {
                    foreach ($attr as $key => $value) {
                        if ($key == $attrName) {
                            $info = '' . $value;
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

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        // create player in Database
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        if ($this->use_default_game_launch_password) {
            $password = $this->default_game_launch_password;
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'gameUserName' => $gameUsername,
            'playerId' => $playerId
        ];

        $params = [
            'cagent' => $this->c_agent,
            'loginname' => $gameUsername,
            'method' => 'lg',
            'actype' => 1,
            'password' => $password,
            'oddtype' => 'A',
            'cur' => $this->currency,
        ];

        return $this->callApi(self::API_createPlayer,$params,$context);
    }

    public function processResultForCreatePlayer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $gameUserName = $this->getVariableFromContext($params,'gameUserName');
        $playerId = $this->getVariableFromContext($params,'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultXml, array('key_error', 'network_error', 'account_add_fail', 'error'), true, self::API_createPlayer);
        $result['exists'] = false;

        if($success){
            // update flag to registered = true
            $this->updateRegisterFlag($playerId,Abstract_game_api::FLAG_TRUE);
            $result['exists'] = true;
        }

        return [$success, $result];
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;

        $result = [
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id' => null,
            'didnot_insert_game_logs' => true,
        ];

        return $result;
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;

        $result = [
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id' => null,
            'didnot_insert_game_logs' => true,
        ];

        return $result;
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function getLauncherLanguage($language) {
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-US':
            case 'en-us':
                $language = self::LANGUAGE_CODE_ENGLISH;
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh':
            case 'cn':
            case 'zh-CN':
            case 'zh-cn':
                $language = self::LANGUAGE_CODE_CHINESE;
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-ID':
                $language = self::LANGUAGE_CODE_INDONESIAN;
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-VN':
                $language = self::LANGUAGE_CODE_VIETNAMESE;
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko':
            case 'ko-KR':
                $language = self::LANGUAGE_CODE_KOREAN;
                break;
            case Language_function::INT_LANG_THAI:
            case 'th':
            case 'th-TH':
                $language = self::LANGUAGE_CODE_THAI;
                break;
            case Language_function::INT_LANG_INDIA:
            case 'hi':
            case 'hi-IN':
                $language = self::LANGUAGE_CODE_HINDI;
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
            case 'pt-BR':
            case 'prt':
                $language = self::LANGUAGE_CODE_PORTUGUESE;
                break;
            case 'zhtw':
            case 'zh-TW':
            case 'zh-tw':
                $language = self::LANGUAGE_CODE_TAIWANESE;
                break;
            default:
                $language = self::LANGUAGE_CODE_ENGLISH;
                break;
        }

        return $language;
    }

    public function queryForwardGame($playerName, $extra) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordString($playerName);
        $sid = $this->c_agent . random_string('numeric', 16);
        $is_mobile = isset($extra['is_mobile']) ? $extra['is_mobile'] : false;
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : $this->default_lobby_game_code;
        $game_mode = isset($extra['game_mode']) && $extra['game_mode'] == 'real' ? '1' : '0';
        $language = isset($extra['language']) ? $extra['language'] : $this->language;
        $player_current_token = $this->getPlayerTokenByUsername($playerName);
        $isTokenValid = $this->isTokenValid($player_current_token);
        $url = null;

        if ($this->use_default_game_launch_password) {
            $password = $this->default_game_launch_password;
        }

        if($isTokenValid){
            $queryPlayerBalance = $this->queryPlayerBalance($playerName);
            $player_balance = !empty($queryPlayerBalance['balance']) ? $queryPlayerBalance['balance'] : 0;
            $cPg = $this->createPlayerGameSession($this->product_id, $playerName, $player_current_token, $player_balance);

            $params = [
                'cagent' => $this->c_agent,
                'loginname' => $gameUsername,
                'password' => $password,
                'dm' => $this->partner_website,
                'sid' => $sid,
                'mh5' => $this->mh5,
                'actype' => $game_mode,
                'lang' => $this->getLauncherLanguage($language),
                'gameType' => $game_code,
                'oddtype' => $this->odd_type,
                'cur' => $this->currency
            ];

            $url = $this->game_launch_url . '/forwardGame.do' . '?' . $this->generateUrlGetParam($params);

            return [
                'success' => true,
                'url' => $url
            ];
        }

        return [
            'success' => false,
            'url' => $url
        ];
    }

    public function createPlayerGameSession($product_id, $username, $session_token, $credit) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($username);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayerGameSession',
            'gameUsername' => $gameUsername
        ];

        $params = [
            'productid' => $product_id,
            'username' => $gameUsername,
            'session_token' => $session_token,
            'credit' => $credit
        ];

        return $this->callApi(self::API_createPlayerGameSession,$params,$context);
    }

    public function processResultForCreatePlayerGameSession($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml),true);

        $result = [
            'response_result_id' => $responseResultId,
            'result_array' => $resultArr
        ];

        return [true, $result];
    }

    public function isTokenValid($token) {
        $playerInfo = parent::getPlayerInfoByToken($token);

        if(empty($playerInfo)){
            return false;
        }

        return true;
    }

    public function syncOriginalGameLogs($token = false) {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogsFromTrans($token = false) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $start_date = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $end_date = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $start_date->modify($this->getDatetimeAdjust());
        $query_datetime_start = $start_date->format('Y-m-d H:i:s');
        $query_datetime_end = $end_date->format('Y-m-d H:i:s');
        $transactions = $this->queryTransactionsForUpdate($query_datetime_start, $query_datetime_end);

        if (!empty($transactions) && is_array($transactions)) {
            foreach ($transactions as $transaction) {
                $validated_transaction = [
                    'type' => !empty($transaction['transaction_type']) ? $transaction['transaction_type'] : null,
                    'id' => !empty($transaction['transaction_id']) ? $transaction['transaction_id'] : null,
                    'player_id' => !empty($transaction['player_id']) ? $transaction['player_id'] : null,
                    'game_code' => !empty($transaction['game_code']) ? $transaction['game_code'] : null,
                    'round_id' => !empty($transaction['round_id']) ? $transaction['round_id'] : null,
                    'start_at' => !empty($transaction['start_at']) ? $transaction['start_at'] : null,
                    'status' => !empty($transaction['status']) ? $transaction['status'] : Game_logs::STATUS_UNSETTLED,
                    'updated_at' => !empty($transaction['updated_at']) ? $transaction['updated_at'] : null,
                    'external_unique_id' => !empty($transaction['external_unique_id']) ? $transaction['external_unique_id'] : null,
                ];


                extract($validated_transaction, EXTR_PREFIX_ALL, 'transaction');

                $bet_transaction_id = $transaction_id;
                $win_transaction_id = [];
                if (strpos($transaction_id, '-') != false) {
                    $win_transaction_id = explode('-', $transaction_id)[0];
                }

                if (!empty($win_transaction_id)) {
                    $bet_transaction_id = $win_transaction_id;
                }

                // live_dealer
                $bet_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_BET, $transaction_player_id, $transaction_game_code, $transaction_round_id, $bet_transaction_id);
                $win_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_WIN, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id);
                $lose_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_LOSE, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id);
                $refund_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_REFUND, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id);

                // slots
                $withdraw_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_WITHDRAW, $transaction_player_id, $transaction_game_code, $transaction_round_id);
                $deposit_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_DEPOSIT, $transaction_player_id, $transaction_game_code, $transaction_round_id);
                $rollback_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_ROLLBACK, $transaction_player_id, $transaction_game_code, $transaction_round_id);

                $validated_bet_transaction = [
                    'amount' => !empty($bet_transaction['amount']) ? $bet_transaction['amount'] : 0,
                    'external_unique_id' => !empty($bet_transaction['external_unique_id']) ? $bet_transaction['external_unique_id'] : null,
                ];

                $validated_win_transaction = [
                    'amount' => !empty($win_transaction['amount']) ? $win_transaction['amount'] : 0,
                    'external_unique_id' => !empty($win_transaction['external_unique_id']) ? $win_transaction['external_unique_id'] : null,
                ];

                $validated_lose_transaction = [
                    'amount' => !empty($lose_transaction['amount']) ? $lose_transaction['amount'] : 0,
                    'external_unique_id' => !empty($lose_transaction['external_unique_id']) ? $lose_transaction['external_unique_id'] : null,
                ];

                $validated_refund_transaction = [
                    'amount' => !empty($refund_transaction['amount']) ? $refund_transaction['amount'] : 0,
                    'external_unique_id' => !empty($refund_transaction['external_unique_id']) ? $refund_transaction['external_unique_id'] : null,
                ];

                $validated_withdraw_transaction = [
                    'amount' => !empty($withdraw_transaction['amount']) ? $withdraw_transaction['amount'] : 0,
                    'external_unique_id' => !empty($withdraw_transaction['external_unique_id']) ? $withdraw_transaction['external_unique_id'] : null,
                ];

                $validated_deposit_transaction = [
                    'amount' => !empty($deposit_transaction['amount']) ? $deposit_transaction['amount'] : 0,
                    'external_unique_id' => !empty($deposit_transaction['external_unique_id']) ? $deposit_transaction['external_unique_id'] : null,
                ];

                $validated_rollback_transaction = [
                    'amount' => !empty($rollback_transaction['amount']) ? $rollback_transaction['amount'] : 0,
                    'external_unique_id' => !empty($rollback_transaction['external_unique_id']) ? $rollback_transaction['external_unique_id'] : null,
                ];

                extract($validated_bet_transaction, EXTR_PREFIX_ALL, 'bet');
                extract($validated_win_transaction, EXTR_PREFIX_ALL, 'win');
                extract($validated_lose_transaction, EXTR_PREFIX_ALL, 'lose');
                extract($validated_refund_transaction, EXTR_PREFIX_ALL, 'refund');
                extract($validated_withdraw_transaction, EXTR_PREFIX_ALL, 'withdraw');
                extract($validated_deposit_transaction, EXTR_PREFIX_ALL, 'deposit');
                extract($validated_rollback_transaction, EXTR_PREFIX_ALL, 'rollback');

                if (array_key_exists('transaction_type', $transaction)) {
                    switch ($transaction_type) {
                        case self::TRANSACTION_TYPE_BET:
                            $bet_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => $win_amount,
                                'result_amount' => $win_amount - $bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $transaction_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_WIN:
                            $win_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'bet_amount' => $bet_amount,
                                'win_amount' => $win_amount,
                                'result_amount' => $win_amount - $bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $win_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_LOSE:
                            $lose_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'bet_amount' => $bet_amount,
                                'win_amount' => $lose_amount,
                                'result_amount' => $lose_amount - $bet_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $lose_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_REFUND:
                            $refund_data = [
                                'status' => Game_logs::STATUS_REFUND,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $bet_data = [
                                'status' => Game_logs::STATUS_REFUND,
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' => $refund_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $refund_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_WITHDRAW:
                            $withdraw_data = [
                                'status' => $transaction_status,
                                'bet_amount' => $withdraw_amount,
                                'win_amount' => $deposit_amount,
                                'result_amount' => $deposit_amount - $withdraw_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $withdraw_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($withdraw_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $withdraw_data, 'external_unique_id', $transaction_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_DEPOSIT:
                            $deposit_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $withdraw_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'bet_amount' => $withdraw_amount,
                                'win_amount' => $deposit_amount,
                                'result_amount' => $deposit_amount - $withdraw_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $withdraw_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($withdraw_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $deposit_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $withdraw_data, 'external_unique_id', $withdraw_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_ROLLBACK:
                            $rollback_data = [
                                'status' => Game_logs::STATUS_REFUND,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            $withdraw_data = [
                                'status' => Game_logs::STATUS_REFUND,
                                'bet_amount' => $withdraw_amount,
                                'win_amount' => 0,
                                'result_amount' => $rollback_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            $withdraw_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($withdraw_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $rollback_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $withdraw_data, 'external_unique_id', $withdraw_external_unique_id);
                            break;
                        default:
                            break;
                    }
                }

                $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'transaction_start_at', $transaction_start_at, 'transaction_updated_at', $transaction_updated_at);
            }
        }

        $total_transactions_updated = count($transactions);

        $result = [
            $this->CI->utils->pluralize('total_transaction_updated', 'total_transactions_updated', $total_transactions_updated) => $total_transactions_updated,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'result', $result);

        return ['success' => true, $result];
    }

    public function queryTransactionsForUpdate($dateFrom, $dateTo, $transaction_type = null) {
        $sqlTime = 'start_at >= ? AND end_at <= ?';

        if (!empty($transaction_type)) {
            $and_transaction_type = "AND transaction_type = ?";
        } else {
            $and_transaction_type = '';
        }

        $sql = <<<EOD
SELECT
id,
game_platform_id,
player_id,
transaction_type,
transaction_id,
game_code,
round_id,
amount,
start_at,
status,
extra_info,
external_unique_id,
updated_at
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND flag_of_updated_result = ? AND {$sqlTime} {$and_transaction_type}
EOD;

        if (!empty($transaction_type)) {
            $params = [
                $this->getPlatformCode(),
                self::FLAG_NOT_UPDATED,
                $dateFrom,
                $dateTo,
                $transaction_type,
            ];
        } else {
            $params = [
                $this->getPlatformCode(),
                self::FLAG_NOT_UPDATED,
                $dateFrom,
                $dateTo
            ];
        }

        // $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function queryPlayerTransaction($transaction_type, $player_id, $game_code, $round_id, $transaction_id = null) {
        if (!empty($transaction_id)) {
            $and_transaction_id = 'AND transaction_id = ?';
        } else {
            $and_transaction_id = '';
        }

        $sql = <<<EOD
SELECT DISTINCT 
player_id,
id,
sum(amount) as amount,
status,
extra_info,
external_unique_id,
bet_amount,
win_amount,
result_amount
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND player_id = ? AND game_code = ? AND round_id = ? {$and_transaction_id}
EOD;

        if (!empty($transaction_id)) {
            $params = [
                $this->getPlatformCode(),
                $transaction_type,
                $player_id,
                $game_code,
                $round_id,
                $transaction_id,
            ];
        } else {
            $params = [
                $this->getPlatformCode(),
                $transaction_type,
                $player_id,
                $game_code,
                $round_id,
            ];
        }

        // $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $results = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $results;
    }

    public function queryBillNumber($transaction_type, $transaction_id) {
        $sql = <<<EOD
SELECT
bill_no
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND transaction_id = ?
EOD;

        $params = [
            $this->getPlatformCode(),
            $transaction_type,
            $transaction_id,
        ];

        // $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $result;
    }

    public function syncMergeToGameLogs($token) {
        $this->syncOriginalGameLogsFromTrans($token);
        $enabled_game_logs_unsettle = true;

        return $this->commonSyncMergeToGameLogs(
            $token,
            $this,
            [$this, 'queryOriginalGameLogsFromTrans'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
            [$this, 'preprocessOriginalRowForGameLogsFromTrans'],
            $enabled_game_logs_unsettle
        );
    }

    /**
     * queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time) {
        $sqlTime = 'transaction.updated_at >= ? AND transaction.updated_at <= ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at >= ? AND transaction.start_at <= ?';
        }

        $sql = <<<EOD
SELECT
transaction.id AS sync_index,
transaction.player_id,
transaction.game_username AS player_username,
transaction.before_balance,
transaction.after_balance,
transaction.game_code,
transaction.transaction_type,
transaction.status,
transaction.response_result_id,
transaction.external_unique_id as external_uniqueid,
transaction.start_at,
transaction.start_at AS bet_at,
transaction.end_at,
transaction.created_at,
transaction.updated_at,
transaction.md5_sum,
transaction.transaction_id,
transaction.round_id AS round_number,
transaction.bet_amount,
transaction.bet_amount AS real_betting_amount,
transaction.win_amount,
transaction.result_amount,
transaction.flag_of_updated_result,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game
FROM {$this->original_seamless_wallet_transactions_table} AS transaction
LEFT JOIN game_description ON transaction.game_code = game_description.external_game_id AND game_description.game_platform_id = ?
WHERE transaction.game_platform_id = ? AND transaction.flag_of_updated_result = ? AND {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            self::FLAG_UPDATED_FOR_GAME_LOGS,
            $dateFrom,
            $dateTo,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) {
        if (empty($row['md5_sum'])) {
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE_FROM_TRANS);
        }

        $data = [
            'game_info' => [
                'game_type_id' => !empty($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id' => !empty($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code' => !empty($row['game_code']) ? $row['game_code'] : null,
                'game_type' => null,
                'game' => !empty($row['game']) ? $row['game'] : null,
            ],
            'player_info' => [
                'player_id' => !empty($row['player_id']) ? $row['player_id'] : null,
                'player_username' => !empty($row['player_username']) ? $row['player_username'] : null,
            ],
            'amount_info' => [
                'bet_amount' => !empty($row['bet_amount']) ? $row['bet_amount'] : 0,
                'result_amount' => !empty($row['result_amount']) ? $row['result_amount'] : 0,
                'bet_for_cashback' => !empty($row['bet_amount']) ? $row['bet_amount'] : 0,
                'real_betting_amount' => !empty($row['real_betting_amount']) ? $row['real_betting_amount'] : 0,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => !empty($row['after_balance']) ? $row['after_balance'] : 0,
            ],
            'date_info' => [
                'start_at' => !empty($row['start_at']) ? $row['start_at'] : '0000-00-00 00:00:00',
                'end_at' => !empty($row['end_at']) ? $row['end_at'] : '0000-00-00 00:00:00',
                'bet_at' => !empty($row['bet_at']) ? $row['bet_at'] : '0000-00-00 00:00:00',
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => !empty($row['status']) ? $row['status'] : null,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => !empty($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number' => !empty($row['round_number']) ? $row['round_number'] : null,
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => !empty($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index' => $row['sync_index'],
                'bet_type' => null,
            ],
            'bet_details' => [
                'Transaction Id' => !empty($row['transaction_id']) ? $row['transaction_id'] : null,
                'Game Code' => !empty($row['game_code']) ? $row['game_code'] : null,
                'Round Id' => !empty($row['round_number']) ? $row['round_number'] : null,
            ],
            'extra' => [
                'note' => !empty($row['note']) ? $row['note'] : null,
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $win_bill_no = $this->queryBillNumber(self::TRANSACTION_TYPE_WIN, $row['transaction_id']);
        $lose_bill_no = $this->queryBillNumber(self::TRANSACTION_TYPE_LOSE, $row['transaction_id']);
        $refund_bill_no = $this->queryBillNumber(self::TRANSACTION_TYPE_REFUND, $row['transaction_id']);

        if (!empty($win_bill_no)) {
            $data['bet_details']['Bill no'] = $win_bill_no['bill_no'];
        } else {
            if (!empty($lose_bill_no)) {
                $data['bet_details']['Bill no'] = $lose_bill_no['bill_no'];
            } else {
                if (!empty($refund_bill_no)) {
                    $data['bet_details']['Bill no'] = $refund_bill_no['bill_no'];
                }
            }
        }

        //$this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'data', $data);

        return $data;
    }

    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogsFromTrans(array &$row) {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $result_amount = !empty($row['result_amount']) ? $row['result_amount'] : 0;
        $row['after_balance'] += $row['win_amount'];
        $status = !empty($row['status']) ? $row['status'] : null;

        if ($status == Game_logs::STATUS_PENDING) {
            $row['note'] = 'Pending';
        } elseif ($status == Game_logs::STATUS_SETTLED) {
            if ($result_amount > 0) {
                $row['note'] = 'Win';
            } elseif ($result_amount < 0) {
                $row['note'] = 'Lose';
            } elseif ($result_amount == 0) {
                $row['note'] = 'Draw';
            } else {
                $row['note'] = 'Free Game';
            }
        } elseif ($status == Game_logs::STATUS_REFUND) {
            $row['note'] = 'Refunded';
        } elseif ($status == Game_logs::STATUS_UNSETTLED) {
            $row['note'] = 'Unsettled';
        } else {
            $row['note'] = 'Unknown';
        }
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_code = !empty($row['game_code']) ? $row['game_code'] : null;
        $game_type_id = !empty($row['game_type_id']) ? $row['game_type_id'] : $unknownGame->game_type_id;

        if (!empty($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
        } else {
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $game_code, $game_code);
        }

        return array($game_description_id, $game_type_id);
    }

    public function queryTransactionByDateTime($startDate, $endDate) {
        $original_transactions_table = $this->getSeamlessTransactionTable();

        if (!$original_transactions_table) {
            $this->CI->utils->debug_log("queryTransactionByDateTime cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }

        $md5_fields = implode(", ", array('t.amount', 't.before_balance', 't.after_balance', 't.result_amount', 't.updated_at'));

        $sql = <<<EOD
SELECT
t.player_id as player_id,
t.start_at as transaction_date,
t.amount as amount,
t.after_balance,
t.before_balance,
t.result_amount,
t.round_id as round_no,
t.transaction_id,
t.external_unique_id as external_uniqueid,
t.transaction_type as trans_type,
t.updated_at,
MD5(CONCAT({$md5_fields})) as md5_sum,
t.extra_info
FROM {$original_transactions_table} as t
WHERE t.game_platform_id = ? and t.updated_at >= ? AND t.updated_at <= ?
ORDER BY t.updated_at asc, t.id asc;
EOD;

        $params = [
            $this->getPlatformCode(),
            $startDate,
            $endDate,
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processTransactions(&$transactions) {
        $temp_game_records = [];

        if (!empty($transactions)) {
            foreach ($transactions as $transaction) {
                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];
                $temp_game_record['amount'] = abs($transaction['amount']);
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $temp_game_record['md5_sum'] = $transaction['md5_sum'];

                if (empty($temp_game_record['round_no']) && isset($transaction['transaction_id'])) {
                    $temp_game_record['round_no'] = $transaction['transaction_id'];
                }

                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;

                if ($transaction['trans_type'] == self::TRANSACTION_TYPE_BET || $transaction['trans_type'] == self::TRANSACTION_TYPE_WITHDRAW) {
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                }

                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }
}