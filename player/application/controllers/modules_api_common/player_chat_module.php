<?php
/**
 * uri: /chat
 *
 * @property playerapi_lib $playerapi_lib
 * @property chat_library $chat_library
 *
 */
trait player_chat_module{
	public function chat($action=null, $additional=null, $append=null)
	{
		if (!$this->initApi()) {
			return;
		}
		$this->load->library(['playerapi_lib', 'chat_library']);
		$this->load->model(['chat_manager']);
		$request_method = $this->input->server('REQUEST_METHOD');
		$additional = $additional ? trim($additional, "\t\n\r\0\x0B\x2C") : null;

		switch($action) {
            case 'init':
                if ($request_method == 'POST') {
                    return $this->initChat();
                }
                break;
		}
		return $this->returnErrorWithCode(Playerapi::CODE_GENERAL_CLIENT_ERROR);
	}

    protected function initChat(){
        $result = ['code' => Playerapi::CODE_OK];

		try {
            $username = $this->username;
            if(empty($username)){
                throw new \APIException('username is empty', Playerapi::CODE_INVALID_PARAMETER);
            }

			$request_body = $this->playerapi_lib->getRequestPramas();
            $currency = !empty($request_body['currency']) ? $request_body['currency'] : $this->currency;
            $chatRoomId = !empty($request_body['chatroomid']) ? $request_body['chatroomid'] : null;
            if($this->utils->getConfig('auto_generate_room_id')){
                if(empty($chatRoomId)){
                    $chatRoomId = random_string('alnum', 36);
                    $request_body['chatroomid'] = $chatRoomId;
                }
            }

            $validate_fields = [
				['name' => 'chatroomid', 'type' => 'string', 'required' => true],
			];

			$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
			if (!$is_validate_basic_passed['validate_flag']) {
				throw new \APIException($is_validate_basic_passed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
			}

            if(empty($chatRoomId)){
                throw new \APIException('chatroomid is empty', Playerapi::CODE_INVALID_PARAMETER);
            }

            $message = null;
            $token =  $this->playerapi_lib->loopCurrencyForAction($currency, function() use ($username, $chatRoomId, &$message) {
                $language = $this->playerapi_lib->getIsoLang($this->indexLanguage);
                return $this->chat_library->requestChatToken($username, $chatRoomId, $message, $language);
			});

            if(!empty($message)){
                throw new \APIException($message, Playerapi::CODE_OPERATION_FAILED);
            }

            return $this->returnSuccessWithResult($result);
		} catch (\APIException $ex) {
			$result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
			$this->comapi_log(__METHOD__, 'APIException', $result);
			return $this->returnErrorWithResult($result);
		}
    }
}
