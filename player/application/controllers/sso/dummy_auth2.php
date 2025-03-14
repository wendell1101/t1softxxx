<?php

require_once dirname(__FILE__) . '/../playerapi.php';

// $config['google_credential'] = [
//     'auth_url'      => 'https://accounts.google.com/o/oauth2/v2/auth',
//     'test_redirect_uri'  => 'http://localhost/google_auth:80',
//     'redirect_uri'  => 'http://player.og.com/google_auth',
//     'playerapi_redirect_uri'=> 'http://player.og.com/google_auth2',
//     'client_id'     => '6',
//     'client_secret' => '',
//     'scope'         => 'openid profile email',
//     'response_type' => 'code',
//     'client_token'  => '',
//     'prompt'        => 'select_account',
//     'success_endpoint' => '/auth/token',
//     'message_endpoint' => '/auth/message',
// ];
// http://player.og.local/playerapi/sso/google?currency=cny
class Dummy_auth2 extends playerapi
{

    private $uuid;
    protected $credential;
    // private $get_token_url = 'https://oauth2.googleapis.com/token';
    // private $get_profile_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    const RETURN_SUCCESS_CODE = 'success';
    const CONFIG_KEY_CREDENTIAL = 'dummy_credential';

    function __construct()
    {
        parent::__construct();
        $this->load->model('third_party_login');
        $this->load->library(['playerapi_lib', 'player_library']);
        $this->credential = $this->utils->getConfig(self::CONFIG_KEY_CREDENTIAL);
    }

    public function index()
    {
        // player.og.local/playerapi/sso/dummy
        try {
            $redirect_to = $this->utils->getHttpHost();
            if (!$this->credential) {
                //$this->redriect_error();
                throw new Exception(lang('dummy_credential not found'), self::CODE_LOGIN_FAILED);
            }
            $oauth_result = null;
            // $extra_info = json_decode($thirdPartyLogin['extra_info'], true);
            $redirect_to = $this->utils->getHttpHost();
            $currCurrency = $fallback_currency = $this->utils->safeGetArray($this->credential, 'fallback_currency', '');
            $thirdparty_player['player_id'] = $this->credential['test_id'];

            $player = $this->player_model->getPlayerArrayById($thirdparty_player['player_id']);
            if (!empty($player)) {
                # DECRYPT PASSWORD
                $decryptedPwd = $this->salt->decrypt($player['password'], $this->getDeskeyOG());
                $oauth_result = $this->ssoOauthToken($player['username'], $decryptedPwd, $currCurrency);
                $this->utils->debug_log('========================google_auth_data oauth_result', $oauth_result);

            }

            $oauth_result_json = json_decode($oauth_result, true);
            if(empty($oauth_result_json['access_token']) || isset($oauth_result_json['errorMessage'])){
                throw new Exception($oauth_result_json['errorMessage'], $oauth_result_json['code']);
            }
        } catch (\Throwable $th) {
            //throw $th;
            $this->utils->debug_log('=============google_login error', $th->getMessage());
            $this->handle_redirect_error($this->credential, $th->getCode(), $th->getMessage(), $redirect_to);
            return;
        }
        // var_dump($oauth_result_json);
        return $this->handle_redirect_success($this->credential, $oauth_result_json, $redirect_to);

    }

    private function checkError($params)
    {
        $this->utils->debug_log('========================google_auth_data checkError', $params);
        if (empty($params)) {
            $this->utils->error_log('Google Validation Failed');
            throw new Exception(lang('Google Validation Failed'), self::CODE_LOGIN_FAILED);

        } elseif (isset($params['error'])) {
            if (!empty($this->uuid)) {
                $error_note = json_encode($params);
                $data = [
                    'error_note' => $error_note,
                    'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_FAILED
                ];
                $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);
            }

            $this->utils->error_log('Google Validation Failed', $params);
            throw new Exception($error_note, self::CODE_LOGIN_FAILED);
        }
    }

    private function processCurl($url, $params)
    {
        try {
            $ch = curl_init();

            if (isset($params['access_token'])) {
                curl_setopt_array($ch, array(
                    CURLOPT_URL => $url . '?' . http_build_query($params),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json']
                )
                );
                $content = curl_exec($ch);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['application/x-www-form-urlencoded']);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
                $response = curl_exec($ch);
                $errCode = curl_errno($ch);
                $error = curl_error($ch);
                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $last_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
                $header = substr($response, 0, $header_size);
                $content = substr($response, $header_size);
            }
            curl_close($ch);
            $result = json_decode($content, true);
            $this->checkError($result);

            return $result;
        } catch (Exception $e) {
            $this->utils->error_log('POST failed', $e);
        }
    }

    public function createHtmlForm($url, $params)
    {
        $formId = 'f_' . random_string('unique');
        $html = '<form name="' . $formId . '" id="' . $formId . '" method="POST" action="' . $url . '">';
        if (is_array($params)) {
            foreach ($params as $name => $val) {
                $html = $html . "<input type=\"hidden\" name=\"" . $name . "\" value=\"" . htmlentities($val) . "\">\n";
            }
        }
        $html = $html . "<button type=\"hidden\" style=\"display:none\" id=\"submit_form_btn_" . $formId . "\">\n";
        $html = $html . '</form>';
        return array($html, $formId);
    }
}
