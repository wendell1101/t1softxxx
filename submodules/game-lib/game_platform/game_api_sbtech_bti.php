<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
/**
 * Defines general behavior of game API classes.
 *
 * General behaviors include:
 * * Create Player
 * * Get player balance
 * * deposit balance to game
 * * withdraw balance to game
 * * forward game
 * * sync original game logs and merge
 * * get game log statistics
 * * get total betting amount
 *
 * The functions implemented by child class:
 * * Populating game form parameters
 * * Handling callbacks
 *
 *
 *
 * @see Redirect redirect to game page
 *
 * @category Game_platform
 * @version 3.52.02
 * @copyright 2013-2022 tot
 * @integrator Garry
 */

class Game_api_sbtech_bti extends Abstract_game_api {
    private $url;
    private $is_data_api;
    private $token;
    public $sync_time_interval;
    public $sync_sleep_time;

    const API_getbetbyPurchaseID = 'getbetbyPurchaseID';

    const URI_MAP = array(
        self::API_createPlayer => 'CreateUser',
        self::API_checkLoginToken => 'GetCustomerAuthToken',
        self::API_queryPlayerBalance => 'GetBalance',
        self::API_depositToGame => 'TransferToWHL',
        self::API_withdrawFromGame => 'TransferFromWHL',
        self::API_isPlayerExist => 'GetBalance',
        self::API_syncGameRecords => 'GetBetsFeedFilesListForPeriod',
        self::API_queryPlayerInfo => 'MemberBalance',
        self::API_queryForwardGame => 'PlayGame',
        self::API_queryTransaction => 'CheckTransaction',

        self::API_generateToken => 'gettoken',
        self::API_queryGameRecords => 'bettinghistory',
        self::API_getbetbyPurchaseID => 'getbetbyPurchaseID',
        'openBets' => 'openBets',
        'authorize_v2' => 'authorize_v2',
        'get-open-bets-paging' => 'get-open-bets-paging',
        'get-history-bets-paging' => 'get-history-bets-paging',
        'get-bet-by-id' => 'get-bet-by-id',
    );

    const DEFAULT_VIP_LEVEL_IN_GAME = 1;
    const ERROR_CODE_NO_ERR = 'NoError';
    const WHL_STATUS_CALLBACK = 'status';
    const WHL_REFRESH_SESSION_CALLBACK = 'refresh_session';
    const SUCCESS_CODE = 0;

    const DATA_API_VERSION1 = '1.0';
    const DATA_API_VERSION2 = '2.0';
    const DATA_API_VERSION3 = '3.0';


    # Fields in sbtech_bti_game_logs we want to detect changes for update
    const MD5_FIELDS_FOR_ORIGINAL=[
        'gain',
        'pl',
        'non_cashout_amount',
        'combo_bonus_amount',
        'bet_settled_date',
        'update_date',
        'odds',
        'odds_in_user_style',
        'total_stake',
        'bet_status',
        'username',
        'status',
        'selections',
        'creation_date',
        'validStake'
    ];

    # Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS = [
        'gain',
        'pl',
        'non_cashout_amount',
        'combo_bonus_amount',
        'odds',
        'odds_in_user_style',
        'total_stake',
        'validStake'
    ];

    # Fields in game_logs we want to detect changes for merge, and when sbtech_bti_game_logs.md5_sum is empty
    const MD5_FIELDS_FOR_MERGE=[
        'gain',
        'pl',
        'non_cashout_amount',
        'combo_bonus_amount',
        'bet_settled_date',
        'update_date',
        'odds',
        'odds_in_user_style',
        'total_stake',
        'bet_status',
        'username',
        'status',
        'selections',
        'creation_date',
        'real_bet_amount'
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE=[
        'gain',
        'pl',
        'non_cashout_amount',
        'combo_bonus_amount',
        'odds',
        'odds_in_user_style',
        'total_stake',
        'real_bet_amount'
    ];

    # Don't ignore on refresh
    const DEFAULT_IGNORED_0_ON_REFRESH = FALSE;

    public function __construct() {
        parent::__construct();
        $this->url = $this->getSystemInfo('url');
        $this->agent_username = $this->getSystemInfo('agent_username');
        $this->agent_password = $this->getSystemInfo('agent_password');
        $this->currency_code = $this->getSystemInfo('currency_code', 'CNY');
        $this->country_code = $this->getSystemInfo('country_code', 'CN');
        $this->view_mode = $this->getSystemInfo('view_mode', 'asia');
        $this->language = $this->getSystemInfo('language', 'en');
        $this->url_web_launch = $this->getSystemInfo('url_web_launch');
        $this->url_mobile_launch = $this->getSystemInfo('url_mobile_launch');
        $this->url_direct_launch = $this->getSystemInfo('url_direct_launch');
        $this->data_api = $this->getSystemInfo('data_api');
        $this->data_api_version = $this->getSystemInfo('data_api_version', '1.0');
        $this->open_bets_data_api = $this->getSystemInfo('open_bets_data_api', $this->data_api);
        $this->betting_history_data_api = $this->getSystemInfo('betting_history_data_api', $this->data_api);
        $this->getttoken_data_api = $this->getSystemInfo('getttoken_data_api', $this->data_api);

        $this->sync_time_interval = $this->getSystemInfo('sync_time_interval', '+1 day');
        $this->sync_sleep_time = intval($this->getSystemInfo('sync_sleep_time', 5));

        // default to false due to so slow api. to follow to game provider
        $this->sync_open_bets = $this->getSystemInfo('sync_open_bets', true);

        // try to add in info. game provider js use method get in getting token
        $this->use_iframe_in_web_launch = $this->getSystemInfo('use_iframe_in_web_launch', true);

        // to add min width as per game provider ( due to design issue )
        $this->set_min_width = $this->getSystemInfo('set_min_width', true);
        $this->default_frame_min_width = $this->getSystemInfo('default_frame_min_width', '1280px');

        $this->is_data_api = false;
        $this->token = '';

        $this->bet_detail_remove_selection = $this->getSystemInfo('bet_detail_remove_selection',array());
        $this->odd_style_id = $this->getSystemInfo('odd_style_id');

        $this->virtual_sports_branchid = $this->getSystemInfo('virtual_sports_branchid', ['73', '75']);
        //$this->langid = $this->getSystemInfo('langid', '');
        $this->is_redirect_to_login = $this->getSystemInfo('is_redirect_to_login', false);
        #new version
        $this->use_new_version = $this->getSystemInfo('use_new_version',false);
        $this->new_url_web_launch = $this->getSystemInfo('new_url_web_launch');
        $this->new_url_mobile_launch = $this->getSystemInfo('new_url_mobile_launch');
        $this->new_url_direct_launch = $this->getSystemInfo('new_url_direct_launch');
        $this->new_token_param = $this->getSystemInfo('new_token_param', 'operatorToken');

        $this->original_username_prefix = $this->getSystemInfo('original_username_prefix', $this->agent_username.'_');
        $this->data_api_v3 = $this->getSystemInfo('data_api_v3', 'https://data-api.442hattrick.com');
    }


    public function callback($token, $method) {
        $this->CI->load->model(array('external_common_tokens', 'player_model'));

        $player_id = $this->CI->external_common_tokens->getPlayerIdByExternalToken($token, $this->getPlatformCode());
        $balance = '';
        if($player_id) {
            $player = $this->CI->player_model->getPlayerUsername($player_id);
            if(!empty($player['username'])) {
                $result = $this->queryPlayerBalance($player['username']);
                if($result['success']) {
                    $balance = $result['balance'] == 0 ? $result['balance'] : '';
                }
            }
        }

        $result = [];
        if($method == self::WHL_STATUS_CALLBACK ) {
            $result = array(
                'uid' => random_string('unique'),
                'token' => $token,
                'status' => $player_id ? 'real' : 'anon',
                'message' => '',
                'balance' => $balance
            );
        } else if($method == self::WHL_REFRESH_SESSION_CALLBACK) {
            $result = array(
                'status' => $player_id ? 'success' : 'failure',
                'message' => '',
                'balance' => $balance
            );
        }
        return $result;
    }

    public function getPlatformCode() {
        return SBTECH_BTI_API;
    }

    public function generateUrl($apiName, $params) {
        if ($this->is_data_api) {
            if($this->data_api_version==self::DATA_API_VERSION2){
                if ($apiName==self::API_queryGameRecords) {
                    return $this->betting_history_data_api.'/'.self::URI_MAP[$apiName].'?token='.$this->token;
                }elseif(self::URI_MAP[$apiName] == 'openBets'){
                    return $this->open_bets_data_api.'/'.self::URI_MAP[$apiName].'?token='.$this->token;
                }elseif($apiName==self::API_generateToken){
                    return $this->getttoken_data_api.'/'.self::URI_MAP[$apiName];
                } elseif ($apiName == self::API_getbetbyPurchaseID) {
                    return $this->data_api . '/' . self::URI_MAP[$apiName] . '?token=' . $this->token;
                } else {
                    return $this->data_api.'/'.self::URI_MAP[$apiName];
                }
            }elseif ($this->data_api_version==self::DATA_API_VERSION3) {
                if ($apiName == 'get-history-bets-paging') {
                    $url = $this->data_api_v3.'/'.self::URI_MAP[$apiName].'?token='.$this->token;
                }elseif($apiName == 'get-open-bets-paging'){
                    $url = $this->data_api_v3.'/'.self::URI_MAP[$apiName].'?token='.$this->token;
                }elseif($apiName== 'authorize_v2'){
                    $url = $this->data_api_v3.'/'.self::URI_MAP[$apiName];
                } elseif ($apiName == 'get-bet-by-id') {
                    return $this->data_api_v3 . '/' . self::URI_MAP[$apiName] . '?token=' . $this->token;
                } else {
                    $url = null;
                }
                return $url;
            }else{
                if (self::URI_MAP[$apiName] == self::URI_MAP[self::API_queryGameRecords] ||
                    self::URI_MAP[$apiName] == 'openBets' ||
                    self::URI_MAP[$apiName] == self::API_getbetbyPurchaseID) {
                    return $this->data_api.'/'.self::URI_MAP[$apiName].'?token='.$this->token;
                } else {
                    return $this->data_api.'/'.self::URI_MAP[$apiName];
                }
            }
            
        } else {
            return $this->url.'/'.self::URI_MAP[$apiName];
        }
	}

    protected function getHttpHeaders($params) {

        if ($this->is_data_api) {
            return array("Content-Type" => "application/json");
        } else {
            return array('Content-Type' =>'application/x-www-form-urlencoded');
        }
    }

    protected function customHttpCall($ch, $params) {
        if ($this->is_data_api) {
            $post_data = json_encode($params);
        } else {
            $post_data = http_build_query($params);
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    }

    protected function isErrorCode($apiName, $params, $statusCode, $errCode, $error) {
        return $errCode || intval($statusCode, 10) >= 501;
    }

    public function processResultBoolean($responseResultId, $resultText, $playerName) {
        $success = false;

        if (strpos($resultText, '@ReturnedError') !== false) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->error_log("==========SBTECH API GOT ERROR=============",$resultText, $playerName);
        } else {
                try {
                    $resultXml = new SimpleXMLElement($resultText, LIBXML_NOERROR);
                    $resultArr = json_decode(json_encode($resultXml), true);

                    if( isset($resultArr['ErrorCode']) && $resultArr['ErrorCode'] == self::ERROR_CODE_NO_ERR ) {
                        $success = true;
                    } else {
                        $this->setResponseResultToError($responseResultId);
                        $this->CI->utils->error_log("==========SBTECH API GOT ERROR=============",$resultArr, $playerName);
                    }
                }
                catch (Exception $ex) {
                    $success = false;
                    $this->setResponseResultToError($responseResultId);
                    $this->CI->utils->error_log("==========SBTECH API GOT ERROR=============", $ex->getMessage());
                }
        }
        return $success;
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerName, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
            'playerId' => $playerId,
        );

        $params = array(
            'AgentUserName' => $this->agent_username,
            'AgentPassword' =>  $this->agent_password,
            'MerchantCustomerCode' => $gameUsername,
            'LoginName' => $gameUsername,
            'CurrencyCode' => $this->currency_code,
            'CountryCode' => $this->country_code,
            'City' => '',
            'FirstName' => $gameUsername,
            'LastName' => $gameUsername,
            'Group1ID' => self::DEFAULT_VIP_LEVEL_IN_GAME,
            'CustomerMoreInfo'=> '',
            'CustomerDefaultLanguage' => $this->language,
            'DomainID' => '',
            'DateOfBirth' => '',
        );

        return $this->callApi(self::API_createPlayer, $params, $context);
	}

    public function processResultForCreatePlayer($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultText, $gameUsername);
        $result['exists'] = true;
        if ($success) {
            $resultXml = new SimpleXMLElement($resultText);
            $resultArr = json_decode(json_encode($resultXml), true);
            $token = $resultArr['AuthToken'];
            $this->CI->load->model('external_common_tokens');
            $this->CI->external_common_tokens->addPlayerToken($playerId, $token, SBTECH_BTI_API);

            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result['exists'] = false;
        }
        return array($success, $result);
    }


    public function getPlayerAuthToken($playerName) {

        $playerId = $this->getPlayerIdInPlayer($playerName);

        if(!$playerId) {
            return 'Invalid Player';
        }
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetPlayerAuthToken',
            'gameUsername' => $gameUsername,
        );

        $params = array(
            'AgentUserName' => $this->agent_username,
            'AgentPassword' =>  $this->agent_password,
            'MerchantCustomerCode' => $gameUsername,
        );

        return $this->callApi(self::API_checkLoginToken, $params, $context);
    }

    public function processResultForGetPlayerAuthToken($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);

        // response return string if error else xml data
        $success = $this->processResultBoolean($responseResultId, $resultText, $gameUsername);
        $result = [];
        if ($success) {
            $resultXml = new SimpleXMLElement($resultText);
            $resultArr = json_decode(json_encode($resultXml), true);
            $result['auth'] = $resultArr['AuthToken'];
        }

        return array($success, $result);
    }

    public function queryPlayerBalance($playerName) {

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameUsername' => $gameUsername,
        );

        $params = array(
            'AgentUserName' => $this->agent_username,
            'AgentPassword' =>  $this->agent_password,
            'MerchantCustomerCode' => $gameUsername,
        );

        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultText, $gameUsername);

        $result = [];
        if($success) {
            $resultXml = new SimpleXMLElement($resultText);
            $resultArr = json_decode(json_encode($resultXml), true);
            $result['balance'] = $this->gameAmountToDB($resultArr['Balance']);
        }

        return array($success, $result);
    }

    public function getDataApiToken() {

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetDataApiToken',
        );

        $params = array(
            'agentUserName' => $this->agent_username,
            'agentPassword' =>  $this->agent_password,
        );

        $this->is_data_api = true;
        $apiName = self::API_generateToken;
        if($this->data_api_version==self::DATA_API_VERSION3){
            $apiName = 'authorize_v2';
        }

        return $this->callApi($apiName, $params, $context);
    }

    public function processResultForGetDataApiToken($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);

        $success = false;
        $result = [];
        if (isset($resultJson['token'])) {
            $success = true;
            $result['token'] = $resultJson['token'];
        }
        $result['response_result_id'] = $responseResultId;

        return array($success, $result);
    }

    public function depositToGame($playerName, $amount, $transfer_secure_id=null){
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');
        $external_trans_id = $transfer_secure_id ? $transfer_secure_id : $secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_trans_id
        );

        $params = array(
            'AgentUserName' => $this->agent_username,
            'AgentPassword' =>  $this->agent_password,
            'MerchantCustomerCode' => $gameUsername,
            'Amount' => $amount,
            'RefTransactionCode' => $external_trans_id,
            'BonusCode' => '',
        );

        return $this->callApi(self::API_depositToGame, $params, $context);
    }

    public function processResultForDepositToGame($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
		$statusCode = $this->getStatusCodeFromParams($params);
        $resultText = $this->getResultTextFromParams($params);

        $success = $this->processResultBoolean($responseResultId, $resultText, $gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );
        if($success) {
            $result['didnot_insert_game_logs']=true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $resultXml = new SimpleXMLElement($resultText);
            $resultArr = json_decode(json_encode($resultXml), true);

            $error_code = @$resultArr['ErrorCode'];
            switch($error_code) {
                case 'AuthenticationFailed' :
                    $result['reason_id']=self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
                    break;
                case 'MerchantIsFrozen' :
                case 'MerchantNotActive' :
                    $result['reason_id']=self::REASON_AGENT_NOT_EXISTED;
                    break;
                case 'Exception' :
                    $result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
                    break;
                case 'InsufficientFunds' :
                    $result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
                    break;
                case 'TransactionCodeNotFound' :
                    $result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
                    break;
            }

            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;

            //if it's 500 error code, we treat it as success
            if(((in_array($statusCode, $this->other_status_code_treat_as_success)) || (in_array($error_code, $this->other_status_code_treat_as_success))) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            }
        }

        return array($success, $result);
    }

    public function withdrawFromGame($playerName, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $secure_id = $this->getSecureId('transfer_request', 'secure_id', false, 'T');
        $external_trans_id = $transfer_secure_id ? $transfer_secure_id : $secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'external_transaction_id' => $external_trans_id
        );

        $params = array(
            'AgentUserName' => $this->agent_username,
            'AgentPassword' =>  $this->agent_password,
            'MerchantCustomerCode' => $gameUsername,
            'Amount' => $amount,
            'RefTransactionCode' => $external_trans_id,
            'BonusCode' => '',
        );

        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerName = $this->getVariableFromContext($params, 'playerName');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);

        $success = $this->processResultBoolean($responseResultId, $resultText, $gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );
        if($success) {
            $result['didnot_insert_game_logs']=true;
            $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {
            $resultXml = new SimpleXMLElement($resultText);
            $resultArr = json_decode(json_encode($resultXml), true);

            $error_code = @$resultArr['ErrorCode'];
            switch($error_code) {
                case 'AuthenticationFailed' :
                    $result['reason_id']=self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
                    break;
                case 'MerchantIsFrozen' :
                case 'MerchantNotActive' :
                    $result['reason_id']=self::REASON_AGENT_NOT_EXISTED;
                    break;
                case 'Exception' :
                    $result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
                    break;
                case 'InsufficientFunds' :
                    $result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
                    break;
                case 'TransactionCodeNotFound' :
                    $result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
                    break;
            }
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }
        return array($success, $result);
    }

    public function isPlayerExist($playerName) {

        $gameName = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
            'gameName' => $gameName,
        );

        $params = array(
            'AgentUserName' => $this->agent_username,
            'AgentPassword' =>  $this->agent_password,
            'MerchantCustomerCode' => $gameName,
        );

        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params) {
        $gameName = $this->getVariableFromContext($params, 'gameName');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);
        $isExist = $this->processResultBoolean($responseResultId, $resultText, $gameName);

        $success = $isExist ? false : true; // if not exist set to true

        $result['exists'] = $isExist ? true : false;

        return array($success, $result);
    }

    public function queryForwardGame($playerName, $extra = null) {
        // $lang = $this->getLauncherLanguage($extra["language"]);
        $lang = $this->getSystemInfo('language', $extra["language"]);
        $lang = $this->getLauncherLanguage($lang);


        $token_param = "operatorToken";
        if($this->use_new_version){
            $this->url_web_launch = $this->new_url_web_launch;
            $this->url_mobile_launch = $this->new_url_mobile_launch;
            $this->url_direct_launch = $this->new_url_direct_launch;
            $token_param =  $this->new_token_param;
        }

        //$is_direct_launch = false;

        # NOT LOGIN, GOTO DEMO GAME
        $url = $this->url_web_launch . "/" . $lang;
        if (!empty($extra['is_mobile'])) {
            $url = $this->url_mobile_launch . "/" . $lang . "/sports";
        }

        if(isset($extra["game_code"]) && !empty($extra["game_code"])) {
            $url = $this->url_direct_launch. "/";
            $is_direct_launch = true;
        }


        if (!empty($playerName)){
            $playerId = $this->getPlayerIdInPlayer($playerName);
            $response = $this->getPlayerAuthToken($playerName);
            if(isset($response['auth'])){
                $this->CI->load->model('external_common_tokens');
                $this->CI->external_common_tokens->setPlayerToken($playerId, $response['auth'], SBTECH_BTI_API);
                $url .= "?{$token_param}=" . $response['auth'];

                if(isset($this->odd_style_id) && !empty($this->odd_style_id)) {
                    $url .= '&oddsstyleid=' . $this->odd_style_id;
                }

                if(isset($extra["game_code"]) && !empty($extra["game_code"])) {
                    $url .= '&branchid=' . $extra["game_code"];
                }

                if($this->view_mode){
                    $url .= '&mode=' . $this->view_mode;
                }

                //OGP-22855
                /*if($this->langid){
                    $url .= '&langid=' . $this->langid;
                }*/
            }
        }

        return array(
            'success' => true,
            'url' => $url,
            'use_iframe_in_web_launch' => $this->use_iframe_in_web_launch,
            'set_min_width' => $this->set_min_width,
            'default_frame_min_width' => $this->default_frame_min_width,
        );
    }

    public function getLauncherLanguage($language){
        $lang='';
        switch ($language) {
            case Language_function::INT_LANG_ENGLISH:
            case 'en-us':
                $lang = 'en'; // english
                break;
            case Language_function::INT_LANG_CHINESE:
            case 'zh-cn':
                $lang = 'zh'; // chinese
                break;
            case Language_function::INT_LANG_INDONESIAN:
            case 'id-id':
                $lang = 'id'; // indonesia
                break;
            case Language_function::INT_LANG_VIETNAMESE:
            case 'vi-vn':
                $lang = 'vi'; // vietnamese
                break;
            case Language_function::INT_LANG_KOREAN:
            case 'ko-kr':
                $lang = 'ko'; // korean
                break;
            case Language_function::INT_LANG_THAI:
            case 'th-th':
                $lang = 'th'; // thai
                break;
            default:
                $lang = 'en'; // default as english
                break;
        }
        return $lang;
    }

    public function syncOriginalGameLogs($token = false) {

        $openSettledApi = array('openBets', self::API_queryGameRecords);
        if($this->data_api_version==self::DATA_API_VERSION3){
            $openSettledApi = array('get-open-bets-paging', 'get-history-bets-paging');
        }
        $rtn = array();
        foreach($openSettledApi as $api) {
            if (!$this->sync_open_bets && ($api == 'openBets' || $api == 'get-open-bets-paging')) {  # make sure sync open bets
                continue;
           }

            $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
            $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

            $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
            $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
            $startDate->modify($this->getDatetimeAdjust());

            //observer the date format
            $queryDateTimeStart = $startDate->format('Y-m-d H:i:s');
            $queryDateTimeEnd = $startDate->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
            $queryDateTimeMax = $endDate->format('Y-m-d H:i:s');

            $this->max_row_page = $this->getSystemInfo('max_row_page', 1000);
            $this->max_row_page_break = $this->getSystemInfo('max_row_page_break', 20);

            while ($queryDateTimeMax  > $queryDateTimeStart) {

                $startDateParam=new DateTime($queryDateTimeStart);
                if($queryDateTimeEnd>$queryDateTimeMax){
                    $endDateParam=new DateTime($queryDateTimeMax);
                }else{
                    $endDateParam=new DateTime($queryDateTimeEnd);
                }
                $startDateParam = $startDateParam->format('Y-m-d\TH:i:s');
                $endDateParam = $endDateParam->format('Y-m-d\TH:i:s');

                $result = $this->getDataApiToken();
                if ($result['success']) {
                    $this->token = $result['token'];
                }

                $context = array(
                    'callback_obj' => $this,
                    'callback_method' => 'processResultForSyncGameRecords',
                    'startDate' => $startDateParam,
                    'endDate' => $endDateParam,
                    'isOpenBets' => $api == 'openBets' ? true : false  # check if open bets api
                );

                $initialPage = 0;


                $params = array(
                    'From' => $startDateParam,
                    'To' => $endDateParam,
                );

                $this->is_data_api = true;
                
                if($this->data_api_version==self::DATA_API_VERSION2 && $api <> 'openBets'){
                    $params['pagination'] = ['page'=>$initialPage, 'rowperpage'=>$this->max_row_page];
                    $rtn[] = $return = $this->callApi($api, $params, $context);                    
                    $totalPages = isset($return['settledLogs'])&&isset($return['settledLogs']['totalPages'])?$return['settledLogs']['totalPages']:-1;
                    if($totalPages>0){                        
                        for($page=1;$page<=$totalPages&&$page<=$this->max_row_page_break;$page++){
                            $params['pagination'] = ['page'=>$page, 'rowperpage'=>$this->max_row_page];
                            $rtn[] = $subreturn = $this->callApi($api, $params, $context);                            
                        }
                    }
                }elseif($this->data_api_version==self::DATA_API_VERSION3){
                    $params['pagination'] = ['page'=>$initialPage, 'rowperpage'=>$this->max_row_page];
                    $rtn[] = $return = $this->callApi($api, $params, $context);                    
                    $totalPages = isset($return['settledLogs'])&&isset($return['settledLogs']['totalPages'])?$return['settledLogs']['totalPages']:-1;
                    if($totalPages>0){                        
                        for($page=1;$page<=$totalPages&&$page<=$this->max_row_page_break;$page++){
                            $params['pagination'] = ['page'=>$page, 'rowperpage'=>$this->max_row_page];
                            $rtn[] = $subreturn = $this->callApi($api, $params, $context);                            
                        }
                    }
                }else{
                    $rtn[] = $return = $this->callApi($api, $params, $context);                    
                }

                $this->utils->info_log(__METHOD__, [
                    'sync_time_interval' => $this->sync_time_interval,
                    'sync_sleep_time' => $this->sync_sleep_time,
                    'from' => $startDateParam,
                    'to' => $endDateParam,
                ]);

                // sleep count down
                for ($x = $this->sync_sleep_time; $x >= 1; $x--) {
                    $this->utils->info_log(__METHOD__, [
                        'sleep count down' => $x,
                    ]);

                    sleep(1);
                }

                $queryDateTimeStart = (new DateTime($endDateParam))->format('Y-m-d H:i:s');
                $queryDateTimeEnd  = (new DateTime($queryDateTimeStart))->modify($this->sync_time_interval)->format('Y-m-d H:i:s');
            }
        }

        return array("success"=>true,"sync_details" => $rtn);
    }

    private function rebuildSbtechBtiGameRecords(&$gameRecords,$extra){

        $new_gameRecords = [];
        foreach($gameRecords as $index => $logs) {
            $selections = '';
            $branch_name = '';
            $bet_id = '';
            if (!empty($logs['Selections'])){
                foreach($logs['Selections'] as $bet_detail) {
                    $branch_name = $bet_detail['BranchName'];
                }
                $bet_id = $logs['Selections'][0]['BetID'];

                $selections = json_encode($logs['Selections']);
            }

            $purchase_id = isset($logs['PurchaseID']) ? $logs['PurchaseID'] : null;

            # create own unique id ( Purchase ID + Bet ID )
            $unique_id = $purchase_id . '-' . $bet_id;

            $new_gameRecords[$index]['gain'] = isset($logs['Gain']) ? $logs['Gain']:0;
            $new_gameRecords[$index]['pl'] = isset($logs['PL']) ? $logs['PL'] : 0;  # set to 0 if open bets
            $new_gameRecords[$index]['non_cashout_amount'] = isset($logs['NonCashOutAmount']) ? $logs['NonCashOutAmount'] : 0;
            $new_gameRecords[$index]['combo_bonus_amount'] = isset($logs['ComboBonusAmount']) ? $logs['ComboBonusAmount'] : 0;
            $new_gameRecords[$index]['purchase_id'] = $purchase_id;
            $new_gameRecords[$index]['update_date'] = isset($logs['UpdateDate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($logs['UpdateDate']))) : null;
            $new_gameRecords[$index]['odds'] = isset($logs['Odds']) ? $logs['Odds'] : null;
            $new_gameRecords[$index]['odds_in_user_style'] = isset($logs['OddsInUserStyle']) ? $logs['OddsInUserStyle'] : null;
            $new_gameRecords[$index]['return'] = isset($logs['Return']) ? $logs['Return'] : null;
            $new_gameRecords[$index]['domain_id'] = isset($logs['DomainID']) ? $logs['DomainID'] : null;
            $new_gameRecords[$index]['ip'] = isset($logs['IP']) ? $logs['IP'] : null;
            $new_gameRecords[$index]['bet_status'] = isset($logs['BetStatus']) ? $logs['BetStatus'] : null;
            $new_gameRecords[$index]['odds_style_of_user'] = isset($logs['OddsStyleOfUser']) ? $logs['OddsStyleOfUser'] : null;
            $new_gameRecords[$index]['total_stake'] = isset($logs['TotalStake']) ? $logs['TotalStake'] : null;
            $new_gameRecords[$index]['odds_dec'] = isset($logs['OddsDec']) ? $logs['OddsDec'] : null;
            $new_gameRecords[$index]['system_name'] = isset($logs['SystemName']) ? $logs['SystemName'] : null;
            $new_gameRecords[$index]['platform'] = isset($logs['Platform']) ? $logs['Platform'] : null;

            $username = isset($logs['UserName']) ? $logs['UserName'] : null;
            if (substr($username, 0, strlen($this->original_username_prefix)) == $this->original_username_prefix) {
                $username = substr($username, strlen($this->original_username_prefix));
            }
            $new_gameRecords[$index]['username'] = $username;

            $new_gameRecords[$index]['bet_type_name'] = isset($logs['BetTypeName']) ? $logs['BetTypeName'] : null;
            $new_gameRecords[$index]['bet_type_id'] = isset($logs['BetTypeId']) ? $logs['BetTypeId'] : 0;
            $new_gameRecords[$index]['creation_date'] = isset($logs['CreationDate']) ?  $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($logs['CreationDate']))) : null;
            $new_gameRecords[$index]['status'] = isset($logs['Status']) ? $logs['Status'] : null;
            $new_gameRecords[$index]['customer_id'] = isset($logs['CustomerID']) ? $logs['CustomerID'] : null;
            $new_gameRecords[$index]['merchant_customer_id'] = isset($logs['MerchantCustomerID']) ? $logs['MerchantCustomerID'] : null;
            $new_gameRecords[$index]['currency'] = isset($logs['Currency']) ? $logs['Currency'] : null;
            $new_gameRecords[$index]['player_level_id'] = isset($logs['PlayerLevelID']) ? $logs['PlayerLevelID'] : null;
            $new_gameRecords[$index]['player_level_name'] = isset($logs['PlayerLevelName']) ? $logs['PlayerLevelName'] : null;
            $new_gameRecords[$index]['selections'] = isset($logs['Selections']) ? $selections : null;
            $new_gameRecords[$index]['branch_name'] = $branch_name;
            $new_gameRecords[$index]['validStake'] = isset($logs['ValidStake']) ? $logs['ValidStake'] : null;
            $new_gameRecords[$index]['freebet_amount'] = isset($logs['FreeBet']['Amount']) ? $logs['FreeBet']['Amount'] : 0;
            $new_gameRecords[$index]['freebet_isriskfreebet'] = isset($logs['FreeBet']['IsRiskFreeBet']) ? $logs['FreeBet']['IsRiskFreeBet'] : 0;
            $new_gameRecords[$index]['real_money_amount'] = isset($logs['RealMoneyAmount']) ? $logs['RealMoneyAmount'] : 0;

            // if empty use append bet time in finish game time
            if (isset($logs['BetSettledDate'])) {
                $new_gameRecords[$index]['bet_settled_date'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($logs['BetSettledDate'])));
            } else {
                $new_gameRecords[$index]['bet_settled_date'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($logs['CreationDate'])));
            }

            //extra info from SBE
            $new_gameRecords[$index]['external_uniqueid'] = $unique_id;
            $new_gameRecords[$index]['response_result_id'] = $extra['response_result_id'];
        }

        $gameRecords = $new_gameRecords;
    }

    public function processResultForSyncGameRecords($params) {
        $this->CI->load->model('original_game_logs_model');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);

        $isOpenBets	= $this->getVariableFromContext($params, 'isOpenBets');
        # bet settled date
        $startDate 	= $this->getVariableFromContext($params, 'startDate');
        $endDate 	= $this->getVariableFromContext($params, 'endDate');
        $result = array('data_count'=>0);
        $success = false;

        if(isset($resultJson['errorCode']) && $resultJson['errorCode'] == self::SUCCESS_CODE) {
            $extra = ['response_result_id'=>$responseResultId];
            $success = true;
            $gameRecords = $resultJson['Bets'];

            if (!empty($gameRecords)) {
                $this->rebuildSbtechBtiGameRecords($gameRecords,$extra);
                list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    'sbtech_bti_game_logs',
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

                if (!empty($insertRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                        ['responseResultId'=>$responseResultId]);
                }
                unset($insertRows);

                if (!empty($updateRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                        ['responseResultId'=>$responseResultId]);
                }
                unset($updateRows);
            }

        }

        if($isOpenBets) {
            $result['openBets'] = $resultJson;
        } else {
            $resultJson['settledLogs'] = $resultJson;
        }

        return array($success, $resultJson);
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($rows)){
            $responseResultId=$additionalInfo['responseResultId'];
            foreach ($rows as $record) {
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal('sbtech_bti_game_logs', $record);
                } else {
                    unset($record['id']);
                    $this->CI->original_game_logs_model->insertRowsToOriginal('sbtech_bti_game_logs', $record);
                }
                $dataCount++;
                unset($data);
            }
        }

        return $dataCount;
    }

    public function changePassword($playerName, $oldPassword = null, $newPassword) {
        return $this->returnUnimplemented();
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

    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        //$sqlTime='sbtech.bet_settled_date >= ? and sbtech.bet_settled_date <= ?';
        $sqlTime='sbtech.update_date >= ? and sbtech.update_date <= ?';
        if($use_bet_time){
            $sqlTime='sbtech.creation_date >= ? and sbtech.creation_date <= ?';
        }

        $sql = <<<EOD
SELECT
sbtech.id as sync_index,
sbtech.id,
sbtech.response_result_id,
sbtech.gain,
sbtech.pl,
sbtech.non_cashout_amount,
sbtech.combo_bonus_amount,
sbtech.bet_settled_date,
sbtech.update_date,
sbtech.creation_date,
sbtech.odds,
sbtech.odds_in_user_style,
sbtech.odds_style_of_user,
sbtech.total_stake,
sbtech.bet_status,
sbtech.username,
sbtech.status,
sbtech.selections,
sbtech.bet_settled_date AS end_time,
sbtech.creation_date AS bet_time,
sbtech.bet_type_name,
sbtech.purchase_id,
sbtech.validStake as real_bet_amount,
sbtech.md5_sum,
sbtech.branch_name as game_code,
sbtech.branch_name as game,
sbtech.external_uniqueid,
(sbtech.pl + sbtech.combo_bonus_amount) as result_amount,

game_provider_auth.player_id,
gd.id as game_description_id,
gd.game_type_id

FROM sbtech_bti_game_logs as sbtech

left JOIN game_description as gd ON sbtech.branch_name = gd.game_code and gd.game_platform_id=?
JOIN game_provider_auth ON sbtech.username = game_provider_auth.login_name and game_provider_auth.game_provider_id=?

WHERE

{$sqlTime}

EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(), $dateFrom,$dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        return $result;
    }

    public function generateMarket($selections){
        $selections = json_decode($selections,true);
        $market_list = array();
        if(!empty($selections)){
            foreach ($selections as $key => $selection) {
                if(isset($selection['LineTypeName']) && $selection['EventTypeName']){
                    $selected_market = $selection['EventTypeName'];
                    if($selection['LineTypeName'] != $selection['EventTypeName']){
                        $selected_market = $selection['LineTypeName'] . " " .  $selection['EventTypeName'];
                    }
                    if(!in_array($selected_market, $market_list)){
                        $market_list[] = $selected_market;
                    }
                }

            }
        }
        if(!empty($market_list)){
            $count = count($market_list);
            if($count > 1){
                return $count . " " . lang("Markets");
            } else {
                return $market_list[0];
            }
        }
        return "";

    }

    public function getOdds($selections){
        $selections = json_decode($selections,true);
        $odds = 0;
        if(!empty($selections)){
            foreach ($selections as $key => $selection) {
                if(isset($selection['OddsDec'])){
                    $odds+=$selection['OddsDec'];
                }
            }
        }
        return $odds;
    }

    public function checkScoreStr($score_str){
        if(preg_match('/( : )/', $score_str, $matches)){
           $score_str =  "FT: ". str_replace(":","-",$score_str);
        }
        return $score_str;
    }

    public function generateMatchDetails($selections){
        $selections = json_decode($selections,true);
        $scores = null;
        $count = 1;
        $is_parlay = (count($selections) > 1 ) ? TRUE : FALSE;
        if(!empty($selections)){
            foreach ($selections as $key => $selection) {
                if(isset($selection['Score'])){
                    // $this->checkScoreStr($selection['Score']);

                    if($is_parlay){
                        if(!empty($scores)){
                            $scores .= "<br>";
                        }

                        if(!empty($selection['Score'])){
                            $scores .= $count. ". " . $this->checkScoreStr($selection['Score']);
                        } else {
                            $scores .= $count. ". " . lang("No score.");
                        }

                        $count++;
                    } else {
                        if(!empty($selection['Score'])){
                            $scores = $this->checkScoreStr($selection['Score']);
                        } else {
                            $scores = lang("No score.");
                        }
                    }
                }
            }
        }

        return $scores;
    }

    const STATUS_DRAW = 'draw';

    public function getOddsType($key = null){
        $array = array(
            "2" => lang("MY"),//Malay Odds
            "4" => lang("HK"),//Hongkong Odds
            "1" => lang("DEC"),//Decimal Odd
            "3" => lang("IN"),//INdo Odds
            "5" => lang("US"),//American Odds
            "6" => lang("FR"),//Fractional Odds
        );
        if(isset($array[$key])){
            return $array[$key];
        } else {
            return null;
        }

    }


    const EUROPEAN_ODDS = 1;
    const MALAY_ODDS = 2;
    const INDO_ODDS = 3;
    const HONGKONG_ODDS = 4;
    const AMERICAN_ODDS = 5;
    const FRACTIONAL_ODDS = 6;

    private function mapOddsType($string){
        switch (strtolower($string)) {
            case 'european':
                return self::EUROPEAN_ODDS;
                break;
            case 'malay':
                return self::MALAY_ODDS;
                break;
            case 'indo':
                return self::INDO_ODDS;
                break;
            case 'hongkong':
                return self::HONGKONG_ODDS;
                break;
            case 'american':
                return self::AMERICAN_ODDS;
                break;
            case 'fractional':
                return self::FRACTIONAL_ODDS;
                break;
            default:
                return null;
                break;
        }
    }

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){
        $extra_info=[
            "note" => $row['note'],
            "odds" => $row['odds_in_user_style'],
            "odds_type" => $this->mapOddsType($row['odds_style_of_user']),
            "match_details" => $this->generateMatchDetails($row['selections']),
            "match_type" => $this->generateMarket($row['selections'])
        ];

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $logs_info = [
            'game_info'=>['game_type_id'=>$row['game_type_id'], 'game_description_id'=>$row['game_description_id'],
                'game_code'=>$row['game_code'], 'game_type'=>null, 'game'=>$row['game']],
            'player_info'=>['player_id'=>$row['player_id'], 'player_username'=>$row['username']],
            'amount_info'=>['bet_amount'=>$row['bet_amount'], 'result_amount'=>$row['result_amount'],
                'bet_for_cashback'=>$row['bet_amount'], 'real_betting_amount'=>$row['real_betting_amount'],
                'win_amount'=>null, 'loss_amount'=>null, 'after_balance'=>null],
            'date_info'=>['start_at'=>$row['bet_time'], 'end_at'=>$row['end_time'], 'bet_at'=>$row['bet_time'],
                'updated_at'=>$row['update_date']],
            'flag'=>Game_logs::FLAG_GAME,
            'status'=>$row['status'],
            'additional_info'=>['has_both_side'=>0, 'external_uniqueid'=>$row['external_uniqueid'], 'round_number'=>$row['external_uniqueid'],
                'md5_sum'=>$row['md5_sum'], 'response_result_id'=>$row['response_result_id'], 'sync_index'=>$row['sync_index'],
                'bet_type'=>null ],
            'bet_details'=>$row['bet_details'],
            'extra'=>$extra_info,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $logs_info;
    }

    private function getGameDescriptionInfo($row, $unknownGame) {
        $game_description_id = null;

        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $row['game_code']);

        $game_type_id = $unknownGame->game_type_id;
        $game_type = $unknownGame->game_name;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    public function preprocessOriginalRowForGameLogs(array &$row){
        $game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];
        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }
        $row['game_description_id']=$game_description_id;
        $row['game_type_id']=$game_type_id;

        $row['real_betting_amount'] = $row['total_stake'];
        $row['bet_amount'] = $row['real_bet_amount'];
        if(strtolower($row['status']) == self::STATUS_DRAW){
            $row['bet_amount'] = 0;
        }
        $row['status'] =  $this->getGameRecordsStatus($row['status']);
        $row['bet_details']= array('sports_bet' => $this->betDetails($row));

        ###### START PROCESS BET AMOUNT CONDITIONS
        # get bet conditions for status
        $betConditionsParams = [];
        $betConditionsParams['bet_status'] = strtolower(str_replace(' ', '_', $row['bet_status']));

        # get bet conditions for win/loss
        $betConditionsParams['win_loss_status'] = null;
        $betConditionsParams['odds_status'] = null;

        if($row['bet_amount']>0){
            if ($row['result_amount'] < 0) {
                if ((abs($row['result_amount']) / $row['bet_amount']) == .5 ) {
                    $betConditionsParams['win_loss_status'] = 'half_lose';
                }
            } else {
                if (($row['result_amount'] / $row['bet_amount']) == .5 ) {
                    $betConditionsParams['win_loss_status'] = 'half_win';
                }
            }
        }

        # get bet conditions for odds
        $oddsType = $this->getUnifiedOddsType(strtolower($row['odds_style_of_user']));
        $betConditionsParams['valid_bet_amount'] = $row['bet_amount'];
        $betConditionsParams['bet_amount_for_cashback'] = $row['bet_amount'];
        $betConditionsParams['real_betting_amount'] = $row['real_betting_amount'];
        $betConditionsParams['odds_type'] = $oddsType;
        $betConditionsParams['odds_amount'] = $row['odds_in_user_style'];

        list($_appliedBetRules, $_validBetAmount, $_betAmountForCashback, $_realBettingAmount, $_betconditionsDetails, $note) = $this->processBetAmountByConditions($betConditionsParams);

        if (!empty($_appliedBetRules)) {
            $row['bet_amount'] = $_validBetAmount;
            $row['bet_for_cashback'] = $_betAmountForCashback;
            $row['real_betting_amount'] = $_realBettingAmount;
            $row['note'] = $note;
        } else {
            $row['note'] = ($row['bet_amount'] == 0) ? lang('draw') : null;
        }
        ###### /END PROCESS BET AMOUNT CONDITIONS
    }

    const BRANCH_TENNIS = 'tennis';
    const EVENT_TENNIS_SET = ['1st set', '2nd set', '3rd set'];

    public function generateTennisScoreForSet($strScore, $type){
        if(!empty($strScore)){
            $array_score = str_split(strrev($strScore));//reverse string to start from the right

            $serve = isset($array_score[0]) ? $array_score[0] : 0;
            $point1 = isset($array_score[1]) ? $array_score[1] : 0;
            $point2 = isset($array_score[2]) ? $array_score[2] : 0;
            $set1 = isset($array_score[3]) ? $array_score[3] : 0;
            $set2 = isset($array_score[4]) ? $array_score[4] : 0;
            $set3 = isset($array_score[5]) ? $array_score[5] : 0;

            switch (strtolower($type)) {
                case '1st set':
                    $score = $set1;
                    break;
                case '2nd set':
                    $score = $set2;
                    break;
                case '3rd set':
                    $score = $set3;
                    break;
                default:
                    $score = 0;
                    break;
            }
            return $score;
        }
        return 0;
    }

    public function betDetails($data) {
        $bet_details = [];
        $selections = json_decode($data['selections'], true);
        if(!empty($selections)){
            foreach($selections as $key => $game) {
                $bet_details[$key] = array(
                    'Your Bet' => $game['YourBet'],
                    'Is Live' => $game['IsLive'] == 1 ? 'Live!' : 'Not Live',
                    'Odd' => $game['OddsInUserStyle'],
                    'Hdp' => 'N/A',
                    'Bet Score'=> $game['LiveScore1'] . " : " . $game['LiveScore2'],
                    'Settlement Score'=> $game['Score'],
                    'Event' => $game['HomeTeam'].' vs '.$game['AwayTeam'],
                    'League' => $game['LeagueName'],
                );

                if((strtolower($game['BranchName']) == self::BRANCH_TENNIS ) && in_array(strtolower($game['EventTypeName']), self::EVENT_TENNIS_SET)){
                    $liveScore1 = $this->generateTennisScoreForSet($game['LiveScore1'], $game['EventTypeName']);
                    $liveScore2 = $this->generateTennisScoreForSet($game['LiveScore2'], $game['EventTypeName']);
                    $bet_details[$key]['Bet Score'] = $liveScore1 . " : " . $liveScore2; //override bet score if tennis and place bet per set
                }

                if(empty($game['HomeTeam']) || empty($game['AwayTeam'])){
                    if(isset($game['LeagueName']) && !empty($game['LeagueName'])){
                        $bet_details[$key]['Event'] = $game['LeagueName'];//override event key if home and way team key is empty
                    }
                }

                if(!empty($this->bet_detail_remove_selection)){
                    foreach ($this->bet_detail_remove_selection as $selection) {
                        if(isset($bet_details[$key][$selection])){
                            unset($bet_details[$key][$selection]);
                        }
                    }
                }
            }
        }

        return $bet_details;
    }

    private function getGameRecordsStatus($status) {
        $this->CI->load->model(array('game_logs'));
        $status = strtolower($status);

        switch ($status) {
            case 'opened':
            case 'open':
                $status = Game_logs::STATUS_ACCEPTED;
                break;
            case 'canceled':
                $status = Game_logs::STATUS_CANCELLED;
                break;
            case 'won':
            case 'half won':
            case 'draw':
            case 'lost':
            case 'half lost':
            case 'cashout':
                $status = Game_logs::STATUS_SETTLED;
                break;
        }
        return $status;
    }

    public function batchQueryPlayerBalance($playerNames, $syncId = null) {

        if (empty($playerNames)) {
            $playerNames = $this->getAllGameUsernames();
        }

        return $this->batchQueryPlayerBalanceOneByOne($playerNames, $syncId);

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


    public function login($username, $password = null) {
        return $this->returnUnimplemented();
    }

    public function processResultForgetVendorId($params) {
        return $this->returnUnimplemented();
    }

    public function queryTransaction($transactionId, $extra) {
        $playerName=$extra['playerName'];
        $playerId=$extra['playerId'];
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'gameUsername' => $gameUsername,
            'playerName' => $playerName,
            'playerId'=>$playerId,
            'external_transaction_id' => $transactionId
        );

        $params = array(
            'AgentUserName' => $this->agent_username,
            'AgentPassword' =>  $this->agent_password,
            'RefTransactionCode' => $transactionId,
        );

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction( $params ){
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultText = $this->getResultTextFromParams($params);

        $success = $this->processResultBoolean($responseResultId, $resultText, $gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=>$external_transaction_id,
            'status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        $resultXml = new SimpleXMLElement($resultText);
        $resultArr = json_decode(json_encode($resultXml), true);

        if($success) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        } else {

            $error_code = @$resultArr['ErrorCode'];
            switch($error_code) {
                case 'AuthenticationFailed' :
                    $result['reason_id']=self::REASON_GAME_PROVIDER_ACCOUNT_PROBLEM;
                    break;
                case 'MerchantIsFrozen' :
                case 'MerchantNotActive' :
                    $result['reason_id']=self::REASON_AGENT_NOT_EXISTED;
                    break;
                case 'Exception' :
                    $result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
                    break;
                case 'InsufficientFunds' :
                    $result['reason_id']=self::REASON_NO_ENOUGH_BALANCE;
                    break;
                case 'TransactionCodeNotFound' :
                    $result['reason_id']=self::REASON_INVALID_TRANSACTION_ID;
                    break;
            }

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
        // return array("success" => true);
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

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return array(false, null);
    }

    public function getbetbyPurchaseID($purchase_id) {

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetbetbyPurchaseID',
            'purchase_id' => $purchase_id,
        );

        $result = $this->getDataApiToken();

        if ($result['success']) {
            $this->token = $result['token'];
        }

        $params = [
            'PurchaseID' => $purchase_id,
        ];

        $this->is_data_api = true;

        if ($this->data_api_version == self::DATA_API_VERSION3) {
            $api_name = 'get-bet-by-id';
        } else {
            $api_name = self::API_getbetbyPurchaseID;
        }

        return $this->callApi($api_name, $params, $context);
    }

    public function processResultForGetbetbyPurchaseID($params) {
        $this->CI->load->model('original_game_logs_model');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJson = $this->getResultJsonFromParams($params);
        $success = false;

        $result = [
            'data_count' => 0
        ];

        if (isset($resultJson['errorCode']) && $resultJson['errorCode'] == self::SUCCESS_CODE) {
            $success = true;

            $extra = [
                'response_result_id' => $responseResultId
            ];

            $gameRecords = $resultJson['Bets'];

            if (!empty($gameRecords)) {
                $this->rebuildSbtechBtiGameRecords($gameRecords, $extra);

                list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    'sbtech_bti_game_logs',
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

                if (!empty($insertRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($insertRows, 'insert',
                        ['responseResultId'=>$responseResultId]);
                }

                unset($insertRows);

                if (!empty($updateRows)) {
                    $result['data_count'] += $this->updateOrInsertOriginalGameLogs($updateRows, 'update',
                        ['responseResultId'=>$responseResultId]);
                }

                unset($updateRows);
            }
        }

        $resultJson['settledLogs'] = $resultJson;

        return array($success, $resultJson);
    }
}

/*end of file*/