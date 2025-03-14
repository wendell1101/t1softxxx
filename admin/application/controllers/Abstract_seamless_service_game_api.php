<?php
if(! defined('BASEPATH')){
    exit('No direct script access allowed');
}

require_once dirname(__FILE__) . '/BaseController.php';


/** 
 * Service API for Seamless Game API
*/
abstract class Abstract_seamless_service_game_api extends BaseController
{

    /** 
     * @var object gameApiClass
     * 
    */
    protected $gameApiSysLibObj;

    /**
     * Default Currency of Game
     * 
     * @var string $defaultGameCurrency;
     */
    protected $defaultGameCurrency = 'CNY';

    /** 
     * Generated response result id in request
     * @var string $generatedResponseResultId;
    */
    protected $generatedResponseResultId = null;

    /** @var array model to loads in construct*/
    protected $modelsToLoad = [
        'common_seamless_wallet_transactions'
    ];

    /**
     * @var mixed constants for transaction_type `common_seamless_wallet_transactions`
     */
    const TRANSACTION_BET = 'bet';
    const TRANSACTION_LOSE = 'lose';
    const TRANSACTION_WIN = 'win';
    const TRANSACTION_DRAW = 'draw';
    const TRANSACTION_REFUND = 'refund';
    const TRANSACTION_SETTLE = 'settle';

    /**
     * @var mixed constants for status in table `common_seamless_wallet_transactions`
     */
    const STATUS_OK = 'ok';
    const STATUS_ERROR = 'error';


    public function __construct()
    {
        parent::__construct();

        $this->gameApiSysLibObj = $this->utils->loadExternalSystemLibObject($this->getPlatformCode());
        # loads models here
        $this->loadModel($this->modelsToLoad);
    }

    /**
     * Debit or Deduct in Player Wallet in our Database
     * 
     * @param
     * 
     * @return void
     */
    abstract public function debit();


    /**
     * Credit or Add in Player Wallet in our Database
     * 
     * @param
     * 
     * @return void
     */
    abstract public function credit();


    /** 
     * Refund the Player Bet or Win in the Player Wallet
     * 
     * @param
     * 
     * @return
    */
    abstract public function refund();

    /** 
     * Get Platform Code
     * 
     * @return int
    */
    public function getPlatformCode()
    {
        return null;
    }

    /**
     * 
     */
    protected function queryPlayerBalance($playerName)
    {
        return $this->gameApiSysLibObj->queryPlayerBalance($playerName);
    }

    /**
     * Get Player Balance
     * 
     * @param int $playerId the player id
     * @return mixed
     */
    protected function getPlayerSubWalletBalance($playerId)
    {
        $this->loadModel(['player_model']);

        if(empty($playerId)){
            return false;
        }

        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId,$this->getPlatformCode());

        return $balance;
    }

    /**
     * Get attribute in object
     * 
     * @param Object $obj
     * @param string $attr
     * @param mixed $mixed
     * 
     * @return mixed
     */
    public function getAttributeInObject($obj,$attr,$def=null)
    {
        return property_exists($obj,$attr) ? ((is_array($obj->$attr) ? (array)$obj->$attr : $obj->$attr)) : $def;
    }

    /**
     * Get Game Platform Id of game API
     * 
     */
    public function getSysObjectGamePlatformId()
    {
        return $this->gameApiSysLibObj->getPlatformCode();
    }

    /** 
     * Do Deduct to player sub wallet balance
     * 
     * @param int $playerId
     * @param  double $betAmount
     * @param array $data
     * 
     * @return boolean
    */
    protected function doDeduct($playerId,$betAmount,&$data){

        $controller = $this;
        $bB = null;
        $aA = null;
        $subWalletid = null;

        $is_success_trans = $this->lockAndTransForPlayerBalance($playerId,function() use($controller,$playerId,$betAmount,&$bB,&$aA,&$data,&$subWalletid){

            $bB = $this->getPlayerSubWalletBalance($playerId);
            $subWalletid = $controller->getSysObjectGamePlatformId();

            if($betAmount > 0){
                $isDeduct = $controller->wallet_model->decSubWallet($playerId,$subWalletid ,$betAmount);
            }elseif($betAmount == 0){
                $isDeduct = true;
            }

            $controller->utils->debug_log(__METHOD__." deduct to subwallet is: ",$isDeduct);

            $aA = $this->getPlayerSubWalletBalance($playerId);

            $data[0]['before_balance'] = $bB;
            $data[0]['after_balance'] = $aA;
            $data[0]['player_id'] = $playerId;

            $isInserted = false;
            if($isDeduct){
                $isInserted = $controller->doInsertToGameTransactions($data);
            }

            $isSuccess = $isDeduct && $isInserted;

            return $isSuccess;

        });

        $this->utils->debug_log(__METHOD__. ' Deducting in Abstract seamless service details: ',[
            'playerId' => $playerId,'beforeBalance'=>$bB,'afterBalance'=>$aA,'data' => $data,'isSuccess' => $is_success_trans,'Game API ID, if null we have error' => $subWalletid
        ]);

        return $is_success_trans;
    }

    /** 
     * Check if Transaction Exist or not and Do Deduct to player sub wallet balance
     * 
     * @param int $playerId
     * @param  double $amount
     * @param array $data
     * @param array $extra
     * 
     * @return array
    */
    protected function doDeductWithTransactionChecking($playerId,$amount,&$data, array $extra){

        $controller = $this;
        $bB = null;
        $aA = null;
        $subWalletid = null;
        $isTransactionExist = false;

        $is_success_trans = $this->lockAndTransForPlayerBalance($playerId,function() use($controller,$playerId,$amount,$extra,&$bB,&$aA,&$data,&$subWalletid,&$isTransactionExist){
            $requestId = $controller->utils->getRequestId();
            # if transaction id is missing, we should not accept the request because we cannot check if transaction exist
            if(empty($extra['transaction_id'])){
                # need to get balance, even error
                $aA = $this->getPlayerSubWalletBalance($playerId);
                $controller->utils->debug_log(__METHOD__.' transaction ID is required to process the Deduct request',$requestId);
                return false;
            }
            
            $this->utils->debug_log(__METHOD__." doDeductWithTransactionChecking: ", $extra);
            $isExist = $controller->isTransactionExist($extra['transaction_id']);

            # debit here
            if($isExist){
                $isTransactionExist = true;
                # need to get balance, even error
                $aA = $this->getPlayerSubWalletBalance($playerId);
                $controller->utils->debug_log(__METHOD__.'  transaction ID already exist, we cannot process it',$requestId);
                return false;
            }

            $bB = $this->getPlayerSubWalletBalance($playerId);
            $subWalletid = $controller->getSysObjectGamePlatformId();

            if($amount > 0){
                $isDeduct = $controller->wallet_model->decSubWallet($playerId,$subWalletid ,$amount);
            }elseif($amount == 0){
                $isDeduct = true;
            }

            $controller->utils->debug_log(__METHOD__." deduct to subwallet is: ",$isDeduct);

            $aA = $this->getPlayerSubWalletBalance($playerId);

            $data[0]['before_balance'] = $bB;
            $data[0]['after_balance'] = $aA;
            $data[0]['player_id'] = $playerId;

            $isInserted = false;
            if($isDeduct){
                $isInserted = $controller->doInsertToGameTransactions($data);
            }

            $isSuccess = $isDeduct && $isInserted;

            return $isSuccess;

        });

        $this->utils->debug_log(__METHOD__. ' Deducting with transaction checking in Abstract seamless service details: ',[
            'playerId' => $playerId,'beforeBalance'=>$bB,'afterBalance'=>$aA,'data' => $data,'isSuccess' => $is_success_trans,'Game API ID, if null we have error' => $subWalletid,'amount'=>$amount
        ]);

        return [
            'is_trans_success' => $is_success_trans,
            'is_transaction_already_exist' => $isTransactionExist,
            'after_balance' => $aA
        ];
    }


    /** 
     * Do Increment to player sub wallet balance
     * 
     * @param int $playerId
     * @param  double $betAmount
     * @param array $data
     * 
     * @return boolean
    */
    protected function doIncrement($playerId,$betAmount,&$data){
        
        $controller = $this;
        $bB = null;
        $aA = null;
        $subWalletid = null;

        $is_success_trans = $this->lockAndTransForPlayerBalance($playerId,function() use($controller,$playerId,$betAmount,&$bB,&$aA,&$data,&$subWalletid){

            $bB = $this->getPlayerSubWalletBalance($playerId);
            $subWalletid = $controller->getSysObjectGamePlatformId();

            if($betAmount > 0){
                $isIncrement = $controller->wallet_model->incSubWallet($playerId,$subWalletid,$betAmount);
            }elseif($betAmount == 0){
                $isIncrement = true;
            }

            $controller->utils->debug_log(__METHOD__." increment to subwallet is: ",$isIncrement);

            $aA = $this->getPlayerSubWalletBalance($playerId);

            $data[0]['before_balance'] = $bB;
            $data[0]['after_balance'] = $aA;
            $data[0]['player_id'] = $playerId;

            $isInserted = false;
            if($isIncrement){
                $isInserted = $controller->doInsertToGameTransactions($data);
            }

            $isSuccess = $isIncrement && $isInserted;

            return $isSuccess;

        });

        $this->utils->debug_log(__METHOD__. ' Incrementing in Abstract seamless service details: ',[
            'playerId' => $playerId,'beforeBalance'=>$bB,'afterBalance'=>$aA,'data' => $data,'isSuccess' => $is_success_trans,'Game API ID, if null we have error' => $subWalletid
        ]);

        return $is_success_trans;
    }

    /** 
     * Check if Transaction Exist or not and Do Increment to player sub wallet balance
     * 
     * * Note: we use this method for refund request too, because in refund, normally we increment player balance
     * 
     * @param int $playerId
     * @param double $amount
     * @param array $data
     * @param array $extra
     * 
     * @return array
    */
    protected function doIncrementWithTransactionChecking($playerId,$amount,&$data,array $extra){

        $controller = $this;
        $bB = null;
        $aA = null;
        $subWalletid = null;
        $isTransactionExist = false;
        $isRefundedTransactionStatusUpdated = false;
        $reasonRefundTransactionNotExist = false;
        $reasonRefundedAlready = false;

        $is_success_trans = $this->lockAndTransForPlayerBalance($playerId,function() use($controller,$playerId,$amount,&$bB,&$aA,&$data,&$subWalletid,&$isTransactionExist,&$isRefundedTransactionStatusUpdated,$extra,&$reasonRefundTransactionNotExist,&$reasonRefundedAlready){
            $requestId = $controller->utils->getRequestId();
            $isRefundRequest = false;
            $goAheadAndRefundIt = false;

            # if transaction id is missing, we should not accept the request because we cannot check if transaction exist
            if(empty($extra['transaction_id'])){

                $aA = $controller->getPlayerSubWalletBalance($playerId);
                $controller->utils->debug_log(__METHOD__.' transaction ID is required to process the Increment request',$requestId);
                return false;
            }

            $isTransactionExist = $controller->isTransactionExist($extra['transaction_id']);

            # check if refund transaction
            if(isset($extra['is_refund']) && $extra['is_refund']){
                # check first if exist
                if($isTransactionExist){
                    $isAlreadyRefunded = $controller->isRefundTransactionExist($extra['transaction_id']);
                    if($isAlreadyRefunded){
                        # refunded already
                        $reasonRefundedAlready = true;
                        $goAheadAndRefundIt=false;
                        return false;
                    }else{
                        # key to refund
                        $isRefundRequest = true;
                        $goAheadAndRefundIt=true;
                    }
                }else{
                    $reasonRefundTransactionNotExist = true;
                    $aA = $controller->getPlayerSubWalletBalance($playerId);
                    $controller->utils->debug_log(__METHOD__.' transaction ID is not exist, we cannot process it',$requestId);
                    return false;

                }
            }else{
                # credit here
                if($isTransactionExist){
                    $isTransactionExist = true;
                    $aA = $controller->getPlayerSubWalletBalance($playerId);
                    $controller->utils->debug_log(__METHOD__.'  transaction ID already exist, we cannot process it',$requestId);
                    return false;
                }
            }

            $bB = $controller->getPlayerSubWalletBalance($playerId);
            $subWalletid = $controller->getSysObjectGamePlatformId();

            if($amount > 0){
                #refund process here
                if($isRefundRequest && $goAheadAndRefundIt){
                    $isIncrement = $controller->wallet_model->incSubWallet($playerId,$subWalletid,$amount);
                    if($isIncrement){
                        $isUpdated = $controller->common_seamless_wallet_transactions->updateRefundedTransaction($extra['transaction_id']);
                        $isRefundedTransactionStatusUpdated = $isUpdated;
                    }
                }else{
                    # deposit process here
                    $isIncrement = $controller->wallet_model->incSubWallet($playerId,$subWalletid,$amount); 
                }
            }else{
                $isIncrement = true;
            }

            $controller->utils->debug_log(__METHOD__." increment to subwallet is: ",$isIncrement,'request_id',$requestId);

            $aA = $controller->getPlayerSubWalletBalance($playerId);

            $data[0]['before_balance'] = $bB;
            $data[0]['after_balance'] = $aA;
            $data[0]['player_id'] = $playerId;

            $isInserted = false;
            if($isIncrement){
                $isInserted = $controller->doInsertToGameTransactions($data);
            }

            $isSuccess = $isIncrement && $isInserted;

            return $isSuccess;

        });

        $this->utils->debug_log(__METHOD__. ' Incrementing with transaction checking in Abstract seamless service details: ',[
            'playerId' => $playerId,'beforeBalance'=>$bB,'afterBalance'=>$aA,'data' => $data,'isSuccess' => $is_success_trans,'Game API ID, if null we have error' => $subWalletid,'amount'=>$amount
        ]);

        return [
            'is_trans_success' => $is_success_trans,
            'is_transaction_already_exist' => $isTransactionExist,
            'is_refund_transaction_status_updated' => $isRefundedTransactionStatusUpdated,
            'reason_refund_transaction_not_exist' => $reasonRefundTransactionNotExist,
            'reason_refunded_already' => $reasonRefundedAlready,
            'after_balance' => $aA
        ];
    }

    /** 
     * Check if transaction Exist
     * 
     * @param int $transactionId
     * 
     * @return boolean
    */
    public function isTransactionExist($transactionId)
    {

        $isTransactionExist = $this->common_seamless_wallet_transactions->isTransactionExistCustom($this->getPlatformCode(),$transactionId);

        return $isTransactionExist;
    }

    /**
     * Check if Transaction is Already Refunded, if so, we must not accept the refund request
     * 
     * @param int $transaction
     * 
     * @return boolean
     */
    public function isRefundTransactionExist($transactionId)
    {
        $isAlreadyRefunded = $this->common_seamless_wallet_transactions->isTransactionAlreadyRefunded($transactionId);

        return $isAlreadyRefunded;
    }

    /**
     * Get Request Data
     * 
     * 
     * @return Object
     */
    public function request()
    {
        $request = file_get_contents("php://input");

        $objectRequest = json_decode($request);

        return $objectRequest;
    }

    /** 
     * Detect if parameter is json data type
     * 
     * @param mixed the data to check if json or not
     * 
     * @return boolean
    */
    public function isJson($data = null)
    {
        $rlt = true;
        
        if(null === @json_decode($data)){
            $rlt = false;
        }
        
        return $rlt;
    }

    /** 
     * Save Request to Response result
     * 
     * @return int the last insert id
    */
    protected function saveToResponseResult($request_method, $request_params = null, $extra = null, $fields = [], $http_status_code = 200, $flag = 1)
    {
        $headers = getallheaders();

        if (empty($extra)) {
            $extra = is_array($headers) ? json_encode($headers) : $headers;
        }

        $data = is_array($request_params) ? json_encode($request_params) : $request_params;

        $lastInsertId = $this->CI->response_result->saveResponseResult(
            $this->getSysObjectGamePlatformId(), #1
            $flag,#2
            $request_method,#3
            $data,#4
            "",#5 response
            $http_status_code,#6
            null,#7
            $extra,#8
            $fields #9
        );

        $this->generatedResponseResultId = $lastInsertId;

        return $lastInsertId;
    }

    /**
     * Load Model
     * 
     * @param array $model
     * 
     * @return void
     */
    public function loadModel(array $model)
    {
        return $this->load->model($model);
    }


    /**
     * Output JSON data
     * 
     * @param array $data
     * @param int $statusHeader
     * @param array $header
     * @param int $responseResultId
     * @param int $playerId
     * 
     * @return object
     */
    public function outputHttpResponse($data=[],$statusHeader=400,$header=[],$responseResultId=null,$playerId=null,$flag=2)
    {
        $this->loadModel(['response_result']);
        $content = json_encode((array) $data);
        $jsonData = json_encode((array) $data);

        if($responseResultId){
            $this->response_result->updateResponseResultCommonData($responseResultId,$content,$playerId,$flag);
        }
        $output = $this->output;

        if(is_array($header) && count($header)>0){
            foreach($header as $val){
                $output->set_header($val);
            }
        }

        return $output->set_content_type('application/json')
                    ->set_status_header($statusHeader)
                    ->set_output($jsonData);
    }

    /** 
     * Check if token is valid
     * 
     * @param string $token the token to validate
     * 
     * @return boolean
    */
    protected function isTokenValid($token)
    {
        $playerInfo = $this->gameApiSysLibObj->getPlayerInfoByToken($token);

        if(empty($playerInfo)){
            return false;
        }

        return true;
    }

    /**
     * Generate date and time with micro seconds now
     * 
     * @return string
     */
    public function generateMicroDateTimeSecondsNow()
    {
        $date = new DateTime();
        $t = microtime(true);
        $m = sprintf("%04d",($t - floor($t)) * 1000);
        $d = $date->format( "Y-m-d\TH:i:s" );
        $d .= '.' . $m . 'Z';

        return $d;
    }

    /**
     * Check if player is exist in game provider
     * 
     * @param string $playerName
     * @param int $gamePlatformId
     * 
     * @return boolean 
     */
    protected function isPlayerExistInProvider($playerName,$gamePlatformId)
    {
        $gameUsername = $this->gameApiSysLibObj->getGameUsernameByPlayerUsername($playerName);

        $this->loadModel(['game_provider_auth']);

        $playerId = $this->game_provider_auth->getPlayerIdByPlayerName($gameUsername,$gamePlatformId);

        if(! is_null($playerId)){
            return true;
        }

        return false;
    }

    /** 
     * Insert data to common_seamless_wallet_transactions table
     * 
     * @param object $data
     * 
     * @return mixed
     * 
    */
    public function doInsertToGameTransactions($data)
    {

        $this->loadModel(['common_seamless_wallet_transactions']);

        $lastInsertId = $this->common_seamless_wallet_transactions->insertTransaction($data);

        $this->CI->utils->debug_log(__METHOD__ ."insert transaction with last insert ID of: ",$lastInsertId);

        return $lastInsertId;
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
            foreach($gameRecords as $index => $record){
                $data['game_platform_id'] = isset($record['game_platform_id']) ? $record['game_platform_id'] : null;
                $data['amount'] = isset($record['amount']) ? $record['amount'] : null;
                $data['before_balance'] = isset($record['before_balance']) ? $record['before_balance'] : null;
                $data['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;
                $data['player_id'] = isset($record['player_id']) ? $record['player_id'] : null;
                $data['game_id'] = isset($record['game_id']) ? $record['game_id'] : null;
                $data['transaction_type'] = isset($record['transaction_type']) ? $record['transaction_type'] : null;
                $data['status'] = isset($record['status']) ? $record['status'] : null;
                $data['response_result_id'] = isset($record['response_result_id']) ? $record['response_result_id'] : null;
                $data['external_unique_id'] = isset($record['external_unique_id']) ? $record['external_unique_id'] : null;
                $data['extra_info'] = isset($record['extra_info']) ? $record['extra_info'] : null;
                $data['start_at'] = isset($record['start_at']) ? $record['start_at'] : null;
                $data['end_at'] = isset($record['end_at']) ? $record['end_at'] : null;
                $data['transaction_id'] = isset($record['transaction_id']) ? $record['transaction_id'] : null;
                $data['elapsed_time'] = isset($record['elapsed_time']) ? $record['elapsed_time'] : $elapsed;
                $data['round_id'] = isset($record['round_id']) ? $record['round_id'] : null;

                $gameRecords[$index] = $data;
                unset($data);
            }
        }
    }

    /**
     * Get Player Balance with locking
     * 
     * @see class::getPlatformCode()
     * 
     * @param int $playerId
     * @param mixed|0 $defBalance the default balance if we have error fetching the balance of player
     * 
     * @return int $balance
     */
    protected function getPlayerBalanceWithLock($playerId,$defBalance=0)
    {
        $balance = $defBalance;
        $controller  = $this;

       $this->lockAndTransForPlayerBalance($playerId,function() use(&$balance,$controller,$playerId){

            $balance =   $controller->getPlayerSubWalletBalance($playerId);

            return true;
        });

        return $balance;
    }
}