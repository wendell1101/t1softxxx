<?php require_once dirname(__FILE__) . '/abstract_game_api.php';

/**
 * Class Game_api_finance
 *
 * Interface key        : 920f3b3d794245e8
 * Interface address    : https://apibo.bestxg.com
 * Front end address    : https://bo.bestxg.com
 *
 * https://apibo.bestxg.com
 */
class Game_api_finance extends Abstract_game_api {

    const TRANSFER_SUCCESS = 1;
    const DEFAULT_LANG = 'en';
    const ALL_USERS = '0';
    const MOBILE_GAME = 'H5';

    const URI_MAP = array(
        self::API_createPlayer => '/Req/Reg',
        self::API_login => '/Req/Login',
        self::API_depositToGame => '/Req/Recharge',
        self::API_withdrawFromGame => '/Req/Withdraw',
        self::API_queryPlayerBalance => '/Req/UserInfo',
        self::API_queryForwardGame => '/Financials',
        self::API_isPlayerExist =>  '/Req/Login',
        self::API_syncGameRecords =>  '/Req/OrderRecord',  // TradeRecord  # OrderRecord
    );

    public function __construct() {
        parent::__construct();

        $this->api_url = $this->getSystemInfo('url');
        $this->interface_key = $this->getSystemInfo('interface_key');
        $this->platform_id = $this->getSystemInfo('platform_id');
        $this->timezone = $this->getSystemInfo('timezone');
        $this->merchant_id = $this->getSystemInfo('merchant_id');
        $this->game_url = $this->getSystemInfo('game_url');
    }

    public function getPlatformCode() {
        return FINANCE_API;
    }

    protected function customHttpCall($ch, $params) {
        #echo json_encode($params);exit;
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }

    public function generateUrl($apiName, $params) {
        $api_uri = self::URI_MAP[$apiName];
        $url = $this->api_url.$api_uri;
        #echo $url;exit;
        return $url;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        $sbePlayerName = $playerName;
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'playerId' => $playerId,
            'sbePlayerName' => $sbePlayerName
        );

        $params = array(
            'platformId' => $this->platform_id,
            'userId' => $playerName,
            'userName' => $playerName,
            'timeZone' => $this->timezone
        );
        $params['sign'] = $this->signMd5RequestParams($params, $this->interface_key);

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);

        $resultJsonArr = json_decode($resultText,TRUE);

        $sbePlayerName = $this->getVariableFromContext($params, 'sbePlayerName');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        if($success) {
            $this->updateExternalAccountIdForPlayer($playerId, $resultJsonArr['userId']);
        } else {
            // if player exist set success to true
            $response = $this->isPlayerExist($sbePlayerName);
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
            $this->CI->utils->error_log("==========FINANCE API GOT ERROR=============", $resultJson['msg'], $playerName);
            return false;
        }
        return $success;
    }


    public function signMd5RequestParams($params, $secret) {

        ksort($params);

        $params['key'] = $secret;

        $sign = '';
        foreach ($params as $key => $val) {
            $sign .= $key. '=' . $val . '&';
        }
        $sign = rtrim($sign, '&');

        return strtoupper(md5($sign));

    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_user_id = $this->getExternalAccountIdByPlayerUsername($playerName);

        $secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');

        $external_trans_id = $transfer_secure_id ? $transfer_secure_id : $secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'recordNo' => $external_trans_id,
            //'external_transaction_id' => $external_trans_id
        );

        $params = array(
            'platformId' =>$this->platform_id,
            'userId' => $external_user_id,
            'recordNo' => $external_trans_id,
            'amount' => $this->convertYuanAmountInFBO($amount),
        );

        $params['sign'] = $this->signMd5RequestParams($params, $this->interface_key);

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function convertYuanAmountInFBO($amount) {
        return $amount * 100;      // finance back office
    }

    public function convertYuanAmountInSBE($amount) {
        return $amount / 100;      // smartbackend
    }

    public function processResultForDepositToGame($params) {
        //$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');

        $amount = $this->getParamValueFromParams($params, 'amount');
        $resultText = $this->getResultTextFromParams($params);

        $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
            //'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if($success) {
            // $playerBalance = $this->queryPlayerBalance($playerName);

            // $afterBalance = $playerBalance['balance'];

            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
            // if ($playerId) {
            //     $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());
            // } else {
            //     $this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
            // }
            $success = true;
            $result['didnot_insert_game_logs']=true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $error_code = @$resultJsonArr['msg'];
            switch($error_code) {
                case '参数格式不正确' :  // invalid parameter ( api throw one message if error )
                    $result['reason_id'] = self::REASON_INCOMPLETE_INFORMATION;
                    break;
            }
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
        return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $external_user_id = $this->getExternalAccountIdByPlayerUsername($playerName);

        $secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');
        $external_trans_id = $transfer_secure_id ? $transfer_secure_id : $secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'recordNo' => $external_trans_id,
            //'external_transaction_id' => $external_trans_id,
        );

        $params = array(
            'platformId' => $this->platform_id,
            'userId' => $external_user_id,
            'recordNo' => $external_trans_id,
            'amount' => $this->convertYuanAmountInFBO($amount)
        );

        $params['sign'] = $this->signMd5RequestParams($params, $this->interface_key);

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        //$external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');

        $amount = $this->getParamValueFromParams($params, 'amount');
        $resultText = $this->getResultTextFromParams($params);

        $resultJsonArr = json_decode($resultText,TRUE);

        $result = array(
            'response_result_id' => $responseResultId,
         //   'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);
        if($success) {
            // $playerBalance = $this->queryPlayerBalance($playerName);

            // $afterBalance = $playerBalance['balance'];
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
            // if ($playerId) {
            //     $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
            // } else {
            //     $this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
            // }
            $result['didnot_insert_game_logs']=true;
            $success = true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $error_code = @$resultJsonArr['msg'];
            switch($error_code) {
                case '参数格式不正确' :  // invalid parameter ( api throw one message if error )
                    $result['reason_id'] = self::REASON_INCOMPLETE_INFORMATION;
                    break;
            }
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
        return array($success, $result);
    }

    public function login($playerName, $extra = null) {
        $sbePlayerName = $playerName;
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $external_user_id = $this->getExternalAccountIdByPlayerUsername($sbePlayerName);

        $ip_address = $this->CI->input->ip_address();

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
        );

        $params = array(
            'platformId' => $this->platform_id,
            'userId' => $external_user_id,
            'Lang' => !empty($extra['language']) ? $this->getGameLanguage($extra['language']) :  self::DEFAULT_LANG,
            'IP' => $ip_address
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
                $lang = 'cn';
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
        $sbePlayerName = $playerName;
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $external_user_id = $this->getExternalAccountIdByPlayerUsername($sbePlayerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName
        );

        $params = array(
            'platformId' =>$this->platform_id,
            'userId' => $external_user_id,
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
            $result['balance'] = $this->gameAmountToDB($this->convertYuanAmountInSBE(floatval($resultJsonArr['user']['Balance'])));
        }

        return array($success, $result);
    }

    public function isPlayerExist($playerName) {
        $sbePlayerName = $playerName;
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $external_user_id = $this->getExternalAccountIdByPlayerUsername($sbePlayerName);

        $ip_address = $this->CI->input->ip_address();

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'playerName' => $playerName,
        );

        $params = array(
            'platformId' => $this->platform_id,
            'userId' => $external_user_id,
            'Lang' => !empty($extra['language']) ? $this->getGameLanguage($extra['language']) :  self::DEFAULT_LANG,
            'IP' => $ip_address
        );

        $params['sign'] = $this->signMd5RequestParams($params, $this->interface_key);

        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');

        $resultJsonArr = json_decode($resultText,TRUE);

        $isExist = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        $success = $isExist ? false : true; // if not exist set to true

        $result['exists'] = $isExist ? true : false;

        return array($success, $result);
    }

    public function syncOriginalGameLogs($token = false) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $startDate->modify($this->getDatetimeAdjust());

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate = $endDate->format('Y-m-d H:i:s');

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
            'pageIndex' => 1,
            'pageSize' => 100,
        );

        $params['sign'] =  $this->signMd5RequestParams($params, $this->interface_key);

        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function processResultForSyncGameRecords($params) {

        $this->CI->load->model('finance_game_logs');

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);

        $gameRecords = json_decode($resultText,TRUE);
        $this->CI->utils->debug_log("finance sync original response ===============================================================", $gameRecords);
        $success = $gameRecords['success'];

        $data_count = 0;
        if($success) {
            if (!empty($gameRecords['list'])) {
                $data = [];
                foreach ($gameRecords['list'] as $record) {
                    $playerID = $this->getPlayerIdInGameProviderAuth(strtolower($record['PlatformAccountId']));
                    $playerUsername = $this->getGameUsernameByPlayerId($playerID);

                    $data['OrderNo'] = $record['OrderNo'];
                    $data['PlatformAccountId'] =  $record['PlatformAccountId'];
                    $data['ProCNName'] =  $record['ProCNName'];
                    $data['ProENName'] =  $record['ProENName'];
                    $data['RuleType'] =  $record['RuleType'];
                    $data['Odds'] =  $this->convertYuanAmountInSBE($record['Odds']);
                    $data['BetAmount'] =  $this->convertYuanAmountInSBE($record['BetAmount']);
                    $data['BetTime'] =  $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['BetTime'])));
                    $data['CurrentPrice'] =  $record['CurrentPrice'];
                    $data['ExpirePrice'] =  $record['ExpirePrice'];
                    $data['EndTime'] =  $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['EndTime'])));
                    $data['WinLose'] =  $record['WinLose'];
                    $data['PayoutAmount'] =  $this->convertYuanAmountInSBE($record['PayoutAmount']);
                    $data['OrderNote'] =  $record['OrderNote'];
                    $data['IsDouble'] =  $record['IsDouble'];
                    $data['IsDelay'] =  $record['IsDelay'];
                    $data['IsClose'] =  $record['IsClose'];

                    //extra info from SBE
                    $data['username'] = $playerUsername;
                    $data['player_id'] = $playerID;
                    $data['external_uniqueid'] = $record['OrderNo'];
                    $data['response_result_id'] = $responseResultId;

                    $this->CI->finance_game_logs->syncGameLogs($data);
                    $data_count++;
                }
            }
        }

        $result['data_count'] = $data_count;

        return array($success, $result);
    }

    public function queryForwardGame($playerName, $extra=null) {

        $result = $this->login($playerName, $extra);

        if($result['success']) {
            // $api_uri = self::URI_MAP[self::API_queryForwardGame];

            $language = !empty($extra['language']) ? $this->getGameLanguage($extra['language']) :  self::DEFAULT_LANG;

            if (!empty($extra['is_mobile'])) {
                $siteAccess =  strtoupper($language).self::MOBILE_GAME;
            } else {
                $siteAccess = strtoupper($language);
            }

           # $url = $this->game_url.$api_uri.'/'.$siteAccess.'?'.http_build_query($params);
            // $url = $this->game_url.$api_uri.'/'.$siteAccess;

             $params = array(
                'url'   => $this->game_url,
                "ptype" => $siteAccess,
                'mchId' => $this->merchant_id,
                'token' => $result['token'],
            );

            // $params['url'] = $url;

            return $params;
        }
    }


    public function syncMergeToGameLogs($token) {
        $this->CI->load->model(array('game_logs', 'player_model', 'finance_game_Logs'));

        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeFrom->modify($this->getDatetimeAdjust());
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = $dateTimeFrom->format('Y-m-d H:i:s');
        $endDate = $dateTimeTo->format('Y-m-d H:i:s');

        $result = $this->CI->finance_game_Logs->getGameLogStatistics($startDate, $endDate);
        $this->CI->utils->debug_log("finance sync gamelogs ===============================================================");
        $count = 0;
        if($result) {
            $unknownGame = $this->getUnknownGame();

            foreach ($result as $data) {
                $count++;

                $game_description_id = $data['game_description_id'];
                $game_type_id = $data['game_type_id'];

                if (empty($game_description_id)) {
                    $game_description_id = $unknownGame->id;
                    $game_type_id = $unknownGame->game_type_id;
                }
                $result_amount = ($data['PayoutAmount'] > 0 ) ? $data['PayoutAmount'] - $data['BetAmount'] : -$data['BetAmount'];
                $extra = array('trans_amount' => $data['BetAmount'],);

                $this->syncGameLogs(
                    $game_type_id,
                    $game_description_id,
                    $data['game_code'],
                    $data['game_type'],
                    $data['game_name'],
                    $data['player_id'],
                    $data['username'],
                    $data['BetAmount'],
                    $result_amount,//$data['PayoutAmount'],
                    null, // win_amount
                    null, // loss_amount
                    null, // after balance
                    0,    // has both side
                    $data['external_uniqueid'],
                    $data['BetTime'], //start
                    $data['EndTime'], // end
                    $data['response_result_id'],
                    Game_logs::FLAG_GAME,
                    $extra
                );
            }
        }

        $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $count);

        return  array('success' => true );
    }


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
        return $this->returnUnimplemented();
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return array(false, null);
    }
}

/*end of file*/