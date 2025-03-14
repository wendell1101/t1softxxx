<?php
/**
 * Ray Gaming Integration
 * OGP-14597
 *
 * @author  Erickson Qua
 */
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__) . '/../../core-lib/application/libraries/third_party/jwt.php';

class Game_api_rg extends Abstract_game_api {

    const METHOD_POST = 'POST';

    const TRANSACTION_WITHDRAW = 4;
    const TRANSACTION_DEPOSIT = 0;
    const ODDS_RATE_BASE_CALCULATION = 1.5;
    const ORDER_TYPE_PARLAY = 1;
    const ORDER_TYPE_NORMAL = 0;
    const SINGLE_BET = 1;
    const DOUBLE_BET = 2;
    const TREBLE_BET = 3;

    const MD5_FIELDS_FOR_ORIGINAL=[
        'live',
        'order_type',
        'odds_count',
        'username',
        'total_stake',
        'total_bonus',
        'total_bet_bonus',
        'win',
        'status',
        'create_time',
        'settle_time',
        'order_id',
        'currency',
        'detail_comment',
        'detail_win',
        'detail_order_id',
        'detail_game_id',
        'detail_live',
        'detail_odds',
        'detail_title',
        'detail_match_name',
    ];


    const MD5_FLOAT_AMOUNT_FIELDS = [
        'total_stake',
        'total_bonus',
        'total_bet_bonus'
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'total_stake',
        'total_bonus',
        'total_bet_bonus',
        'win',
        'status',
        'start_at',
        'end_at',
        'round_number',
        'game_code',
        'external_uniqueid',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'total_stake',
        'total_bonus',
        'total_bet_bonus',
    ];

    public function __construct() {
        parent::__construct();
        $this->originalTable = "rg_game_logs";
        $this->apiUrl = $this->getSystemInfo('url','https://openapi.raygaming.co/gateway');
        $this->apiKey = $this->getSystemInfo('key','8c0aff0233df109b');
        $this->apiSecret = $this->getSystemInfo('secret','2c55bccc91513262983ea46dda9f4e54');
        $this->launchURL = $this->getSystemInfo('launchURL','https://pc.raygaming.co');
        $this->mobileLaunchURL = $this->getSystemInfo('mobileLaunchURL','https://h5.raygaming.co');
        $this->currency = $this->getSystemInfo('currency','CNY');
        $this->lobbyURL = $this->getSystemInfo('lobby_url','http://player.og.local');
        $this->customCSS = $this->getSystemInfo('custom_css', null);
        $this->method = self::METHOD_POST;
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');

        # fix exceed game username length
        $this->fix_username_limit = $this->getSystemInfo('fix_username_limit', true);
        $this->minimum_user_length = $this->getSystemInfo('minimum_user_length', 4);
        $this->maximum_user_length = $this->getSystemInfo('maximum_user_length', 16);
        $this->default_fix_name_length = $this->getSystemInfo('default_fix_name_length', 8);
        $this->use_rg_valid_bet_calculation = $this->getSystemInfo('use_rg_valid_bet_calculation', true);
        $this->METHOD_MAP = array(
            self::API_createPlayer => 'account.register',
            self::API_isPlayerExist => 'account.balance',
            self::API_queryPlayerBalance => 'account.balance',
            self::API_depositToGame => 'account.deposit',
            self::API_withdrawFromGame => 'account.withdraw',
            self::API_syncGameRecords => 'order.list',
            self::API_queryTransaction => 'account.trade.query',
        );

        $this->API_ACTIONS = [
            self::API_logout => 'MB_LOGOUT',
            self::API_blockPlayer => 'MB_UPD_STATUS', // status 0: locked
            self::API_unblockPlayer => 'MB_UPD_STATUS' //status 1: unblocked
        ];
    }

    public function getPlatformCode(){
        return RG_API;
    }

    protected function customHttpCall($ch, $params) {
        if($this->method == self::METHOD_POST){
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
    }

    protected function generateJwtToken($apiName, $data)
    {
        $jwt = new JWT;
        $payload = [
            'app_key' => $this->apiKey,
            'data' => $data,
        ];

        if($apiName != null) {
            $payload['method'] = $this->METHOD_MAP[$apiName];
        }

        $token = $jwt->encode($payload, $this->apiSecret);

        return $token;
    }

    
    public function callApi($apiName, $token, $context = null) {

        $params = [
            'payload' => $token
        ];

        return parent::callApi($apiName, $params, $context);
    }

    public function generateUrl($apiName, $params) {
        return $this->apiUrl;
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null) {
        $success = ($resultArr['code'] == 'ok') ? true : false;
        $result = array();
        if(!$success){
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('---------- RG Process Result Boolean False ----------', $responseResultId, 'playerName', $playerName, 'result', $resultArr);
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
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId,
        );

        $data = [
            'account_name' => $gameUsername,
            'nickname' => $gameUsername,
            'currency' => $this->currency
        ];

        $token = $this->generateJwtToken(self::API_createPlayer, $data);
        
        $this->CI->utils->debug_log('---------- RG createPlayer ----------', $token);

        return $this->callApi(self::API_createPlayer, $token, $context);
    }

    public function processResultForCreatePlayer($params){
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $result = [];
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);

        $result = ['player' => $playerName, 'exists' => false];
        if($success){
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result['exists'] = true;
        }

        if($arrayResult['code'] == 'exist') {
            $result['exists'] = true;
        }
        $this->CI->utils->debug_log('---------- RG result for createPlayer ----------', $arrayResult);
        return array($success, $result);
    }

    public function isPlayerExist($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName,
        );

        $data = [
            'account_name' => $gameUsername,
        ];

        $token = $this->generateJwtToken(self::API_isPlayerExist, $data);
        
        $this->CI->utils->debug_log('---------- RG isPlayerExist ----------', $token);

        return $this->callApi(self::API_isPlayerExist, $token, $context);
    }

    public function processResultForIsPlayerExist($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
        $result = ['exists' => false];

        if($success) {
            $result['exists'] = true;
        }
        $this->CI->utils->debug_log('---------- RG result for isPlayerExist ----------', $arrayResult);
        return array($success, $result);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        
        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName,
        );

        $data = [
            'account_name' => $gameUsername,
        ];

        $token = $this->generateJwtToken(self::API_queryPlayerBalance, $data);
        
        $this->CI->utils->debug_log('---------- RG queryPlayerBalance ----------', $token);

        return $this->callApi(self::API_queryPlayerBalance, $token, $context);

    }

    public function processResultForQueryPlayerBalance($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);
        $result = [];

        if($success) {
            $result["balance"] = $arrayResult['balance'];
        }
        $this->CI->utils->debug_log('---------- RG result for queryPlayerBalance ----------', $arrayResult);
        return array($success, $result);
    }


    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);
        $orderId = $transfer_secure_id;
        if($orderId == null) {
            $orderId = $this->getSecureId('transfer_request', 'secure_id', true, 'T');
        }
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'external_transaction_id'=> $orderId
        );

        $data = [
            'account_name' => $gameUsername,
            'amount' => $amount,
            'out_trade_no' => $orderId
        ];

        $token = $this->generateJwtToken(self::API_depositToGame, $data);

        $this->CI->utils->debug_log('---------- RG params for depositToGame ----------', $token);
        return $this->callApi(self::API_depositToGame, $token, $context);
    }

    public function processResultForDepositToGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($success){
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            $status = $arrayResult['code'];
            $result['reason_id'] = $this->getTransferErrorReasonCode($status);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

        $this->CI->utils->debug_log('---------- RG result for depositToGame ----------', $arrayResult);
        return array($success, $result);
    }


    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $playerId = $this->getPlayerIdInPlayer($playerName);
        $orderId = $transfer_secure_id;
        if($orderId == null) {
            $orderId = $this->getSecureId('transfer_request', 'secure_id', true, 'T');
        }
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'external_transaction_id'=> $orderId
        );

        $data = [
            'account_name' => $gameUsername,
            'amount' => $amount,
            'out_trade_no' => $orderId
        ];

        $token = $this->generateJwtToken(self::API_withdrawFromGame, $data);

        $this->CI->utils->debug_log('---------- RG params for withdrawFromGame ----------', $token);
        return $this->callApi(self::API_withdrawFromGame, $token, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, $playerName);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        );

        if($success){
            $result['didnot_insert_game_logs'] = true;
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
        }  else {
            $status = $arrayResult['code'];
            $result['reason_id'] = $this->getTransferErrorReasonCode($status);
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

        $this->CI->utils->debug_log('---------- RG result for withdrawFromGame ----------', $arrayResult);
        return array($success, $result);
    }

    public function getTransferErrorReasonCode($errorCode) {
        switch ($errorCode) {
            case 'exist':
                $reasonCode = self::REASON_DUPLICATE_TRANSFER;
                break;
            case 'insufficient':
                $reasonCode = self::REASON_NO_ENOUGH_CREDIT_IN_SYSTEM;
                break;
            case 'invalid':
                $reasonCode = self::REASON_INVALID_TRANSFER_AMOUNT;
                break;
            default:
                $reasonCode = self::REASON_UNKNOWN;
        }

        return $reasonCode;
    }

    public function queryForwardGame($playerName, $extra = null) {

        $launch_url = $this->launchURL;
        if($extra['is_mobile']) {
            $launch_url = $this->mobileLaunchURL;
        }
        $token = '';
        if($playerName !== null) {
            $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
            $data = [
                'user_name' => $gameUsername,
                'lobby_url' => $this->lobbyURL,
                'iat' => time() + 60*30, 
            ];

            $token = $this->generateJwtToken(null, $data);
        }

        $init = [
            'lang' => $this->getLauncherLanguage($extra['language']),
        ];

        if($this->customCSS !== null) {
            $init['ocss'] = $this->customCSS . '?v=' . $this->CI->utils->getCmsVersion();
        }
        $init = urlencode(json_encode($init));

        $result = [
            'success' => true,
            'url' => $launch_url . "/?payload={$token}&init={$init}"
        ];

        return $result;
    }


    public function processResultForQueryForwardGame($params) {
        $arrayResult = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult);

        $result['success'] = false;
        if($success){
            $result['success'] = true;
            $result['url'] = $arrayResult['launchurl'];
            $this->CI->utils->debug_log('---------- RG result url for queryForwardGame ----------', $result['url']);
        }
        return array($success, $result);
    }

    public function getLauncherLanguage($currentLang) {
        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case "zh-cn":
                $language = 'zh';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case "id-id":
                $language = 'id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case "vi-vn":
                $language = 'vi';
                break;
            case "en-us":
            $language = 'en';
            break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case "th-TH":
                $language = 'th';
                break;
            default:
                $language = 'en';
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

        $page = 1;

        $result = [];
        $current_result = [];

        do {

            $data = [
                'start_time' => strtotime($startDate),
                'end_time' => strtotime($endDate),
                'settle' => 2, //0 Unsettled 1 Settled 2 All
                'page' => $page
            ];
    
            $token = $this->generateJwtToken(self::API_syncGameRecords, $data);

            $this->CI->utils->debug_log('---------- RG params for syncOriginalGameLogs ----------', $token, 'page', $page);
            $result[] = $current_result = $this->callApi(self::API_syncGameRecords, $token, $context);
            $page++;
            sleep(5);
        } while($current_result['data_count'] > 0);

        return $current_result;

    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $arrayResult = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $arrayResult, null);
        $gameRecords = !empty($arrayResult['data']) ? $arrayResult['data']:[];

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );

        $this->CI->utils->debug_log('---------- RG result for processResultForSyncGameRecords ----------', $arrayResult);
        if($success && !empty($gameRecords)) {

            $this->processGameRecords($gameRecords,$responseResultId);

            list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                $this->originalTable,
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

                $data['live'] = isset($record['live']) ? $record['live'] : null;
                $data['order_type'] = isset($record['order_type']) ? $record['order_type'] : null;
                $data['odds_count'] = isset($record['odds_count']) ? $record['odds_count'] : null;
                $data['username'] = isset($record['username']) ? $record['username'] : null;
                $data['total_stake'] = isset($record['total_stake']) ? $record['total_stake'] : null;
                $data['total_bonus'] = isset($record['total_bonus']) ? $record['total_bonus'] : null;
                $data['total_bet_bonus'] = isset($record['total_bet_bonus']) ? $record['total_bet_bonus'] : null;
                $data['win'] = isset($record['win']) ? $record['win'] : null;
                $data['status'] = isset($record['status']) ? $record['status'] : null;
                $data['create_time'] = isset($record['create_time']) ? $record['create_time'] : null;
                $data['settle_time'] = isset($record['settle_time']) ? $record['settle_time'] : null;
                $data['order_id'] = isset($record['order_id']) ? $record['order_id'] : null;
                $data['currency'] = isset($record['currency']) ? $record['currency'] : null;
                $data['detail_comment'] = isset($record['detail'][0]['comment']) ? $record['detail'][0]['comment'] : null;
                $data['detail_win'] = isset($record['detail'][0]['win']) ? $record['detail'][0]['win'] : null;
                $data['detail_order_id'] = isset($record['detail'][0]['order_id']) ? $record['detail'][0]['order_id'] : null;
                $data['detail_game_id'] = isset($record['detail'][0]['game_id']) ? $record['detail'][0]['game_id'] : null;
                $data['detail_live'] = isset($record['detail'][0]['live']) ? $record['detail'][0]['live'] : null;
                $data['detail_odds'] = isset($record['detail'][0]['odds']) ? $record['detail'][0]['odds'] : null;
                $data['detail_title'] = isset($record['detail'][0]['title']) ? $record['detail'][0]['title'] : null;
                $data['detail_match_name'] = isset($record['detail'][0]['match_name']) ? $record['detail'][0]['match_name'] : null;

                $bet_detail = $this->getBetDetails($record);
                $data['bet_details'] = json_encode($bet_detail);

                $data['external_uniqueid'] = $data['order_id'];
                $data['response_result_id'] = $responseResultId;

                $gameRecords[$index] = $data;
                unset($data);

            }
        }
    }

    private function getBetDetails($data) {
        $bet_details = [];
        if (is_array($data)) {
            if (count($data['detail']) > 1) {
                foreach ($data['detail'] as $data_k => $data_v) {
                    $bet_details[] = [
                        'comment' => $data_v['comment'],
                        'odds' => $data_v['odds'],
                        'title' => $data_v['title'],
                        'match_name' => $data_v['match_name'],
                    ];
                }
            } else {
                $bet_details = [
                    'comment' => $data['detail'][0]['comment'],
                    'odds' => $data['detail'][0]['odds'],
                    'title' => $data['detail'][0]['title'],
                    'match_name' => $data['detail'][0]['match_name'],
                ];
            }
        }
        return $bet_details;
    }


    private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->originalTable, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->originalTable, $record);
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
        $table = $this->originalTable;
        $sqlTime='rg.updated_at >= ? and rg.updated_at <= ?';
        $sql = <<<EOD
SELECT
    rg.id as sync_index,
    rg.username,
    rg.total_stake,
    rg.total_bonus,
    rg.total_bet_bonus,
    rg.win,
    rg.status,
    rg.create_time as start_at,
    rg.create_time as bet_at,
    rg.settle_time as end_at,
    rg.detail_order_id as round_number,
    rg.detail_game_id as game_code,

    rg.detail_comment,
    rg.detail_odds,
    rg.detail_title,
    rg.detail_match_name,
    rg.order_type,
    rg.odds_count,
    rg.bet_details,
    rg.detail_live,

    rg.external_uniqueid,
    rg.updated_at,
    rg.md5_sum,
    rg.response_result_id,

    game_provider_auth.login_name as player_username,
    game_provider_auth.player_id,

    game_description.id as game_description_id,
    game_description.game_name as game_description_name,
    game_description.game_type_id

FROM
    {$table} as rg
    LEFT JOIN game_description ON rg.detail_game_id = game_description.external_game_id AND game_description.game_platform_id = ?
    LEFT JOIN game_type ON game_description.game_type_id = game_type.id
    JOIN game_provider_auth ON rg.username = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
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

        $bet_details = [
            'comment' => $row['detail_comment'],
            'odds' => $row['detail_odds'],
            'title' => $row['detail_title'],
            'match_name' => $row['detail_match_name'],
        ];

        /* FOR OGP-16565 Base on Ray Gaming Guide of eSports Events*/
        $valid_bet = $row['total_stake'];
        if($this->use_rg_valid_bet_calculation){
            if($row['order_type'] == self::ORDER_TYPE_NORMAL){//check if not parlay
                if($row['detail_odds'] < self::ODDS_RATE_BASE_CALCULATION){//check if odds less than 1.5
                    $valid_bet = 0;
                }
            } else {
                $odds_count = $row['odds_count'];
                if($odds_count == self::SINGLE_BET || $odds_count == self::DOUBLE_BET || $odds_count == self::TREBLE_BET){
                    $valid_bet = 0;
                }
            }
        }

        $data = [
            'game_info' => [
                'game_type_id'          => $row['game_type_id'],
                'game_description_id'   => $row['game_description_id'],
                'game_code'             => $row['game_code'],
                'game_type'             => null,
                'game'                  => $row['game_description_name']
            ],
            'player_info' => [
                'player_id'             => $row['player_id'],
                'player_username'       => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount'            => $valid_bet,
                'result_amount'         => $row['total_bonus'] - $row['total_stake'],
                'bet_for_cashback'      => $valid_bet,
                'real_betting_amount'   => $row['total_stake'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => null,
            ],
            'date_info' => [
                'start_at'              => $row['start_at'],
                'end_at'                => $row['end_at'] ?:$row['start_at'],
                'bet_at'                => $row['bet_at'],
                'updated_at'            => $row['updated_at']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_number'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => null
            ],
            'bet_details' => $row['bet_details'],
            'extra' => [
                'handicap'=> $row['detail_live'],
                'odds'=> $row['detail_odds']
            ],

            'game_logs_id' => isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];
        return $data;

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

        switch($row['status']) {
            case 0:
                $row['status'] = Game_logs::STATUS_PENDING;
                break;
            case 1:
                $row['status'] = Game_logs::STATUS_SETTLED;
                break;
            case 2:
                $row['status'] = Game_logs::STATUS_CANCELLED;
                break;
            default:
                $row['status'] = Game_logs::STATUS_REJECTED;
                break;
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
                $unknownGame->game_type_id, $row['game_description_name'], $row['game_code']);
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
        $playerName = $extra['playerName'];
        $transferMethod = $extra['transfer_method'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id' => $transactionId
        );
        // TRANSACTION_WITHDRAW
        // TRANSACTION_DEPOSIT
        $transaction_type = $transferMethod == 'deposit' ? self::TRANSACTION_DEPOSIT : self::TRANSACTION_WITHDRAW;
        $data = [
            'out_trade_no' => $transactionId,
            'account_name' => $gameUsername,
            'type' => $transaction_type,
        ];

        $token = $this->generateJwtToken(self::API_queryTransaction, $data);

        $this->CI->utils->debug_log('---------- RG params for queryTransaction ----------', $token);
        return $this->callApi(self::API_queryTransaction, $token, $context);
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
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $arrayResult);

        $result = [
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'status' => self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id' => self::REASON_UNKNOWN
        ];

        if($success) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
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

}
