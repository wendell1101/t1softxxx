<?php

/**
 * Api_common core module: messages
 * Built 4/22/2020
 * @see			api_common.php
 *
 * @author		Rupert Chen
 * @copyright   tot May 2020
 */
trait comapi_core_messages {

	/**
	 * 10. 站内信接口
	 * Returns message list, or thread contents
	 *
	 * @api		/(api_common_root)/message
	 *
	 * @uses	POST:api_key		string	The api_key, as md5 sum. Required.
	 * @uses 	POST:username		tring	Player username.  Required.
	 * @uses 	POST:token			string	Effective token for player. Required.
	 * @uses	POST:messageType	string  Optional.  Any of [ 'unreadMessage', 'readMessage',
	 *       'allMessage' ].  Defaults to 'allMessage'.
	 * @uses 	POST:limit			int		Optional.  Len limit for message list, defaults to 15.
	 * @uses	POST:offset			int		Optional.  Offset of starting for message list, defaults to 0.
	 * @uses 	POST:messageId		int		Optional.  Returns thread contents of specified messageId if
	 *       present.  Limit and offset also apply.
	 *
	 * @return  JSON	{ success, code, message, result }
	 *                 Result holds the message list or thread contents when successful.
	 *
	 */
	public function message(){        
        $api_key = $this->input->post('api_key');
        $username = $this->input->post('username');
        $token = $this->input->post('token');
        $messageType = $this->input->post('messageType');
        $limit = $this->input->post('limit');
        $offset = $this->input->post('offset');
        $messageId = $this->input->post('messageId');

        if (!$this->__checkKey($api_key)) { return; }
        $this->load->library(['notify_in_app_library']);
        $this->load->model(['player_model', 'internal_message']);
        $player = $this->player_model->getPlayerByUsername($username);

        if (empty($token) || empty($player) || !$this->__isLoggedIn($player->playerId, $token)){
            // OGP-14098: Use CODE_COMMON_INVALID_TOKEN for all token errors//
            $this->__returnApiResponse(false, self::CODE_COMMON_INVALID_TOKEN, lang('Invalid token or user not logged in'));
            return;
        }

        // No messageId - Return message list
        if (empty($messageId)) {
        	// See: (model)	 Internal_message::getMessages()

            $where = null;
            $where = 'c.deleted = 0 ';

            switch($messageType) {
                case "unreadMessage":
                    $where = "AND c.status = " . Internal_message::STATUS_UNPROCESSED;
                    break;
                case "readMessage":
                    $where = "AND c.status = " . Internal_message::STATUS_READ;
                    break;
                case "playerUnreadMessage":
                    $where = "AND c.status = " . Internal_message::STATUS_ADMIN_NEW;
                case "allMessage":
                    break;
            }

            $rows = $this->internal_message->getMessages($player->playerId, $limit, $offset, $where);
            $count = (empty($rows)) ? 0 : count($rows);


            foreach ($rows as & $r) {
                $r['admin_unread'] = $r['player_unread_count'];
                $r['player_unread'] = $r['admin_unread_count'];
                unset($r['admin_unread_count'], $r['player_unread_count']);
            }


            $source_method = __METHOD__; // Messages::index
            $this->notify_in_app_library->triggerOnGotMessagesEvent($player->playerId, $source_method);


            $result = [ 'messages' => $rows, 'count' => $count ];
        	return $this->__returnApiResponse(true, self::CODE_SUCCESS, lang('Got message list successfully'), $result);

        }

        // messageId present - Return thread contents
    	$limit = $limit ?: 15;
    	$offset = $offset ?: 0;
    	$result = $this->internal_message->getMessagesHistoryByMessageId($messageId, $limit, $offset, 'desc');
    	return $this->__returnApiResponse(true, self::CODE_SUCCESS, lang('Got thread contents successfully'), $result);

	} // End function message()

    /**
     * 10.2 新增站内信
     * Write new message, or reply to some existing message
     *
     * @api		/(api_common_root)/addMessage
     *
	 * @uses	POST:api_key	string	The api_key, as md5 sum. Required.
	 * @uses 	POST:username	tring	Player username.  Req
	 * @uses 	POST:token		string	Effective token for player
	 * @uses	POST:subject	string	Subject of the message
	 * @uses	POST:message	string	The message body.
	 * @uses 	POST:messageId	int		Optional.  If present, the new message would be a reply to some
	 *        existing message.
     */
    public function addMessage() {
        $api_key    = trim($this->input->post('api_key' , 1));
        $username   = trim($this->input->post('username', 1));
        $token      = trim($this->input->post('token'  , 1));
        $subject    = trim($this->input->post('subject', 1));
        $message    = trim($this->input->post('message', 1));
        $messageId  = intval($this->input->post('messageId', 1));

        // $this->__checkKey($api_key);
        if (!$this->__checkKey($api_key)) { return; }

        $this->load->model(['player_model']);
        $this->load->library(['player_message_library', 'notify_in_app_library']);
        $player = $this->player_model->getPlayerByUsername($username);

        if (empty($token) || empty($player) || !$this->__isLoggedIn($player->playerId, $token)){
            // OGP-14098: Use CODE_COMMON_INVALID_TOKEN for all token errors
            $this->__returnApiResponse(FALSE, self::CODE_COMMON_INVALID_TOKEN, lang('Invalid token or user not logged in'));
            return;
        }

        if (empty($messageId)){
            if ($this->utils->isEnabledFeature('disabled_player_send_message')) {
                $this->comapi_log(__METHOD__, 'disabled_player_send_message is on, player cannot send new messages');
                return $this->__returnApiResponse(FALSE, self::CODE_ADD_MESSAGE_FAILED, 'error', lang('mess.20'));
            }

            if(!$this->player_message_library->run_validation(Player_message_library::VALIDATION_TYPE_ADD)){
                return $this->__returnApiResponse(FALSE, self::CODE_NOT_ALLOW_EMPTY_MESSAGE, 'error', lang('mess.16'));
            }

            $result = $this->player_message_library->addMessage($player->playerId, $player->username, $subject, $message);
            if ($result) {
                $message = lang('mess.18');
                $success = TRUE;
                $code = Api_common::CODE_SUCCESS;

                $source_method = __METHOD__; // Api_common::addMessage
                $this->notify_in_app_library->triggerOnAddedNewMessageEvent($player->playerId, $source_method);

            } else {
                $message = lang('error.default.db.message');
                $success = FALSE;
                $code = Api_common::CODE_ADD_MESSAGE_FAILED;
            }
        }
        else {

            if($this->utils->isEnabledFeature('disabled_player_reply_message')){
                $this->comapi_log(__METHOD__, 'disabled_player_reply_message is on, player cannot reply to messages');
                return $this->__returnApiResponse(FALSE, self::CODE_ADD_MESSAGE_FAILED, 'error', lang('mess.20'));
            }

            if(!$this->player_message_library->run_validation(Player_message_library::VALIDATION_TYPE_REPLY)){
                return $this->__returnApiResponse(FALSE, self::CODE_NOT_ALLOW_EMPTY_MESSAGE, 'error', lang('mess.16'));
            }

            $result = $this->player_message_library->replyMessage($messageId, $player->username, $message);

            if ($result['status']) {
                $message = lang('mess.18');
                $success = TRUE;
                $code = Api_common::CODE_SUCCESS;
            } else {
                $message = $result['message'];
                $success = FALSE;
                $code = Api_common::CODE_ADD_MESSAGE_FAILED;
            }
        }

        return $this->__returnApiResponse($success, $code, $message, []);

    } // End function addMessage()

    /**
     * Sets given message status read
     *
     * @uses    string  POST:api_key    api key given by system
     * @uses    string  POST:username   Player username
     * @uses    string  POST:token      Effective token for player
     * @uses    int     POST:messageId
     *
     * @return  JSON    Standard JSON return structure [ success, code, mesg, result ]
     *                       With result = [ promos (array), categories (array) ] when successful
     */
    public function messageSetRead() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }
        $res_mesg = '';

        try {
            $this->load->model([ 'player_model' ]);
            $this->load->library([ 'player_message_library', 'notify_in_app_library']);

            // Read arguments
            $token      = trim($this->input->post('token', true));
            $username   = trim($this->input->post('username', true));
            $messageId  = intval($this->input->post('messageId', 1));

            $request      = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token, 'messageId' => $messageId ];
            $this->comapi_log(__METHOD__, 'request', $request);

            // Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception(lang('Player username invalid'), self::CODE_COMMON_INVALID_USERNAME);
            }

            // Check player token
            $logcheck = $this->_isPlayerLoggedIn($player_id, $token);
            if ($logcheck['code'] != 0) {
                throw new Exception(lang('Token invalid or player not logged in'), self::CODE_COMMON_INVALID_TOKEN);
            }

            $int_mesg = $this->player_message_library->getMessageByIdForPlayer($player_id, $messageId);
            if (empty($int_mesg)) {
                throw new Exception(lang('Message ID invalid'), self::CODE_INTMESG_MESSAGE_ID_INVALID);
            }

            // If everything goes alright
            $ret = [
                'success'   => true ,
                'code'      => self::CODE_SUCCESS ,
                'mesg'      => lang('Message status set read'),
                'result'    => null
            ];

            $source_method = __METHOD__;
            $this->notify_in_app_library->triggerOnUpdatedMessageStatusToReadEvent($player_id, $source_method);

        }
        catch (Exception $ex) {
            $this->comapi_log(__METHOD__, 'Exception', [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ]);

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
    } // End function messageSetRead()

    public function messageReply() {
    	$api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        $username   = trim($this->input->post('username', 1));
        $token      = trim($this->input->post('token'  , 1));
        // $subject    = trim($this->input->post('subject', 1));
        $message    = trim($this->input->post('message', 1));
        $messageId  = (int) $this->input->post('messageId', 1);

        try {
        	// $this->load->model([ 'player_model' ]);
            $this->load->library([ 'player_message_library' ]);

            $request = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token,
                'messageId' => $messageId, 'message_hash' => md5($message) , 'message' => $message ];
            $this->comapi_log(__METHOD__, 'request', $request);

            // Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Player username invalid', self::CODE_COMMON_INVALID_USERNAME);
            }

            // Check player token
            $logcheck = $this->_isPlayerLoggedIn($player_id, $token);
            if ($logcheck['code'] != 0) {
                throw new Exception($logcheck['mesg'], $logcheck['code']);
            }

            // Check for messageId
            if (empty($messageId)) {
            	throw new Exception('Message ID invalid or does not belong to player', self::CODE_MESG_MESSAGE_ID_INVALID);
            }

            // Check for sys feature disabled_player_reply_message
            if ($this->utils->isEnabledFeature('disabled_player_reply_message')) {
                throw new Exception('System disabled player message replies', self::CODE_MESG_SYS_DISABLED_REPLIES);
            }

            // Validation
            if (empty($message)) {
                throw new Exception('Message body empty or contains illegal characters', self::CODE_MESG_BODY_EMPTY_OR_INVALID);
            }

            $mesg_check = $this->internal_message->checkMesgOwnership($messageId, $player_id);

            if (!$mesg_check) {
                throw new Exception('Message ID invalid or does not belong to player', self::CODE_MESG_MESSAGE_ID_INVALID);
            }

            $result = $this->player_message_library->replyMessage($messageId, $username, $message);

            $this->comapi_log(__METHOD__, 'replyMessage() return', $result);

            if (!$result['status']) {
                switch ($result['code']) {
                    case Player_message_library::CODE_MESSAGE_ID_INVALID :
                        throw new Exception('Invalid messageId', self::CODE_MESG_MESSAGE_ID_INVALID);
                        break;
                    case Player_message_library::CODE_MESSAGE_DELETED :
                        throw new Exception('Message deleted', self::CODE_MESG_MESSAGE_DELETED);
                        break;
                    case Player_message_library::CODE_MESSAGE_CLOSED :
                        throw new Exception('Message closed', self::CODE_MESG_MESSAGE_CLOSED);
                        break;
                    case Player_message_library::CODE_MESSAGE_BODY_TOO_LONG :
                        throw new Exception('Message body too long', self::CODE_MESG_BODY_TOO_LONG);
                        break;
                    case Player_message_library::CODE_ERROR_SENDING_REPLY :
                    case Player_message_library::CODE_ERROR_UPDATING_MESG_STATUS :
                    default :
                        throw new Exception('Error sending reply', self::CODE_MESG_ERROR_SENDING_MESG);
                        break;
                }
            }

            $success_mesg = lang('Reply sent successfully');


			// --------------------------------------------

            $ret = [
                'success'   => true,
                'code'      => 0,
                'mesg'      => $success_mesg ,
                'result'    => null
            ];
        }
        catch (Exception $ex) {
            $ex_log = [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ];
            $this->comapi_log(__FUNCTION__, 'Exception', $ex_log);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
        }
        finally {
            // $this->comapi_log(__FUNCTION__, 'Response', $ret);
            $this->returnApiResponseByArray($ret);
        }
    } // End class messageReply()

    public function messageNew() {
    	$api_key = $this->input->post('api_key');
    	if (!$this->__checkKey($api_key)) { return; }

        $username   = trim($this->input->post('username', 1));
        $token      = trim($this->input->post('token'  , 1));
        $subject    = trim($this->input->post('subject', 1));
        $message    = trim($this->input->post('message', 1));

        try {
        	$this->load->library([ 'player_message_library' ]);

            $request = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token,
                'subject' => $subject, 'message_hash' => md5($message) , 'message' => $message ];
            $this->comapi_log(__METHOD__, 'request', $request);

            // Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Player username invalid', self::CODE_COMMON_INVALID_USERNAME);
            }

            // Check player token
            $logcheck = $this->_isPlayerLoggedIn($player_id, $token);
            if ($logcheck['code'] != 0) {
                throw new Exception($logcheck['mesg'], $logcheck['code']);
            }

            // Check for sys feature disabled_player_send_message
            if ($this->utils->isEnabledFeature('disabled_player_send_message')) {
                throw new Exception('System disabled sending new messages by players', self::CODE_MESG_SYS_DISABLED_NEW_MESGS);
            }

            // Validation
            if (empty($message)) {
                throw new Exception('Message body empty or contains illegal characters', self::CODE_MESG_BODY_EMPTY_OR_INVALID);
            }

            if (empty($subject)) {
                throw new Exception('Message subject empty or contains illegal characters', self::CODE_MESG_SUBJECT_EMPTY_OR_INVALID);
            }

            $result = $this->player_message_library->addMessage($player_id, $username, $subject, $message);

            if (isset($result['status']) && !$result['status']) {
                switch ($result['code']) {
                    case Player_message_library::CODE_MESSAGE_BODY_TOO_LONG :
                        throw new Exception('Message body too long', self::CODE_MESG_BODY_TOO_LONG);
                        break;
                    case Player_message_library::CODE_ERROR_CREATING_NEW_MESG :
                    default :
                        throw new Exception('Error creating new message', self::CODE_MESG_ERROR_SENDING_MESG);
                        break;
                }
            }

            $success_mesg = lang('New message created successfully');

            // --------------------------------------------

            $ret = [
                'success'   => true,
                'code'      => 0,
                'mesg'      => $success_mesg ,
                'result'    => null
            ];
        }
        catch (Exception $ex) {
            $ex_log = [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ];
            $this->comapi_log(__FUNCTION__, 'Exception', $ex_log);

            $ret = [
                'success'   => false,
                'code'      => $ex->getCode(),
                'mesg'      => $ex->getMessage(),
                'result'    => null
            ];
        }
        finally {
            // $this->comapi_log(__FUNCTION__, 'Response', $ret);
            $this->returnApiResponseByArray($ret);
        }
    }

    public function messageList() {
        $api_key = $this->input->post('api_key');
        if (!$this->__checkKey($api_key)) { return; }

        $username		= $this->input->post('username');
        $token			= $this->input->post('token');
        $messageType	= $this->input->post('messageType');
        $limit 			= $this->input->post('limit');
        $offset 		= $this->input->post('offset');
        $messageId 		= $this->input->post('messageId');

        try {
        	$this->load->model(['player_model', 'internal_message']);

        	$request = [ 'api_key' => $api_key, 'username' => $username, 'token' => $token, 'messageId' => $messageId, 'messageType' => $messageType , 'limit' => $limit, 'offset' => $offset ];
            $this->comapi_log(__METHOD__, 'request', $request);

            // Check player username
            $player_id  = $this->player_model->getPlayerIdByUsername($username);
            if (empty($player_id)) {
                throw new Exception('Invalid username', self::CODE_COMMON_INVALID_USERNAME);
            }

            // Check player token
            $logcheck = $this->_isPlayerLoggedIn($player_id, $token);
            if ($logcheck['code'] != 0) {
                throw new Exception($logcheck['mesg'], $logcheck['code']);
            }

            // Has messageId: read given thread
            if (!empty($messageId)) {
                // Check ownership
                $mesg_check = $this->internal_message->checkMesgOwnership($messageId, $player_id);

                if (!$mesg_check) {
                    throw new Exception('Message ID invalid or does not belong to player', self::CODE_MESG_MESSAGE_ID_INVALID);
                }

                if ($this->internal_message->isDeleted($messageId)) {
                    throw new Exception('Message deleted', self::CODE_MESG_MESSAGE_DELETED);
                }

            	$limit	= $limit ?: 15;
		    	$offset	= $offset ?: 0;
		    	$success_mesg = lang('Thread contents retrieved successfully');
		    	$result = $this->internal_message->getMessagesHistoryByMessageId($messageId, $limit, $offset, 'desc');

		    	// if (!is_array($result) || count($result) <= 0) {
		    	// 	throw new Exception(lang('No thread found by given messageId'), self::CODE_MESG_NO_THREAD_FOUND_BY_MESSAGE_ID);
		    	// }
            }

            // No messageId: load list of threads
	        if (empty($messageId)) {
	        	$read_flag = Internal_message::FLAG_GETMESG_ALL;
	            switch ($messageType) {
	            	case 'unread' :  case "unreadMessage":
	                    $read_flag = Internal_message::FLAG_GETMESG_UNREAD;
	                    break;
	                case 'read' :    case "readMessage":
	                    $read_flag = Internal_message::FLAG_GETMESG_READ;
	                    break;
	                case 'all' :     case "allMessage":
	                default :
                        // Keep default (FLAG_GETMESG_ALL)
	                    break;
	            }

	            $rows = $this->internal_message->getMessages2($player_id, $limit, $offset, $read_flag);
	            $count = (empty($rows)) ? 0 : count($rows);


	            $success_mesg = lang('List of threads retrieved successfully');
	            $result = [ 'messages' => $rows, 'count' => $count ];
	        }

        	// --------------------------------------------

            $ret = [
                'success'   => true,
                'code'      => 0,
                'mesg'      => $success_mesg ,
                'result'    => $result
            ];
        }
        catch (Exception $ex) {
        	$ex_log = [ 'code' => $ex->getCode(), 'message' => $ex->getMessage() ];
            $this->comapi_log(__FUNCTION__, 'Exception', $ex_log);

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

    } // End function messageList()

} // End of trait comapi_core_messages
