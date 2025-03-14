<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Hogaming_seamless_service_api extends BaseController
{
    public $game_api;

    // Error codes for get balance response.
    const ERR_GETBAL_SUCCESS = 0;
    const ERR_GETBAL_ACCOUNT_NOT_EXIST = 1000;
    const ERR_GETBAL_INVALID_CURRENCY = 1001;
    const ERR_GETBAL_LOCKED_ACCOUNT = 1004;
    const ERR_GETBAL_SYSTEM_ERROR = 9999;

    // Error code list for get balance response.
    public static $get_bal_error_codes = array(
        self::ERR_GETBAL_SUCCESS => 'No Error. Success response.',
        self::ERR_GETBAL_ACCOUNT_NOT_EXIST => 'user account does not exist',
        self::ERR_GETBAL_INVALID_CURRENCY => 'invalid currency',
        self::ERR_GETBAL_LOCKED_ACCOUNT => 'locked account',
        self::ERR_GETBAL_SYSTEM_ERROR => 'system error'
    );

    // Error codes for fund transfer response.
    const ERR_FUNDTRANS_SUCCESS = 0;
    const ERR_FUNDTRANS_ACCOUNT_NOT_EXIST = 1000;
    const ERR_FUNDTRANS_INVALID_CURRENCY = 1001;
    const ERR_FUNDTRANS_INVALID_AMOUNT = 1002;
    const ERR_FUNDTRANS_EXCEEDED_DAILY_WINLOST_LIMIT = 1003;
    const ERR_FUNDTRANS_LOCKED_ACCOUNT = 1004;
    const ERR_FUNDTRANS_INSUFFICIENT_BALANCE = 1005;
    const ERR_FUNDTRANS_EXCEEDED_DAILY_TURNOVER_LIMIT = 1006;
    const ERR_FUNDTRANS_SYSTEM_ERROR = 9999;

    // Error code list for fund transfer response.
    public static $fund_trans_error_codes = array(
        self::ERR_FUNDTRANS_SUCCESS => 'No Error. Success response.',
        self::ERR_FUNDTRANS_ACCOUNT_NOT_EXIST => 'User account does not exist',
        self::ERR_FUNDTRANS_INVALID_CURRENCY => 'Invalid currency',
        self::ERR_FUNDTRANS_INVALID_AMOUNT => 'Invalid amount',
        self::ERR_FUNDTRANS_EXCEEDED_DAILY_WINLOST_LIMIT => 'Exceeded win/loss limit for the day',
        self::ERR_FUNDTRANS_LOCKED_ACCOUNT => 'Locked account',
        self::ERR_FUNDTRANS_INSUFFICIENT_BALANCE => 'Insufficient Balance',
        self::ERR_FUNDTRANS_EXCEEDED_DAILY_TURNOVER_LIMIT => 'Exceeded the turnover limit for the day',
        self::ERR_FUNDTRANS_SYSTEM_ERROR => 'System error'
    );

    const FUNDTRANS_PLACE_BET_REQUEST = 500;
    const FUNDTRANS_CANCEL_BET_REQUEST = 501;
    const FUNDTRANS_PLAYER_WIN_REQUEST = 510;
    const FUNDTRANS_PLAYER_LOSE_REQUEST = 520;
    const FUNDTRANS_PLACE_BET_CANCELLATION_REQUEST = 502;

    public static $fund_trans_requests = array(
        self::FUNDTRANS_PLACE_BET_REQUEST => 'Place Bet Request',
        self::FUNDTRANS_CANCEL_BET_REQUEST => 'Cancel Bet Request',
        self::FUNDTRANS_PLAYER_WIN_REQUEST => 'Player Win Request',
        self::FUNDTRANS_PLAYER_LOSE_REQUEST => 'Player Lose Request',
        self::FUNDTRANS_PLACE_BET_CANCELLATION_REQUEST => 'Place Bet Cancellation Request'
    );

    public static $fund_trans_categories = array(
        self::FUNDTRANS_PLACE_BET_REQUEST => 'placebet',
        self::FUNDTRANS_CANCEL_BET_REQUEST => 'cancelbet',
        self::FUNDTRANS_PLAYER_WIN_REQUEST => 'playerwin',
        self::FUNDTRANS_PLAYER_LOSE_REQUEST => 'playerlose',
        self::FUNDTRANS_PLACE_BET_CANCELLATION_REQUEST => 'placebetcancel'
    );

    public static $fund_trans_operators = array(
        self::FUNDTRANS_PLACE_BET_REQUEST => '-',
        self::FUNDTRANS_CANCEL_BET_REQUEST => '+',
        self::FUNDTRANS_PLAYER_WIN_REQUEST => '+',
        self::FUNDTRANS_PLAYER_LOSE_REQUEST => null,
        self::FUNDTRANS_PLACE_BET_CANCELLATION_REQUEST => null
    );

    const FUNDTRANS_REFUND_OPERATOR = '+';

    private $transaction_for_fast_track = null;

    private $requestArray = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('wallet_model','game_provider_auth','common_token','original_game_logs_model','player_model','response_result'));
        $this->game_platform_id = isset($_GET['platform']) ? $_GET['platform'] : HOGAMING_SEAMLESS_API;
        $this->game_api = $this->utils->loadExternalSystemLibObject($this->game_platform_id);
        if(!$this->game_api){
            throw new Exception("Can't load API");
        }
        $game_api = $this->game_api;
        $this->currency = $this->game_api->getSystemInfo('currency', $game_api::CURRENCY);
    }

    private function _xmlToArray($xml_string)
    {
        $xml = simplexml_load_string($xml_string);
        $json = json_encode($xml);
        $array = json_decode($json, true);

        return $array;
    }

    private function _xmlToJson($xml_string)
    {
        $xml = simplexml_load_string($xml_string);
        $json = json_encode($xml);

        return $json;
    }

    private function _convertToDecimalWithoutComma($amount)
    {
        $balance = number_format($amount, 2, '.', '');

        return $balance;
    }

    private function _getBalanceResponse($is_success, $game_username, $currency, $error_code, $amount = null)
    {
        if ($is_success) {
            $response =  "<cw type='getBalanceResp' uname ='{$game_username}' cur='{$currency}' amt='{$amount}' err='{$error_code}' />";
        } else {
            $response =  "<cw type='getBalanceResp' uname ='{$game_username}' cur='{$currency}' err='{$error_code}' />";
        }
        return $response;
    }

    public function getBalance()
    {

        $game_api = $this->game_api;
        $request_xml_string = file_get_contents('php://input');
        $this->utils->debug_log("HOGAMING_SEAMLESS_API FUND TRANSFER REQUEST ============================>", $request_xml_string);
        $request_array = $this->_xmlToArray($request_xml_string);
        $this->requestArray = $request_array;
        $is_response_success = false;
        $method = __FUNCTION__ ;

        try {   

            #Index parameter
            if (!isset($request_array['@attributes']['type'],$request_array['@attributes']['uname']) || $request_array['@attributes']['type'] != 'getBalanceReq') {
                $error = $this->_getBalanceResponse(self::FALSE, null, $this->currency, self::ERR_FUNDTRANS_SYSTEM_ERROR);
                throw new Exception($error);
            }

            #User information
            list($player_id, $player_name, $game_username, $current_balance) = $this->getUserDetails($request_array);

            if(empty($player_name) || !$player_id){
                $error = $this->_getBalanceResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_ACCOUNT_NOT_EXIST);
                throw new Exception($error);
            }

            #Blocked Information
            if($this->player_model->isBlocked($player_id) || $game_api->isBlocked($player_name)){
                $error = $this->_getBalanceResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_LOCKED_ACCOUNT);
                throw new Exception($error);
            }

            $is_response_success = true;
            $formatted_balance = $this->_convertToDecimalWithoutComma($current_balance);
            $response = $this->_getBalanceResponse(self::TRUE, $game_username, $this->currency, self::ERR_GETBAL_SUCCESS, $formatted_balance);

        } catch (Exception $e) {
            $this->utils->debug_log('error',  $e->getMessage());
            $response = $e->getMessage();
        }
        $this->saveResponseResult($is_response_success, $method, $request_xml_string, $response, null);

        return $this->returnText($response);
    }

    private function _fundTransferResponse($is_success, $game_username, $currency, $error_code, $amount = null)
    {
        if ($is_success) {
            $response = "<cw type='fundTransferResp' uname ='{$game_username}' cur='{$currency}' amt='{$amount}' err='{$error_code}' />";
        } else {
            $response = "<cw type='fundTransferResp' uname ='{$game_username}' cur='{$currency}' err='{$error_code}' />";
        }
// 
        // return $this->returnText($response);
        return $response;
    }

    private function _addAmount($playerId, $amount)
    {
        if($this->utils->getConfig('enable_seamless_single_wallet')) {
            $reason_id=Abstract_game_api::REASON_UNKNOWN;
            $success = $this->wallet_model->transferSeamlessSingleWallet($playerId, Wallet_model::TRANSFER_TYPE_IN, $amount, $reason_id);
        } else {
            $success = $this->wallet_model->incSubWallet($playerId, $this->game_platform_id, $amount);
        }
        return $success;
    }

    private function _subtractAmount($playerId, $amount)
    {

        if($this->utils->getConfig('enable_seamless_single_wallet')) {
            $reason_id=Abstract_game_api::REASON_UNKNOWN;
            $success = $this->wallet_model->transferSeamlessSingleWallet($playerId, Wallet_model::TRANSFER_TYPE_OUT, $amount, $reason_id);
        } else {
            $success = $this->wallet_model->decSubWallet($playerId, $this->game_platform_id, $amount);
        }
        return $success;
    }

    private function _checkProcessedPlaceBetTransactionId($txnid)
    {
        $game_api = $this->game_api;
        $table = $game_api::TRANSACTION_LOGS_TABLE;

        $sql = <<<EOD
            SELECT id
            FROM {$table}
            WHERE txnid = ?
EOD;

        $params = [
            $txnid
        ];

        $queryResult = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        if (!empty($queryResult)) {
            return true;
        }

        return false;
    }

    private function _isProcessedTransaction($txnid)
    {
        $game_api = $this->game_api;
        $table = $game_api::TRANSACTION_LOGS_TABLE;

        $sql = <<<EOD
            SELECT id
            FROM {$table}
            WHERE txnid = ?
EOD;

        $params = [
            $txnid
        ];

        $queryResult = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        if (!empty($queryResult)) {
            return true;
        }

        return false;
    }

    private function _insertIntoOriginalGameLogs($data, $response_result_id)
    {
        $game_api = $this->game_api;
        $data = $data['@attributes'];

        $original_data = array(
            'uname' => isset($data['uname']) ? $data['uname'] : null,
            'cur' => isset($data['cur']) ? $data['cur'] : null,
            'amt' => isset($data['amt']) ? $data['amt'] : null,
            'txnid' => isset($data['txnid']) ? $data['txnid'] : null,
            'gametypeid' => isset($data['gametypeid']) ? $data['gametypeid'] : null,
            'txnsubtypeid' => isset($data['txnsubtypeid']) ? $data['txnsubtypeid'] : null,
            'gameid' => isset($data['gameid']) ? $data['gameid'] : null,
            'bAmt' => isset($data['bAmt']) ? $data['bAmt'] : null,
            'txn_reverse_id' => isset($data['txn_reverse_id']) ? $data['txn_reverse_id'] : null,
            'category' => isset($data['category']) ? $data['category'] : null,
            'operator' => isset($data['operator']) ? $data['operator'] : null,
            'response_result_id' => isset($response_result_id) ? $response_result_id : null,
            'external_uniqueid' => isset($data['txnid']) ? $data['txnid'] . '-' . time() : null
        );

        $game_api->doSyncOriginal(array($original_data), $game_api::ORIGINAL_GAMELOGS_TABLE);
    }

    private function _insertIntoTransactionLogs($data, $response_result_id, $before_balance = null, $after_balance = null)
    {
        $game_api = $this->game_api;
        $data = $data['@attributes'];

        $transaction_data = array(
            'uname' => isset($data['uname']) ? $data['uname'] : null,
            'cur' => isset($data['cur']) ? $data['cur'] : null,
            'amt' => isset($data['amt']) ? $data['amt'] : null,
            'txnid' => isset($data['txnid']) ? $data['txnid'] : null,
            'gametypeid' => isset($data['gametypeid']) ? $data['gametypeid'] : null,
            'txnsubtypeid' => isset($data['txnsubtypeid']) ? $data['txnsubtypeid'] : null,
            'gameid' => isset($data['gameid']) ? $data['gameid'] : null,
            'bAmt' => isset($data['bAmt']) ? $data['bAmt'] : null,
            'txn_reverse_id' => isset($data['txn_reverse_id']) ? $data['txn_reverse_id'] : null,
            'category' => isset($data['category']) ? $data['category'] : null,
            'operator' => isset($data['operator']) ? $data['operator'] : null,
            'response_result_id' => isset($response_result_id) ? $response_result_id : null,
            'external_uniqueid' => isset($data['txnid']) ? $data['txnid'] . '-' . time() : null,
            'provider_id' => $game_api->getPlatformCode(),
            'before_balance' => isset($before_balance) ? $before_balance : null,
            'after_balance' => isset($after_balance) ? $after_balance : null,
            'sessionid' => isset($data['sessionid']) ? $data['sessionid'] : null
        );

        $result = $game_api->doSyncTransaction(array($transaction_data), $game_api::TRANSACTION_LOGS_TABLE);
        $this->transaction_for_fast_track = null;
        if($result) {
            $this->CI->load->model('original_game_logs_model');
            $this->transaction_for_fast_track = $transaction_data;
            $this->transaction_for_fast_track['id'] = $this->CI->original_game_logs_model->getLastInsertedId();
        }
        return $result;
    }

    private function _addCategoryAndOperatorField($data, $fund_transfer_type, $is_refund = null)
    {
        if (isset($data['@attributes'])) {
            $data['@attributes']['category'] = self::$fund_trans_categories[$fund_transfer_type];
            $data['@attributes']['operator'] = self::$fund_trans_operators[$fund_transfer_type];

            if ($is_refund) {
                $data['@attributes']['operator'] = self::FUNDTRANS_REFUND_OPERATOR;
            }
        }

        return $data;
    }

    /**
     * overview : generate user details
     *
     * @param array $params
     * @return array
     */

    private function getUserDetails($request_array){
        $balance = null;

        //params
        $game_username  = isset($request_array['@attributes']['uname']) ? $request_array['@attributes']['uname'] : null;

        $player_name = $this->game_api->getPlayerUsernameByGameUsername($game_username);
        $player_id = $this->game_api->getPlayerIdFromUsername($player_name);
        

        if($player_name){
            $balance = $this->getPlayerBalance($player_name, self::FALSE);
        }

        return array($player_id, $player_name, $game_username, $balance);
    }

    public function fundTransfer()
    {
        $game_api = $this->game_api;
        $request_xml_string = file_get_contents('php://input');
        $this->utils->debug_log("HOGAMING_SEAMLESS_API FUND TRANSFER REQUEST ============================>", $request_xml_string);
        $request_array = $this->_xmlToArray($request_xml_string);
        $this->requestArray = $request_array;
        $is_response_success = false;
        $method = isset($request_array['@attributes']['txnsubtypeid']) ? __FUNCTION__ . "_".$request_type = $request_array['@attributes']['txnsubtypeid'] : __FUNCTION__ ;
        $respRlt = null;
        $respFileRlt = null;

        try {   

            #Index parameter
            if (!isset($request_array['@attributes']['type'],$request_array['@attributes']['txnsubtypeid'],$request_array['@attributes']['uname'],$request_array['@attributes']['amt'],$request_array['@attributes']['bAmt']) || $request_array['@attributes']['type'] != 'fundTransferReq') {
                $error = $this->_fundTransferResponse(self::FALSE, null, $this->currency, self::ERR_FUNDTRANS_SYSTEM_ERROR);
                throw new Exception($error);
            }

            #User information
            list($player_id, $player_name, $game_username, $current_balance) = $this->getUserDetails($request_array);

            if(empty($player_name) || !$player_id){
                $error = $this->_fundTransferResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_ACCOUNT_NOT_EXIST);
                throw new Exception($error);
            }

            #Blocked Information
            if($this->player_model->isBlocked($player_id) || $game_api->isBlocked($player_name)){
                $error = $this->_fundTransferResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_LOCKED_ACCOUNT);
                throw new Exception($error);
            }
            $extra_param = array(
                "player_id" => $player_id,
                "player_name" => $player_name,
                "game_username" => $game_username,
                "current_balance" => $current_balance,
                "request_xml_string" => $request_xml_string,
            );
            $request_array = array_merge($request_array, $extra_param);

            #default response
            $response = null;

            #save response result
            $response_result_id = $this->saveResponseResult(true, $method, $request_xml_string, $response, null);
            if(empty($response_result_id)){
                $error = $this->_fundTransferResponse(self::FALSE, null, $this->currency, self::ERR_FUNDTRANS_SYSTEM_ERROR);
                throw new Exception($error);
            }

            $success = $this->lockAndTransForPlayerBalance($player_id, function() use($request_array, &$response, $response_result_id) {
                return $this->_processFundTransfer($request_array, $response, $response_result_id);
            });

            if(!empty($response_result_id)){
                $response_result = $this->response_result->getResponseResultById($response_result_id);
                $result   = $this->response_result->getRespResultByTableField($response_result->filepath);

                $content = json_decode($result['content'], true);
                $content['resultText'] = $response;
                $content = json_encode($content);

                if(!$success){
                    $this->response_result->setResponseResultToError($response_result_id);
                }
                $this->response_result->updateResponseResultContentByFilepath($response_result->filepath, $content);
            }
        } catch (Exception $e) {
            $this->utils->debug_log('error',  $e->getMessage());
            $response = $e->getMessage();
            $this->saveResponseResult($is_response_success, $method, $request_xml_string, $response, null);
        }

        return $this->returnText($response); 
    }

    private function _processFundTransfer($request_array, &$response,$response_result_id){

        #parameters
        $amount = empty($request_array['@attributes']['amt']) ? 0 : $request_array['@attributes']['amt'];
        $bonus_amount = empty($request_array['@attributes']['bAmt']) ? 0 : $request_array['@attributes']['bAmt'];
        #amount to adjust
        $calculated_amount = $amount - $bonus_amount;

        $request_type = $request_array['@attributes']['txnsubtypeid'];
        $is_processed_transaction = isset($request_array['@attributes']['txnid']) ? $this->_isProcessedTransaction($request_array['@attributes']['txnid']) : false;

        #extra parameters
        $game_username = $request_array['game_username'];
        $player_id = $request_array['player_id'];
        $player_name = $request_array['player_name'];
        $current_balance = $request_array['current_balance'];

        $game_api = $this->game_api;
        $data = $this->_addCategoryAndOperatorField($request_array, $request_type);
        $before_balance = $this->getPlayerBalance($player_name);
        #default
        $success = false;
        switch ($request_type) {
            case self::FUNDTRANS_PLACE_BET_REQUEST:
                if ($this->utils->compareResultFloat($calculated_amount, '>', 0)) {
                    if ($this->utils->compareResultFloat($calculated_amount, '<=', $current_balance)) {
                        $response = $this->_fundTransferResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_SYSTEM_ERROR);
                        if (!$is_processed_transaction) {
                            if ($this->utils->compareResultFloat($before_balance, '=', $current_balance)) {
                                $success = $this->_subtractAmount($player_id, $calculated_amount);
                            } 
                        } 
                    } else {
                        $response = $this->_fundTransferResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_INSUFFICIENT_BALANCE);
                    }
                } else {
                    $response = $this->_fundTransferResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_INVALID_AMOUNT);
                }
                break;

            case self::FUNDTRANS_CANCEL_BET_REQUEST:
                if ($this->utils->compareResultFloat($calculated_amount, '>', 0)) {
                    $response = $this->_fundTransferResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_SYSTEM_ERROR);
                    if (!$is_processed_transaction) {
                        if ($this->utils->compareResultFloat($before_balance, '=', $current_balance)) {
                            $success = $this->_addAmount($player_id, $calculated_amount);
                        } 
                    }
                } else {
                    $response = $this->_fundTransferResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_INVALID_AMOUNT);
                }
                break;

            case self::FUNDTRANS_PLAYER_WIN_REQUEST:
                if ($this->utils->compareResultFloat($calculated_amount, '>', 0)) {
                    $response = $this->_fundTransferResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_SYSTEM_ERROR);
                    if (!$is_processed_transaction) {
                        if ($this->utils->compareResultFloat($before_balance, '=', $current_balance)) {
                            $success = $this->_addAmount($player_id, $calculated_amount);
                        } 
                    }
                } else {
                    $response = $this->_fundTransferResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_INVALID_AMOUNT);
                }
                break;

            case self::FUNDTRANS_PLAYER_LOSE_REQUEST:
                if ($this->utils->compareResultFloat($calculated_amount, '=', 0)) {
                    $response = $this->_fundTransferResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_SYSTEM_ERROR);
                    if (!$is_processed_transaction) {
                        if ($this->utils->compareResultFloat($before_balance, '=', $current_balance)) {
                            $success = true;
                        }
                    }
                } else {
                    $response = $this->_fundTransferResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_INVALID_AMOUNT);
                }
                break;

            case self::FUNDTRANS_PLACE_BET_CANCELLATION_REQUEST:
                $is_refund = isset($request_array['@attributes']['txn_reverse_id']) ? $this->_checkProcessedPlaceBetTransactionId($request_array['@attributes']['txn_reverse_id']) : false;
                $response = $this->_fundTransferResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_SYSTEM_ERROR);
                if ($is_refund) {
                    if ($this->utils->compareResultFloat($calculated_amount, '>', 0)) {
                        if (!$is_processed_transaction) {
                            if ($this->utils->compareResultFloat($before_balance, '=', $current_balance)) {
                                $success = $this->_addAmount($player_id, $calculated_amount);
                            }
                        }
                    } else {
                        $response = $this->_fundTransferResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_INVALID_AMOUNT);
                    }
                } 
                break;
            default:
                $response = $this->_fundTransferResponse(self::FALSE, $game_username, $this->currency, self::ERR_FUNDTRANS_SYSTEM_ERROR);
                break;
        }

        #allow 0 and negative result base on calculation as long as real amount is positive
        if (!$is_processed_transaction && $this->utils->compareResultFloat($calculated_amount, '<=', 0) && $this->utils->compareResultFloat($amount, '>', 0)) {
            $success = true;
        }

        #override response on success
        if($success){
            $after_balance = $this->getPlayerBalance($player_name);
            $formatted_balance = $this->_convertToDecimalWithoutComma($after_balance);
            $response = $this->_fundTransferResponse(self::TRUE, $game_username, $this->currency, self::ERR_FUNDTRANS_SUCCESS, $formatted_balance);

            $trans = $this->_insertIntoTransactionLogs($data, $response_result_id, $before_balance, $after_balance);
            return $trans;
        } 
        return true;
    }

    private function getPlayerBalance($playerName, $is_locked = true){
        if($this->utils->getConfig('enable_seamless_single_wallet')) {
            $player_id = $this->game_api->getPlayerIdFromUsername($playerName);
            $seamless_balance = 0;
            $seamless_reason_id = null;
            // $seamless_reason_id = null;
            // $seamless_wallet = $this->wallet_model->querySeamlessSingleWallet($player_id, $seamless_balance, $seamless_reason_id);
            // if(!$seamless_wallet) {
            //     return false;
            // }
            // else {
            //     return $seamless_balance;
            // }
            if(!$is_locked){
                $this->lockAndTransForPlayerBalance($player_id, function() use($player_id, &$seamless_balance, &$seamless_reason_id) {
                    return  $this->wallet_model->querySeamlessSingleWallet($player_id, $seamless_balance, $seamless_reason_id);
                });
            } else {
                $this->wallet_model->querySeamlessSingleWallet($player_id, $seamless_balance, $seamless_reason_id);
            }
            // $this->wallet_model->querySeamlessSingleWallet($player_id, $seamless_balance, $seamless_reason_id);
            
            return $seamless_balance;
        }
        else {
            $get_bal_req = $this->game_api->queryPlayerBalance($playerName);
            if($get_bal_req['success']) {
                return $get_bal_req['balance'];
            }
            else {
                return false;
            }
        }
    }

    public function saveResponseResult($success, $apiName, $params, $resultText, $statusCode, $statusText = null, $extra = null, $field = null, $dont_save_response_in_api = false)
    {
        $flag = $success ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;
        if ($field==null) {
            $field=[];
        }

        return $this->response_result->saveResponseResult($this->game_platform_id, $flag, $apiName, json_encode($params), $resultText, $statusCode, $statusText, $extra, $field, $dont_save_response_in_api);
    }

    public function returnText($msg) {
        if($this->transaction_for_fast_track != null && $this->utils->getConfig('enable_fast_track_integration')) {
            $this->sendToFastTrack();
        }
        return parent::returnText($msg);
    }

    private function sendToFastTrack() {
        $this->CI->load->model(['game_description_model']);

        $this->utils->debug_log("HO GAMING: (sendToFastTrack) transaction_for_fast_track", $this->transaction_for_fast_track, $this->requestArray);
        $game_description = $this->game_description_model->getGameDetailsByGameCodeAndGamePlatform($this->game_api->getPlatformCode(), $this->requestArray['@attributes']['tableid']);
        $betType = null;
        switch($this->transaction_for_fast_track['category']) {
            case 'placebet':
                $betType = 'Bet';
                break;
            case 'playerlose':
            case 'playerwin':
                $betType = 'Win';
                break;
            case 'cancelbet':
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
            "amount" => (float) abs($this->transaction_for_fast_track['amt']),
            "balance_after" =>  $this->transaction_for_fast_track['after_balance'],
            "balance_before" =>  $this->transaction_for_fast_track['before_balance'],
            "bonus_wager_amount" =>  0.00,
            "currency" =>  $this->currency,
            "exchange_rate" =>  1,
            "game_id" => isset($game_description) ? $game_description->game_description_id : 'unknown',
            "game_name" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_name)['en'] : 'unknown',
            "game_type" => isset($game_description) ? $this->utils->extractLangJson($game_description->game_type)['en'] : 'unknown',
            "is_round_end" =>  $betType == 'Win' ? true : false,
            "locked_wager_amount" =>  0.00,
            "origin" =>  $_SERVER['HTTP_HOST'],
            "round_id" =>  strval($this->transaction_for_fast_track['gameid']),
            "timestamp" =>  str_replace('+00:00', 'Z', gmdate('c', strtotime('now'))),
            "type" =>  $betType,
            "user_id" => $this->game_api->getPlayerIdInGameProviderAuth($this->transaction_for_fast_track['uname']),
            "vendor_id" =>  strval($this->game_api->getPlatformCode()),
            "vendor_name" =>  $this->external_system->getSystemName($this->game_api->getPlatformCode()),
            "wager_amount" => $betType == 'Bet' ? (float) abs($this->transaction_for_fast_track['amt']) : 0,
        ];

        $this->utils->debug_log("HO GAMING: (sendToFastTrack) data", $data);

        $this->load->library('fast_track');
        $this->fast_track->addToQueue('sendGameLogs', $data);
    }
}
