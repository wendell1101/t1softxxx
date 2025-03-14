<?php

/**
 *
 * uri: /cms, /announcements, /messages, /site-announcements
 *
 * @property playerapi_lib $playerapi_lib
 * @property Playerapi_model $playerapi_model
 */
trait player_cms_module{

	public function cms($action, $additional=null, $append=null){
		if(!$this->initApi()){
			return;
		}

		$this->load->library(['playerapi_lib', 'player_message_library']);
		$this->load->model(['cms_model', 'playerapi_model', 'internal_message']);
		$request_method = $this->input->server('REQUEST_METHOD');
		$this->utils->debug_log('======================================enter cms module', $action, $additional, $append);

		switch ($action) {
			case 'announcements':
				if($request_method == 'GET') {
					if(is_numeric($additional) && $additional > 0 && $additional == round($additional, 0)){
						return $this->getAnnouncementByNewsId($additional);
					}
					else {
						return $this->getAnnouncements();
					}
				}
				break;
			case 'player-announcements':
				if($request_method == 'GET') {
					if(is_numeric($additional) && $additional > 0 && $additional == round($additional, 0)){
						return $this->getAnnouncementByNewsId($additional);
					}
					else {
						return $this->getAnnouncements();
					}
				}
				break;
			case 'messages':
				if($request_method == 'GET') {
					return $this->getMessagesByPlayerId($this->player_id);
				}
				else if ($request_method == 'POST') {
					if($additional == 'all-read') {
						return $this->setMessagesAsReadByPlayerIdAndThreaId($this->player_id);
					}
					else if($additional == 'send') {
						return $this->sendNewMessageByPlayerId($this->player_id);
					}
					else if (is_numeric($additional) && $additional > 0 && $additional == round($additional, 0) && $append == 'thread-read') {
						return $this->setMessagesAsReadByPlayerIdAndThreaId($this->player_id, $additional);
					}
					else if (is_numeric($additional) && $additional > 0 && $additional == round($additional, 0) && $append == 'read') {
						return $this->setMessagesAsReadByPlayerIdAndThreaId($this->player_id, null, $additional);
					}
					else if (is_numeric($additional) && $additional > 0 && $additional == round($additional, 0) && $append == 'reply') {
						return $this->replyMessageByPlayerIdAndThreaId($this->player_id, $additional);
					}
				}
				break;
			case 'site-announcements':
				break;
			case 'content-store':
				if($additional=='all'){
					if($this->_cmsContentStoreAll()){
						return;
					}
				}
				break;
		}

		$this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);

	}

	protected function _cmsContentStoreAll(){
		$result=['code'=>self::CODE_OK];
		$result['data']=$this->_mockDataForPlayerapi();
		$this->returnSuccessWithResult($result);
		return true;
	}

	protected function getAnnouncements() {
		$validate_fields = [
			['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'sort', 'type' => 'string', 'required' => false, 'length' => 0],
		];

		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);
		$this->load->library(array('language_function'));
		$languageIndex = $this->indexLanguage;
		$isoLang = Language_function::ISO2_LANG[$languageIndex];
		
		if ($isoLang == 'cn') {
			$isoLang = language_function::PROMO_SHORT_LANG_CHINESE;
		} else if ($isoLang == 'vt') {
			$isoLang = language_function::PROMO_SHORT_LANG_VIETNAMESE;
		}

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		$limit = !empty($request_body['limit']) ? $request_body['limit'] : 20;
		$sort = !empty($request_body['sort']) ? $request_body['sort'] : 'DESC';
		$page = !empty($request_body['page']) ? $request_body['page'] : 1;

		$page_all_announcements = $this->playerapi_model->getAnnouncements($sort, $limit, $page, $isoLang);
		$page_all_announcements['list'] = $this->playerapi_lib->customizeApiOutput($page_all_announcements['list'], ['startAt', 'endAt', 'createdAt', 'updatedAt']);
		$page_all_announcements['list'] = $this->playerapi_lib->convertOutputFormat($page_all_announcements['list']);
		$result['data'] = $page_all_announcements;
		return $this->returnSuccessWithResult($result);
	}

	protected function getAnnouncementByNewsId($news_id) {
		$result=['code'=>self::CODE_OK];
		$announcement = $this->playerapi_model->getAnnouncementByNewsId($news_id);
		$announcement = !empty($announcement) ? $this->playerapi_lib->customizeApiOutput([$announcement], ['startAt', 'endAt', 'createdAt', 'updatedAt']) : [];
		$announcement = $this->playerapi_lib->convertOutputFormat($announcement);
		$output_announcement = !empty($announcement) ? $announcement[0] : null;
		$result['data'] = $output_announcement;

		if(empty($output_announcement)) {
			return $this->returnErrorWithCode(self::CODE_PLAYER_ANNOUNCEMENT_NOT_FOUND, $this->codes[self::CODE_PLAYER_ANNOUNCEMENT_NOT_FOUND]);
		}

		return $this->returnSuccessWithResult($result);
	}

	protected function handleBroadcastMessages($player_id) {
		$player_registr_date = $this->player_model->getPlayerRegisterDate($player_id);
		$broadcast_messages = $this->player_message_library->getPlayerAllBroadcastMessages($player_id, $player_registr_date);
		$player_name = $this->username;
		$created_by_broadcast = [];
		$data = [];

		if (!empty($broadcast_messages)) {
			foreach ($broadcast_messages as $key => $value) {
				$broadcast_id = $value['broadcastId'];

				$message_id = $this->player_message_library->addNewMessageFromBroadcast($broadcast_id, $player_name);
				$created_by_broadcast[$broadcast_id] = $message_id;
			}
			$data['count'] = count($broadcast_messages);
			$data['created_by_broadcast'] = $created_by_broadcast;
		}

		$this->comapi_log(__METHOD__, '=======data', $data);
		return $data;
	}

	protected function getMessagesByPlayerId($player_id) {
		$validate_fields = [
			['name' => 'threadId', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'createdAtStart', 'type' => 'date-time', 'required' => false, 'length' => 0],
			['name' => 'createdAtEnd', 'type' => 'date-time', 'required' => false, 'length' => 0],
		];

		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		$createdAtStart= !empty($request_body['createdAtStart'])?$this->playerapi_lib->convertDateTimeToMysql($request_body['createdAtStart']):null;
		$createdAtEnd= !empty($request_body['createdAtEnd'])?$this->playerapi_lib->convertDateTimeToMysql($request_body['createdAtEnd']):null;

		$message_id = isset($request_body['threadId']) ? $request_body['threadId'] : null;
		$date_start = $createdAtStart;
		$date_end = $createdAtEnd;

		if($this->utils->getConfig('enabled_new_broadcast_message_job')) {
			$this->handleBroadcastMessages($player_id);
		}

		$result_messages = [];
		$all_player_messages = $this->playerapi_model->getPlayerAllMessages($player_id, $message_id);
		$all_player_messages_details = $this->playerapi_model->getPlayerAllMessagesDetails($player_id, $message_id, $date_start, $date_end);

		$messages_details_index = [];
		foreach ($all_player_messages_details as $messages_details_item) {
			$messages_details_item['createdAt'] = $this->playerapi_lib->formatDateTime($messages_details_item['createdAt']);
			$messages_details_item['updatedAt'] = $this->playerapi_lib->formatDateTime($messages_details_item['updatedAt']);
			$messages_details_item['read'] = (bool)$messages_details_item['read'];
			$messages_details_item['system'] = (bool)$messages_details_item['system'];
			$messages_details_item['content'] = stripslashes(htmlspecialchars_decode($messages_details_item['content']));

			$messages_details_index[$messages_details_item['threadId']][] = $messages_details_item;
		}

		foreach ($all_player_messages as $messages_item_value) {
			$messages_item_value['unread'] = false;
			$messages_item_value['messages'] = isset($messages_details_index[$messages_item_value['threadId']]) ? $messages_details_index[$messages_item_value['threadId']] : [];

			if (!empty($messages_item_value['messages'])) {
				foreach ($messages_item_value['messages'] as $message) {
					if (!$message['read'] && $message['system']) {
						$messages_item_value['unread'] = true;
						break;
					}
				}
				$result_messages[] = $messages_item_value;
			}
		}

		$result['data'] = $this->playerapi_lib->convertOutputFormat($result_messages);
		return $this->returnSuccessWithResult($result);
	}

	protected function setMessagesAsReadByPlayerIdAndThreaId($player_id, $message_thread_id = null, $message_details_id = null) {
		$result=['code'=>self::CODE_OK];

		$output_set_messages_details = 0;
		$output_set_messages = 0;
		$output_set_messages_details = $this->playerapi_model->setAllMessagesDetailsAsReadByPlayerId($player_id, $message_thread_id, $message_details_id);

		$this->comapi_log(__METHOD__, '=======output_set_messages_details', $output_set_messages_details);

		if($output_set_messages_details > 0) {
			$total_unreads_count = $this->internal_message->countPlayerUnreadMessages($message_thread_id);
			$this->comapi_log(__METHOD__, '=======total_unreads_count', $total_unreads_count);
			$output_set_messages = $this->playerapi_model->setAllMessagesAsReadByPlayerId($player_id, $message_thread_id, $message_details_id, $total_unreads_count);

			$this->comapi_log(__METHOD__, '=======output_set_messages', $output_set_messages);
			if($output_set_messages > 0) {
				$result['successMessage'] = $output_set_messages_details. lang('messages had been read.');
			}
			else {
				$result['errorMessage'] = $output_set_messages_details. ' messages had been read. But message thread failed for updating';
			}
		}
		else {
			$result['errorMessage'] = 'None message had been read.';
		}
		return $this->returnSuccessWithResult($result);
	}

	protected function sendNewMessageByPlayerId($player_id) {
		$result=['code'=>self::CODE_OK];

		try
		{
			$validate_fields = [
				['name' => 'content', 'type' => 'string', 'required' => true, 'length' => 0],
				['name' => 'subject', 'type' => 'string', 'required' => true, 'length' => 0],
			];

			$request_body = $this->playerapi_lib->getRequestPramas();
			$this->comapi_log(__METHOD__, '=======request_body', $request_body);
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
			$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

			if(!$is_validate_basic_passed['validate_flag']) {
				throw new APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
			}

			$content = $this->stripHTMLtags($request_body['content']);
			$content = $this->playerapi_lib->utf8convert($content);
			$content = addslashes($this->utils->emoji_mb_htmlentities($content));

			$subject = $this->stripHTMLtags($request_body['subject']);
			$player_name = $this->username;
			$message_details_id = $this->playerapi_model->addNewMessage($player_id, $subject, $player_name, $content);

			if(empty($message_details_id)) {
				throw new APIException(lang('None message had been send.'), self::CODE_PLAYER_MESSAGE_OPERATION_FAILED);
			}

			return $this->returnSuccessWithResult($result);
		}
		catch (Exception $ex)
		{
			$this->comapi_log(__METHOD__, '=======Exception', $ex->getMessage());
			$result['code'] =  $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
			return $this->returnErrorWithResult($result);
		}
	}

	protected function replyMessageByPlayerIdAndThreaId($player_id, $message_thread_id) {
		$result=['code'=>self::CODE_OK];

		try
		{
			$validate_fields = [
				['name' => 'content', 'type' => 'string', 'required' => true, 'length' => 0],
			];

			$request_body = $this->playerapi_lib->getRequestPramas();
			$this->comapi_log(__METHOD__, '=======request_body', $request_body);
			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
			$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);

			if(!$is_validate_basic_passed['validate_flag']) {
				throw new APIException($is_validate_basic_passed['validate_msg'], self::CODE_INVALID_PARAMETER);
			}

			$content = $this->stripHTMLtags($request_body['content']);
			$content = $this->playerapi_lib->utf8convert($content);
			$content = addslashes($this->utils->emoji_mb_htmlentities($content));

			$player_name = $this->username;
			$output_reply = $this->player_message_library->replyMessage($message_thread_id, $player_name, $content);

			if(!$output_reply['status']) {
				if($output_reply['code'] == 49025){
					throw new APIException($output_reply['message'], self::CODE_PLAYER_MESSAGE_DISABLED_REPLY);	
				}else{
					throw new APIException($output_reply['message'], self::CODE_PLAYER_MESSAGE_OPERATION_FAILED);
				}
			}
			return $this->returnSuccessWithResult($result);
		}
		catch (Exception $ex)
		{
			$this->comapi_log(__METHOD__, '=======Exception', $ex->getMessage());
			$result['code'] =  $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
			return $this->returnErrorWithResult($result);
		}
	}
}
