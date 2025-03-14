<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
* Game Provider: Sexy Baccarat
* Game Type: Live Casino
* Wallet Type: Seamless
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @mccoy.php.ph

    Related File
     - sexybaccarat_service_api.php
     - sexy_baccarat_transactions.php
    
**/

abstract class Abstract_game_api_common_sexy_baccarat extends Abstract_game_api {

	const MD5_FIELDS_FOR_ORIGINAL= [
        //unique id
        'platformTxId',
        'roundId',
        'betAmount',
        'winAmount',
        'userId',
        'gameName',
        'gameCode',
        'gameType',
        'betTime',
        'gameInfo',
        'external_uniqueid',
        'bet_status'
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'betAmount',
        'winAmount',
        'after_balance',
        'before_balance',
    ];

    // Fields in game_logs we want to detect changes for merge, and only available when original md5_sum is empty
    const MD5_FIELDS_FOR_MERGE = [
        'external_uniqueid',
        'bet_amount',
        'real_betting_amount',
        //'result_amount',
        'win_amount',
        'round_number',
        'game_code',
        'game_name',
        'game_description_id',
        'player_username',        
        'updated_at'
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'win_amount',
        'real_betting_amount',
        'result_amount',
    ];

    const URI_MAP = [
    	self::API_createPlayer => '/wallet/createMember',
    	self::API_login => '/wallet/login',
    	// self::API_queryForwardGame => '/wallet/doLoginAndLaunchGame',
        self::API_queryForwardGame => '/wallet/login',
    	self::API_logout => '/wallet/logout',
    	self::API_syncGameRecords => '/wallet/getSummaryByBetTimeHour',
        self::API_setMemberBetSetting => '/wallet/updateBetLimit',
        self::API_updatePlayerInfo => '/wallet/updateBetLimit',
    ];

    const GAME_TYPE = 'LIVE';
    const GAME_PLATFORM = 'SEXYBCRT';
    const SUCCESS = 0000;

    public function __construct() {
    	parent::__construct();
    	$this->CI->load->model(array('original_game_logs_model','player_model'));
    	$this->cert = $this->getSystemInfo('cert', 'kDrU6Afbv7oObmhRNn0');
    	$this->secret_key = $this->getSystemInfo('secret_key', '');
    	$this->agentId = $this->getSystemInfo('agentId', 'sexycasino1');
    	$this->gamePlatformId = $this->getPlatformCode();
    	$this->currency = $this->getSystemInfo('currency', 'THB');
    	$this->limitId = $this->getSystemInfo('limitId', 280905);
        $this->api_url = $this->getSystemInfo('url');
        $this->original_gamelogs_table = $this->getOriginalTable();
        $this->language = $this->getSystemInfo('language', 'en');
        $this->external_url = $this->getSystemInfo('external_url');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');
        $this->sv388_maxbet = $this->getSystemInfo('sv388_maxbet', 1000); //required for sv388
        $this->sv388_minbet = $this->getSystemInfo('sv388_minbet', 1); //required for sv388
        $this->sv388_mindraw = $this->getSystemInfo('sv388_mindraw', 1); //required for sv388
        $this->sv388_maxdraw = $this->getSystemInfo('sv388_maxdraw', 100); //required for sv388
        $this->sv388_matchlimit = $this->getSystemInfo('sv388_matchlimit', 1000); //required for sv388
        // $this->getSystemInfo('testing_dynamic_secret_key','secret_unique');

        # fix exceed game username length
        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 4);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 12);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 8);

        $this->is_redirect = $this->getSystemInfo('is_redirect', false);
        $this->use_insert_ignore = $this->getSystemInfo('use_insert_ignore', true);
        $this->use_single_bet_deduction = $this->getSystemInfo('use_single_bet_deduction', true);


        $this->use_new_sync_method = $this->getSystemInfo('use_new_sync_method', true);
    }

    public function isSeamLessGame()
    {
       return true;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function getPlatformCode() {
        return $this->returnUnimplemented();
    }

    public function generateUrl($apiName, $params) {
        return $this->api_url.self::URI_MAP[$apiName];
    }

    public function getHttpHeaders($params){

        return array("Content-Type: ".'application/x-www-form-urlencoded');

    }

    protected function customHttpCall($ch, $params) {
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$this->utils->debug_log('SEXY BACCARAT POSTFEILD: ',$params);
	}

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        $success = ($resultArr['status'] == self::SUCCESS) ? true : false;
        $result = array();
        if(!$success){
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('SEXY BACCARAT got error ======================================>', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }

	public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {

        $extra = [
            'prefix' => $this->prefix_for_username,

            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
        ];

        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = [
                'callback_obj' => $this,
                'callback_method' => 'processResultForCreatePlayer',
                'gameUsername' => $gameUsername,
                'playerName' => $playerName,
            ];

        $betLimit = json_encode(array(
        	self::GAME_PLATFORM => [
        		self::GAME_TYPE => [
                    'limitId' => $this->limitId
        		]
        	]
        ));

        // $betLimit = json_encode($betLimitInfo,true);

        $params = [
            'cert' => $this->cert,
            'agentId' => $this->agentId,
            'userId' => $gameUsername,
            'currency' => $this->getCurrency(),
            'betLimit' => $betLimit,
            'language' => $this->language
        ];

        $this->CI->utils->debug_log('<-------------------------PARAMS------------------------->', $params);

        return $this->callApi(self::API_createPlayer, $params, $context); 
    }

    public function processResultForCreatePlayer($params) {
    	$playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        // print_r($resultArr);exit;
        $result = array();
        $result['response_result_id'] = $responseResultId;
        if($success){
            $result['status'] = $resultArr['status'];
            $result['msg'] = $resultArr['desc'];
        }
        return array($success, $result);
    }

	public function depositToGame($playerName, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;

        $player_id = $this->getPlayerIdFromUsername($playerName);
        $playerBalance = $this->queryPlayerBalance($playerName);
        $afterBalance = @$playerBalance['balance'];
        if(empty($transfer_secure_id)){
            $external_transaction_id = $this->utils->getTimestampNow();
        }

        $transaction = $this->insertTransactionToGameLogs($player_id, $playerName, $afterBalance, $amount, NULL,$this->transTypeMainWalletToSubWallet());

        $this->utils->debug_log('<---------------Sexy Baccarat------------> External Transaction ID: ', $external_transaction_id);

        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
        );
    }
    public function withdrawFromGame($playerName, $amount, $transfer_secure_id = null) {
        $external_transaction_id = $transfer_secure_id;

        $player_id = $this->getPlayerIdFromUsername($playerName);
        $playerBalance = $this->queryPlayerBalance($playerName);
        $afterBalance = @$playerBalance['balance'];
        if(empty($transfer_secure_id)){
            $external_transaction_id = $this->utils->getTimestampNow();
        }

        $this->insertTransactionToGameLogs($player_id, $playerName, $afterBalance, $amount, NULL,$this->transTypeSubWalletToMainWallet());

        $this->utils->debug_log('<---------------Sexy Baccarat------------> External Transaction ID: ', $external_transaction_id);

        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'response_result_id ' => NULL,
        );
    }
    public function queryPlayerBalance($playerName) {
        // $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true, 
            'balance' => $balance
        );

        $this->utils->debug_log('<---------------Sexy Baccarat------------> Query Player Balance: ', $result);

        return $result;
    }
    public function queryPlayerBalanceByPlayerId($playerId) {
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true, 
            'balance' => $balance
        );

        $this->utils->debug_log('<---------------Sexy Baccarat------------> Query Player Balance by player id: ', $result);

        return $result;
    }
    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function queryForwardGame($playerName, $extra) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        
        if(isset($extra['language'])){
            $language = $this->getLauncherLanguage($extra['language']);
        } else {
            $language = $this->getLauncherLanguage($this->language);
        }

        $context = [
                'callback_obj' => $this,
                'callback_method' => 'processResultForQueryForwardGame',
                'gameUsername' => $gameUsername,
                'playerName' => $playerName,
                'is_redirect' => $this->is_redirect
            ];

        $params = [
            'cert' => $this->cert,
            'agentId' => $this->agentId,
            'userId' => $gameUsername,
            'language' => $this->language,
            'externalURL' => $this->external_url
        ];

        // if(isset($extra['is_mobile'])){
        //     $ismobile = $extra['is_mobile'] ? true : false; 
        //     if($ismobile){
        //         $params['isMobileLogin'] = true;
        //     }
        // }

        $this->CI->utils->debug_log('<-------------------------PARAMS------------------------->', $params);

        return $this->callApi(self::API_queryForwardGame, $params, $context); 
    }

    protected function getLauncherLanguage($language) {
        $lang='';
        switch ($language) {
            case 1: case 'en': case 'EN': case "English": $lang = 'en'; break;
            case 2: case 'cn': case 'CN': case "Chinese": $lang = 'cn'; break;
            case 4: case 'vn': case 'VN': case "Vietnamese": $lang = 'vn'; break;
            case 5: case 'ko-kr': case 'KO-KR': case "Korean": $lang = 'ko'; break;
            case 6: case 'th': case 'TH': case "Thai": $lang = 'th'; break;
            case 7: case 'id': case 'ID': case "Indonesian": $lang = 'id'; break;
            default: $lang = 'en'; break;
        }
        return $lang;
    }

    public function processResultForQueryForwardGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);
        $is_redirect = $this->getVariableFromContext($params, 'is_redirect');
        $result = ['response_result_id' => $responseResultId];

        if($success){
            if(isset($resultArr['url'])){
                $result['url']=$resultArr['url'];
            }
        } else {
            $success = false;
        }

        return [$success, $result];
    }

    public function syncOriginalGameLogs($token) {

        if($this->use_new_sync_method){            
            return $this->returnUnimplemented();
            //return $this->newSyncOriginalGameLogs($token);
        }

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
        $endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
        $startDate->modify($this->getDatetimeAdjust());
        //observer the date format
        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate   = $endDate->format('Y-m-d H:i:s');

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );

        $gameRecords = $this->queryTransactions($startDate, $endDate);
        if(!empty($gameRecords)){
            $this->processGameRecords($gameRecords);
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
        return array('success'=>true, $dataResult);
    }

    public function newSyncOriginalGameLogs($token) {

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($startDate->format('Y-m-d H:i:s'));
        $endDate = new DateTime($endDate->format('Y-m-d H:i:s'));
        $startDate->modify($this->getDatetimeAdjust());
        //observer the date format
        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate   = $endDate->format('Y-m-d H:i:s');

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );

        $betTransactions = $this->queryBetTransactions($startDate, $endDate);
        
        $gameRecords = [];

        $this->newProcessGameRecords($betTransactions, $gameRecords);
        
        if(!empty($gameRecords)){
            
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
        return array('success'=>true, $dataResult);
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    if ($this->use_insert_ignore) {
                        $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal($this->original_gamelogs_table, $record);
                    } else {
                        $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                    }
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }


    public function processGameRecords(&$gameRecords) {
        // print_r($gameRecords);exit;
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $data['platformTxId'] = isset($record['platformTxId']) ? $record['platformTxId'] : null;
                $data['userId'] = isset($record['userId']) ? $record['userId'] : null;
                $data['currency'] = isset($record['currency']) ? $record['currency'] : $this->getCurrency();
                $data['platform'] = isset($record['platform']) ? $record['platform'] : null;
                $data['gameType'] = isset($record['gameType']) ? $record['gameType'] : null;
                $data['gameCode'] = isset($record['gameCode']) ? $record['gameCode'] : null;
                $data['gameName'] = isset($record['gameName']) ? $record['gameName'] : null;
                $data['betType'] = isset($record['betType']) ? $record['betType'] : null;
                $data['betAmount'] = isset($record['betAmount']) ? $record['betAmount'] : null;
                $data['winAmount'] = isset($record['winAmount']) ? $record['winAmount'] : null;
                // $betTime = date('Y-m-d H:i:s', strtotime($record['betTime']));
                // print_r($betTime);exit;
                $data['betTime'] = isset($record['betTime']) ? $this->convertBetTime($record['betTime']) : null;
                $data['roundId'] = isset($record['roundId']) ? $record['roundId'] : null;
                $data['gameInfo'] = isset($record['gameInfo']) ? $record['gameInfo'] : null;
                $data['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;
                $data['before_balance'] = isset($record['before_balance']) ? $record['before_balance'] : null;
                $data['action'] = isset($record['action']) ? $record['action'] : null;
                $data['response_result_id'] = isset($record['response_result_id']) ? $record['response_result_id'] : null;
                $data['external_uniqueid'] = isset($record['external_uniqueid']) ? $record['external_uniqueid'] : null;
                $data['md5_sum'] = isset($record['md5_sum']) ? $record['md5_sum'] : null;
                $data['tip_amount'] = isset($record['tip_amount']) ? $record['tip_amount'] : null;
                if(empty($data['betTime'])){
                    $data['betTime'] = isset($record['created_at']) ? $record['created_at'] : null;
                }

                if($data['action'] != 'cancelBet') {
                    $result = $this->querySettleTransactions($data['userId'], $data['roundId']);
                    if(!empty($result)) {
                        if(count($result) > 1) {
                            $totalBets=0;
                            $totalWin=0;
                            foreach ($result as $res) {
                                $totalBets+=$res['betAmount'];
                                $totalWin+=$res['winAmount'];
                            }
                            $data['winAmount'] = $totalWin;
                            $data['betAmount'] = $totalBets;
                        }
                        $getAfterBalance = reset($result)['before_balance'];
                        if($data['winAmount'] > 0) {
                            $data['after_balance'] = $getAfterBalance + $data['winAmount'];
                        } else {
                            $data['after_balance'] = $getAfterBalance;
                        }
                    }
                }
                $gameRecords[$index] = $data;
                unset($data);
            }
            $gameRecords = $this->processData($gameRecords,'roundId','userId');
        }

    }


    /*public function newProcessGameRecords($betTransactions, &$gameRecords) {

        foreach($betTransactions as $record ){
            $data = [];
            $transaction_id = $record['platformTxId'];
            $data['platformTxId'] = isset($record['platformTxId']) ? $record['platformTxId'] : null;
            $userId = $data['userId'] = isset($record['userId']) ? $record['userId'] : null;
            $data['currency'] = isset($record['currency']) ? $record['currency'] : $this->getCurrency();
            $data['platform'] = isset($record['platform']) ? $record['platform'] : null;
            $data['gameType'] = isset($record['gameType']) ? $record['gameType'] : null;
            $data['gameCode'] = isset($record['gameCode']) ? $record['gameCode'] : null;
            $data['gameName'] = isset($record['gameName']) ? $record['gameName'] : null;
            $data['betType'] = isset($record['betType']) ? $record['betType'] : null;
            $data['betAmount'] = isset($record['betAmount']) ? $record['betAmount'] : null;
            $data['winAmount'] = isset($record['winAmount']) ? $record['winAmount'] : null;
            $data['betTime'] = isset($record['betTime']) ? $this->convertBetTime($record['betTime']) : null;
            $data['roundId'] = isset($record['roundId']) ? $record['roundId'] : null;
            $data['gameInfo'] = isset($record['gameInfo']) ? $record['gameInfo'] : null;
            $data['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;
            $data['before_balance'] = isset($record['before_balance']) ? $record['before_balance'] : null;
            $data['action'] = isset($record['action']) ? $record['action'] : null;
            $data['response_result_id'] = isset($record['response_result_id']) ? $record['response_result_id'] : null;
            $data['external_uniqueid'] = isset($record['external_uniqueid']) ? $record['external_uniqueid'] : null;
            $data['md5_sum'] = isset($record['md5_sum']) ? $record['md5_sum'] : null;
            $data['tip_amount'] = isset($record['tip_amount']) ? $record['tip_amount'] : null;
            
            if($record['action']=='give'){
                $data['gameCode'] = "Give";
                $data['gameName'] = "Promotion Bonus";
            }

            if(empty($data['betTime'])){
                $data['betTime'] = isset($record['created_at']) ? $record['created_at'] : null;
            }

            if($record['action_status'] == Game_logs::STATUS_SETTLED){
                $data['bet_status'] = 'settled';
            }elseif($record['action_status'] == Game_logs::STATUS_CANCELLED){
                $data['bet_status'] = 'cancelled';
            }elseif($record['action_status'] == Game_logs::STATUS_REFUND){
                $data['bet_status'] = 'refunded';
            }elseif($record['action_status'] == Game_logs::STATUS_VOID){
                $data['bet_status'] = 'voided';
            }else{
                $data['bet_status'] = 'pending';
            }

            //get other transactions
            $totalBet = 0;
            $totalWin = 0;            
            $allTrans = $this->queryOtherTransactions($transaction_id, $userId);
            foreach($allTrans as $trans){
                if($trans['action']=='bet'){
                    $totalBet += $trans['betAmount'];
                }

                if($trans['action']=='settle' && $trans['action_status']==Game_logs::STATUS_SETTLED){
                    $totalBet += $trans['betAmount'];
                }

                if($trans['action']=='settle' && 
                $trans['action_status']<>Game_logs::STATUS_CANCELLED &&
                $trans['action_status']<>Game_logs::STATUS_VOID){
                    $totalWin += $trans['winAmount'];
                }

                if($trans['action']=='give'){
                    $totalWin += $trans['winAmount'];
                }

            }

            $data['winAmount'] = $totalWin;
            $data['betAmount'] = $totalBet;

            $gameRecords[] = $data;
        }

    }*/

    public function processData($betResults, $uniqueidField, $uniqueidField2) {
        $newArray=[];
        $deleteList=[];
        $idMap = [];
        for ($i=0; $i < count($betResults); $i++) {
            $key=$betResults[$i][$uniqueidField];
            $key2=$betResults[$i][$uniqueidField2];
            if($betResults[$i]['action'] != 'cancelBet' && $betResults[$i]['action'] != 'tip') {
                if(!isset($idMap[$key][$key2])){
                    $idMap[$key][$key2]=[$i];
                }else{
                    $idMap[$key][$key2][]=$i;
                }
            }
        }
        if(!empty($idMap)){
            foreach ($idMap as $key => $indexArr) {
                foreach ($indexArr as $key => $index) {
                    if(count($index)>1){
                        if(count($index)>2){
                            //keep last one
                            unset($index[count($index)-1]);
                            foreach ($index as $idx) {
                                $deleteList[]=$idx;
                            }
                        } else {
                            $deleteList[]=$index[0];
                        }
                    }
                }
            }
        }
        if(!empty($deleteList)){
            foreach ($deleteList as $delIndex) {
                $this->utils->debug_log('delete row', $betResults[$delIndex]);
                unset($betResults[$delIndex]);
            }
        }
        unset($idMap);
        unset($deleteList);
        
        return array_values($betResults);
    }

    public function querySettleTransactions($userId, $roundId){
        $sqlTime='sb.userId = ? and sb.roundId = ? and action in ("settle")';
        $sql = <<<EOD
SELECT 
sb.userId,
sb.action,
sb.platformTxId,
sb.platform,
sb.gameType,
sb.gameCode,
sb.gameName,
sb.betType,
sb.betAmount,
sb.winAmount,
sb.betTime,
sb.roundId,
sb.gameInfo,
sb.before_balance,
sb.after_balance,
sb.response_result_id,
sb.created_at,
sb.updated_at,
sb.external_uniqueid,
sb.md5_sum,
sb.currency,
sb.response_result_id

FROM sexy_baccarat_transactions as sb
WHERE

{$sqlTime}

EOD;

        $params=[$userId,$roundId];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }


    /**
     * queryBetTransactions
     * @param  string $dateFrom
     * @param  string $dateTo
     * @return array
     */
    public function queryTransactions($dateFrom, $dateTo){
        $sqlTime='sb.created_at >= ? and sb.created_at <= ? and action in ("settle","cancelBet", "tip")';
        $sql = <<<EOD
SELECT 
sb.userId,
sb.action,
sb.platformTxId,
sb.platform,
sb.gameType,
sb.gameCode,
sb.gameName,
sb.betType,
sb.betAmount,
sb.betAmount,
sb.winAmount,
sb.betTime,
sb.roundId,
sb.gameInfo,
sb.before_balance,
sb.after_balance,
sb.response_result_id,
sb.created_at,
sb.updated_at,
sb.external_uniqueid,
sb.md5_sum,
sb.currency,
sb.response_result_id,
sb.tip_amount

FROM sexy_baccarat_transactions as sb
WHERE

{$sqlTime}

EOD;

        $params=[$dateFrom,$dateTo];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }
    
    public function queryBetTransactions($dateFrom, $dateTo){
        $sqlTime='sb.updated_at >= ? and sb.updated_at <= ? and (action = "bet" OR action = "give")';

        $sql = <<<EOD
SELECT 
sb.userId,
sb.action,
sb.platformTxId,
sb.platform,
sb.gameType,
sb.gameCode,
sb.gameName,
sb.betType,
sb.betAmount,
sb.action_status,
sb.winAmount,
sb.betTime,
sb.roundId,
sb.gameInfo,
sb.before_balance,
sb.after_balance,
sb.response_result_id,
sb.created_at,
sb.updated_at,
sb.external_uniqueid,
sb.md5_sum,
sb.currency,
sb.response_result_id,
sb.tip_amount
FROM sexy_baccarat_transactions as sb
WHERE

{$sqlTime}

EOD;

        $params=[$dateFrom,$dateTo];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }
    
    public function queryOtherTransactions($transactionId, $userId){        

        $sql = <<<EOD
SELECT 
sb.*
FROM sexy_baccarat_transactions as sb
WHERE
sb.platformTxId = ? AND sb.userId = ?
ORDER BY id asc;
EOD;

        $params=[$transactionId, $userId];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

        /**
     * queryResultTransactions
     * @param  string $dateFrom
     * @param  string $dateTo
     * @return array
     */
    public function queryResultTransactions($platformTxId, $roundId){
        $sqlTime='sb.platformTxId = ? and sb.roundId = ? and (action != "unsettle" or action != "bet" or action != "cancelBet")';
        $sql = <<<EOD
SELECT sb.id as sync_index,
sb.userId,
sb.action,
sb.platformTxId,
sb.platform,
sb.gameType,
sb.gameCode,
sb.gameName,
sb.betType,
sb.betAmount,
sb.betAmount,
sb.winAmount,
sb.betTime,
sb.roundId,
sb.gameInfo,
sb.before_balance,
sb.after_balance,
sb.response_result_id,
sb.created_at,
sb.updated_at,
sb.external_uniqueid,
sb.md5_sum,
sb.currency,
sb.response_result_id

FROM sexy_baccarat_transactions as sb
WHERE

{$sqlTime}

EOD;

        $params=[$platformTxId,$roundId];
        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return end($result);
    }

	public function syncMergeToGameLogs($token) {

        $enabled_game_logs_unsettle=true;

        if($this->use_new_sync_method){            
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogsFromTrans'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
            [$this, 'preprocessOriginalRowForGameLogsFromTrans'],
            $enabled_game_logs_unsettle);
        }

        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    /** queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        //only one time field
        $sqlTime='sb.updated_at >= ? AND sb.updated_at <= ?';
        if($use_bet_time){
            $sqlTime='sb.betTime >= ? AND sb.betTime <= ?';
        }

        $sql = <<<EOD
SELECT
sb.id as sync_index,
sb.response_result_id,
sb.external_uniqueid,
sb.md5_sum,

sb.userId as player_username,
sb.action,
sb.platformTxId,
sb.platform,
sb.gameType as game_type,
sb.gameCode as game_code,
sb.gameName as game_name,
sb.betType as bet_type,
sb.betAmount as bet_amount,
sb.betAmount as real_betting_amount,
sb.winAmount as win_amount,
sb.betTime as bet_at,
sb.betTime as start_at,
sb.betTime as end_at,
sb.roundId as round_number,
sb.gameInfo,
sb.before_balance,
sb.after_balance,
sb.response_result_id,
sb.created_at,
sb.updated_at,
sb.tip_amount,
sb.bet_status,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM sexy_baccarat_game_logs as sb
LEFT JOIN game_description as gd ON sb.gameCode = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON sb.userId = game_provider_auth.login_name
AND game_provider_auth.game_provider_id=?
WHERE
{$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $this->debug_log('merge sql', $sql, $params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    /** queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time){
        //only one time field
        $this->action_for_merge = $this->getSystemInfo('action_for_merge', ['bet', 'settle', 'tip', 'give','resettle']);
        $sqlTime='sb.updated_at >= ? AND sb.updated_at <= ?';

        if(!empty($this->action_for_merge) && is_array($this->action_for_merge)){
            $sqlTime.= ' and action in ("' . implode('","',$this->action_for_merge) .'")';
        }
        
        if($use_bet_time){
            //$sqlTime='sb.createdAt >= ? AND sb.createdAt <= ?';
        }

        $sql = <<<EOD
SELECT
sb.id as sync_index,
sb.response_result_id,
sb.external_uniqueid,
sb.betTime,
sb.betTime as bet_at,
sb.betTime as start_at,
sb.betTime as end_at,
sb.betType as bet_type,
sb.created_at,
sb.updated_at,
game_provider_auth.player_id,
sb.platformTxId as platformTxId,
sb.roundId as round_number,
sb.md5_sum,
sb.userId as player_username,
sb.action trans_type,
sb.action_status action_status,
sb.platformTxId,
sb.platform,
sb.after_balance,
sb.before_balance,
sb.action,
sb.action_status,
IF(sb.action='bet',sb.betAmount,0) bet_amount,
IF(sb.action='bet',sb.betAmount,0) real_betting_amount,
IF(sb.action='adjustBet',sb.betAmount,0) adjust_bet_amount,
IF(sb.action='settle' OR sb.action='give' OR sb.action='resettle',sb.winAmount,0) win_amount,
IF(sb.action='tip' AND sb.action_status<>?,sb.tip_amount,0) tip_amount,
IF(sb.action='give',sb.winAmount,0) give_amount,
sb.created_at,
sb.updated_at,
sb.gameType as game_type,
sb.gameCode as game_code,
sb.gameName as game_name,
gd.id as game_description_id,
gd.game_type_id,

sb.gameInfo

FROM sexy_baccarat_transactions as sb
LEFT JOIN game_description as gd ON sb.gameCode = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON sb.userId = game_provider_auth.login_name
AND game_provider_auth.game_provider_id=?
WHERE
{$sqlTime}
EOD;

        $params=[
            Game_logs::STATUS_CANCELLED,
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $this->debug_log('merge sql', $sql, $params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }



    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(empty($row['md5_sum'])){
            $this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        if($row['action'] == 'settle') {
            $row['result_amount'] = -$row['bet_amount'];
            $row['bet_amount'] = 0;
        } else {
            $row['result_amount'] = $row['win_amount'] - $row['bet_amount'];
        }

        $extra = [];
        if(isset($row['tip_amount']) && $row['tip_amount'] > 0){
            $row['result_amount'] = 0;
            $row['bet_amount'] = 0;
            $row['real_betting_amount'] = 0;
            $extra['note'] = lang("Tip amount"). ":{$row['tip_amount']}";
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => $row['game_type'],
                'game' => $row['game_name'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username'],
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['real_betting_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => $row['bet_type']
            ],
            'bet_details' => $row['bet_details'],
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }



    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row) {
        
        $row['result_amount'] = floatval($row['win_amount']) - floatval($row['bet_amount']);
        if($row['action'] == 'resettle'){
            $row['result_amount'] = $row['win_amount'];
        }


        if(!isset($row['md5_sum']) || empty($row['md5_sum'])){
            //$this->CI->utils->error_log('no md5 on ', $row['external_uniqueid']);
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        if(!empty($row['betTime'])){
            $betTime = date('Y-m-d H:i:s', strtotime($row['betTime']));
        }else{
            $betTime = $row['created_at'];
        }

        $row['bet_at'] = $betTime;
        $row['start_at'] = $betTime;
        $row['end_at'] = $betTime;

        /*if($row['bet_amount']===null){
            $row['result_amount'] = 0;
        }

        $row['result_amount'] = floatval($row['win_amount']) - floatval($row['bet_amount']);
        if($row['win_amount'] == 0) {
            $row['result_amount'] = -$row['bet_amount'];
            $row['bet_amount'] = $row['real_betting_amount'] = $row['bet_amount'] = 0;
        }*/

        $extra = [];
        if(isset($row['tip_amount']) && $row['tip_amount'] > 0){
            $row['result_amount'] = 0;
            $row['bet_amount'] = 0;
            $row['real_betting_amount'] = 0;
            $extra['note'] = lang("Tip amount"). ":{$row['tip_amount']}";
        }

        if(isset($row['give_amount']) && $row['give_amount'] > 0){
            $row['result_amount'] = $row['give_amount'];
            $row['bet_amount'] = 0;
            $row['real_betting_amount'] = 0;
            $extra['note'] = lang("Promotion Bonus"). ":{$row['give_amount']}";
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => $row['game_type'],
                'game' => $row['game_name'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username'],
            ],
            'amount_info' => [
                'bet_amount' => $row['bet_amount'],
                'result_amount' => $row['result_amount'],
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance'],
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => $row['bet_type']
            ],
            'bet_details' => $row['bet_details'],
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

    public function preprocessOriginalRowForGameLogsFromTrans(array &$row) {

        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        
        $status = $this->getGameRecordStatus($row['action_status']);
        $row['status'] = $status;
        $bet_details = $this->processBetDetails($row);
        $row['bet_details'] = $bet_details;

    }

    public function preprocessOriginalRowForGameLogs(array &$row) {

        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }
        
        $status = $this->getGameRecordStatus($row['bet_status']);
        $row['status'] = $status;
        $bet_details = $this->processBetDetails($row);
        $row['bet_details'] = $bet_details;

    }

    public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['game_name'];
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    private function getGameRecordStatus($recordStatus) {
        if($this->use_new_sync_method){
            if(empty($recordStatus)){
                return Game_logs::STATUS_PENDING;
            }
            return $recordStatus;            
        }

        switch ($recordStatus) {
            case 'settle':
            case 'tip':
                $status = Game_logs::STATUS_SETTLED;
                break;
            case 'bet':
                $status = Game_logs::STATUS_ACCEPTED;
                break;
            case 'unsettle':
            case 'adjustBet':
            case 'unvoidSettle':
            case 'unvoidBet':
                $status = Game_logs::STATUS_PENDING;
                break;
            case 'cancelBet':
                $status = Game_logs::STATUS_CANCELLED;
                break;
            case 'voidBet':
            case 'voidSettle':
                $status = Game_logs::STATUS_VOID;
                break;
            case 'refund':
                $status = Game_logs::STATUS_REFUND;
                break;
            
        }
        
        return $status;

    }

    private function processBetDetails($gameRecords) {
        if(!empty($gameRecords)) {
            $bet_details = array();
            $betRecords = json_decode($gameRecords['gameInfo'], true);

            if(isset($gameRecords['bet_type'])){
                $bet_details['BetType'] = $gameRecords['bet_type'];
            }

            if(isset($betRecords['winner'])){
                $bet_details['Winner'] = $betRecords['winner'];
            }

            if(isset($betRecords['result'])){
                $bet_details['Result'] = $betRecords['result'];
            }

            if(isset($betRecords['status'])){
                $bet_details['Status'] = $betRecords['status'];
            }

            if(isset($gameRecords['action'])){
                $bet_details['Action'] = $gameRecords['action'];
            }

        }

        return $bet_details;
    }

    private function convertBetTime($betTime){
        $time = date('Y-m-d H:i:s', strtotime($betTime));

        $betTime = $this->gameTimeToServerTime($time);

        return $betTime;
    }

    public function queryTransactionByDateTime($startDate, $endDate){
        $this->CI->load->model(array('original_game_logs_model'));

$sql = <<<EOD
SELECT 
gpa.player_id as player_id,
t.created_at transaction_date,
t.betAmount as bet_amount,
t.winAmount as win_amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.cancel_after as after_cancel_balance,
t.cancel_before as before_cancel_balance,
t.roundId as round_no,
t.external_uniqueid as external_uniqueid,
t.`action` trans_type
FROM {$this->original_transactions_table} as t
JOIN game_provider_auth gpa on gpa.login_name = t.userId
WHERE `t`.`updated_at` >= ? AND `t`.`updated_at` <= ? 
ORDER BY t.updated_at asc;

EOD;

$params=[$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    public function processTransactions(&$transactions){
        $temp_game_records = [];
      
        if(!empty($transactions)){
            foreach($transactions as $transaction){
                
                $temp_game_record = [];
                $temp_game_record['player_id'] = $transaction['player_id'];
                $temp_game_record['game_platform_id'] = $this->getPlatformCode();
                $temp_game_record['transaction_date'] = $transaction['transaction_date'];                
                $temp_game_record['before_balance'] = $transaction['before_balance'];
                $temp_game_record['after_balance'] = $transaction['after_balance'];
                $temp_game_record['round_no'] = $transaction['round_no'];
                $extra_info = [];
                $extra=[];
                $extra['trans_type'] = $transaction['trans_type'];
                $extra['extra'] = $extra_info;
                $temp_game_record['extra_info'] = json_encode($extra);
                $temp_game_record['external_uniqueid'] = $transaction['external_uniqueid'];

                $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                $temp_game_record['amount'] = abs($transaction['bet_amount']);   

                if($transaction['trans_type']=='bet'){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                    $temp_game_record['amount'] = abs($transaction['bet_amount']);   
                }elseif($transaction['trans_type']=='settle'){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                    $temp_game_record['amount'] = abs($transaction['win_amount']);   
                }elseif($transaction['trans_type']=='cancelBet'){
                    $temp_game_record['transaction_type'] = Transactions::GAME_API_ADD_SEAMLESS_BALANCE;
                    $temp_game_record['amount'] = abs($transaction['bet_amount']);   
                    $temp_game_record['before_balance'] = abs($transaction['before_balance']);
                    $temp_game_record['after_balance'] = abs($transaction['after_balance']);
                    $temp_game_record['amount'] = ($temp_game_record['after_balance'] - $temp_game_record['before_balance']);
                }else{
                    $diff = $temp_game_record['before_balance'] - $temp_game_record['after_balance'];
                    if($diff<0){
                        $temp_game_record['transaction_type'] = Transactions::GAME_API_SUBTRACT_SEAMLESS_BALANCE;
                    }
                }
                
                $temp_game_records[] = $temp_game_record;
                unset($temp_game_record);
            }
        }

        $transactions = $temp_game_records;
    }
    
    public function getUnsettledRounds($dateFrom, $dateTo){
        $sqlTime='`created_at` >= ? AND `created_at` <= ?';

        $this->CI->load->model(array('original_game_logs_model'));
        $finalResult = [];
        $this->original_transactions_table = $this->getTransactionsTable();
        $status = Game_logs::STATUS_PENDING;

        $sql = <<<EOD
select group_concat(`action`) as concat_action,  roundId as round_id, userId as game_username, platformTxId as transaction_id,
SUM(IF(`action` = 'settle', winAmount, 0)) as total_win,
SUM(IF(`action` = 'bet', betAmount, 0)) as sum_deduct
from {$this->original_transactions_table} 
where {$sqlTime} and `action` in ('bet','settle')
group by roundId, userId, platformTxId
having concat_action not like '%settle%';
EOD;

        $params=[            
            $dateFrom,
            $dateTo
		];
		
	    $this->CI->utils->debug_log('AE SEXY SEAMLESS (getUnsettledRounds)', 'params',$params,'sql',$sql);
        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

	public function checkBetStatus($data) {
        $this->CI->utils->debug_log('AE SEXY SEAMLESS (checkBetStatus)', $data);
        if(!isset($data['transaction_id']) || !isset($data['transaction_id'])){
            return array('success'=>false, 'exists'=>false);
        }

        $gamePlatformId = $this->getPlatformCode();        
        $this->CI->load->model(array('original_game_logs_model', 'seamless_missing_payout'));
        $this->original_transactions_table = $this->getTransactionsTable();
        $ispayoutexist = true;

        //check round if no refund
        $this->CI->db->from($this->original_transactions_table)
            ->where("platformTxId",$data['transaction_id'])
            ->where("roundId",$data['round_id'])
            ->where("action !=", 'bet')
            ->where("userId",$data['game_username']);
        $ispayoutexist = $this->CI->original_game_logs_model->runExistsResult();

        if($ispayoutexist){
            return array('success'=>true, 'exists'=>$ispayoutexist);
        }

        $transTable=$this->getTransactionsTable();

        //save record to missing payout report
$sql = <<<EOD
SELECT
t.created_at transaction_date, 
t.`action` transaction_type,
game_provider_auth.player_id,
t.roundId round_id,
t.id transaction_id,
t.betAmount amount,
t.betAmount deducted_amount,
gd.id as game_description_id,
gd.game_type_id,
t.external_uniqueid
FROM {$transTable} as t
left JOIN game_description as gd ON t.gameCode = gd.external_game_id and gd.game_platform_id=?
JOIN game_provider_auth ON t.userId = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
WHERE
t.platformTxId = ? and t.`action` = 'bet' and t.userId=?
EOD;
        
        $params=[$this->getPlatformCode(), $this->getPlatformCode(), $data['transaction_id'], $data['game_username']];
        
        $trans = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        
        if(empty($trans)){
            return array('success'=>false, 'exists'=>false); 
        }
        
        foreach($trans as $insertData){
            $insertData['transaction_status'] = Game_logs::STATUS_PENDING;
            $insertData['game_platform_id'] = $this->getPlatformCode();
            $insertData['added_amount'] = 0;
            $insertData['status'] = Seamless_missing_payout::NOT_FIXED;
            $notes = [];
            $insertData['note'] = json_encode($notes);
            $result = $this->CI->original_game_logs_model->insertIgnoreRowsToOriginal('seamless_missing_payout_report',$insertData);
            if($result===false){
                $this->CI->utils->error_log('AE SEXY SEAMLESS (checkBetStatus) Error insert missing payout', $insertData);
            }
        }

        if($this->enable_mm_channel_nofifications){

            //save data to seamless_missing_payout

            //check if transaction has no payout
            $adminUrl = $this->CI->utils->getConfig('admin_url');
            $message = "@all AE SEXY Seamless to check missing Payout"."\n";   
            $message = "Client: ".$adminUrl."\n";             
            $message .= json_encode($data);       

            $this->CI->load->helper('mattermost_notification_helper');

            $notif_message = array(
                array(
                    'text' => $message,
                    'type' => 'warning'
                )
            );
            sendNotificationToMattermost("AE SEXY SEAMLESS SERVICE ($gamePlatformId)", $this->mm_channel, $notif_message, null);
            $this->CI->utils->debug_log('AE SEXY SEAMLESS (checkBetStatus) sendNotificationToMattermost', $message);
        }

		return array('success'=>true, 'exists'=>$ispayoutexist);
	}

    public function getAmountFromTransactions($trans){
        $betAmount= 0;$winAmount=0;

        return [$betAmount, $winAmount];
    }

	public function setMemberBetSettingByGameUsername($gameUsername, $limit_group = null) {

		$context = array(
			'callback_obj' => $this,
			'callback_method' => 'processForSetMemberBetSettingByGameUsername',
			'gameUsername' => $gameUsername
		);

        if(!empty($limit_group)){
            if(is_array($limit_group)){
                $this->limitId = (array)$limit_group;
            }elseif(is_string($limit_group)){
                $this->limitId = explode(' ', $limit_group);
            }
        }

        $betLimit = json_encode(array(
        	self::GAME_PLATFORM => [
        		self::GAME_TYPE => [
                    'limitId' => $this->limitId
        		]
        	]
        ));

        $params = [
            'cert' => $this->cert,
            'agentId' => $this->agentId,
            'userId' => $gameUsername,            
            'betLimit' => $betLimit
        ];

		return $this->callApi(self::API_setMemberBetSetting, $params, $context);

	}

	public function processForSetMemberBetSettingByGameUsername($params) {

    	$playerName = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);        
        $result = array();
        $result['response_result_id'] = $responseResultId;
        if($success){
            $result['status'] = $resultArr['status'];
            $result['msg'] = $resultArr['desc'];
        }
        return array($success, $result);

	}

	public function updatePlayerInfo($playerName, $infos) {
		$gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
			'callback_obj' => $this,
			'callback_method' => 'processResultForUpdatePlayerInfo',
			'gameUsername' => $gameUsername,
			'playerName' => $playerName
		);

        $limit_group = isset($infos['limitId'])?$infos['limitId']:null;

        if(!empty($limit_group)){
            if(is_array($limit_group)){
                $this->limitId = (array)$limit_group;
            }elseif(is_string($limit_group)){
                $this->limitId = explode(',', $limit_group);
            }
        }

        $betLimit = json_encode(array(
        	self::GAME_PLATFORM => [
        		self::GAME_TYPE => [
                    'limitId' => $this->limitId
        		]
        	]
        ));

        $params = [
            'cert' => $this->cert,
            'agentId' => $this->agentId,
            'userId' => $gameUsername,            
            'betLimit' => $betLimit
        ];

		return $this->callApi(self::API_updatePlayerInfo, $params, $context);
	}

	public function processResultForUpdatePlayerInfo($params){
    	$playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $playerName);        
        $result = array();
        $result['response_result_id'] = $responseResultId;
        if($success){
            $result['status'] = $resultArr['status'];
            $result['msg'] = $resultArr['desc'];
        }
        return array($success, $result);
	}


}