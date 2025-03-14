<?php require_once dirname(__FILE__) . '/abstract_game_api.php';

# winLoss == result_amount

/**
 * Class Game_api_ig
 *
 * Note : unsettled not pulled in api
 */
class Game_api_ig extends Abstract_game_api {

    const SUCCESS_CODE = 0;
    const MAX_RECORD_COUNT = '2000'; // use string
    const LOGIN_PASS_COMMAND = 1;
    const TRANSACTION_COMMAND = 2;
    const GAME_RECORDS_COMMAND = 3;
    const MAXIMUM_USERNAME_LENGTH = 20;
    const DEFAULT_GAME_TYPE = 'LOTTERY';
    const DEFAULT_LOTTERY_PAGE = '2'; # Corresponds to gameInfoId 3, Beijing PK10
    const GAME_TYPES = array('LOTTERY','LOTTO');
    const LOTTO_EXTERNAL_ID = '100'; // lotto unique identifier use in game_info_id
    const MAX_SYNC_LOG_TIME_MINS = 5; # The syncOriginalGameLog process will only keep running for a max of X minutes

    public function __construct() {
        parent::__construct();

        $this->url = $this->getSystemInfo('url');                                        // "http://gbklottery.ppkp88.com/gbkapilottery/app/api.do";
        $this->transfer_api =  $this->getSystemInfo('transfer_api');                     // "http://gbktrade.ppkp88.com/gbkapitrade/app/api.do";
        $this->game_records_api = $this->getSystemInfo('game_records_api');              // "http://gbkrecord.ppkp88.com/gbkapirecord/app/api.do";
        $this->hash_code = $this->getSystemInfo('hash_code');                            // "alebo_74452bc9-9802-4e31-b63f-86d0a8";

        $this->default_game_no_id = $this->getSystemInfo('default_game_no_id', 1);
        $this->sync_sleep_time = $this->getSystemInfo('sync_sleep_time', '5');
        $this->currency = $this->getSystemInfo('currency', 'CNY');
        $this->default_language = $this->getSystemInfo('default_language', 'EN');
        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');

        $this->game_lotto = $this->getSystemInfo('game_lotto');

        $this->sync_lottery_id = $this->getSystemInfo('sync_lottery_id', 1);
        $this->sync_lotto_id = $this->getSystemInfo('sync_lotto_id', 1);

        $this->command = '';
        $this->command_flag = '';
        $this->access_lottery_game = false;

    }

    public function getPlatformCode() {
        return IG_API;
    }

    protected function customHttpCall($ch, $params) {
        $data['hashCode'] = $this->hash_code;
        $data['command']  = $this->command;
        $data['params']   = $params;
        $this->utils->debug_log("IG http params", $data);

        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }

    public function generateUrl($apiName, $params) {
        $url = '';
        switch ($this->command_flag) {
            case self::LOGIN_PASS_COMMAND :
                if($this->access_lottery_game) {
                    $url = $this->url;
                } else {
                    $url=  $this->game_lotto;
                }
                break;
            case self::TRANSACTION_COMMAND :
               $url = $this->transfer_api;
                break;
            case self::GAME_RECORDS_COMMAND :
                $url = $this->game_records_api;
                break;
        }
        return $url;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameName = $this->getGameUsernameByPlayerUsername($playerName);

        if(strlen($gameName) > self::MAXIMUM_USERNAME_LENGTH) {
            $gameName =  $this->prefix_for_username.random_string('alpha', 8);
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'gameName' => $gameName,
            'playerId' => $playerId,
            'sbePlayerName' => $playerName
        );

        $params = array(
            'username' => $gameName,
            'password' => md5($password),
            'currency' => $this->currency,
            'nickname' => $gameName,
            'language' => $this->default_language,
            'gameType' => self::DEFAULT_GAME_TYPE,
            'userCode' => $gameName,
        );

        if($params['gameType'] == self::DEFAULT_GAME_TYPE) {
            $params['lobby'] = 1;
            $params['lotteryTray'] = 'A';
            $params['lotteryType'] = 'PC';
            $params['lotteryPage'] = self::DEFAULT_LOTTERY_PAGE;
            $this->access_lottery_game = true;
        } else {
            $params['line'] = 1;
            $params['lottoTray'] = 'A';
        }

        $this->command = 'LOGIN';
        $this->command_flag = self::LOGIN_PASS_COMMAND;
        $this->access_lottery_game = TRUE;

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){
        $gameName = $this->getVariableFromContext($params, 'gameName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameName);

        $result['response_result_id'] = $responseResultId;
        $result['exists'] = false;

        if($success) {
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result['exists'] = true;
        }
        return array($success, $result);
    }

    public function processResultBoolean($responseResultId, $resultJson, $playerName) {
        if($resultJson['errorCode'] == self::SUCCESS_CODE) {
            $success = true;
        } else {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->error_log("==========IG API GOT ERROR=============",$resultJson['errorMessage'], $playerName);
            $success = false;
        }
        return $success;
    }

    public function changePassword($playerName, $oldPassword, $newPassword) {
        $gameName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForChangePassword',
            'gameName' => $gameName,
            'playerName' => $playerName,
            'password' => $newPassword,
        );

        $params = array(
            'username' => $gameName,
            'password' => md5($newPassword)
        );

        $this->command = 'CHANGE_PASSWORD';
        $this->command_flag = self::LOGIN_PASS_COMMAND;

        return $this->callApi(self::API_changePassword, $params, $context);
    }

    public function processResultForChangePassword($params) {
        $gameName = $this->getVariableFromContext($params, 'gameName');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameName);

        $result = array();
        if ($success) {
            $result["password"] = $this->getVariableFromContext($params, 'password');
            $playerId = $this->getPlayerIdInPlayer($playerName);
            if ($playerId) {
                $this->updatePasswordForPlayer($playerId, $result["password"]);
            } else {
                $this->CI->utils->debug_log('cannot find player', $playerName);
            }
        }

        return array($success, $result);
    }

    public function queryPlayerBalance($playerName) {
        $gameName = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameName' => $gameName,
            'playerName' => $playerName,
        );

        $params = array(
            'username' => $gameName,
            'password' => md5($password),
        );

        $this->command = 'GET_BALANCE';
        $this->command_flag = self::TRANSACTION_COMMAND;

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);

    }

    public function processResultForQueryPlayerBalance($params) {
        $gameName = $this->getVariableFromContext($params, 'gameName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameName);

        $result = [];
        if($success) {
            $result['balance'] = $this->gameAmountToDB($resultJsonArr['params']['balance']);
        }

        return array($success, $result);
    }

    public function gameAmountToDB($amount,$currecyCode=null) {
        $conversion_rate = floatval($this->getSystemInfo('conversion_rate', 1));
        $value = floatval($amount / $conversion_rate);
        return $this->round_down($value,3);
    }

    private function round_down($number, $precision = 2){
        $fig = (int) str_pad('1', $precision, '0');
        return (floor($number * $fig) / $fig);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {

        $gameName = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameName);

        $secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');
        $external_trans_id = $transfer_secure_id ? $transfer_secure_id : $secure_id;

        $this->CI->utils->debug_log('=========Deposit IG player===========', $gameName, 'password',  $password );

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameName' => $gameName,
            'sbeName' => $playerName,
            'external_transaction_id' => $external_trans_id,
            'amount' => $amount,
        );

        $params = array(
            'username' => $gameName,
            'password' => md5($password),
            'ref' => $external_trans_id,
            'desc' => 'Deposit for user '.$playerName.', trans id '.$external_trans_id,
            'amount' => (string)$amount,
        );

        $this->command = 'DEPOSIT';
        $this->command_flag = self::TRANSACTION_COMMAND;

        return $this->callApi(self::API_depositToGame, $params, $context);
    }


    public function processResultForDepositToGame($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $gameName = $this->getVariableFromContext($params, 'gameName');
        $sbeName = $this->getVariableFromContext($params, 'sbeName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameName);
        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if($success) {
            // $afterBalance = $this->gameAmountToDB($resultJsonArr['params']['balance']);
            // $result["current_player_bal"] = $afterBalance;
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameName);
            // if ($playerId) {
            //     $this->insertTransactionToGameLogs($playerId, $gameName, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());
            // } else {
            //     $this->CI->utils->debug_log('error', 'cannot get player id from ' . $sbeName . ' getPlayerIdInGameProviderAuth');
            // }
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['external_transaction_id'] = $resultJsonArr['logId'];
            $result['didnot_insert_game_logs']=true;
        } else {
            $error_code = @$resultJsonArr['errorCode'];
            switch($error_code) {
                case '6001' :   # hashcode error
                    $result['reason_id']=self::REASON_INVALID_KEY;
                    break;
                case '6002' :
                    $result['reason_id']=self::REASON_IP_NOT_AUTHORIZED;
                    break;
                case '6005' :
                case '6609':
                    $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
                    break;
                case '6613' :
                    $result['reason_id']=self::REASON_INVALID_TRANSFER_AMOUNT;
                    break;
                case '6614' :
                    $result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
                    break;
                case '6616' :
                    $result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
                    break;
            }
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
        return array($success, $result);
    }

    // NEED TO RETEST. staging env can't withdraw api
    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {

        $gameName = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameName);

        $secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');
        $external_trans_id = $transfer_secure_id ? $transfer_secure_id : $secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameName' => $gameName,
            'sbeName' => $playerName,
            'external_transaction_id' => $external_trans_id,
            'amount' => $amount,
        );

        $params = array(
            'username' => $gameName,
            'password' => md5($password),
            'ref' => $external_trans_id,
            'desc' => 'With for user '.$playerName.', trans id '.$external_trans_id,
            'amount' => (string)$amount,
        );

        $this->command = 'WITHDRAW';
        $this->command_flag = self::TRANSACTION_COMMAND;

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $gameName = $this->getVariableFromContext($params, 'gameName');
        $sbeName = $this->getVariableFromContext($params, 'sbeName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameName);
        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if($success) {
            // $afterBalance = $this->gameAmountToDB($resultJsonArr['params']['balance']);
            // $result["current_player_bal"] = $afterBalance;
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameName);
            // if ($playerId) {
            //     $this->insertTransactionToGameLogs($playerId, $gameName, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
            // } else {
            //     $this->CI->utils->debug_log('error', 'cannot get player id from ' . $sbeName . ' getPlayerIdInGameProviderAuth');
            // }
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['external_transaction_id'] = $resultJsonArr['logId'];
            $result['didnot_insert_game_logs']=true;
        } else {
            $error_code = @$resultJsonArr['errorCode'];
            switch($error_code) {
                case '6001' :   # hashcode error
                    $result['reason_id']=self::REASON_INVALID_KEY;
                    break;
                case '6002' :
                    $result['reason_id']=self::REASON_IP_NOT_AUTHORIZED;
                    break;
                case '6005' :
                case '6609':
                    $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
                    break;
                case '6613' :
                    $result['reason_id']=self::REASON_INVALID_TRANSFER_AMOUNT;
                    break;
                case '6614' :
                    $result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
                    break;
                case '6616' :
                    $result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
                    break;

            }
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
        return array($success, $result);
    }

    public function queryTransaction($transactionId, $extra) {

        $playerName=$extra['playerName'];
        $playerId=$extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'gameUsername'=>$gameUsername,
            'playerId'=>$playerId,
            'external_transaction_id'=>$transactionId,
        );

        $params = array(
            'ref' => $transactionId,
        );

        $this->command = 'CHECK_REF';
        $this->command_flag = self::TRANSACTION_COMMAND;

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction( $params ){
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);
        $gameName = $this->getVariableFromContext($params, 'gameUsername');

        $success = false;
        if(!empty($resultJsonArr)) {
            // if contain "is exist" means approved.
            if (strpos($resultJsonArr['errorMessage'], 'is exist') !== false) {
                $success = true;
            }
        }

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if($success) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $error_code = @$resultJsonArr['errorCode'];
            switch($error_code) {
                case '6001' :   # hashcode error
                    $result['reason_id']=self::REASON_INVALID_KEY;
                    break;
                case '6002' :
                    $result['reason_id']=self::REASON_IP_NOT_AUTHORIZED;
                    break;
                case '6614' :
                case '0' :
                    $result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
                    break;
            }
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
        return array($success, $result);
    }

    protected function convertStatus($status){

        if(isset($this->status_map[$status])){
            return $this->status_map[$status];
        }else{
            return self::COMMON_TRANSACTION_STATUS_UNKNOWN;
        }

    }


    public function login($playerName, $extra = null) {
        $gameName = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'gameName' => $gameName,
            'sbeName' => $playerName
        );

        $params = array(
            'username' => $gameName,
            'password' => md5($password),
            'currency' => $this->currency,
            'nickname' => $gameName,
            'language' => !empty($extra['language']) ? $this->getGameLanguage($extra['language']) :  $this->default_language,
            'userCode' => $gameName,
            'line'     => 1
        );

        $params['gameType'] =  strtoupper($extra['game_type']);

        if($params['gameType'] == self::DEFAULT_GAME_TYPE) {
            $params['lobby'] = 1;
            $params['lotteryTray'] = 'A';
            $params['lotteryType'] =  !empty($extra['is_mobile'])  ? 'MP' : 'PC';
            $params['lotteryPage'] = self::DEFAULT_LOTTERY_PAGE;
            $this->access_lottery_game = true;
        } else {
            $params['line'] = 1;
            $params['lottoTray'] = 'A';
            $this->access_lottery_game = false;
        }

        $this->command = 'LOGIN';
        $this->command_flag = self::LOGIN_PASS_COMMAND;

        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForLogin($params){
        $sbeName = $this->getVariableFromContext($params, 'sbeName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $resultJsonArr = json_decode($resultText,TRUE);

        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $sbeName);
        $result = array();
        if($success) {
            $result['url'] = $resultJsonArr['params']['link'];
        }

        return array($success, $result);
    }

    public function queryForwardGame($playerName, $extra=null) {
        $result = $this->login($playerName, $extra);
        $url = $result['success'] ? $result['url'] : '';
        return array( 'url' => $url );
    }


    public function getGameLanguage($language) {
        switch ($language) {
            case 1:
                $lang = 'CN';
                break;
            case 2:
                $lang = 'EN';
                break;
            default:
                $lang = 'EN';
                break;
        }
        return $lang;
    }

    public function isPlayerExist($playerName) {
        $gameName = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordByGameUsername($gameName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'gameName' => $gameName,
            'sbeName' => $playerName
        );

        $params = array(
            'username' => $gameName,
            'password' => md5($password),
        );

        $this->command = 'GET_BALANCE';
        $this->command_flag = self::TRANSACTION_COMMAND;

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $sbeName = $this->getVariableFromContext($params, 'sbeName');

        $resultJsonArr = json_decode($resultText,TRUE);

        $success = false;
        if(isset($resultJsonArr['errorCode'])){
            $success = true;
            if($resultJsonArr['errorCode'] == 0){
                $result['exists'] = true;
            }else{
                $result['exists'] = false;
            }
        }else{
            $result['exists'] = null;
        }

        return array($success, $result);
    }


    public function syncOriginalGameLogs($token = false) {

        $syncId = $this->getValueFromSyncInfo($token, 'syncId');

        $this->sync_lottery_id = $this->getSystemInfo('sync_lottery_id', 1);
        $this->sync_lotto_id = $this->getSystemInfo('sync_lotto_id', 1);

        $syncLotteryId = $this->sync_lottery_id;
        $syncLottoId = $this->sync_lotto_id;

        $ignore_public_sync = $this->getValueFromSyncInfo($token, 'ignore_public_sync');

		if ($ignore_public_sync == true) {
			//ignore public sync to avoid API query too fast
			$this->CI->utils->error_log('ignore public sync');
			return array('success' => true);
        }

        $this->syncGameLogsByGameNoId($syncLotteryId, $syncLottoId, $syncId);
        return array('success' => true);
    }

    public function syncGameLogsByGameNoId($syncLotteryId, $syncLottoId, $syncId = null) {
        $this->CI->load->model(array('external_system', 'ig_game_logs', 'player_model'));

        $this->command_flag = self::GAME_RECORDS_COMMAND;
        $apiName = self::API_syncGameRecords;

        $isSyncSuccess = false;
        foreach(self::GAME_TYPES as $types) {

            if ($types == self::DEFAULT_GAME_TYPE) {
                $this->command = 'GET_LOTTERY_RECORD_BY_GAMENO_SEQUENCENO';
                $gameNoId = $syncLotteryId;
            } else {
                $this->command = 'GET_LOTTO_RECORD_BY_GAMENO_SEQUENCENO';
                $gameNoId = $syncLottoId;
            }

            $count = 0;
            $success = true;
            $nextGameId = null;
            $nextReportDate = null;
            $nextBeginId = null;

            $loopCount = 0;
            $maxLoopCount = self::MAX_SYNC_LOG_TIME_MINS * 60 / $this->sync_sleep_time;

            while ($success) {

                $params = array(
                    'gameNoId' => $nextGameId ? (string)$nextGameId : (string)$gameNoId,
                    'beginId' =>  $nextBeginId ? (string)$nextBeginId : '0',
                    'count' => self::MAX_RECORD_COUNT,
                );

                if ($types == self::DEFAULT_GAME_TYPE) {
                    $params['reportDate'] = $nextReportDate ? (string)$nextReportDate : '0';
                }

                list($header, $resultText, $statusCode, $statusText, $errCode, $error, $resultObj) = $this->httpCallApi($this->game_records_api, $params);

                $this->CI->utils->debug_log('IG_API Response', 'resultText', $statusCode, 'statusCode', 'errorCode', $errCode, $error, $resultObj);

                $resultJsonArr = json_decode($resultText,TRUE);

                $extra = $header;
                $responseResultId = $this->saveResponseResult($success, $apiName, $params,
                    $resultText, $statusCode, $statusText, $extra, array('sync_id' => $syncId));

                # hasNewGame: Even if there is no record returned, we still need to take another loop if there is nextGameNoId returned
                $hasNewGame = is_array($resultJsonArr['params']) && !empty($resultJsonArr['params']['nextGameNoId']) &&
                    ($params['gameNoId'] != $resultJsonArr['params']['nextGameNoId']);

                $params = $resultJsonArr['params'];

                $nextGameId = isset($params['nextGameNoId']) ? $params['nextGameNoId'] : $this->default_game_no_id;
                $nextReportDate = isset($params['nextReportDate']) ? $params['nextReportDate'] : '0';
                $nextBeginId = isset($params['nextBeginId']) ? $params['nextBeginId'] : '0';
                $gameInfoId = isset($params['gameInfoId']) ? $params['gameInfoId'] : self::LOTTO_EXTERNAL_ID;

                if(isset($params['recordList']) && !empty($params['recordList'])) {
                    $availableRows = $this->CI->ig_game_logs->getAvailableRows($params['recordList']);

                    $data = [];
                    if (!empty($availableRows)) {
                        foreach ($availableRows as $record) {

                            $playerID = $this->getPlayerIdInGameProviderAuth($record['username']);

                            $data['ig_id'] =  isset($record['id']) ? $record['id'] : null;
                            $data['username'] = isset($record['username']) ? $record['username']:null;
                            $data['tray'] =  isset($record['tray']) ? $record['tray' ]:null;
                            $data['bet_time'] =  $this->gameTimeToServerTime(date("Y-m-d H:i:s", $record['betTime']/1000));
                            $data['result_time'] =  $this->gameTimeToServerTime(date("Y-m-d H:i:s", $record['resultTime']/1000));
                            $data['bet_id'] =  isset($record['betId']) ? $record['betId']:null;
                            $data['bet_on'] =  isset($record['betOn']) ? $record['betOn'] : NULL;
                            $data['bet_type'] = isset($record['betType']) ? $record['betType'] : NULL;
                            $data['bet_details'] =  $record['betDetails'];
                            $data['odds'] =  isset($record['odds']) ? $record['odds' ]:null;
                            $data['stake_amount'] =  isset($record['stakeAmount']) ? $record['stakeAmount']:null;
                            $data['valid_stake'] =  isset($record['validStake']) ? $record['validStake']:null;
                            $data['win_loss'] =  isset($record['winLoss']) ? $record['winLoss']:null;
                            $data['ip'] =  isset($record['ip']) ? $record['ip'] : null;
                            $data['bet_type_id'] =  isset($record['betTypeId']) ? $record['betTypeId']:null;

                            // LOTTO to include : odds2, oddsC, oddsC2, betOnId
                            $data['odds_2'] = isset($record['odds2']) ? $record['odds2'] : 0;
                            $data['odds_c'] = isset($record['oddsC']) ? $record['oddsC'] : 0;
                            $data['odds_c2'] = isset($record['oddsC2']) ? $record['oddsC2'] : 0;
                            $data['bet_on_id'] = isset($record['betOnId']) ? $record['betOnId'] : NULL;
                            $data['game_type'] = 'ig_'. strtolower($types);

                            $data['game_info_id'] = $gameInfoId;

                            //extra info from SBE
                            $data['player_id'] = $playerID ? $playerID : '';
                            $data['external_uniqueid'] = $record['betId'];
                            $data['response_result_id'] = $responseResultId;

                            $this->CI->ig_game_logs->insertGameLogs($data);
                        }
                    }

                    #$this->CI->external_system->setLastSyncId($this->getPlatformCode(), $nextGameId);
                    if ($types == self::DEFAULT_GAME_TYPE) {
                        $extra_info['sync_lottery_id'] = $nextGameId;
                    } else {
                        $extra_info['sync_lotto_id'] = $nextGameId;
                    }
                    $this->updateExternalSystemExtraInfo(IG_API,$extra_info);
                } else {
                    $success = $hasNewGame;
                }

                # Stop further sync if it's looping for too many times
                if($loopCount++ > $maxLoopCount) {
                    $this->utils->debug_log("Current loop count [$loopCount] exceeds max loop count [$maxLoopCount], break out of loop.");
                    break;
                }
                sleep($this->sync_sleep_time);  // to avoid this error [errorMessage] => pull the report time too fast.
            }

            $isSyncSuccess = $success;
        }

        return $isSyncSuccess;
    }


    public function syncMergeToGameLogs($token) {

        $this->CI->load->model(array('game_logs', 'player_model', 'ig_game_logs'));

        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeFrom->modify($this->getDatetimeAdjust());
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = $dateTimeFrom->format('Y-m-d H:i:s');
        $endDate = $dateTimeTo->format('Y-m-d H:i:s');

        $result = $this->CI->ig_game_logs->getGameLogStatistics($startDate, $endDate);

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
                $result_amount = $data['win_loss'];

                $bet_details = $this->processGameBetDetail($data);

                $extra = array(
                    'table'=> $data['ig_id'],
                    'trans_amount' => $data['stake_amount'],
                    'note' =>  $bet_details,
                    'sync_index' => $data['id'],
                );

                $playerId = $data['player_id'];
                if(empty($playerId)) {
                    $playerId = null;
                }

                $this->syncGameLogs(
                    $game_type_id,
                    $game_description_id,
                    $data['game_code'],
                    $data['game_type'],
                    $data['game'],
                    $playerId,
                    $data['username'],
                    $data['stake_amount'],
                    $result_amount,
                    null, // win_amount
                    null, // loss_amount
                    null, // after balance
                    0,    // has both side
                    $data['external_uniqueid'],
                    $data['bet_time'], //start
                    $data['result_time'], // end
                    $data['response_result_id'],
                    Game_logs::FLAG_GAME,
                    $extra
                );
            }
        }
        return  array('success' => true );
    }

    public function processGameBetDetail($data){
        $details = array(
            "Bet ID" => $data['bet_id'],
            "Bet Detail" => $data['bet_details'],
            "Bet On" => $data['bet_on'],
            "Bet Type" => $data['bet_type'],
        );
        return json_encode($details);
    }


    public function queryPlayerInfo($playerName) {
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

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return array(false, null);
    }
}

/*end of file*/