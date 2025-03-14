<?php

use function PHPSTORM_META\map;

 if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
require_once dirname(__FILE__) . '/BaseController.php';

class Ebet_service_api extends BaseController {

    const RETURN_OK = 200;

    const TYPE_BET = 1;
    const TYPE_PAYOUT = 2;
    const TYPE_REFUND = 3;

    const STATUS_OPEN = "OPEN";
    const STATUS_SETTLED = "SETTLED";
    const STATUS_REFUND = "REFUNDED";
    const STATUS_ERROR = "ERROR";

    const TRANS_TYPE_CREDIT = "credit";
    const TRANS_TYPE_DEBIT = "debit";
    const TRANS_TYPE_REFUND = "refunded";

    const METHOD_BATCH_REFUND = "autoBatchRefund";

    const SUCCESS = [
        'status' => 200,
        'msg' => "Successful"
    ];

    const ERROR_CODE_REPEATED = [
        'status' => 201,
        'msg' => 'Repeated'
    ];

    const ERROR_CODE_CHANNEL_NOT_EXIST = [
        'status' => 202,
        'msg' => 'Channel no exist'
    ];

    const ERROR_CODE_SEQNO_NOT_EXIST = [
        'status' => 206,
        'msg' => 'seqNo no exist'
    ];

    const ERROR_CODE_REFUND_MONEY_INCONSISTENT = [
        'status' => 207,
        'msg' => 'Refund Money Inconsistent'
    ];

    const ERROR_CODE_RECORD_NOT_EXISTED = [
        'status' => 208,
        'msg' => 'Record is not existed'
    ];

    const ERROR_CODE_RECORD_ALREADY_REFUND = [
        'status' => 209,
        'msg' => 'Check record already refund'
    ];

    const ERROR_CODE_USER_PASSWORD_ERROR = [
        'status' => 401,
        'msg' => 'User or password error'
    ];

    const ERROR_CODE_TOKEN_ERROR = [
        'status' => 410,
        'msg' => 'Token error'
    ];

    const ERROR_CODE_SERVER_ERROR = [
        'status' => 500,
        'msg' => 'Server error'
    ];

    const ERROR_CODE_CHANNEL_UNDER_MAINTENANCE = [
        'status' => 505,
        'msg' => 'Channel under maintenance'
    ];

    const ERROR_CODE_NOT_ENOUGH_BALANCE = [
        'status' => 1003,
        'msg' => 'Not enough balance'
    ];

    const ERROR_CODE_SYSTEM_BUSY = [
        'status' => 4003,
        'msg' => 'System busy, try back later'
    ];

    const ERROR_CODE_PARAMETER_ERROR = [
        'status' => 4025,
        'msg' => 'Parameter error'
    ];

    const ERROR_CODE_SIGNATURE_ERROR = [
        'status' => 4026,
        'msg' => 'Signature error'
    ];

    const ERROR_CODE_IP_NOT_AUTHORIZED = [
        'status' => 4027,
        'msg' => 'IP is not authorized'
    ];

    const ERROR_CODE_FUNCTION_DISABLE = [
        'status' => 4029,
        'msg' => 'Function disable'
    ];

    const ERROR_CODE_DATA_TYPE_ERROR = [
        'status' => 4030,
        'msg' => 'Data type error'
    ];

    const ERROR_CODE_USER_NOT_EXISTENCE = [
        'status' => 4037,
        'msg' => 'User not existence'
    ];

    const ERROR_CODE_FREQUENT_REQ = [
        'status' => 4038,
        'msg' => 'Frequent request'
    ];

    const ERROR_CODE_SUB_CHANNEL_NOT_EXIST = [
        'status' => 5003,
        'msg' => 'Sub channel no exist'
    ];

    const BLACKLIST_METHODS = [
        'registerOrLogin',
        'syncCredit',
        'increaseCredit',
        'queryIncreaseCreditRecord',
        'refundSingleWallet',
        'autoBatchRefund'
    ];

    const HTTP_STATUS_CODE_MAP = [
        self::SUCCESS['status']=>200,
        self::ERROR_CODE_IP_NOT_AUTHORIZED['status']=>401,
        self::ERROR_CODE_TOKEN_ERROR['status']=>401,
        self::ERROR_CODE_USER_PASSWORD_ERROR['status']=>401,
        self::ERROR_CODE_CHANNEL_NOT_EXIST['status']=>400,
        self::ERROR_CODE_USER_NOT_EXISTENCE['status']=>400,
        self::ERROR_CODE_SIGNATURE_ERROR['status']=>400,
        self::ERROR_CODE_PARAMETER_ERROR['status']=>400,
        self::ERROR_CODE_NOT_ENOUGH_BALANCE['status']=>400,
        self::ERROR_CODE_SEQNO_NOT_EXIST['status']=>400,
        self::ERROR_CODE_RECORD_NOT_EXISTED['status']=>400,
        self::ERROR_CODE_REPEATED['status']=>400,
        self::ERROR_CODE_REFUND_MONEY_INCONSISTENT['status']=>400,
        self::ERROR_CODE_RECORD_ALREADY_REFUND['status']=>400,
        self::ERROR_CODE_NOT_ENOUGH_BALANCE['status']=>400,
        self::ERROR_CODE_SERVER_ERROR['status']=>500,
        self::ERROR_CODE_CHANNEL_UNDER_MAINTENANCE['status']=>503,
        self::ERROR_CODE_SYSTEM_BUSY['status']=>503
    ];

    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';
    const TRANSACTION_REFUND = 'refunded';


    const TRANSACTION_CANCELLED = 'cancel';


    private $requestHeaders;
    private $requestParams;
    private $api;
    private $currency;
    private $channel_id;
    private $sub_channel;
    private $currentPlayer = [];
    private $wallet_transaction_id = null;
    private $resultCode;

    public $slots_game = array(
        'M4-0012',
        'WHG-0015',
        'WHG-0006',
        'NG-1023',
        'LGP-0003',
        'M4-0006',
        'M4-0026',
        'M4-0035',
        'M4-0039',
        'M4-0041',
        'M4-0068',
        'M4-0069',
        'M4-0072',
        'M4-0076',
        'M4-0077',
        'M4-0082',
        'M4-0086',
        'M4-0089',
        'M4-0099',
        'NG-1033',
        'NG-1032',
        'NG-0008',
        'NG-0063',
        'NG-1000',
        'NG-1003',
        'NG-1004',
        'NG-1012',
        'NG-1013',
        'NG-1014',
        'NG-1017',
        'NG-1019',
        'NG-1021',
        'NG-1022',
        'NG-1025',
        'NG-1026',
        'NG-1010',
        'NG-1001',
        'WHG-0014',
        'WHG-0007',
        'M4-0079',
        'M4-0003',
        'M4-0002',
        'M4-0083',
        'M4-0100',
        'M4-0101',
        'M4-0092',
        'NG-1027',
        'NG-1020',
        'M4-0004',
        'NG-1029',
        'M4-0001',
        'M4-0008',
        'NG-1028',
        'WHG-0008',
        'NG-8080',
        'M4-0005',
        'M4-0040',
        'M4-0047',
        'M4-0054',
        'M4-0071',
        'M4-0084',
        'M4-0093',
        'NG-1031',
        'M4-0051',
        'RGL-0007',
        'RGL-0008',
        'RGL-0005',
        'RGL-0006',
        'WHG-0016',
        'RGL-0010',
        'RGL-0009',
        'M4-0087',
        'RGL-0011',
        'WHG-0020',
        'NG-1035',
        'WHG-0022',
        'WHG-0021'
    );

    public function __construct() {
        parent::__construct();
    }

    public function index($api, $method) {
        $this->api = $this->utils->loadExternalSystemLibObject($api);

        $this->api->rsa->loadKey($this->api->pub_key);

        $this->channel_id = $this->api->channel_id;
        $this->sub_channel = $this->api->sub_channel;
        $this->currency = $this->api->currency;

        $this->requestParams = new stdClass();
        $this->requestParams->function = $method;
        $this->requestParams->params = json_decode(file_get_contents("php://input"), true);

        if(!$this->api) {
            $data = array(
                "status" => self::ERROR_CODE_CHANNEL_UNDER_MAINTENANCE['status'],
                "event" =>  $this->requestParams->params['event'],
                "seqNo" => $this->requestParams->params['seqNo']
            );
            return $this->setResponse(self::ERROR_CODE_CHANNEL_UNDER_MAINTENANCE, $data);

        }

        if($this->api->isMaintenance() || $this->api->isDisabled()) {
            $data = array(
                "status" => self::ERROR_CODE_CHANNEL_UNDER_MAINTENANCE['status'],
                "event" =>  $this->requestParams->params['event'],
                "seqNo" => $this->requestParams->params['seqNo']
            );
            return $this->setResponse(self::ERROR_CODE_CHANNEL_UNDER_MAINTENANCE, $data);
        }

        $this->CI->load->model('ebet_seamless_wallet_transactions', 'ebet_transactions');
        $this->ebet_transactions->tableName = $this->api->original_transaction_table_name;

        if(!method_exists($this, $method)) {
            $this->requestHeaders = $this->input->request_headers();
            $this->utils->debug_log('EBET ' . __METHOD__ , $method . ' method not allowed');

            $data = array(
                "status" => self::ERROR_CODE_PARAMETER_ERROR['status'],
                "event" =>  $this->requestParams->params['event'],
                "seqNo" => $this->requestParams->params['seqNo']
            );

            return $this->setResponse(self::ERROR_CODE_PARAMETER_ERROR, $data);
        }

        if(!method_exists($this, $method)) {
            $data = array(
                "status" => self::ERROR_CODE_PARAMETER_ERROR['status'],
                "event" =>  $this->requestParams->params['event'],
                "seqNo" => $this->requestParams->params['seqNo']
            );

            return $this->setResponse(self::ERROR_CODE_PARAMETER_ERROR, $data);
        }

        return $this->$method();
    }

    /**
     * This api is used to verify user login.
     * eventType = 1: Enter account and password on eBET h5 webpage or App
     * eventType = 3: The user clicks the applink link after logging in on the channel platform
     * eventType = 4: The user clicks the token link after logging in on the channel platform
     */
    public function registerOrLogin(){

        $rule_set = [
            "username" => "required",
            "channelId" => "required|numeric",
            "currency" => "required",
            "ip" => "required",
            "eventType" => "required",
            "event" => "required",
            "timestamp" => "required",
            "sessionToken" => "required",
            "seqNo" => "required",
            "signature" => "required"
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->api->validateWhiteIP()){
            return $this->setResponse(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        /**
         * Signature. String splicing:
         * normal/applink: seqNo+event+channelId+username+timestamp+password
         * token: seqNo+event+channelId+timestamp+username+accessToken
         */

        $plaintext1 = $this->requestParams->params['seqNo'].$this->requestParams->params['event'].$this->requestParams->params['channelId'].$this->requestParams->params['username'].$this->requestParams->params['timestamp'].$this->requestParams->params['password'];
        $plaintext2 = $this->requestParams->params['seqNo'].$this->requestParams->params['event'].$this->requestParams->params['channelId'].$this->requestParams->params['timestamp'].$this->requestParams->params['username'].$this->requestParams->params['accessToken'];

        if(isset($this->requestParams->params['accessToken']) && $this->requestParams->params['accessToken'] != ""){
            $verify = $this->verifySignature($plaintext2, $this->requestParams->params['signature']);
        }else{
            $verify = $this->verifySignature($plaintext1, $this->requestParams->params['signature']);
        }

        if($verify != 'verified'){
            $data = [
                "status" => self::ERROR_CODE_SIGNATURE_ERROR['status'],
                "event" => $this->requestParams->params['event'],
                "seqNo" => $this->requestParams->params['seqNo']
            ];

            return $this->setResponse(self::ERROR_CODE_SIGNATURE_ERROR, $data);
        }else{
            $data = [
                "accessToken" => isset($this->requestParams->params['accessToken']) ? $this->requestParams->params['accessToken'] : "",
                "subChannelId" => $this->sub_channel,
                "username" => $this->requestParams->params['username'],
                "sessionToken" => $this->requestParams->params['sessionToken'],
                "currency" => $this->requestParams->params['currency'],
                "status" => self::SUCCESS,
                "event" => $this->requestParams->params['event'],
                "seqNo" => $this->requestParams->params['seqNo']
            ];

            return $this->setResponse(self::SUCCESS, $data);
        }

    }

    /**
     * This api is to confirm the user's current amount of money.
     */
    public function syncCredit() {
        $this->CI->load->model('common_token');

        $rule_set = [
            "channelId" => "required|numeric",
            "username" => "required",
            "tableType" => "required",
            "currency" => "required",
            "event" => "required",
            "timestamp" => "required",
            "sessionToken" => "required",
            "seqNo" => "required"
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->api->validateWhiteIP()){
            return $this->setResponse(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        $player_info = $this->currentPlayer;

        if(!empty($player_info)) {
            $data = [
                "username" => $this->requestParams->params['username'],
                "money" => $this->api->queryPlayerBalance($player_info['username'])['balance'],
                "currency" => $this->requestParams->params['currency'],
                "event" => $this->requestParams->params['event'],
                "seqNo" => $this->requestParams->params['seqNo'],
                "timestamp" => $this->requestParams->params['timestamp']
            ];

            return $this->setResponse(self::SUCCESS, $data);
        }else{
            $data = [
                "username" => $this->requestParams->params['username'],
                "money" => 0,
                "currency" => $this->requestParams->params['currency'],
                "event" => $this->requestParams->params['event'],
                "seqNo" => $this->requestParams->params['seqNo'],
                "timestamp" => $this->requestParams->params['timestamp']
            ];

            return $this->setResponse(self::ERROR_CODE_USER_NOT_EXISTENCE, $data);
        }
    }

    /**
     * This API is a notification channel for changing player money.
     * Note 1: seqNo is unique value. Avoid repeated processing of money, do not record the same payout seqNo.
     * Note 2: We have a retry mechanism. If it has been processed successfully before, please return 201 (seqNo repeated).
     * Note 3: If the network occurs or your system returns a system error and the system is busy, we will try to resend 3 times.
     *
     * Type 1 - Bet
     * Type 2 - Payout
     */

    public function increaseCredit(){
        $this->CI->load->model('common_seamless_wallet_transactions', 'wallet_transactions');

        $rule_set = [
            'username' => 'required',
            'channelId' => 'required|numeric',
            'money' => 'required|numeric',
            'type' => 'required|numeric',
            'currency' => 'required',
            'seqNo' => 'required',
            'detail' => 'required',
            'event' => 'required',
            'timestamp' => 'required',
            'sessionToken' => 'required',
            'signature' => 'required'
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->api->validateWhiteIP()){
            return $this->setResponse(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        $player_info = $this->currentPlayer;

        $transaction_data = [];

        #Signature. String splicing : seqNo+event+channelId+timestamp+username+money
        $plaintext = $this->requestParams->params['seqNo'].$this->requestParams->params['event'].$this->requestParams->params['channelId'].$this->requestParams->params['timestamp'].$this->requestParams->params['username'].$this->requestParams->params['money'];

        $verify = $this->verifySignature($plaintext, $this->requestParams->params['signature']);

        if($verify != 'verified'){
            $data = [
                "status" => self::ERROR_CODE_SIGNATURE_ERROR['status'],
                "event" => $this->requestParams->params['event'],
                "seqNo" => $this->requestParams->params['seqNo']
            ];

            return $this->setResponse(self::ERROR_CODE_SIGNATURE_ERROR, $data);
        }else{
            if(!empty($player_info)) {
                $controller = $this;

                $bet_list  = $controller->requestParams->params;

                $transaction_data['moneyBefore'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

                //Lock Balance
                $transaction_result = $this->lockAndTransForPlayerBalance($player_info['player_id'], function() use($controller, $player_info, &$bet_list) {

                    if($controller->requestParams->params['type'] == self::TYPE_BET){

                        $existing_transaction = $this->ebet_transactions->searchByExternalTransactionIdBySeqNo($bet_list['seqNo']);

                        $this->utils->debug_log('EBET_SEAMLESS_BET ', 'BET EXISITNG: ', $existing_transaction);

                        if(!empty($existing_transaction)){
                            $controller->resultCode = self::ERROR_CODE_REPEATED;
                            return false;
                        }else{
                            $current_balance = $this->api->queryPlayerBalance($player_info['username'])['balance'];

                            $this->utils->debug_log('EBET_SEAMLESS ', 'ADJUST_WALLET_DEBIT_TAL: ', $existing_transaction);

                            $bet_amount = $bet_list['detail']['totalBet'];

                            #If NOT Enough Balance, this is needed to add in the database for monitoring (needed by GP)
                            if($current_balance < $bet_amount) {
                                $this->utils->debug_log('EBET ' . __METHOD__, "Not enough balance");

                                $controller->resultCode = self::ERROR_CODE_NOT_ENOUGH_BALANCE;
                                return false;

                            }else{
                                if(isset($bet_list['detail']['betList'])){
                                    $bet_id =  $bet_list['detail']['roundCode']."-".$player_info['game_username'];
                                    $bet_id_string = strlen($bet_id);

                                    if($bet_id_string > 50){
                                        $bet_id = substr($bet_id,0,50);
                                    }
                                    $transaction_data['bet_id'] =  $bet_id;
                                    $transaction_data['external_unique_id'] =  $bet_list['seqNo'];

                                    $transaction_data['bet_amount'] =  $bet_list['money']; //$val['betMoney'];
                                    $transaction_data['bet_type'] =  NULL; //$val['betType'];

                                    $transaction_data['seqNo'] = $bet_list['seqNo'];
                                    $transaction_data['game_id'] = $bet_list['detail']['tableCode'];
                                    $transaction_data['round_id'] = $bet_list['detail']['roundCode']."-".$player_info['game_username'];
                                    $transaction_data['bet_time'] = $bet_list['detail']['betTime'];
                                    $transaction_data['type'] = $bet_list['type'];
                                    $transaction_data['extra_info'] = json_encode($bet_list['detail']['betList']);

                                    $adjustWallet = $controller->adjustWallet(self::TRANSACTION_DEBIT, $player_info, $transaction_data);
                                    $this->utils->debug_log('EBET_SEAMLESS ', 'ADJUST_WALLET_DEBIT_TAL: ', $adjustWallet);
                                }else{
                                    $bet_id =  $bet_list['detail']['roundCode']."-".$player_info['game_username'];
                                    $bet_id_string = strlen($bet_id);

                                    if($bet_id_string > 50){
                                        $bet_id = substr($bet_id,0,50);
                                    }
                                    $transaction_data['bet_id'] =  $bet_id;
                                    $transaction_data['external_unique_id'] =  $bet_list['seqNo'];

                                    $transaction_data['bet_amount'] =   $bet_list['money'];
                                    $transaction_data['bet_type'] =  $bet_list['detail']['tableType'];

                                    $transaction_data['seqNo'] = $bet_list['seqNo'];
                                    $transaction_data['game_id'] = $bet_list['detail']['tableCode'];
                                    $transaction_data['round_id'] = $bet_list['detail']['roundCode']."-".$player_info['game_username'];
                                    $transaction_data['bet_time'] = $bet_list['detail']['betTime'];
                                    $transaction_data['type'] = $bet_list['type'];
                                    $transaction_data['extra_info'] = json_encode($bet_list['detail']);

                                    $adjustWallet = $controller->adjustWallet(self::TRANSACTION_DEBIT, $player_info, $transaction_data);
                                    $this->utils->debug_log('EBET_SEAMLESS ', 'ADJUST_WALLET_DEBIT_TAL: ', $adjustWallet);
                                }

                            }
                        }

                    }else{
                        #PAYOUT
                        $existing_seqNo = $this->ebet_transactions->searchByExternalTransactionIdBySeqNo($bet_list['seqNo']);

                        #Check if seqNo is already existing
                        if(!empty($existing_seqNo)){
                            $controller->resultCode = self::ERROR_CODE_REPEATED;
                            return false;
                        }else{
                            #This is for MULTIPLE transactions PAYOUT
                            if(isset($bet_list['detail']['betList'])) {

                                $round_id =  $bet_list['detail']['roundCode']."-".$player_info['game_username'];
                                $round_id_length = strlen($round_id);

                                if($round_id_length > 50){
                                    $round_id = substr($round_id,0,50);
                                }

                                #Check if round_id is already existing and status is open
                                $round_open_bet_transcction = $this->ebet_transactions->searchByExternalTransactionByRoundIdAndStatus( $round_id, self::STATUS_OPEN);
                                $round_settled_bet_transcction = $this->ebet_transactions->searchByExternalTransactionByRoundIdAndStatus($round_id, self::STATUS_SETTLED);
                                $round_refund_bet_transcction = $this->ebet_transactions->searchByExternalTransactionByRoundIdAndStatus($round_id, self::STATUS_REFUND);

                                    if(!empty($round_open_bet_transcction)){
                                        $bet_id =  $bet_list['detail']['roundCode']."-".$player_info['game_username'];
                                        $bet_id_string = strlen($bet_id);

                                        if($bet_id_string > 50){
                                            $bet_id = substr($bet_id,0,50);
                                        }

                                        $transaction_data['bet_id'] =  $bet_id;
                                        $transaction_data['external_unique_id'] = $bet_list['seqNo'];

                                        $transaction_data['bet_amount'] =  0;
                                        $transaction_data['payout'] =  $bet_list['money'];
                                        $transaction_data['odds'] =  null;
                                        $transaction_data['validBet'] =  null;
                                        $transaction_data['bet_type'] =  null;

                                        $transaction_data['seqNo'] = $bet_list['seqNo'];
                                        $transaction_data['game_id'] = $bet_list['detail']['tableCode'];
                                        $transaction_data['round_id'] = $bet_list['detail']['roundCode']."-".$player_info['game_username'];
                                        $transaction_data['bet_time'] = $bet_list['detail']['betTime'];
                                        $transaction_data['payout_time'] = $bet_list['detail']['payoutTime'];
                                        $transaction_data['type'] = $bet_list['type'];

                                        $transaction_data['extra_info'] = json_encode($bet_list['detail']['betList']);

                                        if ($bet_list['money'] < 0){ //check if negative value
                                            $controller->resultCode = self::ERROR_CODE_PARAMETER_ERROR;
                                            return false;
                                        }else{
                                            $adjustWallet = $controller->adjustWallet(self::TRANSACTION_CREDIT, $player_info, $transaction_data);
                                            $this->utils->debug_log('EBET_SEAMLESS ', 'ADJUST_WALLET_DEBIT_TAL_CREDIT: ', $adjustWallet);
                                        }

                                    }else if(!empty($round_settled_bet_transcction)){
                                        $same_seqno_transcction = $this->ebet_transactions->searchByExternalTransactionBySeqNo($round_id, $bet_list['seqNo']);

                                        if(!empty($same_seqno_transcction)){
                                            $controller->resultCode = self::ERROR_CODE_REPEATED;
                                            return false;
                                        }else{
                                            $bet_id =  $bet_list['detail']['roundCode']."-".$player_info['game_username'];
                                            $bet_id_string = strlen($bet_id);

                                            if($bet_id_string > 50){
                                                $bet_id = substr($bet_id,0,50);
                                            }

                                            $transaction_data['bet_id'] =  $bet_id;
                                            $transaction_data['external_unique_id'] = $bet_list['seqNo'];

                                            $transaction_data['bet_amount'] =  0;
                                            $transaction_data['payout'] =  $bet_list['money'];
                                            $transaction_data['odds'] =  null;
                                            $transaction_data['validBet'] =  null;
                                            $transaction_data['bet_type'] =  null;

                                            $transaction_data['seqNo'] = $bet_list['seqNo'];
                                            $transaction_data['game_id'] = $bet_list['detail']['tableCode'];
                                            $transaction_data['round_id'] = $bet_list['detail']['roundCode']."-".$player_info['game_username'];
                                            $transaction_data['bet_time'] = $bet_list['detail']['betTime'];
                                            $transaction_data['payout_time'] = $bet_list['detail']['payoutTime'];
                                            $transaction_data['type'] = $bet_list['type'];

                                            $transaction_data['extra_info'] = json_encode($bet_list['detail']['betList']);

                                            if ($bet_list['money'] < 0){ //check if negative value
                                                $controller->resultCode = self::ERROR_CODE_PARAMETER_ERROR;
                                                return false;
                                            }else{
                                                $adjustWallet = $controller->adjustWallet(self::TRANSACTION_CREDIT, $player_info, $transaction_data);
                                                $this->utils->debug_log('EBET_SEAMLESS ', 'ADJUST_WALLET_DEBIT_TAL_CREDIT: ', $adjustWallet);
                                            }
                                        }
                                    }else if(!empty($round_refund_bet_transcction)){
                                        $controller->resultCode = self::ERROR_CODE_RECORD_ALREADY_REFUND;
                                        return false;
                                    }else{
                                        $controller->resultCode = self::ERROR_CODE_RECORD_NOT_EXISTED;
                                        return false;
                                    }

                            }else{
                                $round_id =  $bet_list['detail']['roundCode']."-".$player_info['game_username'];
                                $round_id_length = strlen($round_id);

                                if($round_id_length > 50){
                                    $round_id = substr($round_id,0,50);
                                }

                                $round_open_bet_transcction = $this->ebet_transactions->searchByExternalTransactionByRoundIdAndStatus($round_id, self::STATUS_OPEN);
                                $round_settled_bet_transcction = $this->ebet_transactions->searchByExternalTransactionByRoundIdAndStatus($round_id, self::STATUS_SETTLED);
                                $round_refund_bet_transcction = $this->ebet_transactions->searchByExternalTransactionByRoundIdAndStatus($round_id, self::STATUS_REFUND);

                                if(!empty($round_open_bet_transcction)){
                                    $bet_id =  $bet_list['detail']['roundCode']."-".$player_info['game_username'];
                                    $bet_id_string = strlen($bet_id);

                                    if($bet_id_string > 50){
                                        $bet_id = substr($bet_id,0,50);
                                    }

                                    $transaction_data['bet_id'] =  $bet_id;
                                    $transaction_data['external_unique_id'] =  $bet_list['seqNo'];

                                    $transaction_data['bet_amount'] = 0;
                                    $transaction_data['payout'] =  $bet_list['money'];
                                    $transaction_data['odds'] =  null;
                                    $transaction_data['validBet'] =  null;
                                    $transaction_data['bet_type'] =  null;

                                    $transaction_data['seqNo'] = $bet_list['seqNo'];
                                    $transaction_data['game_id'] = $bet_list['detail']['tableCode'];
                                    $transaction_data['round_id'] = $bet_list['detail']['roundCode']."-".$player_info['game_username'];
                                    $transaction_data['bet_time'] = $bet_list['detail']['betTime'];
                                    $transaction_data['payout_time'] = $bet_list['detail']['payoutTime'];
                                    $transaction_data['type'] = $bet_list['type'];

                                    $transaction_data['extra_info'] = json_encode($bet_list['detail']);

                                    if ($bet_list['detail']['payout'] < 0){ //check if negative value
                                        $controller->resultCode = self::ERROR_CODE_PARAMETER_ERROR;
                                        return false;
                                    }else{
                                        $adjustWallet = $controller->adjustWallet(self::TRANSACTION_CREDIT, $player_info, $transaction_data);
                                        $this->utils->debug_log('EBET_SEAMLESS ', 'ADJUST_WALLET_DEBIT_TAL_CREDIT: ', $adjustWallet);
                                    }

                                }else if(!empty($round_settled_bet_transcction)){
                                    $controller->resultCode = self::ERROR_CODE_REPEATED;
                                    return false;
                                }else if(!empty($round_refund_bet_transcction)){
                                    $controller->resultCode = self::ERROR_CODE_RECORD_ALREADY_REFUND;
                                    return false;
                                }else{
                                    $controller->resultCode = self::ERROR_CODE_RECORD_NOT_EXISTED;
                                    return false;
                                }
                            }

                        }

                    }

                    return true;

                });

                if($transaction_result) {
                    $transaction_data['moneyAfter'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

                    $this->utils->debug_log('EBET_SEAMLESS ', 'TRANSACTION_DATA: ', $transaction_data);

                    if(!empty($controller->resultCode)){
                        switch ($controller->resultCode['status']) {
                            case self::ERROR_CODE_NOT_ENOUGH_BALANCE['status']:
                                $statusCode = self::ERROR_CODE_NOT_ENOUGH_BALANCE['status'];
                            break;
                            case self::ERROR_CODE_PARAMETER_ERROR['status']:
                                $statusCode = self::ERROR_CODE_PARAMETER_ERROR['status'];
                            break;
                            case self::ERROR_CODE_REPEATED['status']:
                                $statusCode = self::ERROR_CODE_REPEATED['status'];
                                break;
                            case self::ERROR_CODE_RECORD_ALREADY_REFUND['status']:
                                $statusCode = self::ERROR_CODE_RECORD_ALREADY_REFUND;
                                break;
                            case self::ERROR_CODE_RECORD_NOT_EXISTED['status']:
                                $statusCode = self::ERROR_CODE_RECORD_NOT_EXISTED['status'];
                                break;
                            default:
                                $statusCode = self::ERROR_CODE_SYSTEM_BUSY['status'];
                            break;
                        }

                        $data = array(
                            "username" => $this->requestParams->params['username'],
                            "status" => $statusCode,
                            "event" =>  $this->requestParams->params['event'],
                            "seqNo" => $this->requestParams->params['seqNo']
                        );

                        if(!empty($controller->resultCode)){
                            return $this->setResponse($controller->resultCode, $data);
                        }else{
                            return $this->setResponse(self::ERROR_CODE_SYSTEM_BUSY, $data);
                        }

                    }else{
                        $data = array(
                            "username" => $this->requestParams->params['username'],
                            "money" => $transaction_data['moneyAfter'],
                            "moneyBefore" => $transaction_data['moneyBefore'],
                            "status" => self::SUCCESS['status'],
                            "event" =>  $this->requestParams->params['event'],
                            "seqNo" => $this->requestParams->params['seqNo']
                            );

                        return $this->setResponse(self::SUCCESS, $data);
                    }

                }else {
                    $this->utils->debug_log('EBET_SEAMLESS ', 'TRANSACTION_DATA_ERROR: ', $transaction_data);

                    $statusCode = "";

                    switch ($controller->resultCode['status']) {
                        case self::ERROR_CODE_NOT_ENOUGH_BALANCE['status']:
                            $statusCode = self::ERROR_CODE_NOT_ENOUGH_BALANCE['status'];
                        break;
                        case self::ERROR_CODE_PARAMETER_ERROR['status']:
                            $statusCode = self::ERROR_CODE_PARAMETER_ERROR['status'];
                        break;
                        case self::ERROR_CODE_REPEATED['status']:
                            $statusCode = self::ERROR_CODE_REPEATED['status'];
                            break;
                        case self::ERROR_CODE_RECORD_ALREADY_REFUND['status']:
                            $statusCode = self::ERROR_CODE_RECORD_ALREADY_REFUND;
                            break;
                        case self::ERROR_CODE_RECORD_NOT_EXISTED['status']:
                            $statusCode = self::ERROR_CODE_RECORD_NOT_EXISTED['status'];
                            break;
                        default:
                            $statusCode = self::ERROR_CODE_SYSTEM_BUSY['status'];
                        break;
                    }

                    $data = array(
                        "username" => $this->requestParams->params['username'],
                        "status" => $statusCode,
                        "event" =>  $this->requestParams->params['event'],
                        "seqNo" => $this->requestParams->params['seqNo']
                    );

                    if(!empty($controller->resultCode)){
                        return $this->setResponse($controller->resultCode, $data);
                    }else{
                        return $this->setResponse(self::ERROR_CODE_SYSTEM_BUSY, $data);
                    }

                }

            }
        }
    }

    /**
     * This API is for querying the processing status of increaseCredit in the channel's database.
     */
    public function queryIncreaseCreditRecord(){
        $this->CI->load->model('common_token');

        $rule_set = [
            "channelId" => "required|numeric",
            "username" => "required",
            "querySeqNo" => "required",
            "roundCode" => "required",
            "event" => "required",
            "timestamp" => "required",
            "sessionToken" => "required",
            "seqNo" => "required",
            "signature" => "required"
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->api->validateWhiteIP()){
            return $this->setResponse(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        $player_info = $this->currentPlayer;

        #Signature. String splicing: username+timestamp
        $plaintext = $this->requestParams->params['username'].$this->requestParams->params['timestamp'];

        $this->utils->debug_log('EBET_PLAINTEXT ', 'In queryIncreaseCreditRecord:', $plaintext);

        $verify = $this->verifySignature($plaintext, $this->requestParams->params['signature']);

        if($verify != 'verified'){
            $data = [
                "status" => self::ERROR_CODE_SIGNATURE_ERROR['status'],
                "event" => $this->requestParams->params['event'],
                "seqNo" => $this->requestParams->params['seqNo']
            ];

            return $this->setResponse(self::ERROR_CODE_SIGNATURE_ERROR, $data);
        }else{
            if(!empty($player_info)) {


                $record = $this->ebet_transactions->searchTransactionBySeqNoAndRoundId($this->requestParams->params['querySeqNo'], $this->requestParams->params['roundCode']);

                if(!empty($record)){

                    $data = array(
                        "seqNo" => $this->requestParams->params['seqNo'],
                        "event" => $this->requestParams->params['event'],
                        "timestamp" => $this->requestParams->params['timestamp'],
                        "username" => $this->requestParams->params['username'],
                    );

                    $creditRecord = [];

                    foreach($record as $r => $val){
                        $crecord = array(
                            "querySeqNo" => $val['seqNo'],
                            "type" => ($val['transaction_type'] == self::TRANSACTION_DEBIT) ? self::TYPE_BET :  self::TYPE_PAYOUT,
                            "username" => $this->requestParams->params['username'],
                            "roundCode" => $val['round_id'],
                            "status" => $val['response_status'],
                            "creditTime" => $val['end_at'],
                            "moneyBefore" => $val['before_balance'],
                            "moneyAfter" => $val['after_balance'],
                            "money" => $val['amount'],

                        );
                        array_push($creditRecord, $crecord);

                    }

                    $data['creditRecord'] = $creditRecord;

                    return $this->setResponse(self::SUCCESS, $data);

                }else{
                    $data = array(
                        "seqNo" => $this->requestParams->params['seqNo'],
                        "event" => $this->requestParams->params['event'],
                    );
                    return $this->setResponse(self::ERROR_CODE_RECORD_NOT_EXISTED, $data);
                }




            }else{
                $data = [
                    "event" => $this->requestParams->params['event'],
                    "seqNo" => $this->requestParams->params['seqNo']
                ];
                return $this->setResponse(self::ERROR_CODE_USER_NOT_EXISTENCE, $data);
            }
        }


    }

    /**
     * This API is eBET's manual refund request for failed bets.
     */
    public function refundSingleWallet(){
        $this->CI->load->model('common_token');

        $rule_set = [
            "channelId" => "required|numeric",
            "username" => "required",
            "refundList" => "required",
            "roundCode" => "required",
            "seqNo" => "required",
            "currency" => "required",
            "event" => "required",
            "timestamp" => "required",
            "sessionToken" => "required",
            "signature" => "required",
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->api->validateWhiteIP()){
            return $this->setResponse(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        $player_info = $this->currentPlayer;

        #Signature. String splicing: username+timestamp
        $plaintext = $this->requestParams->params['username'].$this->requestParams->params['timestamp'];

        $this->utils->debug_log('EBET_PLAINTEXT ', 'In refundSingleWallet:', $plaintext);

        $verify = $this->verifySignature($plaintext, $this->requestParams->params['signature']);

        if($verify != 'verified'){
            $data = [
                "status" => self::ERROR_CODE_SIGNATURE_ERROR['status'],
                "event" => $this->requestParams->params['event'],
                "seqNo" => $this->requestParams->params['seqNo']
            ];

            return $this->setResponse(self::ERROR_CODE_SIGNATURE_ERROR, $data);
        }else{
            if(!empty($player_info)) {

                //Checked if refund seqno is existing
                $existing_refund_transaction = $this->ebet_transactions->searchByExternalTransactionIdBySeqNo($this->requestParams->params['seqNo']);
                $transaction_data['moneyBefore'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

                if(!empty($existing_refund_transaction)){
                    //Refunded already
                    $data = [
                        "event" => $this->requestParams->params['event'],
                        "seqNo" => $this->requestParams->params['seqNo'],
                    ];

                    return $this->setResponse(self::ERROR_CODE_RECORD_ALREADY_REFUND, $data);

                }else{
                    $controller = $this;

                    $refund_details  = $controller->requestParams->params;

                    $transaction_data['before_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

                    //Lock balance
                    $transaction_result = $this->lockAndTransForPlayerBalance($player_info['player_id'], function() use($controller, $player_info, &$refund_details) {

                        $round_id =  $refund_details['roundCode']."-".$player_info['game_username'];
                        $round_id_length = strlen($round_id);

                        if($round_id_length > 50){
                            $round_id = substr($round_id,0,50);
                        }

                        $round_open_bet_transaction = $this->ebet_transactions->searchByExternalTransactionByRoundIdAndStatus($round_id, self::STATUS_OPEN);
                        $round_settled_bet_transaction = $this->ebet_transactions->searchByExternalTransactionByRoundIdAndStatus($round_id, self::STATUS_SETTLED);
                        $round_refund_bet_transaction = $this->ebet_transactions->searchByExternalTransactionByRoundIdAndStatus($round_id, self::STATUS_REFUND);

                        if(!empty($round_open_bet_transaction)){
                            $existing_bet_seqno = $this->ebet_transactions->searchByExternalTransactionIdBySeqNo($refund_details['refundList'][0]['refundSeqNo']);

                            if(!empty($existing_bet_seqno)){
                                $transaction_data['bet_id'] =  $refund_details['roundCode']."-".$player_info['game_username'];
                                $transaction_data['external_unique_id'] =  $refund_details['seqNo'];
                                $transaction_data['seqNo'] =  $refund_details['seqNo'];
                                $transaction_data['refund_money'] = $refund_details['refundList'][0]['refundMoney'] * -1; //make it positive
                                $transaction_data['round_id'] = $refund_details['roundCode']."-".$player_info['game_username'];
                                $transaction_data['timestamp'] = $refund_details['timestamp'];
                                $transaction_data['game_username'] = $refund_details['refundList'][0]['username'];

                                $transaction_data['type'] = self::TYPE_REFUND;

                                if ($refund_details['refundList'][0]['refundMoney'] > 0){ //check if negative value
                                    $controller->resultCode = self::ERROR_CODE_PARAMETER_ERROR;
                                    return false;
                                }else{
                                    $adjustWallet = $controller->adjustWallet(self::TRANSACTION_REFUND, $player_info, $transaction_data);
                                    $this->utils->debug_log('EBET_SEAMLESS ', 'ADJUST_WALLET_REFUND: ', $adjustWallet);

                                    return true;
                                }
                            }else{
                                $controller->resultCode = self::ERROR_CODE_RECORD_NOT_EXISTED;
                                return false;
                            }
                        }else if(!empty($round_settled_bet_transaction)){
                            $controller->resultCode = self::ERROR_CODE_RECORD_ALREADY_REFUND;
                            return false;
                        }else if(!empty($round_refund_bet_transaction)){
                            $controller->resultCode = self::ERROR_CODE_RECORD_ALREADY_REFUND;
                            return false;
                        }else{
                            $controller->resultCode = self::ERROR_CODE_RECORD_NOT_EXISTED;
                            return false;
                        }

                    });

                    $this->utils->debug_log('EBET_SEAMLESS ', 'ADJUST_WALLET_REFUND: ', $transaction_result);

                    if($transaction_result){
                        $transaction_data['moneyAfter'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

                        $refund_list = [];

                        $get_refund_details = $this->ebet_transactions->searchTransactionBySeqNoAndType($this->requestParams->params['seqNo'], self::TRANSACTION_REFUND);

                        $total_refund = isset($get_refund_details[0]['refund_money']) ? intval($get_refund_details[0]['refund_money']) : intval($get_refund_details['refund_money']);

                        foreach($get_refund_details as $r => $val){

                            $rrecord = array(
                                "refundSeqNo" => $val['transaction_id'],
                                "status" => $val['response_status'],
                            );
                            array_push($refund_list, $rrecord);
                        }

                        $data = array(
                            "seqNo" => $this->requestParams->params['seqNo'],
                            "event" => $this->requestParams->params['event'],
                            "timestamp" => $this->requestParams->params['timestamp'],
                            "username" => $this->requestParams->params['username'],
                            "moneyAfter" => $transaction_data['moneyAfter'],
                            "moneyBefore" =>  $transaction_data['moneyBefore'],
                            "refundMoney" => $total_refund,
                            "resultList"=> $refund_list,
                            "status" => self::RETURN_OK
                        );

                        return $this->setResponse(self::SUCCESS, $data);
                    }else{
                        $statusCode = "";

                        switch ($controller->resultCode['status']) {
                            case self::ERROR_CODE_NOT_ENOUGH_BALANCE['status']:
                                $statusCode = self::ERROR_CODE_NOT_ENOUGH_BALANCE['status'];
                              break;
                            case self::ERROR_CODE_PARAMETER_ERROR['status']:
                                $statusCode = self::ERROR_CODE_PARAMETER_ERROR['status'];
                              break;
                            case self::ERROR_CODE_REPEATED['status']:
                                $statusCode = self::ERROR_CODE_REPEATED['status'];
                                break;
                            case self::ERROR_CODE_RECORD_ALREADY_REFUND['status']:
                                $statusCode = self::ERROR_CODE_RECORD_ALREADY_REFUND;
                                break;
                            case self::ERROR_CODE_RECORD_NOT_EXISTED['status']:
                                $statusCode = self::ERROR_CODE_RECORD_NOT_EXISTED['status'];
                                break;
                            default:
                                $statusCode = self::ERROR_CODE_SYSTEM_BUSY['status'];
                              break;
                          }

                        $data = array(
                            "username" => $this->requestParams->params['username'],
                            "status" => $statusCode,
                            "event" =>  $this->requestParams->params['event'],
                            "seqNo" => $this->requestParams->params['seqNo']
                        );

                        if(!empty($controller->resultCode)){
                            return $this->setResponse($controller->resultCode, $data);
                        }else{
                            return $this->setResponse(self::ERROR_CODE_SYSTEM_BUSY, $data);
                        }

                    }

                }

            }else{
                $data = [
                    "event" => $this->requestParams->params['event'],
                    "seqNo" => $this->requestParams->params['seqNo'],
                ];

                return $this->setResponse(self::ERROR_CODE_USER_NOT_EXISTENCE, $data);
            }
        }

    }

    /**
    * Regularly check the records of failed bets and send them to the channel to process refunds in batches.
    * Note 1.Parameter detail may contain plural betId/betType/money
    * Note 2.Parameter detail > money total is refundMoney
    * Note 3.Only refund bet fail but payout success record
    */
    public function autoBatchRefund(){
        $this->CI->load->model('common_token');

        $rule_set = [
            "channelId" => "required|numeric",
            "timestamp" => "required",
            "currency" => "required",
            "event" => "required",
            "seqNo" => "required"
        ];

        $this->preProcessRequest(__FUNCTION__, $rule_set);

        if(!$this->api->validateWhiteIP()){
            return $this->setResponse(self::ERROR_CODE_IP_NOT_AUTHORIZED);
        }

        #Signature. String splicing: channelId+timestamp
        $plaintext = $this->requestParams->params['channelId'].$this->requestParams->params['timestamp'];

        $this->utils->debug_log('EBET_PLAINTEXT ', 'In autoBatchRefund:', $plaintext);

        $verify = $this->verifySignature($plaintext, $this->requestParams->params['signature']);

        if($verify != 'verified'){
            $data = [
                "status" => self::ERROR_CODE_SIGNATURE_ERROR['status'],
                "event" => $this->requestParams->params['event'],
                "seqNo" => $this->requestParams->params['seqNo']
            ];

            return $this->setResponse(self::ERROR_CODE_SIGNATURE_ERROR, $data);
        }else{
            #Check if autobatchrefund seqno is existing
            $existing_autobatchrefund_seqno = $this->ebet_transactions->searchByExternalTransactionIdBySeqNo($this->requestParams->params['seqNo']);

            if(!empty($existing_autobatchrefund_seqno)){
                //Refunded already
                $data = [
                    "event" => $this->requestParams->params['event'],
                    "seqNo" => $this->requestParams->params['seqNo'],
                ];

                return $this->setResponse(self::ERROR_CODE_RECORD_ALREADY_REFUND, $data);
            }else{
                $controller = $this;

                $refund_details  = $controller->requestParams->params;
                $batch_refund_details = $refund_details['batchRefundList'];

                foreach($batch_refund_details as $brefund => $bval){

                    $player_details = $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($bval['username'], $this->api->getPlatformCode());

                    if(!empty($player_details)){ //check if player is existing

                            $player_details_arr = get_object_vars($player_details);

                            $transaction_data['before_balance'] = $this->api->queryPlayerBalance($player_details_arr['username'])['balance'];

                            $details = $batch_refund_details[$brefund];

                            //Lock balance
                            $transaction_result = $this->lockAndTransForPlayerBalance($player_details_arr['player_id'], function() use($controller, $player_details_arr, &$details) {

                                $round_id =  $details['roundCode']."-".$player_details_arr['game_username'];
                                $round_id_length = strlen($round_id);

                                if($round_id_length > 50){
                                    $round_id = substr($round_id,0,50);
                                }

                                $round_open_bet_transaction = $this->ebet_transactions->searchByExternalTransactionByRoundIdAndStatus($round_id, self::STATUS_OPEN);
                                $round_settled_bet_transaction = $this->ebet_transactions->searchByExternalTransactionByRoundIdAndStatus($round_id, self::STATUS_SETTLED);
                                $round_refund_bet_transaction = $this->ebet_transactions->searchByExternalTransactionByRoundIdAndStatus($round_id, self::STATUS_REFUND);

                                if(!empty($round_open_bet_transaction)){

                                    $transaction_data['bet_id'] =  $details['roundCode']."-".$player_details_arr['game_username'];
                                    $transaction_data['external_unique_id'] =  $this->requestParams->params['seqNo'].'-'.$details['roundCode']; // use seqno of refund
                                    $transaction_data['seqNo'] =  $this->requestParams->params['seqNo'];
                                    $transaction_data['refund_money'] = $details['refundMoney'];
                                    $transaction_data['round_id'] = $details['roundCode']."-".$player_details_arr['game_username'];
                                    $transaction_data['timestamp'] = $this->requestParams->params['timestamp'];
                                    $transaction_data['extra_info'] = json_encode($details['detail']);
                                    $transaction_data['game_username'] = $player_details_arr['game_username'];

                                    $transaction_data['type'] = self::TYPE_REFUND;

                                    if ($details['refundMoney'] < 0){ //check if negative value
                                        $controller->resultCode = self::ERROR_CODE_PARAMETER_ERROR;
                                        return false;
                                    }else{
                                        $adjustWallet = $controller->adjustWallet(self::TRANSACTION_REFUND, $player_details_arr, $transaction_data);
                                        $this->utils->debug_log('EBET_SEAMLESS ', 'ADJUST_WALLET: ', $adjustWallet);

                                        return true;
                                    }

                                }else if(!empty($round_settled_bet_transaction)){
                                    $controller->resultCode = self::ERROR_CODE_RECORD_ALREADY_REFUND;
                                    return false;
                                }else if($round_refund_bet_transaction){
                                    $controller->resultCode = self::ERROR_CODE_RECORD_ALREADY_REFUND;
                                    return false;
                                }else{
                                    $controller->resultCode = self::ERROR_CODE_RECORD_NOT_EXISTED;
                                    return false;
                                }

                            });


                    }else{
                        $data = [
                            "event" => $this->requestParams->params['event'],
                            "seqNo" => $this->requestParams->params['seqNo'],
                        ];

                        return $this->setResponse(self::ERROR_CODE_USER_NOT_EXISTENCE, $data);
                    }

                }

                if($transaction_result){

                    $get_refund_details = $this->ebet_transactions->searchTransactionBySeqNoAndType($this->requestParams->params['seqNo'], self::TRANSACTION_REFUND);

                    $refund_list = [];
                    foreach($get_refund_details as $r => $val){

                        $bet_transaction = $this->ebet_transactions->searchByExternalTransactionByRoundIdAndTransType($val['round_id'], self::TRANSACTION_DEBIT);

                        $bet_list = [];
                        foreach($bet_transaction as $b => $bvalue){
                            $t = array (
                                 "seqNo" => $bvalue['seqNo'],
                                "status" => $bvalue['response_status']
                            );

                            array_push($bet_list, $t);
                        }

                        $refResult = array(
                                            'username' => $val['player_username'],
                                            'roundCode' => $val['round_id'],
                                            'refundTotalMoney' => $val['refund_money'],
                                            'sucRefundSeqNoList' => $bet_list
                                    );

                        array_push($refund_list, $refResult);

                    }

                        $data = array(
                            "seqNo" => $this->requestParams->params['seqNo'],
                            "event" => $this->requestParams->params['event'],
                            "timestamp" => $this->requestParams->params['timestamp'],
                            "status" => self::RETURN_OK,
                            "refundResultList"=> $refund_list,

                        );

                        return $this->setResponse(self::SUCCESS, $data);

                }else{
                    $statusCode = "";

                        switch ($controller->resultCode['status']) {
                            case self::ERROR_CODE_NOT_ENOUGH_BALANCE['status']:
                                $statusCode = self::ERROR_CODE_NOT_ENOUGH_BALANCE['status'];
                              break;
                            case self::ERROR_CODE_PARAMETER_ERROR['status']:
                                $statusCode = self::ERROR_CODE_PARAMETER_ERROR['status'];
                              break;
                            case self::ERROR_CODE_REPEATED['status']:
                                $statusCode = self::ERROR_CODE_REPEATED['status'];
                                break;
                            case self::ERROR_CODE_RECORD_ALREADY_REFUND['status']:
                                $statusCode = self::ERROR_CODE_RECORD_ALREADY_REFUND;
                                break;
                            case self::ERROR_CODE_RECORD_NOT_EXISTED['status']:
                                $statusCode = self::ERROR_CODE_RECORD_NOT_EXISTED['status'];
                                break;
                            default:
                                $statusCode = self::ERROR_CODE_SYSTEM_BUSY['status'];
                              break;
                          }

                        $data = array(
                            "status" => $statusCode,
                            "event" =>  $this->requestParams->params['event'],
                            "seqNo" => $this->requestParams->params['seqNo']
                        );

                        if(!empty($controller->resultCode)){
                            return $this->setResponse($controller->resultCode, $data);
                        }else{
                            return $this->setResponse(self::ERROR_CODE_SYSTEM_BUSY, $data);
                        }
                }

            }
        }

    }

    private function adjustWallet($transaction_type, $player_info, $extra = []) {

        $return_data = [
            'code' => self::RETURN_OK
        ];

        $wallet_transaction = [];

        $return_data['before_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

        if($extra['type']==self::TYPE_BET){

            $bet_amount = $extra['bet_amount'] * -1;

            $request_timestamp = date("Y-m-d H:i:s", ($extra['bet_time'] / 1000));

            $wallet_transaction['start_at'] = $request_timestamp;
            $wallet_transaction['end_at'] = $request_timestamp;

            $wallet_transaction['amount'] = $extra['bet_amount'];
            $wallet_transaction['bet_amount'] = $bet_amount;
            $wallet_transaction['transaction_type'] = self::TRANSACTION_DEBIT;
            $wallet_transaction['status'] = self::STATUS_OPEN;

            if($return_data['before_balance'] < $extra['bet_amount']){
                $this->resultCode = self::ERROR_CODE_NOT_ENOUGH_BALANCE;
                $return_data['code'] = self::ERROR_CODE_NOT_ENOUGH_BALANCE;
            }else{
                $response = $this->wallet_model->decSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $bet_amount);

                if(!$response) {
                    $this->resultCode = self::ERROR_CODE_PARAMETER_ERROR;
                    $return_data['code'] = self::ERROR_CODE_PARAMETER_ERROR;
                }
            }

        }else if($extra['type']==self::TYPE_PAYOUT){
            $payoutAmount = $extra['payout'];

            $wallet_transaction['amount'] = $payoutAmount;
            $wallet_transaction['bet_amount'] = 0;
            $wallet_transaction['result_amount'] = $payoutAmount;
            $wallet_transaction['win_amount'] = $payoutAmount;
            $wallet_transaction['odds'] = $extra['odds'];
            $wallet_transaction['validBet'] = $extra['validBet'];
            $wallet_transaction['start_at'] = date("Y-m-d H:i:s", ($extra['bet_time'] / 1000));
            $wallet_transaction['end_at'] = date("Y-m-d H:i:s", ($extra['payout_time'] / 1000));
            $wallet_transaction['status'] = self::STATUS_SETTLED;

            $response = $this->wallet_model->incSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $payoutAmount);

            $wallet_transaction['transaction_type'] = self::TRANSACTION_CREDIT;

            $this->utils->debug_log('EBET_SEAMLESS ', 'ADD_AMOUNT_TAL: ', $response);

            if(!$response && $payoutAmount != 0) {
                $this->utils->debug_log('EBET_SEAMLESS ', 'ADD_AMOUNT_TAL: ', 'PAYOUT AMOUNT IS NOT ZERO', 'response:', $response);
                $return_data['code'] = self::ERROR_CODE_PARAMETER_ERROR;
            }else if(!$response && $payoutAmount == 0){
                $this->utils->debug_log('EBET_SEAMLESS ', 'ADD_AMOUNT_TAL: ', 'PAYOUT AMOUNT IS ZERO', 'response:', $response);
                $response = true;
            }

            $this->ebet_transactions->updateOriginalBetToSettledStatus($extra['bet_id'], self::STATUS_OPEN);

        }else if($extra['type']==self::TYPE_REFUND){
            $refunMoney = $extra['refund_money'];

            $wallet_transaction['token'] = $this->requestParams->params['sessionToken'];
            $wallet_transaction['player_id'] = $player_info['player_id'];
            $wallet_transaction['currency'] =  $this->requestParams->params['currency'];
            $wallet_transaction['transaction_type'] = self::TRANSACTION_REFUND;
            $wallet_transaction['amount'] = $refunMoney;

            $wallet_transaction['game_platform_id'] = $this->api->getPlatformCode();
            $wallet_transaction['transaction_id'] =  $extra['bet_id'];
            $wallet_transaction['external_unique_id'] =  $extra['external_unique_id'];
            $wallet_transaction['seqNo'] =  $extra['seqNo'];
            $wallet_transaction['round_id'] = $extra['round_id'];
            $wallet_transaction['refund_money'] = $refunMoney;
            $wallet_transaction['start_at'] = date("Y-m-d H:i:s", ($extra['timestamp'] / 1000));
            $wallet_transaction['end_at'] = date("Y-m-d H:i:s", ($extra['timestamp'] / 1000));
            $wallet_transaction['status'] = self::STATUS_REFUND;
            $wallet_transaction['before_balance'] = $return_data['before_balance'];
            $wallet_transaction['created_at'] = date("Y-m-d H:i:s");
            $wallet_transaction['updated_at'] = date("Y-m-d H:i:s");
            $wallet_transaction['extra_info'] = isset($extra['extra_info']) ? $extra['extra_info'] : null;

            $response = $this->wallet_model->incSubWallet($player_info['player_id'], $this->api->getPlatformCode(), $refunMoney);

            $wallet_transaction['response_status'] = self::RETURN_OK;
            $wallet_transaction['after_balance'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];
            $wallet_transaction['player_username'] = $extra['game_username'];

            $this->wallet_transaction_id = $this->ebet_transactions->refundTransaction($wallet_transaction);
            $this->wallet_transaction = $wallet_transaction;

            $this->ebet_transactions->updateOriginalBetToRefundStatus($extra['bet_id'], self::STATUS_OPEN); //Update BET status

            $this->utils->debug_log('EBET_SEAMLESS ', 'ADJUST_WALLET_REFUND refundTransaction', $this->wallet_transaction_id);

            if(!$this->wallet_transaction_id) {
                throw new Exception('failed to insert transaction');
            }else{
                $return_data = array(
                    'before_balance'=> $return_data['before_balance'],
                    'after_balance' => $wallet_transaction['after_balance'],
                    'code' => self::RETURN_OK
                );

                return $return_data;
            }


        }else{
            $return_data['code'] = self::ERROR_CODE_PARAMETER_ERROR;
        }

        $return_data['moneyAfter'] = $this->api->queryPlayerBalance($player_info['username'])['balance'];

        $wallet_transaction['game_platform_id'] = $this->api->getPlatformCode();
        $wallet_transaction['token'] = $this->requestParams->params['sessionToken'];
        $wallet_transaction['player_id'] = $player_info['player_id'];
        $wallet_transaction['currency'] =  $this->requestParams->params['currency'];
        $wallet_transaction['transaction_id'] =  $extra['bet_id'];
        $wallet_transaction['game_id'] =  isset($extra['game_id']) ? $extra['game_id'] : null;
        $wallet_transaction['round_id'] =  $extra['round_id'];
        $wallet_transaction['before_balance'] = $return_data['before_balance'];
        $wallet_transaction['after_balance'] = $return_data['moneyAfter'];


        $wallet_transaction['betType'] =  isset($extra['bet_type']) ? $extra['bet_type'] : null;

        $wallet_transaction['seqNo'] = $extra['seqNo'];
        $wallet_transaction['external_unique_id'] = $extra['external_unique_id'];
        $wallet_transaction['extra_info'] = isset($extra['extra_info']) ? $extra['extra_info'] : null;
        $wallet_transaction['created_at'] = date("Y-m-d H:i:s");
        $wallet_transaction['updated_at'] = date("Y-m-d H:i:s");

        $wallet_transaction['response_status'] = self::RETURN_OK;

        $wallet_transaction['player_username'] = $this->requestParams->params['username'];

        if($return_data['code'] == self::RETURN_OK) {
            if(isset($extra['to_update']) && $extra['to_update']){
                $this->wallet_transaction_id = $this->ebet_transactions->updateTransaction($wallet_transaction, self::STATUS_ERROR);
                $this->wallet_transaction = $wallet_transaction;

                $this->utils->debug_log('EBET_SEAMLESS ', 'TALLYNN_UPDATE', $this->wallet_transaction_id);

                if(!$this->wallet_transaction_id) {
                    throw new Exception('failed to insert transaction');
                }
            }else{
                $this->utils->debug_log('EBET_SEAMLESS ', 'TALLYNN_INSERT');
                $this->wallet_transaction_id = $this->ebet_transactions->insertTransaction($wallet_transaction);
                $this->wallet_transaction = $wallet_transaction;
                if(!$this->wallet_transaction_id) {
                    throw new Exception('failed to insert transaction');
                }
            }

        }

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
                        $this->utils->debug_log('EBET ' . __METHOD__ , 'missing parameter', $key);
                        break;
                    }
                    if($rule == 'numeric' && !is_numeric($this->requestParams->params[$key])) {
                        $is_valid = false;

                        $this->utils->debug_log('EBET ' . __METHOD__ , 'not numeric', $key);
                        break;
                    }
                }else{
                    $is_valid = false;

                    $this->utils->debug_log('EBET ' . __METHOD__ , 'pass paramater is not an array', $key);
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

        $params = json_decode(file_get_contents("php://input"), true);

        $this->requestParams->function = $functionName ;
        $this->requestParams->params = $params;

        $is_valid = $this->validateRequest($rule_set);

        if(!$is_valid) {
            $data = [
                "event" => $this->requestParams->params['event'],
                "seqNo" => $this->requestParams->params['seqNo'],
            ];

            return $this->setResponse(self::ERROR_CODE_PARAMETER_ERROR, $data);
        }

        if($params['channelId'] != $this->channel_id ) {
            $data = [
                "event" => $this->requestParams->params['event'],
                "seqNo" => $this->requestParams->params['seqNo'],
            ];

            return $this->setResponse(self::ERROR_CODE_CHANNEL_NOT_EXIST, $data);
        }

        $this->CI->load->model('game_provider_auth');

        if(isset($this->requestParams->params['username'])){
            $this->currentPlayer = (array) $this->game_provider_auth->getPlayerCompleteDetailsByGameUsername($this->requestParams->params['username'], $this->api->getPlatformCode());
        }

        if(empty($this->currentPlayer) && in_array($functionName, self::BLACKLIST_METHODS) && $functionName != self::METHOD_BATCH_REFUND) {
            $data = [
                "username" => $this->requestParams->params['username'],
                "event" => $this->requestParams->params['event'],
                "seqNo" => $this->requestParams->params['seqNo'],
            ];

            return $this->setResponse(self::ERROR_CODE_USER_NOT_EXISTENCE, $data);
        }
    }

    private function setResponse($returnCode, $data = []) {
        $data = array_merge($data, $returnCode);

        return $this->setOutput($data);
    }

    private function setOutput($data = []) {
        $flag = $data['status'] == self::RETURN_OK ? Response_result::FLAG_NORMAL : Response_result::FLAG_ERROR;

        $httpStatusCode = 400;
        $httpStatusText = "Bad Request";

        if(isset($data['status']) && array_key_exists($data['status'], self::HTTP_STATUS_CODE_MAP)){
            $httpStatusCode = self::HTTP_STATUS_CODE_MAP[$data['status']];
            $httpStatusText = $data['msg'];
        }

        $data = json_encode($data);

        $fields = array(
            'player_id' => isset($this->currentPlayer['playerId']) ? $this->currentPlayer['playerId'] : 0
        );

        if($this->api) {
            $response_result_id = $this->CI->response_result->saveResponseResult(
                $this->api->getPlatformCode(),
                $flag,
                $this->requestParams->function,
                json_encode($this->requestParams->params),
                $data,
                $httpStatusCode,
                $httpStatusText,
                $this->requestHeaders,
                $fields
            );
        }

        $this->output->set_status_header($httpStatusCode);
        $this->output->set_content_type('application/json')->set_output($data);
        $this->output->_display();
        exit();
    }

    public function verifySignature($plaintext, $signature){
        return $this->api->rsa->verify($plaintext, base64_decode($signature)) ? 'verified' : 'unverified';
    }

}