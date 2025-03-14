<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Game Provider: Evoplay Entertainment
 * Game Type: Slots
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2013-2022 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -evoplay_seamless_service_api.php
 **/

class Game_api_evoplay_seamless extends Abstract_game_api
{
    public $api_url;
    public $project_id;
    public $api_version;
    public $callback_version;
    public $secret_key;
    public $language;
    public $currency;
    public $exit_url;
    public $cash_url;
    public $settings_https;
    public $specified_time_seconds;
    public $return_url_info;
    public $denomination;
    public $need_extra_data;
    public $scope;
    public $no_refund;
    public $result_rounds_limit;
    public $sync_time_interval;
    public $sleep_time;
    public $prefix_for_username;
    public $http_method;
    public $original_transactions_table;
    public $use_new_token;
    public $encryption_key;
    public $secret_encription_iv;
    public $encrypt_method;
    public $seamless_service_callback_url;
    public $use_bet_extra_info_token;
    public $use_signature;
    public $check_by_round_and_action;
    public $use_is_win_exist_real_error;
    public $use_is_refund_exist_real_error;
    public $demo_user_id;

    const SEAMLESS_GAME_API_NAME = 'Evoplay Seamless Game API';
    const OFFSET = 1;

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const API_triggerInternalPayoutRound = 'triggerInternalPayoutRound';
    const API_triggerInternalBetRound = 'triggerInternalBetRound';
    const API_triggerInternalRefundRound = 'triggerInternalRefundRound';

    const URI_MAP = [
        self::API_queryForwardGame => '/Game/getURL',
        self::API_queryGameListFromGameProvider => '/Game/getList',
        self::API_queryBetDetailLink => '/Game/getRound',
        self::API_syncGameRecords => '/Game/getRounds',
    ];

    const RESPONSE_STATUS_OK = 'ok';
    const RESPONSE_STATUS_ERROR = 'error';

    const UNKNOWN_ERROR = 'Unknown Error';

    const ERROR_CODE_UNKNOWN_ERROR = 'Serious';
    const ERROR_CODE_BET_IN_AMOUNT_VALUE_IS_NOT_IN_RANGE = 0;
    const ERROR_CODE_FAILED_TO_REMOVE_EXTRA_BONUS_FROM_STORAGE = 0;
    const ERROR_CODE_INVALID_DATETIME_FORMAT_FOR_EXPIRE_DATE = 0;
    const ERROR_CODE_API_DOES_NOT_EXIST = 1;
    const ERROR_CODE_CURRENCY_DOES_NOT_EXIST = 3;
    const ERROR_CODE_NOT_ENOUGH_PARAMS_FOR_API = 14;
    const ERROR_CODE_SIGNATURE_IS_INVALID = 16;
    const ERROR_CODE_GAME_DOES_NOT_EXIST = 36;
    const ERROR_CODE_NO_SUCH_EXTRA_BONUS_IN_STORAGE = 48;
    const ERROR_CODE_PAYOUT_IS_NOT_ALLOWED_FOR_THIS_GAME = 61;
    const ERROR_CODE_DENOMINATION_IS_INVALID = 64;
    const ERROR_CODE_FREE_SPINS_COUNT_IS_INVALID = 71;
    const ERROR_CODE_EVENT_DOES_NOT_EXIST = 72;
    const ERROR_CODE_ROUND_DOES_NOT_EXIST = 73;
    const ERROR_CODE_CURRENCY_NOT_ALLOWED = 76;

    const LANGUAGE_CODE_ENGLISH = 'en';
    const LANGUAGE_CODE_CHINESE = 'zh';
    const LANGUAGE_CODE_INDONESIAN = 'id';
    const LANGUAGE_CODE_VIETNAMESE = 'vi';
    const LANGUAGE_CODE_KOREAN = 'ko';
    const LANGUAGE_CODE_THAI = 'th';
    const LANGUAGE_CODE_PORTUGUESE = 'pt';
    const LANGUAGE_CODE_RUSSIAN = 'ru';
    const LANGUAGE_CODE_TAIWANESE = 'zhtw';
    const LANGUAGE_CODE_FRENCH = 'fr';
    const LANGUAGE_CODE_GERMAN = 'de';
    const LANGUAGE_CODE_ITALIAN = 'it';
    const LANGUAGE_CODE_JAPANESE = 'ja';
    const LANGUAGE_CODE_SPANISH = 'es';
    const LANGUAGE_CODE_TURKISH = 'tr';
    const LANGUAGE_CODE_ROMANIAN = 'ro';
    const LANGUAGE_CODE_BULGARIAN = 'bg';
    const LANGUAGE_CODE_CROATIAN = 'hr';
    const LANGUAGE_CODE_LITHUANIAN = 'lt';

    const TRANSACTION_BET = 'bet';
    const TRANSACTION_WIN = 'win';
    const TRANSACTION_REFUND = 'refund';

    const WALLET_TRANSACTIONS = [
        self::TRANSACTION_BET,
        self::TRANSACTION_WIN,
        self::TRANSACTION_REFUND,
    ];

    const FLAG_NOT_UPDATED = 0;
    const FLAG_BET_UPDATED = 1;
    const FLAG_UPDATED = 2;


    const MD5_FIELDS_FOR_ORIGINAL = [
        'status',
        'bet_amount',
        'result_amount',
        'flag_of_updated_result',
        'updated_at',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet_amount',
        'win_amount',
        'result_amount',
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'game_platform_id',
        'token',
        'player_id',
        'currency',
        'transaction_type',
        'transaction_id',
        'absolute_name',
        'game_code',
        'round_number',
        'action_id',
        'action',
        'amount',
        'before_balance',
        'after_balance',
        'start_at',
        'bet_at',
        'end_at',
        'trans_status',
        'signature',
        'elapsed_time',
        'extra_info',
        'bet_amount',
        'real_betting_amount',
        'win_amount',
        'result_amount',
        'flag_of_updated_result',
        'response_result_id',
        'external_uniqueid',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'amount',
        'before_balance',
        'after_balance',
        'bet_amount',
        'real_betting_amount',
        'win_amount',
        'result_amount',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->CI->load->model(array('original_game_logs_model'));
        $this->api_url = $this->getSystemInfo('url');
        $this->project_id = $this->getSystemInfo('project_id');
        $this->api_version = $this->getSystemInfo('api_version', 1);
        $this->callback_version = $this->getSystemInfo('callback_version', 2);
        $this->secret_key = $this->getSystemInfo('secret_key');
        $this->language = $this->getSystemInfo('language');
        $this->currency = $this->getSystemInfo('currency');
        $this->exit_url = $this->getSystemInfo('exit_url');
        $this->cash_url = $this->getSystemInfo('cash_url');
        $this->settings_https = $this->getSystemInfo('settings_https', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 1 : 0);
        $this->specified_time_seconds = $this->getSystemInfo('specified_time_seconds', 3600); //seconds
        $this->denomination = $this->getSystemInfo('denomination', 1);
        $this->return_url_info = $this->getSystemInfo('return_url_info', 1);
        $this->need_extra_data = $this->getSystemInfo('need_extra_data', 1);
        $this->scope = $this->getSystemInfo('scope', 'internal'); //internal - error message is not available to user | user - error message seen by user
        $this->no_refund = $this->getSystemInfo('no_refund', 0); //1 - callback should not be resent | 0 - callback can be resent
        $this->result_rounds_limit = $this->getSystemInfo('result_rounds_limit', 1000); //max.value1000
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+2 minutes'); //minutes/hours/days
        $this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->http_method = self::HTTP_METHOD_GET;
        $this->original_transactions_table = $this->getSystemInfo('original_transactions_table', 'evoplay_seamless_wallet_transactions');
        $this->use_new_token = $this->getSystemInfo('use_new_token', true);
        $this->encryption_key = $this->getSystemInfo('encryption_key', 'yrdSg4BWkYuZPK8p');
        $this->secret_encription_iv = $this->getSystemInfo('secret_encription_iv', 'XuZDCW4ReWDhdNau');
        $this->encrypt_method = $this->getSystemInfo('encrypt_method', 'AES-256-CBC');
        $this->seamless_service_callback_url = $this->getSystemInfo('seamless_service_callback_url', '');
        $this->use_bet_extra_info_token = $this->getSystemInfo('use_bet_extra_info_token', false);
        $this->use_signature = $this->getSystemInfo('use_signature', true);
        $this->check_by_round_and_action = $this->getSystemInfo('check_by_round_and_action', true);
        $this->use_is_win_exist_real_error = $this->getSystemInfo('use_is_win_exist_real_error', true);
        $this->use_is_refund_exist_real_error = $this->getSystemInfo('use_is_refund_exist_real_error', true);
        $this->demo_user_id = $this->getSystemInfo('demo_user_id', '');
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function getPlatformCode()
    {
        return EVOPLAY_SEAMLESS_GAME_API;
    }

    public function generateUrl($apiName, $params)
    {
        $project_id = $this->project_id;
        $api_version = $this->api_version;
        $secret_key = $this->secret_key;
        $signature = $this->getSignature($project_id, $api_version, $params, $secret_key);

        $main_params = [
            'project' => $project_id,
            'version' => $api_version,
            'signature' => $signature,
        ];

        $rebuild_params = array_merge($main_params, $params);

        if ($apiName == self::API_triggerInternalPayoutRound || $apiName == self::API_triggerInternalRefundRound) {
            return $this->seamless_service_callback_url;
        }

        $url = $this->api_url . self::URI_MAP[$apiName];

        if ($this->http_method == self::HTTP_METHOD_GET) {
            $url .= '?' .  http_build_query($rebuild_params);
        }

        return $url;
    }

    protected function getHttpHeaders($params)
    {
        $headers['Content-Type'] = 'application/json';
        /* $headers['Content-Type'] = 'multipart/form-data';
        $headers['Content-Type'] = 'application/x-www-form-urlencoded'; */

        return $headers;
    }

    protected function customHttpCall($ch, $params)
    {
        if ($this->http_method == self::HTTP_METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode, $playerGameUsername = null)
    {
        $success = false;

        if (@$statusCode == 200) {
            if (isset($resultArr['status']) && $resultArr['status'] == self::RESPONSE_STATUS_OK) {
                $success = true;
            }
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('Evoplay Seamless got error ', $responseResultId, 'result', $resultArr, 'playerGameUsername', $playerGameUsername);
        }

        return $success;
    }

    public function getSignature($project_id, $version, array $params, $secret_key)
    {
        $md5 = array();
        $md5[] = $project_id;
        $md5[] = $version;

        foreach ($params as $param) {
            if (is_array($param)) {
                if (count($param)) {
                    $recursive_param = '';

                    array_walk_recursive($param, function ($item) use (&$recursive_param) {
                        if (!is_array($item)) {
                            $recursive_param .= ($item . ':');
                        }
                    });

                    $md5[] = substr($recursive_param, 0, strlen($recursive_param) - 1); // get rid of last colon-sign
                } else {
                    $md5[] = '';
                }
            } else {
                $md5[] = $param;
            }
        };

        $md5[] = $secret_key;
        $md5_str = implode('*', $md5);
        $md5 = md5($md5_str);

        return $md5;
    }

    public function getTransactionsTable()
    {
        return $this->original_transactions_table;
    }

    public function getSeamlessTransactionTable()
    {
        return $this->original_transactions_table;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $createPlayer = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = 'Unable to create account for Evoplay seamless game API.';

        if ($createPlayer) {
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = 'Successfully create account for Evoplay seamless game API';
        }

        return array('success' => $success, 'message' => $message);
    }

    public function getLauncherLanguage($language)
    {
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
            case 'en-US':
                $result = self::LANGUAGE_CODE_ENGLISH;
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh':
            case 'cn':
            case 'zh-CN':
                $result = self::LANGUAGE_CODE_CHINESE;
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-ID':
                $result = self::LANGUAGE_CODE_INDONESIAN;
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-VN':
                $result = self::LANGUAGE_CODE_VIETNAMESE;
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko':
            case 'ko-KR':
                $result = self::LANGUAGE_CODE_KOREAN;
                break;
            case Language_function::INT_LANG_THAI:
            case 'th':
            case 'th-TH':
                $result = self::LANGUAGE_CODE_THAI;
                break;
            case Language_function::INT_LANG_INDIA:
            case 'hi':
            case 'hi-IN':
                $result = self::LANGUAGE_CODE_ENGLISH; //indian not supported
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt':
            case 'pt-BR':
            case 'pt-br':
            case 'pt-PT':
                $result = self::LANGUAGE_CODE_PORTUGUESE;
                break;
            case 'ru':
            case 'ru-RU':
                $result = self::LANGUAGE_CODE_RUSSIAN;
                break;
            case 'zhtw':
            case 'zh-TW':
                $result = self::LANGUAGE_CODE_TAIWANESE;
                break;
            case 'fr':
            case 'fr-FR':
                $result = self::LANGUAGE_CODE_FRENCH;
                break;
            case 'de':
            case 'de-DE':
                $result = self::LANGUAGE_CODE_GERMAN;
                break;
            case 'it':
            case 'it-IT':
                $result = self::LANGUAGE_CODE_ITALIAN;
                break;
            case 'ja':
            case 'ja-JP':
                $result = self::LANGUAGE_CODE_JAPANESE;
                break;
            case 'es':
            case 'es-ES':
                $result = self::LANGUAGE_CODE_SPANISH;
                break;
            case 'tr':
            case 'tr-TR':
                $result = self::LANGUAGE_CODE_TURKISH;
                break;
            case 'ro':
            case 'ro-RO':
                $result = self::LANGUAGE_CODE_ROMANIAN;
                break;
            case 'bg':
            case 'bg-BG':
                $result = self::LANGUAGE_CODE_BULGARIAN;
                break;
            case 'hr':
            case 'hr-HR':
                $result = self::LANGUAGE_CODE_CROATIAN;
                break;
            case 'lt':
            case 'lt-LT':
                $result = self::LANGUAGE_CODE_LITHUANIAN;
                break;
            default:
                $result = self::LANGUAGE_CODE_ENGLISH;
                break;
        }

        return $result;
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id' => null,
            'didnot_insert_game_logs' => true,
        );
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
        $external_transaction_id = $transfer_secure_id;

        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id' => null,
            'didnot_insert_game_logs' => true,
        );
    }

    public function queryPlayerBalance($playerName)
    {
        $playerId = $this->CI->player_model->getPlayerIdByUsername($playerName);
        $useReadonly = true;
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode(), $useReadonly);

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    }

    public function generatePlayerToken($playerName)
    {
        $token = $this->encrypt($playerName);
        return $token;
    }

    public function encrypt($data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }

        $output = false;
        $key = hash('sha256', $this->encryption_key);
        $iv = substr(hash('sha256', $this->secret_encription_iv), 0, 16);
        $output = openssl_encrypt($data, $this->encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
        return $output;
    }

    public function decrypt($data)
    {
        $output = false;
        $key = hash('sha256', $this->encryption_key);
        $iv = substr(hash('sha256', $this->secret_encription_iv), 0, 16);
        $output = openssl_decrypt(base64_decode($data), $this->encrypt_method, $key, 0, $iv);
        return $output;
    }

    public function queryForwardGame($playerName, $extra = null)
    {
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $token = $this->getPlayerToken($playerId);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        if (!$this->validateWhitePlayer($playerName)) {
            return array('success' => false);
        }

        if ($this->use_new_token) {
            $token = $this->generatePlayerToken($playerName);
        }

        $game_code = $extra['game_code'];
        $game_mode = $extra['game_mode'];

        if ($game_mode == 'demo' || $game_mode == 'fun' || $game_mode == 'trial') {
            $token = 'demo';
        }

        $this->language = $this->getSystemInfo('language', $this->getLauncherLanguage($extra['language']));
        $this->exit_url = $this->getSystemInfo('exit_url', isset($extra['home_link']) && !empty($extra['home_link']) ? $extra['home_link'] : $this->getHomeLink());

        if (empty($this->cash_url)) {
            $this->cash_url = $this->CI->utils->getSystemUrl('player', '/player_center/dashboard/cashier#memberCenter');
            $this->appendCurrentDbOnUrl($this->cash_url);
        }

        if (isset($extra['extra']['home_link'])) {
            $this->exit_url = $extra['extra']['home_link'];
        }

        if (isset($extra['extra']['cashier_link'])) {
            $this->cash_url = $extra['extra']['cashier_link'];
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'token' => $token,
            'gameUsername' => $gameUsername,
        );

        $params = [
            'token' => $token,
            'game' => $game_code,
            'settings' => [
                'user_id' => $gameUsername,
                'exit_url' => $this->exit_url,
                'cash_url' => $this->cash_url,
                'language' => $this->language,
                'https' => $this->settings_https,
                'reality_check' => $this->specified_time_seconds,
            ],
            'denomination' => $this->denomination,
            'currency' => $this->currency,
            'return_url_info' => $this->return_url_info,
            'callback_version' => $this->callback_version,
        ];

        if (!empty($gameUsername)) {
            $params['settings']['user_id'] = $gameUsername;
        } else {
            if (!empty($this->demo_user_id)) {
                $params['settings']['user_id'] = $this->demo_user_id;
            }
        }

        $this->http_method = self::HTTP_METHOD_GET;
        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params);

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);

        $result = [
            'responseResultId' => $responseResultId,
        ];

        if ($success) {
            $result['url'] = isset($resultArr['data']['link']) ? $resultArr['data']['link'] : null;
            $result['session_id'] = isset($resultArr['data']['session_id']) ? $resultArr['data']['session_id'] : null;
        } else {
            if (isset($resultArr['ERROR']['CODE']) && !empty($resultArr['ERROR']['CODE']) && isset($resultArr['ERROR']['MESSAGE']) && !empty($resultArr['ERROR']['MESSAGE'])) {
                $result['code'] = $resultArr['ERROR']['CODE'];
                $result['message'] = $resultArr['ERROR']['MESSAGE'];
            } else {
                $result['message'] = self::UNKNOWN_ERROR;
            }
        }

        return array($success, $result);
    }

    public function queryGameListFromGameProvider($extra = null)
    {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        );

        $params = array(
            'need_extra_data' => $this->need_extra_data,
        );

        $this->http_method = self::HTTP_METHOD_GET;
        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params);

        return $this->callApi(self::API_queryGameListFromGameProvider, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params)
    {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result['games'] = [];

        if ($success) {
            $result['games'] = isset($resultArr['data']) ? $resultArr['data'] : [];
        } else {
            if (isset($resultArr['ERROR']['CODE']) && !empty($resultArr['ERROR']['CODE']) && isset($resultArr['ERROR']['MESSAGE']) && !empty($resultArr['ERROR']['MESSAGE'])) {
                $result['code'] = $resultArr['ERROR']['CODE'];
                $result['message'] = $resultArr['ERROR']['MESSAGE'];
            } else {
                $result['message'] = self::UNKNOWN_ERROR;
            }
        }

        return array($success, $result);
    }

    public function queryBetDetailLink($playerUsername, $betId = null, $extra = null)
    {
        if ($this->force_bet_detail_default_format) {
            return parent::queryBetDetailLink($playerUsername, $betId, $extra);
        }

        $success = true;
        $params = ['round_id' => $betId];
        $this->http_method = self::HTTP_METHOD_GET;
        $url = $this->generateUrl(self::API_queryBetDetailLink, $params);
        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params);

        return array('success' => $success, 'url' => $url);
    }

    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogs($token = false)
    {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogsFromTrans($token = false)
    {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $queryDateTimeStart = $startDateTime->format('Y-m-d H:i:s');
        $queryDateTimeEnd = $endDateTime->format('Y-m-d H:i:s');
        $transactions = $this->queryTransactionsForUpdate($queryDateTimeStart, $queryDateTimeEnd);

        if (!empty($transactions)) {
            foreach ($transactions as $transaction) {
                $transaction_type = $transaction['transaction_type'];
                $player_id = $transaction['player_id'];
                $game_id = $transaction['game_id'];
                $round_id = $transaction['round_id'];
                $action_id = $transaction['action_id'];
                $amount = $transaction['amount'];

                $bet_transaction = $this->getPlayerTransactionByType(self::TRANSACTION_BET, $player_id, $game_id, $round_id);
                $bet_amount = isset($bet_transaction['amount']) && !empty($bet_transaction['amount']) ? $bet_transaction['amount'] : 0;
                $bet_round_count = isset($bet_transaction['round_count']) && !empty($bet_transaction['round_count']) ? $bet_transaction['round_count'] : 0;

                $win_transaction = $this->getPlayerTransactionByType(self::TRANSACTION_WIN, $player_id, $game_id, $round_id);
                $win_amount = isset($win_transaction['amount']) && !empty($win_transaction['amount']) ? $win_transaction['amount'] : 0;

                $refund_transaction = $this->getPlayerTransactionByType(self::TRANSACTION_REFUND, $player_id, $game_id, $round_id);
                $refund_amount = isset($refund_transaction['amount']) && !empty($refund_transaction['amount']) ? $refund_transaction['amount'] : 0;

                if ($transaction_type == self::TRANSACTION_BET) {
                    if ($bet_round_count > 1) {
                        $win_transaction = $this->getPlayerTransactionByTypeAction(self::TRANSACTION_WIN, $player_id, $game_id, $round_id, $action_id);
                        $refund_transaction = $this->getPlayerTransactionByTypeAction(self::TRANSACTION_REFUND, $player_id, $game_id, $round_id, $action_id);

                        if (!empty($win_transaction)) {
                            $win_amount = isset($win_transaction['amount']) && !empty($win_transaction['amount']) ? $win_transaction['amount'] : 0;
                        }

                        if (!empty($refund_transaction)) {
                            $refund_amount = isset($refund_transaction['amount']) && !empty($refund_transaction['amount']) ? $refund_transaction['amount'] : 0;
                        }
                    }

                    unset($bet_transaction['round_count']);
                    unset($win_transaction['round_count']);
                    unset($refund_transaction['round_count']);

                    $transaction['status'] = isset($refund_transaction['external_unique_id']) && !empty($refund_transaction['external_unique_id']) ? Game_logs::STATUS_REFUND : Game_logs::STATUS_SETTLED;
                    $transaction['bet_amount'] = $amount;
                    $transaction['win_amount'] = $win_amount + $refund_amount;
                    $transaction['result_amount'] = ($win_amount + $refund_amount) - $amount;
                    $transaction['flag_of_updated_result'] = self::FLAG_BET_UPDATED;
                    $transaction['updated_at'] = $this->CI->utils->getNowForMysql();
                    $transaction['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($transaction, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_transactions_table, $transaction);
                }

                if ($transaction_type == self::TRANSACTION_WIN) {
                    if ($bet_round_count > 1) {
                        $bet_transaction = $this->getPlayerTransactionByTypeAction(self::TRANSACTION_BET, $player_id, $game_id, $round_id, $action_id);
                        $win_transaction = $this->getPlayerTransactionByTypeAction(self::TRANSACTION_WIN, $player_id, $game_id, $round_id, $action_id);
                        $refund_transaction = $this->getPlayerTransactionByTypeAction(self::TRANSACTION_REFUND, $player_id, $game_id, $round_id, $action_id);

                        if (!empty($win_transaction)) {
                            $win_amount = isset($win_transaction['amount']) && !empty($win_transaction['amount']) ? $win_transaction['amount'] : 0;
                        }

                        if (!empty($refund_transaction)) {
                            $refund_amount = isset($refund_transaction['amount']) && !empty($refund_transaction['amount']) ? $refund_transaction['amount'] : 0;
                        }
                    }

                    unset($bet_transaction['round_count']);
                    unset($win_transaction['round_count']);
                    unset($refund_transaction['round_count']);

                    if (is_array($bet_transaction) && !empty($bet_transaction)) {
                        $bet_transaction['status'] = Game_logs::STATUS_SETTLED;
                        $bet_transaction['bet_amount'] = $bet_amount;
                        $bet_transaction['win_amount'] = $win_amount + $refund_amount;
                        $bet_transaction['result_amount'] = ($win_amount + $refund_amount) - $bet_amount;
                        $bet_transaction['flag_of_updated_result'] = self::FLAG_BET_UPDATED;
                        $bet_transaction['updated_at'] = $this->CI->utils->getNowForMysql();
                        $bet_transaction['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($bet_transaction, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
                        $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_transactions_table, $bet_transaction);
                    }

                    $transaction['bet_amount'] = 0;
                    $transaction['win_amount'] = 0;
                    $transaction['result_amount'] = 0;
                    $transaction['flag_of_updated_result'] = self::FLAG_UPDATED;
                    $transaction['updated_at'] = $this->CI->utils->getNowForMysql();
                    $transaction['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($transaction, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_transactions_table, $transaction);
                }

                if ($transaction_type == self::TRANSACTION_REFUND) {
                    if ($bet_round_count > 1) {
                        $bet_transaction = $this->getPlayerTransactionByTypeAction(self::TRANSACTION_BET, $player_id, $game_id, $round_id, $action_id);
                        $win_transaction = $this->getPlayerTransactionByTypeAction(self::TRANSACTION_WIN, $player_id, $game_id, $round_id, $action_id);
                        $refund_transaction = $this->getPlayerTransactionByTypeAction(self::TRANSACTION_REFUND, $player_id, $game_id, $round_id, $action_id);

                        if (!empty($win_transaction)) {
                            $win_amount = isset($win_transaction['amount']) && !empty($win_transaction['amount']) ? $win_transaction['amount'] : 0;
                        }

                        if (!empty($refund_transaction)) {
                            $refund_amount = isset($refund_transaction['amount']) && !empty($refund_transaction['amount']) ? $refund_transaction['amount'] : 0;
                        }
                    }

                    unset($bet_transaction['round_count']);
                    unset($win_transaction['round_count']);
                    unset($refund_transaction['round_count']);

                    if (is_array($bet_transaction) && !empty($bet_transaction)) {
                        $bet_transaction['status'] = Game_logs::STATUS_REFUND;
                        $bet_transaction['bet_amount'] = $bet_amount;
                        $bet_transaction['win_amount'] = $win_amount + $refund_amount;
                        $bet_transaction['result_amount'] = ($win_amount + $refund_amount) - $bet_amount;
                        $bet_transaction['flag_of_updated_result'] = self::FLAG_BET_UPDATED;
                        $bet_transaction['updated_at'] = $this->CI->utils->getNowForMysql();
                        $bet_transaction['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($bet_transaction, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
                        $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_transactions_table, $bet_transaction);
                    }

                    $transaction['bet_amount'] = 0;
                    $transaction['win_amount'] = 0;
                    $transaction['result_amount'] = 0;
                    $transaction['flag_of_updated_result'] = self::FLAG_UPDATED;
                    $transaction['updated_at'] = $this->CI->utils->getNowForMysql();
                    $transaction['md5_sum'] = $this->CI->original_game_logs_model->generateMD5SumOneRow($transaction, self::MD5_FIELDS_FOR_ORIGINAL, self::MD5_FLOAT_AMOUNT_FIELDS);
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_transactions_table, $transaction);
                }
            }
        }

        $total_transactions_updated = count($transactions);

        $result = [
            $this->CI->utils->pluralize('total_transaction_updated', 'total_transactions_updated', $total_transactions_updated) => $total_transactions_updated,
        ];

        return array('success' => true, $result);
    }

    public function queryTransactionsForUpdate($dateFrom, $dateTo, $transaction_type = null)
    {
        $sqlTime = 'start_at >= ? AND end_at <= ?';

        if (!empty($transaction_type)) {
            $and_transaction_type = `AND transaction_type = ?`;
        } else {
            $and_transaction_type = '';
        }

        $sql = <<<EOD
SELECT
id,
game_platform_id,
amount,
player_id,
game_id,
transaction_type,
status,
external_unique_id,
transaction_id,
round_id,
action_id
FROM {$this->original_transactions_table}
WHERE game_platform_id = ? AND flag_of_updated_result = ? AND {$sqlTime} {$and_transaction_type}
EOD;
        $params = [
            $this->getPlatformCode(),
            self::FLAG_NOT_UPDATED,
            $dateFrom,
            $dateTo,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function getPlayerTransactionByType($transaction_type, $player_id, $game_id, $round_id)
    {
        $sql = <<<EOD
SELECT DISTINCT 
player_id,
id,
sum(amount) as amount,
status,
external_unique_id,
count(transaction_type) as round_count
FROM {$this->original_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND player_id = ? AND game_id = ? AND round_id = ?
EOD;
        $params = [
            $this->getPlatformCode(),
            $transaction_type,
            $player_id,
            $game_id,
            $round_id,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $result;
    }

    public function getPlayerTransactionByTypeAction($transaction_type, $player_id, $game_id, $round_id, $action_id)
    {
        $sql = <<<EOD
SELECT DISTINCT 
player_id,
id,
amount,
status,
external_unique_id,
extra_info
FROM {$this->original_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND player_id = ? AND game_id = ? AND round_id = ? AND action_id = ?
EOD;
        $params = [
            $this->getPlatformCode(),
            $transaction_type,
            $player_id,
            $game_id,
            $round_id,
            $action_id,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $result;
    }

    public function queryAmountByRoundIdAndType($round_id, $transaction_type)
    {
        $sql = <<<EOD
SELECT
SUM(amount) as total_amount
FROM {$this->original_transactions_table}
WHERE game_platform_id = ? and round_id = ? and transaction_type = ?
EOD;
        $params = [
            $this->getPlatformCode(),
            $round_id,
            $transaction_type,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $result;
    }

    public function getSpecificRoundIdByTransactionType($round_id, $transaction_type)
    {
        $sql = <<<EOD
SELECT
round_id
FROM {$this->original_transactions_table}
WHERE game_platform_id = ? and round_id = ? and transaction_type = ?
EOD;
        $params = [
            $this->getPlatformCode(),
            $round_id,
            $transaction_type,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $result;
    }

    public function isTransactionExistByRoundAndAction($player_id, $round_id, $action_id, $transaction_type)
    {
        $sql = <<<EOD
SELECT
id,
game_platform_id,
amount,
player_id,
game_id,
status,
external_unique_id,
extra_info,
transaction_id,
round_id
FROM {$this->original_transactions_table}
WHERE game_platform_id = ? and player_id = ? and round_id = ? and transaction_type = ?
EOD;
        $params = [
            $this->getPlatformCode(),
            $player_id,
            $round_id,
            $transaction_type,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        $extra_info = isset($result['extra_info']) ? json_decode($result['extra_info'], true) : null;

        if ($transaction_type == self::TRANSACTION_REFUND) {
            $existing_action_id = isset($extra_info['data']['refund_action_id']) ? $extra_info['data']['refund_action_id'] : null;
        } else {
            $existing_action_id = isset($extra_info['data']['action_id']) ? $extra_info['data']['action_id'] : null;
        }

        if ($action_id == $existing_action_id) {
            return true;
        } else {
            return false;
        }
    }

    public function queryTransactionsByTypeAndRound($transaction_type, $round_id)
    {
        $sql = <<<EOD
SELECT
id,
game_platform_id,
amount,
player_id,
game_id,
transaction_type,
status,
external_unique_id,
extra_info,
transaction_id,
round_id
FROM {$this->original_transactions_table}
WHERE game_platform_id = ? and transaction_type = ? and round_id = ?
EOD;
        $params = [
            $this->getPlatformCode(),
            $transaction_type,
            $round_id,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function queryBetTransactionsForUpdate($dateFrom, $dateTo)
    {
        $sqlTime = 'start_at >= ? AND end_at <= ? AND game_platform_id = ? AND flag_of_updated_result = ? AND transaction_type = ?';

        $sql = <<<EOD
SELECT
id,
game_platform_id,
amount,
status,
external_unique_id,
transaction_id,
round_id

FROM {$this->original_transactions_table}
WHERE {$sqlTime}
EOD;
        $params = [
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
            self::FLAG_NOT_UPDATED,
            self::TRANSACTION_BET,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function queryRefundTransactionsForUpdate($dateFrom, $dateTo)
    {
        $sqlTime = 'start_at >= ? AND end_at <= ? AND game_platform_id = ? AND transaction_type = ?';

        $sql = <<<EOD
SELECT
id,
game_platform_id,
amount,
status,
external_unique_id,
transaction_id,
round_id

FROM {$this->original_transactions_table}
WHERE {$sqlTime}
EOD;
        $params = [
            $dateFrom,
            $dateTo,
            $this->getPlatformCode(),
            self::TRANSACTION_REFUND,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function queryBetTransactionForRefund($round_id)
    {
        $sql = <<<EOD
SELECT
id,
game_platform_id,
amount,
status,
external_unique_id,
transaction_id,
round_id

FROM {$this->original_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND round_id = ? AND status != ?
EOD;
        $params = [
            $this->getPlatformCode(),
            self::TRANSACTION_BET,
            $round_id,
            Game_logs::STATUS_REFUND,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $result;
    }

    public function isTransactionExist($external_transaction_id)
    {
        $this->CI->db->from($this->original_transactions_table)
            ->where('game_platform_id', $this->getPlatformCode())
            ->where('external_unique_id', $external_transaction_id);

        return $this->CI->original_game_logs_model->runExistsResult();
    }

    public function queryTransactionByActionId($action_id, $playerName)
    {
        $player_id = $this->CI->player_model->getPlayerIdByUsername($playerName);

        $sql = <<<EOD
SELECT
id,
extra_info
FROM {$this->original_transactions_table}
WHERE game_platform_id = ? AND player_id = ?
EOD;
        $params = [
            $this->getPlatformCode(),
            $player_id,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        foreach ($results as $result) {
            $extra_info = isset($result['extra_info']) ? json_decode($result['extra_info'], true) : null;
            $existing_action_id = isset($extra_info['data']['action_id']) ? $extra_info['data']['action_id'] : null;

            if ($action_id == $existing_action_id) {
                return [
                    'id' => $result['id'],
                    'extra_info' => $extra_info,
                ];
                break;
            }
        }

        return ['message' => 'Action Id not exist.'];
    }

    public function syncMergeToGameLogs($token)
    {
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
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time)
    {
        $sqlTime = 'transaction.updated_at >= ? AND transaction.updated_at <= ?';

        if ($use_bet_time) {
            $sqlTime = 'transaction.start_at >= ? AND transaction.start_at <= ?';
        }

        $sql = <<<EOD
SELECT
transaction.id AS sync_index,
transaction.game_platform_id,
transaction.token,
transaction.player_id,
transaction.currency,
transaction.transaction_type,
transaction.transaction_id,
transaction.absolute_name,
transaction.game_id AS game_code,
transaction.round_id AS round_number,
transaction.action_id,
transaction.action,
transaction.amount,
transaction.before_balance,
transaction.after_balance,
transaction.start_at,
transaction.start_at AS bet_at,
transaction.end_at,
transaction.status as trans_status,
transaction.signature,
transaction.elapsed_time,
transaction.extra_info,
transaction.bet_amount,
transaction.bet_amount AS real_betting_amount,
transaction.win_amount,
transaction.result_amount,
transaction.flag_of_updated_result,
transaction.response_result_id,
transaction.external_unique_id as external_uniqueid,
transaction.created_at,
transaction.updated_at,
transaction.md5_sum,
game_description.id AS game_description_id,
game_description.game_type_id,
game_description.english_name AS game,
game_provider_auth.login_name AS player_username

FROM {$this->original_transactions_table} AS transaction
LEFT JOIN game_description ON transaction.game_id = game_description.external_game_id AND game_description.game_platform_id = ?
JOIN game_provider_auth ON transaction.player_id = game_provider_auth.player_id and game_provider_auth.game_provider_id = ?
WHERE transaction.game_platform_id = ? AND {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
        ];

        if ($this->enable_merging_rows) {
            // If enable_merging_rows is true, exclude the condition related to flag_of_updated_result
            $sql .= ' AND transaction.flag_of_updated_result = ?';
            $params[] = self::FLAG_BET_UPDATED;
        }
    

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
    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row)
    {
        if (empty($row['md5_sum'])) {
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $after_balance = isset($row['after_balance']) ? $row['after_balance'] : null;
        $result_amount = $row['result_amount'];
        if(!$this->enable_merging_rows){
            if($row['transaction_type'] == 'bet'){
                $win_amount = 0;
                $bet_amount = $row['amount'];
                $result_amount = $win_amount - $bet_amount;
                $after_balance = $row['before_balance'] - $bet_amount;
            }

            if($row['transaction_type'] == 'win'){
                $win_amount = $row['win_amount'];
                $bet_amount = $row['amount'];
                $result_amount = abs($win_amount - $bet_amount);
            }
        }

        $data = [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username'],
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $result_amount,
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $after_balance
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null,
            ],
            // 'bet_details' => [
            //     'Round ID' => $row['round_number'],
            //     'Action ID' => $row['action_id'],
            //     'Action' => $row['action'],
            //     'Final Action' => $row['status'],
            // ],
            'bet_details' => $this->formatBetDetails(json_decode($row['extra_info'],true)),
            'extra' => [
                'note' => $row['note'],
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        $this->CI->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'data', $data);

        return $data;
    }

    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogsFromTrans(array &$row)
    {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row['after_balance'] += $row['win_amount'];

        if ($row['trans_status'] == Game_logs::STATUS_REFUND) {
            $row['note'] = 'Refund';
            $row['status'] = Game_logs::STATUS_REFUND;
        } else {
            $row['status'] = Game_logs::STATUS_SETTLED;

            if ($row['result_amount'] > 0) {
                $row['note'] = 'Win';
            } elseif ($row['result_amount'] < 0) {
                $row['note'] = 'Lose';
            } elseif ($row['result_amount'] == 0) {
                $row['note'] = 'Draw';
            } else {
                $row['note'] = 'Free Game';
            }
        }

        if (!$this->enable_merging_rows && $row['transaction_type'] == 'win' && $row['result_amount'] == 0) {
            $row['note'] = 'win';
        }
    }

    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_description_id = null;
        $game_type_id = null;
        $extra_info = json_decode($row['extra_info'] ?: null, true);
        $details = json_decode($extra_info['data']['details'] ?: null, true);
        $row['game'] = !empty($this->setGameName('\\', $details['game']['absolute_name'])) ? $this->setGameName('\\', $details['game']['absolute_name']) : $row['game_code'];

        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if (empty($game_description_id)) {
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $row['game'], $row['game_code']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function setGameName($separator, $string)
    {
        $explode_result = explode($separator, $string);
        $lastElement = end($explode_result);

        return ucfirst($lastElement);
    }

    public function isValidCallbackSignature(array $transaction)
    {
            $params = $transaction;
            $is_match = false;
            $token = isset($transaction['token']) ? $transaction['token'] : null;
            $callback_id = isset($transaction['callback_id']) ? $transaction['callback_id'] : null;
            $callback_type = isset($transaction['name']) ? $transaction['name'] : null;
            
            if ($callback_type == self::TRANSACTION_REFUND) {
                $data['refund_round_id'] = isset($transaction['data']['refund_round_id']) ? $transaction['data']['refund_round_id'] : null;
                $data['refund_action_id'] = isset($transaction['data']['refund_action_id']) ? $transaction['data']['refund_action_id'] : null;
                $data['refund_callback_id'] = isset($transaction['data']['refund_callback_id']) ? $transaction['data']['refund_callback_id'] : null;
                $data['amount'] = isset($transaction['data']['amount']) ? $transaction['data']['amount'] : 0;
                $data['currency'] = isset($transaction['data']['currency']) ? $transaction['data']['currency'] : null;
                $data['details'] = isset($transaction['data']['details']) ? $transaction['data']['details'] : null;
            } elseif ($callback_type == self::TRANSACTION_WIN) {
                $data['round_id'] = isset($transaction['data']['round_id']) ? $transaction['data']['round_id'] : null;
                $data['action_id'] = isset($transaction['data']['action_id']) ? $transaction['data']['action_id'] : null;
                $data['final_action'] = isset($transaction['data']['final_action']) ? $transaction['data']['final_action'] : 0;
                $data['amount'] = isset($transaction['data']['amount']) ? $transaction['data']['amount'] : 0;
                $data['currency'] = isset($transaction['data']['currency']) ? $transaction['data']['currency'] : null;
                $data['details'] = isset($transaction['data']['details']) ? $transaction['data']['details'] : null;
            } else {
                $data['round_id'] = isset($transaction['data']['round_id']) ? $transaction['data']['round_id'] : null;
                $data['action_id'] = isset($transaction['data']['action_id']) ? $transaction['data']['action_id'] : null;
                $data['amount'] = isset($transaction['data']['amount']) ? $transaction['data']['amount'] : 0;
                $data['currency'] = isset($transaction['data']['currency']) ? $transaction['data']['currency'] : null;
                $data['details'] = isset($transaction['data']['details']) ? $transaction['data']['details'] : null;
            }

            if (isset($params['signature'])) {
                unset($params['signature']);
            }

            $get_signature = $this->getSignature($this->project_id, $this->callback_version, $params, $this->secret_key);

            if ($transaction['signature'] != $get_signature) {
                $reconstruct_params = [
                    'token' => $token,
                    'callback_id' => $callback_id,
                    'name' => $callback_type,
                    'data' => $data,
                ];

                $get_signature = $this->getSignature($this->project_id, $this->callback_version, $reconstruct_params, $this->secret_key);

                if ($transaction['signature'] == $get_signature) {
                    $is_match = true;
                } 
            } else {
                $is_match = true;
            }

            return $is_match;
    }

    public function triggerInternalPayoutRound($transaction)
    {
        //API_triggerInternalPayoutRound
        $this->CI->utils->debug_log('EVOPLAY SEAMLESS (triggerInternalPayoutRound)', 'transaction', $transaction);

        //check if parameters complete
        if (!is_array($transaction)) {
            $transaction = json_decode($transaction, true);
        }

        //check other data needed if complete
        if (!isset($transaction['token'])) {
            return array('success' => false, 'message' => 'Missing token.');
        }

        if (!isset($transaction['callback_id'])) {
            return array('success' => false, 'message' => 'Missing callback_id.');
        }

        if (!isset($transaction['name'])) {
            return array('success' => false, 'message' => 'Missing callback name.');
        }

        if (!isset($transaction['data'])) {
            return array('success' => false, 'message' => 'Missing data.');
        }

        if (!isset($transaction['data']['round_id'])) {
            return array('success' => false, 'message' => 'Missing data.round_id.');
        }

        if (!isset($transaction['data']['action_id'])) {
            return array('success' => false, 'message' => 'Missing data.action_id.');
        }

        if (!isset($transaction['data']['amount'])) {
            return array('success' => false, 'message' => 'Missing data.amount.');
        }

        if (!isset($transaction['data']['currency'])) {
            return array('success' => false, 'message' => 'Missing data.currency.');
        }

        if (!isset($transaction['signature'])) {
            return array('success' => false, 'message' => 'Missing signature.');
        }

        if ($this->use_signature) {
            $is_valid_signature = $this->isValidCallbackSignature($transaction);

            if (!$is_valid_signature) {
                return array('success' => false, 'message' => 'Invalid signature.');
            }
        }

        $player_id = null;
        $game_username = null;

        if (!isset($transaction['off_bet_token_checking'])) {
            $bet_transactions = $this->queryTransactionsByTypeAndRound(self::TRANSACTION_BET, $transaction['data']['round_id']);

            if (!empty($bet_transactions)) {
                foreach ($bet_transactions as $bet_transaction) {
                    $extra_info = isset($bet_transaction['extra_info']) ? json_decode($bet_transaction['extra_info'], true) : null;
                    $token = isset($extra_info['token']) ? $extra_info['token'] : null;

                    if ($transaction['token'] == $token) {
                        $player_id = isset($bet_transaction['player_id']) ? $bet_transaction['player_id'] : null;
                        $game_username = $this->getGameUsernameByPlayerId($player_id);
                        break;
                    }
                }
            } else {
                return array('success' => false, 'message' => 'Bet not exist.');
            }

            if (empty($player_id) || empty($game_username)) {
                return array('success' => false, 'message' => 'Invalid Token');
            }

            $is_transacton_exist = $this->isTransactionExist($transaction['callback_id']);

            if ($is_transacton_exist) {
                return array('success' => false, 'message' => 'Payout already processed. Double Payout is not allowed.');
            }

            $is_transaction_exist_by_action_id = $this->isTransactionExistByRoundAndAction($player_id, $transaction['data']['round_id'], $transaction['data']['action_id'], self::TRANSACTION_WIN);

            if ($is_transaction_exist_by_action_id && $this->check_by_round_and_action) {
                return array('success' => false, 'message' => 'Payout already processed. Double Payout is not allowed.');
            }

            $is_refund_exist = $this->isTransactionExistByRoundAndAction($player_id, $transaction['data']['round_id'], $transaction['data']['action_id'], self::TRANSACTION_REFUND);

            if ($is_refund_exist && $this->check_by_round_and_action) {	
                return array('success' => false, 'message' => 'Payout cannot process. Bet already settled/with refund.');
            }
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForTriggerInternalPayoutRound',
            'gameUsername' => $game_username,
            'transaction' => $transaction
        );

        $params = $transaction;
        $this->http_method = self::HTTP_METHOD_POST;

        return $this->callApi(self::API_triggerInternalPayoutRound, $params, $context);
    }

    public function processResultForTriggerInternalPayoutRound($params)
    {
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('EVOPLAY SEAMLESS (processResultForTriggerInternalPayoutRound)', $params, $resultArr);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $transaction = $this->getVariableFromContext($params, 'transaction');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode, $gameUsername);

        $result = array(
            'gameUsername' => $gameUsername,
            'transaction' => $transaction,
            'status' => null,
            'exists' => null,
            'triggered_payout' => false,
            'success' => false,
        );

        if ($success && $resultArr['status'] == self::RESPONSE_STATUS_OK) {
            $result['status'] = true;
            $result['triggered_payout'] = true;
            $result['success'] = true;
        }

        $result['message'] = isset($resultArr['error']['message']) ? $resultArr['error']['message'] : '';

        return $result;
    }

    public function triggerInternalRefundRound($transaction)
    {
        //API_triggerInternalRefundRound
        $this->CI->utils->debug_log('EVOPLAY SEAMLESS (triggerInternalRefundRound)', 'transaction', $transaction);

        //check if parameters complete
        if (!is_array($transaction)) {
            $transaction = json_decode($transaction, true);
        }

        //check other data needed if complete
        if (!isset($transaction['token'])) {
            return array('success' => false, 'message' => 'Missing token.');
        }

        if (!isset($transaction['callback_id'])) {
            return array('success' => false, 'message' => 'Missing callback_id.');
        }

        if (!isset($transaction['name'])) {
            return array('success' => false, 'message' => 'Missing callback name.');
        }

        if (!isset($transaction['data'])) {
            return array('success' => false, 'message' => 'Missing data.');
        }

        if (!isset($transaction['data']['refund_round_id'])) {
            return array('success' => false, 'message' => 'Missing data.refund_round_id.');
        }

        if (!isset($transaction['data']['refund_action_id'])) {
            return array('success' => false, 'message' => 'Missing data.refund_action_id.');
        }

        if (!isset($transaction['data']['refund_callback_id'])) {
            return array('success' => false, 'message' => 'Missing data.refund_callback_id.');
        }

        if (!isset($transaction['data']['amount'])) {
            return array('success' => false, 'message' => 'Missing data.amount.');
        }

        if (!isset($transaction['data']['currency'])) {
            return array('success' => false, 'message' => 'Missing data.currency.');
        }

        if (!isset($transaction['signature'])) {
            return array('success' => false, 'message' => 'Missing signature.');
        }

        if ($this->use_signature) {
            $is_valid_signature = $this->isValidCallbackSignature($transaction);

            if (!$is_valid_signature) {
                return array('success' => false, 'message' => 'Invalid signature.');
            }
        }

        $player_id = null;
        $game_username = null;

        if (!isset($transaction['off_bet_token_checking'])) {
            $bet_transactions = $this->queryTransactionsByTypeAndRound(self::TRANSACTION_BET, $transaction['data']['refund_round_id']);

            if (!empty($bet_transactions)) {
                foreach ($bet_transactions as $bet_transaction) {
                    $extra_info = isset($bet_transaction['extra_info']) ? json_decode($bet_transaction['extra_info'], true) : null;
                    $token = isset($extra_info['token']) ? $extra_info['token'] : null;

                    if ($transaction['token'] == $token) {
                        $player_id = isset($bet_transaction['player_id']) ? $bet_transaction['player_id'] : null;
                        $game_username = $this->getGameUsernameByPlayerId($player_id);
                        break;
                    }
                }
            } else {
                return array('success' => false, 'message' => 'Bet not exist.');
            }

            if (empty($player_id) || empty($game_username)) {
                return array('success' => false, 'message' => 'Invalid Token');
            }

            $is_transacton_exist = $this->isTransactionExist($transaction['callback_id'] . '-' . $transaction['data']['refund_callback_id']);

            if ($is_transacton_exist) {
                return array('success' => false, 'message' => 'Refund already processed. Double refund is not allowed.');
            }

            $is_transaction_exist_by_action_id = $this->isTransactionExistByRoundAndAction($player_id, $transaction['data']['refund_round_id'], $transaction['data']['refund_action_id'], self::TRANSACTION_REFUND);

            if ($is_transaction_exist_by_action_id && $this->check_by_round_and_action) {
                return array('success' => false, 'message' => 'Refund already processed. Double refund is not allowed.');
            }

            $is_win_exist = $this->isTransactionExistByRoundAndAction($player_id, $transaction['data']['refund_round_id'], $transaction['data']['refund_action_id'], self::TRANSACTION_WIN);

            if ($is_win_exist && $this->check_by_round_and_action) {	
                return array('success' => false, 'message' => 'Refund cannot process. Bet already settled/with payout.');
            }
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForTriggerInternalRefundRound',
            'gameUsername' => $game_username,
            'transaction' => $transaction
        );

        $params = $transaction;
        $this->http_method = self::HTTP_METHOD_POST;

        return $this->callApi(self::API_triggerInternalRefundRound, $params, $context);
    }

    public function processResultForTriggerInternalRefundRound($params)
    {
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('EVOPLAY SEAMLESS (processResultForTriggerInternalRefundRound)', $params, $resultArr);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $transaction = $this->getVariableFromContext($params, 'transaction');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode, $gameUsername);

        $result = array(
            'gameUsername' => $gameUsername,
            'transaction' => $transaction,
            'status' => null,
            'exists' => null,
            'triggered_refund' => false,
            'success' => false,
        );

        if ($success && $resultArr['status'] == self::RESPONSE_STATUS_OK) {
            $result['status'] = true;
            $result['triggered_refund'] = true;
            $result['success'] = true;
        }

        $result['message'] = isset($resultArr['error']['message']) ? $resultArr['error']['message'] : '';

        return $result;
    }

    public function manualFixMissingPayoutFormat()
    {
        return '{
            "data": {
              "amount": "5",
              "details": "{\"game\":{\"absolute_name\":\"socketgames\\\\evoplay\\\\highstriker\",\"game_id\":5344,\"version\":\"-\",\"action\":\"spin\",\"action_id\":\"28020555\"},\"details\":{\"event_id\":28020555,\"data\":{\"round_id\":2098634,\"state\":\"process\"},\"time\":1604053690408,\"date\":\"2020-10-30\",\"type\":\"gameaction\",\"system_id\":1465},\"denomination\":1,\"currency_rate\":{\"currency\":\"USD\",\"rate\":1},\"provider_id\":\"417\",\"total_bet\":1,\"bet\":\"1\",\"total_win\":1.07,\"game_mode_code\":0,\"round\":{\"game_bet\":1}}",
              "currency": "BRL",
              "round_id": "testround3",
              "action_id": "socketgames_1_1019754924_28020555",
              "final_action": "1"
            },
            "name": "win",
            "token": "WUtzeHZjc0RvM0JvcHNneWtzcklOZz09",
            "signature": "dafac5ce4a3fe2883bf9a3916fd2ed72",
            "callback_id": "dp7mkkx2ebqzb"
          }';
    }

    public function manualFixMissingRefundFormat()
    {
        return '{
            "data": {
              "amount": "5",
              "details": "{\"game\":{\"absolute_name\":\"socketgames\\\\evoplay\\\\highstriker\",\"game_id\":5344,\"version\":\"-\",\"action\":\"spin\",\"action_id\":\"28020555\"},\"details\":{\"event_id\":28020555,\"data\":{\"round_id\":2098634,\"state\":\"process\"},\"time\":1604053690408,\"date\":\"2020-10-30\",\"type\":\"gameaction\",\"system_id\":1465},\"denomination\":1,\"currency_rate\":{\"currency\":\"USD\",\"rate\":1},\"provider_id\":\"417\",\"total_bet\":1,\"bet\":\"1\",\"total_win\":1.07,\"game_mode_code\":0,\"round\":{\"game_bet\":1}}",
              "currency": "BRL",
              "refund_round_id": "testround3",
              "refund_action_id": "socketgames_1_1019754924_28020555",
              "refund_callback_id": "testcallbackid3"
            },
            "name": "refund",
            "token": "WUtzeHZjc0RvM0JvcHNneWtzcklOZz09",
            "signature": "4821c11f1309e48257cd56f22883f945",
            "callback_id": "8gjfytbnlkm161"
          }';
    }

    public function formatBetDetails($extra_info=null){
        $decoded_info = $extra_info;
        if ($decoded_info !== null && isset($decoded_info['data']['details'])) {
            $nested_details = $decoded_info['data']['details'];
            $this->CI->utils->debug_log('EVOPLAY-formatBetDetails-nested_details1',$nested_details);
            $nested_details = str_replace('\\', '', $nested_details);
            $this->CI->utils->debug_log('EVOPLAY-formatBetDetails-nested_details2',$nested_details);

            $decoded_info['data']['details'] = json_decode($nested_details, true);
        }
        $details = $decoded_info['data']['details'];
        $bet_details = [];

        $freespin = isset($details['freespin']) ? $details['freespin'] : null;

        if($extra_info){
            $bet_details = [
                'win_amount'    => isset($details['total_win']) ? $details['total_win'] : null,
                'bet_amount'   => isset($details['total_bet']) ? $details['total_bet'] : null,
                'action'        => isset($details['game']['action']) ? $details['game']['action'] : null,
                'round_id'     => isset($decoded_info['data']['round_id']) ? $decoded_info['data']['round_id'] : null,
                'is_free_spin'  => !is_null($freespin) ? true : false,
                'extra_info'    => $decoded_info,
            ]; 
            return $bet_details;
        }
        return $bet_details;
    }

    #OGP-34427
    public function getProviderAvailableLanguage() {
        return $this->getSystemInfo('provider_available_langauge', ['en','zh-cn','id-id','vi-vi','th-th','pt']);
    }
}
//end of class