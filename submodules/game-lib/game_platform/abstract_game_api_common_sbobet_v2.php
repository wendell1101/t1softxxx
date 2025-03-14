<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
require_once dirname(__FILE__).'/sbobet_bet_module.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';
/**
* Game Provider: SBOBET
* Game Type: Casino ,sports, virtual sports, games(poker and other games), seamless game(not incuded)
* Wallet Type: Transfer
*
* @category Game_platform
* @version not specified
* @integrator @jerbey.php.ph
**/

abstract class Abstract_game_api_common_sbobet_v2 extends Abstract_game_api {
    use sbobet_bet_module;

	const ODDS_TYPES = ['hk', 'eu'];

    //constant for processing agent
    const API_register_agent = '/web-root/restricted/agent/register-agent.aspx';
    const API_update_agent_bet_settings = '/web-root/restricted/agent/update-agent-preset-bet-settings.aspx';
    const API_update_player_bet_settings = '/web-root/restricted/player/update-player-bet-settings.aspx';

    //constant report syncing
    const API_GET_BETLIST_BY_TRANSACTION_DATE = '/web-root/restricted/report/get-bet-list-by-transaction-date.aspx';
    const API_GET_BETLIST_BY_MODIFY_DATE = '/web-root/restricted/report/get-bet-list-by-modify-date.aspx';

    //others
    const API_update_bet_settings_by_sportid_and_marketype = '/web-root/restricted/player/update-player-bet-setting-by-sportid-and-markettype.aspx';
    const API_get_member_bet_settings_by_sportid_and_marketype = '/web-root/restricted/player/get-member-bet-settings-with-sportid-and-markettype.aspx';

    const URI_MAP = array(
        self::API_createPlayer => '/web-root/restricted/player/register-player.aspx',
        self::API_isPlayerExist => '/web-root/restricted/player/get-player-balance.aspx',
        self::API_login => '/web-root/restricted/player/login.aspx',
        self::API_logout => '/web-root/restricted/player/logout.aspx',
        self::API_queryPlayerBalance => '/web-root/restricted/player/get-player-balance.aspx',
        self::API_depositToGame => '/web-root/restricted/player/deposit.aspx',
        self::API_withdrawFromGame => '/web-root/restricted/player/withdraw.aspx',
        self::API_syncGameRecords => 'BetRecord',
        self::API_batchQueryPlayerBalance => 'CheckUsrBalance',
        self::API_queryTransaction => '/web-root/restricted/player/check-transaction-status.aspx',
    );

    const SUCCESS_API_CODE = 0;
    const ERROR_CODE_USER_EXIST = 4103;
    const ERROR_CODE_USER_DOESNT_EXIST = 3303;
    const IS_PARLAY = "Mix Parlay";

    const MD5_FIELDS_FOR_ORIGINAL = [
        'order_time',
        'modify_date',
        'winlost_date',
        'currency',
        'status',
        'bet_details',
        'win_lost',
        'stake',
        'actual_stake',
        'turnover',
        'turnover_by_stake',
        'turnover_by_actual_stake',
        'net_turnover_by_stake',
        'net_turnover_by_actual_stake',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS = [
        'stake',
        'actual_stake',
        'win_lost',
        'turnover',
        'turnover_by_stake',
        'turnover_by_actual_stake',
        'net_turnover_by_stake',
        'net_turnover_by_actual_stake',
    ];

    const GAME_STATUS_RUNNING = 'running';

    //portfolio code for game login
    const PORTFORLIO_SPORTSBOOK = 'sportsbook';
    const PORTFORLIO_CASINO = 'casino';
    const PORTFORLIO_GAMES = 'games';
    const PORTFORLIO_VIRTUALSPORTS = 'virtualsports';
    const PORTFORLIO_SEAMLESSGAME = 'seamlessgame';

    # Don't ignore on refresh 
    const DEFAULT_IGNORED_0_ON_REFRESH = FALSE;

    public function __construct() {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->currency = $this->getSystemInfo('currency');
        $this->language = $this->getSystemInfo('language','zh-cn');
        $this->agent_code = $this->getSystemInfo('agent_code');
        $this->agent_password = $this->getSystemInfo('agent_password');
        $this->company_key = $this->getSystemInfo('key');
        $this->server_id = $this->getSystemInfo('server_id');
        $this->gm_online_id = $this->getSystemInfo('gm_online_id');
        $this->casino_url = $this->getSystemInfo('casino_url');
        $this->sportsbook_url = $this->getSystemInfo('sportsbook_url');
        $this->virtualsports_url = $this->getSystemInfo('virtualsports_url');
        $this->theme = $this->getSystemInfo('theme','SBO');
        $this->m_theme = $this->getSystemInfo('m_theme','SBO');
        $this->minimum_bet = $this->getSystemInfo('minimum_bet',1);
        $this->maximum_bet = $this->getSystemInfo('maximum_bet',1000000);
        $this->maxPerMatch = $this->getSystemInfo('maxPerMatch',1000000);
        $this->casino_table_limit = $this->getSystemInfo('casino_table_limit',4);
        $this->is_agent_created = $this->getSystemInfo('is_agent_created');
        $this->is_agent_updated = $this->getSystemInfo('is_agent_updated');
        $this->domain = $this->getSystemInfo('domain');
        $this->update_domain = $this->getSystemInfo('update_domain');
        $this->odds_style = $this->getSystemInfo('odds_style','MY');
        $this->oddsmode = $this->getSystemInfo('oddsmode','double');
        $this->redirect = $this->getSystemInfo('redirect',false);
        $this->client_portfolios = $this->getSystemInfo('client_portfolios',['SportsBook','Casino','VirtualSports','Games','SeamlessGame']);
        $this->enable_game_language = $this->getSystemInfo('enable_game_language',false);
        $this->use_extra_info_date_field = $this->getSystemInfo('use_extra_info_date_field', false);
        $this->extra_info_date_field = $this->getSystemInfo('extra_info_date_field', 'updated_at');
        $this->use_winlost_date_on_end_at = $this->getSystemInfo('use_winlost_date_on_end_at', false);
        $this->use_winlost_date_on_end_at_portfolio = $this->getSystemInfo('use_winlost_date_on_end_at_portfolio',['sportsbook']);
        $this->force_https_for_game_launch = $this->getSystemInfo('force_https_for_game_launch', false);

        $this->is_wap_sports = $this->getSystemInfo('is_wap_sports', false);
    }

    public function getPlatformCode() {
        return $this->returnUnimplemented();
    }

    public function generateUrl($apiName, $params) {
        $url = $this->api_url.$params['method'];
        // echo $url;
        return  str_replace(" ","",$url);
    }

    public function getHttpHeaders($params){
        return array("Content-Type" => "application/json");
    }

    protected function customHttpCall($ch, $params) {
        unset($params["method"]); //unset action not need on params
        // echo json_encode($params, true);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, true));
        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    }

    public function afterProcessResult($apiName, $params, $responseResultId, $resultText, $statusCode, $statusText = null, $extra = null, $resultObj = null) {
        return array(false, null);
    }

    public function processResultBoolean($responseResultId, $resultArr, $gameUsername = null) {
        $success = ($resultArr['error']['id']==0) ? true : false;

        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('SBOBET API got error ======================================>', $responseResultId, 'gameUsername', $gameUsername, 'result', $resultArr);
        }
        return $success;
    }

    #============================================ Prerequisite functions (needed for api setup) =========================================

    /**
     * overview : Create agent using extra info data
     *
     * @return array
     * command sudo ./command.sh create_agent_on_api <platform>
     */
    public function createAgent() {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreateAgent',
            'agent' => $this->agent_code,
        );

        $params = array(
            "CompanyKey"    => $this->company_key,
            "ServerId"      => $this->server_id,
            "Username"      => $this->agent_code,
            "Password"		=> $this->agent_password,
            "currency"      => $this->currency,
            "Min"           => $this->minimum_bet,
            "Max"           => $this->maximum_bet,
            "MaxPerMatch"   => $this->maxPerMatch,
            "CasinoTableLimit" => $this->casino_table_limit,
            "method"        => self::API_register_agent
        );

        $this->CI->utils->debug_log("CreateAgent params ============================>", $params);
        return $this->callApi(self::API_register_agent, $params, $context);
    }

    public function processResultForCreateAgent($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $success =  ($resultJsonArr['error']['id']==0) ? true : false;
        if (!$success) {
            $this->setResponseResultToError($responseResultId);
            $this->CI->utils->debug_log('SBOBET create agent got error ======================================>', $responseResultId, 'agent', $this->agent_code, 'result', $resultJsonArr);
            //$success = false;
        }
        //only success allow on update external info json
        if($success){
           $data = array(
                "is_agent_created" => $success,
            );
            $update = $this->updateExternalSystemExtraInfo($this->getPlatformCode(),$data); 
        }
        
        //$this->updateAgentDomain();
        return array(true, $resultJsonArr);

    }

    public function updateAgentBetSettings() {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForUpdateAgentBetSettings',
            'agent' => $this->agent_code,
        );

        $params = array(
            "CompanyKey"    => $this->company_key,
            "ServerId"      => $this->server_id,
            "Username"      => $this->agent_code,
            "Min"           => $this->minimum_bet,
            "Max"           => $this->maximum_bet,
            "MaxPerMatch"   => $this->maxPerMatch,
            "CasinoTableLimit" => $this->casino_table_limit,
            "method"        => self::API_update_agent_bet_settings
        );
        $this->CI->utils->debug_log("updateAgentBetSettings params ============================>", $params);
        return $this->callApi(self::API_update_agent_bet_settings, $params, $context);
    }

    public function processResultForUpdateAgentBetSettings($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('SBOBET updateAgentBetSettings result ======================================>','result', $resultJsonArr);
        $success = (($resultJsonArr['error']['id']) == 0 ) ? true: false;
        //only success allow on update external info json
        if($success){
            $data = array(
                "is_agent_updated" => $success,
            );
            $update = $this->updateExternalSystemExtraInfo($this->getPlatformCode(),$data);
        }
        
        return array(true, $resultJsonArr);
    }

    /**
     * This function is for updating bet settings of a player by sport type and market type in white label system.
     * MaxPerMatch have to greater than / equal to max bet and max bet have to greater than / equal to min bet.
     * Enter an agent name will update all existing players' bet setting under that agent.
     * OGP-12321
     */
    
    public function updatePlayerBetSettingsBySportTypeAndMarketType($username,$sport_type,$market_type,$min_bet,$max_bet,$maxPerMatch) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForUpdatePlayerBetSettingsBySportTypeAndMarketType',
            'agent' => $this->agent_code,
        );
        $sport_type = $sport_type != null? $sport_type : 0 ;// 0 = All sports type
        $market_type = $market_type != null? $market_type : 0 ; // 0 = All market type
        $min_bet = $min_bet != null ? $min_bet : $this->minimum_bet;
        $max_bet = $max_bet != null ? $max_bet : $this->maximum_bet;
        $maxPerMatch = $maxPerMatch != null ? $maxPerMatch : $this->maxPerMatch;
        $params = array(
            "companyKey"    => $this->company_key,
            "username"      => $username,  // can use player or agent.
            "serverId"      => $this->server_id,
            "betSettings"   => [
                            ["sport_type" => $sport_type, 
                    "market_type"       => $market_type, 
                    "min_bet"           => $min_bet,
                    "max_bet"           => $max_bet,
                    "max_bet_per_match" => $maxPerMatch
                ]
            ],
            "method"        => self::API_update_bet_settings_by_sportid_and_marketype
        );

        $this->CI->utils->debug_log("updatePlayerBetSettingsBySportTypeAndMarketType params ============================>", $params);
        return $this->callApi(self::API_update_bet_settings_by_sportid_and_marketype, $params, $context);
    }

    public function processResultForUpdatePlayerBetSettingsBySportTypeAndMarketType($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr);
        
        $this->CI->utils->debug_log('processResultForUpdatePlayerBetSettingsBySportTypeAndMarketType ==========================>', $resultJsonArr);
        return array($success, $resultJsonArr);
    }

    /**
     * overview : Update player general bet settings
     *
     * @param string $gameUsername
     * @param array $customParams
     * @return array
     */

    public function updatePlayerBetSettings($gameUsername, $customParams = null) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForUpdatePlayerBetSettings',
            'agent' => $this->agent_code,
        );
        $params = array(
            "companyKey"    => $this->company_key,
            "username"      => $gameUsername,
            "serverId"      => $this->server_id,
            "min"           => $this->minimum_bet,
            "max"           => $this->maximum_bet,
            "maxPerMatch"   => $this->maxPerMatch,
            "casinoTableLimit"   => $this->casino_table_limit,
            "method"        => self::API_update_player_bet_settings
        );
        if(!empty($customParams)){
            //override params if custom param exist
            $params['min'] = (float)$customParams['min'];
            $params['max'] = (float)$customParams['max'];
            $params['maxPerMatch'] = (float)$customParams['maxPerMatch'];
            $params['casinoTableLimit'] = (float)$customParams['casinoTableLimit'];
        }

        $this->CI->utils->debug_log("updatePlayerBetSettings params ============================>", $params);
        return $this->callApi(self::API_update_player_bet_settings, $params, $context);
    }

    public function processResultForUpdatePlayerBetSettings($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        // echo "<pre>";
        // print_r(json_encode($resultJsonArr));exit();
        return array(true, $resultJsonArr);
    }
    #============================================ end =========================================

    public function createPlayer($playerUsername, $playerId, $password, $email = null, $extra = null) {
        parent::createPlayer($playerUsername, $playerId, $password, $email, $extra);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreatePlayer',
            'playerUsername' => $playerUsername,
            'playerId' => $playerId,
            'gameUsername' => $gameUsername,
        );

        $params = array(
            "CompanyKey"    => $this->company_key,
            "ServerId"      => $this->server_id,
            "Username"      => $gameUsername,
            "Agent"      => $this->agent_code,
            "method"        => self::URI_MAP[self::API_createPlayer]
        );

        $this->CI->utils->debug_log("CreatePlayer params ============================>", $params);
        return $this->callApi(self::API_createPlayer, $params, $context);
    }

    public function processResultForCreatePlayer($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $playerId = $this->getVariableFromContext($params, 'playerId');

        if (!empty($resultJsonArr['error']['id']) && $resultJsonArr['error']['id'] == "4103") {
            $success = true;
        }else{
            $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);
        }

        $result = array(
            "player" => $gameUsername,
            "exists" => false
        );

        if($success){
            # update flag to registered = true
            $this->updateRegisterFlag($playerId, Abstract_game_api::FLAG_TRUE);
            $result["exists"] = true;
        }

        $this->CI->utils->debug_log('processResultForCreatePlayer ==========================>', $resultJsonArr);
        return array($success, $resultJsonArr);
    }

    public function isPlayerExist($playerUsername) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForIsPlayerExist',
        );

        $params = array(
            "CompanyKey"    => $this->company_key,
            "ServerId"      => $this->server_id,
            "Username"      => $gameUsername,
            "method"        => self::URI_MAP[self::API_isPlayerExist]
        );

        $this->CI->utils->debug_log("isPLayerExist params ============================>", $params);
        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params) {
        $resultArr = $this->getResultJsonFromParams($params);

        $success = false;
        $result = array();
        if($resultArr['error']['id'] == self::SUCCESS_API_CODE){
            $success = true;
            $result['exists'] = true;
        }else if($resultArr['error']['id'] == self::ERROR_CODE_USER_DOESNT_EXIST){
            $success = true;
            $result['exists'] = false;
        } else {
            $result['exists'] = null;
        }

        $this->CI->utils->debug_log("Check player if exist ============================>", $success);
        return array($success, $result);
    }

    public function queryPlayerBalance($playerUsername) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameUsername' => $gameUsername,
        );

        $params = array(
            "CompanyKey"    => $this->company_key,
            "Username"      => $gameUsername,
            "ServerId"      => $this->server_id,
            "method"        => self::URI_MAP[self::API_queryPlayerBalance]
        );

        $this->CI->utils->debug_log("queryPlayerBalance ============================>", $params);
        return $this->callApi(self::API_queryPlayerBalance, $params, $context);
    }

    public function processResultForQueryPlayerBalance($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $amount = $this->getVariableFromContext($params, 'amount');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);
        // echo "<pre>";
        // print_r($resultArr);exit();
        if ($success) {
            $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

            if ($playerId) {
                $this->CI->utils->debug_log('SBOBET API GAME API query balance playerId', $playerId, 'gameUsername', $gameUsername, 'balance', $resultArr['balance']);
            } else {
                $this->CI->utils->debug_log('SBOBET API GAME API cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
            }
        }

        $real_balance = $resultArr['balance'] - @$resultArr['outstanding'];
        $result['balance'] = $this->gameAmountToDB($real_balance);
        $result['exists'] = true;

        $this->CI->utils->debug_log("real_balance [whitelabel] ============================>", $real_balance, @$resultArr['outstanding']);
        return array($success, $result);
    }

    public function depositToGame($playerUsername, $amount, $transfer_secure_id=null){

        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

        if(empty($transfer_secure_id)){
            $transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', true, 'T');
        }

        // $serialNo = substr(date('YmdHis'), 2) . random_string('alnum', 5);//'S' . random_string('unique');
        $serialNo = $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'external_transaction_id' => $serialNo
        );

        $params = array(
            "CompanyKey"    => $this->company_key,
            "Username"      => $gameUsername,
            "Amount"        => $amount,
            "TxnId"         => $serialNo,
            "ServerId"      => $this->server_id,
            "method"        => self::URI_MAP[self::API_depositToGame]
        );

        $this->CI->utils->debug_log("Deposit params ============================>", $params);
        return $this->callApi(self::API_depositToGame, $params, $context);

    }

    public function processResultForDepositToGame($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $amount = $this->getVariableFromContext($params, 'amount');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $statusCode = $this->getStatusCodeFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;
        }else{
            if((in_array($statusCode, $this->other_status_code_treat_as_success)) && $this->treat_500_as_success_on_deposit){
                $result['reason_id']=self::REASON_GAME_PROVIDER_INTERNAL_PROBLEM;
                $result['transfer_status']=self::COMMON_TRANSACTION_STATUS_UNKNOWN;
                $success=true;
            } else {
                $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
                $result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
            }
        }
        return array($success, $result);
    }

    public function withdrawFromGame($playerUsername, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);

        if(empty($transfer_secure_id)){
            $transfer_secure_id = $this->getSecureId('transfer_request', 'secure_id', true, 'T');
        }
        // $serialNo = substr(date('YmdHis'), 2) . random_string('alnum', 5);//'S' . random_string('unique');
        $serialNo = $transfer_secure_id;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'external_transaction_id' => $serialNo
        );

        $params = array(
            "CompanyKey"    => $this->company_key,
            "Username"      => $gameUsername,
            "Amount"        => $amount,
            "isFullAmount"  => False,
            "TxnId"         => $serialNo,
            "ServerId"      => $this->server_id,
            "method"        => self::URI_MAP[self::API_withdrawFromGame]
        );
        $this->CI->utils->debug_log("Withraw params ============================>", $params);
        return $this->callApi(self::API_withdrawFromGame, $params, $context);
    }

    public function processResultForWithdrawFromGame($params) {
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $amount = $this->getVariableFromContext($params, 'amount');
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if ($success) {
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
        }
        return array($success, $result);
    }


    public function queryTransaction($transactionId, $extra) {
        $context = array(
            'callback_obj'    => $this,
            'callback_method' => 'processResultForQueryTransaction',
            'external_transaction_id'    => $transactionId
        );

        $params = array(
            "CompanyKey"    => $this->company_key,
            "TxnId"         => $transactionId,
            "ServerId"      => $this->server_id,
            "method"        => self::URI_MAP[self::API_queryTransaction]
        );

        return $this->callApi(self::API_queryTransaction, $params, $context);
    }

    public function processResultForQueryTransaction($params){
        $external_transaction_id = $this->getVariableFromContext($params, 'external_transaction_id');
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = !empty($resultArr) ? true : false;

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id'=> $external_transaction_id,
        );

        if ($success && $resultArr['error']['id']==self::SUCCESS_API_CODE) {
            $result['status']=self::COMMON_TRANSACTION_STATUS_APPROVED;
        }else{
            $result['status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
        }

        $this->CI->utils->debug_log('processResultForQueryTransaction ===========>',$resultArr);
        return array($success, $result);
    }


    public function queryForwardGame($playerUsername, $extra = null) {

        $result = $this->login($playerUsername, $extra);
        $url = null;
        
        $success = $result['success'];
        if($success){
            $device = $extra['is_mobile'] ? "m":"d";
            $game_url = $result['url'];

            $params = array();
            $portfolio = strtolower($result['portfolio']);
            $params['lang'] = $this->getLanguage($extra['language']);

            if($portfolio == self::PORTFORLIO_SPORTSBOOK){
                // $params['lang'] = $this->language;
                $params['oddstyle'] = $this->odds_style;
                $params['theme'] = $extra['is_mobile'] ? $this->m_theme : $this->theme;
                $params['oddsmode'] = $this->oddsmode;
                $params['device'] = $device;
            }

            if($portfolio == self::PORTFORLIO_CASINO){
                $params['device'] = $device;
                $params['IsHtml5'] = 'Y';//make html 5 by default to be compatible to both desktop and mobile
                $params['locale'] = $this->getLanguage($this->language);
                unset($params['lang']);
            }

            if($portfolio == self::PORTFORLIO_GAMES){
                if(!$this->enable_game_language){
                    unset($params['lang']);//only en available so far
                }
                $params['gameId'] = isset($extra['game_code']) ? $extra['game_code']:null;
            }


            if ($portfolio == self::PORTFORLIO_SEAMLESSGAME) {
                if ($extra['game_code'] != 'default' && $extra['game_mode'] != 'default') {
                    $params['gpid'] = $extra['game_code'];
                    $params['gameid'] = $extra['game_mode'];
                } else {
                    //strongly recommended by provider for seamless lobby
                    $params['gpid'] = 10000;
                    $params['gameid'] = 1;
                }
                $params['locale'] = $this->getLanguage($this->language);

                $params['device'] = $device;
            }

            $url = $this->getCurrentProtocol().':'. $game_url.'&'.http_build_query($params);

            if ($this->force_https_for_game_launch) {
                $url = 'https:' . $game_url . '&' . http_build_query($params);
            }

        }

        $data =  array(
            'success' => $success,
            'url' => $url,
            'redirect' => $this->redirect
        );
        return $data;
    }

    public function getLanguage($currentLang) {

        switch ($currentLang) {
            case LANGUAGE_FUNCTION::INT_LANG_CHINESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_CHINESE :
            case 'zh-cn':
                $language = 'zh-cn';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_INDONESIAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_INDONESIAN :
            case 'id-id':
                $language = 'id-id';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_VIETNAMESE:
            case LANGUAGE_FUNCTION::PLAYER_LANG_VIETNAMESE :
            case 'vi-vn':
                $language = 'vi-vn';
                break;
            case 'ko-kr':
            case LANGUAGE_FUNCTION::INT_LANG_KOREAN:
            case LANGUAGE_FUNCTION::PLAYER_LANG_KOREAN :
            case 'ko-kr':
                $language = 'ko-kr';
                break;
            case LANGUAGE_FUNCTION::INT_LANG_THAI:
            case Language_function::PLAYER_LANG_THAI :
            case 'th-th':
                $language = 'th-th';
                break;
            default:
                $language = 'en';
                break;
        }
        return $language;
    }

    public function login($playerUsername, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $portfolio = isset($extra['game_type']) ? $extra['game_type'] : self::PORTFORLIO_SPORTSBOOK;

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'gameUsername' => $gameUsername,
            'portfolio'=> $portfolio
        );

        $params = array(
            "CompanyKey"    => $this->company_key,
            "Username"      => $gameUsername,
            "ServerId"      => $this->server_id,
            "Portfolio"     => $portfolio,
            "method"        => self::URI_MAP[self::API_login]
        );

        if($this->is_wap_sports){
            $params['IsWapSports'] = true;
        }

        $this->CI->utils->debug_log("login params ============================>", $params);
        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForLogin($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);
        $portfolio = $this->getVariableFromContext($params, 'portfolio');
        $data = array(
            "portfolio" => $portfolio,
        );
        if($portfolio){
            $resultJsonArr = array_merge($resultJsonArr,$data);
        }

        $this->CI->utils->debug_log('login result  params ============================>', $resultJsonArr);
        return array($success, $resultJsonArr);
    }

    public function logout($playerName, $password = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogout',
            'gameUsername' => $gameUsername,
        );

        $params = array(
            "CompanyKey"    => $this->company_key,
            "Username"      => $gameUsername,
            "ServerId"      => $this->server_id,
            "method"        => self::URI_MAP[self::API_logout]
        );

        $this->CI->utils->debug_log("logout params ============================>", $params);
        return $this->callApi(self::API_logout, $params, $context);
    }

    public function processResultForLogout($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);

        $this->CI->utils->debug_log('logout result  params ============================>', $resultJsonArr);
        return array($success, $resultJsonArr);
    }

    public function syncLostAndFound($token) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());
        //observer the date format
        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate = $endDate->format('Y-m-d H:i:s');
        $client_portfolios = $this->client_portfolios;

        $result = array();
        if(!empty($client_portfolios)){
            foreach ($client_portfolios as $key => $portfolio) {
                $result[$portfolio] = $this->getBetListByModifyDate($startDate, $endDate, ucfirst(strtolower($portfolio)));
            }
        }
        return array('success' => true, $result);
    }

    public function syncOriginalGameLogs($token = false) {
        $startDate = clone parent::getValueFromSyncInfo($token, 'dateTimeFrom');
        $endDate = clone parent::getValueFromSyncInfo($token, 'dateTimeTo');

        $startDate = new DateTime($this->serverTimeToGameTime($startDate->format('Y-m-d H:i:s')));
        $endDate = new DateTime($this->serverTimeToGameTime($endDate->format('Y-m-d H:i:s')));
        $startDate->modify($this->getDatetimeAdjust());
        //observer the date format
        $startDate = $startDate->format('Y-m-d H:i:s');
        $endDate = $endDate->format('Y-m-d H:i:s');
        $client_portfolios = $this->client_portfolios;

        $result = array();
        if(!empty($client_portfolios)){
            foreach ($client_portfolios as $key => $portfolio) {
                $result[$portfolio] = $this->getBetListByTransactionDate($startDate, $endDate, strtolower($portfolio));
            }
        }
        return array('success' => true, $result);
    }

    public function getBetListByTransactionDate($startDate, $endDate, $portfolio) {


        // $portfolio = "games";
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameRecords',
            'portfolio' => $portfolio,
        );

        /*portfolio
            SportsBook
            Casino
            Games
            VirtualSports
            SeamlessGame
        */

        $params = array(
            "CompanyKey"    => $this->company_key,
            "ServerId"      => $this->server_id,
            "Username"      => $this->agent_code,
            "Portfolio"     => $portfolio,
            "StartDate"     => $startDate,
            "EndDate"       => $endDate,
            "method"        => self::API_GET_BETLIST_BY_TRANSACTION_DATE
        );

        // echo"<pre>";
        // print_r($params);

        return $this->callApi(self::API_syncGameRecords, $params, $context);
    }

    public function getBetListByModifyDate($startDate, $endDate, $portfolio) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameRecords',
            'portfolio' => $portfolio,
        );

        $params = array(
            "CompanyKey"    => $this->company_key,
            "ServerId"      => $this->server_id,
            "Username"      => $this->agent_code,
            "Portfolio"     => $portfolio,
            "StartDate"     => $startDate,
            "EndDate"       => $endDate,
            "method"        => self::API_GET_BETLIST_BY_MODIFY_DATE
        );

        return $this->callApi(self::API_syncLostAndFound, $params, $context);
    }

    public function processResultForSyncOriginalGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $success = $this->processResultBoolean($responseResultId, $resultArr);
        $portfolio = $this->getVariableFromContext($params,'portfolio');


        $this->CI->utils->info_log('SBOBET_V2 GAME LOGS RESPONSE ===>', $resultArr);

        $dataResult = array(
            'data_count' => 0,
            'data_count_insert'=> 0,
            'data_count_update'=> 0
        );
        // echo "<br>";
        // echo json_encode($resultArr);exit();
        // echo"<pre>";
        // print_r($resultArr);exit();

        if($success){
            if(isset($resultArr['result']) && !empty($resultArr['result'])){
                $gameRecords = isset($resultArr['result']) ? $resultArr['result'] : null;  
                if(!empty($gameRecords)){
                    $this->rebuildGameRecords($gameRecords, $responseResultId, $portfolio);

                    list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                        $this->original_gamelogs_table,
                        $gameRecords,
                        'external_uniqueid',
                        'external_uniqueid',
                        self::MD5_FIELDS_FOR_ORIGINAL,
                        'md5_sum',
                        'id',
                        self::MD5_FLOAT_AMOUNT_FIELDS
                    );
                    
                    $this->CI->utils->debug_log('after process available rows', 'gamerecords ->',count($gameRecords), 'insertrows->',count($insertRows), 'updaterows->',count($updateRows));

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
            }
        }
  
        return array($success, $dataResult);
    }

    public function rebuildGameRecords(&$gameRecords,$responseResultId, $portfolio) {


        if(!empty($gameRecords)){
            foreach($gameRecords as $index => &$record) {
                //default
                $preRecord['ref_no'] = isset($record['refNo']) ? $record['refNo'] : null;
                $preRecord['username'] = isset($record['username']) ? $record['username'] : null;
                $preRecord['sports_type'] = isset($record['sportsType']) ? $record['sportsType'] : null;
                $preRecord['order_time'] = isset($record['orderTime']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['orderTime']))) : null;
                $preRecord['modify_date'] = isset($record['modifyDate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['modifyDate']))) : null;
                //sports and virtual
                $preRecord['winlost_date'] = isset($record['winLostDate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['winLostDate']))) : null;
                $preRecord['odds'] = isset($record['odds']) ? $record['odds'] : null;
                $preRecord['odds_style'] = isset($record['oddsStyle']) ? $record['oddsStyle'] : null;
                $preRecord['stake'] = isset($record['stake']) ? $this->gameAmountToDB($record['stake']) : null;
                $preRecord['actual_stake'] = isset($record['actualStake']) ? $this->gameAmountToDB($record['actualStake']) : null;
                $preRecord['currency'] = isset($record['currency']) ? $record['currency'] : null;
                $preRecord['status'] = isset($record['status']) ? $record['status'] : null;

                
                if(strtolower($portfolio) == self::PORTFORLIO_CASINO){
                    $preRecord['win_lost'] = isset($record['winlost']) ? $this->gameAmountToDB($record['winlost']) : null;
                    #double check other index, might exist
                    if( isset($record['winLost']) ){
                        $preRecord['win_lost'] = $this->gameAmountToDB($record['winLost']);
                    }
                } else {
                    $preRecord['win_lost'] = isset($record['winLost']) ? $this->gameAmountToDB($record['winLost']) : null; 
                }
                $preRecord['turnover'] = isset($record['turnover']) ? $this->gameAmountToDB($record['turnover']) : null;
                $preRecord['is_half_won_lose'] = isset($record['isHalfWonLose']) ? $record['isHalfWonLose'] : null;
                $preRecord['is_live'] = isset($record['isLive']) ? $record['isLive'] : null;
                $preRecord['max_win_without_actual_stake'] = isset($record['maxWinWithoutActualStake']) ? $record['maxWinWithoutActualStake'] : null;
                $preRecord['ip'] = isset($record['ip']) ? $record['ip'] : null;
                $preRecord['bet_details'] = isset($record['subBet']) ? json_encode($record['subBet']) : null;

                //casino and game
                $preRecord['game_id'] = isset($record['gameId']) ? $record['gameId'] : null;
                $preRecord['table_name'] = isset($record['tableName']) ? $record['tableName'] : null;
                $preRecord['product_type'] = isset($record['productType']) ? $record['productType'] : null;

                //sbe additional info
                $preRecord['response_result_id'] = $responseResultId;
                $preRecord['external_uniqueid'] = isset($record['refNo']) ? $record['refNo'] : null;

                //for extra checking
                $preRecord['portfolio'] = $portfolio;
                $external_game_id = (!empty($preRecord['sports_type'])) ? $preRecord['sports_type'] : $preRecord['product_type'] ;
                $preRecord['external_game_id'] = $external_game_id;

                if(strtolower($portfolio) == self::PORTFORLIO_SEAMLESSGAME) {
                    $preRecord['actual_stake'] = isset($record['turnoverStake']) ? $this->gameAmountToDB($record['turnoverStake']) : (isset($record['actualStake']) ? $this->gameAmountToDB($record['actualStake']) : (isset($record['stake']) ? $this->gameAmountToDB($record['stake']) : null));
                    $preRecord['external_game_id'] = isset($record['gameType']) ? $record['gameType'] : $external_game_id;
                    $preRecord['product_type'] = isset($record['gameType']) ? $record['gameType'] : (isset($record['productType']) ? $record['productType'] : null);
                }    

                $preRecord['turnover_by_stake'] = isset($record['turnoverByStake']) ? $this->gameAmountToDB($record['turnoverByStake']) : null;
                $preRecord['turnover_by_actual_stake'] = isset($record['turnoverByActualStake']) ? $this->gameAmountToDB($record['turnoverByActualStake']) : null;
                $preRecord['net_turnover_by_stake'] = isset($record['netTurnoverByStake']) ? $this->gameAmountToDB($record['netTurnoverByStake']) : null;
                $preRecord['net_turnover_by_actual_stake'] = isset($record['netTurnoverByActualStake']) ? $this->gameAmountToDB($record['netTurnoverByActualStake']) : null;
                $preRecord['extra_info'] = !empty($record) && is_array($record) ? json_encode($record) : null;

                $gameRecords[$index] = $preRecord;
                unset($preRecord);
            }
        }
        // echo"<pre>";
        // print_r($gameRecords);exit();
    }

    private function updateOrInsertOriginalGameLogs($data, $queryType){
        $dataCount=0;
        if(!empty($data)){
            foreach ($data as $record) {
                if ($queryType == 'update') {
                    $record['updated_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->updateRowsToOriginal($this->original_gamelogs_table, $record);
                } else {
                    unset($record['id']);
                    $record['created_at'] = $this->utils->getNowForMysql();
                    $this->CI->original_game_logs_model->insertRowsToOriginal($this->original_gamelogs_table, $record);
                }
                $dataCount++;
                unset($record);
            }
        }
        return $dataCount;
    }

    public function syncMergeToGameLogs($token) {
        $this->unknownGame = $this->getUnknownGame($this->getPlatformCode());
        return $this->commonSyncMergeToGameLogs($token,
        $this,
        [$this, 'queryOriginalGameLogs'],
        [$this, 'makeParamsForInsertOrUpdateGameLogsRow'],
        [$this, 'preprocessOriginalRowForGameLogs'],
        true);
    }

    /**
     * [queryOriginalGameLogs get all available data for merging]
     * @param  [datetime] $dateFrom     [description]
     * @param  [datetime] $dateTo       [description]
     * @param  [datetime] $use_bet_time [use bet time or update time]
     * @return [array]               [game records]
     */
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time) {
        $sqlTime='sbobet.order_time >= ? and sbobet.modify_date <= ?';
        if ($use_bet_time) {
            $sqlTime='sbobet.order_time >= ? and sbobet.order_time <= ?';
        }

        if ($this->use_extra_info_date_field) {
            $sqlTime = 'sbobet.' . $this->extra_info_date_field . ' >= ? AND sbobet.' . $this->extra_info_date_field . ' <= ?';
        }
        $this->CI->utils->debug_log('SBOBET sqlTime ===>', $sqlTime);


        $sql = <<<EOD
SELECT sbobet.id as sync_index,
sbobet.username as player_username,
IFNULL(`sbobet`.`sports_type`,`sbobet`.`product_type`) as game,
IFNULL(`sbobet`.`sports_type`,`sbobet`.`product_type`) as game_code,
sbobet.ref_no,
sbobet.game_id as round_number,
IFNULL(`sbobet`.`actual_stake`,`sbobet`.`stake`) as bet_amount,
sbobet.stake as real_bet_amount,
sbobet.win_lost as result_amount,
sbobet.order_time as bet_at,
sbobet.order_time as start_at,
sbobet.modify_date as end_at,
sbobet.winlost_date as winlost_date,
sbobet.status,
sbobet.actual_stake,
sbobet.turnover,
sbobet.is_live,
sbobet.sports_type,
sbobet.odds,
sbobet.odds_style,
sbobet.bet_details,
sbobet.portfolio,
sbobet.response_result_id,
sbobet.external_uniqueid,
sbobet.created_at,
sbobet.updated_at,
sbobet.md5_sum,

game_provider_auth.player_id,

gd.id as game_description_id,
gd.game_name as game_description_name,
gd.game_type_id

FROM {$this->original_gamelogs_table} as sbobet
LEFT JOIN game_description as gd ON sbobet.external_game_id = gd.external_game_id AND gd.game_platform_id = ?
LEFT JOIN game_type as gt ON gd.game_type_id = gt.id
JOIN game_provider_auth ON sbobet.username = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
WHERE

{$sqlTime}

EOD;

        $params=[$this->getPlatformCode(), $this->getPlatformCode(),
          $dateFrom,$dateTo];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        // echo "<pre>";
        // print_r($result);exit();
        return $result;
    }

    const MD5_FIELDS_FOR_MERGE = ['external_uniqueid','status','bet_amount','result_amount','player_username','end_at','odds','game_description_id'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = ['bet_amount','result_amount',];
    const SPORTS_GAME = ['sportsbook','virtualsports'];

    public function makeParamsForInsertOrUpdateGameLogsRow(array $row) {

        $this->processExtraDetails($row,$extra,$betDetails);
        $extra['note'] = $row['note'];
        $extra_info=$row['extra_info'];
        $has_both_side=0;

        if(empty($row['md5_sum'])){
            //genereate md5 sum
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        return [
            //set game_type to null unless we know exactly game type name from original game logs
            'game_info'=>['game_type_id'=>$row['game_type_id'], 'game_description_id'=>$row['game_description_id'],
                'game_code'=>$row['game_code'], 'game_type'=>null, 'game'=>$row['game']],
            'player_info'=>['player_id'=>$row['player_id'], 'player_username'=>$row['player_username']],
            'amount_info'=>['bet_amount'=>$row['bet_amount'], 'result_amount'=>$row['result_amount'],
                'bet_for_cashback'=>$row['bet_for_cashback'], 'real_betting_amount'=>$row['real_bet_amount'],
                'win_amount'=>null, 'loss_amount'=>null, 'after_balance'=>null],
            'date_info'=>['start_at'=>$row['start_at'], 'end_at'=>$row['end_at'], 'bet_at'=>$row['bet_at'],
                'updated_at'=>$row['updated_at']],
            'flag'=>Game_logs::FLAG_GAME,
            'status'=>$row['status'],
            'additional_info'=>['has_both_side'=>$has_both_side, 'external_uniqueid'=>$row['external_uniqueid'], 'round_number'=>$row['round_number'],
                'md5_sum'=>$row['md5_sum'], 'response_result_id'=>$row['response_result_id'], 'sync_index'=>$row['sync_index'],
                'bet_type'=>null ],
            // 'bet_details'=>$row['bet_details'],
            // 'extra'=>$extra_info,
            'bet_details'=>$betDetails,
            'extra'=>$extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
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

        $game_description_id = $row['game_description_id'];
        $game_type_id = $row['game_type_id'];

        if (empty($game_description_id)) {
            list($game_description_id, $game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
        }

        // if(in_array(strtolower($row['portfolio']), self::SPORTS_GAME)){
        //     $row['result_amount'] = (float)$row['result_amount'] - $row['real_bet_amount'];
        // }

        if(strtolower($row['portfolio']) == self::PORTFORLIO_VIRTUALSPORTS || strtolower($row['portfolio']) == self::PORTFORLIO_CASINO){
            $row['bet_amount'] = $row['turnover'];
        }

        #OGP-32975 set valid bet to 0 if result status is draw
        if($row['status'] == 'draw'){
            $row['bet_amount'] = 0;
        }

        $row['game_description_id']=$game_description_id;
        $row['game_type_id']=$game_type_id;
        $row['extra_info'] = array();

        if ($this->use_winlost_date_on_end_at && in_array(strtolower($row['portfolio']), $this->use_winlost_date_on_end_at_portfolio)) {
            $row['end_at'] = date('Y-m-d 23:59:59', strtotime($row['winlost_date']));
        }


        ###### START PROCESS BET AMOUNT CONDITIONS
        # get bet conditions for status
        $betConditionsParams = [];
        $betConditionsParams['bet_status'] = strtolower( trim($row['status'], '"') );
        # get bet conditions for win/loss
        $betConditionsParams['win_loss_status'] = null;
        $betConditionsParams['odds_status'] = null;

        if($row['status'] != 'draw' && $row['status'] != 'running'){
            if($row['result_amount']<0){
                if(abs($row['result_amount']) / $row['bet_amount'] == .5 ){
                    $betConditionsParams['win_loss_status'] = 'half_lose';
                }
            }else{
                if($row['result_amount'] / $row['bet_amount'] == .5 ){
                    $betConditionsParams['win_loss_status'] = 'half_win';
                }
            }
        }

        # get bet conditions for odds
        $oddsType = $this->getUnifiedOddsType($row['odds_style']);
        $betConditionsParams['valid_bet_amount'] =  $row['bet_amount'];
        $betConditionsParams['bet_amount_for_cashback'] =  $row['bet_amount'];
        $betConditionsParams['real_betting_amount'] = $row['real_bet_amount'];
        $betConditionsParams['odds_type'] = $oddsType;
        $betConditionsParams['odds_amount'] = $row['odds'];
        $row['bet_for_cashback'] = $row['bet_amount'];
        list($_appliedBetRules, $_validBetAmount, $_betAmountForCashback, $_realBettingAmount, $_betconditionsDetails, $note) = $this->processBetAmountByConditions($betConditionsParams);
        if(!empty($_appliedBetRules)){
            $row['bet_amount'] = $_validBetAmount;
            $row['bet_for_cashback'] = $_betAmountForCashback;
            $row['real_bet_amount'] = $_realBettingAmount;
        }

        $row['note'] = $note;
        ###### /END PROCESS BET AMOUNT CONDITIONS

        $row['status']= $this->getGameRecordsStatus($row['status']);
    }

    public function getUnifiedOddsType($odds){
		switch ($odds) {
            case 'us':
              return 'US';
            case 'eu':
            case 'E':
            case 'Euro':
              return 'EU';
            case 'HK':
            case 'H':
                return 'HK';
            case 'I':
                return 'ID';
            case 'my':
            case 'M':
            case 'Malay':
                return 'MY';
          }
          return $odds;
	}

    private function processExtraDetails($row,&$extra,&$betDetails)
    {
        $betDetails = "NA";
        $other_extra = array();
        
        if(strtolower($row['portfolio']) == self::PORTFORLIO_SPORTSBOOK){
            $is_parlay = ($row['sports_type'] == self::IS_PARLAY) ? true : false;
            $betDetails = array_merge(
                    array('is_parlay' => $is_parlay,'bet' => $row['real_bet_amount'], 'rate' => $row['odds']),
                    $this->processGameBetDetail($row));


            $other_extra = array(
                'table'         =>  $row['ref_no'],
                'odds'          =>  $row['odds'],
                'odds_type'     =>  $row['odds_style'],
                'note'          =>  '',
            );
        }

        if(strtolower($row['portfolio']) == self::PORTFORLIO_SEAMLESSGAME) {
            $other_extra = array(
                'table'         =>  $row['ref_no'],
            );
        }

        $extra = array(
            // 'trans_amount'  =>  $row['bet_amount'],
        );

        $extra = array_merge($extra, $other_extra);
    }

    public function processGameBetDetail($rowArray){
        $betDetails =  array('sports_bet' => $this->setBetDetails($rowArray));
        $this->CI->utils->debug_log('=====> Bet Details return', $betDetails);
        return $betDetails;
    }

    public function setBetDetails($field){
        $data = json_decode($field['bet_details'],true);
        $set = array();
        if(!empty($data)){
            foreach ($data as $key => $game) {
                $live = explode(':',$game['liveScore']);
                $set[$key] = array(
                    'yourBet' => $game['betOption'],
                    'isLive' => ($live[0] > 0 || $live[1]) > 0,
                    'odd' => $game['odds'],
                    'hdp'=> $game['hdp'],
                    'htScore'=> $game['htScore'],
                    'eventName' => $game['match'],
                    'league' => $game['league'],
                );

                if(isset($field['is_live'])&&$field['is_live']){
                    $set[$key]['isLive']=true;
                }
            }
        }

        return $set;
    }

    /**
     * overview : get game record status
     *
     * @param $status
     * @return int
     */
    private function getGameRecordsStatus($status) {
        $this->CI->load->model(array('game_logs'));
        $status = strtolower($status);

        switch ($status) {
        case 'running':
            $status = Game_logs::STATUS_ACCEPTED;
            break;
        case 'reject':
        case 'waiting rejected':
            $status = Game_logs::STATUS_REJECTED;
            break;
        case 'void':
        case 'void(suspended match)':
            $status = Game_logs::STATUS_VOID;
            break;
        case 'refund':
            $status = Game_logs::STATUS_REFUND;
            break;
        case 'won':
        case 'draw':
        case 'lose':
            $status = Game_logs::STATUS_SETTLED;
            break;
        }
        return $status;
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
        $game_name = str_replace("알수없음",$row['game_code'],
                     str_replace("不明",$row['game_code'],
                     str_replace("Unknown",$row['game_code'],$unknownGame->game_name)));
        $external_game_id = $row['game_code'];
        $extra = array('game_code' => $external_game_id,'game_name' => $game_name);

        $game_type_id = $unknownGame->game_type_id ? $unknownGame->game_type_id : null;
        $game_type = $unknownGame->game_name ? $unknownGame->game_name : self::TAG_CODE_UNKNOWN_GAME;

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $external_game_id, $game_type, $external_game_id, $extra,
            $unknownGame);
    }

    public function blockPlayer($playerUsername) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $success = $this->blockUsernameInDB($gameUsername);
        return array("success" => true);
    }

    public function unblockPlayer($playerUsername) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $success = $this->unblockUsernameInDB($gameUsername);
        return array("success" => true);
    }

    /**
     * overview : get game time to server time
     *
     * @return string
     */
    /*public function getGameTimeToServerTime() {
        return '+12 hours';
    }*/

    /**
     * overview : get server time to game time
     *
     * @return string
     */
    /*public function getServerTimeToGameTime() {
        return '-12 hours';
    }*/

    public function syncPlayerAccount($username, $password, $playerId) {
        return $this->returnUnimplemented();
    }

    public function queryPlayerInfo($playerName) {
        return $this->returnUnimplemented();
    }

    public function updatePlayerInfo($playerName, $infos) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        return $this->updatePlayerBetSettings($gameUsername, $infos);
        // return $this->returnUnimplemented();
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

    public function changePassword($playerName, $oldPassword = null, $newPassword) {
        return $this->returnUnimplemented();
    }
}