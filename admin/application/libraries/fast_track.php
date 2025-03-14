<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

// define('STATUS_ACTIVATED', '1');
// define('STATUS_NOT_ACTIVATED', '0');

/**
 *
 * FAST TRACK Integration
 *
 * @author		Sony
 */

class fast_track {
    private $error = array();

    const STATUS_ACTIVATED = '1';
    const STATUS_NOT_ACTIVATED = '0';
    // const FASTTRACK_LOGIN = 'login'
    const URL_MAP = [
        'login' => 'v2/integration/login',
        'register' => 'v2/integration/user',
        'updateUser' => 'v2/integration/user',
        'requestWithdraw' => 'v1/integration/payment',
        'declineWithdraw' => 'v1/integration/payment',
        'approveWithdraw' => 'v1/integration/payment',
        'blockUser' => 'v2/integration/user/blocks',
        'unBlockUser' => 'v2/integration/user/blocks',
        'updateConsent' =>  'v2/integration/user/consents',
        'approveDeposit' =>  'v1/integration/payment',
        'requestDeposit' => 'v1/integration/payment',
        'declineDeposit' => 'v1/integration/payment',
        'sendGameLogs' => 'v1/integration/casino',
        'sendSportsGameLogs' => 'v1/integration/sports',
    ];

    function __construct() {
        $this->ci = &get_instance();

        // $this->initiateLang();

        /*date_default_timezone_set('Asia/Manila');*/
    }

    private function callHttp($api_method, $params) {
        $curlOptions = null;
        $headers = [
            'X-Api-Key' => $this->ci->utils->getConfig('fast_track_server_api_key'),
            'Content-Type' => 'application/json'
        ];
        $domain = $this->ci->utils->getConfig('fast_track_api_base_url');
        $url = $domain . self::URL_MAP[$api_method];
        $method = $this->getHTTPMethod($api_method);
        $params = json_encode($params);
        $this->ci->utils->debug_log('FAST TRACK API URL', $url);
        $this->ci->utils->debug_log('FAST TRACK API PARAMS', $method, $params);
        $response = $this->ci->utils->callHttp($url, $method, $params, $curlOptions, $headers);

        $this->ci->utils->debug_log('FAST TRACK RESPONSE', $response);
    }

    private function getHTTPMethod($api_method)
    {
        $method = 'GET';
        switch($api_method) {
            case 'login':
            case 'register':
            case 'requestWithdraw':
            case 'declineWithdraw':
            case 'approveWithdraw':
            case 'approveDeposit':
            case 'requestDeposit':
            case 'declineDeposit':
            case 'sendGameLogs':
            case 'sendSportsGameLogs':
                $method = 'POST';
                break;
            case 'updateUser':
            case 'blockUser':
            case 'unBlockUser':
            case 'updateConsent':
                $method = 'PUT';
                break;
            default:
                $method = 'GET';
                break;
        }

        return $method;
    }

    public function login($player_id)
    {

        $params = [
            'user_id' => (string) $player_id,
            'ip_address' => $this->ci->utils->getIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'timestamp' => str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            'origin' => $_SERVER['HTTP_HOST'],
        ];

        $this->callHttp('login', $params);

    }

    public function register($player_id)
    {

        $params = [
            'user_id' => (string) $player_id,
            "url_referer" => $_SERVER['HTTP_REFERER'],
            'note' => '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'ip_address' => $this->ci->utils->getIP(),
            'timestamp' => str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            'origin' => $_SERVER['HTTP_HOST'],
        ];

        $this->callHttp('register', $params);

    }

    public function updateUser($player_id)
    {

        $params = [
            'user_id' => (string) $player_id,
            'timestamp' => str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            'origin' => $_SERVER['HTTP_HOST'],
        ];

        $this->callHttp('updateUser', $params);
    }

    public function requestWithdraw($info)
    {

        $params = [
            "amount" => (float) $info['amount'],
            "currency" => $this->ci->utils->getActiveCurrencyKey(),
            "exchange_rate" => 1,
            "fee_amount" => (float) $info['withdrawal_fee_amount'],
            "origin" => $_SERVER['HTTP_HOST'],
            "payment_id" => $info['walletAccountId'],
            "status" => "Requested",
            "timestamp" => str_replace('+00:00', 'Z', gmdate('c', strtotime($info['dwDateTime']))),
            "type" => "Debit",
            "user_id" => $info['playerId'],
            "vendor_id" => $info['player_bank_details_id'],
            "vendor_name" => $this->ci->utils->extractLangJson($info['bankName'])['en'],
        ];

        $this->callHttp('requestWithdraw', $params);
    }

    public function declineWithdraw($info)
    {

        $params = [
            "amount" => (float) $info['amount'],
            "currency" => $this->ci->utils->getActiveCurrencyKey(),
            "exchange_rate" => 1,
            "fee_amount" => (float) $info['withdrawal_fee_amount'],
            "origin" => $_SERVER['HTTP_HOST'],
            "payment_id" => $info['walletAccountId'],
            "status" => "Rejected",
            "timestamp" => str_replace('+00:00', 'Z', gmdate('c', strtotime($info['dwDateTime']))),
            "type" => "Debit",
            "user_id" => $info['playerId'],
            "vendor_id" => $info['player_bank_details_id'],
            "vendor_name" => $this->ci->utils->extractLangJson($info['bankName'])['en'],
        ];

        $this->callHttp('declineWithdraw', $params);
    }

    public function approveWithdraw($info)
    {

        $params = [
            "amount" => (float) $info['amount'],
            "currency" => $this->ci->utils->getActiveCurrencyKey(),
            "exchange_rate" => 1,
            "fee_amount" => (float) $info['withdrawal_fee_amount'],
            "origin" => $_SERVER['HTTP_HOST'],
            "payment_id" => $info['walletAccountId'],
            "status" => "Approved",
            "timestamp" => str_replace('+00:00', 'Z', gmdate('c', strtotime($info['dwDateTime']))),
            "type" => "Debit",
            "user_id" => $info['playerId'],
            "vendor_id" => $info['player_bank_details_id'],
            "vendor_name" => $this->ci->utils->extractLangJson($info['bankName'])['en'],
        ];

        $this->callHttp('approveWithdraw', $params);
    }

    public function approveDeposit($info)
    {
        // print_r($info);
        // exit;
        $params = [
            "amount" => (float) $info['amount'],
            "currency" => $this->ci->utils->getActiveCurrencyKey(),
            "exchange_rate" => 1,
            "fee_amount" => (float) $info['transaction_fee'],
            "origin" => $_SERVER['HTTP_HOST'],
            "payment_id" => (int) $info['id'],
            "status" => "Approved",
            "timestamp" => str_replace('+00:00', 'Z', gmdate('c', strtotime($info['player_submit_datetime']))),
            "type" => "Credit",
            "user_id" => $info['player_id'],
            "vendor_id" => $info['payment_account_id'],
            "vendor_name" => $this->ci->utils->extractLangJson($info['payment_type_name'])['en'],
        ];

        $this->callHttp('approveDeposit', $params);
    }


    public function requestDeposit($info)
    {

        $params = [
            "amount" => (float) $info['amount'],
            "currency" => $this->ci->utils->getActiveCurrencyKey(),
            "exchange_rate" => 1,
            "fee_amount" => (float) $info['transaction_fee'],
            "origin" => $_SERVER['HTTP_HOST'],
            "payment_id" => (int) $info['id'],
            "status" => "Requested",
            "timestamp" => str_replace('+00:00', 'Z', gmdate('c', strtotime($info['player_submit_datetime']))),
            "type" => "Credit",
            "user_id" => $info['player_id'],
            "vendor_id" => $info['payment_account_id'],
            "vendor_name" => $this->ci->utils->extractLangJson($info['payment_type_name'])['en'],
        ];

        $this->callHttp('requestDeposit', $params);
    }

    public function declineDeposit($info)
    {

        $params = [
            "amount" => (float) $info['amount'],
            "currency" => $this->ci->utils->getActiveCurrencyKey(),
            "exchange_rate" => 1,
            "fee_amount" => (float) $info['transaction_fee'],
            "origin" => $_SERVER['HTTP_HOST'],
            "payment_id" => (int) $info['id'],
            "status" => "Rejected",
            "timestamp" => str_replace('+00:00', 'Z', gmdate('c', strtotime($info['player_submit_datetime']))),
            "type" => "Credit",
            "user_id" => $info['player_id'],
            "vendor_id" => $info['payment_account_id'],
            "vendor_name" => $this->ci->utils->extractLangJson($info['payment_type_name'])['en'],
        ];

        $this->callHttp('declineDeposit', $params);
    }

    public function blockUser($player_id)
    {

        $params = [
            'user_id' => (string) $player_id,
            'timestamp' => str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            'origin' => $_SERVER['HTTP_HOST'],
        ];

        $this->callHttp('blockUser', $params);
    }

    public function unBlockUser($player_id)
    {

        $params = [
            'user_id' => (string) $player_id,
            'timestamp' => str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            'origin' => $_SERVER['HTTP_HOST'],
        ];

        $this->callHttp('unBlockUser', $params);
    }

    public function updateConsent($player_id)
    {

        $params = [
            'user_id' => (string) $player_id,
            'timestamp' => str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            'origin' => $_SERVER['HTTP_HOST'],
        ];

        $this->callHttp('updateConsent', $params);
    }

    public function sendGameLogs($params)
    {
        $this->callHttp('sendGameLogs', $params);
    }

    public function sendSportsGameLogs($params)
    {
        $this->callHttp('sendSportsGameLogs', $params);
    }

    public function addToQueue($function, $payload) {
        $data = [
            'function' => $function,
            'payload' => $payload
        ];
        $this->ci->load->library(['lib_queue']);
        $callerType=Queue_result::CALLER_TYPE_SYSTEM;
        $caller=Queue_result::SYSTEM_UNKNOWN;
        $state=null;
        $token = $this->ci->lib_queue->addRemoteSendDataToFastTrack($data, $callerType, $caller, $state);
    }
}