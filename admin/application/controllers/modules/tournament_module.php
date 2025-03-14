<?php

/**
 * uri: /tournament
 *
 * @property Tournament_lib $tournament_lib
 * @property Tournament_model $tournament_model
 */
trait tournament_module{

	public function tournament($action, $additional=null)
	{
		$this->load->library(['playerapi_lib','tournament_lib']);
		$this->load->model(['tournament_model', 'users']);
		$request_method = $this->input->server('REQUEST_METHOD');
		$additional = $additional ? trim($additional, "\t\n\r\0\x0B\x2C") : null;
		switch ($action) {
			case 'list':
				if($request_method == 'POST') {
                    return $this->getTournamentList();
				}
            case 'schedule':
                if($additional == 'list' && $request_method == 'POST'){
                    return $this->getTournamentScheduleList();
                }
            case 'event':
                if($additional == 'list' && $request_method == 'POST'){
                    return $this->getTournamentEventList();
                }
                if($additional == 'rankSettings' && $request_method == 'POST'){
                    return $this->getTournamentEventRankSettingList();
                }
                if($additional == 'playerRankingList' && $request_method == 'POST'){
                    return $this->getTournamentEventPlayerRankList();
                }
            default:
                $result['code'] = Api::CODE_GENERAL_CLIENT_ERROR;
                $result['errorMessage'] = 'Invalid request';
                return $this->returnJsonResult($result);
                break;
		}
	}

    public function getTournamentList()
    {
        try {
            $validateFields = [
				['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0]
			];
			$requestBody = $this->playerapi_lib->getRequestPramas();
			$isValidateBasicPassed = $this->playerapi_lib->validParmasBasic($requestBody, $validateFields);
			if($isValidateBasicPassed['validate_flag']){
				$page = !empty($requestBody['page'])? $requestBody['page'] : 1; //default 1
				$limit = !empty( $requestBody['limit'])? $requestBody['limit'] : 10; //default 10
                $conditions = [];
                $orderby = ['tournamentId'];
                $data = $this->tournament_model->getTournamentListPagination($limit, $page, $conditions, $orderby);
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
                            'tournamentId' => $val['tournamentId'],
                            'order' => $val['order'],
                            'tournamentName' => $val['tournamentName'],
                            'status' => $val['tournamentStatus'],
                            'createdAt' => $this->playerapi_lib->formatDateTime($val['tournamentCreatedAt']),
                            'createdBy' => $this->users->getUsernameById($val['tournamentCreatedBy']),                                                    
                        ];
                    }
                }
				$output['code'] = Api::CODE_OK;
				$output['data'] = $this->playerapi_lib->convertOutputFormat($result);
				return $this->returnJsonResult($output);
			}
			throw new Exception($isValidateBasicPassed['validate_msg'], Api::CODE_INVALID_PARAMETER);
        }
        catch (\Exception $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->utils->debug_log(__METHOD__,'Exception', $ex);
            return $this->returnJsonResult($result);
        }
    }

    public function getTournamentScheduleList()
    {
        try {
            $validateFields = [
				['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
                ['name' => 'tournamentId', 'type' => 'int', 'required' => false, 'length' => 0]
			];
			$requestBody = $this->playerapi_lib->getRequestPramas();
			$isValidateBasicPassed = $this->playerapi_lib->validParmasBasic($requestBody, $validateFields);
			if($isValidateBasicPassed['validate_flag']){
				$page = !empty($requestBody['page'])? $requestBody['page'] : 1; //default 1
				$limit = !empty( $requestBody['limit'])? $requestBody['limit'] : 10; //default 10
                $conditions = [];
                if(!empty($requestBody['tournamentId'])){
                    $conditions['tournamentId'] = $requestBody['tournamentId'];
                }
                $orderby = ['scheduleId'];
                $data = $this->tournament_model->getScheduleListPagination($limit, $page, $conditions, $orderby);
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
                            'tournamentId' => $val['tournamentId'],
                            'scheduleId' => $val['scheduleId'],
                            'scheduleName' => $val['scheduleName'],
                            'periods' => $val['periods'],
                            'tournamentStartedAt' => $this->playerapi_lib->formatDateTime($val['tournamentStartedAt']),
                            'tournamentEndedAt' => $this->playerapi_lib->formatDateTime($val['tournamentEndedAt']),
                            'applyStartedAt' => $this->playerapi_lib->formatDateTime($val['applyStartedAt']),
                            'applyEndedAt' => $this->playerapi_lib->formatDateTime($val['applyEndedAt']),
                            'contestStartedAt' => $this->playerapi_lib->formatDateTime($val['contestStartedAt']),
                            'contestEndedAt' => $this->playerapi_lib->formatDateTime($val['contestEndedAt']),
                            'status' => $val['scheduleStatus'],
                            'createdAt' => $this->playerapi_lib->formatDateTime($val['scheduleCreatedAt']),
                            'createdBy' => $this->users->getUsernameById($val['scheduleCreatedBy']),
                        ];
                    }
                }
				$output['code'] = Api::CODE_OK;
				$output['data'] = $this->playerapi_lib->convertOutputFormat($result);
				return $this->returnJsonResult($output);
			}
			throw new Exception($isValidateBasicPassed['validate_msg'], Api::CODE_INVALID_PARAMETER);
        }
        catch (\Exception $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->utils->debug_log(__METHOD__,'Exception', $ex);
            return $this->returnJsonResult($result);
        }
    }

    public function getTournamentEventList()
    {
        try {
            $validateFields = [
				['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
                ['name' => 'scheduleId', 'type' => 'int', 'required' => false, 'length' => 0]
			];
			$requestBody = $this->playerapi_lib->getRequestPramas();
			$isValidateBasicPassed = $this->playerapi_lib->validParmasBasic($requestBody, $validateFields);
			if($isValidateBasicPassed['validate_flag']){
				$page = !empty($requestBody['page'])? $requestBody['page'] : 1; //default 1
				$limit = !empty( $requestBody['limit'])? $requestBody['limit'] : 10; //default 10
				$conditions = [];
                if(!empty($requestBody['scheduleId'])){
                    $conditions['scheduleId'] = $requestBody['scheduleId'];
                }
                $orderby = ['eventId'];
                $data = $this->tournament_model->getEventListPagination($limit, $page, $conditions, $orderby);
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
                            'scheduleId' => $val['scheduleId'],
                            'eventId' => $val['eventId'],
                            'eventName' => $val['eventName'],
                            'registrationFee' => $val['registrationFee'],
                            'applyCountThreshold' => $val['applyCountThreshold'],
                            'status' => $val['eventStatus'],
                            'createdAt' => $this->playerapi_lib->formatDateTime($val['eventCreatedAt']),
                            'createdBy' => $this->users->getUsernameById($val['eventCreatedBy']),
                        ];
                    }
                }
				$output['code'] = Api::CODE_OK;
				$output['data'] = $this->playerapi_lib->convertOutputFormat($result);
				return $this->returnJsonResult($output);
			}
			throw new Exception($isValidateBasicPassed['validate_msg'], Api::CODE_INVALID_PARAMETER);
        }
        catch (\Exception $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->utils->debug_log(__METHOD__,'Exception', $ex);
            return $this->returnJsonResult($result);
        }
    }

    public function getTournamentEventRankSettingList()
    {
        try {

            $validateFields = [
                ['name' => 'eventId', 'type' => 'int', 'required' => false, 'length' => 0]
			];

            $bonusTypeArray = [
                Tournament_model::RANK_BONUS_TYPE_FIXED_AMOUNT => 'Fixed',
                Tournament_model::RANK_BONUS_TYPE_PERCENTAGE => 'Percentage',
            ];
			$requestBody = $this->playerapi_lib->getRequestPramas();
			$isValidateBasicPassed = $this->playerapi_lib->validParmasBasic($requestBody, $validateFields);
			if($isValidateBasicPassed['validate_flag']){
				$eventId = $requestBody['eventId'];
                $result = $this->tournament_model->getTournamentRanksSetting($eventId);

                foreach($result as $key => $val){
                    unset($result[$key]['id']);
                    $result[$key]['bonusType'] = $bonusTypeArray[$val['bonusType']];
                }
				$output['code'] = Api::CODE_OK;
				$output['data'] = $this->playerapi_lib->convertOutputFormat($result);
				return $this->returnJsonResult($output);
			}
			throw new Exception($isValidateBasicPassed['validate_msg'], Api::CODE_INVALID_PARAMETER);
        }
        catch (\Exception $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->utils->debug_log(__METHOD__,'Exception', $ex);
            return $this->returnJsonResult($result);
        }
    }

    public function getTournamentEventPlayerRankList()
    {
        try {
            $validateFields = [
				['name' => 'page', 'type' => 'int', 'required' => false, 'length' => 0],
				['name' => 'limit', 'type' => 'int', 'required' => false, 'length' => 0],
                ['name' => 'eventId', 'type' => 'int', 'required' => false, 'length' => 0]
			];
			$requestBody = $this->playerapi_lib->getRequestPramas();
			$isValidateBasicPassed = $this->playerapi_lib->validParmasBasic($requestBody, $validateFields);
			if($isValidateBasicPassed['validate_flag']){
				$page = !empty($requestBody['page'])? $requestBody['page'] : 1; //default 1
				$limit = !empty( $requestBody['limit'])? $requestBody['limit'] : 10; //default 10
                $data = $this->tournament_model->getTournamentEventRank($requestBody['eventId'], $limit, $page, 'DESC', false);
                $result = [
                    "totalRecordCount" => $data['totalRecordCount'],
                    "totalPages" => $data['totalPages'],
                    "totalRowsCurrentPage" => $data['totalRowsCurrentPage'],
                    "currentPage" => $data['currentPage'],
                    'list' => [],
                ];
                if(!empty($data['list']) && is_array($data['list'])){
                    $result['list'] = $data['list'];
                }
				$output['code'] = Api::CODE_OK;
				$output['data'] = $this->playerapi_lib->convertOutputFormat($result);
				return $this->returnJsonResult($output);
			}
			throw new Exception($isValidateBasicPassed['validate_msg'], Api::CODE_INVALID_PARAMETER);
        }
        catch (\Exception $ex) {
            $result['code'] = $ex->getCode();
			$result['errorMessage']= $ex->getMessage();
            $this->utils->debug_log(__METHOD__,'Exception', $ex);
            return $this->returnJsonResult($result);
        }
    }
}
