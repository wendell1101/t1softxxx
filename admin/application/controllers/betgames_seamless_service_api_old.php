<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Betgames_seamless_service_api extends BaseController {

	const RETURN_SUCCESS_CODE=0;
	const ERROR_WRONG_SIGNATURE=1;
	const ERROR_INVALID_TOKEN=3;
	const ERROR_INVALID_PAYIN=700;
	const ERROR_INVALID_PLAYER=702;
	const ERROR_INSUFFICIENT_BALANCE=703;
	const ERROR_INVALID_TIMESTAMP=704;
	const ERROR_ENCOUNTERED=705;
	const RETURN_SUCCESS=1;
	const RETURN_ERROR=0;
	const TRANSACTION_CREDIT='credit';
	const TRANSACTION_DEBIT='debit';
	const TRANSACTION_DEBIT_SUBSCRIPTION='debit_subscription';
	const TRANSACTION_DEBIT_COMBINATION='debit_combination';
	const TRANSACTION_CREDIT_COMBINATION='credit_combination';
	const TRANSACTION_CREDIT_PROMO='credit_promo';
	//COMBO is used for game list with combination bets so that it wont be unknown
	const COMBO=999;

	protected $gamePlatformId = BETGAMES_SEAMLESS_THB1_GAME_API;
	protected $requestParams;

	public function __construct() {

		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','player_model','game_description_model','betgames_wallet_transactions'));
		$this->game_api = $this->utils->loadExternalSystemLibObject($this->gamePlatformId);
		$this->secret_key = $this->game_api->secret_key;
		$this->requestParams = new stdClass();

	}

	public function index() {
		$this->processRequest();
		$callMethod = $this->requestParams['method'];
		$response['root'] = $this->callMethod($callMethod, $this->requestParams);
		unset($response['root']['response_result_id']);
		$arrayToXml = $this->CI->utils->arrayToXml($response);
		
		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Response Array): ', $response);

		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Response XML): ', $arrayToXml);


		return $this->outputXmlResponse($response);

}

	protected function processRequest(){
		$request = file_get_contents('php://input');
		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Raw Input): ', $request);
		$parseRequest = simplexml_load_string($request);
		$encodeRequest=json_encode($parseRequest,true);
		$arrayToXml=json_decode($encodeRequest,true);
		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Raw array input): ', $arrayToXml);
		$this->requestParams = $arrayToXml;

		return $this->requestParams;
	}

	protected function validateRequest($requestParams){
		
		$signature = $this->requestParams['signature'];
		$md5_string = $this->encryptMd5String($requestParams);
		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (md5 encrypted): ', $md5_string);
		$dateTime = $this->checkDateTime($this->requestParams['time']);
		$is_valid = false;

		if($md5_string == $signature && $dateTime) {
			$is_valid=true;
		}

		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Is Valid): ', $is_valid);

		return $is_valid;

	}

	private function encryptMd5String($requestParams) {

		unset($requestParams['signature']);
		if(empty($requestParams['params'])){
			unset($requestParams['params']);
		}

		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Encrypty Parameters): ', $requestParams);

		$request=null;
		foreach($requestParams as $requestParam => &$requestParamVal) {

            if(is_object($requestParamVal) || is_array($requestParamVal)) {
				$md5String=null;
            	foreach ($requestParamVal as $key => $value) {
            		if(is_array($value)) {
            			$md5String.=$key;
            			foreach ($value as $k => $v) {
            				if(is_array($v)) {
            					foreach ($v as $kp => $vp) {
            						if(is_array($vp)) {
            							$md5String.=$kp;
            							foreach ($vp as $k1 => $v1) {
            								$md5String.=$k1.$v1;
            							}
            						} else {
            							$md5String.=$kp.$vp;
            						}
            					}
            				} else {
            					$md5String.=$k.$v;
            				}
            			}
            		} else {
            			$md5String.=$key.$value;
            		}
            	}
            	unset($requestParams[$requestParam]);
            	break;
            }
            $request.=$requestParam.$requestParamVal;
		}

		if(isset($md5String)){
			$md5Encrypt=$request.$md5String.$this->secret_key;
		} else {
			$md5Encrypt=$request.$this->secret_key;
		}

		$this->CI->utils->debug_log(__FUNCTION__, 'md5 Encrypted Values: ', $md5Encrypt);

		return md5($md5Encrypt);
	}

	protected function checkDateTime($dateTime){

		$dateTimeStamp=$this->utils->getTimestampNow();
		$dateTimeNow=date('Y-m-d H:i:s', strtotime('-1 minutes',$dateTimeStamp));
		$getDateTimeNow=date('Y-m-d H:i:s', $dateTimeStamp);
		$timeStamp=$dateTime;
		$requestDate=date('Y-m-d H:i:s', $timeStamp);

		if($requestDate < $dateTimeNow){
			$isValidDateTime=false;
		} else {
			$isValidDateTime=true;
		}

		$this->CI->utils->debug_log(__FUNCTION__, 'Request Date: ', $requestDate, 'Date Time: ', $dateTimeNow, 'is Valid date time: ', $isValidDateTime);

		return $isValidDateTime;

	}

	protected function callMethod($callMethod,$requestParams) {
		switch ($callMethod) {
			case 'ping':
				return $this->ping($requestParams);
				break;
			case 'get_account_details':
				return $this->get_account_details($requestParams);
				break;
			case 'refresh_token':
				return $this->refresh_token($requestParams);
				break;
			case 'request_new_token':
				return $this->request_new_token($requestParams);
				break;
			case 'get_balance':
				return $this->get_balance($requestParams);
				break;
			case 'transaction_bet_payin':
				return $this->transaction_bet_payin($requestParams);
				break;
			case 'transaction_bet_subscription_payin':
				return $this->transaction_bet_subscription_payin($requestParams);
				break;
			case 'transaction_bet_payout':
				return $this->transaction_bet_payout($requestParams);
				break;
			case 'transaction_bet_combination_payin':
				return $this->transaction_bet_combination_payin($requestParams);
				break;
			case 'transaction_bet_combination_payout':
				return $this->transaction_bet_combination_payout($requestParams);
				break;
			case 'transaction_promo_payout':
				return $this->transaction_promo_payout($requestParams);
				break;
			
			default:
				# code...
				break;
		}
	}

	protected function ping($requestParams){

		$is_valid = $this->validateRequest($requestParams);

		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Is valid request): ', $is_valid);

		$method = $this->requestParams['method'];
		$params = $this->requestParams['params'];
		$data = [
			'method' => $method,
			'token' => '-',
		];

		if($is_valid){
			$response = $this->getErrorSuccessMessage(self::RETURN_SUCCESS, self::RETURN_SUCCESS_CODE, $data);
			unset($response['response_result_id']);

			return $response;
		} else {
			
			$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_WRONG_SIGNATURE, $data);

			return $response;
		}

	}

	protected function get_account_details($requestParams){

		$is_valid = $this->validateRequest($requestParams);

		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Is valid request): ', $is_valid);

		$method = $this->requestParams['method'];
		$token = $this->requestParams['token'];
		$data = [
			'method' => $method,
			'token' => $token,
		];

		if($is_valid){
			$playerId=$this->common_token->getPlayerIdByToken($token);
			$validateToken=$this->common_token->isTokenValid($playerId, $token);

			if($validateToken) {
				$playerInfo=$this->common_token->getPlayerInfoByToken($token);
				$gameUsername=$this->game_api->getGameUsernameByPlayerUsername($playerInfo['username']);
				$params = [
					'user_id' => $gameUsername,
					'username' => $gameUsername,
					'currency' => 'THB',
					'info' => '-'
				];

				$response = $this->getErrorSuccessMessage(self::RETURN_SUCCESS, self::RETURN_SUCCESS_CODE, $data, $params);

				return $response;

			} else {
				$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_INVALID_TOKEN, $data);

				return $response;
			}
		} else {
			
			$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_WRONG_SIGNATURE, $data);

			return $response;
		}
	}

	protected function refresh_token($requestParams){

		$is_valid = $this->validateRequest($requestParams);

		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Is valid request): ', $is_valid);

		$method = $this->requestParams['method'];
		$token = $this->requestParams['token'];
		$data = [
			'method' => $method,
			'token' => $token,
		];

		if($is_valid){
			$playerId=$this->common_token->getPlayerIdByToken($token);
			$validateToken=$this->common_token->isTokenValid($playerId, $token);

			if($validateToken) {
				$response = $this->getErrorSuccessMessage(self::RETURN_SUCCESS, self::RETURN_SUCCESS_CODE, $data);

				return $response;

			} else {
				$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_INVALID_TOKEN, $data);

				return $response;
			}
		} else {
			
			$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_WRONG_SIGNATURE, $data);

			return $response;
		}

	}

	protected function request_new_token($requestParams){

		$is_valid = $this->validateRequest($requestParams);

		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Is valid request): ', $is_valid);

		$method = $this->requestParams['method'];
		$token = $this->requestParams['token'];
		$data = [
			'method' => $method,
			'token' => $token,
		];

		if($is_valid){
			$playerId=$this->common_token->getPlayerIdByToken($token);
			$validateToken=$this->common_token->isTokenValid($playerId, $token);

			if($validateToken) {
				$newToken=$this->game_api->getPlayerToken($playerId);
				$params = [
					'new_token' => $newToken,
				];

				$response = $this->getErrorSuccessMessage(self::RETURN_SUCCESS, self::RETURN_SUCCESS_CODE, $data, $params);

				return $response;

			} else {
				$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_INVALID_TOKEN, $data);

				return $response;
			}
		} else {
			
			$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_WRONG_SIGNATURE, $data);

			return $response;
		}
	}

	protected function get_balance($requestParams){

		$is_valid = $this->validateRequest($requestParams);

		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Is valid request): ', $is_valid);

		$method = $this->requestParams['method'];
		$token = $this->requestParams['token'];
		$data = [
			'method' => $method,
			'token' => $token,
		];

		if($is_valid){
			$playerInfo=$this->common_token->getPlayerInfoByToken($token);
			$playerName=$playerInfo['username'];
			$playerId=$playerInfo['playerId'];
			$validateToken=$this->common_token->isTokenValid($playerId, $token);

			if($validateToken) {

				$playerBalance=$this->game_api->queryPlayerBalance($playerName)['balance'];

				$params = [
					'balance' => $this->convertBalance($playerBalance),
				];

				$response = $this->getErrorSuccessMessage(self::RETURN_SUCCESS, self::RETURN_SUCCESS_CODE, $data, $params);

				return $response;

			} else {
				$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_INVALID_TOKEN, $data);

				return $response;
			}
		} else {
			
			$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_WRONG_SIGNATURE, $data);

			return $response;
		}

	}

	protected function transaction_bet_payin($requestParams){

		$is_valid = $this->validateRequest($requestParams);

		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Is valid request): ', $is_valid);

		$method = $this->requestParams['method'];
		$token = $this->requestParams['token'];
		$data = [
			'method' => $method,
			'token' => $token,
		];

		if($is_valid){
			$transaction_id = $this->requestParams['params']['transaction_id'];
			$bet_amount = $this->requestParams['params']['amount'];
			$playerInfo=$this->common_token->getPlayerInfoByToken($token);
			$playerName=$playerInfo['username'];
			$playerId=$playerInfo['playerId'];
			$validateToken=$this->common_token->isTokenValid($playerId, $token);


			if($validateToken) {

				$playerBalance=$this->game_api->queryPlayerBalance($playerName)['balance'];

					$controller = $this;
					$trans_data = [
						'code' => self::RETURN_SUCCESS_CODE
					];

					$this->lockAndTransForPlayerBalance($playerId, function() use($controller, $playerInfo, &$trans_data, $transaction_id) {
						$before_balance=$controller->game_api->queryPlayerBalance($playerInfo['username'])['balance'];
						$existingRow = $controller->betgames_wallet_transactions->isTransactionIdAlreadyExists($transaction_id);
			            if($existingRow) {
			            	$trans_data['code'] = self::RETURN_SUCCESS_CODE;
			                $trans_data['after_balance'] = $before_balance;
			                $trans_data['already_processed'] = 1;
			                return true;
			            }
			            $trans_data = $controller->debitCreditAmount(self::TRANSACTION_DEBIT, $playerInfo);
			            if($trans_data['code']!= self::RETURN_SUCCESS_CODE){
			                return false;
			            }
			            return true;
			        });

			        if($trans_data['code'] != self::RETURN_SUCCESS_CODE) {
			        	$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, $trans_data['code'], $data);
			        	return $response;
			        }

			        $params = [
			        	'balance_after' => $this->convertBalance($trans_data['after_balance']),
			        	'already_processed' => isset($trans_data['already_processed'])?$trans_data['already_processed']:0
			        ];

			        $response = $this->getErrorSuccessMessage(self::RETURN_SUCCESS, self::RETURN_SUCCESS_CODE, $data, $params);
			        
			        return $response;

			} else {
				$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_INVALID_TOKEN, $data);

				return $response;
			}
		} else {
			
			$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_WRONG_SIGNATURE, $data);

			return $response;
		}
	}

	protected function transaction_bet_subscription_payin($requestParams){

		$is_valid = $this->validateRequest($requestParams);

		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Is valid request): ', $is_valid);
		$method = $this->requestParams['method'];
		$token = $this->requestParams['token'];
		$data = [
			'method' => $method,
			'token' => $token,
		];

		if($is_valid){
			$playerInfo=$this->common_token->getPlayerInfoByToken($token);
			$playerName=$playerInfo['username'];
			$playerId=$playerInfo['playerId'];
			$validateToken=$this->common_token->isTokenValid($playerId, $token);

			$bets_data=$requestParams['params']['bet'];
			$controller=$this;

			if($validateToken) {

				$this->lockAndTransForPlayerBalance($playerId, function() use($controller, $playerInfo, &$trans_data, $bets_data) {
					$before_balance=$controller->game_api->queryPlayerBalance($playerInfo['username'])['balance'];
					$trans_data = $controller->debitCreditAmount(self::TRANSACTION_DEBIT_SUBSCRIPTION, $playerInfo, $bets_data);
					if($trans_data['code']!= self::RETURN_SUCCESS_CODE){
		                return false;
		            }
		            return true;
				});

				if($trans_data['code'] != self::RETURN_SUCCESS_CODE) {
		        	$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, $trans_data['code'], $data);
		        	return $response;
		        }

		        $params = [
		        	'balance_after' => $this->convertBalance($trans_data['after_balance']),
		        	'already_processed' => isset($trans_data['already_processed'])?$trans_data['already_processed']:0
		        ];

		        $response = $this->getErrorSuccessMessage(self::RETURN_SUCCESS, self::RETURN_SUCCESS_CODE, $data, $params);
		        
		        return $response;

	        } else {
				$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_INVALID_TOKEN, $data);

				return $response;
			}
		} else {
			
			$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_WRONG_SIGNATURE, $data);

			return $response;
		}
				
	}

	protected function transaction_bet_payout($requestParams){

		$is_valid = $this->validateRequest($requestParams);

		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Is valid request): ', $is_valid);

		$method = $this->requestParams['method'];
		$token = $this->requestParams['token'];
		$data = [
			'method' => $method,
			'token' => $token,
		];

		if($is_valid){
			$transaction_id = $this->requestParams['params']['transaction_id'];
			$bet_id = $this->requestParams['params']['bet_id'];
			$gameUsername=$requestParams['params']['player_id'];
			$playerDetails=$this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->gamePlatformId);

			if(!is_null($playerDetails) && !empty($playerDetails)) {
				$playerId=$playerDetails->player_id;
				$playerUsername=$playerDetails->username;
				$playerInfo=$this->player_model->getPlayerArrayById($playerId);

				$playerBalance=$this->game_api->queryPlayerBalance($playerUsername)['balance'];

				$controller = $this;
				$trans_data = [
					'code' => self::RETURN_SUCCESS_CODE
				];

				$this->lockAndTransForPlayerBalance($playerId, function() use($controller, $playerInfo, &$trans_data, $transaction_id, $bet_id) {
					$before_balance=$controller->game_api->queryPlayerBalance($playerInfo['username'])['balance'];
					$existingRow = $controller->betgames_wallet_transactions->isTransIdAlreadyExists($transaction_id,self::TRANSACTION_CREDIT);
		            if($existingRow) {
		            	$trans_data['code'] = self::RETURN_SUCCESS_CODE;
		                $trans_data['after_balance'] = $before_balance;
		                $trans_data['already_processed'] = 1;
		                return true;
		            }
		            $existingBet = $controller->betgames_wallet_transactions->isBetIdExists($bet_id);
		            if(!$existingBet) {
		            	$trans_data['code'] = self::ERROR_INVALID_PAYIN;
		                return true;
		            }
		            $existingBetAndTrans = $controller->betgames_wallet_transactions->isTransIdAndBetIdAlreadyExists($transaction_id,$bet_id,self::TRANSACTION_CREDIT);
		            if($existingBetAndTrans) {
		            	$trans_data['code'] = self::RETURN_SUCCESS_CODE;
		                $trans_data['after_balance'] = $before_balance;
		                $trans_data['already_processed'] = 1;
		                return true;
		            }
		            $existingBetOnPayout = $controller->betgames_wallet_transactions->isBetIdOnPayoutExists($bet_id);
		            if($existingBetOnPayout) {
		            	$trans_data['code'] = self::RETURN_SUCCESS_CODE;
		                $trans_data['after_balance'] = $before_balance;
		                $trans_data['already_processed'] = 1;
		                return true;
		            }
		            $trans_data = $controller->debitCreditAmount(self::TRANSACTION_CREDIT, $playerInfo);

		            if($trans_data['code']!= self::RETURN_SUCCESS_CODE){
		                return false;
		            }
		            return true;
		        });

		        if($trans_data['code'] != self::RETURN_SUCCESS_CODE) {
		        	$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, $trans_data['code'], $data);
		        	return $response;
		        }

		        $params = [
		        	'balance_after' => $this->convertBalance($trans_data['after_balance']),
		        	'already_processed' => isset($trans_data['already_processed'])?$trans_data['already_processed']:0
		        ];

		        $response = $this->getErrorSuccessMessage(self::RETURN_SUCCESS, self::RETURN_SUCCESS_CODE, $data, $params);
		        
		        return $response;

			} else {
				$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_INVALID_PLAYER, $data);
				unset($response['response_result_id']);

				return $response;
			}
		} else {
			
			$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_WRONG_SIGNATURE, $data);

			return $response;
		}

	}

	protected function transaction_bet_combination_payin($requestParams){

		$is_valid = $this->validateRequest($requestParams);

		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Is valid request): ', $is_valid);

		$method = $this->requestParams['method'];
		$token = $this->requestParams['token'];
		$data = [
			'method' => $method,
			'token' => $token,
		];

		if($is_valid){
			$playerInfo=$this->common_token->getPlayerInfoByToken($token);
			$playerName=$playerInfo['username'];
			$playerId=$playerInfo['playerId'];
			$validateToken=$this->common_token->isTokenValid($playerId, $token);

			$bets_data=$requestParams['params']['bet'];
			$controller=$this;

			if($validateToken) {

				$this->lockAndTransForPlayerBalance($playerId, function() use($controller, $playerInfo, &$trans_data, $bets_data) {
					$before_balance=$controller->game_api->queryPlayerBalance($playerInfo['username'])['balance'];
					$trans_data = $controller->debitCreditAmount(self::TRANSACTION_DEBIT_COMBINATION, $playerInfo, $bets_data);
					if($trans_data['code'] != self::RETURN_SUCCESS_CODE){
		                return false;
		            }
		            return true;
				});

				if($trans_data['code'] != self::RETURN_SUCCESS_CODE) {
		        	$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, $trans_data['code'], $data);
		        	return $response;
		        }

		        $params = [
		        	'balance_after' => $this->convertBalance($trans_data['after_balance']),
		        	'already_processed' => isset($trans_data['already_processed'])?$trans_data['already_processed']:0
		        ];

		        $response = $this->getErrorSuccessMessage(self::RETURN_SUCCESS, self::RETURN_SUCCESS_CODE, $data, $params);
		        
		        return $response;
	        } else {
				$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_INVALID_TOKEN, $data);
				unset($response['response_result_id']);

				return $response;
			}
		} else {
			
			$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_WRONG_SIGNATURE, $data);

			return $response;
		}
	}

	protected function transaction_bet_combination_payout($requestParams){

		$is_valid = $this->validateRequest($requestParams);

		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Is valid request): ', $is_valid);

		if($is_valid){
			$method = $this->requestParams['method'];
			$token = $this->requestParams['token'];

			$data = [
				'method' => $method,
				'token' => $token,
			];

			$gameUsername=$requestParams['params']['player_id'];
			$playerDetails=$this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->gamePlatformId);

			if(!is_null($playerDetails) && !empty($playerDetails)) {

				$playerId=$playerDetails->player_id;
				$playerUsername=$playerDetails->username;
				$playerInfo=$this->player_model->getPlayerArrayById($playerId);
				$bets_data=$requestParams['params']['bet'];

				$playerBalance=$this->game_api->queryPlayerBalance($playerUsername)['balance'];

				$controller = $this;
				$trans_data = [
					'code' => self::RETURN_SUCCESS_CODE
				];

				$this->lockAndTransForPlayerBalance($playerId, function() use($controller, $playerInfo, &$trans_data, $bets_data) {
					$before_balance=$controller->game_api->queryPlayerBalance($playerInfo['username'])['balance'];
		            $trans_data = $controller->debitCreditAmount(self::TRANSACTION_CREDIT_COMBINATION, $playerInfo, $bets_data);
		            if($trans_data['code']!= self::RETURN_SUCCESS_CODE){
		                return false;
		            }
		            return true;
		        });

		        if($trans_data['code'] != self::RETURN_SUCCESS_CODE) {
		        	$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, $trans_data['code'], $data);
		        	return $response;
		        }

		        $params = [
		        	'balance_after' => $this->convertBalance($trans_data['after_balance']),
		        	'already_processed' => isset($trans_data['already_processed'])?$trans_data['already_processed']:0
		        ];

		        $response = $this->getErrorSuccessMessage(self::RETURN_SUCCESS, self::RETURN_SUCCESS_CODE, $data, $params);
		        
		        return $response;

			} else {
				$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_INVALID_TOKEN, $data);
				unset($response['response_result_id']);

				return $response;
			}
		} else {
			
			$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_WRONG_SIGNATURE, $data);

			return $response;
		}

	}

	protected function transaction_promo_payout($requestParams){

		$is_valid = $this->validateRequest($requestParams);

		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Is valid request): ', $is_valid);

		$method = $this->requestParams['method'];
			$token = $this->requestParams['token'];
		$data = [
			'method' => $method,
			'token' => $token,
		];

		if($is_valid){
			$promo_transaction_id = $this->requestParams['params']['promo_transaction_id'];
			$bet_id = isset($this->requestParams['params']['bet_id']) ? $this->requestParams['params']['bet_id'] : null;
			$gameUsername=$requestParams['params']['player_id'];
			$playerDetails=$this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($gameUsername, $this->gamePlatformId);

			if(!is_null($playerDetails) && !empty($playerDetails)) {

				$playerId=$playerDetails->player_id;
				$playerUsername=$playerDetails->username;
				$playerInfo=$this->player_model->getPlayerArrayById($playerId);

				$controller = $this;
				$trans_data = [
					'code' => self::RETURN_SUCCESS_CODE
				];

				$this->lockAndTransForPlayerBalance($playerId, function() use($controller, $playerInfo, &$trans_data, $promo_transaction_id, $bet_id) {
					$before_balance=$controller->game_api->queryPlayerBalance($playerInfo['username'])['balance'];
					$existingRow = $controller->betgames_wallet_transactions->isPromoIdAlreadyExists($promo_transaction_id);
		            if($existingRow) {
		            	$trans_data['code'] = self::RETURN_SUCCESS_CODE;
		                $trans_data['after_balance'] = $before_balance;
		                $trans_data['already_processed'] = 1;
		                return true;
		            }
		            if(isset($bet_id)) {
			            $existingBet = $controller->betgames_wallet_transactions->isBetIdExists($bet_id);
			            if(!$existingBet) {
			            	$trans_data['code'] = self::ERROR_INVALID_PAYIN;
			                return true;
			            }
		            }
		            $trans_data = $controller->debitCreditAmount(self::TRANSACTION_CREDIT_PROMO, $playerInfo);
		            if($trans_data['code']!= self::RETURN_SUCCESS_CODE){
		                return false;
		            }
		            return true;
		        });

		        if($trans_data['code'] != self::RETURN_SUCCESS_CODE) {
		        	$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, $trans_data['code'], $data);
		        	return $response;
		        }

		        $params = [
		        	'balance_after' => $this->convertBalance($trans_data['after_balance']),
		        	'already_processed' => isset($trans_data['already_processed'])?$trans_data['already_processed']:0
		        ];

		        $response = $this->getErrorSuccessMessage(self::RETURN_SUCCESS, self::RETURN_SUCCESS_CODE, $data, $params);
		        
		        return $response;

			} else {
				$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_INVALID_TOKEN, $data);
				unset($response['response_result_id']);

				return $response;
			}
		} else {
			
			$response = $this->getErrorSuccessMessage(self::RETURN_ERROR, self::ERROR_WRONG_SIGNATURE, $data);

			return $response;
		}

	}

	protected function debitCreditAmount($transaction_type, $player_info, $bets_data=[], $extra=[]) {

		$return_data = [
            'code' => self::RETURN_SUCCESS_CODE
        ];
		$amount = $this->convertAmount($this->requestParams['params']['amount']);
		$this->CI->utils->debug_log(__FUNCTION__, 'BetGames (convert amount)', $amount);
		$trans_record=$this->requestParams;

		$return_data['before_balance'] = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];

		if($transaction_type == self::TRANSACTION_CREDIT) {
			$success=$this->wallet_model->incSubWallet($player_info['playerId'], $this->game_api->getPlatformCode(), $amount);
            $return_data['after_balance'] = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];
            if(!$success && $amount == 0){
                $return_data['code']=self::RETURN_SUCCESS_CODE;
            }
		} else if ($transaction_type == self::TRANSACTION_DEBIT) {
			if($amount > $return_data['before_balance']) {
				$return_data['code']=self::ERROR_INSUFFICIENT_BALANCE;
				$return_data['after_balance'] = $return_data['before_balance'];
			} else {
				$success=$this->wallet_model->decSubWallet($player_info['playerId'], $this->game_api->getPlatformCode(), $amount);
				if(!$success) {
					$return_data['code']=self::ERROR_ENCOUNTERED;
				}
				$return_data['after_balance'] = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];
			}
		} else if($transaction_type == self::TRANSACTION_DEBIT_SUBSCRIPTION) {
			if($amount > $return_data['before_balance']) {
				$return_data['code']=self::ERROR_INSUFFICIENT_BALANCE;
				$return_data['after_balance'] = $return_data['before_balance'];
			} else {
				foreach ($bets_data as $bet_data) {
					$existingRow = $this->betgames_wallet_transactions->isRowByTransactionId($bet_data['transaction_id']);
					if(!empty($existingRow)) {
						$return_data['code']=self::RETURN_SUCCESS_CODE;
						$return_data['already_processed']=1;
						$return_data['after_balance'] = $return_data['before_balance'];
						return $return_data;
					}
				}
				$success=$this->wallet_model->decSubWallet($player_info['playerId'], $this->game_api->getPlatformCode(), $amount);
				$return_data['after_balance'] = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];
				if($success) {
					foreach ($bets_data as $bet_data) {
						$existingRow = $this->betgames_wallet_transactions->isRowByTransactionId($bet_data['transaction_id']);
						if(empty($existingRow)) {
							$this->processTransactionRecord($trans_record, $player_info, $transaction_type, $return_data, $bet_data);
						}

					}
				} else {
					$return_data['code']=self::ERROR_ENCOUNTERED;
				}
				return $return_data;
			}
		} else if($transaction_type == self::TRANSACTION_DEBIT_COMBINATION) {
			if($amount > $return_data['before_balance']) {
				$return_data['code']=self::ERROR_INSUFFICIENT_BALANCE;
				$return_data['after_balance'] = $return_data['before_balance'];
			} else {
				foreach ($bets_data as $bet_data) {
					$existingRow = $this->betgames_wallet_transactions->isRowByTransactionId($bet_data['transaction_id']);
					if(!empty($existingRow)) {
						$return_data['code']=self::RETURN_SUCCESS_CODE;
						$return_data['already_processed']=1;
						$return_data['after_balance'] = $return_data['before_balance'];
						return $return_data;
					}
				}
				$success=$this->wallet_model->decSubWallet($player_info['playerId'], $this->game_api->getPlatformCode(), $amount);
				$return_data['after_balance'] = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];
				if($success) {
					$this->processTransactionRecord($trans_record, $player_info, $transaction_type, $return_data);
				} else {
					$return_data['code']=self::ERROR_ENCOUNTERED;
				}
				return $return_data;
			}
		} else if($transaction_type == self::TRANSACTION_CREDIT_COMBINATION) {
			$combination_id = $this->requestParams['params']['combination_id'];
			$existingCombination = $this->betgames_wallet_transactions->isCombinationIdAlreadyExists($combination_id,self::TRANSACTION_DEBIT_COMBINATION);
			if(empty($existingCombination)){
				$return_data['code']=self::ERROR_INVALID_PAYIN;
			}
			if($return_data['code'] == self::RETURN_SUCCESS_CODE) {
				$existingRow = $this->betgames_wallet_transactions->isCombinationIdAlreadyExists($combination_id, self::TRANSACTION_CREDIT_COMBINATION);
				if($existingRow) {
					$return_data['code']=self::RETURN_SUCCESS_CODE;
					$return_data['already_processed']=1;
					$return_data['after_balance'] = $return_data['before_balance'];
					return $return_data;
				}
				$success=$this->wallet_model->incSubWallet($player_info['playerId'], $this->game_api->getPlatformCode(), $amount);
		        $return_data['after_balance'] = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];
	            if($success){
					$this->processTransactionRecord($trans_record, $player_info, $transaction_type, $return_data);
				}
			}
			return $return_data;

		} else if($transaction_type == self::TRANSACTION_CREDIT_PROMO) {
			$success=$this->wallet_model->incSubWallet($player_info['playerId'], $this->game_api->getPlatformCode(), $amount);
            if(!$success){
                $return_data['code']=self::ERROR_ENCOUNTERED;
            }
            $return_data['after_balance'] = $this->game_api->queryPlayerBalance($player_info['username'])['balance'];
        }

		$this->processTransactionRecord($trans_record, $player_info, $transaction_type, $return_data);

		return $return_data;

	}

	private function processTransactionRecord(&$trans_record, $player_info, $transaction_type, $return_data, $bet_data=null) {
		// print_r($trans_record);exit;
		$wallet_trans=[];

		$wallet_trans['player_name'] = $player_info['playerId'];
        $wallet_trans['method'] = isset($trans_record['method']) ? $trans_record['method'] : null;
        $wallet_trans['amount'] = isset($trans_record['params']['amount']) && $transaction_type != self::TRANSACTION_DEBIT_SUBSCRIPTION ? $this->convertAmount($trans_record['params']['amount']) : $this->convertAmount($bet_data['amount']);
        $wallet_trans['currency'] = isset($trans_record['params']['currency']) ? $trans_record['params']['currency'] : null;
        $wallet_trans['bet_id'] = isset($trans_record['params']['bet_id']) && !isset($bet_data) ? $trans_record['params']['bet_id'] : $bet_data['bet_id'];
        $wallet_trans['transaction_id'] = isset($trans_record['params']['transaction_id']) && !isset($bet_data) ? $trans_record['params']['transaction_id'] : $bet_data['transaction_id'];
        $wallet_trans['promo_transaction_id'] = isset($trans_record['params']['promo_transaction_id']) ? $trans_record['params']['promo_transaction_id'] : null;
        $wallet_trans['retrying'] = isset($trans_record['params']['retrying']) ? $trans_record['params']['retrying'] : null;
        $wallet_trans['bet'] = isset($trans_record['params']['bet']) && !isset($bet_data) ? json_encode($trans_record['params']['bet']) : json_encode($bet_data);
        $wallet_trans['bet_type'] = isset($trans_record['params']['bet_type']) ? $trans_record['params']['bet_type'] : null;
        $wallet_trans['type'] = isset($trans_record['params']['type']) ? $trans_record['params']['type'] : null;
        $wallet_trans['odd'] = isset($trans_record['params']['odd']) && is_array($trans_record['params']['odd']) ? $trans_record['params']['odd']['value'] : (isset($trans_record['params']['odd_value']) ? $trans_record['params']['odd_value'] : (isset($trans_record['params']['odd']) && !is_array($trans_record['params']['odd']) ? $trans_record['params']['odd'] : null));
        $wallet_trans['bet_time'] = isset($trans_record['params']['bet_time']) ? $trans_record['params']['bet_time'] : null;
        /*$wallet_trans['game'] = isset($trans_record['params']['game']) ? json_encode($trans_record['params']['game']) : (
            $transaction_type != self::TRANSACTION_CREDIT_COMBINATION?json_encode($bet_data['game']): ($transaction_type == self::TRANSACTION_DEBIT_COMBINATION) ? self::COMBO : null
        );*/
        $wallet_trans['game'] = null;
        if(isset($trans_record['params']['game'])){
            $wallet_trans['game'] = json_encode($trans_record['params']['game']);
        }else{
            if($transaction_type != self::TRANSACTION_CREDIT_COMBINATION){
                $wallet_trans['game'] = json_encode($bet_data['game']);
            }else{
                if($transaction_type == self::TRANSACTION_DEBIT_COMBINATION){
                    $wallet_trans['game'] = self::COMBO;
                }
            }
        }
        
        
        $wallet_trans['draw_code'] = isset($trans_record['params']['draw_code']) && !isset($bet_data) ? $trans_record['params']['draw_code'] : ($transaction_type==self::TRANSACTION_DEBIT_COMBINATION?$bet_data['draw']['code']:null);
        $wallet_trans['draw_time'] = isset($trans_record['params']['draw_time']) && !isset($bet_data) ? $trans_record['params']['draw_time'] : ($transaction_type==self::TRANSACTION_DEBIT_COMBINATION?$bet_data['draw']['time']:null);
        $wallet_trans['subscription_id'] = isset($trans_record['params']['subscription_id']) ? $trans_record['params']['subscription_id'] : null;
        $wallet_trans['subscription_time'] = isset($trans_record['params']['subscription_time']) ? $trans_record['params']['subscription_time'] : null;
        $wallet_trans['combination_id'] = isset($trans_record['params']['combination_id']) ? $trans_record['params']['combination_id'] : null;
        $wallet_trans['combination_time'] = isset($trans_record['params']['combination_time']) ? $trans_record['params']['combination_time'] : null;
        $wallet_trans['is_mobile'] = isset($trans_record['params']['is_mobile']) ? $trans_record['params']['is_mobile'] : null;
        $wallet_trans['action'] = $transaction_type;
        $wallet_trans['before_balance'] = $return_data['before_balance'];
        $wallet_trans['after_balance'] = $return_data['after_balance'];
        $wallet_trans['external_uniqueid'] = isset($trans_record['params']['transaction_id'])?$transaction_type."-".$trans_record['params']['transaction_id']:(isset($trans_record['params']['promo_transaction_id'])?$transaction_type."-".$trans_record['params']['promo_transaction_id']:(isset($trans_record['params']['combination_id']) ? $transaction_type."-".$trans_record['params']['combination_id'] : $transaction_type."-".$bet_data['transaction_id']));
        $wallet_trans['odd_name'] = isset($trans_record['params']['odd']['name']) ? $trans_record['params']['odd']['name'] : null;

        $this->CI->utils->debug_log(__FUNCTION__, 'BetGames (Wallet Trans): ', $wallet_trans);

        if($return_data['code'] == self::RETURN_SUCCESS_CODE) {
            $this->betgames_wallet_transactions->insertTransaction($wallet_trans);
        }

        $return_data=$wallet_trans;

        return $return_data;
	}

	private function getErrorSuccessMessage($status,$code,$data=null,$params=null){
		// print_r($status);exit;
		switch ($code) {
			case self::RETURN_SUCCESS_CODE:
				$error_text=null;
				break;
			case self::ERROR_WRONG_SIGNATURE:
				$error_text='wrong signature';
				break;
			case self::ERROR_INVALID_TOKEN:
				$error_text='invalid token';
				break;
			case self::ERROR_INVALID_PAYIN:
				$error_text='there is no PAYIN with provided bet_id';
				break;
			case self::ERROR_INVALID_PLAYER:
				$error_text='Invalid player';
				break;
			case self::ERROR_INSUFFICIENT_BALANCE:
				$error_text='Insufficient balance';
				break;
			case self::ERROR_INVALID_TIMESTAMP:
				$error_text='Invalid timestamp';
				break;
			case self::ERROR_ENCOUNTERED:
				$error_text='Error encountered on server';
				break;
			
			default:
				$message = $status ? "Success" : "Unknown Error";
				break;
		}

		$response_data = array(
				"success" => $status,
				"error_code" => $code,
				"error_text" => $error_text,
				"time" => $this->utils->getTimestampNow(),
		);

		if($code==self::RETURN_SUCCESS_CODE){
			$response_data['params']=$params;
		}

		$response = array_merge($data, $response_data);
		$response['signature'] = $this->encryptMd5String($response);
		$response_result_id = $this->saveResponseResult($status,$response);
		$response['response_result_id'] = $response_result_id;

		return $response;

	}

	private function saveResponseResult($success, $response){
        $flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        return $this->CI->response_result->saveResponseResult(
        	$this->gamePlatformId, 
        	$flag, 
        	json_encode($this->requestParams['method']), 
        	json_encode($this->requestParams['params']), 
        	$response, 
        	200, 
        	null, 
        	null
        );
    }

    private function convertBalance($amount) {
    	$amount = $amount * $this->game_api->conversion_rate;

    	return $amount;
    }

    private function convertAmount($amount) {
    	$amount = $amount / $this->game_api->conversion_rate;

    	return $amount;
    }

}