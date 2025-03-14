<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Flow_gaming_service_api extends BaseController {

	const ENDPOINT_COMMON = 'common';
	const ENDPOINT_AUTH = 'auth';
	const ENDPOINT_BALANCE = 'balance';
	const ENDPOINT_TRANSACTION = 'transaction';
	const ENDPOINT_ENDROUND = 'endround';
	const ENDPOINT_DEFAULT = 'default';
	const ENDPOINT_REDIRECTOR = 'redirect';
	const ENDPOINT_PUSHFEED = 'push_feed';

	const TRANSACTION_WAGER = 'WAGER';
	const TRANSACTION_WAGER_INVALID = 'WAGER_INVALID';
	const TRANSACTION_PAYOUT = 'PAYOUT';
	const TRANSACTION_PAYOUT_INVALID = 'PAYOUT_INVALID';
	const TRANSACTION_REFUND = 'REFUND';
	const TRANSACTION_REFUND_INVALID = 'REFUND_INVALID';

	const LOGS_REFUND = 'refunded';
	const LOGS_SETTLE_API_REQUEST = 'settled_api_request';
	const LOGS_SETTLE_API_RESPONSE = 'settled_api_response';
	const LOGS_REFUND_API_RESPONSE = 'refunded_api_response';

	const STATUS_CODES = [
		'ERROR' => 500,
		'SUCCESS' => 200,
		'INVALID_TOKEN' => 401,
		'INVALID_FORMAT' => 400,
		'INSUFFICIENT_BALANCE' => 402,
		'DUPLICATE_TRANSACTION' => 409,
        'IP_NOT_ALLOWED' => 401,
	];

	const GAME_LOGS = 'game';
	const TRANSACTION_LOGS = 'transaction';

	const SUCCESS = true;
	const FAIL = false;

	const ERROR_MESSAGE = array(
		'duplicate_entry'   	     		=> 'Duplicate transaction entry',
		'insufficient_balance'       		=> 'Insufficient balance',
		'internal_error' 			 		=> 'Internal Error',
		'internal_error_balance_adjustment' => 'Internal Error upon Balance Adjustment',
		'wager_not_found'   		 		=> 'Internal Error, Wager not found',
        'ip_not_allowed'   		 		    => 'IP Address is not allowed.',
        'already_refunded'   		 		=> 'Already refunded.',
		'already_settled'   		 		=> 'Already settled.',
		'game_error'						=> 'The game platform is disabled or under maintenance.',
	);

	const API_REQUEST = 'api_request_';
	const ENDPOINT_RESPONSE = 'endpoint_response_';
	const ENDPOINT_ADD_SUBWALLET = 'endpoint_add_subwallet';
	const ENDPOINT_SUBTRACT_SUBWALLET = 'endpoint_subtract_subwallet';

    private $transaction_for_fast_track = null;

	function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','original_game_logs_model'));
		$this->game_api = null;
		$this->status_code = null;
		$this->is_error_wager = false;
		$this->refund_in_process = false;
	}

	private function init($game_platform_id) {
		$this->game_api 					 = $this->utils->loadExternalSystemLibObject($game_platform_id);
		$this->gamePlatformId 				 = $game_platform_id;
		$this->original_game_logs_table 	 = $this->game_api->original_gamelogs_table;
		$this->transaction_game_logs_table 	 = $this->game_api->original_transaction_table;
		$this->use_insert_ignore_transaction = $this->game_api->use_insert_ignore_transaction;
		$this->use_old_insert_transaction 	 = $this->game_api->use_old_insert_transaction;
	}

	public function index($game_platform_id, $version = 'v1', $end_point) {
		$this->init($game_platform_id);
		$postJson = file_get_contents("php://input");
		$postData = !empty($postJson) ? json_decode($postJson,true) : array();
		$this->utils->debug_log("FLOW GAMING SEAMLESS RAW REQUEST ====> ", $postJson, "URL ====> ", $_SERVER['REQUEST_URI']);
		$this->api_url = $_SERVER['REQUEST_URI'];

		if ($this->external_system->isGameApiActive($game_platform_id) && !$this->external_system->isGameApiMaintenance($game_platform_id)) {
			// $postJson = file_get_contents("php://input");
			// $postData = !empty($postJson) ? json_decode($postJson,true) : array();

			// $this->utils->debug_log("FLOW GAMING SEAMLESS RAW REQUEST ====> ", $postJson, "URL ====> ", $_SERVER['REQUEST_URI']);

			// $this->api_url = $_SERVER['REQUEST_URI'];

            if (!$this->game_api->validateWhiteIP()) {
                $start_time = strtotime(date('Y-m-d H:i:s'));
                $this->status_code = self::STATUS_CODES['IP_NOT_ALLOWED'];
                $response = $this->returnErrorResponse(null, $start_time, null, self::ERROR_MESSAGE['ip_not_allowed'], self::API_REQUEST . self::ENDPOINT_DEFAULT, null, $this->status_code, null);
                $this->game_api->saveToResponseResult(self::FAIL, 'validateWhiteIP', $postData, $response, [], self::STATUS_CODES['IP_NOT_ALLOWED']);

                $this->utils->debug_log("FLOW_GAMING_SEAMLESS RESPONSE_IP_WHITELIST", $response);

                return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($response['api_response']));
            }

			switch ($end_point) {
				case self::ENDPOINT_COMMON:
					$this->processCommon($postData);
					break;
				case self::ENDPOINT_AUTH:
					$result = $this->processAuth($postData);
					return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($result));
					break;
				case self::ENDPOINT_BALANCE:
					$result = $this->processBalance($postData);
					return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($result));
					break;
				case self::ENDPOINT_TRANSACTION:
					$result = $this->processTransaction($postData);
                    if($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration') && $result) {
                        $this->sendToFastTrack();
                    }
					return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($result));
					break;
				case self::ENDPOINT_ENDROUND:
					$result = $this->processEndround($postData);
					return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($result));
					break;
				case self::ENDPOINT_PUSHFEED:
					$result = $this->processPushFeed($postData);
                    if($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration') && $result) {
                        $this->sendToFastTrack();
                    }
					return $this->output->set_status_header($this->status_code);
					break;
				case self::ENDPOINT_REDIRECTOR:
					$postData = $this->CI->input->get();
					$result = $this->processRedirector($postData);
					break;
				default:
					$this->utils->debug_log("FLOW_GAMING_SEAMLESS ENDPOINT_ERROR ====> ", $end_point);
					$this->game_api->saveToResponseResult(self::FAIL, self::API_REQUEST . self::ENDPOINT_DEFAULT, $postData, self::API_REQUEST . self::ENDPOINT_DEFAULT);
					break;
			}
		} else {
			$start_time = strtotime(date('Y-m-d H:i:s'));
            $this->status_code = self::STATUS_CODES['ERROR'];
            $response = $this->returnErrorResponse(null, $start_time, null, self::ERROR_MESSAGE['game_error'], self::API_REQUEST . self::ENDPOINT_DEFAULT, null, $this->status_code, null);
            $this->game_api->saveToResponseResult(self::FAIL, self::API_REQUEST . self::ENDPOINT_DEFAULT, $postData, $response, [], self::STATUS_CODES['ERROR']);
            $this->utils->debug_log("FLOW_GAMING_SEAMLESS GAME NOT ACTIVE OR ON MAINTENANCE", $response);
            return $this->output->set_status_header($this->status_code)->set_content_type('application/json')->set_output(json_encode($response['api_response']));
		}

	}

	private function processCommon($data) {
		$this->utils->debug_log("FLOW_GAMING_SEAMLESS ENDPOINT_COMMON ====> ", $data);
		$this->game_api->saveToResponseResult(self::SUCCESS, self::API_REQUEST . self::ENDPOINT_COMMON, $data, self::API_REQUEST . self::ENDPOINT_COMMON);
	}

	private function processRedirector($data) {
		$this->utils->debug_log("FLOW_GAMING_SEAMLESS ENDPOINT_REDIRECTOR ====> ", $data);
		$this->game_api->saveToResponseResult(self::SUCCESS, self::API_REQUEST . self::ENDPOINT_REDIRECTOR, $data, self::API_REQUEST . self::ENDPOINT_REDIRECTOR);
		redirect($this->game_api->mobile_lobby_url);
	}

	public function processAuth($data = null) {
		$this->utils->debug_log("FLOW_GAMING_SEAMLESS ENDPOINT_AUTH ====> ", $data);
		$start_time = strtotime(date('Y-m-d H:i:s'));
		$apiResult = $this->game_api->processVerifyToken($data);
		$end_time = strtotime(date('Y-m-d H:i:s'));
        $processing_time = $end_time - $start_time;
		$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);

		if ($apiResult['success']) {
			$this->status_code = self::STATUS_CODES['SUCCESS'];
			$responseData = [
        		'req_id'    	  => $apiResult['data']['req_id'],
        		'processing_time' => $elapsed_time,
            	'token'     	  => $apiResult['data']['token'],
            	'username'  	  => $apiResult['data']['username'],
            	'account_ext_ref' => $apiResult['data']['account_ext_ref'],
            	'balance'  		  => $this->game_api->roundDownAmount($apiResult['data']['balance']),
            	'currency' 		  => $apiResult['data']['currency'],
            	'country'  	 	  => $apiResult['data']['country'],
            	// 'lang'     	  	  => $apiResult['data']['lang'],
            	'timestamp'	      => $apiResult['data']['timestamp']
            ];

            $response_result['request_data'] = $data;
            $response_result['verify_token'] = $apiResult;
            $response_result['response'] = $responseData;
			$this->utils->debug_log("FLOW_GAMING_SEAMLESS RESPONSE_AUTH =====>req_id=" . $data['req_id'], $apiResult['data']);
			$this->game_api->saveToResponseResult(self::SUCCESS, self::ENDPOINT_RESPONSE . self::ENDPOINT_AUTH, $response_result, self::ENDPOINT_RESPONSE . self::ENDPOINT_AUTH);
			return $responseData;
		}
		$this->status_code = self::STATUS_CODES['INVALID_TOKEN'];
		$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
		$apiResult['data']['processing_time'] = $elapsed_time;

		$response_result = [
			'request'  => $data,
			'response' => $apiResult['data']
		];
		$this->game_api->saveToResponseResult(self::SUCCESS, self::ENDPOINT_RESPONSE . self::ENDPOINT_AUTH, $response_result, self::ENDPOINT_RESPONSE . self::ENDPOINT_AUTH);

		$this->utils->debug_log("FLOW_GAMING_SEAMLESS RESPONSE_AUTH =====>req_id=" . $data['req_id'], $apiResult['data']);
		return $apiResult['data'];
	}

	private function processBalance($data) {
		$this->utils->debug_log("FLOW_GAMING_SEAMLESS ENDPOINT_BALANCE ====> ", $data);
		$start_time = strtotime(date('Y-m-d H:i:s'));
		$this->response = '';

		$response_result = [
			'code'     => self::FAIL,
			'api_name' => self::ENDPOINT_RESPONSE . self::ENDPOINT_BALANCE,
			'body'	   => [],
			'response' => self::ENDPOINT_RESPONSE . self::ENDPOINT_BALANCE
		];

		$apiResult = $this->game_api->processVerifyToken($data);
		$this->status_code = self::STATUS_CODES['ERROR'];

		if ($apiResult['success']) {
			$player_id = $apiResult['data']['player_id'];
	        $success = $this->lockAndTransForPlayerBalance($player_id,function() use($apiResult, $start_time, &$response_result, $data) {
				$this->status_code = self::STATUS_CODES['SUCCESS'];
				$responseData = [
					'req_id'          => $apiResult['data']['req_id'],
					'processing_time' => "",
					'token'			  => $apiResult['data']['token'],
					'balance'		  => $this->game_api->roundDownAmount($apiResult['data']['balance'])
				];

	            $responseResult = [
	            	'api_url'      => $this->api_url,
	            	'request_data' => $data,
	            	'verify_token' => $apiResult,
	            	'response'	   => $responseData
	            ];

		        $response_result['code'] = self::SUCCESS;
		        $response_result['body'] = $responseResult;

				$this->response = $responseData;

				$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
				return true;
	        });
		} else {
            $responseResult = [
            	'api_url'      => $this->api_url,
            	'request_data' => $data,
            	'verify_token' => $apiResult,
            	'response'	   => $apiResult['data']
            ];

	        $response_result['code'] = self::FAIL;
	        $response_result['body'] = $responseResult;
			$this->response = $apiResult['data'];
		}


		$end_time = strtotime(date('Y-m-d H:i:s'));
        $processing_time = $end_time - $start_time;
		$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
		$response_result['body']['response']['processing_time'] = $elapsed_time;
		$this->response['processing_time'] = $elapsed_time;

		$this->utils->debug_log("FLOW_GAMING_SEAMLESS RESPONSE_BALANCE =====>req_id=" . $data['req_id'], $this->response);

		$additionalFields = [
			'player_id' => isset($player_id)?$player_id:null
		];
		$this->game_api->saveToResponseResult($response_result['code'], $response_result['api_name'], $response_result['body'], $response_result['response'], $additionalFields);

		return $this->response;
	}

	private function processPushFeedUsingQueue($data, $extra = null) {
		
		$this->utils->debug_log("bermar processPushFeedUsingQueue ", $data);
				
		//process remote queue
		$this->load->library(['lib_queue']);

		//add it to queue job
		$params['system_id'] = $this->gamePlatformId;
		$params['table'] = $this->transaction_game_logs_table;
		$params['data'] = $data;
		$callerType=Queue_result::CALLER_TYPE_ADMIN;
		$caller=$this->authentication->getUserId();
		$state='';
		$this->load->library(['language_function','authentication']);
		$lang=$this->language_function->getCurrentLanguage();
		$caller = $this->authentication->getUserId();
		$state = null;
		$lang = $this->language_function->getCurrentLanguage();			
		$systemId = Queue_result::SYSTEM_UNKNOWN;
		$funcName = 'flowgaming_process_pushfeed';		
		$token =  $this->lib_queue->commonAddRemoteJob($systemId, $funcName, $params, $callerType, $caller, $state, $lang);
		if(empty($token)){
			return false;
		}

		//verify if saved properly

		return true;
	}

	private function processPushFeedUsingService($data, $extra = null) {
		
		$unique_id = $this->CI->utils->getRequestId();

		//one file only
		$saveResult = $this->saveRequestToFile($unique_id, $data);	
		if(!$saveResult){
			return false;
		}

		$fileName = $saveResult['fileName'];
		$fileNames[] = $fileName;
		
		//save to redis
		$key= $this->game_api->getBatchPayoutRedisKey($unique_id);
		$key_full = $this->CI->utils->addAppPrefixForKey($key);
		$json=[
			'table' => $this->transaction_game_logs_table,
			'file_name'=>$fileName,
			'with_error'=>false,
			'date_hour'=>$saveResult['dateHour'],	
			'status'=>Game_logs::STATUS_PENDING, 
			'request_time'=>$this->CI->utils->getNowForMysql(), 
			'redis_key'=>$key_full
		];
		$this->utils->debug_log("bermar key", $key);
			
		$success=$this->CI->utils->writeJsonToRedis($key, $json, 864000);

		if(!$fileName || !$success){
			return false;
		}

		return $json;
	}

	private function processPushFeed($data, $extra = null) {
		$responseResult = [
        	'api_url'              		  	=> $this->api_url,
			'request_data'         		  	=> $data,
			'response_result_status_code' 	=> $this->status_code,
			'internal_response_result_body' => 'SUCCESS < NO RESPONSE BODY >'
		];

		if($this->game_api->enable_process_pushfeed_by_service){
			$this->status_code = self::STATUS_CODES['SUCCESS'];						
			$resp = $this->processPushFeedUsingService($data, $extra);			
			if(!$resp){
				$this->status_code = self::STATUS_CODES['ERROR'];
			}
			$responseResult['response_status_code'] = $this->status_code;
			$this->utils->debug_log("FLOW_GAMING_SEAMLESS ENDPOINT_PUSHFEED ====> ", $data, 'INTERNAL_RESPONSE =>', $responseResult);
			$this->game_api->saveToResponseResult(self::SUCCESS, self::ENDPOINT_RESPONSE . self::ENDPOINT_PUSHFEED, $responseResult, self::ENDPOINT_RESPONSE . self::ENDPOINT_PUSHFEED);
			$this->output->set_status_header($this->status_code);
			return $this->output->set_content_type('application/json')->set_output(json_encode($resp));
		}

		if($this->game_api->enable_process_pushfeed_by_queue){
			$this->status_code = self::STATUS_CODES['SUCCESS'];			
			$resp = $this->processPushFeedUsingQueue($data, $extra);					
			if(!$resp){
				$this->status_code = self::STATUS_CODES['ERROR'];
			}
			$responseResult['response_status_code'] = $this->status_code;
			$this->utils->debug_log("FLOW_GAMING_SEAMLESS ENDPOINT_PUSHFEED ====> ", $data, 'INTERNAL_RESPONSE =>', $responseResult);
			$this->game_api->saveToResponseResult(self::SUCCESS, self::ENDPOINT_RESPONSE . self::ENDPOINT_PUSHFEED, $responseResult, self::ENDPOINT_RESPONSE . self::ENDPOINT_PUSHFEED);
			return true;
		}
		
		$data = $data[0];

		$trans_category = array_key_exists('category', $data)  ? $data['category'] : null;
		$duplicate = false;
		$is_missing_wager = false;

		$this->status_code = self::STATUS_CODES['SUCCESS'];
        $data['account_ext_ref'] = isset($data['account_ext_ref']) ? $data['account_ext_ref'] : $data['ext_ref'];
        $data['parent_transaction_id'] = isset($data['parent_transaction_id']) ? $data['parent_transaction_id'] : $data['130248222'];
		$player_id = $this->game_api->getPlayerIdByGameUsername($data['account_ext_ref']);
		$response = '';

		if ($trans_category == self::TRANSACTION_REFUND && $data['sub_category'] == self::TRANSACTION_WAGER) {
	        $success = $this->lockAndTransForPlayerBalance($player_id, function() use($data, &$responseResult, &$response, &$player_id, &$duplicate, &$is_missing_wager, &$trans_category) {
				$bExist = $this->queryExistingTransaction([$data['id'], $data['parent_transaction_id']], true);
				$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
				$this->utils->debug_log("FLOW_GAMING_SEAMLESS pushFeed queryExistingTransaction ELAPSED TIME ====> ", $elapsed_time);
				if ($bExist['success']) {
					$res = count($bExist['data']);

					// $res is EXPECTED TO RETURN 1 ROW ONLY WHICH IS WAGER
					if ($res == 1 && $bExist['data'][0]['category'] != self::TRANSACTION_WAGER) {
						$duplicate = true;

					// if $res returns 0 rows this means that WAGER NOT EXISTING
					} else if ($res == 0) {
						$is_missing_wager = true;

					// if $res returns 2 rows this means that WAGER AND REFUND EXISTS
					} else if ($res > 1) {
						$duplicate = true;
					}
				} else {
					$is_missing_wager = true;
				}

		    	if ($duplicate) {
					$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
					$this->utils->debug_log("FLOW_GAMING_SEAMLESS pushFeed ELAPSED TIME ====> ", $elapsed_time);
					$response = $this->returnErrorResponse($data['session'], $elapsed_time, null, self::ERROR_MESSAGE['duplicate_entry'], self::ENDPOINT_PUSHFEED . '_' . $trans_category, [], self::STATUS_CODES['SUCCESS']);
					$responseResult['internal_response_result_body'] = $response['api_response'];
					return true;
		    	}


		    	// FOR REFUND IT WILL CHECK IF WE SAVED THE REFUNDED TRANSACTION
		    	if ($is_missing_wager && $trans_category == self::TRANSACTION_REFUND) {
					$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
					$this->utils->debug_log("FLOW_GAMING_SEAMLESS pushFeed ELAPSED TIME ====> ", $elapsed_time);
					$response = $this->returnErrorResponse($data['session'], $elapsed_time, null, self::ERROR_MESSAGE['wager_not_found'] . ' upon ' . $trans_category, self::ENDPOINT_PUSHFEED . '_' . $trans_category, [], self::STATUS_CODES['SUCCESS']);
					$responseResult['internal_response_result_body'] = $response['api_response'];
					return true;
		    	}

				$transaction_data = $this->game_api->rebuildPushFeed($data);
				$ext_tx_id = $this->game_api->getUniqueTicket();
				$player_name = $this->game_api->getPlayerUsernameByGameUsername($data['account_ext_ref']);
				$prev_balance = $this->processPlayerBalance($player_name);
				$after_balance = floatval($prev_balance) + floatval($data['amount']);

				$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
				$this->utils->debug_log("FLOW_GAMING_SEAMLESS pushFeed ELAPSED TIME ====> ", $elapsed_time);

				$transaction_data['ext_tx_id'] = isset($ext_tx_id) ? $ext_tx_id : null;
				$transaction_data['before_balance'] = isset($prev_balance) ? $prev_balance : null;
				$transaction_data['after_balance'] = isset($after_balance) ? $after_balance : null;
				$transaction_data['elapsed_time'] = $elapsed_time;

				$insert = $this->insert_data($this->transaction_game_logs_table, $transaction_data);
				$this->utils->debug_log("FLOW_GAMING_SEAMLESS pushFeed INSERT DATA RESULT ====> ", $insert);

                if (!($trans_category == self::TRANSACTION_WAGER || $trans_category == self::TRANSACTION_PAYOUT || $trans_category == self::TRANSACTION_REFUND)) {
                    $this->utils->error_log("FLOW_GAMING_SEAMLESS pushFeed ERROR DATA TO BE INSERTED ====> ", $transaction_data);
                    $this->utils->error_log("FLOW_GAMING_SEAMLESS pushFeed ERROR RAW POST REQUEST ====> ", $data);
                }
				// 1 FOR INSERT
				if ($insert == 1 && $this->use_insert_ignore_transaction) {
					$succ = $this->add_amount($player_id, $data['amount'], $trans_category);
					if (!$succ) {
						$error_message = self::ERROR_MESSAGE['internal_error_balance_adjustment'] . ' on ' . $trans_category;
						$response = $this->returnErrorResponse($data['session'], $elapsed_time, null, $error_message, self::ENDPOINT_PUSHFEED . '_' . $trans_category, [], self::STATUS_CODES['FAIL']);
						$responseResult['internal_response_result_body'] = $response['api_response'];
						return false;
					}
				}
				return true;
	        });

			if (!$success) {
				$this->status_code = self::STATUS_CODES['ERROR'];
			}
		}

		$responseResult['response_status_code'] = $this->status_code;
		$this->utils->debug_log("FLOW_GAMING_SEAMLESS ENDPOINT_PUSHFEED ====> ", $data, 'INTERNAL_RESPONSE =>', $responseResult);
		$this->game_api->saveToResponseResult(self::SUCCESS, self::ENDPOINT_RESPONSE . self::ENDPOINT_PUSHFEED, $responseResult, self::ENDPOINT_RESPONSE . self::ENDPOINT_PUSHFEED);
	}

	/*private function rebuildPushFeed($postData) {
		// $round_id = isset($postData['round_id']) ? strtok($postData['round_id'], '-') . '-' . strtok('-') . '-' . strtok('-') : null;
		$round_id = isset($postData['meta_data']['round_id']) ? $postData['meta_data']['round_id'] : null;
		$trans_category = isset($postData['category']) ? $postData['category'] : null;

		return [
			'req_id'    		 => isset($postData['session']) ? $postData['session'] : null,
			'timestamp' 		 => isset($postData['transaction_time']) ? $this->game_api->gameTimeToServerTime($postData['transaction_time']) : null,
			'token'              => isset($postData['token']) ? $postData['token'] : null,
			'account_ext_ref' 	 => isset($postData['account_ext_ref']) ? $postData['account_ext_ref'] : null,
			'category' 			 => $trans_category,
			'tx_id' 			 => isset($postData['id']) ? $postData['id'] : null,
			'refund_tx_id' 	   	 => isset($postData['parent_transaction_id']) ? $postData['parent_transaction_id'] : null,
			'amount' 		 	 => isset($postData['amount']) ? $postData['amount'] : null,
			'pool_amount' 		 => isset($postData['pool_amount']) ? $postData['pool_amount'] : null,
			'application_id' 	 => isset($postData['application_id']) ? $postData['application_id'] : null,
			'item_id' 		 	 => isset($postData['meta_data']['item_id']) ? $postData['meta_data']['item_id'] : null,
			'external_game_id'   => (isset($postData['application_id']) && isset($postData['meta_data']['item_id'])) ? $postData['application_id'] . '-' . $postData['meta_data']['item_id'] : null,
			'round_id' 			 => isset($postData['meta_data']['round_id']) ? $round_id : null,
			'response_result_id' => isset($postData['response_result_id']) ? $postData['response_result_id'] : null,
			'external_uniqueid'  => isset($postData['id']) ? $postData['id'] : null,
		];
	}*/

	private function processTransaction($data, $extra = null) {
		$this->utils->debug_log("FLOW_GAMING_SEAMLESS REQUEST_TRANSACTION ====>tx_id=" . $data['tx_id'], $data);
		$trans_category = $data['category'];

		$apiResult = $this->game_api->processVerifyToken($data);
		$start_time = strtotime(date('Y-m-d H:i:s'));

		$response = [];

        $response_result = [
            'code'     => self::FAIL,
            'api_name' => self::ENDPOINT_RESPONSE . self::ENDPOINT_TRANSACTION . '_' . $trans_category,
            'body'     => [],
            'response' => self::ENDPOINT_RESPONSE . self::ENDPOINT_TRANSACTION . '_' . $trans_category
        ];

		$this->status_code = self::STATUS_CODES['ERROR'];

		if ($apiResult['success']) {
			$player_id = $apiResult['data']['player_id'];
			$round_id = isset($data['round_id'])?$data['round_id']:null;
	        $success = $this->lockAndTransForPlayerBalance($player_id,function() use($apiResult, $data, $player_id, $start_time, &$response, &$response_result, $round_id) {
				$trans_category = $data['category'];
				$trans_amount = $data['amount'];
				$duplicate = false;
				$wager_exist = false;
				$wager_id = [];
				$wager_exist_2 = false;
				$error_balance = false;
				$after_balance = 0;
				$error_message = false;
				$is_missing_wager = false;

				$this->refund_in_process = false;
				$this->status_code = self::STATUS_CODES['SUCCESS'];
				$prev_balance = $this->game_api->roundDownAmount($apiResult['data']['balance']);

				if ($trans_category == self::TRANSACTION_WAGER && $trans_amount > $prev_balance) {
					$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);

					$response = $this->returnErrorResponse($data['req_id'], $start_time, $apiResult['data']['token'], self::ERROR_MESSAGE['insufficient_balance'] . ' upon ' . $trans_category, self::ENDPOINT_TRANSACTION . '_' . $trans_category, $apiResult, self::STATUS_CODES['INSUFFICIENT_BALANCE'], $data);

					$this->utils->debug_log("FLOW_GAMING_SEAMLESS RESPONSE_TRANSACTION ====>tx_id=" . $data['tx_id'], $response);
					return true;
				}

				list($_alreadyExist, $_alreadyRefunded, $_alreadyPayout, $betExist) = $this->queryTransaction($round_id, $trans_category);

				# check if bet exist
				if (($trans_category == self::TRANSACTION_PAYOUT || $trans_category == self::TRANSACTION_REFUND) && !$betExist) {					
					$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
					$response = $this->returnErrorResponse($data['req_id'], $start_time, $apiResult['data']['token'], self::ERROR_MESSAGE['wager_not_found'] . ' upon ' . $trans_category, self::ENDPOINT_TRANSACTION . '_' . $trans_category, $apiResult, self::STATUS_CODES['ERROR'], $data);	
					$this->utils->error_log("FLOW_GAMING_SEAMLESS RESPONSE_TRANSACTION MISSING WAGER" , $data, $response);
					return true;
				}	
				# check if payout already refunded
				if ($trans_category == self::TRANSACTION_PAYOUT && $_alreadyRefunded) {
					$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
					$response = $this->returnErrorResponse($data['req_id'], $start_time, $apiResult['data']['token'], self::ERROR_MESSAGE['already_refunded'], self::ENDPOINT_TRANSACTION . '_' . $trans_category, $apiResult, self::STATUS_CODES['DUPLICATE_TRANSACTION'], $data);
					$this->utils->error_log("FLOW_GAMING_SEAMLESS RESPONSE_TRANSACTION ALREADY REFUNDED" , $data, $response);
					return true;
				}	

				# check if refun already payout
				if ($trans_category == self::TRANSACTION_REFUND && $_alreadyPayout) {
					$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
					$response = $this->returnErrorResponse($data['req_id'], $start_time, $apiResult['data']['token'], self::ERROR_MESSAGE['already_settled'], self::ENDPOINT_TRANSACTION . '_' . $trans_category, $apiResult, self::STATUS_CODES['DUPLICATE_TRANSACTION'], $data);
					$this->utils->error_log("FLOW_GAMING_SEAMLESS RESPONSE_TRANSACTION ALREADY SETTLED" , $data, $response);
					return true;
				}	

				# check if refund already refunded
				if ($trans_category == self::TRANSACTION_REFUND && $_alreadyRefunded) {
					$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
					$response = $this->returnErrorResponse($data['req_id'], $start_time, $apiResult['data']['token'], self::ERROR_MESSAGE['already_refunded'], self::ENDPOINT_TRANSACTION . '_' . $trans_category, $apiResult, self::STATUS_CODES['DUPLICATE_TRANSACTION'], $data);
					$this->utils->error_log("FLOW_GAMING_SEAMLESS RESPONSE_TRANSACTION ALREADY SETTLED" , $data, $response);
					return true;
				}	

				# check if refund already refunded
				if ($trans_category == self::TRANSACTION_PAYOUT && $_alreadyPayout) {
					$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
					$response = $this->returnErrorResponse($data['req_id'], $start_time, $apiResult['data']['token'], self::ERROR_MESSAGE['already_settled'], self::ENDPOINT_TRANSACTION . '_' . $trans_category, $apiResult, self::STATUS_CODES['DUPLICATE_TRANSACTION'], $data);
					$this->utils->error_log("FLOW_GAMING_SEAMLESS RESPONSE_TRANSACTION ALREADY SETTLED" , $data, $response);
					return true;
				}	

				# check is already exist
				if($_alreadyExist){
					$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
					$response = $this->returnErrorResponse($data['req_id'], $start_time, $apiResult['data']['token'], self::ERROR_MESSAGE['duplicate_entry'], self::ENDPOINT_TRANSACTION . '_' . $trans_category, $apiResult, self::STATUS_CODES['DUPLICATE_TRANSACTION'], $data);
					$this->utils->error_log("FLOW_GAMING_SEAMLESS RESPONSE_TRANSACTION ALREADY EXIST" , $data, $response);
					return true;
				}


				 /*if ($trans_category == self::TRANSACTION_REFUND) {
					$bExist = $this->queryExistingTransaction([$data['tx_id'], $data['refund_tx_id']], true);
					$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
					if ($bExist['success']) {
						$res = count($bExist['data']);

						// $res is EXPECTED TO RETURN 1 ROW ONLY WHICH IS WAGER
						if ($res == 1 && $bExist['data'][0]['category'] != self::TRANSACTION_WAGER) {
							$duplicate = true;

						// if $res returns 0 rows this means that WAGER NOT EXISTING
						} else if ($res == 0) {
							$is_missing_wager = true;

						// if $res returns 2 rows this means that WAGER AND REFUND EXISTS
						} else if ($res > 1) {
							$duplicate = true;
						}
					} else {
						$is_missing_wager = true;
					}
				}

		    	if ($duplicate) {
					$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
					$response = $this->returnErrorResponse($data['req_id'], $start_time, $apiResult['data']['token'], self::ERROR_MESSAGE['duplicate_entry'], self::ENDPOINT_TRANSACTION . '_' . $trans_category, $apiResult, self::STATUS_CODES['DUPLICATE_TRANSACTION'], $data);

					$this->utils->debug_log("FLOW_GAMING_SEAMLESS RESPONSE_TRANSACTION ====>tx_id=" . $data['tx_id'], $response);
					return true;
		    	}*/


		    	// FOR REFUND IT WILL CHECK IF WE SAVED THE REFUNDED TRANSACTION
		    	/*if ($is_missing_wager && $trans_category == self::TRANSACTION_REFUND) {
					$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
					$response = $this->returnErrorResponse($data['req_id'], $start_time, $apiResult['data']['token'], self::ERROR_MESSAGE['wager_not_found'] . ' upon ' . $trans_category, self::ENDPOINT_TRANSACTION . '_' . $trans_category, $apiResult, self::STATUS_CODES['ERROR'], $data);

					$this->utils->debug_log("FLOW_GAMING_SEAMLESS RESPONSE_TRANSACTION ====>tx_id=" . $data['tx_id'], $response);
					return true;
		    	}*/

				$transaction_data = $this->rebuildTransaction($data);
				$ext_tx_id = $this->game_api->getUniqueTicket();
				$after_balance = $prev_balance;
				if ($trans_category == self::TRANSACTION_WAGER) {
					$after_balance = floatval($prev_balance) - floatval($trans_amount);
				} else if ($trans_category == self::TRANSACTION_PAYOUT || $trans_category == self::TRANSACTION_REFUND) {
					$after_balance = floatval($prev_balance) + floatval($trans_amount);
				}

				$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);

				$transaction_data['ext_tx_id'] = isset($ext_tx_id) ? $ext_tx_id : null;
				$transaction_data['before_balance'] = isset($prev_balance) ? $prev_balance : null;
				$transaction_data['after_balance'] = isset($after_balance) ? $after_balance : null;
				$transaction_data['elapsed_time'] = $elapsed_time;

				// insert/Ignore, CATCH IF SUCCESS SUBTRACT WAGER, IF IGNORE RETURN NOW
				// if ($this->use_old_insert_transaction) {
				// 	$this->game_api->doSyncOriginal(array($transaction_data), $this->transaction_game_logs_table, self::TRANSACTION_LOGS);
				// 	$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
				// 	$this->utils->debug_log("FLOW_GAMING_SEAMLESS processTransaction ELAPSED TIME ====> ", $elapsed_time);
				// } else {
				$insert = $this->insert_data($this->transaction_game_logs_table, $transaction_data);
				$this->utils->debug_log("FLOW_GAMING_SEAMLESS processTransaction INSERT DATA RESULT ====> ", $insert);

                if (!($trans_category == self::TRANSACTION_WAGER || $trans_category == self::TRANSACTION_PAYOUT || $trans_category == self::TRANSACTION_REFUND)) {
                    $this->utils->error_log("FLOW_GAMING_SEAMLESS processTransaction ERROR DATA TO BE INSERTED ====> ", $transaction_data);
                    $this->utils->error_log("FLOW_GAMING_SEAMLESS processTransaction ERROR RAW POST REQUEST ====> ", $data);
                }

				// 1 FOR INSERT
				if ($insert == 1 && $this->use_insert_ignore_transaction) {
					if ($trans_category == self::TRANSACTION_WAGER) {
						$succ = $this->subtract_amount($player_id, $trans_amount, $trans_category);
						if (!$succ) {
							$error_message = self::ERROR_MESSAGE['internal_error_balance_adjustment'] . ' on ' . $trans_category;
							$response = $this->returnErrorResponse($data['req_id'], $start_time, $apiResult['data']['token'], $error_message, self::ENDPOINT_TRANSACTION . '_' . $trans_category, $apiResult, self::STATUS_CODES['ERROR'], $data);

							$this->utils->debug_log("processTransaction 1 FLOW_GAMING_SEAMLESS RESPONSE_TRANSACTION ====>tx_id=" . $data['tx_id'], $response);
							return false;
						}
					} else if ($trans_category == self::TRANSACTION_PAYOUT || $trans_category == self::TRANSACTION_REFUND) {
						$succ = $this->add_amount($player_id, $trans_amount, $trans_category);
						if (!$succ) {
							$error_message = self::ERROR_MESSAGE['internal_error_balance_adjustment'] . ' on ' . $trans_category;

							$this->utils->debug_log("processTransaction 2 FLOW_GAMING_SEAMLESS RESPONSE_TRANSACTION ====>tx_id=" . $data['tx_id'], $response);
							$response = $this->returnErrorResponse($data['req_id'], $start_time, $apiResult['data']['token'], $error_message, self::ENDPOINT_TRANSACTION . '_' . $trans_category, $apiResult, self::STATUS_CODES['ERROR'], $data);
							return false;
						}
					}
				} else if ($insert == 0 && $this->use_insert_ignore_transaction) {
					$response = $this->returnErrorResponse($data['req_id'], $start_time, $apiResult['data']['token'], self::ERROR_MESSAGE['duplicate_entry'], self::ENDPOINT_TRANSACTION . '_' . $trans_category, $apiResult, self::STATUS_CODES['DUPLICATE_TRANSACTION'], $data);

					$this->utils->debug_log("processTransaction 3 FLOW_GAMING_SEAMLESS RESPONSE_TRANSACTION ====>tx_id=" . $data['tx_id'], $response);
					return false;
				} else {
					$after_balance = floatval($prev_balance);
				}
				// }
				$end_time = strtotime(date('Y-m-d H:i:s'));
		        $processing_time = $end_time - $start_time;

				$responseData = [
	        		'req_id'    	  => $apiResult['data']['req_id'],
	        		'processing_time' => $processing_time,
	            	'token'     	  => $apiResult['data']['token'],
	            	'balance'  	  	  => $this->game_api->roundDownAmount($after_balance),
	            	'ext_tx_id' 	  => $ext_tx_id,
	            	'timestamp'       => $apiResult['data']['timestamp'],
	            ];

	            $response_result['code']          = self::SUCCESS;
	            $response_result['api_name'] 	  = self::ENDPOINT_RESPONSE . self::ENDPOINT_TRANSACTION . '_' . $trans_category;
	            $response_result['response'] 	  = self::ENDPOINT_RESPONSE . self::ENDPOINT_TRANSACTION . '_' . $trans_category;

	            $responseResult = [
	            	'api_url'      => $this->api_url,
	            	'request_data' => $data,
	            	'verify_token' => $apiResult,
	            	'insert_data'  => $transaction_data,
	            	'response'	   => $responseData
	            ];

	            $response['response_result_body'] = $responseResult;

	            $response['api_response'] = $responseData;

				$this->utils->debug_log("processTransaction 4 FLOW_GAMING_SEAMLESS RESPONSE_TRANSACTION ====>tx_id=" . $data['tx_id'], $response);
	            return true;
	        });
			if (!$success) {
				$this->status_code = self::STATUS_CODES['ERROR'];
				$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
	            $response_result['code'] = self::FAIL;
	            $response['api_response'] = [
		    		'req_id'          => $data['req_id'],
		    		'processing_time' => $elapsed_time,
		        	'token'     	  => $apiResult['data']['token'],
					'err_desc'        => self::ERROR_MESSAGE['internal_error']
	            ];

				$responseResult = [
					'api_url'      => $this->api_url,
					'request_data' => $data,
					'verify_token' => $apiResult,
					'response'	   => $apiResult['data']
				];
				$response['response_result_body'] = $responseResult;
			}
		} else {
			$this->status_code = self::STATUS_CODES['INVALID_TOKEN'];
            $responseResult = [
            	'api_url'      => $this->api_url,
            	'request_data' => $data,
            	'verify_token' => $apiResult,
            	'response'	   => $apiResult['data']
            ];

            $response_result['code']          = self::FAIL;
            $response_result['api_name'] 	  = self::ENDPOINT_RESPONSE . self::ENDPOINT_TRANSACTION . '_' . $trans_category;
            $response_result['response'] 	  = self::ENDPOINT_RESPONSE . self::ENDPOINT_TRANSACTION . '_' . $trans_category;

            $response['response_result_body'] = $responseResult;
			$response['api_response'] 		  = $apiResult['data'];
			$this->utils->debug_log("FLOW_GAMING_SEAMLESS RESPONSE_TRANSACTION ====>tx_id=" . $data['tx_id'], $response);
		}
		$additionalFields = [
			'player_id' => isset($player_id)?$player_id:null
		];
		$response_result_body = isset($response['response_result_body'])?$response['response_result_body']:null;
		$this->game_api->saveToResponseResult(
			$response_result['code'], 
			$response_result['api_name'], 
			$response_result_body, 
			$response_result['response'], 
			$additionalFields);

		return $response['api_response'];
	}

	private function rebuildTransaction($postData) {
		// $round_id = isset($postData['round_id']) ? strtok($postData['round_id'], '-') . '-' . strtok('-') . '-' . strtok('-') : null;
		$round_id = isset($postData['round_id']) ? $postData['round_id'] : null;
		$trans_category = isset($postData['category']) ? $postData['category'] : null;
		if ($this->is_error_wager) {
			$trans_category = self::TRANSACTION_WAGER_INVALID;
		}

		return [
			'req_id'    		 => isset($postData['req_id']) ? $postData['req_id'] : null,
			'timestamp' 		 => isset($postData['timestamp']) ? $this->game_api->gameTimeToServerTime($postData['timestamp']) : null,
			'token'              => isset($postData['token']) ? $postData['token'] : null,
			'account_ext_ref' 	 => isset($postData['account_ext_ref']) ? $postData['account_ext_ref'] : null,
			'category' 			 => $trans_category,
			'tx_id' 			 => isset($postData['tx_id']) ? $postData['tx_id'] : null,
			'refund_tx_id' 	   	 => isset($postData['refund_tx_id']) ? $postData['refund_tx_id'] : null,
			'amount' 		 	 => isset($postData['amount']) ? $postData['amount'] : null,
			'pool_amount' 		 => isset($postData['pool_amount']) ? $postData['pool_amount'] : null,
			'application_id' 	 => isset($postData['application_id']) ? $postData['application_id'] : null,
			'item_id' 		 	 => isset($postData['item_id']) ? $postData['item_id'] : null,
			'external_game_id'   => (isset($postData['application_id']) && isset($postData['item_id'])) ? $postData['application_id'] . '-' . $postData['item_id'] : null,
			'round_id' 			 => isset($postData['round_id']) ? $round_id : null,
			'response_result_id' => isset($postData['response_result_id']) ? $postData['response_result_id'] : null,
			'external_uniqueid'  => isset($postData['tx_id']) ? $postData['tx_id'] : null,
		];
	}

	private function processEndround($data) {
		$this->utils->debug_log("FLOW_GAMING_SEAMLESS ENDPOINT_ENDROUND ====> ", $data);
		$start_time = strtotime(date('Y-m-d H:i:s'));
		$apiResult = $this->game_api->processVerifyToken($data);

		$response = [
    		'req_id'          => $data['req_id'],
    		'processing_time' => $start_time,
        	'token'     	  => $data['token'],
			'err_desc'        => self::ERROR_MESSAGE['internal_error']
		];

	    $response_result = [
            'code'     => self::FAIL,
            'api_name' => self::ENDPOINT_RESPONSE . self::ENDPOINT_ENDROUND,
            'body'     => [],
            'response' => self::ENDPOINT_RESPONSE . self::ENDPOINT_ENDROUND
        ];

		$this->status_code = self::STATUS_CODES['ERROR'];

    	if ($apiResult['success']) {
				$player_id = $apiResult['data']['player_id'];
		        $success = $this->lockAndTransForPlayerBalance($player_id,function() use($apiResult, $data, $player_id, $start_time, &$response, &$response_result) {
					$this->status_code = self::STATUS_CODES['SUCCESS'];

					$endround_data = $this->rebuildEndround($apiResult, $data);
					$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
					$endround_data['elapsed_time'] = $elapsed_time;
					$insert = $this->insert_data($this->original_game_logs_table, $endround_data);

					$end_time = strtotime(date('Y-m-d H:i:s'));
			        $processing_time = $end_time - $start_time;

					$current_balance = $this->game_api->roundDownAmount($apiResult['data']['balance']);

					$responseData = [
		        		'req_id'    	  => $apiResult['data']['req_id'],
		        		'processing_time' => $processing_time,
		            	'token'     	  => $apiResult['data']['token'],
		            	'balance'  	  	  => $this->game_api->roundDownAmount($current_balance),
		            	'timestamp'       => $apiResult['data']['timestamp'],
		            ];

		            $responseResult = [
		            	'api_url'      => $this->api_url,
		            	'request_data' => $data,
		            	'verify_token' => $apiResult,
		            	'insert_data'  => $endround_data,
		            	'response'	   => $responseData
		            ];

			        $response_result['code'] = self::SUCCESS;
			        $response_result['body'] = $responseResult;
		            $response = $responseData;

		            return true;
		        });
	    	} else {
				$this->status_code = self::STATUS_CODES['INVALID_TOKEN'];
	            $responseResult = [
	            	'api_url'      => $this->api_url,
	            	'verify_token' => $apiResult,
	            	'response'	   => $apiResult['data']
	            ];
		        $response_result['code'] = self::FAIL;
		        $response_result['body'] = $responseResult;
				$response = $apiResult['data'];
			}

		$end_time = strtotime(date('Y-m-d H:i:s'));
        $processing_time = $end_time - $start_time;
		$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);
		$response_result['body']['processing_time'] = $elapsed_time;

		$this->utils->debug_log("FLOW_GAMING_SEAMLESS RESPONSE_ENDROUND ====> ", $response);
		$this->game_api->saveToResponseResult($response_result['code'], $response_result['api_name'], $response_result['body'], $response_result['response']);

        return $response_result['body']['response'];
	}


	private function rebuildEndround($verifyTokenData, $postData) {
		$player_id = $this->game_api->getPlayerIdInGameProviderAuth($postData['account_ext_ref']);
		$round_stats = isset($postData['round_stats']) ? $postData['round_stats'] : array();
		$txs = isset($postData['txs']) ? json_encode($postData['txs']) : '';
		$ext_tx_id = $this->game_api->getUniqueTicket();
		$start_time = strtotime(date('Y-m-d H:i:s'));
		$wager_count = null;
		$wager_sum = null;
		$payout_count = null;
		$payout_sum = null;
		$refund_count = null;
		$refund_sum = null;

		$current_balance = $this->game_api->roundDownAmount($verifyTokenData['data']['balance']);
		$after_balance = $current_balance;
		$prev_balance = $current_balance;

		foreach ($round_stats as $round) {
			if (isset($round['category']) && $round['category'] == self::TRANSACTION_WAGER) {
				$wager_count = isset($round['count']) ? $round['count'] : null;
				$wager_sum = isset($round['sum']) ? $round['sum'] : null;
			}
			if (isset($round['category']) && $round['category'] == self::TRANSACTION_PAYOUT) {
				$payout_count = isset($round['count']) ? $round['count'] : null;
				$payout_sum = isset($round['sum']) ? $round['sum'] : null;
			}
			if (isset($round['category']) && $round['category'] == self::TRANSACTION_REFUND) {
				$refund_count = isset($round['count']) ? $round['count'] : null;
				$refund_sum = isset($round['sum']) ? $round['sum'] : null;
				if ($refund_count >= 1) {
					// UPDATE 1/21/2021: WE WILL NOT PROCESS REFUND ON ENDROUND
					// $this->processRefundTransaction($postData, $player_id, $refund_count, $refund_sum);
				}
			}
		}

		if (isset($wager_count) && isset($wager_sum) && $wager_count >= 1) {
			$prev_balance = $prev_balance + $wager_sum;
		}
		if (isset($payout_count) && isset($payout_sum) && $payout_count >= 1) {
			$prev_balance = $prev_balance - $payout_sum;
		}
		if (isset($refund_count) && isset($refund_sum) && $refund_count >= 1) {
			$prev_balance = $prev_balance - $refund_sum;
		}

		// $round_id = isset($postData['round_id']) ? strtok($postData['round_id'], '-') . '-' . strtok('-') . '-' . strtok('-') : null;
		$round_id = isset($postData['round_id']) ? $postData['round_id'] : null;

		$endround_data = [
			'req_id'    		 => isset($postData['req_id']) ? $postData['req_id'] : null,
			'timestamp' 		 => isset($postData['timestamp']) ? $this->game_api->gameTimeToServerTime($postData['timestamp']) : null,
			'token'              => isset($postData['token']) ? $postData['token'] : null,
			'account_ext_ref' 	 => isset($postData['account_ext_ref']) ? $postData['account_ext_ref'] : null,
			'tx_id' 			 => isset($postData['tx_id']) ? $postData['tx_id'] : null,
			'application_id' 	 => isset($postData['application_id']) ? $postData['application_id'] : null,
			'item_id' 		 	 => isset($postData['item_id']) ? $postData['item_id'] : null,
			'external_game_id'   => (isset($postData['application_id']) && isset($postData['item_id'])) ? $postData['application_id'] . '-' . $postData['item_id'] : null,
			'round_id' 			 => isset($postData['round_id']) ? $round_id : null,
			'complete_round_id'  => isset($postData['round_id']) ? $postData['round_id'] : null,
			'txs' 				 => isset($postData['txs']) ? $txs : null,
			'wager_count' 		 => $wager_count,
			'wager_sum' 		 => $wager_sum,
			'payout_count' 		 => $payout_count,
			'payout_sum' 		 => $payout_sum,
			'refund_count' 		 => $refund_count,
			'refund_sum' 		 => $refund_sum,
			'status' 			 => (isset($refund_count) && isset($refund_sum) && $refund_count >= 1) ? self::LOGS_REFUND_API_RESPONSE : self::LOGS_SETTLE_API_RESPONSE,
			'response_result_id' => isset($postData['response_result_id']) ? $postData['response_result_id'] : null,
			'external_uniqueid'  => isset($postData['tx_id']) ? $postData['tx_id'] : null,
			'ext_tx_id' 		 => $ext_tx_id,
			'before_balance' 	 => isset($prev_balance) ? $prev_balance : null,
			'after_balance' 	 => isset($after_balance) ? $after_balance : null,
			'category' 	 		 => self::ENDPOINT_ENDROUND,
		];
		return $endround_data;
	}

	/*
	 * Update January 22, 2021: We will not be using this function anymore since this one execute only upon endround
	 *
	 *
	 * For some instances that the API did'nt send a REFUND request on ENDPOINT:TRANSACTION that the ENDPOINT:ENDROUND has. Because upon ENDPOINT:TRANSACTION if they receive an
	 * invalid format on response they will not send a REFUND request on ENDPOINT:TRANSACTION because that TRANSACTION is invalid anymore then upon ENDPOINT:ENDROUND when they
	 * receive an invalid format on response on ENDPOINT:TRANSACTION it will have a REFUND context
	 *
	 * As per provider if the transaction is successfull between API and ENDPOINT the API requesting on ENDPOINT will not be terminated/ cancelled. Like for example upon
	 * ENDPOINT:TRANSACTION ( WAGER ) request if the ENDPOINT response contains an error then that transaction is INVALID anymore and no other transaction will be requested on
	 * ENDPOINT: TRANSACTION
	 */
	private function processRefundTransaction($data, $player_id, $refund_count, $refund_sum) {
		$this->refund_in_process = true;
        $success = $this->lockAndTransForPlayerBalance($player_id,function() use($data, $player_id, $refund_count, $refund_sum) {
			if (isset($data['txs'])) {
				foreach ($data['txs'] as $tx_id) {
					// Will check if there's a valid wager before processing refund
					$bExistValidWager = $this->queryExistingTransaction($data['tx_id'], true);
					// Will check if the current TX_ID is already existing on transaction then if not it means it is the REFUND because WAGER and PAYOUT always been saved upon callback
					// $bExist = $this->queryExistingTransaction($data['round_id'], null, $tx_id, false, true);

			    	if (empty($bExist) == true && $bExistValidWager == true) {
						$newData = [
							'req_id'    		 => isset($data['req_id']) ? $data['req_id'] : null,
							'timestamp' 		 => isset($data['timestamp']) ?$data['timestamp'] : null,
							'token'              => isset($data['token']) ? $data['token'] : null,
							'account_ext_ref' 	 => isset($data['account_ext_ref']) ? $data['account_ext_ref'] : null,
							'category' 			 => self::TRANSACTION_REFUND,
							'tx_id' 			 => isset($tx_id) ? $tx_id : null,
							'refund_tx_id' 	   	 => null,
							'amount' 		 	 => (isset($this->refund_in_process) && $this->refund_in_process == true) ? $refund_sum : 0,
							'pool_amount' 		 => isset($data['pool_amount']) ? $data['pool_amount'] : null,
							'item_id' 		 	 => isset($data['item_id']) ? $data['item_id'] : null,
							'application_id' 	 => isset($data['application_id']) ? $data['application_id'] : null,
							'round_id' 			 => isset($data['round_id']) ? $data['round_id'] : null
						];

						$extra = [
							'refund_wager_category' => self::TRANSACTION_WAGER,
							'transaction_upon_endround' => true
						];

						// $this->processTransaction($newData, $extra);
			    	} else if (empty($bExist) == true && $bExistValidWager == false) {
						$newData = [
							'req_id'    		 => isset($data['req_id']) ? $data['req_id'] : null,
							'timestamp' 		 => isset($data['timestamp']) ?$data['timestamp'] : null,
							'token'              => isset($data['token']) ? $data['token'] : null,
							'account_ext_ref' 	 => isset($data['account_ext_ref']) ? $data['account_ext_ref'] : null,
							'category' 			 => self::TRANSACTION_REFUND_INVALID,
							'tx_id' 			 => isset($tx_id) ? $tx_id : null,
							'refund_tx_id' 	   	 => null,
							'amount' 		 	 => (isset($this->refund_in_process) && $this->refund_in_process == true) ? $refund_sum : 0,
							'pool_amount' 		 => isset($data['pool_amount']) ? $data['pool_amount'] : null,
							'item_id' 		 	 => isset($data['item_id']) ? $data['item_id'] : null,
							'application_id' 	 => isset($data['application_id']) ? $data['application_id'] : null,
							'round_id' 			 => isset($data['round_id']) ? $data['round_id'] : null
						];

						$extra = [
							'refund_wager_category' => self::TRANSACTION_WAGER_INVALID,
							'transaction_upon_endround' => true
						];
						// $this->processTransaction($newData, $extra);
			    	}
				}
			}
			return true;
		});

		return;
	}

	private function processPlayerBalance($player_username) {
		$current_balance = $this->game_api->queryPlayerBalance($player_username);
		return $this->game_api->roundDownAmount($current_balance['balance']);
	}

	private function queryExistingTransaction($id, $by_tx_id = false) {
        $sql = <<<EOD
            SELECT tx_id, round_id, category FROM {$this->transaction_game_logs_table} WHERE
EOD;

        $params = [
            $id
        ];

		if ($by_tx_id) {
			if (is_array($id)) {
		    	$id_counts = count($id);
		    	$sql .= " tx_id IN (" . implode(',', array_fill(0, $id_counts, '?')) . ")";
		    	$params = $id;
			} else {
				$sql .= ' tx_id = ?';
			}
		} else {
			$sql .= ' round_id = ?';
		}

		// if ($by_category) {
		// 	$sql .= ' AND category = ?';
		// 	array_push($params, $category);
		// }

		// if ($by_tx_id) {
		// 	$sql .= ' AND tx_id = ?';
		// 	array_push($params, $tx_id);
		// }

    	$queryResult = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql,$params	);
    	if (!empty($queryResult)) {
    		return ['success' => true, 'data' => $queryResult];
    	}
    	return ['success' => false, 'data' => $queryResult];;
	}

	private function queryTransaction($round_id, $category) {
		$betExist = $alreadyExist = $alreadyRefunded = $alreadyPayout = false;
		$qry = $this->CI->db->get_where($this->transaction_game_logs_table, array('round_id' => $round_id));

		$data = $qry->result_array();

		$this->utils->debug_log("queryTransaction" , $data);
		foreach($data as $row){
			if($category==$row['category']){
				$alreadyExist = true;
			}
			if($row['category']=='REFUND'){
				$alreadyRefunded = true;
			}
			if($row['category']=='PAYOUT'){
				$alreadyPayout = true;
			}
			if($row['category']=='WAGER'){
				$betExist = true;
			}
		}

		return [$alreadyExist, $alreadyRefunded, $alreadyPayout, $betExist];
	}

	private function insert_data($table_name, $data) {
        $inserted = 0;
        if ($this->use_insert_ignore_transaction) {
        	// 1 FOR INSERT, 0 FOR IGNORE
            $inserted = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal($table_name, $data);
        } else {
            $inserted = $this->CI->original_game_logs_model->insertRowsToOriginal($table_name, $data);
        }

        if($inserted && $this->utils->getConfig('enable_fast_track_integration')) {
            $this->transaction_for_fast_track = $data;
            $this->transaction_for_fast_track['id'] = $this->CI->db->insert_id();
        }

        return $inserted;
	}


    private function sendToFastTrack() {
        $this->CI->load->model(['game_description_model']);
        $game_description = $this->game_description_model->getGameDetailsByGameCodeAndGamePlatform($this->game_api->getPlatformCode(), $this->transaction_for_fast_track['external_game_id']);
        $betType = null;
        switch($this->transaction_for_fast_track['category']) {
            case 'WAGER':
                $betType = 'Bet';
                break;
            case 'PAYOUT':
                $betType = 'Win';
                break;
            case 'REFUND':
            case 'WAGER_INVALID':
            case 'REFUND_INVALID':
                $betType = 'Refund';
                break;
            default:
                $betType = null;
                break;
        }

        if ($betType == null) {
            return;
        }

        $data = [
            "activity_id" =>  strval($this->transaction_for_fast_track['id']),
            "amount" => (float) abs($this->transaction_for_fast_track['amount']),
            "balance_after" =>  $this->transaction_for_fast_track['after_balance'],
            "balance_before" =>  $this->transaction_for_fast_track['before_balance'],
            "bonus_wager_amount" =>  0.00,
            "currency" =>  $this->game_api->currency,
            "exchange_rate" =>  1,
            "game_id" => isset($game_description) ? $game_description->game_description_id : 'unknown',
            "game_name" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_name)['en'] : 'unknown',
            "game_type" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_type)['en'] : 'unknown',
            "is_round_end" =>  $betType == 'Win' ? true : false,
            "locked_wager_amount" =>  0.00,
            "origin" =>  $_SERVER['HTTP_HOST'],
            "round_id" =>  strval($this->transaction_for_fast_track['external_uniqueid']),
            "timestamp" =>  str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            "type" =>  $betType,
            "user_id" => $this->game_api->getPlayerIdInGameProviderAuth($this->transaction_for_fast_track['account_ext_ref']),
            "vendor_id" =>  strval($this->game_api->getPlatformCode()),
            "vendor_name" =>  $this->external_system->getSystemName($this->game_api->getPlatformCode()),
            "wager_amount" => $betType == 'Bet' ? (float) abs($this->transaction_for_fast_track['amount']) : 0,
        ];

        $this->load->library('fast_track');
        $this->fast_track->addToQueue('sendGameLogs', $data);
    }

	private function subtract_amount($playerId, $amount, $transaction = null) {
        // $success=$this->lockAndTransForPlayerBalance($playerId,function() use($playerId, $amount, $transaction){
        	$succ = true;
        	// POSITIVE AMOUNT THEN DO WALLET ADJUSTMENT
        	if ($amount > 0) {
				$succ=$this->wallet_model->decSubWallet($playerId, $this->gamePlatformId, $amount);

        	} else if ($amount === 0){
        		$succ = true;

			// NEGATIVE AMOUNT RETURN ERROR
        	} else {
        		$succ = false;
        	}
			return $succ;
        // });
		$this->game_api->saveToResponseResult(self::SUCCESS, self::ENDPOINT_SUBTRACT_SUBWALLET . '_' . $transaction,
			['player_id' => $playerId, 'amount' => $amount], self::ENDPOINT_SUBTRACT_SUBWALLET . '_' . $transaction);
        // return $success;

		// $lockedKey = NULL;
		// $lock_it = $this->utils->lockResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
		// $this->utils->debug_log('lock subtract_amount', 'id', $playerId, $lock_it);

		// if ($lock_it) {
		// 	try {
		// 		$this->startTrans();
		// 		$this->wallet_model->decSubWallet($playerId, $this->gamePlatformId, $amount);
		// 		$this->endTransWithSucc();
		// 	} finally {
		// 		$this->utils->releaseResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
		// 		$this->utils->debug_log('release subtract_amount lock', 'id', $playerId);
		// 		$this->game_api->saveToResponseResult(self::SUCCESS, self::ENDPOINT_SUBTRACT_SUBWALLET . '_' . $transaction, ['player_id' => $playerId, 'amount' => $amount], self::ENDPOINT_SUBTRACT_SUBWALLET . '_' . $transaction);
		// 	}
		// }
	}

	private function add_amount($playerId, $amount, $transaction = null) {
        // $success=$this->lockAndTransForPlayerBalance($playerId,function() use($playerId, $amount, $transaction){
        	$succ = true;
        	// POSITIVE AMOUNT THEN DO WALLET ADJUSTMENT
        	if ($amount > 0) {
				$succ=$this->wallet_model->incSubWallet($playerId, $this->gamePlatformId, $amount);

        	} else if ($amount === 0){
        		$succ = true;

			// NEGATIVE AMOUNT RETURN ERROR
        	} else {
        		$succ = false;
        	}
			return $succ;
        // });
		$this->game_api->saveToResponseResult(self::SUCCESS, self::ENDPOINT_ADD_SUBWALLET . '_' . $transaction,
			['player_id' => $playerId, 'amount' => $amount], self::ENDPOINT_ADD_SUBWALLET . '_' . $transaction);
		// return $success;

		// $lockedKey = NULL;
		// $lock_it = $this->utils->lockResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
		// $this->utils->debug_log('lock add_amount', 'id', $playerId, $lock_it);

		// if ($lock_it) {
		// 	try {
		// 		$this->startTrans();
		// 		$this->wallet_model->incSubWallet($playerId, $this->gamePlatformId, $amount);
		// 		$this->endTransWithSucc();
		// 	} finally {
		// 		$this->utils->releaseResourceBy($playerId, Utils::LOCK_ACTION_BALANCE, $lockedKey);
		// 		$this->utils->debug_log('release add_amount lock', 'id', $playerId);
		// 		$this->game_api->saveToResponseResult(self::SUCCESS, self::ENDPOINT_ADD_SUBWALLET . '_' . $transaction, ['player_id' => $playerId, 'amount' => $amount], self::ENDPOINT_ADD_SUBWALLET . '_' . $transaction);
		// 	}
		// }
	}

	private function returnErrorResponse($req_id, $start_time, $token, $err_desc, $end_point, $extra = [], $status_code, $request = []) {
		$elapsed_time = intval($this->utils->getExecutionTimeToNow()*1000);

		$responseData = [
    		'req_id'          => $req_id,
    		'processing_time' => $elapsed_time,
        	'token'     	  => $token,
			'err_desc'        => $err_desc
		];

		$responseResult = [
        	'api_url'      => $this->api_url,
        	'request_data' => $request,
			'result'       => $extra,
			'response'     => $responseData
		];

		$this->is_error_wager = false;
		$this->status_code = $status_code;
		// $this->game_api->saveToResponseResult(self::FAIL, self::ENDPOINT_RESPONSE . $end_point, $responseResult, self::ENDPOINT_RESPONSE . $end_point);

		return ['api_response' => $responseData, 'response_result_body' => $responseResult];
	}

	public function saveRequestToFile($unique_id, $data, $dir = null) {

		if(empty($data)){
			return false;
		}

		$dataHour = date('Ymd').'/'.date('Hi');

		if(empty($dir)){
			$dir=$this->CI->utils->getBatchpayoutSharingUploadPath($this->game_api->getPlatformCode(), $dataHour);
		}

		$filename = $unique_id . ".json";
		$f = $dir .'/'. $filename;
		$success = file_put_contents($f, json_encode($data));
		if(!$success){
			return false;
		}

		//verify if really exist
		/*if(!empty($f) && file_exists($f)){
			return false;
		}*/

		return ['fileName'=>$filename, 'dateHour'=>$dataHour];
	}
	
	public function testWriteToRedis($gamePlatformId){	
		
        $this->utils->debug_log("bermar: (testWriteToRedis)");

		$this->init($gamePlatformId);

		$postJson = file_get_contents("php://input");
		$arr = !empty($postJson) ? json_decode($postJson,true) : array();
		
		$unique_id = $arr['unique_id'];
		$ttl = $arr['ttl'];

		//save to redis
		$key= $this->game_api->getBatchPayoutRedisKey($unique_id);
		$json=[
			'file_name'=>'test file name',
			'status'=>Game_logs::PENDING, 
			'request_time'=>$this->CI->utils->getNowForMysql()
		];
		$success=$this->CI->utils->writeJsonToRedis($key, $json, $ttl);
		$data = [];
		$data['success'] = $success;
		
		return $this->output->set_content_type('application/json')->set_output(json_encode($data));
	}

	public function testGetFromRedis($gamePlatformId){	
		
        $this->utils->debug_log("bermar: (testGetFromRedis)");

		$this->init($gamePlatformId);  

		$postJson = file_get_contents("php://input");
		$arr = !empty($postJson) ? json_decode($postJson,true) : array();

		$key = $arr['key'];
		$appPrefix = $this->CI->utils->getAppPrefix();
		//$key = 'default-og-batch-payout-5928-323ca684-01de-484f-bd32-631ac8b07ac3';
        $keyNoPrefix = str_replace($appPrefix.'-','',$key);
		$data = $this->CI->utils->readJsonFromRedis($keyNoPrefix);
		
		return $this->output->set_content_type('application/json')->set_output(json_encode($data));
	}

	public function testGetAllFromRedis($gamePlatformId){	
		
        $this->utils->debug_log("bermar: (testGetFromRedis)");

		$this->init($gamePlatformId);
		$appPrefix = $this->CI->utils->getAppPrefix();
		$postJson = file_get_contents("php://input");
		$arr = !empty($postJson) ? json_decode($postJson,true) : array();		

		//$keysMatched = 'batch-payout-'.$this->game_api->getPlatformCode().'*';
		$keysMatched = '*';
		if(isset($arr['keys'])){
			$keysMatched = $arr['keys'];
		}
		$allKeys=$this->CI->utils->readRedisKeysNoAppPrefix($keysMatched, $appPrefix);		
		
		$result=[];
		$result['allKeys']=$allKeys;
		
		return $this->output->set_content_type('application/json')->set_output(json_encode($result));
	}

	public function testRemoveFromRedis($gamePlatformId){	
		
        $this->utils->debug_log("bermar: (testGetFromRedis)");

		$this->init($gamePlatformId);  

		$postJson = file_get_contents("php://input");
		$arr = !empty($postJson) ? json_decode($postJson,true) : array();

		$key = $arr['key'];
		$appPrefix = $this->CI->utils->getAppPrefix();
		//$key = 'default-og-batch-payout-5928-323ca684-01de-484f-bd32-631ac8b07ac3';
        $keyNoPrefix = str_replace($appPrefix.'-','',$key);
		//$data = $this->CI->utils->readJsonFromRedis($keyNoPrefix);
		$data = [];
		$result = $this->CI->utils->deleteRedisKey($key);
		$data['result'] = $result;
		return $this->output->set_content_type('application/json')->set_output(json_encode($data));
	}

	
}

///END OF FILE////////////
