<?php

require_once dirname(__FILE__) . '/../playerapi.php';

class Sbe_auth2 extends playerapi
{
    private $uuid;
    protected $credential;
    const RETURN_SUCCESS_CODE = 'success';
    const CONFIG_KEY_CREDENTIAL = 'sbe_credential';

    function __construct()
    {
        parent::__construct();
        $this->load->model('third_party_login');
        $this->load->library(['playerapi_lib', 'player_library']);
        $this->credential = $this->utils->getConfig(self::CONFIG_KEY_CREDENTIAL);
    }

    public function index()
    {
        //player.og.local/playerapi/sso/sbe
        try {
            if (!$this->credential) {
                throw new Exception(lang('sbe_credential not found'), self::CODE_LOGIN_FAILED);
            }
            $oauth_result = null;
            $params = $this->getInputGetAndPost();

            $redirect_to = $this->credential['redirect_uri'] ?: $this->utils->getHttpHost();
            $currCurrency = $fallback_currency = $this->utils->safeGetArray($this->credential, 'fallback_currency', '');
            $thirdparty_player['player_id'] = $params['player_id'];
            $thirdPartyLogin = $this->third_party_login->getThirdPartyLoginByUuid($params['state']);

            $this->checkError($thirdPartyLogin);
            $this->uuid = $thirdPartyLogin['uuid'];

            $player = $this->player_model->getPlayerArrayById($thirdparty_player['player_id']);
            if (!empty($player)) {
                # DECRYPT PASSWORD
                $decryptedPwd = $this->salt->decrypt($player['password'], $this->getDeskeyOG());
                $oauth_result = $this->ssoOauthToken($player['username'], $decryptedPwd, $currCurrency, false);
                $this->utils->debug_log('========================sbe_auth_data oauth_result', $oauth_result);
            }

            $oauth_result_json = json_decode($oauth_result, true);
            if(empty($oauth_result_json['access_token']) || isset($oauth_result_json['errorMessage'])){
                throw new Exception($oauth_result_json['errorMessage'], $oauth_result_json['code']);
            }
        } catch (\Throwable $th) {
            $this->utils->debug_log('=============sbe_login error', $th->getMessage());
            $this->handle_redirect_error($this->credential, $th->getCode(), $th->getMessage(), $redirect_to);
            return;
        }
        return $this->handle_redirect_success($this->credential, $oauth_result_json, $redirect_to);
    }

    private function checkError($params)
    {
        $this->utils->debug_log('========================sbe_auth_data checkError', $params);
        if (empty($params)) {
            $this->utils->error_log('Sbe Validation Failed');
            throw new Exception(lang('Sbe Validation Failed'), self::CODE_LOGIN_FAILED);

        } elseif (isset($params['error'])) {
            if (!empty($this->uuid)) {
                $error_note = json_encode($params);
                $data = [
                    'error_note' => $error_note,
                    'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_FAILED
                ];
                $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);
            }

            $this->utils->error_log('Sbe Validation Failed', $params);
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
