<?php

/**
 * uri: /reports
 */
trait player_reports_module {

	public function reports($action, $additional=null){
		if(!$this->initApi()){
			return;
		}

		$this->load->library(['playerapi_lib', 'payment_library']);
		$this->load->model(['playerapi_model']);
		$request_method = $this->input->server('REQUEST_METHOD');

		switch ($action) {
			case 'gamelog':
                if($request_method == 'POST') {
                    return $this->getPlayerGameLogsByPlayerId($this->player_id);
                }
				break;
			case 'transaction':
				return $this->getPlayerTransactionsByPlayerId($this->player_id);
				break;
			case 'promorequest':
				return $this->getPlayerPromoByPlayerId($this->player_id);
				break;
		}

		$this->returnErrorWithCode(self::CODE_GENERAL_CLIENT_ERROR);
	}


	public function getPlayerTransactionsByPlayerId($player_id) {
		$validate_fields = [
			['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'createdAtEnd', 'type' => 'date-time', 'required' => true, 'length' => 0],
			['name' => 'createdAtStart', 'type' => 'date-time', 'required' => true, 'length' => 0],
			['name' => 'sort', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'type', 'type' => 'int', 'required' => false, 'length' => 0],
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

		$createdAtStart = !empty($request_body['createdAtStart'])?$this->playerapi_lib->convertDateTimeToMysql($request_body['createdAtStart']):$this->playerapi_lib->convertDateTimeToMysql(date('Y-m-d').' 00:00:00');
		$createdAtEnd = !empty($request_body['createdAtEnd'])?$this->playerapi_lib->convertDateTimeToMysql($request_body['createdAtEnd']):$this->playerapi_lib->convertDateTimeToMysql(date('Y-m-d').' 23:59:59');

		$time_start = $createdAtStart;
		$time_end = $createdAtEnd;
		$limit = !empty($request_body['limit']) ? $request_body['limit'] : 20;
		$sort = !empty($request_body['sort']) ? $request_body['sort'] : 'DESC';
		$transactions_type = isset($request_body['type']) ? $request_body['type'] : null;
		if(!is_null($transactions_type)) {
			$transactions_type = $this->playerapi_lib->matchInputTransactionType($transactions_type);
		}
		$page = !empty($request_body['page']) ? $request_body['page'] : 1;
		$currency = !empty($request_body['currency']) ? strtoupper($request_body['currency']) : strtoupper($this->config->item('fallback_target_db'));

		$page_player_transactions = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($currency, $player_id, $time_start, $time_end, $limit, $sort, $transactions_type, $page){
			return $this->playerapi_model->getPlayerTransactionsByPlayerId($currency, $player_id, $time_start, $time_end, $limit, $sort, $transactions_type, $page);
		});
		$rebuild_key_arr = [];
		foreach ($page_player_transactions['list'] as $key => $row) {
			$operator = [
				"id" => $row["operatorId"],
				"username" => $row["operatorUsername"]
			];
			unset($row["operatorId"], $row["operatorUsername"]);
		
			$typeIndex = array_search("type", array_keys($row));

			$row = array_merge(
				array_slice($row, 0, $typeIndex),
				["operator" => $operator],
				array_slice($row, $typeIndex)
			);
		
			$page_player_transactions['list'][$key] = $row;
		}
		$page_player_transactions['list'] = $this->playerapi_lib->customizeApiOutput($page_player_transactions['list'], ['createdAt','transactionType']);
		$page_player_transactions['list'] = $this->playerapi_lib->convertOutputFormat($page_player_transactions['list']);
		$result['data'] = $page_player_transactions;
		return $this->returnSuccessWithResult($result);
	}


	public function getPlayerGameLogsByPlayerId($player_id) {

    	$this->load->library(['game_list_lib','language_function']);

        $game_logs_status_map = [
            Game_logs::STATUS_SETTLED=>1,
            Game_logs::STATUS_PENDING=>0,
            Game_logs::STATUS_CANCELLED=>10
        ];


		$validate_fields = [
			['name' => 'betTimeEnd', 'type' => 'date-time', 'required' => true, 'length' => 0],
			['name' => 'betTimeStart', 'type' => 'date-time', 'required' => true, 'length' => 0],
			['name' => 'externalUid', 'type' => 'string', 'required' => false, 'length' => 0],
            ['name' => 'virtualGamePlatformList', 'type' => 'array', 'required' => false, 'length' => 0],//api IDs
			['name' => 'virtualGameId', 'type' => 'string', 'required' => false, 'length' => 0],//game ap id +'-'+ external game id
            ['name' => 'gameTypeCode', 'type' => 'string', 'required' => false, 'length' => 0],
            ['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
            ['name' => 'minBet', 'type' => 'positive_double', 'required' => false, 'length' => 0],
            ['name' => 'maxBet', 'type' => 'positive_double', 'required' => false, 'length' => 0],
			['name' => 'minPayout', 'type' => 'positive_double', 'required' => false, 'length' => 0],
            ['name' => 'maxPayout', 'type' => 'positive_double', 'required' => false, 'length' => 0],
            ['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
            ['name' => 'roundId', 'type' => 'string', 'required' => false, 'length' => 0],
            ['name' => 'sort', 'type' => 'string', 'required' => false, 'length' => 0],
            ['name' => 'status', 'type' => 'int', 'required' => false, 'length' => 0],

            /*['name' => 'agentId', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'agentIncludeDownline', 'type' => 'bool', 'required' => false, 'length' => 0],
			['name' => 'gameCode', 'type' => 'string', 'required' => false, 'length' => 0],
			['name' => 'gameTypeIds', 'type' => 'array', 'required' => false, 'length' => 0],
            ['name' => 'groups', 'type' => 'array', 'required' => false, 'length' => 0],
			['name' => 'playerId', 'type' => 'int', 'required' => false, 'length' => 0],
            ['name' => 'tags', 'type' => 'array', 'required' => false, 'length' => 0],
            ['name' => 'username', 'type' => 'string', 'required' => false, 'length' => 0],
            ['name' => 'usernames', 'type' => 'array', 'required' => false, 'length' => 0],*/
        ];

		$this->load->library(["game_list_lib", 'language_function']);

		// $languageIndex = 1;
		$languageIndex=$this->indexLanguage;

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

		$this->load->model(array('common_token','game_description_model','favorite_game_model','game_logs'));

		$request_body['betTimeStart'] = !empty($request_body['betTimeStart'])?$this->playerapi_lib->convertDateTimeToMysql($request_body['betTimeStart']):$this->playerapi_lib->convertDateTimeToMysql(date('Y-m-d').' 00:00:00');
		$request_body['betTimeEnd'] = !empty($request_body['betTimeEnd'])?$this->playerapi_lib->convertDateTimeToMysql($request_body['betTimeEnd']):$this->playerapi_lib->convertDateTimeToMysql(date('Y-m-d').' 23:59:59');

        $result = $this->game_logs->getPlayerCenterGameLogs($player_id, $request_body);
        if($result){
            $result['code'] = self::CODE_OK;
        }else{
		    $result['code'] = self::CODE_SERVER_ERROR;
        }

        return $this->returnSuccessWithResult($result);
	}

	public function getPlayerPromoByPlayerId($player_id) {
		$validate_fields = [
			['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
			['name' => 'createdAtEnd', 'type' => 'date-time', 'required' => true, 'length' => 0],
			['name' => 'createdAtStart', 'type' => 'date-time', 'required' => true, 'length' => 0],
			['name' => 'sortBy', 'type' => 'array', 'required' => false, 'length' => 0],
		];

		$result=['code'=>self::CODE_OK];
		$request_body = $this->playerapi_lib->getRequestPramas();
		$this->load->model(['player_promo']);
		$this->comapi_log(__METHOD__, '=======request_body', $request_body);
		$is_validate_basic_passed = $this->playerapi_lib->validParmasBasic($request_body, $validate_fields);
		$this->comapi_log(__METHOD__, '=======is_validate_basic_passed', $is_validate_basic_passed);
		
		if(!$is_validate_basic_passed['validate_flag']) {
			$result['code'] = self::CODE_INVALID_PARAMETER;
			$result['errorMessage']= $is_validate_basic_passed['validate_msg'];
			return $this->returnErrorWithResult($result);
		}

		$time_start = !empty($request_body['createdAtStart'])?$this->playerapi_lib->convertDateTimeToMysql($request_body['createdAtStart']):$this->playerapi_lib->convertDateTimeToMysql(date('Y-m-d').' 00:00:00');
		$time_end = !empty($request_body['createdAtEnd'])?$this->playerapi_lib->convertDateTimeToMysql($request_body['createdAtEnd']):$this->playerapi_lib->convertDateTimeToMysql(date('Y-m-d').' 23:59:59');
		$limit = !empty($request_body['limit']) ? $request_body['limit'] : 20;
		$key = !empty($request_body['sortBy']['sortKey']) ? $request_body['sortBy']['sortKey'] : '';
		$type = !empty($request_body['sortBy']['sortType']) ? $request_body['sortBy']['sortType'] : '';
		$page = !empty($request_body['page']) ? $request_body['page'] : 1;
		$currency = !empty($request_body['currency']) ? strtoupper($request_body['currency']) : strtoupper($this->config->item('fallback_target_db'));

		$sortKey = '';
		$sortType = '';
		$sortColumnList = [
			'applyDate' => 'playerpromo.dateApply',
		];
		if (!empty($key) && array_key_exists($key, $sortColumnList)) {
			$sortKey = $sortColumnList[$key];
		}
		if (!empty($type) && in_array($type, ['asc', 'desc'])) {
			$sortType = $type;
		}

		$page_player_promo_history = $this->playerapi_lib->switchCurrencyForAction($currency, function() use ($currency, $player_id, $time_start, $time_end, $limit, $sortKey, $sortType, $page){
			$data = $this->playerapi_model->getPlayerPromoByPlayerId($currency, $player_id, $time_start, $time_end, $limit, $sortKey, $sortType, $page);
			$result = [
				"totalRecordCount" => $data['totalRecordCount'],
				"totalPages" => $data['totalPages'],
				"totalRowsCurrentPage" => $data['totalRowsCurrentPage'],
				"currentPage" => $data['currentPage'],
				'list' => [],
			];
			if(!empty($data['list']) && is_array($data['list'])){
				foreach ($data['list'] as $val) {       
					$result['list'][] = [
						'currency' => $currency,
						'applyDate' => $val['dateApply'],
						'promoId' => $val['promoCmsSettingId'],
						'promoName' => $val['promoName'],
						'status' => $this->player_promo->statusToName($val['transactionStatus']),
						'bonusAmount' => $val['bonusAmount']
					];
				}
			}
			return $result;
		});

		$result['data'] = $this->playerapi_lib->convertOutputFormat($page_player_promo_history);
        return $this->returnSuccessWithResult($result);
	}
}
