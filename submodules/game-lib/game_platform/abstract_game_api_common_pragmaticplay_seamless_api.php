<?php
require_once dirname(__FILE__) . '/game_api_pragmaticplay.php';

abstract class abstract_game_api_common_pragmaticplay_seamless_api extends Game_api_pragmaticplay {

    public $allow_multi_session;
    public $use_sync_game_logs;
    public $use_api_game_result;
    public $bingo_room_url;
    public $lobby_url;
    public $mini_games_url;
    public $mini_games_url_mobile;
    public $transaction_table_name;
    public $original_transaction_table_name;
    public $use_monthly_transactions_table;
    public $force_check_previous_transactions_table;
    public $force_check_other_transactions_table;
    public $force_disable_home_link;
    public $force_disable_cashier_url;
    public $default_table_id;
    public $use_utils_get_url;

    const LOBBY_LIVE_DEALER = '101';
    const LOBBY_VIRTUAL_SPORTS = 'vplobby';

    public function __construct() {
        parent::__construct();
        $this->provider_id = $this->getSystemInfo('provider_id', 'PragmaticPlay');
        $this->launch_url = $this->getSystemInfo('launch_url');
		$this->trial_jurisdiction = $this->getSystemInfo('trial_jurisdiction', null);
        $this->use_game_api_method_game_launch = $this->getSystemInfo('use_game_api_method_game_launch', false);
        $this->use_transaction_data = $this->getSystemInfo('use_transaction_data', true);
        $this->show_hash_code = $this->getSystemInfo('show_hash_code', false);
        $this->allow_multi_session = $this->getSystemInfo('allow_multi_session', false);
        $this->use_sync_game_logs = $this->getSystemInfo('use_sync_game_logs', false);
        $this->use_api_game_result = $this->getSystemInfo('use_api_game_result', false);
        $this->bingo_room_url = $this->getSystemInfo('bingo_room_url');
        $this->lobby_url = $this->getSystemInfo('lobby_url');
        $this->mini_games_url = $this->getSystemInfo('mini_games_url');
        $this->mini_games_url_mobile = $this->getSystemInfo('mini_games_url_mobile');
        $this->use_monthly_transactions_table = $this->getSystemInfo('use_monthly_transactions_table', false);
        $this->force_check_previous_transactions_table = $this->getSystemInfo('force_check_previous_transactions_table', false);
        $this->force_check_other_transactions_table = $this->getSystemInfo('force_check_other_transactions_table', false);
        $this->default_table_id = $this->getSystemInfo('default_table_id', self::LOBBY_LIVE_DEALER); #101 live casino lobby,104 Baccarat Lobby, can base on excel
        $this->default_game_code = $this->getSystemInfo('default_game_code', $this->default_table_id);
        $this->force_disable_home_link = $this->getSystemInfo('force_disable_home_link', false);
        $this->force_disable_cashier_url = $this->getSystemInfo('force_disable_cashier_url', false);
        $this->use_end_round_round_finished_indicator = $this->getSystemInfo('use_end_round_round_finished_indicator', false);
        $this->use_utils_get_url = $this->getSystemInfo('use_utils_get_url', false);
        /*
            example:
            Game Name               Tables              Tables ID
            Boom City               Boom City           1401
            Fortune 6 Baccarat      Baccarat Lobby      104
                                    Fortune 6 Baccarat  434
            Super 8 Baccarat        Baccarat Lobby      104
                                    Super 8 Baccarat    433

         */

        $this->enable_merging_rows = $this->getSystemInfo('enable_merging_rows', true);
    }

    const BINGO_GAMES = [3825, 3729, 2004, 2003, 2002];

    // Fields in game_logs we want to detect changes for merge, and only available when original md5_sum is empty
    const MD5_FIELDS_FOR_MERGE = [
        'external_uniqueid',
        'bet_amount',
        'real_betting_amount',
        'result_amount',
        'round_number',
        'game_code',
        'game_name',
        'start_at',
        'end_at',
        'bet_at',
        'game_type_id'
    ];

    // Values of these fields will be rounded when calculating MD5
    const MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE = [
        'bet_amount',
        'real_betting_amount',
        'result_amount',
        'after_balance',
        'before_balance'
    ];


    public function isSeamLessGame(){
        return true;
    }

    public function getPlatformCode(){
        return $this->returnUnimplemented();
    }

    public function createPlayer($playerName, $playerId, $password, $email = null, $extra = null) {
        if(!$this->validateWhitePlayer($playerName)){
			$gamePlatformId = $this->getPlatformCode();
			$this->CI->utils->debug_log("PRAGMATIC_PLAY ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }

        $extra = [
            'prefix' => $this->prefix_for_username,

            # fix exceed game length name
            'fix_username_limit' => $this->fix_username_limit,
            'minimum_user_length' => $this->minimum_user_length,
            'maximum_user_length' => $this->maximum_user_length,
            'default_fix_name_length' => $this->default_fix_name_length,
            'check_username_only' => true
        ];

        $return = $this->createPlayerInDB($playerName, $playerId, $password, $email, $extra);
        $success = false;
        $message = "Unable to create account for Pragmatic Play";
        if($return){
            $success = true;
            $this->setGameAccountRegistered($playerId);
            $message = "Successfully created account for Pragmatic Play";
        }

        return array("success" => $success, "message" => $message);
    }

    public function isPlayerExist($playerName) {
        return ['success'=>true, 'exists'=>$this->isPlayerExistInDB($playerName)];
    }

    // will use default queryPlayerBalance in abstract
    /* public function queryPlayerBalance($playerName) {
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);

        $playerId = $this->getPlayerIdInGameProviderAuth($gameUsername);
        $balance = $this->CI->player_model->getPlayerSubWalletBalance($playerId, $this->getPlatformCode());

        $result = array(
            'success' => true,
            'balance' => $balance
        );

        return $result;
    } */

    public function depositToGame($userName, $amount, $transfer_secure_id=null){
        if(!$this->validateWhitePlayer($userName)){
			$gamePlatformId = $this->getPlatformCode();
			$this->CI->utils->debug_log("PRAGMATIC_PLAY ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $userName);
            return array('success' => false);
        }
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function withdrawFromGame($userName, $amount, $transfer_secure_id=null){
        if(!$this->validateWhitePlayer($userName)){
			$gamePlatformId = $this->getPlatformCode();
			$this->CI->utils->debug_log("PRAGMATIC_PLAY ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $userName);
            return array('success' => false);
        }
        $external_transaction_id = $transfer_secure_id;
        return array(
            'success' => true,
            'external_transaction_id' => $external_transaction_id,
            'transfer_status' => self::COMMON_TRANSACTION_STATUS_APPROVED,
            'response_result_id ' => NULL,
            'didnot_insert_game_logs'=> true,
        );
    }

    public function queryForwardGame($playerName,$extra=[]) {
        if(!$this->validateWhitePlayer($playerName)){
			$gamePlatformId = $this->getPlatformCode();
			$this->CI->utils->debug_log("PRAGMATIC_PLAY ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }

        if($this->use_game_api_method_game_launch){
            //Checked if from bingo games
            if(in_array($extra['game_code'], self::BINGO_GAMES)){
                $token = $this->getPlayerTokenByUsername($playerName);
                $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
                $language = $this->getLauncherLanguage(!empty($this->language) ? $this->language : $extra['language']);

                $game_lobby = $extra['is_mobile'] ? $this->mini_games_url_mobile :  $this->mini_games_url;
                
                $params = [
                    'token' => $token,
                    'room' => $extra['game_code'],
                    'language' => $language,
                    'cashierUrl' => $this->cashier_url,
                    'lobbyUrl' => $this->lobby_url,
                    'gamesLobby' => $game_lobby,
                    'currency' => $this->currency,                    
                ];

                if ($this->use_utils_get_url) {
                    $params['lobbyUrl'] = $this->utils->getUrl();
                }

                if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
                    unset($params['lobbyUrl']);
                }
        
                if($this->force_disable_home_link){
                    unset($params['lobbyUrl']);
                }

                if($this->force_disable_cashier_url){
                    unset($params['cashierUrl']);
                }
                    
                ksort($params);
                $param_build = '';
                foreach ($params as $key => $value) {
                    $param_build .= "&{$key}={$value}";
                }
                $param_build = trim($param_build, '&');
                
                $realUrl = $this->bingo_room_url . '?key='. urlencode($param_build) .'&stylename=' . $this->secureLogin;

                $this->CI->utils->debug_log('queryForwardGameBINGO', $realUrl);
                
                return [
                    'success' => true,
                    'url' => $realUrl
                ];
            }else{
                return $this->queryForwardGameGameAPIMethod($playerName,$extra);
            }
        }

        $token = $this->getPlayerTokenByUsername($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $language = $this->getLauncherLanguage(!empty($this->language) ? $this->language : $extra['language']);
        $platform = $extra['is_mobile'] ? "MOBILE" : "WEB";
        
       /*  if(isset($this->lobby_url) && !empty($this->lobby_url)) {
            $this->lobby_url = $this->getSystemInfo('lobby_url');
        } else {
        $this->lobby_url = $extra['is_mobile'] == 'true'
                        ? $this->utils->getSystemUrl('m') . $this->getSystemInfo('lobby_url')
                        : $this->utils->getSystemUrl('www') . $this->getSystemInfo('lobby_url');
        } */

        if (empty($this->lobby_url)) {
            $this->lobby_url = $extra['is_mobile'] == 'true'
            ? $this->utils->getSystemUrl('m') . $this->getSystemInfo('lobby_url')
            : $this->utils->getSystemUrl('www') . $this->getSystemInfo('lobby_url');
        }

        if (array_key_exists("extra", $extra)) {

            if(isset($extra['extra']['t1_lobby_url'])) {
                $this->lobby_url = $extra['extra']['t1_lobby_url'];
            }
            //extra checking for home link
            if(isset($extra['home_link']) && !empty($extra['home_link'])) {
                $this->lobby_url = $extra['home_link'];
            }
        }

        // if($extra['game_mode'] == "fun" || $extra['game_mode'] == "trial") {
        if(in_array($extra['game_mode'], $this->demo_game_identifier)){
            $demoUrl = $this->demo_url."?gameSymbol=".$extra['game_code']."&lang=".$language."&cur=".$this->currency."&lobbyURL=".$this->lobby_url;
            // trial_jurisdiction
            if($this->trial_jurisdiction !== null) {

                $demoUrl .= "&jurisdiction=".$this->trial_jurisdiction;
            }
            return array("success"=>true,"url"=>$demoUrl);
        }
        else{

            if(in_array($extra['game_code'], self::BINGO_GAMES)){

                $game_lobby = $extra['is_mobile'] ? $this->mini_games_url_mobile :  $this->mini_games_url;

                $params = [
                    'token' => $token,
                    'room' => $extra['game_code'],
                    'language' => $language,
                    'cashierUrl' => $this->cashier_url,
                    'lobbyUrl' => $this->lobby_url,
                    'gamesLobby' => $game_lobby,
                    'currency' => $this->currency,
                    
                ];
            }else{
                $params = [
                    'token' => $token,
                    'symbol' => $extra['game_code'],
                    'technology' => 'H5',
                    'platform' => $platform,
                    'language' => $language,
                    'lobbyUrl' => $this->lobby_url,
                ];
            }

            if ($this->use_utils_get_url) {
                $params['lobbyUrl'] = $this->utils->getUrl();
            }

            if (isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
                unset($params['lobbyUrl']);
            }
    
            if ($this->force_disable_home_link) {
                unset($params['lobbyUrl']);
            }

            if ($this->force_disable_cashier_url) {
                unset($params['cashierUrl']);
            }

            ksort($params);
            $param_build = '';
            foreach ($params as $key => $value) {
                $param_build .= "&{$key}={$value}";
            }
            $param_build = trim($param_build, '&');

            if(in_array($extra['game_code'], self::BINGO_GAMES)){
                $realUrl = $this->bingo_room_url . '?key='. urlencode($param_build) .'&stylename=' . $this->secureLogin;

            }else{
                $realUrl = $this->launch_url . '?key='. urlencode($param_build) .'&stylename=' . $this->secureLogin;
            }

            return [
                'success' => true,
                'url' => $realUrl
            ];
        }

    }

    public function queryForwardGameGameAPIMethod($playerName,$extra=[]) {
        if(!$this->validateWhitePlayer($playerName)){
			$gamePlatformId = $this->getPlatformCode();
			$this->CI->utils->debug_log("PRAGMATIC_PLAY ($gamePlatformId) using backend_api_white_player_list, failed to proceed", $playerName);
            return array('success' => false);
        }
        $this->CI->utils->debug_log('PRAGMATIC PLAY API FORWARD GAME playerName =========================>',$playerName);
        $token = $this->getPlayerTokenByUsername($playerName);
        $gameUsername = $this->getGameUsernameByPlayerUsername($playerName);
        $this->CI->utils->debug_log('PRAGMATIC PLAY API FORWARD GAME gameUsername =========================>',$gameUsername);
        $language = $this->getLauncherLanguage(!empty($this->language) ? $this->language : $extra['language']);
        $platform = $extra['is_mobile'] ? "MOBILE" : "WEB";
        $game_type = isset($extra['game_type']) ? $extra['game_type'] : null;

        /* if(isset($this->lobby_url) && !empty($this->lobby_url)) {
            $this->lobby_url = $this->getSystemInfo('lobby_url');
        } else {
        $this->lobby_url = $extra['is_mobile']
                        ? $this->utils->getSystemUrl('m') . $this->getSystemInfo('lobby_url')
                        : $this->utils->getSystemUrl('www') . $this->getSystemInfo('lobby_url');
        } */

        if (empty($this->lobby_url)) {
            $this->lobby_url = $extra['is_mobile']
            ? $this->utils->getSystemUrl('m') . $this->getSystemInfo('lobby_url')
            : $this->utils->getSystemUrl('www') . $this->getSystemInfo('lobby_url');
        }

        if (array_key_exists("extra", $extra)) {
            if(isset($extra['extra']['t1_lobby_url'])) {
                $this->lobby_url = $extra['extra']['t1_lobby_url'];
            }
            //extra checking for home link
            if(isset($extra['home_link']) && !empty($extra['home_link'])) {
                $this->lobby_url = $extra['home_link'];
            }
            //extra checking for home link
            if(isset($extra['cashier_link'])) {
                $this->cashier_url = $extra['cashier_link'];
            }
            //extra checking for home link
            if(isset($extra['extra']['home_link']) && !empty($extra['extra']['home_link'])) {
                $this->lobby_url = $extra['extra']['home_link'];
            }
            //extra checking for home link
            if(isset($extra['extra']['cashier_link'])) {
                $this->cashier_url = $extra['extra']['cashier_link'];
            }
        }

        $mode = "REAL";
        // if($extra['game_mode'] == "fun" || $extra['game_mode'] == "trial") {
        if(in_array($extra['game_mode'], $this->demo_game_identifier)){
            $mode = "DEMO";

            if(empty($gameUsername)){
                $gameUsername = $this->getSystemInfo('demo_username', uniqid());
            }
        }

        if(empty($extra['game_code'])){
            #empty game code, live dealer lobby

            switch ($game_type) {
                case self::GAME_TYPE_VIRTUAL_SPORTS:
                    $extra['game_code'] = self::LOBBY_VIRTUAL_SPORTS;
                    break;
                case self::GAME_TYPE_LIVE_DEALER:
                    $extra['game_code'] = $this->default_table_id;
                    break;
                default:
                    $extra['game_code'] = $this->default_game_code;
                    break;
            }
        } else{
            #_null game code, possible lobby
            if($extra['game_code'] == "_null"){
                switch ($game_type) {
                    case self::GAME_TYPE_VIRTUAL_SPORTS:
                        $extra['game_code'] = self::LOBBY_VIRTUAL_SPORTS;
                        break;
                    case self::GAME_TYPE_LIVE_DEALER:
                        $extra['game_code'] = $this->default_table_id;
                        break;
                    default:
                        $extra['game_code'] = $this->default_game_code;
                        break;
                }
            }
        }

        $context = array(
            'callback_obj' => $this,
            'callback_method' => 'processResultForQueryForwardGame',
            'language' => $language,
            'playerName' => $playerName,
            'gameUsername' => $gameUsername,
        );

        $params = [
            'secureLogin' => $this->secureLogin,
            'symbol' => $extra['game_code'],
            'language' => $language,
            'token' => $token,
            'externalPlayerId' => $gameUsername,
            'platform' => $platform,
            'technology' => 'H5',
            'stylename' => $this->secureLogin,
            'cashierUrl' => $this->cashier_url,
            'lobbyUrl' => $this->lobby_url,
            'playMode' => $mode,
        ];

        if ($this->use_utils_get_url) {
            $params['lobbyUrl'] = $this->utils->getUrl();
        }

        if(isset($extra['extra']['disable_home_link']) && $extra['extra']['disable_home_link']) {
            unset($params['lobbyUrl']);
        }

        if($this->force_disable_home_link){
            unset($params['lobbyUrl']);
        }

        if($this->force_disable_cashier_url){
            unset($params['cashierUrl']);
        }
        
        if ($mode == 'DEMO') {
            unset($params['token']);
        }

        ksort($params);
        $param_build = '';
        foreach ($params as $key => $value) {
            $param_build .= "&{$key}={$value}";
        }
        $param_build = trim($param_build, '&');
        $params['hash'] = MD5($param_build.$this->secretKey);
        $this->CI->utils->debug_log('PRAGMATIC PLAY API FORWARD GAME =========================>',$params);

        return $this->callApi(self::API_queryForwardGame2, $params, $context);
    }

    public function logout($userName, $password = null) {
        return $this->returnUnimplemented();
    }

    public function queryTransaction($transactionId, $extra) {
        return $this->returnUnimplemented();
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

    /**
     * overview : get game time to server time
     *
     * @return string
     */
    /*public function getGameTimeToServerTime() {
        // return '+8 hours';
    }*/

    /**
     * overview : get server time to game time
     *
     * @return string
     */
    /*public function getServerTimeToGameTime() {
        // return '-8 hours';
    }*/

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

    public function changePassword($playerName, $oldPassword = null, $newPassword) {
        return $this->returnUnimplemented();
    }


    public function syncOriginalGameLogs($token = false)
    {
        if($this->use_transaction_data) {
            return $this->returnUnimplemented();
        } else {
            parent::syncOriginalGameLogs($token);
        }
    }

    public function syncMergeToGameLogs($token = false)
    {

        $enabled_game_logs_unsettle = $this->getSystemInfo('enabled_game_logs_unsettle', true);

        if(!$this->enable_merging_rows){
            return $this->commonSyncMergeToGameLogs($token,
                    $this,
                    [$this, 'queryOriginalGameLogsFromTrans'],
                    [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTrans'],
                    [$this, 'preprocessOriginalRowForGameLogsFromTrans'],
                    $enabled_game_logs_unsettle);
        }

        if($this->use_transaction_data) {

            return $this->commonSyncMergeToGameLogs($token,
                    $this,
                    [$this, 'queryOriginalGameLogsFromTransMerge'],
                    [$this, 'makeParamsForInsertOrUpdateGameLogsRowFromTransMerge'],
                    [$this, 'preprocessOriginalRowForGameLogsFromTransMerge'],
                    $enabled_game_logs_unsettle);

        } else {

            parent::syncMergeToGameLogs($token);
        }
    }

    public function preprocessOriginalRowForGameLogsFromTransMerge(array &$row){

		if (empty($row['game_description_id'])) {
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        if ($row['transaction_type'] == 'cancel') {
            // current_table
            $rollback_after_balance = floatval($this->queryAfterBalanceByType('rollback', $row['player_username'], $row['game_code'], $row['round_number']));

            if ($this->checkPreviousSeamlessWalletTransactionsTable()) {
                if (empty($rollback_after_balance)) {
                    // previous_table
                    $rollback_after_balance = floatval($this->queryAfterBalanceByType('rollback', $row['player_username'], $row['game_code'], $row['round_number'], $this->getPreviousSeamlessWalletTransactionsTable()));
                }
            }

            $row['after_balance'] = $rollback_after_balance;
            $row['status'] = Game_logs::STATUS_REFUND;
        } else {
            $row['status'] = Game_logs::STATUS_SETTLED;

            $credit_after_balance = floatval($this->queryAfterBalanceByType('credit', $row['player_username'], $row['game_code'], $row['round_number']));
            if ($this->checkPreviousSeamlessWalletTransactionsTable()) {
                if (empty($credit_after_balance)) {
                    // previous_table
                    $credit_after_balance = floatval($this->queryAfterBalanceByType('credit', $row['player_username'], $row['game_code'], $row['round_number'], $this->getPreviousSeamlessWalletTransactionsTable()));
                }
            }

            $credit_after_balance = $credit_after_balance ? $credit_after_balance : ($row['before_balance'] + $row['result_amount']);

            $row['after_balance'] = $credit_after_balance;
            if($this->use_end_round_round_finished_indicator && isset($row['is_round_finished'])){
                if($row['is_round_finished']==true){
                    $row['status'] = Game_logs::STATUS_SETTLED;
                }else{
                    $row['status'] = Game_logs::STATUS_PENDING;
                }

            }
        }
	}

    /* queryOriginalGameLogsFromTrans
     * @param  string $dateFrom
     * @param  string $dateTo
     * @param  bool   $use_bet_time
     * @return array
     */
    public function queryOriginalGameLogsFromTransMerge($dateFrom, $dateTo, $use_bet_time){
        $table_name = $this->getSeamlessWalletTransactionsTableByDate($dateFrom);
        $gameRecords = $this->getDataFromTrans($dateFrom, $dateTo, $use_bet_time, $table_name);
        $rebuildGameRecords = array();
        $this->CI->load->model(array('original_game_logs_model'));
        $this->CI->original_game_logs_model->removeDuplicateUniqueid($gameRecords, 'external_uniqueid', function($row1st, $row2nd){return 2;});
        $this->processGameRecordsFromTrans($gameRecords, $rebuildGameRecords);

        return $rebuildGameRecords;

    }

    public function getDataFromTrans($dateFrom, $dateTo, $use_bet_time = false, $table_name = null) {
        #query bet only
        // $sqlTime = "pp.updated_at >= ? AND pp.updated_at <= ? AND pp.transaction_type in ('debit', 'cancel')";
        $sqlTime = "pp.updated_at >= ? AND pp.updated_at <= ?";

        if ($use_bet_time) {
            // $sqlTime = "pp.created_at >= ? AND pp.created_at <= ? AND pp.transaction_type in ('debit', 'cancel')";
            $sqlTime = "pp.created_at >= ? AND pp.created_at <= ?";
        }

        if (empty($table_name)) {
            $table_name = $this->getSeamlessWalletTransactionsTable();
        }

        $md5Fields = implode(", ", array('pp.amount', 'pp.updated_at', 'gd.game_type_id'));

        $sql = <<<EOD
SELECT
pp.id,
pp.id as sync_index,
pp.response_result_id,
CONCAT(pp.round_id, pp.user_id) as external_uniqueid,
pp.user_id as player_username,
gpa.player_id,
pp.amount as bet_amount,
pp.amount as real_betting_amount,
pp.game_id as game_code,
pp.round_id as round_number,
pp.round_details as bet_details,
pp.created_at as start_at,
pp.created_at as end_at,
pp.created_at as bet_at,
pp.updated_at,
pp.before_balance,
pp.after_balance,
pp.transaction_type,
pp.transaction_id,
MD5(CONCAT({$md5Fields})) as md5_sum,
gd.id as game_description_id,
gd.game_type_id,
gd.game_type_id,
gd.game_name as game_name,
gd.game_name as game_description_name,
gd.game_type_id,
gd.english_name as game_english_name

FROM {$table_name} as pp
LEFT JOIN game_description as gd ON pp.game_id = gd.external_game_id AND gd.game_platform_id = ?
JOIN game_provider_auth gpa on gpa.login_name = pp.user_id and gpa.game_provider_id = ?
WHERE
{$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $this->CI->utils->debug_log(__METHOD__ . ' ===========================> sql and params - ' . __LINE__, $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;

    }

    public function getDebitOrCancelFromTransByRound($player_username, $round_id, $table_name = null) {
        if (empty($table_name)) {
            $table_name = $this->getSeamlessWalletTransactionsTable();
        }

        $md5Fields = implode(", ", array('pp.amount', 'pp.updated_at', 'gd.game_type_id'));

        $sql = <<<EOD
SELECT
pp.id,
pp.id as sync_index,
pp.response_result_id,
CONCAT(pp.round_id, pp.user_id) as external_uniqueid,
pp.user_id as player_username,
gpa.player_id,
pp.amount as bet_amount,
pp.amount as real_betting_amount,
pp.game_id as game_code,
pp.round_id as round_number,
pp.round_details as bet_details,
pp.created_at as start_at,
pp.created_at as end_at,
pp.created_at as bet_at,
pp.updated_at,
pp.before_balance,
pp.after_balance,
pp.transaction_type,
pp.transaction_id,
MD5(CONCAT({$md5Fields})) as md5_sum,
gd.id as game_description_id,
gd.game_type_id,
gd.english_name as game_english_name

FROM {$table_name} as pp
LEFT JOIN game_description as gd ON pp.game_id = gd.external_game_id AND gd.game_platform_id = ?
JOIN game_provider_auth gpa on gpa.login_name = pp.user_id and gpa.game_provider_id = ?
WHERE
pp.user_id = ? AND pp.round_id = ? AND pp.transaction_type in ('debit', 'cancel')
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $player_username,
            $round_id
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);

        #check if there are multiple bet on a round
        if(count($result) > 1){
            #iterate each record and check if there is a valid bet(one valid bet is enough to consider the round as valid; same with BO)
            foreach ($result as $key => $value) {
                if($value['transaction_type'] == 'debit'){
                    return $value;
                }
            }

        }

        return $result[0];

    }

    public function processGameRecordsFromTrans(&$gameRecords, &$rebuildGameRecords) {

        $transaction_ids = array();

        if(!empty($gameRecords)) {
            foreach ($gameRecords as $index => $record) {
                $transaction_type = isset($record['transaction_type']) ? $record['transaction_type'] : null;

                if (!in_array($transaction_type, ['debit', 'cancel'])) {
                    $currentRecord = $this->getDebitOrCancelFromTransByRound($record['player_username'], $record['round_number']);
                    $previousRecord = [];

                    if ($this->use_monthly_transactions_table) {
                        if ($this->checkPreviousSeamlessWalletTransactionsTable()) {
                            if (empty($currentRecord)) {
                                $previousRecord = $this->getDebitOrCancelFromTransByRound($record['player_username'], $record['round_number'], $this->getPreviousSeamlessWalletTransactionsTable());
                            }
                        }
                    }

                    $record = !empty($currentRecord) ? $currentRecord : $previousRecord;
                    $transaction_type = isset($record['transaction_type']) ? $record['transaction_type'] : null;
                }

                if (in_array($transaction_type, ['debit', 'cancel'])) {
                    $temp_game_records = $record;
                    $temp_game_records['player_id'] = isset($record['player_id']) ? $record['player_id'] : null;
                    $temp_game_records['sync_index'] = isset($record['sync_index']) ? $record['sync_index'] : null;
                    $temp_game_records['player_username'] = isset($record['player_username']) ? $record['player_username'] : null;

                    $temp_game_records['game_code'] = isset($record['game_code']) ? $record['game_code'] : null;
                    $temp_game_records['game_name'] = isset($record['game_code']) ? $record['game_code'] : null;
                    $temp_game_records['round_number'] = isset($record['round_number']) ? $record['round_number'] : null;
                    $temp_game_records['external_uniqueid'] = isset($record['external_uniqueid']) ? $record['external_uniqueid'] : null;
                    $temp_game_records['response_result_id'] = isset($record['response_result_id']) ? $record['response_result_id'] : null;
                    // $temp_game_records['md5_sum'] = isset($record['md5_sum']) ? $record['md5_sum'] : null;
                    $temp_game_records['start_at'] = isset($record['start_at']) ? $record['start_at'] : null;
                    $temp_game_records['bet_at'] = isset($record['bet_at']) ? $record['bet_at'] : null;
                    $temp_game_records['end_at'] = isset($record['end_at']) ? $record['end_at'] : null;

                    $temp_game_records['updated_at'] = isset($record['updated_at']) ? $record['updated_at'] : null;
                    $temp_game_records['before_balance'] = isset($record['before_balance']) ? $record['before_balance'] : null;
                    // $temp_game_records['after_balance'] = isset($record['after_balance']) ? $record['after_balance'] : null;

                    // before_balance and after_balance because bet and (result) is in separate transaction.

                    $temp_game_records['transaction_type'] = isset($record['transaction_type']) ? $record['transaction_type'] : null;

                    $bet_amount = null;
                    if(isset($record["bet_amount"])) {
                        if($record["bet_amount"] < 0) {
                            $bet_amount = -$record["bet_amount"];
                        } else {
                            $bet_amount = $record["bet_amount"];
                        }
                    }

                    if(strtolower($record['transaction_type']) == 'debit'){
                        #try get all bet amount by sum
                        // current_table
                        $total_bet_amount = $this->queryTotalBetAmountByRound($record['round_number'],$record['player_username']);
                        if($total_bet_amount){
                            $bet_amount = $total_bet_amount;
                        } else {
                            if ($this->checkPreviousSeamlessWalletTransactionsTable()) {
                                // previous_table
                                $total_bet_amount = $this->queryTotalBetAmountByRound($record['round_number'], $record['player_username'], $this->getPreviousSeamlessWalletTransactionsTable());
                                
                                if ($total_bet_amount) {
                                    $bet_amount = $total_bet_amount;
                                }
                            }
                        }
                    }

                    $temp_game_records['bet_amount'] = $bet_amount;
                    $temp_game_records['real_betting_amount'] = $bet_amount;
                    $temp_game_records['is_round_finished'] = false;

                    $bet_results = $this->queryBetResults($record['round_number'],$record['player_username']);

                    if ($this->checkPreviousSeamlessWalletTransactionsTable()) {
                        if (!$bet_results) {
                            // previous_table
                            $bet_results = $this->queryBetResults($record['round_number'],$record['player_username'], $this->getPreviousSeamlessWalletTransactionsTable());
                        }
                    }

                    if($bet_results) {
                        $temp_game_records['result_amount'] = round($bet_results['result_amount'] - $bet_amount, 2);
                        $temp_game_records['end_at'] = $bet_results['end_at'];
                        if(isset($bet_results['after_balance'])){
                            $temp_game_records['after_balance'] = $bet_results['after_balance'];
                        }
                        if(isset($bet_results['is_round_finished'])&&$bet_results['is_round_finished']==true){
                            $temp_game_records['is_round_finished'] = true;
                        }
                    } else {
                        // $temp_game_records['result_amount'] = isset($record['bet_amount']) ? $record['bet_amount'] : null;
                        $temp_game_records['result_amount'] = isset($bet_amount) ? -$bet_amount : null; #use bet amount variable incase for double debit on round but lose
                    }

                    if (is_null($temp_game_records['after_balance'])) {
                        $temp_game_records['after_balance'] = $temp_game_records['before_balance'] - abs($bet_amount);
                    }

                    $temp_game_records['bet_details'] = isset($record['bet_details']) ? $record['bet_details'] : null;
                    //$temp_game_records['md5_sum'] =  $this->CI->original_game_logs_model->generateMD5SumOneRow($temp_game_records, ['transaction_type'], ['bet_amount', 'result_amount', 'updated_at']);

                    // $gameRecords[$index] = $temp_game_records;
                    $rebuildGameRecords[] = $temp_game_records;
                    unset($data);
                }
            }

        }

    }

    private function queryTotalBetAmountByRound($round_number,$game_username, $table_name = null){
        $this->CI->load->model('original_game_logs_model');

        if (empty($table_name)) {
            $table_name = $this->getSeamlessWalletTransactionsTable();
        }

        $sqlRound="pp.round_id = ? AND pp.user_id = ? AND pp.transaction_type = 'debit'";

        $sql = <<<EOD
SELECT
sum(ABS(pp.amount)) as total_bet
FROM {$table_name} as pp
WHERE
{$sqlRound}
EOD;
        $params=[
            $round_number,
            $game_username
        ];

        $this->CI->utils->debug_log('queryTotalBetAmountByRound sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        if(isset($result['total_bet'])){
            return $result['total_bet'];
        }
        return null;
    }

    public function getGameResultFromAPI($round_id)
    {
        $sql = <<<EOD
SELECT
sbeplayerid,
username,
gameid,
bet,
win,
start_date,
end_date,
status,
type_game_round,
external_uniqueid
FROM {$this->original_logs_table_name}
WHERE external_uniqueid = ?
EOD;
        $params = [
            $round_id,
        ];

        $results = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);

        return $results;
    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTransMerge(array $row)
    {
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
            self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $bet_amount = $this->gameAmountToDB($row['bet_amount']);
        $result_amount = $this->gameAmountToDB($row['result_amount']);

        if ($this->use_api_game_result) {
            $game_result = $this->getGameResultFromAPI($row['round_number']);

            if (isset($game_result['external_uniqueid']) && !empty($game_result['external_uniqueid'])) {
                $bet_amount = $this->gameAmountToDB($game_result['bet']);
                $compute_result = $game_result['win'] - $game_result['bet'];
                $result_amount = $this->gameAmountToDB($compute_result);
            }
        }

        $data = [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount,
                'bet_for_cashback' => $bet_amount,
                'real_betting_amount' => $bet_amount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance']
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $this->formatBetDetails($row),
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;
    }


    public function queryBetResults($round_id, $player_name, $table_name = null) {
        if (empty($table_name)) {
            $table_name = $this->getSeamlessWalletTransactionsTable();
        }

        $sqlTime="pp.round_id = ? and pp.user_id = ? and pp.transaction_type not in ('debit', 'cancel', 'rollback')";

        $sql = <<<EOD
SELECT
pp.id as sync_index,
pp.amount,
pp.created_at as end_at,
pp.after_balance,
pp.transaction_type
FROM {$table_name} as pp
WHERE
{$sqlTime}
EOD;

            $params=[
                $round_id,
                $player_name
            ];
            $this->CI->utils->debug_log('merge sql', $sql, $params);

            $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);


            return $this->processResultAmount($result);
    }

    public function queryAfterBalanceByType($transaction_type, $user_id, $game_id, $round_id, $table_name = null)
    {
        if (empty($table_name)) {
            $table_name = $this->getSeamlessWalletTransactionsTable();
        }

        $where = "transaction_type = ? and user_id = ? and game_id = ? and round_id = ?";

        $sql = <<<EOD
SELECT
after_balance
FROM {$table_name}
WHERE
{$where}
EOD;

        $params = [
            $transaction_type,
            $user_id,
            $game_id,
            $round_id,
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);
        $result = $this->CI->original_game_logs_model->commonGetOneOriginalGameLogs($sql, $params);
        $after_balance = isset($result['after_balance']) && !empty($result['after_balance']) ? $result['after_balance'] : 0;

        return $after_balance;
}

    private function processResultAmount($datas) {

        $result = false;

        if(!empty($datas)){


            $total_result_amount = 0;
            $end_at = null;
            $after_balance = 0;
            $roundFinished = false;

            foreach($datas as $data) {
                $total_result_amount += $data["amount"];
                $end_at = $data["end_at"];
                $after_balance = $data["after_balance"];

                if($data['transaction_type']=='endRound'){
                    $roundFinished = true;
                }
            }

            $result = array(
                                "result_amount" => $total_result_amount,
                                "end_at" => $end_at,
                                "after_balance" => $after_balance,
                                "is_round_finished" => $roundFinished
                            );



        }
        return $result;
    }

    public function queryTransactionByDateTime($startDate, $endDate, $table_name = null){
        $this->CI->load->model(array('original_game_logs_model'));

        if (empty($table_name)) {
            $table_name = $this->getSeamlessWalletTransactionsTable();
        }

$sql = <<<EOD
SELECT
gpa.player_id as player_id,
t.created_at transaction_date,
t.amount as amount,
t.after_balance as after_balance,
t.before_balance as before_balance,
t.round_id as round_no,
t.external_uniqueid as external_uniqueid,
t.transaction_type trans_type
FROM {$table_name} as t
JOIN game_provider_auth gpa on gpa.login_name = t.user_id
WHERE `t`.`updated_at` >= ? AND `t`.`updated_at` <= ? and t.transaction_type <> 'bonus-no-c'
ORDER BY t.updated_at asc;

EOD;

$params=[$startDate, $endDate];

        $result = $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
        return $result;
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

    public function create_seamless_wallet_transactions_year_month_table($table_name, $yearMonth = null) {
        if (!empty($yearMonth)) {
            $table_name .= '_' . $yearMonth;
        } else {
            $table_name .= '_' . $this->utils->getThisYearMonth();
        }

        if (!$this->utils->table_really_exists($table_name)) {
            try {
                $this->CI->load->dbforge();

                $fields = [
                    'id' => [
                        'type' => 'BIGINT',
                        'null' => false,
                        'auto_increment' => true,
                    ],
                    'user_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '30',
                        'null' => true,
                    ],
                    'transaction_type' => [
                        'type' => 'VARCHAR',
                        'constraint' => '10',
                        'null' => true,
                    ],
                    'transaction_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true,
                    ],
                    'game_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '30',
                        'null' => true,
                    ],
                    'round_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '100',
                        'null' => true,
                    ],
                    'amount' => [
                        'type' => 'DOUBLE',
                        'null' => true,
                    ],
                    'before_balance' => [
                        'type' => 'DOUBLE',
                        'null' => true,
                    ],
                    'after_balance' => [
                        'type' => 'DOUBLE',
                        'null' => true,
                    ],
                    'bonus_code' => [
                        'type' => 'VARCHAR',
                        'constraint' => '10',
                        'null' => true,
                    ],
                    'provider_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true,
                    ],
                    'timestamp' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                    'settled_at' => [
                        'type' => 'DATETIME',
                        'null' => true,
                    ],
                    'round_details' => [
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true,
                    ],
                    'currency' => [
                        'type' => 'VARCHAR',
                        'constraint' => '10',
                        'null' => true,
                    ],
                    'campaign_type' => [
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true,
                    ],
                    'campaign_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '50',
                        'null' => true,
                    ],
                    'jackpot_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '100',
                        'null' => true,
                    ],
                    'request_id' => [
                        'type' => 'VARCHAR',
                        'constraint' => '64',
                        'null' => true,
                    ],
                    'response_result_id' => [
                        'type' => 'INT',
                        'null' => true
                    ],
                    'external_uniqueid' => [
                        'type' => 'VARCHAR',
                        'constraint' => '150',
                        'null' => true
                    ],
                    'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' => [
                        'null' => false,
                    ],
                    'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' => [
                        'null' => false,
                    ],
                ];

                $this->CI->dbforge->add_field($fields);
                $this->CI->dbforge->add_key('id', TRUE);
                $this->CI->dbforge->create_table($table_name);

                # Add Index
                $this->CI->load->model('player_model');
                $this->CI->player_model->addIndex($table_name,'idx_user_id','user_id');
                $this->CI->player_model->addIndex($table_name,'idx_game_id','game_id');
                $this->CI->player_model->addIndex($table_name,'idx_round_id','round_id');
                $this->CI->player_model->addIndex($table_name,'idx_transaction_type','transaction_type');
                $this->CI->player_model->addIndex($table_name,'idx_transaction_id','transaction_id');
                $this->CI->player_model->addIndex($table_name,'idx_timestamp','timestamp');
                $this->CI->player_model->addIndex($table_name,'idx_settled_at','settled_at');
                $this->CI->player_model->addIndex($table_name,'idx_created_at','created_at');
                $this->CI->player_model->addIndex($table_name,'idx_updated_at','updated_at');
                $this->CI->player_model->addUniqueIndex($table_name, 'idx_external_uniqueid', 'external_uniqueid');

            } catch(Exception $e) {
                $this->utils->error_log('create table failed: ' . $table_name, $e);
            }
        }

        return $table_name;
    }

    public function getTransactionsTable(){
        return $this->getSeamlessWalletTransactionsTable();
    }

    public function getSeamlessWalletTransactionsTable() {
        $table_name = $this->transaction_table_name;

        if ($this->use_monthly_transactions_table) {
            $table_name = $this->create_seamless_wallet_transactions_year_month_table($table_name);
            // create next month
            $this->getNextSeamlessWalletTransactionsTable();
        }

        return $table_name;
    }

    public function getPreviousSeamlessWalletTransactionsTable() {
        $table_name = $this->transaction_table_name;
        $yearMonth = $this->utils->getLastYearMonth();

        if ($this->use_monthly_transactions_table) {
            $table_name = $this->create_seamless_wallet_transactions_year_month_table($table_name, $yearMonth);
        }

        return $table_name;
    }

    public function getNextSeamlessWalletTransactionsTable() {
        $table_name = $this->transaction_table_name;
        $yearMonth = $this->utils->getNextYearMonth();

        if ($this->use_monthly_transactions_table) {
            $table_name = $this->create_seamless_wallet_transactions_year_month_table($table_name, $yearMonth);
        }

        return $table_name;
    }

    public function checkPreviousSeamlessWalletTransactionsTable() {
        if ($this->use_monthly_transactions_table) {
            if ($this->force_check_previous_transactions_table) {
                return true;
            }

            if ($this->utils->isFirstDateOfCurrentMonth()) {
                return true;
            }
        }

        return false;
    }

    public function getSeamlessWalletTransactionsTableByDate($date) {
        $table_name = $this->getSeamlessWalletTransactionsTable();

        if ($this->use_monthly_transactions_table) {
            if ($this->checkPreviousSeamlessWalletTransactionsTable()) {
                if ($this->utils->table_really_exists($table_name)) {
                    $table_name = $this->original_transaction_table_name . '_' . date('Ym', strtotime($date));
                }
            }
        }

        return $table_name;
    }

    public function formatBetDetails($data){
        $bet_details = [];
        if($data){
            $bet_details = [
                'bet_amount'        => $data['bet_amount'],
                'win_amount'        => $data['result_amount'],
                'round_id'          => $data['round_number'],
                'game_name'         => $data['game_name'],
                'event_datetime'   => $data['bet_at'],
                // 'others'            => $data
                
                'game_name' => $data['game_english_name'],
                'bet_type' => $data['bet_details'],
                'bet_id' => $data['round_number'],
                'betting_time' => $data['start_at'],
            ];

        }
        return $bet_details;
    }

    ##### SYNC MERGED GAMELOGS METHOD
    
    public function queryOriginalGameLogsFromTrans($dateFrom, $dateTo, $use_bet_time) {
        $currentTable = $this->getSeamlessWalletTransactionsTableByDate($dateFrom);

        $currentTableData = $this->queryOriginalGameLogsWithTable($currentTable, $dateFrom, $dateTo, $use_bet_time);        

        $this->CI->utils->debug_log("PP SEAMLESS: (queryOriginalGameLogsFromTrans) tables used", 'currentTable', $currentTable);
        $prevTableData = [];
        
        $prevTable = $this->getPreviousSeamlessWalletTransactionsTable();    
        
        if($this->checkPreviousSeamlessWalletTransactionsTable() && ($currentTable<>$prevTable)){            
            $this->CI->utils->debug_log("PP SEAMLESS: (queryOriginalGameLogsFromTrans) tables used", 'prevTable', $prevTable);
            $prevTableData = $this->queryOriginalGameLogsWithTable($prevTable, $dateFrom, $dateTo, $use_bet_time);                               
        }
        $gameRecords = array_merge($currentTableData, $prevTableData);        
        $this->CI->original_game_logs_model->removeDuplicateUniqueid($gameRecords, 'external_uniqueid', function($row1st, $row2nd){return 2;});
        
        return $gameRecords;
    }

    public function queryOriginalGameLogsWithTable($table_name, $dateFrom, $dateTo, $use_bet_time){
        
        $sqlTime = "pp.updated_at >= ? AND pp.updated_at <= ?";

        if ($use_bet_time) {
            $sqlTime = "pp.created_at >= ? AND pp.created_at <= ?";
        }

        if (empty($table_name)) {
            $table_name = $this->getSeamlessWalletTransactionsTable();
        }

        $md5Fields = implode(", ", array('pp.amount', 'pp.updated_at', 'gd.game_type_id', 'pp.transaction_type', 'pp.external_uniqueid'));

        $sql = <<<EOD
SELECT
pp.id,
pp.id as sync_index,
pp.response_result_id,
pp.external_uniqueid,
pp.user_id as player_username,
gpa.player_id,
IF(pp.transaction_type='debit', pp.amount, 0) as bet_amount,
IF(pp.transaction_type='debit', pp.amount, 0) as real_betting_amount,
IF(pp.transaction_type='credit', pp.amount, 0) as win_amount,
IF(pp.transaction_type='cancel', pp.amount, 0) as bet_cancel_amount,
IF(pp.transaction_type='rollback', pp.amount, 0) as bet_rollback_amount,
pp.game_id as game_code,
pp.round_id as round_number,
pp.round_details as bet_details,
pp.created_at as start_at,
pp.created_at as end_at,
pp.created_at as bet_at,
pp.updated_at,
pp.before_balance,
pp.after_balance,
pp.transaction_type,
pp.transaction_id,
MD5(CONCAT({$md5Fields})) as md5_sum,
gd.id as game_description_id,
gd.game_type_id,
gd.game_name as game_name,
gd.game_name as game_description_name,
gd.game_type_id,
gd.english_name as game_english_name

FROM {$table_name} as pp
LEFT JOIN game_description as gd ON pp.game_id = gd.external_game_id AND gd.game_platform_id = ?
JOIN game_provider_auth gpa on gpa.login_name = pp.user_id and gpa.game_provider_id = ?
WHERE
{$sqlTime}
EOD;

        $params=[
            $this->getPlatformCode(),
            $this->getPlatformCode(),
            $dateFrom,
            $dateTo
        ];

        $this->CI->utils->debug_log('merge sql', $sql, $params);

        return $this->CI->original_game_logs_model->commonGetOriginalGameLogs($sql, $params);
    }

    public function makeParamsForInsertOrUpdateGameLogsRowFromTrans(array $row)
    {
        if(empty($row['md5_sum'])){
            $row['md5_sum']=$this->CI->game_logs->generateMD5SumOneRow($row, self::MD5_FIELDS_FOR_MERGE,
            self::MD5_FLOAT_AMOUNT_FIELDS_FOR_MERGE);
        }

        $bet_amount = $this->gameAmountToDB(abs($row['bet_amount']));
        $win_amount = $this->gameAmountToDB(abs($row['win_amount']));
        if($row['transaction_type']=='cancel'){
            $bet_amount = $this->gameAmountToDB(abs($row['bet_cancel_amount']));
        }
        if($row['transaction_type']=='rollback'){
            $bet_amount = $this->gameAmountToDB(abs($row['bet_rollback_amount']));
        }

        $row['result_amount'] = $result_amount = $win_amount - $bet_amount;

        $data = [
            'game_info' => [
                'game_type_id' => $row['game_type_id'],
                'game_description_id' => $row['game_description_id'],
                'game_code' => $row['game_code'],
                'game_type' => null,
                'game' => $row['game_code']
            ],
            'player_info' => [
                'player_id' => $row['player_id'],
                'player_username' => $row['player_username']
            ],
            'amount_info' => [
                'bet_amount' => $bet_amount,
                'result_amount' => $result_amount,
                'bet_for_cashback' => $bet_amount,
                'real_betting_amount' => $bet_amount,
                'win_amount' => null,
                'loss_amount' => null,
                'after_balance' => $row['after_balance']
            ],
            'date_info' => [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'],
                'bet_at' => $row['bet_at'],
                'updated_at' => $this->CI->utils->getNowForMysql(),
            ],
            'flag' => Game_logs::FLAG_GAME,
            'status' => $row['status'],
            'additional_info' => [
                'has_both_side' => 0,
                'external_uniqueid' => $row['external_uniqueid'],
                'round_number' => $row['round_number'],
                'md5_sum' => $row['md5_sum'],
                'response_result_id' => $row['response_result_id'],
                'sync_index' => $row['sync_index'],
                'bet_type' => null
            ],
            'bet_details' => $this->formatBetDetails($row),
            'extra' => [],
            //from exists game logs
            'game_logs_id'=>isset($row['game_logs_id']) ? $row['game_logs_id'] : null,
            'game_logs_unsettle_id'=>isset($row['game_logs_unsettle_id']) ? $row['game_logs_unsettle_id'] : null,
        ];

        return $data;
    }

    public function preprocessOriginalRowForGameLogsFromTrans(array &$row){

		if (empty($row['game_description_id'])) {
            list($game_description_id,$game_type_id) = $this->getGameDescriptionInfo($row, $this->getUnknownGame());
            $row['game_description_id']= $game_description_id;
            $row['game_type_id'] = $game_type_id;
        }

        $row['status'] = Game_logs::STATUS_SETTLED;

        if ($row['transaction_type'] == 'cancel') {
            $row['status'] = Game_logs::STATUS_CANCELLED;
        }

        if ($row['transaction_type'] == 'rollback') {
            $row['status'] = Game_logs::STATUS_REFUND;
        }
	}

    ##### END SYNC MERGED GAMELOGS METHOD

    #OGP-34427
    public function getProviderAvailableLanguage() {
        return $this->getSystemInfo('provider_available_langauge', ['en','zh-cn','id-id','vi-vi','ko-kr','th-th','pt']);
    }
}

/*end of file*/