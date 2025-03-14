<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/../../../submodules/game-lib/game_platform/common_seamless_utils.php';

class Lotto97_seamless_service_api extends BaseController {

	use common_seamless_utils;

	protected $gamePlatformId;
	protected $currencyCode;
	private $player_id;
	private $api;
	private $requestParams;
	private $refId;
    private $http_status_code;

	const RETURN_OK = [
		'error' => 0,
		'desc' => 'Success.'
	];
	const ERROR_NOT_ENOUGH_BALANCE = [
		'error' => 1,
		'desc' => 'Insufficient Balance.'
	];
	const ERROR_PLAYER_NOT_FOUND = [
		'error' => 2,
		'desc' => 'Player not found.'
	];
	const ERROR_AUTH_FAILED = [
        'error' => 3,
        'desc' => 'Player authentication failed.'
    ];
    const SYSTEM_ERROR = [
    	'error' => 100,
        'desc' => 'Internal Server Error.'
    ];
    const ERROR_METHOD_DISABLED = [
    	'error' => 100,
        'desc' => 'Method Disabled.'
    ];
    const BET='Bet';
    const BET_RESPONSE='BetResponse';
    const BET_RESULT='BetResult';
    const BET_REFUND='BetRefund';
    const STATUS_OK='ok';
    const FAIL='Fail';

	public function __construct() {

		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','game_description_model','external_system',
			'common_seamless_wallet_transactions','original_game_logs_model','external_common_tokens','common_seamless_error_logs'));

		$this->requestParams = new stdClass();
        $this->http_status_code = 200;
	}

	/**
     * Entry point
     *
     *
     * @param int $apiId
     * @param string $currency
     * @param string $method
     *
    */
	public function index($api,$currency,$method) {
		$this->CI->utils->debug_log(__FUNCTION__, 'Lotto97 (currency)',$currency,'api',$api,'method',$method);
		$this->processRequest();
		$this->currencyCode=$currency;
		$this->gamePlatformId=$api;
		$this->requestParams->method=$method;
		$is_valid=$this->getCurrencyAndValidateDB();
		if($is_valid) {
			if (!$this->external_system->isGameApiActive($this->gamePlatformId) || $this->external_system->isGameApiMaintenance($this->gamePlatformId)) {
				$this->utils->debug_log('Lotto97 is inactive/maintenance (Error Response Result)');
				return $this->setResponse(self::SYSTEM_ERROR['error'],self::SYSTEM_ERROR['desc']);
			}

			$this->api=$this->utils->loadExternalSystemLibObject($api);

            if (!$this->api->validateWhiteIP()) {
                $this->requestParams->method = 'validateWhiteIP';
                $this->http_status_code = 401;
                return $this->setResponse(self::ERROR_AUTH_FAILED['error'],self::ERROR_AUTH_FAILED['desc']);
            }

			if(!method_exists($this, $method)) {
				return $this->setResponse(self::SYSTEM_ERROR['error'],self::SYSTEM_ERROR['desc']);
			}

			$this->$method();
		}
	}

	protected function processRequest(){
		$request = file_get_contents('php://input');
		$this->CI->utils->debug_log(__FUNCTION__, 'Lotto97 (Raw Input): ', $request);
		parse_str($request, $paramsOutput);
		$this->CI->utils->debug_log(__FUNCTION__, 'Lotto97 (Raw array input): ', $paramsOutput);
		$this->requestParams->params = $paramsOutput;
		if(isset($this->requestParams->params['playerID'])) {
			$this->requestParams->params['playerID'] = (int)$this->requestParams->params['playerID'];
		}
	}

	/**
     * Authenticate, checks whether player's token is valid and if player exist
     * 
     * @return array
     */
	protected function Authenticate() {
		$gameUsername=$this->requestParams->params['username'];
		$this->player_id=$this->api->getPlayerIdByGameUsername($gameUsername);
		if(!empty($this->player_id)) {
			$playerName=$this->api->getPlayerUsernameByGameUsername($gameUsername);
			$player_bal=$this->api->queryPlayerBalance($playerName)['balance'];
			$data = ['balance' => $player_bal];
			return $this->setResponse(self::RETURN_OK['error'],self::RETURN_OK['desc'],$data);
		} else {
			return $this->setResponse(self::ERROR_AUTH_FAILED['error'],self::ERROR_AUTH_FAILED['desc']);
		}
	}

	protected function GetBalance() {

        $this->player_id=$this->external_common_tokens->getTokenInfoByExtras($this->requestParams->params['playerID'],$this->gamePlatformId);
		$this->CI->utils->debug_log(__FUNCTION__, 'Lotto97 (Player ID): ', $this->player_id,'provider player id',$this->requestParams->params['playerID']);
		if(!empty($this->player_id)) {
			$player_id=$this->player_id;
			$player_info=(array)$this->api->getPlayerInfo($player_id);
			$balance=0;
			$controller=$this;

			$trans_data=[
				'error' => self::RETURN_OK['error'],
				'desc' => self::RETURN_OK['desc']
			];

			$trans_success=$this->lockAndTransForPlayerBalance($player_id, function() use($controller,$player_info,&$balance,&$trans_data) {
				$balance = $controller->api->queryPlayerBalance($player_info['username'])['balance'];
				if($balance===false){
					return false;
				}
				$trans_data['balance']=$balance;
				return true;
			});

			if(!$trans_success) {
				return $this->setResponse(self::SYSTEM_ERROR['error'],self::SYSTEM_ERROR['desc']);
			} else {
				return $this->setResponse($trans_data['error'],$trans_data['desc'],$trans_data);
			}
		} else {
			return $this->setResponse(self::ERROR_AUTH_FAILED['error'],self::ERROR_AUTH_FAILED['desc']);
		}

	}

	protected function Bet() {

		$this->player_id=$this->external_common_tokens->getTokenInfoByExtras($this->requestParams->params['playerID'],$this->gamePlatformId);
		if(!empty($this->player_id)) {
			$player_id=$this->player_id;
			$player_info=(array)$this->api->getPlayerInfo($player_id);

			$controller=$this;
			$trans_data=[
				'error' => self::RETURN_OK['error'],
				'desc' => self::RETURN_OK['desc']
			];

			$trans_success=$this->lockAndTransForPlayerBalance($player_id, function() use($controller,$player_id,$player_info,&$trans_data) {
				$player_bal=$controller->api->queryPlayerBalance($player_info['username'])['balance'];
				$betAmount=isset($controller->requestParams->params['betAmount']) ? $controller->requestParams->params['betAmount'] : 0;

				if (($player_bal - $betAmount) < 0) {
					$trans_data['error']=self::ERROR_NOT_ENOUGH_BALANCE['error'];
					$trans_data['desc']=self::ERROR_NOT_ENOUGH_BALANCE['desc'];
					return false;
				}
				// if($controller->common_seamless_wallet_transactions->isTransactionExistCustom($controller->gamePlatformId,$controller->requestParams->params['roundID'])) {
				// 	$trans_data['error']=self::SYSTEM_ERROR['error'];
				// 	$trans_data['desc']=self::SYSTEM_ERROR['desc'];
				// 	return false;
				// }
				$trans_data = $controller->adjustWallet(self::BET,$player_info);

                if($trans_data['error']!= self::RETURN_OK['error']){
                    return false;
                }

				return true;
			});

			if($trans_data['error'] != self::RETURN_OK['error'] || !$trans_success) {
				$this->handleErrorLogs($trans_data);
				return $this->setResponse($trans_data['error'],$trans_data['desc']);
			} else {
				$data=[
					'balance' => $trans_data['after_balance'],
					'refID' => $this->refId,
				];
				return $this->setResponse($trans_data['error'],$trans_data['desc'],$data);
			}

		} else {
			return $this->setResponse(self::ERROR_AUTH_FAILED['error'],self::ERROR_AUTH_FAILED['desc']);
		}

	}

	protected function BetResponse() { #Note: To be deprecated!
		if($this->api->disabled_bet_response){
            return $this->setResponse(self::ERROR_METHOD_DISABLED['error'],self::ERROR_METHOD_DISABLED['desc']);
        }

		$this->player_id=$this->external_common_tokens->getTokenInfoByExtras($this->requestParams->params['playerID'],$this->gamePlatformId);
		if(!empty($this->player_id)) {
			$player_id=$this->player_id;
			$player_info=(array)$this->api->getPlayerInfo($player_id);

			$controller=$this;
			$trans_data=[
				'error' => self::RETURN_OK['error'],
				'desc' => self::RETURN_OK['desc']
			];

			$trans_success=$this->lockAndTransForPlayerBalance($player_id, function() use($controller,$player_id,$player_info,&$trans_data) {

				$isRowExist=$controller->common_seamless_wallet_transactions->getTransIdRowArray($controller->gamePlatformId,$controller->requestParams->params['betID'],self::BET_RESPONSE);
				if(!empty($isRowExist)) {
					return true;
				}

				if($controller->common_seamless_wallet_transactions->isTransactionExistCustom($controller->gamePlatformId,$controller->requestParams->params['refID'])) {
					$trans_data = $controller->adjustWallet(self::BET_RESPONSE,$player_info);
				} else {
					$trans_data['error'] = self::SYSTEM_ERROR['error'];
					$trans_data['message'] = self::SYSTEM_ERROR['message'];
				}

                if($trans_data['error']!= self::RETURN_OK['error']){
                    return false;
                }

				return true;
			});

			if($trans_data['error'] != self::RETURN_OK['error'] || !$trans_success) {
				$this->handleErrorLogs($trans_data);
				return $this->setResponse($trans_data['error'],$trans_data['desc']);
			} else {
				return $this->setResponse($trans_data['error'],$trans_data['desc']);
			}

		} else {
			return $this->setResponse(self::ERROR_AUTH_FAILED['error'],self::ERROR_AUTH_FAILED['desc']);
		}

	}

	public function BetResponses(){
		#Noted: There will be no balance adjusment for this part, since adjustment arleady made on request bet. 
		####### This is only for bet records (Bet Id's) generated for request bet.
		
		$requestParams = (array) $this->requestParams;
		$this->CI->utils->debug_log( 'BetResponses_lotto97_request: ', $requestParams);
		$params = isset($requestParams['params']) ? $requestParams['params'] : null;
		// $bets = isset($params['bets']) ? json_decode($params['bets'], true) : null;
		$bets = isset($params['bets']) && is_array($params['bets']) ? $params['bets'] : null;
		unset($requestParams, $params['bets']);
		$this->player_id = $this->external_common_tokens->getTokenInfoByExtras($params['playerID'],$this->gamePlatformId);
		if(!$this->player_id){
			return $this->setResponse(self::ERROR_AUTH_FAILED['error'],self::ERROR_AUTH_FAILED['desc']);
		}

		$playerDetails = (array)$this->api->getPlayerInfo($this->player_id);
		$balance = $this->api->queryPlayerBalance($playerDetails['username'])['balance'];
		$success = true; #default

		if(!empty($bets)){
			$insertRows = array();
			foreach ($bets as $key => $bet) {
				$betResponse = $this->common_seamless_wallet_transactions->getTransIdRowArray($this->gamePlatformId,$bet['betID'], self::BET_RESPONSE);
				if(empty($betResponse)){
					$insertRows[] = $bet;
				}
			}

			if(!empty($insertRows)){
				$this->processBetResponses($playerDetails, $params, $insertRows);
				$success = $this->common_seamless_wallet_transactions->runBatchInsertWithLimit($this->db, 'common_seamless_wallet_transactions', $insertRows);
			}
		}

		if($success){
			return $this->setResponse(self::RETURN_OK['error'], self::RETURN_OK['desc'], array('balance' => $balance));
		} else {
			$error =[
				'error' => self::SYSTEM_ERROR['error'],
				'desc' => self::SYSTEM_ERROR['desc'],
				'balance' => $balance
			];
			$this->handleErrorLogs($error);
			return $this->setResponse(self::SYSTEM_ERROR['error'], self::SYSTEM_ERROR['desc'], array('balance' => $balance));
		}
	}


	function processBetResponses($playerDetails, $params, &$betResponses){
		if(!empty($betResponses)){
			foreach ($betResponses as $key => $betResponse) {

				#Bet Data
				$transactionType = self::BET_RESPONSE;
				$externalUniqueId = isset($betResponse['betID']) ? $betResponse['betID'] : null;
				$startTime = isset($betResponse['betTime']) ? $this->api->gameTimeToServerTime(date("Y-m-d H:i:s", $betResponse['betTime'])) : $this->utils->getNowForMysql();
				$amount = isset($betResponse['betAmount']) ? $betResponse['betAmount'] : null;

				#request params
				$endTime= isset($params['closed_datetime']) ? $this->api->gameTimeToServerTime(date("Y-m-d H:i:s", $params['closed_datetime'])) : $this->utils->getNowForMysql();
				$refId = isset($params['refID']) ? $params['refID'] : null;
				$gameID = isset($params['gameID']) ? $params['gameID'] : null;
				$status = isset($params['status']) ? $params['status'] : null;

				#additional info for sbe
				$balance = $this->api->queryPlayerBalance($playerDetails['username'])['balance'];
				$playerId = $playerDetails['playerId'];
				$extraInfo = json_encode(array_merge($params, $betResponse));

				$betResponses[$key] = array(
					'game_platform_id' => $this->api->getPlatformCode(),
	                'amount' => $amount,
	                'game_id' => $gameID,
	                'transaction_type' => $transactionType,
	                'status' => $status,
	                'response_result_id' => $this->utils->getRequestId(),
	                'external_unique_id' => $transactionType.'-'.$externalUniqueId,
	                'extra_info' => $extraInfo,
	                'start_at' => $startTime,
	                'end_at' => $endTime,
	                'transaction_id' => $externalUniqueId,
	                'before_balance' => $balance,
	                'after_balance' => $balance,
	                'player_id' => $playerId,
	                'round_id' => $refId
				);
			}
		}
	}

	public function BetResults(){
		$success = true;
		$requestParams = (array) $this->requestParams;
		$this->CI->utils->debug_log( 'BetResults_lotto97_request: ', $requestParams);
		$betResults = isset($requestParams['params']['results']) && is_array($requestParams['params']['results']) ? $requestParams['params']['results'] : null;
		// $betResults = isset($requestParams['params']['results']) ? json_decode($requestParams['params']['results'], true) : null;
		if(!empty($betResults)){
			$gamePlayerIds = array_unique(array_column($betResults, 'playerID'));
			if(!empty($gamePlayerIds)){
				foreach ($gamePlayerIds as $gamePlayerId) {
					$playerId = $this->external_common_tokens->getTokenInfoByExtras((int)$gamePlayerId, $this->gamePlatformId);
					if($playerId){
						$playerBetResults = array_values(array_filter($betResults, function($row) use($gamePlayerId) {
							return $row['playerID'] == $gamePlayerId;
						}));
						if(!empty($playerBetResults)){
							$insertRows = array();
							foreach ($playerBetResults as $playerBetResult) {
								$betExist = $this->common_seamless_wallet_transactions->isTransactionExistCustom($this->gamePlatformId, $playerBetResult['refID']);
								if($betExist){
									$betResult = $this->common_seamless_wallet_transactions->getTransIdRowArray($this->gamePlatformId, $playerBetResult['betID'], self::BET_RESULT);
									if(empty($betResult)){
										$insertRows[] = $playerBetResult;
									}
								}
							}
							unset($playerBetResults);

							if(!empty($insertRows)){
								$playerDetails = (array)$this->api->getPlayerInfo($playerId);
								$success = $this->lockAndTransForPlayerBalance($playerId, function() use($playerDetails, &$insertRows) {
									$betIds = $this->adjustWalletForBetResults($playerDetails, $insertRows);
									$playerBetResults = array_values(array_filter($insertRows, function($row) use($betIds) {
										return in_array($row['betID'],$betIds['success']);
									}));

									$this->processBetResults($playerBetResults);
									$success = $this->common_seamless_wallet_transactions->runBatchInsertWithLimit($this->db, 'common_seamless_wallet_transactions', $playerBetResults);
            						return $success;
								});
							}
						}
					}
				}
			}
		}
		if($success){
			return $this->setResponse(self::RETURN_OK['error'], self::RETURN_OK['desc'], array('balance' => null));
		} else {
			$error =[
				'error' => self::SYSTEM_ERROR['error'],
				'desc' => self::SYSTEM_ERROR['desc'],
				'balance' => null
			];
			$this->handleErrorLogs($error);
			return $this->setResponse(self::SYSTEM_ERROR['error'], self::SYSTEM_ERROR['desc'], array('balance' => null));
		}
	}

	function adjustWalletForBetResults($playerDetails, &$insertRows) {
		$betResults = array( "success" => [], "fail" => []);
		foreach ($insertRows as $key => $row) {
			$insertRows[$key]['sbeBeforeBalance'] = $this->api->queryPlayerBalance($playerDetails['username'])['balance'];
			$winAmount = isset($row['winAmount']) ? $row['winAmount'] : null;
			if($this->utils->compareResultFloat($winAmount, '>', 0)) {
				$success = $this->wallet_model->incSubWallet($playerDetails['playerId'], $this->api->getPlatformCode(), $winAmount);
			} elseif ($this->utils->compareResultFloat($winAmount, '=', 0)) {
                $success = true;#allowed winAmount 0
            } else { #default error
                $success = false;
            }

            if($success){
            	$betResults['success'][] = $row['betID'];
            } else {
            	$betResults['fail'][] = $row['betID'];
            }
            $insertRows[$key]['sbeAfterBalance'] = $this->api->queryPlayerBalance($playerDetails['username'])['balance'];
            $insertRows[$key]['sbePlayerId'] = $playerDetails['playerId'];
		}

		return $betResults;
	}

	function processBetResults(&$betResults){
		if(!empty($betResults)){
			foreach ($betResults as $key => $betResult) {

				#request data
				$transactionType = self::BET_RESULT;
				$externalUniqueId = isset($betResult['betID']) ? $betResult['betID'] : null;
				$startTime = $this->utils->getNowForMysql();
				$endTime= $this->utils->getNowForMysql();
				$amount = isset($betResult['winAmount']) ? $betResult['winAmount'] : null;
				$refId = isset($betResult['refID']) ? $betResult['refID'] : null;
				$gameID = isset($betResult['gameID']) ? $betResult['gameID'] : null;

				#additional info for sbe
				$beforeBalance = isset($betResult['sbeBeforeBalance']) ? $betResult['sbeBeforeBalance'] : null;
				$afterBalance = isset($betResult['sbeAfterBalance']) ? $betResult['sbeAfterBalance'] : null;
				$playerId = isset($betResult['sbePlayerId']) ? $betResult['sbePlayerId'] : null;
				unset($betResult['sbeBeforeBalance'],$betResult['sbeAfterBalance'],$betResult['sbePlayerId']);
				$extraInfo = json_encode($betResult);

				$betResults[$key] = array(
					'game_platform_id' => $this->api->getPlatformCode(),
	                'amount' => $amount,
	                'game_id' => $gameID,
	                'transaction_type' => $transactionType,
	                'status' => self::STATUS_OK,
	                'response_result_id' => $this->utils->getRequestId(),
	                'external_unique_id' => $transactionType.'-'.$externalUniqueId,
	                'extra_info' => $extraInfo,
	                'start_at' => $startTime,
	                'end_at' => $endTime,
	                'transaction_id' => $externalUniqueId,
	                'before_balance' => $beforeBalance,
	                'after_balance' => $afterBalance,
	                'player_id' => $playerId,
	                'round_id' => $refId
				);
			}
		}
	}

	protected function BetResult() { #Note: To be deprecated!
		if($this->api->disabled_bet_result){
            return $this->setResponse(self::ERROR_METHOD_DISABLED['error'],self::ERROR_METHOD_DISABLED['desc']);
        }

		$this->player_id=$this->external_common_tokens->getTokenInfoByExtras($this->requestParams->params['playerID'],$this->gamePlatformId);
		if(!empty($this->player_id)) {
			$player_id=$this->player_id;
			$player_info=(array)$this->api->getPlayerInfo($player_id);

			$controller=$this;
			$trans_data=[
				'error' => self::RETURN_OK['error'],
				'desc' => self::RETURN_OK['desc'],
				'balance' => $this->api->queryPlayerBalance($player_info['username'])['balance']
			];

			$trans_success=$this->lockAndTransForPlayerBalance($player_id, function() use($controller,$player_id,$player_info,&$trans_data) {

				$isRowExist=$controller->common_seamless_wallet_transactions->isTransactionExistCustom($controller->gamePlatformId,$controller->requestParams->params['refID']);
				$isRowResultExist=$controller->common_seamless_wallet_transactions->getTransIdRowArray($controller->gamePlatformId,$controller->requestParams->params['betID'],self::BET_RESULT);

				if(empty($isRowExist)) {
					$trans_data['error']=self::SYSTEM_ERROR['error'];
					$trans_data['desc']=self::SYSTEM_ERROR['desc'];
					return false;
				}

				if(!empty($isRowResultExist)) {
					return true;
				} else {
					$trans_data = $controller->adjustWallet(self::BET_RESULT,$player_info);
				}

                if($trans_data['error']!= self::RETURN_OK['error']){
                    return false;
                }

                $player_bal=$controller->api->queryPlayerBalance($player_info['username'])['balance'];
				$trans_data['balance']=$player_bal;

				return true;
			});

			if($trans_data['error'] != self::RETURN_OK['error'] || !$trans_success) {
				$this->handleErrorLogs($trans_data);
				return $this->setResponse($trans_data['error'],$trans_data['desc']);
			} else {
				$data=['balance'=>$trans_data['balance']];
				return $this->setResponse($trans_data['error'],$trans_data['desc'],$data);
			}

		} else {
			return $this->setResponse(self::ERROR_AUTH_FAILED['error'],self::ERROR_AUTH_FAILED['desc']);
		}

	}

	protected function BetRefund() {

		$this->player_id=$this->external_common_tokens->getTokenInfoByExtras($this->requestParams->params['playerID'],$this->gamePlatformId);
		if(!empty($this->player_id)) {
			$player_id=$this->player_id;
			$player_info=(array)$this->api->getPlayerInfo($player_id);

			$controller=$this;
			$trans_data=[
				'error' => self::RETURN_OK['error'],
				'desc' => self::RETURN_OK['desc']
			];

			$trans_success=$this->lockAndTransForPlayerBalance($player_id, function() use($controller,$player_id,$player_info,&$trans_data) {
				$isRowExist=$controller->common_seamless_wallet_transactions->getTransIdRowArray($controller->gamePlatformId,$controller->requestParams->params['betID'],self::BET_REFUND);
				$isBetRowExist=$controller->common_seamless_wallet_transactions->getTransIdRowArray($controller->gamePlatformId,$controller->requestParams->params['betID'],self::BET_RESPONSE);
				if(!empty($isRowExist)) {
					$trans_data['after_balance'] = $isRowExist[0]['after_balance'];
					return true;
				}

				if(!empty($isBetRowExist)) {
					$trans_data = $controller->adjustWallet(self::BET_REFUND,$player_info);
				}

                if($trans_data['error']!= self::RETURN_OK['error']){
                    return false;
                }

				return true;
			});

			if($trans_data['error'] != self::RETURN_OK['error'] || !$trans_success) {
				$this->handleErrorLogs($trans_data);
				return $this->setResponse($trans_data['error'],$trans_data['desc']);
			} else {
				$balance=isset($trans_data['after_balance']) ? $trans_data['after_balance'] : 0;
				$data=[
					'balance' => $trans_data['after_balance']
				];
				return $this->setResponse($trans_data['error'],$trans_data['desc'],$data);
			}

		} else {
			return $this->setResponse(self::ERROR_AUTH_FAILED['error'],self::ERROR_AUTH_FAILED['desc']);
		}

	}

	private function adjustWallet($action,$player_info) {
		$trans_data = [
			'error' => self::RETURN_OK['error'],
			'desc' => self::RETURN_OK['desc']
		];

		$trans_data['before_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
		if($action==self::BET){
			$deduct_balance = $this->wallet_model->decSubWallet($player_info['playerId'],$this->api->getPlatformCode(),$this->requestParams->params['betAmount']);
			if($this->requestParams->params['betAmount'] == 0 && !$deduct_balance) {
				$trans_data['error'] = self::RETURN_OK['error'];
				$trans_data['desc'] = self::RETURN_OK['desc'];
			}
			if(!$deduct_balance && $this->requestParams->params['betAmount'] > 0) {
				$trans_data['error'] = self::SYSTEM_ERROR['error'];
				$trans_data['desc'] = self::SYSTEM_ERROR['desc'];
			}
		} else if($action==self::BET_RESULT) {
			$add_balance = $this->wallet_model->incSubWallet($player_info['playerId'],$this->api->getPlatformCode(),$this->requestParams->params['winAmount']);
			if($this->requestParams->params['winAmount'] == 0 && !$add_balance) {
				$trans_data['error'] = self::RETURN_OK['error'];
				$trans_data['desc'] = self::RETURN_OK['desc'];
			}
			if(!$add_balance && $this->requestParams->params['winAmount'] > 0) {
				$trans_data['error'] = self::SYSTEM_ERROR['error'];
				$trans_data['desc'] = self::SYSTEM_ERROR['desc'];
			}
		} else if($action==self::BET_REFUND) {
			$add_balance = $this->wallet_model->incSubWallet($player_info['playerId'],$this->api->getPlatformCode(),$this->requestParams->params['betAmount']);
			if($this->requestParams->params['betAmount'] == 0 && !$add_balance) {
				$trans_data['error'] = self::RETURN_OK['error'];
				$trans_data['desc'] = self::RETURN_OK['desc'];
			}
			if(!$add_balance && $this->requestParams->params['betAmount'] > 0) {
				$trans_data['error'] = self::SYSTEM_ERROR['error'];
				$trans_data['desc'] = self::SYSTEM_ERROR['desc'];
			}
		} else if($action==self::BET_RESPONSE) {
			if($this->requestParams->params['status'] == self::FAIL) {
				$add_balance = $this->wallet_model->incSubWallet($player_info['playerId'],$this->api->getPlatformCode(),$this->requestParams->params['betAmount']);
				if($this->requestParams->params['betAmount'] == 0 && !$add_balance) {
					$trans_data['error'] = self::RETURN_OK['error'];
					$trans_data['desc'] = self::RETURN_OK['desc'];
				}
				if(!$add_balance && $this->requestParams->params['betAmount'] > 0) {
					$trans_data['error'] = self::SYSTEM_ERROR['error'];
					$trans_data['desc'] = self::SYSTEM_ERROR['desc'];
				}
			}
		}
		$trans_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

		if($trans_data['error'] == self::RETURN_OK['error']) {
			$gameRecords=$this->processTransaction($trans_data['before_balance'],$trans_data['after_balance'],$player_info);
			$insert=$this->common_seamless_wallet_transactions->insertRow($gameRecords);
			if(!$insert) {
				$trans_data['error'] = self::SYSTEM_ERROR['error'];
				$trans_data['desc'] = self::SYSTEM_ERROR['desc'];
			}
		}

		return $trans_data;
	}

	private function processTransaction($before_balance,$after_balance,$player_info) {
		$apiId=$this->gamePlatformId;
    	$transaction_type=$this->requestParams->method;
    	$playerId=$this->player_model->getPlayerIdByUsername($player_info['username']);
    	$gamePlayerId=isset($this->requestParams->params['playerID']) ? $this->requestParams->params['playerID'] : null;
    	$gameID=isset($this->requestParams->params['gameID']) ? $this->requestParams->params['gameID'] : null;
    	$gameName=isset($this->requestParams->params['gameName']) ? $this->requestParams->params['gameName'] : null;
    	$betAmount=isset($this->requestParams->params['betAmount']) ? $this->requestParams->params['betAmount'] : null;
    	$winAmount=isset($this->requestParams->params['winAmount']) ? $this->requestParams->params['winAmount'] : null;
    	$isAdjusted=isset($this->requestParams->params['isAdjusted']) ? $this->requestParams->params['isAdjusted'] : null;
    	$betID=isset($this->requestParams->params['betID']) ? $this->requestParams->params['betID'] : null;
    	$betType=isset($this->requestParams->params['betType']) ? $this->requestParams->params['betType'] : null;
    	$betNumber=isset($this->requestParams->params['betNumber']) ? $this->requestParams->params['betNumber'] : null;
    	$betTime=isset($this->requestParams->params['betTime']) ? $this->requestParams->params['betTime'] : null;
    	$status=isset($this->requestParams->params['status']) ? $this->requestParams->params['status'] : null;
    	$closed_datetime=isset($this->requestParams->params['closed_datetime']) ? $this->requestParams->params['closed_datetime'] : null;
    	$startTime=isset($this->requestParams->params['betTime']) ?$this->api->gameTimeToServerTime(date("Y-m-d H:i:s", $this->requestParams->params['betTime'])) : $this->utils->getNowForMysql();
    	$endTime=isset($closed_datetime) ? $this->api->gameTimeToServerTime(date("Y-m-d H:i:s", $closed_datetime)) : $this->utils->getNowForMysql();
    	$roundID=isset($this->requestParams->params['betID']) ? $this->requestParams->params['betID'] : $gamePlayerId.'-'.$this->requestParams->params['roundID'].'-'.strtotime($endTime);
    	$refID=isset($this->requestParams->params['refID']) ? $this->requestParams->params['refID'] : $roundID;
    	$externalUniqueId=$roundID;
    	$this->refId=$refID;

    	$extra_info = [
    		'playerId' => $playerId,
            'gamePlayerId' => $gamePlayerId,
            'gameID' => $gameID,
            'gameName' => $gameName,
            'roundID' => $roundID,
            'betAmount' => $betAmount,
            'winAmount' => $winAmount,
            'isAdjusted' => $isAdjusted,
            'refID' => $refID,
            'betID' => $betID,
            'betType' => $betType,
            'betNumber' => $betNumber,
            'betTime' => $betTime,
            'status' => $status,
            'closed_datetime' => $closed_datetime,
            'startTime' => $startTime,
            'endTime' => $endTime,
            'externalUniqueId' => $externalUniqueId
        ];

        $extraInfo = json_encode($extra_info);

        $gameRecords = [
            // [
                'game_platform_id' => $apiId,
                'amount' => $transaction_type == 'BetResult' ? $winAmount : $betAmount,
                'game_id' => $gameID,
                'transaction_type' => $transaction_type,
                'status' => isset($status) ? $status : self::STATUS_OK,
                'response_result_id' => $this->utils->getRequestId(),
                'external_unique_id' => $transaction_type.'-'.$externalUniqueId,
                'extra_info' => $extraInfo,
                'start_at' => $startTime,
                'end_at' => $endTime,
                'transaction_id' => $externalUniqueId,
                'before_balance' => $before_balance,
                'after_balance' => $after_balance,
                'player_id' => $playerId,
                'round_id' => $this->refId
            // ]
        ];

        $this->processGameRecords($gameRecords);

        return $gameRecords;
	}

	/**
     * Process Game Records Array
     * 
     * @param array $gameRecords
     * @return void
     */
    public function processGameRecords(&$gameRecords){
        $elapsed=intval($this->utils->getExecutionTimeToNow()*1000);
        if(! empty($gameRecords)){
            // foreach($gameRecords as $index => $record){
                $data['game_platform_id'] = isset($gameRecords['game_platform_id']) ? $gameRecords['game_platform_id'] : null;
                $data['amount'] = isset($gameRecords['amount']) ? $gameRecords['amount'] : null;
                $data['before_balance'] = isset($gameRecords['before_balance']) ? $gameRecords['before_balance'] : null;
                $data['after_balance'] = isset($gameRecords['after_balance']) ? $gameRecords['after_balance'] : null;
                $data['player_id'] = isset($gameRecords['player_id']) ? $gameRecords['player_id'] : null;
                $data['game_id'] = isset($gameRecords['game_id']) ? $gameRecords['game_id'] : null;
                $data['transaction_type'] = isset($gameRecords['transaction_type']) ? $gameRecords['transaction_type'] : null;
                $data['status'] = isset($gameRecords['status']) ? $gameRecords['status'] : null;
                $data['response_result_id'] = isset($gameRecords['response_result_id']) ? $gameRecords['response_result_id'] : null;
                $data['external_unique_id'] = isset($gameRecords['external_unique_id']) ? $gameRecords['external_unique_id'] : null;
                $data['extra_info'] = isset($gameRecords['extra_info']) ? $gameRecords['extra_info'] : null;
                $data['start_at'] = isset($gameRecords['start_at']) ? $gameRecords['start_at'] : null;
                $data['end_at'] = isset($gameRecords['end_at']) ? $gameRecords['end_at'] : null;
                $data['transaction_id'] = isset($gameRecords['transaction_id']) ? $gameRecords['transaction_id'] : null;
                $data['elapsed_time'] = isset($gameRecords['elapsed_time']) ? $gameRecords['elapsed_time'] : $elapsed;
                $data['round_id'] = isset($gameRecords['round_id']) ? $gameRecords['round_id'] : null;

                $gameRecords = $data;
                unset($data);
            // }
        }
    }

	/**
	 * getCurrencyAndValidateDB
	 *
	 * @return [type]            [description]
	 */
	private function getCurrencyAndValidateDB() {
		if(isset($this->currencyCode) && !empty($this->currencyCode)) {
			# Get Currency Code for switching of currency and db forMDB
			$is_valid=$this->validateCurrencyAndSwitchDB();

			return $is_valid;
		} else {
			return false;
		}
	}

	protected function validateCurrencyAndSwitchDB(){
        if(!$this->utils->isEnabledMDB()){
            return true;
        }
        if(empty($this->currencyCode)){
            return false;
        }else{
            //validate currency name
            if(!$this->utils->isAvailableCurrencyKey($this->currencyCode)){
                //invalid currency name
                return false;
            }else{
                //switch to target db
                $_multiple_db=Multiple_db::getSingletonInstance();
                $_multiple_db->switchCIDatabase($this->currencyCode);
                return true;
            }
        }
    }

	private function handleErrorLogs($trans_data) {

		$commonSeamlessErrorDetails=json_encode([
			'response'=>$trans_data, 'request'=>$this->requestParams->params
		]);
        $request_id = $this->utils->getRequestId();
        $elapsed = intval($this->utils->getExecutionTimeToNow()*1000);
        $now = $this->utils->getNowForMysql();
		$insertData = [
            'game_platform_id' => $this->gamePlatformId,
            'request_id' => $request_id,
            'elapsed_time' => $elapsed,
            'error_date' => $now,
            'extra_info' => $commonSeamlessErrorDetails,
            'error_id' => $trans_data['error']
        ];
        try{
            $this->common_seamless_error_logs->insertTransaction($insertData);
        }catch(\Exception $e){
            $this->utils->error_log(__METHOD__.' error inserting into common_seamless_error_log',$e->getMessage());
        }
	}

    private function setResponse($returnCode, $message, $data = []) {
    	$code = ['error' => $returnCode, 'desc' => $message];
        $data = array_merge($data,$code);
        return $this->setOutput($data);
    }

    private function setOutput($data = []) {
        $flag = $data['error'] == 0 ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        $data = json_encode($data);
		$this->CI->utils->debug_log(__FUNCTION__, 'Lotto97 (Response): ', $data);
        $fields = ['player_id' => $this->player_id];
        $headers = getallheaders();

        if($this->api) {
            $response_result_id=$this->CI->response_result->saveResponseResult(
                $this->api->getPlatformCode(),
                $flag,
                $this->requestParams->method,
                json_encode($this->requestParams->params),
                $data,
                $this->http_status_code,
                null,
                is_array($headers) ? json_encode($headers) : $headers,
                $fields
            );
        }

        $this->output->set_status_header($this->http_status_code)->set_content_type('application/json')->set_output($data);
        $this->output->_display();
        exit();
    }


}