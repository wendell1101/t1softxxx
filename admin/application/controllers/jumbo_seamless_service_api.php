<?php

use function PHPSTORM_META\map;

 if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Jumbo_seamless_service_api extends BaseController {
    use Seamless_service_api_module;

    const GET_BALANCE = 6;
    const BET_N_SETTLE = 8;
    const CANCEL_BET_N_SETTLE = 4;
    const BET = 9;
    const SETTLED = 10;
    const CANCEL = 11;
    const GENERATE_COMMON_TOKEN = "TestToken"; #added for automated testing purposes. 

    const GTYPE_SLOT_GAMES = 0;
    const GTYPE_FISHING_GAMES = 7;
    const GTYPE_ARCADE_GAMES = 9;
    const GTYPE_LOTTERY_GAMES = 12;

    const RETURN_OK = 200;

    const SUCCEED = [
        'status' => '0000',
        'err_text' => 'Succeed'
    ];

    const ERROR_CODE_PLAYER_BALANCE_ZERO = [
        'status' => '6002',
        'err_text' => 'Player balance is zero.'
    ];

    const ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT = [
        'status' => '6006',
        'err_text' => 'Player balance is insufficient.'
    ];

    const ERROR_CODE_CAN_NOT_CANCEL = [
        'status' => '6101',
        'err_text' => 'Can not cancel.'
    ];

    const ERROR_CODE_USER_ID_NOT_FOUND = [
        'status' => '7501',
        'err_text' => 'User ID cannot be found.'
    ];

    const ERROR_CODE_PARAMETER_ERROR = [
        'status' => '8000',
        'err_text' => 'The parameter of input error, please check your parameter is correct or not.'
    ];

    const ERROR_CODE_IP_NOT_AUTHORIZED = [
        'status' => '9001',
        'err_text' => 'No authorized to access.'
    ];

    const ERROR_CODE_TIMEOUT = [
        "status" => '9009',
        "err_text" => "Time out."
    ];

    const ERROR_CODE_TRANSFER_ID_REPEATED = [
        'status' => '9011',
        'err_text' => 'Transfer ID repeated.'
    ];

    const ERROR_CODE_UNDER_MAINTENANCE = [
        'status' => '9013',
        'err_text' => 'System is maintained.'
    ];

    const ERROR_CODE_DATA_NOT_EXIST = [
        'status' => '9015',
        'err_text' => 'Data does not exist.'
    ];
    const ERROR_CODE_WORK_IN_PROCESS = [
        'status' => '9017',
        'err_text' => 'Work in process, please try again later.'
    ];

    const ERROR_CODE_FAILED = [
        'status' => '9999',
        'err_text' => 'Failed.'
    ];

    const ERROR_CODE_BAD_REQUEST = [
        'status' => '9999',
        'err_text' => 'Bad request.'
    ];

    const HTTP_STATUS_CODE_MAP = [
        self::SUCCEED['status']=>200,
        self::ERROR_CODE_IP_NOT_AUTHORIZED['status']=>401,
        self::ERROR_CODE_PARAMETER_ERROR['status']=>400,
        self::ERROR_CODE_PLAYER_BALANCE_ZERO['status']=>200,
        self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT['status']=>200,
        self::ERROR_CODE_TRANSFER_ID_REPEATED['status']=>200,
        self::ERROR_CODE_FAILED['status']=>200
    ];

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_CANCELLED = 'cancelled';
    const TRANSACTION_REFUND = 'refunded';

    const STATUS_OPEN = 'open';
    const STATUS_SETTLED = 'settled';


    private $requestHeaders;
    private $requestParams;
    private $api;

    private $dc;
    private $iv;
    private $key;
    private $currency;

    private $currentPlayer = [];
    private $wallet_transaction_id = null;
    private $resultCode;
    private $seamless_service_related_unique_id = null;
    private $seamless_service_related_action = null;
    private $remote_wallet_status = null;
    private $use_remote_wallet_failed_transaction_monthly_table = false;
    private $wallet_transaction = [];

    public function __construct() {
        parent::__construct();
    }

    public function index($api, $method) {
        $this->api = $this->utils->loadExternalSystemLibObject($api);

        $this->dc = $this->api->dc;
        $this->iv = $this->api->iv;
        $this->key = $this->api->key;
        $this->currency = $this->api->currency;
        $this->use_remote_wallet_failed_transaction_monthly_table = $this->api->use_remote_wallet_failed_transaction_monthly_table;

        $this->retrieveHeaders();

        $this->CI->load->model('jumbo_seamless_wallet_transactions', 'jumbo_transactions','wallet_model');
        $this->jumbo_transactions->tableName = $this->api->original_transaction_table_name;


        $this->requestParams = new stdClass();
        $this->checkPreviousMonth = false;
        if(date('j', $this->utils->getTimestampNow()) <= $this->api->getSystemInfo('allowed_day_to_check_monthly_table', '30')) {
            $this->checkPreviousMonth = true;
        }

        return $this->$method();
    }

    public function retrieveHeaders() {
        $this->requestHeaders = getallheaders();
    }

    public function generateCommonToken($arr){
        $this->requestParams->function = __function__;
        if(isset($arr->currency)){
            $check_currency = $this->checkCurrency($arr->currency);
            if(!$check_currency){
                $this->utils->debug_log('JUMBO_SEAMLESS ' . __METHOD__ , ' currency not allowed');
                return $this->setOutput(self::ERROR_CODE_PARAMETER_ERROR);
            }
        }

        $player = $this->getPlayer($arr->uid);

        if(!$player){
            $this->utils->debug_log('JUMBO_SEAMLESS ' . __METHOD__ , ' player not found');
            return $this->setOutput(self::ERROR_CODE_USER_ID_NOT_FOUND);
        }else{

    		$new_token = $this->common_token->createTokenBy($player['player_id'], 'player_id');
            $player_token = $this->common_token->getValidPlayerToken($player['player_id']);
            if(empty($player_token)){
                return $this->setOutput(self::ERROR_CODE_TIMEOUT);
            }

            $balance = $this->api->queryPlayerBalance($this->currentPlayer['username']);

                $data = [
                    "status" => self::SUCCEED['status'],
                    "balance" => $balance['balance'],
                    "err_text" => self::SUCCEED['err_text']
                ];

                return $this->setOutput($data);
        }

    }

    public function transact(){
        $this->CI->load->model(['common_token', 'original_seamless_wallet_transactions']);

        $rule_set = [
            "x" => "required"
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->api) {
            return $this->setOutput(self::ERROR_CODE_UNDER_MAINTENANCE);
        }

        if($this->api->isMaintenance() || $this->api->isDisabled()) {
            return $this->setOutput(self::ERROR_CODE_UNDER_MAINTENANCE);
        }

        if(!$this->api->validateWhiteIP()){
            return $this->setOutput(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        $this->utils->debug_log('TAL_JUMBO ' . __METHOD__ , $this->requestParams->params);

        $jsonString = $this->api->decrypt($this->requestParams->params['x'], $this->key, $this->iv);
        $arr = json_decode($jsonString);

        $this->utils->debug_log('TAL_JUMBO ' . __METHOD__ , 'arr', $arr);
        $this->requestParams->params['decrypt_request'] = $arr;

        if(isset($arr->action)){
            if($arr->action == self::GET_BALANCE){
                $this->getBalance($arr);
            }else if($arr->action == self::BET_N_SETTLE){
                $this->betNSettle($arr);
            }else if($arr->action == self::CANCEL_BET_N_SETTLE){
                $this->cancelBetNSettle($arr);
            }else if($arr->action == self::BET){
                $this->bet($arr);
            }else if($arr->action == self::SETTLED){
                $this->settle($arr);
            }else if($arr->action == self::CANCEL){
                $this->cancel($arr);
            }else if($arr->action == self::GENERATE_COMMON_TOKEN){
                $this->generateCommonToken($arr);
            }else{
                $this->utils->debug_log('JUMBO_SEAMLESS ' . __METHOD__ , ' Wrong value of Action');
                return $this->setOutput(self::ERROR_CODE_PARAMETER_ERROR);
            }
        }else{
            $this->utils->debug_log('JUMBO_SEAMLESS ' . __METHOD__ , ' Wrong value of Action');
            return $this->setOutput(self::ERROR_CODE_PARAMETER_ERROR);
        }
    }
    /**
     * Return player’s remaining account balance.
     * When the player logs into the game, it will call once every five seconds until the player leaves the game.
     * When the player logs into the game lobby, it will call once every three seconds until the player leaves the game.
     */
    public function getBalance($arr) {
        $this->requestParams->function = __function__;
        if(isset($arr->currency)){
            $check_currency = $this->checkCurrency($arr->currency);
            if(!$check_currency){
                $this->utils->debug_log('JUMBO_SEAMLESS ' . __METHOD__ , ' currency not allowed');
                return $this->setOutput(self::ERROR_CODE_PARAMETER_ERROR);
            }
        }

        $player = $this->getPlayer($arr->uid);

        if(!$player){
            $this->utils->debug_log('JUMBO_SEAMLESS ' . __METHOD__ , ' player not found');
            return $this->setOutput(self::ERROR_CODE_USER_ID_NOT_FOUND);
        }else{

            $player_token = $this->common_token->getValidPlayerToken($player['player_id']);
            if(empty($player_token)){
                $this->utils->debug_log('JUMBO_SEAMLESS ' . __METHOD__ , ' player not login');
                return $this->setOutput(self::ERROR_CODE_TIMEOUT);
            }

            $balance = $this->api->queryPlayerBalance($this->currentPlayer['username']);

                $data = [
                    "status" => self::SUCCEED['status'],
                    "balance" => $balance['balance'],
                    "err_text" => self::SUCCEED['err_text']
                ];

                return $this->setOutput($data);
        }

    }

    /**
    * mb is the abbreviation for minimum balance.
    * The mb of non-fish game means bet.
    */
    public function betNSettle($arr){
        $this->requestParams->function = __function__;
        if(isset($arr->currency)){
            $check_currency = $this->checkCurrency($arr->currency);
            if(!$check_currency){
                $data = [
                    "status" => self::ERROR_CODE_PARAMETER_ERROR['status'],
                    "balance" => 0.00,
                    "err_text" => self::ERROR_CODE_PARAMETER_ERROR['err_text']
                ];
                return $this->setOutput($data);
            }
        }

        $player = $this->getPlayer($arr->uid);
        $this->utils->debug_log("JUMBO @betNSettle getPlayer last query: " , $this->CI->db->last_query());
        $this->utils->debug_log("JUMBO @betNSettle getPlayer result: ", $player);

        if(!$player){
            $data = [
                "status" => self::ERROR_CODE_USER_ID_NOT_FOUND['status'],
                "err_text" => self::ERROR_CODE_USER_ID_NOT_FOUND['err_text']
            ];

            return $this->setOutput($data);
        }else{
            $allow_timeout_token = isset($this->api->allow_timeout_token) ? $this->api->allow_timeout_token : false; 
            $player_token = $this->common_token->getValidPlayerToken($player['player_id']);
            if(empty($player_token) && !$allow_timeout_token){
                return $this->setOutput(self::ERROR_CODE_TIMEOUT);
            }

            $controller = $this;


            //Lock Balance
            $transaction_result = $this->lockAndTransForPlayerBalance($player['player_id'], function() use($controller, $player, &$arr) {
                $current_balance = $this->api->queryPlayerBalance($player['username'])['balance'];

                if($current_balance == 0){
                    $controller->resultCode = self::ERROR_CODE_PLAYER_BALANCE_ZERO;
                    return false;
                }
   
                $transaction_data['balance_before'] = $current_balance;
                $bet_amount = $arr->bet * -1;

                if($current_balance < $bet_amount){
                    $controller->resultCode = self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT;
                    return false;
                }

                $transaction_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($arr->transferId, $this->api->getTransactionsTable());

                if(!empty($transaction_id_exist)){
                    /* $controller->resultCode = self::ERROR_CODE_TRANSFER_ID_REPEATED;
                    return false; */
                    $controller->resultCode = self::SUCCEED;
                    return true;
                } else {
                    if($this->checkPreviousMonth && $this->api->use_monthly_transactions_table){
                        $transaction_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($arr->transferId, $this->api->getTransactionsPreviousTable());
                        if(!empty($transaction_id_exist)){
                            /* $controller->resultCode = self::ERROR_CODE_TRANSFER_ID_REPEATED;
                            return false; */
                            $controller->resultCode = self::SUCCEED;
                            return true;
                        }
                    }
                }

                $transaction_data['transaction_id'] = isset($arr->transferId) ? $arr->transferId : null;
                $transaction_data['external_unique_id'] = isset($arr->transferId) ? $arr->transferId : null;
                $transaction_data['game_seq_no'] = isset($arr->gameSeqNo) ? $arr->gameSeqNo : null;
                $transaction_data['round_id'] = isset($arr->gameSeqNo) ? $arr->gameSeqNo : null;
                $transaction_data['history_id'] = isset($arr->historyId) ? $arr->historyId : null;
                $transaction_data['player_id'] = $player['player_id'];
                $transaction_data['game_type'] = isset($arr->gType) ? $arr->gType : null;
                $transaction_data['game_id'] =  isset($arr->mType) ? $arr->mType : null;
                $transaction_data['report_date'] =  isset($arr->reportDate) ? $arr->reportDate : null;
                $transaction_data['start_at'] =  isset($arr->gameDate) ? $arr->gameDate : null;
                $transaction_data['end_at'] =  isset($arr->lastModifyTime) ? $arr->lastModifyTime : null;
                $transaction_data['currency'] =  isset($arr->currency) ? $arr->currency : null;
                $transaction_data['bet_amount'] =  $bet_amount;
                $transaction_data['win_amount'] =  isset($arr->win) ? $arr->win : null;
                $transaction_data['net_win'] =  isset($arr->netWin) ? $arr->netWin : null;
                $transaction_data['denom'] = isset($arr->denom) ? $arr->denom : null;
                $transaction_data['client_type'] = isset($arr->clientType) ? $arr->clientType : null;
                $transaction_data['system_take_win'] = isset($arr->systemTakeWin) ? $arr->systemTakeWin : null;
                $transaction_data['mb'] = isset($arr->mb) ? $arr->mb : '';
                $transaction_data['jackpot_win'] = isset($arr->jackpotWin) ? $arr->jackpotWin : null;
                $transaction_data['jackpot_contribute'] = isset($arr->jackpotContribute) ? $arr->jackpotContribute : null;
                $transaction_data['has_free_game'] = isset($arr->hasFreeGame) ? $arr->hasFreeGame : null;
                $transaction_data['has_gamble'] = isset($arr->hasGamble) ? $arr->hasGamble : null;
                //OGP-28649
                $uniqueid_of_seamless_service=$this->api->getPlatformCode().'-'.$transaction_data['external_unique_id'];
                $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $transaction_data['game_id']);

                $this->wallet_model->setGameProviderRoundId($transaction_data['round_id']);

                $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET_PAYOUT);
                $betAmount = isset($transaction_data['bet_amount'])?$transaction_data['bet_amount']:0;
                $this->wallet_model->setGameProviderBetAmount($betAmount);
                $payoutAmount = isset($transaction_data['win_amount'])?$transaction_data['win_amount']:0;
                $this->wallet_model->setGameProviderPayoutAmount($payoutAmount);


                $transaction_data['historyId'] = isset($arr->historyId) ? $arr->historyId : null;
                $adjustWallet = $controller->adjustWalletBetNSettle(self::TRANSACTION_CREDIT, $player, $transaction_data, true);
                $this->utils->debug_log('JUMBO_SEAMLESS ', 'ADJUST_WALLET_CREDIT: ', $adjustWallet);

                $this->utils->debug_log('JUMBO_SEAMLESS @betNSettle ADJUST_WALLET response: ', $adjustWallet);

                return true;
            });
        }

        $transaction_data['balance_after'] = $this->api->queryPlayerBalance($player['username'])['balance'];

        if($transaction_result) {
            $data_err = [
                "status" => self::SUCCEED['status'],
                "balance" => $transaction_data['balance_after'],
                "err_text" => self::SUCCEED['err_text']
            ];

            return $this->setOutput($data_err);

        }else{
            $statusCode = "";

            switch ($controller->resultCode['status']) {
                case self::ERROR_CODE_PLAYER_BALANCE_ZERO['status']:
                    $statusCode = self::ERROR_CODE_PLAYER_BALANCE_ZERO;
                    break;
                case self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT['status']:
                    $statusCode = self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT;
                    break;
                case self::ERROR_CODE_TRANSFER_ID_REPEATED['status']:
                    $statusCode = self::ERROR_CODE_TRANSFER_ID_REPEATED;
                break;
                case self::ERROR_CODE_PARAMETER_ERROR['status']:
                    $statusCode = self::ERROR_CODE_PARAMETER_ERROR;
                    break;
                default:
                    $statusCode = self::ERROR_CODE_FAILED;
                    break;
                }

            $data_err = [
                "status" => $statusCode['status'],
                "balance" => $transaction_data['balance_after'],
                "err_text" => $statusCode['err_text']
            ];

            if(!empty($controller->resultCode)){
                return $this->setOutput($data_err);
            }else{
                return $this->setOutput($data_err);
            }
        }
    }

    /**
     * This action is called by JDB after 1 minute when the BetNSettle(Action 8) request failed.
     * If JDB received status 9017 from your service, JDB will resend Cancel BetNSettle (Action 4) after 1 minute.
     * If JDB received status 6101 from your service, JDB will recover the game history corresponding to the original BetNSettle(Action 8) request.
     */
    public function cancelBetNSettle($arr){
        $transactionData = $this->jumbo_transactions->checkTransactionSettled($arr->transferId, $this->api->getTransactionsTable());
        if(empty($transactionData)){
            if($this->checkPreviousMonth && $this->api->use_monthly_transactions_table){
                $transactionData = $this->jumbo_transactions->checkTransactionSettled($arr->transferId, $this->api->getTransactionsPreviousTable());
                if(empty($transactionData)){
                    $data = [
                        "status" => self::ERROR_CODE_DATA_NOT_EXIST['status'],
                        "err_text" => self::ERROR_CODE_DATA_NOT_EXIST['err_text']
                    ];
                    return $this->setOutput($data);
                }else{
                    if($transactionData->game_status == self::STATUS_SETTLED){
                        return $this->setOutput(self::ERROR_CODE_CAN_NOT_CANCEL);
                    }
                }
            } else {
                $data = [
                    "status" => self::ERROR_CODE_DATA_NOT_EXIST['status'],
                    "err_text" => self::ERROR_CODE_DATA_NOT_EXIST['err_text']
                ];
                return $this->setOutput($data);
            }

        }else{
            if($transactionData->game_status == self::STATUS_SETTLED){
                return $this->setOutput(self::ERROR_CODE_CAN_NOT_CANCEL);
            }
        }
    }

    public function bet($arr){
        $this->requestParams->function = __function__;
        if(isset($arr->currency)){
            $check_currency = $this->checkCurrency($arr->currency);
            if(!$check_currency){
                $data = [
                    "status" => self::ERROR_CODE_PARAMETER_ERROR['status'],
                    "balance" => 0.00,
                    "err_text" => self::ERROR_CODE_PARAMETER_ERROR['err_text']
                ];
                return $this->setOutput($data);
            }
        }

        $player = $this->getPlayer($arr->uid);

        if(!$player){
            $data = [
                "status" => self::ERROR_CODE_USER_ID_NOT_FOUND['status'],
                "err_text" => self::ERROR_CODE_USER_ID_NOT_FOUND['err_text']
            ];

            return $this->setOutput($data);
        }else{

            $player_token = $this->common_token->getValidPlayerToken($player['player_id']);
            if(empty($player_token)){
                return $this->setOutput(self::ERROR_CODE_TIMEOUT);
            }

            $controller = $this;
            $transaction_data = [];
            //Lock Balance
            $transaction_result = $this->lockAndTransForPlayerBalance($player['player_id'], function() use($controller, $player, &$transaction_data, $arr) {
                $bet_amount = isset($arr->amount) ? $arr->amount : null;
                $current_balance = null;    

                if(method_exists($this->utils, 'isEnabledRemoteWalletClient') && $this->utils->isEnabledRemoteWalletClient()){
                    $this->utils->debug_log("SPRIBE: (bet) ignored insufficient balance checking bet_amount > get_balance ", $arr, $transaction_data);
                }else{
                    $current_balance = $this->api->queryPlayerBalance($player['username'])['balance'];
                    if($current_balance == 0){
                        $controller->resultCode = self::ERROR_CODE_PLAYER_BALANCE_ZERO;
                        return false;
                    }
    
                    if($current_balance < $bet_amount){
                        $controller->resultCode = self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT;
                        return false;
                    }
                }                
  
                $transaction_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($arr->transferId, $this->api->getTransactionsTable());

                if(!empty($transaction_id_exist)){
                    /* $controller->resultCode = self::ERROR_CODE_TRANSFER_ID_REPEATED;
                    return false; */
                    $transaction_data['code'] = self::RETURN_OK;
                    $controller->resultCode = self::SUCCEED;
                    return true;
                } else {
                    if($this->checkPreviousMonth && $this->api->use_monthly_transactions_table){
                        $transaction_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($arr->transferId, $this->api->getTransactionsPreviousTable());
                        if(!empty($transaction_id_exist)){
                            /* $controller->resultCode = self::ERROR_CODE_TRANSFER_ID_REPEATED;
                            return false; */
                            $transaction_data['code'] = self::RETURN_OK;
                            $controller->resultCode = self::SUCCEED;
                            return true;
                        }
                    }
                }

                #process round id
                $arr->round_id = $arr->gameRoundSeqNo;
                $arr->round_id .= '-' . (isset($arr->transferId)&&!empty($arr->transferId)?$arr->transferId:null);

                $transaction_data = $controller->processPrepayBetGame(self::TRANSACTION_DEBIT, $player, $arr, $current_balance);

                $this->utils->debug_log('JUMBO_SEAMLESS - ADJUST_WALLET_DEBIT: ', $transaction_data);

                $success = isset($transaction_data['is_success']) ? $transaction_data['is_success'] : false;

                return $success;
            });
        }

        if(!isset($transaction_data['balance_after'])){
            $transaction_data['balance_after'] = $this->api->queryPlayerBalance($player['username'])['balance'];
        }

        if($this->api->enable_mock_cancel_bet && in_array($player['username'], $this->api->enable_mock_cancel_player_list)){
            $this->utils->debug_log("SPRIBE - triggered force cancel on bet request");
            return null; // force cancel here , return unexpected response
        }

        if($transaction_result && isset($transaction_data['code']) && $transaction_data['code'] == self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT) {
            $this->utils->debug_log("SPRIBE bet - transaction_data status:", $transaction_data['code']['status']);
            return $this->setOutput(self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT);
        }

        if($transaction_result && isset($transaction_data['code']) && $transaction_data['code'] == self::RETURN_OK) {
            $data_err = [
                "status" => self::SUCCEED['status'],
                "balance" => $transaction_data['balance_after'],
                "err_text" => self::SUCCEED['err_text']
            ];

            
            $this->utils->debug_log("SPRIBE - bet: success final data_err", $data_err);
            return $this->setOutput($data_err);

        }else{
            $statusCode = "";

            switch ($controller->resultCode['status']) {
                case self::ERROR_CODE_PLAYER_BALANCE_ZERO['status']:
                    $statusCode = self::ERROR_CODE_PLAYER_BALANCE_ZERO;
                    break;
                case self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT['status']:
                    $statusCode = self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT;
                    break;
                case self::ERROR_CODE_TRANSFER_ID_REPEATED['status']:
                    $statusCode = self::ERROR_CODE_TRANSFER_ID_REPEATED;
                break;
                case self::ERROR_CODE_PARAMETER_ERROR['status']:
                    $statusCode = self::ERROR_CODE_PARAMETER_ERROR;
                    break;
                default:
                    $statusCode = self::ERROR_CODE_FAILED;
                    break;
                }

            $data_err = [
                "status" => $statusCode['status'],
                "balance" => $transaction_data['balance_after'],
                "err_text" => $statusCode['err_text']
            ];

            $this->utils->debug_log("SPRIBE - bet: final data_err", $data_err);
            if(!empty($controller->resultCode)){
                return $this->setOutput($data_err);
            }else{
                return $this->setOutput($data_err);
            }
        }
    }

    public function settle($arr){
        $this->requestParams->function = __function__;
        if(isset($arr->currency)){
            $check_currency = $this->checkCurrency($arr->currency);
            if(!$check_currency){
                $data = [
                    "status" => self::ERROR_CODE_PARAMETER_ERROR['status'],
                    "balance" => 0.00,
                    "err_text" => self::ERROR_CODE_PARAMETER_ERROR['err_text']
                ];
                return $this->setOutput($data);
            }
        }

        $player = $this->getPlayer($arr->uid);

        if(!$player){
            $data = [
                "status" => self::ERROR_CODE_USER_ID_NOT_FOUND['status'],
                "err_text" => self::ERROR_CODE_USER_ID_NOT_FOUND['err_text']
            ];

            return $this->setOutput($data);
        }else{

            // $player_token = $this->common_token->getValidPlayerToken($player['player_id']);
            // if(empty($player_token)){
            //     return $this->setOutput(self::ERROR_CODE_TIMEOUT);
            // }

            $controller = $this;
            $transaction_data = [];

            //Lock Balance
            $transaction_result = $this->lockAndTransForPlayerBalance($player['player_id'], function() use($controller, $player, &$transaction_data, $arr) {
                $round_id = isset($arr->gameRoundSeqNo) ? $arr->gameRoundSeqNo : null;
                $this->wallet_model->setGameProviderRoundId($round_id);
                $transaction_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($arr->transferId, $this->api->getTransactionsTable());
                if(!empty($transaction_id_exist)){
                    /* $controller->resultCode = self::ERROR_CODE_TRANSFER_ID_REPEATED;
                    return false; */
                    $transaction_data['code'] = self::RETURN_OK;
                    $controller->resultCode = self::SUCCEED;
                    return true;
                } else {
                    if($this->checkPreviousMonth && $this->api->use_monthly_transactions_table){
                        $transaction_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($arr->transferId, $this->api->getTransactionsPreviousTable());
                        if(!empty($transaction_id_exist)){
                            /* $controller->resultCode = self::ERROR_CODE_TRANSFER_ID_REPEATED;
                            return false; */
                            $transaction_data['code'] = self::RETURN_OK;
                            $controller->resultCode = self::SUCCEED;
                            return true;
                        }
                    }
                }

                $ref_transfer_ids = isset($arr->refTransferIds) ? $arr->refTransferIds : null;
                if(!empty($ref_transfer_ids) &&  count($ref_transfer_ids) > 1){
                    $controller->resultCode = self::ERROR_CODE_BAD_REQUEST;
                    return false;
                }
                $ref_transfer_id = current($ref_transfer_ids); #get first data only
                $ref_transfer_id=(string)$ref_transfer_id;
                $transaction_type = self::TRANSACTION_DEBIT;
                $related_uniqueid_of_seamless_service = 'game-' . $this->api->getPlatformCode()."-{$transaction_type}-".$ref_transfer_id;
                if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                    $this->wallet_model->setRelatedUniqueidOfSeamlessService($related_uniqueid_of_seamless_service);
                }

                if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
                    $this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
                }

                $reference_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($ref_transfer_id, $this->api->getTransactionsTable());
                if(empty($reference_id_exist)){
                    if($this->checkPreviousMonth && $this->api->use_monthly_transactions_table){
                        $reference_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($ref_transfer_id, $this->api->getTransactionsPreviousTable());
                        if(empty($reference_id_exist)){
                            $controller->resultCode = self::ERROR_CODE_DATA_NOT_EXIST;
                            return false;
                        }
                    } else {
                        $controller->resultCode = self::ERROR_CODE_DATA_NOT_EXIST;
                        return false;
                    }
                }

                $isSettled = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->api->getTransactionsTable(), ["ref_transfer_id" =>$ref_transfer_id]);
                if($isSettled){
                    $controller->resultCode = self::ERROR_CODE_FAILED;
                    return false;
                } else {
                    if($this->checkPreviousMonth && $this->api->use_monthly_transactions_table){
                        $isSettled = $this->original_seamless_wallet_transactions->isTransactionExistCustom($this->api->getTransactionsPreviousTable(), ["ref_transfer_id" =>$ref_transfer_id]);
                        if($isSettled){
                            $controller->resultCode = self::ERROR_CODE_FAILED;
                            return false;
                        }
                    }
                }

                // list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData($this->jumbo_transactions->tableName, ["round_id" =>$arr->gameRoundSeqNo], 'game_status');
                // if(!empty($lastStatus) && $lastStatus ==GAME_LOGS::STATUS_CANCELLED){
                //     $controller->resultCode = self::ERROR_CODE_FAILED;
                //     return false;
                // }



                #process round id
                $arr->round_id = $arr->gameRoundSeqNo;
                if(isset($arr->refTransferIds) && !empty($arr->refTransferIds)){
                    $refTransferIdsArr = (array)$arr->refTransferIds;
                    $arr->round_id .=  '-' . implode('-', $refTransferIdsArr);
                }
                $arr->round_id =  trim($arr->round_id, '-');

                $transaction_data = $controller->processPrepayBetGame(self::TRANSACTION_CREDIT, $player, $arr, null, true);
                $this->utils->debug_log('JUMBO_SEAMLESS ', 'ADJUST_WALLET_SETTLE: ', $transaction_data);
                $success = isset($transaction_data['is_success']) ? $transaction_data['is_success'] : false;
                if($success){
                    #update bet status by round id and player id
                    $betStatus = GAME_LOGS::STATUS_SETTLED;
                    $bet_external_unique_id = (string)$ref_transfer_id;
                    $this->jumbo_transactions->updateStatusByTransactionId($betStatus, $bet_external_unique_id, $this->api->getTransactionsTable());
                    if($this->checkPreviousMonth && $this->api->use_monthly_transactions_table){
                        $this->jumbo_transactions->updateStatusByTransactionId($betStatus, $bet_external_unique_id, $this->api->getTransactionsPreviousTable());
                    }
                }
                
                return $success;
            });
        }

        if(!isset($transaction_data['balance_after'])){
            $transaction_data['balance_after'] = $this->api->queryPlayerBalance($player['username'])['balance'];
        }

        if($transaction_result && isset($transaction_data['code']) && $transaction_data['code'] == self::RETURN_OK) {
            $data_err = [
                "status" => self::SUCCEED['status'],
                "balance" => $transaction_data['balance_after'],
                "err_text" => self::SUCCEED['err_text']
            ];

            return $this->setOutput($data_err);

        }else{
            $statusCode = "";

            switch ($controller->resultCode['status']) {
                case self::ERROR_CODE_PLAYER_BALANCE_ZERO['status']:
                    $statusCode = self::ERROR_CODE_PLAYER_BALANCE_ZERO;
                    break;
                case self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT['status']:
                    $statusCode = self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT;
                    break;
                case self::ERROR_CODE_TRANSFER_ID_REPEATED['status']:
                    $statusCode = self::ERROR_CODE_TRANSFER_ID_REPEATED;
                break;
                case self::ERROR_CODE_PARAMETER_ERROR['status']:
                    $statusCode = self::ERROR_CODE_PARAMETER_ERROR;
                    break;
                case self::ERROR_CODE_DATA_NOT_EXIST['status']:
                    $statusCode = self::ERROR_CODE_DATA_NOT_EXIST;
                    break;
                default:
                    $statusCode = self::ERROR_CODE_FAILED;
                    break;
                }

            $data_err = [
                "status" => $statusCode['status'],
                "balance" => $transaction_data['balance_after'],
                "err_text" => $statusCode['err_text']
            ];

            if(!empty($controller->resultCode)){
                return $this->setOutput($data_err);
            }else{
                return $this->setOutput($data_err);
            }
        }
    }

    public function cancel($arr){
        $this->requestParams->function = __function__;
        if(isset($arr->currency)){
            $check_currency = $this->checkCurrency($arr->currency);
            if(!$check_currency){
                $data = [
                    "status" => self::ERROR_CODE_PARAMETER_ERROR['status'],
                    "balance" => 0.00,
                    "err_text" => self::ERROR_CODE_PARAMETER_ERROR['err_text']
                ];
                return $this->setOutput($data);
            }
        }

        $player = $this->getPlayer($arr->uid);

        if(!$player){
            $data = [
                "status" => self::ERROR_CODE_USER_ID_NOT_FOUND['status'],
                "err_text" => self::ERROR_CODE_USER_ID_NOT_FOUND['err_text']
            ];

            return $this->setOutput($data);
        }else{

            // $player_token = $this->common_token->getValidPlayerToken($player['player_id']);
            // if(empty($player_token)){
            //     return $this->setOutput(self::ERROR_CODE_TIMEOUT);
            // }

            $controller = $this;
            $transaction_data = [];
            //Lock Balance
            $transaction_result = $this->lockAndTransForPlayerBalance($player['player_id'], function() use($controller, $player, &$transaction_data, $arr) {

                $transaction_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($arr->transferId, $this->api->getTransactionsTable());
                if(!empty($transaction_id_exist)){
                    /* $controller->resultCode = self::ERROR_CODE_TRANSFER_ID_REPEATED;
                    return false; */
                    $transaction_data['code'] = self::RETURN_OK;
                    $controller->resultCode = self::SUCCEED;
                    return true;
                } else {
                    if($this->checkPreviousMonth && $this->api->use_monthly_transactions_table){
                        $transaction_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($arr->transferId, $this->api->getTransactionsPreviousTable());
                        if(!empty($transaction_id_exist)){
                            /* $controller->resultCode = self::ERROR_CODE_TRANSFER_ID_REPEATED;
                            return false; */
                            $transaction_data['code'] = self::RETURN_OK;
                            $controller->resultCode = self::SUCCEED;
                            return true;
                        }
                    }
                }

                $ref_transfer_ids = isset($arr->refTransferIds) ? $arr->refTransferIds : null;
                if(!empty($ref_transfer_ids) &&  count($ref_transfer_ids) > 1){
                    $controller->resultCode = self::ERROR_CODE_BAD_REQUEST;
                    return false;
                }
                $ref_transfer_id = current($ref_transfer_ids); #get first data only
                $ref_transfer_id=(string)$ref_transfer_id;
                $transaction_type = self::TRANSACTION_DEBIT;
                $related_uniqueid_of_seamless_service = 'game-' . $this->api->getPlatformCode()."-{$transaction_type}-".$ref_transfer_id;
                if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                    $this->wallet_model->setRelatedUniqueidOfSeamlessService($related_uniqueid_of_seamless_service);
                }


                if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
                    $this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
                }

                $reference_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($ref_transfer_id, $this->api->getTransactionsTable());
                if(empty($reference_id_exist)){
                    if($this->checkPreviousMonth && $this->api->use_monthly_transactions_table){
                        $reference_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($ref_transfer_id, $this->api->getTransactionsPreviousTable());
                        if(empty($reference_id_exist)){
                            $transaction_data['code'] = self::RETURN_OK;
                            $controller->resultCode = self::SUCCEED;
                            return true;
                        }
                    } else {
                        $transaction_data['code'] = self::RETURN_OK;
                        $controller->resultCode = self::SUCCEED;
                        return true;
                    }
                }

                $settledStatuses  = [Game_logs::STATUS_SETTLED, Game_logs::STATUS_REFUND, Game_logs::STATUS_CANCELLED];
                $isSettled = $this->jumbo_transactions->getExistingTransactionByRefTransferId($this->api->getTransactionsTable(), $ref_transfer_id);
                if(!empty($isSettled) && in_array($isSettled->game_status, $settledStatuses)){
                    $controller->resultCode = self::ERROR_CODE_FAILED;
                    return false;
                } else {
                    if($this->checkPreviousMonth && $this->api->use_monthly_transactions_table){
                        $isSettled = $this->jumbo_transactions->getExistingTransactionByRefTransferId($this->api->getTransactionsPreviousTable(), $ref_transfer_id);
                        if(!empty($isSettled) && in_array($isSettled->game_status, $settledStatuses)){
                            $controller->resultCode = self::ERROR_CODE_FAILED;
                            return false;
                        }
                    }
                }

                // list($lastId, $lastStatus) = $this->original_seamless_wallet_transactions->getLastStatusOfCommonData($this->jumbo_transactions->tableName, ["round_id" =>$arr->gameRoundSeqNo], 'game_status');
                // if(!empty($lastStatus) && $lastStatus ==GAME_LOGS::STATUS_SETTLED){
                //     $controller->resultCode = self::ERROR_CODE_CAN_NOT_CANCEL;
                //     return false;
                // }

                #process round id
                $arr->round_id = $arr->gameRoundSeqNo;
                if(isset($arr->refTransferIds) && !empty($arr->refTransferIds)){
                    $refTransferIdsArr = (array)$arr->refTransferIds;
                    $arr->round_id .=  '-' . implode('-', $refTransferIdsArr);
                }
                $arr->round_id =  trim($arr->round_id, '-');
                if(!isset($arr->mType)&&isset($isSettled->game_id)){
                    $arr->mType = isset($isSettled->game_id)?$isSettled->game_id:null;
                }

                $external_game_id = $arr->mType;
                $transaction_data = $controller->processPrepayBetGame(self::TRANSACTION_CANCELLED, $player, $arr, null, true, $external_game_id);

                $this->utils->debug_log('JUMBO_SEAMLESS ', 'CANCEL_EXTERNAL_GAME_ID: ', $external_game_id);
                $this->utils->debug_log('JUMBO_SEAMLESS ', 'ADJUST_WALLET_DEBIT: ', $transaction_data);
                $success = isset($transaction_data['is_success']) ? $transaction_data['is_success'] : false;
                if($success){
                    #update bet status by round id and player id
                    $betStatus = GAME_LOGS::STATUS_CANCELLED;
                    $bet_external_unique_id = (string)$ref_transfer_id;
                    $this->jumbo_transactions->updateStatusByTransactionId($betStatus, $bet_external_unique_id, $this->api->getTransactionsTable());
                    if($this->checkPreviousMonth && $this->api->use_monthly_transactions_table){
                        $this->jumbo_transactions->updateStatusByTransactionId($betStatus, $bet_external_unique_id, $this->api->getTransactionsPreviousTable());
                    }
                }

                return $success;
            });
        }

        if(!isset($transaction_data['balance_after'])){
            $transaction_data['balance_after'] = $this->api->queryPlayerBalance($player['username'])['balance'];
        }

        if($transaction_result && isset($transaction_data['code']) && $transaction_data['code'] == self::RETURN_OK) {
            $data_err = [
                "status" => self::SUCCEED['status'],
                "balance" => $transaction_data['balance_after'],
                "err_text" => self::SUCCEED['err_text']
            ];

            return $this->setOutput($data_err);

        }else{
            $statusCode = "";

            switch ($controller->resultCode['status']) {
                case self::ERROR_CODE_PLAYER_BALANCE_ZERO['status']:
                    $statusCode = self::ERROR_CODE_PLAYER_BALANCE_ZERO;
                    break;
                case self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT['status']:
                    $statusCode = self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT;
                    break;
                case self::ERROR_CODE_TRANSFER_ID_REPEATED['status']:
                    $statusCode = self::ERROR_CODE_TRANSFER_ID_REPEATED;
                break;
                case self::ERROR_CODE_PARAMETER_ERROR['status']:
                    $statusCode = self::ERROR_CODE_PARAMETER_ERROR;
                    break;
                case self::ERROR_CODE_DATA_NOT_EXIST['status']:
                    $statusCode = self::ERROR_CODE_DATA_NOT_EXIST;
                    break;
                case self::ERROR_CODE_CAN_NOT_CANCEL['status']:
                    $statusCode = self::ERROR_CODE_CAN_NOT_CANCEL;
                    break;
                default:
                    $statusCode = self::ERROR_CODE_FAILED;
                    break;
                }

            $data_err = [
                "status" => $statusCode['status'],
                "balance" => $transaction_data['balance_after'],
                "err_text" => $statusCode['err_text']
            ];

            if(!empty($controller->resultCode)){
                return $this->setOutput($data_err);
            }else{
                return $this->setOutput($data_err);
            }
        }
    }

    public function getPlayer($uid){
        $this->CI->load->model('game_provider_auth');

        if(isset($uid)){
            $this->currentPlayer = (array) $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($uid, $this->api->getPlatformCode());
            if(empty($this->currentPlayer)){
                return false;
            }else{
                return $this->currentPlayer;
            }
        }else{
            return false;
        }
    }

    public function checkCurrency($currency){
        if($currency!=$this->currency){
            return false;
        }else{
            return true;
        }
    }

    public function convert_timestamp($ts){
        return date("Y-m-d H:i:s", ($ts / 1000));
    }

    public function processPrepayBetGame($transaction_type, $player_info, $object, $current_balance = null, $is_end = false, $external_game_id=null){
        if(is_numeric($current_balance) || !is_null($current_balance))
        {
            $before_balance = $current_balance;
        } else {
            $before_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];
        }
        $return_data = [
            'code' => self::ERROR_CODE_BAD_REQUEST['status'],
            'before_balance' => $before_balance,
            'after_balance' => $before_balance
        ];
        $wallet_transaction = [];
        $data = (array) $object;
        $after_balance = null;
        $bet_amount = null;
        $result_amount = null;
        $success = false;

        if(is_null($before_balance)){
            $this->utils->debug_log("SPRIBE @processPrepayBetGame before_balance is null");
            $return_data['code'] = self::ERROR_CODE_FAILED;
            $return_data['is_success'] = false;
        }

        $mType = isset($data['mType']) ? $data['mType'] : null;
        $external_game_id = is_null($external_game_id) ? $mType : $external_game_id;
        $transferId =  $external_unique_id = isset($data['transferId']) ? $data['transferId'] : null;
        $refTransferIds = isset($data['refTransferIds']) ? $data['refTransferIds'] : [];
        $game_seq_no = isset($data['gameSeqNo']) ? $data['gameSeqNo'] : null;
        $round_id = isset($data['round_id']) ? $data['round_id'] : null;

        if(empty($round_id)){
            $round_id = isset($data['gameRoundSeqNo']) ? $data['gameRoundSeqNo'] : null;
        }

        $uniqueid_of_seamless_service = $this->api->getPlatformCode()."-{$transaction_type}-".$external_unique_id;
        $amount = isset($data['amount']) ? $data['amount'] : null;
        $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $external_game_id);
        $this->wallet_model->setGameProviderRoundId($round_id);

        #set remote is_end
        $this->wallet_model->setGameProviderIsEndRound($is_end);

        if($transaction_type == self::TRANSACTION_DEBIT){
            $this->wallet_model->setGameProviderActionType(wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
            $report_date = $start_at = $end_at = $this->utils->getNowForMysql();
            $status = GAME_LOGS::STATUS_PENDING;
            $bet_amount = $amount;
            $result_amount = -$amount;

            $success = $this->wallet_model->decSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $amount, $after_balance);
            $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

            if(method_exists($this->utils, 'isEnabledRemoteWalletClient') && $this->utils->isEnabledRemoteWalletClient()){
                $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                $this->utils->debug_log("SPRIBE (processPrePayBetGame) - remoteErrorCode: " , $remoteErrorCode);
                if($remoteErrorCode==Wallet_model::REMOTE_WALLET_CODE_INSUFFICIENT_BALANCE){
                    $return_data['code'] = self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT;
                    $this->utils->debug_log("SPRIBE (processPrePayBetGame) - remoteErrorCode: " , $remoteErrorCode);
                }
            }
        } else if($transaction_type == self::TRANSACTION_CREDIT){
            $this->wallet_model->setGameProviderActionType(wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT);
            $report_date = DateTime::createFromFormat('d-m-Y H:i:s', $data['reportDate']);
            $report_date = $this->api->gameTimeToServerTime($report_date);

            $start_at = DateTime::createFromFormat('d-m-Y H:i:s', $data['gameDate']);
            $start_at = $this->api->gameTimeToServerTime($start_at);

            $end_at = DateTime::createFromFormat('d-m-Y H:i:s', $data['lastModifyTime']);
            $end_at = $this->api->gameTimeToServerTime($end_at);

            $status = GAME_LOGS::STATUS_SETTLED;
            if(isset($data['roundClosed'])){
                $status = $data['roundClosed'] == true ? GAME_LOGS::STATUS_SETTLED : GAME_LOGS::STATUS_PENDING;
            }
            $bet_amount = isset($data['bet']) ? $data['bet'] : null;
            $result_amount = isset($data['netWin']) ? $data['netWin'] : null;
            if($amount> 0){
                $success = $this->wallet_model->incSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $amount, $after_balance);
                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();
            } else if($amount == 0){

                if(method_exists($this->utils, 'isEnabledRemoteWalletClient') && $this->utils->isEnabledRemoteWalletClient()){
                    $this->utils->debug_log("JUMBO/SPRIBE SEAMLESS SERVICE API: (processPrepayBetGame) amount 0 call remote wallet",
                    'transaction_type', $transaction_type, 'data', $data);
                    if(method_exists($this->wallet_model,'incRemoteWallet')){
                        $succ=$this->wallet_model->incRemoteWallet($player_info['player_id'], $amount, $this->api->getPlatformCode(), $after_balance);
                        $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                    }
                }
                $success = true;
            }

        } else if($transaction_type == self::TRANSACTION_CANCELLED){
            $this->wallet_model->setGameProviderActionType(wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND);
            $report_date = $start_at = $end_at = $this->utils->getNowForMysql();
            $status = GAME_LOGS::STATUS_CANCELLED;
            $bet_amount = $amount;
            $result_amount = 0;
            if($amount> 0){
                $success = $this->wallet_model->incSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $amount, $after_balance);
                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

            } else if($amount == 0){
                $success = true;
            }
        } else if($transaction_type == self::TRANSACTION_REFUND){
            $this->wallet_model->setGameProviderActionType(wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND);
            $report_date = $start_at = $end_at = $this->utils->getNowForMysql();
            $status = GAME_LOGS::STATUS_REFUND;
            $bet_amount = 0;
            $result_amount = 0;
            $amount = isset($data['netWin']) ? $data['netWin'] : null;
            $update = false;

            if($amount> 0){
                $amount = abs($amount);
                $success = $this->wallet_model->decSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $amount, $after_balance);
                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                $update = $success;
            } else if($amount< 0){
                $amount = abs($amount);
                $success = $this->wallet_model->incSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $amount, $after_balance);
                $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

                $update = $success;
            } else if($amount == 0){
                $success = true;
            }

            if($update){
                $this->jumbo_transactions->updateStatusByTransactionId($status, $external_unique_id, $this->api->getTransactionsTable());
                if($this->checkPreviousMonth && $this->api->use_monthly_transactions_table){
                    $this->jumbo_transactions->updateStatusByTransactionId($status, $external_unique_id, $this->api->getTransactionsPreviousTable());
                }
            }
            #override
            $external_unique_id = isset($data['transferId']) ? "RM".$data['transferId'] : null;
        } else {
            $success = false;
        }

        if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {
            if ($this->ssa_remote_wallet_error_double_unique_id()) {
                $this->utils->debug_log('SPRIBE @processPrepayBetGame double unique'); 
                $success = true;
                $return_data['code'] = self::RETURN_OK;
            }
        }

        if(!is_numeric($after_balance)){
            $return_data['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
            $this->utils->debug_log("SPRIBE (processPrePayBetGame) - final queryPlayerBalance: " , $return_data);
            if(method_exists($this->utils, 'isEnabledRemoteWalletClient') && $this->utils->isEnabledRemoteWalletClient()){
                $remoteErrorCode = $this->wallet_model->getRemoteWalletErrorCode();
                $this->utils->debug_log("SPRIBE (processPrePayBetGame) - remoteErrorCode: " , $remoteErrorCode);
                if($remoteErrorCode==Wallet_model::REMOTE_WALLET_CODE_INSUFFICIENT_BALANCE){
                    $return_data['code'] = self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT;
                    $this->utils->debug_log("SPRIBE (processPrePayBetGame) - remoteErrorCode: " , $remoteErrorCode);
                    $success = false;
                }
            }
        } else {
            $return_data['after_balance'] = $after_balance;
        }

        $this->utils->debug_log("SPRIBE (processPrePayBetGame) - final success: " , $success);

        if(!$success){ #error on adjustment, check if unique id already process
            $this->utils->debug_log('JUMBO_SEAMLESS response: ', $success);
            if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {
                if ($this->ssa_remote_wallet_error_double_unique_id()) {
                    $this->utils->debug_log('JUMBO_SEAMLESS double unique');
                    $return_data['code'] = self::RETURN_OK;
                    $success = true;
                }
            }
        }

        $wallet_transaction['extra_info'] = json_encode($data);
        $wallet_transaction['amount'] = $amount;
        $wallet_transaction['bet_amount'] = $bet_amount;
        $wallet_transaction['result_amount'] = $result_amount;
        $wallet_transaction['transaction_id'] = $external_unique_id;
        $wallet_transaction['external_unique_id'] = $external_unique_id;
        $wallet_transaction['game_seq_no'] = isset($data['gameSeqNo']) ? $data['gameSeqNo'] : null;
        $wallet_transaction['valid_bet'] = isset($data['validBet']) ? $data['validBet'] : null;
        $wallet_transaction['round_id'] = isset($data['round_id']) ? $data['round_id'] : null;

        if(empty($wallet_transaction['round_id'])){
            $wallet_transaction['round_id'] = isset($data['gameRoundSeqNo']) ? $data['gameRoundSeqNo'] : null;
        }

        $wallet_transaction['player_id'] = $player_info['player_id'];
        $wallet_transaction['game_username'] = $player_info['game_username'];
        $wallet_transaction['game_type'] = isset($data['gType']) ? $data['gType'] : null;
        $wallet_transaction['game_id'] = $external_game_id;
        $wallet_transaction['report_date'] = $report_date;
        $wallet_transaction['start_at'] = $start_at;
        $wallet_transaction['end_at'] = $end_at;
        $wallet_transaction['currency'] = isset($data['currency']) ? $data['currency'] : null;
        $wallet_transaction['win_amount'] = isset($data['win']) ? $data['win'] : null;
        $wallet_transaction['net_win'] = isset($data['netWin']) ? $data['netWin'] : null;
        $wallet_transaction['denom'] = isset($data['denom']) ? $data['denom'] : null;
        $wallet_transaction['client_type'] = isset($data['clientType']) ? $data['clientType'] : null;
        $wallet_transaction['system_take_win'] = isset($data['systemTakeWin']) ? $data['systemTakeWin'] : null;
        $wallet_transaction['jackpot_win'] = isset($data['jackpotWin']) ? $data['jackpotWin'] : null;
        $wallet_transaction['jackpot_contribute'] = isset($data['jackpotContribute']) ? $data['jackpotContribute'] : null;
        $wallet_transaction['has_free_game'] = isset($data['hasFreegame']) ? $data['hasFreegame'] : null;
        $wallet_transaction['has_gamble'] = isset($data['hasGamble']) ? $data['hasGamble'] : null;
        $wallet_transaction['historyId'] = isset($data['historyId']) ? $data['historyId'] : null;

        $wallet_transaction['game_platform_id'] = $this->api->getPlatformCode();
        $wallet_transaction['before_balance'] = $return_data['before_balance'];
        $wallet_transaction['after_balance'] = $return_data['after_balance'];

        $wallet_transaction['transaction_type'] = $transaction_type;
        $wallet_transaction['game_status'] = $status;

        $wallet_transaction['created_at'] = $this->utils->getNowForMysql();
        $wallet_transaction['updated_at'] = $this->utils->getNowForMysql();
        $ref_transfer_ids = isset($data['refTransferIds']) ? $data['refTransferIds'] : null;
        if(!empty($ref_transfer_ids) && is_array($ref_transfer_ids)){
            $wallet_transaction['ref_transfer_id'] = current($ref_transfer_ids);
        }

        if (!empty($this->remote_wallet_status) && !empty($wallet_transaction)) {
            $this->save_remote_wallet_failed_transaction($this->ssa_insert, $wallet_transaction);
        }

        if(is_null($wallet_transaction['after_balance'])){
            $wallet_transaction['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
            if(is_null($wallet_transaction['after_balance'])){ #if still null return error
                $this->utils->debug_log("SPRIBE @processPrepayBetGame aafter_balance is null");
                $return_data['code'] = self::ERROR_CODE_FAILED;
                $success = false;
            }
        }

        if ($success) {
            $return_data['code'] = self::RETURN_OK;
            $this->wallet_transaction_id = $this->jumbo_transactions->insertTransaction($wallet_transaction, $this->api->getTransactionsTable());
            $this->utils->debug_log('JUMBO_SEAMLESS', $this->wallet_transaction_id);
            $this->wallet_transaction = $wallet_transaction;
            if(!$this->wallet_transaction_id) {
                $this->utils->debug_log("SPRIBE @prrocessPrepayBetGame failed to save transaction");
                $return_data['code'] = self::ERROR_CODE_FAILED;
                $success = false;
            }
        }



        $return_data['balance_after'] = $return_data['after_balance'];
        $return_data['is_success'] = $success;

        $this->utils->debug_log("SPRIBE (processPrePayBetGame) - final return_data: " , $return_data);
        return $return_data;
    }

    private function adjustWalletBetNSettle($transaction_type, $player_info, $extra = [], $is_end=false) {

        $return_data = [
            'code' => self::RETURN_OK
        ];
        $external_game_id = isset($extra['game_id']) ? $extra['game_id'] : null;

        //OGP-28649
        $uniqueid_of_seamless_service=$this->api->getPlatformCode().'-'.$extra['external_unique_id'];
        $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service,$external_game_id);
        $this->wallet_model->setGameProviderRoundId($extra['round_id']);

        $wallet_transaction = [];
        $before_balance = $extra['balance_before'];
        $after_balance = $before_balance;
        $return_data['before_balance'] = $before_balance;

        //Settling bet
        $bet_amount = $extra['bet_amount'];
        $payout_amount = $extra['win_amount'];
        // $result = $extra['net_win'];
        $result =   $payout_amount - $bet_amount;

        #set remote is_end
        $this->wallet_model->setGameProviderIsEndRound($is_end);


        if($result > 0) {
            $response = $this->wallet_model->incSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $result, $after_balance);
            $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

            if(!$response) {
                $return_data['code'] = self::ERROR_CODE_BAD_REQUEST['status'];
            }
        }else if($result < 0 && $payout_amount!=0){

            $this->utils->debug_log('JUMBO_SEAMLESS ', 'PAYOUT NOT ZERO');
            $uniqueid_of_seamless_service=$this->api->getPlatformCode().'-debit-'.$extra['external_unique_id'];
            $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $external_game_id);
            $response = $this->wallet_model->decSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $bet_amount);
            $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

            $uniqueid_of_seamless_service=$this->api->getPlatformCode().'-credit-'.$extra['external_unique_id'];
            $this->wallet_model->setUniqueidOfSeamlessService($uniqueid_of_seamless_service, $external_game_id);
            $this->wallet_model->setGameProviderRoundId($extra['round_id']);

            $response = $this->wallet_model->incSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $payout_amount, $after_balance);
            $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

            $this->utils->debug_log('Jumbo @adjustWalletBetNSettle -  incSubWallet response', $response);

            if(!$response) {
                $return_data['code'] = self::ERROR_CODE_BAD_REQUEST['status'];
                $this->utils->debug_log('Jumbo @adjustWalletBetNSettle -  incSubWallet failed, return code: ', $return_data['code']);
            }
        }else if($result < 0 && $payout_amount==0){
            $response = $this->wallet_model->decSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $bet_amount, $after_balance);
            $this->remote_wallet_status = $this->ssa_get_remote_wallet_error_code();

            $this->utils->debug_log('Jumbo @adjustWalletBetNSettle -  decSubWallet response', $response);
            if(!$response) {
                $return_data['code'] = self::ERROR_CODE_BAD_REQUEST['status'];
                $this->utils->debug_log('Jumbo @adjustWalletBetNSettle -  decSubWallet failed, return code: ', $return_data['code']);
            }
        }

        if(!$response){ #error on adjustment, check if unique id already process
            $this->utils->debug_log('JUMBO_SEAMLESS response: ', $response);
            if ($this->ssa_enabled_remote_wallet() && !empty($this->remote_wallet_status)) {
                if ($this->ssa_remote_wallet_error_double_unique_id()) {
                    $this->utils->debug_log('JUMBO_SEAMLESS double unique');
                    $return_data['code'] = self::RETURN_OK;
                }
            }
        }

        $wallet_transaction['amount'] = $result;
        $wallet_transaction['bet_amount'] = $bet_amount;
        $wallet_transaction['result_amount'] = $result;

        if($result <= 0){
            $transaction_type = self::TRANSACTION_DEBIT;
        }else{
            $transaction_type = self::TRANSACTION_CREDIT;
        }
        if(is_null($after_balance)){
            $after_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];
        }

        $return_data['after_balance'] = $after_balance;

        $start_datetime = DateTime::createFromFormat('d-m-Y H:i:s', $extra['start_at']);
        $format_start_datetime = $start_datetime->format('Y-m-d H:i:s');

        $end_datetime = DateTime::createFromFormat('d-m-Y H:i:s', $extra['end_at']);
        $format_end_datetime = $end_datetime->format('Y-m-d H:i:s');

        $wallet_transaction['transaction_id'] = $extra['transaction_id'];
        $wallet_transaction['external_unique_id'] = $extra['external_unique_id'];
        $wallet_transaction['game_seq_no'] = $extra['game_seq_no'];
        $wallet_transaction['round_id'] = $extra['round_id'];
        $wallet_transaction['player_id'] = $player_info['player_id'];
        $wallet_transaction['game_username'] = $player_info['game_username'];
        $wallet_transaction['game_type'] = $extra['game_type'];
        $wallet_transaction['game_id'] = $extra['game_id'];
        $wallet_transaction['report_date'] = $extra['report_date'];
        $wallet_transaction['start_at'] =  $this->api->gameTimeToServerTime($format_start_datetime);
        $wallet_transaction['end_at'] = $this->api->gameTimeToServerTime($format_end_datetime);
        $wallet_transaction['currency'] = $extra['currency'];
        $wallet_transaction['win_amount'] = $payout_amount;
        $wallet_transaction['net_win'] = $result;
        $wallet_transaction['denom'] = $extra['denom'];
        $wallet_transaction['client_type'] = $extra['client_type'];
        $wallet_transaction['system_take_win'] = $extra['system_take_win'];
        $wallet_transaction['jackpot_win'] = $extra['jackpot_win'];
        $wallet_transaction['jackpot_contribute'] = $extra['jackpot_contribute'];
        $wallet_transaction['has_free_game'] = $extra['has_free_game'];
        $wallet_transaction['has_gamble'] = $extra['has_gamble'];

        $wallet_transaction['game_platform_id'] = $this->api->getPlatformCode();
        $wallet_transaction['before_balance'] = $return_data['before_balance'];
        $wallet_transaction['after_balance'] = $return_data['after_balance'];

        $wallet_transaction['transaction_type'] = $transaction_type;
        $wallet_transaction['game_status'] = self::STATUS_SETTLED;

        $wallet_transaction['created_at'] = date("Y-m-d H:i:s");
        $wallet_transaction['updated_at'] = date("Y-m-d H:i:s");
        $wallet_transaction['historyId'] = isset($extra['historyId']) ? $extra['historyId'] : null;
        $wallet_transaction['extra_info'] = !empty($this->requestParams->params) ? json_encode($this->requestParams->params) : null;

        if (!empty($this->remote_wallet_status) && !empty($wallet_transaction)) {
            $this->save_remote_wallet_failed_transaction($this->ssa_insert, $wallet_transaction);
        }

        if($return_data['code'] == self::RETURN_OK) {
            $this->utils->debug_log('JUMBO_SEAMLESS ', 'TALLYNN_INSERT');
            $this->wallet_transaction_id = $this->jumbo_transactions->insertTransaction($wallet_transaction, $this->api->getTransactionsTable());
            
            $this->utils->debug_log("Jumbo @adjustWalletBetNSettle insertTransaction last query: ", $this->CI->db->last_query());
            $this->wallet_transaction = $wallet_transaction;
            if(!$this->wallet_transaction_id) {
                $this->utils->debug_log("Jumbo @adjustWalletBetNSettle failed to save transaction");
                $return_data['code'] = self::ERROR_CODE_BAD_REQUEST['status'];
            }
        }
        $this->utils->debug_log("JUMBO @adjustWalletBetNSettle final return_data value: ", $return_data);        
        return $return_data;
    }

    private function validateRequest($rule_set) {

        $is_valid = true;
        foreach($rule_set as $key => $rules) {
            $rules = explode("|", $rules);
            foreach($rules as $rule) {
                if(is_array($this->requestParams->params)){
                    if($rule == 'required' && !array_key_exists($key, $this->requestParams->params)) {
                        $is_valid = false;
                        $this->utils->debug_log('JUMBO ' . __METHOD__ , 'missing parameter', $key);
                        break;
                    }
                    if($rule == 'numeric' && !is_numeric($this->requestParams->params[$key])) {
                        $is_valid = false;

                        $this->utils->debug_log('JUMBO ' . __METHOD__ , 'not numeric', $key);
                        break;
                    }
                }else{
                    $is_valid = false;

                    $this->utils->debug_log('JUMBO ' . __METHOD__ , 'pass paramater is not an array', $key);
                    break;
                }
            }
            if(!$is_valid) {
                break;
            }
        }
        return $is_valid;
    }

    public function preProcessRequest($functionName="", $rule_set = []) {

        $params = $_POST;
        if(empty($params)){
            $params = json_decode(file_get_contents('php://input'), true);
        }

        $this->requestParams->function = $functionName ;
        $this->requestParams->params = $params;

        $this->utils->debug_log('TAL_JUMBO ' . __METHOD__ , "parameters", $this->requestParams->params);

        $is_valid = $this->validateRequest($rule_set);

        if(!$is_valid) {
            $this->utils->debug_log('TAL_JUMBO ' . __METHOD__ , ' x not valid');

            $data = [
                "status" => self::ERROR_CODE_PARAMETER_ERROR['status'],
                "err_text" => self::ERROR_CODE_PARAMETER_ERROR['err_text'],
            ];

            return $this->setOutput($data);
        }

    }

    private function setOutput($data = []) {
        $flag = $data['status'] == self::SUCCEED['status'] ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        $httpStatusCode = 200;
        $httpStatusText = "Success";

        if(isset($data['status']) && array_key_exists($data['status'], self::HTTP_STATUS_CODE_MAP)){
            $httpStatusCode = self::HTTP_STATUS_CODE_MAP[$data['status']];
            $httpStatusText = $data['err_text'];
        }

        $data = json_encode($data);

        $fields = array(
            'player_id' => isset($this->currentPlayer['player_id']) ? $this->currentPlayer['player_id'] : 0
        );

        if($this->api) {
            $response_result_id = $this->CI->response_result->saveResponseResult(
                $this->api->getPlatformCode(), #1
                $flag, #2
                $this->requestParams->function, #3
                json_encode($this->requestParams->params), #4
                $data, #5
                $httpStatusCode, #6
                $httpStatusText, #7
                json_encode($this->requestHeaders), #8
                $fields, #9
                false, #10
                null, #11
                intval($this->utils->getExecutionTimeToNow()*1000) #12
            );
        }

        $this->output->set_status_header($httpStatusCode);
        $this->output->set_content_type('application/json')->set_output($data);
        $this->output->_display();
        exit();
    }

    public function encryptX(){
        $params = file_get_contents("php://input");
        $sign = $this->api->encryptParams($params);
        $this->returnJsonResult($sign);
    }

    public function decryptX(){
        $params = file_get_contents("php://input");
        $sign = json_decode($params);
        $params = $this->api->decryptParams($sign->x);
        $this->returnJsonResult($params);
    }

    public function rollback(){
        $this->CI->load->model(['common_token', 'original_seamless_wallet_transactions']);
        $params = file_get_contents("php://input");
        $arr = json_decode($params);
        $this->requestParams->function = 'rollback' ;
        $this->requestParams->params = $params;

        if(isset($arr->currency)){
            $check_currency = $this->checkCurrency($arr->currency);
            if(!$check_currency){
                $data = [
                    "status" => self::ERROR_CODE_PARAMETER_ERROR['status'],
                    "balance" => 0.00,
                    "err_text" => self::ERROR_CODE_PARAMETER_ERROR['err_text']
                ];
                return $this->setOutput($data);
            }
        }

        $player = $this->getPlayer($arr->uid);

        if(!$player){
            $data = [
                "status" => self::ERROR_CODE_USER_ID_NOT_FOUND['status'],
                "err_text" => self::ERROR_CODE_USER_ID_NOT_FOUND['err_text']
            ];

            return $this->setOutput($data);
        }else{

            $controller = $this;
            $transaction_data = [];
            //Lock Balance
            $transaction_result = $this->lockAndTransForPlayerBalance($player['player_id'], function() use($controller, $player, &$transaction_data, $arr) {
                $transaction_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($arr->transferId, $this->api->getTransactionsTable());
                if(empty($transaction_id_exist)){
                    if($this->checkPreviousMonth && $this->api->use_monthly_transactions_table){
                        $transaction_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($arr->transferId, $this->api->getTransactionsPreviousTable());
                        if(empty($transaction_id_exist)){
                            $controller->resultCode = self::ERROR_CODE_DATA_NOT_EXIST;
                            return false;
                        }
                    } else {
                        $controller->resultCode = self::ERROR_CODE_DATA_NOT_EXIST;
                        return false;
                    }
                }

                $ref_transfer_ids = isset($arr->refTransferIds) ? $arr->refTransferIds : null;
                if(!empty($ref_transfer_ids) &&  count($ref_transfer_ids) > 1){
                    $controller->resultCode = self::ERROR_CODE_BAD_REQUEST;
                    return false;
                }
                $ref_transfer_id = current($ref_transfer_ids); #get first data only
                $ref_transfer_id = (string) $ref_transfer_id;
                $reference_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($ref_transfer_id, $this->api->getTransactionsTable());
                if(empty($reference_id_exist)){
                    if($this->checkPreviousMonth && $this->api->use_monthly_transactions_table){
                        $reference_id_exist = $this->jumbo_transactions->searchByExternalTransactionIdByTransactionType($ref_transfer_id, $this->api->getTransactionsPreviousTable());
                        if(empty($reference_id_exist)){
                            $controller->resultCode = self::ERROR_CODE_DATA_NOT_EXIST;
                            return false;
                        }
                    } else {
                        $controller->resultCode = self::ERROR_CODE_DATA_NOT_EXIST;
                        return false;
                    }
                }

                $transaction_type = self::TRANSACTION_DEBIT;
                $related_uniqueid_of_seamless_service = 'game-' . $this->api->getPlatformCode()."-{$transaction_type}-".$ref_transfer_id;
                if (method_exists($this->wallet_model, 'setRelatedUniqueidOfSeamlessService')) {
                    $this->wallet_model->setRelatedUniqueidOfSeamlessService($related_uniqueid_of_seamless_service);
                }


                if (method_exists($this->wallet_model, 'setRelatedActionOfSeamlessService')) {
                    $this->wallet_model->setRelatedActionOfSeamlessService(Wallet_model::REMOTE_RELATED_ACTION_BET);
                }

                $transaction_data = $controller->processPrepayBetGame(self::TRANSACTION_REFUND, $player, $arr);
                $this->utils->debug_log('JUMBO_SEAMLESS ', 'ADJUST_WALLET_SETTLE: ', $transaction_data);
                $success = isset($transaction_data['is_success']) ? $transaction_data['is_success'] : false;

                return $success;
            });
        }

        if(!isset($transaction_data['balance_after'])){
            $transaction_data['balance_after'] = $this->api->queryPlayerBalance($player['username'])['balance'];
        }

        if($transaction_result && isset($transaction_data['code']) && $transaction_data['code'] == self::RETURN_OK) {
            $data_err = [
                "status" => self::SUCCEED['status'],
                "balance" => $transaction_data['balance_after'],
                "err_text" => self::SUCCEED['err_text']
            ];

            return $this->setOutput($data_err);

        }else{
            $statusCode = "";

            switch ($controller->resultCode['status']) {
                case self::ERROR_CODE_PLAYER_BALANCE_ZERO['status']:
                    $statusCode = self::ERROR_CODE_PLAYER_BALANCE_ZERO;
                    break;
                case self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT['status']:
                    $statusCode = self::ERROR_CODE_PLAYER_BALANCE_INSUFFICIENT;
                    break;
                case self::ERROR_CODE_TRANSFER_ID_REPEATED['status']:
                    $statusCode = self::ERROR_CODE_TRANSFER_ID_REPEATED;
                break;
                case self::ERROR_CODE_PARAMETER_ERROR['status']:
                    $statusCode = self::ERROR_CODE_PARAMETER_ERROR;
                    break;
                case self::ERROR_CODE_DATA_NOT_EXIST['status']:
                    $statusCode = self::ERROR_CODE_DATA_NOT_EXIST;
                    break;
                default:
                    $statusCode = self::ERROR_CODE_FAILED;
                    break;
                }

            $data_err = [
                "status" => $statusCode['status'],
                "balance" => $transaction_data['balance_after'],
                "err_text" => $statusCode['err_text']
            ];

            if(!empty($controller->resultCode)){
                return $this->setOutput($data_err);
            }else{
                return $this->setOutput($data_err);
            }
        }
    }

    private function save_remote_wallet_failed_transaction($query_type, $data, $where = []) {
        $save_data = $md5_data = [
            'transaction_id' => !empty($data['transaction_id']) ? strval($data['transaction_id']) : null,
            'round_id' => !empty($data['round_id']) ? strval($data['round_id']) : null,
            'external_game_id' => !empty($data['game_id']) ? strval($data['game_id']) : null,
            'player_id' => !empty($data['player_id']) ? $data['player_id'] : null,
            'game_username' => !empty($data['game_username']) ? $data['game_username'] : null,
            'amount' => isset($data['amount']) ? $data['amount'] : null,
            'balance_adjustment_type' => !empty($data['transaction_type']) && $data['transaction_type'] == 'debit' ? $this->ssa_decrease : $this->ssa_increase,
            'action' => !empty($data['transaction_type']) ? $data['transaction_type'] : null,
            'game_platform_id' => !empty($data['game_platform_id']) ? $data['game_platform_id'] : null,
            'transaction_raw_data' => !empty($data['extra_info']) ? $data['extra_info'] : null,
            'remote_raw_data' => null,
            'remote_wallet_status' => $this->remote_wallet_status,
            'transaction_date' => !empty($data['report_date']) ? $data['report_date'] : $this->utils->getNowForMysql(),
            'request_id' => $this->utils->getRequestId(),
            'headers' => !empty($this->ssa_request_headers()) && is_array($this->ssa_request_headers()) ? json_encode($this->ssa_request_headers()) : null,
            'full_url' => $this->utils->paddingHostHttp($_SERVER['REQUEST_URI']),
            'external_uniqueid' => !empty($data['external_unique_id']) ? strval($data['external_unique_id']) : null,
        ];

        $save_data['md5_sum'] = md5(json_encode($md5_data));

        if (empty($save_data['external_uniqueid'])) {
            return false;
        }

        // check if exist
        if ($this->use_remote_wallet_failed_transaction_monthly_table) {
            $year_month = $this->utils->getThisYearMonth();
            $table_name = "{$this->ssa_failed_remote_common_seamless_transactions_table}_{$year_month}";
        } else {
            $table_name = $this->ssa_failed_remote_common_seamless_transactions_table;
        }

        if ($this->ssa_is_transaction_exists($table_name, ['external_uniqueid' => $save_data['external_uniqueid']])) {
            $query_type = $this->ssa_update;

            if (empty($where)) {
                $where = [
                    'external_uniqueid' => $save_data['external_uniqueid'],
                ];
            }
        }

        return $this->ssa_save_transaction_data($this->ssa_failed_remote_common_seamless_transactions_table, $query_type, $save_data, $where, $this->use_remote_wallet_failed_transaction_monthly_table);
    }
}
