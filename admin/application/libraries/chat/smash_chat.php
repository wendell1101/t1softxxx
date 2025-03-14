<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 *
 * SMASH CHAT INTEGRATION
 * OGP-25793
 *
 * @author      Sony
 */

class smash_chat {

    private $chat_config = null;

    const URL_MAP = [
        'generate_token' => '/chat/generate_token',
        'query_launcher' => '/chat/query_launcher'
    ];

    public function __construct() {
        $this->ci = &get_instance();
        $this->chat_config = $this->ci->utils->getConfig('p2p_chat_api')['smash_chat'];
    }

    public function getChatUrl($player_id) {
        $url = null;
        $this->ci->load->model('common_token');
        $auth_token = $this->generateToken();
        $player_token = $this->ci->common_token->getPlayerToken($player_id);

        $params = [
            'auth_token' => $auth_token,
            'merchant_code' => $this->chat_config['merchant_code'],
            'token' => $player_token
        ];

        $params['sign'] = $this->generateSign($params);

        $response = $this->callHttp('query_launcher', $params);
		$this->ci->utils->debug_log(__METHOD__ . ' getChatUrl response: ', $response);

        $response_body = $response['content'];

        if($response_body['code'] == 0 && !empty($response_body['detail']['chat_url'])) {
            $url = $response_body['detail']['chat_url'];
        }
        return $url;
    }

    private function generateToken() {

        $cache_key = 'smash_chat_auth_token';
        // $this->ci->saveTextToCache
        $auth_token = $this->ci->utils->getTextFromCache($cache_key);
        if(!empty($auth_token)) {
            return $auth_token;
        }

        $params = [
            'merchant_code' => $this->chat_config['merchant_code'],
            'secure_key' => $this->chat_config['secure_key'],
        ];
        $params['sign'] = $this->generateSign($params);

        $response = $this->callHttp('generate_token', $params);
        $response_body = $response['content'];
        if($response_body['code'] == 0 && !empty($response_body['detail']['auto_token'])) {
            $auth_token = $response_body['detail']['auto_token'];
            $ttl = $response_body['detail']['timeout'];
            $this->ci->utils->saveTextToCache($cache_key, $auth_token, $ttl);
        }
        return $auth_token;
    }

    private function generateSign($params) {
        unset($params['sign']);

        ksort($params);
        $sign = sha1(implode('', $params) . $this->chat_config['sign_key']);
        return $sign;
    }

    private function callHttp($api_method, $params) {
        $curlOptions = null;
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        $url = $this->chat_config['host'] . self::URL_MAP[$api_method];
        $method = 'POST';
        $params = http_build_query($params);
        $response = $this->ci->utils->callHttp($url, $method, $params, $curlOptions, $headers);

        $response = [
            'header' => $response[0],
            'content' => json_decode($response[1], true),
            'statusCode' => $response[2],
            'statusText' => $response[3],
            'errCode' => $response[4],
            'error' => $response[5],
        ];

        return $response;
    }
}