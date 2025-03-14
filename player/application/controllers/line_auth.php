<?php

require_once dirname(__FILE__) . '/iframe_module.php';

class Line_auth extends Iframe_module {

    private $uuid;
    private $credential;
    private $get_token_url = 'https://api.line.me/oauth2/v2.1/token';
    private $get_profile_url = 'https://api.line.me/oauth2/v2.1/verify';
    const RETURN_SUCCESS_CODE = 'success';

    function __construct()
    {
        parent::__construct();
        $this->load->model('third_party_login');
        $this->load->library(['player_library']);
        $this->credential = $this->utils->getConfig('line_credential');
    }

    public function index()
    {
        $enable_OGP19808 = $this->utils->getConfig('enable_OGP19808');
        if(!$this->credential){
            $this->goPlayerLogin();
        }

        $params = $this->getInputGetAndPost();
        $this->checkError($params);

        $states = explode('|', $params['state']);
        $state = $states[0];
        $mode = $states[1];
        $playerId = "";
        $sign = "";
        $type = "";
        $goto_url = "";

        switch($mode){
            case '0': // api bind line
                $playerId = $states[2];
                $sign = $states[3];
                $type = $states[4];
                $goto_url = $states[5];
                break;
            case '1': // web bind line
                $playerId = $states[2];
                $sign = $states[3];
                $type = $states[4];
                break;
            case '2': // api login line
                $goto_url = $states[2];
                break;
            default:
                break;
        }

        $thirdPartyLogin = $this->third_party_login->getThirdPartyLoginByUuid($state);
        $this->checkError($thirdPartyLogin);
        $this->uuid = $thirdPartyLogin['uuid'];

        $data = [
            'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_AUTH
        ];
        $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);


        if(!empty($params['code'])) {

            $redirect_uri = $this->credential['redirect_uri'];
            $currDomain = $this->utils->getHttpHost();
            $redirect_uri = sprintf($redirect_uri, $currDomain);

            $get_token_params = [
                'grant_type'    => 'authorization_code',
                'code'          => $params['code'],
                'redirect_uri'  => $redirect_uri,
                'client_id'     => $this->credential['client_id'],
                'client_secret' => $this->credential['client_secret'],
            ];

            $token = $this->processCurl($this->get_token_url, $get_token_params);
            $get_profile_params = [
                'client_id' => $this->credential['client_id'],
                'id_token'  => $token['id_token']
            ];
            $profile = $this->processCurl($this->get_profile_url, $get_profile_params);

            $line_user_id = $profile['sub'];
            $line_username = $profile['name'];
            $line_email = $profile['email'];

            $data = [
                'third_party_user_id' => $line_user_id,
                'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_SUCCESS
            ];
            $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);

            $line_player = $this->third_party_login->getLinePlayersByUserId($line_user_id);

            if(!empty($line_player['player_id']) && $type != "bind"){ #login                
                $this->line_login($line_username, $token, $line_user_id, $line_player, $enable_OGP19808, $goto_url);
            } else { #register
                // for load $extra_info (in order to check player had filled basic info (username, password)
                $extra_info = json_decode($thirdPartyLogin['extra_info'], true);

                if( ! empty($thirdPartyLogin['pre_register_form']) ){
                    $pre_register_form = json_decode($thirdPartyLogin['pre_register_form'], true);
                    $extra_info = array_merge($extra_info, $pre_register_form);
                }

                if(!empty($playerId) && !empty($sign)){
                    $this->line_bind($line_user_id, $line_username, $token, $line_player, $extra_info, $enable_OGP19808, $playerId, $sign, $line_email);
                }else{
                    $this->line_register($line_user_id, $line_username, $token, $line_player, $extra_info, $enable_OGP19808, $line_email, $goto_url);
                }                
            }
        }
    }

    private function checkError($params)
    {
        if(empty($params)){
            $this->utils->error_log('Line Validation Failed');
            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, lang('Line Validation Failed'));
            redirect('/iframe/auth/login');

        } elseif(isset($params['error'])){
            if(!empty($this->uuid)){
                $error_note = json_encode($params);
                $data = [
                    'error_note' => $error_note,
                    'status' => Third_party_login::THIRD_PARTY_LOGIN_STATUS_FAILED
                ];
                $this->third_party_login->updateThirdPartyLoginByUuid($this->uuid, $data);
            }

            $this->utils->error_log('Line Validation Failed', $params);
            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, lang('Line Validation Failed'));
            redirect('/iframe/auth/login');
        }
    }

    private function processCurl($url, $params)
    {
        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['application/x-www-form-urlencoded']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params) );

            $response = curl_exec($ch);
            $errCode     = curl_errno($ch);
            $error       = curl_error($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $statusCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $last_url    = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $header      = substr($response, 0, $header_size);
            $content     = substr($response, $header_size);
            curl_close($ch);
            $result = json_decode($content, true);
            $this->checkError($result);

            return $result;
        } catch (Exception $e) {
            $this->utils->error_log('POST failed', $e);
        }
    }

    public function createHtmlForm($url, $params) {
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

    private function line_login($line_username, $token, $line_user_id, $line_player, $enable_OGP19808, $goto_url){
        $data = [
            'line_username' => $line_username,
            'id_token' => $token['id_token'], // @todo id_token.id_token data type,"varchar(255)" too short and will ignore over-long string.
         ];
         $this->third_party_login->updateLinePlayersByUserId($line_user_id, $data);

         $player = $this->player_model->getPlayerArrayById($line_player['player_id']);
         $result = $this->player_library->login_by_player_info($player);
         if($result['success']){
             $url = $this->utils->getPlayerHomeUrl('home');

             if( ! empty($enable_OGP19808) ){
                 // OGP-19808 check  real name & SMS OTP
                 $playerId = $player['playerId'];
                 $result4fromLine = $this->player_model->check_playerDetail_from_line($playerId);
                 if($result4fromLine['success'] === false ){
                     $url = $this->utils->getPlayerHomeUrl('home');
                 }else{
                     $url = site_url($this->utils->getPlayerProfileSetupUrl());
                 }
             }

             if($this->utils->getConfig('enable_fast_track_integration')) {
                 $this->load->library('fast_track');
                 $this->fast_track->login($line_player['player_id']);
             }

            if($goto_url != ""){
                $player_token = $this->common_token->getPlayerToken($player['playerId']);

                $ret = [
                    'code'      => 0 ,
                    'mesg'      => 'Player logged in',
                    'result'    => [
                        'playerName'    => $player['username'] ,
                        'playerId'      => $player['playerId'],
                        'token'         => $player_token
                    ]
                ];
                // redirect($this->utils->getSystemUrl($goto_url)."?token=".$player_token);
                header('Content-Type: application/json');
                echo json_encode($ret);
            }
             redirect($url);
         } else {
             $this->checkError($result);
         }
    }

    private function line_register($line_user_id, $line_username, $token, $line_player, $extra_info, $enable_OGP19808, $line_email, $goto_url){
        
        if(empty($line_player)){
            $isUsernameFilled = !empty($extra_info['username']);
            if($this->utils->getConfig('force_line_player_to_register') && !$isUsernameFilled){
                $go_register = $this->utils->getSystemUrl('player', '/player_center/iframe_register');
                return redirect($go_register);
            }

            $data = [
               'line_user_id'   => $line_user_id,
               'line_username' => $line_username,
               'id_token' => $token['id_token']
            ];
            $this->third_party_login->insertLinePlayers($data);
        } else { #incase failed for last try
            $data = [
               'line_username' => $line_username,
               'id_token' => $token['id_token'],
            ];
            $this->third_party_login->updateLinePlayersByUserId($line_user_id, $data);
        }

        $password_min_max_enabled = $this->utils->isPasswordMinMaxEnabled();
        $max_username_length = $this->utils->getConfig('default_max_size_username');
        $max_password_length = !empty($password_min_max_enabled['max']) ? $password_min_max_enabled['max'] : $this->utils->getConfig('default_max_size_password');

        $player_data['username'] = strtolower(random_string('alnum', $max_username_length - 1)).random_string('numeric', 1);
        if(! empty($extra_info['username']) ){
            $player_data['username'] = $extra_info['username'];
        }
        $player_data['password'] = strtolower(random_string('alnum', $max_password_length - 1)).random_string('numeric', 1);
        if( ! empty($extra_info['password']) ){
            $player_data['password'] = $extra_info['password'];
        }

        $player_data['thirdPartyLoginType'] = Third_party_login::THIRD_PARTY_LOGIN_TYPE_LINE;
        $player_data['line_user_id'] = $line_user_id;
        $player_data['line_email'] = $line_email;

        $player_data['goto_url'] = ($this->operatorglobalsettings->getSettingJson('redirect_after_registration') == 2) ? site_url($this->utils->getSystemUrl('www')) : site_url($this->utils->getPlayerHomeUrl('home'));

        // because extra_info['password'] from $thirdPartyLogin['pre_register_form'].
        if( ! empty($extra_info['password']) ){ // for detect applied OGP-19860
            // should be into Add Bank
            if( ! empty( $this->utils->getConfig('gotoAddBankAfterRegister') ) ){
                $url = $this->utils->getPlayerBankAccountUrl();
                $url .= '#triggerAddBank';
                $player_data['goto_url'] = $url;
            }
        }else{
            // will apply into OGP-19808
            if( ! empty($enable_OGP19808) ){
                $player_data['goto_url'] = site_url($this->utils->getPlayerProfileSetupUrl());
            }
        }

        //    moved to "load $extra_info".
        //    $extra_info = json_decode($thirdPartyLogin['extra_info'], true);
        //    if( ! empty($thirdPartyLogin['pre_register_form']) ){
        //        $pre_register_form = json_decode($thirdPartyLogin['pre_register_form'], true);
        //        $extra_info = array_merge($extra_info, $pre_register_form);
        //    }

        if($goto_url != ""){
            $player_data['goto_url'] = $goto_url;
            $player_data['from_api'] = true;
        }

        $this->utils->debug_log('========================line_auth_data player_data', $player_data);
        $this->utils->debug_log('========================line_auth_data extra_info', $extra_info);

        $data = array_merge($player_data, $extra_info);
        $this->_process_register($data, $extra_info['tracking_code'], $extra_info['tracking_source_code'], $extra_info['agent_tracking_code'] , $extra_info['agent_tracking_source_code'], $extra_info['invitationCode'], $extra_info['btag']);

        list($html, $formId) = $this->createHtmlForm(site_url('iframe_module/postRegisterPlayer'), $data);
        $data = array('form_html' => $html, 'form_id' => $formId);
        $this->utils->debug_log('========================line_auth_data createHtmlForm data', $data);

        $this->load->view('player/redirect', $data);
    }

    private function line_bind($line_user_id, $line_username, $token, $line_player, $extra_info, $enable_OGP19808, $playerId, $sign, $line_email){
        
        $playerinfo = $this->CI->player_model->getPlayerById($playerId);
        $verify = [
            'playerId' => $playerId,
            'username' => $playerinfo->username
        ];
        $verifySign = $this->validateSign($verify, $sign);

        if(!$verifySign){
            $this->utils->error_log('Line Validation Failed');
            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, lang('Line Validation Failed'));
            $url = site_url($this->utils->getPlayerProfileSetupUrl());
            redirect($url);
        }

        $line_player_by_playerid =  $this->third_party_login->getLineInfoByPlayerId($playerId);
        // 沒有line user id, player id
        if(empty($line_player) && empty($line_player_by_playerid)){
            $data = [
               'line_user_id'  => $line_user_id,
               'line_username' => $line_username,
               'id_token'      => $token['id_token'],
               'player_id'     => $playerId,
            ];
            
            $this->third_party_login->insertLinePlayers($data);
            $checkEmail = $this->player_model->getEmailsByPlayerIds($playerId);
            if(empty($checkEmail[0]['email'])){
                $this->player_model->updatePlayerEmail($playerId, $line_email);
            }
            $this->player_model->updatePlayerImAccount($playerId, $line_email);
        }else{ // 有line user id 或 player id 都返回已綁定
            $this->utils->error_log('Line Has Been Successfully Bound');
            $this->utils->flash_message(FLASH_MESSAGE_TYPE_DANGER, lang('Line Has Been Successfully Bound'));
            $url = site_url($this->utils->getPlayerProfileSetupUrl());
            redirect($url);
        }

        $this->utils->debug_log('Success! Line login linked.');
        $this->utils->flash_message(FLASH_MESSAGE_TYPE_SUCCESS, lang('Success! Line login linked.'));
        $url = site_url($this->utils->getPlayerProfileSetupUrl());
        redirect($url);
    }

    private function sign($params) {
        $signStr = $this->createSignStr($params);
        $sign = strtolower(md5($signStr));
        return $sign;
    }

	private function createSignStr($params) {
    	ksort($params);
       	$signStr='';
		foreach ($params as $key => $value) {
			if(is_null($value) || empty($value)){
				continue;
			}
			$signStr .= $key."=".$value."&";
		}
		$api_keys = $this->config->item('api_key_player_center');
		foreach($api_keys as $key => $value ){
			$api_key = $key;
			break;
		}
		
		$signStr .= 'key='. $this->utils->encodePasswordMD5($api_key);
		return $signStr;
	}
    
    public function validateSign($params, $signature) {
        $sign = $this->sign($params);
        if ( $signature == $sign ) {
            return true;
        } else {
            return false;
        }    
    }
}
