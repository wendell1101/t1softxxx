<?php

require_once dirname(__FILE__) . '/../playerapi.php';

// $config['facebook_credential'] = [
//     'auth_url'      => 'https://www.facebook.com/v12.0/dialog/oauth',
//     'redirect_uri'  => 'https://%s/facebook_auth',
//     'playerapi_redirect_uri'=> 'http://player.og.com/sso/facebook_auth2',
//     'client_id'     => '1475362782860272',
//     'client_secret' => '85d1d47072a726f4e24ffae910913195',
//     'scope'         => 'public_profile',
//     'response_type' => 'code',
//     'client_token'    => '4f78fc47bebbc0bd827fe3f63c3df98a',
//     'fallback_currency' => 'cny',
// ];
// http://player.og.local/playerapi/sso/facebook?currency=cny
class Facebook_auth2 extends playerapi
{

    private $uuid;
    protected $credential;
    private $facebook_host = 'https://graph.facebook.com/';
    private $api_vers = 'v18.0';
    private $get_token_url = 'https://graph.facebook.com/v18.0/oauth/access_token';
    private $debug_token_url = 'https://graph.facebook.com/debug_token';
    private $get_profile_url = 'https://graph.facebook.com/me';
    private $profile_endpoint = 'me';
    private $access_token = null;
    const RETURN_SUCCESS_CODE = 'success';
    const CONFIG_KEY_CREDENTIAL = 'facebook_credential';

    function __construct()
    {
        parent::__construct();
        $this->load->model('third_party_login');
        $this->load->library(['playerapi_lib', 'player_library']);
        $this->credential = $this->utils->getConfig(self::CONFIG_KEY_CREDENTIAL);
    }

    public function index()
    {
        try {
            $redirect_to = $this->utils->getHttpHost();

            if (!$this->credential) {
                //$this->redriect_error();
                throw new Exception(lang('Credential not found'), self::CODE_LOGIN_FAILED);
            }
            $params = $this->getInputGetAndPost();
            $this->checkError($params);

            $thirdPartyLogin = $this->third_party_login->getThirdPartyLoginByUuid($params['state']);
            $this->checkError($thirdPartyLogin);
            $this->uuid = $thirdPartyLogin['uuid'];

            $data = [
                'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_AUTH
            ];
            $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);

            $oauth_result = null;
            $extra_info = json_decode($thirdPartyLogin['extra_info'], true);
            $redirect_to = empty($extra_info['redirect_to'])?$this->utils->getHttpHost(): $extra_info['redirect_to'];
            $fallback_currency = $this->utils->safeGetArray($this->credential, 'fallback_currency', '');
            $currCurrency = empty($extra_info['currency']) ? $fallback_currency : $extra_info['currency'];

            if (!empty($params['code'])) {

                $redirect_uri = $this->credential['playerapi_redirect_uri'];
                // $currDomain = 'player.og.com';
                $currDomain = $this->utils->getHttpHost();
                $redirect_uri = $this->utils->paddingHostHttp($redirect_uri, $currDomain);
                // $currDomain = $this->utils->getHttpHost();
                // $redirect_uri = sprintf($redirect_uri, $currDomain);
                // if($this->utils->notExistHttp($redirect_uri)){
                //     $redirect_uri = ($this->utils->isHttps() ? 'https://' : 'http://') . $redirect_uri;
                // }
                $get_token_params = [
                    'code' => $params['code'],
                    'client_id' => $this->credential['client_id'],
                    'client_secret' => $this->credential['client_secret'],
                    'redirect_uri' => $redirect_uri,
                ];
                $this->utils->debug_log('========================facebook_auth get_token_params : ', $get_token_params);
                
                $token = $this->processCurl($this->get_token_url, $get_token_params);
                $this->utils->debug_log('========================facebook_auth get_token_url result : ', $token);
                if(empty($token['access_token'])){
                    throw new Exception(lang('Facebook Validation Failed'), self::CODE_LOGIN_FAILED);
                }

                $get_profile_params = [
                    'access_token' => $token['access_token']
                ];

                $this->utils->debug_log('========================facebook_auth get_profile_params : ', $get_profile_params);

                $profile = $this->processCurl(sprintf($this->get_profile_url, $this->profile_endpoint), $get_profile_params);

                $this->utils->debug_log('========================facebook_auth get_profile_url : ', $profile);

                $thirdparty_user_id = $this->utils->safeGetArray($profile, 'id', '');
                $thirdparty_username = $this->utils->safeGetArray($profile, 'name', '');

                if (empty($thirdparty_user_id)) {
                    throw new Exception(lang('Facebook Validation Failed'), self::CODE_LOGIN_FAILED);
                }

                $data = [
                    'third_party_user_id' => $thirdparty_user_id,
                    'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_SUCCESS
                ];
                $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);

                $thirdparty_player = $this->third_party_login->getFacebookPlayersByUserId($thirdparty_user_id);

                if (!empty($thirdparty_player['player_id'])) { #login
                    $data = [
                        'facebook_username' => $thirdparty_username,
                        'id_token' => $token['access_token'],
                        // @todo id_token.id_token data type,"varchar(255)" too short and will ignore over-long string.
                    ];
                    $this->third_party_login->updateFacebookPlayersByUserId($thirdparty_user_id, $data);

                    $player = $this->player_model->getPlayerArrayById($thirdparty_player['player_id']);
                    if (!empty($player)) {
                        if ($this->utils->getConfig('enable_fast_track_integration')) {
                            $this->load->library('fast_track');
                            $this->fast_track->login($thirdparty_player['player_id']);
                        }
                        # DECRYPT PASSWORD
                        $decryptedPwd = $this->salt->decrypt($player['password'], $this->getDeskeyOG());
                        $oauth_result = $this->ssoOauthToken($player['username'], $decryptedPwd, $currCurrency);
                        $this->utils->debug_log('========================facebook_auth_data oauth_result', $oauth_result);

                    } 
                    else {
                        // $this->checkError($result);
                        throw new Exception(lang('Facebook Validation Failed'), self::CODE_LOGIN_FAILED);
                    }
                } else { #register
                    // for load $extra_info (in order to check player had filled basic info (username, password)
                    // $extra_info = json_decode($thirdPartyLogin['extra_info'], true);
                    if (empty($thirdparty_player)) {
                        $isUsernameFilled = !empty($extra_info['username']);
                        $data = [
                            'facebook_user_id' => $thirdparty_user_id,
                            'facebook_username' => $thirdparty_username,
                            'id_token' => $token['access_token']
                        ];
                        $this->third_party_login->insertFacebookPlayers($data);
                    } else { #incase failed for last try
                        $data = [
                            'facebook_username' => $thirdparty_username,
                            'id_token' => $token['access_token'],
                        ];
                        $this->third_party_login->updateFacebookPlayersByUserId($thirdparty_user_id, $data);
                    }

                    $player_data['username'] = $this->generateUsername();
                    if (!empty($extra_info['username'])) {
                        $player_data['username'] = $extra_info['username'];
                    }
                    $player_data['password'] = $this->generatePassword();
                    if (!empty($extra_info['password'])) {
                        $player_data['password'] = $extra_info['password'];
                    }

                    // if (!empty($thirdparty_email)) {
                    //     $player_data['email'] = $thirdparty_email;
                    // }

                    $player_data['thirdPartyLoginType'] = Third_party_login::THIRD_PARTY_LOGIN_TYPE_FACEBOOK;
                    $player_data['facebook_user_id'] = $thirdparty_user_id;
                    // $player_data['firstName'] = $thirdparty_first_name;
                    // $player_data['lastName'] = $thirdparty_last_name;

                    $this->utils->debug_log('========================facebook_auth_data player_data', $player_data);
                    $this->utils->debug_log('========================facebook_auth_data extra_info', $extra_info);

                    $data = array_merge($player_data, $extra_info);
                    $post_data = $this->generateRegisterPostData($data);
                    $post_data['thirdPartyLoginType'] = Third_party_login::THIRD_PARTY_LOGIN_TYPE_FACEBOOK;
                    $post_data['facebook_user_id'] = $thirdparty_user_id;
                    $rlt = $this->postRegisterPlayerAccount($post_data);
                    $this->utils->debug_log('========================facebook_auth_data generateRegisterPostData data', $data);
                    $this->utils->debug_log('========================facebook_auth_data postRegisterPlayerAccount post_data', $post_data);
                    $this->utils->debug_log('========================facebook_auth_data postRegisterPlayerAccount rlt', $rlt);

                    // $this->load->view('player/redirect', $data);
                    $success = !empty($rlt['success']) ? $rlt['success'] : false;
                    if ($success) {
                        //do outh2 login
                        $oauth_result = $this->ssoOauthToken($player_data['username'], $player_data['password'], $currCurrency);
                        $this->utils->debug_log('========================facebook_auth_data oauth_result', $oauth_result);
                    } else {
                        throw new Exception(lang('Facebook Validation Failed'), self::CODE_LOGIN_FAILED);
                    }
                }
            }
            $oauth_result_json = json_decode($oauth_result, true);
            if(empty($oauth_result_json['access_token']) || isset($oauth_result_json['errorMessage'])){
                throw new Exception($oauth_result_json['errorMessage'], $oauth_result_json['code']);
            }
        } catch (\Throwable $th) {
            //throw $th;
            $this->utils->debug_log('=============facebook_login error', $th->getMessage());
            $this->handle_redirect_error($this->credential, $th->getCode(), $th->getMessage(), $redirect_to);
            return;
        }

        return $this->handle_redirect_success($this->credential, $oauth_result_json, $redirect_to);

    }

    private function checkError($params)
    {
        $this->utils->debug_log('========================facebook_auth_data checkError', $params);
        if (empty($params)) {
            $this->utils->error_log('Facebook Validation Failed');
            throw new Exception(lang('Facebook Validation Failed'), self::CODE_LOGIN_FAILED);

        } elseif (isset($params['error'])) {
            if (!empty($this->uuid)) {
                $error_note = json_encode($params);
                $data = [
                    'error_note' => $error_note,
                    'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_FAILED
                ];
                $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);
            }

            $this->utils->error_log('Facebook Validation Failed', $params);
            throw new Exception($error_note, self::CODE_LOGIN_FAILED);
        }
    }

    private function processCurl($url, $params)
    {
        try {
            // $url = $url.'?'.http_build_query($params);
            $ch = curl_init();

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
            ));
            $response = curl_exec($ch);


            curl_close($ch);
            $result = json_decode($response, true);
            $this->checkError($result);

            return $result;
        } catch (Exception $e) {
            $this->utils->error_log('POST failed', $e);
        }
    }
}
