<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Class Player_library
 *
 * @author Elvis Chen
 *
 * @property PlayerCenterBaseController $CI
 */
class Player_library{
    /* @var Player */
    public $player;

    /* @var Player_model */
    public $player_model;

    /* @var Http_request */
    public $http_request;

    /* @var Player_login_token */
    public $player_login_token;

    /* @var Common_token */
    public $common_token;

    /* @var Utils */
    public $utils;

    public function __construct(){
        $this->CI =& get_instance();
        $this->CI->load->model(['player', 'player_model', 'http_request', 'player_login_token', 'common_token']);
        $this->CI->load->helper('cookie');

        $this->player = $this->CI->player;
        $this->player_model = $this->CI->player_model;
        $this->http_request = $this->CI->http_request;
        $this->player_login_token = $this->CI->player_login_token;
        $this->common_token = $this->CI->common_token;

        $this->utils = $this->CI->utils;
    }

    public function _check_player(&$result, $player_id){
        $isBlocked = $this->player_model->isBlocked($player_id);
        $isDeleted = $this->player_model->isDeleted($player_id);
        $isBlockedFailedLoginAttempt = $this->player_model->isBlockedFailedLoginAttempt($player_id);
        $isSelfExclusioned = $this->player_model->isSelfExclusion($player_id);
        $isCooloff = $this->player_model->isCooloff($player_id);

        if ($isBlocked) {
            // check the player.blockedUntil
            // update player.blocked and player.blockedUntil, if blockedUntil had expired
            // after updated, reload player_model->isBlocked()
            $isBlockedUntilExpired_rlt = $this->player_model->isBlockedUntilExpired($player_id);
            if( $isBlockedUntilExpired_rlt['isBlocked']
                && $isBlockedUntilExpired_rlt['isExpired']
            ){  // reload
                $isBlocked = $this->player_model->isBlocked($player_id);
            }
        }

        if ($isBlocked) {
            $result['success'] = FALSE;
            $result['errors']['blocked'] = lang('player.blocked');
            return $result;
        }

        if ($isDeleted) {
            $result['success'] = FALSE;
            $result['errors']['login'] = lang('con.04');
            return $result;
        }

        if ($isBlockedFailedLoginAttempt) {
            $getFailedLoginAttemptTimeoutUntilByPlayerId = $this->player_model->getFailedLoginAttemptTimeoutUntilByPlayerId($player_id);
            $login_attempt_reset_timeout = $this->CI->operatorglobalsettings->getSettingIntValue('player_login_failed_attempt_reset_timeout');
            $rest_of_time =floor((strtotime($getFailedLoginAttemptTimeoutUntilByPlayerId) + ($login_attempt_reset_timeout * 60) - time()) / 60);

            if(($this->CI->operatorglobalsettings->getSettingBooleanValue('player_login_failed_attempt_blocked') && !empty($login_attempt_reset_timeout) && !empty($getFailedLoginAttemptTimeoutUntilByPlayerId)) && !$this->CI->operatorglobalsettings->getSettingBooleanValue('player_login_failed_attempt_admin_unlock') ){
                if($this->CI->utils->isTimeoutNow($getFailedLoginAttemptTimeoutUntilByPlayerId, $login_attempt_reset_timeout)){
                    $this->player_model->updatePlayer($player_id, [
                        'blocked' => Player_model::DEFAULT_PLAYERACCOUNT_STATUS,
                        //'failed_login_attempt_timeout_until' => null,
                    ]);
                    $this->player_model->updatePlayerTotalWrongLoginAttempt($player_id);
                } else {
                    $result['success'] = FALSE;
                    $result['errors']['blocked'] = sprintf(lang('player.failed.login.attempt'), $rest_of_time);
                    return $result;
                }
            } else {
                $result['success'] = FALSE;
                $custom_failed_login_link_control=$this->CI->utils->getConfig('custom_failed_login_link_control');
                if($custom_failed_login_link_control){
                    $result['errors']['blocked'] = sprintf(lang('player.failed.login.attempt.forever').'<a href="' . $this->CI->utils->getConfig('custom_failed_login_link_control') . '">'.lang('click here')."</a>", $rest_of_time);
                }else{
                    $result['errors']['blocked'] = sprintf(lang('player.failed.login.attempt.forever'), $rest_of_time);
                }
                return $result;

            }
        }

        if($isSelfExclusioned){
            $result['success'] = FALSE;
            $result['errors']['selfexclusion'] = lang('player.selfexclusion');

            return $result;
        }

        if($isCooloff){
            $result['success'] = FALSE;
            $result['errors']['selfexclusion'] = lang('player.selfexclusion');

            return $result;
        }

        return $result;
    }

    protected function _login_success($player_id, $remember_me){
        if($this->CI->operatorglobalsettings->getSettingBooleanValue('player_login_failed_attempt_blocked')){
            $this->player_model->updatePlayerTotalWrongLoginAttempt($player_id);
        }

        if ($remember_me) {
            list($loginTokenId, $token) = $this->player_login_token->newLoginToken($player_id);
            set_cookie('remember_me', $token, 604800);
        } else {
            delete_cookie('remember_me');
        }
    }

    public function login_by_player_info($playerInfo, $remember_me = FALSE, $allow_clear_session = true){
        $result = [
            'success' => TRUE,
            'errors' => []
        ];

        $player_id = $playerInfo['playerId'];

        $result = $this->_check_player($result, $player_id);

        if(!$result['success']){
            return $result;
        }

        if (!$this->CI->authentication->loginByPlayerInfo($playerInfo, $allow_clear_session)) {
            $result['success'] = FALSE;
            $result['errors'] = array_replace($result['errors'], $this->CI->authentication->get_error_errors());
            return $result;
        }

        $this->_login_success($player_id, $remember_me);

        $this->CI->utils->saveHttpRequest($player_id, Http_request::TYPE_LAST_LOGIN);

        return $result;
    }

    public function login_by_password($username, $password, $remember_me = FALSE, $extra = []){
        $result = [
            'success' => TRUE,
            'errors' => []
        ];

        $player_id = $this->player_model->getPlayerIdByUsername($username);
        $result = $this->_check_player($result, $player_id);

        $enable_restrict_username_more_options = !empty($this->CI->utils->getConfig('enable_restrict_username_more_options'));
        $username_on_register = null; // default
        $usernameRegDetails = []; // for collect.
        if( ! empty($player_id) ){
            $username_on_register = $this->get_username_on_register($player_id, $usernameRegDetails);
        }

        if( empty($usernameRegDetails['username_case_insensitive']) && $enable_restrict_username_more_options && !empty($username_on_register)){ // Case Sensitive
            if ( $username_on_register != $username) {
                $result['success'] = FALSE;
                $result['errors']['login'] = lang('con.04');
            }
        } // EOF if( empty($usernameRegDetails['username_case_insensitive']) ){...

        if(!$result['success']){
            return $result;
        }

        $allow_clear_session = $this->CI->utils->getConfig('allow_clear_session_when_launch_game');
		$this->utils->debug_log(__METHOD__, 'allow_clear_session_when_launch_game', $allow_clear_session);

        $success=$this->CI->authentication->login($username, $password);
        if (!$success) {
            $result['success'] = FALSE;
            $result['errors'] = array_replace($result['errors'], $this->CI->authentication->get_error_message());
        }else{

            if( ! empty($player_id) ){
                $result['player_id'] = $player_id;
            }

            $this->_login_success($player_id, $remember_me);
            $this->CI->utils->saveHttpRequest($player_id, Http_request::TYPE_LAST_LOGIN, $extra);
        }
        if ($this->CI->utils->getConfig('enable_player_login_report')) {
            $this->CI->load->model(array('player_login_report','player_model'));
            $this->CI->utils->savePlayerLoginDetails($player_id, $username, $result, Player_login_report::LOGIN_FROM_PLAYER, $extra);
            $this->CI->utils->debug_log(__METHOD__,'savePlayerLoginDetails', $result);
        }
        return $result;
    }

    /**
     * Get the username_on_register (and regex_username detail)
     * cloned form Player_Functions::get_username_on_register()
     * @param integer $playerId The player.playerId field
     * @param point(array) $usernameRegDetails
     * @return void
     */
	public function get_username_on_register($playerId, &$usernameRegDetails = []){
		$this->CI->load->model(['player_preference']);
		$regex_username = $this->CI->utils->getUsernameReg($usernameRegDetails);
        $username_on_register = $this->CI->player_preference->getUsernameOnRegisterByPlayerId($playerId);
		return $username_on_register;
	}// EOF get_username_on_register

    public function login_by_contact_number($contact_number, $remember_me = FALSE){
        $result = [
            'success' => TRUE,
            'errors' => []
        ];


        $playerInfo = $this->player_model->getPlayerLoginInfoByNumber($contact_number);

        if(empty($playerInfo)){
            $result['success'] = FALSE;
            $result['errors']['login'] = lang('Login failed, wrong player info');
            return $result;
        }

        return $this->login_by_player_info($playerInfo, $remember_me);
    }

    public function login_by_token($token, $remember_me = FALSE){
        $game_platform_id = filter_input(INPUT_GET, 'game_platform_id', FILTER_SANITIZE_URL);
        $allow_clear_session = true;
        if(in_array($game_platform_id, $this->utils->getConfig('game_allowed_multiple_login'))){
            $allow_clear_session = false;
        }

        if($this->utils->getConfig('check_input_next_if_launcher_in_login_by_token')){
            $next_param_string = filter_input(INPUT_GET, 'next', FILTER_SANITIZE_URL);
            $needle_launcher_string= "player_center/goto_";
            if (strpos($next_param_string, $needle_launcher_string) !== false) {
                $allow_clear_session = false;
            } else {
                $needle_launcher_string= "iframe_module/goto_";
                if (strpos($next_param_string, $needle_launcher_string) !== false) {
                    $allow_clear_session = false;
                }
            }
        }

        $this->CI->utils->debug_log('login_by_token on pl --- token == '. $token);
        $result = [
            'success' => TRUE,
            'errors' => []
        ];

        $playerInfo = $this->common_token->getPlayerInfoByToken($token);

        if(empty($playerInfo)){
            $result['success'] = FALSE;
            $result['errors']['login'] = lang('Login status timed out, please login again');
            return $result;
        }

        return $this->login_by_player_info($playerInfo, $remember_me, $allow_clear_session);
    }

    public function login_from_game($platformId, $token){
        $this->CI->load->model(array('game_provider_auth'));

        $result = [
            'success' => TRUE,
            'errors' => []
        ];
        //check token by api
        /* @var Abstract_game_api $api */
        $api = $this->CI->utils->loadExternalSystemLibObject($platformId);

        if(empty($api)){
            $result['success'] = FALSE;
            $result['errors']['api_not_exists'] = lang('Login failed, wrong player info');
            return $result;
        }

        $rlt = $api->getPlayerInfoByToken($token);

        if (!isset($rlt['gameUsername']) && !isset($rlt['gameName'])) {
            $result['success'] = FALSE;
            $result['errors']['login'] = lang('Login failed, wrong player info');
            return $result;
        }

        return $this->login_by_player_info($rlt, FALSE);
    }

    // 1.1 /api/player_center/login
    /**
     * @deprecated conflict with triggerPlayerLoggedInEvent
    */
    public function triggerPlayerLoginEvent($info, $ip, $player_id) {
        // if ($this->utils->getConfig('enable_mutiple_login_notify')) {
        //     return $this->CI->triggerPlayerLoginEvent($info, $ip, $player_id);
        // }
        // return false;
    }

    public function triggerPlayerLoggedInEvent($player_id, $source_method) {
        $this->CI->load->library(['lib_queue']);
        $this->CI->load->model(['queue_result']);
        $callerType = Queue_result::CALLER_TYPE_PLAYER;
        $caller = $player_id;
        $token=$this->CI->lib_queue->triggerAsyncRemotePlayerLoggedInEvent(Queue_result::EVENT_AFTER_PLAYER_LOGIN, $player_id, $source_method, $callerType, $caller);
        return $token;
    }

    public function kickPlayer($player_id){
        if (!is_numeric($player_id) || !preg_match("/^[0-9]+$/", $player_id)) {
            return false;
        }
        $result = $this->player_model->kickoutPlayer($player_id);
        if($result){
            $this->CI->load->model(['external_common_tokens']);
            $cancel_common_token = $this->common_token->cancelAllPlayerToken($player_id);
            $cancel_external_common_token = $this->CI->external_common_tokens->cancelAllPlayerToken($player_id);

            if($this->CI->utils->getConfig('kickout_player_oauth2_token')){
                $cancel_oauth2_token = $this->kickPlayerOauth2($player_id);
                $this->CI->utils->debug_log('kickout_player_oauth2_token', $result, $cancel_common_token, $cancel_external_common_token, $cancel_oauth2_token);
                if($cancel_common_token && $cancel_external_common_token && $cancel_oauth2_token){
                    $this->player_model->updatePlayerOnlineStatus($player_id, Player_model::PLAYER_OFFLINE);
                    return true;
                }
            }

            $this->CI->utils->debug_log('kickout', $result, $cancel_common_token, $cancel_external_common_token);
            if($cancel_common_token && $cancel_external_common_token){
                $this->player_model->updatePlayerOnlineStatus($player_id, Player_model::PLAYER_OFFLINE);
                return true;
            }
        }
        return false;
    }

    public function set2DefaultGroupLevel($playerId){
        $this->CI->load->model(['group_level']);

        // set to default level
        $return =[];
        $return['bool'] = null;
        $return['code'] = null;
        if ( $this->CI->utils->isEnabledMDB() ){
            $this->CI->load->library(['group_level_lib']);
            $this->CI->load->model(array('multiple_db_model'));
            $dbName = $this->CI->multiple_db_model->db->getOgTargetDB();
            $dbKey = str_replace('_readonly', '', $dbName);
            $default_level_id = $this->CI->group_level_lib->getDefaultLevelIdBySourceDB($dbKey);
        }else{
            $default_level_id = $this->CI->utils->getConfig('default_level_id'); // @.default_level_id
        }
        if( ! empty($default_level_id) ){
            $defaultVipGroupLevelDetails = $this->CI->group_level->getVipGroupLevelDetails($default_level_id);
            if(!empty($defaultVipGroupLevelDetails)){
                $default_vipSettingId = $defaultVipGroupLevelDetails['vipSettingId'];
                $default_vipLevel = $defaultVipGroupLevelDetails['vipLevel'];
            }else{
                $return['bool'] = false;
            }
        }else{
            $return['bool'] = false;
        }
        if( ! empty($default_vipSettingId) ){
            $defaultVipLevel = $this->CI->group_level->getVIPTopLevel($default_vipSettingId, $default_vipLevel);
        }else{
            $return['bool'] = false;
        }

        if (!empty($defaultVipLevel)) {
            $this->CI->load->library(['authentication']);
            $operator = $this->CI->authentication->getUsername();
            $newPlayerLevel = $defaultVipLevel->vipsettingcashbackruleId;
            $this->CI->utils->debug_log('======================== defaultVipLevel: ', $newPlayerLevel);
            $this->CI->group_level->adjustPlayerLevel($playerId, $newPlayerLevel);
            $data = array(
                'playerId' => $playerId,
                'changes' => 'Set player level to default level',
                'createdOn' => date('Y-m-d H:i:s'),
                'operator' => $operator,
            );
            $this->CI->player_model->addPlayerInfoUpdates($playerId, $data);

            $playerInfo = $this->CI->player_model->getPlayerInfoDetailById($playerId, null);
            if(!empty($playerInfo)){
                $return['bool'] = ($playerInfo['vipsettingcashbackruleId'] == $default_level_id)? true: false;
                $return['code'] = 399;
                $return['playerInfo'] = $playerInfo;
                $return['msg'] = 'success';
            }else{
                $return['bool'] = false;
                $return['code'] = 401;
                $return['msg'] = "Empty player( playerId = $playerId )";
            }
        }else{
            $return['bool'] = false;
            if( ! empty($default_vipSettingId) ){
                $return['code'] = 414;
                $return['msg'] = "Empty default defaultVipLevel( vipSettingId = $default_vipSettingId )";
            }else{
                $return['code'] = 417;
                $return['msg'] = 'Not Found for Default Group Level, default_level_id='. $default_level_id;
            }
        }

        return $return;
    }

    public function kickPlayerOauth2($player_id) {
        $username = $this->player_model->getUsernameById($player_id);
        $this->CI->load->model(['player_oauth2_model']);

        if ($this->utils->getConfig('playerapi_sync_auth_token_to_all_currency') && $this->utils->isEnabledMDB()) {
            $cancel_all_currency_oauth2_token = $this->CI->player_oauth2_model->cancelPlayerTokensToOtherCurrency($username);
            $this->CI->utils->debug_log('kickPlayerOauth2 cancel_all_currency_oauth2_token', $cancel_all_currency_oauth2_token);
            return $cancel_all_currency_oauth2_token;
        } else {
            $cancel_oauth2_token = $this->CI->player_oauth2_model->cancelPlayerTokens($username);
            $this->CI->utils->debug_log('kickPlayerOauth2 cancel_oauth2_token', $cancel_oauth2_token);
            return $cancel_oauth2_token;
        }
    }

    public function kickPlayerGamePlatform($player_id, $player_name){
        if($this->CI->utils->isEnabledFeature('kickout_game_when_kickout_player')){
            $this->CI->load->model(['game_provider_auth']);
            $gameApis = $this->CI->game_provider_auth->getPlayerGamePlatform($player_id);

            if(empty($gameApis)){
                $this->CI->utils->debug_log('Kick player with empty player game platform');
                return;
            }

            foreach ($gameApis as $key) {
                if($this->CI->utils->getConfig('use_queue_for_kick_player_by_game_platform_id')) {
                    $data = [
                        'game_platform_id' => $key['id'],
                        'player_name' => $player_name
                    ];
                    $this->CI->load->library(['lib_queue']);
                    $callerType=Queue_result::CALLER_TYPE_SYSTEM;
                    $caller=Queue_result::SYSTEM_UNKNOWN;
                    $state=null;
                    $token = $this->CI->lib_queue->addRemoteToKickPlayerByGamePlatformId($data, $callerType, $caller, $state);
                }
                else {
                    $api = $this->CI->utils->loadExternalSystemLibObject($key['id']);
                    if ($api) {
                        $api_result = $api->logout($player_name);
                        $this->CI->utils->debug_log('api', $key['id'], 'logout result', $api_result);
                    }
                }
            }
        }else{
            $this->CI->utils->debug_log('Kick player with disabled feature');
        }
    }

    public function getPlayerById($player_id) {
        $result = $this->player_model->getPlayerInfoById($player_id);
        return $result;
    }

    public function checkModifiedFields($player_id, $new_data) {
        $old_data = $this->getPlayerById($player_id);
        $old_data = !empty($old_data) ? $old_data : [];

        $diff = array_diff_assoc($new_data, $old_data);

        foreach ($diff as $key => $value) {
            $changes[lang('reg.fields.' . $key) ?: $key] = [
                'old' => $old_data[$key],
                'new' => $new_data[$key],
            ];
        }

        $output = '<ul>';

        if (!empty($changes)) {
            ksort($changes);

            foreach ($changes as $key => $value) {
                $output .= "<li>{$key}:<br><code>Old: {$value['old']}</code><br><code>New: {$value['new']}</code></li>";
            }
        }
        $output .= '</ul>';

        return $output;
    }

    public function editPlayerDetails($data, $player_id){
        $this->player_model->editPlayerDetails($data, $player_id);
    }

    public function addPlayerInfoUpdates($playerId, $data) {
        return $this->player_model->addPlayerInfoUpdates($playerId, $data);
    }

    public function savePlayerUpdateLog($player_id, $changes, $updatedBy) {
        $dataset = [
            'playerId'	=> $player_id,
            'changes'	=> $changes,
            'createdOn'	=> $this->CI->utils->getNowForMysql() ,
            'operator'	=> $updatedBy,
        ];

        $this->addPlayerInfoUpdates($player_id, $dataset);
    }

    public function isValidPassword($player_id, $password){
        $this->CI->load->library(['authentication']);
        return $this->CI->authentication->validatePasswordOnly($player_id, $password);
    }

    public function checkLoginPwdCannotSameWithdrawalPwd($player_id, $password){
        $this->CI->load->model(['player_model']);
        $player=$this->CI->player_model->getPlayerArrayById($player_id);

        if ($this->CI->salt->decrypt($player['password'], $this->CI->config->item('DESKEY_OG')) == $password) {
            return false;
        } else {
            return true;
        }
    }

    /**
	 * Batch sync "vipsetting.groupName" to "player.groupName".
	 *
     * @param integer $theVipSettingId The field,"vipsetting,vipSettingId".
     * @param string $queue_token The token field in the queue_results data-table.
     * If there is no need to update in the queue, set it to zero.
	 * @return array The result info after sync.
	 */
	public function batch_sync_group_name_in_player($theVipSettingId = null, $queue_token = '_replace_to_queue_token_'){
		$this->CI->load->model(['vipsetting', 'group_level', 'queue_result']);
        $this->vipsetting = $this->CI->vipsetting;
        $this->group_level = $this->CI->group_level;

		$controller = $this;
		$result = null; // for collect the result detail.
		$success = $this->vipsetting->runDBTransOnly( $this->vipsetting->db
			, $result
			,  function($_db, &$_result) use ( $controller, $theVipSettingId ) { // , &$added_count, &$failed_count) {

			$total_need_to_fix_counter = 0;
			$total_affected_rows_counter = 0;

			if( empty($theVipSettingId) ){
				$sort = "vipSettingId";
				$vipSettingList = $controller->group_level->getVIPSettingList($sort, null, null);
			}else{
				$vipSettingList = $controller->vipsetting->getVIPGroupDetails($theVipSettingId);
			}

			if( ! empty($vipSettingList) ){
				$_result['affected_rows_details'] = [];

				foreach($vipSettingList as $indexNumber => $vipSetting){
					// @todo handle progress, $indexNumber / count(vipSettingList)

					$need_to_fix_counter = null;
					$vipsettingId = $vipSetting['vipSettingId'];
					$affected_rows_counter = $controller->vipsetting->sync_group_name_to_player_by_vipsettingId($vipsettingId, $need_to_fix_counter);
					$total_need_to_fix_counter += $need_to_fix_counter;
					$total_affected_rows_counter += $affected_rows_counter;

					/// $_result will be return to $result via vipsetting::runDBTransOnly().
					$_result['affected_rows_details'][$vipsettingId]['result_boolean'] =$need_to_fix_counter == $affected_rows_counter;
					$_result['affected_rows_details'][$vipsettingId]['affected_rows_counter'] = $affected_rows_counter;
					$_result['affected_rows_details'][$vipsettingId]['need_to_fix_counter'] = $need_to_fix_counter;
				} // EOF foreach($vipSettingList as $indexNumber => $vipSetting){...
			} // EOF if( ! empty($vipSettingList) ){...

			/// $_result will be return to $result via vipsetting::runDBTransOnly().
			$_result['total_need_to_fix_counter'] = $total_need_to_fix_counter;
			$_result['total_affected_rows_counter'] = $total_affected_rows_counter;

			$_result['issue_case_list'] = [];
			if( ! ($total_need_to_fix_counter == $total_affected_rows_counter) ){
				$_result['issue_case_list'][] = 'total_need_to_fix_counter != total_affected_rows_counter';
			}
			if( ! ($total_affected_rows_counter > 0)){
				$_result['issue_case_list'][] = 'total_affected_rows_counter < 1';
			}
			$returnBool = ($total_need_to_fix_counter == $total_affected_rows_counter) && ($total_affected_rows_counter > 0);
			return $returnBool;

		}); // EOF $this->vipsetting->runDBTransOnly(...
		if($success){
			$this->utils->debug_log('batch_sync_group_name_in_player.OK.result:', $result);
		}else{
			$this->utils->debug_log('batch_sync_group_name_in_player.NG.result:',$result);
		}

        if( ! empty($queue_token)
            && $queue_token != '_replace_to_queue_token_'
        ){ // trigger from queue and will update the result in queue.
            $this->utils->debug_log('batch_sync_group_name_in_player will update in queue, token:', $queue_token);
            /// update to queue_results
            $done = $success; // always be done
            $error=false;
            if(!$success){
                $error=$result;
            }
            $token = $queue_token;
            $this->CI->queue_result->appendResult($token, $result, $done, $error);
        }

        return $result;
	} // EOF batch_sync_group_name_in_player

    public function syncPlayerCurrentToMDBWithLock($player_id, $username, $insert_only=false, &$rlt=null){
        if(!$this->utils->isEnabledMDB()){
            return true;
        }
        //sync by id , so lock by id
        return $this->utils->globalLockPlayerRegistration($player_id, function ()
                use ($player_id, $insert_only, &$rlt) {
            return $this->syncPlayerCurrentToMDB($player_id, $insert_only, $rlt);
        });
    } // EOF syncPlayerCurrentToMDBWithLock
    //
    public function syncPlayerCurrentToMDB($playerId, $insertOnly=false, &$rlt=null){
        if(!$this->utils->isEnabledMDB()){
            return true;
        }

        $this->CI->load->model(['multiple_db_model']);
        $rlt=$this->CI->multiple_db_model->syncPlayerFromCurrentToOtherMDB($playerId, $insertOnly);
        $this->utils->debug_log('syncPlayerFromCurrentToOtherMDB :'.$playerId, $rlt);
        $success=false;
        if(!empty($rlt)){
            foreach ($rlt as $key => $dbRlt) {
                $success=$dbRlt['success'];
                if(!$success){
                    break;
                }
            }
        }
        return $success;
    }// EOF syncPlayerCurrentToMDB

    public function updateOnePlayerTags($playerId, $tagIds, $user_id = 1, $user_name = 'admin'){

        $_int_lang = Language_function::INT_LANG_ENGLISH;

        /// 3 params: oldTag, tagHistoryAction and latestTag
        $_changesFormat = lang('player.26', $_int_lang). ' - ';
        $_changesFormat .= lang('adjustmenthistory.title.beforeadjustment', $_int_lang). ' ( %s ) '; // oldTag
        $_changesFormat .= lang('adjustmenthistory.title.afteradjustment', $_int_lang). ' ( %s %s ) '; // tagHistoryAction, latestTag

        $results = [];
        $results['bool'] = true;
        $results['details'] = [];
        // /// 1 params: newTag
        // $_actionFormat = lang('adjustmenthistory.title.beforeadjustment', $_int_lang). ' ('. lang('player.tp03', $_int_lang). ') ';
        // $_actionFormat .= lang('adjustmenthistory.title.afteradjustment'). ' (%s) '; // $newTag
        // // 2 params: user_name, playerId
        // $_descriptionFormat = "User %s has adjusted player '%s'"; // user_name, playerId

        // Users::SUPER_ADMIN_ID
        // Users::SUPER_ADMIN_NAME
        $today     = date("Y-m-d H:i:s");
        $tagged = $this->player_model->getPlayerTags($playerId);
        if( empty($tagged) ) {
            $tagged = [];
        }

        // 2 params, tagColor and tagName.
        // $oldTag .= " <span class='tag label label-info' style='background-color:".$val['tagColor']."'>".$val['tagName']."</span>";
        $tagLabelFormat = " <span class='tag label label-info' style='background-color: %s'> %s </span>";

        if ( ! empty($tagIds) ) {
            $playerTaggedIds = [];
            $playerUnTaggedIds = [];
            $playerUnTagged = [];
            // Collect for playerTaggedIds and playerUnTaggedIds
            foreach ($tagged as $playerTag) {
                if (in_array($playerTag['tagId'], $tagIds)) {
                    $playerTaggedIds[] = $playerTag['tagId'];
                    continue;
                } else {
                    $playerUnTaggedIds[] = $playerTag['playerTagId'];
                    $playerUnTagged[] = $playerTag;
                }
            } // EOF foreach ($tagged as $playerTag) {...

            $newTagIds = [];
            foreach ($tagIds as $tagId) {
                if (in_array($tagId, $playerTaggedIds)) {
                    /// the tag already exists on the player
                    continue; // skip this round
                }
                $_rlt = $this->updateOnePlayerTags4insertAndGetPlayerTag($playerId, $user_id, $tagId);
                $results['details']['updateOnePlayerTags4insertAndGetPlayerTag'][$tagId] = $_rlt;
                $newTagIds[] = $tagId;

                /// ignore, comapi_lib::record_api_action
                // $_management = self::ACTION_MANAGEMENT_TITLE; // TODO
                // $_action = sprintf($_actionFormat, $newTag);
                // $_description = sprintf($_descriptionFormat, $user_name, $playerId);
                // $this->utils->recordAction($_management, $_action, $_description);

            } // EOF foreach ($tagIds as $tagId) {...

            // for insertPlayerTagHistory
            $oldTag = '';
            if(!empty($newTagIds)){
                $this->utils->debug_log('611.the $newTagIds ---->', $newTagIds);
                $playerNewTags = $this->CI->player_model->getTagDetails($newTagIds);
                $this->utils->debug_log('the $playerNewTags ---->', $playerNewTags);
                $latestTag = '';
                foreach($tagged as $val) {
                    $oldTag .= sprintf($tagLabelFormat, $val['tagColor'], $val['tagName']);
                }

                $tagHistoryAction = 'add';
                foreach($playerNewTags as $res){
                    $latestTag .= sprintf($tagLabelFormat, $res['tagColor'], $res['tagName']);

                    $tagHistoryData = array(
                        'playerId' => $playerId,
                        'taggerId' => $user_name,
                        'tagId' => $res['tagId'],
                        'tagColor' => $res['tagColor'],
                        'tagName' => $res['tagName'],
                    );
                    $this->player_model->insertPlayerTagHistory($tagHistoryData, $tagHistoryAction);
                } // EOF foreach($playerNewTags as $res){...

                $appendStr = '';
                $_token = $this->_addRemoteCallSyncTagsTo3rdApiJob($playerId, $user_id);
                if(! empty($_token)){
                    $appendStr .= ' SyncTagsTo3rdApi: ';
                    $appendStr .= $_token;
                }
                $this->savePlayerUpdateLog( $playerId
                                            , sprintf($_changesFormat, $oldTag, $tagHistoryAction, $latestTag)
                                            . $appendStr
                                            , $user_name
                                        );
            } // EOF if(!empty($newTagIds)){...

            if( ! empty($playerUnTagged) ){
                $tagHistoryAction = 'remove';
                $_rlt = $this->updateOnePlayerTags4removePlayerTag($playerId, $user_name, $playerUnTaggedIds, $playerUnTagged, $tagLabelFormat);
                $results['details']['updateOnePlayerTags4removePlayerTag'][$playerId] = $_rlt;
                $appendStr = '';
                $_token = $this->_addRemoteCallSyncTagsTo3rdApiJob($playerId, $user_id);
                if(! empty($_token)){
                    $appendStr .= ' SyncTagsTo3rdApi: ';
                    $appendStr .= $_token;
                }
                $unlinkTag = $_rlt['details']['unlinkTag'];
                $this->savePlayerUpdateLog( $playerId
                                            , sprintf($_changesFormat, $oldTag, $tagHistoryAction, $unlinkTag)
                                                . $appendStr
                                            , $user_name
                                        );
            } // EOF if( ! empty($playerUnTagged) ){...


        } else { /// else OF if ( ! empty($tagIds) ) {...

            # if tagId is zero update to no tag
            $_rlt = $this->updateOnePlayerTags4deleteAndGetPlayerTag($playerId, $user_name);
            $results['details']['updateOnePlayerTags4deleteAndGetPlayerTag'] = $_rlt;
            $appendStr = '';
            $_token = $this->_addRemoteCallSyncTagsTo3rdApiJob($playerId, $user_id);
            if(! empty($_token)){
                $appendStr .= ' SyncTagsTo3rdApi: ';
                $appendStr .= $_token;
            }
            $_implode_source_tag = '';
            if( ! empty($_rlt['details']['source_tag']) ){
                $source_tag = $_rlt['details']['source_tag'];
                $_implode_source_tag = implode(',', $source_tag);
            }

            $this->savePlayerUpdateLog( $playerId
                , sprintf($_changesFormat, $_implode_source_tag, lang('player.tp10', $_int_lang), '')
                    . $appendStr
                , $user_name
            );
        } // EOF if ( ! empty($tagIds) ) {...

        return $results;
    } // EOF updateOnePlayerTags
    //
    public function updateOnePlayerTags4insertAndGetPlayerTag($playerId, $user_id, $tagId){

        $today = date("Y-m-d H:i:s");
        $data = array(
            'playerId' => $playerId,
            'taggerId' => $user_id,
            'tagId' => $tagId,
            'createdOn' => $today,
            'updatedOn' => $today,
            'status' => 1,
        );
        $_rlt = $this->player_model->insertAndGetPlayerTag($data);
        return $_rlt;
    }// EOF updateOnePlayerTags4insertAndGetPlayerTag
    //
    public function updateOnePlayerTags4removePlayerTag($playerId, $user_name, $playerUnTaggedIds, $playerUnTagged, $tagLabelFormat){

        $return_array = [];
        $return_array['bool'] = null;
        $return_array['details'] = [];

        $tagged = $this->player_model->getPlayerTags($playerId);
        $_rlt = $this->removePlayerTagInPlayerSite($playerUnTaggedIds);
        $return_array['details']['removePlayerTag'] = $_rlt;
        $return_array['bool'] = $_rlt;

        $oldTag = '';
        $unlinkTag = '';
        foreach($tagged as $val) {
            $oldTag .= sprintf($tagLabelFormat, $val['tagColor'], $val['tagName']);
        }

        $_rlt = true; // for insertPlayerTagHistory()
        foreach($playerUnTagged as $res){
            $unlinkTag .= sprintf($tagLabelFormat, $res['tagColor'], $res['tagName']);

            $tagHistoryData = array(
                'playerId' => $playerId,
                'taggerId' => $user_name,
                'tagId' => $res['tagId'],
                'tagColor' => $res['tagColor'],
                'tagName' => $res['tagName'],
            );
            $tagHistoryAction = 'remove';
            $this->player_model->insertPlayerTagHistory($tagHistoryData, $tagHistoryAction);
            $_rlt = $_rlt && $this->player_model->db->insert_id();
        } // EOF foreach($playerUnTagged as $res){...
        $return_array['details']['insertPlayerTagHistory'] = $_rlt;
        $return_array['details']['unlinkTag'] = $unlinkTag;
        return $return_array;
    } // EOF updateOnePlayerTags4removePlayerTag
    //
    public function updateOnePlayerTags4deleteAndGetPlayerTag($playerId, $user_name = 'admin'){
        $this->CI->load->model(['player_model']);
        $return_array = [];
        $return_array['bool'] = null;
        $return_array['details'] = [];

        $tagged = $this->player_model->getPlayerTags($playerId);

        #delete player tags by player
        $this->deletePlayerTagByPlayerIdInPlayerSite($playerId);
        $deleted_tagged = $this->player_model->getPlayerTags($playerId);
        $_rlt = empty($deleted_tagged);

        $return_array['details']['deletePlayerTagByPlayerId'] = $_rlt;
        $return_array['bool'] = $_rlt;

        $source_tag = [];
        $_rlt = true; // for insertPlayerTagHistory()
        foreach ($tagged as $playerTag) {
            $source_tag[] = $playerTag['tagName'];
            $tagHistoryData = array(
                'playerId' => $playerId,
                'taggerId' => $user_name,
                'tagId' => $playerTag['tagId'],
                'tagColor' => $playerTag['tagColor'],
                'tagName' => $playerTag['tagName'],
            );
            $tagHistoryAction = 'remove';
            $this->player_model->insertPlayerTagHistory($tagHistoryData, $tagHistoryAction);
            $_rlt = $_rlt && $this->player_model->db->insert_id();
        }
        $return_array['details']['insertPlayerTagHistory'] = $_rlt;

        $return_array['details']['source_tag'] = $source_tag;
        return $return_array;
    } // EOF updateOnePlayerTags4deleteAndGetPlayerTag
    public function _addRemoteCallSyncTagsTo3rdApiJob($playerId, $caller){
        $_token = false;
        if( ! empty( $this->utils->getConfig('sync_tags_to_3rd_api') ) ){
            $this->CI->load->library(['lib_queue']);
            $player_id_list = [$playerId];
            $csv_file_of_bulk_import_playertag = '';
            $source_token = '';
            $callerType=Queue_result::CALLER_TYPE_ADMIN;
            $state=null;
            $lang=null;
            $_token = $this->CI->lib_queue->addRemoteCallSyncTagsTo3rdApiJob( $player_id_list
                                                                , $csv_file_of_bulk_import_playertag
                                                                , $source_token
                                                                , $callerType
                                                                , $caller
                                                                , $state
                                                                , $lang
                                                            );
            return $_token;
        } // EOF if( ! empty( $this->utils->getConfig('sync_tags_to_3rd_api') ) ){...
        return $_token;
    } // EOF _addRemoteCallSyncTagsTo3rdApiJob
    //
    public function removePlayerTagInPlayerSite($playerTagId) {
		try {
            if(is_array($playerTagId)){
                $this->player_model->db->where_in('playerTagId', $playerTagId);
            }else{
                $this->player_model->db->where('playerTagId', $playerTagId);
            }
            $this->player_model->db->delete('playertag');

			if ($this->player_model->db->_error_message()) {
				return FALSE;
			} else {
				return TRUE;
			}

		} catch (Exception $e) {
			return FALSE;
		}
	} // EOF removePlayerTagInPlayerSite

    public function deletePlayerTagByPlayerIdInPlayerSite($player_id) {
        $this->player_model->db->where('playerId', $player_id);
		$this->player_model->db->delete('playertag');
	}
}