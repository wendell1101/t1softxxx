<?php
require_once dirname(__FILE__) . '/abstract_game_api.php';
set_include_path(dirname(__FILE__) . '/../unencrypt/phpseclib');
include_once 'Crypt/RSA.php';
require_once dirname(__FILE__).'/sbobet_bet_module.php';
class Game_api_sbobet extends Abstract_game_api {

    use sbobet_bet_module;

    const ODDS_TYPES = ['hk', 'eu'];

    //constant for agent
    const API_register_agent = '/web-root/restricted/agent/register-agent.aspx';
    const API_update_domains = '/web-root/restricted/agent/update-domains.aspx';
    const API_update_agent_bet_settings = '/web-root/restricted/agent/update-agent-bet-settings.aspx';
    const API_update_bet_settings_by_sportid_and_marketype = '/web-root/restricted/betsetting/update-bet-setting-by-sportid-and-markettype.aspx';
    const API_get_member_bet_settings_by_sportid_and_marketype = '/web-root/restricted/betsetting/get-member-bet-settings-with-sportid-and-markettype.aspx';

    //constant for player
    const API_update_player_bet_settings = '/web-root/restricted/player/update-player-bet-settings.aspx';

    //constant for reports
    const API_get_bet_list = '/web-root/restricted/report/get-bet-list.aspx';
    const API_get_casino_bet_list_by_modifydate = '/web-root/restricted/report/get-casino-bet-list-by-modifydate.aspx';
    const API_get_virtualsports_bet_list_by_modifydate = '/web-root/restricted/report/get-virtualsports-bet-list-by-modifydate.aspx';
    const API_get_customer_bet_list_by_modifydate = '/web-root/restricted/report/get-customer-bet-list-by-modifydate.aspx';
    const API_get_bet_payload = '/web-root/restricted/report/get-bet-payload.aspx';
    const API_get_livecasino_beauty = '/web-root/restricted/report/v2/get-livecasinobeauty-bet-list-by-modifydate.aspx';

    // FUNKY GAMES
    const API_getSeamlessGameProviderBetListByModifydate = '/web-root/restricted/report/get-seamlessgameprovider-bet-list-by-modifydate.aspx';
    const API_GET_SEAMLESSGAME_PROVIDER = '/web-root/restricted/seamlessgameprovider/get-game-list.aspx';

    // League bet settings
    const API_GetLeagueIdAndName = '/web-root/restricted/league/get-league.aspx';
    const API_SetLeagueBetSetting = '/web-root/restricted/league/set-league-bet-setting.aspx';
    const API_GetLeagueBetSetting = '/web-root/restricted/league/get-league-bet-setting.aspx';
    const API_SetLeagueGroupBetSetting = '/web-root/restricted/league/set-league-group-bet-setting.aspx';
    const API_GetLeagueGroupBetSetting = '/web-root/restricted/league/get-league-group-bet-setting.aspx';


    const URI_MAP = array(
        self::API_createPlayer => '/web-root/restricted/player/register-player.aspx',
        self::API_isPlayerExist => '/web-root/restricted/player/register-player.aspx',
        self::API_login => '/web-root/restricted/player/login.aspx',
        // self::API_logout => 'Logout',
        self::API_queryPlayerBalance => '/web-root/restricted/player/get-player-balance.aspx',
        self::API_depositToGame => '/web-root/restricted/player/deposit.aspx',
        self::API_withdrawFromGame => '/web-root/restricted/player/withdraw.aspx',
        self::API_syncGameRecords => 'BetRecord',
        self::API_batchQueryPlayerBalance => 'CheckUsrBalance',
        self::API_queryTransaction => '/web-root/restricted/player/check-transaction-status.aspx',
        self::API_get_livecasino_beauty => '/web-root/restricted/report/v2/get-livecasinobeauty-bet-list-by-modifydate.aspx',
    );

    const SUCCESS_API_CODE = 0;
    const is_parlay = "Mix Parlay";

    const MD5_FLOAT_AMOUNT_FIELDS_CASINO = [
        'stake',
        'winlose',
        'turnover',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_SPORTS = [
        'stake',
        'actualStake',
        'winlose',
        'turnover',

        'hdp',
        'odds',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_VIRTUALSPORTS = [
        'stake',
        'winlose',
        'turnover',
    ];

    const MD5_FLOAT_AMOUNT_FIELDS_SEAMLESSGAMEPROVIDER = [
        'stake',
        'winlose',
        'winLost',
        'actualStake'
    ];


    const MD5_FIELDS_FOR_ORIGINAL_CASINO = [
        'refNo',
        'orderTime',
        'status',

        'accountId',
        'tableName',
        'gameId',
        'ProductType',
        'stake',
        'winlose',
        'turnover',
    ];

    const MD5_FIELDS_FOR_ORIGINAL_SPORTS = [
        'refNo',
        'orderTime',
        'status',

        'username',
        'liveScore',
        'htScore',
        'ftScore',
        'isLive',
        'oddsStyle',
        'betOption',
        'sportType',
        'marketType',
        'league',
        'match',
        'winlostDate',
        'customeizedBetType',
        'currency',
        'Ip',
        'stake',
        'actualStake',
        'winlose',
        'turnover',

        'hdp',
        'odds',
    ];

    const MD5_FIELDS_FOR_ORIGINAL_VIRTUALSPORTS = [
        'refNo',
        'orderTime',
        'status',

        'accountId',
        'tableName',
        'gameId',
        'ProductType',
        'stake',
        'actualStake',
        'winlose',
        'turnover',
    ];

    // WHOLE ROW JSON ENCODED
    const MD5_FIELDS_FOR_ORIGINAL_SEAMLESSGAMEPROVIDER = [
        'external_game_id',
        'accountId',
        'stake',
        'winlose',
        'actualStake',
        'turnover',
        'winlostDate',

        // Some response on SEAMLESS GMAE PROVIDER is the same with SPORTS
        'refNo',
        'orderTime',
        'status'
    ];

    const GAME_STATUS_RUNNING = 'running';

    const PORTFORLIO = [
        "sportsbook" => 1,
        "casino" => 7,
        "games" => 3,
        "virtualsports" => 3,
        "seamless_gameprovider" => 9,

    ];

    # Don't ignore on refresh 
    const DEFAULT_IGNORED_0_ON_REFRESH = FALSE;
    
    public function __construct() {
        parent::__construct();
        $this->api_url = $this->getSystemInfo('url');
        $this->currency = $this->getSystemInfo('currency');
        $this->language = $this->getSystemInfo('language','zh-cn');
        $this->agent_code = $this->getSystemInfo('agent_code');
        $this->company_key = $this->getSystemInfo('key');
        $this->server_id = $this->getSystemInfo('server_id');
        $this->gm_online_id = $this->getSystemInfo('gm_online_id');
        $this->casino_url = $this->getSystemInfo('casino_url');
        $this->sportsbook_url = $this->getSystemInfo('sportsbook_url');
        $this->virtualsports_url = $this->getSystemInfo('virtualsports_url');
        $this->seamless_gameprovider_url = $this->getSystemInfo('seamless_gameprovider_url');
        $this->theme_id = $this->getSystemInfo('theme_id');
        $this->minimum_bet = $this->getSystemInfo('minimum_bet');
        $this->maximum_bet = $this->getSystemInfo('maximum_bet');
        $this->maxPerMatch = $this->getSystemInfo('maxPerMatch');
        $this->casino_table_limit = $this->getSystemInfo('casino_table_limit', 4);
        $this->is_agent_created = $this->getSystemInfo('is_agent_created');
        $this->is_agent_updated = $this->getSystemInfo('is_agent_updated');
        $this->domain = $this->getSystemInfo('domain');
        $this->update_domain = $this->getSystemInfo('update_domain');
        $this->odds_style = $this->getSystemInfo('odds_style');
        $this->seamless_game_provider_code = $this->getSystemInfo('seamless_game_provider_code', []);

    }

    public function getPlatformCode() {
        return SBOBET_API;
    }

    public function generateUrl($apiName, $params) {
        $url = $this->api_url.$params['method'];
        // echo $url;
        return  str_replace(" ","",$url);
    }

    public function getHttpHeaders($params){
        return array("Content-Type" => "application/x-www-form-urlencoded");
    }

    protected function customHttpCall($ch, $params) {
        unset($params["method"]); //unset action not need on params
        curl_setopt($ch, CURLOPT_POST, TRUE);
        // echo "?param=".json_encode($params, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params, true));
        curl_setopt($ch, CURLOPT_POSTFIELDS, "param=".json_encode($params, true));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        //curl_setopt( $ch, CURLOPT_TIMEOUT, 60 );
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
    public function createAgent() {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForCreateAgent',
            'agent' => $this->agent_code,
        );

        $params = array(
            "companyKey"    => $this->company_key,
            "serverId"      => $this->server_id,
            "username"      => $this->agent_code,
            "currency"      => $this->currency,
            "language"      => $this->language,
            "themeId"       => $this->theme_id,
            "min"           => $this->minimum_bet,
            "max"           => $this->maximum_bet,
            "maxPerMatch"   => $this->maxPerMatch,
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
        $data = array(
            "is_agent_created" => $success,
        );
        $update = $this->updateExternalSystemExtraInfo($this->getPlatformCode(),$data);
        //$this->updateAgentDomain();
        return array(true, $resultJsonArr);

    }

    public function updateAgentDomain() {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForUpdateAgentDomain',
            'agent' => $this->agent_code,
        );
        $params = array(
            "companyKey"    => $this->company_key,
            "serverId"      => $this->server_id,
            "username"      => $this->agent_code,
            "domains"       => $this->domain,
            "method"        => self::API_update_domains
        );

        $this->CI->utils->debug_log("Update domain params ============================>", $params);
        return $this->callApi(self::API_update_domains, $params, $context);
    }

    public function processResultForUpdateAgentDomain($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('SBOBET update domain result ======================================>','result', $resultJsonArr);
        $check = (($resultJsonArr['error']['id']) == 0 ) ? false: true;
        $data = array(
            "update_domain" => $check,
        );
        $update = $this->updateExternalSystemExtraInfo($this->getPlatformCode(),$data);
        return array(true, $resultJsonArr);
    }

    public function updateAgentBetSettings() {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForUpdateAgentBetSettings',
            'agent' => $this->agent_code,
        );
        $params = array(
            "companyKey"    => $this->company_key,
            "username"      => $this->agent_code,
            "serverId"      => $this->server_id,
            "min"           => $this->minimum_bet,
            "max"           => $this->maximum_bet,
            "maxPerMatch"   => $this->maxPerMatch,
            "casinoTableLimit"   => $this->casino_table_limit,
            "method"        => self::API_update_agent_bet_settings
        );
        $this->CI->utils->debug_log("updateAgentBetSettings params ============================>", $params);
        return $this->callApi(self::API_update_agent_bet_settings, $params, $context);
    }

    public function processResultForUpdateAgentBetSettings($params) {
        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $this->CI->utils->debug_log('SBOBET updateAgentBetSettings result ======================================>','result', $resultJsonArr);
        $check = (($resultJsonArr['error']['id']) == 0 ) ? true: false;
        $data = array(
            "is_agent_updated" => $check,
        );
        $update = $this->updateExternalSystemExtraInfo($this->getPlatformCode(),$data);
        return array(true, $resultJsonArr);
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
            "companyKey"    => $this->company_key,
            "username"      => $gameUsername,
            "currency"      => $this->currency,
            "agent"         => $this->agent_code,
            "language"      => $this->language,
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
            $this->updatePlayerBetSettings($gameUsername);
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
            "companyKey"    => $this->company_key,
            "username"      => $gameUsername,
            "currency"      => $this->currency,
            "agent"         => $this->agent_code,
            "language"      => $this->language,
            "method"        => self::URI_MAP[self::API_isPlayerExist]
        );

        $this->CI->utils->debug_log("isPLayerExist params ============================>", $params);
        return $this->callApi(self::API_isPlayerExist, $params, $context);
    }

    public function processResultForIsPlayerExist($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $success = $resultArr['error']['id'] == 4103 ? true : false;
        $result['exists'] = $success;

        $this->CI->utils->debug_log("Check player if exist ============================>", $success);
        return array(true, $result);
    }

    public function queryPlayerBalance($playerUsername) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryPlayerBalance',
            'gameUsername' => $gameUsername,
        );

        $params = array(
            "companyKey"    => $this->company_key,
            "username"      => $gameUsername,
            "serverId"      => $this->server_id,
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
        $serialNo = substr(date('YmdHis'), 2) . random_string('alnum', 5);//'S' . random_string('unique');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForDepositToGame',
            'gameUsername' => $gameUsername,
            'external_transaction_id' => $serialNo
        );

        $params = array(
            "companyKey"    => $this->company_key,
            "username"      => $gameUsername,
            "amount"        => $amount,
            "txnId"         => $serialNo,
            "serverId"      => $this->server_id,
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
        $success = $this->processResultBoolean($responseResultId, $resultArr,$gameUsername);

        $result = array(
            'response_result_id' => $responseResultId,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if ($success) {
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

            // if ($playerId) {
            //     if ($resultArr['error']['id']==self::SUCCESS_API_CODE) {
            //         $result["currentplayerbalance"] = $afterBalance = $resultArr['balance'] - @$resultArr['outstanding'];

            //         $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());

            //     } else {
            //         $this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
            //         $result['reason_id'] = $this->getReasons($resultArr['error']['id']);
            //     }
            // }else{
            //     $this->CI->utils->debug_log('error', 'SBOBET =============== cannot get player id from '.$gameUsername.' getPlayerIdInGameProviderAuth');
            //     $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            //     $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
            // }
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            $result['didnot_insert_game_logs']=true;
        }else{
            $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            $result['reason_id']=self::REASON_INCOMPLETE_INFORMATION;
        }
        return array($success, $result);
    }

    public function withdrawFromGame($playerUsername, $amount, $transfer_secure_id=null,$notRecordTransaction=false) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $serialNo = substr(date('YmdHis'), 2) . random_string('alnum', 5);//'S' . random_string('unique');

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForWithdrawFromGame',
            'gameUsername' => $gameUsername,
            'external_transaction_id' => $serialNo
        );

        $params = array(
            "companyKey"    => $this->company_key,
            "username"      => $gameUsername,
            "amount"        => $amount,
            "isFullAmount"  => False,
            "txnId"         => $serialNo,
            "serverId"      => $this->server_id,
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
            'external_transaction_id' => $resultArr['txnId'],
            'transfer_status'=>self::COMMON_TRANSACTION_STATUS_UNKNOWN,
            'reason_id'=>self::REASON_UNKNOWN
        );

        if ($success) {
            // $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);

            // if ($playerId) {
            //     if ($resultArr['error']['id']==self::SUCCESS_API_CODE) {
            //         $result["currentplayerbalance"] = $afterBalance = $resultArr['balance'] - @$resultArr['outstanding'];

            //         $this->insertTransactionToGameLogs($playerId, $gameUsername, $afterBalance, $amount, $responseResultId,$this->transTypeMainWalletToSubWallet());

            //         $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_APPROVED;
            //     } else {
            //         $this->CI->utils->debug_log('error', 'cannot get player id from ' . $gameUsername . ' getPlayerIdInGameProviderAuth');
            //         $result['reason_id'] = $this->getReasons($resultArr['error']['id']);
            //     }
            // } else {
            //     $this->CI->utils->debug_log('error', 'SBOBET =============== cannot get player id from '.$gameUsername.' getPlayerIdInGameProviderAuth');
            //     $result['transfer_status'] = self::COMMON_TRANSACTION_STATUS_DECLINED;
            //     $result['reason_id']=self::REASON_NOT_FOUND_PLAYER;
            // }
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
            "companyKey"    => $this->company_key,
            "txnId"         => $transactionId,
            "serverId"      => $this->server_id,
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

    private function getReasons($error_code){

        switch ($error_code) {
            case 4501:
                return self::REASON_NO_ENOUGH_BALANCE;
                break;
            case 4402:
                return self::REASON_INVALID_TRANSFER_AMOUNT;
                break;
            case 2:
                return self::REASON_INCOMPLETE_INFORMATION;
                break;
            case 1:
                return self::REASON_INVALID_KEY;
                break;
            case 33:
                return self::REASON_NOT_FOUND_PLAYER;
                break;
            case 5:
                return self::REASON_IP_NOT_AUTHORIZED;
                break;
            case 4101:
                return self::REASON_AGENT_NOT_EXISTED;
                break;
            default:
                return self::REASON_UNKNOWN;
                break;
        }
    }

    public function queryForwardGame($playerUsername, $extra = null) {
        $device = !empty($extra['mobile']) ? "m":"d";
        unset($extra['mobile']);

        if(isset($extra['is_mobile'])){
            $device = $extra['is_mobile'] ? "m":"d";
        }

        $extra['portfolio'] = isset($extra['portfolio']) ? $extra['portfolio'] : $extra['game_type'];

        $resultArr = $this->login($playerUsername, $extra);
        $game_url = $this->sportsbook_url;
        $token = isset($resultArr['token']) ? $resultArr['token'] : null;

        $params = array(
            "token"     => $token,
            "lang"      => $this->language,
            "theme"     => $this->getSystemInfo('theme','default')
        );

        if ($extra['portfolio'] == 'casino') {
            $game_url = $this->casino_url;
            $params = array(
                "token"     => $token,
                "locale"    => $this->language,
            );
        }

        if ($extra['portfolio'] == 'virtualsports') {
            $game_url = $this->virtualsports_url;
            $params = array(
                "gmOnlineId" => $this->gm_online_id,
                "token" => $token,
            );
        }        

        if ($extra['portfolio'] == 'seamless_gameprovider') {
            $game_url = $this->seamless_gameprovider_url;
            $params = array(
                "gpid"   => $extra['gpid'],
                "gameid" => $extra['gameid'],
                "token"  => $token,
                "locale"   => $this->language,
            );
        }


        // $game_url = ($extra['portfolio'] == "casino") ? $this->casino_url : $this->sportsbook_url;
        
        if(!empty($this->odds_style))
            $params['Oddstyle'] = $this->odds_style;

        $generateUrl = $game_url.'?'.http_build_query($params);
        $generateUrl .= '&device='.$device;

        $data = array(
            'url' => $generateUrl,
            'success' => true
        );

        return $data;
    }

    public function login($playerUsername, $extra = null) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerUsername);
        $portfolio = array_key_exists($extra['portfolio'], self::PORTFORLIO) ? self::PORTFORLIO[$extra['portfolio']] : self::PORTFORLIO['sportsbook'];

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForLogin',
            'gameUsername' => $gameUsername,
        );

        $params = array(
            "companyKey"    => $this->company_key,
            "username"      => $gameUsername,
            "serverId"      => $this->server_id,
            "portfolio"     => self::PORTFORLIO[$extra['portfolio']],
            "method"        => self::URI_MAP[self::API_login]
        );

        $this->CI->utils->debug_log("login params ============================>", $params);
        return $this->callApi(self::API_login, $params, $context);
    }

    public function processResultForLogin($params) {

        $responseResultId = $this->getResponseResultIdFromParams($params);
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $gameUsername = $this->getVariableFromContext($params, 'gameUsername');
        $success = $this->processResultBoolean($responseResultId, $resultJsonArr, $gameUsername);
        $portfolio = $this->getParamValueFromParams($params, 'portfolio');
        $data = array(
            "portfolio" => $portfolio,
        );
        if($portfolio){
            $resultJsonArr = array_merge($resultJsonArr,$data);
        }

        $this->CI->utils->debug_log('login result  params ============================>', $resultJsonArr);
        return array($success, $resultJsonArr);
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

        $gameType = $this->getValueFromSyncInfo($token, 'gameType');
        if(!empty($gameType)){
            switch ($gameType) {
                case 'sports':
                    return $this->getSportsBookGamelogs($token, $gameKind, $gameType, $apiName);
                    break;
                case 'casino':
                    return $this->getCasinoGameLogs($startDate, $endDate);
                    break;
                case 'casino_beauty':
                    return $this->getCasinoBeautyGameLogs($startDate, $endDate);
                    break;
                case 'virtual':
                    return $this->getVirtualSportsGameLogs($startDate, $endDate);
                    break;                
                case 'seamless_gameprovider':
                    return $this->getSeamlessGameProviderGameLogs($startDate, $endDate);
                    break;

            }
        }
        $result['casino_beauty'] = $this->getCasinoBeautyGameLogs($startDate, $endDate);
        $result['sportsbook'] = $this->getSportsBookGamelogs($startDate, $endDate);
        $result['casino'] = $this->getCasinoGameLogs($startDate, $endDate);
        $result['virtualsports'] = $this->getVirtualSportsGameLogs($startDate, $endDate);
        $result['seamless_gameprovider'] = $this->getSeamlessGameProviderGameLogs($startDate, $endDate);
        return array('success' => true,$result);
    }

    public function getSeamlessGameProviderGameLogs($startDate, $endDate) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameRecords',
            'type' => 'seamless_gameprovider',
        );

        $result = [];

        if (!is_array($this->seamless_game_provider_code) || empty($this->seamless_game_provider_code)) {
            $this->CI->utils->info_log('<=== SEAMLESS GAME_PROVIDER MUST BE IN ARRAY AND CANNOT BE EMPTY ===>');
            return;
        }

        foreach ($this->seamless_game_provider_code as $game_provider) {
            $params = array(
                "companyKey" => $this->company_key,
                "gameProvider" => $game_provider,
                "serverId" => $this->server_id,
                "username" => $this->agent_code,
                "startDate" => $startDate,
                "endDate" => $endDate,
                "method" => self::API_getSeamlessGameProviderBetListByModifydate
            );
            $result += $this->callApi(self::API_getSeamlessGameProviderBetListByModifydate, $params, $context);
        }
        return $result;

    }


    public function getSportsBookGamelogs($startDate, $endDate) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameRecords',
            'type' => 'sports',
        );

        $params = array(
            "companyKey"    => $this->company_key,
            "serverId"      => $this->server_id,
            "username"      => $this->agent_code,
            //"portfolio"       => "sportsbook",
            "startDate"     => $startDate,
            "endDate"       => $endDate,
            //"lang"          => $this->language, // will remove this because default is English
            "method"        => self::API_get_customer_bet_list_by_modifydate
        );

        return $this->callApi(self::API_get_customer_bet_list_by_modifydate, $params, $context);
    }

    public function getCasinoBeautyGameLogs($startDate, $endDate) {
        // $context = array(
        //     'callback_obj' => $this,
        //     'callback_method' => 'processResultForSyncOriginalGameRecords',
        //     'type' => 'casino',
        // );

        // $params = array(
        //     "companyKey"    => $this->company_key,
        //     "serverId"      => $this->server_id,
        //     "username"      => $this->agent_code,
        //     "startDate"     => $startDate,
        //     "endDate"       => $endDate,
        //     "method"        => self::API_get_livecasino_beauty
        // );

        // return $this->callApi(self::API_get_livecasino_beauty, $params, $context);

        $result = array();
        $result[] = $this->CI->utils->loopDateTimeStartEnd($startDate, $endDate, '+30 minutes', function($startDate, $endDate)  {
            $startDate = $startDate->format('Y-m-d H:i:s');
            $endDate = $endDate->format('Y-m-d H:i:s');
            $context = array(
                'callback_obj' => $this,
                'callback_method' => 'processResultForSyncOriginalGameRecords',
                'type' => 'casino',
            );

            $params = array(
                "companyKey"    => $this->company_key,
                "serverId"      => $this->server_id,
                "username"      => $this->agent_code,
                "startDate"     => $startDate,
                "endDate"       => $endDate,
                "method"        => self::API_get_livecasino_beauty
            );

            $this->CI->utils->debug_log('-----------------------sbobet getCasinoBeautyGameLogs params ----------------------------',$params);
            return $this->callApi(self::API_get_livecasino_beauty, $params, $context);
        });
        return array(true, $result);
    }

    public function getCasinoGameLogs($startDate, $endDate) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameRecords',
            'type' => 'casino',
        );

        $params = array(
            "companyKey"    => $this->company_key,
            "serverId"      => $this->server_id,
            "username"      => $this->agent_code,
            //"portfolio"       => "sportsbook",
            "startDate"     => $startDate,
            "endDate"       => $endDate,
            "lang"          => $this->language,
            "method"        => self::API_get_casino_bet_list_by_modifydate
        );

        return $this->callApi(self::API_get_casino_bet_list_by_modifydate, $params, $context);
    }

    public function getVirtualSportsGameLogs($startDate, $endDate) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForSyncOriginalGameRecords',
            'type' => 'virtualsports',
        );

        $params = array(
            "companyKey"    => $this->company_key,
            "serverId"      => $this->server_id,
            "username"      => $this->agent_code,
            //"portfolio"       => "sportsbook",
            "startDate"     => $startDate,
            "endDate"       => $endDate,
            "lang"          => $this->language,
            "method"        => self::API_get_virtualsports_bet_list_by_modifydate
        );

        return $this->callApi(self::API_get_virtualsports_bet_list_by_modifydate, $params, $context);
    }

    public function processResultForSyncOriginalGameRecords($params) {
        $this->CI->load->model(array('original_game_logs_model'));
        $resultArr = $this->getResultJsonFromParams($params);
        $type = $this->getVariableFromContext($params, 'type');
        // $this->CI->utils->debug_log('<===============  Response  result  ============================>', $resultArr);
        $this->CI->utils->debug_log('<===============  Response  error  ============================>', $resultArr['error']);
        $responseResultId = $this->getResponseResultIdFromParams($params);

        if ($type == "casino") {
            $success = isset($resultArr['result']) && count($resultArr['result']);
            $gameRecords = isset($resultArr['result']) ? $resultArr['result'] : [];
            $md5Fields = self::MD5_FIELDS_FOR_ORIGINAL_CASINO;
            $md5FloatFields = self::MD5_FLOAT_AMOUNT_FIELDS_CASINO;
        } elseif ($type == "virtualsports") {
            $success = isset($resultArr['result']) && count($resultArr['result']);
            $gameRecords = isset($resultArr['result']) ? $resultArr['result'] : [];
            $md5Fields = self::MD5_FIELDS_FOR_ORIGINAL_VIRTUALSPORTS;
            $md5FloatFields = self::MD5_FLOAT_AMOUNT_FIELDS_VIRTUALSPORTS;
        } else if ($type == "seamless_gameprovider") {
            $success = isset($resultArr['result']) && count($resultArr['result']);
            $gameRecords = isset($resultArr['result']) ? $resultArr['result'] : [];
            $md5Fields = self::MD5_FIELDS_FOR_ORIGINAL_SEAMLESSGAMEPROVIDER;
            $md5FloatFields = self::MD5_FLOAT_AMOUNT_FIELDS_SEAMLESSGAMEPROVIDER;
            $this->CI->utils->info_log('<==== SEAMLESS GAME_PROVIDER ====> ', $resultArr);
        } else {
            $success = isset($resultArr['PlayerBetList']) && count($resultArr['PlayerBetList']);
            $gameRecords = isset($resultArr['PlayerBetList']) ? $resultArr['PlayerBetList'] : [];
            $md5Fields = self::MD5_FIELDS_FOR_ORIGINAL_SPORTS;
            $md5FloatFields = self::MD5_FLOAT_AMOUNT_FIELDS_SPORTS;
        }

        $result = array();
        if ($success) {
            $dateTimeNow = date('Y-m-d H:i:s');

            if (!empty($gameRecords)) {
                $result['data_count'] = $count = 0;
                $extra = ['responseResultId'=>$responseResultId];
                $this->rebuildGameRecords($gameRecords,$extra,$type);

                list($insertRows, $updateRows)=$this->CI->original_game_logs_model->getInsertAndUpdateRowsForOriginal(
                    'whitelabel_game_logs',
                    $gameRecords,
                    'external_uniqueid',
                    'external_uniqueid',
                    $md5Fields,
                    'md5_sum',
                    'id',
                    $md5FloatFields
                );

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

                $count++;
                $result['data_count'] = $count;
            }
        }
        return array($success, $result);
    }

    public function rebuildGameRecords(&$gameRecords,$extra,$type){
        $preRecord = [];

        foreach($gameRecords as $index => &$record) {
            $preRecord[$index]['refNo'] = $record['refNo'];
            $preRecord[$index]['external_uniqueid'] = $record['refNo'];
            $external_game_id = isset($record['sportType'])?$record['sportType']:'';
            if(!$external_game_id){
                $external_game_id = isset($record['ProductType'])?$record['ProductType']:'';
            }
            $preRecord[$index]['external_game_id'] = $external_game_id;//parlay
            $preRecord[$index]['extra'] = json_encode($record);
            $preRecord[$index]['type'] = $type;

            $preRecord[$index]['status'] = isset($record['status']) ? $record['status'] : null;
            $preRecord[$index]['turnover'] = isset($record['turnover']) ? $this->gameAmountToDB($record['turnover']) : null;
            $preRecord[$index]['stake'] = isset($record['stake']) ? $this->gameAmountToDB($record['stake']) : null;

            if ($type=="casino") {
                $preRecord[$index]['accountId'] = $record['accountId'];

                $preRecord[$index]['gameId'] = $record['gameId'];
                $preRecord[$index]['ProductType'] = $record['ProductType'];
                $preRecord[$index]['tableName'] = $record['tableName'];
                $preRecord[$index]['winlose'] = $this->gameAmountToDB($record['winlost']);
                $preRecord[$index]['username'] = $record['accountId'];
                $preRecord[$index]['doneTime'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['orderTime'])));
                unset($record['winlost']);
            } else if ($type=="virtualsports") {
                $preRecord[$index]['accountId'] = $record['accountId'];
                $preRecord[$index]['gameId'] = $record['gameId'];
                $preRecord[$index]['tableName'] = $record['tableName'];
                $preRecord[$index]['winlose'] = $this->gameAmountToDB($record['winlost']);
                $preRecord[$index]['ProductType'] = $record['ProductType'];
                $preRecord[$index]['username'] = $record['accountId'];
                $preRecord[$index]['subBet'] = json_encode($record['SubBets']);//parlay
                $preRecord[$index]['actualStake'] = $this->gameAmountToDB($record['actualStake']);
                unset($record['winlost']);
            } else if ($type == "seamless_gameprovider") {
                $preRecord[$index]['ProductType'] = isset($record['gameType']) ? $record['gameType'] : null;
                $preRecord[$index]['accountId'] = isset($record['accountId']) ? $record['accountId'] : null;
                $preRecord[$index]['username'] = isset($record['accountId']) ? $record['accountId'] : (isset($record['username']) ? $record['username'] : null);
                $preRecord[$index]['external_game_id'] = isset($record['gameType']) ? $record['gameType'] : (isset($record['sportType']) ? $record['sportType'] : null);
                $preRecord[$index]['winlose'] = isset($record['winLost']) ? $this->gameAmountToDB($record['winLost']) : (isset($record['winlose']) ? $this->gameAmountToDB($record['winlose']) : null);
                $preRecord[$index]['actualStake'] = isset($record['turnOverStake']) ? $this->gameAmountToDB($record['turnOverStake']) : (isset($record['actualStake']) ? $this->gameAmountToDB($record['actualStake']) : null);
                $preRecord[$index]['turnover'] = isset($record['turnOverStake']) ? $this->gameAmountToDB($record['turnOverStake']) : (isset($record['actualStake']) ? $this->gameAmountToDB($record['actualStake']) : null);

                $WinLostDate = isset($record['WinLostDate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['WinLostDate']))) : (isset($record['winlostDate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['winlostDate']))) : null);
                $preRecord[$index]['oddsStyle'] = isset($record['oddsStyle']) ? $record['oddsStyle'] : null;
                $preRecord[$index]['sportType'] = isset($record['sportType']) ? $record['sportType'] : null;
                $preRecord[$index]['currency'] = isset($record['currency']) ? $record['currency'] : null;
                $preRecord[$index]['Ip'] = isset($record['Ip']) ? $record['Ip'] : null;
                $preRecord[$index]['isLive'] = isset($record['isLive']) ? $record['isLive'] : null;
                $preRecord[$index]['odds'] = isset($record['odds']) ? $record['odds'] : null;
                $preRecord[$index]['betOption'] = isset($record['subBet'][0]['betOption']) ? $record['subBet'][0]['betOption'] : null;
                $preRecord[$index]['marketType'] = isset($record['subBet'][0]['marketType']) ? $record['subBet'][0]['marketType'] : null;
                $preRecord[$index]['hdp'] = isset($record['subBet'][0]['hdp']) ? $record['subBet'][0]['hdp'] : null;
                $preRecord[$index]['league'] = isset($record['subBet'][0]['league']) ? $record['subBet'][0]['league'] : null;
                $preRecord[$index]['match'] = isset($record['subBet'][0]['match']) ? $record['subBet'][0]['match'] : null;
                $preRecord[$index]['winlostDate'] = isset($record['subBet'][0]['winlostDate']) ? $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['subBet'][0]['winlostDate']))) : $WinLostDate;
                $preRecord[$index]['liveScore'] = isset($record['subBet'][0]['liveScore']) ? $record['subBet'][0]['liveScore'] : null;
                $preRecord[$index]['htScore'] = isset($record['subBet'][0]['htScore']) ? $record['subBet'][0]['htScore'] : null;
                $preRecord[$index]['ftScore'] = isset($record['subBet'][0]['ftScore']) ? $record['subBet'][0]['ftScore'] : null;
                $preRecord[$index]['customeizedBetType'] = isset($record['subBet'][0]['customeizedBetType']) ? $record['subBet'][0]['customeizedBetType'] : null;

                unset($record['winlost']);
            } else {
                $preRecord[$index]['username'] = $record['username'];
                $preRecord[$index]['oddsStyle'] = $record['oddsStyle'];
                $preRecord[$index]['sportType'] = $record['sportType'];
                $preRecord[$index]['currency'] = $record['currency'];
                $preRecord[$index]['Ip'] = $record['Ip'];
                $preRecord[$index]['isLive'] = $record['isLive'];
                $preRecord[$index]['odds'] = $record['odds'];
                $preRecord[$index]['betOption'] = $record['subBet'][0]['betOption'];
                $preRecord[$index]['marketType'] = $record['subBet'][0]['marketType'];
                $preRecord[$index]['hdp'] = $record['subBet'][0]['hdp'];
                $preRecord[$index]['league'] = $record['subBet'][0]['league'];
                $preRecord[$index]['match'] = $record['subBet'][0]['match'];
                $preRecord[$index]['winlostDate'] = $record['subBet'][0]['winlostDate'];
                $preRecord[$index]['liveScore'] = $record['subBet'][0]['liveScore'];
                $preRecord[$index]['htScore'] = $record['subBet'][0]['htScore'];
                $preRecord[$index]['ftScore'] = $record['subBet'][0]['ftScore'];
                $preRecord[$index]['customeizedBetType'] = $record['subBet'][0]['customeizedBetType'];
                $preRecord[$index]['winlostDate'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['winlostDate'])));
                $preRecord[$index]['subBet'] = json_encode($record['subBet']);//parlay
                $preRecord[$index]['actualStake'] = $this->gameAmountToDB($record['actualStake']);
                $preRecord[$index]['winlose'] = $this->gameAmountToDB($record['winlose']);
            }

            if ($record['modifyDate'] && $record['status'] != self::GAME_STATUS_RUNNING ) {
                $preRecord[$index]['modifyDate'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['modifyDate'])));
                $preRecord[$index]['doneTime'] = $preRecord[$index]['modifyDate'];
            }

            $preRecord[$index]['response_result_id'] = $extra['responseResultId'];
            $preRecord[$index]['orderTime'] = $this->gameTimeToServerTime(date('Y-m-d H:i:s', strtotime($record['orderTime'])));
        }
        $gameRecords = $preRecord;
        unset($preRecord);
    }

    private function updateOrInsertOriginalGameLogs($rows, $update_type, $additionalInfo=[]){
        $dataCount=0;
        if(!empty($rows)){
            $responseResultId=$additionalInfo['responseResultId'];
            foreach ($rows as $record) {
                $record['last_sync_time'] = $this->CI->utils->getNowForMysql();
                if ($update_type=='update') {
                    $this->CI->original_game_logs_model->updateRowsToOriginal('whitelabel_game_logs', $record);
                }else{
                    $this->CI->original_game_logs_model->insertRowsToOriginal('whitelabel_game_logs', $record);
                }
                $dataCount++;
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
    public function queryOriginalGameLogs($dateFrom, $dateTo, $use_bet_time){
        $sqlTime='IFNULL(`whitelabel_game_logs`.`doneTime`,`whitelabel_game_logs`.`orderTime`) >= ?
          AND IFNULL(`whitelabel_game_logs`.`doneTime`,`whitelabel_game_logs`.`orderTime`) <= ?';
        if($use_bet_time){
            $sqlTime='`whitelabel_game_logs`.`orderTime` >= ?
          AND `whitelabel_game_logs`.`orderTime` <= ?';
        }

        $sql = <<<EOD
            SELECT
                whitelabel_game_logs.external_uniqueid,
                whitelabel_game_logs.orderTime AS start_date,
                whitelabel_game_logs.doneTime AS end_date,
                whitelabel_game_logs.ProductType,
                whitelabel_game_logs.response_result_id,
                whitelabel_game_logs.winlose AS result_amount,
                whitelabel_game_logs.stake AS bet_amount,
                whitelabel_game_logs.status as game_status,
                whitelabel_game_logs.refNo,
                whitelabel_game_logs.actualStake,
                whitelabel_game_logs.accountId,
                whitelabel_game_logs.gameId,
                whitelabel_game_logs.subBet,
                whitelabel_game_logs.tableName,
                whitelabel_game_logs.doneTime,
                whitelabel_game_logs.stake,
                whitelabel_game_logs.odds,
                whitelabel_game_logs.match,
                whitelabel_game_logs.isLive,
                whitelabel_game_logs.betOption,
                whitelabel_game_logs.hdp,
                whitelabel_game_logs.sportType,
                whitelabel_game_logs.liveScore,
                whitelabel_game_logs.htScore,
                whitelabel_game_logs.ftScore,
                whitelabel_game_logs.oddsStyle,
                whitelabel_game_logs.marketType,
                whitelabel_game_logs.league,
                whitelabel_game_logs.username,
                whitelabel_game_logs.winlostDate,
                whitelabel_game_logs.customeizedBetType,
                whitelabel_game_logs.orderTime,
                whitelabel_game_logs.currency,
                whitelabel_game_logs.Ip,
                whitelabel_game_logs.external_game_id,
                whitelabel_game_logs.md5_sum,

                game_provider_auth.player_id,

                game_description.id AS game_description_id,
                game_description.game_name AS game,
                game_description.game_code,
                game_description.game_type_id,
                game_description.void_bet,
                game_type.game_type
            FROM
                (`whitelabel_game_logs`)
                    LEFT JOIN
                `game_description` ON whitelabel_game_logs.external_game_id = game_description.external_game_id
                    AND game_description.game_platform_id = ?
                    AND game_description.void_bet != 1
                    LEFT JOIN
                `game_type` ON game_description.game_type_id = game_type.id
                JOIN game_provider_auth ON whitelabel_game_logs.username = game_provider_auth.login_name and game_provider_auth.game_provider_id=?
            WHERE
                {$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo,
        ];

        $data = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $data;
    }

    const MD5_FIELDS_FOR_MERGE = ['external_uniqueid','game_status','bet_amount','result_amount','username','doneTime','odds','external_game_id'];
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = ['bet_amount','result_amount',];

    /**
     * * Payout of Sports: winlose - actualStake
     * * Payout of Casino: winlost
     */
    public function makeParamsForInsertOrUpdateGameLogsRow(array $row){

        $status = $this->getGameRecordsStatus($row['game_status']);
        $this->processExtraDetails($row,$extra,$betDetails);

        $extra = array_merge($extra,[
            'match_details' => $row['match'],
            'match_type' => $row['isLive'],
            'bet_info' => $row['betOption'],
            'handicap' => $row['hdp'],
            'table' => $row['external_uniqueid'],
            'is_parlay' => ($row['sportType'] == self::is_parlay) ? true : false
        ]);

        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
                self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }
        $bet_amount = $row['bet_amount'];
        $game_status = strtolower($row['game_status']);
        if($game_status == "draw"){ //set bet amount to zero when draw
            $bet_amount = 0;
            $extra['note'] = lang("Draw");
        }

        if (empty($row['ProductType'])) { // if empty means game type is sports
            // payout = (winlose - actualStake in api) for sports
            $result_amount = $row['result_amount']-$row['actualStake'];
            
            //$result_amount = (in_array($game_status, ["won","draw"])) ? ($row['result_amount'] - $row['bet_amount']) : -($row['bet_amount']);
        } else {
            // (winlost in api) for casino
            $result_amount = $row['result_amount'];
        }

        $processedRow = [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['username']
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount,
                'bet_for_cashback' => $row['bet_amount'],
                'real_betting_amount' => $row['bet_amount'],
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => null
            ],
            'date_info' => [
                'start_at' => $row['start_date'],
                'end_at' => isset($row['end_date']) ? $row['end_date']:$row['start_date'],
                'bet_at' => $row['start_date'],
                'updated_at' => isset($row['end_date']) ? $row['end_date']:$row['start_date'],
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $status,
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['external_uniqueid'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['response_result_id'],
                'bet_type' => stripos($row['sportType'], 'Mix') === false ? 'Single' : $row['sportType'],
            ],
            'bet_details' => $betDetails,
            'extra' => $extra,
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $processedRow;
    }

    private function processExtraDetails($row,&$extra,&$betDetails){
        $is_parlay = ($row['sportType'] == self::is_parlay) ? true : false;
        $betDetails = array_merge(
                array('is_parlay' => $is_parlay,'bet' => $row['stake'], 'rate' => $row['odds']),
                $this->processGameBetDetail($row));

        if(!empty($row['accountId']) && !empty($row['gameId']) ){
            $resultAmount = $row['result_amount'];
            $extra = array(
                'trans_amount'  =>  $row['bet_amount'],
            );
        }else{
            $resultAmount = ($row['game_status'] == "game_status") ? $row['result_amount'] : -($row['bet_amount']);
            $extra = array(
                'trans_amount'  =>  $row['actualStake'],
            );
        }
        $odds_style = strtolower($this->odds_style);
        $odds_type = in_array($odds_style, self::ODDS_TYPES) ? $odds_style : 'eu'; # Invalid odd style is defaulted to 'eu'
        $other_extra = array(
            'table'         =>  $row['gameId'],
            'odds'          =>  $row['odds'],
            'odds_type'     =>  $odds_type,
            'note'          =>  '',
        );
        $extra = array_merge($extra, $other_extra);
    }

    /**
     * [preprocessOriginalRowForGameLogs game details checking]
     * @param  array  &$row [row]
     * @return [array]      [overwrite $row]
     */
    public function preprocessOriginalRowForGameLogs(array &$row){

        if (empty($row['game_description_id'])) {
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row,$this->unknownGame);
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        $row['status'] = $this->getGameRecordsStatus($row['game_status']);
    }

    public function processGameBetDetail($rowArray){
        $betDetails =  array('sports_bet' => $this->setBetDetails($rowArray));
        $this->CI->utils->debug_log('=====> Bet Details return', $betDetails);
        return $betDetails;
    }

    public function setBetDetails($field){
        $data = json_decode($field['subBet'],true);
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
            $status = Game_logs::STATUS_REJECTED;
            break;
        case 'void':
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
        $game_description_id = !empty($row['game_description_id']) ? $row['game_description_id']:null;
        $game_type_id = !empty($row['game_type_id']) ? $row['game_type_id']:null;

        $externalGameId = $row['external_game_id'];
        $extra = array('game_code' => $externalGameId);

        return $this->processUnknownGame(
            $game_description_id, $game_type_id,
            $row['external_game_id'], @$unknownGame->game_name, $externalGameId, $extra,
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

    public function getBetPayload($refno) {

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForGetBetPayLoad',
        );

        $params = array(
            "portfolio"     => "casino",
            "refno"         => $refno,
            "companyKey"    => $this->company_key,
            "serverId"      => $this->server_id,
            "method"        => self::API_get_bet_payload
        );
        $this->CI->utils->debug_log("getBetPayload params ============================>", $params);
        return $this->callApi(self::API_get_bet_payload, $params, $context);
    }

    public function processResultForGetBetPayLoad($params) {
        $resultJsonArr = $this->getResultJsonFromParams($params);
        $casino_url = $this->casino_url;
        $url = "http://lobby-888.568win.com/web-root/public/dispatch.aspx?payload=".$resultJsonArr['result'];

        echo"<pre>";
        print_r($url);exit();
    }

    public function queryGameListFromGameProvider($gpid = 16, $get_all = false) {
        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryGameListFromGameProvider',
        );

        $params = array(
            "gpid"          => $gpid,
            "isGetAll"      => $get_all,
            "companyKey"    => $this->company_key,
            "serverId"      => $this->server_id,
            "method"        => self::API_GET_SEAMLESSGAME_PROVIDER
        );

        return $this->callApi(self::API_GET_SEAMLESSGAME_PROVIDER, $params, $context);
    }

    public function processResultForQueryGameListFromGameProvider($params) {
        $resultArr = $this->getResultJsonFromParams($params);
        $responseResultId = $this->getResponseResultIdFromParams($params);

        $this->CI->utils->info_log('API_GET_SEAMLESSGAME_PROVIDER ====>', $resultArr);
        return array(true, $resultArr);
    }


    public function processResultForgetVendorId($params) {
        return $this->returnUnimplemented();
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

    public function logout($playerName, $password = null) {
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

/*end of file*/