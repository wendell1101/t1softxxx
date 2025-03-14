<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';

class Game_api_ebet_mg extends Abstract_game_api {

    private $api_url;
    private $channelId;
    private $live;
    private $tag;
    private $public_key;
    private $private_key;
    private $thirdParty;
    private $currency;
    private $ebetmg_game_logs_model;

    const OPERATION_DEPOSIT = 'topup';
    const OPERATION_WITHDRAW = 'withdraw';

    const MG_LIVEGAME = 1;
    const MG_RNGGAME = 2;

    public function __construct() {
        parent::__construct();

        $this->api_url          = $this->getSystemInfo('api_url');

        $this->channelId        = $this->getSystemInfo('channelId');
        $this->thirdParty       = $this->getSystemInfo('thirdParty');
        $this->tag              = $this->getSystemInfo('tag');
        $this->live             = $this->getSystemInfo('live');

        $this->currency         = $this->getSystemInfo('currency');
        $this->language         = $this->getSystemInfo('language');
        $this->casinoIsEnabled  = $this->getSystemInfo('casinoIsEnabled');
        $this->product          = $this->getSystemInfo('product');

        $this->public_key       = $this->getSystemInfo('public_key');
        $this->private_key      = $this->getSystemInfo('private_key');
        $this->page_size        = $this->getSystemInfo('page_size', 1000);
        $this->timeOut        = $this->getSystemInfo('timeOut', 30); //seconds

        # init RSA
        $this->rsa = new Crypt_RSA();
        $this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $this->rsa->setHash('md5');

        $this->CI->load->model('ebetmg_game_logs');
        $this->ebetmg_game_logs_model = $this->CI->ebetmg_game_logs;
    }

    public function getPlatformCode() {
        return EBET_MG_API;
    }

    public function generateUrl($apiName, $params) {
        $url = $this->api_url;
        return $url;
    }

    public function getHttpHeaders($params){
        return array(
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        );
    }

    protected function customHttpCall($ch, $params) {

        $command = $params['command'];

        unset($params['command']);

        $postParams = array(
            'channelId' => $this->channelId,
            'thirdParty' => $this->thirdParty,
            'tag' => $this->tag,
            'action' => array(
                'command' => $command,
                'parameters' => $params
            ),
            'live' => $this->live,
            'timestamp' => time()
        );

        $postParams['signature'] = $this->encrypt($this->channelId . $this->thirdParty . $this->tag . $postParams['timestamp']);

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postParams,TRUE));
    }

    function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = NULL, $extra = NULL, $resultObj = NULL) {
        return array(FALSE, NULL);
    }

    function processResultBoolean($responseResultId, $resultJsonArr, $playerName = NULL) {
        $success = false;
        if(isset($resultJsonArr['status']) && $resultJsonArr['status'] == 200){
            $result_array = json_decode($resultJsonArr['result'],true);
            if($result_array['Status']['ErrorCode']==0){
                $success = true;
            }
        }

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            if($result_array['Status']['ErrorCode']!=46){ # skip error log
                $this->CI->utils->error_log('EBET MG got error', $responseResultId, 'playerName', $playerName, 'result', $resultJsonArr);
            }else{
                $this->CI->utils->debug_log('EBET MG got error', $responseResultId, 'playerName', $playerName, 'result', $resultJsonArr);
            }
        }

        return $success;
    }

    function callback($result = NULL, $platform = 'web') {
        if($platform == 'web'){
            $playerId = $this->getPlayerIdByExternalAccountId($result['userId']);
            if(!empty($playerId)){
                $status = array(
                    "status" => 200,
                );
            }else{
                $status = array(
                    "status" => 0,
                    "message"=> "User not found."
                );
            }
            return $status;
        }
    }

    function createPlayer($playerUsername, $playerId, $password, $email = NULL, $extra = NULL) {
        parent::createPlayer($playerUsername, $playerId, $password, $email, $extra);

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $gamePassword = $this->getPasswordString($playerUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerId' => $playerId,
            'playerUsername' => $playerUsername,
            'gameUsername' => $gameUsername,
        );

        $params = array(
            'command'=> 'addplayeraccount',
            'FirstName' => $gameUsername,
            'LastName' => $gameUsername,
            'PreferredAccountNumber' => $gameUsername,
            'PinCode' => $gamePassword,
            'isProgressive' => true,
            // 'BettingProfiles' => #dont know about this
        );

        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    function processResultForCreatePlayer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerUsername = $this->getVariableFromContext($params, 'playerUsername');
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $resultArr = json_decode($resultJsonArr['result'], TRUE);
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerUsername);
        if ($success) {
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }
        return array($success, $resultArr);
    }


    function changePassword($playerUsername, $oldPassword = NULL, $newPassword) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForChangePassword',
            'playerUsername' => $playerUsername,
            'gameUsername' => $gameUsername,
            'newPassword' => $newPassword,
        );

        $params = array(
            'command'=> 'editaccount',
            'AccountNumber' => $gameUsername,
            'PinCode' => $newPassword,
        );

        return $this->callApi(self::API_changePassword, $params, $context);
    }

    function processResultForChangePassword($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerUsername = $this->getVariableFromContext($params, 'playerUsername');
        $newPassword = $this->getVariableFromContext($params, 'newPassword');
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $resultArr = json_decode($resultJsonArr['result'], TRUE);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerUsername);

        if($success){
            $playerId = $this->getPlayerIdInPlayer($playerUsername);
            //sync password to game_provider_auth
            $this->updatePasswordForPlayer($playerId, $newPassword);
        }

        return array($success, $resultArr);
    }

    function blockPlayer($playerUsername) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForBlockPlayer',
            'playerUsername' => $playerUsername,
            'gameUsername' => $gameUsername,
        );

        $params = array(
            'command' => 'lockaccounts',
            'Accounts' => $gameUsername,
            'isLock' => true
        );

        return $this->callApi(self::API_blockPlayer, $params, $context);
    }

    function processResultForBlockPlayer($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerUsername = $this->getVariableFromContext($params, 'playerUsername');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $resultArr = json_decode($resultJsonArr['result'], TRUE);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerUsername);

        if ($success) {
            $this->blockUsernameInDB($gameUsername);
        }

        return array($success, $resultArr);
    }

    function unblockPlayer($playerUsername) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForUnblockPlayer',
            'playerUsername' => $playerUsername,
            'gameUsername' => $gameUsername,
        );

        $params = array(
            'command' => 'lockaccounts',
            'Accounts' => $gameUsername,
            'isLock' => false
        );

        return $this->callApi(self::API_unblockPlayer, $params, $context);
    }

    function processResultForUnblockPlayer($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerUsername = $this->getVariableFromContext($params, 'playerUsername');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $resultArr = json_decode($resultJsonArr['result'], TRUE);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerUsername);

        if ($success) {
            $this->unblockUsernameInDB($gameUsername);
        }

        return array($success, $resultArr);
    }

    function queryPlayerBalance($playerUsername) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerUsername' => $playerUsername,
            'gameUsername' => $gameUsername,
        );

        $params = array(
            'command' => 'getaccountbalance',
            'Accounts' => $gameUsername
        );

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    function processResultForQueryPlayerBalance($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerUsername = $this->getVariableFromContext($params, 'playerUsername');
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $resultArr = json_decode($resultJsonArr['result'], TRUE);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerUsername);
        $result = array('balance' => 0);

        if ($success && isset($resultArr['Result'][0]['CreditBalance'])) {
            $result['balance'] = floatval($resultArr['Result'][0]['CreditBalance']);
        }

        return array($success, $result);
    }

    function batchQueryPlayerBalance($playerNames, $syncId = null) {
        $this->CI->load->model(array('game_provider_auth', 'player_model'));
        $success = false;
        $result = array();

        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        } else {
            //convert to game username
            $newPlayerNames = array();
            foreach ($playerNames as $key => $username) {
                array_push($newPlayerNames,$this->getGameUsernameByPlayerUsername($username));
            }
            $playerNames = $newPlayerNames;
        }

        if(empty($playerNames)){
            $playerNames = array();
        }

        $names = array_chunk($playerNames, 500);

        foreach ($names as $nameArr) {
            if (!empty($nameArr)) {
                $context = array(
                    'callback_obj' => $this,
                    'callback_method' => 'processResultForBatchQueryPlayerBalance',
                    'gameUsernames' =>  implode(',', $nameArr),
                );

                $params = array(
                    'command' => 'getaccountbalance',
                    'Accounts' => implode(',', $nameArr)
                );

                $rlt = $this->callApi(self::API_queryPlayerBalance, $params, $context);
                if ($rlt && $rlt['success']) {
                    $result = $rlt;
                }
            }
        }

        return $this->returnResult($success, "balances", $result);
    }

    function processResultForBatchQueryPlayerBalance($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $gameUsernames = $this->getVariableFromContext($params, 'gameUsernames');
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $resultArr = json_decode($resultJsonArr['result'], TRUE);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr);
        $result = array();
        if($success){
            if(isset($resultArr['Result'])&&!empty($resultArr['Result'])){
                foreach($resultArr['Result'] as $key => $player){

                    $playerId = $this->getPlayerIdInGameProviderAuth($player['AccountNumber']);
                    $bal = floatval($player['AccountNumber']);
                    $result["balances"][$playerId] = $bal;
//                    $this->updatePlayerSubwalletBalance($playerId, $bal);
                }
            }
        }

        return array($success, $result);
    }

    function isPlayerExist($playerUsername) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExists',
            'sbe_playerName' => $playerUsername,
            'gameUsername' => $gameUsername,
        );

        $params = array(
            'command' => 'getaccountbalance',
            'Accounts' => $gameUsername,
        );

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    function processResultForIsPlayerExists($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $playerUsername = $this->getVariableFromContext($params, 'playerUsername');
        $sbe_playerName = $this->getVariableFromContext($params, 'sbe_playerName');
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $resultArr = json_decode($resultJsonArr['result'], TRUE);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerUsername);

        if($success){
            $success = true;
            $result = array(
                'exists' => isset($resultArr['Result'][0]['CreditBalance'])
            );
            #update register flag
            $playerId = $this->getPlayerIdInPlayer($sbe_playerName);
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }else{
            $success = false;
            $result = array('exists' => false);
        }

        return array($success, $result);
    }

    function depositToGame($playerUsername, $amount, $transfer_secure_id = NULL) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $gamePassword = $this->getPasswordString($playerUsername);

        $transaction_id = $this->tag . '_' . md5($gameUsername . time());

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerUsername' => $playerUsername,
            'gameUsername' => $gameUsername,
            'amount' => $amount,
            'transaction_id' => $transaction_id,
        );

        $params = array(
            'command' => 'deposit',
            'AccountNumber' => $gameUsername,
            'Amount' => $amount,
            'TransactionReferenceNumber' => $transaction_id,
            'IdempotencyId' => $this->generateGUID(),
        );

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    private function generateGUID(){
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    function processResultForDepositToGame($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $playerUsername = $this->getVariableFromContext($params, 'playerUsername');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $amount = $this->getVariableFromContext($params, 'amount');
        $transaction_id = $this->getVariableFromContext($params, 'transaction_id');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerUsername);
        $resultArr = json_decode($resultJsonArr['result'], TRUE);
        $result = [];

        if ($success && (isset($resultArr['Result']['isSucceed'])&&$resultArr['Result']['isSucceed']==true)) {

            $result['external_transaction_id'] = $transaction_id;
            $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

            if ($playerId) {

                $playerBalance = $this->queryPlayerBalance($playerUsername);

                $afterBalance = @$playerBalance['balance'];

                if( ! empty($afterBalance)) {
                    $result['currentplayerbalance'] = $afterBalance;
                }

                $this->insertTransactionToGameLogs($playerId, $playerUsername, $afterBalance, $amount, $responseResultId, $this->transTypeMainWalletToSubWallet());

                $result['userNotFound'] = FALSE;
            } else {
                $result['userNotFound'] = TRUE;
            }
        }

        return array($success, $result);
    }

    function withdrawFromGame($playerUsername, $amount, $transfer_secure_id = NULL) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $gamePassword = $this->getPasswordString($playerUsername);

        $transaction_id = $this->tag . '_' . md5($gameUsername . time());

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'playerUsername' => $playerUsername,
            'gameUsername' => $gameUsername,
            'amount' => $amount,
            'transaction_id' => $transaction_id,
        );

        $params = array(
            'command' => 'withdrawal',
            'AccountNumber' => $gameUsername,
            'Amount' => $amount,
            'TransactionReferenceNumber' => $transaction_id,
            'IdempotencyId' => $this->generateGUID()
        );

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    function processResultForWithdrawFromGame($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $playerUsername = $this->getVariableFromContext($params, 'playerUsername');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $amount = $this->getVariableFromContext($params, 'amount');
        $transaction_id = $this->getVariableFromContext($params, 'transaction_id');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $playerUsername);
        $resultArr = json_decode($resultJsonArr['result'], TRUE);
        $result = [];

        if ($success && (isset($resultArr['Result']['isSucceed'])&&$resultArr['Result']['isSucceed']==true)) {

            $result['external_transaction_id'] = $transaction_id;

            $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

            if ($playerId) {

                $playerBalance = $this->queryPlayerBalance($playerUsername);

                $afterBalance = @$playerBalance['balance'];

                if( ! empty($afterBalance)) {
                    $result['currentplayerbalance'] = $afterBalance;
                }

                $this->insertTransactionToGameLogs($playerId, $playerUsername, $afterBalance, $amount, $responseResultId, $this->transTypeSubWalletToMainWallet());

                $result['userNotFound'] = FALSE;
            } else {
                $result['userNotFound'] = TRUE;
            }
        }

        return array($success, $result);
    }

    public function getLauncherLanguage($language,$is_mobile){
        $lang='';
        switch ($language) {
            case 1:
            case 'en-us':
                    $lang = 'en'; # mobile and flash same code
                break;
            case 2:
            case 'zh-cn':
                if($is_mobile){
                    $lang = 'zh-cn'; // chinese
                }else{
                    $lang = 'zh';
                }
                break;
            case 'ko-kr':
                $lang = 'ko-kr'; // korean
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

    function queryForwardGame($playerUsername, $extra = NULL) {
        $this->CI->load->model(array('game_provider_auth'));
        $player_id = $this->getPlayerIdInPlayer($playerUsername);
        $game_type =  $extra['game_type'] = "_null"?($extra['game_code']=="_mglivecasino"?(self::MG_LIVEGAME):(self::MG_RNGGAME)):$extra['game_type'];
        $game_code = $extra['game_code'];
        $game_mode = $extra['game_mode'];
        $is_mobile = $extra['is_mobile'];
        $category = $extra['category'];
        $language = $this->getLauncherLanguage($extra['language'],$is_mobile);
        $params = array();
        $loginInfo = $this->CI->game_provider_auth->getOrCreateLoginInfoByPlayerId($player_id, $this->getPlatformCode());

        if($is_mobile){
            if ($game_type == self::MG_LIVEGAME) {
                $url = $this->getSystemInfo('ebet_mg_mobile_live_game_url');
                //live launcher
                $params = $this->getSystemInfo('ebet_mg_mobile_live_game_params');
                $params['LoginName']= $loginInfo->login_name;
                $params['Password']= $loginInfo->password;
                // $params['BetProfileID'] = $this->getSystemInfo('mg_betting_profile_id_for_add_account');
                $params['UL'] = $language;

                $urlForm=['post'=>true, 'url'=>$url, 'params'=> $params];
                list($form_html, $form_id)=$this->createHtmlForm($urlForm);
                $result['form_html']=$form_html;
                $result['form_id']=$form_id;

            } else {
                $url = $this->getSystemInfo('ebet_mg_mobile_rng_game_url');
                //slots
                $params = $this->getSystemInfo('ebet_mg_mobile_rng_game_params');
                $params['lobbyURL']= $this->CI->utils->getSystemUrl('player');
                $params['bankingURL']= $this->CI->utils->getSystemUrl('player');
                $params['username']= $loginInfo->login_name;
                $params['password']= $loginInfo->password;
                if($game_mode == 'fun'||$game_mode == 'demo'||$game_mode == 'trial'){
                    $params['username'] = 'demo';
                    $params['password'] = 'demo';
                }

                foreach ($params as $key => $value) {
                    $url .= $key.'='.$value.'&';
                }

                $url=rtrim($url, '&');

                $params=[];

                //replace game coe and lang
                $url=str_replace('{lang}', $language, $url);
                $url=str_replace('{game_code}', $game_code, $url);

                $urlForm=['post'=>true, 'url'=>$url, 'params'=> $params];
                list($form_html, $form_id)=$this->createHtmlForm($urlForm);
                $result['form_html']=$form_html;
                $result['form_id']=$form_id;
            }
        }else{
            if ($game_type == self::MG_LIVEGAME) {
                //live launcher
                //load info from config or api
                $params = $this->getSystemInfo('ebet_mg_live_params');
                // $params['BetProfileID'] = $this->getSystemInfo('mg_betting_profile_id_for_add_account');
                $params['LoginName'] = $loginInfo->login_name;
                $params['Password'] = $loginInfo->password;
                $params['UL'] = $language;
                $url = $this->getSystemInfo('ebet_mg_live_game_url_prefix'); //'https://livegames.gameassists.co.uk/ETILandingPage/?';
            } else {
                //slots
                $params = $this->getSystemInfo('ebet_mg_rng_params');

                $params["ul"] = $language;
                $params["gameid"] = $game_code;
                $params['sEXT1'] = $loginInfo->login_name;
                $params['sEXT2'] = $loginInfo->password;
                if($game_mode == 'fun'||$game_mode == 'demo'||$game_mode == 'trial'){
                    $params['sEXT1'] = 'demo';
                    $params['sEXT2'] = 'demo';
                }

                if($category == 'h5'){
                    $params = $this->getSystemInfo('ebet_mg_desktop_html5_rng_game_params');
                    $params['username'] = $loginInfo->login_name;
                    $params['password'] = $loginInfo->password;

                    # fun game
                    if($game_mode == 'fun'||$game_mode == 'demo'||$game_mode == 'trial'){
                        $params['username'] = 'demo';
                        $params['password'] = 'demo';
                        $params['ispracticeplay'] = 'true';
                    }

                    $url = $this->getSystemInfo('ebet_mg_desktop_html5_rng_game_url'); // 'https://igaminga.gameassists.co.uk/aurora/?';
                    $language = $this->getLauncherLanguage($extra['language'],true);
                    $url = str_replace('{game_code}', $game_code, $url);
                    $url = str_replace('{lang}', $language, $url);
                }else{
                    $url = $this->getSystemInfo('ebet_mg_rng_game_url_prefix'); // 'https://igaminga.gameassists.co.uk/aurora/?';
                }
            }

        }

        $qry = http_build_query($params);
        $url .= $qry;

        return array("success" => true, "url" => $url);
    }

    public function getGameTimeToServerTime() {
        return '+8 hours';
    }

    // public function getServerTimeToGameTime() {
    //     return '-8 hours';
    // }
    protected function getTimeoutSecond() {
        return $this->timeOut;
    }

    protected function getConnectTimeout() {
        return $this->timeOut;
    }

    function syncOriginalGameLogs($token = FALSE) {

        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
        );

        $params = array(
            'command' => 'getrawbethistory',
            'startDate' => $startDate->format('Y-m-d H:i:s'),
            'endDate' => $endDate->format('Y-m-d H:i:s'),
            'pageSize' => $this->page_size,
            'pageNumber' => 1,
        );

        $retry_count = 0;
        $max_retry = 10;
        do {

            $result = $this->callApi(self::API_syncGameRecords, $params, $context);

            if ( !$result['success'] ) {
                if($retry_count >= $max_retry) {
                    $this->utils->debug_log("Failed API call, params: ", $params, "Retry Count: ", $retry_count, "Max retries reached, terminate current sync.");
                    return $result;
                } else {
                    $this->utils->debug_log("Failed API call, params: ", $params, "Retry Count: ", $retry_count++);
                    # While loop will re-run the failed page as !$result['success'] is true
                }
            }

        } while (!$result['success'] || ($params['pageNumber']++ * $this->page_size) < $result['totalCount']);

        return $result;
    }

    function processResultForSyncGameRecords($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $resultArr = json_decode($resultJsonArr['result'], TRUE);
        $this->CI->load->model(array('ebetmg_game_logs'));
        $result=['totalCount'=> @$resultArr['totalCount'], 'totalBetAmount'=> @$resultArr['totalBetAmount'],
            'totalWinAmount'=> @$resultArr['totalWinAmount']];
        $success=true;
        $dataCount = 0;

        if ($resultArr && isset($resultArr['betHistories']) && ! empty($resultArr['betHistories'])) {
            $responseResultId = $this->getResponseResultIdFromParams($params);
            $gameRecords = $resultArr['betHistories'];
            $availableRows = $this->CI->ebetmg_game_logs->getAvailableRows($gameRecords);

            foreach ($availableRows as $row) {
                $insertRecord = array();

                $insertRecord['row_id'] = $row['rowId'];
                $insertRecord['account_number'] = $row['accountNumber'];
                $insertRecord['display_name'] = $row['displayName'];
                $insertRecord['display_game_category'] = $row['displayGameCategory'];
                $insertRecord['session_id'] = $row['sessionId'];
                $insertRecord['game_end_time'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s',($row['gameEndTime']/1000)));
                $insertRecord['total_wager'] = $this->gamelogConvertGameAmountToDB($row['totalWager']);
                $insertRecord['total_payout'] = $this->gamelogConvertGameAmountToDB($row['totalPayout']);
                $insertRecord['progressive_wage'] = $this->gamelogConvertGameAmountToDB($row['progressiveWage']);
                $insertRecord['iso_code'] = $row['isoCode'];
                $insertRecord['game_platform'] = $row['gamePlatform'];
                $insertRecord['module_id'] = $row['moduleId'];
                $insertRecord['client_id'] = $row['clientId'];
                $insertRecord['transaction_id'] = $row['transactionId'];
                $insertRecord['pca'] = $row['pca'];
                $insertRecord['tag'] = $row['tag'];
                $insertRecord['third_party'] = $row['thirdParty'];
                #additional info
                $insertRecord['uniqueid'] = $row['rowId'];
                $insertRecord['external_uniqueid'] = $row['rowId'];
                $insertRecord['response_result_id'] = $responseResultId;

                $this->CI->ebetmg_game_logs->insertGameLogs($insertRecord);
                $dataCount++;
            }

        }

        $result['data_count'] = $dataCount;

        return array($success, $result);
    }

    public function syncMergeToGameLogs($token) {
        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');
        $dateTimeFrom->modify($this->getDatetimeAdjust());

        $this->CI->utils->debug_log('dateTimeFrom', $dateTimeFrom, 'dateTimeTo', $dateTimeTo);

        $rlt = array('success' => true);

        $result = $this->getGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));
        $cnt = 0;
        if ($result) {

            $this->CI->load->model(array('game_logs', 'game_description_model'));
            $unknownGame = $this->getUnknownGame();

            foreach ($result as $key) {
                $gameLogs = array();
                $cnt++;

                list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($key, $unknownGame);
                //search game name
                $username = strtolower($key->playername);
                $real_bet = $key->bet_amount;
                $gameDate = new DateTime($key->game_end_time);
                $gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);
                $extra = array('table' => $key->external_uniqueid, 'trans_amount'=> $real_bet); //add round

                $this->syncGameLogs(
                    $game_type_id,
                    $game_description_id,
                    $key->game,
                    $key->game_type,
                    $key->game,
                    $key->player_id,
                    $username,
                    $key->bet_amount,
                    $key->result_amount,
                    null,
                    null,
                    0,
                    0,
                    $key->external_uniqueid,
                    $gameDateStr,
                    $gameDateStr,
                    $key->response_result_id,
                    1,
                    $extra
                );

            }
        }

        $this->CI->utils->debug_log('syncMergeToGameLogs monitor', 'count', $cnt);
        return $rlt;
    }


    private function getGameDescriptionInfo($row, $unknownGame) {
        $externalGameId = $row->game;
        $extra = array('game_code' => $row->module_id . '_' . $row->client_id,
            'moduleid' => $row->module_id, 'clientid' => $row->client_id);
        return $this->processUnknownGame(
            $row->game_description_id, $row->game_type_id,
            $row->game, $row->game_type, $externalGameId, $extra,
            $unknownGame);
    }

    private function getGameLogStatistics($dateTimeFrom, $dateTimeTo) {
        $this->CI->load->model('ebetmg_game_logs');
        return $this->CI->ebetmg_game_logs->getGameLogStatistics($dateTimeFrom, $dateTimeTo);
    }

    private $conversion_rate = 100;

    public function gamelogConvertDBAmountToGame($amount) {
        return round($amount * $this->conversion_rate);
    }

    public function gamelogConvertGameAmountToDB($amount) {
        //only need 2
        return round(floatval(floatval($amount) / $this->conversion_rate), 2);
    }

    function login($userName, $password = NULL) {
        return $this->queryForwardGame($userName, $password);
    }

    function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }

    function syncPlayerAccount($username, $password, $playerId) {
        return $this->returnUnimplemented();
    }

    function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    function logout($playerName, $password = NULL) {
        return $this->returnUnimplemented();
    }

    function updatePlayerInfo($playerName, $infos) {
        return $this->returnUnimplemented();
    }

    function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = NULL, $dateTo = NULL) {
        return $this->returnUnimplemented();
    }

    function queryGameRecords($dateFrom, $dateTo, $playerName = NULL) {
        return $this->returnUnimplemented();
    }

    function checkLoginStatus($playerName) {
        return $this->returnUnimplemented();
    }

    # HELPER ########################################################################################################################################

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

    # HELPER ########################################################################################################################################

}

/*end of file*/
