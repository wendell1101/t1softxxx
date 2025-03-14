<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Game Provider: BGAMING
 * Game Type: Slots
 * Wallet Type: Seamless
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2013-2022 tot
 * @integrator @melvin.php.ph

    Related File
    -routes.php
    -bgaming_seamless_service_api.php
 **/

class Game_api_bgaming_seamless extends Abstract_game_api
{
    public $casino_id;
    public $auth_token;
    public $gcp_url;
    public $script_url;
    public $use_js_game_launch;
    public $country;
    public $language;
    public $currency;
    public $prefix_for_username;
    public $return_url;
    public $deposit_url;
    public $sync_time_interval;
    public $sleep_time;
    public $http_method;
    public $seamless_service_callback_url;
    public $original_seamless_game_logs_table;
    public $original_seamless_wallet_transactions_table;
    public $show_request_params_guide;
    public $use_transaction_data;
    public $precision;
    public $conversion;
    public $CI;
    public $enable_merging_rows;
    public $dev_internal_password;

    const SEAMLESS_GAME_API_NAME = 'BGAMING_SEAMLESS_GAME_API';

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const LANGUAGE_CODE_ENGLISH = 'en';
    const LANGUAGE_CODE_CHINESE = 'zh-cn';
    const LANGUAGE_CODE_INDONESIAN = 'id';
    const LANGUAGE_CODE_VIETNAMESE = 'vi';
    const LANGUAGE_CODE_KOREAN = 'ko';
    const LANGUAGE_CODE_THAI = 'th';
    const LANGUAGE_CODE_HINDI = 'hi';
    const LANGUAGE_CODE_PORTUGUESE = 'pt';

    const COUNTRY_CODE_US = 'US';
    const COUNTRY_CODE_CN = 'CN';
    const COUNTRY_CODE_ID = 'ID';
    const COUNTRY_CODE_VN = 'VN';
    const COUNTRY_CODE_KR = 'KR';
    const COUNTRY_CODE_TH = 'TH';
    const COUNTRY_CODE_IN = 'IN';
    const COUNTRY_CODE_BR = 'BR';

    const CALLBACK_METHOD_PLAY = 'play';
    const CALLBACK_METHOD_ROLLBACK = 'rollback';
    const CALLBACK_METHOD_FREESPINS = 'freespins';

    const ALLOWED_CALLBACK_METHODS = [
        self::CALLBACK_METHOD_PLAY,
        self::CALLBACK_METHOD_ROLLBACK,
        self::CALLBACK_METHOD_FREESPINS,
    ];

    const TRANSACTION_TYPE_BET = 'bet';
    const TRANSACTION_TYPE_WIN = 'win';
    const TRANSACTION_TYPE_ROLLBACK = 'rollback';

    const TRANSACTION_TYPES = [
        self::TRANSACTION_TYPE_BET,
        self::TRANSACTION_TYPE_WIN,
        self::TRANSACTION_TYPE_ROLLBACK,
    ];

    const FLAG_NOT_UPDATED = 0;
    const FLAG_UPDATED_FOR_GAME_LOGS = 1;
    const FLAG_UPDATED = 2;
    const FLAG_RETAIN = 3;

    const API_freespinsIssue = 'freespinsIssue';
    const API_freespinsCancel = 'freespinsCancel';

    const URI_MAP = [
        self::API_queryDemoGame => '/demo',
        self::API_queryForwardGame => '/sessions',
        self::API_freespinsIssue => '/freespins/issue',
        self::API_freespinsCancel => '/freespins/cancel',
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

    public function __construct()
    {
        parent::__construct();
        $this->CI->load->model(['original_game_logs_model']);
        $this->original_seamless_wallet_transactions_table = $this->getSystemInfo('original_seamless_wallet_transactions_table', 'bgaming_seamless_wallet_transactions');
        $this->original_seamless_game_logs_table = $this->getSystemInfo('original_seamless_game_logs_table', 'bgaming_seamless_game_logs');
        $this->seamless_service_callback_url = $this->getSystemInfo('seamless_service_callback_url', '');
        $this->http_method = self::HTTP_METHOD_GET;
        $this->casino_id = $this->getSystemInfo('casino_id');
        $this->auth_token = $this->getSystemInfo('auth_token');
        $this->gcp_url = $this->getSystemInfo('gcp_url');
        $this->script_url = $this->getSystemInfo('script_url', '');
        $this->use_js_game_launch = $this->getSystemInfo('use_js_game_launch', false);
        $this->country = $this->getSystemInfo('country', '');
        $this->language = $this->getSystemInfo('language');
        $this->currency = $this->getSystemInfo('currency');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->return_url = $this->getSystemInfo('return_url', $this->getHomeLink());
        $this->deposit_url = $this->getSystemInfo('deposit_url');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+2 minutes'); //minutes/hours/days
        $this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
        $this->show_request_params_guide = $this->getSystemInfo('show_request_params_guide', false);
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data', true);
        $this->precision = $this->getSystemInfo('precision', 0); // default 0 because balance response must be a whole number
        $this->conversion = $this->getSystemInfo('conversion', 1);
        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
        $this->dev_internal_password = $this->getSystemInfo('dev_internal_password', "");
    }

    public function getPlatformCode()
    {
        return BGAMING_SEAMLESS_GAME_API;
    }

    public function getTransactionsTable(){
        return $this->getSeamlessTransactionTable();
    }

    public function isSeamLessGame()
    {
        return true;
    }

    public function getSeamlessTransactionTable()
    {
        return $this->original_seamless_wallet_transactions_table;
    }

    public function getSeamlessGameLogsTable()
    {
        return $this->original_seamless_game_logs_table;
    }

    public function generateUrl($api_name, $params)
    {
        $api_uri = self::URI_MAP[$api_name];
        $url = $this->gcp_url . $api_uri;

        if ($this->http_method == self::HTTP_METHOD_GET) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    public function generateRequestSign($params, $auth_token, $algo = 'sha256')
    {
        return hash_hmac($algo, $params, $auth_token);
    }

    protected function getHttpHeaders($params)
    {
        $headers = [
            'X-REQUEST-SIGN' => $this->generateRequestSign(json_encode($params), $this->auth_token),
            'Content-type' => 'application/json',
        ];

        return $headers;
    }

    protected function customHttpCall($ch, $params)
    {
        if ($this->http_method == self::HTTP_METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }

    public function processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName = null)
    {
        $success = false;

        if ($statusCode == 200 || $statusCode == 201) {
            $success = true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->utils->debug_log(self::SEAMLESS_GAME_API_NAME . ' API got error ', $responseResultId, 'statusCode', $statusCode, 'playerName', $playerName, 'result', $resultArr);
        }

        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null)
    {
        $createPlayer = parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = 'Unable to create account for ' . self::SEAMLESS_GAME_API_NAME;

        if ($createPlayer) {
            $success = true;
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $message = 'Successfully create account for ' . self::SEAMLESS_GAME_API_NAME;
        }

        $result = [
            'success' => $success, 
            'message' => $message,
        ];

        return $result;
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id = null)
    {
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

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null)
    {
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

    public function queryPlayerBalance($player_id)
    {
        $this->CI->load->model(['player_model']);
        $success = true;
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($player_id, $this->getPlatformCode());

        return array($success, $balance);
    }

    public function queryTransaction($transactionId, $extra)
    {
        return $this->returnUnimplemented();
    }

    public function getLauncherLanguage($language)
    {
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en':
            case 'en-us':
            case 'en-US':
                $lang = self::LANGUAGE_CODE_ENGLISH;
                $country = self::COUNTRY_CODE_US;
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh':
            case 'cn':
            case 'zh-CN':
                $lang = self::LANGUAGE_CODE_CHINESE;
                $country = self::COUNTRY_CODE_CN;
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id':
            case 'id-ID':
                $lang = self::LANGUAGE_CODE_INDONESIAN;
                $country = self::COUNTRY_CODE_ID;
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi':
            case 'vi-VN':
                $lang = self::LANGUAGE_CODE_VIETNAMESE;
                $country = self::COUNTRY_CODE_VN;
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko':
            case 'ko-KR':
                $lang = self::LANGUAGE_CODE_KOREAN;
                $country = self::COUNTRY_CODE_KR;
                break;
            case Language_function::INT_LANG_THAI:
            case 'th':
            case 'th-TH':
                $lang = self::LANGUAGE_CODE_THAI;
                $country = self::COUNTRY_CODE_TH;
                break;
            case Language_function::INT_LANG_INDIA:
            case 'hi':
            case 'hi-IN':
                $lang = self::LANGUAGE_CODE_HINDI;
                $country = self::COUNTRY_CODE_IN;
                break;
            case Language_function::INT_LANG_PORTUGUESE:
            case 'pt-br':
            case 'pt-BR':
            case 'pt':
                $lang = self::LANGUAGE_CODE_PORTUGUESE;
                $country = self::COUNTRY_CODE_BR;
                break;
            default:
                $lang = self::LANGUAGE_CODE_ENGLISH;
                $country = self::COUNTRY_CODE_US;
                break;
        }

        return array($lang, $country);
    }

    public function queryForwardGame($playerName, $extra = null)
    {
        $this->CI->load->model(['player_model', 'game_provider_auth']);
        $this->http_method = self::HTTP_METHOD_POST;
        $game_username = $this->getGameUsernameByPlayerUsername($playerName);
        $player_id = $this->getPlayerIdByGameUsername($game_username);
        $player_info = $this->CI->player_model->getPlayerInfoById($player_id);
        $registered_at = $this->CI->game_provider_auth->getPlayerCreatedAt($this->getPlatformCode(), $player_id);
        $game_code = !empty($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = !empty($extra['game_mode']) ? $extra['game_mode'] : null;
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];
        list($lang, $country) = $this->getLauncherLanguage($extra['language']);
        $language = !empty($this->language) ? $this->language : $lang;
        list($queryPlayerBalance_success, $player_balance) = $this->queryPlayerBalance($player_id);

        if (!empty($extra['home_link'])) {
            $return_url = $extra['home_link'];
        } elseif (!empty($extra['extra']['home_link'])) {
            $return_url = $extra['extra']['home_link'];
        } else {
            $return_url = $this->return_url;
        }

        if (!empty($extra['cashier_link'])) {
            $deposit_url = $extra['cashier_link'];
        } elseif (!empty($extra['extra']['cashier_link'])) {
            $deposit_url = $extra['extra']['cashier_link'];
        } else {
            $deposit_url = !empty($this->deposit_url) ? $this->deposit_url : $return_url;
        }

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'player_name' => $playerName,
            'game_username' => $game_username,
            'game_mode' => $game_mode,
        ];

        if ($is_mobile) {
            $client_type = 'mobile';
        } else {
            $client_type = 'desktop';
        }

        if (!empty($player_info['gender'])) {
            if (strtolower($player_info['gender']) == 'male') {
                $gender = 'm';
            } elseif (strtolower($player_info['gender']) == 'female') {
                $gender = 'f';
            } else {
                $gender =  null;
            }
        } else {
            $gender =  null;
        }

        $params = [
            'casino_id' => $this->casino_id,
            'game' => $game_code,
            'locale' => $language,
            'ip' => $this->utils->getIP(),
            'client_type' => $client_type,
            'jurisdiction' => $country,
        ];

        if (isset($extra['extra']['home_link'])) {
            $return_url = $extra['extra']['home_link'];
        }

        if ($game_mode == 'real') {
            $api_name = self::API_queryForwardGame;
            $params['currency'] = $this->currency;
            $params['balance'] = $queryPlayerBalance_success ? $this->convert_balance($player_balance, $this->conversion, $this->precision) : 0;

            $params['urls'] = [
                'return_url' => $return_url,
                'deposit_url' => $deposit_url,
            ];

            $params['user'] = [
                'id' => $game_username,
                'email' => !empty($player_info['email']) ? $player_info['email'] : null,
                'firstname' => !empty($player_info['firstName']) ? $player_info['firstName'] : null,
                'lastname' => !empty($player_info['lastName']) ? $player_info['lastName'] : null,
                'nickname' => $game_username,
                'city' => !empty($player_info['city']) ? $player_info['city'] : null,
                'date_of_birth' => !empty($player_info['birthdate']) ? date('Y-m-d', strtotime($player_info['birthdate'])) : null,
                'registered_at' => !empty($registered_at) ? date('Y-m-d', strtotime($registered_at)) : null,
                'gender' => $gender,
                'country' => $this->country,
            ];
        } else {
            $api_name = self::API_queryDemoGame;
            $params['urls'] = [
                'return_url' => $return_url,
            ];
        }
        if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
            $params['urls']['return_url'] = "";
        }
        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'params', $params);

        return $this->callApi($api_name, $params, $context);
    }

    public function processResultForQueryForwardGame($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName);

        $result = [
            'responseResultId' => $responseResultId,
        ];

        if ($success && isset($resultArr['launch_options'])) {
            $result['url'] = !empty($resultArr['launch_options']['game_url']) ? $resultArr['launch_options']['game_url'] : null;
            $result['strategy'] = !empty($resultArr['launch_options']['strategy']) ? $resultArr['launch_options']['strategy'] : null;
        }

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'result', $resultArr);

        return [$success, $result];
    }

    public function convert_balance($amount, $conversion = 1, $precision = 2)
    {
        /* $truncate_amount = floatval(bcdiv($amount, 1, $precision));
        return $truncate_amount * $conversion; */

        $value = floatval($amount * $conversion);
        return floatval(bcdiv($value, 1, $precision));
    }

    public function freespinsIssue($playerName, $issue_id, array $games, $freespins_quantity, $bet_level = null, $valid_until)
    {
        $this->CI->load->model(['player_model', 'game_provider_auth']);
        $this->http_method = self::HTTP_METHOD_POST;
        $game_username = $this->getGameUsernameByPlayerUsername($playerName);
        $player_id = $this->getPlayerIdByGameUsername($game_username);
        $player_info = $this->CI->player_model->getPlayerInfoById($player_id);
        $registered_at = $this->CI->game_provider_auth->getPlayerCreatedAt($this->getPlatformCode(), $player_id);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForFreespinsIssue',
            'playerName' => $playerName,
        ];

        $params = [
            'casino_id' => $this->casino_id,
            'issue_id' => $issue_id,
            'currency' => $this->currency,
            'games' => $games,
            'freespins_quantity' => $freespins_quantity,
            'bet_level' => $bet_level,
            'valid_until' => $valid_until,
            'user' => [
                'id' => $game_username,
                'email' => !empty($player_info['email']) ? $player_info['email'] : null,
                'firstname' => !empty($player_info['firstName']) ? $player_info['firstName'] : null,
                'lastname' => !empty($player_info['lastName']) ? $player_info['lastName'] : null,
                'nickname' => !empty($player_info['username']) ? $player_info['username'] : null,
                'city' => !empty($player_info['city']) ? $player_info['city'] : null,
                'date_of_birth' => !empty($player_info['birthdate']) ? $player_info['birthdate'] : null,
                'registered_at' => date('Y-m-d', strtotime($registered_at)),
                'gender' => !empty($player_info['gender']) ? $player_info['gender'] : null,
                'country' => !empty($player_info['country']) ? $player_info['country'] : null,
            ],
        ];

        return $this->callApi(self::API_freespinsIssue, $params, $context);
    }

    public function processResultForFreespinsIssue($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName);

        $result = [
            'responseResultId' => $responseResultId,
        ];

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'result', $resultArr);

        return [$success, $result];
    }

    public function freespinsCancel($playerName = null, $issue_id)
    {
        $this->http_method = self::HTTP_METHOD_POST;

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForFreespinsCancel',
            'playerName' => $playerName,
        ];

        $params = [
            'casino_id' => $this->casino_id,
            'issue_id' => $issue_id,
        ];

        return $this->callApi(self::API_freespinsIssue, $params, $context);
    }

    public function processResultForFreespinsCancel($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode, $playerName);

        $result = [
            'responseResultId' => $responseResultId,
        ];

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'result', $resultArr);

        return [$success, $result];
    }

    public function syncOriginalGameLogs($token = false)
    {
        return $this->returnUnimplemented();
    }

    public function syncOriginalGameLogsFromTrans($token = false)
    {
        $this->CI->load->model(['original_seamless_wallet_transactions']);
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate->modify($this->getDatetimeAdjust());
        $queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
        $queryDateTimeEnd = $endDate->format('Y-m-d H:i:s');
        $transactions = $this->queryTransactionsForUpdate($queryDateTimeStart, $queryDateTimeEnd);

        if (!empty($transactions) && is_array($transactions)) {
            foreach ($transactions as $transaction) {
                if (array_key_exists('transaction_type', $transaction)) {
                    $validated_transaction = [
                        'type' => !empty($transaction['transaction_type']) ? $transaction['transaction_type'] : null,
                        'id' => !empty($transaction['transaction_id']) ? $transaction['transaction_id'] : null,
                        'player_id' => !empty($transaction['player_id']) ? $transaction['player_id'] : null,
                        'game_code' => !empty($transaction['game_code']) ? $transaction['game_code'] : null,
                        'round_id' => !empty($transaction['round_id']) ? $transaction['round_id'] : null,
                        'amount' => !empty($transaction['amount']) ? $transaction['amount'] : 0,
                        'start_at' => !empty($transaction['start_at']) ? $transaction['start_at'] : null,
                        'status' => !empty($transaction['status']) ? $transaction['status'] : null,
                        'updated_at' => !empty($transaction['updated_at']) ? $transaction['updated_at'] : null,
                        'external_unique_id' => !empty($transaction['external_unique_id']) ? $transaction['external_unique_id'] : null,
                        'rollback_action' => !empty($transaction['rollback_action']) ? $transaction['rollback_action'] : null,
                        'rollback_original_action_id' => !empty($transaction['rollback_original_action_id']) ? $transaction['rollback_original_action_id'] : null,
                    ];

                    extract($validated_transaction, EXTR_PREFIX_ALL, 'transaction');

                    $bet_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_BET, $transaction_player_id, $transaction_game_code, $transaction_round_id);
                    $win_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_WIN, $transaction_player_id, $transaction_game_code, $transaction_round_id);

                    if ($transaction_type == self::TRANSACTION_TYPE_BET) {
                        $bet_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_BET, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id);
                    }

                    if ($transaction_type == self::TRANSACTION_TYPE_WIN) {
                        $bet_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_BET, $transaction_player_id, $transaction_game_code, $transaction_round_id, null, false);
                    }

                    /* if ($transaction_type == self::TRANSACTION_TYPE_BET) {
                        $bet_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_BET, $transaction_player_id, $transaction_game_code, $transaction_round_id, $transaction_id);
                    } elseif ($transaction_type == self::TRANSACTION_TYPE_WIN) {
                        $bet_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_BET, $transaction_player_id, $transaction_game_code, $transaction_round_id, null, false);
                    } else {
                        $bet_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_BET, $transaction_player_id, $transaction_game_code, $transaction_round_id);
                    }

                    $win_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_WIN, $transaction_player_id, $transaction_game_code, $transaction_round_id); */
                    $rollback_bet_transaction = $this->queryPlayerRollbackTransaction(self::TRANSACTION_TYPE_ROLLBACK, $transaction_player_id, $transaction_game_code, $transaction_round_id, self::TRANSACTION_TYPE_BET);
                    $rollback_win_transaction = $this->queryPlayerRollbackTransaction(self::TRANSACTION_TYPE_ROLLBACK, $transaction_player_id, $transaction_game_code, $transaction_round_id, self::TRANSACTION_TYPE_WIN);

                    /* if (!$this->enable_merging_rows) {
                        $win_transaction = $this->queryPlayerTransaction(self::TRANSACTION_TYPE_WIN, $transaction_player_id, $transaction_game_code, $transaction_round_id, null, false);
                    } */

                    $validated_bet_transaction = [
                        'amount' => !empty($bet_transaction['amount']) ? $bet_transaction['amount'] : 0,
                        'external_unique_id' => !empty($bet_transaction['external_unique_id']) ? $bet_transaction['external_unique_id'] : null,
                    ];

                    $validated_win_transaction = [
                        'amount' => !empty($win_transaction['amount']) ? $win_transaction['amount'] : 0,
                        'external_unique_id' => !empty($win_transaction['external_unique_id']) ? $win_transaction['external_unique_id'] : null,
                    ];

                    $validated_rollback_bet_transaction = [
                        'amount' => !empty($rollback_bet_transaction['amount']) ? $rollback_bet_transaction['amount'] : 0,
                        'external_unique_id' => !empty($rollback_bet_transaction['external_unique_id']) ? $rollback_bet_transaction['external_unique_id'] : null,
                        'action' => !empty($rollback_bet_transaction['rollback_action']) ? $rollback_bet_transaction['rollback_action'] : null,
                        'original_action_id' => !empty($transaction['rollback_original_action_id']) ? $transaction['rollback_original_action_id'] : null,
                    ];

                    $validated_rollback_win_transaction = [
                        'amount' => !empty($rollback_win_transaction['amount']) ? $rollback_win_transaction['amount'] : 0,
                        'external_unique_id' => !empty($rollback_win_transaction['external_unique_id']) ? $rollback_win_transaction['external_unique_id'] : null,
                        'action' => !empty($rollback_win_transaction['rollback_action']) ? $rollback_win_transaction['rollback_action'] : null,
                        'original_action_id' => !empty($transaction['rollback_original_action_id']) ? $transaction['rollback_original_action_id'] : null,
                    ];

                    extract($validated_bet_transaction, EXTR_PREFIX_ALL, 'bet');
                    extract($validated_win_transaction, EXTR_PREFIX_ALL, 'win');
                    extract($validated_rollback_bet_transaction, EXTR_PREFIX_ALL, 'rollback_bet');
                    extract($validated_rollback_win_transaction, EXTR_PREFIX_ALL, 'rollback_win');

                    switch ($transaction_type) {
                        case self::TRANSACTION_TYPE_BET:
                            $bet_data = [
                                'status' => Game_logs::STATUS_SETTLED,
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' => -$bet_amount,
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

                            if (!$this->enable_merging_rows) {
                                $win_amount = $transaction_amount;

                                $win_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                $win_data['bet_amount'] = 0;
                                $win_data['win_amount'] = $win_amount;
                                $win_data['result_amount'] = $win_amount;

                                $bet_data['bet_amount'] = $bet_amount;
                                $bet_data['win_amount'] = 0;
                                $bet_data['result_amount'] = -$bet_amount;
                            }

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $win_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
                            break;
                        case self::TRANSACTION_TYPE_ROLLBACK:
                            $rollback_data = [
                                'status' => Game_logs::STATUS_REFUND,
                                'flag_of_updated_result' => self::FLAG_UPDATED,
                            ];

                            if ($transaction_rollback_action == self::TRANSACTION_TYPE_BET) {
                                $status = Game_logs::STATUS_REFUND;
                                $result_amount = $rollback_bet_amount;
                            } else {
                                if (empty($rollback_bet_amount)) {
                                    $status = Game_logs::STATUS_SETTLED;
                                    $result_amount = -$bet_amount;
                                } else {
                                    $status = Game_logs::STATUS_REFUND;
                                    $result_amount = $bet_amount;

                                    $rollback_win_data = [
                                        'status' => $status,
                                        'flag_of_updated_result' => self::FLAG_UPDATED,
                                    ];

                                    if (!$this->enable_merging_rows) {
                                        $rollback_win_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                        $rollback_win_data['result_amount'] = $rollback_win_amount;
                                    }

                                    $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $rollback_win_data, 'transaction_id', $rollback_win_original_action_id);
                                }
                            }

                            $bet_data = [
                                'status' => $status,
                                'bet_amount' => $bet_amount,
                                'win_amount' => 0,
                                'result_amount' =>  $result_amount,
                                'flag_of_updated_result' => self::FLAG_UPDATED_FOR_GAME_LOGS,
                            ];

                            if (!$this->enable_merging_rows) {
                                $rollback_data['flag_of_updated_result'] = self::FLAG_UPDATED_FOR_GAME_LOGS;
                                $rollback_data['result_amount'] = $rollback_bet_amount;

                                $bet_data['bet_amount'] = $bet_amount;
                                $bet_data['win_amount'] = 0;
                                $bet_data['result_amount'] = -$bet_amount;
                            }

                            $bet_data['md5_sum'] = $this->CI->original_seamless_wallet_transactions->generateMD5SumOneRow($bet_data, self::MD5_FIELDS_FOR_ORIGINAL_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FROM_TRANS);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $rollback_data, 'external_unique_id', $transaction_external_unique_id);
                            $this->CI->original_seamless_wallet_transactions->updateTransactionDataWithResult($this->original_seamless_wallet_transactions_table, $bet_data, 'external_unique_id', $bet_external_unique_id);
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

    public function queryTransactionsForUpdate($dateFrom, $dateTo, $transaction_type = null)
    {
        $sqlTime = 'start_at >= ? AND end_at <= ?';

        if (!empty($transaction_type)) {
            $and_transaction_type = 'AND transaction_type = ?';
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
updated_at,
rollback_action,
rollback_original_action_id
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

        // $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function queryPlayerTransaction($transaction_type, $player_id, $game_code, $round_id, $transaction_id = null, $is_sum = true)
    {
        if (!empty($transaction_id)) {
            $and_transaction_id = 'AND transaction_id = ?';
        } else {
            $and_transaction_id = '';
        }

        $amount = $is_sum ? 'SUM(amount) as amount' : 'amount';

        $sql = <<<EOD
SELECT DISTINCT 
player_id,
id,
{$amount},
status,
extra_info,
external_unique_id,
rollback_action,
rollback_original_action_id
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

        // $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $results = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $results;
    }

    public function queryPlayerRollbackTransaction($transaction_type, $player_id, $game_code, $round_id, $rollback_action)
    {
        $sql = <<<EOD
SELECT DISTINCT 
player_id,
id,
sum(amount) as amount,
status,
extra_info,
external_unique_id,
rollback_action,
rollback_original_action_id
FROM {$this->original_seamless_wallet_transactions_table}
WHERE game_platform_id = ? AND transaction_type = ? AND player_id = ? AND game_code = ? AND round_id = ? AND rollback_action = ?
EOD;

        $params = [
            $this->getPlatformCode(),
            $transaction_type,
            $player_id,
            $game_code,
            $round_id,
            $rollback_action,
        ];

        // $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
        $results = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $results;
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
     * queryOriginalGameLogsFromTrans
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
transaction.amount,
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

        $this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'sql', $sql, 'params', $params);
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
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE_FROM_TRANS, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE_FROM_TRANS);
        }

        $result_amount = !empty($row['result_amount']) ? $row['result_amount'] : 0;
        if(!$this->enable_merging_rows){
            if($row['transaction_type'] == 'bet'){
                $win_amount = 0;
                $bet_amount = $row['amount'];
                $result_amount = $win_amount - $bet_amount;
            }

            if($row['transaction_type'] == 'win'){
                $result_amount = $row['amount'];
            }
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
                'result_amount' => $result_amount,
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
                'updated_at' => $this->utils->getNowForMysql(),
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
            'bet_details' => $this->preprocessBetDetails($row,null,true),
            'extra' => [
                'note' => !empty($row['note']) ? $row['note'] : null,
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id' => isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        //$this->utils->debug_log(__METHOD__, self::SEAMLESS_GAME_API_NAME, 'data', $data);

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

        $result_amount = !empty($row['result_amount']) ? $row['result_amount'] : 0;
        
        if ($this->enable_merging_rows) {
            // $row['after_balance'] += $row['win_amount'];
            $row['after_balance'] = $this->getAfterBalance($row['player_id'], $row['game_code'], $row['round_number']);
        }

        $status = !empty($row['status']) ? $row['status'] : null;

        if ($status == Game_logs::STATUS_SETTLED) {
            if ($this->enable_merging_rows) {
                if ($result_amount > 0) {
                    $row['note'] = 'Win';
                } elseif ($result_amount < 0) {
                    $row['note'] = 'Lose';
                } elseif ($result_amount == 0) {
                    $row['note'] = 'Draw';
                } else {
                    $row['note'] = 'Free Game';
                }
            } else {
                $row['note'] = $row['transaction_type'];
            }
        } elseif ($status == Game_logs::STATUS_REFUND) {
            $row['note'] = 'Refund';
        } else {
            $row['note'] = 'Unknown';
        }
    }

    private function getGameDescriptionInfo($row, $unknownGame)
    {
        $game_code = !empty($row['game_code']) ? $row['game_code'] : null;
        $game_type_id = !empty($row['game_type_id']) ? $row['game_type_id'] : $unknownGame->game_type_id;

        if (!empty($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
        } else {
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $game_code, $game_code);
        }

        return array($game_description_id, $game_type_id);
    }

    public function defaultBetDetailsFormat($row) {
        $bet_details = [];

        if (isset($row['game'])) {
            $bet_details['game_name'] = $row['game'];
        }
        if (isset($row['round_number'])) {
            $bet_details['round_id'] = $row['round_number'];
        }
        if (isset($row['transaction_id'])) {
            $bet_details['bet_id'] = $row['transaction_id'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['bet_amount'];
        }
        if (isset($row['win_amount'])) {
            $bet_details['win_amount'] = $row['win_amount'];
        }

        if (isset($row['bet_at'])) {
            $bet_details['betting_datetime'] = $row['bet_at'];
        }

        if (isset($row['bet_details'])) {
            $bet_details['extra '] = $row['bet_details'];
        }

        return $bet_details;
     }

     public function getAfterBalance($player_id, $game_code, $round_id) {
        $this->CI->load->model(['original_seamless_wallet_transactions']);

        $transactions = $this->CI->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($this->original_seamless_wallet_transactions_table, [
            'player_id' => $player_id,
            'game_code' => $game_code,
            'round_id' => $round_id,
        ]);
        
        if (count($transactions) > 1) {
            $transactions = end($transactions);

            $after_balance = $transactions['after_balance'];
        } else {
            $after_balance = $transactions[0]['after_balance'];
        }

        return $after_balance;
     }

    public function getUnsettledRounds($dateFrom, $dateTo){
        if(!$this->original_seamless_wallet_transactions_table){
            $this->utils->debug_log("getUnsettledRounds cannot get seamless transaction table", $this->getPlatformCode());
            return false;
        }

        #instead query unsettled,query settled to check if have settlement
        $sqlTime='BGAMING.created_at >= ? AND BGAMING.created_at <= ?';
        $this->CI->load->model(array('original_game_logs_model'));
        $sql = <<<EOD
SELECT 
BGAMING.round_id as round_id, 
BGAMING.transaction_id as transaction_id, 
BGAMING.created_at as transaction_date,
BGAMING.external_unique_id as external_uniqueid,
BGAMING.player_id,
BGAMING.transaction_type,
if(BGAMING.transaction_type = 'bet', BGAMING.amount, 0) as deducted_amount,
if(BGAMING.transaction_type != 'bet', BGAMING.amount, 0) as added_amount,
gd.id as game_description_id,
gd.game_type_id,
{$this->getPlatformCode()} as game_platform_id,
count(*) as row_count

from {$this->original_seamless_wallet_transactions_table} as BGAMING
LEFT JOIN game_description as gd ON BGAMING.game_code = gd.external_game_id and gd.game_platform_id=?
where
{$sqlTime}
GROUP BY BGAMING.round_id, BGAMING.player_id
having row_count = 1
EOD;


        $params=[
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];
        $this->CI->utils->debug_log('==> BGAMING getUnsettledRounds sql', $sql, $params);
        $results = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        // print_r($results);exit();
        return $results;
    }

    public function checkBetStatus($row){
        $this->CI->load->model(['seamless_missing_payout', 'original_seamless_wallet_transactions', 'original_game_logs_model']);
        if(!empty($row)){
            $roundId = $row['round_id'];
            $playerId = $row['player_id'];
            $payoutExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($this->original_seamless_wallet_transactions_table, ['round_id'=> $roundId,  'transaction_type' => 'win', 'player_id'=> $playerId]);
            $rollbackExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($this->original_seamless_wallet_transactions_table, ['round_id'=> $roundId,  'transaction_type' => 'rollback', 'player_id'=> $playerId]);
            if(!$payoutExist && !$rollbackExist){
                $row['amount'] = $row['deducted_amount'];
                $row['transaction_status']  = Game_logs::STATUS_PENDING;
                $row['status'] = Seamless_missing_payout::NOT_FIXED;
                unset($row['row_count']);
                $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report', $row);
                if($result===false){
                    $this->CI->utils->error_log('BGAMING SEAMLESS-' .$this->getPlatformCode().'(checkBetStatus) Error insert missing payout', $row);
                }
            }
        } else {
            return array('success'=>false, 'exists'=>false);
        }
    }

    public function queryBetTransactionStatus($game_platform_id, $external_uniqueid){
        $this->CI->load->model(['original_seamless_wallet_transactions', ]);
        $row = $this->CI->original_seamless_wallet_transactions->querySingleTransactionCustom($this->original_seamless_wallet_transactions_table, ['external_unique_id'=> $external_uniqueid]);
        if(!empty($row)){
            $playerId = $row['player_id'];
            $payoutExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($this->original_seamless_wallet_transactions_table, ['round_id'=> $row['round_id'],  'transaction_type' => 'win', 'player_id'=> $playerId]);
            $rollbackExist = $this->CI->original_seamless_wallet_transactions->isTransactionExistCustom($this->original_seamless_wallet_transactions_table, ['round_id'=> $row['round_id'],  'transaction_type' => 'rollback', 'player_id'=> $playerId]);
            if($payoutExist || $rollbackExist){
                return array('success'=>true, 'status'=> Game_logs::STATUS_SETTLED);
            }
        }
        
        return array('success'=>false, 'status'=> Game_logs::STATUS_PENDING);
    }
}
//end of class