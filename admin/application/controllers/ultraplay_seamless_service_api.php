<?php

/**
 * Ultraplay Iframe Betting Seamless API with External Login
 * 
 **/
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/modules/seamless_service_api_module.php';

class Ultraplay_seamless_service_api extends BaseController
{
    use Seamless_service_api_module;

    #default params
    private $currency;
    private $language;
    private $game_platform_id;
    private $game_api;
    private $api_method_type;

    private $params;
    private $player_details;
    private $player_balance;
    private $before_balance;
    private $after_balance;
    private $bet_amount;
    private $win_amount;
    private $deduction_amount;
    private $additional_amount;
    private $processed_amount;
    private $total_win_amount;

    private $conversion;
    private $precision;
    private $arithmetic_name;
    private $adjustment_precision;
    private $adjustment_conversion;
    private $adjustment_arithmetic_name;
    private $game_provider_gmt = '+0 hours';
    private $game_provider_date_time_format = 'Y-m-d H:i:s';

    private $transaction_type;
    private $game_logs_status;
    private $transaction_table;
    private $external_unique_id;
    private $seamless_service_unique_id;
    private $headers;
    private $ssa_adjustment_operation;

    const SEAMLESS_GAME_API = ULTRAPLAY_SEAMLESS_GAME_API; 
    
    #allowed functions
    const ALLOWED_API_METHODS_TEST = 'test';
    const ALLOWED_API_METHODS_PING = 'ping';
    const ALLOWED_API_METHODS_TOKEN_LOGIN = 'TokenLogin';
    const ALLOWED_API_METHODS_GET_BALANCE = 'GetBalance';
    const ALLOWED_API_METHODS_PLACE_BET = 'PlaceBet';
    const ALLOWED_API_METHODS_ACCEPT_BET = 'AcceptBet';
    const ALLOWED_API_METHODS_DECLINE_BET = 'DeclineBet';
    const ALLOWED_API_METHODS_SETTLE_BET = 'SettleBet';
    const ALLOWED_API_METHODS_UNSETTLE_BET = 'UnsettleBet';

    #seamless transaction types
    const SEAMLESS_TRANSACTION_TYPE_PLACE_BET = 'PlaceBet';
    const SEAMLESS_TRANSACTION_TYPE_ACCEPT_BET = 'AcceptBet';
    const SEAMLESS_TRANSACTION_TYPE_DECLINE_BET = 'DeclineBet';
    const SEAMLESS_TRANSACTION_TYPE_SETTLE_BET = 'SettleBet';
    const SEAMLESS_TRANSACTION_TYPE_UNSETTLE_BET = 'UnsettleBet';

    #Ultraplay params
    const DEBUG_KEY = 'ULTRAPLAY_SEAMLESS: ';
    const STATUS_SUCCESS = 'OK';
    const STATUS_ERROR = 'ERROR';

    const BET_TYPE_SINGLE = 'Single';
    const BET_TYPE_COMBO = 'Combo';
    const BET_TYPE_SYSTEM = 'System';
    const BET_TYPE_IF_BET = 'Ifbet';
    const BET_TYPE_REVERSE_BET = 'Reversebet';
    const BET_TYPE_TEASER = 'Teaser';

    const BET_STATUS_PENDING = 'Pending';
    const BET_STATUS_CANCELLED = 'Cancelled';
    const BET_STATUS_WIN = 'Win';
    const BET_STATUS_LOST = 'Lost';
    const BET_STATUS_HALF_WIN = 'HalfWin';
    const BET_STATUS_HALF_LOSE = 'HalfLose';
    const BET_STATUS_REFUND = 'Refund';


    const ALLOWED_API_METHODS = [
        self::ALLOWED_API_METHODS_TEST,
        self::ALLOWED_API_METHODS_PING,
        self::ALLOWED_API_METHODS_TOKEN_LOGIN,
        self::ALLOWED_API_METHODS_GET_BALANCE,
        self::ALLOWED_API_METHODS_PLACE_BET,
        self::ALLOWED_API_METHODS_ACCEPT_BET,
        self::ALLOWED_API_METHODS_DECLINE_BET,
        self::ALLOWED_API_METHODS_SETTLE_BET,
        self::ALLOWED_API_METHODS_UNSETTLE_BET,
    ];

    const ALLOWED_BET_TYPES = [
        self::BET_TYPE_SINGLE,
        self::BET_TYPE_COMBO,
        self::BET_TYPE_SYSTEM,
        self::BET_TYPE_IF_BET,
        self::BET_TYPE_REVERSE_BET,
        self::BET_TYPE_TEASER,
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ssa_init();
        $this->game_platform_id = self::SEAMLESS_GAME_API;
        $this->game_api = $this->ssa_load_game_api_class($this->game_platform_id);
        $this->retrieveHeaders();

        $this->player_balance = 0;
        $this->game_platform_id = $this->game_api->getPlatformCode();
        $this->transaction_table = $this->game_api->original_seamless_wallet_transactions_table;
        $this->currency = $this->game_api->currency;
        $this->language = $this->game_api->language;
        $this->precision = $this->game_api->precision;
        $this->conversion = $this->game_api->conversion;
        $this->arithmetic_name = $this->game_api->arithmetic_name;
        $this->adjustment_precision = $this->game_api->adjustment_precision;
        $this->adjustment_conversion = $this->game_api->adjustment_conversion;
        $this->adjustment_arithmetic_name = $this->game_api->adjustment_arithmetic_name;
        $this->game_provider_gmt = $this->game_api->game_provider_gmt;
        $this->game_provider_date_time_format = $this->game_api->game_provider_date_time_format;
    }

    private function ping()
    {
        $response = array(
            "TimeStamp" => $this->utils->getTimestampNow(), 
            "Status" => self::STATUS_SUCCESS,
        );

        return $this->rebuildResponsefromArrayToJSON($response, null);
    }

    public function index($api, $api_method_type)
    {
        $this->api_method_type = $api_method_type;
        $this->params = $this->ssa_request_params;

        #validate game api platform
        if ($api != $this->game_platform_id) {
            return $this->responseWithErrorMessage('API ID is incorrect');
        }

        #check if game api is under maintenance, only allow settle
        if($api_method_type != self::ALLOWED_API_METHODS_SETTLE_BET){
            $this->CI->load->model('external_system');
            $isGameApiMaintenance = $this->CI->external_system->isGameApiMaintenance(self::SEAMLESS_GAME_API);
            if ($isGameApiMaintenance){
                return $this->responseWithErrorMessage('Operator is under Maintenance');
            }
        }

        #check if IP address is allowed
        if(!$this->game_api->validateWhiteIP()){
            return $this->responseWithErrorMessage('IP is not allowed: '. $this->input->ip_address());
        }

        #check if method is allowed
        if (in_array($api_method_type, self::ALLOWED_API_METHODS)) {
            return $this->$api_method_type();
        } else {
            return $this->responseWithErrorMessage('API METHOD is incorrect');
        }
    }

    public function TokenLogin(){
        #validate API version
        #api version will only be validated on token login; other methods will not be validated
        $params_api_version = !empty($this->params['ApiVersion']) ? $this->params['ApiVersion'] : null;
        if (!($this->game_api->api_version === $params_api_version)){
            return $this->responseWithErrorMessage('API Version is incorrect');
        }

        #validate token
        $params_token = !empty($this->params['Token']) ? $this->params['Token'] : null;
        $game_username = $this->validateTokenThenGetUserName($params_token);

        if ($game_username) {
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $game_username, $this->game_platform_id);
            $player_balance = 0;
            if (!empty($this->player_details['player_id'])) {
                $player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);

                $response = array(
                    "AccountID" => $game_username,
                    "Balance" => $player_balance,
                    "Currency" => $this->game_api->currency,
                    "Status" => self::STATUS_SUCCESS,
                    "BalanceMode"  => $this->game_api->balance_mode,
                    "StakeFactor"  => $this->game_api->stake_factor,
                    
                    #not implemented
                    // ApplyFreeHalfPointBonus
                    // FreeHalfPointStartDate
                    // FreeHalfPointEndDate
                    // FreeHalfPointMarketLimit
                );

                $response_result_additional_message = 'Valid token';
                $this->utils->debug_log(self::DEBUG_KEY . $response_result_additional_message);
                return $this->rebuildResponsefromArrayToJSON($response, $response_result_additional_message);
            }
        }
        
        return $this->responseWithErrorMessage('Invalid Token');
    }

    public function GetBalance()
    {
        #validate token
        $params_token = !empty($this->params['Token']) ? $this->params['Token'] : null;
        $game_username = $this->validateTokenThenGetUserName($params_token);

        if ($game_username) {
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $game_username, $this->game_platform_id);
            $player_balance = 0;
            if (!empty($this->player_details['player_id'])) {
                $player_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);
                $response = array(
                    "Balance" => $player_balance,
                    "Status" => "OK",
                );

                $response_result_additional_message = 'Balance: '. $player_balance;
                $this->utils->debug_log(self::DEBUG_KEY . $response_result_additional_message);
                return $this->rebuildResponsefromArrayToJSON($response, $response_result_additional_message);
            }
        }
        
        return $this->responseWithErrorMessage('Invalid Token');
    }



    public function PlaceBet()
    {
        #validate parameters
        $is_valid = $this->ssa_validate_request_params($this->params, [
            'RequestID'                         => ['required', 'string', 'maximum_size:100'],
            'ApiVersion'                        => ['required', 'string', 'maximum_size:5'],
            'Token'                             => ['required', 'string', 'maximum_size:250'],
            'IsAccepted'                        => ['required', 'boolean'],
            'Stake'                             => ['required', 'numeric'],
            'TakenAmount'                       => ['optional', 'numeric'],
            'TicketID'                          => ['required', 'integer'],
            'GroupTicketID'                     => ['required', 'integer'],
            'Odds'                              => ['required', 'numeric'],
            'OddsFormat'                        => ['required', 'string'],
            'FormattedOdds'                     => ['required', 'numeric'],
            'IfbetActionConditionType'          => ['nullable', 'integer'],
            'BetType'                           => ['required', 'string'],
            'DeviceType'                        => ['required', 'string'],
            'Selections'                        => ['required', 'string'],
            'SelectionsDetails'                 => ['required', 'multidimensional_array'],
        ]);

        if (!$is_valid || $this->params['TakenAmount'] < 0) {
            return $this->responseWithErrorMessage('Invalid Parameter');
        }

        #check if bet type is allowed
        if (!in_array($this->params['BetType'], $this->game_api->allowed_bet_types)) {
            return $this->responseWithErrorMessage('Bet Type is not allowed: '. $this->params['BetType']);
        }

        #validate token
        $game_username = $this->validateTokenThenGetUserName($this->params['Token']);

        if ($game_username) {
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $game_username, $this->game_platform_id);
            $this->before_balance = 0;

            #check if player exists
            if (!empty($this->player_details['player_id'])){

                #get player before balance and check against TakenAmount
                $this->before_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);

                #check if transaction exists
                $this->seamless_service_unique_id = $this->utils->mergeArrayValues([$this->game_platform_id, $this->api_method_type, $this->params['TicketID']]);
                $is_transaction_exist = $this->ssa_is_transaction_exists($this->transaction_table, ['seamless_service_unique_id' => $this->seamless_service_unique_id]);
                if ($is_transaction_exist) {
                    $response_result_additional_message = 'Request already exists';
                    $response = array(
                        "ErrorDescription" => $response_result_additional_message,
                        "Balance" => $this->before_balance,
                        "Status" => "OK",
                    );
                    $this->utils->debug_log(self::DEBUG_KEY . $response_result_additional_message);
                    return $this->rebuildResponsefromArrayToJSON($response, $response_result_additional_message);
                }

                if ($this->before_balance < $this->params['TakenAmount'] || $this->params['TakenAmount'] < 0) {
                    return $this->responseWithErrorMessage('Insufficient Balance: '. $this->before_balance);
                }

                #adjust wallet
                $controller = $this;
                $this->game_logs_status = Game_logs::STATUS_PENDING;
                $this->transaction_type = self::SEAMLESS_TRANSACTION_TYPE_PLACE_BET;

                if ($this->params['IsAccepted']) {
                    $this->game_logs_status = Game_logs::STATUS_ACCEPTED;
                }

                $game_code = !empty($this->params['SelectionsDetails'][0]['Sport']['Name']) ? $this->params['SelectionsDetails'][0]['Sport']['Name'] : null;
                $this->wallet_model->setExternalGameId($game_code);
                $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_BET);
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use ($controller) {
                    return $controller->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $this->params['TakenAmount']);
                });

                #exit on error walletAdjustment
                if (!$success) {
                    return $this->responseWithErrorMessage('Adjustment Failed: ' . $this->params['RequestID']);
                }

                $response = array(
                    "Balance" => $this->after_balance,
                    "Status" => "OK",
                );

                $response_result_additional_message = 'Balance: '. $this->after_balance;
                $this->utils->debug_log(self::DEBUG_KEY . $response_result_additional_message);
                return $this->rebuildResponsefromArrayToJSON($response, $response_result_additional_message);
            }
        }

        $this->responseWithErrorMessage('Invalid Token');
    }



    public function AcceptBet(){
        #validate parameters
        $is_valid = $this->ssa_validate_request_params($this->params, [
            'RequestID'                         => ['required', 'string', 'maximum_size:100'],
            'ApiVersion'                        => ['required', 'string', 'maximum_size:5'],
            'Token'                             => ['required', 'string', 'maximum_size:250'],
            'TicketID'                          => ['required', 'integer'],
            'Stake'                             => ['required', 'numeric'],
            'OddInfos'                          => ['required', 'multidimensional_array'],
        ]);

        if (!$is_valid) {
            $this->responseWithErrorMessage('Invalid Parameter');
        }

        #validate token
        $game_username = $this->validateTokenThenGetUserName($this->params['Token']);

        if ($game_username) {
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $game_username, $this->game_platform_id);
            $this->before_balance = 0;

            #check if player exists
            if (!empty($this->player_details['player_id'])){
                #then get balance
                $this->before_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);

                #check if transaction exists
                $this->seamless_service_unique_id = $this->utils->mergeArrayValues([$this->game_platform_id, $this->api_method_type, $this->params['TicketID']]);
                $is_transaction_exist = $this->ssa_is_transaction_exists($this->transaction_table, ['seamless_service_unique_id' => $this->seamless_service_unique_id]);
                if ($is_transaction_exist) {
                    $response_result_additional_message = 'Request already exists';
                    $response = array(
                        "ErrorDescription" => $response_result_additional_message,
                        "Balance" => $this->before_balance,
                        "Status" => "OK",
                    );
                    $this->utils->debug_log(self::DEBUG_KEY . $response_result_additional_message);
                    return $this->rebuildResponsefromArrayToJSON($response, $response_result_additional_message);
                }

                #get transaction info if bet exists
                $bet_transaction = $this->ssa_get_transaction($this->transaction_table, 
                    ['Token' => $this->params['Token'],
                    'round_id' => $this->params['TicketID'],
                    'transaction_type' => self::SEAMLESS_TRANSACTION_TYPE_PLACE_BET,
                    'isAccepted' => false,
                    'status' => Game_logs::STATUS_PENDING,],
                    ['id', 'extra_info', 'bet_odd_info']
                );

                if (!$bet_transaction) {
                    return $this->responseWithErrorMessage('Bet not found.');
                }

                #check if stake changed.
                $bet_transaction['extra_info'] = json_decode($bet_transaction['extra_info'], true);
                $bet_transaction['bet_odd_info'] = json_decode($bet_transaction['bet_odd_info'], true);

                #according to GP, a new placebet will be called and the old will be declined. only odds can change.
                if ($this->params['Stake'] != $bet_transaction['extra_info']['Stake']) {              
                    return $this->responseWithErrorMessage('Stake mistach.');
                }

                $new_calculated_odd = 1;
                #check and update odds
                foreach ($bet_transaction['bet_odd_info'] as $key => $bet_odd_info) {
                    foreach ($this->params['OddInfos'] as $odd_info) {
                        if ($bet_odd_info['Odd_Selection_ID'] == $odd_info['ID']) {
                            $bet_transaction['bet_odd_info'][$key]['Odd_Value'] = $odd_info['Value'];
                            $bet_transaction['bet_odd_info'][$key]['Odd_FormattedValue'] = $odd_info['FormattedValue'];
                            $new_calculated_odd *= $odd_info['FormattedValue'];
                            break;
                        }
                    }
                }

                $bet_transaction['extra_info']['Odds'] = $this->truncateAmountToPrecision($new_calculated_odd, 2);

                #set bet detail IsAccepted to true and update odds
                $data = [
                    'IsAccepted' => true,
                    'bet_odd_info' => json_encode($bet_transaction['bet_odd_info']),
                    'extra_info' => json_encode($bet_transaction['extra_info']),
                    'status' => Game_logs::STATUS_ACCEPTED,
                ];
                $this->ssa_update_transaction_without_result($this->transaction_table, $data, 'id', $bet_transaction['id']);
                
                #add new transaction record for accept bet.
                $transaction_data = [
                    'amount' => $this->params['Stake'],
                    'before_balance' => $this->before_balance,
                    'after_balance' => $this->before_balance,
                ];

                $this->transaction_type = self::SEAMLESS_TRANSACTION_TYPE_ACCEPT_BET;
                $this->prepareTransactionRequestData($transaction_data);

                $response = array(
                    "Balance" => $this->before_balance,
                    "Status" => "OK",
                );

                $response_result_additional_message = 'Balance: '. $this->before_balance;
                $this->utils->debug_log(self::DEBUG_KEY . $response_result_additional_message);
                return $this->rebuildResponsefromArrayToJSON($response, $response_result_additional_message);
            }
        }

        $this->responseWithErrorMessage('Invalid Token');
    }



    public function DeclineBet(){
        #validate parameters
        $is_valid = $this->ssa_validate_request_params($this->params, [
            'RequestID'                         => ['required', 'string', 'maximum_size:100'],
            'ApiVersion'                        => ['required', 'string', 'maximum_size:5'],
            'Token'                             => ['required', 'string', 'maximum_size:250'],
            'TicketID'                          => ['required', 'integer'],
        ]);

        if (!$is_valid) {
            $this->responseWithErrorMessage('Invalid Parameter');
        }

        #validate token
        $game_username = $this->validateTokenThenGetUserName($this->params['Token']);

        if ($game_username) {
            $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $game_username, $this->game_platform_id);
            $this->before_balance = 0;

            #check if player exists
            if (!empty($this->player_details['player_id'])){
                #then get balance
                $this->before_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);

                #check if transaction exists
                $this->seamless_service_unique_id = $this->utils->mergeArrayValues([$this->game_platform_id, $this->api_method_type, $this->params['TicketID']]);
                $is_transaction_exist = $this->ssa_is_transaction_exists($this->transaction_table, ['seamless_service_unique_id' => $this->seamless_service_unique_id]);
                if ($is_transaction_exist) {
                    $response_result_additional_message = 'Request already exists';
                    $response = array(
                        "ErrorDescription" => $response_result_additional_message,
                        "Balance" => $this->before_balance,
                        "Status" => "OK",
                    );
                    $this->utils->debug_log(self::DEBUG_KEY . $response_result_additional_message);
                    return $this->rebuildResponsefromArrayToJSON($response, $response_result_additional_message);
                }

                #get transaction info if bet exists
                $bet_transaction = $this->ssa_get_transaction($this->transaction_table, 
                    ['Token' => $this->params['Token'],
                    'round_id' => $this->params['TicketID'], 
                    'transaction_type' => self::SEAMLESS_TRANSACTION_TYPE_PLACE_BET, 
                    'isAccepted' => false,
                    'status' => Game_logs::STATUS_PENDING,],
                    ['id', 'amount', 'game_code']
                );

                if (!$bet_transaction) {
                    return $this->responseWithErrorMessage('Bet not found.');
                }

                #process refund
                $controller = $this;
                $this->transaction_type = self::SEAMLESS_TRANSACTION_TYPE_DECLINE_BET;
                $this->deduction_amount = abs($bet_transaction['amount']);

                $this->wallet_model->setExternalGameId($bet_transaction['game_code']);
                $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND);
                $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use ($controller) {
                    return $controller->walletAdjustment($this->ssa_increase, $this->ssa_insert, $this->deduction_amount);
                });
                
                #exit on error walletAdjustment
                if (!$success) {
                    return $this->responseWithErrorMessage('Adjustment Failed: ' . $this->params['RequestID']);
                }

                #set bet detail status to rejected
                $data = [
                    'status' => Game_logs::STATUS_REJECTED,
                ];

                $this->ssa_update_transaction_without_result($this->transaction_table, $data, 'id', $bet_transaction['id']);
                
                $response = array(
                    "Balance" => $this->after_balance,
                    "Status" => "OK",
                );

                $response_result_additional_message = 'Balance: '. $this->after_balance;
                $this->utils->debug_log(self::DEBUG_KEY . $response_result_additional_message);
                return $this->rebuildResponsefromArrayToJSON($response, $response_result_additional_message);
            }
        }

        $this->responseWithErrorMessage('Invalid Token');
    }



    #Note: Token during placebet vs latest token might be different.
    public function SettleBet(){
        #validate parameters
        $is_valid = $this->ssa_validate_request_params($this->params, [
            'RequestID'                         => ['required', 'string', 'maximum_size:100'],
            'ApiVersion'                        => ['required', 'string', 'maximum_size:5'],
            'Token'                             => ['required', 'string', 'maximum_size:250'],
            'TicketID'                          => ['required', 'integer'],
            'BalanceMode'                       => ['required', 'string'],
            'Payout'                            => ['optional', 'numeric'],
            'PayoutStatus'                      => ['optional', 'integer'],
            'CommitDate'                        => ['required', 'string'],
            'Status'                            => ['required', 'string'],
            'SelectionsStatus'                  => ['required', 'multidimensional_array'],
            'EventsInfo'                        => ['required', 'multidimensional_array'],
            'SelectionsPlayerChanges'           => ['optional', 'array'],
        ]);

        #validate PayoutStatus, Payout Amount
        if (!$is_valid || !in_array($this->params['PayoutStatus'], [0, 1])) {
            return $this->responseWithErrorMessage('Invalid Parameter');
        }

        #get bet information.
        $bet_transaction = $this->ssa_get_transaction($this->transaction_table,
            [
                'round_id' => $this->params['TicketID'],
                'transaction_type' => self::SEAMLESS_TRANSACTION_TYPE_PLACE_BET,
                'isAccepted' => true,
                // 'status' => Game_logs::STATUS_ACCEPTED,
            ],
            ['id', 'token', 'game_username', 'win_amount', 'bet_odd_info', 'extra_info', 'amount', 'BetType', 'GroupTicketID', 'IfbetActionConditionType', 'game_code']
        );

        #compare placebet token to the settlebet token. should be the same.
        if ($bet_transaction['token'] !== $this->params['Token']) {
            return $this->responseWithErrorMessage('Ticket ID not found');
        }

        $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $bet_transaction['game_username'], $this->game_platform_id);
        $this->before_balance = 0;

        #check if player exists
        if (!empty($this->player_details['player_id'])) {
            #get balance
            $this->before_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);

            #validate if transaction exists. respond with balance if exists.
            $this->seamless_service_unique_id = $this->utils->mergeArrayValues([$this->game_platform_id, $this->api_method_type, $this->params['TicketID'], $this->params['RequestID']]);
            $is_transaction_exist = $this->ssa_is_transaction_exists($this->transaction_table, ['seamless_service_unique_id' => $this->seamless_service_unique_id]);
            if ($is_transaction_exist) {
                $response_result_additional_message = 'Request already exists';
                $response = array(
                    "ErrorDescription" => $response_result_additional_message,
                    "Balance" => $this->before_balance,
                    "Status" => "OK",
                );
                $this->utils->debug_log(self::DEBUG_KEY . $response_result_additional_message);
                return $this->rebuildResponsefromArrayToJSON($response, $response_result_additional_message);
            }

            #check if balance mode is Normal
            if ($this->params['BalanceMode'] !== 'Normal') {
                return $this->responseWithErrorMessage('Balance Mode: '. $this->params['BalanceMode']);
            }

            #initialize variables
            $this->ssa_adjustment_operation = $this->ssa_increase;
            $bet_transaction['bet_odd_info'] = json_decode($bet_transaction['bet_odd_info'], true);
            $bet_transaction['extra_info'] = json_decode($bet_transaction['extra_info'], true);
            $invalid_selection_count = 0;
            $bet_amount = 0;
            $settle_bet_status = $this->params['Status'];
            $this->total_win_amount = 0;

            switch ($bet_transaction['BetType']) {
                case self::BET_TYPE_SINGLE:
                    #initialize single bet
                    $game_log_status = Game_logs::STATUS_SETTLED;

                    #iterate through selections though there should be only 1 selection
                    foreach ($bet_transaction['bet_odd_info'] as $key => $bet_odd_info) {
                        foreach ($this->params['SelectionsStatus'] as $selection_status) {
                            #if selection is a match, update status and score
                            if ($bet_odd_info['Odd_Selection_ID'] == $selection_status['ID']) {
                                
                                #check if there is a change in status
                                if ($bet_odd_info['Selection_Status'] != $selection_status['Status'] ) {
                                    #check if the status is a win, then compute win amount.
                                    if($selection_status['Status'] == self::BET_STATUS_WIN ||
                                        $selection_status['Status'] == self::BET_STATUS_LOST ||
                                        $selection_status['Status'] == self::BET_STATUS_HALF_WIN ||
                                        $selection_status['Status'] == self::BET_STATUS_HALF_LOSE){

                                        $this->additional_amount = $this->params['Payout'];
                                    }

                                    #check if the status is a refund, then return the bet to player.
                                    elseif($selection_status['Status'] == self::BET_STATUS_REFUND) {
                                        $this->additional_amount = abs($bet_transaction['amount']);
                                        $bet_transaction['bet_odd_info'][$key]['Refund_Amount'] = $this->additional_amount;
                                        $game_log_status = Game_logs::STATUS_REFUND;
                                        $invalid_selection_count++;
                                    }

                                    #check if the status is a cancelled, then return the bet to player.
                                    elseif($selection_status['Status'] == self::BET_STATUS_CANCELLED) {
                                        $this->additional_amount = abs($bet_transaction['amount']); //most likely same with payout params.
                                        $bet_transaction['bet_odd_info'][$key]['Refund_Amount'] = $this->additional_amount;
                                        $game_log_status = Game_logs::STATUS_CANCELLED;
                                        $invalid_selection_count++;
                                    }
                                    
                                    else{
                                        return $this->responseWithErrorMessage('Unknown Selection Status');
                                    }

                                    #update bet_odd_info level
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Status'] = $selection_status['Status'];
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Score'] = $selection_status['Score'];
                                    break;
                                }
                            }
                        }

                        #update events info
                        foreach ($this->params['EventsInfo'] as $event_info) {
                            if ($bet_odd_info['Event_ID'] == $event_info['ID']) {
                                $bet_transaction['bet_odd_info'][$key]['Event_StartDate'] = $event_info['StartTime'];
                                break;
                            }
                        }
                    }

                    #validate winnings, calculate total win and additional amount
                    $this->total_win_amount = $this->additional_amount;

                    break;

                #Game Mechanics: Combo bet is a combination of single bets. If one selection is lost, the whole combo bet is lost.
                case self::BET_TYPE_COMBO:
                    #initialize combo bet
                    $is_lost_selection_exist = false;
                    $refund_selection_count = 0;
                    $pending_selection_count = 0;
                    $this->additional_amount = 0;
                    $this->total_win_amount = 0;

                    #iterate through selections
                    foreach ($bet_transaction['bet_odd_info'] as $key => $bet_odd_info) {
                        foreach ($this->params['SelectionsStatus'] as $selection_status) {
                            #if selection is a match, update status and score
                            if ($bet_odd_info['Odd_Selection_ID'] == $selection_status['ID']) {

                                #check if the status is a win, then compute win amount.
                                if( $selection_status['Status'] == self::BET_STATUS_WIN ||
                                    $selection_status['Status'] == self::BET_STATUS_HALF_WIN ||
                                    $selection_status['Status'] == self::BET_STATUS_HALF_LOSE){

                                }
                                
                                #count pending selection status
                                elseif($selection_status['Status'] == self::BET_STATUS_PENDING){
                                    $pending_selection_count++;
                                }

                                #check lost selection status
                                elseif($selection_status['Status'] == self::BET_STATUS_LOST) {
                                    $is_lost_selection_exist = true;
                                }

                                #check if the status is a refund/cancelled, then return the bet to player.
                                elseif($selection_status['Status'] == self::BET_STATUS_REFUND || $selection_status['Status'] == self::BET_STATUS_CANCELLED) {
                                    $refund_amount_per_selection = $bet_transaction['amount'] / $bet_transaction['extra_info']['Selection_Count'];
                                    // $this->additional_amount += $refund_amount_per_selection; //refund is already included on the Payout.
                                    $bet_transaction['bet_odd_info'][$key]['Refund_Amount'] = $refund_amount_per_selection;
                                    $refund_selection_count++;

                                    if($bet_odd_info['Selection_Status'] == self::BET_STATUS_PENDING){
                                        $invalid_selection_count++;
                                    }
                                }

                                else{
                                    return $this->responseWithErrorMessage('Unknown Selection Status');
                                }

                                #update bet_odd_info level
                                $bet_transaction['bet_odd_info'][$key]['Selection_Status'] = $selection_status['Status'];
                                $bet_transaction['bet_odd_info'][$key]['Selection_Score'] = $selection_status['Score'];
                                break;
                            }
                        }

                        #update events info
                        foreach ($this->params['EventsInfo'] as $event_info) {
                            if ($bet_odd_info['Event_ID'] == $event_info['ID']) {
                                $bet_transaction['bet_odd_info'][$key]['Event_StartDate'] = $event_info['StartTime'];
                                break;
                            }
                        }

                    }

                    #settle balance if all selection is settled.
                    if($pending_selection_count == 0){
                        $game_log_status = Game_logs::STATUS_SETTLED;
                        if ($is_lost_selection_exist) {
                            $this->additional_amount = 0;
                        }
                        #check if all is refunded/cancelled
                        elseif($bet_transaction['extra_info']['Selection_Count'] == $refund_selection_count) {
                            $game_log_status = Game_logs::STATUS_REFUND;
                            $this->additional_amount = abs($bet_transaction['amount']);
                        }
                        else{
                            $this->additional_amount += $this->params['Payout'];
                        }

                    }else{
                        $this->additional_amount = 0;
                        $game_log_status = Game_logs::STATUS_ACCEPTED;
                    }

                    #calculate total win and additional amount
                    $this->total_win_amount = $this->additional_amount;
                    break;

                #Game Mechanics: System bet is a combination of n selection. Possible to win on each combination.
                case self::BET_TYPE_SYSTEM:
                    #initialize system bet
                    // $system_header_count = 0;

                    #iterate through selection
                    foreach ($bet_transaction['bet_odd_info'] as $key => $bet_odd_info) {
                        foreach ($this->params['SelectionsStatus'] as $selection_status) {
                            if ($bet_odd_info['Odd_Selection_ID'] == $selection_status['ID']){
                                #update if there is a change in status
                                if ($bet_odd_info['Selection_Status'] != $selection_status['Status'] ){
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Status'] = $selection_status['Status'];
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Score'] = $selection_status['Score'];
                                }

                                // #check max system header
                                // $system_header_count = max($system_header_count, $bet_odd_info['SystemHeaderID']);
                                break;
                            }
                        }
                    }

                    #validate winnings, calculate total win and additional amount
                    if ($bet_transaction['win_amount'] > $this->params['Payout']) {
                        return $this->responseWithErrorMessage('Invalid Payout Amount');
                    }
                    $this->additional_amount = $this->params['Payout'] - $bet_transaction['win_amount'];
                    $this->total_win_amount = $this->params['Payout'];

                    #check if all selection is settled.
                    $game_log_status = Game_logs::STATUS_ACCEPTED;
                    if ($this->params['PayoutStatus'] == 0) {
                        $game_log_status = Game_logs::STATUS_SETTLED;
                    }

                    break;
                case self::BET_TYPE_IF_BET:
                    #initialize if bet
                    $game_log_status = Game_logs::STATUS_SETTLED;

                    //refactor this. use only 1 and avoid foreach.

                    #iterate through selections though there should be only 1 selection
                    foreach ($bet_transaction['bet_odd_info'] as $key => $bet_odd_info) {
                        foreach ($this->params['SelectionsStatus'] as $selection_status) {
                            #if selection is a match, update status and score
                            if ($bet_odd_info['Odd_Selection_ID'] == $selection_status['ID']) {
                                
                                #check if there is a change in status
                                if ($bet_odd_info['Selection_Status'] != $selection_status['Status'] ) {
                                    if($selection_status['Status'] == self::BET_STATUS_WIN ||
                                        $selection_status['Status'] == self::BET_STATUS_LOST ||
                                        $selection_status['Status'] == self::BET_STATUS_HALF_WIN ||
                                        $selection_status['Status'] == self::BET_STATUS_HALF_LOSE){

                                        $this->additional_amount = $this->params['Payout'];
                                    }

                                    #check if the status is a refund, then return the bet to player.
                                    elseif($selection_status['Status'] == self::BET_STATUS_REFUND) {
                                        $this->additional_amount = $bet_transaction['amount'];
                                        $bet_transaction['bet_odd_info'][$key]['Refund_Amount'] = $this->additional_amount;
                                        $game_log_status = Game_logs::STATUS_REFUND;
                                        $invalid_selection_count++;
                                    }

                                    #check if the status is a cancelled, then return the bet to player.
                                    elseif($selection_status['Status'] == self::BET_STATUS_CANCELLED) {
                                        $this->additional_amount = $bet_transaction['amount'];
                                        $bet_transaction['bet_odd_info'][$key]['Refund_Amount'] = $this->additional_amount;
                                        $game_log_status = Game_logs::STATUS_CANCELLED;
                                        $invalid_selection_count++;
                                    }

                                    else{
                                        return $this->responseWithErrorMessage('Unknown Selection Status');
                                    }

                                    #update bet_odd_info level
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Status'] = $selection_status['Status'];
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Score'] = $selection_status['Score'];

                                }
                            }
                        }

                        #update events info
                        foreach ($this->params['EventsInfo'] as $event_info) {
                            if ($bet_odd_info['Event_ID'] == $event_info['ID']) {
                                $bet_transaction['bet_odd_info'][$key]['Event_StartDate'] = $event_info['StartTime'];
                                break;
                            }
                        }
                    }

                    #get bet information based on GroupTicketID
                    $bet_transactions = $this->ssa_get_transactions($this->transaction_table,
                    [
                        'GroupTicketID' => $bet_transaction['GroupTicketID'],
                        'transaction_type' => self::SEAMLESS_TRANSACTION_TYPE_PLACE_BET,
                    ],
                    ['id', 'token', 'game_username', 'round_id', 'amount', 'status', 'extra_info', 'game_code']
                    );

                    #sort related group tickets by round id ascending
                    usort($bet_transactions, array($this, 'customSortByRoundIDAscending'));

                    foreach ($bet_transactions as $key => $related_bet_transaction) {
                        #skip bet if settled.
                        if ($related_bet_transaction['status'] == Game_logs::STATUS_SETTLED || 
                        $related_bet_transaction['status'] == Game_logs::STATUS_CANCELLED || 
                        $related_bet_transaction['status'] == Game_logs::STATUS_REFUND) {
                            continue;
                        } 

                        #current round
                        elseif($this->params['TicketID'] == $related_bet_transaction['round_id']){
                            $this->additional_amount = abs($this->params['Payout']);

                            #lost
                            if($this->params['Payout'] <= 0){
                                if ($this->params['Status'] == self::BET_STATUS_REFUND) {
                                    #invalid succedding bet
                                    $game_log_status = Game_logs::STATUS_REFUND;
                                    $this->additional_amount = 0;
                                }else{
                                    $related_bet_transaction['extra_info'] = json_decode($related_bet_transaction['extra_info'], true);
                                    $bet_amount = $related_bet_transaction['extra_info']['Stake'];
                                    $this->ssa_adjustment_operation = 'decrease';#when stake is not deducted and lost.
                                    $this->total_win_amount = 0;
                                    $bet_transaction['extra_info']['TakenAmount'] = $bet_amount;
                                }

                            #win
                            }else{
                                if ($related_bet_transaction['amount'] == 0) { #bet amount is not deducted yet
                                    #bet + win
                                    $related_bet_transaction['extra_info'] = json_decode($related_bet_transaction['extra_info'], true);
                                    $stake = $related_bet_transaction['extra_info']['Stake'];
                                    $this->total_win_amount = $stake + $this->additional_amount;
                                    $bet_amount = $stake;
                                    $bet_transaction['extra_info']['TakenAmount'] = $bet_amount;
                                }
                                else{
                                    $this->total_win_amount = $this->additional_amount;
                                    $bet_amount = abs($related_bet_transaction['amount']);
                                }

                            }

                            break;
                        }else{
                            return $this->responseWithErrorMessage('Invalid TicketID');
                        }
                    }
                    //IfbetActionConditionType? use case?

                    break;
                case self::BET_TYPE_REVERSE_BET:
                    #initialize reverse bet
                    $game_log_status = Game_logs::STATUS_SETTLED;

                    #iterate through selections though there should be only 1 selection
                    foreach ($bet_transaction['bet_odd_info'] as $key => $bet_odd_info) {
                        foreach ($this->params['SelectionsStatus'] as $selection_status) {

                            #if selection is a match, update status and score
                            if ($bet_odd_info['Odd_Selection_ID'] == $selection_status['ID']) {
                                #check if there is a change in status
                                if ($bet_odd_info['Selection_Status'] != $selection_status['Status'] ) {
                                    if($selection_status['Status'] == self::BET_STATUS_WIN ||
                                        $selection_status['Status'] == self::BET_STATUS_LOST ||
                                        $selection_status['Status'] == self::BET_STATUS_HALF_WIN ||
                                        $selection_status['Status'] == self::BET_STATUS_HALF_LOSE){

                                        $this->additional_amount = $this->params['Payout'];
                                    }

                                    #check if the status is a refund, then return the bet to player.
                                    elseif($selection_status['Status'] == self::BET_STATUS_REFUND) {
                                        $this->additional_amount = $bet_transaction['amount'];
                                        $bet_transaction['bet_odd_info'][$key]['Refund_Amount'] = $this->additional_amount;
                                        $game_log_status = Game_logs::STATUS_REFUND;
                                        $invalid_selection_count++;
                                    }

                                    #check if the status is a cancelled, then return the bet to player.
                                    elseif($selection_status['Status'] == self::BET_STATUS_CANCELLED) {
                                        $this->additional_amount = $bet_transaction['amount'];
                                        $bet_transaction['bet_odd_info'][$key]['Refund_Amount'] = $this->additional_amount;
                                        $game_log_status = Game_logs::STATUS_CANCELLED;
                                        $invalid_selection_count++;
                                    }

                                    else{
                                        return $this->responseWithErrorMessage('Unknown Selection Status');
                                    }

                                    #update bet_odd_info level
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Status'] = $selection_status['Status'];
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Score'] = $selection_status['Score'];

                                }
                            }
                        }

                        #update events info
                        foreach ($this->params['EventsInfo'] as $event_info) {
                            if ($bet_odd_info['Event_ID'] == $event_info['ID']) {
                                $bet_transaction['bet_odd_info'][$key]['Event_StartDate'] = $event_info['StartTime'];
                                break;
                            }
                        }
                    }

                    #get bet information based on GroupTicketID
                    $bet_transactions = $this->ssa_get_transactions($this->transaction_table,
                    [
                        'GroupTicketID' => $bet_transaction['GroupTicketID'],
                        'transaction_type' => self::SEAMLESS_TRANSACTION_TYPE_PLACE_BET,
                    ],
                    ['id', 'token', 'game_username', 'round_id', 'amount', 'status', 'extra_info']
                    );

                    #sort related group tickets by round id ascending
                    usort($bet_transactions, array($this, 'customSortByRoundIDAscending'));

                    foreach ($bet_transactions as $key => $related_bet_transaction) {
                        #skip bet if settled.
                        if ($related_bet_transaction['status'] == Game_logs::STATUS_SETTLED || 
                        $related_bet_transaction['status'] == Game_logs::STATUS_CANCELLED || 
                        $related_bet_transaction['status'] == Game_logs::STATUS_REFUND) {
                            continue;
                        } 

                        #current round
                        elseif($this->params['TicketID'] == $related_bet_transaction['round_id']){
                            $this->additional_amount = abs($this->params['Payout']);

                            #lost
                            if($this->params['Payout'] <= 0){
                                if ($this->params['Status'] == self::BET_STATUS_REFUND) {
                                    #invalid succedding bet
                                    $game_log_status = Game_logs::STATUS_REFUND; //do you return the amont as payout on refund?
                                    $this->additional_amount = 0;
                                }else{
                                    $related_bet_transaction['extra_info'] = json_decode($related_bet_transaction['extra_info'], true);
                                    $bet_amount = $related_bet_transaction['extra_info']['Stake'];
                                    $this->ssa_adjustment_operation = 'decrease';#when stake is not deducted and lost.
                                    $this->total_win_amount = 0;
                                    $bet_transaction['extra_info']['TakenAmount'] = $bet_amount;
                                }

                            #win
                            }else{
                                if ($related_bet_transaction['amount'] == 0) { #bet amount is not deducted yet
                                    #bet + win
                                    $related_bet_transaction['extra_info'] = json_decode($related_bet_transaction['extra_info'], true);
                                    $stake = $related_bet_transaction['extra_info']['Stake'];
                                    $this->total_win_amount = $stake + $this->additional_amount;
                                    $bet_amount = $stake;
                                    $bet_transaction['extra_info']['TakenAmount'] = $bet_amount;
                                }
                                else{
                                    $this->total_win_amount = $this->additional_amount;
                                    $bet_amount = abs($related_bet_transaction['amount']);
                                }
                            }

                            break;
                        }else{
                            return $this->responseWithErrorMessage('Invalid TicketID');
                        }
                    }

                    break;
                case self::BET_TYPE_TEASER:
                    #iterate through selection
                    foreach ($bet_transaction['bet_odd_info'] as $key => $bet_odd_info) {
                        foreach ($this->params['SelectionsStatus'] as $selection_status) {
                            if ($bet_odd_info['Odd_Selection_ID'] == $selection_status['ID']){

                                #update bet_odd_info level
                                $bet_transaction['bet_odd_info'][$key]['Selection_Status'] = $selection_status['Status'];
                                $bet_transaction['bet_odd_info'][$key]['Selection_Score'] = $selection_status['Score'];
                                break;
                            }
                        }
                    }

                    #validate winnings, calculate total win and additional amount
                    $this->additional_amount = $this->params['Payout'];
                    $this->total_win_amount = $this->params['Payout'];
                    $game_log_status = Game_logs::STATUS_SETTLED;

                    break;
                default:
                    return $this->responseWithErrorMessage('Transaction Type is not implemented: '. $bet_transaction['BetType']);
            }


            #wallet adjustment
            $controller = $this;
            $this->processed_amount = $this->additional_amount;
            $this->transaction_type = self::SEAMLESS_TRANSACTION_TYPE_SETTLE_BET;
            $this->wallet_model->setExternalGameId($bet_transaction['game_code']);
            $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT);
            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use ($controller) {
                return $controller->walletAdjustment($this->ssa_adjustment_operation, $this->ssa_insert, $this->processed_amount);
            });

            #exit on error walletAdjustment
            if (!$success) {
                return $this->responseWithErrorMessage('Adjustment Failed: ' . $this->params['RequestID']);
            }

            #update extra info
            $bet_transaction['extra_info']['Valid_Selection'] = $bet_transaction['extra_info']['Valid_Selection'] - $invalid_selection_count;
            $bet_transaction['extra_info']['CommitDate'] = $this->params['CommitDate'];
            $bet_transaction['extra_info']['BalanceMode'] = $this->params['BalanceMode'];

            #adjustment data for bet transaction
            $data = [
                // 'win_amount' => $this->processed_amount,
                'win_amount' => $this->total_win_amount, #set the total win amount
                'status' => $game_log_status,
                'SettleBetStatus' => $settle_bet_status,
                'bet_odd_info' => json_encode($bet_transaction['bet_odd_info']),
                'extra_info' => json_encode($bet_transaction['extra_info']),
            ];

            #update bet amount info for ifbet
            if ($bet_transaction['BetType'] == self::BET_TYPE_IF_BET || $bet_transaction['BetType'] == self::BET_TYPE_REVERSE_BET) {
                $data['bet_amount'] = $bet_amount; #set the total bet amount
            }
            $this->ssa_update_transaction_without_result($this->transaction_table, $data, 'id', $bet_transaction['id']);

            $response = array(
                "Balance" => $this->after_balance,
                "Status" => "OK",
            );
            $response_result_additional_message = 'Balance: '. $this->after_balance;
            $this->utils->debug_log(self::DEBUG_KEY . $response_result_additional_message);
            return $this->rebuildResponsefromArrayToJSON($response, $response_result_additional_message);
        }

        $this->responseWithErrorMessage('Invalid Token');
    }



    public function UnsettleBet(){
        #validate parameters
        $is_valid = $this->ssa_validate_request_params($this->params, [
            'RequestID'                         => ['required', 'string', 'maximum_size:100'],
            'ApiVersion'                        => ['required', 'string', 'maximum_size:5'],
            'Token'                             => ['required', 'string', 'maximum_size:250'],
            'TicketID'                          => ['required', 'integer'],
            'BalanceMode'                       => ['required', 'string'],
            'Payout'                            => ['optional', 'numeric'],
            'PayoutStatus'                      => ['optional', 'integer'],
            'SelectionsStatus'                  => ['required', 'multidimensional_array'],
        ]);

        #validate PayoutStatus, Payout Amount
        if (!$is_valid || !in_array($this->params['PayoutStatus'], [0, 1]) || $this->params['Payout'] < 0) {
            return $this->responseWithErrorMessage('Invalid Parameter');
        }

        #get bet information
        $bet_transaction = $this->ssa_get_transaction($this->transaction_table,
            [
                'round_id' => $this->params['TicketID'],
                'transaction_type' => self::SEAMLESS_TRANSACTION_TYPE_PLACE_BET,
                'isAccepted' => true,
            ],
            ['id', 'token', 'game_username', 'BetType', 'win_amount', 'bet_odd_info', 'extra_info', 'SettleBetStatus', 'amount', 'GroupTicketID', 'game_code']
        );

        #compare placebet token to the settlebet token. should be the same.
        if ($bet_transaction['token'] !== $this->params['Token']) {
            return $this->responseWithErrorMessage('Ticket ID not found');
        }

        $this->player_details = $this->ssa_get_player_details($this->ssa_subject_type_game_username, $bet_transaction['game_username'], $this->game_platform_id);
        $this->before_balance = 0;

        #check if player exists
        if (!empty($this->player_details['player_id'])) {
            #get balance
            $this->before_balance = $this->ssa_get_player_wallet_balance($this->player_details['player_id'], $this->game_platform_id);

            #validate if transaction exists. respond with balance if exists.
            $this->seamless_service_unique_id = $this->utils->mergeArrayValues([$this->game_platform_id, $this->api_method_type, $this->params['TicketID'], $this->params['RequestID']]);
            $is_transaction_exist = $this->ssa_is_transaction_exists($this->transaction_table, ['seamless_service_unique_id' => $this->seamless_service_unique_id]);
            if ($is_transaction_exist) {
                $response_result_additional_message = 'Request already exists';
                $response = array(
                    "ErrorDescription" => $response_result_additional_message,
                    "Balance" => $this->before_balance,
                    "Status" => "OK",
                );
                $this->utils->debug_log(self::DEBUG_KEY . $response_result_additional_message);
                return $this->rebuildResponsefromArrayToJSON($response, $response_result_additional_message);
            }

            #check if balance mode is Normal
            if ($this->params['BalanceMode'] !== 'Normal') {
                return $this->responseWithErrorMessage('Balance Mode: '. $this->params['BalanceMode']);
            }

            #initialize variables
            $this->deduction_amount = 0;
            $this->total_win_amount = 0;
            $bet_amount = 0;
            $taken_amount = 0;
            $revalidated_selection_count = 0;
            $bet_transaction['bet_odd_info'] = json_decode($bet_transaction['bet_odd_info'], true);
            $bet_transaction['extra_info'] = json_decode($bet_transaction['extra_info'], true);

            switch ($bet_transaction['BetType']) {
                case self::BET_TYPE_SINGLE:
                    #iterate through selections though there should be only 1 selection
                    foreach ($bet_transaction['bet_odd_info'] as $key => $bet_odd_info) {
                        foreach ($this->params['SelectionsStatus'] as $selection_status) {
                            #if selection is a match, update status and score
                            if ($bet_odd_info['Odd_Selection_ID'] == $selection_status['ID']) {

                                #check if there is a change in status
                                if ($bet_odd_info['Selection_Status'] != $selection_status['Status']) {

                                    #if from refund to other status, deduct balance
                                    if ($selection_status['Status'] == self::BET_STATUS_PENDING) {

                                        #if from refund, deduct the bet amount
                                        if($bet_odd_info['Selection_Status'] == self::BET_STATUS_REFUND || $bet_odd_info['Selection_Status'] == self::BET_STATUS_CANCELLED) {
                                            $this->deduction_amount = $bet_transaction['amount'];
                                            $bet_transaction['bet_odd_info'][$key]['Refund_Amount'] = 0;
                                            $revalidated_selection_count++;
                                        }

                                        #if from win, deduct the win amount
                                        elseif($bet_odd_info['Selection_Status'] == self::BET_STATUS_WIN ||
                                            $bet_odd_info['Selection_Status'] == self::BET_STATUS_LOST ||
                                            $bet_odd_info['Selection_Status'] == self::BET_STATUS_HALF_WIN ||
                                            $bet_odd_info['Selection_Status'] == self::BET_STATUS_HALF_LOSE){

                                            $this->deduction_amount = $bet_transaction['win_amount'];
                                        }

                                        else{
                                            return $this->responseWithErrorMessage('Unknown Selection Status');
                                        }

                                        #update bet_odd_info level
                                        $bet_transaction['bet_odd_info'][$key]['Selection_Status'] = $selection_status['Status'];
                                        $bet_transaction['bet_odd_info'][$key]['Selection_Score'] = $selection_status['Score'];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    break;
                #Game Mechanics: Combo bet is a combination of single bets. If one selection is lost, the whole combo bet is lost.
                case self::BET_TYPE_COMBO:
                    #iterate through selections
                    foreach ($bet_transaction['bet_odd_info'] as $key => $bet_odd_info) {
                        foreach ($this->params['SelectionsStatus'] as $selection_status) {
                            #if selection is a match, update status and score
                            if ($bet_odd_info['Odd_Selection_ID'] == $selection_status['ID']) {
                                #check if selection status is changed to pending
                                if ($bet_odd_info['Selection_Status'] != $selection_status['Status'] && $selection_status['Status'] == self::BET_STATUS_PENDING) {
                                    #reset refund/cancelled status
                                    if($bet_odd_info['Selection_Status'] == self::BET_STATUS_REFUND || $bet_odd_info['Selection_Status'] == self::BET_STATUS_CANCELLED) {
                                        $bet_transaction['bet_odd_info'][$key]['Refund_Amount'] = 0;
                                        $revalidated_selection_count++;
                                    }
                                
                                    #return the win amount
                                    $this->deduction_amount = $bet_transaction['win_amount'];
                                }

                                #update bet_odd_info level
                                $bet_transaction['bet_odd_info'][$key]['Selection_Status'] = $selection_status['Status'];
                                $bet_transaction['bet_odd_info'][$key]['Selection_Score'] = $selection_status['Score'];
                                break;
                            }
                        }
                    }
                    break;
                
                case self::BET_TYPE_SYSTEM:
                    #iterate through selection and update status
                    foreach ($bet_transaction['bet_odd_info'] as $key => $bet_odd_info) {
                        foreach ($this->params['SelectionsStatus'] as $selection_status) {
                            if ($bet_odd_info['Odd_Selection_ID'] == $selection_status['ID']){
                                #update if there is a change in status
                                if ($bet_odd_info['Selection_Status'] != $selection_status['Status'] ){
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Status'] = $selection_status['Status'];
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Score'] = $selection_status['Score'];
                                }
                                break;
                            }
                        }
                    }

                    #validate winnings, calculate total win and deduction amount
                    if ($bet_transaction['win_amount'] < $this->params['Payout']) {
                        return $this->responseWithErrorMessage('Invalid Payout Amount');
                    }
                    $this->deduction_amount = $bet_transaction['win_amount'] - $this->params['Payout'];
                    $this->total_win_amount = $this->params['Payout'];

                    break;
                case self::BET_TYPE_IF_BET:
                    #iterate through selection and update status
                    foreach ($bet_transaction['bet_odd_info'] as $key => $bet_odd_info) {
                        foreach ($this->params['SelectionsStatus'] as $selection_status) {
                            if ($bet_odd_info['Odd_Selection_ID'] == $selection_status['ID']){
                                #update if there is a change in status
                                if ($bet_odd_info['Selection_Status'] != $selection_status['Status'] ){
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Status'] = $selection_status['Status'];
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Score'] = $selection_status['Score'];
                                }
                                break;
                            }
                        }
                    }

                    #get bet information based on GroupTicketID
                    $bet_transactions = $this->ssa_get_transactions($this->transaction_table,
                    [
                        'GroupTicketID' => $bet_transaction['GroupTicketID'],
                        'transaction_type' => self::SEAMLESS_TRANSACTION_TYPE_PLACE_BET,
                    ],
                    ['id', 'token', 'game_username', 'round_id', 'amount', 'status', 'extra_info', 'bet_amount']
                    );

                    #sort related group tickets by round id ascending
                    usort($bet_transactions, array($this, 'customSortByRoundIDAscending'));

                    #iterate through related bet transactions
                    foreach ($bet_transactions as $key => $related_bet_transaction) {
                        
                        #check if current selection is the same as the current round
                        if ($this->params['TicketID'] == $related_bet_transaction['round_id']){
                            #check if current selection is settled. only then allow to unsettle.
                            if ($related_bet_transaction['status'] == Game_logs::STATUS_SETTLED ||
                                $related_bet_transaction['status'] == Game_logs::STATUS_REFUND ||
                                $related_bet_transaction['status'] == Game_logs::STATUS_CANCELLED){

                                #return the result amount
                                $this->deduction_amount = $bet_transaction['win_amount'];
                                $bet_amount = $related_bet_transaction['bet_amount'];

                                #set bet amount and taken amount to 0 if not first bet.
                                if ($related_bet_transaction['amount'] == 0) {
                                    $this->deduction_amount = $bet_transaction['win_amount'] - $related_bet_transaction['bet_amount'];
                                    $bet_amount = $taken_amount = 0;
                                    $bet_transaction['extra_info']['TakenAmount'] = $taken_amount;
                                }

                                break;
                            }else{
                                return $this->responseWithErrorMessage('Selection is not settled');
                            }
                        }
                    }

                    break;

                case self::BET_TYPE_REVERSE_BET:
                    #iterate through selection and update status
                    foreach ($bet_transaction['bet_odd_info'] as $key => $bet_odd_info) {
                        foreach ($this->params['SelectionsStatus'] as $selection_status) {
                            if ($bet_odd_info['Odd_Selection_ID'] == $selection_status['ID']){
                                #update if there is a change in status
                                if ($bet_odd_info['Selection_Status'] != $selection_status['Status'] ){
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Status'] = $selection_status['Status'];
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Score'] = $selection_status['Score'];
                                }
                                break;
                            }
                        }
                    }

                    #get bet information based on GroupTicketID
                    $bet_transactions = $this->ssa_get_transactions($this->transaction_table,
                    [
                        'GroupTicketID' => $bet_transaction['GroupTicketID'],
                        'transaction_type' => self::SEAMLESS_TRANSACTION_TYPE_PLACE_BET,
                    ],
                    ['id', 'token', 'game_username', 'round_id', 'amount', 'status', 'extra_info', 'bet_amount']
                    );

                    #sort related group tickets by round id ascending
                    usort($bet_transactions, array($this, 'customSortByRoundIDAscending'));

                    #iterate through related bet transactions
                    foreach ($bet_transactions as $key => $related_bet_transaction) {

                        #check if current selection is the same as the current round
                        if ($this->params['TicketID'] == $related_bet_transaction['round_id']){
                            #check if current selection is settled. only then allow to unsettle.
                            if ($related_bet_transaction['status'] == Game_logs::STATUS_SETTLED ||
                                $related_bet_transaction['status'] == Game_logs::STATUS_REFUND ||
                                $related_bet_transaction['status'] == Game_logs::STATUS_CANCELLED){

                                #return the result amount
                                $this->deduction_amount = $bet_transaction['win_amount'];
                                $bet_amount = $related_bet_transaction['bet_amount'];

                                #set bet amount and taken amount to 0 if not first bet.
                                if ($related_bet_transaction['amount'] == 0) {
                                    $this->deduction_amount = $bet_transaction['win_amount'] - $related_bet_transaction['bet_amount'];
                                    $bet_amount = $taken_amount = 0;
                                    $bet_transaction['extra_info']['TakenAmount'] = $taken_amount;
                                }

                                break;
                            }else{
                                return $this->responseWithErrorMessage('Selection is not settled');
                            }
                        }
                    }
                    break;
                case self::BET_TYPE_TEASER:
                    #iterate through selection and update status
                    foreach ($bet_transaction['bet_odd_info'] as $key => $bet_odd_info) {
                        foreach ($this->params['SelectionsStatus'] as $selection_status) {
                            if ($bet_odd_info['Odd_Selection_ID'] == $selection_status['ID']){
                                #update if there is a change in status
                                if ($bet_odd_info['Selection_Status'] != $selection_status['Status'] ){
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Status'] = $selection_status['Status'];
                                    $bet_transaction['bet_odd_info'][$key]['Selection_Score'] = $selection_status['Score'];
                                }
                                break;
                            }
                        }
                    }

                    #validate winnings, calculate total win and deduction amount
                    $this->deduction_amount = $bet_transaction['win_amount'];

                    break;
                default:
                    return $this->responseWithErrorMessage('Transaction Type is not implemented: '. $bet_transaction['BetType']);
            }


            #Allow negative balance for unsettle bet. This is to accommodate the case where unsettle amount is higher than the player balance.
            $controller = $this;
            $this->processed_amount = $this->deduction_amount;
            $this->transaction_type = self::SEAMLESS_TRANSACTION_TYPE_UNSETTLE_BET;
            $this->wallet_model->setExternalGameId($bet_transaction['game_code']);
            $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_REFUND);
            $success = $this->lockAndTransForPlayerBalance($this->player_details['player_id'], function () use ($controller) {
                return $controller->walletAdjustment($this->ssa_decrease, $this->ssa_insert, $this->processed_amount);
            });

            #exit on error walletAdjustment
            if (!$success) {
                return $this->responseWithErrorMessage('Adjustment Failed: ' . $this->params['RequestID']);
            }

            #update extra info
            $bet_transaction['extra_info']['Valid_Selection'] = $bet_transaction['extra_info']['Valid_Selection'] + $revalidated_selection_count;
            $bet_transaction['extra_info']['CommitDate'] = 0;
            #adjustment data for bet transaction
            $data = [
                'status' => Game_logs::STATUS_UNSETTLED,
                'win_amount' => $this->total_win_amount,
                'SettleBetStatus' => 'Pending',
                'bet_odd_info' => json_encode($bet_transaction['bet_odd_info']),
                'extra_info' => json_encode($bet_transaction['extra_info']),
            ];

            #update bet amount info for ifbet
            if ($bet_transaction['BetType'] == self::BET_TYPE_IF_BET || $bet_transaction['BetType'] == self::BET_TYPE_REVERSE_BET) {
                $data['bet_amount'] = $bet_amount; #set the total bet amount
            }

            $this->ssa_update_transaction_without_result($this->transaction_table, $data, 'id', $bet_transaction['id']);

            $response = array(
                "Balance" => $this->after_balance,
                "Status" => "OK",
            );

            $response_result_additional_message = 'Balance: '. $this->after_balance;
            $this->utils->debug_log(self::DEBUG_KEY . $response_result_additional_message);
            return $this->rebuildResponsefromArrayToJSON($response, $response_result_additional_message);
        }

        $this->responseWithErrorMessage('Invalid Token');
    }



    private function walletAdjustment($adjustment_type, $query_type, $amount)
    {
        $this->utils->debug_log(self::DEBUG_KEY . 'before balance: ', $this->before_balance, 'adjustment:' , $adjustment_type, ' query: ' , $query_type, ' amount: ', $amount);
        $this->bet_amount = $this->win_amount = $after_balance = 0;
        $this->after_balance = $this->before_balance;

        #set amount to be deducted
        $amount = $this->ssa_operate_amount($amount, $this->adjustment_precision, $this->adjustment_conversion, $this->adjustment_arithmetic_name);

        #if amount is 0 no adjustment needed
        if ($amount != 0) {
            if ($adjustment_type == $this->ssa_decrease) {
                $this->bet_amount = $amount;
                $amount = abs($amount);
                $success = $this->ssa_decrease_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);
                $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id);
                $amount = (-1) * ($amount);
            } elseif ($adjustment_type == $this->ssa_increase) {
                $this->win_amount = $amount;
                $success = $this->ssa_increase_player_wallet($this->player_details['player_id'], $this->game_platform_id, $amount, $after_balance);
                $this->ssa_set_uniqueid_of_seamless_service($this->seamless_service_unique_id);
                $this->wallet_model->setGameProviderActionType(Wallet_model::REMOTE_WALLET_ACTION_TYPE_PAYOUT);
            } else {
                return false;
            }

            $this->after_balance = $after_balance;
        }

        $transaction_data = [
            'amount' => $amount,
            'before_balance' => $this->before_balance,
            'after_balance' => $this->after_balance,
        ];

        $this->prepareTransactionRequestData($transaction_data);
        return true;
    }

    private function prepareTransactionRequestData($transaction_data)
    {
        $transaction_time = $this->ssa_date_time_modifier(null, $this->game_provider_gmt, $this->game_provider_date_time_format);
        $selection_count = 0;

        $OddInfo = [];
        if ($this->transaction_type == self::SEAMLESS_TRANSACTION_TYPE_PLACE_BET) {
            foreach ($this->params['SelectionsDetails'] as $selection){
                $item = [
                    'Odd_Selection_ID' => !empty($selection['Odd']['ID']) ? $selection['Odd']['ID'] : null,
                    'Odd_Name' => !empty($selection['Odd']['Name']) ? $selection['Odd']['Name'] : null,
                    'Odd_Value' => !empty($selection['Odd']['Value']) ? $selection['Odd']['Value'] : null,
                    'Odd_FormattedValue' => !empty($selection['Odd']['FormattedValue']) ? $selection['Odd']['FormattedValue'] : null,
                    'Event_ID' => !empty($selection['Event']['ID']) ? $selection['Event']['ID'] : null,
                    'Event_Name' => !empty($selection['Event']['Name']) ? $selection['Event']['Name'] : null,
                    'Event_StartDate' => !empty($selection['Event']['StartDate']) ? $selection['Event']['StartDate'] : null,
                    'Tournament_Name' => !empty($selection['Tournament']['Name']) ? $selection['Tournament']['Name'] : null,
                    'Category_Name' => !empty($selection['Category']['Name']) ? $selection['Category']['Name'] : null,
                    'Sport_Name' => !empty($selection['Sport']['Name']) ? $selection['Sport']['Name'] : null,
                    'Selection_Status' => self::BET_STATUS_PENDING,
                    'SystemHeaderID' => !empty($selection['SystemHeaderID']) ? $selection['SystemHeaderID'] : null,
                    'Refund_Amount' => 0,
                ];

                $selection_count++;
                array_push($OddInfo, $item);
            }
        }

        $extra_info = [
            'Stake'                     => !empty($this->params['Stake']) ? $this->params['Stake'] : null,
            'TakenAmount'               => !empty($this->params['TakenAmount']) ? $this->params['TakenAmount'] : null,
            'Odds'                      => !empty($this->params['Odds']) ? $this->params['Odds'] : null,
            'OddsFormat'                => !empty($this->params['OddsFormat']) ? $this->params['OddsFormat'] : null,
            'BalanceMode'               => !empty($this->params['BalanceMode']) ? $this->params['BalanceMode'] : null,
            'CommitDate'                => !empty($this->params['CommitDate']) ? $this->params['CommitDate'] : null,
            'Valid_Selection'           => $selection_count,
            'Selection_Count'           => $selection_count,
            //finalize necessary data for game logs
        ];

        $wallet_transaction = [
            #default
            'game_platform_id' => $this->game_platform_id,
            'player_id' => $this->player_details['player_id'],
            'game_username' => $this->player_details['game_username'],
            'language' => $this->language,
            'currency' => $this->currency,
            'transaction_type' => $this->transaction_type,
            'transaction_id' => $this->params['RequestID'],
            'game_code' =>  !empty($this->params['SelectionsDetails'][0]['Sport']['Name']) ? $this->params['SelectionsDetails'][0]['Sport']['Name'] : null,
            'round_id' => $this->params['TicketID'],
            'amount' => $transaction_data['amount'],
            'before_balance' => $transaction_data['before_balance'],
            'after_balance' => $transaction_data['after_balance'],
            'status' => $this->game_logs_status,
            'start_at' => $transaction_time,
            'end_at' => $transaction_time,

            #additional ultraplay params
            'Token' => $this->params['Token'],

            #placebet
            'IsAccepted' => !empty($this->params['IsAccepted']) ? $this->params['IsAccepted'] : false,
            'GroupTicketID' => !empty($this->params['GroupTicketID']) ? $this->params['GroupTicketID'] : null,
            'BetType' => !empty($this->params['BetType']) ? $this->params['BetType'] : null,
            'Selections' => !empty($this->params['Selections']) ? $this->params['Selections'] : null,
            'FormattedOdds' => !empty($this->params['FormattedOdds']) ? $this->params['FormattedOdds'] : null,
            'Description' => !empty($this->params['Description']) ? $this->params['Description'] : null,
            'IfbetActionConditionType'  => !empty($this->params['IfbetActionConditionType']) ? $this->params['IfbetActionConditionType'] : null,
            'bet_odd_info' => json_encode($OddInfo),
            
            #settlebet
            'PayoutStatus' => !empty($this->params['PayoutStatus']) ? $this->params['PayoutStatus'] : null,
            'SettleBetStatus' => !empty($this->params['Status']) ? $this->params['Status'] : null,

            #default
            'elapsed_time' => $this->utils->getCostMs(),
            'request' => json_encode($this->params),
            'response' => null,
            'extra_info' => json_encode($extra_info),
            'bet_amount' => abs($this->bet_amount),
            'win_amount' => abs($this->win_amount),

            'result_amount' => 0,
            'flag_of_updated_result' => $this->ssa_flag_not_updated,
            'wallet_adjustment_status' => null,
            'external_unique_id' => $this->params['RequestID'],
            'seamless_service_unique_id' => $this->seamless_service_unique_id,
        ];

        $this->saveTransactionRequestData($wallet_transaction);
        return;
    }


    private function saveTransactionRequestData($wallet_transaction)
    {
        $this->ssa_insert_update_transaction($this->transaction_table, $this->ssa_insert, $wallet_transaction, 'external_unique_id', $this->params['RequestID'], false);
        return;
    }

    #build functions
    protected function rebuildResponsefromArrayToJSON($arrayData, $response_result_additional_message) {
        #set default values
        $arrayData['Status'] = !empty($arrayData['Status'])? $arrayData['Status']:self::STATUS_ERROR;
        $arrayData['ErrorDescription'] = !empty($arrayData['ErrorDescription'])? $arrayData['ErrorDescription']: '';

        #save response result
        $status = $arrayData['Status'] == self::STATUS_SUCCESS ? true : false;

        $fields = [
			'player_id'	=> $this->player_details['player_id'],
		];

        $this->saveResponseResult(
                                    $status, 
                                    $this->api_method_type, 
                                    $this->params, 
                                    $arrayData, 
                                    200, 
                                    $response_result_additional_message, 
                                    null,
                                    $fields
                                );

        return $this->ssa_response_result([
            'response' => $arrayData,
            'content_type' => 'application/json',
        ]);
    }

    public function retrieveHeaders(){
        $this->headers = getallheaders();
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

    private function validateTokenThenGetUserName($token){
        $this->CI->load->model('external_common_tokens');
        $tokenObject = $this->CI->external_common_tokens->getExternalCommonTokenInfoByToken($token);
        if($tokenObject){
            return json_decode($tokenObject['extra_info'])->gameUsername;
        }
        return false;
    }

    private function responseWithErrorMessage($message){
        $response = array(
            "ErrorDescription" => $message
        );
        $this->utils->debug_log(self::DEBUG_KEY . $message);
        return $this->rebuildResponsefromArrayToJSON($response, $message);
    }

    private function truncateAmountToPrecision($raw_value, $precision){
        return floor($raw_value * pow(10, $precision)) / pow(10, $precision);
    }

    private function customSortByRoundIDAscending($a, $b){
        return $a['round_id'] - $b['round_id'];
    }

}