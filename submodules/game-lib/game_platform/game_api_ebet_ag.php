<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';


/**
 * iframe_module/goto_ebet_ag/0     - live
 * iframe_module/goto_ebet_ag/500   - xin slots
 * iframe_module/goto_ebet_ag/6     - hunter fishing
 */
class Game_api_ebet_ag extends Abstract_game_api {

    private $api_url;
    private $channelId;
    private $islive;
    private $tag;
    private $public_key;
    private $private_key;
    private $thirdParty;
    private $currency;

    const TRANSFER_IN = 'IN';
    const TRANSFER_OUT = 'OUT';
    const REAL_ACCOUNT = 1;
    const PLATFORM_TYPES = array('AGIN', 'XIN', 'HUNTER', 'YOPLAY');
    const GAME_TYPES = array('live' =>  0, 'hunter' => 6, 'slots' => 500);
    const SUCCESS_CODE = 200;
    const HAS_ERROR = 'error';
    const DEFAULT_CODE_GAME = 0;
    const DEFAULT_ODD_TYPE = 'A';
    const LAUNCH_MOBILE_MODE = 'Y';
    const LANG_CN = 1;
    const LANG_EN = 3;

    public function __construct() {
        parent::__construct();

        $this->api_url        = $this->getSystemInfo('url');
        $this->game_url       = $this->getSystemInfo('game_url');
        $this->channelId      = $this->getSystemInfo('channelId');
        $this->islive         = $this->getSystemInfo('live');
        $this->thirdParty     = $this->getSystemInfo('thirdParty');
        $this->tag            = $this->getSystemInfo('tag');
        $this->public_key     = $this->getSystemInfo('public_key');
        $this->private_key    = $this->getSystemInfo('private_key');
        $this->currency       = $this->getSystemInfo('currency','CNY');
        $this->cagent         = $this->getSystemInfo('cagent');
        $this->DM             = $this->getSystemInfo('DM');

        $this->rsa = new Crypt_RSA();
        $this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $this->rsa->setHash('md5');

        $this->command = '';
    }



    public function getPlatformCode() {
        return EBET_AG_API;
    }

    public function generateUrl($apiName, $params) {
        $url = $this->api_url;
        return $url;
    }

    public function getHttpHeaders($params){
        return array("Content-Type" => "application/json");
    }

    protected function customHttpCall($ch, $params) {
        $postParams = array(
            "channelId"         => $this->channelId,
            "thirdParty"        => $this->thirdParty,
            "tag"               => $this->tag,
            "action" => array(
                "command"    => $this->command,
                "parameters" => $params
            ),
            "live"              => $this->islive,
            "timestamp"         => time()
        );
        $postParams["signature"] = $this->encrypt($this->channelId.$this->thirdParty.$this->tag.$postParams["timestamp"]);

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postParams,true));
    }

    public function processResultBoolean($responseResultId, $resultArr, $playerName = null, $isQueryBalance = false, $isGameLauncher=false) {
        if ($resultArr['status'] == self::SUCCESS_CODE) {
//            if (is_string($resultArr['result'])) {
			if($isGameLauncher){
                $info = '0'; // should true in game lunch
            } else {
                $resultXml = new SimpleXMLElement($resultArr['result']);
                $response = json_decode(json_encode($resultXml), true);
                $info = $response['@attributes']['info'];
            }
            if ($isQueryBalance) {
                $success = true; /* Array ( [status] => 200 [result] => <?xml version="1.0" encoding="utf-8"?> <result info="407.00" msg=""/> ) */
            } else if ($info != '0') {
                $this->setResponseResultToError($responseResultId);
                $this->CI->utils->debug_log('==========EBET AG API GOT ERROR=============', $resultArr, $playerName);
                $success = false;
            } else {
                $success = true;
            }
        } else {
            $success = false;
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->error_log("==========EBET AG API GOT ERROR=============", $resultArr, $playerName);
        }
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'userName'        => $gameName,
            'sbe_userName'    => $playerName
        );

        $params = array(
            'loginname' 	=> $gameName,
            'password' 	    => $password,
            'actype'        => self::REAL_ACCOUNT
        );

        $this->command = 'checkorcreategameaccount';

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params){
        $playerName = $this->getVariableFromContext($params, 'userName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerName);

        $result = array("player_name" => $playerName);

        return array($success, $result);
    }

    public function queryPlayerBalance($playerName) {
        $gameName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName'	  => $playerName
        );

        $params = array(
            "loginname"     => $gameName,
            "actype"        => self::REAL_ACCOUNT,
        );

        $this->command = 'getbalance';

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params){
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $isQueryBalance = true;

        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName, $isQueryBalance);

        $result = array();
        if ($success) {
            $resultXml = new SimpleXMLElement($resultArr['result']);
            $response = json_decode(json_encode($resultXml), true);
            if (!empty($response)) {
                $result['balance'] = $this->gameAmountToDB($response['@attributes']['info']);
            }
        }
        return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $transaction_id = $this->cagent.random_string('numeric');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'amount' => $amount,
            'transactionId' => $transaction_id
        );

        $params = array(
            'loginname' => $gameUsername,
            'billno' => $transaction_id,
            'actype' => self::REAL_ACCOUNT,
            'type' => self::TRANSFER_IN,
            'credit' => $amount
        );

        $this->command = 'preparetransfercredit';

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $transactionId = $this->getVariableFromContext($params, 'transactionId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);

        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
        $result = array();
        if($success) {
            $result = $this->confirmDeposit($playerName, $amount, $transactionId);
            if($result['success']) {
                $playerBalance = $this->queryPlayerBalance($playerName);
                $afterBalance = $playerBalance['balance'];

                $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
                if ($playerId) {
                    $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());
                } else {
                    $this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
                }
            }
        }
        return array($success, $result);
    }

    public function confirmDeposit($playerName, $amount, $transactionId){
        $gameName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForConfirmDeposit',
            'playerName' => $playerName,
            'gameName' => $gameName,
            'amount' => $amount,
            'transactionId' => $transactionId
        );

        $params = array(
            'loginname' => $gameName,
            'billno' => $transactionId,
            'actype' => self::REAL_ACCOUNT,
            'type' => self::TRANSFER_IN,
            'credit' => $amount,
            'flag' => 1
        );

        $this->command = 'transfercreditconfirm';

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForConfirmDeposit($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);

        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        return  array($success, $resultArr);
    }


    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null){
        $gameName = $this->getGameUsernameByPlayerUsername($playerName);

        $transaction_id = $this->cagent.random_string('numeric');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameName' => $gameName,
            'playerName' => $playerName,
            'amount' => $amount,
            'transactionId' => $transaction_id
        );

        $params = array(
            'loginname' => $gameName,
            'billno' => $transaction_id,
            'actype' => self::REAL_ACCOUNT,
            'type' => self::TRANSFER_OUT,
            'credit' => $amount
        );

        $this->command = 'preparetransfercredit';

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $transactionId = $this->getVariableFromContext($params, 'transactionId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);

        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);
        $result = array();
        if($success) {
            $result = $this->confirmWithdraw($playerName, $amount, $transactionId);
            if($result['success']) {
//                $playerBalance = $this->queryPlayerBalance($playerName);
                $afterBalance =null; // $playerBalance['balance'];

                $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
                if ($playerId) {
                    $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());
                } else {
                    $this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
                }
            }
        }
        return array($success, $result);
    }

    public function confirmWithdraw($playerName, $amount, $transactionId){
        $gameName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForConfirmWithdraw',
            'playerName' => $playerName,
            'gameName' => $gameName,
            'amount' => $amount,
            'transactionId' => $transactionId
        );

        $params = array(
            'loginname' => $gameName,
            'billno' => $transactionId,
            'actype' => self::REAL_ACCOUNT,
            'type' => self::TRANSFER_OUT,
            'credit' => $amount,
            'flag' => 1
        );

        $this->command = 'transfercreditconfirm';

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForConfirmWithdraw($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);

        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName);

        return  array($success, $resultArr);
    }

    public function isPlayerExist($playerName) {
        $gameName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExists',
            'playerName' => $playerName
        );

        $params = array(
            "loginname" => $gameName,
            "actype" => self::REAL_ACCOUNT,
        );

        $this->command = 'getbalance';

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExists($params) {
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);

        $resultXml = new SimpleXMLElement($resultArr['result']);
        $response = json_decode(json_encode($resultXml), true);

        $result = array();
        if ($response['@attributes']['info'] == self::HAS_ERROR ) {
            $success = true;
            $result['exists'] = false;
            $this->setResponseResultToError($responseResultId);
            $this->utils->debug_log('==========EBET AG GOT ERROR IN PLAYER EXIST =================', $response['@attributes']['msg'], $playerName);
        } else {
            $success = false;
            $result['exists'] = true;
        }

        return array($success, $result);
    }

    public function queryForwardGame($playerName, $extra=null) {
        $gameName = $this->getGameUsernameByPlayerUsername($playerName);
        $password = $this->getPasswordString($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'playerName' => $playerName
        );

        $gameType = !empty($extra['game_type']) ? $extra['game_type'] : self::DEFAULT_CODE_GAME;
        $language = !empty($extra['language']) ? $this->getGameLanguage($extra['language']) :  self::LANG_EN;

        $params = array(
            'loginname' => $gameName,
            'actype' => self::REAL_ACCOUNT,
            'gameType' => $gameType, // casino(0)  - slots xin(500) - fishing hunter(6)
            'dm' => $this->DM,
            'sid' => $this->cagent.random_string('numeric'),
            'lang' => $language,
            'oddtype' => self::DEFAULT_ODD_TYPE,
            'cur' => $this->currency,
            'flashid' => '',
            'password' => $password
        );

        if(!empty($extra['is_mobile'])) {
            $params['mh5'] = self::LAUNCH_MOBILE_MODE;
        }

        $this->command = 'getforwardgameurl';

        return $this->callApi(self::API_queryForwardGame, $params, $context);
    }

    public function processResultForQueryForwardGame($params){
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);

        $success = $this->processResultBoolean($responseResultId, $resultArr,$playerName, false, true);

        $result['url'] = !empty($resultArr['result']) ?  $resultArr['result'] : array();

        return array($success, $result);
    }

    public function syncOriginalGameLogs($token = false) {

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $startDate->modify($this->getDatetimeAdjust());

        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

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

        foreach(self::PLATFORM_TYPES as $types) {
            $params = array(
                'startDate' => $startDate,
                'endDate' => $endDate,
                'platformType' => $types,
                'pageNumber' => 1,
                'pageSize' => 1000
            );

            $this->command = 'getrawbethistory';

            $this->callApi(self::API_syncGameRecords, $params, $context);
        }
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model(array('ebet_ag_game_logs', 'player_model'));

        // set no return to process other platform types
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $result = json_decode($resultArr['result']);

        $gameLogs = array();
        if(!empty($result->betHistories)) {
            foreach($result->betHistories as $data) {
                $data = json_decode($data, true);
                array_push($gameLogs, $data);
            }
        }

        if (sizeof($gameLogs) > 0) {
            $data = [];
            foreach($gameLogs as $record) {
                $playerID = $this->getPlayerIdInGameProviderAuth($record['playerName']);

                $billNo = isset($record['billNo']) ? $record['billNo'] : $record['tradeNo'];

                $data['bill_no'] =  $billNo;
                $data['game_type'] = !empty($record['gameType']) ? $record['gameType'] : strtolower($record['platformType']);
                $data['flag'] =   isset($record['flag']) ? $record['flag'] : null;
                $data['agent_code'] = isset($record['agentCode']) ? $record['agentCode'] : null;
                $data['is_from_lost_and_found_folder'] =   isset($record['isFromLostAndFoundFolder']) ? $record['isFromLostAndFoundFolder'] : null;
                $data['before_credit'] = isset($record['beforeCredit']) ? $record['beforeCredit'] : $record['previousAmount'];
                $data['platform_type'] = isset($record['platformType']) ? $record['platformType'] : null;
                $data['remark'] = isset($record['remark']) ? $record['remark'] : null;
                $data['result'] = isset($record['BetAmount']) ? $record['BetAmount'] : null;
                $data['valid_bet_amount'] = isset($record['result']) ? $record['result'] : null;
                $data['third_party'] = isset($record['thirdParty']) ? $record['thirdParty'] : null;
                $data['recalculate_time'] = isset($record['recalcuTime']) ?  $this->formatTime($record['recalcuTime']) : null;
                $data['currency'] = isset($record['currency']) ? $record['currency'] : null;
                $data['tag'] =  isset($record['tag']) ? $record['tag'] : null;
                $data['device_type'] = isset($record['deviceType']) ? $record['deviceType'] : null;
                $data['net_amount'] =   isset($record['netAmount']) ? $record['netAmount'] : $record['transferAmount'];
                $data['player_name'] =   isset($record['playerName']) ? $record['playerName'] : null;
                $data['data_type'] =   isset($record['dataType']) ? $record['dataType'] : null;
                $data['table_code'] =   isset($record['tableCode']) ? $record['tableCode'] : null;
                $data['bet_amount'] =   isset($record['betAmount']) ? $record['betAmount'] : $record['Cost'];
                $data['round'] =   isset($record['round']) ? $record['round'] : null;
                $data['play_type'] =   isset($record['playType']) ? $record['playType'] : null;
                $data['login_ip'] =   isset($record['loginIP']) ? $record['loginIP'] : $record['IP'];
                $data['bet_time'] =   isset($record['betTime']) ? $this->formatTime($record['betTime']) :  $this->formatTime($record['creationTime']);
                $data['game_code'] =   isset($record['gameCode']) ? $record['gameCode'] : null;
                $data['bet_amount_base'] =   isset($record['betAmountBase']) ? $record['betAmountBase'] : null;
                $data['game_category'] =   isset($record['gameCategory']) ? $record['gameCategory'] : null;
                $data['net_amount_bonus'] =   isset($record['netAmountBonus']) ? $record['netAmountBonus'] : null;
                $data['net_amount_base'] =   isset($record['netAmountBase']) ? $record['netAmountBase'] : null;
                $data['bet_amount_bonus'] =   isset($record['betAmountBonus']) ? $record['betAmountBonus'] : null;
                $data['main_bill_no'] =   isset($record['mainbillno']) ? $record['mainbillno'] : null;
                $data['slot_type'] =   isset($record['slottype']) ? $record['slottype'] : null;
                $data['transfer_amount'] =   isset($record['transferAmount']) ? $record['transferAmount'] : null;
                $data['cost'] =   isset($record['Cost']) ? $record['Cost'] : null;
                $data['room_bet'] =   isset($record['Roombet']) ? $record['Roombet'] : null;
                $data['exchange_rate'] =   isset($record['exchangeRate']) ? $record['exchangeRate'] : null;
                $data['earn'] =   isset($record['Earn']) ? $record['Earn'] : null;
                $data['scene_id'] =   isset($record['sceneId']) ? $record['sceneId'] : null;
                $data['hunter_id'] =   isset($record['ID']) ? $record['ID'] : null;
                $data['current_amount'] =   isset($record['currentAmount']) ? $record['currentAmount'] : null;
                $data['room_id'] =   isset($record['Roomid']) ? $record['Roomid'] : null;
                $data['jackpot_comm'] =   isset($record['Jackpotcomm']) ? $record['Jackpotcomm'] : null;
                $data['scene_start_time'] =   isset($record['SceneStartTime']) ? $this->gameTimeToServerTime($record['SceneStartTime']) : null;
                $data['scene_end_time'] =   isset($record['SceneEndTime']) ? $this->gameTimeToServerTime($record['SceneEndTime']) : null;
                $data['previous_amount'] =   isset($record['previousAmount']) ? $record['previousAmount'] : null;
                //extra info from SBE
                $data['player_id'] = $playerID;
                $data['external_uniqueid'] = $billNo;
                $data['response_result_id'] = $responseResultId;

                $this->CI->ebet_ag_game_logs->syncGameLogs($data);
            }
        }
    }

    public function syncMergeToGameLogs($token) {
        $this->CI->load->model(array('game_logs', 'player_model', 'ebet_ag_game_logs'));

        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($dateTimeFrom->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($dateTimeTo->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());

        $startDate=$startDate->format('Y-m-d H:i:s');
        $endDate = $dateTimeTo->format('Y-m-d H:i:s');

        $result = $this->CI->ebet_ag_game_logs->getGameLogStatistics($startDate, $endDate);

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

                $extra = array('trans_amount' => $data['bet_amount'],);

                $this->syncGameLogs(
                    $game_type_id,
                    $game_description_id,
                    $data['game_code'],
                    $data['game_type'],
                    $data['game'],
                    $data['player_id'],
                    $data['player_name'],
                    $data['bet_amount'],
                    $data['result_amount'],
                    null, // win_amount
                    null, // loss_amount
                    null, // after balance
                    0,    // has both side
                    $data['external_uniqueid'],
                    $data['bet_time'], //start
                    $data['bet_time'], // end
                    $data['response_result_id'],
                    Game_logs::FLAG_GAME,
                    $extra
                );
            }
        }

        $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $count);

        return  array('success' => true );
    }

    public function formatTime($date) {
        return $this->gameTimeToServerTime(date("Y-m-d H:i:s", $date/1000));
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return array(false, null);
    }

    public function logout($playerName, $password = null) {
        return $this->returnUnimplemented();
    }

    public function blockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $this->blockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function unblockPlayer($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $this->unblockUsernameInDB($playerName);
        return array("success" => true);
    }

    public function changePassword($playerName, $oldPassword = null, $newPassword) {
        return $this->returnUnimplemented();
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    public function syncPlayerAccount($username, $password, $playerId) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerInfo($playerName) {
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

    public function getGameLanguage($language) {
        switch ($language) {
            case 1:
                $lang = self::LANG_EN;
                break;
            case 2:
                $lang = self::LANG_CN;
                break;
            default:
                $lang = self::LANG_EN;
                break;
        }
        return $lang;
    }

    public function login($playerName, $password = null) {
        return $this->returnUnimplemented();
    }

    function verify($str, $signature) {
        $signature = base64_decode($signature);
        $this->rsa->loadKey($this->public_key);
        return $this->rsa->verify($str, $signature);
    }

    function encrypt($str) {
        $this->rsa->loadKey($this->private_key);
        $signature = $this->rsa->sign($str);
        $signature = base64_encode($signature);
        return $signature;
    }
}

/*end of file*/