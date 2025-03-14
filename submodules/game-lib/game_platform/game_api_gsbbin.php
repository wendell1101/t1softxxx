<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';


class Game_api_gsbbin extends Abstract_game_api {

    private $bbin_mywebsite;
    private $bbin_create_member;
    private $bbin_login_member;
    private $bbin_logout_member;
    private $bbin_check_member_balance;
    private $bbin_getbet;
    private $bbin_uppername;
    private $url_default;
    private $url_login;

    private  $enable_mobile_api;

    const URI_MAP = array(
        self::API_createPlayer => 'CreateMember',
        self::API_login => 'Login',
        self::API_logout => 'Logout',
        self::API_queryPlayerBalance => 'CheckUsrBalance',
        self::API_isPlayerExist => 'CheckUsrBalance',
        self::API_depositToGame => 'Transfer',
        self::API_withdrawFromGame => 'Transfer',
        self::API_syncGameRecords => 'BetRecord',
        self::API_batchQueryPlayerBalance => 'CheckUsrBalance',
        self::API_changePassword => 'ChangeUserPwd',
        'getFishingRecord' => 'WagersRecordBy30',
        'getFishingRecord2' => 'WagersRecordBy38',
    );

    const START_PAGE = 0;
    const ITEM_PER_PAGE = 500;
    const API_BUSY = 44003;
    const SYSTEM_MAINTENANCE = 44444;

    const PASSWORD_TEXT = 0;
    const PASSWORD_MD5 = 1;

    const DEFAULT_LOTTERY_KINDS=[
        'LT', 'BBLT', 'BBRB', 'BB3D', 'BJ3D','PL3D',
        'SH3D', 'BBGE', 'LDDR', 'LDRS', 'BBLM',
        'LKPA', 'BCRA', 'BCRB', 'BCRC', 'BCRD', 'BCRE',
        'BJPK', 'BBPK', 'RDPK', 'GDE5', 'JXE5', 'SDE5',
        'CQSC', 'XJSC', 'TJSC', 'JLQ3', 'AHQ3', 'BBQK',
        'BBKN', 'CAKN', 'BJKN', 'CQSF', 'TJSF', 'GXSF',
        'CQWC', 'OTHER','BBHL','BBQL','LK28'];

    const BBIN_GAME_PROPERTY = array(
        'bb_sports' => array('game_kind' => 1, 'lose_type' => 'L', 'game_type_name' => 'bb_sports', 'game_type_id' => 33),
        'lottery' => array('game_kind' => 12, 'lose_type' => 'L', 'game_type_name' => 'lottery', 'game_type_id' => 34),
        '3d_hall' => array('game_kind' => 15, 'lose_type' => '200', 'game_type_name' => '3d_hall', 'game_type_id' => 35),
        'live' => array('game_kind' => 3, 'lose_type' => '200', 'game_type_name' => 'live', 'game_type_id' => 36),
        'casino' => array('game_kind' => 5, 'lose_type' => '200', 'game_type_name' => 'casino', 'game_type_id' => 37)
    );

    const BBIN_GAMETYPE = array('33' => 'ball', '34' => 'Ltlottery', '35' => '3DHall', '36' => 'live', '37' => 'game',
        'fish' => 'fisharea', 'fish_hunter' => 1, 'fishing_master' => 2,'fishevent' => 'fishevent');

    const FISHING_GAME = array('fish_hunter' => 'WagersRecordBy30', 'fishing_master' => 'WagersRecordBy38');

    public function __construct() {
        parent::__construct();

        $this->bbin_mywebsite = $this->getSystemInfo('bbin_mywebsite');
        $this->bbin_uppername = $this->getSystemInfo('bbin_uppername');
        $this->url_default = $this->getSystemInfo('url') . '/app/WebService/JSON/display.php';
        $this->url_login = $this->getSystemInfo('bbin_login_api_url') . '/app/WebService/JSON/display.php';

        $this->bbin_transaction_url = $this->getSystemInfo('bbin_transaction_url');
        $this->bbin_private_key = $this->getSystemInfo('bbin_private_key');

        $this->prefix_for_username = $this->getSystemInfo('prefix_for_username');

        $this->bbin_create_member = $this->getSystemInfo('bbin_create_member');
        $this->bbin_login_member = $this->getSystemInfo('bbin_login_member');
        $this->bbin_logout_member = $this->getSystemInfo('bbin_logout_member');
        $this->bbin_check_member_balance = $this->getSystemInfo('bbin_check_member_balance');
        $this->bbin_getbet = $this->getSystemInfo('bbin_getbet');

        $this->keys = array(
            'bbin_create_member' => $this->bbin_create_member,
            'bbin_login_member' => $this->bbin_login_member,
            'bbin_logout_member' => $this->bbin_logout_member,
            'bbin_check_member_balance' => $this->bbin_check_member_balance,
            'bbin_getbet' => $this->bbin_getbet,
        );

        $this->enable_pulling_fishing_record = $this->getSystemInfo('bbin_uppername', false);

        $this->lottery_kinds=$this->getSystemInfo('lottery_kinds', self::DEFAULT_LOTTERY_KINDS);

        $this->enable_mobile_api = !empty($this->getSystemInfo('enable_mobile_api')) && $this->getSystemInfo('enable_mobile_api') ? true : false;

        $this->add_password_when_create_player = $this->getSystemInfo('add_password_when_create_player', true);
        $this->enabled_change_password = $this->getSystemInfo('enabled_change_password', false);
        $this->ignore_password_when_login=$this->getSystemInfo('ignore_password_when_login', true);

        $this->activate_login = false;
        $this->activate_new_api = false;
        $this->new_bbin_uri = '';
    }

    public function getPlatformCode() {
        return GSBBIN_API;
    }

    public function generateUrl($apiName, $params) {

        $apiUri = self::URI_MAP[$apiName];

		$params_string = http_build_query($params);

        if($this->activate_login) {
            $url = $this->url_login . "/" . $apiUri . "?" . $params_string;
        } else if ($this->activate_new_api) {
            $url = $this->bbin_transaction_url."/".$this->new_bbin_uri."?". $params_string ;
        } else {
            $url = $this->url_default . "/" . $apiUri . "?" . $params_string;
        }

        return $url;
	}

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {

        return array(false, null);

    }

    public function processResultBoolean($responseResultId, $resultJson, $playerName = null) {
        $success = !empty($resultJson) && $resultJson['result'];

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('GS BBIN got error', $responseResultId, 'playerName', $playerName, 'result', $resultJson);
        }

        return $success;
    }

    private function formatYMD($dateTimeStr) {
        $d = new Datetime($dateTimeStr);
        return $d->format('Ymd');
    }

    private function getYmdForKey() {
        return $this->formatYMD($this->serverTimeToGameTime(new DateTime()));
    }

    private function getStartKey($key_var) {
        return strtolower(random_string('alpha', $this->keys[$key_var]['start_key_len']));
    }

    private function getEndKey($key_var) {
        return strtolower(random_string('alpha', $this->keys[$key_var]['end_key_len']));
    }

    public function createMobilePlayer($playerName, $playerId, $password, $email = null, $extra = null){

        $playerWithoutPrefix=$playerName;
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreateMobilePlayer',
            'playerWithoutPrefix' => $playerWithoutPrefix,
            'playerName' => $playerName,
            'playerId' => $playerId,
        );

        $params =  array(
            "op" => $this->prefix_for_username,
            "member_id" => $playerName,
            "username" => $playerName,
            "password" => $password,
            "actype" => self::PASSWORD_TEXT,
        );

        $url  = '/api/CreateMobileLogin?'.http_build_query($params);

        $params['auth'] = strtoupper(md5($this->bbin_private_key . $url, false));

        $this->activate_new_api = true;
        $this->new_bbin_uri = 'CreateMobileLogin';

        return $this->callApi(self::API_createPlayer,$params, $context);

    }

    public function processResultForCreateMobilePlayer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

        if(!$success) {
            if($resultJson['error_code'] ==  '20000' || $resultJson['error_code'] ==  '20001') {
                $success = true; // set success to true if exist username and member id
                $this->CI->utils->debug_log('----------CREATE MOBILE API RETURN ----------', $resultJson['error_message']);
            } else {
                $success = false;
                $this->CI->utils->debug_log('----------CREATE MOBILE API RETURN ----------', $resultJson['error_message']);
            }
        }

        return array($success);
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);

        $playerWithoutPrefix=$playerName;
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);

        //use right password
        $password=$this->getPasswordByGameUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerWithoutPrefix' => $playerWithoutPrefix,
            'playerName' => $playerName,
            'playerId' => $playerId,
        );

        $key = $this->getStartKey('bbin_create_member') .
            md5($this->bbin_mywebsite . $playerName . $this->bbin_create_member['keyb'] . $this->getYmdForKey()) .
            $this->getEndKey('bbin_create_member');

        $params =  array(
            "website" => $this->bbin_mywebsite,
            "username" => $playerName,
            "uppername" => $this->bbin_uppername,
            "key" => $key
        );

        if($this->add_password_when_create_player){
            $params['password']=$password;
        }

        $rlt=$this->callApi(self::API_createPlayer, $params, $context);

        if($this->enable_mobile_api){
            if($rlt['success'] ){
                //create mobile player too
                $rlt=$this->createMobilePlayer($playerWithoutPrefix, $playerId, $password, $email, $extra);
            }
        }

        return $rlt;

    }

    public function processResultForCreatePlayer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

        $result=[];

        if (!$success) {
            if (isset($resultJson['data']['Code']) && @$resultJson['data']['Code'] == '21001') {
                //repeated account
                $success = true;     // set success if player exist
                $result['user_exists']=true;
                $this->CI->utils->debug_log('repeated account', $playerName);
            }
        }

        //update register
        if ($success) {
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
        }

        return array($success, $result);
    }

    public function login($playerName, $password = null) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'playerName' => $playerName,
        );

        $key = $this->getStartKey('bbin_login_member') .
            md5($this->bbin_mywebsite . $playerName . $this->bbin_login_member['keyb'] . $this->getYmdForKey()) .
            $this->getEndKey('bbin_login_member');

        $params=array(
            "website" => $this->bbin_mywebsite,
            "username" => $playerName,
            "uppername" => $this->bbin_uppername,
            "key" => $key
        );

        if($this->add_password_when_create_player){
            $params['password']=$password;
        }

        if($this->ignore_password_when_login){
            unset($params['password']);
        }

        $this->activate_login = true;

        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForLogin($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);

        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

        return array($success, $resultJson);
    }

    public function logout($playerName, $password = null) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'playerName' => $playerName,
        );

        $key = $this->getStartKey('bbin_logout_member') .
            md5($this->bbin_mywebsite . $playerName . $this->bbin_logout_member['keyb'] . $this->getYmdForKey()) .
            $this->getEndKey('bbin_logout_member');

        $params =  array(
            "website" => $this->bbin_mywebsite,
            "username" => $playerName,
            "key" => $key
        );

        return $this->callApi(self::API_logout, $params, $context);
    }

    public function processResultForLogout($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
        return array($success, null);
    }

    public function queryPlayerBalance($playerName) {
        $playerInfo = $this->getPlayerInfoByUsername($playerName);
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        if (empty($playerName)) {
            $this->CI->load->library(array('salt'));
            $password = $this->CI->salt->decrypt($playerInfo->password, $this->CI->config->item('DESKEY_OG'));
            //try create player
            $rlt = $this->createPlayer($playerInfo->username, $playerInfo->playerId, $password);
            if (!$rlt['success']) {
                return $rlt;
            } else {
                $this->updateRegisterFlag($playerInfo->playerId, Abstract_game_api::FLAG_TRUE);
                $playerName = $this->getGameUsernameByPlayerUsername($playerInfo->username);

            }
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'playerName' => $playerName,
        );

        $key = $this->getStartKey('bbin_check_member_balance') .
            md5($this->bbin_mywebsite . $playerName . $this->bbin_check_member_balance['keyb'] . $this->getYmdForKey()) .
            $this->getEndKey('bbin_check_member_balance');

        $params =  array(
            "website" => $this->bbin_mywebsite,
            "username" => $playerName,
            "uppername" => $this->bbin_uppername,
            "key" => $key
        );

        $this->activate_new_api = false;

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
        $result = array();

        if ($success && isset($resultJson['data']) && !empty($resultJson['data'])) {
            $balance = null;
            //search player name
            foreach ($resultJson['data'] as $row) {
                if ($playerName == $row['LoginName']) {
                    $balance = $row['Balance'];
                    break;
                }
            }

            if ($balance !== null) {

                $result["balance"] = floatval($balance);
                $playerId = $this->getPlayerIdInGameProviderAuth($playerName);

                $this->CI->utils->debug_log('query balance playerId', $playerId, 'playerName', $playerName,
                    'balance', $balance);

                if ($playerId) {
                    // should update database
                    // $this->updatePlayerSubwalletBalance($playerId, $result["balance"]);
                } else {
                    $msg = $this->CI->utils->debug_log('cannot get player id from ', $playerName, ' getPlayerIdInGameProviderAuth');
                    log_message('error', $msg);
                }
            } else {
                $this->CI->utils->debug_log('lost player', $playerName);
            }
        } else {
            $success = false;
        }
        return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null) {
        $playerUsername = $playerName;
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);

        $remitno = random_string('numeric',15);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'amount' => $amount,
            'remitno' => $remitno,
            'usernameWithoutPrefix' => $playerUsername,
            //for this api
            // 'enabled_guess_success_for_curl_errno_on_this_api' => $this->enabled_guess_success_for_curl_errno_on_this_api,
        );

        $params = array(
            "op" => $this->prefix_for_username,
            "username" => $playerName,
            "uppername" => $this->bbin_uppername,
            "action" => 'IN',
            "remitno" => $remitno,
            "Remit" => $amount,
        );

        $url  = '/api/transfer?'.http_build_query($params);

        $params['auth'] = strtoupper(md5($this->bbin_private_key . $url, false));

        $this->activate_new_api = true;
        $this->new_bbin_uri = 'transfer';

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $remitno = $this->getVariableFromContext($params, 'remitno');
        $usernameWithoutPrefix = $this->getVariableFromContext($params, 'usernameWithoutPrefix');

        $success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
        $result = array('response_result_id' => $responseResultId);

        if ($success) {

            $playerBalance = $this->queryPlayerBalance($usernameWithoutPrefix);

            //for sub wallet
            $afterBalance = $playerBalance['balance'];
            $result["external_transaction_id"] = $remitno;
            $result["currentplayerbalance"] = $afterBalance;
            $result["remitno"] = $remitno;
            $result["userNotFound"] = false;

            //update
            $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
            if ($playerId) {
                //deposit
                $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
                    $this->transTypeMainWalletToSubWallet());
            } else {
                $this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
            }

        } else {
            $result["userNotFound"] = true;

            $this->CI->utils->debug_log('--------- GSBBIN Deposit got error', $resultJson['error_message']);
        }

        return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null) {
        $playerUsername = $playerName;
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);

        $remitno = random_string('numeric',15);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'playerName' => $playerName,
            'amount' => $amount,
            'remitno' => $remitno,
            'usernameWithoutPrefix' => $playerUsername,
            // 'enabled_guess_success_for_curl_errno_on_this_api' => $this->enabled_guess_success_for_curl_errno_on_this_api,
        );

        $params = array(
            "op" => $this->prefix_for_username,
            "username" => $playerName,
            "uppername" => $this->bbin_uppername,
            "action" => 'OUT',
            "remitno" => $remitno,
            "Remit" => $amount,
        );

        $url  = '/api/transfer?'.http_build_query($params);

        $params['auth'] = strtoupper(md5($this->bbin_private_key . $url, false));

        $this->activate_new_api = true;
        $this->new_bbin_uri = 'transfer';

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawToGame($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $remitno = $this->getVariableFromContext($params, 'remitno');
        $usernameWithoutPrefix = $this->getVariableFromContext($params, 'usernameWithoutPrefix');

        $success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);
        $result = array('response_result_id' => $responseResultId);

        if ($success) {
            $playerBalance = $this->queryPlayerBalance($usernameWithoutPrefix);

            //for sub wallet
            $afterBalance = $playerBalance['balance'];
            $result["external_transaction_id"] = $remitno;
            $result["currentplayerbalance"] = $afterBalance;
            $result["remitno"] = $remitno;
            $result["userNotFound"] = false;

            //update
            $playerId = $this->getPlayerIdInGameProviderAuth($playerName);
            if ($playerId) {
                //withdrawal
                $this->insertTransactionToGameLogs($playerId, $playerName, $afterBalance, $amount, $responseResultId,
                    $this->transTypeSubWalletToMainWallet());
            } else {
                $this->CI->utils->debug_log('error', 'cannot get player id from ' . $playerName . ' getPlayerIdInGameProviderAuth');
            }
        } else {
            $result["userNotFound"] = true;
        }

        return array($success, $result);
    }

    public function changePassword($playerName, $oldPassword=null, $newPassword) {

        if($this->enable_mobile_api && $this->enabled_change_password){

            $playerName = $this->getGameUsernameByPlayerUsername($playerName);
            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForChangePassword',
                'playerName' => $playerName,
                'newPassword' => $newPassword,
            );

            $params =  array(
                "op" => $this->prefix_for_username,
                "member_id" => $playerName,
                "password" => $newPassword,
                "actype" => self::PASSWORD_TEXT,
            );

            $url  = '/api/SetMobilePassword?'.http_build_query($params);

            $params['auth'] = strtoupper(md5($this->bbin_private_key . $url, false));

            $this->activate_new_api = true;
            $this->new_bbin_uri = 'SetMobilePassword';

            return $this->callApi(self::API_changePassword, $params, $context);
        }

        return $this->returnUnimplemented();
    }

    function processResultForChangePassword($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $success = $this->processResultBoolean($responseResultId, $resultJson, $playerName);

        if (!$success) {
            if (isset($resultJson['error_code']) == '20001') {
                $this->CI->utils->debug_log('GS BBIN changePasword ERROR', @$resultJson['error_code'], $playerName);
            }
        }

        return array($success, ['message'=>@$resultJson['error_message']]);
    }

    public function queryPlayerInfo($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        return array("success" => true);
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

     public function updatePlayerInfo($playerName, $infos) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        return array("success" => true);
    }

    public function queryPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null) {
        $daily_balance = parent::getPlayerDailyBalance($playerName, $playerId, $dateFrom = null, $dateTo = null);

        $result = array();

        if ($daily_balance != null) {
            foreach ($daily_balance as $key => $value) {
                $result[$value['updated_at']] = $value['balance'];
            }
        }

        return array_merge(array('success' => true, "balanceList" => $result));
    }

    public function queryGameRecords($dateFrom, $dateTo, $playerName = null) {
        $gameRecords = parent::getGameRecords($dateFrom, $dateTo, $playerName, $this->getPlatformCode());
        return array('success' => true, 'gameRecords' => $gameRecords);
    }

    public function checkLoginStatus($playerName) {
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);
        return array("success" => true, "loginStatus" => true);
    }

    public function queryForwardGame($playerName, $extra) {
        $password = $this->getPasswordString($playerName);
        $playerUsername = $playerName;
        $playerName = $this->getGameUsernameByPlayerUsername($playerName);

        $bbin_gametype = self::BBIN_GAMETYPE;
        $gameType = $extra['gameType'];
        $language = $extra['language'];

        $key = $this->getStartKey('bbin_login_member') .
            md5($this->bbin_mywebsite . $playerName . $this->bbin_login_member['keyb'] . $this->getYmdForKey()) .
            $this->getEndKey('bbin_login_member');

        $params = array(
            'website' => $this->bbin_mywebsite,
            'username' => $playerName,
            'uppername' => $this->bbin_uppername,
            'password' => $password,
            'page_site' => @$bbin_gametype[$gameType],
            'key' => $key,
            'lang' => $language,
        );

        if($this->ignore_password_when_login){
            unset($params['password']);
        }

        $params_string = http_build_query($params);
        $url = $this->url_login . '/Login?' . $params_string;

        //call get html form or json error
        $apiName = self::API_queryForwardGame;
        list($header, $resultText, $statusCode, $statusText, $errCode, $error, $resultObj) = $this->httpCallApi($url, $params);
        $success = !$this->isErrorCode($apiName, $params, $statusCode, $errCode, $error);
        $dont_save_response_in_api = $this->CI->utils->getConfig('dont_save_response_in_api');
        if (!$success) {
            $this->CI->utils->debug_log('success', $success, 'result', $resultText, 'url', $url, 'params', $params);
        }
        $fields = null;

        $this->saveResponseResult($success, $apiName, $params, $resultText,
            $statusCode, $statusText, $header, $fields, $dont_save_response_in_api);

        $json = json_decode($resultText, true);

        if (!empty($json)) {
            //error
            $result['success'] = false;
            $this->CI->utils->debug_log('url', $url, 'params', $params, $result, $resultText);
            if (isset($json['data']['Message'])) {
                $result['message'] = $json['data']['Message'];
            } else {
                $result['message_lang'] = 'goto_game.error';
            }
        } else {
            $result['html'] = $resultText;
            $result['success'] = true;
            $this->CI->utils->debug_log($result, $resultText, $json);
        }

        return $result;
    }

    public function syncOriginalGameLogs($token) {


        if ($this->enable_pulling_fishing_record) {
            $this->getFishingRecord($token, 30, '30599' ,'getFishingRecord');
            sleep(0.5);
            $this->getFishingRecord($token, 38, '38001' ,'getFishingRecord2');
            sleep(0.5);
        }

        //get record for ( bbsports, live, 3d_hall )
        $this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['bb_sports']['game_kind']);
		sleep(0.5);
		$this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['live']['game_kind']);
		sleep(0.5);
		$this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['3d_hall']['game_kind']);
		sleep(0.5);
		$this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['casino']['game_kind'], null, 1);
		sleep(0.5);
		$this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['casino']['game_kind'], null, 2);
		sleep(0.5);
		$this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['casino']['game_kind'], null, 3);
		sleep(0.5);
		$this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['casino']['game_kind'], null, 4);
		sleep(0.5);
		$this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['casino']['game_kind'], null, 5);
		sleep(0.5);
		$this->CI->load->model('game_description_model');

        foreach ($this->lottery_kinds as $kind) {
            $this->getBBINRecords($token, self::BBIN_GAME_PROPERTY['lottery']['game_kind'], $kind);
            sleep(0.5);
        }
	}

    private function getFishingRecord($token, $gameKind = null, $gameType =null, $apiName = null) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        //convert to game time first
        $start_date = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $start_date->modify($this->getDatetimeAdjust());
        $end_date = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncFishingRecords',
            'token' => $token,
            'gameKind' => $gameKind,
        );

        $dates = array();
        $dates = $this->CI->utils->dateRange($this->CI->utils->formatDateForMysql($start_date), $this->CI->utils->formatDateForMysql($end_date));

        $key = $this->getStartKey('bbin_getbet')
            . md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey())
            . $this->getEndKey('bbin_getbet');

        foreach($dates as $date) {
            $done = false;
            $failure_count = 0;
            $page = self::START_PAGE;
            while(!$done && $failure_count < $this->common_retry_times) {
                $params = array(
                    "website" => $this->bbin_mywebsite,
                    "username" => '',
                    "action" => 'BetTime',
                    "uppername" => $this->bbin_uppername,
                    "date" => $date,
                    "starttime" => '00:00:00', //$start_date->format('H:i:s'),
                    "endtime" =>  '23:59:59',  //$end_date->format('H:i:s'),
                    "gametype" => $gameType,
                    "page" => $page,
                    "pagelimit" => self::ITEM_PER_PAGE,
                    "key" => $key
                );

                $rlt = $this->callApi($apiName,$params, $context);
                if($rlt['success']) {
                    if($rlt['currentPage'] < $rlt['totalPages']) {
                        $page = $rlt['currentPage'] + 1;
                    } else {
                        $done = true;
                    }
                    $failure_count = 0;
                } else {
                    # API call may fail (e.g. during maintenance)
                    # we shall terminate the loop after certain consecutive failures
                    $failure_count++;
                }
            }
        }
    }

    public function processResultForSyncFishingRecords($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);

        $game_kind = $this->getVariableFromContext($params, 'gameKind');

        $this->CI->load->model(array('bbin_game_logs', 'external_system'));
        $result = array();
        $success = $this->processResultBoolean($responseResultId, $resultJson);
        if ($success) {
            $gameRecords = $resultJson['data'];
            if ($gameRecords) {
                foreach ($gameRecords as $row) {
                    $this->copyRowToDB($row, $responseResultId, $game_kind);
                }
            }
            $page = $resultJson['pagination']['Page'];
            $totalPages = $resultJson['pagination']['TotalPage'];
            $result['currentPage'] = $page;
            $result['totalPages'] = $totalPages;
        } else {
            $success = false;
            $errorCode = $result['error_code']=@$resultJson['data']['Code'];
            if($errorCode == self::SYSTEM_MAINTENANCE){ # system maintenance skip error log
                $result['currentPage'] = 0;
                $result['totalPages'] = 1;
                $result = $resultJson;
            }
        }
        return array($success, $result);
    }

    private function getBBINRecords($token, $gameKind, $gameType = null, $subGameKind = null) {

        $this->CI->utils->debug_log('getBBINRecordsParams' , 'gameKind', $gameKind, 'gameType', 'subGameKind', $subGameKind );
        //should try 3 times
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        //convert to game time first
        $start_date = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $start_date->modify($this->getDatetimeAdjust());
        $end_date = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $dates = $this->CI->utils->dateRange($this->CI->utils->formatDateForMysql($start_date), $this->CI->utils->formatDateForMysql($end_date));
        $this->CI->utils->debug_log('dates', $dates, 'start_date', $start_date, 'end_date', $end_date);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncGameRecords',
            'token' => $token,
            'gameKind' => $gameKind,
            'subGameKind' => $subGameKind,
        );

        $key = $this->getStartKey('bbin_getbet') .
            md5($this->bbin_mywebsite . $this->bbin_getbet['keyb'] . $this->getYmdForKey()) .
            $this->getEndKey('bbin_getbet');

        $cnt = 0;
        while ($cnt < count($dates)) {
            $page = self::START_PAGE;
            $done = false;
            $success = true;

            while (!$done) {
                $starttime = $cnt == 0 ? $start_date->format('H:i:s') : '00:00:00';
                $endtime = $cnt == count($dates) - 1 ? $end_date->format('H:i:s') : '23:59:59';
                $data = array(
                    "website" => $this->bbin_mywebsite,
                    "uppername" => $this->bbin_uppername,
                    "rounddate" => $dates[$cnt],
                    "starttime" => $starttime,
                    "endtime" => $endtime,
                    "gamekind" => $gameKind,
                    "page" => $page,
                    "pagelimit" => self::ITEM_PER_PAGE,
                    "key" => $key);

                //for lottery game type
                if ($gameType) {
                    $data["gametype"] = $gameType;
                }

                //for casino game type
                if ($subGameKind) {
                    $data["subgamekind"] = $subGameKind;
                }

                $retry_count=0;
                $try_again=true;
                while($retry_count<$this->common_retry_times && $try_again){

                    $retry_count++;

                    $rlt = $this->callApi(self::API_syncGameRecords, $data, $context);

                    $done = true;
                    if ($rlt) {
                        $success = $rlt['success'];
                    }
                    if ($rlt && $rlt['success']) {
                        $try_again=false;
                        $page = $rlt['currentPage'];
                        $total_pages = $rlt['totalPages'];
                        //next page
                        $page += 1;

                        $done = $page >= $total_pages;
                        $this->CI->utils->debug_log('page', $page, 'total_pages', $total_pages, 'done', $done, 'result', $rlt);
                    }else{
                        if(@$rlt['error_code']=='40014'){
                            $this->CI->utils->debug_log('get 40014 print param', $data);
                        }

                        //try again if api busy
                        $try_again=@$rlt['error_code']=='44003';
                        if($try_again){
                            $this->CI->utils->debug_log('try again for api busy wait:'.$this->common_wait_seconds);
                            //try again
                            sleep($this->common_wait_seconds);
                        }
                    }
                }
            }
            if ($done) {
                $cnt++;
            }
        }
        return true;
    }

    public function processResultForSyncGameRecords($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);

        // load models
        $this->CI->load->model(array('gsbbin_game_logs', 'external_system'));
        $result = array();
        $success = $this->processResultBoolean($responseResultId, $resultJson);
        if ($success) {
            $gameRecords = $resultJson['data'];

            if ($this->isPrintVerbose()) {
                if ($resultJson['pagination']['TotalPage'] > 0) {
                    $this->CI->utils->debug_log('resultJson', $resultJson);
                }
            }

            if ($gameRecords) {
                foreach ($gameRecords as $row) {
                    $this->copyRowToDB($row, $responseResultId, $params['params']['gamekind']);
                }
            }

            $page = $resultJson['pagination']['Page'];
            $totalPages = $resultJson['pagination']['TotalPage'];
            $result['currentPage'] = $page;
            $result['totalPages'] = $totalPages;

            $this->CI->utils->debug_log('==========get game records', count($gameRecords));
        } else {
            $success = false;
            $token = $this->getVariableFromContext($params, 'token');
            $gameKind = $params['params']['gamekind'];
            $gameType = isset($params['params']['gametype']) ? $params['params']['gametype'] : null;
            $subGameKind = isset($params['params']['subgamekind']) ? $params['params']['subgamekind'] : null;
            $errorCode = $result['error_code']=@$resultJson['data']['Code'];
            if($errorCode != self::API_BUSY){ # skip error log if API busy
                $this->CI->utils->error_log('BBIN Sync Game Log Failed!  game kind: ', $gameKind . ' gameType: ' . $gameType . ' subGameKind: ' . $subGameKind . ' actual params:' . json_encode($params['params']));
            }
        }

        return array($success, $result);
    }

    private function getFlagByRow($row, $gameKind) {
        $this->CI->load->model(array('gsbbin_game_logs'));

        $flagFinished = false;
        if ($gameKind == self::BBIN_GAME_PROPERTY['bb_sports']['game_kind']) {
            //set flag
            //sports need get result !=
            $flagFinished = $row['Result'] == 'L' || $row['Result'] == 'W' ||
                $row['Result'] == 'LW' || $row['Result'] == 'LL' || $row['Result'] == '0';
        }

		if ($gameKind == self::BBIN_GAME_PROPERTY['live']['game_kind']) {
            $flagFinished = $row['ResultType'] != '-1' && $row['ResultType'] != '0';
        }

		if ($gameKind == self::BBIN_GAME_PROPERTY['casino']['game_kind']) {
            $flagFinished = $row['Result'] == '1' || $row['Result'] == '200';
        }

		if ($gameKind == self::BBIN_GAME_PROPERTY['lottery']['game_kind']) {
            $flagFinished = $row['Result'] == 'W' || $row['Result'] == 'L' || $row['Result'] == 'N';
            $flagFinished = $flagFinished && $row['IsPaid'] == 'Y';
        }

		if ($gameKind == self::BBIN_GAME_PROPERTY['3d_hall']['game_kind']) {
            $flagFinished = $row['Result'] == '1' || $row['Result'] == '3';
        }

        if ($gameKind == '30' || $gameKind == '38') {
            $flagFinished = Bbin_game_logs::FLAG_FINISHED;
        }

		return $flagFinished ? Gsbbin_game_logs::FLAG_FINISHED : Gsbbin_game_logs::FLAG_UNFINISHED;

	}

    private function copyRowToDB($row, $responseResultId, $gameKind) {
        $external_uniqueid = $row['WagersID'];
        $result = array(
            'username' => $row['UserName'],
            'wagers_id' => $row['WagersID'],
            'wagers_date' => $this->gameTimeToServerTime($row['WagersDate']),
            'game_type' => $row['GameType'],
            'result' => $row['Result'],
            'bet_amount' => $this->gameAmountToDB($row['BetAmount']),
            'currency' => $row['Currency'],
            'exchange_rate' => $row['ExchangeRate'],
            'external_uniqueid' => $external_uniqueid,
            'response_result_id' => $responseResultId,
            'game_kind' => $gameKind,
            'updated_at' => $this->CI->utils->getNowForMysql(),
        );

        $result['serial_id'] = isset($row['SerialID']) ? $row['SerialID'] : null;
        $result['round_no'] = isset($row['RoundNo']) ? $row['RoundNo'] : null;
        $result['game_code'] = isset($row['GameCode']) ? $row['GameCode'] : null;
        $result['result_type'] = isset($row['ResultType']) ? $row['ResultType'] : null;
        $result['card'] = isset($row['Card']) ? $row['Card'] : null;
        $result['commision'] = isset($row['Commission']) ? $row['Commission'] : null;
        $result['is_paid'] = isset($row['IsPaid']) ? $row['IsPaid'] : null;
        $result['origin'] = isset($row['Origin']) ? $row['Origin'] : null;

        $result['commisionable'] = isset($row['Commissionable']) ? $this->gameAmountToDB($row['Commissionable']) : 0;
        $result['payoff'] = $this->gameAmountToDB($row['Payoff']);

        if ($gameKind == self::BBIN_GAME_PROPERTY['lottery']['game_kind']) {
            $result['commisionable'] = $this->gameAmountToDB($result['bet_amount']);
        }

		$result['flag'] = $this->getFlagByRow($row, $gameKind);

		$this->CI->gsbbin_game_logs->sync($result);
	}

    public function syncMergeToGameLogs($token) {
        $dateTimeFrom = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $dateTimeTo = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $dateTimeFrom->modify($this->getDatetimeAdjust());

        $result = $this->getBBINGameLogStatistics($dateTimeFrom->format('Y-m-d H:i:s'), $dateTimeTo->format('Y-m-d H:i:s'));

        if ($result) {
            $this->CI->load->model(array('game_logs', 'player_model', 'game_description_model'));

            $unknownGame = $this->getUnknownGame();

            foreach ($result as $bbindata) {
                $username = $bbindata->username;
                $player_id = $bbindata->player_id;

                if (!$player_id) {
                    continue;
                }

                $player_username = $username;

                $gameDate = new \DateTime($bbindata->wagers_date);
                $gameDateStr = $this->CI->utils->formatDateTimeForMysql($gameDate);
                $bet_amount = $this->gameAmountToDB($bbindata->commisionable);
                $real_bet_amount=$this->gameAmountToDB($bbindata->bet_amount);

                $game_code = $bbindata->game_code;
                $game_description_id = $bbindata->game_description_id;
                $game_type_id = $bbindata->game_type_id;

                if (empty($game_description_id)) {
                    list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($bbindata, $unknownGame);
                }

                if (empty($game_description_id)) {
                    $game_description_id = $unknownGame->id;
                    $game_type_id = $unknownGame->game_type_id;
                }
                $result_amount = $this->gameAmountToDB($bbindata->payoff);
                $extra=['table' => $bbindata->external_uniqueid, 'trans_amount'=>$real_bet_amount];

                $this->syncGameLogs($game_type_id, $game_description_id, $game_code,
                    $game_type_id, $game_code, $player_id, $player_username,
                    $bet_amount, $result_amount, null, null, null, null,
                    $bbindata->external_uniqueid, $gameDateStr, $gameDateStr,
                    $bbindata->response_result_id, Game_logs::FLAG_GAME, $extra);

            }
        }
    }

   private function getGameDescriptionInfo($row, $unknownGame) {

        $this->CI->load->model('game_type_model');

        $external_game_id = $row->game_type;
        $extra = array('game_code' => $row->game_type);
        $game_description_id = null;

        switch ($row->game_kind) {
            case self::BBIN_GAME_PROPERTY['bb_sports']['game_kind']:
                    $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%sports%')";
                break;

            case self::BBIN_GAME_PROPERTY['lottery']['game_kind']:
                    $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%lottery%')";
                break;

            // case self::BBIN_GAME_PROPERTY['3d_hall']['game_kind']:
            case self::BBIN_GAME_PROPERTY['live']['game_kind']:
                    $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%live%')";
                break;
            case self::BBIN_GAME_PROPERTY['casino']['game_kind']:
                    $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%casino%')";
                break;
            default:
                    $query = "(game_platform_id = " . $this->getPlatformCode() . " and game_type like '%unknown%')";
                break;
        }

        $game_type_details = $this->CI->game_type_model->getGameTypeList($query);

        if(!empty($game_type_details[0])){
            $game_type_id = $game_type_details[0]['id'];
            $row->gametype = $game_type_details[0]['game_type'];
        }else{
            $game_type_id = $unknownGame->game_type_id;
            $row->gametype = $unknownGame->game_name;
        }

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $row->game_type, $row->gametype, $external_game_id, $extra,
            $unknownGame);

    }

    public function gameAmountToDB($amount) {
        return round(floatval($amount), 2);
    }

    private function getBBINGameLogStatistics($dateTimeFrom, $dateTimeTo) {
        $this->CI->load->model('gsbbin_game_logs');
        return $this->CI->gsbbin_game_logs->getBBINGameLogStatistics($dateTimeFrom, $dateTimeTo);
    }

    public function isPlayerExist($playerName) {
        $playerInfo = $this->getPlayerInfoByUsername($playerName);
        $playerId=$this->getPlayerIdFromUsername($playerName);

        $userName = $this->getGameUsernameByPlayerUsername($playerName);
        $userName = !empty($userName)?$userName:$playerName;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsplayerExist',
            'playerName' => $playerName,
            'playerId'=>$playerId,
        );

        $key = $this->getStartKey('bbin_check_member_balance') .
            md5($this->bbin_mywebsite . $userName . $this->bbin_check_member_balance['keyb'] . $this->getYmdForKey()) .
            $this->getEndKey('bbin_check_member_balance');

        $params = array(
            "website" => $this->bbin_mywebsite,
            "username" => $userName,
            "uppername" => $this->bbin_uppername,
            "key" => $key
        );
        return $this->callApi(self::API_isPlayerExist,$params,$context);
    }

    public function processResultForIsplayerExist($params){
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $result = array();

        $success = true;
        if(empty($resultJson)){
            $success = false;
            $result = array('exists' => null);
        }else{
            if (isset($resultJson['data']['Code'])&&$resultJson['data']['Code']=="22002") {
                $result = array('exists' => false);
            }else{
                $result = array('exists' => true);
                $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            }
        }

        return array($success, $result);
    }

    public function getGameTimeToServerTime() {
        return '+12 hours';
    }

    public function getServerTimeToGameTime() {
        return '-12 hours';
    }

    public function batchQueryPlayerBalance($playerNames, $syncId = null) {
        $this->CI->benchmark->mark('bbin_sync_balance_start');

        $this->CI->load->model(array('game_provider_auth', 'player_model'));
        if (empty($playerNames)) {
            // $playerNames = array();
            //load all players
            $playerNames = $this->getAllGameUsernames();
        } else {
            //convert to game username
            // foreach ($playerNames as &$username) {
            // 	$username = $this->getGameUsernameByPlayerUsername($username);
            // }
            //call parent
            // return parent::batchQueryPlayerBalanceForeach($playerNames, $syncId);
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForBatchQueryPlayerBalance',
            'playerNames' => $playerNames,
            'dont_save_response_in_api' => $this->getConfig('dont_save_response_in_api'),
            'syncId' => $syncId,
        );

        $page = self::START_PAGE;
        $done = false;
        $success = true;
        $result = array();

        try {
            $key = $this->getStartKey('bbin_check_member_balance')
            . md5($this->bbin_mywebsite . $this->bbin_check_member_balance['keyb'] . $this->getYmdForKey())
            . $this->getEndKey('bbin_check_member_balance');

            while (!$done) {

                $data = array(
                    "website" => $this->bbin_mywebsite,
                    "uppername" => $this->bbin_uppername,
                    "page" => $page,
                    "pagelimit" => self::ITEM_PER_PAGE,
                    'key' => $key,
                );

                $rlt = $this->callApi(self::API_batchQueryPlayerBalance, $data, $context);

                $done = true;

                if ($rlt && $rlt['success']) {
                    $page = $rlt['currentPage'];
                    $total_pages = $rlt['totalPages'];
                    //next page
                    $page += 1;

                    $done = $page > $total_pages;
                    if (empty($result)) {
                        $result = $rlt['balances'];
                    } else if(is_array($rlt['balances'])){
                        $result = array_merge($rlt['balances'], $result);
                    } else{
                    }

                } else {
                    $this->CI->utils->error_log('failed', $rlt);
                }
            }

        } catch (Exception $e) {
            $this->processError($e);
            $success = false;
        }
        $this->CI->benchmark->mark('bbin_sync_balance_stop');
        $this->CI->utils->debug_log('bbin_sync_balance_bench', $this->CI->benchmark->elapsed_time('bbin_sync_balance_start', 'bbin_sync_balance_stop'));

        return $this->returnResult($success, "balances", $result);
    }

    function processResultForBatchQueryPlayerBalance($params) {

        $responseResultId = $params['responseResultId'];
        $resultJson = $this->convertResultJsonFromParams($params);

        $success = $this->processResultBoolean($responseResultId, $resultJson);

        $result = array('balances' => null);
        $cnt = 0;
        // if ($success) {
        if ($success && isset($resultJson['data']) && !empty($resultJson['data'])) {

            foreach ($resultJson['data'] as $balResult) {
                $gameUsername = $balResult['LoginName'];
                $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
                if ($playerId) {

                    $bal = floatval($balResult['Balance']);

                    $result["balances"][$playerId] = $bal;

                    $this->updatePlayerSubwalletBalance($playerId, $bal);
                    $cnt++;
                }

            }
        }

        $this->CI->utils->debug_log('sync balance', $cnt, 'success', $success);
        if ($success) {
            // $success = true;
            // if (isset($resultJson['pagination'])) {
            $result['totalPages'] = @$resultJson['pagination']['TotalPage'];
            $result['currentPage'] = @$resultJson['pagination']['Page'];
            $result['itemsPerPage'] = self::ITEM_PER_PAGE;
            $result['totalCount'] = @$resultJson['pagination']['TotalNumber'];
        }

        return array($success, $result);

    }

    public function onlyTransferPositiveInteger(){
        return true;
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
    }
}