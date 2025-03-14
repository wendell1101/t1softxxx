<?php require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Class Game_api_ld_lottery
 *
 * Interface key        : Ubl9RBMYPDceE6gRREkSHpKI
 * Interface address    : https://apikeno.bestxg.com
 * Front end address    : https://keno.bestxg.com
 *
 * https://apibo.bestxg.com
 */
class Game_api_ld_lottery extends Abstract_game_api {
    const TRANSFER_SUCCESS = 1;
    const DEFAULT_LANG = 'en';
    const ALL_USERS = '0';
    const MOBILE_GAME = 'H5';
    const TRIAL_MODE_LIST = array("demo", "trial", "fun");
    const REAL_ACCOUNT = "1";
    const ORIGINAL_LOGS_TABLE_NAME = "ld_lottery_game_logs";
    const SINGLE_ROUND_COUNT = 1;

    const URI_MAP = array(
        self::API_createPlayer => '/req/reg',
        self::API_login => '/req/reg',
        self::API_depositToGame => '/req/Recharge',
        self::API_withdrawFromGame => '/req/Withdraw',
        self::API_queryPlayerBalance => '/req/UserInfo',
        self::API_queryForwardGame => '/financials',
        self::API_isPlayerExist =>  '/req/reg',
        self::API_syncGameRecords =>  '/req/settles',  // Transaction records
    );

    const PLAY_TYPE = array(
        "ou_o" => 'Big',
        "ou_u" => 'Small',
        "ou_t" => 'Tie',
        "oe_o" => 'Single',
        "oe_e" => 'Double',
        "c_bo" => 'Big,Single',
        "c_so" => 'Small,Single',
        "c_be" => 'Big,Double',
        "c_se" => 'Small,Double',
        "r_g" => 'Gold',
        "r_wd" => 'Wood',
        "r_wt" => 'Water',
        "r_f" => 'Fire',
        "r_e" => 'Earth',
        "u_u" => 'Up',
        "u_m" => 'Middle',
        "u_l" => 'Down',
        "oes_o" => 'Odd',
        "oes_t" => 'Tie',
        "oes_e" => 'Even',
    );

    const OPPOSITE_BETS_LIST = ["ou_o", "ou_u", "oe_o", "oe_e", "oes_o", "oes_e"];
    const OPPOSITE_BETS_COMBINATION = array(
        ["ou_o", "ou_u"],
        ["oe_o", "oe_e"],
        ["oes_o", "oes_e"]
    );

    # Fields in ld_lottery game logs to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL = [
        'order_no',
        'platform_account_id',
        'rule_type',
        'odds',
        'bet_amount',
        'bet_time',
        'end_time',
        'payout_amount',
        'round_id',
        'lotto_name',
        'numbers',
        'cmd',
        'play_value',
        'round_key',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'bet_amount',
        'payout_amount'
    ];

    const MD5_FIELDS_FOR_MERGE = [
        'order_no',
        'platform_account_id',
        'rule_type',
        'odds',
        'bet_amount',
        'bet_time',
        'end_time',
        'payout_amount',
        'round_id',
        'lotto_name',
        'numbers',
        'cmd',
        'play_value',
        'round_key',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'payout_amount'
    ];

    public function __construct() {
        parent::__construct();

        $this->api_url = $this->getSystemInfo('url');
        $this->interface_key = $this->getSystemInfo('interface_key');
        $this->platform_id = $this->getSystemInfo('platform_id');
        $this->timezone = $this->getSystemInfo('timezone');
        $this->merchant_id = $this->getSystemInfo('merchant_id');
        $this->game_url = $this->getSystemInfo('game_url');
        $this->demo_suffix = $this->getSystemInfo('demo_username_suffix');
        $this->mobile_game_url = $this->getSystemInfo('mobile_game_url',$this->game_url.'/m/zones');
        $this->record_per_page_request = $this->getSystemInfo('record_per_page_request', 100);
        $this->game_mode = $this->getSystemInfo('game_mode', 'real');
    }

    public function getPlatformCode() {
        return LD_LOTTERY_API;
    }

    protected function customHttpCall($ch, $params) {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }

    public function generateUrl($apiName, $params) {
        $url = $this->api_url . self::URI_MAP[$apiName];
        return $url;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        if ($this->isDemoMode($extra)) {
            $gameUsername .= $this->demo_suffix;
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'gameUsername' => $gameUsername
        );

        $params = array(
            'userId' =>  $gameUsername,
            'userName' => $gameUsername,
            'userType' => $this->isDemoMode($extra) ? "0" : "1",
            'platformId' => $this->platform_id
        );

        $params['sign'] = $this->signMd5RequestParams($params, $this->interface_key);

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);

        $resultJsonArr = json_decode($resultText,TRUE);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        if($success) {
            $this->updateExternalAccountIdForPlayer($playerId, $resultJsonArr['userId']);
        } else {
            // if player exist set success to true
            $response = $this->isPlayerExist($playerName);
            if($response['exists']) {
                unset($resultJsonArr['success']);
               $success = true;
            }
        }

        return array($success, $resultJsonArr);
    }

    public function processResultBoolean($responseResultId, $resultJson, $playerName) {
        $success = true;
        if(empty($resultJson['success'])) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log("==========LD Lottery API GOT ERROR=============", $resultJson['msg'], $playerName);
            return false;
        }
        return $success;
    }

    function signMd5RequestParams($params,$secret){
        $params = array_filter($params, function($v, $k){
            return($v != '' && $k != "sign");
        }, ARRAY_FILTER_USE_BOTH);
        ksort($params);
        $sign = [];
        foreach ($params as $k => $v) {
                 $sign[] = $k . '=' . (string)$v;
        }
        $sign = join("&", $sign) . "&key=" . $secret;
        return strtoupper(md5(join('', unpack('c*',$sign))));
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_trans_id = !empty($transfer_secure_id) ? $transfer_secure_id : $this->getSecureId('transfer_request', 'secure_id', false, 'T');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'amount'=>$amount,
            'external_transaction_id' => $external_trans_id,
        );

        $params = array(
            'platformId' =>$this->platform_id,
            'userId' => $gameUsername,
            'recordNo' => $external_trans_id,
            'amount' => $this->convertServerAmountToGameAmount($amount),
        );

        $params['sign'] = $this->signMd5RequestParams($params, $this->interface_key);

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function convertServerAmountToGameAmount($amount) {
       return $amount * 100;      // ld lottery back office
    }

    public function convertGameAmountToServerAmount($amount) {
        return $amount / 100;      // smartbackend
    }

    public function processResultForDepositToGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $amount = $this->getVariableFromContext($params, 'amount');
        $resultJsonArr = $this->getResultJsonFromParams($params);

        // $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
        $result = array('response_result_id' => $responseResultId, 'external_transaction_id'=>$external_transaction_id,
            'reason_id'=>self::REASON_UNKNOWN, 'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN);

        if($success) {
            // $playerBalance = $this->queryPlayerBalance($playerName);

            // $afterBalance = null;// $playerBalance['balance'];

            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
            // if ($playerId) {
            //     $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());
            // } else {
            //     $this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
            // }
            $result['didnot_insert_game_logs']=true;
            $success = true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            //try add reason id
        }
        return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_trans_id = !empty($transfer_secure_id) ? $transfer_secure_id : $this->getSecureId('transfer_request', 'secure_id', false, 'T');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'amount' => $amount,
            'external_transaction_id' => $external_trans_id,
            // 'recordNo' => $external_trans_id
        );

        $params = array(
            'platformId' => $this->platform_id,
            'userId' => $gameUsername,
            'recordNo' => $external_trans_id,
            'amount' => $this->convertServerAmountToGameAmount($amount)
        );

        $params['sign'] = $this->signMd5RequestParams($params, $this->interface_key);

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');

        $amount = $this->getVariableFromContext($params, 'amount');
        $resultJsonArr = $this->getResultJsonFromParams($params);

        // $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);
        $result = array('response_result_id' => $responseResultId, 'external_transaction_id'=>$external_transaction_id,
            'reason_id'=>self::REASON_UNKNOWN, 'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN);

        if($success) {
            // $playerBalance = $this->queryPlayerBalance($playerName);

            // $afterBalance = null; // $playerBalance['balance'];
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
            // if ($playerId) {
            //     $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
            // } else {
            //     $this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
            // }
            $result['didnot_insert_game_logs']=true;
            $success = true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            //try add reason id
        }
        return array($success, $result);
    }

    public function login($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $params = array(
            'userId' => $gameUsername,
            'userName' => $gameUsername,
            'userType' => $this->isDemoMode($extra) ? "0" : "1",
            'platformId' => $this->platform_id
        );

        $params['sign'] = $this->signMd5RequestParams($params, $this->interface_key);

        return $this->callApi(self::API_login, $params, $context);
    }

    public function getGameLanguage($language) {
        switch ($language) {
            case 1:
                $lang = 'en';
                break;
            case 2:
                $lang = 'zh-cn';
                break;
            default:
                $lang = 'en';
                break;
        }
        return $lang;
    }

    public function processResultForLogin($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');

        $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        return array($success, $resultJsonArr);
    }

    public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $params = array(
            'platformId' => $this->platform_id,
            'userId' => $gameUsername,
        );

        $params['sign'] = $this->signMd5RequestParams($params, $this->interface_key);

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);

    }

    public function processResultForQueryPlayerBalance($params) {

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $resultText = $this->getResultTextFromParams($params);

        $resultJsonArr = json_decode($resultText,TRUE);

        $success = $resultJsonArr['success'];

        $result = [];

        if($success) {
            $result['balance'] = $this->gameAmountToDB($this->convertGameAmountToServerAmount(floatval($resultJsonArr['user']['Balance'])));
        }

        return array($success, $result);
    }

    public function isPlayerExist($playerName, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        if ($this->isDemoMode($extra)) {
            $gameUsername .= $this->demo_suffix;
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername
        );

        $params = array(
            'platformId' => $this->platform_id,
            'userId' => $gameUsername,
        );

        $params['sign'] = $this->signMd5RequestParams($params, $this->interface_key);

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $resultJsonArr = json_decode($resultText,TRUE);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        if (empty($resultJsonArr)) {
            $result['exists'] = null;
        } else {
            $success = true;
            $result['exists'] = !empty($resultJsonArr['success']);
        }

        return array($success, $result);
    }

    public function syncOriginalGameLogs($token = false) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());

        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate = $endDate->format('Y-m-d H:i:s');

        return $this->_continueSync($startDate, $endDate);
    }

    public function _continueSync($startDate, $endDate, $page = 1) {
        $result = $this->syncLdLotteryOriginalGames($startDate, $endDate, $page);
        $this->CI->utils->debug_log('=====> CT Lottery syncLdLotteryOriginalGames result ' . json_encode($result));

        if ($result['success'] && !empty($result['response_record_count'])) {
            $page++;
            return $this->_continueSync($startDate, $endDate, $page);
        }

        return $result;
    }

    public function syncLdLotteryOriginalGames($startDate, $endDate, $page) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
            'startDate' => $startDate,
            'endDate' => $endDate,
        );

        $params = array(
            'platformId' => $this->platform_id,
            'userId' => self::ALL_USERS,
            'startTime' => $startDate,
            'endTime' => $endDate,
            'page' => $page,
            'perpage' => $this->record_per_page_request,
        );

        $params['sign'] = $this->signMd5RequestParams($params, $this->interface_key);
        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model('original_game_logs_model');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $gameRecords = json_decode($resultText,TRUE);
        $success = $gameRecords['success'];

        // $record_count = 0;
        $total_pages = 0;
        $result = array('data_count' => 0);
        $data = array();

        if($success) {
            $result['response_record_count'] = count($gameRecords['list']['items']);
            $result['total_pages']           = $gameRecords['list']['pager']['total_pages'];
            $result['total_items']           = $gameRecords['list']['pager']['total_items'];

            if (!empty($gameRecords['list']['items'])) {
                # change api response field to ld lottery game logs column
                $data = $gameRecords['list']['items'];
                $this->rebuildOriginalLogs($data, $responseResultId);

                list($insertRows, $updateRows) = $this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    self::ORIGINAL_LOGS_TABLE_NAME,
                    $data,
                    'external_uniqueid',
                    'external_uniqueid',
                    self::MD5_FIELDS_FOR_ORIGINAL,
                    'md5_sum',
                    'id',
                    self::MD5_FLOAT_AMOUNT_FIELDS
                );

                $this->CI->utils->debug_log('LD lottery after process >>>>>>>>> ', count($data), count($insertRows), count($updateRows));
                unset($gameRecords);
                unset($data);

                if (!empty($insertRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert');
                }
                unset($insertRows);

                if (!empty($updateRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update');
                }
                unset($updateRows);
            }
        }

        return array($success, $result);
    }

    public function rebuildOriginalLogs(&$records, $responseResultId) {
        $data = array();

        foreach ($records as $key => $record) {
            $logs = array(
                'order_no'              => $record['OrderNo'],
                'platform_account_id'   => $record['PlatformAccountId'],
                'rule_type'             => $record['RuleType'],
                'odds'                  => $this->convertGameAmountToServerAmount($record['Odds']),
                'bet_amount'            => $this->convertGameAmountToServerAmount($record['BetAmount']),
                'bet_time'              => $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['BetTime']))),
                'end_time'              => $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['EndTime']))),
                'payout_amount'         =>$this->convertGameAmountToServerAmount($record['PayoutAmount']),
                'win_lose'              => $record['WinLose'],
                'round_id'              => $record['round_no'],
                'lotto_name'            => $record['LottoName'],
                'numbers'               => json_encode($record['Numbers']),
                'cmd'                   => $record['Cmd'],
                'play_value'            => $record['PlayValue'],
                //  'md5_sum'               => md5($data['order_no'] . $data['round_id'] . $data['platform_account_id'] . $data['bet_amount'] . $data['bet_time']),
                'created_at'            => $this->CI->utils->getNowForMysql(),
                'winloss_amount'        => $this->convertGameAmountToServerAmount($record['PayoutAmount']) - $this->convertGameAmountToServerAmount($record['BetAmount']),

                //extra info from SB,
                'external_uniqueid'     => $record['OrderNo'],
                'response_result_id'    => $responseResultId,
                'round_key' => $record['PlatformAccountId'].'-'.$record['LottoName'].'-'.$record['round_no'],
            );

            array_push($data, $logs);
        }

        $records = $data;
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal(self::ORIGINAL_LOGS_TABLE_NAME, $record);
                }
                $dataCount++;
            }
        }

        return $dataCount;
    }


    public function syncRecords($gameRecords, $responseResultId) {
        $round_ids = array();
        $externalUniqueIds = array();
        $map_external_to_round_id = array();
        $map = array();
        $count = 0;

        if (!empty($gameRecords)) {
            $data = array();

            foreach ($gameRecords as $row) {
                # check multiple bets or same round id but diffent player on the same round
                if (!in_array($row['round_no'], $round_ids) ||
                    (in_array($row['round_no'], $round_ids) && isset($map[$map_external_to_round_id[$row['round_no']]]) &&
                        ($map[$map_external_to_round_id[$row['round_no']]]['platform_account_id'] != $row['PlatformAccountId'])
                    ))
                {
                    array_push($round_ids, $row['round_no']);
                    array_push($externalUniqueIds, $row['OrderNo']);
                    $betDetails = array();

                    $data['order_no'] = $row['OrderNo'];
                    $data['platform_account_id'] =  $row['PlatformAccountId'];
                    $data['rule_type'] =  $row['RuleType'];
                    $data['odds'] =  $this->convertGameAmountToServerAmount($row['Odds']);
                    $data['bet_amount'] =  $this->convertGameAmountToServerAmount($row['BetAmount']);
                    $data['bet_time'] =  $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($row['BetTime'])));
                    $data['end_time'] =  empty($row['EndTime'])?$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($row['BetTime']))):$this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($row['EndTime'])));
                    $data['payout_amount'] = $this->convertGameAmountToServerAmount($row['PayoutAmount']);
                    $data['win_lose'] =  $row['WinLose'];
                    $data['round_id'] =  $row['round_no'];
                    $data['winloss_amount'] =  $data['payout_amount'] - $data['bet_amount'];

                    $extra_win_amount = !empty($data['win_lose']) ? $data['payout_amount'] - $data['bet_amount'] : 0;
                    $extra_won_side = !empty($data['win_lose']) ? "Yes" : "No";

                    $betDetails['bet_details'][$row['OrderNo']] = array(
                        "odds" => null,
                        'win_amount' => $extra_win_amount,
                        'bet_amount' => $data['bet_amount'],
                        "bet_placed" => $data['rule_type'],
                        "won_side" => $extra_won_side,
                        "winloss_amount" => $data['winloss_amount'],
                    );
                    $betDetails['isMultiBet'] = false;
                    $data['extra'] = json_encode($betDetails);

                    $playerID = $this->getPlayerIdInGameProviderAuth(strtolower($row['PlatformAccountId']));
                    $playerUsername = $this->getGameUsernameByPlayerId($playerID);

                    //extra info from SBE
                    $data['username'] = $playerUsername;
                    $data['player_id'] = $playerID;
                    $data['external_uniqueid'] = $row['OrderNo'];
                    $data['response_result_id'] = $responseResultId;

                    if (!isset($map[$row['OrderNo']])) {
                        $map[$row['OrderNo']] = $data;
                        $map_external_to_round_id[$data['round_id']]  = $data['order_no'];
                    }
                } else {
                    # merge amount, valid bet, win lose, and valid bet then add it extra info if multiple bets
                    $tmp_data = $map[$map_external_to_round_id[$row['round_no']]];
                    $extra = array();
                    $extra = json_decode($tmp_data['extra'], true);

                    $extra_win_amount = !empty($row['WinLose']) ? $row['PayoutAmount'] - $row['BetAmount'] : 0;
                    $extra_won_side = !empty($row['WinLose']) ? "Yes" : "No";
                    $extra_win_loss_amount = $row['PayoutAmount'] - $row['BetAmount'];

                    $extra['bet_details'][$row['OrderNo']] = array(
                        "odds" => null,
                        'win_amount' => $this->convertGameAmountToServerAmount($extra_win_amount),
                        'bet_amount' => $this->convertGameAmountToServerAmount($row['BetAmount']),
                        "bet_placed" => $row['RuleType'],
                        "won_side" => $extra_won_side,
                        "winloss_amount" => $this->convertGameAmountToServerAmount($extra_win_loss_amount),
                    );
                    $extra['isMultiBet'] = true;

                    $map[$map_external_to_round_id[$row['round_no']]]['bet_amount'] += $this->convertGameAmountToServerAmount($row['BetAmount']);
                    // $map[$map_external_to_round_id[$row['round_no']]]['valid_bet'] += $this->convertGameAmountToServerAmount($row['validBet']);
                    $map[$map_external_to_round_id[$row['round_no']]]['winloss_amount'] += ($this->convertGameAmountToServerAmount($row['PayoutAmount']) - $this->convertGameAmountToServerAmount($row['BetAmount']));
                    $map[$map_external_to_round_id[$row['round_no']]]['payout_amount'] += $this->convertGameAmountToServerAmount($row['PayoutAmount']);
                    $map[$map_external_to_round_id[$row['round_no']]]['extra'] = json_encode($extra);
                }
            }
        }

        $existingRecord = $this->CI->ld_lottery_game_logs->getExistingBetIds($externalUniqueIds);

        # Update data
        if (!empty($existingRecord)) {
            foreach ($existingRecord as $rec) {
                $this->CI->ld_lottery_game_logs->updateGameLog($rec['id'], $map[$rec['order_no']]);
                unset($map[$rec['order_no']]);
                $count++;
            }
       }

        # insert data
        if (!empty($map)) {
            foreach ($map as $key => $row) {
                $this->CI->ld_lottery_game_logs->insertGameLogs($row);
                $count++;
            }
        }

        return $count;
    }

    public function queryForwardGame($playerName, $extra=null) {
        $result = $this->login($playerName, $extra);

        if($result['success']) {
            $language = !empty($extra['language']) ? $this->getGameLanguage($extra['language']) :  self::DEFAULT_LANG;

            $params = array(
                'token' => $result['token'],
                'lang' => $language
            );

            $url = $this->game_url.'?'.http_build_query($params);

            if (!empty($extra['is_mobile'])) {
                $url = $this->mobile_game_url.'?'.http_build_query($params);
            }

            return array( 'url' => $url );
        }
    }

    public function syncMergeToGameLogs($token) {
        $enabled_game_logs_unsettle = false;    # game provider response for this function  /req/settles is only settle and dont provide game status on there response
        return $this->commonSyncMergeToGameLogs($token,
            $this,
            [$this, 'queryOriginalGameLogs'],
            [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
            [$this, 'preprocessOriginalRowForGameLogs'],
            $enabled_game_logs_unsettle,
            [$this, 'prepareOriginalRows']
        );
    }


    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime =  'a.end_time >= ? and a.end_time <= ?';

        if ($use_bet_time) {
            $sqlTime =  'a.bet_time >= ? and a.bet_time <= ?';
        }

        $this->CI->utils->debug_log('use_bet_time on queryOriginalGameLogs', $use_bet_time, $sqlTime);

        $sql = <<<EOD
SELECT
    a.id as sync_index,
    a.order_no,
    a.platform_account_id AS player_username,
    a.rule_type,
    a.odds,
    a.bet_amount,
    a.bet_time,
    a.end_time,
    a.payout_amount,
    a.win_lose,
    a.response_result_id,
    a.external_uniqueid,
    a.round_id,
    a.winloss_amount AS result_amount,
    a.lotto_name AS game_code,
    a.lotto_name AS game,
    a.numbers,
    a.cmd AS bet_placed,
    a.play_value,
    a.lotto_name,
    a.md5_sum,
    a.round_key,
    game_provider_auth.player_id,
    game_description.id AS game_description_id,
    game_description.game_type_id
FROM
    ld_lottery_game_logs a
    JOIN game_provider_auth
        ON a.platform_account_id = game_provider_auth.login_name and game_provider_auth.game_provider_id = ?
    LEFT JOIN game_description
        ON game_description.external_game_id = a.lotto_name
        AND game_description.void_bet != 1
        AND game_description.game_platform_id = ?
WHERE
    {$sqlTime}
EOD;

        $params = [
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE, self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
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
                'result_amount'         => $row['result_amount'],
                'bet_for_cashback'      => $row['bet_amount'],
                'real_betting_amount'   => $row['bet_amount'],
                'win_amount'            => null,
                'loss_amount'           => null,
                'after_balance'         => null
            ],
            'date_info' => [
                'start_at'              => $row['bet_time'],
                'end_at'                => $row['bet_time'],
                'bet_at'                => $row['bet_time'],
                'updated_at'            => $row['end_time']
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => Game_logs::STATUS_SETTLED,
            'additional_info' => [
                'has_both_side'         => 0,
                'external_uniqueid'     => $row['external_uniqueid'],
                'round_number'          => $row['round_id'],
                'md5_sum'               => $row['md5_sum'],
                'response_result_id'    => $row['response_result_id'],
                'sync_index'            => $row['sync_index'],
                'bet_type'              => $row['bet_type']
            ],
            'bet_details' => $row['bet_details'],
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
        ];
    }

    public function prepareOriginalRows(array &$rows){
        if(empty($rows)){
            return;
        }

        //process bet details
        $roundKeyList=array_column($rows, 'round_key');
        $roundKeyList=array_unique($roundKeyList);

        $this->CI->utils->debug_log('prepareOriginalRows count rows',count($rows),'round key count', count($roundKeyList));
        //same fields with bet details
        $this->CI->db->select('round_key, odds, win_lose,payout_amount,bet_amount, play_value, cmd as bet_placed, winloss_amount AS result_amount')
            ->from('ld_lottery_game_logs')->where_in('round_key', $roundKeyList);
        $qry=$this->CI->db->get();

        unset($roundKeyList);
        $roundKeyMap=[];
        if(!empty($qry)){
            $roundRows= $qry->result_array();
            unset($qry);
            if(!empty($roundRows)){
                $this->CI->utils->debug_log('found bet details', count($roundRows));
                foreach ($roundRows as $roundRow) {
                    if(!isset($roundKeyMap[$roundRow['round_key']])){
                        $roundKeyMap[$roundRow['round_key']]=[];
                    }
                    $roundKeyMap[$roundRow['round_key']][]=[
                        "odds"          => $roundRow['odds'],
                        "win_amount"    => !empty($roundRow['win_lose']) ? $roundRow['payout_amount'] - $roundRow['bet_amount'] : 0,
                        "bet_amount"    => $roundRow['bet_amount'],
                        "bet_placed"    => $roundRow['bet_placed'],
                        "won_side"      => null,
                        "winloss_amount"=> $roundRow['result_amount'],
                        "play_value"    => $roundRow['play_value'],
                    ];
                }
            }
            unset($roundRows);
        }
        //update bet details
        foreach ($rows as &$row) {
            if(isset($roundKeyMap[$row['round_key']])){
                $bet_details=$roundKeyMap[$row['round_key']];
                if(count($bet_details)>1){
                    if(count($bet_details)>$this->max_rows_of_bet_details){
                        //max limit 400
                        array_splice($bet_details, $this->max_rows_of_bet_details+1);
                    }
                    $row['bet_details'] = ['bet_details' =>$bet_details ];
                    $row['bet_type']    = Game_logs::BET_TYPE_MULTI_BET;
                }else{
                    //only one record
                    $row['bet_details'] = ['bet_details' =>$bet_details ];
                    $row['bet_type']    = Game_logs::BET_TYPE_SINGLE_BET;
                }
            }else{
                //lost round key
                $row['bet_details'] = [
                    "odds"          => $row['odds'],
                    "win_amount"    => !empty($row['win_lose']) ? $row['payout_amount'] - $row['bet_amount'] : 0,
                    "bet_amount"    => $row['bet_amount'],
                    "bet_placed"    => $row['bet_placed'],
                    "won_side"      => null,
                    "winloss_amount"=> $row['result_amount'],
                    "play_value"    => $row['play_value'],
                ];
                $row['bet_type']    = Game_logs::BET_TYPE_SINGLE_BET;
                $this->CI->utils->error_log('lost round key');
            }
        }
        //free
        unset($roundKeyMap);

    }

    public function preprocessOriginalRowForGameLogs(array &$row){

        if (empty($row['game_type_id'])) {
            list($row['game_description_id'], $row['game_type_id']) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        // $gameResult = $this->getByRoundIdGameAndGameUsername($row['round_id'], $row['player_username'], $row['lotto_name']);
        // if (count($gameResult) == self::SINGLE_ROUND_COUNT) {
            // $row['bet_details'] = array(
            //     "odds"          => $row['odds'],
            //     "win_amount"    => !empty($row['win_lose']) ? $row['payout_amount'] - $row['bet_amount'] : 0,
            //     "bet_amount"    => $row['bet_amount'],
            //     "bet_placed"    => $row['bet_placed'],
            //     "won_side"      => null,
            //     "winloss_amount"=> $row['result_amount'],
            //     "play_value"    => $row['play_value'],
            // );
        // } else {
        //     $row['bet_details'] = array('bet_details' => $this->getBetDetailsWithSameRoundId($gameResult));
        //     // $this->getBetDetailsWithSameRoundId($gameResult, $row['bet_details']);
        // }
        // unset($gameResult);

        // $row['bet_details'] = $bet_details;
        $row['status']      = Game_logs::STATUS_SETTLED;        # ld lottery api response for game logs is settle
        // $row['bet_type']    = Game_logs::BET_TYPE_SINGLE_BET;
        // unset($bet_details);

        $this->CI->utils->debug_log('preprocessOriginalRowForGameLogs', $row['round_id'], $row['player_username']);
    }

    // public function getBetDetailsWithSameRoundId($gameResult) {
    //     $bet_details = [];
    //     // $bet_details = ['bet_details' =>[]];
    //     if (!empty($gameResult)) {
    //         foreach ($gameResult as $key => $row) {
    //             $bet_details[$row['order_no']] = array(
    //                 "odds"          => $row['odds'],
    //                 "win_amount"    => !empty($row['win_lose']) ? $row['payout_amount'] - $row['bet_amount'] : 0,
    //                 "bet_amount"    => $row['bet_amount'],
    //                 "bet_placed"    => $row['bet_placed'],
    //                 "won_side"      => null,
    //                 "winloss_amount"=> $row['result_amount'],
    //                 "play_value"    => $row['play_value'],
    //             );
    //         }
    //     }
    //     unset($gameResult);
    //     return $bet_details;
    // }

    // public function getByRoundIdGameAndGameUsername($roundId, $gameUsername, $gameName){
    //     $rows =null;
    //     try{
    //         $conn=mysqli_connect($this->CI->utils->getConfig('db.default.hostname'),
    //             $this->CI->utils->getConfig('db.default.username'),
    //             $this->CI->utils->getConfig('db.default.password'),
    //             $this->CI->utils->getConfig('db.default.database'),
    //             $this->CI->utils->getConfig('db.default.port'));
    //         $charset=$this->CI->utils->getConfig('db.default.char_set');

    //         $this->CI->db->select('*, cmd as bet_placed, winloss_amount AS result_amount');
    //         $this->CI->db->from(self::ORIGINAL_LOGS_TABLE_NAME);
    //         $this->CI->db->where('round_id', $roundId);
    //         $this->CI->db->where('platform_account_id', $gameUsername);
    //         $this->CI->db->where('lotto_name', $gameName);
    //         $sql=$this->CI->db->_compile_select();
    //         $this->CI->db->_reset_select();

    //         $qry = mysqli_query($conn, $sql, MYSQLI_USE_RESULT);
    //         $rows = mysqli_fetch_all($qry,MYSQLI_ASSOC);
    //         mysqli_free_result($qry);
    //     }finally{
    //         mysqli_close($conn);
    //     }

    //     // $this->CI->db->select('*, cmd as bet_placed, winloss_amount AS result_amount');
    //     // $this->CI->db->from(self::ORIGINAL_LOGS_TABLE_NAME);
    //     // $this->CI->db->where('round_id', $roundId);
    //     // $this->CI->db->where('platform_account_id', $gameUsername);
    //     // $this->CI->db->where('lotto_name', $gameName);
    //     // $qry = $this->CI->db->get();
    //     // $rows= $qry->result_array();
    //     // unset($qry);
    //     return $rows;
    // }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;

        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $row['game_name']);

        $game_type_id = $unknownGame->game_type_id;
        $game_type = $unknownGame->game_name;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    // public function computeAvailableBet($betList) {
    //     if (empty($betList) && empty($betList['bet_details'])) {
    //         return false;
    //     }
    //     $oppositeBetsList = [];
    //     $availableBets = 0;

    //     foreach ($betList['bet_details'] as $key => $value) {
    //         # check if in opposite bets list
    //         if (in_array($value['bet_placed'], self::OPPOSITE_BETS_LIST)) {
    //             # check if dupplicate placed of bet
    //             if (isset($oppositeBetsList[$value['bet_placed']])) {
    //                 $oppositeBetsList[$value['bet_placed']]['betAmount'] += $value['bet_amount'];
    //                 $oppositeBetsList[$value['bet_placed']]['winLossAmount'] += $value['winloss_amount'];
    //                 continue;
    //             }

    //             $oppositeBetsList[$value['bet_placed']] = array(
    //                 "betPlaced" => $value['bet_placed'],
    //                 "betAmount" => $value['bet_amount'],
    //                 "winLossAmount" => $value['winloss_amount']
    //             );
    //         # add to available bets if not included in opposite bet list
    //         } else {
    //             $availableBets += $value['bet_amount'];
    //         }
    //     }

    //     # convert array keys
    //     $oppositeBetsList = array_values($oppositeBetsList);

    //     # check if in opposite bets combination
    //     foreach (self::OPPOSITE_BETS_COMBINATION as $key => $combination) {
    //         $oppositeBetsArrayKey = [];

    //         foreach ($combination as $betType) {
    //             if (in_array($betType, array_column($oppositeBetsList, 'betPlaced'))) {
    //                 # get array key of opposites bets if opposite bets
    //                 $betPlacedKey = array_search($betType, array_column($oppositeBetsList, 'betPlaced'));
    //                 array_push($oppositeBetsArrayKey, $betPlacedKey);
    //             }
    //         }

    //         if (!empty($oppositeBetsArrayKey)) {
    //             $oppositeBets = [];
    //             $tmpBetAmount = 0;
    //             $isSameBetAmount = false;

    //             # get opposite bet's bet amount, and winloss amount
    //             foreach ($oppositeBetsArrayKey as $arrayKey) {
    //                 $oppositeBets[] = array(
    //                     "key" => $arrayKey,
    //                     "bet" => $oppositeBetsList[$arrayKey]['betAmount'],
    //                     "winloss" => $oppositeBetsList[$arrayKey]['winLossAmount'],
    //                 );
    //             }

    //             # check if opposite bets has same bet amount
    //             if (count($oppositeBets) > 1) {
    //                 $isSameBetAmount = (count(array_unique(array_column($oppositeBets, 'bet')))  === 1);
    //             }

    //             # compute valid bet
    //             $tmpAvailableBet = 0;
    //             foreach ($oppositeBets as $oppositeBet) {
    //                  if (empty($tmpAvailableBet)) {
    //                     $tmpAvailableBet = $isSameBetAmount ? abs($oppositeBet['winloss']) : $oppositeBet['bet'];
    //                     continue;
    //                 }

    //                 if ($isSameBetAmount) { # if same bet amount in opposite bet, available_bet = difference between winloss
    //                    $tmpAvailableBet -= abs($oppositeBet['winloss']);
    //                 } else {  # else, available_bet = difference between bet amount
    //                     $tmpAvailableBet -= $oppositeBet['bet'];
    //                 }
    //             }

    //             $availableBets += abs($tmpAvailableBet);
    //         }
    //     }

    //     return $availableBets;
    // }

    // public function prepareBetDetails($betList) {
    //     if (empty($betList) && empty($betList['bet_details'])) {
    //         return;
    //     }

    //     $newBetdetails = [];
    //     $tmpBetList = [];

    //     # merge duplicate bet type then translate bet type code to word
    //     foreach ($betList['bet_details'] as $key => $value) {
    //         if (isset($tmpBetList[$value['bet_placed']])) {
    //             $tmpBetList[$value['bet_placed']]['win_amount'] += $value['win_amount'];
    //             $tmpBetList[$value['bet_placed']]['bet_amount'] += $value['bet_amount'];
    //             $tmpBetList[$value['bet_placed']]['winloss_amount'] += $value['winloss_amount'];
    //             continue;
    //         }

    //         $bet_placed = self::PLAY_TYPE[$value['bet_placed']];
    //         if (empty($bet_placed)) {
    //             $bet_placed = $value['bet_placed'];
    //         }

    //         $tmpBetList[$value['bet_placed']] = array(
    //             'betId' => $key,
    //             'odds' => $value['odds'],
    //             'win_amount' => $value['win_amount'],
    //             'bet_amount' => $value['bet_amount'],
    //             'bet_placed' => $bet_placed,
    //             'won_side' => $value['won_side'],
    //             'winloss_amount' => $value['winloss_amount'],
    //         );
    //         // $betList['bet_details'][$key]['bet_placed'] = self::PLAY_TYPE[$value['bet_placed']];
    //     }

    //     # revert array key to bet id
    //     if (!empty($tmpBetList)) {
    //         foreach ($tmpBetList as $key => $value) {
    //             $betId = $value['betId'];
    //             unset($tmpBetList[$key]['betId']);
    //             $newBetdetails[$betId] = $tmpBetList[$key];
    //         }
    //     }

    //     return array("bet_details" => $newBetdetails);
    // }


    public function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    public function changePassword($playerName, $oldPassword, $newPassword) {
        return $this->returnUnimplemented();
    }

    public function updatePlayerInfo($playerName, $infos) {
        return $this->returnUnimplemented();
    }

    public function logout($playerName, $password = null) {
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

    public function queryTransaction($transactionId, $extra) {
        //try Req/Transactions by date time
        return $this->returnUnimplemented();
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return array(false, null);
    }

    public function isDemoMode($extra) {
        $game_mode = $this->game_mode; // Set to default game mode
        if(!empty($extra["game_mode"])) $game_mode = $extra["game_mode"];
        return in_array(strtolower($game_mode), self::TRIAL_MODE_LIST);
    }
}

/*end of file*/