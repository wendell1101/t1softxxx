<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
// require_once dirname(__FILE__) . '/Abstract_seamless_service_game_api.php';
require_once dirname(__FILE__) . '/BaseController.php';

class sexybaccarat_service_api extends BaseController {

	protected $gamePlatformId = SEXY_BACCARAT_SEAMLESS_API;
	protected $defaultGameCurrency = "THB";
    private $http_status_code;

	const SUCCESS = "0000";
	const FAILED = "9999";
	const ACTION_TYPES = [
		'voidSettle',
		'unvoidSettle',
		'voidBet',
		'unvoidBet',
		'unsettle',
		'settle',
		'cancelBet',
		'refund',
		'adjustBet',
	];

	 /*
		Place bet	1 player / multiple transactions (1 confirm action)
		Cancel bet	1 player / multiple transactions (1 confirm action)
		Settle	multiple player / multiple transactions (1 round action)
		Unsettle	multiple player / multiple transactions (1 round action)
		Voidbet	multiple player / multiple transactions (1 round action)
		VoidSettle	1 player / multiple transaction (by selected transaction)
	*/

	const ERROR_CODE = array(
		"9998" => "System Busy",
		"9999" => "Fail",
		"0000" => "Success",
		"10" => "Please input all data!",
		"1000" => "Invalid user ID",
		"1001" => "Account Existed",
		"1002" => "Account is not Exist",
		"1003" => "Operation ID does not Exist",
		"1004" => "Invalid Currency",
		"1005" => "Language is not exists",
		"1010" => "Invalid Amount",
		"1011" => "Invalid txCode",
		"1012" => "Has Pending Transfer",
		"1013" => "Account is Lock",
		"1014" => "Account is Suspend",
		"1015" => "Account is Close",
		"1016" => "TxCode already operation!",
		"1017" => "TxCode is not exist",
		"1018" => "Not enough balance",
		"1019" => "No Data",
		"1027" => "Invalid Certificate",
		"1028" => "Unable to proceed. please try again later",
        "1029" => "Invalid IP address",
		"1036" => "Invalid Parameters",
	);

	const STATUS_CODE_NOT_ENOUGH_BALANCE  = '1018';
	const STATUS_CODE_TRANSACTION_EXIST  = '1016';
	const STATUS_CODE_INVALID_AMOUNT  = '1010';
	const STATUS_CODE_FAIL = '9999';

	public function __construct() {

		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model', 'game_description_model', 'sexy_baccarat_transactions'));
		$this->game_api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
		// $this->gameApiSys = $this->gamePlatformId;
		// $this->gameApiSysLibObj = $this->getGameApiSysLibObj();
		$this->playerId = null;
        $this->http_status_code = 200;
	}

    private $transaction_for_fast_track = null;


	/*
	*
	* Sample Raw Input Data
	*		key=kDrU6Afbv7oObmhRNn0&message=%7B%0A%09%09%22action%22%3A%20%22bet%22%2C%0A%09%09%22txns%22%3A%20%5B%0A%09%09%09%7B%0A%09%09%09%09%22platformTxId%22%3A%20%22T65%22%2C%0A%09%09%09%09%22userId%22%3A%20%22devtestsexy1%22%2C%0A%09%09%09%09%22currency%22%3A%20%22THB%22%2C%0A%09%09%09%09%22platform%22%3A%20%22SV38%22%2C%0A%09%09%09%09%22gameType%22%3A%20%22LIVE%22%2C%0A%09%09%09%09%22gameCode%22%3A%20%2210%22%2C%0A%09%09%09%09%22gameName%22%3A%20%22CockFight%22%2C%0A%09%09%09%09%22betType%22%3A%20%22MERON%22%2C%0A%09%09%09%09%22betAmount%22%3A%201%2C%0A%09%09%09%09%22betTime%22%3A%20%222020-03-30T19%3A17%3A05.383%2B08%3A00%22%2C%0A%09%09%09%09%22roundId%22%3A%20%22123123123%22%2C%0A%09%09%09%09%22gameInfo%22%3A%20%7B%7D%0A%09%09%09%7D%0A%09%09%5D%0A%09%7D
	*/
	public function index() {
		// print_r($this->game_api);exit;
		if ($this->external_system->isGameApiActive($this->gamePlatformId)) {

			$params = file_get_contents('php://input');
			$this->CI->utils->debug_log(__FUNCTION__,'Sexy Baccarat (Raw Input Data)', $params);
			parse_str($params, $paramsOutput);
			$certKey = $paramsOutput['key'];
			$value = $paramsOutput['message'];
			$reqParams = json_decode($value, true);
			$fullRequest = $reqParams;
			$playerId=null;
			$userId=null;
			$playerUsername=null;
			$certificate = $this->game_api->cert;
			$secret_key = $this->game_api->secret_key;
			$this->CI->utils->debug_log('-----------------------------PARAMETER:', 'Key: ', $certKey, 'Message: ', $value);

             # check IP Address if allowed.
             if (!$this->game_api->validateWhiteIP()) {
                $success = false;
                $this->http_status_code = 401;
                $callMethod = 'validateWhiteIP';
                $response = $this->returnError('1029');

                # save response result and API request
                $this->saveResponseResult($success, $callMethod, $reqParams, $response);

                return $this->returnJsonResult($response, true, "*", false,  false, $this->http_status_code);
            }

			if($reqParams['action'] != 'getBalance') {
				$transacInfo = $reqParams['txns'];
				if(is_array($transacInfo) || is_object($transacInfo)) {
					foreach ($transacInfo as $info => $value) {
						$userId = $value['userId'];
						$paramsValue[] = $value;
					}
				}
			} else {
				$userId = $reqParams['userId'];
				$paramsValue = $reqParams;
			}

			$callMethod = $reqParams['action'];

			$response = [];
			$success = true;
			try{
				# check player if exists
				// if($certificate != $certKey) {
				// 	throw new Exception('1027');
				// }

				if($secret_key != $certKey) {
					throw new Exception('1036');
				}

				# Check if params are not empty
				if(empty($reqParams)) {
					throw new Exception('1019');
				}
				
				$response = $this->callMethod($playerId,$userId,$playerUsername,$callMethod,$fullRequest,$paramsValue);

			} catch (Exception $e) {
				log_message('error', self::ERROR_CODE[$e->getMessage()]);
				$response = $this->returnError($e->getMessage());
			}

		} else {
			$success = false;
			$response = $this->returnError('9998');
		}
			
		# save response result and API request
		$this->saveResponseResult($success,$callMethod,$reqParams,$response);

        if($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration') && $response['status'] == self::SUCCESS) {
            $this->sendToFastTrack();
        }
		return $this->returnJsonResult($response, true, "*", false,  false, $this->http_status_code);
	}


	private function callMethod($playerId,$gameUsername,$playerUsername,$callMethod,$fullRequest=null,$paramsValue=null){

		switch ($callMethod){
			case 'bet':
				return $this->playerBet($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue);
				break;
			case 'cancelBet':
				return $this->cancelBet($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue);
				break;
			case 'adjustBet':
				return $this->adjustBet($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue);
				break;
			case 'voidBet': 
				return $this->voidBet($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue);
				break;
			case 'unvoidBet': 
				return $this->unvoidBet($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue);
				break;
			case 'refund': 
				return $this->refund($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue);
				break;
			case 'settle': 
				return $this->settle($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue);
				break;
			case 'give': 
				return $this->give($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue);
				break;
			case 'unsettle': 
				return $this->unsettle($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue);
				break;
			case 'voidSettle': 
				return $this->voidSettle($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue);
				break;
			case 'unvoidSettle': 
				return $this->unvoidSettle($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue);
				break;
			case 'getBalance': 
				return $this->getBalance($gameUsername);
				break;
			case 'tip': 
			case 'cancelTip': 
				return $this->tipOrCancelTip($fullRequest);
				break;
			case 'resettle': 
				return $this->resettle($playerId, $playerUsername, $gameUsername, $fullRequest, $paramsValue);
				break;

			default:
				throw new Exception('10');
				break;
		}
	}

	function sortTxnsByUser($input,$sortkey){
		foreach ($input as $key=>$val) $output[$val[$sortkey]][]=$val;
		return $output;
	}

	/**
	*
	*	Sending transactions when user pays tip to streamers.
	*
	*/
	public function tipOrCancelTip($request) {
		$action = isset($request['action']) ? $request['action'] : null;
		$txns = isset($request['txns']) ? $request['txns'] : null;
		$response = array();
		if(!empty($txns)){
			$gameUsernames = $this->sortTxnsByUser($txns, 'userId');
			if(!empty($gameUsernames)){
				foreach($gameUsernames as $gameUsername => $playerTxns){
					$playerUsername = $this->game_api->getPlayerUsernameByGameUsername($gameUsername);
					$this->playerId = $playerId = $this->player_model->getPlayerIdByUsername($playerUsername);

					$playerData = array(
						"action" => $action,
						"playerId" => $playerId,
						"playerUsername" => $playerUsername,
						"gameUsername" => $gameUsername,
						"txns" => $playerTxns,

					);

					# Check if player exist
					if(empty($playerId) || empty($playerUsername)) {
						$response[$gameUsername] = array(
							"status" => '1000',
							"desc" => self::ERROR_CODE['1000'],
						);
					} else {
						$success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerData, &$response) { 
							return $this->processTip($playerData, $response);
						});
					}
				}
			}
		}
		if(!empty($response)){
			$response = count($gameUsernames) > 1 ? $response :  call_user_func_array('array_merge', $response);
		} else {
			$response = array(
				"status" => self::FAILED,
				"desc" => self::ERROR_CODE[self::FAILED],
			);
		}
		$this->CI->utils->debug_log('<-----------------------------Response Params:', $response);
		return $response;
	}

	protected function processTip($playerData, &$response){
		$success = false;
		$action = isset($playerData['action']) ? strtolower($playerData['action']) : null;
		$txns = isset($playerData['txns']) ? $playerData['txns'] : null;
		if(!empty($txns)){
			$beforeBalance = $this->game_api->queryPlayerBalance($playerData['playerUsername'])['balance'];

			if($action == "tip"){
				$totalTipAmount = array_sum(array_column($txns,'tip'));
				if($this->utils->compareResultFloat($totalTipAmount, '>', $beforeBalance)) {
					$response[$playerData['gameUsername']] = array(
						"status" => '1018',
						"desc" => self::ERROR_CODE["1018"],
					);
				} else {
					foreach ($txns as $key => $txn) {
						$tipRow = $this->sexy_baccarat_transactions->isRowByTransactionId($txn['platformTxId']);
						if(!empty($tipRow)){
							if($tipRow['action'] == "canceltip" ){
								$response[$playerData['gameUsername']] = array(
									"status" => self::STATUS_CODE_FAIL,
									"desc" => "Tip already cancelled.",
								);
								$success =false;
								break;
							}

							$response[$playerData['gameUsername']] = array(
								"status" => self::STATUS_CODE_TRANSACTION_EXIST,
								"desc" => self::ERROR_CODE[self::STATUS_CODE_TRANSACTION_EXIST],
							);
							$success =false;
							break;
						}

						$tipAmount = isset($txn['tip']) ? $txn['tip'] : null;
						if($this->utils->compareResultFloat($tipAmount, '>', 0)) {
			                $success = $this->wallet_model->decSubWallet($playerData['playerId'], $this->game_api->getPlatformCode(), $tipAmount); 
			            } elseif ($this->utils->compareResultFloat($tipAmount, '=', 0)) {
			                $success = true;#allowed tipAmount 0
			            } else { #default error
			                $response[$playerData['gameUsername']] = array(
								"status" => self::STATUS_CODE_INVALID_AMOUNT,
								"desc" => self::ERROR_CODE[self::STATUS_CODE_INVALID_AMOUNT],
							);
							$success =false;
							break;
			            }

			            if($success){
			            	$afterBalance = $this->game_api->queryPlayerBalance($playerData['playerUsername'])['balance'];
			            	$success = $this->processTransactionRecord($txn, $action, $beforeBalance, $afterBalance);
			            	$before_balance = $afterBalance;
			            	if(!$success){
			            		break;
			            	}
			            } else {
			            	$response[$playerData['gameUsername']] = array(
								"status" => self::STATUS_CODE_NOT_ENOUGH_BALANCE,
								"desc" => self::ERROR_CODE[self::STATUS_CODE_NOT_ENOUGH_BALANCE],
							);
			            }
					}

					if($success){
						$date = date_format(new DateTime(), 'Y-m-d\TH:i:s.vP');
						$response[$playerData['gameUsername']] = array(
							'status' => self::SUCCESS,
							'balanceTs' => $date,
							'balance' => $this->game_api->queryPlayerBalance($playerData['playerUsername'])['balance']
						);
					}
				}	
			} elseif($action == "canceltip"){
				foreach ($txns as $key => $txn) {
					$tipRow = $this->sexy_baccarat_transactions->isRowByTransactionId($txn['platformTxId']);
					if(!empty($tipRow)){
						if($tipRow['action'] == "tip" ){
							$uniqueId =  $action."-".$txn['platformTxId'];
							$cancelId = $action."-".$uniqueId;
							$cancelExist = $this->sexy_baccarat_transactions->isExternalUniqueExist($cancelId);
							if($cancelExist){
								$success = true; #always
								continue;
							} else {
								$txn['tip'] = isset($tipRow['tip_amount']) ? $tipRow['tip_amount'] : 0;
								$txn['platformTxId'] = $uniqueId;
							}
						} else {
							$success = true; #always
							continue;
						}
					}

					$tipAmount = isset($txn['tip']) ? $txn['tip'] : 0;
					if($this->utils->compareResultFloat($tipAmount, '>', 0)) {
		                $success = $this->wallet_model->incSubWallet($playerData['playerId'], $this->game_api->getPlatformCode(), $tipAmount); 
		            } elseif ($this->utils->compareResultFloat($tipAmount, '=', 0)) {
		                $success = true;#allowed tipAmount 0
		            } else { #default error
		                $success = false;
		            }

		            if($success){
		            	$afterBalance = $this->game_api->queryPlayerBalance($playerData['playerUsername'])['balance'];
		            	$success = $this->processTransactionRecord($txn, $action, $beforeBalance, $afterBalance);
		            	$before_balance = $afterBalance;
		            	if(!$success){
		            		break;
		            	}
		            } else {
		            	$response[$playerData['gameUsername']] = array(
							"status" => self::STATUS_CODE_NOT_ENOUGH_BALANCE,
							"desc" => self::ERROR_CODE[self::STATUS_CODE_NOT_ENOUGH_BALANCE],
						);
		            }
				}

				if($success){
					$date = date_format(new DateTime(), 'Y-m-d\TH:i:s.vP');
					$response[$playerData['gameUsername']] = array(
						'status' => self::SUCCESS,
						'balanceTs' => $date,
						'balance' => $this->game_api->queryPlayerBalance($playerData['playerUsername'])['balance']
					);
				}
				
			}
		}
		return $success;
    }


	/**
	*
	*	Sending Information to Single Wallet Site
	* 	Every bet request needs response "balance" parameter to update player balance on the page
	*	Any place bet failure occured will call "Cancel Bet" api to cancel this transaction
	*	Current Status of bet transaction is unsettled
	*
	*/
	public function playerBet($playerId, $playerUsername, $gameUsername, $fullRequest, $paramsValue) {
		$newDate = new DateTime();
		$date = date_format($newDate, 'Y-m-d\TH:i:s.vP');
		$controller = $this;
		$totalAmount = 0;
		foreach ($paramsValue as $paramValue) {
			if(isset($paramValue['userId'])) {
				$userId = $paramValue['userId'];
				$playerUsername = $this->game_api->getPlayerUsernameByGameUsername($userId);
				$this->playerId = $playerId = $this->player_model->getPlayerIdByUsername($playerUsername);
			}
		}

			# Check if player exist
			if(empty($playerId)) {
				throw new Exception('1000');
			}
			# Check if player exist
			if(empty($playerUsername)){
				throw new Exception('1002');
			}

			$status_code = [
				'code' => self::SUCCESS
			];
			$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$playerUsername,&$status_code,$fullRequest,&$paramsValue) {
				$betAmount = 0;

				$status_code = $controller->doDeduct($playerId,$playerUsername,$betAmount,$fullRequest,$paramsValue);

				if($status_code['code'] != self::SUCCESS) {
					return false;
				}

				if($status_code['code'] == self::SUCCESS && !isset($status_code['after_balance'])) {
					$status_code['code'] = '9999';
					return false;
				}

				return true;
			});

		$this->CI->utils->debug_log('<-----------------------------Status Code', $status_code);

		if($status_code['code'] != self::SUCCESS) {
			 throw new Exception($status_code['code']);
		}

		$response = array(
			'status' => $status_code['code'],
			'balanceTs' => $date,
			'balance' => isset($status_code['after_balance'])?$status_code['after_balance']:0
		);

		$this->CI->utils->debug_log('<-----------------------------Response Params:', $response);

		return $response;


	}



	/**
	*
	*	Players settlement is based in status is based on Game Information's status param.
	*	
	*/
	public function settle($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue) {

		$controller = $this;
		$game_usernames = [];
		foreach ($paramsValue as $paramValue) {
		    if(isset($paramValue['userId'])) {
		        $userId = $paramValue['userId'];
		        if (isset($game_usernames[$userId])) {
		            $playerId = $game_usernames[$userId];
		            if(isset($game_usernames[$playerId])) {
		            	$playerUsername = $game_usernames[$playerId];
		            } else {
		            	$playerUsername = $this->game_api->getPlayerUsernameByGameUsername($userId);
		            }
		        }
		        else {
		            $playerUsername = $this->game_api->getPlayerUsernameByGameUsername($userId);
		            $this->playerId = $playerId = $this->player_model->getPlayerIdByUsername($playerUsername);
		            $game_usernames[$userId] = $playerId;
		            $game_usernames[$playerId] = $playerUsername;
		        }
		    }

			# Check if player exist
			if(empty($playerId)) {
				throw new Exception('1000');
			}
			# Check if player exist
			if(empty($playerUsername)){
				throw new Exception('1002');
			}

			$status_code = [
				'code' => self::SUCCESS
			];
			$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$playerUsername,&$status_code,$fullRequest,&$paramValue) {
				$betStatus = isset($paramValue['gameInfo']['status']) ? $paramValue['gameInfo']['status']: null;
				$betAmount = isset($paramValue['betAmount']) ? $paramValue['betAmount'] : 0;
				$winAmount = isset($paramValue['winAmount']) ? $paramValue['winAmount'] : null;
				$transaction_id = isset($paramValue['platformTxId']) ? $paramValue['platformTxId'] : null;
				$existingRow = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'bet');

				# check if Transaction ID is existing
				if(empty($existingRow)){
					$status_code['code'] = '1017';
					return false;
				}

				# check if bet Transaction ID bet already settled
				if($existingRow['action_status']==Game_logs::STATUS_SETTLED){
					$status_code['code'] = '0000';
					return true;
				}

				# check if bet Transaction ID bet already cancelled
				if($existingRow['action_status']==Game_logs::STATUS_CANCELLED){
					$status_code['code'] = '1017';
					return false;
				}

				# check if bet Transaction ID bet already cancelled
				if($existingRow['action_status']==Game_logs::STATUS_VOID){
					$status_code['code'] = '1017';
					return false;
				}

				if($betStatus == 'LOSE') {
					$status_code = $controller->doDeduct($playerId,$playerUsername,$winAmount,$fullRequest,$paramValue);
				} else {
					$status_code = $controller->doIncrement($playerId,$playerUsername,$winAmount,$fullRequest,$paramValue);
				} 

				if($status_code['code'] != self::SUCCESS) {
					return false;
				}

				//flag bet transaction as cancelled
				$updateData = ['action_status'=>Game_logs::STATUS_SETTLED];
				$updateResp = $this->sexy_baccarat_transactions->updateTransactionData($transaction_id, 'bet', $updateData);
				if(!$updateResp){
					$status_code['code'] = '9999';
					return false;
				}

				return true;

			});

		}

		$this->CI->utils->debug_log('<-----------------------------Status Code', $status_code);

		if($status_code['code'] != self::SUCCESS) {
			 throw new Exception($status_code['code']);
		}


		$response = array(
			'status' => $status_code['code']
		);

		$this->CI->utils->debug_log('<-----------------------------Response Params:', $response);
		return $response;

	}



	/**
	*
	*	When technical issues are found this api will be triggered. Here are some sample:
	*	- If provider found out the result is wrong
	*	- If found out some dealer mistakes/input wrong result
	*
	*/
	public function unsettle($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue) {
		
		$controller = $this;
		foreach ($paramsValue as $paramValue) {
			if(isset($paramValue['userId'])) {
		        $userId = $paramValue['userId'];
		        if (isset($game_usernames[$userId])) {
		            $playerId = $game_usernames[$userId];
		        }
		        else {
		            $playerUsername = $this->game_api->getPlayerUsernameByGameUsername($userId);
		            $this->playerId = $playerId = $this->player_model->getPlayerIdByUsername($playerUsername);
		            $game_usernames[$userId] = $playerId;
		        }
		    }

			# Check if player exist
			if(empty($playerId)) {
				throw new Exception('1000');
			}
			# Check if player exist
			if(empty($playerUsername)){
				throw new Exception('1002');
			}

			$status_code = [
				'code' => self::SUCCESS
			];
			$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$playerUsername,&$status_code,$fullRequest,&$paramValue) {
				$betAmount = isset($paramValue['betAmount']) ? $paramValue['betAmount'] : null;
				$transaction_id = isset($paramValue['platformTxId']) ? $paramValue['platformTxId'] : null;
				
				//get settle transaction
				$existingRow = $controller->sexy_baccarat_transactions->isRowBySettleId($transaction_id);


				# check if there is exsiting settle
				if(empty($existingRow)){					
					$status_code['code'] = '1017';
					$this->CI->utils->error_log('<----------------------------- unsettle rejected bet cannot find:', $status_code, 'existingRow', $existingRow);
					return false;
				}

				if(isset($paramValue['gameInfo']['status'])) {
					$betStatus = $paramValue['gameInfo']['status'];
				} else {
					$betStatus = json_decode($existingRow['gameInfo'],true)['status'];
				}

				if(isset($paramValue['gameInfo']['winLoss'])) {
					$betAndWinLoss = $betAmount + $paramValue['gameInfo']['winLoss'];
				} else {
					$betAndWinLoss = $existingRow['winAmount'];
				}

				$unsettledTransactions = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'unsettle');
				if(!empty($unsettledTransactions)){					
					$status_code['code'] = '0000';
					$this->CI->utils->error_log('<----------------------------- unsettle rejected unsettled already:', $status_code, 'unsettledTransactions', $unsettledTransactions);
					return false;
				}

				$betTransactions = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'bet');
				if(empty($betTransactions)){
					$status_code['code'] = '1017';
					return false;
				}

				//allow only settled bet to unsettle
				if($betTransactions['action_status']<>Game_logs::STATUS_SETTLED){
					$status_code['code'] = '1017';
					$this->CI->utils->error_log('<----------------------------- unsettle rejected:', $status_code, 'betTransactions', $betTransactions);
					return false;
				}

				//flag settle transaction as cancelled
				$settleNewUnique = 'settle-'.$transaction_id.'-unsettled';
				$updateData = ['action_status'=>Game_logs::STATUS_CANCELLED, 'external_uniqueid'=>$settleNewUnique];
				$updateResp = $this->sexy_baccarat_transactions->updateTransactionData($transaction_id, 'settle', $updateData);
				if(!$updateResp){
					$status_code['code'] = '9999';
					return false;
				}

				//flag bet transaction as unsettled
				$updateData = ['action_status'=>Game_logs::STATUS_UNSETTLED];
				$updateResp = $this->sexy_baccarat_transactions->updateTransactionData($transaction_id, 'bet', $updateData);
				if(!$updateResp){
					$status_code['code'] = '9999';
					return false;
				}

				//check if there is existing unsettle
				$existingUnsettle = $controller->sexy_baccarat_transactions->isUnsettleIdExist($transaction_id);
				if(!$existingUnsettle) {
					if($betStatus == 'WIN') {
						$status_code = $controller->doDeduct($playerId,$playerUsername,$betAndWinLoss,$fullRequest,$paramValue);
					} else if($betStatus == 'TIE') {
						$status_code = $controller->doDeduct($playerId,$playerUsername,$betAmount,$fullRequest,$paramValue);
					} else {
						$status_code = $controller->doIncrement($playerId,$playerUsername,0,$fullRequest,$paramValue);
					}
				}

				if($status_code['code'] != self::SUCCESS) {
					return false;
				}

				return true;
			});

		}

		$this->CI->utils->debug_log('<-----------------------------Status Code', $status_code);
		if($status_code['code'] != self::SUCCESS) {
			 throw new Exception($status_code['code']);
		}


		$response = array(
			'status' => $status_code['code'],
		);
		$this->CI->utils->debug_log('<-----------------------------Response Params:', $response);

		return $response;

	}



	/**
	*
	*	If round found some technical issue during transaction, before it will be settled
	*	It will be voided directly.
	*
	*/
	public function voidBet($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue) {

		$controller = $this;
		foreach ($paramsValue as $paramValue) {
			if(isset($paramValue['userId'])) {
		        $userId = $paramValue['userId'];
		        if (isset($game_usernames[$userId])) {
		            $playerId = $game_usernames[$userId];
		        }
		        else {
		            $playerUsername = $this->game_api->getPlayerUsernameByGameUsername($userId);
		            $this->playerId = $playerId = $this->player_model->getPlayerIdByUsername($playerUsername);
		            $game_usernames[$userId] = $playerId;
		        }
		    }

			# Check if player exist
			if(empty($playerId)) {
				throw new Exception('1000');
			}
			# Check if player exist
			if(empty($playerUsername)){
				throw new Exception('1002');
			}

			$status_code = [
				'code' => self::SUCCESS
			];
			$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$playerUsername,&$status_code,$fullRequest,&$paramValue) {
				$betAmount = isset($paramValue['betAmount']) ? $paramValue['betAmount'] : null;
				$transaction_id = isset($paramValue['platformTxId']) ? $paramValue['platformTxId'] : null;

				$existingRow = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'bet');
				# check if Transaction ID does not exist
				if(empty($existingRow)){
					$status_code['code'] = '1017';
					return false;
				}

				//cehck if bet is already voided return success
				if($existingRow['action_status']==Game_logs::STATUS_VOID){
					$status_code['code'] = '0000';
					return true;
				}

				//cehck if bet is already settled, cancelled
				if($existingRow['action_status']==Game_logs::STATUS_SETTLED ||
				$existingRow['action_status']==Game_logs::STATUS_CANCELLED){
					$status_code['code'] = '1017';
					return false;
				}

				//flag bet transaction as void
				$transaction_id = isset($paramValue['platformTxId']) ? $paramValue['platformTxId'] : null;
				$updateData = ['action_status'=>Game_logs::STATUS_VOID];
				$updateResp = $this->sexy_baccarat_transactions->updateTransactionData($transaction_id, 'bet', $updateData);
				if(!$updateResp){
					$status_code['code'] = '9999';
					return false;
				}

				$status_code = $this->doIncrement($playerId,$playerUsername,$betAmount,$fullRequest,$paramValue);

				if($status_code['code'] != self::SUCCESS) {
					return false;
				}

				return true;
			});

		}

		$this->CI->utils->debug_log('<-----------------------------Status Code', $status_code);
		if($status_code['code'] != self::SUCCESS) {
			 throw new Exception($status_code['code']);
		}

		$response = array(
			'status' => $status_code['code'],
		);
		$this->CI->utils->debug_log('<-----------------------------Response Params:', $response);

		return $response;

	}


	public function unvoidBet($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue) {

		$controller = $this;
		foreach ($paramsValue as $paramValue) {
			if(isset($paramValue['userId'])) {
				$userId = $paramValue['userId'];
				$playerUsername = $this->game_api->getPlayerUsernameByGameUsername($userId);
				$this->playerId = $playerId = $this->player_model->getPlayerIdByUsername($playerUsername);
			}

			# Check if player exist
			if(empty($playerId)) {
				throw new Exception('1000');
			}
			# Check if player exist
			if(empty($playerUsername)){
				throw new Exception('1002');
			}

			$status_code = [
				'code' => self::SUCCESS
			];
			$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$playerUsername,&$status_code,$fullRequest,&$paramValue) {
				$transaction_id = isset($paramValue['platformTxId']) ? $paramValue['platformTxId'] : null;

				//get bet
				$existingBetRow = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'bet');
				if(empty($existingBetRow)){
					$status_code['code'] = '1017';
					$this->CI->utils->error_log('<----------------------------- unVoidBet rejected cannot find bet:', $status_code, 'existingBetRow', $existingBetRow);
					return true;
				}

				//get existing voidBet
				$existingVoidBetRow = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'voidBet');
				if(empty($existingVoidBetRow)){
					$status_code['code'] = '1017';
					$this->CI->utils->error_log('<----------------------------- unVoidBet rejected bet cannot find voidBet:', $status_code, 'existingVoidBetRow', $existingVoidBetRow);
					return true;
				}

				# check if already unvoid bet
				$existingUnvoidBetRow = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'unvoidBet');
				if(!empty($existingUnvoidBetRow)){
					$status_code['code'] = '0000';
					$this->CI->utils->info_log('<----------------------------- unVoidBet rejected already have unvoidBet:', $status_code, 'existingUnvoidBetRow', $existingUnvoidBetRow);
					return true;
				}

				//do decrement
				$paramValue['betAmount'] = $betAmount = $existingBetRow['betAmount'];
				$status_code = $controller->doDeduct($playerId,$playerUsername,$betAmount,$fullRequest,$paramValue);

				if($status_code['code'] != self::SUCCESS) {
					return false;
				}

				//flag bet transaction as pending if unvoided
				$updateData = ['action_status'=>Game_logs::STATUS_PENDING];
				$updateResp = $this->sexy_baccarat_transactions->updateTransactionData($transaction_id, 'bet', $updateData);
				if(!$updateResp){
					$status_code['code'] = '9999';
					return false;
				}

				/*$paramsValue = array();
				$paramsValue['action'] = 'unvoidBet';
				$updateLogs = $this->sexy_baccarat_transactions->updateGameLog($existingRow['id'],$paramsValue);

				if($status_code['code'] != self::SUCCESS) {
					return false;
				}*/

				return true;
			});

		}

		$this->CI->utils->debug_log('<-----------------------------Status Code', $status_code);
		if($status_code['code'] != self::SUCCESS) {
			 throw new Exception($status_code['code']);
		}

		$response = array(
			'status' => $status_code['code'],
		);
		$this->CI->utils->debug_log('<-----------------------------Response Params:', $response);

		return $response;

	}



	/**
	*
	*	If player found out have suspicious behavior, this api will be called.
	*
	*/
	public function voidSettle($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue) {

		$controller = $this;
		foreach ($paramsValue as $paramValue) {
			if(isset($paramValue['userId'])) {
				$userId = $paramValue['userId'];
				$playerUsername = $this->game_api->getPlayerUsernameByGameUsername($userId);
				$this->playerId = $playerId = $this->player_model->getPlayerIdByUsername($playerUsername);
			}

			# Check if player exist
			if(empty($playerId)) {
				throw new Exception('1000');
			}
			# Check if player exist
			if(empty($playerUsername)){
				throw new Exception('1002');
			}

			$status_code = [
				'code' => self::SUCCESS
			];
			$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$playerUsername,&$status_code,$fullRequest,&$paramValue) {
				$betAmount = isset($paramValue['betAmount']) ? $paramValue['betAmount'] : null;
				$transaction_id = isset($paramValue['platformTxId']) ? $paramValue['platformTxId'] : null;

				//check if there is settle
				$existingRow = $this->sexy_baccarat_transactions->isRowBySettleId($transaction_id);
				# check if Transaction has a settle to voidSettle
				if(empty($existingRow)){
					$status_code['code'] = '1017';
					return false;
				}
				//check if settle already void
				if($existingRow['action_status']==Game_logs::STATUS_VOID){
					$status_code['code'] = '0000';
					return true;
				}

				//check if existing void settle
				$existingVoidSettleRow = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'voidSettle');				
				if(!empty($existingVoidSettleRow)){
					$status_code['code'] = '0000';
					return true;
				}

				//check if existing bet
				$existingBetRow = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'bet');				
				if(empty($existingBetRow)){
					$status_code['code'] = '1017';
					return false;
				}

				//cehck if bet is already voided return success
				if($existingBetRow['action_status']==Game_logs::STATUS_VOID){
					$status_code['code'] = '0000';
					return true;
				}

				//flag settle transaction as void
				$transaction_id = isset($paramValue['platformTxId']) ? $paramValue['platformTxId'] : null;
				$updateData = ['action_status'=>Game_logs::STATUS_VOID];
				$updateResp = $this->sexy_baccarat_transactions->updateTransactionData($transaction_id, 'settle', $updateData);
				if(!$updateResp){
					$status_code['code'] = '9999';
					return false;
				}

				//flag settle transaction as void
				$transaction_id = isset($paramValue['platformTxId']) ? $paramValue['platformTxId'] : null;
				$updateData = ['action_status'=>Game_logs::STATUS_VOID];
				$updateResp = $this->sexy_baccarat_transactions->updateTransactionData($transaction_id, 'bet', $updateData);
				if(!$updateResp){
					$status_code['code'] = '9999';
					return false;
				}


				$status = json_decode($existingRow['gameInfo'], true);
				if($status['status'] == 'LOSE') {
					$status_code = $this->doIncrement($playerId,$playerUsername,$betAmount,$fullRequest,$paramValue);
				} else {
					$betAndWinLoss = $existingRow['winAmount'] - $betAmount;
					$status_code = $this->doDeduct($playerId,$playerUsername,$betAndWinLoss,$fullRequest,$paramValue);
				}

				if($status_code['code'] != self::SUCCESS) {
					return false;
				}

				return true;
			});

		}

		$this->CI->utils->debug_log('<-----------------------------Status Code', $status_code);
		if($status_code['code'] != self::SUCCESS) {
			 throw new Exception($status_code['code']);
		}

		$response = array(
			'status' => $status_code['code'],
		);
		$this->CI->utils->debug_log('<-----------------------------Response Params:', $response);

		return $response;

	}


	public function unvoidSettle($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue) {

		$controller = $this;
		foreach ($paramsValue as $paramValue) {
			if(isset($paramValue['userId'])) {
				$userId = $paramValue['userId'];
				$playerUsername = $this->game_api->getPlayerUsernameByGameUsername($userId);
				$this->playerId = $playerId = $this->player_model->getPlayerIdByUsername($playerUsername);
			}

			# Check if player exist
			if(empty($playerId)) {
				throw new Exception('1000');
			}
			# Check if player exist
			if(empty($playerUsername)){
				throw new Exception('1002');
			}

			$status_code = [
				'code' => self::SUCCESS
			];
			$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$playerUsername,&$status_code,$fullRequest,&$paramValue) {
				$transaction_id = isset($paramValue['platformTxId']) ? $paramValue['platformTxId'] : null;


				//check if unvoid settle
				$existingUnvoidRow = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'unvoidSettle');				
				if(empty($existingUnvoidRow)){
					$status_code['code'] = '0000';
					return true;
				}

				//check if existing bet
				$existingBetRow = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'bet');				
				if(empty($existingBetRow)){
					$status_code['code'] = '1017';
					return false;
				}

				$betAmount = $existingBetRow['betAmount'];

				//check if existing settle
				$existingSettleRow = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'settle');				
				if(empty($existingSettleRow)){
					$status_code['code'] = '1017';
					return false;
				}

				//check if existing void settle
				$existingVoidSettleRow = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'voidSettle');				
				if(empty($existingVoidSettleRow)){
					$status_code['code'] = '1017';
					return false;
				}


				$existingRow = $this->sexy_baccarat_transactions->isRowByVoidedTransactionId($transaction_id);
				# check if Transaction ID does not exist
				if(empty($existingRow)){
					$status_code['code'] = '1017';
					return false;
				}

				$status = json_decode($existingSettleRow['gameInfo'], true);
				if($status['status'] == 'LOSE') {
					$status_code = $this->doIncrement($playerId,$playerUsername,$betAmount,$fullRequest,$paramValue);
				} else {
					$betAndWinLoss = $existingSettleRow['winAmount'] - $betAmount;					
					$status_code = $this->doDeduct($playerId,$playerUsername,$betAndWinLoss,$fullRequest,$paramValue);
				}

				//flag bet transaction as settled
				$updateData = ['action_status'=>Game_logs::STATUS_SETTLED];
				$updateResp = $this->sexy_baccarat_transactions->updateTransactionData($transaction_id, 'bet', $updateData);
				if(!$updateResp){
					$status_code['code'] = '9999';
					return false;
				}

				//flag settle transaction as settled
				$updateData = ['action_status'=>Game_logs::STATUS_SETTLED];
				$updateResp = $this->sexy_baccarat_transactions->updateTransactionData($transaction_id, 'settle', $updateData);
				if(!$updateResp){
					$status_code['code'] = '9999';
					return false;
				}

				return true;
			});

		}

		$this->CI->utils->debug_log('<-----------------------------Status Code', $status_code);
		if($status_code['code'] != self::SUCCESS) {
			 throw new Exception($status_code['code']);
		}

		$response = array(
			'status' => $status_code['code'],
		);
		$this->CI->utils->debug_log('<-----------------------------Response Params:', $response);

		return $response;


	}



	/**
	*
	*	This api will be called when they dont receive our response in time.
	*	Usually network issue.
	*
	*/
	public function cancelBet($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue) {

		$newDate = new DateTime();
		$date = date_format($newDate, 'Y-m-d\TH:i:s.vP');
		$controller = $this;
		foreach ($paramsValue as $paramValue) {
			if(isset($paramValue['userId'])) {
				$userId = $paramValue['userId'];
				$playerUsername = $this->game_api->getPlayerUsernameByGameUsername($userId);
				$this->playerId = $playerId = $this->player_model->getPlayerIdByUsername($playerUsername);
			}
		}

		# Check if player exist
		if(empty($playerId)) {
			throw new Exception('1000');
		}
		# Check if player exist
		if(empty($playerUsername)){
			throw new Exception('1002');
		}

		$status_code = [
			'code' => self::SUCCESS
		];
		$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$playerUsername,&$status_code,$fullRequest,&$paramsValue) {
			$betAmount = 0;

			//flag bet transaction as cancelled
			/*$transaction_id = isset($paramValue['platformTxId']) ? $paramValue['platformTxId'] : null;
			$updateData = ['action_status'=>Game_logs::STATUS_CANCELLED];
			$updateResp = $this->sexy_baccarat_transactions->updateTransactionData($transaction_id, 'bet', $updateData);
			if(!$updateResp){
				$status_code['code'] = '9999';
				return false;
			}*/

			$status_code = $this->doIncrement($playerId,$playerUsername,$betAmount,$fullRequest,$paramsValue);

			if($status_code['code'] != self::SUCCESS) {
				return false;
			}

			return true;
		});


		$this->CI->utils->debug_log('<-----------------------------Status Code', $status_code);
		if($status_code['code'] != self::SUCCESS) {
			 throw new Exception($status_code['code']);
		}

		if(!$success) {
			throw new Exception('9998');
		}

		$response = array(
			'status' => $status_code['code'],
			'balance' => $status_code['after_balance'],
			'balanceTs' => $date,
		);
		$this->CI->utils->debug_log('<-----------------------------Response Params:', $response);

		return $response;

	}



	/**
	*
	*	When odds change or odds is minus will call Adjust Bet on placing bet
	*	
	*/
	public function adjustBet($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue) {

		$newDate = new DateTime();
		$date = date_format($newDate, 'Y-m-d\TH:i:s.vP');
		$controller = $this;
		foreach ($paramsValue as $paramValue) {
			if(isset($paramValue['userId'])) {
				$userId = $paramValue['userId'];
				$playerUsername = $this->game_api->getPlayerUsernameByGameUsername($userId);
				$this->playerId = $playerId = $this->player_model->getPlayerIdByUsername($playerUsername);
			}

			# Check if player exist
			if(empty($playerId)) {
				throw new Exception('1000');
			}
			# Check if player exist
			if(empty($playerUsername)){
				throw new Exception('1002');
			}

			$status_code = [
				'code' => self::SUCCESS
			];
			$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$playerUsername,&$status_code,$fullRequest,&$paramValue) {
				$transaction_id = isset($paramValue['platformTxId']) ? $paramValue['platformTxId'] : null;
				$adjustAmount = isset($paramValue['adjustAmount']) ? $paramValue['adjustAmount'] : null;
				$betAmount = isset($existingRow['betAmount']) ? $existingRow['betAmount'] : 0;

				$existingRow = $this->sexy_baccarat_transactions->isTransactionIdAlreadyExists($transaction_id);
				# check if Transaction ID does not exist
				if(!$existingRow){
					$status_code['code'] = '1016';
					return false;
				}

				$status_code = $this->doIncrement($playerId,$playerUsername,$adjustAmount,$fullRequest,$paramValue);
				
				if($status_code['code'] != self::SUCCESS) {
					return false;
				}

				return true;
			});

		}

		$this->CI->utils->debug_log('<-----------------------------Status Code', $status_code);
		if($status_code['code'] != self::SUCCESS) {
			 throw new Exception($status_code['code']);
		}


		$response = array(
			'status' => $status_code['code'],
			'balance' => $status_code['after_balance'],
			'balanceTs' => $date,
		);
		$this->CI->utils->debug_log('<-----------------------------Response Params:', $response);

		return $response;

	}


	public function refund($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue) {

		$controller = $this;
		foreach ($paramsValue as $paramValue) {
			if(isset($paramValue['userId'])) {
				$userId = $paramValue['userId'];
				$playerUsername = $this->game_api->getPlayerUsernameByGameUsername($userId);
				$this->playerId = $playerId = $this->player_model->getPlayerIdByUsername($playerUsername);
			}

			# Check if player exist
			if(empty($playerId)) {
				throw new Exception('1000');
			}
			# Check if player exist
			if(empty($playerUsername)){
				throw new Exception('1002');
			}

			$status_code = [
				'code' => self::SUCCESS
			];
			$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$playerUsername,&$status_code,$fullRequest,&$paramValue) {
				$betStatus = isset($paramValue['gameInfo']['status']) ? $paramValue['gameInfo']['status']: null;
				$betAmount = isset($paramValue['betAmount']) ? $paramValue['betAmount'] : null;
				$winAmount = isset($paramValue['winAmount']) ? $paramValue['winAmount'] : null;
				$transaction_id = isset($paramValue['refundPlatformTxId']) ? $paramValue['refundPlatformTxId'] : null;

				$existingRow = $this->sexy_baccarat_transactions->isRowByTransactionId($transaction_id);
				# check if Transaction ID does not exist
				if(empty($existingRow)){
					$status_code['code'] = '1017';
					return false;
				}

				if($betStatus == 'WIN') {
				$status_code = $this->doIncrement($playerId,$playerUsername,$winAmount,$fullRequest,$paramValue);
				} else if($betStatus == 'LOSE') {
					$status_code = $this->doDeduct($playerId,$playerUsername,$winAmount,$fullRequest,$paramValue);
				} else {
					$status_code = $this->doIncrement($playerId,$playerUsername,$betAmount,$fullRequest,$paramValue);
				}

				if($status_code['code'] != self::SUCCESS) {
					return false;
				}

				return true;
			});

		}

		$this->CI->utils->debug_log('<-----------------------------Status Code', $status_code);
		if($status_code['code'] != self::SUCCESS) {
			 throw new Exception($status_code['code']);
		}

		$response = array(
			'status' => $status_code['code'],
		);
		$this->CI->utils->debug_log('<-----------------------------Response Params:', $response);

		return $response;

	}



	/**
	*
	*	Sending transactions when user gets bonus from events.
	*	
	*/
	public function give($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue) {

		$controller = $this;
		$game_usernames = [];
		foreach ($paramsValue as $paramValue) {
		    if(isset($paramValue['userId'])) {
		        $userId = $paramValue['userId'];
		        if (isset($game_usernames[$userId])) {
		            $playerId = $game_usernames[$userId];
		            if(isset($game_usernames[$playerId])) {
		            	$playerUsername = $game_usernames[$playerId];
		            } else {
		            	$playerUsername = $this->game_api->getPlayerUsernameByGameUsername($userId);
		            }
		        }
		        else {
		            $playerUsername = $this->game_api->getPlayerUsernameByGameUsername($userId);
		            $this->playerId = $playerId = $this->player_model->getPlayerIdByUsername($playerUsername);
		            $game_usernames[$userId] = $playerId;
		            $game_usernames[$playerId] = $playerUsername;
		        }
		    }

			# Check if player exist
			if(empty($playerId)) {
				throw new Exception('1000');
			}
			# Check if player exist
			if(empty($playerUsername)){
				throw new Exception('1002');
			}

			$status_code = [
				'code' => self::SUCCESS
			];
			$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$playerUsername,&$status_code,$fullRequest,&$paramValue) {
				$betStatus = isset($paramValue['gameInfo']['status']) ? $paramValue['gameInfo']['status']: null;
				$winAmount = isset($paramValue['amount']) ? $paramValue['amount'] : 0;
				$transaction_id = isset($paramValue['promotionTxId']) ? $paramValue['promotionTxId'] : null;
				$existingRow = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'give');

				# check if give Transaction ID already exist
				if(!empty($existingRow)){
					$status_code['code'] = '0000';
					return true;
				}

				$status_code = $controller->doIncrement($playerId,$playerUsername,$winAmount,$fullRequest,$paramValue);

				if($status_code['code'] != self::SUCCESS) {
					return false;
				}

				return true;

			});

		}

		$this->CI->utils->debug_log('<-----------------------------Status Code', $status_code);

		if($status_code['code'] != self::SUCCESS) {
			 throw new Exception($status_code['code']);
		}


		$response = array(
			'status' => $status_code['code']
		);

		$this->CI->utils->debug_log('<-----------------------------Response Params:', $response);

		return $response;

	}

	public function getBalance($gameUsername) {
		$playerName = $this->game_api->getPlayerUsernameByGameUsername($gameUsername);
		$playerBalance = $this->game_api->queryPlayerBalance($playerName);
		$newDate = new DateTime();
		$date = date_format($newDate, 'Y-m-d\TH:i:s.vP');

		$response = array(
			'status' => self::SUCCESS,
			'userId' => $gameUsername,
			'balance' => $playerBalance['balance'],
			'balanceTs' => $date
		);

		$this->CI->utils->debug_log('<-----------------------------Response Params:', $response);

		return $response;
	}

	protected function doDeduct($playerId,$playerUsername,$betAmount,$fullRequest,$data){
		$action = $fullRequest['action'];
        $totalAmount=0;

        $status_code = [
            'code' => self::SUCCESS
        ];

        $before_balance = $this->game_api->queryPlayerBalance($playerUsername);

        if($action == 'bet' || $action == 'unvoidBet') {
			$pendingCancel = [];

        	foreach ($data as $trans_record) {
        		$totalAmount += $trans_record['betAmount'];
    			$transaction_id = isset($trans_record['platformTxId']) ? $trans_record['platformTxId'] : null;
				$existingRow = $this->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'bet');

				# check if Transaction ID bet is existing
				if($existingRow){
					$status_code['code'] = '1016';
					return $status_code;
				}

				//get pending cancel
				$existingCancelRow = $this->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'cancelBet');
				if(!empty($existingCancelRow) && $existingCancelRow['action_status']==Game_logs::STATUS_PENDING){
					$pendingCancel[] = $existingCancelRow;
				}
        	}
			$this->CI->utils->debug_log('<-----------------------------bermar pendingCancel:', $pendingCancel);

        	if($totalAmount > $before_balance['balance']) {
        		$status_code['code'] = '1018';
        		return $status_code;
        	} else {
        		foreach ($data as $trans_record) {
					$before_balance = $this->game_api->queryPlayerBalance($playerUsername);
        			$betAmount = $trans_record['betAmount'];
					$isDeduct = $this->wallet_model->decSubWallet($playerId, $this->gamePlatformId,$betAmount);
					if($isDeduct){
        				$status_code['after_balance'] = $this->game_api->queryPlayerBalance($playerUsername)['balance'];
						$trans_record['action_status'] = Game_logs::STATUS_PENDING;
						
						//process pending cancel
						foreach($pendingCancel as $cancel){
							if($cancel['platformTxId']==$trans_record['platformTxId']){
								//increment balance
								$isIncrement = $this->wallet_model->incSubWallet($playerId, $this->gamePlatformId,$betAmount);
								if($isIncrement == false) {
									$status_code['code'] = '9999';
									return false;
								}else{
									$cancelBeforeBalance = $status_code['after_balance'];
									$status_code['after_balance'] = $cancelAfterBalance = $status_code['after_balance'] + abs($betAmount);
									//update cancel to settled cancel
									$updateData = ['action_status'=>Game_logs::STATUS_SETTLED, 
									'before_balance'=>$cancelBeforeBalance, 
									'after_balance'=>$cancelAfterBalance];
									$updateResp = $this->sexy_baccarat_transactions->updateTransactionData($transaction_id, 'cancelBet', $updateData);
									if(!$updateResp){
										$status_code['code'] = '9999';
										return false;
									}
									$this->CI->utils->debug_log('<-----------------------------bermar pendingCancel updateTransactionData', 
									$updateData, 'transaction_id',
									$transaction_id);
	
									//flag bet cancel
									$trans_record['action_status'] = Game_logs::STATUS_CANCELLED;
								}	
								
							}
						}						
						
			            $insertResponse = $this->processTransactionRecord($trans_record,$action,$before_balance['balance'],$status_code['after_balance']);
						if(!$insertResponse){
							$status_code['code'] = '9999';
						}
			        	
			        	$this->CI->utils->debug_log("Sexy Baccarat deduct to subwallet is: ", $isDeduct, "Balance After Deduct: ", $status_code['after_balance']);
			        } else {
			        	$status_code['code'] = '9999';
						return $status_code;
			        }

    			}

				

			    return $status_code;
        	}
        }

		if($action == 'voidSettle') {
			$totalAmount += $betAmount;

			if($totalAmount > $before_balance['balance']) {
				$status_code['code'] = '1018';
				return $status_code;
			}

			$isDeduct = $this->wallet_model->decSubWallet($playerId, $this->gamePlatformId,$totalAmount);
			if($isDeduct || $totalAmount==0){
				$status_code['after_balance'] = $this->game_api->queryPlayerBalance($playerUsername)['balance'];
				$status_code['code'] = '0000';
				$this->processTransactionRecord($data,$action,$before_balance['balance'],$status_code['after_balance']);
				
				$this->CI->utils->debug_log("Sexy Baccarat deduct to subwallet is: ", $isDeduct, "Balance After Deduct: ", $status_code['after_balance']);
			} else {
				$status_code['code'] = '9999';
			}

			return $status_code;
        }


        $isDeduct = $this->wallet_model->decSubWallet($playerId, $this->gamePlatformId,$betAmount);
        if($isDeduct == false && isset($data['winAmount']) && $data['winAmount'] == 0 && $action !== 'resettle') {
        	$isDeduct = true;
        }

        if($isDeduct){

            $status_code['after_balance'] = $this->game_api->queryPlayerBalance($playerUsername)['balance'];

            $this->processTransactionRecord($data,$action,$before_balance['balance'],$status_code['after_balance']);
        	
        	$this->CI->utils->debug_log("Sexy Baccarat deduct to subwallet is: ", $isDeduct, "Balance After Deduct: ", $status_code['after_balance']);
        } else {
        	$status_code['code'] = self::STATUS_CODE_FAIL;
        	if($action == 'resettle' && $this->utils->compareResultFloat($before_balance, '<', $betAmount)) {
				$status_code['code'] = self::SUCCESS;
				$status_code['after_balance'] = $this->game_api->queryPlayerBalance($playerUsername)['balance'];
				$data['resettle_is_rejected'] = true;
            	$this->processTransactionRecord($data,$action,$before_balance['balance'],$status_code['after_balance']);
        		$this->CI->utils->debug_log("Sexy Baccarat deduct to subwallet is: ", $isDeduct, "Balance After Deduct: ", $status_code['after_balance']);
			}
        }

        return $status_code;
    }

    protected function doIncrement($playerId,$playerUsername,$addAmount,$fullRequest,$data){

        $action = $fullRequest['action'];
        
        $status_code = [
            'code' => self::SUCCESS
        ];

        if($action == 'cancelBet') {
    		foreach ($data as $trans_record) {
				$transaction_id = isset($trans_record['platformTxId']) ? $trans_record['platformTxId'] : null;
				$trans_record['action_status'] = Game_logs::STATUS_SETTLED;

				$before_balance = $this->game_api->queryPlayerBalance($playerUsername);

				//check if cancelAlready processed					
				$isCancelledAlready = $this->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'cancelBet');
				//cancelles action_status=settled meaning the cancel is valid
				if($isCancelledAlready&&$isCancelledAlready['action_status']==Game_logs::STATUS_SETTLED){					
					$status_code['after_balance'] = $this->game_api->queryPlayerBalance($playerUsername)['balance'];
					$status_code['code'] = '0000';
					return $status_code;					
				}

				$existingRow = $this->sexy_baccarat_transactions->isRowByBetTransactionId($transaction_id);
				# check if bet exist, if not save cancelbet
				if(empty($existingRow)){
					//if bet does not exist return success, save transactions as pending and do not update balance
					$status_code['after_balance'] = $this->game_api->queryPlayerBalance($playerUsername)['balance'];
					$status_code['code'] = '0000';
					$trans_record['action_status'] = Game_logs::STATUS_PENDING;
					$this->processTransactionRecord($trans_record,$action,$before_balance['balance'],$status_code['after_balance']);

					return $status_code;
				}

    			
    			$betAmount = isset($existingRow['betAmount']) ? $existingRow['betAmount'] : 0;
    			$isIncrement = $this->wallet_model->incSubWallet($playerId, $this->gamePlatformId,$betAmount);
    			if($isIncrement){
    				 $status_code['after_balance'] = $this->game_api->queryPlayerBalance($playerUsername)['balance'];

					 $insertResponse = $this->processTransactionRecord($trans_record,$action,$before_balance['balance'],$status_code['after_balance']);
					if(!$insertResponse){
						$status_code['code'] = '9999';
						return $status_code;
					}
		        	$this->CI->utils->debug_log("Sexy Baccarat increment to subwallet is: ", $isIncrement, "Balance After Increment: ", $status_code['after_balance']);
		        } else {
		        	$status_code['code'] = '9999';
		        }

				//flag bet cancelled
				$updateData = ['action_status'=>Game_logs::STATUS_CANCELLED];
				$updateResp = $this->sexy_baccarat_transactions->updateTransactionData($transaction_id, 'bet', $updateData);
				if(!$updateResp){
					$this->CI->utils->debug_log("Sexy Baccarat error update bet transaction as cancelled ", 'transaction_id', $transaction_id);
		        	$status_code['code'] = '9999';
					return $status_code;
				}
    		}

		    return $status_code;
        }

        $before_balance = $this->game_api->queryPlayerBalance($playerUsername);

        $isIncrement = $this->wallet_model->incSubWallet($playerId, $this->gamePlatformId,$addAmount);
        if($isIncrement == false && $addAmount == 0) {
        	$isIncrement = true;
        }

        if($isIncrement) {
           $status_code['after_balance'] = $this->game_api->queryPlayerBalance($playerUsername)['balance'];

           $successTransaction = $this->processTransactionRecord($data,$action,$before_balance['balance'],$status_code['after_balance']);
		   if(!$successTransaction){
			$status_code['code'] = '9999';
		   }else{
			$status_code['code'] = '0000';
		   }

           $this->CI->utils->debug_log("Sexy Baccarat increment to subwallet is: ", $isIncrement, "Balance After Increment: ", $status_code['after_balance']);

        } else {
        	$status_code['code'] = '9999';
        }
        return $status_code;
    }


    /*
    *
    * Sample input Data
    *
    	Array
			(
			[gameType] => LIVE
			[gameName] => Baccarat
			[gameCode] => MX-LIVE-001
			[userId] => devtestsx1
			[platform] => SEXYBCRT
			[platformTxId] => BAC-95110722
			[updateTime] => 2020-07-29T17:04:20.000+08:00
			[roundId] => Mexico-05-GA292920041
			[betType] => PlayerPair
			[txId] => 5711555
			[betTime] => 2020-07-29T17:03:58.000+08:00
			[txTime] => 2020-07-29T17:03:58.000+08:00
			[turnover] => 10
			[betAmount] => 10
			[winAmount] => 0
			[gameInfo] => Array
			(
			[result] => Array
			(
			[0] => C02
			[1] => D01
			[2] => C07
			[3] => D07
			[4] => H06
			[5] => H11
			)

			[roundStartTime] => 07/29/2020 17:03:37.831
			[winner] => BANKER
			[ip] => 119.9.106.90
			[odds] => -1
			[tableId] => 5
			[dealerDomain] => Mexico
			[winLoss] => -10
			[status] => LOSE
			)

			)

    */
    private function processTransactionRecord(&$trans_record,$action,$before_balance=null,$after_balance=null) {

    	$data = array();
    	$data['platformTxId'] = isset($trans_record['platformTxId']) ? $trans_record['platformTxId'] : null;
		$data['userId'] = isset($trans_record['userId']) ? $trans_record['userId'] : null;
		$data['currency'] = isset($trans_record['currency']) ? $trans_record['currency'] : null;
		$data['platform'] = isset($trans_record['platform']) ? $trans_record['platform'] : null;
		$data['gameType'] = isset($trans_record['gameType']) ? $trans_record['gameType'] : null;
		$data['gameCode'] = isset($trans_record['gameCode']) ? $trans_record['gameCode'] : null;
		$data['gameName'] = isset($trans_record['gameName']) ? $trans_record['gameName'] : null;
		$data['betType'] = isset($trans_record['betType']) ? $trans_record['betType'] : null;
		$data['betAmount'] = isset($trans_record['betAmount']) ? $trans_record['betAmount'] : null;
		$data['winAmount'] = isset($trans_record['winAmount']) ? $trans_record['winAmount'] : null;
		$data['betTime'] = isset($trans_record['betTime']) ? $trans_record['betTime'] : null;
		$data['roundId'] = isset($trans_record['roundId']) ? $trans_record['roundId'] : null;
		$data['gameInfo'] = isset($trans_record['gameInfo']) ? json_encode($trans_record['gameInfo']) : null;
		$data['action'] = isset($action) ? $action : null;
		$data['tip_amount'] = isset($trans_record['tip']) ? $trans_record['tip'] : null;

		$data['response_result_id'] = isset($trans_record['response_result_id']) ? $trans_record['response_result_id'] : null;
		if($action == 'settle' || $action == 'unsettle') {
			/*$data['external_uniqueid'] = $action . "-" . $data['platformTxId'];
			if(isset($trans_record['updateTime'])){
				$data['external_uniqueid'] .= "-" . strtotime($trans_record['updateTime']);
			}*/
			$data['platformTxId'] = $action . "-" . $data['platformTxId'];
			$data['external_uniqueid'] = $data['platformTxId'];
		}elseif($action == 'give') {
			$data['platformTxId'] = isset($trans_record['promotionTxId']) ? $trans_record['promotionTxId'] : null;
			$data['betAmount'] = 0;
			$data['winAmount'] = isset($trans_record['amount']) ? $trans_record['amount'] : null;
			$data['betTime'] = isset($trans_record['txTime']) ? $trans_record['txTime'] : null;
			$data['gameInfo'] = json_encode($trans_record);
			$promotionId = isset($trans_record['promotionId']) ? $trans_record['promotionId'] : $data['userId'];
			$data['external_uniqueid'] = $action . "-" . $data['platformTxId'] . '-' . $promotionId;
		}elseif($action == 'resettle') {
			$data['winAmount'] = isset($trans_record['resettle_win_amount']) ? $trans_record['resettle_win_amount'] : null;
			$data['external_uniqueid'] = $action . "-" . $data['platformTxId'];
		} else {
			$data['external_uniqueid'] = $action . "-" . $data['platformTxId'];
		}
		$data['before_balance'] = $before_balance;
		$data['after_balance'] = $after_balance;
		$data['voidType'] = isset($trans_record['voidType']) ? $trans_record['voidType'] : null;

		$data['action_status'] = isset($trans_record['action_status']) ? $trans_record['action_status'] : null;
		$resettle_is_rejected = isset($trans_record['resettle_is_rejected']) ? $trans_record['resettle_is_rejected'] : false;
		$trans_record = $data;
		$this->CI->utils->debug_log(__FUNCTION__,'Sexy Baccarat (processTransactionRecord)', 'trans_record', $trans_record);
		/*if($action == 'cancelBet') {
			$transaction_id = $trans_record['platformTxId'];
			$existingRow = $this->sexy_baccarat_transactions->isRowByTransactionId($transaction_id);
			$updateAction = array(
				'action' => $action,
				'cancel_after' => $data['after_balance'],
				'cancel_before' => $data['before_balance'],
				'external_uniqueid' => $data['external_uniqueid'] . '-' . $action
			);
			$this->CI->utils->debug_log(__FUNCTION__,'Sexy Baccarat (processTransactionRecord) update');
			return $this->sexy_baccarat_transactions->updateGameLog($existingRow['id'],$updateAction);
		} else {
			$existExternal = $this->sexy_baccarat_transactions->isExternalUniqueExist($data['external_uniqueid']);
			if($existExternal) {
				return true;
			}
			if($action == 'bet') {
				$trans_record['action_status'] = Game_logs::STATUS_PENDING;
			}
			$this->CI->utils->debug_log(__FUNCTION__,'Sexy Baccarat (processTransactionRecord) insert');
			return $this->insertTransactionWithLog($trans_record);
		}*/

		$existExternal = $this->sexy_baccarat_transactions->isExternalUniqueExist($data['external_uniqueid']);
		if($existExternal) {
			return true;
		}
		if($action == 'settle' || $action == 'resettle') {
			$trans_record['action_status'] = Game_logs::STATUS_SETTLED;
			if($action == 'resettle' && $resettle_is_rejected){
				$trans_record['action_status'] = Game_logs::STATUS_REJECTED;
			}
		}
		$this->CI->utils->debug_log(__FUNCTION__,'Sexy Baccarat (processTransactionRecord) insert');
		return $this->insertTransactionWithLog($trans_record);

    }

	private function returnError($errorCode) {
		$response = array(
			"status" => $errorCode,
			"desc" => self::ERROR_CODE[$errorCode],
		);
		
		return $response;
	}

	private function saveResponseResult($success, $callMethod, $params, $response){
        //save to db
        $this->CI->load->model("response_result");
        $flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

		$cost = intval($this->utils->getExecutionTimeToNow()*1000);

		$fields = [
			'player_id'		=> $this->playerId,
		];
		$statusText = null;
        $headers = getallheaders();
        $extra = is_array($headers) ? json_encode($headers) : $headers;

        return $this->CI->response_result->saveResponseResult(
			$this->gamePlatformId, 
			$flag, 
			$callMethod, 
			json_encode($params), 
			$response, 
			$this->http_status_code, //statusCode
			$statusText, //statusText
			$extra, //extra
			$fields, //fields
			false, 
			null, 
			$cost);

    }

    private function insertTransactionWithLog($data) {

        $affected_rows = $this->sexy_baccarat_transactions->insertTransaction($data);

        $this->transaction_for_fast_track = null;

        $this->CI->utils->debug_log(__FUNCTION__,'Sexy Baccarat (insertTransactionWithLog)', $data, $affected_rows);
        if($affected_rows) {
            $this->transaction_for_fast_track = $data;
            $this->transaction_for_fast_track['id'] = $this->CI->sexy_baccarat_transactions->getLastInsertedId();

            $this->CI->utils->debug_log(__FUNCTION__,'Sexy Baccarat (insertTransactionWithLog) for fasttrack', $this->transaction_for_fast_track);
        }
        return $affected_rows;
    }

    private function sendToFastTrack() {
        $this->CI->load->model(['game_description_model']);
        $game_description = $this->game_description_model->getGameDetailsByGameCodeAndGamePlatform($this->game_api->getPlatformCode(), $this->transaction_for_fast_track['gameCode']);
        $betType = null;
        switch($this->transaction_for_fast_track['action']) {
            case 'bet':
            case 'unvoidBet':
                $betType = 'Bet';
                break;
            case 'settle':
                $betType = 'Win';
                break;
            case 'cancelBet':
            case 'voidBet':
            case 'voidSettle':
            case 'unsettle':
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
            "amount" => $betType == 'Win' ? (float) abs($this->transaction_for_fast_track['winAmount']) : (float) abs($this->transaction_for_fast_track['betAmount']) ,
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
            "round_id" =>  $this->transaction_for_fast_track['platformTxId'],
            "timestamp" =>  str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            "type" =>  $betType,
            "user_id" =>  $this->game_api->getPlayerIdInGameProviderAuth($this->transaction_for_fast_track['userId']),
            "vendor_id" =>  strval($this->game_api->getPlatformCode()),
            "vendor_name" =>  $this->external_system->getSystemName($this->game_api->getPlatformCode()),
            "wager_amount" => $betType == 'Bet' ? (float) abs($this->transaction_for_fast_track['betAmount']) : 0,
        ];

        $this->CI->utils->debug_log(__FUNCTION__,'Sexy Baccarat (sendToFastTrack)', $data);
        $this->load->library('fast_track');
        $this->fast_track->addToQueue('sendGameLogs', $data);
    }

    /**
	*
	*	Players resettlement is based in status is based on Game Information's status param.
	*	
	*/
	public function resettle($playerId,$playerUsername,$gameUsername,$fullRequest,$paramsValue) {

		$controller = $this;
		$game_usernames = [];
		foreach ($paramsValue as $paramValue) {
		    if(isset($paramValue['userId'])) {
		        $userId = $paramValue['userId'];
		        if (isset($game_usernames[$userId])) {
		            $playerId = $game_usernames[$userId];
		            if(isset($game_usernames[$playerId])) {
		            	$playerUsername = $game_usernames[$playerId];
		            } else {
		            	$playerUsername = $this->game_api->getPlayerUsernameByGameUsername($userId);
		            }
		        }
		        else {
		            $playerUsername = $this->game_api->getPlayerUsernameByGameUsername($userId);
		            $this->playerId = $playerId = $this->player_model->getPlayerIdByUsername($playerUsername);
		            $game_usernames[$userId] = $playerId;
		            $game_usernames[$playerId] = $playerUsername;
		        }
		    }

			# Check if player exist
			if(empty($playerId)) {
				throw new Exception('1000');
			}
			# Check if player exist
			if(empty($playerUsername)){
				throw new Exception('1002');
			}

			$status_code = [
				'code' => self::SUCCESS
			];
			$success = $this->lockAndTransForPlayerBalance($playerId, function() use($controller,$playerId,$playerUsername,&$status_code,$fullRequest,&$paramValue) {
				$betStatus = isset($paramValue['gameInfo']['status']) ? $paramValue['gameInfo']['status']: null;
				$betAmount = isset($paramValue['betAmount']) ? $paramValue['betAmount'] : 0;
				$winAmount = isset($paramValue['winAmount']) ? $paramValue['winAmount'] : null;
				$transaction_id = isset($paramValue['platformTxId']) ? $paramValue['platformTxId'] : null;
				$betRow = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'bet');
				
				# check if Transaction ID is existing
				if(empty($betRow)){
					$status_code['code'] = '1017';
					return false;
				}

				# check if bet Transaction ID bet already cancelled
				if($betRow['action_status']==Game_logs::STATUS_CANCELLED){
					$status_code['code'] = '1017';
					return false;
				}

				# check if bet Transaction ID bet already cancelled
				if($betRow['action_status']==Game_logs::STATUS_VOID){
					$status_code['code'] = '1017';
					return false;
				}

				$resettle = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'resettle');
				if(!empty($resettle)){
					$status_code['code'] = self::SUCCESS;
					return true;
				}

				$settleRow = $controller->sexy_baccarat_transactions->getTransactionRecord($transaction_id, 'settle');
				if(empty($settleRow)){
					$status_code['code'] = self::STATUS_CODE_FAIL;
					return false;
				}

				$ressettleWinAmount = $winAmount - $settleRow['winAmount'];
				$paramValue['resettle_win_amount'] = $ressettleWinAmount;
				if($this->utils->compareResultFloat($ressettleWinAmount, '<', 0)) {
					$ressettleWinAmount = abs($ressettleWinAmount);
					$status_code = $controller->doDeduct($playerId,$playerUsername,$ressettleWinAmount,$fullRequest,$paramValue);
				} else {
					$status_code = $controller->doIncrement($playerId,$playerUsername,$ressettleWinAmount,$fullRequest,$paramValue);
				} 

				if($status_code['code'] != self::SUCCESS) {
					return false;
				}

				
				$updateData = ['action_status'=>Game_logs::STATUS_SETTLED];
				$updateResp = $this->sexy_baccarat_transactions->updateTransactionData($transaction_id, 'bet', $updateData);
				if(!$updateResp){
					$status_code['code'] = '9999';
					return false;
				}

				return true;

			});

		}

		$this->CI->utils->debug_log('<-----------------------------Status Code', $status_code);

		if($status_code['code'] != self::SUCCESS) {
			 throw new Exception($status_code['code']);
		}


		$response = array(
			'status' => $status_code['code']
		);

		$this->CI->utils->debug_log('<-----------------------------Response Params:', $response);

		return $response;

	}

}
