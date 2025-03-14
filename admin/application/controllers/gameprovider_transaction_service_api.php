<?php

if(! defined("BASEPATH")){
    exit("No direct script access allowed");
}

require_once dirname(__FILE__) . "/BaseController.php";

class Gameprovider_transaction_service_api extends BaseController
{
    protected $request;
    protected $requestMethod;
    protected $requestHeaders;
    protected $start_time;
    public $output;
    protected $response_result_id;
    protected $method;
	public $enable_pagination = false;
	private $game_provider_backend_api_white_ip_list;
	private $tester_white_ip_list;
	private $remove_white_ip_list;
	private $outlet_code;
	private $outlet_name;
	public $ip;

    const SUCCESS = 0;
	const STATUS_SUCCESS = "SUCCESS";
	const STATUS_FAILED = "FAILED";
	const PLAYER_TYPE_ONLINE = "ONLINE";
	const PLAYER_TYPE_LOCAL = "LOCAL";

    const ERROR_INVALID_REQUEST = '0x1034';
	const ERROR_INTERNAL_SERVER_ERROR = '0x1200';
	const ERROR_IP_NOT_ALLOWED = '0x10';
	const ERROR_SERVER = '0x12';
	const ERROR_CONNECTION_TIMED_OUT = '0x13';
	const ERROR_SERVICE_NOT_AVAILABLE = '0x15';
	const ERROR_INVALID_SIGNATURE = '0x16';
    const ERROR_REQUEST_METHOD_NOT_ALLOWED = 405;
    const ERROR_API_METHOD_NOT_ALLOWED = 401;

    const METHOD_GET_TRANSACTION ="getTransaction";
    

	const HTTP_STATUS_CODE_MAP = [
		self::SUCCESS => 200,
		self::ERROR_INVALID_REQUEST,
		self::ERROR_INTERNAL_SERVER_ERROR,
		self::ERROR_SERVER => 500,
		self::ERROR_CONNECTION_TIMED_OUT => 500,
		self::ERROR_SERVICE_NOT_AVAILABLE => 400,
		self::ERROR_IP_NOT_ALLOWED => 401,
		self::ERROR_REQUEST_METHOD_NOT_ALLOWED => 405,
		self::ERROR_API_METHOD_NOT_ALLOWED => 500
	];

    public function __construct()
	{
		$this->start_time = microtime(true);
		parent::__construct();
		$this->load->model(array('game_logs_stream', 'ip'));
		$this->game_provider_backend_api_white_ip_list = $this->utils->getConfig('game_provider_backend_api_white_ip_list');
		$this->tester_white_ip_list = $this->utils->getConfig('game_provider_tester_white_ip_list');
		$this->remove_white_ip_list = $this->utils->getConfig('game_provider_remove_white_ip_list');
		$this->outlet_code = $this->utils->getConfig('game_provider_default_outlet_code');
		$this->outlet_name = $this->utils->getConfig('game_provider_default_outlet_name');


		$this->requestMethod = $_SERVER['REQUEST_METHOD'];

		$this->request = $this->parseRequest();

		$this->requestHeaders = $this->input->request_headers();
		
	}
    public function getTransaction(){
        $this->method = self::METHOD_GET_TRANSACTION;

		$rules = [
			'apiKey' => 'required',
			'startTime' => 'required',
			'endTime' => 'required'
		];
		
		$this->validateRequest($rules);

		$startTime = isset($this->request['startTime']) ? $this->request['startTime']  :null;
		$endTime = isset($this->request['endTime']) ? $this->request['endTime'] :null;
		$gamePlatformId = isset($this->request['gameProviderId']) ? $this->request['gameProviderId'] :null;

		# pagination
		$page = isset($this->request['pageNumber'])?(int)$this->request['pageNumber']:1;
		$defaultSizePerPage = $this->utils->getConfig('game_provider_default_row_limit');
		$limit = isset($this->request['sizePerPage']) || !empty($this->request['sizePerPage'])?(int)$this->request['sizePerPage']:$defaultSizePerPage;

		if($page<1){
			$page = 1;
		}
		$this->enable_pagination = true;

		$result = $this->game_logs_stream->queryGameLogStream($startTime, $endTime, $page, $limit, $gamePlatformId);
		
		$this->utils->debug_log("gameprovider_gamelist_service_api @getTransaction raw query", $this->db->last_query());
		$this->utils->debug_log("gameprovider_gamelist_service_api @getTransaction raw query result", $result);
		$formattedResult = [];

		if(!empty($result)){
			$index = 0;
			foreach ($result as $key => $row){
				$extra = isset($row['bet_details']) ? json_decode($row['bet_details']): null;
				$jackpotWins = [
					"JW1" => 0,
					"JW2" => 0,
					"JW3" => 0,
					"JW4" => 0,
					"JW5" => 0,
				];
				$progressiveControbutions = [
					"PC1" => 0,
					"PC2" => 0,
					"PC3" => 0,
					"PC4" => 0,
					"PC5" => 0,
				];
			
				if(isset($extra->extra->jackpot_wins) && !empty($extra->extra->jackpot_wins)){
					foreach($extra->extra->jackpot_wins as $key => $value){
						$key += 1;
						$jackpotWins['JW'.$key]  = $value;
					}
				}
				if(isset($extra->extra->progressive_contributions) && !empty($extra->extra->progressive_contributions)){
					foreach($extra->extra->progressive_contributions as $key => $value){
						$key += 1;
						$progressiveControbutions['PC'.$key]  = $value;
					}
				}

				$main_outlet = isset($row['main_outlet']) ? $row['main_outlet'] : null;

				// main outlet field in db is null
				if(!$main_outlet){
					// proceed with conditional outlet from static outlet list mapping
					$raw_outlet_code = !empty($row['outlet_code']) ? explode('_', $row['outlet_code'])[0] : null;
					$main_outlet = $this->CI->game_logs_stream->getMainOutletCode($raw_outlet_code);

					// If `getMainOutletCode (sub outlet)` returns null, check if it's a main code
					if (is_null($main_outlet) && $this->CI->game_logs_stream->isMainOutletCode($raw_outlet_code)) {
						$main_outlet = $raw_outlet_code;
					}
				}

				if($gamePlatformId != null){
					// if filtered by game, should return networkcode/affiliate_outlet_code
					$main_outlet = !empty($row['outlet_code']) ? $row['outlet_code'] : null;
				}

				$formattedResult[] = [
					"GAMEDATE" 				=> isset($row['game_date']) ? $row['game_date'] : null,
					"GAMENAME" 				=> isset($row['game_name']) ? $row['game_name'] : null,
					"GAMEPROVIDER" 			=> isset($row['game_provider']) ? $row['game_provider'] : null,
					"OUTLET" 				=> $main_outlet,
					"JW1" 					=> $jackpotWins['JW1'],
					"JW2" 					=> $jackpotWins['JW2'],
					"JW3" 					=> $jackpotWins['JW3'],
					"JW4" 					=> $jackpotWins['JW4'],
					"JW5" 					=> $jackpotWins['JW5'],
					"PC1" 					=> $progressiveControbutions['PC1'],
					"PC2" 					=> $progressiveControbutions['PC2'],
					"PC3" 					=> $progressiveControbutions['PC3'],
					"PC4" 					=> $progressiveControbutions['PC4'],
					"PC5" 					=> $progressiveControbutions['PC5'],
					"PLAYERACCOUNT" 		=> isset($row['player_username']) ? $row['player_username'] : null,
					"PLAYERTYPE" 			=> self::PLAYER_TYPE_ONLINE,
					"SESSIONID" 			=> isset($row['external_uniqueid']) ? $row['external_uniqueid'] : null,
					"TOTALSTAKES" 			=> isset($row['bet_amount']) ? $row['bet_amount'] : null,
					"TOTALWINS" 			=> isset($row['win_amount']) ? $row['win_amount'] : null,
					"TRANSACTIONID" 		=> isset($row['round_id']) ? $row['round_id'] : null,
					"UPDATEDATETIME" 		=> isset($row['updated_at']) ? $row['updated_at'] : null,
					"SETTLEMENTTIME" 		=> isset($row['end_at']) ? $row['end_at'] : null,
				];
				$index ++;
			}
		}
		$total_records = $this->game_logs_stream->queryGameLogStreamCount($startTime, $endTime, $gamePlatformId);
		if($this->enable_pagination){
			$paginationData['total_record_count'] = $total_records;
			$paginationData['total_pages'] = $total_pages = ceil($total_records / $limit);
			$paginationData['current_page'] = $page;
			$paginationData['end_page'] = $total_pages;
			$paginationData['first_page'] = 1;
			$paginationData['next_page'] = ($page+1)>$total_pages?$page:($page+1);
			$paginationData['prev_page'] = ($page>1)?$page-1:1;
		}

		$code = self::SUCCESS;
		$data =  [
			'result' => $formattedResult,
			'status' => self::STATUS_SUCCESS,
			'message' => lang("Query Successful"),
			'extra' => $paginationData
		];
		return $this->setOutput($code, $data);
    }

	private function validateRequest($rules){
		$apiKey = isset($this->request['apiKey']) ? $this->request['apiKey'] :null;
		$startTime = isset($this->request['startTime']) ? new DateTime($this->request['startTime'])  :null;
		$endTime = isset($this->request['endTime']) ? new DateTime($this->request['endTime']) :null;


		$pageNumber = isset($this->request['pageNumber']) ? $this->request['pageNumber'] :null;
		$sizePerPage = isset($this->request['sizePerPage']) ? $this->request['sizePerPage'] :null;

		$enable_ip_validation = $this->utils->getConfig("fastwin_enable_ip_validation"); //found issues with IP
		if($enable_ip_validation && !$this->validateWhiteIp()){
			$code = self::ERROR_IP_NOT_ALLOWED;
			$data =  [
				'status' => self::STATUS_FAILED,
				'message' => lang("IP Not Allowed")
			];
			return $this->setOutput($code, $data, false);
		}
	
		$this->__checkKey($apiKey);
		$this->validateParams($rules);
		$defaultSizePerPage = $this->utils->getConfig('game_provider_default_row_limit');
		if($sizePerPage > $defaultSizePerPage){
			$success = false;
			$code = self::ERROR_INTERNAL_SERVER_ERROR;
			$data =  [
				'status' => self::STATUS_FAILED,
				'message' => lang("sizePerPage should not be greater than $defaultSizePerPage")
			];
			return $this->setOutput($code, $data, $success);
		}

		//sizePerPage is not present in request body
		if($sizePerPage === null){
			$this->validateQueryDate($startTime, $endTime);
		}
		
	}

	public function validateWhiteIP(){
        $success=false;

        if(empty($this->game_provider_backend_api_white_ip_list)){
			$this->utils->debug_log("empty game_provider_backend_api_white_ip_list", $this->game_provider_backend_api_white_ip_list);
			return true;
        }

        if (!empty($this->tester_white_ip_list) && is_array($this->tester_white_ip_list)) {
			$this->game_provider_backend_api_white_ip_list = array_merge($this->game_provider_backend_api_white_ip_list, $this->tester_white_ip_list);
        }; 

        if (!empty($this->remove_white_ip_list)) {
            foreach ($this->remove_white_ip_list as $remove_ip) {
                if (in_array($remove_ip, $this->game_provider_backend_api_white_ip_list)) {
                    unset($this->game_provider_backend_api_white_ip_list[array_search($remove_ip, $this->game_provider_backend_api_white_ip_list)]);
                }
            }
        }
		$this->utils->debug_log("gameprovider_trandsaction_service_api  @validateWhiteIP tester_white_ip_list", $this->game_provider_backend_api_white_ip_list);
		$this->utils->debug_log("gameprovider_trandsaction_service_api @validateWhiteIP merged IPs:", $this->game_provider_backend_api_white_ip_list);

        $success = $this->CI->ip->checkWhiteIpListForAdmin(function ($ip, &$payload){
            $this->utils->debug_log('gameprovider_transaction_service_api @validateWhiteIP : search ip', $ip);
            if($this->CI->ip->isDefaultWhiteIP($ip)){
                $this->utils->debug_log('gameprovider_transaction_service_api @validateWhiteIP', 'it is default white ip', $ip);
                return true;
            }

            if(is_array($this->game_provider_backend_api_white_ip_list)){
                foreach ($this->game_provider_backend_api_white_ip_list as $whiteIp) {
                    if($this->utils->compareIP($ip, $whiteIp)){
                        $this->utils->debug_log('gameprovider_transaction_service_api @validateWhiteIP', 'found white ip from game_provider_backend_api_white_ip_list', $whiteIp, $ip, 
                        'game_provider_backend_api_white_ip_list', $this->game_provider_backend_api_white_ip_list);
                        //found
                        return true;
                    }
                }
            }

			$this->ip = $ip;
			$this->utils->debug_log("gameprovider_transaction_service_api @validateWhiteIP current IP:" , $this->ip);
			return false;
            //not found
        }, $payload);

        $this->utils->debug_log('gameprovider_transaction_service_api @validateWhiteIP status', $success);

		$this->utils->debug_log('gameprovider_transaction_service_api @validateWhiteIP status', $success);
        return $success;
    }


    protected function __checkKey($apiKey){
    	$validFlag = $this->isValidGameProviderApiKey($apiKey);
		if ($validFlag === false) {
			$success = false;
			$code = self::ERROR_INVALID_SIGNATURE;
			$data =  [
				'status' => self::STATUS_FAILED,
				'message' => lang("Invalid Key")
			];
			return $this->setOutput($code, $data, $success);
		}
	}

	private function validateQueryDate($from, $to) {
		$success = true;
		$code = null;
		$data = null;
	
		if ($to < $from) {
			$success = false;
			$code = self::ERROR_INTERNAL_SERVER_ERROR;
			$data = [
				'status' => self::STATUS_FAILED,
				'message' => lang("From date must be greater than to date")
			];
		} elseif ($from && $to) {
			$interval = $from->diff($to);
			$totalHours = $interval->h + ($interval->days * 24);
			$max_query_date_time_range = $this->utils->getConfig('game_provider_max_query_date_time_range');
			if ($totalHours > $max_query_date_time_range) {
				$success = false;
				$code = self::ERROR_INTERNAL_SERVER_ERROR;
				$data = [
					'status' => self::STATUS_FAILED,
					'message' => lang('Difference between startTime and endTime should not be greater than '.$max_query_date_time_range.' hours')
				];
			}
		}
	
		if($code !== null && $data !== null){
			return $this->setOutput($code, $data, $success);
		}
	}


	private function validateParams($rules)
	{
		$request = $this->request;
		foreach ($rules as $key => $rule) {
			if ($rule == 'required' && !isset($request[$key])) {
				$this->utils->error_log("gameprovider_transaction_service_api: (isValidParams) Missing Parameters: " . $key, $request, $rules);
				$success = false;
				$code = self::ERROR_INVALID_SIGNATURE;
				$data =  [
					'status' => self::STATUS_FAILED,
					'message' => lang("Incomplete parameter")
				];
				return $this->setOutput($code, $data, $success);
			}
		}
		return true;
	}
    public function parseRequest()
	{
		$request_json = file_get_contents('php://input');
		$this->utils->debug_log("gameprovider_transaction_service_api raw:", $request_json);

		$this->request = json_decode($request_json, true);

		if (!$this->request) {
			parse_str($request_json, $request_json);
			$this->utils->debug_log("gameprovider_transaction_service_api raw parsed:", $request_json);
			$this->request = $request_json;
		}
		return $this->request;
	}

    
    private function setOutput($code, $data = [], $status=true){
		$httpStatusCode = $this->getHttpStatusCode($code);
		$this->handleExternalResponse($status, $this->method, $this->request, $data, $code);
			 
		$this->output->set_content_type('application/json')
			->set_output(json_encode($data));
			// ->set_status_header($httpStatusCode);
		$this->output->_display();
		exit();
    }
    
	public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = [])
	{
		$this->utils->debug_log(
			"gameprovider_transaction_service_api (handleExternalResponse)",
			'status',
			$status,
			'type',
			$type,
			'data',
			$data,
			'response',
			$response,
			'error_code',
			$error_code,
			'fields',
			$fields
		);

		if (strpos($error_code, 'timed out') !== false) {
			$this->utils->error_log(
				__METHOD__ . "  (handleExternalResponse) Connection timed out.",
				'status',
				$status,
				'type',
				$type,
				'data',
				$data,
				'response',
				$response,
				'error_code',
				$error_code,
				'fields',
				$fields
			);
			$error_code = self::ERROR_CONNECTION_TIMED_OUT;
		}

		$httpStatusCode = $this->getHttpStatusCode($error_code);

		if ($error_code == self::SUCCESS) {
			$httpStatusCode = 200;
		}

		//add request_id
		if (empty($response)) {
			$response = [];
		}

		$cost = intval($this->utils->getExecutionTimeToNow() * 1000);
		$currentDateTime = date('Y-m-d H:i:s');
		$this->utils->debug_log("gameprovider_transaction_service_api save_response_result: $currentDateTime", ["status" => $status, "type" => $type, "data" =>  $data, "response" => $response, "http_status" => $httpStatusCode, "fields" =>  $fields, "cost" => $cost]);
		$this->utils->debug_log("gameprovider_transaction_service_api save_response: ", $response);

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$this->output->set_status_header($httpStatusCode);
		return $this->output->set_content_type('application/json')->set_output(json_encode($response));
	}

	private function saveResponseResult($success, $callMethod, $params, $response, $httpStatusCode, $statusText = null, $extra = null, $fields = [], $cost = null)
	{
		$flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
	
		$response = is_array($response) ? json_encode($response) : $response;
		$params = is_array($params) ? json_encode($params) : $params;
		
		$extra = json_encode(array_merge((array)$extra, (array)$this->requestHeaders));
		$maxAllowResponseContent = $this->utils->getConfig('max_allow_response_content');

		if (strlen($response) > $maxAllowResponseContent) {
			$response = substr($response, 0, $maxAllowResponseContent) . '... (truncated)';
			$this->utils->debug_log('Response truncated due to exceeding max allowed content size', $maxAllowResponseContent);
		}
	
		return $this->response_result->saveResponseResult(
			DUMMY_GAME_API,
			$flag,
			$callMethod,
			$params,
			$response,
			$httpStatusCode,
			$statusText,
			$extra,
			$fields,
			false,
			null,
			$cost
		);
	}

	private function getHttpStatusCode($errorCode)
	{
		$httpCode = self::HTTP_STATUS_CODE_MAP[self::ERROR_SERVER];
		foreach (self::HTTP_STATUS_CODE_MAP as $key => $value) {
			if ($errorCode == $key) {
				$httpCode = $value;
			}
		}
		return $httpCode;
	}
}