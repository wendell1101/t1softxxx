<?php

if(! defined("BASEPATH")){
    exit("No direct script access allowed");
}

require_once dirname(__FILE__) . "/BaseController.php";

/**
 * To fix undefined properties
 *
 * @property Player_model $query
 * @property Player_security_library $player_security_library
 * @property player_attached_proof_file_model $player_attached_proof_file_model
 */
class Mega_xcess_service_api extends BaseController
{
    protected $request;
    protected $requestMethod;
    protected $requestHeaders;
    protected $start_time;
	protected $validate_date_diff = false;
    public $output;
    protected $response_result_id;
    protected $method;
	public $enable_pagination = false;
	private $fastwin_backend_api_white_api_list;
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
    const METHOD_GET_PLAYERS = 'getPlayers';
    const METHOD_GET_PLAYER_TRANSACTION_SUMMARY = 'getPlayerTransactionSummary';
    const METHOD_GET_AGENCY_TRANSACTION = 'getAgencyTransaction';
    const METHOD_UPDATE_ACCOUNT_STATUS = 'updateAccountStatus';
	const METHOD_GET_PLAYER_ACTIVITY_LOGS = 'getPlayerActivityLogs';
	const METHOD_GET_AGENCY_PROMO_TRANSACTION = 'getAgencyPromoTransaction';


    const API_METHODS = [
        self::METHOD_GET_TRANSACTION,
        self::METHOD_GET_PLAYERS,
        self::METHOD_GET_PLAYER_TRANSACTION_SUMMARY,
        self::METHOD_GET_AGENCY_TRANSACTION,
        self::METHOD_UPDATE_ACCOUNT_STATUS,
        self::METHOD_GET_PLAYER_ACTIVITY_LOGS,
        self::METHOD_GET_AGENCY_PROMO_TRANSACTION
    ];

	const SKIP_VALIDATE_QUERY_DATE_METHODS = [self::METHOD_GET_PLAYER_TRANSACTION_SUMMARY];

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
		$this->load->model(array('game_logs_stream', 'ip', 'player_attached_proof_file_model'));
        $this->load->model('player_model', 'query','player');
		$this->fastwin_backend_api_white_api_list = $this->utils->getConfig('fastwin_backend_api_white_api_list') ?: [];

		$this->tester_white_ip_list = $this->utils->getConfig('fastwin_tester_white_ip_list');
		$this->remove_white_ip_list = $this->utils->getConfig('fastwin_remove_white_ip_list');
		$this->outlet_code = $this->utils->getConfig('game_provider_default_outlet_code');
		$this->outlet_name = $this->utils->getConfig('game_provider_default_outlet_name');
		$this->enable_pagination = true;


		$this->requestMethod = $_SERVER['REQUEST_METHOD'];

		$this->request = $this->parseRequest();
        $this->validate_date_diff = isset($this->request['validateDateDiff']) ? $this->request['validateDateDiff'] : false;

		$this->requestHeaders = $this->input->request_headers();
		
}

    public function index($api_method) {
        if (!in_array($api_method, self::API_METHODS)) {
            $data =  [
                'status' => self::STATUS_FAILED,
                'message' => lang('API method not allowed'),
            ];

            return $this->setOutput(self::ERROR_API_METHOD_NOT_ALLOWED, $data);
        }

        return $this->$api_method();
    }

    public function getTransaction(){
        $this->method = self::METHOD_GET_TRANSACTION;
        $this->validate_date_diff = isset($this->request['validateDateDiff']) ? $this->request['validateDateDiff'] : true;

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
				
				$winAmount = $row['result_amount'] + $row['bet_amount']; // total winning amount

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
					"PAYOUT" 				=> $winAmount,
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
		if($enable_ip_validation){
			$validatedIP = $this->validateWhiteIP();
			if(!$validatedIP){
				$code = self::ERROR_IP_NOT_ALLOWED;
				$data =  [
					'status' => self::STATUS_FAILED,
					'message' => lang("IP Not Allowed")
				];
				return $this->setOutput($code, $data, false);
			}
		}
	
		$this->__checkKey($apiKey);
		$this->validateParams($rules);

		$skipValidateQueryDate = [self::METHOD_GET_PLAYER_TRANSACTION_SUMMARY];

		if(!in_array($this->method, self::SKIP_VALIDATE_QUERY_DATE_METHODS)){
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
				$this->validateQueryDate($startTime, $endTime, $this->validate_date_diff);
			}
		}
		
		
	}

	public function validateWhiteIP(){
        $success=false;

        $this->CI->load->model(['ip']);

        if(empty($this->fastwin_backend_api_white_api_list)){
            return true;
        }

        $success=$this->CI->ip->checkWhiteIpListForAdmin(function ($ip, &$payload){
            $this->utils->debug_log('search ip', $ip);
            if (!$this->skip_default_white_ip_list_validation) {
                if($this->CI->ip->isDefaultWhiteIP($ip)){
                    $this->utils->debug_log('validateWhiteIP', 'it is default white ip', $ip);
                    // return true;
    
                    $default_white_ip_list = $this->utils->getConfig('default_white_ip_list');
                    $this->fastwin_backend_api_white_api_list = array_merge($this->fastwin_backend_api_white_api_list, $default_white_ip_list);
                }
            }

            if(is_array($this->fastwin_backend_api_white_api_list)){
                if (!empty($this->tester_white_ip_list) && is_array($this->tester_white_ip_list)) {
                    $this->fastwin_backend_api_white_api_list = array_merge($this->fastwin_backend_api_white_api_list, $this->tester_white_ip_list);
                }

                if (!empty($this->remove_white_ip_list)) {
                    foreach ($this->remove_white_ip_list as $remove_ip) {
                        if (in_array($remove_ip, $this->fastwin_backend_api_white_api_list)) {
                            unset($this->fastwin_backend_api_white_api_list[array_search($remove_ip, $this->fastwin_backend_api_white_api_list)]);
                        }
                    }
                }

                foreach ($this->fastwin_backend_api_white_api_list as $whiteIp) {
                    if($this->utils->compareIP($ip, $whiteIp)){
                        $this->utils->debug_log('found white ip', $whiteIp, $ip);
                        //found
                        return true;
                    }
                }
            }
            //not found
            return false;
        }, $payload);


        $this->utils->debug_log('validateWhiteIP status', $success);
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

	private function validateQueryDate($from, $to, $validate_date_diff = false) {
		$success = true;
		$code = null;
		$data = null;

        if (!is_object($from)) {
            $from = new DateTime($from);
        }

        if (!is_object($to)) {
            $to = new DateTime($to);
        }
	
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
			if ($totalHours > $max_query_date_time_range && $validate_date_diff) {
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
				$this->utils->error_log("mega_xcess_service_api: (isValidParams) Missing Parameters: " . $key, $request, $rules);
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
		$this->utils->debug_log("mega_xcess_service_api raw:", $request_json);

		$this->request = json_decode($request_json, true);

		if (!$this->request) {
			parse_str($request_json, $request_json);
			$this->utils->debug_log("mega_xcess_service_api raw parsed:", $request_json);
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
			"mega_xcess_service_api (handleExternalResponse)",
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
		$this->utils->debug_log("mega_xcess_service_api save_response_result: $currentDateTime", ["status" => $status, "type" => $type, "data" =>  $data, "response" => $response, "http_status" => $httpStatusCode, "fields" =>  $fields, "cost" => $cost]);
		$this->utils->debug_log("mega_xcess_service_api save_response: ", $response);

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

    public function getPlayers() {
		$this->CI->load->model(['player_model']);
		$this->load->library(['player_security_library']);
        $code = self::SUCCESS;
        $players = $player = [];

        $rules = [
            'apiKey' => 'required',
            'registerDateFrom' => 'required',
            'registerDateTo' => 'required',
        ];

        $this->validateParams($rules);

        $apiKey = isset($this->request['apiKey']) ? $this->request['apiKey'] :null;
        $registerDateFrom = isset($this->request['registerDateFrom']) ? $this->request['registerDateFrom']  :null;
        $registerDateTo = isset($this->request['registerDateTo']) ? $this->request['registerDateTo']  :null;
        $username = isset($this->request['username']) ? $this->request['username']  :null;

        $this->__checkKey($apiKey);
        $this->validateQueryDate($registerDateFrom, $registerDateTo, $this->validate_date_diff);
		# pagination
		$page = isset($this->request['pageNumber'])?(int)$this->request['pageNumber']:1;
		$defaultSizePerPage = $this->utils->getConfig('game_provider_default_row_limit');
		$limit = isset($this->request['sizePerPage']) || !empty($this->request['sizePerPage'])?(int)$this->request['sizePerPage']:$defaultSizePerPage;

		if($page<1){
			$page = 1;
		}
		$extra = [];
		if(!empty($username)){
			$extra['username'] = $username;
		}
		
		$results = $this->game_logs_stream->getPlayers($registerDateFrom,$registerDateTo, $page, $limit, $extra);

        // rebuild data
        if (!empty($results) && is_array($results)) {
            foreach ($results as $result) {
                $player_proof_files = $this->player_attached_proof_file_model->getPlayerProofFileAttachments($result['playerId']);
                $uploadedIdPicture = [];
                if (!empty($player_proof_files['photo_id']) && is_array($player_proof_files['photo_id'])) {
                    foreach ($player_proof_files['photo_id'] as $photo_id) {
                        array_push($uploadedIdPicture, [
                            'image_url' => $photo_id['image_url'],
                            'created_at' => $photo_id['created_at'],
                        ]);
                    }
                }

                $selfieHoldingIdPicture = [];
                if (!empty($player_proof_files['income']) && is_array($player_proof_files['income'])) {
                    foreach ($player_proof_files['income'] as $income) {
                        array_push($selfieHoldingIdPicture, [
                            'image_url' => $income['image_url'],
                            'created_at' => $income['created_at'],
                        ]);
                    }
                }

				$player_account_status = $this->utils->getPlayerStatus($result['playerId']);
				
                $player['username'] = $result['username'];
                $player['firstName'] = $result['firstName'];
                $player['middleName'] = $result['middleName'];
                $player['lastName'] = $result['lastName'];
                $player['dateOfBirth'] = $result['birthdate'];
                $player['registrationDate'] = $result['createdOn'];
				$player['accountStatus'] = $player_account_status != 0 ? 'blocked' : 'normal' ;
                $player['blockReason'] = $this->getPlayerBlockReason($player_account_status, $result);
                $player['contactNo'] = $result['contactNumber'];
                $player['natureOfWork'] = $result['natureWork'];
                $player['sourceOfIncome'] = $result['sourceIncome'];
                $player['agencyCode'] = $result['storeCode'];
                $player['placeOfBirth'] = $result['birthplace'];
                $player['country'] = !empty($result['country']) ? $result['country'] : $result['citizenship'];
                $player['address'] = $result['address'];
                $player['uploadedIdPicture'] = $uploadedIdPicture;
                $player['selfieHoldingIdPicture'] = $selfieHoldingIdPicture;
                $player['kycStatus'] = $this->player_security_library->getPlayerKYCStatus($result['playerId']);
				$player['balance'] = $this->CI->player_model->getMainWalletBalance($result['playerId']);
				$player['lastPlayedDate'] = $this->game_logs_stream->getLastPlayedDateByPlayerId($result['playerId']);
                array_push($players, $player);
            }
        }

		$total_records = $this->game_logs_stream->getPlayersCount($registerDateFrom, $registerDateTo);
		if($this->enable_pagination){
			$paginationData['total_record_count'] = $total_records;
			$paginationData['total_pages'] = $total_pages = ceil($total_records / $limit);
			$paginationData['current_page'] = $page;
			$paginationData['end_page'] = $total_pages;
			$paginationData['first_page'] = 1;
			$paginationData['next_page'] = ($page+1)>$total_pages?$page:($page+1);
			$paginationData['prev_page'] = ($page>1)?$page-1:1;
		}

        $data =  [
            'status' => self::STATUS_SUCCESS,
            'message' => lang('Query successful'),
            'result' => $players,
			'extra' => $paginationData
        ];

        return $this->setOutput($code, $data);
    }

	public function updateAccountStatus() {
		$this->load->model('users');
        $code = self::SUCCESS;
		$success = false;
		$types = [
			'block',
			'unblock'
		];

        $rules = [
            'apiKey' => 'required',
            'username' => 'required',
            'type' => 'required',
            'remarks' => 'required',
            'cashierLogin' => 'required',
        ];

        $this->validateParams($rules);

        $apiKey = isset($this->request['apiKey']) ? $this->request['apiKey'] :null;
        $username = isset($this->request['username']) ? $this->request['username'] :null;
        $type = isset($this->request['type']) ? $this->request['type']  :null;
        $remarks = isset($this->request['remarks']) ? $this->request['remarks']  :null;
        $cashierLogin = isset($this->request['cashierLogin']) ? $this->request['cashierLogin']  :null;

        $this->__checkKey($apiKey);
	
		$datetime = new DateTime('now');
		$player_id = $this->CI->player_model->getPlayerIdByUsername($this->request['username']);

		#validate username
		if(empty($player_id)){
			$data =  [
                'status' => self::STATUS_FAILED,
                'message' => lang('Username not found'),
            ];
            return $this->setOutput(self::ERROR_INVALID_REQUEST, $data);
		}

		#validate type only accepts "block" and "unblock"
		if(!in_array($type, $types)){
			$data =  [
                'status' => self::STATUS_FAILED,
                'message' => lang('Invalid parameter type'),
            ];
            return $this->setOutput(self::ERROR_INVALID_REQUEST, $data);
		}

		#validate cashierLogin as sbe admin account
		if(!$this->CI->users->isUserExist($cashierLogin)){
			$data =  [
                'status' => self::STATUS_FAILED,
                'message' => lang('cashierLogin user not found'),
            ];
            return $this->setOutput(self::ERROR_INVALID_REQUEST, $data);
		}

		if($type == 'block'){
			#validate if player already blocked
			if($this->isPlayerBlock($player_id)){
				$data =  [
					'status' => self::STATUS_FAILED,
					'message' => lang('Account already blocked'),
				];
				return $this->setOutput(self::ERROR_INVALID_REQUEST, $data);
			}else{
				$success = $this->blockPlayer($player_id, $username, $remarks, $cashierLogin);
			}

			if(!$success){
				$data =  [
					'status' => self::STATUS_FAILED,
					'message' => lang('Blocking failed'),
				];
	
				return $this->setOutput(self::ERROR_SERVER, $data);
			}
		}else if($type == 'unblock'){
			#validate if player already unblocked
			if(!$this->isPlayerBlock($player_id)){
				$data =  [
					'status' => self::STATUS_FAILED,
					'message' => lang('Account already unblocked'),
				];
				return $this->setOutput(self::ERROR_INVALID_REQUEST, $data);
			}else{
				// $success = $this->CI->player_model->unblockPlayerById($player_id);
				$this->unblockPlayer($player_id);
				$success = true;
			}
		}
		
		if($success){
			$results = [
				'username' => $username,
				'datetime' => $datetime->format('Y-m-d H:i:s')
			];
		}

        $data =  [
            'status' => self::STATUS_SUCCESS,
            'message' => lang('Query successful'),
            'result' => !empty($results) ? $results : [],
        ];

        return $this->setOutput($code, $data);
    }

	public function isPlayerBlock($player_id){
		return $this->utils->getPlayerStatus($player_id) != 0 ? true : false;
	}

	public function saveBlockPlayer($data){
		return $this->db->insert('blocked_players', $data);
	}

	#this functions deletes player from blocked_players table
	public function removePlayerFromBlockedPlayerTable($player_id){
		$this->db->where('player_id', $player_id);
		return $this->db->delete('blocked_players');
	}

	public function unblockPlayer($playerId){
		$this->load->model('player_model', 'player', 'operatorglobalsettings');
		$updateData = [];
		$updateData['blocked'] = 0;
		$updateData['blocked_status_last_update'] = $this->utils->getNowForMysql();
		$this->CI->player_model->updatePlayer($playerId, $updateData);

		$tagged = $this->player->getPlayerTags($playerId);
		$blockedPlayerTag = json_decode($this->operatorglobalsettings->getSettingJson('blocked_player_tag'));
		$totalWrongLoginAttempt = $this->CI->player_model->getPlayerTotalWrongLoginAttempt($playerId);

		if($this->operatorglobalsettings->getSettingBooleanValue('player_login_failed_attempt_blocked') && ($totalWrongLoginAttempt != null) ) {
			if((int)$totalWrongLoginAttempt >= $this->operatorglobalsettings->getSettingIntValue('player_login_failed_attempt_times')){
				$this->CI->player_model->updatePlayerTotalWrongLoginAttempt($playerId);
			}
		}

		if (!empty($tagged) && !empty($blockedPlayerTag)) {
			foreach ($tagged as $playerTag) {
				if (in_array($playerTag['tagId'], (array) $blockedPlayerTag)) {
					$this->player->removePlayerTag($playerTag['playerTagId']);
				}
			}
		}

		$changes = 'Block/UnBlock Player in MXAPI - ' .
		lang('adjustmenthistory.title.beforeadjustment') . ' (' . lang('tool.pm08') . ') ' .
		lang('adjustmenthistory.title.afteradjustment') . ' (' . lang('tool.pm09') . ') ';

		$data = array(
			'playerId' => $playerId,
			'changes' => $changes,
			'createdOn' => date('Y-m-d H:i:s'),
			'operator' => $this->request['cashierLogin'],
		);

		$this->CI->player->addPlayerInfoUpdates($playerId, $data);
		$this->saveAction('Player Management', lang('member.log.unblock.website'), "User " . $this->request['cashierLogin'] . " has adjusted player '" . $playerId . "'");
		$this->removePlayerFromBlockedPlayerTable($playerId);
		return true;
	}	

	public function blockPlayer($playerId, $username, $remarks, $cashierLogin){
		$changes = 'Block/UnBlock Player in MXAPI - ' .
		lang('adjustmenthistory.title.beforeadjustment') . ' (' . lang('tool.pm09') . ') ' .
		lang('adjustmenthistory.title.afteradjustment') . ' (' . lang('tool.pm08') . ') ';

		$data = array(
			'playerId' => $playerId,
			'changes' => $changes,
			'createdOn' => date('Y-m-d H:i:s'),
			'operator' => $this->request['cashierLogin'],
		);

		$this->CI->player->addPlayerInfoUpdates($playerId, $data);
		$this->saveAction('Player Management', lang('member.log.block.website'), "User " . $this->request['cashierLogin'] . " has adjusted player '" . $playerId . "'");

		$admin_id = $this->CI->users->getIdByUsername($cashierLogin);

		#saves date in blocked_players table
		$save_data = array(
			'player_id' => $playerId,
			'player_username' => $username,
			'reason' => $remarks,
			'blocked_by_admin_id' => $admin_id,
			'blocked_by_admin_username' => $cashierLogin
		);
		
		$this->saveBlockPlayer($save_data);


		return $this->CI->player_model->blockPlayerWithoutGame($playerId);
	}

	public function getPlayerBlockReason($playerStatus, $data){
		if($playerStatus == Player_model::BLOCK_STATUS){
			return $data['playerBlockReason'] ?: $data['playerBlockReasonFromTag'];
		}else if ($playerStatus == Player_model::STATUS_BLOCKED_FAILED_LOGIN_ATTEMPT){
			return lang('Failed Login Attempt');
		}else{
			return null;
		}
	}

	public function getPlayerTransactionSummary(){
        $this->method = self::METHOD_GET_PLAYER_TRANSACTION_SUMMARY;

		$rules = [
			'apiKey' => 'required',
			'startTime' => 'required',
			'endTime' => 'required'
		];
		
		$this->validateRequest($rules);

		$startTime = isset($this->request['startTime']) ? $this->request['startTime']  :null;
		$endTime = isset($this->request['endTime']) ? $this->request['endTime'] :null;
	

		$result = $this->game_logs_stream->queryPlayerTransactionsSummaryByOutlet($startTime, $endTime);

		$this->utils->debug_log("gameprovider_gamelist_service_api @getTransaction raw query", $this->db->last_query());
		$this->utils->debug_log("gameprovider_gamelist_service_api @getTransaction raw query result", $result);

		$extra=[];
		$data = [];

		foreach($result as $item){
			$data[] =  [
				"agencyCode" 				=> isset($item['main_outlet']) ? $item['main_outlet'] : null,
				"totalDeposit" 				=> isset($item['total_deposit_amount']) ? $item['total_deposit_amount'] : null,
				"totalWithdrawal" 			=> isset($item['total_withdraw_amount']) ? $item['total_withdraw_amount'] : null,
				"totalGH"					=> isset($item['total_gh']) ? $item['total_gh'] : null,
			];
		}
		$code = self::SUCCESS;
		return $this->setOutput($code, $data);
    }

	public function getAgencyTransaction(){
        $this->method = self::METHOD_GET_AGENCY_TRANSACTION;
        $this->validate_date_diff = isset($this->request['validateDateDiff']) ? $this->request['validateDateDiff'] : true;

		$rules = [
			'apiKey' => 'required',
			'startTime' => 'required',
			'endTime' => 'required'
		];
		
		$this->validateRequest($rules);

		$startTime = isset($this->request['startTime']) ? $this->request['startTime']  :null;
		$endTime = isset($this->request['endTime']) ? $this->request['endTime'] :null;

		# pagination
		$page = isset($this->request['pageNumber'])?(int)$this->request['pageNumber']:1;
		$defaultSizePerPage = $this->utils->getConfig('game_provider_default_row_limit');
		$limit = isset($this->request['sizePerPage']) || !empty($this->request['sizePerPage'])?(int)$this->request['sizePerPage']:$defaultSizePerPage;

		if($page<1){
			$page = 1;
		}

		$result = $this->game_logs_stream->queryTransactionsWithAgency($startTime, $endTime, $page, $limit);
		
		$this->utils->debug_log("mega_xcess_service_api @queryTransactionsWithAgency raw query", $this->db->last_query());
		$this->utils->debug_log("mega_xcess_service_api @queryTransactionsWithAgency raw query result", $result);
		$data = [];

		if(!empty($result)){
			$index = 0;
			foreach ($result as $item){
				$paymentAccountName = isset($item['payment_account_name']) ? $item['payment_account_name'] : null;

				if($item['transaction_type'] == 2){
					$langId = $this->CI->language_function->getCurrentLanguage();
					$langCode = $this->CI->language_function->getCurrentLangForPromo(true,$langId);
					$paymentAccountName = $this->utils->extractLangJson($paymentAccountName)[$langCode] ?: null;
				}
				$tempArr = [
					'CashierLogin' => isset($item['networkcode']) ? $item['networkcode'] : null,
					'AccountName' => isset($item['username']) ? $item['username'] : null,
					'DateTransaction' => isset($item['created_at']) ? $item['created_at'] : null,
					'TransactionAmount' => isset($item['amount']) ? $this->utils->dBtoGameAmount($item['amount'],2) : null,
					'OutletName' => isset($item['main_outlet']) ? $item['main_outlet'] : null,
					'TransactionID' => isset($item['trans_id']) ? $item['trans_id'] : null,
					'PaymentAccountName' => $paymentAccountName,
					'BeforeBalance' =>  isset($item['before_balance']) ? $this->utils->dBtoGameAmount($item['before_balance'],2) : null,
				];

				if($item['transaction_type'] == 1){
					$tempArr['IsCashIn'] = 1;
				}
				if($item['transaction_type'] == 2){
					$tempArr['IsCashIn'] = 0;
				}
				$data[]  = $tempArr;
			}
		}
		$total_records = $this->game_logs_stream->queryTransactionsWithAgencyCount($startTime, $endTime);
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
			'result' => $data,
			'status' => self::STATUS_SUCCESS,
			'message' => lang("Query Successful"),
			'extra' => $paginationData
		];
		return $this->setOutput($code, $data);
    }

	public function getPlayerActivityLogs(){
        $this->method = self::METHOD_GET_PLAYER_ACTIVITY_LOGS;
		$this->load->model(['player_activity_stream']);
        $this->validate_date_diff = isset($this->request['validateDateDiff']) ? $this->request['validateDateDiff'] : true;

		$rules = [
			'apiKey' => 'required',
			'startTime' => 'required',
			'endTime' => 'required'
		];
		
		$this->validateRequest($rules);

		$startTime = isset($this->request['startTime']) ? $this->request['startTime']  :null;
		$endTime = isset($this->request['endTime']) ? $this->request['endTime'] :null;

		# pagination
		$page = isset($this->request['pageNumber'])?(int)$this->request['pageNumber']:1;
		$defaultSizePerPage = $this->utils->getConfig('game_provider_default_row_limit');
		$limit = isset($this->request['sizePerPage']) || !empty($this->request['sizePerPage'])?(int)$this->request['sizePerPage']:$defaultSizePerPage;

		if($page<1){
			$page = 1;
		}

		$result = $this->player_activity_stream->queryPlayerActivityLogsPaginated($startTime, $endTime, $page, $limit);
		$this->utils->debug_log("mega_xcess_service_api @getPlayerActivityLogs raw query", $this->db->last_query());
		$this->utils->debug_log("mega_xcess_service_api @getPlayerActivityLogs raw query result", $result);
		$data = [];
		
		if(!empty($result)){
			$index = 0;
			foreach ($result as $item){
				$statusTemp = isset($item['status']) ? $item['status'] : null;
				$status = $statusTemp == 1 ? 'Success' : 'Failed';
				$tempArr = [
					'PlayerUsername' => isset($item['username']) ? $item['username'] : null,
					'DateTime' => isset($item['created_at']) ? $item['created_at'] : null,
					'Activity' => isset($item['player_activity_action_type']) ? $item['player_activity_action_type'] : null,
					'ActivityDetails' => [
						// 'domain' => isset($item['domain']) ? $item['domain'] : null,
						'ip' => isset($item['client_ip']) ? $item['client_ip'] : null,
						// 'http_status_code' => isset($item['http_status_code']) ? $item['http_status_code'] : null,
						'extra_info' => isset($item['extra_info']) ? json_decode($item['extra_info']) : null
					],
					'Device' => isset($item['device_type']) ? $item['device_type'] : null,
					'SessionID' => isset($item['request_id']) ? $item['request_id'] : null,
					'Status' => $status
				];
				$data[]  = $tempArr;
			}
		}
		$total_records = $this->player_activity_stream->queryPlayerActivityLogsPaginatedTotalCount($startTime, $endTime);
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
			'result' => $data,
			'status' => self::STATUS_SUCCESS,
			'message' => lang("Query Successful"),
			'extra' => $paginationData
		];
		return $this->setOutput($code, $data);
    }

	public function getAgencyPromoTransaction(){
        $this->method = self::METHOD_GET_AGENCY_PROMO_TRANSACTION;
        $this->validate_date_diff = isset($this->request['validateDateDiff']) ? $this->request['validateDateDiff'] : true;
		$extra =[];
		$rules = [
			'apiKey' => 'required',
			'startTime' => 'required',
			'endTime' => 'required'
		];
		
		$this->validateRequest($rules);

		$startTime = isset($this->request['startTime']) ? $this->request['startTime']  :null;
		$endTime = isset($this->request['endTime']) ? $this->request['endTime'] :null;
		$username = isset($this->request['username']) ? $this->request['username']  :null; //optional
		
		if(!empty($username)){
			$extra['username'] = $username;
		}

		# pagination
		$page = isset($this->request['pageNumber'])?(int)$this->request['pageNumber']:1;
		$defaultSizePerPage = $this->utils->getConfig('game_provider_default_row_limit');
		$limit = isset($this->request['sizePerPage']) || !empty($this->request['sizePerPage'])?(int)$this->request['sizePerPage']:$defaultSizePerPage;

		if($page<1){
			$page = 1;
		}

		$result = $this->game_logs_stream->queryPromoTransactionsWithAgency($startTime, $endTime, $page, $limit, $extra);
		
		$this->utils->debug_log("mega_xcess_service_api @getAgencyPromoTransaction raw query", $this->db->last_query());
		$this->utils->debug_log("mega_xcess_service_api @getAgencyPromoTransaction raw query result", $result);
		$data = [];

		if(!empty($result)){
			$index = 0;
			foreach ($result as $item){
				$transId = isset($item['trans_id']) ? $item['trans_id'] : null;
				$prefix=isset($item['promo_code']) ? $item['promo_code'] : null;
				$promotionName = isset($item['promoName']) ? $item['promoName'] : null;
			
				$tempArr = [
					'CashierLogin' => isset($item['networkcode']) ? $item['networkcode'] : null,
					'AccountName' => isset($item['username']) ? $item['username'] : null,
					'DateTransaction' => isset($item['created_at']) ? $item['created_at'] : null,
					'TransactionAmount' => isset($item['amount']) ? $this->utils->dBtoGameAmount($item['amount'],2) : null,
					'OutletName' => isset($item['main_outlet']) ? $item['main_outlet'] : null,
					'TransactionID' => $prefix.'-'.$transId,
					'PromotionName' => $promotionName,
					'isCashIn'		=> 1
					// 'BeforeBalance' =>  isset($item['before_balance']) ? $this->utils->dBtoGameAmount($item['before_balance'],2) : null,
				];
				$data[]  = $tempArr;
			}
		}
		$total_records = $this->game_logs_stream->queryPromoTransactionsWithAgencyCount($startTime, $endTime, $extra);
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
			'result' => $data,
			'status' => self::STATUS_SUCCESS,
			'message' => lang("Query Successful"),
			'extra' => $paginationData
		];
		return $this->setOutput($code, $data);
    }
}