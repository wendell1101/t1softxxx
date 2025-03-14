<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}
 #OGP-35595

require_once dirname(__FILE__) . '/BaseController.php';

class Playstar_seamless_service_api extends BaseController {

    public  $game_platform_id,
            $start_time,
            $load,
            $host_name,
            $method, 
            $game_api, 
            $currency,
            $playerId,
            $request, 
            $headers, 
            $response_result_id,
            $end_time, $output, 
            $request_method,
			$allow_invalid_signature,
			$key,
			$raw_request,
			$api_key,
			$walletResultAdjustmentData,
			$showHints
			;

	// monthly transaction table
	protected $use_monthly_transactions_table = false;
	protected $force_check_previous_transactions_table = false;
	protected $force_check_other_transactions_table = false;
	protected $previous_table = null;
	public $tableId;
	protected $player_token;

	const CODE_SUCCESS = ["type" => 0, "description" => "Success"];
	const CODE_INVALID_TOKEN = ["type" => 1, "description" => "Invalid Token"];
	const CODE_INVALID_MEMBER_ID = ["type" => 1, "description" => "Invalid Member Id"];
	const CODE_INVALID_TRANSACTION_ID = ["type" => 2, "description" => "Invalid Transaction Id"];
	const CODE_INSUFFICIENT_FUNDS = ["type" => 3, "description" => "Insuffient funds"];
	const CODE_NO_MORE_BETS = ["type" => 4, "description" => "No more bets"];

	const CODE_SYSTEM_ERROR = ["type" => 20, "description" => "System Error"];
	const CODE_GAME_UNDER_MAINTENANCE = ["type" => 21, "description" => "Game under maintenance"];
	const CODE_IP_NOT_ALLOWED = ["type" => 22, "description" => "Ip not allowed"];
	const CODE_INVALID_REQUEST = ["type" => 23, "description" => "Invalid request"];
	const CODE_DUPLICATE_TRANSACTION = ["type" => 24, "description" => "Duplicate Transaction"];
	const CODE_INVALID_AMOUNT = ["type" => 25, "description" => "Invalid Amount"];
	const CODE_ALREADY_SETTLED = ["type" => 26, "description" => "Bet already Settled"];
	const CODE_ALREADY_CANCELLED = ["type" => 27, "description" => "Bet already Cancelled"];
	const CODE_ALREADY_REFUNDED = ["type" => 28, "description" => "Bet already Refunded"];

	const CODE_TYPES = [
		"0" => "CODE_SUCCESS",
		"1" => "CODE_INVALID_TOKEN",
		"1" => "CODE_INVALID_MEMBER_ID",
		"2" => "CODE_INVALID_TRANSACTION_ID",
		"3" => "CODE_INSUFFICIENT_FUNDS",
		"4" => "CODE_NO_MORE_BETS",
		"20" => "CODE_SYSTEM_ERROR",
		"21" => "CODE_GAME_UNDER_MAINTENANCE",
		"22" => "CODE_IP_NOT_ALLOWED",
		"23" => "CODE_INVALID_REQUEST",
		"24" => "CODE_DUPLICATE_TRANSACTION",
		"25" => "CODE_INVALID_AMOUNT",
		"26" => "CODE_ALREADY_SETTLED",
		"27" => "CODE_ALREADY_CANCELLED",
		"28" => "CODE_ALREADY_REFUNDED",
	];


	const METHOD_GETBALANCE = 'GetBalance';
	const METHOD_BET = 'Bet';
	const METHOD_RESULT = 'Result';
	const METHOD_REFUND_BET = 'Refund';
	const METHOD_AUTHENTICATE = 'Authenticate';
	const METHOD_BONUS_AWARD = 'BonusAward';

	const REQUIRED_PARAMS_MAP = [
		self::METHOD_GETBALANCE 	=> ['access_token'],
		self::METHOD_AUTHENTICATE 	=> ['access_token'],
		self::METHOD_BET 			=> ['access_token', 'txn_id', 'total_bet', 'game_id', 'subgame_id', 'ts'],
		self::METHOD_RESULT 		=> ['access_token', 'txn_id', 'total_win', 'bonus_win', 'game_id', 'subgame_id', 'ts', 'jp_contrib'],
		self::METHOD_REFUND_BET 	=> ['access_token', 'txn_id', 'total_win', 'game_id'],
		self::METHOD_BONUS_AWARD 	=> ['access_token', 'bonus_id', 'bonus_reward', 'bonus_type', 'game_id', 'subgame_id', 'txn_id'],
	];
    
	public function __construct() {
		parent::__construct();
		$this->load->model(array('wallet_model','game_provider_auth','common_token','game_logs','original_seamless_wallet_transactions', 'player'));
		$this->parseRequest();
		$this->retrieveHeaders();
		$this->start_time 	= microtime(true);
		$this->host_name 	= $_SERVER['HTTP_HOST'];
		$this->method 		= $_SERVER['REQUEST_METHOD'];
	}

	public function index( $platformCode,  $method){

		$this->game_platform_id = $platformCode;
		$this->request_method = $method;

		$this->utils->debug_log('PLAYSTAR_SEAMLESS_API--method : '. $this->request_method);
		return $this->selectMethod();			
	}

	public function selectMethod(){
		switch ($this->request_method) {
			case 'getbalance':
				$this->GetBalance();
                break;
			case 'authenticate':
				$this->Authenticate();
                break;
            case 'bet':
                $this->Bet();
                break;
			case 'result':
				$this->Result();
				break;
			case 'refund':
				$this->Refund();
				break;
			case 'bonusaward':
				$this->BonusAward();
				break;
			default:
				$this->utils->debug_log('PLAYSTAR_SEAMLESS: Invalid API Method');
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
		$this->game_api 		   =  $this->utils->loadExternalSystemLibObject($this->game_platform_id);
		$this->game_platform_id    =  $this->game_api->getPlatformCode();
		$this->use_monthly_transactions_table    =  $this->game_api->use_monthly_transactions_table;

        if(!$this->game_api){
			$this->utils->debug_log("PLAYSTAR_SEAMLESS: (initialize) ERROR lOAD: ", $this->game_platform_id);
			return false;
        }

        $this->currency                     = $this->game_api->getCurrency();
		$this->showHints 					= $this->game_api->showHints;
			
		$this->CI->load->model(array('player_model'));
		return true;
	}

	public function Authenticate(){
		return $this->processTransaction('Authenticate', function($player_id, $params) {
			return [
                'balance' => $this->game_api->getPlayerBalanceById($player_id),
			];
        });
	}

    public function GetBalance(){
        return $this->processTransaction('GetBalance', function($player_id) {
            return [
                'balance' => $this->game_api->getPlayerBalanceById($player_id)
            ];
        });
    }

    public function Bet(){
        return $this->processTransaction('Bet', function($player_id, $params) {
            return $this->adjustWalletAndReturnBalance('Bet', $player_id, $params);
        });
    }

    public function Result(){
        return $this->processTransaction('Result', function($player_id, $params) {
            return $this->adjustWalletAndReturnBalance('Result', $player_id, $params);
        });
    }

    public function Refund(){
        return $this->processTransaction('Refund', function($player_id, $params) {
            return $this->adjustWalletAndReturnBalance('Refund', $player_id, $params);
        });
    }
    public function BonusAward(){
        return $this->processTransaction('BonusAward', function($player_id, $params) {
            return $this->adjustWalletAndReturnBalance('BonusAward', $player_id, $params);
        });
    }

    private function processTransaction($method, $callback) {
        $this->utils->debug_log("PLAYSTAR_SEAMLESS: ($method)", ['$this->request' => $this->request]);

        $callType = constant("self::METHOD_" . strtoupper($method));
        $statusErrorCode = self::CODE_SUCCESS['type'];
        $externalResponse = [];
        $success = true;
        $player_id = null;
        $requiredParams = self::REQUIRED_PARAMS_MAP[$method];

        try {
            $this->initializeTransaction($method, $requiredParams);

			list($getPlayerStatus, $player, $player_username) = $this->getPlayerInfoById($this->request['access_token']);

			
            if (!$getPlayerStatus) {
                throw new Exception(self::CODE_INVALID_MEMBER_ID['type']); 
            }

            $player_id = $player['playerId'];
            $params = $this->buildParams($method, $player);

            $result = $callback($player_id, $params);

			if($method == self::METHOD_AUTHENTICATE){
				$externalResponse = array_merge($result, [
					"status_code" => $statusErrorCode,
					'member_id' 	=> $player['game_username'],
					'member_name' 	=> $player['username'],
					'type'			=> 0 #default
				]);
			}else if($method == self::METHOD_GETBALANCE){
				$externalResponse = array_merge($result, [
					"status_code" => $statusErrorCode,
				]);
			}else if($method == self::METHOD_BET){
				$externalResponse = array_merge($result, [
					"status_code" => $statusErrorCode,
					'balance' => $this->game_api->getPlayerBalanceById($player_id)
				]);
			}else if($method == self::METHOD_RESULT){
				$externalResponse = array_merge($result, [
					"status_code" => $statusErrorCode,
					'balance' => $this->game_api->getPlayerBalanceById($player_id)
				]);
			}else if($method == self::METHOD_REFUND_BET){
				$externalResponse = array_merge($result, [
					"status_code" => $statusErrorCode,
					'balance' => $this->game_api->getPlayerBalanceById($player_id)
				]);
			}else if($method == self::METHOD_BONUS_AWARD){
				$externalResponse = array_merge($result, [
					"status_code" => $statusErrorCode,
					'balance' => $this->game_api->getPlayerBalanceById($player_id)
				]);
			}

        } catch (Exception $error) {
		$this->utils->debug_log("PLAYSTAR_SEAMLESS: (processTransaction) ERROR", $error);

            $statusErrorCode = $error->getMessage();
            $success = false;
			unset($externalResponse);
			$externalResponse = [
				"status_code" => $statusErrorCode
			];

			if (( !is_array($statusErrorCode) || !is_object($statusErrorCode) ) && $this->showHints) {
				$externalResponse['error_hints'] = $this->formatError($statusErrorCode);
			}
        }

		
 
        $fields = ['player_id' => $player_id];
        return $this->handleExternalResponse($success, $callType, $this->request, $externalResponse, $statusErrorCode, $fields);
    }

	private function initializeTransaction($method, $requiredParams) {
		$this->utils->debug_log("PLAYSTAR_SEAMLESS: (initializeTransaction) START");
	
		try {
			if (!$this->initialize()) {
				throw new Exception(self::CODE_SYSTEM_ERROR['type']['type']);
			}
	
			if (!$this->isGameUnderMaintenance()) {
				throw new Exception(self::CODE_GAME_UNDER_MAINTENANCE['type']);
			}
	
			if (!$this->game_api->validateWhiteIP()) {
				throw new Exception(self::CODE_IP_NOT_ALLOWED['type']);
			}
	
			if (!$this->isValidRequest($this->request, $requiredParams)) {
				throw new Exception(self::CODE_INVALID_REQUEST['type']);
			}
		} catch (Exception $e) {
			$this->utils->debug_log("PLAYSTAR_SEAMLESS: (initializeTransaction) ERROR: " . $e->getMessage());
			throw $e;
		}
	
		$this->utils->debug_log("PLAYSTAR_SEAMLESS: (initializeTransaction) END");
	}
	
	private function formatError($error) {
		$this->utils->debug_log("PLAYSTAR_SEAMLESS: (formatError) ", [
			"error" => $error
		]);
		return  constant("self::" . strtoupper( self::CODE_TYPES[$error] )) ;
	}
	

	private function buildParams($method, $player = []) {
		return [
			'access_token'			=> isset($this->request['access_token']) ? $this->request['access_token'] : null,
			'txn_id'				=> isset($this->request['txn_id']) ? $this->request['txn_id'] : null,
			'member_id'				=> isset($this->request['member_id']) ? $this->request['member_id'] : null,
			'member_name'				=> isset($player['username']) ? $player['username'] : null,
			'total_amount'			=> isset($this->request['total_amount']) ? $this->request['total_amount'] : null,
			'total_bet'				=> isset($this->request['total_bet']) ? $this->request['total_bet'] : null,
			'total_win'				=> isset($this->request['total_win']) ? $this->request['total_win'] : null,
			'bonus_win'				=> isset($this->request['bonus_win']) ? $this->request['bonus_win'] : null,
			'bonus_reward'			=> isset($this->request['bonus_reward']) ? $this->request['bonus_reward'] : null,
			'bonus_type'			=> isset($this->request['bonus_type']) ? $this->request['bonus_type'] : null,
			// 'step' 					=> isset($this->request['step']) ? $this->request['step'] : null,
			'round_id' 				=> isset($this->request['txn_id']) ? $this->request['txn_id'] : null,
			'game_id' 				=> isset($this->request['game_id']) ? $this->request['game_id'] : null,
			'subgame_id' 			=> isset($this->request['subgame_id']) ? $this->request['subgame_id'] : null,
			'jp_contrib' 			=> isset($this->request['jp_contrib']) ? $this->request['jp_contrib'] : null,
			'timestamp'				=> isset($this->request['ts']) ? $this->request['ts'] : null,
			'game_type' 			=> isset($this->request['game_id']) ? $this->request['game_id'] : null,
			'transaction_id' 		=> isset($this->request['txn_id']) ? $this->request['txn_id'] : null,
			'ts' 					=> isset($this->request['ts']) ? $this->request['ts'] : null,
			'currency' 				=> isset($this->currency) ? $this->currency : null,
			'trans_type' 			=> constant("self::METHOD_" . strtoupper($method)),
			'external_uniqueid' 	=> isset($this->request['txn_id']) ? $method . '-' . $this->request['txn_id'] : null
		];
	}
	

    private function adjustWalletAndReturnBalance($method, $player_id, $params) {
        $trans_success = $this->lockAndTransForPlayerBalance($player_id, function() use($method, $player_id, $params, &$errorCode, &$balance) {
            $adjustWalletResponse = $this->adjustWallet($method, $player_id, $params);
            $errorCode = $adjustWalletResponse;
            $balance = $adjustWalletResponse['current_balance'];

            if (!$adjustWalletResponse['success']) {
                return false;
            }

            return true;
        });


        if (!$trans_success) {
			$this->currentBalanceOnError = $balance;
			$this->utils->debug_log("PLAYSTAR_SEAMLESS: (adjustWalletAndReturnBalance) ERrOR", $errorCode);

			if(isset($errorCode['code'])){
				throw new Exception($errorCode['code']);
			}

            throw new Exception($errorCode);
        }
		$this->utils->debug_log("PLAYSTAR_SEAMLESS: (adjustWalletAndReturnBalance) finish", [
			'balance' => $balance,
            'params' => $params,
            'timeStamp' => $this->getTimestamp(),
		]);

        return [
            'status_code' => self::CODE_SUCCESS['type'],
			'balance' => $balance,
        ];
    }
        

	public function adjustWallet($transaction_type,$player_id, &$data){

		$related_inique_id 				= null;
		$related_action 				= null;
		$playerId	 					= $player_id;
		$balance						= $this->game_api->gameAmountToDBTruncateNumber($this->game_api->getPlayerBalanceById($playerId));
		$currency						= $this->game_api->currency;
		$tableName 						= $this->game_api->getTransactionsTable();
		$game_code 						= isset($this->request['gameType']) ? $this->request['gameType'] : null;
		$uniqueid_of_seamless_service 	= $this->game_platform_id. '-' .$data['external_uniqueid']  ;

		#implement unique id
		if (method_exists($this->wallet_model, 'setUniqueidOfSeamlessService')) {
			$this->wallet_model->setUniqueidOfSeamlessService( $uniqueid_of_seamless_service ,$game_code );
		}


		if($transaction_type == self::METHOD_BET){
			$amount = $data['total_bet'] * -1; 								#getting the negative betting value
			$data['total_bet'] = $data['total_bet'];

			$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($tableName, [ 'external_uniqueid' => $data['external_uniqueid']]);

			if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
				if( empty($existingTrans) ){
					$existingTrans = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->previous_table, [ 'external_uniqueid' => $data['external_uniqueid']]);
				}
			}
			
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::CODE_DUPLICATE_TRANSACTION['type'], $playerId, $balance, $balance);
			}

			
			if($data['total_bet'] < 0){
				return $this->resultForAdjustWallet(self::CODE_INVALID_AMOUNT['type'], $playerId,  $balance, $balance);
			}


			// if($balance < $data['total_bet']){
			// 	return $this->resultForAdjustWallet(self::CODE_INSUFFICIENT_FUNDS['type'], $playerId,  $balance, $balance);
			// }


			$data['status'] = GAME_LOGS::STATUS_PENDING;
		
			if(method_exists($this->wallet_model, 'setGameProviderActionType')){
				$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET); 
			}

			$this->wallet_model->setGameProviderIsEndRound(false);


        }else if($transaction_type == self::METHOD_RESULT || $transaction_type == self::METHOD_REFUND_BET){
			$amount = $data['total_win'];

			$bet_transaction_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($tableName, [ 'round_id' => $data['round_id'], 'trans_type' => self::METHOD_BET]);
			#check if bet transaction existing 

			if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
				if( empty($bet_transaction_details) ){
					$bet_transaction_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->previous_table, [ 'round_id' => $data['round_id'],  'trans_type' => self::METHOD_BET]);
				}
			}
			if(empty($bet_transaction_details)){
				return $this->resultForAdjustWallet(self::CODE_INVALID_TRANSACTION_ID['type'], $playerId,  $balance, $balance);
			}
		

			if($bet_transaction_details['status'] == GAME_LOGS::STATUS_SETTLED){
				return $this->resultForAdjustWallet(self::CODE_ALREADY_SETTLED['type'], $playerId,  $balance, $balance);
			}

			$existingTrans  = $this->original_seamless_wallet_transactions->isTransactionExistCustom($tableName, ['external_uniqueid' => $data['external_uniqueid']]);

			if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
				if( empty($existingTrans) ){
					$existingTrans = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->previous_table, ['external_uniqueid' => $data['external_uniqueid']]);
				}
			}
			
			if(!empty($existingTrans)){
				return $this->resultForAdjustWallet(self::CODE_DUPLICATE_TRANSACTION['type'], $playerId, $balance, $balance);
			}

			#check if transaction already settled
			if( $transaction_type == self::METHOD_RESULT ){
				if($bet_transaction_details['status'] == GAME_LOGS::STATUS_CANCELLED){
					return $this->resultForAdjustWallet(self::CODE_ALREADY_CANCELLED['type'], $playerId,  $balance, $balance);
				}

				if($bet_transaction_details['status'] == GAME_LOGS::STATUS_REFUND){
					return $this->resultForAdjustWallet(self::CODE_ALREADY_REFUNDED['type'], $playerId,  $balance, $balance);
				}

				$flag_where_update = [
					'round_id' 			=> $data['round_id'],
					'game_platform_id' 	=> $this->game_platform_id,
					'trans_type' 		=> self::METHOD_BET
				];
	
				$flag_set_update = [
					'status' 		=> Game_logs::STATUS_SETTLED,
					'total_win' 	=> $data['total_win'],
				];
	
				$settle_bet = $this->original_seamless_wallet_transactions->updateTransactionDataWithoutResultCustom($tableName, $flag_where_update, $flag_set_update);
				if(!$settle_bet){
					return $this->resultForAdjustWallet(self::CODE_SYSTEM_ERROR['type'], $playerId,  $balance, $balance); 
				}
	
				$data['total_bet'] 	= $bet_transaction_details['total_bet'];
				$data['total_win'] 	= $data['total_win'];
				$data['status'] 	= GAME_LOGS::STATUS_SETTLED;

				
				$related_inique_id 	= !empty($bet_transaction_details['external_uniqueid']) ? $this->implodeWithDash( ['game', $bet_transaction_details['game_platform_id'], $bet_transaction_details['external_uniqueid']] ) : null;
				$related_action 	= !empty($bet_transaction_details['balance_adjustment_method']) ? Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET : null;
	
				#implement action type
				if(method_exists($this->wallet_model, 'setGameProviderActionType')){
					$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT); 
				}

				#implement related unique id type
				if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
					$this->wallet_model->setRelatedUniqueidOfSeamlessService($related_inique_id);
				}
				#implement related action type
				if(method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')){
					$this->wallet_model->setRelatedActionOfSeamlessService($related_action); 
				}

			}else if( $transaction_type == self::METHOD_REFUND_BET ){
				#get bet transaction

				$bet_transaction_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($tableName, ['transaction_id' => $data['transaction_id'], 'round_id' => $data['round_id'], 'trans_type' => self::METHOD_BET]);
				#check if bet transaction existing 

				if ($this->use_monthly_transactions_table && $this->game_api->ymt_check_previous_year_month_data($this->force_check_previous_transactions_table)) {
					if( empty($bet_transaction_details) ){
						$bet_transaction_details = $this->original_seamless_wallet_transactions->querySingleTransactionCustom($this->previous_table, ['transaction_id' => $data['transaction_id'], 'round_id' => $data['round_id'],  'trans_type' => self::METHOD_BET]);
					}
				}

				
				if(empty($bet_transaction_details)){
					return $this->resultForAdjustWallet(self::CODE_INVALID_TRANSACTION_ID['type'], $playerId,  $balance, $balance);
				}
			

				if($bet_transaction_details['status'] == GAME_LOGS::STATUS_SETTLED){
					return $this->resultForAdjustWallet(self::CODE_ALREADY_SETTLED['type'], $playerId,  $balance, $balance);
				}

				$flag_where_update = [
					'transaction_id' 	=> $data['transaction_id'],
					'round_id' 			=> $data['round_id'],
					'game_platform_id' 	=> $this->game_platform_id,
					'trans_type' 		=> self::METHOD_BET
				];
	
				$flag_set_update = [
					'status' 		=> Game_logs::STATUS_REFUND,
					'total_win' 	=> $data['total_bet'],
				];
	
				$settle_bet = $this->original_seamless_wallet_transactions->updateTransactionDataWithoutResultCustom($tableName, $flag_where_update, $flag_set_update);
				// dd($settle_bet);
				if(!$settle_bet){
					return $this->resultForAdjustWallet(self::CODE_SYSTEM_ERROR['type'], $playerId,  $balance, $balance); 
				}
	
				$data['total_bet'] 	= $bet_transaction_details['total_bet'];
				$data['total_win'] 	= $data['total_win'];
				$data['status'] 		= GAME_LOGS::STATUS_REFUND;

				$related_inique_id 	= !empty($bet_transaction_details['external_uniqueid']) ? $this->implodeWithDash( ['game', $bet_transaction_details['game_platform_id'], $bet_transaction_details['external_uniqueid']] ) : null;
				$related_action 	= !empty($bet_transaction_details['balance_adjustment_method']) ? Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET : null;

				#implement action type
				if(method_exists($this->wallet_model, 'setGameProviderActionType')){
					$this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND); 
				}

				#implement related unique id type
				if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
					$this->wallet_model->setRelatedUniqueidOfSeamlessService($related_inique_id);
				}
				#implement related action type
				if(method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')){
					$this->wallet_model->setRelatedActionOfSeamlessService($related_action); 
				}
			}

			#implement isEndRound
			$this->wallet_model->setGameProviderIsEndRound(true);

		}else{
			return $this->resultForAdjustWallet(self::CODE_INVALID_REQUEST['type'], $playerId,  $balance, $balance);
		}

		$beforeBalance 	= $balance;
		$afterBalance 	= $balance;



		$this->utils->debug_log('PLAYSTAR_SEAMLESS_API-amounts', [$beforeBalance, $afterBalance, $amount]);


		$amount_operator = '>';
		$configEnabled = $this->utils->getConfig('enabled_remote_wallet_client_on_currency');
		if(!empty($configEnabled)){
			$amount_operator = '>=';
		} 
		if($this->utils->compareResultFloat($amount, $amount_operator, 0)){ 

			#result
			$data['balance_adjustment_method'] 	= 'credit';
			$response 							= $this->wallet_model->incSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);
			if(!$response){
				return $this->resultForAdjustWallet(self::CODE_SYSTEM_ERROR['type'], $playerId,  $balance, $balance);
			}
			$afterBalance 						= $beforeBalance + $amount;
			$this->utils->debug_log('PLAYSTAR_SEAMLESS_API', 'ADD-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);

		}else if($this->utils->compareResultFloat($amount, '<', 0)){	

			#bet
			$amount 							= abs($amount);
			$data['balance_adjustment_method'] 	= 'debit';
			$response 							= $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount, $afterBalance);

			$afterBalance 						= $beforeBalance - $amount;
			$this->utils->debug_log('PLAYSTAR_SEAMLESS_API', 'MINUS-AMOUNT: ', $response, 'AFTER_BALANCE',$afterBalance);
			if(!$response){
				return $this->resultForAdjustWallet(self::CODE_SYSTEM_ERROR['type'], $playerId,  $balance, $balance);
			}

		}

		$data['transaction_date']		  		= isset($data['timestamp']) ? date("Y-m-d H:i:s", $data['timestamp'] / 1000) : date("Y-m-d H:i:s");
		$data['extra_info']				 		= json_encode($this->request);
		$data['balance_adjustment_amount'] 		= $amount;
		$data['before_balance'] 				= $beforeBalance;
        $data['after_balance'] 					= $afterBalance;
        $data['elapsed_time'] 					= intval($this->utils->getExecutionTimeToNow()*1000);
        $data['game_platform_id'] 				= $this->game_platform_id;
        $data['member_id'] 				    	= $playerId;
        $data['created_at'] 					= date("Y-m-d H:i:s");
        $data['updated_at'] 					= date("Y-m-d H:i:s");
		
		$this->utils->debug_log('PLAYSTAR_SEAMLESS_API--adjust-wallet', $data);
		$this->walletResultAdjustmentData = $data;

		$insertTransaction = $this->original_seamless_wallet_transactions->insertTransactionData($tableName,$data);

		return $insertTransaction ? $this->resultForAdjustWallet(self::CODE_SUCCESS['type'], $playerId, $beforeBalance, $afterBalance)  : $this->resultForAdjustWallet(self::CODE_SYSTEM_ERROR['type'], $playerId,  $balance, $balance);
	}

	

	public function resultForAdjustWallet($code, $player_id = null, $before_balance = null, $after_balance = null){
		$current_balance 	=  $player_id ? $this->game_api->getPlayerBalanceById($player_id) : null;
		$response =  [ 
				'success' 		  => $code === self::CODE_SUCCESS['type'] ? true : false,
				'code' 		 	  => $code,
				'current_balance' => $current_balance,
				'before_balance'  => $code === self::CODE_SUCCESS['type'] ? $before_balance : null,
				'after_balance'   => $code === self::CODE_SUCCESS['type'] ? $after_balance : null,
			];
		$this->utils->debug_log("PLAYSTAR_SEAMLESS_API--AdjustWalletResult" , $response);
		return $response;
	}

	

    public function handleExternalResponse($status, $type, $data, $response, $error, $fields = []){
		
        
		$httpStatusCode =  200;

		if(empty($error)){

		}

		if(empty($response)){
			$response = [];
		}


        $cost = intval($this->utils->getExecutionTimeToNow()*1000);

		$this->response_result_id = $this->saveResponseResult($status, $type, $data, $response, $httpStatusCode, null, null, $fields, $cost);

		$this->output->set_status_header($httpStatusCode);

		$this->end_time = microtime(true);
		$execution_time = ($this->end_time - $this->start_time);
		$this->utils->debug_log("##### PLAYSTAR_SEAMLESS TOTAL EXECUTION TIME : ". $execution_time, 'response', $response);
		$output = $this->output->set_content_type('application/json')
        ->set_output(json_encode($response));
	
		return $output;
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
		$this->utils->debug_log("PLAYSTAR_SEAMLESS (retrieveHeaders):", [
			'$this->headers' => $this->headers,
		]);
	}

	public function parseRequest(){
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			$request_json = file_get_contents('php://input');
			$this->raw_request = $request_json;
			$this->utils->debug_log("PLAYSTAR_SEAMLESS (parseRequest):", $request_json);
	
			$this->request = json_decode($request_json, true);
	
			if (!$this->request){
				parse_str($request_json, $request_json);
				$this->utils->debug_log("PLAYSTAR_SEAMLESS (parseRequest) parsed:", $request_json);
				$this->request = $request_json;
			}
		}else{
			$this->request = $this->input->get();
		}
		return $this->request;
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

	public function getPlayerInfoById($access_token){
		$player = (array) $this->common_token->getPlayerCompleteDetailsByToken(
			$access_token,
			$this->game_platform_id
		);
		if(empty($player)){
			return [false, null, null];
		}
		$player['playerId'] = $player['player_id'];
		$this->utils->debug_log("PLAYSTAR_SEAMLESS (getPlayerInfoById):", [
			'$player' => $player,
		]);
		return [true, $player, $player['username']];
	}

	public function getPlayerByToken($token){
		$player = $this->common_token->getPlayerCompleteDetailsByToken($token, $this->game_platform_id);

		if(!$player){
			return [false, null, null, null];
		}
		return [true, $player, $player->game_username, $player->username];
	}

	public function isValidRequest($params, $required) {
		if(empty($required)){
			return true;
		}
		if($params && $required){
			# Check if all required parameters are present in the $params array
			foreach ($required as $param) {
				if (!array_key_exists($param, $params)) {
					return false; 
				}
			}
			return true; 
		}
	}

	private function getTimestamp(){
		$milliseconds = round(microtime(true) * 1000);
		return $milliseconds;

	}

	public function isGameUnderMaintenance()
	{
		return !$this->utils->setNotActiveOrMaintenance($this->game_platform_id);
	}

	private function implodeWithDash($array) {
		return implode("-", $array);
	}

}///END OF FILE////////////