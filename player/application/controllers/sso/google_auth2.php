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
class Google_auth2 extends playerapi
{

    private $uuid;
    protected $credential;
    private $get_token_url = 'https://oauth2.googleapis.com/token';
    private $get_profile_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    const RETURN_SUCCESS_CODE = 'success';
    const CONFIG_KEY_CREDENTIAL = 'google_credential';

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
                throw new Exception(lang('google_credential not found'), self::CODE_LOGIN_FAILED);
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
                    'grant_type' => 'authorization_code',
                    'access_type' => 'offline',
                ];
                $this->utils->debug_log('========================Google_auth get_token_params : ', $get_token_params);
                
                $token = $this->processCurl($this->get_token_url, $get_token_params);
                $this->utils->debug_log('========================Google_auth get_token_url result : ', $token);
                if(empty($token['access_token'])){
                    throw new Exception(lang('Google Validation Failed'), self::CODE_LOGIN_FAILED);
                }

                $get_profile_params = [
                    'access_token' => $token['access_token']
                ];

                $this->utils->debug_log('========================Google_auth get_profile_params : ', $get_profile_params);

                $profile = $this->processCurl($this->get_profile_url, $get_profile_params);

                $this->utils->debug_log('========================Google_auth get_profile_url : ', $profile);

                $thirdparty_user_id = $this->utils->safeGetArray($profile, 'id', '');
                $thirdparty_username = $this->utils->safeGetArray($profile, 'name', '');
                $thirdparty_email = $this->utils->safeGetArray($profile, 'email', '');
                $thirdparty_first_name = $this->utils->safeGetArray($profile, 'given_name', '');
                $thirdparty_last_name = $this->utils->safeGetArray($profile, 'family_name', '');

                if (empty($thirdparty_user_id)) {
                    throw new Exception(lang('Google Validation Failed'), self::CODE_LOGIN_FAILED);
                }

                $data = [
                    'third_party_user_id' => $thirdparty_user_id,
                    'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_SUCCESS
                ];
                $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);

                $thirdparty_player = $this->third_party_login->getGooglePlayersByUserId($thirdparty_user_id);

                if (!empty($thirdparty_player['player_id'])) { #login
                    $data = [
                        'google_username' => $thirdparty_username,
                        'id_token' => $token['access_token'],
                        // @todo id_token.id_token data type,"varchar(255)" too short and will ignore over-long string.
                    ];
                    $this->third_party_login->updateGooglePlayersByUserId($thirdparty_user_id, $data);

                    $player = $this->player_model->getPlayerArrayById($thirdparty_player['player_id']);
                    if (!empty($player)) {
                        if ($this->utils->getConfig('enable_fast_track_integration')) {
                            $this->load->library('fast_track');
                            $this->fast_track->login($thirdparty_player['player_id']);
                        }
                        # DECRYPT PASSWORD
                        $decryptedPwd = $this->salt->decrypt($player['password'], $this->getDeskeyOG());
                        $oauth_result = $this->ssoOauthToken($player['username'], $decryptedPwd, $currCurrency);
                        $this->utils->debug_log('========================google_auth_data oauth_result', $oauth_result);

                    } 
                    else {
                        // $this->checkError($result);
                        throw new Exception(lang('Google Validation Failed'), self::CODE_LOGIN_FAILED);
                    }
                } else { #register
                    // for load $extra_info (in order to check player had filled basic info (username, password)
                    // $extra_info = json_decode($thirdPartyLogin['extra_info'], true);
                    if (empty($thirdparty_player)) {
                        $isUsernameFilled = !empty($extra_info['username']);
                        $data = [
                            'google_user_id' => $thirdparty_user_id,
                            'google_username' => $thirdparty_username,
                            'id_token' => $token['access_token']
                        ];
                        $this->third_party_login->insertGooglePlayers($data);
                    } else { #incase failed for last try
                        $data = [
                            'google_username' => $thirdparty_username,
                            'id_token' => $token['access_token'],
                        ];
                        $this->third_party_login->updateGooglePlayersByUserId($thirdparty_user_id, $data);
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

                    $player_data['thirdPartyLoginType'] = Third_party_login::THIRD_PARTY_LOGIN_TYPE_GOOGLE;
                    $player_data['google_user_id'] = $thirdparty_user_id;
                    // $player_data['firstName'] = $thirdparty_first_name;
                    // $player_data['lastName'] = $thirdparty_last_name;

                    $this->utils->debug_log('========================google_auth_data player_data', $player_data);
                    $this->utils->debug_log('========================google_auth_data extra_info', $extra_info);

                    $data = array_merge($player_data, $extra_info);
                    $post_data = $this->generateRegisterPostData($data);
                    $post_data['thirdPartyLoginType'] = Third_party_login::THIRD_PARTY_LOGIN_TYPE_GOOGLE;
                    $post_data['google_user_id'] = $thirdparty_user_id;
                    $rlt = $this->postRegisterPlayerAccount($post_data);
                    $this->utils->debug_log('========================google_auth_data generateRegisterPostData data', $data);
                    $this->utils->debug_log('========================google_auth_data postRegisterPlayerAccount post_data', $post_data);
                    $this->utils->debug_log('========================google_auth_data postRegisterPlayerAccount rlt', $rlt);

                    // $this->load->view('player/redirect', $data);
                    $success = !empty($rlt['success']) ? $rlt['success'] : false;
                    if ($success) {
                        //do outh2 login
                        $oauth_result = $this->ssoOauthToken($player_data['username'], $player_data['password'], $currCurrency);
                        $this->utils->debug_log('========================google_auth_data oauth_result', $oauth_result);
                    } else {
                        throw new Exception(lang('Google Validation Failed'), self::CODE_LOGIN_FAILED);
                    }
                }
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
    
}
