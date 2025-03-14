<?php

/**
 * uri: /notification
 *
 * @property Player_notification_library $player_notification_library
 * @property Player_notification $player_notification
 * @property Playerapi $playerapi
 */
trait player_notification_module{

	public function notification($action, $additional=null)
	{
		if(!$this->initApi()){
			return;
		}
		$this->load->library(['playerapi_lib','player_notification_library']);
		$this->load->model(['player_notification']);
		$request_method = $this->input->server('REQUEST_METHOD');

		switch ($action) {
			case 'list':
				if($request_method == 'GET') {
					return $this->getNotificationList($this->player_id);
				}
                break;
            case 'uninformed':
                if($request_method == 'GET') {
                    return $this->getUninformedNotificationList($this->player_id);
                }
                break;
            case 'informed':
                if($request_method == 'POST') {
                    return $this->postInformedNotification($this->player_id);
                }
                break;
		}
		return $this->returnErrorWithCode(Playerapi::CODE_GENERAL_CLIENT_ERROR);
	}

    public function getNotificationList($playerId)
    {
        try {
			$validateFields = [
                ['name' => 'currency', 'type' => 'string', 'required' => true, 'length' => 0],
				['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0]
			];
			$requestBody = $this->playerapi_lib->getRequestPramas();
			$isValidateBasicPassed = $this->playerapi_lib->validParmasBasic($requestBody, $validateFields);
			if($isValidateBasicPassed['validate_flag']){
                $currency = $requestBody['currency'];
				$page = !empty($requestBody['page'])? $requestBody['page'] : 1; //default 1
				$limit = !empty( $requestBody['limit'])? $requestBody['limit'] : 10; //default 10
				$output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($page, $limit, $playerId){
					$conditions = [
                        'player_id' => $playerId,
                    ];
                    $orderby = ['notify_id'];
					$notifycationData = $this->player_notification->getNotifyListPagination($limit, $page, $conditions, $orderby);
                    $result = [
                        "totalRecordCount" => $notifycationData['totalRecordCount'],
                        "totalPages" => $notifycationData['totalPages'],
                        "totalRowsCurrentPage" => $notifycationData['totalRowsCurrentPage'],
                        "currentPage" => $notifycationData['currentPage'],
                        'list' => [],
                    ];
                    if(!empty($notifycationData['list']) && is_array($notifycationData['list'])){
                        foreach ($notifycationData['list'] as $notifycation) {     
                            $title = @json_decode($notifycation['title'], TRUE);
                            $translatedTitle = $this->player_notification_library->getNotifyTitle($title, $notifycation);
                            $message = @json_decode($notifycation['message'], TRUE);       
                            $translatedMessage = $this->player_notification_library->getNotifyMessage($message, $notifycation);
                            $result['list'][] = [
                                'notifyId' => $notifycation['notify_id'],
                                'playerId' => $notifycation['player_id'],
                                'source_type' => $notifycation['source_type'],
                                'notify_type' => $notifycation['notify_type'],
                                'title' => $this->playerapi_lib->stripHtmltagsAndDecodeSpecialChars($translatedTitle),
                                'message' => $this->playerapi_lib->stripHtmltagsAndDecodeSpecialChars($translatedMessage),                
                                'extraInfo' => $this->player_notification_library->getNotifyExtraInfo($notifycation['source_type'], $message),
                                'url' => $notifycation['url'],
                                'is_notify' => $notifycation['is_notify'],
                                'notify_time' => $this->playerapi_lib->formatDateTime($notifycation['notify_time']),
                                'created_at' => $this->playerapi_lib->formatDateTime($notifycation['created_at']),
                                'updated_at' => $this->playerapi_lib->formatDateTime($notifycation['updated_at']),
                            ];
                        }
                    }
                    return $result;
				});
				$result['code'] = Playerapi::CODE_OK;
				$result['data'] = $this->playerapi_lib->convertOutputFormat($output);
				return $this->returnSuccessWithResult($result);
			}
			throw new APIException($isValidateBasicPassed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }

    public function getUninformedNotificationList($playerId)
    {
        try {
			$validateFields = [
                ['name' => 'currency', 'type' => 'string', 'required' => true, 'length' => 0],
			];
			$requestBody = $this->playerapi_lib->getRequestPramas();
			$isValidateBasicPassed = $this->playerapi_lib->validParmasBasic($requestBody, $validateFields);
			if($isValidateBasicPassed['validate_flag']){
                $currency = $requestBody['currency'];
                //page and limit didn't use in this function
				$page = 1;
				$limit = 10;
				$output = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($page, $limit, $playerId){
					$conditions = [
                        'player_id' => $playerId,
                        'is_notify' => '0',
                    ];
                    $orderby = ['notify_id'];
					$notifycationData = $this->player_notification->getNotifyListPagination($limit, $page, $conditions, $orderby);
                    $result = [];
                    if(!empty($notifycationData['list']) && is_array($notifycationData['list'])){
                        foreach ($notifycationData['list'] as $notifycation) {     
                            $title = @json_decode($notifycation['title'], TRUE);
                            $translatedTitle = $this->player_notification_library->getNotifyTitle($title, $notifycation);
                            $message = @json_decode($notifycation['message'], TRUE);       
                            $translatedMessage = $this->player_notification_library->getNotifyMessage($message, $notifycation);
                            $result[] = [
                                'notifyId' => $notifycation['notify_id'],
                                'playerId' => $notifycation['player_id'],
                                'source_type' => $notifycation['source_type'],
                                'notify_type' => $notifycation['notify_type'],
                                'title' => $this->playerapi_lib->stripHtmltagsAndDecodeSpecialChars($translatedTitle),
                                'message' => $this->playerapi_lib->stripHtmltagsAndDecodeSpecialChars($translatedMessage),                
                                'extraInfo' => $this->player_notification_library->getNotifyExtraInfo($notifycation['source_type'], $message),
                                'url' => $notifycation['url'],
                                'is_notify' => $notifycation['is_notify'],
                                'notify_time' => $this->playerapi_lib->formatDateTime($notifycation['notify_time']),
                                'created_at' => $this->playerapi_lib->formatDateTime($notifycation['created_at']),
                                'updated_at' => $this->playerapi_lib->formatDateTime($notifycation['updated_at']),
                            ];
                        }
                    }
                    return $result;
				});
				$result['code'] = Playerapi::CODE_OK;
				$result['data'] = $this->playerapi_lib->convertOutputFormat($output);
				return $this->returnSuccessWithResult($result);
			}
			throw new APIException($isValidateBasicPassed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }

    public function postInformedNotification($playerId)
    {
        try {
			$validateFields = [
                ['name' => 'currency', 'type' => 'string', 'required' => true, 'length' => 0],
                ['name' => 'notifyId', 'type' => 'int', 'required' => true, 'length' => 0],
			];
			$requestBody = $this->playerapi_lib->getRequestPramas();
			$isValidateBasicPassed = $this->playerapi_lib->validParmasBasic($requestBody, $validateFields);
			if($isValidateBasicPassed['validate_flag']){
                $currency = $requestBody['currency'];
                $notifyId = $requestBody['notifyId'];
				$success = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($playerId, $notifyId){
                    return $this->player_notification_library->setIsNotify($playerId, $notifyId, TRUE);
				});
                if(!$success){
                    throw new APIException('Failed to informed notification', Playerapi::CODE_NOTIFICATION_INFORMED_FAILED);
                }
				$result['code'] = Playerapi::CODE_OK;
				return $this->returnSuccessWithResult($result);
			}
			throw new APIException($isValidateBasicPassed['validate_msg'], Playerapi::CODE_INVALID_PARAMETER);
        }
        catch (\APIException $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->comapi_log(__METHOD__, 'APIException', $result);

            return $this->returnErrorWithResult($result);
        }
    }
}
