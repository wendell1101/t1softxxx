<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
* Game Provider: Gold Deluxe
* Game Type: Casino and slots
* Wallet Type: Seamless
*
* @category Game_platform
* @version not specified
* @integrator @jerbey.php.ph

    Related File
    -routes.php
    -gold_deluxe_service_api.php

    Note: RNG GAMES NOT YET INCLUDED, 
        if client want it, we need to add it.
**/

abstract class Abstract_game_api_common_gd_seamless extends Abstract_game_api {
    const MD5_FIELDS_FOR_ORIGINAL = ['bet_time','balance_time','product_id','bet_type','bet_amount','winloss','bet_result','start_balance','end_balance','bet_arrays'];
    const MD5_FLOAT_AMOUNT_FIELDS = ['bet_amount','winloss','start_balance','end_balance'];
    const MD5_FIELDS_FOR_MERGE=['player_username','game_code','bet_at','end_at','bet_result','bet_details','round_number'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=['bet_amount','real_bet_amount','result_amount'];

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(array('original_game_logs_model','player_model'));
        $this->api_url = $this->getSystemInfo('url');
        $this->api_key = $this->getSystemInfo('key');
        $this->game_launcher_url = $this->getSystemInfo('game_launcher_url');
        $this->merchant_id = $this->getSystemInfo('merchant_id');
        $this->currency_code = $this->getSystemInfo('currency_code');
        $this->merge_after_balance = $this->getSystemInfo('merge_after_balance',false);
        $this->allow_sync_anchor_tips = $this->getSystemInfo('allow_sync_anchor_tips',false);
        $this->isRedirect = $this->getSystemInfo('isRedirect',false);
    }

    const GD_syncGameRecordsLive = 'cGetBetHistory';
    const GD_syncGameRecordsSlots = 'cGetRNGBetHistory';
    const GD_syncTipLogs = 'cGetAnchorTipsLog';
    const URI_MAP = array(
        self::API_logout => 'cLogoutPlayer',
    );

    const MESSAGE_ID_PREFIX_TRANSACTION_STATUS = 'S';
    const MESSAGE_ID_PREFIX_CREATE_MEMBER = 'M';
    const MESSAGE_ID_PREFIX_DEPOSIT = 'D';
    const MESSAGE_ID_PREFIX_WITHDRAWAL = 'W';
    const MESSAGE_ID_PREFIX_BET_HISTORY_LIVE = 'H';
    const MESSAGE_ID_PREFIX_BET_HISTORY_SLOTS = 'R';
    const MESSAGE_ID_PREFIX_MEMBER_BALANCE = 'C';
    const MESSAGE_ID_PREFIX_MEMBER_LOGOUT = 'L';
    const MESSAGE_ID_PREFIX_ANCHOR_TIPS = 'AT';
    const TIP = 'Tip';
    const CODE_SUCCESS = 0;

    const ShowBalance = '1';
    const Index = '0';
    const ShowRefID = '1';

    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode()
    {
        return $this->returnUnimplemented();
    }

    public function generateUrl($apiName, $params) {
        // echo "<pre>";
        // print_r($this->api_url);exit();
        return $this->api_url;
    }

    protected function customHttpCall($ch, $params) {

        $random_string = substr(date('YmdHis'), 2) . random_string('alnum', 5);

        switch ($this->currentProcess) {
            case self::URI_MAP[self::API_logout]:
                $header = array(
                    "Method" => self::URI_MAP[self::API_logout],
                    "MerchantID" => $this->merchant_id,
                    'MessageID' => self::MESSAGE_ID_PREFIX_MEMBER_LOGOUT . $random_string,
                );
                break;

            case self::GD_syncGameRecordsLive:
                $header = array(
                    "Method" => self::GD_syncGameRecordsLive,
                    "MerchantID" => $this->merchant_id,
                    'MessageID' => self::MESSAGE_ID_PREFIX_BET_HISTORY_LIVE . $random_string,
                );
                break;

            case self::GD_syncGameRecordsSlots:
                $header = array(
                    "Method" => self::GD_syncGameRecordsSlots,
                    "MerchantID" => $this->merchant_id,
                    'MessageID' => self::MESSAGE_ID_PREFIX_BET_HISTORY_SLOTS . $random_string,
                );
                break;

            case self::GD_syncTipLogs:
                $header = array(
                    "Method" => self::GD_syncTipLogs,
                    "MerchantID" => $this->merchant_id,
                    'MessageID' => self::MESSAGE_ID_PREFIX_ANCHOR_TIPS . $random_string,
                );
                break;
        }

        $data = array(
            'Header' => $header,
            'Param' => $params,
        );

        $xml_object = new SimpleXMLElement("<Request></Request>");
        $xmlData = $this->CI->utils->arrayToXml($data, $xml_object);

        $this->CI->utils->debug_log('-----------------------GD POST XML STRING ----------------------------',$xmlData);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData);
    }



    public function processResultBoolean($responseResultId, $resultArr, $playerName=null){
        $success = true;
        if(isset($resultArr['Header']['ErrorCode']) && isset($resultArr['Header']['Method'])){
            $method = $resultArr['Header']['Method'];
            if(empty($method)){
                $success = false;
            }
        }

        if(! $success){
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('GD SEAMLES Game Got Error! =========================> ',$responseResultId,'playerName ',$playerName,'result ',$resultArr);
        }

        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null){
        // create player on game provider auth
        $return = parent::createPlayer($playerName, $playerId, $password, $email, $extra); 
        $success = false;
        $message = "Unable to create account for gd seamless api";
        if($return){
            $success = true;
            $message = "Successfull create account for gd seamless api";
        }
        
        return array("success" => $success, "message" => $message);
    }


    public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=>true,
        );
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function processResultForQueryTransaction($params) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerBalance($playerName){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true, 
            'balance' => $balance
        );

        return $result;
    }

    public function processResultForQueryPlayerBalance($params) {
        return $this->returnUnimplemented();
    }

    public function getLauncherLanguage($language){
        $this->CI->load->library("language_function");
        switch ($language) {
            case 'zh-cn':
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
                $lang = 'zh-cn';
                break;
            case 'en-us':
            case LANGUAGE_FUNCTION::INT_LANG_ENGLISH:
                $lang = 'en-us';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
                $lang = 'vi';
                break;
            case 'th':
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
                $lang = 'th';
                break;
            default:
                $lang = 'en-us';
                break;
        }

        return $lang;
    }

    private function getPlayerGDCurrency($username){
        # use correct currency code
        $playerId = $this->getPlayerIdInGameProviderAuth($username);
        if(!is_null($playerId)){
            $this->CI->load->model(array('player_model'));
            $currencyCode = $this->CI->player_model->getPlayerCurrencyByPlayerId($playerId);
            if(!is_null($currencyCode)){
                return $currencyCode;
            }else{
                return $this->currency_code;
            }
        }else{
            return $this->currency_code;
        }
    }

    public function queryForwardGame($playerName, $extra){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $AAA = $this->merchant_id; //OperatorCode
        $BBB = $this->getLauncherLanguage($extra['language']); //"en-us";//lang
        $CCC = $gameUsername; //playerid
        // $DDD = random_string('alnum', 128); //LoginTokenID
        $playerId = $this->getPlayerIdInPlayer($playerName);
        $DDD = $this->getPlayerToken($playerId);
        $EEE = $this->getPlayerGDCurrency($gameUsername); //Currency
        $FFF = hash('sha256', $AAA . $DDD . $this->api_key . $CCC . $EEE);
        $view = $extra['game_code']; //'RNG4583';

        $params = array(
            'OperatorCode' => $AAA,
            'lang' => $BBB,
            'playerid' => $CCC,
            'LoginTokenID' => $DDD,
            'Currency' => $EEE,
            'Key' => $FFF,
            'view' => $view,
        );

        if($extra['game_type']=='slots'){
            $params['mode'] =  $extra['game_mode'] == "real"?$extra['game_mode']:"fun";
        }
        $params['mobile'] = $extra['is_mobile']?"2":"0";

        $params_string = http_build_query($params);
        $link = $this->game_launcher_url . "?" . $params_string;
        $this->utils->debug_log('==========================================>link===========>', $link);
        return array('success' => true, 'url' => $link , 'redirect' => $this->isRedirect);
    }

    public function getAnchorTipLogs($token){
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate->modify($this->getDatetimeAdjust());

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate = $startDate->format('m/d/Y H:i:s');
        $endDate = $endDate->format('m/d/Y H:i:s');

        $this->currentProcess = self::GD_syncTipLogs;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
            'current_process' => $this->currentProcess
        );

        $params = array(
            'FromTime' => $startDate,
            'ToTime' => $endDate,
            'Index' => self::Index,
        );

        $this->utils->debug_log('GD SEAMLESS SYNC TIPS PARAMS', $params);
        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function syncLostAndFound($token) {
        if($this->allow_sync_anchor_tips){
            return $this->getAnchorTipLogs($token);
        }
    }

    public function syncOriginalGameLogs($token = false){

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate->modify($this->getDatetimeAdjust());

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate = $startDate->format('m/d/Y H:i:s');
        $endDate = $endDate->format('m/d/Y H:i:s');

        $this->currentProcess = self::GD_syncGameRecordsLive;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
            'current_process' => $this->currentProcess
        );

        $params = array(

            'FromTime' => $startDate,
            'ToTime' => $endDate,
            'Index' => self::Index,
            'ShowBalance' => self::ShowBalance,
            'ShowRefID' => self::ShowRefID,
        );

        $this->utils->debug_log('GD SEAMLESS SYNC PARAMS', $params);
        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForSyncGameRecords($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $current_process = $this->getVariableFromContext($params, 'current_process');

        if(isset($resultXml)){
            $resultArr = json_decode(json_encode($resultXml), true);
        }    

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );

        $success = $this->processResultBoolean($responseResultId, $resultArr); 
        if($success){
            if(isset($resultArr['Param'])){
                $responseData = array();
                $gameRecords = array();
                if($current_process == self::GD_syncGameRecordsLive){
                    $responseData = isset($resultArr['Param']['BetInfo']) ? $resultArr['Param']['BetInfo'] : null;   
                }

                if($current_process == self::GD_syncTipLogs){
                    $responseData = isset($resultArr['Param']['TipsInfo']['Info']) ? $resultArr['Param']['TipsInfo']['Info'] : null;               
                }

                if(!empty($responseData)){
                    //check if associative array or sequential array
                    // if(array_keys($responseData) !== range(0, count($responseData) - 1)) {
                    //     $gameRecords[]=$responseData;
                    // } else {
                    //     $gameRecords = $responseData;
                    // }
                    if(isset($responseData[0]['UserID'])){//check if some of the required field is exist on the response
                        $gameRecords = $responseData;
                    } else {
                        $gameRecords[]=$responseData;
                    }
                }

                if(!empty($gameRecords)){
                    $this->processGameRecords($gameRecords,$responseResultId);

                    list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                        $this->original_gamelogs_table,
                        $gameRecords,
                        'external_uniqueid',
                        'external_uniqueid',
                        self::MD5_FIELDS_FOR_ORIGINAL,
                        'md5_sum',
                        'id',
                        self::MD5_FLOAT_AMOUNT_FIELDS
                    );
                    
                    $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

                    $dataResult['data_count'] = count($gameRecords);
                    if (!empty($insertRows)) {
                        $dataResult['data_count_insert'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
                    }
                    unset($insertRows);

                    if (!empty($updateRows)) {
                        $dataResult['data_count_update'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
                    }
                    unset($updateRows);
                }
            }
        }

        return array($success, $dataResult);
    }


    public function processGameRecords(&$gameRecords, $responseResultId){
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $data['user_id'] = isset($record['UserID']) ? $record['UserID'] : null;
                $data['currency'] = isset($record['Currency']) ? $record['Currency'] : null;
                $data['bet_time'] = isset($record['BetTime']) ? $this->gameTimeToServerTime($record['BetTime']) : null;//nn
                $data['balance_time'] = isset($record['BalanceTime']) ? $this->gameTimeToServerTime($record['BalanceTime']) : null;//nn
                $data['product_id'] = isset($record['ProductID']) ? $record['ProductID'] : null;
                $data['client_type'] = isset($record['ClientType']) ? $record['ClientType'] : null;
                $data['game_interface'] = isset($record['GameInterface']) ? $record['GameInterface'] : null;
                $data['bet_id'] = isset($record['BetID']) ? $record['BetID'] : null;
                $data['bet_type'] = isset($record['BetType']) ? $record['BetType'] : null;
                $data['bet_amount'] = isset($record['BetAmount']) ? $record['BetAmount'] : 0;//nn
                $data['winloss'] = isset($record['WinLoss']) ? $record['WinLoss'] : 0;//nn
                $data['bet_result'] = isset($record['BetResult']) ? $record['BetResult'] : null;
                $data['start_balance'] = isset($record['StartBalance']) ? $record['StartBalance'] : 0;//nn
                //add extra checking on endbalance , check if not empty or not array
                $data['end_balance'] = ( isset($record['EndBalance']) && !is_array($record['EndBalance']) && !empty($record['EndBalance']) ) ? $record['EndBalance'] : 0;//nn
                $data['transaction_id'] = isset($record['TransactionID']) ? $record['TransactionID'] : null;
                $data['bet_arrays'] = isset($record['BetArrays']) ? json_encode($record['BetArrays']) : null;

                $data['response_result_id'] = $responseResultId;
                $data['external_uniqueid'] = isset($record['BetID']) ? $record['BetID'] : null;

                

                if(isset($record['TipsID'])){ //override some key for tip data
                    $data['bet_time'] = isset($record['Time']) ? $this->gameTimeToServerTime($record['Time']) : null;
                    $data['balance_time'] = isset($record['Time']) ? $this->gameTimeToServerTime($record['Time']) : null;
                    $data['external_uniqueid'] = $record['UserID'].'-'.$record['TipsID'].'-'.$record['AnchorID'];
                    $data['bet_arrays'] = json_encode($record);
                    $data['bet_result'] = $data['product_id'] = $data['bet_type'] = self::TIP;
                    $data['winloss'] = isset($record['Amount']) ? - $record['Amount'] : null;//nn
                }

                $gameRecords[$index] = $data;
                unset($data);
            }
        }
    }


    private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function syncMergeToGameLogs($token){
        $enabled_game_logs_unsettle=true;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

        /**
     * queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime='gds.bet_time >= ? and gds.bet_time <= ?';
        $sql = <<<EOD
SELECT gds.id as sync_index,
gds.user_id as player_username,
gds.product_id as game, 
gds.product_id as game_code, 
gds.transaction_id,
gds.transaction_id as round_number,
gds.bet_amount,
gds.bet_amount as real_bet_amount,
gds.winloss,
gds.bet_amount,
gds.bet_time as bet_at,
gds.bet_time as start_at,
gds.balance_time as end_at,
gds.bet_result,
gds.bet_arrays as bet_details,
gds.bet_arrays,
gds.response_result_id,
gds.external_uniqueid,
gds.created_at,
gds.updated_at,
gds.md5_sum,

game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM {$this->original_gamelogs_table} as gds
LEFT JOIN game_description as gd ON gds.product_id = gd.game_code AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON gds.user_id = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
WHERE

{$sqlTime}

EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(),
          $dateFrom,$dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    /**
     * overview : get game description information
     *
     * @param $row
     * @param $unknownGame
     * @param $gameDescIdMap
     * @return array
     */
    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;
        $game_type_id = null;
        if (isset($row['game_description_id'])) {
            $game_description_id = $row['game_description_id'];
            $game_type_id = $row['game_type_id'];
        }

        if(empty($game_description_id)){
            $gameDescId=$this->CI->game_description_model->processUnknownGame($this->getPlatformCode(),
                $unknownGame->game_type_id, $row['game'], $row['game_code']);
        }

        return [$game_description_id, $game_type_id];
    }

    /**
     * queryTransactions
     * @param  string $transaction_id
     * @return array
     */
    public function queryTransactions($transaction_id){
        $trans='qrt.transaction_id = ? ';
        $sql = <<<EOD
SELECT qrt.id as sync_index,
qrt.action,
qrt.transaction_id,
qrt.bet_info,
qrt.before_balance,
qrt.after_balance

FROM gd_seamless_wallet_transactions as qrt
WHERE

{$trans}

EOD;

        $params=[$transaction_id];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }


    /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
        $game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];

        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }
        
        $row['extra_info'] = array();
        if($row['bet_result'] == self::TIP){
            $row['result_amount'] = 0;
            $bet_details = json_decode($row['bet_details'],true);
            $row['extra_info'] = array("note" => self::TIP);
        } else {
            $row['result_amount'] = $row['winloss']-$row['bet_amount'];
            $bet_details = json_decode($row['bet_details'],true)['Bet'];
        }


        $row['bet_details']= $bet_details;
        $row['game_description_id']=$game_description_id;
        $row['game_type_id']=$game_type_id;
        $row['status']= $this->getStatusValue($row['bet_result']);
    }

    /**
     * overview : get status
     *
     * @param $bet_result
     * @return int
     */

    public function getStatusValue($bet_result){
        //Win/Loss/Tie/Cancel/Voided
        switch (strtolower($bet_result)) {
            case 'win':
            case 'loss':
            case 'tie':
            case 'tip'://allow display tips
                $status =  Game_logs::STATUS_SETTLED;
                break;
            case 'cancel':
                $status = Game_logs::STATUS_CANCELLED;
                break;
            case 'voided':
                $status = Game_logs::STATUS_VOID;
                break;
            
            default:
                $status = Game_logs::STATUS_SETTLED;
                break;
        }
        return $status;
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {

        $extra_info=$row['extra_info'];
        $has_both_side=0;

        if(empty($row['md5_sum'])){
            //genereate md5 sum
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            //set game_type to null unless we know exactly game type name from original game logs
            'game_info'=>['game_type_id'=>$row['game_type_id'], 'game_description_id'=>$row['game_description_id'],
                'game_code'=>$row['game_code'], 'game_type'=>null, 'game'=>$row['game']],
            'player_info'=>['player_id'=>$row['player_id'], 'player_username'=>$row['player_username']],
            'amount_info'=>['bet_amount'=>$row['bet_amount'], 'result_amount'=>$row['result_amount'],
                'bet_for_cashback'=>$row['bet_amount'], 'real_betting_amount'=>$row['real_bet_amount'],
                'win_amount'=>null, 'loss_amount'=>null, 'after_balance'=>null],
            'date_info'=>['start_at'=>$row['start_at'], 'end_at'=>$row['end_at'], 'bet_at'=>$row['bet_at'],
                'updated_at'=>$row['updated_at']],
            'flag'=>Game_logs::FLAG_GAME,
            'status'=>$row['status'],
            'additional_info'=>['has_both_side'=>$has_both_side, 'external_uniqueid'=>$row['external_uniqueid'], 'round_number'=>$row['round_number'],
                'md5_sum'=>$row['md5_sum'], 'response_result_id'=>$row['response_result_id'], 'sync_index'=>$row['sync_index'],
                'bet_type'=>null ],
            'bet_details'=>$row['bet_details'],
            'extra'=>$extra_info,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
    }

    public function logout($playerName, $password = null) {
        $this->currentProcess = self::URI_MAP[self::API_logout];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'playerName' => $playerName,
        );

        $params = array(
            'UserID' => $gameUsername,
        );

        return $this->callApi(self::API_logout, $params, $context);
    }

    public function processResultForLogout($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultXml = $this->getResultXmlFromParams($params);
        $resultArr = json_decode(json_encode($resultXml), true);
        $playerName = $this->getVariableFromContext($params,'playerName');
 
        $success = false;
        if ($this->processResultBoolean($responseResultId, $resultArr, $playerName)) {
            $success = true;
        } else {
            $success = false;
        }
        return array($success, null);
    }
}