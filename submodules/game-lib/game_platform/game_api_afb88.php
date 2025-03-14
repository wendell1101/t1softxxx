<?php
/**
 * Solid Gaming integration
 * OGP-13812
 * OGP-13813
 *
 * @author  Erickson Qua
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';

class Game_api_afb88 extends Abstract_game_api {
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const TRANS_GAME_BET = 1;
    const TRANS_GAME_WIN = 11;

    const ORIGINAL_LOGS_TABLE_NAME = 'afb88_game_logs';

    const MD5_FIELDS_FOR_ORIGINAL=[
        'ventransid',
        'external_id',
        'ip',
        'last_modified',
        'player_name',
        'bet_amount',
        'win_amount',
        'commission',
        'commission_percent',
        'league',
        'home',
        'away',
        'status',
        'game',
        'odds',
        'side',
        'info',
        'half',
        'transaction_date',
        'work_date',
        'match_date',
        'running_score',
        'score',
        'half_time_score',
        'flg',
        'result',
        'sports_type',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet_amount',
        'win_amount',
        'commission',
        'commission_percent',
        'odds'
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'ventransid',
        'bet_amount',
        'win_amount',
        'game_code',
        'result',
        'last_modified',
        'odds',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'win_amount',
    ];

    const API_updatePlayerBetSettings = 'updatePlayerBetSettings';

    public function __construct() {
        parent::__construct();
        $this->method = self::METHOD_POST;
        $this->apiUrl = $this->getSystemInfo('url','https://api.afb1188.com/Public');
        $this->companyKey = $this->getSystemInfo('companyKey','e06dcae665043dffe06dcae665043dffe06dcae665043dffe06dcae665043dffe06dcae665043dffe06dcae665043dff92fe4bb934e2716bfc7ad3868c8904622a59692f5be9e8a0');
        $this->currency = $this->getSystemInfo('currency','THB');
        $this->language = $this->getSystemInfo('language'); //EN-AU for IDR
        $this->oddsStyle = $this->getSystemInfo('oddStyle','ID');
        $this->oddsMode = $this->getSystemInfo('oddsMode','Double');

        $this->minimum_bet = $this->getSystemInfo('minimum_bet',1);
        $this->maximum_bet = $this->getSystemInfo('maximum_bet',1000000);
        $this->max_per_match = $this->getSystemInfo('max_per_match',1000000);

        $this->other_sport_min = $this->getSystemInfo('other_sport_min', 1);
        $this->other_sport_max = $this->getSystemInfo('other_sport_max', 1000000);
        $this->other_sport_max_limit = $this->getSystemInfo('other_sport_max_limit', 1000000);
        $this->sport_max_count = $this->getSystemInfo('sport_max_count', 10000);
        $this->other_sport_max_count = $this->getSystemInfo('other_sport_max_count', 10000);


        $this->URI_MAP = array(
            self::API_generateToken => '/ckAcc.ashx',
            self::API_queryPlayerBalance => '/InnoExcData.ashx',
            self::API_depositToGame => '/InnoExcData.ashx',
            self::API_withdrawFromGame => '/InnoExcData.ashx',
            self::API_syncGameRecords => '/InnoExcData.ashx',
            self::API_queryForwardGame => '/validate.aspx',
            self::API_queryTransaction => '/InnoExcData.ashx',
            self::API_updatePlayerBetSettings => '/InnoExcData.ashx',
        );

        $this->API_ACTIONS = [
            self::API_logout => 'MB_LOGOUT',
            self::API_blockPlayer => 'MB_UPD_STATUS', // status 0: locked
            self::API_unblockPlayer => 'MB_UPD_STATUS', //status 1: unblocked
            self::API_queryPlayerBalance => 'MB_GET_BALANCE', //status 1: unblocked
            self::API_depositToGame => 'MB_DEPOSIT',
            self::API_withdrawFromGame => 'MB_WITHDRAW',
            self::API_syncGameRecords => 'RP_GET_CUSTOMER',
            self::API_updatePlayerBetSettings => 'MB_BET_SETTING',
        ];

    }

    public function getPlatformCode() {
        return AFB88_API;
    }

    protected function customHttpCall($ch, $params) {
        $params['companyKey'] = $this->companyKey;
        if($this->method == self::METHOD_POST){
            $data_json = json_encode($params);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }
    }
    
    public function generateUrl($apiName, $params) {
        $endPoint = $this->URI_MAP[$apiName];
        $url = $this->apiUrl.$endPoint;
        if($params && $this->method == self::METHOD_GET) {
            $url .= '?' . $params;
        }
        return $url;
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        $success = ($resultArr['error'] == '0') ? true : false;
        $result = array();
        if(!$success){
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('---------- Process Result Boolean False ----------', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
        }
        return $success;
    }

    private function getTokenFromProvider($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetTokenFromProvider',
        );

        $params = [
            // 'companyKey' => $this->companyKey,
            'userName' => $gameUsername,
            'currencyName' => $this->currency,
        ];
        $this->CI->utils->debug_log('---------- getTokenFromProvider params ----------', $params);

        return $this->callApi(self::API_generateToken, $params, $context);

    }

    public function processResultForGetTokenFromProvider($params){
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $result = [];
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);

        if($success){
            $result = [
                'success' => true,
                'token' => $arrayResult['token'],
            ];
        }
        return array($success, $result);
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $result = $this->getTokenFromProvider($playerName);
        return $result;
    }

    public function isPlayerExist($playerName) {
        $result = $this->queryPlayerBalance($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);

        $this->CI->utils->debug_log('---------- result for isPlayerExist ----------', $result, "-----------------------------");
        //$success = $result['exists'] === true;
        return $result;
        // return array($success,$result);
    }


    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName,
        );

        $params = [
            'userName' => $gameUsername,
            'Act' => $this->API_ACTIONS[self::API_queryPlayerBalance],
        ];
        
        $this->CI->utils->debug_log('---------- queryPlayerBalance ----------', $params);

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
        $result = ['exists' => false];
        if($success && $arrayResult['currency'] != '') {
            $result['exists'] = true;
            $result["balance"] = $arrayResult['balance'];
        }

        $this->CI->utils->debug_log('---------- result for queryPlayerBalance ----------', $arrayResult);
        return array($success, $result);
    }


    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName
        );

        $params = [
            'userName' => $gameUsername,
            'Act' => $this->API_ACTIONS[self::API_depositToGame],
            'amount' => $amount,
            'remark' => '',
        ];

        $this->CI->utils->debug_log('---------- params for depositToGame ----------', $params);
        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);

        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($success) {
            $result['external_transaction_id'] = $arrayResult['paymentId'];
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }

        if(in_array((int)$statusCode, $this->other_status_code_treat_as_success) && $this->treat_500_as_success_on_deposit){

            $this->CI->utils->debug_log(__METHOD__. ' statusCode', $statusCode, 'success', $success);
            $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
            $success=true;
        }

        $this->CI->utils->debug_log('---------- result for depositToGame ----------', $arrayResult);
        return array($success, $result);
    }


    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName
        );

        $params = [
            'userName' => $gameUsername,
            'Act' => $this->API_ACTIONS[self::API_withdrawFromGame],
            'amount' => $amount,
            'remark' => '',
        ];

        $this->CI->utils->debug_log('---------- params for withdrawFromGame ----------', $params);
        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);

        $result = array(
            'response_result_id' => $responseResultId,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($success) {
            $result['external_transaction_id'] = $arrayResult['paymentId'];
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }

        $this->CI->utils->debug_log('---------- result for withdrawFromGame ----------', $arrayResult);
        return array($success, $result);
    }

    public function queryForwardGame($playerName, $extra = null) {
        $resultToken = $this->getTokenFromProvider($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $lang = ($this->language) ? $this->language : $this->getLauncherLanguage($extra['language']);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
        );
        $this->method = self::METHOD_GET;
        $params = http_build_query([
            'us' => $gameUsername,
            'k' => $resultToken['token'],
            'device' => $extra['is_mobile'] ? 'm' : 'd',
            'oddstyle' => $this->oddsStyle,
            'oddsmode' => $this->oddsMode,
            'lang' => $lang,
            // 'ag' => '', # no agent
            'currencyName' => $this->currency
        ]);

        $this->CI->utils->debug_log('---------- params for queryForwardGame ----------', $params);

        $result = [
            'success' => false,
        ];

        if($resultToken['success']) {
            $result['success'] = true;
            $result['url'] = $this->generateUrl(self::API_queryForwardGame, $params);
        }
        return $result;
    }


    public function getLauncherLanguage($currentLang) {
        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
                $language = 'zh-cn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id-id":
                $language = 'en-au';
                break;
            case "en-us":
            $language = 'en-us';
            break;
            case "th_TH":
                $language = 'th-th';
                break;
            default:
                $language = 'en-us';
                break;
        }
        return $language;
    }

    public function syncOriginalGameLogs($token = false) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate->modify($this->getDatetimeAdjust());
        
        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate   = $endDate->format('Y-m-d H:i:s');
        $result = [];

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
        );

        $params = [
            'Act' => $this->API_ACTIONS[self::API_syncGameRecords],
            'portfolio' => 'sportsbook',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'lang' => $this->language
        ];

        $this->CI->utils->debug_log('---------- params for syncOriginalGameLogs ----------', $params);
        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, null);
        $this->CI->utils->debug_log('---------- syncOriginalGameLogs result ----------', $arrayResult);
        $gameRecords = !empty($resultArr['transactions']) ? $resultArr['transactions']:[];

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );

        if($success) {
            $gameRecords = $arrayResult['playerBetList'];


            $this->processGameRecords($gameRecords, $responseResultId);

            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                self::ORIGINAL_LOGS_TABLE_NAME,
                $gameRecords,
                'external_uniqueid',
                'external_uniqueid',
                self::MD5_FIELDS_FOR_ORIGINAL,
                'md5_sum',
                'id',
                self::MD5_FLOAT_AMOUNT_FIELDS
            );

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
        return array($success, $dataResult);
    }

    public function processGameRecords(&$gameRecords, $responseResultId) {
        if(!empty($gameRecords)){
            foreach($gameRecords as $index => $record) {

                $data['ventransid'] = array_key_exists('ventransid', $record) ? $record['ventransid'] : null;
                $data['external_id'] = array_key_exists('id', $record) ? $record['id'] : null;
                $data['ip'] = array_key_exists('ip', $record) ? $record['ip'] : null;
                $data['last_modified'] = array_key_exists('t', $record) ? $record['t'] : null;
                $data['player_name'] = array_key_exists('u', $record) ? $record['u'] : null;
                $data['bet_amount'] = array_key_exists('b', $record) ? $record['b'] : null;
                $data['win_amount'] = array_key_exists('w', $record) ? $record['w'] : null;
                $data['commission'] = array_key_exists('a', $record) ? $record['a'] : null;
                $data['commission_percent'] = array_key_exists('c', $record) ? $record['c'] : null;
                $data['league'] = array_key_exists('league', $record) ? $record['league'] : null;
                $data['home'] = array_key_exists('home', $record) ? $record['home'] : null;
                $data['away'] = array_key_exists('away', $record) ? $record['away'] : null;
                $data['status'] = array_key_exists('status', $record) ? $record['status'] : null;
                $data['game'] = array_key_exists('game', $record) ? $record['game'] : null;
                $data['odds'] = array_key_exists('odds', $record) ? $record['odds'] : null;
                $data['side'] = array_key_exists('side', $record) ? $record['side'] : null;
                $data['info'] = array_key_exists('info', $record) ? $record['info'] : null;
                $data['half'] = array_key_exists('half', $record) ? $record['half'] : null;
                $data['transaction_date'] = array_key_exists('trandate', $record) ? $record['trandate'] : null;
                $data['work_date'] = array_key_exists('workdate', $record) ? $record['workdate'] : null;
                $data['match_date'] = array_key_exists('matchdate', $record) ? $record['matchdate'] : null;
                $data['running_score'] = array_key_exists('runscore', $record) ? $record['runscore'] : null;
                $data['score'] = array_key_exists('score', $record) ? $record['score'] : null;
                $data['half_time_score'] = array_key_exists('htscore', $record) ? $record['htscore'] : null;
                $data['flg'] = array_key_exists('flg', $record) ? $record['flg'] : null;
                $data['result'] = array_key_exists('res', $record) ? $record['res'] : null;
                $data['sports_type'] = array_key_exists('sportstype', $record) ? $record['sportstype'] : null;

                $data['game_externalid'] = $data['sports_type'];
                $data['external_uniqueid'] = $data['external_id'];
                $data['response_result_id'] = $responseResultId;

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
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function syncMergeToGameLogs($token) {
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
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $table = self::ORIGINAL_LOGS_TABLE_NAME;
        $sqlTime='afb88.last_modified >= ? and afb88.last_modified <= ?';
        $sql = <<<EOD
SELECT
    afb88.id as sync_index,
    afb88.ventransid,
    afb88.bet_amount,
    afb88.bet_amount real_betting_amount,
    afb88.win_amount,
    afb88.sports_type as game_code,
    afb88.result,
    afb88.odds,
    afb88.last_modified,
    afb88.external_uniqueid,
    afb88.updated_at,
    afb88.md5_sum,
    afb88.response_result_id,
    afb88.sports_type as game,
    afb88.transaction_date as bet_at,
    afb88.transaction_date as start_at,
    afb88.transaction_date as end_at,

    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.game_type_id

FROM
    {$table} as afb88
    LEFT JOIN game_description ON afb88.game_externalid = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON afb88.player_name = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
WHERE

{$sqlTime}

EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
    }

    /**
     * it will be used on processUnsettleGameLogs and commonUpdateOrInsertGameLogs
     *
     * @param  array $row
     * @return array $params
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {
        if(!array_key_exists('md5_sum', $row)){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow(
                $row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE
            );
        }

        return [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $row['bet_amount'],
                'result_amount'         => $row['win_amount'],
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['real_betting_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => null
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['bet_at'],
                'bet_at'                => $row['end_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['external_uniqueid'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => ['Created At' => $this->CI->utils->getNowForMysql()],
            'extra' => [
                'odds' => $row['odds']
            ],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

    }

     /**
     *
     * perpare original rows, include process unknown game, pack bet details, convert game status
     *
     * @param  array &$row
     */
    public function preprocessOriginalRowForGameLogs(array &$row){
        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        #$row['real_betting_amount'] = $row['bet_amount'];

        if($row['result'] == 'P') {
            $row['status'] = Game_logs::STATUS_PENDING;
        }
        else {
            if($row['result'] == 'D'){
                $row['bet_amount'] = 0;
            }
            $row['status'] = Game_logs::STATUS_SETTLED;
        }
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

    public function blockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->blockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function unblockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $success = $this->unblockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function changePassword($playerName, $oldPassword = null, $newPassword) {
        return $this->returnUnimplemented();
    }

    /**
     * overview : get game time to server time
     *
     * @return string
     */
    /*public function getGameTimeToServerTime() {
        //return '+8 hours';
    }*/

    /**
     * overview : get server time to game time
     *
     * @return string
     */
    /*public function getServerTimeToGameTime() {
        //return '-8 hours';
    }*/

    public function queryTransaction($transactionId, $extra) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($extra['playerName']);
        $dateFrom = date('Y-m-d H:i:s', strtotime($extra['transfer_time'] . ' -1 minute'));
        $dateTo = date('Y-m-d H:i:s', strtotime($extra['transfer_time'] . ' +1 minute'));
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id' => $transactionId,
            'extra' => $extra
        );

        $params = [
            'userName' => $gameUsername,
            'ACT' => 'MB_TRANSACTION_RECORD',
            'FromDate' => $dateFrom,
            'ToDate' => $dateTo,
        ];

        $this->CI->utils->debug_log('---------- params for queryTransaction ----------', $params);
        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

	/**
	 * overview : process result for queryTransaction
	 * @param $apiName
	 * @param $params
	 * @param $responseResultId
	 * @param $resultXml
	 * @return array
	 */
	public function processResultForQueryTransaction($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $extra = $this->getVariableFromContext($params, 'extra');
        $success = $this->processResultBoolean($responseResultId, $arrayResult);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        $hasTransaction = false;
        if($success) {
            foreach($arrayResult['TRANSFERLIST'] as $transferList) {
                if($transferList['PaymentId'] != $external_transaction_id) {
                    continue;
                }

                if($transferList['PaymentId'] == $external_transaction_id && abs($transferList['Amt']) == $extra['amount']) {
                    $hasTransaction = true;
                    $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
                    break;
                }
            }
        }
        
        if(!$hasTransaction) {
            // no callback error here if api failed
            // display default (network error, reason id) from processGuessSuccess
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        return array($success, $result);
	}

    public function syncPlayerAccount($username, $password, $playerId) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    public function logout($playerName, $password = null) {
        return $this->returnUnimplemented();
    }

    public function updatePlayerInfo($playerName, $infos) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
        return $this->returnUnimplemented();
    }

    public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
        return $this->returnUnimplemented();
    }

    public function checkLoginStatus($playerName) {
        return $this->returnUnimplemented();
    }

    public function checkLoginToken($playerName, $token) {
        return $this->returnUnimplemented();
    }

    public function totalBettingAmount($playerName, $dateTimeFrom, $dateTimeTo) {
        return $this->returnUnimplemented();
    }

    public function queryBetDetailLink($gameUsername, $round_id = null, $extra = null)
    {        
        $context = [
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryBetDetailLink'
        ];
        
        $params = [
            'action' => self::GET_GAME_HISTORY_URL,
            'site_id' => $this->site_id,
            'account_name' => $gameUsername,
            'round_id' => $round_id,
            // 'lang' => 'enUS' <optional> The display language in the game history. 
            // Supported options are ‘enUS’, ‘zhTW’, ‘zhCN’, default value: ‘enUS’
        ];

        $this->CI->utils->debug_log('AE_SLOTS unblockPlayer params ======>',$params);

        $this->generated_token = $this->generateJwtToken($params,$this->secret_key); # generated JWT token here

        if($this->generated_token){
            
            $this->method = self::METHOD_POST;
  
            return $this->callApi(self::API_queryBetDetailLink, $params, $context);
        } 
    }

    /** 
     * Process Result of queryBetDetailLink method
    */
    public function processResultForQueryBetDetailLink($params)
    {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult); 
        
        return array($success, $arrayResult);
    }

    public function updatePlayerBetSettings($gameUsername) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForUpdatePlayerBetSettings',
        );

        $params = [
            'Act' => $this->API_ACTIONS[self::API_updatePlayerBetSettings],
            'userName' => $gameUsername,
            'min' => $this->minimum_bet,
            'max' => $this->maximum_bet,
            'maxPerMatch' => $this->max_per_match,
            'parmin' => $this->minimum_bet,
            'parmax' => $this->maximum_bet,
            'othersportmin' => $this->other_sport_min,
            'othersportmax' => $this->other_sport_max,
            'othersportmaxLimit' => $this->other_sport_max_limit,
            'sportmaxcount' => $this->sport_max_count,
            'othersportmaxcount' => $this->other_sport_max_count,
        ];
        $this->CI->utils->debug_log('---------- updatePlayerBetSettings params ----------', $params);
        return $this->callApi(self::API_updatePlayerBetSettings, $params, $context);

    }

    public function processResultForUpdatePlayerBetSettings($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult);
        return array($success, $arrayResult);
    }

}
