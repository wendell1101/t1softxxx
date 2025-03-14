<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Game Provider: HP Lottery
 * Game Type: Lottery
 * Wallet Type: Transfer
 *
 * @category Game_platform
 * @version not specified
 * @copyright 2023 tot
 * @integrator @melvin.php.ph
 **/
class Game_api_hp_lottery extends Abstract_game_api {
    public $CI;
    public $http_method;
    public $game_api_name = 'HP_LOTTERY_GAME_API';
    public $original_table;
    public $api_url;
    public $licensee_key;
    public $licensee_secret;
    public $language;
    public $currency;
    public $game_launch_url;
    public $prefix_for_username;
    public $sync_time_interval;
    public $sleep_time;

    const HTTP_METHOD_GET = 'GET';
    const HTTP_METHOD_POST = 'POST';

    const RESPONSE_CODE_SUCCEEDED = 'SUCCEEDED';
    const RESPONSE_CODE_FAILED = 'FAILED';

    const URI_MAP = [
        self::API_createPlayer => '/v1/CreatePlayer',
        self::API_queryPlayerBalance => '/v1/GetBalance',
        self::API_isPlayerExist => '/v1/GetBalance',
        self::API_queryPlayerInfo => '/v1/GetBalance',
        self::API_depositToGame => '/v1/ChangeBalance',
        self::API_withdrawFromGame => '/v1/ChangeBalance',
        self::API_queryTransaction => '/v1/GetWalletTransactions',
        self::API_logout => '/v1/ForceUserLogout',
        self::API_queryForwardGame => '/member/auth',
        self::API_syncGameRecords => '/v1/GetBets'
    ];

    const MD5_FIELDS_FOR_ORIGINAL = [
        'betId',
        'betOrderNo',
        'betStatusId',
        'betResultId',
        'eventReferenceDate',
        'gameCode',
        'eventNo',
        'betTypeCode',
        'selectionCode',
        'userCode',
        'currencyCode',
        'wagerCount',
        'winningDeduction',
        'unitCost',
        'unitServiceFee',
        'unitAmount',
        'unitPrizeAmount',
        'unitReceivablePrizeAmount',
        'totalCost',
        'totalServiceFee',
        'totalAmount',
        'totalPrizeAmount',
        'totalReceivablePrizeAmount',
        'overallResultAmount',
        'insertedDateTime',
        'updatedDateTime',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'winningDeduction',
        'unitCost',
        'unitServiceFee',
        'unitAmount',
        'unitPrizeAmount',
        'unitReceivablePrizeAmount',
        'totalCost',
        'totalServiceFee',
        'totalAmount',
        'totalPrizeAmount',
        'totalReceivablePrizeAmount',
        'overallResultAmount',
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'username',
        'round_id',
        'game_code',
        'bet_amount',
        'win_amount',
        'start_at',
        'bet_at',
        'end_at',
        'response_result_id',
        'external_uniqueid',
        'betId',
        'betOrderNo',
        'betStatusId',
        'betResultId',
        'eventReferenceDate',
        'eventNo',
        'wagerCount',
        'unitAmount',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'win_amount',
        'unitAmount',
    ];

    const API_METHOD_GET_TOKEN = 'getToken';
    const API_METHOD_VALIDATE_TOKEN = 'validateToken';

    const API_METHODS = [
        self::API_METHOD_GET_TOKEN,
        self::API_METHOD_VALIDATE_TOKEN,
    ];

    const BET_STATUS_PENDING = '4';
    const BET_STATUS_REJECTED = '5';
    const BET_STATUS_CONFIRMED = '6';
    const BET_STATUS_SETTLED = '8';
    const BET_STATUS_VOIDED = '9';

    const MESSAGE_USERCODE_ALREADY_EXISTS = 'UserCode already exists';

    const ACTION_ID_ACCOUNT_CREATED = 1;
    const ACTION_ID_PLACE_BET = 11;
    const ACTION_ID_REJECTED_BET = 12;
    const ACTION_ID_VOID_BET = 13;
    const ACTION_ID_UNVOID_BET = 14;
    const ACTION_ID_SETTLE_BET = 15;
    const ACTION_ID_UNSETTLE_BET = 16;
    const ACTION_ID_FUND_IN = 21;
    const ACTION_ID_FUND_OUT = 22;
    const ACTION_ID_MANUAL_ADJUSTMENT = 81;

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(['common_token']);
        $this->original_table = 'hp_lottery_game_logs';
        $this->api_url = $this->getSystemInfo('url');
        $this->licensee_key = $this->getSystemInfo('licensee_key');
        $this->licensee_secret = $this->getSystemInfo('licensee_secret');
        $this->language = $this->getSystemInfo('language');
        $this->currency = $this->getSystemInfo('currency');
        $this->game_launch_url = $this->getSystemInfo('game_launch_url');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+15 minutes'); //minutes/hours/days
        $this->sleep_time = $this->getSystemInfo('sleep_time', '1'); //seconds
    }

    public function getPlatformCode() {
        return HP_LOTTERY_GAME_API;
    }

    public function generateUrl($api_name, $params) {
        $uri = self::URI_MAP[$api_name];
        $url = $this->api_url . $uri;

        if ($this->http_method == self::HTTP_METHOD_GET) {
            if ($api_name == self::API_queryForwardGame) {
                $url = $this->game_launch_url . $uri . '?' . http_build_query($params);
            }
        }

        return $url;
    }

    public function getHttpHeaders($params) {
        $http_header = [
            'Content-Type' => 'application/json',
            'Signature' => md5(json_encode($params) . '_' . $this->licensee_secret),
        ];

        return $http_header;
    }

    protected function customHttpCall($ch, $params) {
        if ($this->http_method == self::HTTP_METHOD_POST) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }

    public function processResultBoolean($response_result_id, $result_arr, $status_code, $api_name = null, $game_username = null) {
        $success = false;

        if ($status_code == 200 && isset($result_arr['returnCode']) && $result_arr['returnCode'] == self::RESPONSE_CODE_SUCCEEDED) {
            $this->CI->utils->debug_log('Success processResultBoolean', 'processResultBoolean_result', $result_arr);
            $success = true;

            if (isset($result_arr['data'][0]['returnCode']) && $result_arr['data'][0]['returnCode'] != self::RESPONSE_CODE_SUCCEEDED) {
                $success = false;
            }
        }

        if (isset($result_arr['message'])) {
            $message_usercode_already_exists = self::MESSAGE_USERCODE_ALREADY_EXISTS .' '. $game_username;

            if ($result_arr['message'] == $message_usercode_already_exists) {
                $success = true;
            }
        }

        if (!$success) {
            $this->setResponseResultToError($response_result_id);
            $this->CI->utils->debug_log("{$this->game_api_name} got error processResultBoolean", $response_result_id, 'processResultBoolean_result', $result_arr);
        }

        return $success;
    }

    public function callback($request, $method) {
        if (!in_array($method, self::API_METHODS)) {
            return [
                'returnCode' => self::RESPONSE_CODE_FAILED,
                'message' => 'Invalid method',
            ];
        }

        return $this->$method($request);
    }

    public function getToken($request) {
        $player_username = isset($request['player_username']) ? $request['player_username'] : null;
        $player_id = $this->getPlayerIdInGameProviderAuth($player_username);
        $token = $this->getPlayerToken($player_id);

        if (empty($token)) {
            return [
                'returnCode' => self::RESPONSE_CODE_FAILED,
                'message' => 'Invalid player username',
            ];
        }

        return [
            'returnCode' => self::RESPONSE_CODE_SUCCEEDED,
            'token' => $token,
        ];
    }

    public function validateToken($request) {
        $token = isset($request['token']) ? $request['token'] : null;
        $player_details = (array) $this->CI->common_token->getPlayerCompleteDetailsByToken($token, $this->getPlatformCode());

        if (empty($player_details)) {
            return [
                'returnCode' => self::RESPONSE_CODE_FAILED,
                'message' => 'Invalid token',
            ];
        }

        return [
            'returnCode' => self::RESPONSE_CODE_SUCCEEDED,
            'message' => null,
            'userCode' => $player_details['game_username'],
            'currencyCode' => $this->currency,
            // 'balance' => parent::queryPlayerBalance($player_details['username'])['balance'],
        ];
    }

    public function createPlayer($player_name, $player_id, $password, $email = null, $extra = []) {
        parent::createPlayer($player_name, $player_id, $password, $email, $extra);
        $this->http_method = self::HTTP_METHOD_POST;
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'player_id' => $player_id,
            'player_name' => $player_name,
            'game_username' => $game_username,
        );

        $params = [
            'random' => random_string('alnum', 32),
            'params' => [
                'licenseeKey' => $this->licensee_key,
                'userCode' => $game_username,
                'currencyCode' => $this->currency,
            ]
        ];

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){
        $player_id = $this->getVariableFromContext($params, 'player_id');
        $game_username = $this->getVariableFromContext($params, 'game_username');
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code, self::API_createPlayer, $game_username);

        $result = [
            'response_result_id' => $response_result_id,
        ];

        if ($success) {
            $result['updateRegisterFlag'] = $this->updateRegisterFlag($player_id, Abstract_game_api::FLAG_TRUE);
        }

        $this->CI->utils->debug_log('<---------- processResultForCreatePlayer ---------->', 'processResultForCreatePlayer_result', 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function queryPlayerBalance($player_name) {
        $this->http_method = self::HTTP_METHOD_POST;
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'player_name' => $player_name,
            'game_username' => $game_username,
        ];

        $params = [
            'random' => random_string('alnum', 32),
            'params' => [
                [
                    'licenseeKey' => $this->licensee_key,
                    'userCode' => $game_username,
                ]
            ]
        ];

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);

        $result = [
            'response_result_id' => $response_result_id,
        ];

        if ($success && !empty($result_arr['data'])) {
            $result['response_result'] = $result_arr;
            $result['balance'] = isset($result_arr['data'][0]['balance']) ? $this->gameAmountToDB(floatval($result_arr['data'][0]['balance'])) : 0;
        }
        
        $this->CI->utils->debug_log('<---------- processResultForQueryPlayerBalance ---------->', 'processResultForQueryPlayerBalance_result', 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function isPlayerExist($player_name) {
        $this->http_method = self::HTTP_METHOD_POST;
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'player_name' => $player_name,
            'game_username' => $game_username,
        ];

        $params = [
            'random' => random_string('alnum', 32),
            'params' => [
                [
                    'licenseeKey' => $this->licensee_key,
                    'userCode' => $game_username,
                ]
            ]
        ];

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params) {
        $player_id = $this->getVariableFromContext($params, 'player_id');
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);
            
        $result = [
            'response_result_id' => $response_result_id,
        ];

        if ($success && !empty($result_arr['data'])) {
            $result['response_result'] = $result_arr['data'];
            $result['exists'] = true;
            $this->updateRegisterFlag($player_id, Abstract_game_api::FLAG_TRUE);
        } else {
            $success = true;
            $result['exists'] = false;
        }

        $this->CI->utils->debug_log('<---------- processResultForIsPlayerExist ---------->', 'processResultForIsPlayerExist_result', 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function queryPlayerInfo($player_name) {
        $this->http_method = self::HTTP_METHOD_POST;
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerInfo',
            'player_name' => $player_name,
            'game_username' => $game_username,
        ];

        $params = [
            'random' => random_string('alnum', 32),
            'params' => [
                [
                    'licenseeKey' => $this->licensee_key,
                    'userCode' => $game_username,
                ]
            ]
        ];

        return $this->callApi(self::API_queryPlayerInfo, $params, $context);
    }

    public function processResultForQueryPlayerInfo($params) {
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);
            
        $result = [
            'response_result_id' => $response_result_id,
        ];

        if ($success && !empty($result_arr['data'])) {
            $result['response_result'] = $result_arr['data'];
            $result = $result['response_result'];
        }

        $this->CI->utils->debug_log('<---------- processResultForQueryPlayerInfo ---------->', 'processResultForQueryPlayerInfo_result', 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function depositToGame($player_name, $amount, $transfer_secure_id = null) {
        $this->http_method = self::HTTP_METHOD_POST;
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'external_transaction_id' => $transfer_secure_id,
            'player_name' => $player_name,
            'game_username' => $game_username,
        ];

        $params = [
            'random' => random_string('alnum', 32),
            'params' => [
                [
                    'licenseeKey' => $this->licensee_key,
                    'userCode' => $game_username,
                    'currencyCode' => $this->currency,
                    'amount' => strval($this->dBtoGameAmount($amount)), // as per GP, put amount into string
                    'referenceNo' => $transfer_secure_id,
                ]
            ]
        ];

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params) {
        $player_name = $this->getVariableFromContext($params, 'player_name');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);
        
        $extra = [
            'playerName' => $player_name,
        ];

        $result = [
            'response_result_id' => $response_result_id,
            'external_transaction_id' => $external_transaction_id,
            'reason_id'=> self::REASON_UNKNOWN,
            'transfer_status'=> self::COMMON_TRANSACTION_STATUS_UNKNOWN
        ];

        if ($success) {
            if ($this->verify_transfer_using_query_transaction) {
                $success = false;
                $query_transaction = $this->queryTransaction($external_transaction_id, $extra);

                if (isset($query_transaction['status'])) {
                    $result['transfer_status'] = $query_transaction['status'];

                    if ($query_transaction['status'] == self::COMMON_TRANSACTION_STATUS_APPROVED) {
                        $result['didnot_insert_game_logs'] = true;
                        $success = true;
                    }
                }
            } else {
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
                $result['didnot_insert_game_logs'] = true;
            }
        } else {
            if (in_array($status_code, $this->other_status_code_treat_as_success) && $this->treat_500_as_success_on_deposit) {
                $result['reason_id'] = self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            } else {
                $result['reason_id'] = self::REASON_TRANSACTION_DENIED;
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        }

        $this->CI->utils->debug_log('<---------- processResultForDepositToGame ---------->', 'processResultForDepositToGame_result', 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function withdrawFromGame($player_name, $amount, $transfer_secure_id = null) {
        $this->http_method = self::HTTP_METHOD_POST;
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'external_transaction_id' => $transfer_secure_id,
            'player_name' => $player_name,
            'game_username' => $game_username,
        ];

        $params = [
            'random' => random_string('alnum', 32),
            'params' => [
                [
                    'licenseeKey' => $this->licensee_key,
                    'userCode' => $game_username,
                    'currencyCode' => $this->currency,
                    'amount' => strval(-$this->dBtoGameAmount($amount)), // as per GP, put amount into string
                    'referenceNo' => $transfer_secure_id,
                ]
            ]
        ];

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $player_name = $this->getVariableFromContext($params, 'player_name');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);

        $extra = [
            'playerName' => $player_name,
        ];

        $result = [
            'response_result_id' => $response_result_id,
            'external_transaction_id' => $external_transaction_id,
            'reason_id'=> self::REASON_UNKNOWN,
            'transfer_status'=> self::COMMON_TRANSACTION_STATUS_UNKNOWN,
        ];

        if ($success) {
            if ($this->verify_transfer_using_query_transaction) {
                $success = false;
                $query_transaction = $this->queryTransaction($external_transaction_id, $extra);

                if (isset($query_transaction['status'])) {
                    $result['transfer_status'] = $query_transaction['status'];

                    if ($query_transaction['status'] == self::COMMON_TRANSACTION_STATUS_APPROVED) {
                        $result['didnot_insert_game_logs'] = true;
                        $success = true;
                    }
                }
            } else {
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
                $result['didnot_insert_game_logs'] = true;
            }
        } else {
            $result['reason_id'] = self::REASON_TRANSACTION_DENIED;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $success = false;
        }

        $this->CI->utils->debug_log('<---------- processResultForDepositToGame ---------->', 'processResultForDepositToGame_result', 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function queryTransaction($transactionId, $extra = []) {
        $this->http_method = self::HTTP_METHOD_POST;
        $player_name = !empty($extra['playerName']) ? $extra['playerName'] : null;
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);

        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id' => $transactionId,
            'player_name' => $player_name,
            'game_username' => $game_username,
        ];

        $params = [
            'random' => random_string('alnum', 32),
            'params' => [
                'licenseeKey' => $this->licensee_key,
                'referenceNo' => $transactionId,
            ]
        ];

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);
        
        $result = [
            'external_transaction_id'=> $external_transaction_id,
            'response_result_id' => $response_result_id,
            'reason_id'=> self::REASON_UNKNOWN,
            'status'=> self::COMMON_TRANSACTION_STATUS_UNKNOWN,
        ];

        if ($success) {
            if (isset($result_arr['data'][0])) {
                $data = $result_arr['data'][0];
                $result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
    
                if (isset($data['balanceActionId'])) {
                    $action_id = $data['balanceActionId'];
    
                    switch ($action_id) {
                        case self::ACTION_ID_ACCOUNT_CREATED:
                        case self::ACTION_ID_PLACE_BET:
                        case self::ACTION_ID_UNVOID_BET:
                        case self::ACTION_ID_SETTLE_BET:
                        case self::ACTION_ID_UNSETTLE_BET:
                        case self::ACTION_ID_FUND_IN:
                        case self::ACTION_ID_FUND_OUT:
                        case self::ACTION_ID_MANUAL_ADJUSTMENT:
                            $result['status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
                            break;
                        case self::ACTION_ID_REJECTED_BET:
                        case self::ACTION_ID_VOID_BET:
                            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                            break;
                        default:
                            $result['status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                            break;
                    }
                }
            } else {
                $success = false;
            }
        }

        $this->CI->utils->debug_log('<---------- processResultForQueryTransaction ---------->', 'processResultForQueryTransaction_result', 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function logout($player_name, $password = null) {
        $this->http_method = self::HTTP_METHOD_POST;
        $game_username = $this->getGameUsernameByPlayerUsername($player_name);
        
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'player_name' => $player_name,
            'game_username' => $game_username,
        ];

        $params = [
            'random' => random_string('alnum', 32),
            'params' => [
                [
                    'licenseeKey' => $this->licensee_key,
                    'userCode' => $game_username,
                ]
            ]
        ];

        return $this->callApi(self::API_logout, $params, $context);
    }

    public function processResultForLogout($params) {
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);
        
        $result = [
            'response_result_id' => $response_result_id
        ];

        if ($success && !empty($result_arr['data'])) {
            $result['response_result'] = $result_arr['data'];
        }
        
        $this->CI->utils->debug_log('<---------- processResultForLogout ---------->', 'processResultForLogout_result', 'result: ' . json_encode($result));

        return array($success, $result);
    }

    public function getLauncherLanguage($language) {
        return $this->getGameLauncherLanguage($language, [
    # default 'key' => 'change value only',
            'en_us' => 1,
            'zh_cn' => 1,
            'id_id' => 2,
            'vi_vn' => 4,
            'ko_kr' => 1,
            'th_th' => 3,
            'hi_in' => 1,
            'pt_pt' => 1,
            'es_es' => 1,
            'kk_kz' => 1,
            'pt_br' => 1,
            'ja_jp' => 1,
        ]);
    }

    public function queryForwardGame($player_name, $extra) {
        $this->http_method = self::HTTP_METHOD_GET;
        $success = true;
        $player_id = $this->getPlayerIdFromUsername($player_name);
        $username = $this->getGameUsernameByPlayerUsername($player_name);
        $token = $this->getPlayerToken($player_id);
        $game_code = isset($extra['game_code']) ? $extra['game_code'] : null;
        $game_mode = isset($extra['game_mode']) ? $extra['game_mode'] : null;
        $game_type = isset($extra['game_type']) ? $extra['game_type'] : null;
        $is_mobile = isset($extra['is_mobile']) && $extra['is_mobile'];

        if (!empty($this->language)) {
            $language = $this->language;
        } else {
            if (isset($extra['language'])) {
                $language = $extra['language'];
            } else {
                $language = null;
            }
        }

        $language = $this->getLauncherLanguage($language);

        $params = [
            'licenseeKey' => $this->licensee_key,
            'token' => $token,
            'language' => $language,
        ];

        $url = $this->generateUrl(self::API_queryForwardGame, $params);

        $result = [
            'success' => $success,
            'url' => $url,
        ];

        $this->CI->utils->debug_log('<--------------- queryForwardGame --------------->', 'queryForwardGame_result', $params);

        return $result;
    }

    public function syncOriginalGameLogs($token) {
        $this->http_method = self::HTTP_METHOD_POST;
        $date_time_from = clone $this->getValueFromSyncInfo($token, 'dateTimeFrom');
        $date_time_to = clone $this->getValueFromSyncInfo($token, 'dateTimeTo');
        $start_date_time_modified = $date_time_from->modify($this->getDatetimeAdjust());
        $date_time_from = new DateTime($this->serverTimeToGameTime($start_date_time_modified->format('Y-m-d H:i:s')));
        $date_time_to = new DateTime($date_time_to->format('Y-m-d H:i:s'));
        $start_date_time = $date_time_from->format('Y-m-d H:i:s');
        $end_date_time = $date_time_to->format('Y-m-d H:i:s');

        $result = [
            'success' => true,
            'data_count' => 0,
            'data_count_insert' => 0,
            'data_count_update' => 0,
            'sync_time_interval' => $this->sync_time_interval,
            'sleep_time' => $this->sleep_time
        ];

        while ($start_date_time <= $end_date_time) {
            $end_date_time_modified = (new DateTime($start_date_time))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');

            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncOriginalGameLogs',
                'start_date' => $start_date_time,
                'end_date' => $end_date_time_modified
            );

            $params = [
                'random' => random_string('alnum', 32),
                'params' => [
                    'licenseeKey' => $this->licensee_key,
                    'updatedDateTimeFrom' => $start_date_time,
                    'updatedDateTimeTo' => $end_date_time_modified,
                ]
            ];

            $data = $this->callApi(self::API_syncGameRecords, $params, $context);
            $success = isset($data['success']) && $data['success'] ? true : false;

            if ($success) {
                $game_records = $this->rebuildGameRecords($data);

                list($insert_rows, $update_rows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    $this->original_table,
                    $game_records,
                    'external_uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );

                $this->CI->utils->debug_log('after process available rows ----->', 'gamerecords-> ' . count($game_records), 'insertrows-> ' . count($insert_rows), 'updaterows-> ' . count($update_rows));
                
                $result['data_count'] += is_array($game_records) ? count($game_records): 0;

                if (!empty($insert_rows)) {
                    $result['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insert_rows, 'insert');
                }

                unset($insert_rows);

                if (!empty($update_rows)) {
                    $result['data_count_update'] += $this->updateOrInsertOriginalGameLogs($update_rows, 'update');
                }

                unset($update_rows);
            }

            $this->CI->utils->debug_log('<--------------- processResultForSyncOriginalGameLogs --------------->', 'start_date_time: ' . $start_date_time, 'end_date_time_modified: ' . $end_date_time_modified);

            $success = false;
            sleep($this->sleep_time);

            $start_date_time = (new DateTime($start_date_time))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
        }

        return $result;
    }

    public function processResultForSyncOriginalGameLogs($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $response_result_id = $this->getResponseResultIdFromParams($params);
        $result_arr = $this->getResultJsonFromParams($params);
        $status_code = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($response_result_id, $result_arr, $status_code);

        $result = [
            'response_result_id' => $response_result_id,
            'game_records' => [],
        ];

        if ($success && !empty($result_arr['data'])) {
            $result['response_result'] = $result_arr['data'];
            $result['game_records'] = $result_arr['data'];
        }

        return array($success, $result);
    }

    public function rebuildGameRecords($data) {
        $game_records = !empty($data['game_records']) ? $data['game_records'] : [];
        $response_result_id = !empty($data['response_result_id']) ? $data['response_result_id'] : null;
        $new_game_records = $record = [];

        foreach ($game_records as $game_record) {
            $record['betId'] = isset($game_record['betId']) ? $game_record['betId'] : null;
            $record['betOrderNo']= isset($game_record['betOrderNo']) ? $game_record['betOrderNo'] : null;
            $record['betStatusId'] = isset($game_record['betStatusId']) ? $game_record['betStatusId'] : null;
            $record['betResultId'] = isset($game_record['betResultId']) ? $game_record['betResultId'] : null;
            $record['eventReferenceDate'] = isset($game_record['eventReferenceDate']) ? $game_record['eventReferenceDate'] : null;
            $record['gameCode'] = isset($game_record['gameCode']) ? $game_record['gameCode'] : null;
            $record['eventNo'] = isset($game_record['eventNo']) ? $game_record['eventNo'] : null;
            $record['betTypeCode'] = isset($game_record['betTypeCode']) ? $game_record['betTypeCode'] : null;
            $record['selectionCode'] = isset($game_record['selectionCode']) ? $game_record['selectionCode'] : null;
            $record['userCode'] = isset($game_record['userCode']) ? $game_record['userCode'] : null;
            $record['currencyCode'] = isset($game_record['currencyCode']) ? $game_record['currencyCode'] : null;
            $record['wagerCount'] = isset($game_record['wagerCount']) ? $game_record['wagerCount'] : null;
            $record['winningDeduction'] = isset($game_record['winningDeduction']) ? $game_record['winningDeduction'] : 0;
            $record['unitCost'] = isset($game_record['unitCost']) ? $game_record['unitCost'] : 0;
            $record['unitServiceFee'] = isset($game_record['unitServiceFee']) ? $game_record['unitServiceFee'] : 0;
            $record['unitAmount'] = isset($game_record['unitAmount']) ? $game_record['unitAmount'] : 0;
            $record['unitPrizeAmount'] = isset($game_record['unitPrizeAmount']) ? $game_record['unitPrizeAmount'] : 0;
            $record['unitReceivablePrizeAmount'] = isset($game_record['unitReceivablePrizeAmount']) ? $game_record['unitReceivablePrizeAmount'] : 0;
            $record['totalCost'] = isset($game_record['totalCost']) ? $game_record['totalCost'] : 0;
            $record['totalServiceFee'] = isset($game_record['totalServiceFee']) ? $game_record['totalServiceFee'] : 0;
            $record['totalAmount'] = isset($game_record['totalAmount']) ? $game_record['totalAmount'] : 0;
            $record['totalPrizeAmount'] = isset($game_record['totalPrizeAmount']) ? $game_record['totalPrizeAmount'] : 0;
            $record['totalReceivablePrizeAmount'] = isset($game_record['totalReceivablePrizeAmount']) ? $game_record['totalReceivablePrizeAmount'] : 0;
            $record['overallResultAmount'] = isset($game_record['overallResultAmount']) ? $game_record['overallResultAmount'] : 0;
            $record['insertedDateTime'] = isset($game_record['insertedDateTime']) ? $this->gameTimeToServerTime($game_record['insertedDateTime']) : '';
            $record['updatedDateTime'] = isset($game_record['updatedDateTime']) ? $this->gameTimeToServerTime($game_record['updatedDateTime']) : '';
            $record['extra_info'] = !empty($game_record) && is_array($game_record) ? json_encode($game_record) : '';
            $record['response_result_id'] = $response_result_id;
            $record['external_uniqueid'] = $game_record['betId'] . '-' . $game_record['selectionCode'];

            array_push($new_game_records, $record);
        }

        return $new_game_records;
    }

    private function updateOrInsertOriginalGameLogs($data, $query_type) {
        $data_count = 0;

        if (!empty($data)) {
            foreach ($data as $record) {
                if ($query_type == 'update') {
                    $record['updated_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_table, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->CI->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_table, $record);
                }

                $data_count++;
                unset($record);
            }
        }

        return $data_count;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle = true;

        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $game_logs_table = $this->original_table;
        $sqlTime = "ogl.updated_at BETWEEN ? AND ?";

        if ($use_bet_time) {
            $sqlTime = "ogl.insertedDateTime BETWEEN ? AND ?";
        }

        $sql = <<<EOD
SELECT
    ogl.id AS sync_index,
    ogl.userCode AS username,
    ogl.selectionCode AS round_id,
    ogl.gameCode AS game_code,
    ogl.totalAmount AS bet_amount,
    ogl.totalPrizeAmount AS win_amount,
    ogl.insertedDateTime AS start_at,
    ogl.insertedDateTime AS bet_at,
    ogl.updatedDateTime AS end_at,
    ogl.response_result_id,
    ogl.external_uniqueid,
    ogl.created_at,
    ogl.updated_at,
    ogl.md5_sum,
    ogl.betId,
    ogl.betOrderNo,
    ogl.betStatusId,
    ogl.betResultId,
    ogl.eventReferenceDate,
    ogl.eventNo,
    ogl.wagerCount,
    ogl.unitAmount,
    ogl.totalAmount,
    game_provider_auth.login_name AS player_username,
    game_provider_auth.player_id,
    game_description.id AS game_description_id,
    game_description.game_name AS game_description_name,
    game_description.game_type_id,
    game_description.english_name AS game
FROM
    {$game_logs_table} as ogl
    LEFT JOIN game_description ON ogl.gameCode = game_description.game_code AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON ogl.userCode = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
WHERE {$sqlTime}

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

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if (empty($row['md5_sum'])) {
            $row['md5_sum'] = $this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            'game_info' => [
                'game_type_id'          => isset($row['game_type_id']) ? $row['game_type_id'] : null,
                'game_description_id'   => isset($row['game_description_id']) ? $row['game_description_id'] : null,
                'game_code'             => isset($row['game_code']) ? $row['game_code'] : null,
                'game_type'             => null,
                'game'                  => isset($row['game']) ? $row['game'] : null
            ],
            'player_info' => [
                'player_id'             => isset($row['player_id']) ? $row['player_id'] : null,
                'player_username'       => isset($row['player_username']) ? $row['player_username'] : null
            ],
            'amount_info' => [
                'bet_amount'            => !empty($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'result_amount'         => !empty($row['result_amount']) ? $this->gameAmountToDB($row['result_amount']) : 0,
                'bet_for_cashback'      => !empty($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'real_betting_amount'   => !empty($row['bet_amount']) ? $this->gameAmountToDB($row['bet_amount']) : 0,
                'win_amount'            => 0,
                'loss_amount'           => 0,
                'after_balance'         => isset($row['after_balance']) ? $this->gameAmountToDB($row['after_balance']) : 0,
            ],
            'date_info' => [
                'start_at'              => isset($row['start_at']) ? $row['start_at'] : '',
                'end_at'                => isset($row['end_at']) ? $row['end_at'] : '',
                'bet_at'                => isset($row['bet_at']) ? $row['bet_at'] : '',
                'updated_at'            => isset($row['updated_at']) ? $row['updated_at'] : '',
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
                'round_number'          => isset($row['round_id']) ? $row['round_id'] : null,
                'md5_sum'               => isset($row['md5_sum']) ? $row['md5_sum'] : null,
                'response_result_id'    => isset($row['response_result_id']) ? $row['response_result_id'] : null,
                'sync_index'            => isset($row['sync_index']) ? $row['sync_index'] : null,
                'bet_type'              => null
            ],
            'bet_details' => $this->preprocessBetDetails($row),
            'extra' => [
                'note' => $row['note'],
            ],
            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row) {
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        $row['result_amount'] = $row['win_amount'] - $row['bet_amount'];
        $row['status'] = $this->getStatus($row['betStatusId']);
        $row['note'] = $this->getResult($row['status'], $row['result_amount']);
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;

        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if (empty($game_description_id)) {
            $game_description_id = $this->CI->game_description_model->processUnknownGame($this->getPlatformCode(), $unknownGame->game_type_id, $row['game_code'], $row['game_code']);
            $game_type_id = $unknownGame->game_type_id;
        }

        return [$game_description_id, $game_type_id];
    }

    public function getStatus($status) {
        switch (intval($status)) {
            case self::BET_STATUS_PENDING:
                $status = Game_logs::STATUS_PENDING;
                break;
            case self::BET_STATUS_REJECTED:
                $status = Game_logs::STATUS_REJECTED;
                break;
            case self::BET_STATUS_CONFIRMED:
                $status = Game_logs::STATUS_ACCEPTED;
                break;
            case self::BET_STATUS_SETTLED:
                $status = Game_logs::STATUS_SETTLED;
                break;
            case self::BET_STATUS_VOIDED:
                $status = Game_logs::STATUS_VOID;
                break;
            default:
                $status = Game_logs::STATUS_UNSETTLED;
                break;
        }

        return $status;
    }

    public function getResult($status, $result_amount) {
        $result = 'Unknown';

        if ($status == Game_logs::STATUS_SETTLED) {
            if ($result_amount > 0) {
                $result = 'Win';
            } elseif ($result_amount < 0) {
                $result = 'Lose';
            } elseif ($result_amount == 0) {
                $result = 'Draw';
            } else {
                $result = 'Free Game';
            }
        } elseif ($status == Game_logs::STATUS_PENDING) {
            $result = 'Pending';
        } elseif ($status == Game_logs::STATUS_ACCEPTED) {
            $result = 'Accepted';
        } elseif ($status == Game_logs::STATUS_REJECTED) {
            $result = 'Rejected';
        } elseif ($status == Game_logs::STATUS_CANCELLED) {
            $result = 'Cancelled';
        } elseif ($status == Game_logs::STATUS_VOID) {
            $result = 'Void';
        } elseif ($status == Game_logs::STATUS_REFUND) {
            $result = 'Refund';
        } elseif ($status == Game_logs::STATUS_SETTLED_NO_PAYOUT) {
            $result = 'Settled no payout';
        } elseif ($status == Game_logs::STATUS_UNSETTLED) {
            $result = 'Unsettled';
        } else {
            $result = 'Unknown';
        }

        return $result;
    }

    public function preprocessOriginalRowForBetDetails($row, $extra = []) {
        // print_r($row);exit;
        $bet_details = $row;

        if (isset($row['betId'])) {
            $bet_details['bet_id'] = $row['betId'];
        }

        if (isset($row['betId'])) {
            $bet_details['ticket_id'] = $row['betId'];
        }

        if (isset($row['round_id'])) {
            $bet_details['round_id'] = $row['round_id'];
        }

        if (isset($row['game'])) {
            $bet_details['game_name'] = $row['game'];
        }

        if (isset($row['round_id'])) {
            $bet_details['lottery_picked_number'] = $row['round_id'];
        }

        if (isset($row['bet_amount'])) {
            $bet_details['bet_amount'] = $row['bet_amount'];
        }

        if (isset($row['win_amount'])) {
            $bet_details['win_amount'] = $row['win_amount'];
        }

        if (isset($row['start_at'])) {
            $bet_details['betting_datetime'] = $row['start_at'];
        }

        if (isset($row['end_at'])) {
            $bet_details['settlement_datetime'] = $row['end_at'];
        }

        // print_r($bet_details);exit;
        return $bet_details;
    }
}

/*end of file*/