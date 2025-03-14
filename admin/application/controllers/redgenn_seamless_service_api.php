<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Redgenn_seamless_service_api extends BaseController {

    public  $game_platform_id,
            $start_time,
            $load,
            $host_name,
            $method, 
            $game_api, 
            $currency,
            $request, 
            $headers, 
            $response_result_id,
            $end_time, $output, 
            $request_method,
            $platform_id,
            $partner_id,
            $country,
			$dont_response_on_round_bet_on_player
			;

    #error codes from GP

    #additional error codes
    const SUCCESS      = 'success';
	const SYSTEM_ERROR = 'WL_ERROR';
	const INVALID_KEY  = 'INVALID_KEY';
	const GAME_NOT_ALLOWED = 'GAME_NOT_ALLOWED';
	const NOT_ENOUGH_MONEY = 'NOT_ENOUGH_MONEY';
	
	#additional error codes
	const TRANSACTION_ALREADY_EXIST = 'TRANSACTION_ALREADY_EXIST';
	const AMOUNT_INVALID = 'AMOUNT_INVALID';
	const TRANSACTION_NOT_FOUND = 'TRANSACTION_NOT_FOUND';
    const TRANSACTION_ALREADY_SETTLED = 'TRANSACTION_ALREADY_SETTLED';
    const TRANSACTION_ALREADY_REFUNDED = 'TRANSACTION_ALREADY_REFUNDED';
	
	#method
    const METHOD_ENTER = 'enter';
    const METHOD_GET_BALANCE = 'getbalance';
    const METHOD_ROUND_BET = 'roundbet';
    const METHOD_ROUND_WIN = 'roundwin';
    const METHOD_REFUND = 'refund';
    const METHOD_LOGOUT = 'logout';

    #error messages
	const ERROR_MESSAGE = [
		self::SUCCESS         				=> 'Success',
		self::SYSTEM_ERROR     				=> 'System error',
		self::INVALID_KEY     				=> 'Invalid key',
		self::GAME_NOT_ALLOWED     			=> 'Game Not allowed',
		self::NOT_ENOUGH_MONEY     			=> 'Not enough money',

		#additional error messages
		self::TRANSACTION_ALREADY_EXIST     => 'Transaction already exist',
		self::AMOUNT_INVALID     			=> 'Invalid amount',
		self::TRANSACTION_NOT_FOUND     	=> 'Transaction not found',
		self::TRANSACTION_ALREADY_SETTLED   => 'Transaction already settled',
		self::TRANSACTION_ALREADY_REFUNDED => 'Transaction already refunded',
	];

	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','game_logs','redgenn_seamless_transactions'));
		$this->parseRequest();
		$this->retrieveHeaders();
		$this->start_time 		= microtime(true);
		$this->host_name 		= $_SERVER['HTTP_HOST'];
		$this->method 			= $_SERVER['REQUEST_METHOD'];
		$this->request_method 	= null;
	}

	public function index($platform_id){
		$this->game_platform_id = $platform_id;
		return $this->selectMethod();			
	}

	public function selectMethod(){
		if(isset($this->request[self::METHOD_ENTER])){
			$this->request_method = self::METHOD_ENTER;
			$this->enter();
		}else if(isset($this->request[self::METHOD_GET_BALANCE])){
			$this->request_method = self::METHOD_GET_BALANCE;
			$this->getbalance();

		}else if(isset($this->request[self::METHOD_ROUND_BET])){
			$this->request_method = self::METHOD_ROUND_BET;
			$this->roundbet();

		}else if(isset($this->request[self::METHOD_ROUND_WIN])){
			$this->request_method = self::METHOD_ROUND_WIN;
			$this->roundwin();

		}else if(isset($this->request[self::METHOD_REFUND])){
			$this->request_method = self::METHOD_REFUND;
			$this->refund();

		}else if(isset($this->request[self::METHOD_LOGOUT])){
			$this->request_method = self::METHOD_LOGOUT;
			$this->logout();

		}else{
			$this->utils->debug_log('REDGENN seamless service: Invalid API Method');
			http_response_code(404);

		}
	}

	public function generatePlayerToken(){
		if(!$this->initialize()) {
			return [
				'success'   => 'error',
			];
		}
		if(!$this->game_api->validateWhiteIP()){
			return [
				'success'   => 'error',
				'message'   => 'invalid ip',
			];
		}

		$this->CI->load->model(array('player_model'));
		$player_username 	= isset($this->request['username']) ? $this->request['username'] : null;
        $player_id 			= $this->CI->player_model->getPlayerIdByUsername($player_username);
		$token			 	= $this->common_token->getPlayerToken($player_id);
		$response =  [
			'username' => $player_username,
			'token'    => $token,
		];

		print_r($response); exit;
	}

	public function initialize(){
		$this->game_api 		= $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->game_api){
			$this->utils->debug_log("REDGENN SEAMLESS SERVICE: (initialize) ERROR lOAD: ", $this->game_platform_id);
			return false;
        }
        $this->currency 	= $this->game_api->getCurrency();
		$this->dont_response_on_round_bet_on_player = $this->game_api->dont_response_on_round_bet_on_player;
		return true;
	}

    public function getError($errorCode){
        $error_response = [
            'code'    => $errorCode,
            'message' => self::ERROR_MESSAGE[$errorCode],
        ];

        return $error_response;
    }

    public function enter(){
		$this->CI->load->model('external_common_tokens');
        $this->utils->debug_log("REDGENN SEAMLESS SERVICE:" .$this->method);	
		$externalResponse 			= $this->externalQueryResponse();	
		$callType 					= $this->request_method;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$requiredParams 			= ['key'];
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_ERROR);
            }

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::GAME_NOT_ALLOWED);
			}

			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByExternalCommonToken($this->request['enter']['@attributes']['key']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_KEY);
            }

			$player_id = $player->player_id;

			$external_common_token_data = $this->external_common_tokens->getExternalCommonTokenInfoByToken($this->request['enter']['@attributes']['key']);
			$external_common_token_extra_info = json_decode($external_common_token_data['extra_info'],true);
			if(!empty($external_common_token_extra_info) && isset($external_common_token_extra_info['is_used']) && $external_common_token_extra_info['is_used'] == true){
				throw new Exception(self::INVALID_KEY);
			}

			if(!$this->isParamsRequired($this->request['enter']['@attributes'],$requiredParams)){
				throw new Exception(self::SYSTEM_ERROR);
			}

		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

        $balance = $this->game_api->getPlayerBalanceById($player_id);

		if($success){
			$extra_info = [
				'is_used' => true,
				'guid'	  => $this->request['enter']['@attributes']['guid']
			];	
			$this->CI->external_common_tokens->updatePlayerExternalExtraInfo($player_id, $this->request['enter']['@attributes']['key'], $this->game_api->getPlatformCode(), json_encode($extra_info));
			$externalResponse = [
				'enter' => [
					'@attributes' => [
						'id' => $this->request['enter']['@attributes']['id'],
						'result' => 'ok'
					],
					'balance' => [
						'@attributes' => [
							'currency' => $this->currency,
							'type' => 'real',
							'value' => $this->convertToNonDecimal($balance),
							'version' => '1'
						]
					],
					'user' => [
						'@attributes' => [
							'type' => 'real',
							'wlid' => $game_username
						]
					]
				]
			];
        }else{
            $externalResponse = [
				self::METHOD_ENTER => [
					'@attributes' => [
						'id' => $this->request[self::METHOD_ENTER]['@attributes']['id'],
						'result' => 'fail'
					],
					'error' => [
						'@attributes' => [
							'code' => $errorCode
						],
						'msg' => self::ERROR_MESSAGE[$errorCode]
					]
				]
			];
        }

		$fields = [
			'player_id'	=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
    }

	public function getbalance(){
		$this->CI->load->model('external_common_tokens');
        $this->utils->debug_log("REDGENN SEAMLESS SERVICE:" .$this->method);	
		$externalResponse 			= $this->externalQueryResponse();	
		$callType 					= self::METHOD_GET_BALANCE;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$requiredParams 			= ['wlid'];
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_ERROR);
            }

			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::GAME_NOT_ALLOWED);
			}

			if(!$this->isParamsRequired($this->request[self::METHOD_GET_BALANCE]['@attributes'],$requiredParams)){
				throw new Exception(self::SYSTEM_ERROR);
			}

			list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request[self::METHOD_GET_BALANCE]['@attributes']['wlid']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_KEY);
            }

			$player_id = $player->player_id;

			$token = $this->external_common_tokens->getExternalToken($player_id, $this->game_api->getPlatformCode());
			$external_common_token_data = $this->external_common_tokens->getExternalCommonTokenInfoByToken($token);
			$external_common_token_extra_info = json_decode($external_common_token_data['extra_info'],true);

			if(!isset($external_common_token_extra_info['guid']) || $external_common_token_extra_info['guid'] != $this->request[self::METHOD_GET_BALANCE]['@attributes']['guid']){
				throw new Exception(self::SYSTEM_ERROR);
			}
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

        $balance = $this->game_api->getPlayerBalanceById($player_id);
		if($success){
			$externalResponse = [
				'getbalance' => [
					'@attributes' => [
						'id' => $this->request[self::METHOD_GET_BALANCE]['@attributes']['id'],
						'result' => 'ok'
					],
					'balance' => [
						'@attributes' => [
							'value' => $this->game_api->dBtoGameAmount($balance),
							'version' => time(), 
							'type' => 'real',
							'currency' => $this->currency
						]
					],
				]
			];
        }else{
            $externalResponse = [
				self::METHOD_GET_BALANCE => [
					'@attributes' => [
						'id' => $this->request[self::METHOD_GET_BALANCE]['@attributes']['id'],
						'result' => 'fail'
					],
					'error' => [
						'@attributes' => [
							'code' => $errorCode
						],
						'msg' => self::ERROR_MESSAGE[$errorCode]
					]
				]
			];
        }

		$fields = [
			'player_id'	=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
    }

	private function convertToNonDecimal($amount){
		#Multiply by 100 to shift the decimal places and cast to an integer
		$nonDecimalAmount = $amount * 100;
		return intval($nonDecimalAmount);
	}

	private function convertToDecimal($amount){
		$nonDecimalAmount = $amount / 100;
		return floatval($nonDecimalAmount);
	}

	private function array_to_xml($array, &$xml) {
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				if ($key === '@attributes') {
					foreach ($value as $attr_key => $attr_value) {
						$xml->addAttribute($attr_key, $attr_value);
					}
				} else {
					$subnode = $xml->addChild($key);
					$this->array_to_xml($value, $subnode);
				}
			} else {
				$xml->addChild($key, htmlspecialchars($value));
			}
		}
	}

	public function roundbet(){
		$this->CI->load->model('external_common_tokens');
		$callType 					= self::METHOD_ROUND_BET;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= ['transaction_date','request_id','guid','wlid','bet','bet_type','finished','transaction_id'];
		$this->utils->debug_log('REDGENN-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::GAME_NOT_ALLOWED);
			}

            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request[self::METHOD_ROUND_BET]['@attributes']['wlid']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_KEY);
            }

			$player_id = $player->player_id;

			$token = $this->external_common_tokens->getExternalToken($player_id, $this->game_api->getPlatformCode());
			$external_common_token_data = $this->external_common_tokens->getExternalCommonTokenInfoByToken($token);
			$external_common_token_extra_info = json_decode($external_common_token_data['extra_info'],true);

			if(!isset($external_common_token_extra_info['guid']) || $external_common_token_extra_info['guid'] != $this->request[self::METHOD_ROUND_BET]['@attributes']['guid']){
				throw new Exception(self::SYSTEM_ERROR);
			}
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	
		$transaction_time = isset($this->request['@attributes']['time']) ? $this->request['@attributes']['time'] : null;
		$session_id = isset($this->request['@attributes']['session']) ? $this->request['@attributes']['session'] : null;
		$request = $this->request[self::METHOD_ROUND_BET]['@attributes'];
		$giftspin = isset($this->request[self::METHOD_ROUND_BET]['giftspin']) ? $this->request[self::METHOD_ROUND_BET]['giftspin'] : null;
		$round_id = isset($this->request[self::METHOD_ROUND_BET]['roundnum']['@attributes']['id']) ? $this->request[self::METHOD_ROUND_BET]['roundnum']['@attributes']['id'] : null;
		$params = [
			'request_id' 		=> 	isset($request['id']) ? $request['id'] : null,
			'guid' 				=> 	isset($request['guid']) ? $request['guid'] : null,
			'wlid' 				=> 	isset($request['wlid']) ? $request['wlid'] : null,
			'bet' 				=> 	isset($request['bet']) ?  $this->game_api->gameAmountToDBTruncateNumber($request['bet']) : 0,
			'bet_type' 			=> 	isset($request['type']) ? $request['type'] : null,
			'finished' 			=> 	isset($request['finished']) ? $request['finished'] : null,
			'game_id' 			=> 	isset($request['game_name']) ? $request['game_name'] : null,
			'transaction_date'  => 	$transaction_time,
			'transaction_id' 	=> 	isset($request['id']) ? $request['id'] : null,
			'round_id' 			=> 	$round_id,
			'session' 			=> 	$session_id,
			'trans_type'		=> 	$callType,
			'external_uniqueid' => 	isset($request['id']) ? $request['id'] : null,
		];

		#ignored bet amount when have giftspin
		if($giftspin){
			$params['bet'] = 0;
		}
			
		#checking params 
		if(!$this->isParamsRequired($params,$requiredParams)){
			$this->utils->error_log("REDGENN-invalid parameters");
			$errorCode 	= self::SYSTEM_ERROR;
			$success 	= false;
		}

		#force to return empty on roundbet to trigger refund
		if($this->dont_response_on_round_bet_on_player === $params['wlid']){
			$externalResponse = null;
			$fields = [
				'player_id'		=> $player_id,
			];
			return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);
		}

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);
				$this->utils->debug_log("REDGENN adjustWalletResponse");
				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['after_balance'];
				return true;
			});
		}

		$this->utils->debug_log("REDGENN SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if(!$trans_success && $errorCode == self::SUCCESS){
			$errorCode 	= self::SYSTEM_ERROR;
			// $balance = $this->game_api->getPlayerBalanceById($player_id);
		}

		if($success && $trans_success){
			$externalResponse = [
				'roundbet' => [
					'@attributes' => [
						'id' => $this->request[self::METHOD_ROUND_BET]['@attributes']['id'],
						'result' => 'ok'
					],
					'balance' => [
						'@attributes' => [
							'value' => $this->game_api->dBtoGameAmount($balance),
							'version' => '1',
							'type' => 'real',
							'currency' => $this->currency
						]
					],
				]
			];
        }else{
			$externalResponse = [
				self::METHOD_ROUND_BET => [
					'@attributes' => [
						'id' => $this->request[self::METHOD_ROUND_BET]['@attributes']['id'],
						'result' => 'fail'
					],
					'error' => [
						'@attributes' => [
							'code' => $errorCode
						],
						'msg' => self::ERROR_MESSAGE[$errorCode]
					]
				]
			];
        }
		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function roundwin(){
		$callType 					= self::METHOD_ROUND_WIN;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= ['transaction_date','request_id','guid','wlid','win','bet_type','finished','transaction_id'];
		$this->utils->debug_log('REDGENN-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::GAME_NOT_ALLOWED);
			}

            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request[self::METHOD_ROUND_WIN]['@attributes']['wlid']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_KEY);
            }

			$player_id = $player->player_id;
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	
		$transaction_time = isset($this->request['@attributes']['time']) ? $this->request['@attributes']['time'] : null;
		$session_id = isset($this->request['@attributes']['session']) ? $this->request['@attributes']['session'] : null;
		$request = $this->request[self::METHOD_ROUND_WIN]['@attributes'];
		$round_id = isset($this->request[self::METHOD_ROUND_WIN]['roundnum']['@attributes']['id']) ? $this->request[self::METHOD_ROUND_WIN]['roundnum']['@attributes']['id'] : null;

		$params = [
			'request_id' 		=> 	isset($request['id']) ? $request['id'] : null,
			'guid' 				=> 	isset($request['guid']) ? $request['guid'] : null,
			'wlid' 				=> 	isset($request['wlid']) ? $request['wlid'] : null,
			'win' 				=> 	isset($request['win']) ?  $this->game_api->gameAmountToDBTruncateNumber($request['win']) : 0,
			'bet_type' 			=> 	isset($request['type']) ? $request['type'] : null,
			'finished' 			=> 	isset($request['finished']) ? $request['finished'] : null,
			'game_id' 			=> 	isset($request['game_name']) ? $request['game_name'] : null,
			'transaction_date'  => 	$transaction_time,
			'transaction_id' 	=> 	isset($request['id']) ? $request['id'] : null,
			'round_id' 			=> 	$round_id,
			'session' 			=> 	$session_id,
			'trans_type'		=> 	$callType,
			'external_uniqueid' => 	isset($request['id']) ? $request['id'] : null,
		];

		#checking params 
		if(!$this->isParamsRequired($params,$requiredParams)){
			$this->utils->error_log("REDGENN-invalid parameters");
			$errorCode 	= self::SYSTEM_ERROR;
			$success 	= false;
		}

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("REDGENN-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['after_balance'];
				return true;
			});
		}

		$this->utils->debug_log("REDGENN SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if(!$trans_success){
			$errorCode 	= self::SYSTEM_ERROR;
		}

		if(empty($balance)){
			$balance = $this->game_api->getPlayerBalanceById($player_id);
			$this->utils->debug_log("REDGENN SEAMLESS SERVICE-roundwin will get current balance");
		}

		if($success && $trans_success){
			$externalResponse = [
				'roundwin' => [
					'@attributes' => [
						'id' => $this->request[self::METHOD_ROUND_WIN]['@attributes']['id'],
						'result' => 'ok'
					],
					'balance' => [
						'@attributes' => [
							'value' => $this->convertToNonDecimal($balance),
							'version' => '1',
							'type' => 'real',
							'currency' => $this->currency
						]
					],
				]
			];
        }else{
            $externalResponse = [
				self::METHOD_ROUND_WIN => [
					'@attributes' => [
						'id' => $this->request[self::METHOD_ROUND_WIN]['@attributes']['id'],
						'result' => 'fail'
					],
					'error' => [
						'@attributes' => [
							'code' => $errorCode
						],
						'msg' => self::ERROR_MESSAGE[$errorCode]
					]
				]
			];
        }
		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function refund(){
		$callType 					= self::METHOD_REFUND;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= [];
		$this->utils->debug_log('REDGENN-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::GAME_NOT_ALLOWED);
			}

            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request[self::METHOD_REFUND]['@attributes']['wlid']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_KEY);
            }

			$player_id = $player->player_id;

			$token = $this->external_common_tokens->getExternalToken($player_id, $this->game_api->getPlatformCode());
			$external_common_token_data = $this->external_common_tokens->getExternalCommonTokenInfoByToken($token);
			$external_common_token_extra_info = json_decode($external_common_token_data['extra_info'],true);

			if(!isset($external_common_token_extra_info['guid']) || $external_common_token_extra_info['guid'] != $this->request[self::METHOD_REFUND]['@attributes']['guid']){
				throw new Exception(self::SYSTEM_ERROR);
			}
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	

		$transaction_time = isset($this->request['@attributes']['time']) ? $this->request['@attributes']['time'] : null;
		$session_id = isset($this->request['@attributes']['session']) ? $this->request['@attributes']['session'] : null;
		$request = $this->request[self::METHOD_REFUND]['@attributes'];
		$round_id = isset($this->request[self::METHOD_REFUND]['storno']['roundnum']['@attributes']['id']) ? $this->request[self::METHOD_REFUND]['storno']['roundnum']['@attributes']['id'] : null;
		$reference_id = isset($this->request[self::METHOD_REFUND]['storno']['@attributes']['id']) ? $this->request[self::METHOD_REFUND]['storno']['@attributes']['id'] : null;
		$params = [
			'request_id' 		=> 	isset($request['id']) ? $request['id'] : null,
			'guid' 				=> 	isset($request['guid']) ? $request['guid'] : null,
			'wlid' 				=> 	isset($request['wlid']) ? $request['wlid'] : null,
			'bet' 				=> 	isset($request['cash']) ? $this->game_api->gameAmountToDBTruncateNumber($request['cash']) : 0,
			'game_id' 			=> 	isset($request['game_name']) ? $request['game_name'] : null,
			'transaction_date'  => 	$transaction_time,
			'transaction_id' 	=> 	isset($request['id']) ? $request['id'] : null,
			'round_id' 			=> 	$round_id,
			'reference_id'		=> 	$reference_id,
			'session' 			=> 	$session_id,
			'trans_type'		=> 	$callType,
			'external_uniqueid' => 	isset($request['id']) ? $request['id'] : null,
		];

		#checking params 
		if(!$this->isParamsRequired($params,$requiredParams)){
			$this->utils->error_log("REDGENN-invalid parameters");
			$errorCode 	= self::SYSTEM_ERROR;
			$success 	= false;
		}

		if($success){
			$trans_success = $this->lockAndTransForPlayerBalance($player->player_id, function() use($callType,$player,$params,
			&$errorCode, &$adjustWalletResponse,&$balance
			) {
				$this->utils->debug_log("REDGENN-lockAndTransForPlayerBalance proceed with adjusting wallet");
				$adjustWalletResponse = $this->adjustWallet($callType, $player, $params);

				if(!$adjustWalletResponse['success']){
					$errorCode = $adjustWalletResponse['code'];
					return false;	
				}
				$balance = $adjustWalletResponse['after_balance'];
				return true;
			});
		}

		$this->utils->debug_log("REDGENN SEAMLESS SERVICE-lockAndTransForPlayerBalance", $trans_success);

		if(!$trans_success && $errorCode == self::SUCCESS){
			$errorCode 	= self::SYSTEM_ERROR;
		}

		if(empty($balance)){
			$balance = $this->game_api->getPlayerBalanceById($player_id);
			$this->utils->debug_log("REDGENN SEAMLESS SERVICE-refund will get current balance");
		}

		if($success && $trans_success){
			$externalResponse = [
				'refund' => [
					'@attributes' => [
						'id' => $this->request[self::METHOD_REFUND]['@attributes']['id'],
						'result' => 'ok'
					],
					'balance' => [
						'@attributes' => [
							'value' => $this->convertToNonDecimal($balance),
							'version' => '1',
							'type' => 'real',
							'currency' => $this->currency
						]
					],
				]
			];
        }else{
            $externalResponse = [
				self::METHOD_REFUND => [
					'@attributes' => [
						'id' => $this->request[self::METHOD_REFUND]['@attributes']['id'],
						'result' => 'fail'
					],
					'error' => [
						'@attributes' => [
							'code' => $errorCode
						],
						'msg' => self::ERROR_MESSAGE[$errorCode]
					]
				]
			];
        }
		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

	public function logout(){
		$callType 					= self::METHOD_LOGOUT;
		$errorCode 					= self::SUCCESS;
		$externalResponse			= [];
		$player_id 					= null;
		$success 					= true;
		$balance					= null;
		$trans_success 				= false;
        $requiredParams 			= [];
		$this->utils->debug_log('REDGENN-transaction:',$this->request);
		try { 
			if(!$this->initialize()) {
                throw new Exception(self::SYSTEM_ERROR);
            }

            if($this->utils->setNotActiveOrMaintenance($this->game_platform_id)) {
                throw new Exception(self::SYSTEM_ERROR);
            }
			
			if(!$this->game_api->validateWhiteIP()){
				throw new Exception(self::GAME_NOT_ALLOWED);
			}

            list($getPlayerStatus, $player, $game_username, $player_username) = $this->getPlayerByGameUsername($this->request[self::METHOD_LOGOUT]['@attributes']['wlid']);
            if(!$getPlayerStatus){
                throw new Exception(self::INVALID_KEY);
            }

			$player_id = $player->player_id;

			$token = $this->external_common_tokens->getExternalToken($player_id, $this->game_api->getPlatformCode());
			$external_common_token_data = $this->external_common_tokens->getExternalCommonTokenInfoByToken($token);
			$external_common_token_extra_info = json_decode($external_common_token_data['extra_info'],true);

			if(!isset($external_common_token_extra_info['guid']) || $external_common_token_extra_info['guid'] != $this->request[self::METHOD_LOGOUT]['@attributes']['guid']){
				throw new Exception(self::SYSTEM_ERROR);
			}
		} catch (Exception $error) {
			$errorCode 	= $error->getMessage();
			$success 	= false;
		}	
		
		if($success){
			$balance = $this->game_api->getPlayerBalanceById($player_id);
			$externalResponse = [
				self::METHOD_LOGOUT => [
					'@attributes' => [
						'id' => $this->request[self::METHOD_LOGOUT]['@attributes']['id'],
						'result' => 'ok'
					],
					'balance' => [
						'@attributes' => [
							'value' => $this->convertToNonDecimal($balance),
							'version' => '1',
							'type' => 'real',
							'currency' => $this->currency
						]
					],
				]
			];
        }else{
            $externalResponse = [
				self::METHOD_LOGOUT => [
					'@attributes' => [
						'id' => $this->request[self::METHOD_LOGOUT]['@attributes']['id'],
						'result' => 'fail'
					],
					'error' => [
						'@attributes' => [
							'code' => $errorCode
						],
						'msg' => self::ERROR_MESSAGE[$errorCode]
					]
				]
			];
        }
		$fields = [
			'player_id'		=> $player_id,
		];

		return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $errorCode, $fields);	
    }

    public function getPlayerByGameUsername($gameusername){
        $this->CI->load->model('game_provider_auth');
        if($gameusername){
            $player = $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($gameusername, $this->game_api->getPlatformCode());
            if(!$player){
                return [false, null, null, null];
            }
			$this->utils->debug_log('REDGENN SEAMLESS-getPlayerByGameUsername', $gameusername,$this->game_api->getPlatformCode(),$player);
            return [true, $player, $player->game_username, $player->username];
        }
    }

	public function adjustWallet($transaction_type,$player_info,$data){
		
		$uniqueid_of_seamless_service 	= $this->game_platform_id . '-' . $data['external_uniqueid'];
		$playerId	 					= $player_info->player_id;
		$balance						= $this->game_api->getPlayerBalanceById($playerId);
		$currency						= $this->game_api->currency;
		$tableName 						= $this->game_api->getTransactionsTable();
		$game_code 						= isset($data['game_id']) ? $data['game_id'] : null;
		$this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service,$game_code);
		$this->wallet_model->setGameProviderRoundId($data['round_id']);
		if($transaction_type == self::METHOD_ROUND_BET){
			$amount = $data['bet'] * -1; #getting the negative betting value
			$data['bet_amount'] = $data['bet'];
			$existingTrans  = $this->redgenn_seamless_transactions->isTransactionExist($tableName, $data);
			if(!empty($existingTrans)){
				$this->utils->debug_log('REDGENN-'. self::METHOD_ROUND_BET, 'transaction already exist');
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId, $existingTrans->before_balance, $existingTrans->after_balance);
			}

			// #get bet transaction
			// $where = [
			// 	'round_id' => $data['round_id'],
			// 	'trans_type' => 'roundbet'
			// ];
			// $bet_transaction_details = $this->redgenn_seamless_transactions->getTransaction($tableName, $where);
			// if(!empty($bet_transaction_details)){
			// 	#cheks if same round already exist, returns success if found
			// 	return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			// }

			if($data['bet'] < 0){
				return $this->resultForAdjustWallet(self::AMOUNT_INVALID, $playerId);
			}
			if($balance < $data['bet']){
				return $this->resultForAdjustWallet(self::NOT_ENOUGH_MONEY, $playerId);
			}
			$data['status'] = GAME_LOGS::STATUS_PENDING;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET); 
			}

			$this->wallet_model->setGameProviderIsEndRound(false);

        }else if($transaction_type == self::METHOD_ROUND_WIN){
			$amount = $data['win'];
			$existingTrans  = $this->redgenn_seamless_transactions->isTransactionExist($tableName, $data);
			if(!empty($existingTrans)){
				$this->utils->debug_log('REDGENN-'. self::METHOD_ROUND_WIN, 'transaction already exist');
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId, $existingTrans->before_balance, $existingTrans->after_balance);
			}

			#get bet transaction
			$where = [
				'round_id' => $data['round_id'],
				'trans_type' => 'roundbet'
			];
			$bet_transaction_details = $this->redgenn_seamless_transactions->getTransaction($tableName, $where);
			#check if bet transaction existing 
			if(empty($bet_transaction_details)){
				$this->utils->debug_log('REDGENN-'. self::METHOD_ROUND_WIN, 'bet transaction not found');
				return $this->resultForAdjustWallet(self::TRANSACTION_NOT_FOUND, $playerId);
			}

			#check if transaction already rollback
			if($bet_transaction_details->status == GAME_LOGS::STATUS_REFUND){
				$this->utils->debug_log('REDGENN-'. self::METHOD_ROUND_WIN, 'transaction already rollback', $data, $bet_transaction_details);
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}

			$settle_bet = $this->redgenn_seamless_transactions->flagBetTransactionSettled($tableName, $data);
			if(!$settle_bet){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId); 
			}

			$data['bet_amount'] 	= $bet_transaction_details->bet;
			$data['win_amount'] 	= $data['win'];
			$data['status'] 		= GAME_LOGS::STATUS_SETTLED;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT); 
			}
			
			#implement isEndRound
			$this->wallet_model->setGameProviderIsEndRound(true);

		}else if($transaction_type == self::METHOD_REFUND){
			$existingTrans  = $this->redgenn_seamless_transactions->isTransactionExist($tableName, $data);
			if(!empty($existingTrans)){
				$this->utils->debug_log('REDGENN-'. self::METHOD_REFUND, 'transaction already exist');
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId, $existingTrans->before_balance, $existingTrans->after_balance);
			}

			$where = [
				'transaction_id' => $data['reference_id'],
				'trans_type' => 'roundbet',
			];

			$bet_transaction_details = $this->redgenn_seamless_transactions->getTransaction($tableName, $where);
			#check if bet transaction existing 
			if(empty($bet_transaction_details)){
				$this->utils->debug_log('REDGENN-'. self::METHOD_REFUND, 'bet transaction not found');
				#should return success if transaction not found, as per GP
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}

			#check if transaction already settled
			if($bet_transaction_details->status == GAME_LOGS::STATUS_SETTLED){
				$this->utils->debug_log('REDGENN-'. self::METHOD_REFUND, 'transaction already settled');
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId);
			}

			#check if transaction already rollback
			if($bet_transaction_details->status == GAME_LOGS::STATUS_REFUND){
				#if already proccessed, should return same response
				$where = [
					'round_id' => $data['round_id'],
					'trans_type' => 'refund',
				];
				$existingRefundData = $this->redgenn_seamless_transactions->getTransaction($tableName, $where);
				$existingRefundAfterBalance = null;
				if(!empty($existingRefundData)){
					$existingRefundAfterBalance = $existingRefundData->after_balance;
				}
				$this->utils->debug_log('REDGENN-'. self::METHOD_REFUND, 'transaction already rollback', $data, $existingRefundData);
				return $this->resultForAdjustWallet(self::SUCCESS, $playerId, $bet_transaction_details->before_balance, $existingRefundAfterBalance);
			}

			#get the bet amount to be refunded
			$bet_amount = $bet_transaction_details->bet;

			$amount = $data['bet'];
			if($amount != $bet_amount){
				return $this->resultForAdjustWallet(self::AMOUNT_INVALID, $playerId); 
			}

			$refund_bet = $this->redgenn_seamless_transactions->flagBetTransactionCancel($tableName, $data);
			if(!$refund_bet){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId); 
			}
			$data['status'] = GAME_LOGS::STATUS_REFUND;

			#implement action type
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND); 
			}

			#implement isEnd
			$this->wallet_model->setGameProviderIsEndRound(true);
		}

		$beforeBalance 	= $balance;
		$afterBalance 	= $balance;
		$this->utils->debug_log('REDGENN-amounts', [$beforeBalance, $afterBalance, $amount]);

		$amount_operator = '>';
		$configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
		if(!empty($configEnabled)){
			$amount_operator = '>=';
		} 

		if($this->utils->compareResultFloat($amount, $amount_operator, 0)){ 	
			#credit
			$data['balance_adjustment_method'] 	= 'credit';
			$response 							= $this->wallet_model->incSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
			if(!$response){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId);
			}
			$afterBalance 						= $beforeBalance + $amount;
			$this->utils->debug_log('REDGENN SEAMLESS', 'ADD-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);

		}else if($this->utils->compareResultFloat($amount, '<', 0)){	
			#debit
			$amount 							= abs($amount);
			$data['balance_adjustment_method'] 	= 'debit';
			$response 							= $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
			$afterBalance 						= $beforeBalance - $amount;
			$this->utils->debug_log('REDGENN SEAMLESS', 'MINUS-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);
			if(!$response){
				return $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId);
			}
		}

		$data['extra_info']				 	= json_encode($this->request);
		$data['currency']				 	= $currency;
		$data['balance_adjustment_amount'] 	= $amount;
		$data['before_balance'] 			= $beforeBalance;
        $data['after_balance'] 				= $afterBalance;
        $data['elapsed_time'] 				= intval($this->utils->getExecutionTimeToNow()*1000);
        $data['game_platform_id'] 			= $this->game_platform_id;
        $data['player_id'] 				    = $playerId;

		#unset fields that not should be save
		unset($data['reference_id']);
		
		$this->utils->debug_log('REDGENN--adjust-wallet', $data);

		$insertTransaction = $this->redgenn_seamless_transactions->insertTransaction($tableName,$data);

		return $insertTransaction ? $this->resultForAdjustWallet(self::SUCCESS, $playerId, $beforeBalance, $afterBalance)  : $this->resultForAdjustWallet(self::SYSTEM_ERROR, $playerId);
	}

	public function resultForAdjustWallet($code, $player_id = null, $before_balance = null, $after_balance = null){
		$current_balance  =  $player_id ? $this->game_api->getPlayerBalanceById($player_id) : null;
		$response =  [ 
			'success' 		  => $code === self::SUCCESS ? true : false,
			'code' 		 	  => $code,
			'current_balance' => $code === self::SUCCESS ? $current_balance : null,
			'before_balance'  => $code === self::SUCCESS ? $before_balance : null,
			'after_balance'   => $code === self::SUCCESS ? $after_balance : null,
		];
		$this->utils->debug_log("REDGENN--AdjustWalletResult" , $response);
		return $response;
	}

    public function handleExternalResponse($status, $type, $data, $response, $error_code, $fields = []){
        $this->utils->debug_log("REDGENN SEAMLESS SERVICE: (handleExternalResponse)",
            'status', $status,
            'type', $type,
            'data', $data,
            'response', $response,
            'error_code', $error_code);
        
		$httpStatusCode =  200;
		if(empty($response)){
			$response = [];
		}

        $cost = intval($this->utils->getExecutionTimeToNow()*1000);

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$this->output->set_status_header($httpStatusCode);

		$this->end_time = microtime(true);
		$execution_time = ($this->end_time - $this->start_time);
		$this->utils->debug_log("##### REDGENN SEAMLESS SERVICE TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
		
		$xml = new SimpleXMLElement('<service/>');
		
		if(!empty($response)){
			$response['@attributes'] = [
				'session' => $this->request['@attributes']['session'],
				'time' => (new DateTime())->format('Y-m-d\TH:i:s.u')
			];

			$this->array_to_xml($response, $xml);
			$xmlData =  $xml->asXML();
	
			return $this->output->set_content_type('application/xml')		
			->set_status_header(200)
			->set_output($xmlData);
		}
	}

	public function arrayToPlainXml($array, $xml = false) {
		//var_dump($array);exit;
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><RequestResponse/>');
		//$array = $array[key($array)];
		foreach ($array as $key => $value) {
			
			if (is_array($value)) {
				$this->arrayToPlainXml($value, $xml->addChild($key));
			} else {
				$xml->addChild($key, $value);
			}
		}
		return $xml->asXML();
	}

	//default external response template
	public function externalQueryResponse(){
        return ['data'=>[],'error'=>null];
	}

	
	public function getBaseUrl(){
		$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'];
		$base_url = $protocol . '://' . $host;

		return $base_url;
	}
    
	public function retrieveHeaders() {
		$this->headers = getallheaders();
	}

	public function parseRequest(){
		$request_json = file_get_contents('php://input');
		$this->utils->debug_log("REDGENN SEAMLESS SERVICE raw:", $request_json);
		$this->request = $this->_xmlToArray($request_json);
	}

	private function _xmlToArray($xml_string)
    {
        $xml = simplexml_load_string($xml_string);
        $json = json_encode($xml);
        $array = json_decode($json, true);

        return $array;
    }

	private function saveResponseResult($success, $callMethod, $params, $response, $httpStatusCode, $statusText = null, $extra = null, $fields = [], $cost = null){
		$flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
		if(is_array($response)){
			$response = json_encode($response);
		}
		if(is_array($params)){
			$params = json_encode($params);
		}
		$extra = array_merge((array)$extra,(array)$this->headers);
        return $this->CI->response_result->saveResponseResult(
        	$this->game_platform_id,
        	$flag,
        	$callMethod,
        	$params,
        	$response,
        	$httpStatusCode,
        	$statusText,
			is_array($extra)?json_encode($extra):$extra,
			$fields,
			false,
			null,
			$cost
        );
	}

	public function getPlayerByExternalCommonToken($token){
		$player = $this->external_common_tokens->getPlayerCompleteDetailsByToken($token, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		return [true, $player, $player->game_username, $player->username];
	}

	public function getPlayerByToken($token){
		$player = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		return [true, $player, $player->game_username, $player->username];
	}

	public function isParamsRequired($params, $required) {
		if (empty($required)) {
			return true;
		}
		if ($params && $required) {
			// Check if all required parameters are present in the $params array
			foreach ($required as $param) {
				if (!array_key_exists($param, $params) || $params[$param] === null || $params[$param] === '') {
					print_r($params[$param]);
					return false;
				}
			}
			return true; 
		}
		return false; 
	}
	

}///END OF FILE////////////