<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
* Game Provider: Slot Factory
* Game Type: Slots
* Wallet Type: Seamless/Transfer
*
* @category Game_platform
* @version not specified
* @copyright 2013-2022 tot
* @integrator @mccoy.php.ph

    Related File
    -routes.php
    -slot_factory_service_api.php
    -slot_factory_transaction.php
**/

abstract class Abstract_game_api_common_slot_factory extends Abstract_game_api {

    const POST = 'POST';
    // const API_syncGameRecords = '/playreport';
    // const API_createPlayer = '/mtwallet';
    const GameReport = 'GameReport';
    const LicenseeReport = 'LicenseeReport';
    const CODE_SUCCESS = 0;

    const URI_MAPS = [
        self::API_createPlayer => '/mtwallet',
        self::API_depositToGame => '/mtwallet',
        self::API_withdrawFromGame => '/mtwallet',
        self::API_queryPlayerBalance => '/mtwallet',
        self::API_syncGameRecords => '/playreport',
        self::API_queryForwardGame => '/getgameurl'
    ];

    private $original_gamelogs_table=null;

    // Fields in slot_factory_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        //unique id
        'transaction_id',
        //round id
        'round_id',
        //money
        'totalBet',
        'cashWon',
        //player
        'account_id',
        //game
        'game_name',
        //date time
        'spin_date',
        'bonus_date',
        //bet details
        'lines',
        'lineBet',
        'gambleGames',
        'freeGames',
        'freeGamesPlayed',
        'freeGamesRemaining',
        'currency'

    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'cashWon',
        'totalBet',
    ];

    // Fields in game_logs we want to detect changes for merge, and only available when original md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'external_uniqueid',
        //money
        'bet_amount',
        'real_betting_amount',
        'result_amount',
        //game
        'round_number',
        'game_code',
        'game_name',
        //player
        'player_username',
        //date time
        'start_at',
        'end_at',
        'bet_at',
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'bet_amount',
        'real_betting_amount',
        'result_amount',
    ];

    public function __construct() {
        parent::__construct();
        $this->CI->load->model(array('original_game_logs_model','player_model'));
        $this->api_url = $this->getSystemInfo('url', 'stag-gs.slotfactory.com');
        $this->game_url = $this->getSystemInfo('game_url', 'stag-wa.slotfactory.com');
        $this->secret_key = $this->getSystemInfo('secret', 'drs8gL7iyBSmAKda');
        $this->licensee_name = $this->getSystemInfo('licensee_name', 'TPO1');
        $this->currencyID = $this->getSystemInfo('currency', 'CNY');
        $this->countryID = $this->getSystemInfo('country', 'CHN');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->slot_factory_ip = $this->getSystemInfo('slot_factory_ip', '3.114.25.69');
        $this->original_gamelogs_table = $this->getOriginalTable();
        $this->gamePlatformId = $this->getPlatformCode();
        $this->sync_gamelogs_by_games = $this->getSystemInfo('sync_gamelogs_by_games', false);
        $this->target = $this->getSystemInfo('target', 'CHN');
        $this->method = self::POST;
    }
    
    
    public function isSeamLessGame()
    {
       return false;
    }

    public function getPlatformCode() {
        return $this->returnUnimplemented();
    }

    public function getCurrency() {
        return $this->currencyID;
    }

    public function getCountryCode() {
        return $this->countryID;
    }

    public function generateHMAC($params) {

        $hmac = hash_hmac('SHA256',$params,$this->secret_key, true);

        $base64 = base64_encode($hmac);

        $this->utils->debug_log('<---------------Slot Factory------------> HMAC: ', $base64);

       return $base64;
    }

    public function generateUrl($apiName, $params) {
        return $this->api_url.self::URI_MAPS[$apiName];
    }

    public function getHttpHeaders($params){
        $hmac = $this->generateHMAC($params);
        $content_length = strlen($params);
        $headers = array(
            'HMAC' => $hmac,
            'Content-Type' => 'application/json',
            'Content-Length' => $content_length
        );

        return $headers;

    }

    protected function customHttpCall($ch, $params) {

        if($this->method == self::POST) {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }
    

    public function processResultBoolean($responseResultId, $resultArr, $username=null){
        $success = false;
        if(!empty($resultArr) && $resultArr['StatusCode'] == self::CODE_SUCCESS){
            $success=true;
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('Slot Factory got error: ', $responseResultId,'result', $resultArr);
        }
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        
        //create player in db
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerId' => $playerId,
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        ];

        $params = json_encode(array(
            'AccountID' => $gameUsername,
            'LicenseeName' => $this->licensee_name,
            'Action' => 'CreatePlayer',
            'CurrencyID' => $this->getCurrency(),
            'FirstName' => 'FIRST',
            'LastName' => 'LAST',
            'DateOfBirth' => '1985-01-01',
            'Gender' => 'M',
            'Nickname' => 'Nickname',
            'Email' => 'Email',
            'Address' => 'Address',
            'CountryID' => $this->getCountryCode()
        ));

        $this->CI->utils->debug_log('<--------------PARAMS-------------->',$params);

        return $this->callApi(self::API_createPlayer, $params, $context);

    }

    public function processResultForCreatePlayer($params) {

        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId];

        if($success){
            // update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }
        return array($success, $result);

    }

    public function depositToGame($userName, $amount, $transfer_secure_id = null) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($userName);
        $playerId = $this->getPlayerIdByGameUsername($gameUsername);
        $external_transaction_id = empty($transfer_secure_id) ? 'T' . $this->CI->utils->randomString(12) : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'external_transaction_id' => $external_transaction_id,
            'playerId' => $playerId
        );

        $params = json_encode(array(
            'AccountID' => $gameUsername,
            'LicenseeName' => $this->licensee_name,
            'Action' => 'Deposit',
            'DepositAmount' => (string)$this->dBtoGameAmount($amount)
        ));

        $this->CI->utils->debug_log('<--------------PARAMS-------------->',$params);

        return $this->callApi(self::API_depositToGame, $params, $context);

    }

    public function processResultForDepositToGame($params) {

        $this->CI->load->model('external_common_tokens');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId];

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        if ($success) {
            $token=isset($resultArr['AuthToken']) ? $resultArr['AuthToken'] : null;
            $this->CI->external_common_tokens->addPlayerToken($playerId, $token, $this->getPlatformCode());
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = self::REASON_UNKNOWN;
        }

        return [$success, $result];

    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id = null) {
    
        $gameUsername = $this->getGameUsernameByPlayerUsername($userName);
        $external_transaction_id = empty($transfer_secure_id) ? 'W' . $this->CI->utils->randomString(12) : $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'external_transaction_id' => $external_transaction_id
        );

        $params = json_encode(array(
            'AccountID' => $gameUsername,
            'LicenseeName' => $this->licensee_name,
            'Action' => 'Withdraw',
            'WithdrawAmount' => (string)$this->dBtoGameAmount($amount)
        ));

        $this->CI->utils->debug_log('<--------------PARAMS-------------->',$params);

        return $this->callApi(self::API_withdrawFromGame, $params, $context);

    }

    public function processResultForWithdrawFromGame($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId];

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs'] = true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id'] = self::REASON_UNKNOWN;
        }

        return [$success, $result];

    }


    public function queryPlayerBalance($playerName) {
        
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
        );

        $params = json_encode(array(
            'AccountID' => $gameUsername,
            'LicenseeName' => $this->licensee_name,
            'Action' => 'GetBalance'
        ));

        $this->CI->utils->debug_log('<--------------PARAMS-------------->',$params);

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);

    }

    public function processResultForQueryPlayerBalance($params) {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId];

        if($success){
            if(isset($resultArr['Balance'])){
                $result['balance'] = $this->convertAmountToDB($resultArr['Balance']);
            }else{
                //wrong result, call failed
                $success=false;
            }
        }

        return [$success, $result];
    }

    public function changePassword($playerName, $oldPassword = null, $newPassword) {
        return $this->returnUnimplemented();
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    private function getLauncherLanguage($currentLang) {
     switch ($currentLang) {
           case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
           case "zh":
               $language = 'zh-hans';
               break;
           case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
           case "id":
               $language = 'id';
               break;
           case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
           case "vi":
               $language = 'vi';
               break;
           case "en":
               $language = 'en';
               break;
           case LANGUAGE_FUNCTION::INT_LANG_THAI:
               $language = 'th';
               break;
           case "th":
               $language = 'th';
               break;
           default:
               $language = 'en';
               break;
       }
       return $language;

   }

   public function login($playerName, $password=null) {
        
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForLogin',
                'playerName' => $playerName,
                'gameUsername' => $gameUsername
            );

        $params = json_encode(array(
            'LicenseeName' => $this->licensee_name,
            'Target' => $this->target,
        ));

        return $this->callApi(self::API_queryForwardGame, $params, $context);

    }

    public function processResultForLogin($params) {
        $statusCode = $this->getStatusCodeFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr, $statusCode);
        $result = ['response_result_id' => $responseResultId];

        if($success){
            $result['url'] = $resultArr['GameURL'];
        }

        return array($success, $result);
    }

    public function queryForwardGame($playerName, $extra) {

        $this->depositToGame($playerName,0);
        
        $this->CI->load->model('external_common_tokens');
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdFromUsername($playerName);
        $token = $this->CI->external_common_tokens->getExternalToken($playerId, $this->getPlatformCode());
        $gameCode = $extra['game_code'];
        $lang = $this->getLauncherLanguage($extra['language']);

        $game_url = $this->login($playerName);
        $game_url = isset($game_url['url']) ? $game_url['url'] : $this->game_url;

        $params = array(
            'gn' => $gameCode,
            'ln' => $this->licensee_name,
            'ad' => $gameUsername,
            'at' => $token,
            'lb' => $this->lobby_url,
            'lc' => $lang,
        );


        if(isset($extra['game_mode']) && $extra['game_mode'] == 'trial') {
            $params['fr'] = '1';
        }

        if($this->force_game_url) {
            $game_url = $this->game_url;
        }

        $add_params = http_build_query($params);
        $game_url .= '&'.$add_params;
        $url = $game_url;

        return array('success' => true, 'url' => $url);

    }



    /*
        **
        **  As checked with Game Provider, GameName param is required. We must query the Game Logs
        **  for each and every game that we are passing. If failed to pass a GameName, querying of game logs
        **  will fail.
        **
        **  update as of Jan. 10, 2020 Game Provider has updated a Game Logs syncing
        **  which where we can sync the logs without passing a Game Name parameter
        **  We'll be syncing the logs through 'LicenseeName'
        **
    */
    public function syncOriginalGameLogs($token) {

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDateTime = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $startDateTime->modify($this->getDatetimeAdjust());
        $endDateTime = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startTime = $startDateTime->format('Y-m-d H:i:s');
        $endTime = $endDateTime->format('Y-m-d H:i:s');

        $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncOriginalGameLogs'
            );

        if($this->sync_gamelogs_by_games) {

            $gameNames = $this->getGameNames();
            foreach ($gameNames as $gameName) {
                $i = 0;
                $len = count($gameNames);
                $done = false;
                $success = false;
                while(!$done && $i < $len - 1) {
                    $params = json_encode(array(
                        'ReportType' => self::GameReport,
                        'GameName' => $gameName,
                        'LicenseeName' => $this->licensee_name,
                        'From' => $startTime,
                        'To' => $endTime,
                        'Timestamp' => $this->timeStamp(),
                    ));

                    $this->method = self::POST;

                    $result = $this->callApi(self::API_syncGameRecords, $params, $context);

                    $this->CI->utils->debug_log('<-----------------PARAMS----------------->', $params, 'Result: ', $result, 'Success: ', $success);

                    if($result['success']){
                        if($i < $len - 1) {
                            $i++;
                            $done = true;
                        }
                    } else {
                        $done = false;
                    }

                    if($done) {
                        $success = true;
                    }
                }
            }
        } else {
            $params = json_encode(array(
                'ReportType' => self::LicenseeReport,
                'LicenseeName' => $this->licensee_name,
                'From' => $startTime,
                'To' => $endTime,
                'Timestamp' => $this->timeStamp(),
            ));

            $result = $this->callApi(self::API_syncGameRecords, $params, $context);

            $success = true;
        }

        return array('success' => $success, 'result' => $result);

    }

    public function processResultForSyncOriginalGameLogs($params) {

        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);

        $result = ['data_count' => 0];
        //$gameRecords = isset($resultArr['SpinReport']) ? $resultArr['SpinReport'] : null;
        $spinReport = isset($resultArr['SpinReport']) ? $resultArr['SpinReport'] : [];
        $bonusReport = isset($resultArr['BonusReport']) ? $resultArr['BonusReport'] : [];
        $gameRecords = array_merge($spinReport, $bonusReport);
        // print_r(json_encode($resultArr['SpinReport']));exit();

        if($success && !empty($gameRecords)) {
            $extra = ['response_result_id' => $responseResultId];
            $this->processGameRecords($gameRecords, $extra);

            list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->original_gamelogs_table,
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

            if (!empty($insertRows)){
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($insertRows);

            if (!empty($updateRows)){
                $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                    ['responseResultId'=>$responseResultId]);
            }
            unset($updateRows);

        }

        return array($success, $result);

    }

    private function processGameRecords(&$gameRecords, $extra) {
        // print_r($gameRecords);exit();
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {
                $data['account_id'] = isset($record['AccountID']) ? $record['AccountID'] : null;
                $data['round_id'] = isset($record['RoundID']) ? $record['RoundID'] : null;
                $data['transaction_id'] = isset($record['TransactionID']) ? $record['TransactionID'] : null;
                $data['game_name'] = isset($record['GameName']) ? $record['GameName'] : null;
                $data['spin_date'] = isset($record['SpinDate']) ? $this->gameTimeToServerTime($record['SpinDate']) : null;
                $data['bonus_date'] = isset($record['BonusDate']) ? $this->gameTimeToServerTime($record['BonusDate']) : null;
                $data['currency'] = isset($record['Currency']) ? $record['Currency'] : null;
                $data['lines'] = isset($record['Lines']) ? $record['Lines'] : null;
                $data['lineBet'] = isset($record['LineBet']) ? $this->convertAmount($record['LineBet']) : null;
                $data['totalBet'] = isset($record['TotalBet']) ? $this->convertAmount($record['TotalBet']) : null;
                $data['cashWon'] = isset($record['CashWon']) ? $this->convertAmount($record['CashWon']) : null;
                $data['gambleGames'] = isset($record['GambleGames']) ? $record['GambleGames'] : null;
                $data['freeGames'] = isset($record['FreeGames']) ? $record['FreeGames'] : null;
                $data['freeGamesPlayed'] = isset($record['FreeGamePlayed']) ? $record['FreeGamePlayed'] : null;
                $data['freeGamesRemaining'] = isset($record['FreeGameRemaining']) ? $record['FreeGameRemaining'] : null;               
                // //default data
                $data['response_result_id'] = $extra['response_result_id'];
                $data['external_uniqueid'] = $record['TransactionID'];
                $gameRecords[$index] = $data;
                unset($data);

            }
        }

    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount = 0;
        if(!empty($rows)) {
            foreach ($rows as $key => $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle=false;
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle);
    }

    /* queryOriginalGameLogs
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        //only one time field
        $sqlTime='(sf.spin_date >= ? AND sf.spin_date <= ?)';
        $sqlTime.=' OR (sf.bonus_date >= ? AND sf.bonus_date <= ?)';
        // if($use_bet_time){
        //     $sqlTime='sf.spin_date >= ? AND sf.spin_date <= ?';
        // }

        $sql = <<<EOD
SELECT
sf.id as sync_index,
sf.response_result_id,
sf.external_uniqueid,
sf.md5_sum,

sf.account_id as player_username,
sf.round_id as round_number,
sf.transaction_id,
sf.game_name as game_code,
sf.game_name as game_name,
sf.spin_date as bet_at,
sf.spin_date as start_at,
sf.spin_date as end_at,
sf.bonus_date as bonus_date,
sf.currency,
sf.lines,
sf.lineBet,
sf.totalBet as bet_amount,
sf.totalBet as real_betting_amount,
sf.cashWon as result_amount,
sf.gambleGames,
sf.freeGames,
sf.freeGamesPlayed,
sf.freeGamesRemaining,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM $this->original_gamelogs_table as sf
LEFT JOIN game_description as gd ON sf.game_name = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON sf.account_id = game_provider_auth.login_name
AND game_provider_auth.game_provider_id=?
WHERE
{$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
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

        $bet_amount = $row['bet_amount']?$row['bet_amount']:0;
        $result_amount = $row['result_amount']-$bet_amount;        
        $bet_date = $row['end_at'];
        if(isset($row['bonus_date']) && !empty($row['bonus_date'])){
            $bet_date = $row['bonus_date'];
        }

        return [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_name'],
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username'],
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount,
                'bet_for_cashback' => $bet_amount,
                'real_betting_amount' => $bet_amount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null,
            ],
            'date_info' => [
                'start_at' => $bet_date,
                'end_at' => $bet_date,
                'bet_at' => $bet_date,
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => [],
            'extra' => null,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

    public function preprocessOriginalRowForGameLogs(array &$row) {

        if (empty($row['game_description_id']))
        {
            $unknownGame = $this->getUnknownGame($this->getPlatformCode());
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

    }

    public function getGameDescriptionInfo($row, $unknownGame) {

        $game_description_id = null;
        $game_name = $row['game_name'];
        $external_game_id = $row['game_code'];

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id);
    }



    public function getPlayerIp() {
        $ip=$this->CI->utils->getIP();

        return $ip;
    }

    private function timeStamp() {
        $timeStamp = new DateTime();
        $newTimeStamp = $timeStamp->getTimestamp();

        return $newTimeStamp;
    }

    private function convertBalance($balance) {
        $bal = $balance * 100;

        return $bal;
    }

    private function convertAmount($amount) {
        $amt = $amount / 100;

        return $amt;
    }

    private function getGameNames() {

        $this->CI->load->model('game_description_model');
        $Games = $this->CI->game_description_model->getGamelistPerGameProviders($this->gamePlatformId);
        foreach ($Games as $game => $value) {
            $gameNames[] = $value['external_game_id'];
        }
        
        return $gameNames;

    }


}