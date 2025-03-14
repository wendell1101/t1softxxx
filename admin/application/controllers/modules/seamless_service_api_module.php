<?php
/**
 * Seamless Service API (SSA) Module
 *
 * Provides reusable functions with ssa_prefix for the Seamless Service API (SSA) functionality.
 *
 * @author Melvin (melvin.php.ph)
 */
trait Seamless_service_api_module {
    // remote wallet start ----------------------------------------------------------------------------
    // remote wallet code from PHP7 remote_wallet.php
    protected $ssa_remote_wallet_code_success = 0;
    protected $ssa_remote_wallet_code_double_unique_id = 8;
    protected $ssa_remote_wallet_code_invalid_unique_id = 9;
    protected $ssa_remote_wallet_code_insufficient_balance = 10;
    protected $ssa_remote_wallet_code_maintenance = 11;
    protected $ssa_remote_wallet_game_not_available = 14;

    // remote wallet adjustment
    protected $ssa_remote_wallet_decreased = 'remote_wallet_decreased';
    protected $ssa_remote_wallet_increased = 'remote_wallet_increased';
    protected $ssa_remote_wallet_retained = 'remote_wallet_retained';
    protected $ssa_remote_wallet_preserved = 'remote_wallet_preserved';
    protected $ssa_remote_wallet_failed = 'remote_wallet_failed';
    protected $ssa_failed_remote_common_seamless_transactions_table = 'failed_remote_common_seamless_transactions';
    // remote wallet end ----------------------------------------------------------------------------

    protected $ssa_request_headers;
    protected $ssa_request_params;
    protected $ssa_original_request_params;
    protected $ssa_http_response_status_code = 500;
    protected $ssa_operator_response = [];
    protected $ssa_custom_message_response;

    protected $ssa_request_method_get = 'GET';
    protected $ssa_request_method_post = 'POST';

    protected $ssa_subject_type_token = 'token';
    protected $ssa_subject_type_username = 'username';
    protected $ssa_subject_type_game_username = 'game_username';

    protected $ssa_decrease = 'decrease';
    protected $ssa_increase = 'increase';
    protected $ssa_retain = 'retain';
    protected $ssa_preserve = 'preserve';

    protected $ssa_decreased = 'decreased';
    protected $ssa_increased = 'increased';
    protected $ssa_retained = 'retained';
    protected $ssa_preserved = 'preserved';

    protected $ssa_insert = 'insert';
    protected $ssa_update = 'update';

    protected $ssa_success = 'success';
    protected $ssa_error = 'error';
    protected $ssa_failed = 'failed';

    protected $ssa_flag_not_updated = 0;
    protected $ssa_flag_updated = 1;
    protected $ssa_flag_retain = 3;

    protected $ssa_api_success = [
        'code' => 0,
        'message' => 'SUCCESS',
    ];

    protected $ssa_http_response_status_code_list = [
        200 => [
            'status' => 'ok',
            'code' => 200,
            'text' => 'Ok',
            'description' => 'Success',
        ],
        400 => [
            'status' => 'error',
            'code' => 400,
            'text' => 'Bad Request',
            'description' => 'Malformed request syntax, Invalid request message framing, or Deceptive request routing',
        ],
        401 => [
            'status' => 'error',
            'code' => 401,
            'text' => 'Unauthorized',
            'description' => 'Request has not been completed because it lacks valid authentication credentials for the requested resource.',
        ],
        403 => [
            'status' => 'error',
            'code' => 403,
            'text' => 'Forbidden',
            'description' => 'Forbidden.',
        ],
        404 => [
            'status' => 'error',
            'code' => 404,
            'text' => 'Not Found',
            'description' => 'The server can not find the requested resource.',
        ],
        405 => [
            'status' => 'error',
            'code' => 405,
            'text' => 'Method Not Allowed',
            'description' => 'Method Not Allowed.',
        ],
        412 => [
            'status' => 'error',
            'code' => 412,
            'text' => 'Precondition Failed',
            'description' => 'Precondition Failed.',
        ],
        500 => [
            'status' => 'error',
            'code' => 500,
            'text' => 'Internal Server Error',
            'description' => 'Internal Server Error',
        ],
        501 => [
            'status' => 'error',
            'code' => 501,
            'text' => 'Not Implemented',
            'description' => 'The request method is not supported by the server and cannot be handled.',
        ],
        503 => [
            'status' => 'error',
            'code' => 503,
            'text' => 'Service Unavailable',
            'description' => 'The server is not ready to handle the request. Common causes are a server that is down for maintenance or that is disabled.',
        ],
    ];

    // response type
    protected $ssa_response_type_json = 'json';
    protected $ssa_response_type_xml = 'xml';

    // content type
    protected $ssa_content_type_application_json = 'application/json';
    protected $ssa_content_type_application_xml = 'application/xml';
    protected $ssa_content_type_text_plain = 'text/plain';

    protected $ssa_success_wallet_actions = [];
    protected $ssa_failed_wallet_actions = [];

    protected $ssa_request_param_key = null;
    protected $ssa_game_api;

    // system error
    protected $ssa_system_error = [];
    protected $ssa_system_error_server_ip_address_not_allowed = 'SERVER_IP_ADDRESS_NOT_ALLOWED';
    protected $ssa_system_error_game_api_disabled = 'GAME_API_DISABLED';
    protected $ssa_system_error_game_api_maintenance = 'GAME_API_MAINTENANCE';
    protected $ssa_system_error_player_blocked = 'PLAYER_BLOCKED';
    protected $ssa_system_error_invalid_player_token = 'INVALID_PLAYER_TOKEN';
    protected $ssa_system_error_invalid_player_game_username = 'INVALID_PLAYER_GAME_USERNAME';
    protected $ssa_system_error_player_not_found = 'PLAYER_NOT_FOUND';
    protected $ssa_system_error_invalid_player_subject_type = 'INVALID_PLAYER_SUBJECT_TYPE';
    protected $ssa_system_error_is_currency_domain = 'IS_CURRENCY_DOMAIN';
    protected $ssa_system_error_invalid_currency_key = 'INVALID_CURRENCY_KEY';

    protected $ssa_system_error_remote_wallet_double_unique_id = 'REMOTE_WALLET_DOUBLE_UNIQUEID';
    protected $ssa_system_error_remote_wallet_invalid_unique_id = 'REMOTE_WALLET_INVALID_UNIQUEID';
    protected $ssa_system_error_remote_wallet_insufficient_balance = 'REMOTE_WALLET_INSUFFICIENT_BALANCE';
    protected $ssa_system_error_remote_wallet_maintenance = 'REMOTE_WALLET_MAINTENANCE';
    protected $ssa_system_error_remote_wallet_game_not_available = 'REMOTE_WALLET_GAME_NOT_AVAILABLE';

    // player details
    protected $ssa_player_details = [];
    protected $ssa_player_id = null;
    protected $ssa_player_token = null;
    protected $ssa_player_username = null;
    protected $ssa_player_game_username = null;
    protected $ssa_player_subject = null;
    protected $ssa_is_player_game_username = false;
    protected $ssa_player_balance = null;

    // dev authorization
    protected $ssa_dev_authorization;
    protected $ssa_header_x_show_hint = 0;
    protected $ssa_header_x_decrypt_response = 0;
    protected $ssa_header_x_check_signature = 1;

    // others
    protected $ssa_hint = [];
    protected $ssa_set_custom_response = [];
    protected $ssa_external_game_id = null;
    protected $ssa_use_token_from = 'common_tokens';

    protected function ssa_init() {
        $this->ssa_request_headers = $this->ssa_request_headers();
        $this->ssa_request_params = $this->ssa_original_request_params = $this->ssa_request_params();
        $this->ssa_success_wallet_actions = [$this->ssa_decreased, $this->ssa_increased, $this->ssa_retained];
        $this->ssa_failed_wallet_actions = [$this->ssa_preserved, $this->ssa_failed];
    }

    protected function ssa_api_method($__FUNCTION__, $api_method, $allowed_api_methods) {
        if (!in_array($api_method, $allowed_api_methods)) {
            $api_method = $__FUNCTION__;
        }

        return $api_method;
    }

    protected function ssa_payload() {
        return file_get_contents("php://input");
    }

    protected function ssa_request_params($is_object = false) {
        $payload = $this->ssa_payload();
        $this->utils->debug_log(__CLASS__, __METHOD__, 'raw_request_params', $payload);
        $result = $this->ssa_process_request_data($payload);

        if ($is_object) {
            $result = json_decode(json_encode($result));
        }

        return $result;
    }

    protected function ssa_process_request_data($payload) {
        if (!empty($this->input->post())) {
            $request_params = $this->input->post();
        } else {
            if (!empty($payload)) {
                $request_params = $this->ssa_request_from_json($payload);

                if (empty($request_params)) {
                    $request_params = $this->ssa_request_from_xml($payload);
                }
            } else {
                if (!empty($_GET)) {
                    $request_params = $_GET;
                } else {
                    $request_params = [];
                }
            }
        }

        return $request_params;
    }

    protected function ssa_request_from_json($request) {
        if (!empty($request)) {
            $result = is_array($request) ? $request : json_decode($request, true);
        } else {
            $result = [];
        }

        return $result;
    }

    protected function ssa_request_from_xml($request) {
        if (!empty($request)) {
            $xml_object = simplexml_load_string ($request, 'SimpleXmlElement', LIBXML_NOERROR+LIBXML_ERR_FATAL+LIBXML_ERR_NONE);
            $result = is_array($request) ? $request : json_decode(json_encode(($xml_object)), true); // convert to array;
        } else {
            $result = [];
        }

        return $result;
    }

    protected function ssa_request_headers() {
        return getallheaders();
    }

    protected function ssa_is_api_method_not_found($class_methods, $api_method) {
        return !in_array($api_method, $class_methods);
    }

    protected function ssa_is_api_method_allowed($api_method, $allowed_api_methods) {
        return !in_array($api_method, $allowed_api_methods);
    }

    protected function ssa_load_game_api_class($game_platform_id) {
        return $this->utils->loadExternalSystemLibObject($game_platform_id);
    }

    protected function ssa_is_server_ip_allowed($game_api = null) {
        if (empty($game_api)) {
            if (!empty($this->ssa_game_api)) {
                $game_api = $this->ssa_game_api;
            } else {
                $this->ssa_system_error['ssa_is_game_api_disabled'] = 'Failed to initialize game api';
                $this->utils->debug_log(__METHOD__, $this->ssa_system_error);
                return false;
            }
        }

        return $game_api->validateWhiteIP();
    }

    protected function ssa_is_server_ip_not_allowed($game_api = null) {
        if (empty($game_api)) {
            if (!empty($this->ssa_game_api)) {
                $game_api = $this->ssa_game_api;
            } else {
                $this->ssa_system_error['ssa_is_game_api_disabled'] = 'Failed to initialize game api';
                $this->utils->debug_log(__METHOD__, $this->ssa_system_error);
                return false;
            }
        }

        return !$game_api->validateWhiteIP();
    }

    protected function ssa_is_game_api_active($game_api = null) {
        if (empty($game_api)) {
            if (!empty($this->ssa_game_api)) {
                $game_api = $this->ssa_game_api;
            } else {
                $this->ssa_system_error['ssa_is_game_api_disabled'] = 'Failed to initialize game api';
                $this->utils->debug_log(__METHOD__, $this->ssa_system_error);
                return false;
            }
        }

        return $game_api->isActive();
    }

    protected function ssa_is_game_api_disabled($game_api = null) {
        if (empty($game_api)) {
            if (!empty($this->ssa_game_api)) {
                $game_api = $this->ssa_game_api;
            } else {
                $this->ssa_system_error['ssa_is_game_api_disabled'] = 'Failed to initialize game api';
                $this->utils->debug_log(__METHOD__, $this->ssa_system_error);
                return false;
            }
        }

        return !$game_api->isActive();
    }

    protected function ssa_is_game_api_maintenance($game_api = null) {
        if (empty($game_api)) {
            if (!empty($this->ssa_game_api)) {
                $game_api = $this->ssa_game_api;
            } else {
                $this->ssa_system_error['ssa_is_game_api_disabled'] = 'Failed to initialize game api';
                $this->utils->debug_log(__METHOD__, $this->ssa_system_error);
                return false;
            }
        }

        return $game_api->isMaintenance();
    }

    protected function ssa_is_player_blocked($game_api = null, $subject = null, $is_player_game_username = false) {
        if (empty($game_api)) {
            if (!empty($this->ssa_game_api)) {
                $game_api = $this->ssa_game_api;
            } else {
                $this->ssa_system_error['ssa_is_game_api_disabled'] = 'Failed to initialize game api';
                $this->utils->debug_log(__METHOD__, $this->ssa_system_error);
                return false;
            }
        }

        if (empty($subject)) {
            if (!empty($this->ssa_player_subject)) {
                $subject = $this->ssa_player_subject;
            } else {
                $this->ssa_system_error['ssa_is_game_api_disabled'] = 'Subject is required';
                $this->utils->debug_log(__METHOD__, $this->ssa_system_error);
                return false;
            }
        }

        if (!$is_player_game_username && $this->ssa_is_player_game_username) {
            $is_player_game_username = $this->ssa_is_player_game_username;
        }

        if ($is_player_game_username) {
            return $game_api->isBlockedUsernameInDB($subject);
        } else {
            return $game_api->isBlocked($subject);
        }
    }

    protected function ssa_is_transaction_exists_by_external_uniqueId($table_name, $unique_transaction_id) {
        $this->load->model(['original_seamless_wallet_transactions']);

        return $this->original_seamless_wallet_transactions->isTransactionExistCustom($table_name, ['external_unique_id' => $unique_transaction_id]);
    }

    protected function ssa_is_transaction_exists($table_name, $where = []) {
        $this->load->model(['original_seamless_wallet_transactions']);

        return $this->original_seamless_wallet_transactions->isTransactionExistCustom($table_name, $where);
    }

    protected function ssa_get_transaction($table_name, $where = [], $selected_columns = [], 
        $order_by = ['field_name' => '', 'is_desc' => false]
    ) {
        $this->load->model(['original_seamless_wallet_transactions']);

        return $this->original_seamless_wallet_transactions->querySingleTransactionCustom($table_name, $where, $selected_columns, $order_by);
    }

    protected function ssa_get_transactions($table_name, $where = [], $selected_columns = [], 
        $order_by = ['field_name' => '', 'is_desc' => false]
    ) {
        $this->load->model(['original_seamless_wallet_transactions']);

        return $this->original_seamless_wallet_transactions->queryPlayerTransactionsCustom($table_name, $where, $selected_columns, $order_by);
    }

    protected function ssa_get_transaction_after_balance($table_name, $where = []) {
        $transaction_data = $this->ssa_get_transaction($table_name, $where);
        return !empty($transaction_data['after_balance']) ? $transaction_data['after_balance'] : 0;
    }

    protected function ssa_generate_md5_sum($data = [], $md5_fields_for_original, $md5_float_amount_fields) {
        $this->load->model(['original_seamless_wallet_transactions']);

        return $this->original_seamless_wallet_transactions->generateMD5SumOneRow($data, $md5_fields_for_original, $md5_float_amount_fields);
    }

    protected function ssa_insert_transaction_data($transaction_table, $data = [], $db = null) {
        $this->load->model(['original_seamless_wallet_transactions']);

        return $this->original_seamless_wallet_transactions->insertTransactionData($transaction_table, $data, $db);
    }

    protected function ssa_update_transaction_with_result($transaction_table, $data = [], $field_id, $id, $db = null) {
        $this->load->model(['original_seamless_wallet_transactions']);

        return $this->original_seamless_wallet_transactions->updateTransactionDataWithResult($transaction_table, $data, $field_id, $id, $db);
    }

    protected function ssa_update_transaction_without_result($transaction_table, $data = [], $field_id, $id, $db = null) {
        $this->load->model(['original_seamless_wallet_transactions']);

        return $this->original_seamless_wallet_transactions->updateTransactionDataWithoutResult($transaction_table, $data, $field_id, $id, $db);
    }

    protected function ssa_update_transaction_with_result_custom($transaction_table, $data = [], $where = [], $db = null) {
        $this->load->model(['original_seamless_wallet_transactions']);

        return $this->original_seamless_wallet_transactions->updateTransactionDataWithResultCustom($transaction_table, $where, $data, $db);
    }

    protected function ssa_update_transaction_without_result_custom($transaction_table, $data = [], $where = [], $db = null) {
        $this->load->model(['original_seamless_wallet_transactions']);

        return $this->original_seamless_wallet_transactions->updateTransactionDataWithoutResultCustom($transaction_table, $where, $data, $db);
    }

    protected function ssa_insert_update_transaction($transaction_table, $query_type, $data = [], $field_id = null, $id = null, $update_with_result = true) {
        $this->load->model(['original_seamless_wallet_transactions']);

        return $this->original_seamless_wallet_transactions->insertOrUpdateTransactionData($transaction_table, $query_type, $data, $field_id, $id, $update_with_result);
    }

    protected function ssa_decrease_player_wallet($player_id, $game_platform_id, $amount, &$after_balance = null, $allowed_negative_balance = false) {
        $this->load->model(['wallet_model']);

        // if remote wallet enabled, use config remote_wallet_api_allowed_negative_balance
        if ($allowed_negative_balance && !$this->ssa_enabled_remote_wallet()) {
            $result = $this->wallet_model->decSubWalletAllowNegative($player_id, $game_platform_id, $amount);
        } else {
            $result = $this->wallet_model->decSubWallet($player_id, $game_platform_id, $amount, $after_balance);
        }

        return $result;
    }

    protected function ssa_increase_player_wallet($player_id, $game_platform_id, $amount, &$after_balance = null) {
        $this->load->model(['wallet_model']);

        $result = $this->wallet_model->incSubWallet($player_id, $game_platform_id, $amount, $after_balance);

        return $result;
    }

    protected function ssa_get_player_details_by_token($token, $game_platform_id, $refresh_timout = true, $min_span_allowed = 10, $minutes_to_add = 120) {
        if ($this->ssa_use_token_from == 'external_common_tokens') {
            $this->load->model(['external_common_tokens']);
            return (array) $this->external_common_tokens->getPlayerCompleteDetailsByToken($token, $game_platform_id);
        } else {
            $this->load->model(['common_token']);
            return (array) $this->common_token->getPlayerCompleteDetailsByToken($token, $game_platform_id, $refresh_timout, $min_span_allowed, $minutes_to_add);
        }
    }

    protected function ssa_get_player_details_by_username($username, $game_platform_id) {
        $this->load->model(['common_token']);

        return (array) $this->common_token->getPlayerCompleteDetailsByUsername($username, $game_platform_id);
    }

    protected function ssa_get_player_details_by_game_username($game_username, $game_platform_id) {
        $this->load->model(['common_token']);

        return (array) $this->common_token->getPlayerCompleteDetailsByGameUsername($game_username, $game_platform_id);
    }

    protected function ssa_get_player_details($subject_type, $subject, $game_platform_id, $refresh_timout = true, $min_span_allowed = 10, $minutes_to_add = 120) {
        switch ($subject_type) {
            case 'token':
                $player_details = $this->ssa_get_player_details_by_token($subject, $game_platform_id, $refresh_timout, $min_span_allowed, $minutes_to_add);
                break;
            case 'username':
                $player_details = $this->ssa_get_player_details_by_username($subject, $game_platform_id);
                break;
            case 'game_username':
                $player_details = $this->ssa_get_player_details_by_game_username($subject, $game_platform_id);
                break;
            default:
                $player_details = null;
                break;
        }

        return $player_details;
    }

    protected function ssa_get_player_balance($game_api, $player_name) {
        $result = $game_api->queryPlayerBalance($player_name);
        return !empty($result['balance']) ? $result['balance'] : 0;
    }

    protected function ssa_get_player_wallet_balance($player_id, $game_platform_id, $validate = false, $use_read_only = true, $set_external_game_id = null) {
        if (!empty($set_external_game_id)) {
            $this->ssa_set_external_game_id($set_external_game_id);
        }

        if ($validate) { // will return result
            $balance = $this->player_model->getPlayerSubWalletBalance($player_id, $game_platform_id, $use_read_only);

            if ($balance === null || $balance === false) {
                $result['success'] = false;
            } else {
                $result['success'] = true;
                $result['balance'] = $balance;
            }

            return $result;
        } else { // will return player balance directly
            return $this->player_model->getPlayerSubWalletBalance($player_id, $game_platform_id, $use_read_only);
        }
    }

    private function ssa_truncate_player_balance($username, $game_platform_id, $precision = 2) {
        $balance = $this->ssa_get_player_balance($username, $game_platform_id);
        return $this->ssa_truncate_amount($balance, $precision);
    }

    protected function ssa_validate_basic_auth_request($username, $password, $separator = ':', $allow_empty_password = true) {
        $is_valid = true;
        $request_headers = $this->ssa_request_headers();
        $authorization_request = isset($request_headers['Authorization']) ? $request_headers['Authorization'] : null;

        $params = [
            $username,
            $password,
        ];

        $compose_auth = $this->ssa_composer($params, $separator);
        $authorization = 'Basic ' . base64_encode($compose_auth);

        $explode_auth_request_base64_encode = explode(' ', $authorization_request);
        $auth_request_base64_encode = isset($explode_auth_request_base64_encode[1]) ? $explode_auth_request_base64_encode[1] : null;

        $auth_request_base64_decode = base64_decode($auth_request_base64_encode);
        $explode_auth_request_base64_decode = explode($separator, $auth_request_base64_decode);

        $auth_request_username = isset($explode_auth_request_base64_decode[0]) ? $explode_auth_request_base64_decode[0] : null;
        $auth_request_password = isset($explode_auth_request_base64_decode[1]) ? $explode_auth_request_base64_decode[1] : null;

        if ($auth_request_username != $username) {
            $this->ssa_custom_message_response = 'Unauthorized: Invalid Username';
            $is_valid = false;
        } else {
            if ($auth_request_password != $password) {
                $this->ssa_custom_message_response = 'Unauthorized: Invalid Password';
                $is_valid = false;
            } else {
                if ($allow_empty_password) {
                    if ($authorization_request != $authorization) {
                        $is_valid = false;
                    }
                } else {
                    if (empty($auth_request_password) || empty($password)) {
                        $this->ssa_custom_message_response = 'Unauthorized: Empty password not allowed';
                        $is_valid =  false;
                    } else {
                        if ($authorization_request != $authorization) {
                            $is_valid = false;
                        }
                    }
                }
            }
        }

        $this->utils->debug_log(__METHOD__, $this->ssa_custom_message_response);
        return $is_valid;
    }

    protected function ssa_validate_basic_auth_request_base64_encode($encoded_data) {
        $request_headers = $this->ssa_request_headers();
        $authorization_request = isset($request_headers['Authorization']) ? $request_headers['Authorization'] : null;
        $authorization = 'Basic ' . $encoded_data;

        return $authorization_request == $authorization;
    }

    /* sample:
        -> set parameter then add the custom http_response_status_code or operator_response.

        sample 1. $set_custom_response = [
                    'token' => [
                        'rules' => ['required'],
                        'http_response_status_code' => 400,
                        'operator_response' => [
                            'code' => 1,
                            'message' => 'parameter token is required'
                        ],
                    ],
                    'gameUsername' => [
                        'http_response_status_code' => 400,
                    ],
                ];

        sample 2. $set_custom_response = [
                    'token' => [
                        'required' => [
                            'http_response_status_code' => 400,
                            'operator_response' => [
                                'code' => 1,
                                'message' => 'parameter token is required'
                            ],
                        ],
                        'positive' => [
                            'http_response_status_code' => 400,
                            'operator_response' => [
                                'code' => 1,
                                'message' => 'parameter token is required'
                            ],
                        ],
                    ],
                    'gameUsername' => [
                        'http_response_status_code' => 400,
                    ],
                ];
    */
    protected function ssa_validate_request_params($request_params, $rule_sets, $set_custom_response = [], $default_operator_response = [], $strict = true) {
        $is_valid = true;
        $this->ssa_http_response_status_code = 400;
        $this->ssa_operator_response = !empty($default_operator_response) ? $default_operator_response : $this->ssa_common_operator_response($this->ssa_http_response_status_code);

        foreach ($rule_sets as $param => $rules) {
            $is_dot_notation_array = $this->isDotNotationArray($param);

            if ($is_dot_notation_array) {
                $request_param = $this->validateDotNotationArray($request_params, $param);
            } else {
                $request_param = isset($request_params[$param]) ? $request_params[$param] : null;
            }

            foreach ($rules as $key => $rule) {
                if (is_string($rule) && strpos($rule, ':') !== false) {
                    list($rule, $value) = $this->explodeRule($rule);
                }

                if (is_string($key)) {
                    if (is_array($rule)) {
                        $rule = $key;
                        $value = $rules[$key];
                    } else {
                        $value = $rule;
                        $rule = $key;
                    }
                }

                switch ($rule) {
                    case 'optional':
                        $is_valid = true;
                        break;
                    case 'array':
                        if (!is_null($request_param) && !is_array($request_param)) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' must be an ' . $rule;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'multidimensional_array':
                        if (!is_null($request_param) && !$this->ssa_is_multidimensional_array($request_param, true)) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' must be ' . $rule;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'nullable':
                        continue 2;
                    case 'required':
                        if ($is_dot_notation_array) {
                            if (!in_array('nullable', $rules)) {
                                if (empty($request_param)) {
                                    $is_valid = false;
                                    $this->ssa_custom_message_response = 'Parameter ' . $param . ' is ' . $rule;
                                    $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                                }
                            }
                        } else {
                            if (in_array('nullable', $rules) || in_array('boolean', $rules)) {
                                if (!array_key_exists($param, $request_params)) {
                                    $is_valid = false;
                                    $this->ssa_custom_message_response = 'Parameter ' . $param . ' is ' . $rule;
                                    $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                                }
                            } else {
                                if (!array_key_exists($param, $request_params) || empty($request_param)) {
                                    $is_valid = false;
                                    $this->ssa_custom_message_response = 'Parameter ' . $param . ' is ' . $rule;
                                    $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                                }
                            } 
                        }
                        break;
                    case 'string':
                        if (!is_null($request_param) && !is_string($request_param)) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' must be ' . $rule;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'integer':
                        if (!is_null($request_param) && !is_integer($request_param)) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' must be ' . $rule;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'float':
                        if (!is_null($request_param) && !is_float($request_param)) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' must be ' . $rule;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'double':
                        if (!is_null($request_param) && !is_double($request_param)) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' must be ' . $rule;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'numeric':
                        if (!is_null($request_param) && !is_numeric($request_param)) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' must be ' . $rule;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'positive':
                        if (!is_null($request_param) && $request_param < 0) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' must be ' . $rule;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'negative':
                        if (!is_null($request_param) && $request_param > 0) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' must be ' . $rule;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'greater_than':
                        if (!is_null($request_param) && intval($request_param) <= $value) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' is expected to be ' . str_replace('_', ' ', $rule) . ' ' . $value;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'less_than':
                        if (!is_null($request_param) && intval($request_param) >= $value) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' must be ' . str_replace('_', ' ', $rule) . ' ' . $value;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'date_format:RFC3339':
                        if (!is_null($request_param) && !$this->ssa_validate_datetime('RFC3339', $request_param)) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' must be ' . $rule;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'date_format:ISO8601':
                        if (!is_null($request_param) && !$this->ssa_validate_datetime('ISO8601', $request_param)) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' must be ' . $rule;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'minimum_size':
                        if (!is_null($request_param) && ($value !== null) && (strlen($request_param) < $value)) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' must be ' . str_replace('_', ' ', $rule) . ' ' . $value;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'maximum_size':
                        if (!is_null($request_param) && ($value !== null) && (strlen($request_param) > $value)) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' must be ' . str_replace('_', ' ', $rule) . ' ' . $value;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'boolean':
                        if (!is_null($request_param) && !is_bool($request_param)) {
                            if (in_array($request_param, [0, 1], true)) {
                                $is_valid = true;
                            } else {
                                $is_valid = false;
                                $this->ssa_custom_message_response = 'Parameter ' . $param . ' must be ' . $rule . ' type';
                                $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                            }
                        }
                        break;
                    case 'expected_value':
                        if ($strict) {
                            if (!is_null($request_param) && $request_param != $value) {
                                $is_valid = false;
                                $this->ssa_custom_message_response = "Invalid parameter {$param}";
                                $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                                $this->ssa_hint[$param] = $value;
                            }
                        } else {
                            if (!is_null($request_param) && strtolower($request_param) != strtolower($value)) {
                                $is_valid = false;
                                $this->ssa_custom_message_response = "Invalid parameter {$param}";
                                $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                                $this->ssa_hint[$param] = $value;
                            }
                        }
                        break;
                    case 'expected_value_in':
                        if (!is_array($value)) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = "Rule ({$rule}): must be an array";
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                            $this->ssa_hint[$param] = $value;
                            break;
                        }

                        if ($strict) {
                            if (!is_null($request_param) && is_array($value) && !in_array($request_param, $value)) {
                                $is_valid = false;
                                $this->ssa_custom_message_response = "Invalid parameter {$param}";
                                $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                                $this->ssa_hint[$param] = $value;
                            }
                        } else {
                            if (!is_null($request_param) && is_array($value) && !in_array(strtolower($request_param), $value)) {
                                $is_valid = false;
                                $this->ssa_custom_message_response = "Invalid parameter {$param}";
                                $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                                $this->ssa_hint[$param] = $value;
                            }
                        }
                        break;
                    case 'ip_address':
                        if (!is_null($request_param) && !filter_var($request_param, FILTER_VALIDATE_IP, [FILTER_FLAG_IPV4, FILTER_FLAG_IPV6])) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = "Invalid parameter {$param}, must be a valid IP address";
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        }
                        break;
                    case 'min':
                        if (!is_null($request_param)) {
                            if (is_string($request_param) && strlen($request_param) < $value) {
                                $is_valid = false;
                                $this->ssa_custom_message_response = 'Parameter ' . $param . ' ' . str_replace('_', ' ', $rule) . ' length ' . $value;
                                $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                                break;
                            }

                            if (is_numeric($request_param) && $request_param < $value) {
                                $is_valid = false;
                                $this->ssa_custom_message_response = "Parameter {$param} {$rule} {$value}";
                                $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                                break;
                            }
                        }
                        break;
                    case 'max':
                        if (!is_null($request_param)) {
                            if (is_string($request_param) && strlen($request_param) > $value) {
                                $is_valid = false;
                                $this->ssa_custom_message_response = 'Parameter ' . $param . ' ' . str_replace('_', ' ', $rule) . ' length ' . $value;
                                $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                                break;
                            }

                            if (is_numeric($request_param) && $request_param > $value) {
                                $is_valid = false;
                                $this->ssa_custom_message_response = "Parameter {$param} {$rule} {$value}";
                                $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                                break;
                            }
                        }
                        break;
                    case 'min_length':
                        if (!is_null($request_param) && strlen($request_param) < $value) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' ' . str_replace('_', ' ', $rule) . ' length ' . $value;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                            break;
                        }
                        break;
                    case 'max_length':
                        if (!is_null($request_param) && strlen($request_param) > $value) {
                            $is_valid = false;
                            $this->ssa_custom_message_response = 'Parameter ' . $param . ' ' . str_replace('_', ' ', $rule) . ' length ' . $value;
                            $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                            break;
                        }
                        break;
                    case 'if_exist': //? array
                        /* $value params
                            - table_name (string)
                            - where (array | string)
                            - http_response_status_code (int)
                            - operator_response (array)
                            - return (bool)
                            - use_monthly_transactions_table (bool)
                            - previous_table (string)
                        */
                        if (!is_null($request_param)) {
                            $table_name = !empty($value['table_name']) ? $value['table_name'] : null;
                            $where = !empty($value['where']) ? $value['where'] : null;
                            $use_monthly_transactions_table = isset($value['use_monthly_transactions_table']) && $value['use_monthly_transactions_table'] ? true : false;
                            $force_check_previous_transactions_table = isset($value['force_check_previous_transactions_table']) && $value['force_check_previous_transactions_table'] ? true : false;
                            $previous_table = !empty($value['previous_table']) ? $value['previous_table'] : null;

                            $is_exist = $this->ssa_is_transaction_exists($table_name, $where);

                            if ($use_monthly_transactions_table && $this->ssa_check_previous_year_month_data($force_check_previous_transactions_table)) {
                                if (!empty($previous_table) && !$is_exist) {
                                    $is_exist = $this->ssa_is_transaction_exists($previous_table, $where);
                                }
                            }

                            if (!$value['return']) {
                                if ($is_exist) {
                                    $is_valid = false;
                                    $this->ssa_custom_message_response = "{$param} already exist";
                                    $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);

                                    // will override $set_custom_response
                                    if (isset($value['http_response_status_code'])) {
                                        $this->ssa_http_response_status_code = $value['http_response_status_code'];
                                    }

                                    if (isset($value['operator_response'])) {
                                        $this->ssa_operator_response = $value['operator_response'];

                                        if (isset($value['operator_response']['message'])) {
                                            $this->ssa_custom_message_response = $value['operator_response']['message'];
                                        }
                                    }

                                    break;
                                }
                            }
                        }
                        break;
                    default:
                        $is_valid = false;
                        $this->ssa_custom_message_response = "Invalid rule '" . $rule . "' on parameter '" . $param . "'";
                        $this->processCustomResponse($param, $set_custom_response, $rule, $default_operator_response);
                        break;
                }
            }

            if (!$is_valid) {
                break;
            } else {
                $this->ssa_http_response_status_code = 200;
            }
        }

        return $is_valid;
    }

    private function processCustomResponse($array_key, $set_custom_response, $rule = [], $default_operator_response = []) {
        $success = true;
        $this->ssa_request_param_key = $array_key;

        if (!empty($set_custom_response) && is_array($set_custom_response) && array_key_exists($array_key, $set_custom_response)) {
            foreach ($set_custom_response as $key => $response) {
                if ($key == $array_key) {
                    // use custom response to all set rules else use custom response to specific rule
                    if (isset($response['rules'])) {
                        if (in_array($rule, $response['rules'])) {
                            $this->ssa_http_response_status_code = !empty($response['http_response_status_code']) ? $response['http_response_status_code'] : 400;
                            $this->ssa_operator_response = !empty($response['operator_response']) ? $response['operator_response'] : $this->ssa_common_operator_response($this->ssa_http_response_status_code);
                            $this->ssa_custom_message_response = isset($response['operator_response']['message']) ? $response['operator_response']['message'] : null;
                            break;
                        }
                    } else {
                        if (!empty($response[$rule])) { // use custom response to specific rule
                            $this->ssa_http_response_status_code = !empty($response[$rule]['http_response_status_code']) ? $response[$rule]['http_response_status_code'] : 400;
                            $this->ssa_operator_response = !empty($response[$rule]['operator_response']) ? $response[$rule]['operator_response'] : $this->ssa_common_operator_response($this->ssa_http_response_status_code);
                            $this->ssa_custom_message_response = !empty($response[$rule]['operator_response']['message']) ? $response[$rule]['operator_response']['message'] : null;
                        } else {
                            if (!empty($response['http_response_status_code'])) {
                                $this->ssa_http_response_status_code = $response['http_response_status_code'];
                            } else {
                                $this->ssa_http_response_status_code = !empty($response[$rule]['http_response_status_code']) ? $response[$rule]['http_response_status_code'] : 400;
                            }

                            if (!empty($response['operator_response'])) {
                                $this->ssa_http_response_status_code = $response['operator_response'];
                            } else {
                                $this->ssa_http_response_status_code = !empty($response[$rule]['operator_response']) ? $response[$rule]['operator_response'] : 400;
                            }

                            if (!empty($response['operator_response']['message'])) {
                                $this->ssa_http_response_status_code = $response['operator_response']['message'];
                            } else {
                                $this->ssa_http_response_status_code = !empty($response[$rule]['operator_response']['message']) ? $response[$rule]['operator_response']['message'] : 400;
                            }
                        }
                        break;
                    }
                }
            }
        } else {
            $success = false;
            $this->ssa_http_response_status_code = 400;
            $this->ssa_operator_response = !empty($default_operator_response) ? $default_operator_response : $this->ssa_common_operator_response($this->ssa_http_response_status_code);
        }

        return $success;
    }

    protected function isDotNotationArray($param) {
        return is_string($param) && strpos($param, '.') !== false;
    }

    protected function validateDotNotationArray($request, $path) {
        $keys = explode('.', $path);

        foreach ($keys as $segment) {
            if (!isset($request[$segment])) {
                $request = null;
                break;
            }
            $request = $request[$segment];
        }

        return $request;
    }

    protected function ssa_validate_request_currency($currency, $request_currency) {
        return $currency == $request_currency;
    }

    protected function ssa_validate_datetime($format, $datetime) {
        switch ($format) {
            case 'RFC3339':
                $have_decimal = strpos($datetime, '.');

                if ($have_decimal !== false) {
                    if (DateTime::createFromFormat('Y-m-d\TH:i:s.uP', $datetime) === FALSE) {
                        $date_parse = date_parse($datetime);
                        $tz_abbr = isset($date_parse['tz_abbr']) ? $date_parse['tz_abbr'] : null;
                        $find_milliseconds = isset(explode('.', $datetime)[1]) ? explode('.', $datetime)[1] : null;
                        $milliseconds = isset(explode('-', $find_milliseconds)[0]) ? explode('-', $find_milliseconds)[0] : null;
                        $count_milliseconds = strlen($milliseconds);
                        $utc = isset(explode('-', $find_milliseconds)[1]) ? '-' . explode('-', $find_milliseconds)[1] : null;

                        if (!empty($tz_abbr)) {
                            return false;
                        }

                        if ($utc != $this->utc) {
                            return false;
                        }

                        // up to 9 digit milliseconds supported
                        if (strlen($count_milliseconds) > 9) {
                            $this->ssa_custom_message_response = "Datetime Format Error: {$count_milliseconds} digit milliseconds not supported";
                            return false;
                        }
                    } else {
                        $utc = isset(explode('-', $datetime)[3]) ? '-' . explode('-', $datetime)[3] : null;

                        if ($utc != $this->utc) {
                            return false;
                        }
                    }
                } else {
                    if (DateTime::createFromFormat(DateTime::RFC3339, $datetime) === FALSE) { //'Y-m-d\TH:i:sP'
                        return false;
                    } else {
                        $utc = isset(explode('-', $datetime)[3]) ? '-' . explode('-', $datetime)[3] : null;

                        if ($utc != $this->utc) {
                            return false;
                        }
                    }
                }
                break;
            case 'ISO8601':
                if (DateTime::createFromFormat(DateTime::ISO8601, $datetime) === FALSE) {
                    return false;
                } else {
                    return true;
                }
            default:
                return false;
        }
    }

    protected function ssa_format_dateTime($format = 'Y-m-d H:i:s', $datetime = null) {
        if (empty($datetime)) {
            $datetime = $this->utils->getNowForMysql();
        }

        switch ($format) {
            case 'RFC3339':
                $datetime_formatted = date(DateTime::RFC3339, strtotime($datetime));
                break;
            case 'ISO8601':
                $datetime_formatted = date('Y-m-d\TH:i:s', strtotime($datetime)) . 'Z';
                break;
            default:
                $datetime_formatted = date($format, strtotime($datetime));
            break;
        }

        return $datetime_formatted;
    }

    private function explodeRule($original_rule, $value = 0) {
        if (strpos($original_rule, ':') !== false) {
            $rule = !empty(explode(':', $original_rule)[0]) ? explode(':', $original_rule)[0] : $original_rule;
            $value = !empty(explode(':', $original_rule)[1]) ? explode(':', $original_rule)[1] : $value;
        } else {
            $rule = $original_rule;
        }

        return array($rule, $value);
    }

    protected function ssa_truncate_amount($amount, $precision = 2) {
        $amount = floatval($amount);

        if ($amount == 0) {
            return $amount;
        }

        return floatval(bcdiv($amount, 1, $precision));
    }

    protected function ssa_multiplying_conversion($amount, $conversion = 1) {
        return $amount * $conversion;
    }

    protected function ssa_multiplying_conversion_with_truncate_amount($amount, $conversion = 1, $precision = 2) {
        $value = floatval($amount * $conversion);
        return $this->ssa_truncate_amount($value, $precision);
    }

    protected function ssa_multiplying_conversion_with_round($amount, $conversion = 1, $precision = 2) {
        $value = floatval($amount * $conversion);
        return round($value, $precision);
    }

    protected function ssa_dividing_conversion($amount, $conversion = 1) {
        return $amount / $conversion;
    }

    protected function ssa_dividing_conversion_with_truncate_amount($amount, $conversion = 1, $precision = 2) {
        $value = floatval($amount / $conversion);
        return $this->ssa_truncate_amount($value, $precision);
    }

    protected function ssa_dividing_conversion_with_round($amount, $conversion = 1, $precision = 2) {
        $value = floatval($amount / $conversion);
        return round($value, $precision);
    }

    protected function ssa_operate_amount($amount, $precision = 2, $conversion = 1, $arithmetic_name = '') {
        if (!empty($amount)) {
            switch ($arithmetic_name) {
                case 'multiplication':
                    $value = floatval($amount * $conversion);
                    break;
                case 'division':
                    $value = floatval($amount / $conversion);
                    break;
                case 'addition':
                    $value = floatval($amount + $conversion);
                    break;
                case 'subtraction':
                    $value = floatval($amount - $conversion);
                    break;
                default:
                    $value = floatval($amount);
                    break;
            }
        } else {
            $value = 0;
        }

        return $this->ssa_truncate_amount($value, $precision);
    }

    protected function ssa_amount_conversion($amount, $precision = 2, $conversion = 1, $arithmetic_name = '') {
        return $this->ssa_operate_amount($amount, $precision, $conversion, $arithmetic_name);
    }

    # can use $this->utils->mergeArrayValues($array, $separator);
    protected function ssa_composer($params, $pattern = '-') {
        $compose = array_reduce($params, function ($param1, $param2) use ($pattern) {
            return $param1 . $pattern . $param2;
        });

        return ltrim($compose, $pattern);
    }

    protected function ssa_get_http_response($http_response_status_code, $default_http_response_status_code = 500) {
        if (!is_numeric($http_response_status_code)) {
            $http_response_status_code = 500;
        }

        return isset($this->ssa_http_response_status_code_list[$http_response_status_code]) ? $this->ssa_http_response_status_code_list[$http_response_status_code] : $this->ssa_http_response_status_code_list[$default_http_response_status_code];
    }

    protected function ssa_common_operator_response($http_response_status_code, $custom_message = '', $default_http_response_status_code = 500) {
        if (!is_numeric($http_response_status_code)) {
            $http_response_status_code = $default_http_response_status_code;
        }

        $http_response = isset($this->ssa_http_response_status_code_list[$http_response_status_code]) ? $this->ssa_http_response_status_code_list[$http_response_status_code] : $this->ssa_http_response_status_code_list[$default_http_response_status_code];

        $common_operator_response = [
            'code' => isset($http_response['code']) ? $http_response['code'] : null,
            'message' => isset($http_response['text']) ? $http_response['text'] : null,
        ];

        if (!empty($custom_message)) {
            $common_operator_response['message'] = $custom_message;
        }

        return $common_operator_response;
    }

    protected function ssa_operator_response_custom_message($operator_response, $custom_message = '') {
        $operator_response['message'] = !empty($custom_message) ? $custom_message : $operator_response['message'];
        return $operator_response;
    }

    protected function ssa_save_response_result($game_platform_id, $flag, $request_api, $request_params, $operator_response, $http_response, $player_id = null, $extra = [], $fields = []) {
        $this->load->model(['response_result']);
        $cost_ms = intval($this->utils->getExecutionTimeToNow() * 1000);
        $request_headers = $this->ssa_request_headers();

        $default_extra = [
            'request_headers' => $request_headers,
            'callback_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
        ];

        $extra = array_merge($default_extra, $extra);

        $default_fields = [
            'player_id' => !empty($player_id) ? $player_id : null,
        ];

        // response_results table fields
        $fields = array_merge($default_fields, $fields);
     
        $response_result_id = $this->response_result->saveResponseResult(
            $game_platform_id,
            $flag,
            $request_api,
            is_array($request_params) ? json_encode($request_params) : $request_params,
            $operator_response,
            isset($http_response['code']) ? $http_response['code'] : null,
            isset($http_response['text']) ? $http_response['text'] : null,
            is_array($extra) ? json_encode($extra) : $extra,
            $fields,
            false,
            null,
            $cost_ms
        );

        return $response_result_id;
    }

    protected function ssa_return_unimplemented() {
        return [
            'success' => true,
            'unimplemented' => true,
        ];
    }

    protected function ssa_set_uniqueid_of_seamless_service($uniqueid_of_seamless_service, $external_game_id = null) {
        $this->load->model(['wallet_model']);

        if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
            return $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $external_game_id);
        } else {
            return false;
        }
    }

    protected function ssa_set_game_provider_bet_amount($amount) {
        $this->load->model(['wallet_model']);

        if (method_exists($this->wallet_model, 'setGameProviderBetAmount')) {
            return $this->wallet_model->setGameProviderBetAmount($amount);
        } else {
            return false;
        }
    }

    protected function ssa_set_game_provider_payout_amount($amount) {
        $this->load->model(['wallet_model']);

        if (method_exists($this->wallet_model, 'setGameProviderPayoutAmount')) {
            return $this->wallet_model->setGameProviderPayoutAmount($amount);
        } else {
            return false;
        }
    }

    protected function ssa_set_external_game_id($external_game_id) {
        $this->load->model(['wallet_model']);

        if (method_exists($this->wallet_model, 'setExternalGameId')) {
            return $this->wallet_model->setExternalGameId($external_game_id);
        } else {
            return false;
        }
    }

    protected function ssa_set_game_provider_action_type($action_type) {
        $this->load->model(['wallet_model']);

        if (method_exists($this->wallet_model, 'setGameProviderActionType')) {
            return $this->wallet_model->setGameProviderActionType($action_type);
        } else {
            return false;
        }
    }

    protected function ssa_set_game_provider_round_id($round_id) {
        $this->load->model(['wallet_model']);

        if (method_exists($this->wallet_model, 'setGameProviderRoundId')) {
            return $this->wallet_model->setGameProviderRoundId($round_id);
        } else {
            return false;
        }
    }

    protected function ssa_set_game_provider_is_end_round($is_end) {
        $this->load->model(['wallet_model']);

        return $this->wallet_model->setGameProviderIsEndRound($is_end);
    }

    protected function ssa_set_related_uniqueid_of_seamless_service($related_uniqueid_of_seamless_service) {
        $this->load->model(['wallet_model']);

        if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
            return $this->wallet_model->setRelatedUniqueidOfSeamlessService($related_uniqueid_of_seamless_service);
        } else {
            return false;
        }
    }

    protected function ssa_set_related_uniqueid_array_of_seamless_service($related_uniqueid_array_of_seamless_service) {
        $this->load->model(['wallet_model']);

        if (method_exists($this->wallet_model, 'setRelatedUniqueidArrayOfSeamlessService')) {
            return $this->wallet_model->setRelatedUniqueidArrayOfSeamlessService($related_uniqueid_array_of_seamless_service);
        } else {
            return false;
        }
    }

    protected function ssa_set_related_action_of_seamless_service($related_uniqueid_of_seamless_service) {
        $this->load->model(['wallet_model']);

        if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
            return $this->wallet_model->setRelatedActionOfSeamlessService($related_uniqueid_of_seamless_service);
        } else {
            return false;
        }
    }

    protected function ssa_save_failed_request($table, $query_type, $data = [], $field_name = null, $field_value = null, $update_with_result = false) {
        $this->load->model(['game_seamless_service_logs']);

        return $this->game_seamless_service_logs->save($table, $query_type, $data, $field_name, $field_value, $update_with_result);
    }

    protected function ssa_is_md5_sum_logs_exist($table, $md5_sum) {
        $this->load->model(['game_seamless_service_logs']);

        return $this->game_seamless_service_logs->isMd5SumExist($table, $md5_sum);
    }

    protected function ssa_get_logs_call_count($table, $md5_sum) {
        $this->load->model(['game_seamless_service_logs']);

        return $this->game_seamless_service_logs->getCallCount($table, $md5_sum);
    }

    protected function ssa_date_time_modifier($date_time = null, $modifier = '+0 hours', $format = 'Y-m-d H:i:s') {
        if (empty($date_time)) {
            $date_time = new DateTime($this->utils->getNowForMysql());
        } else {
            $date_time = new DateTime($date_time);
        }

        date_modify($date_time, $modifier);

        return date_format($date_time, $format);
    }

    protected function ssa_get_player_id_by_external_token($token, $game_platform_id) {
        $this->load->model(['external_common_tokens']);

        return $this->external_common_tokens->getPlayerIdByExternalToken($token, $game_platform_id);
    }

    protected function ssa_get_external_token_info_by_token($token) {
        $this->load->model(['external_common_tokens']);

        return $this->external_common_tokens->getExternalCommonTokenInfoByToken($token);
    }

    protected function ssa_get_timeticks($get_usec = false) {
        $unix_timestamp = $get_usec ? microtime(true) : time();
        return intval(number_format(($unix_timestamp * 10000000) + 621355968000000000 , 0, '.', ''));
    }

    protected function ssa_response_result($config = [
        'response' => [],
        'add_origin' => true,
        'origin' => '*',
        'http_status_code' => 200,
        'http_status_text' => '',
        'content_type' => 'application/json',
        'xml' => false,
        'stand_alone' => false,
    ]) {

        if (!isset($config['response'])) {
            $config['response'] = [];
        }

        if (!isset($config['add_origin'])) {
            $config['add_origin'] = true;
        }

        if (!isset($config['origin'])) {
            $config['origin'] = '*';
        }

        if (!isset($config['http_status_code'])) {
            $config['http_status_code'] = 200;
        }

        if (!isset($config['http_status_text'])) {
            $config['http_status_text'] = '';
        }

        if (!isset($config['content_type'])) {
            $config['content_type'] = 'application/json';
        }

        if (!isset($config['xml'])) {
            $config['xml'] = false;
        }

        if (!isset($config['stand_alone'])) {
            $config['stand_alone'] = false;
        }

        switch ($config['content_type']) {
            case 'application/json':
                $result = json_encode($config['response']);
                break;
            case 'application/xml':
                $result = $this->arrayToXmlStandAlone($config['response'], $config['xml'], $config['stand_alone']);
                break;
            case 'text/plain':
                $result = trim(stripslashes(json_encode($config['response'])), '"');
                break;
            default:
                $result = json_encode($config['response']);
                break;
        }

        $this->output->set_status_header($config['http_status_code'], $config['http_status_text'])->set_content_type($config['content_type'])->set_output($result);

        $customHeader = $this->utils->getConfig('player_center_api_x_custom_header');
        $this->utils->debug_log(__FUNCTION__, 'customHeader', $customHeader);

        if ($config['add_origin']) {
            $this->addOriginHeader($config['origin']);
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Expose-Headers: X-Requested-With, Access-Control-Allow-Origin' . $customHeader);
            header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, authorization' . $customHeader);
            header('Access-Control-Allow-Credentials: true');
            header('X-Content-Type-Options: nosniff');
        }

        return true;
    }

    protected function ssa_get_ip_address() {
        $ip = $this->utils->getIp();

        if ($ip == '0.0.0.0') {
            $ip = $this->input->getRemoteAddr();
        }

        return $ip;
    }

    protected function ssa_is_multidimensional_array($array, $any_array_key_type = false) {
        // default, will check array key numeric
        if (isset($array[0])) {
            return true;
        }

        // will check array key numeric and string
        if ($any_array_key_type) {
            foreach ($array as $value) {
                if (isset($value) && is_array($value)) {
                    return true;
                } else {
                    break;
                }
            }
        }

        return false;
    }

    protected function ssa_db() {
        $this->load->model(['original_seamless_wallet_transactions']);

        return $this->original_seamless_wallet_transactions;
    }

    protected function ssa_enabled_remote_wallet() {
        if (!empty($this->utils->getConfig('enabled_remote_wallet_client_on_currency'))) {
            return $this->utils->isEnabledRemoteWalletClient();
        } else {
            return false;
        }
    }

    protected function ssa_get_remote_wallet_error_code() {
        $this->load->model(['wallet_model']);

        if (method_exists($this->wallet_model, 'getRemoteWalletErrorCode')) {
            return $this->wallet_model->getRemoteWalletErrorCode();
        } else {
            return null;
        }
    }

    protected function ssa_remote_wallet_error_double_unique_id() {
        if ($this->ssa_get_remote_wallet_error_code() == $this->ssa_remote_wallet_code_double_unique_id) {
            $this->ssa_system_error[$this->ssa_system_error_remote_wallet_double_unique_id] = $this->ssa_system_error_remote_wallet_double_unique_id;
            return true;
        } else {
            return false;
        }
    }

    protected function ssa_remote_wallet_error_invalid_unique_id() {
        if ($this->ssa_get_remote_wallet_error_code() == $this->ssa_remote_wallet_code_invalid_unique_id) {
            $this->ssa_system_error[$this->ssa_system_error_remote_wallet_invalid_unique_id] = $this->ssa_system_error_remote_wallet_invalid_unique_id;
            return true;
        } else {
            return false;
        }
    }

    protected function ssa_remote_wallet_error_insufficient_balance() {
        if ($this->ssa_get_remote_wallet_error_code() == $this->ssa_remote_wallet_code_insufficient_balance) {
            $this->ssa_system_error[$this->ssa_system_error_remote_wallet_insufficient_balance] = $this->ssa_system_error_remote_wallet_insufficient_balance;
            return true;
        } else {
            return false;
        }
    }

    protected function ssa_remote_wallet_error_maintenance() {
        if ($this->ssa_get_remote_wallet_error_code() == $this->ssa_remote_wallet_code_maintenance) {
            $this->ssa_system_error[$this->ssa_system_error_remote_wallet_maintenance] = $this->ssa_system_error_remote_wallet_maintenance;
            return true;
        } else {
            return false;
        }
    }

    protected function ssa_remote_wallet_error_game_not_available() {
        if ($this->ssa_get_remote_wallet_error_code() == $this->ssa_remote_wallet_game_not_available) {
            $this->ssa_system_error[$this->ssa_system_error_remote_wallet_game_not_available] = $this->ssa_system_error_remote_wallet_game_not_available;
            return true;
        } else {
            return false;
        }
    }

    protected function ssa_remote_wallet_code($key) {
        switch ($key) {
            case $this->ssa_system_error_remote_wallet_double_unique_id:
                $code = $this->ssa_remote_wallet_code_double_unique_id;
                break;
            case $this->ssa_system_error_remote_wallet_invalid_unique_id:
                $code = $this->ssa_remote_wallet_code_invalid_unique_id;
                break;
            case $this->ssa_system_error_remote_wallet_insufficient_balance:
                $code = $this->ssa_remote_wallet_code_insufficient_balance;
                break;
            case $this->ssa_system_error_remote_wallet_maintenance:
                $code = $this->ssa_remote_wallet_code_maintenance;
                break;
            case $this->ssa_system_error_remote_wallet_game_not_available:
                $code = $this->ssa_remote_wallet_game_not_available;
                break;
            default:
                $code = null;
                break;
        }

        return $code;
    }

    protected function ssa_initialize_game_api($game_platform_id) {
        $this->ssa_game_api = $this->ssa_load_game_api_class($game_platform_id);

        return $this->ssa_game_api;
    }

    protected function ssa_system_checkpoint($config = [
        // use system error
        'USE_SERVER_IP_ADDRESS_NOT_ALLOWED' => false,
        'USE_GAME_API_DISABLED' => false,
        'USE_GAME_API_MAINTENANCE' => false,
        'USE_PLAYER_BLOCKED' => false,
    ], $game_api = null) {
        if (empty($game_api)) {
            if (!empty($this->ssa_game_api)) {
                $game_api = $this->ssa_game_api;
            } else {
                $this->ssa_system_error['ssa_is_game_api_disabled'] = 'Failed to initialize game api';
                $this->utils->debug_log(__METHOD__, $this->ssa_system_error);
                return false;
            }
        }

        $config['USE_SERVER_IP_ADDRESS_NOT_ALLOWED'] = isset($config['USE_SERVER_IP_ADDRESS_NOT_ALLOWED']) && $config['USE_SERVER_IP_ADDRESS_NOT_ALLOWED'] ? true : false;
        $config['USE_GAME_API_DISABLED'] = isset($config['USE_GAME_API_DISABLED']) && $config['USE_GAME_API_DISABLED'] ? true : false;
        $config['USE_GAME_API_MAINTENANCE'] = isset($config['USE_GAME_API_MAINTENANCE']) && $config['USE_GAME_API_MAINTENANCE'] ? true : false;
        $config['USE_PLAYER_BLOCKED'] = isset($config['USE_PLAYER_BLOCKED']) && $config['USE_PLAYER_BLOCKED'] ? true : false;

        if ($config['USE_SERVER_IP_ADDRESS_NOT_ALLOWED']) {
            if ($this->ssa_is_server_ip_not_allowed($game_api)) {
                $this->ssa_system_error[$this->ssa_system_error_server_ip_address_not_allowed] = $this->ssa_system_error_server_ip_address_not_allowed;
            }
        }

        if ($config['USE_GAME_API_DISABLED']) {
            if ($this->ssa_is_game_api_disabled($game_api)) {
                $this->ssa_system_error[$this->ssa_system_error_game_api_disabled] = $this->ssa_system_error_game_api_disabled;
            }
        }

        if ($config['USE_GAME_API_MAINTENANCE']) {
            if ($this->ssa_is_game_api_maintenance($game_api)) {
                $this->ssa_system_error[$this->ssa_system_error_game_api_maintenance] = $this->ssa_system_error_game_api_maintenance;
            }
        }

        if ($config['USE_PLAYER_BLOCKED']) {
            if (!empty($this->ssa_player_game_username)) {
                if ($this->ssa_is_player_blocked($game_api, $this->ssa_player_game_username, true)) {
                    $this->ssa_system_error[$this->ssa_system_error_player_blocked] = $this->ssa_system_error_player_blocked;
                }
            }
        }

        return !empty($this->ssa_system_error) ? $this->ssa_system_error : [];
    }

    protected function ssa_system_errors($key = null) {
        if (!empty($key)) {
            return isset($this->ssa_system_error[$key]) ? true : false;
        } else {
            return !empty($this->ssa_system_error) ? $this->ssa_system_error : [];
        }
    }

    protected function ssa_initialize_player($get_balance = false, $get_player_by = 'token', $subject, $game_platform_id, $refresh_timout = true, $min_span_allowed = 10, $minutes_to_add = 120) {
        if ($get_player_by == $this->ssa_subject_type_token) {
            // get player details by token
            $this->ssa_player_details = $this->ssa_get_player_details($this->ssa_subject_type_token, $subject, $this->game_platform_id, $refresh_timout, $min_span_allowed, $minutes_to_add);

            if (empty($this->ssa_player_details)) {
                $this->ssa_system_error[$this->ssa_system_error_invalid_player_token] = $this->ssa_system_error_invalid_player_token;
                $this->ssa_system_error[$this->ssa_system_error_player_not_found] = $this->ssa_system_error_player_not_found;
            }
        } elseif ($get_player_by == $this->ssa_subject_type_game_username) {
            // get player details by player game username
            $this->ssa_player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $subject, $this->game_platform_id);

            if (empty($this->ssa_player_details)) {
                $this->ssa_system_error[$this->ssa_system_error_invalid_player_game_username] = $this->ssa_system_error_invalid_player_game_username;
                $this->ssa_system_error[$this->ssa_system_error_player_not_found] = $this->ssa_system_error_player_not_found;
            }
        } else {
            $this->ssa_system_error[$this->ssa_system_error_invalid_player_subject_type] = $this->ssa_system_error_invalid_player_subject_type;
            $this->ssa_system_error[$this->ssa_system_error_player_not_found] = $this->ssa_system_error_player_not_found;
        }

        if (!empty($this->ssa_player_details)) {
            if ($get_balance) {
                $this->ssa_player_balance = $this->ssa_get_player_wallet_balance($this->ssa_player_details['player_id'], $game_platform_id, false, false, $this->ssa_external_game_id);
            }
        } else {
            return false;
        }

        return true;
    }

    protected function ssa_player_details() {
        return $this->ssa_player_details;
    }

    protected function ssa_validate_request_player_game_username($request_player_game_username, $player_game_username) {
        if ($request_player_game_username != $player_game_username) {
            $this->ssa_system_error[$this->ssa_system_error_player_not_found] = $this->ssa_system_error_player_not_found;
            return false;
        } else {
            return true;
        }
    }

    protected function ssa_system_logs($key = null) {
        switch ($key) {
            case 'ssa_system_errors':
                $log = $this->ssa_system_errors();
                break;
            case 'ssa_hint':
                $log = $this->ssa_hint;
                break;
            default:
                $log = [
                    'ssa_system_errors' => [
                        'type' => 'method',
                        'count' => count($this->ssa_system_errors()),
                        'value' => $this->ssa_system_errors()
                    ],
                    'ssa_hint' => [
                        'type' => 'property',
                        'count' => count($this->ssa_hint),
                        'value' => $this->ssa_hint
                    ]
                ];
                break;
        }

        return $log;
    }

    protected function ssa_switch_db($currency, $force_switch = false) {
        if ($this->utils->isEnabledMDB()) {
            if (!$force_switch) {
                // check if is currency domain, do not switch
                if ($this->utils->isCurrencyDomain()) {
                    $this->ssa_system_error[$this->ssa_system_error_is_currency_domain] = $this->ssa_system_error_is_currency_domain;
                    return false;
                }
            }

            // validate currency name
            if ($this->utils->isAvailableCurrencyKey($currency, false)) {
                // switch to target db
                $_multiple_db = Multiple_db::getSingletonInstance();
                $_multiple_db->switchCIDatabase(strtolower($currency));
                return true;
            } else {
                $this->ssa_system_error[$this->ssa_system_error_invalid_currency_key] = $this->ssa_system_error_invalid_currency_key;
                return false;
            }
        }

        return null;
    }

    protected function ssa_get_player_common_token_by_player_id($player_id, $timeout = null) {
        $this->load->model(['common_token']);
        return $this->common_token->getPlayerToken($player_id, $timeout);
    }

    protected function ssa_get_player_common_token_by_player_game_username($player_game_username, $game_platform_id, $timeout = null) {
        $this->load->model(['common_token', 'game_provider_auth']);

        $player_id = $this->game_provider_auth->getPlayerIdByPlayerName($player_game_username, $game_platform_id);
        return $this->common_token->getPlayerToken($player_id, $timeout);
    }

    protected function ssa_get_player_common_token_by_player_username($player_username, $timeout = null) {
        $this->load->model(['common_token']);

        $player_id = $this->player_model->getPlayerIdByUsername($player_username);
        return $this->common_token->getPlayerToken($player_id, $timeout);
    }

    protected function ssa_update_player_token($player_id, $token, $timeout = null) {
        $this->load->model(['common_token']);

        return $this->common_token->updatePlayerToken($player_id, $token, $timeout);
    }

    protected function ssa_set_response($http_response_status_code, $operator_response, $custom_message = null) {
        $this->ssa_http_response_status_code = $http_response_status_code;

        if (!empty($custom_message)) {
            $operator_response['message'] = $custom_message;
        }

        $this->ssa_operator_response = $operator_response;

        return [
            'http_response_status_code' => $this->ssa_http_response_status_code,
            'operator_response' => $this->ssa_operator_response,
        ];
    }

    protected function ssa_get_http_response_status_code() {
        return $this->ssa_http_response_status_code;
    }

    protected function ssa_set_http_response_status_code($http_response_status_code) {
        return $this->ssa_http_response_status_code = $http_response_status_code;
    }

    protected function ssa_get_operator_response($is_array = true) {
        return $is_array ? (array) $this->ssa_operator_response : (object) $this->ssa_operator_response;
    }

    protected function ssa_set_operator_response($operator_response) {
        return $this->ssa_operator_response = $operator_response;
    }

    protected function ssa_set_hint($key, $message, $clear_hint_first = false) {
        if ($clear_hint_first) {
            $this->ssa_clear_hint();
        }

        return $this->ssa_hint[$key] = $message;
    }

    protected function ssa_get_hint($key = null) {
        if (!empty($key)) {
            if (isset($this->ssa_hint[$key])) {
                return $this->ssa_hint[$key];
            } else {
                return false;
            }
        }

        return $this->ssa_hint;
    }

    protected function ssa_clear_hint() {
        return $this->ssa_hint = [];
    }

    protected function ssa_check_previous_year_month_data($force_check = false) {
        if ($force_check) {
            return true;
        }

        if ($this->utils->isFirstDateOfCurrentMonth()) {
            return true;
        }

        return false;
    }

    protected function ssa_get_specific_column($table_name, $field = '', $where = []) {
        $this->load->model(['original_seamless_wallet_transactions']);

        return $this->original_seamless_wallet_transactions->getSpecificColumn($table_name, $field, $where);
    }

    protected function ssa_increase_remote_wallet($player_id, $amount, $game_platform_id, $after_balance) {
        $this->load->model(['wallet_model']);

        if (method_exists($this->wallet_model, 'incRemoteWallet')) {
            return $this->wallet_model->incRemoteWallet($player_id, $amount, $game_platform_id, $after_balance);
        } else {
            return null;
        }
    }

    protected function ssa_dev_authorization($dev_auth_username, $dev_auth_password, $separator = ':', $allow_empty_password = false) {
        $this->ssa_dev_authorization = $this->ssa_validate_basic_auth_request($dev_auth_username, $dev_auth_password, $separator, $allow_empty_password);
        $request_headers = $this->ssa_request_headers();

        if ($this->ssa_dev_authorization) {
            $this->ssa_header_x_show_hint = isset($request_headers['X-Show-Hint']) ? $request_headers['X-Show-Hint'] : 0;
            $this->ssa_header_x_decrypt_response = isset($request_headers['X-Decrypt-Response']) ? $request_headers['X-Decrypt-Response'] : 0;
            $this->ssa_header_x_check_signature = isset($request_headers['X-Check-Signature']) ? $request_headers['X-Check-Signature'] : 1;
        }
    }

    protected function ssa_create_table_like($original_table_name, $create_table_name) {
        if (!$this->utils->table_really_exists($create_table_name)) {
            try {
                $this->utils->debug_log(__CLASS__, __METHOD__, 'original table name', $original_table_name, 'create table', $create_table_name);
                $this->load->model(['original_seamless_wallet_transactions']);
                $this->original_seamless_wallet_transactions->runRawUpdateInsertSQL("CREATE TABLE {$create_table_name} LIKE {$original_table_name}");
                $is_created = true;
                $message = "Table created: {$create_table_name}";
            } catch(Exception $e) {
                $this->utils->debug_log(__CLASS__, __METHOD__, 'original table name', $original_table_name, 'create table failed', $create_table_name, $e);
                $is_created = false;
                $message = "Create table failed: {$create_table_name}";
            }
        } else {
            $is_created = true;
            $message = 'Table already exists';
        }

        $result = [
            'is_created' => $is_created,
            'message' => $message,
        ];

        return $result;
    }

    /**
    * Save transaction data
    * @param string $transaction_table required
    * @param string $query_type required (insert | update)
    * @param array $data required transaction data
    * @param array $where required if query type is update
    * @param boolean $use_monthly_table optional
    * @return array $result = ['is_saved' => boolean, 'message' => 'string'];
    */
    protected function ssa_save_transaction_data($transaction_table, $query_type, $data, $where = [], $use_monthly_table = false) {
        $original_transaction_table = $transaction_table;
        $is_saved = false;
        $message = 'Failed to save transaction';

        if ($use_monthly_table) {
            $year_month = $this->utils->getThisYearMonth();
            $transaction_table .= "_{$year_month}";

            // create table if not exist
            if (!$this->utils->table_really_exists($transaction_table)) {
                $this->ssa_create_table_like($original_transaction_table, $transaction_table);
            }
        }

        $update_with_result = true;
        $is_saved = $this->original_seamless_wallet_transactions->saveTransactionData($transaction_table, $query_type, $data, $where, $update_with_result);

        if ($is_saved) {
            $message = 'Transaction saved successfully';
        }

        $result = [
            'is_saved' => $is_saved,
            'message' => $message,
        ];

        return $result;
    }

    protected function ssa_get_current_year_month_table($tableName) {
        if (empty($tableName)) {
            return false;
        }

        $newTableName = $tableName . '_' . $this->utils->getThisYearMonth();
        $this->utils->createTableLike($newTableName, $tableName);

        return $newTableName;
    }

    protected function ssa_get_previous_year_month_table($tableName) {
        if (empty($tableName)) {
            return false;
        }

        $newTableName = $tableName . '_' . $this->utils->getLastYearMonth();
        $this->utils->createTableLike($newTableName, $tableName);

        return $newTableName;
    }

    protected function ssa_get_next_year_month_table($tableName) {
        if (empty($tableName)) {
            return false;
        }

        $newTableName = $tableName . '_' . $this->utils->getNextYearMonth();
        $this->utils->createTableLike($newTableName, $tableName);

        return $newTableName;
    }
}