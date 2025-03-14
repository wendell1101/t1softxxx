<?php
trait t1t_comapi_module_ole777_wormhole {

    public function getAllFailedLoginAttempts(){

		$this->load->model(array('player_login_report', 'player_model'));

		$std_creds = [];
		$ret = [];
        $playerId = null;
        $playerIdClauseList = [];
		try{

            $api_key = $this->input->post('api_key', true);
            $reveal_issue = $this->input->post('reveal_issue', true);
            $limit = $this->input->post('size_per_page', true);//max:100
            $page = $this->input->post('page_number', true);
            $username = $this->input->post('username', true);
            $date_from = $this->input->post('date_from', true);
            $date_to = $this->input->post('date_to', true);
            $tag = $this->input->post('tag', true);

            if( !empty( $limit ) ){
                $limit = $limit;
                if($limit > 100){
                    $limit = 100;
                }
            }else{
                $limit = 10;
            }

            if( empty( $date_from ) ){
                $date_from = $this->utils->getTodayForMysql() . ' 00:00:00';;
            }
            if( empty( $date_to ) ){
                $date_to = $this->utils->getNowForMysql();
            }
            if (empty($page)) {
                $page = 1;
            }
            if (!empty($username)) {
                $playerId = $this->player_model->getPlayerIdByUsername($username);
            }

            if (! empty($tag) ){
                $tagId = $this->player_model->getTagIdByTagName($tag);
                if( ! empty($tagId) ){
                    $this->player_model->getPlayerTagById($tagId, $query);
                    if ($query->num_rows() > 0) {
                        foreach ($query->result_array() as $currRow){
                            $currPlayerId = $currRow['playerId'];
                            array_push($playerIdClauseList, $currPlayerId);
                        }
                    }
                }
            }

            $params = [];
            $params['limit'] = $limit;
            $params['page'] = $page;
            $params['username'] = $username;
            $params['date_from'] = $date_from;
            $params['date_to'] = $date_to;
            $params['tag'] = $tag;
            $params['player_id_clause_list'] = $playerIdClauseList;

            $isValidToken = $this->isValidApiKey($api_key);
            if ( ! $isValidToken ) {
                $std_creds['lineNo'] = 323;
                throw new Exception('Invalid value for secure', $this->errors['ERR_INVALID_SECURE']);
            }
            $this->apply_api_acl('getAllFailedLoginAttempts', 'getAllFailedLoginAttempts_of_ole777_wormhole', $params);

            $ret = $this->player_login_report->getAllFailedLoginAttempts($playerId, $playerIdClauseList, $date_from, $date_to, $limit, $page);

        } catch (Exception $ex) {
			$this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);
            $ret = [
                'success'	=> false,
                'code'		=> $ex->getCode(),
                'mesg'		=> $ex->getMessage(),
                'result'	=> null
            ];
		} finally {
            $this->returnJsonResult($ret);
		}
    } // EOF getAllFailedLoginAttempts()

    /**
	 *  Apply ACL to api called
	 * The rule string of the setting,"api_acl"/"api_acl_override" of Config.
	 *
	 * @param string $method_name The currect method name.
	 * @param string $acl_rule_name The rule name in the setting, api_acl of Config.
	 * @param array $method_params The params of currect method, "$method_name".
	 * @return void
	 */
	public function apply_api_acl($method_name, $acl_rule_name, $method_params = []){
		if (self::API_ACL_RESULT_SUCCESS !== $this->_check_api_acl($method_name, $acl_rule_name)) {
			$this->utils->debug_log("block $acl_rule_name on api $method_name", 'params', $method_params, $this->utils->tryGetRealIPWithoutWhiteIP());
			// return $this->_show_last_check_acl_response('json');
			// return show_error('No permission', 403);
			throw new Exception('No permission by ACL', $this->errors['ERR_NO_PERMISSION_ACL']);
		}
	} // EOF apply_api_acl

    public function getAllMessages() {
        $std_creds = [];
        $ret = [];
        $playerId = null;

        try {
            $this->load->library(['player_message_library', 'playerapi_lib']);
            $this->load->model(array('internal_message','player_model'));

            $api_key = $this->input->post('api_key', true);
            $reveal_issue = $this->input->post('reveal_issue', true);
            $limit = $this->input->post('size_per_page', true); // min:10 max:100
            $username = $this->input->post('username', true);
            $date_from = $this->input->post('date_from', true);
            $date_to = $this->input->post('date_to', true);
            $status = $this->input->post('status', true);
            $admin_unread = $this->input->post('admin_unread', true);
            $page = $this->input->post('page_number', true);

            // defaults and limit
            if( !empty( $limit ) ){
                $limit = $limit;
                if($limit > 100){
                    $limit = 100;
                }
            }else{
                $limit = 10;
            }

            if( empty( $date_from ) ){
                $date_from = $this->utils->getTodayForMysql() . ' 00:00:00';;
            }
            if( empty( $date_to ) ){
                $date_to = $this->utils->getNowForMysql();
            }
            if (empty($page)) {
                $page = 1;
            }
            if (!empty($username)) {
                $playerId = $this->player_model->getPlayerIdByUsername($username);
            }

            $params = [];
            $params['limit'] = $limit;
            $params['page'] = $page;
            $params['username'] = $username;
            $params['date_from'] = $date_from;
            $params['date_to'] = $date_to;
            $params['status'] = $status;
            $params['admin_unread'] = $admin_unread;

            $isValidToken = $this->isValidApiKey($api_key);
            if ( ! $isValidToken ) {
                $std_creds['lineNo'] = 323;
                throw new Exception('Invalid value for secure', $this->errors['ERR_INVALID_SECURE']);
            }
            $this->apply_api_acl(__FUNCTION__, 'getAllMessages', $params);

            $default_admin_sender_name = $this->player_message_library->getDefaultAdminSenderName();
            $request_default_guest_name = lang('message.request_form.default_guest_name');

            $ret = $this->internal_message->getAllMessages($playerId, $status, $admin_unread, $date_from, $date_to, $limit, $page);
        } catch (Exception $ex) {
            $this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);
            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
        } finally {
            $this->returnJsonResult($ret);
        }
    }

    public function sendMessage() {
        $std_creds = [];
        $ret = [];
        $allPlayer = [];
        $allPlayerId = [];
        $failedList = [];
        try {
            $this->load->library(['player_message_library']);
            $this->load->model(array('player_model', 'internal_message'));

            $api_key = $this->input->post('api_key', true);
            $manual_input_usernames = $this->input->post('select_players');//'','',''
            $sender = $this->input->post('sender');
            $subject = $this->input->post('subject', TRUE);
            $message = $this->input->post('message', TRUE);
            $disabled_reply = !!$this->input->post('disabled_reply');//1/0
            $is_notific_action = $this->input->post('is_notification', TRUE);//1/0

            $params = [];
            $params['select_players'] = $manual_input_usernames;
            $params['sender'] = $sender;
            $params['subject'] = $subject;
            $params['message'] = $message;
            $params['disabled_reply'] = $disabled_reply;
            $params['is_notific_action'] = $is_notific_action;

            $isValidToken = $this->isValidApiKey($api_key);
            if ( ! $isValidToken ) {
                $std_creds['lineNo'] = 323;
                throw new Exception('Invalid value for secure', $this->errors['ERR_INVALID_SECURE']);
            }
            $this->apply_api_acl('getAllFailedLoginAttempts', 'getAllFailedLoginAttempts_of_ole777_wormhole', $params);

            $this->form_validation->set_rules('select_players', 'select_players', 'trim|required|xss_clean|htmlentities');
            $this->form_validation->set_rules('sender', 'sender', 'trim|required|xss_clean|htmlentities');
            $this->form_validation->set_rules('subject', 'subject', 'trim|required|xss_clean|htmlentities');
            $this->form_validation->set_rules('message', 'message', 'trim|required|xss_clean|htmlentities|callback_checkMsg');
            $this->form_validation->set_message('checkMsg', sprintf(lang('form.validation.max_length'), lang('Message'), $this->player_message_library->getDefaultAdminSendMessageLengthLimit()));

            if ($this->form_validation->run() == false) {
                throw new Exception(strip_tags(validation_errors()), $this->auth_errors['INVALID_PARAMS']);
            }

            $this->utils->debug_log('---------sendMessage manual_input_usernames', $manual_input_usernames, gettype($manual_input_usernames));

            if (!is_string($manual_input_usernames)) {
                throw new Exception('select_players Must be in string.', $this->auth_errors['INVALID_PARAMS']);
            }

            $allPlayer = array_map('trim', array_filter(explode(',', $manual_input_usernames)));
            $this->utils->debug_log('---------sendMessage allPlayer', $allPlayer);

            foreach ($allPlayer as $player_username) {
                $player_username = str_replace(array('.', ' ', "\n", "\t", "\r", ',', '[', ']'), '', trim($player_username));
                $this->utils->debug_log('---------sendMessage player_username', $player_username);
                $playerId = $this->player_model->getPlayerIdByUsername($player_username);
                 if (empty($playerId)) {
                    $failedList[] = $player_username;
                    continue;
                }
                $this->utils->debug_log('---------sendMessage playerId', $playerId);
                $allPlayerId[] = $playerId;

                $this->utils->debug_log('---------sendMessage allPlayerId', $allPlayerId);
            }

            $playerIds = array_filter(array_unique($allPlayerId));
            $this->utils->debug_log('---------sendMessage playerIds', $playerIds, 'failedList', $failedList);

            if(count($playerIds) > 50){
                throw new Exception(lang('Username exceeds maximum limit(50)'), $this->auth_errors['INVALID_PARAMS']);
            }

            if(count($playerIds) <= 0){
                throw new Exception(lang('Invalid username'), $this->auth_errors['INVALID_USERNAME']);
            }

            if (!$subject || !$message ) {
                throw new Exception(lang('con.d02'), $this->auth_errors['INVALID_PARAMS']);
            }

            if ($this->utils->getConfig('internal_message_edit_allow_only_plain_text_when_pasting')) {
                $message = $this->message_remove_script_blocks($message);
            }

            $message = $this->utf8convert($message);
            $message = $this->utils->emoji_mb_htmlentities($message);

            $today = date('Y-m-d H:i:s');
            $userId = 1;
            $sender = (empty($sender)) ? $this->player_message_library->getDefaultAdminSenderName() : $sender;
            $sender = (empty($sender)) ? $this->authentication->getUsername() : $sender;
            $this->startTrans();

            if ($playerIds) {
                $receiver_msg_mapping = [];
                foreach ($playerIds as $playerId) {
                    // messages.messageId
                    $messageId = $this->internal_message->addNewMessageAdmin($userId, $playerId, $sender, $subject, $message, TRUE, $disabled_reply);
                    if(!empty($messageId)){
                        $receiver_msg_mapping[$playerId] = $messageId;
                    }
                }
                if( ! empty($is_notific_action) ){
                    $_chunk_amount=$this->utils->getConfig('notify_api_chunk_amount');
                    $chunk_list = array_chunk($playerIds, $_chunk_amount); // split some players, a batch of 100 players
                    foreach($chunk_list as $_playerIds){
                        $playerId_list = implode('_', $_playerIds);
                        $this->do_notify_send($playerId_list, __METHOD__, $userId, $receiver_msg_mapping);
                    }
                }
            }

            $succ = $this->endTransWithSucc();

            if ($succ) {
                $ret = [
                    'code'   => 200,
                    'status' => 'success'
                ];
            }else{
                throw new Exception('Send Message Failed', $this->auth_errors['GENERAL_ERROR']);
            }
        } catch (Exception $ex) {
            $this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);
            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
        }
        finally{
            $this->returnJsonResult($ret);
        }
    }

    public function checkMsg($str) {
        return $this->player_message_library->checkMessageLength($str, $this->player_message_library->getDefaultAdminSendMessageLengthLimit());
    }

    /**
     * Extra sanitization for internal messages
     * @param	string	$mesg	Message body, generally converted to HTML escape sequences by sceditor first.
     * @return	string	Sanitized message body.
     */
    public function message_remove_script_blocks($mesg) {
        // OGP-14357 - As HTML pasted to sceditor will be encoded into htmlspecialentities, it's hard to got to run through with DOMParser
        // So we just remove everything between 'script' and '/script' here

        $mesg_sanitized = htmlspecialchars_decode($mesg);
        $mesg_sanitized = preg_replace('/(<|&lt;)\/?script.+(>|&gt;)/is', '', $mesg_sanitized);
        $mesg_sanitized = preg_replace('/(<|&lt;)!--(.|\s)*?--(>|&gt;)/', '', $mesg_sanitized);
        $mesg_sanitized = preg_replace('/(<|&lt;)meta[^>]*(>|&gt;)/', '', $mesg_sanitized);
        $mesg_sanitized = preg_replace('/(<|&lt;)\/?span[^>]*(>|&gt;)/', '', $mesg_sanitized);

        return $mesg_sanitized;
    }

    public function getAllPlayer() {
        $std_creds = [];
        $ret = [];
        $playerId = null;
        try {
            $this->load->library(['playerapi_lib']);
            $this->load->model('player_model');

            $api_key = $this->input->post('api_key', true);
            $limit = $this->input->post('limit', true); // min:10 max:100
            $offset = $this->input->post('offset', true);
            $tag = $this->input->post('tag', true);
            $username = $this->input->post('username', true);
            $date_from = $this->input->post('date_from', true);
            $date_to = $this->input->post('date_to', true);
            $first_name = $this->input->post('first_name', true);
            $affiliate = $this->input->post('affiliate', true);
            $vip_level = $this->input->post('vip_level', true);
            $email = $this->input->post('email', true);
            $phone_num = $this->input->post('phone_num', true);
            $account_status = $this->input->post('account_status', true);
            $lastLoginTime = $this->input->post('lastLoginTime', true);
            $lastDepositDate = $this->input->post('lastDepositDate', true);

            // defaults and limit
            if( !empty( $limit ) ){
                $limit = $limit;
                // if($limit > 100){
                //     $limit = 100;
                // }
            }else{
                $limit = 99999999;
            }

            if( empty( $date_from ) ){
                $date_from = '1900-01-01' . ' 00:00:00';
            }
            if( empty( $date_to ) ){
                $date_to = $this->utils->getNowForMysql();
            }

            if (!empty($username)) {
                $playerId = $this->player_model->getPlayerIdByUsername($username);
            }
            if (!empty($username)) {
                $playerId = $this->player_model->getPlayerIdByUsername($username);
            }

            $params = [];
            $params['limit'] = $limit;
            $params['username'] = $username;
            $params['date_from'] = $date_from;
            $params['date_to'] = $date_to;
            $params['offset'] = $offset;
            $params['tag'] = $tag;
            $params['first_name'] = $first_name;
            $params['affiliate'] = $affiliate;
            $params['vip_level'] = $vip_level;
            $params['email'] = $email;
            $params['phone_num'] = $phone_num;
            $params['account_status'] = $account_status;
            $params['lastLoginTime'] = $lastLoginTime;
            $params['lastDepositDate'] = $lastDepositDate;

            $isValidToken = $this->isValidApiKey($api_key);

            if ( ! $isValidToken ) {
                $std_creds['lineNo'] = 323;
                throw new Exception('Invalid value for secure', $this->errors['ERR_INVALID_SECURE']);
            }

            $this->apply_api_acl(__FUNCTION__, 'getAllPlayer', $params);

            $ret = $this->player_model->getAllPlayersForWormhole($params);
            foreach($ret as $key=>$val){
                $player_id=$ret[$key]["playerId"];
                // $getPlayerCurrentLevelForExport=$this->player_model->getPlayerCurrentLevelForExport($player_id);
                $ret[$key]["vipLevelName"]=lang($ret[$key]["groupName"])."-".lang($ret[$key]["vipLevelName"]);
                $ret[$key]["groupName"]=lang($ret[$key]["groupName"]);

                if($ret[$key]["account_status"]=="1"){
                    $ret[$key]["account_status"]="Blocked";
                }elseif($ret[$key]["account_status"]=="0"){
                    $ret[$key]["account_status"]="Normal";
                }
		    }
            // $this->utils->debug_log("===================getAllPlayer getAllPlayersForWormhole",$ret);

        } catch (Exception $ex) {
            $this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);
            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
        } finally {
            $this->returnJsonResult($ret);
        }
    }

    public function getAllPlayer2() {
        $std_creds = [];
        $ret = [];
        $playerId = null;
        try {
            $this->load->library(['playerapi_lib']);
            $this->load->model('player_model');

            $api_key = $this->input->post('api_key', true);
            $limit = $this->input->post('limit', true); // min:10 max:100
            $offset = $this->input->post('offset', true);
            $tag = $this->input->post('tag', true);
            $username = $this->input->post('username', true);
            $date_from = $this->input->post('date_from', true);
            $date_to = $this->input->post('date_to', true);
            $first_name = $this->input->post('first_name', true);
            $affiliate = $this->input->post('affiliate', true);
            $vip_level = $this->input->post('vip_level', true);
            $email = $this->input->post('email', true);
            $phone_num = $this->input->post('phone_num', true);
            $account_status = $this->input->post('account_status', true);
            $lastLoginTime = $this->input->post('lastLoginTime', true);
            $lastDepositDate = $this->input->post('lastDepositDate', true);
            $last_deposit_day_from = $this->input->post('last_deposit_day_from', true);
            $last_deposit_day_to = $this->input->post('last_deposit_day_to', true);
            $deposit_times_in_month_from = $this->input->post('deposit_times_in_month_from', true);
            $deposit_times_in_month_to = $this->input->post('deposit_times_in_month_to', true);
            $page = $this->input->post('page_number', true);

            // defaults and limit
            if( !empty( $limit ) ){
                if($limit > 100){
                    $limit = 100;
                }
            }else{
                $limit = 10;
            }
            if (!empty($username)) {
                $playerId = $this->player_model->getPlayerIdByUsername($username);
                if (empty($playerId)) {
                    throw new Exception(lang('Invalid username'), $this->auth_errors['INVALID_USERNAME']);
                }
            }
            if (!empty($last_deposit_day_from)) {
                $last_deposit_day_from = $this->utils->getMinusDaysForMysql($last_deposit_day_from, 'Y-m-d');
            }
            if (!empty($last_deposit_day_to)) {
                $last_deposit_day_to = $this->utils->getMinusDaysForMysql($last_deposit_day_to, 'Y-m-d');
            }
            if (empty($page)) {
                $page = 1;
            }

            $params = [];
            $params['limit'] = $limit;
            $params['username'] = $username;
            $params['date_from'] = $date_from;
            $params['date_to'] = $date_to;
            $params['offset'] = $offset;
            $params['tag'] = $tag;
            $params['first_name'] = $first_name;
            $params['affiliate'] = $affiliate;
            $params['vip_level'] = $vip_level;
            $params['email'] = $email;
            $params['phone_num'] = $phone_num;
            $params['account_status'] = $account_status;
            $params['lastLoginTime'] = $lastLoginTime;
            $params['lastDepositDate'] = $lastDepositDate;
            $params['last_deposit_date_from'] = $last_deposit_day_from;
            $params['last_deposit_date_to'] = $last_deposit_day_to;
            $params['deposit_times_in_month_from'] = $deposit_times_in_month_from;
            $params['deposit_times_in_month_to'] = $deposit_times_in_month_to;
            $params['page'] = $page;

            $isValidToken = $this->isValidApiKey($api_key);
            if (!$isValidToken) {
                $std_creds['lineNo'] = 323;
                throw new Exception('Invalid value for secure', $this->errors['ERR_INVALID_SECURE']);
            }

            $this->apply_api_acl(__FUNCTION__, 'getAllPlayer2', $params);

            $ret = $this->player_model->getAllPlayersForWormhole2($params, $page, $limit, $playerId);

            $today = new DateTime();
            list($start_date, $end_date) = $this->CI->utils->getThisMonthRange();

            foreach ($ret['list'] as $key => $val) {
                $daysSinceLastDeposit = null;
                $firstDepositDate = null;
                $playerId = null;
                $groupName = '';
                $vipLevelName = '';
               
                if (!empty($ret['list'][$key]["playerId"])) {
                    $playerId = $ret['list'][$key]["playerId"];
                }
                if (!empty($ret['list'][$key]["groupName"])) {
                    $groupName = lang($ret['list'][$key]["groupName"]);
                }
                if (!empty($ret['list'][$key]["vipLevelName"])) {
                    $vipLevelName = lang($ret['list'][$key]["vipLevelName"]);
                }
                if (!empty($ret['list'][$key]["vipLevelName"])) {
                    $ret['list'][$key]["account_status"] = $this->decodePlayerStatus($ret['list'][$key]["account_status"]);
                }
                if (!empty($ret['list'][$key]['lastDepositDate'])) {
                    $display_last_deposit_col = new DateTime(date('Y-m-d', strtotime($ret['list'][$key]['lastDepositDate'])));
                    $dateDiff = $today->diff($display_last_deposit_col);
                    $daysSinceLastDeposit = strval($dateDiff->days);
                }
                if (!empty($playerId)) {
                    $firstDepositDate = $this->player_model->getPlayerFirstDepositDateByPeriod($playerId, $start_date, $end_date);
                }

                $ret['list'][$key]['vipLevelName'] = $groupName."-".$vipLevelName;
                $ret['list'][$key]['groupName'] = $groupName;
                $ret['list'][$key]['firstDepositDateInMonth'] = $firstDepositDate;
                $ret['list'][$key]['daysSinceLastDeposit'] = $daysSinceLastDeposit;
		    }
            $this->utils->debug_log("===================getAllPlayer2 getAllPlayersForWormhole", $ret);
        } catch (Exception $ex) {
            $this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);
            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
        } finally {
            $this->returnJsonResult($ret);
        }
    }

    function do_notify_send($player_id, $source_method, $adminId, $receiver_msg_mapping = []){
        $this->load->library(['notify_in_app_library']);
        $this->notify_in_app_library->triggerOnSentMessageFromAdminEvent($player_id, $source_method, $adminId, $receiver_msg_mapping);
    }

    protected function utf8convert($mesge, $key = null) {
        if (is_array($mesge)) {
            foreach ($mesge as $key => $value) {
                $mesge[$key] = utf8convert($value, $key);
            }
        } elseif (is_string($mesge)) {
            $fixed = mb_convert_encoding($mesge, "UTF-8", "auto");
            return $fixed;
        }
        return $mesge;
    }


    /**
	 * check if key is validate
	 *
	 * @param $api_key
	 * @return bool|void
	 */
    protected function __checkKey($api_key){
    	$validFlag = $this->isValidApiKey($api_key);
    	// $this->utils->debug_log('__checkKey', 'validFlag', $validFlag);
		if ($validFlag === false) {
			//return error
			$this->__returnApiResponse(false, self::CODE_INVALID_SIGNATURE, lang('Invalid signature').", your api_key {$api_key} is not listed");
			return false;
		}
		else if (intval($validFlag) == self::CODE_IP_NOT_WHITELISTED) {
			// rupert: Change _getClientIp() to _getRequestIp() in consistent with isValidApiKey()
			$this->__returnApiResponse(false, self::CODE_IP_NOT_WHITELISTED, lang('IP not whitelisted') . ", your ip: {$this->_getRequestIp()}");
			return false;
		}
		else {
			return true;
		}
	}


    protected function __returnApiResponseFreestyleArrayAllowed($response = []) {

        $this->api_response = $response;
        $req_ip = $this->_getRequestIp();
        $this->comapi_lib->record_api_action($this->api_response, $req_ip);

        return $this->comapi_return_json($response);
    } // EOF __returnApiResponseFreestyleArrayAllowed()...

    protected function getPlayerProfileV2(){

        $std_creds = [];
        $ret       = [];
        $playerId  = null;
        $user      = [];
        $tag       = [];
        try{
            $this->load->model(array('player_model', 'transactions'));

            $api_key  = $this->input->post('api_key', true);
            $username = $this->input->post('username', true);

            $ret['username'] = $username;

            $isValidToken = $this->isValidApiKey($api_key);
            if ( ! $isValidToken ) {
                $std_creds['lineNo'] = 323;
                throw new Exception('Invalid value for secure', $this->errors['ERR_INVALID_SECURE']);
            }

            if (empty($username)) {

                throw new Exception('Get Player Profile Failed', $this->auth_errors['INVALID_USERNAME']);

            }

            $playerId        = $this->player_model->getPlayerIdByUsername($username);
            $user            = $this->player_model->getPlayerInfoDetailById($playerId);
            $tag             = $this->player_model->player_tagged_list($playerId);
            $lastTransaction = $this->transactions->getPlayerLastTransactionByPlayerId($playerId);

            $ret['vip_level']       = sprintf('%s - %s', lang($user['groupName']), lang($user['vipLevelName']));
            $ret['first_name']      = $user['firstName'];
            $ret['last_name']       = $user['lastName'];
            $ret['email']           = $user['email'];
            $ret['phone_num']       = $user['contactNumber'];
            $ret['im_acc_1']        = $user['imAccount'];
            $ret['im_acc_2']        = $user['imAccount2'];
            $ret['im_acc_3']        = $user['imAccount3'];
            $ret['im_acc_4']        = $user['imAccount4'];
            $ret['im_acc_5']        = $user['imAccount5'];
            $ret['tag']             = $tag;
            $ret['account_status']  = $this->decodePlayerStatus($this->utils->getPlayerStatus($playerId));
            $ret['lastLoginTime']   = $user['last_login_time'];
            $ret['lastDepositDate'] = $lastTransaction['last_deposit_date'];

            $this->apply_api_acl('getPlayerProfileV2', 'getPlayerProfileV2', $ret);

        } catch (Exception $ex) {
            $this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);
            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
        } finally {
            $this->returnJsonResult($ret);
        }
    }
    public function  decodePlayerStatus($PlayerStatus){
        switch($PlayerStatus){
            case 0:
                $result = lang('status.normal');
                break;
            case Player_model::BLOCK_STATUS:
                $result = lang('Blocked');
                break;
            case Player_model::SUSPENDED_STATUS:
                $result = lang('Suspended');
                break;
            case Player_model::SELFEXCLUSION_STATUS:
                $result = lang('Self Exclusion');
                break;
            case Player_model::STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT:
                $result = lang('Failed Login Attempt');
                break;
        }
        return $result;
    }

    public function manualDepositTo3rdParty() {
        $std_creds = [];
		$ret = [];

		try {
            $api_key             = $this->input->post('api_key', true);
			$username            = trim($this->input->post('username', true));
			$bankTypeId          = (int) $this->input->post('bankTypeId', true);
			$pay_acc_id	         = (int) $this->input->post('pay_acc_id', true);
            $secure_id           = $this->sale_order->generateSecureId();
			$amount		         = (double) $this->input->post('amount', true);
			$promo_cms_id	     = (int) $this->input->post('promo_cms_id', true);
			$playerBankDetailsId = (int) $this->input->post('playerBankDetailsId', true);
			$mode_of_deposit	 = trim($this->input->post('mode_of_deposit', true));
			$deposit_time		 = trim($this->input->post('deposit_time', true));
            $internal_note       = $this->input->post('internal_note', true);
            $external_note       = $this->input->post('external_note', true);

			$request = [
				'api_key' => $api_key, 'username' => $username,
				'bankTypeId' => $bankTypeId, 'pay_acc_id' => $pay_acc_id,
				'secure_id' => $secure_id, 'amount' => $amount,
                'promo_cms_id' => $promo_cms_id, 'playerBankDetailsId' => $playerBankDetailsId,
                'deposit_time' => $deposit_time, 'mode_of_deposit' => $mode_of_deposit,
                'internal_note' => $internal_note, 'external_note' => $external_note
			];

            $isValidToken = $this->isValidApiKey($api_key);
            if (!$isValidToken) {
                $std_creds['lineNo'] = 323;
                throw new Exception('Invalid value for secure', $this->errors['ERR_INVALID_SECURE']);
            }

			$this->utils->debug_log(__METHOD__, 'request', $request);

            $deposit_dataset = $request;

			// Check player username
			$player_id  = $this->player_model->getPlayerIdByUsername($username);
			if (empty($player_id)) {
				throw new Exception('Player username invalid', self::CODE_COMMON_INVALID_USERNAME);
			}

			$dep_list = $this->comapi_lib->depcat_deposit_paycats($player_id);

			if (empty($this->comapi_lib->depcat_pay_account_by_type_bankTypeId($player_id, $bankTypeId, Comapi_lib::DEPCAT_MANUAL))) {
				throw new Exception(lang('bankTypeId not a valid manual payment or not available for player'), self::CODE_MDN_BANKTYPEID_NOT_VALID_MANU_PAY);
			}

			$secure_id_probe = $this->sale_order->getSaleOrderBySecureId($secure_id);

			if (empty($secure_id) || !empty($secure_id_probe)) {
				throw new Exception(lang('Invalid secure_id'), self::CODE_MDN_INVALID_SECURE_ID);
			}

            if (empty($internal_note)) {
				throw new Exception(lang('Internal note is empty'), self::CODE_MDN_INTERNAL_NOTE_EMPTY);
			}

			$dep_acc = $this->comapi_lib->depcat_manu_account_info($bankTypeId, $player_id, 'generate secure_id', $pay_acc_id);

			if ($dep_acc['code'] != 0) {
				throw new Exception(lang('Payment account not available'), self::CODE_MDN_PAYMENT_ACCOUNT_NOT_ACCESSIBLE);
			}

			// Check playerBankDetailsId: empty or belongs to player
			if (!empty($playerBankDetailsId) && !$this->playerbankdetails->isValidBankForPlayer($playerBankDetailsId, $player_id)) {
				throw new Exception(lang('playerBankDetailsId invalid, must be active and belongs to deposit player'), self::CODE_MDN_PLAYERBANK_INVALID);
			}

            // Optional arguments: mode_of_deposit, deposit_time

			// Check mode_of_deposit: empty or in $this->config->item('mode_of_deposit')
			$all_modes_of_deposit = $this->config->item('mode_of_deposit');
			if (!empty($mode_of_deposit) && !in_array($mode_of_deposit, $all_modes_of_deposit)) {
				throw new Exception(lang('mode_of_deposit invalid, must be empty or any of following: ') . json_encode($all_modes_of_deposit), self::CODE_MDN_DEPOSIT_METHOD_INVALID);
			}

			// OGP-23166: check if player has any withdrawal account if 'deposit bank' is disabled
			if ($this->comapi_lib->fin_acc_player_need_to_bind_wx_account_first($player_id)) {
				throw new Exception(lang('Player has no withdrawal account so far, must set up one first'), self::CODE_MDN_PLAYER_HAS_NO_WX_ACCOUNT);
			}

			// Check deposit_time: empty or valid datetime
			if (!empty($deposit_time)) {
				$dep_time_parsed = strtotime($deposit_time);
				if (empty($dep_time_parsed)) {
					throw new Exception(lang('deposit_time invalid, must be valid datetime'), self::CODE_MDN_DEPOSIT_TIME_INVALID);
				}
			}

			// Build deposit dataset
			$deposit_dataset['playerBankDetailsId']	= $playerBankDetailsId;
			$deposit_dataset['mode_of_deposit']		= $mode_of_deposit;
			$deposit_dataset['deposit_datetime']	= $deposit_time;

			unset($deposit_dataset['api_key']);
			$deposit_dataset['player_id'] = $player_id;
			$deposit_dataset['payment_account_id'] = $dep_acc['result']['payment_account_id'];
			$deposit_dataset['deposit_notes'] = 'by comapi manualDepositTo3rdParty';

			$deposit_res = $this->comapi_lib->comapi_manual_deposit($deposit_dataset);

			if ($deposit_res['success'] == false) {
				throw new Exception($deposit_res['mesg'], $deposit_res['code']);
			}

			$this->utils->debug_log(__METHOD__, 'deposit_res', $deposit_res);

			$ret = [
				'success'   => true,
				'code'      => 0,
				'mesg'      => $deposit_res['mesg'],
				'result'    => null
			];
			$this->utils->debug_log(__METHOD__, 'Successful response', $ret);
		}
		catch (Exception $ex) {
            $this->utils->debug_log(__FUNCTION__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ], $std_creds);
			$ret = [
				'success'   => false,
				'code'      => $ex->getCode(),
				'mesg'      => $ex->getMessage(),
				'result'    => null
			];
		}
		finally {
			$this->returnApiResponseByArray($ret);
		}
	} // End function manualDepositTo3rdParty()

} // End trait t1t_comapi_module_ole777_wormhole