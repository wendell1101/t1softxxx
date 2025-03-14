<?php
require_once dirname(__FILE__) . '/game_api_v2_commmon_ipm.php';

/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * XML extraction of logs
 * * sync and merge games logs for AGBBIN
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 1.8.10
 * @copyright 2013-2022 tot
 */
class Game_api_v2_esports extends Game_api_v2_commmon_ipm {

    # Fields in tianhao_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'PlayerId',
        'LastUpdatedDate',
        'StakeAmount',
        'MemberExposure',
        'PayoutAmount',
        'WinLoss',
        'WagerType',
        'IsSettled',
        'IsConfirmed',
        'IsCancelled',
        'BetTradeCommission',
        'BetTradeBuyBackAmount',
        'GameId',
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'StakeAmount',
        'MemberExposure',
        'PayoutAmount',
        'WinLoss',
        'BetTradeCommission',
        'BetTradeBuyBackAmount',
    ];

    # Fields in game_logs we want to detect changes for merge, and when tianhao_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'gameUsername',
        'game_date',
        'bet_time',
        'game_code',
        'payout_amount',
        'bet_amount',
        'result_amount',
        'is_settled',
        'is_confirmed',
        'is_cancelled',
        'settle_date',
        'game_description_id'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'payout_amount',
        'bet_amount',
        'result_amount'
    ];

    # Don't ignore on refresh 
    const DEFAULT_IGNORED_0_ON_REFRESH = FALSE;

    const RESYNC_BY_NO_OFDAYS_AGO = 7;

    const DEFAULT_GAME_LAUNCH_CODE = 'ESPORTSBULL';
    
    public function getPlatformCode() {
        return IPM_V2_ESPORTS_API;
    }

    public function __construct() {
        parent::__construct();
        $this->currency = $this->getSystemInfo('currency');
        $this->game_launch_code = $this->getSystemInfo('game_launch_code', self::DEFAULT_GAME_LAUNCH_CODE);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId   = $this->getPlayerIdInGameProviderAuth($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameUsername' => $gameUsername,
            'playerId' => $playerId
        );

        $params = array(
            'MerchantCode'  => $this->merchant_code,
            'PlayerId'      => $gameUsername,
            "ProductWallet"     => self::PRODUCT_CODE_IM_ESPORTS
        );

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);

    }

    public function processResultForQueryPlayerBalance($params) {
        $this->CI->utils->debug_log('##########  QUERY PLAYER BALANCE         #####################', $params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('processResultForQueryPlayerBalance ==========================>', $resultJsonArr);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        $result = array();

        if($success){
            //$result['balance'] = @floatval($resultJsonArr['Balance']);
            $result['balance'] = $this->gameAmountToDBTruncateNumber($resultJsonArr['Balance']);

            if ($playerId = $this->getPlayerIdInGameProviderAuth($playerName)) {
                $this->CI->utils->debug_log('query balance playerId ========>', $playerId, 'playerName', $playerName, 'balance', $result['balance']);
            } else {
                $this->CI->utils->debug_log('cannot get player id from ========>' . $playerName . ' getPlayerIdInGameProviderAuth');
            }
        } else {
            if (@$resultJsonArr['Code'] == self::PLAYER_NOT_EXIST) {
                $result['exists'] = false;
            } else {
                $result['exists'] = true;
            }
        }

        return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);

        $amount = $this->dBtoGameAmount($amount);

        $data = array(
                "transaction_type" => self::DEPOSIT_TRANSACTION,
                "external_transaction_id" => $transfer_secure_id,
                "callback_method" => "processResultForDepositToGame",
                "game_username" => $gameUsername,
                "player_id" => $playerId,
                "amount" => $amount,
                "api_url" => self::API_depositToGame,
                "product_code" => self::PRODUCT_CODE_IM_ESPORTS,
            );
        return $this->init_fund_transaction($data);
    }

    public function processResultForDepositToGame($params) {
        // $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);

        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN,
            'external_transaction_id'=>$external_transaction_id,
        );

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr,$gameUsername);
        $this->utils->debug_log("Deposit ResultArr ============================>", $resultJsonArr);
        $this->utils->debug_log("Deposit result from response result id ============================>", $responseResultId);

        if ($success) {
            //get current sub wallet balance
            // $playerBalance = $this->queryPlayerBalance($playerName);

            //for sub wallet
            // $afterBalance = $playerBalance['balance'];
            // $result["external_transaction_id"] = $external_transaction_id;
            // $result["currentplayerbalance"] = $afterBalance;
            // $afterBalance=null;

            //update
            // $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
            // if ($playerId) {
                //deposit
                // $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());
            // } else {
            //     $this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
            // }
            $result['didnot_insert_game_logs']=true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $error_code = isset($resultJsonArr['Code']) ? $resultJsonArr['Code'] : null;
            if(((in_array($statusCode, $this->other_status_code_treat_as_success)) || (in_array($error_code, $this->other_status_code_treat_as_success))) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            } else {
                ;
                $result['reason_id'] = $this->getReason($error_code);
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            }
        }

        return array($success, $result);
    }

    private function getReason($error_code){
        switch($error_code) {
            case '506' :
            case '504' :
                return self::REASON_NOT_FOUND_PLAYER;
                break;
            case '508' :
                return self::REASON_INVALID_PRODUCT_WALLET;
                break;
            case '509' :
            case '516' :
                return self::REASON_INVALID_TRANSACTION_ID;
                break;
            case '510' :
                return self::REASON_NO_ENOUGH_BALANCE;
                break;
            case '519' :
            case '543' :
                return self::REASON_INVALID_TRANSFER_AMOUNT;
                break;
            case '514' :
                return self::REASON_DUPLICATE_TRANSFER;
                break;
            case '604' :
                return self::REASON_TRANSFER_AMOUNT_IS_TOO_HIGH;
                break;
            case '605' :
                return self::REASON_TRANSFER_AMOUNT_IS_TOO_LOW;
                break;
            default:
                return self::REASON_UNKNOWN;
                break;
        }
    }

    public function queryTransaction($transactionId, $extra) {
        $this->utils->debug_log('the extra------>', $extra);
       $playerName = $extra['playerName'];
       $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
       $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'gameUsername' => $gameUsername,
            'playerName'      => $extra['playerName'],
            'external_transaction_id' => $transactionId,
            'playerId' => $extra['playerId'],
        );

        $params = array(
            'MerchantCode'  => $this->merchant_code,
            'PlayerId'      => $gameUsername,
            'TransactionId' => $transactionId,
            'ProductWallet' => self::PRODUCT_CODE_IM_ESPORTS
        );

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params) {
        $this->CI->utils->debug_log('##########  QUERY TRANSACTION  #####################');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('processResultForQueryTransaction ==========================>', $resultJsonArr);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $result = array('response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);
        if ($success) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $result['status']=self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId   = $this->getPlayerIdInGameProviderAuth($gameUsername);        

        $amount = $this->dBtoGameAmount($amount);

        $data = array(
                "transaction_type" => self::WITHDRAWAL_TRANSACTION,
                "external_transaction_id" => $transfer_secure_id,
                "callback_method" => "processResultForWithdrawFromGame",
                "game_username" => $gameUsername,
                "player_id" => $playerId,
                "amount" => $amount,
                "api_url" => self::API_withdrawFromGame,
                "product_code" => self::PRODUCT_CODE_IM_ESPORTS,
            );
        return $this->init_fund_transaction($data);
    }

    public function processResultForWithdrawFromGame($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $amount = $this->getVariableFromContext($params, 'amount');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);

        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN,
            'external_transaction_id'=>$external_transaction_id,
        );

        $success = $this->processResultBoolean($responseResultId, $resultArr, $gameUsername);
        if ($success) {
            //get current sub wallet balance
            // $playerBalance = $this->queryPlayerBalance($playerName);

            //for sub wallet
            // $afterBalance = $playerBalance['balance'];

            // $result["external_transaction_id"] = $transaction_id;
            // $result["currentplayerbalance"] = $afterBalance;
            // $afterBalance=null;
            //update
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
            // if ($playerId) {
                //withdraw
                // $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,$this->transTypeSubWalletToMainWallet());
            // } else {
            //     $this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
            // }
            $result['didnot_insert_game_logs']=true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $error_code = $resultArr['Code'];
            $result['reason_id'] = $this->getReason($error_code);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
    }

    public function getAccessToken($playerName = null, $extra = null) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId   = $this->getPlayerIdInGameProviderAuth($playerName);
        $language =  $this->getLauncherLanguage($this->language = $this->getSystemInfo('language', $extra['language']));

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetAccessToken',
            'playerName' => $playerName
        );

        $params = array(
            'MerchantCode' => $this->merchant_code,
            'PlayerId' => $gameUsername,
            'GameCode' => $this->game_launch_code,
            'Language' => $language,
            'IpAddress'=> $this->CI->input->ip_address(),
            "ProductWallet"=> self::PRODUCT_CODE_IM_ESPORTS
        );
        
        if($extra['is_mobile']){
            $apiMethod = self::API_checkMobileLoginToken;
        }else{
            $apiMethod =  self::API_checkLoginToken;
        }

        return $this->callApi($apiMethod, $params, $context);
    }

    public function processResultForGetAccessToken($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('processResultForGetAccessToken ==========================>', $resultArr);
        $success = $this->processResultBoolean($responseResultId, $resultArr,null);
        if ($success) {
            $this->game_url = $resultArr["GameUrl"];
            return $success;
        }

    }

    public function getLauncherLanguage($lang){
        $this->CI->load->library("language_function");
        switch ($lang) {
            case 'zh-cn':
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'ZH-CN';
                break;
            case 'en-us':
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
                $lang = 'EN';
                break;
            case 'vi':
            case 'vi-vn':
            case 'vi-vi':
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $lang = 'VI';
                break;
            case 'th':
            case 'th-th':
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                $lang = 'TH';
                break;
            default:
                $lang = 'EN';
                break;
        }
        return $lang;
    }

    public function syncOriginalGameLogs($token) {

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));

        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());

        //observer the date format
        $queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
        $queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
        $queryDateTimeMax = $endDate->format('Y-m-d H:i:s');
        $rtn = array();

        while ($queryDateTimeMax  > $queryDateTimeStart) {
            $startDateParam=new DateTime($queryDateTimeStart);
            if($queryDateTimeEnd>$queryDateTimeMax){
                $endDateParam=new DateTime($queryDateTimeMax);
            }else{
                $endDateParam=new DateTime($queryDateTimeEnd);
            }
            $endDateParamNew = $endDateParam->format('Y-m-d H:i:s');

            $startDateParam = $startDateParam->format('Y-m-d H.i.s');
            $endDateParam = $endDateParam->format('Y-m-d H.i.s');


            $page = 1;
            $perpage = 500;

            $rtn[] = $this->_continueSync( $startDateParam, $endDateParam, $perpage, $page);

            $queryDateTimeStart = $endDateParamNew;
            //var_dump($queryDateTimeStart);die();
            $queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
        }

        return array("success"=>true,"sync_details" => $rtn);
    }

     public function _continueSync($startDateParam, $endDateParam, $perpage, $page){
        $return = $this->syncIpmSportsbookGamelogs($startDateParam,$endDateParam,$perpage,$page);
        if($page<$return['total_page']){
            $page++;
            return $this->_continueSync($startDateParam, $endDateParam, $perpage, $page);
        }

        return $return;
    }

    public function syncIpmSportsbookGamelogs($startDateParam, $endDateParam, $perpage = null , $page){

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameLogs',
            'startDateStr' => $startDateParam,
            'endDateStr' => $endDateParam,
        );

        $params = array(
            'MerchantCode'  => $this->merchant_code,
            'StartDate' => $startDateParam,
            'EndDate'   => $endDateParam,
            'Page' => $page,
            'ProductWallet' => self::PRODUCT_CODE_IM_ESPORTS,
            'Currency' => $this->currency,
            'DateFilterType' => self::BET_DATE,
            'Language' => 'EN',

        );
        $this->CI->utils->debug_log('try load game logs syncOriginalGameLogs', $startDateParam, $endDateParam, $params);

        return $this->callApi(self::API_queryGameRecords, $params, $context);
    }

    public function rebuildIPMGameRecords(&$gameRecords,$extra){
        foreach($gameRecords as $index => $record) {
            
            $gameRecords[$index]['external_uniqueid'] = $record['BetId'];
            $gameRecords[$index]['response_result_id'] = $extra['responseResultId'];
            $gameRecords[$index]['WagerCreationDateTime'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['WagerCreationDateTime'])));
            $gameRecords[$index]['LastUpdatedDate'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['LastUpdatedDate'])));
            $gameRecords[$index]['DetailItems'] = json_encode($record['DetailItems']);
            $gameRecords[$index]['MemberExposure'] = isset($record['MemberExposure'])?$record['MemberExposure']:null;
            $gameRecords[$index]['PayoutAmount'] = isset($record['PayoutAmount'])?$record['PayoutAmount']:null;            
            $gameRecords[$index]['BetTradeCommission'] = isset($record['BetTradeCommission'])?$record['BetTradeCommission']:null;            
            $gameRecords[$index]['BetTradeBuyBackAmount'] = isset($record['BetTradeBuyBackAmount'])?$record['BetTradeCommission']:null;                        
            $gameRecords[$index]['SportsName'] = isset($record['DetailItems'][0]['SportsName'])?$record['DetailItems'][0]['SportsName']:date('ymd His');
           
            $gameRecords[$index]['IsConfirmed'] =  isset($record['IsConfirmed'])?$record['IsConfirmed']:null;
            $gameRecords[$index]['IsCancelled'] = isset($record['IsCancelled'])?$record['IsCancelled']:null;
            $gameRecords[$index]['IsSettled'] = isset($record['IsSettled'])?$record['IsSettled']:null;

            $gameRecords[$index]['StakeAmount'] = isset($record['StakeAmount']) ? str_replace(',', '', $record['StakeAmount']) :null;
            $gameRecords[$index]['WinLoss'] = isset($record['WinLoss']) ? str_replace(',', '', $record['WinLoss']) : null;
        }
    }

    public function processResultForSyncOriginalGameLogs($params) {
        // $this->CI->load->model(array('ipm_v2_esports_game_logs', 'player_model'));
        $this->CI->load->model(array('original_game_logs_model'));
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr);
        if(isset($resultJsonArr['Result'])){
            $gameRecords = $resultJsonArr['Result'] === array_values($resultJsonArr['Result']) ? $resultJsonArr['Result'] : array($resultJsonArr['Result']);
        }else{
            $gameRecords = array();
        }

        $result = array('data_count'=>0);

        if ($success) {
            if(!empty($gameRecords)){
                # reprocess Game records
                $extra = ['responseResultId'=>$responseResultId];
                $this->rebuildIPMGameRecords($gameRecords,$extra);
                
                $this->CI->utils->debug_log('IPM ESPORTS processResultForSyncOriginalGameLogs $gameRecords', $gameRecords);
                list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    'ipm_v2_esports_game_logs',
                    $gameRecords,
                    'external_uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );

                $this->CI->utils->debug_log('after process available rows', count($gameRecords), count($insertRows), count($updateRows));

                unset($gameRecords);

                if (!empty($insertRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                        ['responseResultId'=>$responseResultId]);
                }
                unset($insertRows);

                if (!empty($updateRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                        ['responseResultId'=>$responseResultId]);
                }
                unset($updateRows);
            }
        }

        $result['total_page'] = isset($resultJsonArr['Pagination']['TotalPage'])?$resultJsonArr['Pagination']['TotalPage']:1;

        return array(true, $result);
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($rows)){
            $responseResultId=$additionalInfo['responseResultId'];
            foreach ($rows as $record) {
                $record['last_sync_time'] = $this->utils->getNowForMysql();
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal('ipm_v2_esports_game_logs', $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal('ipm_v2_esports_game_logs', $record);
                }
                $dataCount++;
                unset($data);
            }
        }

        return $dataCount;
    }

    public function syncLostAndFound($token) {
        $this->CI->load->model(array('game_logs','ipm_v2_esports_game_logs'));

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        # Start date is set to Default of 1 week ago from start date of inquiry
        $queryStartDate = $startDate->modify($this->sync_days_interval)->format('Y-m-d H:i:s');
        $queryEndDate = $endDate->format('Y-m-d H:i:s');

        $result = $this->CI->ipm_v2_esports_game_logs->getOpenLogs($queryStartDate,$queryEndDate);
        // if(!empty($result)) {
        //  echo"<pre>";print_r($result);exit();
        // }
        $this->utils->debug_log(' syncLostAndFound - is running ');
        $creationDate = array();
        if(!empty($result)) {
            foreach ($result as $key => $resulti) {
                $creationDate[] = $resulti['WagerCreationDateTime'];
            }
        }

        if(!empty($creationDate)) {
            $startDate = date('Y-m-d H:i:s',min(array_map('strtotime', $creationDate)));
            $endDate   = date('Y-m-d H:i:s',max(array_map('strtotime', $creationDate)));

            if(count($creationDate) > 1) {
                $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+10 minutes', function($startDate, $endDate) {
                    $interval = $startDate->diff($endDate);
                    $elapsed = $interval->format('%i');

                    $startDate = $startDate->format('Y-m-d H:i:s');
                    $endDate = $endDate->format('Y-m-d H:i:s');


                    $this->utils->debug_log('syncLostAndFound preStartDate - '.$startDate);
                    $this->utils->debug_log('syncLostAndFound preEndDate - '.$endDate);

                    $resultByRange = $this->CI->ipm_v2_esports_game_logs->getOpenLogs($startDate,$endDate);
                    $this->utils->debug_log('syncLostAndFound resultByRange - '.json_encode($resultByRange));
                    if(!empty($resultByRange)) {
                        $this->utils->debug_log('syncLostAndFound startDate - '.$startDate);
                        $this->utils->debug_log('syncLostAndFound endDate - '.$endDate);

                        if($elapsed < 10){
                            $endDate = (new DateTime($startDate))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
                        }

                        $startDateParam=new DateTime($startDate);
                        $endDateParam=new DateTime($endDate);
                        $startDateParam = $startDateParam->format('Y-m-d H.i.s');
                        $endDateParam = $endDateParam->format('Y-m-d H.i.s');

                        $context = array(
                            'callback_obj' => $this,
                            'callback_method' => 'processResultForSyncOriginalGameLogs',
                            'startDateStr' => $startDateParam,
                            'endDateStr' => $endDateParam,
                        );

                        $params = array(
                            'MerchantCode'  => $this->merchant_code,
                            'StartDate' => $startDateParam,
                            'EndDate'   => $endDateParam,
                            'Page' => 1,
                            'ProductWallet' => self::PRODUCT_CODE_IM_ESPORTS,
                            'Currency' => $this->currency,
                            'DateFilterType' => self::BET_DATE,
                            'Language' => 'EN',

                        );

                        $this->CI->utils->debug_log('try load lost and found game logs syncOriginalGameLogs', $startDateParam, $endDateParam, $params);

                        return $this->callApi(self::API_queryGameRecords, $params, $context);
                    }else{
                        return true;
                    }
                });
            }
            else {
                $startDateParam=new DateTime($startDate);
                $endDateParam=new DateTime($endDate);
                $startDateParam = $startDateParam->format('Y-m-d H.i.s');
                $endDateParam = $endDateParam->format('Y-m-d H.i.s');

                $context = array(
                    'callback_obj' => $this,
                    'callback_method' => 'processResultForSyncOriginalGameLogs',
                    'startDateStr' => $startDateParam,
                    'endDateStr' => $endDateParam,
                );

                $params = array(
                    'MerchantCode'  => $this->merchant_code,
                    'StartDate' => $startDateParam,
                    'EndDate'   => (new DateTime($endDateParam))->modify($this->sync_time_interval)->format('Y-m-d H.i.s'),
                    'Page' => 1,
                    'ProductWallet' => self::PRODUCT_CODE_IM_ESPORTS,
                    'Currency' => $this->currency,
                    'DateFilterType' => self::BET_DATE,
                    'Language' => 'EN',

                );
                //print_r($params);exit();
                $this->CI->utils->debug_log('try load lost and found game logs syncOriginalGameLogs', $startDateParam, $endDateParam, $params);

                return $this->callApi(self::API_queryGameRecords, $params, $context);
            }
            // sleep(self::DEFAULT_SYNC_SLEEP_TIME);
        }
        return array('success' => true);
    }

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime='`ipm_v2_esports_game_logs`.`LastUpdatedDate` >= ?
          AND `ipm_v2_esports_game_logs`.`LastUpdatedDate` <= ?';
        if($use_bet_time){
            $sqlTime='`ipm_v2_esports_game_logs`.`WagerCreationDateTime` >= ?
          AND `ipm_v2_esports_game_logs`.`WagerCreationDateTime` <= ?';
        }

        $sql = <<<EOD
        SELECT 
          `ipm_v2_esports_game_logs`.`id` AS sync_index,
          `ipm_v2_esports_game_logs`.`PlayerId` AS gameUsername,
          `ipm_v2_esports_game_logs`.`external_uniqueid`,
          `ipm_v2_esports_game_logs`.`WagerCreationDateTime` as bet_time,
          `ipm_v2_esports_game_logs`.`LastUpdatedDate` AS game_date,
          `ipm_v2_esports_game_logs`.`SettlementDateTime` AS settle_date,
          `ipm_v2_esports_game_logs`.`SportsName` AS game_code,
          `ipm_v2_esports_game_logs`.`response_result_id`,
          `ipm_v2_esports_game_logs`.`PayoutAmount` AS payout_amount,
          `ipm_v2_esports_game_logs`.`StakeAmount` AS bet_amount,
          `ipm_v2_esports_game_logs`.`WinLoss` AS result_amount,
          `ipm_v2_esports_game_logs`.`Platform` AS platform,
          `ipm_v2_esports_game_logs`.`isSettled` AS is_settled,
          `ipm_v2_esports_game_logs`.`isConfirmed` AS is_confirmed,
          `ipm_v2_esports_game_logs`.`isCancelled` AS is_cancelled,
          `ipm_v2_esports_game_logs`.`BetId` AS RoundID,
          `ipm_v2_esports_game_logs`.`SportsName` AS sports_name,
          `ipm_v2_esports_game_logs`.`DetailItems` AS bet_details,
          `ipm_v2_esports_game_logs`.`md5_sum`, 
          `game_provider_auth`.`player_id`,
          `game_description`.`id` AS game_description_id,
          `game_description`.`game_type_id`
        FROM
          (`ipm_v2_esports_game_logs`) 
          LEFT JOIN `game_description` 
            ON ipm_v2_esports_game_logs.SportsName = game_description.external_game_id 
            AND game_description.game_platform_id = ? 
            AND game_description.void_bet != 1 
          LEFT JOIN `game_type` 
            ON game_description.game_type_id = game_type.id 
          JOIN `game_provider_auth` 
            ON (
              `ipm_v2_esports_game_logs`.`PlayerId` = `game_provider_auth`.`login_name` 
              AND `game_provider_auth`.`game_provider_id` = ?
            ) 
        WHERE 
            {$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle = true; 
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['gameUsername']
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null
            ],
            'date_info' => [
                'start_at' => $row['bet_time'],
                'end_at' => $row['settle_date'],
                'bet_at' => $row['bet_time'],
                'updated_at' => $row['game_date']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $this->getGameRecordsStatus($row),
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['external_uniqueid'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $this->processGameBetDetail($row),
            'extra' => [],
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_description_id'])) {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            $row['game_description_id']= $unknownGame->id;
            $row['game_type_id'] = $unknownGame->game_type_id;
        }
        $row['status'] = $this->getGameRecordsStatus($row);
    }

    public function getGameRecordsStatus($row) {        
        $status = Game_logs::STATUS_PENDING;
        if(($row['is_settled'] == '1' || $row['is_settled'] == 1) && ($row['is_cancelled'] == 0 || $row['is_cancelled'] == '0')){
            $status = Game_logs::STATUS_SETTLED;
        } else {
            if($row['is_cancelled'] == self::CANCEL){
                $status = Game_logs::STATUS_CANCELLED;
            }
        }
        return $status;
    }

    public function processGameBetDetail($rowArray){
        $bet_details =  array('sports_bet' => $this->setBetDetails($rowArray));
        $this->CI->utils->debug_log('=====> Bet Details return', $bet_details);
        return $bet_details;
    }

    public function setBetDetails($row){
        $data = json_decode($row['bet_details'],true);
        $set = array();
        if(!empty($data)){
            foreach ($data as $key => $game) {
                $set[$key] = array(
                    'yourBet' => $row['bet_amount'],
                    'isLive' => null,
                    'odd' => $game['Odds'],
                    'hdp'=> $game['Handicap'],
                    'htScore'=> null,
                    'eventName' => $game['EventName'],
                    'league' => $game['CompetitionName'],
                );
            }
        }
        return $set;
    }

}

/*end of file*/
